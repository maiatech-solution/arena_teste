<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-6 flex flex-col md:flex-row gap-8">

        <div class="flex-1 bg-gray-900 rounded-[2.5rem] border border-gray-800 p-8 flex flex-col shadow-2xl">
            <div class="flex items-center justify-between mb-8">
                <a href="{{ route('bar.tables.index') }}"
                    class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg text-[10px] font-black uppercase tracking-widest">
                    ◀ Voltar ao Mapa
                </a>
                <h2 class="text-2xl font-black text-white uppercase italic">
                    Lançar na <span class="text-orange-500">Mesa {{ $table->identifier }}</span>
                </h2>
            </div>

            <div class="relative mb-6">
                <input type="text" id="mainSearch" onkeyup="liveSearch()" placeholder="🔍 Buscar produto pelo nome..."
                    class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-5 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 outline-none transition-all font-bold">
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 overflow-y-auto no-scrollbar pr-2"
                style="max-height: 600px;">
                @foreach ($products as $product)
                    <button onclick="addItemToOrder({{ $product->id }})" data-name="{{ strtolower($product->name) }}"
                        class="product-card group relative p-4 bg-gray-800 hover:bg-orange-600 rounded-2xl border border-gray-700 hover:border-orange-500 transition-all flex flex-col items-center justify-center text-center active:scale-95 shadow-md h-32">
                        <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🍺</div>
                        <div class="w-full">
                            <h3
                                class="text-[10px] font-black text-white uppercase leading-tight line-clamp-2 mb-1 group-hover:text-white">
                                {{ $product->name }}</h3>
                            <span class="text-orange-500 font-black text-xs group-hover:text-white">R$
                                {{ number_format($product->sale_price, 2, ',', '.') }}</span>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <div
            class="w-full md:w-96 bg-gray-900 rounded-[2.5rem] border border-gray-800 flex flex-col shadow-2xl overflow-hidden">
            <div class="p-6 bg-gray-800/50 border-b border-gray-800 flex justify-between items-center">
                <h3 class="font-black text-white uppercase italic text-sm">📝 Itens da Comanda</h3>
                <span class="bg-orange-600/20 text-orange-500 px-3 py-1 rounded-full text-[10px] font-black">ID:
                    #{{ $order->id }}</span>
            </div>

            <div class="flex-1 p-6 space-y-3 overflow-y-auto no-scrollbar" id="orderItemsList">
                @forelse($order->items as $item)
                    <div
                        class="flex justify-between items-center bg-gray-950 p-4 rounded-2xl border border-gray-800 animate-slide-in">
                        <div class="flex flex-col">
                            <span class="text-white text-[10px] font-black uppercase">{{ $item->product->name }}</span>
                            <span class="text-gray-500 text-[9px] font-bold">{{ $item->quantity }}x R$
                                {{ number_format($item->unit_price, 2, ',', '.') }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-orange-500 text-xs font-black">R$
                                {{ number_format($item->subtotal, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-20 opacity-20 text-white font-black uppercase text-[10px]">Vazio</div>
                @endforelse
            </div>

            <div class="p-6 bg-gray-950 border-t border-gray-800 space-y-4">
                <div class="flex justify-between items-end">
                    <span class="text-gray-500 text-[10px] font-black uppercase">Total Parcial</span>
                    <span class="text-3xl font-black text-white">R$
                        {{ number_format($order->total_value, 2, ',', '.') }}</span>
                </div>

                <button onclick="abrirCheckout()"
                    class="w-full py-5 bg-green-600 hover:bg-green-500 text-white font-black rounded-2xl uppercase text-[10px] tracking-[0.2em] transition-all shadow-xl shadow-green-600/20 active:scale-95">
                    🏁 Fechar e Receber
                </button>
            </div>
        </div>
    </div>

    <div id="modalCheckout"
        class="hidden fixed inset-0 bg-black/95 backdrop-blur-md z-[300] flex items-center justify-center p-4">
        <div
            class="bg-gray-900 border border-gray-800 rounded-[2.5rem] w-full max-w-5xl overflow-hidden shadow-2xl flex flex-col md:flex-row">
            <div class="p-8 bg-gray-800/30 border-r border-gray-800 w-full md:w-96">
                <h3 class="text-white font-black uppercase italic mb-8 tracking-widest">Resumo Financeiro</h3>
                <div class="space-y-6">
                    <div class="bg-gray-950 p-6 rounded-3xl border border-gray-800 text-white font-black">
                        <span class="text-gray-500 text-[9px] uppercase block mb-1">Total da Mesa</span>
                        <span class="text-4xl">R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
                    </div>
                    <div class="bg-orange-600/10 p-6 rounded-3xl border border-orange-600/20">
                        <span class="text-orange-500 text-[9px] font-black uppercase block mb-1">Restante</span>
                        <span class="text-4xl font-black text-orange-500" id="textRestante">R$
                            {{ number_format($order->total_value, 2, ',', '.') }}</span>
                    </div>
                    <div class="bg-green-600/10 p-6 rounded-3xl border border-green-600/20">
                        <span class="text-green-500 text-[9px] font-black uppercase block mb-1">Troco</span>
                        <span class="text-4xl font-black text-green-500" id="textTroco">R$ 0,00</span>
                    </div>
                </div>
            </div>

            <div class="p-8 flex-1">
                <div class="flex justify-between mb-8">
                    <h3 class="text-xl font-black text-white uppercase italic">Finalizar Pagamento</h3>
                    <button onclick="document.getElementById('modalCheckout').classList.add('hidden')"
                        class="text-gray-500 hover:text-white transition-colors text-2xl">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="col-span-1">
                        <label class="text-[9px] font-black text-gray-500 uppercase mb-2 block tracking-widest">Nome do
                            Cliente (Opcional)</label>
                        <input type="text" id="customer_name" placeholder="Ex: João Silva"
                            class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 font-bold outline-none focus:border-orange-600 transition-all">
                    </div>
                    <div class="col-span-1">
                        <label
                            class="text-[9px] font-black text-gray-500 uppercase mb-2 block tracking-widest">WhatsApp</label>
                        <input type="text" id="customer_phone" placeholder="(91) 99999-9999"
                            class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 font-bold outline-none focus:border-orange-600 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label
                            class="text-[9px] font-black text-gray-500 uppercase mb-2 block tracking-widest">Valor</label>
                        <input type="number" id="inputValorPago" step="0.01"
                            class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 text-2xl font-black outline-none focus:border-orange-600">
                    </div>
                    <div>
                        <label
                            class="text-[9px] font-black text-gray-500 uppercase mb-2 block tracking-widest">Método</label>
                        <select id="selectMetodo"
                            class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 font-black outline-none focus:border-orange-600">
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">💎 PIX</option>
                            <option value="debito">💳 Débito</option>
                            <option value="credito">💳 Crédito</option>
                        </select>
                    </div>
                </div>

                <button onclick="addPagamento()"
                    class="w-full py-4 bg-gray-800 hover:bg-orange-600 text-white font-black rounded-2xl uppercase text-[9px] mb-6 transition-all border border-gray-700 hover:border-orange-500 tracking-[0.2em]">
                    + Adicionar na Lista
                </button>

                <div id="listaPagamentos" class="space-y-2 mb-8 max-h-40 overflow-y-auto pr-2 custom-scroll"></div>

                <form action="{{ route('bar.tables.close', $table->id) }}" method="POST" id="formFecharMesa">
                    @csrf
                    <input type="hidden" name="customer_name" id="hidden_customer_name">
                    <input type="hidden" name="customer_phone" id="hidden_customer_phone">
                    <input type="hidden" name="pagamentos" id="inputPagamentosHidden">
                    <input type="hidden" name="print_coupon" id="inputPrintCoupon" value="0">
                    <input type="hidden" name="send_whatsapp" id="inputSendWhatsApp" value="0">

                    <button type="button" onclick="abrirOpcoesFinais()" id="btnFinalizarGeral" disabled
                        class="w-full py-6 bg-green-600 opacity-30 cursor-not-allowed text-white font-black rounded-[2rem] uppercase tracking-[0.3em] text-sm shadow-2xl transition-all">
                        🏁 Finalizar Venda
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="modalPosFinalizar"
        class="hidden fixed inset-0 bg-black/80 backdrop-blur-sm z-[400] flex items-center justify-center p-4">
        <div class="bg-gray-900 border border-gray-800 p-10 rounded-[3rem] w-full max-w-md text-center shadow-2xl">
            <div class="text-5xl mb-6">🎉</div>
            <h3 class="text-2xl font-black text-white uppercase italic mb-2">Venda Concluída!</h3>
            <p class="text-gray-500 font-bold text-xs uppercase tracking-widest mb-8">Como deseja proceder?</p>

            <div class="flex flex-col gap-3">
                <button onclick="executarAcaoFinal('imprimir')"
                    class="w-full py-4 bg-white text-black font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-orange-500 hover:text-white transition-all">
                    🖨️ Emitir Comprovante
                </button>
                <button onclick="executarAcaoFinal('sair')"
                    class="w-full py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px] tracking-widest hover:text-white transition-all">
                    ✖ Apenas Sair
                </button>
            </div>
        </div>
    </div>

    <script>
        let totalMesa = {{ $order->total_value }};
        let pagamentos = [];
        let pagoAcumulado = 0;

        function liveSearch() {
            const query = document.getElementById('mainSearch').value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(card => {
                card.classList.toggle('hidden', !card.dataset.name.includes(query));
            });
        }

        async function addItemToOrder(productId) {
            const response = await fetch("{{ route('bar.tables.add_item', $order->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            });
            if ((await response.json()).success) window.location.reload();
        }

        function abrirCheckout() {
            document.getElementById('modalCheckout').classList.remove('hidden');
            document.getElementById('inputValorPago').value = (totalMesa - pagoAcumulado).toFixed(2);
            document.getElementById('inputValorPago').focus();
        }

        function addPagamento() {
            const valor = parseFloat(document.getElementById('inputValorPago').value);
            const metodo = document.getElementById('selectMetodo').value;
            if (valor > 0) {
                pagamentos.push({
                    metodo,
                    valor
                });
                atualizarTelaPagamento();
            }
        }

        function atualizarTelaPagamento() {
            pagoAcumulado = pagamentos.reduce((acc, p) => acc + p.valor, 0);
            const lista = document.getElementById('listaPagamentos');
            lista.innerHTML = pagamentos.map((p, i) => `
                <div class="flex justify-between items-center bg-gray-950 p-4 rounded-2xl border border-gray-800 text-[10px] font-black uppercase text-white">
                    <span>${p.metodo}</span>
                    <div class="flex items-center gap-4">
                        <span class="text-orange-500 font-bold">R$ ${p.valor.toFixed(2)}</span>
                        <button onclick="removerPagamento(${i})" class="text-red-500 hover:text-red-400">✕</button>
                    </div>
                </div>
            `).join('');

            const faltante = totalMesa - pagoAcumulado;
            const troco = pagoAcumulado > totalMesa ? pagoAcumulado - totalMesa : 0;

            document.getElementById('textRestante').innerText = 'R$ ' + (faltante > 0 ? faltante : 0).toLocaleString(
                'pt-br', {
                    minimumFractionDigits: 2
                });
            document.getElementById('textTroco').innerText = 'R$ ' + troco.toLocaleString('pt-br', {
                minimumFractionDigits: 2
            });

            const btn = document.getElementById('btnFinalizarGeral');
            if (pagoAcumulado >= totalMesa) {
                btn.disabled = false;
                btn.classList.remove('opacity-30', 'cursor-not-allowed');
            } else {
                btn.disabled = true;
                btn.classList.add('opacity-30', 'cursor-not-allowed');
            }

            if (faltante > 0) document.getElementById('inputValorPago').value = faltante.toFixed(2);
            else document.getElementById('inputValorPago').value = '0.00';
        }

        function removerPagamento(index) {
            pagamentos.splice(index, 1);
            atualizarTelaPagamento();
        }

        function abrirOpcoesFinais() {
            const modalOpcoes = document.getElementById('modalPosFinalizar');
            const modalCheckout = document.getElementById('modalCheckout');

            if (modalOpcoes) {
                // 1. Esconde o modal de valores para não dar conflito de cliques
                if (modalCheckout) modalCheckout.classList.add('hidden');

                // 2. Mostra o modal de opções (Imprimir/Zap)
                modalOpcoes.classList.remove('hidden');
                modalOpcoes.style.zIndex = "9999"; // Força ele a ficar na frente de tudo
            } else {
                alert("Erro: O elemento modalPosFinalizar não foi encontrado.");
            }
        }

        function executarAcaoFinal(acao) {
            // 1. Preenche os dados nos campos ocultos
            document.getElementById('inputPrintCoupon').value = (acao === 'imprimir') ? "1" : "0";
            document.getElementById('inputSendWhatsApp').value = (acao === 'whatsapp') ? "1" : "0";

            document.getElementById('hidden_customer_name').value = document.getElementById('customer_name').value;
            document.getElementById('hidden_customer_phone').value = document.getElementById('customer_phone').value;
            document.getElementById('inputPagamentosHidden').value = JSON.stringify(pagamentos);

            console.log("Enviando formulário com ação:", acao);

            // 2. Envia o formulário
            document.getElementById('formFecharMesa').submit();
        }
    </script>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 10px;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
    </style>
</x-bar-layout>
