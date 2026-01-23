<x-app-layout>
    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Cabeçalho --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">
                        RELATÓRIO DE <span class="text-amber-600">DÍVIDAS</span>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 font-medium">
                        Gerencie todos os valores autorizados para "Pagar Depois".
                    </p>
                </div>

                {{-- Card de Totalizador Geral --}}
                <div class="mt-4 md:mt-0 bg-amber-600 text-white px-6 py-4 rounded-2xl shadow-lg shadow-amber-200 dark:shadow-none flex flex-col items-end">
                    <span class="text-[10px] font-bold uppercase tracking-widest opacity-80">Total em Aberto</span>
                    <span class="text-2xl font-black">R$ {{ number_format($totalGlobalDividas, 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- Filtros e Navegação Multi-Quadra --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-2 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-4">

                    {{-- Tabs de Arenas --}}
                    <nav class="flex space-x-1 bg-gray-100 dark:bg-gray-700 p-1 rounded-xl">
                        <a href="{{ route('admin.financeiro.relatorio_dividas', ['arena_id' => '']) }}"
                           class="px-4 py-2 text-xs font-bold rounded-lg transition {{ !request('arena_id') ? 'bg-white dark:bg-gray-600 text-amber-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            TODAS
                        </a>
                        @foreach($arenas as $arena)
                            <a href="{{ route('admin.financeiro.relatorio_dividas', ['arena_id' => $arena->id]) }}"
                               class="px-4 py-2 text-xs font-bold rounded-lg transition {{ request('arena_id') == $arena->id ? 'bg-white dark:bg-gray-600 text-amber-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                                {{ strtoupper($arena->name) }}
                            </a>
                        @endforeach
                    </nav>

                    {{-- Busca por Nome --}}
                    <form method="GET" action="{{ route('admin.financeiro.relatorio_dividas') }}" class="flex-1 max-w-sm">
                        <input type="hidden" name="arena_id" value="{{ request('arena_id') }}">
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Buscar devedor..."
                                   class="w-full pl-10 pr-4 py-2 rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:ring-amber-500 focus:border-amber-500">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabela de Dívidas --}}
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Data / Arena</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Devedor</th>
                            <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Valor Original</th>
                            <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-green-600">Já Pago</th>
                            <th class="px-6 py-4 text-right text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest text-red-600">Restante</th>
                            <th class="px-6 py-4 text-center text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($dividas as $item)
                            @php
                                $totalReserva = $item->final_price ?? $item->price;
                                $restante = $totalReserva - $item->total_paid;
                                $whatsappLink = "https://wa.me/55" . preg_replace('/[^0-9]/', '', $item->client_contact) . "?text=" . urlencode("Olá " . $item->client_name . ", estamos entrando em contato referente à pendência de R$ " . number_format($restante, 2, ',', '.') . " do jogo realizado em " . \Carbon\Carbon::parse($item->date)->format('d/m') . ". Como podemos proceder com o acerto?");
                            @endphp
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors text-gray-700 dark:text-gray-300">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($item->date)->format('d/m/Y') }}</div>
                                    <div class="text-[10px] text-amber-600 font-black uppercase">{{ $item->arena->name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $item->client_name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->client_contact }}</div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium">R$ {{ number_format($totalReserva, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-green-600">R$ {{ number_format($item->total_paid, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right text-sm font-black text-red-600">R$ {{ number_format($restante, 2, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center flex justify-center gap-2">
                                    {{-- Botão WhatsApp --}}
                                    <a href="{{ $whatsappLink }}" target="_blank" class="p-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition" title="Cobrar via WhatsApp">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.672 1.433 5.662 1.433h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    </a>

                                    {{-- Botão Quitar (Redireciona para o Caixa Diário) --}}
                                    <a href="{{ route('admin.payment.index', ['reserva_id' => $item->id, 'arena_id' => $item->arena_id, 'date' => $item->date]) }}" class="px-3 py-2 bg-amber-600 text-white rounded-lg text-xs font-black hover:bg-amber-700 transition">
                                        QUITAR DÍVIDA
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">
                                    Não existem dívidas pendentes para os filtros selecionados.
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
