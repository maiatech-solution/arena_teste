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
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Auditoria detalhada de
                        entradas e saídas</p>
                </div>
            </div>

            {{-- FILTROS RÁPIDOS --}}
            <form action="{{ route('bar.reports.movements') }}" method="GET"
                class="flex flex-wrap items-center gap-3 bg-gray-900/50 p-2 rounded-[2rem] border border-gray-800/50">

                {{-- Filtro de Tipo --}}
                <div class="relative">
                    <select name="type" onchange="this.form.submit()"
                        class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase px-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer appearance-none pr-10">
                        <option value="">🔄 Todos os Tipos</option>
                        <option value="entrada" {{ request('type') == 'entrada' ? 'selected' : '' }}>🟢 Entradas
                        </option>
                        <option value="saida" {{ request('type') == 'saida' ? 'selected' : '' }}>🔴 Saídas</option>
                    </select>
                    <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-[8px] text-gray-600">
                        ▼</div>
                </div>

                {{-- Filtro de Data com Ícone e Seletor Nativo --}}
                <div class="relative group">
                    {{-- Ícone de Calendário --}}
                    <div
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-orange-500 pointer-events-none group-hover:scale-110 transition-transform z-10">
                        📅
                    </div>

                    <input type="date" name="date" id="dateInput" value="{{ request('date') }}"
                        onchange="this.form.submit()"
                        class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase pl-12 pr-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 transition-all cursor-pointer min-w-[180px]">
                </div>

                @if (request('type') || request('date'))
                    <a href="{{ route('bar.reports.movements') }}"
                        class="px-4 py-2 text-red-500 hover:text-white transition-colors text-[9px] font-black uppercase tracking-tighter">
                        Limpar Filtros ✕
                    </a>
                @endif
            </form>
        </div>

        {{-- GRID DE RESUMO RÁPIDO --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-gray-900 p-6 rounded-[2rem] border border-gray-800 border-l-4 border-l-green-600">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Total Entradas (Mês)</p>
                <h3 class="text-2xl font-black text-white italic">+
                    {{ $movimentacoes->where('type', 'entrada')->sum('quantity') }} <span
                        class="text-xs text-gray-500 font-medium">unidades</span></h3>
            </div>
            <div class="bg-gray-900 p-6 rounded-[2rem] border border-gray-800 border-l-4 border-l-red-600">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Total Saídas (Mês)</p>
                <h3 class="text-2xl font-black text-white italic">
                    {{ $movimentacoes->where('type', 'saida')->sum('quantity') }} <span
                        class="text-xs text-gray-500 font-medium">unidades</span></h3>
            </div>
            <div class="bg-orange-600 p-6 rounded-[2rem] shadow-lg shadow-orange-600/20">
                <p class="text-[10px] font-black text-orange-200 uppercase tracking-widest mb-1">Eficiência de Giro</p>
                <h3 class="text-2xl font-black text-white italic">AUDITADO</h3>
            </div>
        </div>

        {{-- TABELA ESTILIZADA --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden relative">
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40">
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Data /
                                Registro</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Produto</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-center">
                                Tipo</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-center">
                                Qtd</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Responsável
                                / Origem</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-right">
                                Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @forelse($movimentacoes as $mov)
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="p-2 bg-gray-950 rounded-lg text-orange-500 font-mono text-[10px] border border-gray-800">
                                            #{{ $mov->id }}
                                        </div>
                                        <div>
                                            <span
                                                class="text-white font-black text-xs block">{{ $mov->created_at->format('d/m/Y') }}</span>
                                            <span
                                                class="text-gray-500 text-[10px] font-bold">{{ $mov->created_at->format('H:i:s') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 bg-black rounded-xl flex items-center justify-center border border-gray-800 group-hover:border-orange-500 transition-colors">
                                            📦
                                        </div>
                                        <div>
                                            <span
                                                class="text-white font-black text-xs uppercase">{{ $mov->product->name }}</span>
                                            <span
                                                class="text-gray-600 text-[9px] block uppercase font-bold">{{ $mov->product->category->name ?? 'Sem Categoria' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6 text-center">
                                    @if ($mov->type == 'entrada')
                                        <span
                                            class="px-3 py-1 bg-green-950 text-green-500 text-[9px] font-black uppercase rounded-full border border-green-500/30">
                                            ● Entrada
                                        </span>
                                    @else
                                        <span
                                            class="px-3 py-1 bg-red-950 text-red-500 text-[9px] font-black uppercase rounded-full border border-red-500/30">
                                            ● Saída
                                        </span>
                                    @endif
                                </td>
                                <td class="p-6 text-center">
                                    <div class="inline-block px-4 py-1 bg-black rounded-lg border border-gray-800">
                                        <span
                                            class="text-sm font-black {{ $mov->type == 'entrada' ? 'text-green-500' : 'text-red-500' }}">
                                            {{ $mov->type == 'entrada' ? '+' : '-' }} {{ abs($mov->quantity) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-6">
                                    <span
                                        class="text-gray-300 font-black text-[10px] uppercase block tracking-tighter">{{ $mov->user->name ?? 'Sistema' }}</span>
                                    <span class="text-gray-500 text-[10px] font-medium">{{ $mov->description }}</span>
                                </td>
                                <td class="p-6 text-right">
                                    <button
                                        class="p-2 text-gray-600 hover:text-white opacity-0 group-hover:opacity-100 transition-all">
                                        🔍
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-24 text-center">
                                    <div class="inline-block p-8 bg-gray-950 rounded-full mb-4">
                                        <span class="text-6xl grayscale opacity-20">🔎</span>
                                    </div>
                                    <p class="text-gray-500 text-xs font-black uppercase tracking-widest">Nenhum
                                        registro encontrado para este período.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINAÇÃO CUSTOMIZADA --}}
            <div class="p-8 bg-black/40 border-t border-gray-800">
                {{ $movimentacoes->appends(request()->query())->links() }}
            </div>
        </div>
    </div>

    <style>
        /* 1. Força o calendário nativo (Datepicker) a abrir no Modo Escuro */
        input[type="date"] {
            color-scheme: dark;
        }

        /* 2. Deixa o ícone de calendário invisível mas clicável em todo o campo */
        /* Isso permite que o usuário clique em qualquer lugar do input para abrir o calendário */
        input[type="date"]::-webkit-calendar-picker-indicator {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            cursor: pointer;
            opacity: 0;
        }

        /* 3. Reset de Barras de Rolagem */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* 4. Toque extra: Efeito de foco no input para destacar o laranja */
        input[type="date"]:focus {
            border: 1px solid #ea580c !important;
            /* orange-600 */
            box-shadow: 0 0 0 1px #ea580c;
        }
    </style>
</x-bar-layout>
