<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detalhes da Reserva') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-2xl sm:rounded-lg">

                {{-- Notificações --}}
                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-t-lg" role="alert">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-t-lg" role="alert">
                        <p>{{ session('error') }}</p>
                    </div>
                @endif

                <div class="p-6 sm:p-8">

                    {{-- Cabeçalho e Status --}}
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b pb-4 mb-6">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Reserva #{{ $reserva->id }}
                        </h3>
                        @php
                            // Lógica para colorir o status
                            $statusClass = [
                                'pending' => 'bg-orange-100 text-orange-800',
                                'confirmed' => 'bg-indigo-100 text-indigo-800',
                                'cancelled' => 'bg-red-100 text-red-800',
                                'rejected' => 'bg-gray-100 text-gray-800',
                                'expired' => 'bg-yellow-100 text-yellow-800',
                            ][$reserva->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="mt-2 md:mt-0 px-3 py-1 text-sm font-semibold rounded-full uppercase {{ $statusClass }}">
                            {{ $reserva->statusText }}
                        </span>
                    </div>

                    {{-- Card de Informações Principais --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg shadow-inner">
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Data e Horário</p>
                            <p class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400">
                                {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                            </p>
                            <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                            </p>
                        </div>

                        <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg shadow-inner">
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase">Valor</p>
                            <p class="text-3xl font-extrabold text-green-600 dark:text-green-400">
                                R$ {{ number_format($reserva->price, 2, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    {{-- Detalhes do Cliente e Gestor --}}
                    <div class="space-y-4 mb-8">

                        {{-- Detalhes do Cliente --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Cliente</h4>
                            <div class="flex flex-col space-y-1">
                                {{-- Usa client_name se for manual, ou user->name se for registrado --}}
                                <p class="text-base font-bold text-indigo-700 dark:text-indigo-300">
                                    {{ $reserva->client_name ?? ($reserva->user ? $reserva->user->name : 'N/A') }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    Contato: {{ $reserva->client_contact ?? ($reserva->user ? $reserva->user->email : 'Não informado') }}
                                </p>
                            </div>
                        </div>

                        {{-- Detalhes da Criação --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Origem e Recorrência</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Criada por: {{ $reserva->criadoPorLabel }}</p>

                            @if ($reserva->manager)
                                <p class="text-sm text-gray-600 dark:text-gray-400">Gestor: {{ $reserva->manager->name }}</p>
                            @endif

                            <p class="mt-2 text-sm font-semibold {{ $reserva->is_recurrent ? 'text-indigo-600' : 'text-gray-500' }}">
                                Tipo: {{ $reserva->is_recurrent ? 'Série Recorrente' : 'Reserva Pontual' }}
                                @if ($reserva->is_recurrent && $reserva->recurrent_series_id)
                                    (Membro da Série #{{ $reserva->recurrent_series_id }})
                                @endif
                            </p>
                        </div>

                        {{-- Observações --}}
                        @if ($reserva->notes)
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Observações</h4>
                                <p class="p-3 bg-yellow-50 dark:bg-yellow-900/50 border-l-4 border-yellow-400 text-sm text-yellow-800 dark:text-yellow-200 rounded-lg">
                                    {{ $reserva->notes }}
                                </p>
                            </div>
                        @endif

                    </div>

                    {{-- Ações de Status (Aparecem apenas se o status permitir a mudança) --}}
                    @if ($reserva->status === $reserva::STATUS_PENDENTE || $reserva->status === $reserva::STATUS_CONFIRMADA)
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Mudar Status da Reserva</h4>
                            <div class="flex flex-col space-y-3">

                                @if ($reserva->status === $reserva::STATUS_PENDENTE)
                                    {{-- Botão Confirmar --}}
                                    <form method="POST" action="{{ route('admin.reservas.confirmar', $reserva) }}" onsubmit="return confirm('Confirmar o agendamento de {{ $reserva->client_name }}?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full md:w-auto px-6 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition duration-150 shadow-lg">
                                            Confirmar Agendamento
                                        </button>
                                    </form>

                                    {{-- Botão Rejeitar --}}
                                    <form method="POST" action="{{ route('admin.reservas.rejeitar', $reserva) }}" onsubmit="return confirm('Tem certeza que deseja REJEITAR a pré-reserva de {{ $reserva->client_name }}?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full md:w-auto px-6 py-2 bg-gray-500 text-white font-bold rounded-lg hover:bg-gray-600 transition duration-150 shadow-lg">
                                            Rejeitar Pré-Reserva
                                        </button>
                                    </form>
                                @endif

                                @if ($reserva->status === $reserva::STATUS_CONFIRMADA)
                                    {{-- Botão Cancelar --}}
                                    <form method="POST" action="{{ route('admin.reservas.cancelar', $reserva) }}" onsubmit="return confirm('Tem certeza que deseja CANCELAR a reserva de {{ $reserva->client_name }}?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="w-full md:w-auto px-6 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150 shadow-lg">
                                            Mudar para Status Cancelada
                                        </button>
                                    </form>
                                @endif

                                {{-- Aviso de Recorrência (Substitui os botões redundantes) --}}
                                @if ($reserva->is_recurrent)
                                    <p class="text-sm text-yellow-600 dark:text-yellow-400 p-2 border border-yellow-300 rounded-md">
                                        ⚠️ Esta é uma reserva recorrente. Para gerenciar o cancelamento pontual ou da série inteira, use a lista de **Reservas Confirmadas** ou o **Calendário**.
                                    </p>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <p class="text-lg font-semibold text-gray-500 dark:text-gray-400">
                                Não há ações de status disponíveis, pois a reserva está **{{ $reserva->statusText }}**.
                            </p>
                        </div>
                    @endif

                    {{-- Retorno para a Lista --}}
                    <div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
                        {{-- CRÍTICO: Troca o link fixo por um botão que usa o histórico do navegador --}}
                        <button type="button" onclick="window.history.back()" class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition duration-150 font-medium">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Voltar para a tela anterior
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
