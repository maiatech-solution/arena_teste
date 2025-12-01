<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }} | Agendamento Online</title>

    {{-- Tailwind CSS & JS --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- FullCalendar Imports --}}
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/main.min.css' rel='stylesheet' />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

        * { font-family: 'Inter', sans-serif; }

        /* Fundo Gradiente para a "Arena" (Ajustado para ser mais sutil e moderno) */
        .arena-bg {
            background: linear-gradient(135deg, #4f46e5 0%, #10b981 100%);
        }

        /* Container do Calendário (Aumentado border-radius e melhorado o box-shadow) */
        .calendar-container {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.2), 0 5px 15px -5px rgba(0, 0, 0, 0.1);
        }

        /* 🛑 CRÍTICO: ESTILO PARA O MODAL (SOBREPOSIÇÃO) 🛑 */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); /* Fundo escuro semi-transparente */
            z-index: 50;
            overflow-y: auto;
        }

        /* Estilos do FullCalendar */
        .fc {
            color: #333;
        }
        .fc-toolbar {
            /* Permite que a barra de ferramentas quebre em linhas */
            flex-wrap: wrap;
            gap: 0.5rem;
            padding-bottom: 10px;
        }
        .fc-toolbar-title {
            font-size: 1.5rem !important;
            white-space: normal;
            text-align: center;
        }
        /* Ajustes responsivos para o FullCalendar */
        @media (max-width: 640px) {
            .fc-header-toolbar {
                flex-direction: column;
                align-items: center;
            }
            .fc-toolbar-chunk {
                margin-top: 10px;
                width: 100%;
                text-align: center;
                display: flex;
                justify-content: center;
            }
            .fc-button {
                padding: 0.25rem 0.5rem;
            }
        }

        /* 🛑 CRÍTICO: ANULAÇÃO DA LÓGICA DE COLISÃO DO FULLCALENDAR NO MODO DIA (Time Grid) 🛑 */
        .fc-timegrid-col-events,
        .fc-timegrid-col-events > div {
            width: 100% !important;
            left: 0 !important;
            right: 0 !important;
            margin-left: 0 !important;
        }

        /* 🛑 NOVO: IMPEDE QUE EVENTOS NÃO DISPONÍVEIS (INVISÍVEIS) CAPTUREM O CLIQUE 🛑 */
        .fc-timegrid-event:not(.fc-event-available) {
            pointer-events: none !important;
        }

        /* Estilo para Eventos Disponíveis (Verde) */
        .fc-timegrid-event.fc-event-available {
            background-color: #10B981 !important;
            border-color: #059669 !important;
            color: white !important;
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 6px;
            opacity: 0.95;
            transition: opacity 0.2s;
            font-size: 0.8rem;
            line-height: 1.3;
            font-weight: 600;

            /* Garante que o botão verde ocupe 100% do espaço forçado acima */
            width: 100% !important;
            left: 0 !important;
            z-index: 2; /* Garante que fique acima dos slots invisíveis/passivos */
        }

        .fc-event-available:hover {
            opacity: 1;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.5), 0 2px 4px -2px rgba(16, 185, 129, 0.5);
        }

        /* Estilos para os marcadores de dia (resumo) */
        .day-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem; /* Ligeiramente maior */
            font-weight: bold;
            padding: 6px; /* Mais preenchimento */
            border-radius: 8px; /* Mais arredondado */
            margin-top: 2px;
            text-align: center;
            line-height: 1.2;
            cursor: default;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
        }

        /* 🛑 CRÍTICO: NOVO ESTILO PARA O CURSOR NOS DIAS CLICÁVEIS 🛑 */
        .fc-daygrid-day.has-slots {
            cursor: pointer;
        }

        .marker-available {
            background-color: #10B981;
            color: white;
            box-shadow: 0 1px 3px 0 rgba(16, 185, 129, 0.4);
        }

        .marker-none {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
            cursor: default;
        }

        /* 🛑 CRÍTICO 1: Oculta o contador nativo "+X more" que está exibindo o valor errado */
        .fc-daygrid-more-link {
            display: none !important;
        }
    </style>
</head>

<body class="font-sans antialiased arena-bg">

{{-- 🛑 MUDANÇA: max-w-5xl para limitar a largura em telas grandes 🛑 --}}
<div class="min-h-screen flex flex-col items-center justify-start p-4 md:p-8 py-16">
    <div class="w-full max-w-5xl mx-auto
        p-6 sm:p-10
        bg-white/95 dark:bg-gray-800/90
        backdrop-blur-sm shadow-2xl shadow-gray-900/70 dark:shadow-indigo-900/50
        rounded-3xl transform transition-all duration-300 ease-in-out">

        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-gray-100 mb-6
            border-b-4 border-indigo-600 dark:border-indigo-400 pb-3 text-center
            tracking-tight">
            ⚽ ELITE SOCCER - Agendamento Online
        </h1>

        <p class="text-gray-600 dark:text-gray-400 mb-8 text-center text-base sm:text-lg font-medium">
            Selecione uma data para ver os horários detalhados e a quantidade de vagas disponíveis.
        </p>


        {{-- No início da seção de mensagens de erro --}}
