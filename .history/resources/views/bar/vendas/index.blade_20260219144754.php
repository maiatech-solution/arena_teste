<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        {{-- 🛰️ CABEÇALHO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="flex items-center gap-5">
                <div class="p-4 bg-orange-600/10 border border-orange-600/20 rounded-3xl shadow-lg shadow-orange-900/20">
                    <span class="text-3xl">📋</span>
                </div>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic leading-none">
                        Histórico de <span class="text-orange-500">Vendas</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-2">
                        Gestão de comandas finalizadas e auditoria de cancelamentos
                    </p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="bg-gray-900 border border-gray-800 px-6 py-3 rounded-2xl border-l-4 border-l-green-500">
                    <span class="block text-[8px] font-black text-gray-500 uppercase">Total Exibido</span>
                    <span class="text-xl font-black text-white italic tracking-tighter font-mono">
                        R$ {{ number_format($vendas->where('status', 'paid')->sum('total_value'), 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 🔍 BARRA DE FILTROS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2rem] p-4 mb-8 shadow-xl">
            <form action="{{ route('bar.vendas.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[150px]">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block">Buscar ID</label>
                    <input type="text" name="id" value="{{ request('id') }}" placeholder="#0000"
                        class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-orange-500 outline-none transition-all">
                </div>

                <div class="flex-1 min-w-[150px]">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block">Filtrar Data</label>
                    <input type="date" name="date" value="{{ request('date') }}"
                        class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-orange-500 outline-none transition-all">
                </div>

                <div class="flex-1 min-w-[150px]">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block">Status</label>
                    <select name="status" class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-orange-500 outline-none transition-all cursor-pointer">
                        <option value="">TODOS</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>PAGAS</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>CANCELADAS</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white px-6 py-3 rounded-xl font-black text-[10px] uppercase transition-all shadow-lg shadow-orange-600/20">
                        Filtrar
                    </button>
                    @if(request()->anyFilled(['id', 'date', 'status']))
                        <a href="{{ route('bar.vendas.index') }}" class="bg-gray-800 hover:bg-gray-700 text-gray-400 px-6 py-3 rounded-xl font-black text-[10px] uppercase transition-all flex items-center">
                            Limpar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- 📋 LISTAGEM DE VENDAS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden relative">
            <div class="absolute right-0 top-0 p-10 opacity-5 text-8xl italic font-black text-white uppercase pointer-events-none">History</div>

            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-800/50">
                            <th class="p-8">ID / Data / Hora</th>
                            <th class="p-8">Responsável</th>
                            <th class="p-8">Detalhamento dos Itens</th>
                            <th class="p-8 text-right">Valor Consolidado</th>
                            <th class="p-8 text-center">Status</th>
                            <th class="p-8 text-center">Auditoria</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/40">
                        @forelse($vendas as $venda)
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-black border border-gray-800 flex flex-col items-center justify-center text-xs font-black text-gray-400 group-hover:text-orange-500 transition-colors shadow-inner italic">
                                            #{{ $venda->id }}
                                        </div>
                                        <div>
                                            <span class="text-white font-black block text-sm tracking-tighter">{{ $venda->updated_at->format('d/m/Y') }}</span>
                                            <span class="text-gray-600 text-[10px] font-bold uppercase tracking-widest">{{ $venda->updated_at->format('H:i') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-8 text-gray-400 font-black text-[10px] uppercase">
                                    {{ $venda->user->name ?? 'Sistema' }}
                                </td>
                                <td class="p-8">
                                    <div class="flex flex-wrap gap-1.5 max-w-sm">
                                        @foreach($venda->items as $item)
                                            <span class="text-[9px] bg-gray-800/50 text-gray-400 px-2.5 py-1 rounded-xl border border-gray-700/50 font-black uppercase italic">
                                                {{ (int)$item->quantity }}x <span class="text-gray-300">{{ $item->product->name }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-8 text-right">
                                    <span class="text-3xl font-black text-white italic tracking-tighter font-mono">
                                        R$ {{ number_format($venda->total_value, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="p-8 text-center">
                                    @if($venda->status == 'paid')
                                        <span class="px-4 py-1.5 bg-green-500/10 text-green-500 text-[9px] font-black rounded-full border border-green-500/20 uppercase tracking-widest shadow-[0_0_15px_rgba(34,197,94,0.1)]">Paga</span>
                                    @else
                                        <span class="px-4 py-1.5 bg-red-500/10 text-red-500 text-[9px] font-black rounded-full border border-red-500/20 uppercase tracking-widest shadow-[0_0_15px_rgba(239,68,68,0.1)]">Cancelada</span>
                                    @endif
                                </td>
                                <td class="p-8 text-center">
                                    @if($venda->status == 'paid')
                                        <button onclick="abrirModalCancelamento({{ $venda->id }}, '{{ number_format($venda->total_value, 2, ',', '.') }}')"
                                            class="w-10 h-10 flex items-center justify-center bg-gray-950 hover:bg-red-600 text-red-500 hover:text-white rounded-xl transition-all border border-gray-800 hover:border-red-500 shadow-lg">
                                            🚫
                                        </button>
                                    @else
                                        <span class="text-[10px] text-gray-700 font-black uppercase italic">Anulada</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-32 text-center opacity-30">
                                    <p class="text-gray-600 font-black uppercase tracking-[0.5em] italic text-2xl">Nenhum Registro</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 🔢 PAGINAÇÃO ESTILIZADA --}}
            @if($vendas->hasPages())
                <div class="p-8 bg-black/20 border-t border-gray-800">
                    <div class="pagination-wrapper custom-dark-pagination">
                        {{ $vendas->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL DE CANCELAMENTO (Inalterado) --}}
    {{-- ... seu modal de cancelamento aqui ... --}}

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        input[type="date"] { color-scheme: dark; }

        /* Estilização para o padrão Tailwind do Laravel Pagination */
        .custom-dark-pagination nav div:last-child span.relative,
        .custom-dark-pagination nav div:last-child a.relative {
            background-color: #111827 !important;
            border-color: #1f2937 !important;
            color: #9ca3af !important;
            border-radius: 0.75rem !important;
            margin: 0 2px;
        }
        .custom-dark-pagination nav div:last-child span.relative[aria-current="page"] {
            background-color: #ea580c !important;
            color: white !important;
            border-color: #ea580c !important;
        }
    </style>
</x-bar-layout>
