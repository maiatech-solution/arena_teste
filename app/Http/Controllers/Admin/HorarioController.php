<?php

namespace App\Http\Controllers\Admin;

use App\Models\Schedule; // MODELO PADRONIZADO
use App\Models\Reserva; // Importando o modelo de Reservas de Clientes
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Carbon\CarbonPeriod;

class HorarioController extends Controller
{
    // =========================================================================
    // 🗓️ MÉTODO API: SLOTS DISPONÍVEIS (Apenas Avulsos)
    // =========================================================================
    /**
     * Retorna os horários disponíveis (avulsos) em formato JSON para o FullCalendar.
     */
    public function getAvailableSlotsApi(Request $request)
    {
        $start = $request->input('start') ? Carbon::parse($request->input('start')) : Carbon::now()->startOfMonth();
        $end = $request->input('end') ? Carbon::parse($request->input('end')) : Carbon::now()->endOfMonth();
        $now = Carbon::now();

        // 🏟️ PEGA A ARENA SELECIONADA (Importante para o filtro do FullCalendar)
        $arenaId = $request->input('arena_id');

        $startString = $start->toDateString();
        $endString = $end->toDateString();

        // 1. Busca Horários Disponíveis filtrando por Arena (se fornecida)
        $availableSlots = Schedule::where('is_active', true)
            ->whereNotNull('date')
            ->whereBetween('date', [$startString, $endString])
            ->when($arenaId, function ($query) use ($arenaId) {
                return $query->where('arena_id', $arenaId);
            })
            ->get();

        // 2. Busca Reservas Ativas filtrando pela mesma Arena
        // Isso permite que o mesmo horário esteja livre na Arena B se estiver ocupado na Arena A
        $occupiedReservas = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->whereBetween('date', [$startString, $endString])
            ->when($arenaId, function ($query) use ($arenaId) {
                return $query->where('arena_id', $arenaId);
            })
            ->get();

        $events = [];

        // 3. Gera os eventos do FullCalendar
        foreach ($availableSlots as $schedule) {
            $dateString = $schedule->date;

            if (empty($schedule->start_time) || empty($schedule->end_time)) {
                continue;
            }

            // A. Checa se o slot já passou
            $scheduleStart = Carbon::parse($dateString . ' ' . $schedule->start_time);
            $scheduleEnd = Carbon::parse($dateString . ' ' . $schedule->end_time);

            if ($scheduleEnd->lt($now)) {
                continue;
            }

            // B. Checa Conflito de Reserva - Lógica por Arena
            $isBooked = $occupiedReservas->contains(function ($reservation) use ($schedule, $scheduleStart, $scheduleEnd) {

                // 1. O conflito só existe se for na MESMA ARENA
                if ($reservation->arena_id !== $schedule->arena_id) {
                    return false;
                }

                // 2. Checagem por ID (Se a reserva nasceu desse slot)
                if (isset($reservation->fixed_slot_id) && $reservation->fixed_slot_id === $schedule->id) {
                    return true;
                }

                // 3. Checagem por Sobreposição de Tempo (Fallback de segurança)
                $reservaStart = Carbon::parse($reservation->date->format('Y-m-d') . ' ' . $reservation->start_time);
                $reservaEnd = Carbon::parse($reservation->date->format('Y-m-d') . ' ' . $reservation->end_time);

                return $reservaStart->lt($scheduleEnd) && $reservaEnd->gt($scheduleStart);
            });

            // Se o slot estiver reservado nesta arena, não mostra o "Verde"
            if ($isBooked) {
                continue;
            }

            // Adiciona o evento de DISPONIBILIDADE
            $events[] = [
                'id' => 'slot-' . $schedule->id . '-' . $dateString,
                'title' => 'Livre: R$ ' . number_format($schedule->price, 2, ',', '.'),
                'start' => $scheduleStart->toDateTimeString(),
                'end' => $scheduleEnd->toDateTimeString(),
                'color' => '#10B981', // Verde
                'className' => 'fc-event-available',
                'extendedProps' => [
                    'status' => 'available',
                    'price' => $schedule->price,
                    'schedule_id' => $schedule->id,
                    'arena_id' => $schedule->arena_id, // Passa a info da arena para o JS
                ],
            ];
        }

        return response()->json($events);
    }
    // =========================================================================