@if ($errors->has('reserva_duplicada'))
    <div class="bg-yellow-100 dark:bg-yellow-900/50 border-l-4 border-yellow-600 text-yellow-800 dark:text-yellow-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg" role="alert">
        <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <div>
            <span class="font-bold text-lg">Atenção!</span>
            <p class="mt-1">{{ $errors->first('reserva_duplicada') }}</p>
        </div>
    </div>
@endif

        {{-- --- Mensagens de Status (Mantidas) --- --}}
        @if (session('success'))
            <div class="bg-green-100 dark:bg-green-900/50 border-l-4 border-green-600 text-green-800 dark:text-green-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg" role="alert">
                <span class="font-bold text-lg">SUCESSO!</span> <span class="ml-2">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('whatsapp_link'))
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-400 dark:border-green-700 p-8 rounded-3xl relative mb-12 text-center shadow-2xl shadow-green-400/40 dark:shadow-green-900/70" role="alert">
                <p class="font-extrabold mb-3 text-3xl sm:text-4xl text-green-700 dark:text-green-300">PRÉ-RESERVA SOLICITADA!</p>
                <p class="mb-6 text-lg text-gray-700 dark:text-gray-300">
                    Sua pré-reserva foi solicitada! <br> <strong>Clique abaixo imediatamente</strong> para entrar em contato com o gestor da arena e confirmar sua reserva com o o pagamento do sinal via WhatsApp.
                </p>
                <a href="{{ session('whatsapp_link') }}" target="_blank"
                    class="mt-2 inline-flex items-center p-4 px-8 sm:px-12 py-4 sm:py-5 bg-green-600 text-white font-extrabold rounded-full shadow-2xl shadow-green-600/50 hover:bg-green-700 transition duration-300 transform hover:scale-105 active:scale-[0.97] uppercase tracking-wider text-base sm:text-xl">
                    ENTRAR EM CONTATO
                </a>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 italic">O horário será liberado se o comprovante não for enviado.</p>
            </div>
        @endif

        {{-- Alerta Geral de Erro de Submissão (incluindo erro de conflito) --}}
        @if (session('error'))
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg" role="alert">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                <span class="font-bold text-lg">ERRO!</span> <span class="ml-2">{{ session('error') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-8 shadow-lg" role="alert">
                <p class="font-bold flex items-center text-lg"><svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg> Correção Necessária!</p>
                <p class="mt-1">Houve um problema com a sua seleção ou dados. Por favor, verifique os campos destacados.</p>
            </div>
        @endif

        {{-- Calendário FullCalendar --}}
        <div class="calendar-container shadow-2xl">
            <div id='calendar'></div>
        </div>

    </div>
</div>

