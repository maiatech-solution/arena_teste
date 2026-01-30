<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarCategory;
use App\Models\Bar\BarStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarProductController extends Controller
{
    /**
     * Listagem de Produtos com Painel de Estoque Crítico
     */
    public function index(Request $request)
    {
        // 1. Captura todos os filtros da URL
        $search = $request->query('search');
        $categoryId = $request->query('bar_category_id');
        $filterStatus = $request->query('filter_status', 'all');

        // 2. Inicia a Query
        $query = BarProduct::with('category');

        // 🔍 Filtro por Nome ou Código de Barras
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', $search);
            });
        }

        // 📂 Filtro por Categoria
        if ($categoryId) {
            $query->where('bar_category_id', $categoryId);
        }

        // 🚀 Filtro por Status (Ajustado para incluir a Reposição Urgente)
        if ($filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($filterStatus === 'inactive') {
            $query->where('is_active', false);
        } elseif ($filterStatus === 'low_stock') {
            $query->where('is_active', true)
                ->where('manage_stock', true)
                ->whereColumn('stock_quantity', '<=', 'min_stock');
        } elseif ($filterStatus === 'out_of_stock') {
            // 🔥 NOVO FILTRO: Apenas produtos com saldo negativo
            $query->where('stock_quantity', '<', 0);
        }

        // 3. Executa a paginação mantendo os filtros na URL
        $products = $query->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        // 4. Dados para os cards e filtros da View
        $categories = BarCategory::orderBy('name', 'asc')->get();

        // ⚠️ Produtos críticos (Abaixo do mínimo, mas positivos)
        $lowStockProducts = BarProduct::where('is_active', true)
            ->where('manage_stock', true)
            ->where('stock_quantity', '>=', 0) // Garante que não mistura com os negativos
            ->whereColumn('stock_quantity', '<=', 'min_stock')
            ->get();

        // 🛒 Reposição Urgente (Saldo Negativo)
        $outOfStockCount = BarProduct::where('stock_quantity', '<', 0)->count();

        $totalPatrimonio = BarProduct::where('is_active', true)
            ->get()
            ->sum(fn($p) => $p->purchase_price * $p->stock_quantity);

        return view('bar.products.index', compact(
            'products',
            'categories',
            'lowStockProducts',
            'totalPatrimonio',
            'outOfStockCount' // 🚀 Enviando a nova contagem para a View
        ));
    }

    public function create()
    {
        $categories = BarCategory::orderBy('name', 'asc')->get();
        return view('bar.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'bar_category_id'  => 'required|exists:bar_categories,id',
            'barcode'          => 'nullable|string|max:13|unique:bar_products,barcode',
            'purchase_price'   => 'required|numeric|min:0',
            'sale_price'       => 'required|numeric|min:0',
            'stock_quantity'   => 'required|integer', // Removido min:0 para aceitar saldo negativo
            'min_stock'        => 'required|integer|min:0',
            'unit_type'        => 'required|string|in:UN,FD,CX,KG',
            'content_quantity' => 'required|integer|min:1',
            'manage_stock'     => 'required|boolean', // 🛡️ Novo campo de controle
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
            'name'             => 'required|string|max:255',
            'bar_category_id'  => 'required|exists:bar_categories,id',
            'barcode'          => 'nullable|string|max:13|unique:bar_products,barcode,' . $product->id,
            'purchase_price'   => 'required|numeric|min:0',
            'sale_price'       => 'required|numeric|min:0',
            'stock_quantity'   => 'required|integer',
            'min_stock'        => 'required|integer|min:0',
            'is_active'        => 'required|boolean',
            'unit_type'        => 'required|string|in:UN,FD,CX,KG',
            'content_quantity' => 'required|integer|min:1',
            'manage_stock'     => 'required|boolean', // 🛡️ Novo campo de controle
        ]);

        $product->update($validated);

        return redirect()->route('bar.products.index')
            ->with('success', 'Produto atualizado!');
    }

    public function destroy(Request $request, BarProduct $product)
    {
        $reason = $request->input('status_reason') ?? ($product->is_active ? 'Desativação manual' : 'Reativação manual');

        DB::transaction(function () use ($product, $reason) {
            $product->update([
                'is_active' => !$product->is_active
            ]);

            BarStockMovement::create([
                'bar_product_id' => $product->id,
                'user_id'        => auth()->id(),
                'quantity'       => 0,
                'type'           => $product->is_active ? 'entrada' : 'saida',
                'description'    => ($product->is_active ? '🟢 REATIVADO: ' : '🔴 DESATIVADO: ') . $reason,
            ]);
        });

        $mensagem = $product->is_active ? "Produto reativado!" : "Produto desativado!";
        return redirect()->route('bar.products.index')->with('success', $mensagem);
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:bar_categories,name']);
        $category = BarCategory::create(['name' => $request->name]);
        return response()->json($category);
    }

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
            'entry_mode' => 'required|in:UN,BULK',
            'description' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $product = BarProduct::findOrFail($validated['product_id']);

            $finalQuantity = $validated['entry_mode'] === 'BULK'
                ? $validated['quantity'] * $product->content_quantity
                : $validated['quantity'];

            $product->increment('stock_quantity', $finalQuantity);

            BarStockMovement::create([
                'bar_product_id' => $product->id,
                'user_id'        => auth()->id(),
                'quantity'       => $finalQuantity,
                'type'           => 'entrada',
                'description'    => $request->description ?? ($validated['entry_mode'] === 'BULK'
                    ? "Entrada de {$validated['quantity']} " . ($product->unit_type ?? 'caixas') . " (Total: {$finalQuantity} un)"
                    : 'Abastecimento avulso de estoque'),
            ]);
        });

        return redirect()->route('bar.products.index')
            ->with('success', "Estoque atualizado com sucesso!");
    }

    public function stockHistory(Request $request)
    {
        $query = BarStockMovement::with(['product', 'user']);

        if ($request->filled('type')) {
            $request->type == 'entrada' ? $query->where('quantity', '>', 0) : $query->where('quantity', '<', 0);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $movements = $query->orderBy('created_at', 'desc')
            ->paginate(30)
            ->appends($request->all());

        return view('bar.products.history', compact('movements'));
    }

    public function addStock(Request $request, BarProduct $product)
    {
        $validated = $request->validate(['quantity' => 'required|integer|min:1']);
        $product->increment('stock_quantity', $validated['quantity']);
        return redirect()->back()->with('success', 'Estoque atualizado!');
    }

    public function recordLoss(Request $request)
    {
        $validated = $request->validate([
            'product_id'  => 'required|exists:bar_products,id',
            'quantity'    => 'required|integer|min:1',
            'description' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated) {
            $product = BarProduct::findOrFail($validated['product_id']);
            $product->decrement('stock_quantity', $validated['quantity']);

            BarStockMovement::create([
                'bar_product_id' => $product->id,
                'user_id'        => auth()->id(),
                'quantity'       => -$validated['quantity'],
                'type'           => 'perda',
                'description'    => 'PERDA: ' . $validated['description'],
            ]);
        });

        return redirect()->back()->with('success', 'Perda registrada e estoque atualizado.');
    }
}
