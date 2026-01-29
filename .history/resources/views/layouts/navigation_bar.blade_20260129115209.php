<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('bar.dashboard') }}" class="flex items-center gap-3">
                        <x-application-logo class="block h-9 w-auto fill-current text-orange-600" />
                        <div class="flex flex-col border-l border-gray-300 pl-3">
                            <span class="text-lg font-black text-gray-900 leading-none uppercase tracking-tighter">
                                {{ $site_info->nome_fantasia ?? 'Bar/PDV' }}
                            </span>
                            <span class="text-[10px] font-bold text-orange-600 uppercase tracking-widest leading-tight">
                                PDV System
                            </span>
                        </div>
                    </a>
                </div>

                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">
                    @if (Auth::check() && Auth::user()->has_admin_access)
                        <x-nav-link :href="route('bar.dashboard')" :active="request()->routeIs('bar.dashboard')" class="px-3 py-2">
                            {{ __('PDV') }}
                        </x-nav-link>

                        <x-nav-link :href="route('bar.estoque.index')" :active="request()->routeIs('bar.estoque.*')" class="px-3 py-2 text-sm font-semibold">
                            {{ __('Estoque') }}
                        </x-nav-link>

                        <x-nav-link :href="route('bar.mesas.index')" :active="request()->routeIs('bar.mesas.*')" class="px-3 py-2 text-sm font-semibold">
                            {{ __('Mesas') }}
                        </x-nav-link>

                        <x-nav-link :href="route('bar.caixa.index')" :active="request()->routeIs('bar.caixa.*')" class="px-3 py-2 text-sm font-semibold">
                            {{ __('Caixa') }}
                        </x-nav-link>

                        <x-nav-link :href="route('bar.relatorios.index')" :active="request()->routeIs('bar.relatorios.*')" class="px-3 py-2 text-sm font-semibold">
                            {{ __('Relatórios') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex flex-col text-right me-2">
                                <div class="text-[10px] font-black uppercase text-orange-500 leading-none">
                                    {{ $site_info->nome_fantasia ?? 'Estabelecimento' }}
                                </div>
                                <div class="text-sm font-bold text-gray-700 leading-tight">
                                    {{ Auth::user()->name }}
                                </div>
                            </div>
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- 🔄 ALTERNAR PARA ARENA (Aparece se for Combo 3) --}}
                        @if($site_info->modules_active == 3)
                            <div class="block px-4 py-2 text-[10px] text-gray-400 font-black uppercase border-b border-gray-100">
                                Mudar de Ambiente
                            </div>
                            <x-dropdown-link :href="route('modules.switch', 'arena')" class="bg-indigo-50 text-indigo-700 font-bold">
                                🏟️ Ir para Arena
                            </x-dropdown-link>
                            <div class="border-t border-gray-200"></div>
                        @endif

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Meu Perfil') }}
                        </x-dropdown-link>

                        @if (Auth::user()->has_admin_access)
                            <x-dropdown-link :href="route('admin.company.edit')">
                                {{ __('Dados do Bar') }}
                            </x-dropdown-link>

                            {{-- 🛡️ EXCLUSIVO ADMIN (Maia e Marcos): Gerenciar Plano --}}
                            @if(Auth::user()->is_admin)
                                <x-dropdown-link :href="route('modules.selection')" class="text-orange-600 font-black border-t border-gray-100 bg-gray-50">
                                    ⚙️ Gerenciar Plano/Módulos
                                </x-dropdown-link>
                            @endif
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
