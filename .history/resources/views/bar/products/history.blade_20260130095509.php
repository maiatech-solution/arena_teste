<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- CABEÇALHO E RESUMO --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <a href="{{ route('bar.products.index') }}"
                    class="text-orange-500 hover:text-orange-400 text-sm font-bold flex items-center gap-2 mb-4 transition-colors">
                    ⬅️ VOLTAR PARA ESTOQUE
                </a>
                <h2 class="text-3xl font-black text-white uppercase tracking-tighter">📜 Histórico de <span
                        class="text-orange-500">Movimentações</span></h2>
                <p class="text-gray-500 text-sm font-medium">Registro cronológico de entradas, saídas e ajustes do
                    inventário.</p>
            </div>

            <div class="bg-gray-900 border border-gray-800 px-6 py-4 rounded-2xl shadow-xl">
                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-1">Total de
                    Registros</span>
                <span class="text-2xl font-black text-white italic">{{ $movements->total() }}</span>
            </div>
        </div>

        {{-- BARRA DE FILTROS --}}
        {{-- BARRA DE FILTROS EVOLUÍDA: PERÍODO PERSONALIZADO --}}
        <div class="bg-gray-900/80 backdrop-blur-xl border border-gray-800 p-6 rounded-[2rem] mb-8 shadow-2xl">
            <form action="{{ route('bar.products.history') }}" method="GET"
                class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">📑 Tipo</label>
                    <select name="type" onchange="this.form.submit()"
                        class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white text-xs font-bold p-4 focus:ring-2 focus:ring-orange-500 appearance-none cursor-pointer">
                        <option value="">🔄 Todos</option>
                        <option value="entrada" {{ request('type') == 'entrada' ? 'selected' : '' }}>🟢 Entradas
                        </option>
                        <option value="saida" {{ request('type') == 'saida' ? 'selected' : '' }}>🔴 Saídas</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">📅 De:</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                        class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white text-xs font-bold p-4 focus:ring-2 focus:ring-orange-500 color-scheme-dark">
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">📅 Até:</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                        class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white text-xs font-bold p-4 focus:ring-2 focus:ring-orange-500 color-scheme-dark">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-orange-600 hover:bg-orange-500 text-white font-black p-4 rounded-2xl transition-all shadow-lg uppercase text-[10px] tracking-widest">
                        Filtrar
                    </button>
                    <a href="{{ route('bar.products.history') }}"
                        class="bg-gray-800 hover:bg-red-900/40 text-gray-400 p-4 rounded-2xl border border-gray-700 transition-all"
                        title="Limpar">
                        🧹
                    </a>
                </div>
            </form>
        </div>

        {{-- TABELA DE MOVIMENTAÇÕES --}}
        <div class="bg-gray-900 rounded-3xl border border-gray-800 overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-800/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="p-4">Data/Hora</th>
                        <th class="p-4">Produto</th>
                        <th class="p-4 text-center">Tipo</th>
                        <th class="p-4 text-center">Movimentação</th>
                        <th class="p-4 text-right">Responsável</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800 text-sm">
                    @forelse($movements as $mov)
                        <tr class="hover:bg-gray-950/50 transition">
                            <td class="p-4 text-gray-500 font-mono text-xs">
                                <span class="text-gray-300 font-bold">{{ $mov->created_at->format('d/m/Y') }}</span><br>
                                <span class="text-[10px] opacity-70">{{ $mov->created_at->format('H:i:s') }}</span>
                            </td>

                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span
                                        class="text-white font-black uppercase tracking-tight text-sm">{{ $mov->product->name }}</span>
                                    <span class="text-[10px] text-gray-500 italic font-medium leading-tight mt-1">
                                        💬 {{ $mov->description ?? 'Sem observações registradas' }}
                                    </span>
                                </div>
                            </td>

                            <td class="p-4 text-center">
                                <span
                                    class="px-3 py-1 rounded-lg text-[10px] font-black uppercase
                                {{ $mov->quantity > 0 ? 'bg-green-950 text-green-500 border border-green-500/20' : '' }}
                                {{ $mov->quantity <= 0 ? 'bg-red-950 text-red-500 border border-red-500/20' : '' }}">
                                    {{ $mov->quantity > 0 ? 'ENTRADA' : 'SAÍDA' }}
                                </span>
                            </td>

                            <td class="p-4 text-center">
                                <span
                                    class="font-black text-lg {{ $mov->quantity > 0 ? 'text-green-500' : 'text-red-500' }}">
                                    {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}
                                </span>
                            </td>

                            <td class="p-4 text-right">
                                <div class="flex flex-col items-end">
                                    <span
                                        class="text-white font-bold uppercase text-[11px]">{{ $mov->user->name }}</span>
                                    <span
                                        class="text-[9px] text-gray-500 uppercase tracking-tighter italic">Responsável</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-20 text-center">
                                <div class="flex flex-col items-center text-gray-600">
                                    <span class="text-5xl mb-4 opacity-20">🔎</span>
                                    <p class="italic font-bold uppercase text-xs tracking-widest">Nenhuma movimentação
                                        encontrada para os filtros aplicados.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINAÇÃO --}}
        <div class="mt-8 mb-12">
            {{ $movements->links() }}
        </div>

    </div>

    <style>
        /* Ajuste do SVG da paginação para não ficar gigante */
        nav[role="navigation"] svg {
            width: 1.25rem;
            display: inline;
        }

        .relative.z-0.inline-flex.shadow-sm.rounded-md {
            background-color: #111827;
            border: 1px solid #1f2937;
        }

        /* Estilização básica dos links da paginação */
        .relative.z-0.inline-flex a,
        .relative.z-0.inline-flex span {
            color: #9ca3af;
            border-color: #374151;
        }

        .relative.z-0.inline-flex span[aria-current="page"] span {
            background-color: #f97316;
            color: white;
            border-color: #f97316;
        }
    </style>
</x-bar-layout>
