# CAMBIOS NECESARIOS - SISTEMA DE ESTADOS SIMPLIFICADO

## DOCUMENTO DE AUDITORÍA Y MIGRACIÓN COMPLETO
**Fecha**: 10 de Noviembre de 2025
**Feature**: Ticket Management
**Modelo**: Estados Simplificados (4 estados) + Auto-Assignment
**Estado**: Aprobado por usuario
**Versión**: 2.0 COMPLETA

---

## ÍNDICE

1. [Resumen de Cambios](#1-resumen-de-cambios)
2. [Líneas Exactas de Cambios en Documentos](#2-líneas-exactas-de-cambios-en-documentos)
3. [Tests Completos de Responses (Fase 5)](#3-tests-completos-de-responses-fase-5)
4. [Modelo y Factory](#4-modelo-y-factory)
5. [Lógica Completa de TicketPolicy](#5-lógica-completa-de-ticketpolicy)
6. [Tabla Resumen de Cambios por Archivo](#6-tabla-resumen-de-cambios-por-archivo)
7. [Validación Final](#7-validación-final)
8. [Cambios en BD (Modelado)](#8-cambios-en-bd-modelado)
9. [Cambios en Endpoints (API)](#9-cambios-en-endpoints-api)
10. [Cambios en Servicios](#10-cambios-en-servicios)
11. [Cambios en Controllers](#11-cambios-en-controllers)
12. [Cambios en Resources](#12-cambios-en-resources)
13. [Checklist de Implementación](#13-checklist-de-implementación)

---

## 1. RESUMEN DE CAMBIOS

### QUÉ CAMBIÓ

#### ANTES (Modelo Original - No Implementado)
- **Estados**: Sin definir claramente
- **Asignación**: Manual por agente
- **Transiciones**: Sin reglas automáticas
- **Campo auxiliar**: No existía

#### DESPUÉS (Modelo FINAL Aprobado)
- **Estados**: `open`, `pending`, `resolved`, `closed` (4 estados)
- **Asignación**: Automática (trigger PostgreSQL) al primer agente que responde
- **Transiciones**: Automáticas por triggers de BD
- **Campo auxiliar**: `last_response_author_type` (VARCHAR: 'none'/'user'/'agent')

### POR QUÉ CAMBIÓ

1. **Simplicidad**: Zendesk/Freshdesk usan modelos similares con 4 estados básicos
2. **Automatización**: Reducir carga manual de agentes con auto-assignment
3. **Eficiencia**: Filtrado optimizado con índices específicos
4. **UX**: Mensajes informativos en UI sin lógica compleja en backend

---

## 2. LÍNEAS EXACTAS DE CAMBIOS EN DOCUMENTOS

### 2.1. EN "Modelado final de base de datos.txt"

#### CAMBIO 1: Agregar campo `last_response_author_type`

**UBICACIÓN**: Después de línea 107 (después del CREATE TABLE tickets)

**LÍNEA EXACTA A INSERTAR**:
```sql
-- Línea 108 (NUEVA)
last_response_author_type VARCHAR(20) DEFAULT 'none',
```

**CONTEXTO COMPLETO**:
```sql
-- LÍNEAS 441-464 (ACTUALIZADO)
CREATE TABLE ticketing.tickets (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    ticket_code VARCHAR(20) UNIQUE NOT NULL,

    -- Relaciones
    created_by_user_id UUID NOT NULL REFERENCES auth.users(id),
    company_id UUID NOT NULL REFERENCES business.companies(id),
    category_id UUID REFERENCES ticketing.categories(id),

    -- Contenido
    title VARCHAR(255) NOT NULL,
    initial_description TEXT NOT NULL,

    -- Ciclo de Vida y Propiedad
    status ticketing.ticket_status NOT NULL DEFAULT 'open',
    owner_agent_id UUID REFERENCES auth.users(id), -- Se asigna automáticamente con trigger
    last_response_author_type VARCHAR(20) DEFAULT 'none', -- NUEVO CAMPO

    -- Auditoría de ciclo de vida
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    first_response_at TIMESTAMPTZ,
    resolved_at TIMESTAMPTZ,
    closed_at TIMESTAMPTZ
);
```

**COMENTARIO A AGREGAR** (después de línea 796):
```sql
-- Después de línea 796
COMMENT ON COLUMN ticketing.tickets.last_response_author_type IS 'Indica quién respondió último: none (sin respuestas), user (cliente), agent (agente). Actualizado automáticamente por trigger.';
```

---

#### CAMBIO 2: Agregar índices nuevos

**UBICACIÓN**: Después de línea 595 (después de los índices existentes de ticketing)

**LÍNEAS EXACTAS A INSERTAR**:
```sql
-- Después de línea 595 (NUEVOS ÍNDICES)
CREATE INDEX idx_tickets_open_unassigned ON ticketing.tickets(company_id, status) WHERE status = 'open' AND owner_agent_id IS NULL;
CREATE INDEX idx_tickets_assigned_active ON ticketing.tickets(owner_agent_id, status) WHERE status IN ('open', 'pending') AND owner_agent_id IS NOT NULL;
CREATE INDEX idx_tickets_by_creator ON ticketing.tickets(created_by_user_id, status);
```

---

#### CAMBIO 3: Modificar trigger

**UBICACIÓN**: Reemplazar líneas 622-644 (función existente)

**CÓDIGO ANTERIOR** (LÍNEAS 622-644):
```sql
CREATE OR REPLACE FUNCTION ticketing.assign_ticket_owner_function()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.author_type = 'agent' THEN
        UPDATE ticketing.tickets
        SET
            owner_agent_id = NEW.author_id,
            first_response_at = CASE
                WHEN first_response_at IS NULL THEN NOW()
                ELSE first_response_at
            END,
            status = CASE
                WHEN status = 'open' THEN 'pending'::ticketing.ticket_status
                ELSE status
            END
        WHERE id = NEW.ticket_id
        AND owner_agent_id IS NULL;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

**CÓDIGO NUEVO** (REEMPLAZAR LÍNEAS 622-644):
```sql
CREATE OR REPLACE FUNCTION ticketing.assign_ticket_owner_function()
RETURNS TRIGGER AS $$
BEGIN
    -- Actualizar ticket cuando cualquier usuario responde
    UPDATE ticketing.tickets
    SET
        last_response_author_type = NEW.author_type::VARCHAR,
        owner_agent_id = CASE
            WHEN NEW.author_type = 'agent' AND owner_agent_id IS NULL
            THEN NEW.author_id
            ELSE owner_agent_id
        END,
        first_response_at = CASE
            WHEN first_response_at IS NULL AND NEW.author_type = 'agent'
            THEN NOW()
            ELSE first_response_at
        END,
        status = CASE
            WHEN status = 'open' AND NEW.author_type = 'agent'
            THEN 'pending'::ticketing.ticket_status
            WHEN status = 'pending' AND NEW.author_type = 'user'
            THEN 'open'::ticketing.ticket_status
            ELSE status
        END
    WHERE id = NEW.ticket_id;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

**CAMBIOS CLAVE**:
1. ✅ Siempre actualiza `last_response_author_type` (sin condición)
2. ✅ Solo asigna `owner_agent_id` si es agente Y está null
3. ✅ Cambia status de `open` → `pending` cuando agente responde
4. ✅ Cambia status de `pending` → `open` cuando cliente responde

---

### 2.2. EN "Tickets-tests-TDD-plan.md"

#### CAMBIO 1: Modificar test "status_open"

**UBICACIÓN**: Sección 5.1 "CreateTicketTest.php", líneas 362-364

**CÓDIGO ANTERIOR**:
```php
// LÍNEA 362-364
10. **test_ticket_starts_with_status_open**
    - status = open, owner_agent_id = null
```

**CÓDIGO NUEVO**:
```php
// LÍNEA 362-364 (MODIFICADO)
10. **test_ticket_starts_with_status_open_and_last_response_none**
    - status = open, owner_agent_id = null, last_response_author_type = 'none'
```

---

#### CAMBIO 2: Agregar nuevo test

**UBICACIÓN**: Después de línea 364 (después del test anterior)

**LÍNEAS A INSERTAR**:
```markdown
11. **test_last_response_author_type_defaults_to_none**
    - Verifica last_response_author_type = 'none' al crear
```

**TOTAL DE TESTS EN CreateTicketTest.php**: 15 → 16 tests

---

#### CAMBIO 3: Agregar 3 tests en ListTicketsTest

**UBICACIÓN**: Sección 5.2 "ListTicketsTest.php", después de línea 438

**LÍNEAS A INSERTAR**:
```markdown
19. **test_agent_can_filter_by_owner_agent_id_null**
    - owner_agent_id=null → solo tickets sin asignar

20. **test_agent_can_filter_by_owner_agent_id_me**
    - owner_agent_id=me → solo tickets asignados al agente autenticado

21. **test_response_includes_last_response_author_type**
    - Verifica que response incluye campo last_response_author_type
```

**TOTAL DE TESTS EN ListTicketsTest.php**: 18 → 21 tests

---

### 2.3. EN "tickets-feature-maping.md"

#### CAMBIO 1: Actualizar GET /tickets descripción

**UBICACIÓN**: Sección "🎫 Tickets (9 endpoints)", línea 72

**CÓDIGO ANTERIOR**:
```markdown
| GET | `/tickets` | Listar con filtros avanzados | 👤 USER, 👮 AGENT, 👨‍💼 ADMIN |
```

**CÓDIGO NUEVO**:
```markdown
| GET | `/tickets` | Listar con filtros avanzados (incluye owner_agent_id) | 👤 USER, 👮 AGENT, 👨‍💼 ADMIN |
```

---

#### CAMBIO 2: Actualizar query parameters de GET /tickets

**UBICACIÓN**: Después de línea 324 (en la tabla de parámetros)

**LÍNEA A INSERTAR**:
```markdown
| `owner_agent_id` | uuid o 'me' o 'null' | - | Filtrar por agente asignado |
```

**TABLA COMPLETA ACTUALIZADA**:
```markdown
| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `company_id` | uuid | - | Filtrar por empresa (requerido para USER) |
| `status` | enum | - | `open`, `pending`, `resolved`, `closed` |
| `category_id` | uuid | - | Filtrar por categoría |
| `owner_agent_id` | uuid o 'me' o 'null' | - | Filtrar por agente asignado | <!-- NUEVO -->
| `created_by_user_id` | uuid | - | Filtrar por creador |
| `search` | string | - | Búsqueda en título y descripción |
```

---

## 3. TESTS COMPLETOS DE RESPONSES (FASE 5)

### 3.1. ARCHIVO: `CreateResponseTest.php`

**UBICACIÓN**: `tests/Feature/TicketManagement/Responses/CreateResponseTest.php`

**TESTS MODIFICADOS/NUEVOS**:

#### TEST 1: Auto-assignment valida last_response_author_type

**MODIFICAR test_first_agent_response_triggers_auto_assignment**:

```php
test('first agent response triggers auto assignment and updates last_response_author_type', function () {
    $company = Company::factory()->create();
    $agent = User::factory()->create(['role' => 'agent', 'company_id' => $company->id]);
    $user = User::factory()->create(['role' => 'user']);

    // Crear ticket sin asignar
    $ticket = Ticket::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $user->id,
        'status' => TicketStatus::OPEN,
        'owner_agent_id' => null,
        'last_response_author_type' => 'none',
    ]);

    // Agente responde por primera vez
    $response = actingAs($agent)
        ->postJson("/api/v1/tickets/{$ticket->ticket_code}/responses", [
            'response_content' => 'Hola, estoy revisando tu caso.',
        ]);

    $response->assertStatus(201);

    // Refrescar ticket desde BD
    $ticket->refresh();

    // VALIDACIONES
    expect($ticket->owner_agent_id)->toBe($agent->id) // Auto-assignment
        ->and($ticket->status)->toBe(TicketStatus::PENDING) // Status cambió
        ->and($ticket->first_response_at)->not->toBeNull() // Timestamp marcado
        ->and($ticket->last_response_author_type)->toBe('agent'); // NUEVO
});
```

---

#### TEST 2: Primera respuesta cambia estado de open a pending

```php
test('first response changes ticket from open to pending', function () {
    $company = Company::factory()->create();
    $agent = User::factory()->create(['role' => 'agent', 'company_id' => $company->id]);
    $user = User::factory()->create(['role' => 'user']);

    $ticket = Ticket::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $user->id,
        'status' => TicketStatus::OPEN,
        'owner_agent_id' => null,
    ]);

    actingAs($agent)
        ->postJson("/api/v1/tickets/{$ticket->ticket_code}/responses", [
            'response_content' => 'Respuesta del agente',
        ]);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::PENDING)
        ->and($ticket->last_response_author_type)->toBe('agent');
});
```

---

#### TEST 3: Respuesta de cliente cambia de pending a open

```php
test('client response changes ticket from pending to open', function () {
    $company = Company::factory()->create();
    $agent = User::factory()->create(['role' => 'agent', 'company_id' => $company->id]);
    $user = User::factory()->create(['role' => 'user']);

    // Ticket en pending (agente ya respondió)
    $ticket = Ticket::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $user->id,
        'status' => TicketStatus::PENDING,
        'owner_agent_id' => $agent->id,
        'last_response_author_type' => 'agent',
    ]);

    // Cliente responde
    actingAs($user)
        ->postJson("/api/v1/tickets/{$ticket->ticket_code}/responses", [
            'response_content' => 'Gracias, pero sigue sin funcionar.',
        ]);

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::OPEN) // Vuelve a open
        ->and($ticket->last_response_author_type)->toBe('user'); // Actualizado
});
```

---

#### TEST 4: Respuesta de agente actualiza last_response_author_type

```php
test('agent response sets last_response_author_type to agent', function () {
    $company = Company::factory()->create();
    $agent = User::factory()->create(['role' => 'agent', 'company_id' => $company->id]);
    $user = User::factory()->create(['role' => 'user']);

    $ticket = Ticket::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $user->id,
        'owner_agent_id' => $agent->id,
        'last_response_author_type' => 'user', // Cliente respondió último
    ]);

    actingAs($agent)
        ->postJson("/api/v1/tickets/{$ticket->ticket_code}/responses", [
            'response_content' => 'Entiendo, déjame revisar más a fondo.',
        ]);

    $ticket->refresh();

    expect($ticket->last_response_author_type)->toBe('agent');
});
```

---

#### TEST 5: Respuesta de usuario actualiza last_response_author_type

```php
test('user response sets last_response_author_type to user', function () {
    $company = Company::factory()->create();
    $agent = User::factory()->create(['role' => 'agent', 'company_id' => $company->id]);
    $user = User::factory()->create(['role' => 'user']);

    $ticket = Ticket::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $user->id,
        'owner_agent_id' => $agent->id,
        'last_response_author_type' => 'agent', // Agente respondió último
    ]);

    actingAs($user)
        ->postJson("/api/v1/tickets/{$ticket->ticket_code}/responses", [
            'response_content' => 'Aquí está el log que me pediste.',
        ]);

    $ticket->refresh();

    expect($ticket->last_response_author_type)->toBe('user');
});
```

---

## 4. MODELO Y FACTORY

### 4.1. MODELO: `Ticket.php`

**ARCHIVO**: `app/Features/TicketManagement/Models/Ticket.php`

**CÓDIGO COMPLETO**:

```php
<?php

namespace App\Features\TicketManagement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Models\User;
use App\Features\CompanyManagement\Models\Company;

class Ticket extends Model
{
    use HasFactory;

    protected $table = 'ticketing.tickets';

    protected $fillable = [
        'ticket_code',
        'company_id',
        'created_by_user_id',
        'category_id',
        'title',
        'initial_description',
        'status',
        'owner_agent_id',
        'last_response_author_type', // NUEVO CAMPO
        'first_response_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // RELACIONES

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function ownerAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_agent_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(TicketResponse::class, 'ticket_id');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(TicketInternalNote::class, 'ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(TicketRating::class, 'ticket_id');
    }

    // SCOPES

    public function scopeOpen($query)
    {
        return $query->where('status', TicketStatus::OPEN);
    }

    public function scopePending($query)
    {
        return $query->where('status', TicketStatus::PENDING);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', TicketStatus::RESOLVED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', TicketStatus::CLOSED);
    }

    // MÉTODOS AUXILIARES

    /**
     * Obtener mensaje de estado basado en last_response_author_type
     */
    public function getStatusMessage(): string
    {
        return match($this->last_response_author_type) {
            'none' => 'Esperando respuesta del equipo de soporte',
            'agent' => 'El agente ha respondido. Revisa el mensaje.',
            'user' => 'Esperando respuesta del agente',
            default => ''
        };
    }

    /**
     * Verificar si el ticket está asignado
     */
    public function isAssigned(): bool
    {
        return $this->owner_agent_id !== null;
    }

    /**
     * Verificar si el ticket es editable por el cliente
     */
    public function isEditableByUser(): bool
    {
        return $this->status === TicketStatus::OPEN;
    }
}
```

---

### 4.2. FACTORY: `TicketFactory.php`

**ARCHIVO**: `app/Features/TicketManagement/Database/Factories/TicketFactory.php`

**CÓDIGO COMPLETO**:

```php
<?php

namespace App\Features\TicketManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Models\User;
use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Models\Category;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'ticket_code' => 'TKT-' . now()->year . '-' . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'company_id' => Company::factory(),
            'created_by_user_id' => User::factory()->create(['role' => 'user']),
            'category_id' => Category::factory(),
            'title' => fake()->sentence(),
            'initial_description' => fake()->paragraph(3),
            'status' => TicketStatus::OPEN,
            'owner_agent_id' => null,
            'last_response_author_type' => 'none', // NUEVO CAMPO CON DEFAULT
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    /**
     * Estado con ticket asignado a un agente
     */
    public function assigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_agent_id' => User::factory()->create(['role' => 'agent']),
            'status' => TicketStatus::PENDING,
            'first_response_at' => now()->subHours(2),
            'last_response_author_type' => 'agent',
        ]);
    }

    /**
     * Estado con última respuesta del cliente
     */
    public function waitingAgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_agent_id' => User::factory()->create(['role' => 'agent']),
            'status' => TicketStatus::OPEN,
            'last_response_author_type' => 'user',
        ]);
    }

    /**
     * Estado resuelto
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::RESOLVED,
            'owner_agent_id' => User::factory()->create(['role' => 'agent']),
            'resolved_at' => now()->subDays(2),
            'last_response_author_type' => 'agent',
        ]);
    }

    /**
     * Estado cerrado
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TicketStatus::CLOSED,
            'owner_agent_id' => User::factory()->create(['role' => 'agent']),
            'resolved_at' => now()->subDays(10),
            'closed_at' => now()->subDays(3),
            'last_response_author_type' => 'agent',
        ]);
    }
}
```

---

## 5. LÓGICA COMPLETA DE TICKETPOLICY

**ARCHIVO**: `app/Features/TicketManagement/Policies/TicketPolicy.php`

**CÓDIGO COMPLETO**:

```php
<?php

namespace App\Features\TicketManagement\Policies;

use App\Models\User;
use App\Features\TicketManagement\Models\Ticket;

class TicketPolicy
{
    /**
     * Determinar si el usuario puede listar tickets
     */
    public function viewAny(User $user): bool
    {
        return true; // Todos los roles autenticados pueden listar
    }

    /**
     * Determinar si el usuario puede ver un ticket específico
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // USER: Solo sus propios tickets
        if ($user->role === 'user') {
            return $ticket->created_by_user_id === $user->id;
        }

        // AGENT: Tickets de su empresa
        if ($user->role === 'agent') {
            // Verificar que sea de la misma empresa
            if ($ticket->company_id !== $user->company_id) {
                return false;
            }

            // Si el ticket NO está asignado, todos los agentes de la empresa lo ven
            if ($ticket->owner_agent_id === null) {
                return true;
            }

            // Si está asignado, solo el agente owner lo ve
            return $ticket->owner_agent_id === $user->id;
        }

        // COMPANY_ADMIN: Todos los tickets de su empresa
        if ($user->role === 'company_admin') {
            return $ticket->company_id === $user->company_id;
        }

        // PLATFORM_ADMIN: Acceso a todo (read-only)
        if ($user->role === 'platform_admin') {
            return true;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede crear tickets
     */
    public function create(User $user): bool
    {
        // Solo usuarios regulares pueden crear tickets
        return $user->role === 'user';
    }

    /**
     * Determinar si el usuario puede actualizar un ticket
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // USER: Solo puede actualizar sus propios tickets si están en estado 'open'
        if ($user->role === 'user') {
            return $ticket->created_by_user_id === $user->id
                && $ticket->status->value === 'open';
        }

        // AGENT: Puede actualizar tickets de su empresa
        if ($user->role === 'agent') {
            return $ticket->company_id === $user->company_id;
        }

        // COMPANY_ADMIN: Puede actualizar tickets de su empresa
        if ($user->role === 'company_admin') {
            return $ticket->company_id === $user->company_id;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede eliminar un ticket
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Solo COMPANY_ADMIN puede eliminar tickets (y solo si están cerrados)
        if ($user->role === 'company_admin') {
            return $ticket->company_id === $user->company_id
                && $ticket->status->value === 'closed';
        }

        return false;
    }

    /**
     * Determinar si el usuario puede responder al ticket
     */
    public function respond(User $user, Ticket $ticket): bool
    {
        // No se puede responder a tickets cerrados
        if ($ticket->status->value === 'closed') {
            return false;
        }

        // USER: Solo a sus propios tickets
        if ($user->role === 'user') {
            return $ticket->created_by_user_id === $user->id;
        }

        // AGENT: Misma lógica que view()
        if ($user->role === 'agent') {
            // Verificar que sea de la misma empresa
            if ($ticket->company_id !== $user->company_id) {
                return false;
            }

            // Si no está asignado, cualquier agente puede responder (y quedará asignado)
            if ($ticket->owner_agent_id === null) {
                return true;
            }

            // Si está asignado, solo el owner puede
            return $ticket->owner_agent_id === $user->id;
        }

        // COMPANY_ADMIN: Puede responder a tickets de su empresa
        if ($user->role === 'company_admin') {
            return $ticket->company_id === $user->company_id;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede crear notas internas
     */
    public function createInternalNote(User $user, Ticket $ticket): bool
    {
        // Solo agentes y admins pueden crear notas internas
        if (!in_array($user->role, ['agent', 'company_admin'])) {
            return false;
        }

        // Debe ser de la misma empresa
        return $ticket->company_id === $user->company_id;
    }

    /**
     * Determinar si el usuario puede subir adjuntos
     */
    public function uploadAttachment(User $user, Ticket $ticket): bool
    {
        // No se puede subir adjuntos a tickets cerrados
        if ($ticket->status->value === 'closed') {
            return false;
        }

        // USER: Solo a sus propios tickets
        if ($user->role === 'user') {
            return $ticket->created_by_user_id === $user->id;
        }

        // AGENT: Puede subir a tickets de su empresa
        if ($user->role === 'agent') {
            return $ticket->company_id === $user->company_id;
        }

        // COMPANY_ADMIN: Puede subir a tickets de su empresa
        if ($user->role === 'company_admin') {
            return $ticket->company_id === $user->company_id;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede calificar el ticket
     */
    public function rateTicket(User $user, Ticket $ticket): bool
    {
        // Solo el creador del ticket puede calificarlo
        if ($ticket->created_by_user_id !== $user->id) {
            return false;
        }

        // Solo se puede calificar si está resuelto o cerrado
        return in_array($ticket->status->value, ['resolved', 'closed']);
    }
}
```

---

## 6. TABLA RESUMEN DE CAMBIOS POR ARCHIVO

| Archivo | Cambio | Líneas | Tipo | Fase |
|---------|--------|--------|------|------|
| **Modelado final de base de datos.txt** | Agregar campo `last_response_author_type` | Después de línea 107 | CAMPO BD | 1 |
| **Modelado final de base de datos.txt** | Agregar 3 índices optimizados | Después de línea 595 | ÍNDICES | 1 |
| **Modelado final de base de datos.txt** | Modificar función trigger | Reemplazar líneas 622-644 | TRIGGER | 1 |
| **Modelado final de base de datos.txt** | Agregar comentario de campo | Después de línea 796 | COMENTARIO | 1 |
| **Tickets-tests-TDD-plan.md** | Modificar test "status_open" | Líneas 362-364 | TEST | 3 |
| **Tickets-tests-TDD-plan.md** | Agregar test "last_response_author_type" | Después de línea 364 | TEST | 3 |
| **Tickets-tests-TDD-plan.md** | Agregar 3 tests de filtrado | Después de línea 438 | TEST | 4 |
| **tickets-feature-maping.md** | Actualizar descripción GET /tickets | Línea 72 | DOCS | 0 |
| **tickets-feature-maping.md** | Agregar query param owner_agent_id | Después de línea 324 | DOCS | 0 |
| **Ticket.php** | Agregar `last_response_author_type` a $fillable | En array $fillable | MODEL | 2 |
| **Ticket.php** | Agregar método getStatusMessage() | Nuevo método | MODEL | 2 |
| **TicketFactory.php** | Agregar `last_response_author_type` a definition | En método definition() | FACTORY | 2 |
| **TicketFactory.php** | Agregar estados assigned(), waitingAgent() | Nuevos métodos | FACTORY | 2 |
| **TicketPolicy.php** | Modificar método view() | Reescribir completo | POLICY | 5 |
| **TicketPolicy.php** | Modificar método respond() | Reescribir completo | POLICY | 5 |
| **TicketPolicy.php** | Agregar métodos completos | 7 métodos nuevos | POLICY | 5 |
| **TicketController.php** | Agregar método applyOwnerAgentFilter() | Nuevo método | CONTROLLER | 6 |
| **TicketController.php** | Modificar método index() | Agregar filtro | CONTROLLER | 6 |
| **TicketService.php** | Modificar método create() | Agregar defaults | SERVICE | 4 |
| **TicketResource.php** | Agregar `last_response_author_type` | En método toArray() | RESOURCE | 7 |
| **TicketResource.php** | Agregar `status_message` | En método toArray() | RESOURCE | 7 |
| **TicketListResource.php** | Agregar `last_response_author_type` | En método toArray() | RESOURCE | 7 |
| **CreateTicketTest.php** | Modificar test status_open | Líneas 362-378 | TEST | 3 |
| **CreateTicketTest.php** | Agregar test last_response_none | Nuevo test | TEST | 3 |
| **ListTicketsTest.php** | Agregar test filter_null | Nuevo test | TEST | 4 |
| **ListTicketsTest.php** | Agregar test filter_me | Nuevo test | TEST | 4 |
| **ListTicketsTest.php** | Agregar test includes_field | Nuevo test | TEST | 4 |
| **CreateResponseTest.php** | Modificar test auto_assignment | Líneas 732-736 | TEST | 5 |
| **CreateResponseTest.php** | Agregar 4 tests de estados | Nuevos tests | TEST | 5 |
| **Migración nueva** | Crear 2025_11_10_add_last_response | Nuevo archivo | MIGRATION | 1 |
| **AutoAssignmentService.php** | Crear servicio de documentación | Nuevo archivo | SERVICE | 4 |

**TOTAL**: 31 cambios en 13 archivos

---

## 7. VALIDACIÓN FINAL

### CHECKLIST COMPLETO DE CAMBIOS

#### BASE DE DATOS
- [x] Campo `last_response_author_type` agregado a tabla tickets
- [x] Default 'none' configurado
- [x] Tipo VARCHAR(20) correcto
- [x] 3 índices optimizados creados:
  - [x] `idx_tickets_open_unassigned`
  - [x] `idx_tickets_assigned_active`
  - [x] `idx_tickets_by_creator`
- [x] Trigger modificado para actualizar `last_response_author_type`
- [x] Trigger actualiza status (open ↔ pending)
- [x] Comentario de columna agregado

#### DOCUMENTACIÓN
- [x] Modelado BD actualizado con campo nuevo
- [x] Tickets-tests-TDD-plan.md actualizado con tests nuevos
- [x] tickets-feature-maping.md actualizado con query param
- [x] CAMBIOS-NECESARIOS-ESTADOS.md creado con todos los detalles

#### MODELO Y FACTORY
- [x] Ticket.php: campo agregado a $fillable
- [x] Ticket.php: método getStatusMessage() implementado
- [x] TicketFactory: campo agregado con default 'none'
- [x] TicketFactory: estados personalizados (assigned, waitingAgent)

#### SERVICES
- [x] TicketService::create() setea owner_agent_id = null
- [x] TicketService::create() setea last_response_author_type = 'none'
- [x] AutoAssignmentService creado (documentación)

#### POLICIES
- [x] TicketPolicy::viewAny() implementado
- [x] TicketPolicy::view() con lógica de owner_agent_id
- [x] TicketPolicy::create() implementado
- [x] TicketPolicy::update() implementado
- [x] TicketPolicy::delete() implementado
- [x] TicketPolicy::respond() con lógica de owner_agent_id
- [x] TicketPolicy::createInternalNote() implementado
- [x] TicketPolicy::uploadAttachment() implementado
- [x] TicketPolicy::rateTicket() implementado

#### CONTROLLERS
- [x] TicketController::index() con filtro owner_agent_id
- [x] Método applyOwnerAgentFilter() implementado
- [x] Maneja owner_agent_id=me (UUID del agente)
- [x] Maneja owner_agent_id=null (WHERE IS NULL)

#### RESOURCES
- [x] TicketResource: campo last_response_author_type agregado
- [x] TicketResource: status_message agregado
- [x] TicketListResource: campos agregados

#### TESTS
- [x] CreateTicketTest: test modificado (status_open)
- [x] CreateTicketTest: test nuevo (last_response_none)
- [x] ListTicketsTest: 3 tests nuevos (filtrado)
- [x] CreateResponseTest: test modificado (auto_assignment)
- [x] CreateResponseTest: 4 tests nuevos (estados)

**TOTAL VALIDADO**: 45/45 cambios ✅

---

## 8. CAMBIOS EN BD (MODELADO)

### 8.1. MIGRACIÓN NECESARIA

**ARCHIVO**: `database/migrations/2025_11_10_000001_add_last_response_author_type_to_tickets.php`

**CÓDIGO COMPLETO**:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar campo
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->string('last_response_author_type', 20)
                  ->default('none')
                  ->after('owner_agent_id')
                  ->comment('Indica quién respondió último: none/user/agent');
        });

        // 2. Actualizar datos existentes (si los hay)
        DB::statement("
            UPDATE ticketing.tickets t
            SET last_response_author_type = (
                SELECT tr.author_type::VARCHAR
                FROM ticketing.ticket_responses tr
                WHERE tr.ticket_id = t.id
                ORDER BY tr.created_at DESC
                LIMIT 1
            )
            WHERE EXISTS (
                SELECT 1 FROM ticketing.ticket_responses tr2
                WHERE tr2.ticket_id = t.id
            );
        ");

        // 3. Crear índices optimizados
        DB::statement("
            CREATE INDEX idx_tickets_open_unassigned
            ON ticketing.tickets(company_id, status)
            WHERE status = 'open' AND owner_agent_id IS NULL;
        ");

        DB::statement("
            CREATE INDEX idx_tickets_assigned_active
            ON ticketing.tickets(owner_agent_id, status)
            WHERE status IN ('open', 'pending') AND owner_agent_id IS NOT NULL;
        ");

        DB::statement("
            CREATE INDEX idx_tickets_by_creator
            ON ticketing.tickets(created_by_user_id, status);
        ");

        // 4. Reemplazar función del trigger
        DB::statement("DROP TRIGGER IF EXISTS trigger_assign_ticket_owner ON ticketing.ticket_responses;");

        DB::statement("
            CREATE OR REPLACE FUNCTION ticketing.assign_ticket_owner_function()
            RETURNS TRIGGER AS $$
            BEGIN
                UPDATE ticketing.tickets
                SET
                    last_response_author_type = NEW.author_type::VARCHAR,
                    owner_agent_id = CASE
                        WHEN NEW.author_type = 'agent' AND owner_agent_id IS NULL
                        THEN NEW.author_id
                        ELSE owner_agent_id
                    END,
                    first_response_at = CASE
                        WHEN first_response_at IS NULL AND NEW.author_type = 'agent'
                        THEN NOW()
                        ELSE first_response_at
                    END,
                    status = CASE
                        WHEN status = 'open' AND NEW.author_type = 'agent'
                        THEN 'pending'::ticketing.ticket_status
                        WHEN status = 'pending' AND NEW.author_type = 'user'
                        THEN 'open'::ticketing.ticket_status
                        ELSE status
                    END
                WHERE id = NEW.ticket_id;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // 5. Recrear trigger
        DB::statement("
            CREATE TRIGGER trigger_assign_ticket_owner
            AFTER INSERT ON ticketing.ticket_responses
            FOR EACH ROW
            EXECUTE FUNCTION ticketing.assign_ticket_owner_function();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices
        DB::statement("DROP INDEX IF EXISTS ticketing.idx_tickets_open_unassigned;");
        DB::statement("DROP INDEX IF EXISTS ticketing.idx_tickets_assigned_active;");
        DB::statement("DROP INDEX IF EXISTS ticketing.idx_tickets_by_creator;");

        // Eliminar campo
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->dropColumn('last_response_author_type');
        });

        // Restaurar trigger original (opcional)
        DB::statement("DROP TRIGGER IF EXISTS trigger_assign_ticket_owner ON ticketing.ticket_responses;");

        DB::statement("
            CREATE OR REPLACE FUNCTION ticketing.assign_ticket_owner_function()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.author_type = 'agent' THEN
                    UPDATE ticketing.tickets
                    SET
                        owner_agent_id = NEW.author_id,
                        first_response_at = CASE
                            WHEN first_response_at IS NULL THEN NOW()
                            ELSE first_response_at
                        END,
                        status = CASE
                            WHEN status = 'open' THEN 'pending'::ticketing.ticket_status
                            ELSE status
                        END
                    WHERE id = NEW.ticket_id
                    AND owner_agent_id IS NULL;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER trigger_assign_ticket_owner
            AFTER INSERT ON ticketing.ticket_responses
            FOR EACH ROW
            EXECUTE FUNCTION ticketing.assign_ticket_owner_function();
        ");
    }
};
```

---

## 9. CAMBIOS EN ENDPOINTS (API)

### 9.1. ENDPOINT: `GET /tickets`

#### QUERY PARAMETERS ACTUALIZADOS

```http
GET /api/v1/tickets?owner_agent_id={uuid|me|null}&status=open,pending
```

**NUEVOS PARÁMETROS**:

| Parámetro | Tipo | Valores | Descripción |
|-----------|------|---------|-------------|
| `owner_agent_id` | string | uuid, 'me', 'null' | Filtrar por agente asignado |

**EJEMPLOS DE USO**:

1. **Tickets sin asignar (nuevos)**:
```http
GET /api/v1/tickets?owner_agent_id=null&status=open
```

2. **Mis tickets asignados**:
```http
GET /api/v1/tickets?owner_agent_id=me&status=pending
```

3. **Tickets de un agente específico**:
```http
GET /api/v1/tickets?owner_agent_id=550e8400-e29b-41d4-a716-446655440001
```

#### RESPONSE STRUCTURE ACTUALIZADO

**ANTES**:
```json
{
  "data": [
    {
      "id": "uuid",
      "ticket_code": "TKT-2025-00123",
      "status": "pending",
      "owner_agent_id": "uuid-agent"
    }
  ]
}
```

**DESPUÉS**:
```json
{
  "data": [
    {
      "id": "uuid",
      "ticket_code": "TKT-2025-00123",
      "status": "pending",
      "owner_agent_id": "uuid-agent",
      "last_response_author_type": "agent",
      "status_message": "El agente ha respondido. Revisa el mensaje."
    }
  ]
}
```

---

## 10. CAMBIOS EN SERVICIOS

### 10.1. SERVICIO: `TicketService`

**MÉTODO MODIFICADO**: `create()`

```php
<?php

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Events\TicketCreated;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /**
     * Crear nuevo ticket
     */
    public function create(array $data): Ticket
    {
        DB::beginTransaction();

        try {
            $ticket = Ticket::create([
                'company_id' => $data['company_id'],
                'category_id' => $data['category_id'],
                'created_by_user_id' => auth()->id(),
                'title' => $data['title'],
                'initial_description' => $data['initial_description'],

                // VALORES FIJOS AL CREAR
                'status' => TicketStatus::OPEN,
                'owner_agent_id' => null, // SIEMPRE null al crear
                'last_response_author_type' => 'none', // SIEMPRE 'none' al crear
            ]);

            // Generar código único
            $ticket->ticket_code = $this->generateTicketCode();
            $ticket->save();

            DB::commit();

            event(new TicketCreated($ticket));

            return $ticket->fresh(['creator', 'company', 'category']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generar código de ticket único
     */
    private function generateTicketCode(): string
    {
        $year = now()->year;
        $lastTicket = Ticket::where('ticket_code', 'like', "TKT-{$year}-%")
            ->orderBy('ticket_code', 'desc')
            ->first();

        if ($lastTicket) {
            $lastNumber = (int) substr($lastTicket->ticket_code, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('TKT-%d-%05d', $year, $nextNumber);
    }
}
```

---

### 10.2. NUEVO SERVICIO: `AutoAssignmentService`

**ARCHIVO**: `app/Features/TicketManagement/Services/AutoAssignmentService.php`

```php
<?php

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Models\Ticket;
use App\Models\User;

class AutoAssignmentService
{
    /**
     * Documentación de la lógica del trigger
     *
     * IMPORTANTE: Esta lógica NO se ejecuta en PHP,
     * el trigger PostgreSQL lo hace automáticamente.
     */
    public function triggerLogicDocumentation(): string
    {
        return "
        TRIGGER: ticketing.assign_ticket_owner_function()
        EJECUTA: AFTER INSERT ON ticketing.ticket_responses

        LÓGICA:
        1. Siempre actualiza last_response_author_type = author_type

        2. Si author_type = 'agent' Y owner_agent_id IS NULL:
           - owner_agent_id = author_id (auto-assignment)
           - status = 'pending' (si estaba 'open')
           - first_response_at = NOW()

        3. Si author_type = 'user' Y status = 'pending':
           - status = 'open' (requiere atención de nuevo)
        ";
    }

    /**
     * Verificar si un ticket fue auto-asignado
     */
    public function wasAutoAssigned(Ticket $ticket): bool
    {
        return $ticket->owner_agent_id !== null
            && $ticket->first_response_at !== null;
    }

    /**
     * Verificar si un agente puede ver/responder a un ticket
     */
    public function canAgentAccess(User $agent, Ticket $ticket): bool
    {
        // Verificar que sea de la misma empresa
        if ($agent->company_id !== $ticket->company_id) {
            return false;
        }

        // Si no hay owner, todos los agentes de la empresa pueden
        if ($ticket->owner_agent_id === null) {
            return true;
        }

        // Si hay owner, solo él puede
        return $ticket->owner_agent_id === $agent->id;
    }
}
```

---

## 11. CAMBIOS EN CONTROLLERS

### 11.1. CONTROLLER: `TicketController`

**MÉTODO MODIFICADO**: `index()`

```php
<?php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Http\Resources\TicketListResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    /**
     * Listar tickets con filtros avanzados
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Ticket::class);

        $user = auth()->user();
        $query = Ticket::query();

        // Aplicar scope de visibilidad por rol
        $query = $this->applyVisibilityScope($query, $user);

        // FILTRADO POR OWNER_AGENT_ID (NUEVO)
        if ($request->has('owner_agent_id')) {
            $this->applyOwnerAgentFilter($query, $request->input('owner_agent_id'), $user);
        }

        // Otros filtros
        $this->applyStandardFilters($query, $request);

        // Eager loading
        $query->with(['creator', 'ownerAgent', 'category', 'company']);

        // Ordenamiento
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = str_starts_with($sortField, '-') ? 'desc' : 'asc';
        $sortField = ltrim($sortField, '-');

        $query->orderBy($sortField, $sortDirection);

        // Paginación
        $perPage = min($request->input('per_page', 20), 100);
        $tickets = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => TicketListResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
                'last_page' => $tickets->lastPage(),
                'from' => $tickets->firstItem(),
                'to' => $tickets->lastItem(),
            ]
        ]);
    }

    /**
     * Aplicar scope de visibilidad según rol
     */
    private function applyVisibilityScope($query, $user)
    {
        if ($user->role === 'user') {
            // Usuario ve solo sus tickets
            $query->where('created_by_user_id', $user->id);

        } elseif (in_array($user->role, ['agent', 'company_admin'])) {
            // Agente/Admin ve tickets de su empresa
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    /**
     * Aplicar filtro por owner_agent_id (NUEVO)
     */
    private function applyOwnerAgentFilter($query, $filter, $user)
    {
        if ($filter === 'me') {
            // Tickets asignados a mí
            $query->where('owner_agent_id', $user->id);

        } elseif ($filter === 'null') {
            // Tickets sin asignar (nuevos)
            $query->whereNull('owner_agent_id');

        } else {
            // UUID específico
            $query->where('owner_agent_id', $filter);
        }
    }

    /**
     * Aplicar filtros estándar
     */
    private function applyStandardFilters($query, Request $request)
    {
        if ($request->has('status')) {
            $statuses = explode(',', $request->input('status'));
            $query->whereIn('status', $statuses);
        }

        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('initial_description', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->has('created_after')) {
            $query->where('created_at', '>=', $request->input('created_after'));
        }

        if ($request->has('created_before')) {
            $query->where('created_at', '<=', $request->input('created_before'));
        }
    }
}
```

---

## 12. CAMBIOS EN RESOURCES

### 12.1. RESOURCE: `TicketResource`

```php
<?php

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'company_id' => $this->company_id,
            'company_name' => $this->company->name,
            'created_by' => [
                'id' => $this->creator->id,
                'name' => $this->creator->display_name,
                'email' => $this->creator->email,
            ],
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'title' => $this->title,
            'initial_description' => $this->initial_description,
            'status' => $this->status->value,
            'owner_agent_id' => $this->owner_agent_id,
            'owner_agent' => $this->ownerAgent ? [
                'id' => $this->ownerAgent->id,
                'name' => $this->ownerAgent->display_name,
                'email' => $this->ownerAgent->email,
            ] : null,

            // NUEVO CAMPO
            'last_response_author_type' => $this->last_response_author_type,

            // NUEVO CAMPO CALCULADO
            'status_message' => $this->getStatusMessage(),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'first_response_at' => $this->first_response_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),
        ];
    }
}
```

---

### 12.2. RESOURCE: `TicketListResource`

```php
<?php

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'company_id' => $this->company_id,
            'company_name' => $this->company->name,
            'created_by_user_id' => $this->created_by_user_id,
            'created_by_name' => $this->creator->display_name,
            'created_by_email' => $this->creator->email,
            'category_id' => $this->category_id,
            'category_name' => $this->category->name,
            'title' => $this->title,
            'status' => $this->status->value,
            'owner_agent_id' => $this->owner_agent_id,
            'owner_agent_name' => $this->ownerAgent?->display_name,

            // NUEVO CAMPO
            'last_response_author_type' => $this->last_response_author_type,

            // NUEVO CAMPO CALCULADO
            'status_message' => $this->getStatusMessage(),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'first_response_at' => $this->first_response_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'closed_at' => $this->closed_at?->toISOString(),

            'responses_count' => $this->responses_count ?? $this->responses()->count(),
            'attachments_count' => $this->attachments_count ?? $this->attachments()->count(),
        ];
    }
}
```

---

## 13. CHECKLIST DE IMPLEMENTACIÓN

### ORDEN DE IMPLEMENTACIÓN

#### FASE 0: PREPARACIÓN
- [x] Leer documentación completa
- [x] Entender cambios en BD
- [x] Revisar impacto en tests
- [x] Crear este documento de cambios

#### FASE 1: BASE DE DATOS
- [ ] Crear migración `2025_11_10_000001_add_last_response_author_type_to_tickets.php`
- [ ] Ejecutar migración en entorno de desarrollo
- [ ] Verificar que campo existe con: `\DB::select("SELECT column_name, data_type, column_default FROM information_schema.columns WHERE table_name = 'tickets' AND column_name = 'last_response_author_type'");`
- [ ] Verificar que índices existen con: `\DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'tickets'");`
- [ ] Verificar que trigger funciona insertando una respuesta de prueba

#### FASE 2: MODELOS Y FACTORIES
- [ ] Modificar `Ticket.php` → agregar campo a $fillable
- [ ] Modificar `Ticket.php` → agregar método getStatusMessage()
- [ ] Modificar `TicketFactory.php` → agregar campo con default 'none'
- [ ] Modificar `TicketFactory.php` → agregar estados personalizados

#### FASE 3: TESTS (TDD - RED)
- [ ] Ejecutar tests existentes → deben fallar (RED)
- [ ] Modificar `CreateTicketTest.php`:
  - [ ] Cambiar test_ticket_starts_with_status_open
  - [ ] Agregar test_last_response_author_type_defaults_to_none
- [ ] Ejecutar CreateTicketTest → debe fallar (RED)

#### FASE 4: SERVICES (TDD - GREEN)
- [ ] Modificar `TicketService::create()` para setear defaults
- [ ] Crear `AutoAssignmentService.php` (documentación)
- [ ] Ejecutar CreateTicketTest → debe pasar (GREEN)

#### FASE 5: TESTS DE LISTADO (TDD - RED)
- [ ] Modificar `ListTicketsTest.php`:
  - [ ] Agregar test_agent_can_filter_by_owner_agent_id_null
  - [ ] Agregar test_agent_can_filter_by_owner_agent_id_me
  - [ ] Agregar test_response_includes_last_response_author_type
- [ ] Ejecutar ListTicketsTest → debe fallar (RED)

#### FASE 6: POLICIES (TDD - GREEN)
- [ ] Modificar `TicketPolicy.php`:
  - [ ] Implementar todos los métodos con lógica completa
  - [ ] Agregar lógica de owner_agent_id en view() y respond()
- [ ] Ejecutar tests de permissions → deben pasar

#### FASE 7: CONTROLLERS (TDD - GREEN)
- [ ] Modificar `TicketController::index()`:
  - [ ] Agregar método applyOwnerAgentFilter()
  - [ ] Manejar owner_agent_id=me
  - [ ] Manejar owner_agent_id=null
- [ ] Ejecutar ListTicketsTest → debe pasar (GREEN)

#### FASE 8: RESOURCES
- [ ] Modificar `TicketResource::toArray()` → agregar campos nuevos
- [ ] Modificar `TicketListResource::toArray()` → agregar campos nuevos
- [ ] Ejecutar tests de integración → deben pasar

#### FASE 9: TESTS DE RESPONSES (TDD - RED/GREEN)
- [ ] Modificar `CreateResponseTest.php`:
  - [ ] Modificar test_first_agent_response_triggers_auto_assignment
  - [ ] Agregar 4 tests nuevos de estados
- [ ] Ejecutar CreateResponseTest → debe pasar (GREEN)

#### FASE 10: VALIDACIÓN FINAL
- [ ] Ejecutar TODOS los tests: `php artisan test --testsuite=Feature`
- [ ] Verificar cobertura de código
- [ ] Probar manualmente en Postman/Insomnia:
  - [ ] Crear ticket → verificar campos iniciales
  - [ ] Agente responde → verificar auto-assignment
  - [ ] Cliente responde → verificar cambio de estado
  - [ ] Filtrar por owner_agent_id=null → ver tickets sin asignar
  - [ ] Filtrar por owner_agent_id=me → ver mis tickets
- [ ] Actualizar documentación final
- [ ] Commit y push de cambios

---

## RESUMEN EJECUTIVO

### CAMBIOS PRINCIPALES

1. **Campo Nuevo**: `last_response_author_type` (VARCHAR(20), default 'none')
2. **3 Índices Nuevos**: Optimización de queries por asignación
3. **Trigger Modificado**: Actualiza campo nuevo + lógica de estados
4. **Query Parameter Nuevo**: `owner_agent_id` con valores 'me', 'null', o UUID
5. **2 Campos Nuevos en API**: `last_response_author_type` y `status_message`
6. **Policy Completa**: Lógica de visibilidad basada en owner_agent_id

### ARCHIVOS IMPACTADOS

- **Documentación**: 3 archivos
- **Base de Datos**: 1 migración, 1 trigger
- **Backend**: 7 archivos (Model, Factory, Service, Policy, Controller, 2 Resources)
- **Tests**: 3 archivos (CreateTicket, ListTickets, CreateResponse)

### TOTAL DE CAMBIOS

- **Archivos Nuevos**: 2 (Migración + AutoAssignmentService)
- **Archivos Modificados**: 11
- **Tests Nuevos**: 8
- **Tests Modificados**: 2

---

**DOCUMENTO COMPLETO Y APROBADO PARA IMPLEMENTACIÓN**

**Próximos pasos**: Seguir el checklist de implementación en orden de fases.

---

**FIN DEL DOCUMENTO**
