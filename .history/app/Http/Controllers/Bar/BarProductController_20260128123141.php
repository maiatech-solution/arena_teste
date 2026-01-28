<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarCategory; // Importante: Adicionamos o Model de Categoria
use Illuminate\Http\Request;

class BarProductController extends Controller
{

    /**
     * Listagem de Produtos com Filtro de Busca, Categorias e Alertas de Estoque
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

        // Busca todas as categorias para o filtro da tabela
        $categories = BarCategory::orderBy('name', 'asc')->get();

        // 🚀 LÓGICA DO ESTOQUE CRÍTICO:
        // Filtra apenas produtos ativos que estão com quantidade igual ou abaixo do mínimo configurado
        $lowStockProducts = BarProduct::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock')
            ->get();

        return view('bar.products.index', compact('products', 'categories', 'lowStockProducts'));
    }

    /**
     * Tela de Cadastro
     */
    public function create()
    {
        $categories = BarCategory::orderBy('name', 'asc')->get();
        return view('bar.products.create', compact('categories'));
    }

    /**
     * Salvar Novo Produto
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'bar_category_id' => 'required|exists:bar_categories,id', // Valida se o ID existe no banco
            'barcode'         => 'nullable|string|max:13|unique:bar_products,barcode',
            'purchase_price'  => 'required|numeric|min:0',
            'sale_price'      => 'required|numeric|min:0',
            'stock_quantity'  => 'required|integer|min:0',
            'min_stock'       => 'required|integer|min:0',
        ], [
            'barcode.max'    => 'O código de barras não pode ultrapassar 13 dígitos.',
            'barcode.unique' => 'Este código de barras já pertence a outro produto cadastrado.',
            'bar_category_id.required' => 'Selecione uma categoria cadastrada.',
        ]);

        BarProduct::create($validated);

        return redirect()->route('bar.products.index')
            ->with('success', 'Produto "' . $validated['name'] . '" cadastrado com sucesso!');
    }

    /**
     * Tela de Edição
     */
    public function edit(BarProduct $product)
    {
        $categories = BarCategory::orderBy('name', 'asc')->get();
        return view('bar.products.edit', compact('product', 'categories'));
    }

    /**
     * Atualizar Produto
     */
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
            ->with('success', 'Produto "' . $product->name . '" atualizado!');
    }

    /**
     * Desativar Produto
     */
    public function destroy(BarProduct $product)
    {
        $product->update(['is_active' => false]);
        return redirect()->route('bar.products.index')->with('success', 'Produto desativado!');
    }

    // =========================================================================
    // ➕ NOVA FUNÇÃO: Cadastrar Categoria via Botão "+" (AJAX)
    // =========================================================================

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:bar_categories,name'
        ]);

        $category = BarCategory::create([
            'name' => $request->name
        ]);

        // Retorna os dados da nova categoria para o JavaScript colocar no Select
        return response()->json($category);
    }

    // =========================================================================
    // 🚚 GESTÃO DE ENTRADA DE MERCADORIA
    // =========================================================================

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
        ]);

        $product = BarProduct::findOrFail($validated['product_id']);
        $product->increment('stock_quantity', $validated['quantity']);

        return redirect()->route('bar.products.index')
            ->with('success', "Abastecimento concluído: +{$validated['quantity']} {$product->name}.");
    }

    public function addStock(Request $request, BarProduct $product)
    {
        $validated = $request->validate(['quantity' => 'required|integer|min:1']);
        $product->increment('stock_quantity', $validated['quantity']);
        return redirect()->back()->with('success', 'Estoque atualizado!');
    }
}
