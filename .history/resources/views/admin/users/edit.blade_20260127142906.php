<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Usuário: ' . $user->name) }}
        </h2>
    </x-slot>

    <div class="max-w-4xl mx-auto mt-6 sm:px-6 lg:px-8">
        <div style="background: #111827; color: #10b981; padding: 20px; font-family: 'Courier New', monospace; border-radius: 10px; border: 2px solid #ef4444; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <h3 style="color: #fff; margin-bottom: 10px; border-bottom: 1px solid #374151; padding-bottom: 5px;">🔎 DADOS BRUTOS DO BANCO</h3>
            <p><strong>Nome:</strong> {{ $user->name }}</p>
            <p><strong>Valor exato em 'customer_qualification':</strong> <span style="background: #ef4444; color: white; padding: 2px 5px;">"{{ $user->customer_qualification }}"</span></p>
            <p><strong>O que o sistema gera para Tag:</strong> <code>{{ htmlspecialchars($user->status_tag) }}</code></p>
            <hr style="border: 0; border-top: 1px solid #374151; margin: 10px 0;">
            <p><strong>Lista de todos os campos:</strong></p>
            <pre style="font-size: 12px; color: #a7f3d0; overflow-x: auto;">{{ print_r($user->getAttributes(), true) }}</pre>
        </div>
    </div>


    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                <div class="mb-6">
                    {{-- Usa a função atual do usuário para voltar ao filtro correto --}}
                    <a href="{{ route('admin.users.index', ['role_filter' => $user->role]) }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Voltar à Lista
                    </a>
                </div>


                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT') {{-- Usamos PUT para atualizações RESTful --}}

                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6 border-b pb-3">
                        Dados Pessoais
                    </h3>


                    @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg shadow-md" role="alert">
                        {{ session('error') }}
                    </div>
                    @endif


                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nome Completo</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required autofocus
                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('name') border-red-500 @enderror">
                        @error('name')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>


                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email (Login)</label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 @error('email') border-red-500 @enderror">
                        @error('email')
                        <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>


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
                            class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            <option value="cliente" {{ old('role', $user->role) == 'cliente' ? 'selected' : '' }}>Cliente</option>
                            <option value="gestor" {{ old('role', $user->role) == 'gestor' ? 'selected' : '' }}>Gestor</option>
                            @if (Auth::user()->role === 'admin')
                            <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin (Super Administrador)</option>
                            @elseif ($user->role === 'admin')
                            <option value="admin" selected disabled>Admin (Somente Super Admin pode alterar)</option>
                            @endif
                        </select>
                    </div>


                    {{-- 🌟 Container VIP --}}
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


                    {{-- 🚫 Container Blacklist (Versão Reforçada com str_contains) --}}
                    <div id="blacklist-container" class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-100 dark:border-red-800 {{ old('role', $user->role) !== 'cliente' ? 'hidden' : '' }}">
                        <div class="flex items-center">
                            @php
                                // Detecta se a palavra "blacklist" está presente na qualificação (independente de maiúsculas)
                                $currentStatus = strtolower($user->customer_qualification ?? '');
                                $hasBlacklistStatus = str_contains($currentStatus, 'blacklist');
                            @endphp
                            <input type="checkbox" name="is_blacklisted" id="is_blacklisted" value="1"
                                {{ (old('is_blacklisted') || $hasBlacklistStatus) ? 'checked' : '' }}
                                class="h-5 w-5 text-red-600 border-gray-300 rounded focus:ring-red-500">
                            <label for="is_blacklisted" class="ml-3">
                                <span class="block text-sm font-bold text-red-900 dark:text-red-300 uppercase tracking-wider">🚫 Lista Negra (Blacklist)</span>
                                <span class="block text-xs text-red-600 dark:text-red-400">Marque para restringir ou desmarque para remover este cliente da Blacklist.</span>
                            </label>
                        </div>
                    </div>


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

                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar Nova Senha</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
                                class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                        </div>
                    </div>


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
            const blacklistContainer = document.getElementById('blacklist-container');

            if (this.value === 'cliente') {
                vipContainer.classList.remove('hidden');
                blacklistContainer.classList.remove('hidden');
            } else {
                vipContainer.classList.add('hidden');
                blacklistContainer.classList.add('hidden');
                document.getElementById('is_vip').checked = false;
                document.getElementById('is_blacklisted').checked = false;
            }
        });
    </script>
</x-app-layout>
