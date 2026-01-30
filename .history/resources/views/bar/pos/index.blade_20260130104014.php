<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 sm:px-8 lg:px-10 py-6">

        <div class="flex flex-col md:flex-row gap-8  overflow-hidden">

            <div
                class="flex-1 flex flex-col min-h-0 bg-gray-900 rounded-[2.5rem] border border-gray-800 shadow-2xl overflow-hidden">

                <div class="p-8 border-b border-gray-800 bg-gray-900/50 space-y-6">
                    <div class="flex flex-col lg:flex-row gap-6 items-center">
                        <div class="flex items-center gap-4">
                            <a href="{{ route('bar.dashboard') }}"
                                class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg group"
                                title="Voltar ao Início">
                                <span
                                    class="group-hover:-translate-x-1 transition-transform duration-200 inline-block">◀</span>
                            </a>
                            <div>
                                <h2 class="text-xl font-black text-white uppercase tracking-tighter italic">Venda <span
                                        class="text-orange-500">Direta</span></h2>
                            </div>
                        </div>

                        <div class="flex-1 relative">
                            <input type="text" id="mainSearch" onkeyup="liveSearch()"
                                placeholder="🔍 Nome ou Código de Barras (Bipagem)..."
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 outline-none transition-all">
                        </div>

                        <div class="md:w-64">
                            <select id="catFilter" onchange="liveSearch()"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 focus:border-orange-500 outline-none uppercase font-black text-xs tracking-widest cursor-pointer">
                                <option value="all">📁 TODAS CATEGORIAS</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 no-scrollbar"
                    id="productsGrid">
                    @foreach ($products as $product)
                        <button {{-- 🚀 Adicionado $product->manage_stock como 5º parâmetro --}}
                            onclick="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->sale_price }}, {{ $product->stock_quantity }}, {{ $product->manage_stock }})"
                            data-category="{{ $product->bar_category_id }}" data-name="{{ strtolower($product->name) }}"
                            data-barcode="{{ $product->barcode }}"
                            class="product-card group relative p-3 bg-gray-800 hover:bg-orange-600 rounded-2xl border border-gray-700 hover:border-orange-500 transition-all flex flex-col items-center justify-center text-center active:scale-95 shadow-md h-28">

                            {{-- 🔔 Badge de Estoque: Só pisca em vermelho se manage_stock for TRUE --}}
                            <div class="absolute top-1.5 right-1.5">
                                <span
                                    class="text-[7px] font-black px-1.5 py-0.5 rounded bg-gray-950/60
                    {{ $product->manage_stock && $product->stock_quantity <= $product->min_stock ? 'text-red-500 animate-pulse' : 'text-gray-400' }}">
                                    {{ $product->stock_quantity }}
                                </span>
                            </div>

                            <div class="text-xl mb-1 group-hover:scale-110 transition-transform">📦</div>

                            <div class="w-full">
                                <h3
                                    class="text-[9px] font-black text-white uppercase leading-tight line-clamp-2 mb-1 group-hover:text-white px-1">
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

            <div
                class="w-full md:w-96 flex flex-col bg-gray-900 rounded-3xl border border-gray-800 shadow-2xl overflow-hidden">
                <div class="p-6 border-b border-gray-800 flex items-center justify-between">
                    <h3 class="text-lg font-black text-white uppercase italic">🧾 Carrinho</h3>
                    <button onclick="clearCart()"
                        class="text-[10px] font-black text-red-500 hover:underline uppercase tracking-tighter">Limpar</button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-3 no-scrollbar" id="cartItems">
                    <div id="emptyCart" class="text-center py-20">
                        <span class="text-5xl block mb-4 opacity-20">🛒</span>
                        <p class="text-gray-500 text-[10px] font-black uppercase tracking-widest">Aguardando Produtos
                        </p>
                    </div>
                </div>

                <div class="p-6 bg-gray-950 border-t border-gray-800">
                    <div class="flex justify-between items-end mb-6">
                        <span class="text-gray-500 text-[10px] font-black uppercase">Total do Pedido</span>
                        <span class="text-3xl font-black text-white" id="cartTotalText">R$ 0,00</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-6">
                        @php
                            $metodos = [
                                'dinheiro' => ['label' => 'Dinheiro', 'icon' => '💵'],
                                'pix' => ['label' => 'PIX', 'icon' => '📱'],
                                'debito' => ['label' => 'Débito', 'icon' => '💳'],
                                'credito' => ['label' => 'Crédito', 'icon' => '💳'],
                            ];
                        @endphp
                        @foreach ($metodos as $key => $info)
                            <button onclick="setPaymentMethod('{{ $key }}')"
                                class="pay-btn flex items-center gap-2 p-3 rounded-2xl border border-gray-800 bg-gray-900 text-gray-500 transition-all font-black text-[10px] uppercase hover:border-orange-500/50"
                                id="btn-{{ $key }}">
                                <span>{{ $info['icon'] }}</span>
                                {{ $info['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <button onclick="checkout()" id="finishBtn" disabled
                        class="w-full py-5 bg-gray-800 text-gray-500 font-black rounded-2xl uppercase tracking-[0.2em] text-xs transition-all cursor-not-allowed shadow-xl">
                        Finalizar Venda
                    </button>
                </div>
            </div>
        </div>
        <script>
            let cart = [];
            let paymentMethod = null;

            // 🟢 ADICIONAR AO CARRINHO (AGORA COM LÓGICA DE GESTÃO)
            function addToCart(id, name, price, stock, manageStock) {
                const existingItem = cart.find(item => item.id === id);

                if (existingItem) {
                    // 🚀 AJUSTE: Só barra se o controle estiver ligado E atingir o estoque
                    if (manageStock && existingItem.quantity >= stock) {
                        alert('🚫 Limite de estoque atingido para este produto!');
                        return;
                    }
                    existingItem.quantity += 1;
                } else {
                    // Se for o primeiro item, ainda assim verifica se tem estoque caso seja controlado
                    if (manageStock && stock <= 0) {
                        alert('🚫 Produto sem estoque disponível!');
                        return;
                    }

                    cart.push({
                        id,
                        name,
                        price,
                        quantity: 1,
                        stock,
                        manageStock // 🛡️ Guardamos se é controlado ou não
                    });
                }
                renderCart();
            }

            // 🔴 ALTERAR QUANTIDADE MANUALMENTE NO CARRINHO
            function changeQty(index, delta) {
                const item = cart[index];

                if (delta > 0) {
                    // 🚀 AJUSTE: Só barra o aumento se for controlado (manageStock == 1)
                    if (item.manageStock && item.quantity >= item.stock) {
                        alert('🚫 Limite de estoque atingido!');
                        return;
                    }
                    item.quantity += 1;
                } else if (delta < 0 && item.quantity > 1) {
                    item.quantity -= 1;
                }
                renderCart();
            }

            function removeFromCart(index) {
                cart.splice(index, 1);
                renderCart();
            }

            function clearCart() {
                if (confirm('Limpar todo o carrinho?')) {
                    cart = [];
                    renderCart();
                }
            }

            // 🔄 RENDERIZAR INTERFACE DO CARRINHO
            function renderCart() {
                const container = document.getElementById('cartItems');
                const totalText = document.getElementById('cartTotalText');
                const finishBtn = document.getElementById('finishBtn');

                if (cart.length === 0) {
                    container.innerHTML =
                        '<div id="emptyCart" class="text-center py-20"><span class="text-5xl block mb-4 opacity-20">🛒</span><p class="text-gray-500 text-[10px] font-black uppercase tracking-widest">Aguardando Produtos</p></div>';
                    totalText.innerText = 'R$ 0,00';
                    validateCheckout(); // Chama para garantir que o botão desabilite
                    return;
                }

                container.innerHTML = cart.map((item, index) => `
        <div class="flex items-center justify-between bg-gray-800 p-3 rounded-2xl border border-gray-700 animate-slide-in">
            <div class="flex flex-col flex-1">
                <span class="text-white text-[10px] font-black uppercase truncate w-32">${item.name}</span>
                <span class="text-orange-500 text-xs font-bold">R$ ${(item.price * item.quantity).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
            </div>
            <div class="flex items-center gap-3 bg-gray-950 px-3 py-2 rounded-xl border border-gray-800">
                <button onclick="changeQty(${index}, -1)" class="text-orange-500 font-black hover:scale-125 transition-transform">-</button>
                <span class="text-white text-[11px] font-black w-6 text-center">x${item.quantity}</span>
                <button onclick="changeQty(${index}, 1)" class="text-orange-500 font-black hover:scale-125 transition-transform">+</button>
            </div>
            <button onclick="removeFromCart(${index})" class="ml-3 text-red-500 hover:text-white transition-colors">✕</button>
        </div>
    `).join('');

                const total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
                totalText.innerText = `R$ ${total.toLocaleString('pt-BR', {minimumFractionDigits: 2})}`;

                validateCheckout();
            }

            // 🔍 BUSCA EM TEMPO REAL E FILTRO POR CATEGORIA
            function liveSearch() {
                const query = document.getElementById('mainSearch').value.toLowerCase();
                const catId = document.getElementById('catFilter').value;
                const cards = document.querySelectorAll('.product-card');

                cards.forEach(card => {
                    const name = card.dataset.name.toLowerCase();
                    const barcode = card.dataset.barcode;
                    const category = card.dataset.category;

                    const matchesSearch = name.includes(query) || barcode.includes(query);
                    const matchesCat = catId === 'all' || category === catId;

                    if (matchesSearch && matchesCat) {
                        card.classList.remove('hidden');
                    } else {
                        card.classList.add('hidden');
                    }
                });
            }

            // 💳 SELECIONAR MÉTODO DE PAGAMENTO
            function setPaymentMethod(method) {
                paymentMethod = method;
                document.querySelectorAll('.pay-btn').forEach(btn => {
                    btn.classList.remove('bg-orange-600', 'text-white', 'border-orange-500', 'shadow-lg',
                        'shadow-orange-600/20');
                    btn.classList.add('bg-gray-900', 'text-gray-500', 'border-gray-800');
                });
                const selectedBtn = document.getElementById('btn-' + method);
                selectedBtn.classList.replace('bg-gray-900', 'bg-orange-600');
                selectedBtn.classList.replace('text-gray-500', 'text-white');
                selectedBtn.classList.replace('border-gray-800', 'border-orange-500');
                selectedBtn.classList.add('shadow-lg', 'shadow-orange-600/20');

                validateCheckout();
            }

            function validateCheckout() {
                const finishBtn = document.getElementById('finishBtn');

                if (cart.length > 0 && paymentMethod) {
                    finishBtn.disabled = false;
                    finishBtn.classList.remove('bg-gray-800', 'text-gray-500', 'cursor-not-allowed');
                    finishBtn.classList.add('bg-green-600', 'text-white', 'cursor-pointer', 'shadow-lg', 'shadow-green-600/30',
                        'hover:bg-green-500', 'active:scale-95');
                } else {
                    finishBtn.disabled = true;
                    finishBtn.classList.remove('bg-green-600', 'text-white', 'cursor-pointer', 'shadow-lg',
                        'shadow-green-600/30', 'hover:bg-green-500', 'active:scale-95');
                    finishBtn.classList.add('bg-gray-800', 'text-gray-500', 'cursor-not-allowed');
                }
            }

            // 🔥 FINALIZAR VENDA (AJAX)
            async function checkout() {
                if (!paymentMethod || cart.length === 0) return;

                const total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);

                const payload = {
                    items: cart,
                    payment_method: paymentMethod,
                    total_value: total
                };

                const btn = document.getElementById('finishBtn');
                btn.disabled = true;
                btn.innerText = 'PROCESSANDO...';

                try {
                    const response = await fetch('{{ route('bar.pos.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(payload)
                    });

                    const data = await response.json();
                    if (data.success) {
                        alert('✅ VENDA FINALIZADA!');
                        location.reload();
                    } else {
                        alert('❌ ERRO: ' + data.message);
                        btn.disabled = false;
                        btn.innerText = 'Finalizar Venda';
                        validateCheckout();
                    }
                } catch (e) {
                    alert('Erro na conexão com o servidor.');
                    btn.disabled = false;
                    btn.innerText = 'Finalizar Venda';
                    validateCheckout();
                }
            }
        </script>

        <style>
            .no-scrollbar::-webkit-scrollbar {
                display: none;
            }

            .no-scrollbar {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(20px);
                }

                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            .animate-slide-in {
                animation: slideIn 0.2s ease-out forwards;
            }
        </style>
</x-bar-layout>
