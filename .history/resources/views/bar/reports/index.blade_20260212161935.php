<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- 🍺 HEADER DO RELATÓRIO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Relatórios <span class="text-orange-600">Financeiros</span>
                </h1>
                <p class="text-gray-500 font-medium italic">Análise de faturamento e desempenho do bar.</p>
            </div>

            {{-- 📅 Filtro de Período (Mês/Ano) --}}
            <div class="bg-gray-900/40 border-2 border-gray-800 p-2 rounded-3xl flex items-center gap-4 px-6 shadow-inner">
                <form action="{{ route('bar.reports.index') }}" method="GET" class="flex items-center gap-4">
                    <div class="text-right">
                        <label class="text-[9px] font-black text-gray-500 uppercase tracking-widest block leading-none">Período</label>
                        <input type="month"
                            name="mes_referencia"
                            value="{{ request('mes_referencia', now()->format('Y-m')) }}"
                            onchange="this.form.submit()"
                            class="bg-transparent border-none p-0 font-black text-orange-500 uppercase text-sm focus:ring-0 cursor-pointer">
                    </div>
                    <span class="text-gray-700 text-xl">|</span>
                    <span class="text-2xl">📅</span>
                </form>
            </div>
        </div>

        {{-- 📊 INDICADORES DE PERFORMANCE (KPIs) --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">

            {{-- Faturamento Consolidado --}}
            <div class="p-8 rounded-[2.5rem] bg-orange-600/10 border-2 border-orange-600/20 shadow-lg shadow-orange-600/5 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 opacity-5 group-hover:scale-110 transition-transform duration-500">
                    <span class="text-9xl text-orange-500 font-black">💰</span>
                </div>
                <h4 class="text-[10px] font-black text-orange-500 uppercase tracking-widest mb-2">Faturamento Total</h4>
                <p class="text-4xl font-black text-white italic tracking-tighter">
                    R$ {{ number_format($faturamentoMensal, 2, ',', '.') }}
                </p>
                <p class="text-[9px] text-gray-500 font-bold uppercase italic mt-4 px-3 py-1 bg-black/40 rounded-full inline-block">
                    Mesas + Balcão
                </p>
            </div>

            {{-- Itens Vendidos --}}
            <div class="p-8 rounded-[2.5rem] bg-gray-900/40 border-2 border-gray-800 hover:border-gray-700 transition-all">
                <h4 class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Volume de Saída</h4>
                <p class="text-4xl font-black text-white italic tracking-tighter">
                    {{ number_format($totalItensMes, 0, ',', '.') }}
                </p>
                <p class="text-[9px] text-gray-600 font-bold uppercase italic mt-4">Unidades Vendidas</p>
            </div>

            {{-- Ticket Médio --}}
            <div class="p-8 rounded-[2.5rem] bg-gray-900/40 border-2 border-gray-800 hover:border-blue-900/30 transition-all">
                <h4 class="text-[10px] font-black text-blue-500 uppercase tracking-widest mb-2">Ticket Médio</h4>
                <p class="text-4xl font-black text-blue-500 italic tracking-tighter">
                    R$ {{ number_format($ticketMedio, 2, ',', '.') }}
                </p>
                <p class="text-[9px] text-gray-600 font-bold uppercase italic mt-4">Média p/ Transação</p>
            </div>

            {{-- Sangrias --}}
            <div class="p-8 rounded-[2.5rem] bg-gray-900/40 border-2 border-gray-800 hover:border-red-900/30 transition-all">
                <h4 class="text-[10px] font-black text-red-500 uppercase tracking-widest mb-2">Total Sangrias</h4>
                <p class="text-4xl font-black text-red-500 italic tracking-tighter">
                    R$ {{ number_format($totalSangriasMes, 2, ',', '.') }}
                </p>
                <p class="text-[9px] text-gray-600 font-bold uppercase italic mt-4">Saídas de Caixa</p>
            </div>
        </div>

        {{-- 🏷️ DIVISOR DE SEÇÃO --}}
        <div class="flex items-center gap-4 mb-8">
            <h3 class="text-[10px] font-black text-gray-600 uppercase tracking-[0.4em] italic">Módulos de Auditoria</h3>
            <div class="flex-1 h-px bg-gray-800"></div>
        </div>

        {{-- 🚀 MENU DE RELATÓRIOS ESPECÍFICOS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

            {{-- Ranking de Produtos --}}
            <a href="{{ route('bar.reports.products') }}"
               class="group p-8 bg-gray-900/40 border-2 border-gray-800 rounded-[2.5rem] hover:border-orange-600 transition-all flex items-center gap-6 shadow-xl">
                <div class="w-16 h-16 bg-orange-600/10 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    🔥
                </div>
                <div>
                    <h4 class="text-white font-black uppercase italic text-lg leading-tight">Top Vendidos</h4>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Produtos Campeões</p>
                </div>
            </a>

            {{-- Histórico de Caixas --}}
            <a href="{{ route('bar.reports.cashier') }}"
               class="group p-8 bg-gray-900/40 border-2 border-gray-800 rounded-[2.5rem] hover:border-emerald-500 transition-all flex items-center gap-6 shadow-xl">
                <div class="w-16 h-16 bg-emerald-600/10 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    📟
                </div>
                <div>
                    <h4 class="text-white font-black uppercase italic text-lg leading-tight">Auditoria Caixa</h4>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Sessões e Turnos</p>
                </div>
            </a>

            {{-- Clientes VIP --}}
            <a href="{{ route('bar.reports.customers') }}"
               class="group p-8 bg-gray-900/40 border-2 border-gray-800 rounded-[2.5rem] hover:border-pink-600 transition-all flex items-center gap-6 shadow-xl">
                <div class="w-16 h-16 bg-pink-600/10 rounded-2xl flex items-center justify-center text-3xl group-hover:scale-110 transition-transform">
                    👑
                </div>
                <div>
                    <h4 class="text-white font-black uppercase italic text-lg leading-tight">Fidelidade</h4>
                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Clientes VIP</p>
                </div>
            </a>

        </div>
    </div>
</x-bar-layout>
