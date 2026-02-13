<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarOrder;
use App\Models\Bar\BarOrderItem;
use App\Models\Bar\BarSale;
use App\Models\Bar\BarSaleItem;
use App\Models\Bar\BarCashMovement;
use App\Models\Bar\BarCashSession;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarStockMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BarReportController extends Controller
{
    /**
     * DASHBOARD PRINCIPAL DE RELATÓRIOS
     */
    public function index(Request $request)
    {
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = Carbon::parse($mesReferencia)->endOfMonth();

        // 1. Faturamento Consolidado
        $faturamentoMesas = BarOrder::where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate])->sum('total_value');
        $faturamentoPDV = BarSale::where('status', 'paid')->whereBetween('created_at', [$startDate, $endDate])->sum('total_value');
        $faturamentoMensal = $faturamentoMesas + $faturamentoPDV;

        // 2. Itens Vendidos
        $itensMesas = BarOrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate]);
        })->sum('quantity');
        $itensPDV = BarSaleItem::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'paid')->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('quantity');
        $totalItensMes = $itensMesas + $itensPDV;

        // 3. Ticket Médio
        $transacoes = BarOrder::where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate])->count() +
            BarSale::where('status', 'paid')->whereBetween('created_at', [$startDate, $endDate])->count();
        $ticketMedio = $transacoes > 0 ? $faturamentoMensal / $transacoes : 0;

        // 4. Sangrias
        $totalSangriasMes = BarCashMovement::where('type', 'sangria')->whereBetween('created_at', [$startDate, $endDate])->sum('amount');

        return view('bar.reports.index', compact('faturamentoMensal', 'totalItensMes', 'ticketMedio', 'totalSangriasMes'));
    }

    /**
     * RANKING DE PRODUTOS + MARGEM DE LUCRO
     */
    public function products(Request $request)
    {
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = Carbon::parse($mesReferencia)->endOfMonth();

        // 1. Consolida itens de Mesas e PDV (Venda Direta)
        // Note: Em 'bar_sale_items' calculamos (quantidade * preço) como subtotal
        $rankingFinal = DB::table('bar_order_items as oi')
            ->join('bar_orders as o', 'oi.bar_order_id', '=', 'o.id')
            ->select('oi.bar_product_id', 'oi.quantity', 'oi.subtotal')
            ->where('o.status', 'paid')
            ->whereBetween('o.updated_at', [$startDate, $endDate])
            ->unionAll(
                DB::table('bar_sale_items as si')
                    ->join('bar_sales as s', 'si.bar_sale_id', '=', 's.id')
                    ->select(
                        'si.bar_product_id',
                        'si.quantity',
                        DB::raw('(si.quantity * si.price) as subtotal') // 🛠️ Ajuste aqui
                    )
                    ->where('s.status', 'paid')
                    ->whereBetween('s.created_at', [$startDate, $endDate])
            );

        // 2. Agrupa por produto e soma as quantidades/faturamento
        $ranking = DB::table(DB::raw("({$rankingFinal->toSql()}) as combined"))
            ->mergeBindings($rankingFinal)
            ->select(
                'bar_product_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue')
            )
            ->groupBy('bar_product_id')
            ->orderBy('total_qty', 'desc')
            ->get();

        // 3. Processa cálculos de Margem de Lucro para cada item
        foreach ($ranking as $item) {
            $product = \App\Models\Bar\BarProduct::find($item->bar_product_id);

            $item->product = $product;

            // Pega o custo do produto no estoque
            $custoUnitario = $product->purchase_price ?? 0;

            $item->total_cost = $custoUnitario * $item->total_qty;
            $item->total_profit = $item->total_revenue - $item->total_cost;

            $item->margin_percent = $item->total_revenue > 0
                ? ($item->total_profit / $item->total_revenue) * 100
                : 0;
        }

        return view('bar.reports.products', compact('ranking', 'mesReferencia'));
    }

    /**
     * AUDITORIA DE FECHAMENTO DE CAIXA
     */
    public function cashier(Request $request)
    {
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));

        $sessoes = BarCashSession::with('user')
            ->whereMonth('opened_at', Carbon::parse($mesReferencia)->month)
            ->orderBy('opened_at', 'desc')
            ->get();

        return view('bar.reports.cashier', compact('sessoes'));
    }

    /**
     * MEIOS DE PAGAMENTO
     */
    public function payments(Request $request)
    {
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = Carbon::parse($mesReferencia)->endOfMonth();

        $pagamentos = DB::table('bar_orders')
            ->select('payment_method', DB::raw('SUM(total_value) as total'))
            ->where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->unionAll(
                DB::table('bar_sales')
                    ->select('payment_method', DB::raw('SUM(total_value) as total'))
                    ->where('status', 'paid')->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('payment_method')
            )->get();

        return view('bar.reports.payments', compact('pagamentos'));
    }

    /**
     * CONTROLE DE ESTOQUE (MOVIMENTAÇÕES)
     */
    public function movements(Request $request)
    {
        $movimentacoes = BarStockMovement::with(['product', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('bar.reports.movements', compact('movimentacoes'));
    }
}
