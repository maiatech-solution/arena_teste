<x-bar-layout>
    <div class="min-h-[80vh] flex flex-col items-center justify-center px-6">

        {{-- Cabeçalho do Painel --}}
        <div class="text-center mb-16">
            <h1 class="text-5xl font-black text-white uppercase tracking-tighter italic">
                Módulo <span class="text-indigo-500">PDV</span>
            </h1>
            <p class="text-gray-500 font-bold text-xs uppercase tracking-[0.3em] mt-4">
                Venda Direta e Balcão
            </p>
        </div>

        {{-- Cards de Escolha --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-4xl">

            {{-- Card: Fazer Venda --}}
            <a href="{{ route('bar.pdv.index') }}"
                class="group relative bg-gray-900 border border-gray-800 p-10 rounded-[3rem] hover:border-indigo-500 transition-all duration-500 shadow-2xl hover:-translate-y-2">
                <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="text-7xl">🛒</span>
                </div>

                <div class="relative z-10">
                    <div class="w-16 h-16 bg-indigo-600/10 border border-indigo-600/20 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:bg-indigo-600 group-hover:text-white transition-all">
                        ⚡
                    </div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter mb-2">Fazer Venda</h2>
                    <p class="text-gray-500 text-sm font-medium leading-relaxed">
                        Abrir interface de vendas rápidas, lançar produtos e finalizar pagamentos.
                    </p>
                </div>
            </a>

            {{-- Card: Histórico --}}
            <a href="{{ route('bar.vendas.pdv.index') }}"
                class="group relative bg-gray-900 border border-gray-800 p-10 rounded-[3rem] hover:border-cyan-500 transition-all duration-500 shadow-2xl hover:-translate-y-2">
                <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:opacity-20 transition-opacity">
                    <span class="text-7xl">📄</span>
                </div>

                <div class="relative z-10">
                    <div class="w-16 h-16 bg-cyan-600/10 border border-cyan-600/20 rounded-2xl flex items-center justify-center text-3xl mb-6 group-hover:bg-cyan-500 group-hover:text-white transition-all">
                        📋
                    </div>
                    <h2 class="text-3xl font-black text-white uppercase italic tracking-tighter mb-2">Histórico</h2>
                    <p class="text-gray-500 text-sm font-medium leading-relaxed">
                        Consultar vendas realizadas, emitir 2ª via e realizar estornos autorizados.
                    </p>
                </div>
            </a>

        </div>

        {{-- Botão Voltar --}}
        <a href="{{ route('bar.dashboard') }}" class="mt-16 text-gray-600 hover:text-white font-black uppercase text-[10px] tracking-widest transition-colors">
            ← Voltar ao Início
        </a>
    </div>
</x-bar-layout>
