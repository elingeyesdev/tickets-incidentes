# 📘 USER MANAGEMENT - REST API Mapping V10.1 (OFFICIAL)

> **Sistema Helpdesk Multi-Tenant**
> **Feature:** User Management
> **Migration:** GraphQL → REST API (100% Functional Parity)
> **Version:** 1.0 FINAL
> **Status:** Ready for Production Implementation
> **Last Updated:** Octubre 2025

---

## ⚠️ Cambios Críticos vs. Documentación Anterior

Este documento **reemplaza** la versión anterior e incorpora correcciones arquitectónicas:

1. ✅ **Sin redundancia `success`** - HTTP status es la fuente de verdad
2. ✅ **Verbos HTTP correctos** - PUT para operaciones idempotentes
3. ✅ **Request body en DELETE** - Parámetros complejos en body, no URL
4. ✅ **Eager loading documentado** - Prevención explícita de N+1
5. ✅ **Identificadores claros** - UUID en rutas, userCode en response

---

## 🎯 Resumen Ejecutivo

**5 Queries GraphQL → 5 GET Endpoints**
- `me` → `GET /api/users/me`
- `myProfile` → `GET /api/users/me/profile`
- `users` → `GET /api/users`
- `user(id)` → `GET /api/users/{id}`
- `availableRoles` → `GET /api/roles`

**7 Mutations GraphQL → 7 REST Endpoints**
- `updateMyProfile` → `PATCH /api/users/me/profile`
- `updateMyPreferences` → `PATCH /api/users/me/preferences`
- `suspendUser` → `PUT /api/users/{id}/status` (cambio)
- `activateUser` → `PUT /api/users/{id}/status` (cambio)
- `deleteUser` → `DELETE /api/users/{id}`
- `assignRole` → `POST /api/users/{id}/roles`
- `removeRole` → `DELETE /api/users/roles/{roleId}`

---

## 📋 Tabla de Contenidos

