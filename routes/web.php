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
// ROTA API PARA BUSCA DE HORÁRIOS DISPONÍVEIS (USADA PELO JS NO ADMIN E CLIENTE)
// =========================================================================

// 1. Horários disponíveis (Slots Verdes)
Route::get('/api/horarios/disponiveis', [ApiReservaController::class, 'getAvailableSlotsApi'])
    ->name('api.horarios.disponiveis');

// 2. Reservas confirmadas/pendentes (Ocupados)
Route::get('/api/reservas/confirmadas', [AdminController::class, 'getConfirmedReservasApi'])
    ->name('api.reservas.confirmadas');
// =========================================================================


// ===============================================
// 👤 ROTAS DE AUTENTICAÇÃO E ÁREA DE CLIENTE
// ===============================================
Route::name('customer.')->group(function () {

    // 🚨 CRÍTICO: Mudei o URI de 'register' para 'customer-register'
    Route::get('customer-register', [CustomerController::class, 'showRegistrationForm'])->name('register');
    Route::post('customer-register', [CustomerController::class, 'register']);

    // Login (Path renomeado para evitar conflito com auth.php)
    Route::get('client-login', [CustomerController::class, 'showLoginForm'])->name('login');
    Route::post('client-login', [CustomerController::class, 'login']);

    // Logout (Path renomeado)
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
// Nota: O middleware 'gestor' é responsável por checar se a role é 'admin' ou 'gestor'.
Route::middleware(['auth', 'gestor'])->group(function () {

    // 🎯 1. DASHBOARD: Rota principal do painel
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // ✅ ROTA API INTERNA PARA O DASHBOARD (Contagem de Pendências)
    // 🛑 CORRIGIDO: O nome da rota deve ser 'api.reservas.pendentes.count'
    Route::get('/api/reservas/pendentes', [ReservaController::class, 'countPending'])
        ->name('api.reservas.pendentes.count');

    // ✅ CORRIGIDO: ROTA API PARA PESQUISA DE CLIENTES (AGORA APONTA PARA USERCONTROLLER)
    Route::get('/api/clientes/search', [UserController::class, 'searchClients'])
        ->name('admin.api.search-clients');

    // =========================================================================
    // 🗓️ ROTAS API PARA AGENDAMENTO RÁPIDO/RECORRENTE (DO DASHBOARD)
    // =========================================================================
    Route::post('/api/reservas/store-quick', [ReservaController::class, 'storeQuickReservaApi'])
        ->name('api.reservas.store_quick');
    Route::post('/api/reservas/store-recurrent', [ReservaController::class, 'storeRecurrentReservaApi'])
        ->name('api.reservas.store_recurrent');
    // =========================================================================

    // ===============================================
    // 🛡️ GRUPO DE ROTAS DE ADMINISTRAÇÃO COM PREFIXO
    // ===============================================
    Route::prefix('admin')->name('admin.')->group(function () {

        // 🚀 MÓDULO: CONFIGURAÇÃO DE HORÁRIOS DA ARENA
        Route::get('/config', [ConfigurationController::class, 'index'])->name('config.index');
        Route::post('/config', [ConfigurationController::class, 'store'])->name('config.store');
        Route::get('/config/generate', [ConfigurationController::class, 'generateFixedReservas'])->name('config.generate');

        // Rotas AJAX para gerenciar slots fixos individuais
        Route::post('/config/fixed-reserva/{reserva}/price', [ConfigurationController::class, 'updateFixedReservaPrice'])->name('config.update_price');
        Route::post('/config/fixed-reserva/{reserva}/status', [ConfigurationController::class, 'toggleFixedReservaStatus'])->name('config.update_status');

        // Rotas AJAX de Exclusão/Gerenciamento de Configuração Recorrente (Com Justificativa)
        Route::post('/config/delete-slot-config', [ConfigurationController::class, 'deleteSlotConfig'])->name('config.delete_slot_config');
        Route::post('/config/delete-day-config', [ConfigurationController::class, 'deleteDayConfig'])->name('config.delete_day_config');

        // --- ROTAS DE GERENCIAMENTO DE RESERVAS ---
        Route::get('reservas', [AdminController::class, 'indexReservas'])->name('reservas.index');
        Route::get('reservas/confirmadas', [AdminController::class, 'confirmed_index'])->name('reservas.confirmed_index');
        Route::get('reservas/{reserva}/show', [AdminController::class, 'showReserva'])->name('reservas.show');
        Route::get('reservas/create', [AdminController::class, 'createReserva'])->name('reservas.create');
        Route::post('reservas', [AdminController::class, 'storeReserva'])->name('reservas.store');
        Route::post('reservas/tornar-fixo', [AdminController::class, 'makeRecurrent'])->name('reservas.make_recurrent');

        // AÇÕES (STATUS E EXCLUSÃO)
        Route::patch('reservas/{reserva}/update-status', [AdminController::class, 'updateStatusReserva'])->name('reservas.updateStatus');
        Route::patch('reservas/{reserva}/confirmar', [AdminController::class, 'confirmarReserva'])->name('reservas.confirmar');
        // Usamos PATCH para rejeitar (atualiza status/deleta a reserva pendente)
        Route::patch('reservas/{reserva}/rejeitar', [AdminController::class, 'rejeitarReserva'])->name('reservas.rejeitar');


        // 🛑 CORREÇÃO CRÍTICA DO PROBLEMA PATCH METHOD NOT SUPPORTED:
        // Rotas de Cancelamento AJAX devem ser PATCH ou DELETE para ser RESTful.

        // 1. Cancelamento Pontual Padrão (Avulso ou Exceção de Pré-reserva)
        Route::patch('reservas/{reserva}/cancelar', [AdminController::class, 'cancelarReserva'])->name('reservas.cancelar');

        // 2. Cancelamento Pontual de Série (Exceção)
        Route::patch('reservas/{reserva}/cancelar-pontual', [AdminController::class, 'cancelarReservaRecorrente'])->name('reservas.cancelar_pontual');

        // 3. Cancelamento de Série Completa
        Route::delete('reservas/{reserva}/cancelar-serie', [AdminController::class, 'cancelarSerieRecorrente'])->name('reservas.cancelar_serie');


        Route::delete('reservas/{reserva}', [AdminController::class, 'destroyReserva'])->name('reservas.destroy');

        // 🛑 ROTA DE RENOVAÇÃO
        Route::post('reservas/{masterReserva}/renew-serie', [ReservaController::class, 'renewRecurrentSeries'])
            ->name('reservas.renew_serie');


        // --- ROTAS DE GERENCIAMENTO DE USUÁRIOS ---
        Route::get('users', [AdminController::class, 'indexUsers'])->name('users.index');
        Route::get('users/create', [AdminController::class, 'createUser'])->name('users.create');
        Route::post('users', [AdminController::class, 'storeUser'])->name('users.store');

    });
    // FIM DO GRUPO DE ROTAS 'admin.'
    // ===============================================

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
require __DIR__.'/auth.php';
