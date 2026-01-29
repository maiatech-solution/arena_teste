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

        <div class="mb-10 text-center space-y-2">
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                MaiaTech <span class="text-orange-500">Solution</span>
            </h1>
            <div class="h-1 w-24 bg-gradient-to-r from-transparent via-orange-600 to-transparent mx-auto rounded-full shadow-[0_0_15px_rgba(234,88,12,0.4)]"></div>
        </div>

        <div class="max-w-4xl w-full">
            <div class="bg-gray-900/40 backdrop-blur-xl border border-gray-800 p-8 md:p-12 rounded-[3rem] shadow-2xl">

                <div class="mb-10 text-center">
                    <h2 class="text-3xl font-bold text-white tracking-tight leading-tight">
                        Bem vindo ao Sistema de gestão da <span class="text-orange-500 italic">MaiaTech</span>.
                    </h2>
                    <p class="text-gray-400 mt-2 text-lg font-medium">
                        Gestor, primeiro preencha os dados básicos da unidade!
                    </p>
                </div>

                <form action="{{ route('onboarding.store') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">Nome Fantasia / Unidade</label>
                            <input type="text" name="nome_fantasia" required
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-4 px-6 transition-all placeholder:text-gray-700"
                                placeholder="Ex: Arena Soccer Beach">
                            @error('nome_fantasia') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">CNPJ (Apenas números)</label>
                            <input type="text" name="cnpj"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-4 px-6 transition-all placeholder:text-gray-700"
                                placeholder="00000000000000">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">WhatsApp Suporte</label>
                            <input type="text" name="whatsapp_suporte"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-4 px-6 transition-all placeholder:text-gray-700"
                                placeholder="91988887777">
                        </div>

                        <div class="space-y-2 md:col-span-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">Cidade</label>
                            <input type="text" name="cidade"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-4 px-6 transition-all"
                                placeholder="Sua Cidade">
                        </div>

                        <div class="space-y-2 md:col-span-1">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2 italic">Estado (UF)</label>
                            <input type="text" name="estado" maxlength="2"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-4 px-6 transition-all"
                                placeholder="Ex: PA">
                        </div>
                    </div>

                    <div class="pt-6">
                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-500 text-white font-black py-5 rounded-[2rem] uppercase tracking-[0.2em] transition-all shadow-xl shadow-orange-900/20 active:scale-[0.98]">
                            Salvar e Escolher Módulos
                        </button>
                    </div>
                </form>

            </div>

            <div class="pt-10 text-center">
                <p class="text-[10px] text-gray-600 uppercase font-black tracking-[0.5em] opacity-40 italic">MaiaTech Solution &copy; Configuração de Primeiro Acesso</p>
            </div>
        </div>
    </div>
</body>
</html>
