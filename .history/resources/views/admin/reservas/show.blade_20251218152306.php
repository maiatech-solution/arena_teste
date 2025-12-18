<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- Botão Voltar Melhorado --}}
                <button type="button" onclick="window.history.back()" class="bg-white dark:bg-gray-800 p-2 rounded-full shadow-sm border border-gray-200 dark:border-gray-700 hover:bg-gray-50 transition-all">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <h2 class="font-black text-xl text-gray-800 dark:text-gray-200 uppercase tracking-tighter">
                    Reserva #{{ $reserva->id }}
                </h2>
            </div>

            {{-- Badge de Status Dinâmica --}}
            @php
                $statusClass = [
                    'pending' => 'bg-orange-100 text-orange-700 border-orange-200',
                    'confirmed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                    'cancelled' => 'bg-red-100 text-red-700 border-red-200',
                    'rejected' => 'bg-gray-100 text-gray-700 border-gray-200',
                ][$reserva->status] ?? 'bg-gray-100 text-gray-700';
            @endphp
            <span class="px-4 py-1 rounded-full text-xs font-black uppercase border {{ $statusClass }}">
                {{ $reserva->statusText }}
            </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Alertas de Sessão --}}
            @if (session('success') || session('error'))
                <div class="{{ session('success') ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-red-50 border-red-500 text-red-700' }} p-4 rounded-xl border-l-4 shadow-sm mb-6 font-bold">
                    {{ session('success') ?? session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {{-- 🟦 COLUNA 1 & 2: DADOS DO CLIENTE E HISTÓRICO --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Card Principal --}}
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="flex justify-between items-start mb-8 border-b dark:border-gray-700 pb-6">
                            <div>
                                <label class="text-[10px] font-black text-indigo-500 uppercase tracking-widest">Responsável pela Reserva</label>
                                <h3 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tighter">
                                    {{ $reserva->client_name ?? ($reserva->user ? $reserva->user->name : 'N/A') }}
                                </h3>
                                <p class="text-sm text-gray-500 font-mono mt-1">📞 {{ $reserva->client_contact ?? 'Não informado' }}</p>
                            </div>
                            <div class="text-right">
                                <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Data do Jogo</label>
                                <p class="text-xl font-bold dark:text-gray-200">{{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}</p>
                                <p class="text-indigo-600 font-black">{{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }}h às {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}h</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl">
                                <span class="text-[9px] font-black text-gray-400 uppercase block mb-1">Localização</span>
                                <span class="font-bold dark:text-white uppercase text-sm">Arena/Quadra #{{ $reserva->court_id ?? '1' }}</span>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl">
                                <span class="text-[9px] font-black text-gray-400 uppercase block mb-1">Origem</span>
                                <span class="font-bold dark:text-white text-sm italic">{{ $reserva->criadoPorLabel }}</span>
                            </div>
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-2xl border border-indigo-100 dark:border-indigo-800">
                                <span class="text-[9px] font-black text-indigo-500 uppercase block mb-1">Tipo de Reserva</span>
                                <span class="font-black text-indigo-700 dark:text-indigo-400 text-sm uppercase">
                                    {{ $reserva->is_recurrent ? '📅 Mensalista' : '⚡ Pontual' }}
                                </span>
                            </div>
                        </div>

                        @if($reserva->notes)
                            <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 rounded-r-xl">
                                <span class="text-[9px] font-black text-amber-600 uppercase block">Observações do Gestor</span>
                                <p class="text-sm text-amber-800 dark:text-amber-200 mt-1 italic">{{ $reserva->notes }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- 📜 HISTÓRICO DE PAGAMENTOS (EXTRATO) --}}
                    <div class="bg-white dark:bg-gray-800 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="px-8 py-5 bg-gray-50 dark:bg-gray-700/50 flex justify-between items-center border-b dark:border-gray-700">
                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest italic">Extrato Financeiro</h4>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">{{ $reserva->transactions->count() }} transações</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-[10px] font-black text-gray-400 uppercase bg-gray-50/50 dark:bg-gray-800">
                                        <th class="px-8 py-3">Data/Hora</th>
                                        <th class="px-8 py-3">Método</th>
                                        <th class="px-8 py-3">Tipo</th>
                                        <th class="px-8 py-3 text-right">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y dark:divide-gray-700">
                                    @forelse($reserva->transactions as $transacao)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30 transition-colors">
                                            <td class="px-8 py-4 font-mono text-[11px] dark:text-gray-400">
                                                {{ $transacao->paid_at->format('d/m/Y') }} <span class="opacity-50 ml-1">{{ $transacao->paid_at->format('H:i') }}</span>
                                            </td>
                                            <td class="px-8 py-4">
                                                <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-[9px] font-black uppercase text-gray-600 dark:text-gray-300">
                                                    {{ $transacao->payment_method }}
                                                </span>
                                            </td>
                                            <td class="px-8 py-4 text-[10px] font-bold text-gray-500 uppercase tracking-tighter italic">
                                                {{ $transacao->type }}
                                            </td>
                                            <td class="px-8 py-4 text-right font-black text-emerald-600 text-sm italic">
                                                R$ {{ number_format($transacao->amount, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-8 py-10 text-center text-gray-400 uppercase text-[10px] font-black tracking-widest italic opacity-50">Nenhum pagamento registrado nesta reserva</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- 🟧 COLUNA 3: STATUS FINANCEIRO E AÇÕES --}}
                <div class="space-y-6">

                    {{-- Card de Saldo --}}
                    <div class="bg-gray-900 text-white p-8 rounded-[2.5rem] shadow-xl relative overflow-hidden group">
                        {{-- Detalhe visual de fundo --}}
                        <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform duration-500">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 002-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                        </div>

                        <div class="relative z-10">
                            <p class="text-[10px] font-black opacity-50 uppercase tracking-[0.2em] mb-6">Balanço da Partida</p>

                            <div class="space-y-4">
                                <div class="flex justify-between items-center opacity-70">
                                    <span class="text-xs uppercase font-bold">Valor Base</span>
                                    <span class="font-mono font-bold">R$ {{ number_format($reserva->price, 2, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between items-center text-emerald-400">
                                    <span class="text-xs uppercase font-bold">Total Pago</span>
                                    <span class="font-mono font-bold">R$ {{ number_format($reserva->total_paid ?? 0, 2, ',', '.') }}</span>
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

                            @if($saldo <= 0)
                                <div class="mt-6 flex items-center justify-center gap-2 bg-emerald-500/20 text-emerald-400 py-3 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] border border-emerald-500/30">
                                    <span>Pagamento Concluído</span>
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- PAINEL DE AÇÕES CRÍTICAS --}}
                    <div class="bg-white dark:bg-gray-800 p-8 rounded-[2.5rem] shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-center mb-2">Comandos do Gestor</h4>

                        {{-- Fluxo de Aprovação --}}
                        @if ($reserva->status === $reserva::STATUS_PENDENTE)
                            <form method="POST" action="{{ route('admin.reservas.confirmar', $reserva) }}" onsubmit="return confirm('Confirmar o agendamento de {{ $reserva->client_name }}?');">
                                @csrf @method('PATCH')
                                <button type="submit" class="w-full bg-emerald-600 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100 dark:shadow-none">
                                    Confirmar Jogo
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.reservas.rejeitar', $reserva) }}" onsubmit="return confirm('Rejeitar esta reserva?');">
                                @csrf @method('PATCH')
                                <button type="submit" class="w-full bg-gray-100 text-gray-600 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-red-50 hover:text-red-600 transition-all">
                                    Rejeitar Pedido
                                </button>
                            </form>
                        @endif

                        {{-- Fluxo de Gerenciamento Financeiro e Cancelamento --}}
                        @if ($reserva->status === $reserva::STATUS_CONFIRMADA)
                            {{-- Aqui abre o seu modal de pagamento (Trigger via JS se necessário) --}}
                            <button class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 dark:shadow-none">
                                💶 Registrar Pagamento
                            </button>

                            <div class="grid grid-cols-2 gap-2">
                                @php
                                    $cancellationRouteName = $reserva->is_recurrent ? 'admin.reservas.cancelar_pontual' : 'admin.reservas.cancelar';
                                    $actionLabel = $reserva->is_recurrent ? 'Cancelar Dia' : 'Cancelar';
                                @endphp
                                <button type="button"
                                        onclick="openCancellationModal('{{ $reserva->client_name }}', {{ $reserva->id }}, '{{ route($cancellationRouteName, $reserva->id) }}', '{{ $actionLabel }}')"
                                        class="bg-gray-100 text-gray-600 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-red-50 hover:text-red-600 transition-all">
                                    {{ $actionLabel }}
                                </button>

                                <form action="{{ route('admin.reservas.no_show', $reserva->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full bg-red-100 text-red-700 py-3 rounded-2xl font-black text-[10px] uppercase hover:bg-red-600 hover:text-white transition-all">
                                        No-Show 🚨
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if ($reserva->status !== $reserva::STATUS_PENDENTE && $reserva->status !== $reserva::STATUS_CONFIRMADA)
                            <div class="text-center py-4 px-2 bg-gray-50 dark:bg-gray-900 rounded-2xl border-2 border-dashed border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] font-black text-gray-400 uppercase leading-tight italic">Não há ações para reservas {{ $reserva->statusText }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MANTIDO SEU MODAL DE CANCELAMENTO E SCRIPT ORIGINAL --}}
    {{-- [Omitido por brevidade, mas deve permanecer exatamente como o seu original no final do arquivo] --}}
</x-app-layout>
