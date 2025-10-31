# 📘 COMPANY MANAGEMENT REST API V11.0 - DOCUMENTACIÓN COMPLETA

> Sistema Helpdesk Multi-Tenant
> Feature: Company Management
> API Type: REST (Migración Completada desde GraphQL)
> Schema Version: 11.0 (API REST con JWT Puro Stateless)
> Última actualización: 31 Octubre 2025

---

## 📑 TABLA DE CONTENIDOS

1. [Introducción](#introducción)
2. [Arquitectura](#arquitectura)
3. [Autenticación](#autenticación)
4. [Public Endpoints](#public-endpoints)
5. [Company Endpoints](#company-endpoints)
6. [Company Request Endpoints](#company-request-endpoints)
7. [Data Models](#data-models)
8. [Error Handling](#error-handling)
9. [Rate Limiting](#rate-limiting)
10. [Casos de Uso](#casos-de-uso)

---

## 🎯 INTRODUCCIÓN

### Propósito del Feature

El **Company Management Feature** gestiona empresas en el sistema Helpdesk multi-tenant mediante una REST API, permitiendo:

- Creación, lectura y actualización de empresas
- Sistema de solicitudes públicas para nuevas empresas
- Sistema de seguimiento de empresas por usuarios
- Gestión de permisos contextuales por empresa
- Notificaciones por email profesionales

### Características Principales

✅ **JWT Puro Stateless**: Sin sesiones Laravel, autenticación vía tokens JWT
✅ **Multi-tenant**: Aislamiento completo entre empresas
✅ **REST API**: Endpoints RESTful estándar
✅ **Rate Limiting**: Protección contra abuso
✅ **Autorización Centralizada**: Validación en FormRequest + Policy
✅ **Emails Profesionales**: Event → Listener → Job → Queue
✅ **Contraseñas Temporales**: Generación automática (16 chars, 7 días)
✅ **Error Handling**: DEV vs PROD diferenciado

---

## 🏗️ ARQUITECTURA

### Filosofía del Diseño REST

A diferencia de GraphQL que usa un único endpoint, la REST API implementa endpoints específicos por recurso:

#### Organización de Endpoints

```
/api/companies                  # Company management
  ├── GET /                     # List (admin only)
  ├── GET /minimal             # Selector data (public)
  ├── GET /explore             # Browse companies (authenticated)
  ├── POST /                    # Create (admin only)
  ├── GET /{id}                # View details
  └── PATCH /{id}              # Update (owner/admin only)

/api/companies/{id}/follow      # Company followers
  ├── POST /                    # Follow company
  ├── DELETE /                  # Unfollow company
  └── GET /is-following        # Check status

/api/company-requests           # Company creation requests
  ├── GET /                     # List (admin only)
  ├── POST /                    # Create request (public, throttled)
  ├── POST /{id}/approve       # Approve (admin only)
  └── POST /{id}/reject        # Reject (admin only)
```

### JWT Puro Stateless

Todos los endpoints autenticados requieren:

```http
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Nota**: NO se usan sesiones Laravel. El usuario se determina del JWT token únicamente.

---

## 🔐 AUTENTICACIÓN

### JWT Token Structure

El token JWT contiene:
- `sub`: User ID
- `email`: User email
- `iat`: Issued at
- `exp`: Expiration time (15-60 minutos)

### Middleware de Autenticación

| Middleware | Descripción |
|-----------|-----------|
| `jwt.require` | Requiere JWT válido, lanza 401 si falta o inválido |
| `role:ROLE1,ROLE2` | Verifica que usuario tenga al menos uno de los roles |
| `company:owner` | Verifica que sea propietario de la empresa |

### Cómo Obtener Token

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "SecurePass123!"
}
```

Respuesta:

```json
{
  "success": true,
  "accessToken": "eyJhbGc...",
  "refreshToken": "refresh_token_value",
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "profile": {
      "firstName": "Juan",
      "lastName": "Pérez"
    },
    "roleContexts": [
      {
        "roleId": "uuid",
        "roleCode": "COMPANY_ADMIN",
        "companyId": "company-uuid",
        "companyName": "Acme Corp"
      }
    ]
  }
}
```

---

## 🌐 PUBLIC ENDPOINTS

Estos endpoints NO requieren autenticación.

### 1. Get Minimal Companies (Para Selectores)

```http
GET /api/companies/minimal
```

**Descripción**: Lista simplificada de empresas para selectores/dropdowns.

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Acme Corporation",
      "logo": "https://cdn.example.com/acme-logo.png"
    },
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Tech Solutions Inc",
      "logo": "https://cdn.example.com/tech-logo.png"
    }
  ]
}
```

**Query Parameters**:

| Parámetro | Tipo | Descripción |
|-----------|------|-----------|
| `search` | string | Filtrar por nombre (opcional) |
| `limit` | integer | Máx 50, default 20 |
| `page` | integer | Paginación (default 1) |

---

### 2. Create Company Request (Solicitud Pública)

```http
POST /api/company-requests
Content-Type: application/json
```

**Descripción**: Permite a usuarios públicos solicitar la creación de una nueva empresa.

**Body**:

```json
{
  "companyName": "Nueva Empresa SA",
  "legalName": "Nueva Empresa Sociedad Anónima",
  "email": "admin@nuevaempresa.com",
  "phone": "+34 912 345 678",
  "website": "https://www.nuevaempresa.com",
  "contactInfo": {
    "address": "Calle Principal 123",
    "city": "Madrid",
    "state": "Madrid",
    "country": "España",
    "postalCode": "28001",
    "taxId": "A12345678",
    "legalRepresentative": "Juan García López"
  }
}
```

**Validación**:

| Campo | Validación |
|-------|-----------|
| `companyName` | Requerido, 2-200 caracteres |
| `legalName` | Opcional, 2-200 caracteres |
| `email` | Requerido, email válido |
| `phone` | Opcional, máx 20 caracteres |
| `website` | Opcional, URL válida |
| `contactInfo.*` | Todos opcionales, máx de caracteres especificados |

**Respuesta Exitosa (201)**:

```json
{
  "success": true,
  "message": "Solicitud creada exitosamente",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440010",
    "companyName": "Nueva Empresa SA",
    "email": "admin@nuevaempresa.com",
    "status": "PENDING",
    "createdAt": "2025-10-31T14:30:00Z"
  }
}
```

**Rate Limiting**: 3 solicitudes por hora (por IP)

**Errores**:

| Código | Status | Mensaje |
|--------|--------|---------|
| `VALIDATION_ERROR` | 422 | Errores de validación específicos |
| `THROTTLE_EXCEEDED` | 429 | Demasiadas solicitudes |

---

## 🏢 COMPANY ENDPOINTS

### Autenticación Requerida

Todos estos endpoints requieren `Authorization: Bearer <token>`

---

### 1. Get Companies (Admin Only)

```http
GET /api/companies
Authorization: Bearer <token>
```

**Descripción**: Lista todas las empresas del sistema (PLATFORM_ADMIN) o del usuario (COMPANY_ADMIN).

**Autorización**:
- `PLATFORM_ADMIN`: Ve todas las empresas
- `COMPANY_ADMIN`: Ve solo sus propias empresas

**Query Parameters**:

| Parámetro | Tipo | Descripción |
|-----------|------|-----------|
| `search` | string | Filtrar por nombre/email |
| `status` | enum | ACTIVE, INACTIVE |
| `sort` | string | Campos: name, createdAt |
| `order` | string | asc, desc |
| `page` | integer | Paginación (default 1) |
| `limit` | integer | Por página (default 20, máx 50) |

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Acme Corporation",
      "legalName": "Acme Corp SA",
      "status": "ACTIVE",
      "adminId": "user-uuid",
      "adminName": "Juan García",
      "adminEmail": "juan@acme.com",
      "adminAvatar": "https://cdn.example.com/avatar.jpg",
      "supportEmail": "support@acme.com",
      "phone": "+34 912 345 678",
      "website": "https://www.acme.com",
      "followersCount": 45,
      "activeAgentsCount": 12,
      "createdAt": "2025-10-01T10:00:00Z",
      "updatedAt": "2025-10-31T14:30:00Z"
    }
  ],
  "meta": {
    "total": 150,
    "perPage": 20,
    "currentPage": 1,
    "lastPage": 8
  }
}
```

**Errores**:

| Código | Status | Mensaje |
|--------|--------|---------|
| `UNAUTHORIZED` | 401 | No está autenticado |
| `FORBIDDEN` | 403 | No tiene permiso de lectura |

---

### 2. Get Explore Companies

```http
GET /api/companies/explore
Authorization: Bearer <token>
```

**Descripción**: Explora empresas disponibles (para seguir, ver detalles, etc).

**Query Parameters**:

| Parámetro | Tipo | Descripción |
|-----------|------|-----------|
| `search` | string | Filtrar por nombre |
| `sort` | string | name, followers, recent |
| `page` | integer | Paginación |

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Acme Corporation",
      "description": "Leading provider of innovative solutions",
      "logo": "https://cdn.example.com/acme-logo.png",
      "website": "https://www.acme.com",
      "followersCount": 45,
      "activeAgentsCount": 12,
      "isFollowing": false
    }
  ],
  "meta": {
    "total": 245,
    "perPage": 20,
    "currentPage": 1
  }
}
```

---

### 3. Get Company Details

```http
GET /api/companies/{companyId}
Authorization: Bearer <token>
```

**Parámetros**:

| Parámetro | Tipo | Descripción |
|-----------|------|-----------|
| `companyId` | UUID | ID de la empresa |

**Descripción**: Obtiene detalles completos de una empresa.

**Autorización**:
- Usuarios autenticados pueden ver empresas públicas
- Admins pueden ver detalles adicionales
- PLATFORM_ADMIN ve todo

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Acme Corporation",
    "legalName": "Acme Corp SA",
    "status": "ACTIVE",
    "adminId": "user-uuid",
    "adminName": "Juan García",
    "adminEmail": "juan@acme.com",
    "supportEmail": "support@acme.com",
    "phone": "+34 912 345 678",
    "website": "https://www.acme.com",
    "followersCount": 45,
    "activeAgentsCount": 12,
    "contactInfo": {
      "address": "Calle Principal 123",
      "city": "Madrid",
      "state": "Madrid",
      "country": "España",
      "postalCode": "28001",
      "taxId": "A12345678",
      "legalRepresentative": "Juan García López"
    },
    "config": {
      "timezone": "Europe/Madrid",
      "businessHours": {
        "monday": {"open": "09:00", "close": "18:00"},
        "tuesday": {"open": "09:00", "close": "18:00"}
      },
      "maxAgents": 50,
      "maxTicketsPerMonth": 5000
    },
    "branding": {
      "logoUrl": "https://cdn.example.com/acme-logo.png",
      "faviconUrl": "https://cdn.example.com/favicon.ico",
      "primaryColor": "#FF5733",
      "secondaryColor": "#33C3F0"
    },
    "createdAt": "2025-10-01T10:00:00Z",
    "updatedAt": "2025-10-31T14:30:00Z"
  }
}
```

**Errores**:

| Código | Status | Mensaje |
|--------|--------|---------|
| `COMPANY_NOT_FOUND` | 404 | La empresa no existe |
| `UNAUTHORIZED` | 401 | No está autenticado |
| `FORBIDDEN` | 403 | No tiene permiso de acceso |

---

### 4. Create Company (Admin Only)

```http
POST /api/companies
Authorization: Bearer <token>
Content-Type: application/json
```

**Autorización**: Requiere `PLATFORM_ADMIN`

**Body**:

```json
{
  "name": "Nueva Empresa Inc",
  "legalName": "Nueva Empresa Inc SA",
  "supportEmail": "support@nuevaempresa.com",
  "phone": "+34 912 345 678",
  "website": "https://www.nuevaempresa.com",
  "adminUserId": "550e8400-e29b-41d4-a716-446655440099",
  "contactInfo": {
    "address": "Calle Principal 123",
    "city": "Madrid",
    "state": "Madrid",
    "country": "España",
    "postalCode": "28001",
    "taxId": "A12345678",
    "legalRepresentative": "Juan García López"
  },
  "config": {
    "timezone": "Europe/Madrid",
    "maxAgents": 50,
    "maxTicketsPerMonth": 5000
  },
  "branding": {
    "primaryColor": "#FF5733",
    "secondaryColor": "#33C3F0"
  }
}
```

**Respuesta Exitosa (201)**:

```json
{
  "success": true,
  "message": "Empresa creada exitosamente",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440100",
    "name": "Nueva Empresa Inc",
    "companyCode": "NEI-2025-10-31-A3K2",
    "status": "ACTIVE",
    "createdAt": "2025-10-31T14:30:00Z"
  }
}
```

**Validación**: Similar a Update Company

**Rate Limiting**: 10 solicitudes por hora

**Errores**:

| Código | Status | Mensaje |
|--------|--------|---------|
| `VALIDATION_ERROR` | 422 | Errores de validación |
| `ADMIN_USER_NOT_FOUND` | 404 | El usuario admin no existe |
| `UNAUTHORIZED` | 401 | No está autenticado |
| `FORBIDDEN` | 403 | No es PLATFORM_ADMIN |

---

### 5. Update Company

```http
PATCH /api/companies/{companyId}
Authorization: Bearer <token>
Content-Type: application/json
```

**Autorización**:
- `PLATFORM_ADMIN`: Puede actualizar cualquier empresa
- `COMPANY_ADMIN`: Solo puede actualizar su propia empresa

**Parámetros**:

| Parámetro | Tipo | Descripción |
|-----------|------|-----------|
| `companyId` | UUID | ID de la empresa a actualizar |

**Body** (todos los campos opcionales):

```json
{
  "name": "Acme Corporation Updated",
  "legal_name": "Acme Corp SA Updated",
  "support_email": "support@acme.com",
  "phone": "+34 912 345 678",
  "website": "https://www.acme.com",
  "contact_info": {
    "address": "New Address 456",
    "city": "Barcelona",
    "state": "Barcelona",
    "country": "España",
    "postal_code": "08002",
    "tax_id": "A12345678",
    "legal_representative": "María López García"
  },
  "config": {
    "timezone": "Europe/Madrid",
    "business_hours": {
      "monday": {"open": "09:00", "close": "18:00"}
    },
    "max_agents": 75,
    "max_tickets_per_month": 7500
  },
  "branding": {
    "logo_url": "https://cdn.example.com/new-logo.png",
    "primary_color": "#FF5733",
    "secondary_color": "#33C3F0"
  }
}
```

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "message": "Empresa actualizada exitosamente",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Acme Corporation Updated",
    "status": "ACTIVE",
    "updatedAt": "2025-10-31T15:45:00Z"
  }
}
```

**Validación**:

| Campo | Validación |
|-------|-----------|
| `name` | Opcional, 2-200 caracteres |
| `legal_name` | Opcional, 2-200 caracteres |
| `support_email` | Opcional, email válido |
| `phone` | Opcional, máx 20 caracteres |
| `website` | Opcional, URL válida |
| `contact_info.tax_id` | Opcional, máx 50 caracteres |
| `config.timezone` | Opcional, timezone válido |
| `config.max_agents` | Opcional, integer 1-1000 |
| `branding.primary_color` | Opcional, hex color válido |

**Errores**:

| Código | Status | Mensaje |
|--------|--------|---------|
| `VALIDATION_ERROR` | 422 | Errores de validación |
| `COMPANY_NOT_FOUND` | 404 | La empresa no existe |
| `UNAUTHORIZED` | 401 | No está autenticado |
| `FORBIDDEN` | 403 | No es propietario ni admin de plataforma |

---

## 👥 COMPANY FOLLOWER ENDPOINTS

### 1. Get Followed Companies

```http
GET /api/companies/followed
Authorization: Bearer <token>
```

**Descripción**: Obtiene todas las empresas que el usuario está siguiendo.

**Query Parameters**:

| Parámetro | Tipo | Descripción |
|-----------|------|-----------|
| `page` | integer | Paginación |
| `limit` | integer | Por página (default 20) |

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Acme Corporation",
      "logo": "https://cdn.example.com/acme-logo.png",
      "followersCount": 45
    }
  ],
  "meta": {
    "total": 12,
    "perPage": 20,
    "currentPage": 1
  }
}
```

