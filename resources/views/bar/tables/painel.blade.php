<x-bar-layout>
    <div class="min-h-[80vh] flex flex-col items-center justify-center px-6">

        {{-- Cabeçalho do Painel --}}
        <div class="text-center mb-16">
            <div class="inline-block p-4 bg-orange-600/10 border border-orange-600/20 rounded-3xl mb-6 shadow-lg">
                <span class="text-4xl">🍽️</span>
            </div>
            <h1 class="text-5xl font-black text-white uppercase tracking-tighter italic leading-none">
                Módulo <span class="text-orange-600">Mesas</span>
            </h1>
            <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.3em] mt-4 font-mono">
                Gestão de Comandas e Atendimento de Salão
            </p>
        </div>

        {{-- Grid de Seleção --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-4xl">

            {{-- CARD 1: Interface de Operação (Mapa de Mesas) --}}
            <a href="{{ route('bar.tables.index') }}"
                class="group relative bg-gray-900 border border-gray-800 p-10 rounded-[3rem] hover:border-orange-600 transition-all duration-500 shadow-2xl hover:-translate-y-2 overflow-hidden">

                {{-- Efeito de Fundo --}}
                <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="text-8xl">📍</span>
                </div>

                <div class="relative z-10">
                    <div class="w-16 h-16 bg-orange-600/10 border border-orange-600/20 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:bg-orange-600 group-hover:text-white transition-all shadow-lg">
                        🪑
                    </div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter mb-2">Mapa de Mesas</h2>
                    <p class="text-gray-400 text-sm font-bold leading-relaxed">
                        Abrir novas mesas, lançar pedidos, gerenciar consumo e realizar o fechamento de comandas.
                    </p>
                </div>
            </a>

            {{-- CARD 2: Histórico de Mesas/Comandas --}}
            <a href="{{ route('bar.vendas.mesas.index') }}"
                class="group relative bg-gray-900 border border-gray-800 p-10 rounded-[3rem] hover:border-gray-400 transition-all duration-500 shadow-2xl hover:-translate-y-2 overflow-hidden">

                {{-- Efeito de Fundo --}}
                <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity">
                    <span class="text-8xl">📂</span>
                </div>

                <div class="relative z-10">
                    <div class="w-16 h-16 bg-gray-800 border border-gray-700 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:bg-white group-hover:text-black transition-all shadow-lg">
                        📋
                    </div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter mb-2">Histórico</h2>
                    <p class="text-gray-400 text-sm font-bold leading-relaxed">
                        Consultar comandas encerradas, auditar itens consumidos e realizar estornos com supervisão.
                    </p>
                </div>
            </a>

        </div>

        {{-- Botão Voltar --}}
        <a href="{{ route('bar.dashboard') }}" class="mt-16 text-gray-600 hover:text-white font-black uppercase text-[10px] tracking-widest transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            VOLTAR AO DASHBOARD
        </a>
    </div>
</x-bar-layout>
