<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.financeiro.dashboard') }}"
                   class="flex items-center gap-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Painel
                </a>
                <h2 class="font-black text-xl text-gray-800 dark:text-gray-200 uppercase tracking-tighter">
                    🏆 Ranking de Clientes (Fidelidade)
                </h2>
            </div>
            <button onclick="window.print()" class="print:hidden bg-black text-white px-6 py-2 rounded-full font-bold text-xs uppercase tracking-widest hover:bg-gray-800 transition-all shadow-lg">
                Gerar Relatório
            </button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="p-8 border-b border-gray-50 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50">
                    <h3 class="text-xs font-black text-indigo-600 uppercase tracking-[0.2em]">Top 15 Clientes da Arena</h3>
                    <p class="text-sm text-gray-400 font-medium">Baseado no total histórico de pagamentos confirmados.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">
                                <th class="px-8 py-5 text-center w-20">Posição</th>
                                <th class="px-6 py-5">Cliente / Contato</th>
                                <th class="px-6 py-5 text-center">Jogos</th>
                                <th class="px-8 py-5 text-right">Total Investido</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @forelse($ranking as $index => $cliente)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition-colors group">
                                    <td class="px-8 py-5">
                                        <div class="flex justify-center">
                                            @if($index == 0)
                                                <span class="flex items-center justify-center w-10 h-10 bg-amber-400 text-white rounded-full shadow-lg shadow-amber-200 font-black italic text-lg">1º</span>
                                            @elseif($index == 1)
                                                <span class="flex items-center justify-center w-9 h-9 bg-slate-300 text-white rounded-full shadow-lg shadow-slate-100 font-black italic">2º</span>
                                            @elseif($index == 2)
                                                <span class="flex items-center justify-center w-9 h-9 bg-orange-400 text-white rounded-full shadow-lg shadow-orange-100 font-black italic text-sm">3º</span>
                                            @else
                                                <span class="text-gray-400 font-black text-sm italic">{{ $index + 1 }}º</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="font-black text-gray-800 dark:text-white uppercase tracking-tight group-hover:text-indigo-600 transition-colors">
                                            {{ $cliente->client_name }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-bold tracking-wider">
                                            {{ $cliente->client_contact ?? 'Sem contato' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-center font-black text-gray-600 dark:text-gray-400 text-sm">
                                        <div class="inline-block px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full">
                                            {{ $cliente->total_reservas }} <span class="text-[9px] text-gray-400 ml-1">PARTIDAS</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-right font-mono font-black text-emerald-600 dark:text-emerald-400 text-lg">
                                        R$ {{ number_format($cliente->total_gasto, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-20 text-center text-gray-400 font-bold uppercase text-xs tracking-widest">
                                        Nenhum dado de faturamento disponível para gerar o ranking.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- DICA DE GESTÃO --}}
            <div class="mt-6 bg-indigo-600 p-6 rounded-3xl flex items-center justify-between text-white shadow-xl shadow-indigo-100 dark:shadow-none">
                <div class="flex items-center gap-4">
                    <span class="text-3xl">💡</span>
                    <div>
                        <h4 class="font-black uppercase text-sm tracking-tight">Dica de Fidelização</h4>
                        <p class="text-xs text-indigo-100">Que tal oferecer um desconto especial ou uma hora grátis para o seu Top 1?</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
