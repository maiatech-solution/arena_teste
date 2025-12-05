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
use App\Models\FinancialTransaction; // Modelo de transações financeiras

class PaymentController extends Controller
{
    /**
     * Verifica e corrige reservas de No-Show onde o valor pago deveria ter sido zerado após o estorno,
     * mas não foi devido à falha de lógica anterior.
     * Esta função garante a integridade dos KPIs (necessário para corrigir dados antigos).
     */
    private function checkAndCorrectNoShowPaidAmounts()
    {
        // Busca reservas antigas que são 'no_show', foram estornadas ('unpaid' neste contexto)
        // e, erroneamente, ainda têm total_paid > 0.
        $reservasToCorrect = Reserva::where('status', 'no_show')
            ->where('payment_status', 'unpaid')
            ->where('total_paid', '>', 0)
            // Também corrige o final_price se o status é unpaid, mas o price não foi zerado antes
            ->where('final_price', '>', 0)
            ->get();

        if ($reservasToCorrect->isNotEmpty()) {
            DB::transaction(function () use ($reservasToCorrect) {
                foreach ($reservasToCorrect as $reserva) {
                    $oldPaid = $reserva->total_paid;
                    $oldPrice = $reserva->final_price;
                    
                    // Zera o campo total_paid E final_price para refletir o estorno total
                    $reserva->total_paid = 0.00; 
                    $reserva->final_price = 0.00; // Zera a expectativa de receita
                    $reserva->save();
                    
                    Log::warning("CORREÇÃO AUTOMÁTICA DE DADOS: Reserva ID {$reserva->id} (No-Show/Estorno) teve total_paid corrigido de R$ {$oldPaid} para R$ 0.00 e final_price de R$ {$oldPrice} para R$ 0.00 para sincronizar KPIs.");
                }
            });
        }
    }

