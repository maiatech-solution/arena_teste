@php
    $user = auth()->user();
@endphp

<x-bar-layout>
    <div class="max-w-4xl mx-auto px-4 py-10">

        {{-- Cabeçalho Estilo PDV --}}
        <div class="flex items-center gap-4 mb-12">
            <a href="{{ route('bar.dashboard') }}"
                class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg group">
                <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block text-xl">◀</span>
            </a>
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Meu <span class="text-orange-500">Perfil</span>
                </h1>
                <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Configurações de acesso do
                    operador</p>
            </div>
        </div>

        <div class="space-y-10">

            {{-- SEÇÃO: INFO PESSOAL --}}
            <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden">
                <div class="p-8 border-b border-gray-800 bg-gray-800/20">
                    <h3 class="text-lg font-black text-white uppercase italic">Dados Pessoais</h3>
                    <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest">Atualize seu nome e e-mail
                        de acesso ao sistema</p>
                </div>

                <div class="p-8">
                    {{-- Action aponta para a rota padrão de update do Breeze --}}
                    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
                        @csrf
                        @method('patch')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Nome
                                    Completo</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                    class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold p-3">
                                <x-input-error class="mt-2" :messages="$errors->get('name')" />
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">E-mail
                                    de Acesso</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                    class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold p-3">
                                <x-input-error class="mt-2" :messages="$errors->get('email')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-4">
                            <button type="submit"
                                class="px-8 py-3 bg-orange-600 hover:bg-orange-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition shadow-lg shadow-orange-600/20 focus:outline-none">
                                Salvar Alterações
                            </button>

                            @if (session('status') === 'profile-updated')
                                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
                                    class="flex items-center gap-2 text-green-500 font-bold text-xs uppercase tracking-widest animate-bounce">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    Dados Atualizados!
                                </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- SEÇÃO: SENHA --}}
            <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] shadow-2xl overflow-hidden">
                <div class="p-8 border-b border-gray-800 bg-gray-800/20">
                    <h3 class="text-lg font-black text-white uppercase italic">Segurança</h3>
                    <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest">Alterar senha de acesso ao
                        PDV</p>
                </div>

                <div class="p-8">
                    {{-- Action aponta para a rota padrão de password update do Breeze --}}
                    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
                        @csrf
                        @method('put')

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label
                                    class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Senha
                                    Atual</label>
                                <input type="password" name="current_password"
                                    class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold p-3">
                                <x-input-error class="mt-2" :messages="$errors->updatePassword->get('current_password')" />
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Nova
                                    Senha</label>
                                <input type="password" name="password"
                                    class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold p-3">
                                <x-input-error class="mt-2" :messages="$errors->updatePassword->get('password')" />
                            </div>

                            <div>
                                <label
                                    class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Confirmar
                                    Senha</label>
                                <input type="password" name="password_confirmation"
                                    class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold p-3">
                                <x-input-error class="mt-2" :messages="$errors->updatePassword->get('password_confirmation')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-4">
                            <button type="submit"
                                class="px-8 py-3 bg-gray-800 hover:bg-gray-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition border border-gray-700">
                                Atualizar Senha
                            </button>

                            @if (session('status') === 'password-updated')
                                <span class="text-green-500 text-xs font-bold uppercase animate-pulse">✅ Senha
                                    Alterada!</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-bar-layout>
