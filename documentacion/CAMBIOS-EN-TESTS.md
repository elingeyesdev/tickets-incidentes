# 📋 CAMBIOS Y MEJORAS IDENTIFICADAS EN TESTS TDD

> **Fecha**: 2025-11-13
> **Propósito**: Documento de síntesis para sincronizar tests → documentación
> **Estado**: ANÁLISIS COMPLETO

---

## 📌 RESUMEN EJECUTIVO

Se han identificado **10 patrones principales** de mejoras/cambios implementados en los tests que mejoran significativamente la calidad de la API:

1. Campo transversal `last_response_author_type`
2. State machine: OPEN → PENDING → OPEN
3. Auto-assignment en primer agente response
4. Triggers automáticos de status
5. Ventanas de tiempo (30 min, 30 días)
6. Permisos complejos por rol y estado
7. Límite de 5 attachments global
8. Query params mejorados (`owner_agent_id=null`, `created_by=me`)
9. Validaciones de integridad referencial
10. Eventos y notificaciones transversales

---

## 🔄 CAMBIOS POR CATEGORÍA

### 1. CAMPO TRANSVERSAL: `last_response_author_type`

**Impacto**: ⭐⭐⭐⭐⭐ CRÍTICO - Afecta TODO el sistema

**Descripción**:
- Campo STRING en tabla `tickets`
- Valores: `'none'`, `'user'`, `'agent'`
- Inicialmente `'none'` para tickets nuevos
- Se actualiza automáticamente cuando hay respuestas (trigger PostgreSQL)
- **NUNCA cambia** durante acciones: resolve, close, reopen, assign
- Se usa para filtrar tickets en listados

**Documentos afectados**:
- ✅ Plan TDD: Agregar a sección de campos críticos
- ✅ Mapping: Agregar a tabla de campos `tickets`
- ✅ Respuestas: Agregar sección de transiciones de este campo

**Tests relacionados**: 18 tests en CreateResponseTest

**Cambios en tests**:
```
CreateTicketTest #16: Valida inicialización en 'none'
CreateResponseTest #16-23: 8 tests dedicados a transiciones
ListTicketsTest #22-26: 5 tests para filtros
ResolveTicketTest #11: Valida que persiste
CloseTicketTest #11: Valida que persiste
ReopenTicketTest #13: Valida que persiste
AssignTicketTest #1: Valida que persiste
```

---

### 2. STATE MACHINE: OPEN → PENDING → OPEN

**Impacto**: ⭐⭐⭐⭐⭐ CRÍTICO - Lógica core de tickets

**Descripción**:
```
OPEN (Nuevo)
    ↓ (PRIMER agente responde)
PENDING (Esperando cliente)
    ↓ (Usuario responde)
OPEN (Cliente respondió)
    ↓ (Agente marca resuelto)
RESOLVED
    ↓ (Cierre manual/auto 7 días)
CLOSED
```

**Cambio clave**: **PENDING→OPEN cuando usuario responde**
- Antes no existía esta transición automática
- Ahora es un TRIGGER PostgreSQL

**Documentos afectados**:
- ✅ Plan TDD: Actualizar diagrama de estados
- ✅ Mapping: Agregar sección de triggers

**Tests relacionados**:
```
CreateResponseTest #16: user_response_to_pending_ticket_changes_status_to_open
CreateResponseTest #21: pending_to_open_transition_preserves_owner_agent_id
CreateResponseTest #22: user_response_to_open_ticket_does_not_change_status
```

---

### 3. AUTO-ASSIGNMENT EN PRIMER RESPONSE DE AGENTE

**Impacto**: ⭐⭐⭐⭐⭐ CRÍTICO - Automación core

**Descripción**:
- Cuando el PRIMER agente responde a un ticket OPEN con `owner_agent_id = NULL`
- Automáticamente se ejecuta trigger que:
  1. Asigna el ticket al agente (`owner_agent_id = agent_id`)
  2. Cambia status: OPEN → PENDING
  3. Marca `first_response_at` con timestamp
  4. Actualiza `last_response_author_type = 'agent'`

