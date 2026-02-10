<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ➡️ IMPORTAÇÕES GERAIS
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiReservaController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\FinanceiroController;
use App\Http\Controllers\Admin\ArenaController;
use App\Http\Controllers\Admin\CompanyInfoController;
use App\Http\Controllers\ModuleController;

// ➡️ IMPORTAÇÕES ESPECÍFICAS DO BAR
use App\Http\Controllers\Bar\BarDashboardController;
use App\Http\Controllers\Bar\BarPosController;
use App\Http\Controllers\Bar\BarProductController;
use App\Http\Controllers\Bar\BarTableController;
use App\Http\Controllers\Bar\BarCashController;
use App\Http\Controllers\Bar\BarUserController;
use App\Http\Controllers\Bar\BarCompanyController;

// 🏠 ROTA RAIZ
Route::get('/', function () {
    return view('boas-vindas');
})->name('home');

// -----------------------------------------------------------------------------------
// 🛡️ CONFIGURAÇÃO E NAVEGAÇÃO DE MÓDULOS (GLOBAL)
// -----------------------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    Route::get('/setup-unidade', [ModuleController::class, 'setupUnidade'])->name('onboarding.setup');
    Route::post('/setup-unidade', [ModuleController::class, 'setupStore'])->name('onboarding.store');
    Route::get('/select-modules', [ModuleController::class, 'index'])->name('modules.selection');

    // Restrição de Planos: Somente Admin Master
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin/planos', [ModuleController::class, 'managePlans'])->name('admin.plans');
        Route::post('/admin/planos', [ModuleController::class, 'store'])->name('modules.store');
    });

    Route::get('/switch-module/{target}', [ModuleController::class, 'switch'])->name('modules.switch');
});

// 🌎 ROTAS PÚBLICAS (AGENDAMENTO EXTERNO)
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

