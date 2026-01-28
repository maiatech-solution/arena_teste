<x-bar-layout>
    <div class="max-w-2xl mx-auto px-4">
        <div class="mb-8">
            <h2 class="text-3xl font-black text-white uppercase tracking-tighter">🚛 Entrada de <span class="text-orange-500">Mercadoria</span></h2>
            <p class="text-gray-500 text-sm">Aumente o estoque dos produtos que acabaram de chegar.</p>
        </div>

        <div class="bg-gray-900 p-8 rounded-3xl border border-gray-800 shadow-2xl">
            <form action="{{ route('bar.products.process_entry') }}" method="POST">
                @csrf
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Selecione o Produto</label>
                        <select name="product_id" class="w-full bg-gray-950 border-gray-800 rounded-xl text-white p-4 focus:ring-orange-500 focus:border-orange-500">
                            <option value="">-- Escolha um item --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} (Atual: {{ $product->stock_quantity }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Quantidade que Chegou</label>
                        <input type="number" name="quantity" min="1" required
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white p-4 text-2xl font-bold focus:ring-orange-500 focus:border-orange-500 text-center"
                               placeholder="Ex: 12, 24, 50">
                    </div>

                    <button type="submit" class="w-full py-5 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-2xl transition shadow-xl uppercase tracking-widest">
                        Confirmar Entrada
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-bar-layout>
