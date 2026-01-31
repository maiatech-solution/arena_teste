<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth; // ⬅️ Importante: Adicionado para funcionar o Auth::user()

class CheckRole
{
    /**
     * Manipula a requisição de entrada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles (Cargos permitidos passados na rota)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Verifica se o usuário está autenticado
        if (!Auth::check()) {
            return $request->ajax() 
                ? response()->json(['error' => 'Não autenticado.'], 401) 
                : redirect()->route('login');
        }

        $user = Auth::user();

        // 2. Verifica se o cargo (role) do usuário está entre os permitidos para esta rota
        // Exemplo de uso na rota: middleware('role:admin,gestor')
        if (!in_array($user->role, $roles)) {
            
            // Se for uma requisição AJAX (como um clique em botão de desconto/estorno)
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Acesso negado. Esta ação exige nível de GESTOR ou ADMIN.',
                    'required_roles' => $roles
                ], 403);
            }

            // Se for navegação de página comum, tenta voltar para a anterior ou vai para a seleção de módulos
            if (url()->previous() !== url()->current()) {
                return redirect()->back()->with('error', '⚠️ Seu nível de acesso não permite realizar esta operação.');
            }

            return redirect()->route('modules.selection')
                ->with('error', '⚠️ Você tentou acessar uma área restrita ao seu nível de usuário.');
        }

        // 3. Permite que a requisição siga adiante
        return $next($request);
    }
}   