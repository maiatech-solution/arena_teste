<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            ✏️ Editar Horário Fixo (ID: {{ $schedule->id }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 lg:p-8">

                {{-- FORMULÁRIO DE EDIÇÃO --}}
                {{-- A rota é 'update' e usamos o método spoofing @method('PUT') --}}
                <form method="POST" action="{{ route('admin.schedules.update', $schedule) }}">
                    @csrf
                    @method('PUT')

                    <h3 class="text-xl font-semibold mb-6 text-gray-900 dark:text-gray-100 border-b pb-2">
                        Detalhes do Bloco de Horário
                    </h3>

                    {{-- CAMPOS DO FORMULÁRIO --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        {{-- 1. DIA DA SEMANA --}}
                        <div>
                            <x-input-label for="day_of_week" :value="__('Dia da Semana')" />
                            <select id="day_of_week" name="day_of_week" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="" disabled>Selecione o Dia</option>
                                {{-- O old() é para reter o valor em caso de erro de validação --}}
                                <option value="1" {{ old('day_of_week', $schedule->day_of_week) == 1 ? 'selected' : '' }}>Segunda-feira</option>
                                <option value="2" {{ old('day_of_week', $schedule->day_of_week) == 2 ? 'selected' : '' }}>Terça-feira</option>
                                <option value="3" {{ old('day_of_week', $schedule->day_of_week) == 3 ? 'selected' : '' }}>Quarta-feira</option>
                                <option value="4" {{ old('day_of_week', $schedule->day_of_week) == 4 ? 'selected' : '' }}>Quinta-feira</option>
                                <option value="5" {{ old('day_of_week', $schedule->day_of_week) == 5 ? 'selected' : '' }}>Sexta-feira</option>
                                <option value="6" {{ old('day_of_week', $schedule->day_of_week) == 6 ? 'selected' : '' }}>Sábado</option>
                                <option value="7" {{ old('day_of_week', $schedule->day_of_week) == 7 ? 'selected' : '' }}>Domingo</option>
                            </select>
                            <x-input-error :messages="$errors->get('day_of_week')" class="mt-2" />
                        </div>

                        {{-- 2. PREÇO --}}
                        <div>
                            <x-input-label for="price" :value="__('Preço (R$)')" />
                            <x-text-input id="price" name="price" type="number" step="0.01" min="0.01" required
                                class="mt-1 block w-full"
                                {{-- Preenche com o valor atual do banco --}}
                                value="{{ old('price', $schedule->price) }}" />
                            <x-input-error :messages="$errors->get('price')" class="mt-2" />
                        </div>

                        {{-- 3. HORA DE INÍCIO --}}
                        <div>
                            <x-input-label for="start_time" :value="__('Início')" />
                            <x-text-input id="start_time" name="start_time" type="time" required
                                class="mt-1 block w-full"
                                {{-- Preenche com o valor atual do banco --}}
                                value="{{ old('start_time', \Carbon\Carbon::parse($schedule->start_time)->format('H:i')) }}" />
                            <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                        </div>

                        {{-- 4. HORA DE TÉRMINO --}}
                        <div>
                            <x-input-label for="end_time" :value="__('Término')" />
                            <x-text-input id="end_time" name="end_time" type="time" required
                                class="mt-1 block w-full"
                                {{-- Preenche com o valor atual do banco --}}
                                value="{{ old('end_time', \Carbon\Carbon::parse($schedule->end_time)->format('H:i')) }}" />
                            <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                        </div>
                    </div>

                    {{-- 5. ATIVO/INATIVO --}}
                    <div class="mt-6">
                        <label for="is_active" class="flex items-center">
                            <input id="is_active" name="is_active" type="checkbox" value="1"
                                class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                {{-- Verifica o status atual no banco --}}
                                {{ old('is_active', $schedule->is_active) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                Horário Ativo (Clientes podem ver e reservar)
                            </span>
                        </label>
                        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                    </div>

                    {{-- BOTÕES DE AÇÃO --}}
                    <div class="flex items-center justify-end mt-6 space-x-4">
                        <a href="{{ route('admin.schedules.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition duration-150">
                            Cancelar
                        </a>
                        <x-primary-button>
                            {{ __('Salvar Alterações') }}
                        </x-primary-button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>