---

### 2. Check if Following Company

```http
GET /api/companies/{companyId}/is-following
Authorization: Bearer <token>
```

**Descripción**: Verifica si el usuario actual está siguiendo una empresa.

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "data": {
    "isFollowing": true
  }
}
```

---

### 3. Follow Company

```http
POST /api/companies/{companyId}/follow
Authorization: Bearer <token>
```

**Descripción**: El usuario comienza a seguir una empresa.

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "message": "Ya estás siguiendo esta empresa",
  "data": {
    "isFollowing": true,
    "followersCount": 46
  }
}
```

**Rate Limiting**: 20 solicitudes por hora

**Errores**:

| Código | Status | Mensaje |
|--------|--------|---------|
| `COMPANY_NOT_FOUND` | 404 | La empresa no existe |
| `ALREADY_FOLLOWING` | 409 | Ya estás siguiendo esta empresa |
| `THROTTLE_EXCEEDED` | 429 | Demasiadas solicitudes |

---

### 4. Unfollow Company

```http
DELETE /api/companies/{companyId}/unfollow
Authorization: Bearer <token>
```

**Descripción**: El usuario deja de seguir una empresa.

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "message": "Ya no estás siguiendo esta empresa",
  "data": {
    "isFollowing": false,
    "followersCount": 44
  }
}
```

**Errores**:

| Código | Status | Mensaje |
|--------|--------|---------|
| `COMPANY_NOT_FOUND` | 404 | La empresa no existe |
| `NOT_FOLLOWING` | 409 | No estás siguiendo esta empresa |

---

## 📋 COMPANY REQUEST ENDPOINTS

### 1. List Company Requests (Admin Only)

```http
GET /api/company-requests
Authorization: Bearer <token>
```

**Autorización**: Requiere `PLATFORM_ADMIN`

**Query Parameters**:

| Parámetro | Tipo | Descripción |
|-----------|------|-----------|
| `status` | enum | PENDING, APPROVED, REJECTED |
| `search` | string | Filtrar por nombre/email |
| `sort` | string | createdAt, name |
| `order` | string | asc, desc |
| `page` | integer | Paginación |

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440010",
      "companyName": "Nueva Empresa SA",
      "email": "admin@nuevaempresa.com",
      "phone": "+34 912 345 678",
      "website": "https://www.nuevaempresa.com",
      "status": "PENDING",
      "createdAt": "2025-10-31T14:30:00Z"
    }
  ],
  "meta": {
    "total": 25,
    "perPage": 20,
    "currentPage": 1
  }
}
```

