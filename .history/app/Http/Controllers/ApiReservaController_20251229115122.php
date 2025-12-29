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

            // CRÍTICO: Incluir TODOS os status que ocupam um horário
            $statuses = [
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_PENDENTE,
                Reserva::STATUS_CONCLUIDA,
                Reserva::STATUS_LANCADA_CAIXA,
                Reserva::STATUS_NO_SHOW,
            ];

            $reservas = Reserva::where('is_fixed', false) // Apenas reservas de cliente
                ->whereIn('status', $statuses)
                ->whereDate('date', '>=', $start)
                ->whereDate('date', '<=', $end)
                ->get();

            $events = $reservas->map(function ($reserva) {

                $isRecurrent = (bool)$reserva->is_recurrent;
                $isPaid = in_array($reserva->status, [Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA]);
                $isNoShow = $reserva->status === Reserva::STATUS_NO_SHOW;
                $isPending = $reserva->status === Reserva::STATUS_PENDENTE;

                // 🎯 CORREÇÃO CRÍTICA APLICADA:
                // Se 'start_time' é um objeto Carbon (devido ao cast no Model), use ->format('H:i:s').
                // Garante que o formato é compatível com a ISO 8601 (FullCalendar).
                $timeStartFormatted = $reserva->start_time instanceof Carbon ? $reserva->start_time->format('H:i:s') : $reserva->start_time;
                $timeEndFormatted = $reserva->end_time instanceof Carbon ? $reserva->end_time->format('H:i:s') : $reserva->end_time;

                $startOutput = $reserva->date->format('Y-m-d') . 'T' . $timeStartFormatted;
                $endOutput = $reserva->date->format('Y-m-d') . 'T' . $timeEndFormatted;

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
                    $color = '#E53E3E'; // Vermelho
                    $className = 'fc-event-no-show';
                    $titlePrefix = 'FALTA: ';
                } elseif ($isPaid) {
                    // PAGA/CONCLUIDA
                    $color = '#10b981'; // Verde para concluída/paga
                    $className .= ' fc-event-paid';
                    $titlePrefix = 'PAGO: ';
                }

                // Prefixo de título para recorrente (deve ser o último a ser adicionado se for o caso)
                if ($isRecurrent) {
                    $titlePrefix = 'RECORR.: ' . str_replace('PAGO: ', '', $titlePrefix);
                }

                $clientName = $reserva->user ? $reserva->user->name : ($reserva->client_name ?? 'Cliente');

                // Monta o título completo.
                $eventTitle = $titlePrefix . $clientName . ' - R$ ' . number_format((float)$reserva->price, 2, ',', '.');

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
            return response()->json(['error' => 'Erro interno ao carregar reservas. Detalhes: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO 2: Horários Disponíveis p/ Calendário (API) - CORRIGIDO
    // Rota: api.horarios.disponiveis
    // =========================================================================
    /**
     * Retorna os slots da GRADE (is_fixed=true) que estão livres.
     * * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableSlotsApi(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->input('start', Carbon::today()->toDateString()));
            $endDate = Carbon::parse($request->input('end', Carbon::today()->addWeeks(6)->toDateString()));

            // Busca apenas slots de disponibilidade (FREE)
            $allFixedSlots = Reserva::where('is_fixed', true)
                ->whereDate('date', '>=', $startDate->toDateString())
                ->whereDate('date', '<=', $endDate->toDateString())
                ->where('status', Reserva::STATUS_FREE) // Apenas os slots livres
                ->get();

            $events = [];

            foreach ($allFixedSlots as $slot) {

                // 🎯 CORREÇÃO CRÍTICA APLICADA AQUI:
                // Se 'start_time' é um objeto Carbon, use ->format('H:i:s') para obter a string de hora.
                $slotStartTime = $slot->start_time instanceof Carbon ? $slot->start_time->format('H:i:s') : $slot->start_time;
                $slotEndTime = $slot->end_time instanceof Carbon ? $slot->end_time->format('H:i:s') : $slot->end_time;

                if (empty($slotStartTime) || empty($slotEndTime)) continue;

                $slotDateString = $slot->date->toDateString();

                // Garante que o parse é feito com a string de hora (corrigido)
                $startDateTime = Carbon::parse($slotDateString . ' ' . $slotStartTime);
                $endDateTime = Carbon::parse($slotDateString . ' ' . $slotEndTime);

                // Lógica de virada de dia (23:00 -> 00:00)
                if ($endDateTime->lte($startDateTime)) {
                    $endDateTime->addDay();
                }

                $startOutput = $startDateTime->format('Y-m-d\TH:i:s');
                $endOutput = $endDateTime->format('Y-m-d\TH:i:s');

                // Filtro de sobreposição: verifica se o slot está ocupado por RESERVA DE CLIENTE
                $isOccupied = Reserva::where('is_fixed', false)
                    ->whereDate('date', $slotDateString)
                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                    ->where(function ($query) use ($slotStartTime, $slotEndTime) {
                        // Usando as strings de hora formatadas para a query SQL
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
            // Retorna o erro 500 para o FullCalendar
            return response()->json(['error' => 'Erro interno ao carregar horários disponíveis. Detalhes: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO 3: Horários Disponíveis p/ FORMULÁRIO PÚBLICO (HTML) - CORRIGIDO
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

        // Slots fixos (FREE)
        $allFixedSlots = Reserva::where('is_fixed', true)
            ->whereDate('date', $dateString)
            ->where('status', Reserva::STATUS_FREE)
            ->get();

        // Reservas ativas de clientes (PENDING/CONFIRMED)
        $occupiedReservas = Reserva::where('is_fixed', false)
            ->whereDate('date', $dateString)
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->get();

        $availableTimes = [];

        foreach ($allFixedSlots as $slot) {
            // 1. Extração segura da hora (como removemos o cast, tratamos como string)
            $sTime = $slot->start_time;
            $eTime = $slot->end_time;
            $dateStr = $slot->date->format('Y-m-d');

            // 2. Ajuste de fuso horário para Belém (Garante que 12h e 13h apareçam)
            $now = Carbon::now('America/Belem');
            $slotEndDT = Carbon::parse($dateStr . ' ' . $eTime, 'America/Belem');

            // Lógica para horários que cruzam a meia-noite
            if ($slotEndDT->hour < Carbon::parse($sTime)->hour) {
                $slotEndDT->addDay();
            }

            // 🛑 FILTRO DE EXPIRAÇÃO: Só some se o horário de FIM já passou
            if ($slot->date->isToday() && $slotEndDT->isBefore($now)) {
                continue;
            }

            // 🛑 LÓGICA DE SOBREPOSIÇÃO (O PONTO CHAVE):
            // Só consideramos o slot "Ocupado" se houver uma reserva CONFIRMADA ou PAGA.
            // O status 'pending' FOI REMOVIDO DAQUI para permitir pré-reservas simultâneas.
            $isOccupied = Reserva::where('is_fixed', false)
                ->whereDate('date', $dateStr)
                ->whereIn('status', [
                    Reserva::STATUS_CONFIRMADA,
                    Reserva::STATUS_CONCLUIDA,
                    Reserva::STATUS_LANCADA_CAIXA
                ])
                ->where('start_time', '<', $eTime)
                ->where('end_time', '>', $sTime)
                ->exists();

            if (!$isOccupied) {
                $events[] = [
                    'id' => $slot->id,
                    'title' => 'Disponível',
                    'start' => $dateStr . 'T' . $sTime,
                    'end' => $dateStr . 'T' . $eTime,
                    'color' => '#10b981',
                    'className' => 'fc-event-available',
                    'extendedProps' => [
                        'status' => 'free',
                        'price' => (float)$slot->price,
                        'is_fixed' => true
                    ]
                ];
            }
        }

        $finalAvailableTimes = collect($availableTimes)->sortBy('start_time')->values();

        return response()->json($finalAvailableTimes);
    }
}
