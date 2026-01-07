<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// Modelos
use App\Models\Reserva;
use App\Models\User;
use App\Models\FinancialTransaction;
use App\Models\Cashier; // 🎯 Certifique-se de que o model Cashier aponta para a tabela correta

// 🎯 Importar o FinanceiroController para acessar helpers se necessário
use App\Http\Controllers\FinanceiroController;

class PaymentController extends Controller
{
    /**
     * Verifica e corrige reservas de No-Show (Integridade de Dados)
     */
    private function checkAndCorrectNoShowPaidAmounts()
    {
        $reservasToCorrect = Reserva::where('status', 'no_show')
            ->where('payment_status', 'unpaid')
            ->where('total_paid', '>', 0)
            ->where('final_price', '>', 0)
            ->get();

        if ($reservasToCorrect->isNotEmpty()) {
            DB::transaction(function () use ($reservasToCorrect) {
                foreach ($reservasToCorrect as $reserva) {
                    $reserva->total_paid = 0.00;
                    $reserva->final_price = 0.00;
                    $reserva->save();
                    Log::warning("CORREÇÃO: Reserva ID {$reserva->id} sincronizada.");
                }
            });
        }
    }

    /**
     * Dashboard de Caixa e Histórico
     */
    public function index(Request $request)
    {
        $this->checkAndCorrectNoShowPaidAmounts();

        $selectedDateString = $request->input('data_reserva') ?? $request->input('date') ?? Carbon::today()->toDateString();
        $dateObject = Carbon::parse($selectedDateString);
        $selectedReservaId = $request->input('reserva_id');
        $searchTerm = $request->input('search');

        // 1. Consulta de Reservas
        $query = Reserva::with('user');
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

        $reservas = $query->whereNotNull('user_id')
            ->where('is_fixed', false)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, 'completed', 'no_show', 'canceled'])
            ->orderBy('start_time', 'asc')
            ->get();

        // 2. Transações e Movimentação
        $totalRecebidoDiaLiquido = FinancialTransaction::whereDate('paid_at', $dateObject)->sum('amount');

        $financialTransactions = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->with(['reserva', 'manager', 'payer'])
            ->orderBy('paid_at', 'desc')
            ->get();

        // 🎯 LÓGICA DO HISTÓRICO: Busca os fechamentos reais para a tabela do fim da página
        $cashierHistory = Cashier::with('user')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        // Status do Caixa
        $cashierRecord = Cashier::where('date', $selectedDateString)->first();
        $cashierStatus = $cashierRecord->status ?? 'open';

        // KPIs de Dashboard
        $totalExpected = $reservas->whereNotIn('status', ['canceled', 'rejected'])->sum(fn($r) => $r->final_price ?? $r->price);
        $totalPending = $reservas->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            ->sum(fn($r) => max(0, ($r->final_price ?? $r->price) - $r->total_paid));

        return view('admin.payment.index', [
            'selectedDate' => $selectedDateString,
            'reservas' => $reservas,
            'totalGeralCaixa' => FinancialTransaction::sum('amount'),
            'totalRecebidoDiaLiquido' => $totalRecebidoDiaLiquido,
            'totalAntecipadoReservasDia' => $reservas->sum('total_paid'),
            'totalReservasDia' => $reservas->whereIn('status', [Reserva::STATUS_CONFIRMADA, 'completed', 'no_show'])->count(),
            'totalPending' => $totalPending,
            'totalExpected' => $totalExpected,
            'noShowCount' => $reservas->where('status', 'no_show')->count(),
            'financialTransactions' => $financialTransactions,
            'cashierStatus' => $cashierStatus,
            'cashierHistory' => $cashierHistory, // Agora a variável vai populada
            'highlightReservaId' => $selectedReservaId,
        ]);
    }

    /**
     * 🎯 FECHAR CAIXA: Grava a auditoria no banco
     */
    public function closeCash(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'calculated_amount' => 'required|numeric',
            'actual_amount' => 'required|numeric',
        ]);

        try {
            $difference = (float)$request->actual_amount - (float)$request->calculated_amount;

            Cashier::updateOrCreate(
                ['date' => $request->date],
                [
                    'user_id' => Auth::id(),
                    'calculated_amount' => $request->calculated_amount,
                    'actual_amount' => $request->actual_amount,
                    'difference' => $difference,
                    'status' => 'closed',
                    'closed_at' => now(),
                ]
            );

            return response()->json(['success' => true, 'message' => 'Caixa fechado com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao fechar caixa.'], 500);
        }
    }

    /**
     * 🎯 REABRIR CAIXA: Registra justificativa
     */
    public function reopenCash(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'reason' => 'required|string|min:5',
        ]);

        try {
            $cashier = Cashier::where('date', $request->date)->firstOrFail();
            $cashier->update([
                'status' => 'open',
                'reopen_reason' => $request->reason,
                'reopened_at' => now(),
                'reopened_by' => Auth::id()
            ]);

            return response()->json(['success' => true, 'message' => 'Caixa reaberto. Alterações permitidas.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao reabrir o caixa.'], 500);
        }
    }

    /**
     * Processa o Pagamento (Integrado com trava de segurança)
     */
    public function processPayment(Request $request, $reservaId)
    {
        $reserva = Reserva::findOrFail($reservaId);

        // 1. Trava de Segurança usando a lógica centralizada (mais seguro)
        $financeiro = app(FinanceiroController::class);
        if ($financeiro->isCashClosed($reserva->date)) {
            return response()->json(['success' => false, 'message' => 'O caixa do dia ' . \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') . ' já está encerrado.'], 403);
        }

        $request->validate([
            'final_price' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'apply_to_series' => 'nullable|boolean',
        ]);

        try {
            $paymentStatus = 'pending';

            DB::transaction(function () use ($request, $reserva, &$paymentStatus) {
                $finalPrice = (float) $request->final_price;
                $amountPaid = (float) $request->amount_paid;
                $newTotalPaid = (float) $reserva->total_paid + $amountPaid;

                // Determina Status
                if (round($newTotalPaid, 2) >= round($finalPrice, 2)) {
                    $paymentStatus = 'paid';
                    $reserva->status = 'completed';
                } elseif ($newTotalPaid > 0) {
                    $paymentStatus = 'partial';
                }

                // Atualiza a Reserva
                $reserva->total_paid = $newTotalPaid;
                $reserva->final_price = $finalPrice;
                $reserva->payment_status = $paymentStatus;
                $reserva->manager_id = Auth::id(); // Registra quem recebeu
                $reserva->save();

                // 2. Registro Financeiro (Corrigido com arena_id)
                if ($amountPaid > 0) {
                    FinancialTransaction::create([
                        'reserva_id'     => $reserva->id,
                        'arena_id'       => $reserva->arena_id, // ✅ ESSENCIAL: Evita erro 1364
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => $amountPaid,
                        'type'           => $paymentStatus === 'paid' ? 'full_payment' : 'partial_payment',
                        'payment_method' => $request->payment_method,
                        'description'    => 'Pagamento reserva #' . $reserva->id,
                        'paid_at'        => now(),
                    ]);
                }
            });

            return response()->json(['success' => true, 'message' => 'Pagamento processado com sucesso!', 'status' => $paymentStatus]);
        } catch (\Exception $e) {
            Log::error("Erro no processamento de pagamento ID {$reservaId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Registra Falta (No-Show) - Com lógica de bloqueio de usuário
     */
    public function registerNoShow(Request $request, $reservaId)
    {
        $reserva = Reserva::findOrFail($reservaId);

        // 1. Trava de Segurança: Caixa Fechado
        $financeiro = app(FinanceiroController::class);
        if ($financeiro->isCashClosed($reserva->date)) {
            return response()->json(['success' => false, 'message' => 'Erro: O caixa deste dia está fechado.'], 403);
        }

        try {
            DB::beginTransaction();

            $paidAmount = (float) $request->input('paid_amount', $reserva->total_paid);
            $shouldRefund = $request->boolean('should_refund');

            // 2. Registro Financeiro (Tratando Arena ID)
            if ($paidAmount > 0) {
                if ($shouldRefund) {
                    // Estorno: Valor Negativo
                    FinancialTransaction::create([
                        'reserva_id'     => $reserva->id,
                        'arena_id'       => $reserva->arena_id, // ✅ Adicionado
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => -$paidAmount,
                        'type'           => 'refund',
                        'payment_method' => 'cash_out',
                        'description'    => 'Estorno No-Show (Falta) #' . $reserva->id,
                        'paid_at'        => now(),
                    ]);
                } else {
                    // Retenção: Criamos a transação de compensação (como fizemos no outro controller)
                    FinancialTransaction::create([
                        'reserva_id'     => $reserva->id,
                        'arena_id'       => $reserva->arena_id, // ✅ Adicionado
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => $paidAmount,
                        'type'           => FinancialTransaction::TYPE_RETEN_NOSHOW_COMP,
                        'payment_method' => 'retained_funds',
                        'description'    => 'Retenção por No-Show (Falta) #' . $reserva->id,
                        'paid_at'        => now(),
                    ]);
                }
            }

            // 3. Lógica de Bloqueio de Usuário (Mantida)
            if ($request->boolean('block_user') && $reserva->user) {
                $user = $reserva->user;
                $user->no_show_count = ($user->no_show_count ?? 0) + 1;
                if ($user->no_show_count >= 3) {
                    $user->is_blocked = true;
                }
                $user->save();
            }

            // 4. LIBERAÇÃO DO SLOT (O que faltava para ficar verde)
            // Chamamos a função de recriar o slot verde que está no outro Controller
            $reservaController = app(\App\Http\Controllers\ReservaController::class);
            $reservaController->recreateFixedSlot($reserva);

            // 5. Deleta a reserva original (Para não ficar o "vermelho" por cima do verde)
            $reserva->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Falta registrada e horário liberado!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro no No-Show: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao processar falta: ' . $e->getMessage()], 500);
        }
    }
}
