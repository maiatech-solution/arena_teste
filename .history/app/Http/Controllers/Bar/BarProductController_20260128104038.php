<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarProduct;
use Illuminate\Http\Request;

class BarProductController extends Controller
{
    /**
     * Listagem de Produtos com Filtro de Busca
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $products = BarProduct::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('barcode', $search);
        })
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('bar.products.index', compact('products'));
    }

    /**
     * Tela de Cadastro de Novo Produto
     */
    public function create()
    {
        return view('bar.products.create');
    }

    /**
     * Salvar Novo Produto no Banco
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'barcode'        => 'nullable|string|max:13|unique:bar_products,barcode',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock'      => 'required|integer|min:0',
        ], [
            'barcode.max'    => 'O código de barras não pode ultrapassar 13 dígitos.',
            'barcode.unique' => 'Este código de barras já pertence a outro produto cadastrado.',
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
        return view('bar.products.edit', compact('product'));
    }

    /**
     * Atualizar Produto Existente
     */
    public function update(Request $request, BarProduct $product)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'barcode'        => 'nullable|string|max:13|unique:bar_products,barcode,' . $product->id,
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock'      => 'required|integer|min:0',
            'is_active'      => 'required|boolean'
        ], [
            'barcode.max'    => 'O código de barras não pode ultrapassar 13 dígitos.',
            'barcode.unique' => 'Este código de barras já está em uso por outro produto.',
        ]);

        $product->update($validated);

        return redirect()->route('bar.products.index')
            ->with('success', 'Produto "' . $product->name . '" atualizado com sucesso!');
    }

    /**
     * Desativar Produto (Soft Delete Lógico)
     */
    public function destroy(BarProduct $product)
    {
        $product->update(['is_active' => false]);

        return redirect()->route('bar.products.index')
            ->with('success', 'O produto foi desativado e não aparecerá mais nas vendas.');
    }

    // =========================================================================
    // 🚚 GESTÃO DE ENTRADA DE MERCADORIA (NOVO)
    // =========================================================================

    /**
     * Exibe a tela dedicada para entrada de estoque
     */
    public function stockEntry()
    {
        // Pegamos apenas produtos ativos para o abastecimento
        $products = BarProduct::where('is_active', true)->orderBy('name')->get();

        return view('bar.products.stock-entry', compact('products'));
    }

    /**
     * Processa o formulário de entrada (abastecimento)
     */
    public function processStockEntry(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:bar_products,id',
            'quantity'   => 'required|integer|min:1',
        ], [
            'product_id.required' => 'Você precisa selecionar um produto.',
            'quantity.min'        => 'A quantidade mínima de entrada é 1.'
        ]);

        $product = BarProduct::findOrFail($validated['product_id']);

        // Incrementa o estoque atual
        $product->increment('stock_quantity', $validated['quantity']);

        return redirect()->route('bar.products.index')
            ->with('success', "Abastecimento concluído! +{$validated['quantity']} unidades de {$product->name}.");
    }

    /**
     * Ajuste rápido individual (PATCH direto da tabela, se houver)
     */
    public function addStock(Request $request, BarProduct $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product->increment('stock_quantity', $validated['quantity']);

        return redirect()->back()->with(
            'success',
            "Estoque de {$product->name} atualizado! (+{$validated['quantity']} unidades)"
        );
    }
}
