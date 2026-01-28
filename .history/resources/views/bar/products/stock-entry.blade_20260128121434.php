<x-bar-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-8">
            <a href="{{ route('bar.products.index') }}"
                class="text-orange-500 hover:text-orange-400 text-sm font-bold flex items-center gap-2 mb-4 transition-colors">
                ⬅️ VOLTAR PARA ESTOQUE
            </a>
            <h2 class="text-3xl font-black text-white uppercase tracking-tighter">🚛 Entrada de <span class="text-orange-500">Mercadoria</span></h2>
            <p class="text-gray-500 text-sm">Abasteça seu inventário selecionando os itens abaixo.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-gray-900 p-6 rounded-3xl border border-gray-800 shadow-xl">
                    <input type="text" id="productSearch" placeholder="🔍 Digite o nome ou bipe o código..."
                           class="w-full bg-gray-950 border-gray-800 rounded-xl text-white p-4 mb-4 focus:ring-orange-500 outline-none transition-all"
                           autofocus>

                    <div class="flex flex-wrap gap-2 mb-6">
                        <button onclick="filterCat('all')" class="cat-btn active px-4 py-2 rounded-lg text-[10px] font-black uppercase bg-orange-600 text-white transition-all">
                            Todos
                        </button>
                        @foreach($categories as $cat)
                            <button onclick="filterCat('{{ $cat->name }}')"
                                    class="cat-btn px-4 py-2 rounded-lg text-[10px] font-black uppercase bg-gray-800 text-gray-400 hover:bg-gray-700 transition-all">
                                {{ $cat->name }}
                            </button>
                        @endforeach
                    </div>

                    <div class="max-h-[500px] overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                        @forelse($products as $product)
                            <div class="product-item p-4 bg-gray-950 rounded-2xl border border-gray-800 hover:border-orange-500/50 cursor-pointer transition-all group"
                                 data-name="{{ strtolower($product->name) }}"
                                 data-category="{{ $product->category->name ?? 'Outros' }}"
                                 onclick="selectProduct('{{ $product->id }}', '{{ $product->name }}', '{{ $product->stock_quantity }}')">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-white font-bold uppercase text-sm group-hover:text-orange-500 transition-colors">{{ $product->name }}</p>
                                        <p class="text-gray-500 text-[10px] uppercase font-black tracking-widest mt-1">
                                            {{ $product->category->name ?? 'Sem categoria' }} | 🏷️ {{ $product->barcode ?? 'SEM EAN' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] text-gray-500 uppercase block font-bold">Atual</span>
                                        <span class="text-sm font-black text-gray-300">{{ $product->stock_quantity }} unid.</span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="p-12 text-center text-gray-600 italic">Nenhum produto ativo encontrado.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1">
                <div id="confirmBox" class="bg-gray-900 p-8 rounded-3xl border border-gray-800 shadow-2xl sticky top-8 opacity-30 pointer-events-none transition-all duration-300">
                    <h3 class="text-orange-500 font-black uppercase text-xs mb-6 tracking-widest text-center">Confirmar Abastecimento</h3>

                    <form action="{{ route('bar.products.process_entry') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" id="selected_id">

                        <div class="mb-8 text-center">
                            <p id="selected_name" class="text-2xl font-black text-white uppercase leading-tight">Escolha um item</p>
                            <p id="selected_stock" class="text-xs text-gray-500 mt-2 uppercase font-bold tracking-tighter">Aguardando seleção...</p>
                        </div>

                        <div class="mb-8">
                            <label class="block text-center text-[10px] font-black text-gray-400 uppercase mb-3 tracking-widest">Quantidade de Entrada</label>
                            <input type="number" name="quantity" min="1" required
                                   class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-5 text-4xl font-black text-center focus:ring-orange-500 focus:border-orange-500 outline-none shadow-inner">
                        </div>

                        <button type="submit" class="w-full py-5 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-2xl transition-all shadow-xl shadow-orange-600/20 uppercase tracking-widest active:scale-95">
                            Finalizar Entrada
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function filterCat(cat) {
            // Estética dos botões
            document.querySelectorAll('.cat-btn').forEach(btn => {
                btn.classList.remove('bg-orange-600', 'text-white');
                btn.classList.add('bg-gray-800', 'text-gray-400');
            });
            event.target.classList.remove('bg-gray-800', 'text-gray-400');
            event.target.classList.add('bg-orange-600', 'text-white');

            // Lógica de filtro
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
            document.getElementById('selected_stock').innerText = 'Estoque atual: ' + stock + ' unidades';

            const box = document.getElementById('confirmBox');
            box.classList.remove('opacity-30', 'pointer-events-none');
            box.classList.add('border-orange-600/50');

            document.querySelector('input[name="quantity"]').focus();
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
    </style>
</x-bar-layout>