// -----------------------------------------------------------------------------------
// 🛡️ ÁREA ADMINISTRATIVA (ARENA / GESTOR)
// -----------------------------------------------------------------------------------
Route::middleware(['auth', 'gestor'])->group(function () {

    // DASHBOARD E APIs DE STATUS (Liberado para todos os internos)
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/api/reservas/pendentes', [ReservaController::class, 'countPending'])->name('api.reservas.pendentes.count');
    Route::get('/api/clientes/search', [UserController::class, 'searchClients'])->name('admin.api.search-clients');
    Route::get('/api/users/reputation/{contact}', [UserController::class, 'getReputation'])->name('api.users.reputation');

    // AGENDAMENTO RÁPIDO
    Route::post('/api/reservas/store-quick', [ReservaController::class, 'storeQuickReservaApi'])->name('api.reservas.store_quick');
    Route::post('/api/reservas/store-recurrent', [ReservaController::class, 'storeRecurrentReservaApi'])->name('api.reservas.store_recurrent');

    // 🚀 GRUPO ADMIN (PREFIXO /admin)
    Route::prefix('admin')->name('admin.')->group(function () {

        // 🏟️ 1. GERENCIAR QUADRAS (🔒 Restrito Gestor/Admin)
        Route::middleware(['role:admin,gestor'])->group(function () {
            Route::get('/arenas', [ArenaController::class, 'index'])->name('arenas.index');
            Route::post('/arenas', [ArenaController::class, 'store'])->name('arenas.store');
        });

        // ⚙️ 2. CONFIGURAÇÃO DE FUNCIONAMENTO (🔒 Restrito Gestor/Admin)
        Route::middleware(['role:admin,gestor'])->group(function () {
            Route::get('/funcionamento-portal', [ConfigurationController::class, 'funcionamento'])->name('config.funcionamento');
            Route::get('/config/{arena_id?}', [ConfigurationController::class, 'index'])->name('config.index');
            Route::post('/config', [ConfigurationController::class, 'store'])->name('config.store');
            Route::get('/config/generate', [ConfigurationController::class, 'generateFixedReservas'])->name('config.generate');
            Route::post('/config/fixed-reserva/{id}/price', [ConfigurationController::class, 'updateFixedReservaPrice'])->name('config.update_price');
            Route::post('/config/fixed-reserva/{reserva}/status', [ReservaController::class, 'toggleFixedReservaStatus'])->name('config.update_status');
            Route::post('/config/delete-slot-config', [ConfigurationController::class, 'deleteSlotConfig'])->name('config.delete_slot_config');
            Route::post('/config/delete-day-config', [ConfigurationController::class, 'deleteDayConfig'])->name('config.delete_day_config');
        });

        // 📅 3. GESTÃO DE RESERVAS
        Route::prefix('reservas')->name('reservas.')->group(function () {
            Route::get('/dashboard/{arena_id?}', [AdminController::class, 'indexReservasDashboard'])->name('index');
            Route::get('/pendentes', [AdminController::class, 'indexReservas'])->name('pendentes');
            Route::get('/confirmadas', [AdminController::class, 'confirmed_index'])->name('confirmadas');
            Route::get('/todas', [AdminController::class, 'indexTodas'])->name('todas');
            Route::get('/rejeitadas', [AdminController::class, 'indexReservasRejeitadas'])->name('rejeitadas');
            Route::get('/{reserva}/show', [AdminController::class, 'showReserva'])->name('show');

            Route::post('/{id}/sincronizar', [AdminController::class, 'sincronizarDadosUsuario'])->name('sincronizar');
            Route::patch('/confirmar/{reserva}', [ReservaController::class, 'confirmar'])->name('confirmar');

            // Ações Críticas de Reserva (🔒 Restrito Gestor/Admin)
            Route::middleware(['role:admin,gestor'])->group(function () {
                Route::patch('/rejeitar/{reserva}', [ReservaController::class, 'rejeitar'])->name('rejeitar');
                Route::patch('/{reserva}/update-price', [AdminController::class, 'updatePrice'])->name('update_price');
                Route::patch('/{reserva}/reativar', [AdminController::class, 'reativar'])->name('reativar');
                Route::patch('/{reserva}/mover-manutencao', [AdminController::class, 'moverManutencao'])->name('mover_manutencao');
                Route::patch('/{reserva}/reativar-manutencao', [AdminController::class, 'reativarManutencao'])->name('reativar_manutencao');
                Route::patch('/{reserva}/cancelar', [AdminController::class, 'cancelarReserva'])->name('cancelar');
                Route::patch('/{reserva}/cancelar-pontual', [AdminController::class, 'cancelarReservaRecorrente'])->name('cancelar_pontual');
                Route::delete('/{reserva}/cancelar-serie', [AdminController::class, 'cancelarSerieRecorrente'])->name('cancelar_serie');
                Route::post('/cancel-client-series/{masterId}', [AdminController::class, 'cancelClientSeries'])->name('cancel_client_series');
                Route::post('/{reserva}/no-show', [PaymentController::class, 'registerNoShow'])->name('no_show');
            });
        });

        // 👥 4. GESTÃO DE USUÁRIOS (🔒 Somente Admin e Gestor podem gerenciar equipe)
        Route::prefix('users')->name('users.')->middleware(['role:admin,gestor'])->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::get('/{user}/reservas', [UserController::class, 'reservas'])->name('reservas');
        });

        // 💰 5. MÓDULO FINANCEIRO (🔒 Restrito Gestor/Admin)
        Route::prefix('pagamentos')->name('payment.')->group(function () {
            Route::middleware(['role:admin,gestor'])->group(function () {
                Route::get('/', [PaymentController::class, 'index'])->name('index');
                Route::get('/status-caixa', [FinanceiroController::class, 'getStatus'])->name('caixa.status');
                Route::post('/fechar-caixa', [PaymentController::class, 'closeCash'])->name('close_cash');
                Route::post('/abrir-caixa', [PaymentController::class, 'reopenCash'])->name('open_cash');
                Route::post('/{reserva}/pendenciar', [PaymentController::class, 'markAsPendingDebt'])->name('mark_debt');
            });
            // Finalizar pagamento pode ser operacional
            Route::post('/{reserva}/finalizar', [PaymentController::class, 'processPayment'])->name('finalize');
            Route::post('/movimentacao-avulsa', [PaymentController::class, 'storeAvulsa'])->name('store_avulsa');
        });

        // 📊 6. RELATÓRIOS ANALÍTICOS (🔒 SÓ ADMIN/GESTOR)
        Route::prefix('financeiro')->name('financeiro.')->middleware(['role:admin,gestor'])->group(function () {
            Route::get('/', [FinanceiroController::class, 'index'])->name('dashboard');
            Route::get('/faturamento', [FinanceiroController::class, 'relatorioFaturamento'])->name('relatorio_faturamento');
            Route::get('/caixa', [FinanceiroController::class, 'relatorioCaixa'])->name('relatorio_caixa');
            Route::get('/cancelamentos', [FinanceiroController::class, 'relatorioCancelamentos'])->name('relatorio_cancelamentos');
            Route::get('/ocupacao', [FinanceiroController::class, 'relatorioOcupacao'])->name('relatorio_ocupacao');
            Route::get('/ranking', [FinanceiroController::class, 'relatorioRanking'])->name('relatorio_ranking');
            Route::get('/dividas', [FinanceiroController::class, 'relatorioDividas'])->name('relatorio_dividas');
        });

        // 🏢 7. DADOS DA EMPRESA (🔒 SÓ ADMIN/GESTOR)
        Route::prefix('dados-empresa')->name('company.')->middleware(['role:admin,gestor'])->group(function () {
            Route::get('/', [CompanyInfoController::class, 'edit'])->name('edit');
            Route::put('/', [CompanyInfoController::class, 'update'])->name('update');
        });
    });

    // APIs FINANCEIRAS (🔒 SÓ ADMIN/GESTOR)
    Route::middleware(['role:admin,gestor'])->group(function () {
        Route::get('/api/financeiro/resumo', [FinanceiroController::class, 'getResumo'])->name('api.financeiro.resumo');
        Route::get('/api/financeiro/pagamentos-pendentes', [FinanceiroController::class, 'getPagamentosPendentes'])->name('api.financeiro.pagamentos-pendentes');
    });
});

