<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Configuração da Unidade - MaiaTech</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 font-sans antialiased text-gray-200">
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-gray-900 via-gray-950 to-black">

        <div class="mb-8 text-center space-y-2">
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                MaiaTech <span class="text-orange-500">Solution</span>
            </h1>
            <div class="h-1 w-24 bg-gradient-to-r from-transparent via-orange-600 to-transparent mx-auto rounded-full shadow-[0_0_15px_rgba(234,88,12,0.4)]"></div>
        </div>

        <div class="max-w-4xl w-full">
            <div class="bg-gray-900/40 backdrop-blur-xl border border-gray-800 p-8 md:p-10 rounded-[3rem] shadow-2xl">

                <div class="mb-8 text-center">
                    <h2 class="text-2xl font-bold text-white tracking-tight">
                        Bem vindo ao Sistema de gestão da <span class="text-orange-500 italic">MaiaTech</span>.
                    </h2>
                    <p class="text-gray-400 mt-2 text-base font-medium">
                        Gestor, primeiro preencha os dados básicos da unidade!
                    </p>
                </div>

                <form action="{{ route('onboarding.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="space-y-1 md:col-span-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">Nome Fantasia / Unidade</label>
                            <input type="text" name="nome_fantasia" required
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-3 px-6 transition-all placeholder:text-gray-700"
                                placeholder="Ex: Arena Soccer Beach">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">CNPJ (Opcional)</label>
                            <input type="text" name="cnpj"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-3 px-6 transition-all"
                                placeholder="00.000.000/0000-00">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">WhatsApp Suporte</label>
                            <input type="text" name="whatsapp_suporte"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-3 px-6 transition-all"
                                placeholder="91988887777">
                        </div>

                        <div class="md:col-span-2 pt-4 border-t border-gray-800/50">
                            <h3 class="text-sm font-bold text-orange-500 uppercase tracking-widest italic mb-4">Localização da Unidade</h3>
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">CEP</label>
                            <input type="text" name="cep" id="cep" onblur="consultarCep(this.value)"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-3 px-6 transition-all"
                                placeholder="00000-000">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">Logradouro / Rua</label>
                            <input type="text" name="logradouro" id="logradouro"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-3 px-6 transition-all"
                                placeholder="Rua, Avenida...">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">Número</label>
                            <input type="text" name="numero" id="numero"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-3 px-6 transition-all"
                                placeholder="123">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">Bairro</label>
                            <input type="text" name="bairro" id="bairro"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-3 px-6 transition-all">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">Cidade</label>
                            <input type="text" name="cidade" id="cidade"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-3 px-6 transition-all">
                        </div>

                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">Estado (UF)</label>
                            <input type="text" name="estado" id="estado" maxlength="2"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-3 px-6 transition-all"
                                placeholder="PA">
                        </div>
                    </div>

                    <div class="pt-6">
                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-500 text-white font-black py-4 rounded-[2rem] uppercase tracking-[0.2em] transition-all shadow-xl shadow-orange-900/20 active:scale-[0.98]">
                            Salvar e Escolher Módulos
                        </button>
                    </div>
                </form>

            </div>

            <div class="pt-8 text-center">
                <p class="text-[10px] text-gray-600 uppercase font-black tracking-[0.5em] opacity-40 italic">MaiaTech Solution &copy; 2026</p>
            </div>
        </div>
    </div>

    <script>
        function consultarCep(cep) {
            const valor = cep.replace(/\D/g, '');
            if (valor.length === 8) {
                // Efeito visual simples de loading nos campos
                const campos = ['logradouro', 'bairro', 'cidade', 'estado'];
                campos.forEach(id => document.getElementById(id).placeholder = 'Buscando...');

                fetch(`https://viacep.com.br/ws/${valor}/json/`)
                    .then(response => response.json())
                    .then(dados => {
                        if (!dados.erro) {
                            document.getElementById('logradouro').value = dados.logradouro;
                            document.getElementById('bairro').value = dados.bairro;
                            document.getElementById('cidade').value = dados.localidade;
                            document.getElementById('estado').value = dados.uf;
                            document.getElementById('numero').focus();
                        } else {
                            alert('CEP não encontrado.');
                        }
                    })
                    .catch(error => console.error('Erro na busca do CEP:', error));
            }
        }
    </script>
</body>
</html>
