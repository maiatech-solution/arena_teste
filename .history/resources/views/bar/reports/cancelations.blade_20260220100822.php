<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 py-10">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Auditoria de <span class="text-red-500">Cancelamentos</span>
                </h1>
                <p class="text-gray-500 font-bold text-xs uppercase tracking-widest mt-2">Controle financeiro e físico de perdas</p>
            </div>

            {{-- Filtro de Mês --}}
            <form action="{{ route('bar.reports.cancelations') }}" method="GET" class="flex items-center gap-2 bg-gray-900 p-2 rounded-2xl border border-gray-800">
                <input type="month" name="mes_referencia" value="{{ $mesReferencia }}"
                    class="bg-transparent border-none text-white font-bold text-sm focus:ring-0">
                <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white px-4 py-2 rounded-xl font-black text-[10px] uppercase transition-all">
                    Filtrar
                </button>
            </form>
        </div>

        {{-- Bloco 1: Estornos Financeiros --}}
        <div class="bg-gray-900 rounded-[2.5rem] border border-gray-800 shadow-2xl overflow-hidden mb-12">
            <div class="p-8 border-b border-gray-800 bg-black/20 flex justify-between items-center">
                <h2 class="text-xl font-black text-white uppercase italic">💰 Estornos de Caixa</h2>
                <div class="text-right">
                    <p class="text-[10px] font-black text-gray-500 uppercase">Total no Período</p>
                    <p class="text-2xl font-black text-red-500 italic">R$ {{ number_format($cancelamentosFinanceiros->sum('amount'), 2, ',', '.') }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-gray-500 text-[10px] font-black uppercase tracking-widest border-b border-gray-800 bg-black/10">
                            <th class="p-6">Data/Hora</th>
                            <th class="p-6">Descrição</th>
                            <th class="p-6">Operador</th>
                            <th class="p-6 text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @foreach($cancelamentosFinanceiros as $mov)
                            @php
                                $partesMotivo = explode(' | MOTIVO: ', $mov->description);
                                $titulo = $partesMotivo[0];
                                $resto = explode(' | POR: ', $partesMotivo[1] ?? '');
                                $motivo = $resto[0] ?? 'Não informado';
                                $autorizador = $resto[1] ?? 'N/A';
                            @endphp
                            <tr class="hover:bg-white/[0.02] transition-colors">
                                <td class="p-6 text-xs text-gray-500 font-bold">{{ $mov->created_at->format('d/m H:i') }}</td>
                                <td class="p-6">
                                    <span class="text-white block font-black text-xs uppercase">{{ $titulo }}</span>
                                    <span class="text-orange-400 text-[10px] font-bold italic block mt-1">💬 Motivo: {{ $motivo }}</span>
                                    <span class="text-indigo-400 text-[9px] font-black uppercase block mt-1 tracking-widest">🔐 Autorizado por: {{ $autorizador }}</span>
                                </td>
                                <td class="p-6 text-gray-400 text-xs font-bold uppercase">{{ $mov->user->name }}</td>
                                <td class="p-6 text-right font-black text-red-500 italic">- R$ {{ number_format($mov->amount, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Bloco 2: Movimentações de Estoque --}}
        <div class="bg-gray-900 rounded-[2.5rem] border border-gray-800 shadow-2xl overflow-hidden">
            <div class="p-8 border-b border-gray-800 bg-black/20">
                <h2 class="text-xl font-black text-white uppercase italic">📦 Movimentações de Estoque (Perdas/Estornos)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-gray-500 text-[10px] font-black uppercase tracking-widest border-b border-gray-800 bg-black/10">
                            <th class="p-6">Data</th>
                            <th class="p-6">Produto</th>
                            <th class="p-6 text-center">Qtd</th>
                            <th class="p-6">Tipo/Descrição</th>
                            <th class="p-6">Registrado por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/50">
                        @foreach($movimentacoesEstoque as $estorno)
                            <tr class="hover:bg-white/[0.02]">
                                <td class="p-6 text-xs text-gray-500 font-bold">{{ $estorno->created_at->format('d/m H:i') }}</td>
                                <td class="p-6 text-white font-black text-xs uppercase">{{ $estorno->product->name ?? 'Excluído' }}</td>
                                <td class="p-6 text-center">
                                    <span class="px-3 py-1 rounded-full font-black text-xs border {{ $estorno->type == 'loss' ? 'bg-red-500/10 text-red-500 border-red-500/20' : 'bg-blue-500/10 text-blue-500 border-blue-500/20' }}">
                                        {{ $estorno->type == 'loss' ? '-' : '+' }}{{ $estorno->quantity }}
                                    </span>
                                </td>
                                <td class="p-6">
                                    <span class="text-gray-400 text-xs italic">{{ $estorno->description }}</span>
                                </td>
                                <td class="p-6 text-gray-500 text-xs font-bold uppercase">{{ $estorno->user->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-bar-layout>
