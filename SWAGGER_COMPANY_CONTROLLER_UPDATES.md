# Actualización Completa de Documentación Swagger - CompanyController

**Fecha**: 2025-11-01
**Feature**: CompanyManagement
**Controlador**: `app/Features/CompanyManagement/Http/Controllers/CompanyController.php`

## Resumen Ejecutivo

Este documento detalla **TODOS los cambios necesarios** para actualizar la documentación Swagger del CompanyController para que sea 100% fiel a la implementación real.

**Problema principal detectado**: La documentación Swagger actual usa `snake_case` pero los Resources retornan `camelCase`.

## Análisis de Resources (Implementación Real)

### 1. CompanyMinimalResource (4 campos)

**Archivo**: `app/Features/CompanyManagement/Http/Resources/CompanyMinimalResource.php`

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "companyCode": "CMP-2025-00001",
  "name": "Acme Corporation",
  "logoUrl": "https://example.com/logo.png"
}
```

**Campos (camelCase)**:
- `id` (string, uuid)
- `companyCode` (string)
- `name` (string)
- `logoUrl` (string|null)

---

### 2. CompanyExploreResource (12 campos)

**Archivo**: `app/Features/CompanyManagement/Http/Resources/CompanyExploreResource.php`

```json
{
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "companyCode": "CMP-2025-00001",
  "name": "Acme Corporation",
  "logoUrl": "https://example.com/logo.png",
  "description": "Leading technology company specializing in enterprise software solutions and cloud infrastructure...",
  "industry": {
    "id": "123e4567-e89b-12d3-a456-426614174000",
    "code": "TECH",
    "name": "Technology"
  },
  "city": "Santiago",
  "country": "Chile",
  "primaryColor": "#FF5733",
  "status": "ACTIVE",
  "followersCount": 42,
  "isFollowedByMe": false
}
```

**Campos (camelCase)**:
- `id` (string, uuid)
- `companyCode` (string)
- `name` (string)
- `logoUrl` (string|null)
- `description` (string|null) - **Truncado a 120 caracteres**
- `industry` (object|null)
  - `id` (string, uuid)
  - `code` (string)
  - `name` (string)
- `city` (string|null) - Viene de `contact_city`
- `country` (string|null) - Viene de `contact_country`
- `primaryColor` (string|null) - Hexadecimal `#RRGGBB`
- `status` (string) - **UPPERCASE**: "ACTIVE", "SUSPENDED", "INACTIVE"
- `followersCount` (integer) - Campo calculado
- `isFollowedByMe` (boolean) - Campo contextual (autenticación)

---

### 3. CompanyResource (40+ campos) - MANAGEMENT

**Archivo**: `app/Features/CompanyManagement/Http/Resources/CompanyResource.php`

```json
{
  // Basic fields
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "companyCode": "CMP-2025-00001",
  "name": "Acme Corporation",
  "legalName": "Acme Corporation S.A.",
  "description": "Leading technology company specializing in enterprise solutions",

  // Industry
  "industryId": "123e4567-e89b-12d3-a456-426614174000",
  "industry": {
    "id": "123e4567-e89b-12d3-a456-426614174000",
    "code": "TECH",
    "name": "Technology",
    "description": "Technology and software companies"
  },

  // Contact and communication
  "supportEmail": "support@acme.com",
  "phone": "+56912345678",
  "website": "https://acme.com",

  // Address
  "contactAddress": "Av. Providencia 1234",
  "contactCity": "Santiago",
  "contactState": "Metropolitana",
  "contactCountry": "Chile",
  "contactPostalCode": "7500000",

  // Legal information
  "taxId": "76.123.456-7",
  "legalRepresentative": "John Doe",

  // Configuration
  "businessHours": {
    "monday": {"open": "09:00", "close": "18:00"}
  },
  "timezone": "America/Santiago",

  // Branding
  "logoUrl": "https://example.com/logo.png",
  "faviconUrl": "https://example.com/favicon.ico",
  "primaryColor": "#FF5733",
  "secondaryColor": "#33FF57",

  // Settings and status
  "settings": {},
  "status": "ACTIVE",

  // Admin (flattened fields)
  "adminId": "123e4567-e89b-12d3-a456-426614174001",
  "adminName": "John Admin",
  "adminEmail": "admin@acme.com",
  "adminAvatar": "https://example.com/avatar.png",

  // Calculated statistics
  "activeAgentsCount": 5,
  "totalUsersCount": 25,
  "totalTicketsCount": 150,
  "openTicketsCount": 12,
  "followersCount": 42,

  // Contextual field (authenticated user)
  "isFollowedByMe": false,

  // Optional relations
  "createdFromRequestId": null,

  // Timestamps
  "createdAt": "2025-01-15T10:30:00Z",
  "updatedAt": "2025-01-20T14:45:00Z"
}
```

