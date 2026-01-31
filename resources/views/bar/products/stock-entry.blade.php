<x-bar-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8">
            <a href="{{ route('bar.products.index') }}"
                class="text-orange-500 hover:text-orange-400 text-[10px] font-black flex items-center gap-2 mb-4 transition-all uppercase tracking-widest">
                ⬅️ VOLTAR PARA ESTOQUE
            </a>
            <h2 class="text-4xl font-black text-white uppercase tracking-tighter italic">🚛 Entrada de <span class="text-orange-500">Mercadoria</span></h2>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-wide mt-1">Abasteça seu inventário selecionando os itens abaixo.</p>
        </div>

        {{-- 1. BLOCO ISCA (Invisível): O navegador 'descarrega' os dados salvos aqui --}}
        <div style="position: absolute; left: -9999px; top: -9999px;">
            <input type="text" name="fake_search_user">
            <input type="password" name="fake_search_pass">
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- COLUNA DA ESQUERDA: LISTAGEM --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-gray-900 p-6 rounded-[2.5rem] border border-gray-800 shadow-2xl">
                    {{-- BUSCA DE PRODUTOS BLINDADA --}}
                    <div class="relative mb-6">
                        <input type="text" id="productSearch"
                            placeholder="🔍 Digite o nome ou bipe o código..."
                            autocomplete="off"
                            readonly
                            onfocus="this.removeAttribute('readonly');"
                            class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-5 focus:ring-2 focus:ring-orange-500 outline-none transition-all font-bold placeholder:text-gray-700 shadow-inner"
                            autofocus>
                    </div>

                    {{-- FILTRO POR CATEGORIA --}}
                    <div class="flex flex-wrap gap-2 mb-6">
                        <button onclick="filterCat('all')" class="cat-btn active px-5 py-2.5 rounded-xl text-[10px] font-black uppercase bg-orange-600 text-white transition-all shadow-lg shadow-orange-600/20">
                            Todos
                        </button>
                        @foreach($categories as $cat)
                        <button onclick="filterCat('{{ $cat->name }}')"
                            class="cat-btn px-5 py-2.5 rounded-xl text-[10px] font-black uppercase bg-gray-800 text-gray-500 hover:bg-gray-700 transition-all border border-gray-700/50">
                            {{ $cat->name }}
                        </button>
                        @endforeach
                    </div>

                    {{-- LISTA DE PRODUTOS --}}
                    <div class="max-h-[550px] overflow-y-auto space-y-3 pr-2 custom-scrollbar">
                        @forelse($products as $product)
                        <div class="product-item p-5 bg-gray-950 rounded-2xl border border-gray-800 hover:border-orange-500/50 cursor-pointer transition-all group relative overflow-hidden"
                            data-name="{{ strtolower($product->name) }}"
                            data-category="{{ $product->category->name ?? 'Outros' }}"
                            onclick="selectProduct('{{ $product->id }}', '{{ $product->name }}', '{{ $product->stock_quantity }}', '{{ $product->unit_type }}', '{{ $product->content_quantity }}')">

                            <div class="flex justify-between items-center relative z-10">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="text-white font-black uppercase text-sm group-hover:text-orange-500 transition-colors tracking-tight">{{ $product->name }}</p>
                                        @if(!$product->manage_stock)
                                        <span class="text-[8px] bg-gray-800 text-gray-500 px-2 py-0.5 rounded font-black uppercase border border-gray-700">Sem Controle</span>
                                        @endif
                                    </div>
                                    <p class="text-gray-600 text-[10px] uppercase font-black tracking-[0.1em] mt-1 italic">
                                        {{ $product->category->name ?? 'Sem categoria' }} | 🏷️ {{ $product->barcode ?? 'SEM EAN' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[9px] text-gray-600 uppercase block font-black mb-1">Saldo em UN</span>
                                    <span class="text-lg font-black {{ $product->stock_quantity <= $product->min_stock ? 'text-red-500' : 'text-gray-300' }}">
                                        {{ $product->stock_quantity }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="p-20 text-center text-gray-700 font-black uppercase text-xs tracking-widest opacity-30">Nenhum produto disponível</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- COLUNA DA DIREITA: CONFIRMAÇÃO --}}
            <div class="lg:col-span-1">
                <div id="confirmBox" class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl sticky top-8 opacity-30 pointer-events-none transition-all duration-500">
                    <h3 class="text-orange-600 font-black uppercase text-[10px] mb-8 tracking-[0.2em] text-center border-b border-gray-800 pb-4">Confirmar Entrada</h3>

                    <form id="formEntradaEstoque" action="{{ route('bar.products.process_entry') }}" method="POST" autocomplete="off">
                        @csrf
                        <input type="hidden" name="product_id" id="selected_id">
                        <input type="hidden" name="content_quantity" id="selected_factor" value="1">

                        <div class="mb-10 text-center">
                            <p id="selected_name" class="text-2xl font-black text-white uppercase leading-none tracking-tighter">Escolha um item</p>
                            <div id="selected_stock" class="inline-block px-4 py-1.5 bg-gray-950 rounded-full text-[10px] text-gray-500 mt-4 uppercase font-black tracking-widest border border-gray-800">
                                Aguardando seleção...
                            </div>
                        </div>

                        <div id="unitSelector" class="hidden mb-8 flex bg-gray-950 p-1.5 rounded-2xl border border-gray-800 shadow-inner">
                            <button type="button" onclick="setEntryMode('UN')" id="btn_un" class="flex-1 py-3 text-[10px] font-black uppercase rounded-xl transition-all bg-orange-600 text-white shadow-lg">Unidades</button>
                            <button type="button" onclick="setEntryMode('BULK')" id="btn_bulk" class="flex-1 py-3 text-[10px] font-black uppercase rounded-xl transition-all text-gray-600 hover:text-gray-300">Fardo/Caixa</button>
                            <input type="hidden" name="entry_mode" id="entry_mode" value="UN">
                        </div>

                        {{-- QUANTIDADE BLINDADA --}}
                        <div class="mb-10">
                            <label id="label_qty" class="block text-center text-[10px] font-black text-gray-600 uppercase mb-4 tracking-[0.2em]">Quantidade de Entrada</label>
                            <input type="number" name="quantity" min="1" required
                                id="mainQtyInput"
                                readonly
                                onfocus="this.removeAttribute('readonly');"
                                autocomplete="new-password"
                                class="w-full bg-gray-950 border-gray-800 rounded-[2rem] text-white p-8 text-5xl font-black text-center focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none shadow-inner transition-all">

                            <p id="conversion_preview" class="hidden text-center text-orange-500 text-[11px] font-black uppercase mt-5 animate-pulse tracking-widest"></p>
                        </div>

                        <button type="button"
                            onclick="requisitarAutorizacao(() => { document.forms['formEntradaEstoque'].submit(); })"
                            class="w-full py-6 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-2xl transition-all shadow-xl shadow-orange-600/20 uppercase text-xs tracking-[0.2em] active:scale-95">
                            Finalizar Entrada
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentFactor = 1;
        let currentUnitType = 'UN';

        function filterCat(cat) {
            document.querySelectorAll('.cat-btn').forEach(btn => {
                btn.classList.remove('bg-orange-600', 'text-white', 'shadow-lg', 'shadow-orange-600/20');
                btn.classList.add('bg-gray-800', 'text-gray-500');
            });
            event.currentTarget.classList.remove('bg-gray-800', 'text-gray-500');
            event.currentTarget.classList.add('bg-orange-600', 'text-white', 'shadow-lg', 'shadow-orange-600/20');

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

        function selectProduct(id, name, stock, unitType, factor) {
            currentFactor = parseInt(factor);
            currentUnitType = unitType;

            document.getElementById('selected_id').value = id;
            document.getElementById('selected_factor').value = factor;
            document.getElementById('selected_name').innerText = name;
            document.getElementById('selected_stock').innerText = 'Saldo atual: ' + stock + ' UN';

            const unitSelector = document.getElementById('unitSelector');
            if (currentFactor > 1) {
                unitSelector.classList.remove('hidden');
                document.getElementById('btn_bulk').innerText = unitType === 'FD' ? 'Fardos (' + factor + ')' : 'Caixas (' + factor + ')';
            } else {
                unitSelector.classList.add('hidden');
            }

            setEntryMode('UN');

            const box = document.getElementById('confirmBox');
            box.classList.remove('opacity-30', 'pointer-events-none');
            box.classList.add('border-orange-600/50', 'bg-gray-900/100');

            const qtyInput = document.querySelector('input[name="quantity"]');
            qtyInput.value = '';
            qtyInput.focus();
        }

        function setEntryMode(mode) {
            const btnUn = document.getElementById('btn_un');
            const btnBulk = document.getElementById('btn_bulk');
            const entryMode = document.getElementById('entry_mode');
            const labelQty = document.getElementById('label_qty');

            entryMode.value = mode;

            if (mode === 'UN') {
                btnUn.classList.add('bg-orange-600', 'text-white', 'shadow-lg');
                btnBulk.classList.remove('bg-orange-600', 'text-white', 'shadow-lg');
                btnBulk.classList.add('text-gray-600');
                labelQty.innerText = 'Quantidade de Entrada (UNID)';
            } else {
                btnBulk.classList.add('bg-orange-600', 'text-white', 'shadow-lg');
                btnUn.classList.remove('bg-orange-600', 'text-white', 'shadow-lg');
                btnUn.classList.add('text-gray-600');
                labelQty.innerText = 'Qtd de ' + (currentUnitType === 'FD' ? 'Fardos' : 'Caixas');
            }
            updatePreview();
        }

        document.querySelector('input[name="quantity"]').addEventListener('input', updatePreview);

        function updatePreview() {
            const qty = document.querySelector('input[name="quantity"]').value;
            const mode = document.getElementById('entry_mode').value;
            const preview = document.getElementById('conversion_preview');

            if (mode === 'BULK' && qty > 0) {
                const total = qty * currentFactor;
                preview.innerText = '🚀 Total a entrar: ' + total + ' unidades';
                preview.classList.remove('hidden');
            } else {
                preview.classList.add('hidden');
            }
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 10px;
        }

        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
        }
    </style>
</x-bar-layout>