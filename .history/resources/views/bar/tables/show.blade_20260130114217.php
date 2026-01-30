<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-6 flex flex-col md:flex-row gap-8">

        <div class="flex-1 bg-gray-900 rounded-[2.5rem] border border-gray-800 p-8 flex flex-col shadow-2xl">
            <div class="flex items-center justify-between mb-8">
                <a href="{{ route('bar.tables.index') }}" class="text-gray-500 hover:text-white transition-colors">◀ VOLTAR</a>
                <h2 class="text-2xl font-black text-white uppercase italic">Lançar na <span class="text-orange-500">Mesa {{ $table->identifier }}</span></h2>
            </div>

            <input type="text" id="mainSearch" onkeyup="liveSearch()" placeholder="🔍 Buscar produto..."
                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 mb-6 focus:border-orange-500 outline-none">

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 overflow-y-auto no-scrollbar pr-2" style="max-height: 500px;">
                @foreach($products as $product)
                    <button onclick="addItemToOrder({{ $product->id }}, '{{ $product->name }}', {{ $product->sale_price }})"
                        data-name="{{ strtolower($product->name) }}"
                        class="product-card p-4 bg-gray-800 hover:bg-orange-600 rounded-2xl border border-gray-700 transition-all text-center group active:scale-95">
                        <span class="block text-white font-black uppercase text-[10px] mb-1 truncate">{{ $product->name }}</span>
                        <span class="text-orange-500 group-hover:text-white font-bold">R$ {{ number_format($product->sale_price, 2, ',', '.') }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="w-full md:w-96 bg-gray-900 rounded-[2.5rem] border border-gray-800 flex flex-col shadow-2xl overflow-hidden">
            <div class="p-6 bg-gray-800/50 border-b border-gray-800">
                <h3 class="font-black text-white uppercase italic">📝 Itens Consumidos</h3>
            </div>

            <div class="flex-1 p-6 space-y-3 overflow-y-auto no-scrollbar" id="orderItemsList">
                @forelse($order->items as $item)
                    <div class="flex justify-between items-center bg-gray-950 p-3 rounded-xl border border-gray-800">
                        <div class="flex flex-col text-[10px] font-black uppercase">
                            <span class="text-white">{{ $item->quantity }}x {{ $item->product->name }}</span>
                            <span class="text-orange-500">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-600 text-[10px] font-black uppercase py-10">Mesa sem consumação</p>
                @endforelse
            </div>

            <div class="p-6 bg-gray-950 border-t border-gray-800 space-y-4">
                <div class="flex justify-between items-end">
                    <span class="text-gray-500 text-[10px] font-black uppercase">Total Parcial</span>
                    <span class="text-3xl font-black text-white">R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
                </div>

                <button onclick="alert('Lógica de pagamento: Escolher Dinheiro/Pix e fechar mesa')"
                    class="w-full py-4 bg-green-600 text-white font-black rounded-2xl uppercase text-xs tracking-widest shadow-lg shadow-green-600/20 active:scale-95 transition-all">
                    🏁 Fechar Conta
                </button>
            </div>
        </div>
    </div>
</x-bar-layout>
