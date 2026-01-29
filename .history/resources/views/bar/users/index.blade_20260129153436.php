<x-bar-layout>
    <div class="py-12 bg-black min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="flex items-center justify-between mb-8 px-4 sm:px-0">
                <div class="flex items-center gap-4">
                    <a href="{{ route('bar.users.index') }}"
                       class="bg-gray-800 hover:bg-gray-700 text-orange-500 p-3 rounded-2xl transition-all border border-gray-700 shadow-lg group"
                       title="Voltar para a lista">
                        <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
                            Novo <span class="text-orange-600">Colaborador</span>
                        </h1>
                        <p class="text-gray-500 font-bold uppercase text-[10px] tracking-widest mt-1">Adicionar integrante à equipe do bar</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-900 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)] rounded-[2.5rem] border border-gray-800 p-8">

                <form method="POST" action="{{ route('bar.users.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-orange-500 font-black uppercase text-[10px] tracking-widest ml-2">Nome Completo</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-500 shadow-inner font-bold">
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-orange-500 font-black uppercase text-[10px] tracking-widest ml-2">WhatsApp / Contato</label>
                            <input type="text" name="whatsapp_contact" value="{{ old('whatsapp_contact') }}" placeholder="(00) 00000-0000"
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-500 shadow-inner font-bold">
                            <x-input-error :messages="$errors->get('whatsapp_contact')" class="mt-2" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="text-orange-500 font-black uppercase text-[10px] tracking-widest ml-2">E-mail (Login)</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-500 shadow-inner font-bold">
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <div class="space-y-2">
                            <label class="text-orange-500 font-black uppercase text-[10px] tracking-widest ml-2">Senha de Acesso</label>
                            <input type="password" name="password" required
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-500 shadow-inner font-bold">
                        </div>

                        <div class="space-y-2">
                            <label class="text-orange-500 font-black uppercase text-[10px] tracking-widest ml-2">Confirmar Senha</label>
                            <input type="password" name="password_confirmation" required
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-500 shadow-inner font-bold">
                        </div>

                        <div class="space-y-2">
                            <label class="text-orange-500 font-black uppercase text-[10px] tracking-widest ml-2">Nível de Acesso</label>
                            <select name="role" required
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 shadow-inner font-bold appearance-none">
                                <option value="gestor" {{ old('role') == 'gestor' ? 'selected' : '' }}>Gestor de Bar (Staff)</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrador (Full)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-6">
                        <button type="submit"
                            class="flex-grow bg-orange-600 hover:bg-orange-700 text-white font-black py-4 rounded-2xl transition-all uppercase text-xs tracking-widest shadow-lg shadow-orange-600/20">
                            Finalizar Cadastro
                        </button>
                        <a href="{{ route('bar.users.index') }}"
                            class="px-8 bg-gray-800 hover:bg-gray-700 text-gray-400 font-black py-4 rounded-2xl transition-all uppercase text-[10px] tracking-widest border border-gray-700 text-center flex items-center">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-bar-layout>
