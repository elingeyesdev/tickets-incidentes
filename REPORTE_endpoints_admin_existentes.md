# Endpoints REST API - PLATFORM_ADMIN

**Fecha:** 2025-11-12
**Versión:** 1.0
**Base URL:** `https://api.helpdesk.com/api`

---

## Tabla de Contenidos

1. [Resumen de Endpoints](#resumen-de-endpoints)
2. [Autenticación](#autenticación)
3. [Endpoints Exclusivos PLATFORM_ADMIN](#endpoints-exclusivos-platform_admin)
4. [Endpoints Compartidos (PLATFORM_ADMIN + COMPANY_ADMIN)](#endpoints-compartidos-platform_admin--company_admin)
5. [Códigos de Respuesta](#códigos-de-respuesta)
6. [Errores Comunes](#errores-comunes)
7. [Casos de Uso Prácticos](#casos-de-uso-prácticos)

---

## Resumen de Endpoints

### Tabla Resumen

| # | Método | Endpoint | Descripción | Acceso |
|---|--------|----------|-------------|--------|
| 1 | `PUT` | `/users/{id}/status` | Suspender o activar usuarios | PLATFORM_ADMIN |
| 2 | `DELETE` | `/users/{id}` | Eliminar usuario (soft delete) | PLATFORM_ADMIN |
| 3 | `POST` | `/companies` | Crear nueva empresa | PLATFORM_ADMIN |
| 4 | `GET` | `/company-requests` | Listar solicitudes de empresa | PLATFORM_ADMIN |
| 5 | `POST` | `/company-requests/{id}/approve` | Aprobar solicitud de empresa | PLATFORM_ADMIN |
| 6 | `POST` | `/company-requests/{id}/reject` | Rechazar solicitud de empresa | PLATFORM_ADMIN |
| 7 | `GET` | `/users` | Listar usuarios con filtros | PLATFORM_ADMIN + COMPANY_ADMIN |
| 8 | `GET` | `/roles` | Listar roles disponibles | PLATFORM_ADMIN + COMPANY_ADMIN |
| 9 | `POST` | `/users/{userId}/roles` | Asignar rol a usuario | PLATFORM_ADMIN + COMPANY_ADMIN |
| 10 | `DELETE` | `/users/roles/{roleId}` | Remover rol de usuario | PLATFORM_ADMIN + COMPANY_ADMIN |
| 11 | `GET` | `/companies` | Listar empresas | PLATFORM_ADMIN + COMPANY_ADMIN |

---

## Autenticación

**Tipo:** JWT Bearer Token

Todos los endpoints requieren un token JWT válido en el header de autenticación.

### Header Requerido

```http
Authorization: Bearer <jwt_token>
```

### Ejemplo de Token

```bash
curl -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." \
     https://api.helpdesk.com/api/users
```

### Obtener Token

El token se obtiene mediante el endpoint de login:

```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "admin@platform.com",
  "password": "SecurePassword123"
}
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

---

## Endpoints Exclusivos PLATFORM_ADMIN

### 1. PUT `/users/{id}/status` - Actualizar Estado de Usuario

Permite suspender o activar usuarios del sistema.

#### Autenticación
- **Requerida:** Sí
- **Rol:** PLATFORM_ADMIN (exclusivo)

#### Parámetros de Ruta
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | UUID | ID del usuario a modificar |

#### Body (JSON)
| Campo | Tipo | Requerido | Descripción | Validación |
|-------|------|-----------|-------------|------------|
| `status` | string | Sí | Estado del usuario | `active` o `suspended` |
| `reason` | string | Condicional | Razón del cambio | Requerido si `status=suspended`. Min: 10 chars, Max: 500 chars |

#### Ejemplo de Request

```bash
PUT /api/users/550e8400-e29b-41d4-a716-446655440000/status
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json

{
  "status": "suspended",
  "reason": "Violación de términos de servicio - spam repetido en tickets"
}
```

#### Respuestas

**200 OK - Usuario suspendido exitosamente**
```json
{
  "data": {
    "userId": "550e8400-e29b-41d4-a716-446655440000",
    "status": "suspended",
    "updatedAt": "2025-11-12T14:30:00+00:00"
  }
}
```

**200 OK - Usuario activado exitosamente**
```json
{
  "data": {
    "userId": "550e8400-e29b-41d4-a716-446655440000",
    "status": "active",
    "updatedAt": "2025-11-12T14:35:00+00:00"
  }
}
```

**401 Unauthorized - Token inválido o ausente**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden - Usuario no es PLATFORM_ADMIN**
```json
{
  "code": "INSUFFICIENT_PERMISSIONS",
  "message": "Only platform administrators can update user status"
}
```

**404 Not Found - Usuario no existe**
```json
{
  "code": "USER_NOT_FOUND",
  "message": "User not found"
}
```

**422 Unprocessable Entity - Validación fallida**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "status": ["Status must be either \"active\" or \"suspended\""],
    "reason": ["Reason must be at least 10 characters"]
  }
}
```

**400 Bad Request - Error al actualizar**
```json
{
  "code": "STATUS_UPDATE_FAILED",
  "message": "Failed to update user status: [detalles del error]"
}
```

#### Validaciones Importantes
- El campo `reason` es **obligatorio** cuando `status=suspended`
- El campo `reason` es **opcional** cuando `status=active`
- No se puede suspender al propio usuario PLATFORM_ADMIN (aunque no hay validación explícita para esto)

#### Rate Limiting
- No hay rate limiting específico para este endpoint

---

### 2. DELETE `/users/{id}` - Eliminar Usuario (Soft Delete)

Elimina un usuario del sistema mediante soft delete (mantiene registros para auditoría).

#### Autenticación
- **Requerida:** Sí
- **Rol:** PLATFORM_ADMIN (exclusivo)

#### Parámetros de Ruta
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | UUID | ID del usuario a eliminar |

#### Query Parameters
| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `reason` | string | No | Razón de la eliminación (para auditoría) |

#### Ejemplo de Request

```bash
DELETE /api/users/550e8400-e29b-41d4-a716-446655440000?reason=Cuenta%20duplicada
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Respuestas

**200 OK - Usuario eliminado exitosamente**
```json
{
  "data": {
    "success": true
  }
}
```

**401 Unauthorized - Token inválido**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden - No es PLATFORM_ADMIN**
```json
{
  "code": "INSUFFICIENT_PERMISSIONS",
  "message": "Only platform administrators can delete users"
}
```

**404 Not Found - Usuario no existe**
```json
{
  "code": "USER_NOT_FOUND",
  "message": "User not found"
}
```

**422 Unprocessable Entity - Intento de auto-eliminación**
```json
{
  "code": "CANNOT_DELETE_SELF",
  "message": "You cannot delete your own account"
}
```

**400 Bad Request - Error al eliminar**
```json
{
  "code": "DELETE_FAILED",
  "message": "Failed to delete user: [detalles del error]"
}
```

#### Validaciones Importantes
- **NO se puede eliminar la propia cuenta** (validación explícita)
- La eliminación es **soft delete** (marca `status=deleted` y `deleted_at`)
- Los registros se mantienen para auditoría

#### Efectos del Soft Delete
1. Campo `status` cambia a `deleted`
2. Campo `deleted_at` se actualiza con timestamp actual
3. Usuario no puede hacer login
4. Registros históricos (tickets, comentarios) se mantienen

---

### 3. POST `/companies` - Crear Nueva Empresa

Crea una empresa directamente sin pasar por el proceso de solicitud.

#### Autenticación
- **Requerida:** Sí
- **Rol:** PLATFORM_ADMIN (exclusivo)

#### Throttling
- **Límite:** 10 requests por hora

#### Body (JSON)
| Campo | Tipo | Requerido | Descripción | Validación |
|-------|------|-----------|-------------|------------|
| `name` | string | Sí | Nombre comercial | Min: 2, Max: 200 |
| `legal_name` | string | No | Nombre legal | Min: 2, Max: 200 |
| `description` | string | No | Descripción de la empresa | Max: 1000 |
| `industry_id` | UUID | Sí | ID de la industria | Debe existir en `company_industries` |
| `admin_user_id` | UUID | Sí | ID del usuario administrador | Debe existir en `users` |
| `support_email` | email | No | Email de soporte | Max: 255 |
| `phone` | string | No | Teléfono de contacto | Max: 20 |
| `website` | URL | No | Sitio web | Max: 255 |
| `contact_address` | string | No | Dirección física | Max: 255 |
| `contact_city` | string | No | Ciudad | Max: 100 |
| `contact_state` | string | No | Estado/Región | Max: 100 |
| `contact_country` | string | No | País | Max: 100 |
| `contact_postal_code` | string | No | Código postal | Max: 20 |
| `tax_id` | string | No | RUT/NIT | Max: 50 |
| `legal_representative` | string | No | Representante legal | Max: 255 |
| `business_hours` | JSON | No | Horarios de atención | Objeto JSONB |
| `timezone` | string | No | Zona horaria | Ejemplo: `America/Santiago` |
| `settings` | JSON | No | Configuraciones adicionales | Objeto JSONB |

#### Ejemplo de Request

```bash
POST /api/companies
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json

{
  "name": "Acme Corporation",
  "legal_name": "Acme Corp S.A.",
  "description": "Empresa líder en soluciones tecnológicas",
  "industry_id": "7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f",
  "admin_user_id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
  "support_email": "support@acme.com",
  "phone": "+56912345678",
  "website": "https://acme.com",
  "contact_address": "Av. Principal 123",
  "contact_city": "Santiago",
  "contact_country": "Chile",
  "contact_postal_code": "8340000",
  "tax_id": "12.345.678-9",
  "timezone": "America/Santiago",
  "business_hours": {
    "monday": {"open": "09:00", "close": "18:00"},
    "tuesday": {"open": "09:00", "close": "18:00"},
    "wednesday": {"open": "09:00", "close": "18:00"},
    "thursday": {"open": "09:00", "close": "18:00"},
    "friday": {"open": "09:00", "close": "18:00"}
  }
}
```

#### Respuestas

**201 Created - Empresa creada exitosamente**
```json
{
  "id": "9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a",
  "company_code": "CMP-20250001",
  "name": "Acme Corporation",
  "legal_name": "Acme Corp S.A.",
  "status": "active",
  "admin": {
    "id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
    "user_code": "USR-20250010",
    "email": "admin@acme.com",
    "profile": {
      "display_name": "John Doe"
    }
  },
  "industry": {
    "id": "7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f",
    "code": "TECH",
    "name": "Tecnología"
  },
  "created_at": "2025-11-12T14:30:00+00:00"
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden - No es PLATFORM_ADMIN**
```json
{
  "message": "Forbidden."
}
```

**422 Unprocessable Entity - Validación fallida**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["El nombre de la empresa es obligatorio."],
    "industry_id": ["La industria seleccionada no es válida."],
    "admin_user_id": ["Este usuario ya es administrador de otra empresa activa."]
  }
}
```

**429 Too Many Requests - Rate limit excedido**
```json
{
  "message": "Too Many Requests",
  "retry_after": 3600
}
```

#### Efectos Automáticos
1. Se crea la empresa con `status=active`
2. Se genera automáticamente un `company_code` único
3. Se asigna el rol `COMPANY_ADMIN` al usuario especificado en `admin_user_id`
4. El usuario administrador queda vinculado a la empresa

#### Validaciones Especiales
- El `admin_user_id` **no puede** ser administrador de otra empresa activa
- El `industry_id` debe existir en la tabla `company_industries`
- El `website` debe ser una URL válida (si se proporciona)

---

### 4. GET `/company-requests` - Listar Solicitudes de Empresa

Obtiene la lista paginada de todas las solicitudes de empresa.

#### Autenticación
- **Requerida:** Sí
- **Rol:** PLATFORM_ADMIN (exclusivo)

#### Query Parameters
| Parámetro | Tipo | Requerido | Descripción | Valores |
|-----------|------|-----------|-------------|---------|
| `status` | string | No | Filtrar por estado | `PENDING`, `APPROVED`, `REJECTED` |
| `search` | string | No | Buscar por nombre de empresa | - |
| `sort` | string | No | Campo para ordenar | - |
| `order` | string | No | Dirección del ordenamiento | `asc`, `desc` |
| `per_page` | integer | No | Elementos por página | Default: 15 |
| `page` | integer | No | Número de página | Default: 1 |

#### Ejemplo de Request

```bash
GET /api/company-requests?status=PENDING&per_page=20&page=1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Respuestas

**200 OK - Lista de solicitudes**
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "requestCode": "REQ-20251101-001",
      "companyName": "TechCorp Solutions",
      "legalName": "TechCorp Solutions S.A.",
      "adminEmail": "admin@techcorp.com",
      "businessDescription": "Empresa líder en soluciones tecnológicas con más de 10 años de experiencia",
      "requestMessage": "Necesitamos un sistema profesional de helpdesk para nuestro equipo de soporte",
      "website": "https://techcorp.com",
      "industryId": "650e8400-e29b-41d4-a716-446655440001",
      "industry": {
        "id": "650e8400-e29b-41d4-a716-446655440001",
        "code": "TECH",
        "name": "Technology"
      },
      "estimatedUsers": 500,
      "contactAddress": "Main Avenue 123, Office 456",
      "contactCity": "Santiago",
      "contactCountry": "Chile",
      "contactPostalCode": "8340000",
      "taxId": "12.345.678-9",
      "status": "PENDING",
      "reviewedAt": null,
      "rejectionReason": null,
      "reviewer": null,
      "createdCompany": null,
      "createdAt": "2025-11-01T10:00:00Z",
      "updatedAt": "2025-11-01T10:00:00Z"
    }
  ],
  "meta": {
    "total": 45,
    "current_page": 1,
    "last_page": 3,
    "per_page": 20
  },
  "links": {
    "first": "https://api.helpdesk.com/api/company-requests?page=1",
    "last": "https://api.helpdesk.com/api/company-requests?page=3",
    "prev": null,
    "next": "https://api.helpdesk.com/api/company-requests?page=2"
  }
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden - No es PLATFORM_ADMIN**
```json
{
  "message": "Forbidden - requires PLATFORM_ADMIN role"
}
```

#### Relaciones Eager-Loaded
- `reviewer.profile` - Usuario que revisó la solicitud
- `createdCompany` - Empresa creada (si fue aprobada)
- `industry` - Industria seleccionada

---

### 5. POST `/company-requests/{companyRequest}/approve` - Aprobar Solicitud

Aprueba una solicitud de empresa pendiente.

#### Autenticación
- **Requerida:** Sí
- **Rol:** PLATFORM_ADMIN (exclusivo)

#### Parámetros de Ruta
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `companyRequest` | UUID | ID de la solicitud a aprobar |

#### Body (JSON)
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `notes` | string | No | Notas adicionales |

#### Ejemplo de Request

```bash
POST /api/company-requests/9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a/approve
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json

{
  "notes": "Solicitud aprobada después de verificar documentación"
}
```

#### Respuestas

**200 OK - Solicitud aprobada (usuario nuevo creado)**
```json
{
  "data": {
    "success": true,
    "message": "Solicitud aprobada exitosamente. Se ha creado la empresa 'TechCorp Bolivia' y se envió un email con las credenciales de acceso a admin@techcorp.com.bo.",
    "company": {
      "id": "9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a",
      "companyCode": "COMP-20250001",
      "name": "TechCorp Bolivia",
      "legalName": "TechCorp Bolivia S.R.L.",
      "description": "Empresa líder en soluciones tecnológicas",
      "status": "ACTIVE",
      "industryId": "7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f",
      "industry": {
        "id": "7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f",
        "code": "TECH",
        "name": "Tecnología"
      },
      "adminId": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
      "adminEmail": "admin@techcorp.com.bo",
      "adminName": "Juan Carlos Pérez",
      "createdAt": "2025-11-01T10:30:00+00:00"
    },
    "newUserCreated": true,
    "notificationSentTo": "admin@techcorp.com.bo"
  }
}
```

**200 OK - Solicitud aprobada (usuario existente)**
```json
{
  "data": {
    "success": true,
    "message": "Solicitud aprobada exitosamente. Se ha creado la empresa 'TechCorp Bolivia' y se asignó el rol de administrador al usuario existente.",
    "company": {
      "id": "9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a",
      "companyCode": "COMP-20250001",
      "name": "TechCorp Bolivia"
    },
    "newUserCreated": false,
    "notificationSentTo": "admin@techcorp.com.bo"
  }
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden**
```json
{
  "message": "Forbidden - requires PLATFORM_ADMIN role"
}
```

**404 Not Found - Solicitud no existe**
```json
{
  "success": false,
  "message": "Request not found",
  "code": "REQUEST_NOT_FOUND",
  "category": "resource"
}
```

**409 Conflict - Solicitud ya procesada**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "company_request": ["Only pending requests can be approved. Current status: approved"]
  }
}
```

**422 Unprocessable Entity - Validación fallida**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "company_request": ["La solicitud de empresa no existe."]
  }
}
```

#### Efectos Automáticos
1. **Crea la empresa** con datos de la solicitud
2. **Crea usuario administrador** (si el email no existe) con:
   - Password temporal generado automáticamente
   - Válido por 7 días
3. **Asigna rol COMPANY_ADMIN** al usuario
4. **Envía email** con credenciales (si usuario nuevo) o notificación (si usuario existente)
5. Actualiza solicitud a `status=APPROVED`
6. Registra `reviewer_user_id` y `reviewed_at`

#### Validaciones
- La solicitud debe estar en estado `PENDING`
- El email del admin no debe estar ya asignado a otra empresa como administrador

---

### 6. POST `/company-requests/{companyRequest}/reject` - Rechazar Solicitud

Rechaza una solicitud de empresa pendiente.

#### Autenticación
- **Requerida:** Sí
- **Rol:** PLATFORM_ADMIN (exclusivo)

#### Parámetros de Ruta
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `companyRequest` | UUID | ID de la solicitud a rechazar |

#### Body (JSON)
| Campo | Tipo | Requerido | Descripción | Validación |
|-------|------|-----------|-------------|------------|
| `reason` | string | Sí | Razón del rechazo | Min: 10 chars, Max: 1000 chars |
| `notes` | string | No | Notas adicionales | - |

#### Ejemplo de Request

```bash
POST /api/company-requests/9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a/reject
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json

{
  "reason": "La documentación proporcionada no cumple con los requisitos mínimos. Por favor adjunte el NIT actualizado y el testimonio de constitución."
}
```

#### Respuestas

**200 OK - Solicitud rechazada**
```json
{
  "data": {
    "success": true,
    "message": "La solicitud de empresa 'TechCorp Bolivia' ha sido rechazada. Se ha enviado un email a admin@techcorp.com.bo con la razón del rechazo.",
    "reason": "La documentación proporcionada no cumple con los requisitos mínimos. Por favor adjunte el NIT actualizado y el testimonio de constitución.",
    "notificationSentTo": "admin@techcorp.com.bo",
    "requestCode": "REQ-20250001"
  }
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden**
```json
{
  "message": "Forbidden - requires PLATFORM_ADMIN role"
}
```

**404 Not Found**
```json
{
  "success": false,
  "message": "Request not found",
  "code": "REQUEST_NOT_FOUND",
  "category": "resource"
}
```

**409 Conflict - Ya procesada**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "company_request": ["Only pending requests can be rejected. Current status: approved"]
  }
}
```

**422 Unprocessable Entity - Razón faltante o inválida**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "reason": ["La razón del rechazo es obligatoria."],
    "reason": ["La razón debe tener al menos 10 caracteres."]
  }
}
```

#### Efectos Automáticos
1. Actualiza solicitud a `status=REJECTED`
2. Guarda `rejection_reason` con el motivo
3. Registra `reviewer_user_id` y `reviewed_at`
4. **Envía email** al solicitante con la razón del rechazo

#### Validaciones
- La solicitud debe estar en estado `PENDING`
- El campo `reason` es **obligatorio** (mínimo 10 caracteres)

---

## Endpoints Compartidos (PLATFORM_ADMIN + COMPANY_ADMIN)

### 7. GET `/users` - Listar Usuarios

Lista usuarios con filtros avanzados y paginación.

#### Autenticación
- **Requerida:** Sí
- **Roles:** PLATFORM_ADMIN o COMPANY_ADMIN

#### Alcance según Rol
- **PLATFORM_ADMIN:** Ve **todos** los usuarios del sistema
- **COMPANY_ADMIN:** Ve **solo** usuarios de su empresa

#### Query Parameters
| Parámetro | Tipo | Descripción | Valores/Formato |
|-----------|------|-------------|-----------------|
| `search` | string | Buscar por email, user_code o nombre | - |
| `status` | string | Filtrar por estado | `active`, `suspended`, `deleted` |
| `emailVerified` | boolean | Filtrar por verificación de email | `true`, `false` |
| `role` | string | Filtrar por rol | `USER`, `AGENT`, `COMPANY_ADMIN`, `PLATFORM_ADMIN` |
| `companyId` | UUID | Filtrar por empresa | UUID válido |
| `recentActivity` | boolean | Usuarios activos últimos 7 días | `true`, `false` |
| `createdAfter` | datetime | Creados después de fecha | ISO 8601 |
| `createdBefore` | datetime | Creados antes de fecha | ISO 8601 |
| `order_by` | string | Campo para ordenar | `created_at`, `updated_at`, `email`, `status`, `last_login_at`, `last_activity_at` |
| `order_direction` | string | Dirección orden | `asc`, `desc` (default: `desc`) |
| `page` | integer | Número de página | Default: 1 |
| `per_page` | integer | Elementos por página | Default: 15, Max: 50 |

#### Ejemplo de Request

```bash
GET /api/users?status=active&role=AGENT&per_page=20&order_by=created_at&order_direction=desc
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Respuestas

**200 OK - Lista de usuarios**
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "userCode": "USR-20250001",
      "email": "agent@acme.com",
      "status": "ACTIVE",
      "emailVerified": true,
      "authProvider": null,
      "profile": {
        "firstName": "John",
        "lastName": "Doe",
        "displayName": "John Doe",
        "avatar": "https://storage.example.com/avatars/user123.jpg"
      },
      "roleContexts": [
        {
          "id": "7b5a4c3d-9e8f-1a2b-3c4d-5e6f7a8b9c0d",
          "roleCode": "AGENT",
          "roleName": "Agent",
          "company": {
            "id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
            "name": "Acme Corporation",
            "logoUrl": "https://example.com/logos/acme.png"
          },
          "isActive": true,
          "assignedAt": "2025-11-01T14:30:00Z"
        }
      ],
      "ticketsCount": 42,
      "resolvedTicketsCount": 38,
      "averageRating": 4.5,
      "lastLoginAt": "2025-11-12T08:30:00Z",
      "lastActivityAt": "2025-11-12T14:15:00Z",
      "createdAt": "2025-01-15T10:00:00Z",
      "updatedAt": "2025-11-12T14:15:00Z"
    }
  ],
  "meta": {
    "total": 156,
    "per_page": 20,
    "current_page": 1,
    "last_page": 8
  },
  "links": {
    "first": "https://api.helpdesk.com/api/users?page=1",
    "last": "https://api.helpdesk.com/api/users?page=8",
    "prev": null,
    "next": "https://api.helpdesk.com/api/users?page=2"
  }
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden - Sin permisos**
```json
{
  "code": "INSUFFICIENT_PERMISSIONS",
  "message": "You do not have permission to list users"
}
```

---

### 8. GET `/roles` - Listar Roles Disponibles

Obtiene la lista de todos los roles disponibles en el sistema.

#### Autenticación
- **Requerida:** Sí
- **Roles:** PLATFORM_ADMIN o COMPANY_ADMIN

#### Query Parameters
Ninguno.

#### Ejemplo de Request

```bash
GET /api/roles
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Respuestas

**200 OK - Lista de roles**
```json
{
  "data": [
    {
      "code": "USER",
      "name": "User",
      "description": "Usuario estándar del sistema",
      "requiresCompany": false,
      "defaultDashboard": "/dashboard",
      "isSystemRole": true
    },
    {
      "code": "AGENT",
      "name": "Agent",
      "description": "Agente de soporte técnico",
      "requiresCompany": true,
      "defaultDashboard": "/agente/dashboard",
      "isSystemRole": true
    },
    {
      "code": "COMPANY_ADMIN",
      "name": "Company Administrator",
      "description": "Administrador de empresa",
      "requiresCompany": true,
      "defaultDashboard": "/empresa/dashboard",
      "isSystemRole": true
    },
    {
      "code": "PLATFORM_ADMIN",
      "name": "Platform Administrator",
      "description": "Administrador de plataforma",
      "requiresCompany": false,
      "defaultDashboard": "/plataforma/dashboard",
      "isSystemRole": true
    }
  ]
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden**
```json
{
  "message": "Unauthorized. Only PLATFORM_ADMIN or COMPANY_ADMIN can view roles."
}
```

---

### 9. POST `/users/{userId}/roles` - Asignar Rol a Usuario

Asigna un rol a un usuario. Si el rol ya fue asignado previamente y está inactivo, lo reactiva.

#### Autenticación
- **Requerida:** Sí
- **Roles:** PLATFORM_ADMIN o COMPANY_ADMIN

#### Throttling
- **Límite:** 100 requests por 60 minutos por usuario autenticado

#### Parámetros de Ruta
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `userId` | UUID | ID del usuario al que se asignará el rol |

#### Body (JSON)
| Campo | Tipo | Requerido | Descripción | Validación |
|-------|------|-----------|-------------|------------|
| `roleCode` | string | Sí | Código del rol | `USER`, `AGENT`, `COMPANY_ADMIN`, `PLATFORM_ADMIN` |
| `companyId` | UUID | Condicional | ID de la empresa | Requerido para `AGENT` y `COMPANY_ADMIN`. Debe ser `null` para `USER` y `PLATFORM_ADMIN` |

#### Ejemplo de Request

```bash
POST /api/users/1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d/roles
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
Content-Type: application/json

{
  "roleCode": "AGENT",
  "companyId": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f"
}
```

#### Respuestas

**201 Created - Rol asignado (nuevo)**
```json
{
  "success": true,
  "message": "Rol asignado exitosamente",
  "data": {
    "id": "7b5a4c3d-9e8f-1a2b-3c4d-5e6f7a8b9c0d",
    "roleCode": "AGENT",
    "roleName": "Agent",
    "company": {
      "id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "name": "Acme Corporation",
      "logoUrl": "https://example.com/logos/acme.png"
    },
    "isActive": true,
    "assignedAt": "2025-11-12T14:30:00Z",
    "assignedBy": {
      "id": "6a4b3c2d-8e7f-9a0b-1c2d-3e4f5a6b7c8d",
      "userCode": "USR-20250001",
      "email": "admin@example.com"
    }
  }
}
```

**200 OK - Rol reactivado (existía pero inactivo)**
```json
{
  "success": true,
  "message": "Rol reactivado exitosamente",
  "data": {
    "id": "7b5a4c3d-9e8f-1a2b-3c4d-5e6f7a8b9c0d",
    "roleCode": "AGENT",
    "roleName": "Agent",
    "company": {
      "id": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f",
      "name": "Acme Corporation",
      "logoUrl": "https://example.com/logos/acme.png"
    },
    "isActive": true,
    "assignedAt": "2025-11-12T14:30:00Z",
    "assignedBy": {
      "id": "6a4b3c2d-8e7f-9a0b-1c2d-3e4f5a6b7c8d",
      "userCode": "USR-20250001",
      "email": "admin@example.com"
    }
  }
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden**
```json
{
  "message": "Unauthorized. Only PLATFORM_ADMIN or COMPANY_ADMIN can assign roles."
}
```

**404 Not Found - Usuario no existe**
```json
{
  "message": "User not found"
}
```

**422 Unprocessable Entity - Validación fallida**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "roleCode": ["Invalid role code"],
    "companyId": ["AGENT role requires a company"],
    "companyId": ["PLATFORM_ADMIN role should not have a company"]
  }
}
```

**429 Too Many Requests**
```json
{
  "message": "Too Many Requests",
  "retry_after": 3600
}
```

#### Validaciones Especiales

**Roles que REQUIEREN empresa:**
- `AGENT`
- `COMPANY_ADMIN`

**Roles que NO deben tener empresa:**
- `USER`
- `PLATFORM_ADMIN`

Si intentas asignar `AGENT` sin `companyId`:
```json
{
  "errors": {
    "companyId": ["AGENT role requires a company"]
  }
}
```

Si intentas asignar `PLATFORM_ADMIN` con `companyId`:
```json
{
  "errors": {
    "companyId": ["PLATFORM_ADMIN role should not have a company"]
  }
}
```

---

### 10. DELETE `/users/roles/{roleId}` - Remover Rol de Usuario

Desactiva una asignación de rol (soft delete).

#### Autenticación
- **Requerida:** Sí
- **Roles:** PLATFORM_ADMIN o COMPANY_ADMIN

#### Alcance según Rol
- **PLATFORM_ADMIN:** Puede remover cualquier rol
- **COMPANY_ADMIN:** Solo puede remover roles de su propia empresa

#### Parámetros de Ruta
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `roleId` | UUID | ID del `UserRole` (NO del Role, sino de la asignación) |

#### Query Parameters
| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `reason` | string | No | Razón de la remoción (max 500 chars) |

#### Ejemplo de Request

```bash
DELETE /api/users/roles/7b5a4c3d-9e8f-1a2b-3c4d-5e6f7a8b9c0d?reason=Usuario%20cambió%20de%20departamento
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Respuestas

