<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- CABEÇALHO E NAVEGAÇÃO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6">
            <div class="flex items-center gap-5">
                <a href="{{ route('bar.reports.index') }}"
                    class="p-3 bg-gray-900 hover:bg-gray-800 text-orange-500 rounded-2xl border border-gray-800 transition-all group shadow-lg">
                    <span class="group-hover:-translate-x-1 transition-transform inline-block">◀</span>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                        Fluxo de <span class="text-orange-600">Recebimentos</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Análise consolidada de caixa</p>
                </div>
            </div>

            {{-- FILTROS AVANÇADOS --}}
            <form action="{{ route('bar.reports.payments') }}" method="GET"
                class="flex flex-wrap items-center gap-3 bg-gray-900/50 p-2 rounded-[2rem] border border-gray-800/50">

                <input type="text" name="search" value="{{ request('search') }}" placeholder="BUSCAR MÉTODO..."
                    class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase px-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 min-w-[150px]">

                <input type="month" name="mes_referencia" value="{{ $mesReferencia }}" onchange="this.form.submit()"
                       class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase px-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer">

                <div class="relative group">
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-orange-500 z-10">📅</div>
                    <input type="date" name="date" value="{{ request('date') }}" onchange="this.form.submit()"
                        class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase pl-12 pr-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer min-w-[180px]">
                </div>

                @if (request('search') || request('mes_referencia') || request('date'))
                    <a href="{{ route('bar.reports.payments') }}" class="px-4 text-red-500 text-[9px] font-black uppercase italic">Limpar ✕</a>
                @endif
            </form>
        </div>

        {{-- DASHBOARD DE RECEITA --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">

            {{-- GRID DE CARDS LADO ESQUERDO --}}
            <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                @php $totalGeral = $pagamentos->sum('total'); @endphp

                <div class="bg-orange-600 p-8 rounded-[2.5rem] shadow-xl shadow-orange-600/20 col-span-1 md:col-span-2 relative overflow-hidden group">
                    <p class="text-[10px] font-black text-orange-200 uppercase tracking-widest mb-1">Receita Líquida Total</p>
                    <h3 class="text-5xl font-black text-white italic tracking-tighter">
                        R$ {{ number_format($totalGeral, 2, ',', '.') }}
                    </h3>
                    <span class="absolute right-[-5%] bottom-[-15%] text-9xl opacity-10 font-black italic tracking-tighter uppercase">Money</span>
                </div>

                <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Total de Transações</p>
                    <h3 class="text-3xl font-black text-white italic tracking-tighter">{{ $pagamentos->sum('qtd') }}</h3>
                </div>

                <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800">
                    <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Ticket Médio por Recebimento</p>
                    <h3 class="text-3xl font-black text-white italic tracking-tighter">
                        R$ {{ $pagamentos->sum('qtd') > 0 ? number_format($totalGeral / $pagamentos->sum('qtd'), 2, ',', '.') : '0,00' }}
                    </h3>
                </div>
            </div>

            {{-- GRÁFICO DE DISTRIBUIÇÃO LADO DIREITO --}}
            <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 flex flex-col items-center justify-center">
                <p class="text-[10px] font-black text-gray-500 uppercase tracking-widest mb-6">Mix de Pagamentos (%)</p>

                <div class="relative w-48 h-48 rounded-full border-[12px] border-black shadow-2xl flex items-center justify-center overflow-hidden">
                    {{-- Lógica Visual de Gráfico Simplificado --}}
                    @php $lastAngle = 0; @endphp
                    <svg viewBox="0 0 32 32" class="w-full h-full transform -rotate-90">
                        @foreach($pagamentos as $index => $p)
                            @php
                                $percent = ($p->total / max($totalGeral, 1)) * 100;
                                $color = ['#ea580c', '#f97316', '#fb923c', '#fdba74', '#fed7aa'][$index % 5];
                            @endphp
                            <circle r="16" cx="16" cy="16" fill="transparent"
                                stroke="{{ $color }}"
                                stroke-width="32"
                                stroke-dasharray="{{ $percent }} 100"
                                stroke-dashoffset="-{{ $lastAngle }}" />
                            @php $lastAngle += $percent; @endphp
                        @endforeach
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center bg-gray-900 w-32 h-32 m-auto rounded-full border border-gray-800">
                        <span class="text-white font-black text-xl italic tracking-tighter">MIX</span>
                    </div>
                </div>

                <div class="mt-8 w-full space-y-2">
                    @foreach($pagamentos as $index => $p)
                        <div class="flex items-center justify-between text-[9px] font-black uppercase">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full" style="background: {{ ['#ea580c', '#f97316', '#fb923c', '#fdba74', '#fed7aa'][$index % 5] }}"></span>
                                <span class="text-gray-400">{{ $p->payment_method }}</span>
                            </div>
                            <span class="text-white">{{ number_format(($p->total / max($totalGeral, 1)) * 100, 1) }}%</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- TABELA DE DETALHAMENTO --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden relative">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black/40">
                        <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest">Modalidade</th>
                        <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-center">Volume</th>
                        <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Total Acumulado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @forelse($pagamentos as $p)
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="p-8">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-black rounded-2xl flex items-center justify-center border border-gray-800 group-hover:border-orange-500 transition-all text-xl">
                                        @php
                                            $m = strtolower($p->payment_method);
                                            if(str_contains($m, 'pix')) echo '💎';
                                            elseif(str_contains($m, 'dinheiro')) echo '💵';
                                            else echo '💳';
                                        @endphp
                                    </div>
                                    <span class="text-white font-black text-sm uppercase block tracking-tight">{{ $p->payment_method }}</span>
                                </div>
                            </td>
                            <td class="p-8 text-center text-gray-400 font-black font-mono text-xl">{{ $p->qtd }}</td>
                            <td class="p-8 text-right font-black text-orange-500 italic text-3xl tracking-tighter">
                                R$ {{ number_format($p->total, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-32 text-center text-gray-600 font-black uppercase italic tracking-widest">Sem movimentações financeiras</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <style>
        input[type="date"], input[type="month"] { color-scheme: dark; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</x-bar-layout>
