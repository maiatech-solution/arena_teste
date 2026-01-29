<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyInfo;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * PASSO 1: Tela de Setup da Unidade (Layout Neutro)
     * Usado em cadastros novos que ainda não preencheram o nome fantasia.
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
     * 🚪 INDEX: Tela de NAVEGAÇÃO (Cards de Arena/Bar)
     * Esta função decide se mostra os cards ou se pula direto para o sistema.
     */
    public function index()
    {
        $company = CompanyInfo::first();

        // 1. VERIFICAÇÃO DE CADASTRO NOVO
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        /**
         * 🎯 REGRA DE NAVEGAÇÃO
         * Se for ADMIN ou se o plano for COMBO (3), mostra a tela de escolha (Cards).
         */
        if (Auth::user()->is_admin || $company->modules_active == 3) {
            return view('admin.choose_module', compact('company'));
        }

        /**
         * Se NÃO for admin e NÃO for combo, redirecionamos baseado no plano ativo.
         */
        if ($company->modules_active == 1) {
            return redirect()->route('dashboard');
        }

        if ($company->modules_active == 2) {
            return redirect()->route('bar.dashboard');
        }

        return view('admin.choose_module', compact('company'));
    }

    /**
     * ⚙️ GESTÃO TÉCNICA: Tela de Upgrade/Downgrade (Rádios)
     * Apenas o Admin Master acessa para mudar o plano do cliente.
     */
    public function managePlans()
    {
        if (!Auth::user()->is_admin) {
            return redirect()->route('modules.selection');
        }

        $company = CompanyInfo::first();
        // ESTA CARREGA A VIEW DE ESCOLHER PLANO (Upgrade)
        return view('admin.select_modules', compact('company'));
    }


    /**
     * SALVAR PASSO 2: Ativa ou Altera o Módulo (Utilizado na configuração de plano)
     */
    public function store(Request $request)
    {
        $company = CompanyInfo::first();
        $user = Auth::user();

        if ($company && $company->modules_active > 0 && !$user->is_admin) {
            return redirect()->back()->with('error', 'Apenas administradores podem alterar o plano de módulos.');
        }

        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        $newModule = (int) $request->module;

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
            $company->nome_fantasia = 'Unidade Principal';
        }

        $company->modules_active = $newModule;
        $company->save();

        $msg = 'Configuração de módulos atualizada com sucesso!';

        // Após salvar o plano, volta para a tela de gestão técnica (Rádios)
        return redirect()->route('admin.plans')->with('success', $msg);
    }

    /**
     * EXTRA: Alterna a visualização rápida entre Arena e Bar
     */
    public function switch($target)
    {
        $company = CompanyInfo::first();
        $user = Auth::user();

        if (!$user->is_admin && (!$company || $company->modules_active != 3)) {
            return redirect()->back()->with('error', 'Troca de ambiente disponível apenas no plano Combo.');
        }

        if ($target === 'bar' || $target === 'pdv') {
            return redirect()->route('bar.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
