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
    public function cancelarVenda(Request $request, \App\Models\Bar\BarSale $sale)
    {
        // 1. Validar Supervisor
        $supervisor = \App\Models\User::where('email', $request->supervisor_email)->first();

        if (
            !$supervisor || !\Illuminate\Support\Facades\Hash::check($request->supervisor_password, $supervisor->password) ||
            !in_array($supervisor->role, ['admin', 'gestor'])
        ) {
            return back()->with('error', '❌ Autorização negada: Senha de gestor inválida.');
        }

        // 2. Localizar Caixa Aberto
        $caixaAberto = \App\Models\Bar\BarCashSession::where('status', 'open')->first();

        if (!$caixaAberto) {
            return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Não existe um caixa aberto no momento.');
        }

        // 3. Verificar se a venda já está cancelada
        if ($sale->status === 'cancelled' || $sale->status === 'anulada') {
            return back()->with('error', 'Esta venda já está anulada.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($sale, $supervisor, $request, $caixaAberto) {

                // 4. Devolver itens ao estoque
                foreach ($sale->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock_quantity', $item->quantity);

                        \App\Models\Bar\BarStockMovement::create([
                            'bar_product_id' => $item->bar_product_id,
                            'user_id' => auth()->id(),
                            'type' => 'input',
                            'quantity' => $item->quantity,
                            'description' => "ESTORNO: Venda PDV #{$sale->id} anulada por {$supervisor->name}.",
                        ]);
                    }
                }

                // 5. Abater do financeiro (Dinheiro em Gaveta)
                // 💡 AQUI ESTAVA O ERRO: Usamos 'bar_order_id' que é o nome real da sua coluna
                $movimentacoesDinheiro = \App\Models\Bar\BarCashMovement::where('bar_order_id', $sale->id)
                    ->where('payment_method', 'dinheiro')
                    ->sum('amount');

                if ($movimentacoesDinheiro > 0) {
                    $caixaAberto->decrement('expected_balance', $movimentacoesDinheiro);
                }

                // 6. Registrar a saída no caixa (Estorno)
                \App\Models\Bar\BarCashMovement::create([
                    'bar_cash_session_id' => $caixaAberto->id,
                    'user_id' => auth()->id(),
                    'bar_order_id' => $sale->id, // 👈 Usando a coluna correta da sua tabela
                    'type' => 'estorno',         // 👈 Usando o tipo correto do seu ENUM
                    'payment_method' => $sale->payment_method ?? 'misto',
                    'amount' => $sale->total_value,
                    'description' => "ESTORNO PDV #{$sale->id}: Cancelada por gestor."
                ]);

                // 7. Atualizar status da venda
                $sale->update(['status' => 'cancelled']);
            });

            return back()->with('success', "✅ Venda #{$sale->id} anulada com sucesso!");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro fatal: ' . $e->getMessage());
        }
    }
}
