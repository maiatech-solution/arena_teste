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

            $statuses = [
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_PENDENTE,
                Reserva::STATUS_CONCLUIDA,
                Reserva::STATUS_LANCADA_CAIXA,
                Reserva::STATUS_NO_SHOW,
            ];

            $reservas = Reserva::where('is_fixed', false)
                               ->whereIn('status', $statuses)
                               ->whereDate('date', '>=', $start)
                               ->whereDate('date', '<=', $end)
                               ->get();

            $events = $reservas->map(function ($reserva) {

                $isRecurrent = (bool)$reserva->is_recurrent;
                $isPaid = in_array($reserva->status, [Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA]);
                $isNoShow = $reserva->status === Reserva::STATUS_NO_SHOW;
                $isPending = $reserva->status === Reserva::STATUS_PENDENTE;

                $timeStartFormatted = $reserva->start_time instanceof Carbon ? $reserva->start_time->format('H:i:s') : $reserva->start_time;
                $timeEndFormatted = $reserva->end_time instanceof Carbon ? $reserva->end_time->format('H:i:s') : $reserva->end_time;

                $startOutput = $reserva->date->format('Y-m-d') . 'T' . $timeStartFormatted;
                $endOutput = $reserva->date->format('Y-m-d') . 'T' . $timeEndFormatted;

                $color = $isRecurrent ? '#c026d3' : '#4f46e5';
                $className = $isRecurrent ? 'fc-event-recurrent' : 'fc-event-quick';
                $titlePrefix = '';

                if ($isPending) {
                    $color = '#ff9800';
                    $className = 'fc-event-pending';
                    $titlePrefix = 'PENDENTE: ';
                } elseif ($isNoShow) {
                    $color = '#E53E3E';
                    $className = 'fc-event-no-show';
                    $titlePrefix = 'FALTA: ';
                } elseif ($isPaid) {
                    $color = '#10b981';
                    $className .= ' fc-event-paid';
                    $titlePrefix = 'PAGO: ';
                }

                if ($isRecurrent) {
                    $titlePrefix = 'RECORR.: ' . str_replace('PAGO: ', '', $titlePrefix);
                }

                $clientName = $reserva->user ? $reserva->user->name : ($reserva->client_name ?? 'Cliente');
                $eventTitle = $titlePrefix . $clientName . ' - R$ ' . number_format((float)$reserva->price, 2, ',', '.');

                return [
                    'id' => $reserva->id,
                    'title' => $eventTitle,
                    'start' => $startOutput,
                    'end' => $endOutput,
                    'color' => $color,
                    'className' => $className,
                    'extendedProps' => [
                        'status' => $reserva->status,
                        'price' => (float)$reserva->price,
                        'total_paid' => (float)($reserva->total_paid ?? $reserva->signal_value),
                        'signal_value' => (float)$reserva->signal_value,
                        'is_recurrent' => $isRecurrent,
                        'is_paid' => $isPaid,
                        'is_fixed' => false
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
            $startDate = Carbon::parse($request->input('start', Carbon::today()->toDateString()));
            $endDate = Carbon::parse($request->input('end', Carbon::today()->addWeeks(6)->toDateString()));

            $allFixedSlots = Reserva::where('is_fixed', true)
                                         ->whereDate('date', '>=', $startDate->toDateString())
                                         ->whereDate('date', '<=', $endDate->toDateString())
                                         ->where('status', Reserva::STATUS_FREE)
                                         ->get();

            $events = [];

            foreach ($allFixedSlots as $slot) {
                $slotStartTime = $slot->start_time instanceof Carbon ? $slot->start_time->format('H:i:s') : $slot->start_time;
                $slotEndTime = $slot->end_time instanceof Carbon ? $slot->end_time->format('H:i:s') : $slot->end_time;

                if (empty($slotStartTime) || empty($slotEndTime)) continue;

                $slotDateString = $slot->date->toDateString();
                $startDateTime = Carbon::parse($slotDateString . ' ' . $slotStartTime);
                $endDateTime = Carbon::parse($slotDateString . ' ' . $slotEndTime);

                if ($endDateTime->lte($startDateTime)) {
                    $endDateTime->addDay();
                }

                $startOutput = $startDateTime->format('Y-m-d\TH:i:s');
                $endOutput = $endDateTime->format('Y-m-d\TH:i:s');

                // 🎯 CORREÇÃO CRÍTICA: Ignorar o status PENDENTE para mostrar o slot verde
                // Isso permite que se a reserva não for paga, o horário ainda apareça disponível para outros.
                $isOccupiedByConfirmed = Reserva::where('is_fixed', false)
                ->whereDate('date', $slotDateString)
                ->where('status', Reserva::STATUS_CONFIRMADA) // Slots Pendentes NÃO bloqueiam o Verde
                ->where(function ($query) use ($slotStartTime, $slotEndTime) {
                    $query->where('start_time', '<', $slotEndTime)
                                 ->where('end_time', '>', $slotStartTime);
                })
                ->exists();

                if (!$isOccupiedByConfirmed) {
                    $events[] = [
                        'id' => $slot->id,
                        'title' => 'Disponível',
                        'start' => $startOutput,
                        'end' => $endOutput,
                        'color' => '#10b981',
                        'className' => 'fc-event-available',
                        'extendedProps' => [
                            'status' => Reserva::STATUS_FREE,
                            'price' => (float)$slot->price,
                            'is_fixed' => true,
                        ]
                    ];
                }
            }

            return response()->json($events);

        } catch (\Exception $e) {
            Log::error("Erro CRÍTICO no getAvailableSlotsApi: " . $e->getMessage());
            return response()->json(['error' => 'Erro interno ao carregar horários disponíveis.'], 500);
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
