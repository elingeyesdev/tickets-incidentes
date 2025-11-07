🔐 SISTEMA JWT ENTERPRISE - GUÍA DE IMPLEMENTACIÓN

Sistema de autenticación avanzado para Laravel + Blade + Alpine.js
Features: Auto-refresh proactivo, Multi-tab sync, IndexedDB persistence, Session heartbeat, Retry logic
Versión: 1.0.0


📋 TABLA DE CONTENIDOS

Arquitectura General
TokenManager.js
AuthChannel.js
PersistenceService.js
HeartbeatService.js
index.js
Integración con Alpine.js
Flujos Completos
Casos de Uso
Testing


🏗️ ARQUITECTURA GENERAL
Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────┐
│                      APLICACIÓN BLADE                            │
│                   (Alpine.js + AdminLTE v3)                      │
└────────────────────────┬────────────────────────────────────────┘
│
│ usa
▼
┌─────────────────────────────────────────────────────────────────┐
│                    authStore.js (Alpine Store)                   │
│  - Estado global de autenticación                                │
│  - Métodos: login(), logout(), loadUser()                        │
└────────────┬──────────┬──────────┬───────────┬───────────────────┘
│          │          │           │
│ usa      │ usa      │ usa       │ usa
▼          ▼          ▼           ▼
┌──────────────┐ ┌──────────┐ ┌────────────┐ ┌──────────────┐
│ TokenManager │ │AuthChannel│ │Persistence │ │Heartbeat     │
│              │ │           │ │Service     │ │Service       │
├──────────────┤ ├──────────┤ ├────────────┤ ├──────────────┤
│• setTokens() │ │• broadcast│ │• save()    │ │• start()     │
│• getToken()  │ │• subscribe│ │• load()    │ │• stop()      │
│• refresh()   │ │           │ │• clear()   │ │• ping()      │
│• clear()     │ │           │ │            │ │              │
│• fetch()     │ │           │ │            │ │              │
└──────┬───────┘ └─────┬─────┘ └──────┬─────┘ └──────┬───────┘
│               │               │               │
│ escribe       │ broadcast     │ persiste      │ llama
▼               ▼               ▼               ▼
┌─────────────┐ ┌──────────────┐ ┌──────────┐ ┌──────────────┐
│localStorage │ │BroadcastChnl │ │IndexedDB │ │API Backend   │
│             │ │o localStorage│ │          │ │              │
└─────────────┘ └──────────────┘ └──────────┘ └──────────────┘
Responsabilidades
ComponenteResponsabilidad PrincipalSecundariasTokenManagerStorage + Auto-refresh + RetryObserver pattern, Validation, Fetch wrapperAuthChannelMulti-tab synchronizationEvent broadcasting, BroadcastChannel/localStoragePersistenceServiceIndexedDB persistenceFallback a localStorage/memory, TTLHeartbeatServiceSession keepaliveBackend ping, Inactivity detectionauthStoreEstado global AlpineOrquestación de componentes

📦 TokenManager.js - Núcleo del Sistema
Responsabilidades

Storage: Guardar/leer access token de localStorage
Auto-refresh: Renovar token al 80% del tiempo de vida
Retry logic: Reintentar refresh con exponential backoff
Observer pattern: Callbacks para eventos (onRefresh, onExpiry)
Fetch wrapper: Interceptor para requests autenticados

Estructura del Código
// resources/js/lib/auth/TokenManager.js

