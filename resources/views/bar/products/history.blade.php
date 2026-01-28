<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <a href="{{ route('bar.products.index') }}" class="text-orange-500 hover:text-orange-400 text-sm font-bold flex items-center gap-2 mb-4 transition-colors">
                    ⬅️ VOLTAR PARA ESTOQUE
                </a>
                <h2 class="text-3xl font-black text-white uppercase tracking-tighter">📜 Histórico de <span class="text-orange-500">Movimentações</span></h2>
                <p class="text-gray-500 text-sm font-medium">Registro cronológico de entradas, saídas e ajustes do inventário.</p>
            </div>

            <div class="bg-gray-900 border border-gray-800 px-6 py-4 rounded-2xl shadow-xl">
                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-1">Total de Registros</span>
                <span class="text-2xl font-black text-white">{{ $movements->total() }}</span>
            </div>
        </div>

        <div class="bg-gray-900 rounded-3xl border border-gray-800 overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-800/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="p-4">Data/Hora</th>
                        <th class="p-4">Produto</th>
                        <th class="p-4 text-center">Tipo</th>
                        <th class="p-4 text-center">Movimentação</th>
                        <th class="p-4 text-right">Responsável</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800 text-sm">
                    @forelse($movements as $mov)
                    <tr class="hover:bg-gray-950/50 transition">
                        <td class="p-4 text-gray-500 font-mono text-xs">
                            <span class="text-gray-300 font-bold">{{ $mov->created_at->format('d/m/Y') }}</span><br>
                            <span class="text-[10px] opacity-70">{{ $mov->created_at->format('H:i:s') }}</span>
                        </td>

                        <td class="p-4">
                            <div class="flex flex-col">
                                <span class="text-white font-black uppercase tracking-tight text-sm">{{ $mov->product->name }}</span>
                                <span class="text-[10px] text-gray-500 italic font-medium leading-tight mt-1">
                                    💬 {{ $mov->description ?? 'Sem observações registradas' }}
                                </span>
                            </div>
                        </td>

                        <td class="p-4 text-center">
                            <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase
                                {{ $mov->type == 'entrada' ? 'bg-green-950 text-green-500 border border-green-500/20' : '' }}
                                {{ $mov->type == 'saida' || $mov->type == 'perda' ? 'bg-red-950 text-red-500 border border-red-500/20' : '' }}
                                {{ $mov->type == 'venda' ? 'bg-blue-950 text-blue-500 border border-blue-500/20' : '' }}">
                                {{ $mov->type }}
                            </span>
                        </td>

                        <td class="p-4 text-center">
                            <span class="font-black text-lg {{ $mov->quantity > 0 ? 'text-green-500' : 'text-red-500' }}">
                                {{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}
                            </span>
                        </td>

                        <td class="p-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="text-white font-bold uppercase text-[11px]">{{ $mov->user->name }}</span>
                                <span class="text-[9px] text-gray-500 uppercase tracking-tighter">Colaborador</span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-20 text-center">
                            <div class="flex flex-col items-center">
                                <span class="text-5xl mb-4 opacity-20">📭</span>
                                <p class="text-gray-500 italic font-medium uppercase text-xs tracking-widest">Nenhuma movimentação registrada no sistema.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-8">
            {{ $movements->links() }}
        </div>

    </div>

    <style>
        nav[role="navigation"] svg { width: 20px; }
        .relative.z-0.inline-flex.shadow-sm.rounded-md { background-color: #111827; border: 1px solid #1f2937; }
    </style>
</x-bar-layout>
