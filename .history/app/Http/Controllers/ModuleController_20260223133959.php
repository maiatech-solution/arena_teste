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

        // Salva ou atualiza a empresa ID 1
        $company = CompanyInfo::updateOrCreate(['id' => 1], $validated);

        // 🚀 O PULO DO GATO:
        // Redirecionamos para 'admin.plans' para que o usuário seja OBRIGADO
        // a escolher entre Arena, Bar ou Combo antes de tentar entrar nos módulos.
        return redirect()->route('admin.plans')
            ->with('success', 'Unidade configurada! Agora selecione o plano de módulos para ativar seu acesso.');
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
         * 🎯 REGRA DE NAVEGAÇÃO AJUSTADA:
         * Mesmo sendo ADMIN, se o plano NÃO for Combo (3),
         * queremos pular direto para o módulo ativo.
         */

        // Se o plano for COMBO (3), mostramos os cards para escolha
        if ($company->modules_active == 3) {
            return view('admin.choose_module', compact('company'));
        }

        // Se o plano for apenas ARENA (1), vai direto
        if ($company->modules_active == 1) {
            return redirect()->route('dashboard');
        }

        // Se o plano for apenas PDV SYSTEM (2), vai direto
        if ($company->modules_active == 2) {
            return redirect()->route('bar.dashboard');
        }

        /** * Caso o plano seja 0 (ainda não definido) ou ocorra algo inesperado,
         * mostramos a tela de escolha para não travar o sistema.
         */
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

        // Bloqueia alteração se o plano já existe e o usuário não for admin
        if ($company && $company->modules_active > 0 && !$user->is_admin) {
            return redirect()->back()->with('error', 'Apenas administradores podem alterar o plano de módulos.');
        }

        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        $newModule = (int) $request->module;

        // Regras de validação de upgrade/downgrade para não-admins
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

        // Se a companhia não existir (segurança para banco limpo), cria a instância
        if (!$company) {
            $company = new CompanyInfo();
            $company->id = 1;
            $company->nome_fantasia = 'Unidade Principal';
        }

        // Salva o novo plano
        $company->modules_active = $newModule;
        $company->save();

        $msg = 'Plano ativado com sucesso!';

        // 🚀 REDIRECIONAMENTO INTELIGENTE FINAL:
        // Se ativou apenas Arena (1), vai para o dashboard da Arena
        if ($newModule == 1) {
            return redirect()->route('dashboard')->with('success', $msg);
        }

        // Se ativou apenas PDV (2), vai direto para o dashboard do Bar
        if ($newModule == 2) {
            return redirect()->route('bar.dashboard')->with('success', $msg);
        }

        // Se for o Combo (3), aí sim mandamos para a tela de escolha (cards)
        return redirect()->route('modules.selection')->with('success', $msg);
    }

    /**
     * EXTRA: Alterna a visualização rápida entre Arena e Bar
     */
    public function switch($target)
    {
        $company = CompanyInfo::first();
        $user = Auth::user();

        // Se você for ADMIN, o sistema deve deixar você trocar independente do plano
        if ($user->is_admin) {
            if ($target === 'bar' || $target === 'pdv') {
                return redirect()->route('bar.dashboard');
            }
            return redirect()->route('dashboard');
        }

        // Regra para usuários comuns
        if (!$company || $company->modules_active != 3) {
            return redirect()->back()->with('error', 'Troca de ambiente disponível apenas no plano Combo.');
        }

        if ($target === 'bar' || $target === 'pdv') {
            return redirect()->route('bar.dashboard');
        }

        return redirect()->route('dashboard');
    }
}
