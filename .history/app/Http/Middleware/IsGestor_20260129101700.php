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

        // Se módulos_active for 0, redireciona os admins mestres para escolha
        if ($company && $company->modules_active === 0) {
            $masterAdmins = ['adriano@exemplo.com', 'marcos@exemplo.com']; // COLOQUE OS EMAILS REAIS AQUI

            if (in_array($user->email, $masterAdmins)) {
                if (!$request->is('select-modules*')) {
                    return redirect()->route('modules.selection');
                }
            } else {
                abort(403, 'O sistema está aguardando configuração do administrador.');
            }
        }

        // 3. Controle de Acesso aos Módulos (Se tentar entrar no Bar sem ter o módulo 2 ou 3)
        if ($company) {
            if ($company->modules_active == 1 && $request->is('bar*')) {
                abort(403, 'Módulo Bar não ativo.');
            }
            if ($company->modules_active == 2 && ($request->is('dashboard') || $request->is('admin*') || $request->is('financeiro*'))) {
                // Se o cara só tem BAR, e tentar entrar na Arena, redireciona pro Bar
                return redirect()->route('bar.dashboard');
            }
        }

        return $next($request);
    }
}
