<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-4">
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Controle de <span class="text-orange-600">Mesas</span>
                </h1>
                <p class="text-gray-500 font-medium italic">Gerencie o salão e a ocupação em tempo real.</p>
            </div>

            <div class="flex gap-3">
                <button onclick="document.getElementById('modalSync').classList.remove('hidden')"
                    class="px-6 py-3 bg-gray-800 text-white font-black rounded-2xl border border-gray-700 hover:bg-gray-700 transition-all uppercase text-[10px] tracking-widest shadow-lg">
                    ⚙️ Configurar Salão
                </button>
            </div>
        </div>

        @if (session('error'))
            <div
                class="bg-red-600 text-white p-4 rounded-2xl mb-6 font-black uppercase text-[10px] tracking-widest animate-pulse">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-600 text-white p-4 rounded-2xl mb-6 font-black uppercase text-[10px] tracking-widest">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
            @foreach ($tables as $table)
                <div class="relative group">
                    <div
                        class="p-6 rounded-[2.5rem] border-2 transition-all duration-300 flex flex-col items-center justify-center min-h-[160px]
                        {{ $table->status == 'available' ? 'border-gray-800 bg-gray-900/40 hover:border-green-500/50' : '' }}
                        {{ $table->status == 'occupied' ? 'border-orange-600 bg-orange-600/10 shadow-lg shadow-orange-600/10' : '' }}
                        {{ $table->status == 'reserved' ? 'border-black bg-black opacity-30 grayscale' : '' }}">

                        <span
                            class="text-3xl mb-2 {{ $table->status == 'occupied' ? 'text-orange-500' : 'text-gray-600' }}">🪑</span>
                        <h3 class="text-xl font-black text-white">Mesa
                            {{ str_pad($table->identifier, 2, '0', STR_PAD_LEFT) }}</h3>

                        <div class="mt-4 text-center">
                            @if ($table->status == 'available')
                                <span
                                    class="text-[9px] font-black text-green-500 uppercase tracking-widest">Livre</span>
                                <form action="{{ route('bar.tables.open', $table->id) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="block mt-2 text-[10px] bg-white text-black px-4 py-1.5 rounded-full font-black hover:bg-orange-500 hover:text-white transition-colors uppercase">
                                        Abrir
                                    </button>
                                </form>
                            @elseif($table->status == 'occupied')
                                <span
                                    class="text-[9px] font-black text-orange-500 uppercase tracking-widest animate-pulse">Ocupada</span>
                                <a href="{{ route('bar.tables.show', $table->id) }}"
                                    class="block mt-2 text-[10px] bg-orange-600 text-white px-4 py-1.5 rounded-full font-black uppercase tracking-tighter shadow-lg shadow-orange-600/20">
                                    Comanda
                                </a>
                            @else
                                <span
                                    class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Reservada</span>
                                <p class="text-[8px] text-gray-600 font-bold mt-1 uppercase">Indisponível</p>
                            @endif
                        </div>

                        <form action="{{ route('bar.tables.toggle', $table->id) }}" method="POST"
                            class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                            @csrf
                            <button type="submit" class="text-xs filter hover:brightness-150"
                                title="Alternar Disponibilidade">
                                {{ $table->status == 'reserved' ? '✅' : '🚫' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div id="modalSync"
        class="hidden fixed inset-0 bg-black/95 backdrop-blur-md z-[200] flex items-center justify-center p-4">
        <div class="bg-gray-900 border border-gray-800 p-8 rounded-[2.5rem] w-full max-w-sm text-center">
            <h3 class="text-xl font-black text-white uppercase italic mb-6">Total de Mesas</h3>
            <form action="{{ route('bar.tables.sync') }}" method="POST">
                @csrf
                <input type="number" name="total_tables" value="{{ $tables->count() }}"
                    class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white p-4 text-center text-3xl font-black mb-6 outline-none focus:border-orange-600">
                <div class="flex gap-3">
                    <button type="button" onclick="this.closest('#modalSync').classList.add('hidden')"
                        class="flex-1 text-gray-400 font-black uppercase text-[10px] hover:text-white transition-colors">Voltar</button>
                    <button type="submit"
                        class="flex-1 py-4 bg-orange-600 text-white rounded-2xl font-black uppercase text-[10px] shadow-lg shadow-orange-600/20 active:scale-95 transition-all">Salvar
                        Layout</button>
                </div>
            </form>
        </div>
    </div>
</x-bar-layout>
