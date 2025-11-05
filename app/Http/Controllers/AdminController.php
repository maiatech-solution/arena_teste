<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reserva;
use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    /**
     * Exibe o dashboard principal do gestor.
     */
    public function dashboard()
    {
        // 1. Buscar todas as reservas confirmadas
        $reservas = Reserva::where('status', 'confirmed')
                            ->with('user')
                            ->get()
                            // CRÍTICO: Filtra quaisquer itens nulos/corrompidos na coleção antes do loop.
                            ->filter();

        // 2. Formatar as reservas para o FullCalendar
        $events = [];
        foreach ($reservas as $reserva) {

            // CORREÇÃO CRÍTICA DA DATA: Acessa o atributo de forma bruta.
            // O acesso direto ao array de atributos é a forma mais segura contra casting forçado.
            $bookingDate = $reserva->getAttributes()['date'];

            $startDateTimeString = $bookingDate . ' ' . $reserva->start_time;

            // Timezone Fix
            $start = Carbon::parse($startDateTimeString)->setTimezone('UTC');

            if ($reserva->end_time) {
                $endDateTimeString = $bookingDate . ' ' . $reserva->end_time;
                $end = Carbon::parse($endDateTimeString)->setTimezone('UTC');
            } else {
                $end = $start->copy()->addHour();
            }

            // CORRIGIDO: Usa optional() para lidar com $reserva->user nulo.
            $userName = optional($reserva->user)->name;
            $clientName = $userName ?? $reserva->client_name ?? 'Cliente Desconhecido';

            $title = 'Reservado: ' . $clientName;

            if (isset($reserva->price)) {
                $title .= ' - R$ ' . number_format($reserva->price, 2, ',', '.');
            }

            $events[] = [
                'id' => $reserva->id,
                'title' => $title,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'backgroundColor' => '#10B981',
                'borderColor' => '#059669',
            ];
        }

        $eventsJson = json_encode($events);
        $reservasPendentesCount = Reserva::where('status', 'pending')->count();

        return view('dashboard', compact('eventsJson', 'reservasPendentesCount'));
    }

    // --- Métodos de CRUD de Horários ---

    /**
     * Exibe o formulário de gerenciamento de horários.
     */
    public function indexHorarios()
    {
        $horarios = Horario::orderBy('day_of_week')->orderBy('start_time')->get();
        return view('admin.horarios.index', compact('horarios'));
    }

    /**
     * Exibe o formulário para criação de um novo horário.
     */
    public function createHorario()
    {
        return view('admin.horarios.create');
    }

    /**
     * Armazena um novo horário no banco de dados.
     */
    public function storeHorario(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_reservations' => 'required|integer|min:1',
        ]);

        Horario::create($request->all());

        return redirect()->route('admin.horarios.index')->with('success', 'Horário criado com sucesso!');
    }

    /**
     * Exibe o formulário para edição de um horário.
     */
    public function editHorario(Horario $horario)
    {
        return view('admin.horarios.edit', compact('horario'));
    }

    /**
     * Atualiza um horário no banco de dados.
     */
    public function updateHorario(Request $request, Horario $horario)
    {
        $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_reservations' => 'required|integer|min:1',
        ]);

        $horario->update($request->all());

        return redirect()->route('admin.horarios.index')->with('success', 'Horário atualizado com sucesso!');
    }

    /**
     * Remove um horário do banco de dados.
     */
    public function destroyHorario(Horario $horario)
    {
        $horario->delete();

        return redirect()->route('admin.horarios.index')->with('success', 'Horário excluído com sucesso!');
    }

    // --- Métodos de CRUD de Reservas ---

    /**
     * Exibe a lista de reservas (Pré-reservas/Pendentes).
     */
    public function indexReservas()
    {
        // Busca por status 'pending'
        $reservas = Reserva::where('status', 'pending')
                            ->with('user')
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);

        return view('admin.reservas.index', compact('reservas'));
    }

    /**
     * Confirma uma reserva (muda status para 'confirmed').
     */
    public function confirmarReserva(Reserva $reserva)
    {
        try {
            $reserva->status = 'confirmed';
            $reserva->save();

            return redirect()->route('dashboard')
                             ->with('success', 'Reserva confirmada com sucesso! O horário está agora visível no calendário.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao confirmar a reserva: ' . $e->getMessage());
        }
    }

    /**
     * Rejeita uma reserva pendente (muda status para 'rejected').
     */
    public final function rejeitarReserva(Reserva $reserva)
    {
        try {
            $reserva->status = 'rejected';
            $reserva->save();

            return redirect()->route('admin.reservas.index')
                             ->with('success', 'Reserva rejeitada com sucesso e removida da lista de pendentes.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao rejeitar a reserva: ' . $e->getMessage());
        }
    }

    /**
     * Cancela uma reserva (muda status para 'cancelled').
     */
    public function cancelarReserva(Reserva $reserva)
    {
        try {
            $reserva->status = 'cancelled';
            $reserva->save();

            return redirect()->route('admin.reservas.index')
                             ->with('success', 'Reserva cancelada com sucesso.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao cancelar a reserva: ' . $e->getMessage());
        }
    }

    // --- Métodos de CRUD de Usuários ---

    /**
     * Exibe a lista de usuários.
     */
    public function indexUsers()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Exibe o formulário de criação de novo usuário.
     */
    public function createUser()
    {
        return view('admin.users.create');
    }

    /**
     * Armazena o novo usuário (exclusivo para administradores).
     */
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
