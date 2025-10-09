<?php declare(strict_types=1);

namespace App\Shared\GraphQL\Errors;

/**
 * Error Code Registry
 *
 * Registro centralizado de códigos de error de GraphQL.
 * Permite un manejo consistente y predecible de errores por parte de los clientes.
 *
 * Códigos basados en:
 * - GraphQL Spec
 * - Apollo Error Codes
 * - HTTP Status Codes (adaptados)
 *
 * Uso:
 * ```php
 * $result['extensions']['code'] = ErrorCodeRegistry::VALIDATION_ERROR;
 * ```
 *
 * En el frontend (React Native, Web):
 * ```typescript
 * if (error.extensions.code === 'VALIDATION_ERROR') {
 *   // Mostrar errores de formulario
 * }
 * ```
 *
 * @package App\Shared\GraphQL\Errors
 */
class ErrorCodeRegistry
{
    // ========================================
    // AUTHENTICATION (401) - No autenticado
    // ========================================

    /** Usuario no autenticado (falta token o es inválido) */
    public const UNAUTHENTICATED = 'UNAUTHENTICATED';

    /** Token de acceso expirado */
    public const TOKEN_EXPIRED = 'TOKEN_EXPIRED';

    /** Token de acceso inválido */
    public const INVALID_TOKEN = 'INVALID_TOKEN';

    /** Credenciales inválidas (email/password incorrectos) */
    public const INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';

    /** Email no verificado */
    public const EMAIL_NOT_VERIFIED = 'EMAIL_NOT_VERIFIED';

    /** Refresh token inválido o expirado */
    public const INVALID_REFRESH_TOKEN = 'INVALID_REFRESH_TOKEN';

    /** Cuenta de usuario suspendida */
    public const ACCOUNT_SUSPENDED = 'ACCOUNT_SUSPENDED';

    // ========================================
    // AUTHORIZATION (403) - No tiene permisos
    // ========================================

    /** Usuario no tiene permisos para realizar la acción */
    public const FORBIDDEN = 'FORBIDDEN';

    /** Usuario no tiene el rol requerido */
    public const INSUFFICIENT_ROLE = 'INSUFFICIENT_ROLE';

    /** Usuario no pertenece a la empresa requerida */
    public const WRONG_COMPANY = 'WRONG_COMPANY';

    /** Acción no permitida en el estado actual del recurso */
    public const ACTION_NOT_ALLOWED = 'ACTION_NOT_ALLOWED';

    // ========================================
    // VALIDATION (400) - Datos inválidos
    // ========================================

    /** Error de validación de input */
    public const VALIDATION_ERROR = 'VALIDATION_ERROR';

    /** Campo requerido faltante */
    public const REQUIRED_FIELD = 'REQUIRED_FIELD';

    /** Formato de campo inválido */
    public const INVALID_FORMAT = 'INVALID_FORMAT';

    /** Valor fuera de rango permitido */
    public const OUT_OF_RANGE = 'OUT_OF_RANGE';

    // ========================================
    // BUSINESS LOGIC (400/409) - Reglas de negocio
    // ========================================

    /** Recurso ya existe (email duplicado, código duplicado, etc.) */
    public const RESOURCE_ALREADY_EXISTS = 'RESOURCE_ALREADY_EXISTS';

    /** Email duplicado */
    public const DUPLICATE_EMAIL = 'DUPLICATE_EMAIL';

    /** Código duplicado (user_code, company_code, etc.) */
    public const DUPLICATE_CODE = 'DUPLICATE_CODE';

    /** Conflicto con el estado actual del recurso */
    public const CONFLICT = 'CONFLICT';

    /** Operación no permitida en el estado actual */
    public const INVALID_STATE = 'INVALID_STATE';

    // ========================================
    // NOT FOUND (404) - Recurso no encontrado
    // ========================================

    /** Recurso no encontrado */
    public const NOT_FOUND = 'NOT_FOUND';

    /** Usuario no encontrado */
    public const USER_NOT_FOUND = 'USER_NOT_FOUND';

    /** Empresa no encontrada */
    public const COMPANY_NOT_FOUND = 'COMPANY_NOT_FOUND';

    /** Ticket no encontrado */
    public const TICKET_NOT_FOUND = 'TICKET_NOT_FOUND';

    // ========================================
    // RATE LIMITING (429) - Demasiadas solicitudes
    // ========================================

    /** Rate limit excedido */
    public const RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';

    /** Demasiados intentos de login */
    public const TOO_MANY_LOGIN_ATTEMPTS = 'TOO_MANY_LOGIN_ATTEMPTS';

    // ========================================
    // SERVER ERRORS (500) - Errores internos
    // ========================================

    /** Error interno del servidor */
    public const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';

    /** Error de base de datos */
    public const DATABASE_ERROR = 'DATABASE_ERROR';

    /** Servicio externo no disponible */
    public const SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';

    // ========================================
    // GRAPHQL SPECIFIC - Errores de GraphQL
    // ========================================

    /** Query o mutation inválida */
    public const GRAPHQL_PARSE_FAILED = 'GRAPHQL_PARSE_FAILED';

    /** Variables inválidas */
    public const GRAPHQL_VALIDATION_FAILED = 'GRAPHQL_VALIDATION_FAILED';

    /** Error al ejecutar la operación */
    public const GRAPHQL_EXECUTION_FAILED = 'GRAPHQL_EXECUTION_FAILED';

    // ========================================
    // MÉTODOS HELPER
    // ========================================