---

### 2. Approve Company Request

```http
POST /api/company-requests/{requestId}/approve
Authorization: Bearer <token>
Content-Type: application/json
```

**Autorización**: Requiere `PLATFORM_ADMIN`

**Body** (opcional - agregar información adicional):

```json
{
  "notes": "Solicitud aprobada por análisis de negocio"
}
```

**Descripción**: Aprueba una solicitud de empresa, crea la empresa y envía email al solicitante con contraseña temporal.

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "message": "Solicitud aprobada exitosamente",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440010",
    "status": "APPROVED",
    "company": {
      "id": "550e8400-e29b-41d4-a716-446655440100",
      "name": "Nueva Empresa SA",
      "companyCode": "NES-2025-10-31-X2Y3"
    },
    "adminUser": {
      "id": "550e8400-e29b-41d4-a716-446655440099",
      "email": "admin@nuevaempresa.com",
      "temporaryPassword": "Auto-Generated-16Chars"
    },
    "approvedAt": "2025-10-31T15:45:00Z"
  }
}
```

**Proceso del Approve**:

1. Crea empresa con datos de solicitud
2. Crea usuario admin con email de solicitud
3. Genera contraseña temporal (16 caracteres)
4. Asigna rol COMPANY_ADMIN al usuario
5. Envía email con credenciales (async via queue)
6. Marca solicitud como APPROVED

**Emails Enviados**:

- A `email` de la solicitud:
  - Asunto: "¡Bienvenido a {companyName}!"
  - Contiene: Contraseña temporal, link para cambiar contraseña
  - Contraseña válida por 7 días

**Errores**:

| Código | Status | Mensaje |
|--------|--------|---------|
| `REQUEST_NOT_FOUND` | 404 | La solicitud no existe |
| `ALREADY_PROCESSED` | 409 | La solicitud ya fue procesada |
| `VALIDATION_ERROR` | 422 | Errores en datos de solicitud |
| `UNAUTHORIZED` | 401 | No está autenticado |
| `FORBIDDEN` | 403 | No es PLATFORM_ADMIN |

---

### 3. Reject Company Request

```http
POST /api/company-requests/{requestId}/reject
Authorization: Bearer <token>
Content-Type: application/json
```

**Autorización**: Requiere `PLATFORM_ADMIN`

**Body**:

```json
{
  "reason": "No cumple requisitos de negocio",
  "notes": "Contactar para aclaraciones"
}
```

**Descripción**: Rechaza una solicitud de empresa y notifica al solicitante.

**Respuesta Exitosa (200)**:

```json
{
  "success": true,
  "message": "Solicitud rechazada exitosamente",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440010",
    "status": "REJECTED",
    "rejectedAt": "2025-10-31T15:50:00Z",
    "reason": "No cumple requisitos de negocio"
  }
}
```

**Email Enviado**:

- A `email` de la solicitud:
  - Asunto: "Actualización sobre tu solicitud"
  - Contiene: Motivo del rechazo, contacto para más información

**Errores**:

| Código | Status | Mensaje |
|--------|--------|---------|
| `REQUEST_NOT_FOUND` | 404 | La solicitud no existe |
| `ALREADY_PROCESSED` | 409 | La solicitud ya fue procesada |
| `VALIDATION_ERROR` | 422 | Errores de validación |
| `UNAUTHORIZED` | 401 | No está autenticado |
| `FORBIDDEN` | 403 | No es PLATFORM_ADMIN |

---

## 📊 DATA MODELS

### Company Object

```json
{
  "id": "UUID",
  "companyCode": "string",
  "name": "string",
  "legalName": "string",
  "status": "ACTIVE|INACTIVE",
  "adminId": "UUID",
  "adminName": "string",
  "adminEmail": "string",
  "adminAvatar": "URL|null",
  "supportEmail": "string|null",
  "phone": "string|null",
  "website": "URL|null",
  "followersCount": "integer",
  "activeAgentsCount": "integer",
  "contactInfo": {
    "address": "string|null",
    "city": "string|null",
    "state": "string|null",
    "country": "string|null",
    "postalCode": "string|null",
    "taxId": "string|null",
    "legalRepresentative": "string|null"
  },
  "config": {
    "timezone": "string|null",
    "businessHours": "object|null",
    "settings": "object|null",
    "maxAgents": "integer|null",
    "maxTicketsPerMonth": "integer|null"
  },
  "branding": {
    "logoUrl": "URL|null",
    "faviconUrl": "URL|null",
    "primaryColor": "hex|null",
    "secondaryColor": "hex|null"
  },
  "createdAt": "DateTime ISO8601",
  "updatedAt": "DateTime ISO8601"
}
```

### CompanyRequest Object

```json
{
  "id": "UUID",
  "companyName": "string",
  "legalName": "string|null",
  "email": "string",
  "phone": "string|null",
  "website": "URL|null",
  "contactInfo": {
    "address": "string|null",
    "city": "string|null",
    "state": "string|null",
    "country": "string|null",
    "postalCode": "string|null",
    "taxId": "string|null",
    "legalRepresentative": "string|null"
  },
  "status": "PENDING|APPROVED|REJECTED",
  "createdAt": "DateTime ISO8601",
  "approvedAt": "DateTime ISO8601|null",
  "rejectedAt": "DateTime ISO8601|null"
}
```

---

## ⚠️ ERROR HANDLING

### Error Response Format

Todos los errores siguen este formato:

```json
{
  "success": false,
  "message": "Descripción del error",
  "code": "ERROR_CODE",
  "category": "validation|authentication|authorization|resource|server",
  "timestamp": "2025-10-31T15:50:00Z",
  "extensions": {
    "fieldErrors": {
      "name": ["El nombre es requerido"],
      "email": ["Email debe ser válido"]
    }
  }
}
```

### Common Error Codes

| Código | Status | Descripción |
|--------|--------|-----------|
| `VALIDATION_ERROR` | 422 | Error de validación en datos |
| `UNAUTHORIZED` | 401 | Falta autenticación JWT |
| `FORBIDDEN` | 403 | Sin permisos para esta acción |
| `NOT_FOUND` | 404 | Recurso no encontrado |
| `CONFLICT` | 409 | Conflicto (e.g., ya existe) |
| `THROTTLE_EXCEEDED` | 429 | Rate limit excedido |
| `INTERNAL_ERROR` | 500 | Error del servidor |

### Development vs Production

**Desarrollo** (app.debug=true):
- Stacktrace completo
- SQL queries
- Paths locales
- Headers HTTP

**Producción** (app.debug=false):
- Mensaje genérico
- Code y category solamente
- Sin información sensible
- Log interno para debugging

---

## 🚀 RATE LIMITING

| Endpoint | Límite | Ventana | Notas |
|----------|--------|---------|-------|
| `POST /company-requests` | 3 | 60 min | Por IP |
| `POST /companies` | 10 | 60 min | Por usuario autenticado |
| `POST /companies/{id}/follow` | 20 | 60 min | Por usuario |
| `PATCH /companies/{id}` | 30 | 60 min | Por usuario |

**Cuando se excede**:

```json
{
  "success": false,
  "message": "Demasiadas solicitudes",
  "code": "THROTTLE_EXCEEDED",
  "category": "rate_limit",
  "extensions": {
    "retryAfter": 3600
  }
}
```

**Headers Devueltos**:

```http
HTTP/1.1 429 Too Many Requests
RateLimit-Limit: 10
RateLimit-Remaining: 0
RateLimit-Reset: 1635696600
Retry-After: 3600
```

---

## 📚 CASOS DE USO

### Caso 1: Usuario Registra Nueva Empresa

```bash
# 1. Público crea solicitud
POST /api/company-requests
{
  "companyName": "Acme Inc",
  "email": "admin@acme.com"
}

