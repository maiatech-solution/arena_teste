<x-app-layout>
    @php
        // 1. Definições de Hierarquia e Papéis
        $requestedRole = request()->query('role', 'cliente');
        $myRole = auth()->user()->role;

        // 🛡️ Segurança: Se um Gestor tentar forçar 'admin' via URL, rebaixamos para 'gestor'
        if ($myRole === 'gestor' && $requestedRole === 'admin') {
            $requestedRole = 'gestor';
        }

        // Determina se estamos criando alguém da Equipe (Staff) ou um Cliente
        $isStaff = in_array($requestedRole, ['gestor', 'admin', 'colaborador']);

        $pageTitle = $isStaff ? 'Cadastrar Novo Membro da Equipe' : 'Cadastrar Novo Cliente';
        $formTitle = $isStaff ? 'Preencha os dados de acesso da equipe' : 'Preencha os dados do novo Cliente';
    @endphp

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $pageTitle }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6 border-b pb-3">{{ $formTitle }}</h3>

                <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Nome --}}
                        <div>
                            <x-input-label for="name" value="Nome Completo" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        {{-- Email --}}
                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Função / Cargo (DINÂMICO) --}}
                        <div>
                            <x-input-label for="role" value="Função / Cargo" />

                            @if($myRole === 'admin' && $isStaff)
                                {{-- Admin Master escolhe qualquer um da equipe --}}
                                <select name="role" id="role" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-bold">
                                    <option value="admin" {{ $requestedRole == 'admin' ? 'selected' : '' }}>Administrador</option>
                                    <option value="gestor" {{ $requestedRole == 'gestor' ? 'selected' : '' }}>Gestor</option>
                                    <option value="colaborador" {{ $requestedRole == 'colaborador' ? 'selected' : '' }}>Colaborador</option>
                                </select>
                            @elseif($myRole === 'gestor' && $isStaff)
                                {{-- Gestor só cria outro Gestor ou Colaborador --}}
                                <select name="role" id="role" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-bold">
                                    <option value="gestor" {{ $requestedRole == 'gestor' ? 'selected' : '' }}>Gestor</option>
                                    <option value="colaborador" {{ $requestedRole == 'colaborador' ? 'selected' : '' }}>Colaborador</option>
                                </select>
                            @else
                                {{-- Cliente ou visualização travada --}}
                                <x-text-input id="role_display" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700 cursor-not-allowed font-bold"
                                              value="{{ $requestedRole === 'cliente' ? 'Cliente' : ucfirst($requestedRole) }}" disabled />
                                <input type="hidden" name="role" value="{{ $requestedRole }}">
                            @endif
                            <x-input-error class="mt-2" :messages="$errors->get('role')" />
                        </div>

                        {{-- Contato WhatsApp --}}
                        <div>
                            <x-input-label for="whatsapp_contact" value="Contato WhatsApp" />
                            <x-text-input id="whatsapp_contact" name="whatsapp_contact" type="text" class="mt-1 block w-full"
                                          :value="old('whatsapp_contact')" placeholder="(99) 99999-9999" maxlength="15" required />
                            <x-input-error class="mt-2" :messages="$errors->get('whatsapp_contact')" />
                        </div>
                    </div>

                    {{-- Campos de Senha (Somente para Equipe/Staff) --}}
                    @if ($isStaff)
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase mb-4">Definição de Senha de Acesso</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="password" value="Senha" />
                                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('password')" />
                                </div>
                                <div>
                                    <x-input-label for="password_confirmation" value="Confirmação de Senha" />
                                    <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-end mt-8 border-t pt-6">
                        <a href="{{ route('admin.users.index') }}" class="text-sm font-bold text-gray-500 hover:text-red-600 transition duration-150 mr-6 uppercase">
                            Cancelar
                        </a>

                        <x-primary-button>
                            Finalizar Cadastro
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Script de Máscara de Telefone --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputTel = document.getElementById('whatsapp_contact');
            if (inputTel) {
                inputTel.addEventListener('input', (e) => {
                    let v = e.target.value.replace(/\D/g, "");
                    v = v.replace(/^(\d{2})(\d)/g, "($1) $2");
                    v = v.replace(/(\d)(\d{4})$/, "$1-$2");
                    e.target.value = v;
                });
            }
        });
    </script>
</x-app-layout>
