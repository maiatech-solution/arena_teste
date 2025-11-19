<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * @var ReservaController
     */
    protected $reservaController;

    // 🛑 CRÍTICO: Injeção de Dependência para acessar os helpers do ReservaController
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
        // 🛑 DELEGA para o método do ReservaController
        return $this->reservaController->dashboard();
    }

    /**
     * Exibe o painel de botões de gerenciamento de reservas.
     */
    public function indexReservasDashboard()
    {
        // O código de contagem não é mais necessário aqui, a view é estática ou usa contagens simples
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
        $isOnlyMine = $request->input('only_mine') === 'true'; // Mantendo a variável, mesmo que o filtro tenha sido simplificado

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
            // O filtro 'only_mine' foi removido do front, mas o código de filtro está aqui para fins de demonstração
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

    // O método 'canceled_index' foi removido, pois a rota não será mais usada.
    // O histórico de cancelamento/rejeição agora é mantido no DB sem a necessidade de deletar.

    /**
     * Exibe o formulário para criação manual de reserva.
     */
    public function createReserva()
    {
        $users = User::where('role', 'cliente')->get();
        // 🛑 CORREÇÃO: O AdminController agora tem um método storeReserva (substituindo o storeManualReserva do seu código)
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
     * (Este método substitui o storeManualReserva do seu código)
     */
    public function storeReserva(Request $request)
    {
        // Validação básica (usando lógica já presente)
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Normaliza as horas para o formato H:i:s
        $startTimeNormalized = Carbon::createFromFormat('H:i', $validated['start_time'])->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('H:i', $validated['end_time'])->format('H:i:s');

        // Checa se o horário está ocupado por outra reserva real (usando helper do ReservaController)
        if ($this->reservaController->checkOverlap($validated['date'], $validated['start_time'], $validated['end_time'], false)) {
            return redirect()->back()->withInput()->with('error', 'O horário selecionado já está ocupado por outra reserva confirmada ou pendente.');
        }

        // Tenta encontrar um slot fixo livre (STATUS_FREE) para consumo
        $fixedSlot = Reserva::where('is_fixed', true)
            ->where('date', $validated['date'])
            ->where('start_time', $startTimeNormalized)
            ->where('end_time', $endTimeNormalized)
            ->where('status', Reserva::STATUS_FREE) // 🛑 CRÍTICO: Busca por STATUS_FREE
            ->first();

        DB::beginTransaction();
        try {
            if ($fixedSlot) {
                // Consome o slot fixo disponível
                $fixedSlot->delete();
            } else {
                // Aviso se o slot fixo não existia, mas permite a criação
                Log::warning("Reserva manual criada sem consumir slot fixo disponível: {$validated['date']} {$startTimeNormalized}.");
            }

            // Cria a nova reserva confirmada
            Reserva::create([
                'user_id' => $validated['user_id'] ?? null,
                'date' => $validated['date'],
                'day_of_week' => Carbon::parse($validated['date'])->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $validated['price'],
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'],
                'notes' => $validated['notes'] ?? null,
                'status' => Reserva::STATUS_CONFIRMADA,
                'is_fixed' => false,
                'is_recurrent' => false,
                'manager_id' => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('admin.reservas.confirmadas')->with('success', 'Reserva criada e confirmada manualmente com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            // 🐛 MELHORIA: Loga a exceção completa
            Log::error("Erro ao criar reserva manual.", ['exception' => $e, 'data' => $validated]);
            return redirect()->back()->withInput()->with('error', 'Erro interno ao criar reserva. Tente novamente.');
        }
    }


    // ------------------------------------------------------------------------
    // MÓDULO: AÇÕES DE STATUS E CANCELAMENTO
    // ------------------------------------------------------------------------

    /**
     * Confirma uma reserva pendente (chamado pelo Admin/Dashboard).
     * @param Reserva $reserva A reserva PENDENTE a ser confirmada.
     */
    public function confirmarReserva(Request $request, Reserva $reserva)
    {
        if ($reserva->status !== Reserva::STATUS_PENDENTE) {
            return response()->json(['success' => false, 'message' => 'A reserva não está pendente.'], 400);
        }

        // Validação para aceitar o valor do sinal
        $validated = $request->validate([
            'confirmation_value' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_CONFIRMADA;
            $reserva->manager_id = Auth::id();
            // Atualiza o preço se um valor de confirmação foi fornecido
            if (isset($validated['confirmation_value'])) {
                $reserva->price = $validated['confirmation_value'];
            }
            $reserva->save();

            DB::commit();
            Log::info("Reserva ID: {$reserva->id} confirmada pelo gestor ID: " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Reserva confirmada com sucesso!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            // 🐛 MELHORIA: Loga a exceção completa
            Log::error("Erro ao confirmar reserva ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao confirmar a reserva.'], 500);
        }
    }


    /**
     * Rejeita uma reserva pendente (chamado pelo Admin/Dashboard).
     * @param Reserva $reserva A reserva PENDENTE a ser rejeitada.
     */
    public function rejeitarReserva(Request $request, Reserva $reserva)
    {
        if ($reserva->status !== Reserva::STATUS_PENDENTE) {
            return response()->json(['success' => false, 'message' => 'A reserva não está pendente.'], 400);
        }

        // O motivo de rejeição é opcional no Front-end, mas importante.
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|min:5|max:255',
        ]);

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_REJEITADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = $validated['rejection_reason'] ?? 'Rejeitada pelo gestor por motivo não especificado.';
            $reserva->save();

            // 1. Recria o slot fixo de disponibilidade (verde)
            // 🛑 CRÍTICO: Delega para o helper correto no ReservaController
            $this->reservaController->recreateFixedSlot($reserva);

            // 2. 🛑 REMOVIDO: A linha $reserva->delete(); FOI REMOVIDA
            //    Mantemos o registro para auditoria.

            DB::commit();
            Log::info("Reserva ID: {$reserva->id} rejeitada pelo gestor ID: " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Reserva rejeitada com sucesso! O horário foi liberado.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            // 🐛 MELHORIA: Loga a exceção completa
            Log::error("Erro ao rejeitar reserva ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao rejeitar a reserva.'], 500);
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
            // 🛑 CRÍTICO: Delega para o helper correto no ReservaController
            $this->reservaController->recreateFixedSlot($reserva);

            // 2. 🛑 REMOVIDO: A linha $reserva->delete(); FOI REMOVIDA
            //    Mantemos o registro para auditoria.

            DB::commit();
            Log::info("Reserva PONTUAL ID: {$reserva->id} cancelada pelo gestor ID: " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Reserva cancelada com sucesso! O horário foi liberado.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            // 🐛 MELHORIA: Loga a exceção completa
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
            // Se for o mestre, devemos parar. Mas o mestre é tratado no DELETE.
            // Aqui, é um slot pontual de uma série.
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = '[Gestor - Pontual Recorrência] ' . $validated['cancellation_reason'];
            $reserva->save();

            // 1. Recria o slot fixo de disponibilidade (verde)
            // ✅ CRÍTICO: Delega para o helper correto no ReservaController. Isso resolve o problema de slot sumir.
            $this->reservaController->recreateFixedSlot($reserva);

            // 2. 🛑 REMOVIDO: A linha $reserva->delete(); FOI REMOVIDA
            //    Mantemos o registro para auditoria.

            DB::commit();
            Log::info("Reserva RECORRENTE PONTUAL ID: {$reserva->id} cancelada pelo gestor ID: " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Reserva recorrente pontual cancelada com sucesso! O horário foi liberado.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            // 🐛 MELHORIA: Loga a exceção completa
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

                // 🛑 CRÍTICO: Recria o slot fixo para cada item cancelado da série.
                $this->reservaController->recreateFixedSlot($slot);

                // 🛑 REMOVIDO: A linha $slot->delete(); FOI REMOVIDA
                //    Mantemos o registro para auditoria.

                $cancelledCount++;
            }

            DB::commit();
            Log::info("Série Recorrente MASTER ID: {$masterId} cancelada pelo gestor ID: " . Auth::id() . ". Total de {$cancelledCount} slots liberados.");

            return response()->json(['success' => true, 'message' => "Toda a série recorrente futura (total de {$cancelledCount} slots) foi cancelada com sucesso! Os horários foram liberados."], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            // 🐛 MELHORIA: Loga a exceção completa
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

            // CRÍTICO: Aqui mantemos o delete, pois o propósito deste método é a exclusão PERMANENTE.
            $reserva->delete();

            DB::commit();
            Log::warning("Reserva ID: {$reserva->id} excluída permanentemente pelo gestor ID: " . Auth::id()); // 🐛 ADICIONADO LOG
            return redirect()->route('admin.reservas.confirmadas')->with('success', 'Reserva excluída permanentemente.');
        } catch (\Exception $e) {
            DB::rollBack();
            // 🐛 MELHORIA: Loga a exceção completa
            Log::error("Erro ao excluir reserva ID: {$reserva->id}.", ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro ao excluir reserva: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------------
    // MÓDULO: GERENCIAMENTO DE USUÁRIOS
    // ------------------------------------------------------------------------

    /**
     * Exibe a lista de todos os usuários.
     */
    public function indexUsers()
    {
        // 🛑 CRÍTICO: Delega para o UserController (se for o caso) ou usa a lógica local
        $users = User::all()->sortBy('name');

        // ✅ CORREÇÃO: Passa a variável $pageTitle para a view
        return view('admin.users.index', [
            'users' => $users,
            'pageTitle' => 'Gerenciamento de Usuários', // Título dinâmico
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
            'role' => ['required', Rule::in(['cliente', 'gestor'])],
        ]);

        try { // 🐛 ADICIONADO TRY/CATCH
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'whatsapp_contact' => $request->whatsapp_contact,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_admin' => $request->role === 'gestor',
            ]);

            return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso.');
        } catch (\Exception $e) {
            // 🐛 MELHORIA: Loga a exceção completa
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
            'role' => ['required', Rule::in(['cliente', 'gestor'])],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp_contact' => $request->whatsapp_contact,
            'role' => $request->role,
            'is_admin' => $request->role === 'gestor',
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        try { // 🐛 ADICIONADO TRY/CATCH
            $user->update($userData);

            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado com sucesso.');
        } catch (\Exception $e) {
            // 🐛 MELHORIA: Loga a exceção completa
            Log::error("Erro ao atualizar usuário ID: {$user->id}.", ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
        }
    }

    /**
     * Exclui um usuário.
     */
    public function destroyUser(User $user)
    {
        // Impede a auto-exclusão
        if (Auth::user()->id === $user->id) {
            return redirect()->back()->with('error', 'Você não pode excluir sua própria conta.');
        }

        try {
            // Antes de excluir o usuário, zere os IDs de manager nas reservas para manter a integridade
            Reserva::where('manager_id', $user->id)->update(['manager_id' => null]);

            $user->delete();

            Log::warning("Usuário ID: {$user->id} excluído pelo gestor ID: " . Auth::id()); // 🐛 ADICIONADO LOG
            return redirect()->route('admin.users.index')->with('success', 'Usuário excluído com sucesso.');
        } catch (\Exception $e) {
            // 🐛 MELHORIA: Loga a exceção completa
            Log::error("Erro ao excluir o usuário {$user->id}.", ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro ao excluir o usuário: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------------
    // MÉTODOS OBSOLETOS OU DELEGADOS (Removidos do corpo do controller)
    // ------------------------------------------------------------------------
    // OBS: Os métodos checkOverlap e findOrCreateClient no seu código foram substituídos
    // pela chamada aos helpers do ReservaController ou movidos para lá para centralização.
}
