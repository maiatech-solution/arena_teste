<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="flex items-center gap-5">
                <a href="{{ route('bar.reports.index') }}"
                    class="p-3 bg-gray-900 hover:bg-gray-800 text-red-500 rounded-2xl border border-gray-800 transition-all shadow-lg group">
                    <span class="group-hover:-translate-x-1 transition-transform inline-block">◀</span>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic leading-none">
                        Auditoria de <span class="text-red-600">Cancelamentos</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Rastreamento de perdas e exclusões de itens</p>
                </div>
            </div>

            <form action="{{ route('bar.reports.cancelations') }}" method="GET" class="bg-gray-900 p-1 rounded-2xl border border-gray-800 shadow-xl">
                <input type="month" name="mes_referencia" value="{{ $mesReferencia }}" onchange="this.form.submit()"
                    class="bg-transparent border-none p-2 font-black text-red-500 uppercase text-xs focus:ring-0 cursor-pointer text-center">
            </form>
        </div>

        {{-- CARDS DE RESUMO --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 border-l-4 border-l-red-600 shadow-2xl relative overflow-hidden group">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Comandas Canceladas</p>
                <h3 class="text-3xl font-black text-white italic tracking-tighter font-mono font-black">
                    {{ $cancelamentos->count() }} <span class="text-xs text-gray-600 uppercase font-bold">Unidades</span>
                </h3>
            </div>

            <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 border-l-4 border-l-orange-600 shadow-2xl">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1 italic">Produtos Estornados</p>
                <h3 class="text-3xl font-black text-white italic tracking-tighter font-mono font-black">
                    {{ $estornosEstoque->sum('quantity') }} <span class="text-xs text-gray-600 uppercase font-bold">Itens</span>
                </h3>
            </div>

            <div class="bg-red-600 p-8 rounded-[2.5rem] shadow-xl shadow-red-600/20 flex flex-col justify-center">
                <p class="text-[10px] font-black text-red-100 uppercase tracking-widest mb-1 italic">Perda em Comandas</p>
                <h3 class="text-3xl font-black text-white italic tracking-tighter font-mono font-black">
                    R$ {{ number_format($cancelamentos->sum('total_value'), 2, ',', '.') }}
                </h3>
            </div>
        </div>

        {{-- TABELA DE ITENS ESTORNADOS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden mb-12">
            <div class="p-8 border-b border-gray-800 bg-black/20 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-white uppercase tracking-widest italic font-mono uppercase">Log de Auditoria de Estoque (Estornos)</h2>
                <span class="text-[8px] bg-red-500/10 text-red-500 px-3 py-1 rounded-full border border-red-500/20 font-black uppercase">Segurança Ativa</span>
            </div>

            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40">
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest">Data / Hora</th>
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest">Produto</th>
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-center">Qtd</th>
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest">Operador</th>
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest">Motivo Gravado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/40">
                        @forelse($estornosEstoque as $estorno)
                            <tr class="hover:bg-red-500/[0.02] transition-colors group">
                                <td class="p-8">
                                    <span class="text-white font-black block text-xs">{{ $estorno->created_at->format('d/m/Y') }}</span>
                                    <span class="text-gray-600 text-[10px] font-bold">{{ $estorno->created_at->format('H:i:s') }}</span>
                                </td>
                                <td class="p-8 text-white font-black uppercase text-sm italic tracking-tighter">
                                    {{ $estorno->product->name }}
                                </td>
                                <td class="p-8 text-center">
                                    <span class="px-3 py-1 bg-green-950 text-green-500 text-[10px] font-black rounded-lg border border-green-500/20 font-mono">
                                        +{{ (int)$estorno->quantity }}
                                    </span>
                                </td>
                                <td class="p-8">
                                    <span class="text-red-500 font-black text-[10px] uppercase bg-red-500/10 px-3 py-1 rounded-lg border border-red-500/20 shadow-sm">
                                        {{ $estorno->user->name ?? 'Sistema' }}
                                    </span>
                                </td>
                                <td class="p-8 text-gray-400 text-xs font-medium italic">
                                    {{ $estorno->description }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-32 text-center text-gray-700 font-black uppercase italic tracking-[0.4em] text-xs opacity-30">Nenhum rastro de estorno encontrado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TABELA DE COMANDAS CANCELADAS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-gray-800 bg-black/20">
                <h2 class="text-[10px] font-black text-white uppercase tracking-widest italic font-mono uppercase">Histórico de Comandas Canceladas (Full)</h2>
            </div>
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <tbody class="divide-y divide-gray-800/40">
                        @foreach($cancelamentos as $canc)
                        <tr class="hover:bg-red-500/[0.02] transition-colors">
                            <td class="p-8">
                                <span class="text-white font-black block text-xs">{{ $canc->updated_at->format('d/m/Y H:i') }}</span>
                                <span class="text-gray-600 text-[10px] font-bold uppercase">ID Comanda #{{ $canc->id }}</span>
                            </td>
                            <td class="p-8">
                                <span class="text-gray-500 font-black text-[10px] uppercase block mb-1">Operador:</span>
                                <span class="text-white font-black uppercase text-xs">{{ $canc->user->name ?? 'N/A' }}</span>
                            </td>
                            <td class="p-8 text-right">
                                <span class="text-gray-500 font-black text-[10px] uppercase block mb-1">Valor Perdido:</span>
                                <span class="text-red-500 font-black text-xl italic font-mono">R$ {{ number_format($canc->total_value, 2, ',', '.') }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-bar-layout>
