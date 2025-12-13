# 🎫 Auditoría Completa: Sistema de Tickets y Triggers

## Resumen Ejecutivo

Este documento audita todo el sistema de tickets, los triggers entre USER y AGENT, cómo cambian los estados, y cómo integrar al **COMPANY_ADMIN como agente funcional**.

---

## 📊 Arquitectura Actual

### Roles del Sistema

| Rol | Código | Contexto | Descripción |
|-----|--------|----------|-------------|
| **PLATFORM_ADMIN** | `platform_admin` | Sin empresa | Administrador de la plataforma (ve todo) |
| **COMPANY_ADMIN** | `company_admin` | `company_id` requerido | Administrador de empresa específica |
| **AGENT** | `agent` | `company_id` requerido | Agente de soporte de empresa |
| **USER** | `user` | Sin empresa específica | Cliente que crea tickets |

### Estados del Ticket (TicketStatus Enum)

```
open → pending → resolved → closed
```

| Estado | Descripción | Quién puede modificar |
|--------|-------------|----------------------|
| **OPEN** | Ticket recién creado, sin respuesta de agente | USER puede editar |
| **PENDING** | Tiene respuesta de agente (auto-asignado) | AGENT puede resolver |
| **RESOLVED** | Marcado como solucionado por agente | USER puede cerrar/reabrir |
| **CLOSED** | Cerrado definitivamente | Solo COMPANY_ADMIN puede eliminar |

### AuthorType Enum

```php
enum AuthorType: string
{
    case USER = 'user';   // Respuesta del cliente
    case AGENT = 'agent'; // Respuesta del agente
}
```

**Mapeo actual:**
```php
public static function fromRole(string $role): self
{
    return match($role) {
        'agent', 'company_admin' => self::AGENT,  // ⚠️ COMPANY_ADMIN ya mapeado como AGENT
        default => self::USER,
    };
}
```

---

## 🔄 Flujo de Estados y Triggers

### Trigger 1: `assign_ticket_owner_function()` (Migración 000002)

**Ubicación:** `app/Features/TicketManagement/Database/Migrations/2025_11_05_000002_create_ticket_categories_table.php`

**Ejecuta cuando:** Se inserta una nueva respuesta en `ticketing.ticket_responses`

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
            status = 'pending'::ticketing.ticket_status,  -- 🔴 CAMBIA ESTADO
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

**Comportamiento:**
- ✅ Si `author_type='agent'` y ticket sin owner → Asigna owner, cambia status a PENDING
- ✅ Si `author_type='agent'` y ticket con owner → Solo actualiza `last_response_author_type`
- ✅ Si `author_type='user'` → Solo actualiza `last_response_author_type`

---

### Trigger 2: `return_pending_to_open_on_user_response()` (Migración 000009)

**Ubicación:** `app/Features/TicketManagement/Database/Migrations/2025_11_05_000009_add_state_transitions_and_indexes_to_tickets.php`