**Cambio clave**: Es 100% automático via TRIGGER, no lógica en PHP

**Documentos afectados**:
- ✅ Plan TDD: Agregar trigger SQL específico
- ✅ Mapping: Agregar trigger en sección de BD

**Tests relacionados**:
```
CreateResponseTest #6: first_agent_response_triggers_auto_assignment
CreateResponseTest #7: auto_assignment_only_happens_once
CreateResponseTest #8: first_agent_response_sets_first_response_at
CreateResponseTest #17: user_response_to_pending_ticket_updates_last_response_author_type_to_user
```

---

### 4. TRANSICIONES AUTOMÁTICAS DE STATUS (TRIGGERS)

**Impacto**: ⭐⭐⭐⭐ ALTO - Automatizan workflows

**Trigger 1: Auto-Assignment (OPEN → PENDING)**
```sql
Condición: author_type = 'agent' AND owner_agent_id IS NULL
Efecto: owner_agent_id, status, first_response_at, last_response_author_type
```

**Trigger 2: User Response Status Change (PENDING → OPEN)**
```sql
Condición: author_type = 'user' AND status = 'pending'
Efecto: status (PENDING→OPEN), last_response_author_type='user'
IMPORTANTE: owner_agent_id NO se modifica (SE MANTIENE)
```

**Trigger 3: Update last_response_author_type**
```sql
Condición: SIEMPRE (cada response)
Efecto: last_response_author_type = NEW.author_type
```

**Documentos afectados**:
- ✅ Plan TDD: Agregar 3 triggers SQL
- ✅ Mapping: Agregar tabla de triggers con explicaciones

**Tests relacionados**: CreateResponseTest #16-23 (8 tests)

---

### 5. VENTANAS DE TIEMPO (TIME WINDOWS)

**Impacto**: ⭐⭐⭐ ALTO - Restricciones críticas

**Ventana 1: 30 minutos**
- Editar respuestas (UpdateResponseTest)
- Eliminar respuestas (DeleteResponseTest)
- Eliminar attachments (DeleteAttachmentTest)
- Upload attachment a response (UploadAttachmentToResponseTest)

**Ventana 2: 30 días** (solo para USERS)
- Reabrir tickets cerrados (ReopenTicketTest)
- Agents NO tienen limite

**Ventana 3: 24 horas** (Rating update)
- Actualizar calificación de ticket (sección de ratings)

**Cambio importante**: Tests validan EXACTAMENTE estas ventanas

**Documentos afectados**:
- ✅ Plan TDD: Agregar tabla de time windows
- ✅ Mapping: Agregar sección de restricciones temporales

**Tests relacionados**:
```
UpdateResponseTest: 10 tests
DeleteResponseTest: 7 tests
DeleteAttachmentTest: 8 tests
UploadAttachmentToResponseTest: 8 tests
ReopenTicketTest: 13 tests
```

---

### 6. PERMISOS COMPLEJOS POR ROL Y ESTADO

**Impacto**: ⭐⭐⭐⭐ ALTO - Seguridad/UX

**Cambio clave en CloseTicketTest**:
- **AGENT**: Puede cerrar CUALQUIER ticket (abierto, pending, resuelto)
- **USER**: SOLO puede cerrar tickets RESOLVED de su propiedad

```
Before: Probablemente USER podía cerrar cualquier estado
After: USER restrictivo solo en RESOLVED
```

**Cambio clave en ReopenTicketTest**:
- **USER**: Máximo 30 días desde cierre
- **AGENT**: Sin límite de tiempo

**Matriz de permisos mejorada**:
- Update: USER si status=OPEN; AGENT siempre
- Resolve: AGENT only
- Close: AGENT siempre; USER solo si RESOLVED
- Reopen: AGENT siempre; USER si <30 días

