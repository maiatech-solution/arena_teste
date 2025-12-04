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
        .fc {
            font-family: 'Inter', sans-serif;
            color: #333;
        }
        .fc-toolbar-title {
            font-size: 1.5rem !important;
        }
        /* Define as propriedades de posicionamento para o modal */
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

        /* Estilo para Eventos RECORRENTES (Fúcsia/Roxo) */
        .fc-event-recurrent {
            background-color: #C026D3 !important; /* Fuchsia 700 */
            border-color: #A21CAF !important;
            color: white !important;
            padding: 2px 5px;
            border-radius: 4px;
            /* Garante que o texto dentro do evento seja branco e negrito */
            font-weight: 700 !important;
            color: #ffffff !important;
        }

        /* Estilo para Eventos AVULSOS/RÁPIDOS (Indigo/Azul) */
        .fc-event-quick {
            background-color: #4f46e5 !important; /* Indigo 600 */
            border-color: #4338ca !important;
            color: white !important;
            padding: 2px 5px;
            border-radius: 4px;
        }

        /* Estilo para Eventos PENDENTES (Laranja) */
        .fc-event-pending {
            background-color: #ff9800 !important; /* Orange 500 */
            border-color: #f97316 !important;
            color: white !important;
            padding: 2px 5px;
            border-radius: 4px;
            font-style: italic;
        }

        /* ✅ NOVO: Estilo para Eventos PAGOS/Baixados (Faded/Apagado) */
        .fc-event-paid {
            opacity: 0.5; /* Fica meio apagado */
            filter: grayscale(80%); /* Fica bem cinza */
            /* Garante que o indicador "PAGO" seja visível, mas o evento fique em segundo plano */
        }


        /* Estilo para Eventos Disponíveis (Verde) */
        .fc-event-available {
            background-color: #10B981 !important; /* Verde 500 */
            border-color: #059669 !important;
            color: white !important;
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 4px;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        /* Estilo para o campo de sinal VIP */
        #signal_value_quick.bg-indigo-50 {
             background-color: #eef2ff !important;
        }
        /* Estilo para campos de moeda no modal rápido */
        .input-money-quick { text-align: right; }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

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

                {{-- ALERTA DE PENDÊNCIA RENDERIZADO PELO SERVIDOR (COM VERIFICAÇÃO DE SEGURANÇA) --}}
                <div id="pending-alert-container">
                    @if ($pendingReservationsCount > 0)
                        <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-6 rounded-lg shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between transition-all duration-300 transform hover:scale-[1.005]" role="alert">
                            <div class="flex items-start">
                                <svg class="h-6 w-6 flex-shrink-0 mt-0.5 sm:mt-0 mr-3 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <p class="font-bold text-lg">Atenção: Pendências!</p>
                                    <p class="mt-1 text-sm">Você tem <span class="font-extrabold text-orange-900">{{ $pendingReservationsCount }}</span> pré-reserva(s) aguardando sua ação.</p>
                                </div>
                            </div>
                            <div class="mt-4 sm:mt-0 sm:ml-6">
                                <a href="{{ route('admin.reservas.pendentes') }}" class="inline-block bg-orange-600 hover:bg-orange-700 active:bg-orange-800 text-white font-bold py-2 px-6 rounded-lg text-sm transition duration-150 ease-in-out shadow-lg">
                                    Revisar Pendências
                                </a>
                            </div>
                        </div>
                    @endif
                </div>


                {{-- ALERTA E BOTÃO PARA RENOVAÇÃO RECORRENTE (COM VERIFICAÇÃO DE SEGURANÇA) --}}
                @if ($expiringSeriesCount > 0)
                    <div id="renewal-alert-container" data-series='@json($expiringSeries)' data-count="{{ $expiringSeriesCount }}"
                        class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md flex flex-col items-start transition-all duration-300 transform hover:scale-[1.005]" role="alert">

                        <div class="flex items-start w-full">
                            <svg class="h-6 w-6 flex-shrink-0 mt-0.5 mr-3 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div class="w-full">
                                <p class="font-bold text-lg">ALERTA DE RENOVAÇÃO ({{ $expiringSeriesCount }} Série{{ $expiringSeriesCount > 1 ? 's' : '' }} Expira{{ $expiringSeriesCount > 1 ? 'm' : '' }} em Breve)</p>
                                <p id="renewal-message" class="mt-1 text-sm mb-3">
                                    <span class="font-extrabold text-yellow-900">{{ $expiringSeriesCount }}</span> série(s) de agendamento recorrente de clientes está(ão) prestes a expirar nos próximos 30 dias.
                                </p>

                                {{-- NOVO: DETALHES DE EXPIRAÇÃO NO ALERTA (6 MESES) --}}
                                <div class="space-y-2 p-3 bg-yellow-50 rounded border border-yellow-200">
                                    <p class="font-semibold text-sm text-yellow-800">Detalhes para Renovação (Sugestão: +6 meses):</p>
                                    @foreach ($expiringSeries as $seriesItem)
                                        @php
                                            $lastDate = \Carbon\Carbon::parse($seriesItem['last_date']);
                                            $suggestedNewDate = $lastDate->copy()->addMonths(6); // ✅ MUDANÇA AQUI: +6 meses
                                        @endphp
                                        <div class="text-xs text-gray-700">
                                            <strong>{{ $seriesItem['client_name'] }}</strong> ({{ $seriesItem['slot_time'] }}) expira em {{ $lastDate->format('d/m/Y') }}.
                                            <span class="font-bold text-green-600">Renovação sugerida até {{ $suggestedNewDate->format('d/m/Y') }}.</span>
                                        </div>
                                    @endforeach
                                </div>
                                {{-- FIM NOVO DETALHE --}}
                            </div>
                        </div>

                        <button onclick="openRenewalModal()" class="mt-4 bg-yellow-600 hover:bg-yellow-700 active:bg-yellow-800 text-white font-bold py-2 px-6 rounded-lg text-sm transition duration-150 ease-in-out shadow-lg">
                            Revisar Renovações
                        </button>
                    </div>
                @endif

                {{-- Legenda ATUALIZADA para incluir status Pago --}}
                <div class="flex flex-wrap gap-4 mb-4 text-sm font-medium">
                    <div class="flex items-center p-2 bg-fuchsia-50 rounded-lg shadow-sm">
                        <span class="inline-block w-4 h-4 rounded-full bg-fuchsia-700 mr-2"></span>
                        <span>Reservado Recorrente (Fixo)</span>
                    </div>
                    <div class      ="flex items-center p-2 bg-indigo-50 rounded-lg shadow-sm">
                        <span class="inline-block w-4 h-4 rounded-full bg-indigo-600 mr-2"></span>
                        <span>Reservado Avulso (Rápido)</span>
                    </div>
                    <div class="flex items-center p-2 bg-gray-100 rounded-lg shadow-sm">
                        <span class="inline-block w-4 h-4 rounded-full bg-gray-400 mr-2 opacity-50"></span>
                        <span class="italic text-gray-600">Reservado PAGO (Faded)</span>
                    </div>
                    <!--<div class="flex items-center p-2 bg-orange-50 rounded-lg shadow-sm">
                        <span class="inline-block w-4 h-4 rounded-full bg-orange-500 mr-2"></span>
                        <span>Pré-Reserva Pendente</span>
                    </div> -->
                    <div class="flex items-center p-2 bg-green-50 rounded-lg shadow-sm">
                        <span class="inline-block w-4 h-4 rounded-full bg-green-500 mr-2"></span>
                        <span>Disponível (Horários Abiertos)</span>
                    </div>
                </div>

                <div class="calendar-container">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Detalhes de Reserva (RESERVAS EXISTENTES CONFIRMADAS/RECORRENTES) --}}
    <div id="event-modal" class="modal-overlay hidden" onclick="closeEventModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">Detalhes da Reserva Confirmada</h3>
            <div class="space-y-3 text-gray-700" id="modal-content">
            </div>
            <div class="mt-6 w-full space-y-2" id="modal-actions">
                {{-- Botões injetados pelo JS --}}
                <button onclick="closeEventModal()" class="w-full px-4 py-2 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 transition duration-150">
                    Fechar
                </button>
            </div>
        </div>
     </div>

    {{-- NOVO: Modal de Ação Pendente (Abre ao clicar no slot Laranja) --}}
    <div id="pending-action-modal" class="modal-overlay hidden" onclick="closePendingActionModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-orange-600 mb-4 border-b pb-2 flex items-center">
                <svg class="h-6 w-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Ação Requerida: Pré-Reserva Pendente
            </h3>

            <div class="mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                <div class="space-y-2 text-gray-700" id="pending-modal-content">
                    {{-- Conteúdo Injetado via JS --}}
                </div>
            </div>

            <form id="pending-action-form" onsubmit="return false;">
                @csrf
                @method('PATCH')
                <input type="hidden" name="reserva_id" id="pending-reserva-id">

                <div id="rejection-reason-area" class="mb-4 hidden">
                    <label for="rejection-reason" class="block text-sm font-medium text-gray-700 mb-1">Motivo da Rejeição (Opcional):</label>
                    <textarea name="rejection_reason" id="rejection-reason" rows="2" placeholder="Descreva o motivo para liberar o horário." class="w-full p-2 border border-gray-300 rounded-lg"></textarea>
                </div>

                <div id="confirmation-value-area" class="mb-4">
                    <label for="confirmation-value" class="block text-sm font-medium text-gray-700 mb-1">Valor do Sinal/Confirmação (R$):</label>
                    <input type="number" step="0.01" name="confirmation_value" id="confirmation-value" required class="w-full p-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <p class="text-xs text-gray-500 mt-1">Este valor é opcional, mas define a confirmação da reserva.</p>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closePendingActionModal()" class="px-4 py-2 bg-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-400 transition duration-150">
                        Voltar
                    </button>
                    <button type="button" id="reject-pending-btn" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">
                        Rejeitar
                    </button>
                    <button type="submit" id="confirm-pending-btn" class="px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition duration-150">
                        Confirmar Reserva
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- MODAL DE CANCELAMENTO (para o Motivo do Cancelamento) --}}
    <div id="cancellation-modal" class="modal-overlay hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-transform duration-300 scale-95 opacity-0" id="cancellation-modal-content" onclick="event.stopPropagation()">
            <h3 id="modal-title-cancel" class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação de Cancelamento</h3>

            <p id="modal-message-cancel" class="text-gray-700 mb-4 font-medium"></p>

            <div class="mb-6">
                <label for="cancellation-reason-input" class="block text-sm font-medium text-gray-700 mb-2">
                    Motivo do Cancelamento:
                </label>
                <textarea id="cancellation-reason-input" rows="3" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Obrigatório, descreva o motivo do cancelamento (mínimo 5 caracteres)..."></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <button onclick="closeCancellationModal()" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                    Fechar
                </button>
                <button id="confirm-cancellation-btn" type="button" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">
                    Confirmar Ação
                </button>
            </div>
        </div>
    </div>


    {{-- MODAL DE RENOVAÇÃO DE SÉRIE --}}
    <div id="renewal-modal" class="modal-overlay hidden" onclick="closeRenewalModal()">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-yellow-700 mb-4 border-b pb-2">Gerenciar Renovações Recorrentes</h3>

            <p class="text-sm text-gray-700 mb-4">
                Abaixo estão as séries de reservas que atingirão o limite (expirarão) nas próximas semanas.
                **Ao clicar em Renovar, o sistema tentará estender a série por mais seis meses.**
            </p>

            <div id="renewal-message-box" class="hidden p-3 mb-4 rounded-lg text-sm font-medium"></div>

            <div id="renewal-list" class="space-y-4">
                {{-- Lista injetada pelo JS --}}
                <p class="text-gray-500 italic">Nenhuma série a ser renovada no momento.</p>
            </div>

            <div class="mt-6 flex justify-end">
                <button onclick="closeRenewalModal()" class="px-4 py-2 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 transition duration-150">
                    Fechar
                </button>
            </div>
        </div>
    </div>


    {{-- Modal de Agendamento Rápido (SLOTS DISPONÍVEIS) - SIMPLIFICADO --}}
    <div id="quick-booking-modal" class="modal-overlay hidden" onclick="document.getElementById('quick-booking-modal').classList.add('hidden')">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-green-700 mb-4 border-b pb-2">Agendamento Rápido de Horários</h3>

            <form id="quick-booking-form">
                @csrf

                <div id="slot-info-display" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                    {{-- Informações do slot (Data/Hora/Preço) injetadas pelo JS --}}
                </div>

                <input type="hidden" name="schedule_id" id="quick-schedule-id">
                <input type="hidden" name="date" id="quick-date">
                <input type="hidden" name="start_time" id="quick-start-time">
                <input type="hidden" name="end_time" id="quick-end-time">
                <input type="hidden" name="price" id="quick-price">
                <input type="hidden" name="reserva_id_to_update" id="reserva-id-to-update">


                <div id="client_fields">
                    <div class="mb-4">
                        <label for="client_name" class="block text-sm font-medium text-gray-700">Nome Completo do Cliente *</label>
                        <input type="text" name="client_name" id="client_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <div class="mb-4">
                        <label for="client_contact" class="block text-sm font-medium text-gray-700">WhatsApp para Contato (Apenas 11 dígitos)*</label>
                        <input type="tel" name="client_contact" id="client_contact" required
                                maxlength="11" pattern="\d{11}"
                                title="O WhatsApp deve conter apenas 11 dígitos (DDD + 9º Dígito + Número)."
                                placeholder="Ex: 91985320997"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <p id="whatsapp-error-message" class="text-xs text-red-600 mt-1 hidden font-semibold">
                            ⚠️ Por favor, insira exatamente 11 dígitos para o WhatsApp (Ex: 91985320997).
                        </p>

                        {{-- ✅ NOVO: Onde a reputação será exibida --}}
                        <div id="client-reputation-display" class="mt-2 text-sm">
                            </div>
                    </div>
                </div>

                {{-- ✅ CORREÇÃO CRÍTICA NO FRONTEND: MUDANDO DE TYPE="NUMBER" PARA TYPE="TEXT" --}}
                <div class="mb-4">
                    <label for="signal_value_quick" class="block text-sm font-medium text-gray-700">Valor do Sinal/Entrada (R$)</label>
                    <input type="text" name="signal_value" id="signal_value_quick" value="0,00"
                            placeholder="Ex: 40,00"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 transition duration-150 input-money-quick">
                    <p class="text-xs text-gray-500 mt-1">Opcional. Valor pago antecipadamente para confirmar a reserva.</p>
                </div>
                {{-- FIM DO CAMPO CORRIGIDO --}}

                {{-- CHECKBOX PARA RECORRÊNCIA --}}
                <div class="mb-4 p-3 border border-indigo-200 rounded-lg bg-indigo-50">
                    <div class="flex items-center">
                        <input type="checkbox" name="is_recurrent" id="is-recurrent" value="1"
                                class="h-5 w-5 text-indigo-600 border-indigo-300 rounded focus:ring-indigo-500">
                        <label for="is-recurrent" class="ml-3 text-base font-semibold text-indigo-700">
                            Tornar esta reserva Recorrente (6 Meses)
                        </label>
                    </div>
                    <p class="text-xs text-indigo-600 mt-1 pl-8">
                        Se marcado, o sistema criará reservas para esta faixa de horário em todas as semanas por **seis meses**.
                    </p>
                </div>
                {{-- FIM DO NOVO CHECKBOX --}}

                <div class="mb-4">
                    <label for="notes" class="block text-sm font-medium text-gray-700">Observações (Opcional)</label>
                    <textarea name="notes" id="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <button type="submit" id="submit-quick-booking" class="mt-4 w-full px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition duration-150">
                    Confirmar Agendamento
                </button>
                <button type="button" onclick="document.getElementById('quick-booking-modal').classList.add('hidden')" class="mt-2 w-full px-4 py-2 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 transition duration-150">
                    Cancelar
                </button>
            </form>
        </div>
    </div>


    <script>
        // === CONFIGURAÇÕES E ROTAS ===
        const PENDING_API_URL = '{{ route("api.reservas.pendentes.count") }}';
        const RESERVED_API_URL = '{{ route("api.reservas.confirmadas") }}';
        const AVAILABLE_API_URL = '{{ route("api.horarios.disponiveis") }}';
        const SHOW_RESERVA_URL = '{{ route("admin.reservas.show", ":id") }}'';

        // 🎯 NOVA ROTA para buscar a reputação do cliente (o :contact será substituído pelo JS)
        const USER_REPUTATION_URL = '{{ route("api.users.reputation", ":contact") }}';

        // 🎯 ROTA PARA O CAIXA/PAGAMENTO
        const PAYMENT_INDEX_URL = '{{ route("admin.payment.index") }}';

        // ROTAS DE SUBMISSÃO
        const RECURRENT_STORE_URL = '{{ route("api.reservas.store_recurrent") }}';
        const QUICK_STORE_URL = '{{ route("api.reservas.store_quick") }}';
        const RENEW_SERIE_URL = '{{ route("admin.reservas.renew_serie", ":masterReserva") }}';

        // ROTAS DE AÇÕES PENDENTES
        const CONFIRM_PENDING_URL = '{{ route("admin.reservas.confirmar", ":id") }}';
        const REJECT_PENDING_URL = '{{ route("admin.reservas.rejeitar", ":id") }}';

        // ROTAS DE CANCELAMENTO
        const CANCEL_PONTUAL_URL = '{{ route("admin.reservas.cancelar_pontual", ":id") }}';
        const CANCEL_SERIE_URL = '{{ route("admin.reservas.cancelar_serie", ":id") }}';
        const CANCEL_PADRAO_URL = '{{ route("admin.reservas.cancelar", ":id") }}';
        // ======================================

        // TOKEN CSRF
        const csrfToken = document.querySelector('input[name="_token"]').value;

        // VARIÁVEIS GLOBAIS DE ESTADO
        let calendar;
        let currentReservaId = null;
        let currentMethod = null;
        let currentUrlBase = null;
        let globalExpiringSeries = [];
        // Variável global para armazenar temporariamente o status VIP/Reputação
        let currentClientStatus = { is_vip: false, reputation_tag: '' };

        // Elementos do Formulário
        const clientNameInput = () => document.getElementById('client_name');
        const clientContactInput = () => document.getElementById('client_contact');
        const whatsappError = () => document.getElementById('whatsapp-error-message');
        const reputationDisplay = () => document.getElementById('client-reputation-display');
        const signalValueInputQuick = () => document.getElementById('signal_value_quick'); // ✅ NOVO NOME


        // === FUNÇÃO PARA FORMATAR MOEDA NO QUICK MODAL ===
        const formatMoneyQuick = (input) => {
            let value = input.value.replace(/\D/g, ''); // Remove tudo que não for dígito
            if (value.length === 0) return '0,00';

            while (value.length < 3) {
                value = '0' + value;
            }

            let integerPart = value.substring(0, value.length - 2);
            let decimalPart = value.substring(value.length - 2);

            integerPart = integerPart.replace(/^0+/, '');
            if (integerPart.length === 0) integerPart = '0';

            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");

            return `${integerPart},${decimalPart}`;
        };
        // ========================================================


        document.addEventListener('DOMContentLoaded', () => {
            const renewalAlertContainer = document.getElementById('renewal-alert-container');
            if (renewalAlertContainer) {
                try {
                    const dataSeriesAttr = renewalAlertContainer.getAttribute('data-series');
                    globalExpiringSeries = dataSeriesAttr ? JSON.parse(dataSeriesAttr) : [];
                } catch (e) {
                    console.error("Erro ao carregar dados de séries expirando:", e);
                    globalExpiringSeries = [];
                }
            }

            // Aplicar formatação nos inputs de moeda do modal rápido
            document.querySelectorAll('.input-money-quick').forEach(input => {
                input.value = formatMoneyQuick(input);

                input.addEventListener('input', (e) => {
                    e.target.value = formatMoneyQuick(e.target);
                });

                input.addEventListener('blur', (e) => {
                    e.target.value = formatMoneyQuick(e.target);
                });
            });
        });


        /**
         * FUNÇÃO PARA CHECAR AS RESERVAS PENDENTES EM TEMPO REAL (PERIÓDICO)
         */
        const checkPendingReservations = async () => {
            const notificationContainer = document.getElementById('pending-alert-container');
            const apiUrl = PENDING_API_URL;

            try {
                const response = await fetch(apiUrl);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                const count = data.count || 0;

                let htmlContent = '';

                if (count > 0) {
                    htmlContent = `
                        <div class="bg-orange-100 border-l-4 border-orange-500 text-orange-700 p-4 mb-6 rounded-lg shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between transition-all duration-300 transform hover:scale-[1.005]" role="alert">
                            <div class="flex items-start">
                                <svg class="h-6 w-6 flex-shrink-0 mt-0.5 sm:mt-0 mr-3 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <div>
                                    <p class="font-bold text-lg">Atenção: Pendências!</p>
                                    <p class="mt-1 text-sm">Você tem <span class="font-extrabold text-orange-900">${count}</span> pré-reserva(s) aguardando sua ação.</p>
                                </div>
                            </div>
                            <div class="mt-4 sm:mt-0 sm:ml-6">
                                <a href="{{ route('admin.reservas.pendentes') }}" class="inline-block bg-orange-600 hover:bg-orange-700 active:bg-orange-800 text-white font-bold py-2 px-6 rounded-lg text-sm transition duration-150 ease-in-out shadow-lg">
                                    Revisar Pendências
                                </a>
                            </div>
                        </div>
                    `;
                } else {
                    htmlContent = '';
                }

                if (notificationContainer.innerHTML.trim() !== htmlContent.trim()) {
                    notificationContainer.innerHTML = htmlContent;
                }

            } catch (error) {
                console.error('[PENDÊNCIA DEBUG] Erro ao buscar o status de pendências:', error);
                notificationContainer.innerHTML = '';
            }
        };

        // =========================================================
        // ✅ FUNÇÃO NOVA: BUSCAR REPUTAÇÃO DO CLIENTE
        // =========================================================

        /**
         * Busca a reputação do cliente via API e atualiza o modal.
         */
        async function fetchClientReputation(contact) {
            const displayEl = reputationDisplay();
            const signalInput = signalValueInputQuick();

            // Limpa estados anteriores
            displayEl.innerHTML = '<span class="text-xs text-gray-500">Buscando reputação...</span>';
            // Deixamos o valor do sinal no formulário, mas limpamos o estilo
            signalInput.removeAttribute('title');
            signalInput.classList.remove('bg-indigo-50', 'border-indigo-400', 'text-indigo-800');

            if (contact.length !== 11) {
                displayEl.innerHTML = '';
                currentClientStatus = { is_vip: false, reputation_tag: '' };
                return;
            }

            const url = USER_REPUTATION_URL.replace(':contact', contact);

            try {
                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error(`Erro HTTP! status: ${response.status}`);
                }

                // A API deve retornar um objeto como: { status_tag: '<span...>', is_vip: true/false }
                const data = await response.json();

                currentClientStatus.is_vip = data.is_vip || false;
                currentClientStatus.reputation_tag = data.status_tag || '';

                // 1. Exibe a tag de reputação
                if (currentClientStatus.reputation_tag) {
                    displayEl.innerHTML = `<p class="font-semibold text-gray-700 mb-1">Reputação:</p>${currentClientStatus.reputation_tag}`;
                } else {
                    displayEl.innerHTML = '<span class="text-sm text-gray-500 font-medium p-1 bg-green-50 rounded-lg">👍 Novo Cliente ou Reputação OK.</span>';
                }

                // 2. Atualiza o valor do sinal se for VIP (seta para 0,00)
                if (currentClientStatus.is_vip) {
                    signalInput.value = '0,00';
                    signalInput.setAttribute('title', 'Sinal zerado automaticamente para cliente VIP.');
                    signalInput.classList.add('bg-indigo-50', 'border-indigo-400', 'text-indigo-800');
                    displayEl.insertAdjacentHTML('beforeend', '<span class="text-xs ml-2 text-indigo-600 font-bold p-1 bg-indigo-100 rounded">✅ VIP DETECTADO</span>');
                } else {
                    // Se não for VIP, restaura para 0,00 ou o valor inicial
                    signalInput.value = '0,00';
                    signalInput.classList.remove('bg-indigo-50', 'border-indigo-400', 'text-indigo-800');
                }

            } catch (error) {
                console.error('[Reputation Debug] Erro ao buscar reputação:', error);
                displayEl.innerHTML = '<span class="text-xs text-red-500">Falha ao buscar reputação.</span>';
                currentClientStatus = { is_vip: false, reputation_tag: '' };
            }
        }


        // =========================================================
        // 🚨 FUNÇÃO DE VALIDAÇÃO WHATSAPP (11 DÍGITOS)
        // =========================================================

        /**
         * Valida se o contato do cliente é um número de WhatsApp com 11 dígitos
         * e dispara a busca de reputação se for válido.
         */
        function validateClientContact(contact) {
            const numbersOnly = contact.replace(/\D/g, '');
            const isValid = numbersOnly.length === 11;

            const errorElement = whatsappError();
            const contactInputEl = clientContactInput();
            const displayEl = reputationDisplay();

            contactInputEl.classList.remove('border-red-500', 'border-green-500');

            if (isValid) {
                errorElement.classList.add('hidden');
                contactInputEl.classList.add('border-green-500');
                // ✅ NOVO: Dispara a busca da reputação apenas com 11 dígitos
                fetchClientReputation(numbersOnly);
            } else {
                errorElement.classList.remove('hidden');
                contactInputEl.classList.add('border-red-500');
                // Limpa o display se não for válido
                displayEl.innerHTML = '';
                signalValueInputQuick().value = '0,00';
                currentClientStatus = { is_vip: false, reputation_tag: '' };
            }

            return isValid;
        }


        // =========================================================
        // FUNÇÃO CRÍTICA: Lidar com a submissão do Agendamento Rápido via AJAX
        // =========================================================
        async function handleQuickBookingSubmit(event) {
            event.preventDefault();

            const clientName = clientNameInput().value.trim();
            const clientContact = clientContactInput().value.trim();

            if (!clientName) {
                // Substituindo alert por mensagem no console ou outro modal, mas mantendo a lógica simples aqui
                console.error("Por favor, preencha o Nome Completo do Cliente.");
                return;
            }

            // Validação de 11 dígitos no WhatsApp
            if (!validateClientContact(clientContact)) {
                return;
            }

            const form = document.getElementById('quick-booking-form');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Função para limpar e converter string monetária (ex: "1.000,50" -> 1000.50)
            const cleanAndConvertForApi = (value) => {
                if (!value) return 0.00;
                value = value.replace('.', ''); // Remove separadores de milhar
                value = value.replace(',', '.'); // Troca vírgula por ponto decimal
                return parseFloat(value) || 0.00;
            };

            // ✅ CRÍTICO: Limpa e converte o valor do sinal antes de enviar
            const signalValueRaw = data.signal_value;
            data.signal_value = cleanAndConvertForApi(signalValueRaw);

            // ⚠️ DEBUG CRÍTICO: Mostra os dados enviados.
            console.log("Dados enviados (signal_value limpo para API):", data.signal_value);

            const isRecurrent = document.getElementById('is-recurrent').checked;
            const targetUrl = isRecurrent ? RECURRENT_STORE_URL : QUICK_STORE_URL;

            const submitBtn = document.getElementById('submit-quick-booking');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Agendando...';

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    const errorText = await response.text();
                    console.error("Falha ao ler JSON de resposta (Pode ser 500).", errorText);
                    // alert(`Erro do Servidor (${response.status}). Verifique o console.`); // Substituído por log
                    return;
                }

                if (response.ok && result.success) {
                    // alert(result.message); // Substituído por log/sucesso
                    document.getElementById('quick-booking-modal').classList.add('hidden');
                    // Recarrega o calendário para mostrar o novo evento (com status parcial e signal_value)
                    setTimeout(() => {
                        window.location.reload();
                    }, 50);

                } else if (response.status === 422 && result.errors) {
                    const errors = Object.values(result.errors).flat().join('\n');
                    // alert(`ERRO DE VALIDAÇÃO:\n${errors}`); // Substituído por log
                    console.error(`ERRO DE VALIDAÇÃO:\n${errors}`);
                } else {
                    // alert(result.message || `Erro desconhecido. Status: ${response.status}.`); // Substituído por log
                    console.error(result.message || `Erro desconhecido. Status: ${response.status}.`);
                }

            } catch (error) {
                console.error('Erro de Rede:', error);
                // alert("Erro de Rede. Tente novamente."); // Substituído por log
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Agendamento';
            }
        }

        // =========================================================
        // FLUXO DE AÇÕES PENDENTES, CANCELAMENTO E RENOVAÇÃO (MANTIDOS)
        // =========================================================

        function closeEventModal() {
            document.getElementById('event-modal').classList.add('hidden');
        }

        function openPendingActionModal(event) {
            const extendedProps = event.extendedProps || {};
            const reservaId = event.id;
            const dateDisplay = moment(event.start).format('DD/MM/YYYY');
            const timeDisplay = moment(event.start).format('HH:mm') + ' - ' + moment(event.end).format('HH:mm');
            const priceDisplay = parseFloat(extendedProps.price || 0).toFixed(2).replace('.', ',');
            const clientName = event.title.split(' - R$ ')[0];

            document.getElementById('pending-reserva-id').value = reservaId;
            document.getElementById('confirmation-value').value = extendedProps.price || '';

            document.getElementById('pending-modal-content').innerHTML = `
                <p>O cliente **${clientName}** realizou uma pré-reserva.</p>
                <p><strong>Data:</strong> ${dateDisplay}</p>
                <p><strong>Horário:</strong> ${timeDisplay}</p>
                <p><strong>Valor Proposto:</strong> R$ ${priceDisplay}</p>
                <p class="text-xs italic mt-2 text-orange-700">A confirmação remove o slot fixo e a rejeição recria o slot fixo.</p>
            `;

            document.getElementById('rejection-reason-area').classList.add('hidden');
            document.getElementById('rejection-reason').value = '';
            document.getElementById('reject-pending-btn').textContent = 'Rejeitar';
            document.getElementById('reject-pending-btn').classList.replace('bg-red-800', 'bg-red-600');

            document.getElementById('pending-action-modal').classList.remove('hidden');
        }

        function closePendingActionModal() {
            document.getElementById('pending-action-modal').classList.add('hidden');
        }

        document.getElementById('confirm-pending-btn').addEventListener('click', function() {
            const form = document.getElementById('pending-action-form');
            const reservaId = document.getElementById('pending-reserva-id').value;
            let confirmationValue = document.getElementById('confirmation-value').value;

            // ✅ CORREÇÃO CRÍTICA 2: Garante que o valor do sinal para confirmação é um float ou 0
            const signalValueFinal = parseFloat(confirmationValue) || 0;

            if (form.reportValidity()) {
                const url = CONFIRM_PENDING_URL.replace(':id', reservaId);
                const data = {
                    signal_value: signalValueFinal, // Usa o valor corrigido
                    is_recurrent: false, // O modal de pendência não tem recorrência. Isso é feito via view de pendentes.
                    _token: csrfToken,
                    _method: 'PATCH',
                };
                sendPendingAction(url, data, 'Confirmando...');
            }
        });

        document.getElementById('reject-pending-btn').addEventListener('click', function() {
            const reasonArea = document.getElementById('rejection-reason-area');
            const reasonInput = document.getElementById('rejection-reason');

            if (reasonArea.classList.contains('hidden')) {
                reasonArea.classList.remove('hidden');
                this.textContent = 'Confirmar Rejeição';
                this.classList.replace('bg-red-600', 'bg-red-800');
            } else {
                const reservaId = document.getElementById('pending-reserva-id').value;
                const reason = reasonInput.value.trim();

                if (reason.length < 5) {
                    console.error("Por favor, forneça um motivo de rejeição com pelo menos 5 caracteres.");
                    return;
                }

                const url = REJECT_PENDING_URL.replace(':id', reservaId);
                const data = {
                    rejection_reason: reason,
                    _token: csrfToken,
                    _method: 'PATCH',
                };
                sendPendingAction(url, data, 'Rejeitando...');
            }
        });

        async function sendPendingAction(url, data, buttonText) {
            const submitBtn = document.getElementById('confirm-pending-btn');
            const rejectBtn = document.getElementById('reject-pending-btn');

            submitBtn.disabled = true;
            rejectBtn.disabled = true;
            submitBtn.textContent = buttonText;
            rejectBtn.textContent = buttonText;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    const errorText = await response.text();
                    console.error("Falha ao ler JSON de resposta.", errorText);
                    // alert(`Erro do Servidor (${response.status}). Verifique o console.`); // Substituído por log
                    return;
                }

                if (response.ok && result.success) {
                    // alert(result.message); // Substituído por log/sucesso
                    closePendingActionModal();
                    setTimeout(() => window.location.reload(), 50);

                } else if (response.status === 422 && result.errors) {
                    const errors = Object.values(result.errors).flat().join('\n');
                    // alert(`ERRO DE VALIDAÇÃO:\n${errors}`); // Substituído por log
                    console.error(`ERRO DE VALIDAÇÃO:\n${errors}`);
                } else {
                    // alert(result.message || `Erro desconhecido. Status: ${response.status}.`); // Substituído por log
                    console.error(result.message || `Erro desconhecido. Status: ${response.status}.`);
                }

            } catch (error) {
                console.error('Erro de Rede:', error);
                // alert("Erro de Rede. Tente novamente."); // Substituído por log
            } finally {
                submitBtn.disabled = false;
                rejectBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Reserva';
                rejectBtn.textContent = 'Rejeitar';
                document.getElementById('rejection-reason-area').classList.add('hidden');
                rejectBtn.classList.replace('bg-red-800', 'bg-red-600');
            }
        }


        function closeEventModal() {
            document.getElementById('event-modal').classList.add('hidden');
        }

        function openCancellationModal(reservaId, method, urlBase, message, buttonText) {
            closeEventModal();
            currentReservaId = reservaId;
            currentMethod = method;
            currentUrlBase = urlBase;
            document.getElementById('cancellation-reason-input').value = '';

            document.getElementById('modal-message-cancel').textContent = message;
            document.getElementById('cancellation-modal').classList.remove('hidden');

            setTimeout(() => {
                document.getElementById('cancellation-modal-content').classList.remove('opacity-0', 'scale-95');
            }, 10);

            document.getElementById('confirm-cancellation-btn').textContent = buttonText;
        }

        function closeCancellationModal() {
            document.getElementById('cancellation-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                document.getElementById('cancellation-modal').classList.add('hidden');
            }, 300);
        }

        async function sendCancellationRequest(reservaId, method, urlBase, reason) {
            const url = urlBase.replace(':id', reservaId);
            const bodyData = {
                cancellation_reason: reason,
                _token: csrfToken,
                _method: method,
            };

            const fetchConfig = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(bodyData)
            };

            const submitBtn = document.getElementById('confirm-cancellation-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processando...';

            try {
                const response = await fetch(url, fetchConfig);

                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    const errorText = await response.text();
                    console.error("Falha ao ler JSON de resposta (Pode ser 500 ou HTML).", errorText);
                    result = { error: `Erro do Servidor (${response.status}). Verifique o console.` };
                }

                if (response.ok && result.success) {
                    // alert(result.message || "Ação realizada com sucesso. O calendário será atualizado."); // Substituído por log/sucesso
                    closeCancellationModal();
                    setTimeout(() => {
                        window.location.reload();
                    }, 50);

                } else if (response.status === 422 && result.errors) {
                    const reasonError = result.errors.cancellation_reason ? result.errors.cancellation_reason.join(', ') : 'Erro de validação desconhecido.';
                    // alert(`ERRO DE VALIDAÇÃO: ${reasonError}`); // Substituído por log
                    console.error(`ERRO DE VALIDAÇÃO: ${reasonError}`);
                } else {
                    // alert(result.error || result.message || `Erro desconhecido ao processar a ação. Status: ${response.status}.`); // Substituído por log
                    console.error(result.error || result.message || `Erro desconhecido ao processar a ação. Status: ${response.status}.`);
                }

            } catch (error) {
                console.error('Erro de Rede/Comunicação:', error);
                // alert("Erro de conexão. Tente novamente."); // Substituído por log
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Ação';
            }
        }

        document.getElementById('confirm-cancellation-btn').addEventListener('click', function() {
            const reason = document.getElementById('cancellation-reason-input').value.trim();

            if (reason.length < 5) {
                // alert("Por favor, forneça um motivo de cancelamento com pelo menos 5 caracteres."); // Substituído por log
                console.error("Por favor, forneça um motivo de cancelamento com pelo menos 5 caracteres.");
                return;
            }

            if (currentReservaId && currentMethod && currentUrlBase) {
                sendCancellationRequest(currentReservaId, currentMethod, currentUrlBase, reason);
            } else {
                // alert("Erro: Dados da reserva não configurados corretamente."); // Substituído por log
                console.error("Erro: Dados da reserva não configurados corretamente.");
            }
        });

        const cancelarPontual = (id, isRecurrent) => {
            const urlBase = isRecurrent ? CANCEL_PONTUAL_URL : CANCEL_PADRAO_URL;
            const method = 'PATCH';
            const confirmation = isRecurrent
                ? "Cancelar SOMENTE ESTA reserva (exceção)? O horário será liberado pontualmente."
                : "Cancelar esta reserva pontual (O horário será liberado e a reserva deletada).";
            const buttonText = isRecurrent ? 'Cancelar ESTE DIA' : 'Confirmar Cancelamento';

            openCancellationModal(id, method, urlBase, confirmation, buttonText);
        };

        const cancelarSerie = (id) => {
            const urlBase = CANCEL_SERIE_URL;
            const method = 'DELETE';
            const confirmation = "⚠️ ATENÇÃO: Cancelar TODA A SÉRIE desta reserva? Todos os horários futuros serão liberados.";
            const buttonText = 'Confirmar Cancelamento de SÉRIE';

            openCancellationModal(id, method, urlBase, confirmation, buttonText);
        };

        function closeRenewalModal() {
            document.getElementById('renewal-modal').classList.add('hidden');
            document.getElementById('renewal-message-box').classList.add('hidden');
        }

        function updateMainAlert() {
            const alertContainer = document.getElementById('renewal-alert-container');
            const count = globalExpiringSeries.length;

            if (count > 0) {
                document.getElementById('renewal-message').innerHTML = `<span class="font-extrabold text-yellow-900">${count}</span> série(s) de agendamento recorrente de clientes está(ão) prestes a expirar nos próximos 30 dias.`;
                alertContainer.classList.remove('hidden');
            } else {
                alertContainer.classList.add('hidden');
            }
        }

        function openRenewalModal() {
            const series = globalExpiringSeries;
            const listContainer = document.getElementById('renewal-list');
            listContainer.innerHTML = '';
            document.getElementById('renewal-message-box').classList.add('hidden');

            if (series.length === 0) {
                listContainer.innerHTML = '<p class="text-gray-500 italic text-center p-4">Nenhuma série a ser renovada no momento. Bom trabalho!</p>';
            } else {
                series.forEach(item => {
                    const dayNames = {0: 'Domingo', 1: 'Segunda', 2: 'Terça', 3: 'Quarta', 4: 'Quinta', 5: 'Sexta', 6: 'Sábado'};
                    const dayName = dayNames[item.day_of_week] || 'Dia Desconhecido';

                    const lastDateDisplay = moment(item.last_date, 'YYYY-MM-DD').format('DD/MM/YYYY');
                    const priceDisplay = parseFloat(item.slot_price).toFixed(2).replace('.', ',');


                    const itemHtml = `
                        <div id="renewal-item-${item.master_id}" class="p-4 border border-yellow-300 rounded-lg bg-yellow-50 flex flex-col md:flex-row justify-between items-start md:items-center shadow-md transition duration-300 hover:bg-yellow-100">
                            <div>
                                <p class="font-bold text-indigo-700">${item.client_name}</p>
                                <p class="text-sm text-gray-600">
                                    Slot: ${item.slot_time} (${dayName}) - R$ ${priceDisplay}
                                </p>
                                <p class="text-xs text-red-600 font-medium mt-1">
                                    Expira em: ${lastDateDisplay}
                                </p>
                            </div>
                            <div class="mt-3 md:mt-0">
                                <button onclick="handleRenewal(${item.master_id})"
                                            class="renew-btn-${item.master_id} w-full md:w-auto px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition duration-150 shadow-lg text-sm">
                                    Renovar por 6 Meses
                                </button>
                            </div>
                        </div>
                    `;
                    listContainer.insertAdjacentHTML('beforeend', itemHtml);
                });
            }


            document.getElementById('renewal-modal').classList.remove('hidden');
        }

        function displayRenewalMessage(message, isSuccess) {
            const msgBox = document.getElementById('renewal-message-box');
            msgBox.textContent = message;
            if (isSuccess) {
                msgBox.className = 'p-3 mb-4 rounded-lg text-sm font-medium bg-green-100 border border-green-400 text-green-700';
            } else {
                msgBox.className = 'p-3 mb-4 rounded-lg text-sm font-medium bg-red-100 border border-red-400 text-red-700';
            }
            msgBox.classList.remove('hidden');
        }


        async function handleRenewal(masterId) {
            const url = RENEW_SERIE_URL.replace(':masterReserva', masterId);
            const itemContainer = document.getElementById(`renewal-item-${masterId}`);
            const renewBtn = document.querySelector(`.renew-btn-${masterId}`);

            const seriesData = globalExpiringSeries.find(s => s.master_id === masterId);
            const clientName = seriesData ? seriesData.client_name : 'Cliente Desconhecido';

            // ATENÇÃO: Corrigido o texto da confirmação para 6 meses
            if (!confirm(`Confirmar a renovação da série #${masterId} por mais 6 meses para ${clientName}?`)) {
                return;
            }

            renewBtn.disabled = true;
            renewBtn.textContent = 'Processando...';
            renewBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            renewBtn.classList.add('bg-gray-500');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({})
                });

                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    const errorText = await response.text();
                    console.error("Falha ao ler JSON de resposta.", errorText);
                    result = { success: false, message: `Erro do Servidor (${response.status}). Verifique o console.` };
                }

                if (response.ok && result.success) {
                    displayRenewalMessage(result.message, true);
                    itemContainer.remove();

                    globalExpiringSeries = globalExpiringSeries.filter(s => s.master_id !== masterId);
                    updateMainAlert();

                    if (document.getElementById('renewal-list').children.length === 0) {
                        document.getElementById('renewal-list').innerHTML = '<p class="text-gray-500 italic text-center p-4">Nenhuma série a ser renovada no momento. Bom trabalho!</p>';
                        setTimeout(() => closeRenewalModal(), 3000);
                    }

                    setTimeout(() => {
                        window.location.reload();
                    }, 50);

                } else {
                    displayRenewalMessage(`Falha na renovação: ${result.message || 'Erro desconhecido.'}`, false);
                    renewBtn.disabled = false;
                    // Corrigido o texto do botão de erro para 6 Meses
                    renewBtn.textContent = 'Renovar por 6 Meses';
                    renewBtn.classList.remove('bg-gray-500');
                    renewBtn.classList.add('bg-green-600', 'hover:bg-green-700');
                }
            } catch (error) {
                console.error('Erro de Rede:', error);
                displayRenewalMessage("Erro de conexão ao tentar renovar.", false);
                renewBtn.disabled = false;
                // Corrigido o texto do botão de erro para 6 Meses
                renewBtn.textContent = 'Renovar por 6 Meses';
                renewBtn.classList.remove('bg-gray-500');
                renewBtn.classList.add('bg-green-600', 'hover:bg-green-700');
            }
        }


        window.onload = function() {
            var calendarEl = document.getElementById('calendar');
            var eventModal = document.getElementById('event-modal');
            var modalContent = document.getElementById('modal-content');
            var modalActions = document.getElementById('modal-actions');
            const quickBookingModal = document.getElementById('quick-booking-modal');
            const quickBookingForm = document.getElementById('quick-booking-form');
            const clientContactInputEl = clientContactInput();

            checkPendingReservations();
            setInterval(checkPendingReservations, 30000);

            quickBookingForm.addEventListener('submit', handleQuickBookingSubmit);

            clientContactInputEl.addEventListener('input', function() {
                // Remove todos os caracteres não numéricos e limita a 11
                this.value = this.value.replace(/\D/g,'').substring(0, 11);
                const cleanedContact = this.value;

                // A validação agora dispara a busca de reputação se o contato tiver 11 dígitos
                validateClientContact(cleanedContact);
            });


            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'dayGridMonth',
                height: 'auto',
                timeZone: 'local',
                slotMinTime: '06:00:00',
                slotMaxTime: '23:00:00',

                validRange: {
                    start: moment().format('YYYY-MM-DD')
                },

                eventSources: [
                    {
                        url: RESERVED_API_URL,
                        method: 'GET',
                        failure: function() {
                            console.error('Falha ao carregar reservas confirmadas via API.');
                        },
                        textColor: 'white',
                        eventDataTransform: function(eventData) {
                            if (eventData.extendedProps && eventData.extendedProps.status === 'available') {
                                return null;
                            }
                            return eventData;
                        }
                    },
                    {
                        id: 'available-slots-source-id',
                        className: 'fc-event-available',
                        display: 'block',
                        events: function(fetchInfo, successCallback, failureCallback) {
                            const now = moment();
                            const todayDate = now.format('YYYY-MM-DD');

                            const urlWithParams = AVAILABLE_API_URL +
                                '?start=' + encodeURIComponent(fetchInfo.startStr) +
                                '&end=' + encodeURIComponent(fetchInfo.endStr);

                            fetch(urlWithParams)
                                .then(response => {
                                    if (!response.ok) throw new Error('Falha ao buscar slots disponíveis.');
                                    return response.json();
                                })
                                .then(availableEvents => {
                                    const filteredEvents = availableEvents.filter(event => {
                                        const eventDate = moment(event.start).format('YYYY-MM-DD');

                                        if (eventDate < todayDate) {
                                            return false;
                                        }

                                        if (eventDate === todayDate) {
                                            const eventEnd = moment(event.end);
                                            return eventEnd.isSameOrAfter(now);
                                        }
                                        return true;
                                    });
                                    successCallback(filteredEvents);
                                })
                                .catch(error => {
                                    console.error('Falha ao carregar e filtrar horários disponíveis:', error);
                                    failureCallback(error);
                                });
                        }
                    }
                ],

                views: {
                    dayGridMonth: { buttonText: 'Mês' },
                    timeGridWeek: { buttonText: 'Semana' },
                    timeGridDay: { buttonText: 'Dia' }
                },
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                editable: false,
                initialDate: new Date().toISOString().slice(0, 10),

                // ✅ HOOK ATUALIZADO: Lógica unificada para mostrar APENAS O NOME, aplicar PAGO e aplicar classes
                eventDidMount: function(info) {
                    const event = info.event;
                    const titleEl = info.el.querySelector('.fc-event-title');
                    const extendedProps = event.extendedProps || {};

                    // Apenas processa eventos reservados (não os disponíveis)
                    if (!titleEl || event.classNames.includes('fc-event-available')) return;

                    let currentTitle = titleEl.textContent;

                    // 1. Limpeza agressiva do prefixo 'RECORR.:' para o formato exato que você viu.
                    // Captura e remove "RECORR" + ".:" (opcional) + qualquer espaço.
                    currentTitle = currentTitle.replace(/^RECORR(?:E)?[\.:\s]*\s*/i, '').trim();

                    // 2. Remove o sufixo de preço ' - R$ XX.XX' e qualquer texto após ele (aplica a TODOS reservados)
                    currentTitle = currentTitle.split(' - R$ ')[0].trim();

                    // 3. ✅ NOVO: Lógica para eventos PAGOS/BAIXADOS
                    if (extendedProps.is_paid) { // Assumindo que a API envia esta flag
                        info.el.classList.add('fc-event-paid');
                        // Adiciona o indicador de pago após o nome do cliente
                        currentTitle += ' (PAGO)';
                    }

                    // 4. O resultado final é aplicado ao elemento.
                    titleEl.textContent = currentTitle;
                },

                eventClick: function(info) {
                    const event = info.event;
                    const isAvailable = event.classNames.includes('fc-event-available');
                    const extendedProps = event.extendedProps || {};
                    const status = extendedProps.status;

                    // --- START DEBUG LOG ---
                    console.log("--- Detalhes do Evento Clicado ---");
                    console.log("ID da Reserva:", event.id);
                    console.log("Extended Props:", extendedProps); // CRÍTICO: Verifique aqui o valor de signal_value
                    console.log("----------------------------------");
                    // --- END DEBUG LOG ---


                    if (status === 'pending') {
                        openPendingActionModal(event);
                        return;
                    }

                    if (isAvailable) {
                        const startDate = moment(event.start);
                        const endDate = moment(event.end);

                        if (endDate.isBefore(moment())) {
                            console.log("Slot passado, clique ignorado.");
                            return;
                        }

                        const dateString = startDate.format('YYYY-MM-DD');
                        const dateDisplay = startDate.format('DD/MM/YYYY');

                        const startTimeInput = startDate.format('H:mm');
                        const endTimeInput = endDate.format('H:mm');

                        const timeSlotDisplay = startDate.format('HH:mm') + ' - ' + endDate.format('HH:mm');

                        const price = extendedProps.price || 0;

                        const reservaIdToUpdate = event.id;

                        document.getElementById('reserva-id-to-update').value = reservaIdToUpdate;
                        document.getElementById('quick-date').value = dateString;
                        document.getElementById('quick-start-time').value = startTimeInput;
                        document.getElementById('quick-end-time').value = endTimeInput;
                        document.getElementById('quick-price').value = price;

                        // Limpa/Reseta os campos do formulário
                        clientNameInput().value = '';
                        clientContactInput().value = '';
                        whatsappError().classList.add('hidden');
                        clientContactInput().classList.remove('border-red-500', 'border-green-500');
                        reputationDisplay().innerHTML = ''; // Limpa a reputação anterior
                        currentClientStatus = { is_vip: false, reputation_tag: '' }; // Reseta o status

                        // Inicializa o campo de sinal do agendamento rápido
                        signalValueInputQuick().value = '0,00';
                        signalValueInputQuick().removeAttribute('title');
                        signalValueInputQuick().classList.remove('bg-indigo-50', 'border-indigo-400', 'text-indigo-800');


                        document.getElementById('notes').value = '';
                        document.getElementById('is-recurrent').checked = false;

                        document.getElementById('slot-info-display').innerHTML = `
                            <p><strong>Data:</strong> ${dateDisplay}</p>
                            <p><strong>Horário:</strong> ${timeSlotDisplay}</p>
                            <p><strong>Valor:</strong> R$ ${parseFloat(price).toFixed(2).replace('.', ',')}</p>

                        `;

                        quickBookingModal.classList.remove('hidden');

                    }
                    else if (event.id) {
                        const startTime = event.start;
                        const endTime = event.end;
                        const reservaId = event.id;

                        const isRecurrent = extendedProps.is_recurrent;
                        // ✅ Pega o valor do sinal (já pago, se houver) da API do calendário
                        const signalValue = extendedProps.signal_value || 0;
                        const price = extendedProps.price || 0; // Pega o preço total
                        const isPaid = extendedProps.is_paid || false; // ✅ NOVO: Flag de pago


                        const dateReservation = moment(startTime).format('YYYY-MM-DD');
                        const dateDisplay = moment(startTime).format('DD/MM/YYYY');


                        // ✅ CORREÇÃO: Usando HH:mm para formato de 24 horas consistente
                        let timeDisplay = moment(startTime).format('HH:mm');
                        if (endTime) {
                            timeDisplay += ' - ' + moment(endTime).format('HH:mm');
                        }

                        // O título do evento deve estar no formato "Nome do Cliente - R$ 123.45"
                        // Para exibir apenas o nome do cliente no modal:
                        const titleParts = event.title.split(' - R$ ');
                        const clientName = titleParts[0];

                        // ✅ ATUALIZADO: Incluir a data E o valor do sinal na URL do Caixa
                        // Isso permite que o Controller de Pagamentos pré-preencha o campo Pago
                        const paymentUrl = `${PAYMENT_INDEX_URL}?reserva_id=${reservaId}&data_reserva=${dateReservation}&signal_value=${signalValue}`;
                        const showUrl = SHOW_RESERVA_URL.replace(':id', reservaId);

                        let statusText = 'Confirmada';
                        if (isPaid) {
                            statusText = '<span class="text-green-600">Baixada/Paga</span>';
                        }

                        let recurrentStatus = isRecurrent ?
                            '<p class="text-sm font-semibold text-fuchsia-600">Parte de uma Série Recorrente</p>' :
                            '<p class="text-sm font-semibold text-gray-500">Reserva Pontual</p>';

                        // ✅ CORREÇÃO: Formata o valor do sinal para exibição
                        const signalValueDisplay = parseFloat(signalValue).toFixed(2).replace('.', ',');
                        const priceDisplayFormatted = parseFloat(price).toFixed(2).replace('.', ',');


                        modalContent.innerHTML = `
                            <p class="font-semibold text-gray-900">${clientName}</p>
                            <p><strong>Status:</strong> <span class="uppercase font-bold text-sm text-indigo-600">${statusText}</span></p>
                            <p><strong>Data:</strong> ${dateDisplay}</p>
                            <p><strong>Horário:</strong> ${timeDisplay}</p>
                            <p><strong>Valor:</strong> <span class="text-green-600 font-bold">R$ ${priceDisplayFormatted}</span></p>
                            <p><strong>Sinal Pago:</strong> <span class="text-blue-600 font-bold">R$ ${signalValueDisplay}</span></p>
                            ${recurrentStatus}
                        `;

                        // 🔄 BOTÕES DE AÇÃO: Priorizando o link para o Caixa
                        let actionButtons = `
                            <a href="${paymentUrl}" class="w-full inline-block text-center mb-2 px-4 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition duration-150 text-md shadow-xl">
                                Registrar Pagamento / Acessar Caixa
                            </a>
                            <a href="${showUrl}" class="w-full inline-block text-center mb-2 px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition duration-150 text-sm">
                                Ver Detalhes / Gerenciar (Status, Notas)
                            </a>
                        `;

                        if (isRecurrent) {
                            actionButtons += `
                                <button onclick="cancelarPontual(${reservaId}, true)" class="w-full mb-2 px-4 py-2 bg-yellow-500 text-white font-medium rounded-lg hover:bg-yellow-600 transition duration-150 text-sm">
                                    Cancelar APENAS ESTE DIA
                                </button>
                                <button onclick="cancelarSerie(${reservaId})" class="w-full mb-2 px-4 py-2 bg-red-800 text-white font-medium rounded-lg hover:bg-red-900 transition duration-150 text-sm">
                                    Cancelar SÉRIE INTEIRA (Futuros)
                                </button>
                            `;
                        } else {
                            actionButtons += `
                                <button onclick="cancelarPontual(${reservaId}, false)" class="w-full mb-2 px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition duration-150 text-sm">
                                    Cancelar Reserva Pontual
                                </button>
                            `;
                        }


                        actionButtons += `
                            <button onclick="closeEventModal()" class="w-full px-4 py-2 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 transition duration-150 text-sm">
                                Fechar
                            </button>
                        `;

                        modalActions.innerHTML = actionButtons;

                        eventModal.classList.remove('hidden');
                    }
                }
            });

            calendar.render();
            window.calendar = calendar;

            setInterval(() => {
                calendar.getEventSourceById('available-slots-source-id')?.refetch();
            }, 60000);
        };
        // Expondo funções globais
        window.cancelarPontual = cancelarPontual;
        window.cancelarSerie = cancelarSerie;
        window.openRenewalModal = openRenewalModal;
        window.handleRenewal = handleRenewal;
        window.openPendingActionModal = openPendingActionModal;
        window.closePendingActionModal = closePendingActionModal;
    </script>
</x-app-layout>
