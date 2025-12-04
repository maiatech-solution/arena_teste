<x-app-layout>
<x-slot name="header">
<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
💰 Gerenciamento de Caixa & Pagamentos
</h2>
</x-slot>

<div class="py-8">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- 1. BARRA DE FILTRO E KPIS (Grid 4 colunas) --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

            {{-- Card de Filtro de Data --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4 flex flex-col justify-center border border-gray-200 dark:border-gray-700">
                <form method="GET" action="{{ route('admin.payment.index') }}">
                    {{-- Preserva o termo de pesquisa ao trocar a data --}}
                    <input type="hidden" name="search" value="{{ request('search') }}">

                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filtrar Data:</label>
                    <div class="flex gap-2">
                        <input type="date" name="date" id="date" value="{{ $selectedDate }}"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white"
                            onchange="this.form.submit()">

                        {{-- Se estiver filtrando uma reserva específica, adicionamos um botão de reset --}}
                        @if(request()->has('reserva_id'))
                            <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'search' => request('search')]) }}"
                                class="px-2 py-1 flex items-center justify-center text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400"
                                title="Mostrar todas as reservas do dia">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            {{-- KPI: Total Recebido (Caixa Real) --}}
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                <div class="text-sm font-medium text-green-600 dark:text-green-400">Recebido Hoje (Caixa)</div>
                <div class="mt-1 text-2xl font-bold text-green-700 dark:text-green-300">
                    R$ {{ number_format($totalReceived, 2, ',', '.') }}
                </div>
            </div>

            {{-- KPI: Pendente (A Receber das Reservas de Hoje) --}}
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                <div class="text-sm font-medium text-yellow-600 dark:text-yellow-400">Pendente (A Receber)</div>
                <div class="mt-1 text-2xl font-bold text-yellow-700 dark:text-yellow-300">
                    R$ {{ number_format($totalPending, 2, ',', '.') }}
                </div>
                <div class="text-xs text-gray-500">De um total previsto de R$ {{ number_format($totalExpected, 2, ',', '.') }}</div>
            </div>

            {{-- KPI: Faltas --}}
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                <div class="text-sm font-medium text-red-600 dark:text-red-400">Faltas (No-Show)</div>
                <div class="mt-1 text-2xl font-bold text-red-700 dark:text-red-300">
                    {{ $noShowCount }}
                </div>
            </div>
        </div>

        {{-- 1.5. BARRA DE PESQUISA (Nome/WhatsApp) --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
            <form method="GET" action="{{ route('admin.payment.index') }}">
                {{-- Preserva a data ao fazer a pesquisa --}}
                <input type="hidden" name="date" value="{{ $selectedDate }}">

                <div class="flex items-end gap-3">
                    <div class="flex-grow">
                        <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar Cliente (Nome ou WhatsApp):</label>
                        <input type="text" name="search" id="search" value="{{ request('search') }}"
                            placeholder="Digite o nome ou WhatsApp do cliente..."
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                    </div>
                    <button type="submit" class="h-10 px-4 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                    @if(request()->has('search'))
                        <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'reserva_id' => request('reserva_id')]) }}"
                            class="h-10 px-2 py-1 flex items-center justify-center text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-gray-50 dark:bg-gray-700 transition duration-150"
                            title="Limpar pesquisa">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>


        {{-- 2. TABELA DE RESERVAS --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h3 class="text-lg font-semibold mb-4 flex items-center justify-between">
                    @if(request()->has('reserva_id'))
                        <span class="text-indigo-500">Reserva Selecionada (ID: {{ request('reserva_id') }})</span>
                    @else
                        Agendamentos do Dia ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                    @endif

                    {{-- Botão Voltar para a visão diária, se estiver no filtro de ID --}}
                    @if(request()->has('reserva_id'))
                        <a href="{{ route('admin.payment.index', ['date' => $selectedDate, 'search' => request('search')]) }}"
                            class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Ver Todas do Dia
                        </a>
                    @endif
                </h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Horário</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status Fin.</th>
                                {{-- ✅ NOVO: Coluna Tipo --}}
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total (R$)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sinal (R$)</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Restante</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($reservas as $reserva)
                                @php
                                    // Cálculos Visuais
                                    $total = $reserva->final_price ?? $reserva->price;
                                    $pago = $reserva->total_paid;
                                    $restante = max(0, $total - $pago);
                                    $currentStatus = $reserva->payment_status;
                                    $isOverdue = false;

                                    // LÓGICA DE DETECÇÃO DE ATRASO (Implementação da melhoria)
                                    if (in_array($currentStatus, ['pending', 'unpaid'])) {
                                        // 1. Corrigir a concatenação da data/hora para evitar o erro de formatação
                                        $dateTimeString = \Carbon\Carbon::parse($reserva->date)->format('Y-m-d') . ' ' . $reserva->end_time;
                                        $reservaEndTime = \Carbon\Carbon::parse($dateTimeString);

                                        // 2. Checar se a hora de término da reserva já passou
                                        if ($reservaEndTime->lessThan(\Carbon\Carbon::now())) {
                                            $isOverdue = true;
                                            // O status real do DB continua sendo 'pending' ou 'unpaid', mas mudamos a visualização
                                        }
                                    }

                                    // Cor da Linha / Status
                                    $statusClass = '';
                                    $statusLabel = '';

                                    if ($reserva->status === 'no_show') {
                                        // 🎯 NOVO STATUS: FALTA / NO-SHOW
                                        $statusClass = 'bg-red-500 text-white font-bold';
                                        $statusLabel = 'FALTA';
                                    } elseif ($currentStatus === 'paid' || $currentStatus === 'completed') {
                                        $statusClass = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
                                        $statusLabel = 'Pago';
                                    } elseif ($currentStatus === 'partial') {
                                        $statusClass = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
                                        $statusLabel = 'Parcial';
                                    } elseif ($isOverdue) {
                                        // NOVO STATUS: ATRASADO (vermelho pulsante)
                                        $statusClass = 'bg-red-700 text-white font-bold animate-pulse shadow-xl';
                                        $statusLabel = 'ATRASADO';
                                    } else {
                                        // Pendente/Unpaid normal (ainda futuro ou dentro do horário)
                                        $statusClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                        $statusLabel = 'Pendente';
                                    }

                                    // Destaque para a linha quando vier do dashboard
                                    $rowHighlight = (isset($highlightReservaId) && $reserva->id == $highlightReservaId)
                                        ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500'
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-700';
                                @endphp
                                <tr class="{{ $rowHighlight }} transition">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-bold">
                                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $reserva->client_name }} (ID: {{ $reserva->id }})</div>
                                        <div class="text-xs text-gray-500">
                                            @if($reserva->user && $reserva->user->is_vip)
                                                <span class="text-indigo-600 font-bold">★ VIP</span>
                                            @endif
                                            {{ $reserva->client_contact }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        {{-- APLICANDO A CLASSE E O TEXTO DO NOVO STATUS --}}
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    {{-- ✅ NOVO: Célula Tipo --}}
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        @if ($reserva->is_recurrent)
                                            <span class="font-semibold text-fuchsia-600">Recorrente</span>
                                        @else
                                            Pontual
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right">
                                        {{ number_format($total, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">
                                        {{ number_format($pago, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold {{ $restante > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                        {{ number_format($restante, 2, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        @if($reserva->payment_status !== 'paid' && $reserva->status !== 'no_show')
                                            {{-- Botão Pagar: Adicionado $pago como 4º argumento E is_recurrent como 6º --}}
                                            <button onclick="openPaymentModal({{ $reserva->id }}, {{ $total }}, {{ $restante }}, {{ $pago }}, '{{ $reserva->client_name }}', {{ $reserva->is_recurrent ? 'true' : 'false' }})"
                                                class="text-white bg-green-600 hover:bg-green-700 rounded px-3 py-1 text-xs mr-2 transition duration-150">
                                                $ Baixar
                                            </button>

                                            {{-- Botão Falta: AGORA passando o valor total pago ($pago) --}}
                                            <button onclick="openNoShowModal({{ $reserva->id }}, '{{ $reserva->client_name }}', {{ $pago }})"
                                                class="text-white bg-red-600 hover:bg-red-700 rounded px-3 py-1 text-xs transition duration-150">
                                                X Falta
                                            </button>
                                        @elseif($reserva->status === 'no_show')
                                            <span class="text-xs text-red-500 italic font-medium">Falta Registrada</span>
                                        @else
                                            <span class="text-xs text-green-500 italic font-medium">Concluído</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    {{-- Colspan ajustado de 7 para 8 --}}
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                        Nenhum agendamento encontrado para esta data ou termo de pesquisa.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- 3. LINK DISCRETO PARA DASHBOARD NO FINAL DA PÁGINA --}}
        <div class="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <a href="{{ route('admin.financeiro.dashboard') }}"
                class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 transition duration-150">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-8v8m-4-8v8M4 16h16a2 2 0 002-2V8a2 2 0 00-2-2H4a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                Ir para Relatórios
            </a>
        </div>

    </div>
</div>

{{-- ================================================================== --}}
{{-- MODAL 1: FINALIZAR PAGAMENTO (CORRIGIDO: Estrutura Otimizada) --}}
{{-- ================================================================== --}}
{{-- A classe 'flex items-center justify-center p-4' no contêiner 'fixed' garante a centralização --}}
<div id="paymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">

    {{-- 1. Overlay (Fixed para cobrir a tela) --}}
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closePaymentModal()"></div>

    {{-- 2. Modal Box (O conteúdo real, que é centralizado pelo pai flex) --}}
    <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg w-full">
        <form id="paymentForm">
            @csrf
            <!-- ID da Reserva Injetado via JS -->
            <input type="hidden" name="reserva_id" id="modalReservaId">

            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                    Finalizar Pagamento
                </h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Cliente: <span id="modalClientName" class="font-bold"></span>
                    </p>

                    {{-- Valor Final (Editável para Desconto) --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Total Acordado (R$)</label>
                        {{-- Adicionada a classe js-recalculate para o listener JS --}}
                        <input type="number" step="0.01" name="final_price" id="modalFinalPrice"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white js-recalculate">
                        <p class="text-xs text-gray-500 mt-1">Edite este valor apenas se for aplicar um desconto no total.</p>
                    </div>

                    {{-- NOVO BLOCO: Opção Recorrente (Visibilidade controlada por JS) --}}
                    <div id="recurrentOption" class="mb-4 hidden p-3 border border-indigo-200 dark:border-indigo-600 rounded-lg bg-indigo-50 dark:bg-indigo-900/30">
                        <div class="flex items-center">
                            {{-- O valor "1" aqui é ignorado no JS, pois estamos enviando um BOOLEAN na requisição AJAX --}}
                            <input id="apply_to_series" name="apply_to_series" type="checkbox" value="1" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                            <label for="apply_to_series" class="ml-2 block text-sm font-medium text-gray-900 dark:text-gray-300">
                                Aplicar este valor (R$ <span id="currentNewPrice" class="font-bold">0,00</span>) a TODAS as reservas futuras desta série.
                            </label>
                        </div>
                        <p class="text-xs text-indigo-700 dark:text-indigo-400 mt-1 pl-6">
                            Se desmarcado, o desconto/ajuste se aplicará apenas a esta reserva.
                        </p>
                    </div>

                    {{-- Valor a Pagar Agora (Restante, calculado) --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor Recebido Agora (R$)</label>
                        {{-- O campo amount_paid será preenchido automaticamente, mas é mantido como input para permitir ajuste fino --}}
                        <input type="number" step="0.01" name="amount_paid" id="modalAmountPaid" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white font-bold text-lg">
                        {{-- NOVO: Mensagem de Troco --}}
                        <div id="trocoMessage" class="text-yellow-600 dark:text-yellow-400 text-sm mt-1 hidden font-semibold"></div>
                    </div>

                    {{-- 🎯 SINAL JÁ PAGO (Display) --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sinal Recebido (R$)</label>
                        <span id="modalSignalAmount" class="text-xl font-extrabold text-indigo-900 dark:text-indigo-200 mt-1 block">R$ 0,00</span>
                        {{-- NOVO: Campo escondido para armazenar o valor FLOAT do sinal --}}
                        <input type="hidden" id="modalSignalAmountRaw" value="0.00">
                    </div>

                    {{-- Método de Pagamento (Forma de Pagamento) --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Forma de Pagamento</label>
                        <select name="payment_method" id="modalPaymentMethod" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:text-white">
                            <option value="" disabled selected>Selecione a forma</option>
                            <option value="pix">Pix</option>
                            <option value="money">Dinheiro</option>
                            <option value="credit_card">Cartão de Crédito</option>
                            <option value="debit_card">Cartão de Débito</option>
                            <option value="transfer">Transferência</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                </div>

                {{-- Placeholder para Erros AJAX --}}
                <div id="payment-error-message" class="text-red-500 text-sm mt-3 hidden"></div>

            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" id="submitPaymentBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                    <span id="submitPaymentText">Confirmar Recebimento</span>
                    <svg id="submitPaymentSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                <button type="button" onclick="closePaymentModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ================================================================== --}}
{{-- MODAL 2: REGISTRAR FALTA (NO-SHOW) (ATUALIZADO com Estorno/Retenção) --}}
{{-- ================================================================== --}}
{{-- A classe 'flex items-center justify-center p-4' no contêiner 'fixed' garante a centralização --}}
<div id="noShowModal" class="fixed inset-0 z-50 hidden overflow-y-auto flex items-center justify-center p-4">

    {{-- 1. Overlay (Fixed para cobrir a tela) --}}
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeNoShowModal()"></div>

    {{-- 2. Modal Box (O conteúdo real, que é centralizado pelo pai flex) --}}
    <div class="relative bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg w-full">
        <form id="noShowForm">
            @csrf
            <input type="hidden" name="reserva_id" id="noShowReservaId">
            {{-- NOVO: Campo escondido para o valor pago --}}
            <input type="hidden" name="paid_amount" id="noShowPaidAmount">

            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-red-600 dark:text-red-400" id="modal-title">
                    Registrar Falta (No-Show)
                </h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Você está registrando que <span id="noShowClientName" class="font-bold"></span> faltou ao horário agendado.
                    </p>

                    {{-- 🎯 BLOCO CRÍTICO: GESTÃO FINANCEIRA DE FALTA --}}
                    <div id="financialNoShowBlock" class="p-4 mb-4 border border-red-300 rounded-lg bg-red-50 dark:bg-red-900/30">
                        <div class="text-sm font-semibold text-red-700 dark:text-red-300 mb-2">
                            Gestão de Pagamento <span id="noShowAmountDisplay" class="font-extrabold">R$ 0,00</span>
                        </div>
                        <p id="noShowInitialWarning" class="text-xs text-gray-700 dark:text-gray-400 mb-3"></p>

                        {{-- Opção de Estorno/Retenção --}}
                        <div id="refundControls" class="mt-2">
                            <label for="should_refund" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ação sobre o valor pago:</label>
                            <select name="should_refund" id="should_refund" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:text-white">
                                <option value="false" selected>Reter o valor pago (Sem estorno)</option>
                                <option value="true">Estornar/Devolver o valor pago (Saída de caixa)</option>
                            </select>
                        </div>
                    </div>
                    {{-- FIM DO BLOCO CRÍTICO --}}

                    {{-- Bloquear Cliente --}}
                    <div class="flex items-center mb-4">
                        <input id="block_user" name="block_user" type="checkbox" value="1" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600">
                        <label for="block_user" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                            Adicionar cliente à Lista Negra (Bloquear)
                        </label>
                    </div>

                    {{-- Motivo da Falta --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo da Falta (Obrigatório)</label>
                        <textarea name="no_show_reason" rows="2" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:bg-gray-700 dark:text-white" placeholder="Detalhes do no-show..."></textarea>
                    </div>
                </div>
                {{-- Placeholder para Erros AJAX --}}
                <div id="noshow-error-message" class="text-red-500 text-sm mt-3 hidden"></div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" id="submitNoShowBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                         <span id="submitNoShowText">Confirmar Falta</span>
                                     <svg id="submitNoShowSpinner" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                         <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                         <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                     </svg>
                </button>
                <button type="button" onclick="closeNoShowModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- SCRIPT PARA MODAIS --}}
<script>
    // Função customizada para mostrar mensagem (substituindo o alert)
    function showMessage(message, isSuccess = true) {
        // Usamos a mesma lógica de notificação flash para o Laravel, mas apenas no console aqui.
        // O redirecionamento tratará o feedback visual na próxima página.
        console.log(isSuccess ? 'SUCESSO: ' : 'ERRO: ', message);
    }

    /**
     * Atualiza o valor do novo preço no texto da checkbox de recorrência.
     */
    function updateRecurrentTogglePrice(newPrice) {
        const currentNewPriceEl = document.getElementById('currentNewPrice');
        const newPriceFloat = parseFloat(newPrice) || 0;

        // Exibe o preço atual do desconto no texto do checkbox
        currentNewPriceEl.innerText = newPriceFloat.toFixed(2).replace('.', ',');
    }


    /**
     * Calcula o valor restante a ser pago AGORA (Valor Total Acordado - Sinal Recebido).
     * Esta função só lida com o cálculo automático na abertura/desconto.
     */
    function calculateAmountDue() {
        const finalPriceEl = document.getElementById('modalFinalPrice');
        const signalRawEl = document.getElementById('modalSignalAmountRaw');
        const amountPaidEl = document.getElementById('modalAmountPaid');
        const trocoMessageEl = document.getElementById('trocoMessage');

        // 1. Limpar estados de sobrepagamento
        trocoMessageEl.classList.add('hidden');
        amountPaidEl.classList.remove('focus:border-yellow-500');
        amountPaidEl.classList.add('focus:border-green-500');


        // 2. Converter valores para float
        const finalPrice = parseFloat(finalPriceEl.value) || 0;
        const signalAmount = parseFloat(signalRawEl.value) || 0;

        // 3. Calcular o restante a ser pago (ou o troco gerado por desconto)
        let remainingOrChange = finalPrice - signalAmount;

        // NOVO: Atualiza o preço no toggle recorrente
        updateRecurrentTogglePrice(finalPrice);


        // 4. Se o restante for negativo, é troco devido a desconto (Situação 1)
        if (remainingOrChange < 0) {
            const trocoAmount = Math.abs(remainingOrChange);

            // Define o valor a ser recebido AGORA como 0.00
            amountPaidEl.value = (0).toFixed(2);

            // Exibir mensagem de troco
            trocoMessageEl.textContent = `🚨 ATENÇÃO: Troco a Devolver: R$ ${trocoAmount.toFixed(2).replace('.', ',')}`;
            trocoMessageEl.classList.remove('hidden');

            // Destaque visual
            amountPaidEl.classList.remove('focus:border-green-500');
            amountPaidEl.classList.add('focus:border-yellow-500');

        } else {
            // Caso normal: há valor restante a ser pago. Define o valor padrão para o input.
            amountPaidEl.value = remainingOrChange.toFixed(2);
            // Chama o checkManualOverpayment para garantir que a cor e mensagem estejam corretas após o set
            checkManualOverpayment();
        }
    }

    /**
     * Verifica se o valor digitado manualmente no campo 'Valor Recebido Agora'
     * causa um sobrepagamento e exibe o troco. (Situação 2)
     */
    function checkManualOverpayment() {
        const finalPrice = parseFloat(document.getElementById('modalFinalPrice').value) || 0;
        const signalAmount = parseFloat(document.getElementById('modalSignalAmountRaw').value) || 0;
        const amountPaidNow = parseFloat(document.getElementById('modalAmountPaid').value) || 0;

        const amountPaidEl = document.getElementById('modalAmountPaid');
        const trocoMessageEl = document.getElementById('trocoMessage');

        // NOVO: Atualiza o preço no toggle recorrente (caso o desconto tenha sido o input)
        updateRecurrentTogglePrice(finalPrice);

        // Total já recebido (Sinal) + Total a ser recebido AGORA (Input Manual)
        const totalReceived = signalAmount + amountPaidNow;

        // 1. Calcular o sobrepagamento (Troco)
        let overpayment = totalReceived - finalPrice;

        // 2. Limpa estados
        trocoMessageEl.classList.add('hidden');
        amountPaidEl.classList.remove('focus:border-yellow-500');
        amountPaidEl.classList.add('focus:border-green-500');

        if (overpayment > 0.005) { // Usamos margem de 0.005 para lidar com erros de ponto flutuante

            // Exibir mensagem de troco
            trocoMessageEl.textContent = `🚨 ATENÇÃO: Troco a Devolver: R$ ${overpayment.toFixed(2).replace('.', ',')}`;
            trocoMessageEl.classList.remove('hidden');

            // Destaque visual
            amountPaidEl.classList.remove('focus:border-green-500');
            amountPaidEl.classList.add('focus:border-yellow-500');
        } else {
            // Se o valor digitado for menor ou igual ao devido (ou se o troco foi gerado pelo desconto)
            // Se a dívida era 0 (devido a desconto), não queremos limpar o troco do desconto.
            if (finalPrice - signalAmount < 0) {
                 calculateAmountDue(); // Recalcula para reexibir a mensagem de troco por desconto, se aplicável
            }
        }
    }


    // --- Lógica do Pagamento ---
    function openPaymentModal(id, totalPrice, remaining, signalAmount, clientName, isRecurrent = false) { // <-- Recebe a flag isRecurrent

        // 1. Popula dados e IDs
        document.getElementById('modalReservaId').value = id;
        document.getElementById('modalClientName').innerText = clientName;

        const formattedSignal = signalAmount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        document.getElementById('modalSignalAmount').innerText = formattedSignal;

        // NOVO: Armazenar o valor float (sem formatação) no campo hidden para cálculos
        document.getElementById('modalSignalAmountRaw').value = signalAmount.toFixed(2);

        // 2. Popula os valores
        document.getElementById('modalFinalPrice').value = totalPrice.toFixed(2);

        // NOVO: Popula o Recurrent Toggle
        const recurrentOptionEl = document.getElementById('recurrentOption');
        if (isRecurrent) {
            recurrentOptionEl.classList.remove('hidden');
            document.getElementById('apply_to_series').checked = true; // Padrão: aplica se for recorrente
        } else {
            recurrentOptionEl.classList.add('hidden');
            document.getElementById('apply_to_series').checked = false; // Garante que esteja desmarcado/invisível
        }


        // 3. Executar o cálculo inicial (que agora lida com o troco gerado por desconto)
        calculateAmountDue();

        // 4. Limpa estados
        document.getElementById('payment-error-message').textContent = '';
        document.getElementById('payment-error-message').classList.add('hidden');
        document.getElementById('modalPaymentMethod').value = ''; // Resetar seleção de método

        // 5. Exibe o modal
        document.getElementById('paymentModal').classList.remove('hidden');
        document.getElementById('paymentModal').classList.add('flex');
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
        document.getElementById('paymentModal').classList.remove('flex');
    }

    // Handle Payment Submit via AJAX
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const reservaId = document.getElementById('modalReservaId').value;
        // *** CORREÇÃO: Garante o PONTO para o back-end ***
        const finalPrice = parseFloat(document.getElementById('modalFinalPrice').value).toFixed(2);
        const amountPaid = parseFloat(document.getElementById('modalAmountPaid').value).toFixed(2);
        const paymentMethod = document.getElementById('modalPaymentMethod').value;

        // NOVO: Pega o estado da checkbox de série.
        const recurrentOptionEl = document.getElementById('recurrentOption');
        const isRecurrentVisible = !recurrentOptionEl.classList.contains('hidden');

        // *** CORREÇÃO CRÍTICA AQUI: Enviar como BOOLEAN JS (true/false) ***
        let applyToSeries = false;
        if (isRecurrentVisible) {
            applyToSeries = document.getElementById('apply_to_series').checked; // true ou false
        }

        const csrfToken = document.querySelector('input[name="_token"]').value;

        const submitBtn = document.getElementById('submitPaymentBtn');
        const submitText = document.getElementById('submitPaymentText');
        const submitSpinner = document.getElementById('submitPaymentSpinner');
        const errorMessageDiv = document.getElementById('payment-error-message');

        // 1. Validação simples
        if (paymentMethod === '') {
            errorMessageDiv.textContent = 'Por favor, selecione a Forma de Pagamento.';
            errorMessageDiv.classList.remove('hidden');
            return;
        }

        // 2. Estado de Carregamento
        submitBtn.disabled = true;
        submitText.classList.add('hidden');
        submitSpinner.classList.remove('hidden');
        errorMessageDiv.classList.add('hidden');

        // MONTANDO O PAYLOAD
        const payload = {
            reserva_id: reservaId,
            final_price: finalPrice, // Garante que seja string com ponto (e.g., "150.00")
            amount_paid: amountPaid, // Garante que seja string com ponto
            payment_method: paymentMethod,
            apply_to_series: applyToSeries // <-- AGORA É true ou false (JSON BOOL)
        };

        // LOG CRÍTICO PARA DEBUG NO BROWSER
        console.log("AJAX Payload Enviado:", payload);

        // 3. Envio AJAX (Assumindo a rota POST /admin/pagamentos/{reserva}/finalizar)
        fetch(`/admin/pagamentos/${reservaId}/finalizar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                // Se houver erro (incluindo validação 422), tenta ler a mensagem de erro
                return response.json().then(data => {
                    throw new Error(data.message || 'Erro de validação ou processamento no servidor.');
                });
            }
            return response.json();
        })
        .then(data => {
            if(data.success) {
                showMessage('Pagamento registrado com sucesso!');
                location.reload();
            } else {
                errorMessageDiv.textContent = data.message || 'Erro ao processar pagamento.';
                errorMessageDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            errorMessageDiv.textContent = 'Erro ao processar pagamento: ' + error.message;
            errorMessageDiv.classList.remove('hidden');
        })
        .finally(() => {
            // 4. Resetar Estado
            submitBtn.disabled = false;
            submitText.classList.remove('hidden');
            submitSpinner.classList.add('hidden');
        });
    });

    // --- Listener de Recálculo ---
    document.addEventListener('DOMContentLoaded', () => {
        // Recalcula o restante quando o preço final for alterado (para descontos - Situação 1)
        document.getElementById('modalFinalPrice').addEventListener('input', calculateAmountDue);

        // Verifica o sobrepagamento quando o valor a ser pago for alterado (overpayment manual - Situação 2)
        document.getElementById('modalAmountPaid').addEventListener('input', checkManualOverpayment);


        // --- Destaque de Linha (após o reload) ---
        const urlParams = new URLSearchParams(window.location.search);
        const reservaId = urlParams.get('reserva_id');

        if (reservaId) {
            const highlightedRow = document.querySelector(`.bg-indigo-50`);
            if (highlightedRow) {
                highlightedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });

    // --- Lógica do No-Show ---
    // ATUALIZADA para receber o valor pago
    function openNoShowModal(id, clientName, paidAmount) {
        document.getElementById('noShowReservaId').value = id;
        document.getElementById('noShowClientName').innerText = clientName;

        // NOVO: Define o valor pago nos campos hidden e display
        document.getElementById('noShowPaidAmount').value = paidAmount.toFixed(2);
        const paidAmountFormatted = paidAmount.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        document.getElementById('noShowAmountDisplay').innerText = paidAmountFormatted;

        // NOVO: Atualiza a mensagem de aviso inicial
        const initialWarningEl = document.getElementById('noShowInitialWarning');
        const refundControlsEl = document.getElementById('refundControls'); // <--- NOVO: Referência ao bloco de controle de estorno

        if (paidAmount > 0) {
            initialWarningEl.innerHTML = `O cliente já pagou <span class="font-bold">${paidAmountFormatted}</span>. Escolha abaixo se este valor será retido (padrão) ou estornado.`;
            // Reseta para 'Reter' como padrão
            document.getElementById('should_refund').value = 'false';
            refundControlsEl.classList.remove('hidden'); // Mostra os controles
        } else {
            initialWarningEl.textContent = `Nenhum valor foi pago. Marcar como falta apenas registrará o status.`;
            document.getElementById('should_refund').value = 'false';
            refundControlsEl.classList.add('hidden'); // Esconde os controles
        }


        document.getElementById('noshow-error-message').textContent = '';
        document.getElementById('noshow-error-message').classList.add('hidden');
        // Limpar o campo de observações ao abrir
        document.querySelector('#noShowForm textarea[name="no_show_reason"]').value = '';
        document.querySelector('#noShowForm input[name="block_user"]').checked = false;

        document.getElementById('noShowModal').classList.remove('hidden');
        document.getElementById('noShowModal').classList.add('flex');
    }

    function closeNoShowModal() {
        document.getElementById('noShowModal').classList.add('hidden');
        document.getElementById('noShowModal').classList.remove('flex');
    }

    document.getElementById('noShowForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const reservaId = document.getElementById('noShowReservaId').value;
        // RENOMEADO: Campo de observação agora é 'no_show_reason'
        const reason = this.querySelector('textarea[name="no_show_reason"]').value;
        const blockUser = this.querySelector('input[name="block_user"]').checked; // Boolean (true/false)

        // NOVO: Captura o valor pago e a decisão de estorno
        const paidAmount = document.getElementById('noShowPaidAmount').value;
        // Converte a string 'true'/'false' em boolean JS para o payload (Laravel JSON)
        const shouldRefund = this.querySelector('select[name="should_refund"]').value === 'true';

        const csrfToken = document.querySelector('input[name="_token"]').value;

        const submitBtn = document.getElementById('submitNoShowBtn');
        const submitText = document.getElementById('submitNoShowText');
        const submitSpinner = document.getElementById('submitNoShowSpinner');
        const errorMessageDiv = document.getElementById('noshow-error-message');

        // 1. Validação de Motivo (Obrigatório)
        if (reason.length < 5) {
             errorMessageDiv.textContent = 'O motivo da falta (Observações) é obrigatório e deve ter no mínimo 5 caracteres.';
             errorMessageDiv.classList.remove('hidden');
             return;
        }

        submitBtn.disabled = true;
        submitText.classList.add('hidden');
        submitSpinner.classList.remove('hidden');
        errorMessageDiv.classList.add('hidden');

        // MONTANDO O PAYLOAD
        const payload = {
            reserva_id: reservaId,
            no_show_reason: reason, // Renomeado para refletir o campo do Controller
            block_user: blockUser,
            paid_amount: paidAmount,
            should_refund: shouldRefund // Boolean (true/false)
        };

        // LOG CRÍTICO PARA DEBUG NO BROWSER
        console.log("No-Show Payload Enviado:", payload);

        // 3. Envio AJAX (Rota: PATCH /admin/reservas/{reserva}/no-show, mas o web.php usa POST /admin/pagamentos/{reserva}/falta)
        fetch(`/admin/pagamentos/${reservaId}/falta`, { // Usando a rota que você definiu no web.php
            method: 'POST', // POST ou PATCH, dependendo da sua configuração, mantendo POST como no seu web.php
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                         // Trata o ValidationException
                        const validationErrors = data.errors ? Object.values(data.errors).flat().join('; ') : '';
                        throw new Error(data.message || validationErrors || 'Erro de processamento no servidor.');
                    });
                }
                return response.json();
        })
        .then(data => {
            if(data.success) {
                showMessage(data.message);
                closeNoShowModal();

                // Recarrega a página para atualizar a tabela e o KPI de Faltas
                location.reload();
            } else {
                errorMessageDiv.textContent = data.message || 'Erro ao registrar falta.';
                errorMessageDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            errorMessageDiv.textContent = 'Erro ao registrar falta: ' + error.message;
            errorMessageDiv.classList.remove('hidden');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitText.classList.remove('hidden');
            submitSpinner.classList.add('hidden');
        });
    });

</script>


</x-app-layout>
