<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gerenciamento de Reservas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

              <!-- Botão de Volta para o Dashboard de Reservas -->
                <div class="mb-6">
                    <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar ao Painel de Reservas
                    </a>
                </div>
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Título da Página -->
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                    Selecione o Status das Reservas:
                </h3>

                <!-- GRID DE BOTÕES DE FILTRO (AJUSTADO PARA 2 COLUNAS) -->
                {{-- Aumentando o grid para 4 colunas para incluir o novo botão --}}
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">

                    <!-- Botão 1: RESERVAS PENDENTES -->
                    <a href="{{ route('admin.reservas.pendentes') }}" class="block p-4 bg-orange-100 border border-orange-200 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center">
                            <h4 class="text-xl font-bold text-orange-700">Pendentes</h4>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">
                            Pré-reservas que aguardam sua confirmação ou rejeição.
                        </p>
                    </a>

                    <!-- Botão 2: RESERVAS CONFIRMADAS -->
                    <a href="{{ route('admin.reservas.confirmadas') }}" class="block p-4 bg-indigo-50 border border-indigo-200 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center">
                            <h4 class="text-xl font-bold text-indigo-700">Confirmadas</h4>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">
                            Próximos agendamentos confirmados (inclui Recorrentes).
                        </p>
                    </a>

                    <!-- Botão 3: RESERVAS REJEITADAS -->
                    <a href="{{ route('admin.reservas.rejeitadas') }}" class="block p-4 bg-red-100 border border-red-200 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center">
                            <h4 class="text-xl font-bold text-red-700">Rejeitadas</h4>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">
                            Reservas rejeitadas pela administração (e Canceladas pelo Gestor).
                        </p>
                    </a>

                    <!-- ✅ NOVO: Botão 4: TODAS AS RESERVAS -->
                    <a href="{{ route('admin.reservas.todas') }}" class="block p-4 bg-gray-100 border border-gray-300 rounded-xl shadow-lg hover:shadow-xl transition duration-300 transform hover:-translate-y-1">
                        <div class="flex items-center">
                            <h4 class="text-xl font-bold text-gray-700">Todos os horários</h4>
                        </div>
                        <p class="mt-2 text-sm text-gray-600">
                            Visão completa de todos os slots (Livre, Manutenção, Clientes, Cancelados, etc.).
                        </p>
                    </a>
                    {{-- FIM NOVO BOTÃO --}}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
