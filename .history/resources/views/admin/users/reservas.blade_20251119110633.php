<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Informações do Cliente -->
                <div class="bg-gray-100 p-4 rounded-lg shadow mb-6 border-l-4 border-indigo-500">
                    <p class="text-lg font-bold text-gray-900">Cliente: {{ $client->name }}</p>
                    <p class="text-sm text-gray-700">Email: {{ $client->email }}</p>
                    <p class="text-sm text-gray-700">Contato: {{ $client->whatsapp_contact ?? 'N/A' }}</p>
                </div>

                <!-- Botão de Volta para a Lista de Usuários -->
                <div class="mb-6 flex justify-between items-center">
                    <a href="{{ route('admin.users.index', ['role_filter' => 'cliente']) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar à Lista de Usuários
                    </a>
                </div>

                @php
                    // Agrupa as reservas pela série recorrente ID, ou 'pontual' se não for recorrente.
                    // Isso é CRÍTICO para separar as reservas na interface
                    $groupedReservas = $reservas->groupBy(function($item) {
                        return $item->is_recurrent ? $item->recurrent_series_id : 'pontual';
                    });
                @endphp

                <!-- Container para Mensagens AJAX -->
                <div id="ajax-message-container" class="mb-4"></div>

                <!-- Lista de Reservas Agrupadas -->
                <div class="space-y-10">

                    {{-- 🟢 RESERVAS PONTUAIS 🟢 --}}
                    @if ($groupedReservas->has('pontual'))
                        <div class="p-6 bg-green-50 rounded-xl shadow-lg border border-green-200" id="series-container-pontual">
                            <h3 class="text-xl font-bold text-green-700 mb-4 border-b border-green-300 pb-2">
                                Reservas Pontuais
                                <span class="text-sm font-normal text-gray-500">({{ $groupedReservas['pontual']->count() }} Total)</span>
                            </h3>

                            {{-- Tabela de Pontuais (Usa a partial view) --}}
                            @include('admin.users.partials.reservation_table', ['reservas' => $groupedReservas['pontual']])
                        </div>
                    @endif


                    {{-- 🟣 SÉRIES RECORRENTES (Agrupadas) 🟣 --}}
                    @foreach ($groupedReservas as $seriesId => $seriesReservas)
                        @if ($seriesId !== 'pontual')
                            @php
                                // Conta quantos slots futuros ainda existem nesta série
                                $futureReservasCount = $seriesReservas->filter(fn($r) => \Carbon\Carbon::parse($r->date)->isFuture() || \Carbon\Carbon::parse($r->date)->isToday())->count();
                                $maxDate = $seriesReservas->max('date');
                            @endphp

                            <div class="p-6 bg-fuchsia-50 rounded-xl shadow-lg border border-fuchsia-200" id="series-container-{{ $seriesId }}">
                                <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-fuchsia-300 pb-3 mb-4">
                                    <h3 class="text-xl font-bold text-fuchsia-700">
                                        Série Recorrente #{{ $seriesId }}
                                        <span class="text-sm font-normal text-gray-500">({{ $seriesReservas->count() }} slots | Expira em: {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }})</span>
                                    </h3>

                                    {{-- Botão de Exclusão da Série --}}
                                    @if ($futureReservasCount > 0)
                                        <button type="button"
                                                onclick="openSeriesCancellationModal({{ $seriesId }}, '{{ $client->name }}', {{ $futureReservasCount }})"
                                                class="mt-3 md:mt-0 px-4 py-2 bg-red-700 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-red-800 transition duration-150">
                                            Cancelar TODA a Série ({{ $futureReservasCount }} futuros)
                                        </button>
                                    @else
                                        <span class="mt-3 md:mt-0 text-sm text-gray-500 italic">Série concluída ou cancelada.</span>
                                    @endif
                                </div>

                                {{-- Tabela de Recorrentes (Usa a partial view) --}}
                                <div class="overflow-x-auto max-h-96 overflow-y-auto">
                                    @include('admin.users.partials.reservation_table', ['reservas' => $seriesReservas])
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Caso não haja reservas --}}
                    @if ($reservas->isEmpty())
                        <div class="text-center py-10 text-gray-500 italic">
                            Este cliente não possui reservas agendadas ou históricas.
                        </div>
                    @endif
                </div>

                <!-- Paginação -->
                @if (!$reservas->isEmpty())
                    <div class="mt-6">
                        {{ $reservas->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- MODAL DE CONFIRMAÇÃO DE CANCELAMENTO DE SÉRIE --}}
    <div id="series-cancellation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6 m-4 transform transition-transform duration-300 scale-95 opacity-0" id="series-cancellation-modal-content" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmar Cancelamento de Série Recorrente</h3>

            <p id="series-cancellation-message" class="text-gray-700 mb-4 font-semibold">
                Você está prestes a cancelar **TODAS** as <span id="slots-count-placeholder" class="font-extrabold text-red-700"></span> reservas futuras da série **#<span id="series-id-placeholder"></span>** do cliente **<span id="client-name-placeholder"></span>**.
            </p>
            <p class="text-sm text-red-600 mb-4">
                Esta ação marcará os slots como cancelados e **liberará os horários** no calendário.
            </p>

            <form id="series-cancellation-form" onsubmit="return false;">
                @csrf
                @method('DELETE')
                <input type="hidden" name="master_id" id="form-master-id">

                <div class="mb-6">
                    <label for="justificativa-gestor" class="block text-sm font-medium text-gray-700 mb-2">
                        Motivo do Cancelamento da Série:
                    </label>
                    <textarea id="justificativa-gestor" name="justificativa_gestor" rows="3" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Obrigatório, descreva o motivo do cancelamento (mínimo 5 caracteres)..." required minlength="5"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeSeriesCancellationModal()" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                        Fechar
                    </button>
                    <button type="submit" id="submit-series-cancellation-btn" class="px-4 py-2 bg-red-700 text-white font-bold rounded-lg hover:bg-red-800 transition duration-150">
                        Confirmar Cancelamento
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- SCRIPTS DE AÇÃO AJAX --}}
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        // 🛑 CORRIGIDO: Passamos ":masterId" como valor para o parâmetro "masterId".
        // O Laravel gera a URL com o placeholder, e o JS o substitui corretamente.
        const CANCEL_SERIES_API_URL = '{{ route("admin.reservas.cancel_client_series", ["masterId" => ":masterId"]) }}';

        // =========================================================
        // LÓGICA DO MODAL DE CANCELAMENTO DE SÉRIE
        // =========================================================

        function openSeriesCancellationModal(seriesId, clientName, futureCount) {
            document.getElementById('series-id-placeholder').textContent = seriesId;
            document.getElementById('client-name-placeholder').textContent = clientName;
            document.getElementById('slots-count-placeholder').textContent = futureCount;
            document.getElementById('form-master-id').value = seriesId;
            document.getElementById('justificativa-gestor').value = '';

            document.getElementById('series-cancellation-modal').classList.remove('hidden');
            document.getElementById('series-cancellation-modal').classList.add('flex');

            setTimeout(() => {
                document.getElementById('series-cancellation-modal-content').classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        function closeSeriesCancellationModal() {
            document.getElementById('series-cancellation-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                document.getElementById('series-cancellation-modal').classList.remove('flex');
                document.getElementById('series-cancellation-modal').classList.add('hidden');
            }, 300);
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

        // --- Listener de Submissão do Formulário de Cancelamento de Série ---
        document.getElementById('series-cancellation-form').addEventListener('submit', async function(event) {
            event.preventDefault();

            const formElement = event.target;

            if (!formElement.reportValidity()) {
                return;
            }

            const masterId = document.getElementById('form-master-id').value;
            const justificativa = document.getElementById('justificativa-gestor').value.trim();
            // Substitui o placeholder no JS (a variável CANCEL_SERIES_API_URL agora tem o ":masterId")
            const url = CANCEL_SERIES_API_URL.replace(':masterId', masterId);

            const submitBtn = document.getElementById('submit-series-cancellation-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Cancelando Série...';

            const data = {
                justificativa_gestor: justificativa,
                _token: CSRF_TOKEN,
                _method: 'DELETE', // Necessário para a rota DELETE/POST
            };

            try {
                const response = await fetch(url, {
                    method: 'POST', // Transporte HTTP
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
                    displayAjaxMessage(result.message, 'success');
                    closeSeriesCancellationModal();

                    // Remove a seção da série da view
                    document.getElementById(`series-container-${masterId}`)?.remove();

                    // Recarrega a página para refletir o status atualizado
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);

                } else if (response.status === 422 && result.errors) {
                    const errors = Object.values(result.errors).flat().join('<br>');
                    displayAjaxMessage(`ERRO DE VALIDAÇÃO:<br>${errors}`, 'error');
                } else {
                    displayAjaxMessage(result.message || `Erro desconhecido. Status: ${response.status}.`, 'error');
                }

            } catch (error) {
                console.error('Erro de Rede/Comunicação:', error);
                displayAjaxMessage("Erro de conexão. Tente novamente.", 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Cancelamento';
            }
        });
    </script>
</x-app-layout>
