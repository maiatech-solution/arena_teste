<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarCashSession;
use App\Models\Bar\BarCashMovement;
use App\Models\Bar\BarTable;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BarCashController extends Controller
{
    /**
     * Tela Principal do Caixa
     */
    public function index(Request $request)
    {
        $date = $request->get('date', date('Y-m-d'));
        $user = auth()->user();

        $openSession = BarCashSession::where('status', 'open')->first();

        $caixaVencido = false;
        if ($openSession) {
            $dataAbertura = Carbon::parse($openSession->opened_at)->startOfDay();
            $hoje = Carbon::today();
            if ($dataAbertura->lt($hoje)) {
                $caixaVencido = true;
            }
        }

        $currentSession = ($openSession && Carbon::parse($openSession->opened_at)->format('Y-m-d') == $date)
            ? $openSession
            : BarCashSession::whereDate('opened_at', $date)->latest()->first();

        $mesasAbertasCount = BarTable::where('status', 'occupied')->count();

        $movements = collect();
        $vendasDinheiro = 0;
        $vendasDigital = 0;
        $reforcos = 0;
        $sangriasDinheiro = 0;
        $sangriasDigital = 0;

        if ($currentSession) {
            // 1. MOVIMENTAÇÕES PARA O HISTÓRICO (Sangrias, Reforços e logs)
            $allMovements = BarCashMovement::with(['user', 'barOrder.table'])
                ->where('bar_cash_session_id', $currentSession->id)
                ->get();

            $movements = (!in_array($user->role, ['admin', 'gestor']))
                ? $allMovements->where('user_id', $user->id)
                : $allMovements;

            $movements = $movements->sortByDesc('created_at');

            // 2. 🎯 AUDITORIA REAL: Busca direto nas fontes da verdade
            $vendasMesas = \App\Models\Bar\BarOrder::where('bar_cash_session_id', $currentSession->id)
                ->where('status', 'paid')
                ->get();

            $vendasPDV = \App\Models\Bar\BarSale::where('bar_cash_session_id', $currentSession->id)
                ->where('status', 'pago')
                ->get();

            // Separando Dinheiro de Digital nas Mesas
            foreach ($vendasMesas as $order) {
                $pagamentos = json_decode($order->payment_method, true);
                if (is_array($pagamentos)) {
                    foreach ($pagamentos as $p) {
                        if ($p['metodo'] == 'dinheiro') $vendasDinheiro += $p['valor'];
                        else $vendasDigital += $p['valor'];
                    }
                }
            }

            // Separando Dinheiro de Digital no PDV
            // (Assumindo que no PDV você salva o método final ou tem o histórico de pagamentos)
            foreach ($vendasPDV as $sale) {
                if ($sale->payment_method == 'dinheiro') $vendasDinheiro += $sale->total_value;
                else $vendasDigital += $sale->total_value;
            }

            // Reforços e Sangrias (Baseado nas movimentações manuais)
            $reforcos = $allMovements->where('type', 'reforco')->sum('amount');
            $sangriasDinheiro = $allMovements->where('type', 'sangria')->sum('amount');
        }

        // 3. CÁLCULOS FINAIS
        $faturamentoDigital = $vendasDigital;
        $totalBruto = $vendasDinheiro + $vendasDigital;
        $saldoInicialSessao = $currentSession ? $currentSession->opening_balance : 0;

        // Dinheiro que deve estar na gaveta agora:
        $dinheiroGeral = $saldoInicialSessao + $vendasDinheiro + $reforcos - $sangriasDinheiro;
        $sangrias = $sangriasDinheiro;

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
            'caixaVencido'
        ));
    }


    /**
     * 💸 PROCESSAR MOVIMENTAÇÕES (Sangria e Reforço) com trava de data
     */
    public function storeMovement(Request $request)
    {
        // 0. 🛡️ VALIDAÇÃO DO SUPERVISOR
        if (!$request->supervisor_email || !$request->supervisor_password) {
            return back()->with('error', '⚠️ Autorização necessária: As credenciais do supervisor não foram detectadas.');
        }

        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        if (!$supervisor || !\Illuminate\Support\Facades\Hash::check($request->supervisor_password, $supervisor->password)) {
            return back()->with('error', '⚠️ Falha na autorização: E-mail ou Senha do supervisor incorretos.');
        }

        // 1. BUSCA SESSÃO ATIVA
        $session = BarCashSession::where('status', 'open')->first();

        if (!$session) {
            return back()->with('error', 'Erro: Não há nenhuma sessão de caixa aberta.');
        }

        // 🛡️ NOVA TRAVA DE DATA: Impede movimentar valores em caixas de dias anteriores
        $dataAbertura = \Carbon\Carbon::parse($session->opened_at)->format('Y-m-d');
        $hoje = date('Y-m-d');

        if ($dataAbertura !== $hoje) {
            return back()->with('error', '⚠️ BLOQUEIO DE MOVIMENTAÇÃO: Este caixa pertence ao dia anterior (' . \Carbon\Carbon::parse($session->opened_at)->format('d/m') . '). Encerre este turno antes de realizar sangrias ou reforços hoje.');
        }

        // 2. VALIDAÇÃO TÉCNICA
        $request->validate([
            'type' => 'required|in:sangria,reforco',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $session, $supervisor) {
            // 3. CRIA A MOVIMENTAÇÃO
            BarCashMovement::create([
                'bar_cash_session_id' => $session->id,
                'user_id' => auth()->id(),
                'type' => $request->type,
                'payment_method' => 'dinheiro',
                'amount' => $request->amount,
                'description' => $request->description . " (Autorizado por: {$supervisor->name})",
            ]);

            // 4. ATUALIZA SALDO ESPERADO NA GAVETA
            if ($request->type === 'reforco') {
                $session->increment('expected_balance', $request->amount);
                $msg = "Reforço realizado com sucesso!";
            } else {
                $session->decrement('expected_balance', $request->amount);
                $msg = "Sangria realizada com sucesso!";
            }

            return back()->with('success', $msg);
        });
    }

    /**
     * Reabrir um caixa fechado (Ação de Gerência)
     */
    public function reopen($id)
    {
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
        if (!$request->supervisor_email || !$request->supervisor_password) {
            return back()->with('error', '⚠️ Autorização necessária: As credenciais do supervisor não foram detectadas.');
        }

        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        if (!$supervisor || !Hash::check($request->supervisor_password, $supervisor->password)) {
            return back()->with('error', '⚠️ Falha na autorização: E-mail ou Senha do supervisor incorretos.');
        }

        if (!in_array($supervisor->role, ['admin', 'gestor'])) {
            return back()->with('error', '⚠️ Acesso negado! Somente um Gestor ou Admin pode autorizar a abertura de caixa.');
        }

        $request->validate([
            'opening_balance' => 'required|numeric|min:0',
        ]);

        $exists = BarCashSession::where('status', 'open')->exists();
        if ($exists) {
            return back()->with('error', 'Já existe um caixa aberto no sistema!');
        }

        BarCashSession::create([
            'user_id' => auth()->id(),
            'opening_balance' => $request->opening_balance,
            'expected_balance' => $request->opening_balance,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => "Abertura autorizada por: {$supervisor->name}"
        ]);

        return redirect()->route('bar.cash.index')->with('success', 'Turno iniciado com sucesso!');
    }

    /**
     * Fechar o Caixa com Auditoria (Versão Corrigida e Sincronizada)
     */
    public function close(Request $request)
    {
        // ... (Mantenha as validações de supervisor e mesas abertas que já existem no seu código) ...

        return DB::transaction(function () use ($request, $supervisor) {
            $session = BarCashSession::where('status', 'open')->lockForUpdate()->first();

            if (!$session) {
                return back()->with('error', 'Erro: Não há nenhuma sessão de caixa aberta.');
            }

            // 🎯 RECALCULO EM TEMPO REAL (Igual ao Relatório de Auditoria)
            $vendasMesas = \App\Models\Bar\BarOrder::where('bar_cash_session_id', $session->id)
                ->where('status', 'paid')
                ->sum('total_value');

            $vendasPDV = \App\Models\Bar\BarSale::where('bar_cash_session_id', $session->id)
                ->where('status', 'pago')
                ->sum('total_value');

            // Busca movimentações de Sangria e Reforço (apenas em dinheiro)
            $movimentacoes = BarCashMovement::where('bar_cash_session_id', $session->id)->get();
            $reforcos = $movimentacoes->where('type', 'reforco')->sum('amount');
            $sangrias = $movimentacoes->where('type', 'sangria')->sum('amount');

            // 💰 O valor que DEVE estar na gaveta + digital
            $faturamentoTotal = $vendasMesas + $vendasPDV;
            $totalEsperadoSistema = $session->opening_balance + $faturamentoTotal + $reforcos - $sangrias;

            $actual = $request->actual_balance; // O que o Adriano informou que tem
            $difference = $actual - $totalEsperadoSistema;

            $session->update([
                'closing_balance' => $actual,
                'expected_balance' => $totalEsperadoSistema, // Atualiza para o valor real auditado
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => ($request->notes ? $request->notes . " | " : "") . "Fechamento autorizado por: {$supervisor->name}"
            ]);

            $msg = "Turno encerrado com sucesso!";
            if (abs($difference) < 0.01) {
                $msg .= " Caixa bateu perfeitamente!";
            } elseif ($difference < 0) {
                $msg .= " Quebra detectada: R$ " . number_format(abs($difference), 2, ',', '.');
            } else {
                $msg .= " Sobra detectada: R$ " . number_format($difference, 2, ',', '.');
            }

            return redirect()->route('bar.cash.index')->with('success', $msg);
        });
    }
}
