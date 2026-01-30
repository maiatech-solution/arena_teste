<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-6 flex flex-col md:flex-row gap-8">

        <div class="flex-1 bg-gray-900 rounded-[2.5rem] border border-gray-800 p-8 flex flex-col shadow-2xl">
            <div class="flex items-center justify-between mb-8">
                <a href="{{ route('bar.tables.index') }}" class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg text-[10px] font-black uppercase tracking-widest">
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

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 overflow-y-auto no-scrollbar pr-2" style="max-height: 600px;">
                @foreach($products as $product)
                    <button onclick="addItemToOrder({{ $product->id }})"
                        data-name="{{ strtolower($product->name) }}"
                        class="product-card group relative p-4 bg-gray-800 hover:bg-orange-600 rounded-2xl border border-gray-700 hover:border-orange-500 transition-all flex flex-col items-center justify-center text-center active:scale-95 shadow-md h-32">

                        <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🍺</div>

                        <div class="w-full">
                            <h3 class="text-[10px] font-black text-white uppercase leading-tight line-clamp-2 mb-1 group-hover:text-white">
                                {{ $product->name }}
                            </h3>
                            <span class="text-orange-500 font-black text-xs group-hover:text-white">
                                R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="w-full md:w-96 bg-gray-900 rounded-[2.5rem] border border-gray-800 flex flex-col shadow-2xl overflow-hidden">
            <div class="p-6 bg-gray-800/50 border-b border-gray-800 flex justify-between items-center">
                <h3 class="font-black text-white uppercase italic text-sm">📝 Itens da Comanda</h3>
                <span class="bg-orange-600/20 text-orange-500 px-3 py-1 rounded-full text-[10px] font-black">ID: #{{ $order->id }}</span>
            </div>

            <div class="flex-1 p-6 space-y-3 overflow-y-auto no-scrollbar" id="orderItemsList">
                @forelse($order->items as $item)
                    <div class="flex justify-between items-center bg-gray-950 p-4 rounded-2xl border border-gray-800 animate-slide-in">
                        <div class="flex flex-col">
                            <span class="text-white text-[10px] font-black uppercase">{{ $item->product->name }}</span>
                            <span class="text-gray-500 text-[9px] font-bold">{{ $item->quantity }}x R$ {{ number_format($item->unit_price, 2, ',', '.') }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-orange-500 text-xs font-black">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-20 opacity-20">
                        <p class="text-[10px] font-black uppercase tracking-widest text-white">Nenhum item lançado</p>
                    </div>
                @endforelse
            </div>

            <div class="p-6 bg-gray-950 border-t border-gray-800 space-y-4">
                <div class="flex justify-between items-end">
                    <span class="text-gray-500 text-[10px] font-black uppercase">Total Parcial</span>
                    <span class="text-3xl font-black text-white">
                        R$ {{ number_format($order->total_value, 2, ',', '.') }}
                    </span>
                </div>

                <button onclick="abrirCheckout()"
                    class="w-full py-5 bg-green-600 hover:bg-green-500 text-white font-black rounded-2xl uppercase text-[10px] tracking-[0.2em] transition-all shadow-xl shadow-green-600/20 active:scale-95">
                    🏁 Fechar e Receber
                </button>
            </div>
        </div>
    </div>

    <div id="modalCheckout" class="hidden fixed inset-0 bg-black/95 backdrop-blur-md z-[300] flex items-center justify-center p-4">
        <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] w-full max-w-4xl overflow-hidden shadow-2xl flex flex-col md:flex-row">

            <div class="p-8 bg-gray-800/30 border-r border-gray-800 w-full md:w-80">
                <h3 class="text-white font-black uppercase italic mb-6">Resumo</h3>
                <div class="space-y-6">
                    <div>
                        <span class="text-gray-500 text-[9px] font-black uppercase block">Total da Mesa</span>
                        <span class="text-2xl font-black text-white">R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
                    </div>
                    <div class="border-t border-gray-800 pt-4">
                        <span class="text-gray-500 text-[9px] font-black uppercase block">Faltando</span>
                        <span class="text-3xl font-black text-orange-500" id="textRestante">R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 text-[9px] font-black uppercase block">Troco</span>
                        <span class="text-2xl font-black text-green-500" id="textTroco">R$ 0,00</span>
                    </div>
                </div>
            </div>

            <div class="p-8 flex-1">
                <div class="flex justify-between mb-8">
                    <h3 class="text-xl font-black text-white uppercase italic">Pagamento Misto</h3>
                    <button onclick="document.getElementById('modalCheckout').classList.add('hidden')" class="text-gray-500 hover:text-white">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="text-[9px] font-black text-gray-500 uppercase mb-2 block">Valor a Adicionar</label>
                        <input type="number" id="inputValorPago" step="0.01" class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 text-xl font-black outline-none focus:border-orange-600">
                    </div>
                    <div>
                        <label class="text-[9px] font-black text-gray-500 uppercase mb-2 block">Forma de Pagamento</label>
                        <select id="selectMetodo" class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 font-black outline-none focus:border-orange-600">
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">💎 PIX</option>
                            <option value="debito">💳 Débito</option>
                            <option value="credito">💳 Crédito</option>
                        </select>
                    </div>
                </div>

                <button onclick="addPagamento()" class="w-full py-3 bg-gray-800 hover:bg-gray-700 text-white font-black rounded-xl uppercase text-[9px] mb-6 border border-gray-700 transition-all">
                    + Adicionar Pagamento
                </button>

                <div id="listaPagamentos" class="space-y-2 mb-8 max-h-32 overflow-y-auto pr-2">
                    </div>

                <form action="{{ route('bar.tables.close', $table->id) }}" method="POST" id="formFecharMesa">
                    @csrf
                    <input type="hidden" name="pagamentos" id="inputPagamentosHidden">
                    <button type="button" onclick="submeterFechamento()" id="btnFinalizar" disabled class="w-full py-5 bg-green-600 opacity-30 cursor-not-allowed text-white font-black rounded-2xl uppercase text-[10px] tracking-widest shadow-lg transition-all">
                        Finalizar e Liberar Mesa
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Lógica de Busca
        function liveSearch() {
            const query = document.getElementById('mainSearch').value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(card => {
                card.classList.toggle('hidden', !card.dataset.name.includes(query));
            });
        }

        // Lançar Item
        async function addItemToOrder(productId) {
            const response = await fetch("{{ route('bar.tables.add_item', $order->id) }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ product_id: productId, quantity: 1 })
            });
            if ((await response.json()).success) window.location.reload();
        }

        // Lógica de Pagamento
        let totalMesa = {{ $order->total_value }};
        let pagamentos = [];
        let pagoAcumulado = 0;

        function abrirCheckout() {
            document.getElementById('modalCheckout').classList.remove('hidden');
            document.getElementById('inputValorPago').value = (totalMesa - pagoAcumulado).toFixed(2);
        }

        function addPagamento() {
            const valor = parseFloat(document.getElementById('inputValorPago').value);
            const metodo = document.getElementById('selectMetodo').value;
            if (valor > 0) {
                pagamentos.push({ metodo, valor });
                atualizarTelaPagamento();
            }
        }

        function atualizarTelaPagamento() {
            pagoAcumulado = pagamentos.reduce((acc, p) => acc + p.valor, 0);
            const lista = document.getElementById('listaPagamentos');
            lista.innerHTML = pagamentos.map(p => `
                <div class="flex justify-between bg-gray-950 p-3 rounded-xl border border-gray-800 text-[10px] font-black uppercase">
                    <span class="text-white">${p.metodo}</span>
                    <span class="text-orange-500">R$ ${p.valor.toFixed(2)}</span>
                </div>
            `).join('');

            const faltante = totalMesa - pagoAcumulado;
            const troco = pagoAcumulado > totalMesa ? pagoAcumulado - totalMesa : 0;

            document.getElementById('textRestante').innerText = 'R$ ' + (faltante > 0 ? faltante : 0).toLocaleString('pt-br', {minimumFractionDigits: 2});
            document.getElementById('textTroco').innerText = 'R$ ' + troco.toLocaleString('pt-br', {minimumFractionDigits: 2});

            const btn = document.getElementById('btnFinalizar');
            if (pagoAcumulado >= totalMesa) {
                btn.disabled = false;
                btn.classList.remove('opacity-30', 'cursor-not-allowed');
            }
        }

        function submeterFechamento() {
            document.getElementById('inputPagamentosHidden').value = JSON.stringify(pagamentos);
            document.getElementById('formFecharMesa').submit();
        }
    </script>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        @keyframes slideIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
        .animate-slide-in { animation: slideIn 0.3s ease-out forwards; }
    </style>
</x-bar-layout>
