<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Reserva;
use App\Models\FinancialTransaction;
use App\Models\Cashier; // 🎯 CRÍTICO: Importa o Model de Caixa para registro e validação
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // ✅ Importação do Auth (necessária para openCash/closeCash)

class FinanceiroController extends Controller
{
    /**
     * Carrega a view principal do dashboard financeiro.
     * 🎯 ATUALIZADO: Agora alimenta a página de Relatórios (index.blade.php)
     */
    public function index()
    {
        $hoje = Carbon::today();
        $mesAtual = Carbon::now()->month;
        $anoAtual = Carbon::now()->year;

        // 1. KPIs de Sinais (Baseado na sua lógica de getResumo)
        $sinalHoje = FinancialTransaction::whereDate('paid_at', $hoje)
            ->where('type', 'signal')
            ->sum('amount');

        $sinalSemana = FinancialTransaction::whereBetween('paid_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->where('type', 'signal')
            ->sum('amount');

        $sinalMes = FinancialTransaction::whereMonth('paid_at', $mesAtual)
            ->whereYear('paid_at', $anoAtual)
            ->where('type', 'signal')
            ->sum('amount');

        // 2. Faturamento Bruto Mensal
        $faturamentoMensal = FinancialTransaction::whereMonth('paid_at', $mesAtual)
            ->whereYear('paid_at', $anoAtual)
            ->sum('amount');

        // 3. Estatísticas de Reservas (Mês Atual)
        $totalReservasMes = Reserva::whereMonth('date', $mesAtual)
            ->whereYear('date', $anoAtual)
            ->where('is_fixed', false)
            ->count();

        $pagasMes = Reserva::whereMonth('date', $mesAtual)
            ->whereYear('date', $anoAtual)
            ->where('status', 'completed')
            ->count();

        $canceladasMes = Reserva::whereMonth('date', $mesAtual)
            ->whereYear('date', $anoAtual)
            ->whereIn('status', ['cancelled', 'rejected'])
            ->count();

        // 4. Reservas com Pagamento Pendente (Para a tabela na View)
        // Usa a sua lógica de getPagamentosPendentes para manter consistência
        $reservasPendentes = Reserva::query()
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('is_fixed', false)
            ->where(function ($query) {
                $query->whereRaw('COALESCE(total_paid, 0) < price')
                      ->orWhereNull('total_paid');
            })
            ->where('date', '>=', $hoje->toDateString())
            ->orderBy('date', 'asc')
            ->paginate(10); // Adicionado paginação para a View

        return view('admin.financeiro.index', compact(
            'sinalHoje',
            'sinalSemana',
            'sinalMes',
            'faturamentoMensal',
            'totalReservasMes',
            'pagasMes',
            'canceladasMes',
            'reservasPendentes'
        ));
    }

    /**
     * Helper para determinar o range de datas com base no período.
     * @param string $periodo O período solicitado ('hoje', 'semana', 'mes').
     * @return array [Carbon $start, Carbon $end]
     */
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

        $end = $end->endOfDay();
        return [$start, $end];
    }

    /**
     * Helper para calcular o Total Líquido de Caixa para uma data específica.
     * @param string $dateString A data no formato 'Y-m-d'.
     * @return float O valor total líquido (soma de todas as FinancialTransactions).
     */
    private function calculateLiquidCash(string $dateString): float
    {
        $start = Carbon::parse($dateString)->startOfDay();
        $end = Carbon::parse($dateString)->endOfDay();

        $liquidTotal = FinancialTransaction::query()
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        return (float) $liquidTotal;
    }

