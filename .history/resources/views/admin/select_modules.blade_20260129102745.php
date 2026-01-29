<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Configuração de Módulos - MaiaTech</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 font-sans antialiased text-gray-200">
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-gray-900 via-gray-950 to-black">

        <div class="mb-12 text-center space-y-2">
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">
                MaiaTech <span class="text-orange-500 italic">Solution</span>
            </h1>
            <div class="h-1 w-24 bg-gradient-to-r from-transparent via-orange-600 to-transparent mx-auto rounded-full"></div>
        </div>

        <div class="max-w-5xl w-full text-center space-y-12">
            <div class="space-y-4">
                <h2 class="text-2xl md:text-3xl font-bold text-white tracking-tight leading-tight">
                    Bem vindo ao Sistema de gestão completo da <span class="text-orange-500">MaiaTech Solution</span>.
                </h2>
                <p class="text-gray-400 text-lg">
                    Gestor, escolha abaixo qual módulo o cliente optou!
                </p>
            </div>

            <form action="{{ route('modules.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @csrf

                <button name="module" value="1" class="group bg-gray-900/40 p-10 rounded-[2.5rem] border border-gray-800 hover:border-blue-500 transition-all duration-500 text-center space-y-6 shadow-2xl hover:bg-gray-800/60 hover:shadow-blue-500/10">
                    <div class="text-7xl group-hover:scale-110 transition-transform duration-300">🎾</div>
                    <div>
                        <h3 class="text-2xl font-black text-white uppercase italic">Arena Booking</h3>
                        <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest leading-relaxed px-4">
                            Gestão de Quadras,<br>Horários e Reservas
                        </p>
                    </div>
                    <div class="pt-4">
                        <span class="text-[10px] bg-blue-500/10 text-blue-500 border border-blue-500/20 px-5 py-2 rounded-full uppercase font-black group-hover:bg-blue-600 group-hover:text-white transition-all">Ativar Arena</span>
                    </div>
                </button>

                <button name="module" value="2" class="group bg-gray-900/40 p-10 rounded-[2.5rem] border border-gray-800 hover:border-orange-500 transition-all duration-500 text-center space-y-6 shadow-2xl hover:bg-gray-800/60 hover:shadow-orange-500/10">
                    <div class="text-7xl group-hover:scale-110 transition-transform duration-300">💰</div>
                    <div>
                        <h3 class="text-2xl font-black text-white uppercase italic">PDV System</h3>
                        <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest leading-relaxed px-4">
                            PDV, Estoque e<br>Vendas de Balcão
                        </p>
                    </div>
                    <div class="pt-4">
                        <span class="text-[10px] bg-orange-500/10 text-orange-500 border border-orange-500/20 px-5 py-2 rounded-full uppercase font-black group-hover:bg-orange-600 group-hover:text-white transition-all">Ativar PDV</span>
                    </div>
                </button>

                <button name="module" value="3" class="group bg-gray-900/40 p-10 rounded-[2.5rem] border-2 border-dashed border-gray-800 hover:border-green-500 transition-all duration-500 text-center space-y-6 shadow-2xl relative overflow-hidden hover:bg-gray-800/60 hover:shadow-green-500/10">
                    <div class="absolute top-0 right-0 bg-green-500 text-black text-[9px] font-black px-6 py-1 uppercase tracking-tighter -rotate-45 translate-x-4 translate-y-3">Completo</div>
                    <div class="text-7xl group-hover:scale-110 transition-transform duration-300">🚀</div>
                    <div>
                        <h3 class="text-2xl font-black text-white uppercase italic">Combo Full</h3>
                        <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest leading-relaxed px-4">
                            Arena + PDV<br>Ecossistema Integrado
                        </p>
                    </div>
                    <div class="pt-4">
                        <span class="text-[10px] bg-green-500/10 text-green-500 border border-green-500/20 px-5 py-2 rounded-full uppercase font-black group-hover:bg-green-600 group-hover:text-white transition-all">Ativar Combo</span>
                    </div>
                </button>
            </form>

            <div class="pt-12">
                <p class="text-[10px] text-gray-600 uppercase font-black tracking-[0.5em] opacity-50">MaiaTech Solution &copy; 2026</p>
            </div>
        </div>
    </div>
</body>
</html>
