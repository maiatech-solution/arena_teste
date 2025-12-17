<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            💰 Gerenciamento de Caixa & Pagamentos
        </h2>
    </x-slot>

    @php
        // Variável de controle para desabilitar ações se o caixa estiver fechado
        $isActionDisabled = isset($cashierStatus) && $cashierStatus === 'closed';
        // Para garantir que exista, caso não venha do Controller
        $totalReservasDia = $totalReservasDia ?? 0;
        $totalRecebidoDiaLiquido = $totalRecebidoDiaLiquido ?? 0;
        $totalAntecipadoReservasDia = $totalAntecipadoReservasDia ?? 0;
        $totalPending = $totalPending ?? 0;
        $totalExpected = $totalExpected ?? 0;
        $noShowCount = $noShowCount ?? 0;
    @endphp

    @php
        // ... suas variáveis existentes ...
        $dataHoje = \Carbon\Carbon::today()->toDateString(); // 🎯 Adicione esta linha
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. ESTRUTURA DE KPIS --}}
            <div class="space-y-4">

                {{-- Linha dos KPIs (4 colunas em telas grandes) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4">

                    {{-- CARD 3: RECEITA GARANTIDA P/ HOJE (PAGO) --}}
                    <div
                        class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-300 dark:border-indigo-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                RECEITA GARANTIDA P/ HOJE (PAGO)
                            </div>
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.504A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.552L12 12m0 0l-8.618 3.552A11.955 11.955 0 0012 21.056a11.955 11.955 0 008.618-3.552L12 12z">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-indigo-700 dark:text-indigo-300">
                            R$ {{ number_format($totalAntecipadoReservasDia, 2, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Valor total pago para as reservas agendadas hoje.
                        </div>
                    </div>

                    {{-- CARD 4: SALDO PENDENTE A RECEBER --}}
                    <div
                        class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                SALDO PENDENTE A RECEBER
                            </div>
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V4m0 12v4M5 9h14M5 15h14M4 12h16">
                                </path>
                            </svg>
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
                        $kpiClass = $isNegativeLiquid
                            ? 'bg-red-50 dark:bg-red-900/20 border-red-300 dark:border-red-800'
                            : 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-800';
                        $textColor = $isNegativeLiquid
                            ? 'text-red-700 dark:text-red-300'
                            : 'text-green-700 dark:text-green-300';
                    @endphp
                    <div
                        class="{{ $kpiClass }} overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                RESERVAS ATIVAS DO DIA
                            </div>
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">
                            {{ $totalReservasDia ?? 0 }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Movimentação Líquida: <span class="{{ $textColor }} font-semibold">R$
                                {{ number_format($totalRecebidoDiaLiquido ?? 0, 2, ',', '.') }}</span>
                        </div>
                    </div>

                    {{-- CARD 6: FALTAS (NO-SHOW) --}}
                    <div
                        class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-center">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                FALTAS (NO-SHOW)
                            </div>
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L12 12M6 6l12 12">
                                </path>
                            </svg>
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
            @if (isset($cashierStatus) && $cashierStatus === 'closed')
                {{-- Bloco para reabrir o caixa --}}
                <div
                    class="bg-gray-50 dark:bg-gray-700/50 overflow-hidden shadow-lg sm:rounded-lg p-5 border border-red-400 dark:border-red-600">
                    <div class="flex flex-col sm:flex-row items-center justify-between">
                        <div
                            class="text-sm sm:text-base font-bold text-red-700 dark:text-red-300 mb-3 sm:mb-0 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L12 12M6 6l12 12">
                                </path>
                            </svg>
                            CAIXA FECHADO! Alterações Bloqueadas para o dia
                            {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}.
                        </div>
                        {{-- O onclick AGORA chama a função que abre o novo modal --}}
                        <button id="openCashBtn" onclick="openCash('{{ $selectedDate }}')"
                            class="w-full sm:w-auto px-6 py-2 bg-red-600 text-white font-bold rounded-lg shadow-md hover:bg-red-700 transition duration-150 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Abrir Caixa (Permitir Alterações)
                        </button>
                    </div>
                </div>
            @else
                {{-- Bloco para fechar o caixa (Se o status for 'open', 'pending' ou não houver registro) --}}
                <div
                    class="bg-gray-50 dark:bg-gray-700/50 overflow-hidden shadow-lg sm:rounded-lg p-5 border border-indigo-400 dark:border-indigo-600">
                    <div class="flex flex-col sm:flex-row items-center justify-between">
                        <div
                            class="text-sm sm:text-base font-medium text-gray-700 dark:text-gray-300 mb-3 sm:mb-0 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Fechamento Diário de Caixa:
                            <span id="cashStatus" class="ml-2 font-bold text-red-500 dark:text-red-400">Aguardando
                                Finalização de Reservas</span>
                        </div>
                        {{-- Campos de suporte para o JS --}}
                        <input type="hidden" id="totalReservasDiaCount" value="{{ $totalReservasDia }}">
                        <input type="hidden" id="cashierDate" value="{{ $selectedDate }}">

                        <button id="openCloseCashModalBtn" onclick="openCloseCashModal()" disabled
                            class="w-full sm:w-auto px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg shadow-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-indigo-700 transition duration-150 flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Fechar Caixa
                        </button>
                    </div>
                </div>
            @endif



            {{-- 1.5. Linha dos Filtros (Agora abaixo do Fechamento/Abertura de Caixa) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- CARD/BLOCO 1: FILTRO DE DATA --}}
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-between border border-gray-200 dark:border-gray-700">
                    <form method="GET" action="{{ route('admin.payment.index') }}">
                        <input type="hidden" name="search" value="{{ request('search') }}">

                        <div class="flex items-center justify-between">
                            <label for="date"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                Data do Caixa:
                            </label>
                            @if (request()->has('reserva_id'))
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
                            <label for="search"
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar Cliente
                                (Nome ou WhatsApp):</label>
                            <div class="flex items-end gap-3 mt-auto">
                                <input type="text" name="search" id="search" value="{{ request('search') }}"
                                    placeholder="Digite o nome ou WhatsApp do cliente..."
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">

                                <button type="submit"
                                    class="h-10 px-4 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>
                                @if (request()->has('search'))
                                    <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'reserva_id' => request('reserva_id')]) }}"
                                        class="h-10 px-2 py-1 flex items-center justify-center text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 transition duration-150"
                                        title="Limpar pesquisa">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
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
                        @if (request()->has('reserva_id'))
                            <span class="text-indigo-500">Reserva Selecionada (ID: {{ request('reserva_id') }})</span>
                        @else
                            Agendamentos do Dia ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                        @endif

                        {{-- Botão Voltar para a visão diária, se estiver no filtro de ID --}}
                        @if (request()->has('reserva_id'))
                            <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'search' => request('search')]) }}"
                                class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Ver Todas do Dia
                            </a>
                        @endif
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">
                                        Horário</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-3/12">
                                        Cliente</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">
                                        Status Fin.</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">
                                        Tipo</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">
                                        Valor Total (R$)</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">
                                        Total Pago (R$)</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">
                                        Saldo a Pagar</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/12">
                                        Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
    @forelse ($reservas as $reserva)
        @php
            // 1. Cálculos Financeiros
            $total = $reserva->final_price ?? $reserva->price;
            $pago = $reserva->total_paid;
            $restante = max(0, $total - $pago);
            $currentStatus = $reserva->payment_status;

            // 2. Identificação de HOJE (Comparação com o relógio do servidor)
            $dataHoje = \Carbon\Carbon::today()->toDateString();
            $dataReserva = \Carbon\Carbon::parse($reserva->date)->toDateString();
            $eHoje = ($dataReserva === $dataHoje);

            // 3. Lógica de Atraso
            $isOverdue = false;
            if (in_array($reserva->status, ['confirmed', 'pending'])) {
                $onlyTime = \Carbon\Carbon::parse($reserva->end_time)->format('H:i:s');
                $dateTimeString = $dataReserva . ' ' . $onlyTime;
                try {
                    $reservaEndTime = \Carbon\Carbon::parse($dateTimeString);
                    if ($reservaEndTime->isPast()) { $isOverdue = true; }
                } catch (\Exception $e) { $isOverdue = false; }
            }

            // 4. Definição de Cores de Status
            if ($reserva->status === 'no_show') {
                $statusClass = 'bg-red-500 text-white font-bold';
                $statusLabel = 'FALTA';
            } elseif ($reserva->status === 'canceled' || $reserva->status === 'rejected') {
                $statusClass = 'bg-gray-400 text-white font-bold';
                $statusLabel = strtoupper($reserva->status);
            } elseif ($currentStatus === 'paid' || $reserva->status === 'completed') {
                $statusClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                $statusLabel = 'PAGO COMPLETO';
            } elseif ($currentStatus === 'partial') {
                $statusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
                $statusLabel = 'PAGO PARCIAL';
            } elseif ($isOverdue) {
                $statusClass = 'bg-red-700 text-white font-bold animate-pulse shadow-xl';
                $statusLabel = 'ATRASADO';
            } else {
                $statusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                $statusLabel = $pago > 0 ? 'SINAL DADO' : 'PENDENTE';
            }

            // 5. DESTAQUE VISUAL (CORES AGRESSIVAS PARA TESTE)
            if (isset($highlightReservaId) && $reserva->id == $highlightReservaId) {
                // Destaque quando você clica no Dashboard (Amarelo)
                $rowHighlight = 'bg-yellow-100 dark:bg-yellow-900/50 border-l-8 border-yellow-500';
            } elseif ($eHoje) {
                // DESTAQUE DE HOJE (Azul com borda lateral grossa)
                $rowHighlight = 'bg-blue-100/70 dark:bg-blue-900/40 border-l-8 border-blue-600';
            } else {
                // Outros dias (Apenas hover normal)
                $rowHighlight = 'hover:bg-gray-100 dark:hover:bg-gray-700';
            }
        @endphp

        <tr class="{{ $rowHighlight }} transition duration-150">
            <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                <div class="flex flex-col">
                    <span>{{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}</span>
                    @if($eHoje)
                        <span class="text-[10px] text-blue-700 dark:text-blue-400 font-extrabold uppercase mt-1">● Agendado p/ Hoje</span>
                    @endif
                </div>
            </td>
            <td class="px-4 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $reserva->client_name }} (ID: {{ $reserva->id }})</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    @if ($reserva->user && $reserva->user->is_vip)
                        <span class="text-indigo-600 dark:text-indigo-400 font-bold">★ VIP</span>
                    @endif
                    {{ $reserva->client_contact }}
                </div>
            </td>
            <td class="px-4 py-4 whitespace-nowrap text-center">
                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>
            </td>
            <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                @if ($reserva->is_recurrent)
                    <span class="font-semibold text-fuchsia-600">Recorrente</span>
                @else
                    <span class="font-semibold text-blue-600">Pontual</span>
                @endif
            </td>
            <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white">
                {{ number_format($total, 2, ',', '.') }}
            </td>
            <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-green-600 font-bold">
                {{ number_format($pago, 2, ',', '.') }}
            </td>
            <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-black {{ $restante > 0 ? 'text-red-600' : 'text-gray-400' }}">
                {{ number_format($restante, 2, ',', '.') }}
            </td>
            <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                @php
                    $canPay = $restante > 0 && !in_array($reserva->status, ['canceled', 'rejected']);
                    $canBeNoShow = !in_array($reserva->status, ['no_show', 'canceled', 'rejected', 'completed']);
                @endphp

                @if ($canPay)
                    <button onclick="openPaymentModal({{ $reserva->id }}, {{ $total }}, {{ $restante }}, {{ $pago }}, '{{ $reserva->client_name }}', {{ $reserva->is_recurrent ? 'true' : 'false' }})"
                        class="text-white bg-green-600 hover:bg-green-700 rounded px-3 py-1 text-xs mr-2 transition {{ $isActionDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ $isActionDisabled ? 'disabled' : '' }}>
                        $ Baixar
                    </button>
                @endif

                @if ($canBeNoShow)
                    <button onclick="openNoShowModal({{ $reserva->id }}, '{{ $reserva->client_name }}', {{ $pago }})"
                        class="text-white bg-red-600 hover:bg-red-700 rounded px-3 py-1 text-xs transition {{ $isActionDisabled ? 'opacity-50 cursor-not-allowed' : '' }}"
                        {{ $isActionDisabled ? 'disabled' : '' }}>
                        X Falta
                    </button>
                @elseif($reserva->status === 'no_show')
                    <span class="text-xs text-red-500 italic font-medium">Falta Registrada</span>
                @elseif($pago >= $total)
                    <span class="text-xs text-green-500 italic font-medium">Finalizado</span>
                @endif
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 font-medium italic">
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
                    <h3
                        class="text-lg font-semibold mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        Movimentação Detalhada de Caixa ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                        <span class="text-sm font-normal text-gray-500 ml-3">(Sinais, Pagamentos, Retenções e
                            Estornos)</span>
                    </h3>

                    @php
                        // 1. Agrupamos as transações por ID da Reserva (Chave de Agrupamento)
                        $groupedTransactions = $financialTransactions->groupBy('reserva_id');
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">
                                        Hora</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">
                                        ID</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/12">
                                        Pagador / Gestor</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-2/12">
                                        Tipo | Forma</th>
                                    {{-- LARGURA DE VOLTA AO ORIGINAL, mas sem truncamento --}}
                                    <th
                                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-4/12">
                                        Descrição</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/12">
                                        Valor (R$)</th>
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
                                            $reservaInfo =
                                                'ID: ' .
                                                $reservaId .
                                                ($clientName !== 'N/A'
                                                    ? ' - ' . $clientName
                                                    : ' (Reserva Ausente/Cancelada)');
                                        @endphp
                                        <tr
                                            class="bg-gray-100 dark:bg-gray-700/50 border-t-2 border-indigo-400 dark:border-indigo-600">
                                            {{-- Colspan ajustado para 5 colunas de texto --}}
                                            <td colspan="5"
                                                class="px-4 py-2 text-sm font-bold text-gray-800 dark:text-gray-200">
                                                ✅ MOVIMENTOS DA RESERVA <span
                                                    class="text-indigo-600 dark:text-indigo-400">{{ $reservaInfo }}</span>
                                            </td>
                                            <td
                                                class="px-4 py-2 text-right text-sm font-bold text-gray-800 dark:text-gray-200">
                                                TOTAL: R$
                                                {{ number_format($transactions->sum('amount'), 2, ',', '.') }}
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
                                            $rowClass = $isRefund
                                                ? 'bg-red-50 dark:bg-red-900/30 hover:bg-red-100'
                                                : 'hover:bg-gray-50 dark:hover:bg-gray-700';
                                            $amountClass = $isPositive
                                                ? 'text-green-600 font-bold'
                                                : 'text-red-600 font-bold';

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
                                            $displayType =
                                                $typeMap[$transaction->type] ??
                                                ucwords(str_replace('_', ' ', $transaction->type));

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
                                            $displayMethod =
                                                $methodMap[$transaction->payment_method] ??
                                                ucwords(str_replace('_', ' ', $transaction->payment_method));
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td
                                                class="px-4 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($transaction->paid_at)->format('H:i:s') }}
                                            </td>
                                            <td
                                                class="px-4 py-2 whitespace-nowrap text-sm font-medium {{ $isRefund ? 'text-red-800 dark:text-red-400' : 'text-indigo-600 dark:text-indigo-400' }}">
                                                {{ $transaction->reserva_id ?? '--' }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm">
                                                <div class="text-gray-900 dark:text-white font-medium">
                                                    {{ $transaction->payer->name ?? 'Caixa Geral' }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">Registrado por:
                                                    {{ $transaction->manager->name ?? 'Desconhecido' }}</div>
                                            </td>
                                            <td
                                                class="px-4 py-2 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                <div class="font-medium text-gray-900 dark:text-white">
                                                    {{ $displayType }}</div>
                                                <div class="text-xs text-gray-500">({{ $displayMethod }})</div>
                                            </td>
                                            {{-- Correção: Permite a quebra de linha --}}
                                            <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                                                {{ $transaction->description }}
                                            </td>
                                            <td
                                                class="px-4 py-2 whitespace-nowrap text-sm text-right {{ $amountClass }}">
                                                {{ number_format($amount, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach

                                @empty
                                    <tr>
                                        <td colspan="6"
                                            class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                            Nenhuma transação financeira registrada para esta data.
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="bg-gray-100 dark:bg-gray-700 font-bold">
                                    <td colspan="5"
                                        class="px-4 py-3 text-right text-gray-800 dark:text-gray-200 uppercase">
                                        Total Líquido do Dia:
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-lg {{ $totalRecebidoDiaLiquido >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
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
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 8v8m-4-8v8m-4-8v8M4 16h16a2 2 0 002-2V8a2 2 0 00-2-2H4a2 2 0 00-2 2v6a2 2 0 002 2z">
                        </path>
                    </svg>
                    Ir para Relatórios
                </a>
            </div>

        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- MODAIS (MANTIDOS SEM ALTERAÇÃO NA ESTRUTURA) --}}
    {{-- ================================================================== --}}

    {{-- MODAL 1: FINALIZAR PAGAMENTO --}}
    <div id="paymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div
            class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form id="paymentForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Baixar Pagamento da Reserva
                            </h3>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <p>Cliente: <span id="modalClientName" class="font-bold"></span></p>
                                <p>Sinal Pago (Base): <span id="modalSignalAmount"
                                        class="font-bold text-green-600"></span></p>
                            </div>
                            <input type="hidden" id="modalReservaId" name="reserva_id">
                            <input type="hidden" id="modalSignalAmountRaw" name="signal_amount_raw">

                            <div class="mt-4 space-y-3">
                                <div>
                                    <label for="modalFinalPrice"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Valor Total Final (Pode ser editado para dar desconto)
                                    </label>
                                    <input type="number" step="0.01" id="modalFinalPrice" name="final_price"
                                        oninput="calculateAmountDue()" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white font-bold text-lg">
                                </div>
                                <div>
                                    <label for="modalAmountPaid"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Valor Recebido AGORA (Saldo a Pagar)
                                    </label>
                                    <input type="number" step="0.01" id="modalAmountPaid" name="amount_paid"
                                        oninput="checkManualOverpayment()" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white font-bold text-xl">
                                    <p id="trocoMessage" class="hidden mt-1 text-sm font-semibold text-yellow-700">
                                        Troco a Devolver: R$ 0,00</p>
                                </div>
                                <div>
                                    <label for="modalPaymentMethod"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Forma de Pagamento
                                    </label>
                                    <select id="modalPaymentMethod" name="payment_method" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                                        <option value="">-- Selecione a forma de pagamento --</option>
                                        <option value="pix">PIX</option>
                                        <option value="money">Dinheiro</option>
                                        <option value="credit_card">Cartão de Crédito</option>
                                        <option value="debit_card">Cartão de Débito</option>
                                        <option value="transfer">Transferência Bancária</option>
                                        <option value="other">Outro / Cortesia</option>
                                    </select>
                                </div>
                                {{-- Opção de Recorrência (Hidden por padrão) --}}
                                <div id="recurrentOption"
                                    class="hidden pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <input type="checkbox" id="apply_to_series" name="apply_to_series"
                                        value="1"
                                        class="rounded dark:bg-gray-700 border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                    <label for="apply_to_series"
                                        class="ml-2 text-sm text-gray-700 dark:text-gray-300 font-medium">
                                        Aplicar novo preço (R$ <span id="currentNewPrice"></span>) a todas as reservas
                                        futuras desta série?
                                    </label>
                                </div>
                            </div>
                            <div id="payment-error-message"
                                class="hidden mt-3 p-3 bg-red-100 text-red-700 text-sm font-medium rounded-lg"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" id="submitPaymentBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm transition duration-150">
                        <span id="submitPaymentText">Finalizar Pagamento</span>
                        <svg id="submitPaymentSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>
                    <button type="button" onclick="closePaymentModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm transition duration-150">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: REGISTRAR FALTA (NO-SHOW) --}}
    <div id="noShowModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div
            class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form id="noShowForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L12 12M6 6l12 12">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Registrar Falta (No-Show)
                            </h3>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <p>Cliente: <span id="noShowClientName" class="font-bold"></span></p>
                                <p id="noShowInitialWarning"
                                    class="mt-2 p-2 rounded-md bg-yellow-50 dark:bg-yellow-900/20">
                                    Nenhum valor foi pago. Marcar como falta apenas registrará o status.
                                </p>
                            </div>

                            <input type="hidden" id="noShowReservaId" name="reserva_id">
                            <input type="hidden" id="noShowPaidAmount" name="paid_amount">

                            <div id="refundControls" class="mt-4 space-y-3 hidden">
                                <label for="should_refund"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Ação com o valor já pago (<span id="noShowAmountDisplay"
                                        class="font-bold"></span>)
                                </label>
                                <select id="should_refund" name="should_refund"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                                    <option value="false">Reter o valor (Padrão: Multa)</option>
                                    <option value="true">Estornar/Devolver o valor</option>
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Se estornado, uma transação
                                    negativa será registrada.</p>
                            </div>

                            <div class="mt-4">
                                <label for="no_show_reason"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Observações (Obrigatório)
                                </label>
                                <textarea id="no_show_reason" name="no_show_reason" rows="3" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="Motivo da falta, se conhecido, ou observações sobre o bloqueio..."></textarea>
                            </div>

                            <div class="mt-4">
                                <input type="checkbox" id="block_user" name="block_user"
                                    class="rounded dark:bg-gray-700 border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                                <label for="block_user" class="ml-2 text-sm font-bold text-red-700 dark:text-red-400">
                                    Bloquear cliente permanentemente (Devido à falta)
                                </label>
                            </div>
                            <div id="noshow-error-message"
                                class="hidden mt-3 p-3 bg-red-100 text-red-700 text-sm font-medium rounded-lg"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" id="submitNoShowBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition duration-150">
                        <span id="submitNoShowText">Registrar Falta</span>
                        <svg id="submitNoShowSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>
                    <button type="button" onclick="closeNoShowModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm transition duration-150">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- MODAL 3: FECHAR CAIXA (CLOSE CASH) --}}
    <div id="closeCashModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div
            class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form id="closeCashForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Fechamento de Caixa: <span id="closeCashDateDisplay"></span>
                            </h3>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                <p>Confirme os valores para finalizar as operações financeiras do dia.</p>
                            </div>

                            <div class="mt-4 space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Total de Movimentação CALCULADO pelo Sistema
                                    </label>
                                    <p id="calculatedLiquidAmount"
                                        class="mt-1 block w-full bg-gray-100 dark:bg-gray-900 p-2 rounded-md font-bold text-lg text-indigo-600">
                                        R$ 0,00</p>
                                </div>
                                <div>
                                    <label for="actualCashAmount"
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Valor TOTAL EM CAIXA FÍSICO (Para Comparação)
                                    </label>
                                    <input type="number" step="0.01" id="actualCashAmount"
                                        name="actual_cash_amount" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:text-white font-bold text-xl">
                                </div>
                            </div>

                            <div id="differenceMessage" class="hidden mt-3 p-3 text-sm font-semibold rounded-lg">
                            </div>
                            <input type="hidden" id="closeCashDate" name="date">
                            <div id="closecash-error-message"
                                class="hidden mt-3 p-3 bg-red-100 text-red-700 text-sm font-medium rounded-lg"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" id="submitCloseCashBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm transition duration-150">
                        <span id="submitCloseCashText">Confirmar Fechamento</span>
                        <svg id="submitCloseCashSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>
                    <button type="button" onclick="closeCloseCashModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm transition duration-150">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- MODAL 4: ABRIR CAIXA (OPEN CASH) - Exige Justificativa --}}
    <div id="openCashModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <div
            class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form id="openCashForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.3 16c-.77 1.333.192 3 1.732 3z">
                                </path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Reabrir Caixa Diário
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    O caixa do dia <span id="openCashDateDisplay"
                                        class="font-bold text-red-500"></span> está **FECHADO**. Para reabri-lo e
                                    permitir alterações, é necessário registrar o motivo.
                                </p>
                            </div>

                            <div class="mt-4">
                                <label for="reopen_reason"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Justificativa da Reabertura <span class="text-red-500">*</span>
                                </label>
                                <textarea id="reopen_reason" name="reopen_reason" rows="3" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                                    placeholder="Ex: Preciso corrigir o valor de troco de uma reserva."></textarea>
                            </div>
                            <input type="hidden" id="reopenCashDate" name="date">
                            <div id="openCash-error-message"
                                class="hidden mt-3 p-3 bg-red-100 text-red-700 text-sm font-medium rounded-lg"></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" id="submitOpenCashBtn"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm transition duration-150">
                        <span id="submitOpenCashText">Reabrir e Salvar Justificativa</span>
                        <svg id="submitOpenCashSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </button>
                    <button type="button" onclick="closeOpenCashModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm transition duration-150">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- SCRIPT PARA MODAIS E LÓGICA DE CAIXA --}}

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
            // Verifica o status de bloqueio global antes de abrir
            if (document.getElementById('openCloseCashModalBtn') && document.getElementById('openCloseCashModalBtn')
                .disabled) {
                // Se o botão de fechar caixa estiver desabilitado, significa que o caixa está fechado
                // A lógica do Blade já deve ter bloqueado, mas esta é uma proteção extra se a data for passada
                if ("{{ $isActionDisabled }}" === '1') {
                    showMessage(
                        'Ações bloqueadas. O caixa para esta data está FECHADO. Por favor, reabra o caixa primeiro.',
                        false);
                    return;
                }
            }

            document.getElementById('modalReservaId').value = id;
            document.getElementById('modalClientName').innerText = clientName;
            const formattedSignal = signalAmount.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
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
                            throw new Error(data.message ||
                                'Erro de validação ou processamento no servidor.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
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
            // Verifica o status de bloqueio global antes de abrir
            if (document.getElementById('openCloseCashModalBtn') && document.getElementById('openCloseCashModalBtn')
                .disabled) {
                if ("{{ $isActionDisabled }}" === '1') {
                    showMessage(
                        'Ações bloqueadas. O caixa para esta data está FECHADO. Por favor, reabra o caixa primeiro.',
                        false);
                    return;
                }
            }

            document.getElementById('noShowReservaId').value = id;
            document.getElementById('noShowClientName').innerText = clientName;

            document.getElementById('noShowPaidAmount').value = paidAmount.toFixed(2);
            const paidAmountFormatted = paidAmount.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
            document.getElementById('noShowAmountDisplay').innerText = paidAmountFormatted;

            const initialWarningEl = document.getElementById('noShowInitialWarning');
            const refundControlsEl = document.getElementById('refundControls');

            if (paidAmount > 0) {
                initialWarningEl.innerHTML =
                    `O cliente já pagou <span class="font-bold">${paidAmountFormatted}</span>. Escolha abaixo se este valor será retido (padrão: multa) ou estornado.`;
                document.getElementById('should_refund').value = 'false';
                refundControlsEl.classList.remove('hidden');
            } else {
                initialWarningEl.textContent =
                    `Nenhum valor foi pago. Marcar como falta apenas registrará o status e o débito total para o cliente.`;
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
                errorMessageDiv.textContent =
                    'O motivo da falta (Observações) é obrigatório e deve ter no mínimo 5 caracteres.';
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
                            const validationErrors = data.errors ? Object.values(data.errors).flat()
                                .join('; ') : '';
                            throw new Error(data.message || data.error || validationErrors ||
                                'Erro de processamento no servidor.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
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

            diffMessageEl.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-yellow-100', 'text-yellow-700',
                'bg-green-100', 'text-green-700');

            if (Math.abs(difference) < 0.01) {
                diffMessageEl.innerHTML = '✅ **Caixa Fechado!** Nenhuma divergência encontrada.';
                diffMessageEl.classList.add('bg-green-100', 'text-green-700');
            } else if (difference > 0) {
                diffMessageEl.innerHTML =
                    `⚠️ **Sobrou R$ ${difference.toFixed(2).replace('.', ',')}** no seu caixa físico. Verifique a diferença!`;
                diffMessageEl.classList.add('bg-yellow-100', 'text-yellow-700');
            } else {
                diffMessageEl.innerHTML =
                    `🚨 **Faltou R$ ${Math.abs(difference).toFixed(2).replace('.', ',')}** no seu caixa físico. Verifique a diferença!`;
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

        // 🚨 CORRIGIDO: Proteção contra elemento nulo (o erro 990 deve ser resolvido aqui)
        document.addEventListener('DOMContentLoaded', () => {
            const actualCashAmountEl = document.getElementById('actualCashAmount');
            if (actualCashAmountEl) {
                actualCashAmountEl.addEventListener('input', calculateDifference);
            }
        });


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
                calculated_amount: parseFloat(document.querySelector('tr.font-bold .text-lg').innerText.replace(
                    'R$', '').replace(/\./g, '').replace(',', '.').trim()), // Passa o valor calculado
                actual_amount: actualAmount,
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
                            throw new Error(data.message ||
                                'Erro ao fechar o caixa. Verifique o console.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
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

        // --- Lógica de Abertura de Caixa (MODAL 4) ---

        // 🎯 Nova função que abre o modal de justificativa
        function openCashModal(dateString) {
            const formattedDate = new Date(dateString + 'T00:00:00').toLocaleDateString('pt-BR');

            document.getElementById('reopenCashDate').value = dateString;
            document.getElementById('openCashDateDisplay').innerText = formattedDate;
            document.getElementById('reopen_reason').value = '';
            document.getElementById('openCash-error-message').classList.add('hidden');

            document.getElementById('openCashModal').classList.remove('hidden');
            document.getElementById('openCashModal').classList.add('flex');
        }

        function closeOpenCashModal() {
            document.getElementById('openCashModal').classList.add('hidden');
            document.getElementById('openCashModal').classList.remove('flex');
        }

        // Função de clique no botão "Abrir Caixa"
        function openCash(date) {
            // Agora chama o modal, em vez do confirm()
            openCashModal(date);
        }

        // Nova função para lidar com o envio do formulário do modal de reabertura
        document.getElementById('openCashForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const date = document.getElementById('reopenCashDate').value;
            const reason = document.getElementById('reopen_reason').value.trim();
            const csrfToken = document.querySelector('input[name="_token"]').value;

            const submitBtn = document.getElementById('submitOpenCashBtn');
            const submitText = document.getElementById('submitOpenCashText');
            const submitSpinner = document.getElementById('submitOpenCashSpinner');
            const errorMessageDiv = document.getElementById('openCash-error-message');

            if (reason.length < 5) {
                errorMessageDiv.textContent = 'A justificativa é obrigatória e deve ter no mínimo 5 caracteres.';
                errorMessageDiv.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitText.classList.add('hidden');
            submitSpinner.classList.remove('hidden');
            errorMessageDiv.classList.add('hidden');

            const payload = {
                date: date,
                reason: reason // 🎯 ENVIANDO O MOTIVO
            };

            fetch(`/admin/pagamentos/abrir-caixa`, {
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
                            throw new Error(data.message ||
                                'Erro ao reabrir o caixa. Verifique o console.');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showMessage(data.message);
                        closeOpenCashModal();
                        // Redireciona para atualizar a view
                        window.location.href = data.redirect || '{{ route('admin.payment.index') }}';
                    } else {
                        errorMessageDiv.textContent = data.message || 'Erro ao reabrir caixa.';
                        errorMessageDiv.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    errorMessageDiv.textContent = 'Erro de rede ou servidor ao reabrir caixa: ' + error.message;
                    errorMessageDiv.classList.remove('hidden');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitText.classList.remove('hidden');
                    submitSpinner.classList.add('hidden');
                });
        });


        // --- Lógica de Habilitação do Botão (CRÍTICO CORRIGIDO) ---
        function checkCashierStatus() {
            // 1. O número total de agendamentos para o dia (do PHP)
            const totalReservations = parseInt("{{ $totalReservasDia }}");

            // Lista de status FINISHED (Concluídos/Finalizados) que permitem fechar o caixa.
            const finalStatuses = ['pago completo', 'falta', 'cancelada', 'rejeitada', 'finalizado'];

            let completedReservations = 0;

            // Se o caixa JÁ estiver fechado, não faz nada com o botão de fechar.
            @if (isset($cashierStatus) && $cashierStatus === 'closed')
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

            if (!closeCashBtn || !cashStatusEl)
                return; // Garante que o elemento existe (pois pode ser o botão de 'Abrir Caixa')

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
            const modalFinalPriceEl = document.getElementById('modalFinalPrice');
            const modalAmountPaidEl = document.getElementById('modalAmountPaid');

            if (modalFinalPriceEl) {
                modalFinalPriceEl.addEventListener('input', calculateAmountDue);
            }
            if (modalAmountPaidEl) {
                modalAmountPaidEl.addEventListener('input', checkManualOverpayment);
            }

            // Destaque de Linha (mantido)
            const urlParams = new URLSearchParams(window.location.search);
            const reservaId = urlParams.get('reserva_id');
            if (reservaId) {
                // Usa uma seleção mais robusta
                const highlightedRow = document.querySelector(`.bg-indigo-50.dark\\:bg-indigo-900\\/20`);
                if (highlightedRow) {
                    highlightedRow.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }
        });
    </script>

</x-app-layout>
