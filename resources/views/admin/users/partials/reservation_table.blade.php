<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Data</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[100px]">Horário</th>
                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">Status</th>
                <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider min-w-[90px]">Preço</th>
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
                            $colorClass = match ($reserva->status) {
                                'confirmed' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'cancelled', 'rejected' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <span class="px-2 inline-flex text-xs leading-5 rounded-full {{ $colorClass }} uppercase">
                            {{ $reserva->status_text }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-right text-green-700">
                        R$ {{ number_format($reserva->price, 2, ',', '.') }}
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
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 italic">
                        Nenhuma reserva neste grupo.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
