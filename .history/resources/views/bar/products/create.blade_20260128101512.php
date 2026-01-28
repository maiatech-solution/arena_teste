<x-bar-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <a href="{{ route('bar.products.index') }}" class="text-orange-500 hover:text-orange-400 text-sm font-bold flex items-center gap-2 mb-4">
                ⬅️ VOLTAR PARA LISTAGEM
            </a>
            <h2 class="text-3xl font-black text-white uppercase tracking-tighter">🆕 Cadastrar <span class="text-orange-500">Novo Produto</span></h2>
        </div>

        <div class="bg-gray-900 rounded-3xl border border-gray-800 p-8 shadow-2xl">
            <form action="{{ route('bar.products.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Nome do Produto</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3"
                               placeholder="Ex: Cerveja Heineken 600ml">
                        @error('name') <p class="text(red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Código de Barras (Bipe agora)</label>
                        <input type="text" name="barcode" value="{{ old('barcode') }}"
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3 font-mono"
                               placeholder="Clique aqui e use o leitor">
                        @error('barcode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Estoque Inicial</label>
                        <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" required
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Preço de Custo (R$)</label>
                        <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price') }}" required
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Preço de Venda (R$)</label>
                        <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price') }}" required
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Estoque Mínimo (Alerta)</label>
                        <input type="number" name="min_stock" value="{{ old('min_stock', 5) }}" required
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                        <p class="text-[10px] text-gray-500 mt-1">O sistema avisará quando atingir este valor.</p>
                    </div>
                </div>

                <div class="mt-10 flex justify-end">
                    <button type="submit" class="px-10 py-4 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-2xl transition-all shadow-xl shadow-orange-600/20 uppercase tracking-widest">
                        Gravar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-bar-layout>
