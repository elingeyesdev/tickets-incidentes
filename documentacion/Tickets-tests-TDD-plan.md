# 🎫 TICKET MANAGEMENT FEATURE - ESTRUCTURA FINAL + TESTING PLAN

> **Feature**: Ticket Management (Tickets + Responses + Internal Notes + Attachments + Ratings)
> **Cobertura de Tests**: Unit + Integration + Feature + Edge Cases
> **Total de Archivos de Test**: 45 archivos (42 originales + 3 nuevos para last_response_author_type)
> **Total de Tests Estimados**: 383 tests (358 originales + 25 nuevos para last_response_author_type)
> **ACTUALIZACIÓN**: Sincronización con tests implementados (Nov 2025)
> **Cambios Críticos Identificados**:
> - Campo transversal `last_response_author_type` (⭐⭐⭐⭐⭐)
> - State machine: OPEN → PENDING → OPEN (⭐⭐⭐⭐⭐)
> - Triggers automáticos PostgreSQL (⭐⭐⭐⭐⭐)
> - Ventanas de tiempo: 30 min, 30 días (⭐⭐⭐⭐)
> - Ver CAMBIOS-EN-TESTS.md para detalles completos

---

## 🔄 SISTEMA DE ESTADOS (STATE MACHINE) - CRÍTICO

### Estados del Ticket (4 Estados)

```
┌─────────────────────────────┐
│  OPEN (Nuevo)               │
│  - Sin agente asignado      │
│  - last_response_author_type: 'none'
└────────────┬────────────────┘
             │
             │ (PRIMER agente responde) [TRIGGER]
             ▼
┌─────────────────────────────┐
│  PENDING (Esperando cliente)│
│  - Agente asignado          │
│  - last_response_author_type: 'agent'
└────────────┬────────────────┘
             │
             │ (Cliente responde) [TRIGGER]
             ▼
┌─────────────────────────────┐
│  OPEN (Cliente respondió)   │
│  - Agente sigue asignado    │
│  - last_response_author_type: 'user'
└────────────┬────────────────┘
             │
             │ (Agente resuelve) [MANUAL]
             ▼
┌─────────────────────────────┐
│  RESOLVED (Resuelto)        │
│  - resolved_at = timestamp  │
└────────────┬────────────────┘
             │
             │ (Auto-close 7 días)
             ▼
┌─────────────────────────────┐
│  CLOSED (Cerrado)           │
│  - closed_at = timestamp    │
└─────────────────────────────┘
```

### Campo Transversal: last_response_author_type

| Valor | Significado | Cuándo |
|-------|-------------|--------|
| `'none'` | Sin respuestas aún | Ticket recién creado |
| `'user'` | Cliente respondió último | Después de respuesta del usuario |
| `'agent'` | Agente respondió último | Después de respuesta del agente |

**CRÍTICO**: Se actualiza SIEMPRE en cada respuesta. NUNCA cambia en acciones como resolve, close, reopen, assign.

---

## ⚙️ TRIGGERS PostgreSQL AUTOMÁTICOS

### Trigger 1: Auto-Assignment (OPEN → PENDING)

**Condición**: `author_type = 'agent'` AND `owner_agent_id IS NULL`

```sql
UPDATE ticketing.tickets
SET
    owner_agent_id = NEW.author_id,
    status = 'pending',
    first_response_at = NOW(),
    last_response_author_type = 'agent',
    updated_at = NOW()
WHERE id = NEW.ticket_id
AND owner_agent_id IS NULL;
```

**Cuándo**: Cuando el PRIMER agente responde a un ticket nuevo (open sin asignar)

### Trigger 2: Status Change (PENDING → OPEN)

**Condición**: `author_type = 'user'` AND `status = 'pending'`

```sql
UPDATE ticketing.tickets
SET
    status = 'open',
    last_response_author_type = 'user',
    updated_at = NOW()
WHERE id = NEW.ticket_id
AND status = 'pending';
```

**CRÍTICO**: `owner_agent_id` NO se modifica (SE MANTIENE)

**Cuándo**: Cuando el CLIENTE responde a un ticket en estado PENDING

### Trigger 3: Update last_response_author_type

**Condición**: SIEMPRE (en cada respuesta)

```sql
UPDATE ticketing.tickets
SET
    last_response_author_type = NEW.author_type,
    updated_at = NOW()
WHERE id = NEW.ticket_id;
```

---

## ⏱️ VENTANAS DE TIEMPO CRÍTICAS

| Restricción | Límite | Aplica a | Validación |
|------------|--------|----------|-----------|
| Edit Response | 30 minutos | `UpdateResponseTest` | `created_at + 30 min` |
| Delete Response | 30 minutos | `DeleteResponseTest` | `created_at + 30 min` |
| Upload to Response | 30 minutos | `UploadAttachmentToResponseTest` | `response.created_at + 30 min` |
| Delete Attachment | 30 minutos | `DeleteAttachmentTest` | `created_at + 30 min` |
| Reopen Closed Ticket | 30 días (USER only) | `ReopenTicketTest` | `closed_at + 30 días` (AGENT: sin límite) |
| Update Rating | 24 horas | `UpdateRatingTest` | `rating.created_at + 24h` |

**Implementación**: Validaciones en Rules/ y Services/

---

## 🔐 MATRIZ DE PERMISOS ACTUALIZADA

| Operación | USER | AGENT | COMPANY_ADMIN |
|-----------|:----:|:-----:|:-------------:|
| Create Ticket | ✅ | ❌ | ❌ |
| List Tickets | Propios | Company | Company |
| Get Ticket | Owner | Company | Company |
| Update Ticket | Si open | Siempre | Siempre |
| Resolve Ticket | ❌ | ✅ | ✅ |
| Close Ticket | Si resolved | Siempre | Siempre |
| Reopen Ticket | Si <30d | Siempre | Siempre |
| Assign Ticket | ❌ | ✅ | ✅ |
| Delete Ticket | ❌ | ❌ | Si closed |
| **RESPONSES** | | | |
| Create Response | Owner | Company | Company |
| Edit Response | Autor 30m | Autor 30m | Autor 30m |
| Delete Response | Autor 30m | Autor 30m | Autor 30m |
| **ATTACHMENTS** | | | |
| Upload | Owner | Company | Company |
| Delete | Uploader 30m | Uploader 30m | Uploader 30m |
| **INTERNAL NOTES** | | | |
| View Notes | ❌ | ✅ | ✅ |
| Create Note | ❌ | ✅ | ✅ |

---

## 📂 PARTE 1: ESTRUCTURA DE CARPETAS FINAL

