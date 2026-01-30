namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarTable;
use Illuminate\Http\Request;

class BarTableController extends Controller
{
    public function index()
    {
        $tables = BarTable::orderBy('identifier', 'asc')->get();
        return view('bar.tables.index', compact('tables'));
    }

    // 🔄 Sincroniza a quantidade de mesas (escolher quantas mesas terá)
    public function sync(Request $request)
    {
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
            // Remove as de maior número, mas apenas se estiverem LIVRES
            BarTable::where('identifier', '>', $totalDesejado)
                    ->where('status', 'livre')
                    ->delete();
        }

        return back()->with('success', 'Quantidade de mesas atualizada!');
    }

    // 🚫 Desabilitar/Habilitar Mesa
    public function toggleStatus($id)
    {
        $table = BarTable::findOrFail($id);

        if ($table->status === 'ocupada') {
            return back()->with('error', 'Não pode desativar uma mesa ocupada!');
        }

        $table->status = ($table->status === 'desativada') ? 'livre' : 'desativada';
        $table->save();

        return back();
    }
}
