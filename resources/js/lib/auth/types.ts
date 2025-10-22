/**
 * types.ts
 *
 * Definiciones de tipos TypeScript para todo el sistema de autenticación.
 * Proporciona una base sólida y segura para la manipulación de datos relacionados
 * con tokens, usuarios, configuración y eventos.
 */

// ============================================================================
// TOKEN RELATED TYPES
// ============================================================================

/**
 * Información esencial de un token de acceso.
 */
export interface AccessToken {
    token: string;
    expiresIn: number; // Duración en segundos
    issuedAt: number; // Timestamp (ms) de cuando fue emitido
    expiresAt: number; // Timestamp (ms) de cuando expira
}

/**
 * Resultado de la validación de un token.
 */
export interface TokenValidationStatus {
    isValid: boolean;
    isExpired: boolean;
    expiresInSeconds: number;
    shouldRefresh: boolean;
}

// ============================================================================
// REFRESH PROCESS TYPES
// ============================================================================

/**
 * Tipos de error que pueden ocurrir durante el proceso de refresco de token.
 */
export enum RefreshErrorType {
    NETWORK_ERROR = 'NETWORK_ERROR',
    INVALID_GRANT = 'INVALID_GRANT', // Refresh token inválido o revocado
    SERVER_ERROR = 'SERVER_ERROR',
    UNKNOWN_ERROR = 'UNKNOWN_ERROR',
}

/**
 * Objeto de error detallado para el proceso de refresco.
 */
export interface RefreshError {
    type: RefreshErrorType;
    message: string;
    retryable: boolean;
    statusCode?: number;
}

/**
 * Resultado de un intento de refresco de token.
 */
export interface RefreshResult {
    success: boolean;
    accessToken?: string;
    expiresIn?: number;
    error?: RefreshError;
    attempt: number;
}

// ============================================================================
// AUTH CHANNEL (MULTI-TAB SYNC) TYPES
// ============================================================================

/**
 * Eventos que se comunican entre pestañas del navegador.
 * Se usa un discriminated union para un manejo de tipos seguro.
 */
export type AuthChannelEvent =
    | { type: 'LOGIN'; payload: { userId: string; timestamp: number } }
    | { type: 'LOGOUT'; payload: { reason?: string; timestamp: number } }
    | { type: 'TOKEN_REFRESHED'; payload: { expiresIn: number; timestamp: number } }
    | { type: 'SESSION_EXPIRED'; payload: { timestamp: number } }
    | { type: 'HEARTBEAT'; payload: { timestamp: number } };

/**
 * Firma para los listeners del canal de autenticación.
 */
export type AuthChannelListener = (event: AuthChannelEvent) => void;

// ============================================================================
// STATE MACHINE (XSTATE) TYPES
// ============================================================================

/**
 * El contexto (estado extendido) de la máquina de estados de autenticación.
 */
export interface AuthMachineContext {
    accessToken: AccessToken | null;
    user: unknown | null; // Reemplazar con el tipo de usuario real
    error: RefreshError | null;
    retryCount: number;
    lastSelectedRole: string | null;
}

/**
 * Los posibles estados de la máquina de autenticación.
 */
export type AuthMachineState =
    | { value: 'initializing'; context: AuthMachineContext }
    | { value: 'authenticated'; context: AuthMachineContext }
    | { value: 'unauthenticated'; context: AuthMachineContext }
    | { value: 'refreshing'; context: AuthMachineContext }
    | { value: 'error'; context: AuthMachineContext }
    | { value: 'expired'; context: AuthMachineContext };

// ============================================================================
// CONFIGURATION TYPES
// ============================================================================

/**
 * Estrategia de reintentos para el refresco de token.
 */
export interface RetryStrategy {
    maxAttempts: number;
    baseDelay: number; // en ms
    factor: number;
    enableJitter: boolean;
    jitterFactor: number;
    maxDelay: number; // en ms
}

/**
 * Configuración del sistema de autenticación.
 */
export interface AuthConfig {
    tokenRefreshBuffer: number; // e.g., 0.8 para refrescar al 80% del tiempo de vida
    minRefreshInterval: number; // en ms
    retryStrategy: RetryStrategy;
    heartbeatInterval: number; // en ms
    persistence: 'indexedDB' | 'localStorage' | 'memory';
}

// ============================================================================
// GENERAL PURPOSE & CALLBACK TYPES
// ============================================================================

/**
 * Función de limpieza retornada por suscripciones para evitar memory leaks.
 */
export type CleanupFunction = () => void;

/**
 * Callback que se ejecuta cuando un token es refrescado exitosamente.
 */
export type RefreshCallback = (newAccessToken: AccessToken) => void | Promise<void>;

/**
 * Callback que se ejecuta cuando la sesión expira definitivamente.
 */
export type ExpiryCallback = () => void | Promise<void>;
