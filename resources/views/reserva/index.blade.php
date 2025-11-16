<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }} | Agendamento Online</title>

    {{-- Tailwind CSS & JS (assumindo que o vite as carrega) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- FullCalendar Imports --}}
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/main.min.css' rel='stylesheet' />

    <style>
        .arena-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #10b981 100%);
        }
        .calendar-container {
            margin: 0 auto;
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

        /* Estilo para Eventos Disponíveis (Verde) */
        .fc-event-available {
            background-color: #10B981 !important; /* Verde 500 */
            border-color: #059669 !important;
            color: white !important;
            cursor: pointer;
            padding: 2px 5px;
            border-radius: 4px;
            opacity: 0.9;
            transition: opacity 0.2s;
            font-size: 0.75rem;
            line-height: 1.2;
        }

        /* Estilos para os marcadores de dia (substitutos dos slots) */
        .day-marker {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            padding: 4px;
            border-radius: 6px;
            margin-top: 2px;
            text-align: center;
            line-height: 1.2;
            cursor: pointer;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.1);
        }

        .marker-available {
            background-color: #10B981; /* Verde 500 */
            color: white;
            box-shadow: 0 1px 3px 0 rgba(16, 185, 129, 0.4);
        }

        .marker-none {
            background-color: #FEE2E2; /* Red 100 */
            color: #991B1B; /* Red 800 */
            border: 1px solid #FCA5A5; /* Red 300 */
        }

        /* Garante que o day-frame seja clicável */
        .fc-daygrid-day-frame {
            cursor: pointer;
        }
    </style>
</head>

<body class="font-sans antialiased arena-bg">

{{-- 🛑 BARRA DE NAVEGAÇÃO SUPERIOR PARA CLIENTES LOGADOS (Incluindo Sair) --}}
@auth
    @if (Auth::user()->isClient())
        <nav class="bg-indigo-700/80 backdrop-blur-sm shadow-lg p-3">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <span class="text-white text-sm font-semibold">
                    Logado como: {{ Auth::user()->name }}
                </span>
                <div class="flex space-x-4">
                    {{-- Botão Minhas Reservas --}}
                    <a href="{{ route('customer.reservations.history') }}"
                       class="px-3 py-1 bg-white text-indigo-700 font-bold rounded-full shadow-md hover:bg-gray-100 transition">
                        Minhas Reservas
                    </a>
                    {{-- Botão Sair (Logout) --}}
                    <form method="POST" action="{{ route('customer.logout') }}" class="inline">
                         @csrf
                        <button type="submit" class="px-3 py-1 bg-red-500 text-white font-bold rounded-full shadow-md hover:bg-red-600 transition text-sm">
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </nav>
    @else
        {{-- Se for Gestor/Admin, pode ter um link para o Dashboard e um botão de Sair --}}
        <nav class="bg-purple-700/80 backdrop-blur-sm shadow-lg p-3">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <span class="text-white text-sm font-semibold">
                    Logado como: {{ Auth::user()->name }} (Gestor)
                </span>
                <div class="flex space-x-4">
                     <a href="{{ route('dashboard') }}"
                       class="px-3 py-1 bg-white text-purple-700 font-bold rounded-full shadow-md hover:bg-gray-100 transition">
                        Dashboard Admin
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                         @csrf
                        <button type="submit" class="px-3 py-1 bg-red-500 text-white font-bold rounded-full shadow-md hover:bg-red-600 transition text-sm">
                            Sair (Admin)
                        </button>
                    </form>
                </div>
            </div>
        </nav>
    @endif
@else
    {{-- Se NÃO estiver logado, mostra o botão de Fazer Login --}}
    <nav class="bg-gray-800/80 backdrop-blur-sm shadow-lg p-3">
        <div class="max-w-7xl mx-auto flex justify-end items-center">
            <a href="{{ route('customer.login') }}"
               class="px-3 py-1 bg-indigo-600 text-white font-bold rounded-full shadow-md hover:bg-indigo-700 transition text-sm">
                Fazer Login / Cadastrar
            </a>
        </div>
    </nav>
@endauth
{{-- FIM DA BARRA DE NAVEGAÇÃO --}}

