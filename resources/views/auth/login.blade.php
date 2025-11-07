<x-guest-layout>
<!-- Contêiner Principal com Fundo Gradiente Escuro -->
<div class="flex items-center justify-center min-h-screen bg-gray-900 p-4 sm:p-6"
style="background-image: linear-gradient(135deg, #1f2937 0%, #0f172a 100%);">

    <!-- Card de Login Aprimorado -->
    <div class="w-full max-w-md bg-white p-10 sm:p-12 rounded-3xl shadow-2xl transition-all duration-500 transform
                 hover:shadow-indigo-500/50 border border-gray-100">

        <!-- Branding/Título -->
        <div class="mb-8 text-center">
            <!-- Ícone de Placeholder (Pode ser substituído por um SVG/Logo real do Elite Soccer) -->

            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                ACESSO EXCLUSIVO
            </h1>
            <p class="text-gray-500 mt-1 text-sm font-medium">
                Gestão Esportiva de Alto Nível
            </p>
        </div>

        <!-- Session Status (mantido, mas estilizado) -->
        <x-auth-session-status class="mb-4 text-sm text-red-600 font-semibold text-center bg-red-50 p-3 rounded-lg border border-red-200" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" class="text-gray-700 font-semibold mb-1" />
                <div class="relative">
                    <x-text-input id="email"
                        class="block w-full py-3 px-4 border-2 border-gray-300 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 transition duration-200 text-sm placeholder-gray-400"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="usuario@elitesoccer.com.br"
                    />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('Senha')" class="text-gray-700 font-semibold mb-1" />

                <div class="relative">
                    <x-text-input id="password"
                        class="block w-full py-3 px-4 border-2 border-gray-300 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 transition duration-200 text-sm placeholder-gray-400"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
            </div>

            <!-- Remember Me & Forgot Password (Modificado) -->
            <div class="flex items-center justify-start pt-2">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded-md border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600 font-medium">{{ __('Lembrar de mim') }}</span>
                </label>

                {{-- O link 'Esqueceu a senha?' foi removido temporariamente conforme solicitado. --}}
            </div>

            <!-- NOVO ALERTA DE REDEFINIÇÃO MANUAL (ESTILO MELHORADO) -->
            <div class="p-4 bg-indigo-50 border-l-4 border-indigo-600 text-indigo-800 rounded-lg shadow-md transition-all duration-300 transform hover:scale-[1.01] mt-4" role="alert">
                <div class="flex items-center">
                    <div>
                        <p class="font-bold text-base text-indigo-900">
                            Esqueceu sua senha?
                        </p>
                        <p class="text-sm">
                            Entre em contato diretamente com a equipe de Desenvolvimento do sistema.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Botão de Login (Impactante) -->
            <div class="mt-8">
                <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg text-base font-extrabold text-white
                                             bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-white
                                             transition duration-300 transform hover:scale-[1.01] active:scale-[0.99] uppercase tracking-wider shadow-indigo-500/40">
                    {{ __('FAZER LOGIN') }}
                </button>
            </div>
        </form>
    </div>
</div>
</x-guest-layout>
