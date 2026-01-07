<?php

namespace App\Http\Controllers\Admin; // 🎯 PRECISA SER EXATAMENTE ASSIM

use App\Http\Controllers\Controller;
use App\Models\Arena;
use Illuminate\Http\Request;

class ArenaController extends Controller
{
    public function index()
    {
        $arenas = Arena::all();
        // Verifique se a pasta da view existe em: resources/views/admin/arenas/index.blade.php
        return view('admin.quadras.index', compact('arenas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Arena::create([
            'name' => $request->name,
            'is_active' => true
        ]);

        return redirect()->back()->with('success', 'Arena cadastrada com sucesso!');
    }
}