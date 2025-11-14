# 🎫 TICKET MANAGEMENT API - DOCUMENTACIÓN COMPLETA DEFINITIVA

> **Sistema**: Helpdesk Multi-Tenant
> **Feature**: Ticket Management
> **Versión**: 1.0 Final - DEFINITIVA
> **Base URL**: `/api`
> **Autenticación**: Bearer Token (JWT)
> **Última Actualización**: 13 Noviembre 2025

---

## 📋 TABLA DE CONTENIDOS

1. [Arquitectura y Filosofía](#arquitectura-y-filosofía)
2. [Autenticación JWT](#autenticación-jwt)
3. [Estados y Transiciones (State Machine)](#estados-y-transiciones)
4. [Índice Completo de Endpoints](#índice-completo-de-endpoints)
5. [API - Categorías (4 endpoints)](#api---categorías)
6. [API - Tickets CRUD (5 endpoints)](#api---tickets-crud)
7. [API - Tickets Actions (4 endpoints)](#api---tickets-actions)
8. [API - Respuestas (4 endpoints)](#api---respuestas)
9. [API - Adjuntos (3 endpoints)](#api---adjuntos)
10. [API - Calificaciones (3 endpoints)](#api---calificaciones)
11. [Reglas de Negocio Críticas](#reglas-de-negocio-críticas)
12. [Permisos y Matriz de Autorización](#permisos-y-matriz-de-autorización)
13. [Códigos de Error](#códigos-de-error)
14. [Validaciones Completas](#validaciones-completas)

---

## 🏗️ ARQUITECTURA Y FILOSOFÍA

### Principios de Diseño

#### 1. Auto-Assignment Automático (Trigger PostgreSQL)
- El **primer agente** que responde queda asignado automáticamente
- Trigger ejecuta DESPUÉS de INSERT en `ticket_responses`
- Condición: `author_type = 'agent'` AND `owner_agent_id IS NULL`
- Cambia `status` de `open` → `pending` automáticamente
- Marca `first_response_at` con timestamp

#### 2. Separación de Conversaciones
- **Responses**: Conversación pública (cliente ↔ agente)
- **Internal Notes**: Colaboración privada (agente ↔ agente) - NO en MVP

#### 3. Attachments Flexibles
- Escenario 1: Al crear ticket → `response_id = NULL`
- Escenario 2: En respuesta específica → `response_id = UUID`

#### 4. Company Context por Rol
- **USER**: DEBE especificar `company_id` (empresa debe existir)
- **AGENT/ADMIN**: Inferido automáticamente del JWT token

#### 5. Stateless Authentication
- JWT con auto-refresh tokens
- Multi-tab synchronization via BroadcastChannel
- Persistent storage con IndexedDB
- Session keepalive mechanism

#### 6. Middleware y Autorización
- **Middlewares Reutilizados**: `AuthenticateJwt`, `EnsureUserHasRole`
- **NO middlewares custom**: No usar `EnsureTicketOwner` ni `EnsureAgentRole`
- **Laravel Policies**: Autorización granular por recurso (TicketPolicy, ResponsePolicy, etc.)
- **Ejemplo de Ruta**:
  ```php
  Route::post('/tickets/{ticket}/assign')
      ->middleware(['auth.jwt', 'role:AGENT']);
  ```
- **Contexto Multi-Tenant**: Siempre usar `JWTHelper::getUserId()` y `JWTHelper::getCompanyId()` (NO `auth()->user()`)

---

## 🔐 AUTENTICACIÓN JWT

### Estructura del Token

```json
{
  "sub": "550e8400-e29b-41d4-a716-446655440001",
  "email": "juan.perez@example.com",
  "role": "USER",
  "company_id": "company-uuid-here",
  "iat": 1699000000,
  "exp": 1699003600
}
```

### Campos del JWT

| Campo | Tipo | Descripción | Presente en |
|-------|------|-------------|-------------|
| `sub` | UUID | ID del usuario autenticado | Todos los roles |
| `email` | string | Email del usuario | Todos los roles |
| `role` | enum | `USER`, `AGENT`, `COMPANY_ADMIN`, `PLATFORM_ADMIN` | Todos los roles |
| `company_id` | UUID | ID de la empresa del agente/admin | Solo AGENT/ADMIN |
| `iat` | timestamp | Issued at | Todos los roles |
| `exp` | timestamp | Expiration | Todos los roles |

### Headers Requeridos

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json
Accept: application/json
```

Para uploads (multipart):
```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: multipart/form-data
Accept: application/json
```

---

## 🔄 ESTADOS Y TRANSICIONES

### Modelo de 4 Estados (State Machine)

```
OPEN ←→ PENDING → RESOLVED → CLOSED
  ↑___________________|
```

| Estado | Significado | Quién Espera Acción | Transiciones Posibles |
|--------|-------------|---------------------|------------------------|
| **OPEN** | Ticket nuevo O cliente respondió | **AGENTE** | → PENDING (agente responde)<br>→ RESOLVED (agente marca) |
| **PENDING** | Agente respondió | **CLIENTE** | → OPEN (cliente responde)<br>→ RESOLVED (agente marca) |
| **RESOLVED** | Problema resuelto | **CLIENTE/SISTEMA** | → OPEN (reabrir)<br>→ CLOSED (manual o auto 7d) |
| **CLOSED** | Ticket cerrado | **Nadie** | → OPEN (reabrir dentro 30d) |

### Triggers Automáticos PostgreSQL

#### Trigger 1: Auto-Assignment + Status Change (OPEN → PENDING)

```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- Condición: author_type = 'agent' AND owner_agent_id IS NULL

CREATE TRIGGER assign_ticket_owner_after_agent_response
AFTER INSERT ON ticketing.ticket_responses
FOR EACH ROW
WHEN (NEW.author_type = 'agent')
EXECUTE FUNCTION assign_ticket_owner();

-- Función del trigger
CREATE OR REPLACE FUNCTION assign_ticket_owner()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE ticketing.tickets
    SET
        owner_agent_id = NEW.author_id,
        first_response_at = NOW(),
        status = 'pending',
        last_response_author_type = 'agent',
        updated_at = NOW()
    WHERE id = NEW.ticket_id
    AND owner_agent_id IS NULL;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

**Qué hace:**
1. Se asigna el ticket al agente que respondió (`owner_agent_id`)
2. Cambia status de `open` → `pending`
3. Marca `first_response_at` (solo la primera vez)
4. Actualiza `last_response_author_type` a `'agent'`

**Condiciones:**
- Solo si `owner_agent_id IS NULL` (primera asignación)
- Solo si `author_type = 'agent'`

---

#### Trigger 2: Status Change (PENDING → OPEN)

```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- Condición: author_type = 'user' AND status = 'pending'

CREATE TRIGGER change_pending_to_open_after_user_response
AFTER INSERT ON ticketing.ticket_responses
FOR EACH ROW
WHEN (NEW.author_type = 'user')
EXECUTE FUNCTION change_pending_to_open();

-- Función del trigger
CREATE OR REPLACE FUNCTION change_pending_to_open()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE ticketing.tickets
    SET
        status = 'open',
        last_response_author_type = 'user',
        updated_at = NOW()
    WHERE id = NEW.ticket_id
    AND status = 'pending';
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

**Qué hace:**
1. Cambia status de `pending` → `open`
2. Actualiza `last_response_author_type` a `'user'`
3. **IMPORTANTE**: `owner_agent_id` SE MANTIENE (no se remueve)

**Condiciones:**
- Solo si `status = 'pending'`
- Solo si `author_type = 'user'`

---

#### Trigger 3: Update last_response_author_type (Siempre)

```sql
-- Se ejecuta DESPUÉS de INSERT en ticket_responses
-- SIEMPRE actualiza el campo last_response_author_type

CREATE TRIGGER update_last_response_author_type
AFTER INSERT ON ticketing.ticket_responses
FOR EACH ROW
EXECUTE FUNCTION update_last_response_author();

-- Función del trigger
CREATE OR REPLACE FUNCTION update_last_response_author()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE ticketing.tickets
    SET
        last_response_author_type = NEW.author_type,
        updated_at = NOW()
    WHERE id = NEW.ticket_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

**Qué hace:**
1. Actualiza `last_response_author_type` con el tipo del autor
2. Valores posibles: `'none'`, `'user'`, `'agent'`

**Condiciones:**
- SIEMPRE se ejecuta en cada INSERT de `ticket_responses`

---

### Campo Crítico: last_response_author_type

Campo transversal que indica quién respondió último:

| Valor | Significado | Cuándo se asigna |
|-------|-------------|------------------|
| `'none'` | Sin respuestas aún | Ticket recién creado (default) |
| `'user'` | Cliente respondió último | Cliente agrega respuesta |
| `'agent'` | Agente respondió último | Agente agrega respuesta |

#### ⚠️ CRÍTICO: Cuándo NO cambia este campo

**El campo `last_response_author_type` SOLO se actualiza vía triggers cuando se inserta una respuesta.**

**❌ NO se actualiza en las siguientes acciones:**

| Acción | Endpoint | Campo persiste | Razón |
|--------|----------|----------------|-------|
| Resolver ticket | `POST /tickets/:code/resolve` | ✅ Sí | No es una respuesta |
| Cerrar ticket | `POST /tickets/:code/close` | ✅ Sí | No es una respuesta |
| Reabrir ticket | `POST /tickets/:code/reopen` | ✅ Sí | No es una respuesta |
| Reasignar ticket | `POST /tickets/:code/assign` | ✅ Sí | No es una respuesta |
| Actualizar ticket | `PUT /tickets/:code` | ✅ Sí | Actualización de metadata |
| Editar respuesta | `PUT /tickets/:code/responses/:id` | ✅ Sí | Solo INSERT activa trigger |
| Eliminar respuesta | `DELETE /tickets/:code/responses/:id` | ✅ Sí | Solo INSERT activa trigger |

#### Uso en UI

Combinar con `status` para determinar estados visuales:

| status | last_response_author_type | Interpretación UI |
|--------|---------------------------|-------------------|
| `open` | `none` | "Ticket nuevo sin respuestas" |
| `open` | `user` | "Cliente respondió, necesita atención" ⚠️ |
| `open` | `agent` | "Agente respondió antes, cliente volvió a responder" |
| `pending` | `agent` | "Esperando respuesta del cliente" |
| `pending` | `user` | "Cliente respondió (trigger cambiará a open)" |
| `resolved` | `agent` | "Agente resolvió" |
| `resolved` | `user` | "Cliente cerró sin quejas" |
| `closed` | `any` | "Ticket cerrado" |

#### Ejemplo de Flujo Completo

```
1. Cliente crea ticket
   → status: 'open'
   → last_response_author_type: 'none'

2. Agente responde (primera vez)
   → status: 'pending' [TRIGGER 1]
   → last_response_author_type: 'agent' [TRIGGER 3]
   → owner_agent_id: UUID del agente [TRIGGER 1]

3. Cliente responde
   → status: 'open' [TRIGGER 2]
   → last_response_author_type: 'user' [TRIGGER 3]
   → owner_agent_id: UUID (SIN CAMBIOS)

4. Agente RESUELVE
   → status: 'resolved' [MANUAL]
   → last_response_author_type: 'user' (SIN CAMBIOS ⭐)

5. Cliente REABRE
   → status: 'pending' [MANUAL]
   → last_response_author_type: 'user' (SIN CAMBIOS ⭐)

6. Agente responde
   → status: 'pending' (ya estaba)
   → last_response_author_type: 'agent' [TRIGGER 3]

7. Agente CIERRA
   → status: 'closed' [MANUAL]
   → last_response_author_type: 'agent' (SIN CAMBIOS ⭐)
```

---

## 📋 ÍNDICE COMPLETO DE ENDPOINTS

### Total: 23 endpoints activos en MVP

#### 🏷️ Categorías (4 endpoints)
1. `GET /tickets/categories` - Listar categorías
2. `POST /tickets/categories` - Crear categoría
3. `PUT /tickets/categories/:id` - Actualizar categoría
4. `DELETE /tickets/categories/:id` - Eliminar categoría

#### 🎫 Tickets CRUD (5 endpoints)
5. `GET /tickets` - Listar tickets
6. `GET /tickets/:code` - Obtener detalle de ticket
7. `POST /tickets` - Crear ticket
8. `PUT /tickets/:code` - Actualizar ticket
9. `DELETE /tickets/:code` - Eliminar ticket

#### 🔄 Tickets Actions (4 endpoints)
10. `POST /tickets/:code/resolve` - Marcar como resuelto
11. `POST /tickets/:code/close` - Cerrar ticket
12. `POST /tickets/:code/reopen` - Reabrir ticket
13. `POST /tickets/:code/assign` - Reasignar a otro agente

#### 💬 Respuestas (4 endpoints)
14. `GET /tickets/:code/responses` - Listar respuestas
15. `POST /tickets/:code/responses` - Agregar respuesta
16. `PUT /tickets/:code/responses/:id` - Editar respuesta
17. `DELETE /tickets/:code/responses/:id` - Eliminar respuesta

#### 📎 Adjuntos (3 endpoints)
18. `GET /tickets/:code/attachments` - Listar adjuntos
19. `POST /tickets/:code/attachments` - Subir adjunto
20. `DELETE /tickets/:code/attachments/:id` - Eliminar adjunto

#### ⭐ Calificaciones (3 endpoints)
21. `POST /tickets/:code/rating` - Calificar ticket
22. `PUT /tickets/:code/rating` - Actualizar calificación
23. `GET /tickets/:code/rating` - Ver calificación

---

## 🏷️ API - CATEGORÍAS

### 1. Listar Categorías

```http
GET /api/tickets/categories
Authorization: Bearer {token}
```

#### Query Parameters

| Parámetro | Tipo | Requerido | Descripción | Ejemplo |
|-----------|------|-----------|-------------|---------|
| `company_id` | UUID | ✅ | ID de la empresa | `550e8400-e29b-41d4-a716-446655440001` |
| `is_active` | boolean | ❌ | Filtrar por estado activo/inactivo | `true`, `false` |

#### Permisos
- ✅ **USER**: Puede listar categorías de cualquier empresa
- ✅ **AGENT**: Puede listar categorías de su empresa
- ✅ **COMPANY_ADMIN**: Puede listar categorías de su empresa

#### Response 200 OK

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
      "active_tickets_count": 12,
      "created_at": "2024-10-01T10:00:00Z",
      "updated_at": "2024-10-01T10:00:00Z"
    },
    {
      "id": "cat-uuid-2",
      "company_id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Facturación",
      "description": "Consultas sobre pagos y facturas",
      "is_active": true,
      "active_tickets_count": 5,
      "created_at": "2024-10-01T10:05:00Z",
      "updated_at": "2024-10-01T10:05:00Z"
    }
  ],
  "meta": {
    "total": 2
  }
}
```

#### Response 401 Unauthorized

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Token de autenticación no válido o expirado"
  }
}
```

---

### 2. Crear Categoría

```http
POST /api/tickets/categories
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body

```json
{
  "name": "Reportes y Analíticas",
  "description": "Consultas sobre reportes y métricas del sistema",
  "is_active": true
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `name` | string | ✅ | min:3, max:100, unique per company | Nombre de la categoría |
| `description` | string | ❌ | max:500 | Descripción (nullable) |
| `is_active` | boolean | ❌ | boolean | Estado activo (default: true) |

**NOTA IMPORTANTE**: El campo `company_id` se infiere automáticamente del JWT token para AGENT/ADMIN. NO se envía en el body.

#### Validaciones Detalladas

**name:**
- ✅ Requerido
- ✅ Mínimo 3 caracteres
- ✅ Máximo 100 caracteres
- ✅ Único por empresa (puede repetirse en diferentes empresas)
- ❌ Error 422 si falta
- ❌ Error 422 si ya existe en la empresa

**description:**
- ⭕ Opcional
- ✅ Máximo 500 caracteres
- ✅ Puede ser null
- ❌ Error 422 si excede 500 caracteres

#### Permisos
- ❌ **USER**: No puede crear categorías
- ❌ **AGENT**: No puede crear categorías
- ✅ **COMPANY_ADMIN**: Puede crear categorías

#### Response 201 Created

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
    "created_at": "2025-11-13T14:00:00Z",
    "updated_at": "2025-11-13T14:00:00Z"
  }
}
```

#### Response 403 Forbidden

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "No tienes permisos para crear categorías. Solo COMPANY_ADMIN puede crearlas."
  }
}
```

#### Response 422 Validation Error

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "name": [
      "El campo name es requerido",
      "El nombre debe tener al menos 3 caracteres",
      "Ya existe una categoría con ese nombre en esta empresa"
    ],
    "description": [
      "La descripción no puede exceder 500 caracteres"
    ]
  }
}
```

---

### 3. Actualizar Categoría

```http
PUT /api/tickets/categories/:id
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body (parcial)

```json
{
  "name": "Reportes, Analíticas y Métricas",
  "is_active": false
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `name` | string | ❌ | min:3, max:100, unique per company | Nuevo nombre |
| `description` | string | ❌ | max:500 | Nueva descripción |
| `is_active` | boolean | ❌ | boolean | Nuevo estado |

**NOTA**: Actualización parcial permitida. Solo enviar campos que se desean modificar.

#### Permisos
- ❌ **USER**: No puede actualizar categorías
- ❌ **AGENT**: No puede actualizar categorías
- ✅ **COMPANY_ADMIN**: Puede actualizar categorías de su empresa

#### Response 200 OK

```json
{
  "success": true,
  "message": "Categoría actualizada exitosamente",
  "data": {
    "id": "cat-uuid-1",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "name": "Reportes, Analíticas y Métricas",
    "description": "Consultas sobre reportes y métricas del sistema",
    "is_active": false,
    "created_at": "2024-10-01T10:00:00Z",
    "updated_at": "2025-11-13T15:00:00Z"
  }
}
```

#### Response 403 Forbidden

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "No tienes permisos para actualizar esta categoría"
  }
}
```

#### Response 404 Not Found

```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Categoría no encontrada"
  }
}
```

---

### 4. Eliminar Categoría

```http
DELETE /api/tickets/categories/:id
Authorization: Bearer {token}
```

#### Permisos
- ❌ **USER**: No puede eliminar categorías
- ❌ **AGENT**: No puede eliminar categorías
- ✅ **COMPANY_ADMIN**: Puede eliminar categorías de su empresa

#### Reglas de Negocio
- ✅ Se puede eliminar si NO hay tickets activos (open, pending)
- ✅ Se puede eliminar si SOLO hay tickets cerrados
- ❌ NO se puede eliminar si hay tickets activos

#### Response 200 OK

```json
{
  "success": true,
  "message": "Categoría eliminada exitosamente"
}
```

#### Response 409 Conflict (Categoría en uso)

```json
{
  "success": false,
  "error": {
    "code": "CATEGORY_IN_USE",
    "message": "No se puede eliminar la categoría porque hay 15 tickets activos usándola",
    "details": {
      "active_tickets_count": 15,
      "open_count": 8,
      "pending_count": 7
    }
  }
}
```

---

## 🎫 API - TICKETS CRUD

### 5. Listar Tickets

```http
GET /api/tickets
Authorization: Bearer {token}
```

#### Query Parameters Completos

| Parámetro | Tipo | Default | Descripción | Valores Posibles |
|-----------|------|---------|-------------|------------------|
| `company_id` | UUID | - | ID de la empresa (requerido para USER) | UUID válido |
| `status` | enum/array | - | Filtrar por estado(s) | `open`, `pending`, `resolved`, `closed` (separar por coma) |
| `category_id` | UUID | - | Filtrar por categoría | UUID válido |
| `owner_agent_id` | string | - | Filtrar por agente asignado | `null`, `me`, `{UUID}` |
| `created_by` | string | - | Filtrar por creador | `me`, `{UUID}` |
| `last_response_author_type` | enum | - | Filtrar por quien respondió último | `none`, `user`, `agent` |
| `search` | string | - | Búsqueda en título y descripción | Cualquier texto |
| `created_after` | datetime | - | Creados después de fecha | ISO 8601 format |
| `created_before` | datetime | - | Creados antes de fecha | ISO 8601 format |
| `sort` | string | `-created_at` | Ordenar por campo | `-created_at`, `-updated_at`, `status` |
| `page` | int | 1 | Número de página | >= 1 |
| `per_page` | int | 20 | Items por página | 1-100 |

#### Detalle de Query Parameters Especiales

##### status (Múltiples valores)
```http
# Formato 1: Separados por coma
GET /tickets?status=open,pending

# Formato 2: Múltiples parámetros (alternativa)
GET /tickets?status=open&status=pending

# Ambos formatos son equivalentes
```

##### owner_agent_id (Valores especiales)

```http
# Tickets sin agente asignado (literal string "null")
GET /tickets?owner_agent_id=null

# Mis tickets asignados
GET /tickets?owner_agent_id=me

# Tickets de agente específico
GET /tickets?owner_agent_id=550e8400-e29b-41d4-a716-446655440001
```

**IMPLEMENTACIÓN BACKEND**:
```php
if ($request->has('owner_agent_id')) {
    if ($request->owner_agent_id === 'null') {
        // String literal "null"
        $query->whereNull('owner_agent_id');
    } elseif ($request->owner_agent_id === 'me') {
        // Resolver a usuario autenticado
        $query->where('owner_agent_id', auth()->id());
    } else {
        // UUID específico
        $query->where('owner_agent_id', $request->owner_agent_id);
    }
}
```

##### created_by (Valor especial)

```http
# Mis tickets creados
GET /tickets?created_by=me

# Tickets de usuario específico
GET /tickets?created_by=550e8400-e29b-41d4-a716-446655440001
```

##### last_response_author_type

```http
# Tickets sin respuestas
GET /tickets?last_response_author_type=none

# Tickets donde cliente respondió último
GET /tickets?last_response_author_type=user

# Tickets donde agente respondió último
GET /tickets?last_response_author_type=agent
```

#### Reglas de Visibilidad por Rol

| Rol | Visibilidad | Filtro Automático |
|-----|-------------|-------------------|
| **USER** | Solo sus propios tickets | `created_by_user_id = auth()->id()` |
| **AGENT** | Todos los tickets de su empresa | `company_id = auth()->user()->company_id` |
| **COMPANY_ADMIN** | Todos los tickets de su empresa | `company_id = auth()->user()->company_id` |

#### Ejemplos de Uso - Casos Reales

**Caso 1: Tickets NUEVOS (sin asignar)**
```http
GET /tickets?status=open&owner_agent_id=null&last_response_author_type=none
```
**Descripción**: Cola de entrada. Todos los agentes pueden tomar estos tickets.

---

**Caso 2: MIS tickets que necesitan atención**
```http
GET /tickets?status=open&owner_agent_id=me&last_response_author_type=user
```
**Descripción**: Tickets asignados a mí donde el cliente acaba de responder.

---

**Caso 3: Tickets esperando respuesta del cliente**
```http
GET /tickets?status=pending&owner_agent_id=me
```
**Descripción**: Mis tickets donde respondí y espero respuesta del cliente.

---

**Caso 4: Mis tickets como CLIENTE**
```http
GET /tickets?created_by=me&status=pending,resolved,closed
```
**Descripción**: Ver historial de mis tickets como cliente.

---

#### Permisos
- ✅ **USER**: Puede listar SOLO sus propios tickets
- ✅ **AGENT**: Puede listar todos los tickets de su empresa
- ✅ **COMPANY_ADMIN**: Puede listar todos los tickets de su empresa

#### Response 200 OK

```json
{
  "success": true,
  "data": [
    {
      "id": "ticket-uuid-1",
      "ticket_code": "TKT-2025-00001",
      "company_id": "550e8400-e29b-41d4-a716-446655440001",
      "category_id": "cat-uuid-1",
      "title": "Error al exportar reporte mensual",
      "status": "open",
      "last_response_author_type": "user",
      "owner_agent_id": "agent-uuid-1",
      "created_by_user_id": "user-uuid-1",
      "created_at": "2025-11-10T10:00:00Z",
      "updated_at": "2025-11-12T14:30:00Z",
      "first_response_at": "2025-11-10T10:15:00Z",
      "resolved_at": null,
      "closed_at": null,
      "created_by_user": {
        "id": "user-uuid-1",
        "name": "Juan Pérez",
        "email": "juan.perez@example.com"
      },
      "owner_agent": {
        "id": "agent-uuid-1",
        "name": "María García",
        "email": "maria.garcia@soporte.com"
      },
      "category": {
        "id": "cat-uuid-1",
        "name": "Soporte Técnico"
      },
      "responses_count": 5,
      "attachments_count": 2
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "/api/tickets?page=1",
    "last": "/api/tickets?page=3",
    "prev": null,
    "next": "/api/tickets?page=2"
  }
}
```

#### Response 401 Unauthorized

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHORIZED",
    "message": "Token de autenticación no válido o expirado"
  }
}
```

---

### 6. Obtener Detalle de Ticket

```http
GET /api/tickets/:code
Authorization: Bearer {token}
```

#### URL Parameters

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `:code` | string | Código del ticket | `TKT-2025-00001` |

#### Permisos
- ✅ **USER**: Puede ver SOLO sus propios tickets
- ✅ **AGENT**: Puede ver todos los tickets de su empresa
- ✅ **COMPANY_ADMIN**: Puede ver todos los tickets de su empresa

#### Response 200 OK

```json
{
  "success": true,
  "data": {
    "id": "ticket-uuid-1",
    "ticket_code": "TKT-2025-00001",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "category_id": "cat-uuid-1",
    "title": "Error al exportar reporte mensual",
    "description": "Cuando intento exportar el reporte mensual de ventas, el sistema muestra un error 500. Esto comenzó ayer por la tarde.",
    "status": "pending",
    "last_response_author_type": "agent",
    "owner_agent_id": "agent-uuid-1",
    "created_by_user_id": "user-uuid-1",
    "created_at": "2025-11-10T10:00:00Z",
    "updated_at": "2025-11-12T14:30:00Z",
    "first_response_at": "2025-11-10T10:15:00Z",
    "resolved_at": null,
    "closed_at": null,
    "created_by_user": {
      "id": "user-uuid-1",
      "name": "Juan Pérez",
      "email": "juan.perez@example.com",
      "avatar_url": "https://example.com/avatars/juan.jpg"
    },
    "owner_agent": {
      "id": "agent-uuid-1",
      "name": "María García",
      "email": "maria.garcia@soporte.com",
      "avatar_url": "https://example.com/avatars/maria.jpg"
    },
    "category": {
      "id": "cat-uuid-1",
      "name": "Soporte Técnico",
      "description": "Problemas técnicos con el sistema"
    },
    "company": {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "name": "Acme Corporation"
    },
    "responses_count": 5,
    "attachments_count": 2,
    "timeline": {
      "created_at": "2025-11-10T10:00:00Z",
      "first_response_at": "2025-11-10T10:15:00Z",
      "resolved_at": null,
      "closed_at": null
    }
  }
}
```

#### Response 403 Forbidden

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "No tienes permisos para ver este ticket"
  }
}
```

#### Response 404 Not Found

```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Ticket no encontrado"
  }
}
```

---

### 7. Crear Ticket

```http
POST /api/tickets
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body

```json
{
  "company_id": "550e8400-e29b-41d4-a716-446655440001",
  "category_id": "cat-uuid-1",
  "title": "Error al exportar reporte mensual",
  "description": "Cuando intento exportar el reporte mensual de ventas, el sistema muestra un error 500. Esto comenzó ayer por la tarde y afecta a todos los usuarios del departamento de ventas."
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `company_id` | UUID | ✅ | exists in companies | ID de la empresa |
| `category_id` | UUID | ✅ | exists, active | ID de la categoría |
| `title` | string | ✅ | min:5, max:255 | Título del problema |
| `description` | string | ✅ | min:10, max:5000 | Descripción detallada |

#### Validaciones Detalladas

**company_id:**
- ✅ Requerido
- ✅ Debe existir en la tabla `companies`
- ❌ Error 422 si no existe

**category_id:**
- ✅ Requerido
- ✅ Debe existir y estar activa (`is_active = true`)
- ❌ Error 422 si no existe o está inactiva

**title:**
- ✅ Requerido
- ✅ Mínimo 5 caracteres
- ✅ Máximo 255 caracteres
- ❌ Error 422 si falta o no cumple longitud

**description:**
- ✅ Requerido
- ✅ Mínimo 10 caracteres
- ✅ Máximo 5000 caracteres
- ❌ Error 422 si falta o no cumple longitud

#### Campos Auto-Generados

| Campo | Valor | Descripción |
|-------|-------|-------------|
| `ticket_code` | `TKT-YYYY-NNNNN` | Código secuencial por año |
| `status` | `'open'` | Estado inicial siempre es OPEN |
| `last_response_author_type` | `'none'` | Sin respuestas inicialmente |
| `created_by_user_id` | `auth()->id()` | Usuario autenticado |
| `owner_agent_id` | `null` | Sin agente asignado inicialmente |
| `created_at` | `NOW()` | Timestamp de creación |

#### Permisos
- ✅ **USER**: Puede crear tickets en cualquier empresa
- ❌ **AGENT**: NO puede crear tickets
- ❌ **COMPANY_ADMIN**: NO puede crear tickets

**Razón**: Solo los clientes (USER) pueden crear tickets de soporte.

#### Response 201 Created

```json
{
  "success": true,
  "message": "Ticket creado exitosamente",
  "data": {
    "id": "ticket-uuid-new",
    "ticket_code": "TKT-2025-00042",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "category_id": "cat-uuid-1",
    "title": "Error al exportar reporte mensual",
    "description": "Cuando intento exportar el reporte mensual de ventas, el sistema muestra un error 500...",
    "status": "open",
    "last_response_author_type": "none",
    "owner_agent_id": null,
    "created_by_user_id": "user-uuid-1",
    "created_at": "2025-11-13T15:30:00Z",
    "updated_at": "2025-11-13T15:30:00Z",
    "first_response_at": null,
    "resolved_at": null,
    "closed_at": null
  }
}
```

#### Response 403 Forbidden

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Solo los usuarios con rol USER pueden crear tickets"
  }
}
```

#### Response 422 Validation Error

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "company_id": [
      "La empresa especificada no existe"
    ],
    "category_id": [
      "La categoría seleccionada no existe o está inactiva"
    ],
    "title": [
      "El título debe tener al menos 5 caracteres",
      "El título no puede exceder 255 caracteres"
    ],
    "description": [
      "La descripción debe tener al menos 10 caracteres",
      "La descripción no puede exceder 5000 caracteres"
    ]
  }
}
```

---

### 8. Actualizar Ticket

```http
PUT /api/tickets/:code
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body (parcial)

```json
{
  "title": "Error al exportar reporte mensual - URGENTE",
  "category_id": "cat-uuid-2"
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `title` | string | ❌ | min:5, max:255 | Nuevo título |
| `category_id` | UUID | ❌ | exists, active | Nueva categoría |

**NOTA**:
- Actualización parcial permitida
- `description` NO es editable
- `status` NO es editable (usar endpoints de acciones)

#### Permisos y Restricciones

| Rol | Puede actualizar | Restricciones |
|-----|-----------------|---------------|
| **USER** | ✅ Solo tickets propios | SOLO si `status = 'open'` |
| **AGENT** | ✅ Tickets de su empresa | Siempre |
| **COMPANY_ADMIN** | ✅ Tickets de su empresa | Siempre |

#### Reglas de Negocio
- ✅ USER puede actualizar SOLO si ticket está en `status = 'open'`
- ❌ USER NO puede actualizar si ticket está en `pending`, `resolved`, o `closed`
- ✅ AGENT/ADMIN pueden actualizar en cualquier estado

#### Response 200 OK

```json
{
  "success": true,
  "message": "Ticket actualizado exitosamente",
  "data": {
    "id": "ticket-uuid-1",
    "ticket_code": "TKT-2025-00001",
    "title": "Error al exportar reporte mensual - URGENTE",
    "category_id": "cat-uuid-2",
    "status": "open",
    "updated_at": "2025-11-13T16:00:00Z"
  }
}
```

#### Response 403 Forbidden (USER en ticket no-open)

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "No puedes actualizar este ticket porque su estado no es 'open'. Solo puedes actualizar tickets en estado abierto."
  }
}
```

---

### 9. Eliminar Ticket

```http
DELETE /api/tickets/:code
Authorization: Bearer {token}
```

#### Permisos
- ❌ **USER**: NO puede eliminar tickets
- ❌ **AGENT**: NO puede eliminar tickets
- ✅ **COMPANY_ADMIN**: Puede eliminar tickets de su empresa

#### Reglas de Negocio
- ✅ Se puede eliminar SOLO si `status = 'closed'`
- ❌ NO se puede eliminar si está `open`, `pending`, o `resolved`
- ✅ Eliminación en cascada de:
    - Responses
    - Attachments (archivos físicos también)
    - Internal notes (si existieran)
    - Ratings

#### Response 200 OK

```json
{
  "success": true,
  "message": "Ticket eliminado exitosamente"
}
```

#### Response 400 Bad Request (Ticket no cerrado)

```json
{
  "success": false,
  "error": {
    "code": "CANNOT_DELETE_ACTIVE_TICKET",
    "message": "Solo se pueden eliminar tickets cerrados. Este ticket tiene estado: open"
  }
}
```

---

## 🔄 API - TICKETS ACTIONS

### 10. Resolver Ticket

```http
POST /api/tickets/:code/resolve
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body (opcional)

```json
{
  "resolution_note": "He reseteado tu contraseña y actualizado los permisos. El problema está resuelto."
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `resolution_note` | string | ❌ | max:5000 | Nota de resolución (opcional) |

#### Permisos
- ❌ **USER**: NO puede resolver tickets
- ✅ **AGENT**: Puede resolver tickets de su empresa
- ✅ **COMPANY_ADMIN**: Puede resolver tickets de su empresa

#### Reglas de Negocio
- ✅ Se puede resolver desde `open` o `pending`
- ❌ NO se puede resolver si ya está `resolved`
- ❌ NO se puede resolver si está `closed`
- ✅ Al resolver:
    - `status` → `'resolved'`
    - `resolved_at` → `NOW()`
    - `last_response_author_type` → SIN CAMBIOS (persiste)

#### Response 200 OK

```json
{
  "success": true,
  "message": "Ticket resuelto exitosamente",
  "data": {
    "id": "ticket-uuid-1",
    "ticket_code": "TKT-2025-00001",
    "status": "resolved",
    "last_response_author_type": "agent",
    "resolved_at": "2025-11-13T16:30:00Z",
    "updated_at": "2025-11-13T16:30:00Z"
  }
}
```

#### Response 400 Bad Request (Ya resuelto)

```json
{
  "success": false,
  "error": {
    "code": "ALREADY_RESOLVED",
    "message": "El ticket ya está resuelto"
  }
}
```

---

### 11. Cerrar Ticket

```http
POST /api/tickets/:code/close
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body (opcional)

```json
{
  "close_note": "Cerrando ticket por inactividad del cliente"
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `close_note` | string | ❌ | max:5000 | Nota de cierre (opcional) |

#### Permisos por Rol y Estado

| Rol | Puede cerrar | Restricciones |
|-----|-------------|---------------|
| **USER** | ✅ Tickets propios | SOLO si `status = 'resolved'` |
| **AGENT** | ✅ Tickets empresa | Cualquier estado |
| **COMPANY_ADMIN** | ✅ Tickets empresa | Cualquier estado |

#### Reglas de Negocio
- ✅ USER puede cerrar SOLO tickets `resolved` (conformidad)
- ✅ AGENT/ADMIN pueden cerrar en cualquier estado
- ❌ NO se puede cerrar si ya está `closed`
- ✅ Al cerrar:
    - `status` → `'closed'`
    - `closed_at` → `NOW()`
    - `last_response_author_type` → SIN CAMBIOS (persiste)

#### Response 200 OK

```json
{
  "success": true,
  "message": "Ticket cerrado exitosamente",
  "data": {
    "id": "ticket-uuid-1",
    "ticket_code": "TKT-2025-00001",
    "status": "closed",
    "last_response_author_type": "agent",
    "closed_at": "2025-11-13T17:00:00Z",
    "updated_at": "2025-11-13T17:00:00Z"
  }
}
```

#### Response 403 Forbidden (USER en ticket no-resolved)

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "Solo puedes cerrar tickets que estén en estado resuelto. Este ticket está en estado: pending"
  }
}
```

---

### 12. Reabrir Ticket

```http
POST /api/tickets/:code/reopen
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body (opcional)

```json
{
  "reopen_reason": "El problema volvió a ocurrir esta mañana"
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `reopen_reason` | string | ❌ | max:5000 | Razón de reapertura (opcional) |

#### Permisos por Rol y Tiempo

| Rol | Puede reabrir | Restricciones de Tiempo |
|-----|--------------|------------------------|
| **USER** | ✅ Tickets propios | ✅ Si cerrado hace < 30 días<br>✅ Sin límite si `resolved` |
| **AGENT** | ✅ Tickets empresa | ✅ Sin límite de tiempo |
| **COMPANY_ADMIN** | ✅ Tickets empresa | ✅ Sin límite de tiempo |

#### Reglas de Negocio
- ✅ Se puede reabrir desde `resolved` o `closed`
- ❌ NO se puede reabrir si está `open` o `pending`
- ✅ USER tiene límite de 30 días SOLO para tickets `closed`
- ✅ USER puede reabrir `resolved` sin límite de tiempo
- ✅ AGENT/ADMIN sin límite de tiempo
- ✅ Al reabrir:
    - `status` → `'pending'` (NO `'open'`)
    - `resolved_at` → `null`
    - `closed_at` → `null`
    - `last_response_author_type` → SIN CAMBIOS (persiste)
    - `owner_agent_id` → SE MANTIENE

#### Response 200 OK

```json
{
  "success": true,
  "message": "Ticket reabierto exitosamente",
  "data": {
    "id": "ticket-uuid-1",
    "ticket_code": "TKT-2025-00001",
    "status": "pending",
    "last_response_author_type": "agent",
    "owner_agent_id": "agent-uuid-1",
    "resolved_at": null,
    "closed_at": null,
    "updated_at": "2025-11-13T17:30:00Z"
  }
}
```

#### Response 403 Forbidden (USER después de 30 días)

```json
{
  "success": false,
  "error": {
    "code": "REOPEN_TIME_EXCEEDED",
    "message": "No puedes reabrir este ticket porque fue cerrado hace más de 30 días",
    "details": {
      "closed_at": "2025-10-01T10:00:00Z",
      "days_since_closed": 43
    }
  }
}
```

---

### 13. Reasignar Ticket

```http
POST /api/tickets/:code/assign
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body

```json
{
  "new_agent_id": "agent-uuid-2",
  "assignment_note": "Reasignando a María porque tiene más experiencia con reportes"
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `new_agent_id` | UUID | ✅ | exists, role=AGENT, same company | ID del nuevo agente |
| `assignment_note` | string | ❌ | max:5000 | Nota de reasignación (opcional) |

#### Validaciones Detalladas

**new_agent_id:**
- ✅ Requerido
- ✅ Debe existir en `users`
- ✅ Usuario debe tener rol `AGENT`
- ✅ Agente debe pertenecer a la misma empresa del ticket
- ❌ Error 422 si no existe
- ❌ Error 422 si no es AGENT
- ❌ Error 422 si es de otra empresa

#### Permisos
- ❌ **USER**: NO puede reasignar tickets
- ✅ **AGENT**: Puede reasignar tickets de su empresa
- ✅ **COMPANY_ADMIN**: Puede reasignar tickets de su empresa

#### Reglas de Negocio
- ✅ Se puede reasignar en cualquier estado
- ✅ Al reasignar:
    - `owner_agent_id` → `new_agent_id`
    - `last_response_author_type` → SIN CAMBIOS (persiste)
    - `updated_at` → `NOW()`
- ✅ Se dispara evento `TicketAssigned`
- ✅ Se notifica al nuevo agente

#### Response 200 OK

```json
{
  "success": true,
  "message": "Ticket reasignado exitosamente",
  "data": {
    "id": "ticket-uuid-1",
    "ticket_code": "TKT-2025-00001",
    "owner_agent_id": "agent-uuid-2",
    "last_response_author_type": "agent",
    "updated_at": "2025-11-13T18:00:00Z",
    "new_agent": {
      "id": "agent-uuid-2",
      "name": "María García",
      "email": "maria.garcia@soporte.com"
    }
  }
}
```

#### Response 422 Validation Error

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "new_agent_id": [
      "El agente especificado no existe",
      "El usuario no tiene rol de agente",
      "El agente pertenece a otra empresa"
    ]
  }
}
```

---

## 💬 API - RESPUESTAS

### 14. Listar Respuestas

```http
GET /api/tickets/:code/responses
Authorization: Bearer {token}
```

#### URL Parameters

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `:code` | string | Código del ticket |

#### Permisos
- ✅ **USER**: Puede listar respuestas de tickets propios
- ✅ **AGENT**: Puede listar respuestas de tickets de su empresa
- ✅ **COMPANY_ADMIN**: Puede listar respuestas de tickets de su empresa

#### Response 200 OK

```json
{
  "success": true,
  "data": [
    {
      "id": "response-uuid-1",
      "ticket_id": "ticket-uuid-1",
      "response_id": null,
      "author_id": "user-uuid-1",
      "author_type": "user",
      "response_content": "Hola, necesito ayuda urgente con este problema",
      "created_at": "2025-11-10T10:05:00Z",
      "updated_at": "2025-11-10T10:05:00Z",
      "author": {
        "id": "user-uuid-1",
        "name": "Juan Pérez",
        "email": "juan.perez@example.com",
        "avatar_url": "https://example.com/avatars/juan.jpg"
      },
      "attachments": []
    },
    {
      "id": "response-uuid-2",
      "ticket_id": "ticket-uuid-1",
      "response_id": "response-uuid-1",
      "author_id": "agent-uuid-1",
      "author_type": "agent",
      "response_content": "Hola Juan, entiendo tu urgencia. Ya estoy investigando el problema y te tendré una respuesta en las próximas 2 horas.",
      "created_at": "2025-11-10T10:15:00Z",
      "updated_at": "2025-11-10T10:15:00Z",
      "author": {
        "id": "agent-uuid-1",
        "name": "María García",
        "email": "maria.garcia@soporte.com",
        "avatar_url": "https://example.com/avatars/maria.jpg"
      },
      "attachments": [
        {
          "id": "attachment-uuid-1",
          "file_name": "screenshot.png",
          "file_url": "/storage/tickets/attachments/screenshot-hash.png",
          "file_type": "image/png",
          "file_size_bytes": 245678,
          "uploaded_by_user_id": "agent-uuid-1",
          "created_at": "2025-11-10T10:15:00Z"
        }
      ]
    }
  ],
  "meta": {
    "total": 2,
    "ticket_code": "TKT-2025-00001"
  }
}
```

**NOTA IMPORTANTE**:
- Las respuestas están ordenadas por `created_at ASC` (cronológico)
- Incluye información del autor (usuario o agente)
- Incluye adjuntos relacionados a cada respuesta

---

### 15. Agregar Respuesta

```http
POST /api/tickets/:code/responses
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body

```json
{
  "response_content": "He identificado el problema. El servidor de reportes estaba sobrecargado. Ya lo reinicié y debería funcionar correctamente."
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `response_content` | string | ✅ | min:1, max:5000 | Contenido de la respuesta |

#### Validaciones Detalladas

**response_content:**
- ✅ Requerido
- ✅ Mínimo 1 carácter
- ✅ Máximo 5000 caracteres
- ❌ Error 422 si falta
- ❌ Error 422 si está vacío
- ❌ Error 422 si excede 5000 caracteres

#### Campos Auto-Generados

| Campo | Valor | Descripción |
|-------|-------|-------------|
| `author_id` | `auth()->id()` | Usuario autenticado |
| `author_type` | `'user'` o `'agent'` | Según rol del autenticado |
| `created_at` | `NOW()` | Timestamp de creación |

#### Permisos
- ✅ **USER**: Puede responder SOLO en tickets propios
- ✅ **AGENT**: Puede responder en tickets de su empresa
- ✅ **COMPANY_ADMIN**: Puede responder en tickets de su empresa

#### Reglas de Negocio

**Estado del ticket:**
- ✅ Se puede responder en `open`, `pending`, `resolved`
- ❌ NO se puede responder en `closed`

**Triggers automáticos que se ejecutan:**

1. **Si autor es AGENT y ticket sin agente**:
    - `owner_agent_id` → agente actual (auto-assignment)
    - `status` → `'pending'`
    - `first_response_at` → `NOW()` (solo primera vez)
    - `last_response_author_type` → `'agent'`

2. **Si autor es USER y ticket en pending**:
    - `status` → `'open'`
    - `last_response_author_type` → `'user'`
    - `owner_agent_id` → SIN CAMBIOS

3. **Siempre**:
    - `last_response_author_type` → tipo del autor

#### Response 201 Created

```json
{
  "success": true,
  "message": "Respuesta agregada exitosamente",
  "data": {
    "id": "response-uuid-new",
    "ticket_id": "ticket-uuid-1",
    "response_id": null,
    "author_id": "agent-uuid-1",
    "author_type": "agent",
    "response_content": "He identificado el problema. El servidor de reportes estaba sobrecargado...",
    "created_at": "2025-11-13T18:30:00Z",
    "updated_at": "2025-11-13T18:30:00Z",
    "author": {
      "id": "agent-uuid-1",
      "name": "María García",
      "email": "maria.garcia@soporte.com"
    }
  }
}
```

#### Response 403 Forbidden (Ticket cerrado)

```json
{
  "success": false,
  "error": {
    "code": "TICKET_CLOSED",
    "message": "No se pueden agregar respuestas a un ticket cerrado"
  }
}
```

---

### 16. Editar Respuesta

```http
PUT /api/tickets/:code/responses/:id
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body

```json
{
  "response_content": "He identificado el problema. El servidor de reportes estaba sobrecargado. Ya lo reinicié y debería funcionar correctamente. Actualización: También actualicé la configuración para evitar futuras sobrecargas."
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `response_content` | string | ✅ | min:1, max:5000 | Nuevo contenido |

#### Permisos
- ✅ **Autor de la respuesta**: Puede editar su propia respuesta
- ❌ **Otros usuarios**: NO pueden editar respuestas de otros

#### Reglas de Negocio

**Restricción de tiempo:**
- ✅ Se puede editar dentro de los **30 minutos** posteriores a la creación
- ❌ NO se puede editar después de 30 minutos

**Estado del ticket:**
- ✅ Se puede editar si ticket NO está `closed`
- ❌ NO se puede editar si ticket está `closed`

**Campos que NO cambian:**
- `created_at` → SIN CAMBIOS (persiste timestamp original)
- `author_id` → SIN CAMBIOS
- `author_type` → SIN CAMBIOS
- `last_response_author_type` del ticket → SIN CAMBIOS

#### Response 200 OK

```json
{
  "success": true,
  "message": "Respuesta actualizada exitosamente",
  "data": {
    "id": "response-uuid-1",
    "response_content": "He identificado el problema. El servidor de reportes estaba sobrecargado...",
    "created_at": "2025-11-13T18:30:00Z",
    "updated_at": "2025-11-13T18:45:00Z"
  }
}
```

#### Response 403 Forbidden (Tiempo excedido)

```json
{
  "success": false,
  "error": {
    "code": "EDIT_TIME_EXCEEDED",
    "message": "No puedes editar esta respuesta porque han pasado más de 30 minutos desde su creación",
    "details": {
      "created_at": "2025-11-13T17:00:00Z",
      "minutes_since_created": 45
    }
  }
}
```

---

### 17. Eliminar Respuesta

```http
DELETE /api/tickets/:code/responses/:id
Authorization: Bearer {token}
```

#### Permisos
- ✅ **Autor de la respuesta**: Puede eliminar su propia respuesta
- ❌ **Otros usuarios**: NO pueden eliminar respuestas de otros

#### Reglas de Negocio

**Restricción de tiempo:**
- ✅ Se puede eliminar dentro de los **30 minutos** posteriores a la creación
- ❌ NO se puede eliminar después de 30 minutos

**Estado del ticket:**
- ✅ Se puede eliminar si ticket NO está `closed`
- ❌ NO se puede eliminar si ticket está `closed`

**Eliminación en cascada:**
- ✅ Se eliminan los adjuntos asociados a la respuesta
- ✅ Se eliminan los archivos físicos del storage

**Campo que NO cambia:**
- `last_response_author_type` del ticket → SIN CAMBIOS (no se recalcula)

#### Response 200 OK

```json
{
  "success": true,
  "message": "Respuesta eliminada exitosamente"
}
```

#### Response 403 Forbidden (No es el autor)

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN",
    "message": "No puedes eliminar esta respuesta porque no eres el autor"
  }
}
```

#### Response 404 Not Found

```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Respuesta no encontrada"
  }
}
```

---

## 📎 API - ADJUNTOS

### 18. Listar Adjuntos

```http
GET /api/tickets/:code/attachments
Authorization: Bearer {token}
```

#### Permisos
- ✅ **USER**: Puede listar adjuntos de tickets propios
- ✅ **AGENT**: Puede listar adjuntos de tickets de su empresa
- ✅ **COMPANY_ADMIN**: Puede listar adjuntos de tickets de su empresa

#### Response 200 OK

```json
{
  "success": true,
  "data": [
    {
      "id": "attachment-uuid-1",
      "ticket_id": "ticket-uuid-1",
      "response_id": null,
      "uploaded_by_user_id": "user-uuid-1",
      "file_name": "error-screenshot.png",
      "file_url": "/storage/tickets/attachments/error-screenshot-hash123.png",
      "file_type": "image/png",
      "file_size_bytes": 345678,
      "created_at": "2025-11-10T10:03:00Z",
      "uploader": {
        "id": "user-uuid-1",
        "name": "Juan Pérez",
        "email": "juan.perez@example.com"
      },
      "response_context": null
    },
    {
      "id": "attachment-uuid-2",
      "ticket_id": "ticket-uuid-1",
      "response_id": "response-uuid-2",
      "uploaded_by_user_id": "agent-uuid-1",
      "file_name": "solution-guide.pdf",
      "file_url": "/storage/tickets/attachments/solution-guide-hash456.pdf",
      "file_type": "application/pdf",
      "file_size_bytes": 1245678,
      "created_at": "2025-11-10T10:15:00Z",
      "uploader": {
        "id": "agent-uuid-1",
        "name": "María García",
        "email": "maria.garcia@soporte.com"
      },
      "response_context": {
        "id": "response-uuid-2",
        "author_type": "agent",
        "created_at": "2025-11-10T10:15:00Z"
      }
    }
  ],
  "meta": {
    "total": 2,
    "ticket_code": "TKT-2025-00001"
  }
}
```

**NOTA**:
- `response_id = null`: Adjunto subido directamente al ticket
- `response_id != null`: Adjunto subido a una respuesta específica

---

### 19. Subir Adjunto

```http
POST /api/tickets/:code/attachments
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

#### Request Body (multipart)

```
file: [binary data]
response_id: [optional UUID]
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `file` | file | ✅ | max:10MB, allowed types | Archivo a subir |
| `response_id` | UUID | ❌ | exists | ID de respuesta (opcional) |

#### Validaciones Detalladas

**file:**
- ✅ Requerido
- ✅ Máximo 10 MB (10240 KB)
- ✅ Tipos permitidos:
    - **Imágenes**: JPG, JPEG, PNG, GIF, WEBP
    - **Documentos**: PDF, DOC, DOCX, XLS, XLSX, TXT
    - **Comprimidos**: ZIP
- ❌ Error 422 si falta
- ❌ Error 413 si excede 10MB
- ❌ Error 422 si tipo no permitido

**response_id:**
- ⭕ Opcional
- ✅ Si se proporciona, debe existir
- ✅ Si se proporciona, debe pertenecer al ticket
- ✅ Solo el autor de la respuesta puede subir adjuntos a su respuesta
- ✅ Solo dentro de 30 minutos de crear la respuesta

**Límite de adjuntos:**
- ✅ Máximo 5 adjuntos por ticket (total)
- ❌ Error 422 si se excede el límite

#### Permisos
- ✅ **USER**: Puede subir adjuntos a tickets propios
- ✅ **AGENT**: Puede subir adjuntos a tickets de su empresa
- ✅ **COMPANY_ADMIN**: Puede subir adjuntos a tickets de su empresa

#### Reglas de Negocio

**Estado del ticket:**
- ✅ Se puede subir si ticket NO está `closed`
- ❌ NO se puede subir si ticket está `closed`

**Storage:**
- Path: `storage/app/public/tickets/attachments/`
- Filename: Hash único + extensión original
- URL pública: `/storage/tickets/attachments/{filename}`

#### Response 200 OK

```json
{
  "success": true,
  "message": "Adjunto subido exitosamente",
  "data": {
    "id": "attachment-uuid-new",
    "ticket_id": "ticket-uuid-1",
    "response_id": null,
    "uploaded_by_user_id": "user-uuid-1",
    "file_name": "error-screenshot.png",
    "file_url": "/storage/tickets/attachments/error-screenshot-a1b2c3d4.png",
    "file_type": "image/png",
    "file_size_bytes": 345678,
    "created_at": "2025-11-13T19:00:00Z"
  }
}
```

#### Response 413 Payload Too Large

```json
{
  "success": false,
  "error": {
    "code": "FILE_TOO_LARGE",
    "message": "El archivo excede el tamaño máximo permitido de 10 MB",
    "details": {
      "max_size_mb": 10,
      "file_size_mb": 15.5
    }
  }
}
```

#### Response 422 Validation Error

```json
{
  "success": false,
  "message": "Error de validación",
  "errors": {
    "file": [
      "El campo file es requerido",
      "El tipo de archivo no está permitido. Tipos permitidos: jpg, png, pdf, doc, docx, xls, xlsx, txt, zip"
    ],
    "response_id": [
      "La respuesta especificada no existe",
      "Solo puedes subir adjuntos a respuestas que creaste tú",
      "No puedes subir adjuntos a una respuesta después de 30 minutos de su creación"
    ]
  }
}
```

#### Response 422 Max Attachments Exceeded

```json
{
  "success": false,
  "error": {
    "code": "MAX_ATTACHMENTS_EXCEEDED",
    "message": "El ticket ha alcanzado el límite máximo de 5 adjuntos",
    "details": {
      "max_attachments": 5,
      "current_attachments": 5
    }
  }
}
```

---

### 20. Eliminar Adjunto

```http
DELETE /api/tickets/:code/attachments/:id
Authorization: Bearer {token}
```

#### Permisos
- ✅ **Uploader**: Puede eliminar su propio adjunto
- ❌ **Otros usuarios**: NO pueden eliminar adjuntos de otros

#### Reglas de Negocio

**Restricción de tiempo:**
- ✅ Se puede eliminar dentro de los **30 minutos** posteriores a la subida
- ❌ NO se puede eliminar después de 30 minutos

**Estado del ticket:**
- ✅ Se puede eliminar si ticket NO está `closed`
- ❌ NO se puede eliminar si ticket está `closed`

**Eliminación física:**
- ✅ Se elimina el archivo del storage
- ✅ Se elimina el registro de BD

#### Response 200 OK

```json
{
  "success": true,
  "message": "Adjunto eliminado exitosamente"
}
```

#### Response 403 Forbidden (Tiempo excedido)

```json
{
  "success": false,
  "error": {
    "code": "DELETE_TIME_EXCEEDED",
    "message": "No puedes eliminar este adjunto porque han pasado más de 30 minutos desde su subida",
    "details": {
      "uploaded_at": "2025-11-13T18:00:00Z",
      "minutes_since_uploaded": 45
    }
  }
}
```

---

## ⭐ API - CALIFICACIONES

### 21. Calificar Ticket

```http
POST /api/tickets/:code/rating
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body

```json
{
  "rating": 5,
  "comment": "Excelente atención, el agente fue muy rápido y profesional. Problema resuelto completamente."
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `rating` | integer | ✅ | min:1, max:5 | Calificación de 1 a 5 estrellas |
| `comment` | string | ❌ | max:1000 | Comentario (opcional) |

#### Validaciones Detalladas

**rating:**
- ✅ Requerido
- ✅ Debe ser entero
- ✅ Mínimo: 1
- ✅ Máximo: 5
- ❌ Error 422 si falta
- ❌ Error 422 si no está entre 1-5

**comment:**
- ⭕ Opcional
- ✅ Máximo 1000 caracteres
- ❌ Error 422 si excede 1000 caracteres

#### Permisos
- ✅ **USER**: Puede calificar SOLO sus propios tickets
- ❌ **AGENT**: NO puede calificar tickets
- ❌ **COMPANY_ADMIN**: NO puede calificar tickets

#### Reglas de Negocio

**Estado del ticket:**
- ✅ Se puede calificar si ticket está `resolved` o `closed`
- ❌ NO se puede calificar si está `open` o `pending`

**Unicidad:**
- ✅ Solo se puede calificar UNA VEZ por ticket
- ❌ Error 409 si ya existe calificación

**Snapshot histórico:**
- ✅ `rated_agent_id` se guarda al momento de calificar
- ✅ NO cambia si reasignan el ticket después

#### Response 201 Created

```json
{
  "success": true,
  "message": "Calificación registrada exitosamente",
  "data": {
    "id": "rating-uuid-1",
    "ticket_id": "ticket-uuid-1",
    "rated_by_user_id": "user-uuid-1",
    "rated_agent_id": "agent-uuid-1",
    "rating": 5,
    "comment": "Excelente atención, el agente fue muy rápido y profesional...",
    "created_at": "2025-11-13T19:30:00Z",
    "updated_at": "2025-11-13T19:30:00Z"
  }
}
```

#### Response 409 Conflict (Ya calificado)

```json
{
  "success": false,
  "error": {
    "code": "RATING_ALREADY_EXISTS",
    "message": "Ya has calificado este ticket. Puedes actualizar tu calificación usando PUT."
  }
}
```

#### Response 400 Bad Request (Estado incorrecto)

```json
{
  "success": false,
  "error": {
    "code": "INVALID_TICKET_STATUS",
    "message": "Solo puedes calificar tickets que estén resueltos o cerrados. Estado actual: open"
  }
}
```

---

### 22. Actualizar Calificación

```http
PUT /api/tickets/:code/rating
Authorization: Bearer {token}
Content-Type: application/json
```

#### Request Body

```json
{
  "rating": 4,
  "comment": "Actualizo mi calificación. La solución funcionó pero tardó un poco más de lo esperado."
}
```

#### Campos del Request

| Campo | Tipo | Requerido | Validación | Descripción |
|-------|------|-----------|------------|-------------|
| `rating` | integer | ❌ | min:1, max:5 | Nueva calificación |
| `comment` | string | ❌ | max:1000 | Nuevo comentario |

**NOTA**: Actualización parcial permitida. Solo enviar campos a modificar.

#### Permisos
- ✅ **Autor de la calificación**: Puede actualizar su propia calificación
- ❌ **Otros usuarios**: NO pueden actualizar calificaciones de otros

#### Reglas de Negocio

**Restricción de tiempo:**
- ✅ Se puede actualizar dentro de las **24 horas** posteriores a la creación
- ❌ NO se puede actualizar después de 24 horas

**Campos que NO cambian:**
- `rated_agent_id` → SIN CAMBIOS (snapshot histórico)
- `created_at` → SIN CAMBIOS

#### Response 200 OK

```json
{
  "success": true,
  "message": "Calificación actualizada exitosamente",
  "data": {
    "id": "rating-uuid-1",
    "rating": 4,
    "comment": "Actualizo mi calificación. La solución funcionó pero tardó un poco más de lo esperado.",
    "created_at": "2025-11-13T19:30:00Z",
    "updated_at": "2025-11-14T10:00:00Z"
  }
}
```

#### Response 403 Forbidden (Tiempo excedido)

```json
{
  "success": false,
  "error": {
    "code": "UPDATE_TIME_EXCEEDED",
    "message": "No puedes actualizar esta calificación porque han pasado más de 24 horas desde su creación",
    "details": {
      "created_at": "2025-11-12T10:00:00Z",
      "hours_since_created": 36
    }
  }
}
```

---

### 23. Ver Calificación

```http
GET /api/tickets/:code/rating
Authorization: Bearer {token}
```

#### Permisos
- ✅ **USER**: Puede ver calificación de tickets propios
- ✅ **AGENT**: Puede ver calificación de tickets de su empresa
- ✅ **COMPANY_ADMIN**: Puede ver calificación de tickets de su empresa

#### Response 200 OK

```json
{
  "success": true,
  "data": {
    "id": "rating-uuid-1",
    "ticket_id": "ticket-uuid-1",
    "rated_by_user_id": "user-uuid-1",
    "rated_agent_id": "agent-uuid-1",
    "rating": 5,
    "comment": "Excelente atención, el agente fue muy rápido y profesional...",
    "created_at": "2025-11-13T19:30:00Z",
    "updated_at": "2025-11-13T19:30:00Z",
    "rated_by_user": {
      "id": "user-uuid-1",
      "name": "Juan Pérez",
      "email": "juan.perez@example.com"
    },
    "rated_agent": {
      "id": "agent-uuid-1",
      "name": "María García",
      "email": "maria.garcia@soporte.com"
    }
  }
}
```

#### Response 404 Not Found (Sin calificación)

```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "Este ticket aún no ha sido calificado"
  }
}
```

---

## 🔒 REGLAS DE NEGOCIO CRÍTICAS

### 0. Principios Arquitectónicos Fundamentales

#### Sistema JWT Stateless
- **NO Laravel Sessions**: Todo basado en JWT tokens
- **Multi-Tenant Context**: Usar `JWTHelper::getUserId()` y `JWTHelper::getCompanyId()`
- **NUNCA usar**: `auth()->user()` (incompatible con JWT stateless)

#### Validaciones de Tiempo Críticas
- **30 minutos**: Editar/eliminar respuestas y adjuntos
- **30 días**: Reabrir tickets cerrados (solo USER)
- **7 días**: Auto-cierre de tickets resueltos (job automático)
- **24 horas**: Actualizar calificaciones

#### PostgreSQL Triggers Automáticos
- **Auto-asignación**: Primer agente que responde queda asignado (`owner_agent_id`)
- **Cambio de estado OPEN → PENDING**: Automático cuando agente responde
- **Cambio de estado PENDING → OPEN**: Automático cuando cliente responde
- **NO manejar en código**: Estos cambios son responsabilidad de la BD

#### Testing y Desarrollo
- **Docker SIEMPRE**: Nunca usar PHP Herd
- **Feature Tests**: Cubrir flujos completos end-to-end
- **TDD**: Tests antes de implementación

---

### 1. Auto-Close de Tickets Resueltos

**Job Programado**: `AutoCloseResolvedTicketsJob`

**Frecuencia**: Ejecutar diariamente (sugerido: 2:00 AM)

**Lógica**:
```sql
UPDATE ticketing.tickets
SET 
    status = 'closed',
    closed_at = NOW(),
    updated_at = NOW()
WHERE status = 'resolved'
AND resolved_at < NOW() - INTERVAL '7 days';
```

**Regla**: Tickets en estado `resolved` por MÁS de 7 días se cierran automáticamente.

---

### 2. Ventanas de Tiempo

| Acción | Ventana | Descripción |
|--------|---------|-------------|
| Editar Respuesta | 30 minutos | Desde `created_at` de la respuesta |
| Eliminar Respuesta | 30 minutos | Desde `created_at` de la respuesta |
| Eliminar Adjunto | 30 minutos | Desde `created_at` del adjunto |
| Subir adjunto a respuesta | 30 minutos | Desde `created_at` de la respuesta |
| Reabrir ticket cerrado (USER) | 30 días | Desde `closed_at` del ticket |
| Actualizar calificación | 24 horas | Desde `created_at` de la calificación |

---

### 3. Ticket Code Generation

**Formato**: `TKT-YYYY-NNNNN`

**Ejemplos**:
- `TKT-2025-00001` (primer ticket de 2025)
- `TKT-2025-00042` (ticket 42 de 2025)
- `TKT-2026-00001` (resetea en nuevo año)

**Implementación**:
```php
// Obtener último número del año actual
$year = now()->year;
$lastTicket = Ticket::where('ticket_code', 'LIKE', "TKT-{$year}-%")
    ->orderBy('ticket_code', 'desc')
    ->first();

if ($lastTicket) {
    // Extraer número y sumar 1
    $lastNumber = (int) substr($lastTicket->ticket_code, -5);
    $newNumber = $lastNumber + 1;
} else {
    // Primer ticket del año
    $newNumber = 1;
}

$ticketCode = sprintf('TKT-%d-%05d', $year, $newNumber);
// Resultado: TKT-2025-00042
```

---

### 4. Campos Inmutables

Campos que NUNCA deben cambiar después de la creación:

| Campo | Tabla | Razón |
|-------|-------|-------|
| `ticket_code` | tickets | Identificador único |
| `created_by_user_id` | tickets | Autor original |
| `created_at` | tickets | Timestamp histórico |
| `first_response_at` | tickets | Primer contacto histórico |
| `author_id` | responses | Autor original |
| `author_type` | responses | Tipo autor original |
| `created_at` | responses | Timestamp histórico |
| `rated_agent_id` | ratings | Snapshot histórico |

---

### 5. Eliminación en Cascada

Cuando se elimina un **Ticket**:
- ✅ Se eliminan todas las **Responses**
- ✅ Se eliminan todos los **Attachments** (y archivos físicos)
- ✅ Se elimina la **Rating** (si existe)
- ✅ Se eliminan **Internal Notes** (si existen en futuro)

Cuando se elimina una **Response**:
- ✅ Se eliminan **Attachments** de esa respuesta (y archivos físicos)

---

## 🛡️ PERMISOS Y MATRIZ DE AUTORIZACIÓN

### Matriz Completa de Permisos

| Operación | USER | AGENT | COMPANY_ADMIN |
|-----------|:----:|:-----:|:-------------:|
| **CATEGORÍAS** |||
| Listar categorías | ✅ | ✅ | ✅ |
| Crear categoría | ❌ | ❌ | ✅ |
| Actualizar categoría | ❌ | ❌ | ✅ |
| Eliminar categoría | ❌ | ❌ | ✅ (si no tiene tickets activos) |
| **TICKETS - CRUD** |||
| Listar tickets | ✅ (propios) | ✅ (empresa) | ✅ (empresa) |
| Ver detalle ticket | ✅ (propios) | ✅ (empresa) | ✅ (empresa) |
| Crear ticket | ✅ | ❌ | ❌ |
| Actualizar ticket | ✅ (propios, solo si open) | ✅ (empresa) | ✅ (empresa) |
| Eliminar ticket | ❌ | ❌ | ✅ (solo si closed) |
| **TICKETS - ACCIONES** |||
| Resolver ticket | ❌ | ✅ (empresa) | ✅ (empresa) |
| Cerrar ticket | ✅ (propios, solo si resolved) | ✅ (empresa) | ✅ (empresa) |
| Reabrir ticket | ✅ (propios, <30d si closed) | ✅ (empresa) | ✅ (empresa) |
| Reasignar ticket | ❌ | ✅ (empresa) | ✅ (empresa) |
| **RESPUESTAS** |||
| Listar respuestas | ✅ (propios) | ✅ (empresa) | ✅ (empresa) |
| Agregar respuesta | ✅ (propios) | ✅ (empresa) | ✅ (empresa) |
| Editar respuesta | ✅ (propia, 30min) | ✅ (propia, 30min) | ✅ (propia, 30min) |
| Eliminar respuesta | ✅ (propia, 30min) | ✅ (propia, 30min) | ✅ (propia, 30min) |
| **ADJUNTOS** |||
| Listar adjuntos | ✅ (propios) | ✅ (empresa) | ✅ (empresa) |
| Subir adjunto | ✅ (propios) | ✅ (empresa) | ✅ (empresa) |
| Eliminar adjunto | ✅ (propio, 30min) | ✅ (propio, 30min) | ✅ (propio, 30min) |
| **CALIFICACIONES** |||
| Ver calificación | ✅ (propios) | ✅ (empresa) | ✅ (empresa) |
| Crear calificación | ✅ (propios) | ❌ | ❌ |
| Actualizar calificación | ✅ (propia, 24h) | ❌ | ❌ |

---

## ❌ CÓDIGOS DE ERROR

### Códigos HTTP Estándar

| Código | Nombre | Cuándo se usa |
|--------|--------|---------------|
| **200** | OK | Operación exitosa (GET, PUT, DELETE) |
| **201** | Created | Recurso creado exitosamente (POST) |
| **400** | Bad Request | Request inválido (lógica de negocio) |
| **401** | Unauthorized | Sin autenticación o token inválido |
| **403** | Forbidden | Sin permisos suficientes |
| **404** | Not Found | Recurso no encontrado |
| **409** | Conflict | Conflicto (ej: calificación ya existe) |
| **413** | Payload Too Large | Archivo muy grande |
| **422** | Unprocessable Entity | Errores de validación |
| **500** | Internal Server Error | Error del servidor |

### Códigos de Error Personalizados

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Mensaje legible para el usuario",
    "details": {
      "campo": "valor adicional"
    }
  }
}
```

#### Catálogo de Códigos

| Código | HTTP | Descripción |
|--------|------|-------------|
| `UNAUTHORIZED` | 401 | Token inválido o expirado |
| `FORBIDDEN` | 403 | Sin permisos |
| `NOT_FOUND` | 404 | Recurso no encontrado |
| `CATEGORY_IN_USE` | 409 | Categoría tiene tickets activos |
| `RATING_ALREADY_EXISTS` | 409 | Ticket ya fue calificado |
| `FILE_TOO_LARGE` | 413 | Archivo excede 10MB |
| `VALIDATION_ERROR` | 422 | Errores de validación (múltiples) |
| `MAX_ATTACHMENTS_EXCEEDED` | 422 | Más de 5 adjuntos |
| `ALREADY_RESOLVED` | 400 | Ticket ya está resuelto |
| `ALREADY_CLOSED` | 400 | Ticket ya está cerrado |
| `TICKET_CLOSED` | 403 | No se puede operar en ticket cerrado |
| `EDIT_TIME_EXCEEDED` | 403 | Ventana de 30 min excedida |
| `UPDATE_TIME_EXCEEDED` | 403 | Ventana de 24h excedida |
| `DELETE_TIME_EXCEEDED` | 403 | Ventana de 30 min excedida |
| `REOPEN_TIME_EXCEEDED` | 403 | Ventana de 30 días excedida |
| `CANNOT_DELETE_ACTIVE_TICKET` | 400 | Solo tickets closed |
| `INVALID_TICKET_STATUS` | 400 | Estado incorrecto para operación |

---

## ✅ VALIDACIONES COMPLETAS

### Categorías

| Campo | Validación | Mensaje de Error |
|-------|------------|------------------|
| `name` | required | "El campo name es requerido" |
| `name` | string | "El campo name debe ser texto" |
| `name` | min:3 | "El nombre debe tener al menos 3 caracteres" |
| `name` | max:100 | "El nombre no puede exceder 100 caracteres" |
| `name` | unique:company_id,name | "Ya existe una categoría con ese nombre en esta empresa" |
| `description` | nullable | - |
| `description` | string | "El campo description debe ser texto" |
| `description` | max:500 | "La descripción no puede exceder 500 caracteres" |
| `is_active` | boolean | "El campo is_active debe ser verdadero o falso" |

---

### Tickets

| Campo | Validación | Mensaje de Error |
|-------|------------|------------------|
| `company_id` | required | "El campo company_id es requerido" |
| `company_id` | uuid | "El company_id debe ser un UUID válido" |
| `company_id` | exists:companies,id | "La empresa especificada no existe" |
| `category_id` | required | "El campo category_id es requerido" |
| `category_id` | uuid | "El category_id debe ser un UUID válido" |
| `category_id` | exists:categories,id | "La categoría especificada no existe" |
| `category_id` | active | "La categoría seleccionada está inactiva" |
| `title` | required | "El campo title es requerido" |
| `title` | string | "El título debe ser texto" |
| `title` | min:5 | "El título debe tener al menos 5 caracteres" |
| `title` | max:255 | "El título no puede exceder 255 caracteres" |
| `description` | required | "El campo description es requerido" |
| `description` | string | "La descripción debe ser texto" |
| `description` | min:10 | "La descripción debe tener al menos 10 caracteres" |
| `description` | max:5000 | "La descripción no puede exceder 5000 caracteres" |

---

### Respuestas

| Campo | Validación | Mensaje de Error |
|-------|------------|------------------|
| `response_content` | required | "El campo response_content es requerido" |
| `response_content` | string | "El contenido debe ser texto" |
| `response_content` | min:1 | "El contenido no puede estar vacío" |
| `response_content` | max:5000 | "El contenido no puede exceder 5000 caracteres" |

---

### Adjuntos

| Campo | Validación | Mensaje de Error |
|-------|------------|------------------|
| `file` | required | "El campo file es requerido" |
| `file` | file | "Debe ser un archivo válido" |
| `file` | max:10240 (KB) | "El archivo no puede exceder 10 MB" |
| `file` | mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip | "Tipo de archivo no permitido" |

---

### Calificaciones

| Campo | Validación | Mensaje de Error |
|-------|------------|------------------|
| `rating` | required | "El campo rating es requerido" |
| `rating` | integer | "La calificación debe ser un número entero" |
| `rating` | min:1 | "La calificación mínima es 1" |
| `rating` | max:5 | "La calificación máxima es 5" |
| `comment` | nullable | - |
| `comment` | string | "El comentario debe ser texto" |
| `comment` | max:1000 | "El comentario no puede exceder 1000 caracteres" |

---

### Reasignación

| Campo | Validación | Mensaje de Error |
|-------|------------|------------------|
| `new_agent_id` | required | "El campo new_agent_id es requerido" |
| `new_agent_id` | uuid | "El new_agent_id debe ser un UUID válido" |
| `new_agent_id` | exists:users,id | "El agente especificado no existe" |
| `new_agent_id` | role:AGENT | "El usuario no tiene rol de agente" |
| `new_agent_id` | same_company | "El agente pertenece a otra empresa" |
| `assignment_note` | nullable | - |
| `assignment_note` | string | "La nota debe ser texto" |
| `assignment_note` | max:5000 | "La nota no puede exceder 5000 caracteres" |

---

## 📊 ESQUEMA DE BASE DE DATOS

### Tabla: tickets

```sql
CREATE TABLE ticketing.tickets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_code VARCHAR(50) NOT NULL UNIQUE,
    company_id UUID NOT NULL REFERENCES business.companies(id),
    category_id UUID NOT NULL REFERENCES ticketing.categories(id),
    created_by_user_id UUID NOT NULL REFERENCES auth.users(id),
    owner_agent_id UUID REFERENCES auth.users(id),
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    last_response_author_type VARCHAR(20) NOT NULL DEFAULT 'none',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    first_response_at TIMESTAMPTZ,
    resolved_at TIMESTAMPTZ,
    closed_at TIMESTAMPTZ,
    
    CONSTRAINT chk_status CHECK (status IN ('open', 'pending', 'resolved', 'closed')),
    CONSTRAINT chk_last_response_author CHECK (last_response_author_type IN ('none', 'user', 'agent'))
);

-- Índices para performance
CREATE INDEX idx_tickets_company_id ON ticketing.tickets(company_id);
CREATE INDEX idx_tickets_created_by_user_id ON ticketing.tickets(created_by_user_id);
CREATE INDEX idx_tickets_owner_agent_id ON ticketing.tickets(owner_agent_id);
CREATE INDEX idx_tickets_status ON ticketing.tickets(status);
CREATE INDEX idx_tickets_last_response_author_type ON ticketing.tickets(last_response_author_type);
CREATE INDEX idx_tickets_status_owner ON ticketing.tickets(status, owner_agent_id);
CREATE INDEX idx_tickets_created_at ON ticketing.tickets(created_at DESC);
```

---

### Tabla: categories

```sql
CREATE TABLE ticketing.categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID NOT NULL REFERENCES business.companies(id),
    name VARCHAR(100) NOT NULL,
    description VARCHAR(500),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    CONSTRAINT uq_company_category_name UNIQUE (company_id, name)
);

CREATE INDEX idx_categories_company_id ON ticketing.categories(company_id);
CREATE INDEX idx_categories_is_active ON ticketing.categories(is_active);
```

---

### Tabla: ticket_responses

```sql
CREATE TABLE ticketing.ticket_responses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
    response_id UUID REFERENCES ticketing.ticket_responses(id) ON DELETE CASCADE,
    author_id UUID NOT NULL REFERENCES auth.users(id),
    author_type VARCHAR(20) NOT NULL,
    response_content TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    CONSTRAINT chk_author_type CHECK (author_type IN ('user', 'agent'))
);

