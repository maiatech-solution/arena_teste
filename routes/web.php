<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ➡️ IMPORTAÇÕES ADICIONADAS
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\HorarioController; // Assumindo que este controller está em Admin/

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
Route::post('/agendamento', [ReservaController::class, 'store'])->name('reserva.store');

// ===============================================


// ===============================================
// 🛡️ GRUPO DE ROTAS DE ADMIN/GESTOR (PROTEGIDO)
// Aplica a autenticação ('auth') E a checagem de role ('gestor')
// ===============================================
Route::middleware(['auth', 'verified', 'gestor'])->group(function () {

    // 🎯 1. DASHBOARD: Rota principal do painel
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // ===============================================
    // 🛡️ GRUPO DE ROTAS DE ADMINISTRAÇÃO COM PREFIXO
    // ===============================================
    Route::prefix('admin')->name('admin.')->group(function () {

        // ROTAS DE HORÁRIOS
        Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
        Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store');
        Route::patch('/horarios/{horario}/status', [HorarioController::class, 'update_status'])->name('horarios.update_status');
        Route::delete('/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');


        // ROTAS DE GERENCIAMENTO DE RESERVAS
        Route::get('reservas', [AdminController::class, 'indexReservas'])->name('reservas.index');
        Route::get('reservas/confirmadas', [AdminController::class, 'confirmed_index'])->name('reservas.confirmed_index');

        // ❌ CORREÇÃO DA CONFIRMAÇÃO: Mapeia para o método confirmarReserva()
        Route::patch('reservas/{reserva}/confirmar', [AdminController::class, 'confirmarReserva'])->name('reservas.confirmar');

        // ✅ NOVA ROTA DE REJEIÇÃO: Mapeia para o novo método rejectReserva()
        Route::patch('reservas/{reserva}/rejeitar', [AdminController::class, 'rejectReserva'])->name('reservas.rejeitar');

        // ❌ CORREÇÃO DO CANCELAMENTO: Mapeia para o método cancelarReserva()
        Route::delete('reservas/{reserva}/cancelar', [AdminController::class, 'cancelarReserva'])->name('reservas.cancelar');

        // NOVAS ROTAS DE GERENCIAMENTO DE USUÁRIOS
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
