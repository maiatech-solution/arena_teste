<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ➡️ IMPORTAÇÕES NECESSÁRIAS
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ApiReservaController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\FinanceiroController;

// -----------------------------------------------------------------------------------
// 🏠 ROTA RAIZ (PÚBLICA) - Bem-vindo à Arena
// -----------------------------------------------------------------------------------
Route::get('/', function () {
    return view('boas-vindas');
})->name('home');

// ===============================================
// 🌎 ROTAS PÚBLICAS DE RESERVA
// ===============================================

// Rota pública para o cliente visualizar (GET) e fazer a pré-reserva (POST)
Route::get('/agendamento', [ReservaController::class, 'index'])->name('reserva.index');
Route::post('/agendamento', [ReservaController::class, 'storePublic'])->name('reserva.store');


// =========================================================================
// ROTA API PARA BUSCA DE HORÁRIOS
// =========================================================================

// 1. Horários disponíveis (Slots Verdes)
Route::get('/api/horarios/disponiveis', [ApiReservaController::class, 'getAvailableSlotsApi'])
    ->name('api.horarios.disponiveis');

// 2. Reservas confirmadas/pendentes (Ocupados)
Route::get('/api/reservas/confirmadas', [ApiReservaController::class, 'getConfirmedReservas'])
    ->name('api.reservas.confirmadas');
// =========================================================================


// ===============================================
// 👤 ROTAS DE AUTENTICAÇÃO E ÁREA DE CLIENTE
// ===============================================
Route::name('customer.')->group(function () {

    // Login e Registro para Clientes
    Route::get('customer-register', [CustomerController::class, 'showRegistrationForm'])->name('register');
    Route::post('customer-register', [CustomerController::class, 'register']);
    Route::get('client-login', [CustomerController::class, 'showLoginForm'])->name('login');
    Route::post('client-login', [CustomerController::class, 'login']);
    Route::post('client-logout', [CustomerController::class, 'logout'])->middleware('auth')->name('logout');

    // ✅ HISTÓRICO DE RESERVAS DO CLIENTE (Protegido por 'auth')
    Route::middleware('auth')->group(function () {
        Route::get('/minhas-reservas', [CustomerController::class, 'reservationHistory'])->name('reservations.history');
        // Rota AJAX para Cancelamento pelo Cliente
        Route::post('/minhas-reservas/{reserva}/cancelar', [ReservaController::class, 'cancelByCustomer'])->name('reservas.cancel_by_customer');
    });
});
// FIM DO GRUPO DE ROTAS DE CLIENTE
// ===============================================


