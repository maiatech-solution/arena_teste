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
            'configs.*.*.start_time' => 'required_with:configs.*.*.default_price|date_format:H:i',
            'configs.*.*.end_time' => 'required_with:configs.*.*.start_time|date_format:H:i|after:configs.*.*.start_time',
            'configs.*.*.default_price' => 'required_with:configs.*.*.start_time|numeric|min:0',
        ];

        $validator->setRules(array_merge($validator->getRules(), $rulesForSlots));

        // 🛑 NOVO: Validação customizada para checar sobreposição de faixas de horário no mesmo dia
        $validator->after(function ($validator) {
            // Se já houver erros de validação básica (ex: horário final antes do inicial), não executa este loop complexo
            if ($validator->errors()->count()) {
                return;
            }

            $configsByDay = $validator->validated()['configs'] ?? [];

            foreach ($configsByDay as $dayOfWeek => $slots) {
                // Filtra apenas os slots que estão ativos e possuem dados válidos
                $activeSlots = collect($slots)->filter(function ($slot) {
                    return isset($slot['is_active']) && (bool)$slot['is_active'] &&
                           !empty($slot['start_time']) && !empty($slot['end_time']);
                })->values();

                $count = $activeSlots->count();
                if ($count < 2) continue;

                // Compara cada slot com todos os outros subsequentes
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $slotA = $activeSlots->get($i);
                        $slotB = $activeSlots->get($j);

                        // Cria objetos Carbon para comparação
                        $startA = Carbon::createFromFormat('H:i', $slotA['start_time']);
                        $endA = Carbon::createFromFormat('H:i', $slotA['end_time']);
                        $startB = Carbon::createFromFormat('H:i', $slotB['start_time']);
                        $endB = Carbon::createFromFormat('H:i', $slotB['end_time']);

                        // Checa a condição de sobreposição: (A_start < B_end) AND (B_start < A_end)
                        // Note que estamos usando lt (less than) para permitir que um slot comece exatamente onde o outro termina.
                        if ($startA->lt($endB) && $startB->lt($endA)) {
                            $dayName = \App\Models\ArenaConfiguration::DAY_NAMES[$dayOfWeek] ?? 'Dia Desconhecido';

                            $errorMsg = "As faixas de horário ({$slotA['start_time']} - {$slotA['end_time']}) e ({$slotB['start_time']} - {$slotB['end_time']}) se **sobrepõem** no {$dayName}. Por favor, corrija.";

                            // Adiciona o erro ao validador, referenciando o array do dia.
                            $validator->errors()->add("configs.{$dayOfWeek}", $errorMsg);
                            // Interrompe o loop do dia após encontrar o primeiro conflito
                            return;
                        }
                    }
                }
            }
        });

        try {
            $validated = $validator->validate();
        } catch (ValidationException $e) {
            Log::error('[ERRO DE VALIDAÇÃO NA CONFIGURAÇÃO DE HORÁRIOS]', ['erros' => $e->errors(), 'input' => $request->all()]);

            $errors = $e->errors();
            $genericError = false;
            $customOverlapError = null;

            foreach ($errors->keys() as $key) {
                if (strpos($key, 'configs.') === 0) {
                    // Captura a mensagem de erro de sobreposição (se existir)
                    if (str_contains($errors->first($key), 'sobrepõem')) {
                        $customOverlapError = $errors->first($key);
                    }
                    $genericError = true;
                }
            }

            // Se houver um erro de sobreposição customizado, exibe-o diretamente
            if ($customOverlapError) {
                return redirect()->back()->withInput()->with('error', 'ERRO DE CONFLITO: ' . $customOverlapError);
            }


            if ($genericError) {
                return redirect()->back()->withInput()->with('error', 'Houve um erro na validação dos dados. Verifique se todos os campos (Início, Fim, Preço) estão preenchidos para os dias ativos, ou se o Horário de Fim é posterior ao de Início.');
            }
            return redirect()->back()->withInput()->withErrors($e->errors())->with('error', 'Erro desconhecido na validação. Verifique os logs.');
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
     * 🛑 CRÍTICO: Agora checa se o horário já está ocupado por um cliente (is_fixed=false).
     */
    public function generateFixedReservas(Request $request)
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addYear();

        // 🛑 CORREÇÃO DE SEGURANÇA: Limpa APENAS os FixedReservas futuras que são slots GENÉRICOS (Slot Fixo de 1h)
        // Slots com preço/status editados pelo gestor SÃO PRESVADOS.
        Reserva::where('is_fixed', true)
            ->where('client_name', 'Slot Fixo de 1h') // ⬅️ CONDIÇÃO CRÍTICA (Somente genéricos)
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

                        $currentSlotTime = $startTime->copy();
                        while ($currentSlotTime->lessThan($endTime)) {
                            $nextSlotTime = $currentSlotTime->copy()->addHour();

                            if ($nextSlotTime->greaterThan($endTime)) {
                                break;
                            }

                            $currentDateString = $date->toDateString();
                            $currentSlotStartTime = $currentSlotTime->format('H:i:s');
                            $nextSlotEndTime = $nextSlotTime->format('H:i:s');

                            // 🛑 Checagem de Conflito CRÍTICA
                            // Verifica se o horário já está ocupado por uma reserva REAL de cliente (is_fixed=false)
                            // OU se já existe um SLOT FIXO NÃO-GENÉRICO (editado pelo gestor)
                            $isOccupied = Reserva::isOccupied($currentDateString, $currentSlotStartTime, $nextSlotEndTime)
                                ->where(function ($query) {
                                    $query->where('is_fixed', false) // Reserva de cliente REAL (Pontual/Recorrente)
                                          ->orWhere(function($q) {
                                               // Slot fixo editado (preço/status) que foi PRESERVADO acima
                                               $q->where('is_fixed', true)
                                                 ->where('client_name', '!=', 'Slot Fixo de 1h');
                                          });
                                })
                                // 🛑 NOVO FILTRO: Adiciona a checagem de slots fixos cancelados (is_fixed=true, status=cancelled)
                                ->orWhere(function ($query) use ($currentDateString, $currentSlotStartTime, $nextSlotEndTime) {
                                    $query->where('is_fixed', true)
                                          ->where('date', $currentDateString)
                                          ->where('status', Reserva::STATUS_CANCELADA)
                                          ->where('start_time', $currentSlotStartTime)
                                          ->where('end_time', $nextSlotEndTime);
                                })
                                ->exists();

                            if ($isOccupied) {
                                // Se estiver ocupado, PULA a criação do slot fixo genérico para este horário.
                                $currentSlotTime->addHour();
                                continue;
                            }

                            // Se não houver conflito, cria o slot fixo
                            Reserva::create([
                                'date' => $currentDateString,
                                'day_of_week' => $dayOfWeek,
                                'start_time' => $currentSlotStartTime,
                                'end_time' => $nextSlotEndTime,
                                'price' => $price,
                                'client_name' => 'Slot Fixo de 1h',
                                'client_contact' => 'N/A',
                                // O status padrão é CONFIRMED (Disponível)
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
    public function updateFixedReservaPrice(Request $request, Reserva $reserva) // ✅ CORRIGIDO: Usando Model Binding
    {
        $request->validate(['price' => 'required|numeric|min:0']);

        // 🛑 CRÍTICO: Valida se a reserva encontrada é de fato um slot fixo
        if (!$reserva->is_fixed) {
             return response()->json(['success' => false, 'error' => 'Ação permitida apenas em slots fixos (is_fixed=true).'], 403);
        }

        // Se o slot era genérico, ele se torna um slot fixo "editado" com o nome do gestor.
        if ($reserva->client_name === 'Slot Fixo de 1h') {
             $reserva->client_name = 'Slot Editado (Gestor: ' . Auth::user()->name . ')';
        }

        $reserva->manager_id = Auth::id(); // Marca o gestor que alterou
        $reserva->price = $request->price;
        $reserva->save();

        return response()->json(['success' => true, 'message' => 'Preço atualizado com sucesso.']);
    }

    /**
     * ✅ NOVO: Altera o status de um slot fixo entre 'confirmed' (Disponível) e 'cancelled' (Indisponível).
     */
    public function toggleFixedReservaStatus(Request $request, Reserva $reserva) // ✅ CORRIGIDO: Usando Model Binding
    {
        // 🛑 CRÍTICO: Valida se o novo status é 'confirmed' ou 'cancelled'
        $request->validate(['status' => ['required', 'string', Rule::in([Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CANCELADA])]]);

        // 🛑 CRÍTICO: Valida se a reserva encontrada é de fato um slot fixo
        if (!$reserva->is_fixed) {
             return response()->json(['success' => false, 'error' => 'Ação permitida apenas em slots fixos (is_fixed=true).'], 403);
        }

        $newStatus = $request->status;

        // Se o slot era genérico, ele se torna um slot fixo "editado" com o nome do gestor.
        if ($reserva->client_name === 'Slot Fixo de 1h') {
             $reserva->client_name = 'Slot Editado (Gestor: ' . Auth::user()->name . ')';
        }

        $reserva->manager_id = Auth::id(); // Marca o gestor que alterou
        $reserva->status = $newStatus;
        $reserva->save();

        $action = $newStatus === Reserva::STATUS_CONFIRMADA ? 'disponibilizado' : 'marcado como indisponível';

        return response()->json(['success' => true, 'message' => "Slot $action com sucesso."]);
    }
}
