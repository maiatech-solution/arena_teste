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
    // Lista o histórico de vendas (Pagas e Canceladas)
    public function indexVendas()
    {
        $vendas = BarOrder::with(['items.product', 'user'])
            ->whereIn('status', ['paid', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('bar.vendas.index', compact('vendas'));
    }

    // Processa o cancelamento com estorno de estoque
    public function cancelarVenda(Request $request, BarOrder $order)
    {
        // 1. Validar Supervisor (Senha de Gestor/Admin)
        $supervisor = User::where('email', $request->supervisor_email)->first();

        if (!$supervisor || !Hash::check($request->supervisor_password, $supervisor->password) ||
            !in_array($supervisor->role, ['admin', 'gestor'])) {
            return back()->with('error', '❌ Autorização negada: Senha de gestor inválida.');
        }

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Esta venda já está cancelada.');
        }

        try {
            DB::transaction(function () use ($order, $supervisor, $request) {
                // 2. Devolver itens ao estoque
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock_quantity', $item->quantity);

                        // Registrar a entrada no estoque por cancelamento
                        BarStockMovement::create([
                            'product_id' => $item->product_id,
                            'user_id' => auth()->id(),
                            'type' => 'input',
                            'quantity' => $item->quantity,
                            'description' => "CANCELAMENTO: Venda #{$order->id} anulada por {$supervisor->name}. Motivo: " . ($request->reason ?? 'Desistência'),
                        ]);
                    }
                }

                // 3. Atualizar status da venda
                $order->update(['status' => 'cancelled']);
            });

            return back()->with('success', "✅ Venda #{$order->id} cancelada com sucesso!");

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao cancelar: ' . $e->getMessage());
        }
    }
}
