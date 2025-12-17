<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// Modelos do usuário
use App\Models\Reserva;
use App\Models\User;
use App\Models\FinancialTransaction;
use App\Models\Cashier;

// 🎯 CRÍTICO: Importar o FinanceiroController para acessar o helper isCashClosed
use App\Http\Controllers\FinanceiroController;

class PaymentController extends Controller
{
    /**
     * Verifica e corrige reservas de No-Show onde o valor pago deveria ter sido zerado.
     * Esta função garante a integridade dos KPIs corrigindo dados inconsistentes no banco.
     */
    private function checkAndCorrectNoShowPaidAmounts()
    {
        // Busca reservas que são 'no_show', mas por erro de lógica anterior ainda possuem saldo ou preço.
        $reservasToCorrect = Reserva::where('status', Reserva::STATUS_NO_SHOW)
            ->where('payment_status', 'unpaid')
            ->where(function($q) {
                $q->where('total_paid', '>', 0)
                  ->orWhere('final_price', '>', 0);
            })
            ->get();

        if ($reservasToCorrect->isNotEmpty()) {
            DB::transaction(function () use ($reservasToCorrect) {
                foreach ($reservasToCorrect as $reserva) {
                    $oldPaid = $reserva->total_paid;
                    $oldPrice = $reserva->final_price;

                    // Zera os campos para refletir o estorno total no financeiro
                    $reserva->total_paid = 0.00;
                    $reserva->final_price = 0.00;
                    $reserva->save();

                    Log::warning("CORREÇÃO AUTOMÁTICA: Reserva ID {$reserva->id} (No-Show) sincronizada para R$ 0.00. (Antigo Pago: {$oldPaid} / Antigo Preço: {$oldPrice})");
                }
            });
        }
    }

