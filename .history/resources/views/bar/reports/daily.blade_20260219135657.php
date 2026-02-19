<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- 🛰️ CABEÇALHO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-6">
            <div class="flex items-center gap-5">
                <a href="{{ route('bar.reports.index') }}"
                    class="p-3 bg-gray-900 hover:bg-gray-800 text-orange-500 rounded-2xl border border-gray-800 transition-all shadow-lg group">
                    <span class="group-hover:-translate-x-1 transition-transform inline-block">◀</span>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic leading-none">
                        Resumo <span class="text-orange-600">Diário</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">
                        Evolução de faturamento por dia de operação
                    </p>
                </div>
            </div>

            <form action="{{ route('bar.reports.daily') }}" method="GET" class="bg-gray-900 p-1 rounded-2xl border border-gray-800 shadow-xl">
                <input type="month" name="mes_referencia" value="{{ $mesReferencia }}" onchange="this.form.submit()"
                    class="bg-transparent border-none p-2 font-black text-orange-500 uppercase text-xs focus:ring-0 cursor-pointer">
            </form>
        </div>

        {{-- 📈 GRÁFICO DE BARRAS (PERFORMANCE VISUAL) --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] p-8 mb-10 shadow-2xl relative overflow-hidden">
            <div class="absolute right-0 top-0 p-10 opacity-5 text-8xl italic font-black text-white uppercase">Gráfico</div>
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-10 italic text-center md:text-left">Desempenho de Vendas no Mês Selecionado</h2>

            <div class="flex items-end justify-between gap-1 h-64 px-2 border-b border-gray-800/50 pb-2 overflow-x-auto no-scrollbar">
                @php
                    $maxValor = collect($datas)->max(fn($d) => ($d['mesas'] ?? 0) + ($d['pdv'] ?? 0)) ?: 1;
                    $diasSemana = [
                        'Sunday' => 'Dom', 'Monday' => 'Seg', 'Tuesday' => 'Ter',
                        'Wednesday' => 'Qua', 'Thursday' => 'Qui', 'Friday' => 'Sex', 'Saturday' => 'Sáb'
                    ];
                @endphp

                @foreach($datas as $dia => $valores)
                    @php
                        $totalDia = ($valores['mesas'] ?? 0) + ($valores['pdv'] ?? 0);
                        $altura = ($totalDia / $maxValor) * 100;
                        $carbonDia = \Carbon\Carbon::parse($dia);
                        $isHoje = $carbonDia->isToday();

                        // Blindagem: Só renderiza se o dia pertencer ao mês selecionado
                        $mesAtualFiltro = date('m', strtotime($mesReferencia));
                    @endphp

                    @if($carbonDia->format('m') == $mesAtualFiltro)
                    <div class="flex-1 min-w-[30px] flex flex-col items-center group relative">
                        {{-- Tooltip ao passar o mouse --}}
                        <div class="absolute -top-10 bg-orange-600 text-white text-[9px] font-black px-2 py-1 rounded shadow-xl opacity-0 group-hover:opacity-100 transition-all z-10 whitespace-nowrap">
                            R$ {{ number_format($totalDia, 2, ',', '.') }}
                        </div>

                        {{-- Barra Vertical --}}
                        <div class="w-full max-w-[22px] rounded-t-md transition-all duration-500 relative
                            {{ $isHoje ? 'bg-orange-500 shadow-[0_0_15px_rgba(234,88,12,0.4)]' : ($totalDia > 0 ? 'bg-gray-700 group-hover:bg-orange-600/50' : 'bg-gray-800/30') }}"
                            style="height: {{ max($altura, 2) }}%">
                        </div>

                        {{-- Legenda Inferior --}}
                        <span class="mt-3 text-[8px] font-black {{ $isHoje ? 'text-orange-500' : 'text-gray-600' }}">
                            {{ $carbonDia->format('d') }}
                        </span>
                        <span class="text-[6px] font-bold uppercase text-gray-700">{{ $diasSemana[$carbonDia->format('l')] }}</span>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- 📋 TABELA DETALHADA --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-gray-800 bg-black/20 flex justify-between items-center">
                <h2 class="text-[10px] font-black text-white uppercase tracking-widest italic font-mono uppercase">Detalhamento por Dia</h2>
                <div class="flex gap-4">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-500"></span><span class="text-[8px] text-gray-500 font-black uppercase tracking-widest">Mesas</span></div>
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-orange-500"></span><span class="text-[8px] text-gray-500 font-black uppercase tracking-widest">PDV / Balcão</span></div>
                </div>
            </div>

            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40">
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest uppercase">Data / Dia da Semana</th>
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Vendas Mesas</th>
                            <th class="p-8 text-[10px] font-black text-gray-500 uppercase tracking-widest text-right">Vendas PDV</th>
                            <th class="p-8 text-[10px] font-black text-white uppercase tracking-widest text-right italic font-bold">Total Diário</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/40">
                        @php
                            $totalGeralMes = 0;
                            $diasSemanaFull = [
                                'Sunday' => 'Domingo', 'Monday' => 'Segunda-feira', 'Tuesday' => 'Terça-feira',
                                'Wednesday' => 'Quarta-feira', 'Thursday' => 'Quinta-feira', 'Friday' => 'Sexta-feira', 'Saturday' => 'Sábado'
                            ];
                        @endphp

                        {{-- Inverte para mostrar o dia mais recente primeiro --}}
                        @foreach(array_reverse($datas, true) as $dia => $valores)
                            @php
                                $totalDia = ($valores['mesas'] ?? 0) + ($valores['pdv'] ?? 0);
                                $carbon = \Carbon\Carbon::parse($dia);
                                $mesAtualFiltro = date('m', strtotime($mesReferencia));
                            @endphp

                            {{-- Filtro: Só mostra se for do mês selecionado E se tiver venda (ou for hoje) --}}
                            @if($carbon->format('m') == $mesAtualFiltro && ($totalDia > 0 || $carbon->isToday()))
                                @php $totalGeralMes += $totalDia; @endphp
                                <tr class="hover:bg-white/[0.02] transition-colors group {{ $carbon->isToday() ? 'bg-orange-600/[0.03]' : '' }}">
                                    <td class="p-8">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 rounded-2xl bg-black border border-gray-800 flex flex-col items-center justify-center text-sm font-black {{ $carbon->isToday() ? 'text-orange-500' : 'text-gray-500' }} uppercase italic shadow-inner">
                                                <span>{{ $carbon->format('d') }}</span>
                                                <span class="text-[7px]">{{ $diasSemana[$carbon->format('l')] }}</span>
                                            </div>
                                            <div>
                                                <span class="text-white font-black block uppercase text-sm tracking-tighter group-hover:text-orange-500 transition-colors">
                                                    {{ $diasSemanaFull[$carbon->format('l')] }}
                                                </span>
                                                <span class="text-[10px] text-gray-600 font-bold uppercase tracking-widest">{{ $carbon->format('d/m/Y') }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-8 text-right font-bold text-blue-400/70 text-xs font-mono">
                                        R$ {{ number_format($valores['mesas'] ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td class="p-8 text-right font-bold text-orange-400/70 text-xs font-mono">
                                        R$ {{ number_format($valores['pdv'] ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td class="p-8 text-right">
                                        <span class="text-2xl font-black text-white italic tracking-tighter font-mono">
                                            R$ {{ number_format($totalDia, 2, ',', '.') }}
                                        </span>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-black/60 border-t-2 border-orange-600/50">
                            <td class="p-10 text-white font-black uppercase italic tracking-[0.2em] text-sm">Faturamento Acumulado (Mês)</td>
                            <td colspan="3" class="p-10 text-right text-4xl font-black text-orange-500 italic tracking-tighter font-mono">
                                R$ {{ number_format($totalGeralMes, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        input[type="month"] { color-scheme: dark; }
    </style>
</x-bar-layout>
