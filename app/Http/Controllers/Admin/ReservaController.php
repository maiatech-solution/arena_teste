<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva; // ✅ CRUCIAL: O Model de Reserva precisa ser importado
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReservaController extends Controller
{
    /**
     * Exibe a lista de todas as reservas para o administrador.
     */
    public function index()
    {
        // 1. Busca os dados: Esta linha CRIA a variável $reservas
        $reservas = Reserva::orderBy('date', 'asc')
                           ->orderBy('start_time', 'asc')
                           ->get();

        // 2. Passa para a View: Esta linha DEVE passar a variável
        // O compact('reservas') é um atalho para ['reservas' => $reservas]
        return view('admin.reservas.index', compact('reservas'));
    }

    /**
     * Confirma uma reserva, alterando o status para 'confirmed'.
     */
    public function confirm(Reserva $reserva)
    {
        // Verifica se a reserva não está rejeitada para evitar confirmação dupla
        if ($reserva->status !== 'rejected') {
            $reserva->status = 'confirmed';
            $reserva->save();

            return redirect()->route('admin.reservas.index')->with('success', 'Reserva de ' . $reserva->client_name . ' confirmada com sucesso!');
        }

        return redirect()->route('admin.reservas.index')->with('error', 'A reserva já havia sido rejeitada.');
    }

    /**
     * Rejeita uma reserva, alterando o status para 'rejected'.
     */
    public function reject(Reserva $reserva)
    {
        $reserva->status = 'rejected';
        $reserva->save();

        return redirect()->route('admin.reservas.index')->with('success', 'Reserva de ' . $reserva->client_name . ' rejeitada com sucesso!');
    }
}
