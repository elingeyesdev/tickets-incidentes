/**
 * TokenManager.ts
 *
 * Singleton para la gestión centralizada del token de acceso.
 *
 * Responsabilidades:
 * - Almacenar y recuperar el token de acceso de forma segura.
 * - Programar el refresco proactivo del token antes de que expire.
 * - Notificar a los suscriptores sobre eventos de refresco o expiración.
 * - Proporcionar una única fuente de verdad para el estado del token.
 */

import { AuthChannel } from './AuthChannel';
import { authLogger, STORAGE_KEYS, TIMING } from './constants';
import { TokenRefreshService } from './TokenRefreshService';
import type { AccessToken, CleanupFunction, ExpiryCallback, RefreshCallback, TokenValidationStatus } from './types';
import { isValidJWTFormat, safeLocalStorageGet, safeLocalStorageRemove, safeLocalStorageSet, validateToken } from './utils';

class TokenManagerClass {
    private accessToken: AccessToken | null = null;
    private refreshTimer: number | null = null;

    // Sistema de suscripción para eventos
    private refreshCallbacks = new Set<RefreshCallback>();
    private expiryCallbacks = new Set<ExpiryCallback>();

    // Flag para evitar múltiples refrescos simultáneos
    private isRefreshing = false;

    constructor() {
        authLogger.info('TokenManager initializing...');
        this.loadTokenFromStorage();
    }

    /**
     * Establece un nuevo token de acceso, lo persiste y programa el próximo refresco.
     * @param token - El string del JWT.
     * @param expiresIn - La duración del token en segundos.
     */
    public setToken(token: string, expiresIn: number): void {
        if (!isValidJWTFormat(token)) {
            authLogger.error('Attempted to set an invalid JWT.');
            this.clearToken();
            return;
        }

        const now = Date.now();
        this.accessToken = {
            token,
            expiresIn,
            issuedAt: now,
            expiresAt: now + expiresIn * 1000,
        };

        authLogger.info('New access token has been set.');
        this.persistToken();
        this.scheduleRefresh();
    }

    /**
     * Obtiene el token de acceso actual si es válido.
     * @returns El string del JWT o null si no es válido o no existe.
     */
    public getAccessToken(): string | null {
        if (this.validateToken().isValid) {
            return this.accessToken?.token ?? null;
        }
        return null;
    }

    /**
     * Valida el token actual.
     * @returns El estado de validación del token.
     */
    public validateToken(): TokenValidationStatus {
        return validateToken(this.accessToken);
    }

    /**
     * Limpia el token actual del estado y del storage.
     */
    public clearToken(): void {
        if (!this.accessToken) return; // No hacer nada si ya está limpio

        authLogger.info('Clearing access token and stopping refresh timer.');
        this.accessToken = null;
        this.stopRefreshTimer();
        safeLocalStorageRemove(STORAGE_KEYS.ACCESS_TOKEN);
        safeLocalStorageRemove(STORAGE_KEYS.TOKEN_EXPIRES_AT);
        safeLocalStorageRemove(STORAGE_KEYS.TOKEN_ISSUED_AT);
    }

    /**
     * Dispara manualmente el proceso de refresco.
     * En Fase 1, solo notifica. En Fase 2, llamará al TokenRefreshService.
     */
    public async triggerRefresh(): Promise<void> {
        if (this.isRefreshing) {
            authLogger.warn('Refresh already in progress. Skipping trigger.');
            return;
        }

        authLogger.info('Refresh triggered via TokenManager.');
        this.isRefreshing = true;

        try {
            // Usar el servicio real para refrescar el token
            const result = await TokenRefreshService.refresh();

            if (result.success && this.accessToken) {
                // El TokenRefreshService ya llama a setToken, lo que actualiza el estado.
                // Aquí solo notificamos a los suscriptores del TokenManager.
                authLogger.info('Notifying TokenManager subscribers of successful refresh.');
                for (const callback of this.refreshCallbacks) {
                    await callback(this.accessToken);
                }
                // Broadcast para otras pestañas
                AuthChannel.broadcast({ type: 'TOKEN_REFRESHED', payload: { expiresIn: this.accessToken.expiresIn, timestamp: Date.now() } });
            } else {
                // Si el refresco falla después de todos los reintentos, el servicio ya habrá limpiado el token.
                // Aquí nos aseguramos de notificar a los suscriptores de la expiración.
                this.notifyExpiry();
                throw new Error(result.error?.message || 'Refresh failed');
            }
        } catch (error) {
            authLogger.error('An error occurred during the triggerRefresh process:', error);
            // Asegurarse de que el estado de refresco se reinicie incluso si hay un error inesperado
        } finally {
            this.isRefreshing = false;
        }
    }

