<x-bar-layout>
    <div class="py-12 bg-black min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="flex items-center justify-between mb-8 px-4 sm:px-0">
                <div class="flex items-center gap-4">
                    <a href="{{ route('bar.users.index') }}"
                        class="bg-gray-800 hover:bg-gray-700 text-orange-500 p-3 rounded-2xl transition-all border border-gray-700 shadow-lg group"
                        title="Voltar para a lista">
                        <svg class="w-6 h-6 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
                            Editar <span class="text-orange-600">Colaborador</span>
                        </h1>
                        <p class="text-gray-500 font-bold uppercase text-[10px] tracking-widest mt-1 italic">
                            Editando: {{ $user->name }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-900 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)] rounded-[2.5rem] border border-gray-800 p-8">

                <form method="POST" action="{{ route('bar.users.update', $user) }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <h3 class="text-xs font-black text-orange-500 uppercase tracking-[0.2em] border-b border-gray-800 pb-3 flex items-center gap-2">
                            <span>👤</span> Dados Pessoais
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2 group">
                                <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 transition-colors group-focus-within:text-orange-500">Nome Completo</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 shadow-inner font-bold transition-all">
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="space-y-2 group">
                                <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 transition-colors group-focus-within:text-orange-500">WhatsApp</label>
                                <input type="tel" name="whatsapp_contact" value="{{ old('whatsapp_contact', $user->whatsapp_contact) }}"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" maxlength="11"
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 shadow-inner font-bold transition-all">
                                <x-input-error :messages="$errors->get('whatsapp_contact')" class="mt-2" />
                            </div>

                            <div class="space-y-2 md:col-span-2 group">
                                <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 transition-colors group-focus-within:text-orange-500">E-mail (Login)</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 shadow-inner font-bold transition-all">
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 pt-4">
                        <h3 class="text-xs font-black text-orange-500 uppercase tracking-[0.2em] border-b border-gray-800 pb-3 flex items-center gap-2">
                            <span>🔐</span> Função e Senha
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2 md:col-span-2">
                                <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2">Nível de Acesso</label>

                                @php $currentUserRole = strtolower(trim(Auth::user()->role)); @endphp

                                {{-- ✅ Se for ADMIN ou GESTOR, liberamos o select (com filtros de segurança) --}}
                                @if($currentUserRole === 'admin' || $currentUserRole === 'gestor')
                                <select name="role" required
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 shadow-inner font-bold appearance-none cursor-pointer">

                                    {{-- 1. Colaborador: Sempre visível para Admin e Gestor --}}
                                    <option value="colaborador" {{ old('role', $user->role) == 'colaborador' ? 'selected' : '' }}>Colaborador (Operacional - Restrito)</option>

                                    {{-- 2. Gestor: Visível para Admin e Gestor --}}
                                    <option value="gestor" {{ old('role', $user->role) == 'gestor' ? 'selected' : '' }}>Gestor de Bar (Staff)</option>

                                    {{-- 3. Admin: SOMENTE Admin Master pode ver/atribuir este nível --}}
                                    @if($currentUserRole === 'admin')
                                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Administrador (Total)</option>
                                    @endif
                                </select>
                                @else
                                {{-- 🔒 Se for um Colaborador tentando editar, ele não muda cargo de ninguém --}}
                                <div class="w-full bg-gray-800/50 border border-gray-700 rounded-2xl p-4 text-gray-400 font-bold flex justify-between items-center shadow-inner">
                                    <span class="uppercase text-xs tracking-widest">
                                        @if($user->role === 'admin') Administrador @elseif($user->role === 'gestor') Gestor @else Colaborador @endif
                                    </span>
                                    <span class="text-[9px] bg-gray-700 px-2 py-1 rounded">SOMENTE LEITURA</span>
                                </div>
                                <input type="hidden" name="role" value="{{ $user->role }}">
                                @endif
                            </div>

                            <div class="space-y-2 group">
                                <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 transition-colors group-focus-within:text-orange-500">Nova Senha (Opcional)</label>
                                <input type="password" name="password" placeholder="Deixe em branco para manter"
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 shadow-inner font-bold transition-all">
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="space-y-2 group">
                                <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 transition-colors group-focus-within:text-orange-500">Confirmar Senha</label>
                                <input type="password" name="password_confirmation"
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 shadow-inner font-bold transition-all">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-4 pt-8">
                        <button type="submit"
                            class="flex-grow bg-orange-600 hover:bg-orange-500 text-white font-black py-4 rounded-2xl transition-all uppercase text-xs tracking-widest shadow-lg shadow-orange-600/20 active:scale-95 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                            Salvar Alterações
                        </button>
                        <a href="{{ route('bar.users.index') }}"
                            class="px-12 bg-gray-800 hover:bg-gray-700 text-gray-400 font-black py-4 rounded-2xl transition-all uppercase text-[10px] tracking-widest border border-gray-700 text-center flex items-center justify-center active:scale-95">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-bar-layout>