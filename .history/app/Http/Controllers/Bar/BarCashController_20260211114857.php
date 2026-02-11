<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarCashSession;
use App\Models\Bar\BarCashMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BarCashController extends Controller
{
    /**
     * Tela Principal do Caixa
     */
    public function index(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $user = auth()->user();

        // 1. BUSCA A SESSÃO ATIVA AGORA
        $openSession = BarCashSession::where('status', 'open')->first();

        // 🚩 LÓGICA DE CAIXA VENCIDO: Verifica se o caixa aberto é de uma data anterior
        $caixaVencido = false;
        if ($openSession) {
            // Comparamos a data de abertura (sem as horas) com a data de hoje
            $dataAbertura = \Carbon\Carbon::parse($openSession->opened_at)->startOfDay();
            $hoje = \Carbon\Carbon::today();

            if ($dataAbertura->lt($hoje)) {
                $caixaVencido = true;
            }
        }

        // 2. BUSCA A SESSÃO PARA EXIBIÇÃO NO HISTÓRICO
        $currentSession = ($openSession && \Carbon\Carbon::parse($openSession->opened_at)->format('Y-m-d') == $date)
            ? $openSession
            : BarCashSession::whereDate('opened_at', $date)->latest()->first();

        // 🛡️ TRAVA DE SEGURANÇA: Contagem de mesas com status real 'occupied'
        $mesasAbertasCount = \App\Models\Bar\BarTable::where('status', 'occupied')->count();

        // 3. MOVIMENTAÇÕES
        $movements = collect();
        $allMovements = collect();

        if ($currentSession) {
            $allMovements = BarCashMovement::with(['user', 'barOrder.table'])
                ->where('bar_cash_session_id', $currentSession->id)
                ->get();

            if (!in_array($user->role, ['admin', 'gestor'])) {
                $movements = $allMovements->where('user_id', $user->id);
            } else {
                $movements = $allMovements;
            }

            $movements = $movements->sortByDesc('created_at');
        }

        // 4. CÁLCULOS FINANCEIROS TOTAIS
        $reforcos = $allMovements->where('type', 'reforco')->where('payment_method', 'dinheiro')->sum('amount');
        $vendasDinheiro = $allMovements->where('type', 'venda')->where('payment_method', 'dinheiro')->sum('amount');
        $vendasDigital = $allMovements->where('type', 'venda')->whereIn('payment_method', ['pix', 'credito', 'debito'])->sum('amount');
        $sangriasDinheiro = $allMovements->where('type', 'sangria')->where('payment_method', 'dinheiro')->sum('amount');
        $sangriasDigital = $allMovements->where('type', 'sangria')->whereIn('payment_method', ['pix', 'credito', 'debito'])->sum('amount');

        $faturamentoDigital = $vendasDigital - $sangriasDigital;
        $saldoInicialSessao = $currentSession ? $currentSession->opening_balance : 0;
        $dinheiroGeral = $saldoInicialSessao + $vendasDinheiro + $reforcos - $sangriasDinheiro;
        $totalBruto = $vendasDinheiro + $vendasDigital;
        $sangrias = $sangriasDinheiro + $sangriasDigital;

        return view('bar.cash.index', compact(
            'currentSession',
            'openSession',
            'movements',
            'date',
            'dinheiroGeral',
            'reforcos',
            'sangrias',
            'faturamentoDigital',
            'totalBruto',
            'mesasAbertasCount',
            'caixaVencido' // 🚀 Nova variável enviada para a View
        ));
    }

    /**
     * Reabrir um caixa fechado (Ação de Gerência)
     */
    public function reopen($id)
    {
        // Só permite reabrir se não houver NENHUM outro caixa aberto no momento
        $hasOpen = BarCashSession::where('status', 'open')->exists();
        if ($hasOpen) {
            return back()->with('error', 'Já existe um caixa aberto! Feche o atual antes de reabrir este.');
        }

        $session = BarCashSession::findOrFail($id);

        $session->update([
            'status' => 'open',
            'closed_at' => null,
            'closing_balance' => null,
        ]);

        return back()->with('success', 'Caixa reaberto com sucesso!');
    }

    /**
     * Abrir o Caixa (Início de Turno com Autorização de Supervisor)
     */
    public function open(Request $request)
    {
        // 0. 🛡️ VALIDAÇÃO DO SUPERVISOR (Ponte de Segurança)
        if (!$request->supervisor_email || !$request->supervisor_password) {
            return back()->with('error', '⚠️ Autorização necessária: As credenciais do supervisor não foram detectadas.');
        }

        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        if (!$supervisor || !\Illuminate\Support\Facades\Hash::check($request->supervisor_password, $supervisor->password)) {
            return back()->with('error', '⚠️ Falha na autorização: E-mail ou Senha do supervisor incorretos.');
        }

        if (!in_array($supervisor->role, ['admin', 'gestor'])) {
            return back()->with('error', '⚠️ Acesso negado! Somente um Gestor ou Admin pode autorizar a abertura de caixa.');
        }

        // 1. Validação técnica do valor informado
        $request->validate([
            'opening_balance' => 'required|numeric|min:0',
        ]);

        // Evita duplicidade de sessões abertas
        $exists = BarCashSession::where('status', 'open')->exists();
        if ($exists) {
            return back()->with('error', 'Já existe um caixa aberto no sistema!');
        }

        // 2. Criação da Sessão com auditoria (Carimbo do Gestor)
        BarCashSession::create([
            'user_id' => auth()->id(), // Quem vai operar fisicamente (ex: Blenda)
            'opening_balance' => $request->opening_balance,
            'expected_balance' => $request->opening_balance,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => "Abertura autorizada por: {$supervisor->name}" // Registra quem deu o aval
        ]);

        return redirect()->route('bar.cash.index')->with('success', 'Turno iniciado com sucesso! Autorizado por ' . $supervisor->name);
    }

    /**
     * Fechar o Caixa com Auditoria (🔒 Restrito a Gestores)
     * Modelo: Validação de Supervisor via Modal
     */
    public function close(Request $request)
    {
        // 0. 🛡️ VALIDAÇÃO INICIAL: Garante que os dados do supervisor chegaram
        if (!$request->supervisor_email || !$request->supervisor_password) {
            return back()->with('error', '⚠️ Autorização necessária: As credenciais do supervisor não foram detectadas.');
        }

        // 1. Busca o supervisor pelas credenciais enviadas do modal de fechamento
        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        // 2. Valida se o supervisor existe e se a senha está correta
        if (!$supervisor || !\Illuminate\Support\Facades\Hash::check($request->supervisor_password, $supervisor->password)) {
            return back()->with('error', '⚠️ Falha na autorização: E-mail ou Senha do supervisor incorretos.');
        }

        // 3. Valida se quem está autorizando tem o cargo correto
        if (!in_array($supervisor->role, ['admin', 'gestor'])) {
            return back()->with('error', '⚠️ Acesso negado! Somente um Gestor ou Admin pode validar o encerramento do turno.');
        }

        // 🔥 3.5 TRAVA DE MESAS ABERTAS: Corrigido para 'occupied' e 'identifier'
        $mesasAbertas = \App\Models\Bar\BarTable::where('status', 'occupied')->get();

        if ($mesasAbertas->count() > 0) {
            // Usamos 'identifier' que é o campo que você usa na sua View de Mesas
            $numeros = $mesasAbertas->pluck('identifier')->implode(', ');
            return back()->with('error', "⚠️ Bloqueio de Fechamento: Existem mesas ocupadas ({$numeros}). Finalize todas as comandas antes de fechar o caixa.");
        }

        // 4. Validação técnica dos campos de fechamento
        $request->validate([
            'actual_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        return DB::transaction(function () use ($request, $supervisor) {
            // Busca a sessão aberta travando a linha para evitar cálculos errados
            $session = BarCashSession::where('status', 'open')->lockForUpdate()->first();

            if (!$session) {
                return back()->with('error', 'Erro: Não há nenhuma sessão de caixa aberta para fechar.');
            }

            // Lógica de Auditoria: Confronto do esperado no sistema vs contado fisicamente
            $expected = $session->expected_balance;
            $actual = $request->actual_balance;
            $difference = $actual - $expected;

            // 5. Atualiza a sessão com o carimbo de quem autorizou
            $session->update([
                'closing_balance' => $actual,
                'status' => 'closed',
                'closed_at' => now(),
                // Concatena as observações do operador com o nome do supervisor
                'notes' => ($request->notes ? $request->notes . " | " : "") . "Fechamento autorizado por: {$supervisor->name}"
            ]);

            $msg = "Turno encerrado com sucesso!";

            // 📊 Feedback de Quebra ou Sobra de Gaveta
            if ($difference < 0) {
                $msg .= " Quebra detectada: R$ " . number_format(abs($difference), 2, ',', '.');
            } elseif ($difference > 0) {
                $msg .= " Sobra detectada: R$ " . number_format($difference, 2, ',', '.');
            }

            return redirect()->route('bar.cash.index')->with('success', $msg);
        });
    }
}
