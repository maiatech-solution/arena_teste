<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-3">
            <span class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg">📊</span>
            Central de Inteligência Financeira
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- 1. INDICADORES DE PERFORMANCE (KPIs) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                        <svg class="w-16 h-16 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path><path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path></svg>
                    </div>
                    <p class="text-xs text-gray-400 uppercase font-black tracking-widest">Receita Bruta (Mês)</p>
                    <p class="text-3xl font-black text-indigo-600 mt-1 italic">R$ {{ number_format($faturamentoMensal, 2, ',', '.') }}</p>
                    <div class="mt-2 flex items-center text-[10px] text-green-500 font-bold uppercase">
                        <span>● Movimentação Ativa</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                    <p class="text-xs text-gray-400 uppercase font-black tracking-widest">Ocupação (Mês)</p>
                    <p class="text-3xl font-black text-gray-800 dark:text-white mt-1">{{ $totalReservasMes }} <span class="text-sm font-normal text-gray-400 uppercase">Jogos</span></p>
                    <div class="mt-2 flex items-center text-[10px] text-indigo-500 font-bold uppercase text-sans">
                        <span>Agendamentos Confirmados</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-3xl shadow-sm border border-red-50 dark:border-red-900/20 relative overflow-hidden group">
                    <p class="text-xs text-gray-400 uppercase font-black tracking-widest">Prejuízo por Faltas</p>
                    <p class="text-3xl font-black text-red-500 mt-1">{{ $canceladasMes }} <span class="text-sm font-normal text-gray-400 uppercase">Faltas</span></p>
                    <div class="mt-2 flex items-center text-[10px] text-red-400 font-bold uppercase italic">
                        <span>🚨 Impacto na Liquidez</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <h3 class="text-sm font-black text-gray-400 uppercase tracking-[0.2em]">Módulos Analíticos</h3>
                <div class="flex-1 h-px bg-gray-200 dark:bg-gray-700"></div>
            </div>

            {{-- 2. GRID DE MENUS REFINADO --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <a href="{{ route('admin.financeiro.relatorio_faturamento') }}" class="group flex items-center p-2 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-transparent hover:border-indigo-500 hover:shadow-xl transition-all duration-300">
                    <div class="p-6 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 rounded-2xl group-hover:scale-95 transition-transform duration-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div class="ml-5">
                        <h4 class="text-lg font-black text-gray-800 dark:text-white group-hover:text-indigo-600 transition-colors uppercase tracking-tight">Faturamento</h4>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">Entradas, Sinais e Métodos</p>
                    </div>
                </a>

                <a href="{{ route('admin.financeiro.relatorio_caixa') }}" class="group flex items-center p-2 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-transparent hover:border-emerald-500 hover:shadow-xl transition-all duration-300">
                    <div class="p-6 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 rounded-2xl group-hover:scale-95 transition-transform duration-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                    </div>
                    <div class="ml-5">
                        <h4 class="text-lg font-black text-gray-800 dark:text-white group-hover:text-emerald-600 transition-colors uppercase tracking-tight">Conferência de Caixa</h4>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">Auditoria Diária e Assinaturas</p>
                    </div>
                </a>

                <a href="{{ route('admin.financeiro.relatorio_cancelamentos') }}" class="group flex items-center p-2 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-transparent hover:border-red-500 hover:shadow-xl transition-all duration-300">
                    <div class="p-6 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-2xl group-hover:scale-95 transition-transform duration-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div class="ml-5">
                        <h4 class="text-lg font-black text-gray-800 dark:text-white group-hover:text-red-600 transition-colors uppercase tracking-tight">Faltas & No-Show</h4>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">Mapeamento de Prejuízos</p>
                    </div>
                </a>

                <a href="{{ route('admin.financeiro.relatorio_ocupacao') }}" class="group flex items-center p-2 bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-transparent hover:border-blue-500 hover:shadow-xl transition-all duration-300">
                    <div class="p-6 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-2xl group-hover:scale-95 transition-transform duration-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="ml-5">
                        <h4 class="text-lg font-black text-gray-800 dark:text-white group-hover:text-blue-600 transition-colors uppercase tracking-tight">Mapa de Ocupação</h4>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-tighter">Agenda para Recepção e Quadra</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
