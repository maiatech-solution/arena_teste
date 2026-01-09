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

        // 🏟️ 1. GERENCIAR QUADRAS (Apenas Cadastro e Lista)
        Route::get('/arenas', [ArenaController::class, 'index'])->name('arenas.index');
        Route::post('/arenas', [ArenaController::class, 'store'])->name('arenas.store');

        // ⚙️ 2. FUNCIONAMENTO (Onde se escolhe a quadra para configurar horários)
        Route::get('/funcionamento-portal', [ConfigurationController::class, 'funcionamento'])->name('config.funcionamento');
        Route::get('/config/{arena_id}', [ConfigurationController::class, 'index'])->name('config.index');
        Route::post('/config', [ConfigurationController::class, 'store'])->name('config.store');

        // CONFIG HORÁRIOS RECORRENTES (🎯 Ajustado com parâmetro opcional {arena_id?})
        Route::get('/config/{arena_id?}', [ConfigurationController::class, 'index'])->name('config.index');
        Route::post('/config', [ConfigurationController::class, 'store'])->name('config.store');
        Route::get('/config/generate', [ConfigurationController::class, 'generateFixedReservas'])->name('config.generate');

        // CONFIGURAÇÕES AJAX (Operações em tempo real na tela de configuração)
        Route::post('/config/fixed-reserva/{id}/price', [ConfigurationController::class, 'updateFixedReservaPrice'])->name('config.update_price');
        Route::post('/config/fixed-reserva/{reserva}/status', [ReservaController::class, 'toggleFixedReservaStatus'])->name('config.update_status');
        Route::post('/config/delete-slot-config', [ConfigurationController::class, 'deleteSlotConfig'])->name('config.delete_slot_config');
        Route::post('/config/delete-day-config', [ConfigurationController::class, 'deleteDayConfig'])->name('config.delete_day_config');

        // GESTÃO DE RESERVAS
        Route::prefix('reservas')->name('reservas.')->group(function () {
            Route::get('/', [AdminController::class, 'indexReservasDashboard'])->name('index');
            Route::get('/pendentes', [AdminController::class, 'indexReservas'])->name('pendentes');
            Route::get('confirmadas', [AdminController::class, 'confirmed_index'])->name('confirmadas');
            Route::get('todas', [AdminController::class, 'indexTodas'])->name('todas');
            Route::get('rejeitadas', [AdminController::class, 'indexReservasRejeitadas'])->name('rejeitadas');
            Route::get('{reserva}/show', [AdminController::class, 'showReserva'])->name('show');
            Route::get('create', [AdminController::class, 'createUser'])->name('create');
            Route::post('/', [AdminController::class, 'storeReserva'])->name('store');
            Route::post('tornar-fixo', [AdminController::class, 'makeRecurrent'])->name('make_recurrent');

            // AÇÕES DE STATUS
            Route::patch('confirmar/{reserva}', [ReservaController::class, 'confirmar'])->name('confirmar');
            Route::patch('rejeitar/{reserva}', [ReservaController::class, 'rejeitar'])->name('rejeitar');

            Route::patch('{reserva}/update-price', [AdminController::class, 'updatePrice'])->name('update_price');
            Route::patch('{reserva}/reativar', [AdminController::class, 'reativar'])->name('reativar');
            Route::patch('{reserva}/cancelar', [AdminController::class, 'cancelarReserva'])->name('cancelar');
            Route::patch('{reserva}/cancelar-pontual', [AdminController::class, 'cancelarReservaRecorrente'])->name('cancelar_pontual');
            Route::delete('{reserva}/cancelar-serie', [AdminController::class, 'cancelarSerieRecorrente'])->name('cancelar_serie');
            Route::match(['post', 'patch'], '{reserva}/no-show', [PaymentController::class, 'registerNoShow'])->name('no_show');
            Route::delete('{reserva}', [AdminController::class, 'destroyReserva'])->name('destroy');
            Route::post('{masterReserva}/renew-serie', [ReservaController::class, 'renewRecurrentSeries'])->name('renew_serie');
            Route::delete('series/{masterId}/cancel', [AdminController::class, 'cancelClientSeries'])->name('cancel_client_series');
        });

        // GESTÃO DE USUÁRIOS
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminController::class, 'indexUsers'])->name('index');
            Route::get('create', [AdminController::class, 'createUser'])->name('create');
            Route::post('/', [AdminController::class, 'storeUser'])->name('store');
            Route::get('{user}/edit', [AdminController::class, 'editUser'])->name('edit');
            Route::put('{user}', [AdminController::class, 'updateUser'])->name('update');
            Route::delete('{user}', [AdminController::class, 'destroyUser'])->name('destroy');
            Route::get('{user}/reservas', [AdminController::class, 'clientReservations'])->name('reservas');
        });

        // 💰 MÓDULO FINANCEIRO & PAGAMENTOS
        Route::prefix('pagamentos')->name('payment.')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');

            // 🎯 ADICIONE ESTA LINHA AQUI:
            Route::get('/status-caixa', [FinanceiroController::class, 'getStatus'])->name('caixa.status');

            Route::post('fechar-caixa', [FinanceiroController::class, 'closeCash'])->name('close_cash');
            Route::post('abrir-caixa', [FinanceiroController::class, 'openCash'])->name('open_cash');
            Route::post('{reserva}/finalizar', [ReservaController::class, 'finalizarPagamento'])->name('finalize');
        });

        // 📊 RELATÓRIOS ANALÍTICOS
        Route::prefix('financeiro')->name('financeiro.')->group(function () {
            Route::get('/', [FinanceiroController::class, 'index'])->name('dashboard');
            Route::get('/faturamento', [FinanceiroController::class, 'relatorioFaturamento'])->name('relatorio_faturamento');
            Route::get('/caixa', [FinanceiroController::class, 'relatorioCaixa'])->name('relatorio_caixa');
            Route::get('/cancelamentos', [FinanceiroController::class, 'relatorioCancelamentos'])->name('relatorio_cancelamentos');
            Route::get('/ocupacao', [FinanceiroController::class, 'relatorioOcupacao'])->name('relatorio_ocupacao');
            Route::get('/ranking', [FinanceiroController::class, 'relatorioRanking'])->name('relatorio_ranking');
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

require __DIR__ . '/auth.php';