**Campos completos (40+ campos en camelCase)**:

#### Básicos (5)
- `id` (string, uuid)
- `companyCode` (string)
- `name` (string)
- `legalName` (string|null)
- `description` (string|null)

#### Industria (2)
- `industryId` (string, uuid|null)
- `industry` (object|null) con `id`, `code`, `name`, `description`

#### Contacto (3)
- `supportEmail` (string, email|null)
- `phone` (string|null)
- `website` (string, uri|null)

#### Dirección (5)
- `contactAddress` (string|null)
- `contactCity` (string|null)
- `contactState` (string|null)
- `contactCountry` (string|null)
- `contactPostalCode` (string|null)

#### Legal (2)
- `taxId` (string|null)
- `legalRepresentative` (string|null)

#### Configuración (2)
- `businessHours` (object|null)
- `timezone` (string, default "UTC")

#### Branding (4)
- `logoUrl` (string, uri|null)
- `faviconUrl` (string, uri|null)
- `primaryColor` (string, hex|null)
- `secondaryColor` (string, hex|null)

#### Estado y Settings (2)
- `settings` (object|null)
- `status` (string) - "ACTIVE", "SUSPENDED", "INACTIVE" (UPPERCASE)

#### Admin desestructurado (4)
- `adminId` (string, uuid)
- `adminName` (string, default "Unknown")
- `adminEmail` (string, email, default "unknown@example.com")
- `adminAvatar` (string, uri|null)

#### Estadísticas calculadas (5)
- `activeAgentsCount` (integer)
- `totalUsersCount` (integer)
- `totalTicketsCount` (integer)
- `openTicketsCount` (integer)
- `followersCount` (integer)

#### Campos contextuales (1)
- `isFollowedByMe` (boolean)

#### Relaciones opcionales (1)
- `createdFromRequestId` (string, uuid|null)

#### Timestamps (2)
- `createdAt` (string, date-time, ISO8601)
- `updatedAt` (string, date-time, ISO8601)

**Total: 43 campos**

---

## Request Bodies Analizados

### CreateCompanyRequest (POST /api/companies)

**Estructura aceptada**: NESTED + FLAT (se aplana en `prepareForValidation()`)

```json
{
  // Campos obligatorios
  "name": "Acme Corporation",
  "industry_id": "123e4567-e89b-12d3-a456-426614174000",
  "admin_user_id": "123e4567-e89b-12d3-a456-426614174001",

  // Campos opcionales básicos
  "legal_name": "Acme Corporation S.A.",
  "description": "Leading technology company",
  "support_email": "support@acme.com",
  "phone": "+56912345678",
  "website": "https://acme.com",

  // Estructura NESTED (opcional)
  "contact_info": {
    "address": "Av. Providencia 1234",
    "city": "Santiago",
    "state": "Metropolitana",
    "country": "Chile",
    "postal_code": "7500000",
    "tax_id": "76.123.456-7",
    "legal_representative": "John Doe"
  },

  // Configuración inicial NESTED (opcional)
  "initial_config": {
    "timezone": "America/Santiago",
    "max_agents": 50,
    "max_tickets_per_month": 1000
  }
}
```

**Notas importantes**:
- `prepareForValidation()` aplana los objetos nested a campos flat (`contact_address`, `contact_city`, etc.)
- Campos required: `name`, `industry_id`, `admin_user_id`
- Validación: `name` 2-200 chars, `legal_name` 2-200 chars
- El request acepta AMBAS estructuras (nested y flat)

---

### UpdateCompanyRequest (PATCH /api/companies/{company})

**Estructura aceptada**: NESTED + FLAT (se aplana en `prepareForValidation()`)

