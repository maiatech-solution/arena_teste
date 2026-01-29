<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dados da Unidade - MaiaTech</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 font-sans antialiased text-gray-200">
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-gray-900 via-gray-950 to-black">

        <div class="mb-10 text-center space-y-2">
            <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
                MaiaTech <span class="text-orange-500 italic">Solution</span>
            </h1>
            <div class="h-1 w-20 bg-orange-600 mx-auto mt-2 rounded-full"></div>
        </div>

        <div class="max-w-4xl w-full">
            <div class="bg-gray-900/50 backdrop-blur-xl border border-gray-800 p-8 md:p-12 rounded-[2.5rem] shadow-2xl">

                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-white tracking-tight">Informações da <span class="text-orange-500">Unidade</span></h2>
                    <p class="text-gray-400 text-sm mt-1">Preencha os dados abaixo para configurar o sistema do seu cliente.</p>
                </div>

                <form action="{{ route('admin.company.update') }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">Nome Fantasia / Unidade</label>
                            <input type="text" name="nome_fantasia" value="{{ old('nome_fantasia', $info->nome_fantasia) }}"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 transition-all" placeholder="Ex: Arena Soccer Beach">
                            @error('nome_fantasia') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">CNPJ (Apenas números)</label>
                            <input type="text" name="cnpj" value="{{ old('cnpj', $info->cnpj) }}"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 transition-all" placeholder="00000000000000">
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">WhatsApp Suporte</label>
                            <input type="text" name="whatsapp_suporte" value="{{ old('whatsapp_suporte', $info->whatsapp_suporte) }}"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 transition-all" placeholder="919XXXXXXXX">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-800/50 grid grid-cols-1 md:grid-cols-3 gap-6">
                         <div class="space-y-2 md:col-span-2">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">Cidade</label>
                            <input type="text" name="cidade" value="{{ old('cidade', $info->cidade) }}"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 transition-all">
                        </div>

                        <div class="space-y-2">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">Estado (UF)</label>
                            <input type="text" name="estado" value="{{ old('estado', $info->estado) }}" maxlength="2"
                                class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 transition-all" placeholder="PA">
                        </div>
                    </div>

                    <div class="pt-8 flex flex-col md:flex-row gap-4">
                        <button type="submit" class="flex-1 bg-orange-600 hover:bg-orange-700 text-white font-black py-4 rounded-2xl uppercase tracking-widest transition-all shadow-lg shadow-orange-900/20">
                            Salvar e Continuar
                        </button>
                    </div>
                </form>

            </div>

            <p class="text-center mt-8 text-[10px] text-gray-600 uppercase font-black tracking-[0.4em]">Configuração Obrigatória de Unidade</p>
        </div>
    </div>
</body>
</html>
