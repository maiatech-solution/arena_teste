<x-bar-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <a href="{{ route('bar.products.index') }}" class="text-orange-500 hover:text-orange-400 text-sm font-bold flex items-center gap-2 mb-4">
                ⬅️ CANCELAR E VOLTAR
            </a>
            <h2 class="text-3xl font-black text-white uppercase tracking-tighter">⚙️ Editar <span class="text-orange-500">{{ $product->name }}</span></h2>
        </div>

        <div class="bg-gray-900 rounded-3xl border border-gray-800 p-8 shadow-2xl">
            <form action="{{ route('bar.products.update', ['estoque' => $product->id]) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Nome do Produto</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Código de Barras</label>
                        <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}" maxlength="13"
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Status do Produto</label>
                        <select name="is_active" class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                            <option value="1" {{ $product->is_active ? 'selected' : '' }}>🟢 ATIVO (Disponível para venda)</option>
                            <option value="0" {{ !$product->is_active ? 'selected' : '' }}>🔴 INATIVO (Ocultar no PDV)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Preço de Venda (R$)</label>
                        <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" required
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white font-bold text-orange-500 focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Estoque Mínimo</label>
                        <input type="number" name="min_stock" value="{{ old('min_stock', $product->min_stock) }}" required
                               class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>
                </div>

                <div class="mt-10 flex justify-between items-center border-t border-gray-800 pt-8">
                    <p class="text-xs text-gray-500 italic">Última atualização: {{ $product->updated_at->format('d/m/Y H:i') }}</p>
                    <button type="submit" class="px-10 py-4 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-2xl transition-all shadow-xl shadow-orange-600/20 uppercase tracking-widest">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-bar-layout>
