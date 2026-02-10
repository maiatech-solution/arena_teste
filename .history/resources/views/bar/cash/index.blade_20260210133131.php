<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        {{-- HEADER COM FILTRO DE DATA --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-10">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-white text-4xl font-black uppercase italic tracking-tighter">Gestão de <span
                            class="text-green-500">Caixa</span></h1>
                    <span
                        class="px-3 py-1 bg-gray-800 text-gray-500 text-[10px] font-black rounded-lg uppercase border border-gray-700">Módulo
                        Bar</span>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <form action="{{ route('bar.cash.index') }}" method="GET" id="filterForm"
                        class="flex items-center gap-2">
                        <input type="date" name="date" value="{{ $date ?? date('Y-m-d') }}"
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-gray-900 border-2 border-gray-800 rounded-xl px-4 py-2 text-white text-xs font-black outline-none focus:border-green-500 transition-all">

                        @if (isset($date) && $date != date('Y-m-d'))
                            <a href="{{ route('bar.cash.index') }}"
                                class="text-[10px] font-black text-orange-500 uppercase underline tracking-widest ml-2">Voltar
                                para Hoje</a>
                        @endif
                    </form>
                </div>
            </div>

            {{-- BOTÕES DE AÇÃO: Só aparecem se houver uma sessão ABERTA agora --}}
            @if ($openSession)
                <div class="flex flex-wrap gap-3">
                    <button onclick="openModalMovement('sangria')"
                        class="px-6 py-3 bg-red-600/10 border border-red-600/20 text-red-500 font-bold rounded-2xl uppercase text-xs hover:bg-red-600 hover:text-white transition-all shadow-lg">
                        🔻 Sangria
                    </button>
                    <button onclick="openModalMovement('reforco')"
                        class="px-6 py-3 bg-blue-600/10 border border-blue-600/20 text-blue-500 font-bold rounded-2xl uppercase text-xs hover:bg-blue-600 hover:text-white transition-all shadow-lg">
                        🔺 Reforço
                    </button>
                    <button onclick="openModalClosing()"
                        class="px-8 py-3 bg-white text-black font-black rounded-2xl uppercase text-[10px] tracking-widest hover:scale-105 transition-all shadow-xl border-b-4 border-gray-300">
                        🔒 Encerrar Turno
                    </button>
                </div>
            @endif
        </div>

        {{-- LÓGICA DE EXIBIÇÃO CENTRAL --}}
        @if (!$openSession && $date == date('Y-m-d'))
            {{-- SE NÃO TEM CAIXA ABERTO HOJE: Mostra opção de abrir NOVO TURNO --}}
            <div class="max-w-xl mx-auto mt-20 text-center animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-gray-900 rounded-[3rem] p-12 border border-gray-800 shadow-2xl shadow-green-900/5">
                    <div
                        class="w-20 h-20 bg-gray-800 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-gray-700 text-4xl text-gray-400">
                        🔓</div>
                    <h2 class="text-white text-2xl font-black uppercase mb-2">Novo Turno</h2>
                    <p class="text-gray-500 mb-8 uppercase text-[10px] font-bold tracking-widest leading-relaxed px-10">
                        Não há sessões de caixa ativas no momento. <br>Inicie um novo turno para processar vendas.
                    </p>

                    <form action="{{ route('bar.cash.open') }}" method="POST">
                        @csrf
                        <div class="text-left mb-6">
                            <label
                                class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest">Troco
                                Inicial de Gaveta</label>
                            <input type="number" name="opening_balance" step="0.01" value="0.00" required
                                class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-3xl font-black text-center focus:border-green-500 outline-none transition-all shadow-inner">
                        </div>
                        <button type="submit"
                            class="w-full py-6 bg-green-600 hover:bg-green-500 text-white font-black rounded-3xl uppercase tracking-widest shadow-lg shadow-green-900/40 transition-all active:scale-95">
                            Abrir Turno de Trabalho
                        </button>
                    </form>
                </div>
            </div>
        @elseif(!$currentSession)
            {{-- SE NÃO HOUVER SESSÃO NA DATA FILTRADA --}}
            <div class="py-20 text-center opacity-20">
                <p class="text-gray-600 font-black uppercase tracking-widest italic text-3xl">Nenhum registo nesta data
                </p>
            </div>
        @else
            {{-- CARDS FINANCEIROS (Baseados na $currentSession selecionada) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div
                    class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 relative overflow-hidden group shadow-2xl border-l-4 border-l-green-500">
                    <span
                        class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Dinheiro
                        em Gaveta</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">
                        R$ {{ number_format($dinheiroGeral ?? 0, 2, ',', '.') }}
                    </span>
                    <p class="text-[9px] text-gray-600 mt-2 font-bold uppercase tracking-tighter italic">Soma em espécie
                        do turno atual.</p>
                </div>

                <div
                    class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl border-l-4 border-l-blue-500">
                    <span
                        class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Aportes
                        / Reforços</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">
                        R$ {{ number_format($reforcos ?? 0, 2, ',', '.') }}
                    </span>
                </div>

                <div
                    class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl border-l-4 border-l-red-500">
                    <span
                        class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Sangrias
                        / Saídas</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">
                        R$ {{ number_format($sangrias ?? 0, 2, ',', '.') }}
                    </span>
                </div>

                <div
                    class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl border-l-4 border-l-blue-400">
                    <span
                        class="text-[10px] font-black text-blue-400 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Faturamento
                        Digital</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">
                        R$ {{ number_format($faturamentoDigital ?? 0, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- AUDITORIA DE FECHAMENTO --}}
            @if ($currentSession->status == 'closed')
                <div
                    class="bg-orange-600/5 border border-orange-600/20 p-8 rounded-[3rem] mb-10 flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-orange-600"></div>
                    <div>
                        <h4 class="text-orange-500 font-black uppercase text-xs italic tracking-[0.2em] mb-4">Turno
                            Encerrado - Auditoria</h4>
                        <div class="flex flex-wrap gap-12">
                            <div>
                                <p class="text-gray-500 text-[9px] font-black uppercase mb-1">Esperado Sistema</p>
                                <p class="text-white font-black text-2xl italic">R$
                                    {{ number_format($currentSession->expected_balance, 2, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-[9px] font-black uppercase mb-1">Contado Físico</p>
                                <p class="text-white font-black text-2xl italic">R$
                                    {{ number_format($currentSession->closing_balance, 2, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 text-[9px] font-black uppercase mb-1">Quebra</p>
                                @php $diff = $currentSession->closing_balance - $currentSession->expected_balance; @endphp
                                <p
                                    class="font-black text-2xl italic {{ $diff < 0 ? 'text-red-500' : ($diff > 0 ? 'text-green-500' : 'text-blue-500') }}">
                                    R$ {{ number_format($diff, 2, ',', '.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-3">
                        <div class="bg-gray-950 p-6 rounded-3xl border border-gray-800 min-w-[300px]">
                            <p class="text-gray-400 text-xs italic font-medium leading-relaxed">
                                "{{ $currentSession->notes ?? 'Sem observações.' }}"</p>
                        </div>
                        {{-- BOTÃO REABRIR (Admin) --}}
                        @if (auth()->user()->hasRole('admin') && !$openSession)
                            <form action="{{ route('bar.cash.reopen', $currentSession->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="text-[9px] font-black text-orange-500 uppercase underline tracking-widest hover:text-orange-400 transition-colors">
                                    ⚠️ Reabrir este caixa
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- LISTA DE MOVIMENTAÇÕES --}}
            <div class="bg-gray-900 rounded-[3rem] border border-gray-800 overflow-hidden shadow-2xl">
                <div class="p-8 border-b border-gray-800 flex justify-between items-center bg-gray-800/20">
                    <h3 class="text-white font-black uppercase italic tracking-widest text-lg">Histórico do Turno</h3>
                    <div class="flex items-center gap-4">
                        <span
                            class="text-[10px] text-gray-600 font-bold uppercase tracking-tighter italic font-black underline decoration-green-500/30 underline-offset-4">Faturado:
                            R$ {{ number_format($totalBruto ?? 0, 2, ',', '.') }}</span>
                        @if ($currentSession->status == 'open')
                            <span
                                class="text-green-500 text-[10px] font-black uppercase tracking-widest animate-pulse flex items-center gap-2">
                                <span class="w-2 h-2 bg-green-500 rounded-full"></span> Aberto
                            </span>
                        @else
                            <span
                                class="text-red-500 text-[10px] font-black uppercase tracking-widest flex items-center gap-2 font-black">
                                <span class="w-2 h-2 bg-red-500 rounded-full"></span> Fechado
                            </span>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-gray-500 text-[10px] font-black uppercase tracking-widest border-b border-gray-800 bg-black/20">
                                <th class="p-6">Hora</th>
                                <th class="p-6">Descrição</th>
                                <th class="p-6">Operador</th>
                                <th class="p-6 text-right font-black">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                            @forelse($movements as $mov)
                                <tr class="hover:bg-white/[0.02] transition-colors group">
                                    <td class="p-6 text-gray-500 font-bold text-xs">
                                        {{ $mov->created_at->format('H:i') }}</td>

                                    <td class="p-6">
                                        <span
                                            class="text-white block font-black text-xs uppercase tracking-tight">{{ $mov->description }}</span>

                                        <div class="flex items-center gap-2 mt-1">
                                            {{-- BADGE DO TIPO (Sangria, Reforço, Venda) --}}
                                            <span
                                                class="text-[8px] uppercase font-black px-2 py-0.5 rounded border {{ $mov->type == 'sangria' ? 'bg-red-500/10 text-red-500 border-red-500/20' : ($mov->type == 'reforco' ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' : 'bg-green-500/10 text-green-500 border-green-500/20') }}">
                                                {{ $mov->type == 'sangria' ? '🔻' : ($mov->type == 'reforco' ? '🔺' : '💰') }}
                                                {{ $mov->type }}
                                            </span>

                                            {{-- BADGE DO MÉTODO (Dinheiro, PIX, Cartão) --}}
                                            @php
                                                $methodData = match ($mov->payment_method) {
                                                    'dinheiro' => [
                                                        'icon' => '💵',
                                                        'label' => 'Dinheiro',
                                                        'class' => 'text-green-500',
                                                    ],
                                                    'pix' => [
                                                        'icon' => '📱',
                                                        'label' => 'PIX',
                                                        'class' => 'text-cyan-400',
                                                    ],
                                                    'debito' => [
                                                        'icon' => '💳',
                                                        'label' => 'Débito',
                                                        'class' => 'text-purple-400',
                                                    ],
                                                    'credito' => [
                                                        'icon' => '💳',
                                                        'label' => 'Crédito',
                                                        'class' => 'text-purple-400',
                                                    ],
                                                    default => [
                                                        'icon' => '❓',
                                                        'label' => $mov->payment_method,
                                                        'class' => 'text-gray-500',
                                                    ],
                                                };
                                            @endphp
                                            <span
                                                class="text-[8px] font-black uppercase italic {{ $methodData['class'] }} flex items-center gap-1">
                                                {{ $methodData['icon'] }} {{ $methodData['label'] }}
                                            </span>
                                        </div>
                                    </td>

                                    <td
                                        class="p-6 text-gray-400 text-[10px] font-bold uppercase tracking-tighter italic tracking-widest">
                                        {{ $mov->user->name }}</td>

                                    <td
                                        class="p-6 text-right font-black italic text-xl {{ $mov->type == 'sangria' ? 'text-red-500' : 'text-white' }}">
                                        {{ $mov->type == 'sangria' ? '-' : '' }} R$
                                        {{ number_format($mov->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-24 text-center opacity-20">
                                        <p class="text-gray-600 font-black uppercase tracking-widest italic text-3xl">
                                            Sem movimentações</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @include('bar.cash.modals.movements')
    @include('bar.cash.modals.closing')
</x-bar-layout>
