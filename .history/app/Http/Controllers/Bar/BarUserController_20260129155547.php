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
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:gestor,admin'],
            'whatsapp_contact' => ['nullable', 'string', 'max:15'],
        ]);

        // 🛡️ Segurança: Somente admin cria outro admin. Gestor só cria gestor.
        $finalRole = auth()->user()->role === 'admin' ? $request->role : 'gestor';

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'arena_id' => auth()->user()->arena_id,
            'role' => $finalRole,
            'whatsapp_contact' => $request->whatsapp_contact,
            'status' => 'active',
            'customer_qualification' => 'normal',
            'is_blocked' => 0,
            'is_vip' => 0,
            'no_show_count' => 0,
        ]);

        return redirect()->route('bar.users.index')->with('success', 'Colaborador adicionado à equipe!');
    }

    public function edit(User $user)
    {
        // 🛡️ SEGURANÇA MÁXIMA: Impede que Gestor abra a edição de um Admin
        if (auth()->user()->role !== 'admin' && $user->role === 'admin') {
            return redirect()->route('bar.users.index')->with('error', 'Segurança: Gestores não podem editar Administradores.');
        }

        // Segurança de Arena
        if ($user->arena_id !== auth()->user()->arena_id) {
            return redirect()->route('bar.users.index')->with('error', 'Acesso negado.');
        }

        return view('bar.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // 🛡️ SEGURANÇA MÁXIMA: Bloqueia atualização se um Gestor tentar forçar via POST
        if (auth()->user()->role !== 'admin' && $user->role === 'admin') {
            abort(403, 'Ação não permitida para o seu nível de acesso.');
        }

        if ($user->arena_id !== auth()->user()->arena_id) abort(403);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'whatsapp_contact' => ['nullable', 'string', 'max:15'],
            'role' => ['required', 'in:gestor,admin'],
        ]);

        // 🛡️ Bloqueia alteração de cargo se não for Admin
        $userAuthRole = strtolower(trim(auth()->user()->role));
        $finalRole = ($userAuthRole === 'admin') ? $request->role : $user->role;

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $finalRole,
            'whatsapp_contact' => $request->whatsapp_contact,
        ]);

        if ($request->filled('password')) {
            $request->validate(['password' => ['confirmed', Rules\Password::defaults()]]);
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('bar.users.index')->with('success', 'Dados atualizados com sucesso!');
    }

    public function destroy(User $user)
    {
        // 🛡️ SEGURANÇA MÁXIMA: Gestor não pode excluir Administrador
        if (auth()->user()->role !== 'admin' && $user->role === 'admin') {
            return redirect()->route('bar.users.index')->with('error', 'Ação proibida: Somente administradores podem remover outros administradores.');
        }

        // Impede auto-exclusão
        if ($user->id === auth()->id()) {
            return redirect()->route('bar.users.index')->with('error', 'Você não pode excluir sua própria conta.');
        }

        if ($user->arena_id !== auth()->user()->arena_id) abort(403);

        $user->delete();
        return redirect()->route('bar.users.index')->with('success', 'Colaborador removido da equipe.');
    }
}
