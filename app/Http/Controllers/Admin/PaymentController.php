<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reserva;
use App\Models\FinancialTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Exibe o Dashboard Financeiro Diário (Lista de Pagamentos).
     */
    public function index(Request $request)
    {
        // 1. Filtro de Data (Padrão: Hoje)
        $dateInput = $request->input('date', Carbon::today()->toDateString());
        $selectedDate = Carbon::parse($dateInput);

        // 2. Busca as Reservas do Dia (para listar na tabela)
        // Incluímos as rejeitadas/canceladas apenas se tiverem gerado transação financeira (ex: estorno pendente)
        // Mas focamos nas ATIVAS (Confirmada/Pendente) e nas FINALIZADAS (que pagaram hoje)
        $reservas = Reserva::with(['user', 'transactions']) // Carrega relacionamentos
            ->whereDate('date', $selectedDate)
            ->where('is_fixed', false) // Apenas clientes reais
            ->orderBy('start_time')
            ->get();

        // 3. KPIs Financeiros (Totais do Topo)
        
        // A. Total Recebido HOJE (Caixa Real)
        // Soma todas as transações feitas na data selecionada (independente de quando foi a reserva)
        $totalReceivedToday = FinancialTransaction::whereDate('paid_at', $selectedDate)
            ->sum('amount');

        // B. Total Previsto (Soma dos preços das reservas confirmadas/pendentes do dia)
        $totalExpected = $reservas->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            ->sum(fn($r) => $r->final_price ?? $r->price);

        // C. Pendente (O que falta receber das reservas de hoje)
        $totalPending = $reservas->sum('remaining_amount');

        // D. Contagem de Faltas (No-Show) no dia
        $noShowCount = $reservas->where('status', 'no_show')->count();

        return view('admin.payment.index', [
            'selectedDate' => $dateInput,
            'reservas' => $reservas,
            'totalReceived' => $totalReceivedToday,
            'totalExpected' => $totalExpected,
            'totalPending' => $totalPending,
            'noShowCount' => $noShowCount,
        ]);
    }

    /**
     * Processa a Baixa de Pagamento (Finalizar Agendamento).
     */
    public function store(Request $request, Reserva $reserva)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string', // money, pix, card
            'amount_paid' => 'required|numeric|min:0', // Valor pago AGORA
            'final_price' => 'nullable|numeric|min:0', // Permite editar o total (desconto)
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $managerId = Auth::id();
            $amountNow = $validated['amount_paid'];
            
            // 1. Atualiza o Preço Final (se o gestor alterou/deu desconto)
            if (isset($validated['final_price'])) {
                $reserva->final_price = $validated['final_price'];
            }
            // Se não tinha final_price, define como o price original para garantir consistência
            if (is_null($reserva->final_price)) {
                $reserva->final_price = $reserva->price;
            }

            // 2. Registra a Transação Financeira
            if ($amountNow > 0) {
                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => $managerId,
                    'amount' => $amountNow,
                    'type' => 'remaining', // Ou 'full', dependendo da lógica
                    'payment_method' => $validated['payment_method'],
                    'description' => $validated['description'] ?? 'Baixa no caixa',
                    'paid_at' => Carbon::now(),
                ]);
            }

            // 3. Atualiza os totais da Reserva
            $reserva->total_paid += $amountNow;
            $reserva->manager_id = $managerId; // Atualiza quem atendeu por último

            // 4. Define Status
            // Se pagou tudo (ou mais), status = paid. Se não, partial.
            if ($reserva->total_paid >= $reserva->final_price) {
                $reserva->payment_status = 'paid';
                $reserva->status = Reserva::STATUS_CONFIRMADA; // Garante que está confirmada
            } else {
                $reserva->payment_status = 'partial';
            }

            $reserva->save();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pagamento registrado com sucesso!']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro no pagamento: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao processar pagamento.'], 500);
        }
    }

    /**
     * Registra Falta (No-Show) e pune o cliente.
     */
    public function markNoShow(Request $request, Reserva $reserva)
    {
        $validated = $request->validate([
            'block_user' => 'nullable|boolean', // Checkbox "Bloquear cliente?"
            'notes' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // 1. Atualiza Reserva
            $reserva->status = 'no_show'; // Status que criamos mentalmente, pode adicionar na constante se quiser
            $reserva->payment_status = 'retained'; // Sinal retido
            $reserva->cancellation_reason = "FALTA (No-Show). " . ($validated['notes'] ?? '');
            $reserva->manager_id = Auth::id();
            $reserva->save();

            // 2. Atualiza Reputação do Cliente
            if ($reserva->user_id) {
                $user = User::find($reserva->user_id);
                if ($user) {
                    $user->increment('no_show_count');
                    
                    // Bloqueio Opcional (checkbox) ou Automático (>3 faltas)
                    if (!empty($validated['block_user']) || $user->no_show_count >= 3) {
                        $user->is_blocked = true;
                        Log::warning("Usuário {$user->name} bloqueado por faltas.");
                    }
                    $user->save();
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Falta registrada e reputação do cliente atualizada.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erro ao registrar falta.'], 500);
        }
    }
}
