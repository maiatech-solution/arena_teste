<x-bar-layout>
    <div class="py-12 no-print">
        <div class="max-w-md mx-auto">
            <div
                class="bg-gray-900 border border-gray-800 rounded-[2.5rem] p-8 shadow-2xl overflow-hidden text-center mb-6">
                <p class="text-orange-500 font-black uppercase text-[10px] tracking-widest mb-2">Venda Finalizada</p>
                <h2 class="text-white font-black text-2xl uppercase italic">Recibo Disponível</h2>
            </div>
        </div>
    </div>

    <div id="printableReceipt" class="receipt-container">
        <style>
            /* Visualização na Tela do Sistema */
            .receipt-container {
                width: 80mm;
                margin: 0 auto;
                background: white;
                color: #000;
                padding: 20px;
                font-family: 'Courier New', Courier, monospace;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }

            .text-center {
                text-align: center;
            }

            .text-right {
                text-align: right;
            }

            .line {
                border-top: 1px dashed #000;
                margin: 10px 0;
            }

            .flex-between {
                display: flex;
                justify-content: space-between;
            }

            .items-table {
                width: 100%;
                font-size: 12px;
                border-collapse: collapse;
            }

            /* 🖨️ AJUSTE DE IMPRESSÃO UNIVERSAL (CENTRALIZADO E NO TOPO) */
            @media print {

                /* Esconde tudo do sistema original */
                body * {
                    visibility: hidden;
                }

                nav,
                aside,
                header,
                .no-print {
                    display: none !important;
                }

                /* Reset de página para evitar deslocamentos do navegador */
                @page {
                    margin: 0;
                }

                /* Prepara o body para centralizar o conteúdo */
                body {
                    visibility: hidden;
                    margin: 0 !important;
                    padding: 0 !important;
                    display: flex !important;
                    justify-content: center !important;
                    /* Centraliza horizontalmente */
                    align-items: flex-start !important;
                    /* Garante que fique no topo */
                    background: #fff !important;
                }

                /* Torna apenas o recibo visível e remove posicionamentos fixos/absolutos */
                #printableReceipt,
                #printableReceipt * {
                    visibility: visible !important;
                }

                #printableReceipt {
                    position: relative !important;
                    width: 80mm !important;
                    /* Mantém a largura de cupom */
                    margin: 0 auto !important;
                    padding: 5mm !important;
                    /* Margem interna para não cortar em impressoras laser */
                    box-shadow: none !important;
                    border: none !important;
                    left: auto !important;
                    top: 0 !important;
                    display: block !important;
                }
            }
        </style>

        <div class="text-center">
            <h2 class="font-bold text-lg" style="margin:0; text-transform: uppercase;">
                {{ config('app.name', 'ARENA BOOKING') }}</h2>
            <p style="margin: 5px 0; font-size: 11px;">Comprovante de Mesa</p>
        </div>

        <div class="line"></div>

        <div style="font-size: 11px;">
            <p style="margin: 2px 0;"><b>MESA:</b> {{ str_pad($order->table->identifier, 2, '0', STR_PAD_LEFT) }} |
                <b>PEDIDO:</b> #{{ $order->id }}
            </p>
            <p style="margin: 2px 0;"><b>DATA:</b> {{ \Carbon\Carbon::parse($order->closed_at)->format('d/m/Y H:i') }}
            </p>
        </div>

        <div class="line"></div>

        <table class="items-table">
            @foreach ($order->items as $item)
                <tr>
                    <td style="padding: 4px 0;">
                        <span style="display:block;">{{ $item->quantity }}x {{ $item->product->name }}</span>
                        <small style="color: #666;">R$ {{ number_format($item->unit_price, 2, ',', '.') }} un</small>
                    </td>
                    <td class="text-right" style="vertical-align: top; padding-top: 4px;">
                        R$ {{ number_format($item->subtotal, 2, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </table>

        <div class="line"></div>

        <div class="flex-between" style="font-weight: bold; font-size: 15px">
            <span>TOTAL:</span>
            <span>R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
        </div>

        <div class="line"></div>

        <div class="text-center" style="font-size: 10px; margin-top: 10px;">
            <p>Obrigado pela preferência!</p>
            <p>Volte Sempre!</p>
        </div>
    </div>

    <div class="max-w-md mx-auto mt-8 no-print flex flex-col gap-4 pb-12">
        @php
            $sugestaoFone = preg_replace('/[^0-9]/', '', $order->customer_phone);

            // Montando a lista de itens para a mensagem
            $itensTexto = '';
            foreach ($order->items as $item) {
                $itensTexto .= "• {$item->quantity}x {$item->product->name}\n";
            }

            $msgBase = '*✨ ' . strtoupper(config('app.name')) . " ✨*\n";
            $msgBase .= "--------------------------------\n";
            $msgBase .= 'Olá, ' . ($order->customer_name ?? 'Cliente') . "! 👋\n";
            $msgBase .= "Aqui está o resumo da sua comanda:\n\n";
            $msgBase .= "*ITENS PEDIDOS:*\n";
            $msgBase .= $itensTexto;
            $msgBase .= "\n*TOTAL: R$ " . number_format($order->total_value, 2, ',', '.') . "*\n";
            $msgBase .= "--------------------------------\n";
            $msgBase .=
                'Mesa: ' . str_pad($order->table->identifier, 2, '0', STR_PAD_LEFT) . " | Pedido: #{$order->id}\n\n";
            $msgBase .= 'Agradecemos a preferência! Volte sempre! 😊';
        @endphp

        <button onclick="enviarZapComPergunta()"
            class="w-full py-5 bg-green-600 hover:bg-green-500 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-xl shadow-green-600/20 flex items-center justify-center gap-3">
            📱 Enviar via WhatsApp
        </button>

        <button onclick="window.print()"
            class="w-full py-5 bg-gray-800 hover:bg-gray-700 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest border border-gray-700 flex items-center justify-center gap-3">
            🖨️ Imprimir Cupom Fiscal
        </button>

        <a href="{{ route('bar.tables.index') }}"
            class="w-full py-5 bg-transparent border border-gray-800 text-gray-500 hover:text-white font-black rounded-2xl uppercase text-[10px] tracking-widest text-center">
            🏠 Voltar ao Mapa de Mesas
        </a>
    </div>

    <div id="modalSucesso"
        class="hidden fixed inset-0 bg-black/90 backdrop-blur-sm z-[500] flex items-center justify-center p-4 no-print">
        <div
            class="bg-gray-900 border border-gray-800 p-8 rounded-[3rem] max-w-sm w-full text-center shadow-2xl border-t-green-500/50 animate-in fade-in zoom-in duration-300">
            <div class="text-6xl mb-4">🎉</div>
            <h2 class="text-2xl font-black text-white uppercase italic mb-2">Venda Concluída!</h2>
            <p class="text-gray-400 text-[10px] uppercase tracking-widest mb-8 font-bold">O estoque foi atualizado e a
                mesa está livre.</p>

            <div class="space-y-3">
                <button onclick="fecharModalSucesso()"
                    class="w-full py-4 bg-white text-black font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-orange-500 hover:text-white transition-all">
                    👁️ Visualizar Recibo
                </button>
                <a href="{{ route('bar.tables.index') }}"
                    class="block w-full py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-gray-700 hover:text-white transition-all">
                    🏠 Voltar ao Mapa
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            /**
             * Verificamos se o Controller enviou o sinal 'show_success_modal'.
             * Se sim, removemos a classe 'hidden' do modal para ele aparecer.
             */
            @if (session('show_success_modal'))
                const modal = document.getElementById('modalSucesso');
                if (modal) {
                    modal.classList.remove('hidden');
                }
            @endif
        });

        /**
         * Função para fechar o modal.
         * Ela apenas esconde a janela de sucesso para que o
         * garçom possa ver o recibo que está por baixo.
         */
        function fecharModalSucesso() {
            const modal = document.getElementById('modalSucesso');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
    </script>
</x-bar-layout>
