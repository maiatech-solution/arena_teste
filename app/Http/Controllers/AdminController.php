<?php
// [START OF FILE]

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

// --- IMPORTS ---
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Exibe o dashboard principal do gestor.
     */
    public function dashboard()
    {
        // Esta linha continua calculando a contagem de pendências
        $reservasPendentesCount = Reserva::where('status', Reserva::STATUS_PENDENTE)->count();

        // O método retorna APENAS a contagem de pendências. O calendário carrega os eventos via API.
        return view('dashboard', compact('reservasPendentesCount'));
    }

    // =========================================================================
    // 🗓️ MÉTODO API: RESERVAS CONFIRMADAS PARA FULLCALENDAR (ADAPTADO)
    // =========================================================================
    /**
     * Retorna as reservas CONFIRMADAS/PENDENTES REAIS (is_fixed = false) em formato JSON para o FullCalendar.
     */
    public function getConfirmedReservasApi(Request $request)
    {
        // O FullCalendar envia os parâmetros 'start' e 'end' para filtrar o período
        $start = $request->input('start') ? Carbon::parse($request->input('start')) : Carbon::now()->startOfMonth();
        $end = $request->input('end') ? Carbon::parse($request->input('end')) : Carbon::now()->endOfMonth();

        // 🛑 CRÍTICO: Busca APENAS reservas REAIS de clientes (is_fixed = false) para o calendário.
        $reservas = Reserva::where('is_fixed', false)
                            ->whereDate('date', '>=', $start->toDateString())
                            ->whereDate('date', '<=', $end->toDateString())
                            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                            ->with('user')
                            ->get();

        $events = $reservas->map(function ($reserva) {
            $bookingDate = $reserva->date->toDateString();

            // Usa os campos de TIME para construir o DateTime
            $start = Carbon::parse($bookingDate . ' ' . $reserva->start_time);
            $end = $reserva->end_time ? Carbon::parse($bookingDate . ' ' . $reserva->end_time) : $start->copy()->addHour();

            $userName = optional($reserva->user)->name;
            $clientName = $userName ?? $reserva->client_name ?? 'Cliente Desconhecido';
            $statusColor = $reserva->status === Reserva::STATUS_PENDENTE ? '#ff9800' : '#4f46e5'; // Laranja/Indigo
            $statusText = $reserva->status === Reserva::STATUS_PENDENTE ? 'PENDENTE: ' : 'RESERVADO: ';

            // Monta o título do evento
            $title = $statusText . $clientName;
            if (isset($reserva->price)) {
                $title .= ' - R$ ' . number_format($reserva->price, 2, ',', '.');
            }

            return [
                'id' => $reserva->id,
                'title' => $title,
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'color' => $statusColor,
                'className' => 'fc-event-booked',
                'extendedProps' => [
                    'status' => $reserva->status,
                    'client_contact' => $reserva->client_contact,
                    // ✅ NOVO: Passa a flag de recorrência para o JS
                    'is_recurrent' => (bool)$reserva->is_recurrent,
                    // ✅ NOVO: Passa o ID da série, se houver
                    'recurrent_series_id' => $reserva->recurrent_series_id,
                ]
            ];
        });

        return response()->json($events);
    }
    // =========================================================================

    // --- Métodos de Listagem, Ação e Status de Reservas ---

    public function indexReservas()
    {
        $reservas = Reserva::where('status', Reserva::STATUS_PENDENTE)
                            ->with('user')
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
        $pageTitle = 'Pré-Reservas Pendentes';
        return view('admin.reservas.index', compact('reservas', 'pageTitle'));
    }

    /**
     * Exibe o índice de reservas confirmadas, ordenadas por data crescente.
     */
    public function confirmed_index(Request $request)
    {
        $query = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                            ->where('is_fixed', false)
                            ->whereDate('date', '>=', Carbon::today()->toDateString())
                            ->with('user');

        $isOnlyMine = $request->get('only_mine') === 'true';

        if ($isOnlyMine) {
            $pageTitle = 'Minhas Reservas Manuais Confirmadas';
            $query->where('manager_id', Auth::id());
        } else {
            $pageTitle = 'Todas as Reservas Confirmadas (Próximos Agendamentos)';
        }

        $reservas = $query->orderBy('date', 'asc')
                            ->orderBy('start_time', 'asc')
                            ->paginate(15);

        return view('admin.reservas.confirmed_index', compact('reservas', 'pageTitle', 'isOnlyMine'));
    }

    public function showReserva(Reserva $reserva)
    {
        $reserva->load('user');
        return view('admin.reservas.show', compact('reserva'));
    }

    public function createReserva()
    {
        return redirect()->route('dashboard')
            ->with('warning', 'A criação manual foi simplificada! Por favor, use o calendário (slots verdes) na tela principal para agendamento rápido.');
    }

    // --- NOVO MÉTODO: Cancelamento Pontual de Reserva Recorrente (Exceção) ---
    public function cancelarReservaRecorrente(Reserva $reserva)
    {
        if (!$reserva->is_recurrent) {
            // ✅ CORREÇÃO: Retorno JSON para o AJAX
            return response()->json(['error' => 'Esta reserva não faz parte de uma série recorrente.'], 400);
        }

        $originalData = $reserva->only(['date', 'day_of_week', 'start_time', 'end_time', 'price']);

        $reserva->delete();

        try {
            Reserva::create([
                'date' => $originalData['date']->toDateString(),
                'day_of_week' => $originalData['day_of_week'],
                'start_time' => $originalData['start_time'],
                'end_time' => $originalData['end_time'],
                'price' => $originalData['price'],
                'client_name' => 'Slot Fixo de 1h',
                'client_contact' => 'N/A',
                'status' => Reserva::STATUS_CONFIRMADA,
                'is_fixed' => true,
            ]);

            // ✅ CORREÇÃO: Retorna JSON em vez de redirect
            return response()->json([
                'success' => true,
                'message' => "Cancelamento pontual realizado! O horário de {$reserva->client_name} no dia {$originalData['date']->format('d/m/Y')} foi liberado para novos agendamentos PONTUAIS.",
            ], 200);

        } catch (\Exception $e) {
            Log::error("Erro ao recriar slot fixo após cancelamento pontual: " . $e->getMessage());
            // ✅ CORREÇÃO: Retorno JSON
            return response()->json(['error' => 'Erro ao processar o cancelamento pontual.'], 500);
        }
    }

    // --- NOVO MÉTODO: Cancelamento de SÉRIE Recorrente ---
    public function cancelarSerieRecorrente(Reserva $reserva)
    {
        if (!$reserva->is_recurrent) {
             // ✅ CORREÇÃO: Retorno JSON para o AJAX
             return response()->json(['error' => 'Esta reserva não faz parte de uma série recorrente.'], 400);
        }

        $masterId = $reserva->recurrent_series_id ?? $reserva->id;
        $clientName = $reserva->client_name;
        $startTime = $reserva->start_time;

        $reservasToCancel = Reserva::where(function($query) use ($masterId) {
                $query->where('id', $masterId)
                      ->orWhere('recurrent_series_id', $masterId);
            })
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->get();

        $count = $reservasToCancel->count();

        DB::beginTransaction();
        try {
            $dates = $reservasToCancel->pluck('date');
            $firstSlot = $reservasToCancel->first();
            $start = $firstSlot->start_time;
            $end = $firstSlot->end_time;
            $dayOfWeek = $firstSlot->day_of_week;
            $price = $firstSlot->price;

            Reserva::whereIn('id', $reservasToCancel->pluck('id'))->delete();

            $dates->each(function($date) use ($dayOfWeek, $start, $end, $price) {
                Reserva::create([
                    'date' => $date->toDateString(),
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $start,
                    'end_time' => $end,
                    'price' => $price,
                    'client_name' => 'Slot Fixo de 1h',
                    'client_contact' => 'N/A',
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => true,
                ]);
            });

            DB::commit();

            // ✅ CORREÇÃO: Retorna JSON em vez de redirect
            return response()->json([
                'success' => true,
                'message' => "Série recorrente do cliente '{$clientName}' ({$start}h) cancelada com sucesso! {$count} slots foram liberados para agendamentos pontuais.",
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar série recorrente (ID Mestra: {$masterId}): " . $e->getMessage());
            // ✅ CORREÇÃO: Retorno JSON
            return response()->json(['error' => 'Erro ao cancelar a série recorrente.'], 500);
        }
    }


    public function confirmarReserva(Reserva $reserva)
    {
        $reservaController = app(\App\Http\Controllers\ReservaController::class);

        try {
            $dateString = $reserva->date->toDateString();
            $isFixed = $reserva->is_fixed;
            $ignoreId = $reserva->id;

            if ($reservaController->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                 return back()->with('error', 'Conflito detectado: Esta reserva não pode ser confirmada pois já existe outro agendamento (Pendente ou Confirmado) neste horário.');
            }

            $reserva->update([
                'status' => Reserva::STATUS_CONFIRMADA,
                'manager_id' => Auth::id(),
            ]);

            return redirect()->route('dashboard')
                             ->with('success', 'Reserva confirmada com sucesso! O horário está agora visível no calendário.');
        } catch (\Exception $e) {
            Log::error("Erro ao confirmar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao confirmar a reserva: ' . $e->getMessage());
        }
    }

    public function rejeitarReserva(Reserva $reserva)
    {
        try {
            $reserva->update([
                'status' => Reserva::STATUS_REJEITADA,
                'manager_id' => Auth::id(),
            ]);
            return redirect()->route('admin.reservas.index')
                                 ->with('success', 'Reserva rejeitada com sucesso e removida da lista de pendentes.');
        } catch (\Exception $e) {
            Log::error("Erro ao rejeitar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao rejeitar a reserva: ' . $e->getMessage());
        }
    }

    public function cancelarReserva(Reserva $reserva)
    {
        try {
            $reserva->update([
                'status' => Reserva::STATUS_CANCELADA,
                'manager_id' => Auth::id(),
            ]);
            return redirect()->route('admin.reservas.confirmed_index')
                                 ->with('success', 'Reserva cancelada com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao cancelar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao cancelar a reserva: ' . $e->getMessage());
        }
    }

    public function updateStatusReserva(Request $request, Reserva $reserva)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_PENDENTE,
                Reserva::STATUS_REJEITADA,
                Reserva::STATUS_CANCELADA,
            ])],
        ]);

        $newStatus = $validated['status'];
        $updateData = ['status' => $newStatus];

        if ($newStatus === Reserva::STATUS_CONFIRMADA) {
            $reservaController = app(\App\Http\Controllers\ReservaController::class);
            try {
                $dateString = $reserva->date->toDateString();
                $isFixed = $reserva->is_fixed;
                $ignoreId = $reserva->id;

                if ($reservaController->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                     return back()->with('error', 'Conflito detectado: Não é possível confirmar, pois já existe outro agendamento (Pendente ou Confirmado) neste horário.');
                }
                $updateData['manager_id'] = Auth::id();
            } catch (\Exception $e) {
                 return back()->with('error', 'Erro na verificação de conflito: ' . $e->getMessage());
            }
        }

        if (in_array($newStatus, [Reserva::STATUS_REJEITADA, Reserva::STATUS_CANCELADA]) && !isset($updateData['manager_id'])) {
            $updateData['manager_id'] = Auth::id();
        }

        try {
            $reserva->update($updateData);
            return redirect()->route('admin.reservas.show', $reserva)
                                 ->with('success', "Status da reserva alterado para '{$newStatus}' com sucesso.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao atualizar o status da reserva: ' . $e->getMessage());
        }
    }

    // O método 'destroyReserva' agora verifica se é recorrente e redireciona.
    public function destroyReserva(Reserva $reserva)
    {
        if ($reserva->is_recurrent) {
            // Se for recorrente, precisa ser cancelada pela lógica de série
            return back()->with('warning', 'Esta reserva faz parte de uma série recorrente. Use a opção "Cancelar Apenas Este Dia" ou "Cancelar Série Inteira" na tela de detalhes para gerenciar.');
        }

        try {
            $reserva->delete();
            return redirect()->route('admin.reservas.index')
                                 ->with('success', 'Reserva excluída permanentemente com sucesso.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir a reserva: ' . $e->getMessage());
        }
    }

    // --- Métodos de CRUD de Usuários (Mantidos) ---

    public function indexUsers()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'role' => ['required', 'string', Rule::in(['cliente', 'gestor'])],
        ]);
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);
        return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso!');
    }
}
