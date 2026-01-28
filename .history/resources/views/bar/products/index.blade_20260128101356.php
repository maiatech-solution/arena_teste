<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-white uppercase tracking-tighter">📦 Gestão de <span class="text-orange-500">Estoque</span></h2>
                <p class="text-gray-500 text-sm">Controle de inventário e preços.</p>
            </div>
            <a href="{{ route('bar.products.create') }}" class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-500 text-white font-bold rounded-xl transition shadow-lg shadow-orange-600/20">
                + NOVO PRODUTO
            </a>
        </div>

        <div class="bg-gray-900 p-4 rounded-2xl mb-6 border border-gray-800">
            <form action="{{ route('bar.products.index') }}" method="GET" class="flex gap-4">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="🔍 Buscar por nome ou bipar código..."
                       class="flex-1 bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500"
                       autofocus>
                <button type="submit" class="px-6 py-2 bg-gray-800 text-white font-bold rounded-xl hover:bg-gray-700">FILTRAR</button>
            </form>
        </div>

        <div class="bg-gray-900 rounded-3xl border border-gray-800 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-800/50">
                        <th class="p-4 text-xs font-bold text-gray-400 uppercase">Cód. Barras</th>
                        <th class="p-4 text-xs font-bold text-gray-400 uppercase">Produto</th>
                        <th class="p-4 text-xs font-bold text-gray-400 uppercase text-center">Estoque</th>
                        <th class="p-4 text-xs font-bold text-gray-400 uppercase">Preço Venda</th>
                        <th class="p-4 text-xs font-bold text-gray-400 uppercase text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-800/30 transition">
                        <td class="p-4 font-mono text-xs text-gray-500">{{ $product->barcode ?? '---' }}</td>
                        <td class="p-4 font-bold text-white">{{ $product->name }}</td>
                        <td class="p-4 text-center">
                            <span class="px-3 py-1 rounded-full text-xs font-bold {{ $product->stock_quantity <= $product->min_stock ? 'bg-red-900/50 text-red-500 border border-red-500/30' : 'bg-green-900/50 text-green-500' }}">
                                {{ $product->stock_quantity }} unid.
                            </span>
                        </td>
                        <td class="p-4 font-bold text-orange-500">R$ {{ number_format($product->sale_price, 2, ',', '.') }}</td>
                        <td class="p-4 text-right">
                            <button class="text-gray-400 hover:text-white p-2">⚙️</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center text-gray-500">Nenhum produto cadastrado ou encontrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    </div>
</x-bar-layout>
