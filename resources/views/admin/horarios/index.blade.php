<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gerenciamento de Disponibilidade') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    {{-- Mensagens de feedback --}}
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- O cabeçalho e os botões de tabulação foram removidos, focando diretamente no gerenciamento avulso. --}}

                    {{-- === FORMULÁRIO DE ADIÇÃO AVULSA === --}}
                    <div class="mb-10 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow-inner">
                        <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Adicionar Novo Horário</h3>

                        <form action="{{ route('admin.horarios.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                            @csrf

                            {{-- Campos Hidden para garantir a compatibilidade com a versão anterior do Controller --}}
                            <input type="hidden" name="is_recurrent" value="0">
                            <input type="hidden" name="day_of_week" value="">


                            {{-- 1. Horário Avulso: Data Específica --}}
                            <div class="md:col-span-2">
                                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Data do Horário</label>
                                <input type="date" name="date" id="date" required
                                    min="{{ now()->toDateString() }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            </div>

                            {{-- 2. Horário Inicial --}}
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Início (HH:MM)</label>
                                <input type="time" name="start_time" id="start_time" required step="300"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            </div>

                            {{-- 3. Horário Final --}}
                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fim (HH:MM)</label>
                                <input type="time" name="end_time" id="end_time" required step="300"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            </div>

                            {{-- 4. Valor --}}
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor (R$)</label>
                                <input type="number" name="price" id="price" required step="0.01" min="0.01" placeholder="100.00"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            </div>

                            {{-- 5. Botão Adicionar --}}
                            <div class="md:col-span-1">
                                <button type="submit"
                                           class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                                    Adicionar
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- === LISTA DE HORÁRIOS AVULSOS (SLOTS ESPECÍFICOS) === --}}
                    <div class="space-y-6">
                        <h3 class="text-2xl font-bold mb-4">Horários Agendáveis</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Estes horários são pontuais, não recorrentes, e são adicionados para **datas específicas**.</p>

                        @if(isset($availableSlots) && $availableSlots->isNotEmpty())
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                @foreach($availableSlots->sortBy('date') as $slot)
                                    <div class="p-4 rounded-lg shadow-sm bg-blue-50 dark:bg-blue-900/50 border border-blue-300 flex flex-col justify-between transition duration-200 hover:shadow-md">
                                        <div class="flex-grow">
                                            {{-- Data e Dia da Semana (USANDO CARBON) --}}
                                            <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1">
                                                {{ \Carbon\Carbon::parse($slot->date)->format('d/m/Y') }}
                                                ({{ \Carbon\Carbon::parse($slot->date)->translatedFormat('l') }})
                                            </p>
                                            {{-- Horário --}}
                                            <p class="font-bold text-base text-gray-900 dark:text-gray-100">
                                                {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                            </p>
                                            {{-- Valor --}}
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                                R$ {{ number_format($slot->price, 2, ',', '.') }}
                                            </p>
                                        </div>
                                        <div class="mt-3 flex justify-end space-x-2">
                                            {{-- Botão de Excluir Avulso (DELETE) --}}
                                            <form action="{{ route('admin.horarios.destroy', $slot) }}" method="POST" class="inline">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" title="Excluir Slot Avulso"
                                                        class="p-2 rounded-full text-gray-400 hover:text-red-700 hover:bg-red-100 dark:hover:bg-red-700 dark:hover:text-red-100 transition duration-150 ease-in-out"
                                                        onclick="return confirm('Tem certeza que deseja excluir este slot avulso? Isso o removerá da disponibilidade.');">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 10-2 0v6a1 1 0 102 0V8z" clip-rule="evenodd" /></svg>
                                                </button>
                                            </form>
                                            {{-- Botão de Editar --}}
                                            <a href="{{ route('admin.horarios.edit', $slot) }}" title="Editar Slot"
                                                class="p-2 rounded-full text-gray-400 hover:text-indigo-700 hover:bg-indigo-100 dark:hover:bg-indigo-700 dark:hover:text-indigo-100 transition duration-150 ease-in-out">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zm-5.464 5.464l-.793.793-2.828-2.828.793-.793 2.828 2.828zm-2.121-2.121l-3.536 3.536A1 1 0 006 13.536V16a1 1 0 001 1h2.464a1 1 0 00.707-.293l3.536-3.536-3.536-3.536z" /></svg>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum slot avulso cadastrado atualmente.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
