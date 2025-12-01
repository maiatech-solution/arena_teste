<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <style>
        /* Estilos para badges de Status na tabela */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            line-height: 1;
        }
        /* Status do Cliente */
        .status-confirmed { background-color: #d1fae5; color: #065f46; } /* Verde - Confirmado */
        .status-pending { background-color: #ffedd5; color: #9a3412; } /* Laranja - Pendente */
        .status-cancelled { background-color: #bfdbfe; color: #1e40af; } /* Azul - Cancelado */
        .status-rejected { background-color: #fee2e2; color: #991b1b; } /* Vermelho - Rejeitado */
        .status-noshow { background-color: #fca5a5; color: #b91c1c; } /* Vermelho Claro - Falta (No Show) */
        /* Status de Inventário (Slots Fixos) */
        .status-free { background-color: #e0f2fe; color: #075985; } /* Azul Claro - Livre */
        .status-maintenance { background-color: #fce7f3; color: #9d174d; } /* Rosa/Roxo - Manutenção */
    </style>

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

                <!-- Botão de Volta para o Dashboard de Reservas -->
                <div class="mb-6">
                    <a href="{{ route('admin.reservas.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Voltar ao Painel de Reservas
                    </a>
                </div>


                <div class="flex flex-col mb-8 space-y-4">

                    {{-- GRUPO DE FILTROS E PESQUISA --}}
                    <div class="flex flex-col md:flex-row items-center md:items-center space-y-4 md:space-y-0 md:space-x-6 w-full">

                        {{-- Formulário de Pesquisa e Datas (Rotas TODAS) --}}
                        <form method="GET" action="{{ route('admin.reservas.todas') }}" class="flex flex-col md:flex-row items-end md:items-center space-y-4 md:space-y-0 md:space-x-4 w-full">
                            <input type="hidden" name="only_mine" value="{{ $isOnlyMine ? 'true' : 'false' }}">
                            {{-- Filtro de Status na URL (para facilitar a pesquisa) --}}
                            <div class="w-full md:w-36 flex-shrink-0">
                                <label for="filter_status" class="block text-xs font-semibold text-gray-500 mb-1">Status:</label>
                                <select name="filter_status" id="filter_status" class="px-3 py-2 text-sm border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 w-full">
                                    <option value="">Todos os Status</option>
                                    <option value="confirmed" {{ ($filterStatus ?? '') === 'confirmed' ? 'selected' : '' }}>Confirmadas</option>
                                    <option value="pending" {{ ($filterStatus ?? '') === 'pending' ? 'selected' : '' }}>Pendentes</option>
                                    <option value="cancelled" {{ ($filterStatus ?? '') === 'cancelled' ? 'selected' : '' }}>Canceladas</option>
                                    <option value="rejected" {{ ($filterStatus ?? '') === 'rejected' ? 'selected' : '' }}>Rejeitadas</option>
                                    <option value="no_show" {{ ($filterStatus ?? '') === 'no_show' ? 'selected' : '' }}>Falta (No Show)</option>
                                    <option value="free" {{ ($filterStatus ?? '') === 'free' ? 'selected' : '' }}>Livre (Slots)</option>
                                    <option value="maintenance" {{ ($filterStatus ?? '') === 'maintenance' ? 'selected' : '' }}>Manutenção</option>
                                </select>
                            </div>

                            {{-- ✅ FILTROS DE DATA (Agrupados e com bom espaçamento) --}}
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

                            {{-- Pesquisa de Texto e Botões de Ação (Agrupados) --}}
                            <div class="flex space-x-2 w-full md:w-auto items-end flex-grow md:flex-grow-0">
                                <div class="flex-grow">
                                    <label for="search" class="block text-xs font-semibold text-gray-500 mb-1">Pesquisar:</label>
                                    <input type="text" name="search" id="search" value="{{ $search ?? '' }}"
                                        placeholder="Nome, contato..."
                                        class="px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition duration-150 w-full">
                                </div>

                                <div class="flex items-end space-x-1 h-[42px]">
                                    <button type="submit"
                                                    class="bg-indigo-600 hover:bg-indigo-700 text-white h-full p-2 rounded-lg shadow-md transition duration-150 flex-shrink-0 flex items-center justify-center"
                                                    title="Buscar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" /></svg>
                                    </button>

                                    @if (isset($search) && $search || $startDate || $endDate || $filterStatus)
                                        {{-- Botão Limpar Filtros/Busca (mantém o only_mine) --}}
                                        <a href="{{ route('admin.reservas.todas', ['only_mine' => $isOnlyMine ? 'true' : 'false']) }}"
                                            class="text-red-500 hover:text-red-700 h-full p-2 transition duration-150 flex-shrink-0 flex items-center justify-center rounded-lg border border-red-200"
                                            title="Limpar Busca e Filtros">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>

                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">Data/Hora</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cliente/Reserva</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">Preço</th>
                                {{-- NOVO: Status da Reserva --}}
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Status</th>
                                {{-- FIM NOVO --}}
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Pagamento</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">Criada Por</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($reservas as $reserva)
                                <tr class="odd:bg-white even:bg-gray-50 hover:bg-indigo-50 transition duration-150">

                                    <td class="px-4 py-3 whitespace-nowrap min-w-[120px]">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/y') }}
                                        </div>
                                        <div class="text-indigo-600 text-xs font-semibold">
                                            {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                        </div>
                                        {{-- ✅ INDICADOR DE RECORRÊNCIA/PONTUALIDADE NA TABELA --}}
                                        @if ($reserva->is_recurrent)
                                            <span class="mt-1 inline-block text-[10px] font-bold text-indigo-700 bg-indigo-200 px-1 rounded">
                                                RECORRENTE
                                            </span>
                                        @else
                                            <!-- Condição para Reserva Pontual (Não-recorrente) -->
                                            <span class="mt-1 inline-block text-[10px] font-bold text-blue-700 bg-blue-200 px-1 rounded">
                                                PONTUAL
                                            </span>
                                        @endif
                                        @if ($reserva->is_fixed)
                                             <span class="mt-1 inline-block text-[10px] font-bold text-gray-700 bg-gray-200 px-1 rounded">
                                                 SLOT FIXO
                                             </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-left">
                                        @if ($reserva->is_fixed)
                                             <div class="text-sm font-bold text-gray-700">Slot de Inventário</div>
                                             <div class="text-xs text-gray-500 font-medium">Não é reserva de cliente</div>
                                        @elseif ($reserva->user)
                                            <div class="text-sm font-semibold text-gray-900">{{ $reserva->user->name }}</div>
                                            <div class="text-xs text-green-600 font-medium">Agendamento de Cliente</div>
                                        @else
                                            <div class="text-sm font-bold text-indigo-700">{{ $reserva->client_name ?? 'Cliente (Manual)' }}</div>
                                            <div class="text-xs text-gray-500 font-medium">{{ $reserva->client_contact ?? 'Contato não informado' }}</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap min-w-[90px] text-sm font-bold text-green-700 text-right">
                                        R$ {{ number_format($reserva->price ?? 0, 2, ',', '.') }}
                                    </td>

                                    {{-- CÉLULA DE STATUS DA RESERVA (NOVA) --}}
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        {{-- Usa o acessor status_text do model --}}
                                        <span class="status-badge status-{{ $reserva->status }}">
                                            {{ $reserva->status_text }}
                                        </span>
                                    </td>
                                    {{-- FIM CÉLULA DE STATUS DA RESERVA --}}

                                    {{-- CÉLULA DE STATUS DE PAGAMENTO COM LÓGICA DE ATRASO --}}
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        @php
                                            // Define a cor e o texto baseado no status do pagamento
                                            $status = $reserva->payment_status;
                                            $reservaStatus = $reserva->status; // NOVO: Captura o status da reserva
                                            $badgeClass = '';
                                            $badgeText = '';
                                            $isOverdue = false;

                                            // 1. Prioridade MÁXIMA: Checar se a reserva foi marcada como FALTA (No Show)
                                            if ($reservaStatus === 'no_show') {
                                                $badgeClass = 'bg-red-400 text-white font-bold shadow-xl';
                                                $badgeText = 'NÃO PAGO (Falta)'; // Status desejado pelo usuário
                                            }

                                            // Se não for 'no_show', continua com a lógica normal de pagamento
                                            elseif ($reserva->is_fixed) {
                                                $badgeClass = 'bg-gray-200 text-gray-600';
                                                $badgeText = 'N/A';
                                            } elseif ($status === 'paid' || $status === 'completed') {
                                                $badgeClass = 'bg-green-100 text-green-800';
                                                $badgeText = 'Pago';
                                            } elseif ($status === 'partial') {
                                                $badgeClass = 'bg-yellow-100 text-yellow-800';
                                                $badgeText = 'Parcial (R$' . number_format($reserva->remaining_amount ?? 0, 2, ',', '.') . ' Restantes)';
                                            } else {
                                                // 2. Se for 'pending' ou 'unpaid', checar se está ATRASADO
                                                // 2.1. Criar a string de Data e Hora Corretamente
                                                $dateTimeString = \Carbon\Carbon::parse($reserva->date)->format('Y-m-d') . ' ' . $reserva->end_time;
                                                $reservaEndTime = \Carbon\Carbon::parse($dateTimeString);

                                                // 2.2. Checar se a hora de término da reserva já passou
                                                if ($reservaEndTime->lessThan(\Carbon\Carbon::now())) {
                                                    $isOverdue = true;
                                                }

                                                if ($isOverdue) {
                                                    // Status ATRASADO (com animação)
                                                    $badgeClass = 'bg-red-700 text-white font-bold animate-pulse shadow-xl';
                                                    $badgeText = 'ATRASADO';
                                                } else {
                                                    // Status pendente normal (ainda dentro do horário ou futuro)
                                                    $badgeClass = 'bg-red-100 text-red-800';
                                                    $badgeText = 'Aguardando Pagamento';
                                                }
                                            }
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                            {{ $badgeText }}
                                        </span>
                                    </td>
                                    {{-- FIM CÉLULA DE STATUS DE PAGAMENTO --}}

                                    <td class="px-4 py-3 text-left min-w-[120px]">
                                        @if ($reserva->manager)
                                            <span class="font-medium text-purple-700 bg-purple-100 px-2 py-0.5 text-xs rounded-full whitespace-nowrap shadow-sm">
                                                {{ \Illuminate\Support\Str::limit($reserva->manager->name, 10, '...') }} (Gestor)
                                            </span>
                                        @elseif ($reserva->user)
                                            <span class="text-green-600 bg-green-100 px-2 py-0.5 text-xs rounded-full whitespace-nowrap shadow-sm">
                                                Cliente Web
                                            </span>
                                        @else
                                            <span class="text-gray-600 bg-gray-100 px-2 py-0.5 text-xs rounded-full whitespace-nowrap shadow-sm">
                                                Manual/Fixo
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium min-w-[100px]">
                                        <div class="flex flex-col space-y-1">

                                            <a href="{{ route('admin.reservas.show', $reserva) }}"
                                                class="inline-block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                Detalhes
                                            </a>

                                            {{-- AÇÕES DE REATIVAÇÃO / CANCELAMENTO / PAGAMENTO / FALTA --}}
                                            @if (in_array($reserva->status, ['cancelled', 'rejected', 'no_show'])) {{-- Adiciona 'no_show' aqui --}}
                                                {{-- Permite REATIVAR (somente reservas de cliente) --}}
                                                @if (!$reserva->is_fixed)
                                                <button onclick="openReactivationModal({{ $reserva->id }}, 'Reativar', 'Tem certeza que deseja REATIVAR esta reserva cancelada/rejeitada? O slot será ocupado novamente.', '{{ route('admin.reservas.reativar', ':id') }}')"
                                                        class="inline-block w-full text-center bg-green-500 hover:bg-green-600 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                            Reativar
                                                </button>
                                                @endif

                                            @elseif (in_array($reserva->status, ['confirmed', 'pending']))
                                                {{-- Ações para reservas ATIVAS de cliente --}}
                                                <a href="{{ route('admin.payment.index', [
                                                    'reserva_id' => $reserva->id,
                                                    'data_reserva' => \Carbon\Carbon::parse($reserva->date)->format('Y-m-d'),
                                                    'signal_value' => $reserva->signal_value ?? 0
                                                    ]) }}"
                                                    class="inline-block w-full text-center bg-green-600 hover:bg-green-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                    Lançar no Caixa
                                                </a>

                                                {{-- REMOVIDO: Botão X Falta. A ação só deve ocorrer na tela de caixa. --}}
                                                {{-- O botão 'Lançar no Caixa' leva para lá, onde a ação de Falta está disponível. --}}

                                                @if ($reserva->is_recurrent)
                                                    <button onclick="openCancellationModal({{ $reserva->id }}, 'PATCH', '{{ route('admin.reservas.cancelar_pontual', ':id') }}', 'Cancelar SOMENTE ESTA reserva recorrente. O slot será liberado pontualmente.', 'Cancelar ESTE DIA')"
                                                            class="inline-block w-full text-center bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                            Cancelar ESTE DIA
                                                    </button>
                                                    <button onclick="openCancellationModal({{ $reserva->id }}, 'DELETE', '{{ route('admin.reservas.cancelar_serie', ':id') }}', 'Tem certeza que deseja cancelar TODA A SÉRIE (futura) para este cliente? Todos os horários serão liberados.', 'Cancelar SÉRIE')"
                                                            class="inline-block w-full text-center bg-red-800 hover:bg-red-900 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                            Cancelar SÉRIE
                                                    </button>
                                                @else
                                                    <button onclick="openCancellationModal({{ $reserva->id }}, 'PATCH', '{{ route('admin.reservas.cancelar', ':id') }}', 'Tem certeza que deseja CANCELAR esta reserva PONTUAL? Isso a marcará como cancelada no sistema.', 'Cancelar')"
                                                            class="inline-block w-full text-center bg-red-600 hover:bg-red-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                            Cancelar
                                                    </button>
                                                @endif
                                            @elseif ($reserva->is_fixed)
                                                {{-- Ações para SLOTS FIXOS (Manutenção/Livre) --}}
                                                @if ($reserva->status === 'maintenance')
                                                <button onclick="handleFixedSlotToggle({{ $reserva->id }}, 'confirmed')"
                                                     class="inline-block w-full text-center bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                     Disponibilizar
                                                </button>
                                                @elseif ($reserva->status === 'free')
                                                <button onclick="handleFixedSlotToggle({{ $reserva->id }}, 'cancelled')"
                                                     class="inline-block w-full text-center bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                     Manutenção
                                                </button>
                                                @endif
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 whitespace-nowrap text-center text-base text-gray-500 italic">
                                        Nenhuma reserva encontrada.
                                        @if (isset($search) && $search)
                                            para a busca por "{{ $search }}".
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-8">
                    {{-- Paginação com todos os filtros --}}
                    {{ $reservas->appends(['search' => $search, 'only_mine' => $isOnlyMine ? 'true' : 'false', 'start_date' => $startDate ?? '', 'end_date' => $endDate ?? '', 'filter_status' => $filterStatus ?? ''])->links() }}
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL DE CANCELAMENTO (EXISTENTE) --}}
    <div id="cancellation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-transform duration-300 scale-95 opacity-0" id="cancellation-modal-content" onclick="event.stopPropagation()">
            <h3 id="modal-title" class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Confirmação de Cancelamento</h3>

            <p id="modal-message" class="text-gray-700 mb-4"></p>

            <div class="mb-6">
                <label for="cancellation-reason-input" class="block text-sm font-medium text-gray-700 mb-2">
                    Motivo do Cancelamento:
                </label>
                <textarea id="cancellation-reason-input" rows="3" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-red-500 focus:border-red-500" placeholder="Obrigatório, descreva o motivo do cancelamento (mínimo 5 caracteres)..."></textarea>
            </div>

            <div class="flex justify-end space-x-3">
                <button onclick="closeCancellationModal()" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                    Fechar
                </button>
                <button id="confirm-cancellation-btn" type="button" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">
                    Confirmar Cancelamento
                </button>
            </div>
        </div>
    </div>

    {{-- 🆕 NOVO MODAL DE REGISTRO DE FALTA (NO SHOW) --}}
    <div id="noshow-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-transform duration-300 scale-95 opacity-0" id="noshow-modal-content" onclick="event.stopPropagation()">
            <h3 id="noshow-modal-title" class="text-xl font-bold text-red-700 mb-4 border-b pb-2">Registrar Falta (No Show)</h3>

            <p id="noshow-modal-message" class="text-gray-700 mb-4 font-semibold"></p>

            <div class="p-4 bg-yellow-50 border border-yellow-300 rounded-lg text-sm text-yellow-800 mb-6">
                Ao confirmar a falta, o status da reserva será alterado para **NO SHOW** e o pagamento será marcado como **NÃO PAGO (Falta)**, resolvendo o status **ATRASADO**.
            </div>

            <div class="flex justify-end space-x-3">
                <button onclick="closeNoShowModal()" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                    Cancelar
                </button>
                <button id="confirm-noshow-btn" type="button" class="px-4 py-2 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition duration-150">
                    Confirmar Falta (No Show)
                </button>
            </div>
        </div>
    </div>

    {{-- NOVO MODAL DE REATIVAÇÃO --}}
    <div id="reactivation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 m-4 transform transition-transform duration-300 scale-95 opacity-0" id="reactivation-modal-content" onclick="event.stopPropagation()">
            <h3 id="reactivation-modal-title" class="text-xl font-bold text-green-700 mb-4 border-b pb-2">Confirmação de Reativação</h3>

            <p id="reactivation-modal-message" class="text-gray-700 mb-4 font-semibold"></p>

            <div class="p-4 bg-green-50 border border-green-300 rounded-lg text-sm text-green-800 mb-6">
                A reativação de uma reserva cancelada ou rejeitada a retorna ao status **CONFIRMADO**.
            </div>

            <div class="flex justify-end space-x-3">
                <button onclick="closeReactivationModal()" type="button" class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition duration-150">
                    Fechar
                </button>
                <button id="confirm-reactivation-btn" type="button" class="px-4 py-2 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition duration-150">
                    Reativar Reserva
                </button>
            </div>
        </div>
    </div>

    {{-- SCRIPTS DE AÇÃO AJAX --}}
    <script>
        // Variáveis de Rota e Token
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        const CSRF_TOKEN = metaTag ? metaTag.getAttribute('content') : null;
        // Usamos as rotas do web.php.
        const CANCEL_PONTUAL_URL = '{{ route("admin.reservas.cancelar_pontual", ":id") }}';
        const CANCEL_SERIE_URL = '{{ route("admin.reservas.cancelar_serie", ":id") }}';
        const CANCEL_PADRAO_URL = '{{ route("admin.reservas.cancelar", ":id") }}';
        const REACTIVATE_URL = '{{ route("admin.reservas.reativar", ":id") }}';
        const UPDATE_SLOT_STATUS_URL = '{{ route("admin.config.update_status", ":id") }}';
        // Rota de registro de Falta (No Show)
        const REGISTER_NOSHOW_URL = '{{ route("admin.payment.noshow", ":id") }}';


        let currentReservaId = null;
        let currentMethod = null; // PATCH ou DELETE (Método Lógico)
        let currentUrlBase = null;

        /**
         * Abre o modal de cancelamento e configura os dados da reserva.
         */
        function openCancellationModal(reservaId, method, urlBase, message, buttonText) {
            currentReservaId = reservaId;
            currentMethod = method;
            currentUrlBase = urlBase;
            document.getElementById('cancellation-reason-input').value = ''; // Limpa o campo

            document.getElementById('modal-title').textContent = buttonText;
            document.getElementById('modal-message').textContent = message;
            document.getElementById('cancellation-modal').classList.remove('hidden');
            document.getElementById('cancellation-modal').classList.add('flex');

            // Ativa a transição do modal
            setTimeout(() => {
                document.getElementById('cancellation-modal-content').classList.remove('opacity-0', 'scale-95');
            }, 10);

            document.getElementById('confirm-cancellation-btn').textContent = buttonText;
        }

        /**
         * Fecha o modal de cancelamento.
         */
        function closeCancellationModal() {
            document.getElementById('cancellation-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                document.getElementById('cancellation-modal').classList.remove('flex');
                document.getElementById('cancellation-modal').classList.add('hidden');
            }, 300);
        }

        // --- LÓGICA DE REGISTRO DE FALTA (NO SHOW) ---

        /**
         * Abre o modal de registro de falta.
         */
        function openNoShowModal(reservaId, title, message, urlBase) {
            currentReservaId = reservaId;
            currentMethod = 'POST'; // A ação é um POST ou PATCH, dependendo da sua rota de controller. Usaremos POST para simplicidade.
            currentUrlBase = urlBase;

            document.getElementById('noshow-modal-title').textContent = title;
            document.getElementById('noshow-modal-message').textContent = message;

            document.getElementById('noshow-modal').classList.remove('hidden');
            document.getElementById('noshow-modal').classList.add('flex');

            setTimeout(() => {
                document.getElementById('noshow-modal-content').classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        /**
         * Fecha o modal de registro de falta.
         */
        function closeNoShowModal() {
            document.getElementById('noshow-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                document.getElementById('noshow-modal').classList.remove('flex');
                document.getElementById('noshow-modal').classList.add('hidden');
            }, 300);
        }

        /**
         * Listener para Registro de Falta (No Show)
         */
        document.getElementById('confirm-noshow-btn').addEventListener('click', function() {
            if (currentReservaId) {
                // Ao registrar falta, enviamos o status 'noshow'
                sendAjaxRequest(currentReservaId, 'POST', REGISTER_NOSHOW_URL, null, { status: 'no_show' });
            } else {
                alert("Erro: Dados da reserva para registrar falta não configurados corretamente.");
            }
        });

        // --- LÓGICA DE REATIVAÇÃO ---

        /**
         * Abre o modal de reativação.
         */
        function openReactivationModal(reservaId, title, message, urlBase) {
            currentReservaId = reservaId;
            currentMethod = 'PATCH'; // A reativação é sempre um PATCH no status
            currentUrlBase = urlBase;

            document.getElementById('reactivation-modal-title').textContent = title;
            document.getElementById('reactivation-modal-message').textContent = message;

            document.getElementById('reactivation-modal').classList.remove('hidden');
            document.getElementById('reactivation-modal').classList.add('flex');

            setTimeout(() => {
                document.getElementById('reactivation-modal-content').classList.remove('opacity-0', 'scale-95');
            }, 10);
        }

        /**
         * Fecha o modal de reativação.
         */
        function closeReactivationModal() {
            document.getElementById('reactivation-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                document.getElementById('reactivation-modal').classList.remove('flex');
                document.getElementById('reactivation-modal').classList.add('hidden');
            }, 300);
        }

        /**
         * Listener para Reativação
         */
        document.getElementById('confirm-reactivation-btn').addEventListener('click', function() {
            if (currentReservaId && currentUrlBase) {
                // Ao reativar, enviamos o status 'confirmed' e NÃO precisamos de justificativa.
                sendAjaxRequest(currentReservaId, 'PATCH', currentUrlBase, null, { status: 'confirmed' });
            } else {
                alert("Erro: Dados da reserva para reativação não configurados corretamente.");
            }
        });


        /**
         * FUNÇÃO PARA ALTERNAR STATUS DE SLOT FIXO (Manutenção <-> Livre)
         */
        async function handleFixedSlotToggle(id, targetAction) {
            const actionText = targetAction === 'confirmed' ? 'disponibilizar (Livre)' : 'marcar como indisponível (Manutenção)';
            // Usamos alert() simples aqui, pois não envolve cancelamento de cliente ou falta.
            if (!confirm(`Confirma a ação de ${actionText} o slot ID #${id} no calendário?`)) {
                 return;
            }

            // Usamos a mesma URL de atualização de status do ConfigurationController
            sendAjaxRequest(id, 'POST', UPDATE_SLOT_STATUS_URL, null, { status: targetAction });
        }


        /**
         * FUNÇÃO AJAX GENÉRICA PARA ENVIAR REQUISIÇÕES (Unificada para Cancelamento, Reativação, Falta e Slots Fixos)
         */
        async function sendAjaxRequest(reservaId, method, urlBase, reason = null, extraData = {}) {
            const url = urlBase.replace(':id', reservaId);

            if (!CSRF_TOKEN) {
                alert("Erro de segurança: Token CSRF não encontrado.");
                return;
            }

            // Monta o body da requisição
            const bodyData = {
                cancellation_reason: reason,
                _token: CSRF_TOKEN,
                ...extraData, // Permite injetar status: 'confirmed' ou status: 'noshow'
            };

            // Se o método lógico for PATCH ou DELETE, adicionamos o campo _method
            if (['PATCH', 'DELETE'].includes(method)) {
                 bodyData._method = method;
            }


            // Log de debug
            console.log(`[DEBUG - Todas Reservas] Tentando enviar AJAX (${method}) para: ${url}`);
            console.log("Payload:", bodyData);

            const fetchConfig = {
                method: 'POST', // Transporte HTTP
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(bodyData)
            };

            const submitBtn = document.getElementById('confirm-cancellation-btn') ||
                              document.getElementById('confirm-reactivation-btn') ||
                              document.getElementById('confirm-noshow-btn'); // NOVO BOTÃO DE FALTA

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processando...';
            }


            try {
                const response = await fetch(url, fetchConfig);

                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    const errorText = await response.text();
                    console.error("Falha ao ler JSON de resposta (Pode ser 500 ou HTML).", errorText);
                    result = { error: `Erro do Servidor (${response.status}). Verifique o console.` };
                }

                if (response.ok && result.success) {
                    alert(result.message || "Ação realizada com sucesso. A lista será atualizada.");
                    closeCancellationModal();
                    closeReactivationModal();
                    closeNoShowModal(); // Fecha o modal de falta

                    // ✅ CORREÇÃO: Recarrega a página após o sucesso em qualquer ação AJAX
                    setTimeout(() => {
                        window.location.reload();
                    }, 50);

                } else if (response.status === 422 && result.errors) {
                    // Lidar com erro de validação (Motivo muito curto)
                    const errorField = result.errors.cancellation_reason || result.errors.status;
                    const errorMsg = errorField ? errorField.join(', ') : 'Erro de validação desconhecida.';
                    alert(`ERRO DE VALIDAÇÃO: ${errorMsg}`);
                } else {
                    alert(result.error || result.message || `Erro desconhecido ao processar a ação. Status: ${response.status}.`);
                }

            } catch (error) {
                console.error('Erro de Rede/Comunicação:', error);
                alert("Erro de conexão. Tente novamente.");
            } finally {
                 // Reativa o botão correto no final
                 if (submitBtn) {
                    submitBtn.disabled = false;
                    if (submitBtn.getAttribute('id') === 'confirm-cancellation-btn') {
                        submitBtn.textContent = 'Confirmar Cancelamento';
                    } else if (submitBtn.getAttribute('id') === 'confirm-reactivation-btn') {
                        submitBtn.textContent = 'Reativar Reserva';
                    } else if (submitBtn.getAttribute('id') === 'confirm-noshow-btn') { // NOVO
                         submitBtn.textContent = 'Confirmar Falta (No Show)';
                    }
                }
            }
        }

        // --- Listener de Confirmação do Modal de Cancelamento ---
        document.getElementById('confirm-cancellation-btn').addEventListener('click', function() {
            const reason = document.getElementById('cancellation-reason-input').value.trim();

            if (reason.length < 5) {
                alert("Por favor, forneça um motivo de cancelamento com pelo menos 5 caracteres.");
                return;
            }

            if (currentReservaId && currentMethod && currentUrlBase) {
                // Passamos o método LÓGICO (PATCH/DELETE) e o motivo
                sendAjaxRequest(currentReservaId, currentMethod, currentUrlBase, reason);
            } else {
                alert("Erro: Dados da reserva não configurados corretamente.");
            }
        });

    </script>
</x-app-layout>
