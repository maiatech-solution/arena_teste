<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarSale;
use App\Models\Bar\BarOrder;
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
    /**
     * 🛒 HISTÓRICO PDV (Venda Direta / Balcão)
     */
    public function indexPdv(Request $request)
    {
        $query = BarSale::with(['items.product', 'user', 'cashSession']);

        if ($request->filled('id')) $query->where('id', $request->id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date')) $query->whereDate('updated_at', $request->date);

        $vendas = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('bar.vendas.pdv', compact('vendas'));
    }

    public function cancelarPdv(Request $request, BarSale $sale)
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
            return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Não existe um caixa aberto.');
        }

        if ($sale->bar_cash_session_id != $caixaAberto->id) {
            return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Venda pertence a um turno de caixa já encerrado.');
        }

        // Verifica se já está cancelada (usando os dois termos possíveis)
        if (in_array($sale->status, ['cancelado', 'cancelled', 'anulada'])) {
            return back()->with('error', 'Esta venda já está cancelada.');
        }

        try {
            DB::transaction(function () use ($sale, $supervisor, $request, $caixaAberto) {

                // 3. Devolver Itens ao Estoque
                foreach ($sale->items as $item) {
                    if ($item->product) {
                        $item->product->increment('stock_quantity', $item->quantity);

                        BarStockMovement::create([
                            'bar_product_id' => $item->bar_product_id,
                            'user_id'        => auth()->id(),
                            'type'           => 'input',
                            'quantity'       => $item->quantity,
                            'description'    => "CANCELAMENTO PDV #{$sale->id}: Por {$supervisor->name}.",
                        ]);
                    }
                }

                // 4. Estorno Financeiro (Abate do saldo esperado se for dinheiro)
                if ($sale->payment_method === 'dinheiro') {
                    $caixaAberto->decrement('expected_balance', $sale->total_value);
                }

                // 5. Registrar Movimentação no Caixa
                // bar_order_id vai NULL porque PDV não é MESA (evita erro de Foreign Key)
                BarCashMovement::create([
                    'bar_cash_session_id' => $caixaAberto->id,
                    'user_id'             => auth()->id(),
                    'bar_order_id'        => null,
                    'type'                => 'estorno',
                    'payment_method'      => $sale->payment_method ?? 'misto',
                    'amount'              => $sale->total_value,
                    'description'         => "ESTORNO PDV #{$sale->id}: Cancelada por gestor."
                ]);

                // 6. Atualizar status da venda
                // 🔥 AJUSTE: Usando 'cancelado' (9 letras) em vez de 'cancelled'
                // para evitar o erro SQLSTATE[01000] Data truncated
                $sale->update(['status' => 'cancelado']);
            });

            return back()->with('success', "✅ Venda PDV #{$sale->id} anulada com sucesso!");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao cancelar: ' . $e->getMessage());
        }
    }

    /**
     * 🍽️ HISTÓRICO MESAS (Comandas)
     */
    public function indexMesas(Request $request)
    {
        $query = BarOrder::with(['items.product', 'user', 'cashSession'])
            ->whereIn('status', ['paid', 'cancelled']);

        if ($request->filled('id')) $query->where('id', $request->id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date')) $query->whereDate('updated_at', $request->date);

        $vendas = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('bar.vendas.mesas', compact('vendas'));
    }

    public function cancelarMesa(Request $request, BarOrder $order)
    {
        // 1. Validar Supervisor
        if (!$request->supervisor_email) {
            return back()->with('error', '❌ Erro técnico: O e-mail do supervisor não foi enviado pelo formulário.');
        }

        $supervisor = User::where('email', $request->supervisor_email)->first();

        if (
            !$supervisor ||
            !Hash::check($request->supervisor_password, $supervisor->password) ||
            !in_array($supervisor->role, ['admin', 'gestor'])
        ) {
            return back()->with('error', '❌ Autorização negada: E-mail ou Senha de gestor inválidos.');
        }

        // 2. Trava de Caixa
        $caixaAberto = BarCashSession::where('status', 'open')->first();
        if (!$caixaAberto) {
            return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Não existe um caixa aberto.');
        }

        if ($order->bar_cash_session_id != $caixaAberto->id) {
            return back()->with('error', '❌ OPERAÇÃO BLOQUEADA: Esta comanda pertence a um turno de caixa já encerrado.');
        }

        try {
            DB::transaction(function () use ($order, $supervisor, $request, $caixaAberto) {

                // 3. Devolver itens ao estoque
                foreach ($order->items as $item) {
                    $productId = $item->bar_product_id ?? $item->product_id;

                    if ($productId && $item->product) {
                        $item->product->increment('stock_quantity', $item->quantity);

                        BarStockMovement::create([
                            'bar_product_id' => $productId,
                            'user_id'        => auth()->id(),
                            'type'           => 'input',
                            'quantity'       => $item->quantity,
                            'description'    => "CANCELAMENTO MESA #{$order->id}: Autorizado por {$supervisor->name}.",
                        ]);
                    }
                }

                // 4. Registrar Estorno no Caixa
                // 🔥 Ajuste: Concatenando o motivo na descrição, já que a tabela não tem a coluna 'reason'
                $motivoDesc = $request->reason ? " | MOTIVO: " . $request->reason : " | MOTIVO: Não informado";

                BarCashMovement::create([
                    'bar_cash_session_id' => $caixaAberto->id,
                    'user_id'             => auth()->id(),
                    'bar_order_id'        => $order->id,
                    'type'                => 'estorno',
                    'payment_method'      => $order->payment_method ?? 'misto',
                    'amount'              => $order->total_value,
                    'description'         => "ESTORNO MESA #{$order->id}" . $motivoDesc
                ]);

                // 5. Atualizar status no banco
                $order->update(['status' => 'cancelled']);
            });

            return back()->with('success', "✅ Comanda #{$order->id} anulada com sucesso!");
        } catch (\Exception $e) {
            Log::error("Erro ao cancelar mesa #{$order->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao cancelar: ' . $e->getMessage());
        }
    }
}
