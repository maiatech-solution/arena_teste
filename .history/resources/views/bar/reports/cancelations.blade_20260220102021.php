<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 py-10">

        {{-- CABEÇALHO COM BOTÃO VOLTAR --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('bar.reports.index') }}"
                    class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg group">
                    <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block">◀</span>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                        Auditoria de <span class="text-red-500">Cancelamentos</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-xs uppercase tracking-widest mt-1">Gestão de perdas, quebras e
                        estornos autorizados</p>
                </div>
            </div>

            {{-- Filtro de Mês --}}
            <form action="{{ route('bar.reports.cancelations') }}" method="GET"
                class="flex items-center gap-2 bg-gray-900 p-2 rounded-2xl border border-gray-800">
                <input type="month" name="mes_referencia" value="{{ $mesReferencia }}"
                    class="bg-transparent border-none text-white font-bold text-sm focus:ring-0 cursor-pointer">
                <button type="submit"
                    class="bg-orange-600 hover:bg-orange-50 text-white px-5 py-2 rounded-xl font-black text-[10px] uppercase transition-all shadow-lg active:scale-95">
                    Filtrar
                </button>
            </form>
        </div>

        {{-- Cards de Resumo --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">

            {{-- Card Estornos --}}
            <div class="bg-gray-900 border border-gray-800 p-8 rounded-[2rem] shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <span class="p-3 bg-orange-600/10 text-orange-500 rounded-2xl">💰</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Estornos de
                        Caixa</span>
                </div>
                <h3 class="text-3xl font-black text-white italic">
                    R$ {{ number_format($cancelamentosFinanceiros->sum('amount'), 2, ',', '.') }}
                </h3>
                <p class="text-gray-500 text-[10px] font-bold uppercase mt-1">Dinheiro devolvido aos clientes</p>
            </div>

            {{-- Card Prejuízo Real --}}
            <div
                class="bg-gray-900 border border-red-500/30 p-8 rounded-[2rem] shadow-xl bg-gradient-to-br from-red-500/5 to-transparent">
                <div class="flex items-center justify-between mb-4">
                    <span class="p-3 bg-red-600/10 text-red-500 rounded-2xl animate-pulse">📉</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Prejuízo em
                        Produtos</span>
                </div>
                <h3 class="text-3xl font-black text-white italic">
                    R$ {{ number_format($valorTotalPerdas, 2, ',', '.') }}
                </h3>
                <p class="text-gray-500 text-[10px] font-bold uppercase mt-1">Custo de mercadoria jogada fora</p>
            </div>

        </div>

        {{-- BLOCO 1: ESTORNOS FINANCEIROS (O que saiu do dinheiro) --}}
        <div class="bg-gray-900 rounded-[2.5rem] border border-gray-800 shadow-2xl overflow-hidden mb-12">
            <div class="p-8 border-b border-gray-800 bg-black/20 flex justify-between items-center">
                <h2 class="text-xl font-black text-white uppercase italic flex items-center gap-3">
                    <span class="text-2xl">💰</span> Estornos de Caixa
                </h2>
                <div class="text-right">
                    <p class="text-[10px] font-black text-gray-500 uppercase">Total Devolvido</p>
                    <p class="text-2xl font-black text-red-500 italic">R$
                        {{ number_format($cancelamentosFinanceiros->sum('amount'), 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="text-gray-500 text-[10px] font-black uppercase tracking-widest border-b border-gray-800 bg-black/10">
                            <th class="p-6">Data/Hora</th>
                            <th class="p-6">Descrição da Transação</th>
                            <th class="p-6">Operador</th>
                            <th class="p-6 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @foreach ($cancelamentosFinanceiros as $mov)
                            @php
                                $partesMotivo = explode(' | MOTIVO: ', $mov->description);
                                $titulo = $partesMotivo[0];
                                $resto = explode(' | POR: ', $partesMotivo[1] ?? '');
                                $motivo = $resto[0] ?? 'Não informado';
                                $autorizador = $resto[1] ?? 'N/A';
                            @endphp
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-6 text-xs text-gray-500 font-bold">
                                    {{ $mov->created_at->format('d/m H:i') }}</td>
                                <td class="p-6">
                                    <span
                                        class="text-white block font-black text-xs uppercase">{{ $titulo }}</span>
                                    <span class="text-orange-400 text-[10px] font-bold italic block mt-1">💬 Motivo:
                                        {{ $motivo }}</span>
                                    <span
                                        class="text-indigo-400 text-[9px] font-black uppercase block mt-1 tracking-widest">🔐
                                        Autorizado por: {{ $autorizador }}</span>
                                </td>
                                <td class="p-6 text-gray-400 text-xs font-bold uppercase">{{ $mov->user->name }}</td>
                                <td class="p-6 text-right font-black text-red-500 italic">- R$
                                    {{ number_format($mov->amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BLOCO 2: PERDAS REAIS (Vencidos, Quebrados, Desperdício) --}}
        <div
            class="bg-gray-900 rounded-[2.5rem] border border-red-500/30 shadow-2xl overflow-hidden mb-12 bg-gradient-to-b from-red-500/5 to-transparent">
            <div class="p-8 border-b border-gray-800 flex justify-between items-center">
                <h2 class="text-xl font-black text-white uppercase italic flex items-center gap-3">
                    <span class="text-2xl">🗑️</span> Prejuízo Físico (Perdas)
                </h2>
                <span
                    class="bg-red-600 text-white text-[9px] font-black px-3 py-1 rounded-full animate-pulse tracking-widest">PERDAS</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="text-gray-500 text-[10px] font-black uppercase tracking-widest border-b border-gray-800 bg-red-900/10">
                            <th class="p-6">Data</th>
                            <th class="p-6">Produto</th>
                            <th class="p-6 text-center">Quantidade</th>
                            <th class="p-6">Justificativa da Perda</th>
                            <th class="p-6">Responsável</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @forelse($perdasReais as $perda)
                            <tr class="hover:bg-red-500/5 transition-colors">
                                <td class="p-6 text-xs text-gray-500 font-bold">
                                    {{ $perda->created_at->format('d/m H:i') }}
                                </td>
                                <td class="p-6 text-white font-black text-xs uppercase tracking-tight">
                                    {{ $perda->product->name ?? 'Produto Excluído' }}
                                </td>
                                <td class="p-6 text-center">
                                    {{-- abs() garante que não apareça sinal duplo se o valor já for negativo no banco --}}
                                    <span class="text-red-500 font-black text-xl">
                                        -{{ abs($perda->quantity) }}
                                    </span>
                                </td>
                                <td class="p-6 text-gray-400 text-xs italic">
                                    {{-- Limpa o prefixo "PERDA: " caso ele já exista na string salva --}}
                                    {{ str_replace('PERDA: ', '', $perda->description) }}
                                </td>
                                <td class="p-6 text-gray-500 text-xs font-bold uppercase">
                                    {{ $perda->user->name }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-16 text-center text-gray-700 font-black uppercase italic">
                                    Nenhuma perda física registrada neste período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- BLOCO 3: RETORNOS AO ESTOQUE (Cancelamento de Venda) --}}
        <div
            class="bg-gray-900 rounded-[2.5rem] border border-gray-800 shadow-xl overflow-hidden opacity-60 hover:opacity-100 transition-opacity duration-500">
            <div class="p-8 border-b border-gray-800 bg-black/20">
                <h2 class="text-lg font-black text-gray-500 uppercase italic flex items-center gap-3">
                    <span class="text-xl">🔄</span> Itens que Voltaram ao Estoque
                </h2>
                <p class="text-[9px] text-gray-600 font-bold uppercase mt-1">Produtos que retornaram à prateleira após
                    cancelamento de mesa ou PDV</p>
            </div>
            <div class="max-h-96 overflow-y-auto">
                <table class="w-full text-left">
                    <tbody class="divide-y divide-gray-800/50">
                        @foreach ($retornosEstoque as $retorno)
                            <tr class="hover:bg-white/[0.01]">
                                <td class="p-5 text-[11px] text-gray-600 font-bold">
                                    {{ $retorno->created_at->format('d/m H:i') }}</td>
                                <td class="p-5 text-[11px] text-gray-400 font-black uppercase italic">
                                    {{ $retorno->product->name }}</td>
                                <td class="p-5 text-center">
                                    <span class="text-green-500 font-black text-xs">+{{ $retorno->quantity }}</span>
                                </td>
                                <td class="p-5 text-[10px] text-gray-600 italic uppercase">{{ $retorno->description }}
                                </td>
                                <td class="p-5 text-[10px] text-gray-600 text-right">{{ $retorno->user->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-bar-layout>
