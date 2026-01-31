<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- CABEÇALHO --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('bar.dashboard') }}"
                    class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg group"
                    title="Voltar ao Início">
                    <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block">◀</span>
                </a>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tighter">📦 Gestão de <span
                            class="text-orange-500">Estoque</span></h2>
                    <p class="text-gray-500 text-sm italic font-medium">Controle de inventário, preços e auditoria.</p>
                </div>
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
                    ENTRADA
                </a>

                <a href="{{ route('bar.products.create') }}"
                    class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-500 text-white font-bold rounded-xl transition shadow-lg shadow-orange-600/20 active:scale-95">
                    + NOVO PRODUTO
                </a>
            </div>
        </div>

        {{-- CARDS DE RESUMO ATUALIZADOS --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

            {{-- NOVO: CARD DE REPOSIÇÃO URGENTE (NEGATIVOS) --}}
            <a href="{{ route('bar.products.index', ['filter_status' => 'out_of_stock']) }}"
                class="bg-gray-900 border {{ $outOfStockCount > 0 ? 'border-red-500 ring-1 ring-red-500/50 shadow-[0_0_20px_rgba(239,68,68,0.15)]' : 'border-gray-800' }} p-6 rounded-3xl transition-all hover:scale-[1.02] group">
                <div class="flex items-center justify-between mb-4">
                    <span class="p-3 rounded-2xl {{ $outOfStockCount > 0 ? 'bg-red-500 text-white animate-pulse' : 'bg-gray-800 text-gray-500' }}">🛒</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Reposição Urgente</span>
                </div>
                <h3 class="text-3xl font-black text-white">{{ $outOfStockCount }}</h3>
                <p class="text-gray-500 text-[10px] font-bold uppercase mt-1">Itens com saldo negativo</p>
            </a>

            {{-- Status Crítico (Abaixo do Mínimo) --}}
            <a href="{{ route('bar.products.index', ['filter_status' => 'low_stock']) }}"
                class="bg-gray-900 border {{ $lowStockProducts->count() > 0 ? 'border-orange-500/50 shadow-[0_0_20px_rgba(249,115,22,0.1)]' : 'border-gray-800' }} p-6 rounded-3xl transition-all hover:scale-[1.02]">
                <div class="flex items-center justify-between mb-4">
                    <span class="p-3 rounded-2xl {{ $lowStockProducts->count() > 0 ? 'bg-orange-500/20 text-orange-500' : 'bg-gray-800 text-gray-400' }}">🚨</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Abaixo do Mínimo</span>
                </div>
                <h3 class="text-3xl font-black text-white">{{ $lowStockProducts->count() }}</h3>
                <p class="text-gray-500 text-[10px] font-bold uppercase mt-1">Exige compra preventiva</p>
            </a>

            {{-- Patrimônio --}}
            <div class="bg-gray-900 border border-gray-800 p-6 rounded-3xl shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <span class="p-3 rounded-2xl bg-green-500/20 text-green-500 text-xl">💰</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Patrimônio</span>
                </div>
                <h3 class="text-3xl font-black text-white italic">R$ {{ number_format($totalPatrimonio, 2, ',', '.') }}</h3>
                <p class="text-gray-500 text-[10px] font-bold uppercase mt-1 tracking-tighter">Custo total em estoque</p>
            </div>

            {{-- Mix de Produtos --}}
            <div class="bg-gray-900 border border-gray-800 p-6 rounded-3xl shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <span class="p-3 rounded-2xl bg-blue-500/20 text-blue-500">📦</span>
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Mix Ativo</span>
                </div>
                <h3 class="text-3xl font-black text-white">{{ $products->total() }}</h3>
                <p class="text-gray-500 text-[10px] font-bold uppercase mt-1 tracking-tighter">Itens no catálogo</p>
            </div>
        </div>

        @if (session('success'))
        <div class="mb-6 p-4 bg-green-900/50 border border-green-500 text-green-200 rounded-xl font-bold flex items-center gap-3 animate-bounce">
            <span class="text-xl">✅</span>
            {{ session('success') }}
        </div>
        @endif

        {{-- FILTROS ATUALIZADOS --}}
        <div class="bg-gray-900 p-6 rounded-3xl mb-8 border border-gray-800 shadow-lg backdrop-blur-sm bg-opacity-80">
            <form action="{{ route('bar.products.index') }}" method="GET" class="space-y-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="🔍 Nome do produto ou Código de barras..."
                        class="flex-1 bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-4 font-bold placeholder:text-gray-700"
                        autofocus>

                    <select name="bar_category_id" onchange="this.form.submit()"
                        class="bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-4 font-bold min-w-[200px] cursor-pointer">
                        <option value="">📂 Todas Categorias</option>
                        @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('bar_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <button type="submit"
                        class="px-10 py-4 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-xl transition-all shadow-lg shadow-orange-600/20 uppercase text-xs tracking-widest active:scale-95">
                        BUSCAR
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-3 pt-4 border-t border-gray-800">
                    <span class="text-[9px] font-black text-gray-600 uppercase mr-2 tracking-[0.2em]">Exibir apenas:</span>

                    @php
                    $currentStatus = request('filter_status', 'all');
                    $btnBase = 'px-5 py-2.5 rounded-xl text-[10px] font-black uppercase transition-all border ';
                    $queryParams = request()->except(['filter_status', 'page']);
                    @endphp

                    <a href="{{ route('bar.products.index', array_merge($queryParams, ['filter_status' => 'all'])) }}"
                        class="{{ $btnBase }} {{ $currentStatus == 'all' ? 'bg-orange-600 text-white border-orange-500 shadow-lg' : 'bg-gray-800 text-gray-500 border-gray-700 hover:bg-gray-700' }}">
                        Todos
                    </a>

                    <a href="{{ route('bar.products.index', array_merge($queryParams, ['filter_status' => 'out_of_stock'])) }}"
                        class="{{ $btnBase }} {{ $currentStatus == 'out_of_stock' ? 'bg-red-600 text-white border-red-500 shadow-lg animate-pulse' : 'bg-gray-800 text-gray-500 border-gray-700 hover:bg-gray-700' }}">
                        🛒 Negativos
                    </a>

                    <a href="{{ route('bar.products.index', array_merge($queryParams, ['filter_status' => 'low_stock'])) }}"
                        class="{{ $btnBase }} {{ $currentStatus == 'low_stock' ? 'bg-yellow-600 text-white border-yellow-500' : 'bg-gray-800 text-gray-500 border-gray-700 hover:bg-gray-700' }}">
                        ⚠️ Baixo
                    </a>

                    <a href="{{ route('bar.products.index', array_merge($queryParams, ['filter_status' => 'active'])) }}"
                        class="{{ $btnBase }} {{ $currentStatus == 'active' ? 'bg-green-600 text-white border-green-500 shadow-lg' : 'bg-gray-800 text-gray-500 border-gray-700 hover:bg-gray-700' }}">
                        Ativos
                    </a>

                    @if (request()->anyFilled(['search', 'bar_category_id', 'filter_status']))
                    <a href="{{ route('bar.products.index') }}"
                        class="ml-auto text-[10px] font-black text-orange-500 hover:text-white transition-colors uppercase tracking-widest border-b border-orange-500">
                        Resetar Filtros
                    </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- TABELA ATUALIZADA --}}
        <div class="bg-gray-900 rounded-[2rem] border border-gray-800 overflow-hidden shadow-2xl relative">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-800/50 text-[10px] font-black text-gray-500 uppercase tracking-[0.2em]">
                        <th class="p-6 text-center">Status</th>
                        <th class="p-6">Identificação</th>
                        <th class="p-6 text-center">Gestão</th>
                        <th class="p-6 text-center">Estoque Atual</th>
                        <th class="p-6 text-center">Preço Venda</th>
                        <th class="p-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800/50">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-800/30 transition-all duration-300 {{ !$product->is_active ? 'opacity-40 grayscale' : '' }}">
                        <td class="p-6 text-center">
                            <span class="w-2.5 h-2.5 rounded-full {{ $product->is_active ? 'bg-green-500 shadow-[0_0_12px_rgba(34,197,94,0.4)]' : 'bg-red-500 shadow-[0_0_12px_rgba(239,68,68,0.4)]' }} inline-block"></span>
                        </td>

                        <td class="p-6">
                            <div class="flex flex-col">
                                <span class="font-black text-white uppercase tracking-tight text-sm leading-tight">{{ $product->name }}</span>
                                <span class="text-[9px] text-gray-500 uppercase font-black tracking-widest mt-1">
                                    🏷️ {{ $product->barcode ?? 'Sem Código' }} | {{ $product->category->name ?? 'Geral' }}
                                </span>
                            </div>
                        </td>

                        <td class="p-6 text-center">
                            @if($product->manage_stock)
                            <span class="text-[8px] bg-blue-900/30 text-blue-400 px-3 py-1 rounded-lg border border-blue-500/20 font-black uppercase italic tracking-tighter">Controlado</span>
                            @else
                            <span class="text-[8px] bg-gray-800 text-gray-500 px-3 py-1 rounded-lg border border-gray-700 font-black uppercase italic tracking-tighter">Livre (Negativo)</span>
                            @endif
                        </td>

                        <td class="p-6 text-center">
                            {{-- Lógica de cores para estoque --}}
                            @php
                            $stockClass = 'bg-gray-950 text-gray-400 border-gray-800';
                            if($product->stock_quantity < 0) {
                                $stockClass='bg-red-900/40 text-red-500 border-red-500/50 font-black animate-pulse' ;
                                } elseif($product->manage_stock && $product->stock_quantity <= $product->min_stock) {
                                    $stockClass = 'bg-yellow-900/30 text-yellow-500 border-yellow-500/30';
                                    } elseif($product->stock_quantity > 0) {
                                    $stockClass = 'bg-gray-950 text-green-500 border-green-500/20';
                                    }
                                    @endphp
                                    <span class="px-4 py-1.5 rounded-xl text-[11px] font-black uppercase border {{ $stockClass }}">
                                        {{ $product->stock_quantity }} UNID.
                                    </span>
                        </td>

                        <td class="p-6 text-center font-black text-orange-500 italic text-sm">
                            R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                        </td>

                        <td class="p-6 text-right">
                            <div class="flex justify-end gap-3">
                                {{-- 1. REGISTRAR PERDA: Todos vêem, mas o Modal tem a trava de senha (já fizemos) --}}
                                @if($product->manage_stock)
                                <button type="button"
                                    onclick="openLossModal({{ $product->id }}, '{{ $product->name }}')"
                                    class="w-10 h-10 flex items-center justify-center bg-gray-800 hover:bg-red-900 text-red-500 rounded-xl transition-all border border-red-500/10 {{ !$product->is_active ? 'opacity-20 cursor-not-allowed' : '' }}"
                                    title="Registrar Perda" {{ !$product->is_active ? 'disabled' : '' }}>
                                    ⚠️
                                </button>
                                @endif

                                {{-- 2. EDITAR FICHA: Somente Gestores e Admins podem alterar dados de produtos existentes --}}
                                @if(in_array(auth()->user()->role, ['admin', 'gestor']))
                                <a href="{{ route('bar.products.edit', $product->id) }}"
                                    class="w-10 h-10 flex items-center justify-center bg-gray-800 hover:bg-orange-600 text-white rounded-xl transition-all active:scale-90 border border-gray-700"
                                    title="Editar Ficha">
                                    ⚙️
                                </a>
                                @else
                                {{-- Cadeado visual para o Colaborador saber que não tem acesso --}}
                                <div class="w-10 h-10 flex items-center justify-center bg-gray-900/50 text-gray-700 rounded-xl border border-gray-800 cursor-not-allowed"
                                    title="Edição restrita a supervisores">
                                    🔒
                                </div>
                                @endif

                                {{-- 3. MUDAR STATUS: Todos vêem o botão, mas o SCRIPT pedirá autorização (ajustaremos a seguir) --}}
                                <form id="form-status-{{ $product->id }}" action="{{ route('bar.products.destroy', $product->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="status_reason" id="reason-{{ $product->id }}">
                                    <button type="button"
                                        onclick="confirmStatusChange({{ $product->id }}, {{ $product->is_active ? 'true' : 'false' }})"
                                        class="w-10 h-10 flex items-center justify-center rounded-xl transition-all active:scale-90 border {{ $product->is_active ? 'bg-gray-800 text-red-500 border-red-500/20 hover:bg-red-900/30' : 'bg-green-600 text-white border-green-500/20 hover:bg-green-500' }}">
                                        {!! $product->is_active ? '🗑️' : '✅' !!}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-24 text-center">
                            <div class="flex flex-col items-center opacity-20">
                                <span class="text-6xl mb-4">🔎</span>
                                <p class="font-black uppercase text-xs tracking-[0.3em] text-gray-500">Nenhum item localizado</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-10 mb-20">
            {{ $products->links() }}
        </div>
    </div>

    {{-- MODAL DE PERDA --}}
    <div id="lossModal" class="hidden fixed inset-0 bg-black/95 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
        <div class="bg-gray-900 border border-gray-800 p-10 rounded-[2.5rem] w-full max-w-md shadow-[0_0_50px_rgba(0,0,0,0.5)]">
            <div class="mb-8 text-center">
                <div class="w-16 h-16 bg-red-900/30 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl border border-red-500/20">⚠️</div>
                <h3 class="text-white font-black uppercase text-xl tracking-tighter italic">Registrar Perda</h3>
                <p id="loss_product_name" class="text-orange-500 font-bold text-xs uppercase mt-2 tracking-widest"></p>
            </div>

            <form id="formPerdaEstoque" action="{{ route('bar.products.record_loss') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" id="loss_product_id">
                <div class="space-y-6">
                    <div class="group">
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-3 tracking-widest ml-1">Quantidade Perdida (UN)</label>
                        <input type="number" name="quantity" required min="1" class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 focus:ring-2 focus:ring-red-500 outline-none font-black text-xl shadow-inner text-center">
                    </div>
                    <div class="group">
                        <label class="block text-[10px] font-black text-gray-600 uppercase mb-3 tracking-widest ml-1">Motivo Detalhado</label>
                        <input type="text" name="description" required class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 focus:ring-2 focus:ring-red-500 outline-none font-bold placeholder:text-gray-800 shadow-inner" placeholder="Ex: Quebra, Vencimento...">
                    </div>
                </div>
                <div class="flex gap-4 mt-10">
                    <button type="button" onclick="closeLossModal()" class="flex-1 py-4 text-gray-500 font-black text-[10px] uppercase tracking-widest hover:text-white transition-colors">Cancelar</button>

                    {{-- Alterado: type="button" e adicionado o onclick com a trava --}}
                    <button type="button"
                        onclick="requisitarAutorizacao(() => document.getElementById('formPerdaEstoque').submit())"
                        class="flex-1 py-4 bg-red-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-red-600/30 active:scale-95 transition-all">
                        Confirmar Baixa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        /**
         * 🛡️ Gerencia a alteração de status (Ativar/Desativar) 
         * Agora exige motivo E autorização do supervisor.
         */
        function confirmStatusChange(id, isActive) {
            const action = isActive ? 'DESATIVAR' : 'REATIVAR';

            // 1. Solicita o motivo para o registro de auditoria
            const reason = prompt(`Deseja realmente ${action} este produto?\n\nInforme o motivo para auditoria:`);

            if (reason !== null && reason.trim() !== "") {

                // 2. 🔒 Intercepta a ação para exigir senha de Gestor/Admin
                requisitarAutorizacao(() => {
                    document.getElementById('reason-' + id).value = reason;
                    document.getElementById('form-status-' + id).submit();
                });

            } else if (reason !== null) {
                alert("O motivo é obrigatório para realizar esta alteração.");
            }
        }

        /**
         * Abre o modal de registro de perda/quebra
         */
        function openLossModal(id, name) {
            document.getElementById('loss_product_id').value = id;
            document.getElementById('loss_product_name').innerText = name;
            document.getElementById('lossModal').classList.remove('hidden');

            // Foca automaticamente no campo de quantidade
            setTimeout(() => {
                const input = document.querySelector('#lossModal input[name="quantity"]');
                if (input) input.focus();
            }, 100);
        }

        /**
         * Fecha o modal de perda
         */
        function closeLossModal() {
            document.getElementById('lossModal').classList.add('hidden');
        }

        /**
         * Atalho para fechar modal com a tecla ESC
         */
        document.addEventListener('keydown', (e) => {
            if (e.key === "Escape") closeLossModal();
        });
    </script>

    <style>
        nav[role="navigation"] svg {
            width: 1.2rem;
            display: inline;
        }

        .relative.z-0.inline-flex {
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid #1f2937;
        }
    </style>
</x-bar-layout>