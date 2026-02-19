<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- 🍺 HEADER --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Ranking de <span class="text-orange-600">Produtos</span>
                </h1>
                <p class="text-gray-500 font-medium italic">Análise de volume e lucratividade real.</p>
            </div>

            <div class="flex items-center gap-4">
                <form action="{{ route('bar.reports.products') }}" method="GET" class="bg-gray-900 p-1 rounded-2xl flex items-center gap-3 px-4 border border-gray-800 shadow-xl">
                    <input type="month" name="mes_referencia" value="{{ $mesReferencia }}" onchange="this.form.submit()"
                        class="bg-transparent border-none p-2 font-black text-orange-500 uppercase text-xs focus:ring-0 cursor-pointer">
                </form>
                <a href="{{ route('bar.reports.index') }}" class="p-3 bg-gray-900 text-orange-500 rounded-xl border border-gray-800 hover:bg-gray-800 transition-all">
                    ◀ VOLTAR
                </a>
            </div>
        </div>

        {{-- 🏆 CARDS DE DESTAQUE --}}
        @if($ranking->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            {{-- Campeão de Volume --}}
            <div class="bg-gray-900 p-6 rounded-[2.5rem] border border-gray-800 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 text-8xl opacity-10 group-hover:rotate-12 transition-transform">🔥</div>
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">O Mais Vendido</p>
                <h3 class="text-xl font-black text-white uppercase italic truncate">{{ $ranking->first()->product->name }}</h3>
                <p class="text-2xl font-black text-orange-500 italic">{{ number_format($ranking->first()->total_qty, 0) }} <span class="text-xs text-gray-600">un</span></p>
            </div>

            {{-- Campeão de Lucro --}}
            @php $topProfit = $ranking->sortByDesc('total_profit')->first(); @endphp
            <div class="bg-gray-900 p-6 rounded-[2.5rem] border border-gray-800 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 text-8xl opacity-10 group-hover:rotate-12 transition-transform">💰</div>
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Maior Lucro Líquido</p>
                <h3 class="text-xl font-black text-white uppercase italic truncate">{{ $topProfit->product->name }}</h3>
                <p class="text-2xl font-black text-green-500 italic text-right">R$ {{ number_format($topProfit->total_profit, 2, ',', '.') }}</p>
            </div>

            {{-- Ticket Médio do Ranking --}}
            <div class="bg-orange-600 p-6 rounded-[2.5rem] shadow-xl shadow-orange-600/20 flex flex-col justify-center">
                <p class="text-[10px] font-black text-orange-200 uppercase tracking-widest mb-1">Média de Margem</p>
                <h3 class="text-4xl font-black text-white italic">{{ number_format($ranking->avg('margin_percent'), 1) }}%</h3>
                <p class="text-[9px] text-orange-100 font-bold uppercase mt-1">Geral do período</p>
            </div>
        </div>
        @endif

        {{-- 📊 RANKING VISUAL E TABELA --}}
        <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">

            {{-- Tabela Detalhada (Lado Esquerdo) --}}
            <div class="xl:col-span-3 bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40">
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest">Item</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-center">Volume</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Faturamento</th>
                            <th class="p-6 text-[10px] font-black text-orange-500 uppercase tracking-widest text-right italic">Lucro</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-center">Margem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @foreach($ranking as $index => $item)
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="p-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black italic
                                        {{ $index == 0 ? 'bg-yellow-500/20 text-yellow-500 border border-yellow-500/50' :
                                           ($index == 1 ? 'bg-gray-400/20 text-gray-300 border border-gray-400/50' :
                                           ($index == 2 ? 'bg-orange-800/20 text-orange-700 border border-orange-800/50' : 'bg-gray-800 text-gray-600')) }}">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <span class="text-white font-black block uppercase text-sm tracking-tighter">{{ $item->product->name }}</span>
                                        <span class="text-[9px] text-gray-600 font-bold uppercase tracking-widest">{{ $item->product->category->name }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6 text-center">
                                <span class="text-lg font-black text-white italic font-mono">{{ $item->total_qty }}</span>
                            </td>
                            <td class="p-6 text-right text-gray-400 font-bold text-sm">
                                R$ {{ number_format($item->total_revenue, 2, ',', '.') }}
                            </td>
                            <td class="p-6 text-right">
                                <span class="text-lg font-black text-green-500 italic">
                                    R$ {{ number_format($item->total_profit, 2, ',', '.') }}
                                </span>
                            </td>
                            <td class="p-6 text-center">
                                @php
                                    $margin = $item->margin_percent;
                                    $color = $margin > 40 ? 'bg-green-500' : ($margin > 20 ? 'bg-blue-500' : 'bg-red-500');
                                @endphp
                                <div class="w-full max-w-[80px] mx-auto bg-gray-800 h-1.5 rounded-full overflow-hidden mb-1">
                                    <div class="{{ $color }} h-full" style="width: {{ $margin }}%"></div>
                                </div>
                                <span class="text-[10px] font-black {{ str_replace('bg-', 'text-', $color) }}">{{ number_format($margin, 1) }}%</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mix de Vendas / Gráfico Simplificado (Lado Direito) --}}
            <div class="bg-gray-900 border border-gray-800 rounded-[3rem] p-8">
                <h2 class="text-xs font-black text-white uppercase tracking-widest mb-8 italic">Mix de Vendas (Volume)</h2>

                <div class="space-y-6">
                    @php $maxQty = $ranking->max('total_qty') ?: 1; @endphp
                    @foreach($ranking->take(8) as $item)
                    <div>
                        <div class="flex justify-between items-end mb-1">
                            <span class="text-[9px] font-black text-gray-500 uppercase truncate pr-4">{{ $item->product->name }}</span>
                            <span class="text-[10px] font-black text-white italic">{{ round(($item->total_qty / $ranking->sum('total_qty')) * 100) }}%</span>
                        </div>
                        <div class="w-full bg-gray-800 h-2 rounded-full overflow-hidden">
                            <div class="bg-orange-600 h-full rounded-full transition-all duration-1000" style="width: {{ ($item->total_qty / $maxQty) * 100 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-10 p-4 bg-orange-600/10 border border-orange-600/20 rounded-2xl">
                    <p class="text-[9px] text-orange-500 font-black uppercase italic leading-tight">
                        💡 Os Top 8 produtos acima representam {{ round(($ranking->take(8)->sum('total_qty') / ($ranking->sum('total_qty') ?: 1)) * 100) }}% do seu volume total.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-bar-layout>
