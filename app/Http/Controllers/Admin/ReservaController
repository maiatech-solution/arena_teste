<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Necessário se for usar o DB::table em qualquer função
use Carbon\Carbon; // Mantido para caso de uso futuro

class ReservaController extends Controller
{
    /**
     * Exibe a lista de reservas que estão pendentes de confirmação.
     * Filtra o status por 'pending'.
     */
    public function index()
    {
        // Busca todas as reservas com status 'pending'
        $reservas = Reserva::with('user', 'quadra')
                            ->where('status', 'pending')
                            ->orderBy('date', 'asc')
                            ->orderBy('start_time', 'asc')
                            ->get();

        /*
        // --- BLOC DE DEBUG CRÍTICO ---
        // Se a tela de admin estiver vazia, COMENTE as linhas 24-28
        // e DESCOMENTE o bloco abaixo (linhas 31-33) para descobrir o status real no seu DB.
        // O código de debug deve ser colocado no método que sua rota está chamando (indexReservasPendentes no AdminController)!

        // $reservas_raw = DB::table('reservas')->get();
        // dd($reservas_raw->pluck('status', 'id')->toArray());

        // --- FIM DO BLOC DE DEBUG CRÍTICO ---
        */

        return view('admin.reservas.index', compact('reservas'));
    }

    /**
     * Confirma uma reserva, alterando seu status para 'confirmed'.
     * O nome do método está alinhado com a rota 'admin.reservas.confirmar'.
     */
    public function confirmar(Reserva $reserva)
    {
        // Verifica se a reserva está realmente pendente antes de confirmar
        if ($reserva->status === 'pending') {
            // Usa o método update para uma operação mais limpa
            $reserva->update(['status' => 'confirmed']);

            $clientName = $reserva->user->name ?? 'Cliente';

            return redirect()->route('admin.reservas.index')->with('success', "Reserva de {$clientName} confirmada com sucesso!");
        }

        return redirect()->route('admin.reservas.index')->with('error', 'A reserva não está em status pendente e não pode ser confirmada.');
    }

    /**
     * Cancela (rejeita) uma reserva, alterando seu status para 'cancelled'.
     * O nome do método está alinhado com a rota 'admin.reservas.cancelar'.
     */
    public function cancelar(Reserva $reserva)
    {
        // Verifica se a reserva está realmente pendente antes de cancelar
        if ($reserva->status === 'pending') {
            // Usa o método update para uma operação mais limpa
            $reserva->update(['status' => 'cancelled']);

            $clientName = $reserva->user->name ?? 'Cliente';

            return redirect()->route('admin.reservas.index')->with('success', "Reserva de {$clientName} cancelada com sucesso!");
        }

        return redirect()->route('admin.reservas.index')->with('error', 'A reserva não está em status pendente e não pode ser cancelada.');
    }
}
