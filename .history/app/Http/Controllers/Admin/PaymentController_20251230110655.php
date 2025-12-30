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

        // Trava de Segurança
        if (Cashier::where('date', $reserva->date)->where('status', 'closed')->exists()) {
            return response()->json(['success' => false, 'message' => 'Caixa Fechado.'], 403);
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

                if (round($newTotalPaid, 2) >= round($finalPrice, 2)) {
                    $paymentStatus = 'paid';
                    $reserva->status = 'completed';
                } elseif ($newTotalPaid > 0) {
                    $paymentStatus = 'partial';
                }

                $reserva->total_paid = $newTotalPaid;
                $reserva->final_price = $finalPrice;
                $reserva->payment_status = $paymentStatus;
                $reserva->save();

                // Transação Financeira
                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => Auth::id(),
                    'amount' => $amountPaid,
                    'type' => $paymentStatus === 'paid' ? 'full_payment' : 'partial_payment',
                    'payment_method' => $request->payment_method,
                    'description' => 'Pagamento reserva #' . $reserva->id,
                    'paid_at' => now(),
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Sucesso!', 'status' => $paymentStatus]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro processamento.'], 500);
        }
    }

    /**
     * Registra Falta (No-Show) - Com lógica de bloqueio de usuário
     */
    public function registerNoShow(Request $request, $reservaId)
    {
        $reserva = Reserva::findOrFail($reservaId);

        if (Cashier::where('date', $reserva->date)->where('status', 'closed')->exists()) {
            return response()->json(['success' => false, 'message' => 'Caixa Fechado.'], 403);
        }

        try {
            DB::transaction(function () use ($request, $reserva) {
                $paidAmount = (float) $request->paid_amount;
                $shouldRefund = $request->boolean('should_refund');

                $reserva->status = 'no_show';
                $reserva->notes = $request->notes;

                if ($paidAmount > 0 && $shouldRefund) {
                    $reserva->payment_status = 'unpaid';
                    $reserva->total_paid = 0;
                    $reserva->final_price = 0;

                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => Auth::id(),
                        'amount' => -$paidAmount,
                        'type' => 'refund',
                        'payment_method' => 'cash_out',
                        'description' => 'Estorno No-Show #' . $reserva->id,
                        'paid_at' => now(),
                    ]);
                } else {
                    $reserva->payment_status = $paidAmount > 0 ? 'retained' : 'unpaid';
                }
                $reserva->save();

                // Lógica de Bloqueio (O que você tinha anteriormente)
                if ($request->boolean('block_user') && $reserva->user) {
                    $user = $reserva->user;
                    $user->no_show_count = ($user->no_show_count ?? 0) + 1;
                    if ($user->no_show_count >= 3) { $user->is_blocked = true; }
                    $user->save();
                }
            });

            return response()->json(['success' => true, 'message' => 'No-Show registrado!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro Falta.'], 500);
        }
    }
}
