<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-2xl text-gray-800 dark:text-gray-200 leading-tight flex items-center gap-3">
            <span class="p-2 bg-orange-100 dark:bg-orange-900/50 rounded-lg shadow-sm">📊</span>
            Relatórios Financeiros do Bar
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- 📅 Filtro de Período Dinâmico --}}
            <div class="max-w-7xl mx-auto mb-6">
                <form action="{{ route('bar.reports.index') }}" method="GET" class="flex items-center gap-4 bg-gray-800 p-4 rounded-3xl shadow-sm border border-gray-700">
                    <div class="flex-1">
                        <h4 class="text-sm font-black text-white uppercase ml-2 tracking-tighter">Visão Geral de Consumo</h4>
                        <p class="text-[10px] text-gray-400 ml-2 font-bold uppercase italic">Mês de Referência: {{ request('mes_referencia', now()->format('m/Y')) }}</p>
                    </div>

                    <div class="h-10 w-px bg-gray-700"></div>

                    {{-- Seletor de Mês --}}
                    <div class="px-4 text-center">
                        <label class="text-[10px] font-black uppercase text-gray-500 block mb-1">Alterar Período</label>
                        <input type="month"
                            name="mes_referencia"
                            value="{{ request('mes_referencia', now()->format('Y-m')) }}"
                            onchange="this.form.submit()"
                            class="border-none focus:ring-0 bg-transparent font-bold text-orange-500 uppercase p-0 cursor-pointer text-sm text-center">
                    </div>
                </form>
            </div>

            {{-- 1. INDICADORES DE PERFORMANCE (KPIs) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                {{-- Card Faturamento Consolidado --}}
                <div class="bg-gradient-to-br from-orange-600 to-red-700 p-6 rounded-[2.5rem] shadow-lg text-white relative overflow-hidden group">
                    <div class="absolute right-[-10px] top-[-10px] opacity-10 group-hover:scale-110 transition-transform">
                        <span class="text-8xl">💰</span>
                    </div>
                    <p class="text-xs opacity-80 uppercase font-black tracking-widest">Faturamento Total</p>
                    <p class="text-3xl font-black mt-2 italic">R$ {{ number_format($faturamentoMensal, 2, ',', '.') }}</p>
                    <p class="text-[8px] mt-2 uppercase font-bold opacity-60 italic">* Mesas finalizadas + Vendas PDV</p>
                </div>

                {{-- Card Volume de Itens --}}
                <div class="bg-gray-800 p-6 rounded-[2.5rem] shadow-sm border border-gray-700">
                    <p class="text-xs text-gray-500 uppercase font-black tracking-widest">Volume de Saída</p>
                    <p class="text-3xl font-black text-white mt-2 italic">{{ number_format($totalItensMes, 0, ',', '.') }}</p>
                    <p class="text-[8px] mt-2 uppercase font-bold text-gray-600 italic">Itens Vendidos no Mês</p>
                </div>

                {{-- Card Ticket Médio --}}
                <div class="bg-gray-800 p-6 rounded-[2.5rem] shadow-sm border border-gray-700">
                    <p class="text-xs text-blue-400 uppercase font-black tracking-widest">Ticket Médio</p>
                    <p class="text-3xl font-black text-blue-500 mt-2 italic">R$ {{ number_format($ticketMedio, 2, ',', '.') }}</p>
                    <p class="text-[8px] mt-2 uppercase font-bold text-gray-600 italic">Média por transação</p>
                </div>

                {{-- Card Sangrias --}}
                <div class="bg-gray-800 p-6 rounded-[2.5rem] shadow-sm border border-red-900/20">
                    <p class="text-xs text-red-400 uppercase font-black tracking-widest">Sangrias / Saídas</p>
                    <p class="text-3xl font-black text-red-500 mt-2 italic">R$ {{ number_format($totalSangriasMes, 2, ',', '.') }}</p>
                    <p class="text-[8px] mt-2 uppercase font-bold text-gray-700 italic">Retiradas manuais de caixa</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <h3 class="text-sm font-black text-gray-600 uppercase tracking-[0.3em] italic">Módulos de Auditoria</h3>
                <div class="flex-1 h-px bg-gray-800"></div>
            </div>

            {{-- 2. GRID DE MENUS REFINADO --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                {{-- Ranking de Produtos --}}
                <a href="{{ route('bar.reports.products') }}"
                    class="group flex items-center p-4 bg-gray-800 rounded-3xl border border-transparent hover:border-orange-600 transition-all duration-300 shadow-2xl">
                    <div class="p-5 bg-orange-600/10 text-orange-500 rounded-2xl group-hover:scale-90 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-black text-white uppercase tracking-tight">Top Vendidos</h4>
                        <p class="text-[10px] text-gray-500 font-bold uppercase italic">Ranking de Saída e Receita</p>
                    </div>
                </a>

                {{-- Auditoria de Caixas --}}
                <a href="{{ route('bar.reports.cashier') }}"
                    class="group flex items-center p-4 bg-gray-800 rounded-3xl border border-transparent hover:border-emerald-500 transition-all duration-300 shadow-2xl">
                    <div class="p-5 bg-emerald-600/10 text-emerald-500 rounded-2xl group-hover:scale-90 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-black text-white uppercase tracking-tight">Sessões de Caixa</h4>
                        <p class="text-[10px] text-gray-500 font-bold uppercase italic">Quebras, Sobras e Operadores</p>
                    </div>
                </a>

                {{-- Clientes VIP --}}
                <a href="{{ route('bar.reports.customers') }}"
                    class="group flex items-center p-4 bg-gray-800 rounded-3xl border border-transparent hover:border-pink-600 transition-all duration-300 shadow-2xl">
                    <div class="p-5 bg-pink-600/10 text-pink-500 rounded-2xl group-hover:scale-90 transition-transform">
                        <span class="text-3xl">🏆</span>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-sm font-black text-white uppercase tracking-tight">Fidelidade Bar</h4>
                        <p class="text-[10px] text-gray-500 font-bold uppercase italic">Maiores Consumidores</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
