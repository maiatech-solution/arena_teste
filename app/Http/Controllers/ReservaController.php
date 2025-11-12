<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Reserva;
use App\Http\Requests\UpdateReservaStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;

class ReservaController extends Controller
{
    /**
     * Mapeamento dos dias da semana para exibição.
     * Mapeamento: 0 (Domingo) a 6 (Sábado), consistente com Carbon::dayOfWeek.
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
     * @param string $startTime Hora de início (HH:MM:SS ou HH:MM).
     * @param string $endTime Hora de fim (HH:MM:SS ou HH:MM).
     * @param bool $isFixed Se a reserva é fixa (recorrente).
     * @param int|null $ignoreReservaId ID da reserva a ser ignorada na checagem (útil para o update).
     * @return bool True se houver conflito, False caso contrário.
     */
    protected function checkOverlap(string $date, string $startTime, string $endTime, bool $isFixed, ?int $ignoreReservaId = null): bool
    {
        // Pega o dia da semana (0-6)
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        Log::debug("CHECK OVERLAP INICIADO: Data={$date}, Start={$startTime}, End={$endTime}, Fixed={$isFixed}, IgnoreId={$ignoreReservaId}");


        // Query base para sobreposição de tempo (somente status que ocupam o slot)
        $baseQuery = Reserva::query()
            // 1. FILTRO CRÍTICO: Usa a lógica de "ocupação" do modelo (PENDENTE E CONFIRMADA)
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->when($ignoreReservaId, function ($query) use ($ignoreReservaId) {
                return $query->where('id', '!=', $ignoreReservaId); // Exclui a reserva atual se for um update
            })
            ->where(function ($query) use ($startTime, $endTime) {
                // 2. Lógica de sobreposição de tempo (overlap)
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });

        if ($isFixed) {
            // Se a nova reserva é FIXA (criação de série), checa conflito contra:

            // 1.1. Conflito com OUTRA SÉRIE FIXA (checa por day_of_week e horário, IGNORANDO a data específica)
            $conflitoComOutraFixa = (clone $baseQuery)
                ->where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->exists();

            if ($conflitoComOutraFixa) {
                Log::warning("Conflito de agendamento FIXO detectado contra outra série fixa. Data={$date}");
                return true;
            }

            // 1.2. Conflito PONTUAL na data de INÍCIO (checa a primeira data contra reservas pontuais ativas)
            // Impede que a série comece em um slot já pontualmente ocupado.
            $conflitoPontualNaPrimeiraData = (clone $baseQuery)
                ->where('date', $date)
                ->exists();

            if ($conflitoPontualNaPrimeiraData) {
                 Log::warning("Conflito de agendamento FIXO detectado contra reserva PONTUAL na data de início. Data={$date}");
            }
            return $conflitoPontualNaPrimeiraData;

        } else {
            // Se a nova reserva é PONTUAL (ou confirmação de instância),
            // ela checa conflito contra QUALQUER reserva ATIVA na DATA EXATA.

            // Usa a baseQuery e adiciona o filtro de data.
            $conflitoNaDataExata = (clone $baseQuery)
                ->where('date', $date) // Checa a data específica
                ->exists();

            if ($conflitoNaDataExata) {
                 Log::warning("Conflito de agendamento PONTUAL detectado na data exata. Data={$date}");
            }
            return $conflitoNaDataExata;
        }
    }

