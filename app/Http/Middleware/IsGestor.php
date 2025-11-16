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
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Garante que o usuário está autenticado
        if (!Auth::check()) {
            // Se o usuário não está logado, redireciona para a tela de login
            return redirect()->route('customer.login');
        }

        // 2. Obter o usuário logado
        $user = Auth::user();

        // 3. Usar a PROPRIEDADE VIRTUAL (Accessor)
        // Isso só funcionará se o Accessor isGestor() estiver no Model User.php
        if ($user->is_gestor) {
            // Se o usuário for Gestor ou Admin, permite o acesso
            return $next($request);
        }

        // Se o usuário estiver logado, mas não tiver a role correta,
        // redireciona para a home com uma mensagem de erro, ou aborta o acesso.
        abort(403, 'Acesso não autorizado. Você precisa de permissão de Gestor.');
    }
}
