<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * O caminho para a rota "home" da sua aplicação.
     *
     * Tipicamente, usuários são redirecionados para cá após a autenticação.
     * Alteramos de '/dashboard' para o painel de gerenciamento de reservas,
     * que é o destino imediato para o gestor.
     *
     * @var string
     */
    public const HOME = '/admin/reservas';

    /**
     * Define seu mapeamento de rotas, constraints de padrão, filtros, e muito mais.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
