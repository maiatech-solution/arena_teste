<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 p-8 shadow-lg rounded-xl">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-xl font-bold text-gray-800 dark:text-white uppercase tracking-tight">Relatório de Cancelamentos e Faltas</h1>
                    <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-bold uppercase">Competência: {{ $mes }}/{{ $ano }}</span>
                </div>

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 text-[11px] font-bold uppercase">
                            <th class="p-4 rounded-l-lg">Data do Jogo</th>
                            <th class="p-4">Cliente</th>
                            <th class="p-4">Ocorrência</th>
                            <th class="p-4 text-right rounded-r-lg">Valor da Perda</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($cancelamentos as $c)
                            <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-900/30 transition">
                                <td class="p-4 dark:text-gray-300">{{ \Carbon\Carbon::parse($c->date)->format('d/m/Y') }}</td>
                                <td class="p-4">
                                    <div class="font-bold dark:text-white">{{ $c->client_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $c->client_contact }}</div>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="px-2 py-1 rounded text-[10px] font-black uppercase {{ $c->status == 'no_show' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $c->status }}
                                    </span>
                                </td>
                                <td class="p-4 text-right font-mono font-bold text-red-500">R$ {{ number_format($c->price, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-6 text-right text-gray-500 text-sm">
                    Total de Perda Bruta no Período: <span class="font-bold text-red-600 text-lg ml-2">R$ {{ number_format($cancelamentos->sum('price'), 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
