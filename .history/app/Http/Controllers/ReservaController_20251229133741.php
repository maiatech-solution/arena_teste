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
use App\Models\FinancialTransaction;
use App\Http\Controllers\FinanceiroController;
use Illuminate\Database\Eloquent\Builder;

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
        $pendingReservationsCount = Reserva::where('status', Reserva::STATUS_PENDENTE)
            ->whereDate('date', '>=', Carbon::today()->startOfDay())
            ->count();

        $expiringSeries = $this->getEndingRecurrentSeries();

        return view('dashboard', [
            'pendingReservationsCount' => $pendingReservationsCount,
            'expiringSeriesCount' => count($expiringSeries),
            'expiringSeries' => $expiringSeries,
        ]);
    }


    // -------------------------------------------------------------------------
    // MÉTODOS AUXILIARES (CheckOverlap, Manipulação de Slots Fixos, Cliente)
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
            $query->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]);
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
        // MUDANÇA: Apenas 'confirmed' causa conflito real que impede outra confirmação
        $activeStatuses = [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA];

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
                'status' => Reserva::STATUS_FREE,
                'is_fixed' => true,
                'is_recurrent' => $reserva->is_recurrent, // Mantém a natureza de recorrência
                'client_name' => 'Slot Fixo', // Placeholder para colunas NOT NULL
                'client_contact' => 'N/A',  // Placeholder para colunas NOT NULL
                'user_id' => null,           // Deve ser NULL
            ]);
            Log::info("Slot fixo recriado para {$reserva->date} {$reserva->start_time}.");
        } else {
            // Se o slot existir, mas estiver em 'maintenance', mantém.
            if (!in_array($existingFixedSlot->status, [Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE])) {
                $existingFixedSlot->update(['status' => Reserva::STATUS_FREE]);
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
            ->whereIn('status', [Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE])
            ->first();

        if ($fixedSlot) {
            // Remove o slot de disponibilidade para liberar o espaço
            $fixedSlot->delete();
            Log::info("Slot fixo ID {$fixedSlot->id} consumido para a reserva ID {$reserva->id}.");
        } else {
            Log::warning("Tentativa de consumir slot fixo para reserva ID {$reserva->id}, mas nenhum slot FREE/MAINTENANCE foi encontrado para a data/hora.");
        }
    }


    /**
     * Encontra ou cria um usuário cliente (baseado no whatsapp_contact).
     */
    public function findOrCreateClient(array $data): User
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

            $updateData['name'] = $name;

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
                'role' => 'cliente',
                'is_admin' => false,
                'data_nascimento' => $data['data_nascimento'] ?? null,
            ]);
            Log::info("Novo cliente criado (ID: {$newUser->id}). E-mail usado: {$emailToUse}");
            return $newUser;
        }
    }


    // -------------------------------------------------------------------------
    // 🎯 MÉTODOS DE SERVIÇO CENTRALIZADO (LÓGICA DE NEGÓCIOS)
    // -------------------------------------------------------------------------

    /**
     * Cria/Atualiza uma Reserva de Cliente confirmada, consumindo o slot fixo.
     * Método centralizado usado por AdminController::storeReserva e ::storeQuickReservaApi.
     *
     * @param array $validatedData Dados validados da requisição.
     * @param User $clientUser O objeto User do cliente.
     * @param int|null $fixedSlotId ID do slot fixo a ser consumido (se houver).
     * @return Reserva
     * @throws \Exception
     */
    public function createConfirmedReserva(array $validatedData, User $clientUser, ?int $fixedSlotId = null): Reserva
    {
        // 1. Checagem de Conflito (Contra reservas reais)
        if ($this->checkOverlap($validatedData['date'], $validatedData['start_time'], $validatedData['end_time'], true, $fixedSlotId)) {
             throw new \Exception('O horário selecionado já está ocupado por outra reserva confirmada ou pendente.');
        }

        // Normaliza as horas para o formato H:i:s
        $startTimeNormalized = Carbon::createFromFormat('H:i', $validatedData['start_time'])->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('H:i', $validatedData['end_time'])->format('H:i:s');

        $price = (float) ($validatedData['price'] ?? $validatedData['fixed_price']); // Usa 'fixed_price' se vier do quick add
        $signalValue = (float) ($validatedData['signal_value'] ?? 0.00);
        $totalPaid = $signalValue;

        $paymentStatus = 'pending';
        $newReservaStatus = Reserva::STATUS_CONFIRMADA;

        if ($signalValue > 0) {
            $paymentStatus = (abs($signalValue - $price) < 0.01 || $signalValue > $price) ? 'paid' : 'partial'; // Ajuste de precisão
            if ($paymentStatus === 'paid') {
                 $newReservaStatus = Reserva::STATUS_CONCLUIDA; // 🟢 Se pago total (com sinal), conclui
            }
        }

        // 2. Consome o slot fixo (se aplicável)
        if ($fixedSlotId) {
            Reserva::where('id', $fixedSlotId)->where('is_fixed', true)->delete();
            Log::info("Slot fixo ID {$fixedSlotId} consumido.");
        }

        // 3. Cria a nova reserva confirmada
        $newReserva = Reserva::create([
            'user_id' => $clientUser->id,
            'date' => $validatedData['date'],
            'day_of_week' => Carbon::parse($validatedData['date'])->dayOfWeek,
            'start_time' => $startTimeNormalized,
            'end_time' => $endTimeNormalized,
            'price' => $price,
            'final_price' => $price,
            'signal_value' => $signalValue,
            'total_paid' => $totalPaid,
            'payment_status' => $paymentStatus,
            'client_name' => $clientUser->name,
            'client_contact' => $clientUser->whatsapp_contact ?? $clientUser->email,
            'notes' => $validatedData['notes'] ?? null,
            'status' => $newReservaStatus,
            'is_fixed' => false,
            'is_recurrent' => $validatedData['is_recurrent'] ?? false,
            'manager_id' => Auth::id(),
        ]);

        // 4. GERA TRANSAÇÃO FINANCEIRA para o sinal
        if ($signalValue > 0) {
            FinancialTransaction::create([
                'reserva_id' => $newReserva->id,
                'user_id' => $newReserva->user_id,
                'manager_id' => Auth::id(),
                'amount' => $signalValue,
                'type' => FinancialTransaction::TYPE_SIGNAL,
                'payment_method' => $validatedData['payment_method'] ?? 'manual',
                'description' => 'Sinal/Pagamento inicial na criação de reserva.',
                'paid_at' => Carbon::now(),
            ]);
        }

        return $newReserva;
    }

    /**
     * Lógica centralizada para Cancelamento ou No-Show (Finalização de Status).
     *
     * @param Reserva $reserva A reserva a ser cancelada/marcada.
     * @param string $newStatus Reserva::STATUS_CANCELADA ou Reserva::STATUS_NO_SHOW.
     * @param string $reason Motivo do cancelamento/falta.
     * @param bool $shouldRefund Se deve ser feito estorno.
     * @param float $amountPaidRef Valor pago (para gerenciamento financeiro).
     * @return array Mensagem de sucesso.
     * @throws \Exception
     */
    public function finalizeStatus(Reserva $reserva, string $newStatus, string $reason, bool $shouldRefund, float $amountPaidRef)
    {
        if ($reserva->status !== Reserva::STATUS_CONFIRMADA) {
            throw new \Exception('A reserva não está confirmada e não pode ser cancelada/marcada como falta.');
        }

        $amountPaid = (float) $amountPaidRef;
        $messageFinance = "";

        // 1. Atualiza a Reserva
        // 🎯 CORREÇÃO CRÍTICA APLICADA: Usar update() condicionalmente para evitar a coluna 'no_show_reason'
        $updateData = [
            'status' => $newStatus,
            'manager_id' => Auth::id(),
        ];

        if ($newStatus === Reserva::STATUS_CANCELADA) {
            $updateData['cancellation_reason'] = $reason;
            // Garantindo que no_show_reason não seja incluído se a coluna não existir.
            if (isset($reserva->no_show_reason)) {
                 $updateData['no_show_reason'] = null; // Limpa se estiver cancelando
            }
        } elseif ($newStatus === Reserva::STATUS_NO_SHOW) {
            $updateData['no_show_reason'] = $reason;
            // Garantindo que cancellation_reason não seja incluído se a coluna não existir (mais limpo).
            if (isset($reserva->cancellation_reason)) {
                 $updateData['cancellation_reason'] = null; // Limpa se for falta
            }
        }

        $reserva->update($updateData);

        // 2. Gerenciamento Financeiro (Exclui sinal e compensa/estorna)
        if ($amountPaid > 0) {
            // 2.1. Exclui o sinal original
            FinancialTransaction::where('reserva_id', $reserva->id)
                ->where('type', FinancialTransaction::TYPE_SIGNAL)
                ->delete();

            // 2.2. Exclui transações antigas de retenção/compensação
             FinancialTransaction::where('reserva_id', $reserva->id)
                ->whereIn('type', [FinancialTransaction::TYPE_RETEN_CANC_COMP, FinancialTransaction::TYPE_RETEN_CANC_P_COMP, FinancialTransaction::TYPE_RETEN_NOSHOW_COMP])
                ->delete();

            if ($shouldRefund) {
                // 2.3. Estorno: A exclusão do sinal já contabiliza a saída.
                $messageFinance = " O valor de R$ " . number_format($amountPaid, 2, ',', '.') . " foi estornado (excluído do caixa).";

            } else {
                // 2.4. Retenção: Cria a transação POSITIVA para COMPENSAR o valor do sinal removido.
                if ($newStatus === Reserva::STATUS_CANCELADA) {
                    $type = $reserva->is_recurrent ? FinancialTransaction::TYPE_RETEN_CANC_P_COMP : FinancialTransaction::TYPE_RETEN_CANC_COMP;
                } else {
                    $type = FinancialTransaction::TYPE_RETEN_NOSHOW_COMP;
                }

                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => Auth::id(),
                    'amount' => $amountPaid,
                    'type' => $type,
                    'payment_method' => 'retained_funds',
                    'description' => "Retenção e Compensação do valor pago (R$ " . number_format($amountPaid, 2, ',', '.') . ") devido a {$newStatus}.",
                    'paid_at' => Carbon::now(),
                ]);
                $messageFinance = " O valor de R$ " . number_format($amountPaid, 2, ',', '.') . " foi RETIDO no caixa (Compensação).";
            }
        }

        // 3. Recria o slot fixo de disponibilidade
        $this->recreateFixedSlot($reserva);

        return ['message_finance' => $messageFinance];
    }

    /**
     * Cancela todas as reservas futuras de uma série.
     * * @param int $masterId O ID da reserva mestra.
     * @param string $reason Motivo do cancelamento.
     * @param bool $shouldRefund Se deve estornar o sinal da série.
     * @param float $amountPaidRef Sinal pago na mestra.
     * @return array
     * @throws \Exception
     */
    public function cancelSeries(int $masterId, string $reason, bool $shouldRefund, float $amountPaidRef)
    {
        $today = Carbon::today()->toDateString();
        $cancellationReason = '[Gestor - Série Recorrente] ' . $reason;
        $managerId = Auth::id();
        $cancelledCount = 0;
        $messageFinance = "";

        $seriesReservas = Reserva::where(function ($query) use ($masterId) {
            $query->where('recurrent_series_id', $masterId)
                ->orWhere('id', $masterId);
        })
            ->where('is_fixed', false)
            ->whereDate('date', '>=', $today)
            ->where('status', Reserva::STATUS_CONFIRMADA)
            ->get();

        $anchorReserva = $seriesReservas->first();

        if (!$anchorReserva) {
             throw new \Exception("Nenhuma reserva ativa encontrada para a série ID: {$masterId}.");
        }

        foreach ($seriesReservas as $slot) {

            // 🛑 CORREÇÃO CRÍTICA AQUI: Combina date (Carbon) e start_time (Carbon) corretamente.
            $slotStartDateTime = $slot->date->copy();

            try {
                // Configura a hora do objeto Carbon da data (Y-m-d) com a hora (H:i:s)
                $slotStartDateTime->setTime(
                    $slot->start_time->hour,
                    $slot->start_time->minute,
                    $slot->start_time->second
                );
            } catch (\Exception $e) {
                // Fallback para garantir que o parse seja feito, caso o cast não funcione
                $timePart = Carbon::parse($slot->start_time);
                $slotStartDateTime->setTime($timePart->hour, $timePart->minute, $timePart->second);
            }

            // 1. Lógica de Tempo Corrigida: Checa se o horário de início da reserva já passou
            if ($slotStartDateTime->isPast() && !$slot->date->isToday()) {
                continue;
            }

            // 2. Atualiza Status
            $slot->status = Reserva::STATUS_CANCELADA;
            $slot->manager_id = $managerId;
            $slot->cancellation_reason = $cancellationReason;
            $slot->save();

            // 3. Gerenciamento Financeiro (Apenas exclui o sinal para cada slot, se houver)
            FinancialTransaction::where('reserva_id', $slot->id)
                ->where('type', FinancialTransaction::TYPE_SIGNAL)
                ->delete();

            // 4. Recria o slot fixo
            $this->recreateFixedSlot($slot);
            $cancelledCount++;
        }

        // 5. Lógica Financeira ÚNICA (Apenas na Mestra/Anchor)
        if ($amountPaidRef > 0) {
            if (!$shouldRefund) {
                // Retenção: Cria a transação POSITIVA de compensação (apenas uma vez)
                FinancialTransaction::create([
                    'reserva_id' => $anchorReserva->id, // Usa a reserva âncora para a transação
                    'user_id' => $anchorReserva->user_id,
                    'manager_id' => $managerId,
                    'amount' => $amountPaidRef,
                    'type' => FinancialTransaction::TYPE_RETEN_CANC_S_COMP,
                    'payment_method' => 'retained_funds',
                    'description' => "Retenção do sinal/valor pago (R$ " . number_format($amountPaidRef, 2, ',', '.') . ") após cancelamento de série.",
                    'paid_at' => Carbon::now(),
                ]);
                $messageFinance = " O sinal de R$ " . number_format($amountPaidRef, 2, ',', '.') . " foi RETIDO no caixa.";
            } else {
                 $messageFinance = " O sinal de R$ " . number_format($amountPaidRef, 2, ',', '.') . " foi estornado (excluído do caixa).";
            }
        }

        return [
            'cancelled_count' => $cancelledCount,
            'message_finance' => $messageFinance,
        ];
    }


    // -------------------------------------------------------------------------
    // 🗓️ MÉTODOS API PARA O DASHBOARD (AGENDAMENTO RÁPIDO)
    // -------------------------------------------------------------------------

    /**
     * API: Cria uma reserva pontual (quick) a partir do Dashboard.
     */
    public function storeQuickReservaApi(Request $request)
    {
        // ... (Validação omitida por brevidade) ...

        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:G:i',
            'end_time' => [
                'required',
                'date_format:G:i',
                function ($attribute, $value, $fail) use ($request) {
                    $startTime = $request->input('start_time');
                    // Permite a transição 23:00 -> 00:00 (ou 0:00) na validação
                    if (($value === '0:00' || $value === '00:00') && $startTime === '23:00') {
                        return;
                    }
                    // Caso normal: aplica a regra after:start_time
                    try {
                        $startTimeCarbon = \Carbon\Carbon::createFromFormat('G:i', $startTime);
                        $endTimeCarbon = \Carbon\Carbon::createFromFormat('G:i', $value);

                        if ($endTimeCarbon->lte($startTimeCarbon)) {
                            $fail('O horário final deve ser posterior ao horário inicial.');
                        }
                    } catch (\Exception $e) {
                        $fail('Formato de horário inválido.');
                    }
                }
            ],
            'fixed_price' => 'nullable|numeric|min:0', // <--- CORREÇÃO APLICADA: De 'required' para 'nullable'
            'reserva_id_to_update' => 'required|exists:reservas,id',

            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|digits:11|max:255',

            'signal_value' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',

            'notes' => 'nullable|string',
        ], [
            'reserva_id_to_update.exists' => 'O slot de horário selecionado não existe ou não está disponível.',
            'client_contact.digits' => 'O WhatsApp deve conter exatamente 11 dígitos (DDD + Número).',
            'client_name.required' => 'O Nome do Cliente é obrigatório.',
            'client_contact.required' => 'O Contato do Cliente (WhatsApp) é obrigatório.',
        ]);


        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $validated = $validator->validated();
        $reservaIdToUpdate = $validated['reserva_id_to_update'];
        $startTimeRaw = $validated['start_time'];
        $endTimeRaw = $validated['end_time'];

        // 🎯 CORREÇÃO CRÍTICA: Trata o caso 23:00 - 00:00 após a validação
        if ($startTimeRaw === '23:00' && ($endTimeRaw === '0:00' || $endTimeRaw === '00:00')) {
            $validated['end_time'] = '23:59';
            Log::info("Horário 23:00-00:00 ajustado para 23:00-23:59 na criação rápida.");
        }
        // FIM CORREÇÃO CRÍTICA

        $oldReserva = Reserva::find($reservaIdToUpdate);

        // 1. Checagens de Segurança
        if (!$oldReserva || !$oldReserva->is_fixed || $oldReserva->status !== Reserva::STATUS_FREE) {
            return response()->json(['success' => false, 'message' => 'O slot selecionado não é um horário fixo disponível.'], 409);
        }

        // 2. Processamento do Cliente
        $clientUser = $this->findOrCreateClient([
            'name' => $validated['client_name'],
            'whatsapp_contact' => $validated['client_contact'],
            'email' => null,
            'data_nascimento' => null,
        ]);

        if (!$clientUser) {
            return response()->json(['success' => false, 'message' => 'Erro interno ao identificar ou criar o cliente.'], 500);
        }

        $validated['date'] = $validated['date'];
        $validated['price'] = $validated['fixed_price'];
        $validated['start_time'] = $startTimeRaw;
        $validated['end_time'] = $validated['end_time'];

        DB::beginTransaction();
        try {
            // 3. DELEGA A LÓGICA DE CRIAÇÃO CENTRALIZADA
            $newReserva = $this->createConfirmedReserva($validated, $clientUser, $reservaIdToUpdate);

            DB::commit();

            $message = "Agendamento pontual para {$newReserva->client_name} confirmado com sucesso!";
            if ($newReserva->signal_value > 0) {
                $message .= " Sinal/Pagamento de R$ " . number_format($newReserva->signal_value, 2, ',', '.') . " registrado.";
            }

            return response()->json(['success' => true, 'message' => $message], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar agendamento rápido (ID slot: {$reservaIdToUpdate}): " . $e->getMessage());

            if (isset($oldReserva)) {
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
        // ... (Validação omitida por brevidade) ...

        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:G:i',
            'end_time' => [
                'required',
                'date_format:G:i',
                function ($attribute, $value, $fail) use ($request) {
                    $startTime = $request->input('start_time');
                    if (($value === '0:00' || $value === '00:00') && $startTime === '23:00') {
                        return;
                    }
                    try {
                        $startTimeCarbon = \Carbon\Carbon::createFromFormat('G:i', $startTime);
                        $endTimeCarbon = \Carbon\Carbon::createFromFormat('G:i', $value);

                        if ($endTimeCarbon->lte($startTimeCarbon)) {
                            $fail('O horário final deve ser posterior ao horário inicial.');
                        }
                    } catch (\Exception $e) {
                        $fail('Formato de horário inválido.');
                    }
                }
            ],
            'fixed_price' => 'required|numeric|min:0',
            'reserva_id_to_update' => 'required|exists:reservas,id', // O ID do slot FIXO inicial

            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|digits:11|max:255',

            'signal_value' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ], [
            'reserva_id_to_update.exists' => 'O slot de horário selecionado não existe ou não está disponível.',
            'client_contact.digits' => 'O WhatsApp deve conter exatamente 11 dígitos (DDD + Número).',
            'client_name.required' => 'O Nome do Cliente é obrigatório.',
            'client_contact.required' => 'O Contato do Cliente (WhatsApp) é obrigatório.',
        ]);


        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $validated = $validator->validated();

        $price = (float) $validated['fixed_price'];
        $signalValue = (float) ($validated['signal_value'] ?? 0.00);

        // 🎯 CORREÇÃO CRÍTICA: Trata o caso 23:00 - 00:00 após a validação
        if ($validated['start_time'] === '23:00' && ($validated['end_time'] === '0:00' || $validated['end_time'] === '00:00')) {
            $validated['end_time'] = '23:59';
            Log::info("Horário 23:00-00:00 ajustado para 23:00-23:59 na criação recorrente.");
        }

        $initialDate = Carbon::parse($validated['date']);
        $dayOfWeek = $initialDate->dayOfWeek;
        $startTimeNormalized = Carbon::createFromFormat('G:i', $validated['start_time'])->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $validated['end_time'])->format('H:i:s');
        $scheduleId = $validated['reserva_id_to_update'];

        $endDate = $initialDate->copy()->addMonths(6);

        // 1. Processamento do Cliente
        $clientUser = $this->findOrCreateClient([
            'name' => $validated['client_name'],
            'whatsapp_contact' => $validated['client_contact'],
            'email' => null,
            'data_nascimento' => null,
        ]);
        if (!$clientUser) {
             return response()->json(['success' => false, 'message' => 'Erro interno ao identificar ou criar o cliente.'], 500);
        }
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

        // 3. Lógica de Checagem Recorrente (Criar array de objetos)
        $reservasToCreate = [];
        $fixedSlotsToDelete = [];
        $conflictCount = 0;

        foreach ($datesToSchedule as $dateString) {
            $currentDate = Carbon::parse($dateString);
            $isFirstDate = $currentDate->toDateString() === $initialDate->toDateString();
            $isConflict = false;

            // 1. Checa conflito contra reservas *reais* de outros clientes
            $overlapWithReal = Reserva::whereDate('date', $dateString)
                ->where('is_fixed', false)
                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
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
                ->where('status', Reserva::STATUS_FREE);

            if ($isFirstDate) {
                $fixedSlotQuery->where('id', $scheduleId);
            }

            $fixedSlot = $fixedSlotQuery->first();

            // 3. Avalia o conflito
            if ($overlapWithReal) {
                $isConflict = true;
            } else if (!$fixedSlot) {
                // Se não há conflito real, mas o slot fixo não existe, é um conflito de agenda (já foi ocupado ou removido)
                $isConflict = true;
            }

            if (!$isConflict) {
                $fixedSlotsToDelete[] = $fixedSlot->id; // Marca para consumo

                // LÓGICA DE PAGAMENTO CONDICIONAL
                $slotSignal = $isFirstDate ? $signalValue : 0.00;
                $slotPaid = $isFirstDate ? $signalValue : 0.00;

                $slotPaymentStatus = 'pending';
                $slotReservaStatus = Reserva::STATUS_CONFIRMADA;

                if ($slotSignal > 0) {
                      $slotPaymentStatus = (abs($slotSignal - $price) < 0.01 || $slotSignal > $price) ? 'paid' : 'partial'; // Ajuste de precisão
                      if ($slotPaymentStatus === 'paid') {
                           $slotReservaStatus = Reserva::STATUS_CONCLUIDA; // 🟢 Se pago total (com sinal), conclui
                      }
                }

                $reservasToCreate[] = [
                    'user_id' => $userId,
                    'manager_id' => Auth::id(),
                    'date' => $dateString,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTimeNormalized,
                    'end_time' => $endTimeNormalized,
                    'price' => $price,
                    'final_price' => $price,
                    'signal_value' => $slotSignal,
                    'total_paid' => $slotPaid,
                    'payment_status' => $slotPaymentStatus,
                    'status' => $slotReservaStatus,
                    'client_name' => $clientName,
                    'client_contact' => $clientContact,
                    'notes' => $validated['notes'] ?? null,
                    'is_fixed' => false,
                    'is_recurrent' => true,
                    // 'recurrent_series_id' será adicionado após a criação da mestra
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            } else {
                $conflictCount++;
                if ($isFirstDate) {
                    $message = "ERRO: O slot inicial da série está ocupado ou indisponível.";
                    return response()->json(['success' => false, 'message' => $message], 409);
                }
            }
        }

        // 4. Checagem final de integridade:
        if (empty($reservasToCreate)) {
            $message = "ERRO: Nenhum slot foi agendado. Cheque o calendário manualmente.";
            return response()->json(['success' => false, 'message' => $message], 409);
        }

        DB::beginTransaction();
        $masterReservaId = null;
        try {
            // 5. Deleta todos os slots fixos válidos
            Reserva::whereIn('id', $fixedSlotsToDelete)->delete();
            Log::info("Slots fixos IDs: " . implode(', ', $fixedSlotsToDelete) . " consumidos/deletados para série recorrente.");

            // 6. Cria a série de reservas reais
            $reservasWithMasterId = [];
            $firstReservaData = array_shift($reservasToCreate);
            $masterReserva = Reserva::create($firstReservaData);
            $masterReservaId = $masterReserva->id;

            // Atualiza a própria mestra e prepara as demais para inserção em massa
            $masterReserva->update(['recurrent_series_id' => $masterReservaId]);

            foreach ($reservasToCreate as $reservaData) {
                $reservaData['recurrent_series_id'] = $masterReservaId;
                $reservasWithMasterId[] = $reservaData;
            }

            if (!empty($reservasWithMasterId)) {
                Reserva::insert($reservasWithMasterId);
            }

            $newReservasCount = count($reservasWithMasterId) + 1; // +1 para a mestra

            // 7. GERA TRANSAÇÃO FINANCEIRA (SINAL)
            if ($signalValue > 0) {
                 FinancialTransaction::create([
                    'reserva_id' => $masterReservaId,
                    'user_id' => $userId,
                    'manager_id' => Auth::id(),
                    'amount' => $signalValue,
                    'type' => FinancialTransaction::TYPE_SIGNAL,
                    'payment_method' => $validated['payment_method'],
                    'description' => 'Sinal recebido na criação da série recorrente (API Dashboard)',
                    'paid_at' => Carbon::now(),
                ]);
            }

            DB::commit();

            $message = "Série recorrente de {$clientName} criada com sucesso! Total de {$newReservasCount} reservas agendadas até " . $endDate->format('d/m/Y') . ".";
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
     * API: Retorna TODAS as reservas e slots fixos para o FullCalendar.
     * Este método unifica a busca de slots (livres/manutenção) e reservas de clientes.
     */
    public function getCalendarEvents(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');

        // Status visíveis: Tudo que ocupa ou mostra disponibilidade no calendário
        $visibleStatuses = [
            Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, Reserva::STATUS_CONCLUIDA,
            Reserva::STATUS_NO_SHOW, Reserva::STATUS_LANCADA_CAIXA,
            Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE, Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA
        ];

        // Busca todos os eventos dentro do período solicitado
        $allEvents = Reserva::whereBetween('date', [$start, $end])
            ->whereIn('status', $visibleStatuses)
            ->get();

        // Mapeamento para o FullCalendar
        return response()->json($this->mapToFullCalendarEvents($allEvents));
    }


    /**
     * Helper para mapear objetos Reserva para o formato JSON do FullCalendar.
     * 🛑 CORRIGIDO: Removida a lógica de prefixo de título aqui para evitar duplicação no JS.
     */
    protected function mapToFullCalendarEvents($reservations)
    {
        $events = [];

        foreach ($reservations as $reserva) {

            // --- GARANTIA DE DATA CORRETA ---
            // 'date' é Carbon, 'start_time' e 'end_time' são Carbon (graças aos casts)
            $dateString = $reserva->date->toDateString();

            // ✅ CORREÇÃO APLICADA: Usar format() no objeto Carbon (start_time/end_time)
            $startTimeFormat = $reserva->start_time->format('H:i:s');
            $endTimeFormat = $reserva->end_time->format('H:i:s');

            $startDateTime = Carbon::parse($dateString . ' ' . $startTimeFormat);
            $endDateTime = Carbon::parse($dateString . ' ' . $endTimeFormat);
            // ---------------------------------

            $basePrice = number_format($reserva->price, 2, ',', '.');
            // 🛑 TÍTULO BASE SEM PREFIXO: O JavaScript fará isso
            $title = $reserva->client_name . ' - R$ ' . $basePrice;
            $color = '#4f46e5'; // Indigo (Padrão: Confirmada, Avulsa)
            $className = 'fc-event-quick';
            $isPaid = false;
            $isFinalized = false;

            // Valor que o cliente pagou e foi retido (sinal ou pagamento total)
            $retainedAmount = (float)$reserva->total_paid;
            // Lógica para determinar se o pagamento está completo
            $isTotalPaid = (abs($retainedAmount - (float)$reserva->final_price) < 0.01 && $reserva->final_price > 0);


            // -------------------------------------------------------------------------
            // 2. Lógica de Status (Aplica a classe CSS e cor no PHP, o JS aplica o prefixo)
            // -------------------------------------------------------------------------

            if ($reserva->is_fixed) {
                 if ($reserva->status === Reserva::STATUS_FREE) {
                    // ** SLOT FIXO LIVRE **
                    $title = 'LIVRE: R$ ' . $basePrice;
                    $color = '#22c55e'; // Verde
                    $className = 'fc-event-available';
                    $isFinalized = false;
                 } elseif ($reserva->status === Reserva::STATUS_MAINTENANCE) {
                    // ** SLOT FIXO MANUTENÇÃO **
                    $title = 'MANUTENÇÃO: Indisponível';
                    $color = '#f59e0b'; // Amarelo/Laranja
                    $className = 'fc-event-maintenance';
                    $isFinalized = true;
                 }

            } elseif ($reserva->status === Reserva::STATUS_NO_SHOW) {
                // ** FALTA **
                $isFinalized = true;
                $isPaid = ($retainedAmount > 0.00);
                $color = '#E53E3E'; // Vermelho
                $className = 'fc-event-no-show';

            } elseif (in_array($reserva->status, [Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])) {
                // ** PAGO/CONCLUÍDA **
                $isFinalized = true;
                $isPaid = true;
                $color = '#10b981';
                $className .= ' fc-event-concluida';

            } elseif (in_array($reserva->status, [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA, Reserva::STATUS_EXPIRADA])) {
                // ** CANCELADA / REJEITADA **
                $isFinalized = true;
                $isPaid = false;
                $color = '#94a3b8'; // Cinza
                $className = 'fc-event-cancelled';

            } elseif ($reserva->status === Reserva::STATUS_PENDENTE) {
                // ** PENDENTE **
                $color = '#ff9800';
                $className = 'fc-event-pending';

            } elseif ($reserva->status === Reserva::STATUS_CONFIRMADA) {

                // Trata o caso de Reservas CONFIRMADAS que estão PAGAS INTEGRALMENTE
                if ($isTotalPaid) {
                     $isPaid = true;
                     $isFinalized = true; // Força a finalização para o calendário
                     $color = '#10b981'; // Cor de Pago
                     $className .= ' fc-event-concluida';
                }
                // Trata o caso de Reservas CONFIRMADAS com Sinal (Parcialmente pago)
                elseif ($retainedAmount > 0) {
                     $isPaid = true; // Tem pagamento (Sinal)
                }
            }

            // Lógica de Cor para Recorrente (se não for FINALIZADA)
            if (!$reserva->is_fixed && $reserva->is_recurrent) {
                 if (!$isFinalized && $reserva->status !== Reserva::STATUS_PENDENTE) {
                    $color = '#C026D3'; // Fuchsia (Roxo)
                    $className = str_replace('fc-event-quick', '', $className);
                    $className .= ' fc-event-recurrent';
                 }
            }


            $events[] = [
                'id' => $reserva->id,
                'title' => $title, // Título base SEM prefixo (ex: "José - R$ 100,00")
                'start' => $startDateTime->toDateTimeString(),
                'end' => $endDateTime->toDateTimeString(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'classNames' => [trim($className)],
                'extendedProps' => [
                    'status' => $reserva->status,
                    'price' => (float)$reserva->price,
                    'final_price' => (float)$reserva->final_price,
                    'signal_value' => (float)$reserva->signal_value,
                    'total_paid' => (float)$reserva->total_paid,
                    'retained_amount' => (float)$retainedAmount,
                    'payment_status' => $reserva->payment_status,
                    'is_recurrent' => (bool)$reserva->is_recurrent,
                    'is_paid' => $isTotalPaid, // Agora reflete se o valor está 100% pago
                    'is_finalized' => $isFinalized,
                    'is_fixed' => (bool)$reserva->is_fixed,
                ],
            ];
        }

        return $events;
    }


    /**
     * Finaliza o pagamento de uma reserva e, opcionalmente, atualiza o preço de reservas futuras da série.
     */
    public function finalizarPagamento(Request $request, $reservaId)
    {
        // 1. Busca a Reserva manualmente
        $reserva = Reserva::find($reservaId);

        if (!$reserva) {
            Log::error("Reserva não encontrada para o ID {$reservaId} durante finalizarPagamento.");
            return response()->json(['success' => false, 'message' => 'Reserva não encontrada.'], 404);
        }

        // 🎯 VALIDAÇÃO DE SEGURANÇA: CAIXA FECHADO
        $financeiroController = app(FinanceiroController::class);
        $reservaDate = Carbon::parse($reserva->date)->toDateString();

        if ($financeiroController->isCashClosed($reservaDate)) {
             return response()->json([
                 'success' => false,
                 'message' => 'Erro: Não é possível finalizar o pagamento. O caixa do dia ' . Carbon::parse($reservaDate)->format('d/m/Y') . ' está fechado. Reabra o caixa para continuar.',
             ], 403);
        }

        // 2. Validação dos dados de entrada
        $request->validate([
            'final_price' => 'required|numeric|min:0',
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
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

            // Define o novo status de pagamento (para o campo payment_status)
            $paymentStatus = 'partial';
            if (abs($newTotalPaid - $finalPrice) < 0.01 || $newTotalPaid > $finalPrice) {
                $paymentStatus = 'paid'; // Totalmente pago ou sobrepago (com troco)
            } elseif ($newTotalPaid == 0) {
                $paymentStatus = 'unpaid';
            }

            // O Status de CONCLUIDA só pode ser setado se o pagamento estiver 'paid'
            $newReservaStatus = $paymentStatus === 'paid'
                ? Reserva::STATUS_CONCLUIDA // 🟢 CONCLUIDA (completed) se pago integralmente
                : Reserva::STATUS_CONFIRMADA; // 🟡 CONFIRMADA (confirmed) se ainda parcial

            // Se o status já for NO_SHOW, mantém o NO_SHOW
            if ($reserva->status === Reserva::STATUS_NO_SHOW) {
                 $newReservaStatus = Reserva::STATUS_NO_SHOW;
            }

            // --- 2. Atualiza a Reserva Atual ---
            $reserva->update([
                'final_price' => $finalPrice, // O preço final acordado, que pode incluir desconto
                'total_paid' => $newTotalPaid,
                'payment_status' => $paymentStatus,
                // 'payment_method' => $request->payment_method, // ✅ REMOVIDO PARA EVITAR ERRO DE COLUNA INEXISTENTE
                'manager_id' => Auth::id(),
                'status' => $newReservaStatus, // ✅ CORRIGIDO: Status Concluída/Confirmada baseado no pagamento
            ]);

            // 2.1. NOVO: GERA TRANSAÇÃO FINANCEIRA (Pagamento do Restante)
            if ($amountPaidNow > 0) {
                FinancialTransaction::create([
                    'reserva_id' => $reserva->id,
                    'user_id' => $reserva->user_id,
                    'manager_id' => Auth::id(),
                    'amount' => $amountPaidNow,
                    'type' => FinancialTransaction::TYPE_PAYMENT,
                    'payment_method' => $request->payment_method, // Aqui é permitido
                    'description' => 'Pagamento final/parcial da reserva',
                    'paid_at' => Carbon::now(),
                ]);
            }


            // --- 3. Lógica para Recorrência: PROPAGAÇÃO DE PREÇO ---
            if ($request->boolean('apply_to_series') && $reserva->is_recurrent) {

                $newPriceForSeries = $finalPrice;
                $masterId = $reserva->recurrent_series_id ?? $reserva->id;
                $reservaDate = Carbon::parse($reserva->date)->toDateString();

                try {
                    $updatedCount = Reserva::where(function ($query) use ($masterId) {
                                                     $query->where('recurrent_series_id', $masterId)
                                                           ->orWhere('id', $masterId);
                                                 })
                                                 ->whereDate('date', '>', $reservaDate)
                                                 ->where('start_time', $reserva->start_time)
                                                 ->where('end_time', $reserva->end_time)
                                                 ->where('is_fixed', false)
                                                 ->where('status', Reserva::STATUS_CONFIRMADA)
                                                 ->where('price', '!=', $newPriceForSeries)
                                                 ->update([
                                                     'price' => $newPriceForSeries,
                                                     'final_price' => $newPriceForSeries,
                                                     'manager_id' => Auth::id(),
                                                 ]);

                    if ($updatedCount > 0) {
                        $message = "Pagamento finalizado e preço da série atualizado com sucesso! ({$updatedCount} reservas alteradas)";
                    } else {
                        $message = "Pagamento finalizado. Preço da série recorrente já estava atualizado ou nenhuma reserva futura elegível encontrada.";
                    }

                } catch (\Exception $e) {
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
            Log::error("Erro no processo de finalizarPagamento (ID: {$reservaId}): " . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro ao finalizar pagamento: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * NOVO MÉTODO: Marca uma reserva como Falta (No-Show) e gerencia o estorno/retenção.
     * (Este método é o endpoint original, mas delega o trabalho centralizado a finalizeStatus)
     */
    public function no_show(Request $request, Reserva $reserva)
    {
        // 1. Validação de Dados
        $validated = $request->validate([
            'no_show_reason' => 'required|string|min:5|max:255',
            'should_refund' => 'required|boolean', // Flag se deve devolver o valor pago
            'paid_amount' => 'required|numeric|min:0', // Valor total que o cliente pagou/sinalizou
        ], [
            'no_show_reason.required' => 'O motivo da falta é obrigatório.',
            'no_show_reason.min' => 'O motivo deve ter pelo menos 5 caracteres.',
            'paid_amount.required' => 'O valor pago é obrigatório para o gerenciamento de estorno.',
        ]);

        // 🎯 VALIDAÇÃO DE SEGURANÇA: CAIXA FECHADO
        $financeiroController = app(FinanceiroController::class);
        $reservaDate = Carbon::parse($reserva->date)->toDateString();

        if ($financeiroController->isCashClosed($reservaDate)) {
             return response()->json(['success' => false, 'message' => 'Erro: Não é possível registrar a falta/estorno. O caixa do dia ' . Carbon::parse($reservaDate)->format('d/m/Y') . ' está fechado.'], 403);
        }
        // FIM DA VALIDAÇÃO DE SEGURANÇA

        if ($reserva->status === Reserva::STATUS_NO_SHOW) {
            return response()->json(['success' => false, 'message' => 'Esta reserva já foi marcada como falta.'], 400);
        }

        DB::beginTransaction();
        try {
            // Delega a lógica centralizada para finalizeStatus
            $result = $this->finalizeStatus(
                $reserva,
                Reserva::STATUS_NO_SHOW,
                '[Gestor] ' . $validated['no_show_reason'],
                $validated['should_refund'],
                (float) $validated['paid_amount']
            );

            DB::commit();

            $message = "Reserva ID {$reserva->id} marcada como FALTA. " . $result['message_finance'];
            return response()->json(['success' => true, 'message' => $message], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            $logMessage = "Erro fatal ao marcar falta (No-Show) ID: {$reserva->id}: " . $e->getMessage();
            Log::error($logMessage, ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao registrar a falta: ' . $e->getMessage()], 500);
        }

    }


    /**
     * Confirma uma reserva pendente, cria a série (se recorrente) e registra o sinal.
     */
    public function confirmar(Request $request, Reserva $reserva)
    {
        // 🎯 VALIDAÇÃO DE SEGURANÇA: CAIXA FECHADO
        $financeiroController = app(FinanceiroController::class);
        $reservaDate = Carbon::parse($reserva->date)->toDateString();

        if ($financeiroController->isCashClosed($reservaDate)) {
             return redirect()->back()->with('error', 'Erro: Não é possível confirmar esta reserva. O caixa do dia ' . Carbon::parse($reservaDate)->format('d/m/Y') . ' está fechado. Reabra o caixa para continuar.');
        }
        // FIM DA VALIDAÇÃO DE SEGURANÇA

        // 1. Validação
        $validated = $request->validate([
            'signal_value' => 'nullable|numeric|min:0',
            'is_recurrent' => ['nullable', 'sometimes'],
            'payment_method' => 'required|string',
        ], [
            'signal_value.numeric' => 'O valor do sinal deve ser um número.',
            'signal_value.min' => 'O valor do sinal não pode ser negativo.',
        ]);

        if ($reserva->status !== Reserva::STATUS_PENDENTE) {
            return redirect()->back()->with('error', 'Esta reserva já foi processada.');
        }

        // LÓGICA FINAL: Checagem robusta contra string ou array
        $isRecurrent = count(array_filter((array)$request->input('is_recurrent'), function($value) {
            return $value === '1' || $value === true;
        })) > 0;

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
            $reserva->status = Reserva::STATUS_CONFIRMADA;
            $reserva->signal_value = $signalValue;
            $reserva->total_paid = $signalValue;
            $reserva->is_recurrent = $isRecurrent;
            $reserva->manager_id = Auth::id();
            $reserva->final_price = $reserva->price;

            // Define o status de pagamento
            $paymentStatus = 'pending';
            if ($signalValue > 0 && $signalValue < $reserva->price) {
                $paymentStatus = 'partial';
            } elseif (abs($signalValue - $reserva->price) < 0.01 || $signalValue > $reserva->price) { // ✅ AJUSTE DE PRECISÃO
                $paymentStatus = 'paid';
                // 🎯 Se totalmente pago, conclui a reserva
                $reserva->status = Reserva::STATUS_CONCLUIDA;
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

            // 4. Consome o slot fixo original (se existir)
            if ($originalFixedSlotId) {
                Reserva::where('id', $originalFixedSlotId)
                    ->where('is_fixed', true)
                    ->where('status', Reserva::STATUS_FREE)
                    ->delete();
            }

            $successMessage = "Reserva de {$reserva->client_name} confirmada com sucesso!";
            $recurrentCount = 0;
            $conflictedOrSkippedCount = 0;

            // 5. LÓGICA CRÍTICA: CRIAÇÃO DA SÉRIE RECORRENTE (6 meses)
            if ($isRecurrent) {
                $masterReserva = $reserva;

                // CORREÇÃO CRÍTICA: Obtém a data da reserva mestra como objeto Carbon
                $masterDate = Carbon::parse($masterReserva->date);

                // 5.1. Definir a janela de renovação: Da próxima semana até 6 meses
                $startDate = $masterDate->copy()->addWeek(); // Começa na próxima semana
                $endDate = $masterDate->copy()->addMonths(6); // 6 meses a partir da data da reserva mestra

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
                        ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                        ->exists();

                    if ($isOccupiedByOtherCustomer) {
                        $isConflict = true;
                    }

                    // NOVO FLUXO: Busca o slot fixo, se existir, para DELETAR (consumir)
                    $fixedSlot = null;
                    if (!$isConflict) {
                        $fixedSlot = Reserva::where('is_fixed', true)
                            ->whereDate('date', $dateString)
                            ->where('start_time', $startTime)
                            ->where('end_time', $endTime)
                            ->where('status', Reserva::STATUS_FREE)
                            ->first();
                    }

                    // Cria a nova reserva se não houver conflito real
                    if (!$isConflict && $fixedSlot) { // Adicionado check for $fixedSlot
                           $newReservasToCreate[] = [
                                'user_id' => $userId,
                                'manager_id' => $managerId,
                                'date' => $dateString,
                                'day_of_week' => $dayOfWeek,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'price' => $price,
                                'final_price' => $price,
                                'signal_value' => 0.00,
                                'total_paid' => 0.00,
                                'payment_status' => 'pending',
                                'client_name' => $clientName,
                                'client_contact' => $clientContact,
                                'notes' => $masterReserva->notes,
                                'status' => Reserva::STATUS_CONFIRMADA,
                                'is_fixed' => false,
                                'is_recurrent' => true,
                                'recurrent_series_id' => $masterId,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ];

                        $fixedSlot->delete(); // Consome o slot verde/FREE
                    } else {
                        $conflictedOrSkippedCount++;
                    }

                    $currentDate->addWeek();
                }

                if (!empty($newReservasToCreate)) {
                    Reserva::insert($newReservasToCreate);
                    $recurrentCount = count($newReservasToCreate);
                }

                $successMessage .= " Série recorrente de " . ($recurrentCount + 1) . " reservas (incluindo a mestra) adicionada até " . $endDate->format('d/m/Y') . ".";
                if ($conflictedOrSkippedCount > 0) {
                    $successMessage .= " Atenção: {$conflictedOrSkippedCount} slots foram pulados devido a conflitos ou ausência de slot fixo.";
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
                    'type' => FinancialTransaction::TYPE_SIGNAL,
                    'payment_method' => $validated['payment_method'] ?? 'pix',
                    'description' => 'Sinal recebido na confirmação da reserva/série',
                    'paid_at' => Carbon::now(),
                ]);
            }

            DB::commit();

            if ($signalValue > 0) {
                $successMessage .= " Sinal de R$ " . number_format($signalValue, 2, ',', '.') . " registrado.";
            }

            return redirect()->back()->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            $logMessage = "Erro fatal ao confirmar reserva ID: {$reserva->id}: " . $e->getMessage();
            Log::error($logMessage, ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro interno ao processar a confirmação: ' . $e->getMessage());
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

        $financeiroController = app(FinanceiroController::class);
        $reservaDate = Carbon::parse($reserva->date)->toDateString();

        if ($financeiroController->isCashClosed($reservaDate)) {
             return response()->json(['success' => false, 'message' => 'Erro: Não é possível rejeitar esta reserva. O caixa do dia ' . Carbon::parse($reservaDate)->format('d/m/Y') . ' está fechado.'], 403);
        }

        if ($reserva->status !== Reserva::STATUS_PENDENTE) {
            return response()->json(['success' => false, 'message' => 'Esta reserva já foi processada.'], 400);
        }

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_REJEITADA;
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
                ->where('status', Reserva::STATUS_PENDENTE)
                ->delete();

            DB::commit();

            return response()->json(['success' => true, 'message' => "Reserva de {$reserva->client_name} rejeitada com sucesso. O horário foi liberado."], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro fatal ao rejeitar reserva ID: {$reserva->id}: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao processar a rejeição: ' . $e->getMessage()], 500);
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

        if ($reserva->status !== Reserva::STATUS_CONFIRMADA) {
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

            // 3. Define a janela de agendamento (Da próxima semana até 6 meses)
            $masterDate = $reserva->date; // Assume que o Laravel fez o cast
            $startDate = $masterDate->copy()->addWeek();
            $endDate = $masterDate->copy()->addMonths(6); // CORRIGIDO

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
                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                    ->exists();

                if ($isOccupiedByOtherCustomer) {
                    $isConflict = true;
                }

                // Busca e deleta o slot fixo, se existir
                $fixedSlot = null;
                if (!$isConflict) {
                    $fixedSlot = Reserva::where('is_fixed', true)
                        ->whereDate('date', $dateString)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTime)
                        ->where('status', Reserva::STATUS_FREE)
                        ->first();
                }

                if (!$isConflict && $fixedSlot) { // Adicionado check for $fixedSlot
                    $newReservasToCreate[] = [
                        'user_id' => $userId,
                        'manager_id' => $managerId,
                        'date' => $dateString,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => $price,
                        'final_price' => $price,
                        'signal_value' => 0.00,
                        'total_paid' => 0.00,
                        'payment_status' => 'pending',
                        'client_name' => $clientName,
                        'client_contact' => $clientContact,
                        'notes' => $reserva->notes,
                        'status' => Reserva::STATUS_CONFIRMADA,
                        'is_fixed' => false,
                        'is_recurrent' => true,
                        'recurrent_series_id' => $masterId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];

                    $fixedSlot->delete();
                } else {
                    $conflictedOrSkippedCount++;
                }

                $currentDate->addWeek();
            }

            if (!empty($newReservasToCreate)) {
                Reserva::insert($newReservasToCreate);
            }

            DB::commit();

            $totalCreated = count($newReservasToCreate) + 1; // +1 para a mestra
            $successMessage = "Conversão concluída! A reserva ID {$masterId} agora é a Mestra, e {$totalCreated} reservas foram agendadas até " . $endDate->format('d/m/Y') . ".";

            if ($conflictedOrSkippedCount > 0) {
                $successMessage .= " Atenção: {$conflictedOrSkippedCount} slots foram pulados devido a conflitos ou ausência de slot fixo.";
            }

            return redirect()->back()->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro fatal ao converter para recorrente (ID: {$masterId}): " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro interno ao converter a reserva para série: ' . $e->getMessage());
        }
    }


    /**
     * Atualiza o status de um slot fixo de inventário (usado na view de Todas as Reservas).
     */
    public function toggleFixedReservaStatus(Request $request, Reserva $reserva)
    {
        // 1. Validação básica para garantir que é um slot fixo
        if (!$reserva->is_fixed) {
            return response()->json(['success' => false, 'message' => 'Esta não é uma reserva de inventário fixo.'], 400);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in([Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE])],
        ]);

        // 2. Checa se o status atual já é o solicitado (evita escrita desnecessária)
        if ($reserva->status === $validated['status']) {
            $message = 'O status já está definido como ' . $validated['status'];
            return response()->json(['success' => false, 'message' => $message], 400);
        }

        // 3. Checagem de integridade (Não pode sair de maintenance/free se houver conflito de cliente)
        if ($validated['status'] === Reserva::STATUS_FREE) {
            // Ao tentar retornar para FREE, verifica se há algum cliente com pending/confirmed
            $overlap = Reserva::where('date', $reserva->date)
                ->where('start_time', $reserva->start_time)
                ->where('end_time', $reserva->end_time)
                ->where('is_fixed', false)
                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
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

        $latestReservations = Reserva::selectRaw('recurrent_series_id, MAX(date) as last_date, MIN(date) as first_date, MIN(start_time) as slot_time, MAX(price) as slot_price, day_of_week, client_name')
            ->where('is_recurrent', true)
            ->where('is_fixed', false)
            ->where('status', Reserva::STATUS_CONFIRMADA)
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
        $endDate = $currentMaxDate->copy()->addMonths(6);

        if ($startDate->greaterThan($endDate)) {
            return response()->json(['success' => false, 'message' => 'A série já está totalmente coberta até ' . $endDate->format('d/m/Y') . '.'], 400);
        }

        // Parâmetros da série
        $dayOfWeek = $masterReserva->day_of_week;
        $startTime = $masterReserva->start_time;
        $endTime = $masterReserva->end_time;
        $price = $masterReserva->price;
        $clientName = $masterReserva->client_name;
        $userId = $masterReserva->user_id;
        $masterId = $masterReserva->id;
        $managerId = Auth::id();

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
                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                    ->exists();

                if ($isDuplicate) {
                    $isConflict = true;
                }

                // 3.2. Checagem de Conflito (Outros Clientes)
                if (!$isConflict) {
                    $isOccupiedByRealCustomer = Reserva::whereDate('date', $dateString)
                        ->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime)
                        ->where('is_fixed', false)
                        ->where('recurrent_series_id', '!=', $masterId)
                        ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                        ->exists();

                    if ($isOccupiedByRealCustomer) {
                        $isConflict = true;
                    }
                }

                // 3.3. Busca o slot fixo, se existir, para DELETAR (consumir)
                $fixedSlot = null;
                if (!$isConflict) {
                    $fixedSlot = Reserva::where('is_fixed', true)
                        ->whereDate('date', $dateString)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTime)
                        ->where('status', Reserva::STATUS_FREE)
                        ->first();
                }

                // 3.4. Cria a nova reserva se não houver conflito REAL nem duplicação
                if (!$isConflict && $fixedSlot) { // Adicionado check for $fixedSlot
                    Reserva::create([
                        'user_id' => $userId,
                        'manager_id' => $managerId,
                        'date' => $dateString,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => $price,
                        'final_price' => $price,
                        'signal_value' => 0.00,
                        'total_paid' => 0.00,
                        'payment_status' => 'pending',
                        'client_name' => $clientName,
                        'client_contact' => $masterReserva->client_contact,
                        'status' => Reserva::STATUS_CONFIRMADA,
                        'is_fixed' => false,
                        'is_recurrent' => true,
                        'recurrent_series_id' => $masterId,
                    ]);
                    $newReservasCount++;

                    $fixedSlot->delete(); // Consome o slot verde/FREE
                } else {
                    $conflictedOrSkippedCount++;
                }

                $currentDate->addWeek();
            }

            DB::commit();

            if ($newReservasCount > 0) {
                $message = "Série #{$masterId} de '{$clientName}' renovada com sucesso! Foram adicionadas {$newReservasCount} novas reservas, estendendo o prazo até " . $endDate->format('d/m/Y') . ".";

                if ($conflictedOrSkippedCount > 0) {
                    $message .= " Atenção: {$conflictedOrSkippedCount} slots foram pulados devido a conflitos, duplicações anteriores ou ausência de slot fixo.";
                }

                return response()->json([
                    'success' => true,
                    'message' => $message,
                ], 200);
            } else {
                $message = "Falha na renovação: Nenhuma nova reserva foi adicionada. Total de slots pulados: {$conflictedOrSkippedCount}.";
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
        if ($reserva->status === Reserva::STATUS_CANCELADA || $reserva->status === Reserva::STATUS_REJEITADA) {
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
                $reserva->status = Reserva::STATUS_CANCELADA;
                $reserva->cancellation_reason = '[Cliente] ' . $reason;
                $reserva->save();

                // Recria o slot fixo de disponibilidade (o evento verde)
                $this->recreateFixedSlot($reserva);

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
        $startTimeRaw = $validated['hora_inicio'];
        $endTimeRaw = $validated['hora_fim'];
        $scheduleId = $validated['schedule_id'];
        $nomeCliente = $validated['nome_cliente'];
        $contatoCliente = $validated['contato_cliente'];
        $emailCliente = $validated['email_cliente'];

        // 🎯 CORREÇÃO CRÍTICA DO HORÁRIO 23:00 - 00:00 NA VIEW PÚBLICA
        if ($startTimeRaw === '23:00' && ($endTimeRaw === '0:00' || $endTimeRaw === '00:00')) {
            $endTimeRaw = '23:59';
        }

        // NOVA LÓGICA DE VALORES E PAGAMENTO (para storePublic)
        $price = (float) $validated['price'];
        $signalValue = (float) ($validated['signal_value'] ?? 0.00);
        $totalPaid = $signalValue;

        $paymentStatus = 'pending';
        $reservaStatus = Reserva::STATUS_PENDENTE;

        if ($signalValue > 0) {
            $paymentStatus = (abs($signalValue - $price) < 0.01 || $signalValue > $price) ? 'paid' : 'partial'; // Ajuste de precisão
             if ($paymentStatus === 'paid') {
                 $reservaStatus = Reserva::STATUS_CONCLUIDA; // ✅ Se pago total, deve ser concluída.
             }
        }

        // FIM NOVA LÓGICA DE VALORES E PAGAMENTO


        // Normaliza as horas para o formato do banco de dados (H:i:s)
        $startTimeNormalized = Carbon::createFromFormat('G:i', $startTimeRaw)->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('G:i', $endTimeRaw)->format('H:i:s');

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
                ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                ->first();

            if ($existingReservation) {
                DB::rollBack();

                $statusMessage = $existingReservation->status === Reserva::STATUS_PENDENTE
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
            $confirmedConflict = Reserva::where('date', $date)
                ->where('is_fixed', false) // Apenas reservas de clientes (não slots fixos)
                ->where('status', Reserva::STATUS_CONFIRMADA)
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

            // 6. Limpa o slot fixo (evento verde)
            $fixedSlot = Reserva::where('id', $scheduleId)
                ->where('is_fixed', true)
                ->where('status', Reserva::STATUS_FREE)
                ->first();

            if (!$fixedSlot) {
                DB::rollBack();
                $validator->errors()->add('schedule_id', 'O slot selecionado não existe mais.');
                throw new ValidationException($validator);
            }


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
                'status' => $reservaStatus, // ✅ CORRIGIDO: Status Concluída se pago total
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

        $futureOrTodayCount = Reserva::where('status', Reserva::STATUS_PENDENTE)
             ->whereDate('date', '>=', $today) // Apenas reservas futuras ou de hoje
             ->count();

        return response()->json(['count' => $futureOrTodayCount], 200);
    }
}
