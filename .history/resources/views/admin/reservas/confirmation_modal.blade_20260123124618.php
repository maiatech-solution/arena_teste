<div id="confirmReservationModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden overflow-y-auto z-50">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6 m-4">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">
                Confirmar Agendamento Pendente
            </h3>

            <form id="confirmReservationForm" method="POST" action="">
                @csrf
                @method('PATCH')

                <input type="hidden" name="reserva_id" id="modal_reserva_id">

                <div class="space-y-4">
                    {{-- Informações da Reserva --}}
                    <div class="bg-gray-50 p-3 rounded-md space-y-1 border-l-4 border-indigo-500">
                        <p class="text-sm text-gray-700">
                            Cliente: <strong id="modal_client_name" class="text-indigo-600"></strong>
                        </p>
                        <p class="text-sm text-gray-700">
                            Horário: <strong id="modal_reservation_time"></strong>
                        </p>
                        {{-- 🏟️ IDENTIFICAÇÃO DA QUADRA --}}
                        <p class="text-sm text-gray-700">
                            Quadra: <strong id="modal_arena_name" class="text-indigo-700"></strong>
                        </p>
                        <p class="text-sm text-gray-700 font-bold">
                            Preço Base: <span id="modal_reservation_price" class="text-green-600"></span>
                        </p>
                    </div>

                    {{-- Campo de Sinal --}}
                    <div>
                        <label for="signal_value" class="block text-sm font-medium text-gray-700">
                            Valor do Sinal Recebido (R$)
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" step="0.01" min="0" name="signal_value" id="signal_value" required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border"
                                placeholder="0.00">
                        </div>
                    </div>

                    {{-- Método de Pagamento --}}
                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">
                            Forma de Pagamento do Sinal
                        </label>
                        <select name="payment_method" id="payment_method" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border">
                            <option value="pix" selected>Pix</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="cartao">Cardão de Crédito/Débito</option>
                            <option value="transferencia">Transferência Bancária</option>
                        </select>
                    </div>

                    {{-- Checkbox Recorrência --}}
                    <div class="flex items-start p-2 bg-indigo-50 rounded-md">
                        <input type="hidden" name="is_recurrent" value="0">
                        <div class="flex items-center h-5">
                            <input id="is_recurrent" name="is_recurrent" type="checkbox" value="1"
                                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="is_recurrent" class="font-medium text-gray-700 cursor-pointer">
                                Agendar como Série Recorrente
                            </label>
                            <p class="text-gray-500 text-xs">Marque para criar cópias automáticas (Mensalista) nesta quadra por 6 meses.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" onclick="closeConfirmModal()"
                        class="py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Confirmar Reserva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openConfirmModal(reservaId, clientName, reservationTime, price, arenaName) {
        // 1. Exibe o Modal
        document.getElementById('confirmReservationModal').classList.remove('hidden');

        // 2. Preenche os IDs e Dados de Texto
        document.getElementById('modal_reserva_id').value = reservaId;
        document.getElementById('modal_client_name').textContent = clientName;
        document.getElementById('modal_reservation_time').textContent = reservationTime;

        // Identificação da Quadra (Certifique-se de que o ID 'modal_arena_name' existe no HTML do seu modal)
        const arenaElement = document.getElementById('modal_arena_name');
        if (arenaElement) {
            arenaElement.textContent = arenaName;
        }

        // 3. Formatação de Preço para exibição
        document.getElementById('modal_reservation_price').textContent = 'R$ ' + parseFloat(price).toFixed(2).replace('.', ',');

        // 4. Configuração do Formulário
        const form = document.getElementById('confirmReservationForm');
        form.action = `/admin/reservas/confirmar/${reservaId}`;

        // 5. Lógica Financeira e de Recorrência
        // Sugestão: Manter o price como valor padrão, mas deixar o campo focado para edição
        document.getElementById('signal_value').value = parseFloat(price).toFixed(2);
        document.getElementById('is_recurrent').checked = false;

        // 6. Resetar para Pix por padrão
        document.getElementById('payment_method').value = 'pix';
    }

    function closeConfirmModal() {
        document.getElementById('confirmReservationModal').classList.add('hidden');
    }
</script>