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
        $search = $request->query('search');
        $categoryId = $request->query('bar_category_id');
        $filterStatus = $request->query('filter_status', 'all');

        // 🛡️ Query limpa: sem dependência de arena_id
        $query = BarProduct::with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', $search);
            });
        }

        if ($categoryId) {
            $query->where('bar_category_id', $categoryId);
        }

        if ($filterStatus === 'active') {
            $query->where('is_active', true);
        } elseif ($filterStatus === 'inactive') {
            $query->where('is_active', false);
        } elseif ($filterStatus === 'low_stock') {
            $query->where('is_active', true)
                ->where('manage_stock', true)
                ->whereColumn('stock_quantity', '<=', 'min_stock');
        } elseif ($filterStatus === 'out_of_stock') {
            $query->where('stock_quantity', '<', 0);
        }

        $products = $query->orderBy('name', 'asc')
            ->paginate(15)
            ->withQueryString();

        $categories = BarCategory::orderBy('name', 'asc')->get();

        // ⚠️ Produtos críticos (Resumo do Painel)
        $lowStockProducts = BarProduct::where('is_active', true)
            ->where('manage_stock', true)
            ->where('stock_quantity', '>=', 0)
            ->whereColumn('stock_quantity', '<=', 'min_stock')
            ->get();

        $outOfStockCount = BarProduct::where('stock_quantity', '<', 0)->count();

        $totalPatrimonio = BarProduct::where('is_active', true)
            ->get()
            ->sum(fn($p) => $p->purchase_price * $p->stock_quantity);

        return view('bar.products.index', compact(
            'products',
            'categories',
            'lowStockProducts',
            'totalPatrimonio',
            'outOfStockCount'
        ));
    }

    public function create()
    {
        $categories = BarCategory::orderBy('name', 'asc')->get();

        // Pegamos todos os produtos simples (que não são combos) para compor novos combos
        $availableProducts = BarProduct::where('is_active', true)
            ->where('is_combo', false)
            ->orderBy('name', 'asc')
            ->get();

        return view('bar.products.create', compact('categories', 'availableProducts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'bar_category_id'  => 'required|exists:bar_categories,id',
            'barcode'          => 'nullable|string|max:13',
            'purchase_price'   => 'required|numeric|min:0',
            'sale_price'       => 'required|numeric|min:0',
            'stock_quantity'   => 'required|integer',
            'min_stock'        => 'required|integer|min:0',
            'unit_type'        => 'required|string|in:UN,FD,CX,KG',
            'content_quantity' => 'required|integer|min:1',
            'manage_stock'     => 'required|boolean',
            'is_combo'         => 'nullable|boolean', // Novo campo
            'combo_items'      => 'required_if:is_combo,1|array', // Obrigatório se for combo
        ]);

        DB::transaction(function () use ($request, $validated) {
            // Se for combo, forçamos o manage_stock para false (estoque virtual)
            if ($request->is_combo) {
                $validated['manage_stock'] = false;
                $validated['stock_quantity'] = 0;
            }

            $product = BarProduct::create($validated);

            // Se for combo, salva a composição
            if ($request->is_combo && $request->has('combo_items')) {
                foreach ($request->combo_items as $item) {
                    if (!empty($item['child_id']) && $item['quantity'] > 0) {
                        \App\Models\Bar\BarProductComposition::create([
                            'parent_id' => $product->id,
                            'child_id'  => $item['child_id'],
                            'quantity'  => $item['quantity'],
                        ]);
                    }
                }
            }
        });

        return redirect()->route('bar.products.index')
            ->with('success', 'Produto cadastrado com sucesso!');
    }

    public function edit(BarProduct $product)
    {
        // 1. Trava de segurança original mantida
        if (auth()->user()->role === 'colaborador') {
            return redirect()->route('bar.products.index')->with('error', 'Acesso negado.');
        }

        // 2. Busca categorias para o select
        $categories = BarCategory::orderBy('name', 'asc')->get();

        // 3. Produtos disponíveis para compor o combo
        // Removi a trava 'is_combo', false para permitir que um combo tenha outro dentro,
        // mas se preferir proibir combos dentro de combos, pode manter.
        $availableProducts = BarProduct::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->orderBy('name', 'asc')
            ->get();

        // 4. 🔥 CARGA DOS DADOS: Importante usar 'childProduct' que é o nome no Model
        $product->load(['compositions.childProduct', 'category']);

        return view('bar.products.edit', compact('product', 'categories', 'availableProducts'));
    }

    public function update(Request $request, BarProduct $product)
    {
        // 1. Trava de segurança (Original)
        if (auth()->user()->role === 'colaborador') {
            abort(403);
        }

        // 2. Ajuste de validação: se for combo, alguns campos não são obrigatórios no request
        // porque o Alpine.js pode esconder/não enviar, ou o estoque é controlado pelos filhos.
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'bar_category_id'  => 'required|exists:bar_categories,id',
            'barcode'          => 'nullable|string|max:13',
            'purchase_price'   => 'required|numeric|min:0',
            'sale_price'       => 'required|numeric|min:0',
            'stock_quantity'   => $request->is_combo ? 'nullable' : 'required|integer',
            'min_stock'        => $request->is_combo ? 'nullable' : 'required|integer|min:0',
            'is_active'        => 'required|boolean',
            'unit_type'        => $request->is_combo ? 'nullable' : 'required|string|in:UN,FD,CX,KG',
            'content_quantity' => $request->is_combo ? 'nullable' : 'required|integer|min:1',
            'manage_stock'     => 'required|boolean',
            'is_combo'         => 'nullable|boolean',
        ]);

        try {
            DB::transaction(function () use ($request, $product, $validated) {

                // 3. Forçar regras de combo antes de salvar
                if ($request->is_combo) {
                    $validated['manage_stock'] = false;
                    $validated['stock_quantity'] = 0; // Combo não tem estoque próprio
                    $validated['min_stock'] = 0;
                }

                // 4. Atualizar o Produto
                $product->update($validated);

                // 5. Sincronizar Itens do Combo 🚀
                if ($request->is_combo) {
                    // Limpa a composição antiga (mais seguro que sync manual para o seu caso)
                    $product->compositions()->delete();

                    if ($request->has('combo_items')) {
                        foreach ($request->combo_items as $item) {
                            // Só registra se o item tiver um ID de produto filho selecionado
                            if (!empty($item['child_id'])) {
                                \App\Models\Bar\BarProductComposition::create([
                                    'parent_id' => $product->id,
                                    'child_id'  => $item['child_id'],
                                    'quantity'  => $item['quantity'] ?? 1,
                                ]);
                            }
                        }
                    }
                } else {
                    // Se o usuário desmarcou 'is_combo', removemos qualquer composição que sobrou
                    $product->compositions()->delete();
                }
            });

            return redirect()->route('bar.products.index')->with('success', '✅ Produto atualizado com sucesso!');
        } catch (\Exception $e) {
            return back()->with('error', '❌ Erro ao salvar: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Request $request, BarProduct $product)
    {
        if (auth()->user()->role === 'colaborador') {
            return redirect()->back()->with('error', 'Ação não permitida.');
        }

        $reason = $request->input('status_reason') ?? ($product->is_active ? 'Desativação manual' : 'Reativação manual');

        DB::transaction(function () use ($product, $reason) {
            $product->update(['is_active' => !$product->is_active]);

            BarStockMovement::create([
                'bar_product_id' => $product->id,
                'user_id'        => auth()->id(),
                'quantity'       => 0,
                'type'           => $product->is_active ? 'entrada' : 'saida',
                'description'    => ($product->is_active ? '🟢 REATIVADO: ' : '🔴 DESATIVADO: ') . $reason,
            ]);
        });

        return redirect()->route('bar.products.index')->with('success', $product->is_active ? "Produto reativado!" : "Produto desativado!");
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
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

        return redirect()->route('bar.products.index')->with('success', "Estoque atualizado!");
    }

    public function stockHistory(Request $request)
    {
        $query = BarStockMovement::with(['product', 'user']);

        if ($request->filled('type')) {
            $request->type == 'entrada' ? $query->where('quantity', '>', 0) : $query->where('quantity', '<', 0);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(30)->appends($request->all());

        return view('bar.products.history', compact('movements'));
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

        return redirect()->back()->with('success', 'Perda registrada.');
    }
}
