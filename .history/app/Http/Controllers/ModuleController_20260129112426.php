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

        if (!empty($info->nome_fantasia)) {
            return redirect()->route('modules.selection');
        }

        return view('admin.setup_empresa', compact('info'));
    }

    /**
     * SALVAR PASSO 1: Grava os dados da unidade
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
     * Ajustado para permitir que ADMINS alterem o módulo a qualquer momento.
     */
    public function index()
    {
        $company = CompanyInfo::first();

        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        /**
         * REGRA DE MUDANÇA:
         * Se o módulo já está ativo E o usuário NÃO é admin, ele não pode trocar sozinho.
         * Se for admin, a tela de cards abre normalmente para upgrade/downgrade.
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
        // Apenas Admins podem alterar um módulo que já foi definido anteriormente
        $company = CompanyInfo::first();
        if ($company->modules_active > 0 && !Auth::user()->is_admin) {
            return redirect()->back()->with('error', 'Apenas administradores podem alterar o plano de módulos.');
        }

        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        if (!$company) {
            $company = new CompanyInfo();
            $company->id = 1;
            $company->nome_fantasia = 'Unidade Principal';
        }

        $company->modules_active = $request->module;
        $company->save();

        // Redirecionamento inteligente após a escolha
        $msg = 'Módulo atualizado com sucesso!';

        if ($request->module == 2) {
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
            return redirect()->back()->with('error', 'Troca de módulo disponível apenas no plano Combo.');
        }

        if ($target === 'pdv') {
            return redirect()->route('bar.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
