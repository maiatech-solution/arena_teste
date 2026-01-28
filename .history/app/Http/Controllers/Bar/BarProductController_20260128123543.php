<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarCategory;
use App\Models\Bar\BarStockMovement; // Importado para os Logs
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Necessário para Transactions

class BarProductController extends Controller
{
    /**
     * Listagem de Produtos com Painel de Estoque Crítico
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('bar_category_id');

        $products = BarProduct::when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('barcode', $search);
                });
            })
            ->when($categoryId, function ($query, $categoryId) {
                return $query->where('bar_category_id', $categoryId);
            })
            ->orderBy('name', 'asc')
            ->paginate(15);

        $categories = BarCategory::orderBy('name', 'asc')->get();

        // 🚀 Lógica de Estoque Crítico
        $lowStockProducts = BarProduct::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock')
            ->get();

        return view('bar.products.index', compact('products', 'categories', 'lowStockProducts'));
    }

    public function create()
    {
        $categories = BarCategory::orderBy('name', 'asc')->get();
        return view('bar.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'bar_category_id' => 'required|exists:bar_categories,id',
            'barcode'         => 'nullable|string|max:13|unique:bar_products,barcode',
            'purchase_price'  => 'required|numeric|min:0',
            'sale_price'      => 'required|numeric|min:0',
            'stock_quantity'  => 'required|integer|min:0',
            'min_stock'       => 'required|integer|min:0',
        ]);

        BarProduct::create($validated);

        return redirect()->route('bar.products.index')
            ->with('success', 'Produto cadastrado com sucesso!');
    }

    public function edit(BarProduct $product)
    {
        $categories = BarCategory::orderBy('name', 'asc')->get();
        return view('bar.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, BarProduct $product)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'bar_category_id' => 'required|exists:bar_categories,id',
            'barcode'         => 'nullable|string|max:13|unique:bar_products,barcode,' . $product->id,
            'purchase_price'  => 'required|numeric|min:0',
            'sale_price'      => 'required|numeric|min:0',
            'stock_quantity'  => 'required|integer|min:0',
            'min_stock'       => 'required|integer|min:0',
            'is_active'       => 'required|boolean'
        ]);

        $product->update($validated);

        return redirect()->route('bar.products.index')
            ->with('success', 'Produto atualizado!');
    }

    public function destroy(BarProduct $product)
    {
        $product->update(['is_active' => false]);
        return redirect()->route('bar.products.index')->with('success', 'Produto desativado!');
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:bar_categories,name']);
        $category = BarCategory::create(['name' => $request->name]);
        return response()->json($category);
    }

    /**
     * 🚚 GESTÃO DE ENTRADA (Com registro de Histórico)
     */
    public function stockEntry()
    {
        $products = BarProduct::where('is_active', true)->orderBy('name')->get();
        $categories = BarCategory::orderBy('name', 'asc')->get();
        return view('bar.products.stock-entry', compact('products', 'categories'));
    }

    public function processStockEntry(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:bar_products,id',
            'quantity'   => 'required|integer|min:1',
            'description'=> 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $product = BarProduct::findOrFail($validated['product_id']);
            $product->increment('stock_quantity', $validated['quantity']);

            // 📜 Grava o Log da Movimentação
            BarStockMovement::create([
                'bar_product_id' => $product->id,
                'user_id'        => auth()->id(),
                'quantity'       => $validated['quantity'],
                'type'           => 'entrada',
                'description'    => $request->description ?? 'Abastecimento de estoque',
            ]);
        });

        return redirect()->route('bar.products.index')
            ->with('success', "Estoque atualizado com registro no histórico!");
    }

    /**
     * 📜 Ver Histórico de Movimentações
     */
    public function stockHistory()
    {
        $movements = BarStockMovement::with(['product', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('bar.products.history', compact('movements'));
    }

    public function addStock(Request $request, BarProduct $product)
    {
        $validated = $request->validate(['quantity' => 'required|integer|min:1']);
        $product->increment('stock_quantity', $validated['quantity']);
        return redirect()->back()->with('success', 'Estoque atualizado!');
    }
}
