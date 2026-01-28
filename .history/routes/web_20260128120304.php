<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ➡️ IMPORTAÇÕES
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiReservaController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\Admin\ArenaController;
use App\Http\Controllers\Admin\CompanyInfoController; // IMPORTAÇÃO ADICIONADA

// 🏠 ROTA RAIZ
Route::get('/', function () {
    return view('boas-vindas');
})->name('home');

// 🌎 ROTAS PÚBLICAS
Route::get('/agendamento', [ReservaController::class, 'index'])->name('reserva.index');
Route::post('/agendamento', [ReservaController::class, 'storePublic'])->name('reserva.store');

// 📊 APIs PÚBLICAS (CALENDÁRIO)
Route::get('/api/horarios/disponiveis', [ApiReservaController::class, 'getAvailableSlotsApi'])->name('api.horarios.disponiveis');
Route::get('/api/reservas/confirmadas', [ApiReservaController::class, 'getConfirmedReservas'])->name('api.reservas.confirmadas');
Route::get('/api/reservas/concluidas', [ApiReservaController::class, 'getConcludedReservas'])->name('api.reservas.concluidas');

// 👤 ÁREA DO CLIENTE
Route::name('customer.')->group(function () {
    Route::get('customer-register', [CustomerController::class, 'showRegistrationForm'])->name('register');
    Route::post('customer-register', [CustomerController::class, 'register']);
    Route::get('client-login', [CustomerController::class, 'showLoginForm'])->name('login');
    Route::post('client-login', [CustomerController::class, 'login']);
    Route::post('client-logout', [CustomerController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/minhas-reservas', [CustomerController::class, 'reservationHistory'])->name('reservations.history');
        Route::post('/minhas-reservas/{reserva}/cancelar', [ReservaController::class, 'cancelByCustomer'])->name('reservas.cancel_by_customer');
    });
});

