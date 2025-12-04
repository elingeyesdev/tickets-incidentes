<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Secret
    |--------------------------------------------------------------------------
    |
    | Clave secreta para firmar los JWT tokens.
    | CRÍTICO: Usar una clave fuerte y NUNCA commitearla en git.
    | Generar con: php artisan key:generate (usar la misma de APP_KEY o crear una específica)
    |
    */

    'secret' => env('JWT_SECRET', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | JWT Algorithm
    |--------------------------------------------------------------------------
    |
    | Algoritmo usado para firmar los tokens.
    | HS256 es el default (HMAC with SHA-256).
    | Alternativas: HS512, RS256 (requiere key pair público/privado)
    |
    */

    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Access Token TTL (Time To Live)
    |--------------------------------------------------------------------------
    |
    | Tiempo de vida del access token en MINUTOS.
    | Default: 60 minutos (1 hora)
    | Recomendado: 15-60 minutos para seguridad
    |
    | TEMPORAL: 1 minuto para testing
    |
    */

    'ttl' => env('JWT_TTL', 1), // TESTING: 1 minute

    /*
    |--------------------------------------------------------------------------
    | Refresh Token TTL
    |--------------------------------------------------------------------------
    |
    | Tiempo de vida del refresh token en MINUTOS.
    | Default: 43200 minutos (30 días)
    | Recomendado: 7-30 días
    |
    */

    'refresh_ttl' => env('JWT_REFRESH_TTL', 43200),

    /*
    |--------------------------------------------------------------------------
    | JWT Issuer
    |--------------------------------------------------------------------------
    |
    | Identificador del emisor del token (claim 'iss')
    | Útil para validar que el token fue emitido por este sistema
    |
    */

    'issuer' => env('JWT_ISSUER', 'helpdesk-api'),

    /*
    |--------------------------------------------------------------------------
    | JWT Audience
    |--------------------------------------------------------------------------
    |
    | Identificador de la audiencia del token (claim 'aud')
    | Útil para validar que el token es para el frontend correcto
    |
    */

    'audience' => env('JWT_AUDIENCE', 'helpdesk-frontend'),

    /*
    |--------------------------------------------------------------------------
    | Required Claims
    |--------------------------------------------------------------------------
    |
    | Claims que DEBEN estar presentes en el token para considerarlo válido
    |
    */

    'required_claims' => [
        'iss',  // Issuer
        'iat',  // Issued at
        'exp',  // Expiration
        'sub',  // Subject (user_id)
        'user_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Leeway
    |--------------------------------------------------------------------------
    |
    | Margen de error en segundos para validación de timestamps.
    | Útil para compensar diferencias de reloj entre servidores.
    | Default: 0 (sin margen)
    |
    */

    'leeway' => env('JWT_LEEWAY', 0),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Enabled
    |--------------------------------------------------------------------------
    |
    | Habilitar blacklist de tokens (para logout inmediato).
    | Si está habilitado, tokens revocados se guardan en cache.
    | Default: true
    |
    */

    'blacklist_enabled' => filter_var(env('JWT_BLACKLIST_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Blacklist Grace Period
    |--------------------------------------------------------------------------
    |
    | Periodo de gracia en segundos después de refresh donde el token viejo
    | sigue siendo válido. Útil para requests en paralelo.
    | Default: 0 (sin periodo de gracia)
    |
    */

    'blacklist_grace_period' => env('JWT_BLACKLIST_GRACE_PERIOD', 0),

    /*
    |--------------------------------------------------------------------------
    | Custom Claims
    |--------------------------------------------------------------------------
    |
    | Claims adicionales que se incluirán en el payload del token
    | Estos se agregan automáticamente por TokenService
    |
    */

    'custom_claims' => [
        'email',
        'roles',
        'companies',
        'session_id',
    ],

];