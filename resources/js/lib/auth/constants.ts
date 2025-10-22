/**
 * constants.ts
 *
 * Configuración centralizada y constantes para el sistema de autenticación.
 * Modificar estos valores permite ajustar el comportamiento del sistema
 * sin tener que tocar la lógica de negocio.
 */

// ============================================================================
// STORAGE KEYS
// ============================================================================

/**
 * Prefijo para todas las llaves en localStorage para evitar colisiones.
 */
const STORAGE_PREFIX = 'helpdesk_auth';

/**
 * Llaves utilizadas en localStorage para persistir el estado de la autenticación.
 * Usar un objeto previene errores de tipeo y facilita la refactorización.
 */
export const STORAGE_KEYS = {
    ACCESS_TOKEN: `${STORAGE_PREFIX}_access_token`,
    TOKEN_EXPIRES_AT: `${STORAGE_PREFIX}_token_expires_at`,
    TOKEN_ISSUED_AT: `${STORAGE_PREFIX}_token_issued_at`,
} as const; // `as const` para hacerlo de solo lectura

/**
 * Llave para la comunicación entre pestañas usando el evento de storage.
 */
export const AUTH_CHANNEL_EVENT_KEY = `${STORAGE_PREFIX}_channel_event`;

// ============================================================================
// TIMING CONFIGURATION (in milliseconds)
// ============================================================================

export const TIMING = {
    /**
     * Factor para el refresco proactivo. 0.8 significa que el token se refrescará
     * cuando haya transcurrido el 80% de su vida útil.
     * E.g., para un token de 1 hora, se refrescará a los 48 minutos.
     */
    TOKEN_REFRESH_BUFFER: 0.8,

    /**
     * Intervalo mínimo entre intentos de refresco para prevenir spam de peticiones.
     * (60 segundos)
     */
    MIN_REFRESH_INTERVAL: 60 * 1000,

    /**
     * Intervalo para el "heartbeat" que verifica si la sesión sigue activa en el backend.
     * (5 minutos)
     */
    HEARTBEAT_INTERVAL: 5 * 60 * 1000,
} as const;

// ============================================================================
// RETRY STRATEGY CONFIGURATION
// ============================================================================

import type { RetryStrategy } from './types';

/**
 * Estrategia de reintentos por defecto para el refresco de token.
 * Utiliza un enfoque de "exponential backoff" con "jitter" para
 * gestionar fallos de red de forma robusta.
 */
export const DEFAULT_RETRY_STRATEGY: RetryStrategy = {
    /**
     * Número máximo de reintentos antes de darse por vencido.
     */
    maxAttempts: 3,

    /**
     * Delay base para el primer reintento (en ms).
     */
    baseDelay: 1000, // 1 segundo

    /**
     * Factor exponencial. Cada reintento multiplica el delay por este factor.
     * E.g., 1s, 2s, 4s, ...
     */
    factor: 2,

    /**
     * Habilita el "jitter", una variación aleatoria en el delay para evitar
     * que múltiples clientes reintenten exactamente al mismo tiempo (Thundering Herd Problem).
     */
    enableJitter: true,

    /**
     * Factor de variación para el jitter. 0.3 significa ±30%.
     */
    jitterFactor: 0.3,

    /**
     * Delay máximo absoluto para no esperar indefinidamente.
     */
    maxDelay: 30 * 1000, // 30 segundos
} as const;

// ============================================================================
// ERROR MESSAGES
// ============================================================================

/**
 * Mensajes de error estandarizados para una experiencia de usuario consistente.
 */
export const ERROR_MESSAGES = {
    [Symbol.for('NETWORK_ERROR')]: 'Error de conexión. Verifique su red e intente de nuevo.',
    [Symbol.for('INVALID_GRANT')]: 'Su sesión ha expirado. Por favor, inicie sesión de nuevo.',
    [Symbol.for('SERVER_ERROR')]: 'Ocurrió un error en el servidor. Por favor, intente más tarde.',
    [Symbol.for('UNKNOWN_ERROR')]: 'Ocurrió un error inesperado durante la autenticación.',
} as const;

// ============================================================================
// DEBUG & LOGGING
// ============================================================================

/**
 * Logger simple y condicional para debugging.
 * Solo imprime en consola si el modo debug está activado.
 */
const isDebugMode = import.meta.env.DEV;

export const authLogger = {
    info: (...args: unknown[]) => {
        if (isDebugMode) {
            console.log('%c[AUTH INFO]', 'color: #3b82f6;', ...args);
        }
    },
    warn: (...args: unknown[]) => {
        if (isDebugMode) {
            console.warn('%c[AUTH WARN]', 'color: #f59e0b;', ...args);
        }
    },
    error: (...args: unknown[]) => {
        if (isDebugMode) {
            console.error('%c[AUTH ERROR]', 'color: #ef4444;', ...args);
        }
    },
};
