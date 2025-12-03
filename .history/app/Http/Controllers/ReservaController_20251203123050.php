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
use App\Models\FinancialTransaction; // Importa o modelo de transações

class ReservaController extends Controller
{
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
        $expiringSeries = $this->getEndingRecurrentSeries();

        return view('dashboard', [
            'expiringSeriesCount' => count($expiringSeries),
            'expiringSeries' => $expiringSeries,
        ]);
    }


    // -------------------------------------------------------------------------
    // MÉTODOS AUXILIARES (CheckOverlap, Conflicting IDs e Manipulação de Slots Fixos)
    // -------------------------------------------------------------------------

    /**
     * Helper CRÍTICO: Checa se há sobreposição no calendário (apenas reservas de cliente).
     */
    public function checkOverlap($date, $startTime, $endTime, $checkActiveOnly = true, $excludeReservaId = null)
    {
        // Normaliza as horas
        try {
            $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
            $endTimeNormalized = Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');
        } catch (\Exception $e) {
            $startTimeNormalized = Carbon::parse($startTime)->format('H:i:s');
            $endTimeNormalized = Carbon::parse($endTime)->format('H:i:s');
        }

        $query = Reserva::where('date', $date)
            ->where('is_fixed', false) // Apenas reservas de clientes (não slots de disponibilidade)
            ->where(function ($q) use ($startTimeNormalized, $endTimeNormalized) {
                // Lógica de sobreposição: (A_start < B_end) AND (B_start < A_end)
                $q->where('start_time', '<', $endTimeNormalized)
                    ->where('end_time', '>', $startTimeNormalized);
            });

        if ($checkActiveOnly) {
            // Checa apenas status que indicam ocupação real
            $query->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]); // PADRONIZADO
        }

        if ($excludeReservaId) {
            $query->where('id', '!=', $excludeReservaId);
        }

        return $query->exists();
    }


    /**
     * Função auxiliar para buscar os IDs conflitantes para feedback (uso interno do Admin).
     */
    protected function getConflictingReservaIds(string $date, string $startTime, string $endTime, ?int $ignoreReservaId = null)
    {
        // Apenas 'confirmed' e 'pending' causam conflito
        $activeStatuses = [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA]; // PADRONIZADO

        // Normaliza as horas para garantir que a consulta SQL seja precisa
        try {
             $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
             $endTimeNormalized = Carbon::parse($endTime)->format('H:i:s');
        } catch (\Exception $e) {
             $startTimeNormalized = Carbon::parse($startTime)->format('H:i:s');
             $endTimeNormalized = Carbon::parse($endTime)->format('H:i:s');
        }

        $conflictingReservas = Reserva::whereIn('status', $activeStatuses)
            ->whereDate('date', $date)
            ->where('is_fixed', false) // Apenas reservas de cliente
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
     * Helper CRÍTICO: Recria o slot fixo de disponibilidade ('free')
     * quando uma reserva de cliente é cancelada ou rejeitada.
     */
    public function recreateFixedSlot(Reserva $reserva)
    {
        // 1. Evita processar se for um slot fixo
        if ($reserva->is_fixed) {
            return;
        }

        // 2. Verifica se já existe um slot fixo no mesmo horário (evita duplicidade)
        $existingFixedSlot = Reserva::where('is_fixed', true)
            ->where('date', $reserva->date)
            ->where('start_time', $reserva->start_time)
            ->where('end_time', $reserva->end_time)
            ->first();

        // 3. Se não houver, recria o slot como LIVRE ('free')
        if (!$existingFixedSlot) {
            Reserva::create([
                'date' => $reserva->date,
                'day_of_week' => $reserva->day_of_week,
                'start_time' => $reserva->start_time,
                'end_time' => $reserva->end_time,
                'price' => $reserva->price, // Mantém o preço original para o slot
                'status' => Reserva::STATUS_FREE, // PADRONIZADO
                'is_fixed' => true,
                'is_recurrent' => $reserva->is_recurrent, // Mantém a natureza de recorrência
                'client_name' => 'Slot Fixo', // Placeholder para colunas NOT NULL
                'client_contact' => 'N/A',  // Placeholder para colunas NOT NULL
                'user_id' => null,          // Deve ser NULL
            ]);
            Log::info("Slot fixo recriado para {$reserva->date} {$reserva->start_time}.");
        } else {
            // Se o slot existir, mas estiver em 'maintenance', mantém.
            // Se estiver em outro status (tipo 'pending' ou 'confirmed' por erro), força para 'free'.
            if (!in_array($existingFixedSlot->status, [Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE])) { // PADRONIZADO
                 $existingFixedSlot->update(['status' => Reserva::STATUS_FREE]); // PADRONIZADO
                 Log::warning("Slot fixo existente para {$reserva->date} foi corrigido para FREE.");
            }
        }
    }


    /**
     * Helper CRÍTICO: Consome o slot fixo de disponibilidade (remove)
     * quando uma reserva de cliente é criada (manualmente) ou reativada (AdminController::reativar).
     */
    public function consumeFixedSlot(Reserva $reserva)
    {
        // 1. Evita processar se for um slot fixo
        if ($reserva->is_fixed) {
            return;
        }

        // 2. Encontra o slot fixo correspondente e o remove
        // Busca slots 'free' ou 'maintenance'
        $fixedSlot = Reserva::where('is_fixed', true)
            ->where('date', $reserva->date)
            ->where('start_time', $reserva->start_time)
            ->where('end_time', $reserva->end_time)
            ->whereIn('status', [Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE]) // PADRONIZADO
            ->first();

        if ($fixedSlot) {
            // Remove o slot de disponibilidade para liberar o espaço
            $fixedSlot->delete();
            Log::info("Slot fixo ID {$fixedSlot->id} consumido para a reserva ID {$reserva->id}.");
        } else {
            // O slot fixo pode não existir se a reserva de cliente foi criada "por cima" de um horário
            // que não tinha slot fixo, o que é um aviso, mas não um erro fatal.
             Log::warning("Tentativa de consumir slot fixo para reserva ID {$reserva->id}, mas nenhum slot FREE/MAINTENANCE foi encontrado para a data/hora.");
        }
    }


    // -------------------------------------------------------------------------
    // 👤 LÓGICA DE CLIENTE: ENCONTRAR OU CRIAR
    // -------------------------------------------------------------------------

    /**
     * Encontra ou cria um usuário cliente (baseado no whatsapp_contact).
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

             // CORREÇÃO: Garante que a role esteja sempre em Português ('cliente')
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
                // CORREÇÃO: USAR SEMPRE O PADRÃO EM PORTUGUÊS: 'cliente'
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

    /**
     * API: Cria uma reserva pontual (quick) a partir do Dashboard.
     */
    public function storeQuickReservaApi(Request $request)
    {
        // VALIDAÇÃO CORRIGIDA: user_id é removido da regra de required_without
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:G:i',
            'end_time' => 'required|date_format:G:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'reserva_id_to_update' => 'required|exists:reservas,id',

            // AGORA SÓ EXIGE NAME E CONTACT
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|digits:11|max:255',

            // Adiciona a validação do valor do sinal
            'signal_value' => 'nullable|numeric|min:0',

            'notes' => 'nullable|string',
        ], [
            'reserva_id_to_update.exists' => 'O slot de horário selecionado não existe ou não está disponível.',
            'client_contact.digits' => 'O WhatsApp deve conter exatamente 11 dígitos (DDD + Número).',
            'client_name.required' => 'O Nome do Cliente é obrigatório.',
            'client_contact.required' => 'O Contato do Cliente (WhatsApp) é obrigatório.',
        ]);

        // NOVA LÓGICA DE VALORES E PAGAMENTO
        $price = (float) $validated['price'];
        $signalValue = (float) ($validated['signal_value'] ?? 0.00);
        $totalPaid = $signalValue;

        $paymentStatus = 'pending';
        if ($signalValue > 0 && $signalValue < $price) {
            $paymentStatus = 'partial'; // Pagamento parcial (sinal)
        } elseif ($signalValue >= $price) {
            $paymentStatus = 'paid'; // Totalmente pago (sinal == preço total)
        }
        // FIM NOVA LÓGICA DE VALORES E PAGAMENTO

        $reservaIdToUpdate = $validated['reserva_id_to_update'];
        $startTimeNormalized = Carbon::createFromFormat('G:i', $validated['start_time'])->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $validated['end_time'])->format('H:i:s');

        $oldReserva = Reserva::find($reservaIdToUpdate);

        // 1. Checagens de Segurança
        if (!$oldReserva || !$oldReserva->is_fixed || $oldReserva->status !== Reserva::STATUS_FREE) { // PADRONIZADO
            return response()->json(['success' => false, 'message' => 'O slot selecionado não é um horário fixo disponível.'], 409);
        }

        // 2. Checagem de Conflito Final (contra reservas reais)
        if ($this->checkOverlap($validated['date'], $validated['start_time'], $validated['end_time'], true, $reservaIdToUpdate)) {
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
                'final_price' => $price, // Define o final_price igual ao price
                // Adicionado: Valor do Sinal, Total Pago e Status de Pagamento
                'signal_value' => $signalValue,
                'total_paid' => $totalPaid,
                'payment_status' => $paymentStatus,
                'client_name' => $clientName,
                'client_contact' => $clientContact,
                'notes' => $validated['notes'] ?? null,
                'status' => Reserva::STATUS_CONFIRMADA, // PADRONIZADO
                'is_fixed' => false,
                'is_recurrent' => false,
                'manager_id' => Auth::id(),
            ]);

            // 6. NOVO: GERA TRANSAÇÃO FINANCEIRA (SINAL) - Mesmo para reservas pontuais, se houver pagamento inicial
            if ($signalValue > 0) {
                FinancialTransaction::create([
                    'reserva_id' => $newReserva->id,
                    'user_id' => $userId,
                    'manager_id' => Auth::id(),
                    'amount' => $signalValue,
                    'type' => 'signal',
                    'payment_method' => 'pix', // Assumindo PIX no quick add
                    'description' => 'Sinal/Pagamento integral recebido na criação do agendamento pontual (API Dashboard)',
                    'paid_at' => Carbon::now(),
                ]);
                Log::info("Transação de Sinal (R$ {$signalValue}) registrada para Reserva ID {$newReserva->id}.");
            }


            DB::commit();

            $message = "Agendamento pontual para {$clientName} confirmado com sucesso!";
            if ($signalValue > 0) {
                $message .= " Sinal/Pagamento de R$ " . number_format($signalValue, 2, ',', '.') . " registrado.";
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
     * API: Cria uma série recorrente (6 meses) a partir do Agendamento Rápido do Dashboard.
     */
    public function storeRecurrentReservaApi(Request $request)
    {
        // VALIDAÇÃO CORRIGIDA: user_id é removido da regra de required_without
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:G:i',
            'end_time' => 'required|date_format:G:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'reserva_id_to_update' => 'required|exists:reservas,id', // O ID do slot FIXO inicial

            // AGORA SÓ EXIGE NAME E CONTACT
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|digits:11|max:255',

            // CORREÇÃO CRÍTICA: Adiciona a validação do valor do sinal
            'signal_value' => 'nullable|numeric|min:0',

            'notes' => 'nullable|string',
        ], [
            'reserva_id_to_update.exists' => 'O slot de horário selecionado não existe ou não está disponível.',
            'client_contact.digits' => 'O WhatsApp deve conter exatamente 11 dígitos (DDD + Número).',
            'client_name.required' => 'O Nome do Cliente é obrigatório.',
            'client_contact.required' => 'O Contato do Cliente (WhatsApp) é obrigatório.',
        ]);

        // NOVA LÓGICA DE VALORES E PAGAMENTO (para a Mestra e todas as cópias)
        $price = (float) $validated['price'];
        $signalValue = (float) ($validated['signal_value'] ?? 0.00);
        $totalPaid = $signalValue;

        $paymentStatus = 'pending';
        if ($signalValue > 0 && $signalValue < $price) {
            $paymentStatus = 'partial'; // Pagamento parcial (sinal)
        } elseif ($signalValue >= $price) {
            $paymentStatus = 'paid'; // Totalmente pago (sinal == preço total)
        }
        // FIM NOVA LÓGICA DE VALORES E PAGAMENTO

        $initialDate = Carbon::parse($validated['date']);
        $dayOfWeek = $initialDate->dayOfWeek;

        $startTimeRaw = $validated['start_time'];
        $endTimeRaw = $validated['end_time'];

        $startTimeNormalized = Carbon::createFromFormat('G:i', $startTimeRaw)->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $endTimeRaw)->format('H:i:s');

        $scheduleId = $validated['reserva_id_to_update'];

        // Define a janela de agendamento (Exatamente 6 meses a partir da data inicial)
        // CORREÇÃO AQUI: De addYear() para addMonths(6)
        $endDate = $initialDate->copy()->addMonths(6); // CORRIGIDO


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
                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]) // PADRONIZADO
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
                                             ->where('status', Reserva::STATUS_FREE); // PADRONIZADO

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

                // LÓGICA DE PAGAMENTO CONDICIONAL
                if ($isFirstDate) {
                    // Mestra: Mantém os valores de pagamento originais (que incluem o sinal)
                    $slotSignal = $signalValue;
                    $slotPaid = $totalPaid;
                    $slotPaymentStatus = $paymentStatus;
                } else {
                    // Cópias futuras: Zera o pagamento para forçar a cobrança integral
                    $slotSignal = 0.00;
                    $slotPaid = 0.00;
                    $slotPaymentStatus = 'pending';
                }

                $reservasToCreate[] = [
                    'user_id' => $userId, // Usa o ID do cliente sincronizado/criado
                    'manager_id' => Auth::id(), // Adicionado o manager_id
                    'date' => $dateString,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTimeNormalized,
                    'end_time' => $endTimeNormalized,
                    'price' => $price,
                    'final_price' => $price, // Define o final_price igual ao price
                    // CORREÇÃO APLICADA AQUI
                    'signal_value' => $slotSignal,
                    'total_paid' => $slotPaid,
                    'payment_status' => $slotPaymentStatus,
                    // FIM CORREÇÃO
                    'client_name' => $clientName,
                    'client_contact' => $clientContact,
                    'notes' => $validated['notes'] ?? null,
                    'status' => Reserva::STATUS_CONFIRMADA, // PADRONIZADO
                    'is_fixed' => false,
                    'is_recurrent' => true,
                    // 'recurrent_series_id' será adicionado após a criação da mestra
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
            Log::info("Slots fixos IDs: " . implode(', ', $fixedSlotsToDelete) . " consumidos/deletados para série recorrente.");

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

            // 7. NOVO: GERA TRANSAÇÃO FINANCEIRA (SINAL)
            if ($signalValue > 0) {
                FinancialTransaction::create([
                    'reserva_id' => $masterReservaId,
                    'user_id' => $userId,
                    'manager_id' => Auth::id(),
                    'amount' => $signalValue,
                    'type' => 'signal',
                    'payment_method' => 'pix', // Assumindo PIX no quick add
                    'description' => 'Sinal recebido na criação da série recorrente (API Dashboard)',
                    'paid_at' => Carbon::now(),
                ]);
                Log::info("Transação de Sinal (R$ {$signalValue}) registrada para Master ID {$masterReservaId}.");
            }

            DB::commit();

            $message = "Série recorrente de {$clientName} criada com sucesso! Total de {$newReservasCount} reservas agendadas até " . $endDate->format('d/m/Y') . ".";

            // Adicionado: Mensagem sobre o sinal
            if ($signalValue > 0) {
                $message .= " Sinal de R$ " . number_format($signalValue, 2, ',', '.') . " registrado na série mestra.";
            }

            if ($conflictCount > 0) {
                $message .= " Atenção: {$conflictCount} datas foram puladas/conflitantes e não foram agendadas. Verifique o calendário.";
            }

            return response()->json(['success' => true, 'message' => $message], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            // Tenta recriar o slot fixo original se a transação falhar (o slot inicial já foi deletado)
            $oldReserva = Reserva::find($scheduleId);
            if (!$oldReserva) {
                $oldReserva = new Reserva(['date' => $validated['date'], 'start_time' => $startTimeNormalized, 'end_time' => $endTimeNormalized, 'is_fixed' => false, 'day_of_week' => $dayOfWeek, 'price' => $price]);
                $this->recreateFixedSlot($oldReserva);
            }
            Log::error("Erro ao criar série recorrente: " . $e->getMessage(), ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Erro interno ao criar série recorrente: Transação falhou. ' . $e->getMessage()], 500);
        }
    }

    /**
     * Finaliza o pagamento de uma reserva e, opcionalmente, atualiza o preço de reservas futuras da série.
     * Rota: POST /admin/pagamentos/{reserva}/finalizar
     */
    public function finalizarPagamento(Request $request, Reserva $reserva)
    {
        // 1. Validação dos dados de entrada
        $request->validate([
            'final_price' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            // O campo apply_to_series é opcional, enviado pelo frontend
            'apply_to_series' => 'sometimes|boolean',
        ], [
            'final_price.required' => 'O preço final é obrigatório.',
            'amount_paid.required' => 'O valor recebido é obrigatório.',
            'payment_method.required' => 'O método de pagamento é obrigatório.',
        ]);

        DB::beginTransaction();
        try {
            $finalPrice = (float) $request->final_price;
            $amountPaidNow = (float) $request->amount_paid;
            $signalAmount = (float) ($reserva->total_paid ?? 0); // Valor total já pago (sinal)

            // Total pago após esta transação
            $newTotalPaid = $signalAmount + $amountPaidNow;

            // Define o novo status de pagamento
            $paymentStatus = 'partial';
            if (abs($newTotalPaid - $finalPrice) < 0.01 || $newTotalPaid > $finalPrice) {
                $paymentStatus = 'paid'; // Totalmente pago ou sobrepago (com troco)
            } elseif ($newTotalPaid == 0) {
                 $paymentStatus = 'unpaid';
            }

            // --- 2. Atualiza a Reserva Atual ---
            $reserva->update([
                'final_price' => $finalPrice, // O preço final acordado, que pode incluir desconto
                'total_paid' => $newTotalPaid,
                'payment_status' => $paymentStatus,
                'payment_method' => $request->payment_method, // Método de pagamento final
                'manager_id' => Auth::id(),
            ]);

            // 2.1. NOVO: GERA TRANSAÇÃO FINANCEIRA (Pagamento do Restante)
            if ($amountPaidNow > 0) {
                 FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => Auth::id(),
                    'amount' => $amountPaidNow,
                    'type' => 'payment',
                    'payment_method' => $request->payment_method,
                    'description' => 'Pagamento final/parcial da reserva',
                    'paid_at' => Carbon::now(),
                ]);
                Log::info("Transação de Pagamento (R$ {$amountPaidNow}) registrada para Reserva ID {$reserva->id}.");
            }


            // --- 3. Lógica para Recorrência: PROPAGAÇÃO DE PREÇO ---
            if ($request->boolean('apply_to_series') && $reserva->is_recurrent) {

                // O novo preço de base para as futuras reservas será o final_price desta reserva.
                $newPriceForSeries = $finalPrice;

                // O masterId deve ser obtido de forma robusta.
                $masterId = $reserva->recurrent_series_id ?? $reserva->id;

                // A data de corte é a data da reserva PAGA.
                $reservaDate = Carbon::parse($reserva->date)->toDateString();

                // CRÍTICO: Atualiza todas as reservas futuras (data estritamente MAIOR)
                $updatedCount = Reserva::where(function ($query) use ($masterId) {
                       // Target the entire series (master and copies)
                       $query->where('recurrent_series_id', $masterId)
                             ->orWhere('id', $masterId);
                   })
                   // CRÍTICO: Pega todas as reservas com data ESTREITAMENTE MAIOR que a data atual
                   ->whereDate('date', '>', $reservaDate)
                   // NOVO: Adiciona filtro de horário para garantir que é o slot semanal correto
                   ->where('start_time', $reserva->start_time)
                   ->where('end_time', $reserva->end_time)
                   ->where('is_fixed', false)
                   // Inclui o status PARTIAL para atingir reservas futuras com sinal pago, mas com preço desatualizado.
                   ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, Reserva::STATUS_PARTIAL])
                   ->update([
                       // Atualiza o preço base (price) e o preço final (final_price)
                       'price' => $newPriceForSeries,
                       'final_price' => $newPriceForSeries,
                       'manager_id' => Auth::id(),
                   ]);

                Log::info("Preço de série recorrente (ID {$masterId}) atualizado para R$ {$newPriceForSeries} em {$updatedCount} reservas futuras. Por Gestor ID: " . Auth::id());
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pagamento finalizado e preço da série atualizado com sucesso!']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erro ao finalizar pagamento: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * NOVO MÉTODO: Confirmação de Reserva Pendente.
     */
    public function confirmar(Request $request, Reserva $reserva)
    {
        // DIAGNÓSTICO DE INPUT: Loga o input de recorrência
        Log::debug("Input 'is_recurrent' RAW: " . print_r($request->input('is_recurrent'), true));

        // 1. Validação
        $validated = $request->validate([
            'signal_value' => 'nullable|numeric|min:0',
            // O Laravel/PHP, ao ver múltiplos inputs com o mesmo nome (hidden + checkbox),
            // pode receber uma string com o último valor OU um array ['0', '1'].
            'is_recurrent' => ['nullable', 'sometimes'], // Remove in:0,1 da validação para aceitar array
        ], [
            'signal_value.numeric' => 'O valor do sinal deve ser um número.',
            'signal_value.min' => 'O valor do sinal não pode ser negativo.',
        ]);

        if ($reserva->status !== Reserva::STATUS_PENDENTE) { // PADRONIZADO
            return redirect()->back()->with('error', 'Esta reserva já foi processada.');
        }

        // LÓGICA FINAL: Checagem robusta contra string ou array
        // Força o input para array e usa array_filter para checar se o valor '1' está presente.
        $isRecurrent = count(array_filter((array)$request->input('is_recurrent'), function($value) {
            return $value === '1' || $value === true; // Adiciona check para bool true
        })) > 0;

        // DIAGNÓSTICO: Loga o resultado da variável de controle
        Log::debug("isRecurrent (Flag de controle): " . ($isRecurrent ? 'TRUE' : 'FALSE'));

        $signalValue = (float)($validated['signal_value'] ?? 0.00);

        // 2. Checagem de Conflito (Contra outras reservas ativas, exceto a própria reserva que está sendo confirmada)
        if ($this->checkOverlap($reserva->date, $reserva->start_time, $reserva->end_time, true, $reserva->id)) {
            $conflictingIds = $this->getConflictingReservaIds($reserva->date, $reserva->start_time, $reserva->end_time, $reserva->id);
            return redirect()->back()->with('error', "Conflito: Não é possível confirmar. O horário está ocupado por outra reserva. (IDs: {$conflictingIds})");
        }

        DB::beginTransaction();
        try {
            $originalFixedSlotId = $reserva->fixed_slot_id;

            // 3. Atualiza a reserva atual para 'confirmed'
            $reserva->status = Reserva::STATUS_CONFIRMADA; // PADRONIZADO
            $reserva->signal_value = $signalValue;
            $reserva->total_paid = $signalValue;
            $reserva->is_recurrent = $isRecurrent; // <--- DEFINIDO DINAMICAMENTE AQUI
            $reserva->manager_id = Auth::id();
            $reserva->final_price = $reserva->price; // Define o final_price igual ao price na confirmação

            // Define o status de pagamento
            $paymentStatus = 'pending';
            if ($signalValue > 0 && $signalValue < $reserva->price) {
                $paymentStatus = 'partial';
            } elseif ($signalValue >= $reserva->price) {
                $paymentStatus = 'paid';
            }
            $reserva->payment_status = $paymentStatus;

            // Se for recorrente, ela se tornará a reserva Mestra
            if ($isRecurrent) {
                $reserva->save(); // Salva antes de usar o ID
                $reserva->recurrent_series_id = $reserva->id;
                $reserva->save();
            } else {
                $reserva->save();
            }

            // Log de INFO original do usuário (para aparecer no log)
            Log::info("Reserva ID: {$reserva->id} confirmada por Gestor ID: " . Auth::id() . ". Sinal: R$ " . number_format($signalValue, 2, ',', '.') . ", Recorrente: " . ($isRecurrent ? 'Sim' : 'Não'));


            // 4. Consome o slot fixo original (se existir)
            if ($originalFixedSlotId) {
                Reserva::where('id', $originalFixedSlotId)
                    ->where('is_fixed', true)
                    ->where('status', Reserva::STATUS_FREE) // PADRONIZADO
                    ->delete();
                Log::info("Slot fixo ID {$originalFixedSlotId} consumido/deletado.");
            }

            $successMessage = "Reserva de {$reserva->client_name} confirmada com sucesso!";
            $recurrentCount = 0;
            $conflictedOrSkippedCount = 0;

            // 5. LÓGICA CRÍTICA: CRIAÇÃO DA SÉRIE RECORRENTE (6 meses)
            if ($isRecurrent) { // SÓ EXECUTA SE O CHECKBOX ESTIVER MARCADO
                // Adicionando um log para confirmar que entramos neste bloco
                Log::info("Iniciando a lógica de criação de série recorrente para Master ID {$reserva->id}.");

                $masterReserva = $reserva;

                // Garante que a data de início é um objeto Carbon para manipulação segura
                // NOVA CORREÇÃO: Força a conversão para string antes do parse para total segurança
                $masterDate = Carbon::parse($masterReserva->date->format('Y-m-d'));

                // 5.1. Definir a janela de renovação: Da próxima semana até 6 meses
                $startDate = $masterDate->copy()->addWeek();
                $endDate = $masterDate->copy()->addMonths(6); // 6 meses a partir da data da reserva mestra

                Log::info("Criando série recorrente Master ID {$reserva->id}: Início ({$startDate->toDateString()}) - Fim ({$endDate->toDateString()}).");

                // Parâmetros da série
                $dayOfWeek = $masterReserva->day_of_week;
                $startTime = $masterReserva->start_time;
                $endTime = $masterReserva->end_time;
                $price = $masterReserva->price;
                $clientName = $masterReserva->client_name;
                $clientContact = $masterReserva->client_contact;
                $userId = $masterReserva->user_id;
                $masterId = $reserva->id; // Usa o ID já salvo da mestra
                $managerId = Auth::id();

                $newReservasToCreate = [];

                $currentDate = $startDate->copy();

                while ($currentDate->lessThanOrEqualTo($endDate)) {
                    $dateString = $currentDate->toDateString();
                    $isConflict = false;

                    // Checagem de Conflito (Outros Clientes: confirmed/pending)
                    // Esta é a única checagem necessária, pois garantimos que o horário é livre para aluguel.
                    $isOccupiedByOtherCustomer = Reserva::whereDate('date', $dateString)
                        ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime)
                        ->where('is_fixed', false)
                        ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]) // PADRONIZADO
                        ->exists();

                    if ($isOccupiedByOtherCustomer) {
                        $isConflict = true;
                        Log::warning("Conflito com OUTRO CLIENTE durante a repetição da série #{$masterId} na data {$dateString}. Slot pulado.");
                    }

                    // NOVO FLUXO: Busca o slot fixo, se existir, para DELETAR (consumir), mas NÃO USA ISSO COMO CONFLITO.
                    $fixedSlot = null;
                    if (!$isConflict) {
                        // Busca o slot fixo (se existir) para DELETAR, mas a criação procede mesmo que ele não exista.
                        $fixedSlot = Reserva::where('is_fixed', true)
                            ->whereDate('date', $dateString)
                            ->where('start_time', $startTime)
                            ->where('end_time', $endTime)
                            ->where('status', Reserva::STATUS_FREE) // PADRONIZADO
                            ->first();
                    }

                    // Cria a nova reserva se não houver conflito real (confirmado/pendente por outro cliente)
                    if (!$isConflict) {
                        $newReservasToCreate[] = [
                            'user_id' => $userId,
                            'manager_id' => $managerId,
                            'date' => $dateString,
                            'day_of_week' => $dayOfWeek,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'price' => $price,
                            'final_price' => $price, // Definido como o preço base na criação
                            // CORREÇÃO: Zerado para slots futuros.
                            'signal_value' => 0.00,
                            'total_paid' => 0.00,
                            'payment_status' => 'pending',
                            // FIM CORREÇÃO
                            'client_name' => $clientName,
                            'client_contact' => $clientContact,
                            'notes' => $masterReserva->notes, // Mantém a nota da reserva mestra
                            'status' => Reserva::STATUS_CONFIRMADA, // PADRONIZADO
                            'is_fixed' => false,
                            'is_recurrent' => true,
                            'recurrent_series_id' => $masterId,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];

                        if ($fixedSlot) {
                            $fixedSlot->delete(); // Consome o slot verde/FREE
                            // NOVO LOG: Confirma a exclusão do slot fixo para diagnóstico
                            Log::debug("Slot fixo ID {$fixedSlot->id} consumido para data recorrente {$dateString} em série {$masterId}.");
                        } else {
                            // NOVO LOG: Alerta se não encontrar o slot fixo
                            Log::warning("Nenhum slot fixo encontrado para consumir para data recorrente {$dateString} em série {$masterId}.");
                        }
                    } else {
                        $conflictedOrSkippedCount++;
                    }

                    $currentDate->addWeek();
                }

                if (!empty($newReservasToCreate)) {
                    Reserva::insert($newReservasToCreate);
                    // NOVO LOG: Confirma a inserção em massa
                    Log::info("Inserção em massa concluída: " . count($newReservasToCreate) . " reservas recorrentes criadas para série {$masterId}.");

                    $recurrentCount = count($newReservasToCreate);
                }

                $successMessage .= " Série recorrente de " . ($recurrentCount + 1) . " reservas (incluindo a mestra) adicionada até " . $endDate->format('d/m/Y') . ".";
                if ($conflictedOrSkippedCount > 0) {
                    $successMessage .= " Atenção: {$conflictedOrSkippedCount} slots foram pulados devido a conflitos.";
                }
            }
            // FIM DA LÓGICA DE RECORRÊNCIA

            // 6. NOVO: GERA TRANSAÇÃO FINANCEIRA (SINAL)
            if ($signalValue > 0) {
                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => Auth::id(),
                    'amount' => $signalValue,
                    'type' => 'signal',
                    'payment_method' => 'pix', // Assumindo PIX na confirmação manual
                    'description' => 'Sinal recebido na confirmação da reserva/série',
                    'paid_at' => Carbon::now(),
                ]);
                Log::info("Transação de Sinal (R$ {$signalValue}) registrada para Master ID {$reserva->id}.");
            }


            DB::commit();

            if ($signalValue > 0) {
                $successMessage .= " Sinal de R$ " . number_format($signalValue, 2, ',', '.') . " registrado.";
            }

            return redirect()->back()->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            $logMessage = "Erro fatal ao confirmar reserva ID: {$reserva->id} (Recorrente: " . ($isRecurrent ? 'Sim' : 'Não') . "): " . $e->getMessage();
            Log::error($logMessage, ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro interno ao processar a confirmação: ' . $e->getMessage());
        }
    }

    /**
     * NOVO MÉTODO: Converte uma reserva PONTUAL CONFIRMADA em uma série recorrente (Mestra) e cria as cópias futuras (6 meses).
     */
    public function convertPunctualToRecurrent(Request $request, Reserva $reserva)
    {
        // 1. Checagens iniciais
        if ($reserva->is_fixed || $reserva->is_recurrent) {
            return redirect()->back()->with('error', 'Esta reserva já é um slot fixo ou já faz parte de uma série recorrente.');
        }

        if ($reserva->status !== Reserva::STATUS_CONFIRMADA) { // PADRONIZADO
            return redirect()->back()->with('error', 'Apenas reservas com status CONFIRMADO podem ser convertidas em séries.');
        }

        DB::beginTransaction();
        try {
            // 2. Transforma a reserva atual em Mestra da Série
            $masterId = $reserva->id;
            $reserva->is_recurrent = true;
            $reserva->recurrent_series_id = $masterId;
            $reserva->manager_id = Auth::id();
            $reserva->save();

            Log::info("Reserva ID {$masterId} convertida em série MESTRA.");

            // 3. Define a janela de agendamento (Da próxima semana até 6 meses)
            $masterDate = Carbon::parse($reserva->date->format('Y-m-d'));
            $startDate = $masterDate->copy()->addWeek();
            $endDate = $masterDate->copy()->addMonths(6); // CORRIGIDO

            Log::info("Iniciando a criação das cópias: Início ({$startDate->toDateString()}) - Fim ({$endDate->toDateString()}).");


            // Parâmetros da série
            $dayOfWeek = $reserva->day_of_week;
            $startTime = $reserva->start_time;
            $endTime = $reserva->end_time;
            $price = $reserva->price;
            $clientName = $reserva->client_name;
            $clientContact = $reserva->client_contact;
            $userId = $reserva->user_id;
            $managerId = Auth::id();

            $newReservasToCreate = [];
            $conflictedOrSkippedCount = 0;
            $currentDate = $startDate->copy();

            while ($currentDate->lessThanOrEqualTo($endDate)) {
                $dateString = $currentDate->toDateString();
                $isConflict = false;

                // Checagem de Conflito (Outros Clientes: confirmed/pending)
                $isOccupiedByOtherCustomer = Reserva::whereDate('date', $dateString)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->where('is_fixed', false)
                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]) // PADRONIZADO
                    ->exists();

                if ($isOccupiedByOtherCustomer) {
                    $isConflict = true;
                    Log::warning("Conflito com OUTRO CLIENTE durante a repetição da série #{$masterId} na data {$dateString}. Slot pulado.");
                }

                // Busca e deleta o slot fixo, se existir
                $fixedSlot = null;
                if (!$isConflict) {
                    $fixedSlot = Reserva::where('is_fixed', true)
                        ->whereDate('date', $dateString)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTime)
                        ->where('status', Reserva::STATUS_FREE) // PADRONIZADO
                        ->first();
                }

                if (!$isConflict) {
                    $newReservasToCreate[] = [
                        'user_id' => $userId,
                        'manager_id' => $managerId,
                        'date' => $dateString,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => $price,
                        'final_price' => $price, // Definido como o preço base na criação
                        // CORREÇÃO: Zerado para slots futuros.
                        'signal_value' => 0.00,
                        'total_paid' => 0.00,
                        'payment_status' => 'pending',
                        // FIM CORREÇÃO
                        'client_name' => $clientName,
                        'client_contact' => $clientContact,
                        'notes' => $reserva->notes, // Mantém a nota da reserva mestra
                        'status' => Reserva::STATUS_CONFIRMADA, // PADRONIZADO
                        'is_fixed' => false,
                        'is_recurrent' => true,
                        'recurrent_series_id' => $masterId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];

                    if ($fixedSlot) {
                        $fixedSlot->delete();
                        Log::debug("Slot fixo ID {$fixedSlot->id} consumido para data recorrente {$dateString} em série {$masterId}.");
                    }
                } else {
                    $conflictedOrSkippedCount++;
                }

                $currentDate->addWeek();
            }

            if (!empty($newReservasToCreate)) {
                Reserva::insert($newReservasToCreate);
                Log::info("Inserção em massa concluída: " . count($newReservasToCreate) . " reservas recorrentes criadas para série {$masterId}.");
            }

            DB::commit();

            $totalCreated = count($newReservasToCreate) + 1; // +1 para a mestra
            $successMessage = "Conversão concluída! A reserva ID {$masterId} agora é a Mestra, e {$totalCreated} reservas foram agendadas até " . $endDate->format('d/m/Y') . ".";

            if ($conflictedOrSkippedCount > 0) {
                $successMessage .= " Atenção: {$conflictedOrSkippedCount} slots foram pulados devido a conflitos.";
            }

            return redirect()->back()->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro fatal ao converter para recorrente (ID: {$masterId}): " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro interno ao converter a reserva para série: ' . $e->getMessage());
        }
    }


    /**
     * NOVO MÉTODO: Rejeita uma reserva pendente.
     */
    public function rejeitar(Request $request, Reserva $reserva)
    {
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        if ($reserva->status !== Reserva::STATUS_PENDENTE) { // PADRONIZADO
            return redirect()->back()->with('error', 'Esta reserva já foi processada.');
        }

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_REJEITADA; // PADRONIZADO
            $reserva->cancellation_reason = $validated['rejection_reason'] ?? 'Rejeitada pela administração.';
            $reserva->manager_id = Auth::id();
            $reserva->save();

            // 1. Recria o slot fixo original
            $this->recreateFixedSlot($reserva);

            // 2. Apaga outras reservas PENDENTES no mesmo horário (opcional, mas recomendado para liberar agenda)
            Reserva::where('date', $reserva->date)
                ->where('start_time', $reserva->start_time)
                ->where('end_time', $reserva->end_time)
                ->where('id', '!=', $reserva->id)
                ->where('status', Reserva::STATUS_PENDENTE) // PADRONIZADO
                ->delete();
            Log::info("Reservas pendentes conflitantes deletadas após rejeição da Reserva ID: {$reserva->id}.");


            DB::commit();

            return redirect()->back()->with('success', "Reserva de {$reserva->client_name} rejeitada com sucesso. O horário foi liberado.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro fatal ao rejeitar reserva ID: {$reserva->id}: " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro interno ao processar a rejeição: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza o status de um slot fixo de inventário (usado na view de Todas as Reservas).
     * Permite alternar entre 'free' e 'maintenance'.
     */
    public function toggleFixedReservaStatus(Request $request, Reserva $reserva)
    {
        // 1. Validação básica para garantir que é um slot fixo
        if (!$reserva->is_fixed) {
            return response()->json(['success' => false, 'message' => 'Esta não é uma reserva de inventário fixo.'], 400);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE])], // PADRONIZADO
        ]);

        // 2. Checa se o status atual já é o solicitado (evita escrita desnecessária)
        if ($reserva->status === $validated['status']) {
            $message = 'O status já está definido como ' . $validated['status'];
            return response()->json(['success' => false, 'message' => $message], 400);
        }

        // 3. Checagem de integridade (Não pode sair de maintenance/free se houver conflito de cliente)
        if ($validated['status'] === Reserva::STATUS_FREE) { // PADRONIZADO
            // Ao tentar retornar para FREE, verifica se há algum cliente com pending/confirmed
            $overlap = Reserva::where('date', $reserva->date)
                ->where('start_time', $reserva->start_time)
                ->where('end_time', $reserva->end_time)
                ->where('is_fixed', false)
                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]) // PADRONIZADO
                ->exists();

            if ($overlap) {
                return response()->json(['success' => false, 'message' => 'Impossível reverter para LIVRE. Há uma reserva de cliente (confirmada/pendente) ocupando este horário.'], 400);
            }
        }


        DB::beginTransaction();
        try {
            $reserva->status = $validated['status'];
            $reserva->manager_id = Auth::id(); // Registra quem mudou o status
            $reserva->save();

            DB::commit();

            $message = $reserva->status === Reserva::STATUS_FREE ? 'Slot fixo disponibilizado (Livre) com sucesso.' : 'Slot fixo marcado como Manutenção (Indisponível) com sucesso.';

            Log::info("Slot fixo ID: {$reserva->id} alterado para status: {$reserva->status} por Gestor ID: " . Auth::id());

            return response()->json(['success' => true, 'message' => $message], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao alterar status do slot fixo ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao alterar status: ' . $e->getMessage()], 500);
        }
    }
}
