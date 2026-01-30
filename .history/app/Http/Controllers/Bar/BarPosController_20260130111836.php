<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarSale;
use App\Models\Bar\BarSaleItem;
use App\Models\Bar\BarCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarPosController extends Controller
{
    public function index()
    {
        // Pegamos produtos ativos que:
        // 1. Tenham estoque disponível (> 0)
        // 2. OU que não dependam de controle de estoque (manage_stock = false)
        $products = BarProduct::where('is_active', true)
            ->where(function ($query) {
                $query->where('stock_quantity', '>', 0)
                    ->orWhere('manage_stock', false);
            })
            ->orderBy('name')
            ->get();

        $categories = BarCategory::orderBy('name')->get();

        return view('bar.pos.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        // 1. Ajuste na Validação: agora aceitamos a lista de pagamentos
        $request->validate([
            'items' => 'required|array',
            'payments' => 'nullable|array', // Nova lista de pagamentos
            'payment_method' => 'nullable|string', // Para manter compatibilidade
            'total_value' => 'required|numeric'
        ]);

        try {
            return DB::transaction(function () use ($request) {

                // Determinar o método de pagamento para salvar no banco
                // Se houver múltiplos, salvamos como 'misto', se não, pegamos o único.
                $metodoFinal = 'misto';
                if ($request->payments && count($request->payments) === 1) {
                    $metodoFinal = $request->payments[0]['method'];
                } elseif ($request->payment_method) {
                    $metodoFinal = $request->payment_method;
                }

                // 1. Criar a Venda
                $sale = BarSale::create([
                    'user_id' => auth()->id(),
                    'total_value' => $request->total_value,
                    'payment_method' => $metodoFinal,
                    'status' => 'pago',
                    // Se sua tabela tiver uma coluna JSON para detalhes, você pode salvar assim:
                    // 'payment_details' => json_encode($request->payments)
                ]);

                // 2. Processar cada item
                foreach ($request->items as $item) {
                    $product = BarProduct::findOrFail($item['id']);

                    // 🚀 AJUSTE DA TRAVA: Só lança erro se manage_stock for TRUE
                    if ($product->manage_stock && $product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Estoque insuficiente para: {$product->name}");
                    }

                    // Registrar o item da venda
                    BarSaleItem::create([
                        'bar_sale_id' => $sale->id,
                        'bar_product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price_at_sale' => $product->sale_price
                    ]);

                    // 📉 Baixa no estoque
                    $product->decrement('stock_quantity', $item['quantity']);

                    // 📜 REGISTRO NO HISTÓRICO
                    \App\Models\Bar\BarStockMovement::create([
                        'bar_product_id' => $product->id,
                        'user_id'        => auth()->id(),
                        'quantity'       => -$item['quantity'],
                        'type'           => 'saida',
                        'description'    => "Venda Direta PDV #{$sale->id}",
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Venda finalizada com sucesso!'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
