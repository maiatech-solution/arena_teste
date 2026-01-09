<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            💰 Gerenciamento de Caixa
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

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- 1. ESTRUTURA DE KPIS (RESPONSIVO COM DESCRIÇÕES) --}}
            <div class="space-y-4">
                <div class="grid grid-cols-1 lg:grid-cols-5 gap-3 lg:gap-4">

                    {{-- CARD 1: SALDO EM CAIXA (LÍQUIDO REAL) --}}
                    <div class="bg-green-600 dark:bg-green-700 overflow-hidden shadow-md rounded-lg p-4 lg:p-3 xl:p-4 flex flex-col justify-center border-b-4 border-green-900">
                        <div class="text-[10px] font-bold text-green-50 uppercase tracking-tighter truncate">
                            💰 Saldo {{ request('arena_id') ? 'da Arena' : 'Caixa' }}
                        </div>
                        <div class="mt-1 text-2xl font-extrabold text-white truncate"
                            title="Valor exato: {{ $totalRecebidoDiaLiquido }}">
                            R$ {{ number_format($totalRecebidoDiaLiquido, 2, ',', '.') }}
                        </div>
                        <div class="text-[9px] text-green-100 mt-1 italic leading-tight">
                            {{ request('arena_id') ? 'Dinheiro/Pix desta quadra.' : 'Total real em dinheiro/pix hoje.' }}
                        </div>
                    </div>

                    {{-- CARD 2: RECEITA JOGOS HOJE --}}
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-300 dark:border-indigo-800 overflow-hidden shadow-md rounded-lg p-4 lg:p-3 xl:p-4 flex flex-col justify-center text-left">
                        <div class="text-[10px] font-medium text-gray-700 dark:text-gray-300 uppercase tracking-tighter truncate">
                            🎾 RECEITA {{ request('arena_id') ? 'DA QUADRA' : 'DO DIA' }}
                        </div>
                        <div class="mt-1 text-2xl lg:text-lg xl:text-2xl font-extrabold text-indigo-700 dark:text-indigo-300 truncate">
                            R$ {{ number_format($totalAntecipadoReservasDia, 2, ',', '.') }}
                        </div>
                        <div class="text-[9px] text-gray-500 mt-1 leading-tight">
                            Faturamento dos jogos {{ request('arena_id') ? 'nesta arena.' : 'de hoje.' }}
                        </div>
                    </div>

                    {{-- CARD 3: PENDENTE A RECEBER --}}
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800 overflow-hidden shadow-md rounded-lg p-4 lg:p-3 xl:p-4 flex flex-col justify-center text-left">
                        <div class="text-[10px] font-medium text-gray-700 dark:text-gray-300 uppercase tracking-tighter truncate">
                            ⏳ Pendente
                        </div>
                        <div class="mt-1 text-2xl lg:text-lg xl:text-2xl font-extrabold text-yellow-700 dark:text-yellow-300 truncate">
                            R$ {{ number_format($totalPending, 2, ',', '.') }}
                        </div>
                        <div class="text-[9px] text-gray-500 mt-1 leading-tight">
                            Valor em aberto {{ request('arena_id') ? 'desta arena.' : 'total.' }}
                        </div>
                    </div>

                    {{-- CARD 4: RESERVAS ATIVAS --}}
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 overflow-hidden shadow-md rounded-lg p-4 lg:p-3 xl:p-4 flex flex-col justify-center text-left">
                        <div class="text-[10px] font-medium text-gray-700 dark:text-gray-300 uppercase tracking-tighter truncate">
                            📅 Ativas
                        </div>
                        <div class="mt-1 text-2xl lg:text-lg xl:text-2xl font-extrabold text-gray-900 dark:text-white truncate">
                            {{ $totalReservasDia }}
                        </div>
                        <div class="text-[9px] text-gray-500 mt-1 leading-tight">
                            Agendamentos {{ request('arena_id') ? 'nesta quadra.' : 'hoje.' }}
                        </div>
                    </div>

                    {{-- CARD 5: FALTAS (NO-SHOW) --}}
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-800 overflow-hidden shadow-md rounded-lg p-4 lg:p-3 xl:p-4 flex flex-col justify-center text-left">
                        <div class="text-[10px] font-medium text-gray-700 dark:text-gray-300 uppercase tracking-tighter truncate">
                            ❌ Faltas
                        </div>
                        <div class="mt-1 text-2xl lg:text-lg xl:text-2xl font-extrabold text-red-700 dark:text-red-300 truncate">
                            {{ $noShowCount }}
                        </div>
                        <div class="text-[9px] text-gray-500 mt-1 leading-tight">
                            Faltas {{ request('arena_id') ? 'nesta quadra.' : 'totais.' }}
                        </div>
                    </div>

                </div>

            </div>


            {{-- 🚨 2 FECHAMENTO DE CAIXA (Lógica Condicional) --}}
            @if (isset($cashierStatus) && $cashierStatus === 'closed')
            {{-- Bloco para reabrir o caixa --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 overflow-hidden shadow-lg sm:rounded-lg p-5 border border-red-400 dark:border-red-600">
                <div class="flex flex-col sm:flex-row items-center justify-between">
                    <div class="text-sm sm:text-base font-bold text-red-700 dark:text-red-300 mb-3 sm:mb-0 flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L12 12M6 6l12 12"></path>
                        </svg>
                        CAIXA FECHADO! Alterações Bloqueadas para o dia {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}.
                    </div>

                    <button id="openCashBtn" onclick="openCash('{{ $selectedDate }}')"
                        class="w-full sm:w-auto px-6 py-2 bg-red-600 text-white font-bold rounded-lg shadow-md hover:bg-red-700 transition duration-150 flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Abrir Caixa (Permitir Alterações)
                    </button>
                </div>
            </div>
            @else
            {{-- Bloco para fechar o caixa --}}
            <div class="bg-gray-50 dark:bg-gray-700/50 overflow-hidden shadow-lg sm:rounded-lg p-5 border border-indigo-400 dark:border-indigo-600">
                {{-- AVISO DE FILTRO ATIVO: Importante para o usuário não se confundir --}}
                @if(request('arena_id'))
                <div class="mb-3 p-2 bg-amber-100 text-amber-800 text-xs rounded-lg border border-amber-200 flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    Nota: Você está com um filtro de arena ativo. O fechamento de caixa considera sempre o <strong>total geral</strong>.
                </div>
                @endif

                <div class="flex flex-col sm:flex-row items-center justify-between">
                    <div class="text-sm sm:text-base font-medium text-gray-700 dark:text-gray-300 mb-3 sm:mb-0 flex items-center">
                        <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Fechamento Diário de Caixa:
                        {{-- O texto abaixo será trocado pelo JS --}}
                        <span id="cashStatus" class="ml-2 font-bold text-red-500">Verificando...</span>
                    </div>

                    {{-- DADOS OCULTOS PARA O DEBUG --}}
                    <input type="hidden" id="js_totalReservas" value="{{ (int)($totalReservasGeral ?? $totalReservasDia) }}">
                    <input type="hidden" id="js_isFiltered" value="{{ request('arena_id') ? '1' : '0' }}">
                    <input type="hidden" id="js_cashierDate" value="{{ $selectedDate }}">

                    <!-- ADICIONE AQUI -->
                    <input type="hidden" id="js_isActionDisabled" value="{{ $isActionDisabled ? '1' : '0' }}">


                    <button id="openCloseCashModalBtn" onclick="openCloseCashModal()" disabled
                        class="w-full sm:w-auto px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg shadow-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-indigo-700 transition duration-150">
                        Fechar Caixa Geral
                    </button>
                </div>
            </div>
            @endif




            {{-- 1.5. Linha dos Filtros --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- CARD/BLOCO 1: FILTRO DE DATA --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-lg p-5 flex flex-col justify-between border border-gray-200 dark:border-gray-700">
                    <form method="GET" action="{{ route('admin.payment.index') }}">
                        {{-- Preserva a pesquisa e a arena ao mudar a data --}}
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        @if(request('arena_id'))
                        <input type="hidden" name="arena_id" value="{{ request('arena_id') }}">
                        @endif

                        <div class="flex items-center justify-between">
                            <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Data do Caixa:
                            </label>

                            {{-- Link para limpar todos os filtros de uma vez --}}
                            @if (request()->has('reserva_id') || request()->has('arena_id') || request()->has('search'))
                            <a href="{{ route('admin.payment.index', ['date' => $selectedDate]) }}"
                                class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 font-medium"
                                title="Limpar todos os filtros e ver tudo">
                                Limpar Filtros
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
                        {{-- Preserva a data e a arena ao pesquisar --}}
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        @if(request('arena_id'))
                        <input type="hidden" name="arena_id" value="{{ request('arena_id') }}">
                        @endif

                        <div class="flex flex-col h-full justify-between">
                            <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Buscar Cliente (Nome ou WhatsApp):
                            </label>
                            <div class="flex items-end gap-3 mt-auto">
                                <input type="text" name="search" id="search" value="{{ request('search') }}"
                                    placeholder="Digite o nome..."
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">

                                <button type="submit"
                                    class="h-10 px-4 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700 transition duration-150 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>

                                @if (request()->has('search'))
                                <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'arena_id' => request('arena_id')]) }}"
                                    class="h-10 px-2 py-1 flex items-center justify-center text-gray-500 hover:text-red-500 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 transition duration-150">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>




            {{-- 🏟️ NOVO: CARD DE FATURAMENTO POR ARENA (COM FILTRO CLICÁVEL) --}}
            <div class="space-y-3 mb-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-[11px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 8.293A1 1 0 013 7.586V4z"></path>
                        </svg>
                        Faturamento Segmentado por Arena (Clique para filtrar)
                    </h3>

                    {{-- Botão para Resetar Filtro de Arena --}}
                    @if(request('arena_id'))
                    <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'search' => request('search')]) }}"
                        class="text-[10px] bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded-lg font-bold text-gray-600 dark:text-gray-300 hover:bg-red-500 hover:text-white transition">
                        ✕ LIMPAR FILTRO
                    </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @forelse($faturamentoPorArena as $arena)
                    @php
                    $isActive = request('arena_id') == $arena->id;
                    @endphp

                    <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'arena_id' => $arena->id, 'search' => request('search')]) }}"
                        class="p-4 rounded-2xl shadow-sm border transition flex justify-between items-center 
               {{ $isActive 
                  ? 'bg-indigo-50 dark:bg-indigo-900/30 border-indigo-500 ring-2 ring-indigo-500 ring-opacity-50' 
                  : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 hover:shadow-md hover:border-indigo-300' }} 
               border-l-4 {{ $isActive ? 'border-l-indigo-600' : 'border-l-indigo-400' }}">

                        <div class="truncate">
                            <span class="block text-[10px] font-black {{ $isActive ? 'text-indigo-600' : 'text-gray-400' }} uppercase tracking-tighter truncate">
                                {{ $arena->name }}
                            </span>
                            <span class="text-lg font-black {{ $isActive ? 'text-indigo-800 dark:text-indigo-300' : 'text-indigo-700 dark:text-indigo-400' }}">
                                R$ {{ number_format($arena->total, 2, ',', '.') }}
                            </span>
                        </div>

                        <div class="{{ $isActive ? 'text-indigo-600' : 'text-indigo-500' }}">
                            @if($isActive)
                            {{-- Ícone de Check se estiver ativo --}}
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            @else
                            {{-- Ícone de Gráfico se não estiver ativo --}}
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            @endif
                        </div>
                    </a>
                    @empty
                    <div class="col-span-full bg-gray-50 dark:bg-gray-800/40 border border-dashed border-gray-200 dark:border-gray-700 rounded-2xl p-4 text-center">
                        <p class="text-xs text-gray-400 italic font-medium">Nenhum faturamento registrado por arena para esta data.</p>
                    </div>
                    @endforelse
                </div>
            </div>



            {{-- 2. TABELA DE RESERVAS (PRIORIDADE DO CAIXA) --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4 flex items-center justify-between">
                        <div class="flex items-center">
                            @if (request()->has('reserva_id'))
                            <span class="text-indigo-500">Reserva Selecionada (ID: {{ request('reserva_id') }})</span>
                            @elseif(request()->has('arena_id'))
                            @php
                            // Busca o nome da arena filtrada para exibir no título
                            $arenaNome = $faturamentoPorArena->firstWhere('id', request('arena_id'))->name ?? 'Arena Selecionada';
                            @endphp
                            <span class="text-indigo-600 dark:text-indigo-400">Agendamentos: {{ $arenaNome }}</span>
                            @else
                            Agendamentos do Dia ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                            @endif
                        </div>

                        {{-- Botão Voltar: Limpa filtro de ID e de Arena --}}
                        @if (request()->has('reserva_id') || request()->has('arena_id'))
                        <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'search' => request('search')]) }}"
                            class="text-sm font-medium text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-300 flex items-center transition duration-150">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Ver Visão Geral do Dia
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
                                // 1. Cálculos Financeiros
                                $total = $reserva->final_price ?? $reserva->price;
                                $pago = $reserva->total_paid;
                                $restante = max(0, $total - $pago);
                                $currentStatus = $reserva->payment_status;

                                // 2. Destaque e Lógica de Atraso
                                $dataHoje = \Carbon\Carbon::today()->toDateString();
                                $dataReserva = \Carbon\Carbon::parse($reserva->date)->toDateString();
                                $eHoje = $dataReserva === $dataHoje;

                                $isOverdue = false;
                                if (in_array($reserva->status, ['confirmed', 'pending']) && $currentStatus !== 'paid') {
                                $onlyTime = \Carbon\Carbon::parse($reserva->end_time)->format('H:i:s');
                                try {
                                $reservaEndTime = \Carbon\Carbon::parse($dataReserva . ' ' . $onlyTime);
                                if ($reservaEndTime->isPast()) { $isOverdue = true; }
                                } catch (\Exception $e) { $isOverdue = false; }
                                }

                                // 3. Status Visual
                                $statusClass = '';
                                $statusLabel = '';
                                if ($reserva->status === 'no_show') {
                                $statusClass = 'bg-red-500 text-white font-bold';
                                $statusLabel = 'FALTA';
                                } elseif (in_array($reserva->status, ['canceled', 'rejected'])) {
                                $statusClass = 'bg-gray-400 text-white font-bold';
                                $statusLabel = strtoupper($reserva->status);
                                } elseif ($currentStatus === 'paid' || $reserva->status === 'completed') {
                                $statusClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                                $statusLabel = 'PAGO COMPLETO';
                                } elseif ($isOverdue) {
                                $statusClass = 'bg-red-700 text-white font-bold animate-pulse shadow-xl';
                                $statusLabel = 'ATRASADO';
                                } elseif ($currentStatus === 'partial') {
                                $statusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
                                $statusLabel = 'PAGO PARCIAL';
                                } else {
                                $statusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                $statusLabel = $pago > 0 ? 'SINAL DADO' : 'PENDENTE';
                                }

                                // 4. Destaque da Linha
                                if (isset($highlightReservaId) && $reserva->id == $highlightReservaId) {
                                $rowHighlight = 'bg-indigo-100 dark:bg-indigo-900/40 border-l-4 border-indigo-600 shadow-inner';
                                } elseif ($eHoje) {
                                $rowHighlight = 'bg-blue-50/70 dark:bg-blue-900/10 border-l-4 border-blue-500';
                                } else {
                                $rowHighlight = 'hover:bg-gray-50 dark:hover:bg-gray-700';
                                }

                                $canPay = $restante > 0 && !in_array($reserva->status, ['canceled', 'rejected']);
                                $canBeNoShow = !in_array($reserva->status, ['no_show', 'canceled', 'rejected']);
                                @endphp

                                <tr class="{{ $rowHighlight }} transition hover:bg-opacity-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-700 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $reserva->client_name }} (ID: {{ $reserva->id }})
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 mt-1">
                                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 font-black uppercase border border-indigo-200 dark:border-indigo-800">
                                                🏟️ {{ $reserva->arena->name ?? 'N/A' }}
                                            </span>
                                            <div class="text-[10px] text-gray-400 italic">{{ $reserva->client_contact }}</div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap"
                                        data-status="{{ $reserva->status }}">
                                        <span class="badge">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>


                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        <span class="font-semibold {{ $reserva->is_recurrent ? 'text-fuchsia-600' : 'text-blue-600' }}">
                                            {{ $reserva->is_recurrent ? 'Recorrente' : 'Pontual' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-700 dark:text-gray-300">
                                        {{ number_format($total, 2, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                        {{ number_format($pago, 2, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold {{ $restante > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                        {{ number_format($restante, 2, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        @if ($canPay)
                                        <button type="button"
                                            onclick="openPaymentModal({{ $reserva->id }}, {{ (float)$total }}, {{ (float)$restante }}, {{ (float)$pago }}, '{{ addslashes($reserva->client_name) }}', {{ $reserva->is_recurrent ? 'true' : 'false' }})"
                                            class="text-white bg-green-600 hover:bg-green-700 rounded px-3 py-1 text-xs mr-2 transition {{ $isActionDisabled ? 'opacity-50 cursor-not-allowed' : 'shadow-sm' }}"
                                            {{ $isActionDisabled ? 'disabled' : '' }}>
                                            $ Baixar
                                        </button>
                                        @endif

                                        @if ($canBeNoShow)
                                        <button type="button"
                                            onclick="openNoShowModal({{ $reserva->id }}, '{{ addslashes($reserva->client_name) }}', {{ (float)$pago }})"
                                            class="text-white bg-red-600 hover:bg-red-700 rounded px-3 py-1 text-xs transition {{ $isActionDisabled ? 'opacity-50 cursor-not-allowed' : 'shadow-sm' }}"
                                            {{ $isActionDisabled ? 'disabled' : '' }}>
                                            X Falta
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 italic">
                                        Nenhum agendamento encontrado para este filtro.
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
                    <h3 class="text-lg font-semibold mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Movimentação {{ request('arena_id') ? 'da Arena' : 'Detalhada de Caixa' }}
                        </div>
                        <span class="text-xs font-mono text-gray-400">{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</span>
                    </h3>

                    {{-- DASHBOARD DE ENTRADAS VS SAÍDAS (Filtrado) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="p-4 bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800 rounded-xl flex justify-between items-center">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-green-600 dark:text-green-400 tracking-widest">Entradas {{ request('arena_id') ? '(Arena)' : '' }}</span>
                                <span class="text-2xl font-black text-green-700 dark:text-green-300">
                                    R$ {{ number_format($financialTransactions->where('amount', '>', 0)->sum('amount'), 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="bg-green-100 dark:bg-green-800/30 p-2 rounded-lg text-green-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="p-4 bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-xl flex justify-between items-center">
                            <div>
                                <span class="block text-[10px] uppercase font-bold text-red-600 dark:text-red-400 tracking-widest">Saídas / Estornos</span>
                                <span class="text-2xl font-black text-red-700 dark:text-red-300">
                                    R$ {{ number_format(abs($financialTransactions->where('amount', '<', 0)->sum('amount')), 2, ',', '.') }}
                                </span>
                            </div>
                            <div class="bg-red-100 dark:bg-red-800/30 p-2 rounded-lg text-red-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    @php
                    $groupedTransactions = $financialTransactions->groupBy('reserva_id');
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase w-1/12">Hora</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase w-1/12">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase w-2/12">Pagador/Gestor</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase w-2/12">Tipo | Forma</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase w-4/12">Descrição</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-300 uppercase w-1/12">Valor (R$)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($groupedTransactions as $reservaId => $transactions)
                                @if ($reservaId)
                                @php
                                $transactionExample = $transactions->first();
                                $reserva = $transactionExample->reserva;
                                $clientName = $reserva->client_name ?? ($transactionExample->payer->name ?? 'N/D');
                                $arenaTag = $reserva->arena->name ?? '';

                                // 🎯 LÓGICA DE RECUPERAÇÃO DE HORÁRIO (REGEX)
                                // Tenta encontrar o horário [HH:mm] salvo na descrição via Controller
                                preg_match('/\[(\d{2}:\d{2})\]/', $transactionExample->description, $matches);
                                $horarioBackup = $matches[1] ?? null;

                                // Tenta encontrar a Arena | Nome | salvo na descrição via Controller
                                if(!$arenaTag) {
                                preg_match('/\|\s([^|\[]+)\s\[/', $transactionExample->description, $arenaMatches);
                                $arenaTag = trim($arenaMatches[1] ?? '');
                                }
                                @endphp

                                {{-- LINHA DE CABEÇALHO DA RESERVA (CINZA) --}}
                                <tr class="bg-gray-100 dark:bg-gray-700/60 border-t-2 border-indigo-500">
                                    <td colspan="5" class="px-4 py-2.5 text-sm font-bold text-gray-800 dark:text-gray-100">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-600 text-white mr-3">RESERVA</span>

                                        ID: {{ $reservaId }} - {{ $clientName }}

                                        {{-- EXIBIÇÃO DO HORÁRIO AGENDADO --}}
                                        @if($reserva)
                                        <span class="ml-3 bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-[11px] font-black border border-indigo-200">
                                            ⏰ {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} às {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        </span>
                                        @elseif($horarioBackup)
                                        <span class="ml-3 bg-orange-100 text-orange-700 px-2 py-0.5 rounded text-[11px] font-black border border-orange-200" title="Horário extraído do histórico">
                                            ⏰ {{ $horarioBackup }} (Original)
                                        </span>
                                        @else
                                        <span class="ml-3 text-[10px] text-gray-400 italic font-normal">(Horário N/D)</span>
                                        @endif

                                        @if($arenaTag)
                                        <span class="ml-2 text-[9px] text-indigo-400 uppercase font-black tracking-widest">[{{ $arenaTag }}]</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-sm font-black text-gray-900 dark:text-white bg-indigo-50/50 dark:bg-indigo-900/20 border-l border-indigo-100 dark:border-indigo-800/50">
                                        R$ {{ number_format($transactions->sum('amount'), 2, ',', '.') }}
                                    </td>
                                </tr>
                                @endif

                                {{-- TRANSAÇÕES INDIVIDUAIS (BRANCAS/VERMELHAS) --}}
                                @foreach ($transactions as $transaction)
                                @php
                                $amount = (float) $transaction->amount;
                                $isRefund = $transaction->type === 'refund' || $amount < 0;
                                    $rowClass=$isRefund ? 'bg-red-50/50 dark:bg-red-900/10 border-l-4 border-red-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700 border-l-4 border-transparent' ;
                                    $amountClass=$amount>= 0 ? 'text-green-600 font-bold' : 'text-red-600 font-black';
                                    @endphp
                                    <tr class="{{ $rowClass }} transition duration-150">
                                        <td class="px-4 py-3 text-sm text-gray-500 font-mono italic">
                                            {{ \Carbon\Carbon::parse($transaction->paid_at)->format('H:i:s') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium {{ $isRefund ? 'text-red-700' : 'text-indigo-600' }}">
                                            #{{ $transaction->reserva_id ?? '--' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="font-semibold text-gray-700 dark:text-gray-300">{{ $transaction->payer->name ?? 'Caixa Geral' }}</div>
                                            <div class="text-[10px] text-gray-400 italic">Gestor: {{ $transaction->manager->name ?? 'Sistema' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="text-[10px] font-extrabold uppercase text-gray-500">{{ $transaction->type }}</div>
                                            <div class="text-[9px] px-1 bg-gray-100 dark:bg-gray-700 w-fit rounded font-bold text-gray-600 dark:text-gray-400">({{ $transaction->payment_method }})</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm leading-tight text-gray-600 dark:text-gray-400">
                                            <div class="flex items-center">
                                                @if ($isRefund)
                                                <svg class="w-3 h-3 mr-1 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                                </svg>
                                                @endif
                                                {{ $transaction->description }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm font-mono {{ $amountClass }}">
                                            {{ $amount < 0 ? '-' : '' }} R$ {{ number_format(abs($amount), 2, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach

                                    @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-12 text-center text-gray-500 italic">
                                            Nenhuma transação financeira registrada para este dia.
                                        </td>
                                    </tr>
                                    @endforelse

                                    {{-- LINHA DE TOTALIZADOR FINAL --}}
                                    <tr class="bg-gray-100 dark:bg-gray-700 font-bold border-t-2 border-gray-300">
                                        <td colspan="5" class="px-4 py-4 text-right uppercase text-xs tracking-widest text-gray-600 dark:text-gray-300">Total Líquido do Período:</td>
                                        <td id="valor-liquido-total-real" class="px-4 py-4 text-right text-lg {{ $totalRecebidoDiaLiquido >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                            R$ {{ number_format($totalRecebidoDiaLiquido, 2, ',', '.') }}
                                        </td>
                                    </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>



            {{-- 4.5. HISTÓRICO DE FECHAMENTOS (AUDITORIA DE DIVERGÊNCIAS) --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg mt-6">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-fuchsia-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Histórico de Fechamentos Gerais
                        </div>
                        @if(request('arena_id'))
                        <span class="text-[10px] bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-400 px-2 py-1 rounded-md font-bold uppercase">
                            Visão Consolidada (Todas as Quadras)
                        </span>
                        @endif
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Responsável</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Esperado (Sistema)</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Informado (Físico)</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Diferença</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($cashierHistory ?? [] as $caixa)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ \Carbon\Carbon::parse($caixa->date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $caixa->user->name ?? 'Sistema' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-600 dark:text-gray-400 font-mono">
                                        R$ {{ number_format($caixa->calculated_amount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-900 dark:text-white font-mono">
                                        R$ {{ number_format($caixa->actual_amount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold font-mono">
                                        @if ($caixa->difference > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800"
                                            title="Sobrou dinheiro físico">
                                            <span class="w-2 h-2 mr-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                            + R$ {{ number_format($caixa->difference, 2, ',', '.') }} ⚠️
                                        </span>
                                        @elseif($caixa->difference < 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300 border border-red-200 dark:border-red-800"
                                            title="Faltou dinheiro físico">
                                            <span class="w-2 h-2 mr-1.5 rounded-full bg-red-500 shadow-[0_0_5px_rgba(239,68,68,0.5)]"></span>
                                            - R$ {{ number_format(abs($caixa->difference), 2, ',', '.') }} 🚨
                                            </span>
                                            @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800">
                                                <span class="w-2 h-2 mr-1.5 rounded-full bg-green-500"></span>
                                                R$ 0,00 ✅
                                            </span>
                                            @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400 italic">
                                        Nenhum histórico de fechamento disponível.
                                    </td>
                                </tr>
                                @endforelse
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

        <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form id="paymentForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white" id="modal-title">
                                Baixar Pagamento
                            </h3>

                            {{-- INFO DA RESERVA --}}
                            <div class="mt-2 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-100 dark:border-gray-600 space-y-1">
                                <p class="text-sm text-gray-600 dark:text-gray-300 italic">
                                    Cliente: <span id="modalClientName" class="font-bold text-gray-900 dark:text-white uppercase"></span>
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-300 italic">
                                    Sinal já Pago: <span id="modalSignalAmount" class="font-bold text-green-600"></span>
                                </p>
                            </div>

                            <input type="hidden" id="modalReservaId" name="reserva_id">
                            <input type="hidden" id="modalSignalAmountRaw" name="signal_amount_raw">

                            <div class="mt-4 space-y-4">
                                {{-- VALOR TOTAL --}}
                                <div>
                                    <label for="modalFinalPrice" class="block text-xs font-black text-gray-500 uppercase tracking-widest">
                                        Valor Total da Reserva (Final)
                                    </label>
                                    <div class="relative mt-1">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm font-bold">R$</span>
                                        </div>
                                        <input type="number" step="0.01" id="modalFinalPrice" name="final_price"
                                            oninput="calculateAmountDue()" required
                                            class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white font-black text-xl">
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-1">* Altere aqui para aplicar descontos ou acréscimos.</p>
                                </div>

                                {{-- VALOR RECEBIDO AGORA --}}
                                <div class="p-3 bg-green-50 dark:bg-green-900/10 rounded-xl border border-green-100 dark:border-green-800">
                                    <label for="modalAmountPaid" class="block text-xs font-black text-green-700 dark:text-green-400 uppercase tracking-widest">
                                        Valor Recebido AGORA (Saldo)
                                    </label>
                                    <div class="relative mt-1">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-green-600 font-bold">R$</span>
                                        </div>
                                        <input type="number" step="0.01" id="modalAmountPaid" name="amount_paid"
                                            oninput="checkManualOverpayment()" required
                                            class="pl-10 block w-full rounded-md border-green-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-green-400 font-black text-2xl">
                                    </div>
                                    <p id="trocoMessage" class="hidden mt-2 text-sm font-bold text-amber-600 animate-bounce">
                                        ⚠️ Troco: R$ 0,00
                                    </p>
                                </div>

                                {{-- FORMA DE PAGAMENTO --}}
                                <div>
                                    <label for="modalPaymentMethod" class="block text-xs font-black text-gray-500 uppercase tracking-widest">
                                        Forma de Pagamento
                                    </label>
                                    <select id="modalPaymentMethod" name="payment_method" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white font-bold">
                                        <option value="">Selecione...</option>
                                        <option value="pix">PIX</option>
                                        <option value="money">Dinheiro</option>
                                        <option value="credit_card">Cartão de Crédito</option>
                                        <option value="debit_card">Cartão de Débito</option>
                                        <option value="transfer">Transferência</option>
                                        <option value="other">Outro / Cortesia</option>
                                    </select>
                                </div>

                                {{-- RECORRÊNCIA --}}
                                <div id="recurrentOption" class="hidden pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <label class="relative flex items-start cursor-pointer group">
                                        <input type="checkbox" id="apply_to_series" name="apply_to_series" value="1"
                                            class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 transition cursor-pointer">
                                        <span class="ml-3">
                                            <span class="block text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-tight">Atualizar série futura</span>
                                            <span class="block text-xs text-gray-500 italic">Aplicar este novo preço (R$ <span id="currentNewPrice" class="font-bold"></span>) em todas as reservas desta recorrência.</span>
                                        </span>
                                    </label>
                                </div>
                            </div>

                            <div id="payment-error-message" class="hidden mt-4 p-3 bg-red-100 text-red-700 text-xs font-bold rounded-lg border border-red-200"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" id="submitPaymentBtn"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-2.5 bg-green-600 text-base font-black text-white hover:bg-green-700 focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:w-auto sm:text-sm transition duration-150">
                        <span id="submitPaymentText">CONCLUIR PAGAMENTO</span>
                        <svg id="submitPaymentSpinner" class="animate-spin ml-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                    <button type="button" onclick="closePaymentModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-2.5 bg-white dark:bg-gray-800 text-base font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm transition duration-150">
                        CANCELAR
                    </button>
                </div>
            </form>
        </div>
    </div>



    {{-- MODAL 2: REGISTRAR FALTA (NO-SHOW) COM ESTORNO FLEXÍVEL --}}
    <div id="noShowModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form id="noShowForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L12 12M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white uppercase tracking-tight">
                                Registrar Falta (No-Show)
                            </h3>

                            <div class="mt-2 p-3 bg-red-50 dark:bg-red-900/10 rounded-lg border border-red-100 dark:border-red-900/50">
                                <p class="text-sm text-red-800 dark:text-red-300 font-medium">
                                    Cliente: <span id="noShowClientName" class="font-black"></span>
                                </p>
                            </div>

                            <input type="hidden" id="noShowReservaId" name="reserva_id">
                            <input type="hidden" id="noShowPaidAmount" name="paid_amount">

                            {{-- CONTROLE DE ESTORNO EVOLUÍDO --}}
                            <div id="refundControls" class="mt-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl hidden border border-gray-200 dark:border-gray-600 space-y-4">
                                <div>
                                    <label for="should_refund" class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                        Dinheiro Recebido: <span id="noShowAmountDisplay" class="text-indigo-600"></span>
                                    </label>
                                    <select id="should_refund" name="should_refund" onchange="toggleCustomRefundInput()"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:text-white font-bold">
                                        <option value="false">Reter valor total (Multa de No-Show)</option>
                                        <option value="true">Estornar / Devolver Valor ao Cliente</option>
                                    </select>
                                </div>

                                {{-- CAMPO DINÂMICO DE VALOR A DEVOLVER --}}
                                <div id="customRefundDiv" class="hidden">
                                    <label for="custom_refund_amount" class="block text-[10px] font-black text-red-600 dark:text-red-400 uppercase tracking-widest mb-1">
                                        Quanto deseja devolver agora?
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-bold">R$</span>
                                        </div>
                                        <input type="number" step="0.01" id="custom_refund_amount" name="custom_refund_amount"
                                            class="pl-10 block w-full rounded-md border-red-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-800 dark:text-red-400 font-black text-xl">
                                    </div>
                                    <p class="mt-1 text-[9px] text-gray-400 italic font-medium">* Este valor será subtraído do caixa de hoje.</p>
                                </div>
                            </div>

                            {{-- MOTIVO --}}
                            <div class="mt-4">
                                <label for="no_show_reason" class="block text-xs font-black text-gray-500 uppercase tracking-widest">
                                    Motivo / Observação (Obrigatório)
                                </label>
                                <textarea id="no_show_reason" name="no_show_reason" rows="2" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:text-white text-sm"
                                    placeholder="Ex: Não compareceu e não atendeu as ligações."></textarea>
                            </div>

                            {{-- BLOQUEIO --}}
                            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg border-2 border-dashed border-red-200 dark:border-red-800">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="block_user" name="block_user"
                                        class="h-5 w-5 rounded border-gray-300 text-red-600 focus:ring-red-500 transition cursor-pointer">
                                    <span class="ml-2 text-xs font-black text-red-700 dark:text-red-400 uppercase tracking-tighter">
                                        Bloquear cliente permanentemente
                                    </span>
                                </label>
                            </div>

                            <div id="noshow-error-message" class="hidden mt-3 p-3 bg-red-100 text-red-700 text-xs font-bold rounded-lg border border-red-200"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                    <button type="submit" id="submitNoShowBtn"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-2.5 bg-red-600 text-base font-black text-white hover:bg-red-700 focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm transition duration-150">
                        <span id="submitNoShowText">CONFIRMAR FALTA</span>
                        <svg id="submitNoShowSpinner" class="animate-spin ml-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                    <button type="button" onclick="closeNoShowModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-2.5 bg-white dark:bg-gray-800 text-base font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm transition duration-150">
                        VOLTAR
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- MODAL 3: FECHAR CAIXA (CLOSE CASH) --}}
    <div id="closeCashModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form id="closeCashForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white uppercase tracking-tight" id="modal-title">
                                Fechamento de Caixa: <span id="closeCashDateDisplay" class="text-indigo-600"></span>
                            </h3>

                            {{-- ALERTA DE FILTRO ATIVO --}}
                            @if(request('arena_id'))
                            <div class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <p class="text-[10px] font-black text-amber-700 dark:text-amber-400 uppercase leading-tight">
                                    ⚠️ Atenção: Você está visualizando uma Arena específica, mas o fechamento é sempre do **TOTAL GERAL**.
                                </p>
                            </div>
                            @endif

                            <div class="mt-4 space-y-4">
                                {{-- VALOR CALCULADO --}}
                                <div>
                                    <label class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                        Total Esperado (Sistema)
                                    </label>
                                    <div id="calculatedLiquidAmount" class="mt-1 block w-full bg-gray-100 dark:bg-gray-900 p-3 rounded-md font-black text-2xl text-indigo-600 border border-gray-200 dark:border-gray-700">
                                        R$ 0,00
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-1">Soma de todas as entradas menos estornos do dia.</p>
                                </div>

                                {{-- VALOR FÍSICO --}}
                                <div class="p-4 bg-indigo-50 dark:bg-indigo-900/10 rounded-xl border-2 border-indigo-100 dark:border-indigo-800">
                                    <label for="actualCashAmount" class="block text-xs font-black text-indigo-700 dark:text-indigo-400 uppercase tracking-widest mb-1">
                                        Valor Total em Caixa Físico
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-indigo-500 font-bold">R$</span>
                                        </div>
                                        <input type="number" step="0.01" id="actualCashAmount" name="actual_cash_amount" required
                                            class="pl-10 block w-full rounded-md border-indigo-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white font-black text-2xl"
                                            placeholder="0,00">
                                    </div>
                                </div>

                                {{-- MENSAGEM DE DIFERENÇA (DINÂMICA VIA JS) --}}
                                <div id="differenceMessage" class="hidden mt-3 p-3 text-sm font-bold rounded-lg text-center border">
                                </div>

                                <input type="hidden" id="closeCashDate" name="date">
                                <div id="closecash-error-message" class="hidden mt-3 p-3 bg-red-100 text-red-700 text-xs font-bold rounded-lg border border-red-200"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" id="submitCloseCashBtn"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-2.5 bg-indigo-600 text-base font-black text-white hover:bg-indigo-700 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm transition duration-150 uppercase tracking-wider">
                        <span id="submitCloseCashText">Confirmar Fechamento Geral</span>
                        <svg id="submitCloseCashSpinner" class="animate-spin ml-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                    <button type="button" onclick="closeCloseCashModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-2.5 bg-white dark:bg-gray-800 text-base font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm transition duration-150">
                        VOLTAR
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- MODAL 4: ABRIR CAIXA (OPEN CASH) - Exige Justificativa --}}
    <div id="openCashModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4"
        aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg sm:w-full">
            <form id="openCashForm">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.3 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-gray-900 dark:text-white uppercase tracking-tight" id="modal-title">
                                Reabrir Caixa Diário
                            </h3>

                            <div class="mt-2 p-3 bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/50 rounded-lg">
                                <p class="text-sm text-red-700 dark:text-red-400">
                                    O caixa do dia <span id="openCashDateDisplay" class="font-black underline"></span> está <strong>FECHADO</strong>.
                                </p>
                                <p class="mt-1 text-xs text-red-600 dark:text-red-500 italic font-medium">
                                    A reabertura permite novas baixas e alterações, mas gera um log de auditoria no sistema.
                                </p>
                            </div>

                            <div class="mt-4">
                                <label for="reopen_reason" class="block text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1">
                                    Justificativa da Reabertura <span class="text-red-500">*</span>
                                </label>
                                <textarea id="reopen_reason" name="reopen_reason" rows="3" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:text-white font-medium text-sm"
                                    placeholder="Descreva detalhadamente o motivo (Ex: Erro no lançamento da Reserva #123)"></textarea>
                            </div>

                            <input type="hidden" id="reopenCashDate" name="date">
                            <div id="openCash-error-message" class="hidden mt-3 p-3 bg-red-100 text-red-700 text-xs font-bold rounded-lg border border-red-200"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit" id="submitOpenCashBtn"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-6 py-2.5 bg-red-600 text-base font-black text-white hover:bg-red-700 focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm transition duration-150 uppercase tracking-widest">
                        <span id="submitOpenCashText">Confirmar Reabertura</span>
                        <svg id="submitOpenCashSpinner" class="animate-spin ml-2 h-4 w-4 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                    <button type="button" onclick="closeOpenCashModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-6 py-2.5 bg-white dark:bg-gray-800 text-base font-bold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 sm:mt-0 sm:w-auto sm:text-sm transition duration-150">
                        CANCELAR
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
            if (currentNewPriceEl) {
                const newPriceFloat = parseFloat(newPrice) || 0;
                currentNewPriceEl.innerText = newPriceFloat.toFixed(2).replace('.', ',');
            }
        }

        // --- Lógica de Controle de Estorno (NOVO) ---
        function toggleCustomRefundInput() {
            const shouldRefund = document.getElementById('should_refund').value === 'true';
            const customDiv = document.getElementById('customRefundDiv');
            const paidAmount = parseFloat(document.getElementById('noShowPaidAmount').value) || 0;
            const inputRefund = document.getElementById('custom_refund_amount');

            if (shouldRefund) {
                customDiv.classList.remove('hidden');
                // Sugere o valor total pago por padrão, mas permite editar
                inputRefund.value = paidAmount.toFixed(2);
            } else {
                customDiv.classList.add('hidden');
                inputRefund.value = 0;
            }
        }

        // --- Lógica de Cálculo de Pagamento ---
        function calculateAmountDue() {
            const finalPriceEl = document.getElementById('modalFinalPrice');
            const signalRawEl = document.getElementById('modalSignalAmountRaw');
            const amountPaidEl = document.getElementById('modalAmountPaid');
            const trocoMessageEl = document.getElementById('trocoMessage');

            if (!finalPriceEl || !amountPaidEl) return;

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
            const finalPriceEl = document.getElementById('modalFinalPrice');
            const signalRawEl = document.getElementById('modalSignalAmountRaw');
            const amountPaidEl = document.getElementById('modalAmountPaid');
            const trocoMessageEl = document.getElementById('trocoMessage');

            if (!finalPriceEl || !amountPaidEl) return;

            const finalPrice = parseFloat(finalPriceEl.value) || 0;
            const signalAmount = parseFloat(signalRawEl.value) || 0;
            const amountPaidNow = parseFloat(amountPaidEl.value) || 0;

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

        // --- Lógica do Pagamento ---
        function openPaymentModal(id, totalPrice, remaining, signalAmount, clientName, isRecurrent = false) {
            const isClosed = document.getElementById('js_isActionDisabled').value === '1';

            if (isClosed) {
                alert('Ações bloqueadas. O caixa para esta data está FECHADO.');
                return;
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
            const applyToSeriesCheckbox = document.getElementById('apply_to_series');

            if (isRecurrent) {
                recurrentOptionEl.classList.remove('hidden');
                applyToSeriesCheckbox.checked = true;
            } else {
                recurrentOptionEl.classList.add('hidden');
                applyToSeriesCheckbox.checked = false;
            }

            calculateAmountDue();
            const modal = document.getElementById('paymentModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.replace('flex', 'hidden');
            checkCashierStatus();
        }

        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const reservaId = document.getElementById('modalReservaId').value;
            const finalPrice = parseFloat(document.getElementById('modalFinalPrice').value).toFixed(2);
            const amountPaid = parseFloat(document.getElementById('modalAmountPaid').value).toFixed(2);
            const paymentMethod = document.getElementById('modalPaymentMethod').value;
            const applyToSeries = document.getElementById('apply_to_series').checked;
            const csrfToken = document.querySelector('input[name="_token"]').value;

            const submitBtn = document.getElementById('submitPaymentBtn');
            const errorMessageDiv = document.getElementById('payment-error-message');

            if (paymentMethod === '') {
                errorMessageDiv.textContent = 'Por favor, selecione a Forma de Pagamento.';
                errorMessageDiv.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            document.getElementById('submitPaymentText').classList.add('hidden');
            document.getElementById('submitPaymentSpinner').classList.remove('hidden');

            fetch(`/admin/pagamentos/${reservaId}/finalizar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        reserva_id: reservaId,
                        final_price: finalPrice,
                        amount_paid: amountPaid,
                        payment_method: paymentMethod,
                        apply_to_series: applyToSeries
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        throw new Error(data.message);
                    }
                })
                .catch(error => {
                    errorMessageDiv.textContent = error.message;
                    errorMessageDiv.classList.remove('hidden');
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    document.getElementById('submitPaymentText').classList.remove('hidden');
                    document.getElementById('submitPaymentSpinner').classList.add('hidden');
                });
        });

        // --- Lógica de No-Show (REVISADA) ---
        function openNoShowModal(id, clientName, paidAmount) {
            const isClosed = document.getElementById('js_isActionDisabled').value === '1';
            if (isClosed) {
                alert("Ações bloqueadas. O caixa para esta data está FECHADO.");
                return;
            }

            document.getElementById('noShowReservaId').value = id;
            document.getElementById('noShowClientName').innerText = clientName;
            document.getElementById('noShowPaidAmount').value = paidAmount;

            const refundControls = document.getElementById('refundControls');
            const displayAmount = document.getElementById('noShowAmountDisplay');
            const customDiv = document.getElementById('customRefundDiv');

            if (paidAmount > 0) {
                refundControls.classList.remove('hidden');
                displayAmount.innerText = paidAmount.toLocaleString("pt-BR", {
                    style: "currency",
                    currency: "BRL"
                });
                // Resetar para reter por padrão ao abrir
                document.getElementById('should_refund').value = 'false';
                customDiv.classList.add('hidden');
            } else {
                refundControls.classList.add('hidden');
            }

            document.getElementById('noShowModal').classList.remove('hidden');
            document.getElementById('noShowModal').classList.add('flex');
        }

        function closeNoShowModal() {
            document.getElementById('noShowModal').classList.replace('flex', 'hidden');
            checkCashierStatus();
        }

        document.getElementById('noShowForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const reservaId = document.getElementById('noShowReservaId').value;
            const notes = document.getElementById('no_show_reason').value;
            const blockUser = document.getElementById('block_user').checked;
            const shouldRefund = document.getElementById('should_refund').value === 'true';
            const refundAmount = document.getElementById('custom_refund_amount').value;
            const csrfToken = document.querySelector('input[name="_token"]').value;

            const submitBtn = document.getElementById('submitNoShowBtn');
            submitBtn.disabled = true;

            // 🎯 URL ATUALIZADA PARA O PADRÃO QUE DEFINIMOS NAS ROTAS
            fetch(`/admin/reservas/${reservaId}/no-show`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        notes,
                        block_user: blockUser,
                        should_refund: shouldRefund,
                        refund_amount: refundAmount
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                        submitBtn.disabled = false;
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Erro ao processar requisição.");
                    submitBtn.disabled = false;
                });
        });

        // --- Fechamento de Caixa (MODAL 3) ---
        function calculateDifference() {
            const calculatedAmountEl = document.getElementById('valor-liquido-total-real');
            const diffMessageEl = document.getElementById('differenceMessage');
            const actualAmountInput = document.getElementById('actualCashAmount');

            if (!calculatedAmountEl || !diffMessageEl || !actualAmountInput) return;

            diffMessageEl.classList.remove('hidden');

            let calculatedText = calculatedAmountEl.innerText
                .replace('R$', '')
                .replace(/\./g, '')
                .replace(',', '.')
                .trim();

            const calculatedAmount = parseFloat(calculatedText) || 0;
            const actualAmount = parseFloat(actualAmountInput.value) || 0;
            const difference = (actualAmount - calculatedAmount).toFixed(2);

            diffMessageEl.classList.remove('bg-red-100', 'text-red-700', 'bg-yellow-100', 'text-yellow-700', 'bg-green-100', 'text-green-700');

            if (Math.abs(difference) < 0.01) {
                diffMessageEl.innerHTML = '✅ <strong>Caixa exato!</strong> Valores conferem.';
                diffMessageEl.classList.add('bg-green-100', 'text-green-700');
            } else if (difference > 0) {
                diffMessageEl.innerHTML = `⚠️ <strong>Sobrou R$ ${Math.abs(difference).toFixed(2).replace('.', ',')}</strong>. Físico maior que sistema.`;
                diffMessageEl.classList.add('bg-yellow-100', 'text-yellow-700');
            } else {
                diffMessageEl.innerHTML = `🚨 <strong>Faltou R$ ${Math.abs(difference).toFixed(2).replace('.', ',')}</strong>. Físico menor que sistema.`;
                diffMessageEl.classList.add('bg-red-100', 'text-red-700');
            }
        }

        function openCloseCashModal() {
            const totalText = document.getElementById('valor-liquido-total-real').innerText;
            const cashierDate = document.getElementById('js_cashierDate').value;

            document.getElementById('closeCashDate').value = cashierDate;
            document.getElementById('closeCashDateDisplay').innerText =
                new Date(cashierDate + 'T00:00:00').toLocaleDateString('pt-BR');
            document.getElementById('calculatedLiquidAmount').innerText = totalText;

            const rawValue = totalText.replace(/[^\d,]/g, '').replace(',', '.');
            document.getElementById('actualCashAmount').value = parseFloat(rawValue).toFixed(2);

            calculateDifference();
            document.getElementById('closeCashModal').classList.replace('hidden', 'flex');
        }

        function closeCloseCashModal() {
            document.getElementById('closeCashModal').classList.replace('flex', 'hidden');
        }

        document.getElementById('closeCashForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitCloseCashBtn');
            const date = document.getElementById('closeCashDate').value;
            const actualAmount = document.getElementById('actualCashAmount').value;
            const csrfToken = document.querySelector('input[name="_token"]').value;

            submitBtn.disabled = true;
            const btnText = document.getElementById('submitCloseCashText');
            if (btnText) btnText.innerText = "PROCESSANDO...";

            fetch(`/admin/pagamentos/fechar-caixa`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        date: date,
                        actual_amount: actualAmount
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("Erro ao fechar caixa: " + data.message);
                        submitBtn.disabled = false;
                        if (btnText) btnText.innerText = "Confirmar Fechamento Geral";
                    }
                })
                .catch(err => {
                    alert("Erro de conexão com o servidor.");
                    submitBtn.disabled = false;
                });
        });

        // --- Abertura de Caixa (MODAL 4) ---
        function openCash(date) {
            const formattedDate = new Date(date + 'T00:00:00').toLocaleDateString('pt-BR');
            document.getElementById('reopenCashDate').value = date;
            document.getElementById('openCashDateDisplay').innerText = formattedDate;
            document.getElementById('openCashModal').classList.replace('hidden', 'flex');
        }

        function closeOpenCashModal() {
            document.getElementById('openCashModal').classList.replace('flex', 'hidden');
        }

        document.getElementById('openCashForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const date = document.getElementById('reopenCashDate').value;
            const reason = document.getElementById('reopen_reason').value;
            const csrfToken = document.querySelector('input[name="_token"]').value;

            fetch(`/admin/pagamentos/abrir-caixa`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        date,
                        reason
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                });
        });

        function checkCashierStatus() {
            const btn = document.getElementById('openCloseCashModalBtn');
            const statusEl = document.getElementById('cashStatus');
            const inputTotal = document.getElementById('js_totalReservas');
            const inputFilter = document.getElementById('js_isFiltered');
            const valorLiquidoEl = document.getElementById('valor-liquido-total-real');

            if (!btn || !statusEl) return;

            const totalReservations = inputTotal ? parseInt(inputTotal.value) : 0;
            const isFiltered = inputFilter && inputFilter.value === '1';

            let totalCashToday = 0;
            if (valorLiquidoEl) {
                let rawText = valorLiquidoEl.innerText.replace(/[^\d,]/g, '').replace(',', '.');
                totalCashToday = parseFloat(rawText) || 0;
            }

            if (isFiltered) {
                btn.disabled = true;
                statusEl.innerHTML = "💡 Limpe o filtro de arena para fechar.";
                statusEl.style.color = "#f59e0b";
                return;
            }

            if (totalReservations === 0) {
                if (totalCashToday > 0) {
                    btn.disabled = false;
                    statusEl.innerHTML = "✅ Pronto para Fechar (Entradas Antecipadas)";
                    statusEl.style.color = "#16a34a";
                } else {
                    btn.disabled = true;
                    statusEl.innerHTML = "⚪ Nenhum movimento hoje.";
                    statusEl.style.color = "#6b7280";
                }
                return;
            }

            const finalStatuses = ['pago completo', 'pago', 'finalizado', 'falta', 'cancelada', 'rejeitada', 'no_show', 'paid', 'complete'];
            let completedOnScreen = 0;
            const rows = document.querySelectorAll('table tbody tr');

            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 3) {
                    const statusCell = cells[2];
                    const textContent = statusCell.innerText.trim().toLowerCase();
                    const dataStatus = statusCell.getAttribute('data-status') ? statusCell.getAttribute('data-status').toLowerCase() : '';
                    if (finalStatuses.includes(textContent) || finalStatuses.includes(dataStatus)) {
                        completedOnScreen++;
                    }
                }
            });

            if (completedOnScreen < totalReservations) {
                btn.disabled = true;
                statusEl.innerHTML = `🚨 Pendentes: ${totalReservations - completedOnScreen} reservas.`;
                statusEl.style.color = "#ef4444";
            } else {
                btn.disabled = false;
                statusEl.innerHTML = "✅ Pronto para Fechamento!";
                statusEl.style.color = "#16a34a";
            }
        }

        // --- Inicialização ---
        document.addEventListener('DOMContentLoaded', () => {
            checkCashierStatus();
            const actualCashInput = document.getElementById('actualCashAmount');
            if (actualCashInput) actualCashInput.addEventListener('input', calculateDifference);
            const modalFinalPriceEl = document.getElementById('modalFinalPrice');
            if (modalFinalPriceEl) modalFinalPriceEl.addEventListener('input', calculateAmountDue);
            const modalAmountPaidEl = document.getElementById('modalAmountPaid');
            if (modalAmountPaidEl) modalAmountPaidEl.addEventListener('input', checkManualOverpayment);
        });
    </script>
</x-app-layout>