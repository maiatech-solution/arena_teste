<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiReservaController extends Controller
{
    // =========================================================================
    // ✅ MÉTODO 1: DASHBOARD DO GESTOR (Visão Completa) - CORRIGIDO FINANCEIRO
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

                // 💰 Cálculos Financeiros para o Modal
                $totalPaid = (float)($reserva->total_paid ?? 0);
                $price = (float)($reserva->price ?? 0);
                $finalPrice = (float)($reserva->final_price > 0 ? $reserva->final_price : $price);

                if ($reserva->is_fixed) {
                    return [
                        'id' => $reserva->id,
                        'title' => 'Livre (Fixo)',
                        'start' => $dateStr . 'T' . $sStart,
                        'end' => $dateStr . 'T' . $sEnd,
                        'color' => '#d1d5db',
                        'display' => 'background',
                        'extendedProps' => [
                            'is_fixed' => true,
                            'price' => $price
                        ]
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
                    'extendedProps' => [
                        'is_fixed' => false, 
                        'status' => $reserva->status,
                        'is_recurrent' => (bool)$reserva->is_recurrent,
                        'total_paid' => $totalPaid,       // 🚀 AGORA VAI!
                        'price' => $price,               // 🚀 AGORA VAI!
                        'final_price' => $finalPrice,    // 🚀 AGORA VAI!
                        'retained_amount' => $totalPaid, // 🚀 AGORA VAI!
                        'client_contact' => $reserva->client_contact ?? 'N/A'
                    ]
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
            $start = $request->query('start'); 
            $end = $request->query('end');

            if (!$arenaId || $arenaId == 'null') {
                return response()->json([]);
            }

            $dateStart = Carbon::parse($start)->toDateString();
            $dateEnd = Carbon::parse($end)->toDateString();
            
            $financeiroController = app(\App\Http\Controllers\FinanceiroController::class);

            $slots = Reserva::where('arena_id', $arenaId)
                ->where('is_fixed', true)
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->get();

            $occupied = Reserva::where('arena_id', $arenaId)
                ->where('is_fixed', false)
                ->whereBetween('date', [$dateStart, $dateEnd])
                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, Reserva::STATUS_CONCLUIDA])
                ->get();

            $available = $slots->filter(function ($slot) use ($occupied, $financeiroController) {
                $slotDate = $slot->date->format('Y-m-d');
                $sStart = Carbon::parse($slot->start_time)->format('H:i:s');
                $sEnd = Carbon::parse($slot->end_time)->format('H:i:s');

                if ($financeiroController->isCashClosed($slotDate)) {
                    return false;
                }

                if (Carbon::parse($slotDate . ' ' . $sEnd)->isPast()) {
                    return false;
                }

                $hasConfirmedConflict = $occupied->where('date', $slot->date)->contains(function ($res) use ($sStart, $sEnd) {
                    $resStart = Carbon::parse($res->start_time)->format('H:i:s');
                    $resEnd = Carbon::parse($res->end_time)->format('H:i:s');
                    $isBlockingStatus = in_array($res->status, [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA]);
                    return $isBlockingStatus && ($resStart < $sEnd && $resEnd > $sStart);
                });

                return !$hasConfirmedConflict;
            });

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
    // ✅ MÉTODO 3: LISTA/SELECT
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