    /**
     * Função auxiliar para buscar os IDs conflitantes para feedback.
     */
    protected function getConflictingReservaIds(string $date, string $startTime, string $endTime, ?int $ignoreReservaId = null)
    {
        // *** CRÍTICO: Se suas constantes não forem 'pending'/'confirmed', ajuste aqui! ***
        $activeStatuses = [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA];

        $conflictingReservas = Reserva::whereIn('status', $activeStatuses)
            ->when($ignoreReservaId, function ($query) use ($ignoreReservaId) {
                return $query->where('id', '!=', $ignoreReservaId);
            })
            ->where('date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->pluck('id');

        $idsString = $conflictingReservas->implode(', ');
        Log::info("IDs de conflito para Data={$date}, Start={$startTime}, End={$endTime}, IgnoreId={$ignoreReservaId}: " . ($idsString ?: 'NENHUM ENCONTRADO'));

        return $idsString;
    }

    /**
     * Exibe a grade de horários disponíveis. (Método index existente)
     * CRÍTICO: Inclui a lógica para reabrir slots cancelados de reservas fixas.
     */
    public function index()
    {
        // Define o período de cálculo (próximas 2 semanas)
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addWeeks(2);

        // ====================================================================
        // PASSO 1: Ocupações por Reservas Fixas ATIVAS (Anulam a recorrência do Schedule)
        // ====================================================================
        $fixedReservaSlots = Reserva::where('is_fixed', true)
                                     ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                     ->select('day_of_week', 'start_time', 'end_time')
                                     ->get();

        // Mapeia os slots fixos reservados para fácil exclusão (chave: 'day_of_week-start_time-end_time')
        $fixedReservaMap = $fixedReservaSlots->map(function ($reserva) {
            return "{$reserva->day_of_week}-{$reserva->start_time}-{$reserva->end_time}";
        })->toArray();
        // ====================================================================

        // ====================================================================
        // PASSO 1.5: Cancelamentos de Reservas Fixas (Exceções)
        // Busca reservas fixas que foram CANCELADAS no período para REABRIR O SLOT PONTUALMENTE.
        // ====================================================================
        $canceledFixedReservas = Reserva::where('is_fixed', true)
                                         ->where('status', Reserva::STATUS_CANCELADA)
                                         ->whereDate('date', '>=', $startDate->toDateString())
                                         ->whereDate('date', '<=', $endDate->toDateString())
                                         ->select('date', 'start_time', 'end_time', 'price', 'schedule_id')
                                         ->get();

        // Mapeia as exceções de cancelamento (chave: 'Y-m-d H:i')
        $canceledFixedMap = $canceledFixedReservas->mapWithKeys(function ($reserva) {
            // Chave: 'YYYY-MM-DD HH:MM'
            $dateTime = Carbon::parse($reserva->date->toDateString() . ' ' . $reserva->start_time)->format('Y-m-d H:i');
            return [$dateTime => [
                'start_time' => Carbon::parse($reserva->start_time)->format('H:i'),
                'end_time' => Carbon::parse($reserva->end_time)->format('H:i'),
                'price' => $reserva->price, // O preço da reserva cancelada
                'schedule_id' => $reserva->schedule_id,
                'is_fixed_cancellation' => true
            ]];
        })->toArray();
        // ====================================================================


        // 2. HORÁRIOS RECORRENTES FIXOS (Disponibilidade do Admin)
        $recurringSchedules = Schedule::whereNotNull('day_of_week')
                                         ->whereNull('date')
                                         ->where('is_active', true)
                                         ->orderBy('day_of_week')
                                         ->orderBy('start_time')
                                         ->get();

        // ====================================================================
        // PASSO 2.5: FILTRA SLOTS RECORRENTES ANULADOS POR RESERVAS FIXAS ATIVAS
        // Remove da lista de schedules recorrentes tudo o que está em $fixedReservaMap.
        // ====================================================================
        $recurringSchedules = $recurringSchedules->filter(function ($schedule) use ($fixedReservaMap) {
            $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
            // Retorna TRUE (mantém o slot) se a chave NÃO estiver no mapa de reservas fixas ativas
            return !in_array($scheduleKey, $fixedReservaMap);
        });
        // ====================================================================


        // 3. HORÁRIOS AVULSOS: Onde date é definido e está dentro do período.
        $adHocSchedules = Schedule::whereNotNull('date')
                                     ->where('is_active', true)
                                     ->whereDate('date', '>=', $startDate->toDateString())
                                     ->whereDate('date', '<=', $endDate->toDateString())
                                     ->orderBy('start_time')
                                     ->get();

        // === RETORNA AGENDA VAZIA SE NÃO HOVER REGISTROS ===
        if ($recurringSchedules->isEmpty() && $adHocSchedules->isEmpty() && empty($canceledFixedMap)) {
            $dayNames = $this->dayNames;
            return view('reserva.index', ['weeklySchedule' => [], 'dayNames' => $dayNames]);
        }
        // ====================================================================

        // ====================================================================
        // 4. RESERVAS ATIVAS DENTRO DO PERÍODO (Pontuais E Fixas)
        // Busca todas as reservas ativas dentro do período para o filtro final.
        // ====================================================================
        $allActiveReservations = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                         ->whereDate('date', '>=', $startDate->toDateString())
                                         ->whereDate('date', '<=', $endDate->toDateString())
                                         ->get();

        // Mapeia os slots ocupados (chave: 'Y-m-d H:i')
        $occupiedMap = $allActiveReservations->mapWithKeys(function ($reserva) {
            $dateTime = Carbon::parse($reserva->date->toDateString() . ' ' . $reserva->start_time)->format('Y-m-d H:i');
            return [$dateTime => true];
        })->toArray();
        // ====================================================================

        // 5. CALCULA O CRONOGRAMA SEMANAL (próximas 2 semanas)
        $weeklySchedule = [];
        $period = CarbonPeriod::create($startDate, $endDate);
        $now = Carbon::now();

        foreach ($period as $date) {
            $currentDateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeek; // 0 (Dom) a 6 (Sáb)

            // A) Horários Recorrentes para este dia da semana (JÁ FILTRADOS contra Reservas Fixas)
            $dayRecurringSlots = $recurringSchedules->where('day_of_week', $dayOfWeek);

            // B) Horários Avulsos Específicos para esta data
            $dayAdHocSlots = $adHocSchedules->where('date', $currentDateString);

            // C) Combina e ordena os dois tipos de horários para o dia
            $combinedSchedules = $dayRecurringSlots->merge($dayAdHocSlots)->sortBy('start_time');

            // === CRÍTICO: ADICIONA EXCEÇÕES DE CANCELAMENTO FIXO ===
            $dateKeyForCancellation = $date->copy()->format('Y-m-d');

            foreach ($canceledFixedMap as $slotKey => $cancellationDetails) {
                // Checa se a chave do slot cancelado corresponde ao dia atual
                if (Str::startsWith($slotKey, $dateKeyForCancellation)) {
                    // Verifica se o slot jÁ está ocupado por uma nova reserva PONTUAL
                    $isOccupiedByNewReservation = isset($occupiedMap[$slotKey]);

                    // Verifica se o horário do slot de cancelamento já passou
                    $slotEnd = Carbon::createFromFormat('H:i', $cancellationDetails['end_time']);
                    $slotEndDateTime = $date->copy()->setTime($slotEnd->hour, $slotEnd->minute);
                    $isPassed = $date->isToday() && $slotEndDateTime->lt($now);

                    if (!$isOccupiedByNewReservation && !$isPassed) {
                        // Adiciona o slot da reserva cancelada como um slot avulso (tipo: 'Cancelamento Fixo')
                        $startTime = Carbon::createFromFormat('H:i', $cancellationDetails['start_time']);
                        $endTime = Carbon::createFromFormat('H:i', $cancellationDetails['end_time']);

                        $weeklySchedule[$currentDateString][] = [
                            'start_time' => $startTime->format('H:i'),
                            'end_time' => $endTime->format('H:i'),
                            'price' => $cancellationDetails['price'],
                            'schedule_id' => $cancellationDetails['schedule_id'],
                            'type' => 'Cancelamento Fixo',
                        ];
                    }
                }
            }
            // ====================================================

            foreach ($combinedSchedules as $schedule) {
                $startTime = Carbon::parse($schedule->start_time);
                $endTime = Carbon::parse($schedule->end_time);

                // Constrói o DateTime completo para o FIM do slot, usando a data do loop.
                $scheduleEndDateTime = $date->copy()->setTime($endTime->hour, $endTime->minute);

                // CORREÇÃO DE LÓGICA: Ignorar horários que JÁ PASSARAM hoje (comparando com o FIM do slot)
                if ($date->isToday() && $scheduleEndDateTime->lt($now)) {
                    continue;
                }

                $slotDateTime = $date->copy()->setTime($startTime->hour, $startTime->minute);
                $slotKey = $slotDateTime->format('Y-m-d H:i');

                // Verifica se o slot está livre de TODAS as RESERVAS ATIVAS (pontuais ou fixas)
                if (!isset($occupiedMap[$slotKey])) {

                    $slotType = $schedule->date ? 'Avulso' : 'Recorrente';

                    $weeklySchedule[$currentDateString][] = [
                        'start_time' => $startTime->format('H:i'),
                        'end_time' => $endTime->format('H:i'),
                        'price' => $schedule->price,
                        'schedule_id' => $schedule->id,
                        'type' => $slotType, // Adiciona o tipo de slot
                    ];
                }
            }

            // Ordena o array final de slots do dia por hora de início
            if (isset($weeklySchedule[$currentDateString])) {
                // Remove duplicatas que podem ter ocorrido entre slots de Cancelamento Fixo e Avulsos/Recorrentes
                $uniqueSlots = collect($weeklySchedule[$currentDateString])->unique(function ($item) {
                     return $item['start_time'] . '-' . $item['end_time'];
                })->all();

                usort($uniqueSlots, function($a, $b) {
                    return strcmp($a['start_time'], $b['start_time']);
                });

                $weeklySchedule[$currentDateString] = $uniqueSlots;
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
                            ->whereDate('date', '>=', $hoje->toDateString())
                            ->whereDate('date', '<=', $hoje->copy()->addDays($diasParaVerificar)->toDateString())
                            ->pluck('date') // Retorna uma Collection de strings 'YYYY-MM-DD'
                            ->unique()
                            ->toArray();

        // 2.5. TRATA CANCELAMENTOS FIXOS: Adiciona as datas de cancelamento fixo como disponibilidade
        // Isso garante que o seletor de data mostre um dia que estava bloqueado.
        $canceledFixedDates = Reserva::where('is_fixed', true)
                                     ->where('status', Reserva::STATUS_CANCELADA)
                                     ->whereDate('date', '>=', $hoje->toDateString())
                                     ->whereDate('date', '<=', $hoje->copy()->addDays($diasParaVerificar)->toDateString())
                                     ->pluck('date')
                                     ->unique()
                                     ->map(fn($date) => $date->toDateString())
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
            $isFixedCancellation = in_array($currentDateString, $canceledFixedDates);


            // Se for um dia recorrente disponível OU for uma data avulsa específica OU tiver um cancelamento fixo que a liberou
            if ($isRecurringAvailable || $isAdHocAvailable || $isFixedCancellation) {
                // A data só é adicionada se for hoje E já não tiver passado (que é tratado no getAvailableTimes)
                // Aqui apenas filtramos se há qualquer disponibilidade
                $diasDisponiveisNoFuturo[] = $currentDateString;
            }
        }

        // 4. RETORNO PARA A VIEW
        return view('admin.reservas.create', [
            'diasDisponiveisJson' => json_encode(array_values(array_unique($diasDisponiveisNoFuturo))),
        ]);
    }

    /**
     * Calcula e retorna os horários disponíveis para uma data específica.
     * CRÍTICO: Inclui a lógica para reabrir slots cancelados de reservas fixas.
     */
    public function getAvailableTimes(Request $request)
    {
        // 1. Validação
        $request->validate([
             'date' => 'required|date_format:Y-m-d',
        ]);

        $dateString = $request->input('date');
        $selectedDate = Carbon::parse($dateString);
        $dayOfWeek = $selectedDate->dayOfWeek;
        $isToday = $selectedDate->isToday();

        // Pega o Carbon::now() uma vez, que agora está no fuso horário correto
        $now = Carbon::now();

        // 2. Schedules (slots) definidos para este dia (Recorrentes ou Avulsos)
        $allSchedules = Schedule::where('is_active', true)
            ->where(function ($query) use ($dayOfWeek, $dateString) {
                // Slots recorrentes (para este dia da semana)
                $query->whereNotNull('day_of_week')
                    ->whereNull('date')
                    ->where('day_of_week', $dayOfWeek);
                // Slots avulsos (para esta data específica)
                $query->orWhere(function ($query) use ($dateString) {
                    $query->whereNotNull('date')
                          ->where('date', $dateString);
                });
            })
            ->orderBy('start_time')
            ->get();

        // 2.1. Slots Recorrentes bloqueados por Reservas Fixas Ativas
        $fixedReservaSlots = Reserva::where('is_fixed', true)
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->select('day_of_week', 'start_time', 'end_time')
            ->get();

        $fixedReservaMap = $fixedReservaSlots->map(function ($reserva) {
            return "{$reserva->day_of_week}-{$reserva->start_time}-{$reserva->end_time}";
        })->toArray();
        // Filtra os slots do Schedule que estão bloqueados de forma recorrente (permanente)
        $allSchedules = $allSchedules->filter(function ($schedule) use ($fixedReservaMap) {
            $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
            return !in_array($scheduleKey, $fixedReservaMap);
        });

        // 2.2. Slots Reabertos por Cancelamento Fixo para esta data
        // Busca a reserva cancelada para transformá-la em slot DISPONÍVEL.
        $canceledFixedReservas = Reserva::where('is_fixed', true)
            ->where('status', Reserva::STATUS_CANCELADA)
            ->whereDate('date', $dateString)
            ->get();

        $cancellationSlots = [];
        foreach($canceledFixedReservas as $reserva) {
            $cancellationSlots[] = [
                'id' => $reserva->id, // Usamos o ID da reserva cancelada, apenas para referência
                'start_time' => $reserva->start_time,
                'end_time' => $reserva->end_time,
                'price' => $reserva->price,
                'schedule_id' => $reserva->schedule_id,
                'type' => 'Cancelamento Fixo',
            ];
        }


        // 3. Reservas Confirmadas/Pendentes para a data
        // BUSCA TODAS AS RESERVAS (FIXAS E PONTUAIS) QUE OCUPAM ESTA DATA.
        $activeStatuses = [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA];

        $occupiedReservas = Reserva::whereDate('date', $dateString)
            ->whereIn('status', $activeStatuses)
            ->get();

        // 4. Filtrar Schedules Ocupados (Usando Lógica de Sobreposição)
        $combinedAvailableSlots = [];

        // D. Adiciona slots de Cancelamento Fixo (que foram reabertos)
        foreach ($cancellationSlots as $slot) {
            $slotStart = Carbon::parse($slot['start_time']);
            $slotEnd = Carbon::parse($slot['end_time']);

            // Verifica se o slot de cancelamento já passou hoje
            $slotEndDateTime = $selectedDate->copy()->setTime($slotEnd->hour, $slotEnd->minute);
            if ($isToday && $slotEndDateTime->lt($now)) {
                continue;
            }

            // Verifica conflito com novas reservas (se alguém pontualmente reservou este slot)
            $isBooked = $occupiedReservas->contains(function ($reservation) use ($slotStart, $slotEnd) {
                // Checa overlap
                return $reservation->start_time < $slotEnd->format('H:i:s') && $reservation->end_time > $slotStart->format('H:i:s');
            });

            if (!$isBooked) {
                $priceFormatted = number_format($slot['price'], 2, ',', '.');

                $combinedAvailableSlots[] = [
                    'id' => $slot['schedule_id'], // Usamos o ID do Schedule
                    'time_slot' => $slotStart->format('H:i') . ' - ' . $slotEnd->format('H:i'),
                    'price' => $slot['price'], // RAW
                    'price_formatted' => $priceFormatted, // FORMATTED
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'schedule_id' => $slot['schedule_id'],
                    'type' => $slot['type'],
                ];
            }
        }


        // E. Adiciona slots Recorrentes/Avulsos (os que sobraram após a filtragem de slots fixos)
        $availableScheduleTimes = $allSchedules->filter(function ($schedule) use ($isToday, $now, $selectedDate, $occupiedReservas) {

            $startTime = Carbon::parse($schedule->start_time);
            $endTime = Carbon::parse($schedule->end_time);

            // Constrói o DateTime completo para o FIM do slot, usando a data do loop.
            $scheduleEndDateTime = $selectedDate->copy()->setTime($endTime->hour, $endTime->minute);

            // A. Checagem de slots passados (apenas se for hoje)
            if ($isToday && $scheduleEndDateTime->lt($now)) {
                return false;
            }

            // B. Checagem de Conflito de Horário (Lógica de Sobreposição)
            $isBooked = $occupiedReservas->contains(function ($reservation) use ($schedule) {
                $overlap = $reservation->start_time < $schedule->end_time && $reservation->end_time > $schedule->start_time;
                return $overlap;
            });

            // Retorna TRUE se NÃO estiver reservado (disponível)
            return !$isBooked;

        })->map(function ($schedule) {
            // Formata os dados para o JavaScript
            $startTime = Carbon::parse($schedule->start_time);
            $endTime = Carbon::parse($schedule->end_time);
            $priceFormatted = number_format($schedule->price, 2, ',', '.');

            return [
                'id' => $schedule->id,
                'time_slot' => $startTime->format('H:i') . ' - ' . $endTime->format('H:i'),
                'price' => $schedule->price, // RAW
                'price_formatted' => $priceFormatted, // FORMATTED
                'start_time' => $startTime->format('H:i'),
                'end_time' => $endTime->format('H:i'),
                'schedule_id' => $schedule->id,
                'type' => $schedule->date ? 'Avulso' : 'Recorrente',
            ];
        })->toArray();

        // 5. Combina os dois arrays e remove duplicatas (se houver um slot de cancelamento e um slot avulso idêntico)
        // E ordena pelo horário
        $finalAvailableTimes = collect(array_merge($combinedAvailableSlots, $availableScheduleTimes))
                                         ->unique(function ($item) {
                                              return $item['start_time'] . '-' . $item['end_time'];
                                         })
                                         ->sortBy('start_time')
                                         ->values();

        // 6. Retorna apenas o array de slots
        return response()->json($finalAvailableTimes);
    }


    // =========================================================================
    // MÉTODO `store` (Para o Painel Admin)
    // =========================================================================
    /**
     * Salva uma nova reserva a partir do Painel Admin (Confirmação Imediata).
     */
    public function store(Request $request)
    {
        // 0. Pré-Sanitização do contato
        $contactValue = $request->input('client_contact', '');
        $cleanedContact = preg_replace('/\D/', '', $contactValue);
        $request->merge(['client_contact' => $cleanedContact]);

        $managerId = Auth::id();

        // Crio um Validator dummy para que possamos injetar erros de conflito.
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'client_contact' => ['required', 'digits_between:10,11'],
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'price' => 'required|numeric|min:0',
            'schedule_id' => 'required|integer|exists:schedules,id',
            'notes' => 'nullable|string|max:500',
            // O campo 'overlap_check' foi renomeado para 'reserva_conflito_id' para maior clareza
            'reserva_conflito_id' => 'nullable',
        ], [
            'client_contact.digits_between' => 'O contato do cliente deve conter 10 ou 11 dígitos (apenas números, incluindo o DDD).',
            'client_name.required' => 'O nome do cliente é obrigatório.',
            'date.required' => 'A data é obrigatória.',
            'start_time.required' => 'O horário de início é obrigatório (selecione um slot).',
            'end_time.required' => 'O horário de fim é obrigatório (selecione um slot).',
            'price.required' => 'O preço é obrigatório (selecione um slot).',
            'price.numeric' => 'O preço deve ser um número válido.',
            'schedule_id.required' => 'O ID do horário é obrigatório (selecione um slot).',
        ]);

        // Se a validação básica falhar, retorne os erros normais.
        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput();
        }

