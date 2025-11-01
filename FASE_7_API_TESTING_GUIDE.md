# FASE 7: GUÍA DE PRUEBAS DE API
## CompanyManagement Controllers - V8.0

**Fecha:** 2025-11-01
**Ambiente:** Docker + Laravel 12
**Base URL:** http://localhost:8000/api

---

## ÍNDICE

1. [Endpoints Públicos](#1-endpoints-públicos-sin-autenticación)
2. [Endpoints Autenticados](#2-endpoints-autenticados-require-jwt)
3. [Endpoints de Administración](#3-endpoints-de-administración)
4. [Variables de Entorno](#4-variables-de-entorno)
5. [Troubleshooting](#5-troubleshooting)

---

## 1. ENDPOINTS PÚBLICOS (Sin Autenticación)

### 1.1 Listar Industrias

**Propósito:** Obtener catálogo de industrias para selectores de formularios

```bash
# Listar todas las industrias (sin conteos)
curl -X GET http://localhost:8000/api/company-industries \
  -H "Accept: application/json"

# Listar industrias con conteo de empresas activas
curl -X GET "http://localhost:8000/api/company-industries?with_counts=true" \
  -H "Accept: application/json"
```

**Respuesta esperada (200 OK):**
```json
{
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "code": "technology",
      "name": "Tecnología",
      "description": "Empresas de tecnología y software",
      "createdAt": "2025-10-31T12:00:00Z",
      "activeCompaniesCount": 45  // Solo si with_counts=true
    },
    {
      "id": "660e8400-e29b-41d4-a716-446655440001",
      "code": "finance",
      "name": "Finanzas",
      "description": "Servicios financieros y bancarios",
      "createdAt": "2025-10-31T12:00:00Z",
      "activeCompaniesCount": 23
    }
  ]
}
```

---

### 1.2 Listar Empresas Mínimas

**Propósito:** Obtener lista básica de empresas para selectores

```bash
# Todas las empresas activas
curl -X GET http://localhost:8000/api/companies/minimal \
  -H "Accept: application/json"

# Con búsqueda
curl -X GET "http://localhost:8000/api/companies/minimal?search=Tech" \
  -H "Accept: application/json"

# Con paginación personalizada
curl -X GET "http://localhost:8000/api/companies/minimal?per_page=10&page=2" \
  -H "Accept: application/json"
```

**Respuesta esperada (200 OK):**
```json
{
  "data": [
    {
      "id": "770e8400-e29b-41d4-a716-446655440002",
      "companyCode": "CMP-2025-00001",
      "name": "TechCorp Solutions",
      "logoUrl": "https://example.com/logo.png"
    }
  ],
  "meta": {
    "total": 150,
    "currentPage": 1,
    "lastPage": 3,
    "perPage": 50
  },
  "links": {
    "first": "http://localhost:8000/api/companies/minimal?page=1",
    "last": "http://localhost:8000/api/companies/minimal?page=3",
    "prev": null,
    "next": "http://localhost:8000/api/companies/minimal?page=2"
  }
}
```

---

### 1.3 Crear Solicitud de Empresa

**Propósito:** Público puede solicitar creación de empresa

**Rate limit:** 3 requests por hora

```bash
curl -X POST http://localhost:8000/api/company-requests \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "company_name": "Nueva Tech S.A.",
    "legal_name": "Nueva Tech Sociedad Anónima",
    "admin_email": "admin@nuevatech.com",
    "business_description": "Somos una empresa de desarrollo de software especializada en soluciones empresariales con más de 5 años de experiencia en el mercado latinoamericano.",
    "industry_id": "550e8400-e29b-41d4-a716-446655440000",
    "website": "https://nuevatech.com",
    "estimated_users": 100,
    "contact_country": "Chile",
    "contact_city": "Santiago"
  }'
```

**Respuesta esperada (201 Created):**
```json
{
  "id": "990e8400-e29b-41d4-a716-446655440004",
  "requestCode": "REQ-2025-00001",
  "companyName": "Nueva Tech S.A.",
  "adminEmail": "admin@nuevatech.com",
  "status": "PENDING",
  "createdAt": "2025-11-01T14:30:00Z"
}
```

**Errores comunes:**
- **422 Validation Error:** Campo requerido faltante o inválido
- **429 Too Many Requests:** Rate limit excedido (3 por hora)

---

## 2. ENDPOINTS AUTENTICADOS (Require JWT)

### Obtener JWT Token

Primero necesitas autenticarte:

```bash
# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'

# Respuesta:
# {
#   "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
#   "refreshToken": "...",
#   "expiresIn": 3600
# }
```

**Guardar el token:**
```bash
export JWT_TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."
```

---

### 2.1 Explorar Empresas

**Propósito:** Buscar empresas con filtros avanzados

```bash
# Todas las empresas activas
curl -X GET http://localhost:8000/api/companies/explore \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Filtrar por industria
curl -X GET "http://localhost:8000/api/companies/explore?industry_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Filtros combinados
curl -X GET "http://localhost:8000/api/companies/explore?industry_id=550e8400-e29b-41d4-a716-446655440000&country=Chile&search=Tech&per_page=20" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Solo empresas que sigo
curl -X GET "http://localhost:8000/api/companies/explore?followed_by_me=true" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Con ordenamiento
curl -X GET "http://localhost:8000/api/companies/explore?sort_by=name&sort_direction=asc" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"
```

**Respuesta esperada (200 OK):**
```json
{
  "data": [
    {
      "id": "770e8400-e29b-41d4-a716-446655440002",
      "companyCode": "CMP-2025-00001",
      "name": "TechCorp Solutions",
      "logoUrl": "https://example.com/logo.png",
      "website": "https://techcorp.com",
      "contactCountry": "Chile",
      "industryId": "550e8400-e29b-41d4-a716-446655440000",
      "industry": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "code": "technology",
        "name": "Tecnología"
      },
      "followersCount": 125,
      "isFollowedByMe": false
    }
  ],
  "meta": {
    "total": 45,
    "currentPage": 1,
    "lastPage": 3,
    "perPage": 20
  },
  "links": { ... }
}
```

---

### 2.2 Ver Detalle de Empresa

**Propósito:** Información completa de una empresa específica

```bash
# Reemplaza {company_id} con el UUID real
curl -X GET http://localhost:8000/api/companies/{company_id} \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"
```

**Respuesta esperada (200 OK):**
```json
{
  "id": "770e8400-e29b-41d4-a716-446655440002",
  "companyCode": "CMP-2025-00001",
  "name": "TechCorp Solutions",
  "legalName": "TechCorp Solutions S.A.",
  "description": "Leading software company",
  "status": "ACTIVE",
  "supportEmail": "support@techcorp.com",
  "phone": "+56912345678",
  "website": "https://techcorp.com",
  "industryId": "550e8400-e29b-41d4-a716-446655440000",
  "industry": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "code": "technology",
    "name": "Tecnología",
    "description": "Empresas de tecnología y software"
  },
  "adminId": "880e8400-e29b-41d4-a716-446655440003",
  "adminName": "John Doe",
  "adminEmail": "admin@techcorp.com",
  "adminAvatar": "https://example.com/avatar.png",
  "followersCount": 125,
  "activeAgentsCount": 10,
  "totalUsersCount": 50,
  "isFollowedByMe": false,
  "createdAt": "2025-10-15T10:30:00Z",
  "updatedAt": "2025-10-20T15:45:00Z"
}
```

---

### 2.3 Seguir/Dejar de Seguir Empresa

```bash
# Seguir empresa
curl -X POST http://localhost:8000/api/companies/{company_id}/follow \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Respuesta (201 Created):
# {
#   "success": true,
#   "message": "Ahora sigues a TechCorp Solutions.",
#   "company": { ... },
#   "followedAt": "2025-11-01T14:30:00Z"
# }

# Dejar de seguir
curl -X DELETE http://localhost:8000/api/companies/{company_id}/unfollow \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Verificar si sigo una empresa
curl -X GET http://localhost:8000/api/companies/{company_id}/is-following \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Respuesta:
# {
#   "data": {
#     "isFollowing": true
#   }
# }
```

---

### 2.4 Listar Empresas Seguidas

```bash
curl -X GET http://localhost:8000/api/companies/followed \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"
```

---

## 3. ENDPOINTS DE ADMINISTRACIÓN

### 3.1 Listar Todas las Empresas (ADMIN)

**Roles permitidos:** PLATFORM_ADMIN, COMPANY_ADMIN

```bash
# Todas las empresas
curl -X GET http://localhost:8000/api/companies \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Filtrar por industria
curl -X GET "http://localhost:8000/api/companies?industry_id=550e8400-e29b-41d4-a716-446655440000" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Filtrar por estado
curl -X GET "http://localhost:8000/api/companies?status=active" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Múltiples filtros
curl -X GET "http://localhost:8000/api/companies?status=active&industry_id=550e8400-e29b-41d4-a716-446655440000&search=Tech" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"
```

**Respuesta esperada (200 OK):**
```json
{
  "data": [
    {
      "id": "770e8400-e29b-41d4-a716-446655440002",
      "companyCode": "CMP-2025-00001",
      "name": "TechCorp Solutions",
      "status": "ACTIVE",
      "industryId": "550e8400-e29b-41d4-a716-446655440000",
      "industry": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "code": "technology",
        "name": "Tecnología",
        "description": "Empresas de tecnología y software"
      },
      "adminId": "880e8400-e29b-41d4-a716-446655440003",
      "adminName": "John Doe",
      "adminEmail": "admin@techcorp.com",
      "followersCount": 125,
      "activeAgentsCount": 10,
      "totalUsersCount": 50,
      "createdAt": "2025-10-15T10:30:00Z"
    }
  ],
  "meta": { ... },
  "links": { ... }
}
```

---

### 3.2 Crear Empresa (PLATFORM_ADMIN)

**Solo PLATFORM_ADMIN**

```bash
curl -X POST http://localhost:8000/api/companies \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "New Company Inc.",
    "legal_name": "New Company Incorporated",
    "support_email": "support@newcompany.com",
    "admin_user_id": "880e8400-e29b-41d4-a716-446655440003",
    "industry_id": "550e8400-e29b-41d4-a716-446655440000",
    "phone": "+56912345678",
    "website": "https://newcompany.com",
    "contact_country": "Chile",
    "contact_city": "Santiago",
    "timezone": "America/Santiago"
  }'
```

**Respuesta esperada (201 Created):**
```json
{
  "id": "990e8400-e29b-41d4-a716-446655440005",
  "companyCode": "CMP-2025-00002",
  "name": "New Company Inc.",
  "status": "ACTIVE",
  "adminId": "880e8400-e29b-41d4-a716-446655440003",
  "adminName": "John Doe",
  "adminEmail": "admin@newcompany.com",
  "createdAt": "2025-11-01T15:00:00Z"
}
```

---

### 3.3 Actualizar Empresa

**Roles permitidos:** PLATFORM_ADMIN o COMPANY_ADMIN (solo su empresa)

```bash
curl -X PATCH http://localhost:8000/api/companies/{company_id} \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Updated Company Name",
    "description": "New company description",
    "industry_id": "660e8400-e29b-41d4-a716-446655440001",
    "website": "https://updated.com",
    "primary_color": "#FF5733",
    "secondary_color": "#33FF57"
  }'
```

**Respuesta esperada (200 OK):**
```json
{
  "id": "770e8400-e29b-41d4-a716-446655440002",
  "companyCode": "CMP-2025-00001",
  "name": "Updated Company Name",
  "description": "New company description",
  "industryId": "660e8400-e29b-41d4-a716-446655440001",
  "industry": {
    "id": "660e8400-e29b-41d4-a716-446655440001",
    "code": "finance",
    "name": "Finanzas",
    "description": "Servicios financieros y bancarios"
  },
  "website": "https://updated.com",
  "primaryColor": "#FF5733",
  "secondaryColor": "#33FF57",
  "updatedAt": "2025-11-01T15:30:00Z"
}
```

---

### 3.4 Gestionar Solicitudes de Empresas (PLATFORM_ADMIN)

#### Listar Solicitudes

```bash
# Todas las solicitudes
curl -X GET http://localhost:8000/api/company-requests \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"

# Filtrar por estado
curl -X GET "http://localhost:8000/api/company-requests?status=PENDING" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"
```

**Respuesta esperada (200 OK):**
```json
{
  "data": [
    {
      "id": "990e8400-e29b-41d4-a716-446655440004",
      "requestCode": "REQ-2025-00001",
      "companyName": "Nueva Tech S.A.",
      "legalName": "Nueva Tech Sociedad Anónima",
      "adminEmail": "admin@nuevatech.com",
      "status": "PENDING",
      "industryId": "550e8400-e29b-41d4-a716-446655440000",
      "industry": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "code": "technology",
        "name": "Tecnología"
      },
      "createdAt": "2025-11-01T14:30:00Z"
    }
  ],
  "meta": { ... },
  "links": { ... }
}
```

---

#### Aprobar Solicitud

```bash
curl -X POST http://localhost:8000/api/company-requests/{request_id}/approve \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "notes": "Aprobado después de verificación"
  }'
```

**Respuesta esperada (200 OK):**
```json
{
  "data": {
    "success": true,
    "message": "Solicitud aprobada exitosamente. Se ha creado la empresa 'Nueva Tech S.A.' y se envió un email con las credenciales de acceso a admin@nuevatech.com.",
    "company": {
      "id": "aa0e8400-e29b-41d4-a716-446655440006",
      "companyCode": "CMP-2025-00003",
      "name": "Nueva Tech S.A.",
      "legalName": "Nueva Tech Sociedad Anónima",
      "status": "ACTIVE",
      "adminId": "bb0e8400-e29b-41d4-a716-446655440007",
      "adminEmail": "admin@nuevatech.com",
      "adminName": "Admin User",
      "createdAt": "2025-11-01T15:45:00Z"
    },
    "newUserCreated": true,
    "notificationSentTo": "admin@nuevatech.com"
  }
}
```

---

#### Rechazar Solicitud

```bash
curl -X POST http://localhost:8000/api/company-requests/{request_id}/reject \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "reason": "Información insuficiente proporcionada"
  }'
```

**Respuesta esperada (200 OK):**
```json
{
  "data": {
    "success": true,
    "message": "La solicitud de empresa 'Nueva Tech S.A.' ha sido rechazada. Se ha enviado un email a admin@nuevatech.com con la razón del rechazo.",
    "reason": "Información insuficiente proporcionada",
    "notificationSentTo": "admin@nuevatech.com",
    "requestCode": "REQ-2025-00001"
  }
}
```

---

## 4. VARIABLES DE ENTORNO

Para facilitar las pruebas, configura estas variables:

```bash
# Base URL
export API_URL="http://localhost:8000/api"

# JWT Token (obtener mediante login)
export JWT_TOKEN="eyJ0eXAiOiJKV1QiLCJhbGc..."

# IDs de ejemplo (reemplazar con IDs reales de tu base de datos)
export TECH_INDUSTRY_ID="550e8400-e29b-41d4-a716-446655440000"
export FINANCE_INDUSTRY_ID="660e8400-e29b-41d4-a716-446655440001"
export COMPANY_ID="770e8400-e29b-41d4-a716-446655440002"
export USER_ID="880e8400-e29b-41d4-a716-446655440003"
```

**Ejemplo de uso:**
```bash
curl -X GET "$API_URL/companies/explore?industry_id=$TECH_INDUSTRY_ID" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"
```

---

## 5. TROUBLESHOOTING

### Error 401 - Unauthenticated

**Causa:** Token JWT inválido, expirado o ausente

**Solución:**
```bash
# Obtener nuevo token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

---

### Error 403 - Forbidden

**Causa:** Usuario no tiene el rol necesario

**Verificar roles del usuario:**
```bash
curl -X GET http://localhost:8000/api/auth/status \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Accept: application/json"
```

**Roles necesarios por endpoint:**
- `GET /api/companies` → PLATFORM_ADMIN o COMPANY_ADMIN
- `POST /api/companies` → PLATFORM_ADMIN
- `PATCH /api/companies/{id}` → PLATFORM_ADMIN o COMPANY_ADMIN (owner)
- `GET /api/company-requests` → PLATFORM_ADMIN
- `POST /api/company-requests/{id}/approve` → PLATFORM_ADMIN
- `POST /api/company-requests/{id}/reject` → PLATFORM_ADMIN

---

### Error 404 - Not Found

**Causa:** Recurso no existe o UUID inválido

**Verificar UUID:**
```bash
# Listar empresas disponibles
curl -X GET http://localhost:8000/api/companies/minimal \
  -H "Accept: application/json"
```

---

### Error 422 - Validation Error

**Causa:** Datos de entrada inválidos

**Ejemplo de respuesta:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "industry_id": [
      "The industry id must be a valid UUID."
    ],
    "business_description": [
      "The business description must be at least 50 characters."
    ]
  }
}
```

**Solución:** Corregir los campos indicados en `errors`

---

### Error 429 - Too Many Requests

**Causa:** Rate limit excedido

**Rate limits:**
- `POST /api/company-requests` → 3 por hora
- `POST /api/companies/{id}/follow` → 20 por hora
- `PATCH /api/users/me/profile` → 30 por hora

**Solución:** Esperar hasta que el límite se reinicie

---

### Verificar Estado de la API

```bash
# Health check
curl -X GET http://localhost:8000/api/health

# Respuesta:
# {
#   "status": "ok",
#   "timestamp": "2025-11-01T15:00:00Z",
#   "database": "connected",
#   "redis": "connected"
# }
```

---

### Limpiar Caché de Rutas

Si los endpoints no responden después de cambios:

```bash
docker compose exec app php artisan route:clear
docker compose exec app php artisan optimize:clear
```

---

## ANEXO: POSTMAN COLLECTION

Puedes importar esta colección básica en Postman:

**Archivo:** `CompanyManagement_V8.postman_collection.json`

```json
{
  "info": {
    "name": "CompanyManagement V8.0",
    "description": "API endpoints for CompanyManagement feature",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Public",
      "item": [
        {
          "name": "List Industries",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/company-industries?with_counts=true",
              "host": ["{{base_url}}"],
              "path": ["company-industries"],
              "query": [{"key": "with_counts", "value": "true"}]
            }
          }
        },
        {
          "name": "List Companies Minimal",
          "request": {
            "method": "GET",
            "header": [],
            "url": {
              "raw": "{{base_url}}/companies/minimal",
              "host": ["{{base_url}}"],
              "path": ["companies", "minimal"]
            }
          }
        },
        {
          "name": "Create Company Request",
          "request": {
            "method": "POST",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"company_name\": \"Test Company\",\n  \"legal_name\": \"Test Company Inc.\",\n  \"admin_email\": \"admin@test.com\",\n  \"business_description\": \"A test company for API testing purposes with sufficient description length to pass validation.\",\n  \"industry_id\": \"{{industry_id}}\",\n  \"website\": \"https://test.com\"\n}"
            },
            "url": {
              "raw": "{{base_url}}/company-requests",
              "host": ["{{base_url}}"],
              "path": ["company-requests"]
            }
          }
        }
      ]
    },
    {
      "name": "Authenticated",
      "item": [
        {
          "name": "Explore Companies",
          "request": {
            "method": "GET",
            "header": [{"key": "Authorization", "value": "Bearer {{jwt_token}}"}],
            "url": {
              "raw": "{{base_url}}/companies/explore?industry_id={{industry_id}}",
              "host": ["{{base_url}}"],
              "path": ["companies", "explore"],
              "query": [{"key": "industry_id", "value": "{{industry_id}}"}]
            }
          }
        },
        {
          "name": "Show Company",
          "request": {
            "method": "GET",
            "header": [{"key": "Authorization", "value": "Bearer {{jwt_token}}"}],
            "url": {
              "raw": "{{base_url}}/companies/{{company_id}}",
              "host": ["{{base_url}}"],
              "path": ["companies", "{{company_id}}"]
            }
          }
        }
      ]
    }
  ],
  "variable": [
    {"key": "base_url", "value": "http://localhost:8000/api"},
    {"key": "jwt_token", "value": ""},
    {"key": "industry_id", "value": ""},
    {"key": "company_id", "value": ""}
  ]
}
```

---

**Autor:** Claude Code
**Fecha:** 2025-11-01
**Versión:** V8.0 - FASE 7 CONTROLADORES
