<?php

declare(strict_types=1);

namespace App\Features\ContentManagement;

use Illuminate\Support\ServiceProvider;

class ContentManagementServiceProvider extends ServiceProvider
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
     * Register event listeners for ContentManagement feature
     */
    protected function registerEventListeners(): void
    {
        // $events = $this->app['events'];
        // Ejemplo:
        // $events->listen(
        //     \App\Features\ContentManagement\Events\ArticlePublished::class,
        //     \App\Features\ContentManagement\Listeners\NotifyArticlePublished::class
        // );
    }
}
