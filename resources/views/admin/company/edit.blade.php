<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dados do Estabelecimento') }}
        </h2>
    </x-slot>

    {{-- Iniciamos o Alpine.js com o estado 'editando: false' --}}
    <div class="py-12" x-data="{ editando: false }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-4 font-medium text-sm text-green-600 bg-green-100 p-4 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border">
                <div class="p-6 text-gray-900">
                    
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-700">
                            <i class="fas fa-building mr-2"></i>Informações da Elite Soccer
                        </h3>
                        
                        {{-- Botão que alterna o estado --}}
                        <button type="button" 
                                @click="editando = !editando" 
                                :class="editando ? 'bg-gray-500 hover:bg-gray-600' : 'bg-indigo-600 hover:bg-indigo-700'"
                                class="inline-flex items-center px-4 py-2 rounded-md font-semibold text-xs text-white uppercase tracking-widest transition ease-in-out duration-150">
                            <span x-show="!editando">{{ __('Editar Informações') }}</span>
                            <span x-show="editando">{{ __('Cancelar') }}</span>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('admin.company.update') }}">
                        @csrf
                        @method('PUT')

                        {{-- Grupo de Campos --}}
                        <fieldset :disabled="!editando" class="space-y-8">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div :class="!editando ? 'opacity-70' : ''">
                                    <x-input-label for="nome_fantasia" :value="__('Nome do Estabelecimento')" />
                                    <x-text-input id="nome_fantasia" name="nome_fantasia" type="text" class="mt-1 block w-full bg-gray-50" :value="old('nome_fantasia', $info->nome_fantasia)" required />
                                </div>

                                <div :class="!editando ? 'opacity-70' : ''">
                                    <x-input-label for="cnpj" :value="__('CNPJ (Opcional)')" />
                                    <x-text-input id="cnpj" name="cnpj" type="text" class="mt-1 block w-full bg-gray-50" :value="old('cnpj', $info->cnpj)" />
                                </div>

                                <div :class="!editando ? 'opacity-70' : ''">
                                    <x-input-label for="email_contato" :value="__('E-mail de Contato')" />
                                    <x-text-input id="email_contato" name="email_contato" type="email" class="mt-1 block w-full bg-gray-50" :value="old('email_contato', $info->email_contato)" />
                                </div>

                                <div :class="!editando ? 'opacity-70' : ''">
                                    <x-input-label for="whatsapp_suporte" :value="__('WhatsApp da arena')" />
                                    <x-text-input id="whatsapp_suporte" name="whatsapp_suporte" type="text" class="mt-1 block w-full bg-gray-50" :value="old('whatsapp_suporte', $info->whatsapp_suporte)" maxlength="11" />
                                </div>
                            </div>

                            <div class="border-t pt-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Localização (Belém - PA)</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div :class="!editando ? 'opacity-70' : ''">
                                        <x-input-label for="cep" :value="__('CEP')" />
                                        <x-text-input id="cep" name="cep" type="text" class="mt-1 block w-full bg-gray-50" :value="old('cep', $info->cep)" onblur="consultarCep(this.value)" />
                                    </div>

                                    <div class="md:col-span-2" :class="!editando ? 'opacity-70' : ''">
                                        <x-input-label for="logradouro" :value="__('Logradouro')" />
                                        <x-text-input id="logradouro" name="logradouro" type="text" class="mt-1 block w-full bg-gray-50" :value="old('logradouro', $info->logradouro)" />
                                    </div>

                                    <div :class="!editando ? 'opacity-70' : ''">
                                        <x-input-label for="numero" :value="__('Número')" />
                                        <x-text-input id="numero" name="numero" type="text" class="mt-1 block w-full bg-gray-50" :value="old('numero', $info->numero)" />
                                    </div>

                                    <div :class="!editando ? 'opacity-70' : ''">
                                        <x-input-label for="bairro" :value="__('Bairro')" />
                                        <x-text-input id="bairro" name="bairro" type="text" class="mt-1 block w-full bg-gray-50" :value="old('bairro', $info->bairro)" />
                                    </div>

                                    <div :class="!editando ? 'opacity-70' : ''">
                                        <x-input-label for="cidade" :value="__('Cidade')" />
                                        <x-text-input id="cidade" name="cidade" type="text" class="mt-1 block w-full bg-gray-50" :value="old('cidade', $info->cidade)" />
                                    </div>

                                    <div :class="!editando ? 'opacity-70' : ''">
                                        <x-input-label for="estado" :value="__('UF')" />
                                        <x-text-input id="estado" name="estado" type="text" class="mt-1 block w-full bg-gray-50" :value="old('estado', $info->estado)" maxlength="2" />
                                    </div>
                                </div>
                            </div>
                        </fieldset>

                        {{-- Botão de Salvar só aparece quando estiver editando --}}
                        <div class="mt-8 pt-6 border-t flex justify-end" x-show="editando" x-transition>
                            <x-primary-button class="bg-green-600 hover:bg-green-700">
                                {{ __('Confirmar e Salvar Alterações') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function consultarCep(cep) {
            const valor = cep.replace(/\D/g, '');
            if (valor.length === 8) {
                fetch(`https://viacep.com.br/ws/${valor}/json/`)
                    .then(response => response.json())
                    .then(dados => {
                        if (!dados.erro) {
                            document.getElementById('logradouro').value = dados.logradouro;
                            document.getElementById('bairro').value = dados.bairro;
                            document.getElementById('cidade').value = dados.localidade;
                            document.getElementById('estado').value = dados.uf;
                            document.getElementById('numero').focus();
                        }
                    });
            }
        }
    </script>
</x-app-layout>