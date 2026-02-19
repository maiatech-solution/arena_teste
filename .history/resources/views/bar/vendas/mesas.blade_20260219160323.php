<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        {{-- 🛰️ CABEÇALHO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="flex items-center gap-5">
                <div class="p-4 bg-indigo-600/10 border border-indigo-600/20 rounded-3xl shadow-lg shadow-indigo-900/20">
                    <span class="text-3xl">🍽️</span>
                </div>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic leading-none">
                        Histórico de <span class="text-indigo-500">Mesas</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-2">
                        Gestão de Comandas Finalizadas e Estornos
                    </p>
                </div>
            </div>

            {{-- Card de Resumo --}}
            <div class="flex gap-4">
                <div class="bg-gray-900 border border-gray-800 px-6 py-3 rounded-2xl border-l-4 border-l-indigo-500 shadow-xl">
                    <span class="block text-[8px] font-black text-gray-500 uppercase">Total de Comandas</span>
                    <span class="text-xl font-black text-white italic tracking-tighter font-mono">
                        R$ {{ number_format($vendas->where('status', 'paid')->sum('total_value'), 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 🔍 FILTROS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2rem] p-5 mb-8 shadow-xl">
            <form action="{{ route('bar.vendas.mesas.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="w-32">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block tracking-widest">Mesa/ID</label>
                    <input type="text" name="id" value="{{ request('id') }}" placeholder="#000"
                        class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-indigo-500 outline-none transition-all">
                </div>

                <div class="w-44">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block tracking-widest">Status</label>
                    <select name="status" class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-indigo-500 outline-none cursor-pointer">
                        <option value="">TODOS</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>PAGAS</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ANULADAS</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase transition-all shadow-lg active:scale-95">
                        Filtrar
                    </button>
                    <a href="{{ route('bar.vendas.mesas.index') }}" class="bg-gray-800 text-gray-400 px-6 py-3 rounded-xl font-black text-[10px] uppercase flex items-center hover:bg-gray-700 transition-all">
                        Limpar
                    </a>
                </div>
            </form>
        </div>

        {{-- 📋 LISTAGEM --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden relative">
            <div class="absolute right-0 top-0 p-10 opacity-5 text-8xl italic font-black text-white uppercase pointer-events-none">MESAS</div>

            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-800/50">
                            <th class="p-8">Comanda</th>
                            <th class="p-8">Garçom/User</th>
                            <th class="p-8">Produtos</th>
                            <th class="p-8 text-right">Valor Pago</th>
                            <th class="p-8 text-center">Status</th>
                            <th class="p-8 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/40">
                        @forelse($vendas as $venda)
                            @php
                                $caixaAberto = $venda->cashSession && $venda->cashSession->status === 'open';
                                $isPaga = $venda->status === 'paid' || $venda->status === 'pago';
                            @endphp
                            <tr class="hover:bg-white/[0.01] transition-colors group">
                                <td class="p-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-indigo-900/20 border border-indigo-500/30 flex items-center justify-center text-xs font-black text-indigo-400 italic">
                                            #{{ $venda->id }}
                                        </div>
                                        <div>
                                            <span class="text-white font-black block text-sm tracking-tighter">{{ $venda->updated_at->format('d/m/Y') }}</span>
                                            <span class="text-gray-600 text-[10px] font-bold uppercase">{{ $venda->updated_at->format('H:i') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-8 text-gray-500 font-black text-[10px] uppercase italic">
                                    {{ $venda->user->name ?? 'N/A' }}
                                </td>
                                <td class="p-8 text-gray-400 text-[10px] font-bold">
                                    {{ $venda->items->count() }} itens na comanda
                                </td>
                                <td class="p-8 text-right font-mono text-xl font-black {{ !$isPaga ? 'text-red-900 line-through' : 'text-white' }}">
                                    R$ {{ number_format($venda->total_value, 2, ',', '.') }}
                                </td>
                                <td class="p-8 text-center">
                                    @if($isPaga)
                                        <span class="px-3 py-1 bg-green-500/10 text-green-500 text-[9px] font-black rounded-full border border-green-500/20">PAGA</span>
                                    @else
                                        <span class="px-3 py-1 bg-red-500/10 text-red-500 text-[9px] font-black rounded-full border border-red-500/20">ANULADA</span>
                                    @endif
                                </td>
                                <td class="p-8 text-center">
                                    @if($isPaga && $caixaAberto)
                                        <button onclick="abrirModalCancelamento({{ $venda->id }}, '{{ number_format($venda->total_value, 2, ',', '.') }}')"
                                            class="w-10 h-10 mx-auto flex items-center justify-center bg-gray-950 hover:bg-red-600 text-red-500 hover:text-white rounded-xl transition-all border border-gray-800">
                                            🚫
                                        </button>
                                    @else
                                        <span class="text-[10px] text-gray-800 font-black italic">BLOQUEADO</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-20 text-center text-gray-600 uppercase font-black italic">Nenhuma mesa encontrada</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($vendas->hasPages()) <div class="p-6 border-t border-gray-800">{{ $vendas->links() }} </div> @endif
        </div>
    </div>

    {{-- MODAL REUTILIZA A LÓGICA DO PDV --}}
    <div id="modalCancelamento" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
        <div class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative">
            <div class="text-center mb-8">
                <h3 class="text-white text-2xl font-black uppercase italic">Anular Comanda <span id="cancel_id_text" class="text-indigo-500"></span></h3>
                <p class="text-gray-500 text-[10px] font-bold uppercase mt-2">Valor: R$ <span id="cancel_valor_text"></span></p>
            </div>

            <form id="formCancelarMesa" method="POST">
                @csrf
                <input type="hidden" name="supervisor_email" value="{{ in_array(auth()->user()->role, ['admin', 'gestor']) ? auth()->user()->email : '' }}">
                <input type="hidden" name="supervisor_password">

                <textarea name="reason" required placeholder="Motivo do estorno da mesa..."
                    class="w-full bg-black border border-gray-800 rounded-3xl p-5 text-white text-xs mb-6 h-28 focus:border-indigo-500 outline-none"></textarea>

                <div class="p-6 bg-indigo-600/5 border border-indigo-600/20 rounded-[2.5rem] mb-6">
                    <input type="password" id="pass_auth_cancel" placeholder="SENHA DO SUPERVISOR"
                        class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-sm outline-none focus:border-indigo-600 font-mono tracking-[0.3em]">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <button type="button" onclick="fecharModalCancelamento()" class="py-5 text-gray-500 font-black uppercase text-[10px]">Voltar</button>
                    <button type="button" onclick="confirmarCancelamentoMesa()" class="py-5 bg-indigo-600 hover:bg-indigo-500 text-white font-black rounded-3xl uppercase text-[10px] shadow-xl">Confirmar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalCancelamento(id, valor) {
            document.getElementById('cancel_id_text').innerText = '#' + id;
            document.getElementById('cancel_valor_text').innerText = valor;
            document.getElementById('formCancelarMesa').action = `/bar/historico/mesas/${id}/cancelar`;
            document.getElementById('modalCancelamento').classList.remove('hidden');
        }

        function fecharModalCancelamento() {
            document.getElementById('modalCancelamento').classList.add('hidden');
        }

        function confirmarCancelamentoMesa() {
            const form = document.getElementById('formCancelarMesa');
            const passInput = document.getElementById('pass_auth_cancel');

            if(!passInput.value) { alert("Senha obrigatória!"); return; }

            form.querySelector('input[name="supervisor_password"]').value = passInput.value;
            form.submit();
        }
    </script>
</x-bar-layout>