    /**
     * Exibe o Dashboard de Caixa e gerencia filtros de data, ID e Pesquisa.
     * 🎯 AJUSTADO: Lógica de Saldo Pendente corrigida para evitar divergências em dias passados.
     */
    public function index(Request $request)
    {
        // 🛡️ PASSO DE INTEGRIDADE
        $this->checkAndCorrectNoShowPaidAmounts();

        // 1. Definição da Data (Prioridade para data_reserva ou date vindo da URL)
        $selectedDateString = $request->input('data_reserva')
                                    ?? $request->input('date')
                                    ?? Carbon::today()->toDateString();

        $dateObject = Carbon::parse($selectedDateString);
        $selectedReservaId = $request->input('reserva_id');
        $searchTerm = $request->input('search');

        // =========================================================================
        // 1. CONSULTA DE RESERVAS PARA A TABELA
        // =========================================================================

        $query = Reserva::with(['user', 'manager']);

        if ($selectedReservaId) {
            $query->where('id', $selectedReservaId);
        } else {
            $query->whereDate('date', $dateObject);

            if ($searchTerm) {
                $searchWildcard = '%' . $searchTerm . '%';
                $query->where(function ($q) use ($searchWildcard) {
                    $q->where('client_name', 'LIKE', $searchWildcard)
                      ->orWhere('client_contact', 'LIKE', $searchWildcard);
                });
            }
        }

        // Filtros de Segurança e Exclusão de Fixos
        $query->whereNotNull('user_id')
              ->where('is_fixed', false)
              ->whereIn('status', [
                  Reserva::STATUS_CONFIRMADA,
                  Reserva::STATUS_PENDENTE,
                  Reserva::STATUS_CONCLUIDA,
                  Reserva::STATUS_NO_SHOW,
                  Reserva::STATUS_CANCELADA, // 'cancelled' conforme seu Model
                  Reserva::STATUS_REJEITADA
              ])
              ->orderBy('start_time', 'asc');

        $reservas = $query->get();

        // =========================================================================
        // 2. CÁLCULOS DOS KPIS FINANCEIROS (CORREÇÃO DE DIVERGÊNCIA)
        // =========================================================================

        // A) Receita Garantida: Soma o que já foi efetivamente pago para as reservas do dia.
        $totalAntecipadoReservasDia = $reservas->sum('total_paid');

        // B) Movimentação Líquida: Todas as transações financeiras ocorridas nesta data física.
        $totalRecebidoDiaLiquido = FinancialTransaction::whereDate('paid_at', $dateObject)->sum('amount');

        // C) Total de Reservas do Dia (Apenas as que deveriam comparecer)
        $totalReservasDia = $reservas->whereIn('status', [
            Reserva::STATUS_CONFIRMADA,
            Reserva::STATUS_CONCLUIDA,
            Reserva::STATUS_NO_SHOW
        ])->count();

        // D) RECEITA BRUTA PREVISTA (TOTAL PREVISTO)
        // Inclui tudo que não foi cancelado.
        $totalExpected = $reservas->whereNotIn('status', [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA])
            ->sum(fn($r) => $r->final_price ?? $r->price);

        // E) 🎯 SALDO PENDENTE A RECEBER (AQUI ESTAVA O ERRO)
        // Só calculamos saldo pendente para reservas que ainda estão ATIVAS (confirmed/pending).
        // Se a reserva foi de ontem e ficou como No-Show ou Completed, ela não deve mais nada ao "caixa esperado".
        $totalPendingLiquido = $reservas->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            ->sum(function ($r) {
                $total = $r->final_price ?? $r->price;
                $pago = $r->total_paid ?? 0;
                return max(0, $total - $pago);
            });

        $noShowCount = $reservas->where('status', Reserva::STATUS_NO_SHOW)->count();

        // Transações para Auditoria
        $financialTransactions = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->with(['reserva', 'manager', 'payer'])
            ->orderBy('paid_at', 'desc')
            ->get();

        // Status do Caixa
        $financeiroController = app(FinanceiroController::class);
        $cashierRecord = \App\Models\Cashier::where('date', $selectedDateString)->first();
        $cashierStatus = $cashierRecord->status ?? 'open';

        // Retorno da View com todos os dados sincronizados
        return view('admin.payment.index', [
            'selectedDate' => $selectedDateString,
            'reservas' => $reservas,
            'totalGeralCaixa' => FinancialTransaction::sum('amount'),
            'totalRecebidoDiaLiquido' => $totalRecebidoDiaLiquido,
            'totalAntecipadoReservasDia' => $totalAntecipadoReservasDia,
            'totalReservasDia' => $totalReservasDia,
            'totalPending' => $totalPendingLiquido,
            'totalExpected' => $totalExpected,
            'noShowCount' => $noShowCount,
            'highlightReservaId' => $selectedReservaId,
            'financialTransactions' => $financialTransactions,
            'cashierStatus' => $cashierStatus,
        ]);
    }

