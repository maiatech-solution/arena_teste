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
        // Buscamos a empresa ou criamos uma instância vazia para não dar erro de "variable undefined"
        $info = CompanyInfo::first() ?? new CompanyInfo();

        // Se já tiver nome fantasia, pula para a seleção de módulos
        if (!empty($info->nome_fantasia)) {
            return redirect()->route('modules.selection');
        }

        return view('admin.setup_empresa', compact('info'));
    }

    public function index()
    {
        $company = CompanyInfo::first();

        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        if ($company->modules_active > 0) {
            return $company->modules_active == 2
                ? redirect()->route('bar.dashboard')
                : redirect()->route('dashboard');
        }

        // A view de seleção de módulos NÃO precisa da variável $info
        return view('admin.select_modules');
    }
    /**
     * Salva o Setup Inicial da Unidade com endereço completo
     */
    public function setupStore(Request $request)
    {
        $validated = $request->validate([
            'nome_fantasia'    => 'required|string|max:255',
            'cnpj'             => 'nullable|string|max:20',
            'whatsapp_suporte' => 'nullable|string|max:20',
            // Campos de Endereço adicionados:
            'cep'              => 'nullable|string|max:10',
            'logradouro'       => 'nullable|string|max:255',
            'numero'           => 'nullable|string|max:20',
            'bairro'           => 'nullable|string|max:100',
            'cidade'           => 'nullable|string|max:100',
            'estado'           => 'nullable|string|max:2',
        ]);

        // Persiste no ID 1 para manter como registro único da unidade
        CompanyInfo::updateOrCreate(['id' => 1], $validated);

        // Redireciona para a tela de escolha de módulos (Cards)
        return redirect()->route('modules.selection')
            ->with('success', 'Informações salvas! Agora escolha o módulo de operação.');
    }

    /**
     * PASSO 2: Tela de Seleção de Módulos (Cards)
     */
    public function index()
    {
        $company = CompanyInfo::first();

        // Se não tiver empresa cadastrada, volta para o passo 1
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        // Se já escolheu o módulo, redireciona para o dashboard correto
        if ($company->modules_active > 0) {
            return $company->modules_active == 2
                ? redirect()->route('bar.dashboard')
                : redirect()->route('dashboard');
        }

        return view('admin.select_modules');
    }

    /**
     * Ativa o Módulo escolhido
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

        // Redirecionamento baseado na escolha
        if ($request->module == 2) {
            return redirect()->route('bar.dashboard')->with('success', 'Módulo PDV System ativado!');
        }

        return redirect()->route('dashboard')->with('success', 'Módulo Arena Booking ativado!');
    }
}
