<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de límites de tasa por endpoint.
    | Protege contra ataques de fuerza bruta y abuso de la API.
    |
    | Estructura:
    | 'endpoint' => [
    |     'max' => número máximo de intentos,
    |     'decay' => ventana de tiempo en segundos,
    |     'message' => mensaje personalizado cuando se alcanza el límite
    | ]
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Authentication Endpoints
    |--------------------------------------------------------------------------
    */

    'register' => [
        'max' => env('RATE_LIMIT_REGISTER_MAX', 5),
        'decay' => env('RATE_LIMIT_REGISTER_DECAY', 3600), // 1 hora
        'message' => 'Demasiados intentos de registro. Por favor, intenta más tarde.',
    ],

    'login' => [
        'max' => env('RATE_LIMIT_LOGIN_MAX', 5),
        'decay' => env('RATE_LIMIT_LOGIN_DECAY', 900), // 15 minutos
        'message' => 'Demasiados intentos de login. Por favor, espera 15 minutos.',
    ],

    'login_with_google' => [
        'max' => env('RATE_LIMIT_GOOGLE_LOGIN_MAX', 10),
        'decay' => env('RATE_LIMIT_GOOGLE_LOGIN_DECAY', 3600), // 1 hora
        'message' => 'Demasiados intentos de login con Google. Por favor, intenta más tarde.',
    ],

    'refresh_token' => [
        'max' => env('RATE_LIMIT_REFRESH_MAX', 20),
        'decay' => env('RATE_LIMIT_REFRESH_DECAY', 60), // 1 minuto
        'message' => 'Demasiadas renovaciones de token. Por favor, espera un momento.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset Endpoints
    |--------------------------------------------------------------------------
    */

    'reset_password' => [
        'max' => env('RATE_LIMIT_RESET_PASSWORD_MAX', 3),
        'decay' => env('RATE_LIMIT_RESET_PASSWORD_DECAY', 3600), // 1 hora
        'message' => 'Demasiados intentos de reset. Por favor, espera 1 hora.',
    ],

    'confirm_password_reset' => [
        'max' => env('RATE_LIMIT_CONFIRM_RESET_MAX', 3),
        'decay' => env('RATE_LIMIT_CONFIRM_RESET_DECAY', 900), // 15 minutos
        'message' => 'Demasiados intentos de confirmación. Por favor, espera 15 minutos.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Verification Endpoints
    |--------------------------------------------------------------------------
    */

    'verify_email' => [
        'max' => env('RATE_LIMIT_VERIFY_EMAIL_MAX', 5),
        'decay' => env('RATE_LIMIT_VERIFY_EMAIL_DECAY', 3600), // 1 hora
        'message' => 'Demasiados intentos de verificación. Por favor, intenta más tarde.',
    ],

    'resend_email_verification' => [
        'max' => env('RATE_LIMIT_RESEND_VERIFICATION_MAX', 3),
        'decay' => env('RATE_LIMIT_RESEND_VERIFICATION_DECAY', 300), // 5 minutos
        'message' => 'Debes esperar 5 minutos antes de reenviar el email de verificación.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Rate Limit
    |--------------------------------------------------------------------------
    |
    | Límite por defecto para endpoints que no tienen configuración específica
    |
    */

    'default' => [
        'max' => env('RATE_LIMIT_DEFAULT_MAX', 60),
        'decay' => env('RATE_LIMIT_DEFAULT_DECAY', 60), // 1 minuto
        'message' => 'Demasiadas solicitudes. Por favor, espera un momento.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit by IP vs User
    |--------------------------------------------------------------------------
    |
    | Definir si el rate limiting se aplica por IP o por usuario autenticado
    | 'ip' = Por dirección IP (default para endpoints públicos)
    | 'user' = Por usuario autenticado (para endpoints protegidos)
    |
    */

    'rate_limit_by' => [
        'register' => 'ip',
        'login' => 'ip',
        'login_with_google' => 'ip',
        'reset_password' => 'ip',
        'confirm_password_reset' => 'ip',
        'verify_email' => 'ip',
        'resend_email_verification' => 'user', // Usuario debe estar autenticado
        'refresh_token' => 'user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Connection
    |--------------------------------------------------------------------------
    |
    | Conexión de Redis a usar para almacenar counters de rate limiting
    | Default: 'default'
    |
    */

    'redis_connection' => env('RATE_LIMIT_REDIS_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Cache Driver
    |--------------------------------------------------------------------------
    |
    | Driver de cache a usar si Redis no está disponible
    | Default: 'redis' (recomendado para producción)
    | Alternativa: 'file' (solo para desarrollo)
    |
    */

    'cache_driver' => env('RATE_LIMIT_CACHE_DRIVER', 'redis'),

];