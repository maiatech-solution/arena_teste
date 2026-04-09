@php
    use Carbon\Carbon;
    $today = Carbon::now()->startOfDay();
@endphp

<div class="overflow-x-auto shadow-sm rounded-lg border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th
                    class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest min-w-[100px]">
                    Data</th>
                <th
                    class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest min-w-[100px]">
                    Horário</th>
                <th
                    class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest min-w-[120px]">
                    Arena</th>
                <th
                    class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest min-w-[90px]">
                    Status</th>
                <th
                    class="px-4 py-3 text-right text-[10px] font-black text-gray-500 uppercase tracking-widest min-w-[90px]">
                    Preço</th>
                <th
                    class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest min-w-[130px]">
                    Pagamento</th>
                <th
                    class="px-4 py-3 text-left text-[10px] font-black text-gray-500 uppercase tracking-widest min-w-[150px]">
                    Tipo</th>
                <th
                    class="px-4 py-3 text-center text-[10px] font-black text-gray-500 uppercase tracking-widest min-w-[120px]">
                    Ações</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100 italic">
            @forelse ($reservas as $reserva)
                @php
                    $reservaDate = Carbon::parse($reserva->date);
                    $totalPrice = (float) ($reserva->final_price ?? $reserva->price);
                    $totalPaid = (float) ($reserva->total_paid ?? 0);
                    $isFullyPaid = $reserva->is_paid ?? $totalPaid >= $totalPrice;

                    // Lógica de Status da Reserva
                    $statusInfo = match ($reserva->status) {
                        'confirmed' => ['label' => 'Confirmada', 'class' => 'bg-green-100 text-green-700'],
                        'completed' => ['label' => 'Concluída', 'class' => 'bg-blue-100 text-blue-700'],
                        'pending' => ['label' => 'Aguardando', 'class' => 'bg-yellow-100 text-yellow-700'],
                        'maintenance' => ['label' => 'Manutenção', 'class' => 'bg-gray-200 text-gray-600'],
                        'canceled', 'rejected' => ['label' => 'Cancelada', 'class' => 'bg-red-100 text-red-700'],
                        'no_show' => ['label' => 'Falta', 'class' => 'bg-red-200 text-red-800 font-black'],
                        default => ['label' => ucfirst($reserva->status), 'class' => 'bg-gray-100 text-gray-700'],
                    };

                    // Lógica de Status do Pagamento (PAGA, PENDENTE, ATRASADO)
                    if ($reserva->status === 'maintenance' || ($reserva->status === 'rejected' && $totalPaid == 0)) {
                        $pInfo = ['label' => 'N/A', 'class' => 'bg-gray-100 text-gray-400'];
                    } elseif ($isFullyPaid) {
                        $pInfo = ['label' => 'PAGA', 'class' => 'bg-green-500 text-white font-black'];
                    } elseif ($reservaDate->lt($today)) {
                        $pInfo =
                            $totalPaid == 0
                                ? ['label' => 'NÃO PAGO', 'class' => 'bg-red-600 text-white font-black']
                                : ['label' => 'DÉBITO PARCIAL', 'class' => 'bg-red-100 text-red-700'];
                    } else {
                        $pInfo =
                            $totalPaid > 0
                                ? ['label' => 'PARCIAL', 'class' => 'bg-blue-100 text-blue-700']
                                : ['label' => 'PENDENTE', 'class' => 'bg-yellow-100 text-yellow-700'];
                    }
                @endphp
                <tr class="odd:bg-white even:bg-gray-50/50 hover:bg-indigo-50/30 transition duration-150">
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900">
                        {{ $reservaDate->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600 font-mono">
                        {{ substr($reserva->start_time, 0, 5) }} - {{ substr($reserva->end_time, 0, 5) }}
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span
                            class="text-[10px] font-black px-2 py-1 rounded bg-white border border-gray-200 text-gray-600 uppercase">
                            {{ $reserva->arena->name ?? 'N/A' }}
                        </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap">
                        <span
                            class="px-2 py-0.5 inline-flex text-[10px] leading-5 font-black rounded-full {{ $statusInfo['class'] }} uppercase shadow-sm">
                            {{ $statusInfo['label'] }}
                        </span>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-sm font-black text-right text-gray-700">
                        R$ {{ number_format($totalPrice, 2, ',', '.') }}
                    </td>

                    {{-- 💰 COLUNA DE PAGAMENTO: STATUS + FORMAS UTILIZADAS --}}
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex flex-col gap-1">
                            <span
                                class="px-2 py-0.5 inline-flex text-[9px] leading-4 rounded font-black {{ $pInfo['class'] }} w-fit shadow-sm">
                                {{ $pInfo['label'] }}
                            </span>

                            @if ($reserva->transactions && $reserva->transactions->count() > 0)
                                <div class="flex flex-wrap gap-1 mt-0.5">
                                    @foreach ($reserva->transactions->pluck('payment_method')->unique() as $method)
                                        @php
                                            $methodStyle = match (strtolower($method)) {
                                                'pix' => 'bg-teal-50 text-teal-700 border-teal-200',
                                                'money', 'dinheiro' => 'bg-green-50 text-green-700 border-green-200',
                                                'card',
                                                'cartao',
                                                'credito',
                                                'debito'
                                                    => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'voucher' => 'bg-amber-100 text-amber-800 border-amber-300',
                                                default => 'bg-gray-50 text-gray-600 border-gray-200',
                                            };
                                        @endphp
                                        <span
                                            class="px-1 py-0.5 border text-[8px] font-black rounded uppercase {{ $methodStyle }}">
                                            {{ $method }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                        @if ($reserva->is_recurrent)
                            <span class="font-black text-fuchsia-600 uppercase tracking-tighter">Série
                                #{{ $reserva->recurrent_series_id }}</span>
                        @else
                            <span class="text-gray-400 font-medium">Pontual</span>
                        @endif
                    </td>

                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-medium">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.reservas.show', $reserva) }}"
                                class="bg-gray-800 text-white px-3 py-1 text-[10px] font-black rounded hover:bg-black transition shadow-sm uppercase">
                                Detalhes
                            </a>

                            @if (!$isFullyPaid && !in_array($reserva->status, ['canceled', 'rejected', 'maintenance']))
                                <a href="{{ route('admin.payment.index', ['reserva_id' => $reserva->id, 'date' => $reservaDate->format('Y-m-d')]) }}"
                                    class="bg-green-600 text-white px-3 py-1 text-[10px] font-black rounded hover:bg-green-700 transition shadow-sm uppercase">
                                    Pagar
                                </a>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8"
                        class="px-6 py-12 text-center text-gray-400 font-bold uppercase text-xs tracking-widest italic">
                        Nenhuma reserva encontrada para este cliente.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
