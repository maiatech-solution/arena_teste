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

        $startString = $start->toDateString();
        $endString = $end->toDateString();

        // 1. Busca APENAS Horários Avulsos Ativos (com data definida) dentro do período.
        $availableSlots = Schedule::where('is_active', true)
            ->whereNotNull('date')
            ->whereBetween('date', [$startString, $endString])
            ->get();

        // 2. Busca Reservas Ativas no Período para verificação de conflito
        // Filtramos todas as reservas que se sobrepõem ao período visível do calendário.
        $occupiedReservas = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_time', '<', $end->endOfDay()->toDateTimeString())
                      ->where('end_time', '>', $start->startOfDay()->toDateTimeString());
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

            // B. Checa Conflito de Reserva - Lógica ROBUSTA
            $isBooked = $occupiedReservas->contains(function ($reservation) use ($schedule, $scheduleStart, $scheduleEnd, $dateString) {

                // TENTATIVA 1: Checagem direta por ID (Mais confiável para slots avulsos)
                // Se o campo 'schedule_id' existe na sua tabela 'reservas':
                if (isset($reservation->schedule_id) && $reservation->schedule_id === $schedule->id) {
                    return true;
                }

                // TENTATIVA 2: Checagem por Sobreposição de Tempo (Fallback)
                $reservaStart = Carbon::parse($reservation->start_time);
                $reservaEnd = Carbon::parse($reservation->end_time);

                // 🚨 Garante que a data da reserva bate com o slot avulso
                if ($reservaStart->toDateString() !== $dateString) {
                    return false;
                }

                // Checagem de sobreposição
                return $reservaStart->lt($scheduleEnd) && $reservaEnd->gt($scheduleStart);
            });

            // Se o slot estiver reservado, NUNCA adiciona o evento de DISPONIBILIDADE (verde)
            if ($isBooked) {
                continue;
            }

            // Adiciona o evento de DISPONIBILIDADE (verde)
            $events[] = [
                'id' => 'slot-' . $schedule->id . '-' . $dateString,
                'title' => 'Disponível: R$ ' . number_format($schedule->price, 2, ',', '.'),
                'start' => $scheduleStart->toDateTimeString(),
                'end' => $scheduleEnd->toDateTimeString(),
                'color' => '#10B981',
                'className' => 'fc-event-available',
                'extendedProps' => [
                    'status' => 'available',
                    'price' => $schedule->price,
                    'schedule_id' => $schedule->id,
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
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'price' => ['required', 'numeric', 'min:0.01'],
        ]);

        $date = $validated['date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        // 1. VERIFICAÇÃO DE CONFLITO COM OUTROS SLOTS DE DISPONIBILIDADE (Schedule)
        $existingHorario = Schedule::where('date', $date)
            ->where('is_active', true)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($existingHorario) {
            return back()->withInput()->withErrors(['time_conflict' => 'Já existe outro slot de disponibilidade ativo e conflitante para esta data e período.']);
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

        // Cria o slot de disponibilidade avulso
        $newSchedule = Schedule::create([
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'price' => $validated['price'],
            'is_active' => true,
            'day_of_week' => null, // Mantém nulo
        ]);

        $message = 'Slot Avulso adicionado com sucesso para ' . Carbon::parse($validated['date'])->format('d/m/Y') . '!';

        return redirect()->route('admin.horarios.index')->with('success', $message);
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
