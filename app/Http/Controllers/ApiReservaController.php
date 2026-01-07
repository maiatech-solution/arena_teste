<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiReservaController extends Controller
{
    // =========================================================================
    // ✅ MÉTODO 1: Reservas de CLIENTE (TODOS OS STATUS DE OCUPAÇÃO)
    // Rota: api.reservas.confirmadas
    // =========================================================================
    public function getConfirmedReservas(Request $request)
    {
        try {
            $start = Carbon::parse($request->input('start', Carbon::today()->subMonths(1)->toDateString()));
            $end = Carbon::parse($request->input('end', Carbon::today()->addMonths(6)->toDateString()));

            // 🎯 CAPTURA O FILTRO DE ARENA
            $arenaId = $request->input('arena_id');

            $statuses = [
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_PENDENTE,
                Reserva::STATUS_CONCLUIDA,
                Reserva::STATUS_LANCADA_CAIXA,
                Reserva::STATUS_NO_SHOW,
            ];

            $query = Reserva::where('is_fixed', false)
                ->whereIn('status', $statuses)
                ->whereDate('date', '>=', $start)
                ->whereDate('date', '<=', $end);

            // 🎯 APLICA O FILTRO SE UMA ARENA FOR SELECIONADA
            if (!empty($arenaId)) {
                $query->where('arena_id', $arenaId);
            }

            $reservas = $query->get();

            // ... (resto do seu código de mapeamento $events permanece igual)
            $events = $reservas->map(function ($reserva) {
                // ... mantenha sua lógica de cores e títulos aqui ...
                // (Apenas certifique-se de fechar o map e retornar o JSON ao final)
                // [Omitido para brevidade, mantenha o seu original]
                return [
                    'id' => $reserva->id,
                    'title' => (isset($titlePrefix) ? $titlePrefix : '') . ($reserva->user ? $reserva->user->name : ($reserva->client_name ?? 'Cliente')) . ' - R$ ' . number_format((float)$reserva->price, 2, ',', '.'),
                    'start' => $reserva->date->format('Y-m-d') . 'T' . ($reserva->start_time instanceof Carbon ? $reserva->start_time->format('H:i:s') : $reserva->start_time),
                    'end' => $reserva->date->format('Y-m-d') . 'T' . ($reserva->end_time instanceof Carbon ? $reserva->end_time->format('H:i:s') : $reserva->end_time),
                    'color' => (isset($color) ? $color : '#4f46e5'),
                    'className' => (isset($className) ? $className : 'fc-event-quick'),
                    'extendedProps' => [
                        'status' => $reserva->status,
                        'price' => (float)$reserva->price,
                        'total_paid' => (float)($reserva->total_paid ?? $reserva->signal_value),
                        'is_recurrent' => (bool)$reserva->is_recurrent,
                        'arena_id' => $reserva->arena_id // Útil para debug
                    ]
                ];
            });

            return response()->json($events);
        } catch (\Exception $e) {
            Log::error("Erro CRÍTICO ao buscar reservas de cliente: " . $e->getMessage());
            return response()->json(['error' => 'Erro interno ao carregar reservas.'], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO 2: Horários Disponíveis p/ Calendário (API) - CORRIGIDO
    // =========================================================================
    public function getAvailableSlotsApi(Request $request)
    {
        try {
            // 1. Tratamento robusto das datas de entrada
            $startInput = $request->input('start');
            $endInput = $request->input('end');

            $startDate = $startInput ? Carbon::parse($startInput) : Carbon::today();
            $endDate = $endInput ? Carbon::parse($endInput) : Carbon::today()->addWeeks(6);

            // 2. Captura e limpeza do Filtro de Arena
            // Usamos filled() para garantir que não seja uma string vazia ou espaço
            $arenaId = $request->input('arena_id');

            $query = Reserva::where('is_fixed', true)
                ->where('status', Reserva::STATUS_FREE)
                ->whereDate('date', '>=', $startDate->toDateString())
                ->whereDate('date', '<=', $endDate->toDateString());

            // 🎯 FILTRO DE ARENA: Só aplica se houver um valor real
            if ($request->filled('arena_id') && $arenaId !== 'null') {
                $query->where('arena_id', $arenaId);
            }

            $allFixedSlots = $query->get();

            $events = [];
            foreach ($allFixedSlots as $slot) {
                // Tratamento de Horários (Garante formato HH:mm:ss)
                $slotStartTime = $slot->start_time instanceof Carbon ? $slot->start_time->format('H:i:s') : $slot->start_time;
                $slotEndTime = $slot->end_time instanceof Carbon ? $slot->end_time->format('H:i:s') : $slot->end_time;

                if (empty($slotStartTime) || empty($slotEndTime)) continue;

                // Tratamento da Data (Garante formato YYYY-MM-DD)
                $slotDateString = $slot->date instanceof Carbon ? $slot->date->toDateString() : Carbon::parse($slot->date)->toDateString();

                // 3. Verificação de Ocupação (Evitar sobreposição com reservas confirmadas)
                $isOccupiedByConfirmed = Reserva::where('is_fixed', false)
                    ->where('arena_id', $slot->arena_id)
                    ->whereDate('date', $slotDateString)
                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
                    ->where(function ($q) use ($slotStartTime, $slotEndTime) {
                        $q->where('start_time', '<', $slotEndTime)
                            ->where('end_time', '>', $slotStartTime);
                    })
                    ->exists();

                if (!$isOccupiedByConfirmed) {
                    $events[] = [
                        'id' => $slot->id,
                        'title' => 'Disponível',
                        'start' => $slotDateString . 'T' . $slotStartTime,
                        'end' => $slotDateString . 'T' . $slotEndTime,
                        'color' => '#10b981',
                        'className' => 'fc-event-available',
                        'extendedProps' => [
                            'status' => Reserva::STATUS_FREE,
                            'price' => (float)$slot->price,
                            'is_fixed' => true,
                            'arena_id' => $slot->arena_id
                        ]
                    ];
                }
            }

            return response()->json($events);
        } catch (\Exception $e) {
            Log::error("Erro CRÍTICO no getAvailableSlotsApi: " . $e->getMessage());
            return response()->json(['error' => 'Erro interno ao carregar horários.'], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO 3: Horários Disponíveis p/ FORMULÁRIO PÚBLICO (HTML) - CORRIGIDO
    // =========================================================================
    public function getAvailableTimes(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $dateString = $request->input('date');
        $selectedDate = Carbon::parse($dateString);
        $isToday = $selectedDate->isToday();
        $now = Carbon::now();

        $allFixedSlots = Reserva::where('is_fixed', true)
            ->whereDate('date', $dateString)
            ->where('status', Reserva::STATUS_FREE)
            ->get();

        // 🎯 CORREÇÃO: Pegar apenas reservas CONFIRMADAS para liberar horários pendentes na lista
        $occupiedReservas = Reserva::where('is_fixed', false)
            ->whereDate('date', $dateString)
            ->where('status', Reserva::STATUS_CONFIRMADA)
            ->get();

        $availableTimes = [];

        foreach ($allFixedSlots as $slot) {
            $slotStart = $slot->start_time instanceof Carbon ? $slot->start_time->format('H:i') : substr($slot->start_time, 0, 5);
            $slotEnd = $slot->end_time instanceof Carbon ? $slot->end_time->format('H:i') : substr($slot->end_time, 0, 5);

            if (empty($slotStart) || empty($slotEnd)) continue;

            $slotStartCarbon = Carbon::parse($slotStart);
            $slotEndCarbon = Carbon::parse($slotEnd);
            $slotEndDateTime = $selectedDate->copy()->setTime($slotEndCarbon->hour, $slotEndCarbon->minute);

            if ($slotEndCarbon->lt($slotStartCarbon)) {
                $slotEndDateTime->addDay();
            }

            if ($isToday && $slotEndDateTime->lt($now)) {
                continue;
            }

            $isOccupied = $occupiedReservas->contains(function ($reservation) use ($slotStart, $slotEnd) {
                $reservationStart = $reservation->start_time instanceof Carbon ? $reservation->start_time->format('H:i:s') : $reservation->start_time;
                $reservationEnd = $reservation->end_time instanceof Carbon ? $reservation->end_time->format('H:i:s') : $reservation->end_time;
                return $reservationStart < $slotEnd . ':00' && $reservationEnd > $slotStart . ':00';
            });

            if (!$isOccupied) {
                $availableTimes[] = [
                    'id' => $slot->id,
                    'time_slot' => $slotStart . ' - ' . $slotEnd,
                    'price' => number_format($slot->price, 2, ',', '.'),
                    'raw_price' => $slot->price,
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'schedule_id' => $slot->id,
                ];
            }
        }

        $finalAvailableTimes = collect($availableTimes)->sortBy('start_time')->values();
        return response()->json($finalAvailableTimes);
    }
}
