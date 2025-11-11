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
2. [Estados y Transiciones](#estados-y-transiciones)
3. [Índice Completo de Endpoints](#índice-completo-de-endpoints)
4. [Autenticación y Contexto](#autenticación-y-contexto)
5. [Endpoints - Categorías](#endpoints---categorías)
6. [Endpoints - Tickets](#endpoints---tickets)
   - [Query Parameters Detallados](#detalle-de-query-parameters-clave)
   - [Ejemplos de Requests - Casos de Uso](#ejemplos-de-requests---casos-de-uso-completos)
   - [Ejemplos de Responses - Estados](#ejemplos-de-responses---casos-de-estados-diferentes)
7. [Endpoints - Respuestas](#endpoints---respuestas)
8. [Endpoints - Notas Internas](#endpoints---notas-internas)
9. [Endpoints - Adjuntos](#endpoints---adjuntos)
10. [Endpoints - Calificaciones](#endpoints---calificaciones)
11. [Reglas de Negocio](#reglas-de-negocio)
12. [Resumen Crítico - Alineación con Base de Datos](#resumen-crítico---alineación-con-base-de-datos)
13. [Permisos y Visibilidad](#permisos-y-visibilidad)
14. [Códigos de Error](#códigos-de-error)

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

## 🔄 ESTADOS Y TRANSICIONES

### Modelo de 4 Estados

El sistema utiliza un modelo de 4 estados que refleja el ciclo de vida completo del ticket:

| Estado | Significado | Cuándo Ocurre | Quién Espera Acción |
|--------|-------------|---------------|---------------------|
| **OPEN** | Ticket nuevo o cliente respondió | 1) Ticket recién creado (sin agente)<br>2) Cliente respondió a ticket PENDING | **AGENTE** debe responder |
| **PENDING** | Agente respondió, esperando cliente | Agente respondió (automático vía trigger) | **CLIENTE** debe responder |
| **RESOLVED** | Problema resuelto | Agente marca manualmente como resuelto | **CLIENTE** (cerrar o reabrir)<br>**SISTEMA** (auto-close en 7 días) |
| **CLOSED** | Ticket cerrado definitivamente | 1) Manual (agente/cliente)<br>2) Auto-close después de 7 días en RESOLVED | Nadie (historial) |

### Transiciones Automáticas (Triggers PostgreSQL)

#### Trigger 1: Auto-Assignment + Status Change (OPEN → PENDING)
```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- Condición: author_type = 'agent' Y owner_agent_id IS NULL

UPDATE ticketing.tickets
SET
    owner_agent_id = NEW.author_id,
    first_response_at = NOW(),
    status = 'pending',
    last_response_author_type = 'agent'
WHERE id = NEW.ticket_id
AND owner_agent_id IS NULL;
```

**Explicación**: Cuando el PRIMER agente responde a un ticket nuevo, automáticamente:
- Se asigna el ticket a ese agente (`owner_agent_id`)
- Cambia el status de `open` → `pending`
- Marca `first_response_at` con el timestamp
- Actualiza `last_response_author_type` a `agent`

#### Trigger 2: Status Change (PENDING → OPEN)
```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- Condición: author_type = 'user' Y status = 'pending'

UPDATE ticketing.tickets
SET
    status = 'open',
    last_response_author_type = 'user'
WHERE id = NEW.ticket_id
AND status = 'pending';
```

**Explicación**: Cuando el cliente responde a un ticket en estado `pending`:
- Cambia el status de `pending` → `open`
- Actualiza `last_response_author_type` a `user`
- **IMPORTANTE**: El `owner_agent_id` SE MANTIENE igual (no se remueve)

#### Trigger 3: Update last_response_author_type
```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- SIEMPRE actualiza el campo last_response_author_type

UPDATE ticketing.tickets
SET
    last_response_author_type = NEW.author_type,
    updated_at = NOW()
WHERE id = NEW.ticket_id;
```

**Explicación**: Cada vez que alguien responde (agente o cliente):
- Actualiza `last_response_author_type` con el tipo de autor
- Valores posibles: `'none'`, `'user'`, `'agent'`

### Campo: last_response_author_type

Campo crítico para la UI que indica quién respondió último:

| Valor | Significado | Cuándo |
|-------|-------------|--------|
| `none` | Sin respuestas aún | Ticket recién creado |
| `user` | Cliente respondió último | Cliente agregó una respuesta |
| `agent` | Agente respondió último | Agente agregó una respuesta |

**Uso en UI**:
- Combinar con `status` para determinar estados visuales
- Ejemplo: `status=open` + `last_response_author_type=user` = "Cliente respondió, necesita tu atención"
- Ejemplo: `status=pending` + `last_response_author_type=agent` = "Esperando respuesta del cliente"

### Diagrama de Flujo Completo

```
┌─────────────────────────────────────────────────┐
│  TICKET NUEVO (Cliente crea ticket)            │
│  status: open                                    │
│  owner_agent_id: null                           │
│  last_response_author_type: none                │
└──────────────────┬──────────────────────────────┘
                   │
                   │ (PRIMER Agente responde)
                   │ [TRIGGER AUTO-ASSIGNMENT]
                   ▼
┌─────────────────────────────────────────────────┐
│  AGENTE RESPONDIÓ (Esperando cliente)          │
│  status: pending                                 │
│  owner_agent_id: {agente-uuid}                  │
│  last_response_author_type: agent               │
│  first_response_at: 2025-11-11T10:30:00Z       │
└──────────────────┬──────────────────────────────┘
                   │
                   │ (Cliente responde)
                   │ [TRIGGER STATUS CHANGE]
                   ▼
┌─────────────────────────────────────────────────┐
│  CLIENTE RESPONDIÓ (Necesita atención agente)  │
│  status: open                                    │
│  owner_agent_id: {agente-uuid} ← SE MANTIENE   │
│  last_response_author_type: user                │
└──────────────────┬──────────────────────────────┘
                   │
                   │ (Agente marca como resuelto)
                   │ [MANUAL]
                   ▼
┌─────────────────────────────────────────────────┐
│  PROBLEMA RESUELTO                              │
│  status: resolved                                │
│  owner_agent_id: {agente-uuid}                  │
│  last_response_author_type: agent               │
│  resolved_at: 2025-11-11T15:00:00Z             │
└──────────────────┬──────────────────────────────┘
                   │
                   │ (Manual o Auto-close 7 días)
                   ▼
┌─────────────────────────────────────────────────┐
│  TICKET CERRADO                                 │
│  status: closed                                  │
│  owner_agent_id: {agente-uuid}                  │
│  closed_at: 2025-11-11T16:00:00Z               │
└─────────────────────────────────────────────────┘
```

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
| `status` | enum | - | `open`, `pending`, `resolved`, `closed` (soporta múltiples separados por coma) |
| `category_id` | uuid | - | Filtrar por categoría |
| `owner_agent_id` | string/uuid | - | Filtrar por agente: `null` (sin asignar), `me` (mis tickets), `{uuid}` (agente específico) |
| `created_by` | string/uuid | - | Filtrar por creador: `me` (mis tickets creados), `{uuid}` (usuario específico) |
| `last_response_author_type` | enum | - | Filtrar por quién respondió último: `none`, `user`, `agent` |
| `search` | string | - | Búsqueda en título y descripción |
| `created_after` | date | - | Creados después de fecha |
| `created_before` | date | - | Creados antes de fecha |
| `sort` | string | `-created_at` | `-created_at`, `-updated_at`, `status` |
| `page` | int | 1 | Número de página |
| `per_page` | int | 20 | Items por página (max: 100) |

#### Detalle de Query Parameters Clave

**status** (Filtro por estado):
- **Valores**: `open`, `pending`, `resolved`, `closed`
- **Uso**: Filtrar tickets por uno o múltiples estados
- **Ejemplos**:
  - `status=open` → Solo tickets abiertos
  - `status=pending,resolved` → Tickets en pending O resolved
  - `status=open&status=pending` → Tickets en open O pending (alternativa)

**owner_agent_id** (Filtro por agente asignado):
- **Valores**:
  - `null` → Tickets SIN asignar (literal string "null", no valor NULL de BD)
  - `me` → Tickets asignados al agente autenticado
  - `{uuid}` → Tickets asignados a un agente específico
- **Uso**: Filtrar tickets según asignación de agente
- **Ejemplos**:
  - `owner_agent_id=null` → Tickets nuevos sin asignar (cola de entrada)
  - `owner_agent_id=me` → Mis tickets asignados
  - `owner_agent_id=550e8400-e29b-41d4-a716-446655440001` → Tickets de un agente específico

**created_by** (Filtro por creador del ticket):
- **Valores**:
  - `me` → Tickets creados por el usuario autenticado
  - `{uuid}` → Tickets creados por un usuario específico
- **Uso**: Ver tickets que YO creé (perspectiva de cliente)
- **Ejemplos**:
  - `created_by=me` → Mis tickets como cliente
  - `created_by=550e8400-e29b-41d4-a716-446655440001` → Tickets de un usuario específico

**last_response_author_type** (Filtro por último respondedor):
- **Valores**: `none`, `user`, `agent`
- **Uso**: Filtrar tickets según quién respondió último (útil para priorización)
- **Nota**: Campo actualizado automáticamente por trigger PostgreSQL
- **Ejemplos**:
  - `last_response_author_type=none` → Tickets sin respuestas aún
  - `last_response_author_type=user` → Tickets donde cliente respondió último
  - `last_response_author_type=agent` → Tickets donde agente respondió último

**Reglas de Visibilidad**:
- **USER**: Solo ve sus propios tickets (filtro automático por `created_by_user_id`)
- **AGENT**: Ve todos los tickets de su empresa (filtro automático por `company_id`)
- **COMPANY_ADMIN**: Ve todos los tickets de su empresa (filtro automático por `company_id`)

---

### Ejemplos de Requests - Casos de Uso Completos

#### Caso 1: Obtener tickets NUEVOS (sin asignar)

```http
GET /api/v1/tickets?status=open&owner_agent_id=null
Authorization: Bearer {token-agente}
```

**Descripción**: Todos los agentes ven estos tickets. Son tickets sin respuesta de agente.

**Uso**: Cola de entrada / Tickets disponibles para tomar

**Response esperado**:
- `status`: `open`
- `owner_agent_id`: `null`
- `last_response_author_type`: `none`

---

#### Caso 2: Obtener MIS tickets ASIGNADOS

```http
GET /api/v1/tickets?status=open&owner_agent_id=me
Authorization: Bearer {token-agente}
```

**Descripción**: Solo veo mis tickets asignados que requieren mi respuesta.

**Explicación del estado**:
- `status=open` significa: ticket nuevo O cliente respondió a PENDING

**Uso**: Bandeja de entrada del agente / Tickets que necesitan mi atención

**Response esperado**:
- `status`: `open`
- `owner_agent_id`: `{mi_id}`
- `last_response_author_type`: `none` (ticket nuevo) O `user` (cliente respondió)

---

#### Caso 3: Obtener tickets esperando RESPUESTA DEL CLIENTE

```http
GET /api/v1/tickets?status=pending&owner_agent_id=me
Authorization: Bearer {token-agente}
```

**Descripción**: Mis tickets que ya respondí y estoy esperando que cliente responda.

**Uso**: Tickets en espera / Seguimiento

**Response esperado**:
- `status`: `pending`
- `owner_agent_id`: `{mi_id}`
- `last_response_author_type`: `agent`

---

#### Caso 4: Obtener MIS TICKETS como CLIENTE

```http
GET /api/v1/tickets?status=pending,resolved,closed&created_by=me
Authorization: Bearer {token-usuario}
```

**Descripción**: Ver mis propios tickets que no son OPEN (agente ya respondió).

**Uso**: Historial de tickets como cliente / Seguimiento de mis solicitudes

**Response esperado**:
- `created_by_user_id`: `{mi_id}`
- `status`: `pending`, `resolved`, o `closed`
- Múltiples tickets con diferentes estados

---

#### Caso 5: Obtener TICKETS donde acabo de responder (CLIENTE)

```http
GET /api/v1/tickets?status=open&created_by=me&last_response_author_type=user
Authorization: Bearer {token-usuario}
```

**Descripción**: Mis tickets donde YO acabo de responder (y estoy esperando que agente responda).

**Uso**: Tickets pendientes de respuesta del agente

**Response esperado**:
- `status`: `open`
- `created_by_user_id`: `{mi_id}`
- `owner_agent_id`: `{agente-uuid}` (agente asignado SE MANTIENE)
- `last_response_author_type`: `user`

---

#### Caso 6: Obtener TICKETS donde cliente acaba de RESPONDER (AGENTE)

```http
GET /api/v1/tickets?status=open&owner_agent_id=me&last_response_author_type=user
Authorization: Bearer {token-agente}
```

**Descripción**: Mis tickets asignados donde el cliente acaba de responder (necesito atención urgente).

**Uso**: Priorizar respuestas / Notificaciones de cliente

**Response esperado**:
- `status`: `open`
- `owner_agent_id`: `{mi_id}`
- `last_response_author_type`: `user`
- Tickets que requieren mi respuesta inmediata

---

### Tabla Resumen de Filtros Comunes

| Escenario (Rol) | Query String | Descripción |
|-----------------|--------------|-------------|
| **AGENTE: Cola de entrada** | `status=open&owner_agent_id=null` | Tickets nuevos sin asignar |
| **AGENTE: Mis tickets activos** | `status=open&owner_agent_id=me` | Tickets asignados a mí que necesitan respuesta |
| **AGENTE: En espera de cliente** | `status=pending&owner_agent_id=me` | Mis tickets esperando respuesta del cliente |
| **AGENTE: Cliente respondió** | `status=open&owner_agent_id=me&last_response_author_type=user` | Mis tickets con nueva respuesta del cliente |
| **AGENTE: Todos mis tickets** | `owner_agent_id=me` | Todos los tickets asignados a mí |
| **CLIENTE: Mis tickets activos** | `created_by=me&status=open,pending` | Mis tickets en progreso |
| **CLIENTE: Mis tickets resueltos** | `created_by=me&status=resolved` | Mis tickets resueltos (puedo cerrar) |
| **CLIENTE: Historial completo** | `created_by=me` | Todos mis tickets |
| **CLIENTE: Esperando agente** | `created_by=me&status=open&last_response_author_type=user` | Mis tickets donde respondí y espero agente |

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
      "last_response_author_type": "agent",
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
      "status": "pending"
    }
  }
}
```

---

### Ejemplos de Responses - Casos de Estados Diferentes

A continuación se muestran 4 ejemplos de responses que representan los diferentes estados del ciclo de vida de un ticket:

#### Response Ejemplo 1: Ticket OPEN NUEVO (sin agente asignado)

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440099",
    "ticket_code": "TKT-2025-00001",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "company_name": "Tech Solutions Inc.",
    "created_by_user_id": "user-uuid-123",
    "created_by_name": "Juan Pérez",
    "created_by_email": "juan@email.com",
    "category_id": "cat-uuid-1",
    "category_name": "Soporte Técnico",
    "title": "No puedo acceder al sistema",
    "initial_description": "Cuando intento hacer login me sale error 500...",
    "status": "open",
    "owner_agent_id": null,
    "owner_agent_name": null,
    "last_response_author_type": "none",
    "created_at": "2025-11-11T10:00:00Z",
    "updated_at": "2025-11-11T10:00:00Z",
    "first_response_at": null,
    "resolved_at": null,
    "closed_at": null,
    "responses_count": 0,
    "attachments_count": 1
  }
}
```

**Interpretación**:
- Ticket recién creado por el cliente
- Sin respuestas aún (`responses_count: 0`)
- Sin agente asignado (`owner_agent_id: null`)
- Campo `last_response_author_type: "none"` indica que nadie ha respondido
- Visible para TODOS los agentes en la cola de entrada

---

#### Response Ejemplo 2: Ticket PENDING (agente respondió)

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440099",
    "ticket_code": "TKT-2025-00001",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "company_name": "Tech Solutions Inc.",
    "created_by_user_id": "user-uuid-123",
    "created_by_name": "Juan Pérez",
    "created_by_email": "juan@email.com",
    "category_id": "cat-uuid-1",
    "category_name": "Soporte Técnico",
    "title": "No puedo acceder al sistema",
    "initial_description": "Cuando intento hacer login me sale error 500...",
    "status": "pending",
    "owner_agent_id": "agent-uuid-456",
    "owner_agent_name": "María González",
    "last_response_author_type": "agent",
    "created_at": "2025-11-11T10:00:00Z",
    "updated_at": "2025-11-11T10:30:00Z",
    "first_response_at": "2025-11-11T10:30:00Z",
    "resolved_at": null,
    "closed_at": null,
    "responses_count": 1,
    "attachments_count": 1
  }
}
```

**Interpretación**:
- El agente María González respondió por primera vez
- Trigger automático asignó el ticket a María (`owner_agent_id`)
- Trigger cambió el status de `open` → `pending`
- Campo `last_response_author_type: "agent"` indica que el agente respondió último
- `first_response_at` se marcó con el timestamp de la primera respuesta
- Esperando que el cliente responda

---

#### Response Ejemplo 3: Ticket OPEN (cliente respondió a PENDING)

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440099",
    "ticket_code": "TKT-2025-00001",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "company_name": "Tech Solutions Inc.",
    "created_by_user_id": "user-uuid-123",
    "created_by_name": "Juan Pérez",
    "created_by_email": "juan@email.com",
    "category_id": "cat-uuid-1",
    "category_name": "Soporte Técnico",
    "title": "No puedo acceder al sistema",
    "initial_description": "Cuando intento hacer login me sale error 500...",
    "status": "open",
    "owner_agent_id": "agent-uuid-456",
    "owner_agent_name": "María González",
    "last_response_author_type": "user",
    "created_at": "2025-11-11T10:00:00Z",
    "updated_at": "2025-11-11T11:00:00Z",
    "first_response_at": "2025-11-11T10:30:00Z",
    "resolved_at": null,
    "closed_at": null,
    "responses_count": 2,
    "attachments_count": 1
  }
}
```

**Interpretación**:
- El cliente Juan respondió a la respuesta del agente
- Trigger cambió el status de `pending` → `open`
- **IMPORTANTE**: El `owner_agent_id` SE MANTIENE (sigue asignado a María)
- Campo `last_response_author_type: "user"` indica que el cliente respondió último
- El ticket requiere atención urgente del agente María
- `first_response_at` NO cambió (solo se marca la primera vez)

---

#### Response Ejemplo 4: Ticket RESOLVED

```json
{
  "success": true,
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440099",
    "ticket_code": "TKT-2025-00001",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "company_name": "Tech Solutions Inc.",
    "created_by_user_id": "user-uuid-123",
    "created_by_name": "Juan Pérez",
    "created_by_email": "juan@email.com",
    "category_id": "cat-uuid-1",
    "category_name": "Soporte Técnico",
    "title": "No puedo acceder al sistema",
    "initial_description": "Cuando intento hacer login me sale error 500...",
    "status": "resolved",
    "owner_agent_id": "agent-uuid-456",
    "owner_agent_name": "María González",
    "last_response_author_type": "agent",
    "created_at": "2025-11-11T10:00:00Z",
    "updated_at": "2025-11-11T15:00:00Z",
    "first_response_at": "2025-11-11T10:30:00Z",
    "resolved_at": "2025-11-11T15:00:00Z",
    "closed_at": null,
    "responses_count": 5,
    "attachments_count": 1
  }
}
```

**Interpretación**:
- El agente María marcó manualmente el ticket como resuelto
- `resolved_at` se marcó con el timestamp de resolución
- Campo `last_response_author_type: "agent"` (probablemente la última respuesta fue del agente)
- Cliente puede cerrar el ticket o reabrirlo si el problema persiste
- Sistema auto-cerrará el ticket en 7 días si no hay actividad

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
    "last_response_author_type": "agent",
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
    "last_response_author_type": "none",
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

**⚠️ Side Effects Automáticos (Triggers PostgreSQL)**:

**Si author_type = 'agent' Y es la PRIMERA respuesta**:
1. `owner_agent_id` = Se asigna al agente que respondió
2. `status` = Cambia de `open` → `pending`
3. `first_response_at` = Se marca con timestamp actual
4. `last_response_author_type` = Se actualiza a `'agent'`

**Si author_type = 'user' Y status = 'pending'**:
1. `status` = Cambia de `pending` → `open`
2. `last_response_author_type` = Se actualiza a `'user'`
3. **IMPORTANTE**: `owner_agent_id` NO se remueve (se mantiene)

**SIEMPRE** (en cada respuesta):
- `last_response_author_type` = Se actualiza con el tipo de autor (`'user'` o `'agent'`)
- `updated_at` = Se actualiza con timestamp actual

**Response 201 Created** (Ejemplo: Primera respuesta de agente):
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
      "first_response_at": "2025-11-09T15:00:00Z",
      "last_response_author_type": "agent"
    }
  }
}
```

**Response 201 Created** (Ejemplo: Cliente responde a ticket pending):
```json
{
  "success": true,
  "message": "Respuesta agregada exitosamente",
  "data": {
    "id": "resp-uuid-new-2",
    "ticket_id": "tkt-uuid-1",
    "author_id": "user-uuid-1",
    "author_name": "Juan Pérez",
    "author_type": "user",
    "response_content": "Gracias, pero sigo sin poder acceder...",
    "created_at": "2025-11-09T16:00:00Z",
    "ticket_updated": {
      "owner_agent_id": "agent-uuid-1",
      "status": "open",
      "last_response_author_type": "user"
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

### Ciclo de Vida del Ticket (Modelo de 4 Estados)

```
┌─────────────────────────────────────────────┐
│  OPEN (Nuevo)                               │
│  - Ticket recién creado                     │
│  - owner_agent_id: NULL                     │
│  - last_response_author_type: 'none'        │
└────────────────┬────────────────────────────┘
                 │
                 │ (PRIMER agente responde)
                 │ [TRIGGER AUTO-ASSIGNMENT]
                 ▼
┌─────────────────────────────────────────────┐
│  PENDING (Esperando cliente)                │
│  - Agente respondió                         │
│  - owner_agent_id: {agente-uuid}            │
│  - last_response_author_type: 'agent'       │
└────────────────┬────────────────────────────┘
                 │                          ▲
                 │ (Cliente responde)       │
                 │ [TRIGGER STATUS]         │ (Agente responde)
                 ▼                          │
┌─────────────────────────────────────────────┐
│  OPEN (Cliente respondió)                   │
│  - Cliente respondió a PENDING              │
│  - owner_agent_id: {agente-uuid} ← MANTIENE │
│  - last_response_author_type: 'user'        │
└────────────────┬────────────────────────────┘
                 │
                 │ (Agente marca como resuelto)
                 │ [MANUAL]
                 ▼
┌─────────────────────────────────────────────┐
│  RESOLVED (Problema resuelto)               │
│  - Agente resolvió el problema              │
│  - resolved_at: {timestamp}                 │
│  - Cliente puede cerrar o reabrir           │
└────────────────┬────────────────────────────┘
                 │
                 │ (Manual o Auto-close 7 días)
                 │ [CRON JOB]
                 ▼
┌─────────────────────────────────────────────┐
│  CLOSED (Cerrado definitivamente)           │
│  - Ticket finalizado                        │
│  - closed_at: {timestamp}                   │
│  - Historial permanente                     │
└─────────────────────────────────────────────┘
```

### Triggers Automáticos PostgreSQL

#### 1. Trigger: Auto-Assignment (OPEN → PENDING)

**Condición**: `author_type = 'agent'` Y `owner_agent_id IS NULL`

```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- Cuando el PRIMER agente responde a un ticket nuevo

UPDATE ticketing.tickets
SET
    owner_agent_id = NEW.author_id,
    first_response_at = NOW(),
    status = 'pending',
    last_response_author_type = 'agent',
    updated_at = NOW()
WHERE id = NEW.ticket_id
AND owner_agent_id IS NULL;
```

**Efecto**: El ticket se asigna automáticamente al agente que respondió primero.

---

#### 2. Trigger: Status Change (PENDING → OPEN)

**Condición**: `author_type = 'user'` Y `status = 'pending'`

```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- Cuando el cliente responde a un ticket en PENDING

UPDATE ticketing.tickets
SET
    status = 'open',
    last_response_author_type = 'user',
    updated_at = NOW()
WHERE id = NEW.ticket_id
AND status = 'pending';
```

**Efecto**: El ticket vuelve a estado OPEN, pero **mantiene** el `owner_agent_id`.

---

#### 3. Trigger: Update last_response_author_type

**Condición**: SIEMPRE (en cada respuesta)

```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- Actualiza quién respondió último

UPDATE ticketing.tickets
SET
    last_response_author_type = NEW.author_type,
    updated_at = NOW()
WHERE id = NEW.ticket_id;
```

**Efecto**: Mantiene sincronizado quién fue el último en responder.

---

### Diferencias Importantes: OPEN Nuevo vs OPEN (Cliente Respondió)

Ambos tienen `status = 'open'`, pero se diferencian por otros campos:

| Campo | OPEN Nuevo | OPEN (Cliente Respondió) |
|-------|------------|--------------------------|
| `owner_agent_id` | `NULL` | `{agente-uuid}` (asignado) |
| `last_response_author_type` | `'none'` | `'user'` |
| `first_response_at` | `NULL` | `{timestamp}` |
| **Significado** | Ticket sin asignar en cola de entrada | Ticket asignado esperando respuesta del agente |
| **Visible para** | Todos los agentes | El agente asignado específicamente |

**Consultas para diferenciarlos**:

```sql
-- OPEN Nuevo (cola de entrada)
WHERE status = 'open' AND owner_agent_id IS NULL

-- OPEN Cliente respondió (requiere atención del agente)
WHERE status = 'open'
  AND owner_agent_id IS NOT NULL
  AND last_response_author_type = 'user'

-- OPEN Ticket asignado pero sin respuestas aún (raro, posible con asignación manual)
WHERE status = 'open'
  AND owner_agent_id IS NOT NULL
  AND last_response_author_type = 'none'
```

---

### Auto-Close (Cron Job)

**Ejecutar diariamente** a las 00:00 UTC:

```php
// Cerrar automáticamente tickets resueltos después de 7 días

Ticket::where('status', 'resolved')
    ->where('resolved_at', '<', now()->subDays(7))
    ->update([
        'status' => 'closed',
        'closed_at' => now()
    ]);
```

**Lógica**:
- Solo afecta tickets en estado `resolved`
- Si `resolved_at` tiene más de 7 días
- Cambia automáticamente a `closed`
- Marca `closed_at` con timestamp actual

---

### Notas Importantes sobre Transiciones

1. **owner_agent_id NUNCA se remueve automáticamente**:
   - Una vez asignado, permanece hasta que se reasigne manualmente
   - Incluso cuando el ticket vuelve a OPEN (cliente respondió)

2. **last_response_author_type es crítico para la UI**:
   - Permite distinguir quién debe actuar
   - Combinado con `status` determina prioridad
   - Actualizado automáticamente por triggers

3. **first_response_at solo se marca UNA vez**:
   - Cuando el primer agente responde
   - No se actualiza en respuestas posteriores
   - Útil para calcular tiempo de primera respuesta (SLA)

4. **Transiciones permitidas**:
   ```
   open → pending (trigger automático: agente responde)
   pending → open (trigger automático: cliente responde)
   pending → resolved (manual: agente marca como resuelto)
   open → resolved (manual: agente marca como resuelto)
   resolved → closed (manual o auto-close 7 días)
   resolved → open (manual: cliente/agente reabre)
   closed → open (manual: cliente/agente reabre dentro de 30 días)
   ```

---

## ✅ RESUMEN CRÍTICO - ALINEACIÓN CON BASE DE DATOS

Esta sección documenta la alineación completa con el **Modelado final de base de datos.txt v10.0**.

### Campos de la Tabla `tickets`

| Campo BD | Tipo | Descripción | Valores Posibles | Actualización |
|----------|------|-------------|------------------|---------------|
| `id` | uuid | ID único del ticket | UUID v4 | Al crear |
| `ticket_code` | varchar(20) | Código legible (TKT-2025-00001) | Formato: TKT-YYYY-NNNNN | Auto-generado |
| `company_id` | uuid | ID de la empresa | UUID válido | Al crear |
| `created_by_user_id` | uuid | ID del usuario que creó el ticket | UUID válido | Al crear |
| `category_id` | uuid | ID de la categoría | UUID válido | Editable |
| `title` | varchar(255) | Título del ticket | 5-255 caracteres | Editable (si open) |
| `initial_description` | text | Descripción inicial | 10-5000 caracteres | Al crear |
| `status` | varchar(20) | Estado actual | `'open'`, `'pending'`, `'resolved'`, `'closed'` | Automático + Manual |
| `owner_agent_id` | uuid \| null | ID del agente asignado | UUID válido o NULL | Automático (trigger) + Reasignación |
| `last_response_author_type` | varchar(20) | Quién respondió último | `'none'`, `'user'`, `'agent'` | Automático (trigger) |
| `created_at` | timestamp | Fecha de creación | ISO 8601 | Al crear |
| `updated_at` | timestamp | Última actualización | ISO 8601 | Cada cambio |
| `first_response_at` | timestamp \| null | Primera respuesta de agente | ISO 8601 o NULL | Automático (trigger, una sola vez) |
| `resolved_at` | timestamp \| null | Fecha de resolución | ISO 8601 o NULL | Manual (agente) |
| `closed_at` | timestamp \| null | Fecha de cierre | ISO 8601 o NULL | Manual + Auto-close |

### Estados del Ticket (Enum: TicketStatus)

```php
enum TicketStatus: string
{
    case OPEN = 'open';          // Ticket nuevo O cliente respondió
    case PENDING = 'pending';    // Agente respondió, esperando cliente
    case RESOLVED = 'resolved';  // Problema resuelto
    case CLOSED = 'closed';      // Ticket cerrado
}
```

### Campo Crítico: last_response_author_type

**Tipo BD**: `VARCHAR(20) NOT NULL DEFAULT 'none'`

**Valores permitidos**:
- `'none'` → Ticket recién creado, sin respuestas
- `'user'` → Cliente respondió último
- `'agent'` → Agente respondió último

**Actualización**: Automática vía trigger PostgreSQL (cada vez que se agrega una respuesta)

**Uso en API**:
- Filtro query param: `?last_response_author_type=user`
- Campo en response JSON: `"last_response_author_type": "agent"`
- Combinado con `status` para determinar prioridad en UI

### Reglas de Integridad Referencial

1. **company_id** → FOREIGN KEY a `companies.id`
   - ON DELETE: No permitido si hay tickets activos
   - Validación: Empresa debe existir

2. **created_by_user_id** → FOREIGN KEY a `users.id`
   - ON DELETE: No permitido
   - Validación: Usuario debe existir

3. **category_id** → FOREIGN KEY a `ticket_categories.id`
   - ON DELETE: No permitido si hay tickets usando la categoría
   - Validación: Categoría debe existir y estar activa

4. **owner_agent_id** → FOREIGN KEY a `users.id` (WHERE role = 'AGENT')
   - ON DELETE: SET NULL (si agente se elimina, ticket queda sin asignar)
   - Validación: Usuario debe tener rol AGENT

### Índices Críticos para Performance

```sql
-- Índice compuesto para query más común (agente: mis tickets)
CREATE INDEX idx_tickets_agent_status ON tickets(owner_agent_id, status);

-- Índice compuesto para cola de entrada
CREATE INDEX idx_tickets_unassigned ON tickets(company_id, status)
WHERE owner_agent_id IS NULL;

-- Índice para filtros de cliente
CREATE INDEX idx_tickets_creator ON tickets(created_by_user_id, status);

-- Índice para last_response_author_type (nuevo campo)
CREATE INDEX idx_tickets_last_response ON tickets(last_response_author_type, status);

-- Índice para auto-close (cron job)
CREATE INDEX idx_tickets_resolved ON tickets(status, resolved_at)
WHERE status = 'resolved';
```

### Validaciones Críticas Backend

1. **Al crear ticket**:
   - `status` DEBE iniciar en `'open'`
   - `owner_agent_id` DEBE ser `NULL`
   - `last_response_author_type` DEBE ser `'none'`

2. **Trigger auto-assignment**:
   - Solo se ejecuta si `owner_agent_id IS NULL`
   - Solo se ejecuta si `author_type = 'agent'`
   - Actualiza 4 campos: `owner_agent_id`, `status`, `first_response_at`, `last_response_author_type`

3. **Trigger status change**:
   - Solo se ejecuta si `status = 'pending'`
   - Solo se ejecuta si `author_type = 'user'`
   - `owner_agent_id` NO se modifica (se mantiene)

4. **Query param `owner_agent_id=null`**:
   - Backend debe interpretar literal string `"null"` como condición SQL `IS NULL`
   - NO confundir con valor NULL de JSON

### Consultas SQL Equivalentes a Query Params

**Ejemplo 1**: `?status=open&owner_agent_id=null`
```sql
SELECT * FROM tickets
WHERE status = 'open'
  AND owner_agent_id IS NULL;
```

**Ejemplo 2**: `?status=open&owner_agent_id=me&last_response_author_type=user`
```sql
SELECT * FROM tickets
WHERE status = 'open'
  AND owner_agent_id = :current_agent_id
  AND last_response_author_type = 'user';
```

**Ejemplo 3**: `?created_by=me&status=pending,resolved`
```sql
SELECT * FROM tickets
WHERE created_by_user_id = :current_user_id
  AND status IN ('pending', 'resolved');
```

### Diferencias Clave con Versiones Anteriores

| Aspecto | Versión Anterior | Versión Actual (v10.0) |
|---------|------------------|------------------------|
| **Estados** | 3 estados (open, in_progress, closed) | 4 estados (open, pending, resolved, closed) |
| **Campo tracking** | NO existía | `last_response_author_type` (nuevo) |
| **Auto-asignación** | Manual | Automática vía trigger |
| **Transición PENDING→OPEN** | NO existía | Automática cuando cliente responde |
| **owner_agent_id** | Se removía al reabrir | SE MANTIENE siempre |
| **Query param owner_agent_id** | Solo UUIDs | Soporta `null`, `me`, UUID |
| **Query param created_by** | `created_by_user_id` | Simplificado a `created_by` |

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