    /**
     * Retorna os dados resumidos (Cards) para todos os períodos.
     */
    public function getResumo(Request $request)
    {
        Log::info('FinanceiroController: getResumo iniciado (Baseado em FinancialTransactions).');

        try {
            $periodos = ['hoje', 'semana', 'mes'];
            $resultados = [
                'total_recebido' => [],
                'sinais' => [],
                'reservas' => [],
            ];

            $transactionIncomeTypes = [
                'signal', 'full_payment', 'partial_payment', 'payment_settlement',
                'RETEN_CANC_COMP', 'RETEN_CANC_P_COMP', 'RETEN_CANC_S_COMP', 'RETEN_NOSHOW_COMP'
            ];

            foreach ($periodos as $periodo) {
                list($start, $end) = $this->getDateRange($periodo);

                $transacoesNoPeriodo = FinancialTransaction::query()
                    ->whereBetween('paid_at', [$start, $end])
                    ->whereIn('type', $transactionIncomeTypes)
                    ->get();

                $totalRecebido = $transacoesNoPeriodo->sum('amount');
                $totalSinais = $transacoesNoPeriodo->where('type', 'signal')->sum('amount');

                $countReservas = Reserva::query()
                    ->whereBetween('date', [$start, $end])
                    ->where('is_fixed', false)
                    ->count();

                $resultados['total_recebido'][$periodo] = (float) $totalRecebido;
                $resultados['sinais'][$periodo] = (float) $totalSinais;
                $resultados['reservas'][$periodo] = (int) $countReservas;
            }

            return response()->json(['success' => true, 'data' => $resultados]);
        } catch (\Exception $e) {
            Log::error('ERRO CRÍTICO no getResumo: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao carregar resumo.'], 500);
        }
    }

    /**
     * Retorna uma lista de pagamentos pendentes para a tabela.
     */
    public function getPagamentosPendentes()
    {
        Log::info('FinanceiroController: getPagamentosPendentes iniciado.');

        try {
            $statusPendente = 'pending';
            $statusConfirmada = 'confirmed';

            $reservasPendentes = Reserva::query()
                ->whereIn('status', [$statusPendente, $statusConfirmada])
                ->where('is_fixed', false)
                ->where(function ($query) {
                    $query->whereRaw('COALESCE(total_paid, 0) < price')
                          ->orWhereNull('total_paid');
                })
                ->where('date', '>=', Carbon::today()->toDateString())
                ->orderBy('date', 'asc')
                ->limit(30)
                ->get();

            $pendentesFormatados = $reservasPendentes->map(function ($reserva) {
                $valorTotalCobranca = $reserva->price;
                $totalPago = $reserva->total_paid ?? 0;
                $valorRestante = max(0, $valorTotalCobranca - $totalPago);

                return [
                    'id' => $reserva->id,
                    'cliente' => $reserva->client_name,
                    'contato' => $reserva->client_contact,
                    'data' => Carbon::parse($reserva->date)->format('d/m/Y'),
                    'horario' => Carbon::parse($reserva->start_time)->format('H:i'),
                    'valor_total' => (float) $valorTotalCobranca,
                    'total_pago' => (float) $totalPago,
                    'valor_restante' => (float) $valorRestante,
                    'status_pagamento_texto' => $totalPago == 0 ? 'Não Iniciado' : 'Parcial',
                    'link_acoes' => route('admin.reservas.show', $reserva->id),
                ];
            });

            return response()->json(['success' => true, 'data' => $pendentesFormatados]);
        } catch (\Exception $e) {
            Log::error('ERRO no getPagamentosPendentes: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao carregar pendentes.'], 500);
        }
    }

    /**
     * Processa o fechamento do caixa diário.
     */
    public function closeCash(Request $request)
    {
        Log::info('FinanceiroController: closeCash iniciado.');

        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'actual_amount' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $date = Carbon::parse($request->date);
            $actualAmount = (float) $request->actual_amount;
            $dateString = $date->format('Y-m-d');

            $calculatedAmount = $this->calculateLiquidCash($dateString);

            Cashier::updateOrCreate(
                ['date' => $dateString],
                [
                    'calculated_amount' => $calculatedAmount,
                    'actual_amount' => $actualAmount,
                    'status' => 'closed',
                    'closed_by_user_id' => auth()->id(),
                    'closing_time' => Carbon::now(),
                ]
            );

            $difference = round($actualAmount - $calculatedAmount, 2);
            $message = "Caixa do dia {$date->format('d/m/Y')} fechado.";

            DB::commit();
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERRO ao fechar o caixa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao fechar o caixa.'], 500);
        }
    }

    /**
     * Reabre o caixa.
     */
    public function openCash(Request $request)
    {
        Log::info('FinanceiroController: openCash iniciado.');
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'reason' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $dateString = Carbon::parse($request->date)->format('Y-m-d');
            $cashier = Cashier::where('date', $dateString)->first();

            if (!$cashier) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => "Registro não encontrado."], 404);
            }

            $managerName = Auth::user()->name;
            $reopenNote = "[REABERTURA por {$managerName} em " . Carbon::now()->format('d/m/Y H:i:s') . "]: {$request->reason}";

            $cashier->update([
                'status' => 'open',
                'notes' => $cashier->notes ? $cashier->notes . "\n---\n" . $reopenNote : $reopenNote,
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Caixa reaberto."]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERRO ao reabrir o caixa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao reabrir.'], 500);
        }
    }

    /**
     * Verifica se o caixa está fechado.
     */
    public function isCashClosed(string $dateString): bool
    {
        return (bool) Cashier::where('date', $dateString)->where('status', 'closed')->first();
    }
}