CREATE INDEX idx_responses_ticket_id ON ticketing.ticket_responses(ticket_id);
CREATE INDEX idx_responses_author_id ON ticketing.ticket_responses(author_id);
CREATE INDEX idx_responses_created_at ON ticketing.ticket_responses(created_at);
```

---

### Tabla: ticket_attachments

```sql
CREATE TABLE ticketing.ticket_attachments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
    response_id UUID REFERENCES ticketing.ticket_responses(id) ON DELETE CASCADE,
    uploaded_by_user_id UUID NOT NULL REFERENCES auth.users(id),
    file_name VARCHAR(255) NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size_bytes BIGINT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    CONSTRAINT chk_ticket_id_not_null CHECK (ticket_id IS NOT NULL)
);

CREATE INDEX idx_attachments_ticket_id ON ticketing.ticket_attachments(ticket_id);
CREATE INDEX idx_attachments_response_id ON ticketing.ticket_attachments(response_id);
CREATE INDEX idx_attachments_uploaded_by ON ticketing.ticket_attachments(uploaded_by_user_id);
```

---

### Tabla: ticket_ratings

```sql
CREATE TABLE ticketing.ticket_ratings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL UNIQUE REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
    rated_by_user_id UUID NOT NULL REFERENCES auth.users(id),
    rated_agent_id UUID NOT NULL REFERENCES auth.users(id),
    rating INTEGER NOT NULL,
    comment TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    
    CONSTRAINT chk_rating_range CHECK (rating BETWEEN 1 AND 5)
);

