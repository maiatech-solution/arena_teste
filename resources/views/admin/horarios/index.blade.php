<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Configuração de Horários Fixos (Grade)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

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

                    {{-- Formulário de Adição de Novo Horário --}}
                    <div class="mb-8 p-6 bg-gray-50 dark:bg-gray-700 rounded-lg shadow-md">
                        <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Adicionar Novo Horário Fixo</h3>
                        {{-- ✅ AQUI DEVE SER admin.horarios.store --}}
                        <form action="{{ route('admin.horarios.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
                            @csrf

                            {{-- Dia da Semana --}}
                            <div class="md:col-span-2">
                                <label for="day_of_week" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dia</label>
                                <select name="day_of_week" id="day_of_week" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                                    @foreach($dayNames as $dayNumber => $dayName)
                                        <option value="{{ $dayNumber }}">{{ $dayName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Horário Inicial --}}
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Início (HH:MM)</label>
                                <input type="time" name="start_time" id="start_time" required step="300"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            </div>

                            {{-- Horário Final --}}
                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fim (HH:MM)</label>
                                <input type="time" name="end_time" id="end_time" required step="300"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            </div>

                            {{-- Valor --}}
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor (R$)</label>
                                <input type="number" name="price" id="price" required step="0.01" min="0.01" placeholder="100.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-600 dark:border-gray-500 dark:text-white">
                            </div>

                            {{-- Botão Adicionar --}}
                            <div class="md:col-span-1">
                                <button type="submit"
                                        class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                                    Adicionar
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Lista de Horários Existentes --}}
                    <div class="space-y-8">
                        @forelse($dayNames as $dayNumber => $dayName)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="text-lg font-semibold mb-3">{{ $dayName }}</h4>

                                @if(isset($schedules[$dayNumber]) && $schedules[$dayNumber]->isNotEmpty())
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                        @foreach($schedules[$dayNumber] as $schedule)
                                            <div class="p-3 rounded-lg shadow-sm {{ $schedule->is_active ? 'bg-green-50 dark:bg-green-900/50' : 'bg-red-50 dark:bg-red-900/50 opacity-70' }} flex justify-between items-center transition duration-200 hover:shadow-md">
                                                <div class="flex-grow">
                                                    <p class="font-bold text-sm text-gray-900 dark:text-gray-100">
                                                        {{ $schedule->start_time }} - {{ $schedule->end_time }}
                                                    </p>
                                                    <p class="text-xs text-green-700 dark:text-green-400">
                                                        R$ {{ number_format($schedule->price, 2, ',', '.') }}
                                                    </p>
                                                </div>
                                                <div class="flex space-x-2 ml-4">
                                                    {{-- Botão de Status --}}
                                                    <form action="{{ route('admin.horarios.update_status', $schedule) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('patch')
                                                        <button type="submit" title="{{ $schedule->is_active ? 'Desativar' : 'Ativar' }}"
                                                                class="p-2 rounded-full {{ $schedule->is_active ? 'text-green-600 hover:bg-green-100' : 'text-red-600 hover:bg-red-100' }} transition duration-150 ease-in-out">
                                                            @if ($schedule->is_active)
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                                                            @else
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                                                            @endif
                                                        </button>
                                                    </form>

                                                    {{-- Botão de Excluir --}}
                                                    <form action="{{ route('admin.horarios.destroy', $schedule) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="submit" title="Excluir Horário"
                                                                class="p-2 rounded-full text-gray-400 hover:text-red-700 hover:bg-red-100 transition duration-150 ease-in-out"
                                                                onclick="return confirm('Tem certeza que deseja excluir este horário fixo?');">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm4 0a1 1 0 10-2 0v6a1 1 0 102 0V8z" clip-rule="evenodd" /></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum horário cadastrado para este dia.</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum dia da semana encontrado (erro de lógica de dias).</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
