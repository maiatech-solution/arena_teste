<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyInfo;
use Illuminate\Http\Request;

class CompanyInfoController extends Controller
{
    public function edit()
    {
        // Pega o primeiro e único registro da empresa
        $info = CompanyInfo::first() ?? new CompanyInfo();
        return view('admin.company.edit', compact('info'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nome_fantasia' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:20',
            'whatsapp_suporte' => 'nullable|string|max:20',
            'email_contato' => 'nullable|email|max:255',
            'cep' => 'nullable|string|max:9',
            'logradouro' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:20',
            'bairro' => 'nullable|string|max:100',
            'cidade' => 'nullable|string|max:100',
            'estado' => 'nullable|string|max:2',
        ]);

        // Atualiza o ID 1 ou cria se não existir
        CompanyInfo::updateOrCreate(['id' => 1], $validated);

        return redirect()->back()->with('success', 'Dados da Elite Soccer atualizados com sucesso!');
    }
}