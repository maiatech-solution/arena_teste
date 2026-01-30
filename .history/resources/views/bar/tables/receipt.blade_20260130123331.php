<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recibo - Mesa {{ $order->table->identifier }}</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; width: 80mm; margin: 0 auto; padding: 20px; background: #fff; color: #000; }
        .text-center { text-align: center; }
        .line { border-top: 1px dashed #000; margin: 10px 0; }
        .flex { display: flex; justify-content: space-between; }
        .btn-zap { background: #25D366; color: white; padding: 12px; border-radius: 10px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: bold; margin-top: 10px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print();">

    <div class="text-center">
        <h2 style="margin:0">MEU BAR & RESTAURANTE</h2>
        <p>Comprovante de Mesa</p>
    </div>

    <div class="line"></div>
    <p><b>Mesa:</b> {{ $order->table->identifier }} | <b>Pedido:</b> #{{ $order->id }}</p>
    <p><b>Data:</b> {{ \Carbon\Carbon::parse($order->closed_at)->format('d/m/Y H:i') }}</p>
    <div class="line"></div>

    <table style="width: 100%; font-size: 12px;">
        @foreach($order->items as $item)
        <tr>
            <td>{{ $item->quantity }}x {{ substr($item->product->name, 0, 15) }}</td>
            <td style="text-align: right">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
        </tr>
        @endforeach
    </table>

    <div class="line"></div>
    <div class="flex" style="font-weight: bold; font-size: 16px">
        <span>TOTAL:</span>
        <span>R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
    </div>
    <div class="line"></div>

    <div class="no-print text-center" style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;">

        @php
            // Prepara a mensagem do WhatsApp
            $mensagem = "Olá! Segue o seu recibo da Mesa {$order->table->identifier}.\n\n";
            $mensagem .= "Total: R$ " . number_format($order->total_value, 2, ',', '.') . "\n";
            $mensagem .= "Obrigado pela preferência!";
            $urlZap = "https://api.whatsapp.com/send?phone=55" . preg_replace('/[^0-9]/', '', $order->customer_phone) . "&text=" . urlencode($mensagem);
        @endphp

        @if($order->customer_phone)
        <a href="{{ $urlZap }}" target="_blank" class="btn-zap">
            <span>📱 Enviar WhatsApp</span>
        </a>
        @endif

        <a href="{{ route('bar.tables.index') }}" style="background: #333; color: white; padding: 12px; border-radius: 10px; text-decoration: none; font-weight: bold;">
            🏠 Voltar ao Mapa de Mesas
        </a>
    </div>

</body>
</html>
