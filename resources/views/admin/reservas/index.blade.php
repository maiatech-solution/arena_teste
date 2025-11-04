<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gerenciamento de Reservas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Cliente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Data
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Horário
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Valor
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @if ($reservas->isEmpty())
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">
                                            Nenhuma reserva encontrada.
                                        </td>
                                    </tr>
                                @else
                                    @foreach ($reservas as $reserva)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $reserva->client_name }} <br>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $reserva->client_contact }}</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                                @php
                                                    // Tenta fazer o parse do campo 'date' para um objeto Carbon
                                                    $dataReserva = \Carbon\Carbon::parse($reserva->date);
                                                @endphp
                                                {{ $dataReserva->isoFormat('D MMM YYYY') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                                {{ $reserva->start_time }} - {{ $reserva->end_time }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 font-bold">
                                                R$ {{ number_format($reserva->price, 2, ',', '.') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                @php
                                                    $statusClasses = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
                                                        'confirmed' => 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
                                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                                                    ];
                                                @endphp
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClasses[$reserva->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ ucfirst($reserva->status) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                @if ($reserva->status === 'pending')
                                                    {{-- Formulário de Confirmação --}}
                                                    <form action="{{ route('admin.reservas.confirm', $reserva) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('patch')
                                                        <button type="submit"
                                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200 transition duration-150 mr-2">
                                                            Confirmar
                                                        </button>
                                                    </form>

                                                    {{-- Formulário de Rejeição --}}
                                                    <form action="{{ route('admin.reservas.reject', $reserva) }}" method="POST" class="inline-block">
                                                        @csrf
                                                        @method('patch')
                                                        <button type="submit"
                                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200 transition duration-150"
                                                                onclick="return confirm('Tem certeza que deseja rejeitar esta reserva?');">
                                                            Rejeitar
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-gray-400 dark:text-gray-500">Ação Concluída</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
