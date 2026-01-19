<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiReservaController extends Controller
{
    /**
     * ✅ MÉTODO 1: DASHBOARD DO GESTOR (Visão Completa)
     * Ajustado para reconhecer e colorir o status de Manutenção.
     */
    public function getConfirmedReservas(Request $request)
    {
        try {
            // ... (seu código inicial de datas e query permanece igual)

            return response()->json($reservas->map(function ($reserva) {
                $sStart = Carbon::parse($reserva->start_time)->format('H:i:s');
                $sEnd = Carbon::parse($reserva->end_time)->format('H:i:s');
                $dateStr = Carbon::parse($reserva->date)->format('Y-m-d');

                // 🛠️ 1ª PRIORIDADE: Tratar Manutenção (Mesmo que seja is_fixed)
                if ($reserva->status === 'maintenance') {
                    return [
                        'id' => $reserva->id,
                        'title' => '🛠️ MANUTENÇÃO',
                        'start' => $dateStr . 'T' . $sStart,
                        'end' => $dateStr . 'T' . $sEnd,
                        'color' => '#DB2777', // Rosa Pink
                        'extendedProps' => [
                            'is_fixed' => false, // No Dashboard, tratamos como evento para sobrepor o verde
                            'status' => 'maintenance',
                            'price' => (float)$reserva->price,
                            'client_contact' => 'N/A'
                        ]
                    ];
                }

                // 2ª PRIORIDADE: Se for um slot fixo comum de base (Cinza)
                if ($reserva->is_fixed) {
                    return [
                        'id' => $reserva->id,
                        'title' => 'Livre (Fixo)',
                        'start' => $dateStr . 'T' . $sStart,
                        'end' => $dateStr . 'T' . $sEnd,
                        'color' => '#d1d5db',
                        'display' => 'background', // O 'background' faz ele ficar "por baixo"
                        'extendedProps' => [
                            'is_fixed' => true,
                            'price' => (float)$reserva->price
                        ]
                    ];
                }

                // 3ª PRIORIDADE: LÓGICA DE CORES PARA CLIENTES (Azul, Laranja, Verde)
                $color = '#4f46e5';
                $title = ($reserva->user->name ?? $reserva->client_name ?? 'Cliente');

                if ($reserva->status === Reserva::STATUS_PENDENTE) $color = '#f59e0b';
                if ($reserva->status === Reserva::STATUS_CONCLUIDA) $color = '#10b981';

                return [
                    'id' => $reserva->id,
                    'title' => $title,
                    'start' => $dateStr . 'T' . $sStart,
                    'end' => $dateStr . 'T' . $sEnd,
                    'color' => $color,
                    'extendedProps' => [
                        'is_fixed' => false,
                        'status' => $reserva->status,
                        'total_paid' => (float)$reserva->total_paid,
                        'price' => (float)$reserva->price,
                        'client_contact' => $reserva->client_contact ?? 'N/A'
                    ]
                ];
            }));
        } catch (\Exception $e) {
            Log::error("Erro Dashboard API: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ✅ MÉTODO 2: AGENDAMENTO PÚBLICO (Ultra-Seguro)
     * Ajustado para BLOQUEAR horários em manutenção.
     */
    public function getAvailableSlotsApi(Request $request)
    {
        try {
            $arenaId = $request->query('arena_id');
            $start = $request->query('start');
            $end = $request->query('end');

            if (!$arenaId || $arenaId == 'null') return response()->json([]);

            $dateStart = Carbon::parse($start)->toDateString();
            $dateEnd = Carbon::parse($end)->toDateString();

            $financeiroController = app(\App\Http\Controllers\FinanceiroController::class);

            // Slots de base
            $slots = Reserva::where('arena_id', $arenaId)
                ->where('is_fixed', true)
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->get();

            // 🚫 Ocupação: Agora incluímos explicitamente 'maintenance' como impeditivo
            $occupied = Reserva::where('arena_id', $arenaId)
                ->where('is_fixed', false)
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->whereIn('status', [
                    Reserva::STATUS_CONFIRMADA,
                    Reserva::STATUS_PENDENTE,
                    Reserva::STATUS_CONCLUIDA,
                    'maintenance' // 👈 ADICIONADO: Manutenção bloqueia o site
                ])
                ->get();

            $available = $slots->filter(function ($slot) use ($occupied, $financeiroController) {
                $slotDate = $slot->date->format('Y-m-d');
                $sStart = Carbon::parse($slot->start_time)->format('H:i:s');
                $sEnd = Carbon::parse($slot->end_time)->format('H:i:s');

                // Filtros de segurança (Caixa e Passado)
                if ($financeiroController->isCashClosed($slotDate)) return false;
                if (Carbon::parse($slotDate . ' ' . $sEnd)->isPast()) return false;

                // Checa conflito (se houver QUALQUER reserva não cancelada naquele horário)
                $hasConflict = $occupied->where('date', $slot->date)->contains(function ($res) use ($sStart, $sEnd) {
                    $resStart = Carbon::parse($res->start_time)->format('H:i:s');
                    $resEnd = Carbon::parse($res->end_time)->format('H:i:s');
                    return ($resStart < $sEnd && $resEnd > $sStart);
                });

                return !$hasConflict;
            });

            return response()->json($available->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'title' => 'Livre',
                    'start' => $slot->date->format('Y-m-d') . 'T' . Carbon::parse($slot->start_time)->format('H:i:s'),
                    'end' => $slot->date->format('Y-m-d') . 'T' . Carbon::parse($slot->end_time)->format('H:i:s'),
                    'className' => 'fc-event-available',
                    'extendedProps' => ['price' => (float)$slot->price, 'is_fixed' => true]
                ];
            })->values());
        } catch (\Exception $e) {
            Log::error("Erro API Agendamento: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * ✅ MÉTODO 3: LISTA/SELECT DE HORÁRIOS
     * Ajustado para não mostrar horários em manutenção no select.
     */
    public function getAvailableTimes(Request $request)
    {
        $date = Carbon::parse($request->date)->toDateString();
        $arenaId = $request->arena_id;
        $now = Carbon::now();

        $slots = Reserva::where('arena_id', $arenaId)->where('is_fixed', true)->whereDate('date', $date)->get();

        $occupied = Reserva::where('arena_id', $arenaId)
            ->where('is_fixed', false)
            ->whereDate('date', $date)
            ->whereIn('status', [
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_PENDENTE,
                Reserva::STATUS_CONCLUIDA,
                'maintenance' // 👈 ADICIONADO
            ])->get();

        $times = [];
        foreach ($slots as $slot) {
            $sStart = Carbon::parse($slot->start_time)->format('H:i');
            $sEnd = Carbon::parse($slot->end_time)->format('H:i');

            if (Carbon::parse($date . ' ' . $sEnd)->lt($now)) continue;

            $isOccupied = $occupied->contains(function ($res) use ($sStart, $sEnd) {
                $resStart = Carbon::parse($res->start_time)->format('H:i');
                $resEnd = Carbon::parse($res->end_time)->format('H:i');
                return $resStart < $sEnd && $resEnd > $sStart;
            });

            if (!$isOccupied) {
                $times[] = [
                    'id' => $slot->id,
                    'time_slot' => $sStart . ' - ' . $sEnd,
                    'price' => number_format($slot->price, 2, ',', '.'),
                    'raw_price' => $slot->price,
                    'start_time' => $sStart,
                    'end_time' => $sEnd,
                ];
            }
        }
        return response()->json(collect($times)->sortBy('start_time')->values());
    }
}
