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
        // Load migrations from feature directories
        $this->loadMigrationsFrom([
            database_path('migrations'),
            app_path('Features/UserManagement/Database/Migrations'),
            app_path('Features/Authentication/Database/Migrations'),
            app_path('Features/CompanyManagement/Database/Migrations'),
        ]);
    }
}
