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

                <a href="#" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-500 transition-all duration-200">
                    <div class="flex items-center mb-4 text-indigo-600 dark:text-indigo-400">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span class="font-bold text-lg">Faturamento</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Análise detalhada de entradas, lucros, meios de pagamento e evolução mensal.</p>
                </a>

                <a href="#" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-green-500 dark:hover:border-green-500 transition-all duration-200">
                    <div class="flex items-center mb-4 text-green-600 dark:text-green-400">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                        <span class="font-bold text-lg">Fechamento de Caixa</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Histórico de fechamentos diários, conferência de valores físicos e diferenças.</p>
                </a>

                <a href="#" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-500 transition-all duration-200">
                    <div class="flex items-center mb-4 text-blue-600 dark:text-blue-400">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="font-bold text-lg">Reservas Confirmadas</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Lista completa de agendamentos futuros, ocupação de quadras e horários nobres.</p>
                </a>

                <a href="#" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-red-500 dark:hover:border-red-500 transition-all duration-200">
                    <div class="flex items-center mb-4 text-red-600 dark:text-red-400">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="font-bold text-lg">Cancelamentos & Faltas</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Análise de No-Show, motivos de cancelamento e taxas de retenção de sinal.</p>
                </a>

                <a href="#" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-purple-500 dark:hover:border-purple-500 transition-all duration-200">
                    <div class="flex items-center mb-4 text-purple-600 dark:text-purple-400">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="font-bold text-lg">Ranking de Clientes</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Descubra quem são seus clientes mais fiéis e os que geram mais receita.</p>
                </a>

                <a href="{{ route('admin.financeiro.getPagamentosPendentes') }}" class="group bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 hover:border-yellow-500 dark:hover:border-yellow-500 transition-all duration-200 text-left">
                    <div class="flex items-center mb-4 text-yellow-600 dark:text-yellow-400">
                        <svg class="w-8 h-8 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span class="font-bold text-lg">Contas a Receber</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Todas as reservas futuras ou passadas que ainda possuem saldo devedor.</p>
                </a>

            </div>

        </div>
    </div>
</x-app-layout>
