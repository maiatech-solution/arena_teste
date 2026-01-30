<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarTable;
use App\Models\Bar\BarOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarTableController extends Controller
{
    /**
     * Exibe o Mapa de Mesas
     */
    public function index()
    {
        $tables = BarTable::orderBy('identifier', 'asc')->get();

        // Stats para o Dashboard (opcional)
        $stats = [
            'mesas_abertas' => BarTable::where('status', 'ocupada')->count()
        ];

        return view('bar.tables.index', compact('tables', 'stats'));
    }

    /**
     * Sincroniza a quantidade de mesas (Cria ou remove)
     */
    public function sync(Request $request)
    {
        $request->validate(['total_tables' => 'required|integer|min:1|max:100']);

        $totalDesejado = (int) $request->total_tables;
        $atual = BarTable::count();

        if ($totalDesejado > $atual) {
            for ($i = $atual + 1; $i <= $totalDesejado; $i++) {
                BarTable::create([
                    'identifier' => $i,
                    'status' => 'livre'
                ]);
            }
        } elseif ($totalDesejado < $atual) {
            // Remove apenas as mesas com maior número que estejam LIVRES
            BarTable::where('identifier', '>', $totalDesejado)
                    ->where('status', 'livre')
                    ->delete();
        }

        return back()->with('success', 'Layout do salão atualizado!');
    }

    /**
     * Ativa ou Desativa uma mesa (🚫/✅)
     */
    public function toggleStatus($id)
    {
        $table = BarTable::findOrFail($id);

        if ($table->status === 'ocupada') {
            return back()->with('error', 'Não é possível desativar uma mesa com comanda aberta!');
        }

        $table->status = ($table->status === 'desativada') ? 'livre' : 'desativada';
        $table->save();

        return back();
    }

    /**
     * Abre a Comanda da Mesa
     */
    public function openOrder($id)
    {
        $table = BarTable::findOrFail($id);

        if ($table->status !== 'livre') {
            return back()->with('error', 'Esta mesa não está disponível para abertura.');
        }

        try {
            DB::transaction(function () use ($table) {
                // Cria o "envelope" da comanda
                BarOrder::create([
                    'bar_table_id' => $table->id,
                    'user_id' => auth()->id(),
                    'status' => 'aberto',
                    'total_value' => 0
                ]);

                // Ocupa a mesa física
                $table->update(['status' => 'ocupada']);
            });

            return back()->with('success', "Mesa {$table->identifier} aberta!");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao abrir mesa: ' . $e->getMessage());
        }
    }
}