// ===============================================
// 🛡️ GRUPO DE ROTAS DE ADMIN/GESTOR (PROTEGIDO)
// ===============================================
Route::middleware(['auth', 'gestor'])->group(function () {

    // 🎯 1. DASHBOARD: Rota principal do painel
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // ✅ ROTA API INTERNA PARA O DASHBOARD (Contagem de Pendências)
    Route::get('/api/reservas/pendentes', [ReservaController::class, 'countPending'])->name('api.reservas.pendentes.count');

    // ✅ ROTA API PARA PESQUISA DE CLIENTES
    Route::get('/api/clientes/search', [UserController::class, 'searchClients'])
        ->name('admin.api.search-clients');

    // 🎯 NOVO: ROTA API PARA BUSCAR STATUS/REPUTAÇÃO DO CLIENTE PELO CONTATO
    Route::get('/api/users/reputation/{contact}', [UserController::class, 'getReputation'])
        ->name('api.users.reputation');

    // =========================================================================
    // 🗓️ ROTAS API PARA AGENDAMENTO RÁPIDO/RECORRENTE (DO DASHBOARD)
    // =========================================================================
    Route::post('/api/reservas/store-quick', [ReservaController::class, 'storeQuickReservaApi'])
        ->name('api.reservas.store_quick');
    Route::post('/api/reservas/store-recurrent', [ReservaController::class, 'storeRecurrentReservaApi'])
        ->name('api.reservas.store_recurrent');
    // =========================================================================

    // 🛑 ROTAS AJAX MOVIDAS PARA FORA DO PREFIXO ANINHADO PARA EVITAR ERRO DE ROTA
    Route::post('/admin/config/fixed-reserva/{id}/price', [ConfigurationController::class, 'updateFixedReservaPrice'])->name('admin.config.update_price');
    Route::post('/admin/config/fixed-reserva/{reserva}/status', [ReservaController::class, 'toggleFixedReservaStatus'])->name('admin.config.update_status');

    // Rotas AJAX de Exclusão/Gerenciamento de Configuração Recorrente (Com Justificativa)
    Route::post('/admin/config/delete-slot-config', [ConfigurationController::class, 'deleteSlotConfig'])->name('admin.config.delete_slot_config');
    Route::post('/admin/config/delete-day-config', [ConfigurationController::class, 'deleteDayConfig'])->name('admin.config.delete_day_config');
    // FIM DAS ROTAS MOVIDAS

    // =========================================================================
    // ✅ ROTAS CRÍTICAS DE AÇÃO DE RESERVA (MOVEMOS PARA CÁ PARA MAIOR PRECEDÊNCIA)
    // Usam o prefixo completo 'admin/reservas' e o nome 'admin.reservas.'
    // =========================================================================
    Route::patch('/admin/reservas/{reserva}/confirmar', [ReservaController::class, 'confirmar'])
        ->name('admin.reservas.confirmar');

    Route::patch('/admin/reservas/{reserva}/rejeitar', [ReservaController::class, 'rejeitar'])
        ->name('admin.reservas.rejeitar');
    // =========================================================================


    // ===============================================
    // 🛡️ GRUPO DE ROTAS DE ADMINISTRAÇÃO COM PREFIXO
    // ===============================================
    Route::prefix('admin')->name('admin.')->group(function () {

        // 🚀 MÓDULO: CONFIGURAÇÃO DE HORÁRIOS DA ARENA (Index e Store permanecem aqui)
        Route::get('/config', [ConfigurationController::class, 'index'])->name('config.index');
        Route::post('/config', [ConfigurationController::class, 'store'])->name('config.store');
        Route::get('/config/generate', [ConfigurationController::class, 'generateFixedReservas'])->name('config.generate');

        // =========================================================================
        // 🚀 MÓDULO: GERENCIAMENTO DE RESERVAS (Centralizado)
        // =========================================================================
        Route::prefix('reservas')->name('reservas.')->group(function () {

            // Rotas de Listagem
            Route::get('/', [AdminController::class, 'indexReservasDashboard'])->name('index');
            Route::get('pendentes', [AdminController::class, 'indexReservas'])->name('pendentes');
            Route::get('confirmadas', [AdminController::class, 'confirmed_index'])->name('confirmadas');
            Route::get('todas', [AdminController::class, 'indexTodas'])->name('todas');
            Route::get('rejeitadas', [AdminController::class, 'indexReservasRejeitadas'])->name('rejeitadas');


            // --- ROTAS DE AÇÕES E CRIAÇÃO ---
            Route::get('{reserva}/show', [AdminController::class, 'showReserva'])->name('show');
            Route::get('create', [AdminController::class, 'createUser'])->name('create');
            Route::post('/', [AdminController::class, 'storeReserva'])->name('store');
            Route::post('tornar-fixo', [AdminController::class, 'makeRecurrent'])->name('make_recurrent');

            // ❌ ROTAS DE CONFIRMAR/REJEITAR REMOVIDAS DAQUI PARA EVITAR CONFLITO DE PREFIXO/ORDEM
            // Route::patch('{reserva}/confirmar', [ReservaController::class, 'confirmar'])->name('confirmar');
            // Route::patch('{reserva}/rejeitar', [ReservaController::class, 'rejeitar'])->name('rejeitar');


            // Rotas de Modificação
            Route::patch('{reserva}/update-price', [AdminController::class, 'updatePrice'])->name('update_price');
            Route::patch('{reserva}/reativar', [AdminController::class, 'reativar'])->name('reativar');

            // ROTAS DE CANCELAMENTO AJAX (RESTful)
            Route::patch('{reserva}/cancelar', [AdminController::class, 'cancelarReserva'])->name('cancelar');
            Route::patch('{reserva}/cancelar-pontual', [AdminController::class, 'cancelarReservaRecorrente'])->name('cancelar_pontual');
            Route::delete('{reserva}/cancelar-serie', [AdminController::class, 'cancelarSerieRecorrente'])->name('cancelar_serie');

            Route::delete('{reserva}', [AdminController::class, 'destroyReserva'])->name('destroy');

            // 🛑 ROTA DE RENOVAÇÃO
            Route::post('{masterReserva}/renew-serie', [ReservaController::class, 'renewRecurrentSeries'])
                ->name('renew_serie');

            // 🛑 ROTA NOVA E CRÍTICA PARA O CANCELAMENTO DE SÉRIE EM MASSA NO HISTÓRICO DE CLIENTE
            Route::delete('series/{masterId}/cancel', [AdminController::class, 'cancelClientSeries'])->name('cancel_client_series');
        });

        // --- ROTAS DE GERENCIAMENTO DE USUÁRIOS (User Resource) ---
        Route::get('users', [AdminController::class, 'indexUsers'])->name('users.index');
        Route::get('users/create', [AdminController::class, 'createUser'])->name('users.create');
        Route::post('users', [AdminController::class, 'storeUser'])->name('users.store');

        // ✅ ROTAS PARA EDIÇÃO, ATUALIZAÇÃO E EXCLUSÃO
        Route::get('users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::put('users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');

        // ✅ NOVA ROTA: Reservas de um cliente específico
        Route::get('users/{user}/reservas', [AdminController::class, 'clientReservations'])->name('users.reservas');
    });

    //ROTAS DE PAGAMENTOS
    // 💰 Módulo Financeiro / Pagamentos
    Route::get('/admin/pagamentos', [PaymentController::class, 'index'])->name('admin.payment.index');
    Route::post('/admin/pagamentos/{reserva}/finalizar', [PaymentController::class, 'processPayment'])->name('admin.payment.process');
    Route::post('/admin/pagamentos/{reserva}/falta', [PaymentController::class, 'registerNoShow'])->name('admin.payment.noshow');

    // 📊 ROTAS DO DASHBOARD FINANCEIRO
    Route::get('/admin/financeiro', [FinanceiroController::class, 'index'])->name('admin.financeiro.dashboard');
    Route::get('/api/financeiro/resumo', [FinanceiroController::class, 'getResumo'])->name('api.financeiro.resumo');
    Route::get('/api/financeiro/pagamentos-pendentes', [FinanceiroController::class, 'getPagamentosPendentes'])->name('api.financeiro.pagamentos-pendentes');

});
// FIM DO GRUPO DE ROTAS PROTEGIDAS PELO MIDDLEWARE 'gestor'
// ===============================================


// -----------------------------------------------------------------------------------
// ROTAS DE PROFILE (PADRÃO DO BREEZE/JETSTREAM)
// -----------------------------------------------------------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Importação das rotas de autenticação (Login, Logout, etc.)
require __DIR__ . '/auth.php';
