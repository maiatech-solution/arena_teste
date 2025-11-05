<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="font-sans text-gray-900  antialiased arena-bg">
        <div class="min-h-screen flex flex-col  items-center pt-6 sm:pt-0">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            {{-- ⚠️ CORREÇÃO APLICADA AQUI: REMOVIDAS AS CLASSES DE RESTRIÇÃO DE LARGURA --}}
            {{-- Removido: sm:max-w-md, bg-white, px-6 py-4, shadow-md, sm:rounded-lg --}}
            <div class="w-full mt-6 sm:px-6">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
