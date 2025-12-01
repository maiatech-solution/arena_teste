<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Configuração de Horários da Arena') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                        <p>{{ session('error') }}</p>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                        <p class="font-bold">Erros de Validação:</p>
                        <ul class="mt-2 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">
                    <span class="text-indigo-600">1.</span> Configuração Semanal Recorrente
                </h3>

                {{-- FORMULÁRIO DE CONFIGURAÇÃO --}}
                <form action="{{ route('admin.config.store') }}" method="POST">
                    @csrf

                    {{-- 🛑 NOVO CAMPO OCULTO: Define a recorrência automática de 6 meses --}}
                    <input type="hidden" name="recurrent_months" value="6">

                    {{-- INFORMAÇÃO CRÍTICA SOBRE A RECORRÊNCIA --}}
                    <div class="p-4 mb-6 text-sm bg-blue-50 border-l-4 border-blue-400 text-blue-800 rounded">
                        <p class="font-semibold">Atenção:</p>
                        <p>O salvamento desta configuração limpará e recriará os slots disponíveis para os **próximos 6 meses**. Isso garante que o calendário reflita suas regras de preço e horário. Reservas de clientes existentes (confirmadas) não serão afetadas.</p>
                    </div>
                    {{-- FIM DA INFORMAÇÃO --}}

                    @foreach (\App\Models\ArenaConfiguration::DAY_NAMES as $dayOfWeek => $dayName)
                        @php
                            $dayConfig = $dayConfigurations[$dayOfWeek] ?? [];
                            $isDayActive = \App\Models\ArenaConfiguration::where('day_of_week', $dayOfWeek)->value('is_active') ?? false;
                        @endphp

                        <div class="mb-8 p-4 border border-gray-200 rounded-lg shadow-sm {{ $isDayActive ? 'bg-green-50' : 'bg-gray-50' }}">
                            <div class="flex items-center justify-between mb-3 border-b pb-2">
                                <h4 class="font-bold text-lg text-gray-800">{{ $dayName }}</h4>
                                <div class="flex items-center space-x-4">

                                    {{-- Botão de Excluir Dia Inteiro --}}
                                    @if ($isDayActive)
                                        <button type="button"
                                                data-day-of-week="{{ $dayOfWeek }}"
                                                data-day-name="{{ $dayName }}"
                                                class="delete-day-config-btn text-xs px-3 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-150">
                                            Excluir Dia Inteiro
                                        </button>
                                    @endif

                                    {{-- Status Ativo/Inativo --}}
                                    <label class="inline-flex items-center cursor-pointer">
                                        <span class="mr-3 text-sm font-medium text-gray-600">{{ $isDayActive ? 'Ativo' : 'Inativo' }}</span>
                                        <input type="checkbox" name="day_status[{{ $dayOfWeek }}]" value="1" class="sr-only peer" {{ $isDayActive ? 'checked' : '' }}>
                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-green-300 dark:peer-focus:ring-green-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-4" id="slots-container-{{ $dayOfWeek }}">
                                @foreach (array_pad($dayConfig, 3, null) as $index => $slot)
                                    @php
                                        $slot_is_active = $slot['is_active'] ?? (!is_null($slot) ? true : false); // Se há dados, consideramos ativo por padrão
                                    @endphp
                                    <div class="slot-item p-3 border border-dashed border-gray-300 rounded-md {{ $slot_is_active ? 'bg-white' : 'bg-gray-100' }}">
                                        <div class="flex items-center space-x-4">
                                            <span class="font-semibold text-sm text-indigo-600">Slot #{{ $index + 1 }}</span>

                                            <label class="inline-flex items-center space-x-2 text-sm text-gray-600">
                                                <input type="checkbox"
                                                       name="configs[{{ $dayOfWeek }}][{{ $index }}][is_active]"
                                                       value="1"
                                                       class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                                       {{ $slot_is_active ? 'checked' : '' }}>
                                                <span>Ativo</span>
                                            </label>

                                            @if ($slot && $slot_is_active)
                                                <button type="button"
                                                        class="delete-slot-config-btn text-red-500 hover:text-red-700 text-xs font-semibold px-2 py-1 bg-red-100 rounded"
                                                        data-day-of-week="{{ $dayOfWeek }}"
                                                        data-slot-index="{{ $index }}"
                                                        data-start-time="{{ $slot['start_time'] }}"
                                                        data-end-time="{{ $slot['end_time'] }}"
                                                        data-day-name="{{ $dayName }}">
                                                    Excluir Faixa
                                                </button>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-2">
                                            <div class="col-span-1">
                                                <label for="start_time_{{ $dayOfWeek }}_{{ $index }}" class="block text-xs font-medium text-gray-500">Início (HH:MM)</label>
                                                <input type="time"
                                                       name="configs[{{ $dayOfWeek }}][{{ $index }}][start_time]"
                                                       id="start_time_{{ $dayOfWeek }}_{{ $index }}"
                                                       value="{{ substr($slot['start_time'] ?? '', 0, 5) }}"
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                                                       step="600"> {{-- step="600" para 10 minutos ou "1800" para 30 min --}}
                                            </div>

                                            <div class="col-span-1">
                                                <label for="end_time_{{ $dayOfWeek }}_{{ $index }}" class="block text-xs font-medium text-gray-500">Fim (HH:MM)</label>
                                                <input type="time"
                                                       name="configs[{{ $dayOfWeek }}][{{ $index }}][end_time]"
                                                       id="end_time_{{ $dayOfWeek }}_{{ $index }}"
                                                       value="{{ substr($slot['end_time'] ?? '', 0, 5) }}"
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                                                       step="600">
                                            </div>

                                            <div class="col-span-1">
                                                <label for="default_price_{{ $dayOfWeek }}_{{ $index }}" class="block text-xs font-medium text-gray-500">Preço Padrão (R$)</label>
                                                <input type="number"
                                                       name="configs[{{ $dayOfWeek }}][{{ $index }}][default_price]"
                                                       id="default_price_{{ $dayOfWeek }}_{{ $index }}"
                                                       value="{{ $slot['default_price'] ?? '' }}"
                                                       step="0.01" min="0"
                                                       placeholder="Ex: 50.00"
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm text-right">
                                            </div>

                                            <div class="col-span-1">
                                                {{-- Campo Oculto para o dia da semana --}}
                                                <input type="hidden"
                                                       name="configs[{{ $dayOfWeek }}][{{ $index }}][day_of_week]"
                                                       value="{{ $dayOfWeek }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <button type="button" onclick="addSlot({{ $dayOfWeek }})" class="mt-4 text-sm px-4 py-2 bg-indigo-500 text-white rounded-lg hover:bg-indigo-600 transition duration-150">
                                + Adicionar Faixa de Horário
                            </button>
                        </div>
                    @endforeach

                    <div class="mt-8 pt-4 border-t border-gray-200">
                        <button type="submit" class="px-6 py-3 bg-green-600 text-white font-bold rounded-lg shadow-lg hover:bg-green-700 transition duration-150">
                            Salvar Configuração e Gerar Slots (6 Meses)
                        </button>
                    </div>
                </form>

                <h3 class="text-lg font-bold text-gray-900 mt-12 mb-4 border-b pb-2">
                    <span class="text-indigo-600">2.</span> Próximas Reservas/Slots Fixos (Disponibilidade)
                </h3>

                {{-- Tabela para visualizar slots fixos futuros --}}
                <div class="overflow-x-auto">
                    @if ($fixedReservas->isEmpty())
                        <p class="text-gray-500 italic">Nenhum slot fixo futuro encontrado. Salve a configuração acima para gerá-los.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horário</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Preço Padrão</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($fixedReservas as $reserva)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }} ({{ \App\Models\ArenaConfiguration::DAY_NAMES[$reserva->day_of_week] ?? '' }})</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ substr($reserva->start_time, 0, 5) }} - {{ substr($reserva->end_time, 0, 5) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-right">
                                            R$ {{ number_format($reserva->price, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                {{ $reserva->status === 'free' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $reserva->status === 'free' ? 'Disponível' : 'Indisponível' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button onclick="toggleFixedSlotStatus({{ $reserva->id }}, '{{ $reserva->status }}')"
                                                    class="text-indigo-600 hover:text-indigo-900 text-xs px-2 py-1 rounded-md transition duration-150">
                                                {{ $reserva->status === 'free' ? 'Tornar Indisponível' : 'Tornar Disponível' }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Modal de Confirmação e Justificativa (Exclusão de Slots/Dia) --}}
    <div id="justification-modal" class="modal-overlay hidden" onclick="closeJustificationModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 id="justification-modal-title" class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Excluir Faixa Recorrente</h3>

            <p id="justification-modal-message" class="text-gray-700 mb-4 font-medium"></p>

            <p id="justification-modal-conflict" class="text-sm bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-3 mb-4 rounded-lg hidden">
                ⚠️ **Atenção:** Existem <span id="conflict-count" class="font-bold">0</span> reservas de clientes futuras que serão CANCELADAS e DELETADAS. Confirme abaixo.
            </p>

            <div class="mb-4">
                <label for="justificativa_gestor" class="block text-sm font-medium text-gray-700 mb-1">Motivo/Justificativa (Obrigatório):</label>
                <textarea id="justificativa_gestor" rows="3" class="w-full p-2 border border-gray-300 rounded-lg" placeholder="Descreva o motivo (Ex: Mudança de preço ou horário)."></textarea>
            </div>

            <input type="hidden" id="delete-action-type">
            <input type="hidden" id="delete-day-of-week">
            <input type="hidden" id="delete-slot-index">
            <input type="hidden" id="delete-start-time">
            <input type="hidden" id="delete-end-time">
            <input type="hidden" id="confirm-cancel" value="0">

            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeJustificationModal()" class="px-4 py-2 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition duration-150">
                    Cancelar
                </button>
                <button type="button" id="confirm-delete-btn" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">
                    Confirmar Exclusão
                </button>
            </div>
        </div>
    </div>


    <script>
        const UPDATE_PRICE_URL = '{{ route("admin.config.update_price", ":reserva") }}';
        const TOGGLE_STATUS_URL = '{{ route("admin.config.update_status", ":reserva") }}';
        const DELETE_SLOT_URL = '{{ route("admin.config.delete_slot_config") }}';
        const DELETE_DAY_URL = '{{ route("admin.config.delete_day_config") }}';
        const csrfToken = document.querySelector('input[name="_token"]').value;

        // Função para Adicionar um Novo Slot Vazio
        function addSlot(dayOfWeek) {
            const container = document.getElementById(`slots-container-${dayOfWeek}`);
            const index = container.children.length;
            const newSlotHtml = `
                <div class="slot-item p-3 border border-dashed border-gray-300 rounded-md bg-white">
                    <div class="flex items-center space-x-4">
                        <span class="font-semibold text-sm text-indigo-600">Slot #${index + 1}</span>

                        <label class="inline-flex items-center space-x-2 text-sm text-gray-600">
                            <input type="checkbox"
                                   name="configs[${dayOfWeek}][${index}][is_active]"
                                   value="1"
                                   class="form-checkbox h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                   checked>
                            <span>Ativo</span>
                        </label>
                        {{-- O botão de exclusão de faixa só aparece em slots salvos --}}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-2">
                        <div class="col-span-1">
                            <label for="start_time_${dayOfWeek}_${index}" class="block text-xs font-medium text-gray-500">Início (HH:MM)</label>
                            <input type="time"
                                   name="configs[${dayOfWeek}][${index}][start_time]"
                                   id="start_time_${dayOfWeek}_${index}"
                                   value=""
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                                   step="600">
                        </div>

                        <div class="col-span-1">
                            <label for="end_time_${dayOfWeek}_${index}" class="block text-xs font-medium text-gray-500">Fim (HH:MM)</label>
                            <input type="time"
                                   name="configs[${dayOfWeek}][${index}][end_time]"
                                   id="end_time_${dayOfWeek}_${index}"
                                   value=""
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm"
                                   step="600">
                        </div>

                        <div class="col-span-1">
                            <label for="default_price_${dayOfWeek}_${index}" class="block text-xs font-medium text-gray-500">Preço Padrão (R$)</label>
                            <input type="number"
                                   name="configs[${dayOfWeek}][${index}][default_price]"
                                   id="default_price_${dayOfWeek}_${index}"
                                   value=""
                                   step="0.01" min="0"
                                   placeholder="Ex: 50.00"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm text-right">
                        </div>

                        <div class="col-span-1">
                            <input type="hidden"
                                   name="configs[${dayOfWeek}][${index}][day_of_week]"
                                   value="${dayOfWeek}">
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', newSlotHtml);
        }

        // Função para Alternar o Status de Slots Individuais
        async function toggleFixedSlotStatus(reservaId, currentStatus) {
            const newStatus = currentStatus === 'free' ? 'cancelled' : 'free';
            const confirmation = currentStatus === 'free'
                ? 'Tem certeza que deseja marcar este slot como INDISPONÍVEL (Manutenção, etc.)?'
                : 'Tem certeza que deseja tornar este slot DISPONÍVEL novamente?';

            if (!confirm(confirmation)) {
                return;
            }

            const url = TOGGLE_STATUS_URL.replace(':reserva', reservaId);
            const data = {
                status: newStatus,
                _token: csrfToken,
                _method: 'POST'
            };

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    alert(result.message);
                    window.location.reload();
                } else {
                    alert(result.error || 'Erro ao alternar o status do slot.');
                }
            } catch (error) {
                console.error('Erro de Rede:', error);
                alert('Erro de conexão ao tentar atualizar o status.');
            }
        }

        // === LÓGICA DO MODAL DE JUSTIFICATIVA E EXCLUSÃO ===

        function openJustificationModal(type, data) {
            const modal = document.getElementById('justification-modal');
            const titleEl = document.getElementById('justification-modal-title');
            const messageEl = document.getElementById('justification-modal-message');
            const conflictEl = document.getElementById('justification-modal-conflict');

            document.getElementById('delete-action-type').value = type;
            document.getElementById('delete-day-of-week').value = data.dayOfWeek;
            document.getElementById('justificativa_gestor').value = '';
            document.getElementById('confirm-cancel').value = 0; // Reseta a confirmação
            conflictEl.classList.add('hidden'); // Oculta o alerta de conflito

            if (type === 'slot') {
                titleEl.textContent = 'Excluir Faixa de Horário Recorrente';
                messageEl.textContent = `Você está excluindo a faixa ${data.startTime.substring(0, 5)} - ${data.endTime.substring(0, 5)} na ${data.dayName}. Isso apagará todos os slots disponíveis futuros desta faixa.`;
                document.getElementById('delete-slot-index').value = data.slotIndex;
                document.getElementById('delete-start-time').value = data.startTime;
                document.getElementById('delete-end-time').value = data.endTime;
            } else if (type === 'day') {
                titleEl.textContent = 'Excluir Dia Recorrente Completo';
                messageEl.textContent = `Você está excluindo TODAS as faixas de horário da ${data.dayName}. Isso apagará todos os slots disponíveis futuros para este dia.`;
            }

            modal.classList.remove('hidden');
        }

        function closeJustificationModal() {
            document.getElementById('justification-modal').classList.add('hidden');
        }

        document.querySelectorAll('.delete-slot-config-btn').forEach(button => {
            button.addEventListener('click', function() {
                openJustificationModal('slot', {
                    dayOfWeek: this.dataset.dayOfWeek,
                    slotIndex: this.dataset.slotIndex,
                    startTime: this.dataset.startTime,
                    endTime: this.dataset.endTime,
                    dayName: this.dataset.dayName
                });
            });
        });

        document.querySelectorAll('.delete-day-config-btn').forEach(button => {
            button.addEventListener('click', function() {
                openJustificationModal('day', {
                    dayOfWeek: this.dataset.dayOfWeek,
                    dayName: this.dataset.dayName
                });
            });
        });

        document.getElementById('confirm-delete-btn').addEventListener('click', async function() {
            const justificativa = document.getElementById('justificativa_gestor').value.trim();
            const actionType = document.getElementById('delete-action-type').value;
            const dayOfWeek = document.getElementById('delete-day-of-week').value;
            const confirmCancel = document.getElementById('confirm-cancel').value;

            if (justificativa.length < 5) {
                alert("Por favor, forneça uma justificativa com pelo menos 5 caracteres.");
                return;
            }

            this.disabled = true;
            this.textContent = 'Processando...';

            let url = actionType === 'slot' ? DELETE_SLOT_URL : DELETE_DAY_URL;
            let data = {
                _token: csrfToken,
                day_of_week: dayOfWeek,
                justificativa_gestor: justificativa,
                confirm_cancel: parseInt(confirmCancel)
            };

            if (actionType === 'slot') {
                data.slot_index = document.getElementById('delete-slot-index').value;
                data.start_time = document.getElementById('delete-start-time').value;
                data.end_time = document.getElementById('delete-end-time').value;
            }

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.status === 409 && result.requires_confirmation) {
                    // Requer confirmação para excluir reservas de clientes
                    document.getElementById('conflict-count').textContent = result.count;
                    document.getElementById('justification-modal-conflict').classList.remove('hidden');
                    document.getElementById('confirm-cancel').value = 1; // Marca para confirmar na próxima tentativa
                    alert(result.message);

                    // Reseta o botão para permitir a segunda tentativa (confirmação)
                    this.disabled = false;
                    this.textContent = 'Confirmar Exclusão (Aviso Aceito)';
                    this.classList.replace('bg-red-600', 'bg-orange-600');

                } else if (response.ok && result.success) {
                    alert(result.message);
                    closeJustificationModal();
                    window.location.reload();
                } else {
                    alert(result.error || result.message || 'Erro ao processar a exclusão.');
                }

            } catch (error) {
                console.error('Erro de Rede:', error);
                alert('Erro de conexão ao tentar excluir a configuração.');
            } finally {
                if (document.getElementById('confirm-cancel').value != 1) {
                    this.disabled = false;
                    this.textContent = 'Confirmar Exclusão';
                }
            }
        });

        // Expor funções globais para uso no HTML
        window.toggleFixedSlotStatus = toggleFixedSlotStatus;
        window.addSlot = addSlot;
        window.closeJustificationModal = closeJustificationModal;
    </script>
</x-app-layout>
