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
        $unitId = auth()->user()->unit_id;

        // Inicia a query filtrando sempre pela unidade do gestor logado
        $query = User::where('unit_id', $unitId);

        // 🔍 Lógica de Busca (Nome, Email ou Contato)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('contact', 'LIKE', "%{$search}%");
            });
        }

        // 🎭 Lógica de Filtro por Cargo (Role)
        if ($roleFilter) {
            if ($roleFilter === 'gestor') {
                $query->whereIn('role', ['gestor', 'admin']);
            } else {
                $query->where('role', $roleFilter);
            }
        }

        // Paginação com 10 por página, mantendo os filtros na URL
        $users = $query->paginate(10);

        // Título dinâmico para a View
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:gestor,cliente'],
            'contact' => ['nullable', 'string', 'max:20'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'unit_id' => auth()->user()->unit_id,
            'role' => $request->role,
            'contact' => $request->contact,
            'status' => 'active', // Assumindo que novo user entra ativo
        ]);

        return redirect()->route('bar.users.index')->with('success', 'Colaborador cadastrado com sucesso!');
    }

    public function edit(User $user)
    {
        // Segurança: Não deixa editar usuário de outra unidade
        if ($user->unit_id !== auth()->user()->unit_id) {
            return redirect()->route('bar.users.index')->with('error', 'Acesso não autorizado.');
        }

        return view('bar.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->unit_id !== auth()->user()->unit_id) abort(403);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'string'],
        ]);

        $user->update($request->only('name', 'email', 'role', 'contact'));

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->route('bar.users.index')->with('success', 'Usuário atualizado!');
    }

    public function destroy(User $user)
    {
        if ($user->unit_id !== auth()->user()->unit_id) abort(403);
        if ($user->id === auth()->id()) return back()->with('error', 'Você não pode se excluir.');

        $user->delete();
        return redirect()->route('bar.users.index')->with('success', 'Usuário removido!');
    }
}
