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
    // O seu código não tinha constantes, então assumi as constantes padrão do Modelo Reserva.
    // Para fins de clareza nas correções, usarei as strings literais que você usou.

    /**
     * Exibe a página pública de agendamento (que carrega os slots via API).
     */
    public function index()
    {
        return view('reserva.index');
    }

    /**
     * Exibe o Dashboard administrativo (incluindo o alerta de renovação).
     * NOTA: Esta função normalmente residiria no AdminController, mas é mantida aqui se o Dashboard chamar o ReservaController.
     */
    public function dashboard()
    {
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
     * @return bool Retorna true se houver sobreposição.
     */
    public function checkOverlap(string $date, string $startTime, string $endTime, bool $isFixed, ?int $ignoreReservaId = null): bool
    {
        // Normaliza as horas (Formato H:i:s é o padrão para comparação no DB)
        try {
            $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
            $endTimeNormalized = Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');
        } catch (\Exception $e) {
            $startTimeNormalized = Carbon::parse($startTime)->format('H:i:s');
            $endTimeNormalized = Carbon::parse($endTime)->format('H:i:s');
        }

        $query = Reserva::whereDate('date', $date)
            // 1. Checa apenas contra RESERVAS ATIVAS DE CLIENTES
            ->whereIn('status', ['confirmed', 'pending']);


        // 2. Ignora a reserva atual (seja ela fixa ou real)
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
        // Apenas CONFIRMADA e PENDENTE causam conflito
        $activeStatuses = ['pending', 'confirmed'];

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
     * 🛑 CRÍTICO: Permite a recriação para reservas recorrentes que são canceladas pontualmente.
     */
    public function recreateFixedSlot(Reserva $originalReserva): void
    {
        // Se a reserva é recorrente E é a reserva mestra, não deve recriar para evitar duplicação.
        if ($originalReserva->is_recurrent && $originalReserva->id === $originalReserva->recurrent_series_id) {
             Log::info("Slot ID {$originalReserva->id} é MESTRE recorrente. Recriação ignorada para evitar duplicação em cascata.");
             return;
        }

        // ✅ MUDANÇA CRÍTICA: Checa se já existe um slot FIXO e LIVRE (STATUS_FREE)
        $existsFixedSlot = Reserva::where('is_fixed', true)
            ->where('date', $originalReserva->date)
            ->where('start_time', $originalReserva->start_time)
            ->where('end_time', $originalReserva->end_time)
            ->where('status', 'free') // 🛑 AGORA BUSCA POR FREE
            ->exists();

        if ($existsFixedSlot) {
            Log::info("Slot fixo já existe para {$originalReserva->date->format('Y-m-d')} {$originalReserva->start_time}. Recriação ignorada.");
            return;
        }

        // ATENÇÃO: As colunas client_name, client_contact e user_id devem ser NULLable
        // ou ter valores padrão para slots fixos, senão o DB MySQL gera erro 1048.
        Reserva::create([
            'date' => $originalReserva->date,
            'day_of_week' => $originalReserva->day_of_week,
            'start_time' => $originalReserva->start_time,
            'end_time' => $originalReserva->end_time,
            'price' => $originalReserva->price,
            'status' => 'free', // 🛑 CRÍTICO: Cria como FREE (disponível)
            'is_fixed' => true,
            'client_name' => 'Slot Fixo de 1h', // Pode ser um valor de placeholder
            'client_contact' => 'N/A', // 🛑 VALOR DE PLACEHOLDER para evitar erro 1048 (not null)
            'user_id' => null,
            'manager_id' => null,
            'recurrent_series_id' => null,
            'notes' => 'Recriado após cancelamento (ID original: ' . $originalReserva->id . ')'
        ]);

        Log::info("Slot fixo recriado após cancelamento da Reserva ID: {$originalReserva->id}.");
    }


    // -------------------------------------------------------------------------
    // 👤 LÓGICA DE CLIENTE: ENCONTRAR OU CRIAR
    // -------------------------------------------------------------------------

    /**
     * Encontra ou cria um usuário cliente (baseado no whatsapp_contact).
     *
     * @param array $data Contém 'name', 'email' (opcional), 'whatsapp_contact'.
     * @return User
     */
    protected function findOrCreateClient(array $data): User
    {
        $contact = $data['whatsapp_contact'];
        $name = $data['name'];
        $inputEmail = $data['email'] ?? null;

        $emailToUse = $inputEmail;

        // LÓGICA: Se o email do input estiver vazio, gera um provisório.
        if (empty($inputEmail)) {
            $uniquePart = Str::random(5);
            $emailToUse = "temp_" . time() . "{$uniquePart}" . "@arena.local";
        }

        // 1. Tenta encontrar o usuário pelo WhatsApp
        $user = User::where('whatsapp_contact', $contact)->first();

        if ($user) {
            // 2. Cliente encontrado: Atualiza o nome e e-mail (se for temp ou se for fornecido)
            $updateData = ['name' => $name];

            // Atualiza o e-mail APENAS SE: (a) for um e-mail temporário OU (b) o cliente forneceu um e-mail real.
            if (Str::contains($user->email, '@arena.local') || !empty($inputEmail)) {
                 $updateData['email'] = $emailToUse;
            }

            // Garante que o nome seja atualizado
             $updateData['name'] = $name;

             // 🛑 CORREÇÃO: Garante que a role esteja sempre em Português ('cliente')
             if ($user->role === 'client') {
                 $updateData['role'] = 'cliente';
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
                // 🛑 CORREÇÃO: USAR SEMPRE O PADRÃO EM PORTUGUÊS: 'cliente'
                'role' => 'cliente',
                'is_admin' => false,
                'data_nascimento' => $data['data_nascimento'] ?? null,
            ]);
            Log::info("Novo cliente criado (ID: {$newUser->id}). E-mail usado: {$emailToUse}");
            return $newUser;
        }
    }


    // -------------------------------------------------------------------------
    // 🗓️ MÉTODOS API PARA O DASHBOARD (AGENDAMENTO RÁPIDO) - CORRIGIDOS
    // -------------------------------------------------------------------------

    /**
     * API: Cria uma reserva pontual (quick) a partir do Dashboard.
     * Lógica de validação alterada para aceitar apenas client_name e client_contact
     */
    public function storeQuickReservaApi(Request $request)
    {
        // 🚨 VALIDAÇÃO CORRIGIDA: user_id é removido da regra de required_without
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:G:i',
            'end_time' => 'required|date_format:G:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'reserva_id_to_update' => 'required|exists:reservas,id',

            // 🛑 AGORA SÓ EXIGE NAME E CONTACT
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|digits:11|max:255',
            
            // ✅ CORREÇÃO CRÍTICA: Adiciona a validação do valor do sinal
            'signal_value' => 'nullable|numeric|min:0',

            'notes' => 'nullable|string',
        ], [
            'reserva_id_to_update.exists' => 'O slot de horário selecionado não existe ou não está disponível.',
            'client_contact.digits' => 'O WhatsApp deve conter exatamente 11 dígitos (DDD + Número).',
            'client_name.required' => 'O Nome do Cliente é obrigatório.',
            'client_contact.required' => 'O Contato do Cliente (WhatsApp) é obrigatório.',
        ]);

        // ---------------------------------------------------------------------
        // ✅ NOVA LÓGICA DE VALORES E PAGAMENTO
        // ---------------------------------------------------------------------
        $price = (float) $validated['price'];
        $signalValue = (float) ($validated['signal_value'] ?? 0.00);
        $totalPaid = $signalValue;
        
        $paymentStatus = 'pending';
        if ($signalValue > 0 && $signalValue < $price) {
            $paymentStatus = 'partial'; // Pagamento parcial (sinal)
        } elseif ($signalValue >= $price) {
            $paymentStatus = 'paid'; // Totalmente pago (sinal == preço total)
        }
        // ---------------------------------------------------------------------

        $reservaIdToUpdate = $validated['reserva_id_to_update'];
        $startTimeNormalized = Carbon::createFromFormat('G:i', $validated['start_time'])->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $validated['end_time'])->format('H:i:s');

        $oldReserva = Reserva::find($reservaIdToUpdate);

        // 1. Checagens de Segurança
        if (!$oldReserva || !$oldReserva->is_fixed || $oldReserva->status !== 'free') {
            return response()->json(['success' => false, 'message' => 'O slot selecionado não é um horário fixo disponível.'], 409);
        }

        // 2. Checagem de Conflito Final (contra reservas reais)
        if ($this->checkOverlap($validated['date'], $validated['start_time'], $validated['end_time'], false, $reservaIdToUpdate)) {
            $conflictingIds = $this->getConflictingReservaIds($validated['date'], $validated['start_time'], $validated['end_time'], $reservaIdToUpdate);
            return response()->json([
                'success' => false,
                'message' => 'Conflito: O horário acabou de ser agendado por outro cliente. (IDs: ' . $conflictingIds . ')'], 409);
        }


        // 3. Processamento do Cliente (NOVA LÓGICA)
        $clientName = $validated['client_name'];
        $clientContact = $validated['client_contact'];

        // Sincroniza/cria o cliente no DB (baseado no WhatsApp/contact)
        $clientUser = $this->findOrCreateClient([
            'name' => $clientName,
            'whatsapp_contact' => $clientContact,
            'email' => null,
            'data_nascimento' => null,
        ]);

        if (!$clientUser) {
            return response()->json(['success' => false, 'message' => 'Erro interno ao identificar ou criar o cliente.'], 500);
        }

        // Atualiza as variáveis de reserva com os dados Sincronizados
        $userId = $clientUser->id;
        $clientName = $clientUser->name;
        $clientContact = $clientUser->whatsapp_contact ?? $clientUser->email;


        DB::beginTransaction();
        try {
            // 4. Deleta o slot fixo de disponibilidade (o evento verde)
            $oldReserva->delete();

            // 5. Cria a nova reserva real do cliente (o evento azul)
            $newReserva = Reserva::create([
                'user_id' => $userId, // Usa o ID sincronizado
                'date' => $validated['date'],
                'day_of_week' => Carbon::parse($validated['date'])->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $price,
                // ✅ Adicionado: Valor do Sinal, Total Pago e Status de Pagamento
                'signal_value' => $signalValue,
                'total_paid' => $totalPaid,
                'payment_status' => $paymentStatus,
                'client_name' => $clientName,
                'client_contact' => $clientContact,
                'notes' => $validated['notes'] ?? null,
                'status' => 'confirmed', // Reserva de cliente confirmada pelo Admin
                'is_fixed' => false,
                'is_recurrent' => false,
                'manager_id' => Auth::id(),
            ]);

            DB::commit();

            $message = "Agendamento pontual para {$clientName} confirmado com sucesso!";
            if ($signalValue > 0) {
                $message .= " Sinal de R$ " . number_format($signalValue, 2, ',', '.') . " registrado.";
            }

            return response()->json(['success' => true, 'message' => $message], 200);

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
     * Lógica de validação alterada para aceitar apenas client_name e client_contact
     */
    public function storeRecurrentReservaApi(Request $request)
    {
        // 🚨 VALIDAÇÃO CORRIGIDA: user_id é removido da regra de required_without
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:G:i',
            'end_time' => 'required|date_format:G:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'reserva_id_to_update' => 'required|exists:reservas,id', // O ID do slot FIXO inicial

            // 🛑 AGORA SÓ EXIGE NAME E CONTACT
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|digits:11|max:255',
            
            // ✅ CORREÇÃO CRÍTICA: Adiciona a validação do valor do sinal
            'signal_value' => 'nullable|numeric|min:0',

            'notes' => 'nullable|string',
        ], [
            'reserva_id_to_update.exists' => 'O slot de horário selecionado não existe ou não está disponível.',
            'client_contact.digits' => 'O WhatsApp deve conter exatamente 11 dígitos (DDD + Número).',
            'client_name.required' => 'O Nome do Cliente é obrigatório.',
            'client_contact.required' => 'O Contato do Cliente (WhatsApp) é obrigatório.',
        ]);

        // ---------------------------------------------------------------------
        // ✅ NOVA LÓGICA DE VALORES E PAGAMENTO (para a Mestra e todas as cópias)
        // ---------------------------------------------------------------------
        $price = (float) $validated['price'];
        $signalValue = (float) ($validated['signal_value'] ?? 0.00);
        $totalPaid = $signalValue;
        
        $paymentStatus = 'pending';
        if ($signalValue > 0 && $signalValue < $price) {
            $paymentStatus = 'partial'; // Pagamento parcial (sinal)
        } elseif ($signalValue >= $price) {
            $paymentStatus = 'paid'; // Totalmente pago (sinal == preço total)
        }
        // ---------------------------------------------------------------------

        $initialDate = Carbon::parse($validated['date']);
        $dayOfWeek = $initialDate->dayOfWeek;

        $startTimeRaw = $validated['start_time'];
        $endTimeRaw = $validated['end_time'];

        $startTimeNormalized = Carbon::createFromFormat('G:i', $startTimeRaw)->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $endTimeRaw)->format('H:i:s');

        $scheduleId = $validated['reserva_id_to_update'];

        // Define a janela de agendamento (Exatamente 1 ano a partir da data inicial)
        $endDate = $initialDate->copy()->addYear()->subDay();


        // 1. Processamento do Cliente (NOVA LÓGICA)
        $clientName = $validated['client_name'];
        $clientContact = $validated['client_contact'];

        $clientUser = $this->findOrCreateClient([
            'name' => $clientName,
            'whatsapp_contact' => $clientContact,
            'email' => null,
            'data_nascimento' => null,
        ]);

        if (!$clientUser) {
            return response()->json(['success' => false, 'message' => 'Erro interno ao identificar ou criar o cliente.'], 500);
        }

        // Atualiza as variáveis de reserva com os dados Sincronizados
        $userId = $clientUser->id;
        $clientName = $clientUser->name;
        $clientContact = $clientUser->whatsapp_contact ?? $clientUser->email;


        // 2. Coleta todas as datas futuras para este dia da semana dentro da janela
        $datesToSchedule = [];
        $date = $initialDate->copy();
        while ($date->lte($endDate)) {
            $datesToSchedule[] = $date->toDateString();
            $date->addWeek();
        }

        // 3. Lógica de Checagem Recorrente (MANTIDA)
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
            $overlapWithReal = Reserva::whereDate('date', $dateString)
                ->where('is_fixed', false) // CRÍTICO: Somente reservas de cliente
                ->whereIn('status', ['confirmed', 'pending'])
                ->where(function ($q) use ($startTimeNormalized, $endTimeNormalized) {
                    $q->where('start_time', '<', $endTimeNormalized)
                      ->where('end_time', '>', $startTimeNormalized);
                })
                ->exists();


            // 2. Busca o slot fixo ATIVO (free) para esta data/hora
            $fixedSlotQuery = Reserva::where('is_fixed', true)
                                         ->whereDate('date', $dateString)
                                         ->where('start_time', $startTimeNormalized)
                                         ->where('end_time', $endTimeNormalized)
                                         ->where('status', 'free');

            if ($isFirstDate) {
                $fixedSlotQuery->where('id', $scheduleId);
            }

            $fixedSlot = $fixedSlotQuery->first();

            // 3. Avalia o conflito
            if ($overlapWithReal) {
                $isConflict = true;
            } else if (!$fixedSlot) {
                $isConflict = true;
            }

            if (!$isConflict) {
                // Se não há conflito nem ausência do slot fixo, podemos agendar
                $fixedSlotsToDelete[] = $fixedSlot->id; // Marca para consumo

                $reservasToCreate[] = [
                    'user_id' => $userId, // ✅ Usa o ID do cliente sincronizado/criado
                    'date' => $dateString,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTimeNormalized,
                    'end_time' => $endTimeNormalized,
                    'price' => $price,
                    // ✅ Adicionado: Valor do Sinal, Total Pago e Status de Pagamento
                    'signal_value' => $signalValue,
                    'total_paid' => $totalPaid,
                    'payment_status' => $paymentStatus,
                    'client_name' => $clientName,
                    'client_contact' => $clientContact,
                    'notes' => $validated['notes'] ?? null,
                    'status' => 'confirmed',
                    'is_fixed' => false,
                    'is_recurrent' => true,
                    'manager_id' => Auth::id(),
                    // Campos para mass insert
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            } else {
                $conflictCount++;
                if ($isFirstDate) {
                    Log::error("Conflito/Ausência no slot inicial da série recorrente. ID: {$scheduleId}.");
                    $conflictCount = count($datesToSchedule);
                    break;
                }
            }
        }

        // 4. Checagem final de integridade:
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
            // 5. Deleta todos os slots fixos válidos
            Reserva::whereIn('id', $fixedSlotsToDelete)->delete();

            // 6. Cria a série de reservas reais
            $reservasWithMasterId = [];

            // Cria a primeira reserva (que se tornará a Mestra)
            $firstReservaData = array_shift($reservasToCreate);
            $masterReserva = Reserva::create($firstReservaData);
            $masterReservaId = $masterReserva->id;

            // Atualiza a própria mestra e prepara as demais para inserção em massa
            $masterReserva->update(['recurrent_series_id' => $masterReservaId]);

            // Adiciona o masterId nas reservas restantes antes do insert
            foreach ($reservasToCreate as $reservaData) {
                 $reservaData['recurrent_series_id'] = $masterReservaId;
                 $reservasWithMasterId[] = $reservaData;
            }

            // Inserção em Massa
            if (!empty($reservasWithMasterId)) {
                Reserva::insert($reservasWithMasterId);
            }

            $newReservasCount = count($reservasWithMasterId) + 1; // +1 para a mestra

            DB::commit();

            $message = "Série recorrente de {$clientName} criada com sucesso! Total de {$newReservasCount} reservas agendadas até " . $endDate->format('d/m/Y') . ".";
            
            // ✅ Adicionado: Mensagem sobre o sinal
            if ($signalValue > 0) {
                $message .= " Sinal de R$ " . number_format($signalValue, 2, ',', '.') . " registrado na série mestra.";
            }

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
     */
    protected function getSeriesMaxDate(int $masterId): ?Carbon
    {
        $maxDate = Reserva::where(function($query) use ($masterId) {
             $query->where('id', $masterId)
                 ->orWhere('recurrent_series_id', $masterId);
             })
            ->where('is_recurrent', true)
            ->where('is_fixed', false)
            ->where('status', 'confirmed')
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

        $latestReservations = Reserva::selectRaw('recurrent_series_id, MAX(date) as last_date, MIN(date) as first_date, MIN(start_time) as slot_time, MAX(price) as slot_price, day_of_week, client_name')
            ->where('is_recurrent', true)
            ->where('is_fixed', false)
            ->where('status', 'confirmed')
            ->groupBy('recurrent_series_id', 'day_of_week', 'client_name')
            ->get();

        $expiringSeries = [];

        foreach ($latestReservations as $latest) {
            if ($latest->recurrent_series_id === null) {
                continue;
            }

            $lastDate = Carbon::parse($latest->last_date);
            $firstDate = Carbon::parse($latest->first_date);

            // 1. Condição principal: A série está no período de expiração (próximos 60 dias)?
            if ($lastDate->greaterThanOrEqualTo($today) && $lastDate->lessThanOrEqualTo($cutoffDate)) {

                // 2. FILTRO DE SEGURANÇA: Se a série tem menos de 7 dias de duração, ignora.
                if ($lastDate->diffInDays($firstDate) <= 7) {
                    Log::warning("Série Recorrente ID {$latest->recurrent_series_id} ignorada. Duração muito curta.");
                    continue;
                }

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
        if (!$masterReserva->is_recurrent || $masterReserva->id !== $masterReserva->recurrent_series_id) {
            return response()->json(['success' => false, 'message' => 'A reserva fornecida não é a mestra de uma série recorrente.'], 400);
        }

        // 1. Encontrar a data de expiração ATUAL (última data na série)
        $currentMaxDate = $this->getSeriesMaxDate($masterReserva->id);

        if (!$currentMaxDate) {
            return response()->json(['success' => false, 'message' => 'Nenhuma reserva confirmada encontrada para esta série.'], 404);
        }

        // 2. Definir a janela de renovação
        $startDate = $currentMaxDate->copy()->addWeek();
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

        // ---------------------------------------------------------------------
        // ✅ Mantém os valores de pagamento da série mestra para as novas cópias
        // ---------------------------------------------------------------------
        $signalValue = $masterReserva->signal_value ?? 0.00;
        $totalPaid = $masterReserva->total_paid ?? 0.00;
        $paymentStatus = $masterReserva->payment_status ?? 'pending';
        // ---------------------------------------------------------------------
        
        $newReservasCount = 0;

        DB::beginTransaction();
        try {
            // 3. Loop de renovação: Avança de semana em semana
            $currentDate = $startDate->copy();
            $conflictedOrSkippedCount = 0;

            while ($currentDate->lessThanOrEqualTo($endDate)) {
                $dateString = $currentDate->toDateString();
                $isConflict = false;

                // 3.1. Checagem de Duplicação (dentro da própria série)
                $isDuplicate = Reserva::whereDate('date', $dateString)
                    ->where('start_time', $startTime)
                    ->where('end_time', $endTime)
                    ->where('recurrent_series_id', $masterId)
                    ->where('is_fixed', false)
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->exists();

                if ($isDuplicate) {
                    $isConflict = true;
                    Log::info("Duplicação detectada para série #{$masterId} na data {$dateString}. Slot pulado.");
                }

                // 3.2. Checagem de Conflito (Outros Clientes)
                if (!$isConflict) {
                    $isOccupiedByRealCustomer = Reserva::whereDate('date', $dateString)
                        ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime)
                        ->where('is_fixed', false)
                        ->where('recurrent_series_id', '!=', $masterId)
                        ->whereIn('status', ['confirmed', 'pending'])
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
                        ->where('status', 'free')
                        ->first();
                }

                // 3.4. Cria a nova reserva se não houver conflito REAL nem duplicação
                if (!$isConflict) {
                    Reserva::create([
                        'user_id' => $userId,
                        'manager_id' => $managerId,
                        'date' => $dateString,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => $price,
                        // ✅ Adicionado: Valores de Pagamento (iguais aos da mestra)
                        'signal_value' => 0.00, // Sinal só é pago uma vez na série mestra
                        'total_paid' => 0.00, // O pagamento é gerenciado na mestra/fatura
                        'payment_status' => 'pending', // Pagamento individual é pending
                        // ---------------------------------------------------------------------
                        'client_name' => $clientName,
                        'client_contact' => $clientContact,
                        'status' => 'confirmed',
                        'is_fixed' => false,
                        'is_recurrent' => true,
                        'recurrent_series_id' => $masterId,
                    ]);
                    $newReservasCount++;

                    if ($fixedSlot) {
                        $fixedSlot->delete(); // Consome o slot verde/FREE
                    } else {
                        Log::warning("Slot fixo ausente para série #{$masterId} na data {$dateString} durante a renovação. Reserva criada sem consumir slot FREE.");
                    }
                } else {
                    $conflictedOrSkippedCount++;
                }

                $currentDate->addWeek();
            }

            DB::commit();

            if ($newReservasCount > 0) {
                // 4. Atualiza a data final em todas as reservas existentes da série. (Seu código não tinha o campo 'recurrent_end_date' mas é uma boa prática)
                // Se você tiver a coluna 'recurrent_end_date' no seu modelo Reserva, descomente e adapte.
                /*
                 Reserva::where('recurrent_series_id', $masterId)
                              ->orWhere('id', $masterId) // Inclui a própria masterReserva
                              ->where('is_fixed', false)
                              ->update(['recurrent_end_date' => $endDate]);
                 */

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
    /**
     * Permite ao cliente cancelar uma reserva pontual ou solicitar o cancelamento de uma série recorrente.
     */
    public function cancelByCustomer(Request $request, Reserva $reserva)
    {
        $user = Auth::user();
        if (!$user || $reserva->user_id !== $user->id) {
            return response()->json(['message' => 'Não autorizado ou a reserva não pertence a você.'], 403);
        }

        // 1. Validação (Incluindo a nova flag)
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
            'is_series_cancellation' => 'nullable|boolean',
        ], [
            'cancellation_reason.required' => 'O motivo do cancelamento é obrigatório.',
            'cancellation_reason.min' => 'O motivo deve ter pelo menos 5 caracteres.',
        ]);

        $isSeriesRequest = (bool)($request->input('is_series_cancellation') ?? false);
        $reason = $validated['cancellation_reason'];

        $reservaDateTime = Carbon::parse($reserva->date->format('Y-m-d') . ' ' . $reserva->start_time);

        if ($reservaDateTime->isPast()) {
            return response()->json(['message' => 'Esta reserva é no passado e não pode ser cancelada.'], 400);
        }

        // Checa status
        if ($reserva->status === 'cancelled' || $reserva->status === 'rejected') {
            return response()->json(['message' => 'Esta reserva já está cancelada ou rejeitada.'], 400);
        }


        // =====================================================================
        // 🚨 FLUXO 1: SOLICITAÇÃO DE CANCELAMENTO DE SÉRIE (RECORRENTE)
        // =====================================================================
        if ($reserva->is_recurrent && $isSeriesRequest) {

            // 1. Encontra a reserva Mestra (ou usa a atual se for a mestra)
            $masterReservaId = $reserva->recurrent_series_id ?? $reserva->id;
            $masterReserva = Reserva::find($masterReservaId);

            if (!$masterReserva) {
                return response()->json(['message' => 'Erro interno ao encontrar a série recorrente.'], 500);
            }

            // 2. Atualiza a reserva Mestra com o pedido.
            DB::beginTransaction();
            try {
                // Adiciona o pedido de cancelamento nas notas/motivo da reserva mestra
                $newNote = "[SOLICITAÇÃO DE CANCELAMENTO DE SÉRIE PELO CLIENTE {$user->name} ({$user->id}) em ". Carbon::now()->format('d/m/Y H:i') ."]: {$reason}\n\n[Notas Originais]: {$masterReserva->notes}";

                $masterReserva->update([
                    'notes' => Str::limit($newNote, 5000), // Evita estouro de campo
                    'cancellation_reason' => '[PENDENTE GESTOR] Solicitação de cancelamento de série registrada.'
                ]);

                Log::warning("SOLICITAÇÃO DE CANCELAMENTO DE SÉRIE: Cliente ID: {$user->id}, Série ID: {$masterReservaId}. Motivo: {$reason}");

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Solicitação de cancelamento da série enviada com sucesso ao Gestor. Ele fará a análise e a aprovação.'
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erro ao registrar solicitação de cancelamento de série: " . $e->getMessage());
                return response()->json(['message' => 'Erro interno ao registrar a solicitação.'], 500);
            }
        }

        // =====================================================================
        // 🛑 FLUXO 2: RESERVA RECORRENTE INDIVIDUAL (Bloqueio)
        // =====================================================================
        if ($reserva->is_recurrent && !$isSeriesRequest) {
             return response()->json(['message' => 'Você não pode cancelar slots individuais de uma série recorrente. Use a opção de cancelamento de série no histórico.'], 400);
        }

        // =====================================================================
        // ✅ FLUXO 3: CANCELAMENTO DE RESERVA PONTUAL (Ação Direta)
        // =====================================================================
        if (!$reserva->is_recurrent) {
            DB::beginTransaction();
            try {
                $reserva->status = 'cancelled';
                $reserva->cancellation_reason = '[Cliente] ' . $reason;
                $reserva->save();

                // Recria o slot fixo de disponibilidade (o evento verde)
                $this->recreateFixedSlot($reserva);

                Log::info("Reserva ID: {$reserva->id} (Pontual) cancelada pelo cliente ID: {$user->id}. Slot fixo recriado.");
                DB::commit();

                return response()->json(['success' => true, 'message' => 'Reserva pontual cancelada com sucesso! O slot foi liberado.'], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erro ao cancelar reserva pontual pelo cliente ID: {$user->id}. Reserva ID: {$reserva->id}. Erro: " . $e->getMessage());
                return response()->json(['message' => 'Ocorreu um erro ao processar o cancelamento. Tente novamente.'], 500);
            }
        }

        return response()->json(['message' => 'Ação inválida para o tipo de reserva selecionado.'], 400);
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
            'schedule_id' => ['required', 'integer', 'exists:reservas,id,is_fixed,1,status,free'],
            'reserva_conflito_id' => 'nullable',

            // Validação de formato/presença do cliente, SEM 'unique'
            'nome_cliente' => 'required|string|max:255',
            'contato_cliente' => 'required|string|regex:/^\d{10,11}$/|max:20',
            'email_cliente' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:500',
            // ✅ Adiciona validação do sinal na pré-reserva (embora seja pré-reserva, é bom ter)
            'signal_value' => 'nullable|numeric|min:0',
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
        
        // ---------------------------------------------------------------------
        // ✅ NOVA LÓGICA DE VALORES E PAGAMENTO (para storePublic)
        // ---------------------------------------------------------------------
        $price = (float) $validated['price'];
        $signalValue = (float) ($validated['signal_value'] ?? 0.00);
        $totalPaid = $signalValue;
        
        $paymentStatus = 'pending';
        if ($signalValue > 0 && $signalValue < $price) {
            $paymentStatus = 'partial';
        } elseif ($signalValue >= $price) {
            $paymentStatus = 'paid';
        }
        // ---------------------------------------------------------------------


        // Normaliza as horas para o formato do banco de dados (H:i:s)
        $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');

        DB::beginTransaction();
        try {
            // 2. 🔑 CHAMADA DA LÓGICA findOrCreateClient local (Encontra ou cria o cliente)
            $clientUser = $this->findOrCreateClient([
                'name' => $nomeCliente,
                'email' => $emailCliente,
                'whatsapp_contact' => $contatoCliente,
                'data_nascimento' => null,
            ]);

            // === 3. Checagem de Conflito FINAL (CRÍTICO) ===
            if ($this->checkOverlap($date, $startTime, $endTime, false, $scheduleId)) {
                DB::rollBack();
                // A checagem falhou: há uma reserva REAL (não o slot fixo) em conflito.
                $conflictingIds = $this->getConflictingReservaIds($date, $startTime, $endTime, $scheduleId);
                $validator->errors()->add('reserva_conflito_id', "ERRO: Este horário acabou de ser reservado ou está em conflito. IDs: ({$conflictingIds})");
                throw new ValidationException($validator);
            }

            // 4. Limpa o slot fixo (evento verde)
            $fixedSlot = Reserva::where('id', $scheduleId)
                ->where('is_fixed', true)
                ->where('status', 'free')
                ->first();

            if (!$fixedSlot) {
                DB::rollBack();
                // Se o slot não existe mais, a transação deve ser abortada.
                $validator->errors()->add('schedule_id', 'O slot selecionado não existe mais.');
                throw new ValidationException($validator);
            }
            $fixedSlot->delete();


            // 5. Criação da Reserva Real (Status Pendente)
            $reserva = Reserva::create([
                'user_id' => $clientUser->id,
                'date' => $date,
                'day_of_week' => Carbon::parse($date)->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $price,
                // ✅ Adicionado: Valor do Sinal, Total Pago e Status de Pagamento
                'signal_value' => $signalValue,
                'total_paid' => $totalPaid,
                'payment_status' => $paymentStatus,
                'client_name' => $clientUser->name,
                'client_contact' => $clientUser->whatsapp_contact,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'is_fixed' => false,
                'is_recurrent' => false,
            ]);

            DB::commit();

            // 6. Mensagem de Sucesso e Link do WhatsApp
            $successMessage = 'Pré-reserva registrada com sucesso! Seu cadastro de cliente foi atualizado ou criado automaticamente. Aguarde a confirmação.';

            // Adaptação da mensagem do WhatsApp para incluir o sinal
            $whatsappNumber = '91985320997';
            $data = Carbon::parse($reserva->date)->format('d/m/Y');
            $hora = Carbon::parse($reserva->start_time)->format('H:i');
            $valorSinal = $signalValue > 0 ? "Sinal Pago: R$ " . number_format($signalValue, 2, ',', '.') : "Sinal: R$ 0,00";


            $messageText = "🚨 NOVA PRÉ-RESERVA PENDENTE\n\n" .
                "Cliente: {$reserva->client_name}\n" .
                "Data/Hora: {$data} às {$hora}\n" .
                "Valor Total: R$ " . number_format($reserva->price, 2, ',', '.') . "\n" .
                "{$valorSinal}\n";

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
            $validator->errors()->add('server_error', 'Erro interno ao processar a reserva. Tente novamente mais tarde.');
            throw new ValidationException($validator);
        }
    }


    /**
     * Retorna a contagem de reservas pendentes para o Dashboard.
     */
    public function countPending()
    {
        $futureOrTodayCount = Reserva::where('status', 'pending')
            ->count();

        return response()->json(['count' => $futureOrTodayCount], 200);
    }
}