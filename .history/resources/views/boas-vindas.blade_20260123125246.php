<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $site_info->nome_fantasia ?? config('app.name') }} | Bem-vindo</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .arena-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #10b981 100%);
        }
    </style>
</head>

<body class="font-sans antialiased arena-bg">

    <div class="absolute top-0 right-0 p-6 text-right sm:fixed flex items-center space-x-4">
        @auth
            <a href="{{ url('/dashboard') }}"
                class="font-semibold text-white hover:text-gray-200 focus:outline focus:outline-2 focus:rounded-sm focus:outline-white transition duration-300">
                Acessar Dashboard
            </a>

            <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                @csrf
                <button type="submit"
                    class="font-semibold text-white hover:text-gray-200 focus:outline focus:outline-2 focus:rounded-sm focus:outline-white transition duration-300">
                    Sair
                </button>
            </form>
        @else
            <a href="{{ route('login') }}"
                class="font-semibold text-white hover:text-gray-200 focus:outline focus:outline-2 focus:rounded-sm focus:outline-white transition duration-300">
                Acesso Gestor
            </a>
        @endauth
    </div>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div
            class="text-center p-8 bg-white/95 backdrop-blur-sm shadow-2xl rounded-xl max-w-lg w-full transform transition-all hover:scale-[1.01] duration-300 ease-in-out">

            <div class="mb-8">
                {{-- Ícone Dinâmico --}}
                <svg class="mx-auto h-16 w-16 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm0 18c-4.411 0-8-3.589-8-8 0-1.276.311-2.51.895-3.605l7.105 7.105v.5c0 1.103.897 2 2 2h.5l-.014 1.514C15.827 20 13.988 20 12 20zm5.105-2.895l-7.105-7.105c1.095-.584 2.329-.895 3.605-.895 4.411 0 8 3.589 8 8 0 1.988-.514 3.827-1.395 5.499zM12 4c1.276 0 2.51.311 3.605.895l-7.105 7.105H8c-1.103 0-2-.897-2-2V8.395L4.895 6.895C6.425 4.793 9.07 4 12 4z" />
                </svg>

                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mt-4">
                    {{ $site_info->nome_fantasia ?? 'Bem-vindo' }}
                </h1>

                <span class="text-2xl md:text-xl font-extrabold text-gray-900 mt-4">
                    Agendamento Online
                </span>
            </div>

            <div class="space-y-4">
                {{-- Agendamento --}}
                <a href="{{ route('reserva.index') }}"
                    class="w-full inline-flex justify-center items-center px-6 py-4 border border-gray-300 text-lg font-bold rounded-lg shadow-md text-gray-800 bg-yellow-400 hover:bg-yellow-500 hover:shadow-lg transition duration-300">
                    <span class="mr-2">📅</span> Conferir Horários Disponíveis
                </a>

                {{-- WhatsApp Dinâmico --}}
                @if ($site_info && $site_info->whatsapp_suporte)
                    <a href="https://wa.me/55{{ $site_info->whatsapp_suporte }}?text=Olá%2C%20gostaria%20de%20falar%20sobre%20agendamento%20na%20{{ urlencode($site_info->nome_fantasia) }}"
                        target="_blank"
                        class="w-full inline-flex justify-center items-center px-6 py-4 border border-transparent text-lg font-bold rounded-lg shadow-md text-white bg-green-600 hover:bg-green-700 hover:shadow-lg transition duration-300 transform hover:-translate-y-0.5">
                        <span class="mr-2">💬</span> Contato (WhatsApp)
                    </a>
                @endif

                {{-- Google Maps Dinâmico --}}
                @php
                    $enderecoCompleto =
                        ($site_info->logradouro ?? '') .
                        ' ' .
                        ($site_info->numero ?? '') .
                        ' ' .
                        ($site_info->bairro ?? '') .
                        ' ' .
                        ($site_info->cidade ?? '') .
                        ' ' .
                        ($site_info->estado ?? '');
                @endphp

                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($enderecoCompleto ?: 'Elite Soccer Belém') }}"
                    target="_blank"
                    class="w-full inline-flex justify-center items-center px-6 py-4 border border-gray-300 text-lg font-bold rounded-lg shadow-md text-white bg-blue-600 hover:bg-blue-700 hover:shadow-lg transition duration-300">
                    <span class="mr-2">🗺️</span> Como Chegar
                </a>
            </div>
        </div>
    </div>
    {{-- FOOTER MAIATECH SOLUTION --}}
    <footer class="w-full py-8 mt-auto print:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                class="flex flex-col md:flex-row justify-between items-center gap-4 opacity-80 hover:opacity-100 transition-opacity duration-500">
                <div
                    class="text-[10px] font-black uppercase text-white/70 tracking-[0.2em] italic text-center md:text-left">
                    © {{ date('Y') }} • {{ $site_info->nome_fantasia ?? 'Elite Soccer' }} • Gestão Profissional
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-[9px] font-bold text-white/60 uppercase italic">Desenvolvido por</span>
                    <a href="https://www.maiatechsolution.com.br/" target="_blank"
                        class="flex items-center gap-1.5 group transition-transform hover:scale-105">
                        <span
                            class="text-xs font-black text-white tracking-tighter uppercase group-hover:text-yellow-400">
                            Maiatech
                        </span>
                        <span
                            class="px-1.5 py-0.5 bg-white text-blue-900 text-[8px] font-black rounded uppercase italic shadow-sm group-hover:bg-yellow-400 group-hover:text-gray-900 transition-colors">
                            Solution
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </footer>

</body>

</html>
</body>

</html>
