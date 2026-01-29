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

        {{-- NAVIGATION --}}
        <nav class="bg-gray-900 border-b border-orange-600/30 shadow-2xl">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20">

                    <div class="flex items-center gap-8">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                            <span class="text-3xl group-hover:scale-110 transition-transform">🍺</span>
                            <span class="text-2xl font-black tracking-tighter text-orange-500 uppercase italic">
                                Bar <span class="text-white">System</span>
                            </span>
                        </a>

                        @if(Route::currentRouteName() !== 'dashboard')
                            <div class="hidden md:flex items-center space-x-2">
                                @php
                                    $navLinks = [
                                        'bar.pdv' => 'PDV',
                                        'bar.products.index' => 'Estoque',
                                        'bar.tables.index' => 'Mesas',
                                        'bar.cash.index' => 'Caixa',
                                        'bar.relatorios.index' => 'Relatórios'
                                    ];
                                @endphp

                                @foreach($navLinks as $route => $label)
                                    @php $isActive = request()->routeIs($route); @endphp
                                    <a href="{{ route($route) }}"
                                       class="px-4 py-2 text-xs font-black uppercase tracking-widest transition-all duration-300
                                       {{ $isActive
                                          ? 'text-white bg-orange-600 rounded-xl shadow-[0_0_15px_rgba(234,88,12,0.5)] border-b-2 border-white'
                                          : 'text-gray-400 hover:text-orange-500 hover:bg-gray-800 rounded-xl' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center ms-6">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="flex items-center gap-3 px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-xl hover:bg-gray-800 hover:border-orange-500/50 transition-all duration-200 shadow-lg">
                                    <div class="flex flex-col text-right">
                                        <span class="text-[10px] font-black uppercase text-orange-500 leading-none mb-1">Acesso Gestor</span>
                                        <span class="text-sm font-bold text-white">{{ Auth::user()->name }}</span>
                                    </div>
                                    <svg class="h-4 w-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                {{-- 🔄 MUDAR PARA ARENA: Aparece se for Combo (3) --}}
                                @if($site_info->modules_active == 3)
                                    <div class="block px-4 py-2 text-[10px] text-gray-400 font-black uppercase tracking-widest border-b border-gray-100 italic">Mudar Ambiente</div>
                                    <x-dropdown-link :href="route('modules.switch', 'arena')" class="bg-indigo-50 text-indigo-700 font-bold">
                                        🏟️ Painel Arena
                                    </x-dropdown-link>
                                    <div class="border-t border-gray-200"></div>
                                @endif

                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Meu Perfil') }}
                                </x-dropdown-link>

                                {{-- 🛡️ ADMIN MASTER (Maia/Marcos) --}}
                                @if(Auth::user()->is_admin)
                                    <x-dropdown-link :href="route('modules.selection')" class="text-orange-600 font-black border-t border-gray-100 bg-orange-50">
                                        ⚙️ Gerenciar Módulos
                                    </x-dropdown-link>
                                @endif

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600 font-bold border-t border-gray-100">
                                        {{ __('Sair') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>

                </div>
            </div>
        </nav>

        {{-- CONTEÚDO --}}
        <main class="flex-grow py-12">
            {{ $slot }}
        </main>

    </div>
</body>
</html>
