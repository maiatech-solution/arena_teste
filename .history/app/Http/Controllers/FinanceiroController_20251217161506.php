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
     * Alimenta a View Central com as estatísticas do mês atual.
     */
    public function index()
    {
        Log::info('FinanceiroController: Acessando Hub de Relatórios.');

        $mesAtual = now()->month;
        $anoAtual = now()->year;

        // Faturamento Mensal Líquido (Soma de transações no mês)
        $faturamentoMensal = FinancialTransaction::whereMonth('paid_at', $mesAtual)
            ->whereYear('paid_at', $anoAtual)
            ->sum('amount');

        // Contagem de Reservas (Ocupação vs Cancelamentos)
        $totalReservasMes = Reserva::whereMonth('date', $mesAtual)
            ->whereYear('date', $anoAtual)
            ->where('is_fixed', false)
            ->count();

        $canceladasMes = Reserva::whereMonth('date', $mesAtual)
            ->whereYear('date', $anoAtual)
            ->whereIn('status', [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA, Reserva::STATUS_NO_SHOW])
            ->count();

        return view('admin.financeiro.index', compact('faturamentoMensal', 'totalReservasMes', 'canceladasMes'));
    }

    /**
     * Relatório 01: Faturamento Detalhado por Período e Método
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
     * Relatório 02: Histórico de Caixa e Conferência de Movimentação
     */
    public function relatorioCaixa(Request $request)
    {
        $data = $request->input('data', now()->format('Y-m-d'));

        $movimentacoes = FinancialTransaction::whereDate('paid_at', $data)
            ->with(['reserva', 'manager'])
            ->orderBy('paid_at', 'asc')
            ->get();

        return view('admin.financeiro.relatorios.caixa', compact('movimentacoes', 'data'));
    }

    /**
     * Relatório 03: Análise de Cancelamentos, Rejeições e No-Show
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

        return view('admin.financeiro.relatorios.cancelamentos', compact('cancelamentos', 'mes', 'ano'));
    }

    /**
     * Relatório 04: Mapa de Ocupação (Agenda de Jogos Confirmados)
     */
    public function relatorioOcupacao(Request $request)
    {
        $dataInicio = $request->input('data_inicio', now()->format('Y-m-d'));

        $reservas = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereDate('date', '>=', $dataInicio)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return view('admin.financeiro.relatorios.ocupacao', compact('reservas', 'dataInicio'));
    }

    // =========================================================================
    // 🔒 MÉTODOS ORIGINAIS DE GESTÃO DE CAIXA (SISTEMA OPERACIONAL)
    // =========================================================================

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

    private function calculateLiquidCash(string $dateString): float
    {
        return (float) FinancialTransaction::whereDate('paid_at', $dateString)->sum('amount');
    }

    public function getResumo(Request $request)
    {
        Log::info('FinanceiroController: getResumo via AJAX iniciado.');
        try {
            $periodos = ['hoje', 'semana', 'mes'];
            $resultados = ['total_recebido' => [], 'sinais' => [], 'reservas' => []];
            $incomeTypes = ['signal', 'full_payment', 'partial_payment', 'payment_settlement', 'RETEN_CANC_COMP', 'RETEN_CANC_P_COMP', 'RETEN_CANC_S_COMP', 'RETEN_NOSHOW_COMP'];

            foreach ($periodos as $periodo) {
                list($start, $end) = $this->getDateRange($periodo);
                $transacoes = FinancialTransaction::whereBetween('paid_at', [$start, $end])->whereIn('type', $incomeTypes)->get();
                $resultados['total_recebido'][$periodo] = (float) $transacoes->sum('amount');
                $resultados['sinais'][$periodo] = (float) $transacoes->where('type', 'signal')->sum('amount');
                $resultados['reservas'][$periodo] = Reserva::whereBetween('date', [$start, $end])->where('is_fixed', false)->count();
            }
            return response()->json(['success' => true, 'data' => $resultados]);
        } catch (\Exception $e) {
            Log::error('Erro getResumo: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    public function getPagamentosPendentes()
    {
        try {
            $reservas = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                ->where('is_fixed', false)
                ->where(function ($q) {
                    $q->whereRaw('COALESCE(total_paid, 0) < price')->orWhereNull('total_paid');
                })
                ->where('date', '>=', Carbon::today())
                ->orderBy('date', 'asc')->limit(30)->get();

            $data = $reservas->map(fn($r) => [
                'id' => $r->id,
                'cliente' => $r->client_name,
                'contato' => $r->client_contact,
                'data' => Carbon::parse($r->date)->format('d/m/Y'),
                'horario' => Carbon::parse($r->start_time)->format('H:i'),
                'valor_total' => (float) $r->price,
                'total_pago' => (float) ($r->total_paid ?? 0),
                'valor_restante' => max(0, $r->price - ($r->total_paid ?? 0)),
                'status_pagamento_texto' => ($r->total_paid ?? 0) == 0 ? 'Não Iniciado' : 'Parcial',
                'link_acoes' => route('admin.reservas.show', $r->id),
            ]);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('Erro getPagamentosPendentes: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

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
                'closed_by_user_id' => auth()->id(),
                'closing_time' => now(),
            ]);

            Log::info("Caixa fechado para a data {$dateString} pelo usuário " . auth()->id());
            DB::commit();
            return response()->json(['success' => true, 'message' => "Caixa fechado com sucesso."]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao fechar caixa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno ao fechar caixa.'], 500);
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
            if (!$cashier) {
                return response()->json(['success' => false, 'message' => "Registro de caixa não encontrado."], 404);
            }

            $managerName = Auth::user()->name;
            $reopenNote = "[REABERTURA por {$managerName} em " . now()->format('d/m/Y H:i:s') . "]: {$request->reason}";

            $cashier->update([
                'status' => 'open',
                'notes' => $cashier->notes ? $cashier->notes . "\n---\n" . $reopenNote : $reopenNote,
            ]);

            Log::warning("Caixa reaberto para a data {$request->date}. Motivo: {$request->reason}");
            DB::commit();
            return response()->json(['success' => true, 'message' => "Caixa reaberto com sucesso."]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao reabrir caixa: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    public function isCashClosed(string $dateString): bool
    {
        return (bool) Cashier::where('date', $dateString)->where('status', 'closed')->first();
    }
}
