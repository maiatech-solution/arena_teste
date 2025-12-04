@php
    // Injeta a classe Carbon, necessária para a lógica de data
    use Carbon\Carbon;
    $today = Carbon::now()->startOfDay();
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Data</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Horário</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">Status</th>
                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">Preço</th>
                {{-- Coluna de Pagamento --}}
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">Pagamento</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[150px]">Tipo</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Detalhes</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            @forelse ($reservas as $reserva)
                @php
                    // Variáveis de data e valores
                    $reservaDate = Carbon::parse($reserva->date);
                    $totalPrice = $reserva->final_price ?? $reserva->price;
                    $totalPaid = $reserva->total_paid ?? 0;
                    $isFullyPaid = $reserva->is_paid ?? ($totalPaid >= $totalPrice); // Usa o is_paid do modelo ou calcula
                    $isConfirmed = $reserva->status === 'confirmed' || $reserva->status === 'recurrent';
                    $remainingAmount = $totalPrice - $totalPaid;

                    // Lógica de Status da Reserva (Mantida)
                    $statusInfo = match ($reserva->status) {
                        'confirmed' => ['label' => 'Confirmada', 'class' => 'bg-green-100 text-green-700'],
                        'pending' => ['label' => 'Aguardando', 'class' => 'bg-yellow-100 text-yellow-700'],
                        'canceled', 'rejected' => ['label' => 'Cancelada', 'class' => 'bg-red-100 text-red-700'],
                        'expired' => ['label' => 'Expirada', 'class' => 'bg-gray-300 text-gray-700'],
                        'no_show' => ['label' => 'Falta (Retido)', 'class' => 'bg-red-200 text-red-800 font-extrabold'],
                        default => ['label' => 'Desconhecido', 'class' => 'bg-gray-100 text-gray-700'],
                    };

                    // --- LÓGICA DE PAGAMENTO SOLICITADA ---
                    $paymentStatusInfo = ['label' => 'N/A', 'class' => 'bg-gray-200 text-gray-700'];

                    if (!$isConfirmed && $reserva->status !== 'pending') {
                         // Reservas canceladas, rejeitadas, etc., não entram na lógica de cobrança ativa.
                         $paymentStatusInfo = ['label' => 'N/A', 'class' => 'bg-gray-200 text-gray-700'];

                    } elseif ($isFullyPaid) {
                        $paymentStatusInfo = ['label' => 'PAGA', 'class' => 'bg-green-100 text-green-700 font-bold'];

                    } elseif ($reservaDate->lt($today)) {
                        // ** ⚠️ DATA JÁ PASSOU (Status de Atraso ou Não Pago) **
                        if ($totalPaid == 0) {
                            $paymentStatusInfo = ['label' => 'NÃO PAGO', 'class' => 'bg-red-200 text-red-800 font-extrabold shadow-sm'];
                        } else {
                            $paymentStatusInfo = ['label' => 'ATRASADO', 'class' => 'bg-red-100 text-red-700 font-bold'];
                        }

                    } elseif ($totalPaid > 0) {
                        // Data Futura, mas Pagamento Parcial
                        $paymentStatusInfo = ['label' => 'Parcialmente Paga', 'class' => 'bg-blue-100 text-blue-700'];

                    } else {
                        // Data Futura, Pagamento Zero (Aguardando Sinal/Total)
                        $paymentStatusInfo = ['label' => 'Pendente de Pagto', 'class' => 'bg-yellow-100 text-yellow-700'];
                    }

                    // Formatação
                    $priceDisplay = number_format($totalPrice, 2, ',', '.');

                    // ✅ CRÍTICO: Obtém a data no formato YYYY-MM-DD para a URL
                    $reservaDateUrl = $reservaDate->format('Y-m-d');
                @endphp
                <tr class="odd:bg-white even:bg-gray-50 hover:bg-gray-100 transition duration-150">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold">
                        <span class="px-2 inline-flex text-xs leading-5 rounded-full {{ $statusInfo['class'] }} uppercase">
                            {{ $statusInfo['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-right text-green-700">
                        R$ {{ $priceDisplay }}
                    </td>

                    {{-- Célula de Pagamento - LÓGICA DE DATA E VALOR APLICADA AQUI --}}
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold">
                        <span class="px-2 inline-flex text-xs leading-5 rounded-full {{ $paymentStatusInfo['class'] }}">
                            {{ $paymentStatusInfo['label'] }}
                        </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        @if ($reserva->is_recurrent)
                            <span class="font-semibold text-fuchsia-600">Recorrente (Série #{{ $reserva->recurrent_series_id }})</span>
                        @else
                            Pontual
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.reservas.show', $reserva) }}"
                            class="inline-block text-center bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                            Detalhes
                        </a>
                        {{-- 🎯 CORREÇÃO CRÍTICA APLICADA AQUI: Adicionando o parâmetro 'date' --}}
                        <a href="{{ route('admin.payment.index', ['reserva_id' => $reserva->id, 'date' => $reservaDateUrl]) }}"
                            class="ml-2 inline-block text-center bg-green-500 hover:bg-green-600 text-white px-3 py-1 text-xs font-semibold rounded-md shadow transition duration-150">
                            Pagar
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    {{-- Colspan ajustado para 7 --}}
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                        Nenhuma reserva neste grupo.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
