<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyInfo;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * Exibe a tela de seleção de módulos.
     */
    public function index()
    {
        // Verifica se já existe uma escolha para não reexibir desnecessariamente
        $company = CompanyInfo::first();

        // Se já houver módulo e o nome da empresa estiver preenchido, manda para o dashboard
        if ($company && $company->modules_active > 0 && !empty($company->nome_fantasia)) {
            return redirect()->route('dashboard');
        }

        return view('admin.select_modules');
    }

    /**
     * Salva a escolha do módulo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|in:1,2,3',
        ]);

        // Atualiza ou Cria o registro único da empresa (ID 1)
        $company = CompanyInfo::updateOrCreate(
            ['id' => 1],
            ['modules_active' => $request->module]
        );

        // Se a empresa ainda não tem nome, obriga a preencher os dados básicos
        if (empty($company->nome_fantasia)) {
            return redirect()->route('admin.company.edit')
                ->with('success', 'Módulo ativado! Agora, complete os dados da sua empresa.');
        }

        // Se já tiver dados, redireciona conforme o módulo escolhido
        if ($request->module == 2) {
            return redirect()->route('bar.dashboard');
        }

        return redirect()->route('arena.dashboard');
    }
}
