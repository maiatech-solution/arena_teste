<?php

namespace App\Http\Controllers;

use App\Models\ArenaConfiguration;
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
use Illuminate\Validation\Rule;


class ReservaController extends Controller
{
    /**
     * Checa se o horário de uma nova reserva entra em conflito com reservas existentes.
     *
     * @param string $date Data da reserva (YYYY-MM-DD).
     * @param string $startTime Hora de início (HH:MM:SS ou HH:MM).
     * @param string $endTime Hora de fim (HH:MM:SS ou HH:MM).
     * @param bool $isFixed Se a reserva é fixa (criação de série).
     * @param int|null $ignoreReservaId ID da reserva a ser ignorada na checagem.
     * @return bool True se houver conflito, False caso contrário.
     */
    public function checkOverlap(string $date, string $startTime, string $endTime, bool $isFixed, ?int $ignoreReservaId = null): bool
    {
        // 🛑 CRÍTICO: Tornamos este método PUBLIC para que ConfigurationController possa chamá-lo
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Query base para sobreposição de tempo (somente status que ocupam o slot)
        $baseQuery = Reserva::query()
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->when($ignoreReservaId, function ($query) use ($ignoreReservaId) {
                return $query->where('id', '!=', $ignoreReservaId);
            })
            ->where(function ($query) use ($startTime, $endTime) {
                // Lógica de sobreposição de tempo (overlap)
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });

        if ($isFixed) {
            // Se a nova reserva é FIXA (criação de série no /config):
            // 1. Checa conflito com OUTRA SÉRIE FIXA (checa por day_of_week e horário, IGNORANDO a data específica)
            $conflitoComOutraFixa = (clone $baseQuery)
                ->where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->exists();

            if ($conflitoComOutraFixa) {
                return true;
            }

            // 2. Checa conflito PONTUAL na data de INÍCIO (Impede que a série comece em um slot já pontualmente ocupado)
            $conflitoPontualNaPrimeiraData = (clone $baseQuery)
                ->where('date', $date)
                ->exists();

            return $conflitoPontualNaPrimeiraData;

        } else {
            // Se a nova reserva é PONTUAL (cliente ou admin manual),
            // checa conflito contra QUALQUER reserva ATIVA na DATA EXATA.

            $conflitoNaDataExata = (clone $baseQuery)
                ->where('date', $date)
                ->exists();

            return $conflitoNaDataExata;
        }
    }

    /**
     * Função auxiliar para buscar os IDs conflitantes para feedback.
     */
    protected function getConflictingReservaIds(string $date, string $startTime, string $endTime, ?int $ignoreReservaId = null)
    {
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

        return $conflictingReservas->implode(', ');
    }

