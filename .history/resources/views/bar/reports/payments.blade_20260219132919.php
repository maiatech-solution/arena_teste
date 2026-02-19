<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- CABEÇALHO E NAVEGAÇÃO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="flex items-center gap-5">
                <a href="{{ route('bar.reports.index') }}"
                    class="p-3 bg-gray-900 hover:bg-gray-800 text-orange-500 rounded-2xl border border-gray-800 transition-all shadow-lg group">
                    <span class="group-hover:-translate-x-1 transition-transform inline-block">◀</span>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                        Fluxo de <span class="text-orange-600">Recebimento</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Consolidado PDV e Comandas de Mesa</p>
                </div>
            </div>

            {{-- FILTRO DE MÊS --}}
            <form action="{{ route('bar.reports.payments') }}" method="GET" class="flex items-center gap-3 bg-gray-900/50 p-2 rounded-[2rem] border border-gray-800/50">
                <input type="month" name="mes_referencia" value="{{ $mesReferencia }}" onchange="this.form.submit()"
                       class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase px-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer">
            </form>
        </div>

        {{-- GRID DE RESUMO --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-orange-600 p-8 rounded-[2.5rem] shadow-xl shadow-orange-600/20 col-span-1 md:col-span-2 relative overflow-hidden group">
                 <p class="text-[10px] font-black text-orange-200 uppercase tracking-widest mb-1">Faturamento Bruto Consolidado</p>
                <h3 class="text-5xl font-black text-white italic tracking-tighter">
                    R$ {{ number_format($pagamentos->sum('total'), 2, ',', '.') }}
                </h3>
                <span class="absolute right-[-5%] bottom-[-15%] text-9xl opacity-10 font-black italic tracking-tighter uppercase">Cash</span>
            </div>

            <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 flex flex-col justify-center">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Total de Operações</p>
                <h3 class="text-3xl font-black text-white italic tracking-tighter">{{ $pagamentos->sum('qtd') }}</h3>
                <p class="text-[9px] text-gray-600 font-bold uppercase mt-2 italic">Mesa + Balcão</p>
            </div>
        </div>

        {{-- TABELA DE MÉTODOS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-gray-800 bg-black/20 flex justify-between items-center">
                <h2 class="text-xs font-black text-white uppercase tracking-[0.2em]">Detalhamento de Receita</h2>
                <span class="px-4 py-1 bg-green-900/20 text-green-500 text-[9px] font-black rounded-full border border-green-500/20 uppercase">Liquidado</span>
            </div>

            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black/40">
                        <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest">Modalidade / Origem</th>
                        <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-center">Volume</th>
                        <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Valor Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @forelse($pagamentos as $p)
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="p-8">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-black rounded-2xl flex items-center justify-center border border-gray-800 group-hover:border-orange-500 transition-all text-xl shadow-inner">
                                        @php
                                            $m = strtolower($p->method);
                                            if(str_contains($m, 'pix')) echo '💎';
                                            elseif(str_contains($m, 'dinheiro')) echo '💵';
                                            elseif(str_contains($m, 'mesa')) echo '🏘️';
                                            else echo '💳';
                                        @endphp
                                    </div>
                                    <div>
                                        <span class="text-white font-black text-sm uppercase block tracking-tight">{{ $p->method }}</span>
                                        <span class="text-gray-600 text-[9px] font-bold uppercase tracking-widest italic">Pagamento Confirmado</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-8 text-center text-gray-400 font-black font-mono text-lg">
                                {{ $p->qtd }}
                            </td>
                            <td class="p-8 text-right">
                                <span class="text-3xl font-black text-orange-500 italic tracking-tighter">
                                    R$ {{ number_format($p->total, 2, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-32 text-center">
                                <p class="text-gray-600 text-xs font-black uppercase tracking-[0.3em] opacity-20">Nenhuma movimentação encontrada</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-bar-layout>
