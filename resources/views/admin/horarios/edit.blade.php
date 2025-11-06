<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight">
            ✏️ Editar Horário Fixo (ID: {{ $schedule->id }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 lg:p-8">

                {{-- FORMULÁRIO DE EDIÇÃO (UPDATE) --}}
                {{-- AÇÃO CORRIGIDA PARA admin.horarios.update --}}
                <form method="POST" action="{{ route('admin.horarios.update', $schedule) }}">
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
                                {{-- Itera sobre os dias da semana (1=Segunda, 7=Domingo) --}}
                                @foreach ([1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 7 => 'Domingo'] as $value => $label)
                                    <option value="{{ $value }}" {{ old('day_of_week', $schedule->day_of_week) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('day_of_week')" class="mt-2" />
                        </div>

                        {{-- 2. PREÇO --}}
                        <div>
                            <x-input-label for="price" :value="__('Preço (R$)')" />
                            <x-text-input id="price" name="price" type="number" step="0.01" min="0.01" required
                                class="mt-1 block w-full"
                                value="{{ old('price', number_format($schedule->price, 2, '.', '')) }}" />
                            <x-input-error :messages="$errors->get('price')" class="mt-2" />
                        </div>

                        {{-- 3. HORA DE INÍCIO --}}
                        <div>
                            <x-input-label for="start_time" :value="__('Início')" />
                            <x-text-input id="start_time" name="start_time" type="time" required
                                class="mt-1 block w-full"
                                value="{{ old('start_time', \Carbon\Carbon::parse($schedule->start_time)->format('H:i')) }}" />
                            <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
                        </div>

                        {{-- 4. HORA DE TÉRMINO --}}
                        <div>
                            <x-input-label for="end_time" :value="__('Término')" />
                            <x-text-input id="end_time" name="end_time" type="time" required
                                class="mt-1 block w-full"
                                value="{{ old('end_time', \Carbon\Carbon::parse($schedule->end_time)->format('H:i')) }}" />
                            <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
                        </div>
                    </div>

                    {{-- 5. ATIVO/INATIVO --}}
                    <div class="mt-6">
                        <label for="is_active" class="flex items-center">
                            {{-- HIDDEN FIELD: Garante que '0' seja enviado se o checkbox for desmarcado --}}
                            <input type="hidden" name="is_active" value="0">
                            <input id="is_active" name="is_active" type="checkbox" value="1"
                                class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800"
                                {{ old('is_active', $schedule->is_active) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">
                                Horário Ativo (Clientes podem ver e reservar)
                            </span>
                        </label>
                        <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                        <x-input-error :messages="$errors->get('time_conflict')" class="mt-2" />
                    </div>

                    {{-- BOTÕES DE AÇÃO (SALVAR / CANCELAR) --}}
                    <div class="flex items-center justify-end mt-6 space-x-4">
                        {{-- ROTA CORRIGIDA PARA admin.horarios.index --}}
                        <a href="{{ route('admin.horarios.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition duration-150">
                            Cancelar
                        </a>
                        <x-primary-button>
                            {{ __('Salvar Alterações') }}
                        </x-primary-button>
                    </div>
                </form>


                {{--- ZONA DE EXCLUSÃO (DELETE) ---}}
                <div class="mt-10 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-xl font-semibold mb-4 text-red-600 dark:text-red-400">
                        Zona de Exclusão
                    </h3>

                    {{-- Botão que exibe o bloco de confirmação --}}
                    <button
                        onclick="document.getElementById('delete-confirmation-{{ $schedule->id }}').classList.toggle('hidden')"
                        class="px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    >
                        Deletar Horário Fixo
                    </button>

                    {{-- Diálogo de Confirmação (Substitui alert()/confirm()) --}}
                    <div id="delete-confirmation-{{ $schedule->id }}" class="hidden mt-4 p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-600 text-red-700 dark:text-red-300 rounded-lg shadow-inner">
                        <p class="font-bold mb-2">Confirmação de Exclusão</p>
                        <p class="text-sm mb-4">
                            Tem certeza que deseja DELETAR este bloco de horário (ID: {{ $schedule->id }})?
                            Esta ação é <strong class="text-red-900 dark:text-red-100">irreversível</strong>.
                        </p>

                        <div class="flex space-x-3">
                            {{-- Formulário de Exclusão Real --}}
                            {{-- AÇÃO CORRIGIDA PARA admin.horarios.destroy --}}
                            <form method="POST" action="{{ route('admin.horarios.destroy', $schedule) }}">
                                @csrf
                                @method('DELETE')
                                <x-danger-button type="submit">
                                    Sim, Deletar Permanentemente
                                </x-danger-button>
                            </form>

                            {{-- Botão de Cancelar --}}
                            <button
                                onclick="document.getElementById('delete-confirmation-{{ $schedule->id }}').classList.add('hidden')"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 active:bg-gray-200 dark:active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                            >
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