        $validatedData = $validator->validated();
        $isFixed = $request->has('is_fixed');
        $date = $validatedData['date'];
        $startTime = $validatedData['start_time'];
        $endTime = $validatedData['end_time'];

        // ==========================================================
        // CASO 1: RESERVA PONTUAL (is_fixed = false) - CRIAÇÃO MANUAL
        // ==========================================================
        if (!$isFixed) {
            // === USA O HELPER checkOverlap ===
            if ($this->checkOverlap($date, $startTime, $endTime, false)) {
                $conflictingIds = $this->getConflictingReservaIds($date, $startTime, $endTime);

                $baseMsg = 'Conflito! O horário já está ocupado por uma reserva pontual ou fixa existente.';

                if (empty($conflictingIds)) {
                    Log::error("ERRO CRÍTICO (ANOMALIA PONTUAL): checkOverlap TRUE, mas getConflictingReservaIds vazio para Data={$date}");
                    $errorMsg = $baseMsg . ' (ANOMALIA: ID do conflito não encontrado. Cheque os logs!)';
                } else {
                    $errorMsg = $baseMsg . ' O ID(s) do conflito é: ' . $conflictingIds;
                }

                // Loga a mensagem final para confirmar que ela foi construída corretamente
                Log::warning("ERRO DE CONFLITO PONTUAL AO SALVAR (ADMIN): Mensagem final: " . $errorMsg);

                // *** INJEÇÃO DE ERRO COM CHAVE ÚNICA ***
                $validator->errors()->add('reserva_conflito_id', $errorMsg);
                throw new ValidationException($validator);
            }
            // ==========================================================

            try {
                $dayOfWeek = Carbon::parse($date)->dayOfWeek;

                Reserva::create([
                    'user_id' => null,
                    'manager_id' => $managerId,
                    'schedule_id' => $validatedData['schedule_id'],
                    'date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'price' => $validatedData['price'],
                    'client_name' => $validatedData['client_name'],
                    'client_contact' => $request->input('client_contact'),
                    'notes' => $validatedData['notes'] ?? null,
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => false,
                    'day_of_week' => $dayOfWeek,
                    'recurrent_series_id' => null,
                    'week_index' => null,
                ]);

                return redirect()->route('admin.reservas.create')
                               ->with('success', 'Reserva pontual confirmada com sucesso!');

            } catch (\Exception $e) {
                Log::error("Erro ao criar reserva pontual (Admin): " . $e->getMessage());
                return redirect()->back()
                    ->with('error', 'Erro do servidor. Não foi possível criar a reserva.')
                    ->withInput();
            }
        }

