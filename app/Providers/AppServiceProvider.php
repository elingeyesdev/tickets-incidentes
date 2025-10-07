<?php

namespace App\Providers;

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
        // Load migrations from shared infrastructure and feature directories
        // Order matters: Shared first (extensions, schemas), then features
        $this->loadMigrationsFrom([
            app_path('Shared/Database/Migrations'),           // Extensions, schemas, audit system
            app_path('Features/UserManagement/Database/Migrations'),
            app_path('Features/Authentication/Database/Migrations'),
            app_path('Features/CompanyManagement/Database/Migrations'),
        ]);
    }
}
