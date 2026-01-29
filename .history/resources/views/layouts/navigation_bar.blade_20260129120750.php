<nav x-data="{ open: false }" class="bg-gray-900 border-b border-orange-600/30 shadow-2xl">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-20">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('bar.dashboard') }}" class="flex items-center gap-3 group">
                        <span class="text-3xl group-hover:scale-110 transition-transform">🍺</span>
                        <div class="flex flex-col border-l border-gray-700 pl-3">
                            <span class="text-xl font-black text-white leading-none uppercase tracking-tighter italic">
                                {{ $site_info->nome_fantasia ?? 'Bar System' }}
                            </span>
                            <span class="text-[10px] font-black text-orange-500 uppercase tracking-widest leading-tight mt-1">
                                MaiaTech Solution
                            </span>
                        </div>
                    </a>
                </div>

                @if(!request()->routeIs('bar.dashboard'))
                    <div class="hidden space-x-1 sm:-my-px sm:ms-10 sm:flex items-center">
                        <x-nav-link :href="route('bar.dashboard')" :active="request()->routeIs('bar.dashboard')"
                            class="text-gray-400 hover:text-white px-3 py-2 text-xs font-bold uppercase tracking-widest transition-colors">
                            {{ __('Início') }}
                        </x-nav-link>

                        <x-nav-link :href="route('bar.pdv')" :active="request()->routeIs('bar.pdv')"
                            class="text-gray-400 hover:text-white px-3 py-2 text-xs font-bold uppercase tracking-widest transition-colors">
                            {{ __('PDV') }}
                        </x-nav-link>

                        <x-nav-link :href="route('bar.products.index')" :active="request()->routeIs('bar.products.*')"
                            class="text-gray-400 hover:text-white px-3 py-2 text-xs font-bold uppercase tracking-widest transition-colors">
                            {{ __('Estoque') }}
                        </x-nav-link>

                        <x-nav-link :href="route('bar.tables.index')" :active="request()->routeIs('bar.tables.*')"
                            class="text-gray-400 hover:text-white px-3 py-2 text-xs font-bold uppercase tracking-widest transition-colors">
                            {{ __('Mesas') }}
                        </x-nav-link>

                        <x-nav-link :href="route('bar.cash.index')" :active="request()->routeIs('bar.cash.*')"
                            class="text-gray-400 hover:text-white px-3 py-2 text-xs font-bold uppercase tracking-widest transition-colors">
                            {{ __('Caixa') }}
                        </x-nav-link>
                    </div>
                @endif
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-4 py-2 border border-gray-700 text-sm leading-4 font-bold rounded-xl text-gray-200 bg-gray-800/50 hover:bg-gray-800 hover:text-white hover:border-orange-500/50 focus:outline-none transition-all duration-200">
                            <div class="flex flex-col text-right me-3">
                                <div class="text-[9px] font-black uppercase text-orange-500 leading-none mb-1">
                                    {{ $site_info->nome_fantasia ?? 'Estabelecimento' }}
                                </div>
                                <div class="text-sm font-bold leading-tight">
                                    {{ Auth::user()->name }}
                                </div>
                            </div>
                            <svg class="fill-current h-4 w-4 text-orange-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- 🔄 VOLTAR PARA ARENA (Combo 3) --}}
                        @if($site_info->modules_active == 3)
                            <div class="block px-4 py-2 text-[10px] text-gray-500 font-black uppercase tracking-widest border-b border-gray-100">
                                Mudar de Ambiente
                            </div>
                            <x-dropdown-link :href="route('modules.switch', 'arena')" class="bg-indigo-50 text-indigo-700 font-bold italic">
                                🏟️ Painel Arena
                            </x-dropdown-link>
                            <div class="border-t border-gray-100"></div>
                        @endif

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Meu Perfil') }}
                        </x-dropdown-link>

                        @if (Auth::user()->has_admin_access)
                            <x-dropdown-link :href="route('admin.company.edit')">
                                {{ __('Dados do Bar') }}
                            </x-dropdown-link>

                            {{-- 🛡️ EXCLUSIVO ADMIN (Maia e Marcos) --}}
                            @if(Auth::user()->is_admin)
                                <x-dropdown-link :href="route('modules.selection')" class="text-orange-600 font-black border-t border-gray-100 bg-orange-50">
                                    ⚙️ Gerenciar Plano
                                </x-dropdown-link>
                            @endif
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-600 font-bold border-t border-gray-100">
                                {{ __('Sair do Sistema') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-800 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</nav>
