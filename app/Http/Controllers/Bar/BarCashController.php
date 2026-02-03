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

        // 1. BUSCA A SESSÃO ATIVA AGORA (Independente da data)
        // Isso é o que libera os botões de Sangria/Fechar no topo
        $openSession = BarCashSession::where('status', 'open')->first();

        // 2. BUSCA A SESSÃO PARA EXIBIÇÃO NO HISTÓRICO
        // Prioriza a aberta se for hoje, senão busca a última da data filtrada
        $currentSession = ($openSession && Carbon::parse($openSession->opened_at)->format('Y-m-d') == $date) 
            ? $openSession 
            : BarCashSession::whereDate('opened_at', $date)->latest()->first();

        // 3. Movimentações da sessão que estamos visualizando
        $movements = collect();
        if ($currentSession) {
            // CORREÇÃO: Usando 'barOrder' em vez de 'order' para bater com o Model corrigido
            $movements = BarCashMovement::with(['user', 'barOrder.table'])
                ->where('bar_cash_session_id', $currentSession->id)
                ->latest()
                ->get();
        }

        // 4. Cálculos (Sempre baseados na $currentSession que está na tela)
        $reforcos = $movements->where('type', 'reforco')->sum('amount');
        $sangrias = $movements->where('type', 'sangria')->sum('amount');
        
        $vendasDinheiro = $movements->where('type', 'venda')
            ->where('payment_method', 'dinheiro')
            ->sum('amount');

        $faturamentoDigital = $movements->where('type', 'venda')
            ->whereIn('payment_method', ['pix', 'credito', 'debito'])
            ->sum('amount');

        // Saldo Dinheiro = Abertura + Entradas Dinheiro + Reforços - Sangrias
        $dinheiroGeral = ($currentSession->opening_balance ?? 0) + $vendasDinheiro + $reforcos - $sangrias;
        $totalBruto = $movements->where('type', 'venda')->sum('amount');

        return view('bar.cash.index', compact(
            'currentSession',
            'openSession',
            'movements',
            'date',
            'dinheiroGeral',
            'reforcos',
            'sangrias',
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
     * Lançar Sangria ou Reforço
     */
    public function storeMovement(Request $request)
    {
        $request->validate([
            'type' => 'required|in:sangria,reforco',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:191',
        ]);

        return DB::transaction(function () use ($request) {
            $session = BarCashSession::where('status', 'open')->lockForUpdate()->first();

            if (!$session) {
                return back()->with('error', 'Não há caixa aberto.');
            }

            BarCashMovement::create([
                'bar_cash_session_id' => $session->id,
                'user_id' => auth()->id(),
                'type' => $request->type,
                'payment_method' => 'dinheiro',
                'amount' => $request->amount,
                'description' => $request->description,
            ]);

            // Atualiza o saldo esperado na auditoria
            if ($request->type === 'reforco') {
                $session->increment('expected_balance', $request->amount);
            } else {
                $session->decrement('expected_balance', $request->amount);
            }

            return back()->with('success', 'Movimentação registada!');
        });
    }

    /**
     * Fechar o Caixa com Auditoria
     */
    public function close(Request $request)
    {
        $request->validate([
            'actual_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        return DB::transaction(function () use ($request) {
            $session = BarCashSession::where('status', 'open')->lockForUpdate()->first();

            if (!$session) {
                return back()->with('error', 'Caixa não encontrado.');
            }

            $expected = $session->expected_balance;
            $actual = $request->actual_balance;
            $difference = $actual - $expected;

            $session->update([
                'closing_balance' => $actual,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => $request->notes
            ]);

            $msg = "Turno encerrado!";
            if ($difference < 0) {
                $msg .= " Quebra detetada: R$ " . number_format(abs($difference), 2, ',', '.');
            }

            return redirect()->route('bar.cash.index')->with('success', $msg);
        });
    }
}