<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarTable;
use App\Models\Bar\BarOrder;
use App\Models\Bar\BarOrderItem;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarCategory;
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
                    'status' => 'available'
                ]);
            }
        } elseif ($totalDesejado < $atual) {
            BarTable::where('identifier', '>', $totalDesejado)
                ->where('status', 'available')
                ->delete();
        }

        return back()->with('success', 'Salão atualizado!');
    }

    // 🚫 Alternar Status (Available / Reserved)
    public function toggleStatus($id)
    {
        $table = BarTable::findOrFail($id);

        if ($table->status === 'occupied') {
            return back()->with('error', 'Mesa ocupada não pode ser alterada!');
        }

        $table->status = ($table->status === 'reserved') ? 'available' : 'reserved';
        $table->save();

        return back();
    }

    // 🍻 Abrir Comanda
    // No seu BarTableController.php
    public function open($id)
    {
        // O sistema vai parar aqui e mostrar o ID na tela
        dd("O formulário enviou o comando para a mesa ID: " . $id);
    }

    // 📝 Exibir a Comanda da Mesa
    public function showOrder($id)
    {
        $table = BarTable::findOrFail($id);

        $order = BarOrder::with('items.product')
            ->where('bar_table_id', $table->id)
            ->where('status', 'aberto')
            ->firstOrFail();

        $products = BarProduct::orderBy('name')->get();
        $categories = BarCategory::all();

        return view('bar.tables.show', compact('table', 'order', 'products', 'categories'));
    }

    // ➕ Lançar Item na Comanda (Ajax)
    public function addItem(Request $request, $orderId)
    {
        try {
            return DB::transaction(function () use ($request, $orderId) {
                $order = BarOrder::findOrFail($orderId);
                $product = BarProduct::findOrFail($request->product_id);
                $qty = $request->quantity ?? 1;

                // Verifica se o item já existe na comanda para somar
                $item = BarOrderItem::where('bar_order_id', $order->id)
                    ->where('bar_product_id', $product->id)
                    ->first();

                if ($item) {
                    $item->increment('quantity', $qty);
                    $item->update(['subtotal' => $item->quantity * $product->sale_price]);
                } else {
                    BarOrderItem::create([
                        'bar_order_id' => $order->id,
                        'bar_product_id' => $product->id,
                        'quantity' => $qty,
                        'price_at_sale' => $product->sale_price,
                        'subtotal' => $qty * $product->sale_price
                    ]);
                }

                // Atualiza o total da comanda
                $order->update(['total_value' => $order->items()->sum('subtotal')]);

                // Baixa no estoque (Se configurado para gerenciar)
                if ($product->manage_stock) {
                    $product->decrement('stock_quantity', $qty);
                }

                return response()->json(['success' => true]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ❌ Remover/Estornar Item (Opcional)
    public function removeItem($itemId)
    {
        try {
            DB::transaction(function () use ($itemId) {
                $item = BarOrderItem::findOrFail($itemId);
                $order = $item->order;
                $product = $item->product;

                // Devolve ao estoque
                if ($product->manage_stock) {
                    $product->increment('stock_quantity', $item->quantity);
                }

                $item->delete();

                // Recalcula total
                $order->update(['total_value' => $order->items()->sum('subtotal')]);
            });
            return back()->with('success', 'Item removido e estoque estornado!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao remover item.');
        }
    }
}
