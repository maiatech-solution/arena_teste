<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ➡️ IMPORTAÇÕES NECESSÁRIAS
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConfigurationController; // NOVO: Controller de Configuração da Arena
// ❌ IMPORTAÇÃO ANTIGA REMOVIDA: use App\Http\Controllers\Admin\HorarioController;

// -----------------------------------------------------------------------------------
// 🏠 ROTA RAIZ (PÚBLICA) - Bem-vindo à Arena
// -----------------------------------------------------------------------------------
Route::get('/', function () {
    return view('boas-vindas');
})->name('home');

// ===============================================
// 🌎 ROTAS PÚBLICAS DE RESERVA (CLIENTE)
// ===============================================

// Rota pública para o cliente visualizar (GET) e fazer a pré-reserva (POST)
Route::get('/agendamento', [ReservaController::class, 'index'])->name('reserva.index');
Route::post('/agendamento', [ReservaController::class, 'storePublic'])->name('reserva.store');


// =========================================================================
// ROTA API PARA BUSCA DE HORÁRIOS DISPONÍVEIS (USADA PELO JS NO ADMIN E CLIENTE)
// =========================================================================
Route::get('/api/reservas/available-times', [ReservaController::class, 'getAvailableTimes'])
    ->name('api.reservas.available-times');


// ===============================================
// 🛡️ GRUPO DE ROTAS DE ADMIN/GESTOR (PROTEGIDO)
// ===============================================
Route::middleware(['auth', 'verified', 'gestor'])->group(function () {

    // 🎯 1. DASHBOARD: Rota principal do painel
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // ✅ ROTA API INTERNA PARA O DASHBOARD (Contagem de Pendências)
    Route::get('/api/reservas/pendentes', [ReservaController::class, 'countPending'])
        ->name('api.reservas.pendentes');

    // =========================================================================
    // 🗓️ ROTAS API PARA FULLCALENDAR (AGORA NO RESERVACONTROLLER)
    // =========================================================================
    // 1. Endpoint para RESERVAS CONFIRMADAS (Mantido no AdminController, pois contém mais lógica de exibição)
    Route::get('/api/reservas/confirmadas', [AdminController::class, 'getConfirmedReservasApi'])
        ->name('api.reservas.confirmadas');

    // 2. Endpoint para HORÁRIOS DISPONÍVEIS (Movido para ReservaController - Nova Lógica)
    Route::get('/api/horarios/disponiveis', [ReservaController::class, 'getAvailableSlotsApi'])
        ->name('api.horarios.disponiveis');

    // 🚀 Rota API para Agendamento Rápido Pontual (POST)
    Route::post('/api/reservas/store-quick', [ReservaController::class, 'storeQuickReservaApi'])
        ->name('api.reservas.store_quick');

    // ✅ NOVA ROTA: Para Agendamento Rápido Recorrente (POST)
    Route::post('/api/reservas/store-recurrent', [ReservaController::class, 'storeRecurrentReservaApi'])
        ->name('api.reservas.store_recurrent');
    // =========================================================================

    // ===============================================
    // 🛡️ GRUPO DE ROTAS DE ADMINISTRAÇÃO COM PREFIXO
    // ===============================================
    Route::prefix('admin')->name('admin.')->group(function () {

        // ❌ LIMPEZA: TODAS AS ROTAS DE HORARIO CONTROLLER FORAM REMOVIDAS AQUI

        // 🚀 NOVO MÓDULO: CONFIGURAÇÃO DE HORÁRIOS DA ARENA
        Route::get('/config', [ConfigurationController::class, 'index'])->name('config.index');
        Route::post('/config', [ConfigurationController::class, 'store'])->name('config.store');
        Route::get('/config/generate', [ConfigurationController::class, 'generateFixedReservas'])->name('config.generate');

        // Rotas AJAX para gerenciar slots fixos individuais (usadas na tabela de gerenciamento)
        Route::post('/config/fixed-reserva/{reserva}/price', [ConfigurationController::class, 'updateFixedReservaPrice'])->name('config.update_price');
        Route::post('/config/fixed-reserva/{reserva}/status', [ConfigurationController::class, 'updateFixedReservaStatus'])->name('config.update_status');


        // --- ROTAS DE GERENCIAMENTO DE RESERVAS (Mantidas) ---

        // Listagens
        Route::get('reservas', [AdminController::class, 'indexReservas'])->name('reservas.index'); // Pendentes/Todas
        Route::get('reservas/confirmadas', [AdminController::class, 'confirmed_index'])->name('reservas.confirmed_index');

        // Detalhes
        Route::get('reservas/{reserva}/show', [AdminController::class, 'showReserva'])->name('reservas.show');

        // Criação Manual (Gestor)
        Route::get('reservas/create', [AdminController::class, 'createReserva'])->name('reservas.create');
        Route::post('reservas', [AdminController::class, 'storeReserva'])->name('reservas.store');
        Route::post('reservas/tornar-fixo', [AdminController::class, 'makeRecurrent'])->name('reservas.make_recurrent');

        // AÇÕES (STATUS E EXCLUSÃO)
        Route::patch('reservas/{reserva}/status', [AdminController::class, 'updateStatusReserva'])->name('reservas.updateStatus');
        Route::patch('reservas/{reserva}/confirmar', [AdminController::class, 'confirmarReserva'])->name('reservas.confirmar');
        Route::patch('reservas/{reserva}/rejeitar', [AdminController::class, 'rejeitarReserva'])->name('reservas.rejeitar');
        Route::patch('reservas/{reserva}/cancelar', [AdminController::class, 'cancelarReserva'])->name('reservas.cancelar');

        // ❌ Rota DELETE Antiga (Destrói Reserva Pontual)
        Route::delete('reservas/{reserva}', [AdminController::class, 'destroyReserva'])->name('reservas.destroy');

        // ✅ NOVAS ROTAS DE CANCELAMENTO RECORRENTE
        // 1. Cancelamento Pontual de uma Reserva Recorrente (Recria o Slot Fixo)
        Route::delete('reservas/{reserva}/cancelar-pontual', [AdminController::class, 'cancelarReservaRecorrente'])->name('reservas.cancelar_pontual');
        // 2. Cancelamento da Série Inteira (Recria todos os Slots Fixos futuros)
        Route::delete('reservas/{reserva}/cancelar-serie', [AdminController::class, 'cancelarSerieRecorrente'])->name('reservas.cancelar_serie');


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
