<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-4">
                {{-- ✅ Botão Voltar seguindo o padrão Estoque --}}
                <a href="{{ route('bar.dashboard') }}"
                   class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg group"
                   title="Voltar ao Início">
                    <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block">◀</span>
                </a>
                <div>
                    <h2 class="text-3xl font-black text-white uppercase tracking-tighter">
                        👥 Gestão de <span class="text-orange-500">Equipe</span>
                    </h2>
                    <p class="text-gray-500 text-sm italic font-bold uppercase tracking-widest">Colaboradores e Acessos.</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                {{-- Botão Novo Colaborador --}}
                <a href="{{ route('bar.users.create') }}"
                    class="inline-flex items-center px-6 py-3 bg-orange-600 hover:bg-orange-500 text-white font-bold rounded-xl transition shadow-lg shadow-orange-600/20 uppercase text-xs tracking-widest">
                    <span class="mr-2">➕</span> NOVO COLABORADOR
                </a>
            </div>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="mb-6 p-4 bg-green-900/50 border border-green-500 text-green-200 rounded-xl font-bold flex items-center gap-3 shadow-lg">
                <span class="text-xl">✅</span>
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-gray-900 p-6 rounded-3xl mb-8 border border-gray-800 shadow-lg">
            <form action="{{ route('bar.users.index') }}" method="GET" class="space-y-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="🔍 Buscar por nome ou email..."
                        class="flex-1 bg-gray-950 border-gray-800 rounded-xl text-white focus:border-orange-500 focus:ring-orange-500 p-3 font-bold"
                        autofocus>

                    <button type="submit"
                        class="px-8 py-3 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-xl transition uppercase text-xs tracking-widest">
                        BUSCAR
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-gray-900 rounded-[2.5rem] border border-gray-800 overflow-hidden shadow-2xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-800/50 text-[10px] font-black text-gray-400 uppercase tracking-widest">
                        <th class="p-6">Colaborador</th>
                        <th class="p-6">Cargo</th>
                        <th class="p-6 text-center">WhatsApp</th>
                        <th class="p-6 text-center">Status</th>
                        <th class="p-6 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-800/30 transition-all group">
                            <td class="p-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center text-white font-black border border-gray-700 group-hover:border-orange-500 transition-all">
                                        {{ substr($user->name, 0, 1) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-black text-white uppercase tracking-tight">{{ $user->name }}</span>
                                        <span class="text-[10px] text-gray-500 font-bold tracking-widest">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-6">
                                <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest {{ $user->role === 'admin' ? 'bg-red-500/10 text-red-500 border border-red-500/20' : 'bg-indigo-500/10 text-indigo-500 border border-indigo-500/20' }}">
                                    {{ $user->role === 'admin' ? 'Administrador' : 'Gestor' }}
                                </span>
                            </td>
                            <td class="p-6 text-center font-mono text-xs text-gray-400">
                                {{ $user->whatsapp_contact ?? '---' }}
                            </td>
                            <td class="p-6 text-center">
                                <span class="flex items-center justify-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.4)]"></span>
                                    <span class="text-[9px] font-black text-white uppercase tracking-widest">Ativo</span>
                                </span>
                            </td>
                            <td class="p-6 text-right">
                                <div class="flex justify-end gap-2">
                                    {{-- Editar --}}
                                    <a href="{{ route('bar.users.edit', $user->id) }}"
                                        class="p-2 bg-gray-800 hover:bg-orange-600 text-white rounded-lg transition-all border border-gray-700"
                                        title="Editar">
                                        ⚙️
                                    </a>

                                    {{-- Excluir --}}
                                    @if (Auth::id() !== $user->id)
                                    <form action="{{ route('bar.users.destroy', $user->id) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="button" onclick="showCustomConfirmation(this)"
                                            class="p-2 bg-gray-800 hover:bg-red-600 text-white rounded-lg transition-all border border-gray-700"
                                            data-username="{{ $user->name }}">
                                            🗑️
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-gray-500 font-bold italic uppercase text-xs tracking-widest">
                                Nenhum colaborador encontrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-8">
            {{ $users->links() }}
        </div>
    </div>

    {{-- Script e Modal de Confirmação (Mantido do anterior) --}}
    <div id="confirmation-modal" class="fixed inset-0 bg-black/95 backdrop-blur-md z-[100] hidden transition-opacity duration-300 flex items-center justify-center p-4">
        <div id="modal-content" class="bg-gray-900 border border-gray-800 rounded-[2.5rem] shadow-2xl p-10 max-w-sm w-full transform transition-all text-center">
            <h3 class="text-2xl font-black text-white uppercase tracking-tighter mb-4">Remover Staff?</h3>
            <p class="text-gray-400 font-bold text-sm mb-8 px-4 uppercase tracking-widest leading-relaxed">
                Confirmar remoção de <br><span id="username-placeholder" class="text-orange-500"></span>?
            </p>
            <div class="flex flex-col gap-3">
                <button type="button" id="confirm-delete-btn" class="w-full bg-red-600 hover:bg-red-700 text-white font-black py-4 rounded-2xl transition-all uppercase text-xs tracking-widest">Sim, Excluir</button>
                <button type="button" onclick="closeCustomConfirmation()" class="w-full bg-gray-800 hover:bg-gray-700 text-gray-300 font-black py-4 rounded-2xl transition-all uppercase text-xs tracking-widest border border-gray-700">Cancelar</button>
            </div>
        </div>
    </div>

    <script>
        let formToSubmit = null;
        function showCustomConfirmation(button) {
            formToSubmit = button.closest('form');
            document.getElementById('username-placeholder').textContent = button.getAttribute('data-username');
            const modal = document.getElementById('confirmation-modal');
            modal.classList.remove('hidden');
            setTimeout(() => { document.getElementById('modal-content').classList.add('scale-100', 'opacity-100'); }, 10);
        }
        function closeCustomConfirmation() {
            document.getElementById('confirmation-modal').classList.add('hidden');
        }
        document.getElementById('confirm-delete-btn').addEventListener('click', () => { if(formToSubmit) formToSubmit.submit(); });
    </script>
</x-bar-layout>
