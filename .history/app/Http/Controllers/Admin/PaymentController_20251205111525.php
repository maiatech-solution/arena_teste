<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // 🎯 Importado para capturar o ID do gestor
use Carbon\Carbon;

// Modelos do usuário
use App\Models\Reserva;
use App\Models\User;
use App\Models\FinancialTransaction; // Modelo de transações financeiras

class PaymentController extends Controller
{
    /**
     * Exibe o Dashboard de Caixa e gerencia filtros de data, ID e Pesquisa.
     */
    public function index(Request $request)
    {
        // 1. Definição da Data e ID da Reserva
        $selectedDateString = $request->input('data_reserva')
                             ?? $request->input('date')
                             ?? Carbon::today()->toDateString();

        $dateObject = Carbon::parse($selectedDateString);
        // Captura o ID da reserva que pode ter vindo do dashboard
        $selectedReservaId = $request->input('reserva_id');
        // 🎯 NOVO: Captura o termo de pesquisa
        $searchTerm = $request->input('search');

        // =========================================================================
        // 1. CONSULTA REAL NO BANCO DE DADOS (Reservas para a Tabela)
        // =========================================================================

        $query = Reserva::with('user'); // 🎯 Inicia a query e carrega os dados do cliente (User)

        // --- LÓGICA DE FILTRO DE DATA/ID ---
        if ($selectedReservaId) {
            // ✅ PRIORIDADE: Se um ID de reserva for fornecido (clique no dashboard),
            // filtra APENAS por ele.
            $query->where('id', $selectedReservaId);
        } else {
            // Caso contrário, filtra pela data (visão padrão do caixa diário).
            $query->whereDate('date', $dateObject);

            // 🎯 NOVO: LÓGICA DE FILTRO POR PESQUISA (NOME OU WHATSAPP)
            if ($searchTerm) {
                $searchWildcard = '%' . $searchTerm . '%';
                $query->where(function ($q) use ($searchWildcard) {
                    $q->where('client_name', 'LIKE', $searchWildcard)
                      ->orWhere('client_contact', 'LIKE', $searchWildcard);
                });
            }
        }

        // Filtros comuns (aplicados em ambos os casos para garantir que sejam reservas de cliente válidas)
        $query->whereNotNull('user_id')
              ->where('is_fixed', false) // Exclui slots fixos

              // Inclui reservas confirmadas, pendentes, concluídas e no_show (para visualização no caixa)
              ->whereIn('status', [
                  Reserva::STATUS_CONFIRMADA,
                  Reserva::STATUS_PENDENTE,
                  'completed',
                  'no_show'
              ])
              ->orderBy('start_time', 'asc'); // ⚠️ Adicionado ordenação para garantir ordem cronológica

        $reservas = $query->get();

        // =========================================================================
        // 2. Cálculo dos Totais sobre a coleção de Reservas (CORRIGIDO PARA USAR TRANSAÇÕES)
        // =========================================================================

        // 🛑 CRÍTICO: Lista de todos os tipos de transação que contam como ENTRADA no CAIXA
        $transactionIncomeTypes = [
            'signal',
            'payment',
            'full_payment',
            'partial_payment',
            'payment_settlement',
            'RETEN_CANC_COMP',    // Compensação de retenção (Cancelamento Pontual)
            'RETEN_CANC_P_COMP',  // Compensação de retenção (Cancelamento Pontual Recorrente)
            'RETEN_CANC_S_COMP',  // Compensação de retenção (Cancelamento de Série)
            'RETEN_NOSHOW_COMP'   // Compensação de retenção (No-Show)
        ];

        // Total Recebido Hoje (Caixa): SOMA DAS TRANSAÇÕES (CORREÇÃO CRÍTICA)
        // Este KPI DEVE consultar a tabela de Transações para refletir o fluxo de caixa real (Entradas - Saídas)
        $totalReceived = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->whereIn('type', $transactionIncomeTypes)
            ->sum('amount');

        // 🛑 NOVO: LOG DE DEBUG PARA RASTREAR OS R$ 900,00
        $detailedTransactions = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->whereIn('type', $transactionIncomeTypes)
            ->get(['amount', 'type', 'reserva_id']);

        $debugLog = [];
        $debugLog['total_received_calculated'] = $totalReceived;
        $debugLog['transactions_by_type'] = $detailedTransactions->groupBy('type')->map(fn($group) => $group->sum('amount'));
        $debugLog['transactions_list'] = $detailedTransactions->map(fn($t) => "R$ {$t->amount} (Tipo: {$t->type}, Reserva: {$t->reserva_id})")->toArray();

        Log::info("DEBUG FINANCEIRO: Detalhamento do Total Recebido Hoje.", $debugLog);
        // --------------------------------------------------------

        // Total Esperado: Soma de todos os final_price ou price
        $totalExpected = $reservas->sum(fn($r) => $r->final_price ?? $r->price);

        // Total Pendente (A Receber): Soma do que falta pagar
        // OBS: Certifique-se de ter o accessor getRemainingAmountAttribute() no seu modelo Reserva!
        $totalPending = $reservas->sum('remaining_amount');

        // Faltas (No-Show)
        $noShowCount = $reservas->where('status', 'no_show')->count();

        // 3. Retorno para a View
        return view('admin.payment.index', [
            'selectedDate' => $selectedDateString,
            'reservas' => $reservas,
            'totalReceived' => $totalReceived, // Agora é baseado nas Transações
            'totalPending' => $totalPending,
            'totalExpected' => $totalExpected,
            'noShowCount' => $noShowCount,
            'highlightReservaId' => $selectedReservaId,
        ]);
    }

