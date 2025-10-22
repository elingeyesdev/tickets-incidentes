# 🔐 REFACTORIZACIÓN SISTEMA DE AUTENTICACIÓN ENTERPRISE

> **Estado del Proyecto:** ⏳ FASE 0 DE 10 (0%)
> **Última Actualización:** 2025-10-15
> **Versión del Sistema:** 1.0.0
> **Opción Elegida:** C (Enterprise-grade completo)

---

## 📋 ÍNDICE

1. [Contexto del Proyecto](#contexto-del-proyecto)
2. [Diagnóstico Inicial](#diagnóstico-inicial)
3. [Roadmap Completo (10 Fases)](#roadmap-completo-10-fases)
4. [Fase 1: PENDIENTE](#fase-1-pendiente)
5. [Fase 2: PENDIENTE](#fase-2-pendiente)
6. [Fase 3: PENDIENTE](#fase-3-pendiente)
7. [Fase 4: PENDIENTE](#fase-4-pendiente)
8. [Fase 5: PENDIENTE](#fase-5-pendiente)
9. [Fase 6: PENDIENTE](#fase-6-pendiente)
10. Fase 7: PENDIENTE
11. [Fase 8: PENDIENTE](#fase-8-pendiente)
12. [Fase 9: PENDIENTE](#fase-9-pendiente)
13. [Fase 10: PENDIENTE](#fase-10-pendiente)
14. [Estructura de Archivos Actual](#estructura-de-archivos-actual)
15. [Cómo Continuar](#cómo-continuar)

---

## 🎯 CONTEXTO DEL PROYECTO

### Sistema Helpdesk Multi-Tenant

**Stack:**
- Backend: Laravel 12 + Lighthouse GraphQL 6
- Frontend: React 19 + Inertia.js + TypeScript
- Database: PostgreSQL 17
- Auth: JWT (access) + Refresh Token (HTTP-only cookie)

### Problema Original

**Síntomas:**
1. ❌ Al cerrar/reabrir navegador, sesión no se detecta (muestra Welcome a pesar de tener refresh token válido)
2. ❌ Navbar muestra estado incorrecto
3. ❌ Solo se arregla cerrando sesión manualmente
4. ❌ No hay refresh automático proactivo
5. ❌ Código duplicado en 3 lugares (AuthContext, Apollo Client, useLogin)

**Causa Raíz:**
- **Race condition** en AuthContext al cargar página
- Query `authStatus` es asíncrona (200-500ms) pero layout renderiza inmediatamente
- Datos temporales se limpian inmediatamente después de login
- Sin sincronización entre tabs
- Sin timer de refresh automático

---

## 🔍 DIAGNÓSTICO INICIAL

### Estado Original del Sistema

```
resources/js/lib/apollo/client.ts
├── TokenStorage object (localStorage básico)
├── refreshAccessToken() function (sin retry)
├── errorLink con lógica duplicada
└── Variables globales: isRefreshing, pendingRequests

resources/js/contexts/AuthContext.tsx
├── useEffect con race condition
├── getTempUserData() (se limpia inmediatamente)
└── Sin detección robusta de sesión

resources/js/Features/authentication/hooks/useLogin.ts
├── saveAuthTokens()
├── saveUserData()
└── Sin integración con sistema centralizado
```

### Problemas Identificados

| Problema | Impacto | Prioridad |
|----------|---------|-----------|
| Race condition al cargar | ⚠️ Usuario ve estado incorrecto | 🔴 CRÍTICO |
| No refresh proactivo | ⚠️ Micro-freezes cuando expira | 🔴 CRÍTICO |
| Código duplicado | ⚠️ Difícil mantener | 🟡 ALTO |
| Sin sync multi-tab | ⚠️ Logout en tab1 no afecta tab2 | 🟡 ALTO |
| Sin retry en refresh | ⚠️ Error de red = sesión perdida | 🟡 ALTO |

---

## 🗺️ ROADMAP COMPLETO (10 FASES)

### Resumen Ejecutivo

| Fase | Nombre | Estado | Tiempo | Complejidad |
|------|--------|--------|--------|-------------|
| 1 | TokenManager + Tipos | ⏳ PENDIENTE | 45 min | Media |
| 2 | TokenRefreshService | ⏳ PENDIENTE | 1 hora | Alta |
| 3 | AuthChannel (Multi-tab) | ⏳ PENDIENTE | 45 min | Media |
| 4 | State Machine (XState) | ⏳ PENDIENTE | 2 horas | Alta |
| 5 | IndexedDB Persistence | ⏳ PENDIENTE | 1.5 horas | Alta |
| 6 | Session Heartbeat | ⏳ PENDIENTE | 1 hora | Media |
| 7 | Refactor AuthContext | ⏳ PENDIENTE | 2 horas | Alta |
| 8 | Apollo Integration | ⏳ PENDIENTE | 1 hora | Media |
| 9 | Refactor Hooks | ⏳ PENDIENTE | 1 hora | Media |
| 10 | Testing + Docs | ⏳ PENDIENTE | 2 horas | Media |

**Total Estimado:** 12-16 horas
**Progreso Actual:** 0% (0/10 fases)

---

## ⏳ FASE 1: PENDIENTE

### Fundamentos - TokenManager + Tipos

**Objetivo:** Crear la base sólida de todo el sistema.

### Archivos a Crear

```
resources/js/lib/auth/
├── types.ts              (200 líneas) - Tipos TypeScript completos
├── constants.ts          (250 líneas) - Configuración centralizada
├── utils.ts              (400 líneas) - Utilidades helper
├── TokenManager.ts       (550 líneas) - Núcleo del sistema
├── index.ts              (50 líneas)  - Barrel export
└── README.md             (500 líneas) - Documentación completa
```

### Componentes a Implementar

#### 1. **types.ts**

Definiciones TypeScript para type-safety:

```typescript
// Tokens
export interface TokenInfo { accessToken, refreshToken?, tokenType, expiresIn, issuedAt }
export interface TokenValidation { isValid, isExpired, expiresInSeconds, shouldRefresh }

// Refresh
export interface RefreshResult { success, accessToken?, expiresIn?, error?, attempt }
export interface RefreshError { type, message, retryable, statusCode? }
export enum RefreshErrorType { NETWORK_ERROR, INVALID_TOKEN, EXPIRED_REFRESH_TOKEN, ... }

// Auth Channel
export type AuthChannelEvent = LOGIN | LOGOUT | TOKEN_REFRESHED | SESSION_EXPIRED | HEARTBEAT

// State Machine
export interface AuthMachineContext { accessToken, expiresIn, user, error, retryCount }
export type AuthMachineState = initializing | authenticated | refreshing | error | expired

// Config
export interface AuthConfig { tokenRefreshBuffer, retryStrategy, heartbeat, persistence, ... }
```

#### 2. **constants.ts**

Configuración centralizada:

```typescript
// Storage keys
export const STORAGE_KEYS = {
    ACCESS_TOKEN: 'helpdesk_access_token',
    TOKEN_EXPIRY: 'helpdesk_token_expiry',
    ISSUED_AT: 'helpdesk_token_issued_at',
}

// Timing
export const TIMING = {
    TOKEN_REFRESH_BUFFER: 0.8,  // Refresh al 80% del tiempo de vida
    MIN_REFRESH_INTERVAL: 60 * 1000,
    RETRY_BASE_DELAY: 1000,
    HEARTBEAT_INTERVAL: 5 * 60 * 1000,
}

// Retry config
export const RETRY_CONFIG = {
    MAX_ATTEMPTS: 3,
    ENABLE_JITTER: true,
    JITTER_FACTOR: 0.3,
}

// Error messages
export const ERROR_MESSAGES = {
    TOKEN_EXPIRED: 'Tu sesión ha expirado...',
    NETWORK_ERROR: 'Error de conexión...',
    // ...
}

// Logger
export const authLogger = {
    info: (...args) => { if (debug) console.log('[AUTH]', ...args) },
    error: (...args) => { if (debug) console.error('[AUTH ERROR]', ...args) },
}
```

#### 3. **utils.ts**

Funciones helper reutilizables:

```typescript
// Token validation
export const validateToken = (expiresAt, issuedAt): TokenValidation => { ... }
export const calculateRefreshDelay = (expiresIn): number => { ... }

// Retry logic
export const calculateRetryDelay = (attempt, strategy): number => {
    // Exponential backoff: delay = baseDelay * (factor ^ attempt)
    let delay = strategy.baseDelay * Math.pow(strategy.factor, attempt);

    // Agregar jitter (±30%)
    if (strategy.jitter) {
        const jitterAmount = delay * 0.3;
        const randomJitter = (Math.random() * 2 - 1) * jitterAmount;
        delay += randomJitter;
    }

    return Math.min(delay, strategy.maxDelay);
}

export const withRetry = async <T>(fn, strategy): Promise<T> => { ... }

// JWT helpers
export const isValidJWTFormat = (token): boolean => { ... }
export const decodeJWT = (token): any | null => { ... }
export const extractUserIdFromJWT = (token): string | null => { ... }

// Storage helpers
export const safeLocalStorageGet = (key): string | null => { ... }
export const safeLocalStorageSet = (key, value): boolean => { ... }

// Time helpers
export const sleep = (ms): Promise<void> => { ... }
export const formatTimestamp = (timestamp): string => { ... }
export const formatTimeRemaining = (expiresAt): string => { ... }
```

#### 4. **TokenManager.ts** (NÚCLEO DEL SISTEMA)

Singleton para gestión centralizada de tokens:

```typescript
class TokenManagerClass {
    // Propiedades
    private refreshTimer: NodeJS.Timeout | null = null;
    private refreshCallbacks: Set<RefreshCallback> = new Set();
    private expiryCallbacks: Set<ExpiryCallback> = new Set();
    private isRefreshing: boolean = false;

    // Métodos principales
    public setTokens(accessToken, expiresIn, skipAutoRefresh = false) {
        // 1. Validar formato JWT
        // 2. Calcular metadata de expiración
        // 3. Guardar en localStorage
        // 4. Cancelar refresh anterior
        // 5. Programar nuevo refresh automático
    }

    public getAccessToken(): string | null {
        // 1. Leer de localStorage
        // 2. Validar no expirado
        // 3. Retornar token o null
    }

    public validateCurrentToken(): TokenValidation | null {
        // Retorna estado: isValid, isExpired, shouldRefresh
    }

    public async triggerRefresh(): Promise<void> {
        // Ejecuta callbacks registrados
        // En Fase 2: integrado con TokenRefreshService
    }

    public onRefresh(callback): CleanupFunction {
        // Registra callback para cuando se renueve
    }

    public onExpiry(callback): CleanupFunction {
        // Registra callback para cuando expire
    }

    // Métodos privados
    private scheduleRefresh(expiresIn) {
        const delay = calculateRefreshDelay(expiresIn);
        this.refreshTimer = setTimeout(async () => {
            await this.triggerRefresh();
        }, delay);
    }

    private detectExistingSession() {
        // Al inicializar, detecta tokens existentes
        // y programa refresh si es válido
    }
}

export const TokenManager = new TokenManagerClass();
```

### Conceptos Clave a Implementar

1. **Singleton Pattern** - Una sola instancia global
2. **Observer Pattern** - Callbacks para eventos (onRefresh, onExpiry)
3. **Type-Safe Configuration** - Todo tipado con TypeScript
4. **Proactive Refresh** - Renovar al 80% del tiempo, no al 100%
5. **Barrel Export** - Import limpio: `import { TokenManager } from '@/lib/auth'`

### Logros Esperados de Fase 1

- ⏳ Base sólida para todo el sistema
- ⏳ 100% TypeScript strict mode
- ⏳ 0 usos de `any`
- ⏳ ~1200 líneas de código profesional
- ⏳ Documentación completa en README.md
- ⏳ Logging estructurado
- ⏳ Auto-refresh programado

---

## ⏳ FASE 2: PENDIENTE

### TokenRefreshService + Retry Strategies

**Objetivo:** Implementar refresh robusto con reintentos inteligentes.

### Archivos a Crear/Modificar

```
resources/js/lib/auth/
├── TokenRefreshService.ts  ⏳ NUEVO (500 líneas)
├── TokenManager.ts         ⏳ MODIFICADO (integrado con servicio)
└── index.ts                ⏳ MODIFICADO (exporta servicio)

resources/js/lib/apollo/
└── client.ts               ⏳ REFACTORIZADO (-150 líneas de código muerto)
```

### Componentes a Implementar

#### 1. **TokenRefreshService.ts**

Servicio profesional para refresh con retry:

```typescript
class TokenRefreshServiceClass {
    // Estado interno
    private state: ServiceState = {
        isRefreshing: false,
        lastRefreshAttempt: null,
        successfulRefreshes: 0,
        failedRefreshes: 0,
    };

    private pendingRequests: PendingRequest[] = [];

    // Método principal
    public async refresh(): Promise<RefreshResult> {
        // Si ya hay refresh en progreso, agregar a cola
        if (this.state.isRefreshing) {
            return this.waitForCurrentRefresh();
        }

        this.state.isRefreshing = true;

        // Ejecutar con retry
        const result = await this.executeRefreshWithRetry();

        this.state.isRefreshing = false;

        // Actualizar estadísticas y notificar
        if (result.success) {
            this.state.successfulRefreshes++;
            this.resolvePendingRequests(result.accessToken);
        } else {
            this.state.failedRefreshes++;
            this.rejectPendingRequests(new Error(result.error?.message));
        }

        return result;
    }

    // Retry con exponential backoff + jitter
    private async executeRefreshWithRetry(): Promise<RefreshResult> {
        for (let attempt = 0; attempt < maxAttempts; attempt++) {
            const result = await this.attemptRefresh(attempt + 1);

            if (result.success) return result;
            if (!result.error?.retryable) return result; // No reintentar
            if (attempt === maxAttempts - 1) return result; // Último intento

            // Delay con jitter
            const delay = calculateRetryDelay(attempt, strategy);
            await sleep(delay);
        }
    }

    // Request a GraphQL
    private async attemptRefresh(attempt): Promise<RefreshResult> {
        try {
            const response = await fetch('/graphql', {
                method: 'POST',
                credentials: 'include', // HTTP-only cookie
                body: JSON.stringify({ query: REFRESH_MUTATION }),
            });

            const result = await response.json();

            if (result.errors) {
                return this.handleGraphQLErrors(result.errors, attempt);
            }

            return {
                success: true,
                accessToken: result.data.refreshToken.accessToken,
                expiresIn: result.data.refreshToken.expiresIn,
                attempt,
            };
        } catch (error) {
            return {
                success: false,
                error: this.createError('NETWORK_ERROR', 'Error de red', true),
                attempt,
            };
        }
    }

    // Esperar refresh actual
    public async waitForRefresh(): Promise<string> {
        return new Promise((resolve, reject) => {
            this.pendingRequests.push({ resolve, reject, timestamp: Date.now() });
        });
    }

    // Estadísticas
    public getStats() {
        return {
            successfulRefreshes: this.state.successfulRefreshes,
            failedRefreshes: this.state.failedRefreshes,
            successRate: '...',
            pendingRequests: this.pendingRequests.length,
        };
    }
}

export const TokenRefreshService = new TokenRefreshServiceClass();
```

#### 2. **TokenManager.ts** (Integración)

Modificado para usar TokenRefreshService:

```typescript
public async triggerRefresh(): Promise<void> {
    // Lazy load para evitar circular dependency
    if (!TokenRefreshService) {
        const module = await import('./TokenRefreshService');
        TokenRefreshService = module.TokenRefreshService;
    }

    this.isRefreshing = true;

    try {
        // Usar servicio con retry automático
        const result = await TokenRefreshService.refresh();

        if (result.success) {
            // Guardar nuevo token
            this.setTokens(result.accessToken, result.expiresIn, true);

            // Ejecutar callbacks registrados
            for (const callback of this.refreshCallbacks) {
                await callback(result.accessToken, result.expiresIn);
            }
        } else {
            // Falló después de todos los reintentos
            this.clearTokens();
            this.notifyExpiry();
            throw new Error(result.error?.message || 'Refresh failed');
        }
    } finally {
        this.isRefreshing = false;
    }
}
```

#### 3. **Apollo Client** (Refactorizado)

Código a limpiar y simplificar:

```typescript
// ANTES: 100+ líneas de lógica duplicada
let isRefreshing = false;
let pendingRequests = [];
const refreshAccessToken = async () => { /* ... */ };

// AHORA: Clean & simple
const errorLink = onError(({ graphQLErrors, operation, forward }) => {
    if (err.extensions?.code === 'UNAUTHENTICATED') {
        return new Observable((observer) => {
            (async () => {
                let newToken: string;

                // Si ya hay refresh, esperar
                if (TokenRefreshService.isRefreshing()) {
                    newToken = await TokenRefreshService.waitForRefresh();
                } else {
                    // Refresh con retry automático
                    const result = await TokenRefreshService.refresh();
                    if (!result.success) throw new Error(result.error?.message);
                    newToken = result.accessToken;
                }

                // Reintentar operación
                operation.setContext({ headers: { authorization: `Bearer ${newToken}` } });
                forward(operation).subscribe(observer);
            })();
        });
    }
});
```

**Código muerto a eliminar:**
- ❌ `let isRefreshing` (duplicado)
- ❌ `let pendingRequests` (duplicado)
- ❌ `refreshAccessToken()` function (obsoleto)
- ❌ `resolvePendingRequests()` (duplicado)

**Funciones a marcar @deprecated:**
- `saveUserData()` - Remover en Fase 7
- `getTempUserData()` - Remover en Fase 7
- `TokenStorage` object - Wrapper legacy

### Conceptos Clave a Implementar

1. **Exponential Backoff** - Delay crece exponencialmente: 1s → 2s → 4s
2. **Jitter** - Variación aleatoria ±30% para evitar thundering herd
3. **Request Queueing** - 10 queries fallan → solo 1 refresh
4. **Lazy Import** - Evitar circular dependency
5. **Error Classification** - Retryable vs Non-retryable
6. **Metrics & Stats** - Tracking de successRate

### Logros Esperados de Fase 2

- ⏳ Retry automático (hasta 3 intentos)
- ⏳ Exponential backoff + jitter
- ⏳ Cola de requests pendientes
- ⏳ Manejo inteligente de errores
- ⏳ Apollo Client refactorizado (-150 líneas)
- ⏳ 0 código duplicado
- ⏳ Estadísticas detalladas

---

## ⏳ FASE 3: PENDIENTE

### AuthChannel - Sync Multi-Tab

**Objetivo:** Sincronizar estado de auth entre tabs del navegador.

### Archivos a Crear/Modificar

```
resources/js/lib/auth/
├── AuthChannel.ts          ⏳ NUEVO (320 líneas)
├── TokenManager.ts         ⏳ MODIFICADO (integrado con AuthChannel)
└── index.ts                ⏳ MODIFICADO (exporta AuthChannel)

resources/js/lib/auth/README.md  ⏳ ACTUALIZADO (documentación completa)
```

### Componentes a Implementar

#### 1. **AuthChannel.ts**

Clase singleton para comunicación entre tabs:

```typescript
class AuthChannelClass {
    // Usa BroadcastChannel si está disponible
    private channel: BroadcastChannel | null = null;
    private listeners: Set<AuthChannelListener> = new Set();
    private usingBroadcastChannel: boolean = false;

    // Métodos principales
    public broadcast(event: AuthChannelEvent): void {
        // Envía evento a otras tabs (no a la actual)
        if (this.usingBroadcastChannel && this.channel) {
            this.channel.postMessage(event);
        } else {
            this.broadcastViaLocalStorage(event);
        }
    }

    public subscribe(listener: AuthChannelListener): CleanupFunction {
        // Registra listener para recibir eventos
        this.listeners.add(listener);
        return () => this.listeners.delete(listener);
    }

    public getDebugInfo() {
        // Info de debug: canal, soporte, listeners activos
        return { /* ... */ };
    }
}
```

**Características:**
- ⏳ BroadcastChannel API para navegadores modernos
- ⏳ localStorage events como fallback automático
- ⏳ Detección automática de soporte del navegador
- ⏳ Sistema de suscripción con cleanup functions
- ⏳ Prevención de colisión de eventos con timestamps únicos

#### 2. **Eventos Tipados**

Todos los eventos estarán completamente tipados (ya definidos en types.ts):

```typescript
type AuthChannelEvent =
    | { type: 'LOGIN'; payload: { userId: string; timestamp: number } }
    | { type: 'LOGOUT'; payload: { reason?: string; timestamp: number } }
    | { type: 'TOKEN_REFRESHED'; payload: { expiresIn: number; timestamp: number } }
    | { type: 'SESSION_EXPIRED'; payload: { timestamp: number } }
    | { type: 'HEARTBEAT'; payload: { timestamp: number } };
```

#### 3. **Integración con TokenManager**

TokenManager hará broadcast automático de eventos:

```typescript
// En TokenManager.triggerRefresh() (línea 387-394)
AuthChannel.broadcast({
    type: 'TOKEN_REFRESHED',
    payload: {
        expiresIn: result.expiresIn,
        timestamp: Date.now(),
    },
});

// En TokenManager.notifyExpiry() (línea 531-538)
AuthChannel.broadcast({
    type: 'SESSION_EXPIRED',
    payload: {
        timestamp: Date.now(),
    },
});
```

**No se requerirá broadcast manual de estos eventos.**

#### 4. **Fallback localStorage**

Si BroadcastChannel no está disponible:

```typescript
private broadcastViaLocalStorage(event: AuthChannelEvent): void {
    // Agregar timestamp único para evitar colisión
    const eventWithTimestamp = {
        ...event,
        _timestamp: Date.now(),
        _random: Math.random(), // Forzar cambio
    };

    // Escribir a localStorage (dispara 'storage' event en otras tabs)
    localStorage.setItem(STORAGE_EVENT_KEY, JSON.stringify(eventWithTimestamp));

    // Limpiar después de 100ms
    setTimeout(() => {
        localStorage.removeItem(STORAGE_EVENT_KEY);
    }, 100);
}
```

**Ventaja:** Los eventos `storage` solo se disparan en OTRAS tabs, perfecto para nuestro caso de uso.

### Casos de Uso a Implementar

1. ⏳ **Logout en Tab 1** → Logout automático en Tab 2
2. ⏳ **Login en Tab 1** → Tab 2 detecta sesión activa
3. ⏳ **Token refresh** → Todas las tabs se notifican automáticamente
4. ⏳ **Session expired** → Todas las tabs reciben evento SESSION_EXPIRED

### Ejemplo de Uso

```typescript
import { AuthChannel } from '@/lib/auth';

// Suscribirse a eventos
const cleanup = AuthChannel.subscribe((event) => {
  switch (event.type) {
    case 'LOGOUT':
      TokenManager.clearTokens();
      window.location.href = '/login';
      break;
    case 'SESSION_EXPIRED':
      window.location.href = '/login?reason=expired';
      break;
    // ...
  }
});

// Cleanup al desmontar
return cleanup;
```

### Soporte de Navegadores

- ⏳ **Chrome 54+, Firefox 38+, Edge 79+, Safari 15.4+**: BroadcastChannel nativo
- ⏳ **Safari < 15.4, IE11**: localStorage fallback automático
- ⏳ **Todos los navegadores modernos**: Soporte completo

### Conceptos Clave a Implementar

1. **BroadcastChannel API** - Comunicación entre tabs sin servidor
2. **Graceful Degradation** - Fallback automático a localStorage
3. **Event-Driven Architecture** - Sistema de suscripción con observers
4. **Type-Safe Events** - Discriminated unions para eventos
5. **Cleanup Functions** - Prevención de memory leaks

### Logros Esperados de Fase 3

- ⏳ Sync multi-tab completo
- ⏳ BroadcastChannel + fallback localStorage
- ⏳ Integración automática con TokenManager
- ⏳ Event system completamente tipado
- ⏳ Soporte universal de navegadores
- ⏳ Documentación completa en README.md
- ⏳ ~320 líneas de código profesional
- ⏳ 0 memory leaks (cleanup functions)

---

## ⏳ FASE 4: PENDIENTE

### State Machine con XState

**Objetivo:** Gestionar estados de auth de forma declarativa.

### Dependencias a Instalar

```bash
npm install xstate@^5.0.0
npm install @xstate/react@^4.1.3
```

### Archivos a Crear/Modificar

```
resources/js/lib/auth/
├── AuthMachine.ts          ⏳ NUEVO (440 líneas)
└── index.ts                ⏳ MODIFICADO (exporta authMachine)

resources/js/hooks/
├── useAuthMachine.ts       ⏳ NUEVO (240 líneas)
└── index.ts                ⏳ NUEVO (barrel export)

resources/js/lib/auth/README.md  ⏳ ACTUALIZADO (documentación completa)
```

### State Machine Definition

```typescript
import { createMachine, assign } from 'xstate';

export const authMachine = createMachine({
    id: 'auth',
    initial: 'initializing',
    context: {
        accessToken: null,
        expiresIn: null,
        user: null,
        error: null,
        retryCount: 0,
    },
    states: {
        initializing: {
            on: {
                SESSION_DETECTED: 'authenticated',
                SESSION_INVALID: 'unauthenticated',
            },
        },
        unauthenticated: {
            on: {
                LOGIN: {
                    target: 'authenticated',
                    actions: 'setAuthData',
                },
            },
        },
        authenticated: {
            on: {
                TOKEN_EXPIRED: 'refreshing',
                LOGOUT: 'unauthenticated',
            },
        },
        refreshing: {
            invoke: {
                src: 'refreshToken',
                onDone: {
                    target: 'authenticated',
                    actions: 'setAuthData',
                },
                onError: {
                    target: 'error',
                    actions: 'setError',
                },
            },
        },
        error: {
            on: {
                RETRY: 'refreshing',
                LOGOUT: 'unauthenticated',
            },
        },
    },
});
```

### Hook Custom

```typescript
export const useAuthMachine = () => {
    const [state, send] = useMachine(authMachine, {
        services: {
            refreshToken: async () => {
                const result = await TokenRefreshService.refresh();
                if (!result.success) throw result.error;
                return result;
            },
        },
    });

    return {
        state: state.value,
        context: state.context,
        login: (data) => send({ type: 'LOGIN', payload: data }),
        logout: () => send({ type: 'LOGOUT' }),
        refresh: () => send({ type: 'TOKEN_EXPIRED' }),
    };
};
```

### DevTools Integration

```typescript
import { inspect } from '@xstate/inspect';

if (import.meta.env.DEV) {
    inspect({ iframe: false });
}
```

### Tiempo Estimado

⏱️ 2 horas

---

## ⏳ FASE 5: PENDIENTE

### Persistencia con IndexedDB + Fallbacks

**Objetivo:** Sistema de persistencia robusto con múltiples capas de fallback.

### Archivos a Crear/Modificar

```
resources/js/lib/auth/
├── PersistenceService.ts   ⏳ NUEVO (450 líneas)
├── TokenManager.ts         ⏳ MODIFICADO (integrado con persistencia)
└── index.ts                ⏳ MODIFICADO (exporta PersistenceService)

resources/js/lib/auth/README.md  ⏳ ACTUALIZADO (documentación completa)
```

### Componentes a Implementar

#### 1. **PersistenceService.ts**

Sistema de persistencia con 3 backends:

```typescript
class PersistenceServiceClass {
    private backend: StorageBackend;

    constructor() {
        // Auto-detect mejor backend disponible
        if (isIndexedDBAvailable()) {
            this.backend = new IndexedDBBackend();
        } else if (isLocalStorageAvailable()) {
            this.backend = new LocalStorageBackend();
        } else {
            this.backend = new InMemoryBackend();
        }
    }

    // API unificada
    async saveAuthState(state, options?) { ... }
    async loadAuthState(options?) { ... }
    async clearAuthState() { ... }
    async clearAll() { ... }
}
```

**Características:**
- ⏳ **IndexedDB Backend**: Storage principal con versionado y migraciones
- ⏳ **localStorage Backend**: Fallback automático
- ⏳ **In-Memory Backend**: Último recurso (no persiste entre recargas)
- ⏳ **TTL Support**: Expiración automática de datos obsoletos
- ⏳ **Obfuscación**: Base64 encode opcional para ofuscar datos
- ⏳ **Migraciones**: Sistema de versiones con upgrade automático

#### 2. **IndexedDBBackend**

```typescript
class IndexedDBBackend implements StorageBackend {
    private async init(): Promise<IDBDatabase> {
        const request = indexedDB.open(dbName, version);

        request.onupgradeneeded = (event) => {
            const db = request.result;
            const objectStore = db.createObjectStore(storeName, {
                keyPath: 'key',
            });

            // Índices para queries eficientes
            objectStore.createIndex('updatedAt', 'updatedAt', { unique: false });
            objectStore.createIndex('version', 'version', { unique: false });
        };
    }

    async set(key, value) { /* ... */ }
    async get(key) { /* ... */ }
    async remove(key) { /* ... */ }
    async clear() { /* ... */ }
}
```

#### 3. **localStorage Backend** (Fallback)

```typescript
class LocalStorageBackend implements StorageBackend {
    private prefix = 'helpdesk_auth_';

    async set(key, value) {
        localStorage.setItem(this.prefix + key, JSON.stringify(value));
    }

    async get(key) {
        const data = localStorage.getItem(this.prefix + key);
        return data ? JSON.parse(data) : null;
    }
}
```

#### 4. **In-Memory Backend** (Último Fallback)

```typescript
class InMemoryBackend implements StorageBackend {
    private storage = new Map<string, PersistedAuthData>();

    async set(key, value) {
        this.storage.set(key, value);
    }

    async get(key) {
        return this.storage.get(key) || null;
    }
}
```

#### 5. **Integración con TokenManager**

TokenManager persiste automáticamente en IndexedDB:

```typescript
// En TokenManager.setTokens() (línea 180)
this.persistToStorage(accessToken, expiry.expiresAt);

// En TokenManager.clearTokens() (línea 272)
this.clearPersistence();

// En TokenManager.constructor() (línea 121)
this.initPersistence(); // Restaura sesión al inicializar
```

**Flujo de persistencia:**
1. Usuario hace login → `setTokens()` guarda en localStorage + IndexedDB
2. Usuario cierra navegador
3. Usuario reabre app → `initPersistence()` detecta localStorage vacío
4. PersistenceService busca en IndexedDB → encuentra sesión
5. Restaura tokens a localStorage
6. TokenManager detecta sesión y programa refresh
7. **Usuario permanece logueado sin intervención** ⏳

#### 6. **Restauración Automática**

```typescript
private async restoreFromPersistence(): Promise<void> {
    // Si ya hay token en localStorage, no restaurar
    if (safeLocalStorageGet(STORAGE_KEYS.ACCESS_TOKEN)) {
        return;
    }

    const persisted = await PersistenceService.loadAuthState();

    if (!persisted || !persisted.accessToken) {
        return;
    }

    // Verificar TTL
    if (persisted.expiresAt && persisted.expiresAt < Date.now()) {
        await PersistenceService.clearAuthState();
        return;
    }

    // Restaurar a localStorage
    safeLocalStorageSet(STORAGE_KEYS.ACCESS_TOKEN, persisted.accessToken);
    // ... restaurar otros campos

    // Re-detectar sesión
    this.detectExistingSession();
}
```

#### 7. **TTL (Time To Live)**

```typescript
// Al cargar datos
const data = await backend.get(storageKey);

if (data.expiresAt && data.expiresAt < Date.now()) {
    // ⏰ Datos expirados, limpiar
    await this.clearAuthState();
    return null;
}

return data; // ✅ Datos válidos
```

#### 8. **Migraciones de Versión**

```typescript
private async migrate(data: PersistedAuthData): Promise<void> {
    // Detectar versión antigua
    if (data.version !== this.currentVersion) {
        authLogger.info('Migrating persisted data', {
            from: data.version,
            to: this.currentVersion,
        });

        // Aquí se pueden agregar migraciones específicas
        // Por ejemplo, agregar nuevos campos, transformar datos, etc.

        // Actualizar versión
        data.version = this.currentVersion;
        await this.backend.set(this.storageKey, data);
    }
}
```

### Conceptos Clave a Implementar

1. **Strategy Pattern** - StorageBackend interface con 3 implementaciones
2. **Auto-Detection** - Detecta automáticamente el mejor backend
3. **Graceful Degradation** - Fallback en cascada: IndexedDB → localStorage → Memory
4. **TTL Pattern** - Expiración automática con timestamps
5. **Migration Pattern** - Versiones y upgrades automáticos
6. **Lazy Loading** - PersistenceService se carga solo cuando se necesita

### Logros Esperados de Fase 5

- ⏳ IndexedDB backend con versionado completo
- ⏳ Fallback automático a localStorage
- ⏳ In-memory fallback para navegadores legacy
- ⏳ Integración transparente con TokenManager
- ⏳ Restauración automática de sesiones
- ⏳ TTL con expiración automática
- ⏳ Sistema de migraciones
- ⏳ Obfuscación opcional de datos
- ⏳ ~450 líneas de código profesional
- ⏳ Documentación completa en README.md
- ⏳ Bundle: +8KB (740KB total)

### Soporte de Navegadores

| Browser | Soporte |
|---------|---------|
| Chrome 24+ | ⏳ IndexedDB |
| Firefox 16+ | ⏳ IndexedDB |
| Safari 10+ | ⏳ IndexedDB |
| Edge 12+ | ⏳ IndexedDB |
| IE 10+ | ⏳ IndexedDB |
| IE 9 | ⏳ localStorage |
| IE 8 | ⏳ In-Memory |

**Resultado: 100% cobertura de navegadores esperada** 🎯

---

## ⏳ FASE 6: PENDIENTE

### Session Heartbeat

**Objetivo:** Ping periódico al backend para mantener sesión activa.

### Archivos a Crear

```
resources/js/lib/auth/
└── HeartbeatService.ts  (NUEVO - ~250 líneas)
```

### HeartbeatService

```typescript
class HeartbeatServiceClass {
    private intervalId: NodeJS.Timeout | null = null;
    private failedAttempts: number = 0;
    private lastPing: number | null = null;

    // Iniciar heartbeat
    public start(): void {
        if (this.intervalId) return;

        this.intervalId = setInterval(async () => {
            await this.ping();
        }, HEARTBEAT_INTERVAL); // 5 minutos
    }

    // Ping al backend
    private async ping(): Promise<void> {
        try {
            const response = await fetch('/graphql', {
                method: 'POST',
                credentials: 'include',
                body: JSON.stringify({
                    query: `query { authStatus { isAuthenticated } }`,
                }),
            });

            if (response.ok) {
                this.failedAttempts = 0;
                this.lastPing = Date.now();
            } else {
                this.failedAttempts++;
                if (this.failedAttempts >= 3) {
                    // Sesión inactiva, hacer logout
                    this.onInactiveSession();
                }
            }
        } catch (error) {
            this.failedAttempts++;
        }
    }

    // Callback de sesión inactiva
    private onInactiveSession(): void {
        TokenManager.clearTokens();
        window.location.href = '/login?reason=inactive';
    }
}
```

### Tiempo Estimado

⏱️ 1 hora

---

## ⏳ FASE 7: PENDIENTE

### Refactorizar AuthContext

**Objetivo:** Integrar state machine y todos los servicios.

### Archivos a Modificar

```
resources/js/contexts/AuthContext.tsx  (REFACTORIZAR - ~300 líneas)
```

### AuthContext Refactorizado

```typescript
export const AuthProvider: React.FC<{ children }> = ({ children }) => {
    // Usar state machine
    const { state, context, login, logout } = useAuthMachine();

    // Suscribirse a AuthChannel
    useEffect(() => {
        const unsubscribe = AuthChannel.subscribe((event) => {
            if (event.type === 'LOGOUT') {
                logout();
            }
        });
        return unsubscribe;
    }, []);

    // Iniciar heartbeat
    useEffect(() => {
        if (state === 'authenticated') {
            HeartbeatService.start();
        } else {
            HeartbeatService.stop();
        }
    }, [state]);

    return (
        <AuthContext.Provider value={{
            user: context.user,
            isAuthenticated: state === 'authenticated',
            loading: state === 'initializing',
            // ...
        }}>
            {children}
        </AuthContext.Provider>
    );
};
```

### Código a Eliminar

- ❌ `getTempUserData()` calls
- ❌ Race condition logic
- ❌ Manual state management
- ⏳ Reemplazado por state machine

### Tiempo Estimado

⏱️ 2 horas

---

## ⏳ FASE 8: PENDIENTE

### Apollo Client Integration Final

**Objetivo:** Integrar todos los servicios con Apollo.

### Archivos a Modificar

```
resources/js/lib/apollo/client.ts  (REFACTORIZAR)
```

### Cambios

1. Remover `TokenStorage` wrapper (usar TokenManager directo)
2. Remover funciones `@deprecated`
3. Integrar con AuthChannel para notificaciones

### Tiempo Estimado

⏱️ 1 hora

---

## ⏳ FASE 9: PENDIENTE

### Refactorizar Hooks de Autenticación

**Objetivo:** Actualizar hooks para usar nuevo sistema.

### Archivos a Modificar

```
resources/js/Features/authentication/hooks/
├── useLogin.ts      (REFACTORIZAR)
├── useLogout.ts     (REFACTORIZAR)
└── useRegister.ts   (REFACTORIZAR)
```

### useLogin Refactorizado

```typescript
export const useLogin = () => {
    const [login, { loading, error }] = useMutation(LOGIN_MUTATION, {
        onCompleted: (data) => {
            // Usar TokenManager directamente
            TokenManager.setTokens(data.login.accessToken, data.login.expiresIn);

            // Broadcast a otras tabs
            AuthChannel.broadcast({
                type: 'LOGIN',
                payload: { userId: data.login.user.id, timestamp: Date.now() },
            });

            // Redirigir
            window.location.href = data.login.roleContexts[0].dashboardPath;
        },
    });

    return { handleSubmit: login, loading, error };
};
```

### Tiempo Estimado

⏱️ 1 hora

---

## ⏳ FASE 10: PENDIENTE

### Testing Exhaustivo + Documentación

**Objetivo:** Validar todo el sistema y documentar.

### Tests a Crear

```
resources/js/lib/auth/__tests__/
├── TokenManager.test.ts
├── TokenRefreshService.test.ts
├── AuthChannel.test.ts
├── AuthMachine.test.ts
└── integration.test.ts
```

### Casos de Prueba

1. **Token lifecycle**
    - Login → Token guardado → Refresh programado
    - Refresh al 80% → Nuevo token guardado
    - Logout → Tokens eliminados

2. **Retry logic**
    - Error de red → Retry 3 veces
    - Refresh token expirado → No retry
    - Exponential backoff verificado

3. **Multi-tab sync**
    - Logout en tab1 → Tab2 hace logout
    - Login en tab1 → Tab2 detecta sesión

4. **State machine**
    - Transiciones correctas
    - Guards funcionan
    - Error states manejados

5. **Edge cases**
    - Cerrar navegador → Reabrir → Sesión detectada
    - 10 queries simultáneas → 1 refresh
    - Backend cambia expiresIn → Sistema se adapta

### Documentación Final

1. Actualizar `/documentacion/AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt`
2. Crear diagramas de flujo (Mermaid)
3. API reference completo
4. Troubleshooting guide

### Tiempo Estimado

⏱️ 2 horas

---

## 📁 ESTRUCTURA DE ARCHIVOS ACTUAL

### Estado Actual - Fase 0 (SIN AVANCE)

```
Helpdesk/
├── resources/js/
│   ├── lib/
│   │   ├── auth/                        ⏳ POR CREAR
│   │   │   ├── types.ts                 ⏳ FASE 1
│   │   │   ├── constants.ts             ⏳ FASE 1
│   │   │   ├── utils.ts                 ⏳ FASE 1
│   │   │   ├── TokenManager.ts          ⏳ FASE 1
│   │   │   ├── TokenRefreshService.ts   ⏳ FASE 2
│   │   │   ├── AuthChannel.ts           ⏳ FASE 3
│   │   │   ├── index.ts                 ⏳ FASE 1
│   │   │   └── README.md                ⏳ FASE 1
│   │   │
│   │   ├── apollo/
│   │   │   └── client.ts                ⏳ REFACTORIZAR FASE 2
│   │   │
│   │   └── graphql/
│   │       ├── queries/
│   │       │   └── auth.queries.ts
│   │       └── mutations/
│   │           └── auth.mutations.ts
│   │
│   ├── contexts/
│   │   └── AuthContext.tsx              ⏳ REFACTORIZAR FASE 7
│   │
│   ├── Features/
│   │   └── authentication/
│   │       └── hooks/
│   │           ├── useLogin.ts          ⏳ REFACTORIZAR FASE 9
│   │           └── useLogout.ts         ⏳ REFACTORIZAR FASE 9
│   │
│   └── app.tsx
│
├── documentacion/
│   ├── AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt
│   ├── USER MANAGEMENT FEATURE - DOCUMENTACIÓN.txt
│   ├── Modelado final de base de datos.txt
│   └── REFACTORIZACION_AUTH_SISTEMA_ENTERPRISE.md  ⏳ ESTE ARCHIVO
│
└── .cursor/
    └── rules/
        └── arquitecture-frontend.mdc
```

---

## 🚀 CÓMO COMENZAR

### Paso 1: Preparación Inicial

1. **Leer este documento completo** - Entender el plan completo
2. **Revisar estructura propuesta** - Ver cómo se organizará el código
3. **Preparar el entorno** - Limpiar carpetas y crear directorio `/lib/auth`

### Comando para Crear Estructura

```bash
# Crear directorio de auth
mkdir -p resources/js/lib/auth

# Crear archivos vacíos
touch resources/js/lib/auth/{types,constants,utils,TokenManager,index}.ts
touch resources/js/lib/auth/README.md
```

### Checklist de Inicio

Antes de comenzar con Fase 1, verificar:

- [ ] Directorio `/resources/js/lib/auth/` creado
- [ ] Archivos base creados (types.ts, constants.ts, etc.)
- [ ] TypeScript en modo strict mode
- [ ] No hay código legacy de auth sistema anterior
- [ ] Proyecto compila sin errores

### Próxima Fase: TokenManager + Tipos (Fase 1)

**Prompt sugerido para continuar:**

```
Hola! Estamos comenzando el proyecto de refactorización del sistema de autenticación.

Estado:
⏳ Fase 0: Preparación inicial (sin avance)

Próximo paso:
👉 Fase 1: TokenManager + Tipos

Por favor:
1. Leer /documentacion/REFACTORIZACION_AUTH_SISTEMA_ENTERPRISE.md (sección Fase 1)
2. Implementar types.ts, constants.ts, utils.ts y TokenManager.ts
3. Crear README.md con documentación completa
4. Verificar que TokenManager funciona como singleton
5. Probar que getAccessToken(), setTokens(), onRefresh() funcionan correctamente

Contexto completo en el documento. ¡Comenzemos!
```

---

## 📊 MÉTRICAS DEL PROYECTO

### Código a Escribir (Totales)

| Fase | Líneas Nuevas | Líneas a Eliminar | Neto |
|------|---------------|-------------------|------|
| 1 | +1,450 | 0 | +1,450 |
| 2 | +500 | -150 | +350 |
| 3 | +320 | 0 | +320 |
| 4 | +680 | 0 | +680 |
| 5 | +580 | 0 | +580 |
| **Total Estimado** | **+3,530** | **-150** | **+3,380** |

### Cobertura de Tipos Esperada

- ⏳ 100% TypeScript strict mode
- ⏳ 0 usos de `any` (excepto 1 lazy import temporal)
- ⏳ Todas las funciones tipadas

### Performance Esperado

| Operación | Tiempo |
|-----------|--------|
| `TokenManager.getAccessToken()` | < 1ms |
| `TokenManager.setTokens()` | < 5ms |
| `TokenRefreshService.refresh()` (1er intento) | ~300ms |
| Retry completo (3 intentos) | ~7s máximo |

### Bundle Size Esperado

| Componente | Tamaño (minified) |
|------------|-------------------|
| types.ts | 0 KB (compile-time) |
| constants.ts | 2 KB |
| utils.ts | 4 KB |
| TokenManager.ts | 8 KB |
| TokenRefreshService.ts | 12 KB |
| AuthChannel.ts | 6 KB |
| AuthMachine.ts + XState | 39 KB |
| PersistenceService.ts | 8 KB |
| **Total Fases 1-5** | **~79 KB** |
| **Bundle Total (app.js)** | **740 KB** |

---

## 🎓 CONCEPTOS A APRENDER

### Patterns a Implementar

1. **Singleton Pattern** - TokenManager, TokenRefreshService
2. **Observer Pattern** - Callbacks (onRefresh, onExpiry)
3. **Strategy Pattern** - RetryStrategy configurable
4. **Factory Pattern** - createError() en TokenRefreshService
5. **Queue Pattern** - pendingRequests array

### Algoritmos

1. **Exponential Backoff** - delay = base * (factor ^ attempt)
2. **Jitter** - ±30% variación aleatoria
3. **Token Validation** - Verificar expiración antes de usar

### TypeScript Avanzado

1. **Union Types** - `AuthChannelEvent = LOGIN | LOGOUT | ...`
2. **Discriminated Unions** - `{ type: 'LOGIN', payload: {...} }`
3. **Conditional Types** - `CleanupFunction = () => void`
4. **Const Assertions** - `as const` en constantes
5. **Generic Functions** - `withRetry<T>(fn): Promise<T>`

---

## 🔮 VISIÓN FUTURA

### Después de Fase 10 (ESTADO FINAL ESPERADO)

El sistema tendrá:

- ⏳ Auto-refresh proactivo
- ⏳ Retry con exponential backoff + jitter
- ⏳ Sync entre tabs
- ⏳ State machine declarativa
- ⏳ Persistencia en IndexedDB
- ⏳ Session heartbeat
- ⏳ 0 código duplicado
- ⏳ 100% TypeScript
- ⏳ Tests completos
- ⏳ Documentación exhaustiva

### Beneficios Finales Esperados

| Métrica | Antes | Después |
|---------|-------|---------|
| **Resiliencia** | 1 intento | 3 intentos automáticos |
| **Código duplicado** | 3 lugares | 0 duplicación |
| **Type safety** | ~60% | 100% |
| **Bundle size** | ~80KB | ~50KB (optimizado) |
| **Mantenibilidad** | Baja | Alta |
| **Debugging** | Difícil | Fácil (logs + stats) |

---

## 📞 SOPORTE

### Si Tienes Problemas

1. **Revisar este documento** - Sección correspondiente a la fase
2. **Ver README.md** en `/resources/js/lib/auth/README.md` (una vez creado)
3. **Consultar documentación de backend** en `/documentacion/AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt`
4. **Verificar logs** con `authLogger` en consola

### Debug Helpers (A Implementar)

```typescript
// En consola del navegador (DevTools)

// 1. Importar sistema
import { TokenManager, TokenRefreshService, authLogger } from '/resources/js/lib/auth/index.ts';

// 2. Ver estado de TokenManager
console.table(TokenManager.getDebugInfo());

// 3. Ver estadísticas de refresh
console.table(TokenRefreshService.getStats());

// 4. Simular refresh (si hay sesión activa)
const result = await TokenRefreshService.refresh();
console.log('Refresh result:', result);
```

---

**Documento generado:** 2025-10-15
**Versión:** 1.0.0
**Estado:** Fase 0 de 10 - SIN AVANCE ⏳

---

🎯 **¡Sistema de autenticación enterprise - LISTO PARA COMENZAR!**

Progreso: ░░░░░░░░░░░░░░░░░░░░ 0% (0/10 fases)
