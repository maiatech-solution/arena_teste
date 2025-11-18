<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiReservaController extends Controller
{
    // =========================================================================
    // ✅ MÉTODO: Horários Disponíveis p/ Calendário (API) - ISOLADO E ROBUSTO
    // =========================================================================
    /**
     * Retorna os slots gerados pelas Reservas Fixas (is_fixed=true) que estão disponíveis (GREEN).
     */
    public function getAvailableSlotsApi(Request $request)
    {
        try {
            // O FullCalendar envia 'start' e 'end' para delimitar o período
            $startDate = Carbon::parse($request->input('start', Carbon::today()->toDateString()));
            $endDate = Carbon::parse($request->input('end', Carbon::today()->addWeeks(6)->toDateString()));

            // 1. Busca todos os slots de horário fixo (GRADE DE DISPONIBILIDADE)
            // 🛑 CRÍTICO: Filtra APENAS slots com status CONFIRMADA. Se o status for CANCELADA, é ignorado!
            $allFixedSlots = Reserva::where('is_fixed', true)
                                           ->whereDate('date', '>=', $startDate->toDateString())
                                           ->whereDate('date', '<=', $endDate->toDateString())
                                           ->where('status', Reserva::STATUS_CONFIRMADA) // Slots que definem a grade
                                           ->get();

            $events = [];

            foreach ($allFixedSlots as $slot) {
                $slotStartTime = $slot->start_time;
                $slotEndTime = $slot->end_time;

                // 🛑 CRÍTICO: Ignora o slot se o tempo for inválido (NULL/Empty)
                if (empty($slotStartTime) || empty($slotEndTime)) {
                    Log::warning("Slot fixo ID {$slot->id} pulado devido a start_time/end_time inválido.");
                    continue;
                }

                $slotDateString = $slot->date->toDateString();

                // 1. Cria o objeto Carbon para o início
                $startDateTime = Carbon::parse($slotDateString . ' ' . $slotStartTime);

                // 2. Cria o objeto Carbon para o fim (inicialmente no mesmo dia)
                $endDateTime = Carbon::parse($slotDateString . ' ' . $slotEndTime);

                // 🛑 CORREÇÃO CRÍTICA PARA SLOTS QUE CRUZAM A MEIA-NOITE (Ex: 23:00 -> 00:00) 🛑
                // Se a hora de fim for menor ou igual à hora de início (como 00:00:00 é menor que 23:00:00),
                // significa que o slot termina no dia seguinte.
                if ($endDateTime->lte($startDateTime)) {
                    $endDateTime->addDay();
                    Log::info("Slot ID {$slot->id}: Ajuste de data final para o dia seguinte (De {$slotDateString} para {$endDateTime->toDateString()}).");
                }

                // 🛑 CORREÇÃO DE REGRESSÃO: Usar format('Y-m-d\TH:i:s') em vez de toIso8601String()
                // para evitar problemas de fuso horário no frontend.
                $startOutput = $startDateTime->format('Y-m-d\TH:i:s');
                $endOutput = $endDateTime->format('Y-m-d\TH:i:s');

                // 2. Checa se o slot FIXO está ocupado por uma RESERVA PONTUAL (real cliente)
                $isOccupiedByPunctual = Reserva::where('is_fixed', false)
                    ->whereDate('date', $slotDateString)
                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                    // Compara as strings de tempo no DB
                    ->where(function ($query) use ($slotStartTime, $slotEndTime) {
                        $query->where('start_time', '<', $slotEndTime)
                              ->where('end_time', '>', $slotStartTime);
                    })
                    ->exists();

                // 3. Se o slot NÃO estiver ocupado por um pontual, ele está DISPONÍVEL (GREEN).
                if (!$isOccupiedByPunctual) {

                    $title = "Slot Livre: R$ " . number_format($slot->price, 2, ',', '.');

                    $events[] = [
                        'id' => $slot->id,
                        'title' => $title,
                        'start' => $startOutput,
                        'end' => $endOutput,
                        'color' => '#10b981', // Verde para Disponível (Emerald)
                        'className' => 'fc-event-available',
                        'extendedProps' => [
                            'status' => 'available',
                            'price' => $slot->price,
                            'is_fixed' => true,
                        ]
                    ];
                }
            }

            return response()->json($events);

        } catch (\Exception $e) {
            // Loga o erro detalhadamente no servidor
            Log::error("Erro fatal ao gerar slots disponíveis para o calendário público (ApiReservaController): " . $e->getMessage(), ['exception' => $e]);

            // Retorna uma resposta de erro JSON com status 500
            return response()->json([
                'error' => 'Erro interno do servidor ao buscar horários. Detalhes: ' . $e->getMessage(),
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO: Horários Disponíveis p/ FORMULÁRIO PÚBLICO (HTML) - ROBUSTO
    // =========================================================================
    /**
     * Calcula e retorna os horários disponíveis para uma data específica (página pública e /admin/reservas/create).
     */
    public function getAvailableTimes(Request $request)
    {
        $request->validate(['date' => 'required|date_format:Y-m-d']);
        $dateString = $request->input('date');
        $selectedDate = Carbon::parse($dateString);
        $isToday = $selectedDate->isToday();
        $now = Carbon::now();

        // 1. Busca todos os slots de horário fixo (GRADE DE DISPONIBILIDADE) para esta data
        // 🛑 CRÍTICO: Não busca slots com status CANCELADA
        $allFixedSlots = Reserva::where('is_fixed', true)
                                     ->whereDate('date', $dateString)
                                     ->where('status', Reserva::STATUS_CONFIRMADA) // Filtro para ignorar CANCELADA
                                     ->get();

        // 2. Busca todas as RESERVAS PONTUAIS (ocupações)
        $occupiedReservas = Reserva::where('is_fixed', false)
                                           ->whereDate('date', $dateString)
                                           ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                           ->get();

        $availableTimes = [];

        // 3. Itera sobre a grade de slots fixos
        foreach ($allFixedSlots as $slot) {

            // 🛑 CORREÇÃO CRÍTICA: Ignora o slot se o tempo for inválido (NULL/Empty)
            if (empty($slot->start_time) || empty($slot->end_time)) {
                Log::warning("Slot fixo ID {$slot->id} pulado devido a start_time/end_time inválido no getAvailableTimes.");
                continue;
            }

            $slotStart = Carbon::parse($slot->start_time);
            $slotEnd = Carbon::parse($slot->end_time);

            // 🛑 AQUI A HORA DE FIM ESTÁ DENTRO DO DIA ATUAL
            $slotEndDateTime = $selectedDate->copy()->setTime($slotEnd->hour, $slotEnd->minute);

            // 🛑 CORREÇÃO PONTUAL: Se a hora de fim for menor que a de início, avançar para o próximo dia
            if ($slotEnd->lt($slotStart)) {
                $slotEndDateTime->addDay();
            }

            // Verifica se o slot já passou hoje
            if ($isToday && $slotEndDateTime->lt($now)) {
                continue;
            }

            // Checagem de Conflito: O slot fixo é considerado indisponível se houver uma reserva PONTUAL por cima.
            $isOccupiedByPunctual = $occupiedReservas->contains(function ($reservation) use ($slotStart, $slotEnd) {
                return $reservation->start_time < $slotEnd->format('H:i:s') && $reservation->end_time > $slotStart->format('H:i:s');
            });

            if (!$isOccupiedByPunctual) {
                // Slot disponível
                $availableTimes[] = [
                    'id' => $slot->id, // Usando ID da Reserva Fixa
                    'time_slot' => $slotStart->format('H:i') . ' - ' . $slotEnd->format('H:i'),
                    'price' => number_format($slot->price, 2, ',', '.'),
                    'raw_price' => $slot->price,
                    'start_time' => $slotStart->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'schedule_id' => $slot->id, // O ID do slot disponível é o ID da Reserva Fixa
                ];
            }
        }

        // Ordena por hora de início
        $finalAvailableTimes = collect($availableTimes)->sortBy('start_time')->values();

        return response()->json($finalAvailableTimes);
    }
}
