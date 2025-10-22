/**
 * utils.ts
 *
 * Funciones de utilidad puras y reutilizables para el sistema de autenticación.
 * Estas funciones no tienen estado y son fácilmente testeables.
 */

import { authLogger, DEFAULT_RETRY_STRATEGY, TIMING } from './constants';
import type { AccessToken, RetryStrategy, TokenValidationStatus } from './types';

// ============================================================================
// TOKEN VALIDATION UTILS
// ============================================================================

/**
 * Valida un token de acceso, comprobando su expiración y si necesita ser refrescado.
 * @param token - El objeto del token de acceso a validar.
 * @returns Un objeto con el estado de validación del token.
 */
export const validateToken = (token: AccessToken | null): TokenValidationStatus => {
    if (!token || !token.expiresAt) {
        return { isValid: false, isExpired: true, expiresInSeconds: 0, shouldRefresh: false };
    }

    const now = Date.now();
    const isExpired = now >= token.expiresAt;
    const expiresInSeconds = isExpired ? 0 : Math.round((token.expiresAt - now) / 1000);

    // El token debe refrescarse si ha pasado el umbral definido en TIMING.TOKEN_REFRESH_BUFFER
    const lifetime = token.expiresAt - token.issuedAt;
    const refreshThreshold = token.issuedAt + lifetime * TIMING.TOKEN_REFRESH_BUFFER;
    const shouldRefresh = now >= refreshThreshold && !isExpired;

    return { isValid: !isExpired, isExpired, expiresInSeconds, shouldRefresh };
};

/**
 * Calcula el delay en milisegundos para programar el próximo refresco proactivo.
 * @param expiresIn - La duración del token en segundos.
 * @returns El delay en milisegundos.
 */
export const calculateRefreshDelay = (expiresIn: number): number => {
    // Multiplicamos por 1000 para convertir a milisegundos
    const delay = expiresIn * 1000 * TIMING.TOKEN_REFRESH_BUFFER;
    authLogger.info(`Programming next proactive refresh in ${Math.round(delay / 1000)} seconds.`);
    return delay;
};

// ============================================================================
// RETRY LOGIC UTILS
// ============================================================================

/**
 * Calcula el delay para el próximo reintento usando una estrategia de exponential backoff + jitter.
 * @param attempt - El número de intento actual (empezando en 0).
 * @param strategy - La configuración de la estrategia de reintentos.
 * @returns El delay en milisegundos.
 */
export const calculateRetryDelay = (
    attempt: number,
    strategy: RetryStrategy = DEFAULT_RETRY_STRATEGY
): number => {
    // Exponential backoff: delay = baseDelay * (factor ^ attempt)
    let delay = strategy.baseDelay * Math.pow(strategy.factor, attempt);

    // Aplicar jitter (variación aleatoria) para evitar el "Thundering Herd Problem"
    if (strategy.enableJitter) {
        const jitterAmount = delay * strategy.jitterFactor;
        // El jitter puede ser positivo o negativo
        const randomJitter = (Math.random() * 2 - 1) * jitterAmount;
        delay += randomJitter;
    }

    // Asegurarse de que el delay no exceda el máximo configurado
    const finalDelay = Math.min(delay, strategy.maxDelay);

    authLogger.info(`Calculating retry delay for attempt ${attempt + 1}: ${Math.round(finalDelay)}ms`);

    return finalDelay;
};

// ============================================================================
// JWT UTILS
// ============================================================================

/**
 * Verifica si un string tiene el formato básico de un JWT (xxx.yyy.zzz).
 * @param token - El string del token.
 * @returns `true` si el formato es válido.
 */
export const isValidJWTFormat = (token: string | null | undefined): token is string => {
    if (!token) return false;
    return token.split('.').length === 3;
};

/**
 * Decodifica la parte del payload de un JWT de forma segura.
 * No verifica la firma, solo decodifica de Base64Url.
 * @param token - El string del token JWT.
 * @returns El payload decodificado como un objeto, o null si falla.
 */
export const decodeJWT = <T = unknown>(token: string): T | null => {
    if (!isValidJWTFormat(token)) {
        authLogger.error('Attempted to decode an invalid JWT format.');
        return null;
    }
    try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(
            atob(base64)
                .split('')
                .map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
                .join('')
        );
        return JSON.parse(jsonPayload) as T;
    } catch (error) {
        authLogger.error('Failed to decode JWT payload:', error);
        return null;
    }
};

/**
 * Extrae el ID de usuario (sub) del payload de un JWT.
 * @param token - El string del token JWT.
 * @returns El ID de usuario o null si no se encuentra.
 */
export const extractUserIdFromJWT = (token: string): string | null => {
    const payload = decodeJWT<{ sub?: string }>(token);
    return payload?.sub ?? null;
};

// ============================================================================
// STORAGE UTILS
// ============================================================================

/**
 * Wrapper seguro para obtener un item de localStorage.
 * @param key - La llave a obtener.
 * @returns El valor como string, o null si no existe o hay un error.
 */
export const safeLocalStorageGet = (key: string): string | null => {
    try {
        return localStorage.getItem(key);
    } catch (error) {
        authLogger.error(`Failed to get item '${key}' from localStorage:`, error);
        return null;
    }
};

/**
 * Wrapper seguro para guardar un item en localStorage.
 * @param key - La llave a guardar.
 * @param value - El valor a guardar.
 * @returns `true` si se guardó correctamente, `false` si hubo un error.
 */
export const safeLocalStorageSet = (key: string, value: string): boolean => {
    try {
        localStorage.setItem(key, value);
        return true;
    } catch (error) {
        authLogger.error(`Failed to set item '${key}' in localStorage:`, error);
        return false;
    }
};

/**
 * Wrapper seguro para eliminar un item de localStorage.
 * @param key - La llave a eliminar.
 */
export const safeLocalStorageRemove = (key: string): void => {
    try {
        localStorage.removeItem(key);
    } catch (error) {
        authLogger.error(`Failed to remove item '${key}' from localStorage:`, error);
    }
};

// ============================================================================
// GENERAL UTILS
// ============================================================================

/**
 * Pausa la ejecución por un número determinado de milisegundos.
 * @param ms - El tiempo a esperar en milisegundos.
 */
export const sleep = (ms: number): Promise<void> => new Promise(resolve => setTimeout(resolve, ms));
