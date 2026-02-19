<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarOrder;
use App\Models\Bar\BarStockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BarOrderController extends Controller
{
    // Método para abrir a tela com a lista de vendas
    public function indexVendas()
    {
        $vendas = BarOrder::with(['items.product', 'user'])
            ->whereIn('status', ['paid', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('bar.vendas.index', compact('vendas'));
    }

    // Método que faz o cancelamento real
    public function cancelarVenda(Request $request, BarOrder $order)
    {
        // Valida se a senha digitada é de um GESTOR
        $supervisor = User::where('email', $request->supervisor_email)->first();

        if (!$supervisor || !Hash::check($request->supervisor_password, $supervisor->password) ||
            !in_array($supervisor->role, ['admin', 'gestor'])) {
            return back()->with('error', '❌ Autorização negada: Apenas um GESTOR pode cancelar vendas.');
        }

        // Devolve os itens ao estoque e cancela a venda
        DB::transaction(function () use ($order, $supervisor, $request) {
            foreach ($order->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);

                BarStockMovement::create([
                    'product_id' => $item->product_id,
                    'user_id' => auth()->id(),
                    'type' => 'input',
                    'quantity' => $item->quantity,
                    'description' => "Estorno: Venda #{$order->id} cancelada por {$supervisor->name}. Motivo: {$request->reason}",
                ]);
            }
            $order->update(['status' => 'cancelled']);
        });

        return back()->with('success', "✅ Venda #{$order->id} cancelada!");
    }
}
