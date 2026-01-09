<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Arena;
use App\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // =========================================================================
    // 🖥️ PARTE 1: PÁGINAS (VIEWS) E LISTAGEM
    // =========================================================================

    public function index(Request $request)
    {
        $search = $request->input('search');
        $roleFilter = $request->input('role_filter');

        $users = User::query()
            ->with('arena') // Importante para saber a arena do gestor
            ->when($roleFilter, function ($q) use ($roleFilter) {
                return $q->where('role', $roleFilter);
            })
            ->when($search, function ($q) use ($search) {
                return $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('whatsapp_contact', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15);

        $pageTitle = "Gerenciamento de Usuários";

        return view('admin.users.index', compact('users', 'search', 'roleFilter', 'pageTitle'));
    }

    public function reservas(User $user)
    {
        $reservas = $user->reservas()
            ->with('arena')
            ->orderBy('date', 'desc')
            ->paginate(20);

        $seriesFutureCounts = $user->reservas()
            ->where('is_recurrent', true)
            ->where('date', '>=', now()->toDateString())
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            ->select('recurrent_series_id', DB::raw('count(*) as total'))
            ->groupBy('recurrent_series_id')
            ->pluck('total', 'recurrent_series_id');

        $pageTitle = "Histórico: " . $user->name;

        return view('admin.users.reservas', [
            'client' => $user,
            'reservas' => $reservas,
            'seriesFutureCounts' => $seriesFutureCounts,
            'pageTitle' => $pageTitle
        ]);
    }

    // =========================================================================
    // 🛠️ PARTE 2: CRUD (O QUE ESTAVA FALTANDO)
    // =========================================================================

    public function create()
    {
        $arenas = Arena::all(); // Necessário para vincular gestor a uma arena
        return view('admin.users.create', compact('arenas'));
    }

    public function store(Request $request)
    {
        $isGestor = in_array($request->role, ['gestor', 'admin']);

        // Validação Dinâmica
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'whatsapp_contact' => 'required|string|max:20',
            'role' => ['required', Rule::in(['cliente', 'gestor', 'admin'])],
        ];

        // Só exige senha se for Gestor ou Admin
        if ($isGestor) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp_contact' => $validated['whatsapp_contact'],
            // Se for cliente, a senha vira um Hash aleatório ou nulo (já que ele não loga)
            'password' => $isGestor ? Hash::make($validated['password']) : Hash::make(Str::random(16)),
            'role' => $validated['role'],
            'is_admin' => $isGestor,
            'arena_id' => null, // Forçamos null conforme sua regra de negócio
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso!');
    }

    public function edit(User $user)
    {
        $arenas = Arena::all();
        return view('admin.users.edit', compact('user', 'arenas'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'whatsapp_contact' => 'nullable|string|max:20',
            'role' => ['required', Rule::in(['cliente', 'gestor', 'admin'])],
            'arena_id' => 'nullable|exists:arenas,id',
            'is_vip' => 'nullable|boolean',
        ]);

        // 1. Atualiza a senha se preenchida
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // 2. Preenche os dados validados
        $user->fill($validated);

        // 3. 🛡️ GARANTIA DE CAMPOS OPCIONAIS
        // O fill() pode ignorar chaves ausentes. Aqui forçamos a limpeza se vierem vazios.
        $user->arena_id = $validated['arena_id'] ?? null;
        $user->whatsapp_contact = $validated['whatsapp_contact'] ?? null;

        // 4. Lógica de Admin/Status
        $user->is_admin = in_array($request->role, ['gestor', 'admin']);
        $user->is_vip = $request->has('is_vip'); // Para checkboxes costuma-se usar has()

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json(['success' => false, 'message' => 'Você não pode se excluir!'], 403);
        }

        // Verifica se há reservas ativas para não quebrar a rastreabilidade
        $hasActive = $user->reservas()->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])->exists();

        if ($hasActive) {
            return response()->json(['success' => false, 'message' => 'Usuário possui reservas ativas!'], 400);
        }

        $user->delete();
        return response()->json(['success' => true, 'message' => 'Usuário removido com sucesso.']);
    }

    // =========================================================================
    // ⚡ PARTE 3: API (DASHBOARD)
    // =========================================================================

    public function searchClients(Request $request)
    {
        $query = $request->input('query');
        if (empty($query) || strlen($query) < 2) return response()->json([]);

        $keywords = explode(' ', $query);
        $clients = User::where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('name', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%')
                    ->orWhere('whatsapp_contact', 'like', '%' . $keyword . '%');
            }
        })->limit(10)->get(['id', 'name', 'email', 'whatsapp_contact']);

        return response()->json($clients);
    }

    public function getReputation(string $contact)
    {
        $cleanedContact = preg_replace('/\D/', '', $contact);
        $user = User::where('whatsapp_contact', $cleanedContact)->first();

        return response()->json([
            'is_vip' => $user->is_vip ?? false,
            'status_tag' => $user->status_tag ?? null,
        ]);
    }
}
