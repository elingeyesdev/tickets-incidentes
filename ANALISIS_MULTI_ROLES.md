# 🔐 Análisis: Problema de Diseño con Usuarios Multi-Rol

## 📋 Índice
1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Diagnóstico del Problema](#diagnóstico-del-problema)
3. [Estado Actual del Sistema](#estado-actual-del-sistema)
4. [Tabla de Endpoints Afectados](#tabla-de-endpoints-afectados)
5. [Edge Cases y Escenarios Problemáticos](#edge-cases-y-escenarios-problemáticos)
6. [Solución Propuesta: Sistema de Rol Activo](#solución-propuesta-sistema-de-rol-activo)
7. [Plan de Implementación](#plan-de-implementación)
8. [Respuestas a Preguntas Específicas](#respuestas-a-preguntas-específicas)
9. [Estimación de Complejidad](#estimación-de-complejidad)

---

## 🎯 Resumen Ejecutivo

### El Problema
El sistema actual permite a los usuarios tener múltiples roles (USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN), pero **la API no tiene forma de saber cuál rol usar** cuando un usuario con múltiples roles hace una petición. Esto causa:

1. **Comportamiento inconsistente**: El endpoint `/tickets` podría mostrar datos diferentes dependiendo de cómo el código decide qué rol priorizar
2. **Fugas de datos potenciales**: Un usuario podría ver más o menos datos de lo que debería en su contexto actual
3. **Confusión de UX**: El usuario no sabe "como quién" está operando

### La Solución (TL;DR)
Implementar un **sistema de "Rol Activo"** donde:
- El usuario selecciona explícitamente un rol al iniciar sesión (ya existe UI: `/role-selector`)
- El rol seleccionado se incluye en el JWT como claim `active_role`
- Todos los endpoints usan el `active_role` para filtrar datos
- Se puede cambiar de rol sin re-login mediante un endpoint `/auth/switch-role`

### Complejidad Estimada
⚠️ **MEDIA-ALTA** - Requiere cambios en:
- ~15-20 archivos de servicios/controladores
- Modificación del JWT y TokenService
- Actualización de JWTHelper
- Testing exhaustivo

---

## 🔍 Diagnóstico del Problema

### ¿Cómo funciona actualmente?

#### 1. El JWT contiene TODOS los roles del usuario
```php
// TokenService.php - generateAccessToken()
$payload = [
    // ...
    'roles' => $user->getAllRolesForJWT(),  // ← Array con TODOS los roles
];

// Ejemplo de payload.roles para un usuario multi-rol:
[
    {"code": "USER", "company_id": null},
    {"code": "AGENT", "company_id": "uuid-empresa-1"},
    {"code": "COMPANY_ADMIN", "company_id": "uuid-empresa-2"}
]
```

#### 2. Los endpoints deciden arbitrariamente qué rol usar
```php
// TicketService.php - getUserRole() - PROBLEMÁTICO
private function getUserRole(User $user): string
{
    // ⚠️ PROBLEMA: Retorna el PRIMER rol que encuentre
    if (JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN')) {
        return 'PLATFORM_ADMIN';  // ← Si tiene este, SIEMPRE lo usa
    }
    if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
        return 'COMPANY_ADMIN';   // ← Aunque también sea AGENT
    }
    if (JWTHelper::hasRoleFromJWT('AGENT')) {
        return 'AGENT';
    }
    return 'USER';
}
```

#### 3. El frontend guarda el rol en localStorage (pero la API lo ignora)
```javascript
// role-selector.blade.php - selectRole()
localStorage.setItem('active_role', JSON.stringify(activeRole));
// ⚠️ PROBLEMA: La API nunca lee este valor
```

### ¿Por qué esto es un problema?

| Escenario | Comportamiento Actual | Comportamiento Esperado |
|-----------|----------------------|------------------------|
| Usuario es COMPANY_ADMIN + USER | Ve TODOS los tickets de su empresa (actúa como admin) | Debería poder elegir ver solo SUS tickets (como USER) |
| Usuario es AGENT en Empresa A + COMPANY_ADMIN en Empresa B | Siempre ve datos de Empresa B (COMPANY_ADMIN tiene prioridad) | Debería poder elegir con qué empresa/rol trabajar |
| Usuario es PLATFORM_ADMIN + AGENT | Siempre ve TODOS los tickets globales | Podría querer ver solo los tickets asignados a él como AGENT |

---

## 📊 Estado Actual del Sistema

### Arquitectura de Roles

```
┌─────────────────────────────────────────────────────────┐
│                      auth.users                         │
│  id, email, password_hash, status...                    │
└────────────────────────┬────────────────────────────────┘
                         │ 1:N
                         ▼
┌─────────────────────────────────────────────────────────┐
│                    auth.user_roles                      │
│  id, user_id, role_code, company_id, is_active         │
│                                                         │
│  CONSTRAINTS:                                           │
│  - COMPANY_ADMIN y AGENT requieren company_id          │
│  - PLATFORM_ADMIN y USER tienen company_id = NULL      │
└─────────────────────────────────────────────────────────┘
```

### Roles Disponibles

| Rol | Descripción | Requiere company_id | Puede ser múltiple |
|-----|-------------|--------------------|--------------------|
| `PLATFORM_ADMIN` | Admin global de la plataforma | ❌ No | ❌ No (único) |
| `COMPANY_ADMIN` | Admin de una empresa | ✅ Sí | ✅ Sí (diferentes empresas) |
| `AGENT` | Agente de soporte | ✅ Sí | ✅ Sí (diferentes empresas) |
| `USER` | Usuario final | ❌ No | ❌ No |

### Combinaciones de Roles Válidas

| Combinación | ¿Es válida? | Caso de uso |
|-------------|-------------|-------------|
| USER + AGENT | ✅ | Empleado que también puede crear tickets como cliente |
| USER + COMPANY_ADMIN | ✅ | Dueño de empresa que también usa el helpdesk de otras |
| AGENT + COMPANY_ADMIN | ✅ | Admin que también atiende tickets |
| PLATFORM_ADMIN + cualquier | ⚠️ | Raro pero posible |

---

## 📑 Tabla de Endpoints Afectados

### 🔴 Alta Prioridad (Afectan visibilidad de datos críticos)

| Endpoint | Método | Archivo | Problema | Impacto |
|----------|--------|---------|----------|---------|
| `/api/tickets` | GET | `TicketService.php:108` | `getUserRole()` usa prioridad fija | Usuario multi-rol ve datos del rol "más alto" siempre |
| `/api/tickets` | GET | `TicketService.php:225-234` | `applyVisibilityFilters()` usa primer `company_id` encontrado | Datos de empresa incorrecta |
| `/api/announcements` | GET | `AnnouncementController.php:192` | `hasRole()` + `getCompanyIdFromJWT()` sin contexto | Anuncios de empresa incorrecta |
| `/api/analytics/company-dashboard` | GET | `AnalyticsController.php:47-56` | Usa primer COMPANY_ADMIN | Dashboard de empresa incorrecta |
| `/api/analytics/agent-dashboard` | GET | `AnalyticsController.php:124-134` | Usa primer AGENT | Métricas de empresa incorrecta |
| `/api/activity-logs` | GET | `ActivityLogController.php:97` | `isAdmin` sin contexto específico | Logs de empresa incorrecta |

### 🟡 Media Prioridad (Gestión de recursos)

| Endpoint | Método | Archivo | Problema |
|----------|--------|---------|----------|
| `/api/areas` | POST/PUT/DELETE | `AreaController.php` | Usa `getCompanyIdFromJWT('COMPANY_ADMIN')` |
| `/api/tickets/categories` | POST/PUT/DELETE | `CategoryController.php` | Usa `getCompanyIdFromJWT('COMPANY_ADMIN')` |
| `/api/announcements/maintenance` | POST | `MaintenanceAnnouncementController.php` | Usa `getCompanyIdFromJWT('COMPANY_ADMIN')` |
| `/api/announcements/incidents` | POST | `IncidentAnnouncementController.php` | Usa `getCompanyIdFromJWT('COMPANY_ADMIN')` |
| `/api/announcements/news` | POST | `NewsAnnouncementController.php` | Usa `getCompanyIdFromJWT('COMPANY_ADMIN')` |
| `/api/announcements/alerts` | POST | `AlertAnnouncementController.php` | Usa `getCompanyIdFromJWT('COMPANY_ADMIN')` |
| `/api/help-center/articles` | POST/PUT | `ArticleController.php` | Usa `getCompanyIdFromJWT('COMPANY_ADMIN')` |

### 🟢 Baja Prioridad (Policies - verificación puntual)

| Endpoint | Archivo | Problema |
|----------|---------|----------|
| `/api/tickets/{ticket}` | `TicketPolicy.php` | Usa fallback `AGENT ?? COMPANY_ADMIN` |
| `/api/tickets/{ticket}/responses` | `TicketResponsePolicy.php` | Usa fallback |
| `/api/tickets/{ticket}/attachments` | `TicketAttachmentPolicy.php` | Usa fallback |
| `/api/users/{id}` | `UserPolicy.php` | Checks `if/elseif` secuenciales |

### 📊 Resumen Estadístico

| Categoría | Cantidad de Endpoints |
|-----------|----------------------|
| Alta Prioridad | 6 |
| Media Prioridad | 8 |
| Baja Prioridad | 4 |
| **Total Afectados** | **18** |

---

## ⚠️ Edge Cases y Escenarios Problemáticos

### Escenario 1: El "Dueño Multi-Empresa"
```
Usuario: Juan
Roles:
  - COMPANY_ADMIN en "TechCorp" (company_id: aaa-111)
  - COMPANY_ADMIN en "DataInc" (company_id: bbb-222)

Problema:
  GET /api/tickets → ¿Tickets de cuál empresa?
  GET /api/analytics/company-dashboard → ¿Dashboard de cuál empresa?
  POST /api/tickets/categories → ¿Categoría para cuál empresa?
  
Comportamiento actual:
  - Retorna datos de la PRIMERA empresa encontrada en el JWT
  - No hay forma de cambiar de contexto
```

### Escenario 2: El "Empleado que también es Cliente"
```
Usuario: María
Roles:
  - AGENT en "SupportCo" (company_id: ccc-333)
  - USER (sin company_id)

Problema:
  GET /api/tickets → ¿Todos los tickets de SupportCo o solo los suyos?
  
Comportamiento actual:
  - Como AGENT tiene "prioridad" sobre USER
  - María SIEMPRE ve todos los tickets de SupportCo
  - NO puede ver solo los tickets que ella creó como cliente
```

### Escenario 3: El "Super Admin que también trabaja"
```
Usuario: Carlos (fundador)
Roles:
  - PLATFORM_ADMIN
  - COMPANY_ADMIN en "StartupX" (company_id: ddd-444)

Problema:
  GET /api/tickets → Ve TODOS los tickets de TODA la plataforma
  
Comportamiento actual:
  - PLATFORM_ADMIN tiene máxima prioridad
  - Carlos no puede "bajar" a ver solo su empresa
  - La UI de PLATFORM_ADMIN no tiene filtros por empresa
```

### Escenario 4: Refresh Token y Cambio de Rol
```
Usuario: Ana
Roles:
  - USER
  - AGENT en "HelpMe"

Flujo problemático:
  1. Ana hace login → selecciona rol AGENT
  2. Trabaja 4 horas atendiendo tickets
  3. Access token expira → Refresh automático
  4. ¿El nuevo token mantiene el rol AGENT? 
  
Problema actual:
  - El refresh token NO guarda el rol seleccionado
  - El nuevo access token tiene todos los roles pero no "active_role"
  - Ana podría perder su contexto de trabajo
```

### Escenario 5: Primer Login sin Selección de Rol
```
Usuario: Pedro (nuevo)
Roles:
  - USER (asignado en registro)
  - AGENT en "TestCo" (asignado después por admin)

Flujo:
  1. Pedro hace login
  2. Frontend detecta 2 roles → redirige a /role-selector
  3. Pedro cierra el navegador sin seleccionar
  4. Próximo login: ¿qué pasa?

Problema actual:
  - El código asume que si hay multiple roles, debe seleccionar
  - Pero algunos endpoints funcionan sin active_role (usan fallback)
  - Comportamiento inconsistente
```

---

## 💡 Solución Propuesta: Sistema de Rol Activo

### Arquitectura de la Solución

```
┌─────────────────────────────────────────────────────────────────┐
│                         JWT Payload                             │
│  {                                                              │
│    "sub": "user-uuid",                                          │
│    "roles": [...todos los roles...],                            │
│    "active_role": {           ← NUEVO                           │
│      "code": "COMPANY_ADMIN",                                   │
│      "company_id": "uuid-empresa"                               │
│    }                                                            │
│  }                                                              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    JWTHelper (modificado)                       │
│                                                                 │
│  getActiveRole(): array                                         │
│  getActiveRoleCode(): string                                    │
│  getActiveCompanyId(): ?string                                  │
│  isActiveRole(string $roleCode): bool                           │
└─────────────────────────────────────────────────────────────────┘
```

### Flujo Propuesto

```
┌─────────────┐     ┌─────────────┐     ┌─────────────────┐
│   Login     │────▶│ ¿Multiple   │─YES─▶│ /role-selector  │
│  /api/auth  │     │   Roles?    │      │  (UI existente) │
│   /login    │     └─────────────┘      └────────┬────────┘
└─────────────┘            │                      │
                          NO                      │
                           │                      ▼
                           │             ┌─────────────────┐
                           │             │ POST /api/auth  │
                           │             │  /select-role   │
                           │             └────────┬────────┘
                           │                      │
                           ▼                      ▼
                    ┌─────────────────────────────────────┐
                    │  JWT generado con active_role       │
                    │  - Si 1 rol: auto-seleccionado      │
                    │  - Si múltiples: el que eligió      │
                    └──────────────────┬──────────────────┘
                                       │
                                       ▼
                    ┌─────────────────────────────────────┐
                    │  Cualquier endpoint usa             │
                    │  JWTHelper::getActiveRoleCode()     │
                    │  en lugar de hasRoleFromJWT()       │
                    └─────────────────────────────────────┘
```

### Nuevos Endpoints Requeridos

#### 1. POST `/api/auth/select-role`
Selecciona el rol activo después del login (o cambia el rol actual).

```php
// Request
{
    "role_code": "COMPANY_ADMIN",
    "company_id": "uuid-empresa"  // null para PLATFORM_ADMIN/USER
}

// Response
{
    "accessToken": "nuevo-jwt-con-active_role",
    "refreshToken": "en-cookie",
    "user": { ... },
    "activeRole": {
        "code": "COMPANY_ADMIN",
        "company_id": "uuid-empresa",
        "company_name": "Mi Empresa"
    }
}
```

#### 2. GET `/api/auth/available-roles`
Lista los roles disponibles para cambiar (útil para el switcher en UI).

```php
// Response
{
    "roles": [
        {
            "code": "USER",
            "company_id": null,
            "company_name": null,
            "dashboard_path": "/app/user/dashboard"
        },
        {
            "code": "COMPANY_ADMIN",
            "company_id": "uuid-empresa",
            "company_name": "Mi Empresa",
            "dashboard_path": "/app/company/dashboard"
        }
    ],
    "active_role": {
        "code": "COMPANY_ADMIN",
        "company_id": "uuid-empresa"
    }
}
```

### Cambios en JWTHelper

```php
<?php
// Nuevos métodos a agregar en JWTHelper.php

/**
 * Get the active role from JWT.
 * @return array ['code' => string, 'company_id' => ?string]
 */
public static function getActiveRole(): array
{
    $payload = request()->attributes->get('jwt_payload');
    
    if (!$payload) {
        throw new AuthenticationException('JWT payload not found');
    }
    
    $activeRole = $payload['active_role'] ?? null;
    
    if (!$activeRole) {
        // Fallback: si no hay active_role, usar el primer rol
        // Esto es para backward compatibility durante migración
        $roles = self::getRoles();
        return $roles[0] ?? ['code' => 'USER', 'company_id' => null];
    }
    
    return is_object($activeRole) ? (array) $activeRole : $activeRole;
}

/**
 * Get the active role code.
 */
public static function getActiveRoleCode(): string
{
    return self::getActiveRole()['code'];
}

/**
 * Get the company_id of the active role.
 */
public static function getActiveCompanyId(): ?string
{
    return self::getActiveRole()['company_id'] ?? null;
}

/**
 * Check if the active role matches.
 */
public static function isActiveRole(string $roleCode): bool
{
    return self::getActiveRoleCode() === $roleCode;
}
```

### Cambios en TokenService

```php
<?php
// En TokenService.php - generateAccessToken()

public function generateAccessToken(User $user, ?string $sessionId = null, ?array $activeRole = null): string
{
    $roles = $user->getAllRolesForJWT();
    
    // Determinar active_role
    if ($activeRole === null) {
        // Auto-seleccionar si solo tiene 1 rol
        $activeRole = count($roles) === 1 ? $roles[0] : null;
    }
    
    $payload = [
        'iss' => config('jwt.issuer'),
        'aud' => config('jwt.audience'),
        'iat' => time(),
        'exp' => time() + ((int) config('jwt.ttl') * 60),
        'sub' => $user->id,
        'user_id' => $user->id,
        'email' => $user->email,
        'session_id' => $sessionId ?? Str::random(32),
        'roles' => $roles,
        'active_role' => $activeRole,  // ← NUEVO CLAIM
    ];

    return JWT::encode($payload, config('jwt.secret'), config('jwt.algo'));
}
```

### Cambios en TicketService (Ejemplo)

```php
<?php
// ANTES (problemático)
private function getUserRole(User $user): string
{
    if (JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN')) {
        return 'PLATFORM_ADMIN';
    }
    // ... cadena if/elseif
}

// DESPUÉS (correcto)
private function getUserRole(User $user): string
{
    return JWTHelper::getActiveRoleCode();
}

// ANTES (problemático)
private function applyVisibilityFilters(Builder $query, string $userId, string $userRole): void
{
    if ($userRole === 'COMPANY_ADMIN') {
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
        // ...
    }
}

// DESPUÉS (correcto)
private function applyVisibilityFilters(Builder $query, string $userId, string $userRole): void
{
    if ($userRole === 'COMPANY_ADMIN' || $userRole === 'AGENT') {
        $companyId = JWTHelper::getActiveCompanyId();
        // ...
    }
}
```

---

## 📝 Plan de Implementación

### Fase 1: Backend Core (2-3 días)

| Tarea | Archivo(s) | Esfuerzo |
|-------|------------|----------|
| 1.1 Modificar TokenService para incluir `active_role` | `TokenService.php` | 2h |
| 1.2 Agregar métodos a JWTHelper | `JWTHelper.php` | 2h |
| 1.3 Crear endpoint POST `/auth/select-role` | `AuthController.php` + Request | 3h |
| 1.4 Crear endpoint GET `/auth/available-roles` | `AuthController.php` | 1h |
| 1.5 Modificar refresh token para mantener active_role | `RefreshTokenController.php` | 2h |
| 1.6 Tests unitarios para nuevos endpoints | `tests/Feature/Authentication/` | 3h |

### Fase 2: Migrar Servicios Críticos (3-4 días)

| Tarea | Archivo(s) | Esfuerzo |
|-------|------------|----------|
| 2.1 Migrar TicketService | `TicketService.php` | 3h |
| 2.2 Migrar AnnouncementController | `AnnouncementController.php` | 2h |
| 2.3 Migrar AnalyticsController | `AnalyticsController.php` | 2h |
| 2.4 Migrar ActivityLogController | `ActivityLogController.php` | 1h |
| 2.5 Migrar Area y CategoryController | `AreaController.php`, `CategoryController.php` | 2h |
| 2.6 Tests de integración | `tests/Feature/` | 4h |

### Fase 3: Migrar Policies y Controllers Restantes (2 días)

| Tarea | Archivo(s) | Esfuerzo |
|-------|------------|----------|
| 3.1 Actualizar TicketPolicy | `TicketPolicy.php` | 1h |
| 3.2 Actualizar TicketResponsePolicy | `TicketResponsePolicy.php` | 1h |
| 3.3 Actualizar TicketAttachmentPolicy | `TicketAttachmentPolicy.php` | 1h |
| 3.4 Actualizar UserPolicy | `UserPolicy.php` | 1h |
| 3.5 Actualizar CompanyPolicy | `CompanyPolicy.php` | 1h |
| 3.6 Migrar announcement controllers especializados | `*AnnouncementController.php` | 2h |
| 3.7 Migrar ArticleController | `ArticleController.php` | 1h |

### Fase 4: Frontend y UX (2 días)

| Tarea | Archivo(s) | Esfuerzo |
|-------|------------|----------|
| 4.1 Actualizar role-selector para usar nuevo endpoint | `role-selector.blade.php` | 2h |
| 4.2 Agregar "switcher" de rol en el header | `layouts/authenticated.blade.php` | 3h |
| 4.3 Actualizar login.blade.php para nuevo flujo | `login.blade.php` | 2h |
| 4.4 Manejar refresh token con active_role | `authenticated.blade.php` | 2h |

### Fase 5: Testing y Estabilización (2-3 días)

| Tarea | Descripción | Esfuerzo |
|-------|-------------|----------|
| 5.1 Tests E2E | Flujos completos de login → select role → usar API | 4h |
| 5.2 Tests de regresión | Verificar que usuarios de 1 rol siguen funcionando | 2h |
| 5.3 Tests edge cases | Refresh, cambio de rol, sesiones múltiples | 3h |
| 5.4 Documentación | Actualizar API docs | 2h |

### Timeline Estimado Total

```
Semana 1: Fases 1 y 2 (Backend core + servicios críticos)
Semana 2: Fases 3, 4 y 5 (Policies + Frontend + Testing)

Total: ~10-14 días de desarrollo
```

---

## ❓ Respuestas a Preguntas Específicas

### 1. ¿Debería implementar un endpoint para seleccionar/cambiar rol?

**Sí, definitivamente.** El endpoint `POST /api/auth/select-role` es necesario porque:
- El JWT es inmutable una vez generado
- Para cambiar el rol activo, necesitas generar un nuevo JWT
- Este endpoint valida que el usuario realmente tiene ese rol antes de generar el token

### 2. ¿Qué pasa en el login o primer login?

**Flujo propuesto:**

```
Login API Response
      │
      ▼
¿Usuario tiene 1 solo rol?
      │
  ┌───┴───┐
  │       │
 YES      NO
  │       │
  ▼       ▼
JWT con   JWT sin active_role
active_   (o con active_role = null)
role      │
auto      │
          ▼
    Frontend detecta que
    necesita seleccionar rol
          │
          ▼
    Redirige a /role-selector
          │
          ▼
    Usuario selecciona
          │
          ▼
    POST /auth/select-role
          │
          ▼
    Nuevo JWT con active_role
```

### 3. ¿Qué pasa si seleccioné un rol y luego salgo pero vuelvo a entrar (refresh token)?

**El refresh token debe mantener el active_role:**

```php
// En RefreshTokenController.php - refresh()
public function refresh(): JsonResponse
{
    // Validar refresh token
    $refreshToken = $this->tokenService->validateRefreshToken($token);
    
    // Obtener el active_role del access token viejo (si existe)
    $oldPayload = request()->attributes->get('jwt_payload');
    $activeRole = $oldPayload['active_role'] ?? null;
    
    // Generar nuevo access token MANTENIENDO el active_role
    $accessToken = $this->tokenService->generateAccessToken(
        $refreshToken->user,
        $sessionId,
        $activeRole  // ← Preservar el rol activo
    );
    
    // ...
}
```

### 4. ¿Select-role es lo correcto o debería usar otra cosa?

**`select-role` es correcto**, pero considera también:

| Opción | Pros | Contras |
|--------|------|---------|
| `POST /auth/select-role` | Claro, semántico | N/A |
| `POST /auth/switch-context` | Más genérico (permite cambiar empresa también) | Puede ser confuso |
| `PATCH /auth/me/active-role` | RESTful | Mezcla auth con user management |

**Recomendación:** Usa `POST /api/auth/select-role` con la opción de expandirlo en el futuro.

### 5. ¿Qué cambios recibirían los endpoints existentes?

Los cambios son **retrocompatibles** si se implementa correctamente:

```php
// El helper detecta si hay active_role o no
public static function getActiveRole(): array
{
    $payload = request()->attributes->get('jwt_payload');
    
    // Si no hay active_role, funciona como antes (backward compatible)
    if (!isset($payload['active_role'])) {
        return self::determineFallbackRole($payload['roles']);
    }
    
    return $payload['active_role'];
}
```

---

## 📈 Estimación de Complejidad

### Nivel de Complejidad: ⚠️ MEDIA-ALTA

| Aspecto | Complejidad | Razón |
|---------|-------------|-------|
| Cambios en JWT/Auth | Media | Bien encapsulado, pero crítico |
| Migración de servicios | Media | Muchos archivos pero cambios similares |
| Testing | Alta | Muchos edge cases que cubrir |
| Frontend | Baja | La UI ya existe, solo conectar |
| Regresión | Media | Usuarios actuales no deberían romperse |

### Riesgos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Romper usuarios existentes | Media | Alto | Backward compatibility en JWTHelper |
| Sessions inconsistentes | Media | Medio | Testing exhaustivo de refresh |
| Confusión de UX | Baja | Medio | Documentar bien el flujo |

### ¿Vale la pena hacerlo?

**Sí, absolutamente.** Los problemas actuales:
1. Son **bugs silenciosos** que pueden causar fugas de datos
2. Se **agravan** a medida que crece la plataforma
3. Afectan la **UX** de usuarios multi-rol

El costo de no arreglarlo es mayor que el costo de implementar la solución.

---

## 📚 Archivos a Modificar (Resumen)

### Backend - Core Auth
- `app/Features/Authentication/Services/TokenService.php`
- `app/Shared/Helpers/JWTHelper.php`
- `app/Features/Authentication/Http/Controllers/AuthController.php`
- `app/Features/Authentication/Http/Controllers/RefreshTokenController.php`

### Backend - Services
- `app/Features/TicketManagement/Services/TicketService.php`
- `app/Features/ContentManagement/Services/AnnouncementService.php`
- `app/Features/ContentManagement/Services/VisibilityService.php`
- `app/Features/Analytics/Services/AnalyticsService.php`

### Backend - Controllers
- `app/Features/TicketManagement/Http/Controllers/TicketController.php`
- `app/Features/ContentManagement/Http/Controllers/AnnouncementController.php`
- `app/Features/ContentManagement/Http/Controllers/*.php` (todos los de announcements)
- `app/Features/Analytics/Http/Controllers/AnalyticsController.php`
- `app/Features/AuditLog/Http/Controllers/ActivityLogController.php`
- `app/Features/CompanyManagement/Http/Controllers/AreaController.php`
- `app/Features/TicketManagement/Http/Controllers/CategoryController.php`

### Backend - Policies
- `app/Features/TicketManagement/Policies/TicketPolicy.php`
- `app/Features/TicketManagement/Policies/TicketResponsePolicy.php`
- `app/Features/TicketManagement/Policies/TicketAttachmentPolicy.php`
- `app/Features/UserManagement/Policies/UserPolicy.php`

### Frontend
- `resources/views/auth-flow/role-selector.blade.php`
- `resources/views/public/login.blade.php`
- `resources/views/layouts/authenticated.blade.php`

### Tests
- `tests/Feature/Authentication/` (nuevos tests)
- `tests/Feature/TicketManagement/` (actualizar existentes)

---

## ✅ Conclusión

El problema de diseño identificado es **real y significativo**, pero tiene una solución clara y bien definida. La implementación del sistema de "Rol Activo" resolverá:

1. ✅ Comportamiento predecible para usuarios multi-rol
2. ✅ Control explícito sobre qué datos ver/gestionar
3. ✅ Mejor UX con switcher de rol
4. ✅ Fundamentos sólidos para futuras funcionalidades

**Recomendación:** Priorizar esta implementación antes de agregar nuevas funcionalidades que dependan de roles, ya que el problema solo empeorará con el tiempo.

---

*Documento generado: 2025-12-07*  
*Autor: Claude (asistente de desarrollo)*  
*Proyecto: Helpdesk - Sistema de Gestión de Tickets*
