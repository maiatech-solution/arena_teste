<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📊 Central de Relatórios e Inteligência
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-10 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 p-4 rounded-lg">
                    <p class="text-xs text-indigo-500 uppercase font-bold">Faturamento ({{ now()->translatedFormat('M') }})</p>
                    <p class="text-xl font-bold dark:text-white">R$ {{ number_format($faturamentoMensal, 2, ',', '.') }}</p>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 p-4 rounded-lg">
                    <p class="text-xs text-green-500 uppercase font-bold">Reservas Ativas</p>
                    <p class="text-xl font-bold dark:text-white">{{ $totalReservasMes }}</p>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800 p-4 rounded-lg">
                    <p class="text-xs text-red-500 uppercase font-bold">Cancelamentos</p>
                    <p class="text-xl font-bold dark:text-white">{{ $canceladasMes }}</p>
                </div>
            </div>

            <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-6 uppercase tracking-wider">Selecione o Relatório</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

    <a href="{{ route('admin.financeiro.dashboard') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-indigo-500 transition-all duration-200">
        <div class="flex items-center mb-4 text-indigo-600 dark:text-indigo-400">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            <span class="font-bold text-lg">Faturamento</span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Análise de entradas, lucros e evolução mensal.</p>
    </a>

    <a href="{{ route('admin.payment.index') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-green-500 transition-all duration-200">
        <div class="flex items-center mb-4 text-green-600 dark:text-green-400">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
            <span class="font-bold text-lg">Movimentação Diária</span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Conferência de valores, entradas e saídas do dia.</p>
    </a>

    <a href="{{ route('admin.reservas.confirmadas') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-blue-500 transition-all duration-200">
        <div class="flex items-center mb-4 text-blue-600 dark:text-blue-400">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="font-bold text-lg">Agendas Confirmadas</span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Lista completa de agendamentos que estão para acontecer.</p>
    </a>

    <a href="{{ route('admin.reservas.rejeitadas') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-red-500 transition-all duration-200">
        <div class="flex items-center mb-4 text-red-600 dark:text-red-400">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-bold text-lg">Cancelamentos</span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Análise de No-Show e reservas rejeitadas.</p>
    </a>

    <a href="{{ route('api.financeiro.pagamentos-pendentes') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-yellow-500 transition-all duration-200 text-left">
        <div class="flex items-center mb-4 text-yellow-600 dark:text-yellow-400">
            <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            <span class="font-bold text-lg">Contas a Receber</span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">Listagem técnica de saldos devedores (JSON).</p>
    </a>

</div>

        </div>
    </div>
</x-app-layout>
