<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Botão de Volta para o Dashboard de Reservas -->
                <div class="mb-4">
                    <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar ao Painel de Reservas
                    </a>
                </div>

                <!-- Formulário de Pesquisa -->
                <div class="mb-6 flex justify-between items-center">
                    <form action="{{ route('admin.reservas.canceladas') }}" method="GET" class="flex items-center space-x-2 w-full md:w-1/2">
                        <input type="text" name="search" placeholder="Buscar por cliente ou contato..." value="{{ $search ?? '' }}"
                            class="border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm w-full">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition duration-150">
                            Buscar
                        </button>
                        @if ($search)
                            <a href="{{ route('admin.reservas.canceladas') }}" class="px-3 py-2 text-gray-500 hover:text-gray-700 transition duration-150">
                                Limpar
                            </a>
                        @endif
                    </form>
                </div>

                <!-- Tabela de Reservas -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Cliente / Data
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Horário
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Motivo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Gerenciada por
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($reservas as $reserva)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @php
                                            // Assume que as constantes STATUS_CANCELADA e STATUS_REJEITADA estão acessíveis
                                            // E que App\Models\Reserva é o caminho correto.
                                            // Como o blade não tem acesso ao namespace, usamos o texto da string.
                                            $isCancelled = $reserva->status === 'cancelled';
                                            $color = $isCancelled ? 'text-red-600 bg-red-100' : 'text-yellow-600 bg-yellow-100';
                                            $statusText = $isCancelled ? 'Cancelada' : 'Rejeitada';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }} uppercase">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $reserva->client_name ?? optional($reserva->user)->name ?? 'N/A' }}
                                            @if ($reserva->is_recurrent)
                                                <span class="text-xs text-fuchsia-600">(Recorrente)</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        (R$ {{ number_format($reserva->price, 2, ',', '.') }})
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                        {{ $reserva->cancellation_reason ?? 'Motivo não registrado' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ optional($reserva->manager)->name ?? 'Cliente / Sistema' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Nenhuma reserva cancelada ou rejeitada encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div class="mt-4">
                    {{ $reservas->appends(['search' => $search])->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout><x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Botão de Volta para o Dashboard de Reservas -->
                <div class="mb-4">
                    <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar ao Painel de Reservas
                    </a>
                </div>

                <!-- Formulário de Pesquisa -->
                <div class="mb-6 flex justify-between items-center">
                    <form action="{{ route('admin.reservas.canceladas') }}" method="GET" class="flex items-center space-x-2 w-full md:w-1/2">
                        <input type="text" name="search" placeholder="Buscar por cliente ou contato..." value="{{ $search ?? '' }}"
                            class="border-gray-300 focus:border-red-500 focus:ring-red-500 rounded-md shadow-sm w-full">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition duration-150">
                            Buscar
                        </button>
                        @if ($search)
                            <a href="{{ route('admin.reservas.canceladas') }}" class="px-3 py-2 text-gray-500 hover:text-gray-700 transition duration-150">
                                Limpar
                            </a>
                        @endif
                    </form>
                </div>

                <!-- Tabela de Reservas -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Cliente / Data
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Horário
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Motivo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Gerenciada por
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($reservas as $reserva)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @php
                                            // Assume que as constantes STATUS_CANCELADA e STATUS_REJEITADA estão acessíveis
                                            // E que App\Models\Reserva é o caminho correto.
                                            // Como o blade não tem acesso ao namespace, usamos o texto da string.
                                            $isCancelled = $reserva->status === 'cancelled';
                                            $color = $isCancelled ? 'text-red-600 bg-red-100' : 'text-yellow-600 bg-yellow-100';
                                            $statusText = $isCancelled ? 'Cancelada' : 'Rejeitada';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }} uppercase">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $reserva->client_name ?? optional($reserva->user)->name ?? 'N/A' }}
                                            @if ($reserva->is_recurrent)
                                                <span class="text-xs text-fuchsia-600">(Recorrente)</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} -
                                        {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        (R$ {{ number_format($reserva->price, 2, ',', '.') }})
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                        {{ $reserva->cancellation_reason ?? 'Motivo não registrado' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ optional($reserva->manager)->name ?? 'Cliente / Sistema' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Nenhuma reserva cancelada ou rejeitada encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div class="mt-4">
                    {{ $reservas->appends(['search' => $search])->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