/**
* TokenManager - Sistema de gestión de tokens JWT
*
* Características:
* - Storage en localStorage
* - Auto-refresh proactivo (80% del TTL)
* - Retry con exponential backoff + jitter
* - Observer pattern (callbacks)
* - Fetch wrapper con auto-refresh en 401
*
* @class TokenManager
* @singleton
  */
  class TokenManager {
  constructor() {
  // ==================== CONFIG ====================
  this.KEYS = {
  ACCESS_TOKEN: 'helpdesk_access_token',
  EXPIRY: 'helpdesk_token_expiry',
  ISSUED_AT: 'helpdesk_token_issued_at',
  };

       this.CONFIG = {
           REFRESH_BUFFER: 0.8,        // Refresh al 80% del TTL
           MAX_RETRIES: 3,             // Máximo 3 reintentos
           BASE_DELAY: 1000,           // Delay base: 1s
           JITTER_FACTOR: 0.3,         // Jitter: ±30%
       };

       // ==================== STATE ====================
       this.refreshTimer = null;       // Timer de auto-refresh
       this.isRefreshing = false;      // Flag de refresh en progreso
       this.pendingRequests = [];      // Cola de requests pendientes

       // ==================== OBSERVERS ====================
       this.onRefreshCallbacks = new Set();
       this.onExpiryCallbacks = new Set();

       // ==================== STATS ====================
       this.stats = {
           refreshes: 0,
           failures: 0,
           lastRefresh: null,
       };

       // ==================== INIT ====================
       this.init();
  }

  // ==================== INITIALIZATION ====================

  /**
    * Inicializar - Detectar sesión existente
    * Si hay token válido, programar auto-refresh
      */
      init() {
      const token = this.getAccessToken();

      if (token) {
      const expiry = localStorage.getItem(this.KEYS.EXPIRY);

           if (expiry) {
               const expiresIn = (parseInt(expiry) - Date.now()) / 1000;
               
               if (expiresIn > 0) {
                   this.scheduleRefresh(expiresIn);
                   this.log('Session detected, auto-refresh scheduled', {
                       expiresIn: Math.round(expiresIn) + 's'
                   });
               }
           }
      }
      }

  // ==================== STORAGE ====================

  /**
    * Guardar tokens después de login/refresh
    *
    * @param {string} accessToken - JWT access token
    * @param {number} expiresIn - TTL en segundos (default: 3600)
      */
      setTokens(accessToken, expiresIn = 3600) {
      // Validar formato JWT
      if (!this.isValidJWT(accessToken)) {
      throw new Error('Invalid JWT format');
      }

      // Guardar token
      localStorage.setItem(this.KEYS.ACCESS_TOKEN, accessToken);

      // Calcular timestamps
      const now = Date.now();
      const expiryTime = now + (expiresIn * 1000);

      localStorage.setItem(this.KEYS.EXPIRY, expiryTime.toString());
      localStorage.setItem(this.KEYS.ISSUED_AT, now.toString());

      // Programar auto-refresh
      this.scheduleRefresh(expiresIn);

      this.log('Tokens saved', {
      expiresIn: expiresIn + 's',
      refreshAt: Math.round(expiresIn * this.CONFIG.REFRESH_BUFFER) + 's'
      });
      }

  /**
    * Obtener access token actual
    * Valida que no haya expirado
    *
    * @returns {string|null} Access token o null si expiró
      */
      getAccessToken() {
      const token = localStorage.getItem(this.KEYS.ACCESS_TOKEN);
      const expiry = localStorage.getItem(this.KEYS.EXPIRY);

      if (!token || !expiry) {
      return null;
      }

      // Verificar no expirado
      if (Date.now() >= parseInt(expiry)) {
      this.warn('Token expired');
      this.clearTokens();
      return null;
      }

      return token;
      }

  /**
    * Limpiar todos los tokens (logout)
      */
      clearTokens() {
      localStorage.removeItem(this.KEYS.ACCESS_TOKEN);
      localStorage.removeItem(this.KEYS.EXPIRY);
      localStorage.removeItem(this.KEYS.ISSUED_AT);

      // Cancelar timer
      if (this.refreshTimer) {
      clearTimeout(this.refreshTimer);
      this.refreshTimer = null;
      }

      this.log('Tokens cleared');
      }

  // ==================== AUTO-REFRESH ====================

  /**
    * Programar refresh automático
    * Se ejecuta al 80% del tiempo de vida del token
    *
    * @param {number} expiresIn - TTL en segundos
      */
      scheduleRefresh(expiresIn) {
      // Cancelar timer anterior
      if (this.refreshTimer) {
      clearTimeout(this.refreshTimer);
      }

      // Calcular delay (80% del TTL)
      const delay = expiresIn * this.CONFIG.REFRESH_BUFFER * 1000;

      // Programar
      this.refreshTimer = setTimeout(() => {
      this.log('Auto-refresh triggered');
      this.refresh();
      }, delay);
      }

  /**
    * Refresh token con retry automático
    * Exponential backoff + jitter
    *
    * @param {number} attempt - Intento actual (1-indexed)
    * @returns {Promise<string>} Nuevo access token
      */
      async refresh(attempt = 1) {
      // Si ya hay refresh en progreso, esperar
      if (this.isRefreshing) {
      return new Promise((resolve, reject) => {
      this.pendingRequests.push({ resolve, reject });
      });
      }

      this.isRefreshing = true;

      try {
      // ========== REQUEST ==========
      const response = await fetch('/api/auth/refresh', {
      method: 'POST',
      credentials: 'include', // Envía HttpOnly cookie
      headers: {
      'Content-Type': 'application/json',
      }
      });

           if (!response.ok) {
               throw new Error(`HTTP ${response.status}`);
           }

           const data = await response.json();

           // ========== SUCCESS ==========
           // Guardar nuevo token
           this.setTokens(data.data.accessToken, data.data.expiresIn);

           // Resolver requests pendientes
           this.pendingRequests.forEach(req => {
               req.resolve(data.data.accessToken);
           });
           this.pendingRequests = [];

           // Stats
           this.stats.refreshes++;
           this.stats.lastRefresh = Date.now();

           // Notificar observers
           this.notifyRefresh(data.data.accessToken);

           this.log('Refresh successful', {
               attempt: attempt,
               newExpiry: data.data.expiresIn + 's'
           });

           return data.data.accessToken;

      } catch (error) {
      this.error('Refresh failed', { attempt, error: error.message });

           // ========== RETRY ==========
           if (attempt < this.CONFIG.MAX_RETRIES) {
               const delay = this.calculateRetryDelay(attempt);
               
               this.log(`Retrying in ${delay}ms`, {
                   attempt: attempt + 1,
                   maxRetries: this.CONFIG.MAX_RETRIES
               });

               await this.sleep(delay);
               return this.refresh(attempt + 1);
           }

           // ========== FAILURE ==========
           // Falló después de todos los reintentos
           this.stats.failures++;
           this.clearTokens();
           this.notifyExpiry();

           // Rechazar requests pendientes
           this.pendingRequests.forEach(req => {
               req.reject(error);
           });
           this.pendingRequests = [];

           throw error;

      } finally {
      this.isRefreshing = false;
      }
      }

  /**
    * Calcular delay de retry con exponential backoff + jitter
    * Formula: delay = baseDelay * (2 ^ (attempt - 1)) ± jitter
    *
    * @param {number} attempt - Número de intento (1-indexed)
    * @returns {number} Delay en milisegundos
    *
    * @example
    * attempt 1: 1000ms ± 300ms = 700-1300ms
    * attempt 2: 2000ms ± 600ms = 1400-2600ms
    * attempt 3: 4000ms ± 1200ms = 2800-5200ms
      */
      calculateRetryDelay(attempt) {
      // Base: 1s, 2s, 4s
      let delay = this.CONFIG.BASE_DELAY * Math.pow(2, attempt - 1);

      // Jitter (±30%)
      const jitterAmount = delay * this.CONFIG.JITTER_FACTOR;
      const jitter = (Math.random() * 2 - 1) * jitterAmount;
      delay += jitter;

      return Math.round(delay);
      }

  // ==================== FETCH WRAPPER ====================

  /**
    * Fetch con token automático y auto-refresh en 401
    *
    * @param {string} url - URL del endpoint
    * @param {object} options - Fetch options
    * @returns {Promise<Response>}
    *
    * @example
    * const response = await tokenManager.fetch('/api/tickets');
    * const data = await response.json();
      */
      async fetch(url, options = {}) {
      const token = this.getAccessToken();

      if (!token) {
      window.location.href = '/login';
      throw new Error('No token available');
      }

      // Request con token
      const response = await fetch(url, {
      ...options,
      headers: {
      ...options.headers,
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
      }
      });

      // Si 401, intentar refresh
      if (response.status === 401) {
      this.log('401 detected, refreshing token...');

           try {
               await this.refresh();
               
               // Reintentar request
               return this.fetch(url, options);
               
           } catch (error) {
               this.error('Refresh failed after 401', error);
               window.location.href = '/login';
               throw error;
           }
      }

      return response;
      }

  // ==================== OBSERVER PATTERN ====================

  /**
    * Registrar callback para evento de refresh
    *
    * @param {function} callback - Callback(newToken)
    * @returns {function} Cleanup function
    *
    * @example
    * const cleanup = tokenManager.onRefresh((newToken) => {
    *   console.log('Token refreshed:', newToken);
    * });
    *
    * // Cleanup al desmontar componente
    * cleanup();
      */
      onRefresh(callback) {
      this.onRefreshCallbacks.add(callback);
      return () => this.onRefreshCallbacks.delete(callback);
      }

  /**
    * Registrar callback para evento de expiración
    *
    * @param {function} callback - Callback()
    * @returns {function} Cleanup function
      */
      onExpiry(callback) {
      this.onExpiryCallbacks.add(callback);
      return () => this.onExpiryCallbacks.delete(callback);
      }

  /**
    * Notificar refresh a observers
      */
      notifyRefresh(newToken) {
      this.onRefreshCallbacks.forEach(cb => {
      try {
      cb(newToken);
      } catch (error) {
      this.error('Observer error (onRefresh)', error);
      }
      });
      }

  /**
    * Notificar expiración a observers
      */
      notifyExpiry() {
      this.onExpiryCallbacks.forEach(cb => {
      try {
      cb();
      } catch (error) {
      this.error('Observer error (onExpiry)', error);
      }
      });
      }

  // ==================== HELPERS ====================

  /**
    * Validar formato JWT
    * @param {string} token
    * @returns {boolean}
      */
      isValidJWT(token) {
      if (!token || typeof token !== 'string') return false;
      const parts = token.split('.');
      return parts.length === 3;
      }

  /**
    * Sleep async
    * @param {number} ms
    * @returns {Promise<void>}
      */
      sleep(ms) {
      return new Promise(resolve => setTimeout(resolve, ms));
      }

  /**
    * Obtener estadísticas
    * @returns {object}
      */
      getStats() {
      return {
      refreshes: this.stats.refreshes,
      failures: this.stats.failures,
      successRate: this.stats.refreshes > 0
      ? ((this.stats.refreshes / (this.stats.refreshes + this.stats.failures)) * 100).toFixed(2) + '%'
      : 'N/A',
      lastRefresh: this.stats.lastRefresh
      ? new Date(this.stats.lastRefresh).toLocaleString()
      : 'Never'
      };
      }

  // ==================== LOGGING ====================

  log(message, data = {}) {
  console.log(`[TokenManager] ${message}`, data);
  }

  warn(message, data = {}) {
  console.warn(`[TokenManager] ${message}`, data);
  }

  error(message, error) {
  console.error(`[TokenManager] ${message}`, error);
  }
  }

