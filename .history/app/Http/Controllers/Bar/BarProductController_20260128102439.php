<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarProduct;
use Illuminate\Http\Request;

class BarProductController extends Controller
{
    // Listagem de Produtos
    public function index(Request $request)
    {
        $search = $request->query('search');

        $products = BarProduct::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('barcode', $search); // Busca exata por código de barras
        })
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('bar.products.index', compact('products'));
    }

    // Tela de Cadastro
    public function create()
    {
        return view('bar.products.create');
    }

    // Salvar Novo Produto
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            // Delimitado a 13 caracteres para o padrão EAN-13
            'barcode'        => 'nullable|string|max:13|unique:bar_products,barcode',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price'     => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock'      => 'required|integer|min:0',
        ], [
            'barcode.max'    => 'O código de barras não pode ultrapassar 13 dígitos.',
            'barcode.unique' => 'Este código de barras já está cadastrado em outro produto.',
        ]);

        BarProduct::create($validated);

        return redirect()->route('bar.products.index')
            ->with('success', 'Produto ' . $validated['name'] . ' cadastrado com sucesso!');
    }

    // Tela de Edição
    public function edit(BarProduct $product)
    {
        return view('bar.products.edit', compact('product'));
    }

    // Atualizar Produto
    public function update(Request $request, BarProduct $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|unique:bar_products,barcode,' . $product->id,
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'is_active' => 'required|boolean'
        ]);

        $product->update($validated);

        return redirect()->route('bar.products.index')->with('success', 'Produto atualizado!');
    }

    // Desativar Produto (Soft Delete lógico)
    public function destroy(BarProduct $product)
    {
        $product->update(['is_active' => false]);
        return redirect()->route('bar.products.index')->with('success', 'Produto desativado com sucesso!');
    }
}