// 🛡️ ÁREA ADMINISTRATIVA (GESTOR)
Route::middleware(['auth', 'gestor'])->group(function () {

    // DASHBOARD E APIs DE STATUS
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/api/reservas/pendentes', [ReservaController::class, 'countPending'])->name('api.reservas.pendentes.count');
    Route::get('/api/clientes/search', [UserController::class, 'searchClients'])->name('admin.api.search-clients');
    Route::get('/api/users/reputation/{contact}', [UserController::class, 'getReputation'])->name('api.users.reputation');

    // AGENDAMENTO RÁPIDO
    Route::post('/api/reservas/store-quick', [ReservaController::class, 'storeQuickReservaApi'])->name('api.reservas.store_quick');
    Route::post('/api/reservas/store-recurrent', [ReservaController::class, 'storeRecurrentReservaApi'])->name('api.reservas.store_recurrent');

    // 🚀 GRUPO ADMIN (PREFIXO /admin)
    Route::prefix('admin')->name('admin.')->group(function () {

        // 🏟️ 1. GERENCIAR QUADRAS
        Route::get('/arenas', [ArenaController::class, 'index'])->name('arenas.index');
        Route::post('/arenas', [ArenaController::class, 'store'])->name('arenas.store');

        // ⚙️ 2. CONFIGURAÇÃO DE FUNCIONAMENTO E HORÁRIOS
        Route::get('/funcionamento-portal', [ConfigurationController::class, 'funcionamento'])->name('config.funcionamento');

        // Unificado: Parâmetro opcional {arena_id?} resolve ambos os casos
        Route::get('/config/{arena_id?}', [ConfigurationController::class, 'index'])->name('config.index');
        Route::post('/config', [ConfigurationController::class, 'store'])->name('config.store');
        Route::get('/config/generate', [ConfigurationController::class, 'generateFixedReservas'])->name('config.generate');

        // Operações AJAX de Configuração
        Route::post('/config/fixed-reserva/{id}/price', [ConfigurationController::class, 'updateFixedReservaPrice'])->name('config.update_price');
        Route::post('/config/fixed-reserva/{reserva}/status', [ReservaController::class, 'toggleFixedReservaStatus'])->name('config.update_status');
        Route::post('/config/delete-slot-config', [ConfigurationController::class, 'deleteSlotConfig'])->name('config.delete_slot_config');
        Route::post('/config/delete-day-config', [ConfigurationController::class, 'deleteDayConfig'])->name('config.delete_day_config');

        // 📅 3. GESTÃO DE RESERVAS
        Route::prefix('reservas')->name('reservas.')->group(function () {
            // Listagens e Visualização
            Route::get('/dashboard/{arena_id?}', [AdminController::class, 'indexReservasDashboard'])->name('index');
            Route::get('/pendentes', [AdminController::class, 'indexReservas'])->name('pendentes');
            Route::get('/confirmadas', [AdminController::class, 'confirmed_index'])->name('confirmadas');
            Route::get('/todas', [AdminController::class, 'indexTodas'])->name('todas');
            Route::get('/rejeitadas', [AdminController::class, 'indexReservasRejeitadas'])->name('rejeitadas');
            Route::get('/{reserva}/show', [AdminController::class, 'showReserva'])->name('show');

            // 🔄 ROTA DE SINCRONIZAÇÃO (ADICIONADA AQUI)
            Route::post('/{id}/sincronizar', [AdminController::class, 'sincronizarDadosUsuario'])->name('sincronizar');

            // Ações de Confirmação e Rejeição
            Route::patch('/confirmar/{reserva}', [ReservaController::class, 'confirmar'])->name('confirmar');
            Route::patch('/rejeitar/{reserva}', [ReservaController::class, 'rejeitar'])->name('rejeitar');

            // Edição e Reativação (Clientes)
            Route::patch('/{reserva}/update-price', [AdminController::class, 'updatePrice'])->name('update_price');
            Route::patch('/{reserva}/reativar', [AdminController::class, 'reativar'])->name('reativar');

            // 🛠️ LÓGICA DE MANUTENÇÃO (Novas Rotas)
            Route::patch('/{reserva}/mover-manutencao', [AdminController::class, 'moverManutencao'])->name('mover_manutencao');
            Route::patch('/{reserva}/reativar-manutencao', [AdminController::class, 'reativarManutencao'])->name('reativar_manutencao');

            // Cancelamentos e No-Show
            Route::patch('/{reserva}/cancelar', [AdminController::class, 'cancelarReserva'])->name('cancelar');
            Route::patch('/{reserva}/cancelar-pontual', [AdminController::class, 'cancelarReservaRecorrente'])->name('cancelar_pontual');
            Route::delete('/{reserva}/cancelar-serie', [AdminController::class, 'cancelarSerieRecorrente'])->name('cancelar_serie');
            Route::post('/cancel-client-series/{masterId}', [AdminController::class, 'cancelClientSeries'])->name('cancel_client_series');
            Route::post('/{reserva}/no-show', [PaymentController::class, 'registerNoShow'])->name('no_show');
        });

        // 👥 4. GESTÃO DE USUÁRIOS
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::get('/{user}/reservas', [UserController::class, 'reservas'])->name('reservas');
        });

        // 💰 5. MÓDULO FINANCEIRO & PAGAMENTOS
        Route::prefix('pagamentos')->name('payment.')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('/status-caixa', [FinanceiroController::class, 'getStatus'])->name('caixa.status');
            Route::post('/fechar-caixa', [PaymentController::class, 'closeCash'])->name('close_cash');
            Route::post('/abrir-caixa', [PaymentController::class, 'reopenCash'])->name('open_cash');
            Route::post('/{reserva}/finalizar', [PaymentController::class, 'processPayment'])->name('finalize');

            // ⬇️ ADICIONE ESTA LINHA ABAIXO ⬇️
            Route::post('/movimentacao-avulsa', [PaymentController::class, 'storeAvulsa'])->name('store_avulsa');

            // 🕒 ROTA ADICIONADA: Processa o "Pagar Depois"
            Route::post('/{reserva}/pendenciar', [PaymentController::class, 'markAsPendingDebt'])->name('mark_debt');
        });

        // 📊 6. RELATÓRIOS ANALÍTICOS
        Route::prefix('financeiro')->name('financeiro.')->group(function () {
            Route::get('/', [FinanceiroController::class, 'index'])->name('dashboard');
            Route::get('/faturamento', [FinanceiroController::class, 'relatorioFaturamento'])->name('relatorio_faturamento');
            Route::get('/caixa', [FinanceiroController::class, 'relatorioCaixa'])->name('relatorio_caixa');
            Route::get('/cancelamentos', [FinanceiroController::class, 'relatorioCancelamentos'])->name('relatorio_cancelamentos');
            Route::get('/ocupacao', [FinanceiroController::class, 'relatorioOcupacao'])->name('relatorio_ocupacao');
            Route::get('/ranking', [FinanceiroController::class, 'relatorioRanking'])->name('relatorio_ranking');

            // 🚀 NOVA ROTA DE DÍVIDAS PENDENTES (Passo 3)
            Route::get('/dividas', [FinanceiroController::class, 'relatorioDividas'])->name('relatorio_dividas');
        });

        // 🏢 7. DADOS DA EMPRESA (Elite Soccer - Local Único)
        Route::prefix('dados-empresa')->name('company.')->group(function () {
            Route::get('/', [CompanyInfoController::class, 'edit'])->name('edit');
            Route::put('/', [CompanyInfoController::class, 'update'])->name('update');
        });
    });

    // APIs FINANCEIRAS (AJAX)
    Route::get('/api/financeiro/resumo', [FinanceiroController::class, 'getResumo'])->name('api.financeiro.resumo');
    Route::get('/api/financeiro/pagamentos-pendentes', [FinanceiroController::class, 'getPagamentosPendentes'])->name('api.financeiro.pagamentos-pendentes');
});

