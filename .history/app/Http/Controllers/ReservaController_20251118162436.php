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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Http\Controllers\AdminController;

class ReservaController extends Controller
{
    // Opcional: Define constantes internas para legibilidade, assumindo que o Model Reserva as define.
    // 🛑 IMPORTANTE: Estas constantes devem ser definidas no seu modelo 'Reserva'.
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


    // -------------------------------------------------------------------------
    // MÉTODOS AUXILIARES (CheckOverlap e Conflicting IDs)
    // -------------------------------------------------------------------------

    /**
     * Checa sobreposição de horários (para validação do Controller).
     * 🛑 Versão simplificada e robusta de sobreposição.
     * @return bool Retorna true se houver sobreposição.
     */
    public function checkOverlap(string $date, string $startTime, string $endTime, bool $isFixed, ?int $ignoreReservaId = null): bool
    {
        // Normaliza as horas (Formato H:i:s é o padrão para comparação no DB)
        try {
            // Assume que a entrada é G:i, como esperado na validação
            $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
            $endTimeNormalized = Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');
        } catch (\Exception $e) {
            // Em caso de erro de formato, usa o parsing padrão do Carbon
            $startTimeNormalized = Carbon::parse($startTime)->format('H:i:s');
            $endTimeNormalized = Carbon::parse($endTime)->format('H:i:s');
        }

        $query = Reserva::whereDate('date', $date)
            // 1. Checa contra reservas CONFIRMADAS ou PENDENTES (reais ou slots fixos)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]);

        // 2. Ignora a própria reserva se estivermos editando/confirmando uma existente
        if ($ignoreReservaId) {
            $query->where('id', '!=', $ignoreReservaId);
        }

        // 3. Lógica principal de sobreposição: (A_start < B_end) AND (B_start < A_end)
        $query->where(function ($q) use ($startTimeNormalized, $endTimeNormalized) {
            $q->where('start_time', '<', $endTimeNormalized)
              ->where('end_time', '>', $startTimeNormalized);
        });

        return $query->exists();
    }


