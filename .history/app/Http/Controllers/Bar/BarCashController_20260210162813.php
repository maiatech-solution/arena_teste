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

        // 2. BUSCA A SESSÃO PARA EXIBIÇÃO NO HISTÓRICO
        $currentSession = ($openSession && Carbon::parse($openSession->opened_at)->format('Y-m-d') == $date)
            ? $openSession
            : BarCashSession::whereDate('opened_at', $date)->latest()->first();

        // 3. MOVIMENTAÇÕES
        $movements = collect();
        $allMovements = collect(); // Criamos uma coleção para o cálculo TOTAL

        if ($currentSession) {
            // Pegamos TODAS as movimentações da sessão para os cálculos dos cards
            $allMovements = BarCashMovement::with(['user', 'barOrder.table'])
                ->where('bar_cash_session_id', $currentSession->id)
                ->get();

            // Para a TABELA (Histórico), filtramos se for colaborador
            if (!in_array($user->role, ['admin', 'gestor'])) {
                $movements = $allMovements->where('user_id', $user->id);
            } else {
                $movements = $allMovements;
            }

            // Ordenamos o histórico para exibição
            $movements = $movements->sortByDesc('created_at');
        }

        // 4. CÁLCULOS FINANCEIROS TOTAIS (Baseados em allMovements - Gaveta Única)

        // Reforços e Vendas em Dinheiro (Total da Gaveta)
        $reforcos = $allMovements->where('type', 'reforco')->where('payment_method', 'dinheiro')->sum('amount');
        $vendasDinheiro = $allMovements->where('type', 'venda')->where('payment_method', 'dinheiro')->sum('amount');

        // Vendas Digitais (Total do estabelecimento)
        $vendasDigital = $allMovements->where('type', 'venda')->whereIn('payment_method', ['pix', 'credito', 'debito'])->sum('amount');

        // Sangrias (Total retirado da gaveta/contas)
        $sangriasDinheiro = $allMovements->where('type', 'sangria')->where('payment_method', 'dinheiro')->sum('amount');
        $sangriasDigital = $allMovements->where('type', 'sangria')->whereIn('payment_method', ['pix', 'credito', 'debito'])->sum('amount');

        // Faturamento Digital Líquido
        $faturamentoDigital = $vendasDigital - $sangriasDigital;

        // --- LÓGICA DE GAVETA UNIFICADA ---
        // O saldo inicial da sessão SEMPRE conta para o dinheiro em gaveta, independente de quem logou
        $saldoInicialSessao = $currentSession ? $currentSession->opening_balance : 0;

        // Valor exato que deve estar no caixa físico agora
        $dinheiroGeral = $saldoInicialSessao + $vendasDinheiro + $reforcos - $sangriasDinheiro;

        $totalBruto = $vendasDinheiro + $vendasDigital;
        $sangrias = $sangriasDinheiro + $sangriasDigital;

        return view('bar.cash.index', compact(
            'currentSession',
            'openSession',
            'movements', // Filtrado para o colaborador na tabela
            'date',
            'dinheiroGeral', // Total Gaveta
            'reforcos',      // Total Gaveta
            'sangrias',      // Total Gaveta
            'faturamentoDigital',
            'totalBruto'
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
     * Abrir o Caixa (Início de Turno)
     */
    public function open(Request $request)
    {
        $request->validate([
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $exists = BarCashSession::where('status', 'open')->exists();
        if ($exists) {
            return back()->with('error', 'Já existe um caixa aberto!');
        }

        BarCashSession::create([
            'user_id' => auth()->id(),
            'opening_balance' => $request->opening_balance,
            'expected_balance' => $request->opening_balance, // Inicia o cálculo de auditoria
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return redirect()->route('bar.cash.index')->with('success', 'Turno iniciado com sucesso!');
    }

    /**
     * Lançar Sangria ou Reforço (Validando Supervisor)
     */
    public function storeMovement(Request $request)
    {
        // 1. Busca o supervisor pelas credenciais enviadas do modal
        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        // 2. Valida se o supervisor existe, se a senha bate e se ele tem poder (admin/gestor)
        if (!$supervisor || !\Illuminate\Support\Facades\Hash::check($request->supervisor_password, $supervisor->password)) {
            return back()->with('error', '⚠️ Falha na autorização: E-mail ou Senha do supervisor incorretos.');
        }

        if (!in_array($supervisor->role, ['admin', 'gestor'])) {
            return back()->with('error', '⚠️ Operação negada! O usuário autorizador não tem nível de Gestor/Admin.');
        }

        // 3. Validação dos dados do movimento
        $request->validate([
            'type' => 'required|in:sangria,reforco',
            'payment_method' => 'required|in:dinheiro,pix,debito,credito',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:191',
        ]);

        return DB::transaction(function () use ($request, $supervisor) {
            $session = BarCashSession::where('status', 'open')->lockForUpdate()->first();

            if (!$session) {
                return back()->with('error', 'Não há caixa aberto.');
            }

            // Criamos o movimento, mas agora registramos quem autorizou se quiser (opcional)
            BarCashMovement::create([
                'bar_cash_session_id' => $session->id,
                'user_id' => auth()->id(), // Quem está operando (ex: Blenda)
                'type' => $request->type,
                'payment_method' => $request->payment_method,
                'amount' => $request->amount,
                'description' => $request->description . " (Auth: {$supervisor->name})",
            ]);

            if ($request->payment_method === 'dinheiro') {
                if ($request->type === 'reforco') {
                    $session->increment('expected_balance', $request->amount);
                } else {
                    $session->decrement('expected_balance', $request->amount);
                }
            }

            return back()->with('success', 'Movimentação autorizada e registrada com sucesso!');
        });
    }

    /**
     * Fechar o Caixa com Auditoria (🔒 Restrito a Gestores)
     */
    public function close(Request $request)
    {
        // 1. Busca o supervisor pelas credenciais enviadas do modal de fechamento
        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        // 2. Valida se o supervisor existe e se a senha está correta
        if (!$supervisor || !\Illuminate\Support\Facades\Hash::check($request->supervisor_password, $supervisor->password)) {
            return back()->with('error', '⚠️ Falha na autorização: E-mail ou Senha do supervisor incorretos.');
        }

        // 3. Valida se quem está autorizando é Admin ou Gestor
        if (!in_array($supervisor->role, ['admin', 'gestor'])) {
            return back()->with('error', '⚠️ Acesso negado! O encerramento de turno exige a validação de um Gestor ou Admin.');
        }

        // 4. Validação dos campos de fechamento
        $request->validate([
            'actual_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        return DB::transaction(function () use ($request, $supervisor) {
            $session = BarCashSession::where('status', 'open')->lockForUpdate()->first();

            if (!$session) {
                return back()->with('error', 'Caixa não encontrado ou já encerrado.');
            }

            $expected = $session->expected_balance;
            $actual = $request->actual_balance;
            $difference = $actual - $expected;

            // 5. Atualiza a sessão com a nota de quem autorizou
            $session->update([
                'closing_balance' => $actual,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => $request->notes . " (Fechamento autorizado por: {$supervisor->name})"
            ]);

            $msg = "Turno encerrado com sucesso!";

            // Notificação de Quebra ou Sobra
            if ($difference < 0) {
                $msg .= " Quebra detectada: R$ " . number_format(abs($difference), 2, ',', '.');
            } elseif ($difference > 0) {
                $msg .= " Sobra detectada: R$ " . number_format($difference, 2, ',', '.');
            }

            return redirect()->route('bar.cash.index')->with('success', $msg);
        });
    }
}
