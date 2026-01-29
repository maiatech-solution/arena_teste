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

        // 1. Setup inicial se não houver empresa
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        // 2. Se for Admin (Maia/Marcos), você quer a tela de GERENCIAR (Upgrade/Downgrade)
        if (Auth::user()->is_admin) {
            return view('admin.select_modules', compact('company'));
        }

        // 3. Se NÃO for Admin, mas for COMBO (3), mostra a tela de NAVEGAÇÃO
        if ($company->modules_active == 3) {
            return view('admin.choose_module', compact('company'));
        }

        // 4. Se for Módulo Único, vai direto
        return $company->modules_active == 2
            ? redirect()->route('bar.dashboard')
            : redirect()->route('dashboard');
    }

    public function switch($target)
    {
        // Apenas redireciona para o ambiente escolhido
        return $target === 'bar'
            ? redirect()->route('bar.dashboard')
            : redirect()->route('dashboard');
    }


    /**
     * SALVAR PASSO 2: Ativa ou Altera o Módulo (Utilizado na configuração de plano)
     */
    public function store(Request $request)
    {
        $company = CompanyInfo::first();
        $user = Auth::user();

        // 🛡️ SEGURANÇA: Se já houver módulo, apenas ADMINS (Maia/Marcos) podem trocar o plano raiz.
        if ($company && $company->modules_active > 0 && !$user->is_admin) {
            return redirect()->back()->with('error', 'Apenas administradores podem alterar o plano de módulos.');
        }

        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        $newModule = (int) $request->module;

        /**
         * 🛡️ REGRA DE INTEGRIDADE: Impede downgrades ou trocas cruzadas indevidas por gestores comuns.
         */
        if (!$user->is_admin && $company) {
            if ($company->modules_active == 1 && $newModule == 2) {
                return redirect()->back()->with('error', 'Para adicionar o Bar mantendo sua Arena, escolha o Combo Full.');
            }

            if ($company->modules_active == 2 && $newModule == 1) {
                return redirect()->back()->with('error', 'Para adicionar a Arena mantendo seu Bar, escolha o Combo Full.');
            }

            if ($company->modules_active == 3 && $newModule < 3) {
                return redirect()->back()->with('error', 'Downgrade de plano deve ser solicitado ao suporte.');
            }
        }

        if (!$company) {
            $company = new CompanyInfo();
            $company->id = 1;
        }

        $company->modules_active = $newModule;
        $company->save();

        $msg = 'Plano de módulos atualizado com sucesso!';

        // Redirecionamento após salvar a escolha do plano
        if ($newModule == 2) {
            return redirect()->route('bar.dashboard')->with('success', $msg);
        }

        if ($newModule == 3) {
            return redirect()->route('modules.selection')->with('success', $msg);
        }

        return redirect()->route('dashboard')->with('success', $msg);
    }

    /**
     * EXTRA: Alterna a visualização rápida entre Arena e Bar
     */
    public function switch($target)
    {
        $company = CompanyInfo::first();
        $user = Auth::user();

        // Permite a troca se for Combo (3) ou se for o Admin Master
        if (!$user->is_admin && (!$company || $company->modules_active != 3)) {
            return redirect()->back()->with('error', 'Troca de ambiente disponível apenas no plano Combo.');
        }

        // Redireciona para o Bar
        if ($target === 'bar') {
            return redirect()->route('bar.dashboard');
        }

        // Redireciona para a Arena (Padrão)
        return redirect()->route('dashboard');
    }
}
