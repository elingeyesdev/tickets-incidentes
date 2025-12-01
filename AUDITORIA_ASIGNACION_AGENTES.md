# 🔍 Auditoría: Asignación de Agentes a Tickets

**Fecha**: 2025-12-01  
**Objetivo**: Verificar permisos y capacidades de asignación de agentes a tickets  
**Estado**: ⚠️ HALLAZGOS CRÍTICOS ENCONTRADOS

---

## 📋 Preguntas de Auditoría

### 1. ¿Puede un COMPANY_ADMIN asignar agentes a tickets sin asignar?
**Respuesta**: ❌ **NO**

### 2. ¿Pueden los agentes reasignar sus tickets a otros agentes?
**Respuesta**: ✅ **SÍ**

---

## 🔴 HALLAZGOS CRÍTICOS

### **HALLAZGO #1: COMPANY_ADMIN no puede asignar agentes**
**Severidad**: 🔴 **CRÍTICA**  
**Categoría**: Limitación de Funcionalidad

#### Descripción
El rol `COMPANY_ADMIN` **NO** tiene permiso para asignar tickets a agentes, a pesar de que:
- Puede ver todos los tickets de su empresa
- Puede editar tickets de su empresa
- Puede eliminar tickets cerrados
- Tiene privilegios administrativos sobre la empresa

#### Evidencia

**Archivo**: `app/Features/TicketManagement/Policies/TicketPolicy.php`  
**Líneas**: 138-144

```php
/**
 * Asignar ticket: solo AGENT de la compañía.
 */
public function assign(User $user, Ticket $ticket): bool
{
    return $user->hasRoleInCompany('AGENT', $ticket->company_id);
}
```

**Impacto**:
- Un COMPANY_ADMIN **NO** puede asignar tickets sin asignar (con `owner_agent_id = null`)
- Un COMPANY_ADMIN **NO** puede reasignar tickets entre agentes
- Esta limitación reduce la capacidad administrativa del rol

#### Análisis de Consistencia con Otros Permisos

El `TicketPolicy` muestra las siguientes capacidades del COMPANY_ADMIN:

| Acción | AGENT | COMPANY_ADMIN | Código |
|--------|-------|---------------|--------|
| **Ver tickets** | ✅ Tickets de su empresa | ✅ Tickets de su empresa | `view()` líneas 38-60 |
| **Actualizar tickets** | ✅ Tickets de su empresa | ✅ Tickets de su empresa | `update()` líneas 65-82 |
| **Eliminar tickets** | ❌ No permitido | ✅ Si están CLOSED | `delete()` líneas 87-91 |
| **Resolver tickets** | ✅ Permitido | ❌ No permitido | `resolve()` líneas 96-99 |
| **Cerrar tickets** | ✅ Cualquiera de su empresa | ❌ No permitido | `close()` líneas 104-114 |
| **Reabrir tickets** | ✅ Sin restricciones | ❌ No permitido | `reopen()` líneas 120-136 |
| **Asignar tickets** | ✅ Permitido | ❌ **NO PERMITIDO** | `assign()` líneas 141-144 |
| **Enviar recordatorios** | ✅ Permitido | ✅ Permitido | `sendReminder()` líneas 149-153 |

**Observación**: El COMPANY_ADMIN tiene permisos **inconsistentes** - puede editar tickets pero no puede realizar acciones de estado ni asignación, a excepción de poder enviar recordatorios.

---

### **HALLAZGO #2: Los agentes SÍ pueden reasignar tickets**
**Severidad**: ℹ️ **INFORMATIVO** (Funciona según diseño)  
**Categoría**: Confirmación de Funcionalidad

#### Descripción
Los agentes **SÍ** pueden reasignar tickets a otros agentes de la misma empresa.

#### Evidencia

**Archivo**: `tests/Feature/TicketManagement/Tickets/Actions/AssignTicketTest.php`  
**Test**: `agent_can_assign_ticket_to_another_agent()`  
**Líneas**: 68-107

