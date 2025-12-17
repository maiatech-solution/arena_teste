<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📊 Central de Inteligência Financeira
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 p-6 rounded-2xl shadow-lg text-white">
                    <p class="text-xs opacity-80 uppercase font-bold tracking-widest">Receita Bruta (Mês Atual)</p>
                    <p class="text-3xl font-black mt-1">R$ {{ number_format($faturamentoMensal, 2, ',', '.') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <p class="text-xs text-gray-500 uppercase font-bold">Ocupação do Mês</p>
                    <p class="text-3xl font-bold dark:text-white mt-1">{{ $totalReservasMes }} <span class="text-sm font-normal text-gray-400 font-sans">Reservas</span></p>
                </div>
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 font-sans">
                    <p class="text-xs text-gray-500 uppercase font-bold">Perda por Cancelamento</p>
                    <p class="text-3xl font-bold text-red-500 mt-1">{{ $canceladasMes }} <span class="text-sm font-normal text-gray-400">Ocorrências</span></p>
                </div>
            </div>



            <hr class="border-gray-200 dark:border-gray-700">

            <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Relatórios Detalhados</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">

                <a href="#" class="flex items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border-2 border-transparent hover:border-indigo-500 transition-all group">
                    <div class="p-4 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 rounded-xl mr-5">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold dark:text-white">Relatório de Faturamento</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Lucros, métodos de pagamento e impostos.</p>
                    </div>
                </a>

                <a href="{{ route('admin.payment.index') }}" class="flex items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border-2 border-transparent hover:border-green-500 transition-all group">
                    <div class="p-4 bg-green-100 dark:bg-green-900/30 text-green-600 rounded-xl mr-5">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold dark:text-white">Fechamento de Caixa</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Histórico diário e conferência física.</p>
                    </div>
                </a>

                <a href="{{ route('admin.reservas.rejeitadas') }}" class="flex items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border-2 border-transparent hover:border-red-500 transition-all group">
                    <div class="p-4 bg-red-100 dark:bg-red-900/30 text-red-600 rounded-xl mr-5">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold dark:text-white">Cancelamentos & No-Show</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Motivos de desistência e taxas de falta.</p>
                    </div>
                </a>

                <a href="{{ route('admin.reservas.confirmadas') }}" class="flex items-center p-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border-2 border-transparent hover:border-blue-500 transition-all group">
                    <div class="p-4 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-xl mr-5">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold dark:text-white">Reservas Confirmadas</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Próximos jogos e mapa de ocupação.</p>
                    </div>
                </a>

            </div>
        </div>
    </div>
</x-app-layout>
