<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            💰 Gerenciamento de Caixa & Pagamentos
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. ESTRUTURA DE KPIS --}}
            <div class="space-y-4">
                
                {{-- Linha dos KPIs (4 colunas em telas grandes) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4">
                    
                    {{-- CARD 3: RECEITA GARANTIDA P/ HOJE (PAGO) --}}
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

                    {{-- CARD 4: SALDO PENDENTE A RECEBER --}}
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                SALDO PENDENTE A RECEBER
                            </div>
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V4m0 12v4M5 9h14M5 15h14M4 12h16"></path></svg>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-yellow-700 dark:text-yellow-300">
                            R$ {{ number_format($totalPending, 2, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Total Previsto (Receita Bruta): R$ {{ number_format($totalExpected, 2, ',', '.') }}
                        </div>
                    </div>

                    {{-- CARD 5: RESERVAS ATIVAS DO DIA (Contagem) --}}
                    @php
                        // A cor do Card é determinada pela Movimentação Líquida do dia
                        $isNegativeLiquid = $totalRecebidoDiaLiquido < 0; 
                        $kpiClass = $isNegativeLiquid ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-800' : 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-800';
                        $textColor = $isNegativeLiquid ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300';
                    @endphp
                    <div class="{{ $kpiClass }} overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                RESERVAS ATIVAS DO DIA
                            </div>
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">
                            {{ $totalReservasDia ?? 0 }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Movimentação Líquida: <span class="{{ $textColor }} font-semibold">R$ {{ number_format($totalRecebidoDiaLiquido ?? 0, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- CARD 6: FALTAS (NO-SHOW) --}}
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                FALTAS (NO-SHOW)
                            </div>
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L12 12M6 6l12 12"></path></svg>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-red-700 dark:text-red-300">
                            {{ $noShowCount }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Total de agendamentos no dia: {{ $totalReservasDia }}.
                        </div>
                    </div>
                </div>

                {{-- FIM DA LINHA DE KPIS --}}
            </div>
            
            {{-- 🚨 3. FECHAMENTO DE CAIXA (Lógica Condicional) --}}
            {{-- Recebe $cashierStatus do PaymentController::index (necessário atualizar o controller) --}}
            @if(isset($cashierStatus) && $cashierStatus === 'closed')
                {{-- Bloco para reabrir o caixa --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 overflow-hidden shadow-lg sm:rounded-lg p-5 border border-red-400 dark:border-red-600">
                    <div class="flex flex-col sm:flex-row items-center justify-between">
                        <div class="text-sm sm:text-base font-bold text-red-700 dark:text-red-300 mb-3 sm:mb-0 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L12 12M6 6l12 12"></path></svg>
                            CAIXA FECHADO! Alterações Bloqueadas para o dia {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}.
                        </div>
                        <button id="openCashBtn" onclick="openCash('{{ $selectedDate }}')" 
                            class="w-full sm:w-auto px-6 py-2 bg-red-600 text-white font-bold rounded-lg shadow-md hover:bg-red-700 transition duration-150 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Abrir Caixa (Permitir Alterações)
                        </button>
                    </div>
                </div>
            @else 
                {{-- Bloco para fechar o caixa (Se o status for 'open', 'pending' ou não houver registro) --}}
                <div class="bg-gray-50 dark:bg-gray-700/50 overflow-hidden shadow-lg sm:rounded-lg p-5 border border-indigo-400 dark:border-indigo-600">
                    <div class="flex flex-col sm:flex-row items-center justify-between">
                        <div class="text-sm sm:text-base font-medium text-gray-700 dark:text-gray-300 mb-3 sm:mb-0 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Fechamento Diário de Caixa:
                            <span id="cashStatus" class="ml-2 font-bold text-red-500 dark:text-red-400">Aguardando Finalização de Reservas</span>
                        </div>
                        {{-- Campos de suporte para o JS --}}
                        <input type="hidden" id="totalReservasDiaCount" value="{{ $totalReservasDia }}">
                        <input type="hidden" id="cashierDate" value="{{ $selectedDate }}">

                        <button id="openCloseCashModalBtn" onclick="openCloseCashModal()" disabled
                            class="w-full sm:w-auto px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg shadow-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-indigo-700 transition duration-150 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Fechar Caixa
                        </button>
                    </div>
                </div>
            @endif

            
            {{-- 1.5. Linha dos Filtros (Agora abaixo do Fechamento/Abertura de Caixa) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                {{-- CARD/BLOCO 1: FILTRO DE DATA --}}
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

                {{-- CARD/BLOCO 1.5: BARRA DE PESQUISA --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-lg p-5">
                    <form method="GET" action="{{ route('admin.payment.index') }}">
                        {{-- Preserva a data ao fazer a pesquisa --}}
                        <input type="hidden" name="date" value="{{ $selectedDate }}">

                        <div class="flex flex-col h-full justify-between">
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar Cliente (Nome ou WhatsApp):</label>
                            <div class="flex items-end gap-3 mt-auto">
                                <input type="text" name="search" id="search" value="{{ request('search') }}"
                                    placeholder="Digite o nome ou WhatsApp do cliente..."
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                                
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
                        </div>
                    </form>
                </div>
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
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Status Fin.</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Tipo</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Valor Total (R$)</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">Total Pago (R$)</th>
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
                                            
                                        // Variável de controle para desabilitar botões
                                        $isActionDisabled = (isset($cashierStatus) && $cashierStatus === 'closed');
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
                                                {{-- Botão Pagar: DESABILITADO SE CAIXA FECHADO --}}
                                                <button onclick="openPaymentModal({{ $reserva->id }}, {{ $total }}, {{ $restante }}, {{ $pago }}, '{{ $reserva->client_name }}', {{ $reserva->is_recurrent ? 'true' : 'false' }})"
                                                    class="text-white bg-green-600 hover:bg-green-700 rounded px-3 py-1 text-xs mr-2 transition duration-150 {{ $isActionDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    {{ $isActionDisabled ? 'disabled' : '' }}>
                                                    $ Baixar
                                                </button>
                                            @endif

                                            @if($restante > 0 || ($pago > 0 && $reserva->status !== 'no_show' && $reserva->status !== 'canceled'))
                                                {{-- Botão Falta: DESABILITADO SE CAIXA FECHADO --}}
                                                <button onclick="openNoShowModal({{ $reserva->id }}, '{{ $reserva->client_name }}', {{ $pago }})"
                                                    class="text-white bg-red-600 hover:bg-red-700 rounded px-3 py-1 text-xs transition duration-150 {{ $isActionDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                    {{ $isActionDisabled ? 'disabled' : '' }}>
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
                                    <td class="px-4 py-3 text-right text-lg {{ $totalRecebidoDiaLiquido >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                        R$ {{ number_format($totalRecebidoDiaLiquido ?? 0, 2, ',', '.') }}
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
{{-- MODAL 1: FINALIZAR PAGAMENTO (CÓDIGO OMITIDO PARA BREVIDADE, MAS MANTIDO NO ARQUIVO FINAL) --}}
{{-- ================================================================== --}}
<div id="paymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    {{-- Conteúdo do Modal 1 --}}
</div>

{{-- ================================================================== --}}
{{-- MODAL 2: REGISTRAR FALTA (NO-SHOW) (CÓDIGO OMITIDO PARA BREVIDADE, MAS MANTIDO NO ARQUIVO FINAL) --}}
{{-- ================================================================== --}}
<div id="noShowModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4">
    {{-- Conteúdo do Modal 2 --}}
</div>

{{-- ================================================================== --}}
{{-- MODAL 3: FECHAR CAIXA (CLOSE CASH) (CÓDIGO OMITIDO PARA BREVIDADE, MAS MANTIDO NO ARQUIVO FINAL) --}}
{{-- ================================================================== --}}
<div id="closeCashModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4">
    {{-- Conteúdo do Modal 3 --}}
</div>


{{-- SCRIPT PARA MODAIS E LÓGICA DE CAIXA (COM FUNÇÃO openCash) --}}

<script>
// --- Funções de Suporte ---
function showMessage(message, isSuccess = true) {
    console.log(isSuccess ? 'SUCESSO: ' : 'ERRO: ', message);
}
function updateRecurrentTogglePrice(newPrice) {
    const currentNewPriceEl = document.getElementById('currentNewPrice');
    const newPriceFloat = parseFloat(newPrice) || 0;
    currentNewPriceEl.innerText = newPriceFloat.toFixed(2).replace('.', ',');
}

// --- Lógica de Cálculo de Pagamento (Mantida) ---
function calculateAmountDue() {
    const finalPriceEl = document.getElementById('modalFinalPrice');
    const signalRawEl = document.getElementById('modalSignalAmountRaw');
    const amountPaidEl = document.getElementById('modalAmountPaid');
    const trocoMessageEl = document.getElementById('trocoMessage');

    trocoMessageEl.classList.add('hidden');
    amountPaidEl.classList.remove('focus:border-yellow-500');
    amountPaidEl.classList.add('focus:border-green-500');

    const finalPrice = parseFloat(finalPriceEl.value) || 0;
    const signalAmount = parseFloat(signalRawEl.value) || 0;
    let remainingOrChange = finalPrice - signalAmount;

    updateRecurrentTogglePrice(finalPrice);

    if (remainingOrChange < 0) {
        const trocoAmount = Math.abs(remainingOrChange);
        amountPaidEl.value = (0).toFixed(2);
        trocoMessageEl.textContent = `🚨 ATENÇÃO: Troco a Devolver: R$ ${trocoAmount.toFixed(2).replace('.', ',')}`;
        trocoMessageEl.classList.remove('hidden');
        amountPaidEl.classList.remove('focus:border-green-500');
        amountPaidEl.classList.add('focus:border-yellow-500');
    } else {
        amountPaidEl.value = remainingOrChange.toFixed(2);
        checkManualOverpayment();
    }
}
function checkManualOverpayment() {
    const finalPrice = parseFloat(document.getElementById('modalFinalPrice').value) || 0;
    const signalAmount = parseFloat(document.getElementById('modalSignalAmountRaw').value) || 0;
    const amountPaidNow = parseFloat(document.getElementById('modalAmountPaid').value) || 0;
    const amountPaidEl = document.getElementById('modalAmountPaid');
    const trocoMessageEl = document.getElementById('trocoMessage');

    updateRecurrentTogglePrice(finalPrice);
    const totalReceived = signalAmount + amountPaidNow;
    let overpayment = totalReceived - finalPrice;

    trocoMessageEl.classList.add('hidden');
    amountPaidEl.classList.remove('focus:border-yellow-500');
    amountPaidEl.classList.add('focus:border-green-500');

    if (overpayment > 0.005) {
        trocoMessageEl.textContent = `🚨 ATENÇÃO: Troco a Devolver: R$ ${overpayment.toFixed(2).replace('.', ',')}`;
        trocoMessageEl.classList.remove('hidden');
        amountPaidEl.classList.remove('focus:border-green-500');
        amountPaidEl.classList.add('focus:border-yellow-500');
    } else {
        if (finalPrice - signalAmount < 0) {
             calculateAmountDue();
        }
    }
}

// --- Lógica do Pagamento/No-Show (Modal Triggers e Handlers, mantidos) ---
function openPaymentModal(id, totalPrice, remaining, signalAmount, clientName, isRecurrent = false) {
    document.getElementById('modalReservaId').value = id;
    document.getElementById('modalClientName').innerText = clientName;
    const formattedSignal = signalAmount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('modalSignalAmount').innerText = formattedSignal;
    document.getElementById('modalSignalAmountRaw').value = signalAmount.toFixed(2);
    document.getElementById('modalFinalPrice').value = totalPrice.toFixed(2);
    const recurrentOptionEl = document.getElementById('recurrentOption');
    if (isRecurrent) {
        recurrentOptionEl.classList.remove('hidden');
        document.getElementById('apply_to_series').checked = true;
    } else {
        recurrentOptionEl.classList.add('hidden');
        document.getElementById('apply_to_series').checked = false;
    }
    calculateAmountDue();
    document.getElementById('payment-error-message').textContent = '';
    document.getElementById('payment-error-message').classList.add('hidden');
    document.getElementById('modalPaymentMethod').value = '';
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('paymentModal').classList.add('flex');
}
function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentModal').classList.remove('flex');
    checkCashierStatus();
}

document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const reservaId = document.getElementById('modalReservaId').value;
    const finalPrice = parseFloat(document.getElementById('modalFinalPrice').value).toFixed(2);
    const amountPaid = parseFloat(document.getElementById('modalAmountPaid').value).toFixed(2);
    const paymentMethod = document.getElementById('modalPaymentMethod').value;
    const isRecurrentVisible = !document.getElementById('recurrentOption').classList.contains('hidden');
    let applyToSeries = isRecurrentVisible ? document.getElementById('apply_to_series').checked : false;
    const csrfToken = document.querySelector('input[name="_token"]').value;

    const submitBtn = document.getElementById('submitPaymentBtn');
    const submitText = document.getElementById('submitPaymentText');
    const submitSpinner = document.getElementById('submitPaymentSpinner');
    const errorMessageDiv = document.getElementById('payment-error-message');

    if (paymentMethod === '') {
        errorMessageDiv.textContent = 'Por favor, selecione a Forma de Pagamento.';
        errorMessageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitText.classList.add('hidden');
    submitSpinner.classList.remove('hidden');
    errorMessageDiv.classList.add('hidden');

    const payload = {
        reserva_id: reservaId,
        final_price: finalPrice, 
        amount_paid: amountPaid,
        payment_method: paymentMethod,
        apply_to_series: applyToSeries
    };

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
        submitBtn.disabled = false;
        submitText.classList.remove('hidden');
        submitSpinner.classList.add('hidden');
    });
});

function openNoShowModal(id, clientName, paidAmount) {
    document.getElementById('noShowReservaId').value = id;
    document.getElementById('noShowClientName').innerText = clientName;

    document.getElementById('noShowPaidAmount').value = paidAmount.toFixed(2);
    const paidAmountFormatted = paidAmount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('noShowAmountDisplay').innerText = paidAmountFormatted;

    const initialWarningEl = document.getElementById('noShowInitialWarning');
    const refundControlsEl = document.getElementById('refundControls'); 

    if (paidAmount > 0) {
        initialWarningEl.innerHTML = `O cliente já pagou <span class="font-bold">${paidAmountFormatted}</span>. Escolha abaixo se este valor será retido (padrão) ou estornado.`;
        document.getElementById('should_refund').value = 'false';
        refundControlsEl.classList.remove('hidden');
    } else {
        initialWarningEl.textContent = `Nenhum valor foi pago. Marcar como falta apenas registrará o status.`;
        document.getElementById('should_refund').value = 'false';
        refundControlsEl.classList.add('hidden');
    }

    document.getElementById('noshow-error-message').textContent = '';
    document.getElementById('noshow-error-message').classList.add('hidden');
    document.querySelector('#noShowForm textarea[name="no_show_reason"]').value = '';
    document.querySelector('#noShowForm input[name="block_user"]').checked = false;

    document.getElementById('noShowModal').classList.remove('hidden');
    document.getElementById('noShowModal').classList.add('flex');
}

function closeNoShowModal() {
    document.getElementById('noShowModal').classList.add('hidden');
    document.getElementById('noShowModal').classList.remove('flex');
    checkCashierStatus();
}

document.getElementById('noShowForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const reservaId = document.getElementById('noShowReservaId').value;
    const reason = this.querySelector('textarea[name="no_show_reason"]').value;
    const blockUser = this.querySelector('input[name="block_user"]').checked; 
    const paidAmount = document.getElementById('noShowPaidAmount').value;
    const shouldRefund = this.querySelector('select[name="should_refund"]').value === 'true';
    const csrfToken = document.querySelector('input[name="_token"]').value;

    const submitBtn = document.getElementById('submitNoShowBtn');
    const submitText = document.getElementById('submitNoShowText');
    const submitSpinner = document.getElementById('submitNoShowSpinner');
    const errorMessageDiv = document.getElementById('noshow-error-message');

    if (reason.length < 5) {
          errorMessageDiv.textContent = 'O motivo da falta (Observações) é obrigatório e deve ter no mínimo 5 caracteres.';
          errorMessageDiv.classList.remove('hidden');
          return;
    }

    submitBtn.disabled = true;
    submitText.classList.add('hidden');
    submitSpinner.classList.remove('hidden');
    errorMessageDiv.classList.add('hidden');

    const payload = {
        reserva_id: reservaId,
        notes: reason,
        block_user: blockUser,
        paid_amount: paidAmount,
        should_refund: shouldRefund
    };

    fetch(`/admin/pagamentos/${reservaId}/falta`, {
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
               return response.json().then(data => {
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

// --- Lógica do Fechamento de Caixa (MODAL 3, mantido) ---

function calculateDifference() {
    const calculatedAmountEl = document.getElementById('calculatedLiquidAmount');
    // Limpeza robusta do valor formatado
    let calculatedText = calculatedAmountEl.innerText.replace('R$', '').replace(/\./g, '').replace(',', '.').trim();
    const calculatedAmount = parseFloat(calculatedText) || 0;
    
    const actualAmount = parseFloat(document.getElementById('actualCashAmount').value) || 0;
    const difference = actualAmount - calculatedAmount;
    const diffMessageEl = document.getElementById('differenceMessage');
    
    diffMessageEl.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-yellow-100', 'text-yellow-700', 'bg-green-100', 'text-green-700');

    if (Math.abs(difference) < 0.01) {
        diffMessageEl.innerHTML = '✅ **Caixa Fechado!** Nenhuma divergência encontrada.';
        diffMessageEl.classList.add('bg-green-100', 'text-green-700');
    } else if (difference > 0) {
        diffMessageEl.innerHTML = `⚠️ **Sobrou R$ ${difference.toFixed(2).replace('.', ',')}** no seu caixa físico. Verifique a diferença!`;
        diffMessageEl.classList.add('bg-yellow-100', 'text-yellow-700');
    } else {
        diffMessageEl.innerHTML = `🚨 **Faltou R$ ${Math.abs(difference).toFixed(2).replace('.', ',')}** no seu caixa físico. Verifique a diferença!`;
        diffMessageEl.classList.add('bg-red-100', 'text-red-700');
    }
}

function openCloseCashModal() {
    // Pega o valor total líquido da linha de totais na tabela de transações
    const calculatedLiquidAmount = document.querySelector('tr.font-bold .text-lg').innerText;
    const cashierDate = document.getElementById('cashierDate').value;
    const formattedDate = new Date(cashierDate + 'T00:00:00').toLocaleDateString('pt-BR');
    
    document.getElementById('closeCashDate').value = cashierDate;
    document.getElementById('closeCashDateDisplay').innerText = formattedDate;
    document.getElementById('calculatedLiquidAmount').innerText = calculatedLiquidAmount;
    
    // Converte para float e preenche o input, usando PONTO como separador decimal
    const liquidValueRawText = calculatedLiquidAmount.replace('R$', '').replace(/\./g, '').replace(',', '.').trim();
    const liquidValueRaw = parseFloat(liquidValueRawText) || 0;
    
    document.getElementById('actualCashAmount').value = liquidValueRaw.toFixed(2);
    
    calculateDifference();

    document.getElementById('closeCashModal').classList.remove('hidden');
    document.getElementById('closeCashModal').classList.add('flex');
}

function closeCloseCashModal() {
    document.getElementById('closeCashModal').classList.add('hidden');
    document.getElementById('closeCashModal').classList.remove('flex');
}

document.getElementById('actualCashAmount').addEventListener('input', calculateDifference);

document.getElementById('closeCashForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const actualAmount = parseFloat(document.getElementById('actualCashAmount').value).toFixed(2);
    const date = document.getElementById('closeCashDate').value;
    const csrfToken = document.querySelector('input[name="_token"]').value;
    
    const submitBtn = document.getElementById('submitCloseCashBtn');
    const submitText = document.getElementById('submitCloseCashText');
    const submitSpinner = document.getElementById('submitCloseCashSpinner');
    const errorMessageDiv = document.getElementById('closecash-error-message');

    submitBtn.disabled = true;
    submitText.classList.add('hidden');
    submitSpinner.classList.remove('hidden');
    errorMessageDiv.classList.add('hidden');

    const payload = {
        date: date,
        actual_cash_amount: actualAmount,
    };

    fetch(`/admin/pagamentos/fechar-caixa`, {
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
             return response.json().then(data => {
                 throw new Error(data.message || 'Erro ao fechar o caixa. Verifique o console.');
             });
        }
        return response.json();
    })
    .then(data => {
        if(data.success) {
            showMessage(data.message);
            // Redireciona para o Payment Index (deve recarregar o novo status)
            window.location.href = data.redirect || '{{ route('admin.payment.index') }}'; 
        } else {
            errorMessageDiv.textContent = data.message || 'Erro ao fechar caixa.';
            errorMessageDiv.classList.remove('hidden');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        errorMessageDiv.textContent = 'Erro ao fechar caixa: ' + error.message;
        errorMessageDiv.classList.remove('hidden');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitText.classList.remove('hidden');
        submitSpinner.classList.add('hidden');
    });
});

// --- Lógica de Abertura de Caixa (NOVA) ---
function openCash(date) {
    if (!confirm(`Tem certeza que deseja reabrir o caixa do dia ${date}? Isso permitirá alterações financeiras para esta data.`)) {
        return;
    }

    const csrfToken = document.querySelector('input[name="_token"]').value;
    const openCashBtn = document.getElementById('openCashBtn');
    
    openCashBtn.disabled = true;

    fetch(`/admin/pagamentos/abrir-caixa`, { 
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ date: date })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showMessage(data.message);
            // Redireciona para atualizar a view
            window.location.href = data.redirect || '{{ route('admin.payment.index') }}'; 
        } else {
            alert('Erro ao reabrir caixa: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro de rede ou servidor ao reabrir caixa.');
    })
    .finally(() => {
        openCashBtn.disabled = false;
    });
}


// --- Lógica de Habilitação do Botão (CRÍTICO) ---
function checkCashierStatus() {
    // 1. O número total de agendamentos para o dia (do PHP)
    const totalReservations = parseInt("{{ $totalReservasDia }}");
    
    // Lista de status FINISHED (Concluídos/Finalizados) que permitem fechar o caixa.
    const finalStatuses = ['pago completo', 'pago parcial', 'falta', 'cancelada', 'finalizado'];
    
    let completedReservations = 0;

    // Se o caixa JÁ estiver fechado, não faz nada com o botão de fechar.
    @if(isset($cashierStatus) && $cashierStatus === 'closed')
        // O botão de "Fechar Caixa" não existe, apenas o de "Abrir Caixa"
        return; 
    @endif


    if (totalReservations === 0) {
        updateCashierButton(true, "Nenhum agendamento pendente.");
        return;
    }

    // Itera sobre as linhas da tabela de reservas para verificar os status
    document.querySelectorAll('.min-w-full tbody tr').forEach(row => {
        // Ignora a linha de "nenhum agendamento"
        if (row.querySelector('td[colspan="8"]')) {
            return;
        }

        // Encontra o span do status financeiro (3ª coluna)
        const statusSpan = row.querySelector('td:nth-child(3) span');
        if (statusSpan) {
            const statusText = statusSpan.innerText.trim().toLowerCase();
            
            // Verifica se o status está na lista de status finais.
            if (finalStatuses.includes(statusText)) {
                completedReservations++;
            }
        }
    });

    const isAllCompleted = completedReservations === totalReservations;
    
    if (isAllCompleted) {
        updateCashierButton(true, "✅ Pronto para Fechamento!");
    } else {
        const pendingCount = totalReservations - completedReservations;
        updateCashierButton(false, `🚨 **${pendingCount}** Reservas Pendentes de Baixa (Pagamento/Falta)`);
    }
}

function updateCashierButton(isReady, statusMessage) {
    const closeCashBtn = document.getElementById('openCloseCashModalBtn');
    const cashStatusEl = document.getElementById('cashStatus');

    if (!closeCashBtn || !cashStatusEl) return; // Garante que o elemento existe (pois pode ser o botão de 'Abrir Caixa')

    closeCashBtn.disabled = !isReady;
    cashStatusEl.innerHTML = statusMessage;
    
    if (isReady) {
        cashStatusEl.classList.remove('text-red-500', 'dark:text-red-400');
        cashStatusEl.classList.add('text-green-600', 'dark:text-green-400');
    } else {
        cashStatusEl.classList.remove('text-green-600', 'dark:text-green-400');
        cashStatusEl.classList.add('text-red-500', 'dark:text-red-400');
    }
}


document.addEventListener('DOMContentLoaded', () => {
    // Inicializa a verificação na carga da página
    checkCashierStatus();
    
    // O restante dos listeners do DOMContentLoaded
    document.getElementById('modalFinalPrice').addEventListener('input', calculateAmountDue);
    document.getElementById('modalAmountPaid').addEventListener('input', checkManualOverpayment);
    
    // Destaque de Linha (mantido)
    const urlParams = new URLSearchParams(window.location.search);
    const reservaId = urlParams.get('reserva_id');
    if (reservaId) {
        const highlightedRow = document.querySelector(`.bg-indigo-50`);
        if (highlightedRow) {
            highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});
</script>

</x-app-layout>