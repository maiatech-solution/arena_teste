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
                    {{-- Usando um layout mais espaçado (gap-4) e flexível --}}
                    <div class="flex flex-col md:flex-row items-end md:items-center space-y-4 md:space-y-0 md:space-x-6 w-full">

                        {{-- Botão de Filtro Rápido --}}
                        <div class="flex space-x-3 p-1 bg-gray-100 rounded-xl shadow-inner flex-shrink-0">
                            {{-- Mantemos apenas o botão "Todas Confirmadas" --}}
                            <a href="{{ route('admin.reservas.confirmadas') }}"
                                class="px-4 py-2 text-sm font-semibold rounded-lg shadow-md transition duration-150
                                    @if (!isset($search) && !$isOnlyMine && !$startDate && !$endDate)
                                        bg-indigo-600 text-white hover:bg-indigo-700
                                    @else
                                        text-indigo-600 hover:bg-white
                                    @endif">
                                Todas Confirmadas
                            </a>
                        </div>

                        {{-- Formulário de Pesquisa e Datas --}}
                        {{-- O formulário é a peça central dos filtros --}}
                        <form method="GET" action="{{ route('admin.reservas.confirmadas') }}" class="flex flex-col md:flex-row items-end md:items-center space-y-4 md:space-y-0 md:space-x-4 w-full">
                            <input type="hidden" name="only_mine" value="{{ $isOnlyMine ? 'true' : 'false' }}">

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

                                    @if (isset($search) && $search || $startDate || $endDate)
                                        {{-- Botão Limpar Filtros/Busca (mantém o only_mine) --}}
                                        <a href="{{ route('admin.reservas.confirmadas', ['only_mine' => $isOnlyMine ? 'true' : 'false']) }}"
                                            class="text-red-500 hover:text-red-700 h-full p-2 transition duration-150 flex-shrink-0 flex items-center justify-center rounded-lg border border-red-200"
                                            title="Limpar Busca e Filtros de Data">
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
                                        {{-- ✅ INDICADOR DE RECORRÊNCIA NA TABELA --}}
                                        @if ($reserva->is_recurrent)
                                            <span class="mt-1 inline-block text-[10px] font-bold text-indigo-700 bg-indigo-200 px-1 rounded">
                                                RECORRENTE
                                            </span>
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

                                    <td class="px-4 py-3 whitespace-nowrap min-w-[90px] text-sm font-bold text-green-700 text-right">
                                        R$ {{ number_format($reserva->price ?? 0, 2, ',', '.') }}
                                    </td>

                                    <td class="px-4 py-3 text-left min-w-[120px]">
                                        @if ($reserva->manager)
                                            <span class="font-medium text-purple-700 bg-purple-100 px-2 py-0.5 text-xs rounded-full whitespace-nowrap shadow-sm">
                                                {{ \Illuminate\Support\Str::limit($reserva->manager->name, 10, '...') }} (Gestor)
                                            </span>
                                        @else
                                            <span class="text-gray-600 bg-gray-100 px-2 py-0.5 text-xs rounded-full whitespace-nowrap shadow-sm">
                                                Cliente via Web
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium min-w-[100px]">
                                        <div class="flex flex-col space-y-1">

                                            <a href="{{ route('admin.reservas.show', $reserva) }}"
                                               class="inline-block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                Detalhes
                                            </a>

                                            @if ($reserva->is_recurrent)
                                                {{-- ✅ AÇÕES PARA RESERVAS RECORRENTES (DELETE INTERNO) --}}
                                                {{-- CANCELAR PONTUAL DA SÉRIE --}}
                                                <button onclick="openCancellationModal({{ $reserva->id }}, 'PATCH', '{{ route('admin.reservas.cancelar_pontual', ':id') }}', 'Cancelar SOMENTE ESTA reserva recorrente. O slot será liberado pontualmente.', 'Cancelar ESTE DIA')"
                                                   class="inline-block w-full text-center bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                     Cancelar ESTE DIA
                                                </button>
                                                {{-- CANCELAR SÉRIE INTEIRA --}}
                                                <button onclick="openCancellationModal({{ $reserva->id }}, 'DELETE', '{{ route('admin.reservas.cancelar_serie', ':id') }}', 'Tem certeza que deseja cancelar TODA A SÉRIE (futura) para este cliente? Todos os horários serão liberados.', 'Cancelar SÉRIE')"
                                                     class="inline-block w-full text-center bg-red-800 hover:bg-red-900 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                     Cancelar SÉRIE
                                                </button>
                                            @else
                                                {{-- ✅ AÇÃO PADRÃO PARA RESERVAS PONTUAIS (PATCH INTERNO) --}}
                                                <button onclick="openCancellationModal({{ $reserva->id }}, 'PATCH', '{{ route('admin.reservas.cancelar', ':id') }}', 'Tem certeza que deseja CANCELAR esta reserva PONTUAL? Isso a marcará como cancelada no sistema.', 'Cancelar')"
                                                     class="inline-block w-full text-center bg-red-600 hover:bg-red-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                     Cancelar
                                                </button>
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 whitespace-nowrap text-center text-base text-gray-500 italic">
                                        Nenhuma reserva confirmada encontrada
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
                    {{-- ✅ ATUALIZADO: Inclui filtros de data, busca e only_mine na paginação --}}
                    {{ $reservas->appends(['search' => $search, 'only_mine' => $isOnlyMine ? 'true' : 'false', 'start_date' => $startDate ?? '', 'end_date' => $endDate ?? ''])->links() }}
                </div>

            </div>
        </div>
    </div>

    {{-- MODAL DE CANCELAMENTO (Escondido por padrão) --}}
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


    {{-- SCRIPTS DE AÇÃO AJAX --}}
    <script>
        // Variáveis de Rota e Token
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        // Usamos as rotas do web.php.
        const CANCEL_PONTUAL_URL = '{{ route("admin.reservas.cancelar_pontual", ":id") }}';
        const CANCEL_SERIE_URL = '{{ route("admin.reservas.cancelar_serie", ":id") }}';
        const CANCEL_PADRAO_URL = '{{ route("admin.reservas.cancelar", ":id") }}';

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
         * Fecha o modal.
         */
        function closeCancellationModal() {
            document.getElementById('cancellation-modal-content').classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                document.getElementById('cancellation-modal').classList.remove('flex');
                document.getElementById('cancellation-modal').classList.add('hidden');
            }, 300);
        }

        /**
         * FUNÇÃO AJAX GENÉRICA PARA ENVIAR REQUISIÇÕES
         */
        async function sendAjaxRequest(reservaId, method, urlBase, reason) {
            const url = urlBase.replace(':id', reservaId);

            // Monta o body da requisição
            const bodyData = {
                cancellation_reason: reason,
                _token: CSRF_TOKEN,
                // CRÍTICO: Incluímos o _method para que o Laravel Route Model Binding
                // use o método HTTP correto (PATCH ou DELETE), mesmo que o transporte seja POST.
                _method: method,
            };

            // Log de debug para rastrear a URL
            console.log(`[DEBUG - Confirmed Index] Tentando enviar AJAX (POST com _method=${method}) para: ${url}`);

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

            const submitBtn = document.getElementById('confirm-cancellation-btn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processando...';

            try {
                const response = await fetch(url, fetchConfig);

                // Trata a resposta JSON, incluindo erros de validação Laravel 422
                let result = {};
                try {
                    result = await response.json();
                } catch (e) {
                    const errorText = await response.text();
                    console.error("Falha ao ler JSON de resposta (Pode ser 500 ou HTML).", errorText);
                    result = { error: `Erro do Servidor (${response.status}). Verifique o console.` };
                }

                if (response.ok) {
                    alert(result.message || "Ação realizada com sucesso. A lista será atualizada.");
                    closeCancellationModal();

                    // Recarrega a página após uma breve pausa para o usuário ver o alert
                    setTimeout(() => {
                        window.location.reload();
                    }, 50);

                } else if (response.status === 422 && result.errors) {
                     // Lidar com erro de validação (Motivo muito curto)
                     const reasonError = result.errors.cancellation_reason ? result.errors.cancellation_reason.join(', ') : 'Erro de validação desconhecida.';
                     alert(`ERRO DE VALIDAÇÃO: ${reasonError}`);
                } else {
                    alert(result.error || result.message || `Erro desconhecido ao processar a ação. Status: ${response.status}.`);
                }

            } catch (error) {
                console.error('Erro de Rede/Comunicação:', error);
                alert("Erro de conexão. Tente novamente.");
            } finally {
                 document.getElementById('confirm-cancellation-btn').disabled = false;
                 submitBtn.textContent = 'Confirmar Cancelamento';
            }
        }

        // --- Listener de Confirmação do Modal ---
        document.getElementById('confirm-cancellation-btn').addEventListener('click', function() {
            const reason = document.getElementById('cancellation-reason-input').value.trim();

            // Validação mínima no Front-end (o back-end fará a validação final)
            if (reason.length < 5) {
                alert("Por favor, forneça um motivo de cancelamento com pelo menos 5 caracteres.");
                return;
            }

            if (currentReservaId && currentMethod && currentUrlBase) {
                // Passamos o método LÓGICO (PATCH/DELETE) para a função AJAX
                sendAjaxRequest(currentReservaId, currentMethod, currentUrlBase, reason);
            } else {
                alert("Erro: Dados da reserva não configurados corretamente.");
            }
        });

    </script>
</x-app-layout>
