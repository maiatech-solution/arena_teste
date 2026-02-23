<x-bar-layout>
    {{-- Iniciamos o Alpine.js com o estado 'editando: false' --}}
    <div class="py-12 bg-black min-h-screen" x-data="{ editando: false }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="flex items-center justify-between mb-8 px-4 sm:px-0">
                <div class="flex items-center gap-4">
                    <a href="{{ route('bar.dashboard') }}"
                        class="bg-gray-800 hover:bg-gray-700 text-orange-500 p-3 rounded-2xl transition-all border border-gray-700 shadow-lg group"
                        title="Voltar ao Painel">
                        <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block">◀</span>
                    </a>
                    <div>
                        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
                            Informações da <span class="text-orange-600">{{ $company->nome_fantasia }}</span>
                        </h1>
                        <p class="text-gray-500 font-bold uppercase text-[10px] tracking-widest mt-1 italic">
                            Gerenciamento de dados e localização</p>
                    </div>
                </div>

                {{-- Botão que alterna o estado de edição --}}
                <button type="button" @click="editando = !editando"
                    :class="editando ? 'bg-gray-700 text-gray-300' : 'bg-orange-600 text-white shadow-lg shadow-orange-600/20'"
                    class="px-6 py-3 rounded-2xl font-black text-xs uppercase tracking-widest transition-all active:scale-95">
                    <span x-show="!editando">🔓 Editar Informações</span>
                    <span x-show="editando">❌ Cancelar</span>
                </button>
            </div>

            <div
                class="bg-gray-900 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)] rounded-[2.5rem] border border-gray-800 p-10">

                @if (session('success'))
                    <div
                        class="mb-6 p-4 bg-green-900/50 border border-green-500 text-green-200 rounded-xl font-bold flex items-center gap-3">
                        <span>✅</span> {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('bar.company.update') }}" class="space-y-10">
                    @csrf
                    @method('PUT')

                    {{-- O fieldset :disabled="!editando" bloqueia todos os campos de uma vez --}}
                    <fieldset :disabled="!editando" class="space-y-10 transition-all duration-500"
                        :class="!editando ? 'opacity-60 pointer-events-none' : 'opacity-100'">

                        {{-- 🏢 INFORMAÇÕES BÁSICAS --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2 group">
                                <label
                                    class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">Nome
                                    do Estabelecimento</label>
                                <input type="text" name="nome_fantasia"
                                    value="{{ old('nome_fantasia', $company->nome_fantasia) }}" required
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner">
                            </div>

                            <div class="space-y-2 group">
                                <label
                                    class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">CNPJ
                                    (Opcional)</label>
                                <input type="text" name="cnpj" value="{{ old('cnpj', $company->cnpj) }}"
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner">
                            </div>

                            <div class="space-y-2 group">
                                <label
                                    class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">E-mail
                                    de Contato</label>
                                <input type="email" name="email_contato"
                                    value="{{ old('email_contato', $company->email_contato) }}"
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner">
                            </div>

                            <div class="space-y-2 group">
                                <label
                                    class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">WhatsApp
                                    da Arena</label>
                                <input type="text" name="whatsapp_suporte"
                                    value="{{ old('whatsapp_suporte', $company->whatsapp_suporte) }}" maxlength="11"
                                    required
                                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner">
                            </div>
                        </div>

                        {{-- 📍 LOCALIZAÇÃO --}}
                        <div class="border-t border-gray-800 pt-8">
                            <h3
                                class="text-orange-600 font-black uppercase text-xs tracking-[0.2em] mb-8 flex items-center gap-2">
                                <span>📍</span> Localização ({{ $company->cidade }} - {{ $company->estado }})
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="space-y-2 group">
                                    <label
                                        class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">CEP</label>
                                    <input type="text" name="cep" id="cep"
                                        value="{{ old('cep', $company->cep) }}" onblur="consultarCep(this.value)"
                                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner">
                                </div>

                                <div class="md:col-span-2 space-y-2 group">
                                    <label
                                        class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">Logradouro</label>
                                    <input type="text" name="logradouro" id="logradouro"
                                        value="{{ old('logradouro', $company->logradouro) }}" required
                                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner">
                                </div>

                                <div class="space-y-2 group">
                                    <label
                                        class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">Número</label>
                                    <input type="text" name="numero" id="numero"
                                        value="{{ old('numero', $company->numero) }}" required
                                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner">
                                </div>

                                <div class="space-y-2 group">
                                    <label
                                        class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">Bairro</label>
                                    <input type="text" name="bairro" id="bairro"
                                        value="{{ old('bairro', $company->bairro) }}" required
                                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner">
                                </div>

                                <div class="space-y-2 group">
                                    <label
                                        class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">Cidade</label>
                                    <input type="text" name="cidade" id="cidade"
                                        value="{{ old('cidade', $company->cidade) }}" required
                                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner">
                                </div>

                                <div class="space-y-2 group">
                                    <label
                                        class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 group-focus-within:text-orange-500 transition-colors">UF</label>
                                    <input type="text" name="estado" id="estado"
                                        value="{{ old('estado', $company->estado) }}" maxlength="2" required
                                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner text-center">
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    {{-- Botão de Salvar - Só aparece se 'editando' for true --}}
                    <div class="pt-6" x-show="editando" x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-95">
                        <button type="submit"
                            class="w-full bg-green-600 hover:bg-green-500 text-white font-black py-5 rounded-2xl transition-all uppercase text-xs tracking-widest shadow-lg shadow-green-600/20 active:scale-95 flex items-center justify-center gap-2">
                            💾 Confirmar e Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.consultarCep = function(cep) {
            const valor = cep.replace(/\D/g, '');

            if (valor.length === 8) {
                // Feedback visual direto nos elementos
                const campos = ['logradouro', 'bairro', 'cidade', 'estado'];
                campos.forEach(id => {
                    const el = document.getElementById(id);
                    el.disabled = false; // Habilitamos temporariamente para o JS escrever
                    el.value = '...';
                });

                const script = document.createElement('script');
                script.src = `https://viacep.com.br/ws/${valor}/json/?callback=preencherCampos`;
                document.body.appendChild(script);
            }
        };

        window.preencherCampos = function(dados) {
            if (!dados.erro) {
                document.getElementById('logradouro').value = dados.logradouro;
                document.getElementById('bairro').value = dados.bairro;
                document.getElementById('cidade').value = dados.localidade;
                document.getElementById('estado').value = dados.uf;

                // Focamos no número se ele não estiver bloqueado
                const campoNumero = document.getElementById('numero');
                if (!campoNumero.disabled) {
                    campoNumero.focus();
                }
            } else {
                alert("CEP não encontrado.");
                limparCampos();
            }

            // Se o Alpine ainda estiver em modo de visualização, voltamos a bloquear os campos
            // O próprio fieldset do Alpine cuidará disso automaticamente no próximo ciclo

            const scripts = document.querySelectorAll('script[src^="https://viacep.com.br"]');
            scripts.forEach(s => s.remove());
        };

        function limparCampos() {
            ['logradouro', 'bairro', 'cidade', 'estado'].forEach(id => {
                document.getElementById(id).value = '';
            });
        }
    </script>
</x-bar-layout>
