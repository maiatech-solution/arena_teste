<x-app-layout>
    @php
        $pendingReservationsCount = $pendingReservationsCount ?? 0;
        $expiringSeriesCount = $expiringSeriesCount ?? 0;
        $expiringSeries = $expiringSeries ?? [];
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard | Calendário de Reservas') }}
        </h2>
    </x-slot>

    {{-- IMPORTAÇÕES --}}
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/main.min.css' rel='stylesheet' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <style>
        .calendar-container { max-width: 1000px; margin: 40px auto; padding: 20px; background-color: #ffffff; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .fc { font-family: 'Inter', sans-serif; color: #333; }
        .fc-toolbar-title { font-size: 1.5rem !important; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); display: flex; justify-content: center; align-items: center; z-index: 1000; }
        .modal-overlay.hidden { display: none !important; }

        /* Cores dos Eventos */
        .fc-event-recurrent { background-color: #C026D3 !important; border-color: #A21CAF !important; color: white !important; font-weight: 700 !important; }
        .fc-event-quick { background-color: #4f46e5 !important; border-color: #4338ca !important; color: white !important; }
        .fc-event-no-show { background-color: #E53E3E !important; border-color: #C53030 !important; color: white !important; font-weight: 700 !important; }
        .fc-event-pending { background-color: #ff9800 !important; border-color: #f97316 !important; color: white !important; font-style: italic; }
        .fc-event-paid { background-color: #6B7280 !important; border-color: #4B5563 !important; color: white !important; opacity: 0.5 !important; filter: grayscale(40%); }
        .fc-event-available { background-color: #10B981 !important; border-color: #059669 !important; color: white !important; cursor: pointer; opacity: 0.8; }

        /* Inputs Monetários */
        .input-money-quick, #signal_value_quick, #confirmation-value { text-align: right; }
        #signal_value_quick.bg-indigo-50 { background-color: #eef2ff !important; }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                {{-- Contêiner para Mensagens Dinâmicas --}}
                <div id="dashboard-message-container">
                    @foreach(['success', 'warning', 'error'] as $msg)
                        @if(session($msg))
                            <div class="border-l-4 p-4 mb-4 rounded {{ $msg == 'success' ? 'bg-green-100 border-green-500 text-green-700' : ($msg == 'warning' ? 'bg-yellow-100 border-yellow-500 text-yellow-700' : 'bg-red-100 border-red-500 text-red-700') }}">
                                <p>{{ session($msg) }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- ALERTA DE PENDÊNCIA --}}
                <div id="pending-alert-container">
                    @if ($pendingReservationsCount > 0)
                        <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-6 rounded-lg shadow-md flex justify-between items-center transform hover:scale-[1.005]">
                            <div class="flex items-center">
                                <svg class="h-6 w-6 mr-3 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <div>
                                    <p class="font-bold text-lg">Atenção: Pendências!</p>
                                    <p class="text-sm">Você tem <span class="font-extrabold text-orange-900">{{ $pendingReservationsCount }}</span> pré-reserva(s) aguardando.</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.reservas.pendentes') }}" class="bg-orange-600 text-white font-bold py-2 px-6 rounded-lg text-sm shadow-lg">Revisar</a>
                        </div>
                    @endif
                </div>

                {{-- ALERTA DE RENOVAÇÃO --}}
                @if ($expiringSeriesCount > 0)
                    <div id="renewal-alert-container" data-series='@json($expiringSeries)' class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md">
                        <div class="flex items-start">
                            <svg class="h-6 w-6 mr-3 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <div class="w-full">
                                <p class="font-bold text-lg">ALERTA DE RENOVAÇÃO ({{ $expiringSeriesCount }})</p>
                                <div class="space-y-2 p-3 bg-yellow-50 rounded border border-yellow-200 mt-2">
                                    <p class="font-semibold text-sm text-yellow-800">Próximas Expirações:</p>
                                    @foreach ($expiringSeries as $seriesItem)
                                        <div class="text-xs text-gray-700">
                                            <strong>{{ $seriesItem['client_name'] }}</strong> ({{ $seriesItem['slot_time'] }}) - Expira: {{ \Carbon\Carbon::parse($seriesItem['last_date'])->format('d/m/Y') }}
                                        </div>
                                    @endforeach
                                </div>
                                <button onclick="openRenewalModal()" class="mt-4 bg-yellow-600 text-white font-bold py-2 px-6 rounded-lg text-sm shadow-lg">Revisar Renovações</button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- LEGENDA --}}
                <div class="flex flex-wrap gap-4 mb-4 text-sm font-medium">
                    <div class="flex items-center p-2 bg-fuchsia-50 rounded-lg"><span class="w-4 h-4 rounded-full bg-fuchsia-700 mr-2"></span>Recorrente</div>
                    <div class="flex items-center p-2 bg-indigo-50 rounded-lg"><span class="w-4 h-4 rounded-full bg-indigo-600 mr-2"></span>Avulso</div>
                    <div class="flex items-center p-2 bg-red-50 rounded-lg"><span class="w-4 h-4 rounded-full bg-red-600 mr-2"></span>FALTA</div>
                    <div class="flex items-center p-2 bg-gray-100 rounded-lg"><span class="w-4 h-4 rounded-full bg-gray-400 mr-2 opacity-50"></span>Pago/Resolvido</div>
                    <div class="flex items-center p-2 bg-green-50 rounded-lg"><span class="w-4 h-4 rounded-full bg-green-500 mr-2"></span>Disponível</div>
                </div>

                <div class="calendar-container">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAIS --}}

    {{-- 1. DETALHES --}}
    <div id="event-modal" class="modal-overlay hidden" onclick="closeEventModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">Detalhes da Reserva</h3>
            <div id="modal-content" class="space-y-3 text-gray-700"></div>
            <div id="modal-actions" class="mt-6 w-full space-y-2">
                <button onclick="closeEventModal()" class="w-full px-4 py-2 bg-gray-300 rounded-lg">Fechar</button>
            </div>
        </div>
    </div>

    {{-- 2. AÇÃO PENDENTE --}}
    <div id="pending-action-modal" class="modal-overlay hidden" onclick="closePendingActionModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-orange-600 mb-4 border-b pb-2">Ação Requerida: Pré-Reserva</h3>
            <div id="pending-modal-content" class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg text-gray-700"></div>
            <form id="pending-action-form" onsubmit="return false;">
                @csrf @method('PATCH')
                <input type="hidden" name="reserva_id" id="pending-reserva-id">
                <div id="rejection-reason-area" class="mb-4 hidden">
                    <label class="block text-sm font-medium mb-1">Motivo da Rejeição:</label>
                    <textarea id="rejection-reason" rows="2" class="w-full p-2 border rounded-lg"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Valor do Sinal (R$):</label>
                    <input type="text" name="confirmation_value" id="confirmation-value" class="w-full p-2 border rounded-lg input-money-quick" value="0,00">
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closePendingActionModal()" class="px-4 py-2 bg-gray-300 rounded-lg">Voltar</button>
                    <button type="button" id="reject-pending-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg">Rejeitar</button>
                    <button type="submit" id="confirm-pending-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 3. CANCELAMENTO --}}
    <div id="cancellation-modal" class="modal-overlay hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-all duration-300 scale-95 opacity-0" id="cancellation-modal-content" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação de Cancelamento</h3>
            <p id="modal-message-cancel" class="text-gray-700 mb-4"></p>

            <div id="refund-decision-area" class="mb-6 p-4 border border-red-300 bg-red-50 rounded-lg hidden">
                <p class="font-bold text-red-700 mb-3"><span id="refund-title-text">SINAL PAGO:</span> R$ <span id="refund-signal-value">0,00</span></p>
                <div class="flex flex-col gap-2">
                    <label class="inline-flex items-center"><input type="radio" name="refund_choice" value="keep" checked class="mr-2"> Manter no Caixa</label>
                    <label class="inline-flex items-center"><input type="radio" name="refund_choice" value="refund" class="mr-2"> Devolver (Estornar)</label>
                </div>
            </div>
            <textarea id="cancellation-reason-input" rows="3" class="w-full p-2 border rounded-lg mb-4" placeholder="Motivo do cancelamento..."></textarea>
            <div class="flex justify-end space-x-3">
                <button onclick="closeCancellationModal()" class="px-4 py-2 bg-gray-200 rounded-lg">Fechar</button>
                <button id="confirm-cancellation-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg font-bold">Confirmar</button>
            </div>
            <input type="hidden" id="cancellation-paid-amount-ref">
        </div>
    </div>

    {{-- 4. FALTA (NO-SHOW) --}}
    <div id="no-show-modal" class="modal-overlay hidden" onclick="closeNoShowModal()">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-all duration-300 scale-95 opacity-0" id="no-show-modal-content" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Registrar Falta (No-Show)</h3>
            <p id="no-show-modal-message" class="mb-4 text-gray-700"></p>
            <form id="no-show-form" onsubmit="return false;">
                @csrf @method('PATCH')
                <input type="hidden" name="reserva_id" id="no-show-reserva-id">
                <input type="hidden" name="paid_amount_ref" id="paid-amount-ref">
                <div id="no-show-refund-area" class="mb-6 p-4 border border-red-300 bg-red-50 rounded-lg hidden">
                    <p class="font-bold text-red-700 mb-3">VALOR PAGO: R$ <span id="no-show-paid-amount">0,00</span></p>
                    <div class="flex flex-col space-y-2">
                        <label class="inline-flex items-center"><input type="radio" name="no_show_refund_choice" value="keep" checked class="mr-2"> Manter R$ <span id="keep-amount-display"></span> no Caixa</label>
                        <label class="inline-flex items-center"><input type="radio" name="no_show_refund_choice" value="refund_all" class="mr-2"> Devolver R$ <span id="refund-all-amount-display"></span></label>
                    </div>
                </div>
                <textarea id="no-show-reason-input" name="no_show_reason" rows="3" class="w-full p-2 border rounded-lg mb-4" placeholder="Motivo da falta..."></textarea>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeNoShowModal()" class="px-4 py-2 bg-gray-200 rounded-lg">Voltar</button>
                    <button id="confirm-no-show-btn" type="submit" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg">Confirmar Falta</button>
                </div>
            </form>
        </div>
    </div>

    {{-- 5. RENOVAÇÃO --}}
    <div id="renewal-modal" class="modal-overlay hidden" onclick="closeRenewalModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-yellow-700 mb-4 border-b pb-2">Gerenciar Renovações</h3>
            <div id="renewal-list" class="space-y-4"></div>
            <div class="mt-6 flex justify-end">
                <button onclick="closeRenewalModal()" class="px-4 py-2 bg-gray-300 rounded-lg">Fechar</button>
            </div>
        </div>
    </div>

    {{-- 6. AGENDAMENTO RÁPIDO --}}
    <div id="quick-booking-modal" class="modal-overlay hidden" onclick="this.classList.add('hidden')">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-green-700 mb-4 border-b pb-2">Novo Agendamento</h3>
            <form id="quick-booking-form">
                @csrf
                <div id="slot-info-display" class="mb-4 p-3 bg-gray-50 border rounded-lg text-sm"></div>
                <input type="hidden" name="schedule_id" id="quick-schedule-id">
                <input type="hidden" name="date" id="quick-date">
                <input type="hidden" name="start_time" id="quick-start-time">
                <input type="hidden" name="end_time" id="quick-end-time">
                <input type="hidden" name="price" id="quick-price">
                <input type="hidden" name="reserva_id_to_update" id="reserva-id-to-update">

                <div class="mb-4">
                    <label class="block text-sm font-medium">Nome do Cliente</label>
                    <input type="text" name="client_name" id="client_name" required class="w-full border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium">WhatsApp</label>
                    <input type="tel" name="client_contact" id="client_contact" required maxlength="11" class="w-full border-gray-300 rounded-md">
                    <p id="whatsapp-error-message" class="text-xs text-red-600 hidden">Use 11 dígitos.</p>
                    <div id="client-reputation-display" class="mt-2 text-sm"></div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium">Valor do Sinal (R$)</label>
                    <input type="text" name="signal_value" id="signal_value_quick" value="0,00" class="w-full border-gray-300 rounded-md input-money-quick">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium">Método</label>
                    <select name="payment_method" id="payment_method_quick" required class="w-full border-gray-300 rounded-md">
                        <option value="">Selecione...</option>
                        <option value="pix">PIX</option>
                        <option value="cartao">Cartão</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="mb-4 p-3 bg-indigo-50 border border-indigo-200 rounded-lg">
                    <label class="flex items-center font-bold text-indigo-700">
                        <input type="checkbox" name="is_recurrent" id="is-recurrent" value="1" class="mr-2"> Recorrente (6 Meses)
                    </label>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium">Obs:</label>
                    <textarea name="notes" id="notes" rows="2" class="w-full border-gray-300 rounded-md"></textarea>
                </div>
                <button type="submit" id="submit-quick-booking" class="w-full py-3 bg-green-600 text-white rounded-lg font-bold shadow-lg">Confirmar</button>
            </form>
        </div>
    </div>

    <script>
        // === CONFIGURAÇÕES ===
        const PENDING_API_URL = '{{ route("api.reservas.pendentes.count") }}';
        const CONFIRMED_API_URL = '{{ route("api.reservas.confirmadas") }}';
        const AVAILABLE_API_URL = '{{ route("api.horarios.disponiveis") }}';
        const SHOW_RESERVA_URL = '{{ route("admin.reservas.show", ":id") }}';
        const USER_REPUTATION_URL = '{{ route("api.users.reputation", ":contact") }}';
        const PAYMENT_INDEX_URL = '{{ route("admin.payment.index") }}';
        const RECURRENT_STORE_URL = '{{ route("api.reservas.store_recurrent") }}';
        const QUICK_STORE_URL = '{{ route("api.reservas.store_quick") }}';
        const RENEW_SERIE_URL = '{{ route("admin.reservas.renew_serie", ":masterReserva") }}';
        const CONFIRM_PENDING_URL = '{{ route("admin.reservas.confirmar", ":id") }}';
        const REJECT_PENDING_URL = '{{ route("admin.reservas.rejeitar", ":id") }}';
        const CANCEL_PONTUAL_URL = '{{ route("admin.reservas.cancelar_pontual", ":id") }}';
        const CANCEL_SERIE_URL = '{{ route("admin.reservas.cancelar_serie", ":id") }}';
        const CANCEL_PADRAO_URL = '{{ route("admin.reservas.cancelar", ":id") }}';
        const NO_SHOW_URL = '{{ route("admin.reservas.no_show", ":id") }}';

        const csrfToken = document.querySelector('input[name="_token"]').value;
        let calendar, currentReservaId = null, currentMethod = null, currentUrlBase = null;
        let globalExpiringSeries = [];
        let currentClientStatus = { is_vip: false, reputation_tag: '' };

        // === UTILITÁRIOS ===
        const formatMoneyQuick = (input) => {
            let value = input.value.replace(/\D/g, '');
            if (value.length === 0) return '0,00';
            while (value.length < 3) value = '0' + value;
            return value.substring(0, value.length - 2).replace(/^0+/, '') + ',' + value.substring(value.length - 2);
        };
        const cleanAndConvertForApi = (v) => parseFloat(String(v).replace(/\./g, '').replace(',', '.')) || 0;

        // === CORREÇÃO CRÍTICA: EXIBIÇÃO DE MENSAGENS ===
        function showDashboardMessage(message, type = 'success') {
            const container = document.getElementById('dashboard-message-container');
            if (!container) return;

            const colors = {
                error: 'bg-red-100 border-red-500 text-red-700',
                warning: 'bg-yellow-100 border-yellow-500 text-yellow-700',
                success: 'bg-green-100 border-green-500 text-green-700'
            };

            const alertHtml = `
                <div class="${colors[type] || colors.success} border-l-4 p-4 mb-4 rounded shadow-md transform transition-all duration-500 opacity-0 translate-y-[-10px]" role="alert">
                    <p class="font-bold">${message}</p>
                </div>
            `;

            container.insertAdjacentHTML('afterbegin', alertHtml);
            const newAlert = container.firstElementChild;

            // Força o navegador a renderizar o estado inicial antes da animação
            requestAnimationFrame(() => {
                newAlert.classList.remove('opacity-0', 'translate-y-[-10px]');
            });

            setTimeout(() => {
                if (newAlert) {
                    newAlert.classList.add('opacity-0');
                    setTimeout(() => newAlert.remove(), 500);
                }
            }, 5000);
        }

        // === LÓGICA DO DASHBOARD ===
        document.addEventListener('DOMContentLoaded', () => {
            // Renovações
            const renewalContainer = document.getElementById('renewal-alert-container');
            if (renewalContainer) {
                try { globalExpiringSeries = JSON.parse(renewalContainer.getAttribute('data-series')); } catch (e) {}
            }

            // Inputs Dinheiro
            document.querySelectorAll('.input-money-quick').forEach(input => {
                input.value = formatMoneyQuick(input);
                input.addEventListener('input', e => e.target.value = formatMoneyQuick(e.target));
            });

            // Monitor de Pendências
            setInterval(checkPendingReservations, 30000);
            checkPendingReservations();
        });

        // Checagem de Pendências
        const checkPendingReservations = async () => {
            try {
                const res = await fetch(PENDING_API_URL);
                const data = await res.json();
                const container = document.getElementById('pending-alert-container');
                if ((data.count || 0) > 0) {
                    container.innerHTML = `
                        <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-6 rounded-lg shadow-md flex justify-between items-center animate-pulse">
                            <div class="flex items-center"><span class="font-bold text-lg mr-2">⚠️</span><div><p class="font-bold">Pendências!</p><p class="text-sm">Existem ${data.count} pré-reservas.</p></div></div>
                            <a href="{{ route('admin.reservas.pendentes') }}" class="bg-orange-600 text-white font-bold py-2 px-6 rounded-lg text-sm">Ver</a>
                        </div>`;
                } else {
                    container.innerHTML = '';
                }
            } catch (e) {}
        };

        // Reputação Cliente
        async function fetchClientReputation(contact) {
            const display = document.getElementById('client-reputation-display');
            const signalInput = document.getElementById('signal_value_quick');
            display.innerHTML = '...';

            try {
                const res = await fetch(USER_REPUTATION_URL.replace(':contact', contact));
                const data = await res.json();
                currentClientStatus = { is_vip: data.is_vip, reputation_tag: data.status_tag };

                display.innerHTML = data.status_tag || '<span class="text-green-600 text-xs">Novo/OK</span>';

                if (data.is_vip) {
                    signalInput.value = '0,00';
                    signalInput.classList.add('bg-indigo-50');
                    display.innerHTML += ' <span class="text-indigo-600 font-bold text-xs">VIP</span>';
                } else {
                    signalInput.classList.remove('bg-indigo-50');
                }
            } catch (e) { display.innerHTML = ''; }
        }

        // Validação Contato
        document.getElementById('client_contact').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 11);
            if (this.value.length === 11) {
                document.getElementById('whatsapp-error-message').classList.add('hidden');
                this.classList.add('border-green-500');
                fetchClientReputation(this.value);
            } else {
                this.classList.remove('border-green-500');
            }
        });

        // === SUBMISSÃO AGENDAMENTO RÁPIDO ===
        document.getElementById('quick-booking-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const contact = document.getElementById('client_contact').value;
            if (contact.length !== 11) {
                document.getElementById('whatsapp-error-message').classList.remove('hidden');
                return;
            }

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());
            data.fixed_price = cleanAndConvertForApi(document.getElementById('quick-price').value);
            data.signal_value = cleanAndConvertForApi(data.signal_value);
            delete data.price;

            const btn = document.getElementById('submit-quick-booking');
            btn.disabled = true; btn.textContent = 'Agendando...';

            try {
                const url = document.getElementById('is-recurrent').checked ? RECURRENT_STORE_URL : QUICK_STORE_URL;
                const res = await fetch(url, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    showDashboardMessage(result.message, 'success');
                    document.getElementById('quick-booking-modal').classList.add('hidden');
                    calendar.refetchEvents();
                } else {
                    showDashboardMessage(JSON.stringify(result.errors || result.message), 'warning');
                }
            } catch (e) { showDashboardMessage("Erro de conexão", 'error'); }
            finally { btn.disabled = false; btn.textContent = 'Confirmar'; }
        });

        // === FLUXO DE MODAIS (Eventos Globais) ===
        window.closeEventModal = () => document.getElementById('event-modal').classList.add('hidden');
        window.closePendingActionModal = () => document.getElementById('pending-action-modal').classList.add('hidden');
        window.closeNoShowModal = () => {
            const m = document.getElementById('no-show-modal');
            m.querySelector('#no-show-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => m.classList.add('hidden'), 300);
        };
        window.closeCancellationModal = () => {
            const m = document.getElementById('cancellation-modal');
            m.querySelector('#cancellation-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => m.classList.add('hidden'), 300);
        };
        window.closeRenewalModal = () => document.getElementById('renewal-modal').classList.add('hidden');

        // Cancelamento Lógica
        window.cancelarPontual = (id, isRecurrent, paidVal, isPaid) => {
            openCancellationModal(id, 'PATCH', isRecurrent ? CANCEL_PONTUAL_URL : CANCEL_PADRAO_URL, "Cancelar esta reserva?", paidVal, isPaid);
        };
        window.cancelarSerie = (id, paidVal, isPaid) => {
            openCancellationModal(id, 'DELETE', CANCEL_SERIE_URL, "⚠️ Cancelar a SÉRIE INTEIRA?", paidVal, isPaid);
        };

        function openCancellationModal(id, method, urlBase, msg, paidVal, isPaid) {
            closeEventModal();
            currentReservaId = id; currentMethod = method; currentUrlBase = urlBase;

            const val = cleanAndConvertForApi(paidVal);
            document.getElementById('modal-message-cancel').textContent = msg;
            document.getElementById('cancellation-paid-amount-ref').value = val;

            const area = document.getElementById('refund-decision-area');
            if (val > 0) {
                area.classList.remove('hidden');
                document.getElementById('refund-signal-value').textContent = val.toFixed(2).replace('.',',');
                document.getElementById('refund-title-text').textContent = isPaid ? 'VALOR PAGO:' : 'SINAL PAGO:';
            } else {
                area.classList.add('hidden');
            }

            const modal = document.getElementById('cancellation-modal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.querySelector('#cancellation-modal-content').classList.remove('opacity-0', 'scale-95'), 10);
        }

        document.getElementById('confirm-cancellation-btn').addEventListener('click', async () => {
            const reason = document.getElementById('cancellation-reason-input').value;
            if (reason.length < 5) return showDashboardMessage("Motivo obrigatório (mín 5 chars).", 'warning');

            const refundChoice = document.querySelector('input[name="refund_choice"]:checked')?.value;

            try {
                await fetch(currentUrlBase.replace(':id', currentReservaId), {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                    body: JSON.stringify({
                        _method: currentMethod,
                        cancellation_reason: reason,
                        should_refund: refundChoice === 'refund',
                        paid_amount_ref: document.getElementById('cancellation-paid-amount-ref').value
                    })
                });
                showDashboardMessage("Cancelado com sucesso!", 'success');
                closeCancellationModal();
                calendar.refetchEvents();
            } catch(e) { showDashboardMessage("Erro de rede", 'error'); }
        });

        // No Show Lógica
        window.openNoShowModal = (id, name, paidVal, isPaid) => {
            closeEventModal();
            document.getElementById('no-show-reserva-id').value = id;
            document.getElementById('no-show-modal-message').innerHTML = `Marcar falta para <b>${name}</b>?`;

            const val = cleanAndConvertForApi(paidVal);
            document.getElementById('paid-amount-ref').value = val;
            const area = document.getElementById('no-show-refund-area');

            if (val > 0) {
                area.classList.remove('hidden');
                document.getElementById('no-show-paid-amount').textContent = val.toFixed(2).replace('.',',');
                document.getElementById('keep-amount-display').textContent = val.toFixed(2).replace('.',',');
                document.getElementById('refund-all-amount-display').textContent = val.toFixed(2).replace('.',',');
            } else area.classList.add('hidden');

            const m = document.getElementById('no-show-modal');
            m.classList.remove('hidden');
            setTimeout(() => m.querySelector('#no-show-modal-content').classList.remove('opacity-0', 'scale-95'), 10);
        };

        document.getElementById('confirm-no-show-btn').addEventListener('click', async () => {
            const reason = document.getElementById('no-show-reason-input').value;
            if (reason.length < 5) return showDashboardMessage("Motivo obrigatório.", 'warning');

            const choice = document.querySelector('input[name="no_show_refund_choice"]:checked')?.value;

            try {
                await fetch(NO_SHOW_URL.replace(':id', document.getElementById('no-show-reserva-id').value), {
                    method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken},
                    body: JSON.stringify({ _method: 'PATCH', no_show_reason: reason, should_refund: choice === 'refund_all', paid_amount: document.getElementById('paid-amount-ref').value })
                });
                showDashboardMessage("Falta registrada.", 'success');
                closeNoShowModal();
                calendar.refetchEvents();
            } catch(e) { showDashboardMessage("Erro", 'error'); }
        });

        // Renovação Lógica
        window.openRenewalModal = () => {
            const list = document.getElementById('renewal-list');
            list.innerHTML = globalExpiringSeries.length ? '' : 'Nenhuma renovação pendente.';
            globalExpiringSeries.forEach(s => {
                list.innerHTML += `<div class="border p-2 rounded flex justify-between bg-yellow-50 mb-2"><div><strong>${s.client_name}</strong><br><small>${s.slot_time}</small></div><button onclick="handleRenewal(${s.master_id})" class="bg-yellow-600 text-white px-3 rounded text-sm">Renovar</button></div>`;
            });
            document.getElementById('renewal-modal').classList.remove('hidden');
        };

        window.handleRenewal = async (id) => {
            try {
                const res = await fetch(RENEW_SERIE_URL.replace(':masterReserva', id), {
                    method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken}, body: JSON.stringify({_method:'PATCH'})
                });
                if ((await res.json()).success) {
                    showDashboardMessage("Renovado com sucesso!", 'success');
                    globalExpiringSeries = globalExpiringSeries.filter(s => s.master_id !== id);
                    openRenewalModal();
                } else showDashboardMessage("Erro ao renovar", 'error');
            } catch(e) { showDashboardMessage("Erro rede", 'error'); }
        };

        // Pendência Lógica
        window.openPendingActionModal = (event) => {
            const props = event.extendedProps;
            document.getElementById('pending-reserva-id').value = event.id;
            document.getElementById('confirmation-value').value = parseFloat(props.price).toFixed(2).replace('.', ',');
            document.getElementById('pending-modal-content').innerHTML = `Cliente: <b>${event.title}</b><br>Data: ${moment(event.start).format('DD/MM/YYYY HH:mm')}<br>Valor: R$ ${props.price}`;
            document.getElementById('pending-action-modal').classList.remove('hidden');
        };

        document.getElementById('confirm-pending-btn').addEventListener('click', async () => {
            const val = cleanAndConvertForApi(document.getElementById('confirmation-value').value);
            try {
                await fetch(CONFIRM_PENDING_URL.replace(':id', document.getElementById('pending-reserva-id').value), {
                    method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
                    body: JSON.stringify({_method:'PATCH', signal_value: val})
                });
                showDashboardMessage("Confirmado!", 'success');
                closePendingActionModal();
                calendar.refetchEvents();
            } catch(e){ showDashboardMessage("Erro", 'error'); }
        });

        document.getElementById('reject-pending-btn').addEventListener('click', async () => {
            const area = document.getElementById('rejection-reason-area');
            if (area.classList.contains('hidden')) { area.classList.remove('hidden'); return; }

            const reason = document.getElementById('rejection-reason').value;
            if (reason.length < 5) return showDashboardMessage("Motivo obrigatório.", 'warning');

            try {
                await fetch(REJECT_PENDING_URL.replace(':id', document.getElementById('pending-reserva-id').value), {
                    method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken},
                    body: JSON.stringify({_method:'PATCH', rejection_reason: reason})
                });
                showDashboardMessage("Rejeitado.", 'success');
                closePendingActionModal();
                calendar.refetchEvents();
            } catch(e){ showDashboardMessage("Erro", 'error'); }
        });


        // === FULLCALENDAR ===
        window.onload = function() {
            calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                locale: 'pt-br', initialView: 'dayGridMonth', height: 'auto', slotMinTime: '06:00:00', slotMaxTime: '23:00:00',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
                eventSources: [
                    { url: CONFIRMED_API_URL, method: 'GET' },
                    {
                        id: 'available-slots',
                        events: (info, success, fail) => {
                            fetch(`${AVAILABLE_API_URL}?start=${info.startStr}&end=${info.endStr}`)
                                .then(r => r.json()).then(data => success(data)).catch(fail);
                        }
                    }
                ],
                eventDidMount: function(info) {
                    const props = info.event.extendedProps;
                    const status = props.status;
                    const isAvailable = info.event.classNames.includes('fc-event-available');

                    if (isAvailable) return;

                    // Estilização
                    if (status === 'no_show') { info.el.classList.add('fc-event-no-show'); info.event.setProp('title', '(FALTA) ' + info.event.title); }
                    else if (status === 'pending') { info.el.classList.add('fc-event-pending'); info.event.setProp('title', '(PENDENTE) ' + info.event.title); }
                    else if (status === 'paid' || status === 'concluida' || status === 'lancada_caixa') { info.el.classList.add('fc-event-paid'); info.event.setProp('title', '(PAGO) ' + info.event.title); }
                    else if (status === 'cancelada') { info.el.classList.add('fc-event-paid'); info.event.setProp('title', '(CANCELADO) ' + info.event.title); }
                    else if (moment(info.event.end).isBefore(moment()) && status === 'confirmed') { info.el.classList.add('fc-event-paid'); info.event.setProp('title', '(ATRASADO) ' + info.event.title); } // Atrasado
                    else if (status === 'confirmed') { info.el.classList.add('fc-event-quick'); }

                    if (props.is_recurrent && status !== 'pending') info.el.classList.add('fc-event-recurrent');
                },
                eventClick: function(info) {
                    const e = info.event;
                    const props = e.extendedProps;

                    if (e.classNames.includes('fc-event-available')) {
                        // Abrir Quick
                        if (moment(e.end).isBefore(moment())) return; // Não abre passado
                        document.getElementById('quick-date').value = moment(e.start).format('YYYY-MM-DD');
                        document.getElementById('quick-start-time').value = moment(e.start).format('HH:mm');
                        document.getElementById('quick-end-time').value = moment(e.end).format('HH:mm');
                        document.getElementById('quick-price').value = props.price;
                        document.getElementById('slot-info-display').innerHTML = `Data: ${moment(e.start).format('DD/MM')} - ${moment(e.start).format('HH:mm')}<br>Valor: R$ ${props.price}`;
                        document.getElementById('quick-booking-modal').classList.remove('hidden');
                    } else if (props.status === 'pending') {
                        openPendingActionModal(e);
                    } else {
                        // Modal Detalhes
                        const isPaid = props.is_paid || props.status === 'concluida';
                        const isPast = moment(e.end).isBefore(moment());
                        const isFuture = !isPast;

                        let content = `<p><b>Cliente:</b> ${e.title}</p><p><b>Status:</b> ${props.status.toUpperCase()}</p><p><b>Valor:</b> R$ ${props.price}</p>`;
                        let btns = `<a href="${PAYMENT_INDEX_URL}?reserva_id=${e.id}" class="block w-full text-center ${isPaid ? 'bg-gray-500' : 'bg-green-600'} text-white py-2 rounded mb-2">Ir para Caixa/Pagamento</a>`;

                        if (isFuture && props.status !== 'cancelada') {
                             btns += `<button onclick="cancelarPontual(${e.id}, ${props.is_recurrent}, ${props.total_paid}, ${isPaid})" class="w-full bg-red-600 text-white py-2 rounded mb-2">Cancelar</button>`;
                             if(isPaid) btns += `<button onclick="openNoShowModal(${e.id}, '${e.title}', ${props.total_paid}, true)" class="w-full bg-red-800 text-white py-2 rounded">Marcar Falta (Pago)</button>`;
                        } else if (isPast && props.status === 'confirmed') {
                             btns += `<button onclick="openNoShowModal(${e.id}, '${e.title}', ${props.total_paid}, ${isPaid})" class="w-full bg-red-800 text-white py-2 rounded">Registrar Falta</button>`;
                        }

                        btns += `<button onclick="closeEventModal()" class="w-full bg-gray-300 py-2 rounded mt-2">Fechar</button>`;
                        document.getElementById('modal-content').innerHTML = content;
                        document.getElementById('modal-actions').innerHTML = btns;
                        document.getElementById('event-modal').classList.remove('hidden');
                    }
                }
            });
            calendar.render();
        };
    </script>
</x-app-layout>
