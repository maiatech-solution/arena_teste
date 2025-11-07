<x-guest-layout>
<!-- Contêiner Principal com Fundo Gradiente Escuro (IDÊNTICO AO LOGIN) -->
<div class="flex items-center justify-center min-h-screen bg-gray-900 p-4 sm:p-6"
style="background-image: linear-gradient(135deg, #1f2937 0%, #0f172a 100%);">

    <!-- Card Principal Aprimorado -->
    <div class="w-full max-w-md bg-white p-10 sm:p-12 rounded-3xl shadow-2xl transition-all duration-500 transform
                hover:shadow-indigo-500/50 border border-gray-100">

        <!-- Branding/Título (Adaptado para Recuperação) -->
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
                RECUPERAR ACESSO
            </h1>
            <p class="text-gray-500 mt-1 text-sm font-medium">
                Link de redefinição enviado por e-mail.
            </p>
        </div>

        <!-- MENSAGEM DE INSTRUÇÃO -->
        <div class="mb-6 text-sm text-gray-700 p-3 rounded-xl border border-gray-200 bg-gray-50 font-medium">
            {{ __('Esqueceu sua senha? Sem problemas. Informe seu e-mail abaixo e enviaremos o link de redefinição.') }}
        </div>

        <!-- Mensagem de Status (SUCESSO - AGORA EM PORTUGUÊS) -->
        @if (session('status'))
            <div class="mb-4 text-sm text-green-700 font-semibold text-center
                 bg-green-50 p-4 rounded-xl border border-green-200">
                Link de redefinição de senha enviado com sucesso! Verifique sua caixa de entrada e a pasta de Spam.
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email de Cadastro')" class="text-gray-700 font-semibold mb-1" />
                <div class="relative">
                    <x-text-input id="email"
                        class="block w-full py-3 px-4 border-2 border-gray-300 rounded-xl focus:ring-indigo-600 focus:border-indigo-600 transition duration-200 text-sm placeholder-gray-400"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        placeholder="seu_email@elitesoccer.com.br"
                    />
                    <!-- Ícone de Email (Usando SVG de exemplo do login) -->
                    <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1h-4v-1zm-8 0v1H4v-1zM20 7L10 17 4 11"></path></svg>
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm" />
            </div>

            <!-- Botão de Envio (Estilo IMPACTANTE do LOGIN) -->
            <div class="mt-8 pt-4">
                <button type="submit" class="w-full flex justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-lg text-base font-extrabold text-white
                                             bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-white
                                             transition duration-300 transform hover:scale-[1.01] active:scale-[0.99] uppercase tracking-wider shadow-indigo-500/40">
                    {{ __('ENVIAR LINK DE REDEFINIÇÃO') }}
                </button>
            </div>
        </form>

        <!-- Link para Voltar ao Login -->
        <div class="mt-6 text-center">
            <a class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition duration-150 hover:underline" href="{{ route('login') }}">
                {{ __('Lembrei minha senha, voltar para o Login') }}
            </a>
        </div>
    </div>
</div>
</x-guest-layout>
