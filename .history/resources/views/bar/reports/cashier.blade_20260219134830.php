<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- 🍺 HEADER COM NAVEGAÇÃO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="flex items-center gap-5">
                <a href="{{ route('bar.reports.index') }}"
                    class="p-3 bg-gray-900 hover:bg-gray-800 text-orange-500 rounded-2xl border border-gray-800 transition-all group shadow-lg">
                    <span class="group-hover:-translate-x-1 transition-transform inline-block">◀</span>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic leading-none">
                        Conferência de <span class="text-emerald-500">Turnos</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Auditoria de aberturas, fechamentos e quebras de caixa</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <form action="{{ route('bar.reports.cashier') }}" method="GET"
                    class="bg-gray-900 p-1 rounded-2xl flex items-center gap-3 px-4 border border-gray-800 shadow-2xl">
                    <input type="month" name="mes_referencia" value="{{ $mesReferencia }}"
                        onchange="this.form.submit()"
                        class="bg-transparent border-none p-2 font-black text-orange-500 uppercase text-xs focus:ring-0 cursor-pointer">
                </form>
            </div>
        </div>

        {{-- 📈 CARDS DE ACUMULADO MENSAL --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 col-span-1 md:col-span-2 relative overflow-hidden group">
                <div class="absolute -right-4 -bottom-4 text-8xl opacity-5 group-hover:scale-110 transition-transform italic font-black">CASH</div>
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Faturamento Bruto Auditado (Mês)</p>
                <h3 class="text-4xl font-black text-white italic tracking-tighter">
                    R$ {{ number_format($sessoes->sum('vendas_turno'), 2, ',', '.') }}
                </h3>
            </div>

            <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 border-l-4 border-l-red-600">
                @php
                    $quebraTotal = 0;
                    foreach($sessoes as $s) {
                        $diff = $s->closing_balance - $s->total_sistema_esperado;
                        if($s->status == 'closed' && $diff < -0.1) $quebraTotal += abs($diff);
                    }
                @endphp
                <p class="text-[10px] font-black text-red-500 uppercase tracking-widest mb-1">Quebras Acumuladas</p>
                <h3 class="text-3xl font-black text-white italic tracking-tighter">
                    - R$ {{ number_format($quebraTotal, 2, ',', '.') }}
                </h3>
            </div>

            <div class="bg-emerald-600 p-8 rounded-[2.5rem] shadow-xl shadow-emerald-600/20 flex flex-col justify-center">
                <p class="text-[10px] font-black text-emerald-100 uppercase tracking-widest mb-1">Eficiência de Caixa</p>
                <h3 class="text-3xl font-black text-white italic">
                    {{ $quebraTotal > 100 ? 'REVISAR ⚠️' : 'EXCELENTE ✅' }}
                </h3>
            </div>
        </div>

        {{-- 📟 TABELA DE AUDITORIA --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden relative">
            <div class="p-6 bg-black/20 border-b border-gray-800 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-white uppercase tracking-widest">Logs de Encerramento de Turno</h2>
                <div class="flex gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[8px] text-gray-500 font-black uppercase italic">Sincronizado</span>
                </div>
            </div>

            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40">
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest">Início / Operador</th>
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Fundo</th>
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Vendas</th>
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Esperado</th>
                            <th class="p-8 text-[10px] font-black text-white uppercase tracking-widest text-right italic">Real</th>
                            <th class="p-8 text-[10px] font-black text-orange-500 uppercase tracking-widest text-center italic">Diferença</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @forelse($sessoes as $sessao)
                            @php
                                $diferenca = 0;
                                if ($sessao->status == 'closed') {
                                    $diferenca = $sessao->closing_balance - $sessao->total_sistema_esperado;
                                }
                            @endphp
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 bg-black rounded-2xl flex items-center justify-center border border-gray-800 group-hover:border-emerald-500 transition-all text-xl shadow-inner">
                                            👤
                                        </div>
                                        <div>
                                            <span class="text-white font-black block uppercase italic tracking-tighter text-sm">
                                                {{ $sessao->user->name ?? 'N/A' }}
                                            </span>
                                            <span class="text-[10px] text-gray-600 font-bold uppercase tracking-widest">
                                                {{ $sessao->opened_at->format('d/m/Y H:i') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-8 text-right font-bold text-gray-600 text-xs">
                                    R$ {{ number_format($sessao->opening_balance, 2, ',', '.') }}
                                </td>
                                <td class="p-8 text-right font-black text-white text-sm italic">
                                    R$ {{ number_format($sessao->vendas_turno, 2, ',', '.') }}
                                </td>
                                <td class="p-8 text-right font-bold text-gray-500 text-xs italic">
                                    R$ {{ number_format($sessao->total_sistema_esperado, 2, ',', '.') }}
                                </td>
                                <td class="p-8 text-right">
                                    @if ($sessao->status == 'closed')
                                        <span class="text-xl font-black text-white italic tracking-tighter">
                                            R$ {{ number_format($sessao->closing_balance, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="px-4 py-1 bg-orange-500/10 text-orange-500 text-[9px] font-black uppercase rounded-full border border-orange-500/20 animate-pulse italic">Em Aberto</span>
                                    @endif
                                </td>
                                <td class="p-8 text-center">
                                    @if ($sessao->status == 'closed')
                                        @if (abs($diferenca) < 0.1)
                                            <span class="px-4 py-1.5 bg-emerald-500/10 text-emerald-500 text-[10px] font-black uppercase rounded-xl border border-emerald-500/20">Bateu ✅</span>
                                        @elseif($diferenca > 0)
                                            <div class="flex flex-col items-center">
                                                <span class="text-blue-400 font-black italic tracking-tighter text-lg">+ R$ {{ number_format($diferenca, 2, ',', '.') }}</span>
                                                <span class="text-[8px] text-blue-500/50 uppercase font-black">Sobra em Caixa</span>
                                            </div>
                                        @else
                                            <div class="flex flex-col items-center">
                                                <span class="text-red-500 font-black italic tracking-tighter text-lg">- R$ {{ number_format(abs($diferenca), 2, ',', '.') }}</span>
                                                <span class="text-[8px] text-red-600/50 uppercase font-black">Quebra detectada</span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-800">---</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-32 text-center">
                                    <p class="text-gray-600 text-xs font-black uppercase tracking-[0.4em] italic opacity-20">Nenhum turno registrado neste período</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 💡 LEGENDAS E AUDITORIA --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12">
            <div class="p-10 bg-gray-900/40 border border-gray-800 rounded-[3rem] relative overflow-hidden group">
                <div class="absolute right-0 top-0 p-8 opacity-5 text-6xl group-hover:scale-110 transition-transform">⚖️</div>
                <h4 class="text-emerald-500 text-[10px] font-black uppercase tracking-widest mb-4 italic">Auditoria de Precisão</h4>
                <p class="text-[11px] text-gray-500 font-bold uppercase leading-relaxed">
                    Este relatório realiza o <span class="text-white">recalculo em tempo real</span> somando todos os itens de Mesas finalizadas e Vendas Diretas do PDV. Caso o valor real informado seja diferente do esperado, o sistema sinaliza a quebra imediatamente para investigação.
                </p>
            </div>
            <div class="p-10 bg-gray-900/40 border border-gray-800 rounded-[3rem] relative overflow-hidden group">
                <div class="absolute right-0 top-0 p-8 opacity-5 text-6xl group-hover:scale-110 transition-transform">🚩</div>
                <h4 class="text-orange-500 text-[10px] font-black uppercase tracking-widest mb-4 italic">Política de Tolerância</h4>
                <p class="text-[11px] text-gray-500 font-bold uppercase leading-relaxed">
                    Diferenças de centavos são ignoradas pelo algoritmo de conferência. <span class="text-orange-400 italic font-black text-[12px]">Quebras recorrentes</span> por um mesmo operador devem ser auditadas através do relatório de cancelamentos e movimentação de estoque.
                </p>
            </div>
        </div>

    </div>
</x-bar-layout>
