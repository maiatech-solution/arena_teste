<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyInfo;

class IsGestor
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Garante que o usuário está autenticado
        if (!Auth::check()) {
            return redirect()->route('customer.login');
        }

        $user = Auth::user();

        // 2. Verifica a role (Gestor ou Admin)
        if (!in_array($user->role, ['gestor', 'admin'])) {
            abort(403, 'Acesso não autorizado. Você precisa de permissão de Gestor.');
        }

        // 3. Lógica de Módulos (CompanyInfo)
        $company = CompanyInfo::first(); // Pega a única empresa cadastrada

        // Se a empresa ainda não escolheu o módulo (modules_active == 0)
        // E o usuário é um dos admins mestres, redireciona para a escolha
        $masterAdmins = ['drikomaia89@gmail.com', 'marcosbleal26@gmail.com']; // AJUSTE COM OS EMAILS REAIS

        if ($company && $company->modules_active === 0) {
            if (in_array($user->email, $masterAdmins)) {
                if (!$request->is('select-modules*')) {
                    return redirect()->route('modules.selection');
                }
            } else {
                // Se for outro funcionário, avisa que o sistema está em configuração
                abort(403, 'O sistema ainda não foi configurado pelo administrador.');
            }
        }

        // 4. Bloqueio de Acesso Cruzado (Cross-Module)
        if ($company) {
            if ($company->modules_active == 1 && $request->is('bar*')) {
                abort(403, 'O Módulo Bar não está ativo para esta empresa.');
            }
            if ($company->modules_active == 2 && $request->is('arena*')) {
                abort(403, 'O Módulo Arena não está ativo para esta empresa.');
            }
        }

        return $next($request);
    }
}
