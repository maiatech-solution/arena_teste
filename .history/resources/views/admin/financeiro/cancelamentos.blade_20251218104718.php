<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.financeiro.dashboard') }}"
                   class="flex items-center gap-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition-all font-bold text-sm">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Painel
                </a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    🚫 Relatório de Perdas e Cancelamentos
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 🔍 FILTROS RÁPIDOS (AUTO-SUBMIT) --}}
            <div class="bg-white dark:bg-gray-800 p-6 shadow-sm rounded-xl border border-red-100 dark:border-red-900/30 print:hidden">
                <form id="cancelFilterForm" method="GET" action="{{ route('admin.financeiro.relatorio_cancelamentos') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Mês de Referência</label>
                        <select name="mes" onchange="document.getElementById('cancelFilterForm').submit()" class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-red-500">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $mes == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Ano</label>
                        <select name="ano" onchange="document.getElementById('cancelFilterForm').submit()" class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-red-500">
                            @foreach(range(now()->year - 1, now()->year + 1) as $a)
                                <option value="{{ $a }}" {{ $ano == $a ? 'selected' : '' }}>{{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="window.print()" class="bg-gray-800 text-white px-6 py-2 rounded-lg font-bold hover:bg-black transition w-full shadow-md">
                            🖨️ Imprimir Perdas
                        </button>
                    </div>
                </form>
            </div>

            {{-- 📄 CONTEÚDO DO RELATÓRIO --}}
            <div id="reportContent" class="bg-white dark:bg-gray-800 p-8 shadow-lg rounded-xl border border-gray-100 dark:border-gray-700 print:shadow-none print:p-0">

                <div class="flex justify-between items-start border-b-2 border-red-50 dark:border-red-900/20 pb-6 mb-8">
                    <div>
                        <h1 class="text-2xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">Auditoria de Ocorrências</h1>
                        <p class="text-gray-500 text-sm italic">Análise de receita não convertida por desistência ou falta.</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-1">Total de Ocorrências</span>
                        <span class="text-3xl font-black text-red-600">{{ $cancelamentos->count() }}</span>
                    </div>
                </div>

                {{-- TABELA DE OCORRÊNCIAS --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 text-[11px] font-bold uppercase tracking-wider">
                                <th class="p-4 rounded-l-lg">Data / Horário</th>
                                <th class="p-4">Cliente / Contato</th>
                                <th class="p-4 text-center">Tipo de Falha</th>
                                <th class="p-4 text-right rounded-r-lg">Prejuízo Estimado</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y dark:divide-gray-700">
                            @forelse($cancelamentos as $c)
                                <tr class="hover:bg-red-50/30 dark:hover:bg-red-900/10 transition duration-150">
                                    <td class="p-4 dark:text-gray-300 font-medium">
                                        {{ \Carbon\Carbon::parse($c->date)->format('d/m/Y') }}
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">{{ $c->start_time }}h</div>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-black dark:text-white text-base">{{ $c->client_name }}</div>
                                        <div class="text-xs text-indigo-500 font-mono">{{ $c->client_contact }}</div>
                                    </td>
                                    <td class="p-4 text-center">
                                        @if($c->status == 'no_show')
                                            <span class="bg-red-600 text-white px-3 py-1 rounded text-[10px] font-black uppercase shadow-sm">
                                                🚨 No-Show
                                            </span>
                                        @elseif($c->status == 'cancelled')
                                            <span class="bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300 px-3 py-1 rounded text-[10px] font-black uppercase">
                                                ✕ Cancelado
                                            </span>
                                        @else
                                            <span class="bg-amber-100 text-amber-700 px-3 py-1 rounded text-[10px] font-black uppercase">
                                                {{ $c->status }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right font-mono font-black text-red-500 text-lg">
                                        R$ {{ number_format($c->price, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-10 text-center text-gray-400 italic">
                                        Nenhuma ocorrência de cancelamento registrada neste período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- RESUMO FINAL --}}
                <div class="mt-10 p-6 bg-red-50 dark:bg-red-900/20 rounded-2xl border-2 border-dashed border-red-100 dark:border-red-800 flex justify-between items-center">
                    <div class="text-red-800 dark:text-red-400 uppercase text-xs font-black tracking-widest">
                        Perda Financeira Bruta no Período
                    </div>
                    <div class="text-3xl font-black text-red-600">
                        R$ {{ number_format($cancelamentos->sum('price'), 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('cancelFilterForm');
        form.addEventListener('change', () => {
            document.getElementById('reportContent').style.opacity = '0.3';
        });
    </script>
</x-app-layout>