// ==================== SINGLETON ====================
export const tokenManager = new TokenManager();
📡 AuthChannel.js - Sincronización Multi-Tab
Responsabilidades

Multi-tab sync: Sincronizar estado de auth entre pestañas
Event broadcasting: Enviar eventos (LOGIN, LOGOUT, TOKEN_REFRESHED)
BroadcastChannel API: Usar API moderna cuando esté disponible
localStorage fallback: Fallback automático para navegadores antiguos

Estructura del Código

// resources/js/lib/auth/AuthChannel.js

/**
* AuthChannel - Sistema de sincronización multi-tab
*
* Características:
* - BroadcastChannel API (navegadores modernos)
* - Fallback a localStorage events (IE11+)
* - Event system tipado
* - Suscripción con cleanup functions
*
* Eventos soportados:
* - LOGIN: Usuario inició sesión
* - LOGOUT: Usuario cerró sesión
* - TOKEN_REFRESHED: Token renovado
* - SESSION_EXPIRED: Sesión expirada
*
* @class AuthChannel
* @singleton
  */
  class AuthChannel {
  constructor() {
  // ==================== CONFIG ====================
  this.CHANNEL_NAME = 'helpdesk_auth';
  this.STORAGE_KEY = 'helpdesk_auth_event';

       // ==================== STATE ====================
       this.channel = null;
       this.listeners = new Set();
       this.usingBroadcastChannel = false;

       // ==================== INIT ====================
       this.init();
  }

  // ==================== INITIALIZATION ====================

  /**
    * Inicializar canal
    * Intenta BroadcastChannel primero, fallback a localStorage
      */
      init() {
      // Intentar BroadcastChannel
      if ('BroadcastChannel' in window) {
      try {
      this.channel = new BroadcastChannel(this.CHANNEL_NAME);
      this.usingBroadcastChannel = true;

               // Listener de mensajes
               this.channel.onmessage = (event) => {
                   this.handleEvent(event.data);
               };

               this.log('Using BroadcastChannel API');
           } catch (error) {
               this.warn('BroadcastChannel failed, falling back to localStorage', error);
               this.setupLocalStorageFallback();
           }
      } else {
      this.setupLocalStorageFallback();
      }
      }

  /**
    * Setup fallback a localStorage events
    * Los eventos 'storage' solo se disparan en OTRAS tabs
      */
      setupLocalStorageFallback() {
      this.usingBroadcastChannel = false;

      window.addEventListener('storage', (event) => {
      if (event.key === this.STORAGE_KEY && event.newValue) {
      try {
      const data = JSON.parse(event.newValue);
      this.handleEvent(data);
      } catch (error) {
      this.error('Parse error', error);
      }
      }
      });

      this.log('Using localStorage fallback');
      }

  // ==================== BROADCASTING ====================

  /**
    * Enviar evento a OTRAS tabs
    * No se envía a la tab actual
    *
    * @param {object} event - Evento a enviar
    * @param {string} event.type - Tipo de evento
    * @param {object} event.payload - Datos del evento
    *
    * @example
    * authChannel.broadcast({
    *   type: 'LOGIN',
    *   payload: { userId: '123' }
    * });
      */
      broadcast(event) {
      // Agregar metadata
      const payload = {
      ...event,
      timestamp: Date.now(),
      _random: Math.random(), // Forzar cambio en localStorage
      };

      if (this.usingBroadcastChannel && this.channel) {
      // BroadcastChannel
      this.channel.postMessage(payload);
      } else {
      // localStorage fallback
      localStorage.setItem(this.STORAGE_KEY, JSON.stringify(payload));

           // Limpiar después de 100ms
           setTimeout(() => {
               localStorage.removeItem(this.STORAGE_KEY);
           }, 100);
      }

      this.log('Event broadcasted', { type: event.type });
      }

  // ==================== SUBSCRIPTION ====================

  /**
    * Suscribirse a eventos
    *
    * @param {function} listener - Callback(event)
    * @returns {function} Cleanup function
    *
    * @example
    * const cleanup = authChannel.subscribe((event) => {
    *   switch (event.type) {
    *     case 'LOGIN':
    *       console.log('User logged in');
    *       break;
    *     case 'LOGOUT':
    *       window.location.href = '/login';
    *       break;
    *   }
    * });
    *
    * // Cleanup al desmontar
    * cleanup();
      */
      subscribe(listener) {
      this.listeners.add(listener);

      // Retornar cleanup function
      return () => this.listeners.delete(listener);
      }

  /**
    * Manejar evento recibido
    * Notifica a todos los listeners
      */
      handleEvent(event) {
      this.log('Event received', { type: event.type });

      this.listeners.forEach(listener => {
      try {
      listener(event);
      } catch (error) {
      this.error('Listener error', error);
      }
      });
      }

  // ==================== LIFECYCLE ====================

  /**
    * Destruir canal
    * Limpiar recursos
      */
      destroy() {
      if (this.channel) {
      this.channel.close();
      this.channel = null;
      }

      this.listeners.clear();
      this.log('Destroyed');
      }

  // ==================== HELPERS ====================

  /**
    * Obtener info de debug
      */
      getDebugInfo() {
      return {
      backend: this.usingBroadcastChannel ? 'BroadcastChannel' : 'localStorage',
      listeners: this.listeners.size,
      supported: 'BroadcastChannel' in window,
      };
      }

  // ==================== LOGGING ====================

  log(message, data = {}) {
  console.log(`[AuthChannel] ${message}`, data);
  }

  warn(message, data = {}) {
  console.warn(`[AuthChannel] ${message}`, data);
  }

  error(message, error) {
  console.error(`[AuthChannel] ${message}`, error);
  }
  }

