<x-bar-layout>
    {{-- 1. Campo oculto para o JavaScript ler o valor sem erro de sintaxe do Blade --}}
    <input type="hidden" id="totalMesaRaw" value="{{ $order->total_value }}">

    {{-- 2. BLOCO ANTIAUTOFILL (ISCA): Escondido do usuário para o navegador preencher aqui --}}
    <div style="position: absolute; left: -9999px; top: -9999px;">
        <input type="text" name="fake_email_field">
        <input type="password" name="fake_password_field">
    </div>

    <div class="max-w-[1600px] mx-auto px-6 py-6 flex flex-col md:flex-row gap-8">

        {{-- COLUNA DA ESQUERDA: LANÇAMENTO DE PRODUTOS --}}
        <div class="flex-1 bg-gray-900 rounded-[2.5rem] border border-gray-800 p-8 flex flex-col shadow-2xl">
            <div class="flex items-center justify-between mb-8">
                <a href="{{ route('bar.tables.index') }}"
                    class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg text-[10px] font-black uppercase tracking-widest">
                    ◀ Voltar ao Mapa
                </a>
                <h2 class="text-2xl font-black text-white uppercase italic">
                    Lançar na <span class="text-orange-500">Mesa {{ $table->identifier }}</span>
                </h2>
            </div>

            {{-- INPUT DE BUSCA BLINDADO: Não aceita preenchimento automático do Chrome --}}
            <div class="relative mb-6">
                <input type="text" id="mainSearch" onkeyup="liveSearch()"
                    placeholder="🔍 Buscar produto pelo nome..." autocomplete="off" readonly
                    onfocus="this.removeAttribute('readonly');"
                    class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-5 focus:border-orange-500 focus:ring-1 focus:ring-orange-500 outline-none transition-all font-bold">
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 overflow-y-auto no-scrollbar pr-2"
                style="max-height: 600px;">
                @foreach ($products as $product)
                    @php
                        $isEsgotado = $product->manage_stock && $product->stock_quantity <= 0;
                    @endphp

                    <button onclick="addItemToOrder({{ $product->id }})" data-name="{{ strtolower($product->name) }}"
                        class="product-card group relative p-4 bg-gray-800 rounded-2xl border border-gray-700 transition-all flex flex-col items-center justify-center text-center active:scale-95 shadow-md h-32
                {{ $isEsgotado ? 'opacity-40 grayscale cursor-not-allowed' : 'hover:bg-orange-600 hover:border-orange-500' }}"
                        {{ $isEsgotado ? 'disabled' : '' }}>

                        <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🍺</div>

                        <div class="w-full">
                            <h3
                                class="text-[10px] font-black text-white uppercase leading-tight line-clamp-2 mb-1 group-hover:text-white">
                                {{ $product->name }}
                            </h3>

                            <div class="flex flex-col gap-1">
                                <span class="text-orange-500 font-black text-xs group-hover:text-white">
                                    R$ {{ number_format($product->sale_price, 2, ',', '.') }}
                                </span>

                                @if ($product->manage_stock)
                                    <span
                                        class="text-[8px] font-bold uppercase {{ $isEsgotado ? 'text-red-500' : 'text-gray-500 group-hover:text-white' }}">
                                        {{ $isEsgotado ? 'Esgotado' : 'Estoque: ' . $product->stock_quantity }}
                                    </span>
                                @else
                                    <span class="text-[8px] font-bold text-blue-500 group-hover:text-white uppercase">
                                        ∞ Ilimitado
                                    </span>
                                @endif
                            </div>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- COLUNA DA DIREITA: ITENS DA COMANDA --}}
        <div
            class="w-full md:w-96 bg-gray-900 rounded-[2.5rem] border border-gray-800 flex flex-col shadow-2xl overflow-hidden">
            <div class="p-6 bg-gray-800/50 border-b border-gray-800 flex justify-between items-center">
                <h3 class="font-black text-white uppercase italic text-sm">📝 Itens da Comanda</h3>
                <span class="bg-orange-600/20 text-orange-500 px-3 py-1 rounded-full text-[10px] font-black">ID:
                    #{{ $order->id }}</span>
            </div>

            <div class="flex-1 p-6 space-y-3 overflow-y-auto no-scrollbar" id="orderItemsList">
                @forelse($order->items as $item)
                    <div
                        class="flex justify-between items-center bg-gray-950 p-4 rounded-2xl border border-gray-800 animate-slide-in group/item">
                        <div class="flex flex-col">
                            <span class="text-white text-[10px] font-black uppercase">{{ $item->product->name }}</span>
                            <span class="text-gray-500 text-[9px] font-bold">{{ $item->quantity }}x R$
                                {{ number_format($item->unit_price, 2, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="text-orange-500 text-xs font-black">R$
                                    {{ number_format($item->subtotal, 2, ',', '.') }}</span>
                            </div>

                            {{-- 🛡️ BOTÃO DE ESTORNO COM TRAVA DE SEGURANÇA --}}
                            <form id="form-estorno-{{ $item->id }}"
                                action="{{ route('bar.tables.remove_item', $item->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                    onclick="requisitarAutorizacao(() => document.getElementById('form-estorno-{{ $item->id }}').submit())"
                                    class="text-gray-700 hover:text-red-500 transition-colors p-1"
                                    title="Estornar Item">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-20 opacity-20 text-white font-black uppercase text-[10px]">Vazio</div>
                @endforelse
            </div>

            <div class="p-6 bg-gray-950 border-t border-gray-800 space-y-4">
                <div class="flex justify-between items-end">
                    <span class="text-gray-500 text-[10px] font-black uppercase">Total Parcial</span>
                    <span class="text-3xl font-black text-white">R$
                        {{ number_format($order->total_value, 2, ',', '.') }}</span>
                </div>

                <button onclick="abrirCheckout()"
                    class="w-full py-5 bg-green-600 hover:bg-green-500 text-white font-black rounded-2xl uppercase text-[10px] tracking-[0.2em] transition-all shadow-xl shadow-green-600/20 active:scale-95">
                    🏁 Fechar e Receber
                </button>
            </div>
        </div>
    </div>

    <div id="modalCheckout"
        class="hidden fixed inset-0 bg-black/95 backdrop-blur-md z-[300] flex items-center justify-center p-4">
        <div
            class="bg-gray-900 border border-gray-800 rounded-[2.5rem] w-full max-w-5xl overflow-hidden shadow-2xl flex flex-col md:flex-row">

            {{-- LADO ESQUERDO: RESUMO FINANCEIRO --}}
            <div class="p-8 bg-gray-800/30 border-r border-gray-800 w-full md:w-96">
                <h3 class="text-white font-black uppercase italic mb-8 tracking-widest">Resumo Financeiro</h3>
                <div class="space-y-4">
                    <div class="bg-gray-950 p-6 rounded-3xl border border-gray-800 text-white font-black">
                        <span class="text-gray-500 text-[9px] uppercase block mb-1">Total da Mesa</span>
                        <span class="text-4xl">R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
                    </div>

                    {{-- NOVO: BLOCO DE DESCONTO COM TRAVA DE SEGURANÇA --}}
                    <div class="bg-red-600/5 p-6 rounded-3xl border border-red-600/20">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-red-500 text-[9px] font-black uppercase block">Desconto</span>

                            {{-- Botão de liberar só aparece para quem NÃO é gestor/admin --}}
                            @if (!in_array(auth()->user()->role, ['admin', 'gestor']))
                                <button type="button" id="btnLiberarDesconto"
                                    onclick="requisitarAutorizacao(() => liberarCampoDesconto())"
                                    class="text-[8px] bg-red-600 text-white px-2 py-0.5 rounded font-black uppercase shadow-lg active:scale-95 transition-all">
                                    🔑 Liberar
                                </button>
                            @endif
                        </div>

                        <div class="relative">
                            <span
                                class="absolute left-0 top-1/2 -translate-y-1/2 text-gray-600 font-black text-xs">R$</span>
                            <input type="number" id="inputDesconto" name="discount" step="0.01" value="0.00"
                                oninput="atualizarTelaPagamento()"
                                {{ in_array(auth()->user()->role, ['admin', 'gestor']) ? '' : 'disabled' }}
                                class="w-full bg-transparent border-none p-0 pl-6 text-2xl font-black text-red-500 outline-none focus:ring-0 disabled:opacity-30">
                        </div>
                    </div>

                    <div class="bg-orange-600/10 p-6 rounded-3xl border border-orange-600/20">
                        <span class="text-orange-500 text-[9px] font-black uppercase block mb-1">Restante</span>
                        <span class="text-4xl font-black text-orange-500" id="textRestante">R$
                            {{ number_format($order->total_value, 2, ',', '.') }}</span>
                    </div>
                    <div class="bg-green-600/10 p-6 rounded-3xl border border-green-600/20">
                        <span class="text-green-500 text-[9px] font-black uppercase block mb-1">Troco</span>
                        <span class="text-4xl font-black text-green-500" id="textTroco">R$ 0,00</span>
                    </div>
                </div>
            </div>

            {{-- LADO DIREITO: ENTRADA DE PAGAMENTOS --}}
            <div class="p-8 flex-1">
                <div class="flex justify-between mb-8">
                    <h3 class="text-xl font-black text-white uppercase italic">Finalizar Pagamento</h3>
                    <button onclick="document.getElementById('modalCheckout').classList.add('hidden')"
                        class="text-gray-500 hover:text-white transition-colors text-2xl">✕</button>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div class="col-span-1">
                        <label class="text-[9px] font-black text-gray-500 uppercase mb-2 block tracking-widest">Nome do
                            Cliente (Opcional)</label>
                        <input type="text" id="customer_name" placeholder="Ex: João Silva"
                            class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 font-bold outline-none focus:border-orange-600 transition-all">
                    </div>
                    <div class="col-span-1">
                        <label
                            class="text-[9px] font-black text-gray-500 uppercase mb-2 block tracking-widest">WhatsApp</label>
                        <input type="text" id="customer_phone" placeholder="(91) 99999-9999"
                            class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 font-bold outline-none focus:border-orange-600 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label
                            class="text-[9px] font-black text-gray-500 uppercase mb-2 block tracking-widest">Valor</label>
                        <input type="number" id="inputValorPago" step="0.01"
                            class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 text-2xl font-black outline-none focus:border-orange-600">
                    </div>
                    <div>
                        <label
                            class="text-[9px] font-black text-gray-500 uppercase mb-2 block tracking-widest">Método</label>
                        <select id="selectMetodo"
                            class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 font-black outline-none focus:border-orange-600">
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">💎 PIX</option>
                            <option value="debito">💳 Débito</option>
                            <option value="credito">💳 Crédito</option>
                        </select>
                    </div>
                </div>

                <button onclick="addPagamento()"
                    class="w-full py-4 bg-gray-800 hover:bg-orange-600 text-white font-black rounded-2xl uppercase text-[9px] mb-6 transition-all border border-gray-700 hover:border-orange-500 tracking-[0.2em]">
                    + Adicionar na Lista
                </button>

                <div id="listaPagamentos" class="space-y-2 mb-8 max-h-40 overflow-y-auto pr-2 custom-scroll"></div>

                {{-- FORMULÁRIO DE FECHAMENTO --}}
                <form action="{{ route('bar.tables.close', $table->id) }}" method="POST" id="formFecharMesa">
                    @csrf
                    <input type="hidden" name="customer_name" id="hidden_customer_name">
                    <input type="hidden" name="customer_phone" id="hidden_customer_phone">
                    <input type="hidden" name="pagamentos" id="inputPagamentosHidden">

                    {{-- ADICIONE ESTE CAMPO AQUI --}}
                    <input type="hidden" name="discount_value" id="hidden_discount_value" value="0">

                    <input type="hidden" name="print_coupon" id="inputPrintCoupon" value="0">
                    <input type="hidden" name="send_whatsapp" id="inputSendWhatsApp" value="0">

                    <button type="button" onclick="abrirOpcoesFinais()" id="btnFinalizarGeral"
                        class="w-full py-6 bg-green-600 text-white font-black rounded-[2rem] uppercase tracking-[0.3em] text-sm shadow-2xl transition-all opacity-30 cursor-not-allowed"
                        disabled>
                        🏁 Finalizar e Salvar no Banco
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="modalPosFinalizar"
        class="hidden fixed inset-0 bg-black/95 backdrop-blur-md z-[400] flex items-center justify-center p-4">
        <div
            class="bg-gray-900 border border-gray-800 p-10 rounded-[3rem] w-full max-w-md text-center shadow-2xl border-t-green-500/50">

            <div class="text-6xl mb-6 animate-bounce">🎉</div>

            <h3 class="text-2xl font-black text-white uppercase italic mb-2 tracking-tighter">
                Venda <span class="text-green-500">Concluída!</span>
            </h3>

            <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mb-10 leading-relaxed">
                O pagamento foi processado. <br> Como deseja prosseguir?
            </p>

            <div class="flex flex-col gap-3">
                {{-- BOTÃO DE IMPRESSÃO (DESTAQUE) --}}
                <button onclick="executarAcaoFinal('imprimir')"
                    class="w-full py-5 bg-white text-black font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-orange-500 hover:text-white transition-all shadow-xl shadow-white/5">
                    🖨️ Emitir Comprovante
                </button>

                {{-- BOTÃO DE APENAS SAIR --}}
                <button onclick="executarAcaoFinal('sair')"
                    class="w-full py-4 bg-gray-800/50 text-gray-500 font-black rounded-2xl uppercase text-[9px] tracking-widest hover:text-white hover:bg-gray-800 transition-all">
                    ✖ Apenas Sair e Liberar Mesa
                </button>
            </div>
        </div>
    </div>

    <script>
        // 1. Variáveis Globais (Busca do HTML para evitar erro de sintaxe do Blade no JS)
        let totalMesa = parseFloat(document.getElementById('totalMesaRaw').value) || 0;
        let pagamentos = [];
        let pagoAcumulado = 0;

        // 2. Busca Dinâmica de Produtos no Lançamento
        function liveSearch() {
            const query = document.getElementById('mainSearch').value.toLowerCase();
            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                const name = card.dataset.name || "";
                card.classList.toggle('hidden', !name.includes(query));
            });
        }

        // 3. Adicionar Item via AJAX (Revisado para maior estabilidade)
        async function addItemToOrder(productId) {
            try {
                const response = await fetch("{{ route('bar.tables.add_item', $order->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // Mantido conforme seu padrão, mas certifique-se que o token é renovado
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                });

                // Se a resposta for 419 (Token expirado), recarrega a página
                if (response.status === 419) {
                    window.location.reload();
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert("⚠️ Atenção: " + (data.message || "Erro ao adicionar"));
                }
            } catch (error) {
                console.error("Erro na requisição:", error);
                // Evita alertas chatos se o usuário apenas fechar a aba
                if (error.name !== 'AbortError') {
                    alert("❌ Erro de conexão ao tentar lançar o produto.");
                }
            }
        }

        // 4. Lógica do Modal de Checkout
        function abrirCheckout() {
            document.getElementById('modalCheckout').classList.remove('hidden');
            document.getElementById('inputValorPago').value = (totalMesa - pagoAcumulado).toFixed(2);
            document.getElementById('inputValorPago').focus();
        }

        // 5. Adicionar Pagamento à Lista Temporária
        function addPagamento() {
            const valorInput = document.getElementById('inputValorPago');
            const valor = parseFloat(valorInput.value);
            const metodo = document.getElementById('selectMetodo').value;

            if (valor > 0) {
                pagamentos.push({
                    metodo: metodo,
                    valor: valor
                });
                atualizarTelaPagamento();
                valorInput.focus();
            }
        }

        // 6. Atualizar Cálculos de Restante e Troco na Tela
        function atualizarTelaPagamento() {
            pagoAcumulado = pagamentos.reduce((acc, p) => acc + p.valor, 0);
            const lista = document.getElementById('listaPagamentos');

            lista.innerHTML = pagamentos.map((p, i) => `
            <div class="flex justify-between items-center bg-gray-950 p-4 rounded-2xl border border-gray-800 text-[10px] font-black uppercase text-white animate-slide-in">
                <span>${p.metodo}</span>
                <div class="flex items-center gap-4">
                    <span class="text-orange-500 font-bold">R$ ${p.valor.toFixed(2)}</span>
                    <button onclick="removerPagamento(${i})" class="text-red-500 hover:text-red-400">✕</button>
                </div>
            </div>
        `).join('');

            const faltante = totalMesa - pagoAcumulado;
            const troco = pagoAcumulado > totalMesa ? pagoAcumulado - totalMesa : 0;

            document.getElementById('textRestante').innerText = 'R$ ' + (faltante > 0 ? faltante : 0).toLocaleString(
                'pt-br', {
                    minimumFractionDigits: 2
                });
            document.getElementById('textTroco').innerText = 'R$ ' + troco.toLocaleString('pt-br', {
                minimumFractionDigits: 2
            });

            const btn = document.getElementById('btnFinalizarGeral');
            if (pagoAcumulado >= (totalMesa - 0.01)) {
                btn.disabled = false;
                btn.classList.remove('opacity-30', 'cursor-not-allowed');
            } else {
                btn.disabled = true;
                btn.classList.add('opacity-30', 'cursor-not-allowed');
            }

            if (faltante > 0) {
                document.getElementById('inputValorPago').value = faltante.toFixed(2);
            } else {
                document.getElementById('inputValorPago').value = '0.00';
            }
        }

        // 7. Remover Pagamento da Lista
        function removerPagamento(index) {
            pagamentos.splice(index, 1);
            atualizarTelaPagamento();
        }

        // 8. Abrir Modal de Opções Finais (Imprimir/Sair)
        function abrirOpcoesFinais() {
            const modalOpcoes = document.getElementById('modalPosFinalizar');
            const modalCheckout = document.getElementById('modalCheckout');

            if (modalOpcoes) {
                if (modalCheckout) modalCheckout.classList.add('hidden');
                modalOpcoes.classList.remove('hidden');
                modalOpcoes.style.zIndex = "9999";
            } else {
                executarAcaoFinal('sair');
            }
        }

        // 9. Execução Final da Venda (Preenche o form e faz o Submit)
        function executarAcaoFinal(acao) {
            document.getElementById('inputPrintCoupon').value = (acao === 'imprimir') ? "1" : "0";
            document.getElementById('inputSendWhatsApp').value = (acao === 'whatsapp') ? "1" : "0";

            document.getElementById('hidden_customer_name').value = document.getElementById('customer_name').value;
            document.getElementById('hidden_customer_phone').value = document.getElementById('customer_phone').value;
            document.getElementById('inputPagamentosHidden').value = JSON.stringify(pagamentos);

            console.log("Enviando formulário final com ação:", acao);

            document.getElementById('formFecharMesa').submit();
        }
    </script>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #333;
            border-radius: 10px;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(10px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .animate-slide-in {
            animation: slideIn 0.3s ease-out forwards;
        }
    </style>
</x-bar-layout>
