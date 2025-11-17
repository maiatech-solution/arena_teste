<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Events\ReservaCancelada;

class AdminController extends Controller
{
    /**
     * Exibe o dashboard principal do gestor.
     */
    public function dashboard()
    {
        $reservasPendentesCount = Reserva::where('status', Reserva::STATUS_PENDENTE)->count();

        // Pega as séries recorrentes que estão terminando (usando a lógica do ReservaController)
        try {
            $reservaController = app(\App\Http\Controllers\ReservaController::class);
            $expiringSeries = $reservaController->getEndingRecurrentSeries();
            $expiringSeriesCount = count($expiringSeries);
        } catch (\Exception $e) {
            // Caso o ReservaController não esteja disponível ou o método falhe
            Log::warning("Não foi possível carregar séries recorrentes expirando: " . $e->getMessage());
            $expiringSeries = collect();
            $expiringSeriesCount = 0;
        }

        return view('dashboard', compact('reservasPendentesCount', 'expiringSeries', 'expiringSeriesCount'));
    }

    // =========================================================================
    // Pesquisa de Clientes Registrados (Para Agendamento Rápido)
    // =========================================================================
    public function searchClients(Request $request)
    {
        $query = $request->input('query');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        // Busca usuários com a role 'cliente'
        $clients = User::where('role', 'cliente')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('whatsapp_contact', 'like', '%' . $query . '%');
            })
            // Limita a 10 resultados para otimizar a pesquisa
            ->limit(10)
            ->get();

        // Formata a saída para o JS
        $formattedClients = $clients->map(function ($client) {
             // Formatação simples do WhatsApp para exibição no frontend (exemplo)
             $formattedContact = $client->whatsapp_contact;
             if ($formattedContact && strlen($formattedContact) >= 11) {
                 // Ex: 5541999998888 -> (41) 99999-8888
                 $formattedContact = '('.substr($formattedContact, 2, 2) . ') ' . substr($formattedContact, 4, 5) . '-' . substr($formattedContact, 9);
             }

             return [
                 'id' => $client->id,
                 'name' => $client->name,
                 'email' => $client->email,
                 'whatsapp_contact' => $formattedContact,
                 'contact' => $client->whatsapp_contact, // Retorna o contato cru (sem formatação) para uso interno
             ];
        });

        return response()->json($formattedClients);
    }
    // =========================================================================

    // =========================================================================
    // MÉTODO API: RESERVAS CONFIRMADAS/PENDENTES PARA FULLCALENDAR
    // =========================================================================
    public function getConfirmedReservasApi(Request $request)
    {
        // O FullCalendar envia os parâmetros 'start' e 'end' para filtrar o período
        $start = $request->input('start') ? Carbon::parse($request->input('start')) : Carbon::now()->startOfMonth();
        $end = $request->input('end') ? Carbon::parse($request->input('end')) : Carbon::now()->endOfMonth();

        // Busca reservas reais de clientes (is_fixed = false)
        $reservas = Reserva::where('is_fixed', false)
                             ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                             ->whereDate('date', '>=', $start->toDateString())
                             ->whereDate('date', '<=', $end->toDateString())
                             ->with('user')
                             ->get();

        $events = $reservas->map(function ($reserva) {
            $bookingDate = $reserva->date->toDateString();

            // Usa os campos de TIME para construir o DateTime
            $start = Carbon::parse($bookingDate . ' ' . $reserva->start_time);
            $end = $reserva->end_time ? Carbon::parse($bookingDate . ' ' . $reserva->end_time) : $start->copy()->addHour();

            $userName = optional($reserva->user)->name;
            $clientName = $userName ?? $reserva->client_name ?? 'Cliente Desconhecido';

            $isRecurrent = (bool)$reserva->is_recurrent;

            if ($reserva->status === Reserva::STATUS_PENDENTE) {
                $statusColor = '#ff9800'; // Laranja
                $statusText = 'PENDENTE: ';
                $className = 'fc-event-pending';
            } elseif ($isRecurrent) {
                $statusColor = '#C026D3'; // Fuchsia
                $statusText = 'RECORRENTE: ';
                $className = 'fc-event-recurrent';
            } else {
                $statusColor = '#4f46e5'; // Indigo
                $statusText = 'RESERVADO: ';
                $className = 'fc-event-quick';
            }

            // Monta o título do evento
            $title = $statusText . $clientName;
            if (isset($reserva->price)) {
                $title .= ' - R$ ' . number_format($reserva->price, 2, ',', '.');
            }

            return [
                'id' => $reserva->id,
                'title' => $title,
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'color' => $statusColor,
                'className' => $className,
                'extendedProps' => [
                    'status' => $reserva->status,
                    'client_contact' => $reserva->client_contact,
                    'is_recurrent' => (bool)$reserva->is_recurrent,
                    'recurrent_series_id' => $reserva->recurrent_series_id,
                ]
            ];
        });

        return response()->json($events);
    }
    // =========================================================================

    // --- Métodos de Listagem, Ação e Status de Reservas ---

    public function indexReservas()
    {
        $reservas = Reserva::where('status', Reserva::STATUS_PENDENTE)
                            ->with('user')
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
        $pageTitle = 'Pré-Reservas Pendentes';
        return view('admin.reservas.index', compact('reservas', 'pageTitle'));
    }

    /**
     * Exibe o índice de reservas confirmadas, ordenadas por data crescente.
     */
    public function confirmed_index(Request $request)
    {
        // Pega o termo de busca, se existir
        $search = $request->get('search');

        $query = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                            // Apenas reservas reais de clientes
                            ->where('is_fixed', false)
                            // Apenas reservas futuras ou de hoje
                            ->whereDate('date', '>=', Carbon::today()->toDateString())
                            ->with('user');

        // Aplica filtro de pesquisa
        if ($search) {
             $query->where(function($q) use ($search) {
                 $q->where('client_name', 'like', '%' . $search . '%')
                     ->orWhere('client_contact', 'like', '%'.$search.'%');
                 // Se estiver usando user_id, pesquisa pelo nome/email do usuário relacionado
                 $q->orWhereHas('user', function ($userQuery) use ($search) {
                     $userQuery->where('name', 'like', '%' . $search . '%')
                                     ->orWhere('email', 'like', '%' . $search . '%');
                 });
             });
        }


        $isOnlyMine = $request->get('only_mine') === 'true';

        if ($isOnlyMine) {
            $pageTitle = 'Minhas Reservas Manuais Confirmadas';
            $query->where('manager_id', Auth::id());
        } else {
            $pageTitle = 'Todas as Reservas Confirmadas (Próximos Agendamentos)';
        }

        $reservas = $query->orderBy('date', 'asc')
                            ->orderBy('start_time', 'asc')
                            ->paginate(15);

        return view('admin.reservas.confirmed_index', compact('reservas', 'pageTitle', 'isOnlyMine', 'search'));
    }

    public function showReserva(Reserva $reserva)
    {
        $reserva->load('user', 'manager');
        return view('admin.reservas.show', compact('reserva'));
    }

    /**
     * Redireciona a rota de criação manual para o Dashboard.
     */
    public function createReserva()
    {
        return redirect()->route('dashboard')
            ->with('warning', 'A criação manual foi simplificada! Por favor, use o calendário (slots verdes) na tela principal para agendamento rápido.');
    }

    // --- MÉTODOS DE AÇÕES PADRÃO (CONFIRMAR, REJEITAR, CANCELAR) ---

    public function confirmarReserva(Reserva $reserva)
    {
        // Garante que o método checkOverlap é chamado a partir do ReservaController (agora público)
        $reservaController = app(\App\Http\Controllers\ReservaController::class);

        try {
            $dateString = $reserva->date->toDateString();
            $isFixed = $reserva->is_fixed;
            $ignoreId = $reserva->id;

            // 1. Checagem de Conflito (Usando ReservaController)
            if ($reservaController->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                 return back()->with('error', 'Conflito detectado: Esta reserva não pode ser confirmada pois já existe outro agendamento (Pendente ou Confirmado) no mesmo horário.');
            }

            // 2. Atualiza Status e atribui o Gestor
            $reserva->update([
                'status' => Reserva::STATUS_CONFIRMADA,
                'manager_id' => Auth::id(), // O gestor que confirma
            ]);

            return redirect()->route('dashboard')
                             ->with('success', 'Reserva confirmada com sucesso! O horário está agora visível no calendário.');
        } catch (\Exception $e) {
            Log::error("Erro ao confirmar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao confirmar a reserva: ' . $e->getMessage());
        }
    }

    /**
     * Recria o slot fixo após a rejeição da pré-reserva.
     */
    public final function rejeitarReserva(Reserva $reserva)
    {
        DB::beginTransaction();
        try {
            // 1. Captura as informações do slot original (data, hora, preço)
            $originalData = $reserva->only(['date', 'day_of_week', 'start_time', 'end_time', 'price']);

            // 2. Marca o status como REJEITADA e o gestor responsável
            $reserva->update([
                'status' => Reserva::STATUS_REJEITADA,
                'manager_id' => Auth::id(),
                'cancellation_reason' => 'Pré-reserva rejeitada pelo gestor.'
            ]);

            // 3. Recria o slot fixo de disponibilidade (o evento verde)
            Reserva::create([
                'date' => $originalData['date']->toDateString(),
                'day_of_week' => $originalData['day_of_week'],
                'start_time' => $originalData['start_time'],
                'end_time' => $originalData['end_time'],
                'price' => $originalData['price'],
                'client_name' => 'Slot Fixo de 1h',
                'client_contact' => 'N/A',
                'status' => Reserva::STATUS_CONFIRMADA, // Torna o slot DISPONÍVEL (verde)
                'is_fixed' => true,
                'manager_id' => Auth::id(),
            ]);

            // 4. Deleta a reserva rejeitada do histórico ativo
            $reserva->delete();

            DB::commit();

            // Força a recarga do objeto do usuário autenticado no Laravel
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.reservas.index')
                             ->with('success', 'Pré-reserva rejeitada e horário liberado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao rejeitar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao rejeitar a reserva: ' . $e->getMessage());
        }
    }

    // ✅ MÉTODO: Cancelamento Pontual de Reserva Padrão (Avulso)
    public function cancelarReserva(Request $request, Reserva $reserva)
    {
        // Validação do Motivo do Cancelamento
        $request->validate([
            'cancellation_reason' => 'required|string|min:5',
        ]);

        if ($reserva->is_recurrent) {
             return response()->json(['success' => false, 'message' => 'Esta reserva é recorrente. Use o botão "Cancelar ESTE DIA" ou "Cancelar SÉRIE" para gerenciar.'], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Atualiza o status para cancelado e salva o motivo
            $reserva->update([
                'status' => Reserva::STATUS_CANCELADA,
                'manager_id' => Auth::id(),
                'cancellation_reason' => $request->input('cancellation_reason'),
            ]);

            // Dispara o Evento de Notificação (se necessário)
            if (class_exists(\App\Events\ReservaCancelada::class)) {
                event(new \App\Events\ReservaCancelada($reserva));
            }

            // 2. Recria o slot fixo de disponibilidade
            $originalData = $reserva->only(['date', 'day_of_week', 'start_time', 'end_time', 'price']);

             Reserva::create([
                'date' => $originalData['date']->toDateString(),
                'day_of_week' => $originalData['day_of_week'],
                'start_time' => $originalData['start_time'],
                'end_time' => $originalData['end_time'],
                'price' => $originalData['price'],
                'client_name' => 'Slot Fixo de 1h',
                'client_contact' => 'N/A',
                'status' => Reserva::STATUS_CONFIRMADA,
                'is_fixed' => true,
                'manager_id' => Auth::id(),
            ]);

            // 3. Deleta a reserva cancelada (para histórico, você pode mover para uma tabela de arquivamento em vez de deletar)
            $reserva->delete();

            DB::commit();

            // DEFESA: Força a recarga do objeto do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return response()->json(['success' => true, 'message' => 'Reserva pontual cancelada e slot liberado com sucesso.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao processar cancelamento de reserva ID {$reserva->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao processar o cancelamento: ' . $e->getMessage()], 500);
        }
    }


    // =========================================================================
    // Cancelamento Pontual de Reserva Recorrente (Exceção)
    // =========================================================================
    public function cancelarReservaRecorrente(Request $request, Reserva $reserva)
    {
        // Validação do Motivo
        $request->validate([
            'cancellation_reason' => 'required|string|min:5',
        ]);

        if (!$reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'Esta reserva não faz parte de uma série recorrente e deve ser cancelada diretamente.'], 422);
        }

        // 1. Captura as informações do slot original
        $originalData = $reserva->only(['date', 'day_of_week', 'start_time', 'end_time', 'price']);
        $cancellationReason = $request->input('cancellation_reason');

        DB::beginTransaction();
        try {
            // Marca o motivo antes de deletar (para histórico, se necessário)
            $reserva->cancellation_reason = $cancellationReason . " (Pontual da Série)";
            $reserva->manager_id = Auth::id();
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->save();

            // Dispara o Evento de Notificação (se necessário)
            if (class_exists(\App\Events\ReservaCancelada::class)) {
                event(new \App\Events\ReservaCancelada($reserva));
            }

            // 2. Apaga a reserva real do cliente (A reserva recorrente)
            $reserva->delete();

            // 3. Recria o slot fixo de disponibilidade (o evento verde)
            Reserva::create([
                'date' => $originalData['date']->toDateString(),
                'day_of_week' => $originalData['day_of_week'],
                'start_time' => $originalData['start_time'],
                'end_time' => $originalData['end_time'],
                'price' => $originalData['price'],
                'client_name' => 'Slot Fixo de 1h', // Nome padrão
                'client_contact' => 'N/A',
                'status' => Reserva::STATUS_CONFIRMADA, // Torna o slot DISPONÍVEL (verde)
                'is_fixed' => true, // Volta a ser um slot fixo, mas apenas para esta data!
                'manager_id' => Auth::id(), // Registra o gestor que liberou o slot
            ]);

            DB::commit();

            // DEFESA: Força a recarga do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return response()->json([
                'success' => true,
                'message' => "Cancelamento pontual realizado! O horário de {$reserva->client_name} no dia {$originalData['date']->format('d/m/Y')} foi liberado para novos agendamentos PONTUAIS."
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao recriar slot fixo após cancelamento pontual: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao processar o cancelamento pontual: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // Cancelamento de SÉRIE Recorrente
    // =========================================================================
    public function cancelarSerieRecorrente(Request $request, Reserva $reserva)
    {
        // Validação do Motivo
        $request->validate([
            'cancellation_reason' => 'required|string|min:5',
        ]);

        if (!$reserva->is_recurrent) {
             return response()->json(['success' => false, 'message' => 'Esta reserva não faz parte de uma série recorrente e não pode ser cancelada em série.'], 422);
        }

        // 1. Identifica a série (mestra ou membro)
        $masterId = $reserva->recurrent_series_id ?? $reserva->id;
        $clientName = $reserva->client_name;
        $cancellationReason = $request->input('cancellation_reason');

        // 2. Busca o slot mestre e todos os membros futuros
        $reservasToCancel = Reserva::where(function($query) use ($masterId) {
             // Inclui o mestre (se a reserva atual for o mestre)
             $query->where('id', $masterId)
                 // Inclui todos os membros vinculados
                 ->orWhere('recurrent_series_id', $masterId);
             })
             // Apenas reservas futuras (a partir da data da reserva atual ou depois)
             ->whereDate('date', '>=', $reserva->date->toDateString())
             ->where('is_fixed', false) // Apenas reservas reais de cliente
             ->get();

        $count = $reservasToCancel->count();

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'Nenhuma reserva futura encontrada para esta série a partir desta data.'], 404);
        }

        // 3. Executa o cancelamento em massa (Deletar as reservas reais e recriar slots fixos)
        DB::beginTransaction();
        try {
            // Captura os dados para recriação do slot (de qualquer item da série)
            $firstReserva = $reservasToCancel->first();
            $start = $firstReserva->start_time;
            $end = $firstReserva->end_time;
            $dayOfWeek = $firstReserva->day_of_week;
            $price = $firstReserva->price;

            // Marca o motivo em cada reserva antes de deletar
            $reservasToCancel->each(function($r) use ($cancellationReason, $dayOfWeek) {
                 $r->cancellation_reason = $cancellationReason . " (Série Recorrente - Dia da Semana: " . $dayOfWeek . ")";
                 $r->manager_id = Auth::id();
                 $r->status = Reserva::STATUS_CANCELADA;
                 $r->save();

                 // Dispara o Evento de Notificação (se necessário)
                 if (class_exists(\App\Events\ReservaCancelada::class)) {
                     event(new \App\Events\ReservaCancelada($r));
                 }
            });

            // Apaga todas as reservas reais da série futuras
            Reserva::whereIn('id', $reservasToCancel->pluck('id'))->delete();

            // 4. Recria a série de slots fixos genéricos para o mesmo período
            $dates = $reservasToCancel->pluck('date');
            $dates->each(function($date) use ($dayOfWeek, $start, $end, $price) {
                 Reserva::create([
                     'date' => $date->toDateString(),
                     'day_of_week' => $dayOfWeek,
                     'start_time' => $start,
                     'end_time' => $end,
                     'price' => $price,
                     'client_name' => 'Slot Fixo de 1h',
                     'client_contact' => 'N/A',
                     'status' => Reserva::STATUS_CONFIRMADA, // Volta a ser Disponível
                     'is_fixed' => true,
                     'manager_id' => Auth::id(), // Registra o gestor que liberou o slot
                 ]);
            });

            DB::commit();

            // DEFESA: Força a recarga do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }


            return response()->json([
                'success' => true,
                'message' => "Série recorrente do cliente '{$clientName}' ({$start}) cancelada com sucesso! {$count} slots foram liberados para agendamentos pontuais."
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar série recorrente (ID Mestra: {$masterId}): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao cancelar a série recorrente: ' . $e->getMessage()], 500);
        }
    }


    public function updateStatusReserva(Request $request, Reserva $reserva)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_PENDENTE,
                Reserva::STATUS_REJEITADA,
                Reserva::STATUS_CANCELADA,
            ])],
        ]);
        $newStatus = $validated['status'];
        $updateData = ['status' => $newStatus];

        $reservaController = app(\App\Http\Controllers\ReservaController::class);

        // Lógica de Confirmação (com checagem de conflito)
        if ($newStatus === Reserva::STATUS_CONFIRMADA) {
            try {
                $dateString = $reserva->date->toDateString();
                $isFixed = $reserva->is_fixed;
                $ignoreId = $reserva->id;

                if ($reservaController->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                     return back()->with('error', 'Conflito detectado: Não é possível confirmar, pois já existe outro agendamento neste horário.');
                }
                $updateData['manager_id'] = Auth::id();
            } catch (\Exception $e) {
                 return back()->with('error', 'Erro na verificação de conflito: ' . $e->getMessage());
            }
        }

        if (in_array($newStatus, [Reserva::STATUS_REJEITADA, Reserva::STATUS_CANCELADA]) && !isset($updateData['manager_id'])) {
            $updateData['manager_id'] = Auth::id();
        }

        // Se for CANCELAMENTO, precisa de motivo (embora a rota /cancelar seja a principal)
        if ($newStatus === Reserva::STATUS_CANCELADA) {
            $request->validate(['cancellation_reason' => 'nullable|string|min:5']);
            $updateData['cancellation_reason'] = $request->input('cancellation_reason') ?? 'Cancelado via tela de status (Motivo não fornecido).';

            // AÇÃO CRÍTICA: Se for CANCELADA via esta rota, redireciona para o Dashboard (o fluxo ideal é pelo modal)
            return redirect()->route('dashboard')->with('warning', 'Reserva marcada como cancelada. Use o modal de cancelamento na lista/calendário para liberar o slot.');
        }

        try {
            $reserva->update($updateData);

            // DEFESA: Força a recarga do usuário autenticado após o update
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.reservas.show', $reserva)
                             ->with('success', "Status da reserva alterado para '{$newStatus}' com sucesso.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao atualizar o status da reserva: ' . $e->getMessage());
        }
    }

    public function destroyReserva(Reserva $reserva)
    {
        // Impede a exclusão direta de reservas recorrentes.
        if ($reserva->is_recurrent) {
            return back()->with('warning', 'Esta reserva faz parte de uma série recorrente. Use a opção "Cancelar Apenas Este Dia" ou "Cancelar Série Inteira" na tela de detalhes/calendário para gerenciar.');
        }

        try {
            $name = $reserva->client_name;
            $reserva->delete();

            // DEFESA: Força a recarga do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.reservas.index')
                             ->with('success', "Reserva de $name excluída permanentemente com sucesso.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir a reserva: ' . $e->getMessage());
        }
    }

    // --- Métodos de CRUD de Usuários ---

    /**
     * Lista usuários com filtro por 'role'.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function indexUsers(Request $request)
    {
        // 1. Define o filtro e a query base
        $roleFilter = $request->get('role_filter');
        $query = User::orderBy('name', 'asc');

        $activeFilter = null;
        $pageTitle = 'Usuários Cadastrados';

        // 2. Aplica a lógica de filtro de forma condicional
        if ($roleFilter === 'cliente') {
            $query->where('role', 'cliente');
            $pageTitle = 'Clientes Cadastrados';
            $activeFilter = 'cliente';
        } elseif ($roleFilter === 'gestor') {
            // Inclui Gestores e Administradores
            $query->whereIn('role', ['gestor', 'admin']);
            $pageTitle = 'Gestores e Administradores';
            $activeFilter = 'gestor';
        } else {
            // Caso 'TODOS' ou parâmetro ausente. Não aplica WHERE para listar todos.
            // A query base ($query) já retorna todos os usuários.
            $pageTitle = 'Todos os Usuários Cadastrados';
            $activeFilter = 'all'; // Define um valor para o botão 'Todos' ficar ativo no Blade
        }

        // 3. Executa a query com paginação
        $users = $query->paginate(20);

        // 4. Retorna a view com os dados
        return view('admin.users.index', [
            'users' => $users,
            'pageTitle' => $pageTitle,
            'roleFilter' => $activeFilter, // Passa o filtro ativo para o Blade
        ]);
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    /**
     * Lida com a submissão do formulário para criar um novo Gestor/Admin ou Cliente.
     * FIX CRÍTICO: Agora gera senha aleatória se 'password' for nulo para clientes.
     */
    public function storeUser(Request $request)
    {
        // 1. Log para diagnóstico
        Log::info('Tentativa de cadastro de usuário. Dados recebidos: ', $request->all());

        // Define se é Gestor/Admin ou Cliente
        $role = $request->input('role', 'cliente');
        $isGestorOrAdmin = in_array($role, ['gestor', 'admin']);

        // 1. Definição das Regras de Validação CONDICIONAL
        $rules = [
            'name' => 'required|string|max:255',
            // O email precisa ser único para a criação
            'email' => 'required|string|email|max:255|unique:users',
            // Permite 'admin' pois é uma rota de gestão
            'role' => ['required', 'string', Rule::in(['cliente', 'gestor', 'admin'])],
            // **CORREÇÃO:** Adicionado 'unique:users' para o contato do WhatsApp.
            'whatsapp_contact' => 'nullable|string|max:20|unique:users',
            'data_nascimento' => 'nullable|date',
        ];

        if ($isGestorOrAdmin) {
            // Senha OBRIGATÓRIA apenas para Gestor/Admin
            $rules['password'] = 'required|string|confirmed|min:8';
            $rules['password_confirmation'] = 'required'; // Garante que a confirmação foi enviada
        } else {
            // Senha e confirmação são opcionais/não necessárias para Cliente
            $rules['password'] = 'nullable';
            $rules['password_confirmation'] = 'nullable';
        }

        // Validação - Se falhar, redireciona de volta automaticamente.
        // O Laravel agora exibirá um erro de validação se o email ou o whatsapp já existirem.
        $validatedData = $request->validate($rules);

        try {
            // Define a senha a ser salva
            $passwordToSave = null;
            if ($request->filled('password')) {
                // Se o campo password foi preenchido, usa o valor fornecido (hash)
                $passwordToSave = Hash::make($validatedData['password']);
            } elseif ($role === 'cliente') {
                // SE o usuário é cliente E não forneceu senha (o que é esperado),
                // geramos uma senha aleatória e segura para satisfazer a restrição NOT NULL do DB.
                $passwordToSave = Hash::make(Str::random(16));
                Log::info('Gerando senha aleatória para cliente: ' . $validatedData['email']);
            }

            // 2. Criação
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'whatsapp_contact' => $validatedData['whatsapp_contact'] ?? null,
                'data_nascimento' => $validatedData['data_nascimento'] ?? null,
                // Usa o valor tratado acima
                'password' => $passwordToSave,
                'role' => $role,
            ]);

            // 3. Sucesso e Redirecionamento
            return redirect()->route('admin.users.index')->with('success', 'O usuário ' . $user->name . ' ('.$role.') foi criado com sucesso!');

        } catch (\Exception $e) {
            // 4. Captura de Erros e Log
            Log::error('Erro ao criar usuário via Admin: ' . $e->getMessage());
            // **Se o erro persistir, o problema pode ser que a sua view não está exibindo os erros de validação.**
            return redirect()->back()->withInput()->with('error', 'Erro inesperado ao criar o usuário. Verifique o log do sistema.');
        }
    }

