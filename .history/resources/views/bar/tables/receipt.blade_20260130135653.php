<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo - Mesa {{ $order->table->identifier }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 20px;
            background: #fff;
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 10px 0; }
        .flex { display: flex; justify-content: space-between; }

        .btn-zap {
            background: #25D366;
            color: white;
            padding: 14px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: bold;
            font-family: sans-serif;
            font-size: 14px;
            transition: background 0.3s;
        }
        .btn-zap:hover { background: #128C7E; }

        .btn-back {
            background: #333;
            color: white;
            padding: 14px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            font-family: sans-serif;
            font-size: 14px;
        }

        .items-table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .items-table td { padding: 3px 0; }

        @media print {
            .no-print { display: none; }
            body { padding: 0; width: 100%; }
        }
    </style>
</head>
<body onload="window.print();">

    <div class="text-center">
        <h2 style="margin:0; text-transform: uppercase;">{{ config('app.name', 'MEU BAR') }}</h2>
        <p style="margin: 5px 0;">Comprovante de Mesa</p>
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

    <div class="flex" style="font-weight: bold; font-size: 16px">
        <span>TOTAL:</span>
        <span>R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
    </div>

    <div class="line"></div>

    <p class="text-center" style="font-size: 10px;">Obrigado pela preferência!<br>Volte Sempre!</p>

    <div class="no-print" style="margin-top: 40px; display: flex; flex-direction: column; gap: 12px;">

        @php
            // Limpa o número para o link do WhatsApp (deixa apenas dígitos)
            $phoneClean = preg_replace('/[^0-9]/', '', $order->customer_phone);

            // Monta a mensagem personalizada
            $msgZap = "Olá " . ($order->customer_name ?? 'cliente') . "! 👋\n\n";
            $msgZap .= "Segue o seu recibo da Mesa {$order->table->identifier}.\n";
            $msgZap .= "Total: *R$ " . number_format($order->total_value, 2, ',', '.') . "*\n\n";
            $msgZap .= "Agradecemos a preferência!";

            $urlZap = "https://api.whatsapp.com/send?phone=55" . $phoneClean . "&text=" . urlencode($msgZap);
        @endphp

        @if($order->customer_phone)
            <a href="{{ $urlZap }}" target="_blank" class="btn-zap">
                📱 Enviar via WhatsApp
            </a>
        @endif

        <button onclick="window.print()" class="btn-back" style="background: #efefef; color: #333; border: 1px solid #ccc; cursor: pointer;">
            🖨️ Reimprimir Cupom
        </button>

        <a href="{{ route('bar.tables.index') }}" class="btn-back">
            🏠 Voltar ao Mapa de Mesas
        </a>
    </div>

</body>
</html>
