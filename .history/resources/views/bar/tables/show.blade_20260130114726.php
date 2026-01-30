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
                            <span class="text-gray-500 text-[9px] font-bold">{{ $item->quantity }}x R$ {{ number_format($item->price_at_sale, 2, ',', '.') }}</span>
                        </div>
                        <div class="text-right flex flex-col items-end">
                            <span class="text-orange-500 text-xs font-black">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</span>
                            {{-- Botão opcional para remover item se precisar futuramente --}}
                        </div>
                    </div>
                @empty
                    <div class="text-center py-20 opacity-20">
                        <span class="text-5xl block mb-4">Empty</span>
                        <p class="text-[10px] font-black uppercase tracking-widest">Nenhum item lançado</p>
                    </div>
                @endforelse
            </div>

            <div class="p-6 bg-gray-950 border-t border-gray-800 space-y-4">
                <div class="flex justify-between items-end">
                    <span class="text-gray-500 text-[10px] font-black uppercase">Total Parcial</span>
                    <span class="text-3xl font-black text-white" id="orderTotalText">
                        R$ {{ number_format($order->total_value, 2, ',', '.') }}
                    </span>
                </div>

                <button onclick="window.location.href='#'"
                    class="w-full py-5 bg-green-600 hover:bg-green-500 text-white font-black rounded-2xl uppercase text-[10px] tracking-[0.2em] transition-all shadow-xl shadow-green-600/20 active:scale-95">
                    🏁 Fechar e Receber
                </button>
            </div>
        </div>
    </div>

    <script>
        // Busca Dinâmica de Produtos
        function liveSearch() {
            const query = document.getElementById('mainSearch').value.toLowerCase();
            const cards = document.querySelectorAll('.product-card');

            cards.forEach(card => {
                const name = card.dataset.name;
                card.classList.toggle('hidden', !name.includes(query));
            });
        }

        // Lançar Item na Mesa (Ajax)
        async function addItemToOrder(productId) {
            // Usamos a rota que definimos no web.php
            const url = "{{ route('bar.tables.add_item', $order->id) }}";

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Recarrega para atualizar a lista de itens e o total
                    window.location.reload();
                } else {
                    alert('Erro ao lançar: ' + data.message);
                }
            } catch (error) {
                console.error(error);
                alert('Erro de conexão com o servidor.');
            }
        }
    </script>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .animate-slide-in { animation: slideIn 0.3s ease-out forwards; }
    </style>
</x-bar-layout>
