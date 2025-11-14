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

                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 space-y-4 md:space-y-0">

                    <div class="flex space-x-3 p-1 bg-gray-100 rounded-xl shadow-inner">
                        <a href="{{ route('admin.reservas.confirmed_index') }}"
                            class="px-4 py-2 text-sm font-semibold rounded-lg shadow-md transition duration-150
                                @if (!$isOnlyMine)
                                    bg-indigo-600 text-white hover:bg-indigo-700
                                @else
                                    text-indigo-600 hover:bg-white
                                @endif">
                            Todas Confirmadas
                        </a>
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
                                               class="inline-block text-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                Detalhes
                                            </a>

                                            @if ($reserva->is_recurrent)
                                                {{-- ✅ AÇÕES PARA RESERVAS RECORRENTES (DELETE) --}}
                                                <button onclick="cancelarPontualAjax({{ $reserva->id }})"
                                                   class="inline-block w-full text-center bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                    Cancelar ESTE DIA
                                                </button>
                                                <button onclick="cancelarSerieAjax({{ $reserva->id }})"
                                                    class="inline-block w-full text-center bg-red-800 hover:bg-red-900 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                                                    Cancelar SÉRIE
                                                </button>
                                            @else
                                                {{-- ✅ AÇÃO PADRÃO PARA RESERVAS PONTUAIS (PATCH) --}}
                                                <button onclick="cancelarReservaPontualAjax({{ $reserva->id }})"
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
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-8">
                    {{ $reservas->links() }}
                </div>

            </div>
        </div>
    </div>

    {{-- SCRIPTS DE AÇÃO AJAX --}}
    <script>
        // Variáveis de Rota e Token
        const CSRF_TOKEN = document.querySelector('input[name="_token"]').value;
        const CANCEL_PONTUAL_URL = '{{ route("admin.reservas.cancelar_pontual", ":id") }}'; // DELETE (Recorrente: exceção)
        const CANCEL_SERIE_URL = '{{ route("admin.reservas.cancelar_serie", ":id") }}'; // DELETE (Recorrente: série inteira)
        const CANCEL_PADRAO_URL = '{{ route("admin.reservas.cancelar", ":id") }}'; // PATCH (Pontual: status para cancelled)


        /**
         * FUNÇÃO AJAX GENÉRICA PARA ENVIAR REQUISIÇÕES (DELETE/PATCH)
         */
        async function sendAjaxRequest(url, method, confirmationMessage) {
            if (!confirm(confirmationMessage)) {
                return;
            }

            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        // Se for PATCH, o Body deve ser vazio ou incluir o status, mas aqui apenas PATCH já resolve.
                    }
                });

                let result = {};
                try {
                    // Tenta ler o JSON
                    result = await response.json();
                } catch (e) {
                    console.error("Falha ao ler JSON de resposta (Pode ser 500 ou HTML).", e);
                }

                if (response.ok) {
                    alert(result.message || "Ação realizada com sucesso. A lista será atualizada.");

                    // Recarrega a página atual da tabela (após o alert fechar)
                    setTimeout(() => {
                        window.location.reload();
                    }, 50);

                } else {
                    // Trata erros de validação/conflito/servidor
                    alert(result.error || result.message || "Erro desconhecido ao processar a ação.");
                }

            } catch (error) {
                console.error('Erro de Rede/Comunicação:', error);
                alert("Erro de conexão. Tente novamente.");
            }
        }

        // --- FUNÇÕES DE CANCELAMENTO RECORRENTE (DELETE) ---

        function cancelarPontualAjax(reservaId) {
            const url = CANCEL_PONTUAL_URL.replace(':id', reservaId);
            const confirmation = "Tem certeza que deseja cancelar SOMENTE ESTA reserva recorrente? O slot será liberado pontualmente.";
            sendAjaxRequest(url, 'DELETE', confirmation);
        }

        function cancelarSerieAjax(reservaId) {
            const url = CANCEL_SERIE_URL.replace(':id', reservaId);
            const confirmation = "⚠️ ATENÇÃO: Tem certeza que deseja cancelar TODA A SÉRIE (futura) para este cliente? Todos os horários serão liberados.";
            sendAjaxRequest(url, 'DELETE', confirmation);
        }

        // --- FUNÇÃO DE CANCELAMENTO PONTUAL PADRÃO (PATCH) ---

        function cancelarReservaPontualAjax(reservaId) {
            // Rota PATCH /admin/reservas/{reserva}/cancelar que muda o status para 'cancelled'
            const url = CANCEL_PADRAO_URL.replace(':id', reservaId);
            const confirmation = "Tem certeza que deseja CANCELAR esta reserva PONTUAL? Isso a marcará como cancelada no sistema.";

            // Enviamos um PATCH, mas o corpo precisa ser um FormData contendo o status.
            // Para rotas PATCH com status, é mais seguro simular o PATCH com um POST e o método,
            // ou simplificar e apenas usar o PATCH vazio, pois o Controller sabe o que fazer.

            // Como o Controller já sabe que deve mudar o status para 'cancelled' na rota, usamos PATCH.
            sendAjaxRequest(url, 'PATCH', confirmation);
        }

    </script>
</x-app-layout>
