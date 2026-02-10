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
        $openSession = BarCashSession::where('status', 'open')->first();

        // 2. BUSCA A SESSÃO PARA EXIBIÇÃO NO HISTÓRICO
        $currentSession = ($openSession && Carbon::parse($openSession->opened_at)->format('Y-m-d') == $date)
            ? $openSession
            : BarCashSession::whereDate('opened_at', $date)->latest()->first();

        // 3. Movimentações da sessão que estamos visualizando
        $movements = collect();
        if ($currentSession) {
            $movements = BarCashMovement::with(['user', 'barOrder.table'])
                ->where('bar_cash_session_id', $currentSession->id)
                ->latest()
                ->get();
        }

        // 4. CÁLCULOS (Lógica de separação Físico vs Digital)

        // Reforços: Geralmente em dinheiro (ajustado para filtrar por método se houver)
        $reforcos = $movements->where('type', 'reforco')->where('payment_method', 'dinheiro')->sum('amount');

        // Sangrias separadas por destino para não bagunçar a auditoria física
        $sangriasDinheiro = $movements->where('type', 'sangria')->where('payment_method', 'dinheiro')->sum('amount');
        $sangriasDigital = $movements->where('type', 'sangria')->whereIn('payment_method', ['pix', 'credito', 'debito'])->sum('amount');

        // Vendas separadas por método
        $vendasDinheiro = $movements->where('type', 'venda')
            ->where('payment_method', 'dinheiro')
            ->sum('amount');

        $vendasDigital = $movements->where('type', 'venda')
            ->whereIn('payment_method', ['pix', 'credito', 'debito'])
            ->sum('amount');

        // Faturamento Digital Líquido: (Vendas Digital - Sangrias Digital)
        // Isso reflete o saldo real que deve estar na conta/maquininha
        $faturamentoDigital = $vendasDigital - $sangriasDigital;

        // Dinheiro em Gaveta: (Abertura + Vendas em Espécie + Reforços - Sangrias EM DINHEIRO)
        // Note que as sangrias digitais NÃO diminuem este valor
        $dinheiroGeral = ($currentSession->opening_balance ?? 0) + $vendasDinheiro + $reforcos - $sangriasDinheiro;

        // Total Bruto Faturado: Soma de todas as vendas (sem descontar sangrias)
        $totalBruto = $vendasDinheiro + $vendasDigital;

        // Total de Sangrias (Apenas para exibição no Card de Sangrias da View)
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
            'payment_method' => 'required|in:dinheiro,pix,debito,credito', // Adicionado
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:191',
        ]);

        return DB::transaction(function () use ($request) {
            $session = BarCashSession::where('status', 'open')->lockForUpdate()->first();
            if (!$session) return back()->with('error', 'Não há caixa aberto.');

            BarCashMovement::create([
                'bar_cash_session_id' => $session->id,
                'user_id' => auth()->id(),
                'type' => $request->type,
                'payment_method' => $request->payment_method, // Usar o que vem do form
                'amount' => $request->amount,
                'description' => $request->description,
            ]);

            // 🔥 AQUI ESTÁ A CHAVE: Só mexe no 'expected_balance' (auditoria de gaveta) se for DINHEIRO
            if ($request->payment_method === 'dinheiro') {
                if ($request->type === 'reforco') {
                    $session->increment('expected_balance', $request->amount);
                } else {
                    $session->decrement('expected_balance', $request->amount);
                }
            }

            return back()->with('success', 'Movimentação registrada!');
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
