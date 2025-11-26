<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            app_path('Features/TicketManagement/Database/Migrations'),
            app_path('Features/ContentManagement/Database/Migrations'),
        ]);

        // Register custom factory resolver for Feature-First architecture
        // Factories are located in app/Features/[Feature]/Database/Factories/
        // not in the default database/factories/ location
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            // Handle models in Feature directories
            if (str_contains($modelName, 'Features')) {
                $parts = explode('\\', $modelName);
                $modelClass = array_pop($parts); // Get model class name

                // Replace Models with Database\Factories
                // App\Features\Authentication\Models\RefreshToken
                // → App\Features\Authentication\Database\Factories\RefreshTokenFactory
                $modelsIndex = array_search('Models', $parts);
                if ($modelsIndex !== false) {
                    // Replace Models with Database and Factories
                    array_splice($parts, $modelsIndex, 1, ['Database', 'Factories']);
                }

                return implode('\\', $parts) . '\\' . $modelClass . 'Factory';
            }

            // Fallback for any other models (default behavior)
            return 'Database\\Factories\\' . class_basename($modelName) . 'Factory';
        });

        // Register Policies (Feature-First Architecture)
        $this->registerPolicies();
    }

    /**
     * Registrar policies del sistema
     */
    protected function registerPolicies(): void
    {
        Gate::policy(
            \App\Features\CompanyManagement\Models\Company::class,
            \App\Features\CompanyManagement\Policies\CompanyPolicy::class
        );

        Gate::policy(
            \App\Features\TicketManagement\Models\Category::class,
            \App\Features\TicketManagement\Policies\CategoryPolicy::class
        );

        // Ticket Management Policies
        Gate::policy(
            \App\Features\TicketManagement\Models\Ticket::class,
            \App\Features\TicketManagement\Policies\TicketPolicy::class
        );

        // NOTA: Area no necesita policy propia, usa CompanyPolicy->manageAreas()
        // ya que áreas son parte de la configuración de la empresa

        Gate::policy(
            \App\Features\TicketManagement\Models\TicketResponse::class,
            \App\Features\TicketManagement\Policies\TicketResponsePolicy::class
        );

        Gate::policy(
            \App\Features\TicketManagement\Models\TicketAttachment::class,
            \App\Features\TicketManagement\Policies\TicketAttachmentPolicy::class
        );

        Gate::policy(
            \App\Features\TicketManagement\Models\TicketRating::class,
            \App\Features\TicketManagement\Policies\TicketRatingPolicy::class
        );
    }
}
