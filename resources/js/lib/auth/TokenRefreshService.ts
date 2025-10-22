/**
 * TokenRefreshService.ts
 *
 * Servicio Singleton para gestionar el refresco de tokens de acceso.
 * Orquesta la comunicación con el backend, maneja reintentos y asegura
 * que solo una petición de refresco se ejecute a la vez.
 */

import { authLogger, DEFAULT_RETRY_STRATEGY, ERROR_MESSAGES } from './constants';
import { TokenManager } from './TokenManager';
import type { RefreshError, RefreshResult } from './types';
import { RefreshErrorType } from './types';
import { calculateRetryDelay, sleep } from './utils';



// Tipo para las peticiones pendientes mientras se refresca el token
type PendingRequest = {
    resolve: (value: string) => void;
    reject: (reason?: any) => void;
};

class TokenRefreshServiceClass {
    private isRefreshing = false;
    private pendingRequests: PendingRequest[] = [];

    /**
     * El método principal para iniciar el proceso de refresco de token.
     * Si ya hay un refresco en progreso, las llamadas subsecuentes se encolarán
     * y esperarán el resultado del refresco actual.
     */
    public async refresh(): Promise<RefreshResult> {
        if (this.isRefreshing) {
            authLogger.info('Refresh in progress. Queuing request.');
            const newAccessToken = await this.waitForCurrentRefresh();
            return {
                success: true,
                accessToken: newAccessToken,
                expiresIn: TokenManager.validateToken().expiresInSeconds,
                attempt: 0, // No fue un nuevo intento, se unió a uno existente
            };
        }

        this.isRefreshing = true;
        authLogger.info('Starting token refresh process...');

        const result = await this.executeRefreshWithRetry();

        if (result.success && result.accessToken && result.expiresIn) {
            authLogger.info('Token successfully refreshed.');
            // Guardar el nuevo token usando el TokenManager
            TokenManager.setToken(result.accessToken, result.expiresIn);
            // Resolver todas las peticiones encoladas con el nuevo token
            this.resolvePendingRequests(result.accessToken);
        } else {
            authLogger.error('Failed to refresh token after all attempts:', result.error);
            // Rechazar todas las peticiones encoladas
            this.rejectPendingRequests(result.error);
            // Notificar al TokenManager que la sesión ha expirado definitivamente
            TokenManager.clearToken();
        }

        this.isRefreshing = false;
        return result;
    }

    /**
     * Devuelve `true` si el proceso de refresco está activo.
     */
    public getIsRefreshing(): boolean {
        return this.isRefreshing;
    }

    /**
     * Implementa la lógica de reintentos con exponential backoff + jitter.
     */
    private async executeRefreshWithRetry(): Promise<RefreshResult> {
        for (let attempt = 0; attempt < DEFAULT_RETRY_STRATEGY.maxAttempts; attempt++) {
            const result = await this.attemptRefresh(attempt);

            if (result.success) {
                return result; // Éxito, retornar inmediatamente
            }

            // Si el error no es reintentable (ej. refresh token inválido), fallar inmediatamente
            if (result.error && !result.error.retryable) {
                authLogger.warn(`Non-retryable error encountered on attempt ${attempt + 1}. Aborting.`);
                return result;
            }

            // Si es el último intento, retornar el fallo
            if (attempt === DEFAULT_RETRY_STRATEGY.maxAttempts - 1) {
                authLogger.warn('Max refresh attempts reached.');
                return result;
            }

            // Calcular y esperar el delay para el siguiente reintento
            const delay = calculateRetryDelay(attempt);
            await sleep(delay);
        }

        // Este punto solo se alcanzaría si maxAttempts es 0, lo cual es improbable.
        return this.createErrorResult('UNKNOWN_ERROR', 'Max attempts reached', false, 0);
    }

    /**
     * Ejecuta un único intento de refresco de token al backend via REST.
     */
    private async attemptRefresh(attempt: number): Promise<RefreshResult> {
        authLogger.info(`Attempting to refresh token via REST (Attempt ${attempt + 1})...`);
        try {
            const response = await fetch('/api/auth/refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include', // Crucial para enviar la cookie httpOnly del refresh token
            });

            const result = await response.json();

            if (!response.ok) {
                const errorType = result.error === 'INVALID_REFRESH_TOKEN' ? 'INVALID_GRANT' : 'SERVER_ERROR';
                const isRetryable = errorType === 'SERVER_ERROR';
                return this.createErrorResult(errorType, result.message, isRetryable, attempt, response.status);
            }

            const { accessToken, expiresIn } = result;
            return { success: true, accessToken, expiresIn, attempt: attempt + 1 };

        } catch (error) {
            return this.createErrorResult('NETWORK_ERROR', 'Network request failed', true, attempt);
        }
    }

    /**
     * Crea una promesa que espera a que el refresco actual termine.
     */
    private waitForCurrentRefresh(): Promise<string> {
        return new Promise((resolve, reject) => {
            this.pendingRequests.push({ resolve, reject });
        });
    }

    /**
     * Resuelve las promesas de las peticiones encoladas con el nuevo token.
     */
    private resolvePendingRequests(newAccessToken: string): void {
        this.pendingRequests.forEach(({ resolve }) => resolve(newAccessToken));
        this.pendingRequests = []; // Limpiar la cola
    }

    /**
     * Rechaza las promesas de las peticiones encoladas.
     */
    private rejectPendingRequests(error: any): void {
        this.pendingRequests.forEach(({ reject }) => reject(error));
        this.pendingRequests = []; // Limpiar la cola
    }

    /**
     * Helper para crear un objeto de resultado de error estandarizado.
     */
    private createErrorResult(type: RefreshErrorType | string, message: string, retryable: boolean, attempt: number, statusCode?: number): RefreshResult {
        const errorType = Object.values(RefreshErrorType).includes(type as RefreshErrorType)
            ? (type as RefreshErrorType)
            : RefreshErrorType.UNKNOWN_ERROR;

        const finalMessage = ERROR_MESSAGES[Symbol.for(errorType)] || message;

        const error: RefreshError = { type: errorType, message: finalMessage, retryable, statusCode };
        return { success: false, error, attempt: attempt + 1 };
    }
}

/**
 * Exporta una instancia única (Singleton) del TokenRefreshService.
 */
export const TokenRefreshService = new TokenRefreshServiceClass();