    /**
     * Processa a baixa de pagamento no caixa.
     */
    public function processPayment(Request $request, $reservaId)
    {
        try {
            $reserva = Reserva::findOrFail($reservaId);
            $financeiroController = app(FinanceiroController::class);
            $reservaDate = Carbon::parse($reserva->date)->toDateString();

            // Validação de Caixa Fechado
            if ($financeiroController->isCashClosed($reservaDate)) {
                return response()->json([
                    'success' => false,
                    'message' => "O caixa de {$reservaDate} está fechado. Reabra para baixar o valor.",
                ], 403);
            }

            $request->validate([
                'final_price' => 'required|numeric|min:0',
                'amount_paid' => 'required|numeric|min:0',
                'payment_method' => 'required|string',
                'apply_to_series' => 'nullable|boolean',
            ]);

            DB::transaction(function () use ($request, $reserva) {
                $finalPrice = (float) $request->final_price;
                $amountPaid = (float) $request->amount_paid;

                $reserva->total_paid += $amountPaid;
                $reserva->final_price = $finalPrice;

                // Atualização de Status
                if (round($reserva->total_paid, 2) >= round($finalPrice, 2)) {
                    $reserva->payment_status = 'paid';
                    $reserva->status = Reserva::STATUS_CONCLUIDA;
                } else {
                    $reserva->payment_status = 'partial';
                }

                $reserva->save();

                // Lógica de Recorrência (Atualiza preços futuros da série)
                if ($reserva->is_recurrent && $request->boolean('apply_to_series')) {
                    Reserva::where('recurrent_series_id', $reserva->recurrent_series_id)
                           ->where('date', '>', $reserva->date)
                           ->where('status', '!=', Reserva::STATUS_CANCELADA)
                           ->update(['final_price' => $finalPrice]);
                    Log::info("Série recorrente {$reserva->recurrent_series_id} teve preço atualizado.");
                }

                // Registro na Auditoria Financeira
                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => Auth::id(),
                    'amount' => $amountPaid,
                    'type' => $reserva->payment_status === 'paid' ? 'full_payment' : 'partial_payment',
                    'payment_method' => $request->payment_method,
                    'description' => "Baixa manual de caixa para reserva #{$reserva->id}",
                    'paid_at' => Carbon::now(),
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Pagamento processado com sucesso!']);

        } catch (\Exception $e) {
            Log::error("Erro no processPayment: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno ao processar pagamento.'], 500);
        }
    }

    /**
     * Registra a Falta (No-Show) e gerencia Estornos/Multas.
     */
    public function registerNoShow(Request $request, $reservaId)
    {
        try {
            $reserva = Reserva::with('user')->findOrFail($reservaId);
            $reservaDate = Carbon::parse($reserva->date)->toDateString();

            if (app(FinanceiroController::class)->isCashClosed($reservaDate)) {
                return response()->json(['success' => false, 'message' => 'Caixa fechado para esta data.'], 403);
            }

            $request->validate([
                'notes' => 'nullable|string|max:500',
                'block_user' => 'nullable|boolean',
                'paid_amount' => 'required|numeric|min:0',
                'should_refund' => 'required|boolean',
            ]);

            DB::transaction(function () use ($request, $reserva) {
                $paidAmount = (float) $request->paid_amount;
                $shouldRefund = $request->boolean('should_refund');

                $reserva->status = Reserva::STATUS_NO_SHOW;
                $reserva->notes = $request->notes;

                if ($paidAmount > 0) {
                    if ($shouldRefund) {
                        // Devolução total ao cliente
                        $reserva->payment_status = 'unpaid';
                        $reserva->total_paid = 0.00;
                        $reserva->final_price = 0.00;

                        FinancialTransaction::create([
                            'reserva_id' => $reserva->id,
                            'user_id' => $reserva->user_id,
                            'manager_id' => Auth::id(),
                            'amount' => -$paidAmount, // Valor negativo (saída)
                            'type' => 'refund',
                            'payment_method' => 'cash_out',
                            'description' => "ESTORNO No-Show reserva #{$reserva->id}",
                            'paid_at' => Carbon::now(),
                        ]);
                    } else {
                        // Retenção do valor como multa
                        $reserva->payment_status = 'retained';
                        $reserva->final_price = $paidAmount;
                    }
                } else {
                    $reserva->payment_status = 'unpaid';
                    $reserva->total_paid = 0.00;
                }

                $reserva->save();

                // Lógica de Penalização de Usuário
                if ($request->boolean('block_user') && $reserva->user) {
                    $user = $reserva->user;
                    $user->increment('no_show_count');
                    if ($user->no_show_count >= 3) {
                        $user->is_blocked = true;
                    }
                    $user->save();
                    Log::info("Usuário #{$user->id} penalizado por No-Show.");
                }
            });

            return response()->json(['success' => true, 'message' => 'Falta registrada com sucesso!']);

        } catch (\Exception $e) {
            Log::error("Erro no registerNoShow: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao processar No-Show.'], 500);
        }
    }
}
