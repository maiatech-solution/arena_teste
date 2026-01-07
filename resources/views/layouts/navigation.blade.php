<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ Auth::check() && Auth::user()->is_gestor ? route('dashboard') : route('home') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

                <div class="hidden space-x-4 sm:-my-px sm:ms-10 sm:flex items-center">

                    @if (Auth::check() && Auth::user()->is_gestor)
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="px-3 py-2">
                            {{ __('Home') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.reservas.index')"
                                    :active="request()->routeIs('admin.reservas.*')"
                                    class="px-3 py-2 text-sm font-semibold">
                            {{ __('Reservas') }}
                        </x-nav-link>

                        {{-- 🏟️ GERENCIAR QUADRAS: Apenas Cadastro e Lista --}}
                        <x-nav-link :href="route('admin.arenas.index')"
                                    :active="request()->routeIs('admin.arenas.*')"
                                    class="px-3 py-2 text-sm font-semibold">
                            {{ __('Gerenciar Quadras') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.users.index')"
                                    :active="request()->routeIs('admin.users.*')"
                                    class="px-3 py-2 text-sm font-semibold">
                            {{ __('Usuários') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.payment.index')"
                                    :active="request()->routeIs('admin.payment.*')"
                                    class="px-3 py-2 text-sm font-semibold">
                            {{ __('Caixa') }}
                        </x-nav-link>

                        <x-nav-link :href="route('admin.financeiro.dashboard')"
                                    :active="request()->routeIs('admin.financeiro.*')"
                                    class="px-3 py-2 text-sm font-semibold">
                            {{ __('Relatórios') }}
                        </x-nav-link>
                    @endif

                    @if (Auth::check() && !Auth::user()->is_gestor)
                        <x-nav-link :href="route('customer.reservations.history')" :active="request()->routeIs('customer.reservations.history')" class="px-3 py-2 text-sm font-bold text-indigo-700">
                            {{ __('Minhas Reservas') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @if (Auth::check())
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @else
                    <a href="{{ route('customer.login') }}" class="px-3 py-2 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        {{ __('Login Cliente') }}
                    </a>
                @endif
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if (Auth::check() && Auth::user()->is_gestor)
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Home') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.arenas.index')" :active="request()->routeIs('admin.arenas.*')">
                    {{ __('Gerenciar Quadras') }}
                </x-responsive-nav-link>
            @endif
        </div>
    </div>
</nav>