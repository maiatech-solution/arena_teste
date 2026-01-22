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
            ->when($selectedArenaId, function ($q) use ($selectedArenaId) {
                return $q->where('arena_id', $selectedArenaId);
            })
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, 'completed', 'no_show', 'canceled'])
            ->count();

        // 5. Saldo Líquido Real (Dinheiro em caixa na data selecionada - Ajustado para Arena)
        $totalRecebidoDiaLiquido = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->when($selectedArenaId, function ($q) use ($selectedArenaId) {
                return $q->where('arena_id', $selectedArenaId);
            })
            ->sum('amount');

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

        // 8. Auditoria (AJUSTADO: Status e Histórico agora filtram por Arena)
        $cashierRecord = Cashier::where('date', $selectedDateString)
            ->when($selectedArenaId, function ($q) use ($selectedArenaId) {
                return $q->where('arena_id', $selectedArenaId);
            })
            ->first();

        $cashierStatus = $cashierRecord->status ?? 'open';

        $cashierHistory = Cashier::with(['user', 'arena'])
            ->when($selectedArenaId, function ($q) use ($selectedArenaId) {
                return $q->where('arena_id', $selectedArenaId);
            })
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        // 9. KPIs Dinâmicos
        $totalExpected = $reservas->whereNotIn('status', ['canceled', 'rejected'])
            ->sum(fn($r) => $r->final_price ?? $r->price);

        $totalPending = $reservas->whereNotIn('status', ['canceled', 'rejected'])
            ->sum(fn($r) => max(0, ($r->final_price ?? $r->price) - $r->total_paid));

        return view('admin.payment.index', [
            'selectedDate'               => $selectedDateString,
            'reservas'                   => $reservas,
            'filterDebts'                => $filterDebts,
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
     * Registra Falta (No-Show) - Com lógica de proteção de histórico financeiro
     */
    public function registerNoShow(Request $request, $reservaId)
    {
        $reserva = Reserva::with(['arena', 'user'])->findOrFail($reservaId);

        // --- AJUSTE DE DATA OPERACIONAL ---
        $dataOperacional = $request->input('payment_date') ?? $reserva->date;

        // 🛡️ TRAVA: Verifica se o caixa da data já está encerrado
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

            // Guardamos os dados importantes em variáveis antes de deletar a reserva
            $clienteNome = $reserva->client_name ?? $reserva->user?->name ?? 'Cliente Externo';
            $horario = \Carbon\Carbon::parse($reserva->start_time)->format('H:i');
            $infoContexto = " | Jogo original: " . \Carbon\Carbon::parse($reserva->date)->format('d/m') . " às {$horario}";

            if ($totalOriginalPago > 0) {
                if ($shouldRefund && $valorParaEstornar > 0) {
                    // 💰 REGISTRA A SAÍDA (ESTORNO)
                    FinancialTransaction::create([
                        'reserva_id'     => null, // REFINAMENTO: Desvinculamos para preservar o registro após o delete
                        'arena_id'       => $reserva->arena_id,
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => -$valorParaEstornar,
                        'type'           => 'refund',
                        'payment_method' => 'cash_out',
                        'description'    => "SAÍDA (Estorno No-Show) Ref. Reserva #{$reserva->id} | Cliente: {$clienteNome}" . $infoContexto,
                        'paid_at'        => $dataOperacional,
                    ]);

                    // Atualizamos as transações antigas para informar que houve estorno
                    FinancialTransaction::where('reserva_id', $reserva->id)
                        ->where('type', '!=', 'refund')
                        ->update([
                            'reserva_id'  => null, // REFINAMENTO: Protege contra exclusão em cascata
                            'description' => DB::raw("CONCAT(description, ' (No-Show c/ Estorno em " . date('d/m') . ")')")
                        ]);
                } else {
                    // 🔒 RETENÇÃO (Multa): Transformamos os pagamentos existentes em multa e desvinculamos da reserva
                    FinancialTransaction::where('reserva_id', $reserva->id)
                        ->update([
                            'reserva_id'     => null, // REFINAMENTO: Protege contra exclusão em cascata
                            'type'           => 'no_show_penalty',
                            'payment_method' => 'retained_funds',
                            'description'    => DB::raw("CONCAT(description, ' [RETIDO COMO MULTA EM " . date('d/m') . " - Reserva #{$reserva->id}]')")
                        ]);

                    // LOG visual no extrato do dia
                    FinancialTransaction::create([
                        'reserva_id'     => null,
                        'arena_id'       => $reserva->arena_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => 0,
                        'type'           => 'no_show_penalty',
                        'payment_method' => 'retained_funds',
                        'description'    => "LOG: Falta marcada. Valor de R$ {$totalOriginalPago} (Ref #{$reserva->id}) retido como multa.",
                        'paid_at'        => $dataOperacional,
                    ]);
                }
            }

            // Lógica de bloqueio de usuário
            if ($reserva->user) {
                $user = $reserva->user;
                $user->increment('no_show_count');
                if ($user->no_show_count >= 3) {
                    $user->update(['is_blocked' => true]);
                }
            }

            // Recria o horário se for fixista e deleta a reserva física
            app(\App\Http\Controllers\ReservaController::class)->recreateFixedSlot($reserva);
            $reserva->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Falta registrada e financeiro preservado.']);
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
    /**
     * 🎯 LANÇAR COMO PENDÊNCIA (Pagar Depois):
     * Finaliza a reserva operacionalmente mas exige um motivo para a dívida.
     */
    public function markAsPendingDebt(Request $request, $reservaId)
    {
        $reserva = Reserva::findOrFail($reservaId);

        // 1. Validação do Motivo (Obrigatório para auditoria)
        $validated = $request->validate([
            'reason' => 'required|string|min:5|max:255',
        ]);

        // 🛡️ TRAVA: Verifica se o caixa já está encerrado
        if (\App\Http\Controllers\FinanceiroController::isCashClosed($reserva->date)) {
            return response()->json([
                'success' => false,
                'message' => 'Ação bloqueada: O caixa de ' . \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') . ' já está encerrado.'
            ], 403);
        }

        // Se já está paga, impede a ação
        $totalDevido = ($reserva->final_price ?? $reserva->price);
        if ($reserva->total_paid >= $totalDevido && $totalDevido > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Esta reserva já consta como paga no sistema.'
            ], 422);
        }

        try {
            DB::transaction(function () use ($reserva, $validated) {
                // Atualizamos status para 'completed' (o jogo aconteceu)
                // Mantemos o payment_status em aberto (partial ou unpaid)
                $reserva->update([
                    'status' => 'completed',
                    'payment_status' => ($reserva->total_paid > 0) ? 'partial' : 'unpaid',
                    'manager_id' => Auth::id(),
                    // Registramos o MOTIVO real nas notas
                    'notes' => $reserva->notes . " | [DÍVIDA AUTORIZADA]: " . $validated['reason'] . " (por " . Auth::user()->name . " em " . now()->format('d/m H:i') . ")"
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Reserva marcada como pendência de pagamento com sucesso.'
            ]);
        } catch (\Exception $e) {
            Log::error("Erro ao pendenciar reserva #{$reservaId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno ao processar.'], 500);
        }
    }

    /**
     * 💸 MOVIMENTAÇÃO AVULSA: Sangria (Saída) ou Reforço (Entrada)
     * Refinado para garantir vínculo obrigatório com uma Arena.
     */
    public function storeAvulsa(Request $request)
    {
        // 1. Validação: arena_id agora é 'required' para evitar transações sem dono
        $validated = $request->validate([
            'date'           => 'required|date',
            'type'           => 'required|in:in,out',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'description'    => 'required|string|max:255',
            'arena_id'       => 'required|exists:arenas,id', // Ajustado: nullable -> required
        ], [
            'arena_id.required' => 'Selecione a Arena para vincular esta movimentação.',
        ]);

        try {
            // 2. Trava de segurança: impede movimentação em dia com caixa fechado
            if (\App\Http\Controllers\FinanceiroController::isCashClosed($validated['date'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ação bloqueada: O caixa de ' . \Carbon\Carbon::parse($validated['date'])->format('d/m/Y') . ' já está encerrado.'
                ], 403);
            }

            // 3. Lógica do Valor: Entrada (+) ou Saída (-)
            $finalAmount = $validated['type'] === 'out'
                ? -abs($validated['amount'])
                : abs($validated['amount']);

            // 4. Identificação do tipo para o banco de dados
            $transactionType = $validated['type'] === 'out' ? 'sangria' : 'reforco';
            $prefixLabel = $validated['type'] === 'out' ? '🔴 SANGRIA: ' : '🟢 REFORÇO: ';

            // 5. Criação da transação (Garantindo persistência auditável)
            FinancialTransaction::create([
                'arena_id'       => $validated['arena_id'], // Usa o ID validado explicitamente
                'manager_id'     => Auth::id(),
                'amount'         => $finalAmount,
                'type'           => $transactionType,
                'payment_method' => $validated['payment_method'],
                'description'    => $prefixLabel . $validated['description'],
                // Registra a data do caixa com o horário real da operação
                'paid_at'        => $validated['date'] . ' ' . now()->format('H:i:s'),
            ]);

            return response()->json([
                'success' => true,
                'message' => ($validated['type'] === 'out' ? 'Saída' : 'Entrada') . ' registrada com sucesso!'
            ]);
        } catch (\Exception $e) {
            Log::error("Erro na movimentação avulsa: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao processar movimentação: ' . $e->getMessage()
            ], 500);
        }
    }
}
