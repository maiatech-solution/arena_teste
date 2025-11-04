<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReservaController extends Controller
{
    /**
     * Exibe a grade de horários disponíveis para o cliente,
     * excluindo slots que já possuem reservas Pendentes ou Confirmadas.
     */
    public function index()
    {
        // 1. HORÁRIOS FIXOS: Busca todos os horários fixos ativos
        $fixedSchedules = Schedule::where('is_active', true)
                                  ->orderBy('day_of_week')
                                  ->orderBy('start_time')
                                  ->get();

        // 2. RESERVAS QUE OCUPAM O SLOT:
        // Busca todas as reservas que não foram rejeitadas (ou seja, Pendentes ou Confirmadas)
        $occupiedSlots = Reserva::where('status', '!=', 'rejected')
                                ->where('date', '>=', Carbon::today()->toDateString()) // Apenas futuras ou de hoje
                                ->get();

        // Mapeia os slots ocupados para fácil verificação (chave: 'Y-m-d H:i')
        $occupiedMap = $occupiedSlots->mapWithKeys(function ($reserva) {
            $dateTime = Carbon::parse($reserva->date . ' ' . $reserva->start_time)->format('Y-m-d H:i');
            return [$dateTime => true];
        })->toArray();

        // 3. CALCULA O CRONOGRAMA SEMANAL (próximas 2 semanas)
        $weeklySchedule = [];
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addWeeks(2); // Agenda para as próximas 2 semanas

        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dayOfWeek = $date->dayOfWeekIso; // 1 (Segunda) a 7 (Domingo)
            $daySchedules = $fixedSchedules->where('day_of_week', $dayOfWeek);

            foreach ($daySchedules as $schedule) {
                $startTime = Carbon::parse($schedule->start_time);
                $endTime = Carbon::parse($schedule->end_time);

                // Formata a data e hora do slot atual
                // Garante que o slot de hoje só é exibido se for futuro (já que estamos usando Carbon::today())
                if ($date->isToday() && $startTime->lt(Carbon::now())) {
                    continue; // Pula horários que já passaram hoje
                }

                $slotDateTime = $date->copy()->setTime($startTime->hour, $startTime->minute);
                $slotKey = $slotDateTime->format('Y-m-d H:i');

                // Verifica se o slot já está ocupado por uma reserva Pendente ou Confirmada
                if (!isset($occupiedMap[$slotKey])) {
                    $weeklySchedule[$date->toDateString()][] = [
                        'start_time' => $startTime->format('H:i'),
                        'end_time'   => $endTime->format('H:i'),
                        'price'      => $schedule->price,
                    ];
                }
            }
        }

        return view('reserva.index', compact('weeklySchedule'));
    }

    /**
     * Salva a pré-reserva e gera o link de WhatsApp.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'           => 'required|date',
            'start_time'     => 'required|date_format:H:i',
            'end_time'       => 'required|date_format:H:i',
            'client_name'    => 'required|string|max:255',
            'client_contact' => 'required|string|max:255',
            'price'          => 'required|numeric',
        ]);

        // Verifica novamente se o slot não está ocupado ANTES de salvar (proteção extra)
        $isOccupied = Reserva::where('status', '!=', 'rejected')
                            ->where('date', $validated['date'])
                            ->where('start_time', $validated['start_time'])
                            ->exists();

        if ($isOccupied) {
            return redirect()->route('reserva.index')->with('error', 'Desculpe, este horário acabou de ser reservado. Por favor, escolha outro.');
        }

        // Salva a reserva como PENDENTE
        $reserva = Reserva::create([
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'client_name' => $validated['client_name'],
            'client_contact' => $validated['client_contact'],
            'price' => $validated['price'],
            'status' => 'pending', // Status inicial
        ]);

        // ----------------------------------------------------
        // 💬 GERAÇÃO DA MENSAGEM DE WHATSAPP PARA O GESTOR
        // ----------------------------------------------------

        // ⚠️ AJUSTE AQUI: Substitua este valor pelo WhatsApp do Administrador/Gestor.
        $whatsappNumber = '91985320997';

        $data = Carbon::parse($reserva->date)->format('d/m/Y');
        $hora = Carbon::parse($reserva->start_time)->format('H:i');

        $messageText = "🚨 NOVA PRÉ-RESERVA PENDENTE\n\n" .
                       "Cliente: {$reserva->client_name}\n" .
                       "Contato: {$reserva->client_contact}\n" .
                       "Data/Hora: {$data} às {$hora}\n" .
                       "Valor: R$ " . number_format($reserva->price, 2, ',', '.') . "\n";

        $whatsappLink = "https://api.whatsapp.com/send?phone={$whatsappNumber}&text=" . urlencode($messageText);

        return redirect()->route('reserva.index')
                         ->with('whatsapp_link', $whatsappLink)
                         ->with('success', 'Pré-reserva enviada! Por favor, entre em contato via WhatsApp para confirmar o agendamento.');
    }

    // ... você pode adicionar outros métodos aqui se necessário
}
