# 🔍 Análisis de Impacto: COMPANY_ADMIN como Agente

## Resumen Ejecutivo

Este documento analiza **todos los posibles efectos secundarios** de modificar `ResponseService.php` para que COMPANY_ADMIN sea tratado como `author_type='agent'`.

**Veredicto: ⚠️ RIESGO MEDIO-BAJO con 2 problemas menores identificados**

---

## 📋 Cambio Propuesto

```php
// ResponseService.php - determineAuthorType()
// ANTES:
if ($activeRole === 'AGENT') {
    return AuthorType::AGENT;
}

// DESPUÉS:
if (in_array($activeRole, ['AGENT', 'COMPANY_ADMIN'])) {
    return AuthorType::AGENT;
}
```

---

## ✅ Lo que SÍ Funcionará Correctamente

### 1. Trigger SQL `assign_ticket_owner_function()` ✅

```sql
IF NEW.author_type = 'agent' THEN
    UPDATE ticketing.tickets
    SET owner_agent_id = NEW.author_id, ...
```

- ✅ COMPANY_ADMIN será asignado como `owner_agent_id`
- ✅ El estado cambiará a PENDING
- ✅ `first_response_at` se establecerá correctamente
- ✅ `last_response_author_type` será 'agent'

### 2. Trigger SQL `return_pending_to_open_on_user_response()` ✅

```sql
IF NEW.author_type = 'user' THEN
    -- Cambiar status a OPEN
```

- ✅ Solo se activa para author_type='user'
- ✅ No afectado por el cambio

### 3. `SendTicketResponseEmail` Listener ✅

```php
if (!$response->isFromAgent()) {
    return; // NO envía email
}
```

- ✅ COMPANY_ADMIN con `author_type='agent'` → enviará emails al usuario
- ✅ Comportamiento correcto y esperado

### 4. `TicketResponsePolicy` ✅

```php
// Ya incluye COMPANY_ADMIN en múltiples lugares:
if (in_array($activeRole, ['AGENT', 'COMPANY_ADMIN'])) {
    return true;
}
```

- ✅ COMPANY_ADMIN ya puede crear/ver respuestas

### 5. `TicketPolicy` - La mayoría de acciones ✅

| Acción | Ya soporta COMPANY_ADMIN |
|--------|-------------------------|
| view | ✅ Sí |
| update | ✅ Sí |
| delete | ✅ Sí (es el único que puede) |
| assign | ✅ Sí |
| sendReminder | ✅ Sí |
| **resolve** | ❌ **NO - REQUIERE CAMBIO** |
| close | ✅ Sí (vía condicional AGENT) |
| reopen | ✅ Sí (vía condicional AGENT) |

### 6. Scopes del Modelo ✅

```php
// TicketResponse.php
public function scopeByAgents(Builder $query): Builder
{
    return $query->where('author_type', AuthorType::AGENT);
}
```

- ✅ Las respuestas de COMPANY_ADMIN ahora aparecerán en `byAgents()`
- ✅ Esto es **correcto** - queremos que aparezcan ahí

### 7. `TicketRatingPolicy` ✅

- ✅ Ya incluye COMPANY_ADMIN en `view()`
- ✅ El rating se puede crear solo por el creador del ticket

---

## ⚠️ Problemas Identificados (Requieren Cambios Adicionales)

### Problema 1: `TicketPolicy::resolve()` - CRÍTICO

**Archivo:** `app/Features/TicketManagement/Policies/TicketPolicy.php`

```php
public function resolve(User $user, Ticket $ticket): bool
{
    return $user->hasRoleInCompany('AGENT', $ticket->company_id);
    // ❌ COMPANY_ADMIN NO puede resolver tickets actualmente
}
```

**Impacto:** Si COMPANY_ADMIN responde y se convierte en `owner_agent_id`, NO podrá marcar el ticket como RESOLVED.

**Solución Requerida:**
```php
public function resolve(User $user, Ticket $ticket): bool
{
    return $user->hasRoleInCompany('AGENT', $ticket->company_id)
        || $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id);
}
```

---

### Problema 2: `TicketService::assign()` - MEDIO

**Archivo:** `app/Features/TicketManagement/Services/TicketService.php`

```php
public function assign(Ticket $ticket, array $data): Ticket
{
    $newAgent = User::findOrFail($data['new_agent_id']);

    // Validar que el nuevo agente tiene rol AGENT
    if (!$newAgent->hasRoleInCompany('AGENT', $ticket->company_id)) {
        throw new \RuntimeException('INVALID_AGENT_ROLE');
    }
    // ...
}
```

**Impacto:** 
- ❓ Si intentas asignar el ticket a un COMPANY_ADMIN → **fallará**
- ❓ Si un COMPANY_ADMIN ya es `owner_agent_id` por trigger → No puede reasignarse a sí mismo
- ✅ PERO: La asignación vía trigger (respuesta) sí funciona, no usa esta validación

**¿Es un problema real?**
- Si quieres que COMPANY_ADMIN pueda ser **asignado manualmente** → Sí, necesitas cambio
- Si solo quieres que COMPANY_ADMIN pueda responder y auto-asignarse → **No es problema**

