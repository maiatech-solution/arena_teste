<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bar Manager - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 text-gray-100 antialiased selection:bg-orange-500 selection:text-white">
    <div class="min-h-screen flex flex-col">
        <nav class="bg-gray-900 border-b border-orange-600/30 shadow-2xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">
                    <div class="flex items-center">
                        <a href="{{ route('bar.dashboard') }}" class="flex items-center gap-3 group">
                            <span class="text-3xl group-hover:scale-110 transition-transform">🍺</span>
                            <span class="text-2xl font-black tracking-tighter text-orange-500 uppercase">Bar System</span>
                        </a>
                    </div>
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-gray-800 hover:bg-orange-600 text-[10px] font-black rounded-lg transition duration-300 border border-gray-700 uppercase tracking-widest">
                            🏟️ Voltar para Arena
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <main class="flex-grow py-12">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
