<x-bar-layout>
    <div class="max-w-4xl mx-auto px-4">
        <div class="mb-8">
            <h2 class="text-3xl font-black text-white uppercase tracking-tighter">🚛 Entrada de <span class="text-orange-500">Mercadoria</span></h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-gray-900 p-6 rounded-3xl border border-gray-800 shadow-xl">
                    <input type="text" id="productSearch" placeholder="🔍 Digite o nome ou bipe o código..."
                           class="w-full bg-gray-950 border-gray-800 rounded-xl text-white p-4 mb-4 focus:ring-orange-500">

                    <div class="flex flex-wrap gap-2 mb-6">
                        <button onclick="filterCat('all')" class="cat-btn active px-4 py-2 rounded-lg text-xs font-bold uppercase bg-orange-600 text-white">Todos</button>
                        @foreach(['Bebidas', 'Comidas', 'Destilados', 'Petiscos'] as $cat)
                            <button onclick="filterCat('{{ $cat }}')" class="cat-btn px-4 py-2 rounded-lg text-xs font-bold uppercase bg-gray-800 text-gray-400 hover:bg-gray-700 transition">{{ $cat }}</button>
                        @endforeach
                    </div>

                    <div class="max-h-96 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                        @foreach($products as $product)
                            <div class="product-item p-4 bg-gray-950 rounded-xl border border-gray-800 hover:border-orange-500 cursor-pointer transition group"
                                 data-name="{{ strtolower($product->name) }}"
                                 data-category="{{ $product->category }}"
                                 onclick="selectProduct('{{ $product->id }}', '{{ $product->name }}', '{{ $product->stock_quantity }}')">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-white font-bold uppercase text-sm group-hover:text-orange-500">{{ $product->name }}</p>
                                        <p class="text-gray-500 text-[10px] uppercase font-bold">{{ $product->category }} | 🏷️ {{ $product->barcode ?? 'Sem código' }}</p>
                                    </div>
                                    <span class="text-xs font-mono text-gray-400">Estoque: {{ $product->stock_quantity }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div id="confirmBox" class="bg-gray-900 p-6 rounded-3xl border border-orange-600/30 shadow-2xl sticky top-8 opacity-50 pointer-events-none">
                    <h3 class="text-orange-500 font-black uppercase text-xs mb-4 tracking-widest">Confirmar Abastecimento</h3>

                    <form action="{{ route('bar.products.process_entry') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" id="selected_id">

                        <div class="mb-6">
                            <p id="selected_name" class="text-xl font-black text-white uppercase leading-tight">Selecione um produto</p>
                            <p id="selected_stock" class="text-xs text-gray-500 mt-1 uppercase font-bold">Estoque atual: --</p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-2">Quantidade de Entrada</label>
                            <input type="number" name="quantity" min="1" required
                                   class="w-full bg-gray-950 border-gray-800 rounded-xl text-white p-4 text-3xl font-black text-center focus:ring-orange-500 focus:border-orange-500">
                        </div>

                        <button type="submit" class="w-full py-4 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-2xl transition shadow-xl shadow-orange-600/20 uppercase tracking-tighter">
                            Finalizar Entrada
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filterCat(cat) {
            document.querySelectorAll('.cat-btn').forEach(btn => btn.classList.remove('bg-orange-600', 'text-white'));
            event.target.classList.add('bg-orange-600', 'text-white');

            const items = document.querySelectorAll('.product-item');
            items.forEach(item => {
                const matchCat = cat === 'all' || item.dataset.category === cat;
                item.style.display = matchCat ? 'block' : 'none';
            });
        }

        document.getElementById('productSearch').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            const items = document.querySelectorAll('.product-item');

            items.forEach(item => {
                const matchName = item.dataset.name.includes(term);
                item.style.display = matchName ? 'block' : 'none';
            });
        });

        function selectProduct(id, name, stock) {
            document.getElementById('selected_id').value = id;
            document.getElementById('selected_name').innerText = name;
            document.getElementById('selected_stock').innerText = 'Estoque atual: ' + stock;
            document.getElementById('confirmBox').classList.remove('opacity-50', 'pointer-events-none');
            document.querySelector('input[name="quantity"]').focus();
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
    </style>
</x-bar-layout>
