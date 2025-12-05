<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            💰 Gerenciamento de Caixa & Pagamentos
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. BARRA DE FILTRO E KPIS (Grid 3 colunas, 6 cards) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">

                {{-- CARD 1: FILTRO DE DATA --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-between border border-gray-200 dark:border-gray-700">
                    <form method="GET" action="{{ route('admin.payment.index') }}">
                        <input type="hidden" name="search" value="{{ request('search') }}">

                        <div class="flex items-center justify-between">
                            <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Data do Caixa:
                            </label>
                            @if(request()->has('reserva_id'))
                                <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'search' => request('search')]) }}"
                                    class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition duration-150 font-medium"
                                    title="Mostrar todas as reservas do dia">
                                    Resetar Filtro ID
                                </a>
                            @endif
                        </div>

                        <input type="date" name="date" id="date" value="{{ $selectedDate }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white mt-1 text-base"
                            onchange="this.form.submit()">
                    </form>
                </div>

                {{-- CARD 2: TOTAL GERAL EM CAIXA --}}
                <div class="bg-gray-100 dark:bg-gray-700/50 border border-gray-300 dark:border-gray-600 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            TOTAL EM CAIXA (GERAL)
                        </div>
                        <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m4 0h10a2 2 0 002-2V9a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <div class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">
                        R$ {{ number_format($totalGeralCaixa, 2, ',', '.') }}
                    </div>
                </div>

                {{-- CARD 3: TOTAL RECEBIDO DO DIA (SALDO LÍQUIDO) --}}
                @php
                    // Usando $totalRecebidoDia, que é o Saldo Líquido do dia
                    $isNegative = $totalRecebidoDia < 0;
                    $kpiClass = $isNegative ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-800' : 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-800';
                    $iconColor = $isNegative ? 'text-red-600' : 'text-green-600';
                    $textColor = $isNegative ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300';
                @endphp
                <div class="{{ $kpiClass }} overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            CAIXA DO DIA (MOVIMENTAÇÃO LÍQUIDA)
                        </div>
                        <svg class="w-6 h-6 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            @if ($isNegative)
                                {{-- Ícone de Saída (Caixa Negativo/Estorno) --}}
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h6M6 12h12M13 7h6M10 21h4c2.21 0 4-1.79 4-4V7c0-2.21-1.79-4-4-4h-4c-2.21 0-4 1.79-4 4v10c0 2.21 1.79 4 4 4z"></path>
                            @else
                                {{-- Ícone de Moeda/Entrada --}}
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2M21 12c0 4.418-4.03 8-9 8a9 9 0 01-9-8 9 9 0 019-9c4.97 0 9 3.582 9 8z"></path>
                            @endif
                        </svg>
                    </div>
                    <div class="mt-2 text-3xl font-extrabold {{ $textColor }}">
                        R$ {{ number_format($totalRecebidoDia, 2, ',', '.') }}
                    </div>
                </div>
                
                {{-- LINHA 2: DETALHES DA RECEITA (Competência) --}}

                {{-- CARD 4: TOTAL PAGO P/ RESERVAS DO DIA (O valor garantido) --}}
                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-300 dark:border-indigo-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            RECEITA GARANTIDA P/ HOJE (PAGO)
                        </div>
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.504A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.552L12 12m0 0l-8.618 3.552A11.955 11.955 0 0012 21.056a11.955 11.955 0 008.618-3.552L12 12z"></path></svg>
                    </div>
                    <div class="mt-2 text-3xl font-extrabold text-indigo-700 dark:text-indigo-300">
                        R$ {{ number_format($totalAntecipadoReservasDia, 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Valor total pago para as reservas agendadas hoje.
                    </div>
                </div>

                {{-- CARD 5: TOTAL A RECEBER (O que falta pagar) --}}
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            SALDO PENDENTE A RECEBER
                        </div>
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V4m0 12v4M5 9h14M5 15h14M4 12h16"></path></svg>
                    </div>
                    {{-- O valor em destaque continua sendo o Saldo, mas o Total Previsto dá o contexto --}}
                    <div class="mt-2 text-3xl font-extrabold text-yellow-700 dark:text-yellow-300">
                        R$ {{ number_format($totalPending, 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Total Previsto (Receita Bruta): R$ {{ number_format($totalExpected, 2, ',', '.') }}
                    </div>
                </div>


                {{-- CARD 6: FALTAS (NO-SHOW) E TOTAL DE RESERVAS --}}
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                    <div class="flex items-center justify-between">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            FALTAS / TOTAL DE RESERVAS
                        </div>
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L12 12M6 6l12 12"></path></svg>
                    </div>
                    <div class="mt-2 text-3xl font-extrabold text-red-700 dark:text-red-300">
                        {{ $noShowCount }} / {{ $totalReservasDia }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Total de agendamentos para o dia.
                    </div>
                </div>
            </div>

            {{-- 1.5. BARRA DE PESQUISA (Nome/WhatsApp) --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                <form method="GET" action="{{ route('admin.payment.index') }}">
                    {{-- Preserva a data ao fazer a pesquisa --}}
                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                    <div class="flex items-end gap-3">
                        <div class="flex-grow">
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar Cliente (Nome ou WhatsApp):</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}"
                                placeholder="Digite o nome ou WhatsApp do cliente..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        </div>
                        <button type="submit" class="h-10 px-4 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </button>
                        @if(request()->has('search'))
                            <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'reserva_id' => request('reserva_id')]) }}"
                                class="h-10 px-2 py-1 flex items-center justify-center text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 transition duration-150"
                                title="Limpar pesquisa">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                        @endif
                    </div>
                </form>
            </div>


            {{-- 2. TABELA DE RESERVAS (PRIORIDADE DO CAIXA) --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4 flex items-center justify-between">
                        @if(request()->has('reserva_id'))
                            <span class="text-indigo-500">Reserva Selecionada (ID: {{ request('reserva_id') }})</span>
                        @else
                            Agendamentos do Dia ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                        @endif

                        {{-- Botão Voltar para a visão diária, se estiver no filtro de ID --}}
                        @if(request()->has('reserva_id'))
                            <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'search' => request('search')]) }}"
                                class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                Ver Todas do Dia
                            </a>
                        @endif
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Horário</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-3/12">Cliente</th>
                                    {{-- Titulo da coluna AJUSTADO para clareza --}}
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Status Fin.</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Tipo</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Valor Total (R$)</th>
                                    {{-- Titulo da coluna AJUSTADO para clareza: Mostra o total pago (sinal + parciais) --}}
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Total Pago (R$)</th>
                                    {{-- Titulo da coluna AJUSTADO para clareza: O saldo final a receber --}}
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Saldo a Pagar</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/12">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($reservas as $reserva)
                                    @php
                                        // Cálculos Visuais
                                        $total = $reserva->final_price ?? $reserva->price;
                                        $pago = $reserva->total_paid; // Valor total já pago (inclui sinal e parciais)
                                        $restante = max(0, $total - $pago); // Saldo a pagar
                                        $currentStatus = $reserva->payment_status;
                                        $isOverdue = false;

                                        // LÓGICA DE DETECÇÃO DE ATRASO
                                        if (in_array($currentStatus, ['pending', 'unpaid', 'partial'])) { // Adicionando 'partial' ao check de atraso
                                            $dateTimeString = \Carbon\Carbon::parse($reserva->date)->format('Y-m-d') . ' ' . $reserva->end_time;
                                            $reservaEndTime = \Carbon\Carbon::parse($dateTimeString);

                                            if ($reservaEndTime->lessThan(\Carbon\Carbon::now())) {
                                                $isOverdue = true;
                                            }
                                        }

                                        // Cor da Linha / Status
                                        $statusClass = '';
                                        $statusLabel = '';

                                        if ($reserva->status === 'no_show') {
                                            $statusClass = 'bg-red-500 text-white font-bold';
                                            $statusLabel = 'FALTA';
                                        } elseif ($reserva->status === 'canceled') {
                                            $statusClass = 'bg-gray-400 text-white font-bold';
                                            $statusLabel = 'CANCELADA';
                                        } elseif ($currentStatus === 'paid' || $currentStatus === 'completed') {
                                            $statusClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                                            $statusLabel = 'PAGO COMPLETO'; // Mais específico
                                        } elseif ($currentStatus === 'partial') {
                                            $statusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
                                            $statusLabel = 'PAGO PARCIAL'; // Mais específico
                                        } elseif ($isOverdue) {
                                            $statusClass = 'bg-red-700 text-white font-bold animate-pulse shadow-xl';
                                            $statusLabel = 'ATRASADO';
                                        } else {
                                            // Pendente/Unpaid normal (se não houver pagamento ou for só sinal)
                                            $statusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                            // Se o sinal foi dado, mas não é parcial:
                                            $statusLabel = ($pago > 0) ? 'SINAL DADO' : 'PENDENTE'; 
                                        }

                                        // Destaque para a linha quando vier do dashboard
                                        $rowHighlight = (isset($highlightReservaId) && $reserva->id == $highlightReservaId)
                                            ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500'
                                            : 'hover:bg-gray-50 dark:hover:bg-gray-700';
                                    @endphp
                                    <tr class="{{ $rowHighlight }} transition">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-bold">
                                            {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $reserva->client_name }} (ID: {{ $reserva->id }})</div>
                                            <div class="text-xs text-gray-500">
                                                @if($reserva->user && $reserva->user->is_vip)
                                                    <span class="text-indigo-600 font-bold">★ VIP</span>
                                                @endif
                                                {{ $reserva->client_contact }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            {{-- APLICANDO A CLASSE E O TEXTO DO NOVO STATUS --}}
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        {{-- ✅ Célula Tipo --}}
                                        <td class="px-4 py-4 whitespace-nowrap text-sm">
                                            @if ($reserva->is_recurrent)
                                                <span class="font-semibold text-fuchsia-600">Recorrente</span>
                                            @else
                                                <span class="font-semibold text-blue-600">Pontual</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold">
                                            {{ number_format($total, 2, ',', '.') }}
                                        </td>
                                        {{-- Total Pago --}}
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                            {{ number_format($pago, 2, ',', '.') }}
                                        </td>
                                        {{-- Saldo a Pagar --}}
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold {{ $restante > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                            {{ number_format($restante, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            @if($restante > 0 && $reserva->status !== 'no_show' && $reserva->status !== 'canceled')
                                                {{-- Botão Pagar: Adicionado $pago como 4º argumento E is_recurrent como 6º --}}
                                                <button onclick="openPaymentModal({{ $reserva->id }}, {{ $total }}, {{ $restante }}, {{ $pago }}, '{{ $reserva->client_name }}', {{ $reserva->is_recurrent ? 'true' : 'false' }})"
                                                    class="text-white bg-green-600 hover:bg-green-700 rounded px-3 py-1 text-xs mr-2 transition duration-150">
                                                    $ Baixar
                                                </button>
                                            @endif

                                            @if($restante > 0 || ($pago > 0 && $reserva->status !== 'no_show' && $reserva->status !== 'canceled'))
                                                {{-- Botão Falta --}}
                                                <button onclick="openNoShowModal({{ $reserva->id }}, '{{ $reserva->client_name }}', {{ $pago }})"
                                                    class="text-white bg-red-600 hover:bg-red-700 rounded px-3 py-1 text-xs transition duration-150">
                                                    X Falta
                                                </button>
                                            @elseif($reserva->status === 'no_show')
                                                <span class="text-xs text-red-500 italic font-medium">Falta Registrada</span>
                                            @elseif($reserva->status === 'canceled')
                                                <span class="text-xs text-gray-500 italic font-medium">Cancelada</span>
                                            @elseif($pago == $total)
                                                <span class="text-xs text-green-500 italic font-medium">Finalizado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- Colspan ajustado para 8 --}}
                                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            Nenhum agendamento encontrado para esta data ou termo de pesquisa.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 4. TABELA DE TRANSAÇÕES FINANCEIRAS (AUDITORIA DE CAIXA) --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Movimentação Detalhada de Caixa ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                        <span class="text-sm font-normal text-gray-500 ml-3">(Sinais, Pagamentos, Retenções e Estornos)</span>
                    </h3>

                    @php
                        // 1. Agrupamos as transações por ID da Reserva (Chave de Agrupamento)
                        $groupedTransactions = $financialTransactions->groupBy('reserva_id');
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Hora</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/12">Pagador / Gestor</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/12">Tipo | Forma</th>
                                    {{-- LARGURA DE VOLTA AO ORIGINAL, mas sem truncamento --}}
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-4/12">Descrição</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Valor (R$)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($groupedTransactions as $reservaId => $transactions)
                                    {{-- Linha de Agrupamento/Resumo para a Reserva (MELHORIA DE ROBUSTEZ) --}}
                                    @if ($reservaId)
                                        @php
                                            // Pega a primeira transação para ter acesso ao relacionamento 'reserva'
                                            $transactionExample = $transactions->first(); 
                                            // Tenta obter o nome do cliente através do relacionamento (se existir, se foi carregado, se a reserva não foi excluída)
                                            $clientName = $transactionExample->reserva->client_name ?? 'N/A';
                                            $reservaInfo = "ID: " . $reservaId . ($clientName !== 'N/A' ? ' - ' . $clientName : ' (Reserva Ausente/Cancelada)');
                                        @endphp
                                        <tr class="bg-gray-100 dark:bg-gray-700/50 border-t-2 border-indigo-400 dark:border-indigo-600">
                                            {{-- Colspan ajustado para 5 colunas de texto --}}
                                            <td colspan="5" class="px-4 py-2 text-sm font-bold text-gray-800 dark:text-gray-200">
                                                ✅ MOVIMENTOS DA RESERVA <span class="text-indigo-600 dark:text-indigo-400">{{ $reservaInfo }}</span> 
                                            </td>
                                            <td class="px-4 py-2 text-right text-sm font-bold text-gray-800 dark:text-gray-200">
                                                TOTAL: R$ {{ number_format($transactions->sum('amount'), 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endif

                                    {{-- Iteração sobre as transações individuais (Sinal, Pagamento, Estorno, etc.) --}}
                                    @foreach ($transactions as $transaction)
                                        @php
                                            $amount = (float) $transaction->amount;
                                            $isPositive = $amount >= 0;
                                            $isRefund = $transaction->type === 'refund' || $amount < 0; // Se o valor for negativo, é saída
                                            
                                            // Destaque visual para estornos/saídas
                                            $rowClass = $isRefund ? 'bg-red-50 dark:bg-red-900/30 hover:bg-red-100' : 'hover:bg-gray-50 dark:hover:bg-gray-700';
                                            $amountClass = $isPositive ? 'text-green-600 font-bold' : 'text-red-600 font-bold';
                                            
                                            // Mapeamento de Tipos para exibição amigável
                                            $typeMap = [
                                                'signal' => 'Sinal',
                                                'payment' => 'Pagamento Saldo',
                                                'full_payment' => 'Pgto. Total',
                                                'partial_payment' => 'Pgto. Parcial',
                                                'payment_settlement' => 'Acerto',
                                                'refund' => 'Estorno/Devolução', // MUITO IMPORTANTE
                                                'RETEN_CANC_COMP' => 'Retenção (Canc.)',
                                                'RETEN_NOSHOW_COMP' => 'Retenção (No-Show)',
                                            ];
                                            $displayType = $typeMap[$transaction->type] ?? ucwords(str_replace('_', ' ', $transaction->type));

                                            // Mapeamento de Formas de Pagamento
                                            $methodMap = [
                                                'pix' => 'PIX',
                                                'money' => 'Dinheiro',
                                                'credit_card' => 'Crédito',
                                                'debit_card' => 'Débito',
                                                'transfer' => 'Transf.',
                                                'other' => 'Outro',
                                                'retained_funds' => 'Retained Funds',
                                            ];
                                            $displayMethod = $methodMap[$transaction->payment_method] ?? ucwords(str_replace('_', ' ', $transaction->payment_method));
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($transaction->paid_at)->format('H:i:s') }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium {{ $isRefund ? 'text-red-800 dark:text-red-400' : 'text-indigo-600 dark:text-indigo-400' }}">
                                                {{ $transaction->reserva_id ?? '--' }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                <div class="text-gray-900 dark:text-white font-medium">{{ $transaction->payer->name ?? 'Caixa Geral' }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Registrado por: {{ $transaction->manager->name ?? 'Desconhecido' }}</div>
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $displayType }}</div>
                                                <div class="text-xs text-gray-500">({{ $displayMethod }})</div>
                                            </td>
                                            {{-- Correção: Permite a quebra de linha --}}
                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ $transaction->description }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-right {{ $amountClass }}">
                                                {{ number_format($amount, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                            Nenhuma transação financeira registrada para esta data.
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                                    <td colspan="5" class="px-4 py-3 text-right text-gray-800 dark:text-gray-200 uppercase">
                                        Total Líquido do Dia:
                                    </td>
                                    <td class="px-4 py-3 text-right text-lg {{ $totalRecebidoDia >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                        R$ {{ number_format($totalRecebidoDia, 2, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 5. LINK DISCRETO PARA DASHBOARD NO FINAL DA PÁGINA --}}
            <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                <a href="{{ route('admin.financeiro.dashboard') }}"
                    class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition duration-150">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-8v8m-4-8v8M4 16h16a2 2 0 002-2V8a2 2 0 00-2-2H4a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                    Ir para Relatórios
                </a>
            </div>

        </div>
    </div>

{{-- ================================================================== --}}
{{-- MODAL 1: FINALIZAR PAGAMENTO --}}
{{-- ================================================================== --}}
<div id="paymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">

<div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closePaymentModal()"></div>

<div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg w-full">
<form id="paymentForm">
    @csrf
    <input type="hidden" name="reserva_id" id="modalReservaId">

    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
            Finalizar Pagamento
        </h3>
        <div class="mt-2">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Cliente: <span id="modalClientName" class="font-bold"></span>
            </p>

            {{-- Valor Final (Editável para Desconto) --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Total Acordado (R$)</label>
                {{-- Adicionada a classe js-recalculate para o listener JS --}}
                <input type="number" step="0.01" name="final_price" id="modalFinalPrice"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white js-recalculate">
                <p class="text-xs text-gray-500 mt-1">Edite este valor apenas se for aplicar um desconto no total.</p>
            </div>

            {{-- NOVO BLOCO: Opção Recorrente (Visibilidade controlada por JS) --}}
            <div id="recurrentOption" class="mb-4 hidden p-3 border border-indigo-200 dark:border-indigo-600 rounded-lg bg-indigo-50 dark:bg-indigo-900/30">
                <div class="flex items-center">
                    {{-- O valor "1" aqui é ignorado no JS, pois estamos enviando um BOOLEAN na requisição AJAX --}}
                    <input id="apply_to_series" name="apply_to_series" type="checkbox" value="1" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                    <label for="apply_to_series" class="ml-2 block text-sm font-medium text-gray-900 dark:text-gray-300">
                        Aplicar este valor (R$ <span id="currentNewPrice" class="font-bold">0,00</span>) a TODAS as reservas futuras desta série.
                    </label>
                </div>
                <p class="text-xs text-indigo-700 dark:text-indigo-400 mt-1 pl-6">
                    Se desmarcado, o desconto/ajuste se aplicará apenas a esta reserva.
                </p>
            </div>

            {{-- Valor a Pagar Agora (Restante, calculado) --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Recebido Agora (R$)</label>
                {{-- O campo amount_paid será preenchido automaticamente, mas é mantido como input para permitir ajuste fino --}}
                <input type="number" step="0.01" name="amount_paid" id="modalAmountPaid" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white font-bold text-lg">
                {{-- NOVO: Mensagem de Troco --}}
                <div id="trocoMessage" class="text-yellow-600 dark:text-yellow-400 text-sm mt-1 hidden font-semibold"></div>
            </div>

            {{-- 🎯 SINAL JÁ PAGO (Display) --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sinal Recebido (R$)</label>
                <span id="modalSignalAmount" class="text-xl font-extrabold text-indigo-900 dark:text-indigo-200 mt-1 block">R$ 0,00</span>
                {{-- NOVO: Campo escondido para armazenar o valor FLOAT do sinal --}}
                <input type="hidden" id="modalSignalAmountRaw" value="0.00">
            </div>

            {{-- Método de Pagamento (Forma de Pagamento) --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento</label>
                <select name="payment_method" id="modalPaymentMethod" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                    <option value="" disabled selected>Selecione a forma</option>
                    <option value="pix">Pix</option>
                    <option value="money">Dinheiro</option>
                    <option value="credit_card">Cartão de Crédito</option>
                    <option value="debit_card">Cartão de Débito</option>
                    <option value="transfer">Transferência</option>
                    <option value="other">Outro</option>
                </select>
            </div>
        </div>

        {{-- Placeholder para Erros AJAX --}}
        <div id="payment-error-message" class="text-red-500 text-sm mt-3 hidden"></div>

    </div>
    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="submit" id="submitPaymentBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
            <span id="submitPaymentText">Confirmar Recebimento</span>
            <svg id="submitPaymentSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
        <button type="button" onclick="closePaymentModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">
            Cancelar
        </button>
    </div>
</form>
</div>
</div>

{{-- ================================================================== --}}
{{-- MODAL 2: REGISTRAR FALTA (NO-SHOW) --}}
{{-- ================================================================== --}}
<div id="noShowModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4">

<div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeNoShowModal()"></div>

<div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg w-full">
<form id="noShowForm">
    @csrf
    <input type="hidden" name="reserva_id" id="noShowReservaId">
    {{-- NOVO: Campo escondido para o valor pago --}}
    <input type="hidden" name="paid_amount" id="noShowPaidAmount">

    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
        <h3 class="text-lg leading-6 font-medium text-red-600 dark:text-red-400" id="modal-title">
            Registrar Falta (No-Show)
        </h3>
        <div class="mt-2">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Você está registrando que <span id="noShowClientName" class="font-bold"></span> faltou ao horário agendado.
            </p>

            {{-- 🎯 BLOCO CRÍTICO: GESTÃO FINANCEIRA DE FALTA --}}
            <div id="financialNoShowBlock" class="p-4 mb-4 border border-red-300 rounded-lg bg-red-50 dark:bg-red-900/30">
                <div class="text-sm font-semibold text-red-700 dark:text-red-300 mb-2">
                    Gestão de Pagamento <span id="noShowAmountDisplay" class="font-extrabold">R$ 0,00</span>
                </div>
                <p id="noShowInitialWarning" class="text-xs text-gray-700 dark:text-gray-400 mb-3"></p>

                {{-- Opção de Estorno/Retenção --}}
                <div id="refundControls" class="mt-2">
                    <label for="should_refund" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ação sobre o valor pago:</label>
                    <select name="should_refund" id="should_refund" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                        <option value="false" selected>Reter o valor pago (Sem estorno)</option>
                        <option value="true">Estornar/Devolver o valor pago (Saída de caixa)</option>
                    </select>
                </div>
            </div>
            {{-- FIM DO BLOCO CRÍTICO --}}

            {{-- Bloquear Cliente --}}
            <div class="flex items-center mb-4">
                <input id="block_user" name="block_user" type="checkbox" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                <label for="block_user" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                    Adicionar cliente à Lista Negra (Bloquear)
                </label>
            </div>

            {{-- Motivo da Falta --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo da Falta (Obrigatório)</label>
                <textarea name="no_show_reason" rows="2" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:text-white" placeholder="Detalhes do no-show..."></textarea>
            </div>
        </div>
        {{-- Placeholder para Erros AJAX --}}
        <div id="noshow-error-message" class="text-red-500 text-sm mt-3 hidden"></div>
    </div>
    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
        <button type="submit" id="submitNoShowBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                     <span id="submitNoShowText">Confirmar Falta</span>
                                    <svg id="submitNoShowSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
        </button>
        <button type="button" onclick="closeNoShowModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">
            Cancelar
        </button>
    </div>
</form>
</div>


</div>

{{-- SCRIPT PARA MODAIS --}}

<script>
// Função customizada para mostrar mensagem (substituindo o alert)
function showMessage(message, isSuccess = true) {
// Usamos a mesma lógica de notificação flash para o Laravel, mas apenas no console aqui.
// O redirecionamento tratará o feedback visual na próxima página.
console.log(isSuccess ? 'SUCESSO: ' : 'ERRO: ', message);
}

/**
 * Atualiza o valor do novo preço no texto da checkbox de recorrência.
 */
function updateRecurrentTogglePrice(newPrice) {
    const currentNewPriceEl = document.getElementById('currentNewPrice');
    const newPriceFloat = parseFloat(newPrice) || 0;

    // Exibe o preço atual do desconto no texto do checkbox
    currentNewPriceEl.innerText = newPriceFloat.toFixed(2).replace('.', ',');
}


/**
 * Calcula o valor restante a ser pago AGORA (Valor Total Acordado - Sinal Recebido).
 * Esta função só lida com o cálculo automático na abertura/desconto.
 */
function calculateAmountDue() {
    const finalPriceEl = document.getElementById('modalFinalPrice');
    const signalRawEl = document.getElementById('modalSignalAmountRaw');
    const amountPaidEl = document.getElementById('modalAmountPaid');
    const trocoMessageEl = document.getElementById('trocoMessage');

    // 1. Limpar estados de sobrepagamento
    trocoMessageEl.classList.add('hidden');
    amountPaidEl.classList.remove('focus:border-yellow-500');
    amountPaidEl.classList.add('focus:border-green-500');


    // 2. Converter valores para float
    const finalPrice = parseFloat(finalPriceEl.value) || 0;
    const signalAmount = parseFloat(signalRawEl.value) || 0;

    // 3. Calcular o restante a ser pago (ou o troco gerado por desconto)
    let remainingOrChange = finalPrice - signalAmount;

    // NOVO: Atualiza o preço no toggle recorrente
    updateRecurrentTogglePrice(finalPrice);


    // 4. Se o restante for negativo, é troco devido a desconto (Situação 1)
    if (remainingOrChange < 0) {
        const trocoAmount = Math.abs(remainingOrChange);

        // Define o valor a ser recebido AGORA como 0.00
        amountPaidEl.value = (0).toFixed(2);

        // Exibir mensagem de troco
        trocoMessageEl.textContent = `🚨 ATENÇÃO: Troco a Devolver: R$ ${trocoAmount.toFixed(2).replace('.', ',')}`;
        trocoMessageEl.classList.remove('hidden');

        // Destaque visual
        amountPaidEl.classList.remove('focus:border-green-500');
        amountPaidEl.classList.add('focus:border-yellow-500');

    } else {
        // Caso normal: há valor restante a ser pago. Define o valor padrão para o input.
        amountPaidEl.value = remainingOrChange.toFixed(2);
        // Chama o checkManualOverpayment para garantir que a cor e mensagem estejam corretas após o set
        checkManualOverpayment();
    }
}

/**
 * Verifica se o valor digitado manualmente no campo 'Valor Recebido Agora'
 * causa um sobrepagamento e exibe o troco. (Situação 2)
 */
function checkManualOverpayment() {
    const finalPrice = parseFloat(document.getElementById('modalFinalPrice').value) || 0;
    const signalAmount = parseFloat(document.getElementById('modalSignalAmountRaw').value) || 0;
    const amountPaidNow = parseFloat(document.getElementById('modalAmountPaid').value) || 0;

    const amountPaidEl = document.getElementById('modalAmountPaid');
    const trocoMessageEl = document.getElementById('trocoMessage');

    // NOVO: Atualiza o preço no toggle recorrente (caso o desconto tenha sido o input)
    updateRecurrentTogglePrice(finalPrice);

    // Total já recebido (Sinal) + Total a ser recebido AGORA (Input Manual)
    const totalReceived = signalAmount + amountPaidNow;

    // 1. Calcular o sobrepagamento (Troco)
    let overpayment = totalReceived - finalPrice;

    // 2. Limpa estados
    trocoMessageEl.classList.add('hidden');
    amountPaidEl.classList.remove('focus:border-yellow-500');
    amountPaidEl.classList.add('focus:border-green-500');

    if (overpayment > 0.005) { // Usamos margem de 0.005 para lidar com erros de ponto flutuante

        // Exibir mensagem de troco
        trocoMessageEl.textContent = `🚨 ATENÇÃO: Troco a Devolver: R$ ${overpayment.toFixed(2).replace('.', ',')}`;
        trocoMessageEl.classList.remove('hidden');

        // Destaque visual
        amountPaidEl.classList.remove('focus:border-green-500');
        amountPaidEl.classList.add('focus:border-yellow-500');
    } else {
        // Se o valor digitado for menor ou igual ao devido (ou se o troco foi gerado pelo desconto)
        // Se a dívida era 0 (devido a desconto), não queremos limpar o troco do desconto.
        if (finalPrice - signalAmount < 0) {
             calculateAmountDue(); // Recalcula para reexibir a mensagem de troco por desconto, se aplicável
        }
    }
}


// --- Lógica do Pagamento ---
function openPaymentModal(id, totalPrice, remaining, signalAmount, clientName, isRecurrent = false) { // <-- Recebe a flag isRecurrent

    // 1. Popula dados e IDs
    document.getElementById('modalReservaId').value = id;
    document.getElementById('modalClientName').innerText = clientName;

    const formattedSignal = signalAmount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('modalSignalAmount').innerText = formattedSignal;

    // NOVO: Armazenar o valor float (sem formatação) no campo hidden para cálculos
    document.getElementById('modalSignalAmountRaw').value = signalAmount.toFixed(2);

    // 2. Popula os valores
    document.getElementById('modalFinalPrice').value = totalPrice.toFixed(2);

    // NOVO: Popula o Recurrent Toggle
    const recurrentOptionEl = document.getElementById('recurrentOption');
    if (isRecurrent) {
        recurrentOptionEl.classList.remove('hidden');
        document.getElementById('apply_to_series').checked = true; // Padrão: aplica se for recorrente
    } else {
        recurrentOptionEl.classList.add('hidden');
        document.getElementById('apply_to_series').checked = false; // Garante que esteja desmarcado/invisível
    }


    // 3. Executar o cálculo inicial (que agora lida com o troco gerado por desconto)
    calculateAmountDue();

    // 4. Limpa estados
    document.getElementById('payment-error-message').textContent = '';
    document.getElementById('payment-error-message').classList.add('hidden');
    document.getElementById('modalPaymentMethod').value = ''; // Resetar seleção de método

    // 5. Exibe o modal
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('paymentModal').classList.add('flex');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentModal').classList.remove('flex');
}

// Handle Payment Submit via AJAX
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const reservaId = document.getElementById('modalReservaId').value;
    // *** CORREÇÃO: Garante o PONTO para o back-end ***
    const finalPrice = parseFloat(document.getElementById('modalFinalPrice').value).toFixed(2);
    const amountPaid = parseFloat(document.getElementById('modalAmountPaid').value).toFixed(2);
    const paymentMethod = document.getElementById('modalPaymentMethod').value;

    // NOVO: Pega o estado da checkbox de série.
    const recurrentOptionEl = document.getElementById('recurrentOption');
    const isRecurrentVisible = !recurrentOptionEl.classList.contains('hidden');

    // *** CORREÇÃO CRÍTICA AQUI: Enviar como BOOLEAN JS (true/false) ***
    let applyToSeries = false;
    if (isRecurrentVisible) {
        applyToSeries = document.getElementById('apply_to_series').checked; // true ou false
    }

    const csrfToken = document.querySelector('input[name="_token"]').value;

    const submitBtn = document.getElementById('submitPaymentBtn');
    const submitText = document.getElementById('submitPaymentText');
    const submitSpinner = document.getElementById('submitPaymentSpinner');
    const errorMessageDiv = document.getElementById('payment-error-message');

    // 1. Validação simples
    if (paymentMethod === '') {
        errorMessageDiv.textContent = 'Por favor, selecione a Forma de Pagamento.';
        errorMessageDiv.classList.remove('hidden');
        return;
    }

    // 2. Estado de Carregamento
    submitBtn.disabled = true;
    submitText.classList.add('hidden');
    submitSpinner.classList.remove('hidden');
    errorMessageDiv.classList.add('hidden');

    // MONTANDO O PAYLOAD
    const payload = {
        reserva_id: reservaId,
        final_price: finalPrice, // Garante que seja string com ponto (e.g., "150.00")
        amount_paid: amountPaid, // Garante que seja string com ponto
        payment_method: paymentMethod,
        apply_to_series: applyToSeries // <-- AGORA É true ou false (JSON BOOL)
    };

    // LOG CRÍTICO PARA DEBUG NO BROWSER
    console.log("AJAX Payload Enviado:", payload);

    // 3. Envio AJAX (Assumindo a rota POST /admin/pagamentos/{reserva}/finalizar)
    fetch(`/admin/pagamentos/${reservaId}/finalizar`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        if (!response.ok) {
            // Se houver erro (incluindo validação 422), tenta ler a mensagem de erro
            return response.json().then(data => {
                throw new Error(data.message || 'Erro de validação ou processamento no servidor.');
            });
        }
        return response.json();
    })
    .then(data => {
        if(data.success) {
            showMessage('Pagamento registrado com sucesso!');
            location.reload();
        } else {
            errorMessageDiv.textContent = data.message || 'Erro ao processar pagamento.';
            errorMessageDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        errorMessageDiv.textContent = 'Erro ao processar pagamento: ' + error.message;
        errorMessageDiv.classList.remove('hidden');
    })
    .finally(() => {
        // 4. Resetar Estado
        submitBtn.disabled = false;
        submitText.classList.remove('hidden');
        submitSpinner.classList.add('hidden');
    });
});

// --- Listener de Recálculo ---
document.addEventListener('DOMContentLoaded', () => {
    // Recalcula o restante quando o preço final for alterado (para descontos - Situação 1)
    document.getElementById('modalFinalPrice').addEventListener('input', calculateAmountDue);

    // Verifica o sobrepagamento quando o valor a ser pago for alterado (overpayment manual - Situação 2)
    document.getElementById('modalAmountPaid').addEventListener('input', checkManualOverpayment);


    // --- Destaque de Linha (após o reload) ---
    const urlParams = new URLSearchParams(window.location.search);
    const reservaId = urlParams.get('reserva_id');

    if (reservaId) {
        const highlightedRow = document.querySelector(`.bg-indigo-50`);
        if (highlightedRow) {
            highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});

// --- Lógica do No-Show ---
// ATUALIZADA para receber o valor pago
function openNoShowModal(id, clientName, paidAmount) {
    document.getElementById('noShowReservaId').value = id;
    document.getElementById('noShowClientName').innerText = clientName;

    // NOVO: Define o valor pago nos campos hidden e display
    document.getElementById('noShowPaidAmount').value = paidAmount.toFixed(2);
    const paidAmountFormatted = paidAmount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('noShowAmountDisplay').innerText = paidAmountFormatted;

    // NOVO: Atualiza a mensagem de aviso inicial
    const initialWarningEl = document.getElementById('noShowInitialWarning');
    const refundControlsEl = document.getElementById('refundControls'); // <--- NOVO: Referência ao bloco de controle de estorno

    if (paidAmount > 0) {
        initialWarningEl.innerHTML = `O cliente já pagou <span class="font-bold">${paidAmountFormatted}</span>. Escolha abaixo se este valor será retido (padrão) ou estornado.`;
        // Reseta para 'Reter' como padrão
        document.getElementById('should_refund').value = 'false';
        refundControlsEl.classList.remove('hidden'); // Mostra os controles
    } else {
        initialWarningEl.textContent = `Nenhum valor foi pago. Marcar como falta apenas registrará o status.`;
        document.getElementById('should_refund').value = 'false';
        refundControlsEl.classList.add('hidden'); // Esconde os controles
    }


    document.getElementById('noshow-error-message').textContent = '';
    document.getElementById('noshow-error-message').classList.add('hidden');
    // Limpar o campo de observações ao abrir
    document.querySelector('#noShowForm textarea[name="no_show_reason"]').value = '';
    document.querySelector('#noShowForm input[name="block_user"]').checked = false;

    document.getElementById('noShowModal').classList.remove('hidden');
    document.getElementById('noShowModal').classList.add('flex');
}

function closeNoShowModal() {
    document.getElementById('noShowModal').classList.add('hidden');
    document.getElementById('noShowModal').classList.remove('flex');
}

document.getElementById('noShowForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const reservaId = document.getElementById('noShowReservaId').value;
    // RENOMEADO: Campo de observação agora é 'no_show_reason'
    const reason = this.querySelector('textarea[name="no_show_reason"]').value;
    const blockUser = this.querySelector('input[name="block_user"]').checked; // Boolean (true/false)

    // NOVO: Captura o valor pago e a decisão de estorno
    const paidAmount = document.getElementById('noShowPaidAmount').value;
    // Converte a string 'true'/'false' em boolean JS para o payload (Laravel JSON)
    const shouldRefund = this.querySelector('select[name="should_refund"]').value === 'true';

    const csrfToken = document.querySelector('input[name="_token"]').value;

    const submitBtn = document.getElementById('submitNoShowBtn');
    const submitText = document.getElementById('submitNoShowText');
    const submitSpinner = document.getElementById('submitNoShowSpinner');
    const errorMessageDiv = document.getElementById('noshow-error-message');

    // 1. Validação de Motivo (Obrigatório)
    if (reason.length < 5) {
          errorMessageDiv.textContent = 'O motivo da falta (Observações) é obrigatório e deve ter no mínimo 5 caracteres.';
          errorMessageDiv.classList.remove('hidden');
          return;
    }

    submitBtn.disabled = true;
    submitText.classList.add('hidden');
    submitSpinner.classList.remove('hidden');
    errorMessageDiv.classList.add('hidden');

    // MONTANDO O PAYLOAD
    const payload = {
        reserva_id: reservaId,
        notes: reason, // Mudado para 'notes' para ser mais genérico e se adequar ao seu Controller (se ele usar 'notes' no Request)
        block_user: blockUser,
        paid_amount: paidAmount,
        should_refund: shouldRefund // Boolean (true/false)
    };

    // LOG CRÍTICO PARA DEBUG NO BROWSER
    console.log("No-Show Payload Enviado:", payload);

    // 3. Envio AJAX (Rota: PATCH /admin/reservas/{reserva}/no-show, mas o web.php usa POST /admin/pagamentos/{reserva}/falta)
    fetch(`/admin/pagamentos/${reservaId}/falta`, { // Usando a rota que você definiu no web.php
        method: 'POST', // POST ou PATCH, dependendo da sua configuração, mantendo POST como no seu web.php
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
           if (!response.ok) {
               return response.json().then(data => {
                    // Trata o ValidationException
                   const validationErrors = data.errors ? Object.values(data.errors).flat().join('; ') : '';
                   throw new Error(data.message || data.error || validationErrors || 'Erro de processamento no servidor.');
               });
           }
           return response.json();
    })
    .then(data => {
        if(data.success) {
            showMessage(data.message);
            closeNoShowModal();

            // Recarrega a página para atualizar a tabela e o KPI de Faltas
            location.reload();
        } else {
            errorMessageDiv.textContent = data.message || 'Erro ao registrar falta.';
            errorMessageDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        errorMessageDiv.textContent = 'Erro ao registrar falta: ' + error.message;
        errorMessageDiv.classList.remove('hidden');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitText.classList.remove('hidden');
        submitSpinner.classList.add('hidden');
    });
});
</script>

</x-app-layout>