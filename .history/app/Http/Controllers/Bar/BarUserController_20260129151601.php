<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class BarUserController extends Controller
{
    public function index()
    {
        // Filtra usuários da mesma unidade/empresa
        $users = User::where('unit_id', auth()->user()->unit_id)->get();
        return view('bar.users.index', compact('users'));
    }

    public function create()
    {
        return view('bar.users.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'unit_id' => auth()->user()->unit_id,
            'usertype' => 'gestor', // Ou a lógica de cargo que você preferir
        ]);

        return redirect()->route('bar.users.index')->with('success', 'Colaborador cadastrado!');
    }
}