    /**
     * Exibe o Dashboard de Caixa e gerencia filtros de data, ID e Pesquisa.
     */
    public function index(Request $request)
    {
        // 🛡️ PASSO DE INTEGRIDADE: Executa a correção automática de dados inconsistentes
        $this->checkAndCorrectNoShowPaidAmounts();
        
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
        // 1. CONSULTA REAL NO BANCO DE DADOS (Reservas para a Tabela de Pagamentos)
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

              // Inclui apenas status ativos/relevantes (confirmadas, pendentes, concluídas e no_show).
              ->whereIn('status', [
                  Reserva::STATUS_CONFIRMADA,
                  Reserva::STATUS_PENDENTE,
                  'completed',
                  'no_show'
              ])
              ->orderBy('start_time', 'asc'); // ⚠️ Adicionado ordenação para garantir ordem cronológica

        $reservas = $query->get();

        // =========================================================================
        // 2. Cálculo dos Totais e Busca das Transações Financeiras (PARA A TABELA/KPIs)
        // =========================================================================
        
        // --- CÁLCULOS GERAIS/AGREGADOS ---
        
        // 1. TOTAL EM CAIXA (Total de todo o caixa - Soma de TODOS os 'amount' na tabela de transações)
        $totalGeralCaixa = FinancialTransaction::sum('amount');
        
        // 2. TOTAL RECEBIDO DO DIA (Saldo Líquido - Entradas - Saídas DO CAIXA hoje)
        $totalRecebidoDia = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->sum('amount');
            
        // 3. KPI CORRIGIDO: TOTAL JÁ PAGO pelas reservas que estão agendadas para o dia selecionado.
        $totalAntecipadoReservasDia = $reservas->sum('total_paid'); 
            
        // 4. TOTAL DE RESERVAS CONFIRMADAS
        $totalReservasDia = $reservas->whereIn('status', [
            Reserva::STATUS_CONFIRMADA, 
            'completed',
            'no_show'
        ])->count();

        // Total Expected (Receita Bruta): Soma de todos os final_price ou price das reservas
        $totalExpected = $reservas->sum(fn($r) => $r->final_price ?? $r->price);

        // Total Pendente (A Receber - Líquido): Soma do que falta pagar (remaining_amount)
        $totalPendingLiquido = $reservas->sum('remaining_amount'); // R$ 250,00

        // Faltas (No-Show)
        $noShowCount = $reservas->where('status', 'no_show')->count();

        // Busca todas as transações do dia para a Tabela de Movimentação Detalhada
        $financialTransactions = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->with(['reserva', 'manager', 'payer'])
            ->orderBy('paid_at', 'desc')
            ->get();
        
        // 3. Retorno para a View
        return view('admin.payment.index', [
            'selectedDate' => $selectedDateString,
            'reservas' => $reservas,
            
            // --- VARIÁVEIS PARA OS KPIS DE SUMÁRIO ---
            'totalGeralCaixa' => $totalGeralCaixa,
            'totalRecebidoDia' => $totalRecebidoDia, 
            'totalAntecipadoReservasDia' => $totalAntecipadoReservasDia, 
            'totalReservasDia' => $totalReservasDia,
            
            // --- VARIÁVEIS PARA DESTAQUE ---
            'totalReceived' => $totalRecebidoDia, // Mantido por compatibilidade
            
            // 🎯 CORREÇÃO CRÍTICA: PASSANDO A RECEITA BRUTA ($totalExpected) PARA O DESTAQUE PRINCIPAL DA VIEW ($totalPending)
            'totalPending' => $totalExpected, // AGORA É R$ 500,00
            
            // NOVO CAMPO: O SALDO LÍQUIDO PENDENTE (R$ 250,00) É PASSADO EM UMA VARIÁVEL NOVA E CLARA
            'saldoPendenteLiquido' => $totalPendingLiquido, 

            'totalExpected' => $totalExpected, // Mantido para o texto menor do card
            'noShowCount' => $noShowCount,
            'highlightReservaId' => $selectedReservaId,
            'financialTransactions' => $financialTransactions, 
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
        // 1. Validação: Adicionando os novos campos do modal
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'block_user' => 'nullable|boolean',
            'paid_amount' => 'required|numeric|min:0', // Valor que já foi pago
            'should_refund' => 'required|boolean',      // Se deve ser estornado
        ]);

        try {
            // 1. Encontrar a Reserva REAL
            $reserva = Reserva::with('user')->findOrFail($reservaId);
            $managerId = Auth::id(); // Captura o ID do gestor autenticado

            DB::transaction(function () use ($request, $reserva, $managerId) {

                $paidAmount = (float) $request->paid_amount;
                $shouldRefund = $request->boolean('should_refund');

                // 2. Atualizar a Reserva
                $reserva->status = 'no_show';
                $reserva->notes = $request->notes;

                // Lógica para zerar a expectativa de receita e o total pago, se necessário.
                if ($paidAmount > 0) {
                    if ($shouldRefund) {
                        // O valor pago será devolvido. A expectativa de receita é ZERADA.
                        $reserva->payment_status = 'unpaid';
                        
                        // 🎯 CORREÇÃO CRÍTICA: ZERAR o total_paid e o final_price
                        $reserva->total_paid = 0.00; 
                        $reserva->final_price = 0.00; // Zera a expectativa de recebimento e zera o Saldo a Pagar na View.
                        
                    } else {
                        // O valor pago será retido (mantém o sinal/parcial)
                        $reserva->payment_status = 'retained';
                        
                        // Ajustamos o final_price para o valor retido. 
                        // Ex: Se pagou R$ 100 e retivemos R$ 100, final_price = 100. Total Pago = 100. Saldo a Pagar = 0.
                        $reserva->final_price = $paidAmount; 
                        // Mantemos o total_paid no valor pago para refletir a retenção.
                    }
                } else {
                    // Se nada foi pago, o status é unpaid, e o total_paid é 0.
                    $reserva->payment_status = 'unpaid';
                    $reserva->total_paid = 0.00;
                    // Mantém o final_price original, de modo que o Saldo a Pagar seja o valor total.
                }
                $reserva->save();

                // 🎯 PASSO CRÍTICO: Registrar a SAÍDA DE CAIXA (Estorno)
                if ($paidAmount > 0 && $shouldRefund) {
                    // Se houver valor pago E o operador escolheu estornar:
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => $managerId,
                        'amount' => -$paidAmount, // ✅ O VALOR NEGATIVO REGISTRA UMA SAÍDA DE CAIXA
                        'type' => 'refund',
                        'payment_method' => 'cash_out', 
                        'description' => 'ESTORNO: Devolução de R$ ' . number_format($paidAmount, 2, ',', '.') . ' devido à falta (No-Show) da Reserva ID ' . $reserva->id . '.',
                        'paid_at' => Carbon::now(),
                    ]);
                } 


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
                'message' => 'Falta (No-Show) registrada com sucesso. O estorno/retenção foi processado.',
            ]);

        } catch (\Exception $e) {
            Log::error("Erro ao registrar falta: {$e->getMessage()}", ['reserva_id' => $reservaId]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao registrar a falta: ' . $e->getMessage(),
            ], 500);
        }
    }
}