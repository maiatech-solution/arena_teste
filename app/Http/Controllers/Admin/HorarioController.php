<?php

namespace App\Http\Controllers\Admin;

use App\Models\Horario;
use App\Models\Reserva; // Importando o modelo de Reservas de Clientes
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;

class HorarioController extends Controller
{
    // Removido $dayNames pois o recurso recorrente foi descontinuado

    /**
     * Exibe o formulário e a lista de horários (agora apenas avulsos).
     */
    public function index()
    {
        // Busca todos os horários cadastrados (agora todos são avulsos)
        $availableSlots = Horario::orderBy('date', 'asc')
                                 ->orderBy('start_time', 'asc')
                                 ->get();

        // Retorna a view com os dados necessários
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

        // 1. VERIFICAÇÃO DE CONFLITO COM OUTROS SLOTS DE DISPONIBILIDADE (Horario)
        // Checa conflito apenas com outros horários de Horario ATIVOS na mesma data.
        $existingHorario = Horario::where('date', $date)
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
        // Nenhum slot pode sobrepor qualquer reserva (fixa ou pontual) naquele dia.
        $startDatetime = Carbon::parse($date . ' ' . $startTime);
        $endDatetime = Carbon::parse($date . ' ' . $endTime);

        $conflictReserva = Reserva::whereDate('start_time', $date)
            ->where(function ($query) use ($startDatetime, $endDatetime) {
                // Verifica sobreposição de períodos de tempo (datetimes)
                $query->where('start_time', '<', $endDatetime)
                      ->where('end_time', '>', $startDatetime);
            })
            ->exists();

        if ($conflictReserva) {
            return back()->withInput()->withErrors(['time_conflict' => 'Conflito! Já existe uma reserva de cliente (fixa ou avulsa) cobrindo parte deste horário na data selecionada.']);
        }

        // Cria o slot de disponibilidade avulso
        Horario::create([
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'price' => $validated['price'],
            'is_active' => true,
            'day_of_week' => null, // Mantém nulo
        ]);

        $message = 'Slot Avulso adicionado com sucesso para ' . Carbon::parse($validated['date'])->format('d/m/Y') . '!';

        // ROTA PADRONIZADA: admin.horarios.index
        return redirect()->route('admin.horarios.index')->with('success', $message);
    }

    /**
     * Exibe o formulário para editar um horário existente.
     */
    public function edit(Horario $horario)
    {
        // Impede a edição de antigos horários recorrentes
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
    public function update(Request $request, Horario $horario)
    {
        // Impede a atualização de antigos horários recorrentes
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

        // 1. Verifica conflito com outra disponibilidade avulsa na mesma data (Horario)
        $existingHorario = Horario::where('id', '!=', $horario->id) // Ignora o registro atual
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
        $startDatetime = Carbon::parse($date . ' ' . $startTime);
        $endDatetime = Carbon::parse($date . ' ' . $endTime);

        $conflictReserva = Reserva::whereDate('start_time', $date)
            ->where(function ($query) use ($startDatetime, $endDatetime) {
                $query->where('start_time', '<', $endDatetime)
                      ->where('end_time', '>', $startDatetime);
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

        // ROTA PADRONIZADA: admin.horarios.index
        return redirect()->route('admin.horarios.index')->with('success', $message);
    }

    /**
     * Remove um horário avulso.
     */
    public function destroy(Horario $horario)
    {
        // Bloqueia a exclusão de antigos horários recorrentes
        if ($horario->day_of_week !== null) {
            return back()->with('error', 'Este é um horário fixo recorrente e não pode ser excluído diretamente por esta rota. Use o banco de dados se precisar removê-lo.');
        }

        $tipo = 'Avulso';
        $identificador = Carbon::parse($horario->date)->format('d/m/Y');

        $timeSlot = Carbon::parse($horario->start_time)->format('H:i') . ' - ' . Carbon::parse($horario->end_time)->format('H:i');
        $fullIdentifier = "{$identificador} das {$timeSlot}";

        try {
            $horario->delete();
            // ROTA PADRONIZADA: admin.horarios.index
            return redirect()->route('admin.horarios.index')->with('success', "Horário {$tipo} ({$fullIdentifier}) excluído com sucesso.");
        } catch (QueryException $e) {
            // Tratamento de exceção de chave estrangeira (se houver reservas dependentes)
            return back()->with('error', "Não foi possível excluir o horário {$tipo} ({$fullIdentifier}). Ele pode ter reservas associadas ou outras dependências de banco de dados. Tente primeiro desativá-lo.");
        } catch (\Exception $e) {
             // Captura outras exceções genéricas
             return back()->with('error', 'Erro desconhecido ao excluir o horário.');
        }
    }

    // O método updateStatus foi removido, pois era específico para horários recorrentes.
}