```json
{
  // Información básica (opcional)
  "name": "Acme Corporation",
  "legal_name": "Acme Corporation S.A.",
  "description": "Updated description",
  "industry_id": "123e4567-e89b-12d3-a456-426614174000",
  "support_email": "support@acme.com",
  "phone": "+56912345678",
  "website": "https://acme.com",

  // Contact info NESTED (opcional)
  "contact_info": {
    "address": "Av. Providencia 1234",
    "city": "Santiago",
    "state": "Metropolitana",
    "country": "Chile",
    "postal_code": "7500000",
    "tax_id": "76.123.456-7",
    "legal_representative": "John Doe"
  },

  // Config NESTED (opcional)
  "config": {
    "timezone": "America/Santiago",
    "business_hours": {},
    "settings": {},
    "max_agents": 50,
    "max_tickets_per_month": 1000
  },

  // Branding NESTED (opcional)
  "branding": {
    "logo_url": "https://example.com/logo.png",
    "favicon_url": "https://example.com/favicon.ico",
    "primary_color": "#FF5733",
    "secondary_color": "#33FF57"
  }
}
```

**Notas importantes**:
- TODOS los campos son opcionales (usa `sometimes` en validación)
- `prepareForValidation()` aplana 3 objetos nested: `contact_info`, `config`, `branding`
- Validación: colores hexadecimales (`/^#[0-9A-Fa-f]{6}$/`)
- Autorización: PLATFORM_ADMIN o COMPANY_ADMIN (owner)

---

## Cambios Necesarios por Endpoint

### 1. GET /api/companies/minimal

**Cambios en response schema**:
```php
// ANTES (snake_case)
new OA\Property(property: 'company_code', type: 'string')
new OA\Property(property: 'logo_url', type: 'string', nullable: true)

// DESPUÉS (camelCase)
new OA\Property(property: 'companyCode', type: 'string', example: 'CMP-2025-00001')
new OA\Property(property: 'logoUrl', type: 'string', nullable: true, example: 'https://example.com/logo.png')
```

---

### 2. GET /api/companies/explore

**Cambios en response schema**:
```php
// AGREGAR todos estos campos faltantes (camelCase):
new OA\Property(property: 'companyCode', type: 'string', example: 'CMP-2025-00001'),
new OA\Property(property: 'logoUrl', type: 'string', nullable: true),
new OA\Property(property: 'description', type: 'string', nullable: true, description: 'Truncated to 120 characters'),
new OA\Property(property: 'city', type: 'string', nullable: true, example: 'Santiago'),
new OA\Property(property: 'country', type: 'string', nullable: true, example: 'Chile'),
new OA\Property(property: 'primaryColor', type: 'string', nullable: true, pattern: '^#[0-9A-Fa-f]{6}$', example: '#FF5733'),
new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'SUSPENDED', 'INACTIVE'], example: 'ACTIVE'),
new OA\Property(property: 'followersCount', type: 'integer', example: 42),
new OA\Property(property: 'isFollowedByMe', type: 'boolean', example: false),

// QUITAR (campos que NO retorna):
// - website
// - contact_country
// - industry_id (solo retorna industry object)
```

---

### 3. GET /api/companies (index)

**Cambios en response schema**: Reemplazar TODOS los campos con los 43 campos de CompanyResource en camelCase.

```php
// Ejemplo de estructura correcta:
new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
new OA\Property(property: 'companyCode', type: 'string', example: 'CMP-2025-00001'),
new OA\Property(property: 'name', type: 'string', example: 'Acme Corporation'),
new OA\Property(property: 'legalName', type: 'string', nullable: true, example: 'Acme Corporation S.A.'),
new OA\Property(property: 'description', type: 'string', nullable: true),
// ... (ver lista completa de 43 campos arriba)
```

**Campos críticos a agregar**:
- Admin desestructurado: `adminId`, `adminName`, `adminEmail`, `adminAvatar`
- Estadísticas: `activeAgentsCount`, `totalUsersCount`, `totalTicketsCount`, `openTicketsCount`, `followersCount`
- Branding: `faviconUrl`, `primaryColor`, `secondaryColor`
- Configuración: `businessHours`, `timezone`, `settings`
- Timestamps: `createdAt`, `updatedAt` (ISO8601 format)

---

### 4. GET /api/companies/{company} (show)

**Cambios**: IDÉNTICOS a GET /api/companies (index), usa CompanyResource completo.

**Diferencia clave**: También retorna `isFollowedByMe` si el usuario está autenticado.

---

### 5. POST /api/companies (store)

