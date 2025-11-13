<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ➡️ IMPORTAÇÕES NECESSÁRIAS
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\HorarioController; // ⬅️ Controller de Horários na subpasta Admin

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
// CORREÇÃO CRÍTICA: O POST agora aponta para o método storePublic()
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
    // 🗓️ NOVAS ROTAS API PARA FULLCALENDAR (DASHBOARD) - ADICIONADAS AQUI
    // =========================================================================
    // 1. Endpoint para RESERVAS CONFIRMADAS (AdminController)
    Route::get('/api/reservas/confirmadas', [AdminController::class, 'getConfirmedReservasApi'])
        ->name('api.reservas.confirmadas');

    // 2. Endpoint para HORÁRIOS DISPONÍVEIS (HorarioController)
    Route::get('/api/horarios/disponiveis', [HorarioController::class, 'getAvailableSlotsApi'])
        ->name('api.horarios.disponiveis');

    // 🚀 NOVO: Rota API para Agendamento Rápido Manual (POST)
    Route::post('/api/reservas/store-quick', [AdminController::class, 'storeQuickReservaApi'])
        ->name('api.reservas.store_quick');
    // =========================================================================

    // ===============================================
    // 🛡️ GRUPO DE ROTAS DE ADMINISTRAÇÃO COM PREFIXO
    // ===============================================
    Route::prefix('admin')->name('admin.')->group(function () {

        // --- ROTAS DE HORÁRIOS (CRUD) ---
        Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
        Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store');
        Route::get('/horarios/{horario}/edit', [HorarioController::class, 'edit'])->name('horarios.edit');
        Route::patch('/horarios/{horario}', [HorarioController::class, 'update'])->name('horarios.update');
        Route::patch('/horarios/{horario}/status', [HorarioController::class, 'updateStatus'])->name('horarios.update_status');
        Route::delete('/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');


        // --- ROTAS DE GERENCIAMENTO DE RESERVAS ---

        // Listagens
        Route::get('reservas', [AdminController::class, 'indexReservas'])->name('reservas.index'); // Pendentes/Todas
        Route::get('reservas/confirmadas', [AdminController::class, 'confirmed_index'])->name('reservas.confirmed_index');

        // Detalhes
        Route::get('reservas/{reserva}/show', [AdminController::class, 'showReserva'])->name('reservas.show');

        // Criação Manual (Gestor)
        Route::get('reservas/create', [AdminController::class, 'createReserva'])->name('reservas.create');
        // Rota de POST do Admin, chamando o método do AdminController
        Route::post('reservas', [AdminController::class, 'storeReserva'])->name('reservas.store');
        Route::post('reservas/tornar-fixo', [AdminController::class, 'makeRecurrent'])->name('reservas.make_recurrent');

        // AÇÕES (STATUS E EXCLUSÃO)

        // ROTA GENÉRICA: Usada para mudar o status de qualquer reserva (via formulário na tela 'show')
        Route::patch('reservas/{reserva}/status', [AdminController::class, 'updateStatusReserva'])->name('reservas.updateStatus');

        // ROTA DE CONFIRMAÇÃO (Específica)
        Route::patch('reservas/{reserva}/confirmar', [AdminController::class, 'confirmarReserva'])->name('reservas.confirmar');

        // ROTA DE REJEIÇÃO (Específica)
        Route::patch('reservas/{reserva}/rejeitar', [AdminController::class, 'rejeitarReserva'])->name('reservas.rejeitar');

        // ROTA DE CANCELAMENTO (Específica)
        Route::patch('reservas/{reserva}/cancelar', [AdminController::class, 'cancelarReserva'])->name('reservas.cancelar');

        // ROTA DE EXCLUSÃO PERMANENTE (Usada na lista geral)
        Route::delete('reservas/{reserva}', [AdminController::class, 'destroyReserva'])->name('reservas.destroy');


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
