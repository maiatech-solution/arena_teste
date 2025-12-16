<x-app-layout>

    @php
        // Garantindo que as variáveis existam, se não forem passadas
        $pendingReservationsCount = $pendingReservationsCount ?? 0;
        $expiringSeriesCount = $expiringSeriesCount ?? 0;
        $expiringSeries = $expiringSeries ?? [];
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard | Calendário de Reservas') }}
        </h2>
    </x-slot>

    {{-- IMPORTAÇÕES (Mantidas) --}}
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/main.min.css' rel='stylesheet' />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <style>
        .calendar-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .fc { font-family: 'Inter', sans-serif; color: #333; }
        .fc-toolbar-title { font-size: 1.5rem !important; }
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex; justify-content: center; align-items: center; z-index: 1000;
        }
        .modal-overlay.hidden { display: none !important; }

        /* Estilos de Eventos */
        .fc-event-recurrent { background-color: #C026D3 !important; border-color: #A21CAF !important; color: white !important; font-weight: 700 !important; }
        .fc-event-quick { background-color: #4f46e5 !important; border-color: #4338ca !important; color: white !important; }
        .fc-event-no-show { background-color: #E53E3E !important; border-color: #C53030 !important; color: white !important; font-weight: 700 !important; }
        .fc-event-pending { background-color: #ff9800 !important; border-color: #f97316 !important; color: white !important; font-style: italic; }
        .fc-event-paid { background-color: #6B7280 !important; border-color: #4B5563 !important; color: white !important; opacity: 0.5 !important; filter: grayscale(40%); }
        .fc-event-available { background-color: #10B981 !important; border-color: #059669 !important; color: white !important; cursor: pointer; opacity: 0.8; }
        .input-money-quick { text-align: right; }

        /* Ajuste de animação para visibilidade */
        .dashboard-alert { transition: all 0.5s ease; }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                {{-- Contêiner para Mensagens Dinâmicas --}}
                <div id="dashboard-message-container" class="min-h-[10px]">
                    @foreach (['success', 'warning', 'error'] as $msg)
                        @if(session($msg))
                            <div class="session-msg border-l-4 p-4 mb-4 rounded shadow-sm {{ $msg == 'success' ? 'bg-green-100 border-green-500 text-green-700' : ($msg == 'warning' ? 'bg-yellow-100 border-yellow-500 text-yellow-700' : 'bg-red-100 border-red-500 text-red-700') }}" role="alert">
                                <p class="font-bold">{{ session($msg) }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div id="pending-alert-container">
                    @if ($pendingReservationsCount > 0)
                        <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-6 rounded-lg shadow-md flex justify-between items-center transform hover:scale-[1.005] transition-all">
                            <div class="flex items-center">
                                <svg class="h-6 w-6 mr-3 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <div>
                                    <p class="font-bold text-lg">Atenção: Pendências!</p>
                                    <p class="text-sm">Existem {{ $pendingReservationsCount }} pré-reservas aguardando.</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.reservas.pendentes') }}" class="bg-orange-600 text-white font-bold py-2 px-6 rounded-lg text-sm shadow-lg">Revisar</a>
                        </div>
                    @endif
                </div>

                @if ($expiringSeriesCount > 0)
                    <div id="renewal-alert-container" data-series='@json($expiringSeries)' class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md">
                        <p class="font-bold">Renovações Próximas ({{ $expiringSeriesCount }})</p>
                        <button onclick="openRenewalModal()" class="mt-2 bg-yellow-600 text-white py-1 px-4 rounded text-xs">Ver Detalhes</button>
                    </div>
                @endif

                <div class="flex flex-wrap gap-4 mb-4 text-sm font-medium">
                    <div class="flex items-center p-2 bg-fuchsia-50 rounded-lg"><span class="w-4 h-4 rounded-full bg-fuchsia-700 mr-2"></span>Fixo</div>
                    <div class="flex items-center p-2 bg-indigo-50 rounded-lg"><span class="w-4 h-4 rounded-full bg-indigo-600 mr-2"></span>Avulso</div>
                    <div class="flex items-center p-2 bg-red-50 rounded-lg"><span class="w-4 h-4 rounded-full bg-red-600 mr-2"></span>FALTA</div>
                    <div class="flex items-center p-2 bg-gray-100 rounded-lg"><span class="w-4 h-4 rounded-full bg-gray-400 mr-2 opacity-50"></span>Pago</div>
                    <div class="flex items-center p-2 bg-green-50 rounded-lg"><span class="w-4 h-4 rounded-full bg-green-500 mr-2"></span>Disponível</div>
                </div>

                <div class="calendar-container">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAIS COMPLETOS --}}
    {{-- MODAL DETALHES --}}
    <div id="event-modal" class="modal-overlay hidden" onclick="closeEventModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">Detalhes da Reserva</h3>
            <div id="modal-content" class="space-y-3 text-gray-700"></div>
            <div id="modal-actions" class="mt-6 w-full space-y-2">
                <button onclick="closeEventModal()" class="w-full px-4 py-2 bg-gray-300 rounded-lg">Fechar</button>
            </div>
        </div>
    </div>

    {{-- MODAL PENDENTE --}}
    <div id="pending-action-modal" class="modal-overlay hidden" onclick="closePendingActionModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-orange-600 mb-4 border-b pb-2">Ação Requerida: Pré-Reserva</h3>
            <div id="pending-modal-content" class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg text-gray-700"></div>
            <form id="pending-action-form">
                @csrf @method('PATCH')
                <input type="hidden" name="reserva_id" id="pending-reserva-id">
                <div id="rejection-reason-area" class="mb-4 hidden">
                    <label class="block text-sm font-medium mb-1">Motivo da Rejeição:</label>
                    <textarea name="rejection_reason" id="rejection-reason" rows="2" class="w-full p-2 border rounded-lg"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium mb-1">Valor do Sinal (R$):</label>
                    <input type="text" name="confirmation_value" id="confirmation-value" class="w-full p-2 border rounded-lg input-money-quick">
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closePendingActionModal()" class="px-4 py-2 bg-gray-300 rounded-lg">Voltar</button>
                    <button type="button" id="reject-pending-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg">Rejeitar</button>
                    <button type="submit" id="confirm-pending-btn" class="px-4 py-2 bg-green-600 text-white rounded-lg">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL AGENDAMENTO RÁPIDO --}}
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
                    <div id="client-reputation-display" class="mt-2 text-sm"></div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Valor do Sinal (R$)</label>
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
                <button type="submit" id="submit-quick-booking" class="w-full py-3 bg-green-600 text-white rounded-lg font-bold shadow-lg">Confirmar</button>
            </form>
        </div>
    </div>
    {{-- MODAIS RESTANTES (CANCELAMENTO, FALTA, RENOVAÇÃO) --}}

    {{-- MODAL CANCELAMENTO --}}
    <div id="cancellation-modal" class="modal-overlay hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-all duration-300 scale-95 opacity-0" id="cancellation-modal-content" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação de Cancelamento</h3>
            <p id="modal-message-cancel" class="text-gray-700 mb-4"></p>

            <div id="refund-decision-area" class="mb-6 p-4 border border-red-300 bg-red-50 rounded-lg hidden">
                <p class="font-bold text-red-700 mb-3">SINAL PAGO: R$ <span id="refund-signal-value">0,00</span></p>
                <p class="text-sm text-gray-700 mb-2 font-medium">O que fazer com o valor?</p>
                <div class="flex flex-col gap-2">
                    <label class="inline-flex items-center"><input type="radio" name="refund_choice" value="keep" checked class="mr-2"> Manter no Caixa (Crédito)</label>
                    <label class="inline-flex items-center"><input type="radio" name="refund_choice" value="refund" class="mr-2"> Devolver (Estornar)</label>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium mb-2">Motivo do Cancelamento:</label>
                <textarea id="cancellation-reason-input" rows="3" class="w-full p-2 border border-gray-300 rounded-lg" placeholder="Descreva o motivo..."></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <button onclick="closeCancellationModal()" class="px-4 py-2 bg-gray-200 rounded-lg">Fechar</button>
                <button id="confirm-cancellation-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg font-bold">Confirmar</button>
            </div>
        </div>
    </div>

    {{-- MODAL FALTA (NO-SHOW) --}}
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
                        <label class="inline-flex items-center p-2 bg-white rounded border">
                            <input type="radio" name="no_show_refund_choice" value="keep" checked class="mr-2">
                            Manter R$ <span id="keep-amount-display"></span> no Caixa
                        </label>
                        <label class="inline-flex items-center p-2 bg-white rounded border">
                            <input type="radio" name="no_show_refund_choice" value="refund_all" class="mr-2">
                            Devolver R$ <span id="refund-all-amount-display"></span>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium mb-2">Motivo/Observação:</label>
                    <textarea id="no-show-reason-input" name="no_show_reason" rows="3" class="w-full p-2 border border-gray-300 rounded-lg"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
