<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard | Calendário de Reservas') }}
        </h2>
    </x-slot>

    <!-- FullCalendar CSS/JS Imports -->
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
        /* CORREÇÃO: Define as propriedades de posicionamento */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex; /* Mantemos o display flex para quando estiver visível */
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        /* CORREÇÃO CRÍTICA: Força o display: none quando a classe 'hidden' está presente.
           Isso garante que ela sobreponha o display: flex acima. */
        .modal-overlay.hidden {
            display: none !important;
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

                <div class="calendar-container">
                    <div id='calendar'></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <!-- Ajustei o onclick para ser mais seguro -->
    <div id="event-modal" class="modal-overlay hidden" onclick="document.getElementById('event-modal').classList.add('hidden')">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">Detalhes da Reserva</h3>
            <div class="space-y-3 text-gray-700" id="modal-content">
            </div>
            <!-- O botão de fechar já estava correto -->
            <button onclick="document.getElementById('event-modal').classList.add('hidden')" class="mt-6 w-full px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition duration-150">
                Fechar
            </button>
        </div>
    </div>

    <!-- FullCalendar Scripts -->
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>

    <script>
        window.onload = function() {
            var calendarEl = document.getElementById('calendar');
            var modal = document.getElementById('event-modal');
            var modalContent = document.getElementById('modal-content');

            // INJEÇÃO DINÂMICA FINAL: $eventsJson
            var eventsJson;
            try {
                // É essencial que a sintaxe seja  para variáveis JSON
                eventsJson = JSON.parse('{!! isset($eventsJson) ? $eventsJson : "[]" !!}');
            } catch (e) {
                console.error("Erro ao parsear $eventsJson. Verifique a saída JSON do Laravel.", e);
                eventsJson = [];
            }

            // [Lógica do FullCalendar]
            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'dayGridMonth',
                height: 'auto',
                timeZone: 'local',
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
                events: eventsJson,
                initialDate: new Date().toISOString().slice(0, 10),

                eventClick: function(info) {
                    const startTime = info.event.start;
                    const endTime = info.event.end;

                    const dateOptions = { day: '2-digit', month: '2-digit', year: 'numeric' };
                    const timeOptions = { hour: '2-digit', minute: '2-digit' };

                    const dateDisplay = startTime.toLocaleDateString('pt-BR', dateOptions);

                    let timeDisplay = startTime.toLocaleTimeString('pt-BR', timeOptions);
                    if (endTime) {
                        timeDisplay += ' - ' + endTime.toLocaleTimeString('pt-BR', timeOptions);
                    }

                    const titleParts = info.event.title.split(' - R$ ');
                    const title = titleParts[0];
                    const priceDisplay = titleParts.length > 1 ? `R$ ${titleParts[1]}` : 'N/A';

                    modalContent.innerHTML = `
                        <p class="font-semibold text-gray-900">${title}</p>
                        <p><strong>Data:</strong> ${dateDisplay}</p>
                        <p><strong>Horário:</strong> ${timeDisplay}</p>
                        <p><strong>Valor:</strong> <span class="text-green-600 font-bold">${priceDisplay}</span></p>
                    `;

                    // Aqui removemos a classe 'hidden' para mostrar a modal
                    modal.classList.remove('hidden');
                }
            });

            calendar.render();
        };
    </script>
</x-app-layout>
