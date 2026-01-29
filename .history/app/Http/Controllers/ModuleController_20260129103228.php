<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CompanyInfo;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    public function index()
    {
        $company = CompanyInfo::first();

        // 1. Se não tiver empresa cadastrada ou nome vazio, manda cadastrar primeiro
        // Isso garante que a identidade da unidade venha antes da escolha do módulo
        if (!$company || empty($company->nome_fantasia)) {
            return redirect()->route('admin.company.edit')
                ->with('info', 'Por favor, preencha os dados da empresa antes de selecionar o módulo.');
        }

        // 2. Se já escolheu o módulo e não é admin mestre tentando trocar, vai para o dashboard
        if ($company->modules_active > 0) {
            return $company->modules_active == 2
                ? redirect()->route('bar.dashboard')
                : redirect()->route('dashboard');
        }

        return view('admin.select_modules');
    }

    public function store(Request $request)
    {
        // Validação estrita dos módulos: 1-Arena, 2-PDV, 3-Combo
        $request->validate([
            'module' => 'required|in:1,2,3'
        ]);

        // Atualizamos o registro único da unidade
        // Usamos updateOrCreate para garantir que o ID 1 receba a configuração
        $company = CompanyInfo::updateOrCreate(
            ['id' => 1],
            ['modules_active' => $request->module]
        );

        // Redirecionamento inteligente pós-escolha
        // Se escolheu apenas PDV System (2), manda para o bar
        if ($request->module == 2) {
            return redirect()->route('bar.dashboard')->with('success', 'Módulo PDV System ativado com sucesso!');
        }

        // Se escolheu Arena (1) ou Combo (3), manda para o dashboard da Arena
        return redirect()->route('dashboard')->with('success', 'Módulo ativado com sucesso!');
    }
}
