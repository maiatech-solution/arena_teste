<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }} | Agendamento Online</title>

    {{-- Tailwind CSS & JS (assumindo que o vite as carrega) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- FullCalendar Imports --}}
    <link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.11/main.min.css' rel='stylesheet' />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

        * { font-family: 'Inter', sans-serif; }

        /* Fundo Gradiente para a "Arena" */
        .arena-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #10b981 100%);
        }

        /* Container do Calendário */
        .calendar-container {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Estilos do FullCalendar */
        .fc {
            color: #333;
        }
        .fc-toolbar-title {
            font-size: 1.5rem !important;
        }

        /* Estilos do Modal (Ajustado para Tailwind) */
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
            z-index: 50; /* Z-index alto para sobrepor tudo */
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
            border-radius: 6px;
            opacity: 0.95;
            transition: opacity 0.2s;
            font-size: 0.8rem;
            line-height: 1.3;
            font-weight: 600;
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
            cursor: default;
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

        {{-- --- Mensagens de Status (Mantidas) --- --}}
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
<div id="booking-modal" class="modal-overlay hidden items-center justify-center z-50 p-4">
    <div id="modal-content" class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-100 border-t-8
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

            <h4 class="text-3xl font-extrabold mb-6 text-gray-900 dark:text-gray-100 border-b pb-3">Confirme Sua Pré-Reserva</h4>

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
                {{-- 🛑 LÓGICA CONDICIONAL: DADOS DO CLIENTE (MANTIDA) 🛑 --}}
                {{-- ========================================================= --}}
                
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
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100 rounded-xl shadow-md p-3 @error('contato_cliente') border-red-500 ring-1 ring-red-500 @enderror" minlength="10" maxlength="11">
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
                    </div>
                

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
                        <span class="font-extrabold text-4xl text-green-700 dark:text-green-400">Total:</span>
                        <span class="font-extrabold text-4xl text-green-700 dark:text-green-400">R$ <span id="modal-price"></span></span>
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
    const IS_AUTHENTICATED = @json(Auth::check());
    const IS_AUTHENTICATED_AS_CLIENT = @json(Auth::check() && optional(Auth::user())->isClient());
    const IS_AUTHENTICATED_AS_GESTOR = @json(Auth::check() && optional(Auth::user())->isGestor());

    let calendar; // Variável global para o calendário

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

        // Garante que o modal esteja visível se o alerta for acionado por clique no calendário
        const modal = document.getElementById('booking-modal');
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        setTimeout(() => {
            alertBox.classList.add('hidden');
        }, 5000); // 5 segundos

        console.error(message);
    }

    /**
     * 🛑 NOVO: Limpa a string de telefone, removendo tudo exceto dígitos (0-9).
     * @param {string} value
     * @returns {string} A string contendo apenas dígitos.
     */
    function cleanPhoneNumber(value) {
        return value.replace(/\D/g, '');
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

        // 🛑 NOVO: Adiciona o listener para limpar a entrada de telefone no formulário
        const guestContactInput = document.getElementById('guest-contact');
        if (guestContactInput) {
            guestContactInput.addEventListener('input', function() {
                // Aplica a limpeza a cada input (ex: (91) 99999-8888 -> 91999998888)
                this.value = cleanPhoneNumber(this.value);
            });
        }


        // --- FUNÇÃO CRÍTICA: LÓGICA DE MARCADORES RESUMO ---
        function updateDayMarkers(calendar) {
            // Só executa na visão de mês
            if (calendar.view.type !== 'dayGridMonth') return;

            const dayCells = calendarEl.querySelectorAll('.fc-daygrid-day-frame');

            dayCells.forEach(dayEl => {
                const dateEl = dayEl.closest('.fc-daygrid-day');
                const dateStr = dateEl ? dateEl.getAttribute('data-date') : null;
                if (!dateStr) return;

                // 1. Limpa marcadores antigos
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

                // 2. Cria o novo marcador
                if (availableSlots > 0) {
                    markerHtml = `
                        <div class="day-marker marker-available">
                            Confira ${availableSlots} horário(s) disponível(eis)
                        </div>`;
                } else {
                    let message = (bookedEvents > 0)
                        ? "Dia Ocupado/Fechado"
                        : "Nenhum horário disponível";

                    // Verifica se a data é anterior ao dia atual (não mostra marcador 'none' para o passado)
                    const today = moment().startOf('day');
                    const dayMoment = moment(dateStr);
                    if (dayMoment.isBefore(today, 'day')) {
                        markerHtml = '';
                    } else {
                        markerHtml = `
                            <div class="day-marker marker-none">
                                ${message}
                            </div>`;
                    }
                }

                // 3. Adiciona ao DOM
                if (markerHtml) {
                    // Adiciona o marcador ao contêiner de dia (abaixo do número do dia)
                    markerContainer.insertAdjacentHTML('beforeend', markerHtml);
                }
            });
        }


        // === Inicialização do FullCalendar ===
        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'pt-br',
            initialView: 'dayGridMonth',
            height: 'auto',
            timeZone: 'local',

            dayMaxEvents: true,

            eventSources: [
                // 1. Reservas Reais (Ocupados - Sem className 'available')
                {
                    url: RESERVED_API_URL,
                    method: 'GET',
                    failure: function() {
                        console.error('Falha ao carregar reservas reais.');
                    },
                    // Usar display: 'none' para que não polua a view de mês/dia, mas participe do filtro
                    display: 'none'
                },
                // 2. Slots Disponíveis (Grade Fixa - Com className 'available')
                {
                    // CRÍTICO: ID para recarga no setInterval
                    id: 'available-slots-source-id',
                    className: 'fc-event-available',
                    display: 'block', // Garante que estejam visíveis na timeGridDay
                    // USANDO A FUNÇÃO EVENTS PARA FILTRAR OS HORÁRIOS JÁ PASSADOS
                    events: function(fetchInfo, successCallback, failureCallback) {
                        const now = moment();
                        // Formato YYYY-MM-DD para comparação de data
                        const todayDate = now.format('YYYY-MM-DD');

                        // Adiciona os limites de data/hora (start/end) no fetchInfo para a API
                        const urlWithParams = AVAILABLE_API_URL +
                            '?start=' + encodeURIComponent(fetchInfo.startStr) +
                            '&end=' + encodeURIComponent(fetchInfo.endStr);

                        fetch(urlWithParams)
                            .then(response => {
                                if (!response.ok) throw new Error('Falha ao buscar slots disponíveis.');
                                return response.json();
                            })
                            .then(availableEvents => {
                                // Lógica de Filtro: Remove eventos disponíveis que JÁ PASSARAM (em tempo) se for hoje
                                const filteredEvents = availableEvents.filter(event => {
                                    const eventDate = moment(event.start).format('YYYY-MM-DD');

                                    // 1. Se não for hoje, sempre exibe.
                                    if (eventDate !== todayDate) {
                                        return true;
                                    }

                                    // 2. Se for hoje, verifica a hora final do slot.
                                    const eventEnd = moment(event.end);

                                    // Retorna TRUE se o horário de término do evento for AGORA ou FUTURO.
                                    return eventEnd.isSameOrAfter(now);
                                });

                                // 3. Retorna a lista filtrada para o FullCalendar
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

            // Bloqueia dias anteriores ao atual para cliques
            validRange: function(now) {
                return {
                    start: now.toISOString().split('T')[0]
                };
            },

            eventsSet: function(info) {
                // Chama o marcador após o carregamento dos eventos
                updateDayMarkers(calendar);
            },

            eventDidMount: function(info) {
                // 🛑 LÓGICA DE VISIBILIDADE CRÍTICA 🛑
                if (info.view.type === 'dayGridMonth') {
                    // Esconde TODOS os eventos na visão de Mês (dayGridMonth) para priorizar o marcador resumo
                    info.el.style.display = 'none';
                }
            },

            dateClick: function(info) {
                // dateClick é acionado ao clicar em um dia *vazio* no mês.
                const clickedDate = moment(info.dateStr);
                const today = moment().startOf('day');

                if (clickedDate.isBefore(today, 'day')) {
                    return; // Ignora cliques em dias passados
                }

                // Muda para a visão de Dia
                calendar.changeView('timeGridDay', info.dateStr);
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

                    // Validação: garante que o evento não está no passado (segurança extra, mas o filtro já faz isso)
                    if (endDate.isBefore(moment())) {
                        showFrontendAlert("❌ Este horário acabou de ser expirado. Por favor, recarregue o calendário e tente um slot futuro.");
                        // Força a recarga dos slots
                        calendar.getEventSourceById('available-slots-source-id')?.refetch();
                        return;
                    }

                    if (!event.id || !startDate.isValid() || !endDate.isValid() || extendedProps.price === undefined) {
                        showFrontendAlert("❌ Não foi possível carregar os detalhes do horário. Tente novamente.");
                        return;
                    }

                    const dateString = startDate.format('YYYY-MM-DD');
                    // Garante o formato H:mm (ex: 6:00, não 06:00) para o controller
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

                    // 2.3 Exibir o modal
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');

                } else {
                    // Clicou em um evento Ocupado/Fechado - Ação de ignorar
                    showFrontendAlert("❌ Este horário está ocupado ou é uma pré-reserva. Por favor, clique em um slot verde (disponível).");
                }
            }
        });

        calendar.render();

        // Torna o calendário globalmente acessível para funções externas
        window.calendar = calendar;

        // CRÍTICO: Recarrega os eventos a cada 60 segundos
        // Isso garante que os slots "disponíveis" no dia atual sejam corretamente filtrados.
        setInterval(() => {
            console.log("Forçando recarga de eventos disponíveis para atualizar slots passados...");
            // O getEventSourceById só funciona se o ID foi definido (id: 'available-slots-source-id')
            calendar.getEventSourceById('available-slots-source-id')?.refetch();
        }, 60000); // 60 segundos

        // === Lógica de Reabertura do Modal em caso de Erro de Validação ===
        if (oldDate && oldStart) {
            // Se o erro ocorreu, reabre o modal com os dados 'old'

            const formattedOldPrice = parseFloat(oldPrice).toFixed(2).replace('.', ',');

            document.getElementById('modal-date').textContent = formatarDataBrasileira(oldDate);
            document.getElementById('modal-time').textContent = `${oldStart} - ${oldEnd}`;
            document.getElementById('modal-price').textContent = formattedOldPrice;
            document.getElementById('form-schedule-id').value = oldScheduleId;

            // Em caso de erro, reabre no modo Dia na data correta para visualização
            calendar.changeView('timeGridDay', oldDate);

            // Abre o modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        // Listener para fechar o modal
        closeModalButton.addEventListener('click', () => {
            modal.classList.add('hidden');
        });

        // Listener para fechar o modal ao clicar no overlay
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });
</script>

</body>
</html>
