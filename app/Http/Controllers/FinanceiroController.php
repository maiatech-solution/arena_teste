<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Reserva;
use App\Models\FinancialTransaction;
use App\Models\Cashier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FinanceiroController extends Controller
{
    /**
     * Dashboard Principal (Hub de Relatórios)
     */
    public function index()
    {
        Log::info('FinanceiroController: Acessando Hub de Relatórios.');

        $mesAtual = now()->month;
        $anoAtual = now()->year;

        $faturamentoMensal = FinancialTransaction::whereMonth('paid_at', $mesAtual)
            ->whereYear('paid_at', $anoAtual)
            ->sum('amount');

        $totalReservasMes = Reserva::whereMonth('date', $mesAtual)
            ->whereYear('date', $anoAtual)
            ->where('is_fixed', false)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
            ->count();

        $canceladasMes = Reserva::whereMonth('date', $mesAtual)
            ->whereYear('date', $anoAtual)
            ->whereIn('status', [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA, Reserva::STATUS_NO_SHOW])
            ->count();

        return view('admin.financeiro.index', compact('faturamentoMensal', 'totalReservasMes', 'canceladasMes'));
    }

    /**
     * Relatório 01: Faturamento Detalhado
     */
    public function relatorioFaturamento(Request $request)
    {
        $dataInicio = $request->input('data_inicio') ? Carbon::parse($request->input('data_inicio'))->startOfDay() : now()->startOfMonth();
        $dataFim = $request->input('data_fim') ? Carbon::parse($request->input('data_fim'))->endOfDay() : now()->endOfDay();

        $transacoes = FinancialTransaction::whereBetween('paid_at', [$dataInicio, $dataFim])
            ->with('reserva')
            ->orderBy('paid_at', 'desc')
            ->get();

        $totaisPorMetodo = $transacoes->groupBy('payment_method')->map(fn($row) => $row->sum('amount'));
        $faturamentoTotal = $transacoes->sum('amount');

        return view('admin.financeiro.relatorio_faturamento', compact('transacoes', 'totaisPorMetodo', 'faturamentoTotal', 'dataInicio', 'dataFim'));
    }

    /**
     * Relatório 02: Histórico de Caixa
     */
    public function relatorioCaixa(Request $request)
    {
        $data = $request->input('data', now()->format('Y-m-d'));

        // 1. Movimentações detalhadas do dia selecionado
        $movimentacoes = FinancialTransaction::whereDate('paid_at', $data)
            ->with(['reserva', 'manager'])
            ->orderBy('paid_at', 'asc')
            ->get();

        // 2. BUSCA O HISTÓRICO DE FECHAMENTOS (Corrigido a setinha -> )
        $cashierHistory = Cashier::with('user')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get(); // <--- O erro estava aqui, mudei de .get() para ->get()

        return view('admin.financeiro.caixa', compact('movimentacoes', 'data', 'cashierHistory'));
    }

    /**
     * Relatório 03: Cancelamentos
     */
    public function relatorioCancelamentos(Request $request)
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);

        $cancelamentos = Reserva::whereIn('status', [Reserva::STATUS_CANCELADA, Reserva::STATUS_NO_SHOW, Reserva::STATUS_REJEITADA])
            ->whereMonth('date', $mes)
            ->whereYear('date', $ano)
            ->with('user')
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.financeiro.cancelamentos', compact('cancelamentos', 'mes', 'ano'));
    }

    /**
     * Relatório 04: Mapa de Ocupação & Histórico
     */
    public function relatorioOcupacao(Request $request)
    {
        $dataInicio = $request->input('data_inicio') ? Carbon::parse($request->input('data_inicio'))->startOfDay() : now()->startOfMonth();
        $dataFim = $request->input('data_fim') ? Carbon::parse($request->input('data_fim'))->endOfDay() : now()->endOfMonth();

        $reservas = Reserva::whereBetween('date', [$dataInicio, $dataFim])
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        return view('admin.financeiro.ocupacao', compact('reservas', 'dataInicio', 'dataFim'));
    }

    /**
     * Relatório 05: Ranking de Clientes (Fidelidade Real)
     */
    public function relatorioRanking()
    {
        $hoje = now()->format('Y-m-d');

        $ranking = Reserva::select(
            'client_name',
            'client_contact',
            DB::raw('SUM(total_paid) as total_gasto'),
            DB::raw("COUNT(CASE WHEN total_paid > 0 AND date <= '$hoje' THEN 1 END) as total_reservas")
        )
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
            ->where('total_paid', '>', 0)
            ->groupBy('client_name', 'client_contact')
            ->orderBy('total_gasto', 'desc')
            ->limit(15)
            ->get();

        return view('admin.financeiro.ranking', compact('ranking'));
    }


    // app/Http/Controllers/FinanceiroController.php

    public static function isCashClosed(string $dateString): bool
    {
        return \App\Models\Cashier::whereDate('date', $dateString)
            ->where('status', 'closed')
            ->exists();
    }

    private function calculateLiquidCash(string $dateString)
    {
        // Removido o (float). O Laravel usará a precisão do banco de dados.
        return FinancialTransaction::whereDate('paid_at', $dateString)->sum('amount');
    }

    private function getDateRange(string $periodo): array
    {
        $now = Carbon::now();
        $start = $now->copy()->startOfDay();
        $end = $now->copy()->endOfDay();

        switch ($periodo) {
            case 'semana':
                $start = $now->copy()->startOfWeek(Carbon::SUNDAY);
                $end = $now->copy()->endOfWeek(Carbon::SATURDAY);
                break;
            case 'mes':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
        }
        return [$start, $end->endOfDay()];
    }


    /**
     * API para verificar o status do caixa em tempo real
     * Usado pelo JavaScript para bloquear agendamentos se o caixa estiver fechado.
     */
    public function getStatus(Request $request)
    {
        try {
            $targetDate = $request->query('date', now()->format('Y-m-d'));
            $hoje = now()->format('Y-m-d');

            // 1. 🔍 BUSCA NO BANCO PRIMEIRO
            // Independente de ser hoje, passado ou futuro, se o caixa existe no banco,
            // o status dele é a palavra final.
            $caixa = Cashier::where('date', $targetDate)->first();

            if ($caixa) {
                return response()->json([
                    'isOpen' => $caixa->status !== 'closed', // Se for 'closed', retorna false
                    'date'   => $targetDate,
                    'status' => $caixa->status
                ]);
            }

            // 2. 🚀 SE NÃO EXISTE NO BANCO, LIBERAMOS O FUTURO
            // Se chegou aqui e a data é futura, permitimos clicar pois o caixa ainda será criado.
            if ($targetDate > $hoje) {
                return response()->json([
                    'isOpen' => true,
                    'date'   => $targetDate,
                    'status' => 'not_created'
                ]);
            }

            // 3. SE É HOJE OU PASSADO E NÃO TEM REGISTRO
            // Permitimos o clique para que o primeiro lançamento crie o caixa (comportamento padrão)
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
}
