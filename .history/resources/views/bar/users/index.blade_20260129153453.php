<x-bar-layout>
    <div class="py-12 bg-black min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-8 px-4 sm:px-0 flex justify-between items-end">
                <div>
                    <h1 class="text-4xl font-black text-white uppercase tracking-tighter">
                        {{ $pageTitle }} <span class="text-orange-600">Equipe</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase text-xs tracking-widest mt-1">Gestão de Colaboradores e Acessos Administrativos</p>
                </div>

                <a href="{{ route('bar.users.create') }}"
                    class="hidden md:flex px-6 py-3 bg-orange-600 text-white text-[10px] font-black rounded-xl shadow-lg shadow-orange-600/20 hover:scale-105 transition-transform text-center uppercase tracking-widest items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                    Novo Colaborador
                </a>
            </div>

            <div class="bg-gray-900 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)] sm:rounded-[2.5rem] p-8 border border-gray-800">

                @if (session('success'))
                <div class="bg-green-500/10 border-l-4 border-green-500 text-green-500 p-4 mb-6 rounded-xl shadow-lg" role="alert">
                    <span class="font-black uppercase text-xs tracking-widest">Sucesso:</span> {{ session('success') }}
                </div>
                @endif

                <div class="mb-8">
                    <form method="GET" action="{{ route('bar.users.index') }}" class="flex items-center gap-3">
                        <div class="relative flex-grow">
                            <input type="text" name="search" placeholder="Buscar colaborador por nome ou email..."
                                value="{{ $search ?? '' }}"
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-500 focus:ring-2 focus:ring-orange-500 shadow-inner font-bold">
                        </div>

                        <button type="submit" class="px-8 py-4 bg-gray-800 text-orange-500 font-black rounded-2xl hover:bg-gray-700 transition-all uppercase text-xs tracking-widest border border-gray-700">
                            Buscar
                        </button>

                        @if (!empty($search))
                        <a href="{{ route('bar.users.index') }}"
                            class="px-5 py-4 bg-red-500/10 text-red-500 font-black rounded-2xl hover:bg-red-500/20 transition-all border border-red-500/20">X</a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto bg-gray-800/20 rounded-[2rem] border border-gray-800 shadow-2xl">
                    <table class="min-w-full divide-y divide-gray-800">
                        <thead class="bg-gray-800/50">
                            <tr>
                                <th class="px-8 py-5 text-left text-[10px] font-black text-orange-500 uppercase tracking-widest">Colaborador</th>
                                <th class="px-8 py-5 text-left text-[10px] font-black text-orange-500 uppercase tracking-widest">Cargo / Role</th>
                                <th class="px-8 py-5 text-left text-[10px] font-black text-orange-500 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-5 text-center text-[10px] font-black text-orange-500 uppercase tracking-widest min-w-[150px]">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @forelse ($users as $user)
                            <tr class="hover:bg-orange-600/[0.03] transition-colors group">
                                <td class="px-8 py-6 whitespace-nowrap">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-white font-black border border-gray-600 group-hover:border-orange-500/50 transition-all">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-black text-white uppercase tracking-tight">{{ $user->name }}</div>
                                            <div class="text-[11px] text-gray-500 font-bold tracking-wide">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-8 py-6 whitespace-nowrap">
                                    @if ($user->role === 'admin')
                                        <span class="px-3 py-1 rounded-lg text-[9px] font-black bg-red-500/10 text-red-500 uppercase border border-red-500/20 shadow-sm">Administrador</span>
                                    @else
                                        <span class="px-3 py-1 rounded-lg text-[9px] font-black bg-indigo-500/10 text-indigo-500 uppercase border border-indigo-500/20 shadow-sm">Gestor / Staff</span>
                                    @endif
                                </td>

                                <td class="px-8 py-6 whitespace-nowrap">
                                    <span class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                        <span class="text-[10px] font-black text-white uppercase tracking-widest">Ativo</span>
                                    </span>
                                </td>

                                <td class="px-8 py-6 whitespace-nowrap text-center">
                                    <div class="flex justify-center items-center gap-3">
                                        <a href="{{ route('bar.users.edit', $user) }}"
                                            class="bg-gray-700 hover:bg-orange-600 p-2 rounded-xl transition-all border border-gray-600 text-white group/btn shadow-lg">
                                            <svg class="w-4 h-4 group-hover/btn:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </a>

                                        @if (Auth::id() !== $user->id)
                                        <form action="{{ route('bar.users.destroy', $user) }}" method="POST" onsubmit="return false;" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="button" onclick="showCustomConfirmation(this)"
                                                class="bg-gray-700 hover:bg-red-600 p-2 rounded-xl transition-all border border-gray-600 text-white group/del shadow-lg"
                                                data-username="{{ $user->name }}">
                                                <svg class="w-4 h-4 group-hover/del:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-8 py-10 text-center text-gray-500 font-bold uppercase tracking-widest text-xs italic">Nenhum integrante da equipe encontrado.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-8">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    <div id="confirmation-modal" class="fixed inset-0 bg-black/90 backdrop-blur-md z-[100] hidden transition-opacity duration-300 ease-out" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div id="modal-content" class="bg-gray-900 border border-gray-800 rounded-[2rem] shadow-2xl p-8 max-w-sm w-full transform transition-all duration-300 scale-95 opacity-0 text-center">
                <div class="h-20 w-20 rounded-full bg-red-500/10 flex items-center justify-center mx-auto mb-6 border border-red-500/20">
                    <svg class="h-10 w-10 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.398 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
                <h3 class="text-2xl font-black text-white uppercase tracking-tighter mb-2">Remover Staff?</h3>
                <p class="text-gray-400 font-bold text-sm mb-6 px-4">
                    Isso revogará o acesso de <br><span id="username-placeholder" class="text-orange-500 uppercase"></span> ao sistema do bar.
                </p>

                <div class="flex flex-col gap-3">
                    <button type="button" id="confirm-delete-btn" class="w-full bg-red-600 hover:bg-red-700 text-white font-black py-4 rounded-2xl transition-all uppercase text-xs tracking-widest shadow-lg shadow-red-600/20">Confirmar Remoção</button>
                    <button type="button" onclick="closeCustomConfirmation()" class="w-full bg-gray-800 hover:bg-gray-700 text-gray-300 font-black py-4 rounded-2xl transition-all uppercase text-xs tracking-widest border border-gray-700">Manter Acesso</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let formToSubmit = null;
        let issubmitting = false;

        function showCustomConfirmation(button) {
            formToSubmit = button.closest('form');
            const userName = button.getAttribute('data-username') || 'Colaborador';
            document.getElementById('username-placeholder').textContent = userName;
            const modal = document.getElementById('confirmation-modal');
            const modalContent = document.getElementById('modal-content');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.style.opacity = '1';
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeCustomConfirmation() {
            const modal = document.getElementById('confirmation-modal');
            const modalContent = document.getElementById('modal-content');
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            modal.style.opacity = '0';
            setTimeout(() => { modal.classList.add('hidden'); issubmitting = false; }, 300);
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('confirm-delete-btn').addEventListener('click', function() {
                if (formToSubmit && !issubmitting) {
                    issubmitting = true;
                    this.innerText = 'Processando...';
                    formToSubmit.submit();
                }
            });
        });
    </script>
</x-bar-layout>
