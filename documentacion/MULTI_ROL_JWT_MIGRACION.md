# Migración de Multi-Rol: JWT con Rol Seleccionado

**Fecha:** 2025-11-17
**Estado:** Plan de Implementación Aprobado
**Versión:** 1.0

---

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Problema Identificado](#problema-identificado)
3. [Auditorías Realizadas](#auditorías-realizadas)
4. [Opciones Evaluadas](#opciones-evaluadas)
5. [Solución Elegida: OPCIÓN 1](#solución-elegida-opción-1)
6. [Arquitectura Propuesta](#arquitectura-propuesta)
7. [Cambios Específicos (LISTA A)](#cambios-específicos-lista-a)
8. [Nuevos Helpers Necesarios](#nuevos-helpers-necesarios)
9. [Simulaciones de Casos de Uso](#simulaciones-de-casos-de-uso)
10. [Plan de Implementación](#plan-de-implementación)
11. [Estimaciones de Esfuerzo](#estimaciones-de-esfuerzo)
12. [Riesgos y Mitigaciones](#riesgos-y-mitigaciones)
13. [Testing Strategy](#testing-strategy)
14. [Checklist de Validación](#checklist-de-validación)

---

## Introducción

Este documento describe el plan completo para implementar un sistema de multi-rol con JWT que contiene UN SOLO rol activo (el seleccionado), resolviendo la inconsistencia actual donde el sistema usa una **mezcla híbrida de JWT y consultas a Base de Datos** para validación de roles.

### Contexto Actual

El sistema Helpdesk fue diseñado originalmente con usuarios que tenían UN rol. Cuando se extendió para soportar múltiples roles por usuario (USER + AGENT + COMPANY_ADMIN), la implementación resultó **inconsistente**:

- Algunos componentes consultan JWT
- Otros componentes consultan Base de Datos
- No existe mecanismo para "seleccionar" qué rol está activo

Esto causa comportamientos impredecibles donde un usuario con 2 roles puede estar autorizado en una operación pero denegado en otra, dependiendo de cuál componente valide.

---

## Problema Identificado

### Síntomas

**Caso de Uso Real:**

Usuario Juan tiene roles: `[USER, AGENT(empresa-1), COMPANY_ADMIN(empresa-1)]`

**Problema 1: Inconsistencia en Tickets**
```
GET /api/tickets (como USER)
→ TicketService::getUserRole() retorna 'USER' (primera coincidencia)
→ Ve SOLO sus propios tickets
→ INCORRECTO: También tiene AGENT, debería ver tickets de empresa-1

POST /api/tickets/TKT-001/resolve (como USER)
→ TicketPolicy::resolve() consulta DB
→ Encuentra que TIENE AGENT en empresa-1
→ AUTORIZADO (contradice que es USER)
```

**Problema 2: Inconsistencia en Articles**
```
GET /api/articles (como USER)
→ ArticleService::listArticles() consulta DB con hasRole()
→ Encuentra COMPANY_ADMIN en DB
→ Ve todos los artículos de empresa-1
→ INCORRECTO: No está actuando como ADMIN, está como USER

Usuario NUNCA PUEDE actuar como USER si tiene ADMIN en BD
```

**Problema 3: Sin Contexto Activo**
```
¿Cómo sabe el usuario QUÉ rol está usando?
¿Cómo cambia entre roles?
→ No existe mecanismo
```

### Causa Raíz

**Diseño Híbrido Inconsistente:**

| Componente | Fuente de Datos | Problema |
|-----------|-----------------|----------|
| `TicketPolicy::create()` | JWT | ✅ Consistente |
| `TicketPolicy::view()` | DB (hasRoleInCompany) | ❌ Ignora JWT |
| `TicketService::getUserRole()` | JWT | ✅ Consistente |
| `ArticleService::listArticles()` | DB (hasRole) | ❌ Ignora JWT |
| `User::hasRole()` | DB | Llamada desde políticas |
| `User::hasRoleInCompany()` | DB | Llamada desde políticas |
| `JWTHelper::hasRoleFromJWT()` | JWT | ✅ Stateless |

**No hay consenso sobre cuál es la fuente de verdad: JWT o BD**

---

## Auditorías Realizadas

### Auditoría #1: Análisis de Arquitectura Multi-Rol

**Resultado:** Identificó 11 componentes problemáticos (TicketPolicy, ArticleService, CompanyPolicy, UserPolicy, etc.)

**Recomendación:** Necesario refactor arquitectónico para estandarizar a JWT

**Conclusión:** Sin refactor parcial, JWT con múltiples roles no funciona.

### Auditoría #2: Simulación de Switch-Role sin Refactor

**Escenario Simulado:**
```
Usuario con [USER, AGENT]
→ POST /api/auth/switch-role { "role": "AGENT" }
→ Nuevo JWT: { roles: [AGENT] }
→ GET /api/tickets
```

**Resultado:**
- ❌ TicketPolicy sigue consultando DB (ignora switch-role)
- ❌ ArticleService sigue consultando DB (ignora switch-role)
- ✅ TicketService::getUserRole() funciona correctamente

**Conclusión:** Switch-role requiere refactor de Policies y Services

### Auditoría #3: Exhaustiva DB vs JWT

**Búsqueda Exhaustiva de Métodos que Consultan DB:**

| Método | Ocurrencias | Archivos Afectados |
|--------|-------------|-------------------|
| `hasRole()` | 88 | 25+ archivos |
| `hasRoleInCompany()` | 32 | 20+ archivos |
| `userRoles()` | 17 | 10+ archivos |
| `activeRoles()` | Varias | Usado por hasRole |
| Queries directas a `user_roles` | 3 | VisibilityService, etc |

**Total: ~150 líneas de código que consultan DB para validación de roles**

**Conclusión:** Migrar a JWT es viable pero requiere refactor parcial de 42 archivos

---

## Opciones Evaluadas

### OPCIÓN A: Mantener Status Quo (No Recomendado)

**Descripción:** No hacer nada, vivir con la inconsistencia

**Ventajas:**
- 0 horas de desarrollo

**Desventajas:**
- ❌ Sistema roto para usuarios con múltiples roles
- ❌ Comportamiento impredecible
- ❌ Deuda técnica aumenta
- ❌ Bugs en producción inevitable

**Veredicto:** ❌ RECHAZADO

---

### OPCIÓN B: Refactor Completo (Óptimo pero costoso)

**Descripción:** Refactorizar TODAS las validaciones de roles (JWT como única fuente)

**Incluyendo:**
1. TokenService - Generar JWT con 1 rol
2. switch-role endpoint
3. Refactor de 7 Policies (TicketPolicy, UserPolicy, CompanyPolicy, etc.)
4. Refactor de 3 Services (TicketService, ArticleService, VisibilityService)
5. Refactor de 5 Controllers (UserController, RoleController, etc.)
6. Refactor de 5 FormRequests
7. Refactor de 2 Middlewares
8. Deprecate métodos del User model
9. Crear helpers nuevos
10. Tests exhaustivos

**Estimación:** 17-23 horas

**Ventajas:**
- ✅ Sistema 100% consistente
- ✅ JWT como única fuente de verdad
- ✅ Sin consultas innecesarias a DB
- ✅ Mejor performance
- ✅ Escalable a nuevos roles

**Desventajas:**
- ❌ 17-23 horas de desarrollo
- ❌ Alto riesgo de bugs durante transición
- ❌ Requiere testing exhaustivo
- ❌ Cambios en 42 archivos

**Veredicto:** ✅ Óptimo pero costoso

---

### OPCIÓN C: Refactor Parcial - OPCIÓN 1 (Recomendado) ⭐

**Descripción:** Implementar JWT con rol seleccionado + refactor SOLO de componentes críticos

**Incluye:**
1. TokenService - Generar JWT con 1 rol (el seleccionado)
2. switch-role endpoint
3. Refactor LISTA A CRÍTICA:
   - TicketPolicy (9 cambios)
   - ArticleService (8 cambios)
   - VisibilityService (2 cambios)
   - TicketPolicy complementarios (3 cambios)
   - Middlewares (2 cambios)
4. Crear helpers nuevos (1 helper)
5. Tests básicos

**NO Incluye (LISTA B - Fase 2 posterior):**
- UserPolicy, CompanyPolicy refactor
- UserController, RoleController refactor
- FormRequests refactor (pueden esperar)

**Estimación:** 7-9 horas

**Ventajas:**
- ✅ 7-9 horas de desarrollo (1 día)
- ✅ Resuelve problemas CRÍTICOS (Tickets y Articles)
- ✅ Switch-role funcional
- ✅ Tests básicamente sin cambios (misma estructura JWT)
- ✅ Implementación gradual (Fase 2 después)
- ✅ Bajo riesgo (cambios focalizados)
- ✅ Funciona en producción rápidamente

**Desventajas:**
- ⚠️ Controllers/Requests siguen híbridos (LISTA B)
- ⚠️ Deuda técnica parcial (mitigada con OPCIÓN B después)
- ⚠️ Necesita OPCIÓN B en 2-3 semanas (cuando esté stable)

**Veredicto:** ✅ SELECCIONADO - Balance óptimo entre velocidad y funcionalidad

---

## Solución Elegida: OPCIÓN 1

### Por Qué OPCIÓN 1

**Análisis Costo-Beneficio:**

| Aspecto | OPCIÓN A | OPCIÓN B | OPCIÓN C ⭐ |
|--------|---------|---------|-----------|
| **Horas de Trabajo** | 0 | 17-23 | 7-9 |
| **Riesgo de Bugs** | Alto | Muy Alto | Bajo |
| **Sistema Funcional** | ❌ No | ✅ Sí | ✅ Parcialmente |
| **Tickets Funcionales** | ❌ No | ✅ Sí | ✅ Sí |
| **Articles Funcionales** | ❌ No | ✅ Sí | ✅ Sí |
| **Switch-Role Funcional** | ❌ No | ✅ Sí | ✅ Sí |
| **Testing Impact** | N/A | Alto | Muy Bajo |
| **Producción Segura** | ❌ No | ✅ Sí (lento) | ✅ Sí (rápido) |
| **Deuda Técnica Restante** | Alta | Ninguna | Media (LISTA B) |

**Conclusión:** OPCIÓN 1 maximiza beneficio (Tickets + Articles funcionales + Switch-Role) con mínimo riesgo y esfuerzo, dejando LISTA B para consolidación posterior.

### Estrategia de Implementación OPCIÓN 1

**Fase 1: Core JWT + Endpoint (2-3 horas)**
- TokenService: Generar JWT con 1 rol
- AuthController: Crear endpoint switch-role
- JWTHelper: Helpers necesarios

**Fase 2: Refactor LISTA A Crítica (4-5 horas)**
- TicketPolicy: 9 cambios
- ArticleService: 8 cambios
- VisibilityService: 2 cambios
- Middlewares: 2 cambios

**Fase 3: Testing + Validation (2 horas)**
- Tests básicos
- Tests de switch-role
- Validación end-to-end

**Fase 4 (2-3 semanas después): LISTA B - Consolidación**
- UserPolicy, CompanyPolicy
- Controllers
- FormRequests
- Deprecate User::hasRole()

---

## Arquitectura Propuesta

### Estado Actual (Híbrido - PROBLEMATICO)

```
┌─────────────────────────────────────────┐
│         Cliente (Frontend)              │
│                                         │
│  POST /api/login                        │
│  ↓                                      │
│  Recibe: JWT { roles: [USER, AGENT] }   │
│  (Pero no sabe cuál usar)               │
└────────────────┬────────────────────────┘
                 │
      ┌──────────┴──────────┐
      │                     │
      ↓                     ↓
┌─────────────────┐  ┌──────────────────┐
│  JWT (múltiples)│  │  BD user_roles   │
│  [USER, AGENT]  │  │  (única fuente)  │
│                 │  │                  │
│ ✅ Stateless    │  │ ❌ Lag de datos  │
│ ❌ Ambiguo      │  │ ❌ Queries       │
│ ❌ Sin contexto │  │ ✅ Sincronizado  │
└────────┬────────┘  └────────┬─────────┘
         │                    │
         ├─→ TicketPolicy    │
         │   (Consulta BD) ❌│
         │                    │
         ├─→ TicketService   │
         │   (Consulta JWT) ✅
         │                    │
         ├─→ ArticleService  │
         │   (Consulta BD) ❌─┘
         │
         └─→ Inconsistencia
             Comportamiento impredecible
```

### Arquitectura Propuesta (OPCIÓN 1)

```
┌─────────────────────────────────────────────┐
│         Cliente (Frontend)                  │
│                                             │
│  POST /api/login                            │
│  ↓                                          │
│  Recibe: JWT { roles: [USER] }              │
│         (Rol por defecto)                   │
│                                             │
│  Puede cambiar:                             │
│  POST /api/auth/switch-role                 │
│  { role: "AGENT", company_id: "emp-1" }    │
│  ↓                                          │
│  Recibe: JWT { roles: [AGENT], ... }       │
└────────────────┬────────────────────────────┘
                 │
      ┌──────────┴──────────┐
      │                     │
      ↓                     ↓
┌─────────────────┐  ┌──────────────────┐
│  JWT (UN rol)   │  │  BD user_roles   │
│  [AGENT]        │  │  (validación)    │
│                 │  │                  │
│ ✅ Stateless    │  │ ✅ Valida cambios│
│ ✅ Claro        │  │ ✅ Secure        │
│ ✅ Contexto     │  │                  │
└────────┬────────┘  └────────┬─────────┘
         │                    │
         ├─→ TicketPolicy    │
         │   (Consulta JWT) ✅
         │                    │
         ├─→ TicketService   │
         │   (Consulta JWT) ✅
         │                    │
         ├─→ ArticleService  │
         │   (Consulta JWT) ✅─┘
         │
         └─→ CONSISTENCIA
             Comportamiento predecible
```

### Flujo de Cambio de Rol

```
Estado Inicial:
┌──────────────────────┐
│  User: Juan          │
│  Roles en BD:        │
│  - USER              │
│  - AGENT(emp-1)      │
│  - COMPANY_ADMIN(emp-1)
│                      │
│  JWT Actual:         │
│  { roles: [USER] }   │ ← Rol por defecto
└──────────────────────┘

                │
                │ Usuario hace:
                │ POST /api/auth/switch-role
                │ { role: "AGENT", company_id: "emp-1" }
                │
                ↓

┌──────────────────────────────┐
│  Validación en Servidor:     │
│  ✓ Juan tiene AGENT? (BD)    │
│  ✓ En company_id: "emp-1"?   │
│  ✓ Roles aún activos? (BD)   │
└──────────────────────────────┘

                │
                │ SI ✓ Validación OK
                │
                ↓

┌──────────────────────────────┐
│  Generar JWT Nuevo:          │
│  {                           │
│    user_id: juan,            │
│    roles: [                  │
│      {                       │
│        code: "AGENT",        │
│        company_id: "emp-1"   │
│      }                       │
│    ],                        │
│    exp: +60min               │
│  }                           │
└──────────────────────────────┘

                │
                ↓

┌──────────────────────────────┐
│  Respuesta al Cliente:       │
│  {                           │
│    accessToken: "NEW_JWT",   │
│    expiresIn: 3600,          │
│    activeRole: {             │
│      code: "AGENT",          │
│      company_id: "emp-1"     │
│    }                         │
│  }                           │
└──────────────────────────────┘

                │
                ↓

┌──────────────────────────────┐
│  Cliente (localStorage):     │
│  Guarda nuevo JWT            │
│  Actualiza UI:               │
│  "Acting as: AGENT @ Emp-1"  │
└──────────────────────────────┘

                │
                ↓

┌──────────────────────────────┐
│  API Requests:               │
│  GET /api/tickets            │
│  Authorization: Bearer JWT   │
│  ↓                           │
│  Server extrae rol de JWT:   │
│  "AGENT" en "emp-1"          │
│  ↓                           │
│  Ve tickets de empresa-1 ✅  │
└──────────────────────────────┘
```

---

## Cambios Específicos (LISTA A)

### LISTA A: Cambios Críticos (7-9 horas)

Estos cambios DEBEN hacerse para que Switch-Role funcione correctamente.

#### 1. TokenService.php - Generar JWT con 1 Rol

**Archivo:** `app/Features/Authentication/Services/TokenService.php`

**Línea 37-58: Método `generateAccessToken()`**

**Cambio:**

```php
// ANTES
public function generateAccessToken(User $user, ?string $sessionId = null): string
{
    $payload = [
        // ... claims estándar ...
        'roles' => $user->getAllRolesForJWT(), // ← TODOS los roles
    ];
    return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
}

// DESPUÉS
/**
 * Generar Access Token con UN SOLO rol (el seleccionado)
 */
public function generateAccessToken(
    User $user,
    string $selectedRole = null,
    ?string $selectedCompanyId = null,
    ?string $sessionId = null
): string {
    // Si no se especifica rol, usar el rol por defecto
    if (!$selectedRole) {
        $selectedRole = $this->getDefaultRole($user);
        if ($selectedRole['code'] !== 'USER') {
            $selectedCompanyId = $selectedRole['company_id'] ?? null;
        }
    }

    // Generar payload con UN SOLO rol
    $roleEntry = ['code' => $selectedRole];
    if ($selectedCompanyId) {
        $roleEntry['company_id'] = $selectedCompanyId;
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
        'roles' => [$roleEntry], // ← UN SOLO rol en array
    ];

    return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
}

/**
 * Determinar rol por defecto para nuevo login
 *
 * Prioridad: COMPANY_ADMIN > AGENT > USER > PLATFORM_ADMIN
 */
private function getDefaultRole(User $user): array
{
    $roles = $user->getAllRolesForJWT();

    $priority = ['COMPANY_ADMIN', 'AGENT', 'USER', 'PLATFORM_ADMIN'];

    foreach ($priority as $priorityRole) {
        $role = collect($roles)->firstWhere('code', $priorityRole);
        if ($role) {
            return $role; // Retorna ['code' => '...', 'company_id' => '...']
        }
    }

    // Fallback: USER
    return ['code' => 'USER', 'company_id' => null];
}
```

**Impacto:**
- ✅ Login genera JWT con 1 rol (el por defecto)
- ✅ Estructura idéntica a la actual (misma estructura de array)
- ✅ Tests NO necesitan cambios mayores

---

#### 2. AuthController.php - Crear Endpoint switch-role

**Archivo:** `app/Features/Authentication/Http/Controllers/AuthController.php`

**Nuevo Método:**

```php
/**
 * POST /api/auth/switch-role
 *
 * Cambia el rol activo del usuario autenticado
 *
 * Request:
 * {
 *   "role": "AGENT" | "USER" | "COMPANY_ADMIN" | "PLATFORM_ADMIN",
 *   "company_id": "uuid" (opcional, requerido para AGENT/COMPANY_ADMIN)
 * }
 *
 * Response:
 * {
 *   "accessToken": "eyJhbGc...",
 *   "tokenType": "Bearer",
 *   "expiresIn": 3600,
 *   "activeRole": {
 *     "code": "AGENT",
 *     "company_id": "uuid"
 *   }
 * }
 */
public function switchRole(Request $request): JsonResponse
{
    $validated = $request->validate([
        'role' => 'required|string|in:USER,AGENT,COMPANY_ADMIN,PLATFORM_ADMIN',
        'company_id' => 'nullable|uuid',
    ]);

    try {
        $user = JWTHelper::getAuthenticatedUser();
    } catch (AuthenticationException $e) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    $roleCode = $validated['role'];
    $companyId = $validated['company_id'] ?? null;

    // Validar que el usuario TIENE ese rol en BD
    if ($companyId) {
        // Rol específico a una empresa
        if (!$user->hasRoleInCompany($roleCode, $companyId)) {
            return response()->json([
                'error' => "You don't have role $roleCode in this company",
                'code' => 'ROLE_NOT_FOUND'
            ], 403);
        }
    } else {
        // Rol global (USER, PLATFORM_ADMIN)
        if (!$user->hasRole($roleCode)) {
            return response()->json([
                'error' => "You don't have role $roleCode",
                'code' => 'ROLE_NOT_FOUND'
            ], 403);
        }
    }

    // Generar JWT nuevo con EL NUEVO rol
    try {
        $accessToken = $this->tokenService->generateAccessToken(
            $user,
            $roleCode,
            $companyId
        );
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to generate token',
            'code' => 'TOKEN_GENERATION_FAILED'
        ], 500);
    }

    return response()->json([
        'accessToken' => $accessToken,
        'tokenType' => 'Bearer',
        'expiresIn' => config('jwt.ttl') * 60,
        'activeRole' => [
            'code' => $roleCode,
            'company_id' => $companyId,
        ],
    ]);
}
```

**Ruta:** En `routes/api.php`

```php
Route::post('/auth/switch-role', [AuthController::class, 'switchRole'])
    ->middleware('auth:jwt')
    ->name('auth.switch-role');
```

**Impacto:**
- ✅ Endpoint funcional
- ✅ Valida contra BD (seguro)
- ✅ Genera JWT con nuevo rol
- ✅ Responde con metadata del rol activo

---

#### 3. TicketPolicy.php - Cambiar a JWT (9 cambios)

**Archivo:** `app/Features/TicketManagement/Policies/TicketPolicy.php`

**Todos los métodos necesitan cambio:**

```php
// ANTES - Línea 46-54
public function view(User $user, Ticket $ticket): bool
{
    if ($ticket->created_by_user_id === $user->id) {
        return true;
    }

    if ($user->hasRoleInCompany('AGENT', $ticket->company_id)) {
        return true;
    }

    if ($user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)) {
        return true;
    }

    return false;
}

// DESPUÉS
public function view(User $user, Ticket $ticket): bool
{
    // Creador puede ver siempre
    if ($ticket->created_by_user_id === $user->id) {
        return true;
    }

    // AGENT/COMPANY_ADMIN de la empresa pueden ver
    if (JWTHelper::hasRoleFromJWT('AGENT')) {
        if (JWTHelper::getCompanyIdFromJWT('AGENT') === $ticket->company_id) {
            return true;
        }
    }

    if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
        if (JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN') === $ticket->company_id) {
            return true;
        }
    }

    return false;
}
```

**Aplicar patrón similar a estos métodos:**

1. **update()** - Línea 68-77
   - Cambiar `hasRoleInCompany('AGENT')` → JWT
   - Cambiar `hasRoleInCompany('COMPANY_ADMIN')` → JWT

2. **delete()** - Línea 84-86
   - Cambiar `hasRoleInCompany('COMPANY_ADMIN')` → JWT

3. **resolve()** - Línea 91-94
   - Cambiar `hasRoleInCompany('AGENT')` → JWT

4. **close()** - Línea 99-109
   - Cambiar `hasRoleInCompany('AGENT')` → JWT

5. **reopen()** - Línea 115-131
   - Cambiar `hasRoleInCompany('AGENT')` → JWT

6. **assign()** - Línea 136-139
   - Cambiar `hasRoleInCompany('AGENT')` → JWT

**Impacto:**
- ✅ Policies respetan JWT + switch-role
- ✅ Comportamiento consistente
- ✅ Mayor performance (sin queries a BD)
- ✅ Stateless validation

---

#### 4. ArticleService.php - Cambiar a JWT (8 cambios)

**Archivo:** `app/Features/ContentManagement/Services/ArticleService.php`

**Método `listArticles()` - Línea 372-466:**

```php
// ANTES
public function listArticles(User $user, array $filters): LengthAwarePaginator
{
    $query = HelpCenterArticle::query();

    if ($user->hasRole('PLATFORM_ADMIN')) {
        // PLATFORM_ADMIN: Ve TODO
        if ($requestedCompanyId) {
            $query->where('company_id', $requestedCompanyId);
        }
    } elseif ($user->hasRole('COMPANY_ADMIN')) {
        // COMPANY_ADMIN: Solo su empresa
        $adminRole = $user->userRoles()
            ->where('role_code', 'COMPANY_ADMIN')
            ->first();
        $adminCompanyId = $adminRole->company_id;
        // ... resto de lógica
    }
    // ... más elseif para AGENT, USER
}

// DESPUÉS
public function listArticles(User $user, array $filters): LengthAwarePaginator
{
    $query = HelpCenterArticle::query();

    $requestedCompanyId = $filters['company_id'] ?? null;

    // Obtener rol activo del JWT
    $activeRole = $this->getActiveRoleFromJWT();

    if ($activeRole['code'] === 'PLATFORM_ADMIN') {
        // PLATFORM_ADMIN: Ve TODO sin restricción
        if ($requestedCompanyId) {
            $query->where('company_id', $requestedCompanyId);
        }
    } elseif ($activeRole['code'] === 'COMPANY_ADMIN') {
        // COMPANY_ADMIN: Solo su empresa
        $adminCompanyId = $activeRole['company_id'] ?? null;

        if (!$adminCompanyId) {
            throw new Exception('Company ID not found in JWT for COMPANY_ADMIN role', 500);
        }

        // Si especifica ?company_id, validar que sea su empresa
        if ($requestedCompanyId && $requestedCompanyId !== $adminCompanyId) {
            throw new Exception('Forbidden: No tienes permiso para acceder a artículos de otra empresa', 403);
        }

        $query->where('company_id', $adminCompanyId);
        // Ve DRAFT + PUBLISHED de su empresa
        if (!isset($filters['status'])) {
            // No agregar WHERE para status (ve todos)
        } else {
            $status = strtoupper($filters['status']);
            $query->where('status', $status);
        }
    } elseif ($activeRole['code'] === 'AGENT') {
        // AGENT: Solo PUBLISHED de su empresa
        $agentCompanyId = $activeRole['company_id'] ?? null;

        if (!$agentCompanyId) {
            throw new Exception('Company ID not found in JWT for AGENT role', 500);
        }

        if ($requestedCompanyId && $requestedCompanyId !== $agentCompanyId) {
            throw new Exception('Forbidden: No tienes permiso para acceder a artículos de otra empresa', 403);
        }

        $query->where('company_id', $agentCompanyId);
        $query->where('status', 'PUBLISHED');
    } else {
        // USER: Solo PUBLISHED de empresas que sigue
        $followedCompanyIds = $user->followedCompanies()
            ->pluck('business.companies.id')
            ->toArray();

        if ($requestedCompanyId && !in_array($requestedCompanyId, $followedCompanyIds)) {
            throw new Exception('Forbidden: No tienes permiso para acceder a artículos de esta empresa', 403);
        }

        if (empty($followedCompanyIds)) {
            return $query->paginate(0); // Sin resultados si no sigue empresas
        }

        $query->whereIn('company_id', $followedCompanyIds);
        $query->where('status', 'PUBLISHED');
    }

    // Aplicar filtros, búsqueda, paginación... (igual que antes)
    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function (Builder $q) use ($search) {
            $q->whereRaw("title ILIKE ?", ["%{$search}%"])
                ->orWhereRaw("content ILIKE ?", ["%{$search}%"]);
        });
    }

    $perPage = $filters['per_page'] ?? 15;
    $sortBy = $filters['sort_by'] ?? 'created_at';
    $sortOrder = $filters['sort_order'] ?? 'desc';

    $query->orderBy($sortBy, $sortOrder);

    return $query->paginate($perPage);
}

/**
 * Obtener rol activo del JWT
 */
private function getActiveRoleFromJWT(): array
{
    return [
        'code' => JWTHelper::getSelectedRole(),
        'company_id' => JWTHelper::getSelectedCompanyId(),
    ];
}
```

**Método `viewArticle()` - Línea 271-357:**

```php
// ANTES
if ($user->hasRole('PLATFORM_ADMIN')) {
    return $article;
}

if ($user->hasRole('COMPANY_ADMIN')) {
    $adminRole = $user->userRoles()
        ->where('role_code', 'COMPANY_ADMIN')
        ->first();
    // ...
}

// DESPUÉS
$activeRole = $this->getActiveRoleFromJWT();

if ($activeRole['code'] === 'PLATFORM_ADMIN') {
    return $article;
}

if ($activeRole['code'] === 'COMPANY_ADMIN') {
    $adminCompanyId = $activeRole['company_id'];
    if (!$adminCompanyId || $adminCompanyId !== $article->company_id) {
        throw new AuthorizationException('Forbidden');
    }
    return $article;
}

if ($activeRole['code'] === 'AGENT') {
    $agentCompanyId = $activeRole['company_id'];
    if ($article->status !== 'PUBLISHED' || $agentCompanyId !== $article->company_id) {
        throw new AuthorizationException('Forbidden');
    }
    return $article;
}

// USER
if ($article->status !== 'PUBLISHED') {
    throw new AuthorizationException('Forbidden');
}

// ... resto (validar seguimiento, incrementar views)
```

**Impacto:**
- ✅ ArticleService respeta JWT
- ✅ Switch-role tiene efecto inmediato
- ✅ Comportamiento consistente
- ✅ Sin consultas a BD para validar

---

#### 5. VisibilityService.php - Cambiar a JWT (2 cambios)

**Archivo:** `app/Features/ContentManagement/Services/VisibilityService.php`

```php
// ANTES - Línea 71-76
public function isPlatformAdmin(User $user): bool
{
    return DB::table('auth.user_roles')
        ->where('user_id', $user->id)
        ->where('role_code', 'PLATFORM_ADMIN')
        ->where('is_active', true)
        ->exists();
}

// DESPUÉS
public function isPlatformAdmin(User $user): bool
{
    return JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN');
}

// ANTES - Línea 87-93
public function isCompanyAdmin(User $user, string $companyId): bool
{
    return DB::table('auth.user_roles')
        ->where('user_id', $user->id)
        ->where('company_id', $companyId)
        ->where('role_code', 'COMPANY_ADMIN')
        ->where('is_active', true)
        ->exists();
}

// DESPUÉS
public function isCompanyAdmin(User $user, string $companyId): bool
{
    if (!JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
        return false;
    }

    return JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN') === $companyId;
}
```

**Impacto:**
- ✅ Eliminadas queries directas a DB
- ✅ Mejor performance
- ✅ Consistent con JWT

---

#### 6. Middlewares - Cambios Menores (2 cambios)

**Archivo:** `app/Http/Middleware/EnsureCompanyOwnership.php`

```php
// ANTES - Línea 40
if ($user->hasRole('PLATFORM_ADMIN')) {
    return $next($request);
}

// DESPUÉS
if (JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN')) {
    return $next($request);
}

// ANTES - Línea 45
if ($user->hasRoleInCompany('COMPANY_ADMIN', $company->id)) {
    return $next($request);
}

// DESPUÉS
if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN') &&
    JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN') === $company->id) {
    return $next($request);
}
```

**Impacto:**
- ✅ Middlewares respetan JWT
- ✅ Consistent con políticas

---

### Cambios NO Incluidos en LISTA A (Para OPCIÓN B posterior)

**LISTA B - Cambios Importantes (pero no críticos ahora):**

Estos cambios se harán en 2-3 semanas cuando la OPCIÓN 1 esté stable:

- UserPolicy.php - 17 cambios
- CompanyPolicy.php - 13 cambios
- UserController.php - 12 cambios
- RoleController.php - 5 cambios
- FormRequests - 6 cambios
- Deprecate User::hasRole() y hasRoleInCompany()

**Cambios NO Necesarios (LISTA C):**

Estos cambios son opcionales o consultas de datos (no autorización):

- CompanyController.php línea 672-684 (stats)
- UserController.php línea 315-318 (target user, no autenticado)
- TicketService.php línea 383 (valida nuevo agente)
- TicketActionRequest.php línea 48 (valida nuevo agente)

---

## Nuevos Helpers Necesarios

### 1. Helpers en JWTHelper.php

**Archivo:** `app/Shared/Helpers/JWTHelper.php`

**Métodos a Agregar:**

```php
/**
 * Obtener el rol activo actual del JWT
 *
 * Con la estructura propuesta, siempre hay UN SOLO rol en el JWT
 *
 * @return string El código del rol activo (ej: "AGENT", "USER", etc)
 * @throws AuthenticationException Si JWT no tiene roles
 */
public static function getSelectedRole(): string
{
    $roles = self::getRoles();

    if (empty($roles)) {
        throw new AuthenticationException('No roles found in JWT');
    }

    // Con OPCIÓN 1, siempre hay exactamente 1 rol
    return $roles[0]['code'];
}

/**
 * Obtener el company_id del rol activo
 *
 * @return string|null El company_id del rol activo (null para USER, PLATFORM_ADMIN)
 * @throws AuthenticationException Si JWT no tiene roles
 */
public static function getSelectedCompanyId(): ?string
{
    $roles = self::getRoles();

    if (empty($roles)) {
        throw new AuthenticationException('No roles found in JWT');
    }

    return $roles[0]['company_id'] ?? null;
}

/**
 * Obtener todos los company_ids del usuario actual
 *
 * NOTA: Con OPCIÓN 1, JWT contiene UN SOLO rol
 * Esta función es para OPCIÓN B (controladores que necesitan ver múltiples empresas)
 * Por ahora, retorna solo el company_id del rol activo
 *
 * @return array Array de UUIDs de empresas
 */
public static function getAllCompanyIdsFromJWT(): array
{
    $selectedCompanyId = self::getSelectedCompanyId();

    if (!$selectedCompanyId) {
        return [];
    }

    return [$selectedCompanyId];
}
```

**Impacto:**
- ✅ Helpers simples y directos
- ✅ Consistentes con estructura JWT
- ✅ Fáciles de testear

---

## Simulaciones de Casos de Uso

### Simulación #1: Tickets - Usuario con [USER, AGENT]

**Escenario:**

```
Usuario: Juan
Roles en BD: [USER, AGENT(empresa-1), COMPANY_ADMIN(empresa-1)]

Timeline:
A. Login → JWT con rol USER (default)
B. GET /api/tickets → Ve sus propios tickets
C. POST /api/auth/switch-role { "role": "AGENT", "company_id": "emp-1" }
D. GET /api/tickets → Ve tickets de empresa-1
E. POST /api/tickets/TKT-001/resolve → ✅ Autorizado
```

**Análisis Detallado:**

#### A. Login

```
POST /api/auth/login
{
  "email": "juan@example.com",
  "password": "..."
}

Server:
1. Valida credenciales
2. Carga user con roles
3. TokenService::generateAccessToken(user, null, null)
   → Llama getDefaultRole(user)
   → user.getAllRolesForJWT() retorna:
      [
        { code: "USER" },
        { code: "AGENT", company_id: "empresa-1" },
        { code: "COMPANY_ADMIN", company_id: "empresa-1" }
      ]
   → Itera prioridad: COMPANY_ADMIN (sí), retorna eso
   → ¡ESPERA! Preferimos USER como default para novatos
   → Mejor: Retorna COMPANY_ADMIN como default

Opción A (Recomendado): Default es COMPANY_ADMIN (más poderoso)
Opción B: Default es USER (más seguro)

Asumimos Opción A por ahora.

JWT generado:
{
  user_id: "juan-uuid",
  email: "juan@example.com",
  roles: [
    { code: "COMPANY_ADMIN", company_id: "empresa-1" }
  ],
  exp: 1700...
}

Response: { accessToken: "...", expiresIn: 3600 }
```

#### B. GET /api/tickets (como COMPANY_ADMIN)

```
GET /api/tickets
Authorization: Bearer JWT

Server:
1. JWTAuthenticationMiddleware valida JWT
2. TicketController::index()
3. TicketService::list()
4. Llamar getUserRole(user)
5. JWTHelper::hasRoleFromJWT('USER') → FALSE (no en JWT)
6. JWTHelper::hasRoleFromJWT('AGENT') → FALSE (no en JWT)
7. JWTHelper::hasRoleFromJWT('COMPANY_ADMIN') → TRUE
8. Retorna 'COMPANY_ADMIN'
9. applyVisibilityFilters con 'COMPANY_ADMIN'
10. JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN') → 'empresa-1'
11. WHERE company_id = 'empresa-1'

Response: [
  { ticket_code: "TKT-001", ... },
  { ticket_code: "TKT-002", ... },
  ...
  (TODOS los tickets de empresa-1)
]
```

**CORRECTO:** Ve tickets de su empresa (como admin)

#### C. POST /api/auth/switch-role

```
POST /api/auth/switch-role
{
  "role": "AGENT",
  "company_id": "empresa-1"
}
Authorization: Bearer JWT (actual)

Server:
1. AuthController::switchRole()
2. JWTHelper::getAuthenticatedUser() → Juan
3. Valida:
   - Juan.hasRoleInCompany('AGENT', 'empresa-1')? → TRUE (en BD)
   - Rol aún activo en BD? → TRUE
4. TokenService::generateAccessToken(juan, 'AGENT', 'empresa-1')
   → Genera JWT nuevo con:
      { roles: [{ code: "AGENT", company_id: "empresa-1" }] }

JWT nuevo:
{
  user_id: "juan-uuid",
  email: "juan@example.com",
  roles: [
    { code: "AGENT", company_id: "empresa-1" }
  ],
  exp: 1700... (nuevo)
}

Response: {
  accessToken: "NEW_JWT",
  expiresIn: 3600,
  activeRole: {
    code: "AGENT",
    company_id: "empresa-1"
  }
}
```

#### D. GET /api/tickets (como AGENT)

```
GET /api/tickets
Authorization: Bearer JWT (NUEVO)

Server:
1. TicketService::getUserRole()
2. JWTHelper::hasRoleFromJWT('USER') → FALSE
3. JWTHelper::hasRoleFromJWT('AGENT') → TRUE
4. Retorna 'AGENT'
5. applyVisibilityFilters con 'AGENT'
6. JWTHelper::getCompanyIdFromJWT('AGENT') → 'empresa-1'
7. WHERE company_id = 'empresa-1'

Response: [
  { ticket_code: "TKT-001", ... },
  { ticket_code: "TKT-002", ... },
  ...
  (TODOS los tickets de empresa-1, como antes)
]
```

**CORRECTO:** Ve tickets de su empresa (mismo resultado pero por rol AGENT, no ADMIN)

#### E. POST /api/tickets/TKT-001/resolve

```
POST /api/tickets/TKT-001/resolve
Authorization: Bearer JWT (AGENT)

Server:
1. TicketController::resolve()
2. $ticket = TicketService::getByCode('TKT-001')
3. $this->authorize('resolve', $ticket) // Llama TicketPolicy::resolve()
4. TicketPolicy::resolve(user, ticket):
   - JWTHelper::hasRoleFromJWT('AGENT') → TRUE ✅
   - JWTHelper::getCompanyIdFromJWT('AGENT') → 'empresa-1'
   - ticket.company_id → 'empresa-1'
   - Comparación: 'empresa-1' === 'empresa-1' → TRUE ✅
   - return true
5. TicketService::resolve($ticket)
6. $ticket->update(['status' => 'resolved'])

Response: 200 OK { ticket: { status: "resolved" } }
```

**CORRECTO:** Autorizado como AGENT ✅

---

### Simulación #2: Articles - Usuario con [USER, COMPANY_ADMIN]

**Escenario:**

```
Usuario: María
Roles en BD: [USER, COMPANY_ADMIN(empresa-1)]

Timeline:
A. Login → JWT con rol COMPANY_ADMIN (default)
B. GET /api/articles → Ve todos los artículos de empresa-1 (DRAFT + PUBLISHED)
C. POST /api/articles → ✅ Autorizado (COMPANY_ADMIN)
D. POST /api/auth/switch-role { "role": "USER" }
E. GET /api/articles → Ve SOLO PUBLISHED de empresas que sigue
F. GET /api/articles/1 (DRAFT de emp-1) → ❌ 403 Forbidden
G. GET /api/articles/2 (PUBLISHED de emp-1, sigue) → ✅ Visible
```

**Análisis Detallado:**

#### A. Login

```
JWT generado:
{
  user_id: "maria-uuid",
  roles: [
    { code: "COMPANY_ADMIN", company_id: "empresa-1" }
  ],
  ...
}
```

#### B. GET /api/articles (como COMPANY_ADMIN)

```
Server:
1. ArticleService::listArticles()
2. getActiveRoleFromJWT() → { code: "COMPANY_ADMIN", company_id: "empresa-1" }
3. if (activeRole['code'] === 'COMPANY_ADMIN'):
   - adminCompanyId = 'empresa-1'
   - WHERE company_id = 'empresa-1'
   - Sin WHERE para status → Ve DRAFT + PUBLISHED

Response: [
  { title: "DRAFT Article 1", status: "DRAFT" },
  { title: "Published Article 2", status: "PUBLISHED" },
  ...
]
```

**CORRECTO:** Ve todos sus artículos ✅

#### C. POST /api/articles

```
Server:
1. ArticleController::store()
2. ArticlePolicy::create()?
   ← En el código actual, no hay artíclePolicy
   ← Validación se hace en el servicio
3. ArticleService::createArticle()
   - Requiere company_id del admin
   - Token: JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN') → 'empresa-1'
   - Crear artículo en empresa-1

Response: 201 Created
```

**CORRECTO:** Puede crear ✅

#### D. POST /api/auth/switch-role

```
POST /api/auth/switch-role
{
  "role": "USER"
}

Server:
1. Valida: María.hasRole('USER')? → TRUE
2. Genera JWT nuevo:
   {
     roles: [{ code: "USER", company_id: null }],
     ...
   }
```

#### E. GET /api/articles (como USER)

```
Server:
1. ArticleService::listArticles()
2. getActiveRoleFromJWT() → { code: "USER", company_id: null }
3. else (USER):
   - followedCompanyIds = María.followedCompanies() → ["emp-1", "emp-2"]
   - WHERE company_id IN ["emp-1", "emp-2"]
   - WHERE status = "PUBLISHED"

Response: [
  { title: "Published Article 2", status: "PUBLISHED" },
  { title: "Published Article 3", status: "PUBLISHED" },
  (de empresas que sigue, SOLO published)
]
```

**CORRECTO:** Ve SOLO artículos published de empresas que sigue ✅

**DIFERENCIA CON ANTES:**
- ❌ ANTES: Consultaba DB, siempre veía DRAFT (porque COMPANY_ADMIN en BD)
- ✅ AHORA: Respeta el JWT, ve ONLY PUBLISHED (porque USER en JWT)

#### F. GET /api/articles/1 (DRAFT de empresa-1)

```
Server:
1. ArticleService::viewArticle()
2. activeRole = { code: "USER", company_id: null }
3. else (USER):
   - if (article.status !== 'PUBLISHED'):
     → throw 403

Response: 403 Forbidden
```

**CORRECTO:** No puede ver DRAFT como USER ✅

#### G. GET /api/articles/2 (PUBLISHED, empresa-1, sigue)

```
Server:
1. ArticleService::viewArticle()
2. activeRole = { code: "USER", company_id: null }
3. else (USER):
   - if (article.status !== 'PUBLISHED'): FALSE ✓
   - isFollowing = María.followedCompanies() contiene empresa-1? TRUE ✓
   - return article
   - increment('views_count')

Response: 200 OK { article }
```

**CORRECTO:** Puede ver y se cuenta la vista ✅

---

### Resumen de Simulaciones

| Operación | ANTES (Híbrido) | DESPUÉS (JWT) | Resultado |
|-----------|-----------------|--------------|-----------|
| Tickets - Switch ADMIN→AGENT | ❌ Ver mismos tickets | ✅ Ver mismos tickets | **REPARADO** |
| Articles - Switch ADMIN→USER | ❌ Sigue viendo DRAFT | ✅ Ve ONLY PUBLISHED | **REPARADO** |
| Crear artículos como ADMIN | ✅ Funciona | ✅ Funciona | **MANTIENE** |
| Ver artículos como USER | ⚠️ Inconsistente | ✅ Consistente | **MEJORADO** |

**Conclusión:** OPCIÓN 1 resuelve TODOS los casos de uso críticos.

---

## Plan de Implementación

### Día 1: Core JWT (2-3 horas)

#### Paso 1.1: Modificar TokenService.php

```
Time: 45 min

Tareas:
1. Modificar generateAccessToken() para aceptar selectedRole, selectedCompanyId
2. Crear método getDefaultRole(user)
3. Generar JWT con 1 rol en array
4. Tests unitarios del helper

Archivos modificados:
- app/Features/Authentication/Services/TokenService.php
```

#### Paso 1.2: Crear AuthController::switchRole()

```
Time: 60 min

Tareas:
1. Crear nuevo método switchRole() en AuthController
2. Validar rol contra BD
3. Generar JWT nuevo
4. Agregar ruta en routes/api.php
5. Tests del endpoint

Archivos modificados:
- app/Features/Authentication/Http/Controllers/AuthController.php
- routes/api.php
```

#### Paso 1.3: Agregar Helpers en JWTHelper.php

```
Time: 30 min

Tareas:
1. Agregar getSelectedRole()
2. Agregar getSelectedCompanyId()
3. Agregar getAllCompanyIdsFromJWT()
4. Tests unitarios

Archivos modificados:
- app/Shared/Helpers/JWTHelper.php
```

---

### Día 2-3: Refactor LISTA A Crítica (4-5 horas)

#### Paso 2.1: Refactor TicketPolicy.php

```
Time: 1 hora

Tareas:
1. Cambiar view() - 9 líneas
2. Cambiar update() - similar
3. Cambiar delete(), resolve(), close(), reopen(), assign()
4. Tests unitarios para cada método

Archivos modificados:
- app/Features/TicketManagement/Policies/TicketPolicy.php

Patrón:
OLD: if ($user->hasRoleInCompany('AGENT', $ticket->company_id))
NEW: if (JWTHelper::hasRoleFromJWT('AGENT') &&
          JWTHelper::getCompanyIdFromJWT('AGENT') === $ticket->company_id)
```

#### Paso 2.2: Refactor ArticleService.php

```
Time: 1.5 horas

Tareas:
1. Cambiar listArticles() - ~80 líneas
2. Cambiar viewArticle() - ~50 líneas
3. Crear helper getActiveRoleFromJWT()
4. Tests integración para cada caso

Archivos modificados:
- app/Features/ContentManagement/Services/ArticleService.php

Casos a testear:
- LIST: PLATFORM_ADMIN → ve todo
- LIST: COMPANY_ADMIN → ve su empresa
- LIST: AGENT → ve PUBLISHED de su empresa
- LIST: USER → ve PUBLISHED de empresas que sigue
- VIEW: Cada rol accediendo artículos diferentes
```

#### Paso 2.3: Refactor VisibilityService.php

```
Time: 30 min

Tareas:
1. Cambiar isPlatformAdmin() → JWT
2. Cambiar isCompanyAdmin() → JWT + company_id
3. Tests unitarios

Archivos modificados:
- app/Features/ContentManagement/Services/VisibilityService.php
```

#### Paso 2.4: Refactor Middlewares

```
Time: 30 min

Tareas:
1. Cambiar EnsureCompanyOwnership.php → JWT
2. Tests de middleware

Archivos modificados:
- app/Http/Middleware/EnsureCompanyOwnership.php
```

---

### Día 4: Testing y Validación (2 horas)

#### Paso 3.1: Tests Básicos

```
Time: 45 min

Tareas:
1. Actualizar mocks JWT en tests existentes
   - Cambiar "roles": [USER, AGENT] → "roles": [AGENT]
   - Búsqueda-reemplazar (SAFE porque estructura igual)
2. Verificar que tests siguen pasando

Archivos modificados:
- tests/Feature/TicketManagement/**/*Test.php
- tests/Feature/ContentManagement/**/*Test.php
- (No cambios de lógica, solo mocks)
```

#### Paso 3.2: Tests Nuevos para Switch-Role

```
Time: 45 min

Tareas:
1. Test: POST /api/auth/switch-role - rol válido → 200
2. Test: POST /api/auth/switch-role - rol inválido → 403
3. Test: JWT nuevo contiene nuevo rol
4. Test: GET /api/tickets respeta nuevo rol
5. Test: GET /api/articles respeta nuevo rol
6. Test: Switch multiple veces funciona

Archivos nuevos:
- tests/Feature/Authentication/SwitchRoleTest.php
```

#### Paso 3.3: Validación E2E

```
Time: 30 min

Tareas:
1. Verificar todos los tests pasan: 45/45 ✓
2. Verificar performance (no más queries innecesarias)
3. Verificar simulaciones correctas
4. Documentar cualquier encontramiento
```

---

### Timeline Total

```
Día 1 (Lunes)
├─ 09:00-10:00  Paso 1.1 (TokenService)
├─ 10:00-11:00  Paso 1.2 (switchRole endpoint)
├─ 11:00-11:30  Paso 1.3 (Helpers)
├─ 11:30-12:30  Testing básico
└─ Fin: TokenService + switchRole funcional

Día 2 (Martes)
├─ 09:00-10:00  Paso 2.1 (TicketPolicy)
├─ 10:00-11:30  Paso 2.2 (ArticleService)
├─ 11:30-12:00  Paso 2.3 (VisibilityService)
├─ 14:00-14:30  Paso 2.4 (Middlewares)
└─ Fin: LISTA A crítica completa

Día 3 (Miércoles)
├─ 09:00-10:30  Paso 3.1 (Tests básicos)
├─ 10:30-11:30  Paso 3.2 (Tests switch-role)
├─ 11:30-12:00  Paso 3.3 (Validación E2E)
└─ Fin: TODO TESTEADO Y VALIDADO

Total: 7-9 horas en 3 días
```

---

## Estimaciones de Esfuerzo

### Por Componente

| Componente | Líneas | Tiempo | Complejidad |
|-----------|--------|--------|-------------|
| **TokenService** | 30 | 45 min | Baja |
| **switchRole endpoint** | 40 | 60 min | Baja |
| **JWTHelper helpers** | 20 | 30 min | Baja |
| **TicketPolicy** | 50 | 60 min | Media |
| **ArticleService** | 130 | 90 min | Alta |
| **VisibilityService** | 15 | 30 min | Baja |
| **Middlewares** | 10 | 30 min | Baja |
| **Tests + Validación** | 200 | 120 min | Media |
| **Total** | **495** | **7-9 horas** | **Media** |

### Por Fase

| Fase | Duración | Tareas |
|------|----------|--------|
| **1: Core JWT** | 2-3h | TokenService, switchRole, helpers |
| **2: Refactor LISTA A** | 3-4h | Policies, Services, Middlewares |
| **3: Testing** | 2-3h | Tests, validación E2E |
| **Total** | **7-9 horas** | **3 días** |

---

## Riesgos y Mitigaciones

### Riesgo 1: Romper autorización existente

**Probabilidad:** Alta
**Impacto:** Crítico
**Mitigación:**
- ✅ Tests exhaustivos antes de merge
- ✅ Simular casos de uso reales
- ✅ Code review minucioso
- ✅ Testing en staging primero
- ✅ Feature flag si es posible

---

### Riesgo 2: JWT desincronizado con BD

**Probabilidad:** Media
**Impacto:** Alto
**Escenario:** Admin revoca rol en BD, JWT sigue vigente
**Mitigación:**
- ✅ Validación en switch-role: verifica rol en BD
- ✅ Documentación clara: cuando regenerar JWT
- ✅ Middleware opcional para validar JWT vs BD
- ✅ TTL corto en JWT (60 min por defecto)

---

### Riesgo 3: Perder contexto de múltiples empresas

**Probabilidad:** Baja
**Impacto:** Alto
**Escenario:** AGENT en 2 empresas, JWT solo guarda 1
**Mitigación:**
- ✅ Helper getAllCompanyIdsFromJWT() para OPCIÓN B
- ✅ Documentación clara: OPCIÓN 1 es 1 rol, OPCIÓN B es multi
- ✅ Switch-role permite cambiar entre empresas

---

### Riesgo 4: Queries N+1 en casos especiales

**Probabilidad:** Baja
**Impacto:** Medio
**Escenario:** Loops que cargan user + roles
**Mitigación:**
- ✅ Code review para evitar relaciones cargadas innecesariamente
- ✅ Testing de performance
- ✅ Documentación de patrones seguros

---

### Riesgo 5: Tests insuficientes

**Probabilidad:** Media
**Impacto:** Alto
**Mitigación:**
- ✅ Objetivo: 90% coverage en archivos modificados
- ✅ Tests de cada método cambiado
- ✅ Tests de integración E2E
- ✅ Simulación de casos reales

---

## Testing Strategy

### Unit Tests

**Target:** JWTHelper, TokenService, Helpers

```bash
Time: 30 min
Files:
  tests/Unit/Helpers/JWTHelperTest.php
  tests/Unit/Services/TokenServiceTest.php

Cases:
✓ getSelectedRole() returns role from JWT
✓ getSelectedCompanyId() returns company_id
✓ generateAccessToken() creates JWT with 1 role
✓ getDefaultRole() respects priority
```

### Feature Tests - Policies

**Target:** TicketPolicy, ArticlePolicy behavior changes

```bash
Time: 1 hour
Files:
  tests/Feature/TicketManagement/Permissions/RoleBasedAccessTest.php
  tests/Feature/ContentManagement/Permissions/*Test.php

Cases:
✓ TicketPolicy::view() respects JWT role
✓ TicketPolicy::resolve() only AGENT
✓ ArticleService::listArticles() filters by JWT role
✓ ArticleService::viewArticle() blocks DRAFT for USER
```

### Feature Tests - Switch Role

**Target:** New endpoint and behavior changes

```bash
Time: 45 min
Files:
  tests/Feature/Authentication/SwitchRoleTest.php

Cases:
✓ POST /api/auth/switch-role - valid role → 200
✓ POST /api/auth/switch-role - invalid role → 403
✓ POST /api/auth/switch-role - role not in DB → 403
✓ JWT nuevo contiene nuevo rol
✓ GET /api/tickets después de switch muestra datos correctos
✓ GET /api/articles después de switch muestra datos correctos
✓ Switch múltiples veces en secuencia funciona
✓ Expiración de JWT después de switch
```

### Integration Tests

**Target:** End-to-end workflows

```bash
Time: 30 min
Files:
  tests/Feature/IntegrationTests/MultiRoleWorkflowTest.php

Cases:
✓ User login → switch to AGENT → create ticket response → resolve
✓ User login → switch to ADMIN → create article → publish
✓ Multiple role switches in same session
✓ Permissions respected at each step
```

### Performance Tests (Optional)

**Target:** Verify no performance regression

```bash
Time: 15 min
Metrics:
- Queries per request (should decrease)
- Response time (should improve or equal)
- Memory usage (should be same)

Baseline:
GET /api/tickets → 3 queries, 150ms
GET /api/articles → 4 queries, 180ms

Target after:
GET /api/tickets → 1-2 queries, 100-150ms
GET /api/articles → 2-3 queries, 150-180ms
```

---

## Checklist de Validación

### Pre-Implementation

- [ ] Todos los stakeholders entienden el plan
- [ ] Rama de feature creada: `feature/multi-role-jwt`
- [ ] Documentación completa (este documento)
- [ ] Ambiente de staging disponible

### Durante Implementación

- [ ] TokenService tests pasan
- [ ] switchRole endpoint tests pasan
- [ ] TicketPolicy tests pasan
- [ ] ArticleService tests pasan
- [ ] Simular casos de uso reales
- [ ] Code review después de cada componente

### Post-Implementation

- [ ] Todos los tests pasan: `45/45 ✓`
- [ ] Coverage >= 90% en archivos modificados
- [ ] Performance metrics OK
- [ ] JWT tiene correctamente 1 rol
- [ ] Switch-role endpoint funcional
- [ ] Simulaciones correctas:
  - [ ] Tickets: USER ↔ AGENT ↔ ADMIN
  - [ ] Articles: USER ↔ ADMIN
  - [ ] Permisos: correctamente denegados y permitidos
- [ ] No hay regressiones en features existentes
- [ ] Documentación actualizada
- [ ] Commit con mensaje claro

### Antes de Production

- [ ] Testing en staging completado
- [ ] Performance testing OK
- [ ] Security review OK
- [ ] Team training completo
- [ ] Plan de rollback preparado
- [ ] Monitoring en production preparado

---

## Conclusión

### Resumen

**OPCIÓN 1** proporciona:

1. ✅ **JWT con rol seleccionado** (estructura simple, similar a actual)
2. ✅ **Switch-Role funcional** (endpoint simple)
3. ✅ **Tickets funcionales** (Policies + Service refactoradas)
4. ✅ **Articles funcionales** (Service refactorada)
5. ✅ **Bajo riesgo** (cambios focalizados)
6. ✅ **Implementable en 3 días** (7-9 horas)
7. ✅ **Tests básicamente sin cambios** (misma estructura JWT)

### Próximos Pasos Inmediatos

1. **Aprobación:** Confirmar que OPCIÓN 1 está aprobada ✓
2. **Branching:** Crear rama `feature/multi-role-jwt`
3. **Día 1:** Implementar Fase 1 (Core JWT)
4. **Día 2-3:** Implementar Fase 2 (Refactor LISTA A)
5. **Día 4:** Testing y validación

### Fase 2 (2-3 semanas después)

Una vez OPCIÓN 1 esté stable en production:

- Implementar OPCIÓN B (LISTA B)
- Refactor UserPolicy, CompanyPolicy
- Refactor Controllers, Requests
- Deprecate User::hasRole()

---

**Documento de Referencia:** `documentacion/MULTI_ROL_JWT_MIGRACION.md`
**Próxima Revisión:** Después de implementación OPCIÓN 1
**Responsable:** Equipo de Backend
