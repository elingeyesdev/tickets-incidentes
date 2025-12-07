<?php

declare(strict_types=1);

namespace App\Features\AuditLog;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * AuditLogServiceProvider
 *
 * Service Provider para el feature de Activity Log.
 */
class AuditLogServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar el servicio como singleton
        $this->app->singleton(
            \App\Features\AuditLog\Services\ActivityLogService::class,
            \App\Features\AuditLog\Services\ActivityLogService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Registrar comandos
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Features\AuditLog\Console\FlushActivityLogBuffer::class,
                \App\Features\AuditLog\Console\CleanOldActivityLogs::class,
            ]);
        }

        // Registrar Event Listeners
        $this->registerEventListeners();

        // Registrar scheduler
        $this->registerScheduledTasks();
    }

    /**
     * Register event listeners
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app['events'];

        // ==================== AUTH EVENTS ====================

        // Login exitoso
        $events->listen(
            \App\Features\Authentication\Events\UserLoggedIn::class,
            [\App\Features\AuditLog\Listeners\LogAuthActivity::class, 'handleLogin']
        );

        // Logout
        $events->listen(
            \App\Features\Authentication\Events\UserLoggedOut::class,
            [\App\Features\AuditLog\Listeners\LogAuthActivity::class, 'handleLogout']
        );

        // Registro
        $events->listen(
            \App\Features\Authentication\Events\UserRegistered::class,
            [\App\Features\AuditLog\Listeners\LogAuthActivity::class, 'handleRegistered']
        );

        // Email verificado
        $events->listen(
            \App\Features\Authentication\Events\EmailVerified::class,
            [\App\Features\AuditLog\Listeners\LogAuthActivity::class, 'handleEmailVerified']
        );

        // Password reset solicitado
        $events->listen(
            \App\Features\Authentication\Events\PasswordResetRequested::class,
            [\App\Features\AuditLog\Listeners\LogAuthActivity::class, 'handlePasswordResetRequested']
        );

        // ==================== TICKET EVENTS ====================

        // Ticket creado
        $events->listen(
            \App\Features\TicketManagement\Events\TicketCreated::class,
            [\App\Features\AuditLog\Listeners\LogTicketActivity::class, 'handleTicketCreated']
        );

        // Ticket resuelto
        $events->listen(
            \App\Features\TicketManagement\Events\TicketResolved::class,
            [\App\Features\AuditLog\Listeners\LogTicketActivity::class, 'handleTicketResolved']
        );

        // Ticket cerrado
        $events->listen(
            \App\Features\TicketManagement\Events\TicketClosed::class,
            [\App\Features\AuditLog\Listeners\LogTicketActivity::class, 'handleTicketClosed']
        );

        // Ticket reabierto
        $events->listen(
            \App\Features\TicketManagement\Events\TicketReopened::class,
            [\App\Features\AuditLog\Listeners\LogTicketActivity::class, 'handleTicketReopened']
        );

        // Ticket asignado
        $events->listen(
            \App\Features\TicketManagement\Events\TicketAssigned::class,
            [\App\Features\AuditLog\Listeners\LogTicketActivity::class, 'handleTicketAssigned']
        );

        // Respuesta agregada
        $events->listen(
            \App\Features\TicketManagement\Events\ResponseAdded::class,
            [\App\Features\AuditLog\Listeners\LogTicketActivity::class, 'handleResponseAdded']
        );
    }

    /**
     * Register scheduled tasks
     */
    protected function registerScheduledTasks(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Flush buffer cada minuto
            $schedule->command('activity-log:flush')
                ->everyMinute()
                ->withoutOverlapping();

            // Limpiar logs antiguos diariamente a las 3am
            $schedule->command('activity-log:clean --days=90')
                ->dailyAt('03:00')
                ->withoutOverlapping();
        });
    }
}
