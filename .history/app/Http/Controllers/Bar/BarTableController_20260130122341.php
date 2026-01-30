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
     * Abre a Comanda
     */
    public function open($id)
    {
        $table = BarTable::findOrFail($id);

        if ($table->status !== 'available') {
            return back()->with('error', 'Esta mesa não está disponível.');
        }

        try {
            DB::transaction(function () use ($table) {
                BarOrder::create([
                    'bar_table_id' => $table->id,
                    'user_id' => auth()->id(),
                    'status' => 'open',
                    'total_value' => 0.00
                ]);

                $table->status = 'occupied';
                $table->save();
            });

            return back()->with('success', "Mesa {$table->identifier} aberta!");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao abrir mesa: ' . $e->getMessage());
        }
    }

    /**
     * Exibe os itens da mesa
     */
    public function showOrder($id)
    {
        $table = BarTable::findOrFail($id);

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

                $item = BarOrderItem::where('bar_order_id', $order->id)
                    ->where('bar_product_id', $product->id)
                    ->first();

                if ($item) {
                    $item->increment('quantity', $qty);
                    $item->update(['subtotal' => $item->quantity * $product->sale_price]);
                } else {
                    BarOrderItem::create([
                        'bar_order_id'   => $order->id,
                        'bar_product_id' => $product->id,
                        'quantity'       => $qty,
                        'unit_price'     => $product->sale_price,
                        'subtotal'       => $qty * $product->sale_price
                    ]);
                }

                $order->update(['total_value' => $order->items()->sum('subtotal')]);

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
     * Remove item da comanda
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
                $order->update(['total_value' => $order->items()->sum('subtotal')]);
            });
            return back()->with('success', 'Item removido!');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao remover item.');
        }
    }

    /**
     * 🏁 FINALIZAR MESA (FECHAMENTO ESTILO PDV)
     */
    public function closeOrder(Request $request, $id)
    {
        $table = BarTable::findOrFail($id);
        $order = BarOrder::where('bar_table_id', $table->id)
            ->where('status', 'open')
            ->firstOrFail();

        try {
            DB::transaction(function () use ($table, $order, $request) {
                // Decodifica os pagamentos enviados pelo Modal
                $pagamentos = json_decode($request->pagamentos, true);

                // 1. Atualiza os dados da Comanda para 'paid'
                $order->update([
                    'status' => 'paid',
                    'closed_at' => now(),
                    // Se você adicionou estas colunas na migration de bar_orders:
                    // 'customer_name' => $request->customer_name,
                    // 'customer_phone' => $request->customer_phone,
                ]);

                // 2. Libera a Mesa Física para novos clientes
                $table->update(['status' => 'available']);

                // 3. Opcional: Registrar os pagamentos em uma tabela de BarCash/Vendas
                // foreach($pagamentos as $pg) { ... }
            });

            // Lógica de Impressão
            if ($request->print_coupon == "1") {
                // Aqui você redirecionaria para a rota de impressão passando o ID da Ordem
                // return redirect()->route('bar.print', $order->id);
                return redirect()->route('bar.tables.index')->with('success', "Mesa {$table->identifier} finalizada e enviada para impressão!");
            }

            return redirect()->route('bar.tables.index')->with('success', "Mesa {$table->identifier} finalizada com sucesso!");

        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao finalizar mesa: ' . $e->getMessage());
        }
    }
}