```
app/Features/TicketManagement/
│
├── Database/
│   ├── Factories/
│   │   ├── CategoryFactory.php
│   │   ├── TicketFactory.php
│   │   ├── TicketResponseFactory.php
│   │   ├── TicketInternalNoteFactory.php
│   │   ├── TicketAttachmentFactory.php
│   │   └── TicketRatingFactory.php
│   │
│   ├── Migrations/
│   │   ├── 2025_11_09_000001_create_ticketing_categories_table.php
│   │   ├── 2025_11_09_000002_create_ticketing_tickets_table.php
│   │   ├── 2025_11_09_000003_create_ticketing_ticket_responses_table.php
│   │   ├── 2025_11_09_000004_create_ticketing_ticket_internal_notes_table.php
│   │   ├── 2025_11_09_000005_create_ticketing_ticket_attachments_table.php
│   │   ├── 2025_11_09_000006_create_ticketing_ticket_ratings_table.php
│   │   ├── 2025_11_09_000007_add_indexes_to_ticketing_tables.php
│   │   └── 2025_11_09_000008_create_assign_ticket_owner_trigger.php
│   │
│   └── Seeders/
│       ├── TicketManagementSeeder.php
│       └── DefaultCategoriesSeeder.php
│
├── Enums/
│   ├── TicketStatus.php (open, pending, resolved, closed)
│   └── AuthorType.php (user, agent)
│
├── Events/
│   ├── TicketCreated.php
│   ├── TicketAssigned.php
│   ├── TicketResolved.php
│   ├── TicketClosed.php
│   ├── TicketReopened.php
│   ├── ResponseAdded.php
│   ├── InternalNoteAdded.php
│   └── TicketRated.php
│
├── Exceptions/
│   ├── TicketNotFoundException.php
│   ├── TicketNotEditableException.php
│   ├── ResponseNotEditableException.php
│   ├── NotTicketOwnerException.php
│   ├── CategoryInUseException.php
│   ├── CannotReopenTicketException.php
│   ├── RatingAlreadyExistsException.php
│   └── FileUploadException.php
│
├── Http/
│   ├── Controllers/
│   │   ├── CategoryController.php
│   │   ├── TicketController.php
│   │   ├── TicketActionController.php (resolve, close, reopen, assign)
│   │   ├── TicketResponseController.php
│   │   ├── TicketInternalNoteController.php
│   │   ├── TicketAttachmentController.php
│   │   └── TicketRatingController.php
│   │
│   ├── Requests/
│   │   ├── Categories/
│   │   │   ├── StoreCategoryRequest.php
│   │   │   └── UpdateCategoryRequest.php
│   │   │
│   │   ├── Tickets/
│   │   │   ├── StoreTicketRequest.php
│   │   │   ├── UpdateTicketRequest.php
│   │   │   ├── ResolveTicketRequest.php
│   │   │   ├── ReopenTicketRequest.php
│   │   │   └── AssignTicketRequest.php
│   │   │
│   │   ├── Responses/
│   │   │   ├── StoreResponseRequest.php
│   │   │   └── UpdateResponseRequest.php
│   │   │
│   │   ├── InternalNotes/
│   │   │   ├── StoreInternalNoteRequest.php
│   │   │   └── UpdateInternalNoteRequest.php
│   │   │
│   │   ├── Attachments/
│   │   │   └── UploadAttachmentRequest.php
│   │   │
│   │   └── Ratings/
│   │       ├── StoreRatingRequest.php
│   │       └── UpdateRatingRequest.php
│   │
│   └── Resources/
│       ├── CategoryResource.php
│       ├── TicketResource.php
│       ├── TicketListResource.php
│       ├── TicketDetailResource.php
│       ├── ResponseResource.php
│       ├── InternalNoteResource.php
│       ├── AttachmentResource.php
│       └── RatingResource.php
│
├── Jobs/
│   ├── AutoCloseResolvedTicketsJob.php (7 días)
│   ├── SendTicketNotificationJob.php
│   └── CleanupOldAttachmentsJob.php
│
├── Listeners/
│   ├── NotifyTicketCreated.php
│   ├── NotifyAgentAssigned.php
│   ├── NotifyTicketResolved.php
│   ├── NotifyNewResponse.php
│   └── UpdateAgentMetrics.php
│
├── Mail/
│   ├── TicketCreatedMail.php
│   ├── TicketAssignedMail.php
│   ├── TicketResolvedMail.php
│   ├── NewResponseMail.php
│   └── TicketRatedMail.php
│
├── Models/
│   ├── Category.php
│   ├── Ticket.php
│   ├── TicketResponse.php
│   ├── TicketInternalNote.php
│   ├── TicketAttachment.php
│   └── TicketRating.php
│
├── Observers/
│   ├── TicketObserver.php (updated_at, audit)
│   ├── ResponseObserver.php (dispatch events)
│   └── RatingObserver.php (update metrics)
│
├── Policies/
│   ├── CategoryPolicy.php
│   ├── TicketPolicy.php
│   ├── ResponsePolicy.php
│   ├── InternalNotePolicy.php
│   ├── AttachmentPolicy.php
│   └── RatingPolicy.php
│
├── Rules/
│   ├── ValidTicketCode.php
│   ├── ValidFileType.php
│   ├── MaxFileSize.php
│   ├── CanReopenTicket.php (30 días check)
│   └── EditableResponse.php (30 min check)
│
├── Services/
│   ├── CategoryService.php
│   ├── TicketService.php
│   ├── TicketCodeGenerator.php
│   ├── ResponseService.php
│   ├── InternalNoteService.php
│   ├── AttachmentService.php
│   ├── RatingService.php
│   ├── AutoAssignmentService.php (maneja trigger logic)
│   ├── TicketVisibilityService.php
│   └── FileUploadService.php
│
│
└── TicketManagementServiceProvider.php
```

---

## 🔑 PRINCIPIOS DE ARQUITECTURA

### Autenticación y Autorización
- **JWT Stateless Architecture**: Sin sesiones, tokens auto-contenidos
- **Middlewares Reutilizados**: Solo `AuthenticateJwt` y `EnsureUserHasRole` (existentes)
- **NO crear middlewares custom** como `EnsureTicketOwner` o `EnsureAgentRole`
- **Autorización por recurso**: Se maneja vía Policies (Laravel Policy pattern)
- **Multi-tenancy**: Gestión de company_id vía `JWTHelper` (extrae del token)

### Triggers PostgreSQL
- **Auto-assignment**: Primer agente que responde se asigna automáticamente
- **Status transitions**: PENDING→OPEN automático cuando cliente responde
- **Campo transversal**: `last_response_author_type` actualizado por triggers

### Validaciones Temporales
- **30 minutos**: Editar/eliminar respuestas y adjuntos
- **30 días**: Reapertura de tickets cerrados (solo USER)
- **7 días**: Auto-close de tickets resueltos
- **24 horas**: Actualización de calificaciones

### Testing
- **Docker obligatorio**: Todas las pruebas se ejecutan en contenedores
- **TDD approach**: Tests primero, implementación después
- **Cobertura completa**: Unit + Integration + Feature tests

---

## 🧪 PARTE 2: PLAN COMPLETO DE TESTING

### Estructura de Tests

```
tests/Feature/TicketManagement/
├── Categories/
├── Tickets/
│   ├── CRUD/
│   └── Actions/
├── Responses/
├── InternalNotes/
├── Attachments/
├── Ratings/
└── Permissions/

tests/Unit/TicketManagement/
├── Services/
├── Models/
├── Rules/
└── Jobs/

tests/Integration/TicketManagement/
```

---

## 📋 TESTS DETALLADOS POR ARCHIVO

---

## 📂 CATEGORIES

### Archivo: `tests/Feature/TicketManagement/Categories/CreateCategoryTest.php`

**Total de Tests: 8**

1. **test_company_admin_can_create_category**
    - POST /tickets/categories
    - Verifica categoría creada

2. **test_validates_name_is_required**
    - Sin name → error 422

3. **test_validates_name_length**
    - name con 2 chars → error (min 3)
    - name con 150 chars → error (max 100)

4. **test_validates_name_is_unique_per_company**
    - Empresa A ya tiene "Soporte Técnico"
    - Intenta duplicado → error 422

5. **test_name_uniqueness_is_per_company**
    - Empresa A y B pueden tener misma categoría → ✅

6. **test_description_is_optional**
    - Sin description → ✅

7. **test_company_id_is_inferred_from_jwt**
    - Verifica company_id = del token

8. **test_user_cannot_create_category**
    - USER → error 403

---

### Archivo: `tests/Feature/TicketManagement/Categories/UpdateCategoryTest.php`

**Total de Tests: 6**

1. **test_company_admin_can_update_category**
    - PUT /tickets/categories/:id
    - Actualiza name, description

2. **test_can_toggle_is_active_status**
    - Actualiza is_active=false

3. **test_validates_updated_name_uniqueness**
    - Cambia a nombre existente → error 422

4. **test_cannot_update_category_from_different_company**
    - Admin B → categoría empresa A → error 403

5. **test_partial_update_preserves_unchanged_fields**
    - Solo actualiza description

6. **test_user_cannot_update_category**
    - USER → error 403

---

### Archivo: `tests/Feature/TicketManagement/Categories/DeleteCategoryTest.php`

**Total de Tests: 6**

1. **test_company_admin_can_delete_unused_category**
    - DELETE /tickets/categories/:id
    - Sin tickets → ✅ eliminada

2. **test_cannot_delete_category_with_open_tickets**
    - Categoría tiene tickets con status=open → error 409

3. **test_cannot_delete_category_with_pending_tickets**
    - Categoría tiene tickets con status=pending → error 409

4. **test_can_delete_category_with_only_closed_tickets**
    - Todos los tickets están closed → ✅ eliminada

5. **test_error_message_shows_active_tickets_count**
    - Error incluye "15 tickets activos"

6. **test_user_cannot_delete_category**
    - USER → error 403

---

### Archivo: `tests/Feature/TicketManagement/Categories/ListCategoriesTest.php`

**Total de Tests: 6**

1. **test_user_can_list_categories_of_company**
    - GET /tickets/categories?company_id=X
    - Usuario puede listar categorías (sin restricción de following)

2. **test_filters_by_is_active_status**
    - is_active=true → solo activas

3. **test_user_can_list_categories_of_any_company**
    - Usuario ve categorías de CUALQUIER empresa
    - No hay restricción por "must follow"

4. **test_agent_can_list_own_company_categories**
    - company_id inferido del JWT

5. **test_includes_tickets_count_per_category**
    - Response incluye active_tickets_count

6. **test_unauthenticated_user_cannot_list**
    - Sin token → error 401

---

## 🎫 TICKETS - CRUD

### Archivo: `tests/Feature/TicketManagement/Tickets/CRUD/CreateTicketTest.php`

**Total de Tests: 16**

1. **test_user_can_create_ticket**
    - POST /tickets
    - Verifica ticket creado con status=open
    - Valida last_response_author_type='none' en response

2. **test_validates_required_fields**
    - Omite title → error 422
    - Omite description → error 422
    - Omite company_id → error 422
    - Omite category_id → error 422

3. **test_validates_title_length**
    - title con 3 chars → error (min 5)
    - title con 300 chars → error (max 255)