// -----------------------------------------------------------------------------------
// 🍺 MÓDULO BAR (TOTALMENTE ISOLADO - LAYOUT DARK)
// -----------------------------------------------------------------------------------
Route::middleware(['auth', 'gestor'])->prefix('bar')->name('bar.')->group(function () {

    // 🏢 DADOS DA EMPRESA (🔒 Restrito)
    Route::get('/configuracoes/empresa', [BarCompanyController::class, 'edit'])->middleware(['role:admin,gestor'])->name('company.edit');
    Route::put('/configuracoes/empresa', [BarCompanyController::class, 'update'])->middleware(['role:admin,gestor'])->name('company.update');

    // 🚀 Dashboard Principal
    Route::get('/dashboard', [BarDashboardController::class, 'index'])->name('dashboard');

    // 🛒 PDV - Venda Direta (Balcão)
    Route::get('/pdv', [BarPosController::class, 'index'])->name('pdv');
    Route::post('/pdv/venda', [BarPosController::class, 'store'])->name('pos.store');
    Route::get('/vendas/{sale}', [BarPosController::class, 'show'])->name('sales.show');

    // 📦 Gestão de Estoque e Produtos
    Route::post('categorias/salvar-rapido', [BarProductController::class, 'storeCategory'])->name('categories.store_ajax');
    Route::get('estoque/entrada', [BarProductController::class, 'stockEntry'])->name('products.stock_entry');
    Route::post('estoque/entrada', [BarProductController::class, 'processStockEntry'])->name('products.process_entry');
    Route::get('estoque/historico', [BarProductController::class, 'stockHistory'])->name('products.history');

    // Ações de Estoque Sensíveis (🔒 Restrito)
    Route::post('estoque/registrar-perda', [BarProductController::class, 'recordLoss'])->middleware(['role:admin,gestor'])->name('products.record_loss');
    Route::patch('estoque/{product}/add-stock', [BarProductController::class, 'addStock'])->middleware(['role:admin,gestor'])->name('products.add_stock');

    // CRUD de Estoque (Resource)
    Route::resource('estoque', BarProductController::class)->names([
        'index'   => 'products.index',
        'create'  => 'products.create',
        'store'   => 'products.store',
        'edit'    => 'products.edit',
        'update'  => 'products.update',
        'destroy' => 'products.destroy',
    ])->parameters(['estoque' => 'product']);

    // 🍽️ Gestão de Mesas e Comandas
    Route::prefix('mesas')->name('tables.')->group(function () {
        Route::get('/', [BarTableController::class, 'index'])->name('index');

        // Configuração de Mesas (🔒 Restrito)
        Route::post('/sync', [BarTableController::class, 'sync'])->middleware(['role:admin,gestor'])->name('sync');
        Route::post('/{id}/toggle', [BarTableController::class, 'toggleStatus'])->middleware(['role:admin,gestor'])->name('toggle');

        // Operações de Comanda (Liberado)
        Route::post('/{id}/abrir', [BarTableController::class, 'open'])->name('open');
        Route::get('/{id}/comanda', [BarTableController::class, 'showOrder'])->name('show');
        Route::post('/order/{orderId}/add-item', [BarTableController::class, 'addItem'])->name('add_item');

        // Estorno e Fechamento (🔒 Restrito)
        Route::delete('/item/{itemId}/remove', [BarTableController::class, 'removeItem'])->middleware(['role:admin,gestor'])->name('remove_item');
        Route::post('/{id}/fechar', [BarTableController::class, 'closeOrder'])->name('close');

        Route::get('/recibo/{orderId}', [BarTableController::class, 'printReceipt'])->name('receipt');
    });

    // 💰 GESTÃO FINANCEIRA DE CAIXA (🔒 Restrito)
    Route::prefix('caixa')->name('cash.')->middleware(['role:admin,gestor'])->group(function () {
        Route::get('/', [BarCashController::class, 'index'])->name('index'); // bar.cash.index
        Route::post('/abrir', [BarCashController::class, 'open'])->name('open'); // bar.cash.open
        Route::post('/movimentar', [BarCashController::class, 'storeMovement'])->name('movement'); // bar.cash.movement
        Route::post('/fechar', [BarCashController::class, 'close'])->name('close'); // bar.cash.close
    });

    // 👥 Gestão de Equipe Bar (🔒 Restrito)
    Route::prefix('usuarios')->name('users.')->middleware(['role:admin,gestor'])->group(function () {
        Route::get('/', [BarUserController::class, 'index'])->name('index');
        Route::get('/create', [BarUserController::class, 'create'])->name('create');
        Route::post('/', [BarUserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [BarUserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [BarUserController::class, 'update'])->name('update');
        Route::delete('/{user}', [BarUserController::class, 'destroy'])->name('destroy');
    });
});

// -----------------------------------------------------------------------------------
// PERFIL E AUTH
// -----------------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rota Ajax de Autorização (VERSÃO BLINDADA - Não desloga o colaborador)
Route::post('/admin/autorizar-acao', function (Illuminate\Http\Request $request) {
    // 1. Busca o supervisor pelo e-mail
    $user = \App\Models\User::where('email', $request->email)->first();

    // 2. O SEGREDO: Hash::check apenas valida a senha, NÃO FAZ LOGIN.
    if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {

        // 3. Confirma se quem está autorizando é realmente um supervisor
        if (in_array($user->role, ['gestor', 'admin'])) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Este usuário não tem nível de supervisor.'], 403);
    }

    return response()->json(['success' => false, 'message' => 'Credenciais de supervisor inválidas.'], 401);
})->middleware('auth')->name('admin.autorizar_acao');

require __DIR__ . '/auth.php';
