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
    public function setupStore(Request $request)
    {
        $validated = $request->validate([
            'nome_fantasia'    => 'required|string|max:255',
            'cnpj'             => 'nullable|string|max:20',
            'whatsapp_suporte' => 'nullable|string|max:20',
            'cep'              => 'nullable|string|max:10',
            'logradouro'       => 'nullable|string|max:255',
            'numero'           => 'nullable|string|max:20',
            'bairro'           => 'nullable|string|max:100',
            'cidade'           => 'nullable|string|max:100',
            'estado'           => 'nullable|string|max:2',
        ]);

        // Persiste sempre no ID 1 para garantir registro único da unidade
        CompanyInfo::updateOrCreate(['id' => 1], $validated);

        return redirect()->route('modules.selection')
            ->with('success', 'Informações salvas! Agora escolha o módulo de operação.');
    }

    /**
     * PASSO 2: Tela de Seleção de Módulos (Cards)
     */
    public function index()
    {
        $company = CompanyInfo::first();

        // Proteção: Se tentar acessar módulos sem ter nome da empresa, volta pro setup
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        // Se o módulo já foi escolhido, redireciona para o painel correto
        if ($company->modules_active > 0) {
            return $company->modules_active == 2
                ? redirect()->route('bar.dashboard')
                : redirect()->route('dashboard');
        }

        return view('admin.select_modules');
    }

    /**
     * SALVAR PASSO 2: Ativa o Módulo (Arena, PDV ou Combo)
     * Ajustado para dar UPDATE e não INSERT (evita erro de campo obrigatório 1364)
     */
    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        $company = CompanyInfo::first();

        if (!$company) {
            $company = new CompanyInfo();
            $company->id = 1;
            $company->nome_fantasia = 'Unidade Principal';
        }

        // Atualiza apenas o módulo ativo
        $company->modules_active = $request->module;
        $company->save();

        if ($request->module == 2) {
            return redirect()->route('bar.dashboard')->with('success', 'PDV System ativado com sucesso!');
        }

        return redirect()->route('dashboard')->with('success', 'Sistema ativado com sucesso!');
    }

    /**
     * EXTRA: Alterna a visualização entre Arena e PDV
     * Exclusivo para clientes que contrataram o Combo Full (Módulo 3)
     */
    public function switch($target)
    {
        $company = CompanyInfo::first();

        // Segurança: Se não for combo (3), não permite a troca manual
        if (!$company || $company->modules_active != 3) {
            return redirect()->back()->with('error', 'Troca de módulo disponível apenas no plano Combo.');
        }

        if ($target === 'pdv') {
            return redirect()->route('bar.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
