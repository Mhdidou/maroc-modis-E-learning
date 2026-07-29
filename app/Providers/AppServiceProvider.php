<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        Vite::prefetch(concurrency: 3);

        // Hors production : tout accès lazy (N+1) lève une exception. Force
        // l'eager loading systématique exigé par la règle « zéro latence ».
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