4. **test_validates_description_length**
    - description con 5 chars → error (min 10)
    - description con 6000 chars → error (max 5000)

5. **test_validates_company_exists**
    - company_id inválido → error 422

6. **test_validates_category_exists_and_is_active**
    - category_id inválido → error 422
    - category_id con is_active=false → error 422

7. **test_user_can_create_ticket_in_any_company**
    - Usuario puede crear ticket en CUALQUIER empresa
    - No se valida restricción "must follow"

8. **test_ticket_code_is_generated_automatically**
    - Verifica formato TKT-2025-00001

9. **test_ticket_code_is_sequential_per_year**
    - Primer ticket 2025 → TKT-2025-00001
    - Segundo ticket 2025 → TKT-2025-00002

10. **test_ticket_starts_with_status_open**
    - status = open, owner_agent_id = null
    - Incluye validación de last_response_author_type='none'

11. **test_created_by_user_id_is_set_to_authenticated_user**
    - Verifica created_by_user_id correcto

12. **test_ticket_creation_triggers_event**
    - Verifica TicketCreated event disparado

13. **test_agent_cannot_create_ticket**
    - AGENT → error 403

14. **test_company_admin_cannot_create_ticket**
    - COMPANY_ADMIN → error 403

15. **test_unauthenticated_user_cannot_create_ticket**
    - Sin token → error 401

16. **test_created_ticket_has_correct_initial_last_response_author_type**
    - Verifica: Nuevo ticket tiene last_response_author_type='none'
    - Checkpoint: Campo inicializado correctamente en creación

---

### Archivo: `tests/Feature/TicketManagement/Tickets/CRUD/ListTicketsTest.php`

**Total de Tests: 24**

1. **test_user_can_list_own_tickets**
    - GET /tickets?company_id=X
    - Usuario ve solo sus tickets
    - Soporta nuevo query param last_response_author_type

2. **test_user_cannot_see_tickets_from_other_users**
    - Usuario A no ve tickets de usuario B

3. **test_agent_can_list_all_company_tickets**
    - AGENT ve todos los tickets de su empresa
    - Soporta filtros: owner_agent_id=null, created_by=me

4. **test_agent_cannot_see_tickets_from_other_companies**
    - Agent empresa A no ve tickets empresa B

5. **test_filter_by_status_works**
    - status=open → solo tickets open
    - Incluye validación de last_response_author_type en response

6. **test_filter_by_category_works**
    - category_id=X → solo esa categoría
    - Response incluye last_response_author_type

7. **test_filter_by_owner_agent_id_works**
    - owner_agent_id=me → tickets asignados a mí
    - Response incluye last_response_author_type

8. **test_filter_owner_agent_id_me_resolves_to_authenticated_user**
    - "me" → UUID del agente autenticado
    - Valida last_response_author_type en resultados

9. **test_filter_by_created_by_user_id**
    - Solo tickets de un usuario específico
    - Nota: created_by acepta 'me' como alias del usuario actual

10. **test_search_in_title_works**
    - search=exportar → busca en title
    - Response incluye last_response_author_type

11. **test_search_in_description_works**
    - Busca en description también
    - Valida last_response_author_type en response

12. **test_filter_by_date_range**
    - created_after & created_before
    - Response incluye last_response_author_type

13. **test_sort_by_created_at_desc_default**
    - Orden descendente por defecto
    - Valida last_response_author_type en todos los resultados

14. **test_sort_by_updated_at_asc**
    - sort=updated_at → ascendente
    - Response incluye last_response_author_type

15. **test_pagination_works**
    - page=2, per_page=20
    - Cada página incluye last_response_author_type

16. **test_includes_related_data_in_list**
    - Incluye creator, owner_agent, category, counts
    - NUEVO: incluye last_response_author_type

17. **test_user_can_view_own_tickets_regardless_of_following**
    - Usuario ve sus propios tickets sin restricción de following
    - Ownership > following
    - Response incluye last_response_author_type

18. **test_unauthenticated_user_cannot_list_tickets**
    - Sin token → error 401

19. **test_filter_by_last_response_author_type_none**
    - Valida: ?last_response_author_type=none retorna tickets con ese valor
    - Checkpoint: Solo tickets sin respuestas

20. **test_filter_by_last_response_author_type_user**
    - Valida: ?last_response_author_type=user retorna tickets con ese valor
    - Checkpoint: Última respuesta fue del cliente

21. **test_filter_by_last_response_author_type_agent**
    - Valida: ?last_response_author_type=agent retorna tickets con ese valor
    - Checkpoint: Última respuesta fue del agente

22. **test_filter_by_owner_agent_id_null_literal**
    - Valida: ?owner_agent_id=null (string literal) retorna tickets sin owner
    - Nota: 'null' es STRING literal, no debe interpretarse como SQL NULL
    - Checkpoint: Tickets no asignados a ningún agente

23. **test_filter_by_created_by_user_id**
    - Valida: ?created_by=me retorna tickets creados por usuario actual
    - Checkpoint: Filtro de autoría funciona correctamente

24. **test_combine_filters_owner_null_and_last_response_author_type_none**
    - Valida: Combinación de ?owner_agent_id=null&last_response_author_type=none
    - Checkpoint: Múltiples filtros funcionan en conjunto correctamente

---

### Archivo: `tests/Feature/TicketManagement/Tickets/CRUD/GetTicketTest.php`

**Total de Tests: 11**

1. **test_user_can_view_own_ticket**
    - GET /tickets/:code
    - Usuario owner → ✅

2. **test_user_cannot_view_other_user_ticket**
    - Usuario A → ticket de usuario B → error 403

3. **test_agent_can_view_any_ticket_from_own_company**
    - Agent ve cualquier ticket de su empresa

4. **test_agent_cannot_view_ticket_from_other_company**
    - Agent empresa A → ticket empresa B → error 403

5. **test_company_admin_can_view_any_ticket_from_own_company**
    - COMPANY_ADMIN ve todos

6. **test_ticket_detail_includes_complete_information**
    - Verifica estructura completa del response
    - NUEVO: Incluye last_response_author_type en response esperado

7. **test_ticket_detail_includes_responses_count**
    - Incluye responses_count, attachments_count

8. **test_ticket_detail_includes_timeline**
    - Timeline con eventos (created, first_response, resolved)

9. **test_nonexistent_ticket_returns_404**
    - Ticket code inválido → 404

10. **test_unauthenticated_user_cannot_view_ticket**
    - Sin token → error 401

11. **test_get_ticket_detail_includes_last_response_author_type**
    - Valida: Response GET /tickets/{id} incluye campo last_response_author_type
    - Checkpoint: Campo visible en detalle del ticket

---

### Archivo: `tests/Feature/TicketManagement/Tickets/CRUD/UpdateTicketTest.php`

**Total de Tests: 12**

1. **test_user_can_update_own_ticket_when_status_open**
    - PUT /tickets/:code
    - status=open → puede actualizar title, category

2. **test_user_cannot_update_ticket_when_status_pending**
    - status=pending → error 403

3. **test_user_cannot_update_ticket_when_status_resolved**
    - status=resolved → error 403

4. **test_user_can_only_update_title_and_category**
    - Intenta actualizar status → ignorado

5. **test_agent_can_update_ticket_title_and_category**
    - Agent actualiza cualquier campo permitido

6. **test_agent_cannot_manually_change_status_to_pending**
    - status=pending solo por trigger auto-assignment

7. **test_validates_updated_title_length**
    - title muy largo → error 422

8. **test_validates_updated_category_exists**
    - category_id inválido → error 422

9. **test_partial_update_preserves_unchanged_fields**
    - Solo actualiza title
    - Ajuste: Verifica que last_response_author_type no cambia al actualizar ticket

10. **test_user_cannot_update_other_user_ticket**
    - Usuario A → ticket B → error 403

11. **test_company_admin_from_different_company_cannot_update**
    - Admin B → ticket empresa A → error 403

