<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- Botão Voltar preservando o filtro de arena --}}
                <a href="{{ route('admin.financeiro.dashboard', ['arena_id' => request('arena_id')]) }}"
                    class="flex items-center gap-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-all font-bold text-sm">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Painel
                </a>
                <h2 class="font-black text-xl text-gray-800 dark:text-gray-200 uppercase tracking-tighter italic">
                    ⚠️ Cobrança:
                    {{ request('arena_id') ? \App\Models\Arena::find(request('arena_id'))?->name : 'Todas as Unidades' }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- 🔍 FILTROS (Padrão Auditoria) --}}
            <div class="bg-white dark:bg-gray-800 p-6 shadow-sm rounded-xl border border-orange-100 dark:border-orange-900/30 print:hidden">
                <form id="debtFilterForm" method="GET" action="{{ route('admin.financeiro.relatorio_dividas') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1 italic">🏟️ Unidade</label>
                        <select name="arena_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-orange-500 font-bold text-sm">
                            <option value="">Todas as Arenas</option>
                            @foreach($arenas as $arena)
                                <option value="{{ $arena->id }}" {{ request('arena_id') == $arena->id ? 'selected' : '' }}>{{ $arena->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1 ml-1 italic">🔍 Buscar Cliente</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome ou telefone..."
                               class="w-full rounded-lg border-gray-300 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:ring-orange-500 font-bold text-sm">
                    </div>

                    <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg font-bold hover:bg-black transition shadow-md text-sm uppercase">
                        🔎 Filtrar
                    </button>
                </form>
            </div>

            {{-- 📊 RESUMO FINANCEIRO (Cards) --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border-l-4 border-orange-500 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase italic">Inadimplência Geral</p>
                    <p class="text-2xl font-black text-orange-600 italic">R$ {{ number_format($totalGlobalDividas, 2, ',', '.') }}</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border-l-4 border-gray-400 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase italic">Total de Devedores</p>
                    <p class="text-2xl font-black text-gray-700 dark:text-gray-200 italic">{{ $dividas->total() }} Clientes</p>
                </div>

                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border-l-4 border-green-500 shadow-sm">
                    <p class="text-[10px] font-black text-gray-400 uppercase italic">Meta de Recuperação</p>
                    <p class="text-2xl font-black text-green-600 italic">100%</p>
                </div>
            </div>

            {{-- 📄 TABELA DE DÍVIDAS --}}
            <div id="reportContent" class="bg-white dark:bg-gray-800 p-8 shadow-lg rounded-xl border border-gray-100 dark:border-gray-700">

                <div class="flex justify-between items-start border-b-2 border-orange-50 dark:border-orange-900/20 pb-6 mb-8 italic">
                    <div>
                        <h1 class="text-3xl font-black text-gray-800 dark:text-white uppercase tracking-tighter">Listagem de Devedores</h1>
                        <p class="text-gray-500 text-sm font-bold uppercase mt-1">Status: Pendente ou Parcial</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest block mb-1">Total Pendente</span>
                        <span class="text-4xl font-black text-orange-600">R$ {{ number_format($totalGlobalDividas, 2, ',', '.') }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 text-[10px] font-black uppercase tracking-widest italic">
                                <th class="p-4 rounded-l-lg">Data / Arena</th>
                                <th class="p-4">Cliente / Contato</th>
                                <th class="p-4 text-right">Valor Jogo</th>
                                <th class="p-4 text-right text-green-600">Total Pago</th>
                                <th class="p-4 text-right text-red-600">Restante</th>
                                <th class="p-4 rounded-r-lg text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y dark:divide-gray-700 font-bold">
                            @forelse($dividas as $item)
                                @php
                                    $totalReserva = $item->final_price ?? $item->price;
                                    $restante = $totalReserva - $item->total_paid;
                                    $whatsappLink = "https://wa.me/55" . preg_replace('/[^0-9]/', '', $item->client_contact) . "?text=" . urlencode("Olá " . $item->client_name . ", tudo bem? Constatamos aqui uma pendência de R$ " . number_format($restante, 2, ',', '.') . " referente ao agendamento do dia " . \Carbon\Carbon::parse($item->date)->format('d/m') . ". Como podemos proceder?");
                                @endphp
                                <tr class="hover:bg-orange-50/30 dark:hover:bg-orange-900/10 transition duration-150">
                                    <td class="p-4">
                                        <div class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</div>
                                        <div class="text-[10px] text-orange-600 font-black uppercase italic">{{ $item->arena->name }}</div>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-black dark:text-white uppercase">{{ $item->client_name }}</div>
                                        <div class="text-[10px] text-indigo-500 font-mono italic">{{ $item->client_contact }}</div>
                                    </td>
                                    <td class="p-4 text-right">R$ {{ number_format($totalReserva, 2, ',', '.') }}</td>
                                    <td class="p-4 text-right text-green-600">R$ {{ number_format($item->total_paid, 2, ',', '.') }}</td>
                                    <td class="p-4 text-right text-red-600 font-black italic">R$ {{ number_format($restante, 2, ',', '.') }}</td>
                                    <td class="p-4 text-center">
                                        <div class="flex justify-center gap-2">
                                            <a href="{{ $whatsappLink }}" target="_blank" class="p-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.672 1.433 5.662 1.433h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                            </a>
                                            <a href="{{ route('admin.payment.index', ['reserva_id' => $item->id, 'arena_id' => $item->arena_id, 'date' => $item->date]) }}"
                                               class="px-4 py-2 bg-orange-600 text-white rounded-lg text-xs font-black hover:bg-orange-700 transition shadow-sm uppercase">
                                                Quitar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-10 text-center text-gray-400 italic font-bold uppercase text-xs">
                                        Nenhuma dívida encontrada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>


            {{-- Paginação --}}
            <div class="mt-6">
                {{ $dividas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