<!-- Container Centralizado (O Card Principal Transparente) -->
<div class="min-h-screen flex items-center justify-center p-4 md:p-8">
    <div class="w-full max-w-7xl
        p-6 sm:p-10 lg:p-12
        bg-white/95 dark:bg-gray-800/90
        backdrop-blur-md shadow-2xl shadow-gray-900/70 dark:shadow-indigo-900/50
        rounded-3xl transform transition-all duration-300 ease-in-out">

        <h1 class="text-5xl font-extrabold text-gray-900 dark:text-gray-100 mb-8
            border-b-4 border-indigo-600 dark:border-indigo-400 pb-4 text-center
            tracking-tighter transform hover:scale-[1.005] transition duration-300">
            ⚽ ELITE SOCCER - Agendamento Online
        </h1>

        <p class="text-gray-600 dark:text-gray-400 mb-10 text-center text-xl font-medium">
            Selecione uma data no calendário abaixo e **clique nela** para ver os horários detalhados.
        </p>

        {{-- --- Mensagens de Status --- --}}
        @if (session('success'))
            <div class="bg-green-100 dark:bg-green-900/50 border-l-4 border-green-600 text-green-800 dark:text-green-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg" role="alert">
                <span class="font-bold text-lg">SUCESSO!</span> <span class="ml-2">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('whatsapp_link'))
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-400 dark:border-green-700 p-8 rounded-3xl relative mb-12 text-center shadow-2xl shadow-green-400/40 dark:shadow-green-900/70" role="alert">
                <p class="font-extrabold mb-3 text-4xl text-green-700 dark:text-green-300">✅ RESERVA PRÉ-APROVADA!</p>
                <p class="mb-6 text-lg text-gray-700 dark:text-gray-300">
                    Sua vaga foi reservada por 30 minutos. **Clique abaixo imediatamente** para confirmar o pagamento do sinal via WhatsApp.
                </p>
                <a href="{{ session('whatsapp_link') }}" target="_blank"
                    class="mt-2 inline-flex items-center p-4 px-12 py-5 bg-green-600 text-white font-extrabold rounded-full shadow-2xl shadow-green-600/50 hover:bg-green-700 transition duration-300 transform hover:scale-105 active:scale-[0.97] uppercase tracking-wider text-xl">
                    ENVIAR COMPROVANTE VIA WHATSAPP
                </a>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 italic">O horário será liberado se o comprovante não for enviado.</p>
            </div>
        @endif

        {{-- Alerta Geral de Erro de Submissão (incluindo erro de conflito) --}}
        @if (session('error'))
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-6 flex items-center shadow-lg" role="alert">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                <span class="font-bold text-lg">ERRO:</span> <span class="ml-2">{{ session('error') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 dark:bg-red-900/50 border-l-4 border-red-600 text-red-800 dark:text-red-300 p-4 rounded-xl relative mb-8 shadow-lg" role="alert">
                <p class="font-bold flex items-center text-lg"><svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg> Correção Necessária!</p>
                <p class="mt-1">Houve um problema com a sua seleção ou dados. Por favor, verifique os campos destacados no formulário abaixo.</p>
            </div>
        @endif

        {{-- Calendário FullCalendar --}}
        <div class="calendar-container shadow-2xl">
            <div id='calendar'></div>
        </div>

    </div>
</div>

