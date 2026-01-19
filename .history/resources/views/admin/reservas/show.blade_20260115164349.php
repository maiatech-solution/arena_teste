<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- Botão Voltar --}}
                <button type="button" onclick="window.history.back()"
                    class="bg-white dark:bg-gray-800 p-2 rounded-full shadow-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 transition-all">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7">
                        </path>
                    </svg>
                </button>
                <h2 class="font-black text-xl text-gray-800 dark:text-gray-200 uppercase tracking-tighter">
                    Detalhes da Reserva #{{ $reserva->id }}
                </h2>
            </div>

            @php
                $statusClass =
                    [
                        'pending' => 'bg-orange-100 text-orange-700 border-orange-200',
                        'confirmed' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                        'rejected' => 'bg-gray-100 text-gray-700 border-gray-200',
                        'expired' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                        'no_show' => 'bg-black text-white border-gray-900',
                    ][$reserva->status] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <span class="px-4 py-1 rounded-full text-xs font-black uppercase border {{ $statusClass }}">
                {{ $reserva->status }} {{-- Use o status direto se o statusText não estiver na model --}}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 🟦 COLUNA DA ESQUERDA E DIREITA --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- INFORMAÇÕES E EXTRATO --}}
                <div class="lg:col-span-2 space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between items-start mb-8 border-b dark:border-gray-700 pb-6">
                            <div>
                                <label
                                    class="text-[10px] font-black text-indigo-500 uppercase tracking-widest">Responsável</label>
                                <h3
                                    class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                                    {{ $reserva->client_name }}
                                </h3>
                                <p class="text-sm text-gray-500 font-mono mt-1">📞
                                    {{ $reserva->client_contact ?? 'Não informado' }}</p>
                            </div>
                            <div class="text-right">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Data e
                                    Horário</label>
                                <p class="text-xl font-bold dark:text-gray-200">
                                    {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}</p>
                                <p class="text-indigo-600 font-black">
                                    {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }}h -
                                    {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}h</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            {{-- 🏟️ AJUSTE MULTIQUADRA AQUI --}}
                            <div
                                class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl text-center border border-gray-100 dark:border-gray-800">
                                <span class="text-[9px] font-black text-gray-400 uppercase block">Arena/Quadra</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400 text-sm uppercase">
                                    {{ $reserva->arena->name ?? 'Quadra Padrão' }}
                                </span>
                            </div>
                            <div
                                class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl text-center border border-gray-100 dark:border-gray-800">
                                <span class="text-[9px] font-black text-gray-400 uppercase block">Atendido por</span>
                                <span
                                    class="font-bold dark:text-white text-sm uppercase">{{ $reserva->manager->name ?? 'Sistema' }}</span>
                            </div>
                            <div
                                class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-800 text-center">
                                <span class="text-[9px] font-black text-indigo-500 uppercase block">Tipo de
                                    Contrato</span>
                                <span class="font-black text-indigo-700 dark:text-indigo-400 text-sm uppercase">
                                    {{ $reserva->is_recurrent ? '📅 Mensalista' : '⚡ Pontual' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- HISTÓRICO FINANCEIRO --}}
                    <div
                        class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-8 py-5 bg-gray-50 dark:bg-gray-700/50 border-b dark:border-gray-700">
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">Extrato Financeiro
                            </h4>
                        </div>
                        <table class="w-full text-left">
                            <tbody class="divide-y dark:divide-gray-700">
                                @forelse($reserva->transactions as $transacao)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                        <td class="px-8 py-4 font-mono text-[11px] dark:text-gray-400">
                                            {{ $transacao->paid_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-8 py-4 uppercase text-[10px] font-bold dark:text-gray-300">
                                            {{ $transacao->payment_method }}
                                        </td>
                                        <td class="px-8 py-4 text-right font-black text-emerald-600">
                                            R$ {{ number_format($transacao->amount, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3"
                                            class="px-8 py-10 text-center text-gray-400 uppercase text-[10px] font-black italic tracking-widest">
                                            Sem movimentações</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- COLUNA DA DIREITA --}}
                <div class="space-y-6">
                    <div class="bg-gray-900 text-white p-8 rounded-[2.5rem] shadow-xl relative overflow-hidden">
                        <p class="text-[10px] font-black opacity-50 uppercase tracking-[0.2em] mb-6">Balanço Final</p>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center opacity-70">
                                <span class="text-xs uppercase font-bold tracking-tighter">Preço do Jogo</span>
                                <span class="font-mono font-bold">R$
                                    {{ number_format($reserva->price, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-emerald-400">
                                <span class="text-xs uppercase font-bold tracking-tighter">Já Recebido</span>
                                <span class="font-mono font-black">R$
                                    {{ number_format($reserva->total_paid ?? 0, 2, ',', '.') }}</span>
                            </div>
                            <hr class="border-white/10 my-4">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black uppercase tracking-widest opacity-50">Saldo a
                                    Pagar</span>
                                @php $saldo = $reserva->price - ($reserva->total_paid ?? 0); @endphp
                                <span
                                    class="text-4xl font-black font-mono mt-1 {{ $saldo > 0 ? 'text-orange-400' : 'text-emerald-400' }}">
                                    R$ {{ number_format(max(0, $saldo), 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- AÇÕES --}}
                    <div
                        class="bg-white dark:bg-gray-800 p-6 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 space-y-3">

                        {{-- 🔒 VERIFICAÇÃO DE CAIXA PARA BLOQUEIO DE AÇÕES --}}
                        @php
                            $isClosed = \App\Models\Cashier::where('date', $reserva->date)
                                ->where('status', 'closed')
                                ->exists();
                        @endphp

                        {{-- 🛠️ CASO A RESERVA SEJA UMA MANUTENÇÃO (Reativar) --}}
                        @if ($reserva->status === 'maintenance')
                            <div class="space-y-3">
                                <div
                                    class="p-4 bg-pink-50 dark:bg-pink-900/20 border border-pink-100 dark:border-pink-800 rounded-2xl text-center">
                                    <p class="text-[10px] font-black text-pink-600 uppercase mb-1">Status Atual</p>
                                    <p class="text-xs font-bold text-pink-700 dark:text-pink-300">HORÁRIO EM MANUTENÇÃO
                                    </p>
                                </div>

                                <form method="POST"
                                    action="{{ route('admin.reservas.reativar_manutencao', $reserva->id) }}"
                                    onsubmit="return confirm('Deseja finalizar a manutenção e liberar este horário?')">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs uppercase hover:bg-indigo-700 transition shadow-lg flex items-center justify-center gap-2">
                                        <span>🔄 Reativar Slot / Liberar</span>
                                    </button>
                                </form>
                            </div>
                        @endif

                        {{-- 1. CASO A RESERVA ESTEJA PENDENTE (Aprovar ou Rejeitar) --}}
                        @if ($reserva->status === 'pending')
                            <form method="POST" action="{{ route('admin.reservas.confirmar', $reserva) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-black text-xs uppercase hover:bg-emerald-700 transition shadow-lg shadow-emerald-100">Aprovar
                                    Reserva</button>
                            </form>
                        @endif

                        {{-- 2. CASO A RESERVA ESTEJA CONFIRMADA (Receber Saldo, No-Show, Cancelar ou Manutenção) --}}
                        @if ($reserva->status === 'confirmed')
                            <div class="space-y-3">
                                {{-- 🎯 BOTÃO DE PAGAMENTO --}}
                                @php $saldoRestante = $reserva->price - ($reserva->total_paid ?? 0); @endphp

                                @if ($saldoRestante > 0)
                                    <a href="{{ route('admin.payment.index', ['reserva_id' => $reserva->id, 'date' => \Carbon\Carbon::parse($reserva->date)->format('Y-m-d'), 'arena_id' => $reserva->arena_id]) }}"
                                        class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs uppercase hover:bg-indigo-700 transition shadow-lg shadow-indigo-100 flex items-center justify-center gap-2 block text-center">
                                        <span>Euro Receber Saldo (R$
                                            {{ number_format($saldoRestante, 2, ',', '.') }})</span>
                                    </a>
                                @else
                                    <div
                                        class="w-full bg-emerald-50 text-emerald-700 py-3 rounded-2xl font-black text-[10px] uppercase text-center border border-emerald-100">
                                        ✅ Jogo Totalmente Pago
                                    </div>
                                @endif

                                <div class="grid grid-cols-2 gap-2 mt-4">
                                    {{-- REGISTRAR NO-SHOW --}}
                                    @if ($isClosed)
                                        <button type="button"
                                            onclick="alert('🚫 Ação Bloqueada: O caixa do dia já foi encerrado.')"
                                            class="w-full bg-gray-100 text-gray-400 py-3 rounded-2xl font-black text-[10px] uppercase cursor-not-allowed border border-gray-200">
                                            No-Show 🔒
                                        </button>
                                    @else
                                        <form action="{{ route('admin.reservas.no_show', $reserva->id) }}"
                                            method="POST" onsubmit="return confirm('Marcar como Falta (No-Show)?');">
                                            @csrf
                                            <button type="submit"
                                                class="w-full bg-red-50 text-red-600 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-red-600 hover:text-white transition">
                                                No-Show 🚨
                                            </button>
                                        </form>
                                    @endif

                                    {{-- CANCELAR --}}
                                    @php $cancellationRoute = $reserva->is_recurrent ? 'admin.reservas.cancelar_pontual' : 'admin.reservas.cancelar'; @endphp
                                    <button type="button"
                                        @if ($isClosed) onclick="alert('🚫 Ação Bloqueada: O caixa deste dia já foi encerrado.')"
                        class="w-full bg-gray-100 text-gray-400 py-3 rounded-2xl font-black text-[10px] uppercase cursor-not-allowed border border-gray-200"
                    @else
                        onclick="openCancellationModal('{{ $reserva->client_name }}', '{{ $reserva->id }}', '{{ route($cancellationRoute, $reserva->id) }}', 'Cancelar Agendamento')"
                        class="w-full bg-gray-50 text-gray-500 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-gray-100 transition" @endif>
                                        Cancelar {{ $isClosed ? '🔒' : '' }}
                                    </button>
                                </div>

                                {{-- 🛠️ BOTÃO DE MANUTENÇÃO (Para reservas confirmadas) --}}
                                @if (!$isClosed)
                                    <button type="button"
                                        onclick="openMaintenanceModal('{{ $reserva->id }}', '{{ $reserva->total_paid ?? 0 }}')"
                                        class="w-full bg-pink-50 text-pink-600 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-pink-600 hover:text-white transition flex items-center justify-center gap-2 border border-pink-100 mt-2">
                                        <span>🛠️ Bloquear p/ Manutenção</span>
                                    </button>
                                @endif
                            </div>
                        @endif

                        {{-- 3. EXIBIR MOTIVO SE FOI CANCELADA/REJEITADA --}}
                        @if ($reserva->status === 'cancelled' || $reserva->status === 'rejected')
                            <div
                                class="p-4 bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-100 dark:border-red-800">
                                <label class="text-[9px] font-black text-red-500 uppercase block mb-1">Motivo da
                                    Inatividade</label>
                                <p class="text-xs text-red-700 dark:text-red-300 italic">
                                    "{{ $reserva->cancellation_reason ?? 'Não informado' }}"</p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
        <div id="maintenanceModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeMaintenanceModal()"></div>

        <div class="relative bg-white dark:bg-gray-800 rounded-[2.5rem] max-w-md w-full p-8 shadow-2xl border border-pink-100 dark:border-pink-900/30 transform transition-all">

            <div class="mb-6">
                <div class="w-12 h-12 bg-pink-100 dark:bg-pink-900/30 rounded-full flex items-center justify-center mb-4">
                    <span class="text-2xl">🛠️</span>
                </div>
                <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                    Bloquear Grade
                </h3>
                <p class="text-sm text-gray-500 mt-2 leading-relaxed">
                    Você está removendo o cliente deste horário para realizar uma manutenção. O slot ficará <span class="text-pink-600 font-bold">ROSA</span> no calendário.
                </p>
            </div>

            <div id="refundWarning" class="hidden mb-6 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-100 dark:border-orange-800 rounded-2xl">
                <div class="flex items-start gap-3">
                    <span class="text-lg">⚠️</span>
                    <div>
                        <p class="text-[10px] font-black text-orange-600 uppercase tracking-widest mb-1">Atenção: Dinheiro em Caixa</p>
                        <p class="text-xs text-orange-700 dark:text-orange-300">
                            Este cliente já pagou <span class="font-bold">R$ <span id="warningPaidAmount"></span></span>. Lembre-se de realizar o estorno manual no caixa após confirmar o bloqueio.
                        </p>
                    </div>
                </div>
            </div>

            <form id="maintenanceForm">
                <div class="mb-6">
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">
                        Motivo do Bloqueio
                    </label>
                    <textarea
                        id="maintenance_reason"
                        required
                        rows="3"
                        class="w-full rounded-2xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:ring-pink-500 focus:border-pink-500 transition-all"
                        placeholder="Ex: Reparo no refletor, limpeza da grama..."></textarea>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mt-8">
                    <button
                        type="button"
                        onclick="closeMaintenanceModal()"
                        class="flex-1 px-4 py-4 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:bg-gray-200 transition">
                        Desistir
                    </button>
                    <button
                        type="submit"
                        id="btnConfirmMaintenance"
                        class="flex-[2] px-4 py-4 bg-pink-600 text-white rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-lg shadow-pink-200 dark:shadow-none hover:bg-pink-700 transition">
                        Confirmar Bloqueio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
    </div>

    {{-- Script e Modal de Cancelamento permanecem iguais, apenas confirme as rotas --}}
    @include('admin.reservas.confirmation_modal') {{-- Se você tiver o modal em um include --}}
    <script>
        let currentCancellationUrl = '';

        /**
         * Atualiza a página ou volta para a listagem garantindo dados novos.
         */
        function goBackAndReload() {
            // Se viemos de outra página, recarrega para atualizar os badges de status
            if (document.referrer && document.referrer !== window.location.href) {
                window.location.href = document.referrer;
            } else {
                window.location.reload();
            }
        }

        function openCancellationModal(clientName, reservaId, url, actionLabel) {
            document.getElementById('modalTitle').textContent = actionLabel;
            document.getElementById('clientNamePlaceholder').textContent = clientName;
            document.getElementById('cancellationReservaId').value = reservaId;
            currentCancellationUrl = url;
            document.getElementById('cancellation_reason').value = '';
            document.getElementById('cancellationReasonModal').classList.remove('hidden');
        }

        function closeCancellationModal() {
            document.getElementById('cancellationReasonModal').classList.add('hidden');
        }

        document.getElementById('cancellationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const reason = document.getElementById('cancellation_reason').value;
            if (!reason || reason.length < 5) {
                alert('Por favor, informe um motivo com pelo menos 5 caracteres.');
                return;
            }

            const submitButton = e.submitter || this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            const originalText = submitButton.textContent;
            submitButton.textContent = 'Processando...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // 🎯 Enviamos os campos que o seu ReservaController/AdminController esperam
            const payload = {
                cancellation_reason: reason,
                should_refund: 0, // Padrão para cancelamento simples via Detalhes
                paid_amount_ref: 0 // Padrão
            };

            fetch(currentCancellationUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Erro ao processar requisição');
                    return data;
                })
                .then(data => {
                    closeCancellationModal();
                    // Feedback visual antes de recarregar
                    alert(data.message || 'Operação realizada com sucesso!');
                    goBackAndReload();
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Falha na operação: ' + error.message);
                })
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                });
        });
    </script>
</x-app-layout>
