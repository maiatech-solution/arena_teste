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
        // 1. Limpeza (Sanitização)
        // Remove parênteses, traços e pontos antes de validar e salvar
        if ($request->filled('whatsapp_suporte')) {
            $request->merge(['whatsapp_suporte' => preg_replace('/\D/', '', $request->whatsapp_suporte)]);
        }
        if ($request->filled('cnpj')) {
            $request->merge(['cnpj' => preg_replace('/\D/', '', $request->cnpj)]);
        }
        if ($request->filled('cep')) {
            $request->merge(['cep' => preg_replace('/\D/', '', $request->cep)]);
        }

        // 2. Validação Estrita
        $validated = $request->validate([
            'nome_fantasia'    => 'required|string|max:255',
            'cnpj'             => 'nullable|numeric|digits:14', // CNPJ: 14 números
            'whatsapp_suporte' => 'nullable|numeric|digits:11', // DDD + 9 dígitos
            'email_contato'    => 'nullable|email|max:255',
            'cep'              => 'nullable|numeric|digits:8',  // CEP: 8 números
            'logradouro'       => 'nullable|string|max:255',
            'numero'           => 'nullable|string|max:20',
            'bairro'           => 'nullable|string|max:100',
            'cidade'           => 'nullable|string|max:100',
            'estado'           => 'nullable|string|max:2',
        ], [
            // Mensagens personalizadas para orientar o gestor
            'whatsapp_suporte.digits' => 'O WhatsApp deve ter exatamente 11 números (Ex: 91988887777).',
            'cnpj.digits'             => 'O CNPJ deve ter exatamente 14 números.',
            'cep.digits'              => 'O CEP deve ter exatamente 8 números.',
            'numeric'                 => 'Este campo deve conter apenas números.',
        ]);

        // 3. Persistência (Sempre no ID 1 para ser registro único)
        CompanyInfo::updateOrCreate(['id' => 1], $validated);

        return redirect()->back()->with('success', 'Dados da Elite Soccer atualizados com sucesso!');
    }
}