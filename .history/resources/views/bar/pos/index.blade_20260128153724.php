<x-bar-layout>
    <div class="h-[calc(100vh-120px)] flex flex-col md:flex-row gap-4 overflow-hidden">

        <div
            class="flex-1 flex flex-col min-h-0 bg-gray-900 rounded-3xl border border-gray-800 shadow-2xl overflow-hidden">
            <div class="p-6 border-b border-gray-800 flex justify-between items-center bg-gray-900/50">
                <div class="flex items-center gap-4">
                    <a href="{{ route('bar.dashboard') }}" class="text-gray-500 hover:text-white transition-colors">◀</a>
                    <h2 class="text-xl font-black text-white uppercase tracking-tighter">🛒 Venda <span
                            class="text-orange-500">Rápida</span></h2>
                </div>
                <div class="relative w-64">
                    <input type="text" id="productSearch" onkeyup="filterProducts()" placeholder="Buscar produto..."
                        class="w-full bg-gray-950 border-gray-800 rounded-xl text-white text-sm p-2 focus:border-orange-500 outline-none">
                </div>
            </div>

            <div class="px-6 py-3 flex gap-2 overflow-x-auto no-scrollbar border-b border-gray-800/50">
                <button onclick="filterCategory('all')"
                    class="cat-btn active px-4 py-2 rounded-lg text-[10px] font-black uppercase transition-all bg-orange-600 text-white border border-orange-500">Todos</button>
                @foreach ($categories as $cat)
                    <button onclick="filterCategory('{{ $cat->id }}')"
                        class="cat-btn px-4 py-2 rounded-lg text-[10px] font-black uppercase transition-all bg-gray-800 text-gray-400 border border-gray-700 hover:bg-gray-700">
                        {{ $cat->name }}
                    </button>
                @endforeach
            </div>

            <div class="flex-1 overflow-y-auto p-6 grid grid-cols-2 lg:grid-cols-4 gap-4" id="productsGrid">
                @foreach ($products as $product)
                    <button
                        onclick="addToCart({{ $product->id }}, '{{ $product->name }}', {{ $product->sale_price }}, {{ $product->stock_quantity }})"
                        data-category="{{ $product->bar_category_id }}" data-name="{{ strtolower($product->name) }}"
                        class="product-card p-4 bg-gray-800 hover:bg-gray-750 rounded-2xl border border-gray-700 hover:border-orange-500/50 transition-all flex flex-col items-center text-center group active:scale-95">
                        <span class="text-3xl mb-2 group-hover:scale-110 transition-transform">📦</span>
                        <span
                            class="text-xs font-bold text-white uppercase leading-tight h-8 overflow-hidden mb-2">{{ $product->name }}</span>
                        <span class="text-orange-500 font-black">R$
                            {{ number_format($product->sale_price, 2, ',', '.') }}</span>
                        <span
                            class="mt-2 text-[9px] {{ $product->stock_quantity <= $product->min_stock ? 'text-red-500 animate-pulse' : 'text-gray-500' }} uppercase font-black">Estoque:
                            {{ $product->stock_quantity }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div
            class="w-full md:w-96 flex flex-col bg-gray-900 rounded-3xl border border-gray-800 shadow-2xl overflow-hidden">
            <div class="p-6 border-b border-gray-800">
                <h3 class="text-lg font-black text-white uppercase flex items-center gap-2 italic">🧾 Cupom <span
                        class="text-orange-500 text-sm italic">Digital</span></h3>
            </div>

            <div class="flex-1 overflow-y-auto p-6 space-y-4" id="cartItems">
                <div id="emptyCart" class="text-center py-10">
                    <span class="text-4xl block mb-4">🛒</span>
                    <p class="text-gray-500 text-xs font-black uppercase italic">Selecione produtos para iniciar</p>
                </div>
            </div>

            <div class="p-6 bg-gray-950 border-t border-gray-800">
                <div class="flex justify-between items-end mb-6">
                    <span class="text-gray-500 text-[10px] font-black uppercase">Total a Pagar</span>
                    <span class="text-3xl font-black text-white" id="cartTotalText">R$ 0,00</span>
                </div>

                <div class="grid grid-cols-3 gap-2 mb-6">
                    <button onclick="setPaymentMethod('dinheiro')"
                        class="pay-btn flex flex-col items-center p-3 rounded-xl border border-gray-800 bg-gray-900 text-gray-500 transition-all"
                        id="btn-dinheiro">
                        <span class="text-xl">💵</span>
                        <span class="text-[8px] font-black uppercase mt-1">Dinheiro</span>
                    </button>
                    <button onclick="setPaymentMethod('pix')"
                        class="pay-btn flex flex-col items-center p-3 rounded-xl border border-gray-800 bg-gray-900 text-gray-500 transition-all"
                        id="btn-pix">
                        <span class="text-xl">📱</span>
                        <span class="text-[8px] font-black uppercase mt-1">PIX</span>
                    </button>
                    <button onclick="setPaymentMethod('cartao')"
                        class="pay-btn flex flex-col items-center p-3 rounded-xl border border-gray-800 bg-gray-900 text-gray-500 transition-all"
                        id="btn-cartao">
                        <span class="text-xl">💳</span>
                        <span class="text-[8px] font-black uppercase mt-1">Cartão</span>
                    </button>
                </div>

                <button onclick="checkout()" id="finishBtn" disabled
                    class="w-full py-4 bg-gray-800 text-gray-500 font-black rounded-2xl uppercase tracking-widest transition-all cursor-not-allowed">
                    Finalizar Venda
                </button>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let paymentMethod = null;

        function addToCart(id, name, price, stock) {
            const index = cart.findIndex(item => item.id === id);
            if (index > -1) {
                if (cart[index].quantity < stock) {
                    cart[index].quantity++;
                } else {
                    alert("Estoque insuficiente!");
                    return;
                }
            } else {
                cart.push({
                    id,
                    name,
                    price,
                    quantity: 1,
                    stock
                });
            }
            updateCart();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCart();
        }

        function updateCart() {
            const container = document.getElementById('cartItems');
            const emptyCart = document.getElementById('emptyCart');
            const totalText = document.getElementById('cartTotalText');
            const finishBtn = document.getElementById('finishBtn');

            if (cart.length === 0) {
                container.innerHTML = '';
                container.appendChild(emptyCart);
                totalText.innerText = 'R$ 0,00';
                finishBtn.disabled = true;
                finishBtn.classList.replace('bg-green-600', 'bg-gray-800');
                finishBtn.classList.replace('text-white', 'text-gray-500');
                return;
            }

            emptyCart.remove();
            container.innerHTML = cart.map((item, index) => `
                <div class="flex items-center justify-between bg-gray-800 p-3 rounded-xl border border-gray-700 animate-slide-in">
                    <div class="flex flex-col">
                        <span class="text-white text-[10px] font-black uppercase">${item.name}</span>
                        <span class="text-orange-500 text-xs font-bold">R$ ${(item.price * item.quantity).toFixed(2).replace('.', ',')}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 text-[10px] font-black">x${item.quantity}</span>
                        <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-400">✕</button>
                    </div>
                </div>
            `).join('');

            const total = cart.reduce((acc, item) => acc + (item.price * item.quantity), 0);
            totalText.innerText = `R$ ${total.toFixed(2).replace('.', ',')}`;

            if (paymentMethod) validateCheckout();
        }

        function setPaymentMethod(method) {
            paymentMethod = method;
            document.querySelectorAll('.pay-btn').forEach(btn => {
                btn.classList.remove('bg-orange-600', 'text-white', 'border-orange-500');
                btn.classList.add('bg-gray-900', 'text-gray-500', 'border-gray-800');
            });
            document.getElementById('btn-' + method).classList.replace('bg-gray-900', 'bg-orange-600');
            document.getElementById('btn-' + method).classList.replace('text-gray-500', 'text-white');
            document.getElementById('btn-' + method).classList.replace('border-gray-800', 'border-orange-500');

            validateCheckout();
        }

        function validateCheckout() {
            const finishBtn = document.getElementById('finishBtn');
            if (cart.length > 0 && paymentMethod) {
                finishBtn.disabled = false;
                finishBtn.classList.replace('bg-gray-800', 'bg-green-600');
                finishBtn.classList.replace('text-gray-500', 'text-white');
                finishBtn.classList.add('shadow-lg', 'shadow-green-600/20');
            }
        }

        function filterCategory(catId) {
            document.querySelectorAll('.cat-btn').forEach(btn => btn.classList.remove('bg-orange-600', 'text-white'));
            event.target.classList.add('bg-orange-600', 'text-white');

            const products = document.querySelectorAll('.product-card');
            products.forEach(p => {
                if (catId === 'all' || p.dataset.category === catId) {
                    p.style.display = 'flex';
                } else {
                    p.style.display = 'none';
                }
            });
        }

        function filterProducts() {
            const query = document.getElementById('productSearch').value.toLowerCase();
            document.querySelectorAll('.product-card').forEach(p => {
                const name = p.dataset.name;
                p.style.display = name.includes(query) ? 'flex' : 'none';
            });
        }

        async function checkout() {
            if (!paymentMethod || cart.length === 0) {
                alert("Selecione os produtos e o método de pagamento.");
                return;
            }

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
                        // 🚀 O SEGREDO ESTÁ AQUI:
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    alert('🔥 VENDA FINALIZADA COM SUCESSO!');
                    location.reload();
                } else {
                    // Se o controller retornar erro (ex: falta de estoque)
                    alert('❌ Erro: ' + (data.message || 'Falha desconhecida'));
                    btn.disabled = false;
                    btn.innerText = 'Finalizar Venda';
                }
            } catch (e) {
                console.error(e);
                alert('Erro crítico ao processar venda.');
                btn.disabled = false;
                btn.innerText = 'Finalizar Venda';
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
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.2s ease-out forwards;
        }
    </style>
</x-bar-layout>
