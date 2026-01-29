<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class BarUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $arenaId = auth()->user()->arena_id;

        // Filtra apenas Staff (Gestores e Admins) daquela unidade
        $query = User::where('arena_id', $arenaId)
            ->whereIn('role', ['gestor', 'admin']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(10);
        $pageTitle = "Equipe do";

        return view('bar.users.index', compact('users', 'search', 'pageTitle'));
    }

    public function create()
    {
        return view('bar.users.create');
    }

    public function store(Request $request)
    {
        // 1. Validação dos dados recebidos
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:gestor,admin'],
            'whatsapp_contact' => ['nullable', 'string', 'max:15'],
        ]);

        // 🛡️ REGRA DE HIERARQUIA:
        // Se o usuário logado NÃO for admin, o cargo criado será sempre 'gestor'
        // independente do que venha no request (previne manipulação de HTML).
        $finalRole = auth()->user()->role === 'admin' ? $request->role : 'gestor';

        // 2. Criação do usuário com os campos corretos da sua tabela
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'arena_id' => auth()->user()->arena_id, // Vincula à mesma unidade
            'role' => $finalRole,
            'whatsapp_contact' => $request->whatsapp_contact,
            'status' => 'active',
            // Campos padrão da sua tabela (vistos no seu dump anterior)
            'customer_qualification' => 'normal',
            'is_blocked' => 0,
            'is_vip' => 0,
            'no_show_count' => 0,
        ]);

        return redirect()->route('bar.users.index')->with('success', 'Colaborador adicionado à equipe com sucesso!');
    }
}