**Documentos afectados**:
- ✅ Plan TDD: Actualizar matriz de permisos
- ✅ Mapping: Actualizar matriz de permisos

**Tests relacionados**:
```
CloseTicketTest: 11 tests
ReopenTicketTest: 13 tests
UpdateTicketTest: 12 tests
ResolveTicketTest: 11 tests
```

---

### 7. LÍMITE DE ATTACHMENTS: 5 GLOBAL POR TICKET

**Impacto**: ⭐⭐⭐ ALTO - Validación de límites

**Cambio clave**:
- Límite es **GLOBAL** a todo el ticket (ticket + responses combinadas)
- NO es 5 por respuesta, NO es 5 en el ticket
- Es 5 TOTALES

**Validaciones**:
```
UploadAttachmentTest: Valida máximo 5
UploadAttachmentToResponseTest: Valida máximo 5 (global)
DeleteAttachmentTest: Permite delete en ventana 30min
```

**Documentos afectados**:
- ✅ Plan TDD: Aclarar en validaciones
- ✅ Mapping: Clarificar límite global

**Tests relacionados**:
```
UploadAttachmentTest #7: validates_max_5_attachments_per_ticket
UploadAttachmentToResponseTest #7: max_5_attachments_applies_to_entire_ticket
```

---

### 8. QUERY PARAMS MEJORADOS

**Impacto**: ⭐⭐⭐ ALTO - Usabilidad API

**Cambio 1: `owner_agent_id` soporta "special" values**
```
?owner_agent_id=null      → Tickets SIN asignar (literal string "null")
?owner_agent_id=me        → Mis tickets asignados
?owner_agent_id={uuid}    → Agente específico
```

**Cambio 2: `created_by` simplificado**
```
?created_by=me            → Tickets que YO creé
?created_by={uuid}        → Tickets creados por usuario específico
```

**Cambio 3: `last_response_author_type` filter (NEW)**
```
?last_response_author_type=none     → Sin respuestas
?last_response_author_type=user     → Cliente respondió último
?last_response_author_type=agent    → Agente respondió último
```

**Cambio 4: Filtros combinables**
```
?owner_agent_id=null&last_response_author_type=none  → Tickets nuevos sin asignar
?owner_agent_id=me&last_response_author_type=user    → Mis tickets: cliente respondió
```

**Documentos afectados**:
- ✅ Mapping: Actualizar tabla de query params
- ✅ Mapping: Agregar ejemplos de requests

**Tests relacionados**: ListTicketsTest #22-26 (5 tests)

---

### 9. VALIDACIONES Y RESTRICCIONES

**Impacto**: ⭐⭐⭐ ALTO - Data integrity

**CreateTicketTest**:
- Title: 5-255 chars
- Description: 10-5000 chars
- Company must exist
- Category must exist AND is_active=true

**CreateResponseTest**:
- Content: 1-5000 chars
- Cannot respond to CLOSED tickets
- Automatic author_type assignment

**UploadAttachmentTest**:
- File max: 10 MB
- Allowed types: PDF, JPG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, ZIP
- Disallowed types: .exe, .sh, etc.
- Cannot upload to CLOSED tickets

**Documentos afectados**:
- ✅ Plan TDD: Agregar validaciones específicas
- ✅ Mapping: Agregar tabla de validaciones

**Tests relacionados**: Múltiples tests de validación

---

### 10. EVENTOS Y NOTIFICACIONES

**Impacto**: ⭐⭐⭐ ALTO - Comunicación

**Eventos despachados**:
```
TicketCreated       → CreateTicketTest
ResponseAdded       → CreateResponseTest
TicketResolved      → ResolveTicketTest
TicketClosed        → CloseTicketTest
TicketReopened      → ReopenTicketTest
TicketAssigned      → AssignTicketTest
```

