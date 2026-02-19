<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- CABEÇALHO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6">
            <div class="flex items-center gap-5">
                <a href="{{ route('bar.reports.index') }}"
                    class="p-3 bg-gray-900 hover:bg-gray-800 text-orange-500 rounded-2xl border border-gray-800 transition-all group shadow-lg">
                    <span class="group-hover:-translate-x-1 transition-transform inline-block">◀</span>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                        Fluxo de <span class="text-orange-600">Pagamentos</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Resumo mensal por modalidade de recebimento</p>
                </div>
            </div>

            {{-- FILTRO DE MÊS --}}
            <form action="{{ route('bar.reports.payments') }}" method="GET" class="flex items-center gap-3 bg-gray-900/50 p-2 rounded-[2rem] border border-gray-800/50">
                <input type="month" name="mes_referencia" value="{{ $mesReferencia }}" onchange="this.form.submit()"
                       class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase px-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer appearance-none">
            </form>
        </div>

        {{-- CARDS DE RESUMO FINANCEIRO --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            @php $totalGeral = $pagamentos->sum('total'); @endphp

            <div class="bg-orange-600 p-6 rounded-[2rem] shadow-lg shadow-orange-600/20 col-span-1 md:col-span-2">
                <p class="text-[10px] font-black text-orange-200 uppercase tracking-widest mb-1">Faturamento Total (Período)</p>
                <h3 class="text-4xl font-black text-white italic tracking-tighter">
                    R$ {{ number_format($totalGeral, 2, ',', '.') }}
                </h3>
            </div>

            <div class="bg-gray-900 p-6 rounded-[2rem] border border-gray-800">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Nº de Transações</p>
                <h3 class="text-2xl font-black text-white italic tracking-tighter">{{ $pagamentos->sum('qtd') }}</h3>
            </div>

            <div class="bg-gray-900 p-6 rounded-[2rem] border border-gray-800">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Ticket Médio</p>
                <h3 class="text-2xl font-black text-white italic tracking-tighter">
                    R$ {{ $pagamentos->sum('qtd') > 0 ? number_format($totalGeral / $pagamentos->sum('qtd'), 2, ',', '.') : '0,00' }}
                </h3>
            </div>
        </div>

        {{-- TABELA DE MÉTODOS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden relative">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black/40">
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">Meio de Pagamento</th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-center">Transações</th>
                        <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] text-right">Total Recebido</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @forelse($pagamentos as $p)
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="p-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-black rounded-xl flex items-center justify-center border border-gray-800 group-hover:border-orange-500 transition-colors">
                                        @if(strtolower($p->payment_method) == 'pix') 💎
                                        @elseif(strtolower($p->payment_method) == 'dinheiro') 💵
                                        @else 💳 @endif
                                    </div>
                                    <span class="text-white font-black text-xs uppercase">{{ $p->payment_method }}</span>
                                </div>
                            </td>
                            <td class="p-6 text-center text-gray-400 font-black font-mono">
                                {{ $p->qtd }}
                            </td>
                            <td class="p-6 text-right">
                                <span class="text-lg font-black text-orange-500 italic">
                                    R$ {{ number_format($p->total, 2, ',', '.') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-24 text-center text-gray-600 font-black uppercase tracking-widest italic opacity-20">
                                Sem registros para este mês
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-bar-layout>
