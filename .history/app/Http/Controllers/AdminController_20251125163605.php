<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Models\FinancialTransaction;

class AdminController extends Controller
{
    /**
     * @var ReservaController
     */
    protected $reservaController;

    // Injeção de Dependência para acessar os helpers do ReservaController
    public function __construct(ReservaController $reservaController)
    {
        $this->reservaController = $reservaController;
    }

    // ------------------------------------------------------------------------
    // MÓDULO: DASHBOARDS E LISTAGENS
    // ------------------------------------------------------------------------

    /**
     * Exibe o Dashboard administrativo (FullCalendar).
     */
    public function dashboard()
    {
        // DELEGA para o método do ReservaController
        return $this->reservaController->dashboard();
    }

    /**
     * Exibe o painel de botões de gerenciamento de reservas.
     */
    public function indexReservasDashboard()
    {
        return view('admin.reservas.index-dashboard');
    }

    /**
     * Exibe a lista de Reservas Pendentes.
     */
    public function indexReservas()
    {
        $reservas = Reserva::where('status', Reserva::STATUS_PENDENTE)
            ->where('is_fixed', false)
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(20);

        return view('admin.reservas.index', [
            'reservas' => $reservas,
            'pageTitle' => 'Pré-Reservas Pendentes',
        ]);
    }

