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
        $roleFilter = $request->input('role_filter');

        // ✅ Ajustado para sua coluna real: arena_id
        $arenaId = auth()->user()->arena_id;

        // Inicia a query filtrando pela arena do gestor logado
        $query = User::where('arena_id', $arenaId);

        // 🔍 Busca (Nome, Email ou WhatsApp)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('whatsapp_contact', 'LIKE', "%{$search}%"); // ✅ Ajustado
            });
        }

        // 🎭 Filtro por Role
        if ($roleFilter) {
            if ($roleFilter === 'gestor') {
                $query->whereIn('role', ['gestor', 'admin']);
            } else {
                $query->where('role', $roleFilter);
            }
        }

        // Paginação mantendo os filtros na URL
        $users = $query->paginate(10);

        $pageTitle = "Gestão de";

        return view('bar.users.index', compact('users', 'search', 'roleFilter', 'pageTitle'));
    }

    public function create(Request $request)
    {
        $role = $request->input('role', 'gestor');
        return view('bar.users.create', compact('role'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:gestor,cliente'],
            'whatsapp_contact' => ['nullable', 'string', 'max:15'], // ✅ Ajustado
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'arena_id' => auth()->user()->arena_id, // ✅ Ajustado
            'role' => $request->role,
            'whatsapp_contact' => $request->whatsapp_contact, // ✅ Ajustado
            'customer_qualification' => 'normal',
            'is_blocked' => 0,
            'is_vip' => 0,
        ]);

        return redirect()->route('bar.users.index')->with('success', 'Colaborador cadastrado com sucesso!');
    }

    public function edit(User $user)
    {
        // Segurança: Bloqueia acesso a users de outras arenas
        if ($user->arena_id !== auth()->user()->arena_id) {
            return redirect()->route('bar.users.index')->with('error', 'Acesso negado.');
        }

        return view('bar.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->arena_id !== auth()->user()->arena_id) abort(403);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'string'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'whatsapp_contact' => $request->whatsapp_contact, // ✅ Ajustado
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('bar.users.index')->with('success', 'Dados atualizados!');
    }

    public function destroy(User $user)
    {
        if ($user->arena_id !== auth()->user()->arena_id) abort(403);
        if ($user->id === auth()->id()) return back()->with('error', 'Auto-exclusão proibida.');

        $user->delete();
        return redirect()->route('bar.users.index')->with('success', 'Usuário removido!');
    }
}
