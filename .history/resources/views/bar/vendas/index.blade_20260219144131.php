<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-4xl font-black text-white uppercase italic tracking-tighter leading-none">
                    Histórico de <span class="text-orange-500">Vendas</span>
                </h1>
                <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-2">Gestão de comandas finalizadas e cancelamentos</p>
            </div>
        </div>

        {{-- TABELA DE VENDAS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-black/40 text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-800">
                        <th class="p-6">ID / Data</th>
                        <th class="p-6">Operador</th>
                        <th class="p-6">Produtos Vendidos</th>
                        <th class="p-6 text-right">Valor Total</th>
                        <th class="p-6 text-center">Status</th>
                        <th class="p-6 text-center">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @foreach($vendas as $venda)
                        <tr class="hover:bg-white/[0.02] transition-colors group">
                            <td class="p-6">
                                <span class="text-white font-black block text-sm">#{{ $venda->id }}</span>
                                <span class="text-gray-600 text-[10px] font-bold">{{ $venda->updated_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="p-6">
                                <span class="text-gray-400 font-black text-[10px] uppercase tracking-tighter">{{ $venda->user->name ?? 'N/A' }}</span>
                            </td>
                            <td class="p-6">
                                <div class="flex flex-wrap gap-1 max-w-xs">
                                    @foreach($venda->items as $item)
                                        <span class="text-[9px] bg-gray-800 text-gray-400 px-2 py-0.5 rounded border border-gray-700 font-bold uppercase italic">
                                            {{ (int)$item->quantity }}x {{ $item->product->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="p-6 text-right">
                                <span class="text-xl font-black text-white italic tracking-tighter font-mono">
                                    R$ {{ number_format($venda->total_value, 2, ',', '.') }}
                                </span>
                            </td>
                            <td class="p-6 text-center">
                                @if($venda->status == 'paid')
                                    <span class="px-3 py-1 bg-green-500/10 text-green-500 text-[9px] font-black rounded-full border border-green-500/20 uppercase">Paga</span>
                                @else
                                    <span class="px-3 py-1 bg-red-500/10 text-red-500 text-[9px] font-black rounded-full border border-red-500/20 uppercase">Cancelada</span>
                                @endif
                            </td>
                            <td class="p-6 text-center">
                                @if($venda->status == 'paid')
                                    <button onclick="abrirModalCancelamento({{ $venda->id }}, '{{ number_format($venda->total_value, 2, ',', '.') }}')"
                                        class="p-3 bg-red-600/10 hover:bg-red-600 text-red-500 hover:text-white rounded-xl transition-all shadow-lg shadow-red-600/5">
                                        🚫
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-6 bg-black/20">
                {{ $vendas->links() }}
            </div>
        </div>
    </div>

    {{-- MODAL DE AUTORIZAÇÃO DE CANCELAMENTO --}}
    <div id="modalCancelamento" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
        <div class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3rem] p-8 shadow-2xl overflow-hidden">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-red-600/20 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-red-600/30">
                    <span class="text-3xl">🚫</span>
                </div>
                <h3 class="text-white text-xl font-black uppercase italic tracking-tighter">Cancelar Venda <span id="cancel_id_text"></span></h3>
                <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-2">Valor: R$ <span id="cancel_valor_text"></span></p>
            </div>

            <form id="formCancelarVenda" method="POST">
                @csrf
                <input type="hidden" name="supervisor_email" value="{{ in_array(auth()->user()->role, ['admin', 'gestor']) ? auth()->user()->email : '' }}">
                <input type="hidden" name="supervisor_password">

                <div class="space-y-4">
                    <div>
                        <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block">Motivo do Cancelamento</label>
                        <textarea name="reason" required placeholder="Ex: Cliente desistiu ou erro de lançamento..."
                            class="w-full bg-black border border-gray-800 rounded-2xl p-4 text-white text-xs outline-none focus:border-red-600 h-24 transition-all"></textarea>
                    </div>

                    {{-- 🛡️ CAMPO DE SENHA DO GESTOR --}}
                    <div class="p-5 bg-orange-600/5 border border-orange-600/20 rounded-[2rem] text-center">
                        @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                            <span class="text-[9px] font-black text-green-500 uppercase block mb-3">Confirme sua senha</span>
                        @else
                            <span class="text-[9px] font-black text-orange-500 uppercase block mb-2 animate-pulse">🔒 Senha do Gestor Necessária</span>
                            <input type="email" id="email_auth_cancel" placeholder="E-MAIL DO GESTOR"
                                class="w-full bg-black border border-gray-800 rounded-xl p-3 text-white text-center text-[10px] mb-2 outline-none focus:border-red-600 font-mono">
                        @endif

                        <input type="password" id="pass_auth_cancel" placeholder="DIGITE A SENHA"
                            class="w-full bg-black border border-gray-800 rounded-xl p-3 text-white text-center text-sm outline-none focus:border-red-600 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mt-8">
                    <button type="button" onclick="fecharModalCancelamento()" class="py-4 text-gray-500 font-black rounded-2xl uppercase text-[10px] hover:text-white transition-all">Voltar</button>
                    <button type="button" onclick="confirmarCancelamento()" class="py-4 bg-red-600 hover:bg-red-500 text-white font-black rounded-2xl uppercase text-[10px] shadow-lg shadow-red-900/40 transition-all active:scale-95">Anular Venda</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let vendaIdAtiva = null;

        function abrirModalCancelamento(id, valor) {
            vendaIdAtiva = id;
            document.getElementById('cancel_id_text').innerText = '#' + id;
            document.getElementById('cancel_valor_text').innerText = valor;
            document.getElementById('formCancelarVenda').action = `/bar/vendas/${id}/cancelar`;
            document.getElementById('modalCancelamento').classList.remove('hidden');
        }

        function fecharModalCancelamento() {
            document.getElementById('modalCancelamento').classList.add('hidden');
        }

        function confirmarCancelamento() {
            const form = document.getElementById('formCancelarVenda');
            const passInput = document.getElementById('pass_auth_cancel');
            const emailInput = document.getElementById('email_auth_cancel');

            // Puxa o e-mail (seja do gestor logado ou do input do colaborador)
            const emailFinal = emailInput ? emailInput.value : form.querySelector('input[name="supervisor_email"]').value;
            const passFinal = passInput.value;

            if(!passFinal || !emailFinal) {
                alert("⚠️ E-mail e Senha do gestor são obrigatórios!");
                return;
            }

            form.querySelector('input[name="supervisor_email"]').value = emailFinal;
            form.querySelector('input[name="supervisor_password"]').value = passFinal;
            form.submit();
        }
    </script>
</x-bar-layout>