```php
/**
 * Test #1: Agent can assign ticket to another agent
 * 
 * Verifies that an agent can successfully assign a ticket to another agent
 * from the same company. The owner_agent_id should change, but
 * last_response_author_type should NOT change.
 */
#[Test]
public function agent_can_assign_ticket_to_another_agent(): void
{
    // ... código de test que pasa exitosamente
    $response->assertStatus(200);
    $response->assertJsonPath('data.owner_agent_id', $agent2->id);
}
```

**Validaciones implementadas**:
1. ✅ El nuevo agente debe existir en la base de datos
2. ✅ El nuevo agente debe tener rol `AGENT`
3. ✅ El nuevo agente debe pertenecer a la misma empresa que el ticket
4. ✅ Se dispara evento `TicketAssigned`
5. ✅ Se envía notificación al nuevo agente

#### Reglas de Negocio Cumplidas

**Archivo**: `app/Features/TicketManagement/Http/Requests/AssignTicketRequest.php`  
**Líneas**: 22-46

```php
return [
    'new_agent_id' => [
        'required',
        'uuid',
        'exists:users,id',
        function ($attribute, $value, $fail) use ($companyId) {
            $agent = User::find($value);
            if (!$agent) {
                $fail('El agente especificado no existe.');
                return;
            }

            // Validar que tiene rol AGENT en la compañía correcta
            $hasAgentRole = collect($agent->roles)
                ->contains(function ($role) use ($companyId) {
                    return $role->code === 'AGENT' && $role->pivot->company_id === $companyId;
                });

            if (!$hasAgentRole) {
                $fail('El usuario especificado no es un agente de esta compañía.');
            }
        },
    ],
    'note' => 'nullable|string|max:500',
];
```

---

### **HALLAZGO #3: Ruta de asignación sin middleware de rol**
**Severidad**: ⚠️ **MEDIA**  
**Categoría**: Control de Acceso

#### Descripción
La ruta de asignación de tickets **NO** tiene un middleware explícito que restrinja el acceso solo a roles `AGENT` o `COMPANY_ADMIN`. La restricción se realiza únicamente a nivel de **Policy**.

#### Evidencia

**Archivo**: `routes/api.php`  
**Líneas**: 596-598

```php
// Assign ticket to agent (AGENT only, policy-based authorization)
Route::post('/tickets/{ticket}/assign', [\\App\\Features\\TicketManagement\\Http\\Controllers\\TicketActionController::class, 'assign'])
    ->name('tickets.assign');
```

**Comparación con otras rutas**:

```php
// Estas rutas SÍ tienen middleware de rol explícito:
Route::post('/tickets', [TicketController::class, 'store'])
    ->middleware('role:USER')  // ✅ Middleware explícito
    ->name('tickets.store');

Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])
    ->middleware('role:COMPANY_ADMIN')  // ✅ Middleware explícito
    ->name('tickets.destroy');
```

**Impacto**:
- La autorización depende **únicamente** de la Policy
- Si la Policy falla o no se llama, cualquier usuario autenticado podría intentar asignar tickets
- No hay una capa de seguridad adicional a nivel de ruta

#### Controlador

**Archivo**: `app/Features/TicketManagement/Http/Controllers/TicketActionController.php`  
**Líneas**: 685-706

```php
public function assign(Ticket $ticket, TicketActionRequest $request): JsonResponse
{
    $this->authorize('assign', $ticket);  // ✅ Policy sí se está llamando
    
    try {
        $validated = $request->validated();
        $updatedTicket = $this->ticketService->assign($ticket, $validated);

        return response()->json([
            'message' => 'Ticket asignado exitosamente',
            'data' => new TicketResource($updatedTicket),
        ], 200);
    } catch (\\RuntimeException $e) {
        if ($e->getMessage() === 'INVALID_AGENT_ROLE') {
            return response()->json([
                'message' => 'El usuario no tiene rol de agente o pertenece a otra empresa',
            ], 400);
        }

        throw $e;
    }
}
```

**Nota**: El método `authorize()` **SÍ** se está llamando correctamente, por lo que la Policy sí se ejecuta. Sin embargo, seguiría siendo mejor práctica agregar el middleware.

