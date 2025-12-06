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
use App\Models\FinancialTransaction; 

// 🎯 CRÍTICO: Importar o FinanceiroController para acessar o helper isCashClosed
use App\Http\Controllers\FinanceiroController; 

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
     * 🎯 ATUALIZADO: Cálculo preciso de Total Previsto e Saldo Pendente (KPIs).
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
        $selectedReservaId = $request->input('reserva_id');
        $searchTerm = $request->input('search');

        // =========================================================================
        // 1. CONSULTA REAL NO BANCO DE DADOS (Reservas para a Tabela de Pagamentos)
        // =========================================================================

        $query = Reserva::with('user');

        // --- LÓGICA DE FILTRO DE DATA/ID ---
        if ($selectedReservaId) {
            $query->where('id', $selectedReservaId);
        } else {
            $query->whereDate('date', $dateObject);

            // 🎯 LÓGICA DE FILTRO POR PESQUISA (NOME OU WHATSAPP)
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
              ->whereIn('status', [
                  Reserva::STATUS_CONFIRMADA,
                  Reserva::STATUS_PENDENTE,
                  'completed',
                  'no_show',
                  'canceled' // Inclui canceladas para ver na tabela (se necessário)
              ])
              ->orderBy('start_time', 'asc');

        $reservas = $query->get();

        // =========================================================================
        // 2. Cálculo dos Totais e Busca das Transações Financeiras (PARA A TABELA/KPIs)
        // =========================================================================
        
        // --- CÁLCULOS GERAIS/AGREGADOS ---
        
        $totalGeralCaixa = FinancialTransaction::sum('amount');
        
        // 2. TOTAL RECEBIDO DO DIA (LÍQUIDO): Saldo total (Entradas - Saídas/Estornos)
        $totalRecebidoDiaLiquido = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->sum('amount');
            
        // 🎯 KPI: Sinais Brutos Recebidos no Dia (só entradas de sinal)
        $totalSinaisBrutosDia = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->where('type', 'signal') // Filtra apenas transações de sinal
            ->sum('amount');
            
        // 3. KPI: TOTAL JÁ PAGO pelas reservas que estão agendadas para o dia selecionado.
        $totalAntecipadoReservasDia = $reservas->sum('total_paid'); 
        
        // 4. TOTAL DE RESERVAS CONFIRMADAS
        $totalReservasDia = $reservas->whereIn('status', [
            Reserva::STATUS_CONFIRMADA, 
            'completed',
            'no_show'
        ])->count();

        // --- 🎯 CORREÇÃO CRÍTICA PARA KPIS: SALDO PENDENTE / TOTAL PREVISTO ---
        // Filtrar as reservas que REALMENTE importam para a projeção de receita AINDA NÃO PAGA.
        
        $reservasKPI = Reserva::query()
            ->whereDate('date', $dateObject)
            ->whereNotIn('status', ['no_show', 'canceled', 'rejected']) // Filtra para o Saldo Pendente
            ->where('is_fixed', false)
            ->get();
        
        // 1. Receita Bruta Total (TOTAL PREVISTO) - Deve incluir todas as reservas agendadas, concluídas ou não.
        // Usamos a coleção original $reservas para refletir o valor total negociado (R$ 350,00).
        $totalExpected = $reservas->sum(fn($r) => $r->final_price ?? $r->price); // ✅ CORREÇÃO APLICADA AQUI

        // 2. Saldo Pendente (SALDO PENDENTE A RECEBER) - Usa a coleção FILTRADA $reservasKPI
        // Deve ser R$ 0,00 se tudo foi resolvido.
        $totalPendingLiquido = $reservasKPI->sum(function ($r) {
            $total = $r->final_price ?? $r->price;
            $pago = $r->total_paid ?? 0;
            return max(0, $total - $pago);
        });

        // Faltas (No-Show) - Usa a coleção original 'reservas' para contagem correta
        $noShowCount = $reservas->where('status', 'no_show')->count();

        // Busca todas as transações do dia para a Tabela de Movimentação Detalhada
        $financialTransactions = FinancialTransaction::whereDate('paid_at', $dateObject)
            ->with(['reserva', 'manager', 'payer'])
            ->orderBy('paid_at', 'desc')
            ->get();
        
        // 🎯 CRÍTICO: Buscar o status do caixa para o dia
        $financeiroController = app(FinanceiroController::class);
        $cashierRecord = \App\Models\Cashier::where('date', $selectedDateString)->first();
        $cashierStatus = $cashierRecord->status ?? 'open';
        
        // 3. Retorno para a View
        return view('admin.payment.index', [
            'selectedDate' => $selectedDateString,
            'reservas' => $reservas,
            
            // --- VARIÁVEIS PARA OS KPIS DE SUMÁRIO ---
            'totalGeralCaixa' => $totalGeralCaixa,
            'totalRecebidoDiaLiquido' => $totalRecebidoDiaLiquido, 
            'totalRecebidoDia' => $totalSinaisBrutosDia, 
            'totalAntecipadoReservasDia' => $totalAntecipadoReservasDia,
            'totalReservasDia' => $totalReservasDia,
            
            // --- VARIÁVEIS PARA DESTAQUE (AGORA CORRETAS) ---
            'totalPending' => $totalPendingLiquido, // ✅ SALDO PENDENTE A RECEBER (R$ 0,00 no seu cenário)
            'saldoPendenteLiquido' => $totalPendingLiquido, 
            'totalExpected' => $totalExpected, // ✅ RECEITA BRUTA (R$ 350,00 no seu cenário)
            'noShowCount' => $noShowCount,
            'highlightReservaId' => $selectedReservaId,
            'financialTransactions' => $financialTransactions, 
            'cashierStatus' => $cashierStatus, // 🎯 Status do caixa
        ]);
    }

    /**
     * Processa o Pagamento de uma Reserva
     */
    public function processPayment(Request $request, $reservaId)
    {
        // 0. Encontrar e validar a reserva primeiro
        try {
            $reserva = Reserva::findOrFail($reservaId);
        } catch (\Exception $e) {
             return response()->json(['success' => false, 'message' => 'Reserva não encontrada.'], 404);
        }
        
        // 🎯 1. VALIDAÇÃO DE SEGURANÇA: CAIXA FECHADO
        $financeiroController = app(FinanceiroController::class);
        $reservaDate = \Carbon\Carbon::parse($reserva->date)->toDateString();
        
        if ($financeiroController->isCashClosed($reservaDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: Não é possível finalizar o pagamento. O caixa do dia ' . \Carbon\Carbon::parse($reservaDate)->format('d/m/Y') . ' está fechado. Reabra o caixa para continuar.',
            ], 403); 
        }
        
        // 2. Validação de dados (movido para depois da checagem de segurança)
        $request->validate([
            'final_price' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'apply_to_series' => 'nullable|boolean',
        ]);

        if ($request->amount_paid <= 0) {
             return response()->json([
                 'success' => false,
                 'message' => 'O valor a ser recebido deve ser positivo.',
               ], 422);
        }

        try {
            $paymentStatus = 'pending';

            DB::transaction(function () use ($request, $reserva, &$paymentStatus) {

                $finalPrice = (float) $request->final_price;
                $amountPaid = (float) $request->amount_paid;
                $paymentMethod = $request->payment_method;
                $applyToSeries = $request->boolean('apply_to_series');

                $managerId = Auth::id(); 
                $previousPaid = (float) $reserva->total_paid;
                $newTotalPaid = $previousPaid + $amountPaid;

                if (round($newTotalPaid, 2) >= round($finalPrice, 2)) {
                    $paymentStatus = 'paid';
                } elseif ($newTotalPaid > 0) {
                    $paymentStatus = 'partial';
                } else {
                    $paymentStatus = 'pending';
                }

                $reserva->total_paid = $newTotalPaid;
                $reserva->final_price = $finalPrice;
                $reserva->payment_status = $paymentStatus;

                if ($paymentStatus === 'paid') {
                    $reserva->status = 'completed';
                }

                $reserva->save();
                
                // Lógica de Recorrência
                if ($reserva->is_recurrent && $applyToSeries && round($finalPrice, 2) !== round($reserva->original_price, 2)) {
                    Reserva::where('series_id', $reserva->series_id)
                           ->where('date', '>', $reserva->date)
                           ->where('status', '!=', 'canceled')
                           ->update([
                               'final_price' => $finalPrice,
                               'payment_status' => 'pending' 
                           ]);
                    Log::info("Preço de R$ {$finalPrice} aplicado a todas as futuras reservas da série {$reserva->series_id}.");
                }

                // Cria o registro da transação financeira
                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => $managerId, 
                    'amount' => $amountPaid,
                    'type' => $paymentStatus === 'paid' ? 'full_payment' : 'partial_payment', 
                    'payment_method' => $paymentMethod, 
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
            $errorMessage = 'Erro interno ao processar o pagamento. Contate o suporte.';

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
        // 0. Encontrar a Reserva REAL
        try {
             $reserva = Reserva::with('user')->findOrFail($reservaId);
        } catch (\Exception $e) {
             return response()->json(['success' => false, 'message' => 'Reserva não encontrada.'], 404);
        }
        
        // 🎯 1. VALIDAÇÃO DE SEGURANÇA: CAIXA FECHADO
        $financeiroController = app(FinanceiroController::class); 
        $reservaDate = \Carbon\Carbon::parse($reserva->date)->toDateString();

        if ($financeiroController->isCashClosed($reservaDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Erro: Não é possível registrar falta. O caixa do dia ' . \Carbon\Carbon::parse($reservaDate)->format('d/m/Y') . ' está fechado.'
            ], 403);
        }
        
        // 2. Validação de dados (movido para depois da checagem de segurança)
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'block_user' => 'nullable|boolean',
            'paid_amount' => 'required|numeric|min:0', 
            'should_refund' => 'required|boolean', 
        ]);

        try {
            $managerId = Auth::id();

            DB::transaction(function () use ($request, $reserva, $managerId) {

                $paidAmount = (float) $request->paid_amount;
                $shouldRefund = $request->boolean('should_refund');

                // 2. Atualizar a Reserva
                $reserva->status = 'no_show';
                $reserva->notes = $request->notes;

                if ($paidAmount > 0) {
                    if ($shouldRefund) {
                        $reserva->payment_status = 'unpaid';
                        $reserva->total_paid = 0.00; 
                        $reserva->final_price = 0.00; 
                    } else {
                        $reserva->payment_status = 'retained';
                        $reserva->final_price = $paidAmount; 
                        // total_paid já está no valor correto (ou foi atualizado pelo processo de pagamento anterior)
                    }
                } else {
                    $reserva->payment_status = 'unpaid';
                    $reserva->total_paid = 0.00;
                    // final_price deve ser mantido para que a linha da tabela reflita a perda total.
                }
                $reserva->save();

                // 🎯 PASSO CRÍTICO: Registrar a SAÍDA DE CAIXA (Estorno)
                if ($paidAmount > 0 && $shouldRefund) {
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => $managerId,
                        'amount' => -$paidAmount, 
                        'type' => 'refund',
                        'payment_method' => 'cash_out', 
                        'description' => 'ESTORNO: Devolução de R$ ' . number_format($paidAmount, 2, ',', '.') . ' devido à falta (No-Show) da Reserva ID ' . $reserva->id . '.',
                        'paid_at' => Carbon::now(),
                    ]);
                } 

                // Lógica de Bloqueio de Usuário (se aplicável)
                if ($request->boolean('block_user') && $reserva->user_id && $reserva->user) {
                    $user = $reserva->user;
                    $user->no_show_count = ($user->no_show_count ?? 0) + 1; 

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