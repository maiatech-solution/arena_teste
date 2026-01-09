<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🚫 Reservas Rejeitadas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md border border-green-400 font-bold">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mb-8 flex flex-col md:flex-row md:items-end md:justify-between space-y-4 md:space-y-0 bg-gray-50 dark:bg-gray-700/30 p-4 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm">
                        
                        <div class="flex flex-col space-y-4 w-full md:w-auto">
                            {{-- Botão Voltar --}}
                            <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center w-fit px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                Painel de Reservas
                            </a>

                            {{-- Formulário de Filtros --}}
                            <form method="GET" action="{{ route('admin.reservas.rejeitadas') }}" class="flex flex-col md:flex-row items-end space-y-4 md:space-y-0 md:space-x-3">
                                {{-- Busca por Nome/Contato --}}
                                <div class="w-full md:w-64">
                                    <label for="search" class="block text-[10px] font-bold text-gray-500 uppercase mb-1 ml-1 tracking-widest">Buscar Cliente:</label>
                                    <input type="text" name="search" id="search" value="{{ request('search') }}" 
                                        placeholder="Nome ou contato..." 
                                        class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 transition duration-150">
                                </div>

                                {{-- Filtro por Arena --}}
                                <div class="w-full md:w-48">
                                    <label for="arena_id" class="block text-[10px] font-bold text-gray-500 uppercase mb-1 ml-1 tracking-widest">Filtrar Quadra:</label>
                                    <select name="arena_id" id="arena_id" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 rounded-lg shadow-sm focus:ring-red-500 focus:border-red-500 transition duration-150">
                                        <option value="">Todas as Quadras</option>
                                        @foreach(\App\Models\Arena::all() as $arena)
                                            <option value="{{ $arena->id }}" {{ request('arena_id') == $arena->id ? 'selected' : '' }}>
                                                {{ $arena->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Botões de Ação --}}
                                <div class="flex space-x-2 w-full md:w-auto">
                                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white p-2.5 rounded-lg shadow-md transition duration-150 flex-1 md:flex-none flex justify-center items-center" title="Pesquisar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                    </button>

                                    @if(request('search') || request('arena_id'))
                                        <a href="{{ route('admin.reservas.rejeitadas') }}" class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 p-2.5 rounded-lg hover:bg-gray-300 transition flex-1 md:flex-none flex justify-center items-center" title="Limpar Filtros">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </a>
                                    @endif
                                </div>
                            </form>
                        </div>

                        {{-- Resumo Lateral --}}
                        <div class="text-right border-t md:border-t-0 md:border-l border-gray-200 dark:border-gray-600 pt-4 md:pt-0 md:pl-6">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Total Exibido</span>
                            <span class="text-3xl font-black text-red-600 leading-none">{{ $reservas->total() }}</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Quadra</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data / Horário</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider text-center">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider w-1/4">Motivo da Rejeição</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Gestor</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($reservas as $reserva)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150">
                                        {{-- CLIENTE --}}
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $reserva->client_name }}</div>
                                            <div class="text-[11px] text-gray-500">{{ $reserva->client_contact }}</div>
                                        </td>

                                        {{-- QUADRA --}}
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-[10px] font-black uppercase rounded bg-indigo-100 text-indigo-700 border border-indigo-200 shadow-sm">
                                                {{ $reserva->arena->name ?? 'N/A' }}
                                            </span>
                                        </td>

                                        {{-- DATA / HORÁRIO --}}
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100 font-semibold">
                                                {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-indigo-600 dark:text-indigo-400 font-bold">
                                                {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                            </div>
                                        </td>

                                        {{-- TIPO --}}
                                        <td class="px-4 py-4 whitespace-nowrap text-center">
                                            @if($reserva->is_recurrent)
                                                <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-purple-100 text-purple-700 border border-purple-200">Recorrente</span>
                                            @else
                                                <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded bg-gray-100 text-gray-600 border border-gray-200">Pontual</span>
                                            @endif
                                        </td>

                                        {{-- MOTIVO --}}
                                        <td class="px-4 py-4 text-sm">
                                            <div class="text-red-600 dark:text-red-400 italic text-[13px] leading-tight">
                                                "{{ $reserva->cancellation_reason ?? 'Motivo não informado' }}"
                                            </div>
                                            <div class="text-[10px] text-gray-400 mt-1 font-medium">
                                                Rejeitado em: {{ $reserva->updated_at->format('d/m/Y H:i') }}
                                            </div>
                                        </td>

                                        {{-- GESTOR --}}
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-700 dark:text-gray-300">
                                            <span class="flex items-center">
                                                <svg class="w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                {{ $reserva->manager->name ?? 'Sistema' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-16 text-center text-gray-500 dark:text-gray-400 italic">
                                            <div class="flex flex-col items-center">
                                                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-full mb-4">
                                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                                </div>
                                                <p class="text-base font-bold">Nenhuma reserva rejeitada</p>
                                                <p class="text-sm">Tente ajustar seus filtros ou busca.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- PAGINAÇÃO --}}
                    <div class="mt-8">
                        {{ $reservas->appends(request()->query())->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>