**Solución (si es necesaria):**
```php
if (!$newAgent->hasRoleInCompany('AGENT', $ticket->company_id)
    && !$newAgent->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)) {
    throw new \RuntimeException('INVALID_AGENT_ROLE');
}
```

---

## 🔎 Análisis de Tests Existentes

### Tests que Pasarán Sin Cambios ✅

| Test | Estado |
|------|--------|
| `user_can_respond_to_own_ticket` | ✅ No afectado |
| `agent_can_respond_to_any_company_ticket` | ✅ No afectado |
| `first_agent_response_triggers_auto_assignment` | ✅ No afectado |
| `user_cannot_respond_to_other_user_ticket` | ✅ No afectado |
| `agent_cannot_respond_to_other_company_ticket` | ✅ No afectado |
| `user_response_to_pending_ticket_changes_status_to_open` | ✅ No afectado |

### Tests que NO Existen (Deberían Crearse)

- `company_admin_can_respond_to_ticket_as_agent`
- `company_admin_response_triggers_auto_assignment`
- `company_admin_can_resolve_tickets`

---

## 📊 Matriz de Impacto Completa

| Área | Archivo | Cambio Requerido | Riesgo |
|------|---------|-----------------|--------|
| **Determinar author_type** | `ResponseService.php` | ✅ SÍ (principal) | 🟢 BAJO |
| **Resolver tickets** | `TicketPolicy.php` | ✅ SÍ | 🟡 MEDIO |
| **Asignar manualmente** | `TicketService.php` | ❓ OPCIONAL | 🟢 BAJO |
| **Crear respuestas** | `TicketResponsePolicy.php` | ✅ YA SOPORTA | ✅ NINGUNO |
| **Ver tickets** | `TicketPolicy.php` | ✅ YA SOPORTA | ✅ NINGUNO |
| **Enviar emails** | `SendTicketResponseEmail.php` | ✅ YA FUNCIONA | ✅ NINGUNO |
| **Triggers SQL** | Migraciones DB | ✅ YA FUNCIONAN | ✅ NINGUNO |

---

## 🎯 Plan de Implementación Seguro

### Fase 1: Cambios Mínimos Requeridos (2 archivos)

**1. ResponseService.php** (línea 96):
```php
if (in_array($activeRole, ['AGENT', 'COMPANY_ADMIN'])) {
    return AuthorType::AGENT;
}
```

**2. TicketPolicy.php** (línea 98):
```php
public function resolve(User $user, Ticket $ticket): bool
{
    return $user->hasRoleInCompany('AGENT', $ticket->company_id)
        || $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id);
}
```

### Fase 2: Cambios Opcionales (si quieres asignación manual)

**3. TicketService.php** (línea 407):
```php
if (!$newAgent->hasRoleInCompany('AGENT', $ticket->company_id)
    && !$newAgent->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)) {
    throw new \RuntimeException('INVALID_AGENT_ROLE');
}
```

### Fase 3: Tests (recomendado)

Crear nuevos tests:
- `test_company_admin_can_respond_as_agent`
- `test_company_admin_can_resolve_ticket`
- `test_company_admin_response_triggers_pending_status`

---

## 🚨 Lo Que NO Se Rompe

1. ✅ **Usuarios existentes** - Tickets existentes no se ven afectados
2. ✅ **AGENTs actuales** - Siguen funcionando exactamente igual
3. ✅ **Triggers de BD** - No requieren modificación
4. ✅ **Filtros y búsquedas** - Siguen funcionando
5. ✅ **Emails** - Se enviarán correctamente
6. ✅ **Dashboard** - Los filtros `last_response_author_type='agent'` incluirán a COMPANY_ADMIN
7. ✅ **API responses** - `author_type='agent'` es correcto para COMPANY_ADMIN

---

## 🤔 Preguntas para Ti

Antes de implementar, necesito confirmar:

1. **¿El COMPANY_ADMIN debe poder ser asignado manualmente a un ticket?**
   - Sí → Modifica `TicketService::assign()`
   - No → Solo modifica los 2 archivos principales

2. **¿El COMPANY_ADMIN debe poder resolver tickets?**
   - Casi seguro que sí → Ya incluido en el plan

3. **¿Quieres que las respuestas de COMPANY_ADMIN aparezcan como "ADMIN" en el chat?**
   - Sí → Implementar columna `user_role_code` (documentado en `COMPANY_ADMIN_ROLE_DIFFERENTIATION.md`)
   - No → No hacer nada adicional

---

## ✍️ Conclusión

**El cambio es SEGURO** si implementas:

1. **Cambio mínimo en `ResponseService.php`** (1 línea)
2. **Cambio en `TicketPolicy::resolve()`** (1 línea)

**Riesgo real:** 🟢 BAJO

Los triggers SQL no necesitan cambios y la mayor parte del código ya soporta COMPANY_ADMIN.

---

**Documento creado:** 2025-12-13
**Estado:** Análisis completo
**Siguiente paso:** Confirmar preguntas y proceder con implementación
