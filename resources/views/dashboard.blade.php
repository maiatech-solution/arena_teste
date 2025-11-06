<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Gerenciamento de Reservas</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos base para o container do calendário */
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
        /* Estilo para o modal (sempre invisível por padrão) */
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
        /* Estilo customizado para garantir que o contêiner de links do menu tenha margem inferior em mobile */
        @media (max-width: 639px) { /* sm breakpoint no tailwind */
            .mobile-nav-stack > * {
                margin-bottom: 0.5rem; /* Adiciona um pequeno espaço entre os botões empilhados */
            }
        }
    </style>

    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/main.min.css' rel='stylesheet' />
</head>
<body class="bg-gray-100 min-h-screen font-sans">

    <header class="bg-indigo-700 text-white shadow-lg">
        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between sm:items-center space-y-4 sm:space-y-0">
            <h1 class="text-2xl font-extrabold tracking-tight">Sistema de Reservas | Gestor</h1>

            <nav class="flex items-center flex-wrap gap-3 mobile-nav-stack mt-2 sm:mt-0">

                <a href="{{ route('admin.horarios.index') }}" class="w-full sm:w-auto text-sm font-medium px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 transition duration-150 text-center">
                    Gerenciar Horários
                </a>

                <a href="{{ route('admin.users.create') }}" class="w-full sm:w-auto text-sm font-medium px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 transition duration-150 text-center">
                    Novo Usuário
                </a>

                @if(isset($reservasPendentesCount) && $reservasPendentesCount > 0)
                    <a href="{{ route('admin.reservas.index') }}" class="w-full sm:w-auto relative inline-flex items-center justify-center p-3 text-sm font-medium text-center text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 transition duration-150 ease-in-out">
                        <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 14 20">
                            <path d="M12.133 10.126A4.062 4.062 0 0 1 1.706 7.82V3.535C1.706 1.183 3.473 0 6.6 0c3.127 0 4.894 1.183 4.894 3.535v4.285c.038.271.184.526.4.708l1.45 1.258a1 1 0 0 1 0 1.54L11.895 12a1 1 0 0 1-1.15 0L9.27 10.975A5.048 5.048 0 0 0 6.6 11c-2.43 0-4.408-1.551-4.894-3.535H1.706A4.062 4.062 0 0 1 0 7.82v1.306a1 1 0 0 0 .272.693l.723.723A.98.98 0 0 0 1.5 12.5v.5a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-.5a.98.98 0 0 0-.272-.693l-.723-.723a1 1 0 0 0-.4-.708Z"/>
                        </svg>
                        <span class="sr-only">Reservas Pendentes</span>
                        <div class="absolute inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-800 border-2 border-white rounded-full -top-2 -end-2">{{ $reservasPendentesCount }}</div>
                    </a>
                @else
                    <a href="{{ route('admin.reservas.index') }}" class="w-full sm:w-auto text-sm font-medium px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 transition duration-150 text-center">
                        Ver Reservas Pendentes
                    </a>
                @endif
            </nav>
        </div>
    </header>
    <main class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Calendário de Reservas Confirmadas</h2>

            @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif

            <div class="calendar-container">
                <div id='calendar'></div>
            </div>
        </div>
    </main>

    <div id="event-modal" class="modal-overlay hidden" onclick="this.classList.add('hidden')">
        <div class="bg-white p-6 rounded-xl shadow-2xl max-w-sm w-full transition-all duration-300 transform scale-100" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-indigo-700 mb-4 border-b pb-2">Detalhes da Reserva</h3>
            <div class="space-y-3 text-gray-700" id="modal-content">
                </div>
            <button onclick="document.getElementById('event-modal').classList.add('hidden')" class="mt-6 w-full px-4 py-2 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition duration-150">
                Fechar
            </button>
        </div>
    </div>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>

    <script>
        // Esta função garante que o script só rode após o DOM estar pronto
        window.onload = function() {
            var calendarEl = document.getElementById('calendar');
            var modal = document.getElementById('event-modal');
            var modalContent = document.getElementById('modal-content');

            // ######################################################################
            // INJEÇÃO DINÂMICA FINAL
            var eventsJson;
            try {
                // Usando {!! $eventsJson !!} para injetar a string JSON sem escapar (solução Blade/Laravel)
                eventsJson = JSON.parse('{!! isset($eventsJson) ? $eventsJson : "[]" !!}');
            } catch (e) {
                console.error("Erro ao parsear $eventsJson. Verifique a saída JSON do Laravel.", e);
                // Se houver erro de parse, usa um array vazio
                eventsJson = [];
            }
            // ######################################################################

            console.log("Eventos carregados:", eventsJson);

            if (typeof FullCalendar === 'undefined') {
                console.error("ERRO CRÍTICO: FullCalendar ainda não está definido.");
                if (calendarEl) {
                    calendarEl.innerHTML = '<div class="text-center p-8 bg-red-100 border border-red-400 text-red-700 rounded-lg">Falha ao carregar o calendário. Verifique a aba Rede (Network) para falhas de CDN.</div>';
                }
                return;
            }

            var calendar = new FullCalendar.Calendar(calendarEl, {
                // Configurações Globais
                locale: 'pt-br',
                initialView: 'dayGridMonth',
                height: 'auto',

                // CORREÇÃO CRÍTICA: Define o fuso horário como 'local' para evitar o deslocamento de 3 horas.
                // Isso diz ao FullCalendar para tratar as strings de data/hora (Ex: 12:00:00) como hora local.
                timeZone: 'local',

                // Configuração para traduzir os botões de visualização
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

                // Carrega os eventos dinâmicos (via Laravel)
                events: eventsJson,

                // A data inicial deve focar no mês de novembro
                initialDate: '2025-11-01',

                // Ação ao clicar em um evento (agora usando um Modal)
                eventClick: function(info) {
                    // Para display, criamos novas datas locais para formatar corretamente
                    const startTime = info.event.start;
                    const endTime = info.event.end;

                    // Opções para toLocaleTimeString e toLocaleDateString
                    const dateOptions = { day: '2-digit', month: '2-digit', year: 'numeric' };
                    const timeOptions = { hour: '2-digit', minute: '2-digit' };

                    const dateDisplay = startTime.toLocaleDateString('pt-BR', dateOptions);
                    let timeDisplay = startTime.toLocaleTimeString('pt-BR', timeOptions);

                    if (endTime) {
                            timeDisplay += ' - ' + endTime.toLocaleTimeString('pt-BR', timeOptions);
                    }

                    // Conteúdo do Modal: Extrai o nome e o preço (se houver)
                    const titleParts = info.event.title.split(' - R$ ');
                    const title = titleParts[0];
                    const priceDisplay = titleParts.length > 1 ? `R$ ${titleParts[1]}` : 'N/A';

                    modalContent.innerHTML = `
                        <p class="font-semibold text-gray-900">${title}</p>
                        <p><strong>Data:</strong> ${dateDisplay}</p>
                        <p><strong>Horário:</strong> ${timeDisplay}</p>
                        <p><strong>Valor:</strong> <span class="text-green-600 font-bold">${priceDisplay}</span></p>
                    `;

                    modal.classList.remove('hidden');
                }
            });

            calendar.render();
        };
    </script>
</body>
</html>