**200 OK - Rol removido exitosamente**
```json
{
  "success": true,
  "message": "Rol removido exitosamente"
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden - Sin permisos**
```json
{
  "message": "Unauthorized. Only PLATFORM_ADMIN or COMPANY_ADMIN can remove roles."
}
```

**403 Forbidden - COMPANY_ADMIN intentando remover rol de otra empresa**
```json
{
  "message": "Unauthorized. COMPANY_ADMIN can only remove roles from their own company."
}
```

**404 Not Found - UserRole no existe**
```json
{
  "message": "Role assignment not found"
}
```

**422 Unprocessable Entity**
```json
{
  "message": "Validation error"
}
```

#### Importante
- El `roleId` es el **ID de la asignación** (`user_roles.id`), NO el código del rol
- La remoción es **soft delete** (marca `is_active=false`)
- Se puede reactivar usando el endpoint de asignar rol

---

### 11. GET `/companies` - Listar Empresas

Lista todas las empresas con información administrativa completa.

#### Autenticación
- **Requerida:** Sí
- **Roles:** PLATFORM_ADMIN o COMPANY_ADMIN

#### Alcance según Rol
- **PLATFORM_ADMIN:** Ve **todas** las empresas
- **COMPANY_ADMIN:** Ve **solo** su propia empresa

#### Query Parameters
| Parámetro | Tipo | Descripción | Valores |
|-----------|------|-------------|---------|
| `search` | string | Buscar por nombre de empresa | - |
| `status` | string | Filtrar por estado | `active`, `suspended`, `inactive` |
| `industry_id` | UUID | Filtrar por industria | UUID válido |
| `sort_by` | string | Campo para ordenar | - |
| `sort_direction` | string | Dirección orden | `asc`, `desc` |
| `per_page` | integer | Elementos por página | Default: 20 |
| `page` | integer | Número de página | Default: 1 |

#### Ejemplo de Request

```bash
GET /api/companies?status=active&per_page=20&sort_by=created_at&sort_direction=desc
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Respuestas

