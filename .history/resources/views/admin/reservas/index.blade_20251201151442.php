<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📋 Reservas Pendentes de Aprovação
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session('success'))
                    {{-- Mensagem de Sucesso --}}
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md border border-green-400">
                        {{ session('success') }}
                    </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    {{-- Cabeçalhos --}}
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Horário</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>

                                    {{-- CAMPO REPUTAÇÃO: USARÁ O NOVO ACCESSOR --}}
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Reputação</th>

                                    {{-- Coluna mesclada para Sinal e Ações --}}
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/4">Sinal e Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($reservas as $reserva)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">

                                    {{-- Cliente --}}
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium">{{ $reserva->client_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $reserva->client_contact }}</div>
                                    </td>

                                    {{-- Data --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                    </td>

                                    {{-- Horário --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-bold">
                                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}

                                        {{-- 🆕 Mostrar quantas pré-reservas existem no mesmo horário --}}
                                        @php
                                        $sameTimeReservasCount = \App\Models\Reserva::where('id', '!=', $reserva->id)
                                        ->where('date', $reserva->date)
                                        ->where('start_time', $reserva->start_time)
                                        ->where('end_time', $reserva->end_time)
                                        ->where('status', 'pending')
                                        ->count();
                                        @endphp

                                        @if($sameTimeReservasCount > 0)
                                        <br>
                                        <span class="text-xs text-red-600 font-normal">
                                            ⚠️ +{{ $sameTimeReservasCount }} outra(s) pré-reserva(s) neste horário
                                        </span>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">
                                            Pendente
                                        </span>
                                    </td>

                                    {{-- ✅ NOVO CÓDIGO para Reputação: Apenas chama o Accessor --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        @if($reserva->user)
                                        {{-- O Accessor status_tag retorna o HTML completo da tag. --}}
                                        {{-- Usamos {!! !!} para renderizar o HTML da string. --}}
                                        {!! $reserva->user->status_tag !!}
                                        @else
                                        <span class="text-xs text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                    {{-- Fim do bloco de Reputação --}}

                                    {{-- FORMULÁRIO UNIFICADO DE CONFIRMAÇÃO + SINAL e AÇÕES --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="flex flex-col space-y-2">

                                            <form action="{{ route('admin.reservas.confirmar', $reserva->id) }}" method="POST" class="flex flex-col space-y-2 items-end">
                                                @csrf
                                                @method('PATCH')

                                                {{-- ✅ NOVO: Checkbox Recorrente (6 meses) --}}
                                                <div class="flex items-center space-x-2 w-full justify-end">
                                                    <input type="checkbox" name="is_recurrent" id="is-recurrent-{{ $reserva->id }}" value="1"
                                                        class="h-4 w-4 text-fuchsia-600 border-gray-300 rounded focus:ring-fuchsia-500 dark:bg-gray-700 dark:border-gray-600">
                                                    <label for="is-recurrent-{{ $reserva->id }}" class="text-xs font-semibold text-fuchsia-700 dark:text-fuchsia-400 select-none cursor-pointer">
                                                        Recorrente (+6m)
                                                    </label>
                                                </div>
                                                {{-- Fim do Checkbox Recorrente --}}

                                                <div class="flex items-center space-x-2 justify-end w-full">
                                                    {{-- Input do Sinal --}}
                                                    <div class="relative w-24">
                                                        <span class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400 text-xs">R$</span>
                                                        <input type="number" name="signal_value" step="0.01" min="0" placeholder="0.00"
                                                            class="w-full pl-6 text-sm rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                            value="{{ ($reserva->user && $reserva->user->is_vip) ? '0.00' : '' }}">
                                                    </div>

                                                    {{-- Botão Confirmar --}}
                                                    <button type="submit" title="Confirmar e Registrar Sinal"
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-xs font-bold transition shadow-sm flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Confirmar
                                                    </button>
                                                </div>
                                            </form>

                                            {{-- Botão Rejeitar (Separado do form de confirmação) --}}
                                            <form action="{{ route('admin.reservas.rejeitar', $reserva->id) }}" method="POST" class="w-full text-right">
                                                @csrf
                                                @method('PATCH')
                                                {{-- ATENÇÃO: É ALTAMENTE RECOMENDÁVEL SUBSTITUIR 'onclick="return confirm(...)"' POR UM MODAL CUSTOMIZADO, POIS O CONFIRM DO NAVEGADOR É BLOQUEANTE E TEM MÁ EXPERIÊNCIA. --}}
                                                <button type="submit" title="Rejeitar Agendamento" onclick="return confirm('Tem certeza que deseja rejeitar? O cliente será notificado.')"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-600 text-xs underline">
                                                    Rejeitar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Não há reservas pendentes no momento.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginação --}}
                    <div class="mt-4">
                        {{ $reservas->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
