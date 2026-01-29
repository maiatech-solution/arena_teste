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

        // Se já tiver nome fantasia, o sistema pula para a seleção de módulos
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

        // Se não houver empresa ou nome fantasia, volta para o setup inicial
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        /**
         * REGRA DE ACESSO:
         * A tela de seleção só abre se:
         * 1. O módulo ainda for zero (novo cliente).
         * 2. O usuário logado for ADMIN (Maia/Marcos).
         * Caso contrário, manda direto para o dashboard ativo.
         */
        if ($company->modules_active > 0 && !Auth::user()->is_admin) {
            return $company->modules_active == 2
                ? redirect()->route('bar.dashboard')
                : redirect()->route('dashboard');
        }

        return view('admin.select_modules', compact('company'));
    }

    /**
     * SALVAR PASSO 2: Ativa ou Altera o Módulo
     */
    public function store(Request $request)
    {
        $company = CompanyInfo::first();

        // 🛡️ SEGURANÇA: Se já houver módulo, apenas ADMINS (Maia/Marcos) podem trocar.
        if ($company->modules_active > 0 && !Auth::user()->is_admin) {
            return redirect()->back()->with('error', 'Apenas administradores podem alterar o plano de módulos.');
        }

        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        $newModule = (int) $request->module;

        /**
         * 🛡️ REGRA DE INTEGRIDADE (PROTEÇÃO CONTRA DOWNGRADE):
         * - Se já é Arena (1), não pode mudar para apenas PDV (2). Deve ser Combo (3).
         * - Se já é PDV (2), não pode mudar para apenas Arena (1). Deve ser Combo (3).
         */
        if ($company->modules_active == 1 && $newModule == 2) {
            return redirect()->back()->with('error', 'Para adicionar o Bar mantendo sua Arena, escolha o Combo Full.');
        }

        if ($company->modules_active == 2 && $newModule == 1) {
            return redirect()->back()->with('error', 'Para adicionar a Arena mantendo seu Bar, escolha o Combo Full.');
        }

        // Atualiza a empresa
        if (!$company) {
            $company = new CompanyInfo();
            $company->id = 1;
            $company->nome_fantasia = 'Unidade Principal';
        }

        $company->modules_active = $newModule;
        $company->save();

        $msg = 'Módulo ativado com sucesso!';

        // Redirecionamento baseado na escolha
        if ($newModule == 2) {
            return redirect()->route('bar.dashboard')->with('success', $msg);
        }

        return redirect()->route('dashboard')->with('success', $msg);
    }

    /**
     * EXTRA: Alterna a visualização entre Arena e PDV (Somente para Módulo 3)
     */
    public function switch($target)
    {
        $company = CompanyInfo::first();

        if (!$company || $company->modules_active != 3) {
            return redirect()->back()->with('error', 'Troca de módulo disponível apenas no plano Combo Full.');
        }

        if ($target === 'pdv') {
            return redirect()->route('bar.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
