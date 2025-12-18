<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- BOTÃO VOLTAR --}}
                <a href="{{ route('admin.financeiro.dashboard') }}"
                   class="flex items-center gap-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Painel
                </a>
                <h2 class="font-black text-xl text-gray-800 dark:text-gray-200 uppercase tracking-tighter">
                    🗓️ Mapa de Ocupação & Histórico
                </h2>
            </div>
            <button onclick="window.print()" class="print:hidden bg-black text-white px-6 py-2 rounded-full font-bold text-xs uppercase tracking-widest hover:bg-gray-800 transition-all shadow-lg">
                🖨️ Imprimir Mapa
            </button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 🔍 FILTRO DE BUSCA AVANÇADA (AUTO-SUBMIT) --}}
            <div class="bg-white dark:bg-gray-800 p-4 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 print:hidden">
                <form id="ocupacaoFilterForm" method="GET" action="{{ route('admin.financeiro.relatorio_ocupacao') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase ml-2">Data Inicial:</label>
                        <input type="date" name="data_inicio" id="data_inicio" value="{{ $dataInicio->format('Y-m-d') }}"
                               onchange="document.getElementById('ocupacaoFilterForm').submit()"
                               class="w-full rounded-2xl border-none bg-gray-100 dark:bg-gray-900 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 font-bold">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-400 uppercase ml-2">Data Final:</label>
                        <input type="date" name="data_fim" id="data_fim" value="{{ $dataFim->format('Y-m-d') }}"
                               onchange="document.getElementById('ocupacaoFilterForm').submit()"
                               class="w-full rounded-2xl border-none bg-gray-100 dark:bg-gray-900 dark:text-gray-300 focus:ring-2 focus:ring-indigo-500 font-bold">
                    </div>
                    <div class="flex items-end">
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 w-full p-3 rounded-2xl text-center border border-indigo-100 dark:border-indigo-800">
                            <span class="text-[10px] font-black text-indigo-600 dark:text-indigo-400 uppercase">Total de Reservas:</span>
                            <span class="ml-2 font-black text-indigo-900 dark:text-white">{{ $reservas->count() }} Jogos</span>
                        </div>
                    </div>
                </form>
            </div>

            {{-- 📄 TIMELINE DE JOGOS --}}
            <div id="reportContent" class="space-y-8 transition-opacity duration-300">
                @forelse($reservas->groupBy('date') as $data => $lista)
                    <div class="relative break-inside-avoid">

                        {{-- IDENTIFICADOR DO DIA --}}
                        <div class="sticky top-0 z-10 flex items-center gap-4 mb-4 py-2 bg-gray-50/80 dark:bg-gray-900/80 backdrop-blur-md">
                            <div class="bg-white dark:bg-gray-800 px-4 py-2 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 text-center min-w-[70px]">
                                <span class="block text-2xl font-black text-gray-800 dark:text-white leading-none">{{ \Carbon\Carbon::parse($data)->format('d') }}</span>
                                <span class="text-[10px] font-black text-indigo-600 uppercase">{{ \Carbon\Carbon::parse($data)->translatedFormat('M') }}</span>
                            </div>
                            <div>
                                <h3 class="font-black text-gray-800 dark:text-white uppercase tracking-tight text-lg">
                                    {{ \Carbon\Carbon::parse($data)->translatedFormat('l') }}
                                </h3>
                                <p class="text-[10px] text-gray-400 font-bold uppercase">{{ $lista->count() }} Reservas Confirmadas</p>
                            </div>
                        </div>

                        {{-- GRID DE JOGOS DO DIA --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pl-4 border-l-2 border-gray-200 dark:border-gray-700 ml-8">
                            @foreach($lista as $r)
                                <div class="bg-white dark:bg-gray-800 p-4 rounded-3xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">

                                    {{-- STATUS DE PAGAMENTO --}}
                                    <div class="absolute top-0 right-0">
                                        @php $totalPago = $r->total_paid ?? 0; @endphp
                                        @if($totalPago >= $r->price)
                                            <span class="bg-emerald-500 text-white text-[8px] font-black px-3 py-1 rounded-bl-xl uppercase tracking-widest shadow-sm">Pago</span>
                                        @elseif($totalPago > 0)
                                            <span class="bg-blue-500 text-white text-[8px] font-black px-3 py-1 rounded-bl-xl uppercase tracking-widest shadow-sm">Parcial</span>
                                        @else
                                            <span class="bg-amber-500 text-white text-[8px] font-black px-3 py-1 rounded-bl-xl uppercase tracking-widest shadow-sm">Pendente</span>
                                        @endif
                                    </div>

                                    {{-- INFO DO JOGO --}}
                                    <div class="text-xs font-black text-indigo-600 mb-1 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ \Carbon\Carbon::parse($r->start_time)->format('H:i') }}h
                                    </div>

                                    <div class="font-bold text-gray-800 dark:text-white truncate pr-6 uppercase text-sm leading-tight mb-4">
                                        {{ $r->client_name }}
                                    </div>

                                    <div class="flex items-center justify-between border-t border-gray-50 dark:border-gray-700 pt-3">
                                        <div class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">
                                            Quadra: <span class="text-indigo-500 dark:text-indigo-400 italic">{{ $r->court_id ?? '1' }}</span>
                                        </div>
                                        <div class="text-[10px] font-mono font-black text-gray-700 dark:text-gray-300">
                                            R$ {{ number_format($r->price, 2, ',', '.') }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-32 bg-white dark:bg-gray-800 rounded-[3rem] border-2 border-dashed border-gray-200 dark:border-gray-700">
                        <div class="text-5xl mb-4">🏜️</div>
                        <p class="text-gray-400 font-black uppercase tracking-widest text-xs italic">
                            Nenhuma reserva encontrada para este período.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- SCRIPT PARA FEEDBACK VISUAL NO FILTRO --}}
    <script>
        document.querySelectorAll('input[type="date"]').forEach(input => {
            input.addEventListener('change', () => {
                document.getElementById('reportContent').style.opacity = '0.3';
            });
        });
    </script>
</x-app-layout>