    /**
     * Exibe a lista de Reservas Confirmadas.
     */
    public function confirmed_index(Request $request)
    {
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $isOnlyMine = $request->input('only_mine') === 'true';

        $reservas = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->where('is_fixed', false)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('client_name', 'like', '%' . $search . '%')
                        ->orWhere('client_contact', 'like', '%' . $search . '%');
                });
            })
            ->when($startDate, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            ->when($isOnlyMine, function ($query) {
                return $query->where('manager_id', Auth::id());
            })
            ->paginate(20)
            ->appends($request->except('page'));

        return view('admin.reservas.confirmed_index', [
            'reservas' => $reservas,
            'pageTitle' => 'Reservas Confirmadas',
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isOnlyMine' => $isOnlyMine,
        ]);
    }

    /**
     * Exibe o formulário para criação manual de reserva.
     */
    public function createReserva()
    {
        $users = User::where('role', 'cliente')->get();
        return view('admin.reservas.create', compact('users'));
    }

    /**
     * Exibe os detalhes de uma reserva.
     */
    public function showReserva(Reserva $reserva)
    {
        return view('admin.reservas.show', compact('reserva'));
    }

    /**
     * Cria uma nova reserva manual (Admin) - Consome o slot FREE se existir.
     */
    public function storeReserva(Request $request)
    {
        // Validação básica
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'signal_value' => 'nullable|numeric|min:0', // NOVO: Captura o valor do sinal
        ]);

        $startTimeNormalized = Carbon::createFromFormat('H:i', $validated['start_time'])->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('H:i', $validated['end_time'])->format('H:i:s');
        $price = (float) $validated['price'];
        $sinal = (float)($validated['signal_value'] ?? 0.00);
        $totalPaid = $sinal; // Na criação manual, o total pago inicial é o sinal

        // Checa se o horário está ocupado por outra reserva real (usando helper do ReservaController)
        if ($this->reservaController->checkOverlap($validated['date'], $validated['start_time'], $validated['end_time'], false)) {
            return redirect()->back()->withInput()->with('error', 'O horário selecionado já está ocupado por outra reserva confirmada ou pendente.');
        }

        // Tenta encontrar um slot fixo livre (STATUS_FREE) para consumo
        $fixedSlot = Reserva::where('is_fixed', true)
            ->where('date', $validated['date'])
            ->where('start_time', $startTimeNormalized)
            ->where('end_time', $endTimeNormalized)
            ->where('status', Reserva::STATUS_FREE)
            ->first();

        DB::beginTransaction();
        try {
            // LOGGING DETALHADO PARA DEBUGAR O SINAL
            Log::debug("STORE_MANUAL: Tentando criar reserva. Preço: {$price}, Sinal capturado: {$sinal}, Total Pago Inicial: {$totalPaid}");

            if ($fixedSlot) {
                // Consome o slot fixo disponível
                $fixedSlot->delete();
            } else {
                Log::warning("Reserva manual criada sem consumir slot fixo disponível: {$validated['date']} {$startTimeNormalized}.");
            }

            // Determinar status de pagamento
            if ($sinal > 0) {
                $paymentStatus = ($sinal >= $price) ? 'paid' : 'partial';
            } else {
                $paymentStatus = 'pending';
            }

            // Cria a nova reserva confirmada
            $reserva = Reserva::create([ // Captura a reserva criada
                'user_id' => $validated['user_id'] ?? null,
                'date' => $validated['date'],
                'day_of_week' => Carbon::parse($validated['date'])->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $price,
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'],
                'notes' => $validated['notes'] ?? null,
                'status' => Reserva::STATUS_CONFIRMADA,
                'is_fixed' => false,
                'is_recurrent' => false,
                'manager_id' => Auth::id(),
                'signal_value' => $sinal,        // NOVO: Salva o sinal
                'total_paid' => $totalPaid,      // NOVO: Salva o total pago inicial
                'payment_status' => $paymentStatus, // NOVO: Salva o status de pagamento
            ]);

            // Gera a Transação Financeira (Entrada no Caixa) se houver sinal
            if ($sinal > 0) {
                // LOGGING DETALHADO PARA CONFIRMAR TRANSAÇÃO
                Log::info("STORE_MANUAL: Criando Transação Financeira para Reserva ID: {$reserva->id} com valor de sinal: {$sinal}");

                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => Auth::id(),
                    'amount' => $sinal,
                    'type' => 'signal',
                    'payment_method' => 'pix', // Assumindo PIX para sinal manual, pode ser ajustado na view
                    'description' => 'Sinal recebido na criação manual do agendamento',
                    'paid_at' => Carbon::now(),
                ]);
            }

            DB::commit();
            return redirect()->route('admin.reservas.confirmadas')->with('success', 'Reserva criada e confirmada manualmente com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("STORE_MANUAL: Erro ao criar reserva manual.", ['exception' => $e, 'data' => $validated]);
            return redirect()->back()->withInput()->with('error', 'Erro interno ao criar reserva. Tente novamente.');
        }
    }


    // ------------------------------------------------------------------------
    // MÓDULO: AÇÕES DE STATUS E CANCELAMENTO
    // ------------------------------------------------------------------------

    /**
     * Confirma uma reserva pendente e registra o sinal financeiro.
     * @param Request $request
     * @param Reserva $reserva
     */
    public function confirmarReserva(Request $request, Reserva $reserva)
    {
        // 1. Validação de Status
        if ($reserva->status !== Reserva::STATUS_PENDENTE) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'A reserva não está pendente.'], 400);
            }
            return redirect()->back()->with('error', 'A reserva não está mais pendente.');
        }

        // 2. Validação do Input (Sinal)
        $validated = $request->validate([
            'signal_value' => 'nullable|numeric|min:0',
        ]);

        $sinal = (float)($validated['signal_value'] ?? 0.00);
        $managerId = Auth::id();
        $price = (float)$reserva->price;

        DB::beginTransaction();
        try {
            // 3. Atualiza a Reserva
            $reserva->status = Reserva::STATUS_CONFIRMADA;
            $reserva->manager_id = $managerId;
            $reserva->signal_value = $sinal;
            $reserva->total_paid = $sinal;

            // Calcular status de pagamento
            if ($sinal > 0) {
                $reserva->payment_status = ($sinal >= $price) ? 'paid' : 'partial';
            } else {
                $reserva->payment_status = 'pending';
            }

            $reserva->save();

            // 4. CONSUMIR O SLOT FIXO (remover do calendário público)
            if ($reserva->fixed_slot_id) {
                $fixedSlot = Reserva::find($reserva->fixed_slot_id);
                if ($fixedSlot && $fixedSlot->is_fixed && $fixedSlot->status === 'free') {
                    $fixedSlot->delete();
                    Log::info("Slot fixo ID: {$reserva->fixed_slot_id} consumido ao confirmar reserva ID: {$reserva->id}");
                }
            }

            // 5. CANCELAR AUTOMATICAMENTE OUTRAS PRÉ-RESERVAS NO MESMO HORÁRIO
            $conflictingPendingReservas = Reserva::where('id', '!=', $reserva->id)
                ->where('date', $reserva->date)
                ->where('start_time', $reserva->start_time)
                ->where('end_time', $reserva->end_time)
                ->where('status', 'pending')
                ->where('is_fixed', false)
                ->get();

            $canceledCount = 0;
            foreach ($conflictingPendingReservas as $conflictingReserva) {
                $conflictingReserva->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => 'Cancelado automaticamente - Horário confirmado para outro cliente (Reserva ID: ' . $reserva->id . ')',
                    'manager_id' => $managerId,
                ]);
                $canceledCount++;

                Log::info("Reserva ID: {$conflictingReserva->id} cancelada automaticamente devido à confirmação da reserva ID: {$reserva->id}");
            }

            // 6. Gera a Transação Financeira (Entrada no Caixa)
            if ($sinal > 0) {
                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => $managerId,
                    'amount' => $sinal,
                    'type' => 'signal',
                    'payment_method' => 'pix',
                    'description' => 'Sinal recebido na confirmação do agendamento',
                    'paid_at' => Carbon::now(),
                ]);
            }

            DB::commit();

            $message = "Reserva confirmada com sucesso!";
            if ($sinal > 0) {
                $message .= " Sinal de R$ " . number_format($sinal, 2, ',', '.') . " registrado.";
            }
            if ($canceledCount > 0) {
                $message .= " {$canceledCount} outra(s) pré-reserva(s) no mesmo horário foi/foram cancelada(s) automaticamente.";
            }

            Log::info("Reserva ID: {$reserva->id} confirmada por Gestor ID: {$managerId}. Sinal: R$ {$sinal}, Canceladas: {$canceledCount}");

            // Resposta compatível com AJAX e Blade
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message], 200);
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao confirmar reserva ID: {$reserva->id}: " . $e->getMessage());

            $errorMessage = 'Erro interno ao confirmar reserva: ' . $e->getMessage();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 500);
            }
            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Exibe a lista de Reservas Rejeitadas.
     */
    public function indexReservasRejeitadas(Request $request)
    {
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $reservas = Reserva::where('status', Reserva::STATUS_REJEITADA)
            ->where('is_fixed', false)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('client_name', 'like', '%' . $search . '%')
                        ->orWhere('client_contact', 'like', '%' . $search . '%')
                        ->orWhere('cancellation_reason', 'like', '%' . $search . '%');
                });
            })
            ->when($startDate, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            ->paginate(20)
            ->appends($request->except('page'));

        return view('admin.reservas.rejeitadas', [
            'reservas' => $reservas,
            'pageTitle' => 'Reservas Rejeitadas',
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Rejeita uma reserva pendente.
     * @param Request $request
     * @param Reserva $reserva
     */
    public function rejeitarReserva(Request $request, Reserva $reserva)
    {
        // 1. Validação de Status
        if ($reserva->status !== Reserva::STATUS_PENDENTE) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'A reserva não está pendente.'], 400);
            }
            return redirect()->back()->with('error', 'A reserva não está mais pendente.');
        }

        // 2. Validação do Motivo (Opcional)
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|min:5|max:255',
        ]);

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_REJEITADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = $validated['rejection_reason'] ?? 'Rejeitada pelo gestor (motivo não especificado).';
            $reserva->save();

            // 3. Recria o slot fixo de disponibilidade (verde) para liberar a agenda
            if (isset($this->reservaController)) {
                $this->reservaController->recreateFixedSlot($reserva);
            } else {
                Log::warning("ReservaController não injetado em AdminController. Slot fixo não recriado automaticamente para reserva {$reserva->id}.");
            }

            DB::commit();
            Log::info("Reserva ID: {$reserva->id} rejeitada pelo gestor ID: " . Auth::id());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Reserva rejeitada com sucesso! O horário foi liberado.'], 200);
            }
            return redirect()->back()->with('success', 'Reserva rejeitada e horário liberado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao rejeitar reserva ID: {$reserva->id}.", ['exception' => $e]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Erro interno ao rejeitar a reserva.'], 500);
            }
            return redirect()->back()->with('error', 'Erro interno ao rejeitar a reserva.');
        }
    }


    /**
     * Cancela uma reserva PONTUAL confirmada (PATCH /admin/reservas/{reserva}/cancelar).
     * @param Reserva $reserva A reserva confirmada PONTUAL a ser cancelada.
     */
    public function cancelarReserva(Request $request, Reserva $reserva)
    {
        if ($reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'Use as rotas de cancelamento de série para reservas recorrentes.'], 400);
        }
        if ($reserva->status !== Reserva::STATUS_CONFIRMADA) {
            return response()->json(['success' => false, 'message' => 'A reserva não está confirmada.'], 400);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
        ]);

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = '[Gestor] ' . $validated['cancellation_reason'];
            $reserva->save();

            // 1. Recria o slot fixo de disponibilidade (verde)
            $this->reservaController->recreateFixedSlot($reserva);

            DB::commit();
            Log::info("Reserva PONTUAL ID: {$reserva->id} cancelada pelo gestor ID: " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Reserva cancelada com sucesso! O horário foi liberado.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar reserva PONTUAL ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao cancelar a reserva.'], 500);
        }
    }


    /**
     * Cancela UMA reserva de uma série recorrente (PATCH /admin/reservas/{reserva}/cancelar-pontual).
     * @param Reserva $reserva A reserva específica na série a ser cancelada.
     */
    public function cancelarReservaRecorrente(Request $request, Reserva $reserva)
    {
        if (!$reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'A reserva não é recorrente. Use a rota de cancelamento pontual.'], 400);
        }
        if ($reserva->status !== Reserva::STATUS_CONFIRMADA) {
            return response()->json(['success' => false, 'message' => 'A reserva não está confirmada.'], 400);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
        ]);

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = '[Gestor - Pontual Recorrência] ' . $validated['cancellation_reason'];
            $reserva->save();

            // 1. Recria o slot fixo de disponibilidade (verde)
            $this->reservaController->recreateFixedSlot($reserva);

            DB::commit();
            Log::info("Reserva RECORRENTE PONTUAL ID: {$reserva->id} cancelada pelo gestor ID: " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Reserva recorrente pontual cancelada com sucesso! O horário foi liberado.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar reserva RECORRENTE PONTUAL ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao cancelar a reserva pontual: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Cancela TODAS as reservas futuras de uma série recorrente (DELETE /admin/reservas/{reserva}/cancelar-serie).
     * @param Reserva $reserva Qualquer reserva pertencente à série.
     */
    public function cancelarSerieRecorrente(Request $request, Reserva $reserva)
    {
        if (!$reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'A reserva não pertence a uma série recorrente.'], 400);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
        ]);

        // Determina o ID mestre da série
        $masterId = $reserva->recurrent_series_id ?? $reserva->id;
        $today = Carbon::today()->toDateString();
        $cancellationReason = '[Gestor - Série Recorrente] ' . $validated['cancellation_reason'];
        $managerId = Auth::id();

        DB::beginTransaction();
        try {
            // Busca todas as reservas da série (incluindo a mestra) que estão no futuro
            $seriesReservas = Reserva::where(function ($query) use ($masterId) {
                $query->where('recurrent_series_id', $masterId)
                    ->orWhere('id', $masterId);
            })
                ->where('is_fixed', false)
                ->whereDate('date', '>=', $today)
                ->where('status', Reserva::STATUS_CONFIRMADA)
                ->get();

            $cancelledCount = 0;

            foreach ($seriesReservas as $slot) {
                $slot->status = Reserva::STATUS_CANCELADA;
                $slot->manager_id = $managerId;
                $slot->cancellation_reason = $cancellationReason;
                $slot->save();

                // Recria o slot fixo para cada item cancelado da série.
                $this->reservaController->recreateFixedSlot($slot);

                $cancelledCount++;
            }

            DB::commit();
            Log::info("Série Recorrente MASTER ID: {$masterId} cancelada pelo gestor ID: " . Auth::id() . ". Total de {$cancelledCount} slots liberados.");

            return response()->json(['success' => true, 'message' => "Toda a série recorrente futura (total de {$cancelledCount} slots) foi cancelada com sucesso! Os horários foram liberados."], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar série recorrente ID: {$masterId}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao cancelar a série recorrente.'], 500);
        }
    }


    /**
     * Exclui permanentemente uma reserva (Admin).
     */
    public function destroyReserva(Reserva $reserva)
    {
        DB::beginTransaction();
        try {
            // Se a reserva era ativa (confirmada/pendente) antes da exclusão
            if ($reserva->status === Reserva::STATUS_CONFIRMADA || $reserva->status === Reserva::STATUS_PENDENTE) {
                // Se for uma reserva de cliente, recria o slot
                if (!$reserva->is_fixed) {
                    $this->reservaController->recreateFixedSlot($reserva);
                }
            }

            // Exclusão PERMANENTE.
            $reserva->delete();

            DB::commit();
            Log::warning("Reserva ID: {$reserva->id} excluída permanentemente pelo gestor ID: " . Auth::id());
            return redirect()->route('admin.reservas.confirmadas')->with('success', 'Reserva excluída permanentemente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao excluir reserva ID: {$reserva->id}.", ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro ao excluir reserva: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------------
    // MÓDULO: GERENCIAMENTO DE USUÁRIOS
    // ------------------------------------------------------------------------

    /**
     * Exibe a lista de todos os usuários, com opção de filtro por função (role) e pesquisa.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function indexUsers(Request $request)
    {
        // 1. Obtém o filtro de função e a busca da query string
        $roleFilter = $request->query('role_filter');
        $search = $request->query('search');

        $query = User::query();

        // 2. Aplica o filtro de função.
        if ($roleFilter) {
            if ($roleFilter === 'gestor') {
                // Inclui 'admin' e 'gestor'
                $query->whereIn('role', ['gestor', 'admin']);
            } elseif ($roleFilter === 'cliente') {
                $query->where('role', 'cliente');
            }
        }

        // 3. Aplica o filtro de pesquisa (Search)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('whatsapp_contact', 'like', '%' . $search . '%');
            });
        }

        // 4. Obtém os usuários, ordenando primeiro por Função (Gestor/Admin = 0, Cliente = 1), e depois por Nome.
        $users = $query
            ->orderByRaw("CASE WHEN role IN ('admin', 'gestor') THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(20);

        // 5. Passa todas as variáveis necessárias para a View
        return view('admin.users.index', [
            'users' => $users,
            'pageTitle' => 'Gerenciamento de Usuários',
            'roleFilter' => $roleFilter,
            'search' => $search,
        ]);
    }

    /**
     * Exibe o formulário de criação de usuário.
     */
    public function createUser()
    {
        return view('admin.users.create');
    }

    /**
     * Salva um novo usuário.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'whatsapp_contact' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['cliente', 'gestor', 'admin'])],
        ]);

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'whatsapp_contact' => $request->whatsapp_contact,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_admin' => in_array($request->role, ['gestor', 'admin']),
            ]);

            return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao criar usuário via Admin:", ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Erro ao criar usuário: ' . $e->getMessage());
        }
    }

    /**
     * Exibe o formulário de edição de usuário.
     */
    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Atualiza um usuário.
     */
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'whatsapp_contact' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(['cliente', 'gestor', 'admin'])],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp_contact' => $request->whatsapp_contact,
            'role' => $request->role,
            'is_admin' => in_array($request->role, ['gestor', 'admin']),
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        try {
            $user->update($userData);

            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar usuário ID: {$user->id}.", ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
        }
    }

    /**
     * Exclui um usuário.
     * Inclui checagem de integridade de reservas ativas.
     */
    public function destroyUser(User $user)
    {
        // 1. Impede a auto-exclusão
        if (Auth::user()->id === $user->id) {
            return redirect()->back()->with('error', 'Você não pode excluir sua própria conta.');
        }

        // 2. CHECAGEM CRÍTICA DE RESERVAS ATIVAS (Pontuais ou Recorrentes)
        $activeReservationsExist = Reserva::where('user_id', $user->id)
            ->where('is_fixed', false) // Apenas reservas reais de clientes, não slots de disponibilidade
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->exists();

        if ($activeReservationsExist) {
            $errorMessage = "Impossível excluir o usuário '{$user->name}'. Ele(a) possui reservas ativas (pendentes ou confirmadas). Cancele ou rejeite todas as reservas dele(a) antes de prosseguir com a exclusão.";
            Log::warning("Exclusão de usuário ID: {$user->id} bloqueada por reservas ativas.");
            return redirect()->back()->with('error', $errorMessage);
        }

        try {
            // 3. Antes de excluir o usuário, zere os IDs de manager nas reservas para manter a integridade
            Reserva::where('manager_id', $user->id)->update(['manager_id' => null]);

            $user->delete();

            Log::warning("Usuário ID: {$user->id} excluído pelo gestor ID: " . Auth::id());
            return redirect()->route('admin.users.index')->with('success', 'Usuário excluído com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao excluir o usuário {$user->id}.", ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro ao excluir o usuário: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------------
    // MÓDULO: RESERVAS POR CLIENTE
    // ------------------------------------------------------------------------

    /**
     * Exibe a lista de reservas (ativas e históricas) de um cliente específico.
     *
     * @param \App\Models\User $user O cliente cujas reservas serão listadas.
     */
    public function clientReservations(User $user)
    {
        if ($user->role !== 'cliente') {
            return redirect()->route('admin.users.index')->with('error', 'Apenas clientes podem ter histórico de reservas nesta seção.');
        }

        // 1. Busca todas as reservas do cliente, excluindo slots fixos (is_fixed=true)
        $reservas = Reserva::where('user_id', $user->id)
            ->where('is_fixed', false)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // 2. Cálculo da Contagem Total de Slots FUTUROS/HOJE por Série
        $seriesFutureCounts = Reserva::where('user_id', $user->id)
            ->where('is_fixed', false)
            ->where('is_recurrent', true)
            // Filtra apenas status que podem ser cancelados (ativos)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            // Filtra apenas reservas futuras ou de hoje
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->select('recurrent_series_id', DB::raw('count(*) as total'))
            ->groupBy('recurrent_series_id')
            ->pluck('total', 'recurrent_series_id')
            ->toArray();


        // 3. Paginação manual do Collection
        $perPage = 20;
        $page = request()->get('page', 1);
        $paginatedReservas = $reservas->slice(($page - 1) * $perPage, $perPage)->values();

        // 4. Cria o Paginator
        $reservasPaginadas = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedReservas,
            $reservas->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );


        return view('admin.users.reservas', [
            'reservas' => $reservasPaginadas,
            'client' => $user,
            'pageTitle' => "Reservas de Cliente: {$user->name}",
            'seriesFutureCounts' => $seriesFutureCounts,
        ]);
    }

    /**
     * Cancela TODAS as reservas futuras de uma série recorrente específica (a partir do masterId).
     * Rota usada na listagem de reservas do cliente.
     * @param Request $request
     * @param int $masterId O ID da reserva mestra (recurrent_series_id).
     */
    public function cancelClientSeries(Request $request, $masterId)
    {
        $validated = $request->validate([
            'justificativa_gestor' => 'required|string|min:5|max:255', // Campo adaptado do front-end
        ]);

        // Validação adicional: garante que o ID mestre existe e pertence a uma série recorrente de cliente
        $masterReserva = Reserva::find($masterId);
        if (!$masterReserva || !$masterReserva->is_recurrent || $masterReserva->is_fixed) {
            return response()->json(['success' => false, 'message' => 'ID da série inválido ou não é uma série recorrente ativa de cliente.'], 400);
        }

        $today = Carbon::today()->toDateString();
        $cancellationReason = '[Gestor - Cliente/Série] ' . $validated['justificativa_gestor'];
        $managerId = Auth::id();

        DB::beginTransaction();
        try {
            // Busca todas as reservas da série (incluindo a mestra) que estão no futuro
            $seriesReservas = Reserva::where(function ($query) use ($masterId) {
                $query->where('recurrent_series_id', $masterId)
                    ->orWhere('id', $masterId);
            })
                ->where('is_fixed', false)
                ->whereDate('date', '>=', $today)
                // Inclui pendentes para garantir que a série inteira seja cancelada
                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                ->get();

            $cancelledCount = 0;

            foreach ($seriesReservas as $slot) {
                // 1. Marca como CANCELADA (status) e adiciona o motivo
                $slot->status = Reserva::STATUS_CANCELADA;
                $slot->manager_id = $managerId;
                $slot->cancellation_reason = $cancellationReason;
                $slot->save();

                // 2. Recria o slot fixo de disponibilidade (verde)
                $this->reservaController->recreateFixedSlot($slot);

                $cancelledCount++;
            }

            DB::commit();
            Log::info("Série Recorrente (Cliente: {$masterReserva->client_name}, Master ID: {$masterId}) cancelada. Total: {$cancelledCount} slots liberados.");

            return response()->json(['success' => true, 'message' => "A série recorrente (ID: {$masterId}) de {$masterReserva->client_name} foi cancelada com sucesso! Total de {$cancelledCount} horários futuros liberados."], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar série recorrente (Admin/Cliente) ID: {$masterId}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao cancelar a série recorrente: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------------
    // MÓDULO: GERENCIAMENTO DE PAGAMENTOS (BAIXA)
    // ------------------------------------------------------------------------

    /**
     * Exibe a página de gerenciamento de pagamentos/baixa de agendamentos.
     */
    public function paymentManagementIndex()
    {
        return view('admin.payment.index');
    }

    /**
     * API: Retorna as reservas do dia para baixa de pagamento.
     */
    public function getDailyReservations(Request $request)
    {
        // 1. Validação da Data
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $targetDate = $request->input('date');

        // 2. Busca Agendamentos do Dia
        $reservations = Reserva::where('is_fixed', false)
            ->whereDate('date', $targetDate)
            // Inclui status que podem ser finalizados (Confirmada, Pendente, Cancelada)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, Reserva::STATUS_CANCELADA])
            ->orderBy('start_time')
            ->get();

        // 3. Formata e calcula valores para a resposta
        $reservationsData = $reservations->map(function ($reserva) {
            // CORREÇÃO: Usar os valores reais do modelo
            $totalPrice = (float) $reserva->price;
            $totalPaid = (float) ($reserva->total_paid ?? 0.00);

            // O valor do sinal é o que foi pago na confirmação
            $signalValue = (float) ($reserva->signal_value ?? 0.00);

            // Valor restante a ser pago no momento da baixa/check-out
            $remainingValue = $totalPrice - $totalPaid;

            return [
                'id' => $reserva->id,
                'time_slot' => Carbon::parse($reserva->start_time)->format('H:i') . ' - ' . Carbon::parse($reserva->end_time)->format('H:i'),
                'date' => Carbon::parse($reserva->date)->format('d/m/Y'),
                'client_name' => $reserva->client_name,
                'client_contact' => $reserva->client_contact,
                'total_price' => number_format($totalPrice, 2, ',', '.'),
                'raw_total_price' => $totalPrice,
                'deposit_price' => number_format($signalValue, 2, ',', '.'),
                'raw_deposit_price' => $signalValue,
                'remaining_value' => number_format(max(0, $remainingValue), 2, ',', '.'),
                'raw_remaining_value' => max(0, $remainingValue),
                'status' => $reserva->status,
                'status_text' => $reserva->status_text, // Acessor no modelo Reserva
            ];
        });

        return response()->json(['success' => true, 'reservations' => $reservationsData]);
    }

    /**
     * Finaliza o pagamento de uma reserva (Baixa no Caixa).
     *
     * @param Request $request
     * @param Reserva $reserva
     */
    public function finalizeReservationPayment(Request $request, Reserva $reserva)
    {
        // 1. Validação
        $validated = $request->validate([
            'final_price' => 'required|numeric|min:0', // Permite que o gestor edite o valor total
            'payment_method' => ['required', Rule::in(['pix', 'cash', 'card', 'transferencia', 'outros'])], // Adiciona método de pagamento
        ]);

        $managerId = Auth::id();
        $currentPaid = (float)($reserva->total_paid ?? 0.0);
        $newFinalPrice = (float)$validated['final_price'];
        $amountToPay = $newFinalPrice - $currentPaid;

        if ($amountToPay < -0.01) { // Tolera pequena diferença de ponto flutuante
             return response()->json(['success' => false, 'message' => 'O valor final não pode ser menor do que o valor já pago.'], 400);
        }

        DB::beginTransaction();
        try {
            // 2. Atualiza os dados da reserva
            // Mantendo STATUS_CONFIRMADA como no original, mas o ideal seria STATUS_CONCLUIDA
            $reserva->status = Reserva::STATUS_CONFIRMADA;
            $reserva->manager_id = $managerId;
            $reserva->price = $newFinalPrice; // Salva o valor final (com ou sem desconto)
            $reserva->total_paid = $newFinalPrice; // O total pago agora é o preço final
            $reserva->payment_status = 'paid'; // Marca como pago
            $reserva->save();

            // 3. Gera a Transação Financeira para o restante (se houver pagamento adicional)
            if ($amountToPay > 0.00) {
                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => $managerId,
                    'amount' => $amountToPay,
                    'type' => 'payment_final', // Tipo para pagamento do restante
                    'payment_method' => $validated['payment_method'],
                    'description' => 'Pagamento final/restante no momento da baixa',
                    'paid_at' => Carbon::now(),
                ]);
            }

            DB::commit();

            Log::info("Pagamento da Reserva ID: {$reserva->id} finalizado por Gestor ID: {$managerId}. Valor Final: R$ {$reserva->price}. Pago agora: R$ {$amountToPay}");

            return response()->json(['success' => true, 'message' => 'Pagamento finalizado com sucesso. Reserva Confirmada e paga.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao finalizar pagamento da Reserva ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao finalizar o pagamento: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Marca uma reserva como falta e qualifica o cliente (opcionalmente blacklist).
     */
    public function markNoShowAndQualify(Request $request, Reserva $reserva)
    {
        // 1. Validação
        $validated = $request->validate([
            'customer_qualification' => 'nullable|string|max:50', // Ex: 'good', 'warning', 'blacklist'
        ]);

        $managerId = Auth::id();

        DB::beginTransaction();
        try {
            // 2. Atualiza a Reserva
            $reserva->status = Reserva::STATUS_CANCELADA; // Usa CANCELADA para indicar que não houve ocupação
            $reserva->manager_id = $managerId;
            $reserva->cancellation_reason = "FALTA (No-Show) - Gestor: " . Auth::user()->name;
            $reserva->save();

            // 3. Atualiza a qualificação do Cliente (se for um usuário registrado)
            if ($reserva->user_id && !empty($validated['customer_qualification'])) {
                $user = User::find($reserva->user_id);
                if ($user) {
                    // CRÍTICO: Este campo 'customer_qualification' deve ser criado na tabela 'users'
                    $user->customer_qualification = $validated['customer_qualification'];
                    $user->save();
                }
            }

            DB::commit();

            Log::info("Reserva ID: {$reserva->id} marcada como FALTA por Gestor ID: {$managerId}. Cliente qualificado: {$validated['customer_qualification']}");

            return response()->json(['success' => true, 'message' => 'Falta registrada e cliente qualificado com sucesso.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao registrar falta/qualificar cliente da Reserva ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao processar a falta: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------------
    // MÓDULO: RELATÓRIOS FINANCEIROS
    // ------------------------------------------------------------------------

    /**
     * Exibe a página principal de relatórios financeiros.
     */
    public function financeiro(Request $request)
    {
        // Data atual
        $hoje = Carbon::today();
        $inicioSemana = $hoje->copy()->startOfWeek();
        $fimSemana = $hoje->copy()->endOfWeek();
        $inicioMes = $hoje->copy()->startOfMonth();
        $fimMes = $hoje->copy()->endOfMonth();

        // Consultas para totais de sinais (usando data de agendamento)
        $sinalHoje = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereDate('date', $hoje)
            ->sum('signal_value');

        $sinalSemana = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereBetween('date', [$inicioSemana, $fimSemana])
            ->sum('signal_value');

        $sinalMes = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereBetween('date', [$inicioMes, $fimMes])
            ->sum('signal_value');

        // Reservas com pagamento pendente (status confirmed mas payment_status não é 'paid')
        $reservasPendentes = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->where(function ($query) {
                $query->where('payment_status', 'pending')
                    ->orWhere('payment_status', 'partial');
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(20);

        return view('admin.financeiro.index', [
            'sinalHoje' => $sinalHoje,
            'sinalSemana' => $sinalSemana,
            'sinalMes' => $sinalMes,
            'reservasPendentes' => $reservasPendentes,
        ]);
    }

    /**
     * Exibe o dashboard financeiro (para ser preenchido por APIs).
     */
    public function dashboardFinanceiro()
    {
        return view('admin.financeiro.dashboard');
    }

    /**
     * API: Resumo Financeiro (para gráficos e cards)
     */
    public function getResumoFinanceiro(Request $request)
    {
        $hoje = now();
        $inicioSemana = $hoje->copy()->startOfWeek();
        $fimSemana = $hoje->copy()->endOfWeek();
        $inicioMes = $hoje->copy()->startOfMonth();
        $fimMes = $hoje->copy()->endOfMonth();

        // TOTAL RECEBIDO (baseado em total_paid no momento da CRIAÇÃO/CONFIRMAÇÃO da reserva)
        $totalRecebidoHoje = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereDate('created_at', $hoje)
            ->sum('total_paid');

        $totalRecebidoSemana = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereBetween('created_at', [$inicioSemana, $fimSemana])
            ->sum('total_paid');

        $totalRecebidoMes = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereBetween('created_at', [$inicioMes, $fimMes])
            ->sum('total_paid');

        // SINAIS RECEBIDOS (informativo, baseado em created_at)
        $sinaisHoje = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereDate('created_at', $hoje)
            ->sum('signal_value');

        $sinaisSemana = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereBetween('created_at', [$inicioSemana, $fimSemana])
            ->sum('signal_value');

        $sinaisMes = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereBetween('created_at', [$inicioMes, $fimMes])
            ->sum('signal_value');

        // CONTAGEM DE RESERVAS (baseado em created_at)
        $reservasConfirmadasHoje = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereDate('created_at', $hoje)
            ->count();

        $reservasConfirmadasSemana = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereBetween('created_at', [$inicioSemana, $fimSemana])
            ->count();

        $reservasConfirmadasMes = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->whereBetween('created_at', [$inicioMes, $fimMes])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_recebido' => [
                    'hoje' => $totalRecebidoHoje,
                    'semana' => $totalRecebidoSemana,
                    'mes' => $totalRecebidoMes,
                ],
                'sinais' => [
                    'hoje' => $sinaisHoje,
                    'semana' => $sinaisSemana,
                    'mes' => $sinaisMes,
                ],
                'reservas' => [
                    'hoje' => $reservasConfirmadasHoje,
                    'semana' => $reservasConfirmadasSemana,
                    'mes' => $reservasConfirmadasMes,
                ]
            ]
        ]);
    }

    /**
     * API: Reservas com Pagamento Pendente (apenas futuras ou de hoje)
     */
    public function getPagamentosPendentes(Request $request)
    {
        $reservasPendentes = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->where(function ($query) {
                $query->where('payment_status', 'pending')
                    ->orWhere('payment_status', 'partial');
            })
            ->whereDate('date', '>=', now()->toDateString())
            ->with('user')
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(function ($reserva) {
                return [
                    'id' => $reserva->id,
                    'cliente' => $reserva->client_name,
                    'contato' => $reserva->client_contact,
                    'data' => Carbon::parse($reserva->date)->format('d/m/Y'),
                    'horario' => Carbon::parse($reserva->start_time)->format('H:i'),
                    'valor_total' => $reserva->price,
                    'sinal_pago' => $reserva->signal_value,
                    'total_pago' => $reserva->total_paid,
                    'valor_restante' => $reserva->price - $reserva->total_paid,
                    'status_pagamento' => $reserva->payment_status,
                    'status_pagamento_texto' => $this->getStatusPagamentoTexto($reserva->payment_status),
                    'cor_status' => $this->getCorStatusPagamento($reserva->payment_status),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $reservasPendentes
        ]);
    }

    // Helper methods para status de pagamento
    private function getStatusPagamentoTexto($status)
    {
        return match ($status) {
            'pending' => 'Pendente',
            'partial' => 'Parcial',
            'paid' => 'Pago',
            'overdue' => 'Atrasado',
            default => 'Desconhecido'
        };
    }

    private function getCorStatusPagamento($status)
    {
        return match ($status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'partial' => 'bg-blue-100 text-blue-800',
            'paid' => 'bg-green-100 text-green-800',
            'overdue' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
}