        // ==========================================================
        // CASO 2: RESERVA FIXA (is_fixed = true)
        // ==========================================================

        // 1. Checagem de conflito para criação de SÉRIE
        if ($this->checkOverlap($date, $startTime, $endTime, true)) {
            $conflictingIds = $this->getConflictingReservaIds($date, $startTime, $endTime);

            $baseMsg = 'Conflito Fixo! Este horário já está reservado por uma série fixa ou pontual na data de início.';

            if (empty($conflictingIds)) {
                Log::error("ERRO CRÍTICO (ANOMALIA FIXA): checkOverlap TRUE, mas getConflictingReservaIds vazio para Data={$date}");
                $errorMsg = $baseMsg . ' (ANOMALIA: ID do conflito não encontrado. Cheque os logs!)';
            } else {
                $errorMsg = $baseMsg . ' O ID(s) do conflito é: ' . $conflictingIds;
            }

            // Loga a mensagem final para confirmar que ela foi construída corretamente
            Log::warning("ERRO DE CONFLITO FIXO AO SALVAR (ADMIN): Mensagem final: " . $errorMsg);

            // *** INJEÇÃO DE ERRO COM CHAVE ÚNICA ***
            $validator->errors()->add('reserva_conflito_id', $errorMsg);
            throw new ValidationException($validator);
        }

