# 🎫 TICKET MANAGEMENT API v1.0 - DOCUMENTACIÓN COMPLETA

> **Sistema**: Helpdesk Multi-Tenant  
> **Feature**: Ticket Management  
> **Versión**: 1.0 Final  
> **Base URL**: `/api/v1`  
> **Autenticación**: Bearer Token (JWT)  
> **Auto-Assignment**: Trigger PostgreSQL automático

---

## 📑 TABLA DE CONTENIDOS

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Índice Completo de Endpoints](#índice-completo-de-endpoints)
3. [Autenticación y Contexto](#autenticación-y-contexto)
4. [Endpoints - Categorías](#endpoints---categorías)
5. [Endpoints - Tickets](#endpoints---tickets)
6. [Endpoints - Respuestas](#endpoints---respuestas)
7. [Endpoints - Notas Internas](#endpoints---notas-internas)
8. [Endpoints - Adjuntos](#endpoints---adjuntos)
9. [Endpoints - Calificaciones](#endpoints---calificaciones)
10. [Reglas de Negocio](#reglas-de-negocio)
11. [Permisos y Visibilidad](#permisos-y-visibilidad)
12. [Códigos de Error](#códigos-de-error)

---

## 🏗️ ARQUITECTURA DEL SISTEMA

### Filosofía de Diseño

**✅ Auto-Assignment con Trigger**: El primer agente que responde queda asignado automáticamente
- Trigger PostgreSQL ejecuta después de INSERT en `ticket_responses`
- Si `author_type = 'agent'` Y `owner_agent_id IS NULL` → asigna automáticamente
- Cambia status de `open` → `pending` automáticamente
- Marca `first_response_at` con timestamp

**✅ Doble Conversación**: Separación clara entre mensajes públicos y privados
- **Responses**: Conversación pública (cliente ↔ agente)
- **Internal Notes**: Colaboración privada (agente ↔ agente)

**✅ Attachments Flexibles**: Soporta 2 escenarios
- Al crear ticket: `response_id = NULL`
- En una respuesta específica: `response_id = UUID`

**✅ Company ID por Contexto**: Inferido según el rol
- **USER**: Debe especificar `company_id` (empresa debe existir en el sistema)
- **AGENT/ADMIN**: Inferido automáticamente del JWT token

**✅ Calificaciones Históricas**: Guarda snapshot del agente
- `rated_agent_id` se guarda al momento de calificar
- NO cambia si reasignan el ticket después

---

## 📋 ÍNDICE COMPLETO DE ENDPOINTS

### 📂 Categorías (4 endpoints)

| Método | Endpoint | Descripción | Roles |
|--------|----------|-------------|-------|
| GET | `/tickets/categories` | Listar categorías de empresa | 👤 USER, 👮 AGENT, 👨‍💼 ADMIN |
| POST | `/tickets/categories` | Crear categoría | 👨‍💼 COMPANY_ADMIN |
| PUT | `/tickets/categories/:id` | Actualizar categoría | 👨‍💼 COMPANY_ADMIN |
| DELETE | `/tickets/categories/:id` | Eliminar categoría | 👨‍💼 COMPANY_ADMIN |

### 🎫 Tickets (9 endpoints)

| Método | Endpoint | Descripción | Roles |
|--------|----------|-------------|-------|
| GET | `/tickets` | Listar con filtros avanzados | 👤 USER, 👮 AGENT, 👨‍💼 ADMIN |
| GET | `/tickets/:code` | Ver ticket específico | 👤 USER (owner), 👮 AGENT |
| POST | `/tickets` | Crear ticket | 👤 USER |
| PUT | `/tickets/:code` | Actualizar ticket | 👤 USER (owner), 👮 AGENT |
| POST | `/tickets/:code/resolve` | Marcar como resuelto | 👮 AGENT |
| POST | `/tickets/:code/close` | Cerrar ticket | 👮 AGENT, 👤 USER (resolved) |
| POST | `/tickets/:code/reopen` | Reabrir ticket | 👤 USER (owner, 30d), 👮 AGENT |
| POST | `/tickets/:code/assign` | Reasignar a otro agente | 👮 AGENT |
| DELETE | `/tickets/:code` | Eliminar ticket | 👨‍💼 COMPANY_ADMIN |

### 💬 Respuestas (4 endpoints)

| Método | Endpoint | Descripción | Roles |
|--------|----------|-------------|-------|
| GET | `/tickets/:code/responses` | Listar respuestas | 👤 USER (owner), 👮 AGENT |
| POST | `/tickets/:code/responses` | Agregar respuesta | 👤 USER (owner), 👮 AGENT |
| PUT | `/tickets/:code/responses/:id` | Editar respuesta | Autor (30 min) |
| DELETE | `/tickets/:code/responses/:id` | Eliminar respuesta | Autor (30 min) |

### 📝 Notas Internas (4 endpoints)

| Método | Endpoint | Descripción | Roles |
|--------|----------|-------------|-------|
| GET | `/tickets/:code/internal-notes` | Listar notas | 👮 AGENT, 👨‍💼 ADMIN |
| POST | `/tickets/:code/internal-notes` | Agregar nota | 👮 AGENT, 👨‍💼 ADMIN |
| PUT | `/tickets/:code/internal-notes/:id` | Editar nota | Autor |
| DELETE | `/tickets/:code/internal-notes/:id` | Eliminar nota | Autor |

### 📎 Adjuntos (3 endpoints)

| Método | Endpoint | Descripción | Roles |
|--------|----------|-------------|-------|
| GET | `/tickets/:code/attachments` | Listar adjuntos | 👤 USER (owner), 👮 AGENT |
| POST | `/tickets/:code/attachments` | Subir adjunto | 👤 USER (owner), 👮 AGENT |
| DELETE | `/tickets/:code/attachments/:id` | Eliminar adjunto | Uploader (30 min) |

### ⭐ Calificaciones (3 endpoints)

| Método | Endpoint | Descripción | Roles |
|--------|----------|-------------|-------|
| POST | `/tickets/:code/rating` | Calificar ticket | 👤 USER (owner, resolved/closed) |
| PUT | `/tickets/:code/rating` | Actualizar calificación | 👤 USER (owner, 24h) |
| GET | `/tickets/:code/rating` | Ver calificación | 👤 USER (owner), 👮 AGENT |

**Total: 30 endpoints**

---

## 🔑 AUTENTICACIÓN Y CONTEXTO

### JWT Token Structure

```json
{
  "sub": "user-uuid-here",
  "role": "USER",  // USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN
  "company_id": "company-uuid-here",  // Solo para AGENT/ADMIN
  "exp": 1699000000
}
```

### Headers Requeridos

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json  // o multipart/form-data para archivos
```

### Company Context por Rol

**USER**:
```json
// Debe especificar company_id en el request
{
  "company_id": "550e8400-e29b-41d4-a716-446655440001",
  "title": "Mi problema..."
}
```

**AGENT/ADMIN**:
```php
// Backend infiere automáticamente
$companyId = auth()->user()->company_id;  // Del JWT
```

---

## 📂 ENDPOINTS - CATEGORÍAS

### 1. Listar Categorías

```http
GET /api/v1/tickets/categories?company_id={uuid}
Authorization: Bearer {token}
```

**Query Parameters**:

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `company_id` | uuid | ✅ | ID de la empresa |
| `is_active` | boolean | ❌ | Filtrar activas/inactivas |

**Response 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "cat-uuid-1",
      "company_id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Soporte Técnico",
      "description": "Problemas técnicos con el sistema",
      "is_active": true,
      "created_at": "2024-10-01T10:00:00Z"
    },
    {
      "id": "cat-uuid-2",
      "company_id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Facturación",
      "description": "Consultas sobre pagos y facturas",
      "is_active": true,
      "created_at": "2024-10-01T10:05:00Z"
    }
  ]
}
```

---

### 2. Crear Categoría

```http
POST /api/v1/tickets/categories
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "name": "Reportes y Analíticas",
  "description": "Consultas sobre reportes y métricas del sistema",
  "is_active": true
}
```

**Validaciones**:
- `name`: 3-100 caracteres, único por empresa
- `description`: Opcional, máximo 500 caracteres

**Response 201 Created**:
```json
{
  "success": true,
  "message": "Categoría creada exitosamente",
  "data": {
    "id": "cat-uuid-new",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "name": "Reportes y Analíticas",
    "description": "Consultas sobre reportes y métricas del sistema",
    "is_active": true,
    "created_at": "2025-11-09T14:00:00Z"
  }
}
```

---

### 3. Actualizar Categoría

```http
PUT /api/v1/tickets/categories/:id
Authorization: Bearer {token}
```

**Request Body** (parcial):
```json
{
  "name": "Reportes, Analíticas y Métricas",
  "is_active": false
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Categoría actualizada exitosamente",
  "data": {
    "id": "cat-uuid-1",
    "name": "Reportes, Analíticas y Métricas",
    "is_active": false,
    "updated_at": "2025-11-09T15:00:00Z"
  }
}
```

---

### 4. Eliminar Categoría

```http
DELETE /api/v1/tickets/categories/:id
Authorization: Bearer {token}
```

**⚠️ Validación**: No se puede eliminar si hay tickets activos usando esta categoría

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Categoría eliminada exitosamente"
}
```

**Response 409 Conflict**:
```json
{
  "success": false,
  "error": {
    "code": "CATEGORY_IN_USE",
    "message": "No se puede eliminar la categoría porque hay 15 tickets activos usándola",
    "active_tickets_count": 15
  }
}
```

---

## 🎫 ENDPOINTS - TICKETS

### 5. Listar Tickets

```http
GET /api/v1/tickets
Authorization: Bearer {token}
```

**Query Parameters**:

| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `company_id` | uuid | - | Filtrar por empresa (requerido para USER) |
| `status` | enum | - | `open`, `pending`, `resolved`, `closed` |
| `category_id` | uuid | - | Filtrar por categoría |
| `owner_agent_id` | uuid | - | Filtrar por agente (`me` = yo) |
| `created_by_user_id` | uuid | - | Filtrar por creador |
| `search` | string | - | Búsqueda en título y descripción |
| `created_after` | date | - | Creados después de fecha |
| `created_before` | date | - | Creados antes de fecha |
| `sort` | string | `-created_at` | `-created_at`, `-updated_at`, `status` |
| `page` | int | 1 | Número de página |
| `per_page` | int | 20 | Items por página (max: 100) |

**Reglas de Visibilidad**:
- **USER**: Solo ve sus propios tickets
- **AGENT**: Ve todos los tickets de su empresa
- **COMPANY_ADMIN**: Ve todos los tickets de su empresa

**Ejemplo Request (Usuario)**:
```http
GET /api/v1/tickets?company_id=550e8400-e29b&status=open&sort=-created_at
Authorization: Bearer {token-usuario}
```

**Ejemplo Request (Agente - "mis tickets")**:
```http
GET /api/v1/tickets?owner_agent_id=me&status=pending&per_page=50
Authorization: Bearer {token-agente}
```

**Response 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "tkt-uuid-1",
      "ticket_code": "TKT-2025-00123",
      "company_id": "550e8400-e29b-41d4-a716-446655440001",
      "company_name": "Tech Solutions Inc.",
      "created_by_user_id": "user-uuid-1",
      "created_by_name": "Juan Pérez",
      "created_by_email": "juan@email.com",
      "category_id": "cat-uuid-1",
      "category_name": "Soporte Técnico",
      "title": "Error al exportar reportes a Excel",
      "status": "pending",
      "owner_agent_id": "agent-uuid-1",
      "owner_agent_name": "María González",
      "created_at": "2025-11-05T10:30:00Z",
      "updated_at": "2025-11-05T11:15:00Z",
      "first_response_at": "2025-11-05T11:15:00Z",
      "resolved_at": null,
      "closed_at": null,
      "responses_count": 3,
      "attachments_count": 2
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1,
    "from": 1,
    "to": 1,
    "filters_applied": {
      "company_id": "550e8400-e29b-41d4-a716-446655440001",
      "status": "open"
    }
  }
}
```

---

### 6. Ver Ticket Específico

```http
GET /api/v1/tickets/:code
Authorization: Bearer {token}
```

**Ejemplo**:
```http
GET /api/v1/tickets/TKT-2025-00123
```

**Response 200 OK**:
```json
{
  "success": true,
  "data": {
    "id": "tkt-uuid-1",
    "ticket_code": "TKT-2025-00123",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "company_name": "Tech Solutions Inc.",
    "created_by": {
      "id": "user-uuid-1",
      "name": "Juan Pérez",
      "email": "juan@email.com",
      "avatar_url": "https://cdn.example.com/avatars/juan.jpg"
    },
    "category": {
      "id": "cat-uuid-1",
      "name": "Soporte Técnico"
    },
    "title": "Error al exportar reportes a Excel",
    "initial_description": "Cuando intento exportar un reporte a Excel, me sale un error 500...",
    "status": "pending",
    "owner_agent": {
      "id": "agent-uuid-1",
      "name": "María González",
      "email": "maria@techsolutions.com",
      "avatar_url": "https://cdn.example.com/avatars/maria.jpg"
    },
    "created_at": "2025-11-05T10:30:00Z",
    "updated_at": "2025-11-05T11:15:00Z",
    "first_response_at": "2025-11-05T11:15:00Z",
    "resolved_at": null,
    "closed_at": null,
    "rating": null
  }
}
```

**Response 403 Forbidden** (No es el owner):
```json
{
  "success": false,
  "error": {
    "code": "NOT_TICKET_OWNER",
    "message": "No puedes ver este ticket porque no eres el propietario",
    "ticket_code": "TKT-2025-00123"
  }
}
```

---

### 7. Crear Ticket

```http
POST /api/v1/tickets
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "company_id": "550e8400-e29b-41d4-a716-446655440001",
  "category_id": "cat-uuid-1",
  "title": "No puedo resetear mi contraseña",
  "initial_description": "Hola, cuando intento resetear mi contraseña usando el link del email, me dice que el link expiró, pero el email me llegó hace 2 minutos.\n\nYa probé 3 veces y sigue sin funcionar.\n\n¿Pueden ayudarme?\n\nGracias."
}
```

**Validaciones**:
- `company_id`: UUID válido, empresa debe existir en el sistema
- `category_id`: UUID válido, categoría debe existir y estar activa
- `title`: 5-255 caracteres
- `initial_description`: 10-5000 caracteres

**Response 201 Created**:
```json
{
  "success": true,
  "message": "Ticket creado exitosamente",
  "data": {
    "id": "tkt-uuid-new",
    "ticket_code": "TKT-2025-00456",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "created_by_user_id": "user-uuid-1",
    "category_id": "cat-uuid-1",
    "title": "No puedo resetear mi contraseña",
    "status": "open",
    "owner_agent_id": null,
    "created_at": "2025-11-09T14:30:00Z",
    "updated_at": "2025-11-09T14:30:00Z"
  }
}
```

---

### 8. Actualizar Ticket

```http
PUT /api/v1/tickets/:code
Authorization: Bearer {token}
```

**Permisos**:
- **USER (owner)**: Solo puede actualizar `title` y `category_id` si status = `open`
- **AGENT**: Puede actualizar `title`, `category_id`, `status` (excepto `closed`)

**Request Body** (parcial):
```json
{
  "title": "Título actualizado",
  "category_id": "cat-uuid-2"
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Ticket actualizado exitosamente",
  "data": {
    "id": "tkt-uuid-1",
    "ticket_code": "TKT-2025-00456",
    "title": "Título actualizado",
    "category_id": "cat-uuid-2",
    "updated_at": "2025-11-09T15:00:00Z"
  }
}
```

**Response 403 Forbidden** (Usuario con ticket pending):
```json
{
  "success": false,
  "error": {
    "code": "CANNOT_EDIT_TICKET",
    "message": "No puedes editar este ticket porque ya tiene respuestas de agentes",
    "current_status": "pending"
  }
}
```

---

### 9. Marcar como Resuelto

```http
POST /api/v1/tickets/:code/resolve
Authorization: Bearer {token}
```

**⚠️ Solo AGENT puede ejecutar esta acción**

**Request Body** (opcional):
```json
{
  "resolution_note": "He reseteado manualmente tu contraseña. Te envié un email con tu nueva contraseña temporal. Por favor cámbiala al iniciar sesión."
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Ticket marcado como resuelto",
  "data": {
    "id": "tkt-uuid-1",
    "ticket_code": "TKT-2025-00456",
    "status": "resolved",
    "resolved_at": "2025-11-09T15:00:00Z",
    "updated_at": "2025-11-09T15:00:00Z"
  }
}
```

**⚠️ Auto-Close**: Sistema cerrará automáticamente el ticket si no hay respuestas en 7 días

---

### 10. Cerrar Ticket

```http
POST /api/v1/tickets/:code/close
Authorization: Bearer {token}
```

**Permisos**:
- **AGENT**: Puede cerrar cualquier ticket
- **USER (owner)**: Puede cerrar su propio ticket si está en `resolved`

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Ticket cerrado exitosamente",
  "data": {
    "id": "tkt-uuid-1",
    "ticket_code": "TKT-2025-00456",
    "status": "closed",
    "closed_at": "2025-11-13T10:00:00Z"
  }
}
```

---

### 11. Reabrir Ticket

```http
POST /api/v1/tickets/:code/reopen
Authorization: Bearer {token}
```

**Permisos**:
- **USER (owner)**: Puede reabrir tickets `resolved` o `closed` (max 30 días)
- **AGENT**: Puede reabrir cualquier ticket

**Request Body** (opcional):
```json
{
  "reopen_reason": "El problema volvió a ocurrir"
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Ticket reabierto exitosamente",
  "data": {
    "id": "tkt-uuid-1",
    "ticket_code": "TKT-2025-00456",
    "status": "pending",
    "updated_at": "2025-11-14T08:00:00Z"
  }
}
```

**Response 403 Forbidden** (Más de 30 días):
```json
{
  "success": false,
  "error": {
    "code": "CANNOT_REOPEN_TICKET",
    "message": "No puedes reabrir un ticket cerrado hace más de 30 días",
    "closed_at": "2024-10-01T10:00:00Z"
  }
}
```

---

### 12. Reasignar Ticket

```http
POST /api/v1/tickets/:code/assign
Authorization: Bearer {token}
```

**⚠️ Solo AGENT puede ejecutar esta acción**

**Request Body**:
```json
{
  "new_agent_id": "agent-uuid-2",
  "assignment_note": "Reasignando a Carlos porque es experto en este tipo de issues"
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Ticket reasignado exitosamente",
  "data": {
    "id": "tkt-uuid-1",
    "ticket_code": "TKT-2025-00456",
    "owner_agent_id": "agent-uuid-2",
    "owner_agent_name": "Carlos Méndez",
    "updated_at": "2025-11-09T15:30:00Z"
  }
}
```

---

### 13. Eliminar Ticket

```http
DELETE /api/v1/tickets/:code
Authorization: Bearer {token}
```

**⚠️ Solo COMPANY_ADMIN puede eliminar tickets**

**Restricción**: Solo se pueden eliminar tickets en estado `closed`

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Ticket eliminado permanentemente"
}
```

**Response 403 Forbidden**:
```json
{
  "success": false,
  "error": {
    "code": "CANNOT_DELETE_ACTIVE_TICKET",
    "message": "No se puede eliminar un ticket activo. Debe estar cerrado.",
    "current_status": "pending"
  }
}
```

---

## 💬 ENDPOINTS - RESPUESTAS

### 14. Listar Respuestas

```http
GET /api/v1/tickets/:code/responses
Authorization: Bearer {token}
```

**Response 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "resp-uuid-1",
      "ticket_id": "tkt-uuid-1",
      "author_id": "agent-uuid-1",
      "author_name": "María González",
      "author_type": "agent",
      "response_content": "Hola Juan, gracias por reportar esto. Estoy investigando el problema...",
      "created_at": "2025-11-05T11:15:00Z",
      "attachments": []
    },
    {
      "id": "resp-uuid-2",
      "ticket_id": "tkt-uuid-1",
      "author_id": "user-uuid-1",
      "author_name": "Juan Pérez",
      "author_type": "user",
      "response_content": "Gracias María. ¿Hay alguna estimación de cuándo estará resuelto?",
      "created_at": "2025-11-05T12:00:00Z",
      "attachments": []
    }
  ],
  "meta": {
    "total": 2
  }
}
```

---

### 15. Agregar Respuesta

```http
POST /api/v1/tickets/:code/responses
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
  "response_content": "He reseteado tu contraseña. Te envié un email con la nueva contraseña temporal."
}
```

**Validaciones**:
- `response_content`: 1-5000 caracteres, requerido

**⚠️ Side Effects (si author_type = agent)**:
1. Si `owner_agent_id` es NULL → Se asigna automáticamente (trigger)
2. Si `status` = `open` → Cambia a `pending` (trigger)
3. Si `first_response_at` es NULL → Se marca timestamp (trigger)

**Response 201 Created**:
```json
{
  "success": true,
  "message": "Respuesta agregada exitosamente",
  "data": {
    "id": "resp-uuid-new",
    "ticket_id": "tkt-uuid-1",
    "author_id": "agent-uuid-1",
    "author_name": "María González",
    "author_type": "agent",
    "response_content": "He reseteado tu contraseña...",
    "created_at": "2025-11-09T15:00:00Z",
    "ticket_updated": {
      "owner_agent_id": "agent-uuid-1",
      "status": "pending",
      "first_response_at": "2025-11-09T15:00:00Z"
    }
  }
}
```

---

### 16. Editar Respuesta

```http
PUT /api/v1/tickets/:code/responses/:id
Authorization: Bearer {token}
```

**⚠️ Restricciones**:
- Solo el autor puede editar
- Solo se puede editar en los primeros 30 minutos
- No se puede editar si el ticket está `closed`

**Request Body**:
```json
{
  "response_content": "Contenido actualizado..."
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Respuesta actualizada exitosamente",
  "data": {
    "id": "resp-uuid-1",
    "response_content": "Contenido actualizado...",
    "updated_at": "2025-11-09T15:15:00Z"
  }
}
```

**Response 403 Forbidden** (Más de 30 min):
```json
{
  "success": false,
  "error": {
    "code": "RESPONSE_NOT_EDITABLE",
    "message": "Solo puedes editar respuestas en los primeros 30 minutos",
    "created_at": "2025-11-05T10:00:00Z",
    "time_elapsed_minutes": 45
  }
}
```

---

### 17. Eliminar Respuesta

```http
DELETE /api/v1/tickets/:code/responses/:id
Authorization: Bearer {token}
```

**⚠️ Mismas restricciones que editar**

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Respuesta eliminada exitosamente"
}
```

---

## 📝 ENDPOINTS - NOTAS INTERNAS

### 18. Listar Notas Internas

```http
GET /api/v1/tickets/:code/internal-notes
Authorization: Bearer {token}
```

**⚠️ Solo AGENT puede ver notas internas**

**Response 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "note-uuid-1",
      "ticket_id": "tkt-uuid-1",
      "agent_id": "agent-uuid-1",
      "agent_name": "María González",
      "note_content": "Este usuario ya reportó un problema similar hace 2 meses. Revisar ticket TKT-2024-03456",
      "created_at": "2025-11-05T11:20:00Z",
      "updated_at": "2025-11-05T11:20:00Z"
    }
  ]
}
```

---

### 19. Agregar Nota Interna

```http
POST /api/v1/tickets/:code/internal-notes
Authorization: Bearer {token}
```

**Request Body**:
```json
{
  "note_content": "Escalé este issue al equipo de backend. Esperando respuesta."
}
```

**Response 201 Created**:
```json
{
  "success": true,
  "message": "Nota interna agregada",
  "data": {
    "id": "note-uuid-new",
    "ticket_id": "tkt-uuid-1",
    "agent_id": "agent-uuid-1",
    "agent_name": "María González",
    "note_content": "Escalé este issue al equipo de backend...",
    "created_at": "2025-11-09T15:30:00Z"
  }
}
```

---

### 20. Editar Nota Interna

```http
PUT /api/v1/tickets/:code/internal-notes/:id
Authorization: Bearer {token}
```

**⚠️ Solo el autor puede editar su propia nota**

**Request Body**:
```json
{
  "note_content": "Nota actualizada..."
}
```

---

### 21. Eliminar Nota Interna

```http
DELETE /api/v1/tickets/:code/internal-notes/:id
Authorization: Bearer {token}
```

**⚠️ Solo el autor puede eliminar su propia nota**

---

## 📎 ENDPOINTS - ADJUNTOS

### 22. Listar Adjuntos

```http
GET /api/v1/tickets/:code/attachments
Authorization: Bearer {token}
```

**Response 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "att-uuid-1",
      "ticket_id": "tkt-uuid-1",
      "response_id": null,
      "uploaded_by_user_id": "user-uuid-1",
      "uploaded_by_name": "Juan Pérez",
      "file_name": "screenshot-error.png",
      "file_url": "https://cdn.example.com/attachments/screenshot-error.png",
      "file_type": "image/png",
      "file_size_bytes": 245678,
      "created_at": "2025-11-05T10:35:00Z"
    }
  ]
}
```

---

### 23. Subir Adjunto

```http
POST /api/v1/tickets/:code/attachments
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Form Data**:
```
file: [binary data]
```

**Validaciones**:
- Tamaño máximo: 10 MB
- Tipos permitidos: PDF, JPG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, ZIP
- Máximo 5 archivos por ticket

**Response 201 Created**:
```json
{
  "success": true,
  "message": "Archivo subido exitosamente",
  "data": {
    "id": "att-uuid-new",
    "ticket_id": "tkt-uuid-1",
    "response_id": null,
    "uploaded_by_user_id": "user-uuid-1",
    "file_name": "error-log.txt",
    "file_url": "https://cdn.example.com/attachments/error-log.txt",
    "file_type": "text/plain",
    "file_size_bytes": 4567,
    "created_at": "2025-11-09T16:00:00Z"
  }
}
```

**Response 413 Payload Too Large**:
```json
{
  "success": false,
  "error": {
    "code": "FILE_TOO_LARGE",
    "message": "El archivo excede el tamaño máximo permitido",
    "max_size_mb": 10,
    "file_size_mb": 15.5
  }
}
```

---

### 24. Eliminar Adjunto

```http
DELETE /api/v1/tickets/:code/attachments/:id
Authorization: Bearer {token}
```

**⚠️ Restricciones**:
- Solo el uploader puede eliminar
- Solo se puede eliminar en los primeros 30 minutos
- No se puede eliminar si el ticket está `closed`

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Archivo eliminado exitosamente"
}
```

---

## ⭐ ENDPOINTS - CALIFICACIONES

### 25. Calificar Ticket

```http
POST /api/v1/tickets/:code/rating
Authorization: Bearer {token}
```

**⚠️ Restricciones**:
- Solo el owner del ticket puede calificar
- Solo se puede calificar tickets en estado `resolved` o `closed`
- Solo se puede calificar UNA vez por ticket

**Request Body**:
```json
{
  "rating": 5,
  "comment": "Excelente atención. María fue muy rápida y clara en sus respuestas. ¡Gracias!"
}
```

**Validaciones**:
- `rating`: Entero entre 1-5, requerido
- `comment`: 0-1000 caracteres, opcional

**Response 201 Created**:
```json
{
  "success": true,
  "message": "Calificación registrada exitosamente",
  "data": {
    "id": "rating-uuid-1",
    "ticket_id": "tkt-uuid-1",
    "customer_id": "user-uuid-1",
    "rated_agent_id": "agent-uuid-1",
    "rated_agent_name": "María González",
    "rating": 5,
    "comment": "Excelente atención. María fue muy rápida...",
    "created_at": "2025-11-09T16:30:00Z"
  }
}
```

**Response 409 Conflict** (Ya calificó):
```json
{
  "success": false,
  "error": {
    "code": "RATING_ALREADY_EXISTS",
    "message": "Este ticket ya fue calificado. Usa PUT para actualizar la calificación.",
    "existing_rating": {
      "rating": 5,
      "created_at": "2025-11-09T16:30:00Z"
    }
  }
}
```

---

### 26. Actualizar Calificación

```http
PUT /api/v1/tickets/:code/rating
Authorization: Bearer {token}
```

**⚠️ Solo se puede actualizar en las primeras 24 horas**

**Request Body**:
```json
{
  "rating": 4,
  "comment": "Comentario actualizado..."
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Calificación actualizada exitosamente",
  "data": {
    "rating": 4,
    "comment": "Comentario actualizado...",
    "updated_at": "2025-11-09T17:00:00Z"
  }
}
```

---

### 27. Ver Calificación

```http
GET /api/v1/tickets/:code/rating
Authorization: Bearer {token}
```

**Permisos**:
- **USER (owner)**: Puede ver su propia calificación
- **AGENT**: Puede ver calificaciones de tickets de su empresa

**Response 200 OK**:
```json
{
  "success": true,
  "data": {
    "id": "rating-uuid-1",
    "ticket_id": "tkt-uuid-1",
    "customer_name": "Juan Pérez",
    "rated_agent_name": "María González",
    "rating": 5,
    "comment": "Excelente atención...",
    "created_at": "2025-11-09T16:30:00Z"
  }
}
```

---

## 📖 REGLAS DE NEGOCIO

### Ciclo de Vida del Ticket

```
┌─────────┐
│  OPEN   │ ← Ticket recién creado
└────┬────┘
     │
     │ (Agente responde por primera vez)
     ▼
┌─────────┐
│ PENDING │ ← Ticket con respuesta de agente
└────┬────┘
     │
     │ (Agente marca como resuelto)
     ▼
┌──────────┐
│ RESOLVED │ ← Problema solucionado
└────┬─────┘
     │
     │ (Manual o Auto después de 7 días)
     ▼
┌─────────┐
│ CLOSED  │ ← Ticket cerrado
└─────────┘
```

### Auto-Assignment (Trigger PostgreSQL)

```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- Solo si author_type = 'agent' Y owner_agent_id IS NULL

UPDATE ticketing.tickets
SET
    owner_agent_id = NEW.author_id,
    first_response_at = NOW(),
    status = 'pending'
WHERE id = NEW.ticket_id
AND owner_agent_id IS NULL;
```

### Auto-Close (Cron Job)

```php
// Ejecutar diariamente
Ticket::where('status', 'resolved')
    ->where('resolved_at', '<', now()->subDays(7))
    ->update([
        'status' => 'closed',
        'closed_at' => now()
    ]);
```

---

## 🔒 PERMISOS Y VISIBILIDAD

### Matriz Completa de Permisos

| Operación | USER | AGENT | COMPANY_ADMIN |
|-----------|:----:|:-----:|:-------------:|
| **CATEGORÍAS** |
| Ver categorías | ✅ | ✅ | ✅ |
| Crear categoría | ❌ | ❌ | ✅ |
| Editar categoría | ❌ | ❌ | ✅ |
| Eliminar categoría | ❌ | ❌ | ✅ |
| **TICKETS** |
| Ver propios tickets | ✅ | ✅ | ✅ |
| Ver todos tickets | ❌ | ✅ | ✅ |
| Crear ticket | ✅ | ❌ | ❌ |
| Editar título (open) | ✅ (propio) | ✅ | ✅ |
| Marcar resuelto | ❌ | ✅ | ✅ |
| Cerrar ticket | ✅ (resolved) | ✅ | ✅ |
| Reabrir ticket | ✅ (30d) | ✅ | ✅ |
| Reasignar agente | ❌ | ✅ | ✅ |
| Eliminar ticket | ❌ | ❌ | ✅ |
| **RESPUESTAS** |
| Ver respuestas | ✅ (propio) | ✅ | ✅ |
| Agregar respuesta | ✅ (propio) | ✅ | ✅ |
| Editar respuesta | ✅ (30min) | ✅ (30min) | ✅ |
| **
