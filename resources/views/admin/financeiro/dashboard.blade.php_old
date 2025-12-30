<x-app-layout>
<x-slot name="header">
<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
📊 Relatórios Financeiros
</h2>
</x-slot>

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Filtros de Período -->
        <div class="mb-6 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700" id="financeiro-filters">
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Visualizar dados de:</h3>
            <div class="flex space-x-4">
                <!-- Botões de filtro -->
                <button data-periodo="hoje" class="px-4 py-2 text-white rounded-lg hover:bg-blue-700 transition periodo-btn periodo-hoje bg-blue-600">
                    Hoje
                </button>
                <button data-periodo="semana" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition periodo-btn periodo-semana">
                    Esta Semana
                </button>
                <button data-periodo="mes" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition periodo-btn periodo-mes">
                    Este Mês
                </button>
            </div>
        </div>

        <!-- Cards de Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Card Total Recebido -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-xl border border-green-100 dark:border-green-900/50">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-500 text-white mr-4">💰</div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Recebido</p>
                            <p id="total-recebido-valor" class="text-2xl font-bold text-gray-900 dark:text-white">
                                <span class="animate-pulse">Carregando...</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Sinais -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-xl border border-blue-100 dark:border-blue-900/50">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-500 text-white mr-4">💳</div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sinais Recebidos</p>
                            <p id="sinais-valor" class="text-2xl font-bold text-gray-900 dark:text-white">
                                <span class="animate-pulse">Carregando...</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Reservas -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-xl border border-purple-100 dark:border-purple-900/50">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-500 text-white mr-4">📋</div>
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Reservas Confirmadas</p>
                            <p id="reservas-count" class="text-2xl font-bold text-gray-900 dark:text-white">
                                <span class="animate-pulse">Carregando...</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Pagamentos Pendentes -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-lg sm:rounded-xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white flex justify-between items-center">
                    <span>💳 Próximos Pagamentos Pendentes</span>
                    <button onclick="carregarPagamentosPendentes()" title="Recarregar Dados" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-500 transition">
                        <!-- Ícone de recarregar -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 15m15.356-2H21m-3-4h.582m15.356 2A8.001 8.001 0 014.582 7m15.356 2H21"></path></svg>
                    </button>
                </h3>

                <div id="loading-pendentes" class="text-center py-4">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-2 text-gray-500 dark:text-gray-400">Carregando...</p>
                </div>

                <div id="tabela-pendentes" class="hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Data/Hora</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor Total</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Sinal Pago</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total Pago</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Restante</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
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

    // Formatador de moeda (BRL)
    const formatador = new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });

    // ====================================================================
    // INICIALIZAÇÃO
    // ====================================================================

    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('#financeiro-filters button');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const periodo = this.getAttribute('data-periodo');
                carregarResumo(periodo); // Carrega o resumo para o novo período
            });
        });

        // Inicialização
        carregarResumo('hoje');
        carregarPagamentosPendentes();

        // Recarregar os dados a cada 30 segundos para manter o dashboard atualizado
        setInterval(() => {
            carregarResumo(periodoAtual);
            carregarPagamentosPendentes();
        }, 30000);
    });
    
    /**
     * Atualiza os valores dos cards de resumo na tela.
     * @param {object} dados - Dados retornados pela API (que contêm hoje, semana, mes).
     */
    function atualizarCards(dados) {
        // Pega o valor específico para o periodoAtual, usando 0 como fallback
        const totalRecebido = dados.total_recebido[periodoAtual] || 0;
        const sinais = dados.sinais[periodoAtual] || 0;
        const reservas = dados.reservas[periodoAtual] || 0;

        document.getElementById('total-recebido-valor').textContent = formatador.format(totalRecebido);
        document.getElementById('sinais-valor').textContent = formatador.format(sinais);
        document.getElementById('reservas-count').textContent = reservas;
    }

    /**
     * Exibe uma mensagem de erro nos cards.
     * @param {string} message - Mensagem de erro.
     */
    function exibirErroCards(message) {
        const erroHtml = `<span class="text-sm text-red-500" title="${message}">ERRO!</span>`;
        document.getElementById('total-recebido-valor').innerHTML = erroHtml;
        document.getElementById('sinais-valor').innerHTML = erroHtml;
        document.getElementById('reservas-count').innerHTML = erroHtml;
    }

    // ====================================================================
    // CARREGAMENTO DO RESUMO (CARDS)
    // ====================================================================

    /**
     * Carrega os dados de resumo financeiro para o período selecionado.
     */
    function carregarResumo(periodo) {
        periodoAtual = periodo;

        // 1. Atualizar botões ativos (estilo)
        document.querySelectorAll('.periodo-btn').forEach(btn => {
            btn.classList.remove('bg-blue-600', 'text-white');
            btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
        });

        const activeBtn = document.querySelector(`.periodo-${periodo}`);
        if (activeBtn) {
            activeBtn.classList.add('bg-blue-600', 'text-white');
            activeBtn.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
        }

        // 2. Mostrar animação de carregamento
        const loadingHtml = '<span class="animate-pulse text-xl">R$ 0,00</span>';
        document.getElementById('total-recebido-valor').innerHTML = loadingHtml;
        document.getElementById('sinais-valor').innerHTML = loadingHtml;
        document.getElementById('reservas-count').innerHTML = '<span class="animate-pulse text-xl">0</span>';


        // 3. Fazer requisição para o endpoint de resumo
        // Note: Usamos o caminho absoluto /api/... que funciona com as rotas que você definiu em web.php
        fetch(`/api/financeiro/resumo`)
            .then(async response => {
                if (!response.ok) {
                    const errorBody = await response.json().catch(() => ({}));
                    throw new Error(errorBody.error_detail || errorBody.message || `Erro HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log(`Dados de Resumo carregados para ${periodoAtual}.`);
                    atualizarCards(data.data); 
                } else {
                    throw new Error(data.message || 'Resposta da API inválida (success=false).');
                }
            })
            .catch(error => {
                console.error('ERRO CRÍTICO ao carregar resumo:', error.message);
                exibirErroCards(error.message);
            });
    }

    // ====================================================================
    // CARREGAMENTO DA TABELA PENDENTES
    // ====================================================================

    /**
     * Carrega e renderiza a lista de pagamentos pendentes na tabela.
     */
    function carregarPagamentosPendentes() {
        const loading = document.getElementById('loading-pendentes');
        const tabela = document.getElementById('tabela-pendentes');
        const semPendentes = document.getElementById('sem-pendentes');
        const tbody = document.getElementById('tbody-pendentes');

        // Mostrar loading e esconder tabela/mensagem de vazio
        loading.classList.remove('hidden');
        tabela.classList.add('hidden');
        semPendentes.classList.add('hidden');
        tbody.innerHTML = ''; 

        // Requisição para buscar todos os pagamentos pendentes futuros
        fetch('/api/financeiro/pagamentos-pendentes') 
            .then(async response => {
                if (!response.ok) {
                    const errorBody = await response.json().catch(() => ({}));
                    throw new Error(errorBody.error_detail || errorBody.message || `Erro HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Dados Pendentes carregados.');
                loading.classList.add('hidden');

                if (data.success && data.data.length > 0) {
                    tabela.classList.remove('hidden');
                    semPendentes.classList.add('hidden');
                    
                    // Mapeia os dados da API para linhas HTML
                    tbody.innerHTML = data.data.map(reserva => `
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">${reserva.cliente}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">${reserva.contato}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                ${reserva.data}<br>
                                <span class="text-gray-500 dark:text-gray-400">${reserva.horario}</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white text-right">
                                ${formatador.format(reserva.valor_total)}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-green-600 font-semibold text-right">
                                ${formatador.format(reserva.sinal_pago)}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-blue-600 font-semibold text-right">
                                ${formatador.format(reserva.total_pago)}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-red-600 font-bold text-right">
                                ${formatador.format(reserva.valor_restante)}
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${reserva.cor_status}">
                                    ${reserva.status_pagamento_texto}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center text-sm">
                                <a href="${reserva.link_acoes}" 
                                   class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                    Ver Detalhes
                                </a>
                            </td>
                        </tr>
                    `).join('');

                } else {
                    // Nenhuma pendência ou success=false
                    tabela.classList.add('hidden');
                    semPendentes.classList.remove('hidden');
                    semPendentes.innerHTML = `<p class="text-gray-500 dark:text-gray-400">🎉 Nenhum pagamento pendente encontrado!</p>`;
                }
            })
            .catch(error => {
                console.error('ERRO CRÍTICO ao carregar pendentes:', error);
                loading.classList.add('hidden');
                tabela.classList.add('hidden');
                
                // Exibe uma mensagem de erro na área de "sem pendentes"
                semPendentes.classList.remove('hidden');
                semPendentes.innerHTML = `<p class="text-red-500 dark:text-red-400 font-bold">ERRO NO CARREGAMENTO:</p><p class="text-sm text-red-500">${error.message}. Verifique o log do Laravel.</p>`;
            });
    }
</script>
@endpush


</x-app-layout>