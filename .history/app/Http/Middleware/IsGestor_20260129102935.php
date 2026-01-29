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

        // Emails dos Administradores Mestres (MaiaTech Solution)
        $masterAdmins = ['drikomaia89@gmail.com', 'marcosbleal26@gmail.com'];

        // --- NOVA ORDEM DE PRIORIDADE ---

        // PRIORIDADE 1: Se a empresa não tem nome (zerada), obriga a preencher os dados
        if (!$company || empty($company->nome_fantasia)) {
            if (in_array($user->email, $masterAdmins)) {
                // Se o admin tentar acessar qualquer coisa que não seja a tela de edição, redireciona
                if (!$request->is('admin/dados-empresa*')) {
                    return redirect()->route('admin.company.edit')
                        ->with('info', 'Bem-vindo! Comece preenchendo as informações da sua empresa.');
                }
            } else {
                abort(403, 'O sistema está aguardando o cadastro inicial da empresa pelo administrador.');
            }
        }

        // PRIORIDADE 2: Se já tem dados da empresa mas modules_active for 0, redireciona para escolha de módulos
        elseif ($company->modules_active === 0) {
            if (in_array($user->email, $masterAdmins)) {
                if (!$request->is('select-modules*')) {
                    return redirect()->route('modules.selection');
                }
            } else {
                abort(403, 'O sistema está aguardando a ativação do módulo pelo administrador.');
            }
        }

        // 3. Controle de Acesso aos Módulos (Bloqueio de acesso cruzado)
        if ($company && $company->modules_active > 0) {

            // Bloqueio para quem tem apenas Arena Booking (1)
            if ($company->modules_active == 1 && $request->is('bar*')) {
                abort(403, 'O Módulo PDV System não está ativo para esta unidade.');
            }

            // Bloqueio para quem tem apenas PDV System (2)
            if ($company->modules_active == 2 && ($request->is('dashboard') || $request->is('admin*') || $request->is('financeiro*'))) {
                // Evita redirecionar se já estiver tentando acessar os dados da empresa (que fica em /admin)
                if (!$request->is('admin/dados-empresa*')) {
                    return redirect()->route('bar.dashboard');
                }
            }
        }

        return $next($request);
    }
}
