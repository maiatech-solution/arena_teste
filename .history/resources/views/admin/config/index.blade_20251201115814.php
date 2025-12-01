<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Configuração de Horários Recorrentes da Arena') }}
        </h2>
    </x-slot>

    <style>
        /* Estilos CSS existentes */
        .fixed-reserva-status-btn {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            cursor: pointer;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .status-confirmed {
            background-color: #d1fae5; /* Green 100 */
            color: #065f46; /* Green 900 */
        }
        /* ✅ NOVO: Estilo para slot Indisponível (Cancelado) */
        .status-cancelled {
            background-color: #fee2e2; /* Red 100 */
            color: #991b1b; /* Red 900 */
        }
        .price-input {
            width: 80px;
            padding: 4px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }
        .icon-save, .icon-edit {
            cursor: pointer;
            margin-left: 8px;
        }
        .slot-container {
            border: 1px solid #e5e7eb; /* Gray 200 */
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.75rem;
            background-color: #fafafa; /* Gray 50 */
        }

        /* Estilo para o modal de confirmação */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-overlay.hidden {
            display: none !important;
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Notificações (MANTIDAS) --}}
            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if (session('warning'))
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded" role="alert">
                    <p>{{ session('warning') }}</p>
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif
            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                    <p>Houve um erro na validação dos dados. Verifique se os campos foram preenchidos corretamente.</p>
                </div>
            @endif


            {{-- Formulário de Configuração Semanal (MÚLTIPLOS SLOTS) --}}
            <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg mb-8">
                <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Horários de Funcionamento Recorrente (Múltiplas Faixas de Preço)
                    </h3>

                    {{-- ✅ NOVO: MENSAGEM DE PROCESSO AUTOMÁTICO (Mantida a descrição de 1 ano para evitar mexer no front) --}}
                    <div class="mt-4 p-4 bg-blue-100 border border-blue-400 rounded-lg dark:bg-blue-900 dark:border-blue-700 mb-6">
                        <p class="text-sm font-semibold text-blue-800 dark:text-blue-200">
                            ✅ Processo Automático: As reservas fixas (slots disponíveis) são agora **geradas automaticamente** para o próximo ano, logo após você clicar em "Salvar Configuração Semanal".
                        </p>
                    </div>


                    <form id="config-form" action="{{ route('admin.config.store') }}" method="POST">
                        @csrf
                        {{-- 🛑 CAMPO HIDDEN INSERIDO PARA FORÇAR 6 MESES --}}
                        <input type="hidden" name="recurrent_months" value="6">
                        <div class="space-y-6">
                            @php
                                $dayConfigurations = $dayConfigurations ?? [];
                            @endphp

                            @foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName)
                                @php
                                    $slots = $dayConfigurations[$dayOfWeek] ?? [];
                                    $hasSlots = !empty($slots);

                                    // Lógica para determinar se o dia está ativo (pelo menos um slot marcado como ativo)
                                    // is_active na base de dados (is_active do model) é o master.
                                    // A variável $isDayActive é para a UI.
                                    $configModel = \App\Models\ArenaConfiguration::where('day_of_week', $dayOfWeek)->first();
                                    $isDayActive = $configModel ? $configModel->is_active : false;

                                    // Adiciona um placeholder se não houver slots salvos
                                    if (!$hasSlots)
                                    {
                                        $slots[] = ['start_time' => '06:00:00', 'end_time' => '23:00:00', 'default_price' => 100.00, 'is_active' => false];
                                    }
                                @endphp

                                <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-lg shadow-inner">
                                    <div class="flex items-center space-x-4 mb-4 border-b pb-2 justify-between">

                                        {{-- Título e Checkbox Mestre --}}
                                        <div class="flex items-center space-x-4">
                                            <input type="checkbox" name="day_status[{{ $dayOfWeek }}]"
                                                    id="day-active-{{ $dayOfWeek }}" value="1"
                                                    {{ $isDayActive ? 'checked' : '' }}
                                                    class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 day-toggle-master">
                                            <label for="day-active-{{ $dayOfWeek }}" class="text-lg font-bold text-gray-900 dark:text-white">
                                                {{ $dayName }}
                                            </label>
                                        </div>

                                        {{-- 🆕 NOVO: Botão de Exclusão de Dia Inteiro --}}
                                        @if ($isDayActive)
                                        <button type="button"
                                                onclick="deleteDayConfig({{ $dayOfWeek }}, '{{ $dayName }}')"
                                                class="px-3 py-1 bg-red-600 text-white font-semibold rounded-lg shadow-md hover:bg-red-700 transition duration-150 text-xs flex items-center space-x-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            <span>Excluir Dia Recorrente</span>
                                        </button>
                                        @endif
                                        {{-- FIM NOVO BOTÃO DE EXCLUSÃO DE DIA --}}

                                    </div>

                                    {{-- Container para as faixas de preço --}}
                                    <div id="slots-container-{{ $dayOfWeek }}" class="slots-container mt-2"
                                            style="{{ !$isDayActive ? 'display: none;' : '' }}">

                                        @foreach ($slots as $index => $slot)
                                            {{-- Renderiza o Slot Salvo ou o Slot de Placeholder --}}
                                            <div class="slot-item slot-container flex items-center space-x-4 p-3 bg-white dark:bg-gray-600"
                                                    data-day="{{ $dayOfWeek }}"
                                                    data-index="{{ $index }}"
                                                    data-start-time="{{ \Carbon\Carbon::parse($slot['start_time'])->format('H:i:s') }}"
                                                    data-end-time="{{ \Carbon\Carbon::parse($slot['end_time'])->format('H:i:s') }}">

                                                <input type="hidden" name="configs[{{ $dayOfWeek }}][{{ $index }}][day_of_week]" value="{{ $dayOfWeek }}">

                                                {{-- Checkbox de Slot Ativo --}}
                                                <div class="flex items-center">
                                                    <input type="checkbox" name="configs[{{ $dayOfWeek }}][{{ $index }}][is_active]"
                                                            id="slot-active-{{ $dayOfWeek }}-{{ $index }}" value="1"
                                                            {{ (isset($slot['is_active']) && $slot['is_active']) ? 'checked' : '' }}
                                                            class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 slot-active-checkbox"
                                                            {{ !$isDayActive ? 'disabled' : '' }}>
                                                    <label for="slot-active-{{ $dayOfWeek }}-{{ $index }}" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        Ativo
                                                    </label>
                                                </div>

                                                {{-- Horário de Início --}}
                                                <div class="w-1/4">
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Início</label>
                                                    <input type="time" name="configs[{{ $dayOfWeek }}][{{ $index }}][start_time]"
                                                            value="{{ old("configs.$dayOfWeek.$index.start_time", \Carbon\Carbon::parse($slot['start_time'])->format('H:i')) }}"
                                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white time-input"
                                                            {{ !$isDayActive ? 'disabled' : '' }}>
                                                    @error("configs.$dayOfWeek.$index.start_time")
                                                        <p class="text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                {{-- Horário de Fim --}}
                                                <div class="w-1/4">
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Fim</label>
                                                    <input type="time" name="configs[{{ $dayOfWeek }}][{{ $index }}][end_time]"
                                                            value="{{ old("configs.$dayOfWeek.$index.end_time", \Carbon\Carbon::parse($slot['end_time'])->format('H:i')) }}"
                                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white time-input"
                                                            {{ !$isDayActive ? 'disabled' : '' }}>
                                                    @error("configs.$dayOfWeek.$index.end_time")
                                                        <p class="text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                {{-- Preço Padrão --}}
                                                <div class="w-1/4">
                                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Preço (R$)</label>
                                                    <input type="number" step="0.01" name="configs[{{ $dayOfWeek }}][{{ $index }}][default_price]"
                                                            value="{{ old("configs.$dayOfWeek.$index.default_price", $slot['default_price']) }}"
                                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white price-input-config"
                                                            {{ !$isDayActive ? 'disabled' : '' }}>
                                                    @error("configs.$dayOfWeek.$index.default_price")
                                                        <p class="text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </div>

                                                {{-- Botão de Remover Slot --}}
                                                <div class="w-1/12 flex items-center justify-end space-x-2">
                                                    {{-- Botão para REMOVER A FAIXA ESPECÍFICA (com checagem de conflito) --}}
                                                    <button type="button"
                                                            onclick="deleteSlotConfig(this)"
                                                            class="text-red-600 hover:text-red-900 delete-slot-config-btn"
                                                            title="Excluir Faixa de Horário Recorrente"
                                                            data-day="{{ $dayOfWeek }}"
                                                            data-index="{{ $index }}"
                                                            data-start-time="{{ \Carbon\Carbon::parse($slot['start_time'])->format('H:i:s') }}"
                                                            data-end-time="{{ \Carbon\Carbon::parse($slot['end_time'])->format('H:i:s') }}"
                                                            {{ !$isDayActive ? 'disabled' : '' }}>
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach

                                    </div>

                                    {{-- Botão Adicionar Faixa --}}
                                    <div class="mt-3">
                                        <button type="button" class="inline-flex items-center px-3 py-1 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 add-slot-btn"
                                                data-day="{{ $dayOfWeek }}"
                                                {{ !$isDayActive ? 'disabled' : '' }}>
                                            + Adicionar Faixa de Horário
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- ✅ ÚNICO BOTÃO DE SUBMISSÃO (MUITO MAIS SIMPLIS) --}}
                        <div class="flex justify-start mt-8">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                                Salvar Configuração Semanal
                            </button>
                            {{-- 🛑 O BOTÃO MANUAL FOI REMOVIDO DAQUI --}}
                        </div>
                    </form>
                </div>
            </div>

            {{-- ... Tabela de Gerenciamento de Reservas Fixas Geradas ... --}}
             <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg">
                 <div class="p-6 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                      <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Gerenciar Horários Recorrentes Gerados (Próximas Reservas Fixas)</h3>
                      <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Esta lista exibe os próximos slots disponíveis (VERDES). Use os botões para desativar (manutenção) ou reativar.</p>

                      <div class="overflow-x-auto">
                          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                              <thead>
                                  <tr>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horário</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome (Série)</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço (R$)</th>
                                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                  </tr>
                              </thead>
                              <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                  @forelse ($fixedReservas as $reserva)
                                      <tr id="row-{{ $reserva->id }}">
                                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $reserva->id }}</td>
                                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}</td>
                                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                              {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                          </td>
                                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $reserva->client_name }}</td>

                                          {{-- Preço Editável --}}
                                          <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300 flex items-center">
                                              <span id="price-display-{{ $reserva->id }}"
                                                      class="font-semibold text-indigo-600 dark:text-indigo-400">
                                                          {{ number_format($reserva->price, 2, ',', '.') }}
                                                      </span>
                                              <input type="number" step="0.01" id="price-input-{{ $reserva->id }}"
                                                      value="{{ $reserva->price }}"
                                                      class="price-input hidden" data-id="{{ $reserva->id }}">

                                              <span class="icon-edit" id="edit-icon-{{ $reserva->id }}"
                                                      data-id="{{ $reserva->id }}"
                                                      onclick="toggleEdit({{ $reserva->id }}, true)">
                                                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500 hover:text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                              </span>

                                              <span class="icon-save hidden" id="save-icon-{{ $reserva->id }}"
                                                      data-id="{{ $reserva->id }}"
                                                      onclick="updatePrice({{ $reserva->id }})">
                                                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 hover:text-green-800" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                              </span>
                                          </td>

                                          {{-- Status/Ações --}}
                                          <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                              <button id="status-btn-{{ $reserva->id }}"
                                                      class="fixed-reserva-status-btn {{ $reserva->status === 'confirmed' ? 'status-confirmed' : 'status-cancelled' }}"
                                                      data-id="{{ $reserva->id }}"
                                                      data-current-status="{{ $reserva->status }}"
                                                      onclick="toggleStatus({{ $reserva->id }})">
                                                  {{ $reserva->status === 'confirmed' ? 'Disponível' : 'Indisponível (Manutenção)' }}
                                              </button>
                                          </td>
                                      </tr>
                                  @empty
                                      <tr>
                                          <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Nenhuma reserva fixa gerada. Configure os horários acima e salve.</td>
                                      </tr>
                                  @endforelse
                              </tbody>
                          </table>
                      </div>
                  </div>
              </div>
        </div>
    </div>


    {{-- 🆕 NOVO MODAL DE CONFIRMAÇÃO DE EXCLUSÃO (Com checagem de cliente) --}}
    <div id="delete-config-modal" class="modal-overlay hidden" onclick="closeDeleteConfigModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação de Exclusão Recorrente</h3>

            <p id="delete-config-message" class="text-gray-700 mb-4 font-medium"></p>

            {{-- ✅ NOVO CAMPO: Justificativa (Obrigatório) --}}
            <div id="justification-section" class="mb-6">
                <label for="config-justification-input" class="block text-sm font-medium text-gray-700 mb-2">
                    Justificativa da Ação (Obrigatória):
                </label>
                <textarea id="config-justification-input" rows="3" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Motivo pelo qual a faixa/dia será excluído (mínimo 5 caracteres)..."></textarea>
                <p id="justification-error" class="text-xs text-red-500 mt-1 hidden">Por favor, insira uma justificativa válida (mínimo 5 caracteres).</p>
            </div>
            {{-- FIM NOVO CAMPO --}}

            {{-- Alerta de Conflito de Clientes (MANTIDO) --}}
            <p id="delete-config-conflict-warning" class="text-base text-red-600 font-semibold mb-6 p-3 bg-red-100 border border-red-300 rounded hidden">
                ⚠️ <span id="conflict-count">0</span> reserva(s) de cliente futuras serão CANCELADAS e DELETADAS.
            </p>

            <div class="flex justify-end space-x-3">
                <button onclick="closeDeleteConfigModal()" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                    Cancelar
                </button>
                <button id="confirm-delete-config-btn" type="button" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">
                    Continuar
                </button>
            </div>
        </div>
    </div>

    <script>
        // TOKEN CSRF NECESSÁRIO PARA REQUISIÇÕES AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : document.querySelector('input[name="_token"]').value;

        // ✅ INJEÇÃO DAS NOVAS ROTAS
        const UPDATE_STATUS_URL = '{{ route("admin.config.update_status", ":id") }}';
        const UPDATE_PRICE_URL = '{{ route("admin.config.update_price", ":id") }}';
        const DELETE_SLOT_CONFIG_URL = '{{ route("admin.config.delete_slot_config") }}';
        const DELETE_DAY_CONFIG_URL = '{{ route("admin.config.delete_day_config") }}';
        // ===================================

        // Variáveis de estado para o modal de exclusão
        let pendingDeleteAction = {
            type: null, // 'slot' ou 'day'
            dayOfWeek: null,
            slotIndex: null,
            startTime: null,
            endTime: null,
            isConfirmed: false,
            justification: null, // ✅ NOVO CAMPO
        };

        // Contadores para garantir índices únicos ao adicionar novos slots
        const nextIndex = {};

        // Inicializa contadores de índice
        @foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName)
            nextIndex[{{ $dayOfWeek }}] = document.querySelectorAll('#slots-container-{{ $dayOfWeek }} .slot-item').length;
            if (nextIndex[{{ $dayOfWeek }}] === 0) {
                 nextIndex[{{ $dayOfWeek }}] = 1; // Garante que o primeiro slot adicionado seja o 1
            }
        @endforeach


        function updateRemoveButtonState(dayOfWeek) {
            const container = document.getElementById(`slots-container-${dayOfWeek}`);
            const numSlots = container.querySelectorAll('.slot-item').length;
        }

        function updateSlotInputsState(dayOfWeek, isDisabled) {
            const container = document.getElementById(`slots-container-${dayOfWeek}`);
            const inputs = container.querySelectorAll('input[type="time"], input[type="number"], .slot-active-checkbox');
            const addBtn = document.querySelector(`.add-slot-btn[data-day="${dayOfWeek}"]`);
            const deleteBtns = container.querySelectorAll('.delete-slot-config-btn');


            inputs.forEach(input => {
                input.disabled = isDisabled;
            });

            // Desabilita/habilita botões de remover/adicionar
            deleteBtns.forEach(btn => {
                 btn.disabled = isDisabled;
            });

            if (addBtn) addBtn.disabled = isDisabled;
        }

        // --- LÓGICA DE GERENCIAMENTO DE SLOTS (JS) ---

        // 1. Alternância do Dia Mestre
        document.querySelectorAll('.day-toggle-master').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const day = this.id.replace('day-active-', '');
                const isDisabled = !this.checked;
                const container = document.getElementById(`slots-container-${day}`);

                if (!isDisabled) {
                    container.style.display = 'block';
                    // Garante que o checkbox do primeiro slot fica ativo quando o mestre é ativado
                    const firstSlotCheckbox = container.querySelector('.slot-active-checkbox');
                    // Verifica se existe o primeiro slot antes de tentar acessá-lo
                    if (firstSlotCheckbox) {
                        firstSlotCheckbox.checked = true;
                    } else {
                        // Se não houver slots (situação rara na UI, mas pode ocorrer), adiciona um novo placeholder
                         document.querySelector(`.add-slot-btn[data-day="${day}"]`).click();
                    }
                } else {
                    container.style.display = 'none';
                    // Desativa todos os slots
                    container.querySelectorAll('.slot-active-checkbox').forEach(cb => cb.checked = false);
                }

                updateSlotInputsState(day, isDisabled);
                updateRemoveButtonState(day);
            });
        });

        // 2. Adicionar Slot
        document.querySelectorAll('.add-slot-btn').forEach(button => {
            button.addEventListener('click', function() {
                const dayOfWeek = this.dataset.day;
                const container = document.getElementById(`slots-container-${dayOfWeek}`);
                const index = nextIndex[dayOfWeek];

                // Cópia do HTML de um slot de placeholder
                const newSlotHtml = `
                    <div class="slot-item slot-container flex items-center space-x-4 p-3 bg-white dark:bg-gray-600"
                            data-day="${dayOfWeek}"
                            data-index="${index}"
                            data-start-time="08:00:00"
                            data-end-time="12:00:00">
                        <input type="hidden" name="configs[${dayOfWeek}][${index}][day_of_week]" value="${dayOfWeek}">

                        <div class="flex items-center">
                            <input type="checkbox" name="configs[${dayOfWeek}][${index}][is_active]"
                                        id="slot-active-${dayOfWeek}-${index}" value="1" checked
                                        class="h-4 w-4 text-green-600 border-gray-300 rounded focus:ring-green-500 slot-active-checkbox">
                            <label for="slot-active-${dayOfWeek}-${index}" class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                Ativo
                            </label>
                        </div>

                        <div class="w-1/4">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Início</label>
                            <input type="time" name="configs[${dayOfWeek}][${index}][start_time]" value="08:00"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white time-input">
                        </div>

                        <div class="w-1/4">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Fim</label>
                            <input type="time" name="configs[${dayOfWeek}][${index}][end_time]" value="12:00"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white time-input">
                        </div>

                        <div class="w-1/4">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400">Preço (R$)</label>
                            <input type="number" step="0.01" name="configs[${dayOfWeek}][${index}][default_price]" value="120.00"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white price-input-config">
                        </div>

                        <div class="w-1/12 flex items-center justify-end space-x-2">
                            <button type="button"
                                        onclick="deleteSlotConfig(this)"
                                        class="text-red-600 hover:text-red-900 delete-slot-config-btn"
                                        title="Excluir Faixa de Horário Recorrente"
                                        data-day="${dayOfWeek}"
                                        data-index="${index}"
                                        data-start-time="08:00:00"
                                        data-end-time="12:00:00">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </button>
                        </div>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', newSlotHtml);
                nextIndex[dayOfWeek]++;

                updateRemoveButtonState(dayOfWeek);
            });
        });

        // Inicializa o estado dos inputs e botões (no carregamento da página)
        document.addEventListener('DOMContentLoaded', function() {
            @foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName)
                updateRemoveButtonState({{ $dayOfWeek }});
            @endforeach
        });

        // --- LÓGICA DO MODAL DE CONFIRMAÇÃO DE EXCLUSÃO ---

        /**
         * Abre o modal de exclusão e configura a mensagem e alerta de conflito.
         */
        function openDeleteConfigModal(message, conflictCount) {
             document.getElementById('delete-config-message').innerHTML = message;

             // Reinicializa o campo de justificativa
             document.getElementById('config-justification-input').value = '';
             document.getElementById('justification-error').classList.add('hidden');

             const conflictWarning = document.getElementById('delete-config-conflict-warning');
             const conflictCountSpan = document.getElementById('conflict-count');

             if (conflictCount > 0) {
                 conflictCountSpan.textContent = conflictCount;
                 conflictWarning.classList.remove('hidden');
             } else {
                 conflictWarning.classList.add('hidden');
             }

             document.getElementById('delete-config-modal').classList.remove('hidden');
             document.getElementById('delete-config-modal').classList.add('flex');
             document.getElementById('confirm-delete-config-btn').textContent = 'Continuar'; // Botão padrão
        }

        /**
         * Fecha o modal de exclusão e reseta o estado de confirmação.
         */
        function closeDeleteConfigModal() {
             document.getElementById('delete-config-modal').classList.remove('flex');
             document.getElementById('delete-config-modal').classList.add('hidden');
             pendingDeleteAction.isConfirmed = false; // Reseta a confirmação
             pendingDeleteAction.justification = null; // ✅ NOVO: Reseta a justificativa
        }

        /**
         * Função para realizar a chamada AJAX de exclusão (slot ou dia).
         */
        async function executeDeleteAction(isConfirmed) {
            const { type, dayOfWeek, slotIndex, startTime, endTime, justification } = pendingDeleteAction;
            let url = '';
            let payload = {
                day_of_week: dayOfWeek,
                confirm_cancel: isConfirmed ? 1 : 0, // Flag para forçar o cancelamento de clientes
                justificativa_gestor: justification, // ✅ ENVIA JUSTIFICATIVA
                _token: csrfToken
            };

            if (type === 'slot') {
                url = DELETE_SLOT_CONFIG_URL;
                payload.slot_index = slotIndex;
                payload.start_time = startTime;
                payload.end_time = endTime;
            } else if (type === 'day') {
                url = DELETE_DAY_CONFIG_URL;
            } else {
                alert('Erro: Ação de exclusão desconhecida.');
                return;
            }

            const confirmBtn = document.getElementById('confirm-delete-config-btn');
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Processando...';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    credentials: 'include', // 🛑 CRÍTICO: Inclui cookies de sessão/autenticação
                    body: JSON.stringify(payload)
                });

                // 🛑 NOVO: Tratamento robusto para erros de JSON/Sessão
                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    // Se falhar ao parsear JSON (provavelmente HTML de redirecionamento/erro),
                    // criamos uma mensagem de erro baseada no status HTTP.
                    if (response.status === 401 || response.status === 403) {
                         alert('⚠️ ERRO DE SESSÃO/AUTORIZAÇÃO: Você foi desconectado ou não tem permissão. Faça login novamente.');
                         window.location.reload();
                         return; // Sai da função
                    } else if (!response.ok) {
                         result.error = `Erro HTTP ${response.status}: Falha de Comunicação. Recarregue a página.`;
                    } else {
                         result.error = 'Resposta inválida do servidor (Não-JSON).';
                    }
                }
                // 🛑 FIM DO NOVO TRATAMENTO

                if (response.ok && result.success) {
                    alert(result.message);
                    closeDeleteConfigModal();
                    // Recarrega a página para refletir as mudanças no formulário
                    window.location.reload();

                } else if (response.status === 409 && result.requires_confirmation) {
                    // Ocorre o primeiro conflito: Reabre o modal pedindo confirmação de cliente
                    pendingDeleteAction.isConfirmed = true; // Marca que o gestor já inseriu a justificativa

                    const message = result.message + " **Esta ação é irreversível e usará a justificativa que você inseriu.**";

                    openDeleteConfigModal(message, result.count);
                    document.getElementById('confirm-delete-config-btn').textContent = 'Confirmar Exclusão'; // Altera o texto do botão

                } else if (response.status === 422 && result.errors) {
                    // Erro de validação (ex: Justificativa muito curta ou não enviada)
                    const errorMsg = result.errors.justificativa_gestor ? result.errors.justificativa_gestor.join(', ') : 'Erro de validação desconhecido. Verifique se o campo de justificativa está preenchido.';

                    // Exibe o erro de validação no campo (se for o caso)
                    if (result.errors.justificativa_gestor) {
                        document.getElementById('justification-error').textContent = result.errors.justificativa_gestor.join(', ');
                        document.getElementById('justification-error').classList.remove('hidden');
                        document.getElementById('config-justification-input').focus();
                    } else {
                        alert('Erro de Validação: ' + errorMsg);
                    }

                    // Se o erro foi na validação, mantém o modal aberto, mas reativa o botão
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Continuar';

                } else {
                    // Erro 404, 500, ou falha de validação do Controller
                    const finalErrorMsg = result.error || result.message || `Erro de servidor ou validação (Status: ${response.status}).`;
                    alert('Erro ao excluir: ' + finalErrorMsg);
                    closeDeleteConfigModal();
                }
            } catch (error) {
                console.error('Erro de rede ao excluir:', error);
                alert('ERRO DE CONEXÃO COM O SERVIDOR (Network Error): Falha ao comunicar com o backend. Verifique sua conexão e tente novamente.');
                closeDeleteConfigModal();
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Continuar';
            }
        }

        // Listener do botão de Confirmação Final do Modal
        document.getElementById('confirm-delete-config-btn').addEventListener('click', function() {
            // Se já for a 2ª rodada (confirmação de cliente), prossegue imediatamente.
            if (pendingDeleteAction.isConfirmed) {
                executeDeleteAction(true);
                return;
            }

            // Caso contrário, coleta a justificativa e chama a função de checagem.
            const justificationInput = document.getElementById('config-justification-input');
            const justificationError = document.getElementById('justification-error');
            const justification = justificationInput.value.trim();

            if (justification.length < 5) {
                justificationError.textContent = 'Por favor, insira uma justificativa válida (mínimo 5 caracteres).';
                justificationError.classList.remove('hidden');
                justificationInput.focus();
                return;
            }
            justificationError.classList.add('hidden');

            // Armazena a justificativa no estado temporário
            pendingDeleteAction.justification = justification;

            // Dispara a checagem de conflito (com a justificativa pronta)
            executeDeleteAction(false);
        });

        // 3.1. Função para Excluir Slot Específico (Chamada pelo Botão 'X')
        function deleteSlotConfig(buttonElement) {
            const slotItem = buttonElement.closest('.slot-item');
            if (!slotItem) return;

            const dayOfWeek = parseInt(slotItem.dataset.day);
            const slotIndex = parseInt(slotItem.dataset.index);
            // CRÍTICO: Pega os horários do dataset do slot (já estão no formato H:i:s)
            const startTime = slotItem.dataset.startTime;
            const endTime = slotItem.dataset.endTime;

            const dayName = @json(\App\Models\ArenaConfiguration::DAY_NAMES)[dayOfWeek];

            // 1. Configura a ação pendente
            pendingDeleteAction = {
                type: 'slot',
                dayOfWeek: dayOfWeek,
                slotIndex: slotIndex,
                startTime: startTime,
                endTime: endTime,
                isConfirmed: false,
                justification: null,
            };

            // 2. Mensagem Inicial
            const initialMessage = `Tem certeza que deseja remover a faixa de horário recorrente **${startTime.substring(0, 5)} - ${endTime.substring(0, 5)}** do dia ${dayName}?`;

            // 3. Abre o modal e espera a justificativa.
            openDeleteConfigModal(initialMessage, 0);
        }

        // 3.2. Função para Excluir Dia Inteiro (Chamada pelo Botão 'Excluir Dia Recorrente')
        function deleteDayConfig(dayOfWeek, dayName) {

            // 1. Configura a ação pendente
            pendingDeleteAction = {
                type: 'day',
                dayOfWeek: dayOfWeek,
                slotIndex: null,
                startTime: null,
                endTime: null,
                isConfirmed: false,
                justification: null,
            };

            // 2. Mensagem Inicial
            const initialMessage = `Tem certeza que deseja **desativar e remover** TODAS as faixas de horário do dia **${dayName}**?`;

            // 3. Abre o modal e espera a justificativa.
            openDeleteConfigModal(initialMessage, 0);
        }

        // --- Restante da Lógica (Update Price/Status) ---

        function toggleEdit(id, isEditing) {
            const display = document.getElementById(`price-display-${id}`);
            const input = document.getElementById(`price-input-${id}`);
            const editIcon = document.getElementById(`edit-icon-${id}`);
            const saveIcon = document.getElementById(`save-icon-${id}`);
            const statusBtn = document.getElementById(`status-btn-${id}`);

            if (statusBtn) statusBtn.disabled = isEditing;

            if (isEditing) {
                display.classList.add('hidden');
                editIcon.classList.add('hidden');
                input.classList.remove('hidden');
                saveIcon.classList.remove('hidden');
                input.focus();
            } else {
                display.classList.remove('hidden');
                editIcon.classList.remove('hidden');
                input.classList.add('hidden');
                saveIcon.classList.add('hidden');
            }
        }

        async function updatePrice(id) {
            const input = document.getElementById(`price-input-${id}`);
            const newPrice = parseFloat(input.value);

            if (!confirm(`Confirma a alteração do preço para R$ ${newPrice.toFixed(2).replace('.', ',')}?`)) {
                 toggleEdit(id, false);
                 return;
            }

            if (isNaN(newPrice) || newPrice < 0) {
                alert('Preço inválido.');
                return;
            }

            toggleEdit(id, false);
            document.getElementById(`status-btn-${id}`).disabled = true;

            try {
                const url = UPDATE_PRICE_URL.replace(':id', id);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'include', // 🛑 CRÍTICO: Inclui cookies de sessão/autenticação
                    body: JSON.stringify({ price: newPrice })
                });

                // 🛑 NOVO: Tratamento robusto para erros de JSON/Sessão
                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                     if (response.status === 401 || response.status === 403) {
                         alert('⚠️ ERRO DE SESSÃO/AUTORIZAÇÃO: Você foi desconectado ou não tem permissão. Faça login novamente.');
                         window.location.reload();
                         return;
                    } else if (!response.ok) {
                         result.error = `Erro HTTP ${response.status}: Falha de Comunicação. Recarregue a página.`;
                    } else {
                         result.error = 'Resposta inválida do servidor (Não-JSON).';
                    }
                }
                // 🛑 FIM DO NOVO TRATAMENTO

                if (response.ok && result.success) {
                    document.getElementById(`price-display-${id}`).textContent = newPrice.toFixed(2).replace('.', ',');
                    alert(result.message);
                } else {
                    const errorMsg = result.error || result.message || `Erro Desconhecido (Status: ${response.status}).`;
                    alert('Erro ao atualizar preço: ' + errorMsg);
                }
            } catch (error) {
                console.error('Erro de rede ao atualizar preço:', error);
                alert('ERRO DE CONEXÃO COM O SERVIDOR (Network Error): Falha ao comunicar com o backend. Verifique sua conexão e tente novamente.');
            } finally {
                document.getElementById(`status-btn-${id}`).disabled = false;
            }
        }

        async function toggleStatus(id) {
            const button = document.getElementById(`status-btn-${id}`);
            const currentStatus = button.getAttribute('data-current-status');

            const newStatus = currentStatus === 'confirmed' ? 'cancelled' : 'confirmed';

            const actionText = newStatus === 'confirmed' ? 'disponibilizar' : 'marcar como indisponível';

            if (!confirm(`Confirma a ação de ${actionText} o slot ID #${id} no calendário? Se houver reservas de clientes, elas serão canceladas.`)) {
                 return;
            }

            button.disabled = true;
            const originalText = button.textContent; // Salva o texto original
            button.textContent = 'Aguardando...';
            document.getElementById(`edit-icon-${id}`).classList.add('opacity-50', 'pointer-events-none');

            try {
                const url = UPDATE_STATUS_URL.replace(':id', id);

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    credentials: 'include', // 🛑 CRÍTICO: Inclui cookies de sessão/autenticação
                    body: JSON.stringify({ status: newStatus })
                });

                // 🛑 NOVO: Tratamento robusto para erros de JSON/Sessão
                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    // Se falhar ao parsear JSON (provavelmente HTML de redirecionamento/erro),
                    // criamos uma mensagem de erro baseada no status HTTP.
                    if (response.status === 401 || response.status === 403) {
                         alert('⚠️ ERRO DE SESSÃO/AUTORIZAÇÃO: Você foi desconectado ou não tem permissão. Faça login novamente.');
                         window.location.reload();
                         return; // Sai da função
                    } else if (!response.ok) {
                         result.error = `Erro HTTP ${response.status}: Falha de Comunicação. Recarregue a página.`;
                    } else {
                         result.error = 'Resposta inválida do servidor (Não-JSON).';
                    }
                }
                // 🛑 FIM DO NOVO TRATAMENTO

                if (response.ok && result.success) {
                    button.setAttribute('data-current-status', newStatus);

                    if (newStatus === 'confirmed') {
                        button.textContent = 'Disponível';
                        button.classList.remove('status-cancelled');
                        button.classList.add('status-confirmed');
                    } else {
                        button.textContent = 'Indisponível (Manutenção)';
                        button.classList.remove('status-confirmed');
                        button.classList.add('status-cancelled');
                    }
                    alert(result.message + " O calendário público será atualizado.");
                } else {
                    // Resposta do servidor falhou (ex: 404, 500)
                    const errorMsg = result.error || result.message || `Erro Desconhecido (Status: ${response.status}).`;
                    alert('Erro ao atualizar status: ' + errorMsg);
                    button.textContent = originalText; // Reverte o texto em caso de erro
                }

            } catch (error) {
                console.error('Erro de rede ao atualizar status:', error);
                // Erro de conexão (Network Error)
                alert('ERRO DE CONEXÃO COM O SERVIDOR (Network Error): Falha ao comunicar com o backend. Verifique sua conexão e tente novamente.');
                button.textContent = originalText; // Reverte o texto em caso de falha de rede
            } finally {
                button.disabled = false;
                document.getElementById(`edit-icon-${id}`).classList.remove('opacity-50', 'pointer-events-none');
            }
        }
    </script>
</x-app-layout>