// -----------------------------------------------------------------------------------
// PERFIL DO USUÁRIO (BREEZE)
// -----------------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// -----------------------------------------------------------------------------------
// 🍺 MÓDULO BAR (TOTALMENTE ISOLADO - APENAS PARA GESTORES)
// -----------------------------------------------------------------------------------
Route::middleware(['auth', 'gestor'])->prefix('bar')->name('bar.')->group(function () {

    // 1. Central de Comando (Dashboard com os Cards Facilitadores)
    Route::get('/dashboard', [App\Http\Controllers\Bar\BarDashboardController::class, 'index'])->name('dashboard');

    // 2. PDV (Venda Rápida / Balcão)
    Route::get('/pdv', [App\Http\Controllers\Bar\BarOrderController::class, 'pdv'])->name('pdv');

    // 3. Estoque (Produtos, Código de Barras e Preços)

    // 🚀 NOVA ROTA: Cadastro rápido de categorias (usado pelo botão + no formulário)
    Route::post('categorias/salvar-rapido', [App\Http\Controllers\Bar\BarProductController::class, 'storeCategory'])->name('categories.store_ajax');

    // Entrada de Mercadoria (Abastecimento)
    Route::get('estoque/entrada', [App\Http\Controllers\Bar\BarProductController::class, 'stockEntry'])->name('products.stock_entry');
    Route::post('estoque/entrada', [App\Http\Controllers\Bar\BarProductController::class, 'processStockEntry'])->name('products.process_entry');

    // Resource Principal de Estoque
    Route::resource('estoque', App\Http\Controllers\Bar\BarProductController::class)->names([
        'index'   => 'products.index',
        'create'  => 'products.create',
        'store'   => 'products.store',
        'edit'    => 'products.edit',
        'update'  => 'products.update',
        'destroy' => 'products.destroy',
    ])->parameters([
        'estoque' => 'product'
    ]);

    // Rota para ajuste/adição rápida individual (PATCH)
    Route::patch('estoque/{product}/add-stock', [App\Http\Controllers\Bar\BarProductController::class, 'addStock'])->name('products.add_stock');

    // 4. Mesas (Mapa de mesas e configuração de quantidade)
    Route::get('/mesas', [App\Http\Controllers\Bar\BarTableController::class, 'index'])->name('tables.index');
    Route::post('/mesas/configurar', [App\Http\Controllers\Bar\BarTableController::class, 'configure'])->name('tables.config');

    // 5. Caixa Exclusivo do Bar
    Route::get('/caixa', [App\Http\Controllers\Bar\BarCashController::class, 'index'])->name('cash.index');
});

require __DIR__ . '/auth.php';
