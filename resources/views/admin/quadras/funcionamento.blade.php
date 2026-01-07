<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            ⚙️ Funcionamento e Horários
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <p class="text-gray-600 dark:text-gray-400">Selecione uma quadra abaixo para gerenciar os horários de funcionamento e preços.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @forelse($arenas as $arena)
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg rounded-xl border-b-4 border-indigo-500 hover:shadow-2xl transition-all duration-300">
                        <div class="p-8 text-center">
                            <div class="inline-flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 text-indigo-600 mb-4">
                                <span class="text-2xl">🏟️</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $arena->name }}</h3>
                            <p class="text-sm text-gray-500 mb-6">Configuração de horários semanais e preços por faixa.</p>
                            
                            <a href="{{ route('admin.config.index', $arena->id) }}" 
                               class="inline-block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-150">
                                Gerenciar Horários
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full bg-white dark:bg-gray-800 p-12 text-center rounded-xl shadow">
                        <p class="text-gray-500">Nenhuma quadra cadastrada.</p>
                        <a href="{{ route('admin.arenas.index') }}" class="text-indigo-600 font-bold hover:underline mt-2 inline-block">Clique aqui para cadastrar a primeira</a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>