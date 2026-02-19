<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        {{-- 🛰️ CABEÇALHO ESTILIZADO --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6">
            <div class="flex items-center gap-5">
                <div class="p-4 bg-orange-600/10 border border-orange-600/20 rounded-3xl shadow-lg shadow-orange-900/20">
                    <span class="text-3xl">📋</span>
                </div>
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic leading-none">
                        Histórico de <span class="text-orange-500">Vendas</span>
                    </h1>
                    <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-2">
                        Gestão de comandas finalizadas e auditoria de cancelamentos
                    </p>
                </div>
            </div>

            {{-- Resumo rápido no topo --}}
            <div class="flex gap-4">
                <div class="bg-gray-900 border border-gray-800 px-6 py-3 rounded-2xl">
                    <span class="block text-[8px] font-black text-gray-500 uppercase">Vendas Hoje</span>
                    <span class="text-xl font-black text-white italic tracking-tighter">R$ {{ number_format($vendas->where('status', 'paid')->sum('total_value'), 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- 📋 LISTAGEM DE VENDAS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[3rem] shadow-2xl overflow-hidden relative">
            <div class="absolute right-0 top-0 p-10 opacity-5 text-8xl italic font-black text-white uppercase pointer-events-none">Sales</div>

            <div class="overflow-x-auto no-scrollbar">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-black/40 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-800/50">
                            <th class="p-8">ID / Data / Hora</th>
                            <th class="p-8">Responsável</th>
                            <th class="p-8">Detalhamento dos Itens</th>
                            <th class="p-8 text-right">Valor Consolidado</th>
                            <th class="p-8 text-center">Status</th>
                            <th class="p-8 text-center">Auditoria</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800/40">
                        @foreach($vendas as $venda)
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                <td class="p-8">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl bg-black border border-gray-800 flex flex-col items-center justify-center text-xs font-black text-gray-400 group-hover:text-orange-500 transition-colors shadow-inner italic">
                                            #{{ $venda->id }}
                                        </div>
                                        <div>
                                            <span class="text-white font-black block text-sm tracking-tighter">{{ $venda->updated_at->format('d/m/Y') }}</span>
                                            <span class="text-gray-600 text-[10px] font-bold uppercase tracking-widest">{{ $venda->updated_at->format('H:i') }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-8">
                                    <span class="text-gray-400 font-black text-[10px] uppercase bg-gray-950 px-3 py-1 rounded-lg border border-gray-800 group-hover:border-gray-700 transition-all">
                                        👤 {{ $venda->user->name ?? 'Sistema' }}
                                    </span>
                                </td>
                                <td class="p-8">
                                    <div class="flex flex-wrap gap-1.5 max-w-sm">
                                        @foreach($venda->items as $item)
                                            <span class="text-[9px] bg-gray-800/50 text-gray-400 px-2.5 py-1 rounded-xl border border-gray-700/50 font-black uppercase italic tracking-tighter">
                                                {{ (int)$item->quantity }}x <span class="text-gray-300">{{ $item->product->name }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-8 text-right">
                                    <span class="text-3xl font-black text-white italic tracking-tighter font-mono">
                                        R$ {{ number_format($venda->total_value, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="p-8 text-center">
                                    @if($venda->status == 'paid')
                                        <span class="px-4 py-1.5 bg-green-500/10 text-green-500 text-[9px] font-black rounded-full border border-green-500/20 uppercase tracking-widest shadow-[0_0_15px_rgba(34,197,94,0.1)]">Paga</span>
                                    @else
                                        <span class="px-4 py-1.5 bg-red-500/10 text-red-500 text-[9px] font-black rounded-full border border-red-500/20 uppercase tracking-widest shadow-[0_0_15px_rgba(239,68,68,0.1)]">Cancelada</span>
                                    @endif
                                </td>
                                <td class="p-8 text-center">
                                    @if($venda->status == 'paid')
                                        <button onclick="abrirModalCancelamento({{ $venda->id }}, '{{ number_format($venda->total_value, 2, ',', '.') }}')"
                                            class="w-10 h-10 flex items-center justify-center bg-gray-950 hover:bg-red-600 text-red-500 hover:text-white rounded-xl transition-all border border-gray-800 hover:border-red-500 active:scale-90 group/btn shadow-lg">
                                            <span class="group-hover/btn:scale-125 transition-transform">🚫</span>
                                        </button>
                                    @else
                                        <span class="text-[10px] text-gray-700 font-black uppercase italic italic">Anulada</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginação --}}
            @if($vendas->hasPages())
                <div class="p-8 bg-black/20 border-t border-gray-800">
                    {{ $vendas->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- 🔒 MODAL DE AUTORIZAÇÃO DE CANCELAMENTO --}}
    <div id="modalCancelamento" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
        <div class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-10 -top-10 text-9xl opacity-5 italic font-black text-white pointer-events-none">VOID</div>

            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-red-600/10 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-red-600/20 shadow-xl">
                    <span class="text-4xl">🚫</span>
                </div>
                <h3 class="text-white text-2xl font-black uppercase italic tracking-tighter leading-tight">Anular Venda <span id="cancel_id_text" class="text-red-500"></span></h3>
                <div class="mt-2 flex items-center justify-center gap-2">
                    <span class="text-gray-500 text-[10px] font-bold uppercase tracking-widest">Montante:</span>
                    <span class="text-white font-black italic tracking-tighter">R$ <span id="cancel_valor_text"></span></span>
                </div>
            </div>

            <form id="formCancelarVenda" method="POST">
                @csrf
                <input type="hidden" name="supervisor_email" value="{{ in_array(auth()->user()->role, ['admin', 'gestor']) ? auth()->user()->email : '' }}">
                <input type="hidden" name="supervisor_password">

                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase ml-4 mb-2 block tracking-widest">Justificativa do Cancelamento</label>
                        <textarea name="reason" required placeholder="Por que esta venda está sendo anulada?"
                            class="w-full bg-black border border-gray-800 rounded-3xl p-5 text-white text-xs outline-none focus:border-red-600 h-28 transition-all shadow-inner placeholder:text-gray-700"></textarea>
                    </div>

                    {{-- 🛡️ ÁREA DE SENHA (Obrigatória para todos) --}}
                    <div class="p-6 bg-red-600/5 border border-red-600/20 rounded-[2.5rem] text-center shadow-inner">
                        @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                            <span class="text-[9px] font-black text-green-500 uppercase block mb-4 tracking-widest italic">Confirme sua senha de Gestor</span>
                        @else
                            <span class="text-[9px] font-black text-orange-500 uppercase block mb-3 tracking-widest animate-pulse italic">🔒 Autorização do Supervisor</span>
                            <input type="email" id="email_auth_cancel" placeholder="E-MAIL DO GESTOR"
                                class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-[10px] mb-3 outline-none focus:border-red-600 font-mono uppercase tracking-widest">
                        @endif

                        <input type="password" id="pass_auth_cancel" placeholder="SENHA DO SUPERVISOR"
                            class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-center text-sm outline-none focus:border-red-600 font-mono tracking-[0.3em]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-10">
                    <button type="button" onclick="fecharModalCancelamento()"
                        class="py-5 text-gray-500 font-black rounded-3xl uppercase text-[10px] tracking-widest hover:text-white hover:bg-gray-800 transition-all">
                        Voltar
                    </button>
                    <button type="button" onclick="confirmarCancelamento()"
                        class="py-5 bg-red-600 hover:bg-red-500 text-white font-black rounded-3xl uppercase text-[10px] tracking-widest shadow-xl shadow-red-900/40 transition-all active:scale-95">
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <script>
        let vendaIdAtiva = null;

        function abrirModalCancelamento(id, valor) {
            vendaIdAtiva = id;
            document.getElementById('cancel_id_text').innerText = '#' + id;
            document.getElementById('cancel_valor_text').innerText = valor;
            document.getElementById('formCancelarVenda').action = `/bar/vendas/${id}/cancelar`;
            document.getElementById('modalCancelamento').classList.remove('hidden');
            setTimeout(() => {
                document.querySelector('textarea[name="reason"]').focus();
            }, 100);
        }

        function fecharModalCancelamento() {
            document.getElementById('modalCancelamento').classList.add('hidden');
        }

        function confirmarCancelamento() {
            const form = document.getElementById('formCancelarVenda');
            const passInput = document.getElementById('pass_auth_cancel');
            const emailInput = document.getElementById('email_auth_cancel');

            const emailFinal = emailInput ? emailInput.value : form.querySelector('input[name="supervisor_email"]').value;
            const passFinal = passInput.value;

            if(!passFinal || !emailFinal) {
                alert("⚠️ A autorização do supervisor é obrigatória para anular vendas.");
                return;
            }

            form.querySelector('input[name="supervisor_email"]').value = emailFinal;
            form.querySelector('input[name="supervisor_password"]').value = passFinal;

            // Troca o texto do botão para feedback
            const btn = event.target;
            btn.innerText = "PROCESSANDO...";
            btn.disabled = true;

            form.submit();
        }
    </script>
</x-bar-layout>