1. [Resumen de Endpoints](#resumen-de-endpoints)
2. [Queries - GET Endpoints](#queries--get-endpoints)
3. [Mutations - PUT/PATCH/DELETE Endpoints](#mutations--putpatchdelete-endpoints)
4. [POST Endpoints - Creación de Relaciones](#post-endpoints--creación-de-relaciones)
5. [Tipos y Estructuras](#tipos-y-estructuras)
6. [Validaciones y Permisos](#validaciones-y-permisos)
7. [Códigos de Error](#códigos-de-error)
8. [Rate Limiting](#rate-limiting)
9. [Auditoría](#auditoría)
10. [Guía de Implementación](#guía-de-implementación)
11. [Prevención de N+1](#prevención-de-n1)

---

## 🗺️ Resumen de Endpoints

### GET Endpoints (Queries)

| GraphQL Query | REST Endpoint | HTTP | Auth | Rol Requerido |
|--------------|---------------|------|------|----------------|
| `me` | `/api/users/me` | GET | ✅ | Cualquiera |
| `myProfile` | `/api/users/me/profile` | GET | ✅ | Cualquiera |
| `users` | `/api/users` | GET | ✅ | PLATFORM_ADMIN, COMPANY_ADMIN |
| `user(id)` | `/api/users/{id}` | GET | ✅ | Según permisos |
| `availableRoles` | `/api/roles` | GET | ✅ | PLATFORM_ADMIN, COMPANY_ADMIN |

### PUT Endpoints (Estado del Usuario)

| Operación | REST Endpoint | HTTP | Auth | Rol Requerido |
|-----------|---------------|------|------|----------------|
| Suspender usuario | `/api/users/{id}/status` | PUT | ✅ | PLATFORM_ADMIN |
| Activar usuario | `/api/users/{id}/status` | PUT | ✅ | PLATFORM_ADMIN |

### PATCH Endpoints (Actualizaciones Parciales)

| GraphQL Mutation | REST Endpoint | HTTP | Auth | Rol Requerido |
|------------------|---------------|------|------|----------------|
| `updateMyProfile` | `/api/users/me/profile` | PATCH | ✅ | Cualquiera |
| `updateMyPreferences` | `/api/users/me/preferences` | PATCH | ✅ | Cualquiera |

### DELETE Endpoints (Eliminación)

| GraphQL Mutation | REST Endpoint | HTTP | Auth | Rol Requerido |
|------------------|---------------|------|------|----------------|
| `deleteUser` | `/api/users/{id}` | DELETE | ✅ | PLATFORM_ADMIN |
| `removeRole` | `/api/users/roles/{roleId}` | DELETE | ✅ | PLATFORM_ADMIN, COMPANY_ADMIN |

### POST Endpoints (Creación de Relaciones)

| GraphQL Mutation | REST Endpoint | HTTP | Auth | Rol Requerido |
|------------------|---------------|------|------|----------------|
| `assignRole` | `/api/users/{id}/roles` | POST | ✅ | PLATFORM_ADMIN, COMPANY_ADMIN |

---

# 🔍 QUERIES - GET Endpoints

## 1. `GET /api/users/me` - Usuario Autenticado

**Descripción:** Obtiene información completa del usuario autenticado.

**Autenticación:** ✅ JWT requerido

**Response (200 OK):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "userCode": "USR-2025-00123",
        "email": "maria.garcia@empresa.com",
        "emailVerified": true,
        "status": "active",
        "authProvider": "local",
        "profile": {
            "firstName": "María",
            "lastName": "García",
            "displayName": "María García",
            "phoneNumber": "+591 70123456",
            "avatarUrl": "https://storage.helpdesk.com/avatars/usr_123.jpg",
            "theme": "dark",
            "language": "es",
            "timezone": "America/La_Paz",
            "pushWebNotifications": true,
            "notificationsTickets": false,
            "createdAt": "2025-01-15T08:00:00Z",
            "updatedAt": "2025-09-20T14:22:00Z"
        },
        "roleContexts": [
            {
                "roleCode": "USER",
                "roleName": "Cliente",
                "company": null,
                "dashboardPath": "/tickets"
            },
            {
                "roleCode": "AGENT",
                "roleName": "Agente de Soporte",
                "company": {
                    "id": "cmp-001",
                    "companyCode": "CMP-2025-00001",
                    "name": "Universidad del Valle",
                    "logoUrl": "https://storage.helpdesk.com/logos/cmp_001.png"
                },
                "dashboardPath": "/agent/dashboard"
            }
        ],
        "ticketsCount": 47,
        "resolvedTicketsCount": 23,
        "averageRating": 4.3,
        "lastLoginAt": "2025-10-03T14:45:00Z",
        "createdAt": "2025-01-15T08:00:00Z",
        "updatedAt": "2025-09-20T14:22:00Z"
    }
}
```

**Errores:**
- `401 Unauthorized` - Token inválido o expirado
- `403 Forbidden` - Usuario suspendido

**Casos de Uso:**
- Header de usuario en interfaz
- Validación de permisos
- Selector de roles/empresas

---

## 2. `GET /api/users/me/profile` - Mi Perfil

**Descripción:** Obtiene solo la información del perfil del usuario autenticado.

**Autenticación:** ✅ JWT requerido

**Response (200 OK):**
```json
{
    "data": {
        "firstName": "María",
        "lastName": "García",
        "displayName": "María García",
        "phoneNumber": "+591 70123456",
        "avatarUrl": "https://storage.helpdesk.com/avatars/usr_123.jpg",
        "theme": "dark",
        "language": "es",
        "timezone": "America/La_Paz",
        "pushWebNotifications": true,
        "notificationsTickets": false,
        "createdAt": "2025-01-15T08:00:00Z",
        "updatedAt": "2025-09-20T14:22:00Z"
    }
}
```

---

## 3. `GET /api/users` - Lista Paginada de Usuarios

**Descripción:** Lista paginada de usuarios con filtros avanzados. Solo admins.

**Autenticación:** ✅ JWT requerido

**Autorización:** PLATFORM_ADMIN (todos) o COMPANY_ADMIN (su empresa)

**Query Parameters:**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `search` | string | Búsqueda en email/nombre | `maria` |
| `status` | enum | active, suspended, deleted | `active` |
| `role` | enum | USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN | `AGENT` |
| `emailVerified` | boolean | Email verificado | `true` |
| `companyId` | uuid | Usuarios con rol en empresa | `cmp-001` |
| `recentActivity` | boolean | Activos últimos 7 días | `true` |
| `createdAfter` | datetime | Desde fecha | `2025-01-01T00:00:00Z` |
| `createdBefore` | datetime | Hasta fecha | `2025-12-31T23:59:59Z` |
| `page` | int | Número de página (default: 1) | `1` |
| `limit` | int | Registros por página (default: 15, max: 50) | `20` |
| `orderBy` | enum | Campo de ordenamiento | `last_login_at` |
| `order` | enum | ASC o DESC (default: DESC) | `desc` |

**Request:**
```
GET /api/users?search=maria&status=active&page=1&limit=20&orderBy=last_login_at&order=desc
Authorization: Bearer {accessToken}
```

**Response (200 OK):**
```json
{
    "data": [
        {
            "id": "550e8400-e29b-41d4-a716-446655440000",
            "userCode": "USR-2025-00123",
            "email": "maria.garcia@empresa.com",
            "emailVerified": true,
            "status": "active",
            "profile": {
                "firstName": "María",
                "lastName": "García",
                "displayName": "María García",
                "avatarUrl": "https://storage.helpdesk.com/avatars/usr_123.jpg"
            },
            "roleContexts": [
                {
                    "roleCode": "AGENT",
                    "roleName": "Agente de Soporte",
                    "company": {
                        "id": "cmp-001",
                        "name": "Universidad del Valle"
                    },
                    "dashboardPath": "/agent/dashboard"
                }
            ],
            "lastLoginAt": "2025-10-03T14:45:00Z",
            "ticketsCount": 47,
            "createdAt": "2025-01-15T08:00:00Z"
        }
    ],
    "pagination": {
        "total": 145,
        "perPage": 20,
        "currentPage": 1,
        "lastPage": 8,
        "hasMorePages": true
    }
}
```

**⚠️ PREVENCIÓN DE N+1:**

```php
// CORRECTO - Eager load roleContexts.company
$users = User::with(['profile', 'roleContexts.company'])
    ->paginate(20);

// INCORRECTO - Causa N+1 cuando Resource intenta acceder roleContexts.company
$users = User::paginate(20);  // ← Sin eager load
```

Ver [Prevención de N+1](#prevención-de-n1) para más detalles.

---

## 4. `GET /api/users/{id}` - Usuario Específico

**Descripción:** Obtiene información completa de un usuario específico (igual que `me`).

**Autenticación:** ✅ JWT requerido

**Autorización:**
- PLATFORM_ADMIN: puede ver cualquier usuario
- COMPANY_ADMIN: puede ver usuarios de su empresa
- Otros: acceso denegado

**URL Parameters:**
- `id` (uuid, required) - ID del usuario

**Request:**
```
GET /api/users/550e8400-e29b-41d4-a716-446655440000
Authorization: Bearer {accessToken}
```

**Response (200 OK):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "userCode": "USR-2025-00123",
        "email": "maria.garcia@empresa.com",
        "emailVerified": true,
        "status": "active",
        "authProvider": "local",
        "profile": { ... },
        "roleContexts": [ ... ],
        "ticketsCount": 47,
        "resolvedTicketsCount": 23,
        "averageRating": 4.3,
        "lastLoginAt": "2025-10-03T14:45:00Z",
        "createdAt": "2025-01-15T08:00:00Z",
        "updatedAt": "2025-09-20T14:22:00Z"
    }
}
```

**Errores:**
- `404 Not Found` - Usuario no existe
- `403 Forbidden` - No tiene permisos para verlo

---

## 5. `GET /api/roles` - Roles Disponibles

**Descripción:** Lista de roles del sistema con sus descripciones.

**Autenticación:** ✅ JWT requerido

**Autorización:** PLATFORM_ADMIN o COMPANY_ADMIN

**Cache:** 1 hora (privado por usuario)

**Request:**
```
GET /api/roles
Authorization: Bearer {accessToken}
```

**Response (200 OK):**
```json
{
    "data": [
        {
            "code": "USER",
            "name": "Cliente",
            "description": "Usuario que crea tickets",
            "requiresCompany": false,
            "defaultDashboard": "/tickets",
            "isSystemRole": true
        },
        {
            "code": "AGENT",
            "name": "Agente de Soporte",
            "description": "Atiende tickets de soporte",
            "requiresCompany": true,
            "defaultDashboard": "/agent/dashboard",
            "isSystemRole": true
        },
        {
            "code": "COMPANY_ADMIN",
            "name": "Administrador de Empresa",
            "description": "Gestiona una empresa específica",
            "requiresCompany": true,
            "defaultDashboard": "/empresa/dashboard",
            "isSystemRole": true
        },
        {
            "code": "PLATFORM_ADMIN",
            "name": "Administrador de Plataforma",
            "description": "Acceso completo a todo el sistema",
            "requiresCompany": false,
            "defaultDashboard": "/admin/dashboard",
            "isSystemRole": true
        }
    ]
}
```

---

# ✏️ MUTATIONS - PUT/PATCH/DELETE Endpoints

## 1. `PATCH /api/users/me/profile` - Actualizar Perfil Personal

**Descripción:** Actualiza datos personales del perfil.

**Autenticación:** ✅ JWT requerido

**Rate Limit:** 30 por hora

**Request:**
```
PATCH /api/users/me/profile
Authorization: Bearer {accessToken}
Content-Type: application/json

{
    "firstName": "María Alejandra",
    "lastName": "García Rodríguez",
    "phoneNumber": "+591 75987654",
    "avatarUrl": "https://storage.helpdesk.com/avatars/new_avatar.jpg"
}
```

**Validaciones:**
- `firstName`: min:2, max:100
- `lastName`: min:2, max:100
- `phoneNumber`: min:10, max:20
- `avatarUrl`: valid URL

**Response (200 OK):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "profile": {
            "firstName": "María Alejandra",
            "lastName": "García Rodríguez",
            "displayName": "María Alejandra García Rodríguez",
            "phoneNumber": "+591 75987654",
            "avatarUrl": "https://storage.helpdesk.com/avatars/new_avatar.jpg",
            "updatedAt": "2025-10-03T16:30:00Z"
        }
    }
}
```

**Errores:**
- `422 Unprocessable Entity` - Validación fallida
- `401 Unauthorized` - Token inválido

**Auditoría:** `profile_update` (con cambios)

---

## 2. `PATCH /api/users/me/preferences` - Actualizar Preferencias

**Descripción:** Actualiza preferencias de aplicación.

**Autenticación:** ✅ JWT requerido

**Rate Limit:** 50 por hora

**Request:**
```
PATCH /api/users/me/preferences
Authorization: Bearer {accessToken}
Content-Type: application/json

{
    "theme": "dark",
    "language": "en",
    "timezone": "America/New_York",
    "pushWebNotifications": false,
    "notificationsTickets": true
}
```

**Validaciones:**
- `theme`: in:light,dark
- `language`: in:es,en
- `timezone`: valid IANA timezone
- `pushWebNotifications`: boolean
- `notificationsTickets`: boolean

**Response (200 OK):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "preferences": {
            "theme": "dark",
            "language": "en",
            "timezone": "America/New_York",
            "pushWebNotifications": false,
            "notificationsTickets": true,
            "updatedAt": "2025-10-03T16:35:00Z"
        }
    }
}
```

**Auditoría:** `preferences_update` (con cambios)

---

## 3. `PUT /api/users/{id}/status` - Cambiar Estado del Usuario

**Descripción:** Cambia el estado de un usuario a `suspended` o `active`. Operación idempotente.

**Autenticación:** ✅ JWT requerido

**Autorización:** Solo PLATFORM_ADMIN

**URL Parameters:**
- `id` (uuid, required) - ID del usuario

**Request (Suspender):**
```
PUT /api/users/550e8400-e29b-41d4-a716-446655440000/status
Authorization: Bearer {adminToken}
Content-Type: application/json

{
    "status": "suspended",
    "reason": "Violación de términos de servicio - spam de tickets"
}
```

**Request (Activar):**
```
PUT /api/users/550e8400-e29b-41d4-a716-446655440000/status
Authorization: Bearer {adminToken}
Content-Type: application/json

{
    "status": "active"
}
```

**Validaciones:**
- `status`: required, in:active,suspended
- `reason`: required if status=suspended

**Response (200 OK):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "status": "suspended",
        "updatedAt": "2025-10-03T16:40:00Z"
    }
}
```

**Idempotencia:** ✅ Sí (ejecutar 2 veces retorna 200 ambas)

**Efectos:**
- Cambia status a suspended/active
- Si suspended: invalida todos los tokens activos
- Registra cambio en auditoría

**Auditoría:**
- `user_suspend` (con payload)
- `user_activate` (con payload)

---

## 4. `DELETE /api/users/{id}` - Eliminar Usuario (Soft Delete)

**Descripción:** Elimina lógicamente un usuario (soft delete, recuperable).

**Autenticación:** ✅ JWT requerido

**Autorización:** Solo PLATFORM_ADMIN

**URL Parameters:**
- `id` (uuid, required) - ID del usuario a eliminar

**Request:**
```
DELETE /api/users/550e8400-e29b-41d4-a716-446655440000
Authorization: Bearer {adminToken}
Content-Type: application/json

{
    "reason": "Solicitud del usuario - GDPR compliance"
}
```

**Response (204 No Content):**
```
(Sin body, solo status 204)
```

**O Response (200 OK) si necesitas confirmación:**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "status": "deleted",
        "deletedAt": "2025-10-03T16:45:00Z"
    }
}
```

**Efectos:**
- Cambia status a "deleted"
- Establece deletedAt timestamp
- Anonimiza datos sensibles
- Mantiene registros para auditoría

**Auditoría:** `user_delete` (con payload)

---

# 📤 POST ENDPOINTS - Creación de Relaciones

## 1. `POST /api/users/{id}/roles` - Asignar Rol

**Descripción:** Asigna un rol a un usuario. Crea nuevo rol O reactiva si existe inactivo.

**Autenticación:** ✅ JWT requerido

**Autorización:**
- PLATFORM_ADMIN: puede asignar cualquier rol
- COMPANY_ADMIN: puede asignar roles en su empresa

**Rate Limit:** 100 por hora

**URL Parameters:**
- `id` (uuid, required) - ID del usuario

**Request Body (Rol CON Empresa - AGENT):**
```
POST /api/users/550e8400-e29b-41d4-a716-446655440000/roles
Authorization: Bearer {adminToken}
Content-Type: application/json

{
    "roleCode": "AGENT",
    "companyId": "cmp-001"
}
```

**Request Body (Rol SIN Empresa - USER):**
```
POST /api/users/550e8400-e29b-41d4-a716-446655440000/roles
Authorization: Bearer {adminToken}
Content-Type: application/json

{
    "roleCode": "USER"
}
```

**Validaciones:**

| Rol | Requiere Empresa | Acción |
|-----|------------------|--------|
| USER | ❌ NO | Omitir `companyId` |
| PLATFORM_ADMIN | ❌ NO | Omitir `companyId` |
| AGENT | ✅ SÍ | Incluir `companyId` (requerido) |
| COMPANY_ADMIN | ✅ SÍ | Incluir `companyId` (requerido) |

**Response (201 Created - Nuevo Rol):**
```json
{
    "data": {
        "success": true,
        "message": "Rol AGENT asignado exitosamente",
        "role": {
            "id": "role-123",
            "roleCode": "AGENT",
            "roleName": "Agente de Soporte",
            "company": {
                "id": "cmp-001",
                "name": "Universidad del Valle",
                "logoUrl": "https://storage.helpdesk.com/logos/cmp_001.png"
            },
            "isActive": true,
            "assignedAt": "2025-10-03T16:45:00Z",
            "assignedBy": {
                "id": "admin-001",
                "userCode": "ADM-2025-00001",
                "email": "admin@helpdesk.com"
            }
        }
    }
}
```

**Response (200 OK - Rol Reactivado):**
```json
{
    "data": {
        "success": true,
        "message": "Rol AGENT reactivado exitosamente",
        "role": {
            "id": "role-123",
            "roleCode": "AGENT",
            "roleName": "Agente de Soporte",
            "company": {
                "id": "cmp-001",
                "name": "Universidad del Valle",
                "logoUrl": "https://storage.helpdesk.com/logos/cmp_001.png"
            },
            "isActive": true,
            "assignedAt": "2025-03-10T10:15:00Z",
            "assignedBy": {
                "id": "admin-002",
                "userCode": "ADM-2025-00002",
                "email": "another_admin@helpdesk.com"
            }
        }
    }
}
```

**Lógica Inteligente:**
```
SI rol existe e inactivo:
    reactivar (isActive = true, revokedAt = null)
    RETORNAR "reactivado"
SINO:
    crear nuevo rol
    RETORNAR "asignado"
FIN
```

**Errores:**
- `422 Unprocessable Entity` - Validación fallida
- `409 Conflict` - Rol ya existe activo
- `403 Forbidden` - Permisos insuficientes

**Auditoría:** `role_assign` (con payload)

---

## 2. `DELETE /api/users/roles/{roleId}` - Remover Rol

**Descripción:** Desactiva un rol de un usuario (soft delete, reversible).

**Autenticación:** ✅ JWT requerido

**Autorización:**
- PLATFORM_ADMIN: puede remover cualquier rol
- COMPANY_ADMIN: puede remover roles de su empresa

**URL Parameters:**
- `roleId` (uuid, required) - ID del rol

**Request:**
```
DELETE /api/users/roles/role-123
Authorization: Bearer {adminToken}
Content-Type: application/json

{
    "reason": "Usuario dejó de trabajar en la empresa"
}
```

**Response (204 No Content):**
```
(Sin body, solo status 204)
```

**O Response (200 OK):**
```json
{
    "data": {
        "id": "role-123",
        "isActive": false,
        "revokedAt": "2025-10-03T16:50:00Z"
    }
}
```

**Efectos:**
- Establece isActive = false
- Registra revokedAt timestamp
- Guarda revocationReason
- Invalida permisos derivados

**Reactivación:** Para reactivar, usar `POST /api/users/{id}/roles` con los mismos parámetros.

**Auditoría:** `role_remove` (con payload)

---

# 📦 Tipos y Estructuras

## User (Completo)

```json
{
    "id": "uuid",
    "userCode": "USR-2025-00123",
    "email": "email",
    "emailVerified": "boolean",
    "status": "active|suspended|deleted",
    "authProvider": "local|google|facebook|...",
    "profile": {
        "firstName": "string",
        "lastName": "string",
        "displayName": "string",
        "phoneNumber": "string|null",
        "avatarUrl": "url|null",
        "theme": "light|dark",
        "language": "es|en",
        "timezone": "string (IANA)",
        "pushWebNotifications": "boolean",
        "notificationsTickets": "boolean",
        "createdAt": "datetime ISO8601",
        "updatedAt": "datetime ISO8601"
    },
    "roleContexts": [
        {
            "roleCode": "USER|AGENT|COMPANY_ADMIN|PLATFORM_ADMIN",
            "roleName": "string",
            "company": {
                "id": "uuid",
                "companyCode": "string",
                "name": "string",
                "logoUrl": "url|null"
            } | null,
            "dashboardPath": "string"
        }
    ],
    "ticketsCount": "integer",
    "resolvedTicketsCount": "integer",
    "averageRating": "float|null",
    "lastLoginAt": "datetime ISO8601|null",
    "lastActivityAt": "datetime ISO8601|null",
    "createdAt": "datetime ISO8601",
    "updatedAt": "datetime ISO8601",
    "deletedAt": "datetime ISO8601|null"
}
```

## UserProfile

```json
{
    "firstName": "string",
    "lastName": "string",
    "displayName": "string",
    "phoneNumber": "string|null",
    "avatarUrl": "url|null",
    "theme": "light|dark",
    "language": "es|en",
    "timezone": "string",
    "pushWebNotifications": "boolean",
    "notificationsTickets": "boolean",
    "createdAt": "datetime ISO8601",
    "updatedAt": "datetime ISO8601"
}
```

## UserRoleInfo

```json
{
    "id": "uuid",
    "roleCode": "USER|AGENT|COMPANY_ADMIN|PLATFORM_ADMIN",
    "roleName": "string",
    "company": {
        "id": "uuid",
        "name": "string",
        "logoUrl": "url|null"
    } | null,
    "isActive": "boolean",
    "assignedAt": "datetime ISO8601",
    "assignedBy": {
        "id": "uuid",
        "userCode": "string",
        "email": "email"
    } | null,
    "revokedAt": "datetime ISO8601|null"
}
```

## RoleInfo

```json
{
    "code": "USER|AGENT|COMPANY_ADMIN|PLATFORM_ADMIN",
    "name": "string",
    "description": "string",
    "requiresCompany": "boolean",
    "defaultDashboard": "string",
    "isSystemRole": "boolean"
}
```

---

# 🔒 Validaciones y Permisos

## Matriz de Permisos por Endpoint

| Endpoint | USER | AGENT | COMPANY_ADMIN | PLATFORM_ADMIN |
|----------|------|-------|---------------|----------------|
| GET `/api/users/me` | ✅ | ✅ | ✅ | ✅ |
| GET `/api/users/me/profile` | ✅ | ✅ | ✅ | ✅ |
| PATCH `/api/users/me/profile` | ✅ | ✅ | ✅ | ✅ |
| PATCH `/api/users/me/preferences` | ✅ | ✅ | ✅ | ✅ |
| GET `/api/users` | ❌ | ❌ | ✅ (empresa) | ✅ (todos) |
| GET `/api/users/{id}` | ❌ | ❌ | ✅ (empresa) | ✅ (todos) |
| PUT `/api/users/{id}/status` | ❌ | ❌ | ❌ | ✅ |
| DELETE `/api/users/{id}` | ❌ | ❌ | ❌ | ✅ |
| POST `/api/users/{id}/roles` | ❌ | ❌ | ✅ (su empresa) | ✅ (todos) |
| DELETE `/api/users/roles/{roleId}` | ❌ | ❌ | ✅ (su empresa) | ✅ (todos) |
| GET `/api/roles` | ❌ | ❌ | ✅ | ✅ |

## Reglas de Validación

**updateMyProfile:**
```
firstName:    required, string, min:2, max:100
lastName:     required, string, min:2, max:100
phoneNumber:  nullable, string, min:10, max:20
avatarUrl:    nullable, url
```

**updateMyPreferences:**
```
theme:                    required, in:light,dark
language:                 required, in:es,en
timezone:                 required, valid_timezone
pushWebNotifications:     required, boolean
notificationsTickets:     required, boolean
```

**assignRole:**
```
roleCode:     required, in:USER,AGENT,COMPANY_ADMIN,PLATFORM_ADMIN
companyId:    required_if:roleCode,AGENT or COMPANY_ADMIN
              nullable_if:roleCode,USER or PLATFORM_ADMIN
              exists:companies,id
```

**changeUserStatus:**
```
status:       required, in:active,suspended
reason:       required_if:status,suspended, min:10, max:500
```

---

# 🚨 Códigos de Error

## HTTP Status Codes

| Código | Significado | Caso |
|--------|-------------|------|
| 200 | OK | Operación exitosa (GET, PATCH, PUT) |
| 201 | Created | Recurso creado (POST) |
| 204 | No Content | Operación exitosa sin body (DELETE) |
| 400 | Bad Request | Solicitud malformada |
| 401 | Unauthorized | Token inválido/expirado |
| 403 | Forbidden | Permisos insuficientes |
| 404 | Not Found | Recurso no existe |
| 409 | Conflict | Conflicto (ej. rol ya existe activo) |
| 422 | Unprocessable Entity | Validación fallida |
| 429 | Too Many Requests | Rate limit excedido |
| 500 | Internal Server Error | Error interno del servidor |

## Error Response Format

**Errores de Validación (422):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "firstName": [
            "The firstName must be at least 2 characters."
        ],
        "phoneNumber": [
            "The phoneNumber must be between 10 and 20 characters."
        ]
    }
}
```

**Errores de Autorización (403):**
```json
{
    "message": "Insufficient permissions",
    "code": "INSUFFICIENT_PERMISSIONS"
}
```

**Errores de Negocio (409):**
```json
{
    "message": "User already has this role assigned",
    "code": "USER_ALREADY_HAS_ROLE",
    "data": {
        "roleCode": "AGENT",
        "companyId": "cmp-001"
    }
}
```

**Errores de Recurso No Encontrado (404):**
```json
{
    "message": "User not found",
    "code": "USER_NOT_FOUND"
}
```

## Códigos de Error (User Management)

| Código | HTTP | Descripción |
|--------|------|-------------|
| `USER_NOT_FOUND` | 404 | Usuario no existe |
| `EMAIL_ALREADY_EXISTS` | 409 | Email ya registrado |
| `INVALID_ROLE_ASSIGNMENT` | 422 | Asignación de rol inválida |
| `ROLE_REQUIRES_COMPANY` | 422 | Rol requiere empresa pero no se proporcionó |
| `ROLE_SHOULD_NOT_HAVE_COMPANY` | 422 | Rol no debería tener empresa pero se proporcionó |
| `INSUFFICIENT_PERMISSIONS` | 403 | Usuario no tiene permisos |
| `USER_SUSPENDED` | 403 | Usuario está suspendido |
| `USER_ALREADY_HAS_ROLE` | 409 | Usuario ya tiene este rol activo |
| `CANNOT_REMOVE_LAST_ADMIN` | 409 | No puede remover último admin |
| `INVALID_INPUT` | 422 | Entrada inválida |
| `RATE_LIMIT_EXCEEDED` | 429 | Demasiadas solicitudes |

---

# ⏱️ Rate Limiting

| Endpoint | Máximo | Ventana | Header |
|----------|--------|---------|--------|
| PATCH `/api/users/me/profile` | 30 | 1 hora | X-RateLimit-* |
| PATCH `/api/users/me/preferences` | 50 | 1 hora | X-RateLimit-* |
| POST `/api/users/{id}/roles` | 100 | 1 hora | X-RateLimit-* |

**Response Headers (Siempre):**
```
X-RateLimit-Limit: 30
X-RateLimit-Remaining: 28
X-RateLimit-Reset: 1634839200
```

**Response de Rate Limit (429):**
```json
{
    "message": "Too many requests. Please try again after 1 hour.",
    "code": "RATE_LIMIT_EXCEEDED",
    "retryAfter": 3600
}
```

---

# 📊 Auditoría

## Eventos Registrados

| Evento | Endpoint | Payload | Descripción |
|--------|----------|---------|-------------|
| `profile_update` | PATCH `/api/users/me/profile` | ✅ Sí | Datos personales actualizados |
| `preferences_update` | PATCH `/api/users/me/preferences` | ✅ Sí | Preferencias actualizadas |
| `user_suspend` | PUT `/api/users/{id}/status` | ✅ Sí | Usuario suspendido |
| `user_activate` | PUT `/api/users/{id}/status` | ✅ Sí | Usuario reactivado |
| `user_delete` | DELETE `/api/users/{id}` | ✅ Sí | Usuario eliminado |
| `role_assign` | POST `/api/users/{id}/roles` | ✅ Sí | Rol asignado |
| `role_remove` | DELETE `/api/users/roles/{roleId}` | ✅ Sí | Rol removido |


