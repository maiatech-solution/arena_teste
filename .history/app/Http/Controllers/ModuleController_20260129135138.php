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
     * PASSO 2 / LOGIN: Decisão de destino
     * Diferencia entre "Gerenciar Plano" (Admin) e "Navegar" (Combo).
     */
    public function index()
    {
        $company = CompanyInfo::first();

        // 1. VERIFICAÇÃO DE CADASTRO NOVO
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('onboarding.setup');
        }

        /**
         * 🎯 REGRA DE ACESSO PÓS-LOGIN
         * Se for ADMIN ou se o plano for COMBO (3), mostra a tela de NAVEGAÇÃO.
         * Assim você (como admin) e o cliente Combo podem escolher em qual entrar.
         */
        if (Auth::user()->is_admin || $company->modules_active == 3) {
            return view('admin.choose_module', compact('company'));
        }

        /**
         * Se NÃO for admin e NÃO for combo, redirecionamos direto para o módulo ativo.
         */

        // Se o módulo for 1 (Arena) redireciona direto
        if ($company->modules_active == 1) {
            return redirect()->route('dashboard');
        }

        // Se o módulo for 2 (Bar) redireciona direto
        if ($company->modules_active == 2) {
            return redirect()->route('bar.dashboard');
        }

        // Fallback de segurança: se nada bater, manda para a gestão de planos
        return view('admin.select_modules', compact('company'));
    }


    /**
     * SALVAR PASSO 2: Ativa ou Altera o Módulo (Utilizado na configuração de plano)
     * Ajustado para permitir Downgrade APENAS por Admins Master (Maia/Marcos).
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
         * 🛡️ REGRA DE INTEGRIDADE E PROTEÇÃO:
         */
        if (!$user->is_admin && $company) {
            // Impede trocar Arena direto para PDV (Downgrade/Perda de dados visual)
            if ($company->modules_active == 1 && $newModule == 2) {
                return redirect()->back()->with('error', 'Para adicionar o Bar mantendo sua Arena, escolha o Combo Full.');
            }

            // Impede trocar PDV direto para Arena (Downgrade/Perda de dados visual)
            if ($company->modules_active == 2 && $newModule == 1) {
                return redirect()->back()->with('error', 'Para adicionar a Arena mantendo seu Bar, escolha o Combo Full.');
            }

            // Impede que o Gestor reduza o Combo para um módulo simples sozinho
            if ($company->modules_active == 3 && $newModule < 3) {
                return redirect()->back()->with('error', 'Downgrade deve ser solicitado ao suporte.');
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

        // Redirecionamento inteligente baseado na nova escolha
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
        if ($target === 'bar' || $target === 'pdv') {
            return redirect()->route('bar.dashboard');
        }

        // Redireciona para a Arena (Padrão)
        return redirect()->route('dashboard');
    }
}
