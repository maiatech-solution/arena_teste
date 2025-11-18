<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gerenciamento de Reservas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Título da Página -->
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                    Selecione o Status das Reservas:
                </h3>

                <!-- GRID DE BOTÕES DE FILTRO -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                    <!-- Botão 1: RESERVAS PENDENTES -->
                    <a href="{{ route('admin.reservas.pendentes') }}" class="block p-6 bg-orange-50 border border-orange-200 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xl font-bold text-orange-700">Pendentes</h4>
                            <span class="text-3xl font-extrabold text-orange-600 bg-orange-200 px-3 py-1 rounded-full">{{ $pendingCount }}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">
                            Pré-reservas que aguardam sua confirmação ou rejeição.
                        </p>
                    </a>

                    <!-- Botão 2: RESERVAS CONFIRMADAS -->
                    <a href="{{ route('admin.reservas.confirmadas') }}" class="block p-6 bg-indigo-50 border border-indigo-200 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xl font-bold text-indigo-700">Confirmadas</h4>
                            <span class="text-3xl font-extrabold text-indigo-600 bg-indigo-200 px-3 py-1 rounded-full">{{ $confirmedCount }}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">
                            Próximos agendamentos confirmados (inclui Recorrentes).
                        </p>
                    </a>

                    <!-- Botão 3: RESERVAS CANCELADAS -->
                    <a href="{{ route('admin.reservas.canceladas') }}" class="block p-6 bg-red-50 border border-red-200 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xl font-bold text-red-700">Canceladas</h4>
                            <span class="text-3xl font-extrabold text-red-600 bg-red-200 px-3 py-1 rounded-full">{{ $canceledCount }}</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">
                            Histórico de reservas canceladas ou rejeitadas.
                        </p>
                    </a>

                </div>

            </div>
        </div>
    </div>
</x-app-layout>
