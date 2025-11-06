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
     * CRÍTICO: Corrigido para 0 (Domingo) a 6 (Sábado), consistente com Carbon::dayOfWeek e a convenção do DB.
     */
    protected $dayNames = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    /**
     * Checa se o horário de uma nova reserva entra em conflito com reservas existentes.
     *
     * @param string $date Data da reserva (YYYY-MM-DD).
     * @param string $startTime Hora de início (HH:MM:SS).
     * @param string $endTime Hora de fim (HH:MM:SS).
     * @param bool $isFixed Se a reserva é fixa (recorrente).
     * @param int|null $ignoreReservaId ID da reserva a ser ignorada na checagem (útil para o update).
     * @return bool True se houver conflito, False caso contrário.
     */
    protected function checkOverlap(string $date, string $startTime, string $endTime, bool $isFixed, ?int $ignoreReservaId = null): bool
    {
        // CORREÇÃO: Usando dayOfWeek (0-6)
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // 1. Query base para sobreposição de tempo
        $baseQuery = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->when($ignoreReservaId, function ($query) use ($ignoreReservaId) {
                return $query->where('id', '!=', $ignoreReservaId); // Exclui a reserva atual se for um update
            })
            ->where(function ($query) use ($startTime, $endTime) {
                // Checagem de overlap robusta: (Existente Inicia Antes da Nova Terminar) AND (Existente Termina Depois da Nova Começar)
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });

        if ($isFixed) {
            // Se a nova reserva é FIXA, só precisamos checar conflito com outras reservas FIXAS
            // no mesmo dia da semana (a data é irrelevante para a recorrência fixa).
            return (clone $baseQuery)
                ->where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->exists();

        } else {
            // Se a nova reserva é PONTUAL, ela deve checar conflito contra dois grupos:

            // 1. Outras reservas PONTUAIS na mesma data.
            $conflitoPontual = (clone $baseQuery)
                ->where('is_fixed', false)
                ->where('date', $date)
                ->exists();

            if ($conflitoPontual) {
                return true;
            }

            // 2. QUALQUER reserva FIXA que caia no mesmo dia da semana e horário (CORREÇÃO CRÍTICA).
            // Isto impede que uma reserva pontual ocupe o slot de uma reserva fixa.
            $conflitoComFixo = (clone $baseQuery)
                ->where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->exists();

            return $conflitoComFixo;
        }
    }

    /**
     * Exibe a grade de horários disponíveis. (Método index existente)
     */
    public function index()
    {
        // Define o período de cálculo (próximas 2 semanas)
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addWeeks(2);

        // ====================================================================
        // PASSO 1: Ocupações por Reservas Fixas (Anulam a recorrência do Schedule)
        // Busca todas as reservas de cliente marcadas como fixas e ativas.
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
        // PASSO 2: FILTRA SLOTS RECORRENTES ANULADOS POR RESERVAS FIXAS
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

        // === RETORNA AGENDA VAZIA SE NÃO HOVER REGISTROS ===
        if ($recurringSchedules->isEmpty() && $adHocSchedules->isEmpty()) {
            $dayNames = $this->dayNames;
            return view('reserva.index', ['weeklySchedule' => [], 'dayNames' => $dayNames]);
        }
        // ====================================================================

        // 3. RESERVAS PONTUAIS QUE OCUPAM O SLOT: (Reservas que não são fixas)
        $occupiedSlots = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                               ->where('is_fixed', false) // Exclui reservas fixas (já tratadas acima)
                               ->where('date', '>=', Carbon::today()->toDateString())
                               ->get();

        // Mapeia os slots ocupados para fácil verificação (chave: 'Y-m-d H:i')
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
            // CORREÇÃO: Usando dayOfWeek (0-6)
            $dayOfWeek = $date->dayOfWeek; // 0 (Dom) a 6 (Sáb)

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
                        'end_time'  => $endTime->format('H:i'),
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
     * fornecendo a lista de datas disponíveis para validação no JavaScript.
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
                                                 // Remove slots de Schedule que são anulados por Reservas Fixas
                                                 $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
                                                 return !in_array($scheduleKey, $fixedReservaMap);
                                             });

        // c) Extrai os dias da semana (dayOfWeek: 0 a 6) que têm pelo menos 1 slot recorrente disponível
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
            // CORREÇÃO: Usando dayOfWeek (0-6)
            $dayOfWeek = $date->dayOfWeek; // 0 (Dom) a 6 (Sáb)

            $isRecurringAvailable = in_array($dayOfWeek, $availableDayOfWeeks);
            $isAdHocAvailable = in_array($currentDateString, $adHocDates);

            // Se for um dia recorrente disponível OU for uma data avulsa específica
            if ($isRecurringAvailable || $isAdHocAvailable) {
                // A data só é adicionada se for hoje E já não tiver passado (que é tratado no getAvailableTimes)
                // Aqui apenas filtramos se há qualquer disponibilidade
                $diasDisponiveisNoFuturo[] = $currentDateString;
            }
        }

        // =================================================================
        // >>> DEBUG CRÍTICO: SE ESTA LINHA EXECUTAR, O DEBUG VAI APARECER!
        // =================================================================
        dd([
            'diasDisponiveisNoFuturo_COUNT' => count($diasDisponiveisNoFuturo),
            'diasDisponiveisNoFuturo_SAMPLE' => array_slice($diasDisponiveisNoFuturo, 0, 10),
            'availableDayOfWeeks_RECORRENTES' => $availableDayOfWeeks,
            'adHocDates_AVULSOS_DB' => $adHocDates,
        ]);
        // =================================================================


        // 4. RETORNO PARA A VIEW
        return view('admin.reservas.create', [
            'diasDisponiveisJson' => array_values(array_unique($diasDisponiveisNoFuturo)),
        ]);
    }

    /**
     * Endpoint para retornar os horários disponíveis (Schedule slots) para uma data específica (AJAX).
     */
    public function getAvailableTimes(Request $request)
    {
        // 1. Validação simples da data
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = Carbon::parse($request->input('date'));
        $dateString = $date->toDateString();
        // CORREÇÃO: Usando dayOfWeek (0-6)
        $dayOfWeek = $date->dayOfWeek; // 0 (Dom) a 6 (Sáb)

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
            if ($isToday && $scheduleStartDateTime->lt(Carbon::now())) {
                return false; // Ignora horários que já passaram
            }

            // 2. Checagem de Conflito com Reservas Pontuais (occupied)
            $isBooked = $existingReservations->contains(function ($reservation) use ($schedule) {
                // Checa se há uma reserva que ocupa EXATAMENTE o slot definido no Schedule.
                // Isso funciona pois o Schedule define slots discretos.
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
     * Salva a pré-reserva. (Método store refatorado para usar checkOverlap)
     */
    public function store(StoreReservaRequest $request)
    {
        $validated = $request->validated();

        // Mapeamento dos nomes de campo (mantendo as chaves em português, se o Request as usa)
        $date = $validated['data_reserva'];
        $startTime = $validated['hora_inicio'];
        $endTime = $validated['hora_fim'];
        $clientName = $validated['nome_cliente'];
        $clientContact = $validated['contato_cliente'];
        $price = $validated['preco'];
        $isFixed = $request->input('is_fixed', false);

        // ✅ Checagem unificada de conflito
        if ($this->checkOverlap($date, $startTime, $endTime, $isFixed)) {
            $message = $isFixed
                ? 'Desculpe, este horário fixo já está ocupado por outra reserva fixa no dia da semana. Por favor, escolha outro.'
                : 'Desculpe, este horário está em conflito com uma reserva existente (pontual ou fixa). Por favor, verifique a duração e escolha outro.';

            return redirect()->route('reserva.index')->with('error', $message);
        }

        // Determina o day_of_week para o registro
        // CORREÇÃO: Usando dayOfWeek (0-6)
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;


        $reserva = Reserva::create([
            'date'           => $date,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'client_name'    => $clientName,
            'client_contact' => $clientContact,
            'price'          => $price,
            'status'         => Reserva::STATUS_PENDENTE, // Usando constante
            'is_fixed'       => $isFixed, // Adiciona is_fixed
            'day_of_week'    => $dayOfWeek, // Garante que o dia da semana é salvo
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
     * Refatorado para usar o checkOverlap, garantindo integridade ao confirmar.
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
                // Checa conflito contra TODAS as outras reservas ativas, ignorando a própria reserva.
                $date = $reserva->date;
                $startTime = $reserva->start_time;
                $endTime = $reserva->end_time;
                $isFixed = $reserva->is_fixed;
                $ignoreId = $reserva->id;

                if ($this->checkOverlap($date, $startTime, $endTime, $isFixed, $ignoreId)) {
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
