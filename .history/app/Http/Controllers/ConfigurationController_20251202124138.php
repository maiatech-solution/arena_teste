<?php

namespace App\Http\Controllers;

use App\Models\ArenaConfiguration;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
            // Apenas status que indicam ocupação real
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

        // 3. Obtém as próximas 50 Reservas (Slots Fixos E Reservas de Clientes) para exibição
        $fixedReservas = Reserva::where('date', '>=', Carbon::today()->toDateString())
            // Ordem por data e horário para um calendário coerente
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
        // 🛑 CRÍTICO: Adicionando 'recurrent_months' à validação, se vier do formulário.
        $validator = Validator::make($request->all(), [
            'day_status.*' => 'nullable|boolean',
            'configs' => 'nullable|array',
            'configs.*' => 'nullable|array',
            'recurrent_months' => 'nullable|integer|min:1|max:12', // 🚨 NOVO CAMPO
        ]);

        $rulesForSlots = [
            'configs.*.*.day_of_week' => 'nullable|integer|min:0|max:6',
            'configs.*.*.is_active' => 'nullable|boolean',
            'configs.*.*.start_time' => 'required_with:configs.*.*.default_price|date_format:H:i',
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

                        // Não ajustamos aqui o dia para evitar conflito na validação.
                        // A validação de sobreposição deve ser feita em um plano de 24h.
                        // Corrigindo a verificação de Horário de Fim anterior ou igual ao Horário de Início

                        $dayName = \App\Models\ArenaConfiguration::DAY_NAMES[$dayOfWeek] ?? 'Dia Desconhecido';

                        // Checagem de duração
                        if ($startA->copy()->addMinute()->gt($endA) && !$crossMidnightA) {
                            $slotNumber = $i + 1;
                            $validator->errors()->add("configs.{$dayOfWeek}", "O Horário de Fim ({$slotA['end_time']}) é anterior ou igual ao Horário de Início ({$slotA['start_time']}) para o Slot {$slotNumber} no {$dayName}.");
                            return;
                        }

                        if ($startB->copy()->addMinute()->gt($endB) && !$crossMidnightB) {
                            $slotNumber = $j + 1;
                            $validator->errors()->add("configs.{$dayOfWeek}", "O Horário de Fim ({$slotB['end_time']}) é anterior ou igual ao Horário de Início ({$slotB['start_time']}) para o Slot {$slotNumber} no {$dayName}.");
                            return;
                        }


                        // Lógica de sobreposição (incluindo cruzamento de meia-noite)
                        // Para checar sobreposição no mesmo dia, precisamos normalizar o tempo para um único dia
                        // se houver cruzamento de meia-noite, ajustamos a hora final para o dia seguinte para o cálculo.

                        // Normalização para o cálculo de sobreposição
                        $endA_calc = $endA->copy();
                        if ($crossMidnightA) $endA_calc->addDay();

                        $endB_calc = $endB->copy();
                        if ($crossMidnightB) $endB_calc->addDay();

                        // Lógica de sobreposição: (A_start < B_end) AND (B_start < A_end)
                        if ($startA->lt($endB_calc) && $startB->lt($endA_calc)) {
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
            Log::error('[ERRO DE VALIDAÇÃO NA CONFIGURAÇÃO DE HORÁRIOS]', ['erros' => $e->errors()->toArray(), 'input' => $request->all()]);

            $errors = $e->errors();
            $customOverlapError = null;

            foreach ($errors->keys() as $key) {
                if (strpos($key, 'configs.') === 0) {
                    if (str_contains($errors->first($key), 'sobrepõem') || str_contains($errors->first($key), 'anterior ou igual')) {
                        $customOverlapError = $errors->first($key);
                        break;
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

        // 🛑 CRÍTICO: Captura o número de meses da recorrência do Request, com padrão de 6.
        $recurrentMonths = (int) $request->input('recurrent_months', 6);

        DB::beginTransaction();
        try {
            foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName) {
                $slotsForDay = $configsByDay[$dayOfWeek] ?? [];

                $activeSlots = collect($slotsForDay)
                    ->filter(function ($slot) {
                        $isActive = isset($slot['is_active']) && (bool)$slot['is_active'];
                        $hasData = !empty($slot['start_time']) && !empty($slot['end_time']) && (isset($slot['default_price']) && is_numeric($slot['default_price']));
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
                $config->default_price = $finalIsActive ? collect($activeSlots)->max('default_price') : 0.00;

                $config->save();
            }

            DB::commit();

            // 🛑 CRÍTICO: Chama o generateFixedReservas passando o número de meses no Request
            // Passamos o Request original, pois ele contém 'recurrent_months'.
            $generateResult = $this->generateFixedReservas($request);

            return $generateResult;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro fatal ao salvar configuração: " . $e->getMessage());
            return redirect()->route('admin.config.index')->with('error', 'Erro ao salvar a configuração: ' . $e->getMessage());
        }
    }

    /**
     * Limpa e Recria TODAS as FixedReservas com base na ArenaConfiguration.
     * 🐛 CORRIGIDO: Agora apaga todos os slots fixos FREE/MANUTENCAO futuros (independente do client_name).
     */
    public function generateFixedReservas(Request $request)
    {
        // 1. Definição da janela de geração
        $today = Carbon::today();
        // 🛑 CRÍTICO: Lê o número de meses do Request (padrão 6) e calcula a data final.
        $recurrentMonths = (int) $request->input('recurrent_months', 6);
        $endDate = $today->copy()->addMonths($recurrentMonths);

        Log::info("Iniciando Geração de Slots Fixos. Janela: {$today->toDateString()} até {$endDate->toDateString()}. Meses: {$recurrentMonths}");

        DB::beginTransaction();
        try {
            // 2. 🛑 CORREÇÃO CRÍTICA: Limpeza Segura
            // Apaga todos os FixedReservas futuros (FREE/MANUTENCAO)
            // Slots de cliente (is_fixed=false) são preservados.
            $deletedCount = Reserva::where('is_fixed', true)
                ->where('date', '>=', $today->toDateString())
                ->whereIn('status', [Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE]) // ✅ Usa STATUS_MAINTENANCE (assumindo que o Modelo foi corrigido)
                ->delete();

            Log::info("Limpeza: {$deletedCount} slots fixos futuros (FREE/MANUTENCAO) deletados antes da recriação.");

            $activeConfigs = ArenaConfiguration::where('is_active', true)->get();
            $reservasToInsert = [];
            $newReservasCount = 0;

            // 3. Loop de geração: vai do dia de hoje até a data final calculada
            for ($date = $today->copy(); $date->lessThan($endDate); $date->addDay()) {
                $dayOfWeek = $date->dayOfWeek;

                $config = $activeConfigs->firstWhere('day_of_week', $dayOfWeek);

                if ($config && $config->is_active && !empty($config->config_data)) {

                    foreach ($config->config_data as $slot) {
                        // 🛑 NOTA: As horas no config_data já estão em H:i:s (salvas no store)
                        $startTime = Carbon::createFromFormat('H:i:s', $slot['start_time']);
                        $endTime = Carbon::createFromFormat('H:i:s', $slot['end_time']);
                        $price = $slot['default_price'];

                        // Lógica para slots que cruzam a meia-noite (ex: 23:00-00:00)
                        // A hora de fim deve ser considerada no dia seguinte para o loop
                        $endTimeOnDay = $endTime->copy();
                        if ($startTime->greaterThanOrEqualTo($endTime)) {
                            $endTimeOnDay->addDay();
                        }

                        $currentSlotTime = $startTime->copy();

                        // O loop subdivide a faixa de horário em slots de 1 hora
                        while ($currentSlotTime->lessThan($endTimeOnDay)) {
                            $nextSlotTime = $currentSlotTime->copy()->addHour();

                            // 🛑 CRÍTICO: Ajusta o fim do slot para não exceder o limite da faixa
                            if ($nextSlotTime->greaterThan($endTimeOnDay)) {
                                break;
                            }

                            $currentDateString = $date->toDateString();

                            // Ajuste da data de fim, se for meia-noite (00:00:00)
                            $currentSlotEndTimeObject = $nextSlotTime;

                            if ($currentSlotEndTimeObject->day > $currentSlotTime->day) {
                                // Se o slot termina no próximo dia, a hora de fim é 00:00:00
                                $currentSlotEndTime = '00:00:00';
                            } else {
                                $currentSlotEndTime = $nextSlotTime->format('H:i:s');
                            }

                            $currentSlotStartTime = $currentSlotTime->format('H:i:s');

                            // 4. Checagem de Conflito: Evita recriar slot FREE onde há Reserva de Cliente REAL.
                            $isOccupiedByCustomer = Reserva::where('date', $currentDateString)
                                ->where('is_fixed', false) // Apenas reservas de cliente
                                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                                ->where(function ($q) use ($currentSlotStartTime, $currentSlotEndTime) {
                                    $q->where('start_time', '<', $currentSlotEndTime)
                                      ->where('end_time', '>', $currentSlotStartTime);
                                })
                                ->exists();

                            if (!$isOccupiedByCustomer) {
                                // Cria o slot fixo FREE
                                $reservasToInsert[] = [
                                    'date' => $currentDateString,
                                    'day_of_week' => $dayOfWeek,
                                    'start_time' => $currentSlotStartTime,
                                    'end_time' => $currentSlotEndTime,
                                    'price' => $price,
                                    'client_name' => 'Slot Fixo de 1h', // Nome genérico para slots recém-criados
                                    'client_contact' => 'N/A',
                                    'notes' => null,
                                    'status' => Reserva::STATUS_FREE,
                                    'is_fixed' => true,
                                    'is_recurrent' => false,
                                    'created_at' => Carbon::now(),
                                    'updated_at' => Carbon::now(),
                                ];
                                $newReservasCount++;
                            } else {
                                Log::debug("Slot ({$currentSlotStartTime}-{$currentSlotEndTime}) em {$currentDateString} pulado por conflito de cliente.");
                            }

                            $currentSlotTime->addHour();
                        }
                    }
                }
            }

            // 5. Inserção em Massa para performance
            if (!empty($reservasToInsert)) {
                Reserva::insert($reservasToInsert);
            }

            DB::commit();

            $message = "Configuração salva e **{$newReservasCount} reservas fixas** geradas com sucesso para os próximos **{$recurrentMonths} meses**. O processo agora é automático após o salvamento.";
            Log::info("Geração de Slots Concluída. Total gerado: {$newReservasCount}.");

            return redirect()->route('admin.config.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro na geração de reservas fixas: " . $e->getMessage());
            return redirect()->route('admin.config.index')->with('error', 'Erro na geração de reservas fixas: ' . $e->getMessage());
        }
    }


    /**
     * Atualiza o preço de um slot fixo individual.
     * 🛑 CORRIGIDO: Recebe o ID e busca a Reserva manualmente para evitar Model Binding.
     */
    public function updateFixedReservaPrice(Request $request, $id)
    {
        // 1. Busca a Reserva (substitui o Model Binding)
        $reserva = Reserva::find($id);

        if (!$reserva) {
            return response()->json(['success' => false, 'error' => 'Reserva não encontrada.'], 404);
        }

        try {
            $request->validate(['price' => 'required|numeric|min:0']);

            if (!$reserva->is_fixed) {
                return response()->json(['success' => false, 'error' => 'Ação permitida apenas em slots fixos (is_fixed=true).'], 403);
            }

            // 🛑 NOVO: Impede a edição de preço em slots de manutenção
            // ✅ Usa STATUS_MAINTENANCE (assumindo que o Modelo foi corrigido)
            if ($reserva->status === Reserva::STATUS_MAINTENANCE) {
                return response()->json(['success' => false, 'error' => 'Não é possível editar o preço de um slot em manutenção. Primeiro, disponibilize-o.'], 403);
            }

            if ($reserva->client_name === 'Slot Fixo de 1h') {
                $reserva->client_name = 'Slot Editado (Gestor: ' . Auth::user()->name . ')';
            }

            $reserva->manager_id = Auth::id();
            $reserva->price = $request->price;
            $reserva->save();

            return response()->json(['success' => true, 'message' => 'Preço atualizado com sucesso.']);
        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error("Erro fatal ao atualizar preço da reserva fixa #{$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Erro interno do servidor.'], 500);
        }
    }

    /**
     * Altera o status de um slot fixo entre 'free' (Disponível) e 'maintenance' (Manutenção),
     * ou cancela reservas de cliente.
     * 🛑 CORRIGIDO: Recebe o ID e busca a Reserva manualmente para evitar Model Binding.
     */
    public function toggleFixedReservaStatus(Request $request, $id)
    {
        // 1. Busca a Reserva (substitui o Model Binding)
        $reserva = Reserva::find($id);

        if (!$reserva) {
            return response()->json(['success' => false, 'error' => 'Reserva não encontrada.'], 404);
        }

        try {
            // ✅ Validação usando strings literais que o JS envia: 'confirmed' e 'cancelled'
            $request->validate(['status' => ['required', 'string', Rule::in(['confirmed', 'cancelled'])]]);

            $newStatus = $request->status; // Será 'confirmed' (Disponibilizar) ou 'cancelled' (Indisponibilizar)

            // --- Lógica de Segurança e Permissão de Ação ---
            $isClientReservationActive = !$reserva->is_fixed && ($reserva->status === Reserva::STATUS_PENDENTE || $reserva->status === Reserva::STATUS_CONFIRMADA);
            $isFixedSlot = $reserva->is_fixed;

            if (!$isFixedSlot && !$isClientReservationActive) {
                return response()->json(['success' => false, 'error' => 'Ação de manutenção permitida apenas em slots fixos ou reservas ativas de clientes.'], 403);
            }

            // 🛑 CRÍTICO: Mapeamento de Status
            if ($isFixedSlot) {
                // =============== LÓGICA PARA SLOTS FIXOS ==================
                // Status REAL do DB: 'free' (Disponível) ou 'maintenance' (Manutenção)
                if ($newStatus === 'confirmed') {
                    $finalStatus = Reserva::STATUS_FREE; // Dispoinibilizando slot fixo
                    $action = 'disponibilizado';
                } else {
                    // Se JS envia 'cancelled', salva como MAINTENANCE no DB.
                    // ✅ Usa STATUS_MAINTENANCE (assumindo que o Modelo foi corrigido)
                    $finalStatus = Reserva::STATUS_MAINTENANCE;
                    $action = 'marcado como indisponível (manutenção)';
                }

                if ($reserva->client_name === 'Slot Fixo de 1h') {
                    $reserva->client_name = 'Slot Editado (Gestor: ' . Auth::user()->name . ')';
                }

            } else {
                // =============== LÓGICA PARA RESERVAS DE CLIENTES ==================
                // Para reserva de cliente ativa, a única ação de 'toggle' com 'cancelled' é CANCELAR.
                if ($newStatus === 'cancelled') {
                    $finalStatus = Reserva::STATUS_CANCELADA; // Cancela a reserva do cliente
                    $reserva->cancellation_reason = 'Cancelamento forçado pelo gestor via tela de Configuração/Manutenção.';
                    // 🐛 Adicionando a recriação do slot fixo após o cancelamento do cliente
                    // O slot fixo será recriado, mas apenas se a lógica de generateFixedReservas não o fizer logo em seguida.
                    // Melhor garantir a recriação.

                    // 🛑 Nota: Esta lógica deve ser movida para o ReservaController
                    // Mas para manter a funcionalidade aqui, usamos a lógica do ReservaController
                    // if (method_exists(app(\App\Http\Controllers\ReservaController::class), 'recreateFixedSlot')) {
                    //    app(\App\Http\Controllers\ReservaController::class)->recreateFixedSlot($reserva);
                    // }

                    $action = 'cancelado para manutenção';
                } else {
                    // Se o JS enviou 'confirmed', mas é uma reserva de cliente,
                    // não faz sentido disponibilizar o slot (ele já está ocupado/disponível).
                    $finalStatus = $reserva->status;
                    return response()->json(['success' => true, 'message' => "Reserva de cliente não foi alterada. Use a ação 'Indisponível' para cancelar o agendamento."], 200);
                }
            }

            $reserva->manager_id = Auth::id();
            $reserva->status = $finalStatus;
            $reserva->save();

            // ✅ Mensagem corrigida para refletir a ação real
            return response()->json(['success' => true, 'message' => "Slot $action com sucesso. O calendário público será atualizado."], 200);

        } catch (ValidationException $e) {
            // Retorna a exceção de validação no formato JSON 422
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            // 🛑 NOVO: Bloco catch de último recurso para garantir o retorno JSON 500
            Log::error("Erro fatal ao alternar status da reserva #{$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Erro interno do servidor.'], 500);
        }
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

            // 4. Exclui FixedReservas futuras correspondentes (slots verdes/manutenção)
            Reserva::where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->whereDate('date', '>=', Carbon::today()->toDateString())
                // 🛑 ATENÇÃO: Incluímos MAINTENANCE na exclusão
                ->whereIn('status', [Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE])
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
                // 🛑 ATENÇÃO: Excluímos slots FREE e MAINTENANCE
                ->whereIn('status', [Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE])
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
