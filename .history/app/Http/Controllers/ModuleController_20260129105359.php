<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyInfo;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * PASSO 1: Tela de Setup da Unidade (Layout Neutro)
     * Exibe o formulário de cadastro inicial sem menus.
     */
    public function setupUnidade()
    {
        // Buscamos a empresa ou criamos uma instância vazia para evitar erro de variável na view
        $info = CompanyInfo::first() ?? new CompanyInfo();

        // Se já tiver nome fantasia, o sistema pula automaticamente para a seleção de módulos
        if (!empty($info->nome_fantasia)) {
            return redirect()->route('modules.selection');
        }

        return view('admin.setup_empresa', compact('info'));
    }

    /**
     * SALVAR PASSO 1: Grava os dados da unidade e segue para módulos
     */
    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        // Em vez de updateOrCreate direto, vamos garantir que pegamos o que foi feito no setup
        $company = CompanyInfo::first();

        if (!$company) {
            // Fallback de segurança: se não existir, cria com um nome genérico para evitar o erro 1364
            $company = new CompanyInfo();
            $company->id = 1;
            $company->nome_fantasia = 'Unidade Principal';
        }

        // Atualiza apenas o módulo ativo
        $company->modules_active = $request->module;
        $company->save();

        // Redirecionamento baseado na escolha
        if ($request->module == 2) {
            return redirect()->route('bar.dashboard')->with('success', 'PDV System ativado com sucesso!');
        }

        return redirect()->route('dashboard')->with('success', 'Sistema configurado com sucesso!');
    }

    /**
     * PASSO 2: Tela de Seleção de Módulos (Cards)
     */
    public function index()
    {
        $company = CompanyInfo::first();

        // Proteção: Se o cara tentar acessar a URL de módulos sem ter nome da empresa, volta pro setup
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        // Se o módulo já foi escolhido, redireciona para o painel correto (evita re-seleção acidental)
        if ($company->modules_active > 0) {
            return $company->modules_active == 2
                ? redirect()->route('bar.dashboard')
                : redirect()->route('dashboard');
        }

        return view('admin.select_modules');
    }

    /**
     * SALVAR PASSO 2: Ativa o Módulo (Arena, PDV ou Combo)
     */
    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        $company = CompanyInfo::updateOrCreate(
            ['id' => 1],
            ['modules_active' => $request->module]
        );

        // Redirecionamento baseado na escolha do gestor
        if ($request->module == 2) {
            return redirect()->route('bar.dashboard')->with('success', 'PDV System ativado com sucesso!');
        }

        // Se for 1 (Arena) ou 3 (Combo), o destino padrão é o Dashboard da Arena
        return redirect()->route('dashboard')->with('success', 'Sistema configurado com sucesso!');
    }
}