{{-- --- Modal de Confirmação de Dados --- --}}
<div id="booking-modal" class="modal-overlay hidden items-center justify-center z-50 p-4">
    {{-- 🛑 MUDANÇA: max-w-md para modal mais apertado 🛑 --}}
    <div id="modal-content" class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-100 border-t-8
        @if ($errors->any() && old('data_reserva')) border-red-600 dark:border-red-500 @else border-indigo-600 dark:border-indigo-500 @endif" onclick="event.stopPropagation()">

        {{-- Área de Mensagens de Erro (reutilizada) --}}
        @if ($errors->any() && old('data_reserva'))
            @if ($errors->has('reserva_conflito_id'))
                <div class="mb-6 p-4 bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 text-yellow-700 dark:text-yellow-300 rounded-xl relative shadow-md" role="alert">
                    <p class="font-bold flex items-center text-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                        Vaga Ocupada!
                    </p>
                    <p class="mt-1 font-semibold">
                        Este horário **acabou de ser reservado** por outro cliente ou está em conflito. Por favor, feche o modal e escolha um slot verde diferente no calendário.
                    </p>
                </div>
            @else
                <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/50 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-xl relative shadow-md" role="alert">
                    <p class="font-bold flex items-center text-lg">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                        Correção Necessária!
                    </p>
                    <p class="mt-1">
                        Por favor, verifique os campos destacados em vermelho e tente novamente.
                    </p>
                </div>
            @endif
        @endif

        {{-- Alerta para Erros de Validação Front-End (Substituto de alert()) --}}
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-xl relative shadow-md hidden" role="alert" id="frontend-alert-box">
            <p id="frontend-alert-message" class="font-bold flex items-center text-lg">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                <span class="text-base">Atenção</span>: <span class="ml-1 text-sm font-normal"></span>
            </p>
        </div>

        {{-- 🛑 BLOQUEIO PARA GESTOR/ADMIN LOGADO 🛑 --}}
        @auth
            @if (Auth::user()->isGestor())
                <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded mb-4" role="alert">
                    <p class="font-bold">Acesso Negado</p>
                    <p>Contas de Gestor/Admin não podem fazer reservas pelo painel público. Por favor, deslogue ou use o agendamento rápido no Dashboard.</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="text-red-500 underline hover:text-red-700 text-sm">Deslogar</button>
                    </form>
                </div>
            @endif
        @endauth
        {{-- FIM DO BLOQUEIO PARA GESTOR/ADMIN --}}


        {{-- 🛑 FORMULÁRIO PRINCIPAL (Visível para Guest E Cliente Logado) 🛑 --}}
        @if (!Auth::check() || (Auth::check() && Auth::user()->isClient()))

            <h4 class="text-2xl font-extrabold mb-5 text-gray-900 dark:text-gray-100 border-b pb-2">Confirme Sua Pré-Reserva</h4>

            <form id="booking-form" method="POST" action="{{ route('reserva.store') }}">
                @csrf

                {{-- Campos Hidden da Reserva (Sempre obrigatórios) --}}
                <input type="hidden" name="data_reserva" id="form-date" value="{{ old('data_reserva') }}">
                <input type="hidden" name="hora_inicio" id="form-start" value="{{ old('hora_inicio') }}">
                <input type="hidden" name="hora_fim" id="form-end" value="{{ old('hora_fim') }}">
                <input type="hidden" name="price" id="form-price" value="{{ old('price') }}">
                <input type="hidden" name="reserva_conflito_id" value="" />
                <input type="hidden" name="schedule_id" id="form-schedule-id" value="{{ old('schedule_id') }}">

                {{-- ========================================================= --}}
                {{-- 🛑 LÓGICA CONDICIONAL: DADOS DO CLIENTE 🛑 --}}
                {{-- ========================================================= --}}

                @if (Auth::check() && Auth::user()->isClient())
                    {{-- CLIENTE LOGADO: Exibe os dados estaticamente e envia via hidden fields --}}
                    <div class="mb-8 p-6 bg-green-50 dark:bg-green-900/30 rounded-2xl border border-green-300 dark:border-green-700 shadow-xl">
                        <h5 class="text-xl font-bold mb-3 text-green-700 dark:text-green-400 flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Reserva para sua conta
                        </h4>
                        <p class="text-gray-700 dark:text-gray-300 mb-2">
                            Esta pré-reserva será vinculada automaticamente ao seu cadastro:
                        </p>
                        <ul class="list-none space-y-1 text-sm text-gray-800 dark:text-gray-200 font-semibold">
                            <li>Nome: **{{ Auth::user()->name }}**</li>
                            <li>WhatsApp: **{{ Auth::user()->contato_cliente }}**</li>
                        </ul>
                        {{-- Inputs Hidden para garantir que o backend receba os dados SEM FALHAR A VALIDAÇÃO UNIQUE --}}
                        <input type="hidden" name="nome_cliente" value="{{ Auth::user()->name }}">
                        <input type="hidden" name="contato_cliente" value="{{ Auth::user()->contato_cliente }}">
                        <input type="hidden" name="email_cliente" value="{{ Auth::user()->email }}">
                    </div> {{-- Fim do Bloco CLIENTE LOGADO --}}
                @else

                    <p class="text-gray-700 dark:text-gray-300 mb-6 text-sm">
                        Preencha seus dados para registrar sua pré-reserva. Seus dados serão usados para **criar ou identificar sua conta**.
                    </p>
                    <div class="space-y-4 p-4 bg-indigo-50 dark:bg-gray-900 rounded-xl border border-indigo-200 dark:border-gray-700 mb-8 shadow-inner">
                        <h5 class="text-lg font-bold text-indigo-700 dark:text-indigo-400 border-b pb-2 mb-2">Seus Dados</h5>

                        {{-- Nome Completo --}}
                        <div>
                            <label for="guest-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nome Completo <span class="text-red-500">*</span></label>
                            <input type="text" name="nome_cliente" id="guest-name" required value="{{ old('nome_cliente') }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100 rounded-xl shadow-md p-3 @error('nome_cliente') border-red-500 ring-1 ring-red-500 @enderror">
                            @error('nome_cliente')
                                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- WhatsApp (Contato) --}}
                        <div>
                            <label for="guest-contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">WhatsApp (Apenas números, DDD+numero) <span class="text-red-500">*</span></label>
                            <input type="tel" name="contato_cliente" id="guest-contact" required value="{{ old('contato_cliente') }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100 rounded-xl shadow-md p-3 @error('contato_cliente') border-red-500 ring-1 ring-red-500 @enderror" minlength="10" maxlength="11" oninput="this.value = this.value.replace(/\D/g, '')">
                            @error('contato_cliente')
                                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email (Opcional) --}}
                        <div>
                            <label for="guest-email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email (Opcional)</label>
                            <input type="email" name="email_cliente" id="guest-email" value="{{ old('email_cliente') }}"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100 rounded-xl shadow-md p-3 @error('email_cliente') border-red-500 ring-1 ring-red-500 @enderror">
                            @error('email_cliente')
                                <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                            @enderror
                        </div>
                    </div> {{-- Fim do Bloco GUEST --}}
                @endif


                {{-- ========================================================= --}}
                {{-- DETALHES DA RESERVA (VISUAL) --}}
                {{-- ========================================================= --}}
                <div class="mb-8 p-6 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl border border-indigo-300 dark:border-indigo-700 shadow-xl">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-2 border-b border-indigo-100 dark:border-indigo-800">
                            <span class="font-medium text-lg text-indigo-800 dark:text-indigo-300">Data:</span>
                            <span id="modal-date" class="font-extrabold text-xl text-gray-900 dark:text-gray-100"></span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="font-medium text-xl text-indigo-800 dark:text-indigo-300">Horário:</span>
                            <span id="modal-time" class="font-extrabold text-2xl text-gray-900 dark:text-gray-100"></span>
                        </div>
                    </div>
                    <hr class="border-indigo-200 dark:border-indigo-700 mt-4 mb-4">
                    <div class="flex justify-between items-center pt-2">
                        <span class="font-extrabold text-3xl sm:text-4xl text-green-700 dark:text-green-400">Total:</span>
                        <span class="font-extrabold text-3xl sm:text-4xl text-green-700 dark:text-green-400">R$ <span id="modal-price"></span></span>
                    </div>
                </div>

                <div class="mb-8 p-6 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 rounded-xl shadow-md dark:border-red-400 dark:text-red-200">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 mr-3 text-red-600 flex-shrink-0 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="font-black text-lg uppercase tracking-wider">Atenção!</p>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed font-semibold">
                        Sua vaga é garantida **apenas** após o **envio imediato do comprovante do sinal** via WhatsApp.
                    </p>
                </div>

                {{-- Observações --}}
                <div class="mb-8">
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Observações (Opcional):
                    </label>
                    <textarea name="notes" id="notes" rows="3"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100 rounded-xl shadow-md p-3 focus:border-indigo-500 focus:ring-indigo-500 @error('notes') border-red-500 ring-1 ring-red-500 @enderror"
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-xs text-red-500 mt-1 font-semibold">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-end space-y-4 sm:space-y-0 sm:space-x-6 pt-8 border-t dark:border-gray-700">
                    <button type="button" id="close-modal" class="order-2 sm:order-1 p-4 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        Voltar / Cancelar
                    </button>
                    <button type="submit" id="submit-booking-button" class="order-1 sm:order-2 p-4 bg-indigo-600 text-white font-extrabold rounded-full hover:bg-indigo-700 transition shadow-xl shadow-indigo-500/50 transform hover:scale-[1.03] active:scale-[0.97]">
                        Confirmar Pré-Reserva
                    </button>
                </div>
            </form>

        @endif
        {{-- FIM DO FORMULÁRIO PRINCIPAL --}}

    </div>
