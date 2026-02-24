<x-bar-layout>
    {{-- 🚀 CONTAINER PRINCIPAL COM ESTADO DO MODAL (Alpine.js) --}}
    <div class="max-w-[1600px] mx-auto px-6 py-8" x-data="{
        modalDetalhes: false,
        venda: { itens: [] },
        carregando: false,
        abrirDetalhes(id) {
            this.carregando = true;
            this.modalDetalhes = true;
            fetch(`/bar/relatorios/venda-detalhes/mesa/${id}`)
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
                {{-- 🔙 BOTÃO VOLTAR PARA O PAINEL DE MESAS --}}
                <a href="{{ route('bar.tables.painel') }}"
                    class="bg-gray-900 hover:bg-gray-800 text-orange-500 p-4 rounded-3xl transition-all border border-gray-800 shadow-lg group"
                    title="Voltar ao Painel de Mesas">
                    <span
                        class="group-hover:-translate-x-1 transition-transform duration-200 inline-block text-xl">◀</span>
                </a>

                <div class="p-4 bg-orange-600/10 border border-orange-600/20 rounded-3xl shadow-lg shadow-orange-900/20">
                    <span class="text-3xl">🍽️</span>
                </div>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic leading-none">
                        Histórico de <span class="text-orange-500">Mesas</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-2">
                        Gestão de Comandas Finalizadas e Estornos
                    </p>
                </div>
            </div>

            {{-- Card de Resumo --}}
            <div class="flex gap-4">
                <div
                    class="bg-gray-900 border border-gray-800 px-6 py-3 rounded-2xl border-l-4 border-l-orange-500 shadow-xl">
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
                    <label
                        class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block tracking-widest">Mesa/ID</label>
                    <input type="text" name="id" value="{{ request('id') }}" placeholder="#000"
                        class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-orange-500 outline-none transition-all">
                </div>

                <div class="w-44">
                    <label
                        class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block tracking-widest">Status</label>
                    <select name="status"
                        class="w-full bg-black border-gray-800 rounded-xl text-white text-xs font-bold p-3 focus:border-orange-500 outline-none cursor-pointer">
                        <option value="">TODOS</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>PAGAS</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>ANULADAS
                        </option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="bg-orange-600 hover:bg-orange-500 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase transition-all shadow-lg active:scale-95">
                        Filtrar
                    </button>
                    <a href="{{ route('bar.vendas.mesas.index') }}"
                        class="bg-gray-800 text-gray-400 px-6 py-3 rounded-xl font-black text-[10px] uppercase flex items-center hover:bg-gray-700 transition-all">
                        Limpar
                    </a>
                </div>
            </form>
        </div>

        {{-- 📋 LISTAGEM --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden relative">
            <div
                class="absolute right-0 top-0 p-10 opacity-5 text-8xl italic font-black text-white uppercase pointer-events-none">
                MESAS</div>

            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-black/40 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-800/50">
                            <th class="p-8">Mesa / Comanda</th>
                            <th class="p-8">Operador</th>
                            <th class="p-8">Visualizar</th>
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
                                        {{-- 🏷️ NÚMERO DA MESA EM DESTAQUE --}}
                                        <div
                                            class="w-14 h-14 rounded-2xl bg-orange-500/10 border border-orange-500/20 flex flex-col items-center justify-center shadow-lg shadow-orange-900/10">
                                            <span
                                                class="text-[8px] font-black text-orange-500/50 uppercase leading-none">Mesa</span>
                                            <span class="text-xl font-black text-orange-500 italic leading-none">
                                                {{ str_pad($venda->table->identifier ?? '00', 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                        </div>

                                        <div>
                                            <span class="text-white font-black block text-sm tracking-tighter">Pedido
                                                #{{ $venda->id }}</span>
                                            <span class="text-gray-600 text-[10px] font-bold uppercase tracking-widest">
                                                {{ $venda->updated_at->format('d/m/Y H:i') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-8">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-2 rounded-full bg-orange-500/40"></div>
                                        <span
                                            class="text-gray-400 font-black text-[11px] uppercase italic tracking-wider">
                                            {{ $venda->user->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="p-8">
                                    <button @click="abrirDetalhes({{ $venda->id }})"
                                        class="flex items-center gap-2 px-4 py-2 bg-gray-950 border border-gray-800 rounded-xl text-[10px] font-black uppercase text-gray-400 hover:text-orange-400 hover:border-orange-500 transition-all">
                                        <span>🔍</span> Ver Itens
                                    </button>
                                </td>
                                <td
                                    class="p-8 text-right font-mono text-xl font-black {{ !$isPaga ? 'text-red-900 line-through' : 'text-white' }}">
                                    R$ {{ number_format($venda->total_value, 2, ',', '.') }}
                                </td>
                                <td class="p-8 text-center">
                                    @if ($isPaga)
                                        <span
                                            class="px-3 py-1 bg-green-500/10 text-green-500 text-[9px] font-black rounded-full border border-green-500/20">PAGA</span>
                                    @else
                                        <span
                                            class="px-3 py-1 bg-red-500/10 text-red-500 text-[9px] font-black rounded-full border border-red-500/20">ANULADA</span>
                                    @endif
                                </td>
                                <td class="p-8 text-center flex justify-center gap-2">
                                    @if ($isPaga && $caixaAberto)
                                        <button
                                            onclick="abrirModalCancelamento({{ $venda->id }}, '{{ number_format($venda->total_value, 2, ',', '.') }}')"
                                            class="w-10 h-10 flex items-center justify-center bg-gray-950 hover:bg-red-600 text-red-500 hover:text-white rounded-xl transition-all border border-gray-800">
                                            🚫
                                        </button>
                                    @else
                                        <span class="text-[10px] text-gray-800 font-black italic">BLOQUEADO</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-20 text-center text-gray-600 uppercase font-black italic">
                                    Vazio</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($vendas->hasPages())
                <div class="p-6 border-t border-gray-800">{{ $vendas->links() }} </div>
            @endif
        </div>

        {{-- 📋 MODAL DE DETALHES (ESTILO COMANDA DARK - ATUALIZADO) --}}
        <div x-show="modalDetalhes" x-transition.opacity
            class="fixed inset-0 z-[110] flex items-center justify-center bg-black/95 backdrop-blur-sm p-4"
            style="display: none;">
            <div @click.away="modalDetalhes = false"
                class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3rem] overflow-hidden shadow-2xl relative">
                <div
                    class="absolute -right-10 -top-10 text-9xl opacity-5 italic font-black text-white pointer-events-none uppercase">
                    MESA</div>

                <div class="p-10 relative">
                    <div class="flex justify-between items-start mb-8">
                        <div>
                            <h3 class="text-white text-3xl font-black uppercase italic tracking-tighter">
                                Comanda <span class="text-orange-500">#</span><span x-text="venda.id"></span>
                            </h3>
                            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1"
                                x-text="venda.data"></p>
                        </div>
                        <button @click="modalDetalhes = false"
                            class="text-gray-500 hover:text-white text-2xl">✕</button>
                    </div>

                    <div x-show="carregando" class="py-20 text-center">
                        <span class="animate-pulse text-orange-500 font-black uppercase text-xs tracking-[0.3em]">
                            Buscando Comanda...
                        </span>
                    </div>

                    <div x-show="!carregando" class="space-y-6">
                        <div class="bg-black/40 rounded-[2rem] p-6 border border-gray-800/50">
                            <label class="text-[8px] font-black text-gray-600 uppercase tracking-widest block mb-4">
                                Consumo Detalhado
                            </label>
                            <div class="space-y-4 max-h-60 overflow-y-auto no-scrollbar">
                                <template x-for="item in venda.itens">
                                    <div
                                        class="flex justify-between items-center border-b border-gray-800 pb-3 last:border-0">
                                        <div>
                                            <span class="text-orange-400 font-black text-xs"
                                                x-text="item.qtd + 'x '"></span>
                                            <span class="text-gray-300 text-xs font-bold uppercase italic"
                                                x-text="item.nome"></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-white font-mono text-xs font-bold"
                                                x-text="'R$ ' + item.subtotal"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-3 pt-4 border-t border-gray-800">
                            {{-- 💰 BLOCO DE DESCONTO (LÓGICA PADRONIZADA) --}}
                            <template x-if="parseFloat(venda.desconto) > 0">
                                <div class="space-y-2 bg-black/20 p-4 rounded-2xl mb-4">
                                    <div
                                        class="flex justify-between items-center text-[10px] font-black uppercase italic text-gray-500">
                                        <span>Subtotal Bruto</span>
                                        <span
                                            x-text="'R$ ' + (parseFloat(venda.total_raw) + parseFloat(venda.desconto)).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div
                                        class="flex justify-between items-center text-[10px] font-black uppercase italic text-red-500">
                                        <span>Desconto Especial</span>
                                        <span
                                            x-text="'- R$ ' + parseFloat(venda.desconto).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </template>

                            <div class="flex justify-between items-center text-[10px] font-black uppercase italic">
                                <span class="text-gray-500">Garçom/Atendente</span>
                                <span class="text-gray-300" x-text="venda.operador"></span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] font-black uppercase italic">
                                <span class="text-gray-500">Meio de Pagamento</span>
                                <span class="text-green-500" x-text="venda.pagamento"></span>
                            </div>
                            <div class="pt-4 border-t border-gray-800 flex justify-between items-end">
                                <span class="text-gray-500 font-black uppercase text-xs italic">Total Pago</span>
                                <span class="text-4xl font-black text-white italic tracking-tighter font-mono"
                                    x-text="'R$ ' + venda.total"></span>
                            </div>
                        </div>
                    </div>

                    <button @click="modalDetalhes = false"
                        class="w-full mt-10 py-5 bg-gray-800 hover:bg-gray-700 text-gray-400 hover:text-white rounded-2xl font-black uppercase text-[10px] tracking-[0.2em] transition-all">
                        Fechar Comanda
                    </button>
                </div>
            </div>
        </div>

        {{-- 🔒 MODAL DE CANCELAMENTO (ESTORNO) --}}
        <div id="modalCancelamento"
            class="hidden fixed inset-0 z-[120] flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
            <div
                class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative overflow-hidden">
                <div
                    class="absolute -right-10 -top-10 text-9xl opacity-5 italic font-black text-white pointer-events-none uppercase">
                    VOID</div>
                <div class="text-center mb-8">
                    <div
                        class="w-20 h-20 bg-orange-600/10 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-orange-600/20 shadow-xl">
                        <span class="text-4xl">🚫</span>
                        </div>
                    <h3 class="text-white text-2xl font-black uppercase italic tracking-tighter">
                        Anular Comanda <span id="cancel_id_text"
                            class="text-orange-500"></span></h3>
                    <div class="mt-2 text-white font-black italic tracking-tighter">R$ <span
                            id="cancel_valor_text"></span></div>
                    </div>
                <form id="formCancelarMesa" method="POST">
                    @csrf
                    <input type="hidden" name="supervisor_email"
                        value="{{ in_array(auth()->user()->role, ['admin', 'gestor']) ? auth()->user()->email : '' }}">
                    <input type="hidden" name="supervisor_password">
                    <div class="space-y-6">

                        <textarea name="reason" required placeholder="Motivo do estorno..."
                            class="w-full bg-black border border-gray-800 rounded-3xl p-5 text-white text-xs h-28 focus:border-orange-500 transition-all shadow-inner placeholder:text-gray-700"></textarea>
                        <div
                            class="p-6 bg-orange-600/5 border border-orange-600/20 rounded-[2.5rem]">
                            @if (!in_array(auth()->user()->role, ['admin', 'gestor']))
                                <input type="email" id="email_auth_cancel" placeholder="E-MAIL DO GESTOR"

                                    class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-[10px] mb-3 font-mono uppercase tracking-widest outline-none">
                            @endif
                            <input type="password" id="pass_auth_cancel"
                                placeholder="SENHA DO GESTOR"
                                class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-sm font-mono tracking-[0.3em] outline-none">
                            </div>

                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-10">
                        <button type="button" onclick="fecharModalCancelamento()"

                            class="py-5 text-gray-500 font-black rounded-3xl uppercase text-[10px] hover:text-white transition-all">Voltar</button>
                        <button type="button" onclick="confirmarCancelamentoMesa()"

                            class="py-5 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-3xl uppercase text-[10px] tracking-widest shadow-xl shadow-orange-900/40 active:scale-95 transition-all">Confirmar</button>
                        </div>
                    </form>
                </div>
            </div>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        input[type="date"] {
            color-scheme: dark;
        }
    </style>

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
            const emailInput = document.getElementById('email_auth_cancel');
            const emailFinal = emailInput ? emailInput.value : form.querySelector('input[name="supervisor_email"]').value;
            if (!passInput.value || !emailFinal) {
                alert("⚠️ Autorização obrigatória.");
                return;
            }
            form.querySelector('input[name="supervisor_email"]').value = emailFinal;
            form.querySelector('input[name="supervisor_password"]').value = passInput.value;
            form.submit();
        }
    </script>
</x-bar-layout>

{{-- 🔒 MODAL DE CANCELAMENTO (Original mantido) --}}
<div id="modalCancelamento"
    class="hidden fixed inset-0 z-[120] flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
    <div
        class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative overflow-hidden">
        <div
            class="absolute -right-10 -top-10 text-9xl opacity-5 italic font-black text-white pointer-events-none uppercase">
            VOID</div>
        <div class="text-center mb-8">
            <div
                class="w-20 h-20 bg-orange-600/10 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-orange-600/20 shadow-xl">
                <span class="text-4xl">🚫</span>
            </div>
            <h3 class="text-white text-2xl font-black uppercase italic tracking-tighter">Anular Comanda <span
                    id="cancel_id_text" class="text-orange-500"></span></h3>
            <div class="mt-2 text-white font-black italic tracking-tighter">R$ <span id="cancel_valor_text"></span>
            </div>
        </div>
        <form id="formCancelarMesa" method="POST">
            @csrf
            <input type="hidden" name="supervisor_email"
                value="{{ in_array(auth()->user()->role, ['admin', 'gestor']) ? auth()->user()->email : '' }}">
            <input type="hidden" name="supervisor_password">
            <div class="space-y-6">
                <textarea name="reason" required placeholder="Motivo do estorno..."
                    class="w-full bg-black border border-gray-800 rounded-3xl p-5 text-white text-xs h-28 focus:border-orange-500 transition-all shadow-inner placeholder:text-gray-700"></textarea>
                <div class="p-6 bg-orange-600/5 border border-orange-600/20 rounded-[2.5rem]">
                    @if (!in_array(auth()->user()->role, ['admin', 'gestor']))
                        <input type="email" id="email_auth_cancel" placeholder="E-MAIL DO GESTOR"
                            class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-[10px] mb-3 font-mono uppercase tracking-widest outline-none">
                    @endif
                    <input type="password" id="pass_auth_cancel" placeholder="SENHA DO GESTOR"
                        class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-sm font-mono tracking-[0.3em] outline-none">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-10">
                <button type="button" onclick="fecharModalCancelamento()"
                    class="py-5 text-gray-500 font-black rounded-3xl uppercase text-[10px] hover:text-white transition-all">Voltar</button>
                <button type="button" onclick="confirmarCancelamentoMesa()"
                    class="py-5 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-3xl uppercase text-[10px] tracking-widest shadow-xl shadow-orange-900/40 active:scale-95 transition-all">Confirmar</button>
            </div>
        </form>
    </div>
</div>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    input[type="date"] {
        color-scheme: dark;
    }
</style>

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
        const emailInput = document.getElementById('email_auth_cancel');
        const emailFinal = emailInput ? emailInput.value : form.querySelector('input[name="supervisor_email"]').value;
        if (!passInput.value || !emailFinal) {
            alert("⚠️ Autorização obrigatória.");
            return;
        }
        form.querySelector('input[name="supervisor_email"]').value = emailFinal;
        form.querySelector('input[name="supervisor_password"]').value = passInput.value;
        form.submit();
    }
</script>
</x-bar-layout>
