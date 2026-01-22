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
        $selectedDateString = $request->input('date') ?? Carbon::today()->toDateString();
        $dateObject = Carbon::parse($selectedDateString);
        $selectedArenaId = $request->input('arena_id');
        $searchTerm = $request->input('search');

        // 🎯 NOVO: Filtro de Dívidas em Aberto
        $filterDebts = $request->input('filter') === 'debts';

        // 3. Consulta de Reservas
        $query = Reserva::with(['user', 'arena']);

        if ($filterDebts) {
            // Busca apenas o que foi concluído mas não foi totalmente pago (Dívidas acumuladas)
            $query->where('status', 'completed')
                ->whereIn('payment_status', ['unpaid', 'partial']);
        } elseif ($request->input('reserva_id')) {
            $query->where('id', $request->input('reserva_id'));
        } else {
            $query->whereDate('date', $dateObject);
        }

        // Filtros comuns (Arena e Busca)
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

        $reservas = $query->whereNotNull('user_id')
            ->where('is_fixed', false)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, 'completed', 'no_show', 'canceled'])
            ->orderBy($filterDebts ? 'date' : 'start_time', $filterDebts ? 'desc' : 'asc')
            ->get();

        // 4. Lógica de Fechamento (Sempre baseada na data real para o JS)
        $totalReservasGeralCount = Reserva::whereDate('date', $dateObject)
            ->whereNotNull('user_id')
            ->where('is_fixed', false)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, 'completed', 'no_show', 'canceled'])
            ->count();

        // 5. Saldo Líquido Real (Dinheiro em caixa na data selecionada)
        $totalRecebidoDiaLiquido = FinancialTransaction::whereDate('paid_at', $dateObject)->sum('amount');

        // 6. Faturamento por Arena (Baseado na data)
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

        // 7. Transações (Proteção contra nulos aplicada na View, mas garantimos a query aqui)
        $transQuery = FinancialTransaction::whereDate('paid_at', $dateObject);
        if ($selectedArenaId) $transQuery->where('arena_id', $selectedArenaId);
        $financialTransactions = $transQuery->with(['reserva', 'manager', 'payer', 'arena'])
            ->orderBy('paid_at', 'desc')
            ->get();

        // 8. Auditoria
        $cashierRecord = Cashier::where('date', $selectedDateString)->first();
        $cashierStatus = $cashierRecord->status ?? 'open';
        $cashierHistory = Cashier::with('user')->orderBy('date', 'desc')->limit(10)->get();

        // 9. KPIs Dinâmicos
        $totalExpected = $reservas->whereNotIn('status', ['canceled', 'rejected'])
            ->sum(fn($r) => $r->final_price ?? $r->price);

        $totalPending = $reservas->whereNotIn('status', ['canceled', 'rejected'])
            ->sum(fn($r) => max(0, ($r->final_price ?? $r->price) - $r->total_paid));

        return view('admin.payment.index', [
            'selectedDate'               => $selectedDateString,
            'reservas'                   => $reservas,
            'filterDebts'                => $filterDebts, // Envia para a view saber se o filtro está ativo
            'faturamentoPorArena'        => $faturamentoPorArena,
            'totalRecebidoDiaLiquido'    => $totalRecebidoDiaLiquido,
            'totalAntecipadoReservasDia' => $reservas->sum('total_paid'),
            'totalReservasDia'           => $reservas->count(),
            'totalReservasGeral'         => $totalReservasGeralCount,
            'totalPending'               => $totalPending,
            'totalExpected'              => $totalExpected,
            'noShowCount'                => $reservas->where('status', 'no_show')->count(),
            'financialTransactions'      => $financialTransactions,
            'cashierStatus'              => $cashierStatus,
            'cashierHistory'             => $cashierHistory,
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
     * Processa o Pagamento (Integrado com trava de segurança e suporte a virada de meia-noite)
     */
    public function processPayment(Request $request, $reservaId)
    {
        $reserva = Reserva::with('arena')->findOrFail($reservaId);

        // 1. Trava de Caixa (Impede alteração em dias encerrados)
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
            'payment_date'    => 'nullable|date', // Validamos a data que vem do Modal
        ]);

        try {
            $paymentStatus = 'pending';

            DB::transaction(function () use ($validated, $reserva, &$paymentStatus) {
                $finalPrice = round((float) $validated['final_price'], 2);
                $amountReceivedNow = round((float) $validated['amount_paid'], 2);
                $newTotalPaid = round((float) $reserva->total_paid + $amountReceivedNow, 2);

                if ($newTotalPaid > $finalPrice) {
                    throw new \Exception("O valor total pago não pode ser maior que o preço final.");
                }

                $newVisualStatus = $reserva->status;
                if ($newTotalPaid >= $finalPrice && $finalPrice > 0) {
                    $paymentStatus = 'paid';
                    $newVisualStatus = 'completed';
                } elseif ($newTotalPaid > 0) {
                    $paymentStatus = 'partial';
                }

                $reserva->update([
                    'total_paid'     => $newTotalPaid,
                    'final_price'    => $finalPrice,
                    'payment_status' => $paymentStatus,
                    'status'         => $newVisualStatus,
                    'manager_id'     => Auth::id(),
                ]);

                if (!empty($validated['apply_to_series']) && $reserva->recurrent_series_id) {
                    Reserva::where('recurrent_series_id', $reserva->recurrent_series_id)
                        ->where('date', '>', $reserva->date)
                        ->where('payment_status', 'unpaid')
                        ->update([
                            'price' => $finalPrice,
                            'final_price' => $finalPrice
                        ]);
                }

                // --- Lógica de Data Operacional (O Segredo para fechar o caixa certo) ---
                // Prioridade: 1. Data enviada pelo modal | 2. Data da reserva | 3. Agora
                $dataParaRegistro = $validated['payment_date'] ?? $reserva->date ?? now();

                // 6. Auditoria Financeira
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
                        'paid_at'        => $dataParaRegistro, // Gravamos com a data correta do caixa aberto
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Pagamento processado e status sincronizado!',
                'status'  => $paymentStatus
            ]);
        } catch (\Exception $e) {
            Log::error("Erro pagamento #{$reservaId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }


    /**
     * Registra Falta (No-Show) - Com lógica de data operacional para virada de dia
     */
    public function registerNoShow(Request $request, $reservaId)
    {
        $reserva = Reserva::with(['arena', 'user'])->findOrFail($reservaId);

        // --- AJUSTE DE DATA OPERACIONAL ---
        // Priorizamos a data enviada pela view (caixa aberto), caso contrário usamos a data da reserva.
        $dataOperacional = $request->input('payment_date') ?? $reserva->date;

        // 🛡️ TRAVA: Verifica se o caixa da data em questão já está encerrado
        if (\App\Http\Controllers\FinanceiroController::isCashClosed($dataOperacional)) {
            return response()->json([
                'success' => false,
                'message' => 'Ação bloqueada: O caixa de ' . \Carbon\Carbon::parse($dataOperacional)->format('d/m/Y') . ' já está encerrado.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $totalOriginalPago = round((float) $reserva->total_paid, 2);
            $shouldRefund = $request->boolean('should_refund');
            $valorParaEstornar = round((float) $request->input('refund_amount', 0), 2);

            $horario = \Carbon\Carbon::parse($reserva->start_time)->format('H:i');
            $infoContexto = " | Jogo: " . \Carbon\Carbon::parse($reserva->date)->format('d/m') . " às {$horario}";

            if ($totalOriginalPago > 0) {
                if ($shouldRefund && $valorParaEstornar > 0) {
                    // 💰 REGISTRA A SAÍDA (ESTORNO) NO CAIXA DA DATA OPERACIONAL
                    FinancialTransaction::create([
                        'reserva_id'     => $reserva->id,
                        'arena_id'       => $reserva->arena_id,
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => -$valorParaEstornar,
                        'type'           => 'refund',
                        'payment_method' => 'cash_out',
                        'description'    => "SAÍDA (Estorno No-Show) #{$reserva->id} | Cliente: {$reserva->client_name}" . $infoContexto,
                        'paid_at'        => $dataOperacional, // <--- DATA CORRIGIDA
                    ]);

                    FinancialTransaction::where('reserva_id', $reserva->id)
                        ->where('type', '!=', 'refund')
                        ->update([
                            'description' => DB::raw("CONCAT(description, ' (No-Show c/ Estorno em " . date('d/m') . ")')")
                        ]);
                } else {
                    // 🔒 RETENÇÃO (Multa)
                    // Atualiza as transações originais para o tipo multa
                    FinancialTransaction::where('reserva_id', $reserva->id)
                        ->update([
                            'type'           => 'no_show_penalty',
                            'payment_method' => 'retained_funds',
                            'description'    => DB::raw("CONCAT(description, ' [RETIDO COMO MULTA EM " . date('d/m') . "]')")
                        ]);

                    // LOG visual que aparece no extrato da data operacional
                    FinancialTransaction::create([
                        'reserva_id'     => $reserva->id,
                        'arena_id'       => $reserva->arena_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => 0,
                        'type'           => 'no_show_penalty',
                        'payment_method' => 'retained_funds',
                        'description'    => "LOG: Falta marcada. Valor de R$ {$totalOriginalPago} (Reserva #{$reserva->id}) retido como multa.",
                        'paid_at'        => $dataOperacional, // <--- DATA CORRIGIDA
                    ]);
                }
            }

            // Lógica de bloqueio de usuário (mantida)
            if ($reserva->user) {
                $user = $reserva->user;
                $user->increment('no_show_count');
                if ($user->no_show_count >= 3) {
                    $user->update(['is_blocked' => true]);
                }
            }

            app(\App\Http\Controllers\ReservaController::class)->recreateFixedSlot($reserva);
            $reserva->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Falta registrada e financeiro processado com sucesso.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro no No-Show #{$reservaId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🎯 LANÇAR COMO PENDÊNCIA (Pagar Depois):
     * Finaliza a reserva para liberar o caixa do dia, mas mantém o status financeiro como 'unpaid'.
     */
    public function markAsPendingDebt(Request $request, $reservaId)
    {
        $reserva = Reserva::findOrFail($reservaId);

        // 🛡️ TRAVA: Verifica se o caixa da data da reserva já está encerrado
        if (\App\Http\Controllers\FinanceiroController::isCashClosed($reserva->date)) {
            return response()->json([
                'success' => false,
                'message' => 'Ação bloqueada: O caixa de ' . \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') . ' já está encerrado.'
            ], 403);
        }

        // Se já está totalmente paga, não faz sentido pendenciar
        $totalDevido = ($reserva->final_price ?? $reserva->price);
        if ($reserva->total_paid >= $totalDevido && $totalDevido > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Esta reserva já consta como paga no sistema.'
            ], 422);
        }

        try {
            DB::transaction(function () use ($reserva) {
                // Atualizamos a reserva para 'completed' para que o JS de fechamento
                // entenda que o compromisso operacional do dia foi resolvido.
                $reserva->update([
                    'status' => 'completed',
                    'payment_status' => ($reserva->total_paid > 0) ? 'partial' : 'unpaid',
                    'manager_id' => Auth::id(),
                    'notes' => $reserva->notes . " | [Dívida Pendente autorizada em " . now()->format('d/m H:i') . "]"
                ]);

                // 💡 NOTA: Não criamos FinancialTransaction aqui.
                // A transação só nasce no dia que o dinheiro REALMENTE entrar.
            });

            return response()->json([
                'success' => true,
                'message' => 'Reserva marcada como pendência. O horário foi liberado para fechamento de caixa.'
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao pendenciar reserva #{$reservaId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno ao processar.'], 500);
        }
    }
}
