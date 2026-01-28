<x-bar-layout>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-white">Central de Comando <span class="text-orange-500">Bar</span></h1>
            <p class="text-gray-400">Selecione uma funcionalidade para começar.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <a href="{{ route('bar.products.index') }}" class="group relative bg-gray-800 p-6 rounded-2xl border border-gray-700 hover:border-orange-500 transition-all duration-300 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-orange-500/10 rounded-lg group-hover:bg-orange-500 transition-colors duration-300">
                        <svg class="w-8 h-8 text-orange-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Atalho F1</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-1">PDV / Venda Avulsa</h3>
                <p class="text-sm text-gray-400">Venda rápida com leitor de código de barras e baixa automática.</p>
            </a>

            <a href="{{ route('bar.tables.index') }}" class="group bg-gray-800 p-6 rounded-2xl border border-gray-700 hover:border-orange-500 transition-all duration-300 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-500/10 rounded-lg group-hover:bg-blue-500 transition-colors duration-300">
                        <svg class="w-8 h-8 text-blue-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Comandas</span>
                </div>
                <h3 class="text-xl font-bold text-white mb-1">Gerenciar Mesas</h3>
                <p class="text-sm text-gray-400">Abrir mesas, configurar quantidade e ver mapa de ocupação.</p>
            </a>

            <a href="{{ route('bar.products.index') }}" class="group bg-gray-800 p-6 rounded-2xl border border-gray-700 hover:border-orange-500 transition-all duration-300 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-green-500/10 rounded-lg group-hover:bg-green-500 transition-colors duration-300">
                        <svg class="w-8 h-8 text-green-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white mb-1">Estoque e Produtos</h3>
                <p class="text-sm text-gray-400">Cadastrar itens, editar preços e gerenciar quantidades mínimas.</p>
            </a>

            <a href="{{ route('bar.cash.index') }}" class="group bg-gray-800 p-6 rounded-2xl border border-gray-700 hover:border-orange-500 transition-all duration-300 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-yellow-500/10 rounded-lg group-hover:bg-yellow-500 transition-colors duration-300">
                        <svg class="w-8 h-8 text-yellow-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zM17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white mb-1">Caixa do Bar</h3>
                <p class="text-sm text-gray-400">Abertura, fechamento e conferência de valores do dia.</p>
            </a>

            <a href="#" class="group bg-gray-800 p-6 rounded-2xl border border-gray-700 hover:border-orange-500 transition-all duration-300 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-purple-500/10 rounded-lg group-hover:bg-purple-500 transition-colors duration-300">
                        <svg class="w-8 h-8 text-purple-500 group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-white mb-1">Relatórios de Vendas</h3>
                <p class="text-sm text-gray-400">Análise de lucro, produtos mais vendidos e histórico por período.</p>
            </a>

        </div>
    </div>
</x-bar-layout>
