# 📘 USER MANAGEMENT - GraphQL to REST API Mapping V10.1

> **Sistema Helpdesk Multi-Tenant**
> **Feature:** User Management
> **Migration:** GraphQL → REST API (100% Functional Parity)
> **Última actualización:** Octubre 2025
> **Status:** Ready for Implementation

---

## 🎯 Resumen Ejecutivo

Este documento mapea la **migración completa del User Management Feature** de GraphQL a REST API, manteniendo **funcionalidad 100% idéntica**.

### Cambios Críticos
- ✅ **5 Queries → 5 GET Endpoints**
- ✅ **7 Mutations → 6 REST Endpoints** (POST, PUT, DELETE)
- ✅ **Mismo contrato de datos** (input/output)
- ✅ **Mismas validaciones** y permisos
- ✅ **Misma rate limiting** y auditoría
- ✅ **Mismos códigos de error**

---

## 📋 Tabla de Contenidos

1. [Resumen de Endpoints](#resumen-de-endpoints)
2. [Queries → GET Endpoints](#queries--get-endpoints)
3. [Mutations → REST Endpoints](#mutations--rest-endpoints)
4. [Tipos y Estructuras](#tipos-y-estructuras)
5. [Validaciones y Permisos](#validaciones-y-permisos)
6. [Ejemplos Completos](#ejemplos-completos)
7. [Códigos de Error](#códigos-de-error)
8. [Rate Limiting](#rate-limiting)
9. [Auditoría](#auditoría)
10. [Checklist de Implementación](#checklist-de-implementación)

---

## 🗺️ Resumen de Endpoints

### GET Endpoints (Queries)

| GraphQL Query | HTTP | REST Endpoint | Auth | Rol Requerido |
|--------------|------|---------------|------|----------------|
| `me` | GET | `/api/users/me` | ✅ JWT | Cualquiera |
| `myProfile` | GET | `/api/users/me/profile` | ✅ JWT | Cualquiera |
| `users` | GET | `/api/users` | ✅ JWT | PLATFORM_ADMIN, COMPANY_ADMIN |
| `user(id)` | GET | `/api/users/{id}` | ✅ JWT | Según permisos |
| `availableRoles` | GET | `/api/roles` | ✅ JWT | PLATFORM_ADMIN, COMPANY_ADMIN |

### POST/PUT/DELETE Endpoints (Mutations)

| GraphQL Mutation | HTTP | REST Endpoint | Auth | Rol Requerido |
|------------------|------|---------------|------|----------------|
| `updateMyProfile` | PATCH | `/api/users/me/profile` | ✅ JWT | Cualquiera |
| `updateMyPreferences` | PATCH | `/api/users/me/preferences` | ✅ JWT | Cualquiera |
| `suspendUser` | POST | `/api/users/{id}/suspend` | ✅ JWT | PLATFORM_ADMIN |
| `activateUser` | POST | `/api/users/{id}/activate` | ✅ JWT | PLATFORM_ADMIN |
| `deleteUser` | DELETE | `/api/users/{id}` | ✅ JWT | PLATFORM_ADMIN |
| `assignRole` | POST | `/api/users/{id}/roles` | ✅ JWT | PLATFORM_ADMIN, COMPANY_ADMIN |
| `removeRole` | DELETE | `/api/users/roles/{roleId}` | ✅ JWT | PLATFORM_ADMIN, COMPANY_ADMIN |

---

## 🔍 Queries → GET Endpoints

### 1. `me` → `GET /api/users/me`

**GraphQL:**
```graphql
query Me {
    me {
        id
        userCode
        email
        emailVerified
        status
        authProvider
        profile { ... }
        roleContexts { ... }
        ticketsCount
        resolvedTicketsCount
        averageRating
        lastLoginAt
        createdAt
        updatedAt
    }
}
```

**REST API:**

**Endpoint:**
```
GET /api/users/me
Authorization: Bearer {accessToken}
```

**Response (200 OK):**
```json
{
    "success": true,
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
- `403 Forbidden` - Usuario suspendido o eliminado

**Casos de Uso:**
- Header de usuario en interfaz
- Página de perfil completo
- Validación de permisos
- Selector de roles/empresas

---

### 2. `myProfile` → `GET /api/users/me/profile`

**REST API:**

**Endpoint:**
```
GET /api/users/me/profile
Authorization: Bearer {accessToken}
```

**Response (200 OK):**
```json
{
    "success": true,
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
        "lastActivityAt": "2025-10-03T15:30:00Z",
        "createdAt": "2025-01-15T08:00:00Z",
        "updatedAt": "2025-09-20T14:22:00Z"
    }
}
```

**Casos de Uso:**
- Formularios de edición de perfil
- Página de configuración personal

---

### 3. `users` → `GET /api/users`

**REST API:**

**Endpoint:**
```
GET /api/users?search=maria&status=active&role=AGENT&page=1&per_page=20&orderBy=last_login_at&order=desc
Authorization: Bearer {accessToken}
```

**Query Parameters:**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `search` | string | Búsqueda en email/nombre | `maria` |
| `status` | enum | ACTIVE, SUSPENDED, DELETED | `active` |
| `role` | enum | USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN | `AGENT` |
| `emailVerified` | boolean | Email verificado | `true` |
| `companyId` | uuid | Usuarios con rol en empresa | `cmp-001` |
| `recentActivity` | boolean | Activos últimos 7 días | `true` |
| `createdAfter` | datetime | Fecha desde | `2025-01-01T00:00:00Z` |
| `createdBefore` | datetime | Fecha hasta | `2025-12-31T23:59:59Z` |
| `page` | int | Número de página (default: 1) | `1` |
| `per_page` | int | Registros por página (default: 15, max: 50) | `20` |
| `orderBy` | enum | Campo de ordenamiento | `last_login_at` |
| `order` | enum | ASC o DESC (default: DESC) | `desc` |

**Response (200 OK):**
```json
{
    "success": true,
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

**Permisos:**
- PLATFORM_ADMIN: puede ver todos los usuarios
- COMPANY_ADMIN: puede ver solo usuarios de su empresa

**Casos de Uso:**
- Panel de administración de usuarios
- Búsqueda de usuarios para asignar roles
- Reportes de actividad

---

### 4. `user(id)` → `GET /api/users/{id}`

**REST API:**

**Endpoint:**
```
GET /api/users/{id}
Authorization: Bearer {accessToken}
```

**URL Parameters:**
- `id` (uuid, required) - ID del usuario

**Response (200 OK):**
```json
{
    "success": true,
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

**Cambios vs GraphQL:**
- ✅ Ahora retorna usuario completo (igual que `me`)
- ✅ Misma información en ambos endpoints

**Casos de Uso:**
- Página de detalle de usuario (admin)
- Modal de información completa
- Verificación antes de asignar roles

---

### 5. `availableRoles` → `GET /api/roles`

**REST API:**

**Endpoint:**
```
GET /api/roles
Authorization: Bearer {accessToken}
```

**Response (200 OK):**
```json
{
    "success": true,
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

**Cache:**
- 1 hora (cache privada por usuario)

**Casos de Uso:**
- Selector de roles en formularios
- Documentación de roles
- Validación de asignaciones

---

## ✏️ Mutations → REST Endpoints

### 1. `updateMyProfile` → `PATCH /api/users/me/profile`

**GraphQL:**
```graphql
mutation UpdateMyProfile($input: UpdateProfileInput!) {
    updateMyProfile(input: $input) {
        userId
        profile { ... }
        updatedAt
    }
}
```

**REST API:**

**Endpoint:**
```
PATCH /api/users/me/profile
Authorization: Bearer {accessToken}
Content-Type: application/json
```

**Request Body:**
```json
{
    "firstName": "María Alejandra",
    "lastName": "García Rodríguez",
    "phoneNumber": "+591 75987654",
    "avatarUrl": "https://storage.helpdesk.com/avatars/new_avatar.jpg"
}
```

**Validaciones:**
- `firstName`: mínimo 2, máximo 100 caracteres
- `lastName`: mínimo 2, máximo 100 caracteres
- `phoneNumber`: entre 10 y 20 caracteres
- `avatarUrl`: URL válida

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "userId": "550e8400-e29b-41d4-a716-446655440000",
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

**Rate Limit:** 30 por hora
**Auditoría:** `profile_update`

**Casos de Uso:**
- Formulario "Editar Perfil"
- Actualizar foto de perfil
- Cambiar número de teléfono

---

### 2. `updateMyPreferences` → `PATCH /api/users/me/preferences`

**REST API:**

**Endpoint:**
```
PATCH /api/users/me/preferences
Authorization: Bearer {accessToken}
Content-Type: application/json
```

**Request Body:**
```json
{
    "theme": "dark",
    "language": "en",
    "timezone": "America/New_York",
    "pushWebNotifications": false,
    "notificationsTickets": true
}
```

**Validaciones:**
- `theme`: debe ser "light" o "dark"
- `language`: debe ser "es" o "en"
- `timezone`: zona horaria IANA válida

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "userId": "550e8400-e29b-41d4-a716-446655440000",
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

**Rate Limit:** 50 por hora
**Auditoría:** `preferences_update`

**Diferencia con updateMyProfile:**
- ✅ Rate limit más alto (preferencias cambian más frecuente)
- ✅ Validaciones diferentes
- ✅ Formularios separados en frontend

---

### 3. `suspendUser` → `POST /api/users/{id}/suspend`

**REST API:**

**Endpoint:**
```
POST /api/users/{id}/suspend
Authorization: Bearer {accessToken}
Content-Type: application/json
```

**URL Parameters:**
- `id` (uuid, required) - ID del usuario a suspender

**Request Body:**
```json
{
    "reason": "Violación de términos de servicio - spam de tickets"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "userId": "550e8400-e29b-41d4-a716-446655440000",
        "status": "suspended",
        "updatedAt": "2025-10-03T16:40:00Z"
    }
}
```

**Efectos:**
- Cambia status a "suspended"
- Invalida todos los tokens activos
- Registra motivo en auditoría
- Envía notificación al usuario

**Permisos:** Solo PLATFORM_ADMIN
**Auditoría:** `user_suspend` (con payload)

---

### 4. `activateUser` → `POST /api/users/{id}/activate`

**REST API:**

**Endpoint:**
```
POST /api/users/{id}/activate
Authorization: Bearer {accessToken}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "userId": "550e8400-e29b-41d4-a716-446655440000",
        "status": "active",
        "updatedAt": "2025-10-03T16:45:00Z"
    }
}
```

**Efectos:**
- Cambia status a "active"
- Permite nuevo login
- Registra reactivación en auditoría

**Permisos:** Solo PLATFORM_ADMIN
**Auditoría:** `user_activate`

---

### 5. `deleteUser` → `DELETE /api/users/{id}`

**REST API:**

**Endpoint:**
```
DELETE /api/users/{id}?reason=Solicitud+del+usuario+-+GDPR+compliance
Authorization: Bearer {accessToken}
```

**Query Parameters:**
- `reason` (string, optional) - Motivo de eliminación

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Usuario eliminado exitosamente"
}
```

**Efectos:**
- Cambia status a "deleted"
- Establece deletedAt timestamp
- Anonimiza datos sensibles
- Mantiene registros para auditoría

**Permisos:** Solo PLATFORM_ADMIN
**Auditoría:** `user_delete` (con payload)

---

### 6. `assignRole` → `POST /api/users/{id}/roles`

**GraphQL:**
```graphql
mutation AssignRole($input: AssignRoleInput!) {
    assignRole(input: $input) {
        success
        message
        role { ... }
    }
}
```

**REST API:**

**Endpoint:**
```
POST /api/users/{id}/roles
Authorization: Bearer {accessToken}
Content-Type: application/json
```

**URL Parameters:**
- `id` (uuid, required) - ID del usuario

**Request Body (Rol CON Empresa - AGENT/COMPANY_ADMIN):**
```json
{
    "roleCode": "AGENT",
    "companyId": "cmp-001"
}
```

**Request Body (Rol SIN Empresa - USER/PLATFORM_ADMIN):**
```json
{
    "roleCode": "USER"
}
```

**Validaciones Críticas:**

| Rol | Requiere Empresa | companyId |
|-----|------------------|----|
| USER | ❌ NO | Debe ser null/omitido |
| PLATFORM_ADMIN | ❌ NO | Debe ser null/omitido |
| AGENT | ✅ SÍ | Obligatorio |
| COMPANY_ADMIN | ✅ SÍ | Obligatorio |

**Response (200 OK - Nuevo Rol):**
```json
{
    "success": true,
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
            "assignedAt": "2025-10-03T16:45:00Z"
        }
    }
}
```

**Response (200 OK - Rol Reactivado):**
```json
{
    "success": true,
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
            "assignedAt": "2025-03-10T10:15:00Z"
        }
    }
}
```

**Lógica Inteligente:**
```
IF rol existe inactivo THEN
    reactivar (isActive = true, revokedAt = null)
    RETURN "reactivado"
ELSE
    crear nuevo rol
    RETURN "asignado"
END
```

**Permisos:** PLATFORM_ADMIN o COMPANY_ADMIN (su empresa)
**Rate Limit:** 100 por hora
**Auditoría:** `role_assign` (con payload)

**Errores:**
```json
{
    "success": false,
    "code": "ROLE_REQUIRES_COMPANY",
    "message": "El rol AGENT requiere empresa asociada",
    "data": {
        "roleCode": "AGENT"
    }
}
```

---

### 7. `removeRole` → `DELETE /api/users/roles/{roleId}`

**GraphQL:**
```graphql
mutation RemoveRole($roleId: UUID!, $reason: String) {
    removeRole(roleId: $roleId, reason: $reason)
}
```

**REST API:**

**Endpoint:**
```
DELETE /api/users/roles/{roleId}?reason=Usuario+dejó+de+trabajar+en+la+empresa
Authorization: Bearer {accessToken}
```

**URL Parameters:**
- `roleId` (uuid, required) - ID del rol a remover

**Query Parameters:**
- `reason` (string, optional) - Motivo de remoción

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Rol removido exitosamente"
}
```

**Efectos:**
- Establece isActive = false
- Registra revokedAt timestamp
- Guarda revocationReason
- Invalida permisos derivados

**Permisos:** PLATFORM_ADMIN o COMPANY_ADMIN (su empresa)
**Auditoría:** `role_remove` (con payload)

**Reactivación:**
Para reactivar el rol, usar `POST /api/users/{id}/roles` con los mismos parámetros.

---

## 📦 Tipos y Estructuras

### User

```json
{
    "id": "uuid",
    "userCode": "string",
    "email": "email",
    "emailVerified": "boolean",
    "status": "active|suspended|deleted",
    "authProvider": "local|google|facebook",
    "profile": {
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
        "createdAt": "datetime",
        "updatedAt": "datetime"
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
    "lastLoginAt": "datetime|null",
    "lastActivityAt": "datetime|null",
    "createdAt": "datetime",
    "updatedAt": "datetime",
    "deletedAt": "datetime|null"
}
```

### UserProfile

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
    "lastActivityAt": "datetime|null",
    "createdAt": "datetime",
    "updatedAt": "datetime"
}
```

### UserPreferences

```json
{
    "theme": "light|dark",
    "language": "es|en",
    "timezone": "string",
    "pushWebNotifications": "boolean",
    "notificationsTickets": "boolean",
    "updatedAt": "datetime"
}
```

### RoleInfo

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

### UserRoleInfo

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
    "assignedAt": "datetime",
    "assignedBy": {
        "id": "uuid",
        "userCode": "string",
        "email": "email"
    } | null
}
```

---

## 🔒 Validaciones y Permisos

### Matriz de Permisos

| Endpoint | USER | AGENT | COMPANY_ADMIN | PLATFORM_ADMIN |
|----------|------|-------|---------------|----------------|
| GET `/api/users/me` | ✅ | ✅ | ✅ | ✅ |
| GET `/api/users/me/profile` | ✅ | ✅ | ✅ | ✅ |
| PATCH `/api/users/me/profile` | ✅ | ✅ | ✅ | ✅ |
| PATCH `/api/users/me/preferences` | ✅ | ✅ | ✅ | ✅ |
| GET `/api/users` | ❌ | ❌ | ✅ (empresa) | ✅ (todos) |
| GET `/api/users/{id}` | ❌ | ❌ | ✅ (empresa) | ✅ (todos) |
| POST `/api/users/{id}/suspend` | ❌ | ❌ | ❌ | ✅ |
| POST `/api/users/{id}/activate` | ❌ | ❌ | ❌ | ✅ |
| DELETE `/api/users/{id}` | ❌ | ❌ | ❌ | ✅ |
| POST `/api/users/{id}/roles` | ❌ | ❌ | ✅ (su empresa) | ✅ (todos) |
| DELETE `/api/users/roles/{roleId}` | ❌ | ❌ | ✅ (su empresa) | ✅ (todos) |
| GET `/api/roles` | ❌ | ❌ | ✅ | ✅ |

### Validaciones de Entrada

**updateMyProfile:**
- `firstName`: `min:2`, `max:100`
- `lastName`: `min:2`, `max:100`
- `phoneNumber`: `min:10`, `max:20`
- `avatarUrl`: URL válida

**updateMyPreferences:**
- `theme`: `in:light,dark`
- `language`: `in:es,en`
- `timezone`: IANA timezone válida
- `pushWebNotifications`: boolean
- `notificationsTickets`: boolean

**assignRole:**
- `roleCode`: required, válido (USER|AGENT|COMPANY_ADMIN|PLATFORM_ADMIN)
- `companyId`: requerido si el rol lo necesita

---

## 💡 Ejemplos Completos

### Caso 1: Actualizar Perfil Personal

**Step 1: Obtener perfil actual**
```bash
GET /api/users/me/profile
Authorization: Bearer {accessToken}
```

**Step 2: Actualizar datos personales**
```bash
PATCH /api/users/me/profile
Authorization: Bearer {accessToken}
Content-Type: application/json

{
    "firstName": "María Alejandra",
    "phoneNumber": "+591 75987654"
}
```

**Step 3: Actualizar preferencias (separado)**
```bash
PATCH /api/users/me/preferences
Authorization: Bearer {accessToken}
Content-Type: application/json

{
    "theme": "dark",
    "language": "es"
}
```

---

### Caso 2: Asignación de Rol de Agente

**Step 1: Admin busca usuario**
```bash
GET /api/users?search=juan&page=1&per_page=10
Authorization: Bearer {adminToken}
```

**Step 2: Admin asigna rol AGENT**
```bash
POST /api/users/usr-123/roles
Authorization: Bearer {adminToken}
Content-Type: application/json

{
    "roleCode": "AGENT",
    "companyId": "cmp-001"
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "success": true,
        "message": "Rol AGENT asignado exitosamente",
        "role": {
            "id": "role-002",
            "roleCode": "AGENT",
            "roleName": "Agente de Soporte",
            "company": {
                "id": "cmp-001",
                "name": "Universidad del Valle"
            },
            "isActive": true,
            "assignedAt": "2025-10-03T16:45:00Z"
        }
    }
}
```

**Step 3: Usuario ahora tiene 2 roles activos**
- USER (global)
- AGENT (en Universidad del Valle)

---

### Caso 3: Usuario Deja de Ser Agente

**Step 1: Admin remueve rol**
```bash
DELETE /api/users/roles/role-002?reason=Usuario+dejó+de+trabajar+aquí
Authorization: Bearer {adminToken}
```

**Step 2: Efectos**
- Rol se desactiva (soft delete)
- Usuario pierde acceso al dashboard de agente
- Próximo login solo muestra rol USER

**Step 3: Si vuelve a la empresa**
```bash
POST /api/users/usr-123/roles
Authorization: Bearer {adminToken}
Content-Type: application/json

{
    "roleCode": "AGENT",
    "companyId": "cmp-001"
}
```

**Respuesta:**
```json
{
    "message": "Rol AGENT reactivado exitosamente"
}
```

---

## 🚨 Códigos de Error

### HTTP Status Codes

| Código | Scenario |
|--------|----------|
| `200` | Operación exitosa |
| `201` | Recurso creado |
| `204` | Operación exitosa sin contenido |
| `400` | Validación fallida |
| `401` | Token inválido/expirado |
| `403` | Permisos insuficientes |
| `404` | Recurso no encontrado |
| `409` | Conflicto (ej. rol ya existe activo) |
| `422` | Validación fallida |
| `429` | Rate limit excedido |

### Error Response Format

```json
{
    "success": false,
    "code": "ERROR_CODE",
    "message": "Mensaje de error legible",
    "data": {
        "field": "Campo que falló",
        "details": "Detalles adicionales"
    }
}
```

### Error Codes (User Management)

| Código | HTTP | Descripción |
|--------|------|-------------|
| `USER_NOT_FOUND` | 404 | Usuario no existe |
| `EMAIL_ALREADY_EXISTS` | 409 | Email ya registrado |
| `INVALID_ROLE_ASSIGNMENT` | 422 | Asignación de rol inválida |
| `ROLE_REQUIRES_COMPANY` | 422 | Rol requiere empresa pero no se proporcionó |
| `ROLE_SHOULD_NOT_HAVE_COMPANY` | 422 | Rol no debería tener empresa pero se proporcionó |
| `INSUFFICIENT_PERMISSIONS` | 403 | Usuario no tiene permisos |
| `PROFILE_UPDATE_FAILED` | 400 | Fallo al actualizar perfil |
| `USER_SUSPENDED` | 403 | Usuario está suspendido |
| `USER_ALREADY_HAS_ROLE` | 409 | Usuario ya tiene este rol activo |
| `CANNOT_REMOVE_LAST_ADMIN` | 409 | No puede remover último admin |
| `INVALID_INPUT` | 422 | Entrada inválida |

---

## ⏱️ Rate Limiting

| Endpoint | Máximo | Ventana |
|----------|--------|---------|
| `PATCH /api/users/me/profile` | 30 | 1 hora |
| `PATCH /api/users/me/preferences` | 50 | 1 hora |
| `POST /api/users/{id}/roles` | 100 | 1 hora |

**Header de Respuesta:**
```
X-RateLimit-Limit: 30
X-RateLimit-Remaining: 28
X-RateLimit-Reset: 1634839200
```

**Error de Rate Limit (429):**
```json
{
    "success": false,
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Demasiadas solicitudes. Intente después de 1 hora.",
    "data": {
        "retryAfter": 3600
    }
}
```

---

## 📊 Auditoría

### Eventos Registrados

| Evento | Endpoint | Payload |
|--------|----------|---------|
| `profile_update` | PATCH `/api/users/me/profile` | ✅ Sí |
| `preferences_update` | PATCH `/api/users/me/preferences` | ✅ Sí |
| `user_suspend` | POST `/api/users/{id}/suspend` | ✅ Sí |
| `user_activate` | POST `/api/users/{id}/activate` | ❌ No |
| `user_delete` | DELETE `/api/users/{id}` | ✅ Sí |
| `role_assign` | POST `/api/users/{id}/roles` | ✅ Sí |
| `role_remove` | DELETE `/api/users/roles/{roleId}` | ✅ Sí |

### Audit Log Format

```json
{
    "id": "uuid",
    "action": "profile_update",
    "userId": "uuid",
    "performedBy": "uuid",
    "ipAddress": "192.168.1.1",
    "userAgent": "Mozilla/5.0...",
    "payload": {
        "changes": {
            "firstName": {
                "from": "María",
                "to": "María Alejandra"
            }
        }
    },
    "createdAt": "2025-10-03T16:30:00Z"
}
```

---

## 📝 Checklist de Implementación

### Backend (Laravel)

**Controllers:**
- [ ] Crear `UserController` con métodos REST
  - [ ] `me()` → GET /api/users/me
  - [ ] `profile()` → GET /api/users/me/profile
  - [ ] `list()` → GET /api/users
  - [ ] `show()` → GET /api/users/{id}
  - [ ] `updateProfile()` → PATCH /api/users/me/profile
  - [ ] `updatePreferences()` → PATCH /api/users/me/preferences
  - [ ] `suspend()` → POST /api/users/{id}/suspend
  - [ ] `activate()` → POST /api/users/{id}/activate
  - [ ] `delete()` → DELETE /api/users/{id}
- [ ] Crear `RoleController` con métodos REST
  - [ ] `available()` → GET /api/roles
  - [ ] `assign()` → POST /api/users/{id}/roles
  - [ ] `remove()` → DELETE /api/users/roles/{roleId}

**FormRequests:**
- [ ] Crear `UpdateProfileRequest` con validaciones
- [ ] Crear `UpdatePreferencesRequest` con validaciones
- [ ] Crear `AssignRoleRequest` con validaciones
- [ ] Crear `SuspendUserRequest` (optional)

**Resources:**
- [ ] Crear `UserResource` (respuesta de usuario completo)
- [ ] Crear `UserProfileResource` (respuesta de perfil)
- [ ] Crear `UserPreferencesResource` (respuesta de preferencias)
- [ ] Crear `RoleInfoResource` (información de rol)
- [ ] Crear `UserRoleInfoResource` (rol asignado)
- [ ] Crear `UserPaginatorResource` (lista paginada)

**Routes:**
- [ ] Registrar rutas en `routes/api.php`
- [ ] Usar middleware `jwt.require` en rutas protegidas
- [ ] Implementar rate limiting
- [ ] Implementar auditoría

**Services:**
- [ ] Verificar que `UserService` tiene toda la lógica
- [ ] Verificar que `RoleService` tiene lógica de asignación inteligente

**Testing:**
- [ ] Tests para cada endpoint GET
- [ ] Tests para cada endpoint POST/PATCH/DELETE
- [ ] Tests de validación de entrada
- [ ] Tests de permisos
- [ ] Tests de rate limiting

### Frontend (React)

**Hooks:**
- [ ] Actualizar `useUser()` para usar REST en lugar de GraphQL
- [ ] Actualizar `useProfile()` para usar REST
- [ ] Crear `useUsers()` para listado de usuarios
- [ ] Crear `useRoles()` para obtener roles disponibles

**Queries:**
- [ ] Reemplazar `GET_ME` con `GET /api/users/me`
- [ ] Reemplazar `GET_MY_PROFILE` con `GET /api/users/me/profile`
- [ ] Reemplazar `GET_USERS` con `GET /api/users`
- [ ] Reemplazar `GET_USER` con `GET /api/users/{id}`
- [ ] Reemplazar `GET_AVAILABLE_ROLES` con `GET /api/roles`

**Mutations:**
- [ ] Reemplazar `UPDATE_MY_PROFILE` con `PATCH /api/users/me/profile`
- [ ] Reemplazar `UPDATE_MY_PREFERENCES` con `PATCH /api/users/me/preferences`
- [ ] Reemplazar `SUSPEND_USER` con `POST /api/users/{id}/suspend`
- [ ] Reemplazar `ACTIVATE_USER` con `POST /api/users/{id}/activate`
- [ ] Reemplazar `DELETE_USER` con `DELETE /api/users/{id}`
- [ ] Reemplazar `ASSIGN_ROLE` con `POST /api/users/{id}/roles`
- [ ] Reemplazar `REMOVE_ROLE` con `DELETE /api/users/roles/{roleId}`

**Components:**
- [ ] Actualizar componentes que usan datos de usuario
- [ ] Actualizar formularios de perfil
- [ ] Actualizar gestión de roles
- [ ] Implementar helper functions para cálculos de permisos

**Testing:**
- [ ] Tests de React para cada hook
- [ ] Tests de integración de formularios
- [ ] Tests de permisos en UI

### Documentación

- [ ] Actualizar OpenAPI/Swagger para nuevos endpoints
- [ ] Crear ejemplos de cURL para cada endpoint
- [ ] Documentar códigos de error
- [ ] Documentar rate limiting

---

## 🎯 Resumen Final

### Mapeo Completo

**5 Queries GraphQL → 5 GET Endpoints**
- ✅ `me` → `GET /api/users/me`
- ✅ `myProfile` → `GET /api/users/me/profile`
- ✅ `users` → `GET /api/users`
- ✅ `user` → `GET /api/users/{id}`
- ✅ `availableRoles` → `GET /api/roles`

**7 Mutations GraphQL → 7 REST Endpoints**
- ✅ `updateMyProfile` → `PATCH /api/users/me/profile`
- ✅ `updateMyPreferences` → `PATCH /api/users/me/preferences`
- ✅ `suspendUser` → `POST /api/users/{id}/suspend`
- ✅ `activateUser` → `POST /api/users/{id}/activate`
- ✅ `deleteUser` → `DELETE /api/users/{id}`
- ✅ `assignRole` → `POST /api/users/{id}/roles`
- ✅ `removeRole` → `DELETE /api/users/roles/{roleId}`

### Características Garantizadas

- ✅ **100% Paridad Funcional** - Todos los endpoints tienen exactamente la misma lógica que GraphQL
- ✅ **Mismos Tipos de Datos** - Respuestas idénticas (JSON en lugar de GraphQL)
- ✅ **Mismas Validaciones** - Reglas de validación idénticas
- ✅ **Mismo Sistema de Permisos** - Matriz de permisos 100% igual
- ✅ **Mismo Rate Limiting** - Límites de solicitud idénticos
- ✅ **Misma Auditoría** - Eventos y logging idénticos

---

**Fin del Mapeo GraphQL a REST**

> **Próximo Paso:** Implementación backend siguiendo estructura feature-first
> **Rama:** `feature/graphql-to-rest-migration`
> **Estado:** Ready for Development
