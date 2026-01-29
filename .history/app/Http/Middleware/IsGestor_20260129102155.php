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
        if (!Auth::check()) {
            return redirect()->route('customer.login');
        }

        $user = Auth::user();

        // 1. Verifica se é gestor/admin
        if (!in_array($user->role, ['gestor', 'admin'])) {
            abort(403, 'Acesso não autorizado.');
        }

        // 2. Verifica configuração da Empresa
        $company = CompanyInfo::first();

        // Emails dos Administradores Mestres (Ajuste conforme seu Seeder)
        $masterAdmins = ['drikomaia89@gmail.com', 'marcosbleal26@gmail.com'];

        // REGRA A: Se módulos_active for 0, redireciona para escolha de módulos
        if (!$company || $company->modules_active === 0) {
            if (in_array($user->email, $masterAdmins)) {
                if (!$request->is('select-modules*')) {
                    return redirect()->route('modules.selection');
                }
            } else {
                abort(403, 'O sistema está aguardando a configuração inicial do administrador.');
            }
        }

        // REGRA B: Se já escolheu o módulo, mas não preencheu os dados da empresa (nome_fantasia vazio)
        // Redireciona para a tela que você já tem: admin.company.edit
        if ($company && $company->modules_active > 0 && empty($company->nome_fantasia)) {
            if (!$request->is('admin/dados-empresa*')) { // Evita loop infinito na própria página de edição
                return redirect()->route('admin.company.edit')
                    ->with('info', 'Por favor, complete o cadastro da empresa para liberar o sistema.');
            }
        }

        // 3. Controle de Acesso aos Módulos (Bloqueio de acesso cruzado)
        if ($company) {
            // Se tem só Arena (1), bloqueia acesso ao Bar
            if ($company->modules_active == 1 && $request->is('bar*')) {
                abort(403, 'O Módulo Bar não está contratado para esta unidade.');
            }

            // Se tem só Bar (2), bloqueia acesso à Arena (Dashboard, admin, financeiro)
            if ($company->modules_active == 2 && ($request->is('dashboard') || $request->is('admin*') || $request->is('financeiro*'))) {
                return redirect()->route('bar.dashboard');
            }
        }

        return $next($request);
    }
}
