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
        $events = $this->app['events'];

        // Auto-crear 5 categorías por defecto cuando se crea una empresa
        // Las categorías específicas dependen del industry_type de la empresa
        $events->listen(
            \App\Features\CompanyManagement\Events\CompanyCreated::class,
            \App\Features\TicketManagement\Listeners\CreateDefaultCategoriesListener::class
        );

        // Cuando se agrega una respuesta a un ticket, enviar email al usuario
        // (solo si la respuesta es de un agente)
        $events->listen(
            \App\Features\TicketManagement\Events\ResponseAdded::class,
            \App\Features\TicketManagement\Listeners\SendTicketResponseEmail::class
        );
    }
}
