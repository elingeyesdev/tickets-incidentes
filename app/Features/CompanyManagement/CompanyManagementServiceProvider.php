<?php

declare(strict_types=1);

namespace App\Features\CompanyManagement;

use Illuminate\Support\ServiceProvider;

class CompanyManagementServiceProvider extends ServiceProvider
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