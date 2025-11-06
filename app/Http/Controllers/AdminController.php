<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    /**
     * Exibe o dashboard principal do gestor.
     */
    public function dashboard()
    {
        // 1. Buscar todas as reservas confirmadas
        $reservas = Reserva::where('status', Reserva::STATUS_CONFIRMADA) // Usando constante
                            ->with('user')
                            ->get()
                            // CRÍTICO: Filtra quaisquer itens nulos/corrompidos na coleção antes do loop.
                            ->filter();

        // 2. Formatar as reservas para o FullCalendar
        $events = [];
        foreach ($reservas as $reserva) {

            // CORREÇÃO CRÍTICA DA DATA: Acessa o atributo de forma bruta.
            $bookingDate = $reserva->getAttributes()['date'];

            $startDateTimeString = $bookingDate . ' ' . $reserva->start_time;

            // CORREÇÃO CRÍTICA DO TIMEZONE: Usamos o timezone da aplicação (geralmente America/Sao_Paulo).
            $start = Carbon::parse($startDateTimeString);

            if ($reserva->end_time) {
                $endDateTimeString = $bookingDate . ' ' . $reserva->end_time;
                $end = Carbon::parse($endDateTimeString);
            } else {
                $end = $start->copy()->addHour();
            }

            // CORRIGIDO: Usa optional() para lidar com $reserva->user nulo.
            // Para reservas manuais sem user_id, usa client_name/nome_cliente
            $userName = optional($reserva->user)->name;
            $clientName = $userName ?? $reserva->client_name ?? 'Cliente Desconhecido';

            $title = 'Reservado: ' . $clientName;

            if (isset($reserva->price)) {
                $title .= ' - R$ ' . number_format($reserva->price, 2, ',', '.');
            }

            $events[] = [
                'id' => $reserva->id,
                'title' => $title,
                // CRÍTICO: Usamos format('Y-m-d\TH:i:s') para gerar a string ISO 8601 com 'T'
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'backgroundColor' => '#10B981',
                'borderColor' => '#059669',
            ];
        }

        $eventsJson = json_encode($events);
        $reservasPendentesCount = Reserva::where('status', Reserva::STATUS_PENDENTE)->count(); // Usa constante

        return view('dashboard', compact('eventsJson', 'reservasPendentesCount'));
    }

    // --- Métodos de CRUD de Reservas ---

    /**
     * Exibe a lista de reservas pendentes (Pré-reservas de clientes).
     */
    public function indexReservas()
    {
        // Busca por status 'pending'
        $reservas = Reserva::where('status', Reserva::STATUS_PENDENTE)
                            ->with('user')
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);

        $pageTitle = 'Pré-Reservas Pendentes';

        return view('admin.reservas.index', compact('reservas', 'pageTitle'));
    }

    /**
     * Exibe a lista de todas as reservas confirmadas, com opção de filtrar
     * pelas reservas criadas manualmente pelo gestor logado.
     */
    public function confirmed_index(Request $request)
    {
        $query = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                        ->with('user');

        // Verifica se o parâmetro 'only_mine=true' está na URL
        $isOnlyMine = $request->get('only_mine') === 'true';

        if ($isOnlyMine) {
            // APLICA O FILTRO: Mostra APENAS as reservas criadas por este gestor
            // Para isso, o manager_id da reserva deve ser igual ao ID do gestor logado.
            $query->where('manager_id', auth()->id());
            $pageTitle = 'Minhas Reservas Manuais Confirmadas';
        } else {
            // Caso contrário, mostra TODAS as reservas confirmadas (clientes e gestores)
            $pageTitle = 'Todas as Reservas Confirmadas';
        }

        $reservas = $query->orderBy('date', 'desc')
                          ->orderBy('start_time', 'asc')
                          ->paginate(15);

        // Passa o estado do filtro para a view, permitindo que os botões mudem o estado
        return view('admin.reservas.confirmed_index', compact('reservas', 'pageTitle', 'isOnlyMine'));
    }

    /**
     * Confirma uma reserva (muda status para 'confirmed').
     */
    public function confirmarReserva(Reserva $reserva)
    {
        try {
            // 1. Verificação de Conflito antes de confirmar (Prevenção dupla)
            $start_time = Carbon::parse($reserva->date . ' ' . $reserva->start_time);
            $end_time = Carbon::parse($reserva->date . ' ' . $reserva->end_time);

            $isConflict = Reserva::where('id', '!=', $reserva->id)
                                 ->whereIn('status', [Reserva::STATUS_CONFIRMADA])
                                 ->where(function ($query) use ($start_time, $end_time) {
                                     $query->where('date', $start_time->toDateString())
                                           ->where(function ($q) use ($start_time, $end_time) {
                                               // A nova reserva começa ou termina dentro de uma reserva existente
                                               $q->where('start_time', '<', $end_time->toTimeString())
                                                 ->where('end_time', '>', $start_time->toTimeString());
                                           });
                                 })->exists();

            if ($isConflict) {
                return back()->with('error', 'Conflito detectado: Esta reserva não pode ser confirmada pois já existe outro agendamento CONFIRMADO no mesmo horário.');
            }

            // 2. Confirma a reserva
            $reserva->status = Reserva::STATUS_CONFIRMADA; // Usa constante
            $reserva->save();

            return redirect()->route('dashboard')
                                 ->with('success', 'Reserva confirmada com sucesso! O horário está agora visível no calendário.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao confirmar a reserva: ' . $e->getMessage());
        }
    }

    /**
     * Rejeita uma reserva pendente (muda status para 'rejected').
     */
    public final function rejeitarReserva(Reserva $reserva)
    {
        try {
            $reserva->status = Reserva::STATUS_REJEITADA; // Usa constante
            $reserva->save();

            return redirect()->route('admin.reservas.index')
                                 ->with('success', 'Reserva rejeitada com sucesso e removida da lista de pendentes.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao rejeitar a reserva: ' . $e->getMessage());
        }
    }

    /**
     * Cancela uma reserva (muda status para 'cancelled').
     */
    public function cancelarReserva(Reserva $reserva)
    {
        try {
            $reserva->status = Reserva::STATUS_CANCELADA; // Usa constante
            $reserva->save();

            return redirect()->route('admin.reservas.index')
                                 ->with('success', 'Reserva cancelada com sucesso.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao cancelar a reserva: ' . $e->getMessage());
        }
    }

    /**
     * 💡 NOVO MÉTODO: Gera uma série de reservas recorrentes (Horário Fixo) para um cliente.
     */
    public function makeRecurrent(Request $request)
    {
        // 1. Validação dos Dados
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $dayOfWeek = $startDate->dayOfWeek; // O dia da semana da primeira reserva (ex: 1 para Segunda)

        // Gera um ID único para agrupar todas as reservas desta série
        $recurrentSeriesId = now()->timestamp . $validated['user_id'];

        $reservasCriadas = 0;
        $conflitos = 0;

        // 2. Loop Semanal para Gerar as Reservas
        $currentDate = $startDate->copy();

        while ($currentDate->lessThanOrEqualTo($endDate)) {

            if ($currentDate->dayOfWeek === $dayOfWeek) {

                // 3. Verificação de Conflito (CRÍTICO!)
                $conflitoExistente = Reserva::where('date', $currentDate->toDateString())
                    ->where('status', Reserva::STATUS_CONFIRMADA)
                    ->where(function ($query) use ($validated) {
                        $query->where('start_time', '<', $validated['end_time'])
                              ->where('end_time', '>', $validated['start_time']);
                    })
                    ->exists();

                if (!$conflitoExistente) {
                    // 4. Criação da Reserva Recorrente
                    Reserva::create([
                        'user_id' => $validated['user_id'],
                        'schedule_id' => null,
                        'date' => $currentDate->toDateString(),
                        'start_time' => $validated['start_time'],
                        'end_time' => $validated['end_time'],
                        'price' => $validated['price'],
                        'client_name' => User::find($validated['user_id'])->name ?? 'Cliente Fixo',
                        'client_contact' => 'Recorrente',
                        'notes' => $validated['notes'],
                        'status' => Reserva::STATUS_CONFIRMADA,
                        'recurrent_series_id' => $recurrentSeriesId,
                        'is_recurrent' => true,
                        // Não é necessário manager_id aqui, pois é uma reserva de cliente
                    ]);
                    $reservasCriadas++;
                } else {
                    $conflitos++;
                }
            }

            // Move para a próxima semana
            $currentDate->addWeek();
        }

        // 5. Retorno ao Gestor
        $message = "Série de horários fixos criada. Total de reservas geradas: {$reservasCriadas}.";
        if ($conflitos > 0) {
            $message .= " Atenção: {$conflitos} datas foram puladas devido a conflitos de horário.";
        }

        return redirect()->route('admin.reservas.confirmed_index')->with('success', $message);
    }

    // ===============================================
    // 🆕 MÉTODOS DE CRIAÇÃO MANUAL DE RESERVA (GESTOR)
    // ===============================================

    /**
     * Exibe o formulário para o gestor criar uma reserva manual.
     */
    public function createReserva()
    {
        return view('admin.reservas.create');
    }

    /**
     * Armazena uma nova reserva criada manualmente pelo gestor.
     * Esta reserva é confirmada imediatamente e não requer um user_id.
     */
    public function storeReserva(Request $request)
    {
        $data = $request->validate([
            'nome_cliente' => 'required|string|max:255',
            'contato_cliente' => 'required|string|max:255', // Ex: Telefone ou E-mail
            'data_reserva' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fim' => 'required|date_format:H:i|after:hora_inicio',
        ], [
            'data_reserva.after_or_equal' => 'A data da reserva deve ser hoje ou uma data futura.',
            'hora_fim.after' => 'A hora de fim deve ser depois da hora de início.',
        ]);

        // 1. Prepara os dados de data/hora para a verificação e salvamento no BD
        $date = $data['data_reserva'];
        $startTime = $data['hora_inicio'];
        $endTime = $data['hora_fim'];

        // 2. VERIFICAÇÃO CRUCIAL DE CONFLITO
        // Busca por reservas existentes (pendentes ou confirmadas) na mesma data que se sobreponham
        $overlap = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->where('date', $date) // Filtra pela data
            ->where(function ($query) use ($startTime, $endTime) {
                // Conflito: A nova reserva começa ou termina dentro de uma reserva existente
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })->exists();

        if ($overlap) {
            return back()->withInput()->with('error', 'O horário selecionado já está reservado (confirmado) ou em pré-reserva (pendente) para esta data. Por favor, escolha outro slot.');
        }

        // 3. CRIAÇÃO E CONFIRMAÇÃO IMEDIATA
        Reserva::create([
            // Campos específicos para a criação manual:
            'client_name' => $data['nome_cliente'],
            'client_contact' => $data['contato_cliente'],
            'price' => 0.00, // Pode ser ajustado se o formulário pedir o preço
            'notes' => 'Reserva criada manualmente pelo gestor.',

            // Campos de agendamento (alinhados com o esquema do BD)
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,

            // ⭐️ NOVO: Associa a reserva ao Gestor logado
            'manager_id' => auth()->id(),

            'user_id' => null, // Cliente não autenticado
            'status' => Reserva::STATUS_CONFIRMADA, // 🔑 CHAVE: Confirmada na hora
        ]);

        return redirect()->route('admin.reservas.confirmed_index')->with('success', 'Reserva manual criada e confirmada com sucesso para ' . $data['nome_cliente'] . '!');
    }


    // --- Métodos de CRUD de Usuários ---

    /**
     * Exibe a lista de usuários.
     */
    public function indexUsers()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Exibe o formulário de criação de novo usuário.
     */
    public function createUser()
    {
        return view('admin.users.create');
    }

    /**
     * Armazena o novo usuário (exclusivo para administradores).
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'role' => ['required', 'string', Rule::in(['cliente', 'gestor'])],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso!');
    }
}
