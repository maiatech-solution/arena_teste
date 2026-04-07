<x-bar-layout>
    <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Ranking de <span class="text-orange-600">Operadores</span>
                </h1>
                <p class="text-gray-500 font-medium italic">Desempenho individual por faturamento líquido.</p>
            </div>
            <a href="{{ route('bar.reports.index', ['mes_referencia' => $mesReferencia]) }}" class="text-orange-500 font-black uppercase text-xs border-b-2 border-orange-500/20 hover:border-orange-500 transition-all">◀ Voltar</a>
        </div>

        <div class="bg-gray-900 rounded-[3rem] border border-gray-800 overflow-hidden shadow-2xl">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-500 text-[10px] font-black uppercase tracking-widest border-b border-gray-800 bg-black/20">
                        <th class="p-6">Operador</th>
                        <th class="p-6 text-center">Vendas Realizadas</th>
                        <th class="p-6 text-right">Faturamento Líquido</th>
                        <th class="p-6 text-right text-orange-500">Comissão Sugerida (5%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @foreach($vendasPorOperador as $rank)
                    <tr class="hover:bg-white/[0.02] transition-colors">
                        <td class="p-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-gray-800 rounded-2xl flex items-center justify-center font-black text-orange-500 border border-gray-700">
                                    {{ substr($rank->user->name, 0, 1) }}
                                </div>
                                <span class="text-white font-black uppercase italic text-sm">{{ $rank->user->name }}</span>
                            </div>
                        </td>
                        <td class="p-6 text-center text-white font-mono font-bold">{{ $rank->qtd_vendas }}</td>
                        <td class="p-6 text-right text-white font-black text-xl italic font-mono">
                            R$ {{ number_format($rank->faturamento_liquido, 2, ',', '.') }}
                        </td>
                        <td class="p-6 text-right text-orange-500 font-black text-lg italic font-mono">
                            R$ {{ number_format($rank->faturamento_liquido * 0.05, 2, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-bar-layout>
