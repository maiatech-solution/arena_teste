<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
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
        // Buscamos os dados da tabela que você usa na Arena
        $company = \App\Models\CompanyInfo::first();

        // Se não encontrar na CompanyInfo, tenta na Arena (ajuste conforme seu banco)
        if (!$company) {
            $company = \App\Models\Arena::first();
        }

        return view('bar.company.edit', compact('company'));
    }

    /**
     * Atualiza os dados no banco
     */
    public function update(Request $request)
    {
        $company = Arena::first();

        $request->validate([
            'name' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'phone' => 'required|string|max:15',
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only(['name', 'cnpj', 'phone', 'address', 'city']);

        // Upload de Logo (opcional)
        if ($request->hasFile('logo')) {
            // Deleta a logo antiga se existir
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $company->update($data);

        return redirect()->route('bar.company.edit')->with('success', 'Dados da empresa atualizados com sucesso!');
    }
}
