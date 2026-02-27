<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <div class="bg-gray-100 dark:bg-gray-700 p-4 rounded-lg shadow mb-6 border-l-4 border-indigo-500">
                    <p class="text-lg font-bold text-gray-900 dark:text-white">Cliente: {{ $client->name }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">Email: {{ $client->email }}</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">Contato: {{ $client->whatsapp_contact ?? 'N/A' }}</p>
                </div>

                <div class="mb-6 flex justify-between items-center">
                    <a href="{{ route('admin.users.index', ['role_filter' => 'cliente']) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-white uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Voltar à Lista de Usuários
                    </a>
                </div>

                @php
                    // Agrupa as reservas da PÁGINA ATUAL pela série recorrente ID, ou 'pontual'.
                    $groupedReservas = $reservas->groupBy(function($item) {
                        return $item->is_recurrent ? $item->recurrent_series_id : 'pontual';
                    });

                    // 🛑 IMPORTANTE: Esta variável é injetada pelo Controller
                    $seriesFutureCounts = $seriesFutureCounts ?? [];

                    // 🛡️ SUPORTE AO COLABORADOR: Define se o usuário logado tem poder de cancelamento
                    $podeCancelarSerie = in_array(auth()->user()->role, ['admin', 'gestor']);
                @endphp

                <div id="ajax-message-container" class="mb-4"></div>

                <div class="space-y-10">

                    {{-- 🟢 RESERVAS PONTUAIS 🟢 --}}
                    @if ($groupedReservas->has('pontual'))
                    <div class="p-6 bg-green-50 dark:bg-green-900/10 rounded-xl shadow-lg border border-green-200 dark:border-green-800" id="series-container-pontual">
                        <h3 class="text-xl font-bold text-green-700 dark:text-green-400 mb-4 border-b border-green-300 dark:border-green-800 pb-2">
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
                        // Obtém a contagem TOTAL de slots futuros (AGORA DO CONTROLLER)
                        $totalFutureSlots = $seriesFutureCounts[$seriesId] ?? $seriesReservas->filter(fn($r) => \Carbon\Carbon::parse($r->date)->isFuture() || \Carbon\Carbon::parse($r->date)->isToday())->count();
                        $maxDate = $seriesReservas->max('date');
                    @endphp

                    <div class="p-6 bg-fuchsia-50 dark:bg-fuchsia-900/10 rounded-xl shadow-lg border border-fuchsia-200 dark:border-fuchsia-800" id="series-container-{{ $seriesId }}">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-fuchsia-300 dark:border-fuchsia-800 pb-3 mb-4">
                            <h3 class="text-xl font-bold text-fuchsia-700 dark:text-fuchsia-400">
                                Série Recorrente #{{ $seriesId }}
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $seriesReservas->count() }} slots | Expira em: {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }})</span>
                            </h3>

                            {{-- Botão de Exclusão da Série - COM TRAVA DE COLABORADOR --}}
                            @if ($totalFutureSlots > 0)
                                @if($podeCancelarSerie)
                                    <button type="button"
                                        class="mt-3 md:mt-0 px-4 py-2 bg-red-600 text-white text-xs font-black rounded-lg hover:bg-red-700 transition shadow-md uppercase"
                                        data-series-id="{{ $seriesId }}"
                                        data-client-name="{{ $client->name }}"
                                        data-future-slots="{{ $totalFutureSlots }}"
                                        onclick="handleSeriesModal(this)">
                                        Cancelar TODA a Série
                                    </button>
                                @else
                                    <span class="text-[10px] font-bold text-gray-400 uppercase italic">Cancelamento restrito a gestores</span>
                                @endif
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
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md p-6 m-4 transform transition-transform duration-300 scale-95 opacity-0" id="series-cancellation-modal-content" onclick="event.stopPropagation()">
            <h3 class="text-xl font-bold text-red-700 dark:text-red-500 mb-4 border-b pb-2">Confirmar Cancelamento de Série Recorrente</h3>

            <p id="series-cancellation-message" class="text-gray-700 dark:text-gray-300 mb-4 font-semibold">
                Você está prestes a cancelar **TODAS** as <span id="slots-count-placeholder" class="font-extrabold text-red-700"></span> reservas futuras da série **#<span id="series-id-placeholder"></span>** do cliente **<span id="client-name-placeholder"></span>**.
            </p>
            <p class="text-sm text-red-600 dark:text-red-400 mb-4 font-bold uppercase tracking-widest">
                Esta ação é irreversível e liberará os horários.
            </p>

            <form id="series-cancellation-form" onsubmit="return false;">
                @csrf
                <input type="hidden" name="master_id" id="form-master-id">

                <div class="mb-6">
                    <label for="justificativa-gestor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Motivo do Cancelamento da Série:
                    </label>
                    <textarea id="justificativa-gestor" name="justificativa_gestor" rows="3" class="w-full p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Descreva o motivo..." required minlength="5"></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeSeriesCancellationModal()" class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-white font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
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
        // ✅ CORRIGIDO: Agora passa o placeholder correto para a rota.
        const CANCEL_SERIES_API_URL = '{{ route("admin.reservas.cancel_client_series", ["masterId" => ":masterId"]) }}';

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

        function displayAjaxMessage(message, type = 'success') {
            const container = document.getElementById('ajax-message-container');
            const bgColor = type === 'success' ? 'bg-green-100' : 'bg-red-100';
            const textColor = type === 'success' ? 'text-green-700' : 'text-red-700';

            container.innerHTML = `
                <div class="p-4 mb-4 text-sm ${textColor} ${bgColor} rounded-lg shadow-md font-bold text-center uppercase" role="alert">
                    ${message}
                </div>
            `;
            setTimeout(() => { container.innerHTML = ''; }, 5000);
        }

        function handleSeriesModal(button) {
            const seriesId = button.getAttribute('data-series-id');
            const clientName = button.getAttribute('data-client-name');
            const futureCount = button.getAttribute('data-future-slots');
            openSeriesCancellationModal(seriesId, clientName, futureCount);
        }

        document.getElementById('series-cancellation-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            const masterId = document.getElementById('form-master-id').value;
            const justificativa = document.getElementById('justificativa-gestor').value.trim();
            const url = CANCEL_SERIES_API_URL.replace(':masterId', masterId);
            const submitBtn = document.getElementById('submit-series-cancellation-btn');

            submitBtn.disabled = true;
            submitBtn.textContent = 'Cancelando Série...';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        justificativa_gestor: justificativa,
                        _method: 'DELETE'
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    displayAjaxMessage(result.message, 'success');
                    closeSeriesCancellationModal();
                    document.getElementById(`series-container-${masterId}`)?.remove();
                    setTimeout(() => { window.location.reload(); }, 500);
                } else {
                    displayAjaxMessage(result.message || 'Erro ao processar.', 'error');
                }

            } catch (error) {
                console.error('Erro:', error);
                displayAjaxMessage("Erro de conexão.", 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Cancelamento';
            }
        });
    </script>
</x-app-layout>
