<body class="bg-gray-50 dark:bg-gray-900 flex items-center justify-center min-h-screen p-4 sm:p-8">

    {{-- CABEÇALHO DE NAVEGAÇÃO --}}
    <header class="absolute top-0 w-full max-w-7xl px-4 py-4">
        @if (Route::has('login'))
            <nav class="flex items-center justify-end gap-4">
                @auth
                    {{-- Botão para o Painel Admin --}}
                    <a
                        href="{{ url('/dashboard') }}"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-md transition duration-150"
                    >
                        Painel Admin
                    </a>
                @else
                    {{-- Botão de Login --}}
                    <a
                        href="{{ route('login') }}"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition duration-150"
                    >
                        Login
                    </a>

                    @if (Route::has('register'))
                        {{-- Botão de Cadastro (Opcional, se precisar de registro de cliente) --}}
                        <a
                            href="{{ route('register') }}"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-500 hover:bg-green-600 rounded-lg shadow-md transition duration-150"
                        >
                            Cadastrar Cliente
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>

    {{-- CONTEÚDO PRINCIPAL: HERO SECTION --}}
    <div class="max-w-6xl mx-auto w-full">
        <main class="flex flex-col lg:flex-row bg-white dark:bg-gray-800 shadow-2xl rounded-2xl overflow-hidden transform hover:scale-[1.01] transition duration-500">

            {{-- LADO ESQUERDO: INFORMAÇÕES e TÍTULO --}}
            <div class="lg:w-3/5 p-8 sm:p-12 lg:p-16 flex flex-col justify-center">

                <p class="text-sm font-semibold text-indigo-500 uppercase tracking-wider mb-2">
                    Reserva Simples e Rápida
                </p>

                <h1 class="text-5xl sm:text-6xl font-extrabold mb-4 text-gray-900 dark:text-white leading-tight">
                    <span class="text-indigo-600">Arena</span> Booking Pro
                </h1>

                <p class="text-xl mb-8 text-gray-600 dark:text-gray-400">
                    O jeito mais fácil de reservar seu espaço esportivo favorito: futebol, vôlei, basquete e mais.
                </p>

                {{-- Benefícios --}}
                <ul class="space-y-3 mb-10 text-lg text-gray-700 dark:text-gray-300">
                    <li class="flex items-center">
                        <svg class="w-6 h-6 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Confirmação de Reserva em Segundos
                    </li>
                    <li class="flex items-center">
                        <svg class="w-6 h-6 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 6v-1m0 6v-1"></path></svg>
                        Preços Transparentes e sem Surpresas
                    </li>
                    <li class="flex items-center">
                        <svg class="w-6 h-6 mr-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-4 18h4M3 10h18M3 14h18m-9 4h4"></path></svg>
                        Disponibilidade Atualizada em Tempo Real
                    </li>
                </ul>

                {{-- Imagem Opcional de Fundo ou Ícone --}}
                <div class="mt-4 text-center text-gray-400 dark:text-gray-600 text-sm">
                    <p>Feito com Laravel e Tailwind CSS.</p>
                </div>
            </div>

            {{-- LADO DIREITO: BOTÃO DE AÇÃO --}}
            <div class="lg:w-2/5 p-8 sm:p-12 lg:p-16 bg-indigo-700 dark:bg-indigo-900 flex flex-col items-center justify-center text-white">

                <h2 class="text-3xl font-extrabold mb-6 text-center">
                    Reserve seu Horário!
                </h2>

                <p class="mb-8 text-center text-indigo-100 dark:text-indigo-200">
                    Veja o calendário completo e garanta o seu espaço antes que seja tarde.
                </p>

                {{-- BOTÃO PRINCIPAL DE AÇÃO (Usando o # temporário) --}}
                <a
                    href="#" {{-- MANTIDO COMO # ATÉ VOCÊ CRIAR A ROTA --}}
                    class="w-full text-center bg-yellow-400 text-gray-900 hover:bg-yellow-500 font-bold py-4 px-6 rounded-xl shadow-2xl text-xl transition-all duration-300 uppercase tracking-widest transform hover:scale-[1.02]"
                >
                    Ver Disponibilidade
                </a>

            </div>

        </main>
    </div>
</body>
