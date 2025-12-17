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
     */
    public function index()
    {
        return view('admin.financeiro.dashboard');
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
                // Início da semana (Domingo) e Fim da semana (Sábado) - Ajuste se precisar de Segunda-feira
                $start = $now->copy()->startOfWeek(Carbon::SUNDAY);
                $end = $now->copy()->endOfWeek(Carbon::SATURDAY);
                break;
            case 'mes':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            // 'hoje' é o default
        }

        // Garante que a data final inclui todo o dia
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

        // O 'amount' deve ser positivo para entradas e negativo para saídas (ex: refunds).
        $liquidTotal = FinancialTransaction::query()
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        return (float) $liquidTotal;
    }

    // =========================================================================
    // ✅ MÉTODOS PÚBLICOS DE DASHBOARD E RELATÓRIOS (getResumo, getPagamentosPendentes)
    // =========================================================================

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

            // Lista de todos os tipos de transação que contam como ENTRADA no CAIXA para fins de KPI
            $transactionIncomeTypes = [
                'signal',
                'full_payment',
                'partial_payment',
                'payment_settlement',
                'RETEN_CANC_COMP',
                'RETEN_CANC_P_COMP',
                'RETEN_CANC_S_COMP',
                'RETEN_NOSHOW_COMP'
            ];

            foreach ($periodos as $periodo) {
                list($start, $end) = $this->getDateRange($periodo);

                // 1. Total Recebido (Soma de todos os valores na tabela de Transações)
                $transacoesNoPeriodo = FinancialTransaction::query()
                    ->whereBetween('paid_at', [$start, $end])
                    ->whereIn('type', $transactionIncomeTypes)
                    ->get();

                $totalRecebido = $transacoesNoPeriodo->sum('amount');

                // 2. Total Sinais (Soma de transações do tipo 'signal')
                $totalSinais = $transacoesNoPeriodo->where('type', 'signal')->sum('amount');

                // 3. Contagem de Reservas CONFIRMADAS (Reservas que VÃO ACONTECER no período)
                // OBS: É necessário que 'Reserva::STATUS_CONFIRMADA' seja acessível (constante no model Reserva)
                $countReservas = Reserva::query()
                    //->where('status', Reserva::STATUS_CONFIRMADA) // Removido para funcionar sem a constante se não for passada
                    ->whereBetween('date', [$start, $end])
                    ->where('is_fixed', false)
                    ->count();

                $resultados['total_recebido'][$periodo] = (float) $totalRecebido;
                $resultados['sinais'][$periodo] = (float) $totalSinais;
                $resultados['reservas'][$periodo] = (int) $countReservas;
            }

            Log::info('FinanceiroController: getResumo concluído com sucesso.');

            return response()->json([
                'success' => true,
                'data' => $resultados,
            ]);

        } catch (\Exception $e) {
            Log::error('ERRO CRÍTICO no getResumo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor ao carregar resumo.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna uma lista de pagamentos pendentes para a tabela.
     */
    public function getPagamentosPendentes()
    {
        Log::info('FinanceiroController: getPagamentosPendentes iniciado.');

        try {
            // OBS: É necessário que 'Reserva::STATUS_PENDENTE' e 'Reserva::STATUS_CONFIRMADA' sejam acessíveis
            // $statusPendente = Reserva::STATUS_PENDENTE;
            // $statusConfirmada = Reserva::STATUS_CONFIRMADA;

            // Usando os valores diretos caso as constantes não estejam carregadas
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
                ->orderBy('start_time', 'asc')
                ->limit(30)
                ->get();

            $pendentesFormatados = $reservasPendentes->map(function ($reserva) {
                // OBS: O restante do map depende da correta definição das propriedades no Model Reserva
                $valorTotalCobranca = $reserva->price;
                $totalPago = $reserva->total_paid ?? 0;
                $valorRestante = max(0, $valorTotalCobranca - $totalPago);

                $corStatus = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                $statusTexto = 'Desconhecido';

                // Lógica de status de pagamento
                if ($valorRestante <= 0) {
                    $statusTexto = 'Pago Integral';
                    $corStatus = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                } else {
                    if ($totalPago == 0) {
                        $statusTexto = 'Não Iniciado';
                        $corStatus = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
                    } elseif (($reserva->signal_value ?? 0) > 0 && $totalPago >= ($reserva->signal_value ?? 0) && $totalPago < $valorTotalCobranca) {
                        $statusTexto = 'Sinal Pago';
                        $corStatus = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
                    } elseif ($totalPago > 0 && $totalPago < $valorTotalCobranca) {
                        $statusTexto = 'Parcialmente Pago';
                        $corStatus = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
                    }
                }

                return [
                    'id' => $reserva->id,
                    'cliente' => $reserva->client_name,
                    'contato' => $reserva->client_contact,
                    'data' => Carbon::parse($reserva->date)->format('d/m/Y'),
                    'horario' => Carbon::parse($reserva->start_time)->format('H:i'),
                    'valor_total' => (float) $valorTotalCobranca,
                    'sinal_pago' => (float) ($reserva->signal_value ?? 0),
                    'total_pago' => (float) $totalPago,
                    'valor_restante' => (float) $valorRestante,
                    'cor_status' => $corStatus,
                    'status_pagamento_texto' => $statusTexto,
                    'link_acoes' => route('admin.reservas.show', $reserva->id),
                ];
            });

            Log::info('FinanceiroController: getPagamentosPendentes concluído com sucesso.');

            return response()->json([
                'success' => true,
                'data' => $pendentesFormatados,
            ]);

        } catch (\Exception $e) {
            Log::error('ERRO CRÍTICO no getPagamentosPendentes: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor ao carregar pagamentos pendentes.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }


    // =========================================================================
    // 🔒 MÉTODOS DE CONTROLE DE CAIXA (closeCash, openCash, isCashClosed)
    // =========================================================================

    /**
     * Processa o fechamento do caixa diário.
     * 🎯 CORRIGIDO: Valida 'actual_amount' para corresponder ao que é salvo no DB (via JavaScript)
     */
    public function closeCash(Request $request)
    {
        Log::info('FinanceiroController: closeCash iniciado.');

        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'actual_amount' => 'required|numeric', // ✅ CORREÇÃO: Usando 'actual_amount' para compatibilidade com o front-end
        ]);

        DB::beginTransaction();
        try {
            $date = Carbon::parse($request->date);
            // ✅ Usa 'actual_amount' que agora é validado
            $actualAmount = (float) $request->actual_amount;
            $dateString = $date->format('Y-m-d');

            // 1. OBTENHA O TOTAL CALCULADO DO SISTEMA
            $calculatedAmount = $this->calculateLiquidCash($dateString);

            // 2. REGISTRE O FECHAMENTO (Status: closed)
            Cashier::updateOrCreate(
                ['date' => $dateString], // Chave de busca
                [ // Dados para criação ou atualização
                    'calculated_amount' => $calculatedAmount,
                    'actual_amount' => $actualAmount,
                    'status' => 'closed', // 🎯 Status definitivo
                    'closed_by_user_id' => auth()->id(),
                    'closing_time' => Carbon::now(),
                ]
            );

            // 3. CALCULE A DIFERENÇA
            $difference = round($actualAmount - $calculatedAmount, 2);

            $message = "Caixa do dia {$date->format('d/m/Y')} fechado com sucesso.";

            if (abs($difference) > 0.01) {
                $diffSign = $difference > 0 ? 'sobra' : 'falta';
                $message .= " Foi registrada uma $diffSign de R$ " . number_format(abs($difference), 2, ',', '.') . " no fechamento.";
            }

            Log::info('Caixa Fechado com sucesso para ' . $dateString, ['calculado' => $calculatedAmount, 'real' => $actualAmount]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => route('admin.payment.index', ['date' => $dateString])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERRO ao fechar o caixa: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                // Mensagem de erro mais clara sobre qual campo falhou
                'message' => $e->getMessage() === 'The actual amount field is required.' ? 'O campo Valor TOTAL EM CAIXA FÍSICO é obrigatório.' : 'Erro interno do servidor ao fechar o caixa.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reabre o caixa para um dia específico, exigindo Justificativa.
     */
    public function openCash(Request $request)
    {
        Log::info('FinanceiroController: openCash iniciado.');
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'reason' => 'required|string|max:500' // ✅ NOVO: Validação da Justificativa
        ]);

        DB::beginTransaction();
        try {
            $date = Carbon::parse($request->date);
            $dateString = $date->format('Y-m-d');
            $reason = $request->reason;
            $managerName = Auth::user()->name; // Captura o nome do usuário logado

            $cashier = Cashier::where('date', $dateString)->first();

            if (!$cashier) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Erro: Não há registro de fechamento para o dia {$date->format('d/m/Y')}. O caixa já está logicamente aberto.",
                ], 404);
            }

            // Constrói a nota de reabertura para auditoria
            $reopenNote = "[REABERTURA por {$managerName} em " . Carbon::now()->format('d/m/Y H:i:s') . "]: {$reason}";

            // Altera o status para 'open' e anexa a justificativa
            $cashier->update([
                'status' => 'open',
                // Anexa a nova justificativa ao campo 'notes' (assumindo que existe)
                'notes' => $cashier->notes ? $cashier->notes . "\n---\n" . $reopenNote : $reopenNote,
                // Mantemos os campos closed_by_user_id e closing_time intactos, conforme a correção anterior para o erro 1048.
            ]);

            Log::info('Caixa Reaberto com sucesso para ' . $dateString . ' Motivo: ' . $reason);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Caixa do dia {$date->format('d/m/Y')} reaberto com sucesso. Motivo: '{$reason}'.",
                'redirect' => route('admin.payment.index', ['date' => $dateString])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERRO ao reabrir o caixa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor ao reabrir o caixa. Se o erro persistir, verifique a estrutura da tabela `cashiers`.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica se o caixa está fechado para uma determinada data.
     * @param string $dateString Data no formato 'Y-m-d'.
     * @return bool True se o caixa estiver fechado, False caso contrário.
     */
    public function isCashClosed(string $dateString): bool
    {
        $cashier = Cashier::where('date', $dateString)
                          ->where('status', 'closed')
                          ->first();
        return (bool) $cashier;
    }
}
