<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyInfo;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * PASSO 1: Tela de Setup da Unidade (Layout Neutro)
     */
    public function setupUnidade()
    {
        $info = CompanyInfo::first() ?? new CompanyInfo();

        // Se já tem nome fantasia, pula para a seleção de módulos
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

        // Persiste no ID 1 para ser registro único
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

        // Se não tiver empresa cadastrada, volta para o passo 1
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        // Se já escolheu o módulo, vai para o dashboard correspondente
        if ($company->modules_active > 0) {
            return $company->modules_active == 2
                ? redirect()->route('bar.dashboard')
                : redirect()->route('dashboard');
        }

        return view('admin.select_modules');
    }

    /**
     * PASSO 3: Ativa o Módulo escolhido (Arena, PDV ou Combo)
     * Ajustado para evitar erro 1364 usando UPDATE em vez de INSERT
     */
    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        // Buscamos o registro que já foi criado no setup
        $company = CompanyInfo::first();

        if (!$company) {
            // Fallback: Se o registro sumiu, criamos um básico com nome obrigatório
            $company = new CompanyInfo();
            $company->id = 1;
            $company->nome_fantasia = 'Unidade Principal';
        }

        // Atualizamos apenas o módulo ativo (isso gera um SQL UPDATE)
        $company->modules_active = $request->module;
        $company->save();

        if ($request->module == 2) {
            return redirect()->route('bar.dashboard')->with('success', 'PDV System ativado!');
        }

        return redirect()->route('dashboard')->with('success', 'Sistema ativado com sucesso!');
    }
}
