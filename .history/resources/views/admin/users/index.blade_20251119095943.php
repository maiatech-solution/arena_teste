<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }} <!-- Agora o título é dinâmico -->
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Mensagens de Feedback (Success, Error, Warning) -->
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
                    // Classes base para os botões de filtro
                    $filterBaseClasses = 'px-5 py-2 rounded-lg text-sm font-medium transition duration-150 ease-in-out shadow-md border-2 border-transparent';
                    // Note: $roleFilter e $search são passados pelo Controller
                @endphp

                <!-- FILTROS E BOTÕES DE CRIAÇÃO -->
                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 space-y-3 sm:space-y-0">

                    <!-- Filtros de Função -->
                    <div class="flex space-x-3 flex-wrap gap-3 sm:gap-x-3">

                        {{-- Botão Todos os Usuários (Ativo quando $roleFilter é null) --}}
                        <a href="{{ route('admin.users.index', ['search' => $search ?? null]) }}"
                            class="{{ $filterBaseClasses }}
                                @if(is_null($roleFilter))
                                    {{-- ESTADO ATIVO: Cinza mais escuro com anel de destaque --}}
                                    bg-gray-600 text-white ring-4 ring-gray-300
                                @else
                                    {{-- ESTADO INATIVO: Cinza original --}}
                                    bg-gray-500 text-white hover:bg-gray-600
                                @endif">
                            Todos
                        </a>

                        {{-- Botão Gestores/Admins (Roxo/Indigo) --}}
                        <a href="{{ route('admin.users.index', ['role_filter' => 'gestor', 'search' => $search ?? null]) }}"
                            class="{{ $filterBaseClasses }}
                                @if($roleFilter == 'gestor')
                                    {{-- ESTADO ATIVO: Roxo mais escuro com anel de destaque --}}
                                    bg-indigo-700 text-white ring-4 ring-indigo-300
                                @else
                                    {{-- ESTADO INATIVO: Roxo original --}}
                                    bg-indigo-600 text-white hover:bg-indigo-700
                                @endif">
                            Gestores e Admins
                        </a>

                        {{-- Botão Clientes (Verde) --}}
                        <a href="{{ route('admin.users.index', ['role_filter' => 'cliente', 'search' => $search ?? null]) }}"
                            class="{{ $filterBaseClasses }}
                                @if($roleFilter == 'cliente')
                                    {{-- ESTADO ATIVO: Verde mais escuro com anel de destaque --}}
                                    bg-green-700 text-white ring-4 ring-green-300
                                @else
                                    {{-- ESTADO INATIVO: Verde original --}}
                                    bg-green-600 text-white hover:bg-green-700
                                @endif">
                            Clientes
                        </a>
                    </div>

                    <!-- Botões de Criação Adaptativos -->
                    <div class="flex space-x-3 w-full sm:w-auto justify-end">
                        <!-- Botão para Cadastrar Novo Gestor (Cor Roxo/Indigo) -->
                        <a href="{{ route('admin.users.create', ['role' => 'gestor']) }}"
                            class="flex-shrink-0 w-1/2 sm:w-auto px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-indigo-700 transition duration-200 text-center">
                            + Novo Gestor
                        </a>

                        <!-- Botão para Cadastrar Novo Cliente (Cor Verde) -->
                        <a href="{{ route('admin.users.create', ['role' => 'cliente']) }}"
                            class="flex-shrink-0 w-1/2 sm:w-auto px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-green-700 transition duration-200 text-center">
                            + Novo Cliente
                        </a>
                    </div>
                </div>

                <!-- INÍCIO DO CAMPO DE PESQUISA -->
                <div class="mb-6">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="flex items-center space-x-2">
                        {{-- Preserva o filtro de função --}}
                        <input type="hidden" name="role_filter" value="{{ $roleFilter ?? '' }}">

                        <input type="text" name="search" placeholder="Buscar por nome, email ou contato..."
                               value="{{ $search ?? '' }}"
                               class="flex-grow p-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500">

                        <button type="submit"
                                class="px-4 py-2 bg-indigo-500 text-white font-semibold rounded-lg shadow-md hover:bg-indigo-600 transition duration-200">
                            Buscar
                        </button>

                        @if (!empty($search))
                            {{-- Botão para Limpar a Busca --}}
                            <a href="{{ route('admin.users.index', ['role_filter' => $roleFilter ?? null]) }}"
                                class="px-3 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg shadow-md hover:bg-gray-300 transition duration-200"
                                title="Limpar busca">
                                X
                            </a>
                        @endif
                    </form>
                </div>
                <!-- FIM DO CAMPO DE PESQUISA -->

                <!-- Tabela de Usuários Atualizada -->
                <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                {{-- A COLUNA ID FOI REMOVIDA AQUI --}}
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Função (Role)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Contato (WhatsApp)</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider min-w-[150px]">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($users as $user)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    {{-- DADOS DO USUÁRIO SEM O ID --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $user->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if ($user->role === 'admin')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Admin</span>
                                        @elseif ($user->role === 'gestor')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">Gestor</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Cliente</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                        {{ $user->whatsapp_contact ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium min-w-[150px]">
                                        <div class="flex justify-center space-x-3 items-center">

                                            <!-- Botão de Reservas (Apenas para Clientes) -->
                                            @if ($user->role === 'cliente')
                                                <a href="{{ route('admin.users.reservas', $user) }}"
                                                   class="text-green-600 hover:text-green-800 transition duration-150 p-1 bg-green-100 rounded-full"
                                                   title="Ver Reservas Agendadas">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                </a>
                                            @endif

                                            <!-- Link de Edição -->
                                            <a href="{{ route('admin.users.edit', $user) }}"
                                               class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-600 transition duration-150"
                                               title="Editar Usuário">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </a>

                                            <!-- Formulário de Exclusão (Chama o modal de confirmação) -->
                                            @if (Auth::check() && Auth::user()->id !== $user->id)
                                                <form id="delete-form-{{ $user->id }}" action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                      onsubmit="return false;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            onclick="showCustomConfirmation(this)"
                                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-600 transition duration-150"
                                                            title="Excluir Usuário"
                                                            data-username="{{ $user->name }}"
                                                            data-userid="{{ $user->id }}">
                                                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                     </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Nenhum usuário encontrado para a função selecionada.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                <div class="mt-6">
                    {{-- CORREÇÃO: Usa appends(request()->query()) para preservar o filtro de função e a busca --}}
                    {{ $users->appends(request()->query())->links() }}
                </div>

            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- INÍCIO DO MODAL DE CONFIRMAÇÃO PERSONALIZADO (ADICIONADO PARA O DELETE) -->
    <!-- ========================================================================= -->
    <div id="confirmation-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 z-50 hidden transition-opacity duration-300 ease-out">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-2xl p-6 m-4 max-w-sm w-full transform transition-all duration-300 scale-95 opacity-0" id="modal-content">
                <div class="flex flex-col items-center">
                    <!-- Ícone de Aviso -->
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.398 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>

                    <h3 class="mt-4 text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                        Confirmar Exclusão
                    </h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                            Você tem certeza que deseja excluir o usuário <strong id="username-placeholder"></strong> (ID: <span id="userid-placeholder"></span>)?
                        </p>
                        <p class="mt-2 text-sm font-semibold text-red-600 dark:text-red-400 text-center">
                            Esta ação não pode ser desfeita.
                        </p>
                    </div>
                </div>

                <div class="mt-5 sm:mt-6 gap-6 sm:flex sm:flex-row-reverse space-y-3 sm:space-y-0 sm:space-x-3 sm:space-x-reverse">
                    <button type="button" id="confirm-delete-btn"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition duration-150 ease-in-out">
                        Excluir Permanentemente
                    </button>
                    <button type="button" onclick="closeCustomConfirmation()"
                            class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none sm:w-auto sm:text-sm transition duration-150 ease-in-out">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- ========================================================================= -->
    <!-- FIM DO MODAL DE CONFIRMAÇÃO PERSONALIZADO -->
    <!-- ========================================================================= -->


    <!-- ========================================================================= -->
    <!-- INÍCIO DO SCRIPT DE CONTROLE DO MODAL (ADICIONADO) -->
    <!-- ========================================================================= -->
    <script>
        let formToSubmit = null; // Variável para armazenar o formulário que será enviado

        /**
         * Abre o modal de confirmação e configura os dados do usuário.
         * @param {HTMLElement} button - O botão clicado (dentro do formulário).
         */
        function showCustomConfirmation(button) {
            // 1. Encontra o formulário pai do botão
            formToSubmit = button.closest('form');
            if (!formToSubmit) {
                console.error('Formulário de exclusão não encontrado.');
                return;
            }

            // 2. Extrai os dados do usuário a partir dos atributos data-* do botão
            const userName = button.getAttribute('data-username');
            const userId = button.getAttribute('data-userid');

            // 3. Atualiza o conteúdo do modal
            document.getElementById('username-placeholder').textContent = userName;
            document.getElementById('userid-placeholder').textContent = userId;

            // 4. Exibe o modal
            const modal = document.getElementById('confirmation-modal');
            const modalContent = document.getElementById('modal-content');

            modal.classList.remove('hidden');
            // Timeout para garantir que a transição de opacidade/escala ocorra após o display
            setTimeout(() => {
                modal.style.opacity = '1';
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10); // Pequeno atraso para triggerar a transição CSS
        }

        /**
         * Fecha o modal de confirmação.
         */
        function closeCustomConfirmation() {
            const modal = document.getElementById('confirmation-modal');
            const modalContent = document.getElementById('modal-content');

            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            modal.style.opacity = '0';

            // Esconde o modal após a transição
            setTimeout(() => {
                modal.classList.add('hidden');
                formToSubmit = null; // Limpa o formulário armazenado
            }, 300);
        }

        /**
         * Adiciona o listener para o botão de "Excluir Permanentemente" dentro do modal.
         * Quando clicado, ele submete o formulário correto.
         */
        document.addEventListener('DOMContentLoaded', function() {
            const confirmBtn = document.getElementById('confirm-delete-btn');

            confirmBtn.addEventListener('click', function() {
                if (formToSubmit) {
                    // Fecha o modal imediatamente
                    closeCustomConfirmation();

                    // Submete o formulário DELETE
                    formToSubmit.submit();
                }
            });

            // Opcional: Fechar o modal ao clicar fora
            const modal = document.getElementById('confirmation-modal');
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeCustomConfirmation();
                }
            });
        });
    </script>
    <!-- ========================================================================= -->
    <!-- FIM DO SCRIPT DE CONTROLE DO MODAL -->
    <!-- ========================================================================= -->
</x-app-layout>
