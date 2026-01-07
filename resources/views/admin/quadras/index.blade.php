<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            🏟️ Gerenciar Quadras e Arenas
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Alertas de Sucesso --}}
            @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow" role="alert">
                <p class="font-bold">Sucesso!</p>
                <p>{{ session('success') }}</p>
            </div>
            @endif

            {{-- Formulário para Adicionar Nova Arena --}}
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md mb-8 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Cadastrar Nova Quadra</h3>
                <form action="{{ route('admin.arenas.store') }}" method="POST" class="flex flex-col md:flex-row items-end gap-4">
                    @csrf
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome da Arena/Quadra</label>
                        <input type="text" name="name" placeholder="Ex: Quadra de Tênis 01" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white p-2 border">
                    </div>
                    <button type="submit" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md font-bold transition duration-150">
                        + Adicionar
                    </button>
                </form>
            </div>

            {{-- Lista de Arenas Cadastradas --}}
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-6">Arenas Ativas</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($arenas as $arena)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-indigo-500">
                    <div class="p-6">
                        <h4 class="text-xl font-bold">{{ $arena->name }}</h4>

                        <div class="mt-4 flex flex-col gap-2">
                            {{-- Esse botão leva para a configuração de horários --}}
                            <a href="{{ route('admin.config.index', $arena->id) }}"
                                class="text-center bg-indigo-600 text-white py-2 rounded font-bold">
                                ⚙️ Configurar Horários de Funcionamento
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full bg-yellow-50 p-4 rounded-md text-yellow-700 border border-yellow-200 text-center">
                    Nenhuma arena cadastrada ainda. Use o formulário acima para começar.
                </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>