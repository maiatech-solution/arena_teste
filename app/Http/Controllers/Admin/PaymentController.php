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

        // 1. Integridade: Corrige inconsistências antes de carregar a página

        $this->checkAndCorrectNoShowPaidAmounts();



        // 2. Definição de Data e Filtros

        $selectedDateString = $request->input('data_reserva') ?? $request->input('date') ?? Carbon::today()->toDateString();

        $dateObject = Carbon::parse($selectedDateString);

        $selectedReservaId = $request->input('reserva_id');

        $selectedArenaId = $request->input('arena_id'); // 🎯 NOVO: Captura o filtro de arena

        $searchTerm = $request->input('search');



        // 3. Consulta de Reservas do Dia

        $query = Reserva::with(['user', 'arena']);



        if ($selectedReservaId) {

            $query->where('id', $selectedReservaId);
        } else {

            $query->whereDate('date', $dateObject);



            // 🎯 NOVO: Aplica o filtro de Arena na listagem de reservas

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



        // 4. Movimentação Financeira do Dia (Líquido Geral)

        $totalRecebidoDiaLiquido = FinancialTransaction::whereDate('paid_at', $dateObject)->sum('amount');



        // 🎯 AJUSTE: Faturamento por Arena (Garantindo que todas as arenas apareçam)

        $arenasAtivas = \App\Models\Arena::all(); // Pega todas as quadras cadastradas



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



        // 5. Histórico de Transações Detalhado

        // 🎯 AJUSTE: Se filtrar por arena, as transações detalhadas também filtram

        $transQuery = FinancialTransaction::whereDate('paid_at', $dateObject);

        if ($selectedArenaId) {

            $transQuery->where('arena_id', $selectedArenaId);
        }

        $financialTransactions = $transQuery->with(['reserva', 'manager', 'payer', 'arena'])

            ->orderBy('paid_at', 'desc')

            ->get();



        // 6. Auditoria de Fechamento (Cashier)

        $cashierHistory = Cashier::with('user')

            ->orderBy('date', 'desc')

            ->limit(10)

            ->get();



        $cashierRecord = Cashier::where('date', $selectedDateString)->first();

        $cashierStatus = $cashierRecord->status ?? 'open';



        // 7. KPIs de Dashboard

        $totalExpected = $reservas->whereNotIn('status', ['canceled', 'rejected'])

            ->sum(fn($r) => $r->final_price ?? $r->price);



        $totalPending = $reservas->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])

            ->sum(fn($r) => max(0, ($r->final_price ?? $r->price) - $r->total_paid));



        // 8. Retorno para a View

        return view('admin.payment.index', [

            'selectedDate' => $selectedDateString,

            'reservas' => $reservas,

            'faturamentoPorArena' => $faturamentoPorArena,

            'totalGeralCaixa' => FinancialTransaction::sum('amount'),

            'totalRecebidoDiaLiquido' => $totalRecebidoDiaLiquido,

            'totalAntecipadoReservasDia' => $reservas->sum('total_paid'),

            'totalReservasDia' => $reservas->whereIn('status', [Reserva::STATUS_CONFIRMADA, 'completed', 'no_show'])->count(),

            'totalPending' => $totalPending,

            'totalExpected' => $totalExpected,

            'noShowCount' => $reservas->where('status', 'no_show')->count(),

            'financialTransactions' => $financialTransactions,

            'cashierStatus' => $cashierStatus,

            'cashierHistory' => $cashierHistory,

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
        $reserva = Reserva::with('arena')->findOrFail($reservaId);

        // 1. Trava de Segurança usando a lógica centralizada
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
                $reserva->manager_id = Auth::id();
                $reserva->save();

                // 2. Registro Financeiro com Horário e Arena
                if ($amountPaid > 0) {
                    // Formata a info para a descrição
                    $infoReserva = " | {$reserva->arena->name} [{$reserva->start_time}]";

                    FinancialTransaction::create([
                        'reserva_id'     => $reserva->id,
                        'arena_id'       => $reserva->arena_id,
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => $amountPaid,
                        'type'           => $paymentStatus === 'paid' ? 'full_payment' : 'partial_payment',
                        'payment_method' => $request->payment_method,
                        // 🎯 DESCRIÇÃO ATUALIZADA COM HORÁRIO E ARENA
                        'description'    => 'Pagamento reserva #' . $reserva->id . $infoReserva,
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
        // 1. Carrega a reserva com a arena para pegar o nome da quadra antes de deletar
        $reserva = Reserva::with('arena')->findOrFail($reservaId);

        // 2. Trava de Segurança: Caixa Fechado
        $financeiro = app(FinanceiroController::class);
        if ($financeiro->isCashClosed($reserva->date)) {
            return response()->json(['success' => false, 'message' => 'Erro: O caixa deste dia está fechado.'], 403);
        }

        try {
            DB::beginTransaction();

            $totalOriginalPago = (float) $reserva->total_paid;
            $shouldRefund = $request->boolean('should_refund');
            $valorParaEstornar = (float) $request->input('refund_amount', 0);
            $motivoFalta = $request->input('no_show_reason', 'Falta (No-show) não justificada');

            // 🎯 PREPARA A STRING DE INFORMAÇÃO PARA O CAIXA
            $infoReserva = " | {$reserva->arena->name} [{$reserva->start_time}]";

            if ($totalOriginalPago > 0) {
                if ($shouldRefund && $valorParaEstornar > 0) {
                    // 💰 REGISTRA A SAÍDA (ESTORNO)
                    FinancialTransaction::create([
                        'reserva_id'     => $reserva->id,
                        'arena_id'       => $reserva->arena_id,
                        'user_id'        => $reserva->user_id,
                        'manager_id'     => Auth::id(),
                        'amount'         => -$valorParaEstornar,
                        'type'           => 'refund',
                        'payment_method' => 'cash_out',
                        'description'    => "Estorno Falta #{$reserva->id} | Motivo: {$motivoFalta} | Cliente: {$reserva->client_name}" . $infoReserva,
                        'paid_at'        => now(),
                    ]);

                    // Ajusta a descrição das transações anteriores (Sinal/Pagamentos) para incluir o horário
                    FinancialTransaction::where('reserva_id', $reserva->id)
                        ->update([
                            'description' => DB::raw("CONCAT(description, '{$infoReserva}')")
                        ]);
                } else {
                    // 🔒 RETENÇÃO INTEGRAL (Lucro para a Arena)
                    FinancialTransaction::where('reserva_id', $reserva->id)
                        ->update([
                            'type' => FinancialTransaction::TYPE_RETEN_NOSHOW_COMP,
                            'description' => "Retenção Integral por Falta #{$reserva->id} | Cliente: {$reserva->client_name}" . $infoReserva,
                            'payment_method' => 'retained_funds'
                        ]);
                }
            }

            // 3. Lógica de Bloqueio de Usuário
            if ($reserva->user) {
                $user = $reserva->user;
                $user->increment('no_show_count');
                if ($user->no_show_count >= 3) {
                    $user->update(['is_blocked' => true]);
                }
            }

            // 4. LIBERAÇÃO DO SLOT
            $reservaController = app(\App\Http\Controllers\ReservaController::class);
            $reservaController->recreateFixedSlot($reserva);

            // 5. DELEÇÃO
            $reserva->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Falta registrada e financeiro ajustado!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro no No-Show ID {$reservaId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao processar falta.'], 500);
        }
    }
}
