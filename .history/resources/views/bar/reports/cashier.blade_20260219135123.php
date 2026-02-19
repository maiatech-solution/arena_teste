<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- 🍺 HEADER --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Conferência de <span class="text-emerald-500">Turnos</span>
                </h1>
                <p class="text-gray-500 font-medium italic">Auditoria de aberturas, fechamentos e quebras de caixa.</p>
            </div>

            <div class="flex items-center gap-4">
                <form action="{{ route('bar.reports.cashier') }}" method="GET"
                    class="bg-gray-800 p-2 rounded-2xl flex items-center gap-3 px-4 border border-gray-700 shadow-inner">
                    <input type="month" name="mes_referencia" value="{{ $mesReferencia }}"
                        onchange="this.form.submit()"
                        class="bg-transparent border-none p-0 font-black text-orange-500 uppercase text-xs focus:ring-0 cursor-pointer">
                </form>

                <a href="{{ route('bar.reports.index') }}"
                    class="px-6 py-3 bg-gray-800 text-gray-400 hover:text-white rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border border-gray-700">
                    ← Voltar
                </a>
            </div>
        </div>

        {{-- 📟 TABELA DE AUDITORIA --}}
        <div class="bg-gray-900/40 border-2 border-gray-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black/40 border-b border-gray-800">
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest">Início / Operador
                        </th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Fundo
                            Abertura</th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Vendas
                            do Turno</th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Total
                            Esperado</th>
                        <th class="p-6 text-[10px] font-black text-white uppercase tracking-widest text-right italic">
                            Fechamento Real</th>
                        <th
                            class="p-6 text-[10px] font-black text-orange-500 uppercase tracking-widest text-center italic">
                            Diferença</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @forelse($sessoes as $sessao)
                        @php
                            $diferenca = 0;
                            if ($sessao->status == 'closed') {
                                // Confronto: O que você contou vs O que o sistema somou (Mesas + PDV)
                                $diferenca = $sessao->closing_balance - $sessao->total_sistema_esperado;
                            }
                        @endphp
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="p-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center text-xl">
                                        👤
                                    </div>
                                    <div>
                                        <span
                                            class="text-white font-bold block uppercase italic tracking-tight">{{ $sessao->user->name ?? 'N/A' }}</span>
                                        <span class="text-[9px] text-gray-600 font-black uppercase tracking-widest">
                                            {{ $sessao->opened_at->format('d/m/Y H:i') }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6 text-right font-bold text-gray-500">
                                R$ {{ number_format($sessao->opening_balance, 2, ',', '.') }}
                            </td>
                            <td class="p-6 text-right font-bold text-blue-400">
                                R$ {{ number_format($sessao->vendas_turno, 2, ',', '.') }}
                            </td>
                            <td class="p-6 text-right font-bold text-gray-300">
                                {{-- 💡 ALTERAÇÃO AQUI: Mostra o esperado REAL (Físico + Digital) --}}
                                R$ {{ number_format($sessao->total_sistema_esperado, 2, ',', '.') }}
                            </td>
                            <td class="p-6 text-right">
                                @if ($sessao->status == 'closed')
                                    <span class="text-lg font-black text-white italic tracking-tighter">
                                        R$ {{ number_format($sessao->closing_balance, 2, ',', '.') }}
                                    </span>
                                @else
                                    <span
                                        class="text-[9px] font-black text-orange-500 uppercase animate-pulse italic">Aberto</span>
                                @endif
                            </td>
                            <td class="p-6 text-center">
                                @if ($sessao->status == 'closed')
                                    @if (abs($diferenca) < 0.1)
                                        <span
                                            class="px-3 py-1 bg-emerald-500/10 text-emerald-500 text-[9px] font-black uppercase rounded-full border border-emerald-500/20 shadow-sm">Bateu
                                            ✅</span>
                                    @elseif($diferenca > 0)
                                        <div class="flex flex-col items-center">
                                            <span class="text-blue-400 font-black italic tracking-tighter">+ R$
                                                {{ number_format($diferenca, 2, ',', '.') }}</span>
                                            <span class="text-[8px] text-blue-500/50 uppercase font-black">Sobra</span>
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center">
                                            <span class="text-red-500 font-black italic tracking-tighter">- R$
                                                {{ number_format(abs($diferenca), 2, ',', '.') }}</span>
                                            <span class="text-[8px] text-red-600/50 uppercase font-black">Quebra
                                                (Falta)</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-gray-700 text-xs">---</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6"
                                class="p-20 text-center text-gray-600 uppercase font-black italic tracking-widest text-xs">
                                Nenhum turno registrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- 💡 LEGENDAS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-10">
            <div class="p-8 bg-gray-900/60 border border-gray-800 rounded-[2rem]">
                <h4 class="text-emerald-500 text-[10px] font-black uppercase tracking-widest mb-3 italic">Auditoria
                    Inteligente</h4>
                <p class="text-[10px] text-gray-500 font-bold uppercase leading-relaxed">
                    Este relatório ignora falhas de gravação no banco e recalcula o <span class="text-white">Total
                        Esperado</span> somando Mesas, PDV e Fundo de Reserva. Por isso, ele é mais preciso que o
                    fechamento instantâneo.
                </p>
            </div>
            <div class="p-8 bg-gray-900/60 border border-gray-800 rounded-[2rem]">
                <h4 class="text-orange-500 text-[10px] font-black uppercase tracking-widest mb-3 italic">Conferência
                    Real</h4>
                <p class="text-[10px] text-gray-500 font-bold uppercase leading-relaxed">
                    Se a diferença for de centavos, o sistema marca como <span class="text-emerald-500">"Bateu"</span>.
                    Diferenças maiores indicam que o operador esqueceu de registrar uma venda ou deu troco errado.
                </p>
            </div>
        </div>

    </div>
</x-bar-layout>
