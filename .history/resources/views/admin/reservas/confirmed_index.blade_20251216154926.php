<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-2xl sm:rounded-xl p-6 lg:p-10">

                {{-- FEEDBACK DE SESSÃO --}}
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

                <div class="mb-6">
                    <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar ao Painel de Reservas
                    </a>
                </div>

                {{-- FORMULÁRIO DE FILTROS --}}
                <div class="flex flex-col mb-8 space-y-4">
                    <form method="GET" action="{{ route('admin.reservas.confirmadas') }}" class="flex flex-col md:flex-row items-end md:items-center space-y-4 md:space-y-0 md:space-x-4 w-full">
                        <input type="hidden" name="only_mine" value="{{ $isOnlyMine ? 'true' : 'false' }}">

                        <div class="flex space-x-3 w-full md:w-auto flex-shrink-0">
                            <div class="w-1/2 md:w-32">
                                <label for="start_date" class="block text-xs font-semibold text-gray-500 mb-1">De:</label>
                                <input type="date" name="start_date" id="start_date" value="{{ $startDate ?? '' }}"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full">
                            </div>
                            <div class="w-1/2 md:w-32">
                                <label for="end_date" class="block text-xs font-semibold text-gray-500 mb-1">Até:</label>
                                <input type="date" name="end_date" id="end_date" value="{{ $endDate ?? '' }}"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full">
                            </div>
                        </div>

                        <div class="flex space-x-2 w-full md:w-auto items-end flex-grow">
                            <div class="flex-grow">
                                <label for="search" class="block text-xs font-semibold text-gray-500 mb-1">Pesquisar:</label>
                                <input type="text" name="search" id="search" value="{{ $search ?? '' }}"
                                    placeholder="Nome ou contato..."
                                    class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition duration-150 w-full">
                            </div>

                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white h-[42px] px-4 rounded-lg shadow-md transition duration-150 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TABELA --}}
                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Data/Hora</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Cliente</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">Preço</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($reservas as $reserva)
                                <tr class="hover:bg-indigo-50 transition duration-150">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/y') }}</div>
                                        <div class="text-indigo-600 text-xs font-semibold">
                                            {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="text-sm font-semibold text-gray-900">{{ $reserva->user->name ?? $reserva->client_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $reserva->client_contact }}</div>
                                    </td>

                                    <td class="px-4 py-3 text-sm font-bold text-green-700 text-right">
                                        R$ {{ number_format($reserva->price ?? 0, 2, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @php
                                            // LÓGICA DE STATUS MELHORADA
                                            $pStatus = $reserva->payment_status;
                                            $rStatus = $reserva->status;
                                            $badgeClass = '';
                                            $badgeText = '';
                                            $isOverdue = false;

                                            // 1. Se estiver Pago ou Concluído (Prioridade Verde)
                                            if ($rStatus === 'completed' || $rStatus === 'concluida' || $pStatus === 'paid') {
                                                $badgeClass = 'bg-green-100 text-green-800 border border-green-200';
                                                $badgeText = 'PAGO';
                                            }
                                            // 2. Se não estiver pago, checamos se está atrasado
                                            else {
                                                // ✅ CORREÇÃO DO ERRO CARBON: Limpando a data do end_time
                                                $onlyTime = \Carbon\Carbon::parse($reserva->end_time)->format('H:i:s');
                                                $dateTimeLimit = \Carbon\Carbon::parse($reserva->date)->format('Y-m-d') . ' ' . $onlyTime;

                                                if (\Carbon\Carbon::parse($dateTimeLimit)->isPast()) {
                                                    $isOverdue = true;
                                                    $badgeClass = 'bg-red-700 text-white font-bold animate-pulse shadow-md';
                                                    $badgeText = 'ATRASADO';
                                                } else {
                                                    $badgeClass = 'bg-red-100 text-red-800';
                                                    $badgeText = 'AGUARDANDO';
                                                }

                                                // Ajuste para Pagamento Parcial
                                                if ($pStatus === 'partial') {
                                                    $badgeClass = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                                                    $badgeText = 'PARCIAL';
                                                }
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $badgeClass }}">
                                            {{ $badgeText }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium">
                                        <div class="flex flex-col space-y-1">
                                            <a href="{{ route('admin.reservas.show', $reserva) }}" class="bg-indigo-600 text-white px-3 py-1 text-xs text-center rounded hover:bg-indigo-700">Detalhes</a>

                                            {{-- Só permite lançar caixa se não estiver totalmente pago --}}
                                            @if($pStatus !== 'paid' && $rStatus !== 'completed')
                                                <a href="{{ route('admin.payment.index', ['reserva_id' => $reserva->id, 'date' => \Carbon\Carbon::parse($reserva->date)->format('Y-m-d')]) }}" class="bg-green-600 text-white px-3 py-1 text-xs text-center rounded hover:bg-green-700">Lançar Caixa</a>
                                            @endif

                                            @if ($reserva->is_recurrent)
                                                <button onclick="openCancellationModal({{ $reserva->id }}, 'PATCH', '{{ route('admin.reservas.cancelar_pontual', ':id') }}', 'Cancelar somente hoje?', 'Confirmar')" class="bg-yellow-600 text-white px-3 py-1 text-xs rounded hover:bg-yellow-700">Cancelar Hoje</button>
                                            @else
                                                <button onclick="openCancellationModal({{ $reserva->id }}, 'PATCH', '{{ route('admin.reservas.cancelar', ':id') }}', 'Deseja cancelar?', 'Confirmar')" class="bg-red-600 text-white px-3 py-1 text-xs rounded hover:bg-red-700">Cancelar</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic font-medium">Nenhuma reserva encontrada para este filtro.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-8">
                    {{ $reservas->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    <div id="cancellation-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4">
            <h3 class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação</h3>
            <p id="modal-message" class="text-gray-700 mb-4"></p>
            <textarea id="cancellation-reason-input" rows="3" class="w-full p-2 border border-gray-300 rounded-lg mb-4" placeholder="Motivo do cancelamento..."></textarea>
            <div class="flex justify-end space-x-3">
                <button onclick="closeCancellationModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold">Voltar</button>
                <button id="confirm-cancellation-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg font-bold">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentReservaId = null, currentMethod = null, currentUrlBase = null;

        function openCancellationModal(id, method, url, msg, btnText) {
            currentReservaId = id; currentMethod = method; currentUrlBase = url;
            document.getElementById('cancellation-reason-input').value = '';
            document.getElementById('modal-message').textContent = msg;
            document.getElementById('confirm-cancellation-btn').textContent = btnText;
            document.getElementById('cancellation-modal').classList.replace('hidden', 'flex');
        }

        function closeCancellationModal() {
            document.getElementById('cancellation-modal').classList.replace('flex', 'hidden');
        }

        document.getElementById('confirm-cancellation-btn').addEventListener('click', async () => {
            const reason = document.getElementById('cancellation-reason-input').value;
            if (reason.length < 5) return alert('Descreva o motivo (mínimo 5 caracteres).');

            const url = currentUrlBase.replace(':id', currentReservaId);
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ cancellation_reason: reason, _method: currentMethod })
            });

            if (response.ok) { window.location.reload(); } else { alert('Erro ao processar.'); }
        });
    </script>
</x-app-layout>
