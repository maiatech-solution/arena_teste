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
        // 1. Verifica se está logado
        if (!Auth::check()) {
            return redirect()->route('customer.login');
        }

        $user = Auth::user();

        // 2. Verifica se tem acesso administrativo (Usando o método do Model)
        if (!$user->has_admin_access) {
            abort(403, 'Acesso não autorizado.');
        }

        $company = CompanyInfo::first();

        // --- FLUXO DE ONBOARDING E SETUP ---

        // PRIORIDADE 1: Cadastro inicial da Unidade
        if (!$company || empty($company->nome_fantasia)) {
            if ($user->is_admin) {
                if (!$request->is('setup-unidade*')) {
                    return redirect()->route('onboarding.setup');
                }
            } else {
                abort(403, 'O sistema está aguardando o cadastro inicial da unidade pela MaiaTech.');
            }
        }

        // PRIORIDADE 2: Escolha do Módulo (Cards)
        elseif ($company->modules_active === 0) {
            if ($user->is_admin) {
                if (!$request->is('select-modules*')) {
                    return redirect()->route('modules.selection');
                }
            } else {
                abort(403, 'O sistema está aguardando a ativação do módulo pela MaiaTech.');
            }
        }

        // --- 3. CONTROLE DE ACESSO AOS MÓDULOS (SEGURANÇA DE ROTA) ---
        if ($company && $company->modules_active > 0) {

            // Caso 1: Plano APENAS ARENA (1) - Bloqueia rotas de BAR
            if ($company->modules_active == 1 && $request->is('bar*')) {
                return redirect()->route('dashboard')->with('error', 'O Módulo PDV System não faz parte do seu plano.');
            }

            // Caso 2: Plano APENAS PDV (2) - Bloqueia rotas de ARENA
            if ($company->modules_active == 2) {
                // Rotas que pertencem à Arena
                $arenaRoutes = ['dashboard', 'admin/reservas*', 'admin/arenas*', 'admin/financeiro*'];

                foreach ($arenaRoutes as $route) {
                    if ($request->is($route)) {
                        return redirect()->route('bar.dashboard');
                    }
                }
            }

            // Caso 3: COMBO FULL (3) - Não bloqueia nada, deixa passar para o próximo passo.
        }

        return $next($request);
    }
}
