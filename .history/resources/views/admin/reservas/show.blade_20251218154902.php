<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- Botão Voltar --}}
                <button type="button" onclick="window.history.back()" class="bg-white dark:bg-gray-800 p-2 rounded-full shadow-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 transition-all">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <h2 class="font-black text-xl text-gray-800 dark:text-gray-200 uppercase tracking-tighter">
                    Detalhes da Reserva #{{ $reserva->id }}
                </h2>
            </div>

            @php
                $statusClass = [
                    'pending' => 'bg-orange-100 text-orange-700 border-orange-200',
                    'confirmed' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                    'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                    'rejected' => 'bg-gray-100 text-gray-700 border-gray-200',
                    'expired' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                ][$reserva->status] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <span class="px-4 py-1 rounded-full text-xs font-black uppercase border {{ $statusClass }}">
                {{ $reserva->statusText }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Mensagens de Feedback --}}
            @if (session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-r-xl shadow-sm font-bold">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-xl shadow-sm font-bold">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- 🟦 COLUNA DA ESQUERDA: INFORMAÇÕES E EXTRATO --}}
                <div class="lg:col-span-2 space-y-6">

                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between items-start mb-8 border-b dark:border-gray-700 pb-6">
                            <div>
                                <label class="text-[10px] font-black text-indigo-500 uppercase tracking-widest">Responsável</label>
                                <h3 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                                    {{ $reserva->client_name ?? ($reserva->user ? $reserva->user->name : 'N/A') }}
                                </h3>
                                <p class="text-sm text-gray-500 font-mono mt-1">📞 {{ $reserva->client_contact ?? 'Não informado' }}</p>
                            </div>
                            <div class="text-right">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Data e Horário</label>
                                <p class="text-xl font-bold dark:text-gray-200">{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}</p>
                                <p class="text-indigo-600 font-black">{{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }}h - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}h</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl text-center">
                                <span class="text-[9px] font-black text-gray-400 uppercase block">Arena</span>
                                <span class="font-bold dark:text-white text-sm">Quadra #{{ $reserva->court_id ?? '1' }}</span>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl text-center">
                                <span class="text-[9px] font-black text-gray-400 uppercase block">Origem</span>
                                <span class="font-bold dark:text-white text-sm italic">{{ $reserva->criadoPorLabel }}</span>
                            </div>
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-800 text-center">
                                <span class="text-[9px] font-black text-indigo-500 uppercase block">Tipo</span>
                                <span class="font-black text-indigo-700 dark:text-indigo-400 text-sm uppercase">
                                    {{ $reserva->is_recurrent ? '📅 Mensalista' : '⚡ Pontual' }}
                                </span>
                            </div>
                        </div>

                        @if ($reserva->notes)
                            <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/50 border-l-4 border-yellow-400 rounded-r-xl">
                                <h4 class="text-[10px] font-black text-yellow-700 uppercase mb-1">Observações</h4>
                                <p class="text-sm text-yellow-800 dark:text-yellow-200 italic">{{ $reserva->notes }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- EXTRATO DE PAGAMENTOS --}}
                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-8 py-5 bg-gray-50 dark:bg-gray-700/50 flex justify-between items-center border-b dark:border-gray-700">
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">Histórico Financeiro</h4>
                        </div>
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 dark:bg-gray-800">
                                <tr class="text-[10px] font-black text-gray-400 uppercase">
                                    <th class="px-8 py-3">Lançamento</th>
                                    <th class="px-8 py-3">Método</th>
                                    <th class="px-8 py-3 text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-gray-700">
                                @forelse($reserva->transactions as $transacao)
                                    <tr>
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
                                        <td colspan="3" class="px-8 py-10 text-center text-gray-400 uppercase text-[10px] font-black italic">Nenhum pagamento registrado</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 🟧 COLUNA DA DIREITA: BALANÇO E AÇÕES --}}
                <div class="space-y-6">
                    <div class="bg-gray-900 text-white p-8 rounded-[2.5rem] shadow-xl relative overflow-hidden">
                        <p class="text-[10px] font-black opacity-50 uppercase tracking-[0.2em] mb-6">Balanço da Reserva</p>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center opacity-70">
                                <span class="text-xs uppercase font-bold">Total do Jogo</span>
                                <span class="font-mono font-bold">R$ {{ number_format($reserva->price, 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-emerald-400">
                                <span class="text-xs uppercase font-bold">Total Pago</span>
                                <span class="font-mono font-bold font-black">R$ {{ number_format($reserva->total_paid ?? 0, 2, ',', '.') }}</span>
                            </div>
                            <hr class="border-white/10 my-4">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black uppercase tracking-widest opacity-50">Saldo Restante</span>
                                @php $saldo = $reserva->price - ($reserva->total_paid ?? 0); @endphp
                                <span class="text-3xl font-black font-mono mt-1 {{ $saldo > 0 ? 'text-orange-400' : 'text-emerald-400' }}">
                                    R$ {{ number_format(max(0, $saldo), 2, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- PAINEL DE CONTROLE --}}
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">

                        @if ($reserva->status === 'pending')
                            <form method="POST" action="{{ route('admin.reservas.confirmar', $reserva) }}" onsubmit="return confirm('Confirmar agendamento?');">
                                @csrf @method('PATCH')
                                <button type="submit" class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-black text-xs uppercase hover:bg-emerald-700 transition-all">Confirmar Jogo</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reservas.rejeitar', $reserva) }}" onsubmit="return confirm('Rejeitar reserva?');">
                                @csrf @method('PATCH')
                                <button type="submit" class="w-full bg-gray-100 text-gray-500 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-red-50 hover:text-red-600 transition-all">Rejeitar</button>
                            </form>
                        @endif

                        @if ($reserva->status === 'confirmed')
                            {{-- Gatilho para seu Modal de Pagamento --}}
                            <button class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-100 dark:shadow-none">
                                💶 Receber Pagamento
                            </button>

                            <div class="grid grid-cols-2 gap-2 mt-4">
                                @php
                                    $cancellationRoute = $reserva->is_recurrent ? 'admin.reservas.cancelar_pontual' : 'admin.reservas.cancelar';
                                    $label = $reserva->is_recurrent ? 'Cancelar Dia' : 'Cancelar';
                                @endphp
                                <button type="button"
                                    onclick="openCancellationModal('{{ $reserva->client_name }}', {{ $reserva->id }}, '{{ route($cancellationRoute, $reserva->id) }}', '{{ $label }}')"
                                    class="bg-gray-100 text-gray-600 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-red-50 transition-all">
                                    {{ $label }}
                                </button>

                                <form action="{{ route('admin.reservas.no_show', $reserva->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-red-100 text-red-700 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-red-600 hover:text-white transition-all">No-Show 🚨</button>
                                </form>
                            </div>
                        @endif

                        @if ($reserva->is_recurrent && $reserva->status === 'confirmed')
                             <p class="text-[9px] text-amber-600 font-bold uppercase text-center mt-2 leading-tight">
                                ⚠️ O cancelamento acima afeta apenas este horário na série fixa.
                             </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE CANCELAMENTO --}}
    <div id="cancellationReasonModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden z-50 overflow-y-auto italic">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-md transform transition-all">
                <h2 class="text-xl font-black mb-2 text-gray-800 uppercase tracking-tighter" id="modalTitle">Cancelar Reserva</h2>
                <p class="mb-6 text-sm text-gray-500 font-medium">Confirme o cancelamento para <span id="clientNamePlaceholder" class="text-red-600 font-bold"></span></p>

                <form id="cancellationForm" method="POST" action="">
                    @csrf @method('PATCH')
                    <input type="hidden" id="cancellationReservaId" name="reserva_id">
                    <div class="mb-6">
                        <label for="cancellation_reason" class="block text-[10px] font-black text-gray-400 uppercase mb-2">Motivo (Obrigatório):</label>
                        <textarea id="cancellation_reason" name="cancellation_reason" rows="3" required
                                  class="w-full rounded-2xl border-gray-200 focus:border-red-500 focus:ring-red-500 p-4 text-sm bg-gray-50"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeCancellationModal()" class="px-6 py-2 text-xs font-bold text-gray-500 uppercase">Fechar</button>
                        <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-xl font-black text-xs uppercase shadow-lg shadow-red-100">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let currentCancellationUrl = '';

        function goBackAndReload() {
            if (document.referrer && document.referrer !== window.location.href) {
                window.location.replace(document.referrer);
            } else {
                window.history.back();
                setTimeout(() => { window.location.reload(); }, 50);
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
            if (!reason || reason.length < 5) return;

            const submitButton = e.submitter;
            submitButton.disabled = true;
            submitButton.textContent = '...';

            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(currentCancellationUrl, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ cancellation_reason: reason, _method: 'PATCH' })
            })
            .then(response => response.json().then(data => ({ status: response.status, body: data })))
            .then(({ status, body }) => {
                closeCancellationModal();
                if (status >= 200 && status < 300) {
                    goBackAndReload();
                }
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Confirmar';
            });
        });
    </script>
</x-app-layout>
