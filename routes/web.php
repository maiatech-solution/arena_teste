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
Route::post('/agendamento', [ReservaController::class, 'store'])->name('reserva.store');


// =========================================================================
// ROTA API PARA BUSCA DE HORÁRIOS DISPONÍVEIS (USADA PELO JS NO ADMIN E CLIENTE)
// =========================================================================
Route::get('/api/reservas/available-times', [ReservaController::class, 'getAvailableTimes'])
    ->name('api.reservas.available-times');


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

        // ROTA POST UNIFICADA. Usa o método 'store' do HorarioController
        Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store');

        // ROTA GET PARA EXIBIR O FORMULÁRIO DE EDIÇÃO
        Route::get('/horarios/{horario}/edit', [HorarioController::class, 'edit'])->name('horarios.edit');

        // ROTA PATCH PARA SALVAR AS MUDANÇAS DE EDIÇÃO
        Route::patch('/horarios/{horario}', [HorarioController::class, 'update'])->name('horarios.update');

        // Mapeia para o método correto 'updateStatus' (CamelCase)
        Route::patch('/horarios/{horario}/status', [HorarioController::class, 'updateStatus'])->name('horarios.update_status');

        Route::delete('/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');


        // ROTAS DE GERENCIAMENTO DE RESERVAS
        Route::get('reservas', [AdminController::class, 'indexReservas'])->name('reservas.index');

        // ROTA PARA EXIBIR O FORMULÁRIO DE CRIAÇÃO MANUAL DE RESERVA
        Route::get('reservas/create', [AdminController::class, 'createReserva'])->name('reservas.create');
        // ROTA PARA PROCESSAR A CRIAÇÃO MANUAL DE RESERVA
        Route::post('reservas', [AdminController::class, 'storeReserva'])->name('reservas.store');


        // NOVA ROTA: Processa o formulário para criar a série de reservas fixas (Horário Fixo para Cliente)
        Route::post('reservas/tornar-fixo', [AdminController::class, 'makeRecurrent'])->name('reservas.make_recurrent');

        Route::get('reservas/confirmadas', [AdminController::class, 'confirmed_index'])->name('reservas.confirmed_index');

        // ROTA DE CONFIRMAÇÃO
        Route::patch('reservas/{reserva}/confirmar', [AdminController::class, 'confirmarReserva'])->name('reservas.confirmar');

        // ROTA DE REJEIÇÃO
        Route::patch('reservas/{reserva}/rejeitar', [AdminController::class, 'rejectReserva'])->name('reservas.rejeitar');

        // ROTA DE CANCELAMENTO
        Route::delete('reservas/{reserva}/cancelar', [AdminController::class, 'cancelarReserva'])->name('reservas.cancelar');

        // ROTAS DE GERENCIAMENTO DE USUÁRIOS
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
