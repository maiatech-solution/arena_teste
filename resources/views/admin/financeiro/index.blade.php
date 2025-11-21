<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📊 Dashboard Financeiro
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Cards de Sinais Recebidos -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Card Hoje -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-500 text-white mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sinal Hoje</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">R$ {{ number_format($sinalHoje, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card Semana -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-500 text-white mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sinal Esta Semana</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">R$ {{ number_format($sinalSemana, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card Mês -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-500 text-white mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sinal Este Mês</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">R$ {{ number_format($sinalMes, 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Reservas com Pagamento Pendente -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4">💳 Reservas com Pagamento Pendente</h3>

                    @if ($reservasPendentes->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Horário</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Total</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sinal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pago</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status Pag.</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($reservasPendentes as $reserva)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium">{{ $reserva->client_name }}</div>
                                                <div class="text-xs text-gray-500">{{ $reserva->client_contact }}</div>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-bold">
                                                {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                R$ {{ number_format($reserva->price, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                R$ {{ number_format($reserva->signal_value, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                R$ {{ number_format($reserva->total_paid, 2, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $reserva->payment_status == 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $reserva->payment_status == 'partial' ? 'Parcial' : 'Pendente' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                <a href="{{ route('admin.reservas.show', $reserva->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Ver</a>
                                                <!-- Adicione aqui ações para registrar pagamento, etc. -->
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <div class="mt-4">
                            {{ $reservasPendentes->links() }}
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400">Nenhuma reserva com pagamento pendente.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>