<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

// Models exclusivos do Módulo Bar
use App\Models\Bar\BarTable;
use App\Models\Bar\BarProduct;
use App\Models\Bar\BarOrder;

class BarDashboardController extends Controller
{
    /**
     * Exibe a Central de Comando (Dashboard) do Bar.
     */
    public function index()
    {
        // 🚀 Ajustamos 'mesas_ocupadas' para 'mesas_abertas' para sincronizar com a View
        $stats = [
            'mesas_abertas'    => BarTable::where('status', 'occupied')->count(),
            'estoque_critico'  => BarProduct::whereRaw('stock_quantity <= min_stock')->count(),
            'vendas_hoje'      => BarOrder::whereDate('created_at', Carbon::today())
                                          ->where('status', 'paid')
                                          ->sum('total_value'),
        ];

        return view('bar.dashboard', compact('stats'));
    }
}
