<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 py-10">

        {{-- Cabeçalho Estilo PDV --}}
        <div class="flex items-center gap-4 mb-10">
            <a href="{{ route('bar.dashboard') }}"
               class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg group">
                <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block text-xl">◀</span>
            </a>
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Configurações de <span class="text-orange-500">Perfil</span>
                </h1>
                <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Gestão de conta do operador de bar</p>
            </div>
        </div>

        <div class="space-y-12">

            {{-- SEÇÃO: INFO PESSOAL --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-black text-white uppercase italic">Dados Pessoais</h3>
                    <p class="text-gray-500 text-xs font-bold uppercase mt-2 leading-relaxed">Nome e E-mail de acesso.</p>
                </div>

                <div class="md:col-span-2 bg-gray-900 border border-gray-800 p-10 rounded-[2.5rem] shadow-2xl">
                    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
                        @csrf
                        @method('patch')

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Nome Completo</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold">
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">E-mail de Acesso</label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold">
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="px-8 py-3 bg-orange-600 hover:bg-orange-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition shadow-lg shadow-orange-600/20">
                                Salvar Alterações
                            </button>
                            @if (session('status') === 'profile-updated')
                                <span class="text-green-500 text-xs font-bold uppercase animate-pulse">✅ Atualizado!</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <div class="border-t border-gray-800/50"></div>

            {{-- SEÇÃO: SENHA --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-black text-white uppercase italic">Segurança</h3>
                    <p class="text-gray-500 text-xs font-bold uppercase mt-2 leading-relaxed">Altere sua senha periodicamente.</p>
                </div>

                <div class="md:col-span-2 bg-gray-900 border border-gray-800 p-10 rounded-[2.5rem] shadow-2xl">
                    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
                        @csrf
                        @method('put')

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Senha Atual</label>
                            <input type="password" name="current_password"
                                   class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold">
                            <x-input-error class="mt-2" :messages="$errors->updatePassword->get('current_password')" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Nova Senha</label>
                            <input type="password" name="password"
                                   class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold">
                            <x-input-error class="mt-2" :messages="$errors->updatePassword->get('password')" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-2">Confirmar Nova Senha</label>
                            <input type="password" name="password_confirmation"
                                   class="w-full bg-black/40 border-gray-800 text-white rounded-xl focus:border-orange-500 focus:ring-orange-500 font-bold">
                            <x-input-error class="mt-2" :messages="$errors->updatePassword->get('password_confirmation')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit" class="px-8 py-3 bg-gray-800 hover:bg-gray-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition border border-gray-700">
                                Atualizar Senha
                            </button>
                            @if (session('status') === 'password-updated')
                                <span class="text-green-500 text-xs font-bold uppercase animate-pulse">✅ Senha Alterada!</span>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-bar-layout>
