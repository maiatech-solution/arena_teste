<x-bar-layout>
    <div class="py-12">
        <div class="max-w-md mx-auto no-print">
            <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] p-8 shadow-2xl overflow-hidden text-center mb-6">
                <p class="text-orange-500 font-black uppercase text-[10px] tracking-widest mb-2">Venda Finalizada</p>
                <h2 class="text-white font-black text-2xl uppercase italic">Recibo Disponível</h2>
            </div>
        </div>

        <div id="printableReceipt" class="mx-auto bg-white p-6 shadow-xl receipt-container">
            <style>
                /* Estilo para visualização na tela do PC */
                .receipt-container {
                    width: 80mm;
                    color: #000;
                    font-family: 'Courier New', Courier, monospace;
                    background: white;
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .line { border-top: 1px dashed #000; margin: 10px 0; }
                .flex-between { display: flex; justify-content: space-between; }
                .items-table { width: 100%; font-size: 12px; border-collapse: collapse; }

                /* 🏁 O SEGREDO DA IMPRESSÃO TÉRMICA */
                @media print {
                    /* Esconde TUDO do layout (menu, topo, etc) */
                    body, html {
                        background: #fff !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }

                    /* Esconde os componentes do layout do Blade */
                    nav, aside, footer, .no-print {
                        display: none !important;
                    }

                    /* Configuração da Página: Força largura de bobina e altura automática */
                    @page {
                        size: 80mm auto;
                        margin: 0;
                    }

                    /* Posicionamento absoluto no topo esquerdo da folha */
                    #printableReceipt {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 80mm;
                        margin: 0 !important;
                        padding: 4mm !important; /* Margem interna mínima para não cortar texto */
                        box-shadow: none !important;
                        border: none !important;
                        visibility: visible !important;
                        display: block !important;
                    }

                    /* Garante que o texto seja preto puro */
                    * {
                        color: #000 !important;
                        -webkit-print-color-adjust: exact;
                    }
                }
            </style>

            <div class="text-center">
                <h2 class="font-bold text-lg" style="margin:0; text-transform: uppercase;">{{ config('app.name', 'ARENA BOOKING') }}</h2>
                <p style="margin: 5px 0; font-size: 11px;">Comprovante de Mesa</p>
            </div>

            <div class="line"></div>

            <div style="font-size: 11px;">
                <p style="margin: 2px 0;"><b>MESA:</b> {{ str_pad($table->identifier, 2, '0', STR_PAD_LEFT) }} | <b>PEDIDO:</b> #{{ $order->id }}</p>
                <p style="margin: 2px 0;"><b>DATA:</b> {{ \Carbon\Carbon::parse($order->closed_at)->format('d/m/Y H:i') }}</p>
            </div>

            <div class="line"></div>

            <table class="items-table">
                @foreach($order->items as $item)
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

        <div class="max-w-md mx-auto mt-8 no-print flex flex-col gap-4">
            @php
                $sugestaoFone = preg_replace('/[^0-9]/', '', $order->customer_phone);
                $msgBase = "Olá! 👋\nSegue o seu recibo da Mesa {$table->identifier}.\nTotal: *R$ " . number_format($order->total_value, 2, ',', '.') . "*";
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
    </div>

    <script>
        function enviarZapComPergunta() {
            let fone = prompt("Confirme o WhatsApp do cliente:", "{{ $sugestaoFone }}");
            if (fone) {
                let foneLimpo = fone.replace(/\D/g, '');
                let urlZap = "https://api.whatsapp.com/send?phone=55" + foneLimpo + "&text=" + encodeURIComponent({!! json_encode($msgBase) !!});
                window.open(urlZap, '_blank');
                setTimeout(() => { window.location.href = '{{ route("bar.tables.index") }}'; }, 1000);
            }
        }
    </script>
</x-bar-layout>
