<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- 🛰️ HEADER --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6">
            <div class="flex items-center gap-5">
                <a href="{{ route('bar.reports.index') }}"
                    class="p-3 bg-gray-900 hover:bg-gray-800 text-orange-500 rounded-2xl border border-gray-800 transition-all shadow-lg">
                    <span class="inline-block">◀</span>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic leading-none">
                        Resumo <span class="text-orange-600">Diário</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Evolução de faturamento por dia</p>
                </div>
            </div>

            <form action="{{ route('bar.reports.daily') }}" method="GET">
                <input type="month" name="mes_referencia" value="{{ $mesReferencia }}" onchange="this.form.submit()"
                    class="bg-black border-none rounded-2xl text-white text-[10px] font-black uppercase px-6 py-3 outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer">
            </form>
        </div>

        {{-- 📈 GRÁFICO DE BARRAS (VISUAL) --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] p-8 mb-10 shadow-2xl relative overflow-hidden">
            <div class="absolute right-0 top-0 p-10 opacity-5 text-8xl italic font-black text-white">CHART</div>
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-10 italic">Performance de Vendas no Mês</h2>

            <div class="flex items-end justify-between gap-2 h-64 px-4">
                @php
                    $maxValor = collect($datas)->max(fn($d) => ($d['mesas'] ?? 0) + ($d['pdv'] ?? 0)) ?: 1;
                @endphp

                @foreach($datas as $dia => $valores)
                    @php
                        $totalDia = ($valores['mesas'] ?? 0) + ($valores['pdv'] ?? 0);
                        $altura = ($totalDia / $maxValor) * 100;
                        $diaNum = date('d', strtotime($dia));
                        $isHoje = $dia == date('Y-m-d');
                    @endphp
                    <div class="flex-1 flex flex-col items-center group relative">
                        {{-- Tooltip ao passar o mouse --}}
                        <div class="absolute -top-12 bg-orange-600 text-white text-[10px] font-black px-3 py-1 rounded-lg opacity-0 group-hover:opacity-100 transition-all pointer-events-none z-20 whitespace-nowrap shadow-xl">
                            R$ {{ number_format($totalDia, 2, ',', '.') }}
                        </div>

                        {{-- Barra --}}
                        <div class="w-full max-w-[40px] rounded-t-xl transition-all duration-500 relative
                            {{ $isHoje ? 'bg-orange-500 shadow-[0_0_20px_rgba(234,88,12,0.4)]' : 'bg-gray-800 group-hover:bg-orange-600/50' }}"
                            style="height: {{ max($altura, 5) }}%">
                        </div>

                        <span class="mt-4 text-[9px] font-black {{ $isHoje ? 'text-orange-500' : 'text-gray-600' }}">
                            {{ $diaNum }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 📋 TABELA DETALHADA --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-gray-800 bg-black/20 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-white uppercase tracking-widest italic">Detalhamento Financeiro por Dia</h2>
                <div class="flex gap-6">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span class="text-[9px] text-gray-500 font-black uppercase">Mesas</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                        <span class="text-[9px] text-gray-500 font-black uppercase">PDV</span>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40">
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest">Data / Dia da Semana</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Faturamento Mesas</th>
                            <th class="p-6 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Faturamento PDV</th>
                            <th class="p-6 text-[10px] font-black text-white uppercase tracking-widest text-right italic">Total Diário</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/40">
                        @php $totalGeral = 0; @endphp
                        @foreach(array_reverse($datas, true) as $dia => $valores)
                            @php
                                $totalDia = ($valores['mesas'] ?? 0) + ($valores['pdv'] ?? 0);
                                $totalGeral += $totalDia;
                                $dataCarbon = \Carbon\Carbon::parse($dia);
                            @endphp
                            <tr class="hover:bg-white/[0.02] transition-colors group {{ $dataCarbon->isToday() ? 'bg-orange-600/[0.03]' : '' }}">
                                <td class="p-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-black border border-gray-800 flex items-center justify-center text-xs font-black text-orange-500 uppercase italic">
                                            {{ $dataCarbon->format('d') }}
                                        </div>
                                        <div>
                                            <span class="text-white font-black block uppercase text-sm tracking-tighter">
                                                {{ $dataCarbon->translatedFormat('l') }}
                                            </span>
                                            <span class="text-[10px] text-gray-600 font-bold uppercase">{{ $dataCarbon->format('d/m/Y') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-6 text-right font-bold text-blue-400/80 text-xs">
                                    R$ {{ number_format($valores['mesas'] ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="p-6 text-right font-bold text-orange-400/80 text-xs">
                                    R$ {{ number_format($valores['pdv'] ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="p-6 text-right">
                                    <span class="text-lg font-black text-white italic tracking-tighter">
                                        R$ {{ number_format($totalDia, 2, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-black/60 border-t-2 border-orange-600/50">
                            <td class="p-8 text-white font-black uppercase italic tracking-widest">Total Acumulado</td>
                            <td colspan="3" class="p-8 text-right text-3xl font-black text-orange-500 italic tracking-tighter">
                                R$ {{ number_format($totalGeral, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-bar-layout>
