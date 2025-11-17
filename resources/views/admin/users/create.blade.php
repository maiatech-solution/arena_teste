<x-app-layout>
    {{-- Captura a função da URL ou define um padrão se não estiver presente --}}
    @php
        $role = request()->query('role', 'cliente');
        $isGestor = in_array($role, ['gestor', 'admin']);
        $pageTitle = $isGestor ? 'Cadastrar Novo Gestor/Admin' : 'Cadastrar Novo Cliente';
        $formTitle = $isGestor ? 'Preencha os dados do novo Gestor ou Administrador' : 'Preencha os dados do novo Cliente';
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    {{-- A largura da div principal foi alterada de max-w-3xl para max-w-7xl para corresponder ao layout do index --}}
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 border-b pb-3">{{ $formTitle }}</h3>

                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
                    @csrf

                    <!-- Campo Nome -->
                    <div>
                        <x-input-label for="name" value="Nome Completo" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <!-- Campo Email -->
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                    </div>

                    <!-- Campo Função (Role) - Fixo, mas visível -->
                    <div>
                        <x-input-label for="role_display" value="Função" />
                        <x-text-input id="role_display" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed"
                                      value="{{ ucfirst($role) }}" disabled />

                        {{-- Input Oculto para enviar o valor real da função --}}
                        <input type="hidden" name="role" value="{{ $role }}">
                        <x-input-error class="mt-2" :messages="$errors->get('role')" />
                    </div>

                    <!-- Campo Contato WhatsApp -->
                    <div>
                        <x-input-label for="whatsapp_contact" value="Contato WhatsApp" />
                        <x-text-input id="whatsapp_contact" name="whatsapp_contact" type="text" class="mt-1 block w-full"
                                      :value="old('whatsapp_contact')" placeholder="(99) 99999-9999" required />
                        <x-input-error class="mt-2" :messages="$errors->get('whatsapp_contact')" />
                    </div>

                    {{-- Campos de Senha (Visível Apenas para Gestores/Admins) --}}
                    @if ($isGestor)
                        <hr class="border-gray-200 dark:border-gray-700 pt-4">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Campo Senha -->
                            <div>
                                <x-input-label for="password" value="Senha" />
                                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                                <x-input-error class="mt-2" :messages="$errors->get('password')" />
                            </div>

                            <!-- Campo Confirmação de Senha -->
                            <div>
                                <x-input-label for="password_confirmation" value="Confirmação de Senha" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                                <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
                            </div>
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">A senha é obrigatória para Gestores/Administradores.</p>
                    @else
                        {{-- Mensagem para Clientes --}}
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                            O campo de senha está oculto para Clientes. Se necessário, a senha será definida através de um fluxo de convite ou primeiro login.
                        </p>
                    @endif

                    <div class="flex items-center justify-end mt-4">
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4 transition duration-150">
                            Cancelar
                        </a>

                        <x-primary-button class="ml-4">
                            Cadastrar {{ ucfirst($role) }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