```sql
CREATE OR REPLACE FUNCTION ticketing.return_pending_to_open_on_user_response()
RETURNS TRIGGER AS $$
BEGIN
    -- Si es respuesta de usuario Y el ticket está PENDING, cambiar a OPEN
    IF NEW.author_type = 'user' THEN
        UPDATE ticketing.tickets
        SET status = 'open'::ticketing.ticket_status
        WHERE id = NEW.ticket_id
        AND status = 'pending'::ticketing.ticket_status;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

**Comportamiento:**
- ✅ Si USER responde a ticket PENDING → Vuelve a OPEN

---

## 🔑 Determinación del `author_type`

### ResponseService.php (Lógica actual)

**Ubicación:** `app/Features/TicketManagement/Services/ResponseService.php`

```php
private function determineAuthorType(User $user): AuthorType
{
    // MIGRADO: Usar el rol ACTIVO del usuario
    $activeRole = JWTHelper::getActiveRoleCode();
    
    if ($activeRole === 'AGENT') {
        return AuthorType::AGENT;
    }

    // De lo contrario, es USER
    return AuthorType::USER;
}
```

### ⚠️ PROBLEMA IDENTIFICADO

**El COMPANY_ADMIN NO está siendo tratado como AGENT:**

```php
// Código actual - SOLO verifica AGENT
if ($activeRole === 'AGENT') {
    return AuthorType::AGENT;
}
// COMPANY_ADMIN cae aquí y es tratado como USER ❌
return AuthorType::USER;
```

---

## 📋 Políticas de Autorización Actuales

### TicketPolicy.php

| Acción | Regla |
|--------|-------|
| **create** | Solo USER puede crear tickets |
| **view** | Creador, AGENT/COMPANY_ADMIN de la compañía, PLATFORM_ADMIN |
| **update** | Creador solo si OPEN, AGENT/COMPANY_ADMIN de la compañía |
| **delete** | Solo COMPANY_ADMIN y ticket debe estar CLOSED |
| **resolve** | Solo AGENT de la compañía |
| **close** | AGENT puede cerrar cualquiera, USER solo si RESOLVED |
| **reopen** | Creador (con restricción 30 días), AGENT sin restricción |
| **assign** | AGENT o COMPANY_ADMIN de la compañía |
| **sendReminder** | AGENT o COMPANY_ADMIN de la compañía |

### TicketResponsePolicy.php

| Acción | Regla |
|--------|-------|
| **create** | Creador del ticket, AGENT o COMPANY_ADMIN con rol activo |
| **viewAny** | Creador del ticket, AGENT o COMPANY_ADMIN con rol activo |
| **update** | Solo autor dentro de 30 minutos |
| **delete** | Solo autor dentro de 30 minutos |

---

## 🚨 Problemas Detectados para COMPANY_ADMIN como Agente

### Problema 1: `determineAuthorType()` no reconoce COMPANY_ADMIN

**Impacto:**
- ❌ Las respuestas de COMPANY_ADMIN se guardan como `author_type='user'`
- ❌ El trigger NO asigna al COMPANY_ADMIN como owner
- ❌ El trigger NO cambia el estado a PENDING
- ❌ El chat muestra "USER" en vez de "ADMIN"

### Problema 2: TicketPolicy.resolve() solo permite AGENT

```php
public function resolve(User $user, Ticket $ticket): bool
{
    return $user->hasRoleInCompany('AGENT', $ticket->company_id);
    // ❌ COMPANY_ADMIN NO puede resolver tickets
}
```

### Problema 3: Trigger solo verifica `author_type='agent'`

El trigger SQL no diferencia entre AGENT y COMPANY_ADMIN, solo usa el `author_type` que viene del código PHP.

---

## ✅ Cambios Necesarios para COMPANY_ADMIN como Agente

### Cambio 1: ResponseService.php - Actualizar `determineAuthorType()`

**Archivo:** `app/Features/TicketManagement/Services/ResponseService.php`

```php
private function determineAuthorType(User $user): AuthorType
{
    $activeRole = JWTHelper::getActiveRoleCode();
    
    // COMPANY_ADMIN y AGENT deben ser tratados como AGENT
    if (in_array($activeRole, ['AGENT', 'COMPANY_ADMIN'])) {
        return AuthorType::AGENT;
    }

    return AuthorType::USER;
}
```

**Impacto:**
- ✅ COMPANY_ADMIN se guarda como `author_type='agent'`
- ✅ El trigger asignará al COMPANY_ADMIN como owner
- ✅ El trigger cambiará el estado a PENDING
- ✅ El chat mostrará correctamente que es respuesta de "agente"

---

### Cambio 2: TicketPolicy.php - Permitir resolve/close a COMPANY_ADMIN

**Archivo:** `app/Features/TicketManagement/Policies/TicketPolicy.php`

```php
public function resolve(User $user, Ticket $ticket): bool
{
    // AGENT o COMPANY_ADMIN de la compañía puede resolver
    return $user->hasRoleInCompany('AGENT', $ticket->company_id)
        || $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id);
}
```

**Opcional:** También actualizar `close()` para ser explícito:

```php
public function close(User $user, Ticket $ticket): bool
{
    // AGENT o COMPANY_ADMIN de la compañía puede cerrar cualquiera
    if ($user->hasRoleInCompany('AGENT', $ticket->company_id) 
        || $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)) {
        return true;
    }

    // Creador solo puede cerrar si está RESOLVED
    return $ticket->created_by_user_id === $user->id
        && $ticket->status === TicketStatus::RESOLVED;
}
```

---

### Cambio 3: (Opcional) Agregar columna `user_role_code`

Para diferenciar visualmente COMPANY_ADMIN de AGENT en el chat/emails:

**Nueva migración:**
```php
Schema::table('ticketing.ticket_responses', function (Blueprint $table) {
    $table->string('user_role_code')->nullable()->after('author_type');
});
```

**ResponseService.php:**
```php
$response = TicketResponse::create([
    'ticket_id' => $ticket->id,
    'author_id' => $user->id,
    'content' => $data['content'],
    'author_type' => $authorType->value,
    'user_role_code' => JWTHelper::getActiveRoleCode(), // 'AGENT', 'COMPANY_ADMIN', 'USER'
]);
```

---

## 📊 Matriz de Cambios Completa

| Archivo | Cambio | Prioridad | Impacto |
|---------|--------|-----------|---------|
| `ResponseService.php` | Agregar COMPANY_ADMIN a `determineAuthorType()` | 🔴 CRÍTICO | Triggers funcionarán |
| `TicketPolicy.php` | Agregar COMPANY_ADMIN a `resolve()` | 🔴 CRÍTICO | Puede resolver tickets |
| `TicketPolicy.php` | Agregar COMPANY_ADMIN a `close()` | 🟠 ALTO | Explícito, ya funciona vía AGENT |
| Nueva migración | `user_role_code` column | 🟢 OPCIONAL | Mejor UI/UX |
| `TicketResponseResource.php` | Agregar `user_role_code` | 🟢 OPCIONAL | API devuelve rol |
| Chat component | Mostrar rol correcto | 🟢 OPCIONAL | UI diferencia roles |

---

## 🔄 Diagrama de Flujo Actualizado

```
[USER crea ticket]
       ↓
   status: OPEN
   owner_agent_id: NULL
       ↓