    /**
     * Obtiene descripción legible del código de error
     *
     * @param string $code
     * @return string
     */
    public static function getDescription(string $code): string
    {
        return match ($code) {
            // Authentication
            self::UNAUTHENTICATED => 'User is not authenticated',
            self::TOKEN_EXPIRED => 'Access token has expired',
            self::INVALID_TOKEN => 'Access token is invalid',
            self::INVALID_CREDENTIALS => 'Invalid email or password',
            self::EMAIL_NOT_VERIFIED => 'Email address has not been verified',
            self::INVALID_REFRESH_TOKEN => 'Refresh token is invalid or expired',
            self::ACCOUNT_SUSPENDED => 'User account is suspended',

            // Authorization
            self::FORBIDDEN => 'User does not have permission',
            self::INSUFFICIENT_ROLE => 'User does not have the required role',
            self::WRONG_COMPANY => 'User does not belong to the required company',
            self::ACTION_NOT_ALLOWED => 'Action not allowed in current state',

            // Validation
            self::VALIDATION_ERROR => 'Input validation failed',
            self::REQUIRED_FIELD => 'Required field is missing',
            self::INVALID_FORMAT => 'Field has invalid format',
            self::OUT_OF_RANGE => 'Value is out of allowed range',

            // Business Logic
            self::RESOURCE_ALREADY_EXISTS => 'Resource already exists',
            self::DUPLICATE_EMAIL => 'Email address is already registered',
            self::DUPLICATE_CODE => 'Code is already in use',
            self::CONFLICT => 'Conflict with current resource state',
            self::INVALID_STATE => 'Operation not allowed in current state',

            // Not Found
            self::NOT_FOUND => 'Resource not found',
            self::USER_NOT_FOUND => 'User not found',
            self::COMPANY_NOT_FOUND => 'Company not found',
            self::TICKET_NOT_FOUND => 'Ticket not found',

            // Rate Limiting
            self::RATE_LIMIT_EXCEEDED => 'Rate limit exceeded',
            self::TOO_MANY_LOGIN_ATTEMPTS => 'Too many login attempts',

            // Server Errors
            self::INTERNAL_SERVER_ERROR => 'Internal server error',
            self::DATABASE_ERROR => 'Database error occurred',
            self::SERVICE_UNAVAILABLE => 'External service unavailable',

            // GraphQL Specific
            self::GRAPHQL_PARSE_FAILED => 'GraphQL query parsing failed',
            self::GRAPHQL_VALIDATION_FAILED => 'GraphQL validation failed',
            self::GRAPHQL_EXECUTION_FAILED => 'GraphQL execution failed',

            default => 'Unknown error',
        };
    }

    /**
     * Obtiene la categoría del código de error
     *
     * Útil para agrupar errores en el frontend
     *
     * @param string $code
     * @return string
     */
    public static function getCategory(string $code): string
    {
        return match ($code) {
            self::UNAUTHENTICATED,
            self::TOKEN_EXPIRED,
            self::INVALID_TOKEN,
            self::INVALID_CREDENTIALS,
            self::EMAIL_NOT_VERIFIED,
            self::INVALID_REFRESH_TOKEN,
            self::ACCOUNT_SUSPENDED => 'authentication',

            self::FORBIDDEN,
            self::INSUFFICIENT_ROLE,
            self::WRONG_COMPANY,
            self::ACTION_NOT_ALLOWED => 'authorization',

            self::VALIDATION_ERROR,
            self::REQUIRED_FIELD,
            self::INVALID_FORMAT,
            self::OUT_OF_RANGE => 'validation',

            self::RESOURCE_ALREADY_EXISTS,
            self::DUPLICATE_EMAIL,
            self::DUPLICATE_CODE,
            self::CONFLICT,
            self::INVALID_STATE => 'business_logic',

            self::NOT_FOUND,
            self::USER_NOT_FOUND,
            self::COMPANY_NOT_FOUND,
            self::TICKET_NOT_FOUND => 'not_found',

            self::RATE_LIMIT_EXCEEDED,
            self::TOO_MANY_LOGIN_ATTEMPTS => 'rate_limit',

            self::INTERNAL_SERVER_ERROR,
            self::DATABASE_ERROR,
            self::SERVICE_UNAVAILABLE => 'server_error',

            self::GRAPHQL_PARSE_FAILED,
            self::GRAPHQL_VALIDATION_FAILED,
            self::GRAPHQL_EXECUTION_FAILED => 'graphql',

            default => 'unknown',
        };
    }

    /**
     * Verifica si el código es de cliente (4xx) o servidor (5xx)
     *
     * @param string $code
     * @return bool True si es error de cliente (4xx)
     */
    public static function isClientError(string $code): bool
    {
        $category = self::getCategory($code);

        return in_array($category, [
            'authentication',
            'authorization',
            'validation',
            'business_logic',
            'not_found',
            'rate_limit',
            'graphql',
        ]);
    }

    /**
     * Obtiene HTTP status code sugerido para el código de error
     *
     * @param string $code
     * @return int
     */
    public static function getSuggestedHttpStatus(string $code): int
    {
        $category = self::getCategory($code);

        return match ($category) {
            'authentication' => 401,
            'authorization' => 403,
            'not_found' => 404,
            'business_logic' => 409, // Conflict
            'rate_limit' => 429,
            'server_error' => 500,
            'validation', 'graphql' => 400,
            default => 500,
        };
    }
}