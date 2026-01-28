<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-white uppercase tracking-tighter">📦 Gestão de <span
                        class="text-orange-500">Estoque</span></h2>
                <p class="text-gray-500 text-sm">Controle de inventário e preços.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('bar.products.history') }}"
                    class="inline-flex items-center px-6 py-3 bg-gray-800 hover:bg-gray-700 text-white font-bold rounded-xl transition border border-gray-700 shadow-lg group">
                    <span class="mr-2 group-hover:rotate-12 transition-transform duration-200">📜</span>
                    HISTÓRICO
                </a>

                <a href="{{ route('bar.products.stock_entry') }}"
                    class="inline-flex items-center px-6 py-3 bg-gray-800 hover:bg-gray-700 text-white font-bold rounded-xl transition border border-gray-700 shadow-lg group">
                    <span class="mr-2 group-hover:scale-125 transition-transform duration-200">🚛</span>
                    ENTRADA DE PRODUTOS
                </a>

                <a href="{{ route('bar.products.create') }}"
                    class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-500 text-white font-bold rounded-xl transition shadow-lg shadow-orange-600/20">
                    + NOVO PRODUTO
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div
                class="bg-gray-900 border {{ $lowStockProducts->count() > 0 ? 'border-red-500/50 ring-1 ring-red-500/20' : 'border-gray-800' }} p-6 rounded-3xl shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <span
                        class="p-3 rounded-2xl {{ $lowStockProducts->count() > 0 ? 'bg-red-500/20 text-red-500 animate-pulse' : 'bg-gray-800 text-gray-400' }}">🚨</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Status Crítico</span>
                </div>
                <h3 class="text-2xl font-black text-white">{{ $lowStockProducts->count() }}</h3>
                <p class="text-gray-500 text-xs font-bold uppercase">Itens para reposição</p>
            </div>

            <div class="bg-gray-900 border border-gray-800 p-6 rounded-3xl shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <span class="p-3 rounded-2xl bg-green-500/20 text-green-500">💰</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Patrimônio</span>
                </div>
                <h3 class="text-2xl font-black text-white">
                    R$
                    {{ number_format($products->sum(fn($p) => $p->stock_quantity * $p->purchase_price), 2, ',', '.') }}
                </h3>
                <p class="text-gray-500 text-xs font-bold uppercase">Valor total em estoque</p>
            </div>

            <div class="bg-gray-900 border border-gray-800 p-6 rounded-3xl shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <span class="p-3 rounded-2xl bg-blue-500/20 text-blue-500">📦</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Catálogo</span>
                </div>
                <h3 class="text-2xl font-black text-white">{{ $products->total() }}</h3>
                <p class="text-gray-500 text-xs font-bold uppercase">Produtos cadastrados</p>
            </div>
        </div>

        @if (session('success'))
            <div
                class="mb-6 p-4 bg-green-900/50 border border-green-500 text-green-200 rounded-xl font-bold flex items-center gap-3">
                <span class="text-xl">✅</span>
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-gray-900 p-4 rounded-2xl mb-6 border border-gray-800 shadow-lg">
            <form action="{{ route('bar.products.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="🔍 Buscar por nome ou bipar código..."
                    class="flex-1 bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3"
                    autofocus>
                <button type="submit"
                    class="px-8 py-3 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-xl transition uppercase text-xs">
                    FILTRAR
                </button>
            </form>
        </div>

        <div class="bg-gray-900 rounded-3xl border border-gray-800 overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-800/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="p-4">Status</th>
                        <th class="p-4">Cód. Barras</th>
                        <th class="p-4">Produto</th>
                        <th class="p-4 text-center">Estoque</th>
                        <th class="p-4 text-center">Preço Venda</th>
                        <th class="p-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-800/30 transition {{ !$product->is_active ? 'opacity-50' : '' }}">
                            <td class="p-4 text-center">
                                <span
                                    class="w-2 h-2 rounded-full {{ $product->is_active ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.5)]' : 'bg-red-500' }} inline-block"></span>
                            </td>
                            <td class="p-4 font-mono text-xs text-gray-500">{{ $product->barcode ?? '---' }}</td>
                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span
                                        class="font-bold text-white uppercase tracking-tight">{{ $product->name }}</span>
                                    <span
                                        class="text-[10px] text-gray-500 uppercase font-black">{{ $product->category->name ?? 'Sem Categoria' }}</span>
                                </div>
                            </td>
                            <td class="p-4 text-center">
                                <span
                                    class="px-3 py-1 rounded-full text-xs font-black uppercase {{ $product->stock_quantity <= $product->min_stock ? 'bg-red-900/50 text-red-500 border border-red-500/30 animate-pulse' : 'bg-green-900/50 text-green-500' }}">
                                    {{ $product->stock_quantity }} UNID.
                                </span>
                            </td>
                            <td class="p-4 text-center font-bold text-orange-500">R$
                                {{ number_format($product->sale_price, 2, ',', '.') }}</td>
                            <td class="p-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" onclick="openLossModal({{ $product->id }}, '{{ $product->name }}')"
                                        class="p-2 bg-gray-800 hover:bg-red-600/40 text-red-500 hover:text-white rounded-lg transition-all border border-red-500/20"
                                        title="Registrar Perda">
                                        ⚠️
                                    </button>

                                    <a href="{{ route('bar.products.edit', $product->id) }}"
                                        class="p-2 bg-gray-800 hover:bg-orange-600 text-white rounded-lg transition-all active:scale-90"
                                        title="Editar Produto">
                                        ⚙️
                                    </a>

                                    <form action="{{ route('bar.products.destroy', $product->id) }}" method="POST"
                                        onsubmit="return confirm('Deseja realmente desativar este produto?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="p-2 bg-gray-800 hover:bg-red-600 text-white rounded-lg transition-all active:scale-90"
                                            title="Desativar">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-gray-500 font-medium italic uppercase text-xs">Nenhum produto cadastrado ou encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>

    <div id="lossModal" class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-gray-900 border border-gray-800 p-8 rounded-3xl w-full max-w-md shadow-2xl">
            <div class="mb-6">
                <h3 class="text-white font-black uppercase text-lg leading-tight tracking-tighter">Registrar Perda</h3>
                <p id="loss_product_name" class="text-orange-500 font-bold text-xs uppercase mt-1"></p>
            </div>

            <form action="{{ route('bar.products.record_loss') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" id="loss_product_id">

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 tracking-widest">Qtd. Perdida</label>
                        <input type="number" name="quantity" required min="1"
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white p-3 focus:border-red-500 outline-none transition-colors"
                            placeholder="Ex: 1">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-2 tracking-widest">Motivo da Baixa</label>
                        <select name="description" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white p-3 focus:border-red-500 outline-none transition-colors appearance-none">
                            <option value="">Selecione o motivo...</option>
                            <option value="Quebra/Dano">🍾 Quebra ou Dano</option>
                            <option value="Vencimento">📅 Produto Vencido</option>
                            <option value="Consumo da Casa">🏠 Consumo da Casa</option>
                            <option value="Erro de Inventário">📉 Erro de Inventário</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-3 mt-8">
                    <button type="button" onclick="closeLossModal()"
                        class="flex-1 py-3 text-gray-500 font-bold text-xs uppercase hover:text-white transition-colors">Cancelar</button>
                    <button type="submit"
                        class="flex-1 py-4 bg-red-600 text-white rounded-2xl font-black text-xs uppercase shadow-lg shadow-red-600/20 active:scale-95 transition-all">Confirmar Baixa</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openLossModal(id, name) {
            document.getElementById('loss_product_id').value = id;
            document.getElementById('loss_product_name').innerText = name;
            document.getElementById('lossModal').classList.remove('hidden');
        }

        function closeLossModal() {
            document.getElementById('lossModal').classList.add('hidden');
        }
    </script>
</x-bar-layout>
