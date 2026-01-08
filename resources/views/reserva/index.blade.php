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

        * {
            font-family: 'Inter', sans-serif;
        }

        .arena-bg {
            background: linear-gradient(135deg, #4f46e5 0%, #10b981 100%);
        }

        .calendar-container {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.2), 0 5px 15px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            /* Garantir que o modal fique acima do calendário */
            overflow-y: auto;
        }

        .fc {
            color: #333;
        }

        .fc-toolbar {
            flex-wrap: wrap;
            gap: 0.5rem;
            padding-bottom: 10px;
        }

        .fc-toolbar-title {
            font-size: 1.5rem !important;
            white-space: normal;
            text-align: center;
        }

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

        /* 🛑 CORREÇÃO DE COLISÃO: Garante que o container não bloqueie o clique 🛑 */
        .fc-timegrid-col-events>div,
        .fc-timegrid-event-harness {
            width: 100% !important;
            left: 0 !important;
            right: 0 !important;
            margin-left: 0 !important;
            pointer-events: none !important;
            /* O container fica "transparente" ao mouse */
        }

        /* Reativar clique APENAS no evento verde e garantir que ele fique na frente */
        .fc-event-available {
            pointer-events: auto !important;
            /* O evento verde captura o mouse */
            cursor: pointer !important;
            z-index: 50 !important;
            background-color: #10B981 !important;
            border-color: #059669 !important;
            color: white !important;
            padding: 2px 5px;
            border-radius: 6px;
            opacity: 0.95;
            transition: all 0.2s;
            font-size: 0.85rem;
            line-height: 1.3;
            font-weight: 700;
        }

        .fc-event-available:hover {
            opacity: 1;
            transform: scale(1.01);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        /* Ocultar eventos que não são verdes no modo Dia */
        .fc-timegrid-event:not(.fc-event-available) {
            display: none !important;
        }

        /* Estilos do marcador do mês */
        .day-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            padding: 6px;
            border-radius: 8px;
            margin-top: 2px;
            text-align: center;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
        }

        .fc-daygrid-day.has-slots {
            cursor: pointer;
        }

        .marker-available {
            background-color: #10B981;
            color: white;
        }

        .marker-none {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }

        .fc-daygrid-more-link {
            display: none !important;
        }
    </style>
</head>

