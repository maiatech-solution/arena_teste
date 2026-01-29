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

        {{-- Aqui chamamos o arquivo de navegação isolado --}}
        @include('layouts.navigation_bar')

        {{-- CONTEÚDO --}}
        <main class="flex-grow py-12">
            {{ $slot }}
        </main>

    </div>
</body>
</html>