// ==================== SINGLETON ====================
export const authChannel = new AuthChannel();



💓 HeartbeatService.js - Session Heartbeat
Responsabilidades

Session keepalive: Ping periódico al backend
Inactivity detection: Detectar sesión inactiva
Auto logout: Cerrar sesión después de 3 fallos consecutivos

Estructura del Código
// resources/js/lib/auth/HeartbeatService.js

/**
* HeartbeatService - Session heartbeat
*
* Características:
* - Ping periódico al backend (default: 5 minutos)
* - Detección de inactividad
* - Auto logout después de 3 fallos
*
* @class HeartbeatService
* @singleton
  */
  class HeartbeatService {
  constructor() {
  // ==================== CONFIG ====================
  this.INTERVAL = 5 * 60 * 1000;  // 5 minutos
  this.MAX_FAILURES = 3;

       // ==================== STATE ====================
       this.intervalId = null;
       this.failedAttempts = 0;
       this.lastPing = null;

       // ==================== STATS ====================
       this.stats = {
           totalPings: 0,
           successfulPings: 0,
           failedPings: 0,
       };
  }

  // ==================== LIFECYCLE ====================

  /**
    * Iniciar heartbeat
      */
      start() {
      if (this.intervalId) {
      this.log('Already running');
      return;
      }

      // Primer ping inmediato
      this.ping();

      // Ping periódico
      this.intervalId = setInterval(() => {
      this.ping();
      }, this.INTERVAL);

      this.log('Started', { interval: this.INTERVAL / 1000 + 's' });
      }

  /**
    * Detener heartbeat
      */
      stop() {
      if (this.intervalId) {
      clearInterval(this.intervalId);
      this.intervalId = null;
      this.log('Stopped');
      }
      }

  // ==================== PING ====================

  /**
    * Ping al backend
      */
      async ping() {
      this.stats.totalPings++;

      try {
      // Obtener token
      const token = tokenManager.getAccessToken();

           if (!token) {
               this.warn('No token available');
               return;
           }

           // Request
           const response = await fetch('/api/auth/status', {
               method: 'GET',
               headers: {
                   'Authorization': `Bearer ${token}`,
               }
           });

           if (response.ok) {
               // Success
               this.failedAttempts = 0;
               this.lastPing = Date.now();
               this.stats.successfulPings++;

               this.log('Ping successful');
           } else {
               // Error
               this.handleFailure();
           }

      } catch (error) {
      this.error('Ping failed', error);
      this.handleFailure();
      }
      }

  /**
    * Manejar fallo de ping
      */
      handleFailure() {
      this.failedAttempts++;
      this.stats.failedPings++;

      this.warn('Ping failed', {
      attempt: this.failedAttempts,
      max: this.MAX_FAILURES
      });

      // Si alcanzó el máximo, logout
      if (this.failedAttempts >= this.MAX_FAILURES) {
      this.onSessionInactive();
      }
      }

  /**
    * Callback de sesión inactiva
      */
      onSessionInactive() {
      this.error('Session inactive, logging out');

      // Limpiar tokens
      tokenManager.clearTokens();

      // Detener heartbeat
      this.stop();

      // Redirigir a login
      window.location.href = '/login?reason=inactive';
      }

  // ==================== HELPERS ====================

  /**
    * Obtener estadísticas
      */
      getStats() {
      return {
      ...this.stats,
      successRate: this.stats.totalPings > 0
      ? ((this.stats.successfulPings / this.stats.totalPings) * 100).toFixed(2) + '%'
      : 'N/A',
      lastPing: this.lastPing
      ? new Date(this.lastPing).toLocaleString()
      : 'Never',
      isRunning: this.intervalId !== null,
      };
      }

  // ==================== LOGGING ====================

  log(message, data = {}) {
  console.log(`[HeartbeatService] ${message}`, data);
  }

  warn(message, data = {}) {
  console.warn(`[HeartbeatService] ${message}`, data);
  }

  error(message, error) {
  console.error(`[HeartbeatService] ${message}`, error);
  }
  }

