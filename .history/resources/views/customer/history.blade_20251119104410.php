<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Minhas Reservas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Botão de Retorno para o Agendamento Público --}}
            <a href="{{ route('reserva.index') }}"
                class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition duration-150 mb-6 font-semibold">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Voltar para Agendamento
            </a>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 lg:p-10">

                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 border-b pb-3">
                    Histórico de Agendamentos ({{ Auth::user()->name }})
                </h3>

                <!-- Mensagens de Feedback -->
                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                @endif

                {{-- MENSAGEM DE REGRA DE NEGÓCIO (Suavizada para refletir a nova funcionalidade de solicitação) --}}
                <div class="p-4 mb-6 bg-red-100 border-l-4 border-red-500 text-red-800 rounded-lg shadow-md">
                    <p class="font-semibold text-sm">
                        ⚠️ **Regra da Arena:** Você pode cancelar **reservas pontuais** diretamente. Cancelamentos de **séries recorrentes** exigem a aprovação do Gestor, mas você pode enviar a solicitação abaixo.
                    </p>
                </div>

                @php
                    // Agrupa as reservas pela série recorrente ID, ou 'pontual' se não for recorrente.
                    // Variável alterada de $reservas para $reservations, conforme o arquivo do usuário.
                    $groupedReservas = $reservations->groupBy(function($item) {
                        return $item->is_recurrent ? $item->recurrent_series_id : 'pontual';
                    });
                @endphp

                <div id="ajax-message-container" class="mb-4"></div>

                <div class="space-y-10">

                    {{-- 🟢 RESERVAS PONTUAIS 🟢 --}}
                    @if ($groupedReservas->has('pontual'))
                        <div class="p-4 bg-green-50 rounded-xl shadow-inner border border-green-200">
                            <h4 class="text-xl font-bold text-green-700 mb-3">Reservas Pontuais</h4>
                            {{-- Passa o grupo 'pontual' como a variável $reservas para a partial --}}
                            @include('customer.partials.reservation_table', ['reservas' => $groupedReservas['pontual']])
                        </div>
                    @endif


                    {{-- 🟣 SÉRIES RECORRENTES (Agrupadas) 🟣 --}}
                    @foreach ($groupedReservas as $seriesId => $seriesReservas)
                        @if ($seriesId !== 'pontual')
                            @php
                                $maxDate = $seriesReservas->max('date');
                                $minDate = $seriesReservas->min('date');
                                $firstReserva = $seriesReservas->first();

                                // O status da série é determinado pelo status da reserva mestra ou da primeira reserva.
                                $seriesStatus = $firstReserva->status;

                                $statusClass = match ($seriesStatus) {
                                    'confirmed' => 'bg-fuchsia-100 text-fuchsia-700 border-fuchsia-200',
                                    'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'cancelled', 'rejected' => 'bg-red-100 text-red-700 border-red-200',
                                    default => 'bg-gray-100 text-gray-700 border-gray-200',
                                };
                            @endphp

                            <div class="p-4 rounded-xl shadow-inner border {{ $statusClass }}">
                                <h4 class="text-xl font-bold text-fuchsia-700 mb-3 flex justify-between items-center">
                                    Série Recorrente #{{ $seriesId }}
                                    <span class="text-sm font-normal text-gray-500">
                                        {{ \Carbon\Carbon::parse($minDate)->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }}
                                    </span>
                                </h4>

                                <p class="text-sm text-fuchsia-600 mb-3">
                                    Esta série possui {{ $seriesReservas->count() }} agendamento(s) confirmado(s) ou pendente(s).
                                </p>

                                @if ($seriesStatus === 'confirmed')
                                    <div class="mb-3">
                                        {{-- Botão que abre o modal de solicitação de cancelamento de SÉRIE --}}
                                        <button type="button"
                                                onclick="openCancellationModal({{ $firstReserva->id }}, true)"
                                                class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition duration-150 shadow-md">
                                            Solicitar Cancelamento de SÉRIE
                                        </button>
                                        <p class="text-xs text-gray-600 mt-2">
                                            A solicitação de cancelamento de série deve ser aprovada pelo Gestor.
                                        </p>
                                    </div>
                                @endif

                                <div class="overflow-x-auto max-h-96 overflow-y-auto border border-fuchsia-300 rounded-lg">
                                    {{-- Tabela de Recorrentes (Usa a partial view) --}}
                                    @include('customer.partials.reservation_table', ['reservas' => $seriesReservas])
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Caso não haja reservas --}}
                    @if ($reservations->isEmpty())
                        <div class="text-center py-10 text-gray-500 italic">
                            Você não possui reservas agendadas ou históricas.
                        </div>
                    @endif
                </div>

                <!-- Paginação -->
                <div class="mt-6">
                    {{ $reservations->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE CONFIRMAÇÃO DE CANCELAMENTO PONTUAL (Ou Solicitação de Série) --}}
    <div id="cancellation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 m-4 transform transition-transform duration-300 scale-95 opacity-0" id="cancellation-modal-content" onclick="event.stopPropagation()">
            <h3 id="modal-title-cancel" class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação de Cancelamento</h3>

            <p id="modal-message-cancel" class="text-gray-700 mb-4 font-semibold"></p>
            <p id="modal-warning-cancel" class="text-sm text-yellow-600 mb-4 hidden"></p>

            <form id="cancellation-form" onsubmit="return handleCancellationSubmit(event)">
                @csrf
                <input type="hidden" name="reserva_id" id="form-reserva-id">

                <div class="mb-6">
                    <label for="cancellation-reason-input" class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo do Cancelamento:
                    </label>
                    <textarea id="cancellation-reason-input" name="cancellation_reason" rows="3" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Obrigatório, descreva o motivo do cancelamento (mínimo 5 caracteres)..." required minlength="5"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeCancellationModal()" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                        Fechar
                    </button>
                    <button type="submit" id="confirm-cancellation-btn" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">
                        Confirmar Cancelamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- SCRIPTS DE AÇÃO AJAX --}}
    <script>
        // CRÍTICO: Buscar o token CSRF de forma segura
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const BASE_CANCEL_URL = '{{ route("customer.reservas.cancel_by_customer", [":reserva" => ""]) }}';
        let isRecurrentSeries = false;

        // =========================================================
        // LÓGICA DO MODAL DE CANCELAMENTO
        // =========================================================

        /**
         * Abre o modal de cancelamento e configura o ID e o tipo de reserva.
         * @param {number} reservaId ID da reserva.
         * @param {boolean} isSeries Se é uma solicitação de cancelamento de série.
         */
        function openCancellationModal(reservaId, isSeries) {
            isRecurrentSeries = isSeries;
            document.getElementById('form-reserva-id').value = reservaId;
            document.getElementById('cancellation-reason-input').value = '';

            const messageEl = document.getElementById('modal-message-cancel');
            const warningEl = document.getElementById('modal-warning-cancel');
            const submitBtn = document.getElementById('confirm-cancellation-btn');

            if (isSeries) {
                document.getElementById('modal-title-cancel').textContent = "Solicitação de Cancelamento de Série";
                messageEl.innerHTML = "Você está solicitando o cancelamento de **TODAS** as reservas futuras desta série recorrente. O Gestor será notificado.";
                warningEl.innerHTML = "Atenção: A série será cancelada **após a aprovação do Gestor**.";
                warningEl.classList.remove('hidden');
                submitBtn.textContent = 'Enviar Solicitação de Cancelamento';
                submitBtn.classList.replace('bg-red-600', 'bg-red-700');
            } else {
                document.getElementById('modal-title-cancel').textContent = "Confirmação de Cancelamento Pontual";
                messageEl.innerHTML = "Você está prestes a cancelar **esta reserva pontual**. O horário será liberado imediatamente.";
                warningEl.classList.add('hidden');
                submitBtn.textContent = 'Confirmar Cancelamento';
                submitBtn.classList.replace('bg-red-700', 'bg-red-600');
            }

            document.getElementById('cancellation-modal').classList.remove('hidden');
            document.getElementById('cancellation-modal').classList.add('flex');

            setTimeout(() => {
                document.getElementById('cancellation-modal-content').classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        function closeCancellationModal() {
            document.getElementById('cancellation-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                document.getElementById('cancellation-modal').classList.remove('flex');
                document.getElementById('cancellation-modal').classList.add('hidden');
            }, 300);
        }

        /**
         * Lida com o envio AJAX do formulário de Cancelamento.
         */
        async function handleCancellationSubmit(event) {
            event.preventDefault();

            const formElement = event.target;

            if (!formElement.reportValidity()) {
                return;
            }

            const reservaId = document.getElementById('form-reserva-id').value;
            // 🛑 CRÍTICO: Usa a rota de cancelamento do cliente que espera o ID da reserva
            const url = BASE_CANCEL_URL.replace(':reserva', reservaId);

            const submitBtn = document.getElementById('confirm-cancellation-btn');
            const originalText = submitBtn.textContent;

            const formData = new FormData(formElement);
            const data = Object.fromEntries(formData.entries());

            // O Laravel espera o método POST para a rota
            data['_method'] = 'POST';
            data['cancellation_reason'] = data['cancellation_reason'].trim(); // Limpa o motivo

            // Adiciona a flag de série para o Controller tratar
            data['is_series_cancellation'] = isRecurrentSeries ? '1' : '0';

            submitBtn.disabled = true;
            submitBtn.textContent = isRecurrentSeries ? 'Enviando Solicitação...' : 'Cancelando...';

            try {
                const response = await fetch(url, {
                    method: 'POST', // Usa POST como método de transporte
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
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
                    console.error("Falha ao ler JSON de resposta (Pode ser 500 ou HTML).", errorText);
                    result = { success: false, message: `Erro do Servidor (${response.status}). Verifique o console.` };
                }

                if (response.ok && result.success) {
                    // Recarrega a página para atualizar o histórico
                    alert(result.message || "Ação realizada com sucesso.");
                    window.location.reload();

                } else if (response.status === 422 && result.errors) {
                    const errors = Object.values(result.errors).flat().join('\n');
                    alert(`ERRO DE VALIDAÇÃO:\n${errors}`);
                } else {
                    alert(result.message || `Erro desconhecido. Status: ${response.status}.`);
                }

            } catch (error) {
                console.error('Erro de Rede/Comunicação:', error);
                alert("Erro de conexão. Tente novamente.");
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }

            return false;
        }

        /**
         * Exibe uma mensagem de alerta temporária no topo da página.
         */
        function displayAjaxMessage(message, type = 'success') {
            const container = document.getElementById('ajax-message-container');
            let bgColor = 'bg-green-100';
            let textColor = 'text-green-700';

            if (type === 'error') {
                bgColor = 'bg-red-100';
                textColor = 'text-red-700';
            }

            container.innerHTML = `
                <div class="p-4 mb-4 text-sm ${textColor} ${bgColor} rounded-lg shadow-md" role="alert">
                    ${message}
                </div>
            `;
            setTimeout(() => {
                container.innerHTML = '';
            }, 5000);
        }
    </script>
</x-app-layout>