    // =========================================================================
    // ✅ NOVO MÉTODO: Agendamento Rápido RECORRENTE via Calendário (API)
    // =========================================================================
    public function storeRecurrentReservaApi(Request $request)
    {
        // 1. Validação
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_contact' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
            'reserva_id_to_update' => ['required', 'integer', 'exists:reservas,id'],
            'is_recurrent' => ['nullable', 'boolean'],
        ]);

        $date = $validated['date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $managerId = Auth::id();
        $reservaIdToUpdate = $validated['reserva_id_to_update'];

        // 2. Checagem de Conflito para o primeiro slot (Pontual vs Tudo)
        $slotFixo = Reserva::where('id', $reservaIdToUpdate)
            ->where('is_fixed', true)
            ->where('date', $date)
            ->first();

        // Checa se o slot existe E se há conflito (exclui o próprio slot da checagem)
        if (!$slotFixo || $this->checkOverlap($date, $startTime, $endTime, false, $reservaIdToUpdate)) {
             return response()->json([
                 'success' => false,
                 'message' => 'Conflito! O horário inicial não está mais disponível ou se sobrepõe a outra reserva. Recarregue a página.',
             ], 409);
        }

        // --- 3. CONVERTER TODA A SÉRIE RECORRENTE ---
        DB::beginTransaction();
        try {
            $dayOfWeek = Carbon::parse($date)->dayOfWeek;

            // 3.1. Converte o primeiro slot (clicado) no Mestre da Série
            $slotFixo->update([
                'user_id' => null,
                'manager_id' => $managerId,
                'schedule_id' => null,
                'price' => $validated['price'],
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'],
                'notes' => $validated['notes'] ?? 'Reserva Recorrente - Slot Inicial',
                'status' => Reserva::STATUS_CONFIRMADA,
                'is_fixed' => false, // O slot inicial VIRA a reserva pontual (real)
                'is_recurrent' => true, // ✅ CORREÇÃO: Força o mestre como recorrente (1)
            ]);

            // Captura o ID do slot Mestre para vincular os futuros
            $masterReservaId = $slotFixo->id;

            // 3.2. Localiza e BLOQUEIA os slots futuros correspondentes
            // A data limite é o fim do período de geração (1 ano)
            $endDateLimit = Carbon::today()->addYear()->toDateString();

            $futureFixedSlots = Reserva::where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->whereDate('date', '>', $date) // Apenas datas futuras
                ->whereDate('date', '<', $endDateLimit) // Até o limite de 1 ano de geração
                ->get();

            $countUpdated = 0;

            // 🛑 CRÍTICO: Se o usuário marcou "recorrente" e encontramos 0 slots futuros, ABORTAR!
            // Isso significa que os slots futuros estão ocupados por outra série recorrente.
            if ($futureFixedSlots->isEmpty()) {
                 DB::rollBack();
                 return response()->json([
                     'success' => false,
                     'message' => 'Não é possível criar uma reserva recorrente. Os horários futuros desta série já estão ocupados por outro cliente fixo ou exceções. Por favor, remova a opção Recorrente e agende apenas pontualmente.',
                 ], 409);
            }


            foreach ($futureFixedSlots as $futureSlot) {
                // Converte cada slot fixo em uma reserva confirmada para o cliente
                $futureSlot->update([
                    'user_id' => null,
                    'manager_id' => $managerId,
                    'schedule_id' => null,
                    'price' => $validated['price'],
                    'client_name' => $validated['client_name'],
                    'client_contact' => $validated['client_contact'],
                    'notes' => $validated['notes'] ?? 'Reserva Recorrente - Série',
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => false,
                    'is_recurrent' => true, // ✅ CORREÇÃO: Força o membro como recorrente (1)
                    'recurrent_series_id' => $masterReservaId, // ✅ Vincula ao mestre
                ]);
                $countUpdated++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Reserva Recorrente criada com sucesso! O slot inicial (ID {$masterReservaId}) foi agendado e mais {$countUpdated} slots futuros foram reservados e vinculados.",
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao converter slot fixo em reserva recorrente (API): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao salvar a reserva recorrente.',
            ], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO: Agendamento Rápido Pontual (Atualizado)
    // =========================================================================
    public function storeQuickReservaApi(Request $request)
    {
        // 1. Validação (Validação do 'price' já existe e é correta)
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_contact' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'price' => ['required', 'numeric', 'min:0.01'], // ✅ O PREÇO É RECEBIDO AQUI
            'notes' => ['nullable', 'string', 'max:500'],
            'schedule_id' => ['nullable'], // Não é mais usado, mas mantemos

            // Campo do ID da Reserva Fixa a ser ATUALIZADA/CONVERTIDA
            'reserva_id_to_update' => ['required', 'integer', 'exists:reservas,id'],
        ]);

        $date = $validated['date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $managerId = Auth::id();
        $reservaIdToUpdate = $validated['reserva_id_to_update'];

        // 2. Checagem de Conflito (Pontual vs Tudo)
        $slotFixo = Reserva::where('id', $reservaIdToUpdate)
            ->where('is_fixed', true)
            ->where('date', $date)
            ->first();

        if (!$slotFixo || $this->checkOverlap($date, $startTime, $endTime, false, $reservaIdToUpdate)) {
             return response()->json([
                 'success' => false,
                 'message' => 'Conflito! O horário não está mais disponível ou se sobrepõe a outra reserva. Recarregue a página.',
             ], 409);
        }


        // 3. Criação/Atualização da Reserva (Convertendo o Slot Fixo em Reserva de Cliente)
        DB::beginTransaction();
        try {
            // Atualiza o slot fixo existente com os dados do cliente, convertendo-o em uma reserva pontual
            $slotFixo->update([
                'user_id' => null, // Não há cliente registrado, apenas dados de contato
                'manager_id' => $managerId,
                'schedule_id' => null,
                'price' => $validated['price'], // ✅ O PREÇO É SALVO AQUI
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'],
                'notes' => $validated['notes'] ?? 'Agendamento Rápido via Gestor',
                'status' => Reserva::STATUS_CONFIRMADA, // Já era CONFIRMADA, mas garantimos o status
                'is_fixed' => false, // 🛑 CRÍTICO: MARCA COMO RESERVA PONTUAL REAL!
                'is_recurrent' => false, // Garante que reservas pontuais não são marcadas como recorrentes
                'recurrent_series_id' => null, // Garante que não há vínculo de série
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reserva rápida criada e confirmada com sucesso! O slot fixo foi convertido. O calendário será atualizado.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao converter slot fixo em reserva rápida (API): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao salvar a reserva.',
            ], 500);
        }
    }
    // =========================================================================


    // =========================================================================
    // ✅ MÉTODO: Horários Disponíveis p/ Calendário (API)
    // =========================================================================
    /**
     * Retorna os slots gerados pelas Reservas Fixas (is_fixed=true) que estão disponíveis (GREEN).
     */
    public function getAvailableSlotsApi(Request $request)
    {
        // O FullCalendar envia 'start' e 'end' para delimitar o período
        $startDate = Carbon::parse($request->input('start', Carbon::today()->toDateString()));
        $endDate = Carbon::parse($request->input('end', Carbon::today()->addWeeks(6)->toDateString()));

        // 1. Busca todos os slots de horário fixo (GRADE DE DISPONIBILIDADE)
        $allFixedSlots = Reserva::where('is_fixed', true)
                                 ->whereDate('date', '>=', $startDate->toDateString())
                                 ->whereDate('date', '<=', $endDate->toDateString())
                                 ->where('status', Reserva::STATUS_CONFIRMADA) // Slots que definem a grade
                                 ->get();

        $events = [];

        foreach ($allFixedSlots as $slot) {
            $slotStart = Carbon::parse($slot->start_time);
            $slotEnd = Carbon::parse($slot->end_time);

            // 2. Checa se o slot FIXO está ocupado por uma RESERVA PONTUAL (real cliente)
            $isOccupiedByPunctual = Reserva::where('is_fixed', false)
                                             ->whereDate('date', $slot->date->toDateString())
                                             ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                                             ->where(function ($query) use ($slotStart, $slotEnd) {
                                                 $query->where('start_time', '<', $slotEnd->format('H:i:s'))
                                                       ->where('end_time', '>', $slotStart->format('H:i:s'));
                                             })
                                             ->exists();

            // 3. Checa se o slot FIXO foi marcado como CANCELADO/Indisponível na tela de Config
            $isManuallyCancelled = Reserva::where('is_fixed', true)
                                         ->where('date', $slot->date->toDateString())
                                         ->where('start_time', $slot->start_time)
                                         ->where('status', Reserva::STATUS_CANCELADA)
                                         ->exists();


            // 4. Se o slot NÃO estiver ocupado por um pontual E NÃO estiver manualmente cancelado, ele está DISPONÍVEL (GREEN).
            if (!$isOccupiedByPunctual && !$isManuallyCancelled) {

                $title = "Slot Livre: R$ " . number_format($slot->price, 2, ',', '.');

                $events[] = [
                    'id' => $slot->id,
                    'title' => $title,
                    'start' => $slot->date->format('Y-m-d') . 'T' . $slot->start_time,
                    'end' => $slot->date->format('Y-m-d') . 'T' . $slot->end_time,
                    'color' => '#10b981', // Verde para Disponível (Emerald)
                    'className' => 'fc-event-available',
                    'extendedProps' => [
                        'status' => 'available',
                        'price' => $slot->price, // ✅ INCLUÍDO AQUI
                        'is_fixed' => true,
                    ]
                ];
            }
        }

        return response()->json($events);
    }
    // =========================================================================


    // =========================================================================
    // ✅ MÉTODO: Horários Disponíveis p/ FORMULÁRIO PÚBLICO (HTML)
    // =========================================================================
    /**
     * Calcula e retorna os horários disponíveis para uma data específica (página pública e /admin/reservas/create).
     */
    public function getAvailableTimes(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $dateString = $request->input('date');
        $selectedDate = Carbon::parse($dateString);
        $isToday = $selectedDate->isToday();
        $now = Carbon::now();

        // 1. Busca todos os slots de horário fixo (GRADE DE DISPONIBILIDADE) para esta data
        $allFixedSlots = Reserva::where('is_fixed', true)
                                 ->whereDate('date', $dateString)
                                 ->get();

        // 2. Busca todas as RESERVAS PONTUAIS (ocupações)
        $occupiedReservas = Reserva::where('is_fixed', false)
                                     ->whereDate('date', $dateString)
                                     ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                                     ->get();

        $availableTimes = [];

        // 3. Itera sobre a grade de slots fixos
        foreach ($allFixedSlots as $slot) {
            $slotStart = Carbon::parse($slot->start_time);
            $slotEnd = Carbon::parse($slot->end_time);
            $slotEndDateTime = $selectedDate->copy()->setTime($slotEnd->hour, $slotEnd->minute);

            // Verifica se o slot já passou hoje
            if ($isToday && $slotEndDateTime->lt($now)) {
                continue;
            }

            // Verifica se o slot está CANCELADO/Indisponível (manutenção)
            if ($slot->status === Reserva::STATUS_CANCELADA) {
                continue;
            }

            // Checagem de Conflito: O slot fixo é considerado indisponível se houver uma reserva PONTUAL por cima.
            $isOccupiedByPunctual = $occupiedReservas->contains(function ($reservation) use ($slotStart, $slotEnd) {
                return $reservation->start_time < $slotEnd->format('H:i:s') && $reservation->end_time > $slotStart->format('H:i:s');
            });

            if (!$isOccupiedByPunctual) {
                // Slot disponível
                $availableTimes[] = [
                    'id' => $slot->id, // Usando ID da Reserva Fixa
                    'time_slot' => $slotStart->format('H:i') . ' - ' . $slotEnd->format('H:i'),
                    'price' => number_format($slot->price, 2, ',', '.'),
                    'raw_price' => $slot->price,
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'schedule_id' => $slot->id, // O ID do slot disponível é o ID da Reserva Fixa
                ];
            }
        }

        // Ordena por hora de início
        $finalAvailableTimes = collect($availableTimes)->sortBy('start_time')->values();

        return response()->json($finalAvailableTimes);
    }
    // =========================================================================


    // =========================================================================
    // MÉTODO `storePublic` (MANTIDO)
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

        // 1. Definição manual das regras
        $rules = [
            'nome_cliente'      => ['required', 'string', 'max:255'],
            'contato_cliente'   => ['required', 'digits_between:10,11'],
            'data_reserva'      => ['required', 'date', "after_or_equal:" . Carbon::today()->format('Y-m-d')],
            'hora_inicio'       => ['required', 'date_format:H:i'],
            'hora_fim'          => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'price'             => ['required', 'numeric', 'min:0'],
            'schedule_id'       => ['required', 'integer'], // ID da Reserva Fixa para rastreamento
            'reserva_conflito_id' => 'nullable',
        ];

        // 2. Validação Manual com mensagens personalizadas
        $validator = Validator::make($request->all(), $rules, [
            'contato_cliente.digits_between' => 'O contato deve ter 10 ou 11 dígitos (apenas números, incluindo o DDD).',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput()->with('error', 'Correção Necessária! Por favor, verifique os campos.');
        }

        $validated = $validator->validated();

        $date = $validated['data_reserva'];
        $startTime = $validated['hora_inicio'];
        $endTime = $validated['hora_fim'];
        $price = $validated['price'];

        // === USA O HELPER checkOverlap ===
        if ($this->checkOverlap($date, $startTime, $endTime, false)) {
            $validator->errors()->add('reserva_conflito_id', 'ERRO: Este horário acabou de ser reservado ou está em conflito.');
            throw new ValidationException($validator);
        }

        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // 🛑 CRÍTICO: Criamos a nova reserva PONTUAL do cliente.
        $reserva = Reserva::create([
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'client_name' => $validated['nome_cliente'],
            'client_contact' => $request->input('contato_cliente'),
            'price' => $price,
            'schedule_id' => $validated['schedule_id'], // Mantém o ID da Reserva Fixa para rastreamento
            'status' => Reserva::STATUS_PENDENTE,
            'is_fixed' => false,
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
            "Tipo: RESERVA PONTUAL\n";

        $whatsappLink = "https://api.whatsapp.com/send?phone={$whatsappNumber}&text=" . urlencode($messageText);

        return redirect()->route('reserva.index')
            ->with('whatsapp_link', $whatsappLink)
            ->with('success', 'Pré-reserva enviada! Por favor, entre em contato via WhatsApp para confirmar o agendamento.');
    }
    // =========================================================================


    // =========================================================================
    // MÉTODO `countPending` (MANTIDO)
    // =========================================================================
    /**
     * Retorna a contagem de reservas com status 'pendente' (hoje ou no futuro E AINDA NÃO EXPIRADAS).
     */
    public function countPending()
    {
        $now = Carbon::now();
        $todayString = $now->toDateString();
        $nowTime = $now->format('H:i:s');

        $futureOrTodayCount = Reserva::where('status', Reserva::STATUS_PENDENTE)
            ->whereDate('date', '>=', $todayString)
            ->where(function ($query) use ($todayString, $nowTime) {
                $query->whereDate('date', '>', $todayString)
                      ->orWhere(function ($q) use ($todayString, $nowTime) {
                          $q->whereDate('date', $todayString)
                            ->where('end_time', '>', $nowTime);
                      });
            })
            ->count();

        return response()->json(['count' => $futureOrTodayCount], 200);
    }
    // =========================================================================
}
