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
            color: #000;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 10px 0; }
        .header h2 { margin: 0; text-transform: uppercase; }
        .items-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .items-table th { text-align: left; border-bottom: 1px solid #000; }
        .total-section { font-weight: bold; font-size: 16px; margin-top: 10px; }
        .footer { margin-top: 20px; font-size: 10px; }

        /* Oculta botões na impressão */
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print(); setTimeout(() => { window.location.href = '{{ route('bar.tables.index') }}'; }, 1000);">

    <div class="header text-center">
        <h2>RECIBO DE MESA</h2>
        <p>Mesa #{{ str_pad($order->table->identifier, 2, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="line"></div>

    <div style="font-size: 11px">
        <p><b>Pedido:</b> #{{ $order->id }}</p>
        <p><b>Data:</b> {{ $order->closed_at ? \Carbon\Carbon::parse($order->closed_at)->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</p>
        <p><b>Atendente:</b> {{ auth()->user()->name }}</p>
    </div>

    <div class="line"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td style="padding: 5px 0;">
                    {{ $item->quantity }}x {{ substr($item->product->name, 0, 20) }}
                </td>
                <td class="text-right">
                    R$ {{ number_format($item->subtotal, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <div class="total-section">
        <div style="display: flex; justify-content: space-between;">
            <span>TOTAL:</span>
            <span>R$ {{ number_format($order->total_value, 2, ',', '.') }}</span>
        </div>
    </div>

    <div class="line"></div>

    <div class="footer text-center italic">
        <p>Obrigado pela preferência!</p>
        <p>Volte Sempre!</p>
    </div>

    <div class="no-print text-center" style="margin-top: 30px;">
        <a href="{{ route('bar.tables.index') }}"
           style="background: #000; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 12px;">
            VOLTAR AO MAPA
        </a>
    </div>

</body>
</html>
