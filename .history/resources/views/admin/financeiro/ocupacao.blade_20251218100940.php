<x-app-layout>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 p-8 shadow-lg rounded-xl print:shadow-none">
                <div class="flex justify-between items-start mb-10">
                    <div>
                        <h1 class="text-2xl font-black text-gray-900 dark:text-white uppercase">Mapa de Ocupação da Arena</h1>
                        <p class="text-gray-500 text-sm italic">Cronograma de jogos confirmados a partir de {{ \Carbon\Carbon::parse($dataInicio)->format('d/m/Y') }}</p>
                    </div>
                    <button onclick="window.print()" class="print:hidden bg-indigo-600 text-white px-5 py-2 rounded-lg font-bold shadow-md">Imprimir Mapa</button>
                </div>

                @foreach($reservas->groupBy('date') as $data => $lista)
                    <div class="mb-12 break-inside-avoid">
                        <div class="flex items-center mb-4">
                            <div class="bg-indigo-600 text-white px-4 py-1 rounded-l-lg font-bold text-sm">
                                {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}
                            </div>
                            <div class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 px-4 py-1 rounded-r-lg font-bold text-sm uppercase">
                                {{ \Carbon\Carbon::parse($data)->translatedFormat('l') }}
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($lista as $r)
                                <div class="border-2 border-gray-100 dark:border-gray-700 p-4 rounded-xl relative overflow-hidden">
                                    <div class="absolute top-0 right-0 bg-gray-100 dark:bg-gray-700 px-2 py-1 text-[10px] font-bold text-gray-500 uppercase">
                                        Ref: #{{ $r->id }}
                                    </div>
                                    <div class="text-2xl font-black text-indigo-600 mb-1">
                                        {{ \Carbon\Carbon::parse($r->start_time)->format('H:i') }}
                                    </div>
                                    <div class="font-bold text-gray-800 dark:text-white uppercase truncate">{{ $r->client_name }}</div>
                                    <div class="text-xs text-gray-400 mt-2 flex justify-between">
                                        <span>Quadra: {{ $r->court_id ?? 'Padrão' }}</span>
                                        <span class="font-bold text-green-500 italic">{{ $r->payment_status == 'paid' ? 'PAGO' : 'PENDENTE' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
