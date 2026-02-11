<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarCashSession;
use App\Models\Bar\BarCashMovement;
use App\Models\Bar\BarTable; // Importação da Mesa
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;   // Essencial para transações
use Illuminate\Support\Facades\Hash; // Essencial para validar senhas

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

        // 🚩 LÓGICA DE CAIXA VENCIDO
        $caixaVencido = false;
        if ($openSession) {
            $dataAbertura = Carbon::parse($openSession->opened_at)->startOfDay();
            $hoje = Carbon::today();
            if ($dataAbertura->lt($hoje)) {
                $caixaVencido = true;
            }
        }

        // 2. BUSCA A SESSÃO PARA EXIBIÇÃO NO HISTÓRICO
        $currentSession = ($openSession && Carbon::parse($openSession->opened_at)->format('Y-m-d') == $date)
            ? $openSession
            : BarCashSession::whereDate('opened_at', $date)->latest()->first();

        // 🛡️ TRAVA DE SEGURANÇA: Contagem de mesas 'occupied'
        $mesasAbertasCount = BarTable::where('status', 'occupied')->count();

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

        // 4. CÁLCULOS FINANCEIROS
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
            'currentSession', 'openSession', 'movements', 'date',
            'dinheiroGeral', 'reforcos', 'sangrias', 'faturamentoDigital',
            'totalBruto', 'mesasAbertasCount', 'caixaVencido'
        ));
    }

    /**
     * 💸 PROCESSAR MOVIMENTAÇÕES (Sangria e Reforço)
     * Resolve o erro 500: Call to undefined method storeMovement
     */
    public function storeMovement(Request $request)
    {
        if (!$request->supervisor_email || !$request->supervisor_password) {
            return back()->with('error', '⚠️ Autorização necessária para movimentar o caixa.');
        }

        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        if (!$supervisor || !Hash::check($request->supervisor_password, $supervisor->password)) {
            return back()->with('error', '⚠️ Credenciais do supervisor incorretas.');
        }

        $request->validate([
            'type' => 'required|in:sangria,reforco',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $session = BarCashSession::where('status', 'open')->first();
        if (!$session) return back()->with('error', 'Não há caixa aberto.');

        return DB::transaction(function () use ($request, $session, $supervisor) {
            BarCashMovement::create([
                'bar_cash_session_id' => $session->id,
                'user_id' => auth()->id(),
                'type' => $request->type,
                'payment_method' => 'dinheiro',
                'amount' => $request->amount,
                'description' => $request->description . " (Aut: {$supervisor->name})",
            ]);

            if ($request->type === 'reforco') {
                $session->increment('expected_balance', $request->amount);
            } else {
                $session->decrement('expected_balance', $request->amount);
            }

            return back()->with('success', ucfirst($request->type) . " realizado com sucesso!");
        });
    }

    public function open(Request $request)
    {
        if (!$request->supervisor_email || !$request->supervisor_password) {
            return back()->with('error', '⚠️ Autorização necessária.');
        }

        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();
        if (!$supervisor || !Hash::check($request->supervisor_password, $supervisor->password)) {
            return back()->with('error', '⚠️ Falha na autorização.');
        }

        $request->validate(['opening_balance' => 'required|numeric|min:0']);
        if (BarCashSession::where('status', 'open')->exists()) {
            return back()->with('error', 'Já existe um caixa aberto!');
        }

        BarCashSession::create([
            'user_id' => auth()->id(),
            'opening_balance' => $request->opening_balance,
            'expected_balance' => $request->opening_balance,
            'status' => 'open',
            'opened_at' => now(),
            'notes' => "Abertura autorizada por: {$supervisor->name}"
        ]);

        return redirect()->route('bar.cash.index')->with('success', 'Caixa aberto!');
    }

    public function close(Request $request)
    {
        if (!$request->supervisor_email || !$request->supervisor_password) {
            return back()->with('error', '⚠️ Autorização necessária.');
        }

        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();
        if (!$supervisor || !Hash::check($request->supervisor_password, $supervisor->password)) {
            return back()->with('error', '⚠️ Credenciais incorretas.');
        }

        $mesasAbertas = BarTable::where('status', 'occupied')->get();
        if ($mesasAbertas->count() > 0) {
            $numeros = $mesasAbertas->pluck('identifier')->implode(', ');
            return back()->with('error', "⚠️ Bloqueio: Existem mesas ocupadas ({$numeros}).");
        }

        $request->validate(['actual_balance' => 'required|numeric|min:0']);

        return DB::transaction(function () use ($request, $supervisor) {
            $session = BarCashSession::where('status', 'open')->lockForUpdate()->first();
            if (!$session) return back()->with('error', 'Sessão não encontrada.');

            $expected = $session->expected_balance;
            $actual = $request->actual_balance;
            $difference = $actual - $expected;

            $session->update([
                'closing_balance' => $actual,
                'status' => 'closed',
                'closed_at' => now(),
                'notes' => ($request->notes ? $request->notes . " | " : "") . "Fechado por: {$supervisor->name}"
            ]);

            return redirect()->route('bar.cash.index')->with('success', 'Caixa encerrado!');
        });
    }

    public function reopen($id)
    {
        if (BarCashSession::where('status', 'open')->exists()) {
            return back()->with('error', 'Já existe um caixa aberto!');
        }
        $session = BarCashSession::findOrFail($id);
        $session->update(['status' => 'open', 'closed_at' => null, 'closing_balance' => null]);
        return back()->with('success', 'Caixa reaberto!');
    }
}
