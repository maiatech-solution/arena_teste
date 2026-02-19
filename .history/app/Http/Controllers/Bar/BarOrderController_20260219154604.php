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
    public function cancelarVenda(Request $request, BarSale $sale)
    {
        // 1. Validar Supervisor
        $supervisor = User::where('email', $request->supervisor_email)->first();

        if (
            !$supervisor || !Hash::check($request->supervisor_password, $supervisor->password) ||
            !in_array($supervisor->role, ['admin', 'gestor'])
        ) {
            return back()->with('error', '❌ Autorização negada: Senha de gestor inválida.');
        }

        // 2. Trava de Caixa
        $caixaAberto = BarCashSession::where('status', 'open')->first();

        if (!$caixaAberto) {
            return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Não existe nenhum caixa aberto agora.');
        }

        // 🛑 Verificação de segurança: A venda deve ser do caixa aberto ou o supervisor assume o risco
        // Se quiser ser rigoroso, descomente as linhas abaixo:
        // if ($sale->bar_cash_session_id != $caixaAberto->id) {
        //    return back()->with('error', '❌ Bloqueado: Venda de um turno já encerrado.');
        // }

        if ($sale->status === 'cancelled' || $sale->status === 'anulada') {
            return back()->with('error', 'Esta venda já está cancelada.');
        }

        try {
            DB::transaction(function () use ($sale, $supervisor, $request, $caixaAberto) {

                // 3. Devolver itens ao estoque
                foreach ($sale->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock_quantity', $item->quantity);

                        BarStockMovement::create([
                            'bar_product_id' => $item->bar_product_id, // Nome da coluna na sua tabela BarSaleItem
                            'user_id' => auth()->id(),
                            'type' => 'input',
                            'quantity' => $item->quantity,
                            'description' => "CANCELAMENTO: Venda #{$sale->id} anulada por {$supervisor->name}.",
                        ]);
                    }
                }

                // 4. Abater do financeiro (Se houver dinheiro envolvido)
                $movimentacoesDinheiro = BarCashMovement::where('bar_sale_id', $sale->id)
                    ->where('payment_method', 'dinheiro')
                    ->sum('amount');

                if ($movimentacoesDinheiro > 0) {
                    $caixaAberto->decrement('expected_balance', $movimentacoesDinheiro);
                }

                // 5. Registrar a saída no caixa atual
                BarCashMovement::create([
                    'bar_cash_session_id' => $caixaAberto->id,
                    'user_id' => auth()->id(),
                    'bar_sale_id' => $sale->id,
                    'type' => 'saida',
                    'payment_method' => $sale->payment_method ?? 'misto',
                    'amount' => $sale->total_value,
                    'description' => "ESTORNO VENDA #{$sale->id}: Cancelada por gestor."
                ]);

                // 6. Atualizar status da venda
                $sale->update(['status' => 'cancelled']);
            });

            return back()->with('success', "✅ Venda #{$sale->id} anulada com sucesso!");

        } catch (\Exception $e) {
            Log::error("Erro ao cancelar venda: " . $e->getMessage());
            return back()->with('error', 'Erro ao cancelar: ' . $e->getMessage());
        }
    }
}
