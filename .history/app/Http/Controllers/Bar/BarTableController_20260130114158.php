<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarTable;
use App\Models\Bar\BarOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarTableController extends Controller
{
    public function index()
    {
        $tables = BarTable::orderBy('identifier', 'asc')->get();
        return view('bar.tables.index', compact('tables'));
    }

    // 🔄 Sincroniza a quantidade de mesas
    public function sync(Request $request)
    {
        $request->validate(['total_tables' => 'required|integer|min:1|max:100']);

        $totalDesejado = (int) $request->total_tables;
        $atual = BarTable::count();

        if ($totalDesejado > $atual) {
            for ($i = $atual + 1; $i <= $totalDesejado; $i++) {
                BarTable::create([
                    'identifier' => $i,
                    'status' => 'available' // ⬅️ Ajustado para o seu banco
                ]);
            }
        } elseif ($totalDesejado < $atual) {
            // Remove apenas as mesas de maior número que estejam disponíveis
            BarTable::where('identifier', '>', $totalDesejado)
                    ->where('status', 'available')
                    ->delete();
        }

        return back()->with('success', 'Salão atualizado!');
    }

    // 🚫 Alternar Status (Disponível / Reservada)
    public function toggleStatus($id)
    {
        $table = BarTable::findOrFail($id);

        if ($table->status === 'occupied') {
            return back()->with('error', 'Mesa ocupada não pode ser alterada!');
        }

        // Como seu banco não tem 'desativada', vamos usar o 'reserved' para bloquear a mesa
        $table->status = ($table->status === 'reserved') ? 'available' : 'reserved';
        $table->save();

        return back();
    }

    // 🍻 Abrir Comanda
    public function openOrder($id)
    {
        $table = BarTable::findOrFail($id);

        if ($table->status !== 'available') {
            return back()->with('error', 'Mesa não disponível.');
        }

        try {
            DB::transaction(function () use ($table) {
                BarOrder::create([
                    'bar_table_id' => $table->id,
                    'user_id' => auth()->id(),
                    'status' => 'aberto',
                    'total_value' => 0
                ]);

                $table->update(['status' => 'occupied']); // ⬅️ Ajustado para o seu banco
            });

            return back()->with('success', "Mesa {$table->identifier} aberta!");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro: ' . $e->getMessage());
        }
    }
}