    /**
     * Processa o Pagamento de uma Reserva
     */
    public function processPayment(Request $request, $reservaId)
    {
        // 1. Validação: Inclui 'payment_method'
        $request->validate([
            'final_price' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
        ]);

        if ($request->amount_paid <= 0) {
             return response()->json([
                 'success' => false,
                 'message' => 'O valor a ser recebido deve ser positivo.',
             ], 422);
        }

        try {
            $reserva = Reserva::findOrFail($reservaId);
            $paymentStatus = 'pending';

            DB::transaction(function () use ($request, $reserva, &$paymentStatus) {

                // Variáveis capturadas do Request
                $finalPrice = (float) $request->final_price;
                $amountPaid = (float) $request->amount_paid;
                $paymentMethod = $request->payment_method;

                // Variável do contexto
                $managerId = Auth::id(); // 🎯 Captura o ID do gestor autenticado

                $previousPaid = (float) $reserva->total_paid;
                $newTotalPaid = $previousPaid + $amountPaid;

                // Define o novo status de pagamento com base no total pago
                if (round($newTotalPaid, 2) >= round($finalPrice, 2)) {
                    $paymentStatus = 'paid';
                } elseif ($newTotalPaid > 0) {
                    $paymentStatus = 'partial';
                } else {
                    $paymentStatus = 'pending';
                }

                // Atualiza a reserva
                $reserva->total_paid = $newTotalPaid;
                $reserva->final_price = $finalPrice;
                $reserva->payment_status = $paymentStatus;

                // Se o pagamento estiver completo, marca a reserva como concluída
                if ($paymentStatus === 'paid') {
                        $reserva->status = 'completed';
                }

                $reserva->save();

                // 🎯 PASSO ESSENCIAL: Cria o registro da transação financeira, incluindo manager_id e payment_method
                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => $managerId, // ✅ ID do gestor
                    'amount' => $amountPaid,
                    'type' => 'payment', // Pode ser ajustado para 'remaining' ou 'full' se necessário.
                    'payment_method' => $paymentMethod, // ✅ Forma de pagamento
                    'description' => 'Pagamento da reserva ' . $reserva->id . ' registrado via caixa.',
                    'paid_at' => Carbon::now(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Pagamento de R$ ' . number_format($request->amount_paid, 2, ',', '.') . ' registrado com sucesso!',
                'status' => $paymentStatus
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao processar pagamento: {$e->getMessage()}", ['reserva_id' => $reservaId]);
            // Em caso de erro, verifica se é um erro de autenticação ou de database
            $errorMessage = $e instanceof \Illuminate\Auth\AuthenticationException ?
                            'Usuário não autenticado para registrar o pagamento.' :
                            'Erro interno ao processar o pagamento. Contate o suporte.';

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
            ], 500);
        }
    }

    /**
     * Registra Falta (No-Show)
     */
    public function registerNoShow(Request $request, $reservaId)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'block_user' => 'nullable|boolean',
            // O ideal seria validar should_refund e paid_amount_ref aqui, se este controller for o único a lidar com NoShow.
        ]);

        try {
            // 1. Encontrar a Reserva REAL, carregando o User para lógica de bloqueio
            $reserva = Reserva::with('user')->findOrFail($reservaId);

            DB::transaction(function () use ($request, $reserva) {

                // 3. Atualizar a Reserva
                $reserva->status = 'no_show';
                $reserva->notes = $request->notes;

                // Mantém o pagamento retido, se houver sinal
                if ($reserva->signal_value > 0) {
                    // Nota: A lógica de compensação de retenção/estorno DEVE estar no AdminController::registerNoShow
                    // para garantir que a transação RETEN_NOSHOW_COMP seja criada no ledger.
                    $reserva->payment_status = 'retained';
                } else {
                    $reserva->payment_status = 'unpaid';
                }
                $reserva->save();

                // 4. Lógica de Bloqueio de Usuário (se aplicável)
                if ($request->boolean('block_user') && $reserva->user_id && $reserva->user) {
                    $user = $reserva->user;
                    $user->no_show_count = ($user->no_show_count ?? 0) + 1; // Incrementa no_show_count

                    // Se o cliente atingir 3 ou mais faltas, bloqueia
                    if ($user->no_show_count >= 3) {
                        $user->is_blocked = true;
                    }
                    $user->save();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Falta (No-Show) registrada com sucesso.',
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao registrar falta: {$e->getMessage()}", ['reserva_id' => $reservaId]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao registrar a falta. Contate o suporte.',
            ], 500);
        }
    }
}
