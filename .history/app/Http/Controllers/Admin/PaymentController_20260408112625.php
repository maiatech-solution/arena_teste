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
     * Atualizado para suportar o status 'debt' (Dívidas Ativas) 🛡️
     */
    public function index(Request $request)
    {
        $this->checkAndCorrectNoShowPaidAmounts();

        $reservaIdParam = $request->input('reserva_id');
        $dateParam = $request->input('date');
        $selectedDateString = $dateParam ?? Carbon::today()->toDateString();
        $dateObject = Carbon::parse($selectedDateString);
        $selectedArenaId = $request->input('arena_id');
        $searchTerm = $request->input('search');
        $filterDebts = $request->input('filter') === 'debts';

        // 🕵️ LOG DE ENTRADA DO CAIXA
        \Log::info("=== MONITOR DE CAIXA ===");
        \Log::info("Data Selecionada: " . $selectedDateString);

        // 1. GLOBAL: Transações de todas as arenas (Para os cards laterais não zerarem)
        $allTransactionsOfDay = FinancialTransaction::whereDate('paid_at', $dateObject)->get();

        // 2. FILTRADO: Transações da arena selecionada
        $financialTransactions = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->when($selectedArenaId, fn($q) => $q->where('arena_id', $selectedArenaId))
            ->with(['reserva', 'manager', 'payer', 'arena'])
            ->orderBy('paid_at', 'desc')
            ->get();

        // 3. IDs de reservas com movimentação hoje
        $reservaIdsComMovimentacaoHoje = $financialTransactions->pluck('reserva_id')->filter()->unique();

        // 4. Consulta de Reservas com Filtro de Status 🛡️
        // 🚫 Ignora Rejeitadas, Canceladas e Horários em Manutenção
        $query = Reserva::with(['user', 'arena', 'transactions'])
            ->whereNotIn('status', ['rejected', 'cancelled', 'maintenance']);

        if ($filterDebts) {
            // ✅ CORREÇÃO: Filtro agora inclui 'debt' para aparecer na lista de pendências
            $query->whereIn('status', ['completed', 'debt'])
                ->whereIn('payment_status', ['unpaid', 'partial']);
        } elseif ($reservaIdParam) {
            $query->where('id', $reservaIdParam);
        } else {
            $query->where(function ($q) use ($dateObject, $reservaIdsComMovimentacaoHoje) {
                $q->whereDate('date', $dateObject)
                    ->orWhereIn('id', $reservaIdsComMovimentacaoHoje);
            });
        }

        if ($selectedArenaId) $query->where('arena_id', $selectedArenaId);

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('client_name', 'LIKE', "%$searchTerm%")
                    ->orWhere('client_contact', 'LIKE', "%$searchTerm%");
            });
        }

        $reservas = $query->whereNotNull('user_id')
            ->where('is_fixed', false)
            ->orderBy($filterDebts ? 'date' : 'start_time', 'asc')
            ->get();

        // 🚀 4.1 SINCRONIZAÇÃO FORÇADA (VERSÃO BLINDADA POR DATA)
        foreach ($reservas as $reserva) {
            $diretas = (float) $reserva->transactions()
                ->whereDate('paid_at', $dateObject)
                ->sum('amount');

            $desvinculadas = (float) FinancialTransaction::whereNull('reserva_id')
                ->where('arena_id', $reserva->arena_id)
                ->whereDate('paid_at', $dateObject)
                ->where('description', 'LIKE', "%#{$reserva->id}%")
                ->sum('amount');

            $realPaid = round($diretas + $desvinculadas, 2);
            $reserva->total_paid = $realPaid;
        }

        // 5. Saldo Líquido Real do Dia
        $totalRecebidoDiaLiquido = round((float) $financialTransactions->sum('amount'), 2);

        // 6. Lógica de Status do Caixa e Histórico
        $cashierRecord = Cashier::where('date', $selectedDateString)
            ->when($selectedArenaId, fn($q) => $q->where('arena_id', $selectedArenaId))
            ->first();
        $cashierStatus = $cashierRecord->status ?? 'open';

        $cashierHistory = Cashier::with(['user', 'arena'])
            ->when($selectedArenaId, fn($q) => $q->where('arena_id', $selectedArenaId))
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        // 7. Faturamento por Arena
        $arenasAtivas = \App\Models\Arena::all();
        $faturamentoPorArena = $arenasAtivas->map(function ($arena) use ($allTransactionsOfDay) {
            return (object)[
                'id'    => $arena->id,
                'name'  => $arena->name,
                'total' => (float) $allTransactionsOfDay->where('arena_id', $arena->id)->sum('amount')
            ];
        });

        // 🚫 8. CONTADOR DE FALTAS (No-Show)
        $noShowCount = $allTransactionsOfDay
            ->when($selectedArenaId, fn($q) => $q->where('arena_id', $selectedArenaId))
            ->filter(function ($t) {
                return str_contains($t->description, 'Falta marcada') ||
                    str_contains($t->description, 'No-Show');
            })->count();

        return view('admin.payment.index', [
            'selectedDate'            => $selectedDateString,
            'reservas'                => $reservas,
            'financialTransactions'   => $financialTransactions,
            'totalRecebidoDiaLiquido' => $totalRecebidoDiaLiquido,
            'faturamentoPorArena'     => $faturamentoPorArena,
            'cashierStatus'           => $cashierStatus,
            'cashierHistory'          => $cashierHistory,
            'totalReservasDia'        => $reservas->count(),
            'totalPending'            => $reservas->whereIn('status', ['confirmed', 'pending'])
                ->sum(fn($r) => max(0, (float)($r->final_price ?? $r->price) - (float)$r->total_paid)),
            'noShowCount'             => $noShowCount,
            'totalAuthorizedDebt'     => $reservas->whereIn('status', ['completed', 'debt']) // ✅ CORREÇÃO: Card do topo
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->sum(fn($r) => max(0, (float)($r->final_price ?? $r->price) - (float)$r->total_paid)),
        ]);
    }


    /**
     * 🎯 FECHAR CAIXA: Grava a auditoria no banco com cálculo automático de segurança por arena.
     */
    public function closeCash(Request $request)
    {
        $validated = $request->validate([
            'date'          => 'required|date',
            'actual_amount' => 'required|numeric',
            'arena_id'      => 'required|exists:arenas,id',
        ]);

        try {
            $date = $validated['date'];
            $arenaId = $validated['arena_id'];

            // --- 🛡️ VALIDAÇÃO PROFISSIONAL DE PENDÊNCIAS ---
            // Só travamos se houver reservas CONFIRMADAS ou PENDENTES que JÁ TERMINARAM.
            // Se for "Dívida Ativa" (Partial) ou "Pago" (Paid), o sistema permite o fechamento.
            $agora = now();
            $pendenciasCriticas = \App\Models\Reserva::where('arena_id', $arenaId)
                ->whereDate('date', $date)
                ->where('is_fixed', false)
                ->whereIn('status', ['confirmed', 'pending'])
                ->where('payment_status', 'unpaid') // Dívida ativa (partial) não trava o caixa
                ->get()
                ->filter(function ($r) use ($agora) {
                    // Monta o Carbon do fim do jogo para comparar com a hora atual
                    $fimJogo = \Carbon\Carbon::parse($r->date->format('Y-m-d') . ' ' . $r->end_time);
                    return $agora->greaterThan($fimJogo);
                });

            if ($pendenciasCriticas->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "🚨 Bloqueio: Existem {$pendenciasCriticas->count()} jogo(s) finalizados sem definição financeira (Quitado, Falta ou Dívida). Resolva-os para fechar."
                ], 403);
            }
            // ------------------------------------------------

            $calculatedSystem = FinancialTransaction::whereDate('paid_at', $date)
                ->where('arena_id', $arenaId)
                ->sum('amount');

            $calculated = round((float)$calculatedSystem, 2);
            $actual     = round((float)$validated['actual_amount'], 2);
            $difference = round($actual - $calculated, 2);

            // 🚀 REGRA DE AUTORIZAÇÃO DO SUPERVISOR
            if ($difference != 0 && Auth::user()->role === 'colaborador') {
                if (!$request->filled('supervisor_token')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Divergência de valores detectada. Requer autorização do supervisor.'
                    ], 403);
                }
            }

            DB::transaction(function () use ($date, $arenaId, $calculated, $actual, $difference, $request) {
                Cashier::updateOrCreate(
                    ['date' => $date, 'arena_id' => $arenaId],
                    [
                        'user_id'           => Auth::id(),
                        'calculated_amount' => $calculated,
                        'actual_amount'     => $actual,
                        'difference'        => $difference,
                        'status'            => 'closed',
                        'closing_time'      => now(),
                        'notes'             => $request->input('notes') . ($request->filled('supervisor_token') ? " | [AUTORIZADO POR SUPERVISOR]" : ""),
                    ]
                );
            });

            return response()->json(['success' => true, 'message' => 'Caixa fechado com sucesso!']);
        } catch (\Exception $e) {
            \Log::error("Erro no fechamento: " . $e->getMessage());
            return response()->json([
                'success' => true,
                'message' => null // Removida a frase que gerava o alert
            ]);
        }
    }

    /**
     * 🎯 REABRIR CAIXA: Registra justificativa e invalida o fechamento anterior.
     */
    public function reopenCash(Request $request)
    {
        // 1. Validação dos campos do formulário
        $request->validate([
            'date'     => 'required|date',
            'reason'   => 'required|string|min:5',
            'arena_id' => 'required|exists:arenas,id',
        ]);

        try {
            // 🚀 2. TRAVA DE SEGURANÇA: Se for colaborador, EXIGE o supervisor_token
            if (Auth::user()->role === 'colaborador') {
                if (!$request->filled('supervisor_token')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A reabertura de caixa exige autorização de um supervisor.'
                    ], 403);
                }
            }

            \Log::info("Tentando reabrir caixa: Data {$request->date} | Arena {$request->arena_id}");

            // 3. Busca o registro
            $cashier = Cashier::whereDate('date', $request->date)
                ->where('arena_id', $request->arena_id)
                ->first();

            if (!$cashier) {
                return response()->json([
                    'success' => false,
                    'message' => "Não existe um fechamento registrado para esta arena no dia " . \Carbon\Carbon::parse($request->date)->format('d/m/Y') . "."
                ], 404);
            }

            // 4. Monta a nota de quem autorizou
            $autorizacaoTxt = $request->filled('supervisor_token')
                ? " | [AUTORIZADO POR: {$request->supervisor_token}]"
                : "";

            // 5. Atualiza para reabrir
            $cashier->update([
                'status'            => 'open',
                'reopen_reason'     => $request->reason . $autorizacaoTxt,
                'reopened_at'       => now(),
                'reopened_by'       => Auth::id(),
                'actual_amount'     => 0,
                'difference'        => 0,
                'closing_time'      => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Caixa da unidade reaberto com sucesso!'
            ]);
        } catch (\Exception $e) {
            \Log::error("Erro fatal ao reabrir caixa: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Falha interna ao processar reabertura.'
            ], 500);
        }
    }


    /**
     * Processa o Pagamento (Blindado contra duplicidade e erro de saldo de estornos)
     */
    public function processPayment(Request $request, $reservaId)
    {
        $reserva = Reserva::with('arena')->findOrFail($reservaId);
        $dataOperacional = $request->input('payment_date') ?? now()->toDateString();
        $labelData = \Carbon\Carbon::parse($dataOperacional)->format('d/m/Y');

        // 🛡️ TRAVA DE CAIXA: Impede lançamentos se o caixa da arena já estiver fechado
        if (\App\Http\Controllers\FinanceiroController::isCashClosed($dataOperacional, $reserva->arena_id)) {
            return response()->json([
                'success' => false,
                'message' => "Ação bloqueada: O caixa do dia {$labelData} já está encerrado nesta unidade."
            ], 403);
        }

        $validated = $request->validate([
            'final_price'     => 'required|numeric|min:0',
            'amount_paid'     => 'required|numeric|min:0',
            'payment_method'  => 'required|string|max:50',
            'apply_to_series' => 'nullable|boolean',
            'payment_date'    => 'nullable|date',
        ]);

        try {
            $paymentStatus = 'pending';

            DB::transaction(function () use ($validated, $reserva, &$paymentStatus, $dataOperacional) {
                // 🔒 LOCK PARA EVITAR CONCORRÊNCIA
                $reservaFresh = Reserva::where('id', $reserva->id)->lockForUpdate()->first();

                $finalPrice = round((float) $validated['final_price'], 2);
                $amountReceivedNow = round((float) $validated['amount_paid'], 2);

                // 🛡️ TRAVA 1: ANTI-CLIQUE DUPLO
                $pagamentoDuplicado = $reservaFresh->transactions()
                    ->where('amount', $amountReceivedNow)
                    ->where('created_at', '>=', now()->subSeconds(10))
                    ->exists();

                if ($pagamentoDuplicado) {
                    throw new \Exception('DUPLICATE_PAYMENT');
                }

                // 🛡️ TRAVA 2: CÁLCULO DE SALDO LÍQUIDO REAL
                $diretas = (float) $reservaFresh->transactions()->sum('amount');
                $orfas = (float) \App\Models\FinancialTransaction::whereNull('reserva_id')
                    ->where('arena_id', $reservaFresh->arena_id)
                    ->where('description', 'LIKE', "%#{$reservaFresh->id}%")
                    ->sum('amount');

                $jaPagoReal = round($diretas + $orfas, 2);
                $saldoDevedor = round($finalPrice - $jaPagoReal, 2);

                // ✅ VERIFICAÇÃO DE DUPLICIDADE LÓGICA
                if ($saldoDevedor <= 0 && $amountReceivedNow > 0) {
                    throw new \Exception('ALREADY_PAID');
                }

                if ($amountReceivedNow > ($saldoDevedor + 0.01)) {
                    throw new \Exception("Valor excede o saldo devedor. O valor máximo aceito agora é: R$ " . number_format($saldoDevedor, 2, ',', '.'));
                }

                $newTotalPaid = round($jaPagoReal + $amountReceivedNow, 2);

                // Define status financeiro e visual
                if ($newTotalPaid >= $finalPrice && $finalPrice > 0) {
                    $paymentStatus = 'paid';
                    $newVisualStatus = 'completed';
                } elseif ($newTotalPaid > 0) {
                    $paymentStatus = 'partial';
                    $newVisualStatus = ($reservaFresh->status === 'maintenance') ? 'confirmed' : $reservaFresh->status;
                } else {
                    $paymentStatus = 'unpaid';
                    $newVisualStatus = $reservaFresh->status;
                }

                // Atualiza a reserva sincronizando os campos
                $reservaFresh->update([
                    'total_paid'     => $newTotalPaid,
                    'final_price'    => $finalPrice,
                    'payment_status' => $paymentStatus,
                    'status'         => $newVisualStatus,
                    'manager_id'     => Auth::id(),
                ]);

                // Atualiza série futura para mensalistas
                if (!empty($validated['apply_to_series']) && $reservaFresh->recurrent_series_id) {
                    Reserva::where('recurrent_series_id', $reservaFresh->recurrent_series_id)
                        ->where('date', '>', $reservaFresh->date)
                        ->where('payment_status', 'unpaid')
                        ->update([
                            'price' => $finalPrice,
                            'final_price' => $finalPrice
                        ]);
                }

                // --- REGISTRO DA TRANSAÇÃO FINANCEIRA ---
                if ($amountReceivedNow > 0) {
                    // 🔍 LÓGICA DE INTELIGÊNCIA E PADRONIZAÇÃO (Dicionário de Tradução)
                    $metodoFinal = strtolower(trim($validated['payment_method']));

                    $dePara = [
                        'money'   => 'dinheiro',
                        'cash'    => 'dinheiro',
                        'credit'  => 'cartao',
                        'card'    => 'cartao',
                        'debito'  => 'cartao',
                        'pix '    => 'pix',
                    ];

                    if (array_key_exists($metodoFinal, $dePara)) {
                        $metodoFinal = $dePara[$metodoFinal];
                    }

                    // Se o método vier genérico, recupera o método do sinal original
                    if (in_array($metodoFinal, ['outro', 'outros', 'ajuste', ''])) {
                        $ultimaEntrada = \App\Models\FinancialTransaction::where('reserva_id', $reservaFresh->id)
                            ->where('amount', '>', 0)
                            ->latest()
                            ->first();

                        if ($ultimaEntrada) {
                            $metodoFinal = $ultimaEntrada->payment_method;
                        } else {
                            $metodoFinal = 'dinheiro'; // Default de segurança
                        }
                    }

                    \App\Models\FinancialTransaction::create([
                        'reserva_id'     => $reservaFresh->id,
                        'arena_id'       => $reservaFresh->arena_id,
                        'user_id'        => $reservaFresh->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => $amountReceivedNow,
                        'type'           => ($paymentStatus === 'paid') ? \App\Models\FinancialTransaction::TYPE_PAYMENT : 'partial_payment',
                        'payment_method' => $metodoFinal,
                        'description'    => "Pagamento reserva #{$reservaFresh->id} | Cliente: {$reservaFresh->client_name}",
                        'paid_at'        => $dataOperacional . ' ' . now()->format('H:i:s'),
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Pagamento de R$ " . number_format($validated['amount_paid'], 2, ',', '.') . " processado com sucesso!",
                'status'  => $paymentStatus
            ]);
        } catch (\Exception $e) {
            if ($e->getMessage() === 'DUPLICATE_PAYMENT') {
                return response()->json([
                    'success' => true,
                    'message' => 'Este pagamento já foi processado (clique duplo evitado).',
                    'status'  => $paymentStatus
                ]);
            }

            if ($e->getMessage() === 'ALREADY_PAID') {
                return response()->json([
                    'success' => true,
                    'message' => 'Esta reserva já foi quitada anteriormente.',
                    'status' => 'paid'
                ]);
            }

            \Log::error("Erro no processamento de pagamento ID #{$reservaId}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Registra Falta (No-Show) - Blindado com auditoria e preservação de histórico
     */
    public function registerNoShow(Request $request, $reservaId)
    {
        // 1. Carrega a reserva com as relações necessárias
        $reserva = \App\Models\Reserva::with(['arena', 'user'])->findOrFail($reservaId);

        // --- 🎯 AJUSTE DE DATA OPERACIONAL ---
        $dataOperacional = $request->input('payment_date') ?? now()->toDateString();
        $labelData = \Carbon\Carbon::parse($dataOperacional)->format('d/m/Y');

        // 🎯 VERIFICAÇÃO DE SEGURANÇA
        if (\App\Http\Controllers\FinanceiroController::isCashClosed($dataOperacional, $reserva->arena_id)) {
            return response()->json([
                'success' => false,
                'message' => "Ação bloqueada: O caixa de {$labelData} já está encerrado nesta unidade."
            ], 403);
        }

        try {
            \DB::beginTransaction();

            $totalOriginalPago = round((float) $reserva->total_paid, 2);
            $shouldRefund = $request->boolean('should_refund');
            $valorParaEstornar = round((float) $request->input('refund_amount', 0), 2);

            // 🔍 IDENTIFICAÇÃO DO MÉTODO ORIGINAL (Para evitar divergência no relatório)
            // Buscamos a última transação de entrada desta reserva
            $ultimaTransacao = \App\Models\FinancialTransaction::where('reserva_id', $reserva->id)
                ->where('amount', '>', 0)
                ->latest()
                ->first();

            // Se encontrar, usa o método original (pix, cartao, etc), senão usa 'cash_out' como fallback
            $metodoOriginal = $ultimaTransacao ? $ultimaTransacao->payment_method : 'cash_out';

            $clienteNome = $reserva->client_name ?? $reserva->user?->name ?? 'Cliente Externo';
            $horario = \Carbon\Carbon::parse($reserva->start_time)->format('H:i');
            $infoContexto = " | Jogo original: " . \Carbon\Carbon::parse($reserva->date)->format('d/m') . " às {$horario}";

            if ($totalOriginalPago > 0) {
                if ($shouldRefund && $valorParaEstornar > 0) {
                    // 💰 REGISTRA A SAÍDA USANDO O MÉTODO ORIGINAL
                    \App\Models\FinancialTransaction::create([
                        'reserva_id'     => null,
                        'arena_id'       => $reserva->arena_id,
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => \Auth::id(),
                        'amount'         => -$valorParaEstornar,
                        'type'           => 'refund',
                        'payment_method' => $metodoOriginal, // ✨ AGORA É DINÂMICO!
                        'description'    => "ESTORNO NO-SHOW (#{$reserva->id}): Cliente {$clienteNome}" . $infoContexto,
                        'paid_at'        => $dataOperacional . ' ' . now()->format('H:i:s'),
                    ]);

                    \App\Models\FinancialTransaction::where('reserva_id', $reserva->id)
                        ->update([
                            'reserva_id'  => null,
                            'description' => \DB::raw("CONCAT(description, ' (Falta c/ Estorno #{$reserva->id})')")
                        ]);
                } else {
                    // 🔒 RETENÇÃO: Mantém o método original para o relatório de faturamento bater por categoria
                    \App\Models\FinancialTransaction::where('reserva_id', $reserva->id)
                        ->update([
                            'reserva_id'     => null,
                            'type'           => 'no_show_penalty',
                            // Mantemos o payment_method original para o faturamento por PIX/Cartão continuar correto
                            'description'    => \DB::raw("CONCAT(description, ' [RETIDO COMO MULTA - Reserva #{$reserva->id}]')")
                        ]);

                    \App\Models\FinancialTransaction::create([
                        'reserva_id'     => null,
                        'arena_id'       => $reserva->arena_id,
                        'manager_id'     => \Auth::id(),
                        'amount'         => 0,
                        'type'           => 'no_show_penalty',
                        'payment_method' => $metodoOriginal,
                        'description'    => "LOG: Falta marcada (#{$reserva->id}). R$ {$totalOriginalPago} retido como multa.",
                        'paid_at'        => $dataOperacional . ' ' . now()->format('H:i:s'),
                    ]);
                }
            }

            // ... (resto do código de penalidade de usuário e recriação de slot permanece igual)
            if ($reserva->user) {
                $user = $reserva->user;
                $user->increment('no_show_count');
                if ($user->no_show_count >= 3) {
                    $user->update(['is_blocked' => true]);
                }
            }

            if (method_exists(app(\App\Http\Controllers\ReservaController::class), 'recreateFixedSlot')) {
                app(\App\Http\Controllers\ReservaController::class)->recreateFixedSlot($reserva);
            }

            $reserva->update([
                'status' => 'no_show',
                'managed_by' => \Auth::id(),
                'cancellation_reason' => '[Falta Registrada] ' . ($request->input('no_show_reason') ?? 'Cliente não compareceu'),
                'is_fixed' => false,
                'total_paid' => 0,
                'final_price' => 0,
            ]);

            \DB::commit();
            return response()->json(['success' => true, 'message' => "Falta registrada e financeiro ajustado no método original."]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erro ao processar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 🎯 LANÇAR COMO PENDÊNCIA (Pagar Depois):
     * Blindado com Lock de banco e verificação de integridade.
     */
    public function markAsPendingDebt(Request $request, $reservaId)
    {
        // LOG DE ENTRADA: Monitoramento de auditoria
        \Log::info("--- INÍCIO PROCESSO DÍVIDA ---", [
            'reserva_id' => $reservaId,
            'motivo' => $request->reason,
            'usuario' => \Auth::user()->name ?? 'Desconhecido'
        ]);

        try {
            // Buscamos a reserva com Lock para garantir que ninguém pague ela enquanto estamos pendenciando
            $reserva = \App\Models\Reserva::where('id', $reservaId)->lockForUpdate()->firstOrFail();

            // 1. Validação do Motivo (Mínimo 5 caracteres para evitar "abcde")
            $validated = $request->validate([
                'reason' => 'required|string|min:5|max:255',
            ]);

            // 🛡️ TRAVA DE CAIXA OPERACIONAL
            // Verificamos se o caixa da data do jogo já foi encerrado
            if (\App\Http\Controllers\FinanceiroController::isCashClosed($reserva->date, $reserva->arena_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ação bloqueada: O caixa do dia ' . \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') . ' já está encerrado.'
                ], 403);
            }

            // 2. Verificação de Saldo (Não pendenciar o que já está pago)
            $totalDevido = round((float)($reserva->final_price ?? $reserva->price), 2);
            $totalPago = round((float)$reserva->total_paid, 2);

            if ($totalPago >= $totalDevido && $totalDevido > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta reserva já consta como totalmente paga.'
                ], 422);
            }

            // 3. Execução da Transação
            \DB::transaction(function () use ($reserva, $validated, $totalPago) {
                // Define se é uma dívida total (unpaid) ou parcial (partial)
                $novoStatusPagamento = ($totalPago > 0) ? 'partial' : 'unpaid';

                // AQUI ESTÁ A MUDANÇA: 'status' agora vira 'debt'
                $reserva->update([
                    'status' => 'debt',
                    'payment_status' => $novoStatusPagamento,
                    'manager_id' => \Auth::id(),
                    'notes' => $reserva->notes . " | [DÍVIDA EM " . now()->format('d/m H:i') . "]: " . $validated['reason'] . " (Autorizado por: " . \Auth::user()->name . ")"
                ]);
            });

            \Log::info("✅ Reserva #{$reservaId} movida para dívidas.");

            return response()->json([
                'success' => true,
                'message' => 'O jogo foi finalizado e o saldo devedor foi enviado para o Gerenciador de Pendências.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'O motivo da pendência é obrigatório (mín. 5 caracteres).'], 422);
        } catch (\Exception $e) {
            \Log::error("❌ Erro ao pendenciar reserva #{$reservaId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 💸 MOVIMENTAÇÃO AVULSA: Sangria (Saída) ou Reforço (Entrada)
     * Refinado para garantir vínculo obrigatório com uma Arena e isolamento de caixa.
     */
    public function storeAvulsa(Request $request)
    {
        // 1. Validação: arena_id é obrigatório
        $validated = $request->validate([
            'date'           => 'required|date',
            'type'           => 'required|in:in,out',
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'description'    => 'required|string|max:255',
            'arena_id'       => 'required|exists:arenas,id',
        ], [
            'arena_id.required' => 'Selecione a Arena para vincular esta movimentação.',
        ]);

        try {
            // 🚀 2. BLINDAGEM ANTI-DUPLICIDADE (Double-Click)
            // Calculamos o valor final antes para checar no banco
            $checkAmount = $validated['type'] === 'out' ? -abs($validated['amount']) : abs($validated['amount']);

            // Verifica se já existe uma transação idêntica nos últimos 10 segundos
            $isDuplicate = FinancialTransaction::where('arena_id', $validated['arena_id'])
                ->where('amount', $checkAmount)
                ->where('description', 'LIKE', '%' . $validated['description'] . '%')
                ->where('created_at', '>=', now()->subSeconds(10))
                ->exists();

            if ($isDuplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ação bloqueada: Esta movimentação já foi registrada (clique duplo detectado).'
                ], 422);
            }

            // 3. TRAVA DE CAIXA FECHADO
            if (\App\Http\Controllers\FinanceiroController::isCashClosed($validated['date'], $validated['arena_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ação bloqueada: O caixa desta arena para o dia ' . \Carbon\Carbon::parse($validated['date'])->format('d/m/Y') . ' já está encerrado.'
                ], 403);
            }

            // 4. Lógica do Valor: Entrada (+) ou Saída (-)
            $finalAmount = $checkAmount;

            // 5. Identificação do tipo para o banco de dados
            $transactionType = $validated['type'] === 'out' ? 'sangria' : 'reforco';
            $prefixLabel = $validated['type'] === 'out' ? '🔴 SANGRIA: ' : '🟢 REFORÇO: ';

            // 6. Criação da transação
            FinancialTransaction::create([
                'arena_id'       => $validated['arena_id'],
                'manager_id'     => Auth::id(),
                'amount'         => $finalAmount,
                'type'           => $transactionType,
                'payment_method' => $validated['payment_method'],
                'description'    => $prefixLabel . $validated['description'],
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
                'message' => 'Erro interno ao processar movimentação.'
            ], 500);
        }
    }
}
