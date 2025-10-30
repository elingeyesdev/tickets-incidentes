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
            \App\Http\Middleware\HandleInertiaRequests::class,
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $handler = new ApiExceptionHandler();
                return $handler->handle($request, function() use ($e) { throw $e; });
            }
        });
    })->create();