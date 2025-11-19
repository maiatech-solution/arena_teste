<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">

                    {{-- Exibir mensagens de sessão (MANTIDO) --}}
                    @if (session('success'))
                        <div id="session-success" class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-400" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div id="session-error" class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-800 dark:text-red-400" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{-- Container para Mensagens AJAX --}}
                    <div id="ajax-message-container" class="mb-4"></div>

                    <!-- Botão de Volta para o Dashboard de Reservas -->
                    <div class="mb-6">
                        <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Voltar ao Painel de Reservas
                        </a>
                    </div>


                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Cliente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Data
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Horário
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[300px]">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($reservas as $reserva)
                                    <tr id="reserva-row-{{ $reserva->id }}">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $reserva->user->name ?? $reserva->client_name }}
                                            @if ($reserva->client_contact)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $reserva->client_contact }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-yellow-500 dark:text-white">
                                            {{ ucfirst($reserva->status) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2 min-w-[300px]">
                                            @if ($reserva->status === 'pending')

                                                {{-- CONFIRMAR - Com input de preço --}}
                                                <form id="confirm-form-{{ $reserva->id }}"
                                                      action="{{ route('admin.reservas.confirmar', $reserva) }}"
                                                      method="POST"
                                                      class="inline"
                                                      onsubmit="return handleConfirm(event, this, {{ $reserva->id }})">
                                                    @csrf
                                                    @method('PATCH')
                                                    {{-- Usando confirmation_value, que é o nome correto da variável no AdminController --}}
                                                    <input type="number" step="0.01" name="confirmation_value" placeholder="Valor (R$)"
                                                           class="mt-1 inline-block border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-500 dark:text-white price-input-config"
                                                           style="min-width: 120px; text-align: right;" required>
                                                    <button type="submit" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-600 mt-1 font-semibold">
                                                        Confirmar
                                                    </button>
                                                </form>

                                                {{-- REJEITAR - Abre modal para motivo --}}
                                                <button type="button"
                                                        onclick="openRejectionModal({{ $reserva->id }})"
                                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-600 font-semibold ml-2">
                                                    Rejeitar
                                                </button>

                                                {{-- CANCELAR (Removido, pois o fluxo correto para pendente é Rejeitar) --}}

                                            @else
                                                <span class="text-gray-400 text-xs">Ação indisponível</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500 dark:text-gray-400">
                                            🎉 Não há reservas pendentes de confirmação! Tudo em dia.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginação --}}
                    <div class="mt-4">
                        {{ $reservas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE REJEIÇÃO (para coletar o motivo) --}}
    <div id="rejection-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 m-4 transform transition-transform duration-300 scale-95 opacity-0" id="rejection-modal-content" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação de Rejeição</h3>

            <p class="text-gray-700 mb-4">
                Você está prestes a rejeitar esta pré-reserva. O horário será liberado no calendário.
            </p>

            <form id="rejection-form" onsubmit="return handleReject(event)">
                @csrf
                @method('PATCH')
                <input type="hidden" name="reserva_id" id="rejection-reserva-id">

                <div class="mb-6">
                    <label for="rejection-reason" class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo da Rejeição:
                    </label>
                    <textarea id="rejection-reason" name="rejection_reason" rows="3" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Obrigatório, descreva o motivo da rejeição (mínimo 5 caracteres)..." required minlength="5"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeRejectionModal()" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                        Fechar
                    </button>
                    <button type="submit" id="submit-rejection-btn" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">
                        Confirmar Rejeição
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- SCRIPTS DE AÇÃO AJAX --}}
    <script>
        // Variáveis de Rota e Token
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        let currentReservaId = null;

        // =========================================================
        // LÓGICA DO MODAL DE REJEIÇÃO
        // =========================================================

        function openRejectionModal(reservaId) {
            currentReservaId = reservaId;
            document.getElementById('rejection-reserva-id').value = reservaId;
            document.getElementById('rejection-reason').value = '';

            document.getElementById('rejection-modal').classList.remove('hidden');
            document.getElementById('rejection-modal').classList.add('flex');

            // Ativa a transição do modal
            setTimeout(() => {
                document.getElementById('rejection-modal-content').classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        function closeRejectionModal() {
            document.getElementById('rejection-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                document.getElementById('rejection-modal').classList.remove('flex');
                document.getElementById('rejection-modal').classList.add('hidden');
            }, 300);
        }

        // =========================================================
        // FUNÇÕES AJAX (CONFIRMAR E REJEITAR)
        // =========================================================

        /**
         * Exibe uma mensagem de alerta temporária no topo da página.
         */
        function displayAjaxMessage(message, type = 'success') {
            const container = document.getElementById('ajax-message-container');
            let bgColor = 'bg-green-100';
            let textColor = 'text-green-700';

            if (type === 'error') {
                bgColor = 'bg-red-100';
                textColor = 'text-red-700';
            } else if (type === 'warning') {
                bgColor = 'bg-yellow-100';
                textColor = 'text-yellow-700';
            }

            container.innerHTML = `
                <div class="p-4 mb-4 text-sm ${textColor} ${bgColor} rounded-lg shadow-md" role="alert">
                    ${message}
                </div>
            `;
            // Remove as mensagens de sessão estáticas
            document.getElementById('session-success')?.remove();
            document.getElementById('session-error')?.remove();

            // Remove a mensagem após 5 segundos
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }

        /**
         * Lida com o envio AJAX do formulário de Confirmação.
         */
        async function handleConfirm(event, formElement, reservaId) {
            event.preventDefault(); // Impede o envio nativo

            // Se o formulário não for válido (preço não preenchido), para a execução
            if (!formElement.reportValidity()) {
                return;
            }

            const url = formElement.action;
            const submitBtn = formElement.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;

            const formData = new FormData(formElement);
            const data = Object.fromEntries(formData.entries());

            submitBtn.disabled = true;
            submitBtn.textContent = 'Confirmando...';

            await sendAjaxAction(url, data, submitBtn, originalText, reservaId);

            return false;
        }

        /**
         * Lida com o envio AJAX do formulário de Rejeição (dentro do modal).
         */
        async function handleReject(event) {
            event.preventDefault(); // Impede o envio nativo

            const formElement = event.target;

            // Se o formulário não for válido, para a execução
            if (!formElement.reportValidity()) {
                return;
            }

            const reservaId = document.getElementById('rejection-reserva-id').value;
            const url = `{{ route('admin.reservas.rejeitar', ':id') }}`.replace(':id', reservaId);
            const submitBtn = document.getElementById('submit-rejection-btn');

            const formData = new FormData(formElement);
            const data = Object.fromEntries(formData.entries());

            submitBtn.disabled = true;
            submitBtn.textContent = 'Rejeitando...';

            // Adiciona o _method=PATCH manualmente pois o form não tem action
            data['_method'] = 'PATCH';

            await sendAjaxAction(url, data, submitBtn, 'Confirmar Rejeição', reservaId);

            return false;
        }

        /**
         * Função genérica para enviar a requisição AJAX e lidar com a resposta.
         */
        async function sendAjaxAction(url, data, submitBtn, originalText, reservaId) {

            try {
                const response = await fetch(url, {
                    method: 'POST', // Usa POST como método de transporte
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    displayAjaxMessage(result.message);
                    closeRejectionModal();

                    // Remove a linha da reserva da tabela para atualizar a lista
                    document.getElementById(`reserva-row-${reservaId}`)?.remove();

                } else if (response.status === 422 && result.errors) {
                    // Erro de validação (Ex: motivo de rejeição muito curto)
                    const errors = Object.values(result.errors).flat().join('<br>');
                    displayAjaxMessage(`ERRO DE VALIDAÇÃO:<br>${errors}`, 'error');

                } else {
                    // Erro do servidor (400, 500, etc.)
                    displayAjaxMessage(result.message || `Erro desconhecido. Status: ${response.status}.`, 'error');
                }

            } catch (error) {
                console.error('Erro de Rede/Comunicação:', error);
                displayAjaxMessage("Erro de conexão. Tente novamente.", 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    </script>
</x-app-layout>
