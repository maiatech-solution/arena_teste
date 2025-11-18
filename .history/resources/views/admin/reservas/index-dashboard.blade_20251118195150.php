<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                {{-- Exibir mensagens de sessão --}}
                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                @endif

                <!-- Botão de Volta para o Dashboard de Reservas -->
                <div class="mb-6">
                    <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar ao Painel de Reservas
                    </a>
                </div>

                <!-- Tabela de Reservas Pendentes -->
                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700">
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider min-w-[120px]">
                                    Data/Hora
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                    Cliente
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider min-w-[90px]">
                                    Valor Proposto
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider min-w-[150px]">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($reservas as $reserva)
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-orange-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm min-w-[120px]">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                        </div>
                                        <div class="text-orange-600 text-xs font-semibold">
                                            {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-left">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $reserva->client_name ?? optional($reserva->user)->name ?? 'Cliente Desconhecido' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $reserva->client_contact ?? optional($reserva->user)->email ?? 'Contato N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap min-w-[90px] text-sm font-bold text-green-700 text-right">
                                        R$ {{ number_format($reserva->price ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium min-w-[150px]">
                                        <div class="flex flex-col space-y-2">

                                            {{-- Botão Confirmar --}}
                                            <form method="POST" action="{{ route('admin.reservas.confirmar', $reserva) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        onclick="return confirm('Tem certeza que deseja CONFIRMAR esta pré-reserva?')"
                                                        class="w-full text-center bg-green-600 hover:bg-green-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                    Confirmar
                                                </button>
                                            </form>

                                            {{-- Botão Rejeitar --}}
                                            <form method="POST" action="{{ route('admin.reservas.rejeitar', $reserva) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                        onclick="return confirm('Tem certeza que deseja REJEITAR esta pré-reserva? Isso irá liberar o horário.')"
                                                        class="w-full text-center bg-red-600 hover:bg-red-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                    Rejeitar
                                                </button>
                                            </form>

                                            {{-- Botão Ver Detalhes --}}
                                            <a href="{{ route('admin.reservas.show', $reserva) }}"
                                               class="w-full inline-block text-center text-orange-600 hover:text-orange-800 bg-orange-100 px-3 py-1 text-xs font-semibold rounded-md transition duration-150">
                                                Detalhes
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 whitespace-nowrap text-center text-base text-gray-500 italic">
                                        Nenhuma pré-reserva pendente no momento.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div class="mt-4">
                    {{ $reservas->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