// ==================== SINGLETON ====================
export const heartbeatService = new HeartbeatService();


index.js - Export Unificado

// resources/js/lib/auth/index.js

/**
* Sistema JWT Enterprise
* Export unificado de todos los componentes
  */

export { tokenManager } from './TokenManager.js';
export { authChannel } from './AuthChannel.js';
export { persistenceService } from './PersistenceService.js';
export { heartbeatService } from './HeartbeatService.js';

// Re-export como objeto para importación alternativa
export default {
tokenManager,
authChannel,
persistenceService,
heartbeatService,
};



🎨 Integración con Alpine.js
authStore.js


// resources/js/alpine/stores/authStore.js

import {
tokenManager,
authChannel,
persistenceService,
heartbeatService
} from '../../lib/auth/index.js';

/**
* Alpine Store - Estado global de autenticación
  */
  export default {
  // ==================== STATE ====================
  user: null,
  loading: true,
  error: null,
  isAuthenticated: false,

  // ==================== INIT ====================
  async init() {
  console.log('[AuthStore] Initializing...');

       // Intentar restaurar sesión de IndexedDB
       await this.restoreSession();

       // Cargar usuario si hay token
       if (tokenManager.getAccessToken()) {
           await this.loadUser();
       } else {
           this.loading = false;
       }

       // Suscribirse a eventos
       this.setupListeners();

       console.log('[AuthStore] Initialized');
  },

  // ==================== SESSION RESTORATION ====================

  async restoreSession() {
  try {
  const persisted = await persistenceService.loadAuthState();

           if (persisted && persisted.accessToken) {
               // Restaurar a localStorage
               const expiresIn = (persisted.expiresAt - Date.now()) / 1000;
               
               if (expiresIn > 0) {
                   tokenManager.setTokens(persisted.accessToken, expiresIn);
                   console.log('[AuthStore] Session restored from IndexedDB');
               }
           }
       } catch (error) {
           console.error('[AuthStore] Restore session failed:', error);
       }
  },

  // ==================== USER ====================

  async loadUser() {
  this.loading = true;

       try {
           const response = await tokenManager.fetch('/api/auth/status');
           const data = await response.json();

           if (data.data.isAuthenticated) {
               this.user = data.data.user;
               this.isAuthenticated = true;

               // Iniciar heartbeat
               heartbeatService.start();

               // Persistir en IndexedDB
               const expiry = localStorage.getItem(tokenManager.KEYS.EXPIRY);
               if (expiry) {
                   await persistenceService.saveAuthState(
                       tokenManager.getAccessToken(),
                       parseInt(expiry)
                   );
               }
           }
       } catch (error) {
           console.error('[AuthStore] Load user failed:', error);
           this.error = error.message;
       } finally {
           this.loading = false;
       }
  },

  // ==================== AUTH ACTIONS ====================

  async login(email, password) {
  this.loading = true;
  this.error = null;

       try {
           const response = await fetch('/api/auth/login', {
               method: 'POST',
               headers: { 'Content-Type': 'application/json' },
               body: JSON.stringify({ email, password })
           });

           if (!response.ok) {
               const error = await response.json();
               throw new Error(error.message || 'Login failed');
           }

           const data = await response.json();

           // Guardar tokens
           tokenManager.setTokens(data.data.accessToken, data.data.expiresIn);

           // Cargar usuario
           this.user = data.data.user;
           this.isAuthenticated = true;

           // Broadcast
           authChannel.broadcast({
               type: 'LOGIN',
               payload: { userId: this.user.id }
           });

           // Heartbeat
           heartbeatService.start();

           // Persistir
           const expiry = localStorage.getItem(tokenManager.KEYS.EXPIRY);
           await persistenceService.saveAuthState(
               data.data.accessToken,
               parseInt(expiry)
           );

           // Redirigir
           const roleContext = data.data.roleContexts[0];
           window.location.href = roleContext.dashboardPath;

       } catch (error) {
           this.error = error.message;
           throw error;
       } finally {
           this.loading = false;
       }
  },

  async logout() {
  this.loading = true;

       try {
           const token = tokenManager.getAccessToken();
           if (token) {
               await fetch('/api/auth/logout', {
                   method: 'POST',
                   headers: { 'Authorization': `Bearer ${token}` }
               });
           }
       } catch (error) {
           console.error('[AuthStore] Logout error:', error);
       } finally {
           // Limpiar
           tokenManager.clearTokens();
           await persistenceService.clearAuthState();
           heartbeatService.stop();

           this.user = null;
           this.isAuthenticated = false;
           this.error = null;

           // Broadcast
           authChannel.broadcast({
               type: 'LOGOUT',
               payload: {}
           });

           // Redirigir
           window.location.href = '/login';

           this.loading = false;
       }
  },

  // ==================== EVENT LISTENERS ====================

  setupListeners() {
  // AuthChannel events
  authChannel.subscribe((event) => {
  switch (event.type) {
  case 'LOGIN':
  this.loadUser();
  break;

               case 'LOGOUT':
                   tokenManager.clearTokens();
                   persistenceService.clearAuthState();
                   this.user = null;
                   this.isAuthenticated = false;
                   window.location.href = '/login';
                   break;

               case 'TOKEN_REFRESHED':
                   console.log('[AuthStore] Token refreshed in another tab');
                   break;
           }
       });

       // TokenManager events
       tokenManager.onRefresh(async (newToken) => {
           console.log('[AuthStore] Token refreshed');
           
           // Actualizar IndexedDB
           const expiry = localStorage.getItem(tokenManager.KEYS.EXPIRY);
           await persistenceService.saveAuthState(newToken, parseInt(expiry));

           // Broadcast
           authChannel.broadcast({
               type: 'TOKEN_REFRESHED',
               payload: { timestamp: Date.now() }
           });
       });

       tokenManager.onExpiry(() => {
           console.log('[AuthStore] Token expired');
           this.logout();
       });
  }
  };
