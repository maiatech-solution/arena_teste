<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule; // Usamos o Model Schedule para gerenciar horários fixos
use Illuminate\Http\Request;
use Carbon\Carbon;

// ✅ O NOME DA CLASSE AGORA CORRESPONDE AO NOME DO ARQUIVO
class HorarioController extends Controller
{
    /**
     * Exibe a lista de horários fixos.
     */
    public function index()
    {
        // Agrupa os horários por dia da semana para facilitar a visualização
        $schedules = Schedule::orderBy('day_of_week')
                             ->orderBy('start_time')
                             ->get()
                             ->groupBy('day_of_week');

        // Mapeamento dos dias da semana (de 1 a 7) para nomes em português
        $dayNames = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        return view('admin.horarios.index', compact('schedules', 'dayNames'));
    }

    /**
     * Armazena um novo horário fixo.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:1|max:7',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0.01',
            'is_active' => 'boolean',
        ]);

        Schedule::create($validated);

        return redirect()->route('admin.horarios.index')->with('success', 'Horário fixo criado com sucesso!');
    }

    /**
     * Atualiza o status (ativo/inativo) de um horário.
     */
    public function update_status(Schedule $horario)
    {
        // Alterna o status
        $horario->is_active = !$horario->is_active;
        $horario->save();

        $status = $horario->is_active ? 'ativado' : 'desativado';

        return redirect()->route('admin.horarios.index')->with('success', "Horário {$horario->start_time} foi {$status} com sucesso.");
    }

    /**
     * Remove um horário fixo.
     */
    public function destroy(Schedule $horario)
    {
        $horario->delete();

        return redirect()->route('admin.horarios.index')->with('success', 'Horário fixo removido com sucesso.');
    }
}
