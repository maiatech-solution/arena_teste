<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- Botão Voltar preservando o filtro de arena --}}
                <a href="{{ route('admin.financeiro.dashboard', ['arena_id' => request('arena_id')]) }}"
                    class="flex items-center gap-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition-all font-bold text-sm">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Painel
                </a>
                <h2 class="font-black text-xl text-gray-800 dark:text-gray-200 uppercase tracking-tighter italic">
                    🚫 Auditoria:
                    {{ request('arena_id') ? \App\Models\Arena::find(request('arena_id'))?->name : 'Todas as Unidades' }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 🔍 FILTROS --}}
            <div
                class="bg-white dark:bg-gray-800 p-6 shadow-sm rounded-xl border border-red-100 dark:border-red-900/30 print:hidden">
                <form id="lossFilterForm" method="GET"
                    action="{{ route('admin.financeiro.relatorio_cancelamentos') }}"
                    class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1 italic">🏟️
                            Unidade</label>
                        <select name="arena_id" onchange="this.form.submit()"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-red-500 font-bold text-sm">
                            <option value="">Todas as Arenas</option>
                            @foreach (\App\Models\Arena::all() as $arena)
                                <option value="{{ $arena->id }}"
                                    {{ request('arena_id') == $arena->id ? 'selected' : '' }}>{{ $arena->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1 italic">📅
                            Mês</label>
                        <select name="mes" onchange="this.form.submit()"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-red-500 font-bold text-sm">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}"
                                    {{ request('mes', now()->month) == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->locale('pt_BR')->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1 italic">📆
                            Ano</label>
                        <select name="ano" onchange="this.form.submit()"
                            class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-red-500 font-bold text-sm">
                            @foreach (range(now()->year - 1, now()->year + 1) as $a)
                                <option value="{{ $a }}"
                                    {{ request('ano', now()->year) == $a ? 'selected' : '' }}>{{ $a }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" onclick="window.print()"
                        class="bg-gray-800 text-white px-6 py-2 rounded-lg font-bold hover:bg-black transition shadow-md text-sm uppercase">
                        🖨️ Imprimir Auditoria
                    </button>
                </form>
            </div>

            {{-- 📊 RESUMO DE IMPACTO --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                {{-- Card 1: Faltas (No-Show) --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border-l-4 border-red-500 shadow-sm">
                    <div class="flex justify-between items-start">
                        <p class="text-[10px] font-black text-gray-400 uppercase italic">Faltas (No-Show)</p>
                        {{-- ✅ CORREÇÃO: Troquei countRejeitadas por countFaltas e amber por red --}}
                        <span class="bg-red-100 text-red-600 text-[10px] font-black px-2 py-0.5 rounded-full">
                            {{ $countFaltas }} Reservas
                        </span>
                    </div>
                    <p class="text-2xl font-black text-red-600 italic">
                        R$ {{ number_format($prejuizoFaltasReal, 2, ',', '.') }}
                    </p>
                </div>

                {{-- Card 2: Cancelamentos --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border-l-4 border-gray-400 shadow-sm">
                    <div class="flex justify-between items-start">
                        <p class="text-[10px] font-black text-gray-400 uppercase italic">Cancelamentos</p>
                        <span
                            class="bg-gray-100 text-gray-600 text-[10px] font-black px-2 py-0.5 rounded-full">{{ $countCancelamentos }}
                            Jogos</span>
                    </div>
                    <p class="text-2xl font-black text-gray-700 dark:text-gray-200 italic">
                        R$
                        {{ number_format($cancelamentos->where('status', \App\Models\Reserva::STATUS_CANCELADA)->sum('price'), 2, ',', '.') }}
                    </p>
                </div>

                {{-- Card 3: Rejeitadas --}}
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border-l-4 border-amber-500 shadow-sm">
                    <div class="flex justify-between items-start">
                        <p class="text-[10px] font-black text-gray-400 uppercase italic">Rejeitadas/Negadas</p>
                        <span
                            class="bg-amber-100 text-amber-600 text-[10px] font-black px-2 py-0.5 rounded-full">{{ $countRejeitadas }}
                            Reservas</span>
                    </div>
                    <p class="text-2xl font-black text-amber-600 italic">
                        R$
                        {{ number_format($cancelamentos->where('status', \App\Models\Reserva::STATUS_REJEITADA)->sum('price'), 2, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- 📄 CONTEÚDO --}}
            <div id="reportContent"
                class="bg-white dark:bg-gray-800 p-8 shadow-lg rounded-xl border border-gray-100 dark:border-gray-700 italic">

                <div class="flex justify-between items-start border-b-2 border-red-50 dark:border-red-900/20 pb-6 mb-8">
                    <div>
                        <h1 class="text-3xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">
                            Detalhamento de Perdas</h1>
                        <p class="text-gray-500 text-sm font-bold uppercase mt-1">
                            {{-- CORREÇÃO DO ERRO CARBON: (int) cast aplicado para PHP 8.3 --}}
                            Período:
                            {{ \Carbon\Carbon::create()->month((int) request('mes', now()->month))->locale('pt_BR')->translatedFormat('F') }}
                            / {{ request('ano', now()->year) }}
                        </p>
                        @if (request('arena_id'))
                            <p class="text-red-500 font-black text-[10px] uppercase mt-1">📍 Unidade:
                                {{ \App\Models\Arena::find(request('arena_id'))?->name }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-1">
                            Total de Ocorrências
                        </span>
                        <span class="text-4xl font-black text-red-600">
                            {{ $countFaltas + $countRejeitadas }}
                        </span>
                    </div>
                </div>

                {{-- TABELA --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 text-[10px] font-black uppercase tracking-widest">
                                <th class="p-4 rounded-l-lg text-center">Data / Horário</th>
                                <th class="p-4">Arena</th>
                                <th class="p-4">Cliente / Contato</th>
                                <th class="p-4 text-center">Classificação</th>
                                <th class="p-4 text-right rounded-r-lg">Prejuízo (R$)</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y dark:divide-gray-700 font-bold">

                            @foreach ($cancelamentos as $c)
                                @php
                                    // 1. Busca transações diretas (Aqui é onde o Voucher entra para abater o prejuízo)
                                    $vouchersAbate = (float) $c->transactions
                                        ->where('payment_method', 'voucher')
                                        ->sum('amount');

                                    // 2. Busca o vínculo financeiro no caixa (Multas/Sinais retidos) usando o ID na descrição
                                    $vinc = $multasAvulsas->filter(
                                        fn($m) => str_contains($m->description, "#{$c->id}"),
                                    );
                                    $saldoFin = (float) $vinc->sum('amount');

                                    // 3. Cálculo do prejuízo líquido REAL:
                                    // Preço Base - (Multas de Caixa + Valor em Vouchers)
                                    // Usamos max(0, ...) para garantir que o prejuízo nunca seja negativo
                                    $prejuizoLinha = max(0, (float) $c->price - ($saldoFin + $vouchersAbate));
                                @endphp

                                <tr class="hover:bg-red-50/30 dark:hover:bg-red-900/10 transition duration-150">
                                    <td class="p-4 dark:text-gray-300 text-center">
                                        {{ \Carbon\Carbon::parse($c->date)->format('d/m/Y') }}
                                        <div class="text-[10px] text-gray-400 font-black uppercase italic">
                                            {{ \Carbon\Carbon::parse($c->start_time)->format('H:i') }}h
                                        </div>
                                    </td>

                                    <td class="p-4 text-xs text-gray-500 uppercase italic">
                                        {{ $c->arena->name ?? 'Unidade' }}
                                    </td>

                                    <td class="p-4">
                                        <div class="font-black dark:text-white uppercase">{{ $c->client_name }}</div>
                                        <div
                                            class="text-[10px] text-indigo-500 font-mono italic flex items-center gap-2">
                                            {{ $c->client_contact ?? 'S/ Contato' }}

                                            {{-- Labels de Auditoria --}}

                                            {{-- 🎟️ NOVO: Alerta de Voucher --}}
                                            @if ($vouchersAbate > 0)
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-black bg-amber-100 text-amber-600 border border-amber-200 uppercase tracking-tighter">
                                                    🎟️ Voucher (R$ {{ number_format($vouchersAbate, 2, ',', '.') }})
                                                </span>
                                            @endif

                                            {{-- Caso de Estorno --}}
                                            @if ($vinc->contains(fn($m) => $m->amount < 0))
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-black bg-gray-100 text-gray-500 border border-gray-200 uppercase tracking-tighter">
                                                    Sinal Estornado
                                                </span>
                                                {{-- Caso de Multa Retida --}}
                                            @elseif($saldoFin > 0)
                                                <span
                                                    class="inline-flex items-center px-1.5 py-0.5 rounded text-[8px] font-black bg-green-100 text-green-600 border border-green-200 uppercase tracking-tighter">
                                                    Multa Retida (R$ {{ number_format($saldoFin, 2, ',', '.') }})
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="p-4 text-center">
                                        @if ($c->status == 'no_show')
                                            <span
                                                class="bg-red-600 text-white px-3 py-1 rounded text-[9px] font-black uppercase shadow-md">🚨
                                                No-Show</span>
                                        @elseif($c->status == 'rejected')
                                            <span
                                                class="bg-amber-500 text-white px-3 py-1 rounded text-[9px] font-black uppercase shadow-md">⚠️
                                                Rejeitada</span>
                                        @else
                                            <span
                                                class="bg-gray-400 text-white px-3 py-1 rounded text-[9px] font-black uppercase italic shadow-md">✕
                                                Cancelada</span>
                                        @endif
                                    </td>

                                    <td class="p-4 text-right font-mono font-black text-red-500 text-lg italic">
                                        R$ {{ number_format($prejuizoLinha, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Linhas Avulsas do Caixa (Sem vínculo com as reservas acima) --}}
                            @foreach ($multasAvulsas as $m)
                                @php
                                    $vinculado = $cancelamentos->contains(
                                        fn($can) => str_contains($m->description, "#{$can->id}"),
                                    );
                                @endphp
                                @if (!$vinculado)
                                    <tr class="bg-gray-50/50 dark:bg-gray-800/50 border-l-4 border-gray-300">
                                        <td class="p-4 text-center">
                                            {{ \Carbon\Carbon::parse($m->paid_at)->format('d/m/Y') }}
                                            <div class="text-[10px] text-gray-400 font-black uppercase italic">
                                                Financeiro</div>
                                        </td>
                                        <td class="p-4 text-xs text-gray-500 uppercase italic">
                                            {{ $m->arena->name ?? 'Geral' }}</td>
                                        <td class="p-4">
                                            <div class="font-black dark:text-white uppercase">
                                                {{ str_replace(['Multa', '#', 'No-Show'], '', $m->description) }}</div>
                                            <div class="text-[10px] text-gray-400 font-mono italic">Lançamento Avulso
                                            </div>
                                        </td>
                                        <td class="p-4 text-center">
                                            <span
                                                class="bg-gray-500 text-white px-3 py-1 rounded text-[9px] font-black uppercase">💰
                                                Ajuste</span>
                                        </td>
                                        <td class="p-4 text-right font-mono font-black text-gray-400 text-lg italic">
                                            R$ {{ number_format($m->amount, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- RESUMO FINANCEIRO TOTAL --}}
                <div
                    class="mt-10 p-8 bg-black dark:bg-gray-900 rounded-2xl flex justify-between items-center text-white relative overflow-hidden">
                    {{-- Efeito decorativo de fundo --}}
                    <div class="absolute -left-4 -bottom-4 w-32 h-32 bg-red-600/10 rounded-full"></div>

                    <div>
                        <p class="uppercase text-[10px] font-black tracking-[0.3em] opacity-60 italic">
                            Impacto Total em Receita Não Realizada
                        </p>
                        <p class="text-xs italic opacity-80">
                            Soma de cancelamentos, faltas e agendamentos rejeitados pela arena (incluindo multas de
                            caixa).
                        </p>
                    </div>

                    <div class="text-4xl font-black italic text-red-500 z-10">
                        {{-- Mostra o prejuízo líquido: (Preço das Faltas - Multas/Estornos) --}}
                        R$ {{ number_format($prejuizoFaltasReal, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Estilos exclusivos para a Impressão --}}
    <style>
        @media print {

            /* 1. Esconde elementos de navegação e interface do sistema */
            nav,
            header,
            aside,
            footer,
            .print\:hidden,
            #lossFilterForm,
            a[href*="dashboard"] {
                display: none !important;
            }

            /* 2. Reseta o fundo da página para branco e remove sombras */
            body {
                background-color: white !important;
                color: black !important;
            }

            .py-12 {
                padding-top: 0 !important;
                padding-bottom: 0 !important;
            }

            .max-w-6xl {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            /* 3. Estiliza o quadro do relatório para ocupar a folha inteira */
            #reportContent {
                box-shadow: none !important;
                border: 1px solid #eee !important;
                padding: 1cm !important;
                width: 100% !important;
            }

            /* 4. Força a impressão das cores (importante para os badges e rodapé preto) */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* 5. Ajuste de tabelas para não quebrarem no meio de uma linha */
            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>

    <script>
        document.getElementById('lossFilterForm').addEventListener('change', () => {
            document.getElementById('reportContent').style.opacity = '0.3';
        });
    </script>
</x-app-layout>