---

### **HALLAZGO #4: Inconsistencia en documentación OpenAPI**
**Severidad**: ℹ️ **BAJA**  
**Categoría**: Documentación

#### Descripción
La documentación OpenAPI del endpoint `/api/tickets/{ticket}/assign` indica:

> **"Only AGENT role users from the ticket's company can assign tickets"**

Sin embargo, esta restricción no está reforzada con un middleware de ruta, solo con la Policy.

**Archivo**: `app/Features/TicketManagement/Http/Controllers/TicketActionController.php`  
**Líneas**: 524-527

```php
#[OA\Post(
    path: '/api/tickets/{ticket}/assign',
    operationId: 'assign_ticket',
    description: 'Assigns a ticket to a specific agent by updating the owner_agent_id field. Only AGENT role users from the ticket\'s company can assign tickets...',
```

---

## 📊 Resumen de Capacidades de Asignación

| Rol | ¿Puede asignar tickets? | Restricciones | Evidencia |
|-----|------------------------|---------------|-----------|
| **USER** | ❌ NO | No tiene acceso a asignación | Test `user_cannot_assign_ticket()` - línea 429 |
| **AGENT** | ✅ SÍ | Solo a agentes de su misma empresa | Policy `assign()` - línea 143, Test línea 68 |
| **COMPANY_ADMIN** | ❌ **NO** | **No tiene permiso en Policy** | Policy `assign()` - línea 143 |
| **PLATFORM_ADMIN** | ❌ NO | No implementado en Policy | Policy `assign()` - línea 143 |

---

## 🎯 Escenarios de Uso Actuales

### ✅ Escenario 1: Agente asigna ticket sin dueño
**Estado**: Funciona correctamente

```
Ticket: TKT-2025-00001
owner_agent_id: null
Company: ACME Corp

Agente-A (ACME Corp) → Asigna a Agente-B
✅ Resultado: owner_agent_id = Agente-B
```

### ✅ Escenario 2: Agente reasigna su propio ticket
**Estado**: Funciona correctamente

```
Ticket: TKT-2025-00002
owner_agent_id: Agente-A
Company: ACME Corp

Agente-A → Reasigna a Agente-B
✅ Resultado: owner_agent_id = Agente-B
```

### ✅ Escenario 3: Agente reasigna ticket de otro agente
**Estado**: Funciona correctamente

```
Ticket: TKT-2025-00003
owner_agent_id: Agente-B
Company: ACME Corp

Agente-A (mismo company) → Reasigna a Agente-C
✅ Resultado: owner_agent_id = Agente-C
```

### ❌ Escenario 4: COMPANY_ADMIN intenta asignar ticket
**Estado**: **NO FUNCIONA** (Prohibido por Policy)

```
Ticket: TKT-2025-00004
owner_agent_id: null
Company: ACME Corp

COMPANY_ADMIN (ACME Corp) → Intenta asignar a Agente-A
❌ Resultado: 403 Forbidden
```

### ❌ Escenario 5: Agente intenta asignar a agente de otra empresa
**Estado**: Validación funciona correctamente

```
Ticket: TKT-2025-00005
owner_agent_id: null
Company: ACME Corp

Agente-A (ACME Corp) → Intenta asignar a Agente-X (Beta Inc)
❌ Resultado: 422 Validation Error
"El usuario especificado no es un agente de esta compañía."
```

---

## 🔧 Archivos Analizados

| Archivo | Propósito | Hallazgos |
|---------|-----------|-----------|
| `app/Features/TicketManagement/Policies/TicketPolicy.php` | Define permisos de acceso | ⚠️ COMPANY_ADMIN excluido de `assign()` |
| `app/Features/TicketManagement/Http/Controllers/TicketActionController.php` | Controlador de acciones | ✅ Llama a Policy correctamente |
| `app/Features/TicketManagement/Http/Requests/AssignTicketRequest.php` | Validación de request | ✅ Validaciones robustas |
| `app/Features/TicketManagement/Services/TicketService.php` | Lógica de negocio | ✅ Implementación correcta |
| `routes/api.php` | Definición de rutas | ⚠️ Falta middleware explícito |
| `tests/Feature/TicketManagement/Tickets/Actions/AssignTicketTest.php` | Pruebas de asignación | ✅ Coverage completo para AGENT |