12. **test_unauthenticated_user_cannot_update**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/Tickets/CRUD/DeleteTicketTest.php`

**Total de Tests: 7**

1. **test_company_admin_can_delete_closed_ticket**
    - DELETE /tickets/:code
    - status=closed → ✅ eliminado

2. **test_cannot_delete_open_ticket**
    - status=open → error 403

3. **test_cannot_delete_pending_ticket**
    - status=pending → error 403

4. **test_cannot_delete_resolved_ticket**
    - status=resolved → error 403

5. **test_deleting_ticket_cascades_to_related_data**
    - Verifica responses, notes, attachments eliminados

6. **test_user_cannot_delete_ticket**
    - USER → error 403

7. **test_agent_cannot_delete_ticket**
    - AGENT → error 403

---

## 🎫 TICKETS - ACTIONS

### Archivo: `tests/Feature/TicketManagement/Tickets/Actions/ResolveTicketTest.php`

**Total de Tests: 11**

1. **test_agent_can_resolve_ticket**
    - POST /tickets/:code/resolve
    - Verifica status → resolved, resolved_at != null
    - Ajuste: Valida que last_response_author_type persiste durante resolución

2. **test_resolution_note_is_optional**
    - Sin resolution_note → ✅

3. **test_resolution_note_is_saved_when_provided**
    - Con resolution_note → guardado correctamente

4. **test_resolve_triggers_ticket_resolved_event**
    - Verifica TicketResolved event disparado

5. **test_resolve_sends_notification_to_ticket_owner**
    - Usuario recibe email/notificación

6. **test_cannot_resolve_already_resolved_ticket**
    - status=resolved → resolve again → error 400

7. **test_cannot_resolve_closed_ticket**
    - status=closed → error 400

8. **test_user_cannot_resolve_ticket**
    - USER → error 403

9. **test_agent_from_different_company_cannot_resolve**
    - Agent empresa B → ticket empresa A → error 403

10. **test_unauthenticated_user_cannot_resolve**
    - Sin token → error 401

11. **test_last_response_author_type_persists_after_ticket_resolve**
    - Valida: Campo no cambia al resolver ticket
    - Checkpoint: Solo cambia status y resolved_at

---

### Archivo: `tests/Feature/TicketManagement/Tickets/Actions/CloseTicketTest.php`

**Total de Tests: 11**

1. **test_agent_can_close_any_ticket**
    - POST /tickets/:code/close
    - Agent cierra ticket → ✅
    - Ajuste: Valida que last_response_author_type persiste durante cierre

2. **test_user_can_close_own_resolved_ticket**
    - status=resolved → usuario owner cierra → ✅

3. **test_user_cannot_close_own_pending_ticket**
    - status=pending → usuario intenta cerrar → error 403

4. **test_user_cannot_close_own_open_ticket**
    - status=open → error 403

5. **test_close_sets_closed_at_timestamp**
    - Verifica closed_at = now()

6. **test_close_triggers_ticket_closed_event**
    - Verifica evento disparado

7. **test_cannot_close_already_closed_ticket**
    - status=closed → close again → error 400

8. **test_user_cannot_close_other_user_ticket**
    - Usuario A → ticket B → error 403

9. **test_agent_from_different_company_cannot_close**
    - Agent B → ticket empresa A → error 403

10. **test_unauthenticated_user_cannot_close**
    - Sin token → error 401

11. **test_last_response_author_type_persists_after_ticket_close**
    - Valida: Campo no cambia al cerrar ticket
    - Checkpoint: Solo cambia status y closed_at

---

### Archivo: `tests/Feature/TicketManagement/Tickets/Actions/ReopenTicketTest.php`

**Total de Tests: 13**

1. **test_user_can_reopen_own_resolved_ticket**
    - POST /tickets/:code/reopen
    - status=resolved → status=pending
    - Ajuste: Valida que last_response_author_type persiste durante reapertura

2. **test_user_can_reopen_own_closed_ticket_within_30_days**
    - Cerrado hace 20 días → ✅

3. **test_user_cannot_reopen_closed_ticket_after_30_days**
    - Cerrado hace 35 días → error 403

4. **test_agent_can_reopen_any_ticket_regardless_of_time**
    - Agent reabre ticket cerrado hace 50 días → ✅

5. **test_reopen_reason_is_optional**
    - Sin reopen_reason → ✅

6. **test_reopen_reason_is_saved_when_provided**
    - Con reopen_reason → guardado

7. **test_reopened_ticket_returns_to_pending_status**
    - Verifica status=pending después de reopen

8. **test_reopen_triggers_ticket_reopened_event**
    - Verifica evento disparado

9. **test_cannot_reopen_open_ticket**
    - status=open → error 400

10. **test_cannot_reopen_pending_ticket**
    - status=pending → error 400

11. **test_user_cannot_reopen_other_user_ticket**
    - Usuario A → ticket B → error 403

12. **test_unauthenticated_user_cannot_reopen**
    - Sin token → error 401

13. **test_last_response_author_type_persists_after_ticket_reopen**
    - Valida: Campo no cambia al reabrir ticket
    - Checkpoint: Solo cambia status a pending

---

### Archivo: `tests/Feature/TicketManagement/Tickets/Actions/AssignTicketTest.php`

**Total de Tests: 10**

1. **test_agent_can_assign_ticket_to_another_agent**
    - POST /tickets/:code/assign
    - owner_agent_id cambia
    - Ajuste: Verifica que last_response_author_type no cambia durante asignación

2. **test_validates_new_agent_id_is_required**
    - Sin new_agent_id → error 422

3. **test_validates_new_agent_exists**
    - new_agent_id inválido → error 422

4. **test_validates_new_agent_is_from_same_company**
    - Agent empresa B → error 422

5. **test_validates_new_agent_has_agent_role**
    - Usuario USER como new_agent → error 422

6. **test_assignment_note_is_optional**
    - Sin assignment_note → ✅

7. **test_assignment_note_is_saved_when_provided**
    - Con note → guardado

8. **test_assign_triggers_ticket_assigned_event**
    - Verifica evento disparado

9. **test_assign_sends_notification_to_new_agent**
    - Nuevo agente recibe notificación

10. **test_user_cannot_assign_ticket**
    - USER → error 403

---

## 💬 RESPONSES

### Archivo: `tests/Feature/TicketManagement/Responses/CreateResponseTest.php`

**Total de Tests: 23**

1. **test_user_can_respond_to_own_ticket**
    - POST /tickets/:code/responses
    - Usuario owner → ✅

2. **test_agent_can_respond_to_any_company_ticket**
    - Agent responde ticket de su empresa → ✅

3. **test_validates_response_content_is_required**
    - Sin response_content → error 422

4. **test_validates_response_content_length**
    - content con 0 chars → error (min 1)
    - content con 6000 chars → error (max 5000)

5. **test_author_type_is_set_automatically**
    - USER → author_type=user
    - AGENT → author_type=agent

6. **test_first_agent_response_triggers_auto_assignment**
    - Ticket con owner_agent_id=null
    - Agent responde
    - Verifica owner_agent_id = agent, status=pending
    - NUEVO: Valida last_response_author_type='agent'

7. **test_auto_assignment_only_happens_once**
    - Primer agente → asignado
    - Segundo agente responde → owner NO cambia
    - Ajuste: Valida last_response_author_type en cada respuesta

8. **test_first_agent_response_sets_first_response_at**
    - Verifica first_response_at timestamp

9. **test_user_response_does_not_trigger_auto_assignment**
    - Usuario responde → owner_agent_id sigue null
    - NUEVO: Valida last_response_author_type='user', campo actualizado correctamente

10. **test_response_triggers_response_added_event**
    - Verifica evento disparado

11. **test_response_sends_notification_to_relevant_parties**
    - Si user responde → notifica agent
    - Si agent responde → notifica user

12. **test_user_cannot_respond_to_other_user_ticket**
    - Usuario A → ticket B → error 403

13. **test_agent_cannot_respond_to_other_company_ticket**
    - Agent B → ticket empresa A → error 403

14. **test_cannot_respond_to_closed_ticket**
    - status=closed → error 403

15. **test_unauthenticated_user_cannot_respond**
    - Sin token → error 401

16. **test_user_response_to_pending_ticket_changes_status_to_open** ⭐ CRÍTICO
    - Valida: Trigger automático PENDING→OPEN cuando cliente responde
    - Checkpoint: Status cambia de pending a open
    - Checkpoint: last_response_author_type se actualiza a 'user'

17. **test_user_response_to_pending_ticket_updates_last_response_author_type_to_user**
    - Valida: Campo se actualiza a 'user' en trigger
    - Checkpoint: Sincronización correcta con cambio de status

18. **test_agent_response_to_open_ticket_sets_last_response_author_type_to_agent**
    - Valida: Cuando agente responde, campo se actualiza a 'agent'
    - Checkpoint: Campo actualizado sin cambiar status

19. **test_multiple_user_responses_keep_last_response_author_type_as_user**
    - Valida: Múltiples respuestas del cliente mantienen 'user'
    - Checkpoint: Idempotencia del campo

20. **test_alternating_responses_update_last_response_author_type_correctly**
    - Valida: User responde → 'user', Agent responde → 'agent', User responde → 'user'
    - Checkpoint: Actualizaciones consecutivas funcionan

21. **test_pending_to_open_transition_preserves_owner_agent_id**
    - Valida: Transición PENDING→OPEN mantiene owner_agent_id intacto
    - Checkpoint: Solo cambia status y last_response_author_type

22. **test_user_response_to_open_ticket_does_not_change_status**
    - Valida: Respuesta cliente a ticket OPEN NO cambia status
    - Checkpoint: Solo actualiza last_response_author_type='user'

23. **test_agent_response_to_pending_ticket_does_not_change_status**
    - Valida: Respuesta agente a ticket PENDING NO cambia status
    - Checkpoint: Solo actualiza last_response_author_type='agent'

---

### Archivo: `tests/Feature/TicketManagement/Responses/ListResponsesTest.php`

**Total de Tests: 8**

1. **test_user_can_list_responses_from_own_ticket**
    - GET /tickets/:code/responses
    - Usuario owner → ✅

2. **test_agent_can_list_responses_from_any_company_ticket**
    - Agent ve responses de tickets de su empresa

3. **test_responses_are_ordered_by_created_at_asc**
    - Orden cronológico
    - Ajuste: Valida que cada response tiene author_type correcto

4. **test_response_includes_author_information**
    - Incluye author_id, author_name, author_type
    - Ajuste: Verifica coherencia con last_response_author_type del ticket

5. **test_response_includes_attachments**
    - Cada response incluye sus attachments
    - Ajuste: Última response actualiza last_response_author_type del ticket

6. **test_user_cannot_list_responses_from_other_user_ticket**
    - Usuario A → ticket B → error 403

7. **test_agent_cannot_list_responses_from_other_company_ticket**
    - Agent B → ticket empresa A → error 403

8. **test_unauthenticated_user_cannot_list_responses**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/Responses/UpdateResponseTest.php`