**200 OK - Lista de empresas**
```json
{
  "data": [
    {
      "id": "9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a",
      "company_code": "CMP-20250001",
      "name": "Acme Corporation",
      "status": "active",
      "industry_id": "7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f",
      "industry": {
        "id": "7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f",
        "code": "TECH",
        "name": "Tecnología",
        "description": "Empresas del sector tecnológico"
      },
      "admin": {
        "id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
        "user_code": "USR-20250010",
        "email": "admin@acme.com",
        "profile": {
          "display_name": "John Doe"
        }
      },
      "followers_count": 1250,
      "active_agents_count": 45,
      "total_users_count": 320,
      "created_at": "2025-01-15T10:00:00+00:00"
    }
  ],
  "meta": {
    "total": 87,
    "current_page": 1,
    "last_page": 5,
    "per_page": 20
  },
  "links": {
    "first": "https://api.helpdesk.com/api/companies?page=1",
    "last": "https://api.helpdesk.com/api/companies?page=5",
    "prev": null,
    "next": "https://api.helpdesk.com/api/companies?page=2"
  }
}
```

**401 Unauthorized**
```json
{
  "message": "Unauthenticated."
}
```

**403 Forbidden**
```json
{
  "message": "Forbidden."
}
```

---

## Códigos de Respuesta

