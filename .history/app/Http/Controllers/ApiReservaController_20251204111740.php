<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiReservaController extends Controller
{
    // Removendo a constante local STATUS_CONCLUIDA daqui, pois o código deve usar
    // a constante definida no Modelo Reserva, seguindo o padrão dos outros métodos.

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
            // CRÍTICO: Não buscar CONCLUIDA aqui, pois ela será buscada separadamente abaixo.
            $reservas = Reserva::where('is_fixed', false)
                            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
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

                // Se for PENDENTE, aplica a classe Laranja
                if ($reserva->status === Reserva::STATUS_PENDENTE) {
                    $color = '#ff9800';
                    $className = 'fc-event-pending';
                }

                $clientName = $reserva->user ? $reserva->user->name : ($reserva->client_name ?? 'Cliente');

                $titlePrefix = '';
                if ((bool)$reserva->is_recurrent) {
                    $titlePrefix = 'RECORR.: ';
                }

                $eventTitle = $titlePrefix . $clientName . ' - R$ ' . number_format((float)$reserva->price, 2, '.', ',');

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
                        'price' => (float)$reserva->price, // Garantindo que seja float

                        'signal_value' => (float)$reserva->signal_value,
                        'is_recurrent' => (bool)$reserva->is_recurrent,
                        // ✅ CRÍTICO: Definido como false para evitar sumir do calendário
                        'is_paid' => false,
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
    // ✅ NOVO MÉTODO 4: Reservas CONCLUÍDAS/PAGAS (As que estavam sumindo)
    // =========================================================================
    /**
     * Busca as reservas CONCLUÍDAS/PAGAS para exibir no FullCalendar.
     * * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConcludedReservas(Request $request)
    {
        // Obtém as datas de início e fim da requisição do FullCalendar
        try {
            // ✅ CORRIGIDO: Usando o mesmo método de parse e filtro do getConfirmedReservas
            $start = Carbon::parse($request->input('start', Carbon::today()->toDateString()));
            $end = Carbon::parse($request->input('end', Carbon::today()->addWeeks(6)->toDateString()));

            \Log::info("FullCalendar - Buscando CONCLUÍDAS. Início: {$start}, Fim: {$end}");

            // Busca APENAS as reservas com status 'concluida'
            $concludedReservas = Reserva::query()
                // 🎯 CORREÇÃO CRÍTICA: Busca por AMBOS STATUS de pagamento/conclusão
                ->whereIn('status', [Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
                // ✅ CORRIGIDO: Usando a coluna 'date' para filtrar o range
                ->whereDate('date', '>=', $start)
                ->whereDate('date', '<=', $end)
                ->where('is_fixed', false) // Apenas reservas de cliente
                ->get();

            // Mapeia para o formato FullCalendar.
            $events = $concludedReservas->map(function ($reserva) {
                // Monta o título: "PAGO: Nome do Cliente - R$ X.XX"
                $clientName = $reserva->user ? $reserva->user->name : ($reserva->client_name ?? 'Cliente Desconhecido');

                // 🎯 CORREÇÃO AQUI: Monta o título apenas com o prefixo PAGO e o nome,
                // ignorando o prefixo RECORRENTE, para padronizar a exibição.
                $eventTitle = 'PAGO: ' . $clientName . ' - R$ ' . number_format((float)$reserva->price, 2, '.', ',');

                $startOutput = $reserva->date->format('Y-m-d') . 'T' . $reserva->start_time;
                $endOutput = $reserva->date->format('Y-m-d') . 'T' . $reserva->end_time;

                return [
                    'id' => $reserva->id,
                    'title' => $eventTitle, // Usando o título padronizado
                    'start' => $startOutput,
                    'end' => $endOutput,
                    // A classe de opacidade 'fc-event-paid' será aplicada pelo front-end
                    'className' => 'fc-event-paid ' . ((bool)$reserva->is_recurrent ? 'fc-event-recurrent' : 'fc-event-quick'),
                    'extendedProps' => [
                        'status' => $reserva->status,
                        // ✅ CRÍTICO: Define como pago explicitamente para que o front-end aplique o estilo
                        'is_paid' => true,
                        'signal_value' => (float)$reserva->signal_value,
                        'price' => (float)$reserva->price,
                        'is_recurrent' => (bool)$reserva->is_recurrent,
                        'is_fixed' => false
                    ],
                ];
            });

            \Log::info("Reservas concluídas encontradas: " . $events->count());
            return response()->json($events);

        } catch (\Exception $e) {
            \Log::error("Erro CRÍTICO ao buscar reservas concluídas: " . $e->getMessage());
            return response()->json([
                'error' => 'Falha na API: ' . $e->getMessage(),
                'message' => 'Erro interno ao processar a busca por reservas concluídas. Verifique o log do Laravel.'
            ], 500);
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
                // ✅ AGORA: Apenas CONFIRMADA e PENDENTE causam ocupação real
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
