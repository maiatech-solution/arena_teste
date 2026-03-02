<x-guest-layout>
<div class="flex items-center justify-center min-h-screen bg-gray-900 p-4 sm:p-6"
style="background-image: linear-gradient(135deg, #1f2937 0%, #0f172a 100%);">

    <div class="w-full max-w-md bg-white p-10 sm:p-12 rounded-3xl shadow-2xl transition-all duration-500 transform
                 hover:shadow-indigo-500/50 border border-gray-100">

        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                ACESSO EXCLUSIVO
            </h1>
            <p class="text-gray-500 mt-1 text-sm font-medium">
                Gestão Esportiva de Alto Nível
            </p>
        </div>

        <x-auth-session-status class="mb-4 text-sm text-red-600 font-semibold text-center bg-red-50 p-3 rounded-lg border border-red-200" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf
            {{-- 🍯 CAMADA 1: HONEYPOT (Invisível para humanos, armadilha para bots) --}}
            @honeypot

            <div>
                <x-input-label for="email" :value="__('Email')" class="text-gray-700 font-semibold mb-1" />
                <x-text-input id="email"
                    class="block w-full py-3 px-4 border-2 border-gray-300 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 transition duration-200 text-sm placeholder-gray-400"
                    type="email" name="email" :value="old('email')" required autofocus placeholder="usuario@gestor.com.br" />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
            </div>

            <div>
                <x-input-label for="password" :value="__('Senha')" class="text-gray-700 font-semibold mb-1" />
                <x-text-input id="password"
                    class="block w-full py-3 px-4 border-2 border-gray-300 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 transition duration-200 text-sm placeholder-gray-400"
                    type="password" name="password" required placeholder="••••••••" />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-sm" />
            </div>

            {{-- 🖼️ CAMADA 2: CAPTCHA LOCAL (Mews Captcha) --}}
            <div class="p-4 bg-gray-50 rounded-2xl border-2 border-gray-100">
                <x-input-label for="captcha" :value="__('Verificação de Segurança')" class="text-gray-700 font-bold mb-3 text-center text-xs uppercase tracking-widest" />

                <div class="flex items-center justify-center space-x-4 mb-3">
                    <div class="rounded-lg overflow-hidden border-2 border-white shadow-sm bg-white">
                       {!! \Mews\Captcha\Facades\Captcha::img('flat') !!}
                    </div>
                    {{-- Botão para atualizar o Captcha via JS simples --}}
                    <button type="button"
                            class="p-2 bg-white text-indigo-600 rounded-full shadow-sm hover:bg-indigo-50 transition-colors border border-gray-200"
                            onclick="document.querySelector('img[src*=\'captcha\']').src = '/captcha/flat?' + Math.random()">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>

                <x-text-input id="captcha"
                    class="block w-full py-2 px-4 border-2 border-gray-300 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 text-center font-bold tracking-[0.3em]"
                    type="text" name="captcha" required placeholder="DIGITE O CÓDIGO" />
                <x-input-error :messages="$errors->get('captcha')" class="mt-2 text-center text-xs" />
            </div>

            <div class="flex items-center justify-start">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded-md border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ms-2 text-sm text-gray-600 font-medium italic">{{ __('Lembrar de mim') }}</span>
                </label>
            </div>

            <div class="mt-6">
                <button type="submit" class="w-full flex justify-center py-4 px-4 border border-transparent rounded-xl shadow-lg text-base font-extrabold text-white
                                             bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-offset-2 focus:ring-indigo-500
                                             transition duration-300 transform hover:scale-[1.01] active:scale-[0.99] uppercase tracking-wider shadow-indigo-500/40">
                    {{ __('AUTENTICAR ACESSO') }}
                </button>
            </div>
        </form>
    </div>
</div>
</x-guest-layout>