### Resumen de Códigos HTTP

| Código | Significado | Descripción |
|--------|-------------|-------------|
| `200` | OK | Operación exitosa |
| `201` | Created | Recurso creado exitosamente |
| `400` | Bad Request | Error en la operación |
| `401` | Unauthorized | Token JWT inválido o ausente |
| `403` | Forbidden | Usuario no tiene permisos suficientes |
| `404` | Not Found | Recurso no encontrado |
| `409` | Conflict | Conflicto con el estado actual del recurso |
| `422` | Unprocessable Entity | Error de validación |
| `429` | Too Many Requests | Rate limit excedido |

### Códigos de Error Personalizados

| Código | Descripción | Endpoint |
|--------|-------------|----------|
| `INSUFFICIENT_PERMISSIONS` | Usuario no tiene el rol necesario | Varios |
| `USER_NOT_FOUND` | Usuario no existe | `/users/{id}`, `/users/{id}/status` |
| `STATUS_UPDATE_FAILED` | Error al actualizar estado | `/users/{id}/status` |
| `CANNOT_DELETE_SELF` | Intento de auto-eliminación | `/users/{id}` |
| `DELETE_FAILED` | Error al eliminar usuario | `/users/{id}` |
| `REQUEST_NOT_FOUND` | Solicitud de empresa no existe | `/company-requests/{id}/approve`, `/company-requests/{id}/reject` |

