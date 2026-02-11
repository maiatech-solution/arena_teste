<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarTable;
use App\Models\Bar\BarOrder;
use App\Models\Bar\BarOrderItem;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarCategory;
use App\Models\Bar\BarStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarTableController extends Controller
{
    /**
     * Exibe o Mapa de Mesas
     */
    public function index()
    {
        // O index agora esconde APENAS o que for 'inactive' (configuração de layout).
        // Mesas 'reserved' (desativadas pelo botão 🚫) continuam aparecendo no mapa.
        $tables = BarTable::where('status', '!=', 'inactive')
            ->orderByRaw('CAST(identifier AS UNSIGNED) ASC')
            ->get();

        return view('bar.tables.index', compact('tables'));
    }

    /**
     * Sincroniza a quantidade de mesas (Cria ou oculta mesas extras)
     */
    public function sync(Request $request)
    {
        $request->validate(['total_tables' => 'required|integer|min:1']);
        $totalDesejado = (int) $request->total_tables;

        // 1. Verifica quantas mesas existem fisicamente no banco de dados
        $atualNoBanco = BarTable::count();

        // 2. Cria novas mesas se o número solicitado for maior que o histórico total
        if ($totalDesejado > $atualNoBanco) {
            for ($i = $atualNoBanco + 1; $i <= $totalDesejado; $i++) {
                BarTable::create([
                    'identifier' => $i,
                    'status' => 'available'
                ]);
            }
        }

        // 3. Atualiza o status em massa:
        // Mesas DENTRO do novo limite: se estavam 'inactive', voltam para 'available'.
        // Respeitamos o status 'occupied' para não resetar mesas com clientes agora.
        BarTable::whereRaw('CAST(identifier AS UNSIGNED) <= ?', [$totalDesejado])
            ->whereIn('status', ['inactive', 'reserved']) // Reativa tanto as ocultas quanto as bloqueadas
            ->update(['status' => 'available']);

        // Mesas FORA do novo limite: ganham status 'inactive' para sumirem do mapa de vez.
        BarTable::whereRaw('CAST(identifier AS UNSIGNED) > ?', [$totalDesejado])
            ->update(['status' => 'inactive']);

        return back()->with('success', 'Layout do salão atualizado!');
    }

    /**
     * Ativa/Desativa Mesa (Usa 'reserved' como bloqueio)
     */
    public function toggleStatus($id)
    {
        $table = BarTable::findOrFail($id);

        if ($table->status === 'occupied') {
            return back()->with('error', 'Mesa ocupada não pode ser alterada!');
        }

        $table->status = ($table->status === 'reserved') ? 'available' : 'reserved';
        $table->save();

        return back();
    }

    /**
     * Abre a Comanda
     */
    /**
     * Abre a Comanda (Com trava de segurança de data)
     */
    public function open($id)
    {
        $table = BarTable::findOrFail($id);

        // 1. BUSCA A SESSÃO DE CAIXA ATIVA
        $session = \App\Models\Bar\BarCashSession::where('status', 'open')->first();

        // 2. 🛡️ VALIDAÇÃO DE CAIXA: Verifica se existe caixa aberto
        if (!$session) {
            return back()->with('error', '⚠️ Bloqueio: Não existe um caixa aberto! Por favor, abra o turno na Gestão de Caixa antes de atender mesas.');
        }

        // 3. 🛡️ VALIDAÇÃO DE DATA: Impede abrir mesa com caixa de ontem
        $dataAbertura = \Carbon\Carbon::parse($session->opened_at)->format('Y-m-d');
        $hoje = date('Y-m-d');

        if ($dataAbertura !== $hoje) {
            return back()->with('error', '⚠️ ATENÇÃO: O caixa aberto pertence ao dia anterior (' . \Carbon\Carbon::parse($session->opened_at)->format('d/m') . '). Você deve encerrar o turno antigo e abrir um novo para hoje antes de iniciar novos atendimentos.');
        }

        // 4. VERIFICA DISPONIBILIDADE DA MESA
        if ($table->status !== 'available') {
            return back()->with('error', 'Esta mesa não está disponível.');
        }

        try {
            return DB::transaction(function () use ($table) {
                // Cria a ordem vinculada à mesa
                BarOrder::create([
                    'bar_table_id' => $table->id,
                    'user_id' => auth()->id(),
                    'status' => 'open',
                    'total_value' => 0.00
                ]);

                // Atualiza o status da mesa para ocupada
                $table->status = 'occupied';
                $table->save();

                return back()->with('success', "Mesa {$table->identifier} aberta com sucesso!");
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao abrir mesa: ' . $e->getMessage());
        }
    }

    /**
     * Exibe os itens da mesa
     */
    public function showOrder($id)
    {
        $table = BarTable::findOrFail($id);

        $order = BarOrder::with('items.product')
            ->where('bar_table_id', $table->id)
            ->where('status', 'open')
            ->firstOrFail();

        $products = BarProduct::orderBy('name')->get();
        $categories = BarCategory::all();

        return view('bar.tables.show', compact('table', 'order', 'products', 'categories'));
    }

    /**
     * Lança item na comanda via AJAX
     */
    public function addItem(Request $request, $orderId)
    {
        try {
            return DB::transaction(function () use ($request, $orderId) {
                $order = BarOrder::findOrFail($orderId);
                $product = BarProduct::findOrFail($request->product_id);
                $qty = $request->quantity ?? 1;

                // 🚀 A MESMA TRAVA DA BarPosController
                if ($product->manage_stock && $product->stock_quantity < $qty) {
                    throw new \Exception("Estoque insuficiente para: {$product->name}");
                }

                // Lógica de inserir/incrementar na comanda
                $item = BarOrderItem::where('bar_order_id', $order->id)
                    ->where('bar_product_id', $product->id)
                    ->first();

                if ($item) {
                    $item->increment('quantity', $qty);
                    $item->update(['subtotal' => $item->quantity * $product->sale_price]);
                } else {
                    BarOrderItem::create([
                        'bar_order_id'   => $order->id,
                        'bar_product_id' => $product->id,
                        'quantity'       => $qty,
                        'unit_price'     => $product->sale_price,
                        'subtotal'       => $qty * $product->sale_price
                    ]);
                }

                // Atualiza o total da mesa
                $order->update(['total_value' => $order->items()->sum('subtotal')]);

                // 📉 BAIXA NO ESTOQUE (Igual ao PDV)
                $product->decrement('stock_quantity', $qty);

                // 📜 REGISTRO NO HISTÓRICO (Igual ao PDV)
                BarStockMovement::create([
                    'bar_product_id' => $product->id,
                    'user_id'        => auth()->id(),
                    'quantity'       => -$qty,
                    'type'           => 'saida',
                    'description'    => "Lançamento Mesa #{$order->table->identifier} (Comanda #{$order->id})",
                ]);

                return response()->json(['success' => true]);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Remove item da comanda
     */
    public function removeItem($itemId)
    {
        try {
            return DB::transaction(function () use ($itemId) {
                $item = BarOrderItem::findOrFail($itemId);
                $order = $item->order;
                $product = $item->product;
                $quantidadeEstornada = $item->quantity;

                // 1. Devolve a quantidade ao estoque (se o produto for controlado)
                if ($product->manage_stock) {
                    $product->increment('stock_quantity', $quantidadeEstornada);

                    // 📜 Registra a ENTRADA por estorno no histórico
                    BarStockMovement::create([
                        'bar_product_id' => $product->id,
                        'user_id'        => auth()->id(),
                        'quantity'       => $quantidadeEstornada, // Positivo pois está entrando de volta
                        'type'           => 'entrada',
                        'description'    => "Estorno: Item removido da Mesa #{$order->table->identifier}",
                    ]);
                }

                // 2. Remove o item e atualiza o total da mesa
                $item->delete();
                $order->update(['total_value' => $order->items()->sum('subtotal') ?? 0]);

                return back()->with('success', 'Item removido e estoque estornado!');
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao remover item: ' . $e->getMessage());
        }
    }

    /**
     * 🏁 FINALIZAR MESA (FECHAMENTO ESTILO PDV INTEGRADO AO CAIXA)
     */
    public function closeOrder(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $table = BarTable::findOrFail($id);

            $order = $table->orders()
                ->where('status', 'open')
                ->latest()
                ->first();

            if (!$order) {
                if ($table->status == 'occupied') {
                    $table->update(['status' => 'available']);
                    return redirect()->route('bar.tables.index')
                        ->with('success', 'Mesa liberada, mas nenhuma comanda ativa foi encontrada.');
                }
                return redirect()->route('bar.tables.index')
                    ->with('error', '⚠️ Nenhuma comanda ativa encontrada para esta mesa.');
            }

            // 1. ATUALIZA A COMANDA
            $order->update([
                'status'         => 'paid',
                'customer_name'  => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'payment_method' => $request->pagamentos,
                'closed_at'      => now(),
            ]);

            // 💰 2. INTEGRAÇÃO COM O CAIXA (BAR_CASH_MOVEMENTS)
            $session = \App\Models\Bar\BarCashSession::where('status', 'open')->first();

            if ($session) {
                // Decodificamos o JSON de pagamentos (ex: [{"metodo":"pix","valor":50},{"metodo":"dinheiro","valor":20}])
                $pagamentosArray = json_decode($request->pagamentos, true);

                if (is_array($pagamentosArray)) {
                    foreach ($pagamentosArray as $pag) {
                        if ($pag['valor'] > 0) {
                            \App\Models\Bar\BarCashMovement::create([
                                'bar_cash_session_id' => $session->id,
                                'user_id'             => auth()->id(),
                                'bar_order_id'        => $order->id,
                                'type'                => 'venda',
                                'payment_method'      => $pag['metodo'], // pix, dinheiro, debito, etc
                                'amount'              => $pag['valor'],
                                'description'         => "Venda Mesa #{$table->identifier}",
                            ]);

                            // Se for dinheiro, atualizamos o saldo esperado do caixa para auditoria
                            if ($pag['metodo'] == 'dinheiro') {
                                $session->increment('expected_balance', $pag['valor']);
                            }
                        }
                    }
                }
            }

            // ✅ Libera a mesa
            $table->update(['status' => 'available']);

            if ($request->print_coupon == "1") {
                return redirect()->route('bar.tables.receipt', $order->id)
                    ->with('show_success_modal', true)
                    ->with('success', 'Venda finalizada com sucesso!');
            }

            return redirect()->route('bar.tables.index')
                ->with('success', 'Venda finalizada com sucesso!');
        });
    }

    /**
     * 🖨️ EXIBIR RECIBO PARA IMPRESSÃO
     */
    public function printReceipt($orderId)
    {
        $order = BarOrder::with(['items.product', 'table'])->findOrFail($orderId);

        // Verifica se a ordem realmente pertence a uma mesa (opcional)
        return view('bar.tables.receipt', compact('order'));
    }
}
