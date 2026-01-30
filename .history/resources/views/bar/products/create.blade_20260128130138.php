<x-bar-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <a href="{{ route('bar.products.index') }}"
                class="text-orange-500 hover:text-orange-400 text-sm font-bold flex items-center gap-2 mb-4 transition-colors">
                ⬅️ VOLTAR PARA LISTAGEM
            </a>
            <h2 class="text-3xl font-black text-white uppercase tracking-tighter">🆕 Cadastrar <span class="text-orange-500">Novo Produto</span></h2>
        </div>

        <div class="bg-gray-900 rounded-3xl border border-gray-800 p-8 shadow-2xl">
            <form action="{{ route('bar.products.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2 tracking-widest">Nome do Produto</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3"
                            placeholder="Ex: Cerveja Heineken 600ml">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-1">
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest">Categoria</label>
                            <button type="button" onclick="openCategoryModal()"
                                    class="text-orange-500 hover:text-orange-400 text-[10px] font-black uppercase border border-orange-500/30 px-2 py-1 rounded-md transition-all active:scale-95">
                                + CADASTRAR NOVA
                            </button>
                        </div>
                        <select name="bar_category_id" id="category_select" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                            <option value="">Selecione uma categoria...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ old('bar_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('bar_category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2 tracking-widest">Código de Barras (EAN-13)</label>
                        <input type="text" name="barcode" value="{{ old('barcode') }}" maxlength="13"
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3 font-mono"
                            placeholder="Bipe agora">
                        @error('barcode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-orange-500 uppercase mb-2 tracking-widest">Unidade de Compra</label>
                        <select name="unit_type" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                            <option value="UN" {{ old('unit_type') == 'UN' ? 'selected' : '' }}>UNIDADE (UN)</option>
                            <option value="FD" {{ old('unit_type') == 'FD' ? 'selected' : '' }}>FARDO (FD)</option>
                            <option value="CX" {{ old('unit_type') == 'CX' ? 'selected' : '' }}>CAIXA (CX)</option>
                            <option value="KG" {{ old('unit_type') == 'KG' ? 'selected' : '' }}>QUILO (KG)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-orange-500 uppercase mb-2 tracking-widest">Qtd no Fardo/Caixa</label>
                        <input type="number" name="content_quantity" value="{{ old('content_quantity', 1) }}" min="1" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3"
                            placeholder="Ex: 12">
                        <p class="text-[9px] text-gray-500 mt-1 uppercase font-bold italic">* Se vender por unidade, mantenha 1.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2 tracking-widest">Preço de Custo (R$)</label>
                        <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price') }}" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2 tracking-widest">Preço de Venda (R$)</label>
                        <input type="number" step="0.01" name="sale_price" value="{{ old('sale_price') }}" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3 font-bold text-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2 tracking-widest">Estoque Inicial (Total UN)</label>
                        <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-2 tracking-widest">Estoque Mínimo (UN)</label>
                        <input type="number" name="min_stock" value="{{ old('min_stock', 5) }}" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>
                </div>

                <div class="mt-10 pt-6 border-t border-gray-800 flex justify-end">
                    <button type="submit"
                        class="px-10 py-4 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-2xl transition-all shadow-xl shadow-orange-600/20 uppercase tracking-widest">
                        Gravar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="catModal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-gray-900 border border-gray-800 p-6 rounded-3xl w-full max-w-sm shadow-2xl">
            <h3 class="text-white font-black uppercase text-sm mb-4">Cadastrar Categoria</h3>
            <input type="text" id="new_cat_name"
                   class="w-full bg-gray-950 border-gray-800 rounded-xl text-white p-3 mb-6 focus:border-orange-500 outline-none"
                   placeholder="Ex: Cervejas, Porções...">

            <div class="flex gap-3">
                <button type="button" onclick="closeCategoryModal()" class="flex-1 py-2 text-gray-500 font-bold text-xs uppercase">Cancelar</button>
                <button type="button" onclick="saveCategory()" class="flex-1 py-3 bg-orange-600 text-white rounded-xl font-black text-xs uppercase">Salvar</button>
            </div>
        </div>
    </div>

    <script>
        function openCategoryModal() {
            document.getElementById('catModal').classList.remove('hidden');
            document.getElementById('new_cat_name').focus();
        }

        function closeCategoryModal() {
            document.getElementById('catModal').classList.add('hidden');
            document.getElementById('new_cat_name').value = '';
        }

        async function saveCategory() {
            const nameInput = document.getElementById('new_cat_name');
            const name = nameInput.value;

            if(!name) return alert('Digite o nome da categoria!');

            try {
                const response = await fetch("{{ route('bar.categories.store_ajax') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name: name })
                });

                if (!response.ok) throw new Error('Erro ao salvar');

                const data = await response.json();

                const select = document.getElementById('category_select');
                const option = new Option(data.name, data.id, true, true);
                select.add(option);

                closeCategoryModal();
            } catch (error) {
                alert('Erro: Categoria já existe ou falha na conexão.');
            }
        }
    </script>
</x-bar-layout>