---

## Errores Comunes

### 1. Token JWT Expirado

**Síntoma:**
```json
{
  "message": "Unauthenticated."
}
```

**Solución:**
Refrescar el token usando el endpoint `/auth/refresh`:
```bash
POST /api/auth/refresh
Authorization: Bearer <expired_token>
```

### 2. Intentar Asignar Rol sin Empresa

**Síntoma:**
```json
{
  "errors": {
    "companyId": ["AGENT role requires a company"]
  }
}
```

**Solución:**
Incluir `companyId` en el body para roles `AGENT` o `COMPANY_ADMIN`:
```json
{
  "roleCode": "AGENT",
  "companyId": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f"
}
```

### 3. Razón de Suspensión Muy Corta

**Síntoma:**
```json
{
  "errors": {
    "reason": ["Reason must be at least 10 characters"]
  }
}
```

**Solución:**
Proporcionar una razón descriptiva de al menos 10 caracteres:
```json
{
  "status": "suspended",
  "reason": "Usuario reportado por spam en múltiples tickets"
}
```

### 4. Intentar Aprobar Solicitud Ya Procesada

**Síntoma:**
```json
{
  "errors": {
    "company_request": ["Only pending requests can be approved. Current status: approved"]
  }
}
```

**Solución:**
Verificar el estado de la solicitud antes de intentar aprobar/rechazar. Solo solicitudes con `status=PENDING` pueden ser procesadas.

