<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            💰 Gerenciamento de Caixa & Pagamentos
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            {{-- 1. BARRA DE FILTRO E KPIS (Grid 4 colunas) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                {{-- Card de Filtro de Data --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4 flex flex-col justify-center border border-gray-200 dark:border-gray-700">
                    <form method="GET" action="{{ route('admin.payment.index') }}">
                        <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filtrar Data:</label>
                        <div class="flex gap-2">
                            <input type="date" name="date" id="date" value="{{ $selectedDate }}" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                                onchange="this.form.submit()">
                            
                            {{-- Se estiver filtrando uma reserva específica, adicionamos um botão de reset --}}
                            @if(request()->has('reserva_id'))
                                <a href="{{ route('admin.payment.index', ['date' => $selectedDate]) }}" 
                                   class="px-2 py-1 flex items-center justify-center text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                                   title="Mostrar todas as reservas do dia">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- KPI: Total Recebido (Caixa Real) --}}
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm font-medium text-green-600 dark:text-green-400">Recebido Hoje (Caixa)</div>
                    <div class="mt-1 text-2xl font-bold text-green-700 dark:text-green-300">
                        R$ {{ number_format($totalReceived, 2, ',', '.') }}
                    </div>
                </div>

                {{-- KPI: Pendente (A Receber das Reservas de Hoje) --}}
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Pendente (A Receber)</div>
                    <div class="mt-1 text-2xl font-bold text-yellow-700 dark:text-yellow-300">
                        R$ {{ number_format($totalPending, 2, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500">De um total previsto de R$ {{ number_format($totalExpected, 2, ',', '.') }}</div>
                </div>

                {{-- KPI: Faltas --}}
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                    <div class="text-sm font-medium text-red-600 dark:text-red-400">Faltas (No-Show)</div>
                    <div class="mt-1 text-2xl font-bold text-red-700 dark:text-red-300">
                        {{ $noShowCount }}
                    </div>
                </div>
            </div>

            {{-- 2. TABELA DE RESERVAS --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-semibold mb-4 flex items-center justify-between">
                        @if(request()->has('reserva_id'))
                            <span class="text-indigo-500">Reserva Selecionada (ID: {{ request('reserva_id') }})</span>
                        @else
                            Agendamentos do Dia ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                        @endif
                        
                        {{-- Botão Voltar para a visão diária, se estiver no filtro de ID --}}
                        @if(request()->has('reserva_id'))
                            <a href="{{ route('admin.payment.index', ['date' => $selectedDate]) }}" 
                               class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                Ver Todas do Dia
                            </a>
                        @endif
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Horário</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status Fin.</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total (R$)</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pago (R$)</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Restante</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($reservas as $reserva)
                                    @php
                                        // Cálculos Visuais
                                        $total = $reserva->final_price ?? $reserva->price;
                                        $pago = $reserva->total_paid;
                                        $restante = max(0, $total - $pago);
                                        
                                        // Cor da Linha / Status
                                        $statusClass = match($reserva->payment_status) {
                                            'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                            'partial' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                            'retained' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                            default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        };
                                        $statusLabel = match($reserva->payment_status) {
                                            'paid' => 'Pago',
                                            'partial' => 'Parcial',
                                            'retained' => 'Retido (Falta)',
                                            default => 'Pendente',
                                        };

                                        // Destaque para a linha quando vier do dashboard
                                        $rowHighlight = (isset($highlightReservaId) && $reserva->id == $highlightReservaId) 
                                            ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500' 
                                            : 'hover:bg-gray-50 dark:hover:bg-gray-700';

                                    @endphp
                                    <tr class="{{ $rowHighlight }} transition">
                                        <td class="px-4 py-4 whitespace-nowrap text-sm font-bold">
                                            {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $reserva->client_name }} (ID: {{ $reserva->id }})</div>
                                            <div class="text-xs text-gray-500">
                                                @if($reserva->user && $reserva->user->is_vip)
                                                    <span class="text-indigo-600 font-bold">★ VIP</span>
                                                @endif
                                                {{ $reserva->client_contact }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                                {{ $statusLabel }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                            {{ number_format($total, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                            {{ number_format($pago, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold {{ $restante > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                            {{ number_format($restante, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            @if($reserva->payment_status !== 'paid' && $reserva->status !== 'no_show')
                                                {{-- Botão Pagar --}}
                                                <button onclick="openPaymentModal({{ $reserva->id }}, {{ $total }}, {{ $restante }}, '{{ $reserva->client_name }}')" 
                                                    class="text-white bg-green-600 hover:bg-green-700 rounded px-3 py-1 text-xs mr-2 transition duration-150">
                                                    $ Baixar
                                                </button>
                                                
                                                {{-- Botão Falta --}}
                                                <button onclick="openNoShowModal({{ $reserva->id }}, '{{ $reserva->client_name }}')" 
                                                    class="text-white bg-red-600 hover:bg-red-700 rounded px-3 py-1 text-xs transition duration-150">
                                                    X Falta
                                                </button>
                                            @elseif($reserva->status === 'no_show')
                                                <span class="text-xs text-red-500 italic font-medium">Falta Registrada</span>
                                            @else
                                                <span class="text-xs text-green-500 italic font-medium">Concluído</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                            Nenhum agendamento encontrado para esta data.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- 3. LINK DISCRETO PARA DASHBOARD NO FINAL DA PÁGINA --}}
            <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                <a href="{{ route('admin.financeiro.dashboard') }}" 
                   class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition duration-150">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-8v8m-4-8v8M4 16h16a2 2 0 002-2V8a2 2 0 00-2-2H4a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                    Ir para Relatórios
                </a>
            </div>
            
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- MODAL 1: FINALIZAR PAGAMENTO --}}
    {{-- ================================================================== --}}
    <div id="paymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closePaymentModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form id="paymentForm" method="POST">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                            Finalizar Pagamento
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                Cliente: <span id="modalClientName" class="font-bold"></span>
                            </p>

                            {{-- Valor Final (Editável para Desconto) --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Total Acordado (R$)</label>
                                <input type="number" step="0.01" name="final_price" id="modalFinalPrice" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                                <p class="text-xs text-gray-500 mt-1">Edite este valor apenas se for aplicar um desconto no total.</p>
                            </div>

                            {{-- Valor a Pagar Agora --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Recebido Agora (R$)</label>
                                <input type="number" step="0.01" name="amount_paid" id="modalAmountPaid" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white font-bold text-lg">
                            </div>

                            {{-- Método de Pagamento --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento</label>
                                <select name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                                    <option value="pix">Pix</option>
                                    <option value="money">Dinheiro</option>
                                    <option value="credit_card">Cartão de Crédito</option>
                                    <option value="debit_card">Cartão de Débito</option>
                                    <option value="transfer">Transferência</option>
                                    <option value="other">Outro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Confirmar Recebimento
                        </button>
                        <button type="button" onclick="closePaymentModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================================================================== --}}
    {{-- MODAL 2: REGISTRAR FALTA (NO-SHOW) --}}
    {{-- ================================================================== --}}
    <div id="noShowModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeNoShowModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form id="noShowForm" method="POST">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-red-600 dark:text-red-400" id="modal-title">
                            Registrar Falta (No-Show)
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                Você está registrando que <span id="noShowClientName" class="font-bold"></span> faltou ao horário agendado.
                            </p>
                            <p class="text-sm text-red-500 font-bold mb-4">
                                O status mudará para "Falta" e o sinal pago (se houver) será retido pela casa.
                            </p>

                            {{-- Bloquear Cliente --}}
                            <div class="flex items-center mb-4">
                                <input id="block_user" name="block_user" type="checkbox" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                                <label for="block_user" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                    Adicionar cliente à Lista Negra (Bloquear)
                                </label>
                            </div>

                            {{-- Observações --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observações</label>
                                <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:text-white" placeholder="Motivo ou detalhes..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                            Confirmar Falta
                        </button>
                        <button type="button" onclick="closeNoShowModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- SCRIPT PARA MODAIS (AJUSTES) --}}
    <script>
        // Função customizada para mostrar mensagem (substituindo o alert)
        function showMessage(message, isSuccess = true) {
            // Implementação simples de console.log para evitar alert/confirm
            console.log(isSuccess ? 'SUCESSO: ' : 'ERRO: ', message);
            
            // Em um app real, você implementaria um Toast/Modal bonito aqui.
            // Para este ambiente, vamos recarregar a página e exibir uma mensagem simples
            // ou apenas confiar no reload para mostrar o novo estado.
        }

        // --- Lógica do Pagamento ---
        function openPaymentModal(id, totalPrice, remaining, clientName) {
            const form = document.getElementById('paymentForm');
            form.action = `/admin/pagamentos/${id}/finalizar`;
            
            document.getElementById('modalClientName').innerText = clientName;
            // Garantir que os números sejam exibidos corretamente (sem arredondamento JS estranho)
            document.getElementById('modalFinalPrice').value = totalPrice.toFixed(2); 
            document.getElementById('modalAmountPaid').value = remaining.toFixed(2); 
            
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        // Handle Payment Submit via AJAX 
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const action = this.action;

            fetch(action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                if (response.status >= 400) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Erro de validação ou processamento.');
                    });
                }
                return response.json();
            })
            .then(data => {
                if(data.success) {
                    showMessage('Pagamento registrado com sucesso!');
                    location.reload(); // Recarrega para atualizar tabela e KPIs
                } else {
                    showMessage('Erro: ' + data.message, false);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showMessage('Erro ao processar pagamento: ' + error.message, false);
            });
        });

        // --- Lógica do No-Show ---
        function openNoShowModal(id, clientName) {
            const form = document.getElementById('noShowForm');
            form.action = `/admin/pagamentos/${id}/falta`;
            
            document.getElementById('noShowClientName').innerText = clientName;
            document.getElementById('noShowModal').classList.remove('hidden');
        }

        function closeNoShowModal() {
            document.getElementById('noShowModal').classList.add('hidden');
        }

        document.getElementById('noShowForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const action = this.action;
            
            // Removendo o confirm e usando uma lógica mais direta
            // Em um ambiente real, um modal de confirmação seria ideal

            fetch(action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    showMessage('Falta registrada com sucesso.');
                    location.reload();
                } else {
                    showMessage('Erro: ' + data.message, false);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showMessage('Erro ao registrar falta.', false);
            });
        });
        
        // --- Destaque de Linha (após o reload) ---
        // Se houver um ID de reserva na URL, garante que a rolagem vá para a linha
        document.addEventListener('DOMContentLoaded', () => {
             const urlParams = new URLSearchParams(window.location.search);
             const reservaId = urlParams.get('reserva_id');
             
             if (reservaId) {
                 const highlightedRow = document.querySelector(`.bg-indigo-50`);
                 if (highlightedRow) {
                     // Rola para a reserva destacada
                     highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 }
             }
        });

    </script>
</x-app-layout>