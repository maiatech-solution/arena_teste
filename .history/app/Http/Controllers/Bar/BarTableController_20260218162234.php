<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarTable;
use App\Models\Bar\BarOrder;
use App\Models\Bar\BarOrderItem;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarCategory;
use App\Models\Bar\BarStockMovement;
use App\Models\Bar\BarCashSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BarTableController extends Controller
{
    /**
     * Exibe o Mapa de Mesas
     */
    public function index()
    {
        // O index agora esconde APENAS o que for 'inactive'.
        $tables = BarTable::where('status', '!=', 'inactive')
            ->orderByRaw('CAST(identifier AS UNSIGNED) ASC')
            ->get();

        // 🛡️ DETECÇÃO DE CAIXA VENCIDO (Para alimentar o banner e os botões cinzas no Front)
        $openSession = BarCashSession::where('status', 'open')->first();
        $caixaVencido = false;

        if ($openSession) {
            $dataAbertura = Carbon::parse($openSession->opened_at)->startOfDay();
            $hoje = Carbon::today();

            if ($dataAbertura->lt($hoje)) {
                $caixaVencido = true;
            }
        }

        return view('bar.tables.index', compact('tables', 'caixaVencido', 'openSession'));
    }

   /**
     * Sincroniza a quantidade de mesas (Cria ou oculta mesas extras)
     */
    public function sync(Request $request)
    {
        $request->validate(['total_tables' => 'required|integer|min:1']);
        $totalDesejado = (int) $request->total_tables;

        // 1. Identifica a maior mesa já criada (pelo número, não por contagem)
        $maxAtual = BarTable::max(DB::raw('CAST(identifier AS UNSIGNED)')) ?: 0;

        // 2. Cria novas mesas APENAS se o número solicitado for maior que o maior identificador existente
        if ($totalDesejado > $maxAtual) {
            for ($i = $maxAtual + 1; $i <= $totalDesejado; $i++) {
                BarTable::create([
                    'identifier' => $i,
                    'status' => 'available'
                ]);
            }
        }

        // 3. Ativa mesas que estavam ocultas ou reservadas dentro do novo limite
        BarTable::whereRaw('CAST(identifier AS UNSIGNED) <= ?', [$totalDesejado])
            ->whereIn('status', ['inactive', 'reserved'])
            ->update(['status' => 'available']);

        // 4. 🛡️ Desativa mesas fora do limite (Apenas se NÃO estiverem ocupadas)
        // Usar o Eloquent aqui garante que as aspas sejam colocadas corretamente no SQL
        BarTable::whereRaw('CAST(identifier AS UNSIGNED) > ?', [$totalDesejado])
            ->where('status', '!=', 'occupied')
            ->update(['status' => 'inactive']);

        // 5. Verifica se alguma mesa ficou visível por estar ocupada mesmo acima do limite
        $ocupadasForaLimite = BarTable::whereRaw('CAST(identifier AS UNSIGNED) > ?', [$totalDesejado])
            ->where('status', 'occupied')
            ->count();

        if ($ocupadasForaLimite > 0) {
            return back()->with('warning', "Layout atualizado! Porém, {$ocupadasForaLimite} mesa(s) acima do limite continuam visíveis por estarem com comandas abertas.");
        }

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
     * Abre a Comanda (Com trava de segurança de data)
     */
    public function open($id)
    {
        $table = BarTable::findOrFail($id);

        // 1. BUSCA A SESSÃO DE CAIXA ATIVA
        $session = BarCashSession::where('status', 'open')->first();

        // 2. 🛡️ VALIDAÇÃO DE CAIXA: Verifica se existe caixa aberto
        if (!$session) {
            return back()->with('error', '⚠️ Bloqueio: Não existe um caixa aberto! Por favor, abra o turno na Gestão de Caixa antes de atender mesas.');
        }

        // 3. 🛡️ VALIDAÇÃO DE DATA: Impede abrir mesa com caixa de ontem
        $dataAbertura = Carbon::parse($session->opened_at)->format('Y-m-d');
        $hoje = date('Y-m-d');

        if ($dataAbertura !== $hoje) {
            return back()->with('error', '⚠️ ATENÇÃO: O caixa aberto pertence ao dia anterior (' . Carbon::parse($session->opened_at)->format('d/m') . '). Você deve encerrar o turno antigo e abrir um novo para hoje antes de iniciar novos atendimentos.');
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

                if ($product->manage_stock && $product->stock_quantity < $qty) {
                    throw new \Exception("Estoque insuficiente para: {$product->name}");
                }

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

                $order->update(['total_value' => $order->items()->sum('subtotal')]);
                $product->decrement('stock_quantity', $qty);

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

                if ($product->manage_stock) {
                    $product->increment('stock_quantity', $quantidadeEstornada);
                    BarStockMovement::create([
                        'bar_product_id' => $product->id,
                        'user_id'        => auth()->id(),
                        'quantity'       => $quantidadeEstornada,
                        'type'           => 'entrada',
                        'description'    => "Estorno: Item removido da Mesa #{$order->table->identifier}",
                    ]);
                }

                $item->delete();
                $order->update(['total_value' => $order->items()->sum('subtotal') ?? 0]);

                return back()->with('success', 'Item removido e estoque estornado!');
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao remover item: ' . $e->getMessage());
        }
    }


    /**
     * 🏁 FINALIZAR MESA (Com blindagem de data e integração ao caixa)
     */
    public function closeOrder(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $table = BarTable::findOrFail($id);

            // 1. BUSCA A SESSÃO DE CAIXA ATIVA
            $session = BarCashSession::where('status', 'open')->first();

            // 🛡️ VALIDAÇÃO DE CAIXA: Verifica se existe caixa aberto
            if (!$session) {
                return redirect()->route('bar.tables.index')
                    ->with('error', '⚠️ Operação Bloqueada: Não há nenhum caixa aberto para processar o recebimento.');
            }

            // 🛡️ VALIDAÇÃO DE DATA: Impede receber pagamento em caixa do dia anterior
            $dataAbertura = Carbon::parse($session->opened_at)->format('Y-m-d');
            $hoje = date('Y-m-d');

            if ($dataAbertura !== $hoje) {
                return redirect()->route('bar.tables.index')
                    ->with('error', '⚠️ CAIXA VENCIDO: O caixa aberto é de ontem (' . Carbon::parse($session->opened_at)->format('d/m') . '). Você deve encerrar o turno antigo na Gestão de Caixa antes de receber pagamentos de mesas hoje.');
            }

            // 2. BUSCA A COMANDA ATIVA
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

            // 3. ATUALIZA A COMANDA PARA PAGA (Carimbando o ID do caixa)
            $order->update([
                'status'              => 'paid',
                'customer_name'       => $request->customer_name,
                'customer_phone'      => $request->customer_phone,
                'payment_method'      => $request->pagamentos,
                'closed_at'           => now(),
                'bar_cash_session_id' => $session->id, // 🔥 A MÁGICA ESTÁ AQUI
            ]);

            // 💰 4. INTEGRAÇÃO COM O CAIXA (Lançamento de Movimentações)
            $pagamentosArray = json_decode($request->pagamentos, true);

            if (is_array($pagamentosArray)) {
                foreach ($pagamentosArray as $pag) {
                    if ($pag['valor'] > 0) {
                        \App\Models\Bar\BarCashMovement::create([
                            'bar_cash_session_id' => $session->id,
                            'user_id'             => auth()->id(),
                            'bar_order_id'        => $order->id,
                            'type'                => 'venda',
                            'payment_method'      => $pag['metodo'],
                            'amount'              => $pag['valor'],
                            'description'         => "Venda Mesa #{$table->identifier}",
                        ]);

                        if ($pag['metodo'] == 'dinheiro') {
                            $session->increment('expected_balance', $pag['valor']);
                        }
                    }
                }
            }

            // ✅ 5. LIBERA A MESA PARA O PRÓXIMO CLIENTE
            $table->update(['status' => 'available']);

            // 6. TRATAMENTO DE CUPOM
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
        return view('bar.tables.receipt', compact('order'));
    }
}
