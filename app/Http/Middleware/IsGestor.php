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
        // 1. Verificar se o usuário está logado
        if (!Auth::check()) {
            // Se não estiver logado, redireciona para a página de login
            return redirect('/login');
        }

        // 2. Obter o usuário logado
        $user = Auth::user();

        // 3. Usar o método helper que definimos no User Model
        // O método isGestor() verifica se a role é 'gestor' OU 'admin'
        if ($user->isGestor()) {
            // Se o usuário for Gestor ou Admin, permite o acesso
            return $next($request);
        }

        // Se o usuário estiver logado, mas não tiver a role correta,
        // redireciona para a home com uma mensagem de erro, ou aborta o acesso.
        // Usaremos 'abort(403)' para acesso negado.
        abort(403, 'Acesso não autorizado. Você precisa de permissão de Gestor.');
    }
}
