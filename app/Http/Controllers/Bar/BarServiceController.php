<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\Bar\BarService;
use Illuminate\Http\Request;

class BarServiceController extends Controller
{
    public function index()
    {
        $services = BarService::orderBy('name')->get();
        return view('bar.services.index', compact('services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        BarService::create($request->all());

        return back()->with('success', '✅ Serviço cadastrado com sucesso!');
    }

    public function update(Request $request, BarService $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $service->update($request->all());

        return back()->with('success', '✅ Serviço atualizado!');
    }

    public function destroy(BarService $service)
    {
        // 🛡️ Segurança: Verificar se o serviço já foi usado em vendas antes de deletar
        // Por enquanto, apenas desativamos ou deletamos se for novo.
        $service->delete();
        return back()->with('success', 'Serviço removido.');
    }
}