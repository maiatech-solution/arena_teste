<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="mb-10">
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">
                Central de <span class="text-orange-600">Comando</span>
            </h1>
            <p class="text-gray-500 font-medium tracking-tight">Gestão integrada de consumo, estoque e equipe.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

            <a href="{{ route('bar.pdv') }}" class="group bg-gray-900 p-8 rounded-3xl border border-gray-800 hover:border-orange-600/50 transition-all duration-300 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="text-6xl text-white">🛒</span>
                </div>
                <div class="relative z-10">
                    <div class="w-14 h-14 bg-orange-600 flex items-center justify-center rounded-2xl mb-6 shadow-lg shadow-orange-600/20">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-2 tracking-tighter uppercase">PDV / Balcão</h3>
                    <p class="text-gray-500 text-sm leading-relaxed font-medium">Venda direta para o cliente com baixa imediata no estoque.</p>
                </div>
            </a>

            <a href="{{ route('bar.tables.index') }}" class="group bg-gray-900 p-8 rounded-3xl border border-gray-700/30 hover:border-orange-600/50 transition-all duration-300 shadow-2xl relative">
                <div class="flex justify-between items-start mb-6">
                    <div class="w-14 h-14 bg-gray-800 flex items-center justify-center rounded-2xl group-hover:bg-orange-600 transition-colors duration-300 shadow-inner">
                        <svg class="w-8 h-8 text-orange-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    @if(isset($stats['mesas_abertas']) && $stats['mesas_abertas'] > 0)
                        <span class="bg-orange-600 text-white text-[10px] font-black px-2 py-1 rounded-md animate-pulse">
                            {{ $stats['mesas_abertas'] }} ATIVAS
                        </span>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-white mb-2 tracking-tighter uppercase">Gerenciar Mesas</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Abertura de comandas, fechamento e controle de ocupação.</p>
            </a>

            <a href="{{ route('bar.products.index') }}" class="group bg-gray-900 p-8 rounded-3xl border border-gray-700/30 hover:border-orange-600/50 transition-all duration-300 shadow-2xl relative">
                <div class="flex justify-between items-start mb-6">
                    <div class="w-14 h-14 bg-gray-800 flex items-center justify-center rounded-2xl group-hover:bg-orange-600 transition-colors duration-300 shadow-inner">
                        <svg class="w-8 h-8 text-orange-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    @if(isset($stats['estoque_critico']) && $stats['estoque_critico'] > 0)
                        <span class="bg-red-600 text-white text-[10px] font-black px-2 py-1 rounded-md">
                            {{ $stats['estoque_critico'] }} ALERTAS
                        </span>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-white mb-2 tracking-tighter uppercase">Estoque e Produtos</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Cadastro de itens, preços de custo/venda e níveis mínimos.</p>
            </a>

            <a href="{{ route('bar.cash.index') }}" class="group bg-gray-900 p-8 rounded-3xl border border-gray-700/30 hover:border-orange-600/50 transition-all duration-300 shadow-2xl relative">
                <div class="w-14 h-14 bg-gray-800 flex items-center justify-center rounded-2xl mb-6 group-hover:bg-orange-600 transition-colors duration-300 shadow-inner">
                    <svg class="w-8 h-8 text-orange-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zM17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2 tracking-tighter uppercase">Caixa do Bar</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Abertura, fechamento e fluxo financeiro isolado.</p>
            </a>

            <a href="{{ route('bar.users.index') }}" class="group bg-gray-900 p-8 rounded-3xl border border-gray-700/30 hover:border-orange-600/50 transition-all duration-300 shadow-2xl relative">
                <div class="w-14 h-14 bg-gray-800 flex items-center justify-center rounded-2xl mb-6 group-hover:bg-orange-600 transition-colors duration-300 shadow-inner">
                    <svg class="w-8 h-8 text-orange-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2 tracking-tighter uppercase">Gestão de Equipe</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Controle de colaboradores, cargos e acessos ao painel do bar.</p>
            </a>

            <a href="{{ route('bar.reports.index') }}" class="group bg-gray-900 p-8 rounded-3xl border border-gray-700/30 hover:border-orange-600/50 transition-all duration-300 shadow-2xl relative">
                <div class="flex justify-between items-start mb-6">
                    <div class="w-14 h-14 bg-gray-800 flex items-center justify-center rounded-2xl group-hover:bg-orange-600 transition-colors duration-300 shadow-inner">
                        <svg class="w-8 h-8 text-orange-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="text-[10px] font-black text-gray-600 uppercase tracking-widest group-hover:text-orange-500 transition-colors">Gestão</span>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2 tracking-tighter uppercase">Relatórios do Bar</h3>
                <p class="text-gray-500 text-sm leading-relaxed font-medium">Dashboard de faturamento, ranking de produtos e auditoria de turnos.</p>
            </a>

        </div>
    </div>
</x-bar-layout>
