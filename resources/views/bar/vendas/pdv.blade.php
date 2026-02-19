<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-8">

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

            {{-- Card de Resumo Dinâmico --}}
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
                    <label class="text-[9px] font-black text-gray-500 uppercase ml-2 mb-1 block tracking-widest">Data Específica</label>
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
                            <th class="p-8">Detalhamento</th>
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
                                        <div class="w-12 h-12 rounded-2xl bg-black border border-gray-800 flex flex-col items-center justify-center text-xs font-black {{ !$isPaga ? 'text-red-900' : 'text-gray-400' }} italic">
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
                                    <div class="flex flex-wrap gap-1.5 max-w-sm">
                                        @foreach($venda->items as $item)
                                            <span class="text-[9px] bg-gray-800/50 text-gray-400 px-2 py-1 rounded-xl border border-gray-700/50 font-black uppercase italic">
                                                {{ (int)$item->quantity }}x <span class="text-gray-300">{{ $item->product->name }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-8 text-right">
                                    <span class="text-2xl font-black {{ !$isPaga ? 'text-red-950 line-through' : 'text-white' }} italic tracking-tighter font-mono">
                                        R$ {{ number_format($venda->total_value, 2, ',', '.') }}
                                    </span>
                                </td>
                                <td class="p-8 text-center">
                                    @if($isPaga)
                                        <span class="px-4 py-1.5 bg-green-500/10 text-green-500 text-[9px] font-black rounded-full border border-green-500/20 uppercase tracking-widest shadow-[0_0_15px_rgba(34,197,94,0.1)]">Paga</span>
                                    @else
                                        <span class="px-4 py-1.5 bg-red-500/10 text-red-500 text-[9px] font-black rounded-full border border-red-500/20 uppercase tracking-widest shadow-[0_0_15px_rgba(239,68,68,0.1)]">Anulada</span>
                                    @endif
                                </td>
                                <td class="p-8 text-center">
                                    @if($isPaga)
                                        @if($caixaAberto)
                                            <button onclick="abrirModalCancelamento({{ $venda->id }}, '{{ number_format($venda->total_value, 2, ',', '.') }}')"
                                                class="w-10 h-10 flex items-center justify-center bg-gray-950 hover:bg-red-600 text-red-500 hover:text-white rounded-xl transition-all border border-gray-800 hover:border-red-500 shadow-lg group/btn">
                                                <span class="group-hover/btn:scale-125 transition-transform">🚫</span>
                                            </button>
                                        @else
                                            <div title="Caixa Fechado" class="w-10 h-10 mx-auto flex items-center justify-center bg-gray-800/30 text-gray-700 rounded-xl border border-gray-800/50 cursor-help opacity-40">
                                                <span class="text-xs">🔒</span>
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-[10px] text-gray-800 font-black uppercase italic tracking-widest">Sem Ações</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-32 text-center opacity-30">
                                    <p class="text-gray-600 font-black uppercase tracking-[0.5em] italic text-2xl">Nenhum Registro Encontrado</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($vendas->hasPages())
                <div class="p-8 bg-black/20 border-t border-gray-800 pagination-dark">
                    {{ $vendas->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- 🔒 MODAL DE CANCELAMENTO --}}
    <div id="modalCancelamento" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
        <div class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[3rem] p-10 shadow-2xl relative overflow-hidden">
            <div class="absolute -right-10 -top-10 text-9xl opacity-5 italic font-black text-white pointer-events-none">VOID</div>

            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-red-600/10 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-red-600/20 shadow-xl">
                    <span class="text-4xl">🚫</span>
                </div>
                <h3 class="text-white text-2xl font-black uppercase italic tracking-tighter leading-tight">Anular Venda PDV <span id="cancel_id_text" class="text-red-500"></span></h3>
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
        input[type="date"] { color-scheme: dark; }

        .pagination-dark nav div:last-child span.relative,
        .pagination-dark nav div:last-child a.relative {
            background-color: #000 !important;
            border-color: #1f2937 !important;
            color: #6b7280 !important;
            border-radius: 0.75rem !important;
            padding: 8px 16px !important;
        }
        .pagination-dark nav div:last-child span.relative[aria-current="page"] {
            background-color: #ea580c !important;
            color: white !important;
            border-color: #ea580c !important;
        }
    </style>

    <script>
        let vendaIdAtiva = null;

        function abrirModalCancelamento(id, valor) {
            vendaIdAtiva = id;
            document.getElementById('cancel_id_text').innerText = '#' + id;
            document.getElementById('cancel_valor_text').innerText = valor;
            // 🛠️ ROTA ATUALIZADA PARA O HISTÓRICO PDV
            document.getElementById('formCancelarVenda').action = `/bar/historico/pdv/${id}/cancelar`;
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
                alert("⚠️ A autorização do supervisor é obrigatória.");
                return;
            }

            form.querySelector('input[name="supervisor_email"]').value = emailFinal;
            form.querySelector('input[name="supervisor_password"]').value = passFinal;

            const btn = event.target;
            btn.innerText = "PROCESSANDO...";
            btn.disabled = true;

            form.submit();
        }
    </script>
</x-bar-layout>
