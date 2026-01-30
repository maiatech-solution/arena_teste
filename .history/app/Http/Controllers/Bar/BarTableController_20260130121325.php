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
    /**
     * Exibe o Mapa de Mesas
     */
    public function index()
    {
        $tables = BarTable::orderBy('identifier', 'asc')->get();
        return view('bar.tables.index', compact('tables'));
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
                    'status' => 'available' // Status padrão no seu banco
                ]);
            }
        } elseif ($totalDesejado < $atual) {
            // Remove apenas as mesas de maior número que estejam LIVRES
            BarTable::where('identifier', '>', $totalDesejado)
                ->where('status', 'available')
                ->delete();
        }

        return back()->with('success', 'Salão atualizado!');
    }

    /**
     * Ativa/Desativa Mesa (Usa 'reserved' como bloqueio)
     */
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

    /**
     * Abre a Comanda (Muda para 'occupied' e cria BarOrder como 'open')
     */
    public function open($id)
    {
        $table = BarTable::findOrFail($id);

        if ($table->status !== 'available') {
            return back()->with('error', 'Esta mesa não está disponível.');
        }

        try {
            DB::transaction(function () use ($table) {
                // 1. Cria a comanda com status 'open' (conforme seu banco)
                BarOrder::create([
                    'bar_table_id' => $table->id,
                    'user_id' => auth()->id(),
                    'status' => 'open',
                    'total_value' => 0.00
                ]);

                // 2. Muda o status da mesa física
                $table->status = 'occupied';
                $table->save();
            });

            return back()->with('success', "Mesa {$table->identifier} aberta!");
        } catch (\Exception $e) {
            // Caso dê erro de ENUM ou banco, o catch captura aqui
            return back()->with('error', 'Erro ao abrir mesa: ' . $e->getMessage());
        }
    }

    /**
     * Exibe os itens e permite lançar produtos na mesa
     */
    public function showOrder($id)
    {
        $table = BarTable::findOrFail($id);

        // Busca a comanda que está 'open' para esta mesa
        $order = BarOrder::with('items.product')
            ->where('bar_table_id', $table->id)
            ->where('status', 'open')
            ->firstOrFail();

        $products = BarProduct::orderBy('name')->get();
        $categories = BarCategory::all();

        return view('bar.tables.show', compact('table', 'order', 'products', 'categories'));
    }

    /**
     * Lança item na comanda via AJAX
     */
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
                    // Atualiza o subtotal (quantity * unit_price)
                    $item->update(['subtotal' => $item->quantity * $product->sale_price]);
                } else {
                    // 🚀 AJUSTE AQUI: Usando 'unit_price' em vez de 'price_at_sale'
                    BarOrderItem::create([
                        'bar_order_id'   => $order->id,
                        'bar_product_id' => $product->id,
                        'quantity'       => $qty,
                        'unit_price'     => $product->sale_price, // Nome exato da sua coluna
                        'subtotal'       => $qty * $product->sale_price
                    ]);
                }

                // Atualiza o total da comanda pai
                $order->update(['total_value' => $order->items()->sum('subtotal')]);

                // Baixa no estoque
                if ($product->manage_stock) {
                    $product->decrement('stock_quantity', $qty);
                }

                return response()->json(['success' => true]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Estorna um item lançado por erro
     */
    public function removeItem($itemId)
    {
        try {
            DB::transaction(function () use ($itemId) {
                $item = BarOrderItem::findOrFail($itemId);
                $order = $item->order;
                $product = $item->product;

                if ($product->manage_stock) {
                    $product->increment('stock_quantity', $item->quantity);
                }

                $item->delete();

                // Recalcula o total da mesa
                $order->update(['total_value' => $order->items()->sum('subtotal')]);
            });
            return back()->with('success', 'Item removido e estoque estornado!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao remover item.');
        }
    }
}
