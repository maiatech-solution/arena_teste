<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ isset($schedule) ? '✏️ Editar Horário Fixo' : '➕ Novo Horário Fixo' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 lg:p-8">

                {{-- Determina se é criação (POST) ou edição (PUT/PATCH) --}}
                @if (isset($schedule))
                    <form action="{{ route('admin.schedules.update', $schedule) }}" method="POST">
                        @method('PUT')
                @else
                    <form action="{{ route('admin.schedules.store') }}" method="POST">
                @endif
                    @csrf

                    <h3 class="text-xl font-bold mb-6 text-gray-700 dark:text-gray-300 border-b pb-2">
                        Detalhes do Bloco de Agendamento Semanal
                    </h3>

                    {{-- Exibe erros de validação --}}
                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <strong class="font-bold">Oops!</strong>
                            <span class="block sm:inline"> Houve problemas com os dados fornecidos.</span>
                            <ul class="mt-2 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Campo Dia da Semana --}}
                        <div>
                            <label for="day_of_week" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">Dia da Semana</label>
                            <select name="day_of_week" id="day_of_week" required
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full">
                                <option value="" disabled selected>Selecione o Dia</option>
                                @php
                                    // Array de dias da semana (1=Segunda a 7=Domingo)
                                    $days = [1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 7 => 'Domingo'];
                                @endphp
                                @foreach ($days as $num => $dayName)
                                    <option value="{{ $num }}"
                                        {{ old('day_of_week', $schedule->day_of_week ?? null) == $num ? 'selected' : '' }}>
                                        {{ $dayName }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Campo Preço --}}
                        <div>
                            <label for="price" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">Preço (R$)</label>
                            <input type="number" step="0.01" min="0.01" name="price" id="price" required
                                value="{{ old('price', $schedule->price ?? null) }}"
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full"
                                placeholder="Ex: 50.00">
                        </div>

                        {{-- Campo Hora de Início --}}
                        <div>
                            <label for="start_time" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">Horário de Início</label>
                            <input type="time" name="start_time" id="start_time" required
                                value="{{ old('start_time', $schedule->start_time ?? null) }}"
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full">
                        </div>

                        {{-- Campo Hora de Fim --}}
                        <div>
                            <label for="end_time" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-1">Horário de Fim</label>
                            <input type="time" name="end_time" id="end_time" required
                                value="{{ old('end_time', $schedule->end_time ?? null) }}"
                                class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm w-full">
                        </div>
                    </div>

                    {{-- Checkbox de Ativo --}}
                    <div class="mt-6">
                        <label for="is_active" class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1"
                                {{ old('is_active', $schedule->is_active ?? true) ? 'checked' : '' }}
                                class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Ativo (Permitir agendamentos neste bloco)</span>
                        </label>
                    </div>


                    {{-- Botões de Ação --}}
                    <div class="flex items-center justify-end mt-8 space-x-3">
                        <a href="{{ route('admin.schedules.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 transition ease-in-out duration-150">
                            Cancelar
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ isset($schedule) ? 'Salvar Alterações' : 'Criar Horário' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>
