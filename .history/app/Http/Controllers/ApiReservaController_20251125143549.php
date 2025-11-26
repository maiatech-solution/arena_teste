<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiReservaController extends Controller
{
    // =========================================================================
    // ✅ MÉTODO 1: Reservas REAIS (Confirmadas/Pendentes) - FILTRA is_fixed=false
    // =========================================================================
    /**
     * Retorna apenas as reservas feitas por clientes (Pontuais ou Recorrentes).
     * FILTRA: is_fixed = false (Remove os slots técnicos da grade, que causavam duplicidade)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfirmedReservas(Request $request)
    {
        try {
            $start = Carbon::parse($request->input('start', Carbon::today()->toDateString()));
            $end = Carbon::parse($request->input('end', Carbon::today()->addWeeks(6)->toDateString()));

            // Filtra por reservas de cliente (não fixas) com status de ocupação real
            // 🛑 MUDANÇA CRÍTICA: Mostra APENAS reservas CONFIRMADAS no calendário
        $reservas = Reserva::where('is_fixed', false)
                           ->where('status', Reserva::STATUS_CONFIRMADA) // APENAS CONFIRMADAS
                           ->whereDate('date', '>=', $start)
                           ->whereDate('date', '<=', $end)
                           ->get();

            $events = $reservas->map(function ($reserva) {
            // Configuração visual do evento
            $color = '#4f46e5';
            $className = 'fc-event-quick';

            if ((bool)$reserva->is_recurrent) {
                $color = '#c026d3';
                $className = 'fc-event-recurrent';
            }

            $clientName = $reserva->user ? $reserva->user->name : ($reserva->client_name ?? 'Cliente');

            $titlePrefix = '';
            if ((bool)$reserva->is_recurrent) {
                $titlePrefix = 'RECORR.: ';
            }

            $eventTitle = $titlePrefix . $clientName;

            $startOutput = $reserva->date->format('Y-m-d') . 'T' . $reserva->start_time;
            $endOutput = $reserva->date->format('Y-m-d') . 'T' . $reserva->end_time;

            return [
                'id' => $reserva->id,
                'title' => $eventTitle,
                'start' => $startOutput,
                'end' => $endOutput,
                'color' => $color,
                'className' => $className,
                'extendedProps' => [
                    'status' => $reserva->status,
                    'price' => $reserva->price,
                    'is_recurrent' => (bool)$reserva->is_recurrent,
                    'is_fixed' => false
                ]
            ];
        });

            return response()->json($events);

        } catch (\Exception $e) {
            Log::error("Erro ao buscar reservas confirmadas: " . $e->getMessage());
            return response()->json(['error' => 'Erro interno ao carregar reservas. Detalhes: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO 2: Horários Disponíveis p/ Calendário (API)
    // =========================================================================
    /**
     * Retorna os slots da GRADE (is_fixed=true) que estão livres.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableSlotsApi(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->input('start', Carbon::today()->toDateString()));
            $endDate = Carbon::parse($request->input('end', Carbon::today()->addWeeks(6)->toDateString()));

            $allFixedSlots = Reserva::where('is_fixed', true)
                                           ->whereDate('date', '>=', $startDate->toDateString())
                                           ->whereDate('date', '<=', $endDate->toDateString())
                                           // 🛑 CRÍTICO: Deve buscar STATUS_FREE para slots disponíveis
                                           ->where('status', Reserva::STATUS_FREE) // CORRIGIDO
                                           ->get();

            $events = [];

            foreach ($allFixedSlots as $slot) {
                $slotStartTime = $slot->start_time;
                $slotEndTime = $slot->end_time;

                if (empty($slotStartTime) || empty($slotEndTime)) continue;

                $slotDateString = $slot->date->toDateString();
                $startDateTime = Carbon::parse($slotDateString . ' ' . $slotStartTime);
                $endDateTime = Carbon::parse($slotDateString . ' ' . $slotEndTime);

                if ($endDateTime->lte($startDateTime)) {
                    $endDateTime->addDay();
                }

                $startOutput = $startDateTime->format('Y-m-d\TH:i:s');
                $endOutput = $endDateTime->format('Y-m-d\TH:i:s');

                // 🛑 MUDANÇA CRÍTICA: Filtro de sobreposição IGNORA reservas pendentes
                // Filtro de sobreposição remanescente (redundante, mas seguro)
                $isOccupied = Reserva::where('is_fixed', false)
                ->whereDate('date', $slotDateString)
                // ✅ AGORA: Apenas CONFIRMADA causa ocupação real
                ->where('status', Reserva::STATUS_CONFIRMADA) // APENAS CONFIRMADAS BLOQUEIAM
                ->where(function ($query) use ($slotStartTime, $slotEndTime) {
                    $query->where('start_time', '<', $slotEndTime)
                          ->where('end_time', '>', $slotStartTime);
                })
                ->exists();

                if (!$isOccupied) {

                    $eventTitle = 'Disponível';

                    $events[] = [
                        'id' => $slot->id,
                        'title' => $eventTitle,
                        'start' => $startOutput,
                        'end' => $endOutput,
                        'color' => '#10b981', // Verde (Available)
                        'className' => 'fc-event-available',
                        'extendedProps' => [
                            'status' => Reserva::STATUS_FREE, // ✅ NOVO STATUS no extendedProps
                            'price' => $slot->price,
                            'is_fixed' => true,
                        ]
                    ];
                }
            }

            return response()->json($events);

        } catch (\Exception $e) {
            Log::error("Erro no getAvailableSlotsApi: " . $e->getMessage());
            return response()->json(['error' => 'Erro interno ao carregar horários disponíveis. Detalhes: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO 3: Horários Disponíveis p/ FORMULÁRIO PÚBLICO (HTML) - ROBUSTO
    // =========================================================================
    /**
     * Calcula e retorna os horários disponíveis para uma data específica (página pública e /admin/reservas/create).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableTimes(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $dateString = $request->input('date');
        $selectedDate = Carbon::parse($dateString);
        $isToday = $selectedDate->isToday();
        $now = Carbon::now();

        $allFixedSlots = Reserva::where('is_fixed', true)
                                     ->whereDate('date', $dateString)
                                     // 🛑 CRÍTICO: Deve buscar STATUS_FREE para slots disponíveis
                                     ->where('status', Reserva::STATUS_FREE) // CORRIGIDO
                                     ->get();

        $occupiedReservas = Reserva::where('is_fixed', false)
                                           ->whereDate('date', $dateString)
                                           // Apenas CONFIRMADA e PENDENTE causam ocupação real
                                           ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                           ->get();

        $availableTimes = [];

        foreach ($allFixedSlots as $slot) {
            if (empty($slot->start_time) || empty($slot->end_time)) continue;

            $slotStart = Carbon::parse($slot->start_time);
            $slotEnd = Carbon::parse($slot->end_time);

            $slotEndDateTime = $selectedDate->copy()->setTime($slotEnd->hour, $slotEnd->minute);

            if ($slotEnd->lt($slotStart)) {
                $slotEndDateTime->addDay();
            }

            if ($isToday && $slotEndDateTime->lt($now)) {
                continue;
            }

            $isOccupied = $occupiedReservas->contains(function ($reservation) use ($slotStart, $slotEnd) {
                return $reservation->start_time < $slotEnd->format('H:i:s') && $reservation->end_time > $slotStart->format('H:i:s');
            });

            if (!$isOccupied) {
                // Slot disponível
                $availableTimes[] = [
                    'id' => $slot->id,
                    'time_slot' => $slotStart->format('H:i') . ' - ' . $slotEnd->format('H:i'),
                    'price' => number_format($slot->price, 2, ',', '.'),
                    'raw_price' => $slot->price,
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'schedule_id' => $slot->id,
                ];
            }
        }

        $finalAvailableTimes = collect($availableTimes)->sortBy('start_time')->values();

        return response()->json($finalAvailableTimes);
    }
}