</div>

{{-- FullCalendar, Moment.js e Scripts Customizados --}}
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>


<script>
    // 🛑 CRÍTICO: Rota API para buscar os horários disponíveis (slots verdes)
    const AVAILABLE_API_URL = '{{ route("api.horarios.disponiveis") }}';

    // 🛑 CRÍTICO: Rota API para buscar as reservas (ocupados)
    const RESERVED_API_URL = '{{ route("api.reservas.confirmadas") }}';

    // Variáveis de checagem de status de autenticação (simplificadas, mas mantidas)
    const IS_AUTHENTICATED_AS_GESTOR = @json(Auth::check() && optional(Auth::user())->isGestor());

    let calendar; // Variável global para o calendário
    // 🛑 NOVO: CACHE GLOBAL DE DIAS FUTUROS DISPONÍVEIS (YYYY-MM-DD)
    let availableDaysCache = [];

    document.addEventListener('DOMContentLoaded', () => {

        const calendarEl = document.getElementById('calendar');
        const modal = document.getElementById('booking-modal');
        const closeModalButton = document.getElementById('close-modal');

        // Variáveis globais para reabertura de modal (se houver erro de validação)
        const oldDate = @json(old('data_reserva'));
        const oldStart = @json(old('hora_inicio'));
        const oldEnd = @json(old('hora_fim'));
        const oldPrice = @json(old('price'));
        const oldScheduleId = @json(old('schedule_id'));


        // --- Funções Auxiliares ---

        /**
         * Formata a data para o padrão Brasileiro (Dia da semana, dia de Mês de Ano).
         */
        function formatarDataBrasileira(dateString) {
            const date = new Date(dateString + 'T00:00:00');
            if (isNaN(date)) {
                return 'Data Inválida';
            }
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formatted = date.toLocaleDateString('pt-BR', options);
            return formatted.charAt(0).toUpperCase() + formatted.slice(1);
        }

        /**
         * Exibe um alerta temporário no modal (Substitui alert()).
         */
        function showFrontendAlert(message) {
            const alertBox = document.getElementById('frontend-alert-box');
            const alertMessage = document.getElementById('frontend-alert-message').querySelector('span.ml-1');

            alertMessage.textContent = message;
            alertBox.classList.remove('hidden');

            setTimeout(() => {
                alertBox.classList.add('hidden');
            }, 5000); // 5 segundos

            console.error(message);
        }

        /**
         * Limpa a string de telefone, removendo tudo exceto dígitos (0-9).
         */
        function cleanPhoneNumber(value) {
            return value.replace(/\D/g, '');
        }

        // ----------------------------------------------------------------------
        // --- FUNÇÃO CRÍTICA: LÓGICA DE CONTAGEM DE SLOTS DISPONÍVEIS ---
        // ----------------------------------------------------------------------
        /**
         * Conta os slots disponíveis para um determinado dia a partir dos eventos carregados.
         */
        function countAvailableSlots(dateStr) {
            if (!calendar) return 0;

            const now = moment();
            const todayDate = now.format('YYYY-MM-DD');

            // Agora busca todos os eventos no cache do FullCalendar para o dia específico
            const eventsOnDay = calendar.getEvents().filter(event =>
                moment(event.start).format('YYYY-MM-DD') === dateStr
            );

            let finalAvailableSlots = 0;

            eventsOnDay.forEach(event => {
                const isAvailableClass = event.classNames.includes('fc-event-available');
                const eventEnd = moment(event.end);

                // Verifica se o slot disponível é válido (não expirou hoje)
                const isExpiredAvailableSlot = isAvailableClass && dateStr === todayDate && eventEnd.isBefore(now);

                if (isAvailableClass && !isExpiredAvailableSlot) {
                    finalAvailableSlots++;
                }
            });

            return finalAvailableSlots;
        }

        // ----------------------------------------------------------------------
        // --- FUNÇÃO CRÍTICA: LÓGICA DE MARCADORES RESUMO (CONTADOR) & SINCRONIZAÇÃO DE CACHE ---
        // ----------------------------------------------------------------------
        function updateDayMarkers() {
            if (!calendar || calendar.view.type !== 'dayGridMonth') return;

            const dayCells = calendarEl.querySelectorAll('.fc-daygrid-day-frame');
            const today = moment().startOf('day');
            const datesProcessed = [];

            dayCells.forEach(dayEl => {
                const dateEl = dayEl.closest('.fc-daygrid-day');
                const dateStr = dateEl ? dateEl.getAttribute('data-date') : null;
                if (!dateStr) return;

                // 1. Limpa classes de clique e marcadores antigos
                dateEl.classList.remove('has-slots');
                const existingMarker = dayEl.querySelector('.day-marker');
                if (existingMarker) existingMarker.remove();

                // Verifica se o dia é passado
                const isTodayOrFuture = !moment(dateStr).isBefore(today, 'day');

                if (!isTodayOrFuture) {
                    return; // Não mostra marcador em dias passados
                }

                // Conta slots usando a função separada
                const finalAvailableSlots = countAvailableSlots(dateStr);

                // Salva a informação para a correção do cache
                datesProcessed.push({ dateStr, hasSlots: finalAvailableSlots > 0 });

                const markerContainer = dayEl.querySelector('.fc-daygrid-day-bottom');
                if (!markerContainer) return;

                let markerHtml = '';

                // 🛑 LÓGICA: MOSTRA A QUANTIDADE 🛑
                if (finalAvailableSlots > 0) {
                    const plural = finalAvailableSlots > 1 ? 's' : '';
                    markerHtml = `
                        <div class="day-marker marker-available" data-available-slots="${finalAvailableSlots}">
                            ${finalAvailableSlots} horário${plural} disponível${plural}
                        </div>`;
                    // Adiciona classe para permitir clique e estilizar o cursor
                    dateEl.classList.add('has-slots');
                } else {
                    markerHtml = `
                        <div class="day-marker marker-none" data-available-slots="0">
                            Esgotado
                        </div>`;
                }

                // Adiciona ao DOM
                if (markerHtml) {
                    markerContainer.insertAdjacentHTML('beforeend', markerHtml);
                }

                // Remoção forçada do contador nativo no escopo geral (Garantia)
                dayEl.querySelectorAll('.fc-daygrid-more-link').forEach(link => link.remove());
            });

            // 🛑 CRÍTICO: SINCRONIZAÇÃO IMEDIATA DO CACHE DE NAVEGAÇÃO 🛑
            datesProcessed.forEach(({ dateStr, hasSlots }) => {
                const index = availableDaysCache.indexOf(dateStr);

                if (hasSlots) {
                    // Se tem slots e não está no cache, adiciona
                    if (index === -1) {
                        availableDaysCache.push(dateStr);
                    }
                } else {
                    // Se não tem slots e está no cache, remove (ex: foi preenchido)
                    if (index !== -1) {
                        availableDaysCache.splice(index, 1);
                    }
                }
            });
            // Reordena o cache para garantir que a navegação funcione
            availableDaysCache.sort();
            console.log(`[CACHE SYNC] Cache sincronizado para mês visível. Total: ${availableDaysCache.length}`);
        }

        // ----------------------------------------------------------------------
        // --- FUNÇÃO CRÍTICA: POPULA O CACHE DE DIAS DISPONÍVEIS (6 MESES) ---
        // ----------------------------------------------------------------------
        /**
         * Carrega a lista de todos os dias futuros com horários disponíveis e popula o cache.
         */
        async function loadAvailableDaysCache() {
            const today = moment().startOf('day').format('YYYY-MM-DD');
            const sixMonthsLater = moment().add(6, 'months').format('YYYY-MM-DD');

            console.log(`[CACHE DEBUG] Buscando dias disponíveis de ${today} até ${sixMonthsLater}...`);

            const urlWithParams = AVAILABLE_API_URL +
                '?start=' + encodeURIComponent(today) +
                '&end=' + encodeURIComponent(sixMonthsLater);

            try {
                const response = await fetch(urlWithParams);
                if (!response.ok) throw new Error('Falha ao buscar slots para o cache.');
                const events = await response.json();

                // Extrai as datas únicas dos eventos
                const uniqueDates = events.reduce((acc, event) => {
                    const dateStr = moment(event.start).format('YYYY-MM-DD');
                    if (!acc.includes(dateStr)) {
                        acc.push(dateStr);
                    }
                    return acc;
                }, []);

                // Filtra para remover quaisquer datas passadas ou inválidas
                const filteredDates = uniqueDates.filter(dateStr =>
                    !moment(dateStr).isBefore(moment(), 'day')
                );

                // Armazena e ordena
                availableDaysCache = filteredDates.sort();
                console.log(`[CACHE DEBUG] Cache populado com ${availableDaysCache.length} dias disponíveis:`, availableDaysCache);

            } catch (error) {
                console.error('[CACHE ERROR] Erro ao popular cache de dias disponíveis:', error);
            }
        }


        // ----------------------------------------------------------------------
        // --- LÓGICA DE PULO DE DIAS ESGOTADOS NA NAVEGAÇÃO ---
        // ----------------------------------------------------------------------

        /**
         * Encontra a próxima data (ou anterior) com slots disponíveis usando o cache.
         */
        function findNextAvailableDate(currentDateStr, direction) {

            if (availableDaysCache.length === 0) {
                console.warn("[NAV DEBUG] Cache de dias vazia. Navegação indisponível.");
                return null;
            }

            const currentDate = moment(currentDateStr);
            let nextDate = null;

            if (direction === 1) {
                // AVANÇAR: Encontra o primeiro dia no cache que é estritamente DEPOIS do dia atual
                for (const dateStr of availableDaysCache) {
                    const cacheDate = moment(dateStr);
                    // O cache já está ordenado, basta encontrar o primeiro depois da data atual
                    if (cacheDate.isAfter(currentDate, 'day')) {
                        nextDate = dateStr;
                        break;
                    }
                }
            } else if (direction === -1) {
                // RETROCEDER: Encontra o último dia no cache que é estritamente ANTES do dia atual
                // O cache está ordenado, então procuramos de trás para frente
                for (let i = availableDaysCache.length - 1; i >= 0; i--) {
                    const dateStr = availableDaysCache[i];
                    const cacheDate = moment(dateStr);

                    if (cacheDate.isBefore(currentDate, 'day')) {
                        nextDate = dateStr;
                        break;
                    }
                }
            }

            console.log(`[NAV DEBUG] Próxima data encontrada na direção ${direction}: ${nextDate}`);
            return nextDate;
        }

        /**
         * FUNÇÃO CRÍTICA: Lógica de clique para os botões de navegação customizados.
         */
        function handleCustomNavigation(direction) {
            // Esta função só deve ser chamada para timeGridDay
            if (calendar.view.type !== 'timeGridDay') {
                 console.warn("[NAV DEBUG] Navegação customizada ignorada: Não está no modo Dia.");
                 return;
            }

            const currentDateStr = calendar.getDate().toISOString().split('T')[0];
            const nextAvailableDate = findNextAvailableDate(currentDateStr, direction);
            const today = moment().startOf('day').format('YYYY-MM-DD');

            if (nextAvailableDate) {
                // Navega para a data que encontramos (o próximo slot verde)
                calendar.changeView('timeGridDay', nextAvailableDate);
            } else {
                // Não encontrou mais datas disponíveis na direção
                if (direction === 1) {
                    showFrontendAlert(`Não há mais horários disponíveis após ${formatarDataBrasileira(currentDateStr)}.`);
                } else {
                    // Se for para retroceder e não encontrou nada, tentamos o dia de hoje, se for no futuro
                    if (moment(currentDateStr).isAfter(moment(today), 'day')) {
                        calendar.changeView('timeGridDay', today);
                        showFrontendAlert(`Você voltou para o dia de hoje, ${formatarDataBrasileira(today)}.`);
                    } else {
                        showFrontendAlert(`Não é possível navegar para dias anteriores com horários disponíveis.`);
                    }
                }
            }
        }


        // CRÍTICO: Lógica de limpeza no input de telefone
        const guestContactInput = document.getElementById('guest-contact');
        if (guestContactInput) {
            guestContactInput.addEventListener('input', function() {
                this.value = cleanPhoneNumber(this.value);
            });
        }


        // === Inicialização do FullCalendar ===
        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'pt-br',
            initialView: 'dayGridMonth',
            height: 'auto',
            timeZone: 'local',

            // 🛑 CRÍTICO: DEFINIÇÃO DOS BOTÕES CUSTOMIZADOS 🛑
            customButtons: {
                customPrev: {
                    text: 'Anterior', // Texto para o botão (opcional)
                    icon: 'chevron-left',
                    click: function() {
                        // Modo Mês: Navegação nativa (muda o mês)
                        if (calendar.view.type === 'dayGridMonth') {
                            calendar.prev();
                        } else { // Modo Dia: Navegação customizada (pula dias esgotados)
                            handleCustomNavigation(-1);
                        }
                    }
                },
                customNext: {
                    text: 'Próximo', // Texto para o botão (opcional)
                    icon: 'chevron-right',
                    click: function() {
                        // Modo Mês: Navegação nativa (muda o mês)
                        if (calendar.view.type === 'dayGridMonth') {
                            calendar.next();
                        } else { // Modo Dia: Navegação customizada (pula dias esgotados)
                            handleCustomNavigation(1);
                        }
                    }
                }
            },

            eventSources: [
                // 1. Reservas Reais (Ocupados - Sem className 'available') - Apenas para bloqueio visual/clique
                {
                    url: RESERVED_API_URL,
                    method: 'GET',
                    failure: function() {
                        console.error('Falha ao carregar reservas reais.');
                    },
                    // Cor totalmente transparente e prioridade para BLOQUEAR.
                    color: 'transparent',
                    textColor: 'transparent',
                    borderColor: 'transparent',
                    editable: false,
                    priority: 5,
                },
                // 2. Slots Disponíveis (Grade Fixa - Com className 'available') - Fonte para a contagem
                {
                    id: 'available-slots-source-id',
                    className: 'fc-event-available',
                    display: 'block',
                    priority: 1,
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

                                    if (eventDate !== todayDate) {
                                        return true;
                                    }

                                    const eventEnd = moment(event.end);
                                    // Filtra slots disponíveis que já expiraram hoje
                                    return eventEnd.isAfter(now);
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
                dayGridMonth: {
                    buttonText: 'Mês',
                    dayMaxEvents: 0,
                },
                timeGridDay: {
                    buttonText: 'Dia',
                    slotMinTime: '06:00:00',
                    slotMaxTime: '24:00:00'
                }
            },
            headerToolbar: {
                // Usa os botões customizados que chamam nossa lógica
                left: 'customPrev,customNext today',
                center: 'title',
                right: 'dayGridMonth,timeGridDay'
            },
            editable: false,
            initialDate: new Date().toISOString().slice(0, 10),

            validRange: function(now) {
                return {
                    start: now.toISOString().split('T')[0]
                };
            },

            eventsSet: function(info) {
                // 1. Chama o marcador (cálculo correto) E SINCRONIZAÇÃO DE CACHE
                updateDayMarkers();

                // 2. Remoção forçada do contador nativo no escopo geral (Garantia)
                document.querySelectorAll('.fc-daygrid-more-link').forEach(link => link.remove());
            },

            eventDidMount: function(info) {
                const event = info.event;
                const isAvailable = event.classNames.includes('fc-event-available');

                // 🛑 LÓGICA DE VISIBILIDADE CRÍTICA (CORREÇÃO DE EMPILHAMENTO) 🛑

                if (info.view.type === 'dayGridMonth') {
                    // Mês: Esconde TODOS os eventos para priorizar o marcador resumo
                    info.el.style.display = 'none';
                }

                if (info.view.type === 'timeGridDay') {

                    if (!isAvailable) {
                        // 1. Se for o slot Reservado (Invisível/Transparente):
                        info.el.style.display = 'none';
                        return;
                    }

                    // 2. Se for um slot disponível (verde - isAvailable é true):

                    // Procura por QUALQUER evento real (não disponível) que se sobreponha a este slot fixo (verde)
                    const isCoveredByRealReservation = calendar.getEvents().some(otherEvent => {
                        // Ignora a si mesmo e outros slots fixos
                        if (otherEvent.id === event.id || otherEvent.classNames.includes('fc-event-available')) {
                            return false;
                        }

                        // Checa se o outro evento (Reserva Real, agora invisível) se sobrepõe
                        const start = moment(event.start);
                        const end = moment(event.end);
                        const otherStart = moment(otherEvent.start);
                        const otherEnd = moment(otherEvent.end);

                        // Lógica de sobreposição
                        return (start.isBefore(otherEvent.end) && otherStart.isBefore(event.end));
                    });

                    if (isCoveredByRealReservation) {
                        // Se há uma reserva real cobrindo este slot, ESCONDA O SLOT VERDE.
                        info.el.style.display = 'none';
                    } else {
                        // Slot verde, não sobreposto: mantenha visível e clicável
                        info.el.style.cursor = 'pointer';
                    }
                }
            },

            // 🛑 dateClick: Bloqueia o clique em dias esgotados (Mês -> Dia) 🛑
            dateClick: function(info) {
                const clickedDateStr = info.dateStr;
                const clickedDate = moment(clickedDateStr);
                const today = moment().startOf('day');

                if (clickedDate.isBefore(today, 'day')) {
                    return; // Ignora cliques em dias passados
                }

                // Checa a disponibilidade usando a função que agora está no escopo correto
                const availableSlotsCount = countAvailableSlots(clickedDateStr);

                if (availableSlotsCount > 0) {
                    // Se houver slots disponíveis, muda para a visão de Dia
                    calendar.changeView('timeGridDay', clickedDateStr);
                } else {
                    // Se estiver esgotado, exibe alerta e não muda a view
                    showFrontendAlert(`❌ O dia ${formatarDataBrasileira(clickedDateStr)} está esgotado ou não tem horários disponíveis.`);
                }
            },

            eventClick: function(info) {
                const event = info.event;
                const isAvailable = event.classNames.includes('fc-event-available');

                // --- 🛑 LÓGICA DE SLOT DISPONÍVEL ---
                if (isAvailable) {

                    // 1. Bloqueio extra para Gestores logados
                    if (IS_AUTHENTICATED_AS_GESTOR) {
                        showFrontendAlert("❌ Você está logado como Gestor/Admin. Use o Dashboard para agendamentos rápidos ou deslogue.");
                        return;
                    }

                    const startDate = moment(event.start);
                    const endDate = moment(event.end);
                    const extendedProps = event.extendedProps || {};

                    if (!event.id || !startDate.isValid() || !endDate.isValid() || extendedProps.price === undefined) {
                        showFrontendAlert("❌ Não foi possível carregar os detalhes do horário. Tente novamente.");
                        return;
                    }

                    const dateString = startDate.format('YYYY-MM-DD');
                    const startTimeInput = startDate.format('H:mm');
                    const endTimeInput = endDate.format('H:mm');
                    const timeSlotDisplay = startDate.format('HH:mm') + ' - ' + endDate.format('HH:mm');

                    const priceRaw = extendedProps.price || 0;
                    const priceDisplay = parseFloat(priceRaw).toFixed(2).replace('.', ',');
                    const scheduleId = event.id;

                    // 2.1 Popula o Modal VISUAL
                    document.getElementById('modal-date').textContent = formatarDataBrasileira(dateString);
                    document.getElementById('modal-time').textContent = timeSlotDisplay;
                    document.getElementById('modal-price').textContent = priceDisplay;

                    // 2.2 Popula os campos HIDDEN do formulário
                    document.getElementById('form-date').value = dateString;
                    document.getElementById('form-start').value = startTimeInput;
                    document.getElementById('form-end').value = endTimeInput;
                    document.getElementById('form-price').value = priceRaw;
                    document.getElementById('form-schedule-id').value = scheduleId;

                    // 2.3 Exibir o modal (AQUI É ONDE ELE DEVE ABRIR CORRETAMENTE COM O NOVO CSS)
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');

                } else {
                    // Clicou em um evento Ocupado/Fechado - Ação de ignorar
                    if (modal.classList.contains('hidden')) {
                        showFrontendAlert("❌ Este horário está ocupado ou é uma pré-reserva. Por favor, clique em um slot verde (disponível).");
                    } else {
                        console.log("Usuário clicou em slot ocupado, modal já estava visível.");
                    }
                }
            }
        });

        calendar.render();

        window.calendar = calendar; // Mantido para debugging externo, se necessário.

        // 🛑 CRÍTICO: CHAMA O CARREGAMENTO DO CACHE NO INÍCIO E PERIODICAMENTE 🛑
        loadAvailableDaysCache();
        // Chama a cada 60s o carregamento de 6 meses (se um mês não estiver na tela, ele pega a info)
        setInterval(loadAvailableDaysCache, 60000);

        // === Lógica de Reabertura do Modal em caso de Erro de Validação ===
        if (oldDate && oldStart) {
            const formattedOldPrice = parseFloat(oldPrice).toFixed(2).replace('.', ',');

            document.getElementById('modal-date').textContent = formatarDataBrasileira(oldDate);
            document.getElementById('modal-time').textContent = `${oldStart} - ${oldEnd}`;
            document.getElementById('modal-price').textContent = formattedOldPrice;
            document.getElementById('form-schedule-id').value = oldScheduleId;

            calendar.changeView('timeGridDay', oldDate);

            modal.classList.remove('hidden');
            modal.classList.add('flex'); // Reaplicação do flex para exibir
        }

        // Listener para fechar o modal
        closeModalButton.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex'); // Removendo o flex ao esconder
        });

        // Listener para fechar o modal ao clicar no overlay
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex'); // Removendo o flex ao esconder
            }
        });

    });
</script>

</body>
</html>