**Total de Tests: 10**

1. **test_author_can_update_own_response_within_30_minutes**
    - PUT /tickets/:code/responses/:id
    - Creada hace 15 min → ✅

2. **test_cannot_update_response_after_30_minutes**
    - Creada hace 35 min → error 403

3. **test_validates_updated_content_length**
    - content muy largo → error 422

4. **test_user_cannot_update_other_user_response**
    - Usuario A → response usuario B → error 403

5. **test_agent_cannot_update_other_agent_response**
    - Agent A → response agent B → error 403

6. **test_cannot_update_response_if_ticket_closed**
    - Ticket closed → error 403

7. **test_partial_update_works**
    - Solo actualiza response_content

8. **test_updating_preserves_original_created_at**
    - created_at no cambia

9. **test_updating_sets_updated_at_timestamp**
    - Verifica updated_at actualizado

10. **test_unauthenticated_user_cannot_update**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/Responses/DeleteResponseTest.php`

**Total de Tests: 7**

1. **test_author_can_delete_own_response_within_30_minutes**
    - DELETE /tickets/:code/responses/:id
    - Creada hace 10 min → ✅

2. **test_cannot_delete_response_after_30_minutes**
    - Creada hace 40 min → error 403

3. **test_user_cannot_delete_other_user_response**
    - Usuario A → response B → error 403

4. **test_cannot_delete_response_if_ticket_closed**
    - Ticket closed → error 403

5. **test_deleting_response_cascades_to_attachments**
    - Response con attachments → eliminados también

6. **test_deleted_response_returns_404**
    - DELETE → GET → 404

7. **test_unauthenticated_user_cannot_delete**
    - Sin token → error 401

---

## 📝 INTERNAL NOTES

### Archivo: `tests/Feature/TicketManagement/InternalNotes/CreateInternalNoteTest.php`

**Total de Tests: 8**

1. **test_agent_can_create_internal_note**
    - POST /tickets/:code/internal-notes
    - Agent → ✅

2. **test_validates_note_content_is_required**
    - Sin note_content → error 422

3. **test_validates_note_content_length**
    - content muy largo → error 422

4. **test_note_is_invisible_to_ticket_owner**
    - Usuario no puede ver internal notes

5. **test_note_is_visible_to_all_company_agents**
    - Otros agents de la empresa ven la nota

6. **test_note_triggers_internal_note_added_event**
    - Verifica evento disparado

7. **test_user_cannot_create_internal_note**
    - USER → error 403

8. **test_unauthenticated_user_cannot_create**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/InternalNotes/ListInternalNotesTest.php`

**Total de Tests: 6**

1. **test_agent_can_list_internal_notes_from_company_ticket**
    - GET /tickets/:code/internal-notes
    - Agent → ✅

2. **test_internal_notes_are_ordered_by_created_at_asc**
    - Orden cronológico

3. **test_internal_note_includes_agent_information**
    - Incluye agent_id, agent_name

4. **test_user_cannot_list_internal_notes**
    - USER → error 403

5. **test_agent_cannot_list_notes_from_other_company_ticket**
    - Agent B → ticket empresa A → error 403

6. **test_unauthenticated_user_cannot_list**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/InternalNotes/UpdateInternalNoteTest.php`

**Total de Tests: 6**

1. **test_agent_can_update_own_internal_note**
    - PUT /tickets/:code/internal-notes/:id
    - Autor → ✅

2. **test_validates_updated_content_length**
    - content muy largo → error 422

3. **test_agent_cannot_update_other_agent_note**
    - Agent A → nota agent B → error 403

4. **test_updating_sets_updated_at_timestamp**
    - Verifica updated_at

5. **test_user_cannot_update_internal_note**
    - USER → error 403

6. **test_unauthenticated_user_cannot_update**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/InternalNotes/DeleteInternalNoteTest.php`

**Total de Tests: 5**

1. **test_agent_can_delete_own_internal_note**
    - DELETE /tickets/:code/internal-notes/:id
    - Autor → ✅

2. **test_agent_cannot_delete_other_agent_note**
    - Agent A → nota agent B → error 403

3. **test_deleted_note_returns_404**
    - DELETE → GET → 404

4. **test_user_cannot_delete_internal_note**
    - USER → error 403

5. **test_unauthenticated_user_cannot_delete**
    - Sin token → error 401

---

## 📎 ATTACHMENTS

### Archivo: `tests/Feature/TicketManagement/Attachments/UploadAttachmentTest.php`

**Total de Tests: 15**

1. **test_user_can_upload_attachment_to_own_ticket**
    - POST /tickets/:code/attachments
    - Form-data con file → ✅

2. **test_agent_can_upload_attachment_to_any_company_ticket**
    - Agent sube archivo → ✅

3. **test_validates_file_is_required**
    - Sin file → error 422

4. **test_validates_file_size_max_10mb**
    - Archivo 15 MB → error 413

5. **test_validates_file_type_allowed**
    - .exe → error 422
    - .pdf → ✅
    - .jpg → ✅

6. **test_allowed_file_types_list**
    - PDF, JPG, PNG, GIF, DOC, DOCX, XLS, XLSX, TXT, ZIP

7. **test_validates_max_5_attachments_per_ticket**
    - Ticket ya tiene 5 → intenta subir 6to → error 422

8. **test_file_is_stored_in_correct_path**
    - Verifica Storage path

9. **test_attachment_record_created_with_metadata**
    - file_name, file_url, file_type, file_size_bytes

10. **test_uploaded_by_user_id_is_set_correctly**
    - Verifica uploaded_by_user_id

11. **test_attachment_response_id_is_null_when_uploaded_to_ticket**
    - Directo al ticket → response_id=null

12. **test_user_cannot_upload_to_other_user_ticket**
    - Usuario A → ticket B → error 403

13. **test_agent_cannot_upload_to_other_company_ticket**
    - Agent B → ticket empresa A → error 403

14. **test_cannot_upload_to_closed_ticket**
    - status=closed → error 403

15. **test_unauthenticated_user_cannot_upload**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/Attachments/UploadAttachmentToResponseTest.php`

**Total de Tests: 8**

1. **test_can_upload_attachment_to_specific_response**
    - POST /tickets/:code/responses/:id/attachments
    - Verifica response_id poblado

2. **test_attachment_linked_to_response_appears_in_response_detail**
    - GET response → incluye attachments

3. **test_validates_response_belongs_to_ticket**
    - response de otro ticket → error 422

4. **test_author_of_response_can_upload_attachment**
    - Autor → ✅

5. **test_cannot_upload_to_response_after_30_minutes**
    - Response creada hace 35 min → error 403

6. **test_agent_cannot_upload_to_user_response**
    - Agent → response de USER → error 403

7. **test_max_5_attachments_applies_to_entire_ticket**
    - 5 totales (ticket + responses) → no más

8. **test_unauthenticated_user_cannot_upload**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/Attachments/ListAttachmentsTest.php`

**Total de Tests: 6**

1. **test_user_can_list_attachments_from_own_ticket**
    - GET /tickets/:code/attachments
    - Ve attachments del ticket + responses

2. **test_agent_can_list_attachments_from_any_company_ticket**
    - Agent → ✅

3. **test_attachments_include_uploader_information**
    - Incluye uploaded_by_user_id, uploaded_by_name

4. **test_attachments_include_response_context**
    - Si response_id != null → incluye info

5. **test_user_cannot_list_attachments_from_other_user_ticket**
    - Usuario A → ticket B → error 403

