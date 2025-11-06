<x-app-layout>
<x-slot name="header">
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
{{ $pageTitle }}
</h2>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

            <!-- Mensagens de Status (Success/Error) -->
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <!-- Filtros e Ações -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">

                <!-- Botões de Filtro -->
                <div class="flex space-x-2">
                    <!-- Link para Todas as Reservas -->
                    <a href="{{ route('admin.reservas.confirmed_index') }}"
                       class="px-4 py-2 text-sm font-medium rounded-lg
                              @if (!$isOnlyMine)
                                  bg-indigo-600 text-white shadow
                              @else
                                  text-indigo-600 border border-indigo-600 hover:bg-indigo-50
                              @endif">
                        Todas as Reservas Confirmadas
                    </a>

                    <!-- Link para Minhas Reservas Manuais -->
                    <a href="{{ route('admin.reservas.confirmed_index', ['only_mine' => 'true']) }}"
                       class="px-4 py-2 text-sm font-medium rounded-lg
                              @if ($isOnlyMine)
                                  bg-indigo-600 text-white shadow
                              @else
                                  text-indigo-600 border border-indigo-600 hover:bg-indigo-50
                              @endif">
                        Minhas Reservas Manuais
                    </a>
                </div>

                <!-- Botão de Nova Reserva Manual -->
                <a href="{{ route('admin.reservas.create') }}"
                   class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition duration-150 ease-in-out">
                    + Nova Reserva Manual
                </a>
            </div>

            <!-- Tabela de Reservas -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Hora</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente/Reserva</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Criada Por</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($reservas as $reserva)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div class="font-medium">{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}</div>
                                    <div class="text-gray-500">{{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($reserva->user)
                                        <div class="text-sm font-medium text-gray-900">{{ $reserva->user->name }}</div>
                                        <div class="text-xs text-gray-500">Agendamento de Cliente</div>
                                    @else
                                        <div class="text-sm font-medium text-indigo-600">{{ $reserva->client_name ?? 'Cliente (Manual)' }}</div>
                                        <div class="text-xs text-gray-500">{{ $reserva->client_contact ?? 'Contato não informado' }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    R$ {{ number_format($reserva->price ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <!-- CRÍTICO: Exibe quem criou -->
                                    @if ($reserva->manager)
                                        <span class="font-bold text-sm text-purple-600">{{ $reserva->manager->name }} (Gestor)</span>
                                    @else
                                        <span class="text-sm text-gray-600">Cliente via Web</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <!-- Link para Cancelamento -->
                                        <form action="{{ route('admin.reservas.cancelar', $reserva) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja cancelar esta reserva?');">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="text-red-600 hover:text-red-900 text-xs font-semibold">
                                                Cancelar
                                            </button>
                                        </form>

                                        <!-- Outras Ações (Ex: Editar) podem ser adicionadas aqui -->
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                    Nenhuma reserva confirmada encontrada
                                    @if ($isOnlyMine)
                                        para este gestor.
                                    @else
                                        no sistema.
                                    @endif
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
