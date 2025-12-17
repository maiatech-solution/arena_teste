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
                @if (session('warning'))
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">{{ session('warning') }}</p>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded" role="alert">
                        <p>Houve um erro na validação dos dados: Verifique se o motivo de cancelamento é válido.</p>
                    </div>
                @endif

                <div class="mb-6">
                    <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar ao Painel de Reservas
                    </a>
                </div>

                {{-- FILTROS --}}
                <div class="flex flex-col mb-8 space-y-4">
                    <div class="flex flex-col md:flex-row items-center space-y-4 md:space-y-0 md:space-x-6 w-full">
                        <form method="GET" action="{{ route('admin.reservas.confirmadas') }}" class="flex flex-col md:flex-row items-end md:items-center space-y-4 md:space-y-0 md:space-x-4 w-full">
                            <input type="hidden" name="only_mine" value="{{ $isOnlyMine ? 'true' : 'false' }}">

                            <div class="flex space-x-3 w-full md:w-auto flex-shrink-0">
                                <div class="w-1/2 md:w-32">
                                    <label for="start_date" class="block text-xs font-semibold text-gray-500 mb-1">De:</label>
                                    <input type="date" name="start_date" id="start_date" value="{{ $startDate ?? '' }}"
                                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm w-full">
                                </div>
                                <div class="w-1/2 md:w-32">
                                    <label for="end_date" class="block text-xs font-semibold text-gray-500 mb-1">Até:</label>
                                    <input type="date" name="end_date" id="end_date" value="{{ $endDate ?? '' }}"
                                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm w-full">
                                </div>
                            </div>

                            <div class="flex space-x-2 w-full md:w-auto items-end flex-grow">
                                <div class="flex-grow">
                                    <label for="search" class="block text-xs font-semibold text-gray-500 mb-1">Pesquisar:</label>
                                    <input type="text" name="search" id="search" value="{{ $search ?? '' }}"
                                        placeholder="Nome, contato..."
                                        class="px-4 py-2 text-sm border border-gray-300 rounded-lg w-full transition duration-150">
                                </div>

                                <div class="flex items-end space-x-1 h-[42px]">
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white h-full p-2 rounded-lg shadow-md transition duration-150 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                                    </button>
                                    @if (isset($search) && $search || $startDate || $endDate)
                                        <a href="{{ route('admin.reservas.confirmadas', ['only_mine' => $isOnlyMine ? 'true' : 'false']) }}"
                                            class="text-red-500 hover:text-red-700 h-full p-2 transition duration-150 rounded-lg border border-red-200 flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- TABELA --}}
                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Data/Hora</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cliente/Reserva</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Preço</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Pagamento</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Criada Por</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($reservas as $reserva)
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-indigo-50 transition duration-150">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/y') }}</div>
                                        <div class="text-indigo-600 text-xs font-semibold">
                                            {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        </div>
                                        @if ($reserva->is_recurrent)
                                            <span class="mt-1 inline-block text-[10px] font-bold text-indigo-700 bg-indigo-200 px-1 rounded">RECORRENTE</span>
                                        @else
                                            <span class="mt-1 inline-block text-[10px] font-bold text-blue-700 bg-blue-200 px-1 rounded">PONTUAL</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-left">
                                        @if ($reserva->user)
                                            <div class="text-sm font-semibold text-gray-900">{{ $reserva->user->name }}</div>
                                            <div class="text-xs text-green-600 font-medium">Agendamento de Cliente</div>
                                        @else
                                            <div class="text-sm font-bold text-indigo-700">{{ $reserva->client_name ?? 'Cliente (Manual)' }}</div>
                                            <div class="text-xs text-gray-500 font-medium">{{ $reserva->client_contact ?? 'Contato não informado' }}</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-green-700 text-right">
                                        R$ {{ number_format($reserva->price ?? 0, 2, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @php
                                            $status = $reserva->payment_status;
                                            $badgeClass = '';
                                            $badgeText = '';
                                            $isOverdue = false;

                                            // 🎯 LÓGICA DE OURO: CORREÇÃO DO ERRO CARBON
                                            // Extraímos apenas a HORA do end_time para evitar a duplicidade de data
                                            $horaFinalLimpa = \Carbon\Carbon::parse($reserva->end_time)->format('H:i:s');
                                            $dateTimeString = \Carbon\Carbon::parse($reserva->date)->format('Y-m-d') . ' ' . $horaFinalLimpa;

                                            try {
                                                $reservaEndTime = \Carbon\Carbon::parse($dateTimeString);
                                                // Verifica se já passou do horário ATUAL
                                                if ($reservaEndTime->lessThan(\Carbon\Carbon::now()) && !in_array($status, ['paid', 'completed'])) {
                                                    $isOverdue = true;
                                                }
                                            } catch (\Exception $e) {
                                                $isOverdue = false;
                                            }

                                            // Define a exibição baseado no status real e no tempo
                                            if ($status === 'paid' || $status === 'completed' || $reserva->status === 'completed') {
                                                $badgeClass = 'bg-green-100 text-green-800 border border-green-300';
                                                $badgeText = 'Pago / Concluído';
                                            } elseif ($status === 'partial') {
                                                $badgeClass = 'bg-yellow-100 text-yellow-800 border border-yellow-300';
                                                $badgeText = 'Parcial (R$' . number_format($reserva->remaining_amount ?? 0, 2, ',', '.') . ')';
                                            } elseif ($isOverdue) {
                                                $badgeClass = 'bg-red-700 text-white font-bold animate-pulse shadow-lg';
                                                $badgeText = 'ATRASADO';
                                            } else {
                                                $badgeClass = 'bg-red-100 text-red-800';
                                                $badgeText = 'Aguardando Pagamento';
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                            {{ $badgeText }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-left min-w-[120px]">
                                        @if ($reserva->manager)
                                            <span class="font-medium text-purple-700 bg-purple-100 px-2 py-0.5 text-xs rounded-full whitespace-nowrap">
                                                {{ \Illuminate\Support\Str::limit($reserva->manager->name, 10, '...') }} (Gestor)
                                            </span>
                                        @else
                                            <span class="text-gray-600 bg-gray-100 px-2 py-0.5 text-xs rounded-full">Web</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium">
                                        <div class="flex flex-col space-y-1">
                                            <a href="{{ route('admin.reservas.show', $reserva) }}"
                                                class="inline-block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                Detalhes
                                            </a>

                                            {{-- Só mostra "Lançar no Caixa" se não estiver concluído --}}
                                            @if($reserva->status !== 'completed' && $status !== 'paid')
                                            <a href="{{ route('admin.payment.index', [
                                                'reserva_id' => $reserva->id,
                                                'date' => \Carbon\Carbon::parse($reserva->date)->format('Y-m-d')
                                            ]) }}"
                                                class="inline-block w-full text-center bg-green-600 hover:bg-green-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                Lançar no Caixa
                                            </a>
                                            @endif

                                            @if ($reserva->is_recurrent)
                                                <button onclick="openCancellationModal({{ $reserva->id }}, 'PATCH', '{{ route('admin.reservas.cancelar_pontual', ':id') }}', 'Cancelar somente hoje?', 'Cancelar ESTE DIA')"
                                                    class="inline-block w-full text-center bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                    Cancelar ESTE DIA
                                                </button>
                                                <button onclick="openCancellationModal({{ $reserva->id }}, 'DELETE', '{{ route('admin.reservas.cancelar_serie', ':id') }}', 'Cancelar série futura?', 'Cancelar SÉRIE')"
                                                    class="inline-block w-full text-center bg-red-800 hover:bg-red-900 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                    Cancelar SÉRIE
                                                </button>
                                            @else
                                                <button onclick="openCancellationModal({{ $reserva->id }}, 'PATCH', '{{ route('admin.reservas.cancelar', ':id') }}', 'Confirmar cancelamento?', 'Cancelar')"
                                                    class="inline-block w-full text-center bg-red-600 hover:bg-red-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                    Cancelar
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 italic">Nenhuma reserva confirmada encontrada.</td>
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
    <div id="cancellation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-all" id="cancellation-modal-content">
            <h3 id="modal-title" class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação de Cancelamento</h3>
            <p id="modal-message" class="text-gray-700 mb-4"></p>
            <div class="mb-6">
                <label for="cancellation-reason-input" class="block text-sm font-medium text-gray-700 mb-2">Motivo:</label>
                <textarea id="cancellation-reason-input" rows="3" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Mínimo 5 caracteres..."></textarea>
            </div>
            <div class="flex justify-end space-x-3">
                <button onclick="closeCancellationModal()" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">Fechar</button>
                <button id="confirm-cancellation-btn" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">Confirmar</button>
            </div>
        </div>
    </div>

    <script>
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        const CSRF_TOKEN = metaTag ? metaTag.getAttribute('content') : null;
        let currentReservaId = null;
        let currentMethod = null;
        let currentUrlBase = null;

        function openCancellationModal(reservaId, method, urlBase, message, buttonText) {
            currentReservaId = reservaId;
            currentMethod = method;
            currentUrlBase = urlBase;
            document.getElementById('cancellation-reason-input').value = '';
            document.getElementById('modal-message').textContent = message;
            document.getElementById('cancellation-modal').classList.remove('hidden');
            document.getElementById('cancellation-modal').classList.add('flex');
            document.getElementById('confirm-cancellation-btn').textContent = buttonText;
        }

        function closeCancellationModal() {
            document.getElementById('cancellation-modal').classList.replace('flex', 'hidden');
        }

        document.getElementById('confirm-cancellation-btn').addEventListener('click', async function() {
            const reason = document.getElementById('cancellation-reason-input').value.trim();
            if (reason.length < 5) {
                alert("Por favor, forneça um motivo com pelo menos 5 caracteres.");
                return;
            }

            const url = currentUrlBase.replace(':id', currentReservaId);
            const submitBtn = this;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processando...';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({ cancellation_reason: reason, _token: CSRF_TOKEN, _method: currentMethod })
                });

                const result = await response.json();
                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(result.message || "Erro ao processar.");
                }
            } catch (error) {
                alert("Erro de conexão.");
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Confirmar Cancelamento';
            }
        });
    </script>
</x-app-layout>
