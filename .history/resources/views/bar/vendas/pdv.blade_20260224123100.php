<x-bar-layout>
    {{-- 🚀 CONTAINER PRINCIPAL COM ESTADO DO MODAL (Alpine.js) --}}
    <div class="max-w-[1600px] mx-auto px-6 py-8" x-data="{
        modalDetalhes: false,
        venda: { itens: [] },
        carregando: false,
        abrirDetalhes(id) {
            this.carregando = true;
            this.modalDetalhes = true;
            fetch(`/bar/relatorios/venda-detalhes/pdv/${id}`)
                .then(res => res.json())
                .then(data => {
                    this.venda = data;
                    this.carregando = false;
                });
        }
    }">

        {{-- 🛰️ CABEÇALHO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="flex items-center gap-5">
                <div class="p-4 bg-orange-600/10 border border-orange-600/20 rounded-3xl shadow-lg shadow-orange-900/20">
                    <span class="text-3xl">🛒</span>
                </div>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic leading-none">
                        Histórico <span class="text-orange-500">PDV</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-2">
                        Vendas Diretas de Balcão e Consumo Rápido
                    </p>
                </div>
            </div>

            <div class="flex gap-4">
                <div class="bg-gray-900 border border-gray-800 px-6 py-3 rounded-2xl border-l-4 border-l-green-500 shadow-xl">
                    <span class="block text-[8px] font-black text-gray-500 uppercase">Faturamento na Página</span>
                    <span class="text-xl font-black text-white italic tracking-tighter font-mono">
                        R$ {{ number_format($vendas->whereIn('status', ['paid', 'pago'])->sum('total_value'), 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 🔍 BARRA DE FILTROS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2rem] p-5 mb-8 shadow-xl">
            <form action="{{ route('bar.vendas.pdv.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="w-32">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block tracking-widest">Ticket ID</label>
                    <input type="text" name="id" value="{{ request('id') }}" placeholder="#000"
                        class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-orange-500 outline-none transition-all uppercase">
                </div>
                <div class="w-44">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block tracking-widest">Data</label>
                    <input type="date" name="date" value="{{ request('date') }}"
                        class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-orange-500 outline-none transition-all">
                </div>
                <div class="w-44">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block tracking-widest">Status</label>
                    <select name="status" class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-orange-500 outline-none cursor-pointer">
                        <option value="">TODOS</option>
                        <option value="pago" {{ request('status') == 'pago' ? 'selected' : '' }}>PAGAS</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ANULADAS</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase transition-all shadow-lg shadow-orange-600/20 active:scale-95">
                        Filtrar
                    </button>
                    @if(request()->anyFilled(['id', 'date', 'status']))
                        <a href="{{ route('bar.vendas.pdv.index') }}" class="bg-gray-800 text-gray-400 px-6 py-3 rounded-xl font-black text-[10px] uppercase flex items-center hover:bg-gray-700 transition-all">
                            Limpar
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- 📋 LISTAGEM --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden relative">
            <div class="absolute right-0 top-0 p-10 opacity-5 text-8xl italic font-black text-white uppercase pointer-events-none">PDV</div>
            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-800/50">
                            <th class="p-8">Identificação</th>
                            <th class="p-8">Operador</th>
                            <th class="p-8">Visualizar</th>
                            <th class="p-8 text-right">Montante</th>
                            <th class="p-8 text-center">Situação</th>
                            <th class="p-8 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/40">
                        @forelse($vendas as $venda)
                            @php
                                $caixaAberto = $venda->cashSession && $venda->cashSession->status === 'open';
                                $isPaga = in_array($venda->status, ['pago', 'paid']);
                            @endphp
                            <tr class="hover:bg-white/[0.01] transition-colors group">
                                <td class="p-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-black border border-gray-800 flex flex-col items-center justify-center text-xs font-black {{ !$isPaga ? 'text-red-900' : 'text-orange-500' }} italic">
                                            #{{ $venda->id }}
                                        </div>
                                        <div>
                                            <span class="text-white font-black block text-sm tracking-tighter">{{ $venda->updated_at->format('d/m/Y') }}</span>
                                            <span class="text-gray-600 text-[10px] font-bold uppercase tracking-widest">{{ $venda->updated_at->format('H:i') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-8 text-gray-500 font-black text-[10px] uppercase italic">
                                    {{ $venda->user->name ?? 'Sistema' }}
                                </td>
                                <td class="p-8">
                                    <button @click="abrirDetalhes({{ $venda->id }})" class="flex items-center gap-2 px-4 py-2 bg-gray-950 border border-gray-800 rounded-xl text-[10px] font-black uppercase text-gray-400 hover:text-orange-500 hover:border-orange-500 transition-all">
                                        <span>🔍</span> Itens
                                    </button>
                                </td>
                                <td class="p-8 text-right">
                                    <span class="text-2xl font-black {{ !$isPaga ? 'text-red-950 line-through' : 'text-white' }} italic tracking-tighter font-mono">
                                        R$ {{ number_format($venda->total_value, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="p-8 text-center">
                                    @if($isPaga)
                                        <span class="px-4 py-1.5 bg-green-500/10 text-green-500 text-[9px] font-black rounded-full border border-green-500/20 uppercase tracking-widest">Paga</span>
                                    @else
                                        <span class="px-4 py-1.5 bg-red-500/10 text-red-500 text-[9px] font-black rounded-full border border-red-500/20 uppercase tracking-widest">Anulada</span>
                                    @endif
                                </td>
                                <td class="p-8 text-center flex justify-center gap-2">
                                    @if($isPaga && $caixaAberto)
                                        <button onclick="abrirModalCancelamento({{ $venda->id }}, '{{ number_format($venda->total_value, 2, ',', '.') }}')"
                                            class="w-10 h-10 flex items-center justify-center bg-gray-950 hover:bg-red-600 text-red-500 hover:text-white rounded-xl transition-all border border-gray-800 hover:border-red-500 shadow-lg group/btn">
                                            <span class="group-hover/btn:scale-125 transition-transform">🚫</span>
                                        </button>
                                    @elseif(!$isPaga)
                                        <span class="text-[9px] text-gray-800 font-black uppercase italic">Sem Ações</span>
                                    @else
                                        <div title="Caixa Fechado" class="w-10 h-10 flex items-center justify-center bg-gray-800/30 text-gray-700 rounded-xl border border-gray-800/50 cursor-help opacity-40">
                                            <span class="text-xs">🔒</span>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-32 text-center opacity-30"><p class="text-gray-600 font-black uppercase tracking-[0.5em] italic text-2xl">Vazio</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 📋 MODAL DE DETALHES (CUPOM FISCAL DARK) --}}
        <div x-show="modalDetalhes" x-transition.opacity class="fixed inset-0 z-[110] flex items-center justify-center bg-black/95 backdrop-blur-sm p-4">
            <div @click.away="modalDetalhes = false" class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3rem] overflow-hidden shadow-2xl relative">
                <div class="absolute -right-10 -top-10 text-9xl opacity-5 italic font-black text-white pointer-events-none uppercase" x-text="venda.tipo"></div>

                <div class="p-10 relative">
                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <h3 class="text-white text-3xl font-black uppercase italic tracking-tighter">Resumo <span class="text-orange-500">#</span><span x-text="venda.id"></span></h3>
                            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1" x-text="venda.data"></p>
                        </div>
                        <button @click="modalDetalhes = false" class="text-gray-500 hover:text-white text-2xl">✕</button>
                    </div>

                    <div x-show="carregando" class="py-20 text-center"><span class="animate-pulse text-orange-500 font-black uppercase text-xs tracking-[0.3em]">Carregando Itens...</span></div>

                    <div x-show="!carregando" class="space-y-6">
                        <div class="bg-black/40 rounded-[2rem] p-6 border border-gray-800/50">
                            <label class="text-[8px] font-black text-gray-600 uppercase tracking-widest block mb-4">Itens da Transação</label>
                            <div class="space-y-4 max-h-60 overflow-y-auto no-scrollbar">
                                <template x-for="item in venda.itens">
                                    <div class="flex justify-between items-center border-b border-gray-800 pb-3 last:border-0">
                                        <div>
                                            <span class="text-orange-500 font-black text-xs" x-text="item.qtd + 'x '"></span>
                                            <span class="text-gray-300 text-xs font-bold uppercase italic" x-text="item.nome"></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-white font-mono text-xs font-bold" x-text="'R$ ' + item.subtotal"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-3 pt-4">
                            <div class="flex justify-between items-center text-[10px] font-black uppercase italic">
                                <span class="text-gray-500">Operador</span>
                                <span class="text-gray-300" x-text="venda.operador"></span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] font-black uppercase italic">
                                <span class="text-gray-500">Pagamento</span>
                                <span class="text-green-500" x-text="venda.pagamento"></span>
                            </div>
                            <div class="pt-4 border-t border-gray-800 flex justify-between items-end">
                                <span class="text-gray-500 font-black uppercase text-xs italic">Total</span>
                                <span class="text-4xl font-black text-white italic tracking-tighter font-mono" x-text="'R$ ' + venda.total"></span>
                            </div>
                        </div>
                    </div>

                    <button @click="modalDetalhes = false" class="w-full mt-10 py-5 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] transition-all">
                        Fechar Resumo
                    </button>
                </div>
            </div>
        </div>

        {{-- 🔒 MODAL DE CANCELAMENTO (Original mantido) --}}
        <div id="modalCancelamento" class="hidden fixed inset-0 z-[120] flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
            <div class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative overflow-hidden">
                <div class="absolute -right-10 -top-10 text-9xl opacity-5 italic font-black text-white pointer-events-none">VOID</div>
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-red-600/10 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-red-600/20 shadow-xl">
                        <span class="text-4xl">🚫</span>
                    </div>
                    <h3 class="text-white text-2xl font-black uppercase italic tracking-tighter">Anular Venda <span id="cancel_id_text" class="text-red-500"></span></h3>
                    <div class="mt-2 text-white font-black italic tracking-tighter">R$ <span id="cancel_valor_text"></span></div>
                </div>
                <form id="formCancelarVenda" method="POST">
                    @csrf
                    <input type="hidden" name="supervisor_email" value="{{ in_array(auth()->user()->role, ['admin', 'gestor']) ? auth()->user()->email : '' }}">
                    <input type="hidden" name="supervisor_password">
                    <div class="space-y-6">
                        <textarea name="reason" required placeholder="Justificativa..." class="w-full bg-black border border-gray-800 rounded-3xl p-5 text-white text-xs h-28 focus:border-red-600 transition-all shadow-inner placeholder:text-gray-700"></textarea>
                        <div class="p-6 bg-red-600/5 border border-red-600/20 rounded-[2.5rem]">
                            @if (!in_array(auth()->user()->role, ['admin', 'gestor']))
                                <input type="email" id="email_auth_cancel" placeholder="E-MAIL DO GESTOR" class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-[10px] mb-3 font-mono uppercase tracking-widest outline-none">
                            @endif
                            <input type="password" id="pass_auth_cancel" placeholder="SENHA DO SUPERVISOR" class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-sm font-mono tracking-[0.3em] outline-none">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-10">
                        <button type="button" onclick="fecharModalCancelamento()" class="py-5 text-gray-500 font-black rounded-3xl uppercase text-[10px] hover:text-white transition-all">Voltar</button>
                        <button type="button" onclick="confirmarCancelamento()" class="py-5 bg-red-600 hover:bg-red-500 text-white font-black rounded-3xl uppercase text-[10px] tracking-widest shadow-xl shadow-red-900/40 active:scale-95 transition-all">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        input[type="date"] { color-scheme: dark; }
    </style>

    <script>
        let vendaIdAtiva = null;
        function abrirModalCancelamento(id, valor) {
            vendaIdAtiva = id;
            document.getElementById('cancel_id_text').innerText = '#' + id;
            document.getElementById('cancel_valor_text').innerText = valor;
            document.getElementById('formCancelarVenda').action = `/bar/historico/pdv/${id}/cancelar`;
            document.getElementById('modalCancelamento').classList.remove('hidden');
        }
        function fecharModalCancelamento() { document.getElementById('modalCancelamento').classList.add('hidden'); }
        function confirmarCancelamento() {
            const form = document.getElementById('formCancelarVenda');
            const passInput = document.getElementById('pass_auth_cancel');
            const emailInput = document.getElementById('email_auth_cancel');
            const emailFinal = emailInput ? emailInput.value : form.querySelector('input[name="supervisor_email"]').value;
            if(!passInput.value || !emailFinal) { alert("⚠️ Autorização obrigatória."); return; }
            form.querySelector('input[name="supervisor_email"]').value = emailFinal;
            form.querySelector('input[name="supervisor_password"]').value = passInput.value;
            form.submit();
        }
    </script>
</x-bar-layout>
