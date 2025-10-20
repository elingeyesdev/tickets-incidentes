<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web-jwt-pure.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // Registrar middleware aliases para protección de rutas (JWT Pure)
        $middleware->alias([
            'jwt.auth' => \App\Http\Middleware\JWT\JWTAuthenticationMiddleware::class,
            'jwt.role' => \App\Http\Middleware\JWT\JWTRoleMiddleware::class,
            'jwt.onboarding' => \App\Http\Middleware\JWT\JWTOnboardingMiddleware::class,
            'jwt.guest' => \App\Http\Middleware\JWT\JWTGuestMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();