<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <a href="{{ route('bar.reports.index') }}" class="text-orange-500 text-xs font-black uppercase tracking-widest hover:underline">← Voltar</a>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter">
                    Gestão de <span class="text-orange-600">Estoque</span>
                </h1>
                <p class="text-gray-500 font-medium tracking-tight">Histórico completo de entradas, saídas e estornos.</p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('bar.products.index') }}" class="px-6 py-3 bg-gray-800 text-white text-xs font-black rounded-xl uppercase tracking-widest hover:bg-gray-700 transition">
                    Ver Inventário
                </a>
            </div>
        </div>

        {{-- TABELA DE MOVIMENTAÇÕES --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/50">
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Data/Hora</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Produto</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-center">Tipo</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-center">Qtd</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Responsável / Motivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($movimentacoes as $mov)
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="p-6">
                                    <span class="text-white font-bold text-xs block">{{ $mov->created_at->format('d/m/Y') }}</span>
                                    <span class="text-gray-500 text-[10px]">{{ $mov->created_at->format('H:i') }}</span>
                                </td>
                                <td class="p-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gray-800 rounded-lg flex items-center justify-center text-lg">📦</div>
                                        <span class="text-white font-black text-xs uppercase">{{ $mov->product->name }}</span>
                                    </div>
                                </td>
                                <td class="p-6 text-center">
                                    @if($mov->type == 'entrada')
                                        <span class="px-3 py-1 bg-green-900/30 text-green-500 text-[9px] font-black uppercase rounded-lg border border-green-500/20">
                                            ▲ Entrada
                                        </span>
                                    @else
                                        <span class="px-3 py-1 bg-red-900/30 text-red-500 text-[9px] font-black uppercase rounded-lg border border-red-500/20">
                                            ▼ Saída
                                        </span>
                                    @endif
                                </td>
                                <td class="p-6 text-center">
                                    <span class="text-sm font-mono font-black {{ $mov->type == 'entrada' ? 'text-green-500' : 'text-red-500' }}">
                                        {{ $mov->type == 'entrada' ? '+' : '' }}{{ $mov->quantity }}
                                    </span>
                                </td>
                                <td class="p-6">
                                    <span class="text-gray-300 font-bold text-[10px] uppercase block">{{ $mov->user->name ?? 'Sistema' }}</span>
                                    <span class="text-gray-500 text-[10px] italic">{{ $mov->description }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-20 text-center">
                                    <span class="text-4xl block mb-4 opacity-20">📋</span>
                                    <p class="text-gray-500 text-xs font-black uppercase tracking-widest">Nenhuma movimentação registrada.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINAÇÃO --}}
            @if($movimentacoes->hasPages())
                <div class="p-6 border-t border-gray-800 bg-black/20">
                    {{ $movimentacoes->links() }}
                </div>
            @endif
        </div>

    </div>
</x-bar-layout>
