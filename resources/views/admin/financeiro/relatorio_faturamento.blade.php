<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            💰 Relatório de Faturamento Detalhado
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <form method="GET" action="{{ route('admin.financeiro.relatorio_faturamento') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Início</label>
                        <input type="date" name="data_inicio" value="{{ $dataInicio->format('Y-m-d') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Data Fim</label>
                        <input type="date" name="data_fim" value="{{ $dataFim->format('Y-m-d') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:text-gray-300">
                    </div>
                    <div class="md:col-span-2 flex space-x-2">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-md font-bold hover:bg-indigo-700 transition w-full">Filtrar</button>
                        <button type="button" onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md font-bold hover:bg-gray-200 transition">Imprimir</button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-t-4 border-indigo-500 shadow-sm text-center">
                    <p class="text-xs text-gray-500 font-bold uppercase">Total no Período</p>
                    <p class="text-2xl font-black text-indigo-600 mt-1">R$ {{ number_format($faturamentoTotal, 2, ',', '.') }}</p>
                </div>
                @foreach($totaisPorMetodo as $metodo => $valor)
                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl border-t-4 border-gray-300 shadow-sm text-center">
                    <p class="text-xs text-gray-500 font-bold uppercase">{{ $metodo }}</p>
                    <p class="text-2xl font-black text-gray-800 dark:text-white mt-1">R$ {{ number_format($valor, 2, ',', '.') }}</p>
                </div>
                @endforeach
            </div>



            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-xl overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Data/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Reserva</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Método</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($transacoes as $t)
                        <tr class="text-sm">
                            <td class="px-6 py-4 dark:text-gray-300">{{ $t->paid_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 dark:text-gray-300">#{{ $t->reserva_id }} - {{ $t->reserva->client_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 uppercase font-bold text-xs dark:text-gray-400">{{ $t->payment_method }}</td>
                            <td class="px-6 py-4 text-xs dark:text-gray-400 italic">{{ $t->type }}</td>
                            <td class="px-6 py-4 text-right font-bold {{ $t->amount < 0 ? 'text-red-500' : 'text-green-600' }}">
                                R$ {{ number_format($t->amount, 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
