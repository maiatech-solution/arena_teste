<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- 🍺 HEADER COM NAVEGAÇÃO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Ranking de <span class="text-orange-600">Produtos</span>
                </h1>
                <p class="text-gray-500 font-medium italic">Análise de volume de vendas e lucratividade por item.</p>
            </div>

            <div class="flex items-center gap-4">
                {{-- Filtro de Mês Rápido --}}
                <form action="{{ route('bar.reports.products') }}" method="GET" class="bg-gray-800 p-2 rounded-2xl flex items-center gap-3 px-4 border border-gray-700">
                    <input type="month" name="mes_referencia" value="{{ $mesReferencia }}" onchange="this.form.submit()"
                        class="bg-transparent border-none p-0 font-black text-orange-500 uppercase text-xs focus:ring-0 cursor-pointer">
                </form>

                <a href="{{ route('bar.reports.index') }}" class="px-6 py-3 bg-gray-800 text-gray-400 hover:text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border border-gray-700">
                    ← Voltar
                </a>
            </div>
        </div>

        {{-- 📊 TABELA DE RANKING --}}
        <div class="bg-gray-900/40 border-2 border-gray-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-black/40 border-b border-gray-800">
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest">Posição / Produto</th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-center">Qtd. Vendida</th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Faturamento</th>
                        <th class="p-6 text-[10px] font-black text-orange-500 uppercase tracking-widest text-right italic">Lucro Est.</th>
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
                                    <span class="text-white font-bold block group-hover:text-orange-500 transition-colors uppercase italic">
                                        {{ $item->product->name ?? 'Produto Removido' }}
                                    </span>
                                    <span class="text-[9px] text-gray-600 font-black uppercase tracking-widest">
                                        {{ $item->product->category->name ?? 'Sem Categoria' }}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="p-6 text-center">
                            <span class="text-xl font-black text-white italic tracking-tighter">
                                {{ number_format($item->total_qty, 0, ',', '.') }}
                            </span>
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
                            @php
                                $colorClass = 'text-red-500 bg-red-500/10';
                                if($item->margin_percent >= 40) $colorClass = 'text-green-500 bg-green-500/10';
                                elseif($item->margin_percent >= 20) $colorClass = 'text-blue-500 bg-blue-500/10';
                            @endphp
                            <div class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase italic {{ $colorClass }}">
                                {{ number_format($item->margin_percent, 1) }}%
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-20 text-center text-gray-600 uppercase font-black italic tracking-widest text-xs">
                            Nenhuma movimentação no período selecionado.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 💡 NOTA DE AUDITORIA --}}
        <div class="mt-8 flex flex-col md:flex-row gap-6">
            <div class="flex-1 bg-gray-900/60 border border-gray-800 p-6 rounded-[2rem]">
                <h4 class="text-orange-500 text-[10px] font-black uppercase tracking-widest mb-2 italic">Como é feito o cálculo?</h4>
                <p class="text-[10px] text-gray-500 font-bold uppercase leading-relaxed">
                    O <span class="text-white">Lucro Estimado</span> considera o Preço de Venda Praticado (-) o Preço de Compra (Custo) cadastrado no Produto. Se a margem estiver negativa, verifique se o preço de custo foi preenchido corretamente na Entrada de Estoque.
                </p>
            </div>
            <div class="flex-1 bg-gray-900/60 border border-gray-800 p-6 rounded-[2rem]">
                <h4 class="text-blue-500 text-[10px] font-black uppercase tracking-widest mb-2 italic">Dica de Gestão</h4>
                <p class="text-[10px] text-gray-500 font-bold uppercase leading-relaxed">
                    Produtos com <span class="text-white">Margem abaixo de 20%</span> são considerados de "baixo retorno". Tente negociar com fornecedores ou ajustar o preço de venda para manter a saúde financeira do bar.
                </p>
            </div>
        </div>
    </div>
</x-bar-layout>
