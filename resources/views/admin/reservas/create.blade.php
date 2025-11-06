<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nova Reserva Manual') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 lg:p-8">

                <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">
                    Agendamento Rápido (Confirmação Imediata)
                </h3>

                {{-- Exibição de Erros e Mensagens de Sessão --}}
                @if (session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 shadow-md" role="alert">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9V6h2v3h-2zm0 4h2v-2h-2v2z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <span class="font-semibold">Erro de Agendamento:</span>
                                <span class="block sm:inline ml-2">{{ session('error') }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6 shadow-md">
                        <p class="font-bold mb-2">Por favor, corrija os seguintes erros:</p>
                        <ul class="list-disc ml-5 mt-2 text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                {{-- FIM: Exibição de Erros e Mensagens de Sessão --}}


                <form id="reserva-form" action="{{ route('admin.reservas.store') }}" method="POST" class="space-y-8">
                    @csrf

                    <div class="space-y-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <h4 class="text-xl font-bold text-indigo-700 border-b pb-2">1. Dados do Cliente</h4>

                        <div>
                            {{-- CORRIGIDO: nome_cliente -> client_name --}}
                            <x-input-label for="client_name" :value="__('Nome Completo do Cliente')" class="font-medium" />
                            <x-text-input id="client_name" name="client_name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" :value="old('client_name')" placeholder="Ex: Maria da Silva" required autofocus />
                        </div>

                        <div>
                            {{-- CORRIGIDO: contato_cliente -> client_contact --}}
                            <x-input-label for="client_contact" :value="__('Contato (Telefone ou E-mail)')" class="font-medium" />
                            <x-text-input id="client_contact" name="client_contact" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" :value="old('client_contact')" placeholder="Ex: (99) 99999-9999 ou email@exemplo.com" required />
                            <p class="text-xs text-gray-500 mt-1">Este contato será usado para comunicação sobre a reserva.</p>
                        </div>
                    </div>

                    <div class="space-y-4 p-4 border border-gray-200 rounded-lg bg-gray-50">
                        <h4 class="text-xl font-bold text-indigo-700 border-b pb-2">2. Selecione a Data</h4>

                        <div>
                            {{-- CORRIGIDO: data_reserva -> date --}}
                            <x-input-label for="date" :value="__('Data da Reserva')" class="font-medium" />
                            <x-text-input
                                id="date"
                                name="date"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm transition duration-150 ease-in-out"
                                :value="old('date')"
                                required
                            />

                            <p id="data-disponibilidade-msg" class="text-sm text-red-600 mt-2 hidden font-semibold bg-red-50 p-2 rounded-md border border-red-300">
                                ❌ Por favor, selecione uma data disponível.
                            </p>

                            <p class="text-xs text-gray-500 mt-1">A data deve ter horários ativos configurados em "Gerenciar Horários".</p>
                        </div>
                    </div>

                    <div id="time-selection-container" class="space-y-4 p-4 border border-gray-200 rounded-lg bg-blue-50" style="display: none;">
                        <h4 class="text-xl font-bold text-blue-700 border-b pb-2">3. Selecione o Horário Disponível</h4>

                        <div id="loading-indicator" class="text-center py-6 text-blue-600 font-semibold" style="display: none;">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Carregando horários para esta data...
                        </div>

                        <div id="time-slots-container" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            </div>

                        <p id="no-times-message" class="text-center py-6 text-gray-500 hidden border-t mt-4 pt-4">
                            🎉 Não há horários disponíveis para agendamento nesta data.
                        </p>
                    </div>


                    <div id="reservation-details-container" class="space-y-4 p-4 border border-green-300 rounded-lg bg-green-50" style="display: none;">
                        <h4 class="text-xl font-bold text-green-700 border-b pb-2">Detalhes da Reserva</h4>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-green-800">Horário Selecionado:</label>
                            <p id="selected-time-slot" class="text-2xl font-extrabold text-green-900"></p>
                            <p id="selected-price" class="text-md text-green-700 font-medium"></p>
                        </div>

                        {{-- CORRIGIDO: Nomes dos campos ocultos --}}
                        <input type="hidden" name="date" id="form-data-reserva-hidden">
                        <input type="hidden" name="start_time" id="form-hora-inicio">
                        <input type="hidden" name="end_time" id="form-hora-fim">
                        <input type="hidden" name="price" id="form-preco">
                        <input type="hidden" name="schedule_id" id="form-schedule-id">

                        {{-- CAMPO DE OBSERVAÇÕES OPCIONAL --}}
                        <div>
                            <x-input-label for="notes" :value="__('Observações (Opcional)')" class="font-medium" />
                            <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Detalhes adicionais sobre a reserva (máx. 500 caracteres)">{{ old('notes') }}</textarea>
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-4">
                        <x-primary-button id="submit-button" class="bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 py-3 px-6 text-base font-semibold transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="fas fa-calendar-check mr-2"></i> {{ __('Agendar e Confirmar Reserva') }}
                        </x-primary-button>

                        <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-indigo-600 transition duration-150 ease-in-out flex items-center">
                            <i class="fas fa-arrow-left mr-1"></i> {{ __('Voltar para o Dashboard') }}
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Elementos DOM
            const dataInput = document.getElementById('date'); // CORRIGIDO ID
            const submitButton = document.getElementById('submit-button');
            const msgElement = document.getElementById('data-disponibilidade-msg');
            const timeSelectionContainer = document.getElementById('time-selection-container');
            const timeSlotsContainer = document.getElementById('time-slots-container');
            const loadingIndicator = document.getElementById('loading-indicator');
            const noTimesMessage = document.getElementById('no-times-message');
            const reservationDetailsContainer = document.getElementById('reservation-details-container');

            // Estado
            let selectedDate = null;

            // 1. INJEÇÃO DA LISTA DE DIAS DISPONÍVEIS DO BACKEND (Laravel Blade)
            let availableDates = [];
            try {
                // Tenta fazer o parse da string JSON injetada pelo Controller
                // Utilizamos @json para melhor segurança e compatibilidade com Blade/JS
                // NOTA: $diasDisponiveisJson precisa ser passado do Controller
                const jsonString = '{!! @json($diasDisponiveisJson ?? []) !!}';
                availableDates = JSON.parse(jsonString);
            } catch (e) {
                console.error("Erro ao carregar dias disponíveis do backend:", e);
                availableDates = [];
            }

            // Cria um Set para pesquisa rápida (O(1))
            const availableDatesSet = new Set(availableDates);

            // Define a data mínima como hoje
            const today = new Date().toISOString().split('T')[0];
            dataInput.min = today; // Configura o atributo HTML 'min'

            // Desabilita o botão inicialmente
            submitButton.disabled = true;

            // ====================================================================
            // FUNÇÕES DE VALIDAÇÃO E AJAX
            // ====================================================================

            /**
             * Valida a data e, se válida, chama a API de horários.
             */
            function validateAndLoadTimes() {
                selectedDate = dataInput.value;

                // Esconde se não houver data selecionada
                if (!selectedDate) {
                    timeSelectionContainer.style.display = 'none';
                    reservationDetailsContainer.style.display = 'none';
                    msgElement.classList.add('hidden');
                    dataInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500');
                    submitButton.disabled = true;
                    return;
                }

                let isValid = true;
                let errorMessage = '';

                // 1. Validação de data passada
                if (selectedDate < today) {
                    isValid = false;
                    errorMessage = '❌ Você não pode agendar para uma data que já passou.';
                }

                // 2. Validação de data configurada (Se a data não está no Set)
                else if (!availableDatesSet.has(selectedDate)) {
                    isValid = false;
                    errorMessage = '❌ Data Indisponível. Não há horários ativos configurados para este dia.';
                }

                // Aplica estilos e estado
                if (isValid) {
                    msgElement.classList.add('hidden');
                    dataInput.classList.remove('border-red-500', 'ring-2', 'ring-red-500');

                    // Se a data é válida, carregue os horários!
                    fetchAvailableTimes(selectedDate);

                } else {
                    msgElement.textContent = errorMessage;
                    msgElement.classList.remove('hidden');
                    dataInput.classList.add('border-red-500', 'ring-2', 'ring-red-500');

                    // Esconde se a data for inválida
                    timeSelectionContainer.style.display = 'none';
                    reservationDetailsContainer.style.display = 'none';
                    submitButton.disabled = true;
                }
            }

            /**
             * Chama o endpoint do Controller para buscar horários.
             */
            async function fetchAvailableTimes(date) {
                timeSlotsContainer.innerHTML = '';
                noTimesMessage.classList.add('hidden');
                reservationDetailsContainer.style.display = 'none';

                timeSelectionContainer.style.display = 'block';
                loadingIndicator.style.display = 'block';
                submitButton.disabled = true; // Desabilita o botão até que um slot seja selecionado

                try {
                    // **CHAMADA REAL AO ENDPOINT DO CONTROLLER**
                    // Utilizamos a função route() do Laravel via Blade para garantir a URL correta
                    const url = '{{ route('api.reservas.available-times') }}' + '?date=' + date;
                    const response = await fetch(url);

                    if (!response.ok) {
                         throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const times = await response.json();

                    renderAvailableTimes(times);

                } catch (error) {
                    console.error('Erro ao buscar horários:', error);
                    timeSlotsContainer.innerHTML = '<p class="text-red-500 text-center">Erro ao carregar horários. Tente novamente.</p>';
                    noTimesMessage.classList.remove('hidden');
                } finally {
                    loadingIndicator.style.display = 'none';
                }
            }

            /**
             * Renderiza os horários disponíveis como botões clicáveis.
             */
            function renderAvailableTimes(times) {
                timeSlotsContainer.innerHTML = '';

                if (times.length === 0) {
                    noTimesMessage.classList.remove('hidden');
                    return;
                }

                noTimesMessage.classList.add('hidden');

                times.forEach(slot => {
                    const button = document.createElement('button');
                    // data-slot armazena todos os dados necessários (start_time, end_time, price, schedule_id, raw_price)
                    button.dataset.slot = JSON.stringify(slot);
                    button.className = 'time-slot bg-blue-500 text-white font-bold py-3 px-4 rounded-xl shadow-md hover:bg-blue-600 transition ease-in-out text-center relative text-sm';
                    button.innerHTML = `
                        <span class="text-md font-semibold">${slot.time_slot}</span>
                        <span class="block text-xs font-medium opacity-80 mt-0.5">R$ ${slot.price}</span>
                    `;
                    // Passamos a slot inteira e o próprio botão para a função
                    button.onclick = (e) => selectTimeSlot(e.currentTarget, slot);
                    timeSlotsContainer.appendChild(button);
                });
            }

            /**
             * Trata a seleção de um horário.
             */
            function selectTimeSlot(button, slot) {
                // 1. Remove a seleção de todos os slots
                document.querySelectorAll('#time-slots-container .time-slot').forEach(s => {
                    s.classList.remove('bg-green-600', 'ring-2', 'ring-green-800');
                    s.classList.add('bg-blue-500');
                });

                // 2. Marca o slot selecionado
                button.classList.remove('bg-blue-500');
                button.classList.add('bg-green-600', 'ring-2', 'ring-green-800');

                // 3. Popula os campos ocultos do formulário
                document.getElementById('form-data-reserva-hidden').value = selectedDate;
                document.getElementById('form-hora-inicio').value = slot.start_time;
                document.getElementById('form-hora-fim').value = slot.end_time;
                document.getElementById('form-preco').value = slot.raw_price; // Usa o valor raw (numérico)
                document.getElementById('form-schedule-id').value = slot.schedule_id;

                // 4. Atualiza o display
                document.getElementById('selected-time-slot').textContent = `${slot.time_slot}`;
                document.getElementById('selected-price').textContent = `Valor: R$ ${slot.price}`;

                // 5. Exibe a seção de confirmação e habilita o botão
                reservationDetailsContainer.style.display = 'block';
                submitButton.disabled = false;

                // Opcional: Rolagem suave até o botão de submissão
                submitButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // ====================================================================
            // LISTENERS
            // ====================================================================

            // Adiciona Listeners para validação e carregamento de horários em tempo real
            dataInput.addEventListener('change', validateAndLoadTimes);
            dataInput.addEventListener('blur', validateAndLoadTimes);

            // Validação inicial ao carregar a página (caso haja old('date'))
            validateAndLoadTimes();
        });
    </script>
</x-app-layout>
