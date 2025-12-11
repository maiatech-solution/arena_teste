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
    /**
     * Retorna TODAS as reservas feitas por clientes:
     * (Confirmadas, Pendentes, Concluídas/Pagas, No-Show).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfirmedReservas(Request $request)
    {
        try {
            // Ajusta o intervalo de busca para incluir eventos passados (para ver pagos e faltas)
            $start = Carbon::parse($request->input('start', Carbon::today()->subMonths(1)->toDateString()));
            $end = Carbon::parse($request->input('end', Carbon::today()->addMonths(6)->toDateString()));

            // 🎯 CRÍTICO: Incluir TODOS os status que ocupam um horário
            $statuses = [
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_PENDENTE,
                Reserva::STATUS_CONCLUIDA,
                Reserva::STATUS_LANCADA_CAIXA,
                Reserva::STATUS_NO_SHOW,
            ];

            Log::info("getConfirmedReservas: Buscando reservas de clientes. Status: " . implode(', ', $statuses));

            $reservas = Reserva::where('is_fixed', false) // Apenas reservas de cliente
                               ->whereIn('status', $statuses)
                               ->whereDate('date', '>=', $start)
                               ->whereDate('date', '<=', $end)
                               ->get();

            Log::info("getConfirmedReservas: Total de reservas encontradas: " . $reservas->count());
            
            $events = $reservas->map(function ($reserva) {
                
                $isRecurrent = (bool)$reserva->is_recurrent;
                $isPaid = in_array($reserva->status, [Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA]);
                $isNoShow = $reserva->status === Reserva::STATUS_NO_SHOW;
                $isPending = $reserva->status === Reserva::STATUS_PENDENTE;

                // 1. Definição inicial (Padrão: Avulso/Recorrente)
                $color = $isRecurrent ? '#c026d3' : '#4f46e5'; // Fúcsia ou Índigo
                $className = $isRecurrent ? 'fc-event-recurrent' : 'fc-event-quick';
                $titlePrefix = '';
                
                // 2. Sobrescrita por Status
                if ($isPending) {
                    $color = '#ff9800'; // Laranja
                    $className = 'fc-event-pending';
                    $titlePrefix = 'PENDENTE: ';
                } elseif ($isNoShow) {
                    // FALTA (A classe do frontend aplica o vermelho, mas forçamos a cor aqui também)
                    $color = '#E53E3E'; // Vermelho
                    $className = 'fc-event-no-show'; 
                    $titlePrefix = 'FALTA: ';
                } elseif ($isPaid) {
                    // PAGA (Mantém a cor original, mas o frontend aplicará a classe .fc-event-paid para o fade)
                    $color = $isRecurrent ? '#c026d3' : '#4f46e5'; 
                }
                
                // Prefixos de título (Adicionados depois de resolver o status principal)
                if ($isRecurrent) {
                    $titlePrefix .= 'RECORR.: ';
                }

                $clientName = $reserva->user ? $reserva->user->name : ($reserva->client_name ?? 'Cliente');

                // Monta o título completo. O frontend removerá o prefixo (PAGO) e adicionará o dele.
                $eventTitle = $titlePrefix . $clientName . ' - R$ ' . number_format((float)$reserva->price, 2, ',', '.');

                $startOutput = $reserva->date->format('Y-m-d') . 'T' . $reserva->start_time;
                $endOutput = $reserva->date->format('Y-m-d') . 'T' . $reserva->end_time;

                // 3. Monta o objeto de evento
                return [
                    'id' => $reserva->id,
                    'title' => $eventTitle,
                    'start' => $startOutput,
                    'end' => $endOutput,
                    'color' => $color,
                    'className' => $className,
                    'extendedProps' => [
                        'status' => $reserva->status, // Status é crucial para o JS saber o que fazer
                        'price' => (float)$reserva->price, 
                        
                        // total_paid é o valor acumulado pago (sinal + saldo)
                        'total_paid' => (float)($reserva->total_paid ?? $reserva->signal_value),
                        'signal_value' => (float)$reserva->signal_value,
                        
                        'is_recurrent' => $isRecurrent,
                        
                        // is_paid é true se for concluída/lançada.
                        'is_paid' => $isPaid, 
                        'is_fixed' => false
                    ]
                ];
            });

            return response()->json($events);

        } catch (\Exception $e) {
            Log::error("Erro CRÍTICO ao buscar reservas de cliente: " . $e->getMessage());
            return response()->json(['error' => 'Erro interno ao carregar reservas. Detalhes: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ⚠️ MÉTODO getConcludedReservas FOI REMOVIDO/CONSOLIDADO
    // =========================================================================

    // =========================================================================
    // ✅ MÉTODO 2: Horários Disponíveis p/ Calendário (API)
    // Rota: api.horarios.disponiveis
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
                                     ->where('status', Reserva::STATUS_FREE)
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

                // Filtro de sobreposição: verifica se o slot está ocupado por RESERVA DE CLIENTE (confirmada/pendente)
                $isOccupied = Reserva::where('is_fixed', false)
                ->whereDate('date', $slotDateString)
                // ✅ Apenas CONFIRMADA e PENDENTE causam ocupação real para slots disponíveis
                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
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
                            'price' => (float)$slot->price, // Garantindo que seja float
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
    // ✅ MÉTODO 3: Horários Disponíveis p/ FORMULÁRIO PÚBLICO (HTML) - MANTIDO
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
                                    ->where('status', Reserva::STATUS_FREE) // Deve buscar STATUS_FREE para slots disponíveis
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