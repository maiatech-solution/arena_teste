<?php

namespace App\Http\Controllers\Bar;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;

class BarUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $arenaId = auth()->user()->arena_id;

        // Agora incluímos 'colaborador' na listagem da equipe
        $query = User::where('arena_id', $arenaId)
            ->whereIn('role', ['gestor', 'admin', 'colaborador']);

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
            'role' => ['required', 'in:gestor,admin,colaborador'], // 👈 Adicionado colaborador
            'whatsapp_contact' => ['nullable', 'string', 'max:15'],
        ]);

        $authUser = auth()->user();
        $requestedRole = $request->role;

        // 🛡️ VALIDAÇÃO DE HIERARQUIA (Back-end)
        // Se um colaborador tentar criar um gestor via script/postman, o sistema barra.
        if ($authUser->role === 'colaborador' && $requestedRole !== 'colaborador') {
            return redirect()->back()->with('error', 'Você só tem permissão para cadastrar colaboradores.');
        }

        if ($authUser->role === 'gestor' && $requestedRole === 'admin') {
            return redirect()->back()->with('error', 'Gestores não podem cadastrar Administradores.');
        }

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'arena_id' => $authUser->arena_id,
            'role' => $requestedRole, // Aqui usamos o role validado acima
            'whatsapp_contact' => $request->whatsapp_contact,
            'status' => 'active',
            'customer_qualification' => 'normal',
            'is_blocked' => 0,
            'is_vip' => 0,
            'no_show_count' => 0,
        ]);

        return redirect()->route('bar.users.index')->with('success', 'Novo integrante adicionado à equipe!');
    }

    public function edit(User $user)
    {
        $authUser = auth()->user();

        // 🛡️ Segurança: Impede que níveis inferiores editem níveis superiores
        if ($authUser->role === 'colaborador' && $user->role !== 'colaborador') {
             return redirect()->route('bar.users.index')->with('error', 'Acesso negado: Você só pode editar outros colaboradores.');
        }

        if ($authUser->role === 'gestor' && $user->role === 'admin') {
            return redirect()->route('bar.users.index')->with('error', 'Segurança: Gestores não podem editar Administradores.');
        }

        if ($user->arena_id !== $authUser->arena_id) {
            return redirect()->route('bar.users.index')->with('error', 'Acesso negado.');
        }

        return view('bar.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();

        // 🛡️ Bloqueio de segurança via servidor
        if ($authUser->role === 'colaborador' && $user->role !== 'colaborador') abort(403);
        if ($authUser->role === 'gestor' && $user->role === 'admin') abort(403);
        if ($user->arena_id !== $authUser->arena_id) abort(403);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'whatsapp_contact' => ['nullable', 'string', 'max:15'],
            'role' => ['required', 'in:gestor,admin,colaborador'],
        ]);

        // Validação de mudança de cargo (Hierarquia)
        $newRole = $request->role;
        if ($authUser->role === 'colaborador' && $newRole !== 'colaborador') $newRole = 'colaborador';
        if ($authUser->role === 'gestor' && $newRole === 'admin') $newRole = 'gestor';
        if ($authUser->role !== 'admin' && $user->id === $authUser->id) $newRole = $authUser->role; // Impede auto-promoção

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $newRole,
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
        $authUser = auth()->user();

        // 🛡️ Gestor ou Colaborador não excluem Admin
        if ($authUser->role !== 'admin' && $user->role === 'admin') {
            return redirect()->route('bar.users.index')->with('error', 'Ação proibida: Somente administradores podem remover outros administradores.');
        }

        // Colaborador só exclui colaborador
        if ($authUser->role === 'colaborador' && $user->role !== 'colaborador') {
            return redirect()->route('bar.users.index')->with('error', 'Acesso negado.');
        }

        if ($user->id === $authUser->id) {
            return redirect()->route('bar.users.index')->with('error', 'Você não pode excluir sua própria conta.');
        }

        if ($user->arena_id !== $authUser->arena_id) abort(403);

        $user->delete();
        return redirect()->route('bar.users.index')->with('success', 'Integrante removido da equipe.');
    }
}