    /**
     * Função auxiliar para buscar os IDs conflitantes para feedback (uso interno do Admin).
     */
    protected function getConflictingReservaIds(string $date, string $startTime, string $endTime, ?int $ignoreReservaId = null)
    {
        $activeStatuses = [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA];

        // Normaliza as horas para garantir que a consulta SQL seja precisa
        try {
            $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
            $endTimeNormalized = Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');
        } catch (\Exception $e) {
             $startTimeNormalized = Carbon::parse($startTime)->format('H:i:s');
             $endTimeNormalized = Carbon::parse($endTime)->addHour()->format('H:i:s');
        }

        $conflictingReservas = Reserva::whereIn('status', $activeStatuses)
            ->whereDate('date', $date)
            ->when($ignoreReservaId, function ($query) use ($ignoreReservaId) {
                return $query->where('id', '!=', $ignoreReservaId);
            })
            ->where(function ($query) use ($startTimeNormalized, $endTimeNormalized) {
                $query->where('start_time', '<', $endTimeNormalized)
                    ->where('end_time', '>', $startTimeNormalized);
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
            ->where('status', Reserva::STATUS_CONFIRMADA)
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


    // -------------------------------------------------------------------------
    // 👤 LÓGICA DE CLIENTE: ENCONTRAR OU CRIAR (COM EMAIL TEMPORÁRIO)
    // -------------------------------------------------------------------------

    /**
     * Encontra ou cria um usuário cliente (baseado no whatsapp_contact).
     *
     * 🛑 CORRIGIDO: Implementa a lógica de e-mail temporário para satisfazer o DB NOT NULL.
     *
     * @param array $data Contém 'name', 'email', 'whatsapp_contact' e 'data_nascimento'.
     * @return User
     */
    protected function findOrCreateClient(array $data): User
    {
        $contact = $data['whatsapp_contact'];
        $name = $data['name'];
        // Pega o email do input.
        $inputEmail = $data['email'] ?? null;

        $emailToUse = $inputEmail;

        // 🛑 LÓGICA RESTAURADA: Se o email do input estiver vazio, gera um provisório.
        if (empty($inputEmail)) {
            // Gera o e-mail temporário no formato original
            $uniquePart = Str::random(5);
            $emailToUse = "temp_" . time() . "{$uniquePart}" . "@arena.local";
        }

        // 1. Tenta encontrar o usuário pelo WhatsApp
        $user = User::where('whatsapp_contact', $contact)->first();

        if ($user) {
            // 2. Cliente encontrado: Atualiza o nome e email (se aplicável)
            $updateData = ['name' => $name];

            // Atualiza o e-mail APENAS SE:
            // a) O e-mail existente for um temporário (@arena.local) - permitindo upgrade para real,
            // b) OU o cliente forneceu um e-mail real no formulário ($inputEmail) - atualiza o e-mail real.
            if (Str::contains($user->email, '@arena.local') || !empty($inputEmail)) {
                 $updateData['email'] = $emailToUse;
            }

            $user->update($updateData);
            Log::info("Cliente existente encontrado e atualizado (ID: {$user->id}).");
            return $user;

        } else {
            // 3. Novo Cliente: Cria um novo usuário
            $randomPassword = Str::random(12);
            $newUser = User::create([
                'name' => $name,
                'email' => $emailToUse,
                'whatsapp_contact' => $contact,
                'password' => Hash::make($randomPassword),
                'role' => 'cliente',
                'is_admin' => false,
                'data_nascimento' => $data['data_nascimento'] ?? null,
            ]);
            Log::info("Novo cliente criado (ID: {$newUser->id}). E-mail usado: {$emailToUse}");
            return $newUser;
        }
    }


    // -------------------------------------------------------------------------
    // 🗓️ MÉTODOS API PARA O DASHBOARD (AGENDAMENTO RÁPIDO)
    // -------------------------------------------------------------------------

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
        if ($this->checkOverlap($validated['date'], $validated['start_time'], $validated['end_time'], false)) {
            $conflictingIds = $this->getConflictingReservaIds($validated['date'], $validated['start_time'], $validated['end_time'], null);
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

        // 🛑 LÓGICA DE CHECAGEM RECORRENTE
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
            $overlapWithReal = $this->checkOverlap($dateString, $validated['start_time'], $validated['end_time'], false);

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


    // -------------------------------------------------------------------------
    // LÓGICA DE RENOVAÇÃO
    // -------------------------------------------------------------------------

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
            // Se recurrent_series_id é null (imprevisto) ou é igual ao ID da reserva, ignora
            if ($latest->recurrent_series_id === null) {
                continue;
            }

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

                // 3.1. Checagem de Duplicação: Verifica se a reserva JÁ EXISTE para ESTA SÉRIE (Integridade de Dados)
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


    // -------------------------------------------------------------------------
    // CANCELAMENTO PELO CLIENTE (FRONT-END)
    // -------------------------------------------------------------------------
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
        $rules = [
            'data_reserva' => ['required', 'date', "after_or_equal:" . Carbon::today()->format('Y-m-d')],
            'hora_inicio' => ['required', 'date_format:G:i'],
            'hora_fim' => ['required', 'date_format:G:i', 'after:hora_inicio'],
            'price' => ['required', 'numeric', 'min:0'],
            'schedule_id' => ['required', 'integer', 'exists:reservas,id,is_fixed,1,status,' . Reserva::STATUS_CONFIRMADA],
            'reserva_conflito_id' => 'nullable',

            // Validação de formato/presença do cliente, SEM 'unique'
            'nome_cliente' => 'required|string|max:255',
            // Regex estrito, requer exatamente 10 ou 11 dígitos, sem formatação.
            'contato_cliente' => 'required|string|regex:/^\d{10,11}$/|max:20',
            'email_cliente' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:500',
        ];

        // 1. Validação
        $validator = Validator::make($request->all(), $rules, [
            'schedule_id.exists' => 'O slot de horário selecionado não está mais disponível ou não é um horário válido.',
            'schedule_id.required' => 'O horário não foi selecionado corretamente. Tente selecionar o slot novamente no calendário.',
            'contato_cliente.regex' => 'O WhatsApp deve conter apenas DDD+ número (10 ou 11 dígitos, Ex: 91900000000).',
        ]);

        if ($validator->fails()) {
            Log::error('[STORE PUBLIC - SEM LOGIN] Erro de Validação:', $validator->errors()->toArray());
            return redirect()->route('reserva.index')->withErrors($validator)->withInput()->with('error', 'Correção Necessária! Por favor, verifique os campos destacados.');
        }

        $validated = $validator->validated();

        $date = $validated['data_reserva'];
        $startTime = $validated['hora_inicio'];
        $endTime = $validated['hora_fim'];
        $scheduleId = $validated['schedule_id'];
        $nomeCliente = $validated['nome_cliente'];
        $contatoCliente = $validated['contato_cliente'];
        $emailCliente = $validated['email_cliente'];


        // Normaliza as horas para o formato do banco de dados (H:i:s)
        $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');

        DB::beginTransaction();
        try {
            // 2. 🔑 CHAMADA DA LÓGICA findOrCreateClient local (Encontra ou cria o cliente)
            // Esta chamada agora garante que um e-mail válido seja usado/gerado.
            $clientUser = $this->findOrCreateClient([
                'name' => $nomeCliente,
                'email' => $emailCliente,
                'whatsapp_contact' => $contatoCliente,
                'data_nascimento' => null, // Assumindo que data_nascimento não está no formulário público
            ]);

            // === 3. Checagem de Conflito FINAL (CRÍTICO) ===
            // Checa contra qualquer outra reserva (que não seja o slot fixo que será deletado)
            if ($this->checkOverlap($date, $startTime, $endTime, false)) {
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
                $validator->errors()->add('schedule_id', 'O slot selecionado não existe mais.');
                throw new ValidationException($validator);
            }
            $fixedSlot->delete();


            // 5. Criação da Reserva Real (Status Pendente)
            $reserva = Reserva::create([
                'user_id' => $clientUser->id, // Usa o ID do cliente retornado
                'date' => $date,
                'day_of_week' => Carbon::parse($date)->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $validated['price'],
                'client_name' => $clientUser->name,
                'client_contact' => $clientUser->whatsapp_contact,
                'notes' => $validated['notes'] ?? null,
                'status' => Reserva::STATUS_PENDENTE,
                'is_fixed' => false,
                'is_recurrent' => false,
            ]);

            DB::commit();

            // 6. Mensagem de Sucesso e Link do WhatsApp
            $successMessage = 'Pré-reserva registrada com sucesso! Seu cadastro de cliente foi atualizado ou criado automaticamente. Aguarde a confirmação.';

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
            // Lança a exceção de volta para o Laravel lidar com os erros de validação
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
