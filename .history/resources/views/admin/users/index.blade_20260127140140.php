<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }} <!-- Agora o título é dinâmico -->
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                @if (session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg shadow-md" role="alert">
                    {{ session('success') }}
                </div>
                @endif
                @if (session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow-md" role="alert">
                    {{ session('error') }}
                </div>
                @endif

                @php
                $filterBaseClasses = 'px-5 py-2 rounded-lg text-sm font-medium transition duration-150 ease-in-out shadow-md border-2 border-transparent';
                @endphp

                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 space-y-3 sm:space-y-0">
                    <div class="flex space-x-3 flex-wrap gap-3 sm:gap-x-3">
                        <a href="{{ route('admin.users.index', ['search' => $search ?? null]) }}"
                            class="{{ $filterBaseClasses }} {{ is_null($roleFilter) ? 'bg-gray-600 text-white ring-4 ring-gray-300' : 'bg-gray-500 text-white hover:bg-gray-600' }}">
                            Todos
                        </a>

                        <a href="{{ route('admin.users.index', ['role_filter' => 'gestor', 'search' => $search ?? null]) }}"
                            class="{{ $filterBaseClasses }} {{ $roleFilter == 'gestor' ? 'bg-indigo-700 text-white ring-4 ring-indigo-300' : 'bg-indigo-600 text-white hover:bg-indigo-700' }}">
                            Gestores e Admins
                        </a>

                        <a href="{{ route('admin.users.index', ['role_filter' => 'cliente', 'search' => $search ?? null]) }}"
                            class="{{ $filterBaseClasses }} {{ $roleFilter == 'cliente' ? 'bg-green-700 text-white ring-4 ring-green-300' : 'bg-green-600 text-white hover:bg-green-700' }}">
                            Clientes
                        </a>
                    </div>

                    <div class="flex space-x-3 w-full sm:w-auto justify-end">
                        <a href="{{ route('admin.users.create', ['role' => 'gestor']) }}"
                            class="flex-shrink-0 w-1/2 sm:w-auto px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-200 text-center">
                            + Novo Gestor
                        </a>

                        <a href="{{ route('admin.users.create', ['role' => 'cliente']) }}"
                            class="flex-shrink-0 w-1/2 sm:w-auto px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-200 text-center">
                            + Novo Cliente
                        </a>
                    </div>
                </div>

                <div class="mb-6">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center space-x-2">
                        <input type="hidden" name="role_filter" value="{{ $roleFilter ?? '' }}">
                        <input type="text" name="search" placeholder="Buscar por nome, email ou contato..."
                            value="{{ $search ?? '' }}"
                            class="flex-grow p-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">

                        <button type="submit" class="px-4 py-2 bg-indigo-500 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-600 transition duration-200">
                            Buscar
                        </button>

                        @if (!empty($search))
                        <a href="{{ route('admin.users.index', ['role_filter' => $roleFilter ?? null]) }}"
                            class="px-3 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-200"
                            title="Limpar busca">X</a>
                        @endif
                    </form>
                </div>

                <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usuário (Nome/Email)</th>
                                {{-- Mantemos a arena apenas para Staff (Gestores/Admins) que possuem unidade fixa --}}
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vínculo / Unidade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Reputação / Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">WhatsApp</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[150px]">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($users as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                    <div class="mt-1">
                                        @if ($user->role === 'admin')
                                        <span class="px-2 inline-flex text-[10px] leading-4 font-semibold rounded-full bg-red-100 text-red-800 uppercase">Admin</span>
                                        @elseif ($user->role === 'gestor')
                                        <span class="px-2 inline-flex text-[10px] leading-4 font-semibold rounded-full bg-indigo-100 text-indigo-800 uppercase">Gestor</span>
                                        @else
                                        <span class="px-2 inline-flex text-[10px] leading-4 font-semibold rounded-full bg-green-100 text-green-800 uppercase">Cliente</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    @if($user->role !== 'cliente')
                                    {{-- Se for gestor/admin, mostra a arena onde ele trabalha --}}
                                    <span class="font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ $user->arena->name ?? 'Acesso Global' }}
                                    </span>
                                    @else
                                    {{-- Se for cliente, indica que ele joga em diversas unidades --}}
                                    <span class="text-gray-400 italic text-xs">Multiarenas</span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    {!! $user->status_tag !!}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 font-mono">
                                    {{ $user->formatted_whatsapp_contact }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex justify-center space-x-3 items-center">
                                        {{-- Botão de Histórico (Essencial para rastreabilidade do cliente) --}}
                                        @if ($user->role === 'cliente')
                                        <a href="{{ route('admin.users.reservas', $user) }}"
                                            class="text-green-600 hover:bg-green-100 p-2 rounded-full transition flex items-center border border-green-200"
                                            title="Ver todas as reservas deste cliente">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span class="text-[10px] font-bold">HISTÓRICO</span>
                                        </a>
                                        @endif

                                        <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:bg-indigo-100 p-2 rounded-full transition" title="Editar Perfil">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                            </svg>
                                        </a>

                                        @if (Auth::id() !== $user->id)
                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return false;" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="button" onclick="showCustomConfirmation(this)"
                                                class="text-red-600 hover:bg-red-100 p-2 rounded-full transition"
                                                data-username="{{ $user->name }}" data-userid="{{ $user->id }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">Nenhum usuário encontrado.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $users->appends(request()->query())->links() }}
                </div>

            </div>
        </div>
    </div>

    <!-- INÍCIO DO MODAL DE CONFIRMAÇÃO PERSONALIZADO (ADICIONADO PARA O DELETE) -->
    <div id="confirmation-modal"
        class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm z-[100] hidden transition-opacity duration-300 ease-out"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true">

        <div class="flex items-center justify-center min-h-screen p-4">
            <div id="modal-content"
                class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-sm w-full transform transition-all duration-300 scale-95 opacity-0 border border-gray-200 dark:border-gray-700">

                <div class="flex flex-col items-center">
                    <div class="flex items-center justify-center h-14 w-14 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                        <svg class="h-8 w-8 text-red-600 dark:text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.398 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <h3 class="text-xl leading-6 font-bold text-gray-900 dark:text-white" id="modal-title">
                        Confirmar Exclusão
                    </h3>

                    <div class="mt-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                            Você tem certeza que deseja excluir o usuário <br>
                            <span id="username-placeholder" class="font-black text-gray-900 dark:text-white uppercase"></span>?
                        </p>

                        {{-- Nota de Rastreabilidade --}}
                        <p class="mt-4 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-100 dark:border-yellow-800 rounded text-xs text-yellow-700 dark:text-yellow-500 text-center italic">
                            Atenção: Reservas vinculadas a este ID (ID: <span id="userid-placeholder"></span>) podem perder a referência direta.
                        </p>

                        <p class="mt-3 text-sm font-bold text-red-600 dark:text-red-400 text-center uppercase tracking-widest">
                            Ação Irreversível
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col sm:flex-row-reverse gap-3">
                    <button type="button"
                        id="confirm-delete-btn"
                        class="w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2.5 bg-red-600 text-base font-bold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm transition-all duration-200">
                        Sim, Excluir Agora
                    </button>

                    <button type="button"
                        onclick="closeCustomConfirmation()"
                        class="w-full inline-flex justify-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2.5 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:w-auto sm:text-sm transition-all duration-200">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- FIM DO MODAL DE CONFIRMAÇÃO PERSONALIZADO -->



    <!-- INÍCIO DO SCRIPT DE CONTROLE DO MODAL (ADICIONADO) -->
    <!-- ========================================================================= -->
    <script>
        let formToSubmit = null; // Variável para armazenar o formulário que será enviado
        let issubmitting = false; // Trava para evitar cliques duplos

        /**
         * Abre o modal de confirmação e configura os dados do usuário.
         * @param {HTMLElement} button - O botão clicado (dentro do formulário).
         */
        function showCustomConfirmation(button) {
            // 1. Encontra o formulário pai do botão
            formToSubmit = button.closest('form');
            if (!formToSubmit) {
                console.error('Erro: Formulário de exclusão não encontrado.');
                return;
            }

            // 2. Extrai os dados do usuário a partir dos atributos data-* do botão
            const userName = button.getAttribute('data-username') || 'Usuário';
            const userId = button.getAttribute('data-userid') || 'N/A';

            // 3. Atualiza o conteúdo do modal (com verificação de existência)
            const namePlaceholder = document.getElementById('username-placeholder');
            const idPlaceholder = document.getElementById('userid-placeholder');

            if (namePlaceholder) namePlaceholder.textContent = userName;
            if (idPlaceholder) idPlaceholder.textContent = userId;

            // 4. Exibe o modal
            const modal = document.getElementById('confirmation-modal');
            const modalContent = document.getElementById('modal-content');

            if (modal && modalContent) {
                modal.classList.remove('hidden');
                // Pequeno atraso para triggerar a transição CSS de opacidade/escala
                setTimeout(() => {
                    modal.style.opacity = '1';
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);
            }
        }

        /**
         * Fecha o modal de confirmação.
         */
        function closeCustomConfirmation() {
            const modal = document.getElementById('confirmation-modal');
            const modalContent = document.getElementById('modal-content');

            if (modal && modalContent) {
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');
                modal.style.opacity = '0';

                // Esconde o modal após o tempo da transição (300ms)
                setTimeout(() => {
                    modal.classList.add('hidden');
                    formToSubmit = null;
                    issubmitting = false; // Libera a trava caso o usuário cancele
                }, 300);
            }
        }

        /**
         * Inicializa os listeners quando o documento estiver pronto.
         */
        document.addEventListener('DOMContentLoaded', function() {
            const confirmBtn = document.getElementById('confirm-delete-btn');

            if (confirmBtn) {
                confirmBtn.addEventListener('click', function() {
                    // Prevenção de envio duplo e verificação de formulário
                    if (formToSubmit && !issubmitting) {
                        issubmitting = true; // Ativa a trava
                        confirmBtn.disabled = true; // Desativa o botão visualmente
                        confirmBtn.innerText = 'Excluindo...';

                        // Opcional: Adicionar um efeito de fade out antes de enviar
                        closeCustomConfirmation();

                        // Submete o formulário via DELETE do Laravel
                        setTimeout(() => {
                            formToSubmit.submit();
                        }, 200);
                    }
                });
            }

            // Fechar o modal ao clicar fora da caixa branca (no fundo escuro)
            const modal = document.getElementById('confirmation-modal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeCustomConfirmation();
                    }
                });
            }
        });
    </script>
    <!-- ========================================================================= -->
    <!-- FIM DO SCRIPT DE CONTROLE DO MODAL -->

</x-app-layout>