    /**
     * Exibe o formulário e a lista de horários (agora apenas avulsos).
     */
    public function index()
    {
        $availableSlots = Schedule::orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return view('admin.horarios.index', [
            'availableSlots' => $availableSlots,
        ]);
    }

    /**
     * Armazena um novo horário avulso (Slot Específico).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'arena_id'   => ['required', 'exists:arenas,id'], // ⬅️ NOVO
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
            'price'      => ['required', 'numeric', 'min:0.01'],
        ]);

        // 1. Verificação de Conflito agora considera a ARENA
        $existingHorario = Schedule::where('date', $validated['date'])
            ->where('arena_id', $validated['arena_id']) // ⬅️ NOVO
            ->where('is_active', true)
            ->where(function ($query) use ($validated) {
                $query->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            })
            ->exists();

        if ($existingHorario) {
            return back()->withInput()->withErrors(['time_conflict' => 'Já existe um slot nesta arena para este horário.']);
        }

        // 2. Cria o slot associado à arena
        Schedule::create([
            'arena_id'   => $validated['arena_id'], // ⬅️ NOVO
            'date'       => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time'   => $validated['end_time'],
            'price'      => $validated['price'],
            'is_active'  => true,
        ]);

        return redirect()->route('admin.horarios.index')->with('success', 'Slot Arena adicionado!');
    }

    /**
     * Exibe o formulário para editar um horário existente.
     */
    public function edit(Schedule $horario)
    {
        if ($horario->day_of_week !== null) {
            return redirect()->route('admin.horarios.index')->with('error', 'Este slot é recorrente e o recurso foi descontinuado. Por favor, remova-o manualmente do seu banco se necessário, ou desative-o.');
        }

        return view('admin.horarios.edit', [
            'schedule' => $horario,
        ]);
    }

    /**
     * Atualiza um horário avulso (Slot Específico).
     */
    public function update(Request $request, Schedule $horario)
    {
        if ($horario->day_of_week !== null) {
            return back()->with('error', 'Este slot é recorrente e o recurso foi descontinuado. Apenas slots avulsos podem ser atualizados.');
        }

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'is_active' => ['required', 'boolean'],
        ]);

        $date = $validated['date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        // 1. Verifica conflito com outra disponibilidade avulsa na mesma data (Schedule)
        $existingHorario = Schedule::where('id', '!=', $horario->id)
            ->where('date', $date)
            ->where('is_active', true)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($existingHorario) {
            return back()->withInput()->withErrors(['time_conflict' => 'Já existe outro slot avulso ativo e conflitante para esta data e período.']);
        }

        // 2. VERIFICAÇÃO DE CONFLITO COM TODAS AS RESERVAS DE CLIENTES (Reserva)
        $conflictReserva = Reserva::whereDate('start_time', $date)
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($conflictReserva) {
            return back()->withInput()->withErrors(['time_conflict' => 'Conflito! Já existe uma reserva de cliente (fixa ou avulsa) cobrindo parte deste horário na data selecionada.']);
        }

        $horario->update([
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'price' => $validated['price'],
            'is_active' => $validated['is_active'],
            'day_of_week' => null, // Mantém nulo
        ]);

        $message = 'Slot Avulso atualizado com sucesso para ' . Carbon::parse($validated['date'])->format('d/m/Y') . '!';

        return redirect()->route('admin.horarios.index')->with('success', $message);
    }

    /**
     * Remove um horário avulso.
     */
    public function destroy(Schedule $horario)
    {
        if ($horario->day_of_week !== null) {
            return back()->with('error', 'Este é um horário fixo recorrente e não pode ser excluído diretamente por esta rota. Use o banco de dados se precisar removê-lo.');
        }

        $tipo = 'Avulso';
        $identificador = Carbon::parse($horario->date)->format('d/m/Y');

        $timeSlot = Carbon::parse($horario->start_time)->format('H:i') . ' - ' . Carbon::parse($horario->end_time)->format('H:i');
        $fullIdentifier = "{$identificador} das {$timeSlot}";

        try {
            $horario->delete();
            return redirect()->route('admin.horarios.index')->with('success', "Horário {$tipo} ({$fullIdentifier}) excluído com sucesso.");
        } catch (QueryException $e) {
            return back()->with('error', "Não foi possível excluir o horário {$tipo} ({$fullIdentifier}). Ele pode ter reservas associadas ou outras dependências de banco de dados. Tente primeiro desativá-lo.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro desconhecido ao excluir o horário.');
        }
    }
}
