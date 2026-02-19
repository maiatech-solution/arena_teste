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

        return view('bar.reports.index', compact('faturamentoMensal', 'totalItensMes', 'ticketMedio', 'totalSangriasMes', 'mesReferencia'));
    }

    /**
     * RANKING DE PRODUTOS + MARGEM DE LUCRO
     */
    public function products(Request $request)
    {
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = Carbon::parse($mesReferencia)->endOfMonth();

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
                        DB::raw('(si.quantity * si.price_at_sale) as subtotal')
                    )
                    ->where('s.status', 'paid')
                    ->whereBetween('s.created_at', [$startDate, $endDate])
            );

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

        foreach ($ranking as $item) {
            $product = BarProduct::find($item->bar_product_id);
            $item->product = $product;
            $custoUnitario = $product->purchase_price ?? 0;
            $item->total_cost = $custoUnitario * $item->total_qty;
            $item->total_profit = $item->total_revenue - $item->total_cost;
            $item->margin_percent = $item->total_revenue > 0 ? ($item->total_profit / $item->total_revenue) * 100 : 0;
        }

        return view('bar.reports.products', compact('ranking', 'mesReferencia'));
    }

    /**
     * AUDITORIA DE FECHAMENTO DE CAIXA
     */
    public function cashier(Request $request)
    {
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = \Carbon\Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = \Carbon\Carbon::parse($mesReferencia)->endOfMonth();

        $sessoes = \App\Models\Bar\BarCashSession::with('user')
            ->whereBetween('opened_at', [$startDate, $endDate])
            ->orderBy('opened_at', 'desc')
            ->get();

        foreach ($sessoes as $sessao) {
            // 1. Soma Mesas vinculadas a este ID de sessão
            $vendasMesas = \App\Models\Bar\BarOrder::where('bar_cash_session_id', $sessao->id)
                ->where('status', 'paid')
                ->sum('total_value');

            // 2. Soma PDV vinculados a este ID de sessão
            $vendasPDV = \App\Models\Bar\BarSale::where('bar_cash_session_id', $sessao->id)
                ->where('status', 'pago')
                ->sum('total_value');

            // 3. Movimentações de caixa (Sangria/Reforço)
            $movimentacoes = \App\Models\Bar\BarCashMovement::where('bar_cash_session_id', $sessao->id)->get();

            // 🔥 AQUI ESTAVA O ERRO: Mudamos de 'suprimento' para 'reforco'
            $reforcos = $movimentacoes->where('type', 'reforco')->sum('amount');
            $sangrias = $movimentacoes->where('type', 'sangria')->sum('amount');

            // 4. Resultado Final Unificado
            $sessao->vendas_turno = $vendasMesas + $vendasPDV;

            // FÓRMULA CORRIGIDA: Total esperado = Fundo + Vendas + Reforços - Sangrias
            $sessao->total_sistema_esperado = $sessao->opening_balance + $sessao->vendas_turno + $reforcos - $sangrias;
        }

        return view('bar.reports.cashier', compact('sessoes', 'mesReferencia'));
    }

    /**
     * RESUMO DE VENDAS DIÁRIAS
     */
    public function daily(Request $request)
    {
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = Carbon::parse($mesReferencia)->endOfMonth();

        $vendasMesas = BarOrder::where('status', 'paid')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('SUM(total_value) as total'))
            ->groupBy('date')->get();

        $vendasPDV = BarSale::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_value) as total'))
            ->groupBy('date')->get();

        $datas = [];
        foreach ($vendasMesas as $v) {
            $datas[$v->date]['mesas'] = $v->total;
        }
        foreach ($vendasPDV as $v) {
            $datas[$v->date]['pdv'] = $v->total;
        }
        ksort($datas);

        return view('bar.reports.daily', compact('datas', 'mesReferencia'));
    }

    /**
     * MEIOS DE PAGAMENTO
     */
    public function payments(Request $request)
    {
        // 1. Define o mês de referência (Ex: 2026-02)
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = \Carbon\Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = \Carbon\Carbon::parse($mesReferencia)->endOfMonth();

        // 2. Buscamos os pagamentos diretamente da tabela de pagamentos
        // Nota: Ajuste o nome da tabela 'bar_payments' se o seu modelo usar outro nome
        $pagamentos = DB::table('bar_payments')
            ->select(
                'method as payment_method', // Padronizamos o nome para a View
                DB::raw('SUM(value) as total'),
                DB::raw('COUNT(*) as qtd')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('method')
            ->get();

        return view('bar.reports.payments', compact('pagamentos', 'mesReferencia'));
    }

    /**
     * DESCONTOS E CANCELAMENTOS (LOGS)
     */
    public function cancelations(Request $request)
    {
        // Aqui você pode buscar ordens canceladas ou com descontos > 0
        $cancelamentos = BarOrder::where('status', 'canceled')
            ->orWhere('discount_value', '>', 0)
            ->orderBy('updated_at', 'desc')->paginate(20);

        return view('bar.reports.cancelations', compact('cancelamentos'));
    }

    /**
     * CONTROLE DE ESTOQUE (MOVIMENTAÇÕES)
     */
    /**
     * CONTROLE DE ESTOQUE (MOVIMENTAÇÕES) COM FILTROS
     */
    public function movements(Request $request)
    {
        // 1. Query para o Histórico de Movimentações (Tabela)
        $query = BarStockMovement::with(['product.category', 'user']);

        // Filtro por Tipo (Entrada ou Saída)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filtro por Data Específica
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Filtro por Busca de Nome de Produto
        if ($request->filled('search')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // Paginação das movimentações
        $movimentacoes = $query->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        // 2. 🔥 NOVIDADE: Busca a Posição Atual de todos os itens (Resumo do Topo)
        // Ordenamos pelos que têm menos estoque primeiro para destacar o que precisa comprar
        $inventorySummary = \App\Models\Bar\BarProduct::with('category')
            ->orderBy('stock_quantity', 'asc')
            ->get();

        return view('bar.reports.movements', compact('movimentacoes', 'inventorySummary'));
    }
}
