<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        {{-- Header com Filtro --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Ranking de <span class="text-orange-600">Produtos</span>
                </h1>
                <p class="text-gray-500 font-medium italic">Produtos mais vendidos e margem de contribuição.</p>
            </div>

            <a href="{{ route('bar.reports.index') }}" class="px-6 py-3 bg-gray-800 text-gray-400 hover:text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border border-gray-700">
                ← Painel Geral
            </a>
        </div>

        {{-- Tabela de Desempenho --}}
        <div class="bg-gray-900/40 border-2 border-gray-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-black/40 border-b border-gray-800">
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest">Posição / Produto</th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-center">Qtd. Vendida</th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Faturamento</th>
                        <th class="p-6 text-[10px] font-black text-orange-500 uppercase tracking-widest text-right italic">Lucro Estimado</th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-center">Margem %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @forelse($ranking as $index => $item)
                    <tr class="hover:bg-white/[0.02] transition-colors group">
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <span class="text-lg font-black {{ $index < 3 ? 'text-orange-600' : 'text-gray-700' }} italic">
                                    #{{ $index + 1 }}
                                </span>
                                <div>
                                    <span class="text-white font-bold block group-hover:text-orange-500 transition-colors uppercase italic">{{ $item->product_name }}</span>
                                    <span class="text-[9px] text-gray-600 font-black uppercase tracking-widest">Ref: {{ $item->bar_product_id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            <span class="text-xl font-black text-white italic tracking-tighter">{{ number_format($item->total_qty, 0, ',', '.') }}</span>
                        </td>
                        <td class="p-6 text-right font-bold text-gray-300">
                            R$ {{ number_format($item->total_revenue, 2, ',', '.') }}
                        </td>
                        <td class="p-6 text-right">
                            <span class="text-lg font-black text-green-500 italic tracking-tighter">
                                R$ {{ number_format($item->total_profit, 2, ',', '.') }}
                            </span>
                        </td>
                        <td class="p-6 text-center">
                            <div class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase italic 
                                {{ $item->margin_percent > 40 ? 'bg-green-500/10 text-green-500' : ($item->margin_percent > 20 ? 'bg-blue-500/10 text-blue-500' : 'bg-red-500/10 text-red-500') }}">
                                {{ number_format($item->margin_percent, 1) }}%
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-20 text-center">
                            <span class="text-4xl block mb-4">🧊</span>
                            <span class="text-gray-500 font-black uppercase italic tracking-widest text-xs">Nenhuma venda registrada no período.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer Informativo --}}
        <div class="mt-8 bg-orange-600/5 border border-orange-600/10 p-6 rounded-[2rem] flex items-center gap-4">
            <span class="text-2xl">💡</span>
            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest leading-relaxed">
                O <span class="text-orange-600">Lucro Estimado</span> é calculado subtraindo o preço de custo (entrada de estoque) do valor total vendido. 
                Certifique-se de manter os preços de custo atualizados no cadastro de produtos.
            </p>
        </div>
    </div>
</x-bar-layout>