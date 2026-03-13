<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- Botão Voltar preservando o filtro de arena --}}
                <a href="{{ route('admin.financeiro.dashboard', ['arena_id' => request('arena_id')]) }}"
                    class="flex items-center gap-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-bold text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Painel
                </a>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    🏦 Caixa:
                    {{ request('arena_id') ? \App\Models\Arena::find(request('arena_id'))->name : 'Todas as Unidades' }}
                </h2>
            </div>
        </div>
    </x-slot>

    @php
        $traducaoMetodos = [
            'pix' => 'PIX',
            'credit_card' => 'Cartão de Crédito',
            'debit_card' => 'Cartão de Débito',
            'cash' => 'Dinheiro',
            'money' => 'Dinheiro',
            'transfer' => 'Transferência',
            'cash_out' => 'Saída/Sangria',
            'retained_funds' => 'Fundo Retido (Multa)',
            'outro' => 'Outros / Ajustes',
        ];

        $traducaoTipos = [
            'signal' => 'Sinal',
            'payment' => 'Pagamento',
            'full_payment' => 'Total',
            'payment_settlement' => 'Acerto de Saldo',
            'refund' => 'Estorno/Devolução',
            'reten_noshow_comp' => 'Multa No-Show',
            'reten_canc_comp' => 'Taxa de Cancelamento',
            'cash_out' => 'Retirada Manual',
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 🔍 FILTRO INSTANTÂNEO COM SELETOR DE ARENA --}}
            <div
                class="bg-white dark:bg-gray-800 p-6 shadow-sm rounded-xl border border-gray-100 dark:border-gray-700 print:hidden">
                <form id="caixaFilterForm" method="GET" action="{{ route('admin.financeiro.relatorio_caixa') }}"
                    class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                    <div class="md:col-span-1">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wider italic">🏟️
                            Unidade</label>
                        <select name="arena_id" onchange="this.form.submit()"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 font-bold text-sm">
                            <option value="">Todas as Arenas</option>
                            @foreach (\App\Models\Arena::all() as $arena)
                                <option value="{{ $arena->id }}"
                                    {{ request('arena_id') == $arena->id ? 'selected' : '' }}>{{ $arena->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-1">
                        <label for="dataInput"
                            class="block text-xs font-bold text-gray-500 uppercase mb-1 tracking-wider italic">📅 Data
                            do Caixa</label>
                        <input type="date" name="data" id="dataInput" value="{{ $data }}"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                    </div>

                    <div class="md:col-span-2 flex gap-2">
                        <a href="{{ route('admin.financeiro.relatorio_caixa', ['data' => now()->format('Y-m-d'), 'arena_id' => request('arena_id')]) }}"
                            class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg font-bold hover:bg-indigo-600 hover:text-white transition-all text-sm flex items-center justify-center">
                            Hoje
                        </a>
                        <button type="button" onclick="window.print()"
                            class="flex-1 bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-emerald-700 transition flex items-center justify-center gap-2 text-sm shadow-sm">
                            <span>🖨️</span> Imprimir Relatório
                        </button>
                    </div>
                </form>
            </div>

            {{-- 📄 RELATÓRIO --}}
            <div id="reportContent"
                class="bg-white dark:bg-gray-800 p-8 shadow-lg rounded-xl print:shadow-none print:p-0 border border-gray-100 dark:border-gray-700">

                <div class="flex justify-between items-start border-b-2 border-gray-100 dark:border-gray-700 pb-6 mb-8">
                    <div>
                        <h1 class="text-3xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">
                            Relatório de Caixa</h1>
                        <p class="text-gray-500 text-sm mt-1 uppercase font-bold">
                            {{ \Carbon\Carbon::parse($data)->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                        </p>
                        {{-- Badge Dinâmico da Arena --}}
                        @if (request('arena_id'))
                            <p class="text-indigo-600 dark:text-indigo-400 font-black text-xs uppercase mt-1 italic">
                                📍 Unidade:
                                {{ \App\Models\Arena::find(request('arena_id'))?->name ?? 'Não encontrada' }}
                            </p>
                        @endif
                    </div>
                    <div class="text-right">
                        @php
                            $caixaStatus = \App\Models\Cashier::where('date', $data)
                                ->when(request('arena_id'), fn($q) => $q->where('arena_id', request('arena_id')))
                                ->first();
                        @endphp
                        @if ($caixaStatus && $caixaStatus->status == 'closed')
                            <span
                                class="bg-green-100 text-green-700 px-4 py-1 rounded-full text-[10px] font-black uppercase border border-green-200 italic">✅
                                Caixa Conferido e Fechado</span>
                        @else
                            <span
                                class="bg-amber-100 text-amber-700 px-4 py-1 rounded-full text-[10px] font-black uppercase border border-amber-200 italic">🔓
                                Movimentação em Aberto</span>
                        @endif
                        <p class="text-[10px] text-gray-400 mt-2 italic font-mono uppercase tracking-tighter">Gerado em:
                            {{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                {{-- RESUMOS --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                    <div class="space-y-3">
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-2 h-2 bg-indigo-500 rounded-full"></span> Distribuição por Método
                        </h3>
                        <div
                            class="bg-gray-50 dark:bg-gray-900/40 p-4 rounded-xl space-y-2 border border-gray-100 dark:border-gray-700">
                            @forelse($movimentacoes->groupBy(fn($m) => (strtolower($m->payment_method) == 'money' ? 'cash' : strtolower($m->payment_method))) as $metodo => $transacoes)
                                <div
                                    class="flex justify-between items-center text-sm border-b border-gray-100 dark:border-gray-800 last:border-0 pb-1">
                                    <span
                                        class="text-gray-500 uppercase font-bold italic">{{ $traducaoMetodos[$metodo] ?? $metodo }}</span>
                                    <span class="font-mono font-bold dark:text-white text-lg">R$
                                        {{ number_format($transacoes->sum('amount'), 2, ',', '.') }}</span>
                                </div>
                            @empty
                                <p class="text-gray-400 text-xs italic text-center py-2 font-bold uppercase">Sem
                                    movimentações registradas.</p>
                            @endforelse
                        </div>
                    </div>

                    <div
                        class="bg-indigo-600 dark:bg-indigo-900 p-8 rounded-2xl flex flex-col justify-center items-center shadow-inner text-white relative overflow-hidden">
                        <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full"></div>
                        <span class="text-xs font-bold uppercase opacity-80 tracking-widest text-center italic">Saldo
                            Líquido Esperado</span>
                        <span class="text-4xl font-black mt-2">R$
                            {{ number_format($movimentacoes->sum('amount'), 2, ',', '.') }}</span>
                    </div>
                </div>

                {{-- HISTÓRICO DE AUDITORIA --}}
                <div class="mb-10">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-fuchsia-500 rounded-full"></span> Auditoria de Fechamento por Unidade
                    </h3>
                    <div
                        class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800 text-[10px] uppercase text-gray-400">
                                <tr>
                                    <th class="py-3 px-4 italic font-bold">Unidade</th>
                                    <th class="py-3 px-2">Operador Responsável</th>
                                    <th class="py-3 px-2 text-right">Sistema</th>
                                    <th class="py-3 px-2 text-right">Físico</th>
                                    <th class="py-3 px-4 text-right">Diferença/Quebra</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                                @forelse($cashierHistory as $hist)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                        <td class="py-3 px-4 font-bold text-gray-700 dark:text-gray-300 text-xs italic">
                                            {{ $hist->arena?->name ?? 'Geral' }}
                                        </td>
                                        <td class="py-3 px-2 text-gray-500 text-xs font-bold uppercase">
                                            {{ $hist->user->name ?? 'Sistema' }}</td>
                                        <td class="py-3 px-2 text-right text-gray-500 font-mono italic">R$
                                            {{ number_format($hist->calculated_amount, 2, ',', '.') }}</td>
                                        <td
                                            class="py-3 px-2 text-right text-gray-800 dark:text-gray-200 font-bold font-mono">
                                            R$ {{ number_format($hist->actual_amount, 2, ',', '.') }}</td>
                                        <td class="py-3 px-4 text-right font-black">
                                            @if ($hist->difference == 0)
                                                <span class="text-emerald-500 text-[11px]">CONFERIDO ✅</span>
                                            @elseif($hist->difference > 0)
                                                <span class="text-amber-500 text-[11px]">+ R$
                                                    {{ number_format($hist->difference, 2, ',', '.') }} (SOBRA)</span>
                                            @else
                                                <span class="text-red-500 text-[11px]">R$
                                                    {{ number_format($hist->difference, 2, ',', '.') }} (FALTA)
                                                    🚨</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="py-8 text-center text-gray-400 italic text-xs uppercase font-bold">
                                            Nenhum registro de conferência encontrado nesta data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- CRONOLÓGICO --}}
                <div class="mb-12 overflow-x-auto">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full"></span> Detalhamento Cronológico
                    </h3>
                    <table class="w-full text-left text-sm">
                        <thead
                            class="text-gray-400 uppercase text-[10px] font-bold border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3 px-2 italic text-center">Horário</th>
                                <th class="py-3 px-2">Unidade</th>
                                <th class="py-3 px-2">Identificação / Cliente</th>
                                <th class="py-3 px-2 text-center">Tipo de Lançamento</th>
                                <th class="py-3 px-2 text-center">Forma de Pagto</th>
                                <th class="py-3 px-2 text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                            @foreach ($movimentacoes as $m)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition duration-150">
                                    <td class="py-3 px-2 text-gray-400 font-mono text-xs text-center italic">
                                        {{ $m->paid_at->format('H:i') }}</td>
                                    <td class="py-3 px-2 text-[10px] font-bold text-gray-500 uppercase italic">
                                        {{ $m->arena?->name ?? '---' }}</td>
                                    <td class="py-3 px-2 pl-4">
                                        <div class="font-bold dark:text-gray-200">
                                            @if ($m->reserva_id)
                                                #{{ $m->reserva_id }}
                                            @else
                                                <span class="text-indigo-500">AVULSO</span>
                                            @endif
                                        </div>
                                        <div
                                            class="text-[10px] text-gray-500 truncate max-w-[180px] italic font-bold uppercase">
                                            {{ $m->reserva->client_name ?? ($m->description ?? 'Lançamento Manual') }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-2 text-center text-[9px] font-black uppercase">
                                        @php
                                            $tipoRaw = strtolower($m->type);
                                            $textoTipo = $traducaoTipos[$tipoRaw] ?? strtoupper($m->type);
                                            $corTipo = 'bg-gray-100 text-gray-600 border border-gray-200';

                                            if ($tipoRaw == 'signal') {
                                                $corTipo = 'bg-blue-50 text-blue-700 border border-blue-200';
                                            }
                                            if (
                                                in_array($tipoRaw, [
                                                    'full_payment',
                                                    'payment',
                                                    'payment_settlement',
                                                    'reforco',
                                                ])
                                            ) {
                                                $corTipo = 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                                            }
                                            if (in_array($tipoRaw, ['refund', 'sangria']) || $m->amount < 0) {
                                                $corTipo = 'bg-red-50 text-red-700 border border-red-200';
                                            }
                                            if (str_contains($tipoRaw, 'reten')) {
                                                $corTipo = 'bg-amber-50 text-amber-700 border border-amber-200';
                                            }
                                        @endphp
                                        <span class="px-2 py-1 rounded {{ $corTipo }}">
                                            {{ $textoTipo }}
                                        </span>
                                    </td>
                                    <td
                                        class="py-3 px-2 text-center text-[10px] text-gray-600 dark:text-gray-400 font-bold italic uppercase">
                                        {{ $traducaoMetodos[strtolower($m->payment_method)] ?? $m->payment_method }}
                                    </td>
                                    <td
                                        class="py-3 px-2 text-right font-mono font-bold {{ $m->amount < 0 ? 'text-red-500' : 'text-gray-800 dark:text-white' }}">
                                        {{ $m->amount < 0 ? '-' : '' }} R$
                                        {{ number_format(abs($m->amount), 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ASSINATURAS --}}
                <div class="mt-20 grid grid-cols-2 gap-16 text-center print:mt-10">
                    <div class="space-y-2">
                        <div class="border-b border-gray-400 dark:border-gray-600 w-full h-8"></div>
                        <p class="text-[10px] uppercase font-black text-gray-400 italic">Responsável pelo Caixa
                            (Operacional)</p>
                        @if ($caixaStatus && $caixaStatus->user)
                            <p class="text-[9px] text-indigo-500 font-bold italic uppercase">
                                {{ $caixaStatus->user->name }}</p>
                        @endif
                    </div>
                    <div class="space-y-2">
                        <div class="border-b border-gray-400 dark:border-gray-600 w-full h-8"></div>
                        <p class="text-[10px] uppercase font-black text-gray-400 italic">Conferência Gerencial
                            (Assinatura)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('caixaFilterForm').addEventListener('change', () => {
            document.getElementById('reportContent').style.opacity = '0.3';
        });
    </script>
</x-app-layout>
