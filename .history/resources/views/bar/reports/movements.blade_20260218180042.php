<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- CABEÇALHO E NAVEGAÇÃO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6">
            <div class="flex items-center gap-5">
                <a href="{{ route('bar.reports.index') }}"
                    class="p-3 bg-gray-900 hover:bg-gray-800 text-orange-500 rounded-2xl border border-gray-800 transition-all group shadow-lg">
                    <span class="group-hover:-translate-x-1 transition-transform inline-block">◀</span>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                        Movimentação de <span class="text-orange-600">Estoque</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">
                        Auditoria detalhada de entradas e saídas
                    </p>
                </div>
            </div>

            {{-- FILTROS RÁPIDOS --}}
            <form action="{{ route('bar.reports.movements') }}" method="GET"
                class="flex flex-wrap items-center gap-3 bg-gray-900/50 p-2 rounded-[2rem] border border-gray-800/50">

                {{-- Busca por Nome --}}
                <div class="relative group">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="BUSCAR PRODUTO..."
                        class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase pl-6 pr-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 transition-all min-w-[200px]">
                </div>

                {{-- Filtro de Tipo --}}
                <div class="relative">
                    <select name="type" onchange="this.form.submit()"
                        class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase px-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer appearance-none pr-10">
                        <option value="">🔄 Todos os Tipos</option>
                        <option value="entrada" {{ request('type') == 'entrada' ? 'selected' : '' }}>🟢 Entradas</option>
                        <option value="saida" {{ request('type') == 'saida' ? 'selected' : '' }}>🔴 Saídas</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-[8px] text-gray-600">
                        ▼</div>
                </div>

                {{-- Filtro de Data --}}
                <div class="relative group">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-orange-500 pointer-events-none group-hover:scale-110 transition-transform z-10">
                        📅
                    </div>
                    <input type="date" name="date" id="dateInput" value="{{ request('date') }}"
                        onchange="this.form.submit()"
                        class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase pl-12 pr-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 transition-all cursor-pointer min-w-[180px]">
                </div>

                @if (request('type') || request('date') || request('search'))
                    <button type="submit" class="hidden"></button>
                    <a href="{{ route('bar.reports.movements') }}"
                        class="px-4 py-2 text-red-500 hover:text-white transition-colors text-[9px] font-black uppercase tracking-tighter">
                        Limpar Filtros ✕
                    </a>
                @endif
            </form>
        </div>

        {{-- GRID DE RESUMO DE MOVIMENTAÇÃO (VALORES DO PERÍODO FILTRADO) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-900 p-6 rounded-[2rem] border border-gray-800 border-l-4 border-l-green-600">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Entradas no Período</p>
                <h3 class="text-2xl font-black text-white italic">
                    + {{ $movimentacoes->where('type', 'entrada')->sum('quantity') }}
                    <span class="text-xs text-gray-500 font-medium">unidades</span>
                </h3>
            </div>
            <div class="bg-gray-900 p-6 rounded-[2rem] border border-gray-800 border-l-4 border-l-red-600">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Saídas no Período</p>
                <h3 class="text-2xl font-black text-white italic">
                    - {{ abs($movimentacoes->where('type', 'saida')->sum('quantity')) }}
                    <span class="text-xs text-gray-500 font-medium">unidades</span>
                </h3>
            </div>
            <div class="bg-orange-600 p-6 rounded-[2rem] shadow-lg shadow-orange-600/20">
                <p class="text-[10px] font-black text-orange-200 uppercase tracking-widest mb-1">Status da Auditoria</p>
                <h3 class="text-2xl font-black text-white italic uppercase tracking-tighter">Conferido ✅</h3>
            </div>
        </div>

        {{-- TABELA DE HISTÓRICO DE MOVIMENTAÇÕES --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden relative">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40">
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Data / Registro</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Produto</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-center">Tipo</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-center">Qtd</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Responsável / Origem</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @forelse($movimentacoes as $mov)
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                {{-- COLUNA DATA --}}
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <div class="p-2 bg-gray-950 rounded-lg text-orange-500 font-mono text-[10px] border border-gray-800">
                                            #{{ $mov->id }}
                                        </div>
                                        <div>
                                            <span class="text-white font-black text-xs block">{{ $mov->created_at->format('d/m/Y') }}</span>
                                            <span class="text-gray-500 text-[10px] font-bold">{{ $mov->created_at->format('H:i:s') }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- COLUNA PRODUTO --}}
                                <td class="p-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-black rounded-xl flex flex-col items-center justify-center border border-gray-800 group-hover:border-orange-500 transition-colors relative">
                                            <span class="text-[8px] text-gray-600 font-black uppercase leading-none italic">ID</span>
                                            <span class="text-xs text-orange-500 font-mono font-black">{{ $mov->product->id }}</span>
                                            @if ($mov->product->stock_quantity <= 10)
                                                <span class="absolute -top-1 -right-1 flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-600"></span>
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="text-white font-black text-xs uppercase">{{ $mov->product->name }}</span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-600 text-[9px] block uppercase font-bold">
                                                    {{ $mov->product->category->name ?? 'Geral' }}
                                                </span>
                                                @if ($mov->product->stock_quantity <= 10)
                                                    <span class="text-[8px] font-black text-red-500 uppercase px-1.5 py-0.5 bg-red-500/10 border border-red-500/20 rounded-md">
                                                        Stock: {{ $mov->product->stock_quantity }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- COLUNA TIPO --}}
                                <td class="p-6 text-center">
                                    <span class="px-3 py-1 {{ $mov->type == 'entrada' ? 'bg-green-950 text-green-500 border-green-500/30' : 'bg-red-950 text-red-500 border-red-500/30' }} text-[9px] font-black uppercase rounded-full border">
                                        ● {{ $mov->type }}
                                    </span>
                                </td>

                                {{-- COLUNA QUANTIDADE --}}
                                <td class="p-6 text-center">
                                    <div class="inline-block px-4 py-1 bg-black rounded-lg border border-gray-800">
                                        <span class="text-sm font-black {{ $mov->type == 'entrada' ? 'text-green-500' : 'text-red-500' }}">
                                            {{ $mov->type == 'entrada' ? '+' : '-' }} {{ abs($mov->quantity) }}
                                        </span>
                                    </div>
                                </td>

                                {{-- COLUNA RESPONSÁVEL --}}
                                <td class="p-6">
                                    <span class="text-gray-300 font-black text-[10px] uppercase block tracking-tighter italic">
                                        {{ $mov->user->name ?? 'Sistema' }}
                                    </span>
                                    <span class="text-gray-500 text-[10px] font-medium leading-tight block max-w-xs truncate">
                                        {{ $mov->description }}
                                    </span>
                                </td>

                                {{-- COLUNA AÇÃO --}}
                                <td class="p-6 text-right">
                                    <button class="p-2 text-gray-600 hover:text-white opacity-0 group-hover:opacity-100 transition-all transform hover:scale-125">
                                        🔍
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-24 text-center">
                                    <p class="text-gray-500 text-xs font-black uppercase tracking-widest italic">Nenhum registro encontrado para este período.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINAÇÃO --}}
            <div class="p-8 bg-black/40 border-t border-gray-800">
                {{ $movimentacoes->appends(request()->query())->links() }}
            </div>
        </div>

        {{-- POSIÇÃO ATUAL DE ESTOQUE (INVENTÁRIO REAL-TIME) --}}
        <div class="mb-10 mt-12 bg-gray-900 border border-gray-800 rounded-[2.5rem] p-8 shadow-2xl">
            <div class="flex items-center gap-3 mb-8">
                <span class="text-2xl">📊</span>
                <h2 class="text-lg font-black text-white uppercase italic tracking-tighter">
                    Posição Atual de <span class="text-orange-600">Estoque</span>
                </h2>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-4">
                @foreach ($inventorySummary as $product)
                    <div class="bg-black/40 border {{ $product->stock_quantity <= 10 ? 'border-red-900/50 animate-pulse' : 'border-gray-800' }} p-4 rounded-2xl flex flex-col items-center justify-center text-center group hover:border-orange-500/50 transition-all">
                        <span class="text-gray-500 text-[9px] font-black uppercase mb-1 tracking-widest line-clamp-1 group-hover:text-white transition-colors">
                            {{ $product->name }}
                        </span>

                        <div class="flex items-baseline gap-1">
                            <span class="text-xl font-black {{ $product->stock_quantity <= 10 ? 'text-red-500' : 'text-white' }}">
                                {{ $product->stock_quantity }}
                            </span>
                            <span class="text-[8px] text-gray-600 font-bold uppercase tracking-tighter">un</span>
                        </div>

                        {{-- Barra de Progresso --}}
                        <div class="w-full bg-gray-800 h-1 mt-2 rounded-full overflow-hidden">
                            <div class="h-full {{ $product->stock_quantity <= 10 ? 'bg-red-600' : 'bg-orange-600' }}"
                                style="width: {{ min(($product->stock_quantity / 50) * 100, 100) }}%">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ESTILOS CSS --}}
    <style>
        input[type="date"] { color-scheme: dark; }

        input[type="date"]::-webkit-calendar-picker-indicator {
            position: absolute; left: 0; top: 0; width: 100%; height: 100%;
            margin: 0; padding: 0; cursor: pointer; opacity: 0;
        }

        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        input:focus {
            border: 1px solid #ea580c !important;
            box-shadow: 0 0 0 1px #ea580c;
        }

        @keyframes pulse-soft {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .animate-pulse-soft { animation: pulse-soft 2s infinite; }
    </style>
</x-bar-layout>
