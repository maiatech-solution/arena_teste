<?php

namespace App\Http\Controllers;

// Imports do seu código original
use App\Models\User;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

// 💡 IMPORTS ADICIONADOS/CORRIGIDOS
use App\Models\Horario;     // ⬅️ CORREÇÃO: Usar o modelo Horario.php
use Carbon\CarbonPeriod;     // Necessário para o loop de datas
use Illuminate\Support\Carbon; // Corrigido para o namespace correto do Carbon

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

            // Verificação de segurança para dados corrompidos
            if (!isset($reserva->getAttributes()['date']) || !$reserva->start_time) {
                continue; // Pula a reserva se dados essenciais faltarem
            }

            // CORREÇÃO CRÍTICA DA DATA: Acessa o atributo de forma bruta.
            $bookingDate = $reserva->getAttributes()['date'];
            $startDateTimeString = $bookingDate . ' ' . $reserva->start_time;

            // CORREÇÃO CRÍTICA DO TIMEZONE: Usamos o timezone da aplicação (config/app.php).
            $start = Carbon::parse($startDateTimeString);

            if ($reserva->end_time) {
                $endDateTimeString = $bookingDate . ' ' . $reserva->end_time;
                $end = Carbon::parse($endDateTimeString);
            } else {
                $end = $start->copy()->addHour();
            }

            // CORRIGIDO: Usa optional() para lidar com $reserva->user nulo.
            $userName = optional($reserva->user)->name;
            $clientName = $userName ?? $reserva->client_name ?? 'Cliente Desconhecido';

            $title = 'Reservado: ' . $clientName;

            if (isset($reserva->price)) {
                $title .= ' - R$ ' . number_format($reserva->price, 2, ',', '.');
            }

            $events[] = [
                'id' => $reserva->id,
                'title' => $title,
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
        $reservas = Reserva::where('status', Reserva::STATUS_PENDENTE)
                            ->with('user')
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);

        $pageTitle = 'Pré-Reservas Pendentes';

        return view('admin.reservas.index', compact('reservas', 'pageTitle'));
    }

    /**
     * Exibe a lista de todas as reservas confirmadas.
     */
    public function confirmed_index(Request $request)
    {
        $query = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                            ->with('user');

        $isOnlyMine = $request->get('only_mine') === 'true';

        if ($isOnlyMine) {
            $query->where('manager_id', auth()->id());
            $pageTitle = 'Minhas Reservas Manuais Confirmadas';
        } else {
            $pageTitle = 'Todas as Reservas Confirmadas';
        }

        $reservas = $query->orderBy('date', 'desc')
                            ->orderBy('start_time', 'asc')
                            ->paginate(15);

        return view('admin.reservas.confirmed_index', compact('reservas', 'pageTitle', 'isOnlyMine'));
    }

    /**
     * Confirma uma reserva (muda status para 'confirmed').
     */
    public function confirmarReserva(Reserva $reserva)
    {
        try {
            // 1. Verificação de Conflito
            $start_time_carbon = Carbon::parse($reserva->date . ' ' . $reserva->start_time);
            $end_time_carbon = Carbon::parse($reserva->date . ' ' . $reserva->end_time);

            $isConflict = Reserva::where('id', '!=', $reserva->id)
                                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA])
                                    ->where('date', $start_time_carbon->toDateString())
                                    ->where(function ($q) use ($start_time_carbon, $end_time_carbon) {
                                        $q->where('start_time', '<', $end_time_carbon->toTimeString())
                                          ->where('end_time', '>', $start_time_carbon->toTimeString());
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
     * Gera uma série de reservas recorrentes (Horário Fixo) para um cliente.
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
        $dayOfWeek = $startDate->dayOfWeek; // O dia da semana da primeira reserva (0=Dom a 6=Sáb)

        $recurrentSeriesId = now()->timestamp . $validated['user_id'];
        $reservasCriadas = 0;
        $conflitos = 0;

        // 2. Loop Semanal para Gerar as Reservas
        $currentDate = $startDate->copy();

        while ($currentDate->lessThanOrEqualTo($endDate)) {

            if ($currentDate->dayOfWeek === $dayOfWeek) {

                // 3. Verificação de Conflito
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
                        // Adicionando o dia da semana (0-6)
                        'day_of_week' => $dayOfWeek,
                    ]);
                    $reservasCriadas++;
                } else {
                    $conflitos++;
                }
            }
            $currentDate->addWeek();
        }

        // 5. Retorno ao Gestor
        $message = "Série de horários fixos criada. Total de reservas geradas: {$reservasCriadas}.";
        if ($conflitos > 0) {
            $message .= " Atenção: {$conflitos} datas foram puladas devido a conflitos de horário.";
        }

        return redirect()->route('admin.reservas.confirmed_index')->with('success', $message);
    }

    // =================================================================
    // 💡 MÉTODOS DE CRIAÇÃO MANUAL DE RESERVA (GESTOR) - CORRIGIDOS
    // =================================================================

    /**
     * Exibe o formulário para o gestor criar uma reserva manual.
     * 💡 CORREÇÃO: Adicionada a lógica para buscar e injetar os dias disponíveis.
     */
    public function createReserva()
    {
        // 1. DADOS DE DISPONIBILIDADE RECORRENTE (Schedule - Reservas Fixas)
        // (Usando convenção 0=Dom a 6=Sáb)

        // a) Busca todos os slots de reserva fixos e ativos (chave de exclusão)
        $fixedReservaSlots = Reserva::where('is_fixed', true)
                                   ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                   ->select('day_of_week', 'start_time', 'end_time')
                                   ->get();

        $fixedReservaMap = $fixedReservaSlots->map(function ($reserva) {
            // Garante que a chave use o padrão 0-6
            return "{$reserva->day_of_week}-{$reserva->start_time}-{$reserva->end_time}";
        })->toArray();

        // b) Busca schedules recorrentes e remove os slots ocupados por reservas fixas
        $availableRecurringSchedules = Horario::whereNotNull('day_of_week') // ⬅️ CORREÇÃO: Usar Horario
                                             ->whereNull('date')
                                             ->where('is_active', true)
                                             ->get()
                                             ->filter(function ($schedule) use ($fixedReservaMap) {
                                                 // Garante que a chave de checagem use o padrão 0-6
                                                 $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
                                                 return !in_array($scheduleKey, $fixedReservaMap);
                                             });

        // c) Extrai os dias da semana (dayOfWeek: 0 a 6)
        $availableDayOfWeeks = $availableRecurringSchedules->pluck('day_of_week')->unique()->map(fn($day) => (int)$day)->toArray();

        // 2. DADOS DE DISPONIBILIDADE AVULSA (Schedule.date)
        $hoje = Carbon::today();
        $diasParaVerificar = 180; // Período de busca

        $adHocDates = Horario::whereNotNull('date') // ⬅️ CORREÇÃO: Usar Horario
                             ->where('is_active', true)
                             // 💡 FIX: Comparando Objeto Carbon (do DB) com Objeto Carbon ($hoje)
                             ->where('date', '>=', $hoje)
                             ->where('date', '<=', $hoje->copy()->addDays($diasParaVerificar))
                             ->pluck('date') // Retorna Objetos Carbon
                             // 💡 FIX: Mapeia os Objetos Carbon para Strings (YYYY-MM-DD)
                             ->map(fn($date) => $date->toDateString())
                             ->unique()
                             ->toArray();

        // 3. COMBINAÇÃO E PROJEÇÃO NO TEMPO
        $diasDisponiveisNoFuturo = [];
        $period = CarbonPeriod::create($hoje, $hoje->copy()->addDays($diasParaVerificar));

        foreach ($period as $date) {
            $currentDateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeek; // 0 (Dom) a 6 (Sáb)

            $isRecurringAvailable = in_array($dayOfWeek, $availableDayOfWeeks);
            $isAdHocAvailable = in_array($currentDateString, $adHocDates);

            if ($isRecurringAvailable || $isAdHocAvailable) {
                $diasDisponiveisNoFuturo[] = $currentDateString;
            }
        }

        // 4. RETORNO PARA A VIEW
        // 💡 CORREÇÃO: Passa a variável $diasDisponiveisJson para a view.
        return view('admin.reservas.create', [
            'diasDisponiveisJson' => json_encode(array_values(array_unique($diasDisponiveisNoFuturo))),
        ]);
    }

    /**
     * Armazena uma nova reserva criada manualmente pelo gestor.
     * 💡 CORREÇÃO: Alinhado os nomes dos campos da validação com o formulário
     * (ex: 'nome_cliente' -> 'client_name').
     */
    public function storeReserva(Request $request)
    {
        // 💡 CORREÇÃO: Nomes dos campos alinhados com o formulário (create.blade.php)
        $data = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|string|max:255',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0', // 💡 Preço agora é obrigatório vindo do form
            'schedule_id' => 'required|exists:schedules,id', // 💡 ID do horário
            'notes' => 'nullable|string|max:500', // 💡 Campo de observações
        ], [
            'date.after_or_equal' => 'A data da reserva deve ser hoje ou uma data futura.',
            'end_time.after' => 'A hora de fim deve ser depois da hora de início.',
        ]);

        // 1. Prepara os dados de data/hora
        $date = $data['date'];
        $startTime = $data['start_time'];
        $endTime = $data['end_time'];

        // 2. VERIFICAÇÃO CRUCIAL DE CONFLITO
        $overlap = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->where('date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })->exists();

        if ($overlap) {
            return back()->withInput()->with('error', 'O horário selecionado já está reservado (confirmado) ou em pré-reserva (pendente) para esta data. Por favor, escolha outro slot.');
        }

        // 3. CRIAÇÃO E CONFIRMAÇÃO IMEDIATA
        Reserva::create([
            'client_name' => $data['client_name'],
            'client_contact' => $data['client_contact'],
            'price' => $data['price'], // 💡 Salva o preço vindo do formulário
            'notes' => $data['notes'] ?? 'Reserva criada manualmente pelo gestor.', // 💡 Salva as notas
            'schedule_id' => $data['schedule_id'], // 💡 Salva o ID do horário

            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,

            'manager_id' => auth()->id(), // Associa ao Gestor logado
            'user_id' => null, // Cliente não autenticado
            'status' => Reserva::STATUS_CONFIRMADA, // Confirmada na hora
            'is_fixed' => false, // Manual é pontual
            'day_of_week' => Carbon::parse($date)->dayOfWeek, // Salva o dia (0-6)
        ]);

        return redirect()->route('admin.reservas.confirmed_index')->with('success', 'Reserva manual criada e confirmada com sucesso para ' . $data['client_name'] . '!');
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
