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
        // Pegamos apenas produtos ativos e que tenham estoque
        $products = BarProduct::where('is_active', true)->orderBy('name')->get();
        $categories = BarCategory::orderBy('name')->get();

        return view('bar.pos.index', compact('products', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            // 🚀 Ajustado para aceitar exatamente o que o JS envia:
            'payment_method' => 'required|in:dinheiro,pix,debito,credito',
            'total_value' => 'required|numeric'
        ]);

        try {
            return DB::transaction(function () use ($request) {
                // 1. Criar a Venda
                $sale = BarSale::create([
                    'user_id' => auth()->id(),
                    'total_value' => $request->total_value,
                    'payment_method' => $request->payment_method,
                    'status' => 'pago'
                ]);

                // 2. Processar cada item
                foreach ($request->items as $item) {
                    $product = BarProduct::findOrFail($item['id']);

                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Estoque insuficiente para: {$product->name}");
                    }

                    BarSaleItem::create([
                        'bar_sale_id' => $sale->id,
                        'bar_product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price_at_sale' => $product->sale_price
                    ]);

                    $product->decrement('stock_quantity', $item['quantity']);
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
