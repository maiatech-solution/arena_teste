<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Informações do Cliente -->
                <div class="bg-gray-100 p-4 rounded-lg shadow mb-6 border-l-4 border-indigo-500">
                    <p class="text-lg font-bold text-gray-900">Cliente: {{ $client->name }}</p>
                    <p class="text-sm text-gray-700">Email: {{ $client->email }}</p>
                    <p class="text-sm text-gray-700">Contato: {{ $client->whatsapp_contact ?? 'N/A' }}</p>
                </div>

                <!-- Botão de Volta para a Lista de Usuários -->
                <div class="mb-6">
                    <a href="{{ route('admin.users.index', ['role_filter' => 'cliente']) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar à Lista de Usuários
                    </a>
                </div>


                <!-- Tabela de Reservas -->
                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">
                                    Data
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">
                                    Horário
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">
                                    Preço
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[150px]">
                                    Tipo
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($reservas as $reserva)
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-indigo-50 transition duration-150">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold">
                                        @php
                                            $colorClass = match ($reserva->status) {
                                                'confirmed' => 'bg-green-100 text-green-700',
                                                'pending' => 'bg-yellow-100 text-yellow-700',
                                                'cancelled', 'rejected' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 rounded-full {{ $colorClass }} uppercase">
                                            {{ $reserva->status_text }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-right text-green-700">
                                        R$ {{ number_format($reserva->price, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        @if ($reserva->is_recurrent)
                                            <span class="font-semibold text-fuchsia-600">Recorrente</span>
                                        @else
                                            Pontual
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        {{-- Botão Detalhes --}}
                                        <a href="{{ route('admin.reservas.show', $reserva) }}"
                                           class="inline-block text-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                            Ver Detalhes
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 whitespace-nowrap text-center text-base text-gray-500 italic">
                                        Este cliente não possui reservas agendadas ou históricas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div class="mt-6">
                    {{ $reservas->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
