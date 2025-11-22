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
                <tr class="odd:bg-white even:bg-gray-50 hover:bg-gray-100 transition duration-150">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ \Carbon\Carbon::parse($reserva->date)->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                        {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold">
                        @php
                            // Lógica de Status da Reserva (Mantida do passo anterior, incluindo 'no_show')
                            $statusInfo = match ($reserva->status) {
                                'confirmed' => ['label' => 'Confirmada', 'class' => 'bg-green-100 text-green-700'],
                                'pending' => ['label' => 'Aguardando', 'class' => 'bg-yellow-100 text-yellow-700'],
                                'cancelled' => ['label' => 'Cancelada', 'class' => 'bg-red-100 text-red-700'],
                                'rejected' => ['label' => 'Rejeitada', 'class' => 'bg-red-100 text-red-700'],
                                'expired' => ['label' => 'Expirada', 'class' => 'bg-gray-300 text-gray-700'],
                                'no_show' => ['label' => 'Falta (Retido)', 'class' => 'bg-red-200 text-red-800 font-extrabold'],
                                default => ['label' => 'Desconhecido', 'class' => 'bg-gray-100 text-gray-700'],
                            };

                            // Lógica de Status de Pagamento (Adaptada ao seu Reserva Model)
                            $paymentStatusInfo = ['label' => 'Aguardando Pagto', 'class' => 'bg-yellow-100 text-yellow-700']; // Padrão
                            
                            if ($reserva->is_paid) {
                                // Se o acessor is_paid for true (total_paid >= final_price/price)
                                $paymentStatusInfo = ['label' => 'Paga', 'class' => 'bg-green-100 text-green-700'];
                            } elseif ($reserva->payment_status === 'overdue' || $reserva->payment_status === 'expired') {
                                // Se o status for explicitamente 'overdue' (Atrasada)
                                $paymentStatusInfo = ['label' => 'Atrasada', 'class' => 'bg-red-100 text-red-700 font-bold'];
                            } elseif ($reserva->total_paid > 0 && $reserva->remaining_amount > 0) {
                                // Se pagou algo, mas ainda deve
                                $paymentStatusInfo = ['label' => 'Parcialmente Paga', 'class' => 'bg-blue-100 text-blue-700'];
                            }
                            // Caso contrário, fica com o padrão 'Aguardando Pagto'
                        @endphp
                        <span class="px-2 inline-flex text-xs leading-5 rounded-full {{ $statusInfo['class'] }} uppercase">
                            {{ $statusInfo['label'] }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-right text-green-700">
                        R$ {{ number_format($reserva->final_price ?? $reserva->price, 2, ',', '.') }}
                    </td>
                    
                    {{-- Célula de Pagamento --}}
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
                    </td>
                </tr>
            @empty
                <tr>
                    {{-- O colspan aumentou para 7 por causa da nova coluna --}}
                    <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                        Nenhuma reserva neste grupo.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>