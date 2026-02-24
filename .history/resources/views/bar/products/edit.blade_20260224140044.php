<x-bar-layout>
    {{-- Injetamos o estado inicial do Alpine.js mantendo o layout --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{
        isCombo: {{ $product->is_combo ? 'true' : 'false' }},
        comboItems: {{ $product->compositions->map(function ($c) {
                return [
                    'id' => $c->id,
                    'child_id' => $c->child_id,
                    'name' => $c->product->name ?? 'Produto não encontrado',
                    'quantity' => $c->quantity,
                ];
            })->toJson() }}
    }">
        <div class="mb-8">
            <a href="{{ route('bar.products.index') }}"
                class="text-orange-500 hover:text-orange-400 text-sm font-bold flex items-center gap-2 mb-4 transition-colors">
                ⬅️ VOLTAR PARA ESTOQUE
            </a>
            <h2 class="text-3xl font-black text-white uppercase tracking-tighter italic">⚙️ Editar <span
                    class="text-orange-500">{{ $product->name }}</span></h2>
        </div>

        <div class="bg-gray-900 rounded-3xl border border-gray-800 p-8 shadow-2xl">

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-900/50 border border-red-500 text-red-200 rounded-xl">
                    <p class="font-bold uppercase text-xs mb-2">Ops! Verifique os campos abaixo:</p>
                    <ul class="list-disc list-inside text-xs">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('bar.products.update', ['product' => $product->id]) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Campo oculto para garantir que o tipo combo não se perca --}}
                <input type="hidden" name="is_combo" :value="isCombo ? 1 : 0">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- NOME DO PRODUTO --}}
                    <div class="md:col-span-2 group">
                        <label
                            class="block text-[10px] font-black text-gray-500 uppercase mb-2 tracking-widest ml-1 transition-colors group-focus-within:text-orange-500">Nome
                            do Produto</label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3 shadow-inner">
                    </div>

                    {{-- 📦 SEÇÃO DE COMPOSIÇÃO DO COMBO (SÓ APARECE SE FOR COMBO) --}}
                    <div class="md:col-span-2 bg-orange-600/5 border border-orange-600/20 p-6 rounded-2xl mb-4"
                        x-show="isCombo" x-transition>
                        <div class="flex items-center justify-between mb-4 border-b border-orange-600/10 pb-4">
                            <div>
                                <h3 class="text-orange-500 font-black text-xs uppercase italic tracking-widest">📋 Itens
                                    da Composição</h3>
                                <p class="text-[9px] text-gray-500 uppercase font-bold">Gerencie os itens que saem do
                                    estoque neste combo</p>
                            </div>
                            <button type="button"
                                @click="comboItems.push({id: Date.now(), child_id: '', name: '', quantity: 1})"
                                class="bg-orange-600 hover:bg-orange-500 text-white text-[10px] font-black px-4 py-2 rounded-lg transition-all active:scale-95 shadow-lg shadow-orange-600/20">
                                + ADICIONAR ITEM
                            </button>
                        </div>

                        <div class="space-y-3">
                            <template x-for="(item, index) in comboItems" :key="item.id">
                                <div
                                    class="flex gap-3 items-center animate-in fade-in slide-in-from-top-1 bg-black/20 p-3 rounded-xl border border-gray-800">
                                    <div class="flex-1">
                                        <label
                                            class="block text-[9px] font-black text-gray-500 uppercase mb-1 ml-1">Buscar
                                            Produto</label>
                                        <input type="text"
                                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white text-xs p-2.5 focus:border-orange-500 outline-none"
                                            placeholder="Digite o nome..." list="products_list_edit" x-model="item.name"
                                            @input="
                                                let selectedOption = Array.from($el.list.options).find(opt => opt.value === $el.value);
                                                item.child_id = selectedOption ? selectedOption.dataset.id : '';
                                            ">
                                        <input type="hidden" :name="`combo_items[${index}][child_id]`"
                                            x-model="item.child_id">
                                    </div>
                                    <div class="w-24">
                                        <label
                                            class="block text-[9px] font-black text-gray-500 uppercase mb-1 text-center">Qtd</label>
                                        <input type="number" :name="`combo_items[${index}][quantity]`"
                                            x-model="item.quantity" min="1" required
                                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white text-xs p-2.5 text-center focus:border-orange-500">
                                    </div>
                                    <button type="button"
                                        @click="comboItems = comboItems.filter(i => i.id !== item.id)"
                                        class="text-red-500 hover:text-white hover:bg-red-600 rounded-lg p-2 transition-all mt-4">
                                        ✕
                                    </button>
                                </div>
                            </template>

                            <datalist id="products_list_edit">
                                @foreach ($availableProducts as $avail)
                                    <option data-id="{{ $avail->id }}" value="{{ $avail->name }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    {{-- CATEGORIA --}}
                    <div class="md:col-span-1 group">
                        <div class="flex justify-between items-center mb-2">
                            <label
                                class="block text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1 group-focus-within:text-orange-500">Categoria</label>
                            <button type="button" onclick="openCategoryModal()"
                                class="text-orange-500 hover:text-orange-400 text-[10px] font-black uppercase border border-orange-500/30 px-2 py-1 rounded-md transition-all active:scale-95">
                                + NOVA CATEGORIA
                            </button>
                        </div>
                        <select name="bar_category_id" id="category_select" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                            <option value="">Selecione...</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ old('bar_category_id', $product->bar_category_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CÓDIGO DE BARRAS --}}
                    <div class="group">
                        <label
                            class="block text-[10px] font-black text-gray-500 uppercase mb-2 tracking-widest ml-1 group-focus-within:text-orange-500">Código
                            de Barras</label>
                        <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                            maxlength="13"
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3 font-mono">
                    </div>

                    {{-- 🚀 GESTÃO DE INVENTÁRIO (OCULTO SE FOR COMBO NO ALPINE) --}}
                    <div class="md:col-span-2 bg-gray-950/40 p-5 rounded-2xl border border-gray-800/50 my-2"
                        x-show="!isCombo">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="max-w-md">
                                <label class="block text-xs font-black text-orange-500 uppercase tracking-[0.2em] mb-1">
                                    ⚙️ Gestão de Inventário
                                </label>
                                <p class="text-[10px] text-gray-500 font-bold uppercase leading-tight">
                                    Deseja que o sistema controle o saldo e emita alertas de estoque baixo?
                                </p>
                            </div>
                            <div class="flex bg-gray-900 p-1 rounded-xl border border-gray-800">
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="manage_stock" value="1" class="hidden peer"
                                        {{ old('manage_stock', $product->manage_stock) == 1 ? 'checked' : '' }}>
                                    <span
                                        class="px-4 py-2 text-[10px] font-black uppercase rounded-lg text-gray-500 peer-checked:bg-orange-600 peer-checked:text-white transition-all">
                                        ✅ Sim
                                    </span>
                                </label>
                                <label class="flex items-center cursor-pointer">
                                    <input type="radio" name="manage_stock" value="0" class="hidden peer"
                                        {{ old('manage_stock', $product->manage_stock) == 0 ? 'checked' : '' }}>
                                    <span
                                        class="px-4 py-2 text-[10px] font-black uppercase rounded-lg text-gray-500 peer-checked:bg-red-600 peer-checked:text-white transition-all">
                                        🚫 Não
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- UNIDADE DE COMPRA --}}
                    <div x-show="!isCombo">
                        <label
                            class="block text-[10px] font-black text-orange-500 uppercase mb-2 tracking-widest ml-1">Unidade
                            de Compra</label>
                        <select name="unit_type" :required="!isCombo"
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                            <option value="UN"
                                {{ old('unit_type', $product->unit_type) == 'UN' ? 'selected' : '' }}>UNIDADE (UN)
                            </option>
                            <option value="FD"
                                {{ old('unit_type', $product->unit_type) == 'FD' ? 'selected' : '' }}>FARDO (FD)
                            </option>
                            <option value="CX"
                                {{ old('unit_type', $product->unit_type) == 'CX' ? 'selected' : '' }}>CAIXA (CX)
                            </option>
                            <option value="KG"
                                {{ old('unit_type', $product->unit_type) == 'KG' ? 'selected' : '' }}>QUILO (KG)
                            </option>
                        </select>
                    </div>

                    {{-- QTD NO FARDO --}}
                    <div x-show="!isCombo">
                        <label
                            class="block text-[10px] font-black text-orange-500 uppercase mb-2 tracking-widest ml-1">Qtd
                            no Fardo/Caixa</label>
                        <input type="number" name="content_quantity"
                            value="{{ old('content_quantity', $product->content_quantity ?? 1) }}" min="1"
                            :required="!isCombo"
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>

                    {{-- PREÇO DE CUSTO --}}
                    <div class="group">
                        <label
                            class="block text-[10px] font-black text-gray-500 uppercase mb-2 tracking-widest ml-1 group-focus-within:text-orange-500">
                            Preço de Custo (R$)
                        </label>
                        <input type="number" step="0.01" name="purchase_price" id="purchase_price"
                            value="{{ old('purchase_price', $product->purchase_price) }}" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3 shadow-inner">
                    </div>

                    {{-- PREÇO DE VENDA COM BOTÕES DE MARKUP --}}
                    <div class="group">
                        <div class="flex justify-between items-center mb-2">
                            <label
                                class="block text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1 group-focus-within:text-orange-500">
                                Preço de Venda (R$)
                            </label>
                            {{-- Botões de atalho para precificar rápido --}}
                            <div class="flex gap-1">
                                <button type="button" onclick="aplicarMarkup(30)"
                                    class="text-[8px] bg-gray-800 text-gray-500 px-2 py-1 rounded-md hover:bg-orange-600 hover:text-white transition-all font-black">+30%</button>
                                <button type="button" onclick="aplicarMarkup(50)"
                                    class="text-[8px] bg-gray-800 text-gray-500 px-2 py-1 rounded-md hover:bg-orange-600 hover:text-white transition-all font-black">+50%</button>
                                <button type="button" onclick="aplicarMarkup(100)"
                                    class="text-[8px] bg-gray-800 text-gray-500 px-2 py-1 rounded-md hover:bg-orange-600 hover:text-white transition-all font-black">+100%</button>
                            </div>
                        </div>
                        <input type="number" step="0.01" name="sale_price" id="sale_price"
                            value="{{ old('sale_price', $product->sale_price) }}" required
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white font-bold text-orange-500 focus:border-orange-500 focus:ring-orange-500 p-3 shadow-inner">
                    </div>

                    {{-- 📊 WIDGET ANALÍTICO DE MÃO DUPLA (Colar abaixo do Preço de Venda) --}}
                    <div class="md:col-span-2 p-5 bg-black/40 border border-gray-800 rounded-[2rem] mt-2 shadow-inner">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center gap-4">
                                {{-- Ícone de Status Dinâmico --}}
                                <div id="margem_status_bg"
                                    class="w-14 h-14 rounded-2xl bg-gray-950 border border-gray-800 flex items-center justify-center transition-all duration-500 text-2xl">
                                    <span id="margem_emoji">💰</span>
                                </div>

                                <div>
                                    <p
                                        class="text-[9px] font-black text-gray-500 uppercase tracking-[0.2em] mb-1 italic">
                                        Análise de Precificação Atual</p>
                                    <div class="flex flex-wrap items-center gap-y-1 gap-x-4">
                                        {{-- Markup --}}
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[8px] text-gray-600 uppercase font-bold leading-none">Markup
                                                (Sobre Custo)</span>
                                            <span id="display_markup"
                                                class="text-lg font-black text-orange-500 italic tracking-tighter leading-none">0%</span>
                                        </div>
                                        <span class="text-gray-800 text-xl font-thin mt-2">/</span>
                                        {{-- Margem Real --}}
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[8px] text-gray-600 uppercase font-bold leading-none">Margem
                                                (Sobre Venda)</span>
                                            <span id="display_margem"
                                                class="text-lg font-black text-white italic tracking-tighter leading-none">0%</span>
                                        </div>
                                        <span class="text-gray-800 text-xl font-thin mt-2">/</span>
                                        {{-- Lucro em Real --}}
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[8px] text-gray-600 uppercase font-bold leading-none">Lucro
                                                Líquido</span>
                                            <span id="display_lucro"
                                                class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-1 italic">R$
                                                0,00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Campo de ajuste manual por porcentagem --}}
                            <div class="w-full md:w-auto text-center md:text-right">
                                <label
                                    class="text-[8px] font-black text-gray-600 uppercase block mb-1 ml-2 tracking-widest">Ajustar
                                    Markup (%)</label>
                                <input type="number" id="input_markup" placeholder="Ex: 30"
                                    class="w-full md:w-32 bg-gray-950 border-gray-800 rounded-xl p-3 text-xs text-orange-500 font-black text-center outline-none focus:border-orange-500 transition-all shadow-inner">
                            </div>
                        </div>
                    </div>


                    {{-- ESTOQUE ATUAL (BLOQUEADO) --}}
                    <div x-show="!isCombo">
                        <label
                            class="block text-[10px] font-black text-gray-600 uppercase mb-2 tracking-widest ml-1 italic">Estoque
                            Atual (Saldo)</label>
                        <input type="number" name="stock_quantity"
                            value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" readonly
                            class="w-full bg-gray-800 border-gray-700 rounded-xl text-gray-500 p-3 cursor-not-allowed font-black shadow-inner"
                            title="O estoque só pode ser alterado via Entrada ou Registro de Perda">
                        <p class="text-[9px] text-gray-600 mt-2 uppercase font-black italic tracking-tighter">
                            ⚠️ Use "Entrada" ou "Perda" para movimentar o saldo.
                        </p>
                    </div>

                    {{-- ESTOQUE MÍNIMO --}}
                    <div class="group" x-show="!isCombo">
                        <label
                            class="block text-[10px] font-black text-gray-500 uppercase mb-2 tracking-widest ml-1 group-focus-within:text-orange-500">Estoque
                            Mínimo</label>
                        <input type="number" name="min_stock" value="{{ old('min_stock', $product->min_stock) }}"
                            :required="!isCombo"
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3">
                    </div>

                    {{-- STATUS --}}
                    <div class="md:col-span-2 group">
                        <label
                            class="block text-[10px] font-black text-gray-500 uppercase mb-2 tracking-widest ml-1 group-focus-within:text-orange-500">Status
                            do Produto</label>
                        <select name="is_active"
                            class="w-full bg-gray-950 border-gray-800 rounded-xl text-white font-bold focus:border-orange-500 focus:ring-orange-500 p-3 shadow-inner">
                            <option value="1" {{ old('is_active', $product->is_active) == 1 ? 'selected' : '' }}>
                                🟢 ATIVO (Disponível no PDV)</option>
                            <option value="0" {{ old('is_active', $product->is_active) == 0 ? 'selected' : '' }}>
                                🔴 INATIVO (Oculto)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-10 flex justify-between items-center border-t border-gray-800 pt-8">
                    <p class="text-[10px] text-gray-600 font-bold uppercase tracking-widest">Atualizado em:
                        {{ $product->updated_at->format('d/m/Y H:i') }}</p>
                    <button type="submit"
                        class="px-12 py-4 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-2xl transition-all shadow-xl shadow-orange-600/20 uppercase tracking-widest active:scale-95">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL CATEGORIA (MANTIDO) --}}
    <div id="catModal"
        class="hidden fixed inset-0 bg-black/95 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-gray-900 border border-gray-800 p-6 rounded-3xl w-full max-w-sm shadow-2xl">
            <h3 class="text-white font-black uppercase text-sm mb-4">Nova Categoria</h3>
            <input type="text" id="new_cat_name"
                class="w-full bg-gray-950 border-gray-800 rounded-xl text-white p-3 mb-6 focus:border-orange-500 outline-none"
                placeholder="Ex: Cervejas, Porções...">

            <div class="flex gap-3">
                <button type="button" onclick="closeCategoryModal()"
                    class="flex-1 py-2 text-gray-500 font-bold text-xs uppercase transition-colors hover:text-white">Cancelar</button>
                <button type="button" onclick="saveCategory()"
                    class="flex-1 py-3 bg-orange-600 text-white rounded-xl font-black text-xs uppercase shadow-lg shadow-orange-600/20 active:scale-95">Salvar</button>
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
            if (!name) return alert('Digite o nome da categoria!');

            try {
                const response = await fetch("{{ route('bar.categories.store_ajax') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: name
                    })
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
