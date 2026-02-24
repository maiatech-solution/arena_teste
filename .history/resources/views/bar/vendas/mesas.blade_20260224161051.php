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

        {{-- 🛰️ CABEÇALHO E RESUMO ESTRATÉGICO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="flex items-center gap-5">
                {{-- 🔙 BOTÃO VOLTAR --}}
                <a href="{{ route('bar.tables.painel') }}"
                    class="bg-gray-900 hover:bg-gray-800 text-orange-500 p-4 rounded-3xl transition-all border border-gray-800 shadow-lg group"
                    title="Voltar ao Painel de Mesas">
                    <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block text-xl">◀</span>
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

            {{-- Card de Resumo Financeiro --}}
            <div class="flex gap-4">
                <div class="bg-gray-900 border border-gray-800 px-6 py-4 rounded-[2rem] border-l-4 border-l-orange-500 shadow-2xl relative overflow-hidden group">
                    <div class="absolute right-[-10%] top-[-20%] text-6xl opacity-5 rotate-12 group-hover:rotate-0 transition-transform duration-500">💰</div>
                    <span class="block text-[8px] font-black text-gray-500 uppercase tracking-widest mb-1">Total em Comandas Pagas</span>
                    <span class="text-2xl font-black text-white italic tracking-tighter font-mono">
                        R$ {{ number_format($vendas->whereIn('status', ['paid', 'pago'])->sum('total_value'), 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- 🔍 FILTROS AVANÇADOS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] p-6 mb-8 shadow-2xl relative overflow-hidden">
             <div class="absolute left-0 top-0 w-1 h-full bg-orange-600/20"></div>
            <form action="{{ route('bar.vendas.mesas.index') }}" method="GET" class="flex flex-wrap items-end gap-6 relative z-10">
                <div class="w-full md:w-40">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-2 block tracking-widest">Localizar Mesa/ID</label>
                    <input type="text" name="id" value="{{ request('id') }}" placeholder="#000"
                        class="w-full bg-black border-gray-800 rounded-2xl text-white text-xs font-bold p-4 focus:border-orange-500 outline-none transition-all shadow-inner placeholder:text-gray-800">
                </div>

                <div class="w-full md:w-52">
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-2 block tracking-widest">Situação da Conta</label>
                    <select name="status" class="w-full bg-black border-gray-800 rounded-2xl text-white text-xs font-bold p-4 focus:border-orange-500 outline-none cursor-pointer appearance-none">
                        <option value="">TODOS OS STATUS</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>✅ PAGAS / FINALIZADAS</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>🚫 ANULADAS / ESTORNADAS</option>
                    </select>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white px-10 py-4 rounded-2xl font-black text-[10px] uppercase transition-all shadow-lg shadow-orange-900/20 active:scale-95 flex items-center gap-2">
                        <span>⚡</span> Filtrar
                    </button>
                    <a href="{{ route('bar.vendas.mesas.index') }}" class="bg-gray-800 text-gray-400 px-8 py-4 rounded-2xl font-black text-[10px] uppercase flex items-center hover:bg-gray-700 hover:text-white transition-all">
                        Limpar
                    </a>
                </div>
            </form>
        </div>

        {{-- 📋 TABELA DE LISTAGEM --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden relative">
            <div class="absolute right-0 top-0 p-10 opacity-[0.03] text-9xl italic font-black text-white uppercase pointer-events-none select-none">
                History
            </div>

            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-800/50">
                            <th class="p-8">Mesa / Identificação</th>
                            <th class="p-8">Operador / Garçom</th>
                            <th class="p-8">Itens</th>
                            <th class="p-8 text-right">Montante Pago</th>
                            <th class="p-8 text-center">Situação</th>
                            <th class="p-8 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/40">
                        @forelse($vendas as $venda)
                            @php
                                $caixaAberto = $venda->cashSession && $venda->cashSession->status === 'open';
                                $isPaga = in_array($venda->status, ['paid', 'pago']);
                            @endphp
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-8">
                                    <div class="flex items-center gap-5">
                                        {{-- CARD DA MESA --}}
                                        <div class="w-16 h-16 rounded-[1.5rem] bg-orange-500/10 border border-orange-500/20 flex flex-col items-center justify-center shadow-lg group-hover:scale-105 transition-transform">
                                            <span class="text-[7px] font-black text-orange-500/50 uppercase leading-none mb-1">Mesa</span>
                                            <span class="text-2xl font-black text-orange-500 italic leading-none">
                                                {{ str_pad($venda->table->identifier ?? '00', 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-white font-black block text-base tracking-tighter mb-1">ID #{{ $venda->id }}</span>
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-600 text-[10px] font-bold uppercase tracking-widest">{{ $venda->updated_at->format('d/m/Y') }}</span>
                                                <span class="w-1 h-1 rounded-full bg-gray-800"></span>
                                                <span class="text-orange-500/50 text-[10px] font-bold uppercase">{{ $venda->updated_at->format('H:i') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="p-8">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-800 border border-gray-700 flex items-center justify-center text-[10px]">👤</div>
                                        <span class="text-gray-400 font-black text-[11px] uppercase italic tracking-wider">
                                            {{ $venda->user->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                </td>

                                <td class="p-8">
                                    <button @click="abrirDetalhes({{ $venda->id }})"
                                        class="flex items-center gap-2 px-5 py-3 bg-black border border-gray-800 rounded-2xl text-[10px] font-black uppercase text-gray-500 hover:text-orange-400 hover:border-orange-500 transition-all shadow-inner group/btn">
                                        <span class="group-hover/btn:rotate-12 transition-transform">🔍</span> Visualizar
                                    </button>
                                </td>

                                <td class="p-8 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="font-mono text-2xl font-black {{ !$isPaga ? 'text-red-900/50 line-through' : 'text-white' }} tracking-tighter">
                                            R$ {{ number_format($venda->total_value, 2, ',', '.') }}
                                        </span>
                                        <span class="text-[8px] font-black text-gray-600 uppercase tracking-[0.2em] mt-1">{{ $venda->payment_method ?? 'Diverso' }}</span>
                                    </div>
                                </td>

                                <td class="p-8 text-center">
                                    @if ($isPaga)
                                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-500/5 text-green-500 text-[10px] font-black rounded-2xl border border-green-500/10 uppercase tracking-widest">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                            Paga
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-red-500/5 text-red-500 text-[10px] font-black rounded-2xl border border-red-500/10 uppercase tracking-widest opacity-60">
                                            🚫 Anulada
                                        </div>
                                    @endif
                                </td>

                                <td class="p-8 text-center">
                                    <div class="flex justify-center gap-3">
                                        @if ($isPaga && $caixaAberto)
                                            <button onclick="abrirModalCancelamento({{ $venda->id }}, '{{ number_format($venda->total_value, 2, ',', '.') }}')"
                                                class="w-12 h-12 flex items-center justify-center bg-gray-950 hover:bg-red-600 text-red-500 hover:text-white rounded-[1.2rem] transition-all border border-gray-800 shadow-lg group/cancel">
                                                <span class="text-xl group-hover/cancel:scale-110 transition-transform">🚫</span>
                                            </button>
                                        @else
                                            <div class="w-12 h-12 flex items-center justify-center bg-black/40 text-gray-800 rounded-[1.2rem] border border-gray-900 cursor-not-allowed" title="Estorno Bloqueado (Caixa Fechado)">
                                                🔒
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-32 text-center">
                                    <div class="opacity-20 flex flex-col items-center">
                                        <span class="text-6xl mb-4">📂</span>
                                        <p class="text-gray-500 font-black uppercase tracking-[0.4em] italic text-xl">Nenhum Registro</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($vendas->hasPages())
                <div class="p-8 border-t border-gray-800/50 bg-black/20">
                    {{ $vendas->links() }}
                </div>
            @endif
        </div>

        {{-- 📋 MODAL DE DETALHES (SISTEMA DARK AUDITORIA) --}}
        <div x-show="modalDetalhes"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="fixed inset-0 z-[110] flex items-center justify-center bg-black/95 backdrop-blur-md p-4"
             style="display: none;">

            <div @click.away="modalDetalhes = false"
                class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3.5rem] overflow-hidden shadow-[0_0_50px_rgba(0,0,0,0.5)] relative">

                {{-- Marca d'água fundo --}}
                <div class="absolute -right-10 -top-10 text-9xl opacity-[0.02] italic font-black text-white pointer-events-none uppercase select-none">MESA</div>

                <div class="p-10 relative z-10">
                    <div class="flex justify-between items-start mb-10">
                        <div>
                            <h3 class="text-white text-3xl font-black uppercase italic tracking-tighter leading-none">
                                Comanda <span class="text-orange-500">#</span><span x-text="venda.id"></span>
                            </h3>
                            <p class="text-gray-500 text-[10px] font-black uppercase tracking-[0.2em] mt-3 bg-white/5 inline-block px-3 py-1 rounded-full" x-text="venda.data"></p>
                        </div>
                        <button @click="modalDetalhes = false" class="w-10 h-10 flex items-center justify-center bg-gray-800 hover:bg-white hover:text-black rounded-full text-white transition-all text-xl">✕</button>
                    </div>

                    <div x-show="carregando" class="py-24 text-center">
                        <div class="inline-block w-8 h-8 border-4 border-orange-500/20 border-t-orange-500 rounded-full animate-spin mb-4"></div>
                        <p class="text-orange-500 font-black uppercase text-[10px] tracking-[0.3em] animate-pulse">Cruzando Dados...</p>
                    </div>

                    <div x-show="!carregando" class="space-y-8">
                        {{-- LISTAGEM DOS ITENS --}}
                        <div class="bg-black/60 rounded-[2.5rem] p-8 border border-gray-800/50 shadow-inner">
                            <label class="text-[9px] font-black text-gray-600 uppercase tracking-[0.2em] block mb-6 border-b border-gray-800 pb-2">📦 Consumo Detalhado</label>

                            <div class="space-y-5 max-h-72 overflow-y-auto no-scrollbar pr-2">
                                <template x-for="item in venda.itens">
                                    <div class="flex justify-between items-center group/item">
                                        <div class="flex flex-col">
                                            <div class="flex items-center gap-2">
                                                <span class="text-orange-500 font-black text-xs" x-text="item.qtd + 'x'"></span>
                                                <span class="text-gray-300 text-xs font-black uppercase italic tracking-tight group-hover/item:text-white transition-colors" x-text="item.nome"></span>
                                            </div>
                                            <span class="text-[9px] text-gray-600 font-bold" x-text="'Un: R$ ' + item.preco"></span>
                                        </div>
                                        <span class="text-white font-mono text-sm font-black italic" x-text="'R$ ' + item.subtotal"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- TOTAIS E DESCONTO --}}
                        <div class="space-y-4 px-2">
                            <template x-if="parseFloat(venda.desconto) > 0">
                                <div class="bg-orange-600/5 p-5 rounded-[2rem] border border-orange-600/10 space-y-3 mb-4">
                                    <div class="flex justify-between items-center text-[10px] font-black uppercase text-gray-500">
                                        <span>Valor Bruto Total</span>
                                        <span class="font-mono" x-text="'R$ ' + (parseFloat(venda.total_raw) + parseFloat(venda.desconto)).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] font-black uppercase text-red-500 bg-red-500/10 p-2 rounded-xl">
                                        <span>⚡ Desconto Especial</span>
                                        <span class="font-mono" x-text="'- R$ ' + parseFloat(venda.desconto).toLocaleString('pt-BR', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </template>

                            <div class="flex justify-between items-center text-[10px] font-black uppercase italic tracking-widest text-gray-500">
                                <span>Garçom</span>
                                <span class="text-gray-300" x-text="venda.operador"></span>
                            </div>
                            <div class="flex justify-between items-center text-[10px] font-black uppercase italic tracking-widest text-gray-500">
                                <span>Meio de Pgto</span>
                                <span class="text-green-500" x-text="venda.pagamento"></span>
                            </div>

                            <div class="pt-6 border-t border-gray-800 flex justify-between items-end mt-4">
                                <span class="text-gray-500 font-black uppercase text-xs italic tracking-tighter">Valor Final Pago</span>
                                <span class="text-5xl font-black text-white italic tracking-tighter font-mono leading-none" x-text="'R$ ' + venda.total"></span>
                            </div>
                        </div>
                    </div>

                    <button @click="modalDetalhes = false"
                        class="w-full mt-10 py-6 bg-gray-800 hover:bg-orange-600 text-gray-400 hover:text-white rounded-[2rem] font-black uppercase text-[11px] tracking-[0.3em] transition-all shadow-xl active:scale-95">
                        Fechar Comanda
                    </button>
                </div>
            </div>
        </div>

        {{-- 🔒 MODAL DE CANCELAMENTO (ESTORNO AUDITADO) --}}
        <div id="modalCancelamento" class="hidden fixed inset-0 z-[120] flex items-center justify-center bg-black/98 backdrop-blur-xl p-4">
            <div class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[4rem] p-12 shadow-[0_0_100px_rgba(239,68,68,0.2)] relative overflow-hidden">
                <div class="absolute -right-10 -top-10 text-9xl opacity-[0.03] italic font-black text-white pointer-events-none uppercase">VOID</div>

                <div class="text-center mb-10">
                    <div class="w-24 h-24 bg-red-600/10 rounded-[2.5rem] flex items-center justify-center mx-auto mb-8 border border-red-600/20 shadow-2xl animate-pulse">
                        <span class="text-5xl">🚫</span>
                    </div>
                    <h3 class="text-white text-3xl font-black uppercase italic tracking-tighter mb-2">Anular Comanda</h3>
                    <div class="text-red-500 font-black text-xl italic tracking-tighter font-mono" id="cancel_id_text"></div>
                    <div class="mt-4 inline-block px-6 py-2 bg-white/5 rounded-full text-white font-black italic tracking-tighter text-2xl font-mono">
                        R$ <span id="cancel_valor_text"></span>
                    </div>
                </div>

                <form id="formCancelarMesa" method="POST">
                    @csrf
                    <input type="hidden" name="supervisor_email" value="{{ in_array(auth()->user()->role, ['admin', 'gestor']) ? auth()->user()->email : '' }}">
                    <input type="hidden" name="supervisor_password">

                    <div class="space-y-6">
                        <div class="space-y-2">
                             <label class="text-[9px] font-black text-gray-500 uppercase ml-4 tracking-widest">Justificativa Obrigatória</label>
                             <textarea name="reason" required placeholder="Descreva o motivo do estorno..."
                                class="w-full bg-black border border-gray-800 rounded-[2rem] p-6 text-white text-xs h-32 focus:border-red-600 transition-all shadow-inner placeholder:text-gray-800 outline-none resize-none"></textarea>
                        </div>

                        <div class="p-8 bg-red-600/5 border border-red-600/10 rounded-[3rem] space-y-4 shadow-inner">
                            <p class="text-[9px] font-black text-red-500/50 uppercase text-center tracking-[0.2em] mb-4">🔐 Autorização de Supervisor</p>
                            @if (!in_array(auth()->user()->role, ['admin', 'gestor']))
                                <input type="email" id="email_auth_cancel" placeholder="E-MAIL DO GESTOR"
                                    class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-[10px] font-mono uppercase tracking-widest outline-none focus:border-red-600 transition-all">
                            @endif
                            <input type="password" id="pass_auth_cancel" placeholder="SENHA DE ACESSO"
                                class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-sm font-mono tracking-[0.5em] outline-none focus:border-red-600 transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-5 mt-12">
                        <button type="button" onclick="fecharModalCancelamento()"
                            class="py-6 text-gray-500 font-black rounded-[2rem] uppercase text-[10px] tracking-widest hover:text-white hover:bg-white/5 transition-all">Voltar</button>
                        <button type="button" onclick="confirmarCancelamentoMesa()"
                            class="py-6 bg-red-600 hover:bg-red-500 text-white font-black rounded-[2rem] uppercase text-[10px] tracking-widest shadow-2xl shadow-red-900/40 active:scale-95 transition-all">Confirmar</button>
                    </div>
                </form>
            </div>
        </div>

    </div> {{-- FIM ALPINE --}}

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        input[type="date"] { color-scheme: dark; }

        /* Estilização da Paginação Laravel p/ Tailwind Dark */
        .pagination span, .pagination a {
            @apply bg-gray-900 border-gray-800 text-gray-500 rounded-xl px-4 py-2;
        }
        .pagination .active {
            @apply bg-orange-600 text-white border-orange-600;
        }
    </style>

    <script>
        function abrirModalCancelamento(id, valor) {
            document.getElementById('cancel_id_text').innerText = 'COMANDA #' + id;
            document.getElementById('cancel_valor_text').innerText = valor;
            document.getElementById('formCancelarMesa').action = `/bar/historico/mesas/${id}/cancelar`;
            document.getElementById('modalCancelamento').classList.remove('hidden');
            document.getElementById('modalCancelamento').classList.add('flex');
        }

        function fecharModalCancelamento() {
            document.getElementById('modalCancelamento').classList.add('hidden');
            document.getElementById('modalCancelamento').classList.remove('flex');
        }

        function confirmarCancelamentoMesa() {
            const form = document.getElementById('formCancelarMesa');
            const passInput = document.getElementById('pass_auth_cancel');
            const emailInput = document.getElementById('email_auth_cancel');
            const emailFinal = emailInput ? emailInput.value : form.querySelector('input[name="supervisor_email"]').value;

            if (!passInput.value || !emailFinal) {
                alert("⚠️ Erro: Identificação do gestor é necessária.");
                return;
            }

            form.querySelector('input[name="supervisor_email"]').value = emailFinal;
            form.querySelector('input[name="supervisor_password"]').value = passInput.value;
            form.submit();
        }
    </script>
</x-bar-layout>
