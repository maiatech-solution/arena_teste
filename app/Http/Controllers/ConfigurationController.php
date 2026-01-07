<?php

namespace App\Http\Controllers;

use App\Models\ArenaConfiguration;
use App\Models\Arena;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ConfigurationController extends Controller
{
    /**
     * 1. Portal de Funcionamento: Mostra os cards para seleção da quadra.
     * Rota sugerida: admin.config.funcionamento
     */
    public function funcionamento()
    {
        $arenas = Arena::all();
        // Verifique se a view está em resources/views/admin/quadras/funcionamento.blade.php
        return view('admin.quadras.funcionamento', compact('arenas'));
    }

    /**
     * 2. Formulário de Configuração: Edição dos slots de uma quadra específica.
     */
    public function index(Request $request, $arena_id = null)
    {
        $arenas = Arena::all();
        
        // Tenta pegar o ID da URL ou da Query String
        $targetId = $arena_id ?? $request->query('arena_id');
        
        // Se não houver arena selecionada, volta para a tela de cards
        if (!$targetId) {
            return redirect()->route('admin.config.funcionamento');
        }

        $currentArena = Arena::find($targetId);

        if (!$currentArena) {
            return redirect()->route('admin.arenas.index')->with('warning', 'Arena não encontrada.');
        }

        // Recupera configs APENAS da arena selecionada
        $configs = ArenaConfiguration::where('arena_id', $currentArena->id)->get()->keyBy('day_of_week');

        $dayConfigurations = [];
        foreach (ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName) {
            $config = $configs->get($dayOfWeek);
            $dayConfigurations[$dayOfWeek] = ($config && !empty($config->config_data)) ? $config->config_data : [];
        }

        // Busca os slots fixos gerados para esta arena (exibição na lista inferior)
        $fixedReservas = Reserva::where('arena_id', $currentArena->id)
            ->where('date', '>=', Carbon::today()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(50)
            ->get();

        return view('admin.config.index', [
            'arenas' => $arenas,
            'currentArena' => $currentArena,
            'dayConfigurations' => $dayConfigurations,
            'fixedReservas' => $fixedReservas,
        ]);
    }

    /**
     * 3. Salvar Configuração: Processa o formulário e persiste no banco por Arena.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'arena_id' => 'required|exists:arenas,id',
            'day_status.*' => 'nullable|boolean',
            'configs' => 'nullable|array',
            'recurrent_months' => 'nullable|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $arenaId = $request->input('arena_id');
        $dayStatus = $request->input('day_status', []);
        $configsByDay = $request->input('configs', []);

        DB::beginTransaction();
        try {
            foreach (ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName) {
                $slotsForDay = $configsByDay[$dayOfWeek] ?? [];
                
                $activeSlots = collect($slotsForDay)->filter(function ($slot) {
                    return isset($slot['is_active']) && (bool)$slot['is_active'] && !empty($slot['start_time']);
                })->map(function ($slot) {
                    unset($slot['is_active']);
                    $slot['start_time'] = Carbon::parse($slot['start_time'])->format('H:i:s');
                    $slot['end_time'] = Carbon::parse($slot['end_time'])->format('H:i:s');
                    return $slot;
                })->values()->toArray();

                $isDayActive = isset($dayStatus[$dayOfWeek]);
                $finalIsActive = $isDayActive && !empty($activeSlots);

                // Persiste a configuração vinculada à ARENA
                $config = ArenaConfiguration::firstOrNew([
                    'day_of_week' => $dayOfWeek,
                    'arena_id' => $arenaId 
                ]);

                $config->is_active = $finalIsActive;
                $config->config_data = $finalIsActive ? $activeSlots : [];
                $config->save();
            }

            DB::commit();
            
            // Chama a geração automática dos slots no calendário
            return $this->generateFixedReservas($request);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro no store de config: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao salvar: ' . $e->getMessage());
        }
    }

    /**
     * 4. Gerador de Slots: Limpa e recria os horários no banco (Reserva).
     */
    public function generateFixedReservas(Request $request)
    {
        $arenaId = $request->input('arena_id');
        $today = Carbon::today();
        $recurrentMonths = (int) $request->input('recurrent_months', 6);
        $endDate = $today->copy()->addMonths($recurrentMonths);

        DB::beginTransaction();
        try {
            // 🛑 Limpa apenas os slots LIVRES ou MANUTENÇÃO daquela arena específica
            Reserva::where('is_fixed', true)
                ->where('arena_id', $arenaId)
                ->where('date', '>=', $today->toDateString())
                ->whereIn('status', [Reserva::STATUS_FREE, Reserva::STATUS_MAINTENANCE])
                ->delete();

            $activeConfigs = ArenaConfiguration::where('arena_id', $arenaId)
                ->where('is_active', true)
                ->get();

            $reservasToInsert = [];

            for ($date = $today->copy(); $date->lessThan($endDate); $date->addDay()) {
                $dayOfWeek = $date->dayOfWeek;
                $config = $activeConfigs->firstWhere('day_of_week', $dayOfWeek);

                if ($config && !empty($config->config_data)) {
                    foreach ($config->config_data as $slot) {
                        $startTime = Carbon::parse($slot['start_time']);
                        $endTime = Carbon::parse($slot['end_time']);
                        
                        $current = $startTime->copy();
                        while ($current->lt($endTime)) {
                            $next = $current->copy()->addHour();
                            if ($next->gt($endTime)) break;

                            $reservasToInsert[] = [
                                'arena_id' => $arenaId,
                                'date' => $date->toDateString(),
                                'day_of_week' => $dayOfWeek,
                                'start_time' => $current->format('H:i:s'),
                                'end_time' => $next->format('H:i:s'),
                                'price' => $slot['default_price'],
                                'status' => Reserva::STATUS_FREE,
                                'is_fixed' => true,
                                'client_name' => 'Slot Livre',
                                'client_contact' => 'N/A',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                            $current->addHour();
                        }
                    }
                }
            }

            // Inserção otimizada em lotes (chunks)
            if (!empty($reservasToInsert)) {
                foreach (array_chunk($reservasToInsert, 500) as $chunk) {
                    Reserva::insert($chunk);
                }
            }

            DB::commit();
            return redirect()->route('admin.config.index', ['arena_id' => $arenaId])
                             ->with('success', 'Configuração salva e horários gerados!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro na geração: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erro na geração: ' . $e->getMessage());
        }
    }
}