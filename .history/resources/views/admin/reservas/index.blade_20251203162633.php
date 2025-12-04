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

                    {{-- Mensagens de Erro/Aviso (caso o Controller retorne erros, eles aparecerão aqui) --}}
                    @if (session('error'))
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md border border-red-400">
                        {{ session('error') }}
                    </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md border border-red-400">
                            <strong>Erro de Validação:</strong> Por favor, verifique o formulário.
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
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/4">Ações</th>
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
                                        // Usando o Scope isOccupied para uma checagem mais robusta (exclui a própria reserva)
                                        $sameTimeReservasCount = \App\Models\Reserva::where('id', '!=', $reserva->id)
                                        ->isOccupied($reserva->date, $reserva->start_time, $reserva->end_time) // ✅ Reutiliza o scope
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

                                    {{-- ✅ Reputação --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        @if($reserva->user)
                                        {{-- Assumindo que o Accessor status_tag retorna o HTML completo da tag. --}}
                                        {!! $reserva->user->status_tag !!}
                                        @else
                                        <span class="text-xs text-gray-400">N/A</span>
                                        @endif
                                    </td>

                                    {{-- 🛑 NOVO BLOCO DE AÇÕES (Botões que chamam o Modal) --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-right">
                                        <div class="flex flex-col space-y-2 items-end">

                                            {{-- 1. BOTÃO CONFIRMAR (Chama o modal) --}}
                                            <button type="button"
                                                onclick="openConfirmModal(
                                                    '{{ $reserva->id }}',
                                                    '{{ $reserva->client_name }}',
                                                    '{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }} às {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }}',
                                                    '{{ $reserva->price }}'
                                                )"
                                                title="Abrir formulário de Confirmação, Sinal e Recorrência"
                                                class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-md text-xs font-bold transition shadow-md w-full sm:w-auto flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Confirmar
                                            </button>

                                            {{-- 2. BOTÃO REJEITAR (Manteve o form de Rejeição, mas com modal placeholder) --}}
                                            <form action="{{ route('admin.reservas.rejeitar', $reserva->id) }}" method="POST" class="w-full text-right">
                                                @csrf
                                                @method('PATCH')

                                                <input type="hidden" name="rejection_reason" value="Rejeitada pela administração - Por favor, refaça o agendamento em um slot livre.">

                                                <button type="submit" title="Rejeitar Agendamento"
                                                    onclick="return confirm('Tem certeza que deseja rejeitar esta PRÉ-RESERVA? O cliente será notificado e o horário voltará a ser livre.')"
                                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-md text-xs font-bold transition shadow-md w-full sm:w-auto ">
>
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

    <!-- 🛑 INCLUSÃO DO MODAL DE CONFIRMAÇÃO (CHAVE PARA O OBJETIVO) -->
    @include('admin.reservas.confirmation_modal')

</x-app-layout>
