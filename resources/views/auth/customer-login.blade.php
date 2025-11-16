<x-guest-layout>
    <div class="p-6 sm:p-8 lg:p-10 bg-white/95 backdrop-blur-sm shadow-2xl rounded-2xl w-full max-w-md mx-auto">

        <div class="flex flex-col items-center mb-6">
            <h1 class="text-3xl font-extrabold text-indigo-700 mb-2">Login do Cliente</h1>
            <p class="text-gray-600 text-center">Acesso exclusivo para agendamento de quadras.</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('customer.login') }}">
            @csrf

            <!-- Email -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />

                <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />

                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Remember Me & Links -->
            <div class="block mt-4 flex justify-between items-center">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600">{{ __('Lembrar-me') }}</span>
                </label>

                @if (Route::has('password.request'))
                    {{-- A rota de recuperação de senha aqui usa a rota PADRÃO do Laravel (admin/gestor) --}}
                    <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Esqueceu a senha?') }}
                    </a>
                @endif
            </div>

            <div class="flex items-center justify-end mt-6">

                {{-- Rota corrigida para customer.register --}}
                <a href="{{ route('customer.register') }}" class="underline text-sm text-indigo-600 hover:text-indigo-900 mr-4">
                    Não tem conta? Crie aqui.
                </a>

                <x-primary-button class="ms-3 bg-indigo-600 hover:bg-indigo-700">
                    {{ __('Entrar na Conta') }}
                </x-primary-button>
            </div>

            <div class="text-center mt-6 border-t pt-4">
                <a href="{{ route('home') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                    ← Voltar para a Home
                </a>
            </div>
        </form>
    </div>
</x-guest-layout>
