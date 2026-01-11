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
use App\Models\Cashier;

// Importação do Fiscal
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
        // 1. Integridade
        $this->checkAndCorrectNoShowPaidAmounts();

        // 2. Definição de Data e Filtros
        $selectedDateString = $request->input('data_reserva') ?? $request->input('date') ?? Carbon::today()->toDateString();
        $dateObject = Carbon::parse($selectedDateString);
        $selectedReservaId = $request->input('reserva_id');
        $selectedArenaId = $request->input('arena_id');
        $searchTerm = $request->input('search');

        // 3. Consulta de Reservas do Dia (Base para KPIs da View)
        $query = Reserva::with(['user', 'arena']);

        if ($selectedReservaId) {
            $query->where('id', $selectedReservaId);
        } else {
            $query->whereDate('date', $dateObject);

            if ($selectedArenaId) {
                $query->where('arena_id', $selectedArenaId);
            }

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

        // 4. Lógica de Fechamento (SEMPRE GERAL - Independente de Filtro)
        // Precisamos saber o total do dia para o JS validar o botão de fechamento
        $totalReservasGeralCount = Reserva::whereDate('date', $dateObject)
            ->whereNotNull('user_id')
            ->where('is_fixed', false)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, 'completed', 'no_show', 'canceled'])
            ->count();

        // 5. Movimentação Financeira (Real vs Contextual)
        // Saldo real em caixa hoje (Geral)
        $totalRecebidoDiaLiquido = FinancialTransaction::whereDate('paid_at', $dateObject)->sum('amount');

        // 6. Faturamento Segmentado por Arena
        $arenasAtivas = \App\Models\Arena::all();
        $faturamentoReal = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->select('arena_id', DB::raw('SUM(amount) as total'))
            ->groupBy('arena_id')
            ->get();

        $faturamentoPorArena = $arenasAtivas->map(function ($arena) use ($faturamentoReal) {
            $transacao = $faturamentoReal->firstWhere('arena_id', $arena->id);
            return (object)[
                'id'    => $arena->id,
                'name'  => $arena->name,
                'total' => $transacao ? $transacao->total : 0
            ];
        });

        // 7. Histórico de Transações Detalhado (Respeita Filtro)
        $transQuery = FinancialTransaction::whereDate('paid_at', $dateObject);
        if ($selectedArenaId) {
            $transQuery->where('arena_id', $selectedArenaId);
        }
        $financialTransactions = $transQuery->with(['reserva', 'manager', 'payer', 'arena'])
            ->orderBy('paid_at', 'desc')
            ->get();

        // 8. Auditoria de Fechamento (Cashier)
        $cashierRecord = Cashier::where('date', $selectedDateString)->first();
        $cashierStatus = $cashierRecord->status ?? 'open';
        $cashierHistory = Cashier::with('user')->orderBy('date', 'desc')->limit(10)->get();

        // 9. KPIs Dinâmicos (Baseados na lista filtrada de $reservas)
        $totalExpected = $reservas->whereNotIn('status', ['canceled', 'rejected'])
            ->sum(fn($r) => $r->final_price ?? $r->price);

        $totalPending = $reservas->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            ->sum(fn($r) => max(0, ($r->final_price ?? $r->price) - $r->total_paid));

        // 10. Retorno
        return view('admin.payment.index', [
            'selectedDate'               => $selectedDateString,
            'reservas'                   => $reservas,
            'faturamentoPorArena'        => $faturamentoPorArena,
            'totalGeralCaixa'            => FinancialTransaction::sum('amount'),
            'totalRecebidoDiaLiquido'    => $totalRecebidoDiaLiquido,
            'totalAntecipadoReservasDia' => $reservas->sum('total_paid'),
            'totalReservasDia'           => $reservas->whereIn('status', [Reserva::STATUS_CONFIRMADA, 'completed', 'no_show'])->count(),
            'totalReservasGeral'         => $totalReservasGeralCount, // 🎯 CRUCIAL para o JS de fechamento
            'totalPending'               => $totalPending,
            'totalExpected'              => $totalExpected,
            'noShowCount'                => $reservas->where('status', 'no_show')->count(),
            'financialTransactions'      => $financialTransactions,
            'cashierStatus'              => $cashierStatus,
            'cashierHistory'             => $cashierHistory,
            'highlightReservaId'         => $selectedReservaId,
        ]);
    }



    /**
     * 🎯 FECHAR CAIXA: Grava a auditoria no banco com cálculo automático de segurança
     */
    public function closeCash(Request $request)
    {
        // 1. Validamos apenas a data e o valor físico (contado pelo operador)
        // Removi o 'calculated_amount' da validação obrigatória para evitar o erro 500
        $validated = $request->validate([
            'date'          => 'required|date',
            'actual_amount' => 'required|numeric',
        ]);

        try {
            $date = $validated['date'];
            $arenaId = $request->input('arena_id'); // Captura a arena se enviada

            // 2. CÁLCULO DE SEGURANÇA (O servidor pergunta ao banco quanto deve ter em caixa)
            $calculatedSystem = FinancialTransaction::whereDate('paid_at', $date)
                ->when($arenaId, function ($q) use ($arenaId) {
                    return $q->where('arena_id', $arenaId);
                })
                ->sum('amount');

            // 3. Precisão decimal
            $calculated = round((float)$calculatedSystem, 2);
            $actual     = round((float)$validated['actual_amount'], 2);
            $difference = round($actual - $calculated, 2);

            // 4. Persistência no banco de dados
            // Usamos updateOrCreate para permitir correções se o caixa for reaberto
            Cashier::updateOrCreate(
                [
                    'date'     => $date,
                    'arena_id' => $arenaId // Garante o fechamento por unidade
                ],
                [
                    'user_id'           => Auth::id(),
                    'calculated_amount' => $calculated, // Gravado via servidor, sem risco de erro nulo
                    'actual_amount'     => $actual,
                    'difference'        => $difference,
                    'status'            => 'closed',
                    'closing_time'      => now(),
                    'notes'             => $request->input('notes'),
                ]
            );

            return response()->json([
                'success'    => true,
                'message'    => 'Caixa fechado com sucesso!',
                'difference' => $difference,
                'system_sum' => $calculated
            ]);
        } catch (\Exception $e) {
            \Log::error("Erro crítico ao fechar caixa: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar o fechamento: ' . $e->getMessage()
            ], 500);
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
        $reserva = Reserva::with('arena')->findOrFail($reservaId);

        // 1. Trava de Caixa (Chamada direta)
        if (\App\Http\Controllers\FinanceiroController::isCashClosed($reserva->date)) {
            return response()->json([
                'success' => false,
                'message' => 'O caixa do dia ' . \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') . ' já está encerrado.'
            ], 403);
        }

        $validated = $request->validate([
            'final_price'     => 'required|numeric|min:0',
            'amount_paid'     => 'required|numeric|min:0',
            'payment_method'  => 'required|string|max:50',
            'apply_to_series' => 'nullable|boolean',
        ]);

        try {
            $paymentStatus = 'pending';

            DB::transaction(function () use ($validated, $reserva, &$paymentStatus) {
                $finalPrice = round((float) $validated['final_price'], 2);
                $amountReceivedNow = round((float) $validated['amount_paid'], 2);
                $newTotalPaid = round((float) $reserva->total_paid + $amountReceivedNow, 2);

                // 2. Validação de segurança: Não permitir pagar mais que o valor final
                if ($newTotalPaid > $finalPrice) {
                    throw new \Exception("O valor total pago (R$ " . number_format($newTotalPaid, 2, ',', '.') . ") não pode ser maior que o preço final (R$ " . number_format($finalPrice, 2, ',', '.') . ").");
                }

                // 3. Determinação Lógica do Status
                if ($newTotalPaid >= $finalPrice) {
                    $paymentStatus = 'paid';
                    $reserva->status = 'completed';
                } elseif ($newTotalPaid > 0) {
                    $paymentStatus = 'partial';
                }

                $reserva->update([
                    'total_paid'     => $newTotalPaid,
                    'final_price'    => $finalPrice,
                    'payment_status' => $paymentStatus,
                    'manager_id'     => Auth::id(),
                ]);

                // 4. Ajuste de Recorrência
                if (!empty($validated['apply_to_series']) && $reserva->recurrent_series_id) {
                    Reserva::where('recurrent_series_id', $reserva->recurrent_series_id)
                        ->where('date', '>', $reserva->date)
                        ->where('payment_status', 'unpaid')
                        ->update([
                            'price' => $finalPrice,
                            'final_price' => $finalPrice
                        ]);
                }

                // 5. Auditoria
                if ($amountReceivedNow > 0) {
                    FinancialTransaction::create([
                        'reserva_id'     => $reserva->id,
                        'arena_id'       => $reserva->arena_id,
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => $amountReceivedNow,
                        'type'           => ($paymentStatus === 'paid') ? FinancialTransaction::TYPE_PAYMENT : 'partial_payment',
                        'payment_method' => $validated['payment_method'],
                        'description'    => "Pagamento reserva #{$reserva->id} | {$reserva->arena->name}",
                        'paid_at'        => now(),
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Pagamento processado!',
                'status'  => $paymentStatus
            ]);
        } catch (\Exception $e) {
            Log::error("Erro pagamento #{$reservaId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() // Retorna a mensagem da exceção (ex: valor maior que o permitido)
            ], 422);
        }
    }


    /**

     * Registra Falta (No-Show) - Com lógica de bloqueio de usuário

     */

    public function registerNoShow(Request $request, $reservaId)
    {
        // 1. Eager loading (PerfSormance)
        $reserva = Reserva::with(['arena', 'user'])->findOrFail($reservaId);

        // 2. Trava de Segurança (Chamada estática direta)
        if (\App\Http\Controllers\FinanceiroController::isCashClosed($reserva->date)) {
            return response()->json([
                'success' => false,
                'message' => 'O caixa do dia ' . \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') . ' já está encerrado.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $totalOriginalPago = round((float) $reserva->total_paid, 2);
            $shouldRefund = $request->boolean('should_refund');
            $valorParaEstornar = round((float) $request->input('refund_amount', 0), 2);
            $motivoFalta = $request->input('no_show_reason', 'Falta não justificada');

            $horario = \Carbon\Carbon::parse($reserva->start_time)->format('H:i');
            $infoContexto = " | Arena: {$reserva->arena->name} [{$horario}]";

            if ($totalOriginalPago > 0) {
                if ($shouldRefund && $valorParaEstornar > 0) {
                    // 💰 REGISTRA O ESTORNO
                    FinancialTransaction::create([
                        'reserva_id'     => $reserva->id,
                        'arena_id'       => $reserva->arena_id,
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => -$valorParaEstornar,
                        'type'           => 'refund',
                        'payment_method' => 'cash_out',
                        'description'    => "ESTORNO No-Show #{$reserva->id} | Cliente: {$reserva->client_name} | Motivo: {$motivoFalta}" . $infoContexto,
                        'paid_at'        => now(),
                    ]);

                    FinancialTransaction::where('reserva_id', $reserva->id)
                        ->where('type', '!=', 'refund')
                        ->update([
                            'description' => DB::raw("CONCAT(description, ' (No-Show c/ Estorno)')")
                        ]);
                } else {
                    // 🔒 RETENÇÃO (Transforma o sinal em multa)
                    FinancialTransaction::where('reserva_id', $reserva->id)
                        ->update([
                            'type'           => 'no_show_penalty',
                            'payment_method' => 'retained_funds',
                            'description'    => DB::raw("CONCAT(description, ' [VALOR RETIDO POR NO-SHOW]')")
                        ]);
                }
            }

            // 3. Penalidade ao Usuário
            if ($reserva->user) {
                $user = $reserva->user;
                $user->increment('no_show_count');

                if ($user->no_show_count >= 3) {
                    $user->update(['is_blocked' => true]);
                    Log::warning("Usuário ID {$user->id} bloqueado automaticamente (3 No-Shows).");
                }
            }

            // 4. Liberação do Slot no Calendário
            app(\App\Http\Controllers\ReservaController::class)->recreateFixedSlot($reserva);

            // 5. Deleção da Reserva (A nova Foreign Key 'set null' agirá aqui)
            $reserva->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Falta registrada com sucesso e horário liberado.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro Crítico no No-Show Reserva #{$reservaId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar: ' . $e->getMessage()
            ], 500);
        }
    }
}