**Cambios en requestBody**:
```php
// Actualizar required fields
required: ['name', 'industry_id', 'admin_user_id']  // NO 'legal_name' ni 'support_email'

// Actualizar límites de caracteres
new OA\Property(property: 'name', minLength: 2, maxLength: 200)  // NO 255
new OA\Property(property: 'legal_name', minLength: 2, maxLength: 200)  // NO 255

// AGREGAR campo description
new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 1000)

// AGREGAR industry_id (REQUIRED)
new OA\Property(property: 'industry_id', type: 'string', format: 'uuid', description: 'Industry UUID (required)')

// AGREGAR estructuras NESTED (ver ejemplo completo arriba)
new OA\Property(property: 'contact_info', type: 'object', nullable: true, ...)
new OA\Property(property: 'initial_config', type: 'object', nullable: true, ...)
```

**Cambios en response (201)**:
- Retorna CompanyResource completo (43 campos en camelCase)
- NO solo `id`, `company_code`, `name`, `status`, `admin`, `created_at`

---

### 6. PATCH /api/companies/{company} (update)

**Cambios en requestBody**:
```php
// TODOS los campos son opcionales (usar 'sometimes' en lugar de 'required')

// Actualizar límites de caracteres
new OA\Property(property: 'name', minLength: 2, maxLength: 200)  // NO 255
new OA\Property(property: 'legal_name', minLength: 2, maxLength: 200)  // NO 255

// AGREGAR description
new OA\Property(property: 'description', type: 'string', nullable: true, maxLength: 1000)

// AGREGAR estructuras NESTED completas
new OA\Property(property: 'contact_info', type: 'object', nullable: true, ...)
new OA\Property(property: 'config', type: 'object', nullable: true, ...)
new OA\Property(property: 'branding', type: 'object', nullable: true, ...)
```

**Cambios en response (200)**:
- Retorna CompanyResource completo (43 campos en camelCase)
- Incluye TODOS los campos calculados

---

## Tabla Comparativa: snake_case vs camelCase

| snake_case (ANTES - Incorrecto) | camelCase (DESPUÉS - Correcto) |
|--------------------------------|-------------------------------|
| `company_code` | `companyCode` |
| `logo_url` | `logoUrl` |
| `legal_name` | `legalName` |
| `support_email` | `supportEmail` |
| `industry_id` | `industryId` |
| `contact_address` | `contactAddress` |
| `contact_city` | `contactCity` |
| `contact_state` | `contactState` |
| `contact_country` | `contactCountry` |
| `contact_postal_code` | `contactPostalCode` |
| `tax_id` | `taxId` |
| `legal_representative` | `legalRepresentative` |
| `business_hours` | `businessHours` |
| `logo_url` | `logoUrl` |
| `favicon_url` | `faviconUrl` |
| `primary_color` | `primaryColor` |
| `secondary_color` | `secondaryColor` |
| `admin_user_id` (en response) | `adminId` |
| `admin_name` | `adminName` |
| `admin_email` | `adminEmail` |
| `admin_avatar` | `adminAvatar` |
| `active_agents_count` | `activeAgentsCount` |
| `total_users_count` | `totalUsersCount` |
| `total_tickets_count` | `totalTicketsCount` |
| `open_tickets_count` | `openTicketsCount` |
| `followers_count` | `followersCount` |
| `is_followed_by_me` | `isFollowedByMe` |
| `created_from_request_id` | `createdFromRequestId` |
| `created_at` | `createdAt` |
| `updated_at` | `updatedAt` |

---

## Campos Calculados (No existen en base de datos)

Estos campos se calculan en memoria en el controlador y DEBEN aparecer en Swagger:

1. `followersCount` (integer) - Query: `CompanyFollower::where('company_id', $company->id)->count()`
2. `activeAgentsCount` (integer) - Filtra `userRoles` con `role_code = 'AGENT'` y `is_active = true`
3. `totalUsersCount` (integer) - Cuenta `user_id` únicos con `is_active = true`
4. `totalTicketsCount` (integer) - Default: 0 (aún no implementado)
5. `openTicketsCount` (integer) - Default: 0 (aún no implementado)
6. `isFollowedByMe` (boolean) - Verifica si existe en `CompanyFollower` para el usuario autenticado

---

## Status Enum Values

**IMPORTANTE**: El Resource convierte status a UPPERCASE.

```php
// En el modelo/BD (lowercase)
'active', 'suspended', 'inactive'

// En el Resource (UPPERCASE)
'ACTIVE', 'SUSPENDED', 'INACTIVE'
```

**Swagger debe documentar**: `enum: ['ACTIVE', 'SUSPENDED', 'INACTIVE']`

---

## Ejemplos de Respuesta REALES