        // 2. Preparar dados para a série de 52 semanas (1 ano)
        $startDate = Carbon::parse($date);
        $dayOfWeek = $startDate->dayOfWeek;
        $seriesId = (string) Str::uuid();
        $totalWeeks = 52;
        $reservasCriadas = 0;
        $reservasFalhadas = 0;
        $datasPuladas = [];

        // 3. Usar Transação de DB
        DB::beginTransaction();

        try {
            for ($i = 0; $i < $totalWeeks; $i++) {
                $currentDate = $startDate->copy()->addWeeks($i);
                $currentDateString = $currentDate->toDateString();

                // 4. Checagem de conflito PONTUAL em cada data
                // Usa isFixed=false aqui para checar conflito PONTUAL contra TUDO que já existe.
                if ($this->checkOverlap($currentDateString, $startTime, $endTime, false)) {

                    $conflictingIds = $this->getConflictingReservaIds($currentDateString, $startTime, $endTime);
                    Log::warning("Conflito pontual na iteração da série (Semana {$i}): Data={$currentDateString}. ID(s) em conflito: " . ($conflictingIds ?: 'ANOMALIA - ID VAZIO'));

                    $reservasFalhadas++;
                    $datasPuladas[] = $currentDate->format('d/m/Y');
                    continue; // Pula esta semana e vai para a próxima
                }

                // 5. Criar a reserva da semana
                Reserva::create([
                    'user_id' => null,
                    'manager_id' => $managerId,
                    'schedule_id' => $validatedData['schedule_id'],
                    'date' => $currentDateString,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'price' => $validatedData['price'],
                    'client_name' => $validatedData['client_name'],
                    'client_contact' => $request->input('client_contact'),
                    'notes' => $validatedData['notes'] ?? null,
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => true,
                    'day_of_week' => $dayOfWeek,
                    'recurrent_series_id' => $seriesId,
                    'week_index' => $i,
                ]);

                $reservasCriadas++;
            }

            // 6. Sucesso! Salva tudo no banco.
            DB::commit();

            // 7. Preparar mensagens de feedback
            $successMessage = "Série de {$reservasCriadas} reservas fixas criada com sucesso!";
            $warningMessage = $reservasFalhadas > 0 ? "{$reservasFalhadas} datas foram puladas por já estarem ocupadas: " . implode(', ', $datasPuladas) : null;

            return redirect()->route('admin.reservas.create')
                             ->with('success', $successMessage)
                             ->with('warning', $warningMessage);

        } catch (\Exception $e) {
            // 8. Falha! Desfaz tudo.
            DB::rollBack();
            Log::error("Erro CRÍTICO ao criar série de reservas fixas (Admin): " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro crítico do servidor. A série de reservas falhou e foi desfeita (rollback). Nenhuma reserva foi salva.')
                ->withInput();
        }
    }


