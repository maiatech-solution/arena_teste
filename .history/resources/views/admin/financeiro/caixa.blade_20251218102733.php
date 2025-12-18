<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                🏦 Auditoria de Movimentação de Caixa
            </h2>
            <a href="{{ route('admin.financeiro.dashboard') }}" class="text-sm text-indigo-600 hover:underline">
                ← Voltar ao Hub
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 🔍 FILTRO INSTANTÂNEO --}}
            <div class="bg-white dark:bg-gray-800 p-6 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 print:hidden">
                <form id="caixaFilterForm" method="GET" action="{{ route('admin.financeiro.relatorio_caixa') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label for="dataInput" class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wider">
                            📅 Escolha a Data (O sistema atualizará sozinho)
                        </label>
                        <input type="date"
                               name="data"
                               id="dataInput"
                               value="{{ $data }}"
                               onchange="document.getElementById('caixaFilterForm').submit()"
                               class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer transition-all">
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('admin.financeiro.relatorio_caixa', ['data' => now()->format('Y-m-d')]) }}"
                           class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-6 py-2 rounded-lg font-bold hover:bg-indigo-600 hover:text-white transition-all text-sm flex items-center">
                            Ir para Hoje
                        </a>

                        <button type="button" onclick="window.print()" class="bg-emerald-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-emerald-700 transition flex items-center gap-2 text-sm shadow-sm">
                            <span>🖨️</span> Imprimir
                        </button>
                    </div>
                </form>
            </div>

            {{-- 📄 RELATÓRIO --}}
            <div id="reportContent" class="bg-white dark:bg-gray-800 p-8 shadow-lg rounded-xl print:shadow-none print:p-0 border border-gray-100 dark:border-gray-700">

                <div class="flex justify-between items-start border-b-2 border-gray-100 dark:border-gray-700 pb-6 mb-8">
                    <div>
                        <h1 class="text-3xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">
                            Relatório de Caixa
                        </h1>
                        <p class="text-gray-500 text-sm mt-1">
                            Competência: <span class="font-bold text-gray-800 dark:text-gray-200">{{ \Carbon\Carbon::parse($data)->translatedFormat('l, d \d\e F \d\e Y') }}</span>
                        </p>
                    </div>

                    @php
                        $caixaStatus = \App\Models\Cashier::where('date', $data)->first();
                    @endphp
                    <div class="text-right">
                        @if($caixaStatus && $caixaStatus->status == 'closed')
                            <span class="bg-green-100 text-green-700 px-4 py-1 rounded-full text-xs font-black uppercase border border-green-200">
                                ✅ Caixa Fechado
                            </span>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-4 py-1 rounded-full text-xs font-black uppercase border border-amber-200">
                                🔓 Caixa em Aberto
                            </span>
                        @endif
                        <p class="text-[10px] text-gray-400 mt-2 italic font-mono uppercase tracking-tighter">Gerado: {{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                {{-- RESUMOS --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <div class="space-y-3">
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                            Distribuição por Método
                        </h3>
                        <div class="bg-gray-50 dark:bg-gray-900/40 p-4 rounded-xl space-y-2 border border-gray-100 dark:border-gray-700">
                            @forelse($movimentacoes->groupBy('payment_method') as $metodo => $transacoes)
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500 uppercase font-bold">{{ $metodo }}</span>
                                    <span class="font-mono font-bold dark:text-white text-lg">R$ {{ number_format($transacoes->sum('amount'), 2, ',', '.') }}</span>
                                </div>
                            @empty
                                <p class="text-gray-400 text-xs italic">Sem movimentações hoje.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-indigo-600 dark:bg-indigo-900 p-8 rounded-2xl flex flex-col justify-center items-center shadow-inner text-white relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full"></div>
                        <span class="text-xs font-bold uppercase opacity-80 tracking-widest">Saldo Total Líquido</span>
                        <span class="text-4xl font-black mt-2">R$ {{ number_format($movimentacoes->sum('amount'), 2, ',', '.') }}</span>
                    </div>
                </div>

                {{-- TABELA --}}
                <div class="mb-12 overflow-x-auto">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                        Cronológico de Lançamentos
                    </h3>
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-gray-400 uppercase text-[10px] font-bold border-b border-gray-100 dark:border-gray-700">
                                <th class="py-3 px-2">Hora</th>
                                <th class="py-3 px-2">Identificação / Cliente</th>
                                <th class="py-3 px-2 text-center">Método</th>
                                <th class="py-3 px-2 text-right">Valor Líquido</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @foreach($movimentacoes as $m)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition duration-150">
                                    <td class="py-3 px-2 text-gray-400 font-mono">{{ $m->paid_at->format('H:i') }}</td>
                                    <td class="py-3 px-2">
                                        <div class="font-bold dark:text-gray-200">#{{ $m->reserva_id }}</div>
                                        <div class="text-[11px] text-gray-500">{{ $m->reserva->client_name ?? 'Lançamento Manual' }}</div>
                                    </td>
                                    <td class="py-3 px-2 text-center uppercase">
                                        <span class="text-[9px] px-2 py-0.5 rounded border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 font-bold tracking-tight">
                                            {{ $m->payment_method }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 text-right font-mono font-bold {{ $m->amount < 0 ? 'text-red-500' : 'text-gray-800 dark:text-white' }}">
                                        R$ {{ number_format($m->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ASSINATURAS --}}
                <div class="mt-20 grid grid-cols-2 gap-16 text-center">
                    <div class="space-y-2">
                        <div class="border-b border-gray-400 dark:border-gray-600 w-full h-8"></div>
                        <p class="text-[10px] uppercase font-black text-gray-400 tracking-tighter">Operador Responsável</p>
                        @if($caixaStatus && $caixaStatus->closed_by_user_id)
                             <p class="text-[9px] text-indigo-500 font-bold italic uppercase tracking-widest">
                                Conferido por: {{ \App\Models\User::find($caixaStatus->closed_by_user_id)->name }}
                             </p>
                        @endif
                    </div>
                    <div class="space-y-2">
                        <div class="border-b border-gray-400 dark:border-gray-600 w-full h-8"></div>
                        <p class="text-[10px] uppercase font-black text-gray-400 tracking-tighter">Conferência Gerencial</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Script para Feedback Visual ao trocar data --}}
    <script>
        document.getElementById('dataInput').addEventListener('change', function() {
            document.getElementById('reportContent').style.opacity = '0.3';
            document.getElementById('caixaFilterForm').style.pointerEvents = 'none';
        });
    </script>
</x-app-layout>
