<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Exibe o formulário de login para clientes.
     */
    public function showLoginForm()
    {
        return view('auth.customer-login');
    }

    /**
     * Processa o login do cliente.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // 1. Checa as credenciais e o role (CRÍTICO)
        if ($user && $user->role === 'cliente' && Auth::attempt($credentials)) {
            $request->session()->regenerate();

            Log::info("Cliente ID: {$user->id} logado com sucesso.");

            // Redireciona para a página de agendamento
            return redirect()->intended(route('reserva.index'))
                            ->with('success', 'Login realizado! Você pode prosseguir com sua reserva.');
        }

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros de cliente.',
        ])->onlyInput('email');
    }

    /**
     * Exibe o formulário de registro para novos clientes.
     */
    public function showRegistrationForm()
    {
        return view('auth.customer-register');
    }

    /**
     * Processa o registro de um novo cliente.
     * * Campos Obrigatórios: Nome, WhatsApp, Data de Nascimento, Senha.
     * Campo Opcional: Email.
     */
    public function register(Request $request)
    {
        // 0. Pré-Sanitização do contato (remove caracteres não numéricos)
        $contactValue = $request->input('whatsapp_contact', '');
        $cleanedContact = preg_replace('/\D/', '', $contactValue);
        $request->merge(['whatsapp_contact' => $cleanedContact]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users'],
            'whatsapp_contact' => ['required', 'digits_between:10,11', 'unique:users,whatsapp_contact'], // Único por contato
            'data_nascimento' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . Carbon::now()->subYears(10)->format('Y-m-d')], // Mínimo 10 anos
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'whatsapp_contact.unique' => 'Este número de WhatsApp já está cadastrado em nossa plataforma.',
            'whatsapp_contact.digits_between' => 'O WhatsApp deve conter 10 ou 11 dígitos (apenas números, incluindo o DDD).',
            'data_nascimento.before_or_equal' => 'Você deve ter pelo menos 10 anos para se registrar.',
        ]);

        // 🛑 CRÍTICO: Atribui a role 'cliente'
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'], // Pode ser nulo
            'whatsapp_contact' => $validated['whatsapp_contact'],
            'data_nascimento' => $validated['data_nascimento'],
            'role' => 'cliente',
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        return redirect(route('reserva.index'))->with('success', 'Cadastro realizado com sucesso! Sua conta de cliente está pronta.');
    }

    /**
     * Desloga o cliente.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect(route('reserva.index'))->with('success', 'Você saiu da sua conta de cliente.');
    }

    // =========================================================================
    // ✅ MÉTODO: HISTÓRICO DE RESERVAS DO CLIENTE
    // =========================================================================
    /**
     * Exibe o histórico de reservas do cliente logado.
     */
    public function reservationHistory()
    {
        $user = Auth::user();

        // Checagem de segurança se o usuário é realmente um cliente
        if (!$user || $user->role !== 'cliente') {
            return redirect()->route('reserva.index')->with('error', 'Acesso negado. Apenas clientes podem visualizar esta página.');
        }

        // Busca todas as reservas (ativas e inativas) do cliente
        $reservations = $user->reservas()
                            ->where('is_fixed', false) // Apenas reservas reais de cliente
                            ->orderBy('date', 'desc')
                            ->orderBy('start_time', 'desc')
                            ->paginate(10);

        return view('customer.history', compact('reservations'));
    }
    // =========================================================================
}
