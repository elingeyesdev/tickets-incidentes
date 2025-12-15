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

        // Registrar Event Listeners
        $this->registerEventListeners();
    }

    /**
     * Register event listeners for Authentication feature
     */
    protected function registerEventListeners(): void
    {
        $events = $this->app['events'];

        // UserRegistered -> SendVerificationEmail
        $events->listen(
            \App\Features\Authentication\Events\UserRegistered::class,
            \App\Features\Authentication\Listeners\SendVerificationEmail::class
        );

        // UserLoggedIn -> LogLoginActivity
        $events->listen(
            \App\Features\Authentication\Events\UserLoggedIn::class,
            \App\Features\Authentication\Listeners\LogLoginActivity::class
        );

        // PasswordResetRequested -> SendPasswordResetEmail
        $events->listen(
            \App\Features\Authentication\Events\PasswordResetRequested::class,
            \App\Features\Authentication\Listeners\SendPasswordResetEmail::class
        );
    }
}
