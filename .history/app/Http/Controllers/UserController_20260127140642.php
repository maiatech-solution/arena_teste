<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Arena;
use App\Models\Reserva;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; // 🚀 Necessário para Str::random()
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
            ->with('arena')
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
    // 🛠️ PARTE 2: CRUD (CORRIGIDO)
    // =========================================================================

    public function create()
    {
        $arenas = Arena::all();
        return view('admin.users.create', compact('arenas'));
    }

    public function store(Request $request)
    {
        $isGestor = in_array($request->role, ['gestor', 'admin']);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'whatsapp_contact' => 'required|string|max:20',
            'role' => ['required', Rule::in(['cliente', 'gestor', 'admin'])],
            'arena_id' => 'nullable|exists:arenas,id',
        ];

        if ($isGestor) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        $validated = $request->validate($rules);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp_contact' => $validated['whatsapp_contact'],
            // Removida is_admin, pois sua tabela usa 'role'
            'password' => $isGestor ? Hash::make($validated['password']) : Hash::make(Str::random(16)),
            'role' => $validated['role'],
            'arena_id' => $validated['arena_id'] ?? null,
            'is_vip' => $request->has('is_vip') ? 1 : 0,
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
        ]);

        // 1. Atualiza a senha se preenchida
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // 2. Mapeamento manual dos campos (Limpando referências a is_admin)
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->whatsapp_contact = $validated['whatsapp_contact'];
        $user->role = $validated['role'];
        $user->arena_id = $validated['arena_id'] ?? null;

        // 3. Status VIP (Garante 1 ou 0 para o TinyInt)
        $user->is_vip = $request->has('is_vip') ? 1 : 0;

        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return response()->json(['success' => false, 'message' => 'Você não pode se excluir!'], 403);
        }

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
    // 🎯 Captura 'term' ou 'query' para evitar erro de comunicação com o JS
    $term = $request->input('term') ?? $request->input('query');

    if (empty($term) || strlen($term) < 2) {
        return response()->json([]);
    }

    // 1. Gera uma versão limpa (apenas números) para busca de WhatsApp
    $cleanTerm = preg_replace('/\D/', '', $term);

    // 2. Divide em palavras para busca por nome (ex: "João Silva")
    $keywords = explode(' ', $term);

    $clients = User::where('role', 'cliente') // Opcional: Garante que só busca clientes
        ->where(function ($q) use ($keywords, $cleanTerm, $term) {

            // Busca por Nome ou Email usando as keywords
            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 1) {
                    $q->orWhere('name', 'like', '%' . $keyword . '%')
                      ->orWhere('email', 'like', '%' . $keyword . '%');
                }
            }

            // 📱 Busca por WhatsApp (A mágica está aqui)
            // Se o termo digitado tiver números, buscamos removendo a formatação do banco
            if (!empty($cleanTerm)) {
                $q->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(whatsapp_contact, '(', ''), ')', ''), ' ', ''), '-', '') LIKE ?", ["%{$cleanTerm}%"]);
            }

            // Busca secundária pelo termo original no campo de contato
            $q->orWhere('whatsapp_contact', 'like', '%' . $term . '%');

        })
        ->limit(10)
        ->get(['id', 'name', 'email', 'whatsapp_contact']);

    return response()->json($clients);
}

    public function getReputation(string $contact)
    {
        // 1. Limpa o contato que veio da digitação no dashboard (apenas números)
        $cleanedContact = preg_replace('/\D/', '', $contact);

        // 2. Busca no banco de dados "limpando" a coluna whatsapp_contact em tempo real
        // Isso remove ( ) - e espaços apenas para a comparação
        $user = User::whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(whatsapp_contact, '(', ''), ')', ''), ' ', ''), '-', '') = ?", [$cleanedContact])
            ->first();

        if (!$user) {
            return response()->json([
                'is_vip' => false,
                'status_tag' => '<span class="text-xs text-gray-400 italic">Novo Cliente</span>',
            ]);
        }

        // 3. Monta a tag de reputação visualmente bonita para o Dashboard
        $badge = $user->is_vip
            ? '<span class="px-2 py-1 bg-indigo-600 text-white text-[10px] font-black rounded uppercase shadow-sm">⭐ Cliente VIP</span>'
            : '<span class="px-2 py-1 bg-green-100 text-green-700 text-[10px] font-black rounded uppercase border border-green-200">✅ Cliente Ativo</span>';

        return response()->json([
            'is_vip' => (bool) $user->is_vip,
            'status_tag' => $badge . ($user->customer_qualification !== 'normal' ? " <span class='text-[10px] font-bold opacity-70'>({$user->customer_qualification})</span>" : ""),
        ]);
    }
}