[AGENT/COMPANY_ADMIN responde]
       ↓
   → author_type = 'agent' (código PHP)
       ↓
   → TRIGGER: assign_ticket_owner_function()
       - owner_agent_id = autor
       - status = PENDING
       - last_response_author_type = 'agent'
       ↓
[USER responde]
       ↓
   → author_type = 'user'
       ↓
   → TRIGGER: return_pending_to_open_on_user_response()
       - status = OPEN (vuelve de PENDING)
       - last_response_author_type = 'user'
       ↓
[AGENT/COMPANY_ADMIN resuelve]
       ↓
   → status = RESOLVED
   → resolved_at = NOW()
       ↓
[USER o AGENT cierra]
       ↓
   → status = CLOSED
   → closed_at = NOW()
```

---

## 🧪 Tests a Actualizar

### Nuevos tests requeridos:

1. `test_company_admin_can_respond_to_ticket_as_agent`
2. `test_company_admin_response_triggers_pending_status`
3. `test_company_admin_can_be_assigned_as_owner`
4. `test_company_admin_can_resolve_tickets`
5. `test_company_admin_can_close_any_ticket`

### Tests existentes afectados:

- `company_admin_cannot_create_ticket` ✅ (no cambiar)
- `company_admin_can_view_any_ticket_from_own_company` ✅ (ya funciona)
- `company_admin_can_delete_closed_ticket` ✅ (ya funciona)
- `company_admin_can_assign_ticket_to_agent` ✅ (ya funciona)
- `company_admin_can_send_reminder` ✅ (ya funciona)

---

## 📁 Archivos Clave

```
app/Features/TicketManagement/
├── Enums/
│   ├── TicketStatus.php          # Estados: open, pending, resolved, closed
│   └── AuthorType.php            # Tipos: user, agent
├── Services/
│   ├── TicketService.php         # CRUD de tickets
│   └── ResponseService.php       # ❌ REQUIERE CAMBIO
├── Policies/
│   ├── TicketPolicy.php          # ❌ REQUIERE CAMBIO (resolve)
│   └── TicketResponsePolicy.php  # ✅ Ya incluye COMPANY_ADMIN
├── Models/
│   ├── Ticket.php                # Modelo de ticket
│   └── TicketResponse.php        # Modelo de respuesta
├── Database/
│   └── Migrations/
│       ├── 2025_11_05_000002_create_ticket_categories_table.php  # assign_ticket_owner_function()
│       └── 2025_11_05_000009_add_state_transitions_and_indexes_to_tickets.php  # return_pending_to_open
└── Http/Controllers/
    ├── TicketController.php
    └── TicketResponseController.php  # Usa ResponseService
```

---

## 📝 Resumen de Acciones

### Inmediatas (CRÍTICO):

1. **ResponseService.php:** Agregar `'COMPANY_ADMIN'` a la condición de `determineAuthorType()`
2. **TicketPolicy.php:** Agregar `'COMPANY_ADMIN'` a `resolve()` 

### Opcionales (MEJORA):

3. Nueva migración para `user_role_code`
4. Actualizar `TicketResponseResource` para incluir `user_role_code`
5. Actualizar componente de chat para mostrar rol específico

---

## ⚡ Implementación Rápida

### Cambio 1 (2 líneas):
```php
// ResponseService.php línea 96
if (in_array($activeRole, ['AGENT', 'COMPANY_ADMIN'])) {
```

### Cambio 2 (1 línea):
```php
// TicketPolicy.php línea 98
return $user->hasRoleInCompany('AGENT', $ticket->company_id)
    || $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id);
```

---

**Documento creado:** 2025-12-13
**Estado:** Auditoría completa
**Próximo paso:** Implementar cambios críticos
