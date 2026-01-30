<x-bar-layout>
    <div class="py-12">
        <div class="max-w-md mx-auto no-print">
            <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] p-8 shadow-2xl overflow-hidden text-center mb-6">
                <p class="text-orange-500 font-black uppercase text-[10px] tracking-widest mb-2">Venda Finalizada</p>
                <h2 class="text-white font-black text-2xl uppercase italic">Recibo Disponível</h2>
            </div>
        </div>

        <div class="receipt-paper mx-auto bg-white p-6 shadow-xl" id="printableReceipt">
            <style>
                /* Estilo específico para simular o papel térmico na tela */
                .receipt-paper {
                    width: 80mm;
                    color: #000;
                    font-family: 'Courier New', Courier, monospace;
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .line { border-top: 1px dashed #000; margin: 10px 0; }
                .flex-between { display: flex; justify-content: space-between; }
                .items-table { width: 100%; font-size: 12px; border-collapse: collapse; }

                /* Ajustes para Impressão Real */
                @media print {
                    body * { visibility: hidden; background: #fff !important; }
                    .no-print { display: none !important; }
                    #printableReceipt, #printableReceipt * { visibility: visible; }
                    #printableReceipt {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 80mm;
                        margin: 0;
                        padding: 10px;
                        box-shadow: none;
                    }
                }
            </style>

            <div class="text-center">
                <h2 class="font-bold text-lg" style="margin:0; text-transform: uppercase;">{{ config('app.name', 'MEU BAR') }}</h2>
                <p style="margin: 5px 0; font-size: 12px;">Comprovante de Mesa</p>
            </div>

            <div class="line"></div>

            <div style="font-size: 12px;">
                <p><b>MESA:</b> {{ str_pad($order->table->identifier, 2, '0', STR_PAD_LEFT) }} | <b>PEDIDO:</b> #{{ $order->id }}</p>
                <p><b>DATA:</b> {{ \Carbon\Carbon::parse($order->closed_at)->format('d/m/Y H:i') }}</p>
                @if($order->customer_name)
                    <p><b>CLIENTE:</b> {{ strtoupper($order->customer_name) }}</p>
                @endif
            </div>

            <div class="line"></div>

            <table class="items-table">
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->quantity }}x {{ substr($item->product->name, 0, 18) }}</td>
                    <td class="text-right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </table>

            <div class="line"></div>

            <div class="flex-between" style="font-weight: bold; font-size: 16px">
                <span>TOTAL:</span>
                <span>R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
            </div>

            <div class="line"></div>

            <p class="text-center" style="font-size: 10px;">Obrigado pela preferência!<br>Volte Sempre!</p>
        </div>

        <div class="max-w-md mx-auto mt-8 no-print flex flex-col gap-4">
            @php
                $sugestaoFone = preg_replace('/[^0-9]/', '', $order->customer_phone);
                $msgBase = "Olá " . ($order->customer_name ?? 'cliente') . "! 👋\n\n";
                $msgBase .= "Segue o seu recibo da Mesa {$order->table->identifier}.\n";
                $msgBase .= "Total: *R$ " . number_format($order->total_value, 2, ',', '.') . "*\n\n";
                $msgBase .= "Agradecemos a preferência!";
            @endphp

            <button onclick="enviarZapComPergunta()"
                class="w-full py-5 bg-green-600 hover:bg-green-500 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-xl shadow-green-600/20 active:scale-95 flex items-center justify-center gap-3">
                📱 Enviar via WhatsApp
            </button>

            <button onclick="window.print()"
                class="w-full py-5 bg-gray-800 hover:bg-gray-700 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest border border-gray-700 transition-all flex items-center justify-center gap-3">
                🖨️ Imprimir Cupom Fiscal
            </button>

            <a href="{{ route('bar.tables.index') }}"
                class="w-full py-5 bg-transparent border border-gray-800 text-gray-500 hover:text-white hover:border-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all text-center">
                🏠 Voltar ao Mapa de Mesas
            </a>
        </div>
    </div>

    <script>
        function enviarZapComPergunta() {
            let fone = prompt("Confirme o WhatsApp do cliente (com DDD):", "{{ $sugestaoFone }}");

            if (fone !== null && fone !== "") {
                let foneLimpo = fone.replace(/\D/g, '');
                let mensagem = {!! json_encode($msgBase) !!};
                let urlZap = "https://api.whatsapp.com/send?phone=55" + foneLimpo + "&text=" + encodeURIComponent(mensagem);

                window.open(urlZap, '_blank');

                setTimeout(() => {
                    window.location.href = '{{ route("bar.tables.index") }}';
                }, 1500);
            }
        }
    </script>
</x-bar-layout>
