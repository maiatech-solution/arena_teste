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

        // 🚀 Filtro por Status (Aqui era onde estava falhando)
        if ($filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($filterStatus === 'inactive') {
            $query->where('is_active', false);
        } elseif ($filterStatus === 'low_stock') {
            $query->where('is_active', true)
                ->whereColumn('stock_quantity', '<=', 'min_stock');
        }

        // 3. Executa a paginação mantendo os filtros na URL
        $products = $query->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        // 4. Dados para os cards e filtros da View
        $categories = BarCategory::orderBy('name', 'asc')->get();

        $lowStockProducts = BarProduct::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'min_stock')
            ->get();

        $totalPatrimonio = BarProduct::where('is_active', true)
            ->get()
            ->sum(fn($p) => $p->purchase_price * $p->stock_quantity);

        return view('bar.products.index', compact(
            'products',
            'categories',
            'lowStockProducts',
            'totalPatrimonio'
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
            'stock_quantity'   => 'required|integer|min:0',
            'min_stock'        => 'required|integer|min:0',
            // 🚀 NOVOS CAMPOS ADICIONADOS AQUI:
            'unit_type'        => 'required|string|in:UN,FD,CX,KG',
            'content_quantity' => 'required|integer|min:1',
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
            'is_active'       => 'required|boolean',
            // 🚀 ADICIONE ESTES DOIS ABAIXO PARA SALVAR A UNIDADE:
            'unit_type'       => 'required|string|in:UN,FD,CX,KG',
            'content_quantity' => 'required|integer|min:1',
        ]);

        $product->update($validated);

        return redirect()->route('bar.products.index')
            ->with('success', 'Produto atualizado!');
    }

    public function destroy(Request $request, BarProduct $product)
    {
        $reason = $request->input('status_reason') ?? ($product->is_active ? 'Desativação manual' : 'Reativação manual');

        DB::transaction(function () use ($product, $reason) {
            // Inverte o status
            $product->update([
                'is_active' => !$product->is_active
            ]);

            // 📜 Grava no histórico o motivo da mudança de status
            BarStockMovement::create([
                'bar_product_id' => $product->id,
                'user_id'        => auth()->id(),
                'quantity'       => 0, // Mudança de status não altera quantidade física
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
            'entry_mode' => 'required|in:UN,BULK', // Verifica se entrou como Unidade ou Fardo/Caixa
            'description' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $product = BarProduct::findOrFail($validated['product_id']);

            // 🧠 Lógica de Conversão:
            // Se o modo for 'BULK' (Fardo/Caixa), multiplica a qtd pelo fator do produto
            $finalQuantity = $validated['entry_mode'] === 'BULK'
                ? $validated['quantity'] * $product->content_quantity
                : $validated['quantity'];

            // 1. Incrementa o estoque real (em unidades)
            $product->increment('stock_quantity', $finalQuantity);

            // 📜 Grava o Log da Movimentação com a quantidade final convertida
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

    /**
     * 📜 Ver Histórico de Movimentações
     */
    public function stockHistory(Request $request)
    {
        $query = BarStockMovement::with(['product', 'user']);

        // Filtro por Tipo
        if ($request->filled('type')) {
            $request->type == 'entrada' ? $query->where('quantity', '>', 0) : $query->where('quantity', '<', 0);
        }

        // 🔥 NOVO FILTRO: Período "De" / "Até"
        if ($request->filled('start_date') && $request->filled('end_date')) {
            // Usamos o whereBetween para pegar o intervalo exato
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
            'description' => 'required|string|max:255', // Motivo é obrigatório na perda
        ]);

        DB::transaction(function () use ($validated) {
            $product = BarProduct::findOrFail($validated['product_id']);

            // 1. Diminui o estoque (decrement)
            $product->decrement('stock_quantity', $validated['quantity']);

            // 2. Grava o Log como 'perda'
            BarStockMovement::create([
                'bar_product_id' => $product->id,
                'user_id'        => auth()->id(),
                'quantity'       => -$validated['quantity'], // Salva como número negativo
                'type'           => 'perda',
                'description'    => 'PERDA: ' . $validated['description'],
            ]);
        });

        return redirect()->back()->with('success', 'Perda registrada e estoque atualizado.');
    }
}