# 2. Respuesta (throttled a 3/hora)
201 Created
```

### Caso 2: Admin Aprueba Solicitud

```bash
# 1. Admin obtiene solicitudes pendientes
GET /api/company-requests?status=PENDING
Authorization: Bearer <token-admin>

# 2. Admin aprueba
POST /api/company-requests/550e8400.../approve
Authorization: Bearer <token-admin>

# 3. Sistema:
#    - Crea empresa
#    - Crea usuario admin
#    - Genera contraseña temporal
#    - Envía email async (queue)
```

### Caso 3: Usuario Explora y Sigue Empresas

```bash
# 1. Usuario ve empresas
GET /api/companies/explore
Authorization: Bearer <token>

# 2. Usuario sigue una
POST /api/companies/550e8400.../follow
Authorization: Bearer <token>

# 3. Usuario ve sus empresas seguidas
GET /api/companies/followed
Authorization: Bearer <token>
```

### Caso 4: Admin Actualiza Empresa

```bash
# 1. Solo COMPANY_ADMIN o PLATFORM_ADMIN puede actualizar
PATCH /api/companies/550e8400...
Authorization: Bearer <token-admin>
Content-Type: application/json
{
  "name": "Acme Corporation Updated",
  "supportEmail": "newsupport@acme.com"
}

# 2. Respuesta
200 OK
{
  "success": true,
  "data": {...}
}
```

---

## 🔗 Referencias

- [Database Schema](./Modelado%20final%20de%20base%20de%20datos.txt)
- [Error System](./SISTEMA_ERRORES_GRAPHQL_IMPLEMENTADO.md)
- [REST API Quick Guide](./REST_API_AUTHENTICATION_GUIA_RAPIDA.md)
- [Authentication Feature](./AUTHENTICATION%20FEATURE%20-%20DOCUMENTACIÓN.txt)
- [User Management Feature](./USER%20MANAGMENT%20FEATURE%20-%20DOCUMENTACION.txt)

---

**Última Actualización**: 31 Octubre 2025
**Estado**: ✅ Production Ready
**Version**: 11.0