CREATE INDEX idx_ratings_ticket_id ON ticketing.ticket_ratings(ticket_id);
CREATE INDEX idx_ratings_rated_agent_id ON ticketing.ticket_ratings(rated_agent_id);
CREATE INDEX idx_ratings_rating ON ticketing.ticket_ratings(rating);
```

---

## 🎯 RESUMEN FINAL

### Características Principales

1. ✅ **23 endpoints activos** en MVP
2. ✅ **4 estados** del ticket (open, pending, resolved, closed)
3. ✅ **3 triggers automáticos** PostgreSQL
4. ✅ **Auto-assignment** del primer agente que responde
5. ✅ **Auto-close** después de 7 días en resolved
6. ✅ **5 ventanas de tiempo** para diferentes operaciones
7. ✅ **3 roles** (USER, AGENT, COMPANY_ADMIN)
8. ✅ **Campo transversal** `last_response_author_type`
9. ✅ **Multi-tenant** con aislamiento por empresa
10. ✅ **JWT stateless** authentication

### Endpoints por Módulo

- 🏷️ Categorías: 4 endpoints
- 🎫 Tickets CRUD: 5 endpoints
- 🔄 Tickets Actions: 4 endpoints
- 💬 Respuestas: 4 endpoints
- 📎 Adjuntos: 3 endpoints
- ⭐ Calificaciones: 3 endpoints

### Listo para Implementación

Este documento contiene **TODA** la información necesaria para implementar el feature completo:

- ✅ Endpoints exactos con métodos HTTP
- ✅ Request bodies completos
- ✅ Response bodies completos con ejemplos
- ✅ Validaciones detalladas
- ✅ Códigos de error específicos
- ✅ Permisos por rol
- ✅ Reglas de negocio
- ✅ Triggers de BD con SQL exacto
- ✅ Esquema de BD completo
- ✅ Índices de performance

---

**FIN DEL DOCUMENTO** 🎉

**Versión**: 1.0 Definitiva  
**Fecha**: 13 Noviembre 2025  
**Autor**: Claude + Lukesito  
**Total Páginas**: [generado automáticamente]
