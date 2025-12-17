<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            💰 Gerenciamento de Caixa & Pagamentos
        </h2>
    </x-slot>

    @php
        // Variáveis de controle
        $isActionDisabled = (isset($cashierStatus) && $cashierStatus === 'closed');
        $totalReservasDia = $totalReservasDia ?? 0;
        $totalRecebidoDiaLiquido = $totalRecebidoDiaLiquido ?? 0;
        $totalAntecipadoReservasDia = $totalAntecipadoReservasDia ?? 0;
        $totalPending = $totalPending ?? 0;
        $totalExpected = $totalExpected ?? 0;
        $noShowCount = $noShowCount ?? 0;
        $dataHoje = \Carbon\Carbon::today()->toDateString();
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 1. ESTRUTURA DE KPIS --}}
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                    {{-- CARD: RECEITA HOJE --}}
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-300 dark:border-indigo-800 shadow-lg rounded-xl p-5">
                        <div class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300">
                            RECEITA GARANTIDA P/ HOJE
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.504A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.552L12 12m0 0l-8.618 3.552A11.955 11.955 0 0012 21.056a11.955 11.955 0 008.618-3.552L12 12z"></path></svg>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-indigo-700 dark:text-indigo-300">
                            R$ {{ number_format($totalAntecipadoReservasDia, 2, ',', '.') }}
                        </div>
                    </div>

                    {{-- CARD: SALDO PENDENTE --}}
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-800 shadow-lg rounded-xl p-5">
                        <div class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300">
                            SALDO PENDENTE A RECEBER
                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V4m0 12v4M5 9h14M5 15h14M4 12h16"></path></svg>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-yellow-700 dark:text-yellow-300">
                            R$ {{ number_format($totalPending, 2, ',', '.') }}
                        </div>
                    </div>

                    {{-- CARD: MOVIMENTAÇÃO LÍQUIDA --}}
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-300 dark:border-green-800 shadow-lg rounded-xl p-5">
                        <div class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300">
                            MOVIMENTAÇÃO LÍQUIDA
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-green-700 dark:text-green-300">
                            R$ {{ number_format($totalRecebidoDiaLiquido, 2, ',', '.') }}
                        </div>
                    </div>

                    {{-- CARD: RESERVAS --}}
                    <div class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 shadow-lg rounded-xl p-5">
                        <div class="flex items-center justify-between text-sm font-medium text-gray-700 dark:text-gray-300">
                            RESERVAS ATIVAS
                            <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">
                            {{ $totalReservasDia }}
                        </div>
                    </div>

                </div>
            </div>

            {{-- 2. FECHAMENTO/ABERTURA --}}
            @if($isActionDisabled)
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-400 p-5 rounded-xl shadow-lg flex items-center justify-between">
                    <div class="text-red-700 font-bold flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        CAIXA FECHADO PARA {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                    </div>
                    <button onclick="openCash('{{ $selectedDate }}')" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2 rounded-lg font-bold transition shadow-md">
                        Abrir Caixa
                    </button>
                </div>
            @else
                <div class="bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-400 p-5 rounded-xl shadow-lg flex items-center justify-between">
                    <div class="text-indigo-800 font-medium flex items-center">
                        <svg class="w-6 h-6 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Status: <span id="cashStatus" class="ml-2 font-bold text-red-500">Verificando pendências...</span>
                    </div>
                    <button id="openCloseCashModalBtn" onclick="openCloseCashModal()" disabled class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-bold transition shadow-md disabled:opacity-50">
                        Fechar Caixa
                    </button>
                </div>
            @endif

            {{-- 3. FILTROS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow border border-gray-200 dark:border-gray-700">
                    <form method="GET">
                        <label class="text-xs font-bold text-gray-500 uppercase">Data do Caixa</label>
                        <input type="date" name="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="block w-full mt-1 rounded-lg border-gray-300 dark:bg-gray-700">
                    </form>
                </div>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-xl shadow border border-gray-200 dark:border-gray-700">
                    <form method="GET">
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <label class="text-xs font-bold text-gray-500 uppercase">Buscar Cliente</label>
                        <div class="flex gap-2 mt-1">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou WhatsApp..." class="block w-full rounded-lg border-gray-300 dark:bg-gray-700">
                            <button class="bg-gray-800 text-white px-4 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- 4. TABELA DE RESERVAS --}}
            <div class="bg-white dark:bg-gray-800 shadow-2xl rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Horário</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Pago</th>
                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Saldo</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($reservas as $reserva)
                            @php
                                $total = $reserva->final_price ?? $reserva->price;
                                $pago = $reserva->total_paid ?? 0;
                                $restante = max(0, $total - $pago);

                                // DETECTOR DE HOJE (Sugestão 1)
                                $eHoje = (\Carbon\Carbon::parse($reserva->date)->toDateString() === $dataHoje);

                                // Detector de Atraso
                                $isOverdue = false;
                                if (in_array($reserva->status, ['confirmed', 'pending'])) {
                                    $endTime = \Carbon\Carbon::parse($reserva->date->format('Y-m-d') . ' ' . $reserva->end_time->format('H:i:s'));
                                    if ($endTime->isPast()) $isOverdue = true;
                                }

                                // Classes de Status
                                $statusClass = 'bg-gray-100 text-gray-800';
                                $statusLabel = 'AGUARDANDO';

                                if ($reserva->status === 'no_show') {
                                    $statusClass = 'bg-red-600 text-white font-bold';
                                    $statusLabel = 'FALTA';
                                } elseif ($reserva->payment_status === 'paid' || $reserva->status === 'completed') {
                                    $statusClass = 'bg-green-100 text-green-800 font-bold';
                                    $statusLabel = 'PAGO';
                                } elseif ($isOverdue) {
                                    $statusClass = 'bg-red-700 text-white font-bold animate-pulse';
                                    $statusLabel = 'ATRASADO';
                                } elseif ($reserva->payment_status === 'partial') {
                                    $statusClass = 'bg-yellow-100 text-yellow-800 font-bold';
                                    $statusLabel = 'PARCIAL';
                                }
                            @endphp

                            <tr class="transition {{ $eHoje ? 'bg-indigo-50/40 dark:bg-indigo-900/10 border-l-4 border-indigo-500' : '' }} hover:bg-gray-100 dark:hover:bg-gray-700">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold">
                                    {{ $reserva->start_time->format('H:i') }} - {{ $reserva->end_time->format('H:i') }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-sm font-bold">{{ $reserva->client_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $reserva->client_contact }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="px-3 py-1 text-[10px] rounded-full {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-bold">R$ {{ number_format($total, 2, ',', '.') }}</td>
                                <td class="px-4 py-4 text-right text-green-600">R$ {{ number_format($pago, 2, ',', '.') }}</td>
                                <td class="px-4 py-4 text-right font-black {{ $restante > 0 ? 'text-red-600' : 'text-gray-300' }}">
                                    R$ {{ number_format($restante, 2, ',', '.') }}
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if($restante > 0 && !$isActionDisabled && !in_array($reserva->status, ['no_show', 'canceled', 'rejected']))
                                        <button onclick="openPaymentModal({{ $reserva->id }}, {{ $total }}, {{ $restante }}, {{ $pago }}, '{{ $reserva->client_name }}', {{ $reserva->is_recurrent ? 'true' : 'false' }})"
                                            class="bg-green-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-green-700 transition">
                                            $ Baixar
                                        </button>
                                    @endif

                                    @if(!in_array($reserva->status, ['no_show', 'canceled', 'rejected', 'completed']) && !$isActionDisabled)
                                        <button onclick="openNoShowModal({{ $reserva->id }}, '{{ $reserva->client_name }}', {{ $pago }})"
                                            class="bg-red-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-red-700 transition ml-1">
                                            X Falta
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500 italic">Nenhuma reserva encontrada para esta data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- 5. MOVIMENTAÇÃO DETALHADA --}}
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="font-bold text-gray-700 dark:text-gray-200 mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    Auditoria de Movimentos ({{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }})
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold uppercase">Hora</th>
                                <th class="px-4 py-2 text-left text-xs font-bold uppercase">Origem</th>
                                <th class="px-4 py-2 text-left text-xs font-bold uppercase">Tipo</th>
                                <th class="px-4 py-2 text-right text-xs font-bold uppercase">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                            @foreach($financialTransactions as $trans)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($trans->paid_at)->format('H:i:s') }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $trans->reserva->client_name ?? 'Caixa Avulso' }}</td>
                                    <td class="px-4 py-3">{{ strtoupper($trans->type) }} ({{ strtoupper($trans->payment_method) }})</td>
                                    <td class="px-4 py-3 text-right font-bold {{ $trans->amount < 0 ? 'text-red-600' : 'text-green-600' }}">
                                        R$ {{ number_format($trans->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-900 font-black">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right">TOTAL LÍQUIDO:</td>
                                <td class="px-4 py-3 text-right text-lg">R$ {{ number_format($totalRecebidoDiaLiquido, 2, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>
    </div>

    {{-- MODAIS E SCRIPTS (Incluídos dinamicamente) --}}
    @include('admin.payment.modals')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            checkCashierStatus();
        });

        function checkCashierStatus() {
            const total = parseInt("{{ $totalReservasDia }}");
            const finalStatuses = ['pago', 'falta', 'cancelada', 'rejeitada', 'finalizado'];
            let concluido = 0;

            document.querySelectorAll('tbody tr').forEach(row => {
                const badge = row.querySelector('span.rounded-full');
                if (badge && finalStatuses.includes(badge.innerText.trim().toLowerCase())) {
                    concluido++;
                }
            });

            const btn = document.getElementById('openCloseCashModalBtn');
            const status = document.getElementById('cashStatus');

            if (!btn || !status) return;

            if (total === 0 || concluido === total) {
                btn.disabled = false;
                status.innerText = "✅ Pronto para fechar!";
                status.className = "ml-2 font-bold text-green-600";
            } else {
                btn.disabled = true;
                status.innerText = `🚨 ${total - concluido} Pendentes`;
                status.className = "ml-2 font-bold text-red-500";
            }
        }
    </script>
</x-app-layout>
