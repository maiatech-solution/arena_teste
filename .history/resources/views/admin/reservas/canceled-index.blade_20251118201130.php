<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Usando shadow-xl e rounded-lg no p-6 para manter a consistência da caixa --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Botão de Volta para o Dashboard de Reservas -->
                <div class="mb-6">
                    <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar ao Painel de Reservas
                    </a>
                </div>

                <!-- PAINEL DE FILTROS COM DATAS E BUSCA -->
                <div class="flex flex-col mb-8 space-y-4">
                    {{-- GRUPO DE FILTROS E PESQUISA --}}
                    <div class="flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-6 w-full">

                        {{-- Botão de Filtro Rápido --}}
                        {{-- ✅ CORREÇÃO: Adicionado mt-6 (em mobile) para compensar o label dos inputs --}}
                        <div class="flex space-x-3 p-1 bg-gray-100 rounded-xl shadow-inner flex-shrink-0 mt-6 md:mt-0">
                            <a href="{{ route('admin.reservas.canceladas') }}"
                                class="px-4 py-2 text-sm font-semibold rounded-lg shadow-md transition duration-150
                                    @if (!isset($search) && !$startDate && !$endDate)
                                        bg-red-600 text-white hover:bg-red-700
                                    @else
                                        text-red-600 hover:bg-white
                                    @endif">
                                Todas Canceladas
                            </a>
                        </div>

                        {{-- Formulário de Pesquisa e Datas --}}
                        <form method="GET" action="{{ route('admin.reservas.canceladas') }}" class="flex flex-col md:flex-row items-end md:items-center space-y-4 md:space-y-0 md:space-x-4 w-full">

                            {{-- FILTROS DE DATA (Agrupados e com bom espaçamento) --}}
                            <div class="flex space-x-3 w-full md:w-auto flex-shrink-0">
                                <div class="w-1/2 md:w-32">
                                    <label for="start_date" class="block text-xs font-semibold text-gray-500 mb-1">De:</label>
                                    <input type="date" name="start_date" id="start_date" value="{{ $startDate ?? '' }}"
                                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 w-full">
                                </div>
                                <div class="w-1/2 md:w-32">
                                    <label for="end_date" class="block text-xs font-semibold text-gray-500 mb-1">Até:</label>
                                    <input type="date" name="end_date" id="end_date" value="{{ $endDate ?? '' }}"
                                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 w-full">
                                </div>
                            </div>

                            {{-- Pesquisa de Texto e Botões de Ação (Agrupados) --}}
                            <div class="flex space-x-2 w-full md:w-auto items-end flex-grow md:flex-grow-0">
                                <div class="flex-grow">
                                    <label for="search" class="block text-xs font-semibold text-gray-500 mb-1">Pesquisar:</label>
                                    <input type="text" name="search" id="search" value="{{ $search ?? '' }}"
                                        placeholder="Nome, contato..."
                                        class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500 shadow-sm transition duration-150 w-full">
                                </div>

                                <div class="flex items-end space-x-1 h-[42px]">
                                    <button type="submit"
                                            class="bg-red-600 hover:bg-red-700 text-white h-full p-2 rounded-lg shadow-md transition duration-150 flex-shrink-0 flex items-center justify-center"
                                            title="Buscar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                                    </button>

                                    @if (isset($search) && $search || $startDate || $endDate)
                                        {{-- Botão Limpar Filtros/Busca --}}
                                        <a href="{{ route('admin.reservas.canceladas') }}"
                                            class="text-red-500 hover:text-red-700 h-full p-2 transition duration-150 flex-shrink-0 flex items-center justify-center rounded-lg border border-red-200"
                                            title="Limpar Busca e Filtros de Data">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- FIM DO PAINEL DE FILTROS -->

                <!-- Tabela de Reservas -->
                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[180px]">
                                    Cliente / Data
                                </th>
                                {{-- Alinhado à direita para números (como em Confirmadas) --}}
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">
                                    Horário / Preço
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[250px]">
                                    Motivo
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">
                                    Gerenciada por
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($reservas as $reserva)
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-red-50 transition duration-150">
                                    {{-- Status --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                                        @php
                                            $isCancelled = $reserva->status === 'cancelled';
                                            $color = $isCancelled ? 'text-red-700 bg-red-100' : 'text-yellow-700 bg-yellow-100';
                                            $statusText = $isCancelled ? 'Cancelada' : 'Rejeitada';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-bold rounded-full {{ $color }} uppercase">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                    {{-- Cliente / Data --}}
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $reserva->client_name ?? optional($reserva->user)->name ?? 'N/A' }}
                                            @if ($reserva->is_recurrent)
                                                <span class="text-xs text-fuchsia-600 font-bold">(RECORR.)</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                        </div>
                                    </td>
                                    {{-- Horário / Preço (Alinhamento de Preço à direita) --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium min-w-[120px]">
                                        <div class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} -
                                            {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        </div>
                                        <div class="text-xs font-bold text-red-700 mt-1">
                                            R$ {{ number_format($reserva->price, 2, ',', '.') }}
                                        </div>
                                    </td>
                                    {{-- Motivo --}}
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-xs min-w-[250px] overflow-hidden truncate">
                                        {{ $reserva->cancellation_reason ?? 'Motivo não registrado' }}
                                    </td>
                                    {{-- Gerenciada por --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 min-w-[120px]">
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-base text-gray-500 italic">
                                        Nenhuma reserva cancelada ou rejeitada encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div class="mt-8">
                    {{-- Inclui filtros de data e busca na paginação --}}
                    {{ $reservas->appends(['search' => $search, 'start_date' => $startDate ?? '', 'end_date' => $endDate ?? ''])->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