### Ejemplo CompanyMinimalResource
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "companyCode": "CMP-2025-00001",
      "name": "Acme Corporation",
      "logoUrl": "https://example.com/logo.png"
    }
  ],
  "meta": {
    "total": 150,
    "current_page": 1,
    "last_page": 3,
    "per_page": 50
  },
  "links": {
    "first": "http://localhost:8000/api/companies/minimal?page=1",
    "last": "http://localhost:8000/api/companies/minimal?page=3",
    "prev": null,
    "next": "http://localhost:8000/api/companies/minimal?page=2"
  }
}
```

### Ejemplo CompanyExploreResource
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "companyCode": "CMP-2025-00001",
      "name": "Acme Corporation",
      "logoUrl": "https://example.com/logo.png",
      "description": "Leading technology company specializing in enterprise software solutions and cloud infrastructure...",
      "industry": {
        "id": "123e4567-e89b-12d3-a456-426614174000",
        "code": "TECH",
        "name": "Technology"
      },
      "city": "Santiago",
      "country": "Chile",
      "primaryColor": "#FF5733",
      "status": "ACTIVE",
      "followersCount": 42,
      "isFollowedByMe": false
    }
  ],
  "meta": {
    "total": 25,
    "current_page": 1,
    "last_page": 2,
    "per_page": 20
  }
}
```

### Ejemplo CompanyResource (ver arriba en sección 3)

---

## Cómo Aplicar los Cambios

### Opción 1: Modificar manualmente CompanyController.php

1. Abrir `app/Features/CompanyManagement/Http/Controllers/CompanyController.php`
2. Buscar cada anotación `#[OA\Get]`, `#[OA\Post]`, `#[OA\Patch]`
3. Reemplazar TODOS los `new OA\Property(property: 'snake_case')` con `property: 'camelCase'`
4. Agregar campos faltantes (ver listas arriba)
5. Actualizar required fields y limits

### Opción 2: Usar script de reemplazo masivo

```bash
# Desactivar Pint temporalmente
mv .pint.json .pint.json.bak

# Hacer cambios en el controlador

# Regenerar Swagger
php artisan l5-swagger:generate

# Reactivar Pint
mv .pint.json.bak .pint.json
```

### Opción 3: Actualizar storage/api-docs/api-docs.json directamente

⚠️ **NO RECOMENDADO**: Los cambios se perderán al regenerar.

---

## Verificación Final

### Checklist de validación:

- [ ] **GET /api/companies/minimal**: 4 campos en camelCase
- [ ] **GET /api/companies/explore**: 12 campos en camelCase + description truncada
- [ ] **GET /api/companies**: 43 campos en camelCase
- [ ] **GET /api/companies/{company}**: 43 campos en camelCase + isFollowedByMe
- [ ] **POST /api/companies**: Request body con nested structure, response con 43 campos
- [ ] **PATCH /api/companies/{company}**: Request body con 3 nested objects, response con 43 campos
- [ ] Status enum en UPPERCASE: "ACTIVE", "SUSPENDED", "INACTIVE"
- [ ] Todos los campos calculados incluidos
- [ ] Admin desestructurado (adminId, adminName, adminEmail, adminAvatar)
- [ ] Timestamps en formato ISO8601

---

## Comandos para Regenerar Swagger

```bash
# Generar documentación desde anotaciones
php artisan l5-swagger:generate

# Verificar endpoint Swagger UI
# http://localhost:8000/api/documentation

# Verificar JSON generado
cat storage/api-docs/api-docs.json | jq '.paths."/api/companies"'
```

---

## Conclusión

La documentación Swagger actual NO refleja la implementación real por:

1. **Uso de snake_case en lugar de camelCase** (28+ campos afectados)
2. **Campos faltantes en responses** (especialmente en explore y management endpoints)
3. **Request bodies incompletos** (falta estructura nested)
4. **Campos calculados no documentados** (followersCount, activeAgentsCount, etc.)
5. **Admin no desestructurado** (falta adminId, adminName, adminEmail, adminAvatar)
6. **Status enum incorrecto** (debe ser UPPERCASE)

**Prioridad**: CRÍTICA - La documentación es la fuente de verdad para consumidores de la API.

---

**Próximos pasos recomendados**:
1. Aplicar cambios en CompanyController.php
2. Regenerar Swagger: `php artisan l5-swagger:generate`
3. Validar con tests automatizados
4. Actualizar documentación en Postman/Insomnia si aplica