{{-- --- Modal de Confirmação de Dados --- --}}
<div id="booking-modal" class="fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div id="modal-content" class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-100 border-t-8
        @if ($errors->any() && old('data_reserva')) border-red-600 dark:border-red-500 @else border-indigo-600 dark:border-indigo-500 @endif">

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

        {{-- Alerta para Erros de Validação Front-End --}}
        <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 rounded-xl relative shadow-md hidden" role="alert" id="frontend-alert-box">
            <p id="frontend-alert-message" class="font-bold flex items-center text-lg">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                <span class="text-base">Atenção</span>: <span class="ml-1 text-sm font-normal"></span>
            </p>
        </div>

        {{-- CRÍTICO: ÁREA DE AUTENTICAÇÃO (Visível apenas se o usuário NÃO estiver logado) --}}
        @guest
        <div id="auth-prompt" class="p-8 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl border border-indigo-300 dark:border-indigo-700 shadow-xl mb-8 text-center">
            <h4 class="text-2xl font-extrabold text-indigo-700 dark:text-indigo-300 mb-4">
                Pré-Requisito: Login
            </h4>
            <p class="text-gray-700 dark:text-gray-300 mb-6">
                Para prosseguir com a pré-reserva, você deve estar logado em sua conta de cliente.
            </p>

            <div class="flex flex-col sm:flex-row justify-center gap-4">
                {{-- ✅ CORRIGIDO: Rota customer.login --}}
                <a href="{{ route('customer.login') }}" class="w-full sm:w-auto p-3 bg-indigo-600 text-white font-bold rounded-xl shadow-lg hover:bg-indigo-700 transition transform hover:scale-[1.03]">
                    Fazer Login
                </a>
                {{-- ✅ CORRIGIDO: Rota customer.register --}}
                <a href="{{ route('customer.register') }}" class="w-full sm:w-auto p-3 bg-green-600 text-white font-bold rounded-xl shadow-lg hover:bg-green-700 transition transform hover:scale-[1.03]">
                    Criar Conta
                </a>
            </div>
        </div>
        @endguest
        {{-- FIM DA ÁREA DE AUTENTICAÇÃO --}}

        {{-- CRÍTICO: O restante do conteúdo (Detalhes e Formulário) só aparece se estiver autenticado --}}
        @auth

            {{-- Estrutura If/Else Reforçada --}}
            @if (Auth::user()->isGestor())
                <div class="p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded mb-4" role="alert">
                    <p class="font-bold">Acesso Negado</p>
                    <p>Contas de Gestor/Admin não podem fazer reservas pelo painel público. Por favor, deslogue ou use o agendamento rápido no Dashboard.</p>
                    {{-- Rota customer.logout MANTIDA --}}
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                         @csrf
                        <button type="submit" class="text-red-500 underline hover:text-red-700 text-sm">Deslogar</button>
                    </form>
                </div>
            @else
                {{-- O formulário de reserva aparece SOMENTE se NÃO for Gestor --}}

                <div class="mb-8 p-6 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 rounded-xl shadow-md dark:border-red-400 dark:text-red-200">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 mr-3 text-red-600 flex-shrink-0 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="font-black text-lg uppercase tracking-wider">Atenção!</p>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed font-semibold">
                        Sua vaga é garantida **apenas** após o **envio imediato do comprovante do sinal** via WhatsApp.
                    </p>
                </div>

                <h4 class="text-3xl font-extrabold mb-6 text-gray-900 dark:text-gray-100 border-b pb-3">Confirme Sua Reserva</h4>

                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 rounded-xl border border-green-300 dark:border-green-700">
                    <p class="text-sm font-semibold text-green-700 dark:text-green-300">
                        Logado como: <span class="font-extrabold">{{ Auth::user()->name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">({{ Auth::user()->whatsapp_contact }})</span>
                    </p>
                    {{-- Rota customer.logout MANTIDA --}}
                    <form method="POST" action="{{ route('customer.logout') }}" class="mt-1">
                         @csrf
                        <button type="submit" class="text-xs text-indigo-600 underline hover:text-indigo-800">Sair da conta de Cliente</button>
                    </form>
                </div>

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
                        <span class="font-extrabold text-4xl text-green-700 dark:text-green-400">Total:</span>
                        <span class="font-extrabold text-4xl text-green-700 dark:text-green-400">R$ <span id="modal-price"></span></span>
                    </div>
                </div>

                <form id="booking-form" method="POST" action="{{ route('reserva.store') }}">
                    @csrf

                    {{-- Campos Hidden --}}
                    <input type="hidden" name="data_reserva" id="form-date" value="{{ old('data_reserva') }}">
                    <input type="hidden" name="hora_inicio" id="form-start" value="{{ old('hora_inicio') }}">
                    <input type="hidden" name="hora_fim" id="form-end" value="{{ old('hora_fim') }}">
                    <input type="hidden" name="price" id="form-price" value="{{ old('price') }}">
                    <input type="hidden" name="reserva_conflito_id" value="" />
                    <input type="hidden" name="schedule_id" id="form-schedule-id" value="{{ old('schedule_id') }}">

                    {{-- 🛑 CLIENTE ID, NOME E CONTATO INJETADOS (AUTOMÁTICO) --}}
                    <input type="hidden" name="user_id" id="client_user_id" value="{{ Auth::user()->id }}">
                    <input type="hidden" name="nome_cliente" id="client_name" value="{{ Auth::user()->name }}">
                    <input type="hidden" name="contato_cliente" id="client_contact" value="{{ Auth::user()->whatsapp_contact }}">

                    <div class="mb-8">
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Observações (Opcional):
                        </label>
                        <textarea name="notes" id="notes" rows="3"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-xl shadow-md focus:border-indigo-500 focus:ring-indigo-500 @error('notes') border-red-500 ring-1 ring-red-500 @enderror"
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
        @endauth
        {{-- FIM DO @auth --}}

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

    // 🛑 CORREÇÃO CRÍTICA AQUI: Uso de optional() para evitar erro de método em objeto null quando deslogado.
    const IS_AUTHENTICATED_AS_CLIENT = @json(Auth::check() && optional(Auth::user())->isClient());
    const IS_AUTHENTICATED_AS_GESTOR = @json(Auth::check() && optional(Auth::user())->isGestor());

    /**
     * Formata a data para o padrão Brasileiro (Dia da semana, dia de Mês de Ano).
     */
    function formatarDataBrasileira(dateString) {
        // FullCalendar usa o formato 'YYYY-MM-DD'
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
        // Este alerta é usado apenas em erros internos do FullCalendar ou JS.
        const alertBox = document.getElementById('frontend-alert-box');
        const alertMessage = document.getElementById('frontend-alert-message').querySelector('span.ml-1');

        alertMessage.textContent = message;
        alertBox.classList.remove('hidden');

        setTimeout(() => {
            alertBox.classList.add('hidden');
        }, 5000); // 5 segundos

        console.error(message);
    }


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


        // --- FUNÇÃO CRÍTICA: LÓGICA DE MARCADORES RESUMO ---
        function updateDayMarkers(calendar) {
            if (calendar.view.type !== 'dayGridMonth') return;

            const dayCells = calendarEl.querySelectorAll('.fc-daygrid-day-frame');

            dayCells.forEach(dayEl => {
                const dateEl = dayEl.closest('.fc-daygrid-day');
                const dateStr = dateEl ? dateEl.getAttribute('data-date') : null;
                if (!dateStr) return;

                // Remove marcadores antigos antes de adicionar novos
                const existingMarker = dayEl.querySelector('.day-marker');
                if (existingMarker) existingMarker.remove();

                const eventsOnDay = calendar.getEvents().filter(event =>
                    moment(event.start).format('YYYY-MM-DD') === dateStr
                );

                const availableSlots = eventsOnDay.filter(event =>
                    event.classNames.includes('fc-event-available')
                ).length;

                const bookedEvents = eventsOnDay.filter(event =>
                    !event.classNames.includes('fc-event-available')
                ).length;

                const markerContainer = dayEl.querySelector('.fc-daygrid-day-bottom');
                if (!markerContainer) return;

                let markerHtml = '';

                if (availableSlots > 0) {
                    markerHtml = `
                        <div class="day-marker marker-available">
                            Confira ${availableSlots} horário(s) disponível(eis)
                        </div>`;
                } else {
                    let message = (bookedEvents > 0)
                        ? "Dia Ocupado/Fechado"
                        : "Nenhum horário disponível";

                    // Verifica se a data é anterior ao dia atual (desativa o marcador 'none' para o passado)
                    const today = moment().startOf('day');
                    const dayMoment = moment(dateStr);
                    if (dayMoment.isBefore(today, 'day')) {
                        // Não mostra marcador de indisponível para dias passados.
                        markerHtml = '';
                    } else {
                        markerHtml = `
                            <div class="day-marker marker-none">
                                ${message}
                            </div>`;
                    }
                }

                if (markerHtml) {
                    markerContainer.insertAdjacentHTML('beforeend', markerHtml);
                }
            });
        }


        // === Inicialização do FullCalendar ===
        let calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'pt-br',
            initialView: 'dayGridMonth',
            height: 'auto',
            timeZone: 'local',

            dayMaxEvents: true,

            eventSources: [
                // 1. Reservas Reais (Ocupados)
                {
                    url: RESERVED_API_URL,
                    method: 'GET',
                    failure: function() {
                        console.error('Falha ao carregar reservas reais.');
                    },
                },
                // 2. Slots Disponíveis (Grade Fixa)
                {
                    url: AVAILABLE_API_URL,
                    method: 'GET',
                    failure: function() {
                        console.error('Falha na API de Horários Disponíveis.');
                    },
                    className: 'fc-event-available',
                    display: 'block'
                }
            ],

            views: {
                dayGridMonth: {
                    buttonText: 'Mês',
                },
                timeGridDay: {
                    buttonText: 'Dia',
                    slotMinTime: '06:00:00',
                    slotMaxTime: '23:00:00'
                }
            },
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridDay'
            },
            editable: false,
            initialDate: new Date().toISOString().slice(0, 10),

            eventsSet: function(info) {
                // Chama o marcador após o carregamento dos eventos
                updateDayMarkers(calendar);
            },

            eventDidMount: function(info) {
                // 🛑 CORREÇÃO CRÍTICA: Esconde TODOS os eventos na visão de Mês (dayGridMonth)
                // O resumo é feito pelos 'day-marker' injetados na eventsSet.
                if (info.view.type === 'dayGridMonth') {
                    info.el.style.display = 'none';
                    return;
                }

                // Lógica de timeGridDay (mantida)
                if (info.view.type === 'timeGridDay' && !info.event.classNames.includes('fc-event-available')) {
                     // Esconde eventos OCUPADOS na visão de Dia (para manter a tela limpa para o cliente)
                     info.el.classList.add('hidden');
                } else if (info.view.type === 'timeGridDay' && info.event.classNames.includes('fc-event-available')) {
                     // Garante que os slots disponíveis estejam visíveis na visão de Dia
                     info.el.classList.remove('hidden');
                }
            },

            dateClick: function(info) {
                // Bloqueia clique em dias passados
                const clickedDate = moment(info.dateStr);
                const today = moment().startOf('day');

                if (clickedDate.isBefore(today, 'day')) {
                    // Não faz nada se a data for anterior a hoje
                    return;
                }

                // Muda para a visão de Dia apenas para datas futuras ou de hoje
                calendar.changeView('timeGridDay', info.dateStr);
            },

            eventClick: function(info) {
                const event = info.event;
                // 🛑 CRÍTICO: Somente processa o agendamento se for um slot disponível
                const isAvailable = event.classNames.includes('fc-event-available');

                // --- 🛑 LÓGICA DE SLOT DISPONÍVEL ---
                if (isAvailable) {

                    // 1. FORÇA O LOGIN OU MOSTRA ALERTA SE O CLIENTE NÃO ESTIVER LOGADO (ou for Gestor)
                    if (!IS_AUTHENTICATED_AS_CLIENT) {
                        // Abre o modal
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');

                        // Exibe apenas o prompt de autenticação
                        document.getElementById('auth-prompt')?.classList.remove('hidden');

                        // Garante que o formulário de reserva fique escondido
                        const formContainer = document.querySelector('#booking-form');
                        if(formContainer) formContainer.style.display = 'none';

                        // Esconde todos os outros elementos do modal que não são o prompt de auth
                        document.querySelectorAll('#modal-content > *:not(#auth-prompt):not(.mb-6, #modal-content > h4)').forEach(el => {
                            if (el.id !== 'auth-prompt') {
                                el.style.display = 'none';
                            }
                        });


                        return;
                    }

                    // Se o usuário está logado como cliente, processa a reserva

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

                    // 2.3 Exibir a área de formulário
                    // Re-exibe todos os elementos do modal (exceto o auth-prompt)
                    document.querySelectorAll('#modal-content > *:not(#auth-prompt)').forEach(el => {
                         el.style.display = 'block';
                    });
                    document.getElementById('auth-prompt')?.classList.add('hidden'); // Garante que o prompt de auth está escondido

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');

                } else {
                    // Clicou em um evento Ocupado/Fechado - Ação de ignorar
                    console.log('Horário ocupado/fechado. Nenhuma ação de reserva.');
                    // Aqui, adicionamos um alerta visual para o usuário
                    showFrontendAlert("❌ Este horário está ocupado ou é uma pré-reserva. Por favor, clique em um slot verde (disponível).");
                }
            }
        });

        calendar.render();

        // === Lógica de Reabertura do Modal em caso de Erro de Validação ===
        if (oldDate && oldStart) {

            // Se o erro ocorreu, precisamos reabrir o modal para que o usuário veja os erros de validação
            // Esta lógica só é executada se houver dados 'old', o que implica que o usuário já estava autenticado.

            const formattedOldPrice = parseFloat(oldPrice).toFixed(2).replace('.', ',');

            document.getElementById('modal-date').textContent = formatarDataBrasileira(oldDate);
            document.getElementById('modal-time').textContent = `${oldStart} - ${oldEnd}`;
            document.getElementById('modal-price').textContent = formattedOldPrice;
            document.getElementById('form-schedule-id').value = oldScheduleId;

            // Em caso de erro, reabre no modo Dia na data correta para visualização
            calendar.changeView('timeGridDay', oldDate);

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Garante que a área de auth esteja escondida
            document.getElementById('auth-prompt')?.classList.add('hidden');

            // Garante que todos os elementos do formulário de reserva estejam visíveis para mostrar os erros
            document.querySelectorAll('#modal-content > *:not(#auth-prompt)').forEach(el => {
                el.style.display = 'block';
            });
        }

        // Listener para fechar o modal
        closeModalButton.addEventListener('click', () => {
             modal.classList.add('hidden');
        });
    });
</script>

</body>
</html>
