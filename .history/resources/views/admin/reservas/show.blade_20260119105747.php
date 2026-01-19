<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- 🧠 UX: Botão Voltar Inteligente --}}
                {{-- Em vez de apenas voltar no histórico, ele garante que você caia no dia e arena corretos do calendário --}}
                <a href="{{ route('admin.reservas.index', ['arena_id' => $reserva->arena_id, 'date' => \Carbon\Carbon::parse($reserva->date)->format('Y-m-d')]) }}"
                    class="bg-white dark:bg-gray-800 p-2 rounded-full shadow-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 transition-all flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>

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
                        'maintenance' => 'bg-pink-100 text-pink-700 border-pink-200', // 🛠️ Cor para manutenção
                    ][$reserva->status] ?? 'bg-gray-100 text-gray-700';

                // Tradução simples para exibição
                $statusLabels = [
                    'pending' => 'Pendente',
                    'confirmed' => 'Confirmado',
                    'cancelled' => 'Cancelado',
                    'rejected' => 'Rejeitado',
                    'expired' => 'Expirado',
                    'no_show' => 'Falta',
                    'maintenance' => 'Manutenção',
                ];
            @endphp

            <span class="px-4 py-1 rounded-full text-xs font-black uppercase border {{ $statusClass }}">
                {{ $statusLabels[$reserva->status] ?? $reserva->status }}
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
                    {{-- ⚡ BLOCO DE AÇÕES DA RESERVA --}}
                    <div
                        class="bg-white dark:bg-gray-800 p-6 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 space-y-3">

                        {{-- 📱 NOTIFICAÇÃO DE WHATSAPP PÓS-REATIVAÇÃO --}}
                        @if (session('whatsapp_link'))
                            <div
                                class="p-4 bg-emerald-50 dark:bg-emerald-900/20 border-2 border-emerald-100 dark:border-emerald-800 rounded-2xl mb-2 flex items-center justify-between group animate-pulse hover:animate-none">
                                <div class="flex items-center gap-3">
                                    <span class="text-xl">📱</span>
                                    <div>
                                        <p class="text-[9px] font-black text-emerald-600 uppercase tracking-widest">
                                            Notificação Pronta</p>
                                        <p class="text-[11px] text-emerald-800 dark:text-emerald-200 font-bold">Avisar
                                            cliente via WhatsApp?</p>
                                    </div>
                                </div>
                                <a href="{{ session('whatsapp_link') }}" target="_blank"
                                    class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase transition-all shadow-md">
                                    Enviar
                                </a>
                            </div>
                        @endif

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
                                    <p class="text-xs font-bold text-pink-700 dark:text-pink-300 uppercase">Horário em
                                        Manutenção</p>
                                </div>

                                <button type="button" onclick="openReactivateModal()"
                                    class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs uppercase hover:bg-indigo-700 transition shadow-lg flex items-center justify-center gap-2">
                                    <span>🔄 Finalizar Manutenção / Liberar</span>
                                </button>
                            </div>
                        @endif

                        {{-- 1️⃣ CASO A RESERVA ESTEJA PENDENTE (Aprovar ou Rejeitar) --}}
                        @if ($reserva->status === 'pending')
                            <form method="POST" action="{{ route('admin.reservas.confirmar', $reserva) }}">
                                @csrf @method('PATCH')
                                <button type="submit"
                                    class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-black text-xs uppercase hover:bg-emerald-700 transition shadow-lg shadow-emerald-100">
                                    Aprovar Reserva
                                </button>
                            </form>
                        @endif

                        {{-- 2️⃣ CASO A RESERVA ESTEJA CONFIRMADA OU CONCLUÍDA --}}
                        @if (in_array($reserva->status, ['confirmed', 'completed']))
                            <div class="space-y-3">
                                {{-- 🎯 BOTÃO DE PAGAMENTO --}}
                                @php $saldoRestante = $reserva->price - ($reserva->total_paid ?? 0); @endphp

                                @if ($saldoRestante > 0)
                                    <a href="{{ route('admin.payment.index', ['reserva_id' => $reserva->id, 'date' => \Carbon\Carbon::parse($reserva->date)->format('Y-m-d'), 'arena_id' => $reserva->arena_id]) }}"
                                        class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs uppercase hover:bg-indigo-700 transition shadow-lg flex items-center justify-center gap-2 block text-center">
                                        <span>💶 Receber Saldo (R$
                                            {{ number_format($saldoRestante, 2, ',', '.') }})</span>
                                    </a>
                                @else
                                    <div
                                        class="w-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 py-3 rounded-2xl font-black text-[10px] uppercase text-center border border-emerald-100 dark:border-emerald-800">
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

                                {{-- 🛠️ BOTÃO DE MANUTENÇÃO --}}
                                @if (!$isClosed)
                                    <button type="button"
                                        onclick="openMaintenanceModal('{{ $reserva->id }}', '{{ $reserva->total_paid ?? 0 }}')"
                                        class="w-full bg-pink-50 text-pink-600 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-pink-600 hover:text-white transition flex items-center justify-center gap-2 border border-pink-100 mt-2">
                                        <span>🛠️ Bloquear p/ Manutenção</span>
                                    </button>
                                @endif
                            </div>
                        @endif

                        {{-- 3️⃣ EXIBIR MOTIVO SE FOI CANCELADA/REJEITADA --}}
                        @if ($reserva->status === 'cancelled' || $reserva->status === 'rejected')
                            <div
                                class="p-4 bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-100 dark:border-red-800">
                                <label class="text-[9px] font-black text-red-500 uppercase block mb-1">Motivo da
                                    Inatividade</label>
                                <p class="text-xs text-red-700 dark:text-red-300 italic">
                                    "{{ $reserva->cancellation_reason ?? 'Não informado' }}"
                                </p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        <div id="maintenanceModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                    onclick="closeMaintenanceModal()"></div>

                <div
                    class="relative bg-white dark:bg-gray-800 rounded-[2.5rem] max-w-md w-full p-8 shadow-2xl border border-pink-100 transform transition-all">

                    <div class="mb-6">
                        <div class="w-12 h-12 bg-pink-100 rounded-full flex items-center justify-center mb-4">
                            <span class="text-2xl">🛠️</span>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                            Manutenção</h3>
                        <p class="text-sm text-gray-500 mt-2">O cliente será removido para bloqueio da quadra.</p>
                    </div>

                    <form id="maintenanceForm">
                        <div id="financeActionSection" class="mb-6 space-y-4">
                            <label
                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block px-1">Ação
                                Financeira</label>

                            <div class="grid grid-cols-1 gap-2">
                                <label
                                    class="relative flex flex-col p-4 border-2 border-gray-100 rounded-2xl cursor-pointer hover:bg-gray-50 transition peer-checked:border-pink-500 has-[:checked]:border-pink-500 has-[:checked]:bg-pink-50/30">
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="finance_action" value="refund" checked
                                            class="text-pink-600 focus:ring-pink-500">
                                        <span class="font-black text-xs uppercase text-gray-700">Devolver Valor
                                            (Caixa)</span>
                                    </div>
                                    <p class="text-[10px] text-pink-600 mt-2 font-bold uppercase italic">⚠️ O valor de
                                        R$ <span class="paid-amount-label">0,00</span> sairá do caixa atual.</p>
                                </label>

                                @if ($reserva->is_recurrent)
                                    <label
                                        class="relative flex flex-col p-4 border-2 border-gray-100 rounded-2xl cursor-pointer hover:bg-gray-50 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/30">
                                        <div class="flex items-center gap-3">
                                            <input type="radio" name="finance_action" value="credit"
                                                class="text-indigo-600 focus:ring-indigo-500">
                                            <span class="font-black text-xs uppercase text-gray-700">Mover p/ Próximo
                                                Horário</span>
                                        </div>
                                        <p class="text-[10px] text-gray-500 mt-2">O valor pago será transferido como
                                            crédito para a próxima reserva deste mensalista.</p>
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div class="mb-6">
                            <label
                                class="text-[10px] font-black text-gray-400 uppercase tracking-widest block mb-2 px-1">Motivo
                                da Manutenção</label>
                            <textarea id="maintenance_reason" required rows="2"
                                class="w-full rounded-2xl border-gray-200 dark:bg-gray-900 text-sm focus:ring-pink-500"
                                placeholder="Ex: Reparo rede..."></textarea>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <button type="button" onclick="closeMaintenanceModal()"
                                class="flex-1 px-4 py-4 bg-gray-100 text-gray-500 rounded-2xl font-black uppercase text-[10px]">Cancelar</button>
                            <button type="submit" id="btnConfirmMaintenance"
                                class="flex-[2] px-4 py-4 bg-pink-600 text-white rounded-2xl font-black uppercase text-[10px] shadow-lg shadow-pink-200">Confirmar
                                e Bloquear</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- 🔄 MODAL DE DECISÃO DE REATIVAÇÃO --}}
        <div id="reactivateDecisionModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-6">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                    onclick="closeReactivateModal()"></div>

                <div
                    class="relative bg-white dark:bg-gray-800 rounded-[2.5rem] max-w-md w-full p-8 shadow-2xl border border-indigo-100 transform transition-all">
                    <div class="mb-6 text-center">
                        <div
                            class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                            <span class="text-3xl">🔄</span>
                        </div>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                            Concluir Manutenção</h3>
                        <p class="text-sm text-gray-500 mt-2">Como deseja liberar este horário?</p>
                    </div>

                    <form method="POST" action="{{ route('admin.reservas.reativar_manutencao', $reserva->id) }}"
                        class="space-y-3">
                        @csrf @method('PATCH')

                        {{-- Botão 1: Recupera o nome e status original --}}
                        <button type="submit" name="action" value="restore_client"
                            class="w-full p-4 border-2 border-indigo-100 rounded-2xl hover:bg-indigo-50 dark:hover:bg-gray-700 transition text-left flex items-start gap-4 group">
                            <div
                                class="bg-indigo-100 dark:bg-indigo-900 p-2 rounded-lg group-hover:bg-white transition">
                                👤</div>
                            <div>
                                <span
                                    class="block font-black text-xs uppercase text-indigo-700 dark:text-indigo-400">Restaurar
                                    Cliente Original</span>
                                <span class="block text-[10px] text-gray-500">O horário volta para o cliente anterior
                                    como 'Confirmado'.</span>
                            </div>
                        </button>

                        {{-- Botão 2: Deleta o registro e deixa o slot vago --}}
                        <button type="submit" name="action" value="release_slot"
                            class="w-full p-4 border-2 border-emerald-100 rounded-2xl hover:bg-emerald-50 dark:hover:bg-gray-700 transition text-left flex items-start gap-4 group">
                            <div
                                class="bg-emerald-100 dark:bg-emerald-900 p-2 rounded-lg group-hover:bg-white transition">
                                🔓</div>
                            <div>
                                <span
                                    class="block font-black text-xs uppercase text-emerald-700 dark:text-emerald-400">Liberar
                                    Horário (Vago)</span>
                                <span class="block text-[10px] text-gray-500">Remove o bloqueio e deixa a quadra livre
                                    para novos agendamentos.</span>
                            </div>
                        </button>

                        <button type="button" onclick="closeReactivateModal()"
                            class="w-full py-2 text-gray-400 text-[10px] font-bold uppercase tracking-widest hover:text-gray-600 transition">Cancelar</button>
                    </form>
                </div>
            </div>
        </div>



        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                onclick="closeReactivateModal()"></div>

            <div
                class="relative bg-white dark:bg-gray-800 rounded-[2.5rem] max-w-md w-full p-8 shadow-2xl border border-indigo-100 transform transition-all">
                <div class="mb-6 text-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                        <span class="text-3xl">🔄</span>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                        Concluir Manutenção</h3>
                    <p class="text-sm text-gray-500 mt-2">Como deseja liberar este horário?</p>
                </div>

                <form method="POST" action="{{ route('admin.reservas.reativar_manutencao', $reserva->id) }}"
                    class="space-y-3">
                    @csrf @method('PATCH')

                    {{-- Botão 1: Recupera o nome e status original --}}
                    <button type="submit" name="action" value="restore_client"
                        class="w-full p-4 border-2 border-indigo-100 rounded-2xl hover:bg-indigo-50 dark:hover:bg-gray-700 transition text-left flex items-start gap-4 group">
                        <div class="bg-indigo-100 dark:bg-indigo-900 p-2 rounded-lg group-hover:bg-white transition">
                            👤</div>
                        <div>
                            <span
                                class="block font-black text-xs uppercase text-indigo-700 dark:text-indigo-400">Restaurar
                                Cliente Original</span>
                            <span class="block text-[10px] text-gray-500">O horário volta para o cliente anterior
                                como 'Confirmado'.</span>
                        </div>
                    </button>

                    {{-- Botão 2: Deleta o registro e deixa verde (vago) --}}
                    <button type="submit" name="action" value="release_slot"
                        class="w-full p-4 border-2 border-emerald-100 rounded-2xl hover:bg-emerald-50 dark:hover:bg-gray-700 transition text-left flex items-start gap-4 group">
                        <div class="bg-emerald-100 dark:bg-emerald-900 p-2 rounded-lg group-hover:bg-white transition">
                            🔓</div>
                        <div>
                            <span
                                class="block font-black text-xs uppercase text-emerald-700 dark:text-emerald-400">Liberar
                                Horário (Vago)</span>
                            <span class="block text-[10px] text-gray-500">Remove o bloqueio e deixa a quadra livre
                                para novos agendamentos.</span>
                        </div>
                    </button>

                    <button type="button" onclick="closeReactivateModal()"
                        class="w-full py-2 text-gray-400 text-[10px] font-bold uppercase tracking-widest">Cancelar</button>
                </form>
            </div>
        </div>


    </div>

    {{-- Script e Modal de Cancelamento permanecem iguais, apenas confirme as rotas --}}
    @include('admin.reservas.confirmation_modal') {{-- Se você tiver o modal em um include --}}

    <script>
        let currentCancellationUrl = '';
        window.currentReservaMaintenanceId = "{{ $reserva->id }}";

        /**
         * Auxiliar para evitar erro de 'null' quando um elemento não existe na página
         */
        function safeAddEventListener(id, event, callback) {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener(event, callback);
            }
        }

        /**
         * Atualiza a página ou volta para a listagem
         */
        function goBackAndReload() {
            if (document.referrer && document.referrer !== window.location.href) {
                window.location.href = document.referrer;
            } else {
                window.location.reload();
            }
        }

        // =========================================================================
        // ❌ LÓGICA DE CANCELAMENTO
        // =========================================================================
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

        safeAddEventListener('cancellationForm', 'submit', function(e) {
            e.preventDefault();
            const reason = document.getElementById('cancellation_reason').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            if (!reason || reason.length < 5) {
                alert('Por favor, informe um motivo com pelo menos 5 caracteres.');
                return;
            }

            fetch(currentCancellationUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        cancellation_reason: reason,
                        should_refund: 0,
                        paid_amount_ref: 0
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    goBackAndReload();
                })
                .catch(error => alert('Erro: ' + error.message));
        });

        // =========================================================================
        // 🛠️ LÓGICA DE MANUTENÇÃO (BLOQUEIO)
        // =========================================================================
        function openMaintenanceModal(reservaId, paidAmount) {
            const amount = parseFloat(paidAmount);
            const financeSection = document.getElementById('financeActionSection');

            document.querySelectorAll('.paid-amount-label').forEach(el => {
                el.textContent = amount.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2
                });
            });

            if (amount <= 0) {
                financeSection.classList.add('hidden');
            } else {
                financeSection.classList.remove('hidden');
            }

            window.currentReservaMaintenanceId = reservaId;
            document.getElementById('maintenanceModal').classList.remove('hidden');
        }

        function closeMaintenanceModal() {
            document.getElementById('maintenanceModal').classList.add('hidden');
            document.getElementById('maintenance_reason').value = '';
        }

        safeAddEventListener('maintenanceForm', 'submit', function(e) {
            e.preventDefault();
            const reason = document.getElementById('maintenance_reason').value;
            const financeAction = this.querySelector('input[name="finance_action"]:checked')?.value || 'none';
            const submitBtn = document.getElementById('btnConfirmMaintenance');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            if (reason.length < 5) {
                alert('Por favor, descreva o motivo (mínimo 5 caracteres).');
                return;
            }

            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'PROCESSANDO...';

            const url = "{{ route('admin.reservas.mover_manutencao', ':id') }}".replace(':id', window
                .currentReservaMaintenanceId);

            fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        reason: reason,
                        finance_action: financeAction
                    })
                })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Erro no servidor');
                    return data;
                })
                .then(data => {
                    alert(data.message);
                    window.location.reload();
                })
                .catch(error => alert('Falha na operação: ' + error.message))
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        });

        // =========================================================================
        // 🔄 LÓGICA DE REATIVAÇÃO (FINALIZAR MANUTENÇÃO)
        // =========================================================================
        function openReactivateModal() {
            document.getElementById('reactivateDecisionModal').classList.remove('hidden');
        }

        function closeReactivateModal() {
            document.getElementById('reactivateDecisionModal').classList.add('hidden');
        }
    </script>
</x-app-layout>
