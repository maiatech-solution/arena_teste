<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\CompanyInfo;
use App\Models\Arena;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BarCompanyController extends Controller
{
    /**
     * Carrega os dados da empresa (Arena) para edição
     */
    public function edit()
    {
        // Prioridade para CompanyInfo, que é onde estão os dados detalhados
        $company = CompanyInfo::first();

        // Se não encontrar na CompanyInfo, tenta na Arena
        if (!$company) {
            $company = Arena::first();
        }

        return view('bar.company.edit', compact('company'));
    }

    /**
     * Atualiza os dados no banco
     */
    public function update(Request $request)
    {
        // Buscamos o registro para atualizar (usando a mesma lógica do edit)
        $company = CompanyInfo::first();

        if (!$company) {
            $company = Arena::first();
        }

        // 1. Validação com os nomes EXATOS dos novos inputs
        $request->validate([
            'nome_fantasia'    => 'required|string|max:255',
            'cnpj'             => 'nullable|string|max:18',
            'email_contato'    => 'nullable|email|max:255',
            'whatsapp_suporte' => 'required|string|max:15',
            'cep'              => 'nullable|string|max:9',
            'logradouro'       => 'required|string|max:255',
            'numero'           => 'required|string|max:20',
            'bairro'           => 'required|string|max:100',
            'cidade'           => 'required|string|max:100',
            'estado'           => 'required|string|max:2',
            'logo'             => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // 2. Mapeamento dos campos para o banco de dados
        $data = $request->only([
            'nome_fantasia',
            'cnpj',
            'email_contato',
            'whatsapp_suporte',
            'cep',
            'logradouro',
            'numero',
            'bairro',
            'cidade',
            'estado'
        ]);

        // 3. Gerenciamento do Logotipo (caso decida usar no futuro, já fica pronto)
        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        // 4. Executa a atualização no banco de dados
        $company->update($data);

        return redirect()->route('bar.company.edit')->with('success', 'Informações da unidade atualizadas com sucesso!');
    }
}
