<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Reserva;
use App\Http\Requests\StoreReservaRequest;
use App\Http\Requests\UpdateReservaStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

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
            // Cria uma chave única baseda no dia e horário da reserva fixa
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

        // ====================================================================
        // 3. RESERVAS ATIVAS DENTRO DO PERÍODO (Pontuais E Fixas) - CORREÇÃO CRÍTICA AQUI
        // Agora, busca todas as reservas ativas dentro do período para o filtro final.
        $allActiveReservations = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                             // Removido: ->where('is_fixed', false)
                                             ->where('date', '>=', $startDate->toDateString())
                                             ->where('date', '<=', $endDate->toDateString())
                                             ->get();

        // Mapeia os slots ocupados para fácil verificação (chave: 'Y-m-d H:i')
        $occupiedMap = $allActiveReservations->mapWithKeys(function ($reserva) {
            // A chave aqui usa a data exata e o horário
            // Usa toDateString() para obter apenas 'YYYY-MM-DD' e evitar o '00:00:00'.
            $dateTime = Carbon::parse($reserva->date->toDateString() . ' ' . $reserva->start_time)->format('Y-m-d H:i');
            return [$dateTime => true];
        })->toArray();
        // ====================================================================

        // 4. CALCULA O CRONOGRAMA SEMANAL (próximas 2 semanas)
        $weeklySchedule = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        // Pega o Carbon::now() uma vez, que agora está no fuso horário correto (America/Sao_Paulo)
        $now = Carbon::now();

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
                    // Define o tipo de slot (Avulso se tiver 'date', Recorrente se tiver 'day_of_week' e não tiver 'date')
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

        // 4. RETORNO PARA A VIEW
        return view('admin.reservas.create', [
            'diasDisponiveisJson' => json_encode(array_values(array_unique($diasDisponiveisNoFuturo))),
        ]);
    }

    /**
     * Calcula e retorna os horários disponíveis para uma data específica.
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

        // Pega o Carbon::now() uma vez, que agora está no fuso horário correto (America/Sao_Paulo)
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

        // 3. Reservas Confirmadas/Pendentes para a data
        // BUSCA TODAS AS RESERVAS (FIXAS E PONTUAIS) QUE OCUPAM ESTA DATA.
        $occupiedReservas = Reserva::whereDate('date', $dateString)
                                             ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                             ->get();

        // --- LOG DE DEBUG FINAL (Crítico para validação) ---
        Log::info("DEBUG AGENDAMENTO (ReservaController) para data: {$dateString} ({$dayOfWeek})");
        Log::info("  Hora atual (America/Sao_Paulo): {$now->toDateTimeString()}");
        foreach ($occupiedReservas as $reserva) {
             Log::info(" - Reserva ID: {$reserva->id}, Horário: {$reserva->start_time} - {$reserva->end_time}, Fixa: " . ($reserva->is_fixed ? 'SIM' : 'NÃO'));
        }
        // --- FIM DO LOG DE DEBUG ---

        // 4. Filtrar Schedules Ocupados (Usando Lógica de Sobreposição)
        $availableTimes = $allSchedules->filter(function ($schedule) use ($isToday, $now, $selectedDate, $occupiedReservas, $dateString) {

            // CORREÇÃO: Usando Carbon::parse robusto para criar o DateTime do FIM do slot.
            // Esta alteração garante que o formato do banco de dados (HH:MM ou HH:MM:SS) seja tratado corretamente.
            $scheduleEndDateTime = Carbon::parse($selectedDate->toDateString() . ' ' . $schedule->end_time);

            // A. Checagem de slots passados (apenas se for hoje)
            // Lógica: Compara o FIM do slot com o horário atual ($now).
            if ($isToday && $scheduleEndDateTime->lt($now)) {
                Log::info(" - Slot {$schedule->start_time}-{$schedule->end_time} ignorado. Passado: {$scheduleEndDateTime->toDateTimeString()} < {$now->toDateTimeString()}");
                return false;
            }

            // B. Checagem de Conflito de Horário (Lógica de Sobreposição)
            $isBooked = $occupiedReservas->contains(function ($reservation) use ($schedule) {
                // Checa se há sobreposição de horário:
                // Reserva (start) < Schedule (end) E Reserva (end) > Schedule (start)
                $overlap = $reservation->start_time < $schedule->end_time && $reservation->end_time > $schedule->start_time;

                if ($overlap) {
                    // Se houver conflito, loga e marca como ocupado
                    Log::warning("CONFLITO FINAL! Schedule ID {$schedule->id} ({$schedule->start_time}-{$schedule->end_time}) CONFLITA com Reserva ID {$reservation->id} ({$reservation->start_time}-{$reservation->end_time}).");
                }

                return $overlap;
            });

            // Retorna TRUE se NÃO estiver reservado (disponível)
            return !$isBooked;

        })->map(function ($schedule) {
            // Formata os dados para o JavaScript
            return [
                'id' => $schedule->id,
                'time_slot' => Carbon::parse($schedule->start_time)->format('H:i') . ' - ' . Carbon::parse($schedule->end_time)->format('H:i'),
                'price' => number_format($schedule->price, 2, ',', '.'),
                'start_time' => Carbon::parse($schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($schedule->end_time)->format('H:i'),
                'raw_price' => $schedule->price,
                'schedule_id' => $schedule->id,
            ];
        })->values();

        return response()->json($availableTimes);
    }


    // =========================================================================
    // MÉTODO `store` (Para o Painel Admin)
    // =========================================================================
    /**
     * Salva uma nova reserva a partir do Painel Admin (Confirmação Imediata).
     * Este método lida com reservas pontuais E com a criação de séries recorrentes.
     * Rota: POST /admin/reservas (name: 'admin.reservas.store')
     */
    public function store(Request $request)
    {
        // 0. Pré-Sanitização (para garantir que só dígitos cheguem ao Validator)
        $contactValue = $request->input('client_contact', '');
        // 🛑 LIMPEZA CRÍTICA: Remove TUDO que não for dígito (0-9).
        $cleanedContact = preg_replace('/\D/', '', $contactValue);
        $request->merge(['client_contact' => $cleanedContact]); // Sobrescreve o valor original
        Log::info("DEBUG ADMIN: Contato Original: '{$contactValue}', Limpo: '{$cleanedContact}'");

        // Pega o ID do gestor logado UMA VEZ. Se não estiver autenticado, será null.
        $managerId = Auth::id();
        // DEBUG CRÍTICO: Registra o ID do gestor antes de salvar
        Log::info("DEBUG MANAGER ID CRÍTICO: ID do Gestor logado (manager_id) é: " . ($managerId ?? 'NULL'));


        // 1. Validação dos dados vindos do formulário Admin
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            // A validação 'digits_between' agora trabalha sobre o campo limpo
            'client_contact' => ['required', 'digits_between:10,11'],
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'price' => 'required|numeric|min:0',
            'schedule_id' => 'required|integer|exists:schedules,id',
            'notes' => 'nullable|string|max:500',
            // is_fixed é checado com $request->has()
        ], [
            'client_name.required' => 'O nome do cliente é obrigatório.',
            'client_contact.required' => 'O contato do cliente é obrigatório.',
            'client_contact.digits_between' => 'O contato do cliente deve conter 10 ou 11 dígitos (apenas números, incluindo o DDD).',
            'date.required' => 'A data é obrigatória.',
            'start_time.required' => 'O horário de início é obrigatório (selecione um slot).',
            'end_time.required' => 'O horário de fim é obrigatório (selecione um slot).',
            'price.required' => 'O preço é obrigatório (selecione um slot).',
            'schedule_id.required' => 'O ID do horário é obrigatório (selecione um slot).',
        ]);

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
        // CASO 1: RESERVA PONTUAL (is_fixed = false)
        // ==========================================================
        if (!$isFixed) {
            // Checa conflito contra pontuais E fixas
            if ($this->checkOverlap($date, $startTime, $endTime, false)) {
                return redirect()->back()
                    ->with('error', 'Conflito! O horário já está ocupado por uma reserva pontual ou fixa existente.')
                    ->withInput();
            }

            // Pega o dia da semana (0-6)
            $dayOfWeek = Carbon::parse($date)->dayOfWeek;

            try {
                // INJEÇÃO CRÍTICA DO manager_id
                Reserva::create([
                    'user_id' => null, // Admin está criando para um cliente
                    'manager_id' => $managerId, // ID do admin logado (agora é a variável)
                    'schedule_id' => $validatedData['schedule_id'],
                    'date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'price' => $validatedData['price'],
                    'client_name' => $validatedData['client_name'],
                    // Usa o valor JÁ LIMPO do request
                    'client_contact' => $request->input('client_contact'),
                    'notes' => $validatedData['notes'] ?? null,
                    'status' => Reserva::STATUS_CONFIRMADA, // Admin confirma direto
                    'is_fixed' => false,
                    'day_of_week' => $dayOfWeek, // Salva o dia da semana
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

        // 1. Checagem de conflito (só precisa checar contra OUTRAS fixas)
        if ($this->checkOverlap($date, $startTime, $endTime, true)) {
            return redirect()->back()
                ->with('error', 'Conflito Fixo! Este dia da semana/horário já está reservado por outra reserva fixa.')
                ->withInput();
        }

        // 2. Preparar dados para a série de 52 semanas (1 ano)
        $startDate = Carbon::parse($date);
        $dayOfWeek = $startDate->dayOfWeek; // O dia da semana (0-6) que será repetido
        $seriesId = (string) Str::uuid(); // ID único para agrupar a série
        $totalWeeks = 52;
        $reservasCriadas = 0;
        $reservasFalhadas = 0;
        $datasPuladas = [];

        // 3. Usar Transação de DB (Se uma falhar, todas falham)
        DB::beginTransaction();

        try {
            for ($i = 0; $i < $totalWeeks; $i++) {
                $currentDate = $startDate->copy()->addWeeks($i);
                $currentDateString = $currentDate->toDateString();

                // 4. Checagem de conflito PONTUAL
                // (Já checamos as FIXAS. Agora, para cada data, checamos se uma reserva PONTUAL está no caminho)
                if ($this->checkOverlap($currentDateString, $startTime, $endTime, false)) {
                    $reservasFalhadas++;
                    $datasPuladas[] = $currentDate->format('d/m/Y');
                    continue; // Pula esta semana e vai para a próxima
                }

                // 5. Criar a reserva da semana
                // INJEÇÃO CRÍTICA DO manager_id
                Reserva::create([
                    'user_id' => null,
                    'manager_id' => $managerId, // ID do admin logado (agora é a variável)
                    'schedule_id' => $validatedData['schedule_id'],
                    'date' => $currentDateString, // A data específica desta semana
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'price' => $validatedData['price'],
                    'client_name' => $validatedData['client_name'],
                    // Usa o valor JÁ LIMPO do request
                    'client_contact' => $request->input('client_contact'),
                    'notes' => $validatedData['notes'] ?? null,
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => true,
                    'day_of_week' => $dayOfWeek, // O mesmo dia da semana para todos
                    'recurrent_series_id' => $seriesId, // O mesmo ID de série para todos
                    'week_index' => $i, // O índice (0-51)
                ]);

                $reservasCriadas++;
            }

            // 6. Sucesso! Salva tudo no banco.
            DB::commit();

            // 7. Preparar mensagens de feedback
            $successMessage = "Série de {$reservasCriadas} reservas fixas criada com sucesso!";
            $warningMessage = null;

            if ($reservasFalhadas > 0) {
                $warningMessage = "{$reservasFalhadas} datas foram puladas por já estarem ocupadas: " . implode(', ', $datasPuladas);
            }

            return redirect()->route('admin.reservas.create')
                             ->with('success', $successMessage)
                             ->with('warning', $warningMessage); // O create.blade.php já sabe exibir 'warning'

        } catch (\Exception $e) {
            // 8. Falha! Desfaz tudo.
            DB::rollBack();
            Log::error("Erro ao criar série de reservas fixas (Admin): " . $e->getMessage());
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
     * Rota: POST /reservas (name: 'reservas.store')
     */
    public function storePublic(Request $request)
    {
        // === DEBUG CRÍTICO E SANITIZAÇÃO AGRESSIVA ===
        $contactValue = $request->input('contato_cliente', '');
        // 🛑 LIMPEZA CRÍTICA: Remove TUDO que não for dígito (0-9).
        $cleanedContact = preg_replace('/\D/', '', $contactValue);
        $request->merge(['contato_cliente' => $cleanedContact]);

        // Loga o valor FINAL que o Validator verá
        Log::info("DEBUG VALIDAÇÃO CRÍTICA (storePublic):");
        Log::info(" - Contato Original: '{$contactValue}'");
        Log::info(" - Contato Limpo (Regex): '{$cleanedContact}'");
        Log::info(" - Comprimento (Limpo): " . strlen($cleanedContact));
        // ===============================================


        // 1. Definição manual das regras (Regras do Request + Correção do Contato)
        $rules = [
            'nome_cliente'      => ['required', 'string', 'max:255'],
            // A validação 'digits_between' agora trabalha sobre o campo limpo
            'contato_cliente'   => ['required', 'digits_between:10,11'],
            // Regra: Data não pode ser passada
            // CORREÇÃO DE LÓGICA: Apenas garante que a data seja HOJE ou futura.
            'data_reserva'      => ['required', 'date', "after_or_equal:" . Carbon::today()->format('Y-m-d')],
            'hora_inicio'       => ['required', 'date_format:H:i'],
            'hora_fim'          => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'price'             => ['required', 'numeric', 'min:0'],
            'schedule_id'       => ['required', 'integer', 'exists:schedules,id'],
            'is_fixed'          => ['sometimes', 'boolean'],
        ];

        // 2. Validação Manual com mensagens personalizadas
        $validator = Validator::make($request->all(), $rules, [
            'nome_cliente.required' => 'O nome do cliente é obrigatório.',
            'contato_cliente.required' => 'O contato do cliente é obrigatório.',
            // Nova mensagem de erro para digits_between
            'contato_cliente.digits_between' => 'O contato deve ter 10 ou 11 dígitos (apenas números, incluindo o DDD).',
            'data_reserva.required' => 'A data da reserva é obrigatória.',
            'data_reserva.after_or_equal' => 'Não é possível agendar em uma data passada.',
            'hora_inicio.required' => 'O horário de início é obrigatório (selecione um slot).',
            'hora_fim.after' => 'O horário final deve ser posterior ao horário de início.',
        ]);


        if ($validator->fails()) {
            // 🛑 DEBUG AGRESSIVO: Loga a requisição e os erros no console do backend.
            Log::error("=================================================");
            Log::error("FALHA DE VALIDAÇÃO EM storePublic");
            Log::error("DADOS RECEBIDOS:", $request->all());
            Log::error("ERROS DETALHADOS:", $validator->errors()->toArray());
            Log::error("=================================================");

            // Retorna o redirect padrão para exibir os erros no front-end
            return redirect()->back()
                             ->withErrors($validator)
                             ->withInput()
                             ->with('error', 'Correção Necessária! Por favor, verifique os campos destacados em vermelho e tente novamente.');
        }

        // --- SE A VALIDAÇÃO PASSAR, O CÓDIGO A SEGUIR É EXECUTADO ---

        $validated = $validator->validated();

        // Mapeamento dos nomes de campo
        $date = $validated['data_reserva'];
        $startTime = $validated['hora_inicio'];
        $endTime = $validated['hora_fim'];
        $clientName = $validated['nome_cliente'];
        // Pega o valor LIMPO do Request
        $clientContact = $request->input('contato_cliente');
        $price = $validated['price'];

        $isFixed = $request->input('is_fixed', false);

        // ✅ Checagem unificada de conflito
        if ($this->checkOverlap($date, $startTime, $endTime, $isFixed)) {
            $message = $isFixed
                ? 'Desculpe, este horário fixo já está ocupado por outra reserva fixa no dia da semana. Por favor, escolha outro.'
                : 'Desculpe, este horário está em conflito com uma reserva existente (pontual ou fixa). Por favor, verifique a duração e escolha outro.';

            return redirect()->route('reserva.index')->with('error', $message);
        }

        // Determina o day_of_week para o registro
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;


        $reserva = Reserva::create([
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'client_name' => $clientName,
            'client_contact' => $clientContact, // Salva o valor LIMPO
            'price' => $price,
            'status' => Reserva::STATUS_PENDENTE, // Usando constante
            'is_fixed' => $isFixed, // Adiciona is_fixed
            'day_of_week' => $dayOfWeek, // Garante que o dia da semana é salvo
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
                // 💡 Corrigindo a passagem de $date para usar toDateString() por segurança, já que é um objeto Carbon.
                $date = $reserva->date->toDateString();
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

            // 3. Atualiza o manager_id se estivermos confirmando/alterando status de algo que era Pendente (cliente)
            // e atribui o ID do gestor logado
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
