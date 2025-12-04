<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
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
        // NOVO: Conta pendências para o Dashboard (mesmo que não seja usado diretamente na view, é bom manter)
        $pendingReservationsCount = Reserva::where('status', Reserva::STATUS_PENDENTE)
            ->whereDate('date', '>=', Carbon::today()->startOfDay())
            ->count();

        $expiringSeries = $this->getEndingRecurrentSeries();

        return view('dashboard', [
            'pendingReservationsCount' => $pendingReservationsCount, // Adicionado para completude
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
            $endTimeNormalized = Carbon::parse($endTime)->format('H:i:s');
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

    // -------------------------------------------------------------------------
    // ✅ MÉTODOS CORRIGIDOS PARA O FULLCALENDAR (API FullCalendar)
    // -------------------------------------------------------------------------

    /**
     * API: Retorna todas as reservas de cliente (confirmadas, pendentes e concluídas) para o FullCalendar.
     */
    public function getConfirmedReservations(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        // Status visíveis: Confirmada, Pendente e CONCLUIDA (Paga/Baixada)
        $visibleStatuses = [
            Reserva::STATUS_CONFIRMADA,
            Reserva::STATUS_PENDENTE,
            Reserva::STATUS_CONCLUIDA,
        ];

        // LOG: Diagnóstico dos status sendo buscados
        Log::debug('getConfirmedReservations: Buscando reservas com status: ' . implode(', ', $visibleStatuses));

        $reservations = Reserva::whereIn('status', $visibleStatuses)
            ->where('is_fixed', false)
            // CORREÇÃO CRÍTICA: Removido o filtro where('is_cancelled', false).
            // Se o status é CONFIRMADA, PENDENTE ou CONCLUIDA, ele não deve ser considerado cancelado
            // para fins de exibição no calendário. Isso resolve o problema de registros CONCLUIDA
            // que podem ter o flag is_cancelled=true incorretamente no DB.
            // Omitir o filtro de is_cancelled é mais seguro, pois o filtro de status já exclui o STATUS_CANCELADA.
            ->whereBetween('date', [$start, $end])
            ->get();

        // LOG: Diagnóstico da contagem de reservas retornadas
        Log::debug('getConfirmedReservations: Total de reservas encontradas: ' . $reservations->count());


        // Retornar no formato FullCalendar
        return response()->json($this->mapToFullCalendarEvents($reservations));
    }

    /**
     * Helper para mapear objetos Reserva para o formato JSON do FullCalendar.
     */
    protected function mapToFullCalendarEvents($reservations)
    {
        $events = [];

        foreach ($reservations as $reserva) {

            // Tratamento de Data/Hora (Garante que o cast para Carbon tenha sido feito pelo Laravel)
            try {
                // Se a data for um objeto Carbon, usa o format para string
                $dateString = is_object($reserva->date) ? $reserva->date->format('Y-m-d') : $reserva->date;

                $startDateTime = Carbon::parse($dateString . ' ' . $reserva->start_time);
                $endDateTime = Carbon::parse($dateString . ' ' . $reserva->end_time);
            } catch (\Exception $e) {
                // Fallback para strings (caso o cast não funcione)
                $startDateTime = Carbon::parse($reserva->date . ' ' . $reserva->start_time);
                $endDateTime = Carbon::parse($reserva->date . ' ' . $reserva->end_time);
            }

            // -------------------------------------------------------------------------
            // 1. Definição da Base: Avulso Confirmado
            // -------------------------------------------------------------------------
            $title = $reserva->client_name . ' - R$ ' . number_format($reserva->final_price, 2, ',', '.');
            $color = '#4f46e5'; // Indigo (Avulso Confirmado)
            $className = 'fc-event-quick';
            $isPaid = false; // Valor padrão


            // -------------------------------------------------------------------------
            // 2. Lógica de Status (Prioridade de Sobrescrita)
            // -------------------------------------------------------------------------

            if ($reserva->is_recurrent) {
                // Sobrescreve a cor base para Recorrente
                $title = 'RECORR.: ' . $title;
                $color = '#C026D3'; // Fuchsia (Recorrente Confirmado)
                $className = 'fc-event-recurrent';
            }

            if ($reserva->status === Reserva::STATUS_PENDENTE) {
                // Sobrescreve para Pendente (maior prioridade visual)
                $color = '#ff9800'; // Orange (Pendente)
                $className = 'fc-event-pending';
                $isPaid = false;
            }

            // -------------------------------------------------------------------------
            // ✅ CORREÇÃO CRÍTICA APLICADA AQUI: LÓGICA DE PAGAMENTO (FADE OUT)
            // -------------------------------------------------------------------------

            // Flag que indica se houve qualquer pagamento (sinal > 0) ou se o status é finalizado
            // O uso de total_paid é mais robusto contra o campo payment_status ser NULL no DB.
            $hasAnyPayment = $reserva->total_paid > 0.00;


            if ($reserva->status === Reserva::STATUS_CONCLUIDA) {
                // Status finalizado (pago no caixa)
                $title = 'PAGO: ' . $title;
                $color = '#10b981'; // Emerald/Green (Cor para evento concluído/baixado)
                $className .= ' fc-event-concluida';
                $isPaid = true; // Definido como PAGO/CONCLUÍDO

            } elseif ($hasAnyPayment && $reserva->status !== Reserva::STATUS_PENDENTE) {
                // É confirmado (não pendente) e tem algum pagamento (sinal/parcial)
                $isPaid = true;
                // Adiciona classe para desbotamento sutil no front-end
                $className .= ' fc-event-paid';
            }

            // NOTE: Se o status for PENDENTE, mesmo que tenha sinal, o isPaid fica false para evitar o fade
            //       e para priorizar a cor Laranja/Pendente.
            // -------------------------------------------------------------------------


            $events[] = [
                'id' => $reserva->id,
                'title' => $title,
                'start' => $startDateTime->toDateTimeString(),
                'end' => $endDateTime->toDateTimeString(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'classNames' => [trim($className)], // Remove espaços em branco
                'extendedProps' => [
                    'status' => $reserva->status,
                    'price' => $reserva->price,
                    'final_price' => $reserva->final_price,
                    'signal_value' => $reserva->signal_value,
                    'total_paid' => $reserva->total_paid,
                    'payment_status' => $reserva->payment_status,
                    'is_recurrent' => $reserva->is_recurrent,
                    'is_paid' => $isPaid, // CRÍTICO: Agora definido de forma robusta
                ],
            ];
            // LOG de diagnóstico para CONCLUIDA
            if ($reserva->status === Reserva::STATUS_CONCLUIDA) {
                 Log::debug("Reserva Concluída ID {$reserva->id} mapeada: Title='{$title}', Color='{$color}'");
            }
        }

        return $events;
    }

    // -------------------------------------------------------------------------
    // FIM MÉTODOS API
    // -------------------------------------------------------------------------

    /**
     * Finaliza o pagamento de uma reserva e, opcionalmente, atualiza o preço de reservas futuras da série.
     * Rota: POST /admin/pagamentos/{reserva}/finalizar
     */
    public function finalizarPagamento(Request $request, $reservaId)
    {
        // 1. Busca a Reserva manualmente
        $reserva = Reserva::find($reservaId);

        if (!$reserva) {
            Log::error("Reserva não encontrada para o ID {$reservaId} durante finalizarPagamento.");
            return response()->json(['success' => false, 'message' => 'Reserva não encontrada.'], 404);
        }

        // LOG DE DIAGNÓSTICO: Mostra TODO o request, incluindo o apply_to_series
        Log::debug('finalizarPagamento Request Data: ' . json_encode($request->all()));
        Log::debug('apply_to_series flag value (boolean): ' . ($request->boolean('apply_to_series') ? 'TRUE' : 'FALSE'));

        // 2. Validação dos dados de entrada
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
                'status' => Reserva::STATUS_CONCLUIDA, // NOVO: Marca como CONCLUÍDA ao finalizar o pagamento
            ]);

            Log::info("Reserva ID {$reserva->id} paga e concluída. Final Price: R$ {$finalPrice}, Total Paid: R$ {$newTotalPaid}. Status setado para: " . Reserva::STATUS_CONCLUIDA);


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

                Log::info('*** INICIANDO PROPAGAÇÃO DE PREÇO PARA SÉRIE RECORRENTE ***');

                $newPriceForSeries = $finalPrice;
                $masterId = $reserva->recurrent_series_id ?? $reserva->id;
                // Deve usar o objeto Carbon para extrair o dateString
                $reservaDate = Carbon::parse($reserva->date)->toDateString();

                Log::debug("Propagação Detalhes: Master ID {$masterId}, Data de Corte {$reservaDate}, Novo Preço R$ {$newPriceForSeries}");

                // CRÍTICO: Identifica todas as reservas futuras elegíveis que PRECISAM de atualização
                try {
                    $updatedCount = Reserva::where(function ($query) use ($masterId) {
                                     // Atinge a série inteira (mestra e cópias)
                                     $query->where('recurrent_series_id', $masterId)
                                             ->orWhere('id', $masterId);
                                 })
                                 // CRÍTICO: Pega todas as reservas com data ESTREITAMENTE MAIOR que a data atual
                                 ->whereDate('date', '>', $reservaDate)
                                 // Filtra por horário, garantindo o slot semanal correto
                                 ->where('start_time', $reserva->start_time)
                                 ->where('end_time', $reserva->end_time)
                                 ->where('is_fixed', false) // Apenas reservas de cliente
                                 // CORREÇÃO CRÍTICA: Alvo: APENAS reservas ATIVAS (Confirmadas)
                                 // Reservas ativas recorrentes têm status 'confirmed'
                                 ->where('status', Reserva::STATUS_CONFIRMADA)
                                 // APENAS ATUALIZA SE O PREÇO ATUAL FOR DIFERENTE DO NOVO PREÇO
                                 ->where('price', '!=', $newPriceForSeries)
                                 ->update([
                                     // Atualiza o preço base (price) e o preço final (final_price)
                                     'price' => $newPriceForSeries,
                                     'final_price' => $newPriceForSeries,
                                     'manager_id' => Auth::id(),
                                 ]);

                    if ($updatedCount > 0) {
                        Log::info("Preço de série recorrente (ID {$masterId}) atualizado para R$ {$newPriceForSeries} em {$updatedCount} reservas futuras.");
                        $message = "Pagamento finalizado e preço da série atualizado com sucesso! ({$updatedCount} reservas alteradas)";
                    } else {
                        Log::info("Propagação executada, mas 0 reservas futuras atualizadas. Motivo: Preço já estava R$ {$newPriceForSeries} ou não houve reservas futuras elegíveis.");
                        $message = "Pagamento finalizado. Preço da série recorrente já estava atualizado ou nenhuma reserva futura elegível encontrada.";
                    }

                } catch (\Exception $e) {
                    // Log de erro específico para a query de update
                    Log::error("Erro na query de propagação de preço para Master ID {$masterId}: " . $e->getMessage());
                    throw $e; // Re-lança para que o rollback ocorra
                }
            } else {
                $message = "Pagamento finalizado com sucesso!";
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            // Adiciona log de erro detalhado da propagação
            Log::error("Erro no processo de finalizarPagamento (ID: {$reservaId}): " . $e->getMessage(), ['exception' => $e]);
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

                // CORREÇÃO CRÍTICA: Obtém a data da reserva mestra como objeto Carbon
                // Usamos ->date diretamente pois o Laravel já deve ter castado para Carbon
                $masterDate = $masterReserva->date;

                // 5.1. Definir a janela de renovação: Da próxima semana até 6 meses
                $startDate = $masterDate->copy()->addWeek(); // Começa na próxima semana
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

                // Garante que o loop só comece APÓS a data da reserva mestra
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

                    // NOVO FLUXO: Busca o slot fixo, se existir, para DELETAR (consumir)
                    $fixedSlot = null;
                    if (!$isConflict) {
                        $fixedSlot = Reserva::where('is_fixed', true)
                            ->whereDate('date', $dateString)
                            ->where('start_time', $startTime)
                            ->where('end_time', $endTime)
                            ->where('status', Reserva::STATUS_FREE) // PADRONIZADO
                            ->first();
                    }

                    // Cria a nova reserva se não houver conflito real
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
                            Log::debug("Slot fixo ID {$fixedSlot->id} consumido para data recorrente {$dateString} em série {$masterId}.");
                        } else {
                            Log::warning("Nenhum slot fixo encontrado para consumir para data recorrente {$dateString} em série {$masterId}.");
                        }
                    } else {
                        $conflictedOrSkippedCount++;
                    }

                    $currentDate->addWeek();
                }

                if (!empty($newReservasToCreate)) {
                    Reserva::insert($newReservasToCreate);
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
            // CORREÇÃO CRÍTICA: Obtém a data da reserva mestra como objeto Carbon
            $masterDate = $reserva->date; // Assume que o Laravel fez o cast
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
            return response()->json(['success' => false, 'message' => 'Esta reserva já foi processada.'], 400);
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

            return response()->json(['success' => true, 'message' => "Reserva de {$reserva->client_name} rejeitada com sucesso. O horário foi liberado."], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro fatal ao rejeitar reserva ID: {$reserva->id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao processar a rejeição: ' . $e->getMessage()], 500);
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


    // -------------------------------------------------------------------------
    // LÓGICA DE RENOVAÇÃO
    // -------------------------------------------------------------------------

    /**
     * Encontra a data máxima de uma série recorrente (que não seja um slot fixo).
     */
    protected function getSeriesMaxDate(int $masterId): ?Carbon
    {
        $maxDate = Reserva::where(function($query) use ($masterId) {
             $query->where('recurrent_series_id', $masterId)
                 ->orWhere('id', $masterId);
             })
             ->where('is_recurrent', true)
             ->where('is_fixed', false)
             ->where('status', Reserva::STATUS_CONFIRMADA) // PADRONIZADO
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
            ->where('status', Reserva::STATUS_CONFIRMADA) // PADRONIZADO
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
     * API: Estende uma série de reservas recorrentes por mais 6 meses.
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

        // CORREÇÃO AQUI: De addYear() para addMonths(6)
        $endDate = $currentMaxDate->copy()->addMonths(6); // CORRIGIDO

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
        // Mantém os valores de pagamento da série mestra para as novas cópias
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
                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]) // PADRONIZADO
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
                        ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]) // PADRONIZADO
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
                        ->where('status', Reserva::STATUS_FREE) // PADRONIZADO
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
                        'final_price' => $price,
                        // Mantido 0.00 para novos slots individuais da série
                        'signal_value' => 0.00,
                        'total_paid' => 0.00,
                        'payment_status' => 'pending',
                        // ---------------------------------------------------------------------
                        'client_name' => $clientName,
                        'client_contact' => $clientContact,
                        'status' => Reserva::STATUS_CONFIRMADA, // PADRONIZADO
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
                // 4. Atualiza a data final em todas as reservas existentes da série.
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
        if ($reserva->status === Reserva::STATUS_CANCELADA || $reserva->status === Reserva::STATUS_REJEITADA) { // PADRONIZADO
            return response()->json(['message' => 'Esta reserva já está cancelada ou rejeitada.'], 400);
        }


        // =====================================================================
        // FLUXO 1: SOLICITAÇÃO DE CANCELAMENTO DE SÉRIE (RECORRENTE)
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
        // FLUXO 2: RESERVA RECORRENTE INDIVIDUAL (Bloqueio)
        // =====================================================================
        if ($reserva->is_recurrent && !$isSeriesRequest) {
            return response()->json(['message' => 'Você não pode cancelar slots individuais de uma série recorrente. Use a opção de cancelamento de série no histórico.'], 400);
        }

        // =====================================================================
        // FLUXO 3: CANCELAMENTO DE RESERVA PONTUAL (Ação Direta)
        // =====================================================================
        if (!$reserva->is_recurrent) {
            DB::beginTransaction();
            try {
                $reserva->status = Reserva::STATUS_CANCELADA; // PADRONIZADO
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
            // PADRONIZADO: Busca apenas status FREE e is_fixed=true
            'schedule_id' => ['required', 'integer', 'exists:reservas,id,is_fixed,1,status,' . Reserva::STATUS_FREE],
            'reserva_conflito_id' => 'nullable',

            // Validação de formato/presença do cliente, SEM 'unique'
            'nome_cliente' => 'required|string|max:255',
            'contato_cliente' => 'required|string|regex:/^\d{10,11}$/|max:20',
            'email_cliente' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:500',
            // Adiciona validação do sinal na pré-reserva (embora seja pré-reserva, é bom ter)
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

        // NOVA LÓGICA DE VALORES E PAGAMENTO (para storePublic)
        $price = (float) $validated['price'];
        $signalValue = (float) ($validated['signal_value'] ?? 0.00);
        $totalPaid = $signalValue;

        $paymentStatus = 'pending';
        if ($signalValue > 0 && $signalValue < $price) {
            $paymentStatus = 'partial';
        } elseif ($signalValue >= $price) {
            $paymentStatus = 'paid';
        }
        // FIM NOVA LÓGICA DE VALORES E PAGAMENTO


        // Normaliza as horas para o formato do banco de dados (H:i:s)
        $startTimeNormalized = Carbon::createFromFormat('G:i', $startTime)->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $endTime)->format('H:i:s');

        DB::beginTransaction();
        try {
            // 2. CHAMADA DA LÓGICA findOrCreateClient local (Encontra ou cria o cliente)
            $clientUser = $this->findOrCreateClient([
                'name' => $nomeCliente,
                'email' => $emailCliente,
                'whatsapp_contact' => $contatoCliente,
                'data_nascimento' => null,
            ]);

            // === 3. Nova Validação: BLOQUEIO DE MÚLTIPLAS SOLICITAÇÕES DO MESMO CLIENTE ===
            $existingReservation = Reserva::where('user_id', $clientUser->id)
                ->where('date', $date)
                ->where('start_time', $startTimeNormalized)
                ->where('end_time', $endTimeNormalized)
                ->where('is_fixed', false)
                ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA]) // PADRONIZADO
                ->first();

            if ($existingReservation) {
                DB::rollBack();

                $statusMessage = $existingReservation->status === Reserva::STATUS_PENDENTE // PADRONIZADO
                    ? 'aguardando aprovação da administração'
                    : 'já foi aprovada';

                $validator->errors()->add('reserva_duplicada',
                    "Você já solicitou reserva para este horário e ela está {$statusMessage}. " .
                    "Aguarde o contato da nossa equipe."
                );

                Log::warning("Tentativa de reserva duplicada - Cliente: {$clientUser->name}, Data: {$date}, Horário: {$startTimeNormalized}-{$endTimeNormalized}, Status: {$existingReservation->status}");

                throw new ValidationException($validator);
            }

            // === 4. CORREÇÃO CRÍTICA: BLOQUEIO CONTRA RESERVAS JÁ CONFIRMADAS ===
            // Uma nova pré-reserva (pending) não pode ser feita em um horário que já está CONFIRMADO por outro cliente.
            $confirmedConflict = Reserva::where('date', $date)
                ->where('is_fixed', false) // Apenas reservas de clientes (não slots fixos)
                ->where('status', Reserva::STATUS_CONFIRMADA) // PADRONIZADO
                ->where('start_time', '<', $endTimeNormalized)
                ->where('end_time', '>', $startTimeNormalized)
                ->exists();

            if ($confirmedConflict) {
                DB::rollBack();
                $validator->errors()->add('confirmed_conflict', 'Este horário já está confirmado e indisponível para pré-reserva. Por favor, selecione outro slot livre.');
                // Força o erro de validação para a tela pública
                throw new ValidationException($validator);
            }
            // === FIM DA VALIDAÇÃO DE CONFLITO CONFIRMADO ===

            // === 5. Mudança Crítica: Não fazer checagem de conflito para outras reservas PENDENTES (Permite fila de espera) ===

            // 6. Limpa o slot fixo (evento verde)
            $fixedSlot = Reserva::where('id', $scheduleId)
                ->where('is_fixed', true)
                ->where('status', Reserva::STATUS_FREE) // PADRONIZADO
                ->first();

            if (!$fixedSlot) {
                DB::rollBack();
                // Se o slot não existe mais, a transação deve ser abortada.
                $validator->errors()->add('schedule_id', 'O slot selecionado não existe mais.');
                throw new ValidationException($validator);
            }
            // O slot fixo é consumido/removido na CONFIRMAÇÃO pelo Admin.
            // Aqui, na pré-reserva, apenas a reserva PENDENTE é criada.


            // 7. Criação da Reserva Real (Status Pendente)
            $reserva = Reserva::create([
                'user_id' => $clientUser->id,
                'date' => $date,
                'day_of_week' => Carbon::parse($date)->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $price,
                'final_price' => $price, // Define o final_price igual ao price
                // Adicionado: Valor do Sinal, Total Pago e Status de Pagamento
                'signal_value' => $signalValue,
                'total_paid' => $totalPaid,
                'payment_status' => $paymentStatus,
                'client_name' => $clientUser->name,
                'client_contact' => $clientUser->whatsapp_contact,
                'notes' => $validated['notes'] ?? null,
                'status' => Reserva::STATUS_PENDENTE, // PADRONIZADO
                'is_fixed' => false,
                'is_recurrent' => false,
                // NOVO: Campo para identificar qual slot fixo foi selecionado
                'fixed_slot_id' => $scheduleId,
            ]);

            DB::commit();

            // 8. Mensagem de Sucesso e Link do WhatsApp
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
            "{$valorSinal}\n" .
            "Status: AGUARDANDO CONFIRMAÇÃO";

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
        $today = Carbon::today()->startOfDay(); // Define hoje à meia-noite

        $futureOrTodayCount = Reserva::where('status', Reserva::STATUS_PENDENTE) // PADRONIZADO
             ->whereDate('date', '>=', $today) // Apenas reservas futuras ou de hoje
             ->count();

        return response()->json(['count' => $futureOrTodayCount], 200);
    }
}
