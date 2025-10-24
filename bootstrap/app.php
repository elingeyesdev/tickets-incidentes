<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;

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

        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // Web middleware aliases
        $middleware->alias([
            'web.auth' => \App\Http\Middleware\JWT\WebAuthenticationMiddleware::class,
        ]);

        // GraphQL API middleware aliases
        $middleware->api(append: [
            'jwt.auth' => \App\Http\Middleware\JWT\JWTAuthenticationMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();