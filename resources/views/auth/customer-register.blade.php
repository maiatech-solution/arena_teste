<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Cliente</title>

    <!-- Assume Tailwind CSS está disponível ou é carregado globalmente -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .input-style {
            @apply block w-full mt-1 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 min-h-screen antialiased flex items-center justify-center p-4">
    <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white dark:bg-gray-800 shadow-2xl overflow-hidden sm:rounded-xl border-t-8 border-indigo-600">

        <div class="mb-6 text-center">
            <h1 class="text-3xl font-extrabold text-indigo-700 dark:text-indigo-400">
                Criar Conta de Cliente
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-sm">Agendamento de quadras</p>
        </div>

        {{-- Bloco de Erros de Validação Padrão --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-300 dark:border-red-600 rounded-lg">
                <div class="font-bold text-red-700 dark:text-red-400">
                    Atenção! Por favor, corrija os erros abaixo:
                </div>
                <ul class="mt-3 list-disc list-inside text-sm text-red-600 dark:text-red-300 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('customer.register') }}">
            @csrf

            <!-- Name -->
            <div class="mb-4">
                <label for="name" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Nome Completo</label>
                <input id="name" class="input-style @error('name') border-red-500 @enderror" type="text" name="name" value="{{ old('name') }}" required autofocus />
            </div>

            <!-- WhatsApp Contact -->
            <div class="mb-4">
                <label for="whatsapp_contact" class="block font-medium text-sm text-gray-700 dark:text-gray-300">WhatsApp / Contato *</label>
                <input id="whatsapp_contact" class="input-style @error('whatsapp_contact') border-red-500 @enderror" type="text" name="whatsapp_contact" value="{{ old('whatsapp_contact') }}" required placeholder="Ex: 5511999998888 (Apenas números)" />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Será seu identificador principal.
                </p>
            </div>

            <!-- Data de Nascimento (NOVO CAMPO OBRIGATÓRIO) -->
            <div class="mb-4">
                <label for="data_nascimento" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Data de Nascimento *</label>
                <input id="data_nascimento" class="input-style @error('data_nascimento') border-red-500 @enderror" type="date" name="data_nascimento" value="{{ old('data_nascimento') }}" required />
            </div>

            <!-- Email Address (Opcional) -->
            <div class="mb-4">
                <label for="email" class="block font-medium text-sm text-gray-700 dark:text-gray-300">E-mail (Opcional)</label>
                <input id="email" class="input-style @error('email') border-red-500 @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="seu.email@exemplo.com" />
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label for="password" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Senha *</label>
                <input id="password" class="input-style @error('password') border-red-500 @enderror" type="password" name="password" required autocomplete="new-password" />
            </div>

            <!-- Confirm Password -->
            <div class="mb-6">
                <label for="password_confirmation" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Confirme a Senha *</label>
                <input id="password_confirmation" class="input-style @error('password_confirmation') border-red-500 @enderror" type="password" name="password_confirmation" required />
            </div>

            <div class="flex items-center justify-end mt-4 space-x-4">

                {{-- Link para Login do Cliente --}}
                <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition" href="{{ route('customer.login') }}">
                    Já tem conta?
                </a>

                {{-- Botão de Submissão (Sem componente) --}}
                <button type="submit" class="ms-3 px-4 py-2 bg-indigo-600 text-white font-bold rounded-lg shadow-lg hover:bg-indigo-700 transition duration-150 transform hover:scale-[1.01] active:scale-[0.99]">
                    Criar Conta
                </button>
            </div>
        </form>
    </div>
</body>
</html>
