<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Configuração de Módulos - Arena</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 font-sans antialiased">
    <div class="min-h-screen flex flex-col items-center justify-center p-6">

        <div class="mb-12 text-center">
            <h1 class="text-5xl font-black text-white uppercase italic tracking-tighter">
                Arena <span class="text-orange-500">System</span>
            </h1>
            <div class="h-1 w-20 bg-orange-600 mx-auto mt-2 rounded-full"></div>
        </div>

        <div class="max-w-5xl w-full text-center space-y-12">
            <div class="space-y-4">
                <h2 class="text-3xl font-bold text-white uppercase tracking-tight">
                    Configuração de <span class="text-gray-400">Módulos</span>
                </h2>
                <p class="text-gray-500 text-lg">Olá Adriano / Marcos, selecione qual sistema será implantado nesta unidade.</p>
            </div>

            <form action="{{ route('modules.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @csrf

                <button name="module" value="1" class="group bg-gray-900/50 p-10 rounded-[2.5rem] border border-gray-800 hover:border-blue-500 transition-all text-center space-y-6 shadow-2xl hover:bg-gray-800">
                    <div class="text-7xl group-hover:scale-110 transition-transform duration-300">🎾</div>
                    <div>
                        <h3 class="text-2xl font-black text-white uppercase italic">Arena Booking</h3>
                        <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest leading-relaxed">
                            Gestão de Quadras,<br>Horários e Reservas
                        </p>
                    </div>
                    <div class="pt-4">
                        <span class="text-[10px] bg-blue-500/10 text-blue-500 border border-blue-500/20 px-4 py-1.5 rounded-full uppercase font-black group-hover:bg-blue-500 group-hover:text-white transition-colors">Selecionar</span>
                    </div>
                </button>

                <button name="module" value="2" class="group bg-gray-900/50 p-10 rounded-[2.5rem] border border-gray-800 hover:border-orange-500 transition-all text-center space-y-6 shadow-2xl hover:bg-gray-800">
                    <div class="text-7xl group-hover:scale-110 transition-transform duration-300">🍺</div>
                    <div>
                        <h3 class="text-2xl font-black text-white uppercase italic">Bar System</h3>
                        <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest leading-relaxed">
                            PDV, Estoque e<br>Vendas de Balcão
                        </p>
                    </div>
                    <div class="pt-4">
                        <span class="text-[10px] bg-orange-500/10 text-orange-500 border border-orange-500/20 px-4 py-1.5 rounded-full uppercase font-black group-hover:bg-orange-500 group-hover:text-white transition-colors">Selecionar</span>
                    </div>
                </button>

                <button name="module" value="3" class="group bg-gray-900/50 p-10 rounded-[2.5rem] border-2 border-dashed border-gray-800 hover:border-green-500 transition-all text-center space-y-6 shadow-2xl relative overflow-hidden hover:bg-gray-800">
                    <div class="absolute top-0 right-0 bg-green-500 text-black text-[9px] font-black px-6 py-1 uppercase tracking-tighter -rotate-45 translate-x-4 translate-y-3">Completo</div>
                    <div class="text-7xl group-hover:scale-110 transition-transform duration-300">🚀</div>
                    <div>
                        <h3 class="text-2xl font-black text-white uppercase italic">Combo Full</h3>
                        <p class="text-xs text-gray-500 mt-2 font-bold uppercase tracking-widest leading-relaxed">
                            Arena + Bar<br>Ecossistema Integrado
                        </p>
                    </div>
                    <div class="pt-4">
                        <span class="text-[10px] bg-green-500/10 text-green-500 border border-green-500/20 px-4 py-1.5 rounded-full uppercase font-black group-hover:bg-green-500 group-hover:text-white transition-colors">Ativar Tudo</span>
                    </div>
                </button>
            </form>

            <div class="pt-12">
                <p class="text-[10px] text-gray-600 uppercase font-black tracking-[0.4em]">Configuração Mestra de Unidade</p>
            </div>
        </div>
    </div>
</body>
</html>
