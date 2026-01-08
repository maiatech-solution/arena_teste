<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiReservaController extends Controller
{
    // =========================================================================
    // ✅ MÉTODO 1: DASHBOARD DO GESTOR (Visão Completa)
    // =========================================================================
    public function getConfirmedReservas(Request $request)
    {
        try {
            $start = $request->input('start');
            $end = $request->input('end');
            $arenaId = $request->input('arena_id');

            $query = Reserva::whereDate('date', '>=', Carbon::parse($start)->toDateString())
                ->whereDate('date', '<=', Carbon::parse($end)->toDateString());

            if ($request->filled('arena_id') && $arenaId !== 'null') {
                $query->where('arena_id', $arenaId);
            }

            $reservas = $query->with('user')->get();

            return response()->json($reservas->map(function ($reserva) {
                // Tratamento seguro de horas
                $sStart = $reserva->start_time instanceof Carbon ? $reserva->start_time->format('H:i:s') : Carbon::parse($reserva->start_time)->format('H:i:s');
                $sEnd = $reserva->end_time instanceof Carbon ? $reserva->end_time->format('H:i:s') : Carbon::parse($reserva->end_time)->format('H:i:s');
                $dateStr = $reserva->date instanceof Carbon ? $reserva->date->format('Y-m-d') : Carbon::parse($reserva->date)->format('Y-m-d');

                if ($reserva->is_fixed) {
                    return [
                        'id' => $reserva->id,
                        'title' => 'Livre (Fixo)',
                        'start' => $dateStr . 'T' . $sStart,
                        'end' => $dateStr . 'T' . $sEnd,
                        'color' => '#d1d5db',
                        'display' => 'background',
                        'extendedProps' => ['is_fixed' => true]
                    ];
                }

                $color = '#4f46e5';
                if ($reserva->status === Reserva::STATUS_PENDENTE) $color = '#f59e0b';
                if ($reserva->status === Reserva::STATUS_CONCLUIDA) $color = '#10b981';

                return [
                    'id' => $reserva->id,
                    'title' => ($reserva->user->name ?? $reserva->client_name ?? 'Cliente'),
                    'start' => $dateStr . 'T' . $sStart,
                    'end' => $dateStr . 'T' . $sEnd,
                    'color' => $color,
                    'extendedProps' => ['is_fixed' => false, 'status' => $reserva->status]
                ];
            }));
        } catch (\Exception $e) {
            Log::error("Erro Dashboard: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO 2: AGENDAMENTO PÚBLICO (Versão Ultra-Segura)
    // =========================================================================
    public function getAvailableSlotsApi(Request $request)
    {
        try {
            $arenaId = $request->query('arena_id');
            $start = $request->query('start'); // Ex: 2026-01-07T00:00:00-03:00
            $end = $request->query('end');

            if (!$arenaId || $arenaId == 'null') {
                return response()->json([]);
            }

            // 🎯 TRATAMENTO DE DATA: Extrai apenas YYYY-MM-DD para o SQL
            $dateStart = Carbon::parse($start)->toDateString();
            $dateEnd = Carbon::parse($end)->toDateString();
            $now = Carbon::now();

            // Instancia o controller financeiro para checar o status do caixa
            $financeiroController = app(\App\Http\Controllers\FinanceiroController::class);

            // 1. Pegamos os slots de funcionamento (is_fixed = true)
            $slots = Reserva::where('arena_id', $arenaId)
                ->where('is_fixed', true)
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->get();

            // 2. Pegamos as reservas de clientes (ocupações)
            $occupied = Reserva::where('arena_id', $arenaId)
                ->where('is_fixed', false)
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, Reserva::STATUS_CONCLUIDA])
                ->get();

            // 3. Filtragem de Conflitos, Horários Passados e CAIXA FECHADO
            $available = $slots->filter(function ($slot) use ($occupied, $now, $financeiroController) {
                $slotDate = $slot->date->format('Y-m-d');
                $sStart = Carbon::parse($slot->start_time)->format('H:i:s');
                $sEnd = Carbon::parse($slot->end_time)->format('H:i:s');

                // 🛑 REGRA ZERO: Se o caixa do dia estiver fechado, o horário fica indisponível
                if ($financeiroController->isCashClosed($slotDate)) {
                    return false;
                }

                // Regra A: Se o horário de fim já passou (considerando data e hora), esconde
                if (Carbon::parse($slotDate . ' ' . $sEnd)->isPast()) {
                    return false;
                }

                // Regra B: Verifica se há reserva de cliente CONFIRMADA no mesmo horário
                // (Mantemos o leilão: se houver apenas PENDENTES, o slot verde continua aparecendo)
                $hasConfirmedConflict = $occupied->where('date', $slot->date)->contains(function ($res) use ($sStart, $sEnd) {
                    $resStart = Carbon::parse($res->start_time)->format('H:i:s');
                    $resEnd = Carbon::parse($res->end_time)->format('H:i:s');

                    // Conflito apenas com quem já foi Confirmado ou Concluído
                    $isBlockingStatus = in_array($res->status, [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA]);

                    return $isBlockingStatus && ($resStart < $sEnd && $resEnd > $sStart);
                });

                return !$hasConfirmedConflict;
            });

            // 🎯 O SEGREDO: values() transforma em array [] para o FullCalendar não "branquear"
            return response()->json($available->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'title' => 'Livre',
                    'start' => $slot->date->format('Y-m-d') . 'T' . Carbon::parse($slot->start_time)->format('H:i:s'),
                    'end' => $slot->date->format('Y-m-d') . 'T' . Carbon::parse($slot->end_time)->format('H:i:s'),
                    'className' => 'fc-event-available',
                    'extendedProps' => [
                        'price' => (float)$slot->price,
                        'is_fixed' => true
                    ]
                ];
            })->values());
        } catch (\Exception $e) {
            Log::error("Erro API Agendamento: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ✅ MÉTODO 3: LISTA/SELECT (Mantido)
    // =========================================================================
    public function getAvailableTimes(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'arena_id' => 'required|exists:arenas,id'
        ]);

        $date = Carbon::parse($request->date)->toDateString();
        $arenaId = $request->arena_id;
        $now = Carbon::now();

        $slots = Reserva::where('arena_id', $arenaId)->where('is_fixed', true)->whereDate('date', $date)->get();
        $occupied = Reserva::where('arena_id', $arenaId)->where('is_fixed', false)->whereDate('date', $date)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, Reserva::STATUS_CONCLUIDA])->get();

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
                    'arena_id' => $slot->arena_id,
                ];
            }
        }
        return response()->json(collect($times)->sortBy('start_time')->values());
    }
}
