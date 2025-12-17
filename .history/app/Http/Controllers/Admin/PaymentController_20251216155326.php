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
use App\Http\Controllers\FinanceiroController;

class PaymentController extends Controller
{
    /**
     * Corrige integridade de No-Show com estorno.
     */
    private function checkAndCorrectNoShowPaidAmounts()
    {
        $reservasToCorrect = Reserva::where('status', 'no_show')
            ->where('payment_status', 'unpaid')
            ->where(function($q) {
                $q->where('total_paid', '>', 0)->orWhere('final_price', '>', 0);
            })
            ->get();

        if ($reservasToCorrect->isNotEmpty()) {
            DB::transaction(function () use ($reservasToCorrect) {
                foreach ($reservasToCorrect as $reserva) {
                    $reserva->total_paid = 0.00;
                    $reserva->final_price = 0.00;
                    $reserva->save();
                }
            });
        }
    }

    /**
     * Dashboard de Caixa
     */
    public function index(Request $request)
    {
        $this->checkAndCorrectNoShowPaidAmounts();

        $selectedDateString = $request->input('data_reserva')
                                    ?? $request->input('date')
                                    ?? Carbon::today()->toDateString();

        $dateObject = Carbon::parse($selectedDateString);
        $selectedReservaId = $request->input('reserva_id');
        $searchTerm = $request->input('search');

        // 1. CONSULTA PRINCIPAL (TABELA)
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

        // ✅ CORREÇÃO: Removido whereNotNull('user_id') para mostrar agendamentos manuais
        $query->where('is_fixed', false)
              ->whereIn('status', [
                  Reserva::STATUS_CONFIRMADA,
                  Reserva::STATUS_PENDENTE,
                  'completed',
                  'no_show'
              ])
              ->orderBy('start_time', 'asc');

        $reservas = $query->get();

        // 2. CÁLCULOS DE KPIS
        $totalGeralCaixa = FinancialTransaction::sum('amount');

        $totalRecebidoDiaLiquido = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->sum('amount');

        $totalSinaisBrutosDia = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->where('type', 'signal')
            ->sum('amount');

        $totalAntecipadoReservasDia = $reservas->sum('total_paid');

        $totalReservasDia = $reservas->whereIn('status', [
            Reserva::STATUS_CONFIRMADA,
            'completed',
            'no_show'
        ])->count();

        // --- 🎯 PROJEÇÃO FINANCEIRA (SALDO PENDENTE) ---
        // Aqui pegamos apenas o que ainda pode gerar dinheiro (Confirmadas/Pendentes/Parciais)
        $totalExpected = $reservas->sum(fn($r) => $r->final_price > 0 ? $r->final_price : $r->price);

        // Saldo Pendente: Total esperado - Total já pago
        // Se Gleidson pagou 100 de 100, sobra 0. Se Afonso pagou 50 de 100, sobra 50.
        $totalPendingLiquido = $reservas->whereNotIn('status', ['no_show', 'canceled'])
            ->sum(function ($r) {
                $valorTotal = $r->final_price > 0 ? $r->final_price : $r->price;
                $pago = $r->total_paid ?? 0;
                $restante = $valorTotal - $pago;
                return $restante > 0 ? $restante : 0;
            });

        $noShowCount = $reservas->where('status', 'no_show')->count();

        $financialTransactions = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->with(['reserva', 'manager', 'payer'])
            ->orderBy('paid_at', 'desc')
            ->get();

        $cashierRecord = \App\Models\Cashier::where('date', $selectedDateString)->first();
        $cashierStatus = $cashierRecord->status ?? 'open';

        return view('admin.payment.index', [
            'selectedDate' => $selectedDateString,
            'reservas' => $reservas,
            'totalGeralCaixa' => $totalGeralCaixa,
            'totalRecebidoDiaLiquido' => $totalRecebidoDiaLiquido,
            'totalRecebidoDia' => $totalSinaisBrutosDia,
            'totalAntecipadoReservasDia' => $totalAntecipadoReservasDia,
            'totalReservasDia' => $totalReservasDia,
            'totalPending' => $totalPendingLiquido,
            'saldoPendenteLiquido' => $totalPendingLiquido,
            'totalExpected' => $totalExpected,
            'noShowCount' => $noShowCount,
            'highlightReservaId' => $selectedReservaId,
            'financialTransactions' => $financialTransactions,
            'cashierStatus' => $cashierStatus,
        ]);
    }

    /**
     * Processa o Pagamento
     */
    public function processPayment(Request $request, $reservaId)
    {
        try {
            $reserva = Reserva::findOrFail($reservaId);
        } catch (\Exception $e) {
             return response()->json(['success' => false, 'message' => 'Reserva não encontrada.'], 404);
        }

        $financeiroController = app(FinanceiroController::class);
        $reservaDate = \Carbon\Carbon::parse($reserva->date)->toDateString();

        if ($financeiroController->isCashClosed($reservaDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: Caixa fechado para o dia ' . \Carbon\Carbon::parse($reservaDate)->format('d/m/Y'),
            ], 403);
        }

        $request->validate([
            'final_price' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
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
                } elseif ($newTotalPaid > 0) {
                    $paymentStatus = 'partial';
                }

                $reserva->total_paid = $newTotalPaid;
                $reserva->final_price = $finalPrice;
                $reserva->payment_status = $paymentStatus;

                if ($paymentStatus === 'paid') {
                    $reserva->status = 'completed';
                }

                $reserva->save();

                // Recorrência
                if ($reserva->is_recurrent && $request->boolean('apply_to_series')) {
                    Reserva::where('series_id', $reserva->series_id)
                           ->where('date', '>', $reserva->date)
                           ->where('status', '!=', 'canceled')
                           ->update(['final_price' => $finalPrice]);
                }

                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => Auth::id(),
                    'amount' => $amountPaid,
                    'type' => $paymentStatus === 'paid' ? 'full_payment' : 'partial_payment',
                    'payment_method' => $request->payment_method,
                    'description' => 'Pagamento reserva ' . $reserva->id,
                    'paid_at' => Carbon::now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Pagamento de R$ ' . number_format($request->amount_paid, 2, ',', '.') . ' registrado!',
                'status' => $paymentStatus
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro interno.'], 500);
        }
    }

    public function registerNoShow(Request $request, $reservaId)
    {
        try {
             $reserva = Reserva::findOrFail($reservaId);
        } catch (\Exception $e) {
             return response()->json(['success' => false, 'message' => 'Erro.'], 404);
        }

        $financeiroController = app(FinanceiroController::class);
        if ($financeiroController->isCashClosed($reserva->date)) {
            return response()->json(['success' => false, 'message' => 'Caixa fechado.'], 403);
        }

        DB::transaction(function () use ($request, $reserva) {
            $paidAmount = (float) $request->paid_amount;
            $reserva->status = 'no_show';

            if ($request->boolean('should_refund')) {
                $reserva->payment_status = 'unpaid';
                $reserva->total_paid = 0;
                $reserva->final_price = 0;

                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'amount' => -$paidAmount,
                    'type' => 'refund',
                    'payment_method' => 'cash_out',
                    'paid_at' => Carbon::now(),
                ]);
            } else {
                $reserva->payment_status = 'retained';
                $reserva->final_price = $paidAmount;
            }
            $reserva->save();
        });

        return response()->json(['success' => true, 'message' => 'Falta registrada.']);
    }
}
