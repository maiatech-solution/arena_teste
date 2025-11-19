<?php

namespace App\Http\Controllers;

use App\Models\ArenaConfiguration;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ConfigurationController extends Controller
{
    /**
     * Checa se há reservas reais de clientes (is_fixed=false) conflitantes
     * para uma configuração recorrente (dia da semana e faixa de tempo).
     * @param int $dayOfWeek Dia da semana (0-6).
     * @param string|null $startTime Hora de início para filtro (H:i:s). Se nulo, checa o dia inteiro.
     * @param string|null $endTime Hora de fim para filtro (H:i:s). Se nulo, checa o dia inteiro.
     * @return \Illuminate\Support\Collection Coleção de reservas conflitantes.
     */
    protected function getConflictingCustomerReservations(int $dayOfWeek, string $startTime = null, string $endTime = null)
    {
        $today = Carbon::today()->toDateString();

        // 🛑 CRÍTICO: Busca apenas reservas reais de clientes (is_fixed=false)
        $query = Reserva::where('is_fixed', false)
            ->where('day_of_week', $dayOfWeek)
            // Apenas reservas futuras ou de hoje
            ->whereDate('date', '>=', $today)
            // Apenas reservas ativas que estão ocupando o slot
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE]);

        // Se startTime/endTime for fornecido (para deleção de slot), aplica filtro de tempo.
        if ($startTime && $endTime) {
            $query->where(function ($q) use ($startTime, $endTime) {
                // Lógica de sobreposição: (A_start < B_end) AND (B_start < A_end)
                $q->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });
        }

        return $query->get();
    }


    /**
     * Exibe o formulário de configuração e a lista de reservas fixas.
     */
    public function index()
    {
        // 1. Recupera todas as configurações do banco, agrupadas pelo dia da semana (0-6)
        $configs = ArenaConfiguration::all()->keyBy('day_of_week');

        // 2. Transforma o resultado para o formato esperado pela View
        $dayConfigurations = [];
        foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName) {
            $config = $configs->get($dayOfWeek);
            if ($config && !empty($config->config_data)) {
                $dayConfigurations[$dayOfWeek] = $config->config_data;
            } else {
                $dayConfigurations[$dayOfWeek] = [];
            }
        }

        // 3. Obtém as próximas 50 Reservas Fixas para exibição na tabela (usando is_fixed=true)
        // 🛑 CRÍTICO: Inclui slots CANCELADOS para que o gestor possa reativá-los!
        $fixedReservas = Reserva::where('is_fixed', true)
            ->where('date', '>=', Carbon::today()->toDateString())
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CANCELADA])
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(50)
            ->get();

        return view('admin.config.index', [
            'dayConfigurations' => $dayConfigurations,
            'fixedReservas' => $fixedReservas,
        ]);
    }

    /**
     * Salva a configuração semanal (agora com múltiplos slots/faixas de preço)
     * e dispara a geração automática de reservas fixas.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'day_status.*' => 'nullable|boolean',
            'configs' => 'nullable|array',
            'configs.*' => 'nullable|array',
        ]);

        $rulesForSlots = [
            'configs.*.*.day_of_week' => 'nullable|integer|min:0|max:6',
            'configs.*.*.is_active' => 'nullable|boolean',
            // Usa H:i (08:00) para o input HTML time
            'configs.*.*.start_time' => 'required_with:configs.*.*.default_price|date_format:H:i',
            // 🛑 CORREÇÃO CRÍTICA: REMOVIDO 'after:configs.*.*.start_time'.
            'configs.*.*.end_time' => 'required_with:configs.*.*.start_time|date_format:H:i',
            'configs.*.*.default_price' => 'required_with:configs.*.*.start_time|numeric|min:0',
        ];

        $validator->setRules(array_merge($validator->getRules(), $rulesForSlots));

        // 🛑 Validação customizada para checar sobreposição de faixas de horário no mesmo dia
        $validator->after(function ($validator) {
            if ($validator->errors()->count()) {
                return;
            }

            $configsByDay = $validator->validated()['configs'] ?? [];

            foreach ($configsByDay as $dayOfWeek => $slots) {
                $activeSlots = collect($slots)->filter(function ($slot) {
                    return isset($slot['is_active']) && (bool)$slot['is_active'] &&
                           !empty($slot['start_time']) && !empty($slot['end_time']);
                })->values();

                $count = $activeSlots->count();
                if ($count < 2) continue;

                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $slotA = $activeSlots->get($i);
                        $slotB = $activeSlots->get($j);

                        $startA = Carbon::createFromFormat('H:i', $slotA['start_time']);
                        $endA = Carbon::createFromFormat('H:i', $slotA['end_time']);
                        $startB = Carbon::createFromFormat('H:i', $slotB['start_time']);
                        $endB = Carbon::createFromFormat('H:i', $slotB['end_time']);

                        // Lógica para lidar com horários cruzando a meia-noite (00:00)
                        $crossMidnightA = $startA->greaterThan($endA);
                        $crossMidnightB = $startB->greaterThan($endB);

                        if ($crossMidnightA) {
                            $endA->addDay();
                        }
                        if ($crossMidnightB) {
                            $endB->addDay();
                        }

                        // Slot A
                        if ($endA->isSameDay($startA) && $endA->lte($startA) && !$endA->isMidnight()) {
                            $dayName = \App\Models\ArenaConfiguration::DAY_NAMES[$dayOfWeek] ?? 'Dia Desconhecido';
                            $slotNumber = $i + 1;
                            // 🛑 CORREÇÃO DE SINTAXE PHP 8.3
                            $validator->errors()->add("configs.{$dayOfWeek}", "O Horário de Fim ({$slotA['end_time']}) é anterior ou igual ao Horário de Início ({$slotA['start_time']}) para o Slot {$slotNumber} no {$dayName}.");
                            return;
                        }

                        // Slot B
                        if ($endB->isSameDay($startB) && $endB->lte($startB) && !$endB->isMidnight()) {
                            $dayName = \App\Models\ArenaConfiguration::DAY_NAMES[$dayOfWeek] ?? 'Dia Desconhecido';
                            $slotNumber = $j + 1;
                            // 🛑 CORREÇÃO DE SINTAXE PHP 8.3
                            $validator->errors()->add("configs.{$dayOfWeek}", "O Horário de Fim ({$slotB['end_time']}) é anterior ou igual ao Horário de Início ({$slotB['start_time']}) para o Slot {$slotNumber} no {$dayName}.");
                            return;
                        }

                        // Lógica de sobreposição: (A_start < B_end) AND (B_start < A_end)
                        if ($startA->lt($endB) && $startB->lt($endA)) {
                            $dayName = \App\Models\ArenaConfiguration::DAY_NAMES[$dayOfWeek] ?? 'Dia Desconhecido';

                            $errorMsg = "As faixas de horário ({$slotA['start_time']} - {$slotA['end_time']}) e ({$slotB['start_time']} - {$slotB['end_time']}) se **sobrepõem** no {$dayName}. Por favor, corrija.";

                            $validator->errors()->add("configs.{$dayOfWeek}", $errorMsg);
                            return;
                        }
                    }
                }
            }
        });

        try {
            $validated = $validator->validate();
        } catch (ValidationException $e) {
            // 🛑 Corrigindo o debug para garantir que 'erros' é o MessageBag
            Log::error('[ERRO DE VALIDAÇÃO NA CONFIGURAÇÃO DE HORÁRIOS]', ['erros' => $e->errors()->toArray(), 'input' => $request->all()]);

            // ✅ CORREÇÃO CRÍTICA AQUI: Garante que $errors é o MessageBag
            $errors = $e->errors();
            $customOverlapError = null;

            // 🛑 CORREÇÃO DE SINTAXE (Era a causa do erro 500)
            foreach ($errors->keys() as $key) {
                if (strpos($key, 'configs.') === 0) {
                    if (str_contains($errors->first($key), 'sobrepõem')) {
                        $customOverlapError = $errors->first($key);
                        break; // Encontrou o erro, pode parar
                    }
                }
            }

            if ($customOverlapError) {
                return redirect()->back()->withInput()->with('error', 'ERRO DE CONFLITO: ' . $customOverlapError);
            }

            return redirect()->back()->withInput()->withErrors($e->errors())->with('error', 'Houve um erro na validação dos dados. Verifique se todos os campos (Início, Fim, Preço) estão preenchidos para os dias ativos, ou se o Horário de Fim é posterior ao de Início.');
        }

        $dayStatus = $validated['day_status'] ?? [];
        $configsByDay = $validated['configs'] ?? [];

        DB::beginTransaction();
        try {
            foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName) {
                $slotsForDay = $configsByDay[$dayOfWeek] ?? [];

                $activeSlots = collect($slotsForDay)
                    ->filter(function ($slot) {
                        $isActive = isset($slot['is_active']) && (bool)$slot['is_active'];
                        $hasData = !empty($slot['start_time']) && !empty($slot['end_time']) && !empty($slot['default_price']);
                        return $isActive && $hasData;
                    })
                    ->map(function ($slot) {
                        unset($slot['is_active']);
                        // Garante que o formato de hora seja H:i:s para o DB (pois a validação usou H:i)
                        $slot['start_time'] = Carbon::createFromFormat('H:i', $slot['start_time'])->format('H:i:s');
                        $slot['end_time'] = Carbon::createFromFormat('H:i', $slot['end_time'])->format('H:i:s');
                        return $slot;
                    })
                    ->values()
                    ->toArray();

                $isDayActive = isset($dayStatus[$dayOfWeek]) && (bool)$dayStatus[$dayOfWeek];
                $finalIsActive = $isDayActive && !empty($activeSlots);

                $config = \App\Models\ArenaConfiguration::firstOrNew(['day_of_week' => $dayOfWeek]);

                $config->is_active = $finalIsActive;
                $config->config_data = $finalIsActive ? $activeSlots : [];

                $config->save();
            }

            DB::commit();

            $generateResult = $this->generateFixedReservas(new Request());

            return $generateResult;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro fatal ao salvar configuração: " . $e->getMessage());
            return redirect()->route('admin.config.index')->with('error', 'Erro ao salvar a configuração: ' . $e->getMessage());
        }
    }

    /**
     * Limpa e Recria TODAS as FixedReservas com base na ArenaConfiguration.
     */
    public function generateFixedReservas(Request $request)
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addYear();

        // Limpa APENAS os FixedReservas futuras que são slots GENÉRICOS
        Reserva::where('is_fixed', true)
            ->where('client_name', 'Slot Fixo de 1h')
            ->where('date', '>=', $today->toDateString())
            ->delete();

        $activeConfigs = ArenaConfiguration::where('is_active', true)->get();
        $newReservasCount = 0;

        DB::beginTransaction();
        try {
            for ($date = $today->copy(); $date->lessThan($endDate); $date->addDay()) {
                $dayOfWeek = $date->dayOfWeek;

                $config = $activeConfigs->firstWhere('day_of_week', $dayOfWeek);

                if ($config && $config->is_active && !empty($config->config_data)) {

                    foreach ($config->config_data as $slot) {
                        $startTime = Carbon::parse($slot['start_time']);
                        $endTime = Carbon::parse($slot['end_time']);
                        $price = $slot['default_price'];

                        // Lógica para slots que cruzam a meia-noite (ex: 23:00-00:00)
                        $crossesMidnight = $startTime->greaterThanOrEqualTo($endTime);

                        if ($crossesMidnight) {
                            // Se cruza a meia-noite, ajustamos o final para o dia seguinte para o loop
                            $endTimeOnDay = $endTime->copy()->addDay();
                        } else {
                            $endTimeOnDay = $endTime->copy();
                        }


                        $currentSlotTime = $startTime->copy();

                        // O loop deve ir até o final da faixa de horário (EndTimeOnDay)
                        while ($currentSlotTime->lessThan($endTimeOnDay)) {
                            $nextSlotTime = $currentSlotTime->copy()->addHour();

                            // 🛑 CRÍTICO: Se o próximo slot exceder o limite (e não for meia-noite), para
                            if ($nextSlotTime->greaterThan($endTimeOnDay)) {
                                break;
                            }

                            $currentDateString = $date->toDateString();

                            // O final do slot pode ser no dia seguinte (meia-noite)
                            $currentSlotEndTimeObject = $nextSlotTime;

                            // Se o slot de 1 hora gerado cruzar a meia-noite, precisamos marcar isso.
                            if ($currentSlotEndTimeObject->day > $currentSlotTime->day) {
                                // O final é 00:00:00 do dia seguinte.
                                $currentSlotEndTime = '00:00:00';
                            } else {
                                $currentSlotEndTime = $currentSlotTime->copy()->addHour()->format('H:i:s');
                            }

                            $currentSlotStartTime = $currentSlotTime->format('H:i:s');

                            // Checagem de Conflito CRÍTICA
                            $isOccupied = Reserva::isOccupied($currentDateString, $currentSlotStartTime, $currentSlotEndTime)
                                ->where(function ($query) {
                                    $query->where('is_fixed', false) // Reserva de cliente REAL
                                          ->orWhere(function($q) {
                                               // Slot fixo editado que foi PRESERVADO acima
                                               $q->where('is_fixed', true)
                                                 ->where('client_name', '!=', 'Slot Fixo de 1h');
                                          });
                                })
                                // FILTRO: Adiciona a checagem de slots fixos cancelados (is_fixed=true, status=cancelled)
                                ->orWhere(function ($query) use ($currentDateString, $currentSlotStartTime, $currentSlotEndTime) {
                                    $query->where('is_fixed', true)
                                          ->where('date', $currentDateString)
                                          ->where('status', Reserva::STATUS_CANCELADA)
                                          ->where('start_time', $currentSlotStartTime)
                                          ->where('end_time', $currentSlotEndTime);
                                })
                                ->exists();

                            if ($isOccupied) {
                                $currentSlotTime->addHour();
                                continue;
                            }

                            // Cria o slot fixo
                            Reserva::create([
                                'date' => $currentDateString,
                                'day_of_week' => $dayOfWeek,
                                'start_time' => $currentSlotStartTime,
                                'end_time' => $currentSlotEndTime,
                                'price' => $price,
                                'client_name' => 'Slot Fixo de 1h',
                                'client_contact' => 'N/A',
                                'status' => Reserva::STATUS_CONFIRMADA,
                                'is_fixed' => true,
                            ]);
                            $newReservasCount++;

                            $currentSlotTime->addHour();
                        }
                    }
                }
            }
            DB::commit();

            return redirect()->route('admin.config.index')->with('success', "Configuração salva e **{$newReservasCount} reservas fixas** geradas com sucesso para o próximo ano. O processo agora é automático após o salvamento.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro na geração de reservas fixas: " . $e->getMessage());
            return redirect()->route('admin.config.index')->with('error', 'Erro na geração de reservas fixas: ' . $e->getMessage());
        }
    }


    /**
     * Métodos de gerenciamento (updateFixedReservaPrice e toggleFixedReservaStatus)
     */
    public function updateFixedReservaPrice(Request $request, Reserva $reserva)
    {
        $request->validate(['price' => 'required|numeric|min:0']);

        if (!$reserva->is_fixed) {
             return response()->json(['success' => false, 'error' => 'Ação permitida apenas em slots fixos (is_fixed=true).'], 403);
        }

        if ($reserva->client_name === 'Slot Fixo de 1h') {
             $reserva->client_name = 'Slot Editado (Gestor: ' . Auth::user()->name . ')';
        }

        $reserva->manager_id = Auth::id();
        $reserva->price = $request->price;
        $reserva->save();

        return response()->json(['success' => true, 'message' => 'Preço atualizado com sucesso.']);
    }

    /**
     * Altera o status de um slot fixo entre 'confirmed' (Disponível) e 'cancelled' (Indisponível).
     */
    public function toggleFixedReservaStatus(Request $request, Reserva $reserva)
    {
        $request->validate(['status' => ['required', 'string', Rule::in([Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CANCELADA])]]);

        if (!$reserva->is_fixed) {
             return response()->json(['success' => false, 'error' => 'Ação permitida apenas em slots fixos (is_fixed=true).'], 403);
        }

        $newStatus = $request->status;

        if ($reserva->client_name === 'Slot Fixo de 1h') {
             $reserva->client_name = 'Slot Editado (Gestor: ' . Auth::user()->name . ')';
        }

        $reserva->manager_id = Auth::id();
        $reserva->status = $newStatus;
        $reserva->save();

        $action = $newStatus === Reserva::STATUS_CONFIRMADA ? 'disponibilizado' : 'marcado como indisponível (manutenção)';

        return response()->json(['success' => true, 'message' => "Slot $action com sucesso."]);
    }

    // =========================================================================
    // MÉTODOS DE EXCLUSÃO DE CONFIGURAÇÃO RECORRENTE (COM JUSTIFICATIVA)
    // =========================================================================

    /**
     * Remove uma faixa de preço específica da configuração semanal.
     */
    public function deleteSlotConfig(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'slot_index' => 'required|integer|min:0',
            'confirm_cancel' => 'nullable|boolean',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'justificativa_gestor' => 'required|string|min:5', // ✅ NOVA VALIDAÇÃO
        ]);

        $dayOfWeek = $request->day_of_week;
        $slotIndex = $request->slot_index;
        $startTime = $request->start_time;
        $endTime = $request->end_time;
        $confirmCancel = (bool)$request->confirm_cancel;
        $justificativa = $request->justificativa_gestor; // ✅ CAPTURA DA JUSTIFICATIVA
        $dayName = ArenaConfiguration::DAY_NAMES[$dayOfWeek] ?? 'Dia Desconhecido';

        $config = ArenaConfiguration::where('day_of_week', $dayOfWeek)->first();

        if (!$config || empty($config->config_data)) {
            return response()->json(['success' => true, 'message' => "Configuração de slot já está vazia para {$dayName}."], 200);
        }

        $slots = $config->config_data;

        // 1. Checa a existência do slot na posição correta
        if (!isset($slots[$slotIndex]) || $slots[$slotIndex]['start_time'] !== $startTime || $slots[$slotIndex]['end_time'] !== $endTime) {
             return response()->json(['success' => false, 'message' => "O slot selecionado não foi encontrado na posição esperada ou os horários não correspondem. Recarregue a página."], 404);
        }

        // 2. Checa conflito com reservas de clientes
        $conflictingReservations = $this->getConflictingCustomerReservations($dayOfWeek, $startTime, $endTime);

        // Se há reservas de cliente, retorna 409 (Conflito) para pedir confirmação.
        if ($conflictingReservations->isNotEmpty() && !$confirmCancel) {
            $count = $conflictingReservations->count();
            $message = "Existem **{$count} reserva(s) de cliente** (pontual/recorrente) futura(s) que serão CANCELADAS e DELETADAS se você continuar. Deseja prosseguir?";

            return response()->json([
                'success' => false,
                'requires_confirmation' => true,
                'message' => $message,
                'count' => $count,
            ], 409);
        }

        DB::beginTransaction();
        try {
            // 🛑 NOVO LOG: Registra a ação do gestor
            Log::info("Gestor ID " . Auth::id() . " excluiu slot recorrente: {$dayName} ({$startTime} - {$endTime}). Justificativa: {$justificativa}");

            $cancelledCount = 0;

            if ($conflictingReservations->isNotEmpty()) {
                // 3. Cancela/Deleta Reservas de Clientes Conflitantes
                $conflictingReservations->each(function ($reserva) use ($justificativa) {
                    $reserva->update([
                        'status' => Reserva::STATUS_CANCELADA,
                        'manager_id' => Auth::id(),
                        // ✅ USA A JUSTIFICATIVA DO GESTOR
                        'cancellation_reason' => "Cancelamento de Slot Recorrente ({$reserva->start_time}-{$reserva->end_time}) via Configuração. Motivo: " . $justificativa,
                    ]);
                    $reserva->delete(); // Deleta a reserva real do cliente
                });
                $cancelledCount = $conflictingReservations->count();
            }

            // 4. Exclui FixedReservas futuras correspondentes (slots verdes)
            Reserva::where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->whereDate('date', '>=', Carbon::today()->toDateString())
                ->delete();

            // 5. Remove o slot da configuração e salva
            unset($slots[$slotIndex]);
            $config->config_data = array_values($slots);

            // Se este era o último slot, desativa o dia
            if (empty($config->config_data)) {
                 $config->is_active = false;
            }

            $config->save();

            DB::commit();

            $clientMessage = $cancelledCount > 0 ? " e **{$cancelledCount} reserva(s) de cliente cancelada(s) e deletada(s)**" : "";
            return response()->json(['success' => true, 'message' => "Faixa de horário ({$startTime} - {$endTime}) removida com sucesso{$clientMessage}. O calendário foi atualizado. (Você deve salvar o formulário do dia para ver as mudanças refletidas na seção superior)."], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao deletar slot de configuração: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Erro interno ao processar a exclusão: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Remove a configuração recorrente inteira de um dia da semana.
     */
    public function deleteDayConfig(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'confirm_cancel' => 'nullable|boolean',
            'justificativa_gestor' => 'required|string|min:5', // ✅ NOVA VALIDAÇÃO
        ]);

        $dayOfWeek = $request->day_of_week;
        $confirmCancel = (bool)$request->confirm_cancel;
        $justificativa = $request->justificativa_gestor; // ✅ CAPTURA DA JUSTIFICATIVA
        $dayName = ArenaConfiguration::DAY_NAMES[$dayOfWeek] ?? 'Dia Desconhecido';

        $config = ArenaConfiguration::where('day_of_week', $dayOfWeek)->first();

        if (!$config || !$config->is_active) {
            return response()->json(['success' => true, 'message' => "Configuração de {$dayName} já está inativa."], 200);
        }

        // 1. Checa conflito com reservas de clientes para TODOS os slots do dia
        $allDayConflicts = $this->getConflictingCustomerReservations($dayOfWeek, null, null);

        if ($allDayConflicts->isNotEmpty() && !$confirmCancel) {
            $count = $allDayConflicts->count();
            $message = "Existem **{$count} reserva(s) de cliente** (pontual/recorrente) futura(s) no(a) {$dayName} que serão CANCELADAS e DELETADAS se você continuar. Deseja prosseguir?";

            return response()->json([
                'success' => false,
                'requires_confirmation' => true,
                'message' => $message,
                'count' => $count,
            ], 409);
        }

        DB::beginTransaction();
        try {
            // 🛑 NOVO LOG: Registra a ação do gestor
            Log::info("Gestor ID " . Auth::id() . " excluiu dia recorrente inteiro: {$dayName}. Justificativa: {$justificativa}");

            $cancelledCount = 0;

            if ($allDayConflicts->isNotEmpty()) {
                // 2. Cancela/Deleta Reservas de Clientes Conflitantes
                $allDayConflicts->each(function ($reserva) use ($justificativa) { // ✅ PASSA JUSTIFICATIVA
                    $reserva->update([
                        'status' => Reserva::STATUS_CANCELADA,
                        'manager_id' => Auth::id(),
                        // ✅ USA A JUSTIFICATIVA DO GESTOR
                        'cancellation_reason' => "Cancelamento de Dia Recorrente INTEIRO ({$reserva->day_of_week}) via Configuração. Motivo: " . $justificativa,
                    ]);
                    $reserva->delete();
                });
                $cancelledCount = $allDayConflicts->count();
            }

            // 3. Exclui FixedReservas futuras de todos os slots do dia
            Reserva::where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->whereDate('date', '>=', Carbon::today()->toDateString())
                ->delete();

            // 4. Desativa a configuração do dia
            $config->is_active = false;
            $config->config_data = [];
            $config->save();

            DB::commit();

            $clientMessage = $cancelledCount > 0 ? " e **{$cancelledCount} reserva(s) de cliente cancelada(s) e deletada(s)**" : "";
            return response()->json(['success' => true, 'message' => "Configuração de {$dayName} removida com sucesso{$clientMessage}. O calendário foi atualizado. (Você deve salvar o formulário do dia para ver as mudanças refletidas na seção superior)."], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao deletar a configuração do dia: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Erro interno ao processar a exclusão do dia: ' . $e->getMessage()], 500);
        }
    }
}
