<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Reserva;
use App\Http\Requests\StoreReservaRequest;
use App\Http\Requests\UpdateReservaStatusRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReservaController extends Controller
{
    /**
     * Mapeamento dos dias da semana para exibição.
     */
    protected $dayNames = [
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
        7 => 'Domingo', // dayOfWeekIso começa em 1 (Segunda)
    ];

    /**
     * Exibe a grade de horários disponíveis. (Método index existente)
     */
    public function index()
    {
        // Define o período de cálculo (próximas 2 semanas)
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addWeeks(2);

        // ====================================================================
        // NOVO PASSO 1: Ocupações por Reservas Fixas (Anulam a recorrência)
        // Busca todas as reservas de cliente marcadas como fixas e ativas.
        // A chave de exclusão será 'day_of_week-start_time-end_time'.
        // ====================================================================
        $fixedReservaSlots = Reserva::where('is_fixed', true)
                                   ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                   ->select('day_of_week', 'start_time', 'end_time')
                                   ->get();

        // Mapeia os slots fixos reservados para fácil exclusão
        $fixedReservaMap = $fixedReservaSlots->map(function ($reserva) {
            // Cria uma chave única baseada no dia e horário da reserva fixa
            return "{$reserva->day_of_week}-{$reserva->start_time}-{$reserva->end_time}";
        })->toArray();
        // ====================================================================

        // 1. HORÁRIOS RECORRENTES FIXOS (Disponibilidade do Admin)
        $recurringSchedules = Schedule::whereNotNull('day_of_week')
                                    ->whereNull('date')
                                    ->where('is_active', true)
                                    ->orderBy('day_of_week')
                                    ->orderBy('start_time')
                                    ->get();

        // ====================================================================
        // NOVO PASSO 2: FILTRA SLOTS RECORRENTES ANULADOS POR RESERVAS FIXAS
        // Remove da lista de schedules recorrentes tudo o que está em $fixedReservaMap.
        // ====================================================================
        $recurringSchedules = $recurringSchedules->filter(function ($schedule) use ($fixedReservaMap) {
            $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
            // Retorna TRUE (mantém o slot) se a chave NÃO estiver no mapa de reservas fixas
            return !in_array($scheduleKey, $fixedReservaMap);
        });
        // ====================================================================


        // 2. HORÁRIOS AVULSOS: Onde date é definido e está dentro do período.
        $adHocSchedules = Schedule::whereNotNull('date')
                                 ->where('is_active', true)
                                 ->where('date', '>=', $startDate->toDateString())
                                 ->where('date', '<=', $endDate->toDateString())
                                 ->orderBy('start_time')
                                 ->get();

        // === CORREÇÃO CRÍTICA: RETORNA AGENDA VAZIA SE NÃO HOUVER REGISTROS ===
        if ($recurringSchedules->isEmpty() && $adHocSchedules->isEmpty()) {
            $dayNames = $this->dayNames;
            return view('reserva.index', ['weeklySchedule' => [], 'dayNames' => $dayNames]);
        }
        // ====================================================================

        // 3. RESERVAS PONTUAIS QUE OCUPAM O SLOT: (Reservas que não são fixas)
        // Busca reservas pontuais, mas também as reservas que conflitam com schedules recorrentes
        $occupiedSlots = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                 ->where('is_fixed', false) // Exclui reservas fixas (já tratadas acima)
                                 ->where('date', '>=', Carbon::today()->toDateString())
                                 ->get();

        // Mapeia os slots ocupados para fácil verificação (chave: 'Y-m-d H:i')
        // NOTE: Esta verificação é simplificada e não cobre overlaps, mas é usada
        // apenas para excluir slots do Schedule, não para a validação final.
        $occupiedMap = $occupiedSlots->mapWithKeys(function ($reserva) {
            // A chave aqui usa a data exata e o horário
            $dateTime = Carbon::parse($reserva->date . ' ' . $reserva->start_time)->format('Y-m-d H:i');
            return [$dateTime => true];
        })->toArray();

        // 4. CALCULA O CRONOGRAMA SEMANAL (próximas 2 semanas)
        $weeklySchedule = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $currentDateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeekIso; // 1 (Seg) a 7 (Dom)

            // A) Horários Recorrentes para este dia da semana (JÁ FILTRADOS contra Reservas Fixas)
            $dayRecurringSlots = $recurringSchedules->where('day_of_week', $dayOfWeek);

            // B) Horários Avulsos Específicos para esta data
            $dayAdHocSlots = $adHocSchedules->where('date', $currentDateString);

            // C) Combina e ordena os dois tipos de horários para o dia
            $combinedSchedules = $dayRecurringSlots->merge($dayAdHocSlots)->sortBy('start_time');

            foreach ($combinedSchedules as $schedule) {
                $startTime = Carbon::parse($schedule->start_time);
                $endTime = Carbon::parse($schedule->end_time);

                // Ignorar horários que já passaram hoje
                if ($date->isToday() && $startTime->lt(Carbon::now())) {
                    continue;
                }

                $slotDateTime = $date->copy()->setTime($startTime->hour, $startTime->minute);
                $slotKey = $slotDateTime->format('Y-m-d H:i');

                // Verifica se o slot está livre de RESERVAS PONTUAIS (simples, apenas por hora de início)
                if (!isset($occupiedMap[$slotKey])) {
                    // Define o tipo de slot (Avulso se tiver 'date', Recorrente se tiver 'day_of_week' e não tiver 'date')
                    $slotType = $schedule->date ? 'Avulso' : 'Recorrente';

                    $weeklySchedule[$currentDateString][] = [
                        'start_time' => $startTime->format('H:i'),
                        'end_time' 	=> $endTime->format('H:i'),
                        'price' => $schedule->price,
                        'schedule_id' => $schedule->id,
                        'type' => $slotType, // Adiciona o tipo de slot
                    ];
                }
            }

            // Ordena o array final de slots do dia por hora de início
            if (isset($weeklySchedule[$currentDateString])) {
                usort($weeklySchedule[$currentDateString], function($a, $b) {
                    return strcmp($a['start_time'], $b['start_time']);
                });
            }
        }

        $dayNames = $this->dayNames;

        return view('reserva.index', compact('weeklySchedule', 'dayNames'));
    }

    /**
     * Exibe o formulário de criação no painel Admin,
     * fornecendo a lista de datas disponíveis para o JavaScript.
     */
    public function create()
    {
        // 1. DADOS DE DISPONIBILIDADE RECORRENTE (Schedule - Reservas Fixas)

        // a) Busca todos os slots de reserva fixos e ativos (chave de exclusão)
        $fixedReservaSlots = Reserva::where('is_fixed', true)
                                   ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                   ->select('day_of_week', 'start_time', 'end_time')
                                   ->get();

        $fixedReservaMap = $fixedReservaSlots->map(function ($reserva) {
            return "{$reserva->day_of_week}-{$reserva->start_time}-{$reserva->end_time}";
        })->toArray();

        // b) Busca schedules recorrentes e remove os slots ocupados por reservas fixas
        $availableRecurringSchedules = Schedule::whereNotNull('day_of_week')
                                                ->whereNull('date')
                                                ->where('is_active', true)
                                                ->get()
                                                ->filter(function ($schedule) use ($fixedReservaMap) {
                                                    $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
                                                    return !in_array($scheduleKey, $fixedReservaMap);
                                                });

        // c) Extrai os dias da semana (dayOfWeekIso: 1 a 7) que têm pelo menos 1 slot recorrente disponível
        $availableDayOfWeeks = $availableRecurringSchedules->pluck('day_of_week')->unique()->map(fn($day) => (int)$day)->toArray();

        // 2. DADOS DE DISPONIBILIDADE AVULSA (Schedule.date)

        $hoje = Carbon::today();
        // Define um período de busca maior (ex: 180 dias) para cobrir o calendário
        $diasParaVerificar = 180;

        $adHocDates = Schedule::whereNotNull('date')
                                 ->where('is_active', true)
                                 ->where('date', '>=', $hoje->toDateString())
                                 ->where('date', '<=', $hoje->copy()->addDays($diasParaVerificar)->toDateString())
                                 ->pluck('date') // Retorna uma Collection de strings 'YYYY-MM-DD'
                                 ->unique()
                                 ->toArray();

        // 3. COMBINAÇÃO E PROJEÇÃO NO TEMPO
        $diasDisponiveisNoFuturo = [];
        $period = CarbonPeriod::create($hoje, $hoje->copy()->addDays($diasParaVerificar));

        foreach ($period as $date) {
            $currentDateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeekIso; // 1 (Seg) a 7 (Dom)

            $isRecurringAvailable = in_array($dayOfWeek, $availableDayOfWeeks);
            $isAdHocAvailable = in_array($currentDateString, $adHocDates);

            // Se for um dia recorrente disponível OU for uma data avulsa específica
            if ($isRecurringAvailable || $isAdHocAvailable) {
                // A data só é adicionada se o dia não for hoje E o horário já não tiver passado.
                $diasDisponiveisNoFuturo[] = $currentDateString;
            }
        }

        // 4. RETORNO PARA A VIEW
        // A view é a do administrador: 'admin.reservas.create'
        return view('admin.reservas.create', [
            'diasDisponiveisJson' => json_encode(array_values(array_unique($diasDisponiveisNoFuturo))),
        ]);
    }

    /**
     * Endpoint para retornar os horários disponíveis (Schedule slots) para uma data específica (AJAX).
     * Retorna apenas horários definidos que AINDA NÃO têm uma reserva idêntica.
     */
    public function getAvailableTimes(Request $request)
    {
        // 1. Validação simples da data
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = Carbon::parse($request->input('date'));
        $dateString = $date->toDateString();
        $dayOfWeek = $date->dayOfWeekIso;

        // Se a data for hoje, precisamos checar os horários que já passaram
        $isToday = $date->isToday();
        $now = Carbon::now();

        // A. Slots Fixos Ocupados por Reservas Fixas (Chave de Exclusão Recorrente)
        $fixedReservaSlots = Reserva::where('is_fixed', true)
                                   ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                   ->select('day_of_week', 'start_time', 'end_time')
                                   ->get();
        $fixedReservaMap = $fixedReservaSlots->map(function ($reserva) {
            return "{$reserva->day_of_week}-{$reserva->start_time}-{$reserva->end_time}";
        })->toArray();

        // B. Slots Definidos pelo Admin (Schedule) para esta data

        // 1. Slots Recorrentes (Filtrados)
        $recurringSchedules = Schedule::whereNotNull('day_of_week')
                                    ->whereNull('date')
                                    ->where('is_active', true)
                                    ->where('day_of_week', $dayOfWeek)
                                    ->get()
                                    ->filter(function ($schedule) use ($fixedReservaMap) {
                                        // Remove slots de Schedule que são anulados por Reservas Fixas
                                        $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
                                        return !in_array($scheduleKey, $fixedReservaMap);
                                    });

        // 2. Slots Avulsos (Específicos da Data)
        $adHocSchedules = Schedule::whereNotNull('date')
                                 ->where('is_active', true)
                                 ->where('date', $dateString)
                                 ->get();

        // 3. Combina e ordena os horários disponíveis definidos
        $allSchedules = $recurringSchedules->merge($adHocSchedules)->sortBy('start_time');


        // C. Slots Ocupados por Reservas Pontuais (Chave de Exclusão Pontual)
        $existingReservations = Reserva::where('is_fixed', false) // Apenas reservas pontuais
                                     ->whereDate('date', $dateString)
                                     ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                     ->get();

        // D. Filtra os horários disponíveis finais
        $availableTimes = $allSchedules->filter(function ($schedule) use ($existingReservations, $isToday, $now) {

            // 1. Checagem de slots passados (apenas se for hoje)
            $scheduleStartDateTime = Carbon::parse($schedule->start_time);
            if ($isToday && $scheduleStartDateTime->lt($now)) {
                return false; // Ignora horários que já passaram
            }

            // 2. Checagem de Conflito com Reservas Pontuais (occupied)
            $isBooked = $existingReservations->contains(function ($reservation) use ($schedule) {
                // Para simplificar, checa se há uma reserva que ocupa EXATAMENTE o slot definido no Schedule.
                // Uma verificação de "overlap" seria mais robusta, mas essa já garante a integridade básica
                // se você garante que os Schedules não se sobrepõem.
                return $schedule->start_time === $reservation->start_time && $schedule->end_time === $reservation->end_time;
            });

            return !$isBooked; // Retorna TRUE se o slot NÃO tiver sido reservado.
        })->map(function ($schedule) {
            // Formata os dados para o JavaScript
            return [
                'id' => $schedule->id,
                'time_slot' => Carbon::parse($schedule->start_time)->format('H:i') . ' - ' . Carbon::parse($schedule->end_time)->format('H:i'),
                'price' => number_format($schedule->price, 2, ',', '.'),
                'start_time' => Carbon::parse($schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($schedule->end_time)->format('H:i'),
                'raw_price' => $schedule->price, // Valor numérico para o campo hidden
            ];
        })->values();

        return response()->json($availableTimes);
    }

    /**
     * Salva a pré-reserva. (Método store corrigido para chaves em português)
     */
    public function store(StoreReservaRequest $request)
    {
        $validated = $request->validated();

        // Mapeamento dos nomes de campo, presumindo que o StoreReservaRequest usa estas chaves.
        $date = $validated['data_reserva']; // Corrigido
        $startTime = $validated['hora_inicio']; // Corrigido
        $endTime = $validated['hora_fim']; // Corrigido
        $clientName = $validated['nome_cliente']; // Corrigido
        $clientContact = $validated['contato_cliente']; // Corrigido
        $price = $validated['preco']; // Corrigido

        // -------------------------------------------------------------------------
        // ✅ Lógica de Sobreposição (Pontual)
        // Checa por conflito com qualquer reserva pontual (is_fixed=false)
        // -------------------------------------------------------------------------
        $conflitoPontualExistente = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->where('is_fixed', false)
            ->where('date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                // A reserva existente começa antes da nova terminar E termina depois da nova começar.
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();

        // 🚨 Tratamento de Conflito para Reservas Fixas (Recorrentes)
        if ($request->input('is_fixed', false)) {
            $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;

            // -------------------------------------------------------------------------
            // ✅ Lógica de Sobreposição (Fixa)
            // Checa por conflito com qualquer outra reserva fixa naquele dia da semana
            // -------------------------------------------------------------------------
            $conflitoFixoExistente = Reserva::where('is_fixed', true)
                ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                ->where('day_of_week', $dayOfWeek)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime)
                          ->where('end_time', '>', $startTime);
                })
                ->exists();

             if ($conflitoFixoExistente) {
                 return redirect()->route('reserva.index')->with('error', 'Desculpe, este horário fixo já está ocupado em seu dia da semana recorrente. Por favor, escolha outro.');
             }
        }

        // Verifica conflitos pontuais (após a checagem de conflito fixo)
        if ($conflitoPontualExistente) {
            return redirect()->route('reserva.index')->with('error', 'Desculpe, este horário está em conflito com uma reserva existente. Por favor, verifique a duração e escolha outro.');
        }

        // Determina o day_of_week para o registro
        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso;


        $reserva = Reserva::create([
            'date' 	        => $date,
            'start_time' 	    => $startTime,
            'end_time' 	    => $endTime,
            'client_name' 	=> $clientName,
            'client_contact' => $clientContact,
            'price' 	        => $price,
            'status' 	        => Reserva::STATUS_PENDENTE, // Usando constante
            'is_fixed' 	    => $request->input('is_fixed', false), // Adiciona is_fixed
            'day_of_week' 	=> $dayOfWeek, // Garante que o dia da semana é salvo
        ]);

        $whatsappNumber = '91985320997'; // Altere para o seu número WhatsApp

        $data = Carbon::parse($reserva->date)->format('d/m/Y');
        $hora = Carbon::parse($reserva->start_time)->format('H:i');

        $messageText = "🚨 NOVA PRÉ-RESERVA PENDENTE\n\n" .
                        "Cliente: {$reserva->client_name}\n" .
                        "Contato: {$reserva->client_contact}\n" .
                        "Data/Hora: {$data} às {$hora}\n" .
                        "Valor: R$ " . number_format($reserva->price, 2, ',', '.') . "\n" .
                        ($reserva->is_fixed ? "Tipo: HORÁRIO FIXO SEMANAL\n" : "Tipo: RESERVA PONTUAL\n");

        $whatsappLink = "https://api.whatsapp.com/send?phone={$whatsappNumber}&text=" . urlencode($messageText);

        return redirect()->route('reserva.index')
                         ->with('whatsapp_link', $whatsappLink)
                         ->with('success', 'Pré-reserva enviada! Por favor, entre em contato via WhatsApp para confirmar o agendamento.');
    }

    /**
     * Implementação do método: Atualiza o status de uma reserva existente.
     */
    public function updateStatus(UpdateReservaStatusRequest $request, Reserva $reserva)
    {
        $newStatus = $request->validated('status');
        $oldStatus = $reserva->status;

        try {
            // 1. Regra de Negócio: Não permitir alteração se o status final já foi alcançado.
            if (in_array($oldStatus, [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA])) {
                return response()->json([
                    'message' => 'O status de uma reserva cancelada ou rejeitada não pode ser alterado.',
                    'current_status' => $oldStatus
                ], 400); // 400 Bad Request
            }

            // 2. Regra de Negócio Crítica: Impedir confirmação (confirmed) se o slot já estiver ocupado.
            if ($newStatus === Reserva::STATUS_CONFIRMADA) {
                // Prepara a query base para buscar conflitos
                $query = Reserva::where('id', '!=', $reserva->id) // Exclui a reserva atual
                    ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                    ->where(function ($q) use ($reserva) {
                        // ✅ Lógica de sobreposição robusta para a duração da $reserva atual
                        $q->where('start_time', '<', $reserva->end_time)
                          ->where('end_time', '>', $reserva->start_time);
                    });


                // Se for reserva fixa, checa por conflito recorrente (pelo dia da semana).
                if ($reserva->is_fixed) {
                    $query->where('is_fixed', true)
                          ->where('day_of_week', $reserva->day_of_week);
                }
                // Se for reserva pontual, checa por conflito pontual (pela data).
                else {
                    $query->where('is_fixed', false)
                          ->where('date', $reserva->date);
                }

                if ($query->exists()) {
                    return response()->json([
                        'message' => 'Não foi possível confirmar. O horário já está ocupado por outra reserva Pendente/Confirmada.',
                    ], 409); // 409 Conflict
                }
            }

            // 3. Atualiza o status no banco de dados
            $reserva->status = $newStatus;
            $reserva->save();

            // Retorno de sucesso
            return response()->json([
                'message' => "Status da reserva #{$reserva->id} alterado de '{$oldStatus}' para '{$newStatus}' com sucesso.",
                'reserva' => $reserva
            ], 200);

        } catch (\Exception $e) {
            \Log::error("Erro ao atualizar status da reserva {$reserva->id}: " . $e->getMessage());

            return response()->json([
                'message' => 'Ocorreu um erro interno ao tentar atualizar o status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
