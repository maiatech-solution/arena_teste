<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Elite Soccer') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,600,700,800&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <!-- Mantendo apenas para o setup padrão do Laravel -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<!--
    BODY: Classes minimalistas, garantindo altura total (h-full) e removendo fundos.
    A classe "arena-bg" foi removida para usar o gradiente de login.blade.php.
-->
<body class="font-sans text-gray-900 antialiased h-full">
    <div class="h-full w-full">
        <!--
            O SLOT (onde o login.blade.php será injetado) agora está diretamente
            no contêiner principal, sem paddings ou logos indesejados.
        -->
        {{ $slot }}
    </div>
</body>


</html>
