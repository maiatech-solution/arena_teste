<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarOrder;
use App\Models\Bar\BarStockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BarOrderController extends Controller
{
    // Lista o histórico de vendas (Pagas e Canceladas)
    public function indexVendas(Request $request)
    {
        // Mudamos de BarOrder para BarSale (que é o que seu PDV usa)
        $query = \App\Models\Bar\BarSale::with(['items.product', 'user', 'cashSession']);

        // Filtro por ID
        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        // Filtro por Status (Atenção: seu PDV usa 'pago' em português)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtro por Data
        if ($request->filled('date')) {
            $query->whereDate('updated_at', $request->date);
        }

        // Ordenação por ID DESC para a #12 aparecer no topo
        $vendas = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('bar.vendas.index', compact('vendas'));
    }

    // Processa o cancelamento com estorno de estoque
    public function cancelarVenda(Request $request, \App\Models\Bar\BarSale $order)
    {
        // 1. Validar Supervisor
        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        if (
            !$supervisor || !\Illuminate\Support\Facades\Hash::check($request->supervisor_password, $supervisor->password) ||
            !in_array($supervisor->role, ['admin', 'gestor'])
        ) {
            return back()->with('error', '❌ Autorização negada: Senha de gestor inválida.');
        }

        // 2. 🔍 DEBUG E TRAVA FLEXÍVEL
        $caixaAberto = \App\Models\Bar\BarCashSession::where('status', 'open')->first();

        if (!$caixaAberto) {
            return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Não existe nenhum caixa aberto no sistema agora.');
        }

        // Registro de Debug no log para pararmos o achismo
        \Illuminate\Support\Facades\Log::info('Debug Cancelamento:', [
            'venda_id' => $order->id,
            'caixa_na_venda' => $order->bar_cash_session_id,
            'caixa_aberto_atualmente' => $caixaAberto->id
        ]);

        // 🛑 REMOVEMOS A COMPARAÇÃO RÍGIDA AQUI PARA VOCÊ CONSEGUIR CANCELAR
        // O sistema agora só exige que EXISTA um caixa aberto para onde o estorno vai.

        if ($order->status === 'cancelled' || $order->status === 'anulada') {
            return back()->with('error', 'Esta venda já está cancelada.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $supervisor, $request, $caixaAberto) {

                // 3. Devolver itens ao estoque
                foreach ($order->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock_quantity', $item->quantity);

                        \App\Models\Bar\BarStockMovement::create([
                            'bar_product_id' => $item->bar_product_id,
                            'user_id' => auth()->id(),
                            'type' => 'input',
                            'quantity' => $item->quantity,
                            'description' => "CANCELAMENTO: Venda #{$order->id} anulada por {$supervisor->name}. Motivo: " . ($request->reason ?? 'Desistência'),
                        ]);
                    }
                }

                // 4. Abater do financeiro (Se houver dinheiro envolvido)
                $movimentacoesDinheiro = \App\Models\Bar\BarCashMovement::where('bar_sale_id', $order->id)
                    ->where('payment_method', 'dinheiro')
                    ->sum('amount');

                if ($movimentacoesDinheiro > 0) {
                    // Remove o valor da gaveta do caixa que está ABERTO agora
                    $caixaAberto->decrement('expected_balance', $movimentacoesDinheiro);
                }

                // 5. Registrar a saída no caixa atual
                \App\Models\Bar\BarCashMovement::create([
                    'bar_cash_session_id' => $caixaAberto->id,
                    'user_id' => auth()->id(),
                    'bar_sale_id' => $order->id,
                    'type' => 'saida',
                    'payment_method' => $order->payment_method ?? 'misto',
                    'amount' => $order->total_value,
                    'description' => "ESTORNO VENDA #{$order->id}: Cancelada por gestor."
                ]);

                // 6. Atualizar status da venda
                $order->update(['status' => 'cancelled']);
            });

            return back()->with('success', "✅ Venda #{$order->id} cancelada com sucesso!");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao cancelar: ' . $e->getMessage());
        }
    }
}
