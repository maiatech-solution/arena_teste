<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuração Inicial - MaiaTech</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-gray-200 font-sans antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-gray-900 via-gray-950 to-black">

        <div class="mb-10 text-center space-y-2">
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                MaiaTech <span class="text-orange-500">Solution</span>
            </h1>
            <div class="h-1 w-24 bg-orange-600 mx-auto rounded-full shadow-[0_0_15px_rgba(234,88,12,0.5)]"></div>
        </div>

        <div class="max-w-4xl w-full">
            <div class="bg-gray-900/40 backdrop-blur-xl border border-gray-800 p-8 md:p-12 rounded-[3rem] shadow-2xl">

                <div class="mb-10 text-center space-y-3">
                    <h2 class="text-3xl font-bold text-white tracking-tight">Bem-vindo ao seu <span class="text-orange-500 text-italic">Novo Sistema</span></h2>
                    <p class="text-gray-400">Antes de começarmos, vamos identificar a sua unidade.</p>
                </div>

                <form action="{{ route('onboarding.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">Nome Fantasia da Unidade</label>
                            <input type="text" name="nome_fantasia" required class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-4 px-6 transition-all" placeholder="Ex: Arena Soccer Beach">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">CNPJ</label>
                            <input type="text" name="cnpj" class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-4 px-6 transition-all" placeholder="00.000.000/0000-00">
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase tracking-[0.2em] ml-2">WhatsApp Suporte</label>
                            <input type="text" name="whatsapp_suporte" class="w-full bg-gray-950 border-gray-800 rounded-2xl text-white focus:border-orange-500 focus:ring-orange-500 py-4 px-6 transition-all" placeholder="91988887777">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-orange-600 hover:bg-orange-500 text-white font-black py-5 rounded-[2rem] uppercase tracking-[0.2em] transition-all shadow-xl shadow-orange-900/20 mt-4">
                        Salvar e Continuar para Módulos
                    </button>
                </form>
            </div>
            <p class="text-center mt-8 text-[10px] text-gray-600 uppercase font-black tracking-[0.5em] opacity-50">Configuração de Primeiro Acesso</p>
        </div>
    </div>
</body>
</html>