    /**
     * Suscribe un callback para el evento de refresco de token.
     * @param callback - La función a llamar cuando el token se refresca.
     * @returns Una función para desuscribirse.
     */
    public onRefresh(callback: RefreshCallback): CleanupFunction {
        this.refreshCallbacks.add(callback);
        return () => this.refreshCallbacks.delete(callback);
    }

    /**
     * Suscribe un callback para el evento de expiración de sesión.
     * @param callback - La función a llamar cuando la sesión expira.
     * @returns Una función para desuscribirse.
     */
    public onExpiry(callback: ExpiryCallback): CleanupFunction {
        this.expiryCallbacks.add(callback);
        return () => this.expiryCallbacks.delete(callback);
    }

    // ============================================================================
    // MÉTODOS PRIVADOS
    // ============================================================================

    /**
     * Carga el token desde localStorage al inicializar.
     */
    private loadTokenFromStorage(): void {
        const token = safeLocalStorageGet(STORAGE_KEYS.ACCESS_TOKEN);
        const expiresAt = safeLocalStorageGet(STORAGE_KEYS.TOKEN_EXPIRES_AT);
        const issuedAt = safeLocalStorageGet(STORAGE_KEYS.TOKEN_ISSUED_AT);

        if (token && expiresAt && issuedAt) {
            const expiresAtNum = parseInt(expiresAt, 10);
            const issuedAtNum = parseInt(issuedAt, 10);

            this.accessToken = {
                token,
                expiresAt: expiresAtNum,
                issuedAt: issuedAtNum,
                expiresIn: Math.round((expiresAtNum - issuedAtNum) / 1000),
            };

            if (this.validateToken().isValid) {
                authLogger.info('Active session loaded from storage.');
                this.scheduleRefresh();
            } else {
                authLogger.info('Expired session found in storage. Clearing...');
                this.clearToken();
            }
        } else {
            authLogger.info('No active session found in storage.');
        }
    }

    /**
     * Guarda el token actual en localStorage.
     */
    private persistToken(): void {
        if (!this.accessToken) return;

        safeLocalStorageSet(STORAGE_KEYS.ACCESS_TOKEN, this.accessToken.token);
        safeLocalStorageSet(STORAGE_KEYS.TOKEN_EXPIRES_AT, this.accessToken.expiresAt.toString());
        safeLocalStorageSet(STORAGE_KEYS.TOKEN_ISSUED_AT, this.accessToken.issuedAt.toString());
        authLogger.info('Session persisted to localStorage.');
    }

    /**
     * Programa el próximo intento de refresco proactivo.
     */
    private scheduleRefresh(): void {
        this.stopRefreshTimer();

        if (!this.accessToken) return;

        const validation = this.validateToken();
        if (validation.isExpired) return;

        // Calculamos el tiempo hasta el punto de refresco (e.g., 80% de la vida del token)
        const lifetime = this.accessToken.expiresAt - this.accessToken.issuedAt;
        const refreshPoint = this.accessToken.issuedAt + lifetime * TIMING.TOKEN_REFRESH_BUFFER;
        const delay = refreshPoint - Date.now();

        if (delay > 0) {
            authLogger.info(`Next proactive refresh scheduled in ${Math.round(delay / 1000)}s.`);
            this.refreshTimer = setTimeout(() => {
                this.triggerRefresh();
            }, delay);
        } else {
            // Si el punto de refresco ya pasó, intentar refrescar inmediatamente
            authLogger.info('Refresh threshold already passed. Triggering refresh now.');
            this.triggerRefresh();
        }
    }

    /**
     * Detiene cualquier timer de refresco programado.
     */
    private stopRefreshTimer(): void {
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    /**
     * Notifica a los suscriptores que la sesión ha expirado.
     */
    private notifyExpiry(): void {
        authLogger.warn('Session has expired. Notifying subscribers.');
        this.clearToken();
        // Broadcast para otras pestañas
        AuthChannel.broadcast({ type: 'SESSION_EXPIRED', payload: { timestamp: Date.now() } });
        for (const callback of this.expiryCallbacks) {
            callback();
        }
    }
}

/**
 * Exporta una instancia única (Singleton) del TokenManager.
 */
export const TokenManager = new TokenManagerClass();