### 5. COMPANY_ADMIN Intentando Acceder a Usuarios de Otra Empresa

**Síntoma:**
```json
{
  "code": "INSUFFICIENT_PERMISSIONS",
  "message": "You can only view users from your company"
}
```

**Solución:**
COMPANY_ADMIN solo puede ver usuarios de su propia empresa. Este es el comportamiento esperado, no un error.

### 6. Rate Limit Excedido

**Síntoma:**
```json
{
  "message": "Too Many Requests",
  "retry_after": 3600
}
```

**Solución:**
Esperar el tiempo indicado en `retry_after` (en segundos) antes de reintentar.

**Endpoints con Rate Limiting:**
- `POST /companies` - 10 requests/hora
- `POST /users/{userId}/roles` - 100 requests/hora por usuario

---

## Casos de Uso Prácticos

### Caso 1: Suspender Usuario por Spam

**Escenario:** Un usuario ha estado enviando spam en los tickets y necesita ser suspendido.

**Pasos:**

1. Buscar el usuario:
```bash
GET /api/users?search=spammer@example.com
Authorization: Bearer <token>
```

2. Suspender el usuario:
```bash
PUT /api/users/550e8400-e29b-41d4-a716-446655440000/status
Authorization: Bearer <token>
Content-Type: application/json

{
  "status": "suspended",
  "reason": "Usuario reportado por spam en múltiples tickets. Acción de moderación automática."
}
```

