<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Usuário: ' . $user->name) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Botão de Volta -->
                <div class="mb-6">
                    {{-- Usa a função atual do usuário para voltar ao filtro correto --}}
                    <a href="{{ route('admin.users.index', ['role_filter' => $user->role]) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Voltar à Lista
                    </a>
                </div>

                <!-- Formulário de Edição -->
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT') {{-- Usamos PUT para atualizações RESTful --}}

                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 border-b pb-3">
                        Dados Pessoais
                    </h3>

                    <!-- Feedback de Erro de Sessão -->
                    @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow-md" role="alert">
                        {{ session('error') }}
                    </div>
                    @endif


                    <!-- 1. Nome -->
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome Completo</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required autofocus
                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('name') border-red-500 @enderror">
                        @error('name')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 2. Email -->
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email (Login)</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('email') border-red-500 @enderror">
                        @error('email')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 3. Contato WhatsApp -->
                    <div class="mb-6">
                        <label for="whatsapp_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contato WhatsApp (Opcional)</label>
                        <input type="text" name="whatsapp_contact" id="whatsapp_contact" value="{{ old('whatsapp_contact', $user->whatsapp_contact) }}"
                            placeholder="Ex: 91988887777"
                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('whatsapp_contact') border-red-500 @enderror">
                        @error('whatsapp_contact')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 border-b pb-3">
                        Função e Senha
                    </h3>

                    <div class="mb-6">
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Função/Permissão</label>
                        <select name="role" id="role" required
                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('role') border-red-500 @enderror">
                            <option value="cliente" {{ old('role', $user->role) == 'cliente' ? 'selected' : '' }}>Cliente</option>
                            <option value="gestor" {{ old('role', $user->role) == 'gestor' ? 'selected' : '' }}>Gestor</option>
                            @if (Auth::user()->role === 'admin')
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin (Super Administrador)</option>
                            @elseif ($user->role === 'admin')
                            <option value="admin" selected disabled>Admin (Somente Super Admin pode alterar)</option>
                            @endif
                        </select>
                    </div>

                    <div id="vip-container" class="mb-6 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg border border-indigo-100 dark:border-indigo-800 {{ old('role', $user->role) !== 'cliente' ? 'hidden' : '' }}">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_vip" id="is_vip" value="1"
                                {{ old('is_vip', $user->is_vip) ? 'checked' : '' }}
                                class="h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <label for="is_vip" class="ml-3">
                                <span class="block text-sm font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider">🌟 Status VIP</span>
                                <span class="block text-xs text-indigo-600 dark:text-indigo-400">Ative para dar destaque e prioridade a este cliente.</span>
                            </label>
                        </div>
                    </div>

                    <!-- 5. Senha (Opcional) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t border-gray-200 dark:border-gray-700 pt-6">
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nova Senha (Opcional)</label>
                            <input type="password" name="password" id="password" autocomplete="new-password"
                                placeholder="Preencha apenas para alterar"
                                class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('password') border-red-500 @enderror">
                            @error('password')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- 6. Confirmação de Senha -->
                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar Nova Senha</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
                                class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                        </div>
                    </div>


                    <!-- Botão de Atualização -->
                    <div class="flex justify-end mt-6">
                        <button type="submit"
                            class="px-6 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 transition duration-150 shadow-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Salvar Alterações
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <script>
        document.getElementById('role').addEventListener('change', function() {
            const vipContainer = document.getElementById('vip-container');
            if (this.value === 'cliente') {
                vipContainer.classList.remove('hidden');
            } else {
                vipContainer.classList.add('hidden');
                document.getElementById('is_vip').checked = false;
            }
        });
    </script>
</x-app-layout>