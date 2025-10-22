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
import { authLogger, TIMING } from './constants';
import { PersistenceService } from './PersistenceService';
import { TokenRefreshService } from './TokenRefreshService';
import type { AccessToken, CleanupFunction, ExpiryCallback, RefreshCallback, TokenValidationStatus } from './types';
import { isValidJWTFormat, validateToken } from './utils';

class TokenManagerClass {
    private accessToken: AccessToken | null = null;
    private user: unknown = null;
    private roleContexts: unknown[] = [];
    private lastSelectedRole: string | null = null;

    private refreshTimer: number | null = null;

    private refreshCallbacks = new Set<RefreshCallback>();
    private expiryCallbacks = new Set<ExpiryCallback>();

    private isRefreshing = false;

    // Promise-based signal for async initialization
    private resolveInitialization: (() => void) | null = null;
    private initializationPromise: Promise<void>;

    constructor() {
        authLogger.info('TokenManager initializing...');
        this.initializationPromise = new Promise(resolve => {
            this.resolveInitialization = resolve;
        });

        this.loadTokenFromPersistence().then(() => {
            authLogger.info('TokenManager finished async initialization.');
        });
    }

    public setToken(token: string, expiresIn: number, user: unknown, roleContexts: unknown[]): void {
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
        this.user = user;
        this.roleContexts = roleContexts;
        // Al hacer login, si solo hay un rol, lo seleccionamos por defecto.
        if (this.roleContexts.length === 1) {
            this.lastSelectedRole = (this.roleContexts[0] as { roleCode: string }).roleCode;
        }

        authLogger.info('New access token and user data have been set.');
        PersistenceService.saveState({ 
            accessToken: this.accessToken, 
            user: this.user, 
            roleContexts: this.roleContexts,
            lastSelectedRole: this.lastSelectedRole
        });
        this.scheduleRefresh();
    }

    public getAccessToken(): string | null {
        if (this.validateToken().isValid) {
            return this.accessToken?.token ?? null;
        }
        return null;
    }

    public getAccessTokenObject(): AccessToken | null {
        if (this.validateToken().isValid) {
            return this.accessToken;
        }
        return null;
    }

    public validateToken(): TokenValidationStatus {
        return validateToken(this.accessToken);
    }

    public clearToken(): void {
        if (!this.accessToken) return;

        authLogger.info('Clearing access token and stopping refresh timer.');
        this.accessToken = null;
        this.user = null;
        this.roleContexts = [];
        this.lastSelectedRole = null;
        this.stopRefreshTimer();
        PersistenceService.clearState();
    }

    public async triggerRefresh(): Promise<void> {
        if (this.isRefreshing) {
            authLogger.warn('Refresh already in progress. Skipping trigger.');
            return;
        }

        authLogger.info('Refresh triggered via TokenManager.');
        this.isRefreshing = true;

        try {
            const result = await TokenRefreshService.refresh();

            if (result.success && result.accessToken && result.expiresIn) {
                // Update the token with the new one from the refresh response
                const now = Date.now();
                this.accessToken = {
                    token: result.accessToken,
                    expiresIn: result.expiresIn,
                    issuedAt: now,
                    expiresAt: now + result.expiresIn * 1000,
                };

                // Persist the updated token (user and roleContexts remain unchanged)
                PersistenceService.saveState({
                    accessToken: this.accessToken,
                    user: this.user,
                    roleContexts: this.roleContexts,
                    lastSelectedRole: this.lastSelectedRole
                });

                authLogger.info('Notifying TokenManager subscribers of successful refresh.');
                for (const callback of this.refreshCallbacks) {
                    await callback(this.accessToken);
                }
                AuthChannel.broadcast({ type: 'TOKEN_REFRESHED', payload: { expiresIn: this.accessToken.expiresIn, timestamp: Date.now() } });

                // Reschedule the next refresh
                this.scheduleRefresh();
            } else {
                this.notifyExpiry();
                throw new Error(result.error?.message || 'Refresh failed');
            }
        } catch (error) {
            authLogger.error('An error occurred during the triggerRefresh process:', error);
        } finally {
            this.isRefreshing = false;
        }
    }

    public onRefresh(callback: RefreshCallback): CleanupFunction {
        this.refreshCallbacks.add(callback);
        return () => this.refreshCallbacks.delete(callback);
    }

    public onExpiry(callback: ExpiryCallback): CleanupFunction {
        this.expiryCallbacks.add(callback);
        return () => this.expiryCallbacks.delete(callback);
    }

    /**
     * Returns a promise that resolves when the async initialization is complete.
     */
    public onReady(): Promise<void> {
        return this.initializationPromise;
    }

    public getLastSelectedRole(): string | null {
        return this.lastSelectedRole;
    }

    public setLastSelectedRole(roleCode: string): void {
        if (this.roleContexts.some((rc: any) => rc.roleCode === roleCode)) {
            this.lastSelectedRole = roleCode;
            authLogger.info(`Last selected role updated to: ${roleCode}`);
            PersistenceService.saveState({ 
                accessToken: this.accessToken!,
                user: this.user,
                roleContexts: this.roleContexts,
                lastSelectedRole: this.lastSelectedRole
            });
        } else {
            authLogger.error(`Attempted to set an invalid role: ${roleCode}`);
        }
    }

    private async loadTokenFromPersistence(): Promise<void> {
        const persistedData = await PersistenceService.loadState();

        if (persistedData) {
            this.accessToken = persistedData.accessToken;
            this.user = persistedData.user;
            this.roleContexts = persistedData.roleContexts;
            this.lastSelectedRole = persistedData.lastSelectedRole;

            if (this.validateToken().isValid) {
                authLogger.info('Active session loaded from persistence layer.');
                this.scheduleRefresh();
            } else {
                authLogger.info('Expired session found in persistence. Clearing...');
                this.clearToken();
            }
        } else {
            authLogger.info('No active session found in persistence layer.');
        }

        // Signal that initialization is complete
        if (this.resolveInitialization) {
            this.resolveInitialization();
        }
    }

    private scheduleRefresh(): void {
        this.stopRefreshTimer();

        if (!this.accessToken) return;

        const validation = this.validateToken();
        if (validation.isExpired) return;

        const lifetime = this.accessToken.expiresAt - this.accessToken.issuedAt;
        const refreshPoint = this.accessToken.issuedAt + lifetime * TIMING.TOKEN_REFRESH_BUFFER;
        const delay = refreshPoint - Date.now();

        if (delay > 0) {
            authLogger.info(`Next proactive refresh scheduled in ${Math.round(delay / 1000)}s.`);
            this.refreshTimer = setTimeout(() => {
                this.triggerRefresh();
            }, delay);
        } else {
            authLogger.info('Refresh threshold already passed. Triggering refresh now.');
            this.triggerRefresh();
        }
    }

    private stopRefreshTimer(): void {
        if (this.refreshTimer) {
            clearTimeout(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    /**
     * Notifies subscribers that the session has expired.
     * Public method so HeartbeatService can force session expiry when needed.
     */
    public notifyExpiry(): void {
        authLogger.warn('Session has expired. Notifying subscribers.');
        this.clearToken();
        AuthChannel.broadcast({ type: 'SESSION_EXPIRED', payload: { timestamp: Date.now() } });
        for (const callback of this.expiryCallbacks) {
            callback();
        }
    }
}

export const TokenManager = new TokenManagerClass();

