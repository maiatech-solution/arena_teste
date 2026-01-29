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

                    <div class="flex items-center gap-8">
                        <a href="{{ route('bar.dashboard') }}" class="flex items-center gap-3 group">
                            <span class="text-3xl group-hover:scale-110 transition-transform">🍺</span>
                            <span class="text-2xl font-black tracking-tighter text-orange-500 uppercase italic">Bar <span class="text-white">System</span></span>
                        </a>

                        <div class="hidden md:flex items-center space-x-1">
                            <x-nav-link :href="route('bar.pdv')" :active="request()->routeIs('bar.pdv')" class="text-gray-400 hover:text-orange-500 px-3 py-2 text-xs font-bold uppercase">PDV</x-nav-link>
                            <x-nav-link :href="route('bar.products.index')" :active="request()->routeIs('bar.products.*')" class="text-gray-400 hover:text-orange-500 px-3 py-2 text-xs font-bold uppercase">Estoque</x-nav-link>
                            <x-nav-link :href="route('bar.tables.index')" :active="request()->routeIs('bar.tables.*')" class="text-gray-400 hover:text-orange-500 px-3 py-2 text-xs font-bold uppercase">Mesas</x-nav-link>
                            <x-nav-link :href="route('bar.cash.index')" :active="request()->routeIs('bar.cash.*')" class="text-gray-400 hover:text-orange-500 px-3 py-2 text-xs font-bold uppercase">Caixa</x-nav-link>
                            {{-- Caso tenha rota de relatórios específica --}}
                            <x-nav-link href="#" class="text-gray-400 hover:text-orange-500 px-3 py-2 text-xs font-bold uppercase">Relatórios</x-nav-link>
                        </div>
                    </div>

                    <div class="flex items-center ms-6">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-3 px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-xl hover:bg-gray-800 transition">
                                    <div class="flex flex-col text-right">
                                        <span class="text-[10px] font-black uppercase text-orange-500 leading-none">Gestor</span>
                                        <span class="text-sm font-bold text-white">{{ Auth::user()->name }}</span>
                                    </div>
                                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                {{-- 🔄 LÓGICA DE COMBO: Só mostra se modules_active == 3 --}}
                                @if($site_info->modules_active == 3)
                                    <div class="block px-4 py-2 text-[10px] text-gray-400 font-black uppercase tracking-widest border-b border-gray-100">
                                        Mudar para Arena
                                    </div>
                                    <x-dropdown-link :href="route('modules.switch', 'arena')" class="bg-indigo-50 text-indigo-700 font-bold">
                                        🏟️ Painel Arena
                                    </x-dropdown-link>
                                    <div class="border-t border-gray-200"></div>
                                @endif

                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Meu Perfil') }}
                                </x-dropdown-link>

                                {{-- 🛡️ LÓGICA DE ADMIN (Maia/Marcos) --}}
                                @if(Auth::user()->is_admin)
                                    <x-dropdown-link :href="route('modules.selection')" class="text-orange-600 font-black border-t border-gray-100 bg-gray-50">
                                        ⚙️ Gerenciar Módulos
                                    </x-dropdown-link>
                                @endif

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('Sair') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
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
