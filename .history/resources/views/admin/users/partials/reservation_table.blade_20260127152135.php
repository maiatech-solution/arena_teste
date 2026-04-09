@php
    use Carbon\Carbon;
    $today = Carbon::now()->startOfDay();
@endphp

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Data</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Horário</th>
                {{-- 🏟️ NOVA COLUNA: ARENA --}}
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">Arena</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">Status</th>
                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">Preço</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[120px]">Pagamento</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[150px]">Tipo</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Ações</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            @forelse ($reservas as $reserva)
                @php
                    $reservaDate = Carbon::parse($reserva->date);
                    $totalPrice = $reserva->final_price ?? $reserva->price;
                    $totalPaid = $reserva->total_paid ?? 0;
                    $isFullyPaid = $reserva->is_paid ?? ($totalPaid >= $totalPrice);
                    $isConfirmed = in_array($reserva->status, ['confirmed', 'recurrent']);
                    
                    $statusInfo = match ($reserva->status) {
                        'confirmed' => ['label' => 'Confirmada', 'class' => 'bg-green-100 text-green-700'],
                        'pending' => ['label' => 'Aguardando', 'class' => 'bg-yellow-100 text-yellow-700'],
                        'canceled', 'rejected' => ['label' => 'Cancelada', 'class' => 'bg-red-100 text-red-700'],
                        'no_show' => ['label' => 'Falta', 'class' => 'bg-red-200 text-red-800 font-extrabold'],
                        default => ['label' => ucfirst($reserva->status), 'class' => 'bg-gray-100 text-gray-700'],
                    };

                    if (!$isConfirmed && $reserva->status !== 'pending') {
                        $pInfo = ['label' => 'N/A', 'class' => 'bg-gray-200 text-gray-700'];
                    } elseif ($isFullyPaid) {
                        $pInfo = ['label' => 'PAGA', 'class' => 'bg-green-100 text-green-700 font-bold'];
                    } elseif ($reservaDate->lt($today)) {
                        $pInfo = $totalPaid == 0 
                            ? ['label' => 'NÃO PAGO', 'class' => 'bg-red-200 text-red-800 font-extrabold'] 
                            : ['label' => 'ATRASADO', 'class' => 'bg-red-100 text-red-700'];
                    } else {
                        $pInfo = $totalPaid > 0 
                            ? ['label' => 'Parcial', 'class' => 'bg-blue-100 text-blue-700'] 
                            : ['label' => 'Pendente', 'class' => 'bg-yellow-100 text-yellow-700'];
                    }
                @endphp
                <tr class="odd:bg-white even:bg-gray-50 hover:bg-gray-100 transition duration-150">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ $reservaDate->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">{{ substr($reserva->start_time, 0, 5) }} - {{ substr($reserva->end_time, 0, 5) }}</td>
                    
                    {{-- 🏟️ CÉLULA DA ARENA --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-[10px] font-bold px-2 py-1 rounded bg-indigo-50 text-indigo-700 border border-indigo-100 uppercase">
                            {{ $reserva->arena->name ?? 'N/A' }}
                        </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 rounded-full {{ $statusInfo['class'] }} uppercase">{{ $statusInfo['label'] }}</span></td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-right text-green-700">R$ {{ number_format($totalPrice, 2, ',', '.') }}</td>
                    <td class="px-4 py-3 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 rounded-full {{ $pInfo['class'] }}">{{ $pInfo['label'] }}</span></td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {!! $reserva->is_recurrent ? '<span class="font-semibold text-fuchsia-600">Série #'.$reserva->recurrent_series_id.'</span>' : 'Pontual' !!}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('admin.reservas.show', $reserva) }}" class="bg-indigo-600 text-white px-2 py-1 text-[10px] rounded shadow">Detalhes</a>
                        <a href="{{ route('admin.payment.index', ['reserva_id' => $reserva->id, 'date' => $reservaDate->format('Y-m-d')]) }}" class="ml-1 bg-green-500 text-white px-2 py-1 text-[10px] rounded shadow">Pagar</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500 italic">Nenhuma reserva neste grupo.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>