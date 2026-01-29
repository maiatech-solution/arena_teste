<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyInfo;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * Exibe a tela de escolha (Arena, Bar ou Combo)
     */
    public function index()
    {
        $company = CompanyInfo::first();

        // Se o sistema já estiver configurado e a empresa tiver nome,
        // não faz sentido ficar nesta tela. Redireciona para o dashboard.
        if ($company && $company->modules_active > 0 && !empty($company->nome_fantasia)) {
            return $company->modules_active == 2
                ? redirect()->route('bar.dashboard')
                : redirect()->route('dashboard');
        }

        return view('admin.select_modules');
    }

    /**
     * Salva a decisão e inicia o fluxo de boas-vindas
     */
    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|in:1,2,3',
        ]);

        // Usamos updateOrCreate para garantir que trabalhamos sempre no ID 1
        $company = CompanyInfo::updateOrCreate(
            ['id' => 1],
            ['modules_active' => $request->module]
        );

        // Se a empresa ainda está sem nome (zerada), obriga a preencher o cadastro
        if (empty($company->nome_fantasia)) {
            return redirect()->route('admin.company.edit')
                ->with('success', 'Módulo ativado! Agora, complete os dados da sua unidade.');
        }

        // Se já tinha dados, vai direto para o dashboard correspondente
        return $request->module == 2
            ? redirect()->route('bar.dashboard')
            : redirect()->route('dashboard');
    }
}