**Notificaciones**:
```
User response       → Notifica AGENT
Agent response      → Notifica USER (creator)
Ticket resolved     → Notifica USER (creator)
Ticket assigned     → Notifica NEW AGENT
```

**Documentos afectados**:
- ✅ Plan TDD: Agregar sección de events
- ✅ Mapping: Agregar sección de notificaciones

**Tests relacionados**: Múltiples tests de events

---

## 📊 MATRIZ DE CAMBIOS POR DOCUMENTO

| Cambio | Plan TDD | Mapping | Prioridad |
|--------|----------|---------|-----------|
| Campo `last_response_author_type` | ✅ | ✅ | ⭐⭐⭐⭐⭐ |
| State Machine OPEN→PENDING→OPEN | ✅ | ✅ | ⭐⭐⭐⭐⭐ |
| Auto-assignment trigger | ✅ | ✅ | ⭐⭐⭐⭐⭐ |
| PENDING→OPEN trigger | ✅ | ✅ | ⭐⭐⭐⭐⭐ |
| Time windows (30 min, 30 días) | ✅ | ✅ | ⭐⭐⭐⭐ |
| Permisos por rol/estado | ✅ | ✅ | ⭐⭐⭐⭐ |
| Max 5 attachments global | ✅ | ✅ | ⭐⭐⭐ |
| Query params mejorados | ✅ | ✅ | ⭐⭐⭐ |
| Validaciones específicas | ✅ | ✅ | ⭐⭐⭐ |
| Eventos/Notificaciones | ✅ | ✅ | ⭐⭐⭐ |

---

## ✅ CHECKLIST PARA SINCRONIZACIÓN

### Plan TDD (Tickets-tests-TDD-plan.md)

- [ ] Agregar campo `last_response_author_type` a estructura de Models
- [ ] Actualizar diagrama de estados con 4 estados (no 3)
- [ ] Agregar transiciones automáticas (triggers)
- [ ] Agregar tabla de time windows
- [ ] Agregar matriz de permisos actualizada
- [ ] Agregar sección de validaciones de límites
- [ ] Agregar triggers SQL completos
- [ ] Agregar tabla de eventos
- [ ] Actualizar respuesta ejemplo con nuevo campo
- [ ] Actualizar query params con nuevas opciones

### Mapping (tickets-feature-maping.md)

- [ ] Agregar campo `last_response_author_type` a tabla de campos `tickets`
- [ ] Actualizar diagrama de flujo con estado PENDING
- [ ] Agregar triggers PostgreSQL completos (3 triggers)
- [ ] Actualizar query params: `owner_agent_id`, `created_by`, `last_response_author_type`
- [ ] Agregar tabla de time windows
- [ ] Actualizar matriz de permisos
- [ ] Agregar ejemplos de responses con nuevo campo
- [ ] Agregar sección de validaciones
- [ ] Actualizar respuestas ejemplo (4 estados diferentes)
- [ ] Agregar tabla de restricciones temporales

---

## 📝 NOTAS IMPORTANTES

1. **last_response_author_type es transversal**: Aparece en TODOS los endpoints de tickets y respuestas

2. **Triggers son críticos**: PENDING→OPEN no puede ser lógica PHP, DEBE ser trigger SQL

3. **Permisos son complejos**: No es solo por rol, también por estado del ticket

4. **Time windows son exactos**: 30 minutos y 30 días son números específicos validados

5. **Attachments son globales**: Máximo 5 POR TICKET, no por sección

6. **Query params son importantes**: Muchas vistas dependen de `owner_agent_id=null` y `last_response_author_type`

---

## 🎯 PRÓXIMOS PASOS

1. Actualizar **Tickets-tests-TDD-plan.md**
2. Actualizar **tickets-feature-maping.md**
3. Crear plan de implementación de código basado en tests
4. Implementar modelos, migrations, controllers
5. Ejecutar tests en rojo → verde