    // =========================================================================
    // MÉTODO `storePublic` (FINAL)
    // =========================================================================
    /**
     * Salva a pré-reserva (Formulário Público).
     */
    public function storePublic(Request $request)
    {
        // 0. Pré-Sanitização do contato
        $contactValue = $request->input('contato_cliente', '');
        $cleanedContact = preg_replace('/\D/', '', $contactValue);
        $request->merge(['contato_cliente' => $cleanedContact]);

        // Pré-Sanitização do preço
        $priceValue = $request->input('price');

        // Se o valor for uma string, aplica a conversão de BR para US
        if (is_string($priceValue)) {
            // 1. Remove todos os separadores de milhar (ponto)
            $cleanedPrice = str_replace('.', '', $priceValue);
            // 2. Troca o separador decimal (vírgula) por ponto
            $cleanedPrice = str_replace(',', '.', $cleanedPrice);

            // 3. Garante que o valor limpo seja mesclado de volta na requisição
            $request->merge(['price' => $cleanedPrice]);
        }
        // **********************************************************************************************

        // 1. Definição manual das regras
        $rules = [
            'nome_cliente'      => ['required', 'string', 'max:255'],
            'contato_cliente'   => ['required', 'digits_between:10,11'],
            'data_reserva'      => ['required', 'date', "after_or_equal:" . Carbon::today()->format('Y-m-d')],
            'hora_inicio'       => ['required', 'date_format:H:i'],
            'hora_fim'          => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'price'             => ['required', 'numeric', 'min:0'],
            'schedule_id'       => ['required', 'integer', 'exists:schedules,id'],
            'is_fixed'          => ['sometimes', 'boolean'],
             // O campo 'overlap_check' foi renomeado para 'reserva_conflito_id' para maior clareza
            'reserva_conflito_id' => 'nullable',
        ];

        // 2. Validação Manual com mensagens personalizadas
        $validator = Validator::make($request->all(), $rules, [
            'nome_cliente.required' => 'O nome do cliente é obrigatório.',
            'contato_cliente.required' => 'O contato do cliente é obrigatório.',
            'contato_cliente.digits_between' => 'O contato deve ter 10 ou 11 dígitos (apenas números, incluindo o DDD).',
            'data_reserva.required' => 'A data da reserva é obrigatória.',
            'data_reserva.after_or_equal' => 'Não é possível agendar em uma data passada.',
            'hora_inicio.required' => 'O horário de início é obrigatório (selecione um slot).',
            'hora_fim.after' => 'O horário final deve ser posterior ao horário de início.',
        ]);


        if ($validator->fails()) {
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput()
                             ->with('error', 'Correção Necessária! Por favor, verifique os campos destacados em vermelho e tente novamente.');
        }

        $validated = $validator->validated();

        $date = $validated['data_reserva'];
        $startTime = $validated['hora_inicio'];
        $endTime = $validated['hora_fim'];
        $clientName = $validated['nome_cliente'];
        $clientContact = $request->input('contato_cliente');
        $price = $validated['price'];
        $isFixed = $request->input('is_fixed', false);

        // === USA O HELPER checkOverlap ===
        if ($this->checkOverlap($date, $startTime, $endTime, $isFixed)) {
            $conflictingIds = $this->getConflictingReservaIds($date, $startTime, $endTime);

            $baseMsg = 'ERRO: Este horário está em conflito com uma reserva existente (pontual ou fixa).';

            if (empty($conflictingIds)) {
                Log::error("ERRO CRÍTICO (ANOMALIA PÚBLICA): checkOverlap TRUE, mas getConflictingReservaIds vazio para Data={$date}");
                $errorMsg = $baseMsg . ' (ANOMALIA: ID do conflito não encontrado. Cheque os logs!)';
            } else {
                $errorMsg = $baseMsg . ' ID(s) em conflito: ' . $conflictingIds;
            }

            Log::warning("ERRO DE CONFLITO PÚBLICO AO SALVAR: Mensagem final: " . $errorMsg);

            // *** INJEÇÃO DE ERRO COM CHAVE ÚNICA ***
            $validator->errors()->add('reserva_conflito_id', $errorMsg);
            throw new ValidationException($validator);
        }
        // ===================================================================

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;


        $reserva = Reserva::create([
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'client_name' => $clientName,
            'client_contact' => $clientContact,
            'price' => $price,
            'schedule_id' => $validated['schedule_id'],
            'status' => Reserva::STATUS_PENDENTE,
            'is_fixed' => $isFixed,
            'day_of_week' => $dayOfWeek,
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
                ], 400);
            }

