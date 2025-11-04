<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ApiExceptionHandler;
use App\Http\Middleware\AuthenticateJwt;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web-jwt-pure.php',  // ← Web routes with Inertia
        api: __DIR__.'/../routes/api.php',           // ← GraphQL endpoint
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Add middleware to the API group for stateless Inertia & cookie-based refresh tokens
        $middleware->api(prepend: [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // Note: HandleInertiaRequests removed from API (causes exception handling issues)
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

        // Manejar todas las demás excepciones para rutas API
        $exceptions->renderable(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $handler = new ApiExceptionHandler();
                return $handler->handleGenericException($e);
            }
        });
    })->create();
