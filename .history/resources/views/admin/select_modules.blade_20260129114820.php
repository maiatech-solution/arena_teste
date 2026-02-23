<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Escolha seu Módulo - MaiaTech</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 font-sans antialiased text-gray-200">
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-gray-900 via-gray-950 to-black">

        <div class="mb-12 text-center">
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                MaiaTech <span class="text-orange-500">Solution</span>
            </h1>
            <div class="h-1 w-24 bg-orange-600 mx-auto mt-2 rounded-full shadow-[0_0_15px_rgba(234,88,12,0.4)]"></div>
        </div>

        <div class="max-w-5xl w-full text-center space-y-12">
            <div class="space-y-4">
                <h2 class="text-2xl md:text-3xl font-bold text-white tracking-tight leading-tight">
                    Bem-vindo ao Sistema de gestão completo da <span class="text-orange-500">MaiaTech Solution</span>.
                </h2>
                <p class="text-gray-400 text-lg">
                    @if($company->modules_active > 0)
                        Gerenciamento de Plano: @if(Auth::user()->is_admin) Maia e Marcos, vocês podem alterar os módulos aqui! @else Faça o upgrade para liberar novos recursos! @endif
                    @else
                        Gestor, escolha abaixo qual módulo o cliente optou!
                    @endif
                </p>
            </div>

            <form action="{{ route('modules.store') }}" method="POST" class="flex flex-wrap justify-center gap-8">
                @csrf

                {{-- MÓDULO 1: ARENA --}}
                @if(Auth::user()->is_admin || $company->modules_active == 0 || $company->modules_active == 1)
                <button type="submit" name="module" value="1"
                    class="group w-full md:w-80 bg-gray-900/40 p-10 rounded-[2.5rem] border {{ $company->modules_active == 1 ? 'border-blue-500 shadow-[0_0_20px_rgba(59,130,246,0.3)]' : 'border-gray-800' }} hover:border-blue-500 transition-all duration-500 text-center space-y-6 shadow-2xl hover:bg-gray-800/60">
                    <div class="text-7xl group-hover:scale-110 transition-transform">🏟️</div>
                    <h3 class="text-2xl font-black text-white uppercase italic">Arena Booking</h3>
                    <div class="pt-4">
                        @if($company->modules_active == 1)
                            <span class="text-[10px] bg-blue-600 text-white px-5 py-2 rounded-full uppercase font-black">Plano Ativo</span>
                        @elseif($company->modules_active == 3)
                            <span class="text-[10px] bg-red-500/10 text-red-500 border border-red-500/20 px-5 py-2 rounded-full uppercase font-black group-hover:bg-red-600 group-hover:text-white transition-all">Desativar Bar (Downgrade)</span>
                        @else
                            <span class="text-[10px] bg-blue-500/10 text-blue-500 border border-blue-500/20 px-5 py-2 rounded-full uppercase font-black group-hover:bg-blue-600 group-hover:text-white transition-all">Ativar Arena</span>
                        @endif
                    </div>
                </button>
                @endif

                {{-- MÓDULO 2: PDV --}}
                @if(Auth::user()->is_admin || $company->modules_active == 0 || $company->modules_active == 2)
                <button type="submit" name="module" value="2"
                    class="group w-full md:w-80 bg-gray-900/40 p-10 rounded-[2.5rem] border {{ $company->modules_active == 2 ? 'border-orange-500 shadow-[0_0_20px_rgba(249,115,22,0.3)]' : 'border-gray-800' }} hover:border-orange-500 transition-all duration-500 text-center space-y-6 shadow-2xl hover:bg-gray-800/60">
                    <div class="text-7xl group-hover:scale-110 transition-transform">🍺</div>
                    <h3 class="text-2xl font-black text-white uppercase italic">PDV System</h3>
                    <div class="pt-4">
                        @if($company->modules_active == 2)
                            <span class="text-[10px] bg-orange-600 text-white px-5 py-2 rounded-full uppercase font-black">Plano Ativo</span>
                        @elseif($company->modules_active == 3)
                            <span class="text-[10px] bg-red-500/10 text-red-500 border border-red-500/20 px-5 py-2 rounded-full uppercase font-black group-hover:bg-red-600 group-hover:text-white transition-all">Desativar Arena (Downgrade)</span>
                        @else
                            <span class="text-[10px] bg-orange-500/10 text-orange-500 border border-orange-500/20 px-5 py-2 rounded-full uppercase font-black group-hover:bg-orange-600 group-hover:text-white transition-all">Ativar PDV</span>
                        @endif
                    </div>
                </button>
                @endif

                {{-- MÓDULO 3: COMBO FULL --}}
                <button type="submit" name="module" value="3"
                    class="group w-full md:w-80 bg-gray-900/40 p-10 rounded-[2.5rem] border-2 {{ $company->modules_active == 3 ? 'border-green-500 shadow-[0_0_25px_rgba(34,197,94,0.3)]' : 'border-dashed border-gray-800' }} hover:border-green-500 transition-all duration-500 text-center space-y-6 shadow-2xl relative overflow-hidden hover:bg-gray-800/60">
                    <div class="absolute top-0 right-0 bg-green-500 text-black text-[9px] font-black px-6 py-1 uppercase tracking-tighter -rotate-45 translate-x-4 translate-y-3">Completo</div>
                    <div class="text-7xl group-hover:scale-110 transition-transform">🚀</div>
                    <h3 class="text-2xl font-black text-white uppercase italic">Combo Full</h3>
                    <div class="pt-4">
                        @if($company->modules_active == 3)
                            <span class="text-[10px] bg-green-600 text-white px-5 py-2 rounded-full uppercase font-black">Plano Ativo</span>
                        @else
                            <span class="text-[10px] bg-green-500/10 text-green-500 border border-green-500/20 px-5 py-2 rounded-full uppercase font-black group-hover:bg-green-600 group-hover:text-white transition-all">Ativar Combo</span>
                        @endif
                    </div>
                </button>
            </form>

            @if($company->modules_active > 0)
                <div class="pt-6">
                    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-white text-sm transition-colors">
                        ← Voltar para o Sistema
                    </a>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
