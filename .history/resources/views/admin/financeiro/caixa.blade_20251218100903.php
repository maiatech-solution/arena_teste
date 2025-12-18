<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 p-8 shadow-lg rounded-xl print:shadow-none print:p-0">

                <div class="flex justify-between items-center border-b-2 border-gray-100 pb-6 mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white uppercase">Relatório de Caixa Diário</h1>
                        <p class="text-gray-500 text-sm">Data de Referência: <span class="font-bold">{{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}</span></p>
                    </div>
                    <button onclick="window.print()" class="print:hidden bg-indigo-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-indigo-700 transition">
                        🖨️ Imprimir Folha
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div class="space-y-2">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Entradas por Categoria</h3>
                        @foreach($movimentacoes->groupBy('payment_method') as $metodo => $transacoes)
                            <div class="flex justify-between border-b border-gray-50 dark:border-gray-700 py-1">
                                <span class="text-gray-600 dark:text-gray-300 uppercase font-medium">{{ $metodo }}</span>
                                <span class="font-mono font-bold dark:text-white">R$ {{ number_format($transacoes->sum('amount'), 2, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900/50 p-6 rounded-xl flex flex-col justify-center items-center">
                        <span class="text-xs font-bold text-gray-400 uppercase">Total Líquido em Sistema</span>
                        <span class="text-3xl font-black text-indigo-600">R$ {{ number_format($movimentacoes->sum('amount'), 2, ',', '.') }}</span>
                    </div>
                </div>

                <table class="w-full text-left text-sm mb-12">
                    <thead>
                        <tr class="text-gray-400 uppercase text-[10px] font-bold border-b dark:border-gray-700">
                            <th class="py-3">Hora</th>
                            <th class="py-3">Reserva/Descrição</th>
                            <th class="py-3">Método</th>
                            <th class="py-3 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        @foreach($movimentacoes as $m)
                            <tr>
                                <td class="py-3 text-gray-500">{{ $m->paid_at->format('H:i') }}</td>
                                <td class="py-3 font-medium dark:text-gray-200">#{{ $m->reserva_id }} - {{ $m->reserva->client_name ?? 'Lançamento Manual' }}</td>
                                <td class="py-3 uppercase text-xs dark:text-gray-400">{{ $m->payment_method }}</td>
                                <td class="py-3 text-right font-mono font-bold {{ $m->amount < 0 ? 'text-red-500' : 'dark:text-white' }}">
                                    R$ {{ number_format($m->amount, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-16 grid grid-cols-2 gap-12 text-center">
                    <div>
                        <div class="border-b border-gray-300 dark:border-gray-600 mb-2"></div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Responsável pelo Caixa</p>
                    </div>
                    <div>
                        <div class="border-b border-gray-300 dark:border-gray-600 mb-2"></div>
                        <p class="text-[10px] uppercase font-bold text-gray-400">Gerência / Conferência</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
