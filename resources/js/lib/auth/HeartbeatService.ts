/**
 * HeartbeatService.ts
 *
 * Servicio Singleton para realizar un "ping" periódico al backend y asegurar
 * que la sesión del usuario sigue siendo válida en el servidor.
 */

import { gql } from '@apollo/client';
import { apolloClient } from '@/lib/apollo/client';
import { authLogger, TIMING } from './constants';
import * as TokenManagerModule from './TokenManager';

// Definir el tipo de la respuesta de la query Heartbeat
interface HeartbeatQueryResponse {
    authStatus: {
        isAuthenticated: boolean;
    };
}

// Query GraphQL mínima para verificar el estado de autenticación
const HEARTBEAT_QUERY = gql`
    query Heartbeat {
        authStatus {
            isAuthenticated
        }
    }
`;

class HeartbeatServiceClass {
    private intervalId: number | null = null;
    private failedAttempts = 0;
    private readonly MAX_FAILED_ATTEMPTS = 3; // Número de pings fallidos antes de forzar logout

    /**
     * Inicia el proceso de heartbeat, ejecutando un ping a intervalos regulares.
     */
    public start(): void {
        if (this.intervalId) {
            authLogger.info('Heartbeat service is already running.');
            return;
        }

        authLogger.info(`Starting heartbeat service with an interval of ${TIMING.HEARTBEAT_INTERVAL / 1000}s.`);
        this.failedAttempts = 0;
        // Ejecutar un ping inmediato al iniciar, y luego a intervalos
        this.ping();
        this.intervalId = window.setInterval(() => this.ping(), TIMING.HEARTBEAT_INTERVAL);
    }

    /**
     * Detiene el proceso de heartbeat.
     */
    public stop(): void {
        if (this.intervalId) {
            authLogger.info('Stopping heartbeat service.');
            window.clearInterval(this.intervalId);
            this.intervalId = null;
            this.failedAttempts = 0; // Resetear intentos fallidos al detener
        }
    }

    /**
     * Realiza un único ping al backend para verificar el estado de la sesión.
     */
    private async ping(): Promise<void> {
        // No hacer ping si no hay token, el TokenManager se encargará de la lógica.
        if (!TokenManagerModule.TokenManager.getAccessToken()) {
            authLogger.warn('Heartbeat: No access token found. Stopping heartbeat.');
            this.stop();
            return;
        }

        authLogger.info('Executing heartbeat ping...');

        try {
            const response = await apolloClient.query<HeartbeatQueryResponse>({
                query: HEARTBEAT_QUERY,
                fetchPolicy: 'network-only', // Siempre ir a la red para este chequeo
            });

            if (response.data?.authStatus?.isAuthenticated) {
                // El token sigue siendo válido, resetear contador de fallos.
                this.failedAttempts = 0;
                authLogger.info('Heartbeat ping successful.');
            } else {
                // El servidor dice que no estamos autenticados, aunque tengamos un token.H
                authLogger.warn('Heartbeat ping failed: Server reports unauthenticated state.');
                this.handleFailure();
            }
        } catch (error) {
            authLogger.error('Heartbeat ping request failed:', error);
            this.handleFailure();
        }
    }

    /**
     * Maneja los fallos del ping. Si fallan varios pings seguidos, se asume que la sesión
     * es inválida y se fuerza el logout.
     */
    private handleFailure(): void {
        this.failedAttempts++;
        authLogger.warn(`Heartbeat failed. Attempts: ${this.failedAttempts}/${this.MAX_FAILED_ATTEMPTS}`);
        if (this.failedAttempts >= this.MAX_FAILED_ATTEMPTS) {
            authLogger.error(`Heartbeat failed ${this.failedAttempts} times. Forcing logout.`);
            this.stop();
            // Notificar al TokenManager que la sesión ha expirado definitivamente.
            // Esto activará la AuthMachine para transicionar a 'expired'.
            TokenManagerModule.TokenManager.notifyExpiry();
        }
    }
}

/**
 * Exporta una instancia única (Singleton) del HeartbeatService.
 */
export const HeartbeatService = new HeartbeatServiceClass();
