<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Escolha o Sistema - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-white antialiased flex items-center justify-center min-h-screen selection:bg-orange-500">

    <div class="max-w-4xl w-full px-6 text-center">
        <header class="mb-12">
            <h1 class="text-3xl font-black uppercase tracking-tighter">Olá, <span class="text-orange-500">{{ Auth::user()->name }}</span></h1>
            <p class="text-gray-500 mt-2 font-bold uppercase tracking-widest text-[10px]">Qual ambiente você deseja gerenciar agora?</p>
        </header>

        <div class="grid md:grid-cols-2 gap-8">

            <a href="{{ route('modules.switch', 'arena') }}"
               class="group bg-gray-900 border-2 border-transparent hover:border-indigo-600 rounded-3xl p-10 transition-all duration-300 hover:-translate-y-2 shadow-2xl">
                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 bg-indigo-600 rounded-2xl flex items-center justify-center text-4xl mb-6 shadow-lg shadow-indigo-600/20 group-hover:scale-110 transition-transform">🏟️</div>
                    <h2 class="text-2xl font-black uppercase tracking-tight">Arena</h2>
                    <p class="text-gray-400 mt-2 text-sm">Gestão de Quadras e Reservas</p>
                    <div class="mt-8 px-6 py-2 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all">
                        Acessar Painel
                    </div>
                </div>
            </a>

            <a href="{{ route('modules.switch', 'bar') }}"
               class="group bg-gray-900 border-2 border-transparent hover:border-orange-600 rounded-3xl p-10 transition-all duration-300 hover:-translate-y-2 shadow-2xl">
                <div class="flex flex-col items-center">
                    <div class="w-20 h-20 bg-orange-600 rounded-2xl flex items-center justify-center text-4xl mb-6 shadow-lg shadow-orange-600/20 group-hover:scale-110 transition-transform">🍺</div>
                    <h2 class="text-2xl font-black uppercase tracking-tight">Bar & PDV</h2>
                    <p class="text-gray-400 mt-2 text-sm">Gestão de Vendas e Estoque</p>
                    <div class="mt-8 px-6 py-2 bg-orange-600 text-white rounded-xl text-xs font-black uppercase tracking-widest opacity-0 group-hover:opacity-100 transition-all">
                        Acessar Painel
                    </div>
                </div>
            </a>

        </div>

        <footer class="mt-16">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-600 hover:text-red-500 font-bold uppercase text-[10px] tracking-widest transition-colors flex items-center gap-2 mx-auto">
                    Sair do Sistema
                </button>
            </form>
        </footer>
    </div>

</body>
</html>
