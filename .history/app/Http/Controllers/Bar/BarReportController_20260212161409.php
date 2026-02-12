<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarOrder;
use App\Models\Bar\BarOrderItem;
use App\Models\Bar\BarSale;
use App\Models\Bar\BarSaleItem;
use App\Models\Bar\BarCashMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BarReportController extends Controller
{
    public function index(Request $request)
    {
        // 1. Definir o período (Mês Atual por padrão)
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = Carbon::parse($mesReferencia)->endOfMonth();

        // 2. KPI: Faturamento Mensal Consolidado (Mesas + PDV)
        $faturamentoMesas = BarOrder::where('status', 'paid')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('total_value');

        $faturamentoPDV = BarSale::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_value');

        $faturamentoMensal = $faturamentoMesas + $faturamentoPDV;

        // 3. KPI: Total de Itens Vendidos (Mesas + PDV)
        $itensMesas = BarOrderItem::whereHas('order', function($q) use ($startDate, $endDate) {
            $q->where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate]);
        })->sum('quantity');

        $itensPDV = BarSaleItem::whereHas('sale', function($q) use ($startDate, $endDate) {
            $q->where('status', 'paid')->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('quantity');

        $totalItensMes = $itensMesas + $itensPDV;

        // 4. KPI: Ticket Médio Consolidado
        $qtdPedidos = BarOrder::where('status', 'paid')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        $qtdVendasPDV = BarSale::where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalTransacoes = $qtdPedidos + $qtdVendasPDV;
        $ticketMedio = $totalTransacoes > 0 ? $faturamentoMensal / $totalTransacoes : 0;

        // 5. KPI: Total de Sangrias no Mês
        $totalSangriasMes = BarCashMovement::where('type', 'sangria')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        return view('bar.reports.index', compact(
            'faturamentoMensal',
            'totalItensMes',
            'ticketMedio',
            'totalSangriasMes'
        ));
    }

    /**
     * Relatório de Ranking de Produtos Consolidado
     */
    public function products(Request $request)
    {
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = Carbon::parse($mesReferencia)->endOfMonth();

        // Ranking nas Mesas
        $rankingMesas = BarOrderItem::select('bar_product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_cash'))
            ->whereHas('order', function($q) use ($startDate, $endDate) {
                $q->where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate]);
            })
            ->groupBy('bar_product_id');

        // Ranking no PDV e Unificação (Query Builder puro para performance no UNION)
        $rankingFinal = DB::table('bar_order_items as oi')
            ->join('bar_orders as o', 'oi.bar_order_id', '=', 'o.id')
            ->select('oi.bar_product_id', 'oi.quantity', 'oi.subtotal')
            ->where('o.status', 'paid')
            ->whereBetween('o.updated_at', [$startDate, $endDate])
            ->unionAll(
                DB::table('bar_sale_items as si')
                ->join('bar_sales as s', 'si.bar_sale_id', '=', 's.id')
                ->select('si.bar_product_id', 'si.quantity', 'si.subtotal')
                ->where('s.status', 'paid')
                ->whereBetween('s.created_at', [$startDate, $endDate])
            );

        $ranking = DB::table(DB::raw("({$rankingFinal->toSql()}) as combined"))
            ->mergeBindings($rankingFinal)
            ->select('bar_product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_cash'))
            ->groupBy('bar_product_id')
            ->orderBy('total_qty', 'desc')
            ->take(15)
            ->get();

        // Mapear os nomes dos produtos
        foreach ($ranking as $item) {
            $item->product = \App\Models\Bar\BarProduct::find($item->bar_product_id);
        }

        return view('bar.reports.products', compact('ranking'));
    }
}
