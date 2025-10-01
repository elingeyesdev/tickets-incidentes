<?php

declare(strict_types=1);

namespace App\Features\Authentication;

use Illuminate\Support\ServiceProvider;

class AuthenticationServiceProvider extends ServiceProvider
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
        // Registrar migrations de la feature
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');
    }
}
