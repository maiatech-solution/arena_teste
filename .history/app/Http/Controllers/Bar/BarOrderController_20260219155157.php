<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarSale; // Importação correta
use App\Models\Bar\BarStockMovement;
use App\Models\Bar\BarCashSession;
use App\Models\Bar\BarCashMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BarOrderController extends Controller
{
    public function indexVendas(Request $request)
    {
        $query = BarSale::with(['items.product', 'user', 'cashSession']);

        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('updated_at', $request->date);
        }

        $vendas = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('bar.vendas.index', compact('vendas'));
    }

    // 🔴 CORREÇÃO AQUI: Mudamos de $order para $sale para bater com a rota {sale}
   public function cancelarVenda(Request $request, $id) // Recebemos apenas o ID para buscar manualmente
{
    // 1. Validar Supervisor
    $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

    if (!$supervisor || !\Illuminate\Support\Facades\Hash::check($request->supervisor_password, $supervisor->password) ||
        !in_array($supervisor->role, ['admin', 'gestor'])) {
        return back()->with('error', '❌ Autorização negada: Senha de gestor inválida.');
    }

    // 2. Identificar a Origem da Venda (PDV ou Mesa)
    // Tenta primeiro no PDV (BarSale), se não achar, tenta na Mesa (BarOrder)
    $venda = \App\Models\Bar\BarSale::find($id);
    $tipoVenda = 'pdv';

    if (!$venda) {
        $venda = \App\Models\Bar\BarOrder::find($id);
        $tipoVenda = 'mesa';
    }

    if (!$venda) {
        return back()->with('error', '❌ Erro: Venda não encontrada em nenhuma base de dados.');
    }

    // 3. Localizar Caixa Aberto
    $caixaAberto = \App\Models\Bar\BarCashSession::where('status', 'open')->first();

    if (!$caixaAberto) {
        return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Não existe um caixa aberto para processar o estorno.');
    }

    // 4. Trava de Segurança por Turno
    if ($venda->bar_cash_session_id != $caixaAberto->id) {
        return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Esta venda pertence a um turno de caixa já encerrado.');
    }

    if ($venda->status === 'cancelled' || $venda->status === 'anulada') {
        return back()->with('error', 'Esta venda já está anulada.');
    }

    try {
        \Illuminate\Support\Facades\DB::transaction(function () use ($venda, $supervisor, $request, $caixaAberto, $tipoVenda) {

            // 5. Devolver itens ao estoque
            // O relacionamento 'items' funciona para ambos, mas as colunas podem variar
            foreach ($venda->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_quantity', $item->quantity);

                    \App\Models\Bar\BarStockMovement::create([
                        'bar_product_id' => $item->bar_product_id ?? $item->product_id,
                        'user_id' => auth()->id(),
                        'type' => 'input',
                        'quantity' => $item->quantity,
                        'description' => "ESTORNO (".strtoupper($tipoVenda).") #{$venda->id}: Anulada por {$supervisor->name}.",
                    ]);
                }
            }

            // 6. Abater do financeiro (Expected Balance)
            // Buscamos no caixa usando a coluna 'bar_order_id' que você tem no banco
            $movimentacoesDinheiro = \App\Models\Bar\BarCashMovement::where('bar_order_id', $venda->id)
                ->where('payment_method', 'dinheiro')
                ->sum('amount');

            if ($movimentacoesDinheiro > 0) {
                $caixaAberto->decrement('expected_balance', $movimentacoesDinheiro);
            }

            // 7. Registrar a saída (Estorno) no Caixa
            \App\Models\Bar\BarCashMovement::create([
                'bar_cash_session_id' => $caixaAberto->id,
                'user_id' => auth()->id(),
                'bar_order_id' => $venda->id,
                'type' => 'estorno',
                'payment_method' => $venda->payment_method ?? 'misto',
                'amount' => $venda->total_value,
                'description' => "ESTORNO ".strtoupper($tipoVenda)." #{$venda->id}: Cancelada por gestor."
            ]);

            // 8. Atualizar status da venda
            $venda->update(['status' => 'cancelled']);
        });

        return back()->with('success', "✅ Venda #{$id} ({$tipoVenda}) anulada com sucesso!");

    } catch (\Exception $e) {
        return back()->with('error', 'Erro fatal: ' . $e->getMessage());
    }
}
}