            // 2. Regra de Negócio Crítica: Impedir confirmação (confirmed) se o slot já estiver ocupado.
            if ($newStatus === Reserva::STATUS_CONFIRMADA) {

                $date = $reserva->date->toDateString();
                $startTime = $reserva->start_time;
                $endTime = $reserva->end_time;
                $ignoreId = $reserva->id;

                // === USA O HELPER checkOverlap ===
                if ($this->checkOverlap($date, $startTime, $endTime, false, $ignoreId)) {

                    $conflictingIds = $this->getConflictingReservaIds($date, $startTime, $endTime, $ignoreId);

                    $baseMsg = 'Conflito detectado: Esta reserva não pode ser confirmada, pois já existe outro agendamento (Pendente ou Confirmado) no mesmo horário.';

                    if (empty($conflictingIds)) {
                        Log::error("ERRO CRÍTICO (ANOMALIA UPDATE): checkOverlap TRUE, mas getConflictingReservaIds vazio para Reserva ID={$reserva->id}");
                        $errorMsg = $baseMsg . ' (ANOMALIA: ID do conflito não encontrado. Cheque os logs!)';
                    } else {
                        $errorMsg = $baseMsg . ' O ID(s) em conflito é: ' . $conflictingIds;
                    }

                    Log::error("Conflito CRÍTICO detectado ao tentar confirmar a Reserva #{$reserva->id}. Mensagem final: " . $errorMsg);


                    return response()->json([
                        'message' => $errorMsg,
                    ], 409); // 409 Conflict
                }
            }

            // 3. Atualiza o manager_id se estivermos confirmando/alterando status de algo que era Pendente (cliente)
            if ($reserva->manager_id === null && in_array($newStatus, [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA])) {
                   $reserva->manager_id = Auth::id();
            }


            // 4. Atualiza o status no banco de dados
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