<body class="font-sans antialiased arena-bg">

    {{-- 🛑 MUDANÇA: max-w-5xl para limitar a largura em telas grandes 🛑 --}}
    <div class="min-h-screen flex flex-col items-center justify-start p-4 md:p-8 py-16">
        <div
            class="w-full max-w-5xl mx-auto
        p-6 sm:p-10
        bg-white/95 dark:bg-gray-800/90
        backdrop-blur-sm shadow-2xl shadow-gray-900/70 dark:shadow-indigo-900/50
        rounded-3xl transform transition-all duration-300 ease-in-out">

            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 dark:text-gray-100 mb-6 border-b-4 border-indigo-600 dark:border-indigo-400 pb-3 text-center tracking-tight">
                ⚽ Agendamento Online
            </h1>

            <p class="text-gray-600 dark:text-gray-400 mb-8 text-center text-base sm:text-lg font-medium">
                Selecione uma data para ver os horários detalhados e a quantidade de vagas disponíveis.
            </p>


            {{-- No início da seção de mensagens de erro --}}
            @if ($errors->has('reserva_duplicada'))
            <div class="bg-yellow-100 dark:bg-yellow-900/50 border-l-4 border-yellow-600 text-yellow-800 dark:text-yellow-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg"
                role="alert">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                <div>
                    <span class="font-bold text-lg">Atenção!</span>
                    <p class="mt-1">{{ $errors->first('reserva_duplicada') }}</p>
                </div>
            </div>
            @endif

            {{-- --- Mensagens de Status (Mantidas) --- --}}
            @if (session('success'))
            <div class="bg-green-100 dark:bg-green-900/50 border-l-4 border-green-600 text-green-800 dark:text-green-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg"
                role="alert">
                <span class="font-bold text-lg">SUCESSO!</span> <span class="ml-2">{{ session('success') }}</span>
            </div>
            @endif

            @if (session('whatsapp_link'))
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-400 dark:border-green-700 p-8 rounded-3xl relative mb-12 text-center shadow-2xl shadow-green-400/40 dark:shadow-green-900/70"
                role="alert">
                <p class="font-extrabold mb-3 text-3xl sm:text-4xl text-green-700 dark:text-green-300">PRÉ-RESERVA
                    SOLICITADA!</p>
                <p class="mb-6 text-lg text-gray-700 dark:text-gray-300">
                    Sua pré-reserva foi solicitada! <br> <strong>Clique abaixo imediatamente</strong> para entrar em
                    contato com o gestor da arena e confirmar sua reserva com o o pagamento do sinal via WhatsApp.
                </p>
                <a href="{{ session('whatsapp_link') }}" target="_blank"
                    class="mt-2 inline-flex items-center p-4 px-8 sm:px-12 py-4 sm:py-5 bg-green-600 text-white font-extrabold rounded-full shadow-2xl shadow-green-600/50 hover:bg-green-700 transition duration-300 transform hover:scale-105 active:scale-[0.97] uppercase tracking-wider text-base sm:text-xl">
                    ENTRAR EM CONTATO
                </a>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 italic">O horário será liberado se o
                    comprovante não for enviado.</p>
            </div>
            @endif

            {{-- Alerta Geral de Erro de Submissão (incluindo erro de conflito) --}}
            @if (session('error'))
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg"
                role="alert">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd" />
                </svg>
                <span class="font-bold text-lg">ERRO!</span> <span class="ml-2">{{ session('error') }}</span>
            </div>
            @endif
            @if ($errors->any())
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-8 shadow-lg"
                role="alert">
                <p class="font-bold flex items-center text-lg"><svg class="w-5 h-5 mr-2" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg> Correção Necessária!</p>
                <p class="mt-1">Houve um problema com a sua seleção ou dados. Por favor, verifique os campos
                    destacados.</p>
            </div>
            @endif

            {{-- 🏟️ SELEÇÃO DE ARENA (Aparece primeiro) --}}
            <div id="arena-selection-container" class="mb-10 transition-all duration-500">
                @if ($errors->any())
                <div class="bg-red-500 text-white p-4 rounded-lg mb-4">
                    <strong>Erro de Validação:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 text-center italic uppercase tracking-widest">
                    Escolha sua Quadra
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach(\App\Models\Arena::all() as $arena)
                    <button onclick="selectArena('{{ $arena->id }}', '{{ $arena->name }}')"
                        class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 border-2 border-transparent hover:border-indigo-500 p-1 text-left">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="p-3 bg-indigo-100 dark:bg-indigo-900/50 rounded-xl text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </span>
                                <span class="text-xs font-bold text-green-500 uppercase tracking-tighter bg-green-50 dark:bg-green-900/30 px-2 py-1 rounded">Disponível</span>
                            </div>
                            <h3 class="text-xl font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight">{{ $arena->name }}</h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mt-1 font-medium italic">Clique para ver os horários desta quadra</p>
                        </div>
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- 📅 CONTAINER DO CALENDÁRIO (Começa oculto e sem ID duplicado lá embaixo) --}}
            <div id="calendar-wrapper" class="hidden opacity-0 transform translate-y-4 transition-all duration-500">
                <div class="flex flex-col sm:flex-row items-center justify-between mb-6 gap-4 px-2">
                    <button onclick="resetSelection()" class="text-indigo-600 dark:text-indigo-400 font-bold flex items-center gap-2 hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        VOLTAR E TROCAR QUADRA
                    </button>

                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 text-[10px] font-black uppercase tracking-widest">Quadra Selecionada:</span>
                        <span id="selected-arena-badge" class="bg-indigo-600 text-white px-6 py-2 rounded-full font-black text-sm uppercase shadow-lg shadow-indigo-500/30"></span>
                    </div>
                </div>

                <div class="calendar-container shadow-2xl">
                    <div id='calendar'></div>
                </div>
            </div>

        </div>
    </div>



    {{-- --- MODAL DE CONFIRMAÇÃO DE AGENDAMENTO (MULTIQUADRA) --- --}}
    <div id="booking-modal"
        class="modal-overlay hidden fixed inset-0 items-center justify-center z-[9999] p-4 backdrop-blur-sm bg-black/60 transition-opacity duration-300">

        <div id="modal-content"
            class="bg-white dark:bg-gray-800 p-8 sm:p-10 rounded-[2.5rem] shadow-2xl w-full max-w-2xl max-h-[95vh] overflow-y-auto transform transition-all duration-300 scale-100 border-t-[12px] 
        @if ($errors->any()) border-red-600 dark:border-red-500 @else border-indigo-600 dark:border-indigo-500 @endif"
            onclick="event.stopPropagation()">

            {{-- Erros de Validação do Laravel --}}
            @if ($errors->any())
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-r-xl shadow-sm">
                <p class="font-black flex items-center text-lg uppercase tracking-tight">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Correção Necessária
                </p>
                <ul class="mt-1 text-sm font-semibold">
                    @foreach ($errors->all() as $error)
                    <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Título e Identificação da Arena --}}
            <div class="mb-8 text-center sm:text-left">
                <h4 class="text-3xl font-black text-gray-900 dark:text-gray-100 tracking-tight leading-none">Dados da Reserva</h4>
                <div class="mt-3 inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 px-4 py-2 rounded-full border border-indigo-100 dark:border-indigo-800">
                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="text-xs font-black uppercase text-indigo-700 dark:text-indigo-300 tracking-widest">
                        Agendando na: <span id="modal-arena-name-display">---</span>
                    </span>
                </div>
            </div>

            <form id="booking-form" method="POST" action="{{ route('reserva.store') }}">
                @csrf

                {{-- --- CAMPOS OCULTOS --- --}}
                <input type="hidden" name="data_reserva" id="form-date" value="{{ old('data_reserva') }}">
                <input type="hidden" name="hora_inicio" id="form-start" value="{{ old('hora_inicio') }}">
                <input type="hidden" name="hora_fim" id="form-end" value="{{ old('hora_fim') }}">
                <input type="hidden" name="price" id="form-price" value="{{ old('price') }}">
                <input type="hidden" name="schedule_id" id="form-schedule-id" value="{{ old('schedule_id') }}">
                <input type="hidden" name="arena_id" id="form-arena-id" value="{{ old('arena_id') }}">
                <input type="hidden" name="reserva_conflito_id" value="" />
                <input type="hidden" name="email_cliente" value="{{ Auth::check() ? Auth::user()->email : old('email_cliente') }}">

                {{-- Resumo Visual --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <div class="bg-indigo-50/50 dark:bg-indigo-900/10 p-5 rounded-3xl border border-indigo-100/50 dark:border-indigo-800/50 transition-colors">
                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-500 block mb-1">Data Agendada</span>
                        <span id="modal-date" class="font-bold text-gray-800 dark:text-gray-100 block leading-tight text-lg"></span>
                    </div>
                    <div class="bg-indigo-50/50 dark:bg-indigo-900/10 p-5 rounded-3xl border border-indigo-100/50 dark:border-indigo-800/50 text-center sm:text-left transition-colors">
                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-500 block mb-1">Horário</span>
                        <span id="modal-time" class="font-black text-3xl text-indigo-600 dark:text-indigo-400 block leading-none"></span>
                    </div>
                </div>

                {{-- Dados do Cliente --}}
                <div class="mb-8">
                    @if (Auth::check() && Auth::user()->isClient())
                    <div class="p-6 bg-green-50 dark:bg-green-900/20 rounded-3xl border border-green-200 dark:border-green-800 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-green-600 uppercase tracking-widest mb-1">Reserva vinculada a:</p>
                            <p class="text-xl font-black text-gray-900 dark:text-gray-100 leading-none">{{ Auth::user()->name }}</p>
                            <p class="text-sm text-green-700 dark:text-green-400 font-medium mt-2 italic">{{ Auth::user()->contato_cliente }}</p>
                        </div>
                        <input type="hidden" name="nome_cliente" value="{{ Auth::user()->name }}">
                        <input type="hidden" name="contato_cliente" value="{{ Auth::user()->contato_cliente }}">
                    </div>
                    @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="group">
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1 transition-colors group-focus-within:text-indigo-500">Nome Completo</label>
                            <input type="text" name="nome_cliente" placeholder="Seu nome" required
                                value="{{ old('nome_cliente') }}"
                                class="w-full bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 dark:text-white rounded-2xl p-4 focus:border-indigo-500 outline-none transition-all shadow-sm focus:bg-white dark:focus:bg-gray-800">
                        </div>
                        <div class="group">
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-2 ml-1 transition-colors group-focus-within:text-indigo-500">WhatsApp (Com DDD)</label>
                            <input type="tel" name="contato_cliente" id="guest-contact"
                                placeholder="919XXXXXXXX" required value="{{ old('contato_cliente') }}"
                                class="w-full bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 dark:text-white rounded-2xl p-4 focus:border-indigo-500 outline-none transition-all shadow-sm focus:bg-white dark:focus:bg-gray-800"
                                maxlength="11">
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Aviso de Pagamento --}}
                <div class="mb-8 p-6 bg-red-50 dark:bg-red-900/20 border-l-8 border-red-600 rounded-r-3xl shadow-sm">
                    <p class="text-xs sm:text-sm text-red-800 dark:text-red-300 font-bold leading-relaxed">
                        Importante: Sua vaga só é garantida após o envio imediato do comprovante de sinal via WhatsApp.
                    </p>
                </div>

                {{-- Rodapé / Ações --}}
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                    <div class="text-center sm:text-left">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-1">Total da Reserva</span>
                        <span class="text-4xl font-black text-green-600 dark:text-green-400 leading-none">
                            R$ <span id="modal-price">0,00</span>
                        </span>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <button type="button" id="close-modal"
                            class="px-8 py-4 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-black rounded-2xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-all uppercase text-xs tracking-widest active:scale-95">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-8 py-4 bg-indigo-600 text-white font-black rounded-2xl hover:bg-indigo-700 shadow-xl shadow-indigo-500/40 transition-all transform hover:scale-105 active:scale-95 uppercase text-xs tracking-widest">
                            Confirmar Jogo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    {{-- FullCalendar, Moment.js e Scripts Customizados --}}
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/index.global.min.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/locale/pt-br.min.js'></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <script>
        // 1. Definições Globais
        window.selectedArenaId = null;
        window.selectedArenaName = "";
        window.calendar = null;

        const AVAILABLE_API_URL = "{{ route('api.horarios.disponiveis') }}";
        // Correção na lógica de parsing do booleano PHP
        const IS_AUTHENTICATED_AS_GESTOR = JSON.parse("{{ Auth::check() && optional(Auth::user())->isGestor() ? 'true' : 'false' }}");


        // 2. FUNÇÕES DE NAVEGAÇÃO (Escopo Global)

        // Selecionar a Quadra
        window.selectArena = function(id, name) {
            console.log("Selecionando arena:", id, name);
            window.selectedArenaId = id;
            window.selectedArenaName = name;

            const selection = document.getElementById('arena-selection-container');
            const wrapper = document.getElementById('calendar-wrapper');

            if (selection && wrapper) {
                selection.classList.add('hidden');
                wrapper.classList.remove('hidden');

                // Forçar transição visual
                setTimeout(() => {
                    wrapper.classList.remove('opacity-0', 'translate-y-4');
                    wrapper.classList.add('opacity-100', 'translate-y-0');
                }, 50);

                document.getElementById('selected-arena-badge').textContent = "Quadra: " + name;
                document.getElementById('form-arena-id').value = id;

                if (window.calendar) {
                    window.calendar.removeAllEvents(); // Limpa lixo visual
                    window.calendar.refetchEvents(); // Busca dados da nova quadra
                    setTimeout(() => {
                        window.calendar.updateSize(); // Ajusta layout branco
                    }, 200);
                }
            }
        };

        // 🎯 NOVO: Voltar para a lista de quadras
        window.resetSelection = function() {
            console.log("Resetando seleção de arena...");

            window.selectedArenaId = null;
            window.selectedArenaName = "";

            const selection = document.getElementById('arena-selection-container');
            const wrapper = document.getElementById('calendar-wrapper');

            if (selection && wrapper) {
                // Inicia animação de saída
                wrapper.classList.add('opacity-0', 'translate-y-4');

                setTimeout(() => {
                    wrapper.classList.add('hidden');
                    selection.classList.remove('hidden');

                    if (window.calendar) {
                        window.calendar.removeAllEvents(); // Limpa o calendário para a próxima vez
                        window.calendar.changeView('dayGridMonth'); // Volta para visão mensal
                    }
                }, 300);
            }
        };

        // 3. AUXILIARES DO CALENDÁRIO
        function countAvailableSlots(dateStr) {
            if (!window.calendar) return 0;
            return window.calendar.getEvents().filter(e =>
                moment(e.start).format('YYYY-MM-DD') === dateStr &&
                e.classNames.includes('fc-event-available')
            ).length;
        }

        function updateDayMarkers() {
            const dayCells = document.querySelectorAll('.fc-daygrid-day');
            dayCells.forEach(dateEl => {
                const dateStr = dateEl.getAttribute('data-date');
                if (!dateStr) return;

                const frame = dateEl.querySelector('.fc-daygrid-day-frame');
                const bottomContainer = dateEl.querySelector('.fc-daygrid-day-bottom') || frame;

                const old = dateEl.querySelector('.day-marker');
                if (old) old.remove();

                const slots = countAvailableSlots(dateStr);
                let html = '';

                if (slots > 0) {
                    html = `<div class="day-marker" style="background:#10b981; color:white; font-size:11px; font-weight:bold; padding:4px; text-align:center; border-radius:6px; margin:2px;">${slots} Horários</div>`;
                } else if (moment(dateStr).isSameOrAfter(moment(), 'day')) {
                    html = `<div class="day-marker" style="background:#fee2e2; color:#991b1b; font-size:11px; padding:4px; text-align:center; border-radius:6px; margin:2px;">Esgotado</div>`;
                }
                bottomContainer.insertAdjacentHTML('beforeend', html);
            });
        }

        // 4. INICIALIZAÇÃO DO DOM
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) return;

            window.calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'pt-br',
                initialView: 'dayGridMonth',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridDay'
                },
                eventSources: [{
                    url: AVAILABLE_API_URL,
                    extraParams: function() {
                        return {
                            arena_id: window.selectedArenaId || 0
                        };
                    },
                    success: function() {
                        setTimeout(updateDayMarkers, 100);
                    }
                }],
                datesSet: function() {
                    setTimeout(updateDayMarkers, 100);
                },
                eventDidMount: function(info) {
                    if (info.view.type === 'dayGridMonth') info.el.style.display = 'none';
                },
                dateClick: function(info) {
                    if (countAvailableSlots(info.dateStr) > 0) {
                        window.calendar.changeView('timeGridDay', info.dateStr);
                    }
                },
                eventClick: function(info) {
                    if (!info.event.classNames.includes('fc-event-available') || IS_AUTHENTICATED_AS_GESTOR) return;

                    const modal = document.getElementById('booking-modal');
                    const start = moment(info.event.start);
                    const end = moment(info.event.end);

                    document.getElementById('modal-arena-name-display').textContent = window.selectedArenaName;
                    document.getElementById('modal-date').textContent = start.format('DD/MM/YYYY');
                    document.getElementById('modal-time').textContent = start.format('HH:mm') + " - " + end.format('HH:mm');

                    const price = info.event.extendedProps.price || 0;
                    document.getElementById('modal-price').textContent = parseFloat(price).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2
                    });

                    document.getElementById('form-date').value = start.format('YYYY-MM-DD');
                    document.getElementById('form-start').value = start.format('HH:mm');
                    document.getElementById('form-end').value = end.format('HH:mm');
                    document.getElementById('form-price').value = price;
                    document.getElementById('form-schedule-id').value = info.event.id;

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            });

            window.calendar.render();

            const closeBtn = document.getElementById('close-modal');
            if (closeBtn) closeBtn.onclick = function() {
                document.getElementById('booking-modal').classList.replace('flex', 'hidden');
            };
        });
    </script>
</body>

</html>