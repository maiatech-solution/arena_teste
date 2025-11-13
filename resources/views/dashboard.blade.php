<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard | Calendário de Reservas') }}
        </h2>
    </x-slot>

    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/main.min.css' rel='stylesheet' />

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

        /* Estilo para Eventos Reservados (Azul) */
        .fc-event-booked {
            background-color: #4f46e5 !important; /* Indigo 600 */
            border-color: #4338ca !important;
            color: white !important;
            padding: 2px 5px;
            border-radius: 4px;
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
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                        <p>{{ session('success') }}</p>
                    </div>
                @endif

                {{-- PLACEHOLDER DINÂMICO PARA NOTIFICAÇÕES --}}
                <div id="realtime-notification">
                    </div>
                {{-- FIM DO PLACEHOLDER --}}

                {{-- Legenda para explicar as cores --}}
                <div class="flex flex-wrap gap-4 mb-4 text-sm font-medium">
                    <div class="flex items-center p-2 bg-indigo-50 rounded-lg shadow-sm">
                        <span class="inline-block w-4 h-4 rounded-full bg-indigo-600 mr-2"></span>
                        <span>Reservado (Confirmado)</span>
                    </div>
                    <div class="flex items-center p-2 bg-green-50 rounded-lg shadow-sm">
                        <span class="inline-block w-4 h-4 rounded-full bg-green-500 mr-2"></span>
                        <span>Disponível (Horários Abertos)</span>
                    </div>
                </div>

                <div class="calendar-container">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    <div id="event-modal" class="modal-overlay hidden" onclick="document.getElementById('event-modal').classList.add('hidden')">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">Detalhes da Reserva</h3>
            <div class="space-y-3 text-gray-700" id="modal-content">
                </div>
            <button onclick="document.getElementById('event-modal').classList.add('hidden')" class="mt-6 w-full px-4 py-2 bg-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-400 transition duration-150">
                Fechar
            </button>
        </div>
    </div>

    <div id="quick-booking-modal" class="modal-overlay hidden" onclick="document.getElementById('quick-booking-modal').classList.add('hidden')">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-lg w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-green-700 mb-4 border-b pb-2">Agendamento Rápido de Slot</h3>

            <form id="quick-booking-form" action="{{ route('api.reservas.store_quick') }}" method="POST">
                @csrf

                <div id="slot-info-display" class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                    </div>

                <input type="hidden" name="schedule_id" id="quick-schedule-id">
                <input type="hidden" name="date" id="quick-date">
                <input type="hidden" name="start_time" id="quick-start-time">
                <input type="hidden" name="end_time" id="quick-end-time">
                <input type="hidden" name="price" id="quick-price">

                <div class="mb-4">
                    <label for="client_name" class="block text-sm font-medium text-gray-700">Nome do Cliente *</label>
                    <input type="text" name="client_name" id="client_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div class="mb-4">
                    <label for="client_contact" class="block text-sm font-medium text-gray-700">Contato (Telefone/Email) *</label>
                    <input type="text" name="client_contact" id="client_contact" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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


    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>

    <script>
        // === CONFIGURAÇÕES E ROTAS ===
        const PENDING_API_URL = '{{ route("api.reservas.pendentes") }}';
        const RESERVED_API_URL = '{{ route("api.reservas.confirmadas") }}';
        const AVAILABLE_API_URL = '{{ route("api.horarios.disponiveis") }}';
        const SHOW_RESERVA_URL = '{{ route("admin.reservas.show", ":id") }}'; // Rota para detalhes/gerenciamento
        // ======================================

        /**
         * FUNÇÃO PARA CHECAR AS RESERVAS PENDENTES EM TEMPO REAL (PERIÓDICO)
         */
        const checkPendingReservations = async () => {
            const notificationContainer = document.getElementById('realtime-notification');
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
                    // Alerta Laranja (Pendências)
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
                                <a href="{{ route('admin.reservas.index') }}" class="inline-block bg-orange-600 hover:bg-orange-700 active:bg-orange-800 text-white font-bold py-2 px-6 rounded-lg text-sm transition duration-150 ease-in-out shadow-lg">
                                    Revisar Pendências
                                </a>
                            </div>
                        </div>
                    `;
                } else {
                    // Alerta Verde (Status OK)
                    htmlContent = `
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                            <div class="flex items-center">
                                <svg class="h-6 w-6 flex-shrink-0 mr-3 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="font-medium">Status OK</p>
                                <p class="ml-4 text-sm">Nenhuma pré-reserva pendente. O painel está limpo.</p>
                            </div>
                        </div>
                    `;
                }

                notificationContainer.innerHTML = htmlContent;

            } catch (error) {
                console.error('Erro ao buscar o status de pendências:', error);
                notificationContainer.innerHTML = `
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">Erro de Conexão</p>
                        <p class="ml-4 text-sm">Não foi possível carregar o status de pendências em tempo real. ${error.message}</p>
                    </div>
                `;
            }
        };


        window.onload = function() {
            var calendarEl = document.getElementById('calendar');
            var modal = document.getElementById('event-modal');
            var modalContent = document.getElementById('modal-content');

            // 1. Inicializa a checagem de pendências imediatamente e configura o intervalo
            checkPendingReservations();
            setInterval(checkPendingReservations, 30000);

            // [Lógica do FullCalendar]
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'dayGridMonth',
                height: 'auto',
                timeZone: 'local',

                eventSources: [
                    // 1. Fonte de Reservas Confirmadas (Eventos Azuis)
                    {
                        url: RESERVED_API_URL,
                        method: 'GET',
                        failure: function() {
                            console.error('Falha ao carregar reservas confirmadas via API.');
                            alert('Erro ao carregar reservas confirmadas!');
                        },
                        className: 'fc-event-booked',
                        textColor: 'white'
                    },
                    // 2. Fonte de Horários Disponíveis (Eventos Verdes)
                    {
                        url: AVAILABLE_API_URL,
                        method: 'GET',
                        failure: function() {
                            console.error('Falha ao carregar horários disponíveis via API.');
                            alert('Erro ao carregar horários disponíveis!');
                        },
                        className: 'fc-event-available',
                        display: 'block'
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

                eventClick: function(info) {
                    const event = info.event;
                    const isAvailable = event.classNames.includes('fc-event-available');
                    const modal = document.getElementById('event-modal');

                    // --- LÓGICA DE SLOT DISPONÍVEL (Agendamento Rápido) ---
                    if (isAvailable) {
                        const quickBookingModal = document.getElementById('quick-booking-modal');
                        const dateString = event.start.toISOString().slice(0, 10);

                        // Opções de formatação para exibição
                        const dateDisplay = event.start.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });

                        const timeOptions = { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' };
                        const startTimeInput = event.start.toLocaleTimeString('pt-BR', timeOptions).slice(0, 5); // Ex: "14:00"
                        const endTimeInput = event.end.toLocaleTimeString('pt-BR', timeOptions).slice(0, 5);   // Ex: "15:00"

                        const timeSlotDisplay = startTimeInput + ' - ' + endTimeInput;

                        const price = event.extendedProps.price;
                        const scheduleId = event.extendedProps.schedule_id;

                        // CORREÇÃO CRÍTICA: Converter preço para float ANTES de usar toFixed()
                        const numericPrice = parseFloat(price);

                        // 1. Preencher os campos ocultos do modal (para envio ao servidor)
                        document.getElementById('quick-schedule-id').value = scheduleId;
                        document.getElementById('quick-date').value = dateString;
                        document.getElementById('quick-start-time').value = startTimeInput;
                        document.getElementById('quick-end-time').value = endTimeInput;
                        document.getElementById('quick-price').value = numericPrice;

                        // 2. Injetar a informação visível
                        document.getElementById('slot-info-display').innerHTML = `
                            <p><strong>Data:</strong> ${dateDisplay}</p>
                            <p><strong>Horário:</strong> ${timeSlotDisplay}</p>
                            <p><strong>Valor:</strong> R$ ${numericPrice.toFixed(2).replace('.', ',')}</p>
                        `;

                        // 3. Abrir o modal de agendamento rápido
                        quickBookingModal.classList.remove('hidden');

                    }
                    // --- LÓGICA DE RESERVA EXISTENTE (Modal de Detalhes) ---
                    else if (event.id) { // Só entra aqui se for uma reserva (tem ID)
                        const startTime = event.start;
                        const endTime = event.end;
                        const reservaId = event.id; // Pegando o ID da reserva
                        const modalContent = document.getElementById('modal-content');

                        const dateOptions = { day: '2-digit', month: '2-digit', year: 'numeric' };
                        const timeOptions = { hour: '2-digit', minute: '2-digit' };
                        const dateDisplay = startTime.toLocaleDateString('pt-BR', dateOptions);

                        let timeDisplay = startTime.toLocaleTimeString('pt-BR', timeOptions);
                        if (endTime) {
                            timeDisplay += ' - ' + endTime.toLocaleTimeString('pt-BR', timeOptions);
                        }

                        // Tenta extrair o nome e o preço do título (Ex: "Reservado: Cliente X - R$ 100,00")
                        const titleParts = event.title.split(' - R$ ');
                        const title = titleParts[0];
                        const priceDisplay = titleParts.length > 1 ? `R$ ${titleParts[1]}` : 'N/A';

                        // Monta a URL de redirecionamento usando a Rota e o ID
                        const showUrl = SHOW_RESERVA_URL.replace(':id', reservaId);


                        modalContent.innerHTML = `
                            <p class="font-semibold text-gray-900">${title}</p>
                            <p><strong>Data:</strong> ${dateDisplay}</p>
                            <p><strong>Horário:</strong> ${timeDisplay}</p>
                            <p><strong>Valor:</strong> <span class="text-green-600 font-bold">${priceDisplay}</span></p>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <a href="${showUrl}" class="w-full inline-block text-center px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition duration-150">
                                    Ver Detalhes / Gerenciar Reserva
                                </a>
                            </div>
                        `;

                        modal.classList.remove('hidden');
                    }
                }
            });

            calendar.render();


            // --- LÓGICA DE SUBMISSÃO AJAX DO FORMULÁRIO RÁPIDO (AGORA DENTRO DO window.onload) ---
            const form = document.getElementById('quick-booking-form');
            const quickBookingModal = document.getElementById('quick-booking-modal');

            // Variável de controle local para o bloco finally
            let hasCommunicationError = false;

            if (form) {
                form.addEventListener('submit', async function (e) {
                    e.preventDefault();

                    const submitButton = document.getElementById('submit-quick-booking');
                    submitButton.disabled = true;
                    submitButton.textContent = 'Reservando...';

                    hasCommunicationError = false; // Reset da flag
                    let isSuccess = false;
                    let message = 'Reserva criada com sucesso, mas houve erro de comunicação no retorno.';

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                            }
                        });

                        let result = {};

                        // Tenta decodificar o JSON. Se falhar, cai no catch.
                        try {
                            result = await response.json();
                            isSuccess = response.ok && result.success;
                            message = result.message;
                        } catch (jsonError) {
                            // JSON Corrompido: Seta a flag de erro de comunicação
                            hasCommunicationError = true;

                            console.error('Falha ao decodificar JSON (possível sujeira no PHP):', jsonError);
                            // Tenta obter o texto bruto da resposta para debug:
                            const responseText = await response.text();
                            console.error('Resposta bruta recebida:', responseText);
                        }

                        if (isSuccess) {
                            // Sucesso total (JSON e status OK)
                            alert(message);
                            quickBookingModal.classList.add('hidden');
                            form.reset();
                        } else if (hasCommunicationError) {
                            // Não faz nada aqui, deixa o bloco finally cuidar
                            throw new Error("Falha na Comunicação: Execução movida para o bloco finally.");

                        } else if (!isSuccess && response.status === 409) {
                            // Erro de Conflito (409)
                            alert(message);
                        } else if (!isSuccess && response.status >= 400) {
                            // Outros erros HTTP (4xx, 5xx)
                            alert(message || 'Erro do Servidor ao processar a requisição.');
                        }

                    } catch (error) {
                        // Tratamento de erro de rede ou o erro forçado acima (Falha na Comunicação)
                        console.error('Erro de Rede/Comunicação:', error);

                        if (error.message.includes('Falha na Comunicação') || error instanceof TypeError) {
                            hasCommunicationError = true;
                        }

                        // Dispara o alerta original, já que houve falha na resposta.
                        alert("Erro de conexão ao tentar reservar. Tente novamente.");

                    } finally {
                        // 🚀 PONTO CRÍTICO: ATUALIZAÇÃO DO CALENDÁRIO
                        // Agora 'calendar' é uma variável local garantida neste escopo!
                        if (calendar) {
                            calendar.refetchEvents();

                            // Se houve o erro de comunicação (mas o backend salvou), informamos o usuário.
                            if (hasCommunicationError) {
                                quickBookingModal.classList.add('hidden');
                                alert('A reserva foi salva com sucesso, mas o sistema encontrou um erro de comunicação no retorno. O calendário foi atualizado.');
                            }
                        }

                        // Reseta o estado do botão
                        submitButton.disabled = false;
                        submitButton.textContent = 'Confirmar Agendamento';
                    }
                });
            }
        };
    </script>
</x-app-layout>
