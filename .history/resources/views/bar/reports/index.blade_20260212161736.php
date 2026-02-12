<x-app-layout>
    {{-- Removemos o x-slot name="header" para não herdar o estilo branco da Arena --}}

    <div class="py-12 bg-gray-900 min-h-screen text-gray-100">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- 🍺 HEADER CUSTOMIZADO (ESTILO BAR) --}}
            <div class="flex items-center justify-between mb-10">
                <div class="flex items-center gap-4">
                    <div class="p-4 bg-orange-600/20 rounded-2xl border border-orange-600/30">
                        <span class="text-3xl">📊</span>
                    </div>
                    <div>
                        <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter">Relatórios Financeiros</h2>
                        <p class="text-orange-500 text-[10px] font-black uppercase tracking-[0.3em]">Módulo de Gestão Bar</p>
                    </div>
                </div>

                {{-- Link para voltar ao Dashboard --}}
                <a href="{{ route('bar.dashboard') }}" class="px-6 py-3 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border border-gray-700">
                    ← Voltar
                </a>
            </div>

            {{-- 📅 Filtro de Período Dinâmico --}}
            <div class="max-w-7xl mx-auto mb-6">
                <form action="{{ route('bar.reports.index') }}" method="GET" class="flex items-center gap-4 bg-gray-800 p-6 rounded-[2rem] shadow-2xl border border-gray-700">
                    <div class="flex-1">
                        <h4 class="text-sm font-black text-white uppercase ml-2 tracking-tighter">Período de Análise</h4>
                        <p class="text-[10px] text-gray-500 ml-2 font-bold uppercase italic">Selecione o mês para filtrar os KPIs</p>
                    </div>

                    <div class="h-12 w-px bg-gray-700"></div>

                    {{-- Seletor de Mês --}}
                    <div class="px-6 text-center">
                        <label class="text-[10px] font-black uppercase text-gray-600 block mb-2">Mês / Ano</label>
                        <input type="month"
                            name="mes_referencia"
                            value="{{ request('mes_referencia', now()->format('Y-m')) }}"
                            onchange="this.form.submit()"
                            class="bg-black border-2 border-gray-700 rounded-xl font-black text-orange-500 uppercase cursor-pointer text-sm text-center focus:border-orange-600 focus:ring-0 transition-all">
                    </div>
                </form>
            </div>

            {{-- 1. INDICADORES DE PERFORMANCE (KPIs) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                {{-- Card Faturamento Consolidado --}}
                <div class="bg-gradient-to-br from-orange-600 to-red-700 p-8 rounded-[2.5rem] shadow-xl text-white relative overflow-hidden group">
                    <div class="absolute right-[-15px] top-[-15px] opacity-10 group-hover:scale-125 transition-all duration-500">
                        <span class="text-9xl">💰</span>
                    </div>
                    <p class="text-[10px] opacity-70 uppercase font-black tracking-widest">Faturamento Total</p>
                    <p class="text-4xl font-black mt-3 italic tracking-tighter">R$ {{ number_format($faturamentoMensal, 2, ',', '.') }}</p>
                    <p class="text-[9px] mt-4 uppercase font-bold opacity-50 italic bg-black/20 py-1 px-3 rounded-full inline-block">Mesas + Balcão</p>
                </div>

                {{-- Card Volume de Itens --}}
                <div class="bg-gray-800 p-8 rounded-[2.5rem] shadow-lg border border-gray-700 hover:border-gray-600 transition-all">
                    <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Volume de Saída</p>
                    <p class="text-4xl font-black text-white mt-3 italic tracking-tighter">{{ number_format($totalItensMes, 0, ',', '.') }}</p>
                    <p class="text-[9px] mt-4 uppercase font-bold text-gray-600 italic">Produtos Vendidos</p>
                </div>

                {{-- Card Ticket Médio --}}
                <div class="bg-gray-800 p-8 rounded-[2.5rem] shadow-lg border border-gray-700 hover:border-blue-900/30 transition-all">
                    <p class="text-[10px] text-blue-400 uppercase font-black tracking-widest">Ticket Médio</p>
                    <p class="text-4xl font-black text-blue-500 mt-3 italic tracking-tighter">R$ {{ number_format($ticketMedio, 2, ',', '.') }}</p>
                    <p class="text-[9px] mt-4 uppercase font-bold text-gray-600 italic">Média p/ Venda</p>
                </div>

                {{-- Card Sangrias --}}
                <div class="bg-gray-800 p-8 rounded-[2.5rem] shadow-lg border border-red-900/20 hover:border-red-900/40 transition-all">
                    <p class="text-[10px] text-red-400 uppercase font-black tracking-widest">Sangrias / Saídas</p>
                    <p class="text-4xl font-black text-red-500 mt-3 italic tracking-tighter">R$ {{ number_format($totalSangriasMes, 2, ',', '.') }}</p>
                    <p class="text-[9px] mt-4 uppercase font-bold text-gray-700 italic">Retiradas de Caixa</p>
                </div>
            </div>

            {{-- DIVISOR --}}
            <div class="flex items-center gap-6 py-4">
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent"></div>
                <h3 class="text-[10px] font-black text-gray-600 uppercase tracking-[0.4em] italic">Módulos de Auditoria e Análise</h3>
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent"></div>
            </div>

            {{-- 2. GRID DE MENUS REFINADO --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">

                {{-- Ranking de Produtos --}}
                <a href="{{ route('bar.reports.products') }}"
                    class="group flex items-center p-6 bg-gray-800 rounded-[2rem] border border-transparent hover:border-orange-600 transition-all duration-300 shadow-2xl relative overflow-hidden">
                    <div class="p-5 bg-orange-600/10 text-orange-500 rounded-2xl group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <div class="ml-5">
                        <h4 class="text-md font-black text-white uppercase tracking-tight">Top Vendidos</h4>
                        <p class="text-[10px] text-gray-500 font-bold uppercase italic mt-1">Produtos mais lucrativos</p>
                    </div>
                </a>

                {{-- Auditoria de Caixas --}}
                <a href="{{ route('bar.reports.cashier') }}"
                    class="group flex items-center p-6 bg-gray-800 rounded-[2rem] border border-transparent hover:border-emerald-500 transition-all duration-300 shadow-2xl relative overflow-hidden">
                    <div class="p-5 bg-emerald-600/10 text-emerald-500 rounded-2xl group-hover:scale-110 transition-transform duration-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                    </div>
                    <div class="ml-5">
                        <h4 class="text-md font-black text-white uppercase tracking-tight">Sessões de Caixa</h4>
                        <p class="text-[10px] text-gray-500 font-bold uppercase italic mt-1">Conferência de Turnos</p>
                    </div>
                </a>

                {{-- Clientes VIP --}}
                <a href="{{ route('bar.reports.customers') }}"
                    class="group flex items-center p-6 bg-gray-800 rounded-[2rem] border border-transparent hover:border-pink-600 transition-all duration-300 shadow-2xl relative overflow-hidden">
                    <div class="p-5 bg-pink-600/10 text-pink-500 rounded-2xl group-hover:scale-110 transition-transform duration-500">
                        <span class="text-3xl italic">🏆</span>
                    </div>
                    <div class="ml-5">
                        <h4 class="text-md font-black text-white uppercase tracking-tight">Clientes VIP</h4>
                        <p class="text-[10px] text-gray-500 font-bold uppercase italic mt-1">Maiores Consumidores</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
