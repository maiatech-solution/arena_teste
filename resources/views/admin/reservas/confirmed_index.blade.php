<x-app-layout>
<x-slot name="header">
<h2 class="font-semibold text-xl text-gray-800 leading-tight">
{{ $pageTitle }}
</h2>
</x-slot>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-2xl sm:rounded-xl p-6 lg:p-10">

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

                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 space-y-4 md:space-y-0">

                                <div class="flex space-x-3 p-1 bg-gray-100 rounded-xl shadow-inner">
                                        <a href="{{ route('admin.reservas.confirmed_index') }}"
                       class="px-4 py-2 text-sm font-semibold rounded-lg shadow-md transition duration-150
                              @if (!$isOnlyMine)
                                  bg-indigo-600 text-white hover:bg-indigo-700
                              @else
                                  text-indigo-600 hover:bg-white
                              @endif">
                        Todas Confirmadas
                    </a>

                                        <a href="{{ route('admin.reservas.confirmed_index', ['only_mine' => 'true']) }}"
                       class="px-4 py-2 text-sm font-semibold rounded-lg shadow-md transition duration-150
                              @if ($isOnlyMine)
                                  bg-indigo-600 text-white hover:bg-indigo-700
                              @else
                                  text-indigo-600 hover:bg-white
                              @endif">
                        Minhas Manuais
                    </a>
                </div>

                                <a href="{{ route('admin.reservas.create') }}"
                   class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-xl shadow-green-400/50 transition duration-150 ease-in-out flex items-center justify-center space-x-1 tracking-wider">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                    <span class="text-sm">Nova Manual</span>
                </a>
            </div>

                        <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">Data/Hora</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cliente/Reserva</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">Preço</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">Criada Por</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse ($reservas as $reserva)
                            <tr class="odd:bg-white even:bg-gray-50 hover:bg-indigo-50 transition duration-150">

                                <td class="px-4 py-3 whitespace-nowrap min-w-[120px]">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/y') }}
                                    </div>
                                    <div class="text-indigo-600 text-xs font-semibold">
                                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-left">
                                    @if ($reserva->user)
                                        <div class="text-sm font-semibold text-gray-900">{{ $reserva->user->name }}</div>
                                        <div class="text-xs text-green-600 font-medium">Agendamento de Cliente</div>
                                    @else
                                        <div class="text-sm font-bold text-indigo-700">{{ $reserva->client_name ?? 'Cliente (Manual)' }}</div>
                                        <div class="text-xs text-gray-500 font-medium">{{ $reserva->client_contact ?? 'Contato não informado' }}</div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 whitespace-nowrap min-w-[90px] text-sm font-bold text-green-700 text-right">
                                    R$ {{ number_format($reserva->price ?? 0, 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-3 text-left min-w-[120px]">
                                    @if ($reserva->manager)
                                        <span class="font-medium text-purple-700 bg-purple-100 px-2 py-0.5 text-xs rounded-full whitespace-nowrap shadow-sm">
                                            {{ \Illuminate\Support\Str::limit($reserva->manager->name, 10, '...') }} (Gestor)
                                        </span>
                                    @else
                                        <span class="text-gray-600 bg-gray-100 px-2 py-0.5 text-xs rounded-full whitespace-nowrap shadow-sm">
                                            Cliente via Web
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-sm font-medium min-w-[100px]">
                                    <div class="flex flex-col space-y-1">
                                        <a href="{{ route('admin.reservas.show', $reserva) }}"
                                           class="inline-block text-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                            Detalhes
                                        </a>

                                        <form action="{{ route('admin.reservas.cancelar', $reserva) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja CANCELAR esta reserva? Isso a removerá do calendário.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="inline-block w-full text-center bg-red-600 hover:bg-red-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                Cancelar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 whitespace-nowrap text-center text-base text-gray-500 italic">
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

                        <div class="mt-8">
                {{ $reservas->links() }}
            </div>

        </div>
    </div>
</div>

</x-app-layout>
