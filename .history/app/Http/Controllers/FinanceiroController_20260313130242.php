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

        $dataFiltro = \Carbon\Carbon::parse($referencia . '-01');
        $mes = $dataFiltro->month;
        $ano = $dataFiltro->year;

        // 💰 1. FATURAMENTO FILTRADO (Bruto Mensal Real que entrou no Caixa)
        $faturamentoMensal = \App\Models\FinancialTransaction::whereMonth('paid_at', $mes)
            ->whereYear('paid_at', $ano)
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->sum('amount');

        // 📅 2. OCUPAÇÃO FILTRADA (Jogos que de fato aconteceram ou estão ativos)
        $totalReservasMes = \App\Models\Reserva::whereMonth('date', $mes)
            ->whereYear('date', $ano)
            ->where('is_fixed', false)
            ->whereIn('status', [
                \App\Models\Reserva::STATUS_CONFIRMADA,
                \App\Models\Reserva::STATUS_CONCLUIDA,
                'completed',
                'debt' // ✅ Incluído: Se é dívida, o cliente ocupou a quadra
            ])
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->count();

        // 🚫 3. FALTAS FILTRADAS (No-Show) - PRECISÃO TOTAL
        // Contamos apenas as reservas marcadas como falta para bater com o relatório de auditoria
        $canceladasMes = \App\Models\Reserva::whereMonth('date', $mes)
            ->whereYear('date', $ano)
            ->where('status', \App\Models\Reserva::STATUS_NO_SHOW)
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->count();

        // 💸 4. CÁLCULO DE DÍVIDAS PENDENTES (Inadimplência Real do Período)
        $totalGlobalDividas = \App\Models\Reserva::whereMonth('date', $mes)
            ->whereYear('date', $ano)
            ->whereIn('status', ['completed', 'debt']) // ✅ Foca nos jogos finalizados que devem
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->with('transactions')
            ->get()
            ->sum(function ($r) {
                $valorVenda = (float) ($r->final_price ?? $r->price);

                // Soma transações diretas + transações órfãs (estornos/ajustes) vinculadas pelo #ID
                $somaVinculada = (float) $r->transactions->sum('amount');
                $somaOrfa = (float) \App\Models\FinancialTransaction::whereNull('reserva_id')
                    ->where('description', 'LIKE', "%#{$r->id}%")
                    ->sum('amount');

                $saldoPagoLiquido = round($somaVinculada + $somaOrfa, 2);

                // Retorna o que falta pagar (mínimo 0)
                return max(0, $valorVenda - $saldoPagoLiquido);
            });

        $arenas = \App\Models\Arena::all();

        return view('admin.financeiro.index', [
            'faturamentoMensal'  => $faturamentoMensal,
            'totalReservasMes'   => $totalReservasMes,
            'canceladasMes'      => $canceladasMes,
            'totalGlobalDividas' => $totalGlobalDividas,
            'arenas'             => $arenas,
            'dataFiltro'         => $dataFiltro
        ]);
    }

    /**
     * Relatório 01: Faturamento Detalhado (Com Arena, Busca, Paginação e Fluxo)
     * ATUALIZADO: Com Unificação de Métodos de Pagamento para Fechamento Profissional
     */
    public function relatorioFaturamento(Request $request)
    {
        $arenaId = $request->get('arena_id');
        $search = $request->get('search');
        $fluxo = $request->get('fluxo'); // 🟢 entrada, saida ou null

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

        // 📊 UNIFICAÇÃO PROFISSIONAL DOS TOTAIS
        $queryParaTotais = clone $query;
        $transacoesParaTotais = $queryParaTotais->get();

        // Agrupamos usando um Dicionário de Unificação para evitar duplicidade no Painel
        $totaisPorMetodo = $transacoesParaTotais->groupBy(function ($item) {
            // Normaliza o texto para evitar duplicidade por erro de digitação
            $metodo = strtolower(trim($item->payment_method));

            // 1. GAVETA (Tudo que é dinheiro vivo)
            if (in_array($metodo, ['dinheiro', 'money', 'cash', 'especie'])) {
                return 'dinheiro';
            }

            // 2. BANCO (Tudo que é digital direto)
            if (in_array($metodo, ['pix', 'transferencia', 'transf', 'transferência', 'bank'])) {
                return 'pix';
            }

            // 3. CARTÃO (Maquininhas)
            if (in_array($metodo, ['cartao', 'cartão', 'credit', 'card', 'debito', 'débito'])) {
                return 'cartao';
            }

            // 4. TRANSFERÊNCIA (Caso queira manter separado do PIX, remova do item 2)
            if (in_array($metodo, ['transferencia', 'transferência', 'transf'])) {
                return 'transferencia';
            }

            return $metodo ?: 'outro';
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
            'fluxo'
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
        $mes = (int) $request->input('mes', now()->month);
        $ano = (int) $request->input('ano', now()->year);

        // 1. Busca as Reservas com status de perda (No-Show, Rejeitada, Cancelada)
        $query = Reserva::whereIn('status', [
            Reserva::STATUS_CANCELADA,
            Reserva::STATUS_NO_SHOW,
            Reserva::STATUS_REJEITADA
        ])
            ->whereYear('date', $ano)
            ->whereMonth('date', $mes)
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->with(['user', 'arena']);

        $cancelamentos = $query->orderBy('date', 'desc')->get();

        // 2. BUSCA AS MULTAS NO FINANCEIRO (Para auditoria)
        $multasAvulsas = FinancialTransaction::whereMonth('paid_at', $mes)
            ->whereYear('paid_at', $ano)
            ->where(function ($q) {
                $q->where('description', 'like', '%Multa de Falta%')
                    ->orWhere('description', 'like', '%No-Show%')
                    ->orWhere('description', 'like', '%ESTORNO NO-SHOW%');
            })
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->get();

        // 🎯 AQUI ESTÁ O SEGREDO DA CONTAGEM:
        // Precisamos contar quantos No-Shows existem na coleção de agendamentos
        $countFaltas = $cancelamentos->where('status', Reserva::STATUS_NO_SHOW)->count();
        $countCancelamentos = $cancelamentos->where('status', Reserva::STATUS_CANCELADA)->count();
        $countRejeitadas = $cancelamentos->where('status', Reserva::STATUS_REJEITADA)->count();

        // O valor financeiro das faltas: Preço das reservas de No-Show + Multas/Estornos
        $valorMultasFinanceiro = $multasAvulsas->sum('amount');
        $prejuizoFaltasReal = $cancelamentos->where('status', Reserva::STATUS_NO_SHOW)->sum('price') + $valorMultasFinanceiro;

        return view('admin.financeiro.cancelamentos', compact(
            'cancelamentos',
            'mes',
            'ano',
            'valorMultasFinanceiro',
            'multasAvulsas',
            'countFaltas',          // Isso enviará "2"
            'countCancelamentos',   // Isso enviará "0"
            'countRejeitadas',      // Isso enviará "1"
            'prejuizoFaltasReal'
        ));
    }

    /**
     * Relatório 05: Ranking de Clientes (Global ou por Unidade)
     */
    public function relatorioRanking(Request $request)
    {
        $arenaId = $request->get('arena_id');
        $mes = $request->input('mes', now()->month); // Pega o mês da URL
        $ano = $request->input('ano', now()->year);
        $hoje = now()->format('Y-m-d');

        $query = Reserva::select(
            'client_name',
            'client_contact',
            'user_id',
            DB::raw('SUM(total_paid) as total_gasto'),
            DB::raw("COUNT(CASE WHEN total_paid > 0 AND date <= '$hoje' THEN 1 END) as total_reservas")
        )
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_LANCADA_CAIXA])
            ->where('total_paid', '>', 0)
            ->whereYear('date', $ano) // Filtro de Ano
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId));

        // Se o mês não for "all", aplica o filtro de mês com cast para inteiro (evita erro 500)
        if ($mes !== 'all') {
            $query->whereMonth('date', (int)$mes);
        }

        $ranking = $query->groupBy('client_name', 'client_contact', 'user_id')
            ->orderBy('total_gasto', 'desc')
            ->limit(15)
            ->get();

        return view('admin.financeiro.ranking', compact('ranking'));
    }

    public static function isCashClosed(string $dateString, $arenaId = null): bool
    {
        // Se não passar a Arena, a função verifica se EXISTE QUALQUER caixa fechado no dia.
        // Isso serve como uma trava de segurança global caso o arena_id se perca.
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

            // 🎯 AJUSTE DE SEGURANÇA:
            // O status deve ser estritamente vinculado à Arena selecionada no Dashboard.
            // Se arena_id não for enviado, o sistema pode acabar pegando o status de outra quadra.
            $caixa = Cashier::whereDate('date', $targetDate)
                ->when($arenaId, function ($q) use ($arenaId) {
                    return $q->where('arena_id', $arenaId);
                })
                ->first();

            if ($caixa) {
                return response()->json([
                    // Um caixa só é considerado "fechado" se o status for explicitamente 'closed'
                    'isOpen' => $caixa->status !== 'closed',
                    'date'   => $targetDate,
                    'status' => $caixa->status,
                    'arena'  => $caixa->arena_id
                ]);
            }

            // Caso não exista registro de caixa para essa data e arena,
            // consideramos como aberto (status inicial do dia)
            return response()->json([
                'isOpen' => true,
                'date'   => $targetDate,
                'status' => 'not_created',
                'arena'  => $arenaId
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao buscar status do caixa: ' . $e->getMessage());
            // Em caso de erro técnico, retornamos true para não travar a usabilidade do gestor
            return response()->json(['isOpen' => true, 'error' => $e->getMessage()], 200);
        }
    }

    /**
     * 📊 Relatório de Ocupação e Histórico de Uso
     */
    public function relatorioOcupacao(Request $request)
    {
        // Usa filled() para verificar se as datas realmente vieram no request
        $dataInicio = $request->filled('data_inicio')
            ? Carbon::parse($request->data_inicio)
            : now()->subDays(7);

        $dataFim = $request->filled('data_fim')
            ? Carbon::parse($request->data_fim)
            : now();

        $arenaId = $request->arena_id;

        $reservas = Reserva::with(['arena', 'user'])
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, 'completed', 'no_show', Reserva::STATUS_CONCLUIDA])
            ->whereBetween('date', [$dataInicio->format('Y-m-d'), $dataFim->format('Y-m-d')])
            ->when($arenaId, fn($q) => $q->where('arena_id', $arenaId))
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'asc')
            ->get();

        return view('admin.financeiro.ocupacao', [
            'reservas' => $reservas,
            'dataInicio' => $dataInicio,
            'dataFim' => $dataFim
        ]);
    }

    /**
     * Relatório 06: Dívidas Pendentes (Inadimplência Geral)
     * Lista todas as reservas 'completed' que não foram totalmente pagas.
     */
    /**
     * Relatório 06: Dívidas Pendentes (Inadimplência Geral)
     * Lista todas as reservas 'completed' que não foram totalmente pagas.
     */
    public function relatorioDividas(Request $request)
    {
        $arenaId = $request->get('arena_id');
        $search = $request->get('search');

        // 1. Query Base: Reservas com status de dívida (com ou sem filtro de arena e busca)
        $query = Reserva::with(['user', 'arena', 'transactions'])
            ->where('status', 'debt')
            ->whereIn('payment_status', ['unpaid', 'partial']);

        // 2. Filtro por Arena
        if ($arenaId) {
            $query->where('arena_id', $arenaId);
        }

        // 3. Filtro por Nome ou ID
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%");
            });
        }

        // 4. Paginação
        $dividas = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(30)
            ->withQueryString();

        // 🎯 CORREÇÃO 2: Refazer o cálculo do Total Global com a query atualizada
        // Usamos uma nova variável para não bagunçar a paginação
        $totalGlobalDividas = $query->get()->sum(function ($r) {
            $valorVenda = (float) ($r->final_price ?? $r->price);

            $diretas = (float) $r->transactions->sum('amount');
            $orfas = (float) \App\Models\FinancialTransaction::whereNull('reserva_id')
                ->where('description', 'LIKE', "%#{$r->id}%")
                ->sum('amount');

            $pagoReal = round($diretas + $orfas, 2);

            return max(0, $valorVenda - $pagoReal);
        });

        $arenas = \App\Models\Arena::all();

        return view('admin.financeiro.dividas', compact('dividas', 'totalGlobalDividas', 'arenas'));
    }
}