3. Verificar suspensión:
```bash
GET /api/users/550e8400-e29b-41d4-a716-446655440000
Authorization: Bearer <token>
```

---

### Caso 2: Crear Empresa y Asignar Administrador

**Escenario:** Necesitas crear una nueva empresa "TechCorp" con un administrador específico.

**Pasos:**

1. Verificar que el usuario administrador existe:
```bash
GET /api/users?search=admin@techcorp.com
Authorization: Bearer <token>
```

2. Obtener el ID de la industria:
```bash
GET /api/company-industries?search=tecnología
Authorization: Bearer <token>
```

3. Crear la empresa:
```bash
POST /api/companies
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "TechCorp Solutions",
  "legal_name": "TechCorp Solutions S.A.",
  "description": "Empresa de soluciones tecnológicas",
  "industry_id": "7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f",
  "admin_user_id": "1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d",
  "support_email": "support@techcorp.com",
  "phone": "+56912345678",
  "website": "https://techcorp.com"
}
```

4. Verificar que el rol COMPANY_ADMIN fue asignado automáticamente:
```bash
GET /api/users/1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d
Authorization: Bearer <token>
```

---

### Caso 3: Aprobar Solicitud de Empresa

**Escenario:** Una empresa ha enviado una solicitud y necesitas aprobarla.

