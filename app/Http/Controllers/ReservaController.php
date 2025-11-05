<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Reserva;
use App\Http\Requests\StoreReservaRequest;
use App\Http\Requests\UpdateReservaStatusRequest; // <-- ESSENCIAL: Certifique-se desta importação
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReservaController extends Controller
{
    /**
     * Exibe a grade de horários disponíveis.
     */
    public function index()
    {
        // 1. HORÁRIOS FIXOS: Busca todos os horários fixos ativos
        $fixedSchedules = Schedule::where('is_active', true)
                                     ->orderBy('day_of_week')
                                     ->orderBy('start_time')
                                     ->get();

        // 2. RESERVAS QUE OCUPAM O SLOT:
        $occupiedSlots = Reserva::whereIn('status', ['pending', 'confirmed'])
                                     ->where('date', '>=', Carbon::today()->toDateString())
                                     ->get();

        // Mapeia os slots ocupados para fácil verificação (chave: 'Y-m-d H:i')
        $occupiedMap = $occupiedSlots->mapWithKeys(function ($reserva) {
            $dateTime = Carbon::parse($reserva->date . ' ' . $reserva->start_time)->format('Y-m-d H:i');
            return [$dateTime => true];
        })->toArray();

        // 3. CALCULA O CRONOGRAMA SEMANAL (próximas 2 semanas)
        $weeklySchedule = [];
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addWeeks(2);

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dayOfWeek = $date->dayOfWeekIso;
            $daySchedules = $fixedSchedules->where('day_of_week', $dayOfWeek);

            foreach ($daySchedules as $schedule) {
                $startTime = Carbon::parse($schedule->start_time);
                $endTime = Carbon::parse($schedule->end_time);

                if ($date->isToday() && $startTime->lt(Carbon::now())) {
                    continue;
                }

                $slotDateTime = $date->copy()->setTime($startTime->hour, $startTime->minute);
                $slotKey = $slotDateTime->format('Y-m-d H:i');

                if (!isset($occupiedMap[$slotKey])) {
                    $weeklySchedule[$date->toDateString()][] = [
                        'start_time' => $startTime->format('H:i'),
                        'end_time'   => $endTime->format('H:i'),
                        'price'      => $schedule->price,
                        'schedule_id' => $schedule->id,
                    ];
                }
            }
        }

        return view('reserva.index', compact('weeklySchedule'));
    }

    /**
     * Salva a pré-reserva.
     */
    public function store(StoreReservaRequest $request)
    {
        $validated = $request->validated();

        $isOccupied = Reserva::whereIn('status', ['pending', 'confirmed'])
                              ->where('date', $validated['date'])
                              ->where('start_time', $validated['start_time'])
                              ->exists();

        if ($isOccupied) {
            return redirect()->route('reserva.index')->with('error', 'Desculpe, este horário acabou de ser reservado. Por favor, escolha outro.');
        }

        $reserva = Reserva::create([
            'date'           => $validated['date'],
            'start_time'     => $validated['start_time'],
            'end_time'       => $validated['end_time'],
            'client_name'    => $validated['client_name'],
            'client_contact' => $validated['client_contact'],
            'signal_value'  => $validated['signal_value'],
            'price'          => $validated['price'],
            'status'         => 'pending',
        ]);

        $whatsappNumber = '91985320997'; // Altere para o seu número WhatsApp

        $data = Carbon::parse($reserva->date)->format('d/m/Y');
        $hora = Carbon::parse($reserva->start_time)->format('H:i');

        $messageText = "🚨 NOVA PRÉ-RESERVA PENDENTE\n\n" .
                       "Cliente: {$reserva->client_name}\n" .
                       "Contato: {$reserva->client_contact}\n" .
                       "Data/Hora: {$data} às {$hora}\n" .
                       //"Valor do Sinal: R$ " . number_format($reserva->signal_value, 2, ',', '.') . "\n" .
                       "Valor: R$ " . number_format($reserva->price, 2, ',', '.') . "\n";

        $whatsappLink = "https://api.whatsapp.com/send?phone={$whatsappNumber}&text=" . urlencode($messageText);

        return redirect()->route('reserva.index')
                         ->with('whatsapp_link', $whatsappLink)
                         ->with('success', 'Pré-reserva enviada! Por favor, entre em contato via WhatsApp para confirmar o agendamento.');
    }

    /**
     * 🎯 Implementação do método: Atualiza o status de uma reserva existente.
     * * @param  \App\Http\Requests\UpdateReservaStatusRequest  $request
     * @param  \App\Models\Reserva  $reserva (Injetado via Route Model Binding)
     * @return \Illuminate\Http\Response (Retorno JSON para uso em API/Painel)
     */
    public function updateStatus(UpdateReservaStatusRequest $request, Reserva $reserva)
    {
        // O status é validado automaticamente pelo Form Request (se é 'confirmed', 'cancelled' ou 'rejected').
        $newStatus = $request->validated('status');
        $oldStatus = $reserva->status;

        try {
            // 1. Regra de Negócio: Não permitir alteração se o status final já foi alcançado.
            if (in_array($oldStatus, ['cancelled', 'rejected'])) {
                return response()->json([
                    'message' => 'O status de uma reserva cancelada ou rejeitada não pode ser alterado.',
                    'current_status' => $oldStatus
                ], 400); // 400 Bad Request
            }

            // 2. Regra de Negócio Crítica: Impedir confirmação (confirmed) se o slot já estiver ocupado.
            if ($newStatus === 'confirmed') {
                $isOccupiedByAnother = Reserva::where('id', '!=', $reserva->id) // Exclui a reserva atual
                                              ->whereIn('status', ['pending', 'confirmed'])
                                              ->where('date', $reserva->date)
                                              ->where('start_time', $reserva->start_time)
                                              ->exists();

                if ($isOccupiedByAnother) {
                    return response()->json([
                        'message' => 'Não foi possível confirmar. O horário já está ocupado por outra reserva Pendente/Confirmada.',
                    ], 409); // 409 Conflict
                }
            }

            // 3. Atualiza o status no banco de dados
            $reserva->status = $newStatus;
            $reserva->save();

            // Retorno de sucesso
            return response()->json([
                'message' => "Status da reserva #{$reserva->id} alterado de '{$oldStatus}' para '{$newStatus}' com sucesso.",
                'reserva' => $reserva
            ], 200);

        } catch (\Exception $e) {
            \Log::error("Erro ao atualizar status da reserva {$reserva->id}: " . $e->getMessage());

            return response()->json([
                'message' => 'Ocorreu um erro interno ao tentar atualizar o status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
