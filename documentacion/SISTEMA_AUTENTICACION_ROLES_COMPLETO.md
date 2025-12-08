# 🔐 Sistema de Autenticación y Roles - Documentación Completa

> **Proyecto:** Helpdesk Multi-tenant  
> **Fecha:** Diciembre 2025  
> **Arquitectura:** API-first con JWT Stateless

---

## 📋 Índice

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Arquitectura General](#2-arquitectura-general)
3. [Sistema JWT](#3-sistema-jwt)
4. [Sistema de Roles](#4-sistema-de-roles)
5. [Middlewares](#5-middlewares)
6. [Flujo de Autenticación](#6-flujo-de-autenticación)
7. [Rutas Web vs API](#7-rutas-web-vs-api)
8. [Comparación con Spatie](#8-comparación-con-spatie)
9. [Archivos Clave](#9-archivos-clave)

---

## 1. Resumen Ejecutivo

### ¿Qué tiene el sistema?

| Componente | Estado | Descripción |
|------------|--------|-------------|
| JWT Stateless | ✅ Completo | Tokens con `active_role` y `roles[]` |
| 4 Roles del Sistema | ✅ Completo | PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER |
| Multi-rol por Usuario | ✅ Completo | Un usuario puede tener múltiples roles |
| Active Role System | ✅ Completo | El usuario selecciona qué rol usar |
| Middleware de Roles | ✅ Completo | `EnsureUserHasRole` verifica rol activo |
| Protección de Rutas | ✅ Completo | web.php y api.php protegidos |

### ¿Qué NO tiene (y NO necesita)?

| Componente | Estado | Razón |
|------------|--------|-------|
| Spatie Permission | ❌ No instalado | Sistema propio equivalente |
| Permisos Granulares | ❌ No implementado | Roles son suficientes actualmente |
| Sesiones Laravel | ❌ No usado en auth | JWT stateless es mejor para API móvil |

---

## 2. Arquitectura General

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                        ARQUITECTURA API-FIRST                                    │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                  │
│  ┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐          │
│  │   APP MÓVIL     │      │   FRONTEND WEB  │      │  TERCEROS/API   │          │
│  │   (Flutter?)    │      │   (Blade + JS)  │      │   (Integraciones)│          │
│  └────────┬────────┘      └────────┬────────┘      └────────┬────────┘          │
│           │                        │                        │                    │
│           │         JWT Token      │         JWT Token      │                    │
│           │      Authorization     │      Authorization     │                    │
│           │                        │                        │                    │
│           └────────────────────────┼────────────────────────┘                    │
│                                    │                                             │
│                                    ▼                                             │
│  ┌─────────────────────────────────────────────────────────────────────────┐    │
│  │                         LARAVEL BACKEND                                  │    │
│  ├─────────────────────────────────────────────────────────────────────────┤    │
│  │                                                                          │    │
│  │  ┌─────────────────────────┐    ┌─────────────────────────┐             │    │
│  │  │      web.php            │    │       api.php           │             │    │
│  │  │  ───────────────────    │    │  ───────────────────    │             │    │
│  │  │  • Retorna VISTAS       │    │  • Retorna JSON         │             │    │
│  │  │  • jwt.require          │    │  • jwt.require          │             │    │
│  │  │  • role:XXX             │    │  • role:XXX             │             │    │
│  │  │  • Blade templates      │    │  • Controllers API      │             │    │
│  │  └─────────────────────────┘    └─────────────────────────┘             │    │
│  │                                                                          │    │
│  │  ┌─────────────────────────────────────────────────────────────────┐    │    │
│  │  │              CAPA DE AUTENTICACIÓN JWT                          │    │    │
│  │  │  • TokenService (genera/valida tokens)                          │    │    │
│  │  │  • JWTHelper (métodos estáticos para acceder a claims)          │    │    │
│  │  │  • RequireJWTAuthentication (middleware obligatorio)            │    │    │
│  │  │  • EnsureUserHasRole (verifica rol activo)                      │    │    │
│  │  └─────────────────────────────────────────────────────────────────┘    │    │
│  │                                                                          │    │
│  │  ┌─────────────────────────────────────────────────────────────────┐    │    │
│  │  │              SISTEMA DE ROLES                                    │    │    │
│  │  │  • auth.roles (tabla de roles)                                  │    │    │
│  │  │  • auth.user_roles (asignación usuario-rol-empresa)             │    │    │
│  │  │  • User::getAllRolesForJWT() (serializa roles para JWT)         │    │    │
│  │  └─────────────────────────────────────────────────────────────────┘    │    │
│  │                                                                          │    │
│  └─────────────────────────────────────────────────────────────────────────┘    │
│                                                                                  │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Sistema JWT

### 3.1 Estructura del Token JWT

```json
{
  "iss": "helpdesk-api",
  "aud": "helpdesk-clients",
  "iat": 1733644800,
  "exp": 1733648400,
  "sub": "550e8400-e29b-41d4-a716-446655440000",
  "user_id": "550e8400-e29b-41d4-a716-446655440000",
  "email": "usuario@ejemplo.com",
  "session_id": "abc123def456",
  "roles": [
    { "code": "PLATFORM_ADMIN", "company_id": null },
    { "code": "COMPANY_ADMIN", "company_id": "660e8400-e29b-41d4-a716-446655440001" }
  ],
  "active_role": {
    "code": "PLATFORM_ADMIN",
    "company_id": null
  }
}
```

### 3.2 Claims Explicados

| Claim | Tipo | Descripción |
|-------|------|-------------|
| `iss` | string | Issuer - Quién emitió el token |
| `aud` | string | Audience - Para quién es el token |
| `iat` | int | Issued At - Cuándo se emitió |
| `exp` | int | Expiration - Cuándo expira |
| `sub` | string | Subject - ID del usuario |
| `user_id` | string (UUID) | ID único del usuario |
| `email` | string | Email del usuario |
| `session_id` | string | ID de sesión para blacklist |
| **`roles`** | array | **TODOS los roles del usuario** |
| **`active_role`** | object\|null | **ROL ACTUALMENTE SELECCIONADO** |

### 3.3 TokenService - Generación

**Archivo:** `app/Features/Authentication/Services/TokenService.php`

```php
public function generateAccessToken(User $user, ?string $sessionId = null, ?array $activeRole = null): string
{
    $roles = $user->getAllRolesForJWT();

    // Auto-selección si tiene solo 1 rol
    if ($activeRole === null && count($roles) === 1) {
        $activeRole = $roles[0];
    }

    $payload = [
        'iss' => config('jwt.issuer'),
        'aud' => config('jwt.audience'),
        'iat' => time(),
        'exp' => time() + (config('jwt.ttl') * 60),
        'sub' => $user->id,
        'user_id' => $user->id,
        'email' => $user->email,
        'session_id' => $sessionId ?? Str::random(32),
        'roles' => $roles,
        'active_role' => $activeRole,
    ];

    return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
}
```

### 3.4 JWTHelper - Métodos de Acceso

**Archivo:** `app/Shared/Helpers/JWTHelper.php`

| Método | Retorna | Uso |
|--------|---------|-----|
| `getAuthenticatedUser()` | `User\|null` | Obtener usuario autenticado |
| `getUserId()` | `string\|null` | Obtener ID del usuario |
| `getRoles()` | `array` | Todos los roles del JWT |
| `hasRoleFromJWT($code)` | `bool` | ¿Tiene este rol? (cualquiera) |
| **`getActiveRole()`** | `array\|null` | Rol activo completo |
| **`getActiveRoleCode()`** | `string\|null` | Código del rol activo |
| **`getActiveCompanyId()`** | `string\|null` | Company del rol activo |
| **`isActiveRole($code)`** | `bool` | ¿Es este el rol activo? |
| **`isActiveRoleOneOf($codes)`** | `bool` | ¿El activo está en lista? |

---

## 4. Sistema de Roles

### 4.1 Los 4 Roles del Sistema

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          JERARQUÍA DE ROLES                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │  PLATFORM_ADMIN                                                        │  │
│  │  ─────────────────                                                     │  │
│  │  • Acceso TOTAL al sistema                                            │  │
│  │  • Gestiona empresas, usuarios globales                               │  │
│  │  • Aprueba/rechaza solicitudes de empresas                           │  │
│  │  • NO requiere company_id                                             │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                              │                                               │
│                              ▼                                               │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │  COMPANY_ADMIN                                                         │  │
│  │  ─────────────────                                                     │  │
│  │  • Administra UNA empresa específica                                  │  │
│  │  • Gestiona agentes, categorías, anuncios                            │  │
│  │  • Ve todos los tickets de su empresa                                │  │
│  │  • REQUIERE company_id                                                │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                              │                                               │
│                              ▼                                               │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │  AGENT                                                                 │  │
│  │  ─────────────────                                                     │  │
│  │  • Agente de soporte de UNA empresa                                   │  │
│  │  • Responde tickets asignados                                         │  │
│  │  • Ve tickets de su empresa                                           │  │
│  │  • REQUIERE company_id                                                │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                              │                                               │
│                              ▼                                               │
│  ┌───────────────────────────────────────────────────────────────────────┐  │
│  │  USER                                                                  │  │
│  │  ─────────────────                                                     │  │
│  │  • Usuario final / Cliente                                            │  │
│  │  • Crea tickets a empresas seguidas                                  │  │
│  │  • Ve solo SUS tickets                                                │  │
│  │  • NO requiere company_id                                             │  │
│  └───────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 4.2 Tabla `auth.roles`

**Archivo:** `database/migrations/2024_01_01_000003_create_roles_table.php`

```sql
CREATE TABLE auth.roles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    role_code VARCHAR(50) UNIQUE NOT NULL,  -- 'PLATFORM_ADMIN', 'COMPANY_ADMIN', etc.
    role_name VARCHAR(100) NOT NULL,         -- 'Administrador de Plataforma'
    description TEXT,
    is_system BOOLEAN DEFAULT TRUE,
    default_dashboard VARCHAR(255),
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
```

**Datos iniciales:**

| role_code | role_name | default_dashboard |
|-----------|-----------|-------------------|
| PLATFORM_ADMIN | Administrador de Plataforma | /app/admin/dashboard |
| COMPANY_ADMIN | Administrador de Empresa | /app/company/dashboard |
| AGENT | Agente de Soporte | /app/agent/dashboard |
| USER | Cliente | /app/user/dashboard |

### 4.3 Tabla `auth.user_roles`

**Archivo:** `database/migrations/2024_01_01_000004_create_user_roles_table.php`

```sql
CREATE TABLE auth.user_roles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    role_code VARCHAR(50) NOT NULL REFERENCES auth.roles(role_code),
    company_id UUID REFERENCES company.companies(id),  -- NULL para PLATFORM_ADMIN y USER
    is_active BOOLEAN DEFAULT TRUE,
    assigned_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    assigned_by UUID REFERENCES auth.users(id),
    revoked_at TIMESTAMPTZ,
    revocation_reason TEXT,
    
    -- Restricciones
    CONSTRAINT uq_user_role_context UNIQUE (user_id, role_code, company_id),
    CONSTRAINT chk_company_context CHECK (
        (role_code IN ('COMPANY_ADMIN', 'AGENT') AND company_id IS NOT NULL) OR
        (role_code NOT IN ('COMPANY_ADMIN', 'AGENT'))
    )
);
```

### 4.4 Ejemplo de Multi-rol

Un usuario puede tener:

```
Usuario: lukqs05@gmail.com
├── PLATFORM_ADMIN (company_id: NULL)
├── COMPANY_ADMIN (company_id: "empresa-A-uuid")
├── COMPANY_ADMIN (company_id: "empresa-B-uuid")  ← Admin de 2 empresas
└── AGENT (company_id: "empresa-C-uuid")
```

Su JWT contendría:

```json
{
  "roles": [
    { "code": "PLATFORM_ADMIN", "company_id": null },
    { "code": "COMPANY_ADMIN", "company_id": "empresa-A-uuid" },
    { "code": "COMPANY_ADMIN", "company_id": "empresa-B-uuid" },
    { "code": "AGENT", "company_id": "empresa-C-uuid" }
  ],
  "active_role": null  // ← Debe seleccionar uno
}
```

---

## 5. Middlewares

### 5.1 Registro de Middlewares

**Archivo:** `bootstrap/app.php`

```php
$middleware->alias([
    'jwt.auth'       => JWTAuthenticationMiddleware::class,    // Opcional (carga user si hay token)
    'jwt.require'    => RequireJWTAuthentication::class,       // OBLIGATORIO (falla si no hay token)
    'role'           => EnsureUserHasRole::class,              // Verifica rol
    'role.selected'  => EnsureRoleSelected::class,             // Verifica que haya active_role
    'jwt.guest'      => RedirectIfAuthenticatedJWT::class,     // Solo para no-autenticados
]);
```

### 5.2 `jwt.require` - RequireJWTAuthentication

**Archivo:** `app/Features/Authentication/Http/Middleware/RequireJWTAuthentication.php`

**Responsabilidades:**
1. Extrae token de `Authorization: Bearer xxx` o cookie `jwt_token`
2. Valida firma y expiración del token
3. Verifica que no esté en blacklist
4. Carga el usuario desde la BD
5. Almacena en request: `jwt_user`, `jwt_payload`

**Comportamiento en fallo:**
- API (`/api/*`): Retorna 401 JSON
- Web: Redirige a Auth Loader (`/`)

### 5.3 `role` - EnsureUserHasRole

**Archivo:** `app/Features/Authentication/Http/Middleware/EnsureUserHasRole.php`

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    $user = JWTHelper::getAuthenticatedUser();
    $payload = $request->attributes->get('jwt_payload');
    $hasExplicitActiveRole = $payload && isset($payload['active_role']) && $payload['active_role'] !== null;

    foreach ($roles as $role) {
        if ($hasExplicitActiveRole) {
            // ═══════════════════════════════════════════════════════════
            // MODO ESTRICTO: Verifica SOLO el active_role
            // ═══════════════════════════════════════════════════════════
            if (JWTHelper::isActiveRole($role)) {
                return $next($request);
            }
        } else {
            // ═══════════════════════════════════════════════════════════
            // MODO FALLBACK: Sin active_role (backward compatibility)
            // ═══════════════════════════════════════════════════════════
            if (JWTHelper::hasRoleFromJWT($role)) {
                return $next($request);
            }
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }
    }

    // Acceso denegado
    if ($request->expectsJson() || $request->is('api/*')) {
        return response()->json(['error' => 'No tienes permisos'], 403);
    }
    abort(403, 'No tienes permisos para acceder a esta sección');
}
```

**Comportamiento CRÍTICO:**

| Escenario | Comportamiento |
|-----------|----------------|
| JWT tiene `active_role` | **MODO ESTRICTO**: Solo acepta si `active_role.code` coincide |
| JWT no tiene `active_role` | **MODO FALLBACK**: Acepta si tiene el rol en `roles[]` o en BD |

---

## 6. Flujo de Autenticación

### 6.1 Login Normal (1 rol)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  FLUJO: Usuario con UN solo rol                                              │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  1. POST /api/auth/login { email, password }                                 │
│     │                                                                        │
│     ▼                                                                        │
│  2. AuthController::login()                                                  │
│     │  - Valida credenciales                                                │
│     │  - Obtiene roles del usuario: [{ code: "USER", company_id: null }]    │
│     │  - Como solo tiene 1 rol → auto-selecciona active_role                │
│     │                                                                        │
│     ▼                                                                        │
│  3. TokenService::generateAccessToken()                                      │
│     │  - roles: [{ code: "USER", company_id: null }]                        │
│     │  - active_role: { code: "USER", company_id: null }  ← AUTO            │
│     │                                                                        │
│     ▼                                                                        │
│  4. Respuesta: { token, user, redirect_to: "/app/user/dashboard" }          │
│     │                                                                        │
│     ▼                                                                        │
│  5. Frontend redirige directamente al dashboard                              │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 6.2 Login Multi-rol (selección requerida)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  FLUJO: Usuario con MÚLTIPLES roles                                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  1. POST /api/auth/login { email, password }                                 │
│     │                                                                        │
│     ▼                                                                        │
│  2. AuthController::login()                                                  │
│     │  - Valida credenciales                                                │
│     │  - Obtiene roles: [PLATFORM_ADMIN, COMPANY_ADMIN, ...]               │
│     │  - Múltiples roles → NO auto-selecciona                               │
│     │                                                                        │
│     ▼                                                                        │
│  3. TokenService::generateAccessToken()                                      │
│     │  - roles: [...]                                                       │
│     │  - active_role: NULL  ← Debe seleccionar                              │
│     │                                                                        │
│     ▼                                                                        │
│  4. Respuesta: { token, user, redirect_to: "/auth-flow/role-selector" }     │
│     │                                                                        │
│     ▼                                                                        │
│  5. Frontend muestra pantalla de selección de rol                            │
│     │                                                                        │
│     ▼                                                                        │
│  6. Usuario selecciona "COMPANY_ADMIN" para "Empresa X"                      │
│     │                                                                        │
│     ▼                                                                        │
│  7. POST /api/auth/select-role { role_code, company_id }                    │
│     │                                                                        │
│     ▼                                                                        │
│  8. Nuevo JWT con active_role establecido                                    │
│     │                                                                        │
│     ▼                                                                        │
│  9. Redirige al dashboard correspondiente                                    │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 6.3 Cambio de Rol (Switch Role)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  FLUJO: Cambiar de rol sin re-login                                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Usuario actual: COMPANY_ADMIN de "Empresa A"                                │
│  Quiere cambiar a: PLATFORM_ADMIN                                            │
│                                                                              │
│  1. Click en "Cambiar Rol" en el menú                                        │
│     │                                                                        │
│     ▼                                                                        │
│  2. GET /api/auth/available-roles                                            │
│     │  Respuesta: [                                                         │
│     │    { code: "PLATFORM_ADMIN", company_id: null, company_name: null },  │
│     │    { code: "COMPANY_ADMIN", company_id: "xxx", company_name: "A" }    │
│     │  ]                                                                    │
│     │                                                                        │
│     ▼                                                                        │
│  3. Modal muestra opciones disponibles                                       │
│     │                                                                        │
│     ▼                                                                        │
│  4. Usuario selecciona "PLATFORM_ADMIN"                                      │
│     │                                                                        │
│     ▼                                                                        │
│  5. POST /api/auth/select-role { role_code: "PLATFORM_ADMIN" }              │
│     │                                                                        │
│     ▼                                                                        │
│  6. Nuevo JWT con active_role: { code: "PLATFORM_ADMIN" }                   │
│     │                                                                        │
│     ▼                                                                        │
│  7. Redirige a /app/admin/dashboard                                          │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 7. Rutas Web vs API

### 7.1 Rutas Web (web.php)

**Propósito:** Servir vistas Blade (HTML)

```php
// ═══════════════════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS (sin autenticación)
// ═══════════════════════════════════════════════════════════════════════════
Route::get('/', fn() => view('auth-loader'))->name('root');
Route::get('/welcome', fn() => view('landing'))->middleware('jwt.guest');
Route::get('/login', fn() => view('auth.login'))->middleware('jwt.guest');
Route::get('/register', fn() => view('auth.register'))->middleware('jwt.guest');

// ═══════════════════════════════════════════════════════════════════════════
// RUTAS AUTENTICADAS (jwt.require)
// ═══════════════════════════════════════════════════════════════════════════
Route::middleware('jwt.require')->prefix('app')->group(function () {
    
    // Dashboard genérico (redirige según rol)
    Route::get('/dashboard', [DashboardController::class, 'redirect']);

    // ───────────────────────────────────────────────────────────────────────
    // PLATFORM_ADMIN
    // ───────────────────────────────────────────────────────────────────────
    Route::middleware('role:PLATFORM_ADMIN')->prefix('admin')->group(function () {
        Route::get('/dashboard', fn() => view('app.platform-admin.dashboard'));
        Route::get('/companies', fn() => view('app.platform-admin.companies.index'));
        Route::get('/company-requests', fn() => view('app.platform-admin.company-requests.index'));
        Route::get('/users', fn() => view('app.platform-admin.users.index'));
    });

    // ───────────────────────────────────────────────────────────────────────
    // COMPANY_ADMIN
    // ───────────────────────────────────────────────────────────────────────
    Route::middleware('role:COMPANY_ADMIN')->prefix('company')->group(function () {
        Route::get('/dashboard', fn() => view('app.company-admin.dashboard'));
        Route::get('/tickets', fn() => view('app.shared.tickets.index'));
        Route::get('/categories', fn() => view('app.company-admin.categories.index'));
        Route::get('/announcements', fn() => view('app.company-admin.announcements.index'));
        Route::get('/agents', fn() => view('app.company-admin.agents.index'));
    });

    // ───────────────────────────────────────────────────────────────────────
    // AGENT
    // ───────────────────────────────────────────────────────────────────────
    Route::middleware('role:AGENT')->prefix('agent')->group(function () {
        Route::get('/dashboard', fn() => view('app.agent.dashboard'));
        Route::get('/tickets', fn() => view('app.shared.tickets.index'));
    });

    // ───────────────────────────────────────────────────────────────────────
    // USER
    // ───────────────────────────────────────────────────────────────────────
    Route::middleware('role:USER')->prefix('user')->group(function () {
        Route::get('/dashboard', fn() => view('app.user.dashboard'));
        Route::get('/tickets', fn() => view('app.user.tickets.index'));
        Route::get('/help-center', fn() => view('app.user.help-center.index'));
    });
});
```

### 7.2 Rutas API (api.php)

**Propósito:** Retornar JSON para frontend y app móvil

```php
// ═══════════════════════════════════════════════════════════════════════════
// RUTAS PÚBLICAS
// ═══════════════════════════════════════════════════════════════════════════
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']);
Route::post('/company-requests', [CompanyRequestController::class, 'store']);

// ═══════════════════════════════════════════════════════════════════════════
// RUTAS AUTENTICADAS
// ═══════════════════════════════════════════════════════════════════════════
Route::middleware('jwt.require')->group(function () {

    // ─── Auth ───
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/select-role', [AuthController::class, 'selectRole']);
    Route::get('/auth/available-roles', [AuthController::class, 'availableRoles']);
    Route::get('/auth/status', [AuthController::class, 'status']);

    // ─── User ───
    Route::get('/users/me', [UserController::class, 'me']);
    Route::get('/profile', [ProfileController::class, 'show']);

    // ─── Companies (lectura para todos) ───
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/companies/{id}', [CompanyController::class, 'show']);
});

// ═══════════════════════════════════════════════════════════════════════════
// RUTAS CON ROLES ESPECÍFICOS
// ═══════════════════════════════════════════════════════════════════════════

// Solo PLATFORM_ADMIN
Route::middleware(['jwt.require', 'role:PLATFORM_ADMIN'])->group(function () {
    Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/company-requests/{id}/approve', ...);
    Route::post('/company-requests/{id}/reject', ...);
});

// PLATFORM_ADMIN o COMPANY_ADMIN
Route::middleware(['jwt.require', 'role:PLATFORM_ADMIN,COMPANY_ADMIN'])->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/users/{userId}/roles', [RoleController::class, 'assign']);
});

// Solo COMPANY_ADMIN
Route::middleware(['jwt.require', 'role:COMPANY_ADMIN'])->group(function () {
    Route::apiResource('/categories', CategoryController::class);
    Route::apiResource('/announcements', AnnouncementController::class);
});
```

### 7.3 Diferencias Clave

| Aspecto | web.php | api.php |
|---------|---------|---------|
| **Retorna** | Vistas Blade (HTML) | JSON |
| **Autenticación** | JWT en cookie | JWT en header `Authorization` |
| **Middleware** | `jwt.require`, `role:X` | `jwt.require`, `role:X` |
| **Error 401** | Redirige a `/` | JSON `{"error": "..."}` |
| **Error 403** | `abort(403)` | JSON `{"error": "..."}` |
| **Consumidor** | Browser (web) | JavaScript fetch, App móvil |

---

## 8. Comparación con Spatie

### 8.1 Lo que TU sistema ya tiene

| Funcionalidad | Tu Sistema | Spatie |
|---------------|------------|--------|
| Definir roles | ✅ `auth.roles` tabla | ✅ `roles` tabla |
| Asignar roles a usuarios | ✅ `auth.user_roles` | ✅ `model_has_roles` |
| Rol por empresa (multi-tenant) | ✅ `company_id` en user_roles | ❌ Necesita extensión |
| Middleware de roles | ✅ `EnsureUserHasRole` | ✅ `RoleMiddleware` |
| Verificar rol en código | ✅ `$user->hasRole('X')` | ✅ `$user->hasRole('X')` |
| **Rol activo (JWT)** | ✅ `active_role` claim | ❌ No aplica |
| Multi-rol por usuario | ✅ Nativo | ✅ Nativo |
| Permisos granulares | ❌ No implementado | ✅ `permissions` tabla |
| Directivas Blade `@role` | ❌ No tiene | ✅ `@role('admin')` |

### 8.2 Lo que Spatie agregaría

| Funcionalidad | Beneficio | ¿Necesario? |
|---------------|-----------|-------------|
| Directivas `@role`, `@can` | Ocultar elementos en Blade | Cosmético |
| Tabla `permissions` | Permisos granulares | No actualmente |
| `$user->can('edit posts')` | Verificar permisos en código | No actualmente |
| Cache de permisos | Performance | Ya tienes JWT |

### 8.3 Problemas de Integrar Spatie

| Problema | Descripción | Solución |
|----------|-------------|----------|
| **Sesiones vs JWT** | Spatie usa sesiones Laravel para caché | Configurar guard personalizado |
| **Multi-tenant** | Spatie no maneja `company_id` por defecto | Extender con teams/tenants |
| **Active Role** | Spatie no tiene concepto de "rol activo" | Tu middleware sigue manejando esto |
| **Duplicación** | Tendrías 2 tablas de roles | Sincronizar o ignorar una |

### 8.4 Recomendación

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  DECISIÓN: ¿Integrar Spatie?                                                 │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  SI EL INGENIERO SOLO QUIERE VER SPATIE EN web.php:                         │
│  ───────────────────────────────────────────────────────────────────────    │
│  ✅ Instalar Spatie                                                          │
│  ✅ Sincronizar tus 4 roles                                                  │
│  ✅ Agregar trait HasRoles a User                                           │
│  ✅ Usar directivas @role en Blade                                          │
│  ✅ Tu middleware sigue controlando active_role                              │
│  ⚠️ Spatie solo será "decorativo" - tu JWT sigue siendo el core              │
│                                                                              │
│  SI PUEDES NEGOCIAR:                                                         │
│  ───────────────────────────────────────────────────────────────────────    │
│  📄 Mostrar esta documentación                                               │
│  📄 Explicar que tienes un sistema equivalente                               │
│  📄 Tu sistema es MEJOR para JWT stateless                                   │
│  📄 Spatie está diseñado para sesiones, no JWT                               │
│                                                                              │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 9. Archivos Clave

### 9.1 Autenticación JWT

| Archivo | Propósito |
|---------|-----------|
| `app/Features/Authentication/Services/TokenService.php` | Genera y valida tokens JWT |
| `app/Shared/Helpers/JWTHelper.php` | Métodos estáticos para acceder a claims |
| `app/Features/Authentication/Traits/JWTAuthenticationTrait.php` | Trait para controllers |
| `config/jwt.php` | Configuración JWT (secret, ttl, issuer) |

### 9.2 Middlewares

| Archivo | Alias | Propósito |
|---------|-------|-----------|
| `app/Features/Authentication/Http/Middleware/RequireJWTAuthentication.php` | `jwt.require` | Auth obligatoria |
| `app/Features/Authentication/Http/Middleware/EnsureUserHasRole.php` | `role` | Verifica rol activo |
| `app/Features/Authentication/Http/Middleware/EnsureRoleSelected.php` | `role.selected` | Requiere active_role |
| `app/Features/Authentication/Http/Middleware/JWTAuthenticationMiddleware.php` | `jwt.auth` | Auth opcional |
| `app/Features/Authentication/Http/Middleware/RedirectIfAuthenticatedJWT.php` | `jwt.guest` | Solo guests |

### 9.3 Modelos

| Archivo | Propósito |
|---------|-----------|
| `app/Features/UserManagement/Models/User.php` | Usuario con métodos de roles |
| `app/Features/UserManagement/Models/Role.php` | Definición de roles |
| `app/Features/UserManagement/Models/UserRole.php` | Asignación usuario-rol-empresa |

### 9.4 Migraciones

| Archivo | Tabla |
|---------|-------|
| `database/migrations/..._create_roles_table.php` | `auth.roles` |
| `database/migrations/..._create_user_roles_table.php` | `auth.user_roles` |

### 9.5 Rutas

| Archivo | Propósito |
|---------|-----------|
| `routes/web.php` | Rutas que retornan vistas |
| `routes/api.php` | Rutas API JSON |

### 9.6 Controllers de Auth

| Archivo | Endpoints |
|---------|-----------|
| `app/Features/Authentication/Http/Controllers/AuthController.php` | login, logout, refresh, selectRole |
| `app/Features/UserManagement/Http/Controllers/RoleController.php` | index, assign, remove |

---

## 10. Conclusión

Tu sistema de autenticación y roles es **completo, robusto y profesional**. Está diseñado específicamente para:

- ✅ JWT Stateless (ideal para API móvil)
- ✅ Multi-tenant (roles por empresa)
- ✅ Multi-rol (un usuario, varios roles)
- ✅ Active Role (selección de contexto)

**Spatie Permission** está diseñado para aplicaciones Laravel tradicionales con sesiones. Integrarlo es posible pero:

1. Sería mayormente "cosmético" (directivas Blade)
2. Tu middleware `EnsureUserHasRole` seguiría siendo el core
3. El sistema de `active_role` es algo que Spatie no maneja

**Si el ingeniero insiste**, la integración es factible en ~1-2 horas, pero tu sistema actual ya cumple la misma función.

---

*Documentación generada: Diciembre 2025*