// -------------------------------------------------------------------------
// 🛠️ MÉTODOS DE EDIÇÃO E EXCLUSÃO DE USUÁRIOS
// -------------------------------------------------------------------------

    /**
     * Exibe o formulário para edição de um usuário específico.
     * @param User $user O modelo de usuário a ser editado (Route Model Binding).
     */
    public function editUser(User $user)
    {
        // Regra de segurança: Gestores não podem editar o próprio 'admin'
        if ($user->role === 'admin' && Auth::user()->role !== 'admin') {
            return redirect()->route('admin.users.index')
                ->with('error', 'Você não tem permissão para editar usuários Administradores.');
        }

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Processa a atualização de um usuário.
     * @param Request $request
     * @param User $user O modelo de usuário a ser atualizado.
     */
    public function updateUser(Request $request, User $user)
    {
        // 1. Regras de Validação
        $rules = [
            'name' => 'required|string|max:255',
            // O email deve ser único, exceto para o usuário atual
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(['cliente', 'gestor', 'admin'])],

            // Campos Adicionais
            'whatsapp_contact' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id, 'whatsapp_contact')], // UNIQUE com IGNORE ID
            'data_nascimento' => 'nullable|date|before:today',

            // Senha é opcional, mas se preenchida, deve ter pelo menos 8 caracteres e ser confirmada
            'password' => 'nullable|string|min:8|confirmed',
        ];

        $request->validate($rules);

        // 2. Garante Permissão para Alterar Role 'admin'
        // Se o usuário logado não for admin, ele não pode definir a role como 'admin'
        if (Auth::user()->role !== 'admin' && $request->role === 'admin') {
             return back()->withInput()->withErrors(['role' => 'Apenas Administradores podem definir um usuário como Administrador.']);
        }

        // Impede que um gestor altere um admin para outra função
        if (Auth::user()->role !== 'admin' && $user->role === 'admin' && $request->role !== 'admin') {
             return back()->withInput()->withErrors(['role' => 'Você não tem permissão para rebaixar um Administrador.']);
        }


        // 3. Atualização dos Dados
        $data = $request->only('name', 'email', 'role', 'whatsapp_contact', 'data_nascimento');

        // Se uma nova senha foi fornecida, hash e adicione aos dados
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // DEFESA: Força a recarga do usuário autenticado caso ele tenha alterado a própria role
        if (Auth::check()) {
            Auth::user()->fresh();
        }

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Remove um usuário do sistema.
     * @param User $user O modelo de usuário a ser excluído.
     */
    public function destroyUser(User $user)
    {
        // Regra de segurança 1: O usuário não pode excluir a si mesmo
        if (Auth::user()->id === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Você não pode excluir a si mesmo.');
        }

        // Regra de segurança 2: Apenas administradores podem excluir outros administradores
        if ($user->role === 'admin' && Auth::user()->role !== 'admin') {
             return redirect()->route('admin.users.index')
                 ->with('error', 'Você não tem permissão para excluir um usuário Administrador.');
        }

        try {
            $name = $user->name;
            $user->delete();

            // DEFESA: Força a recarga do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.users.index')
                ->with('success', "Usuário '$name' excluído com sucesso.");
        } catch (\Exception $e) {
            Log::error("Erro ao excluir o usuário {$user->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao excluir o usuário: ' . $e->getMessage());
        }
    }
}
