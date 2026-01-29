<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Selecionar Ambiente - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-white antialiased flex items-center justify-center min-h-screen">

    <div class="max-w-4xl w-full px-6 text-center">
        <div class="mb-12">
            <h1 class="text-4xl font-black uppercase tracking-tighter italic">
                MaiaTech <span class="text-orange-500">Solutions</span>
            </h1>
            <p class="text-gray-500 mt-2 font-bold uppercase tracking-widest text-xs">Central de Acesso Inteligente</p>
        </div>

        <div class="grid md:grid-cols-2 gap-8">

            <a href="{{ route('modules.switch', 'arena') }}"
               class="group relative overflow-hidden bg-gray-900 border-2 border-transparent hover:border-indigo-600 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-2 shadow-2xl">
                <div class="absolute -right-4 -top-4 text-9xl opacity-10 group-hover:opacity-20 transition-opacity">🏟️</div>
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-20 h-20 bg-indigo-600 rounded-2xl flex items-center justify-center text-4xl mb-6 shadow-lg shadow-indigo-600/20">🏟️</div>
                    <h2 class="text-2xl font-black uppercase tracking-tight">Arena Esportiva</h2>
                    <p class="text-gray-400 mt-2 text-sm leading-relaxed">Gestão de quadras, reservas,<br>horários e mensalistas.</p>
                    <div class="mt-8 px-8 py-3 bg-indigo-600/10 text-indigo-400 rounded-xl text-xs font-black uppercase tracking-widest group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm">
                        Acessar Arena
                    </div>
                </div>
            </a>

            <a href="{{ route('modules.switch', 'bar') }}"
               class="group relative overflow-hidden bg-gray-900 border-2 border-transparent hover:border-orange-600 rounded-3xl p-8 transition-all duration-300 hover:-translate-y-2 shadow-2xl">
                <div class="absolute -right-4 -top-4 text-9xl opacity-10 group-hover:opacity-20 transition-opacity">🍺</div>
                <div class="relative z-10 flex flex-col items-center">
                    <div class="w-20 h-20 bg-orange-600 rounded-2xl flex items-center justify-center text-4xl mb-6 shadow-lg shadow-orange-600/20">🍺</div>
                    <h2 class="text-2xl font-black uppercase tracking-tight text-white">Bar & Bistrô</h2>
                    <p class="text-gray-400 mt-2 text-sm leading-relaxed">PDV, controle de estoque,<br>mesas e comandas.</p>
                    <div class="mt-8 px-8 py-3 bg-orange-600/10 text-orange-500 rounded-xl text-xs font-black uppercase tracking-widest group-hover:bg-orange-600 group-hover:text-white transition-all shadow-sm">
                        Acessar Bar
                    </div>
                </div>
            </a>

        </div>

        <div class="mt-16">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-gray-600 hover:text-red-500 font-bold uppercase text-xs tracking-widest transition-colors flex items-center gap-2 mx-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    Sair do Sistema
                </button>
            </form>
        </div>
    </div>

</body>
</html>
