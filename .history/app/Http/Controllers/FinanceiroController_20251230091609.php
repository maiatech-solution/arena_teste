<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Reserva;
use App\Models\FinancialTransaction;
use App\Models\Cashier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FinanceiroController extends Controller
{
    /**
     * Dashboard Principal (Hub de Relatórios)
     */
    public function index()
    {
        Log::info('FinanceiroController: Acessando Hub de Relatórios.');

        $mesAtual = now()->month;
        $anoAtual = now()->year;

        $faturamentoMensal = FinancialTransaction::whereMonth('paid_at', $mesAtual)
            ->whereYear('paid_at', $anoAtual)
            ->sum('amount');

        $totalReservasMes = Reserva::whereMonth('date', $mesAtual)
            ->whereYear('date', $anoAtual)
            ->where('is_fixed', false)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
            ->count();

        $canceladasMes = Reserva::whereMonth('date', $mesAtual)
            ->whereYear('date', $anoAtual)
            ->whereIn('status', [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA, Reserva::STATUS_NO_SHOW])
            ->count();

        return view('admin.financeiro.index', compact('faturamentoMensal', 'totalReservasMes', 'canceladasMes'));
    }

    /**
     * Relatório 01: Faturamento Detalhado
     */
    public function relatorioFaturamento(Request $request)
    {
        $dataInicio = $request->input('data_inicio') ? Carbon::parse($request->input('data_inicio'))->startOfDay() : now()->startOfMonth();
        $dataFim = $request->input('data_fim') ? Carbon::parse($request->input('data_fim'))->endOfDay() : now()->endOfDay();

        $transacoes = FinancialTransaction::whereBetween('paid_at', [$dataInicio, $dataFim])
            ->with('reserva')
            ->orderBy('paid_at', 'desc')
            ->get();

        $totaisPorMetodo = $transacoes->groupBy('payment_method')->map(fn($row) => $row->sum('amount'));
        $faturamentoTotal = $transacoes->sum('amount');

        return view('admin.financeiro.relatorio_faturamento', compact('transacoes', 'totaisPorMetodo', 'faturamentoTotal', 'dataInicio', 'dataFim'));
    }

    /**
     * Relatório 02: Histórico de Caixa
     */
    public function relatorioCaixa(Request $request)
    {
        $data = $request->input('data', now()->format('Y-m-d'));

        // 1. Movimentações detalhadas do dia selecionado
        $movimentacoes = FinancialTransaction::whereDate('paid_at', $data)
            ->with(['reserva', 'manager'])
            ->orderBy('paid_at', 'asc')
            ->get();

        // 2. BUSCA O HISTÓRICO DE FECHAMENTOS (Corrigido a setinha -> )
        $cashierHistory = Cashier::with('user')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get(); // <--- O erro estava aqui, mudei de .get() para ->get()

        return view('admin.financeiro.caixa', compact('movimentacoes', 'data', 'cashierHistory'));
    }

    /**
     * Relatório 03: Cancelamentos
     */
    public function relatorioCancelamentos(Request $request)
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        $cancelamentos = Reserva::whereIn('status', [Reserva::STATUS_CANCELADA, Reserva::STATUS_NO_SHOW, Reserva::STATUS_REJEITADA])
            ->whereMonth('date', $mes)
            ->whereYear('date', $ano)
            ->with('user')
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.financeiro.cancelamentos', compact('cancelamentos', 'mes', 'ano'));
    }

    /**
     * Relatório 04: Mapa de Ocupação & Histórico
     */
    public function relatorioOcupacao(Request $request)
    {
        $dataInicio = $request->input('data_inicio') ? Carbon::parse($request->input('data_inicio'))->startOfDay() : now()->startOfMonth();
        $dataFim = $request->input('data_fim') ? Carbon::parse($request->input('data_fim'))->endOfDay() : now()->endOfMonth();

        $reservas = Reserva::whereBetween('date', [$dataInicio, $dataFim])
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        return view('admin.financeiro.ocupacao', compact('reservas', 'dataInicio', 'dataFim'));
    }

    /**
     * Relatório 05: Ranking de Clientes (Fidelidade Real)
     */
    public function relatorioRanking()
    {
        $hoje = now()->format('Y-m-d');

        $ranking = Reserva::select(
            'client_name',
            'client_contact',
            DB::raw('SUM(total_paid) as total_gasto'),
            DB::raw("COUNT(CASE WHEN total_paid > 0 AND date <= '$hoje' THEN 1 END) as total_reservas")
        )
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
            ->where('total_paid', '>', 0)
            ->groupBy('client_name', 'client_contact')
            ->orderBy('total_gasto', 'desc')
            ->limit(15)
            ->get();

        return view('admin.financeiro.ranking', compact('ranking'));
    }

    // =========================================================================
    // 🔒 GESTÃO DE CAIXA E APIs
    // =========================================================================

    public function closeCash(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'actual_amount' => 'required|numeric'
        ]);

        DB::beginTransaction();
        try {
            $dateString = $request->date;
            $calculatedAmount = $this->calculateLiquidCash($dateString);

            Cashier::updateOrCreate(['date' => $dateString], [
                'calculated_amount' => $calculatedAmount,
                'actual_amount' => (float)$request->actual_amount,
                'status' => 'closed',
                'closed_by_user_id' => Auth::id(),
                'closing_time' => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Caixa fechado com sucesso."]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao fechar caixa: ' . $e->getMessage());
            // Aqui está o segredo: enviamos a mensagem REAL do erro para o front-end
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function openCash(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'reason' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $cashier = Cashier::where('date', $request->date)->first();
            if (!$cashier) return response()->json(['success' => false, 'message' => 'Caixa não localizado.'], 404);

            $userName = Auth::user()->name ?? 'Admin';
            $reopenNote = "[REABERTURA por {$userName} em " . now()->format('d/m/Y H:i:s') . "]: {$request->reason}";

            $cashier->update([
                'status' => 'open',
                'notes' => $cashier->notes ? $cashier->notes . "\n---\n" . $reopenNote : $reopenNote,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Caixa reaberto com sucesso."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erro ao reabrir.'], 500);
        }
    }

    public function isCashClosed(string $dateString): bool
    {
        return Cashier::whereDate('date', $dateString)
            ->where('status', 'closed')
            ->exists();
    }

    private function calculateLiquidCash(string $dateString)
    {
        // Removido o (float). O Laravel usará a precisão do banco de dados.
        return FinancialTransaction::whereDate('paid_at', $dateString)->sum('amount');
    }

    private function getDateRange(string $periodo): array
    {
        $now = Carbon::now();
        $start = $now->copy()->startOfDay();
        $end = $now->copy()->endOfDay();

        switch ($periodo) {
            case 'semana':
                $start = $now->copy()->startOfWeek(Carbon::SUNDAY);
                $end = $now->copy()->endOfWeek(Carbon::SATURDAY);
                break;
            case 'mes':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
        }
        return [$start, $end->endOfDay()];
    }
}
