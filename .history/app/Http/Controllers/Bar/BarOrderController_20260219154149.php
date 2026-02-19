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
    public function indexVendas(Request $request)
    {
        // Mudamos de BarOrder para BarSale (que é o que seu PDV usa)
        $query = \App\Models\Bar\BarSale::with(['items.product', 'user', 'cashSession']);

        // Filtro por ID
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        // Filtro por Status (Atenção: seu PDV usa 'pago' em português)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por Data
        if ($request->filled('date')) {
            $query->whereDate('updated_at', $request->date);
        }

        // Ordenação por ID DESC para a #12 aparecer no topo
        $vendas = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('bar.vendas.index', compact('vendas'));
    }

    // Processa o cancelamento com estorno de estoque
    public function cancelarVenda(Request $request, \App\Models\Bar\BarSale $order) // 👈 Trocado para BarSale
    {
        // 1. Validar Supervisor (Senha de Gestor/Admin)
        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        if (
            !$supervisor || !\Illuminate\Support\Facades\Hash::check($request->supervisor_password, $supervisor->password) ||
            !in_array($supervisor->role, ['admin', 'gestor'])
        ) {
            return back()->with('error', '❌ Autorização negada: Senha de gestor inválida.');
        }

        // 2. 🔒 TRAVA DE CAIXA FECHADO
        // Buscamos a sessão de caixa atrelada a esta venda (usando bar_cash_session_id da bar_sales)
        $caixa = \App\Models\Bar\BarCashSession::find($order->bar_cash_session_id);

        // Se o caixa não for encontrado ou já estiver fechado, barramos o cancelamento
        if (!$caixa || $caixa->status !== 'open') {
            return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Esta venda pertence a um caixa que já foi encerrado.');
        }

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Esta venda já está cancelada.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $supervisor, $request) {
                // 3. Devolver itens ao estoque
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock_quantity', $item->quantity);

                        // Registrar a entrada no estoque por cancelamento
                        // ⚠️ Ajuste os nomes das colunas conforme sua migration de BarStockMovement
                        \App\Models\Bar\BarStockMovement::create([
                            'bar_product_id' => $item->bar_product_id, // Verifique se é bar_product_id ou product_id
                            'user_id' => auth()->id(),
                            'type' => 'input',
                            'quantity' => $item->quantity,
                            'description' => "CANCELAMENTO: Venda #{$order->id} anulada por {$supervisor->name}. Motivo: " . ($request->reason ?? 'Desistência'),
                        ]);
                    }
                }

                // 4. Atualizar status da venda para 'cancelled'
                $order->update(['status' => 'cancelled']);
            });

            return back()->with('success', "✅ Venda #{$order->id} cancelada com sucesso!");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao cancelar: ' . $e->getMessage());
        }
    }
}
