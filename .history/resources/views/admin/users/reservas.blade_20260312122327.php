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
                    <p class="text-sm text-gray-700 dark:text-gray-300">Contato: {{ $client->whatsapp_contact ?? 'N/A' }}
                    </p>
                </div>

                <div class="mb-6 flex justify-between items-center">
                    <a href="{{ route('admin.users.index', ['role_filter' => 'cliente']) }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-white uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Voltar à Lista de Usuários
                    </a>
                </div>

                @php
                    // 3. LÓGICA DE AGRUPAMENTO (ORIGINAL)
                    $groupedReservas = $reservas->groupBy(function ($item) {
                        return $item->is_recurrent ? $item->recurrent_series_id : 'pontual';
                    });

                    // Injeção de contagens futuras enviadas pelo Controller
                    $seriesFutureCounts = $seriesFutureCounts ?? [];

                    // 🛡️ TRAVA DE COLABORADOR: Somente Admin/Gestor podem cancelar séries inteiras
                    $podeCancelarSerie = in_array(auth()->user()->role, ['admin', 'gestor']);
                @endphp

                <div id="ajax-message-container" class="mb-4"></div>

                <div class="space-y-10">

                    {{-- 🟢 SEÇÃO: RESERVAS PONTUAIS 🟢 --}}
                    @if ($groupedReservas->has('pontual'))
                        <div class="p-6 bg-green-50 dark:bg-green-900/10 rounded-xl shadow-lg border border-green-200 dark:border-green-800"
                            id="series-container-pontual">
                            <div
                                class="flex justify-between items-center border-b border-green-300 dark:border-green-800 pb-3 mb-4">
                                <h3
                                    class="text-xl font-bold text-green-700 dark:text-green-400 uppercase tracking-tighter">
                                    Reservas Pontuais
                                    <span
                                        class="text-sm font-normal text-gray-500 dark:text-gray-400 ml-2">({{ $groupedReservas['pontual']->count() }}
                                        registros na página)</span>
                                </h3>
                            </div>

                            <div class="overflow-x-auto">
                                @include('admin.users.partials.reservation_table', [
                                    'reservas' => $groupedReservas['pontual'],
                                ])
                            </div>
                        </div>
                    @endif


                    {{-- 🟣 SEÇÃO: SÉRIES RECORRENTES (LOOP ORIGINAL) 🟣 --}}
                    @foreach ($groupedReservas as $seriesId => $seriesReservas)
                        @if ($seriesId !== 'pontual')
                            @php
                                // Lógica de contagem e datas original
                                $totalFutureSlots =
                                    $seriesFutureCounts[$seriesId] ??
                                    $seriesReservas
                                        ->filter(
                                            fn($r) => \Carbon\Carbon::parse($r->date)->isFuture() ||
                                                \Carbon\Carbon::parse($r->date)->isToday(),
                                        )
                                        ->count();
                                $maxDate = $seriesReservas->max('date');
                            @endphp

                            <div class="p-6 bg-fuchsia-50 dark:bg-fuchsia-900/10 rounded-xl shadow-lg border border-fuchsia-200 dark:border-fuchsia-800"
                                id="series-container-{{ $seriesId }}">
                                <div
                                    class="flex flex-col md:flex-row justify-between items-start md:items-center border-b border-fuchsia-300 dark:border-fuchsia-800 pb-3 mb-4">
                                    <h3
                                        class="text-xl font-bold text-fuchsia-700 dark:text-fuchsia-400 uppercase tracking-tighter">
                                        Série Recorrente #{{ $seriesId }}
                                        <span
                                            class="text-sm font-normal text-gray-500 dark:text-gray-400 block md:inline md:ml-3">
                                            ({{ $seriesReservas->count() }} slots | Expira:
                                            {{ \Carbon\Carbon::parse($maxDate)->format('d/m/Y') }})
                                        </span>
                                    </h3>

                                    {{-- Ação de Cancelamento de Série --}}
                                    @if ($totalFutureSlots > 0)
                                        @if ($podeCancelarSerie)
                                            <button type="button"
                                                class="mt-3 md:mt-0 px-4 py-2 bg-red-600 text-white text-xs font-black rounded-lg hover:bg-red-700 transition shadow-md uppercase tracking-widest"
                                                data-series-id="{{ $seriesId }}"
                                                data-client-name="{{ $client->name }}"
                                                data-future-slots="{{ $totalFutureSlots }}"
                                                onclick="handleSeriesModal(this)">
                                                Cancelar TODA a Série
                                            </button>
                                        @else
                                            <span
                                                class="text-[10px] font-bold text-gray-400 border border-gray-300 px-2 py-1 rounded uppercase italic">Ação
                                                Restrita a Gestores</span>
                                        @endif
                                    @else
                                        <span
                                            class="mt-3 md:mt-0 text-xs font-bold text-gray-400 bg-gray-100 px-3 py-1 rounded-full uppercase tracking-widest italic">Série
                                            Finalizada</span>
                                    @endif
                                </div>

                                <div class="overflow-x-auto max-h-[500px] overflow-y-auto custom-scrollbar">
                                    @include('admin.users.partials.reservation_table', [
                                        'reservas' => $seriesReservas,
                                    ])
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Empty State --}}
                    @if ($reservas->isEmpty())
                        <div
                            class="text-center py-20 bg-gray-50 dark:bg-gray-700/50 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-600">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                </path>
                            </svg>
                            <p class="mt-4 text-gray-500 dark:text-gray-400 font-medium italic">Nenhuma reserva
                                encontrada para este cliente.</p>
                        </div>
                    @endif
                </div>

                @if (!$reservas->isEmpty())
                    <div class="mt-8">
                        {{ $reservas->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- MODAL DE CANCELAMENTO (MANTIDO TODA A ESTRUTURA ORIGINAL) --}}
    <div id="series-cancellation-modal"
        class="fixed inset-0 bg-gray-900 bg-opacity-80 hidden items-center justify-center z-[100] backdrop-blur-sm transition-opacity duration-300">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-lg p-8 m-4 transform transition-all duration-300 scale-95 opacity-0"
            id="series-cancellation-modal-content" onclick="event.stopPropagation()">

            <div class="flex items-center mb-6 text-red-700 dark:text-red-500">
                <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.398 16c-.77 1.333.192 3 1.732 3z">
                    </path>
                </svg>
                <h3 class="text-2xl font-black uppercase tracking-tighter">Ação Crítica Irreversível</h3>
            </div>

            <div class="space-y-4 mb-8">
                <p id="series-cancellation-message" class="text-base text-gray-700 dark:text-gray-300 leading-relaxed">
                    Você removerá permanentemente <span id="slots-count-placeholder"
                        class="font-black text-red-600 text-xl px-1"></span> reservas futuras da série <span
                        class="font-black text-indigo-600">#<span id="series-id-placeholder"></span></span> pertencente
                    ao cliente <span class="font-black text-gray-900 dark:text-white uppercase"><span
                            id="client-name-placeholder"></span></span>.
                </p>
                <p
                    class="text-sm font-bold text-gray-500 border-l-4 border-red-500 pl-4 bg-red-50 dark:bg-red-900/10 py-2">
                    Todos os horários ficarão livres para novos agendamentos no calendário.
                </p>
            </div>

            <form id="series-cancellation-form" onsubmit="return false;">
                @csrf
                <input type="hidden" name="master_id" id="form-master-id">

                <div class="mb-8">
                    <label for="justificativa-gestor"
                        class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-3">
                        Motivo do Cancelamento (Mín. 5 caracteres):
                    </label>
                    <textarea id="justificativa-gestor" name="justificativa_gestor" rows="4"
                        class="w-full p-4 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-4 focus:ring-red-500/20 focus:border-red-500 transition-all outline-none"
                        placeholder="Ex: Cliente desistiu da mensalidade..." required minlength="5"></textarea>
                </div>

                <div class="flex flex-col sm:flex-row-reverse gap-3">
                    <button type="submit" id="submit-series-cancellation-btn"
                        class="w-full sm:w-auto px-6 py-3 bg-red-600 text-white font-black rounded-xl hover:bg-red-700 transition-all shadow-lg shadow-red-600/30 uppercase tracking-widest text-sm">
                        Confirmar Cancelamento
                    </button>
                    <button type="button" onclick="closeSeriesCancellationModal()"
                        class="w-full sm:w-auto px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-bold rounded-xl hover:bg-gray-300 dark:hover:bg-gray-600 transition-all uppercase tracking-widest text-sm">
                        Desistir
                    </button>
                </div>
            </form>
        </div>
    </div>


    {{-- ⚡ SCRIPTS AJAX (MANTIDO TODO O FUNCIONAMENTO ORIGINAL) ⚡ --}}
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        // ✅ CORREÇÃO DO PLACEHOLDER PARA REPLACE
        const CANCEL_SERIES_API_URL = '{{ route('admin.reservas.cancelar_serie', ['reserva' => ':masterId']) }}';

        function openSeriesCancellationModal(seriesId, clientName, futureCount) {
            document.getElementById('series-id-placeholder').textContent = seriesId;
            document.getElementById('client-name-placeholder').textContent = clientName;
            document.getElementById('slots-count-placeholder').textContent = futureCount;
            document.getElementById('form-master-id').value = seriesId;
            document.getElementById('justificativa-gestor').value = '';

            const modal = document.getElementById('series-cancellation-modal');
            modal.classList.replace('hidden', 'flex');

            setTimeout(() => {
                const content = document.getElementById('series-cancellation-modal-content');
                content.classList.remove('opacity-0', 'scale-95');
                content.classList.add('opacity-100', 'scale-100');
            }, 10);
        }

        function closeSeriesCancellationModal() {
            const content = document.getElementById('series-cancellation-modal-content');
            content.classList.replace('opacity-100', 'scale-100', 'opacity-0', 'scale-95');

            setTimeout(() => {
                const modal = document.getElementById('series-cancellation-modal');
                modal.classList.replace('flex', 'hidden');
            }, 300);
        }

        function displayAjaxMessage(message, type = 'success') {
            const container = document.getElementById('ajax-message-container');
            const colorClasses = type === 'success' ? 'bg-green-100 text-green-700 border-green-200' :
                'bg-red-100 text-red-700 border-red-200';

            container.innerHTML = `
                <div class="p-4 mb-4 text-sm font-black text-center uppercase tracking-widest ${colorClasses} rounded-xl border shadow-sm" role="alert">
                    ${message}
                </div>
            `;
            setTimeout(() => {
                container.innerHTML = '';
            }, 6000);
        }

        function handleSeriesModal(button) {
            const seriesId = button.getAttribute('data-series-id');
            const clientName = button.getAttribute('data-client-name');
            const futureCount = button.getAttribute('data-future-slots');
            openSeriesCancellationModal(seriesId, clientName, futureCount);
        }

        // --- SUBMISSÃO DO FORMULÁRIO ---
        document.getElementById('series-cancellation-form').addEventListener('submit', async function(event) {
            event.preventDefault();
            const submitBtn = document.getElementById('submit-series-cancellation-btn');
            const masterId = document.getElementById('form-master-id').value;
            const justificativa = document.getElementById('justificativa-gestor').value.trim();
            const url = CANCEL_SERIES_API_URL.replace(':masterId', masterId);

            submitBtn.disabled = true;
            submitBtn.textContent = 'CANCELANDO...';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        // ✅ Sincronizado com o validate do Controller
                        cancellation_reason: justificativa,

                        // ✅ Campos obrigatórios enviados como padrão
                        should_refund: false,
                        paid_amount_ref: 0,

                        _method: 'DELETE'
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // 1. Mostra a mensagem de sucesso
                    displayAjaxMessage(result.message, 'success');

                    // 2. Fecha o modal com a animação
                    closeSeriesCancellationModal();

                    // 3. Efeito visual de remoção do container da série
                    const container = document.getElementById(`series-container-${masterId}`);
                    if (container) {
                        container.style.transition = 'all 0.5s ease';
                        container.style.opacity = '0';
                        container.style.transform = 'translateY(20px)';

                        // Remove do DOM após a transição
                        setTimeout(() => {
                            container.remove();
                            // Se não houver mais séries, recarrega para mostrar o "Empty State"
                            if (document.querySelectorAll('[id^="series-container-"]').length === 0) {
                                window.location.reload();
                            }
                        }, 500);
                    }

                    // 4. Recarrega a página após 1 segundo para atualizar saldos e status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);

                } else {
                    // Caso o Controller retorne erro de validação ou lógica
                    const errorMsg = result.errors ? Object.values(result.errors).flat().join(' ') : (result
                        .message || 'Erro ao processar requisição.');
                    displayAjaxMessage(errorMsg, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                displayAjaxMessage("Falha crítica de comunicação com o servidor.", 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Cancelamento';
            }
        });

        // Fechar no ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSeriesCancellationModal();
        });
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</x-app-layout>
