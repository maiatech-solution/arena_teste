<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class IsGestor
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Garante que o usuário está autenticado
        if (!Auth::check()) {
            return redirect()->route('customer.login');
        }

        // 2. Obter o usuário logado
        $user = Auth::user();

        // 3. Verifica a coluna 'role' diretamente
        // Permitimos acesso se a role for 'gestor' ou 'admin'
        if (in_array($user->role, ['gestor', 'admin'])) {
            return $next($request);
        }

        // Se o usuário não tiver a role correta
        abort(403, 'Acesso não autorizado. Você precisa de permissão de Gestor.');
    }
}