<?php

declare(strict_types=1);

namespace App\Features\TicketManagement;

use Illuminate\Support\ServiceProvider;

class TicketManagementServiceProvider extends ServiceProvider
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
     * Register event listeners for TicketManagement feature
     */
    protected function registerEventListeners(): void
    {
        // $events = $this->app['events'];
        // Ejemplo:
        // $events->listen(
        //     \App\Features\TicketManagement\Events\TicketCreated::class,
        //     \App\Features\TicketManagement\Listeners\NotifyNewTicket::class
        // );
    }
}
