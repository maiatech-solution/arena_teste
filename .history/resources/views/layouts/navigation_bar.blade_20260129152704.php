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
                            <span
                                class="text-[10px] font-black text-orange-500 uppercase tracking-widest leading-tight mt-1">
                                Gestão Online
                            </span>
                        </div>
                    </a>
                </div>

                @if (!request()->routeIs('bar.dashboard'))
                    <div class="hidden md:flex items-center space-x-2 sm:ms-10">
                        @php
                            $links = [
                                'bar.pdv' => 'PDV',
                                'bar.products.index' => 'Estoque',
                                'bar.tables.index' => 'Mesas',
                                'bar.cash.index' => 'Caixa',
                                'bar.users.index' => 'Usuários', // ✅ Adicionado aqui
                            ];
                        @endphp

                        @foreach ($links as $route => $label)
                            @php $active = request()->routeIs($route); @endphp
                            <a href="{{ route($route) }}"
                                class="px-4 py-2 text-[11px] font-black uppercase tracking-widest transition-all duration-300
                               {{ $active
                                   ? 'text-white bg-orange-600 rounded-xl shadow-[0_0_15px_rgba(234,88,12,0.6)] border-b-2 border-white'
                                   : 'text-gray-400 hover:text-orange-500 hover:bg-gray-800 rounded-xl' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex items-center space-x-4">
                <div class="hidden sm:flex sm:items-center sm:ms-6">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="flex items-center gap-3 px-4 py-2 bg-gray-800/50 border border-gray-700 rounded-xl hover:bg-gray-800 hover:border-orange-500/50 transition-all duration-200 shadow-lg focus:outline-none">
                                <div class="flex flex-col text-right">
                                    <span
                                        class="text-[10px] font-black uppercase text-orange-500 leading-none mb-1">Acesso
                                        Gestor</span>
                                    <span
                                        class="text-sm font-bold text-white leading-tight">{{ Auth::user()->name }}</span>
                                </div>
                                <svg class="h-4 w-4 text-orange-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if (isset($site_info) && $site_info->modules_active == 3)
                                <div
                                    class="block px-4 py-2 text-[10px] text-gray-400 font-black uppercase tracking-widest border-b border-gray-100 italic">
                                    Mudar Ambiente
                                </div>

                                {{-- Lógica para alternar o botão de switch --}}
                                @if (request()->is('bar*'))
                                    <x-dropdown-link :href="route('modules.switch', 'arena')" class="bg-indigo-50 text-indigo-700 font-bold">
                                        🏟️ Painel Arena
                                    </x-dropdown-link>
                                @else
                                    <x-dropdown-link :href="route('modules.switch', 'bar')" class="bg-orange-50 text-orange-700 font-bold">
                                        🍺 Painel Bar
                                    </x-dropdown-link>
                                @endif
                                <div class="border-t border-gray-100"></div>
                            @endif

                            <x-dropdown-link :href="route('profile.edit')">Meu Perfil</x-dropdown-link>

                            @if (Auth::user()->has_admin_access)
                                @if (request()->is('bar*'))
                                    {{-- 🍺 Links Dinâmicos para o BAR --}}
                                    <x-dropdown-link :href="route('bar.company.edit')">Dados do estabelecimento</x-dropdown-link>
                                    <x-dropdown-link :href="route('bar.users.index')">Gerenciar Equipe</x-dropdown-link>
                                @else
                                    {{-- 🏟️ Links Originais para a ARENA --}}
                                    <x-dropdown-link :href="route('admin.company.edit')">Dados do estabelecimento</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.users.index')">Gerenciar Usuários</x-dropdown-link>
                                @endif
                            @endif

                            @if (Auth::user()->is_admin)
                                <x-dropdown-link :href="route('admin.plans')"
                                    class="text-orange-600 font-black border-t border-gray-100 bg-orange-50">
                                    ⚙️ Gerenciar Plano
                                </x-dropdown-link>
                            @endif

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="text-red-600 font-bold border-t border-gray-100">
                                    Sair
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                <div class="-me-2 flex items-center sm:hidden">
                    <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-md text-orange-500 hover:text-orange-400 hover:bg-gray-800 focus:outline-none transition duration-150 ease-in-out">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden border-t border-gray-800 bg-gray-900">
        <div class="pt-2 pb-3 space-y-1">
            @if (!request()->routeIs('bar.dashboard'))
                <x-responsive-nav-link :href="route('bar.pdv')" :active="request()->routeIs('bar.pdv')"
                    class="text-gray-300">PDV</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('bar.products.index')" :active="request()->routeIs('bar.products.*')"
                    class="text-gray-300">Estoque</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('bar.tables.index')" :active="request()->routeIs('bar.tables.*')"
                    class="text-gray-300">Mesas</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('bar.cash.index')" :active="request()->routeIs('bar.cash.*')"
                    class="text-gray-300">Caixa</x-responsive-nav-link>

                {{-- ✅ Link Corrigido para a View Dark de Usuários --}}
                <x-responsive-nav-link :href="route('bar.users.index')" :active="request()->routeIs('bar.users.*')"
                    class="text-gray-300">Equipe</x-responsive-nav-link>

                {{-- ✅ Adicionado: Configurações da Empresa no Bar --}}
                <x-responsive-nav-link :href="route('bar.company.edit')" :active="request()->routeIs('bar.company.*')"
                    class="text-gray-300">Configurações</x-responsive-nav-link>
            @endif

            @if (isset($site_info) && $site_info->modules_active == 3)
                <x-responsive-nav-link :href="route('modules.switch', 'arena')"
                    class="bg-indigo-900/40 text-indigo-400 font-bold border-l-4 border-indigo-500">
                    🏟️ Mudar para Arena
                </x-responsive-nav-link>
            @endif

            <hr class="border-gray-800 my-2">
            <x-responsive-nav-link :href="route('profile.edit')" class="text-gray-300">Meu Perfil</x-responsive-nav-link>

            @if (Auth::user()->has_admin_access)
                @if (request()->is('bar*'))
                    {{-- Links para o ambiente BAR --}}
                    <x-responsive-nav-link :href="route('bar.company.edit')" :active="request()->routeIs('bar.company.*')" class="text-gray-300">
                        Dados do estabelecimento
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('bar.users.index')" :active="request()->routeIs('bar.users.*')" class="text-gray-300">
                        Gerenciar Equipe
                    </x-responsive-nav-link>
                @else
                    {{-- Links originais para o ambiente ARENA --}}
                    <x-responsive-nav-link :href="route('admin.company.edit')" :active="request()->routeIs('admin.company.*')" class="text-gray-300">
                        Dados do estabelecimento
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')" class="text-gray-300">
                        Gerenciar Usuários
                    </x-responsive-nav-link>
                @endif
            @endif

            @if (Auth::user()->is_admin)
                <x-responsive-nav-link :href="route('admin.plans')" class="text-orange-500 font-black">⚙️ Gerenciar
                    Plano</x-responsive-nav-link>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-responsive-nav-link :href="route('logout')"
                    onclick="event.preventDefault(); this.closest('form').submit();" class="text-red-500">
                    Sair
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