---

## 💡 Recomendaciones

### Recomendación #1: Habilitar asignación para COMPANY_ADMIN
**Prioridad**: 🔴 **ALTA**

Modificar `TicketPolicy::assign()` para permitir que COMPANY_ADMIN también pueda asignar tickets:

```php
/**
 * Asignar ticket: AGENT o COMPANY_ADMIN de la compañía.
 */
public function assign(User $user, Ticket $ticket): bool
{
    return $user->hasRoleInCompany('AGENT', $ticket->company_id)
        || $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id);
}
```

**Justificación**:
- El COMPANY_ADMIN ya puede ver y editar todos los tickets de su empresa
- Es lógico que pueda asignar tickets a sus agentes
- Mejora la gestión operativa de la empresa

### Recomendación #2: Agregar middleware explícito a la ruta
**Prioridad**: ⚠️ **MEDIA**

Modificar la ruta en `routes/api.php`:

```php
// Assign ticket to agent (AGENT or COMPANY_ADMIN only)
Route::post('/tickets/{ticket}/assign', [TicketActionController::class, 'assign'])
    ->middleware('role:AGENT,COMPANY_ADMIN')
    ->name('tickets.assign');
```

**Justificación**:
- Defense in depth (defensa en profundidad)
- Falla rápido si el usuario no tiene el rol correcto
- No requiere ejecutar Query para verificar Policy
- Consistente con otras rutas del sistema

### Recomendación #3: Actualizar documentación OpenAPI
**Prioridad**: ℹ️ **BAJA**

Actualizar la descripción del endpoint en `TicketActionController`:

```php
description: 'Assigns a ticket to a specific agent by updating the owner_agent_id field. Only AGENT and COMPANY_ADMIN role users from the ticket\'s company can assign tickets...',
```

### Recomendación #4: Agregar tests para COMPANY_ADMIN
**Prioridad**: ⚠️ **MEDIA**

Crear tests adicionales en `AssignTicketTest.php`:

```php
#[Test]
public function company_admin_can_assign_ticket_to_agent(): void
{
    // ... test implementation
}

#[Test]
public function company_admin_can_assign_unassigned_ticket(): void
{
    // ... test implementation
}
```

---

## 📝 Conclusiones

1. **COMPANY_ADMIN NO puede asignar agentes** - Esta es una limitación importante que reduce la utilidad del rol administrativo

2. **Los AGENT SÍ pueden reasignar tickets** - La funcionalidad funciona correctamente con validaciones robustas

3. **Existe una **inconsistencia de permisos** - El COMPANY_ADMIN puede editar tickets pero no puede asignarlos, resolver, cerrar o reabrir

4. **La seguridad actual depende solo de Policies** - Aunque funcional, falta redundancia con middleware de ruta

5. **La implementación para AGENT es sólida** - Tests completos, validaciones robustas, eventos y notificaciones correctamente implementados

---

## 🚦 Estado de Implementación

| Funcionalidad | Estado | Calidad |
|---------------|--------|---------|
| Asignación por AGENT | ✅ Implementado | ⭐⭐⭐⭐⭐ Excelente |
| Asignación por COMPANY_ADMIN | ❌ No implementado | N/A |
| Validaciones de rol y empresa | ✅ Implementado | ⭐⭐⭐⭐⭐ Excelente |
| Tests de asignación | ✅ Implementado | ⭐⭐⭐⭐ Bueno (falta COMPANY_ADMIN) |
| Eventos y notificaciones | ✅ Implementado | ⭐⭐⭐⭐⭐ Excelente |
| Middleware de ruta | ⚠️ Parcial | ⭐⭐ Mejorable |
| Documentación OpenAPI | ✅ Implementado | ⭐⭐⭐ Aceptable |

---

**Fin de la auditoría**  
**Auditor**: Gemini AI  
**Versión del documento**: 1.0
