<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ApiExceptionHandler;
use App\Http\Middleware\AuthenticateJwt;

// Detect if we're running tests and load .env.testing
// phpunit.xml is loaded AFTER .env, so we must explicitly load .env.testing here
// Check multiple indicators: PHPUNIT env var, argv contains phpunit, or APP_ENV from phpunit.xml
$isRunningTests = (
    defined('PHPUNIT_COMPOSER_INSTALL') ||
    (isset($_SERVER['argv']) && str_contains(implode(' ', $_SERVER['argv']), 'phpunit')) ||
    (isset($_SERVER['argv']) && str_contains(implode(' ', $_SERVER['argv']), 'artisan test')) ||
    isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing' ||
    isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'testing'
);

// If tests detected, load .env.testing to override .env with test-specific values
if ($isRunningTests) {
    $envTestingPath = dirname(__DIR__) . '/.env.testing';
    if (file_exists($envTestingPath)) {
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__), '.env.testing');
        $dotenv->safeLoad();
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',           // ← Web routes with Blade templates
        api: __DIR__.'/../routes/api.php',           // ← REST API endpoints
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Use custom TrimStrings middleware to preserve content field
        $middleware->use([
            \App\Http\Middleware\TrimStrings::class,
        ]);

        // Exclude jwt_token from encryption so JS can set it and Backend can read it
        $middleware->encryptCookies(except: [
            'jwt_token',
        ]);

        $middleware->web(append: [
            // Using Laravel Blade templates - no Inertia middleware needed
        ]);

        // API middleware for GraphQL endpoint & JWT authentication
        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // Note: ApiExceptionHandler is NOT used as middleware
            // Instead, exceptions are handled via bootstrap/app.php renderable handlers
        ]);

        // API routes are stateless - disable CSRF protection for all /api/* routes
        $middleware->validateCsrfTokens(except: [
            'api/*',  // All API routes bypass CSRF (they use JWT instead)
        ]);

        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // Middleware aliases para autenticación
        $middleware->alias([
            'web.auth' => \App\Http\Middleware\JWT\WebAuthenticationMiddleware::class,
            'jwt.auth' => \App\Http\Middleware\JWT\JWTAuthenticationMiddleware::class,           // ← Autenticación OPCIONAL
            'jwt.require' => \App\Http\Middleware\JWT\RequireJWTAuthentication::class,        // ← Autenticación OBLIGATORIA
            'auth:api' => AuthenticateJwt::class,  // ← Para REST API authentication (legacy)
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,  // ← Role-based authorization
            'role.selected' => \App\Http\Middleware\EnsureRoleSelected::class,  // ← Ensure user has selected active role
            'company.ownership' => \App\Features\CompanyManagement\Http\Middleware\EnsureCompanyOwnership::class,  // ← Company ownership validation
            'throttle.user' => \App\Http\Middleware\ThrottleByUser::class,  // ← User-based rate limiting (requires JWT)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manejar ModelNotFoundException para rutas API
        $exceptions->renderable(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            \Log::debug('ModelNotFoundException renderable called', [
                'path' => $request->path(),
                'is_api' => $request->is('api/*'),
            ]);

            if ($request->is('api/*')) {
                \Log::debug('Handling ModelNotFoundException for API');
                $handler = new ApiExceptionHandler();
                return $handler->handleModelNotFoundException($e);
            }
        });

        // Manejar ValidationException para rutas API
        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $handler = new ApiExceptionHandler();
                return $handler->handleValidationException($e);
            }
        });

        // Manejar AuthorizationException para rutas API
        $exceptions->renderable(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $handler = new ApiExceptionHandler();
                return $handler->handleLaravelAuthorizationException($e);
            }
        });

        // Manejar ErrorWithExtensions para rutas API
        $exceptions->renderable(function (\App\Shared\Errors\ErrorWithExtensions $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $handler = new ApiExceptionHandler();
                return $handler->handleErrorWithExtensions($e);
            }
        });

        // Manejar HelpdeskException para rutas API
        $exceptions->renderable(function (\App\Shared\Exceptions\HelpdeskException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $handler = new ApiExceptionHandler();
                return $handler->handleHelpdeskException($e);
            }
        });

        // Manejar QueryException para rutas API
        $exceptions->renderable(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $handler = new ApiExceptionHandler();
                return $handler->handleDatabaseException($e);
            }
        });
    })->create();
