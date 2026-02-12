<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarCashSession;
use App\Models\Bar\BarOrder;
use App\Models\Bar\BarOrderItem;
use App\Models\Bar\BarCashMovement;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BarReportController extends Controller
{
    public function index(request $request)
    {
        // 1. Definir o período (Mês Atual por padrão)
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = Carbon::parse($mesReferencia)->endOfMonth();

        // 2. KPI: Faturamento Mensal (Apenas pedidos finalizados)
        $faturamentoMensal = BarOrder::where('status', 'paid')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('total_value');

        // 3. KPI: Total de Itens Vendidos
        $totalItensMes = BarOrderItem::whereHas('order', function($q) use ($startDate, $endDate) {
            $q->where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate]);
        })->sum('quantity');

        // 4. KPI: Ticket Médio por Mesa
        $qtdPedidos = BarOrder::where('status', 'paid')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();
        $ticketMedio = $qtdPedidos > 0 ? $faturamentoMensal / $qtdPedidos : 0;

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
     * Exemplo de Relatório de Ranking de Produtos
     */
    public function products(Request $request)
    {
        $mesReferencia = $request->input('mes_referencia', now()->format('Y-m'));
        $startDate = Carbon::parse($mesReferencia)->startOfMonth();
        $endDate = Carbon::parse($mesReferencia)->endOfMonth();

        $ranking = BarOrderItem::select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_cash'))
            ->whereHas('order', function($q) use ($startDate, $endDate) {
                $q->where('status', 'paid')->whereBetween('updated_at', [$startDate, $endDate]);
            })
            ->with('product') // Assumindo que a relação existe
            ->groupBy('product_id')
            ->orderBy('total_qty', 'desc')
            ->take(15)
            ->get();

        return view('bar.reports.products', compact('ranking'));
    }
}