```

---

## 🔄 FLUJOS COMPLETOS

### Flujo 1: Login
```
Usuario                  Frontend                Backend              Storage
│                         │                       │                   │
│ 1. Ingresa credenciales │                       │                   │
├────────────────────────>│                       │                   │
│                         │ 2. POST /api/auth/login                  │
│                         ├──────────────────────>│                   │
│                         │                       │ 3. Valida         │
│                         │                       │    credenciales   │
│                         │ 4. {accessToken,      │                   │
│                         │     refreshToken}     │                   │
│                         │<──────────────────────┤                   │
│                         │ 5. tokenManager.setTokens()              │
│                         ├─────────────────────────────────────────>│
│                         │                       │                   │ localStorage
│                         │ 6. persistenceService.saveAuthState()    │
│                         ├─────────────────────────────────────────>│
│                         │                       │                   │ IndexedDB
│                         │ 7. authChannel.broadcast('LOGIN')        │
│                         ├──────────────────────>│                   │
│                         │                       │                   │ Otras tabs
│                         │ 8. heartbeatService.start()              │
│                         │                       │                   │
│ 9. Redirige a dashboard │                       │                   │
│<────────────────────────┤                       │                   │
```

### Flujo 2: Auto-Refresh
```
Timer                    TokenManager              Backend              Storage
│                         │                       │                   │
│ 1. Timer (80% TTL)      │                       │                   │
├────────────────────────>│                       │                   │
│                         │ 2. refresh()          │                   │
│                         │   (con retry logic)   │                   │
│                         │ 3. POST /api/auth/refresh (HttpOnly cookie)
│                         ├──────────────────────>│                   │
│                         │                       │ 4. Valida refresh │
│                         │                       │    token          │
│                         │                       │ 5. Rota token     │
│                         │ 6. {accessToken}      │                   │
│                         │<──────────────────────┤                   │
│                         │ 7. setTokens()        │                   │
│                         ├─────────────────────────────────────────>│
│                         │                       │                   │ localStorage
│                         │ 8. persistenceService.saveAuthState()    │
│                         ├─────────────────────────────────────────>│
│                         │                       │                   │ IndexedDB
│                         │ 9. notifyRefresh()    │                   │
│                         ├──> Observers          │                   │
│                         │ 10. authChannel.broadcast('TOKEN_REFRESHED')
│                         ├──────────────────────>│                   │
│                         │                       │                   │ Otras tabs
│ 11. Schedule next       │                       │                   │
│     refresh (80% TTL)   │                       │                   │
│<────────────────────────┤                       │                   │
```

### Flujo 3: Multi-tab Logout
```
Tab 1                    Tab 2                    AuthChannel          Backend
│                         │                       │                   │
│ 1. Click "Logout"       │                       │                   │
├────────>│               │                       │                   │
│         │ 2. logout()   │                       │                   │
│         ├──────────────────────────────────────>│                   │
│         │               │                       │ 3. POST /api/auth/logout
│         │               │                       ├──────────────────>│
│         │               │                       │                   │
│         │ 4. tokenManager.clearTokens()         │                   │
│         │               │                       │                   │
│         │ 5. authChannel.broadcast('LOGOUT')    │                   │
│         ├──────────────────────────────────────>│                   │
│         │               │ 6. Event received     │                   │
│         │               │<──────────────────────┤                   │
│         │               │ 7. tokenManager.clearTokens()             │
│         │               │                       │                   │
│         │               │ 8. window.location = '/login'             │
│         │               ├────────>              │                   │
│         │ 9. window.location = '/login'         │                   │
│         ├────────>      │                       │                   │
│                         │                       │                   │
│ AMBAS TABS EN LOGIN     │                       │                   │
```

### Flujo 4: Session Restoration (Reabrir Navegador)
```
Usuario                  Frontend                IndexedDB            Backend
│                         │                       │                   │
│ 1. Abre navegador       │                       │                   │
├────────────────────────>│                       │                   │
│                         │ 2. authStore.init()   │                   │
│                         │                       │                   │
│                         │ 3. persistenceService.loadAuthState()     │
│                         ├──────────────────────>│                   │
│                         │ 4. {accessToken,      │                   │
│                         │     expiresAt}        │                   │
│                         │<──────────────────────┤                   │
│                         │ 5. Verificar TTL      │                   │
│                         │    expiresAt > now()  │                   │
│                         │                       │                   │
│                         │ 6. tokenManager.setTokens()              │
│                         │    (restaura a localStorage)              │
│                         │                       │                   │
│                         │ 7. tokenManager.init()                   │
│                         │    (detecta sesión y programa refresh)    │
│                         │                       │                   │
│                         │ 8. authStore.loadUser()                  │
│                         ├─────────────────────────────────────────>│
│                         │                       │                   │
│                         │ 9. GET /api/auth/status                  │
│                         ├─────────────────────────────────────────>│
│                         │ 10. {user, isAuthenticated}              │
│                         │<─────────────────────────────────────────┤
│                         │                       │                   │
│ 11. Usuario autenticado │                       │                   │
│     Dashboard visible   │                       │                   │
│<────────────────────────┤                       │                   │





🎯 CHECKLIST DE IMPLEMENTACIÓN
Fase 1: Setup Básico

Crear estructura de carpetas /resources/js/lib/auth/
Implementar TokenManager.js
Implementar index.js
Probar storage y auto-refresh

Fase 2: Multi-Tab

Implementar AuthChannel.js
Integrar con TokenManager
Probar sincronización entre tabs

Fase 3: Persistencia

Implementar PersistenceService.js
Integrar con TokenManager
Probar restauración de sesión

Fase 4: Heartbeat

Implementar HeartbeatService.js
Integrar con authStore
Probar detección de inactividad

Fase 5: Integración Alpine

Crear authStore.js
Conectar todos los servicios
Implementar vistas Blade

Fase 6: Testing

Tests unitarios de cada componente
Tests de integración
Tests E2E
