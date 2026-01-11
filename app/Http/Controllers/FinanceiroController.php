<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Reserva;
use App\Models\FinancialTransaction;
use App\Models\Cashier;
use App\Models\Arena;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceiroController extends Controller
{
    /**
     * Dashboard Principal (Hub de Relatórios) - MULTIQUADRA
     */
    public function index(Request $request)
    {
        $arenaId = $request->get('arena_id');
        $referencia = $request->get('mes_referencia', now()->format('Y-m'));

        $dataFiltro = Carbon::parse($referencia . '-01');
        $mes = $dataFiltro->month;
        $ano = $dataFiltro->year;

        // 💰 1. Faturamento Filtrado
        $faturamentoMensal = FinancialTransaction::whereMonth('paid_at', $mes)
            ->whereYear('paid_at', $ano)
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->sum('amount');

        // 📅 2. Ocupação Filtrada
        $totalReservasMes = Reserva::whereMonth('date', $mes)
            ->whereYear('date', $ano)
            ->where('is_fixed', false)
            ->whereIn('status', [
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_CONCLUIDA,
                Reserva::STATUS_LANCADA_CAIXA
            ])
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->count();

        // 🚫 3. Faltas Filtradas (No-Show)
        $canceladasMes = Reserva::whereMonth('date', $mes)
            ->whereYear('date', $ano)
            ->where('status', Reserva::STATUS_NO_SHOW)
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->count();

        $arenas = Arena::all();

        return view('admin.financeiro.index', compact(
            'faturamentoMensal',
            'totalReservasMes',
            'canceladasMes',
            'arenas',
            'dataFiltro'
        ));
    }

    /**
     * Relatório 01: Faturamento Detalhado (Com Arena, Busca, Paginação e Fluxo)
     */
    public function relatorioFaturamento(Request $request)
    {
        $arenaId = $request->get('arena_id');
        $search = $request->get('search');
        $fluxo = $request->get('fluxo'); // 🟢 Novo: entrada, saida ou null

        $dataInicio = $request->input('data_inicio')
            ? Carbon::parse($request->input('data_inicio'))->startOfDay()
            : now()->startOfMonth();

        $dataFim = $request->input('data_fim')
            ? Carbon::parse($request->input('data_fim'))->endOfDay()
            : now()->endOfDay();

        // Início da Query Base
        $query = FinancialTransaction::whereBetween('paid_at', [$dataInicio, $dataFim])
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->with(['reserva', 'arena']);

        // 🟢 FILTRO DE FLUXO (Trabalha sobre o valor do amount)
        if ($fluxo === 'entrada') {
            $query->where('amount', '>', 0);
        } elseif ($fluxo === 'saida') {
            $query->where('amount', '<', 0);
        }

        // 🔍 Filtro por Nome do Cliente ou ID da Reserva
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('reserva', function ($sub) use ($search) {
                    $sub->where('client_name', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            });
        }

        // 📊 UNIFICAÇÃO DE CASH E MONEY NOS TOTAIS
        $queryParaTotais = clone $query;
        $transacoesParaTotais = $queryParaTotais->get();

        // Agrupamos transformando 'money' em 'cash' para somar no mesmo card
        $totaisPorMetodo = $transacoesParaTotais->groupBy(function ($item) {
            $metodo = strtolower($item->payment_method);
            return ($metodo === 'money') ? 'cash' : $metodo;
        })->map(fn($row) => $row->sum('amount'));

        $faturamentoTotal = $transacoesParaTotais->sum('amount');

        // 📄 Tabela Paginada
        $transacoes = $query->orderBy('paid_at', 'desc')->paginate(30)->withQueryString();

        return view('admin.financeiro.relatorio_faturamento', compact(
            'transacoes',
            'totaisPorMetodo',
            'faturamentoTotal',
            'dataInicio',
            'dataFim',
            'fluxo' // Enviado para manter o select preenchido na view
        ));
    }

    /**
     * Relatório 02: Histórico de Caixa (Isolado por Arena)
     */
    public function relatorioCaixa(Request $request)
    {
        $arenaId = $request->get('arena_id');
        $data = $request->input('data', now()->format('Y-m-d'));

        $movimentacoes = FinancialTransaction::whereDate('paid_at', $data)
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->with(['reserva', 'manager', 'arena'])
            ->orderBy('paid_at', 'asc')
            ->get();

        $cashierHistory = Cashier::with(['user', 'arena'])
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        return view('admin.financeiro.caixa', compact('movimentacoes', 'data', 'cashierHistory', 'arenaId'));
    }

    /**
     * Relatório 03: Cancelamentos e No-Show
     */
    public function relatorioCancelamentos(Request $request)
    {
        $arenaId = $request->get('arena_id');
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        $cancelamentos = Reserva::whereIn('status', [Reserva::STATUS_CANCELADA, Reserva::STATUS_NO_SHOW, Reserva::STATUS_REJEITADA])
            ->whereMonth('date', $mes)
            ->whereYear('date', $ano)
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->with(['user', 'arena'])
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.financeiro.cancelamentos', compact('cancelamentos', 'mes', 'ano'));
    }

    /**
     * Relatório 05: Ranking de Clientes (Global ou por Unidade)
     */
    public function relatorioRanking(Request $request)
    {
        $arenaId = $request->get('arena_id');
        $hoje = now()->format('Y-m-d');

        $ranking = Reserva::select(
            'client_name',
            'client_contact',
            'user_id',
            DB::raw('SUM(total_paid) as total_gasto'),
            DB::raw("COUNT(CASE WHEN total_paid > 0 AND date <= '$hoje' THEN 1 END) as total_reservas")
        )
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
            ->where('total_paid', '>', 0)
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->groupBy('client_name', 'client_contact', 'user_id')
            ->orderBy('total_gasto', 'desc')
            ->limit(15)
            ->get();

        return view('admin.financeiro.ranking', compact('ranking'));
    }

    public static function isCashClosed(string $dateString, $arenaId = null): bool
    {
        $query = Cashier::whereDate('date', $dateString)
            ->where('status', 'closed');

        if ($arenaId) {
            $query->where('arena_id', $arenaId);
        }

        return $query->exists();
    }

    public function getStatus(Request $request)
    {
        try {
            $targetDate = $request->query('date', now()->format('Y-m-d'));
            $arenaId = $request->query('arena_id');
            $hoje = now()->format('Y-m-d');

            $caixa = Cashier::where('date', $targetDate)
                ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
                ->first();

            if ($caixa) {
                return response()->json([
                    'isOpen' => $caixa->status !== 'closed',
                    'date'   => $targetDate,
                    'status' => $caixa->status,
                    'arena'  => $caixa->arena_id
                ]);
            }

            return response()->json([
                'isOpen' => true,
                'date'   => $targetDate,
                'status' => 'not_created'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar status do caixa: ' . $e->getMessage());
            return response()->json(['isOpen' => true], 200);
        }
    }

    /**
     * 📊 Relatório de Ocupação e Histórico de Uso
     */
    public function relatorioOcupacao(Request $request)
    {
        // 1. Definição do Período (Padrão: Últimos 7 dias até hoje)
        $dataInicio = $request->has('data_inicio')
            ? Carbon::parse($request->data_inicio)
            : now()->subDays(7);

        $dataFim = $request->has('data_fim')
            ? Carbon::parse($request->data_fim)
            : now();

        $arenaId = $request->arena_id;

        // 2. Consulta de Reservas Confirmadas ou Concluídas no período
        $reservas = Reserva::with(['arena', 'user'])
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, 'completed', 'no_show'])
            ->whereBetween('date', [$dataInicio->format('Y-m-d'), $dataFim->format('Y-m-d')])
            ->when($arenaId, function ($q) use ($arenaId) {
                return $q->where('arena_id', $arenaId);
            })
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        // 3. Retorno para a View
        return view('admin.financeiro.ocupacao', [
            'reservas' => $reservas,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim
        ]);
    }
}
