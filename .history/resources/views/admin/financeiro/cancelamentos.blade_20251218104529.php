<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.financeiro.dashboard') }}"
                   class="flex items-center gap-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Painel
                </a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    💰 Relatório de Faturamento Detalhado
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 🔍 FILTROS COM AUTO-SUBMIT --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 print:hidden">
                <form id="fatFilterForm" method="GET" action="{{ route('admin.financeiro.relatorio_faturamento') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Início</label>
                        <input type="date" name="data_inicio" id="start_date" value="{{ $dataInicio->format('Y-m-d') }}"
                               onchange="document.getElementById('fatFilterForm').submit()"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Fim</label>
                        <input type="date" name="data_fim" id="end_date" value="{{ $dataFim->format('Y-m-d') }}"
                               onchange="document.getElementById('fatFilterForm').submit()"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300">
                    </div>
                    <div class="md:col-span-2 flex space-x-2">
                        <button type="button" onclick="window.print()" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-md font-bold hover:bg-gray-200 transition flex-1">
                            🖨️ Imprimir
                        </button>
                    </div>
                </form>
            </div>

            {{-- 📊 CARDS DE RESUMO --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-indigo-600 p-6 rounded-xl shadow-lg text-white">
                    <p class="text-xs opacity-80 font-bold uppercase tracking-widest">Faturamento Total</p>
                    <p class="text-2xl font-black mt-1">R$ {{ number_format($faturamentoTotal, 2, ',', '.') }}</p>
                </div>
                @foreach($totaisPorMetodo as $metodo => $valor)
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-b-4 border-emerald-500 shadow-sm">
                    <p class="text-xs text-gray-500 font-bold uppercase">{{ $metodo }}</p>
                    <p class="text-2xl font-black text-gray-800 dark:text-white mt-1">R$ {{ number_format($valor, 2, ',', '.') }}</p>
                </div>
                @endforeach
            </div>

            {{-- 📄 TABELA DETALHADA --}}
            <div id="reportContent" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-xl overflow-hidden border border-gray-100 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Pagamento</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Horário Jogo</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Reserva / Cliente</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($transacoes as $t)
                        <tr class="text-sm hover:bg-gray-50 dark:hover:bg-gray-900/50 transition">
                            <td class="px-6 py-4 dark:text-gray-400 font-mono text-xs">
                                {{ $t->paid_at->format('d/m/Y') }}<br>{{ $t->paid_at->format('H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($t->reserva)
                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold">
                                        {{ \Carbon\Carbon::parse($t->reserva->start_time)->format('H:i') }}h
                                    </span>
                                @else
                                    <span class="text-gray-300">---</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold dark:text-gray-200">#{{ $t->reserva_id }}</div>
                                <div class="text-xs text-gray-500">{{ $t->reserva->client_name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($t->type == 'signal')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-blue-100 text-blue-700">Sinal</span>
                                @elseif($t->type == 'full_payment' || $t->type == 'payment_settlement')
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">Acerto</span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-gray-100 text-gray-600">{{ $t->type }}</span>
                                @endif
                                <div class="text-[10px] mt-1 text-gray-400 font-bold uppercase">{{ $t->payment_method }}</div>
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold {{ $t->amount < 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                R$ {{ number_format($t->amount, 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Feedback visual ao filtrar
        const form = document.getElementById('fatFilterForm');
        form.addEventListener('change', () => {
            document.getElementById('reportContent').style.opacity = '0.3';
        });
    </script>
</x-app-layout>
