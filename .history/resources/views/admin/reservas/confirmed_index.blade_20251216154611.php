<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-2xl sm:rounded-xl p-6 lg:p-10">

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
                                <label class="block text-xs font-semibold text-gray-500 mb-1">De:</label>
                                <input type="date" name="start_date" value="{{ $startDate ?? '' }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg w-full">
                            </div>
                            <div class="w-1/2 md:w-32">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Até:</label>
                                <input type="date" name="end_date" value="{{ $endDate ?? '' }}" class="px-3 py-2 text-sm border border-gray-300 rounded-lg w-full">
                            </div>
                        </div>

                        <div class="flex space-x-2 w-full md:w-auto items-end flex-grow">
                            <div class="flex-grow">
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Pesquisar:</label>
                                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Nome, contato..." class="px-4 py-2 text-sm border border-gray-300 rounded-lg w-full">
                            </div>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white p-2 rounded-lg h-[42px] px-4 shadow-md transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Data/Hora</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase">Cliente/Reserva</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase">Preço</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase">Pagamento</th>
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

                                    <td class="px-4 py-3 text-left">
                                        <div class="text-sm font-semibold text-gray-900">{{ $reserva->user->name ?? $reserva->client_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $reserva->client_contact }}</div>
                                    </td>

                                    <td class="px-4 py-3 text-sm font-bold text-green-700 text-right">
                                        R$ {{ number_format($reserva->price ?? 0, 2, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $status = $reserva->payment_status;
                                            $badgeClass = '';
                                            $badgeText = '';
                                            $isOverdue = false;

                                            // Lógica para Pagamento Parcial ou Pendente
                                            if (in_array($status, ['pending', 'unpaid', 'partial'])) {
                                                // ✅ CORREÇÃO CRÍTICA: Limpamos o end_time para evitar data duplicada
                                                $onlyTime = \Carbon\Carbon::parse($reserva->end_time)->format('H:i:s');
                                                $dateTimeString = \Carbon\Carbon::parse($reserva->date)->format('Y-m-d') . ' ' . $onlyTime;
                                                $reservaEndTime = \Carbon\Carbon::parse($dateTimeString);

                                                if ($reservaEndTime->isPast()) {
                                                    $isOverdue = true;
                                                }
                                            }

                                            // Definição das Cores
                                            if ($status === 'paid' || $status === 'completed') {
                                                $badgeClass = 'bg-green-100 text-green-800';
                                                $badgeText = 'Pago';
                                            } elseif ($isOverdue) {
                                                $badgeClass = 'bg-red-700 text-white font-bold animate-pulse';
                                                $badgeText = 'ATRASADO';
                                            } elseif ($status === 'partial') {
                                                $badgeClass = 'bg-yellow-100 text-yellow-800';
                                                $badgeText = 'Parcial';
                                            } else {
                                                $badgeClass = 'bg-red-100 text-red-800';
                                                $badgeText = 'Pendente';
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                            {{ $badgeText }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium">
                                        <div class="flex flex-col space-y-1">
                                            <a href="{{ route('admin.reservas.show', $reserva) }}" class="bg-indigo-600 text-white px-3 py-1 text-xs text-center rounded">Detalhes</a>
                                            <a href="{{ route('admin.payment.index', ['reserva_id' => $reserva->id, 'date' => \Carbon\Carbon::parse($reserva->date)->format('Y-m-d')]) }}" class="bg-green-600 text-white px-3 py-1 text-xs text-center rounded">Lançar Caixa</a>
                                            <button onclick="openCancellationModal({{ $reserva->id }}, 'PATCH', '{{ route('admin.reservas.cancelar', ':id') }}', 'Cancelar reserva?', 'Confirmar')" class="bg-red-600 text-white px-3 py-1 text-xs rounded">Cancelar</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Nenhuma reserva encontrada.</td>
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

    {{-- MODAL DE CANCELAMENTO --}}
    <div id="cancellation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4" id="cancellation-modal-content">
            <h3 class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação</h3>
            <p id="modal-message" class="text-gray-700 mb-4"></p>
            <textarea id="cancellation-reason-input" rows="3" class="w-full p-2 border border-gray-300 rounded-lg mb-4" placeholder="Motivo..."></textarea>
            <div class="flex justify-end space-x-3">
                <button onclick="closeCancellationModal()" class="px-4 py-2 bg-gray-200 rounded-lg">Voltar</button>
                <button id="confirm-cancellation-btn" class="px-4 py-2 bg-red-600 text-white rounded-lg">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        let currentReservaId = null;
        let currentMethod = null;
        let currentUrlBase = null;

        function openCancellationModal(id, method, url, msg, btnText) {
            currentReservaId = id; currentMethod = method; currentUrlBase = url;
            document.getElementById('modal-message').textContent = msg;
            document.getElementById('confirm-cancellation-btn').textContent = btnText;
            document.getElementById('cancellation-modal').classList.replace('hidden', 'flex');
        }

        function closeCancellationModal() {
            document.getElementById('cancellation-modal').classList.replace('flex', 'hidden');
        }

        document.getElementById('confirm-cancellation-btn').addEventListener('click', async () => {
            const reason = document.getElementById('cancellation-reason-input').value;
            if (reason.length < 5) return alert('Motivo obrigatório (min 5 carac)');

            const url = currentUrlBase.replace(':id', currentReservaId);
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ cancellation_reason: reason, _method: currentMethod })
            });

            if (response.ok) {
                alert('Sucesso!');
                window.location.reload();
            } else {
                alert('Erro ao processar.');
            }
        });
    </script>
</x-app-layout>
