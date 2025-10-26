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

        // Registrar Event Listeners
        $this->registerEventListeners();
    }

    /**
     * Register event listeners for CompanyManagement feature
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app['events'];

        \Illuminate\Support\Facades\Log::debug('CompanyManagementServiceProvider: Registering event listeners');

        // CompanyRequestApproved -> SendApprovalEmail
        $events->listen(
            \App\Features\CompanyManagement\Events\CompanyRequestApproved::class,
            \App\Features\CompanyManagement\Listeners\SendApprovalEmail::class
        );
        \Illuminate\Support\Facades\Log::debug('CompanyManagementServiceProvider: Registered SendApprovalEmail listener');

        // CompanyRequestRejected -> SendRejectionEmail
        $events->listen(
            \App\Features\CompanyManagement\Events\CompanyRequestRejected::class,
            \App\Features\CompanyManagement\Listeners\SendRejectionEmail::class
        );
        \Illuminate\Support\Facades\Log::debug('CompanyManagementServiceProvider: Registered SendRejectionEmail listener');
    }
}
