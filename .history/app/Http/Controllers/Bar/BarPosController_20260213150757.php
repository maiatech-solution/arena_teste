<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarSale;
use App\Models\Bar\BarSaleItem;
use App\Models\Bar\BarCategory;
use App\Models\Bar\BarCashSession;    // Importado
use App\Models\Bar\BarCashMovement;   // Importado
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarPosController extends Controller
{
    public function index()
    {
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
        $request->validate([
            'items' => 'required|array',
            'payments' => 'required|array',
            'total_value' => 'required|numeric'
        ]);

        try {
            return DB::transaction(function () use ($request) {

                // 1. BUSCAR SESSÃO DE CAIXA ATIVA
                $session = BarCashSession::where('status', 'open')->first();

                if (!$session) {
                    throw new \Exception("Não existe um caixa aberto! Por favor, abra o caixa primeiro.");
                }

                // 🛡️ NOVA TRAVA: BLOQUEIO DE CAIXA DO DIA ANTERIOR
                $dataAbertura = \Carbon\Carbon::parse($session->opened_at)->format('Y-m-d');
                $hoje = date('Y-m-d');

                if ($dataAbertura !== $hoje) {
                    throw new \Exception("⚠️ CAIXA VENCIDO: O caixa aberto é de ontem (" . \Carbon\Carbon::parse($session->opened_at)->format('d/m') . "). Você deve encerrar o turno antigo e abrir um novo para hoje antes de vender.");
                }

                // Determinar o método final (apenas para histórico da BarSale)
                $metodoFinal = count($request->payments) > 1 ? 'misto' : $request->payments[0]['method'];

                // 2. Criar a Venda (Vinculando ao Caixa Aberto)
                $sale = BarSale::create([
                    'user_id' => auth()->id(),
                    'total_value' => $request->total_value,
                    'payment_method' => $metodoFinal,
                    'status' => 'pago',
                    'bar_cash_session_id' => $session->id, // 🔥 O carimbo para o PDV
                ]);

                // 3. Processar Itens e Estoque
                foreach ($request->items as $item) {
                    $product = BarProduct::findOrFail($item['id']);

                    if ($product->manage_stock && $product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Estoque insuficiente para: {$product->name}");
                    }

                    BarSaleItem::create([
                        'bar_sale_id' => $sale->id,
                        'bar_product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'price_at_sale' => $product->sale_price
                    ]);

                    $product->decrement('stock_quantity', $item['quantity']);

                    // Histórico de Estoque
                    \App\Models\Bar\BarStockMovement::create([
                        'bar_product_id' => $product->id,
                        'user_id'        => auth()->id(),
                        'quantity'       => -$item['quantity'],
                        'type'           => 'saida',
                        'description'    => "Venda Direta PDV #{$sale->id}",
                    ]);
                }

                // 4. INTEGRAÇÃO COM O CAIXA
                foreach ($request->payments as $pay) {
                    BarCashMovement::create([
                        'bar_cash_session_id' => $session->id,
                        'user_id'             => auth()->id(),
                        'bar_sale_id'         => $sale->id,
                        'type'                => 'venda',
                        'payment_method'      => $pay['method'],
                        'amount'              => $pay['value'],
                        'description'         => "Venda Direta PDV #{$sale->id}"
                    ]);

                    // Se foi pago em dinheiro, atualiza o saldo esperado na gaveta
                    if ($pay['method'] === 'dinheiro') {
                        $session->increment('expected_balance', $pay['value']);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Venda finalizada e caixa atualizado!'
                ]);
            });
        } catch (\Exception $e) {
            // Como o PDV usa AJAX, o erro cai aqui e envia a mensagem pro alerta do JS
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
