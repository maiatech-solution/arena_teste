<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// ➡️ IMPORTAÇÕES ADICIONADAS
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\Admin\HorarioController;
use App\Http\Controllers\Admin\ReservaController as AdminReservaController;

Route::get('/', function () {
    return view('welcome');
});

// ===============================================
// 🌎 ROTAS PÚBLICAS DE RESERVA (CLIENTE)
// ===============================================

// Rota pública para o cliente visualizar (GET) e fazer a pré-reserva (POST)
Route::get('/agendamento', [ReservaController::class, 'index'])->name('reserva.index');
Route::post('/agendamento', [ReservaController::class, 'store'])->name('reserva.store');

// ===============================================


// Este grupo de rotas exige autenticação (Admin)
Route::middleware(['auth', 'verified'])->group(function () {

    // Rota do Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // ===============================================
    // 🛡️ GRUPO DE ROTAS DO ADMINISTRADOR (Admin)
    // ===============================================
    Route::prefix('admin')->name('admin.')->group(function () {

        // ❌ ROTAS DE HORÁRIOS: Substituídas as 'resource' pelas manuais
        Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index'); // GET Listar
        Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store'); // POST Criar
        // ✅ ROTA CORRETA: update_status
        Route::patch('/horarios/{horario}/status', [HorarioController::class, 'update_status'])->name('horarios.update_status');
        Route::delete('/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy'); // DELETE Excluir


        // 🆕 ROTAS DE GERENCIAMENTO DE RESERVAS (Confirmação/Rejeição)
        Route::get('reservas', [AdminReservaController::class, 'index'])->name('reservas.index');
        Route::get('reservas/confirmadas', [AdminReservaController::class, 'confirmed_index'])->name('reservas.confirmed_index');
        Route::patch('reservas/{reserva}/confirm', [AdminReservaController::class, 'confirm'])->name('reservas.confirm');
        Route::patch('reservas/{reserva}/reject', [AdminReservaController::class, 'reject'])->name('reservas.reject');

    });
    // ===============================================

});

// Rotas de Profile do usuário logado (já existiam)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
