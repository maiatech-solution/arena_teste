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

        // As definições de middlewares padrões ('auth', 'verified', etc.) são automáticas no Laravel 11+.
        // Apenas o nosso middleware customizado 'gestor' precisa ser definido aqui como alias.

        $middleware->alias([
            // 🎯 FOCO: Apenas o nosso middleware customizado 'gestor' é adicionado aqui.
            'gestor' => \App\Http\Middleware\IsGestor::class,
        ]);

        // Se você tiver outros middlewares que precisam ser aplicados globalmente ou em grupos específicos,
        // você os adiciona neste bloco.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Este bloco lida com a renderização de exceções
    })->create();
