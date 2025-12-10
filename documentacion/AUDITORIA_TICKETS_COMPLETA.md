# Auditoría Completa del Sistema de Tickets

> **Documento técnico para desarrollo de seeders y comprensión profunda del sistema de ticketing**
>
> Fecha: 2025-12-08
> Feature: `TicketManagement`
> Schema DB: `ticketing`

---

## 📋 Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Estructura de Base de Datos](#estructura-de-base-de-datos)
3. [Enumeraciones (ENUMs)](#enumeraciones-enums)
4. [Modelo Principal: Tickets](#modelo-principal-tickets)
5. [Modelos Relacionados](#modelos-relacionados)
6. [Reglas de Validación](#reglas-de-validación)
7. [Políticas de Autorización](#políticas-de-autorización)
8. [Triggers y Funciones de Base de Datos](#triggers-y-funciones-de-base-de-datos)
9. [Generación de Códigos](#generación-de-códigos)
10. [Ciclo de Vida del Ticket](#ciclo-de-vida-del-ticket)
11. [Información para Seeders](#información-para-seeders)

---

## 1. Resumen Ejecutivo

### Características Principales

- **Multi-tenant**: Todos los tickets pertenecen a una compañía (`company_id`)
- **Role-based**: USER crea, AGENT gestiona, COMPANY_ADMIN administra
- **Auto-asignación**: El primer agente que responde se convierte en `owner_agent_id` (trigger automático)
- **Ciclo de vida**: `open` → `pending` → `resolved` → `closed`
- **Código único**: Formato `TKT-YYYY-NNNNN` (ej: `TKT-2025-00001`)
- **Prioridades**: `low`, `medium`, `high`
- **Adjuntos**: Máximo 5 archivos por ticket, 10MB cada uno
- **Conversación**: Respuestas públicas (user/agent) + notas internas (solo agentes)

### Tecnologías

- **ORM**: Eloquent con Feature-First Architecture
- **DB**: PostgreSQL 17 con schema `ticketing`
- **UUID**: Primary keys en todas las tablas
- **ENUMs**: Tipos nativos de PostgreSQL
- **Triggers**: Asignación automática de agentes
- **Soft Delete**: No utilizado (hard delete en CLOSED)

---

## 2. Estructura de Base de Datos

### Schema: `ticketing`

```sql
CREATE SCHEMA IF NOT EXISTS ticketing;
```

### Tablas Principales

#### 2.1. `ticketing.tickets`

**Descripción**: Centro del sistema de soporte. Contiene toda la información principal del ticket.

```sql
CREATE TABLE ticketing.tickets (
    -- Identificación
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_code VARCHAR(20) UNIQUE NOT NULL,

    -- Relaciones (Foreign Keys)
    created_by_user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,
    company_id UUID NOT NULL REFERENCES business.companies(id) ON DELETE CASCADE,
    category_id UUID REFERENCES ticketing.categories(id) ON DELETE SET NULL,
    area_id UUID REFERENCES business.areas(id) ON DELETE SET NULL,
    owner_agent_id UUID REFERENCES auth.users(id) ON DELETE SET NULL,

    -- Contenido del ticket
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    priority VARCHAR(20) NOT NULL DEFAULT 'medium',

    -- Estado y seguimiento
    status ticketing.ticket_status NOT NULL DEFAULT 'open',
    last_response_author_type VARCHAR(20) DEFAULT 'none',

    -- Timestamps de auditoría
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    first_response_at TIMESTAMPTZ,
    resolved_at TIMESTAMPTZ,
    closed_at TIMESTAMPTZ
);
```

**Índices**:
```sql
CREATE INDEX idx_tickets_company_id_status ON ticketing.tickets(company_id, status);
CREATE INDEX idx_tickets_created_by_user_id ON ticketing.tickets(created_by_user_id);
CREATE INDEX idx_tickets_owner_agent_id ON ticketing.tickets(owner_agent_id) WHERE owner_agent_id IS NOT NULL;
CREATE INDEX idx_tickets_created_at ON ticketing.tickets(created_at DESC);
CREATE INDEX idx_tickets_status ON ticketing.tickets(status) WHERE status IN ('open', 'pending');
CREATE INDEX idx_tickets_category_id ON ticketing.tickets(category_id);
CREATE INDEX idx_tickets_priority ON ticketing.tickets(priority) WHERE priority = 'high';
```

#### 2.2. `ticketing.categories`

**Descripción**: Categorías de tickets personalizadas por empresa.

```sql
CREATE TABLE ticketing.categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID NOT NULL REFERENCES business.companies(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_company_category_name UNIQUE (company_id, name)
);
```

**Índices**:
```sql
CREATE INDEX idx_categories_company_id ON ticketing.categories(company_id);
CREATE INDEX idx_categories_is_active ON ticketing.categories(is_active) WHERE is_active = true;
```

#### 2.3. `ticketing.ticket_responses`

**Descripción**: Conversación pública entre cliente y agentes.

```sql
CREATE TABLE ticketing.ticket_responses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
    author_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,
    content TEXT NOT NULL,
    author_type ticketing.author_type NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
```

**Índices**:
```sql
CREATE INDEX idx_ticket_responses_ticket_id ON ticketing.ticket_responses(ticket_id);
CREATE INDEX idx_ticket_responses_author_id ON ticketing.ticket_responses(author_id);
CREATE INDEX idx_ticket_responses_created_at ON ticketing.ticket_responses(created_at DESC);
CREATE INDEX idx_ticket_responses_author_agent ON ticketing.ticket_responses(author_id) WHERE author_type = 'agent';
```

#### 2.4. `ticketing.ticket_attachments`

**Descripción**: Archivos adjuntos en tickets y respuestas.

```sql
CREATE TABLE ticketing.ticket_attachments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
    response_id UUID REFERENCES ticketing.ticket_responses(id) ON DELETE CASCADE,
    uploaded_by_user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,

    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100),
    file_size_bytes BIGINT,

    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
```

**Índices**:
```sql
CREATE INDEX idx_ticket_attachments_ticket_id ON ticketing.ticket_attachments(ticket_id);
CREATE INDEX idx_ticket_attachments_response_id ON ticketing.ticket_attachments(response_id);
CREATE INDEX idx_ticket_attachments_uploaded_by ON ticketing.ticket_attachments(uploaded_by_user_id);
CREATE INDEX idx_ticket_attachments_created_at ON ticketing.ticket_attachments(created_at DESC);
```

#### 2.5. `ticketing.ticket_ratings`

**Descripción**: Calificaciones de satisfacción del cliente (1-5 estrellas).

```sql
CREATE TABLE ticketing.ticket_ratings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL UNIQUE REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
    rated_by_user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,
    rated_agent_id UUID REFERENCES auth.users(id) ON DELETE SET NULL,

    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,

    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
```

**Índices**:
```sql
CREATE INDEX idx_ticket_ratings_ticket_id ON ticketing.ticket_ratings(ticket_id);
CREATE INDEX idx_ticket_ratings_rated_by_user_id ON ticketing.ticket_ratings(rated_by_user_id);
CREATE INDEX idx_ticket_ratings_agent_id ON ticketing.ticket_ratings(rated_agent_id);
CREATE INDEX idx_ticket_ratings_rating ON ticketing.ticket_ratings(rating);
CREATE INDEX idx_ticket_ratings_created_at ON ticketing.ticket_ratings(created_at DESC);
CREATE INDEX idx_ticket_views_by_agent_rating ON ticketing.ticket_ratings(rated_agent_id, rating) WHERE rating >= 4;
```

#### 2.6. `ticketing.ticket_internal_notes`

**Descripción**: Notas privadas entre agentes (NO visibles para el cliente).

```sql
CREATE TABLE ticketing.ticket_internal_notes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
    agent_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,
    note_content TEXT NOT NULL,

    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
```

**Índices**:
```sql
CREATE INDEX idx_ticket_internal_notes_ticket_id ON ticketing.ticket_internal_notes(ticket_id);
CREATE INDEX idx_ticket_internal_notes_agent_id ON ticketing.ticket_internal_notes(agent_id);
CREATE INDEX idx_ticket_internal_notes_created_at ON ticketing.ticket_internal_notes(created_at DESC);
```

---

## 3. Enumeraciones (ENUMs)

### 3.1. `ticketing.ticket_status`

**Ubicación**: `app/Features/TicketManagement/Enums/TicketStatus.php`

```php
enum TicketStatus: string
{
    case OPEN = 'open';       // Recién creado, sin respuesta de agente
    case PENDING = 'pending'; // Con al menos una respuesta de agente
    case RESOLVED = 'resolved'; // Marcado como solucionado
    case CLOSED = 'closed';   // Cerrado definitivamente
}
```

**Definición en PostgreSQL**:
```sql
CREATE TYPE ticketing.ticket_status AS ENUM (
    'open',
    'pending',
    'resolved',
    'closed'
);
```

**Métodos del Enum**:
- `values()`: Retorna `['open', 'pending', 'resolved', 'closed']`
- `isActive()`: `true` si no está `CLOSED`
- `isEditableByUser()`: `true` solo si es `OPEN`
- `canBeReopened()`: `true` si es `RESOLVED` o `CLOSED`
- `canBeRated()`: `true` si es `RESOLVED` o `CLOSED`
- `canReceiveResponses()`: `true` si no es `CLOSED`

### 3.2. `ticketing.ticket_priority`

**Ubicación**: `app/Features/TicketManagement/Enums/TicketPriority.php`

```php
enum TicketPriority: string
{
    case LOW = 'low';       // Baja prioridad
    case MEDIUM = 'medium'; // Prioridad media (default)
    case HIGH = 'high';     // Alta prioridad
}
```

**Definición en PostgreSQL**:
```sql
CREATE TYPE ticketing.ticket_priority AS ENUM ('low', 'medium', 'high');
```

**Métodos del Enum**:
- `values()`: Retorna `['low', 'medium', 'high']`
- `isHigh()`: `true` si es `HIGH`
- `order()`: Peso numérico (LOW=1, MEDIUM=2, HIGH=3)
- `label()`: Label legible ('Baja', 'Media', 'Alta')

### 3.3. `ticketing.author_type`

**Ubicación**: `app/Features/TicketManagement/Enums/AuthorType.php`

```php
enum AuthorType: string
{
    case USER = 'user';   // Cliente/usuario final
    case AGENT = 'agent'; // Agente de soporte
}
```

**Definición en PostgreSQL**:
```sql
CREATE TYPE ticketing.author_type AS ENUM ('user', 'agent');
```

**Métodos del Enum**:
- `values()`: Retorna `['user', 'agent']`
- `isAgent()`: `true` si es `AGENT`
- `isUser()`: `true` si es `USER`
- `fromRole(string $role)`: Convierte rol a AuthorType

---

## 4. Modelo Principal: Tickets

### 4.1. Campos del Modelo

| Campo | Tipo | Nullable | Default | Descripción |
|-------|------|----------|---------|-------------|
| `id` | UUID | NO | `gen_random_uuid()` | Primary key |
| `ticket_code` | VARCHAR(20) | NO | - | Código único (TKT-YYYY-NNNNN) |
| `created_by_user_id` | UUID | NO | - | Usuario creador (FK → auth.users) |
| `company_id` | UUID | NO | - | Compañía propietaria (FK → business.companies) |
| `category_id` | UUID | YES | NULL | Categoría del ticket (FK → ticketing.categories) |
| `area_id` | UUID | YES | NULL | Área relacionada (FK → business.areas) |
| `owner_agent_id` | UUID | YES | NULL | Agente asignado (FK → auth.users) |
| `title` | VARCHAR(255) | NO | - | Título del ticket |
| `description` | TEXT | NO | - | Descripción detallada |
| `priority` | VARCHAR(20) | NO | `'medium'` | Prioridad (low/medium/high) |
| `status` | ticket_status | NO | `'open'` | Estado actual |
| `last_response_author_type` | VARCHAR(20) | NO | `'none'` | Tipo del último autor ('none', 'user', 'agent') |
| `created_at` | TIMESTAMPTZ | NO | `CURRENT_TIMESTAMP` | Fecha de creación |
| `updated_at` | TIMESTAMPTZ | NO | `CURRENT_TIMESTAMP` | Última actualización |
| `first_response_at` | TIMESTAMPTZ | YES | NULL | Primera respuesta de agente (SLA) |
| `resolved_at` | TIMESTAMPTZ | YES | NULL | Fecha de resolución |
| `closed_at` | TIMESTAMPTZ | YES | NULL | Fecha de cierre |

### 4.2. Relaciones Eloquent

```php
// BelongsTo
$ticket->creator()      // User que creó el ticket
$ticket->company()      // Company propietaria
$ticket->category()     // Category del ticket
$ticket->area()         // Area relacionada
$ticket->ownerAgent()   // User agente asignado

// HasMany
$ticket->responses()        // TicketResponse[]
$ticket->attachments()      // TicketAttachment[]
$ticket->internalNotes()    // TicketInternalNote[]

// HasOne
$ticket->rating()          // TicketRating
```

### 4.3. Scopes Disponibles

```php
Ticket::open()              // WHERE status = 'open'
Ticket::pending()           // WHERE status = 'pending'
Ticket::resolved()          // WHERE status = 'resolved'
Ticket::closed()            // WHERE status = 'closed'
Ticket::active()            // WHERE status IN ('open', 'pending', 'resolved')
Ticket::byCompany($id)      // WHERE company_id = $id
Ticket::createdBy($userId)  // WHERE created_by_user_id = $userId
Ticket::ownedBy($agentId)   // WHERE owner_agent_id = $agentId
Ticket::byArea($areaId)     // WHERE area_id = $areaId
Ticket::byPriority($p)      // WHERE priority = $p
```

### 4.4. Métodos de Negocio

```php
$ticket->canBeEditedByCreator()  // true si status = OPEN
$ticket->canReceiveResponses()   // true si status != CLOSED
$ticket->canBeRated()            // true si RESOLVED/CLOSED y sin rating
$ticket->canBeReopened()         // true si RESOLVED/CLOSED
$ticket->canBeDeleted()          // true solo si CLOSED
```

---

## 5. Modelos Relacionados

### 5.1. Category

**Tabla**: `ticketing.categories`
**Modelo**: `App\Features\TicketManagement\Models\Category`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| `id` | UUID | NO | Primary key |
| `company_id` | UUID | NO | Compañía propietaria |
| `name` | VARCHAR(100) | NO | Nombre (único por empresa) |
| `description` | TEXT | YES | Descripción opcional |
| `is_active` | BOOLEAN | NO | Estado activo/inactivo |
| `created_at` | TIMESTAMPTZ | NO | Fecha de creación |

**Constraint**: `UNIQUE(company_id, name)` - Nombres únicos por empresa

**Scopes**:
- `active()`: WHERE is_active = true
- `byCompany($id)`: WHERE company_id = $id

### 5.2. TicketResponse

**Tabla**: `ticketing.ticket_responses`
**Modelo**: `App\Features\TicketManagement\Models\TicketResponse`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| `id` | UUID | NO | Primary key |
| `ticket_id` | UUID | NO | Ticket propietario |
| `author_id` | UUID | NO | Usuario autor |
| `content` | TEXT | NO | Contenido de la respuesta |
| `author_type` | author_type | NO | 'user' o 'agent' |
| `created_at` | TIMESTAMPTZ | NO | Fecha de creación |
| `updated_at` | TIMESTAMPTZ | NO | Última actualización |

**Scopes**:
- `byAgents()`: WHERE author_type = 'agent'
- `byUsers()`: WHERE author_type = 'user'
- `byTicket($id)`: WHERE ticket_id = $id

**Métodos**:
- `canBeEdited()`: true si < 30 minutos y ticket != CLOSED
- `isFromAgent()`: true si author_type = AGENT
- `isFromUser()`: true si author_type = USER

### 5.3. TicketAttachment

**Tabla**: `ticketing.ticket_attachments`
**Modelo**: `App\Features\TicketManagement\Models\TicketAttachment`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| `id` | UUID | NO | Primary key |
| `ticket_id` | UUID | NO | Ticket propietario |
| `response_id` | UUID | YES | Respuesta asociada (opcional) |
| `uploaded_by_user_id` | UUID | NO | Usuario que subió |
| `file_name` | VARCHAR(255) | NO | Nombre original del archivo |
| `file_path` | VARCHAR(500) | NO | Path en storage |
| `file_type` | VARCHAR(100) | YES | Tipo MIME |
| `file_size_bytes` | BIGINT | YES | Tamaño en bytes |
| `created_at` | TIMESTAMPTZ | NO | Fecha de subida |

**Scopes**:
- `byTicket($id)`: WHERE ticket_id = $id
- `ticketLevel()`: WHERE response_id IS NULL
- `responseLevel()`: WHERE response_id IS NOT NULL

**Métodos**:
- `canBeDeleted()`: true si < 30 minutos y ticket != CLOSED
- `isAttachedToResponse()`: true si response_id != NULL
- `getFileUrlAttribute()`: accessor para compatibilidad
- `getFormattedSizeAttribute()`: tamaño legible (ej: "2.5 MB")

### 5.4. TicketRating

**Tabla**: `ticketing.ticket_ratings`
**Modelo**: `App\Features\TicketManagement\Models\TicketRating`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| `id` | UUID | NO | Primary key |
| `ticket_id` | UUID | NO | Ticket calificado (UNIQUE) |
| `rated_by_user_id` | UUID | NO | Cliente que calificó |
| `rated_agent_id` | UUID | YES | Agente calificado (snapshot histórico) |
| `rating` | INT | NO | Calificación 1-5 estrellas |
| `comment` | TEXT | YES | Comentario opcional |
| `created_at` | TIMESTAMPTZ | NO | Fecha de calificación |

**Constraint**: `CHECK (rating BETWEEN 1 AND 5)`

**Scopes**:
- `byAgent($id)`: WHERE rated_agent_id = $id
- `positive()`: WHERE rating >= 4
- `negative()`: WHERE rating <= 2
- `neutral()`: WHERE rating = 3

**Métodos**:
- `canBeUpdated()`: true si < 24 horas
- `isPositive()`: rating >= 4
- `isNegative()`: rating <= 2
- `getStarsAttribute()`: string visual (⭐⭐⭐⭐⭐)

### 5.5. TicketInternalNote

**Tabla**: `ticketing.ticket_internal_notes`
**Modelo**: `App\Features\TicketManagement\Models\TicketInternalNote`

| Campo | Tipo | Nullable | Descripción |
|-------|------|----------|-------------|
| `id` | UUID | NO | Primary key |
| `ticket_id` | UUID | NO | Ticket propietario |
| `agent_id` | UUID | NO | Agente autor |
| `note_content` | TEXT | NO | Contenido de la nota |
| `created_at` | TIMESTAMPTZ | NO | Fecha de creación |
| `updated_at` | TIMESTAMPTZ | NO | Última actualización |

**Nota**: Solo visible para agentes, NO para clientes.

---

## 6. Reglas de Validación

### 6.1. Crear Ticket (StoreTicketRequest)

**Endpoint**: `POST /api/tickets`
**Autorización**: Solo rol `USER`

```php
[
    'title' => 'required|string|min:5|max:200',
    'description' => 'required|string|min:10|max:2000',
    'company_id' => 'required|uuid|exists:companies,id',
    'category_id' => [
        'required',
        'uuid',
        'exists:categories,id',
        'must_be_active',
        'must_belong_to_company'
    ],
    'priority' => 'sometimes|required|string|in:low,medium,high',
    'area_id' => [
        'nullable',
        'uuid',
        'exists:areas,id',
        'must_be_active',
        'must_belong_to_company'
    ]
]
```

### 6.2. Actualizar Ticket (UpdateTicketRequest)

**Endpoint**: `PATCH /api/tickets/{ticket}`
**Autorización**: Policy-based (creador si OPEN, o agent/admin de la empresa)

```php
[
    'title' => 'sometimes|required|string|min:5|max:200',
    'category_id' => [
        'sometimes',
        'required',
        'uuid',
        'must_be_active',
        'must_belong_to_same_company'
    ],
    'priority' => 'sometimes|required|string|in:low,medium,high',
    'area_id' => [
        'sometimes',
        'nullable',
        'uuid',
        'must_be_active',
        'must_belong_to_same_company'
    ]
]
```

### 6.3. Crear Respuesta (StoreResponseRequest)

**Endpoint**: `POST /api/tickets/{ticket}/responses`
**Autorización**: Policy-based (creador o agent de la empresa)

```php
[
    'content' => 'required|string|min:1|max:5000'
]
```

### 6.4. Subir Adjunto (UploadAttachmentRequest)

**Endpoint**: `POST /api/tickets/{ticket}/attachments`
**Autorización**: Policy-based (creador o agent, ticket != CLOSED)

```php
[
    'file' => [
        'required',
        'file',
        'max:10240', // 10MB en KB
        new ValidFileType()
    ],
    'response_id' => [
        'sometimes',
        'uuid',
        'exists:ticket_responses,id'
    ]
]
```

**Tipos de archivo permitidos** (ValidFileType):
```php
const ALLOWED_TYPES = [
    // Documentos (8)
    'pdf', 'txt', 'log', 'doc', 'docx', 'xls', 'xlsx', 'csv',
    // Imágenes (7)
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
    // Videos (1)
    'mp4'
];
```

**Límites adicionales**:
- Máximo **5 archivos por ticket**
- Tamaño máximo por archivo: **10 MB**

### 6.5. Asignar Agente (AssignTicketRequest)

**Endpoint**: `POST /api/tickets/{ticket}/assign`
**Autorización**: AGENT o COMPANY_ADMIN de la empresa

```php
[
    'new_agent_id' => [
        'required',
        'uuid',
        'exists:users,id',
        'must_have_agent_role_in_company'
    ],
    'note' => 'nullable|string|max:500'
]
```

### 6.6. Crear Categoría (StoreCategoryRequest)

**Endpoint**: `POST /api/tickets/categories`
**Autorización**: Solo rol `COMPANY_ADMIN`

```php
[
    'name' => [
        'required',
        'string',
        'min:3',
        'max:100',
        'unique:categories,name,company_id={active_company}'
    ],
    'description' => 'nullable|string|max:500',
    'company_id' => 'prohibited' // Se toma del JWT
]
```

### 6.7. Reabrir Ticket (ReopenTicketRequest)

**Endpoint**: `POST /api/tickets/{ticket}/reopen`
**Autorización**: Policy-based (creador con restricción 30 días, o agent)

```php
[
    'reopen_reason' => 'required|string|max:5000',
    'can_reopen' => [
        new CanReopenTicket() // Valida límite de 30 días para USER
    ]
]
```

---

## 7. Políticas de Autorización

**Ubicación**: `app/Features/TicketManagement/Policies/TicketPolicy.php`

### 7.1. Matriz de Permisos

| Acción | USER (creador) | USER (no creador) | AGENT (misma empresa) | COMPANY_ADMIN | PLATFORM_ADMIN |
|--------|----------------|-------------------|----------------------|---------------|----------------|
| `create` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `view` | ✅ | ❌ | ✅ | ✅ | ✅ |
| `update` | ✅ (solo OPEN) | ❌ | ✅ | ✅ | ❌ |
| `delete` | ❌ | ❌ | ❌ | ✅ (solo CLOSED) | ❌ |
| `resolve` | ❌ | ❌ | ✅ | ❌ | ❌ |
| `close` | ✅ (solo RESOLVED) | ❌ | ✅ (cualquier estado) | ✅ | ❌ |
| `reopen` | ✅ (≤30 días) | ❌ | ✅ (sin límite) | ✅ | ❌ |
| `assign` | ❌ | ❌ | ✅ | ✅ | ❌ |
| `sendReminder` | ❌ | ❌ | ✅ | ✅ | ❌ |

### 7.2. Reglas Especiales

**Eliminar Ticket** (`delete`):
- Solo `COMPANY_ADMIN` de la misma empresa
- Ticket debe estar en estado `CLOSED`
- Es un **hard delete** (no soft delete)

**Reabrir Ticket** (`reopen`):
- **USER (creador)**: Solo si ticket está `RESOLVED` o `CLOSED` hace ≤ 30 días
- **AGENT**: Sin restricción de tiempo
- Ticket pasa a estado `PENDING`
- Se limpian `resolved_at` y `closed_at`

**Cerrar Ticket** (`close`):
- **USER (creador)**: Solo si ticket está `RESOLVED`
- **AGENT**: Puede cerrar en cualquier estado
- Se registra `closed_at`

---

## 8. Triggers y Funciones de Base de Datos

### 8.1. Función: `assign_ticket_owner_function()`

**Ubicación**: Migración `2025_11_05_000002_create_ticket_categories_table.php`

**Propósito**: Asignar automáticamente el `owner_agent_id` al primer agente que responde y actualizar `last_response_author_type`.

```sql
CREATE OR REPLACE FUNCTION ticketing.assign_ticket_owner_function()
RETURNS TRIGGER AS $$
BEGIN
    -- Si el que responde es un agente
    IF NEW.author_type = 'agent' THEN
        -- Asignar owner_agent_id solo si el ticket no tiene owner
        UPDATE ticketing.tickets
        SET
            owner_agent_id = NEW.author_id,
            first_response_at = CASE
                WHEN first_response_at IS NULL THEN NOW()
                ELSE first_response_at
            END,
            status = 'pending'::ticketing.ticket_status,
            last_response_author_type = 'agent'
        WHERE id = NEW.ticket_id
        AND owner_agent_id IS NULL;

        -- Si el ticket ya tiene owner, solo actualizar last_response_author_type
        UPDATE ticketing.tickets
        SET last_response_author_type = 'agent'
        WHERE id = NEW.ticket_id
        AND owner_agent_id IS NOT NULL;

    ELSIF NEW.author_type = 'user' THEN
        -- Si responde un usuario, solo actualizar last_response_author_type
        UPDATE ticketing.tickets
        SET last_response_author_type = 'user'
        WHERE id = NEW.ticket_id;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

### 8.2. Trigger: `trigger_assign_ticket_owner`

**Tabla**: `ticketing.ticket_responses`
**Evento**: `AFTER INSERT`

```sql
CREATE TRIGGER trigger_assign_ticket_owner
AFTER INSERT ON ticketing.ticket_responses
FOR EACH ROW EXECUTE FUNCTION ticketing.assign_ticket_owner_function();
```

**Comportamiento**:
1. Cuando un AGENT responde por primera vez:
   - `owner_agent_id` = author_id del agente
   - `first_response_at` = NOW()
   - `status` = 'pending'
   - `last_response_author_type` = 'agent'

2. Cuando un AGENT responde (ya asignado):
   - `last_response_author_type` = 'agent'

3. Cuando un USER responde:
   - `last_response_author_type` = 'user'

### 8.3. Trigger: `update_updated_at_column()`

**Tablas afectadas**:
- `ticketing.tickets`
- `ticketing.ticket_responses`
- `ticketing.ticket_internal_notes`

**Función**: `public.update_updated_at_column()` (definida en migraciones globales)

**Comportamiento**: Actualiza automáticamente `updated_at = CURRENT_TIMESTAMP` en cada UPDATE.

---

## 9. Generación de Códigos

### 9.1. CodeGenerator

**Ubicación**: `app/Shared/Helpers/CodeGenerator.php`

**Formato**: `PREFIX-YYYY-NNNNN`

**Constantes**:
```php
CodeGenerator::TICKET = 'TKT';
CodeGenerator::TICKET_RESPONSE = 'RSP';
CodeGenerator::CATEGORY = 'CAT';
```

**Uso para Tickets**:
```php
$ticketCode = CodeGenerator::generate(
    table: 'ticketing.tickets',
    prefix: CodeGenerator::TICKET,
    column: 'ticket_code'
);
// Resultado: TKT-2025-00001
```

**Características**:
- Secuencial por año (reinicia cada año)
- Padding de 5 dígitos (00001 - 99999)
- Consulta el último código del año en BD
- Thread-safe con transacciones

**Ejemplo de Secuencia**:
```
TKT-2025-00001
TKT-2025-00002
TKT-2025-00003
...
TKT-2026-00001  ← Reinicia en nuevo año
```

---

## 10. Ciclo de Vida del Ticket

### 10.1. Diagrama de Estados

```
┌──────┐
│ OPEN │ ← Usuario crea ticket (status default)
└──┬───┘
   │
   │ (Agente responde por primera vez)
   │ Trigger: owner_agent_id = agent, first_response_at = NOW()
   ↓
┌─────────┐
│ PENDING │ ← Ticket con al menos 1 respuesta de agente
└────┬────┘
     │
     │ (Agente marca como resuelto)
     │ API: POST /api/tickets/{ticket}/resolve
     ↓
┌──────────┐
│ RESOLVED │ ← Problema solucionado, resolved_at = NOW()
└────┬─────┘
     │
     │ (Usuario o agente cierra)
     │ API: POST /api/tickets/{ticket}/close
     ↓
┌────────┐
│ CLOSED │ ← Cerrado definitivamente, closed_at = NOW()
└────────┘
     │
     │ (Usuario reabre ≤30 días, o agente sin límite)
     │ API: POST /api/tickets/{ticket}/reopen
     └──────┐
            │
            ↓
      ┌─────────┐
      │ PENDING │ (regresa a PENDING)
      └─────────┘
```

### 10.2. Transiciones de Estado

| Desde | A | Quién | Condiciones | Endpoint |
|-------|---|-------|-------------|----------|
| OPEN | PENDING | Trigger | Primera respuesta de AGENT | `POST /api/tickets/{ticket}/responses` |
| OPEN | CLOSED | AGENT | - | `POST /api/tickets/{ticket}/close` |
| PENDING | RESOLVED | AGENT | - | `POST /api/tickets/{ticket}/resolve` |
| RESOLVED | CLOSED | USER/AGENT | - | `POST /api/tickets/{ticket}/close` |
| RESOLVED | PENDING | USER/AGENT | Reopen | `POST /api/tickets/{ticket}/reopen` |
| CLOSED | PENDING | USER/AGENT | Reopen, USER ≤30 días | `POST /api/tickets/{ticket}/reopen` |

### 10.3. Campos de Auditoría

```php
created_at        → Fecha de creación del ticket
first_response_at → Primera respuesta de AGENT (SLA)
resolved_at       → Fecha que se marcó como RESOLVED
closed_at         → Fecha de cierre final
updated_at        → Última modificación (trigger automático)
```

---

## 11. Información para Seeders

### 11.1. Datos Mínimos Requeridos

#### Para crear un Ticket básico:

```php
[
    'ticket_code' => 'TKT-2025-00001', // CodeGenerator::generate()
    'created_by_user_id' => $userId,   // UUID válido de auth.users (rol USER)
    'company_id' => $companyId,        // UUID válido de business.companies
    'category_id' => $categoryId,      // UUID válido de ticketing.categories
    'title' => 'Error al exportar reporte',
    'description' => 'Cuando intento exportar el reporte mensual...',
    'priority' => 'medium',            // low/medium/high
    'area_id' => null,                 // Opcional
    'status' => 'open',                // open/pending/resolved/closed
    'owner_agent_id' => null,          // NULL inicial (se asigna con trigger)
    'last_response_author_type' => 'none', // none/user/agent
    'created_at' => now(),
    'updated_at' => now(),
    'first_response_at' => null,
    'resolved_at' => null,
    'closed_at' => null,
]
```

#### Para crear una Category:

```php
[
    'id' => Str::uuid(),
    'company_id' => $companyId,
    'name' => 'Soporte Técnico',          // Único por empresa
    'description' => 'Problemas técnicos...',
    'is_active' => true,
    'created_at' => now(),
]
```

#### Para crear una Response:

```php
[
    'id' => Str::uuid(),
    'ticket_id' => $ticketId,
    'author_id' => $userId,               // Usuario o agente
    'content' => 'Gracias por contactarnos...',
    'author_type' => 'agent',             // user/agent
    'created_at' => now(),
    'updated_at' => now(),
]
```

**⚠️ IMPORTANTE**: Al insertar una respuesta con `author_type = 'agent'`, el trigger automáticamente:
- Asigna `owner_agent_id` (si es NULL)
- Cambia `status` a `'pending'`
- Actualiza `first_response_at`

#### Para crear un Attachment:

```php
[
    'id' => Str::uuid(),
    'ticket_id' => $ticketId,
    'response_id' => null,                      // Opcional
    'uploaded_by_user_id' => $userId,
    'file_name' => 'screenshot.png',
    'file_path' => 'tickets/attachments/1731774123_screenshot.png',
    'file_type' => 'png',
    'file_size_bytes' => 245760,
    'created_at' => now(),
]
```

#### Para crear un Rating:

```php
[
    'id' => Str::uuid(),
    'ticket_id' => $ticketId,                   // UNIQUE
    'rated_by_user_id' => $customerId,
    'rated_agent_id' => $agentId,               // Snapshot histórico
    'rating' => 5,                              // 1-5
    'comment' => 'Excelente atención',
    'created_at' => now(),
]
```

### 11.2. Títulos y Descripciones Realistas

**Títulos de ejemplo**:
```php
$titles = [
    'No puedo acceder a mi cuenta',
    'Error al exportar reportes a Excel',
    'Problema con reseteo de contraseña',
    'Consulta sobre facturación',
    'El sistema está lento',
    'No recibo notificaciones por email',
    'Error 500 al crear nuevo usuario',
    'Duda sobre permisos de agentes',
    'Problema de conexión con la base de datos',
    'No puedo subir archivos adjuntos',
];
```

**Descripciones de ejemplo**:
```php
$descriptions = [
    'Hola, necesito ayuda urgente con este problema. He intentado varias veces pero no funciona. ¿Pueden ayudarme?',
    'Buenos días, vengo experimentando este inconveniente desde ayer. Adjunto capturas de pantalla.',
    'Estimados, por favor necesito asistencia con este tema. Es importante para nuestro trabajo diario.',
    'Hola equipo de soporte, tengo la siguiente consulta: ',
];
```

### 11.3. Categorías Típicas

```php
$categories = [
    ['name' => 'Soporte Técnico', 'description' => 'Problemas técnicos con el sistema'],
    ['name' => 'Facturación', 'description' => 'Consultas sobre pagos y facturación'],
    ['name' => 'Cuenta y Accesos', 'description' => 'Problemas de login, permisos, etc.'],
    ['name' => 'Reportes', 'description' => 'Problemas con generación de reportes'],
    ['name' => 'General', 'description' => 'Consultas generales'],
];
```

### 11.4. Distribución de Prioridades

```php
// Distribución recomendada
'low' => 30%     // Baja prioridad
'medium' => 55%  // Media (default)
'high' => 15%    // Alta prioridad
```

### 11.5. Distribución de Estados

```php
// Para seeders realistas
'open' => 20%      // Tickets sin responder
'pending' => 50%   // Tickets en progreso
'resolved' => 20%  // Tickets resueltos
'closed' => 10%    // Tickets cerrados
```

### 11.6. Factory: TicketFactory

**Ubicación**: `app/Features/TicketManagement/Database/Factories/TicketFactory.php`

**Métodos de estado**:
```php
Ticket::factory()->create();                    // OPEN por defecto
Ticket::factory()->pending()->create();         // PENDING con agente
Ticket::factory()->resolved()->create();        // RESOLVED
Ticket::factory()->closed()->create();          // CLOSED
Ticket::factory()->forCompany($id)->create();   // Empresa específica
Ticket::factory()->createdBy($userId)->create();// Usuario específico
Ticket::factory()->ownedBy($agentId)->create(); // Agente específico
Ticket::factory()->inCategory($catId)->create();// Categoría específica
Ticket::factory()->withPriority(TicketPriority::HIGH)->create();
Ticket::factory()->inArea($areaId)->create();   // Área específica
Ticket::factory()->old(30)->create();           // Ticket antiguo (30 días)
```

### 11.7. Restricciones Importantes

1. **Categorías**: Deben estar `is_active = true` para ser usadas
2. **Áreas**: Deben estar `is_active = true` para ser usadas
3. **Agentes**: Deben tener rol `AGENT` en la misma empresa del ticket
4. **Rating**: Solo 1 por ticket (constraint UNIQUE en ticket_id)
5. **Archivos adjuntos**: Máximo 5 por ticket
6. **Tamaño de archivos**: Máximo 10MB cada uno
7. **Estados válidos**: Solo `open`, `pending`, `resolved`, `closed`
8. **Prioridades válidas**: Solo `low`, `medium`, `high`

### 11.8. Ejemplo Completo de Seeder

```php
// 1. Crear categorías
$categories = Category::factory()
    ->count(5)
    ->forCompany($companyId)
    ->create();

// 2. Crear tickets en diferentes estados
$ticketsOpen = Ticket::factory()
    ->count(10)
    ->forCompany($companyId)
    ->createdBy($userId)
    ->inCategory($categories->random()->id)
    ->create(); // OPEN por defecto

$ticketsPending = Ticket::factory()
    ->count(20)
    ->pending()
    ->forCompany($companyId)
    ->createdBy($userId)
    ->inCategory($categories->random()->id)
    ->create();

// 3. Agregar respuestas (esto activa el trigger)
foreach ($ticketsPending as $ticket) {
    TicketResponse::factory()
        ->count(rand(1, 5))
        ->create([
            'ticket_id' => $ticket->id,
            'author_type' => fake()->randomElement(['user', 'agent']),
        ]);
}

// 4. Agregar adjuntos
foreach ($ticketsOpen as $ticket) {
    TicketAttachment::factory()
        ->count(rand(0, 3))
        ->create(['ticket_id' => $ticket->id]);
}

// 5. Agregar ratings para tickets resueltos
$ticketsResolved = Ticket::factory()
    ->count(15)
    ->resolved()
    ->forCompany($companyId)
    ->create();

foreach ($ticketsResolved as $ticket) {
    TicketRating::factory()->create([
        'ticket_id' => $ticket->id,
        'rated_by_user_id' => $ticket->created_by_user_id,
        'rated_agent_id' => $ticket->owner_agent_id,
    ]);
}
```

---

## 📊 Resumen de Constraints y Límites

| Entidad | Constraint | Valor |
|---------|-----------|-------|
| Ticket | `ticket_code` | UNIQUE (global) |
| Ticket | `title` | min:5, max:200 |
| Ticket | `description` | min:10, max:2000 |
| Ticket | `priority` | enum: low/medium/high |
| Ticket | `status` | enum: open/pending/resolved/closed |
| Category | `(company_id, name)` | UNIQUE (por empresa) |
| Category | `name` | min:3, max:100 |
| Category | `description` | max:500 |
| Response | `content` | min:1, max:5000 |
| Attachment | MAX per ticket | 5 archivos |
| Attachment | MAX file size | 10 MB (10240 KB) |
| Attachment | Allowed types | 16 tipos (pdf, jpg, png, etc.) |
| Rating | `rating` | CHECK (1-5) |
| Rating | `ticket_id` | UNIQUE (1 rating por ticket) |

---

## 🔗 Referencias Clave

### Archivos de Código

- **Modelo**: `app/Features/TicketManagement/Models/Ticket.php`
- **Enums**: `app/Features/TicketManagement/Enums/`
- **Migraciones**: `app/Features/TicketManagement/Database/Migrations/`
- **Factories**: `app/Features/TicketManagement/Database/Factories/`
- **Policies**: `app/Features/TicketManagement/Policies/TicketPolicy.php`
- **Requests**: `app/Features/TicketManagement/Http/Requests/`
- **CodeGenerator**: `app/Shared/Helpers/CodeGenerator.php`

### Rutas API

- Lista de tickets: `GET /api/tickets`
- Crear ticket: `POST /api/tickets`
- Ver ticket: `GET /api/tickets/{ticket_code}`
- Actualizar ticket: `PATCH /api/tickets/{ticket_code}`
- Eliminar ticket: `DELETE /api/tickets/{ticket_code}`
- Resolver ticket: `POST /api/tickets/{ticket_code}/resolve`
- Cerrar ticket: `POST /api/tickets/{ticket_code}/close`
- Reabrir ticket: `POST /api/tickets/{ticket_code}/reopen`
- Asignar agente: `POST /api/tickets/{ticket_code}/assign`

---

**Documento generado**: 2025-12-08
**Versión Laravel**: 12
**Versión PostgreSQL**: 17
**Feature**: TicketManagement
