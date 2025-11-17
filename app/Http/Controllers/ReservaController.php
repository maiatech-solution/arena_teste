<?php

namespace App\Http\Controllers;

use App\Models\ArenaConfiguration;
use App\Models\Reserva;
use App\Models\User;
use App\Http\Requests\UpdateReservaStatusRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash; // Necessário para criar senhas temporárias
use Illuminate\Support\Str; // Necessário para gerar strings aleatórias
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ReservaController extends Controller
{
    // Opcional: Define constantes internas para legibilidade, assumindo que o Model Reserva as define.
    private const STATUS_PENDENTE = 'pending';
    private const STATUS_CONFIRMADA = 'confirmed';
    private const STATUS_CANCELADA = 'cancelled';
    private const STATUS_REJEITADA = 'rejected';

    /**
     * Exibe a página pública de agendamento (que carrega os slots via API).
     */
    public function index()
    {
        return view('reserva.index');
    }

    /**
     * Exibe o Dashboard administrativo (incluindo o alerta de renovação).
     */
    public function dashboard()
    {
        // Puxa as séries recorrentes a expirar (Usando o método corrigido deste Controller)
        $expiringSeries = $this->getEndingRecurrentSeries();

        return view('dashboard', [
            'expiringSeriesCount' => count($expiringSeries),
            'expiringSeries' => $expiringSeries,
        ]);
    }


    // =========================================================================
    // MÉTODOS AUXILIARES (CheckOverlap e Conflicting IDs)
    // =========================================================================

    /**
     * Checa sobreposição de horários (para validação do Controller).
     * @return bool Retorna true se houver sobreposição.
     */
    public function checkOverlap(string $date, string $startTime, string $endTime, bool $isFixed, ?int $ignoreReservaId = null): bool
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Normaliza as horas (se vierem como string "H:i")
        $startTimeNormalized = Carbon::createFromFormat('H:i:s', $startTime) ? $startTime : Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('H:i:s', $endTime) ? $endTime : Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');

        // Query base para sobreposição de tempo (somente status que ocupam o slot)
        $baseQuery = Reserva::query()
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->when($ignoreReservaId, function ($query) use ($ignoreReservaId) {
                return $query->where('id', '!=', $ignoreReservaId);
            })
            ->where(function ($query) use ($startTimeNormalized, $endTimeNormalized) {
                // Lógica de sobreposição: (A_start < B_end) AND (B_start < A_end)
                $query->where('start_time', '<', $endTimeNormalized)
                    ->where('end_time', '>', $startTimeNormalized);
            });

        if ($isFixed) {
            // Lógica para criação/edição de slots FIXOS (Admin)

            // Um slot fixo não pode conflitar com outro slot fixo no mesmo dia da semana
            $conflitoComOutraFixa = (clone $baseQuery)
                ->where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->exists();

            if ($conflitoComOutraFixa) { return true; }

            // Nem pode conflitar com uma reserva pontual/recorrente na data específica
            $conflitoPontualNaPrimeiraData = (clone $baseQuery)
                ->where('date', $date)
                ->where('is_fixed', false)
                ->exists();

            return $conflitoPontualNaPrimeiraData;

        } else {
            // Lógica para criação de RESERVA PONTUAL/RECORRENTE (Cliente/Admin)

            // Se tentamos criar uma pontual, ela não pode conflitar com outra reserva REAL (pontual/recorrente)
            $conflitoNaDataExata = (clone $baseQuery)
                ->where('date', $date)
                ->where('is_fixed', false) // <--- CRÍTICO: Filtra para checar APENAS reservas de clientes
                ->exists();

            return $conflitoNaDataExata;
        }
    }


    /**
     * Função auxiliar para buscar os IDs conflitantes para feedback (uso interno do Admin).
     */
    protected function getConflictingReservaIds(string $date, string $startTime, string $endTime, ?int $ignoreReservaId = null)
    {
        $activeStatuses = [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA];

        $conflictingReservas = Reserva::whereIn('status', $activeStatuses)
            ->where('is_fixed', false) // Checa APENAS reservas de clientes (pontuais/recorrentes)
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


    /**
     * Recria o slot fixo para que o horário volte a ficar disponível no calendário (usado no cancelamento).
     */
    protected function recreateFixedSlot(Reserva $originalReserva): void
    {
        if ($originalReserva->is_recurrent) {
            Log::info("Slot ID {$originalReserva->id} é recorrente. Ignorando recriação automática.");
            return;
        }

        $existsFixedSlot = Reserva::where('is_fixed', true)
            ->where('date', $originalReserva->date)
            ->where('start_time', $originalReserva->start_time)
            ->where('end_time', $originalReserva->end_time)
            ->exists();

        if ($existsFixedSlot) {
            Log::info("Slot fixo já existe para {$originalReserva->date->format('Y-m-d')} {$originalReserva->start_time}. Recriação ignorada.");
            return;
        }

        Reserva::create([
            'date' => $originalReserva->date,
            'day_of_week' => $originalReserva->day_of_week,
            'start_time' => $originalReserva->start_time,
            'end_time' => $originalReserva->end_time,
            'price' => $originalReserva->price,
            'status' => Reserva::STATUS_CONFIRMADA,
            'is_fixed' => true,
            'client_name' => 'Slot Fixo de 1h',
            'client_contact' => null,
            'user_id' => null,
            'manager_id' => null,
            'recurrent_series_id' => null,
            'notes' => 'Recriado após cancelamento (ID original: ' . $originalReserva->id . ')'
        ]);

        Log::info("Slot fixo recriado após cancelamento da Reserva ID: {$originalReserva->id}.");
    }


    // =========================================================================
    // 🗓️ MÉTODOS API PARA O DASHBOARD (AGENDAMENTO RÁPIDO)
    // =========================================================================

    public function storeQuickReservaApi(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:G:i',
            'end_time' => 'required|date_format:G:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'reserva_id_to_update' => 'required|exists:reservas,id',
            'user_id' => 'nullable|exists:users,id',
            'client_name' => [Rule::requiredIf(empty($request->input('user_id'))), 'nullable', 'string', 'max:255'],
            'client_contact' => [Rule::requiredIf(empty($request->input('user_id'))), 'nullable', 'string', 'max:255'],
            'notes' => 'nullable|string',
        ], [
            'reserva_id_to_update.exists' => 'O slot de horário selecionado não existe ou não está disponível.',
            'client_name.required_without' => 'O Nome do Cliente é obrigatório se nenhum cliente registrado for selecionado.',
            'client_contact.required_without' => 'O Contato do Cliente é obrigatório se nenhum cliente registrado for selecionado.',
        ]);

        $reservaIdToUpdate = $validated['reserva_id_to_update'];
        $startTimeNormalized = Carbon::createFromFormat('G:i', $validated['start_time'])->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $validated['end_time'])->format('H:i:s');

        $oldReserva = Reserva::find($reservaIdToUpdate);

        // 1. Checagens de Segurança
        if (!$oldReserva || !$oldReserva->is_fixed || $oldReserva->status !== Reserva::STATUS_CONFIRMADA) {
            return response()->json(['success' => false, 'message' => 'O slot selecionado não é um horário fixo disponível.'], 409);
        }

        // 2. Checagem de Conflito Final (contra reservas reais)
        if ($this->checkOverlap($validated['date'], $startTimeNormalized, $endTimeNormalized, false)) {
            $conflictingIds = $this->getConflictingReservaIds($validated['date'], $startTimeNormalized, $endTimeNormalized, null);
            return response()->json([
                'success' => false,
                'message' => 'Conflito: O horário acabou de ser agendado por outro cliente. (IDs: ' . $conflictingIds . ')'], 409);
        }


        // 3. Prepara os dados
        $clientName = $validated['client_name'];
        $clientContact = $validated['client_contact'];
        $userId = $validated['user_id'];

        if ($userId) {
            $user = User::find($userId);
            $clientName = $user->name;
            $clientContact = $user->whatsapp_contact ?? $user->email;
        }

        DB::beginTransaction();
        try {
            // 4. Deleta o slot fixo de disponibilidade (o evento verde)
            $oldReserva->delete();

            // 5. Cria a nova reserva real do cliente (o evento azul)
            $newReserva = Reserva::create([
                'user_id' => $userId,
                'date' => $validated['date'],
                'day_of_week' => Carbon::parse($validated['date'])->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $validated['price'],
                'client_name' => $clientName,
                'client_contact' => $clientContact,
                'notes' => $validated['notes'] ?? null,
                'status' => Reserva::STATUS_CONFIRMADA,
                'is_fixed' => false,
                'is_recurrent' => false,
                'manager_id' => Auth::id(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => "Agendamento pontual para {$clientName} confirmado com sucesso!"], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar agendamento rápido (ID slot: {$reservaIdToUpdate}): " . $e->getMessage());

            if ($oldReserva) {
                // Tentativa de recriar o slot fixo em caso de falha de transação
                $this->recreateFixedSlot($oldReserva);
            }

            return response()->json(['success' => false, 'message' => 'Erro interno ao processar o agendamento: ' . $e->getMessage()], 500);
        }
    }


    /**
     * API: Cria uma série recorrente (anual) a partir do Agendamento Rápido do Dashboard.
     * 🛑 Inclui a lógica robusta para pular slots faltantes/conflitantes.
     */
    public function storeRecurrentReservaApi(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:G:i',
            'end_time' => 'required|date_format:G:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'reserva_id_to_update' => 'required|exists:reservas,id', // O ID do slot FIXO inicial

            'user_id' => 'nullable|exists:users,id',
            'client_name' => [Rule::requiredIf(empty($request->input('user_id'))), 'nullable', 'string', 'max:255'],
            'client_contact' => [Rule::requiredIf(empty($request->input('user_id'))), 'nullable', 'string', 'max:255'],

            'notes' => 'nullable|string',
        ], [
            'reserva_id_to_update.exists' => 'O slot de horário selecionado não existe ou não está disponível.',
            'client_name.required_without' => 'O Nome do Cliente é obrigatório se nenhum cliente registrado for selecionado.',
            'client_contact.required_without' => 'O Contato do Cliente é obrigatório se nenhum cliente registrado for selecionado.',
        ]);

        $initialDate = Carbon::parse($validated['date']);
        $dayOfWeek = $initialDate->dayOfWeek;

        $startTimeNormalized = Carbon::createFromFormat('G:i', $validated['start_time'])->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $validated['end_time'])->format('H:i:s');

        $price = $validated['price'];
        $scheduleId = $validated['reserva_id_to_update'];

        // Define a janela de agendamento (Exatamente 1 ano a partir da data inicial)
        $endDate = $initialDate->copy()->addYear()->subDay();

        // 1. Prepara os dados do cliente
        $clientName = $validated['client_name'];
        $clientContact = $validated['client_contact'];
        $userId = $validated['user_id'];

        if ($userId) {
            $user = User::find($userId);
            $clientName = $user->name;
            $clientContact = $user->whatsapp_contact ?? $user->email;
        }

        // 2. Coleta todas as datas futuras para este dia da semana dentro da janela
        $datesToSchedule = [];
        $date = $initialDate->copy();
        while ($date->lte($endDate)) {
            $datesToSchedule[] = $date->toDateString();
            $date->addWeek();
        }

        // 🛑 LÓGICA DE CHECAGEM RECORRENTE MODIFICADA
        $masterReservaId = null;
        $newReservasCount = 0;
        $conflictCount = 0;
        $reservasToCreate = [];
        $fixedSlotsToDelete = [];

        foreach ($datesToSchedule as $dateString) {
            $currentDate = Carbon::parse($dateString);
            $isFirstDate = $currentDate->toDateString() === $initialDate->toDateString();
            $isConflict = false;

            // 1. Checa conflito contra reservas *reais* de outros clientes
            $overlapWithReal = $this->checkOverlap($dateString, $startTimeNormalized, $endTimeNormalized, false);

            // 2. Busca o slot fixo ATIVO (confirmed) para esta data/hora
            $fixedSlotQuery = Reserva::where('is_fixed', true)
                                     ->whereDate('date', $dateString)
                                     ->where('start_time', $startTimeNormalized)
                                     ->where('end_time', $endTimeNormalized)
                                     ->where('status', Reserva::STATUS_CONFIRMADA);

            if ($isFirstDate) {
                // Para o primeiro slot, o ID deve ser o ID que foi clicado no calendário
                $fixedSlotQuery->where('id', $scheduleId);
            }

            $fixedSlot = $fixedSlotQuery->first();


            if ($overlapWithReal) {
                $isConflict = true;
            } else if (!$fixedSlot) {
                 $isConflict = true; // O slot estava ocupado/ausente
            }

            if (!$isConflict) {
                $fixedSlotsToDelete[] = $fixedSlot->id;

                $reservasToCreate[] = [
                    'user_id' => $userId,
                    'date' => $dateString,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTimeNormalized,
                    'end_time' => $endTimeNormalized,
                    'price' => $price,
                    'client_name' => $clientName,
                    'client_contact' => $clientContact,
                    'notes' => $validated['notes'] ?? null,
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => false,
                    'manager_id' => Auth::id(),
                    'is_recurrent' => true,
                    'recurrent_series_id' => null,
                ];
            } else {
                 $conflictCount++;
            }
        }

        // 3. Checagem final de integridade:
        if (empty($reservasToCreate)) {
            $message = "ERRO: O sistema não conseguiu agendar o slot inicial. Há um conflito ativo ou o slot inicial foi removido. Cheque o calendário manualmente.";
            if ($conflictCount > 0) {
                 $message = "ERRO: O sistema não conseguiu criar a série. {$conflictCount} datas foram puladas/conflitantes, incluindo a inicial. Cheque o calendário manualmente.";
            }
            return response()->json(['success' => false, 'message' => $message], 409);
        }

        // FIM DA LÓGICA DE CHECAGEM MODIFICADA


        DB::beginTransaction();
        $masterReservaId = null;
        try {
            // 4. Deleta todos os slots fixos válidos
            Reserva::whereIn('id', $fixedSlotsToDelete)->delete();

            // 5. Cria a série de reservas reais
            foreach ($reservasToCreate as $reservaData) {

                $newReserva = Reserva::create($reservaData);

                if ($masterReservaId === null) {
                    $masterReservaId = $newReserva->id;
                    $newReserva->update(['recurrent_series_id' => $masterReservaId]);
                } else {
                    $newReserva->update(['recurrent_series_id' => $masterReservaId]);
                }

                $newReservasCount++;
            }

            DB::commit();

            $message = "Série recorrente de {$clientName} criada com sucesso! Total de {$newReservasCount} reservas agendadas até " . $endDate->format('d/m/Y') . ".";
            if ($conflictCount > 0) {
                 $message .= " Atenção: {$conflictCount} datas foram puladas/conflitantes e não foram agendadas. Verifique o calendário.";
            }

            return response()->json(['success' => true, 'message' => $message], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar série recorrente: " . $e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Erro interno ao criar série recorrente: Transação falhou. ' . $e->getMessage()], 500);
        }
    }


    // =========================================================================
    // 🛑 LÓGICA DE RENOVAÇÃO (CORRIGIDA COM CHECAGEM DE DUPLICIDADE) 🛑
    // =========================================================================

    /**
     * Encontra a data máxima de uma série recorrente (que não seja um slot fixo).
     * @param int $masterId ID da série (que é o ID da primeira reserva).
     * @return Carbon|null A data de expiração ou null se a série não for encontrada.
     */
    protected function getSeriesMaxDate(int $masterId): ?Carbon
    {
        $maxDate = Reserva::where(function($query) use ($masterId) {
                // Busca o mestre ou os membros
                $query->where('id', $masterId)
                    ->orWhere('recurrent_series_id', $masterId);
            })
            ->where('is_recurrent', true)
            ->where('is_fixed', false)
            ->where('status', Reserva::STATUS_CONFIRMADA)
            ->max('date');

        return $maxDate ? Carbon::parse($maxDate) : null;
    }

    /**
     * Identifica as séries recorrentes ativas que estão terminando nos próximos 60 dias.
     */
    public function getEndingRecurrentSeries(): array
    {
        $cutoffDate = Carbon::now()->addDays(60)->endOfDay();
        $today = Carbon::now()->startOfDay();

        // 1. Encontra o ID da última reserva para cada série (MAX(date) por recurrent_series_id)
        $latestReservations = Reserva::selectRaw('recurrent_series_id, MAX(date) as last_date, MIN(start_time) as slot_time, MAX(price) as slot_price')
            ->where('is_recurrent', true)
            ->where('is_fixed', false)
            ->where('status', Reserva::STATUS_CONFIRMADA)
            ->groupBy('recurrent_series_id')
            ->get();

        $expiringSeries = [];

        foreach ($latestReservations as $latest) {
            $lastDate = Carbon::parse($latest->last_date);

            // 2. Filtra as séries que expiram DENTRO da janela (hoje até +60 dias)
            if ($lastDate->greaterThanOrEqualTo($today) && $lastDate->lessThanOrEqualTo($cutoffDate)) {

                // 3. Busca a reserva MESTRE (onde id = recurrent_series_id) para obter o nome do cliente.
                // O ID Mestre é o recurrent_series_id encontrado.
                $masterReserva = Reserva::find($latest->recurrent_series_id);

                if ($masterReserva) {
                    $expiringSeries[] = [
                        'master_id' => $masterReserva->id,
                        'client_name' => $masterReserva->client_name,
                        'slot_time' => Carbon::parse($latest->slot_time)->format('H:i'),
                        'slot_price' => $latest->slot_price,
                        'day_of_week' => $masterReserva->day_of_week,
                        'last_date' => $lastDate->format('Y-m-d'),
                    ];
                }
            }
        }

        return $expiringSeries;
    }


    /**
     * API: Estende uma série de reservas recorrentes por mais um ano.
     */
    public function renewRecurrentSeries(Request $request, Reserva $masterReserva)
    {
        if (!$masterReserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'A reserva fornecida não é a mestra de uma série recorrente.'], 400);
        }

        // 1. Encontrar a data de expiração ATUAL (última data na série)
        $currentMaxDate = $this->getSeriesMaxDate($masterReserva->id);

        if (!$currentMaxDate) {
            return response()->json(['success' => false, 'message' => 'Nenhuma reserva confirmada encontrada para esta série.'], 404);
        }

        // 2. Definir a janela de renovação
        // Data de início: A próxima ocorrência após a data máxima atual.
        $startDate = $currentMaxDate->copy()->addDay()->next($masterReserva->day_of_week);

        // Data final da renovação: 1 ano após a data máxima atual.
        $endDate = $currentMaxDate->copy()->addYear();

        if ($startDate->greaterThan($endDate)) {
             return response()->json(['success' => false, 'message' => 'A série já está totalmente coberta até ' . $endDate->format('d/m/Y') . '.'], 400);
        }

        // Parâmetros da série
        $dayOfWeek = $masterReserva->day_of_week;
        $startTime = $masterReserva->start_time;
        $endTime = $masterReserva->end_time;
        $price = $masterReserva->price;
        $clientName = $masterReserva->client_name;
        $clientContact = $masterReserva->client_contact;
        $userId = $masterReserva->user_id;
        $masterId = $masterReserva->id;
        $managerId = Auth::id();

        $newReservasCount = 0;

        DB::beginTransaction();
        try {
            // 3. Loop de renovação: Avança de semana em semana
            $currentDate = $startDate->copy();
            $conflictedOrSkippedCount = 0;

            // Loopa até a data final, limitando a 60 para evitar loops infinitos (1 ano + segurança)
            while ($currentDate->lessThanOrEqualTo($endDate) && $newReservasCount + $conflictedOrSkippedCount < 60) {
                $dateString = $currentDate->toDateString();
                $isConflict = false;

                // 3.1. Checagem de Duplicidade: Verifica se a reserva JÁ EXISTE para ESTA SÉRIE (Integridade de Dados)
                $isDuplicate = Reserva::whereDate('date', $dateString)
                    ->where('start_time', $startTime)
                    ->where('end_time', $endTime)
                    ->where('recurrent_series_id', $masterId) // CRÍTICO: Checa se é uma duplicação da própria série
                    ->where('is_fixed', false)
                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                    ->exists();

                if ($isDuplicate) {
                    $isConflict = true;
                    Log::info("Duplicação detectada para série #{$masterId} na data {$dateString}. Slot pulado.");
                }

                // 3.2. Checagem de Conflito (Outros Clientes): Deve estar livre de outras reservas REAIS.
                if (!$isConflict) {
                     $isOccupiedByRealCustomer = Reserva::whereDate('date', $dateString)
                        ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime)
                        ->where('is_fixed', false) // Check APENAS contra outras reservas reais (clientes)
                        ->where('recurrent_series_id', '!=', $masterId) // Exclui membros da PRÓPRIA série
                        ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                        ->exists();

                    if ($isOccupiedByRealCustomer) {
                        $isConflict = true;
                        Log::warning("Conflito com OUTRO CLIENTE para série #{$masterId} na data {$dateString}. Slot pulado.");
                    }
                }

                // 3.3. Busca o slot fixo, se existir, para DELETAR (consumir)
                $fixedSlot = null;
                if (!$isConflict) {
                    $fixedSlot = Reserva::where('is_fixed', true)
                        ->whereDate('date', $dateString)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTime)
                        ->where('status', Reserva::STATUS_CONFIRMADA)
                        ->first();
                }

                // 3.4. Cria a nova reserva se não houver conflito REAL nem duplicação
                if (!$isConflict) {
                    // Cria a nova reserva recorrente
                    Reserva::create([
                        'user_id' => $userId,
                        'manager_id' => $managerId,
                        'date' => $dateString,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => $price,
                        'client_name' => $clientName,
                        'client_contact' => $clientContact,
                        'status' => Reserva::STATUS_CONFIRMADA,
                        'is_fixed' => false, // Cliente real
                        'is_recurrent' => true,
                        'recurrent_series_id' => $masterId,
                    ]);
                    $newReservasCount++;

                    // 🛑 Deleta o slot fixo de disponibilidade, SE ENCONTRADO
                    if ($fixedSlot) {
                        $fixedSlot->delete();
                    } else {
                        // Loga que o slot fixo estava ausente, mas a reserva foi criada
                        Log::warning("Slot fixo ausente para série #{$masterId} na data {$dateString}. Reserva criada sem consumir slot verde.");
                    }
                } else {
                    $conflictedOrSkippedCount++;
                }

                // AVANÇA UMA SEMANA INTEIRA
                $currentDate->addWeek();
            }

            DB::commit();

            if ($newReservasCount > 0) {
                $message = "Série #{$masterId} de '{$clientName}' renovada com sucesso! Foram adicionadas {$newReservasCount} novas reservas, estendendo o prazo até " . $endDate->format('d/m/Y') . ".";

                if ($conflictedOrSkippedCount > 0) {
                     $message .= " Atenção: {$conflictedOrSkippedCount} slots foram pulados devido a conflitos ou duplicações anteriores.";
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ], 200);
            } else {
                 $message = "Falha na renovação: Nenhuma nova reserva foi adicionada. Razões: O período já está totalmente coberto, ou todos os slots futuros encontrados têm conflitos com outros clientes, ou já são duplicatas desta série. Total de slots pulados: {$conflictedOrSkippedCount}.";
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro fatal na renovação de série #{$masterId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro interno do servidor ao renovar a série: ' . $e->getMessage()], 500);
        }
    }


    // =========================================================================
    // 👤 CANCELAMENTO PELO CLIENTE (FRONT-END)
    // =========================================================================
    public function cancelByCustomer(Request $request, Reserva $reserva)
    {
        $user = Auth::user();
        if (!$user || $reserva->user_id !== $user->id) {
            return response()->json(['message' => 'Não autorizado ou a reserva não pertence a você.'], 403);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
        ]);

        $reservaDateTime = Carbon::parse($reserva->date->format('Y-m-d') . ' ' . $reserva->start_time);

        if ($reservaDateTime->isPast()) {
            return response()->json(['message' => 'Esta reserva é no passado e não pode ser cancelada.'], 400);
        }
        if ($reserva->status === Reserva::STATUS_CANCELADA || $reserva->status === Reserva::STATUS_REJEITADA) {
            return response()->json(['message' => 'Esta reserva já está cancelada ou rejeitada.'], 400);
        }

        if ($reserva->is_recurrent) {
            return response()->json(['message' => 'Esta é uma reserva recorrente. Entre em contato com o Gestor para gerenciar séries.'], 400);
        }

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->cancellation_reason = '[Cliente] ' . $validated['cancellation_reason'];
            $reserva->save();

            $this->recreateFixedSlot($reserva); // Chama o helper

            $reserva->delete();

            DB::commit();
            Log::info("Reserva ID: {$reserva->id} cancelada pelo cliente ID: {$user->id}. Slot fixo recriado.");

            return response()->json(['success' => true, 'message' => 'Reserva cancelada com sucesso! O slot foi liberado.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar reserva pelo cliente ID: {$user->id}. Reserva ID: {$reserva->id}. Erro: " . $e->getMessage());
            return response()->json(['message' => 'Ocorreu um erro ao processar o cancelamento. Tente novamente.'], 500);
        }
    }

    /**
     * Salva a pré-reserva (Formulário Público) - FLUXO SEM LOGIN.
     */
    public function storePublic(Request $request)
    {
        // Mínimo 10 anos de idade para agendar (regra de negócio implícita)
        //$minAgeDate = Carbon::now()->subYears(10)->toDateString();

        $rules = [
            'data_reserva' => ['required', 'date', "after_or_equal:" . Carbon::today()->format('Y-m-d')],
            'hora_inicio' => ['required', 'date_format:G:i'],
            'hora_fim' => ['required', 'date_format:G:i', 'after:hora_inicio'],
            'price' => ['required', 'numeric', 'min:0'],
            'schedule_id' => ['required', 'integer', 'exists:reservas,id,is_fixed,1,status,' . Reserva::STATUS_CONFIRMADA], // Deve ser um slot FIXO e CONFIRMADO
            'reserva_conflito_id' => 'nullable',

            // 🛑 NOVOS CAMPOS DO CLIENTE (Obrigatórios no front-end)
            'nome_cliente' => 'required|string|max:255',
            'contato_cliente' => 'required|string|size:11|regex:/^\d+$/', // Aceita apenas 11 dígitos            
            'email_cliente' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:500',
        ];

        $validator = Validator::make($request->all(), $rules, [
            'schedule_id.exists' => 'O slot de horário selecionado não está mais disponível ou não é um horário válido.',
            'schedule_id.required' => 'O horário não foi selecionado corretamente. Tente selecionar o slot novamente no calendário.',
            'contato_cliente.regex' => 'O WhatsApp deve conter apenas DDD+ número (Ex: 91900000000).',
        ]);

        if ($validator->fails()) {
            Log::error('[STORE PUBLIC - SEM LOGIN] Erro de Validação:', $validator->errors()->toArray());
            // Retorna ao índice com os erros e input antigo para reabrir o modal.
            return redirect()->route('reserva.index')->withErrors($validator)->withInput()->with('error', 'Correção Necessária! Por favor, verifique os campos destacados em vermelho.');
        }

        $validated = $validator->validated();

        $date = $validated['data_reserva'];
        $startTime = $validated['hora_inicio'];
        $endTime = $validated['hora_fim'];
        $price = $validated['price'];
        $scheduleId = $validated['schedule_id'];
        $nomeCliente = $validated['nome_cliente'];
        $contatoCliente = $validated['contato_cliente'];
        $emailCliente = $validated['email_cliente'];


        $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');

        DB::beginTransaction();
        $isNewUser = false;
        try {
            // 2. Lógica de Match de Usuário ou Criação Automática
            // Tenta encontrar o usuário pelo WhatsApp e Nome
            $user = User::where('whatsapp_contact', $contatoCliente)
                ->where('name', $nomeCliente)
                ->first();

            if (!$user) {
                // Se não encontrou, cria um novo usuário temporário
                $tempPassword = Str::random(12); // Senha aleatória

                // CRÍTICO: Se o email estiver vazio, gera um placeholder único
                $uniqueEmail = $emailCliente ?: 'temp_' . time() . Str::random(5) . '@arena.local';

                $user = User::create([
                    'name' => $nomeCliente,
                    'email' => $uniqueEmail,
                    'whatsapp_contact' => $contatoCliente,
                    'password' => Hash::make($tempPassword),
                    'role' => 'cliente',
                    'email_verified_at' => Carbon::now(),
                ]);

                Log::info("Novo usuário cliente criado via agendamento público: ID {$user->id}. Contato: {$contatoCliente}");
                $isNewUser = true;
            }

            // === 3. Checagem de Conflito FINAL (CRÍTICO) ===
            // Checa se o slot foi ocupado por outro cliente (is_fixed=false)
            if ($this->checkOverlap($date, $startTimeNormalized, $endTimeNormalized, false)) {
                DB::rollBack();
                $validator->errors()->add('reserva_conflito_id', 'ERRO: Este horário acabou de ser reservado ou está em conflito.');
                throw new ValidationException($validator);
            }

            // 4. Limpa o slot fixo (evento verde)
            $fixedSlot = Reserva::where('id', $scheduleId)
                ->where('is_fixed', true)
                ->where('status', Reserva::STATUS_CONFIRMADA)
                ->first();

            if (!$fixedSlot) {
                 DB::rollBack();
                 // O slot já foi consumido ou cancelado por outro processo/usuário
                 $validator->errors()->add('schedule_id', 'O slot selecionado não existe mais.');
                 throw new ValidationException($validator);
            }
            $fixedSlot->delete();


            // 5. Criação da Reserva Real (Status Pendente)
            $reserva = Reserva::create([
                'user_id' => $user->id,
                'date' => $date,
                'day_of_week' => Carbon::parse($date)->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $price,
                'client_name' => $nomeCliente,
                'client_contact' => $contatoCliente,
                'notes' => $validated['notes'] ?? null,
                'status' => Reserva::STATUS_PENDENTE,
                'is_fixed' => false,
            ]);

            DB::commit();

            // 6. Mensagem de Sucesso e Link do WhatsApp (omitida por brevidade)

            $successMessage = $isNewUser
                ? 'Sua conta foi criada automaticamente e a pré-reserva foi registrada.'
                : 'Pré-reserva registrada com sucesso.';

            // Substitua '91985320997' pelo número correto da Arena
            $whatsappNumber = '91985320997';
            $data = Carbon::parse($reserva->date)->format('d/m/Y');
            $hora = Carbon::parse($reserva->start_time)->format('H:i');

            $messageText = "🚨 NOVA PRÉ-RESERVA PENDENTE\n\n" .
                "Cliente: {$reserva->client_name}\n" .
                "Data/Hora: {$data} às {$hora}\n" .
                "Valor: R$ " . number_format($reserva->price, 2, ',', '.') . "\n";

            $whatsappLink = "https://api.whatsapp.com/send?phone={$whatsappNumber}&text=" . urlencode($messageText);


            return redirect()->route('reserva.index')
                ->with('success', $successMessage)
                ->with('whatsapp_link', $whatsappLink);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("[DEBUG STORE PUBLIC] Erro FATAL: " . $e->getMessage() . " - Linha: " . $e->getLine());
            // Cria um erro de validação genérico para ser capturado pelo Blade
            $validator->errors()->add('server_error', 'Erro interno ao processar a reserva. Tente novamente mais tarde.');
            throw new ValidationException($validator);
        }
    }


    /**
     * Retorna a contagem de reservas pendentes para o Dashboard.
     * AGORA CONTA TODAS AS RESERVAS PENDENTES, INDEPENDENTE DA HORA DE INÍCIO/FIM.
     */
    public function countPending()
    {
        $futureOrTodayCount = Reserva::where('status', Reserva::STATUS_PENDENTE)
            ->count();

        return response()->json(['count' => $futureOrTodayCount], 200);
    }
}
