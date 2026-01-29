<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // 🎯 AJUSTE: Redireciona para a tela de SELEÇÃO DE MÓDULOS.
        // O ModuleController@index cuidará de decidir se o usuário:
        // 1. Vai para o Onboarding (se for novo)
        // 2. Vai para a Seleção (se for Combo ou Admin)
        // 3. Vai direto para o Bar ou Arena (se for módulo único)
        return redirect()->intended(route('modules.selection'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
