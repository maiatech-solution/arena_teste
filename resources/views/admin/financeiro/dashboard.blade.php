<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            📊 Relatórios
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Filtros de Período -->
            <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="flex space-x-4">
                    <button onclick="carregarResumo('hoje')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition periodo-btn periodo-hoje">
                        Hoje
                    </button>
                    <button onclick="carregarResumo('semana')" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition periodo-btn periodo-semana">
                        Esta Semana
                    </button>
                    <button onclick="carregarResumo('mes')" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition periodo-btn periodo-mes">
                        Este Mês
                    </button>
                </div>
            </div>

            <!-- Cards de Resumo -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Card Total Recebido -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-500 text-white mr-4">
                                💰
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Recebido</p>
                                <p id="total-recebido-valor" class="text-2xl font-bold text-gray-900 dark:text-white">R$ 0,00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Sinais -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-500 text-white mr-4">
                                💳
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sinais Recebidos</p>
                                <p id="sinais-valor" class="text-2xl font-bold text-gray-900 dark:text-white">R$ 0,00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Reservas -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-500 text-white mr-4">
                                📋
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Reservas Confirmadas</p>
                                <p id="reservas-count" class="text-2xl font-bold text-gray-900 dark:text-white">0</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Pagamentos Pendentes -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                        💳 Pagamentos Pendentes
                    </h3>

                    <div id="loading-pendentes" class="text-center py-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-2 text-gray-500">Carregando...</p>
                    </div>

                    <div id="tabela-pendentes" class="hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Total</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sinal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pago</th>
                                        <th class="px-4- py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Restante</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-pendentes" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <!-- Dados carregados via JavaScript -->
                                </tbody>
                            </table>
                        </div>

                        <div id="sem-pendentes" class="hidden text-center py-8">
                            <p class="text-gray-500 dark:text-gray-400">🎉 Nenhum pagamento pendente!</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        let periodoAtual = 'hoje';

        // Carregar dados quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            carregarResumo('hoje');
            carregarPagamentosPendentes();
        });

        function carregarResumo(periodo) {
            periodoAtual = periodo;

            // Atualizar botões ativos
            document.querySelectorAll('.periodo-btn').forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            });

            document.querySelector(`.periodo-${periodo}`).classList.add('bg-blue-600', 'text-white');
            document.querySelector(`.periodo-${periodo}`).classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');

            // Fazer requisição
            fetch(`/api/financeiro/resumo?periodo=${periodo}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        atualizarCards(data.data);
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar resumo:', error);
                });
        }

        function atualizarCards(dados) {
    // Formatador de moeda
    const formatador = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });

    // Atualizar cards
    document.getElementById('total-recebido-valor').textContent = formatador.format(dados.total_recebido[periodoAtual]);
    document.getElementById('sinais-valor').textContent = formatador.format(dados.sinais[periodoAtual]);
    document.getElementById('reservas-count').textContent = dados.reservas[periodoAtual];
}

        function carregarPagamentosPendentes() {
            const loading = document.getElementById('loading-pendentes');
            const tabela = document.getElementById('tabela-pendentes');
            const semPendentes = document.getElementById('sem-pendentes');
            const tbody = document.getElementById('tbody-pendentes');

            fetch('/api/financeiro/pagamentos-pendentes')
                .then(response => response.json())
                .then(data => {
                    loading.classList.add('hidden');

                    if (data.success && data.data.length > 0) {
                        tabela.classList.remove('hidden');
                        semPendentes.classList.add('hidden');

                        tbody.innerHTML = data.data.map(reserva => `
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">${reserva.cliente}</div>
                                    <div class="text-xs text-gray-500">${reserva.contato}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    ${reserva.data}<br>
                                    <span class="text-gray-500">${reserva.horario}</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                    R$ ${reserva.valor_total.toFixed(2).replace('.', ',')}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-green-600 font-semibold">
                                    R$ ${reserva.sinal_pago.toFixed(2).replace('.', ',')}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-blue-600 font-semibold">
                                    R$ ${reserva.total_pago.toFixed(2).replace('.', ',')}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-red-600 font-bold">
                                    R$ ${reserva.valor_restante.toFixed(2).replace('.', ',')}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${reserva.cor_status}">
                                        ${reserva.status_pagamento_texto}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <a href="/admin/reservas/${reserva.id}/show" 
                                       class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                        Ver Detalhes
                                    </a>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tabela.classList.add('hidden');
                        semPendentes.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar pendentes:', error);
                    loading.innerHTML = '<p class="text-red-500">Erro ao carregar dados</p>';
                });
        }

        // Recarregar a cada 30 segundos
        setInterval(() => {
            carregarResumo(periodoAtual);
            carregarPagamentosPendentes();
        }, 30000);
    </script>
    @endpush
</x-app-layout>