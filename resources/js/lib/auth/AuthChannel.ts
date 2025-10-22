/**
 * AuthChannel.ts
 *
 * Servicio Singleton para sincronizar el estado de autenticación entre pestañas.
 * Utiliza la API BroadcastChannel para una comunicación eficiente y en tiempo real,
 * con un fallback a eventos de localStorage para navegadores más antiguos.
 */

import { authLogger, AUTH_CHANNEL_EVENT_KEY } from './constants';
import type { AuthChannelEvent, AuthChannelListener, CleanupFunction } from './types';

class AuthChannelClass {
    private channel: BroadcastChannel | null = null;
    private listeners = new Set<AuthChannelListener>();
    private isUsingBroadcastChannel = false;

    constructor() {
        // Intentar usar la API nativa de BroadcastChannel si está disponible
        if ('BroadcastChannel' in window) {
            try {
                this.channel = new BroadcastChannel(AUTH_CHANNEL_EVENT_KEY);
                this.channel.onmessage = this.handleMessage.bind(this);
                this.isUsingBroadcastChannel = true;
                authLogger.info('AuthChannel initialized using BroadcastChannel API.');
            } catch (error) {
                authLogger.error('Failed to initialize BroadcastChannel, falling back to localStorage.', error);
                this.initializeLocalStorageListener();
            }
        } else {
            // Fallback a localStorage para navegadores más antiguos
            authLogger.info('BroadcastChannel not supported, falling back to localStorage events.');
            this.initializeLocalStorageListener();
        }
    }

    /**
     * Envía un evento a todas las demás pestañas.
     * @param event - El evento de autenticación a transmitir.
     */
    public broadcast(event: AuthChannelEvent): void {
        authLogger.info(`Broadcasting event: ${event.type}`);
        if (this.isUsingBroadcastChannel && this.channel) {
            this.channel.postMessage(event);
        } else {
            this.broadcastViaLocalStorage(event);
        }
    }

    /**
     * Se suscribe a los eventos recibidos de otras pestañas.
     * @param listener - La función que se ejecutará cuando se reciba un evento.
     * @returns Una función de limpieza para eliminar la suscripción.
     */
    public subscribe(listener: AuthChannelListener): CleanupFunction {
        this.listeners.add(listener);
        authLogger.info('New listener subscribed to AuthChannel.');
        return () => {
            this.listeners.delete(listener);
            authLogger.info('Listener unsubscribed from AuthChannel.');
        };
    }

    /**
     * Cierra el canal y limpia los listeners para evitar memory leaks.
     */
    public close(): void {
        if (this.channel) {
            this.channel.close();
        }
        if (!this.isUsingBroadcastChannel) {
            window.removeEventListener('storage', this.handleStorageEvent);
        }
        this.listeners.clear();
        authLogger.info('AuthChannel closed.');
    }

    /**
     * Maneja los mensajes entrantes del BroadcastChannel.
     */
    private handleMessage(event: MessageEvent<AuthChannelEvent>): void {
        authLogger.info(`Received event via BroadcastChannel: ${event.data.type}`);
        this.notifyListeners(event.data);
    }

    /**
     * Inicializa el listener para el fallback de localStorage.
     */
    private initializeLocalStorageListener(): void {
        this.handleStorageEvent = this.handleStorageEvent.bind(this); // Bind para el contexto correcto
        window.addEventListener('storage', this.handleStorageEvent);
    }

    /**
     * Maneja los eventos de storage que ocurren en otras pestañas.
     */
    private handleStorageEvent(event: StorageEvent): void {
        // Solo reaccionar a cambios en nuestra llave específica y cuando hay un valor nuevo
        if (event.key === AUTH_CHANNEL_EVENT_KEY && event.newValue) {
            try {
                const authEvent = JSON.parse(event.newValue) as AuthChannelEvent;
                authLogger.info(`Received event via localStorage: ${authEvent.type}`);
                this.notifyListeners(authEvent);
            } catch (error) {
                authLogger.error('Failed to parse AuthChannel event from localStorage.', error);
            }
        }
    }

    /**
     * Transmite un evento usando el mecanismo de localStorage.
     */
    private broadcastViaLocalStorage(event: AuthChannelEvent): void {
        try {
            // Se añade un valor aleatorio para asegurar que el `newValue` del evento `storage` siempre cambie
            const payload = JSON.stringify({ ...event, _rand: Math.random() });
            localStorage.setItem(AUTH_CHANNEL_EVENT_KEY, payload);
            // Es una buena práctica limpiar la llave después de un corto periodo
            // para no dejar basura en localStorage, aunque el evento ya se disparó.
            setTimeout(() => localStorage.removeItem(AUTH_CHANNEL_EVENT_KEY), 50);
        } catch (error) {
            authLogger.error('Failed to broadcast event via localStorage.', error);
        }
    }

    /**
     * Notifica a todos los listeners suscritos sobre un nuevo evento.
     */
    private notifyListeners(event: AuthChannelEvent): void {
        this.listeners.forEach(listener => {
            try {
                listener(event);
            } catch (error) {
                authLogger.error('Error in AuthChannel listener:', error);
            }
        });
    }
}

/**
 * Exporta una instancia única (Singleton) del AuthChannel.
 */
export const AuthChannel = new AuthChannelClass();
