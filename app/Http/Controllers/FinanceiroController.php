<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Reserva; // Usando o Model Reserva que você compartilhou
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceiroController extends Controller
{
    /**
     * Carrega a view principal do dashboard financeiro.
     */
    public function index()
    {
        return view('admin.financeiro.dashboard');
    }

    /**
     * Helper para determinar o range de datas com base no período.
     * @param string $periodo O período solicitado ('hoje', 'semana', 'mes').
     * @return array [Carbon $start, Carbon $end]
     */
    private function getDateRange(string $periodo): array
    {
        $now = Carbon::now(); 
        $start = $now->copy()->startOfDay(); 
        $end = $now->copy()->endOfDay();

        switch ($periodo) {
            case 'semana':
                // Início da semana (Domingo) e Fim da semana (Sábado) - Ajuste se precisar de Segunda-feira
                $start = $now->copy()->startOfWeek(Carbon::SUNDAY); 
                $end = $now->copy()->endOfWeek(Carbon::SATURDAY);
                break;
            case 'mes':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            // 'hoje' é o default
        }

        // Garante que a data final inclui todo o dia
        if ($periodo !== 'hoje') {
             $end = $end->endOfDay();
        }

        return [$start, $end];
    }

    /**
     * Retorna os dados resumidos (Cards) para todos os períodos.
     */
    public function getResumo(Request $request)
    {
        Log::info('FinanceiroController: getResumo iniciado.');
        
        try {
            // ✅ OTIMIZAÇÃO: Usando a constante diretamente do seu Model
            $statusConfirmada = Reserva::STATUS_CONFIRMADA;
            
            $periodos = ['hoje', 'semana', 'mes'];
            $resultados = [
                'total_recebido' => [],
                'sinais' => [],
                'reservas' => [],
            ];

            foreach ($periodos as $periodo) {
                list($start, $end) = $this->getDateRange($periodo);

                $reservasNoPeriodo = Reserva::query()
                    ->where('status', $statusConfirmada)
                    // Filtragem pela coluna 'date'
                    ->whereBetween('date', [$start, $end]) 
                    ->get();

                // Total Recebido = Dinheiro que realmente entrou (total_paid)
                $totalRecebido = $reservasNoPeriodo->sum(function ($reserva) {
                    return $reserva->total_paid ?? 0;
                });
                
                // Total Sinais = Soma dos valores de sinal
                $totalSinais = $reservasNoPeriodo->sum('signal_value');
                $countReservas = $reservasNoPeriodo->count();

                $resultados['total_recebido'][$periodo] = (float) $totalRecebido;
                $resultados['sinais'][$periodo] = (float) $totalSinais;
                $resultados['reservas'][$periodo] = (int) $countReservas;
            }

            Log::info('FinanceiroController: getResumo concluído com sucesso.');

            return response()->json([
                'success' => true,
                'data' => $resultados,
            ]);

        } catch (\Exception $e) {
            Log::error('ERRO CRÍTICO no getResumo: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor ao carregar resumo.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna uma lista de pagamentos pendentes para a tabela.
     */
    public function getPagamentosPendentes()
    {
        Log::info('FinanceiroController: getPagamentosPendentes iniciado.');
        
        try {
            // ✅ OTIMIZAÇÃO: Usando as constantes diretamente do seu Model
            $statusPendente = Reserva::STATUS_PENDENTE;
            $statusConfirmada = Reserva::STATUS_CONFIRMADA;

            $reservasPendentes = Reserva::query()
                // Apenas reservas pendentes ou confirmadas (não canceladas, rejeitadas, etc.)
                ->whereIn('status', [$statusPendente, $statusConfirmada])
                ->where(function ($query) {
                    // Seleciona reservas onde (total_paid < preço final/original) OU (total_paid é nulo/zero)
                    $query->whereRaw('total_paid < COALESCE(final_price, price)') 
                          ->orWhereNull('total_paid');
                })
                // Apenas reservas futuras (a partir de hoje)
                ->where('date', '>=', Carbon::today())
                ->orderBy('date', 'asc')
                ->orderBy('start_time', 'asc')
                ->limit(30)
                ->get();

            $pendentesFormatados = $reservasPendentes->map(function ($reserva) {
                
                $valorTotalCobranca = $reserva->final_price ?? $reserva->price;
                $totalPago = $reserva->total_paid ?? 0;
                $valorRestante = max(0, $valorTotalCobranca - $totalPago);

                $corStatus = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                $statusTexto = 'Desconhecido';
                
                // Lógica de status de pagamento
                if ($valorRestante <= 0) {
                    $statusTexto = 'Pago Integral';
                    $corStatus = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                } else {
                    if ($totalPago == 0) {
                        $statusTexto = 'Não Iniciado';
                        $corStatus = 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
                    } elseif (($reserva->signal_value ?? 0) > 0 && $totalPago >= ($reserva->signal_value ?? 0) && $totalPago < $valorTotalCobranca) {
                        $statusTexto = 'Sinal Pago';
                        $corStatus = 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
                    } elseif ($totalPago > 0 && $totalPago < $valorTotalCobranca) {
                        $statusTexto = 'Parcialmente Pago';
                        $corStatus = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
                    }
                }

                return [
                    'id' => $reserva->id,
                    'cliente' => $reserva->client_name, 
                    'contato' => $reserva->client_contact, 
                    'data' => Carbon::parse($reserva->date)->format('d/m/Y'), 
                    'horario' => Carbon::parse($reserva->start_time)->format('H:i'), 
                    'valor_total' => (float) $valorTotalCobranca,
                    'sinal_pago' => (float) ($reserva->signal_value ?? 0),
                    'total_pago' => (float) $totalPago,
                    'valor_restante' => (float) $valorRestante,
                    'cor_status' => $corStatus,
                    'status_pagamento_texto' => $statusTexto,
                    'link_acoes' => route('admin.reservas.show', $reserva->id), 
                ];
            });

            Log::info('FinanceiroController: getPagamentosPendentes concluído com sucesso.');
            
            return response()->json([
                'success' => true,
                'data' => $pendentesFormatados,
            ]);

        } catch (\Exception $e) {
            Log::error('ERRO CRÍTICO no getPagamentosPendentes: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor ao carregar pagamentos pendentes.',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}