**Pasos:**

1. Listar solicitudes pendientes:
```bash
GET /api/company-requests?status=PENDING
Authorization: Bearer <token>
```

2. Revisar detalles de la solicitud:
```bash
GET /api/company-requests
Authorization: Bearer <token>
```

Respuesta:
```json
{
  "data": [
    {
      "id": "9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a",
      "requestCode": "REQ-20251112-001",
      "companyName": "Innovatech",
      "adminEmail": "admin@innovatech.com",
      "status": "PENDING"
    }
  ]
}
```

3. Aprobar la solicitud:
```bash
POST /api/company-requests/9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a/approve
Authorization: Bearer <token>
Content-Type: application/json

{
  "notes": "Documentación verificada correctamente"
}
```

4. Verificar que la empresa fue creada:
```bash
GET /api/companies?search=Innovatech
Authorization: Bearer <token>
```

---

### Caso 4: Asignar Rol de Agente a Usuario

**Escenario:** Necesitas convertir a un usuario existente en agente de soporte para una empresa.

**Pasos:**

1. Buscar el usuario:
```bash
GET /api/users?search=john.doe@acme.com
Authorization: Bearer <token>
```

2. Obtener ID de la empresa:
```bash
GET /api/companies?search=Acme
Authorization: Bearer <token>
```

3. Asignar rol de AGENT:
```bash
POST /api/users/1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d/roles
Authorization: Bearer <token>
Content-Type: application/json

{
  "roleCode": "AGENT",
  "companyId": "8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f"
}
```

4. Verificar asignación:
```bash
GET /api/users/1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d
Authorization: Bearer <token>
```

---

### Caso 5: Rechazar Solicitud de Empresa

**Escenario:** Una solicitud de empresa no cumple con los requisitos.

**Pasos:**

1. Identificar la solicitud:
```bash
GET /api/company-requests?status=PENDING
Authorization: Bearer <token>
```

2. Rechazar con razón detallada:
```bash
POST /api/company-requests/9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a/reject
Authorization: Bearer <token>
Content-Type: application/json

{
  "reason": "La documentación proporcionada está incompleta. Falta: 1) Certificado de constitución vigente, 2) RUT actualizado, 3) Cédula del representante legal. Por favor complete estos documentos y envíe una nueva solicitud."
}
```

---

### Caso 6: Auditoría - Listar Usuarios Activos Recientemente

**Escenario:** Necesitas generar un reporte de usuarios activos en los últimos 7 días.

**Pasos:**

1. Consultar usuarios con actividad reciente:
```bash
GET /api/users?recentActivity=true&status=active&per_page=50&order_by=last_activity_at&order_direction=desc
Authorization: Bearer <token>
```

2. Filtrar por empresa específica (opcional):
```bash
GET /api/users?recentActivity=true&companyId=8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f
Authorization: Bearer <token>
```

---

### Caso 7: Remover Rol de Agente

**Escenario:** Un agente dejó la empresa y necesitas remover su rol.

**Pasos:**

1. Buscar al usuario y obtener el ID del rol asignado:
```bash
GET /api/users/1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d
Authorization: Bearer <token>
```

Respuesta (extracto):
```json
{
  "roleContexts": [
    {
      "id": "7b5a4c3d-9e8f-1a2b-3c4d-5e6f7a8b9c0d",
      "roleCode": "AGENT",
      "company": { "name": "Acme Corporation" }
    }
  ]
}
```

2. Remover el rol usando el ID de la asignación:
```bash
DELETE /api/users/roles/7b5a4c3d-9e8f-1a2b-3c4d-5e6f7a8b9c0d?reason=Usuario%20dejó%20la%20empresa
Authorization: Bearer <token>
```

---

## Notas Finales

### Mejores Prácticas

1. **Siempre incluir el token JWT** en todas las requests
2. **Verificar el rol del usuario** antes de intentar operaciones administrativas
3. **Proporcionar razones descriptivas** al suspender usuarios o rechazar solicitudes
4. **Respetar los rate limits** para evitar bloqueos temporales
5. **Manejar errores 401/403** refrescando tokens o verificando permisos

### Seguridad

- Todos los endpoints requieren autenticación JWT
- Los tokens expiran después de un período determinado
- Las operaciones están auditadas con `assigned_by`, `reviewer_user_id`, etc.
- Los soft deletes mantienen registros históricos

### Performance

- Usa paginación para listados grandes (`per_page`, `page`)
- Filtra resultados para reducir payload (`status`, `role`, `search`)
- Los endpoints usan eager loading para evitar N+1 queries

---

**Documento generado:** 2025-11-12
**Versión API:** v1
**Mantenido por:** Equipo de Desarrollo Helpdesk
