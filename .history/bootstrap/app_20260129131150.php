<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // 🎯 DEFINIÇÃO DE REDIRECIONAMENTO GLOBAL
        // Isso garante que após o login, o Laravel aponte para a seleção de módulos
        $middleware->redirectTo(
            guests: '/login',
            users: '/select-modules'
        );

        $middleware->alias([
            'gestor' => \App\Http\Middleware\IsGestor::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
