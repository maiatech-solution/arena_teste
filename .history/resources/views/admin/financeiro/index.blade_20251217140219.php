<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                📊 Painel de Relatórios Financeiros
            </h2>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Mês de Referência: <span class="font-bold text-indigo-600">{{ now()->translatedFormat('F / Y') }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- 1. RESUMO ESTRATÉGICO (MÊS ATUAL) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-indigo-600 dark:bg-indigo-900 p-6 rounded-xl shadow-lg text-white">
                    <p class="text-xs opacity-80 uppercase font-bold tracking-wider">Receita Bruta (Mês)</p>
                    <p class="text-3xl font-extrabold mt-1">R$ {{ number_format($faturamentoMensal, 2, ',', '.') }}</p>
                    <div class="mt-4 text-xs bg-white/20 p-2 rounded text-center">Baseado em pagamentos confirmados</div>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold">Jogos Concluídos</p>
                    <p class="text-3xl font-bold dark:text-white mt-1">{{ $pagasMes }}</p>
                    <p class="text-xs text-green-600 mt-2 font-medium">Sucesso na agenda</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border-l-4 border-red-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold">Cancelamentos</p>
                    <p class="text-3xl font-bold dark:text-white mt-1">{{ $canceladasMes }}</p>
                    <p class="text-xs text-red-600 mt-2 font-medium">Perda de oportunidade</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold">Total Reservas</p>
                    <p class="text-3xl font-bold dark:text-white mt-1">{{ $totalReservasMes }}</p>
                    <p class="text-xs text-blue-600 mt-2 font-medium">Volume total do mês</p>
                </div>
            </div>

            {{-- 2. CARDS DE SINAIS (FLUXO DE CAIXA) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Sinal Recebido Hoje</p>
                            <p class="text-2xl font-black text-blue-600 mt-1">R$ {{ number_format($sinalHoje, 2, ',', '.') }}</p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                            <svg class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Sinal na Semana</p>
                            <p class="text-2xl font-black text-green-600 mt-1">R$ {{ number_format($sinalSemana, 2, ',', '.') }}</p>
                        </div>
                        <div class="p-3 bg-green-50 dark:bg-green-900/30 rounded-lg text-green-500">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Sinal no Mês</p>
                            <p class="text-2xl font-black text-purple-600 mt-1">R$ {{ number_format($sinalMes, 2, ',', '.') }}</p>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-lg text-purple-500">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. TABELA DE COBRANÇA (PENDÊNCIAS) --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center">
                            <span class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg mr-3">💳</span>
                            Reservas com Pagamento Pendente
                        </h3>
                        <div class="text-xs text-gray-400">Listando próximas reservas que precisam de acerto</div>
                    </div>

                    @if ($reservasPendentes->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Cliente</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase text-center">Data / Hora</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Total / Sinal</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Saldo Devedor</th>
                                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($reservasPendentes as $reserva)
                                        @php $saldoRestante = $reserva->price - $reserva->total_paid; @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150">
                                            <td class="px-4 py-4">
                                                <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $reserva->client_name }}</div>
                                                <div class="text-xs text-gray-400">{{ $reserva->client_contact }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <div class="text-sm dark:text-gray-300">{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}</div>
                                                <div class="text-xs font-bold text-indigo-500 uppercase">{{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }}h</div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="text-xs text-gray-500">T: R$ {{ number_format($reserva->price, 2, ',', '.') }}</div>
                                                <div class="text-xs text-indigo-400">S: R$ {{ number_format($reserva->signal_value, 2, ',', '.') }}</div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="text-sm font-black text-red-600">R$ {{ number_format($saldoRestante, 2, ',', '.') }}</div>
                                                <div class="text-[10px] text-gray-400 italic">Pago: R$ {{ number_format($reserva->total_paid, 2, ',', '.') }}</div>
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <span class="px-3 py-1 text-[10px] font-bold rounded-full uppercase tracking-tighter
                                                    {{ $reserva->payment_status == 'partial' ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">
                                                    {{ $reserva->payment_status == 'partial' ? 'Parcial' : 'Pendente' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 text-center">
                                                <a href="{{ route('admin.payment.index', ['reserva_id' => $reserva->id]) }}"
                                                   class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                    Receber
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 border-t border-gray-100 dark:border-gray-700 pt-4">
                            {{ $reservasPendentes->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="text-4xl mb-3">✅</div>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">Tudo em dia! Nenhuma reserva pendente de acerto para o futuro.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