6. **test_unauthenticated_user_cannot_list**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/Attachments/DeleteAttachmentTest.php`

**Total de Tests: 8**

1. **test_uploader_can_delete_attachment_within_30_minutes**
    - DELETE /tickets/:code/attachments/:id
    - Subido hace 15 min → ✅

2. **test_cannot_delete_attachment_after_30_minutes**
    - Subido hace 40 min → error 403

3. **test_deleting_attachment_removes_file_from_storage**
    - Verifica Storage::delete llamado

4. **test_user_cannot_delete_other_user_attachment**
    - Usuario A → attachment B → error 403

5. **test_agent_cannot_delete_user_attachment**
    - Agent → attachment de USER → error 403

6. **test_cannot_delete_attachment_if_ticket_closed**
    - Ticket closed → error 403

7. **test_deleted_attachment_returns_404**
    - DELETE → GET → 404

8. **test_unauthenticated_user_cannot_delete**
    - Sin token → error 401

---

## ⭐ RATINGS

### Archivo: `tests/Feature/TicketManagement/Ratings/CreateRatingTest.php`

**Total de Tests: 12**

1. **test_user_can_rate_own_resolved_ticket**
    - POST /tickets/:code/rating
    - status=resolved → ✅

2. **test_user_can_rate_own_closed_ticket**
    - status=closed → ✅

3. **test_validates_rating_is_required**
    - Sin rating → error 422

4. **test_validates_rating_is_integer_between_1_and_5**
    - rating=0 → error 422
    - rating=6 → error 422
    - rating="excellent" → error 422

5. **test_comment_is_optional**
    - Sin comment → ✅

6. **test_validates_comment_max_length**
    - comment con 1500 chars → error (max 1000)

7. **test_rated_agent_id_is_saved_from_ticket_owner_agent**
    - Verifica rated_agent_id = owner_agent_id actual

8. **test_rating_triggers_ticket_rated_event**
    - Verifica evento disparado

9. **test_rating_sends_notification_to_rated_agent**
    - Agente recibe notificación

10. **test_cannot_rate_open_ticket**
    - status=open → error 403

11. **test_cannot_rate_pending_ticket**
    - status=pending → error 403

12. **test_cannot_rate_same_ticket_twice**
    - Ya tiene rating → error 409

---

### Archivo: `tests/Feature/TicketManagement/Ratings/GetRatingTest.php`

**Total de Tests: 6**

1. **test_user_can_view_own_ticket_rating**
    - GET /tickets/:code/rating
    - Usuario owner → ✅

2. **test_agent_can_view_rating_from_company_ticket**
    - Agent ve rating de tickets de su empresa

3. **test_rating_includes_customer_and_agent_info**
    - Incluye customer_name, rated_agent_name

4. **test_ticket_without_rating_returns_404**
    - Ticket sin rating → 404

5. **test_user_cannot_view_rating_from_other_user_ticket**
    - Usuario A → ticket B → error 403

6. **test_unauthenticated_user_cannot_view**
    - Sin token → error 401

---

### Archivo: `tests/Feature/TicketManagement/Ratings/UpdateRatingTest.php`

**Total de Tests: 8**

1. **test_user_can_update_rating_within_24_hours**
    - PUT /tickets/:code/rating
    - Calificado hace 12h → ✅

2. **test_cannot_update_rating_after_24_hours**
    - Calificado hace 30h → error 403

3. **test_can_update_rating_value**
    - Cambia de 4 a 5 estrellas

4. **test_can_update_comment**
    - Actualiza comment

5. **test_partial_update_preserves_unchanged_fields**
    - Solo actualiza comment → rating intacto

6. **test_updating_rating_preserves_rated_agent_id**
    - rated_agent_id NO cambia (histórico)

7. **test_user_cannot_update_rating_from_other_user_ticket**
    - Usuario A → rating ticket B → error 403

8. **test_unauthenticated_user_cannot_update**
    - Sin token → error 401

---

## 🔒 PERMISSIONS

### Archivo: `tests/Feature/TicketManagement/Permissions/TicketOwnershipTest.php`

**Total de Tests: 10**

1. **test_user_can_only_access_own_tickets**
    - Usuario A → ticket A → ✅
    - Usuario A → ticket B → error 403

2. **test_user_can_respond_only_to_own_tickets**
    - Usuario A → responde ticket B → error 403

3. **test_user_can_upload_attachments_only_to_own_tickets**
    - Usuario A → sube archivo ticket B → error 403

4. **test_user_can_rate_only_own_tickets**
    - Usuario A → califica ticket B → error 403

5. **test_agent_can_access_all_tickets_from_own_company**
    - Agent empresa A → ticket empresa A → ✅

6. **test_agent_cannot_access_tickets_from_other_companies**
    - Agent empresa A → ticket empresa B → error 403

7. **test_company_admin_has_full_access_to_own_company_tickets**
    - COMPANY_ADMIN → todos los tickets → ✅

8. **test_company_admin_cannot_access_other_company_tickets**
    - Admin A → ticket empresa B → error 403

9. **test_platform_admin_has_read_only_access_to_all_tickets**
    - PLATFORM_ADMIN → lee todo, no edita

10. **test_suspended_user_cannot_access_tickets**
    - Usuario suspendido → error 403

---

### Archivo: `tests/Feature/TicketManagement/Permissions/CompanyFollowingTest.php`

**Total de Tests: 6**

1. **test_user_can_create_ticket_in_any_company**
    - Usuario puede crear tickets sin restricción de following
    - Following NO es barrera de acceso

2. **test_following_affects_company_listing_order_not_access**
    - Empresas que sigue aparecen primero en listados
    - Pero TODAS las empresas son accesibles
    - No hay error 403 por "unfollowed"

3. **test_following_affects_notifications_not_access**
    - Usuario recibe notificaciones de empresas que sigue
    - Sigue viendo tickets de empresas no seguidas si es propietario
    - Ownership > following

4. **test_agent_does_not_need_to_follow_own_company**
    - Agent siempre tiene acceso a su empresa (por rol)
    - No necesita "follow"

5. **test_company_admin_does_not_need_to_follow_own_company**
    - Admin siempre tiene acceso a su empresa (por rol)
    - No necesita "follow"

6. **test_following_provides_information_priority_only**
    - Following es para información/UI prioritaria
    - NO es control de acceso

---

### Archivo: `tests/Feature/TicketManagement/Permissions/RoleBasedAccessTest.php`

**Total de Tests: 10**

1. **test_user_can_only_create_tickets**
    - USER puede: crear, responder propios
    - USER NO puede: resolver, cerrar, asignar

2. **test_agent_has_full_ticket_management_permissions**
    - AGENT puede: todo excepto eliminar

3. **test_company_admin_can_manage_categories**
    - COMPANY_ADMIN CRUD categorías

4. **test_company_admin_can_delete_closed_tickets**
    - COMPANY_ADMIN único que puede eliminar

5. **test_agent_cannot_create_tickets**
    - AGENT → POST /tickets → error 403

6. **test_user_cannot_see_internal_notes**
    - USER → GET /internal-notes → error 403

7. **test_agent_cannot_rate_tickets**
    - AGENT → POST /rating → error 403

8. **test_platform_admin_has_read_only_access**
    - PLATFORM_ADMIN ve todo, no edita

9. **test_role_validation_happens_before_business_logic**
    - Middleware valida roles primero

10. **test_expired_token_returns_401**
    - Token expirado → error 401

---

## 🧪 UNIT TESTS

### Archivo: `tests/Unit/TicketManagement/Services/TicketServiceTest.php`

**Total de Tests: 10**

1. **test_generates_unique_ticket_codes**
    - TKT-2025-00001, TKT-2025-00002...

2. **test_ticket_codes_reset_per_year**
    - 2024 termina en 00999
    - 2025 empieza en 00001

3. **test_validates_company_exists**
    - company_id inválido → exception
    - (Sin validación de "must follow")

4. **test_list_filters_by_owner_for_users**
    - USER solo ve propios

5. **test_list_shows_all_for_agents**
    - AGENT ve todos de empresa

6. **test_resolve_only_allows_resolved_or_pending_status**
    - open → exception

7. **test_close_only_allows_resolved_tickets_for_users**
    - USER + pending → exception

8. **test_reopen_validates_30_day_limit_for_users**
    - >30 días → exception

9. **test_assign_validates_agent_is_from_same_company**
    - Agent empresa B → exception

10. **test_delete_only_allows_closed_tickets**
    - pending → exception

---

### Archivo: `tests/Unit/TicketManagement/Services/ResponseServiceTest.php`

**Total de Tests: 6**

1. **test_determines_author_type_based_on_user_role**
    - USER → author_type=user
    - AGENT → author_type=agent

2. **test_validates_edit_time_limit_30_minutes**
    - >30 min → false

3. **test_validates_response_editable_only_by_author**
    - Otro usuario → false

4. **test_validates_response_not_editable_if_ticket_closed**
    - Ticket closed → false

5. **test_triggers_auto_assignment_only_for_first_agent_response**
    - Mock auto-assignment call

6. **test_delete_validates_same_conditions_as_edit**
    - Mismas validaciones

---

### Archivo: `tests/Unit/TicketManagement/Services/AttachmentServiceTest.php`

**Total de Tests: 8**

1. **test_validates_file_size_max_10mb**
    - >10MB → exception

2. **test_validates_allowed_file_types**
    - .exe → exception

3. **test_validates_max_5_attachments_per_ticket**
    - 6to archivo → exception

4. **test_stores_file_in_correct_path**
    - tickets/attachments/{uuid}

5. **test_generates_correct_file_url**
    - Storage::url() correcto

6. **test_extracts_file_metadata_correctly**
    - file_name, file_type, file_size_bytes

7. **test_delete_validates_30_minute_limit**
    - >30 min → false

8. **test_delete_removes_file_from_storage**
    - Storage::delete llamado

---

### Archivo: `tests/Unit/TicketManagement/Services/RatingServiceTest.php`

**Total de Tests: 6**

1. **test_validates_ticket_is_resolved_or_closed**
    - open/pending → exception

2. **test_validates_user_is_ticket_owner**
    - Otro usuario → exception

3. **test_validates_no_existing_rating**
    - Ya tiene rating → exception

4. **test_saves_rated_agent_id_from_current_owner**
    - Snapshot histórico

5. **test_update_validates_24_hour_limit**
    - >24h → exception

6. **test_update_preserves_rated_agent_id**
    - NO cambia aunque reasignen después

---

### Archivo: `tests/Unit/TicketManagement/Models/TicketTest.php`

**Total de Tests: 12**

1. **test_ticket_casts_status_to_enum**
    - status es TicketStatus enum

2. **test_belongs_to_creator_relationship**
    - $ticket->creator → User

3. **test_belongs_to_owner_agent_relationship**
    - $ticket->ownerAgent → User

4. **test_belongs_to_company_relationship**
    - $ticket->company → Company

5. **test_belongs_to_category_relationship**
    - $ticket->category → Category

6. **test_has_many_responses**
    - $ticket->responses → Collection

7. **test_has_many_internal_notes**
    - $ticket->internalNotes → Collection

8. **test_has_many_attachments**
    - $ticket->attachments → Collection

9. **test_has_one_rating**
    - $ticket->rating → TicketRating

10. **test_is_open_scope**
    - Ticket::open()->get()

11. **test_is_pending_scope**
    - Ticket::pending()->get()

12. **test_formatted_code_accessor**
    - formattedCode() → "TKT-2025-00123"

---

### Archivo: `tests/Unit/TicketManagement/Models/TicketFieldsTest.php`

**Total de Tests: 3**

1. **test_model_has_last_response_author_type_fillable**
    - Valida: Campo está en fillable array del modelo
    - Checkpoint: Campo puede ser asignado masivamente

2. **test_model_casts_last_response_author_type_correctly**
    - Valida: Cast es 'string'
    - Checkpoint: Tipo de dato correcto en modelo

3. **test_factory_creates_ticket_with_default_last_response_author_type**
    - Valida: Factory genera 'none' como default
    - Checkpoint: Valor por defecto correcto en factory

---

### Archivo: `tests/Unit/TicketManagement/Rules/ValidFileTypeTest.php`

**Total de Tests: 10**

1. **test_pdf_passes**
    - .pdf → passes()

2. **test_jpg_passes**
    - .jpg → passes()

3. **test_png_passes**
    - .png → passes()

4. **test_doc_passes**
    - .doc, .docx → passes()

5. **test_xls_passes**
    - .xls, .xlsx → passes()

6. **test_txt_passes**
    - .txt → passes()

7. **test_zip_passes**
    - .zip → passes()

8. **test_exe_fails**
    - .exe → fails()

9. **test_sh_fails**
    - .sh → fails()

10. **test_error_message_is_descriptive**
    - Mensaje lista tipos permitidos

---

### Archivo: `tests/Unit/TicketManagement/Rules/CanReopenTicketTest.php`

**Total de Tests: 6**

1. **test_user_can_reopen_within_30_days**
    - Cerrado hace 20 días → passes()

2. **test_user_cannot_reopen_after_30_days**
    - Cerrado hace 35 días → fails()

3. **test_agent_can_reopen_regardless_of_time**
    - Agent + 50 días → passes()

4. **test_must_be_resolved_or_closed_to_reopen**
    - open → fails()

5. **test_error_message_for_user_shows_30_day_limit**
    - Mensaje apropiado

6. **test_error_message_for_invalid_status**
    - "Solo se pueden reabrir tickets resolved/closed"

---

### Archivo: `tests/Unit/TicketManagement/Jobs/AutoCloseResolvedTicketsJobTest.php`

**Total de Tests: 5**

1. **test_closes_tickets_resolved_more_than_7_days_ago**
    - resolved_at < now()-7days → closed

2. **test_does_not_close_tickets_resolved_less_than_7_days_ago**
    - resolved_at = now()-5days → sigue resolved

3. **test_sets_closed_at_timestamp**
    - Verifica closed_at = now()

4. **test_only_affects_resolved_tickets**
    - open/pending/closed → no afectados

5. **test_logs_closed_tickets_count**
    - Log info con cantidad cerrados

---

## 🎭 INTEGRATION TESTS

### Archivo: `tests/Integration/TicketManagement/CompleteTicketFlowTest.php`

**Total de Tests: 7**

1. **test_complete_ticket_lifecycle_from_creation_to_rating**
    - User crea → Agent responde (auto-assign) → Resolve → User califica
    - Ajuste: Valida transiciones de last_response_author_type en todo el flujo

2. **test_multiple_agents_responding_preserves_first_assignment**
    - Agent A responde (asignado)
    - Agent B responde
    - owner_agent_id = Agent A
    - Ajuste: Valida last_response_author_type actualizado con cada respuesta

3. **test_ticket_with_attachments_flow**
    - Crear → Subir attachment → Responder con attachment

4. **test_internal_notes_invisible_to_user_throughout_lifecycle**
    - Agent agrega notas
    - User nunca las ve

5. **test_reopened_ticket_can_be_resolved_again**
    - Resolve → Close → Reopen → Resolve again

6. **test_auto_close_after_7_days**
    - Resolved hace 7 días
    - Job ejecuta
    - status → closed

7. **test_integration_complete_flow_validates_all_last_response_author_type_transitions** ⭐ CRÍTICO
    - Valida: Flujo completo (crear → agente responde → cliente responde → resolver)
    - Checkpoint: Campo se actualiza correctamente en cada paso
    - Checkpoint: crear='none', agente='agent', cliente='user', resolver mantiene 'user'

---

### Archivo: `tests/Integration/TicketManagement/AutoAssignmentFlowTest.php`

**Total de Tests: 5**

1. **test_first_agent_response_triggers_auto_assignment**
    - Ticket open, owner=null
    - Agent responde
    - Verifica trigger ejecutado
    - NUEVO: Valida last_response_author_type='agent' tras auto-assignment

2. **test_auto_assignment_changes_status_to_pending**
    - open → pending
    - Valida last_response_author_type actualizado correctamente

3. **test_auto_assignment_sets_first_response_at**
    - first_response_at timestamp

4. **test_user_response_does_not_trigger_auto_assignment**
    - Usuario responde → owner sigue null
    - Valida last_response_author_type='user'

5. **test_second_agent_response_does_not_change_owner**
    - Agent A asignado
    - Agent B responde
    - owner = Agent A
    - last_response_author_type actualizado a 'agent' (Agent B)

---

### Archivo: `tests/Integration/TicketManagement/PermissionsIntegrationTest.php`

**Total de Tests: 4**

1. **test_user_access_isolated_between_companies**
    - User empresa A no ve tickets empresa B

2. **test_agent_access_isolated_between_companies**
    - Agent empresa A no gestiona tickets empresa B

3. **test_following_status_affects_information_not_operations**
    - Following afecta notificaciones y orden en UI
    - No afecta acceso a crear/ver tickets

4. **test_role_changes_affect_permissions_immediately**
    - USER → promovido a AGENT → permisos actualizados

---

## 🎯 RESUMEN DE COBERTURA

### Por Categoría

| Categoría | Archivos | Tests | Cobertura |
|-----------|----------|-------|-----------|
| **Categories** | 4 | 26 | CRUD completo |
| **Tickets CRUD** | 5 | 65 | Crear, listar, ver, editar, eliminar (+7 tests) |
| **Tickets Actions** | 4 | 45 | Resolve, close, reopen, assign (+3 tests) |
| **Responses** | 4 | 48 | CRUD + auto-assignment + triggers (+8 tests) |
| **Internal Notes** | 4 | 25 | CRUD (solo agentes) |
| **Attachments** | 4 | 37 | Upload, list, delete + validaciones |
| **Ratings** | 3 | 26 | Crear, ver, actualizar |
| **Permissions** | 3 | 26 | Ownership, roles, company-isolation |
| **Unit Tests (Services)** | 4 | 30 | Business logic |
| **Unit Tests (Models/Rules)** | 4 | 31 | Relaciones, validaciones, factory (+3 tests) |
| **Unit Tests (Jobs)** | 1 | 5 | Auto-close job |
| **Integration Tests** | 3 | 19 | Flujos completos (+4 tests) |
| **TOTAL** | **43** | **383** | **100%** |

---

## ✅ CHECKLIST DE COBERTURA

### Funcionalidades Core
- ✅ CRUD completo de categorías
- ✅ CRUD completo de tickets
- ✅ Acciones de tickets (resolve, close, reopen, assign)
- ✅ Sistema de respuestas (conversación pública)
- ✅ Notas internas (colaboración agentes)
- ✅ Gestión de adjuntos con validaciones
- ✅ Sistema de calificaciones con histórico

### Reglas de Negocio
- ✅ Auto-assignment del primer agente
- ✅ Generación secuencial de ticket codes
- ✅ Cambios automáticos de status
- ✅ Auto-close después de 7 días
- ✅ Límites de tiempo para edición (30 min)
- ✅ Límite de reapertura (30 días para users)
- ✅ Límite de actualización rating (24h)

### Edge Cases
- ✅ Unicidad de nombres de categorías por empresa
- ✅ Validación de tipos de archivos
- ✅ Límite de 5 archivos por ticket
- ✅ Límite de tamaño 10MB por archivo
- ✅ No se puede eliminar categoría en uso
- ✅ No se puede calificar dos veces
- ✅ Snapshot histórico de rated_agent_id
- ✅ Transiciones de estado inválidas

### Security
- ✅ Autenticación requerida
- ✅ Autorización por roles (USER, AGENT, ADMIN)
- ✅ Ownership validation
- ✅ Company isolation
- ✅ Following affects information/notifications, not access control
- ✅ Time-based edit restrictions
- ✅ Agent-only internal notes

### Performance
- ✅ Paginación en listados
- ✅ Filtros eficientes
- ✅ Eager loading de relaciones
- ✅ Índices en BD validados
- ✅ Trigger optimizado (auto-assignment)

---

## 📊 COMPARACIÓN CON CONTENT MANAGEMENT

| Aspecto | Content Mgmt | Ticket Mgmt | Diferencia |
|---------|--------------|-------------|------------|
| Archivos de test | 40 | 43 | +3 |
| Total de tests | 326 | 383 | +57 |
| Features principales | 2 | 6 | +4 |
| Triggers automáticos | 0 | 2 | +2 (auto-assignment + pending→open) |
| Time restrictions | 0 | 2 | +2 |
| Campos de tracking | 0 | 1 | +1 (last_response_author_type) |

**Conclusión**: Ticket Management es más complejo debido a:
- Auto-assignment con trigger PostgreSQL
- Trigger automático PENDING→OPEN cuando cliente responde
- Campo last_response_author_type para UI y tracking
- Doble conversación (responses + internal notes)
- Múltiples restricciones de tiempo
- Sistema de calificaciones con snapshot histórico
- Más actores (USER, AGENT, ADMIN) con permisos diferentes

**Actualización (last_response_author_type)**:
- +25 tests nuevos agregados
- +37 tests modificados para incluir validaciones del nuevo campo
- 3 nuevos tests unitarios para modelo/factory
- Soporte completo para filtros: owner_agent_id=null (literal), created_by=me, last_response_author_type

---

**FIN DEL PLAN DE TESTING COMPLETO** 🎉

---

## 🆕 RESUMEN DE ACTUALIZACIÓN - Campo last_response_author_type

### Tests Afectados (37 modificaciones)

#### CreateTicketTest (2 modificaciones):
- **test_user_can_create_ticket**: Agregada validación de last_response_author_type='none' en response
- **test_ticket_starts_with_status_open**: Incluida validación de last_response_author_type='none'

#### ListTicketsTest (18 modificaciones):
- TODOS los 18 tests actualizados para soportar:
  - Nuevo query param: last_response_author_type
  - Nuevo query param: owner_agent_id=null (string literal)
  - Nuevo query param: created_by=me
  - Validación del campo en todos los responses

#### GetTicketTest (1 modificación):
- **test_ticket_detail_includes_complete_information**: Agregado last_response_author_type al response esperado

#### CreateResponseTest (6 modificaciones):
- **test_first_agent_response_triggers_auto_assignment**: Validar last_response_author_type='agent'
- **test_auto_assignment_only_happens_once**: Validar campo en cada respuesta
- **test_user_response_does_not_trigger_auto_assignment**: Validar last_response_author_type='user'
- 3 tests adicionales con ajustes menores

#### ListResponsesTest (3 modificaciones):
- **test_responses_are_ordered_by_created_at_asc**: Valida author_type correcto
- **test_response_includes_author_information**: Verifica coherencia con last_response_author_type
- **test_response_includes_attachments**: Última response actualiza last_response_author_type

#### UpdateTicketTest (1 modificación):
- **test_partial_update_preserves_unchanged_fields**: Verifica que last_response_author_type no cambia

#### AssignTicketTest (1 modificación):
- **test_agent_can_assign_ticket_to_another_agent**: Verifica que last_response_author_type no cambia

#### ResolveTicketTest (1 modificación):
- **test_agent_can_resolve_ticket**: Valida que campo persiste durante resolución

#### CloseTicketTest (1 modificación):
- **test_agent_can_close_any_ticket**: Valida que campo persiste durante cierre

#### ReopenTicketTest (1 modificación):
- **test_user_can_reopen_own_resolved_ticket**: Valida que campo persiste durante reapertura

#### AutoAssignmentFlowTest (2 modificaciones):
- **test_first_agent_response_triggers_auto_assignment**: Valida last_response_author_type='agent'
- **test_auto_assignment_changes_status_to_pending**: Valida actualización correcta del campo

#### CompleteTicketFlowTest (2 modificaciones):
- **test_complete_ticket_lifecycle_from_creation_to_rating**: Valida transiciones completas
- **test_multiple_agents_responding_preserves_first_assignment**: Valida actualizaciones con cada respuesta

### Tests Nuevos (25 agregados)

#### CreateTicketTest (+1):
- test_created_ticket_has_correct_initial_last_response_author_type

#### ListTicketsTest (+6):
- test_filter_by_last_response_author_type_none
- test_filter_by_last_response_author_type_user
- test_filter_by_last_response_author_type_agent
- test_filter_by_owner_agent_id_null_literal
- test_filter_by_created_by_user_id
- test_combine_filters_owner_null_and_last_response_author_type_none

#### CreateResponseTest (+8) - CRÍTICOS:
- test_user_response_to_pending_ticket_changes_status_to_open ⭐
- test_user_response_to_pending_ticket_updates_last_response_author_type_to_user
- test_agent_response_to_open_ticket_sets_last_response_author_type_to_agent
- test_multiple_user_responses_keep_last_response_author_type_as_user
- test_alternating_responses_update_last_response_author_type_correctly
- test_pending_to_open_transition_preserves_owner_agent_id
- test_user_response_to_open_ticket_does_not_change_status
- test_agent_response_to_pending_ticket_does_not_change_status

#### GetTicketTest (+1):
- test_get_ticket_detail_includes_last_response_author_type

#### ResolveTicketTest (+1):
- test_last_response_author_type_persists_after_ticket_resolve

#### CloseTicketTest (+1):
- test_last_response_author_type_persists_after_ticket_close

#### ReopenTicketTest (+1):
- test_last_response_author_type_persists_after_ticket_reopen

#### CompleteTicketFlowTest (+1) - CRÍTICO:
- test_integration_complete_flow_validates_all_last_response_author_type_transitions ⭐

#### Unit Tests - Model/Factory (+3):
- test_model_has_last_response_author_type_fillable
- test_model_casts_last_response_author_type_correctly
- test_factory_creates_ticket_with_default_last_response_author_type

#### Resources (+2):
- test_ticket_resource_includes_last_response_author_type
- test_ticket_list_resource_includes_last_response_author_type

### Archivos de Test Afectados

**Modificados (12 archivos)**:
1. CreateTicketTest.php
2. ListTicketsTest.php
3. GetTicketTest.php
4. UpdateTicketTest.php
5. CreateResponseTest.php
6. ListResponsesTest.php
7. AssignTicketTest.php
8. ResolveTicketTest.php
9. CloseTicketTest.php
10. ReopenTicketTest.php
11. AutoAssignmentFlowTest.php
12. CompleteTicketFlowTest.php

**Nuevos (3 archivos)**:
- TicketFieldsTest.php (Unit Tests para validar modelo y factory)
- TicketResourceTest.php (Resource Test para validar serialización)
- TicketListResourceTest.php (List Resource Test para validar en listados)

### Estadísticas Finales

- **Tests Totales**: 358 → 383 (+25 nuevos)
- **Archivos de Test**: 42 → 45 (+3 nuevos)
- **Tests Modificados**: 37
- **Tests Nuevos**: 25
- **Cobertura**: 100% (incluyendo nuevo campo y triggers)

---

> **Próximo paso**: Implementar estos tests siguiendo TDD (Test-Driven Development)
