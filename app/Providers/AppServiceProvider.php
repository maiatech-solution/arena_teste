<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\CompanyInfo;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Compartilha os dados da Elite Soccer com todas as views do sistema
        View::composer('*', function ($view) {
            // Usamos o first() pois só existirá um registro de configuração da empresa
            $view->with('site_info', CompanyInfo::first());
        });
    }
}