# Plan de Implementación Feature Tests - Ticket Management
## Versión 1.0 - Feature Tests Only

---

## 📊 Estado Actual

**Tests Verdes**: 26/226 (11.5%)
**Tests Rojos**: 200/226 (88.5%)

| Grupo | Total | ✅ Verde | ❌ Rojo | % Completo |
|-------|-------|----------|---------|------------|
| Categories | 26 | 26 | 0 | 100% |
| Tickets CRUD | 70 | 0 | 70 | 0% |
| Responses | 48 | 0 | 48 | 0% |
| Attachments | 37 | 4 | 33 | 10.8% |
| Tickets Actions | 45 | 0 | 45 | 0% |

---

## 🎯 Objetivo

Implementar **SOLO Feature tests** de Ticket Management en el orden correcto, grupo por grupo, validando GREEN antes de avanzar al siguiente.

---

## 📐 Arquitectura y Reglas Fundamentales

### JWT Stateless (CRÍTICO)
- ❌ NO usar Laravel sessions
- ✅ Usar `JWTHelper::getAuthenticatedUser()`
- ✅ Usar `JWTHelper::getCompanyIdFromJWT('AGENT')` para contexto de compañía
- ✅ JWT payload contiene: `user_id`, `email`, `roles: [{code, company_id}]`

### Middlewares Existentes (REUTILIZAR)
- `AuthenticateJwt` → Valida JWT, establece usuario autenticado
- `EnsureUserHasRole` → Verifica roles (`->middleware('role:AGENT')`)
- ❌ **NO crear nuevos middlewares**
- ✅ Usar **Policies** para autorización granular

### Patrones de Implementación
1. **Services**: Lógica de negocio (TicketService, ResponseService, etc.)
2. **Policies**: Autorización (TicketPolicy, ResponsePolicy, etc.)
3. **Form Requests**: Validación de input (StoreTicketRequest, etc.)
4. **Resources**: Transformación de output (TicketResource, etc.)
5. **Controllers**: Orquestación delgada (TicketController, etc.)

### Base de Datos
- Schema: `ticketing`
- Triggers PostgreSQL para:
  - Auto-assignment (primera respuesta de agente)
  - Status transitions (PENDING → OPEN cuando usuario responde)
  - Actualización de `last_response_author_type`

---

## 📋 Fases de Implementación

### FASE 0: Preparación Base de Datos
**Objetivo**: Crear toda la infraestructura de BD necesaria

**Archivos a crear**:
1. `database/migrations/2024_01_10_000001_create_ticketing_schema.php`
2. `database/migrations/2024_01_10_000002_create_tickets_table.php`
3. `database/migrations/2024_01_10_000003_create_ticket_responses_table.php`
4. `database/migrations/2024_01_10_000004_create_ticket_attachments_table.php`
5. `database/migrations/2024_01_10_000005_create_ticket_ratings_table.php`
6. `database/migrations/2024_01_10_000006_create_ticket_sequences_table.php`
7. `database/migrations/2024_01_10_000007_create_ticket_triggers.php`

**Tests que pasarán**:
- `AttachmentStructureTest::attachment_must_have_ticket_id_not_null` ✅
- `AttachmentStructureTest::attachment_can_exist_without_response_id` ✅
- `AttachmentStructureTest::attachment_response_id_must_reference_valid_response` ✅
- `AttachmentStructureTest::multiple_attachments_per_ticket_relationship` ✅

**Comando verificación**:
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Attachments/AttachmentStructureTest.php
```

**Duración estimada**: 2-3 horas

---

### FASE 1: Tickets CRUD (Base Fundamental)
**Objetivo**: Implementar creación, lectura, actualización y eliminación de tickets

**Dependencias**: FASE 0 completa

**Por qué primero**:
- Responses y Attachments dependen de Tickets
- Es la entidad central del feature
- 70 tests (31% del total)

#### Archivos a crear:

**Models**:
1. `app/Features/TicketManagement/Models/Ticket.php`
   - Relations: `creator`, `ownerAgent`, `category`, `company`, `responses`, `attachments`, `rating`
   - Casts: `status` → `TicketStatus::class`
   - Fillable: `title`, `description`, `company_id`, `category_id`, etc.

**Services**:
2. `app/Features/TicketManagement/Services/TicketCodeGeneratorService.php`
   - `generate(string $companyId): string` → TKT-2025-00001
   - Manejo de secuencias por año con PostgreSQL sequences

3. `app/Features/TicketManagement/Services/TicketService.php`
   - `create(array $data, User $user): Ticket`
   - `list(array $filters, User $user): LengthAwarePaginator`
   - `show(string $ticketId, User $user): Ticket`
   - `update(Ticket $ticket, array $data): Ticket`
   - `delete(Ticket $ticket): bool`

**Policies**:
4. `app/Features/TicketManagement/Policies/TicketPolicy.php`
   - `viewAny(User $user): bool`
   - `view(User $user, Ticket $ticket): bool`
   - `create(User $user): bool` → Solo USER role
   - `update(User $user, Ticket $ticket): bool`
   - `delete(User $user, Ticket $ticket): bool` → Solo COMPANY_ADMIN, solo CLOSED

**Form Requests**:
5. `app/Features/TicketManagement/Requests/StoreTicketRequest.php`
   - Validaciones: `title` (required, min:5, max:200)
   - `description` (required, min:20, max:5000)
   - `company_id` (required, exists)
   - `category_id` (required, exists, active)

6. `app/Features/TicketManagement/Requests/UpdateTicketRequest.php`
   - Validaciones similares pero opcionales (partial update)
   - No permitir cambio manual de `status` a PENDING

7. `app/Features/TicketManagement/Requests/ListTicketsRequest.php`
   - Filtros: `status`, `category_id`, `owner_agent_id`, `created_by_user_id`
   - `search` (título/descripción)
   - `date_from`, `date_to`
   - `sort_by` (created_at, updated_at)
   - `last_response_author_type` (none, user, agent)

**Resources**:
8. `app/Features/TicketManagement/Resources/TicketResource.php`
   - Detalle completo con relationships
   - Include: `creator`, `ownerAgent`, `category`, `company`
   - Counts: `responses_count`, `attachments_count`

9. `app/Features/TicketManagement/Resources/TicketListResource.php`
   - Versión simplificada para listados
   - Solo datos esenciales + counts

**Controllers**:
10. `app/Features/TicketManagement/Controllers/TicketController.php`
    - `index(ListTicketsRequest $request): JsonResponse`
    - `store(StoreTicketRequest $request): JsonResponse`
    - `show(string $id): JsonResponse`
    - `update(UpdateTicketRequest $request, string $id): JsonResponse`
    - `destroy(string $id): JsonResponse`

**Routes**:
11. `routes/api.php` (agregar grupo):
```php
Route::prefix('ticketing')->middleware(['jwt.auth'])->group(function () {
    Route::apiResource('tickets', TicketController::class);
});
```

**Events** (para CreateTicketTest::ticket_creation_triggers_event):
12. `app/Features/TicketManagement/Events/TicketCreated.php`
13. `app/Features/TicketManagement/Listeners/NotifyAgentsOnTicketCreated.php`

**Exceptions**:
14. `app/Features/TicketManagement/Exceptions/TicketNotFoundException.php`
15. `app/Features/TicketManagement/Exceptions/CannotDeleteActiveTicketException.php`

#### Tests que pasarán (70 total):

**CreateTicketTest** (16 tests):
- ✅ user_can_create_ticket
- ✅ agent_cannot_create_ticket
- ✅ company_admin_cannot_create_ticket
- ✅ unauthenticated_user_cannot_create_ticket
- ✅ validates_required_fields
- ✅ validates_title_length
- ✅ validates_description_length
- ✅ validates_company_exists
- ✅ validates_category_exists_and_is_active
- ✅ user_can_create_ticket_in_any_company
- ✅ ticket_code_is_generated_automatically
- ✅ ticket_code_is_sequential_per_year
- ✅ ticket_starts_with_status_open
- ✅ created_by_user_id_is_set_to_authenticated_user
- ✅ ticket_creation_triggers_event
- ✅ created_ticket_has_correct_initial_last_response_author_type (none)

**GetTicketTest** (11 tests):
- ✅ unauthenticated_user_cannot_view_ticket
- ✅ user_can_view_own_ticket
- ✅ user_cannot_view_other_user_ticket
- ✅ agent_can_view_any_ticket_from_own_company
- ✅ agent_cannot_view_ticket_from_other_company
- ✅ company_admin_can_view_any_ticket_from_own_company
- ✅ ticket_detail_includes_complete_information
- ✅ ticket_detail_includes_responses_count
- ✅ ticket_detail_includes_timeline
- ✅ nonexistent_ticket_returns_404
- ✅ get_ticket_detail_includes_last_response_author_type

**ListTicketsTest** (25 tests):
- ✅ unauthenticated_user_cannot_list_tickets
- ✅ user_can_list_own_tickets
- ✅ user_cannot_see_other_users_tickets
- ✅ agent_can_list_all_company_tickets
- ✅ agent_cannot_see_other_company_tickets
- ✅ filter_by_status_open_works
- ✅ filter_by_status_pending_works
- ✅ filter_by_status_resolved_works
- ✅ filter_by_status_closed_works
- ✅ filter_by_category_works
- ✅ filter_by_owner_agent_id_works
- ✅ filter_owner_agent_id_me_resolves_to_authenticated_user
- ✅ filter_by_created_by_user_id
- ✅ search_in_title_works
- ✅ search_in_description_works
- ✅ filter_by_date_range
- ✅ sort_by_created_at_desc_default
- ✅ sort_by_updated_at_asc
- ✅ pagination_works
- ✅ includes_related_data_in_list
- ✅ user_can_view_own_tickets_regardless_of_following
- ✅ filter_by_last_response_author_type_none
- ✅ filter_by_last_response_author_type_user
- ✅ filter_by_last_response_author_type_agent
- ✅ filter_by_owner_agent_id_null_literal
- ✅ combine_filters_owner_null_and_last_response_author_type_none

**UpdateTicketTest** (11 tests):
- ✅ unauthenticated_user_cannot_update_ticket
- ✅ user_can_update_own_ticket_when_status_open
- ✅ user_cannot_update_ticket_when_status_pending
- ✅ user_cannot_update_ticket_when_status_resolved
- ✅ user_cannot_update_other_user_ticket
- ✅ agent_can_update_ticket_title_and_category
- ✅ agent_cannot_manually_change_status_to_pending
- ✅ validates_updated_title_length
- ✅ validates_updated_category_exists
- ✅ partial_update_preserves_unchanged_fields
- ✅ agent_cannot_update_other_company_ticket

**DeleteTicketTest** (8 tests - NOTA: el output se cortó):
- ✅ unauthenticated_user_cannot_delete_ticket
- ✅ user_cannot_delete_ticket
- ✅ agent_cannot_delete_ticket
- ✅ company_admin_can_delete_closed_ticket
- ✅ cannot_delete_open_ticket
- ✅ cannot_delete_pending_ticket
- ✅ cannot_delete_resolved_ticket
- ✅ deleting_ticket_cascades_to_related_data

**Comando verificación**:
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/CRUD/
```

**Duración estimada**: 8-10 horas

**⚠️ CRÍTICO - Reglas de Negocio**:
1. Solo USER role puede crear tickets (NO agent, NO company_admin)
2. Ticket code: `TKT-{YEAR}-{SEQUENCE}` secuencial por año
3. Status inicial siempre `open`
4. `last_response_author_type` inicial: `none`
5. Solo COMPANY_ADMIN puede eliminar tickets CLOSED
6. Cascada al eliminar: responses, attachments, ratings
7. Usuario solo ve sus propios tickets
8. Agente ve todos los tickets de su compañía
9. Filtro `owner_agent_id=me` → resuelve a ID del agente autenticado
10. Filtro `owner_agent_id=null` → tickets sin asignar

---

### FASE 2: Responses (Lógica de Triggers)
**Objetivo**: Implementar respuestas y triggers de auto-assignment + status transitions

**Dependencias**: FASE 1 completa (Tickets CRUD funcionando)

**Por qué segundo**:
- Depende de tickets existiendo
- Implementa triggers críticos (auto-assignment, PENDING→OPEN)
- Actualiza `last_response_author_type`
- 48 tests (21% del total)

#### Archivos a crear:

**Models**:
1. `app/Features/TicketManagement/Models/TicketResponse.php`
   - Relations: `ticket`, `author`
   - Casts: `author_type` → `AuthorType::class`
   - Fillable: `ticket_id`, `author_id`, `author_type`, `content`

**Services**:
2. `app/Features/TicketManagement/Services/ResponseService.php`
   - `create(Ticket $ticket, array $data, User $user): TicketResponse`
   - `list(Ticket $ticket, User $user): Collection`
   - `update(TicketResponse $response, array $data): TicketResponse`
   - `delete(TicketResponse $response): bool`
   - **CRÍTICO**: Verificar ventana de 30 minutos para editar/eliminar

**Policies**:
3. `app/Features/TicketManagement/Policies/ResponsePolicy.php`
   - `create(User $user, Ticket $ticket): bool`
   - `update(User $user, TicketResponse $response): bool` → Solo autor, 30 min
   - `delete(User $user, TicketResponse $response): bool` → Solo autor, 30 min
   - Validar ticket no está CLOSED

**Form Requests**:
4. `app/Features/TicketManagement/Requests/StoreResponseRequest.php`
   - `content` (required, string, min:1, max:10000)

5. `app/Features/TicketManagement/Requests/UpdateResponseRequest.php`
   - `content` (required, string, min:1, max:10000)

**Resources**:
6. `app/Features/TicketManagement/Resources/ResponseResource.php`
   - Include: `author` (id, name, email, author_type)
   - Include: `attachments` (if loaded)
   - Timestamps: `created_at`, `updated_at`

**Controllers**:
7. `app/Features/TicketManagement/Controllers/ResponseController.php`
   - `index(string $ticketId): JsonResponse` → Lista responses del ticket
   - `store(StoreResponseRequest $request, string $ticketId): JsonResponse`
   - `update(UpdateResponseRequest $request, string $ticketId, string $responseId): JsonResponse`
   - `destroy(string $ticketId, string $responseId): JsonResponse`

**Routes**:
```php
Route::prefix('ticketing')->middleware(['jwt.auth'])->group(function () {
    // Existing tickets routes...

    Route::prefix('tickets/{ticket}')->group(function () {
        Route::apiResource('responses', ResponseController::class)->except(['show']);
    });
});
```

**Events**:
8. `app/Features/TicketManagement/Events/ResponseAdded.php`
9. `app/Features/TicketManagement/Listeners/NotifyOnResponseAdded.php`

**Triggers Database** (ya creados en FASE 0, pero verificar):
- Trigger `assign_ticket_owner()` → Auto-assignment
- Trigger `return_pending_to_open_on_user_response()` → PENDING→OPEN

**Exceptions**:
10. `app/Features/TicketManagement/Exceptions/CannotModifyResponseException.php` (30 min window)

#### Tests que pasarán (48 total):

**CreateResponseTest** (23 tests):
- ✅ user_can_respond_to_own_ticket
- ✅ agent_can_respond_to_any_company_ticket
- ✅ validates_response_content_is_required
- ✅ validates_response_content_length
- ✅ author_type_is_set_automatically
- ✅ first_agent_response_triggers_auto_assignment
- ✅ auto_assignment_only_happens_once
- ✅ first_agent_response_sets_first_response_at
- ✅ user_response_does_not_trigger_auto_assignment
- ✅ response_triggers_response_added_event
- ✅ response_sends_notification_to_relevant_parties
- ✅ user_cannot_respond_to_other_user_ticket
- ✅ agent_cannot_respond_to_other_company_ticket
- ✅ cannot_respond_to_closed_ticket
- ✅ unauthenticated_user_cannot_respond
- ✅ user_response_to_pending_ticket_changes_status_to_open
- ✅ user_response_to_pending_ticket_updates_last_response_author_type_to_user
- ✅ agent_response_to_open_ticket_sets_last_response_author_type_to_agent
- ✅ multiple_user_responses_keep_last_response_author_type_as_user
- ✅ alternating_responses_update_last_response_author_type_correctly
- ✅ pending_to_open_transition_preserves_owner_agent_id
- ✅ user_response_to_open_ticket_does_not_change_status
- ✅ agent_response_to_pending_ticket_does_not_change_status

**ListResponsesTest** (8 tests):
- ✅ user_can_list_responses_from_own_ticket
- ✅ agent_can_list_responses_from_any_company_ticket
- ✅ responses_are_ordered_by_created_at_asc
- ✅ response_includes_author_information
- ✅ response_includes_attachments
- ✅ user_cannot_list_responses_from_other_user_ticket
- ✅ agent_cannot_list_responses_from_other_company_ticket
- ✅ unauthenticated_user_cannot_list_responses

**UpdateResponseTest** (10 tests):
- ✅ author_can_update_own_response_within_30_minutes
- ✅ cannot_update_response_after_30_minutes
- ✅ validates_updated_content_length
- ✅ user_cannot_update_other_user_response
- ✅ agent_cannot_update_other_agent_response
- ✅ cannot_update_response_if_ticket_closed
- ✅ partial_update_works
- ✅ updating_preserves_original_created_at
- ✅ updating_sets_updated_at_timestamp
- ✅ unauthenticated_user_cannot_update

**DeleteResponseTest** (7 tests):
- ✅ author_can_delete_own_response_within_30_minutes
- ✅ cannot_delete_response_after_30_minutes
- ✅ user_cannot_delete_other_user_response
- ✅ cannot_delete_response_if_ticket_closed
- ✅ deleting_response_cascades_to_attachments
- ✅ deleted_response_returns_404
- ✅ unauthenticated_user_cannot_delete

**Comando verificación**:
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Responses/
```

**Duración estimada**: 6-8 horas

**⚠️ CRÍTICO - Reglas de Negocio**:
1. **Auto-assignment** (trigger):
   - Primera respuesta de AGENT → `owner_agent_id` = agent_id, status = PENDING
   - Solo si `owner_agent_id` es NULL
   - Actualiza `first_response_at`
   - Actualiza `last_response_author_type` = 'agent'

2. **Status transition PENDING → OPEN** (trigger):
   - Cuando USER responde a ticket PENDING
   - Preserva `owner_agent_id`
   - Actualiza `last_response_author_type` = 'user'

3. **Ventana de edición/eliminación**:
   - Solo autor puede editar/eliminar
   - Solo dentro de 30 minutos desde creación
   - No permitido si ticket CLOSED

4. **last_response_author_type**:
   - Actualizado por trigger en cada respuesta
   - Valores: 'none', 'user', 'agent'

---

### FASE 3: Attachments (Manejo de Archivos)
**Objetivo**: Implementar upload/delete de archivos en tickets y responses

**Dependencias**: FASE 2 completa (Responses funcionando)

**Por qué tercero**:
- Depende de Tickets y Responses
- Manejo de storage (filesystem)
- 37 tests (4 ya verdes = 33 nuevos, 14.6% del total)

#### Archivos a crear:

**Models**:
1. `app/Features/TicketManagement/Models/TicketAttachment.php`
   - Relations: `ticket`, `response`, `uploadedBy`
   - Fillable: `ticket_id`, `response_id`, `uploaded_by_user_id`, `file_path`, `file_name`, `file_size`, `file_type`

**Services**:
2. `app/Features/TicketManagement/Services/AttachmentService.php`
   - `uploadToTicket(Ticket $ticket, UploadedFile $file, User $user): TicketAttachment`
   - `uploadToResponse(TicketResponse $response, UploadedFile $file, User $user): TicketAttachment`
   - `list(Ticket $ticket, User $user): Collection`
   - `delete(TicketAttachment $attachment): bool`
   - **CRÍTICO**: Validar max 5 attachments por ticket
   - **CRÍTICO**: Eliminar archivo de storage al borrar registro

**Rules**:
3. `app/Features/TicketManagement/Rules/ValidFileType.php`
   - Tipos permitidos: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx, txt, zip, rar
   - Max 10MB

**Policies**:
4. `app/Features/TicketManagement/Policies/AttachmentPolicy.php`
   - `create(User $user, Ticket $ticket): bool`
   - `delete(User $user, TicketAttachment $attachment): bool`
   - Solo uploader puede eliminar
   - Solo dentro de 30 minutos
   - No permitido si ticket CLOSED

**Form Requests**:
5. `app/Features/TicketManagement/Requests/UploadAttachmentRequest.php`
   - `file` (required, file, max:10240, ValidFileType)
   - `response_id` (nullable, exists:ticketing.ticket_responses)

**Resources**:
6. `app/Features/TicketManagement/Resources/AttachmentResource.php`
   - Include: `uploaded_by` (id, name)
   - Include: `response` context (if linked)
   - Metadata: `file_name`, `file_size`, `file_type`, `created_at`
   - URL: `download_url`

**Controllers**:
7. `app/Features/TicketManagement/Controllers/AttachmentController.php`
   - `index(string $ticketId): JsonResponse` → Lista attachments
   - `store(UploadAttachmentRequest $request, string $ticketId): JsonResponse`
   - `destroy(string $ticketId, string $attachmentId): JsonResponse`
   - `download(string $ticketId, string $attachmentId)` → Download file

**Routes**:
```php
Route::prefix('ticketing')->middleware(['jwt.auth'])->group(function () {
    Route::prefix('tickets/{ticket}')->group(function () {
        Route::get('attachments', [AttachmentController::class, 'index']);
        Route::post('attachments', [AttachmentController::class, 'store']);
        Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);
        Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);
    });
});
```

**Storage Config**:
- Disk: `local` (para desarrollo), `s3` (para producción)
- Path: `ticketing/attachments/{ticket_id}/{filename}`

**Exceptions**:
8. `app/Features/TicketManagement/Exceptions/AttachmentLimitExceededException.php`
9. `app/Features/TicketManagement/Exceptions/CannotModifyAttachmentException.php`

#### Tests que pasarán (33 nuevos):

**UploadAttachmentTest** (15 tests):
- ✅ user_can_upload_attachment_to_own_ticket
- ✅ agent_can_upload_attachment_to_any_company_ticket
- ✅ validates_file_is_required
- ✅ validates_file_size_max_10mb
- ✅ validates_file_type_allowed
- ✅ allowed_file_types_list
- ✅ validates_max_5_attachments_per_ticket
- ✅ file_is_stored_in_correct_path
- ✅ attachment_record_created_with_metadata
- ✅ uploaded_by_user_id_is_set_correctly
- ✅ attachment_response_id_is_null_when_uploaded_to_ticket
- ✅ user_cannot_upload_to_other_user_ticket
- ✅ agent_cannot_upload_to_other_company_ticket
- ✅ cannot_upload_to_closed_ticket
- ✅ unauthenticated_user_cannot_upload

**UploadAttachmentToResponseTest** (8 tests):
- ✅ can_upload_attachment_to_specific_response
- ✅ attachment_linked_to_response_appears_in_response_detail
- ✅ validates_response_belongs_to_ticket
- ✅ author_of_response_can_upload_attachment
- ✅ cannot_upload_to_response_after_30_minutes
- ✅ agent_cannot_upload_to_user_response
- ✅ max_5_attachments_applies_to_entire_ticket
- ✅ unauthenticated_user_cannot_upload

**DeleteAttachmentTest** (7 tests):
- ✅ uploader_can_delete_attachment_within_30_minutes
- ✅ cannot_delete_attachment_after_30_minutes
- ✅ deleting_attachment_removes_file_from_storage
- ✅ user_cannot_delete_other_user_attachment
- ✅ agent_cannot_delete_user_attachment
- ✅ cannot_delete_attachment_if_ticket_closed
- ✅ deleted_attachment_returns_404
- ✅ unauthenticated_user_cannot_delete

**ListAttachmentsTest** (6 tests):
- ✅ user_can_list_attachments_from_own_ticket
- ✅ agent_can_list_attachments_from_any_company_ticket
- ✅ attachments_include_uploader_information
- ✅ attachments_include_response_context
- ✅ user_cannot_list_attachments_from_other_user_ticket
- ✅ unauthenticated_user_cannot_list

**Comando verificación**:
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Attachments/
```

**Duración estimada**: 5-6 horas

**⚠️ CRÍTICO - Reglas de Negocio**:
1. Max 5 attachments por ticket (total, incluyendo responses)
2. Max 10MB por archivo
3. Tipos permitidos: jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx, txt, zip, rar
4. Solo uploader puede eliminar, dentro de 30 minutos
5. Eliminar attachment → eliminar archivo de storage (no solo DB)
6. No permitido upload/delete si ticket CLOSED
7. Upload a response: validar que response pertenece al ticket
8. No permitido upload a response después de 30 min de crear response

---

### FASE 4: Tickets Actions (Estado de Tickets)
**Objetivo**: Implementar acciones de estado (assign, resolve, close, reopen)

**Dependencias**: FASE 3 completa (Attachments funcionando)

**Por qué último**:
- Depende de todo lo anterior (necesita tickets, responses)
- Lógica de transiciones de estado
- 45 tests (20% del total)

#### Archivos a crear:

**Services** (extender TicketService):
1. Agregar métodos a `TicketService`:
   - `assign(Ticket $ticket, string $newAgentId, ?string $note, User $user): Ticket`
   - `resolve(Ticket $ticket, ?string $note, User $user): Ticket`
   - `close(Ticket $ticket, User $user): Ticket`
   - `reopen(Ticket $ticket, ?string $reason, User $user): Ticket`

**Rules**:
2. `app/Features/TicketManagement/Rules/CanReopenTicket.php`
   - Validar 30 días para USER role
   - Siempre permitido para AGENT role

**Form Requests**:
3. `app/Features/TicketManagement/Requests/AssignTicketRequest.php`
   - `new_agent_id` (required, exists:users, role=AGENT, same company)
   - `note` (nullable, string, max:500)

4. `app/Features/TicketManagement/Requests/ResolveTicketRequest.php`
   - `resolution_note` (nullable, string, max:1000)

5. `app/Features/TicketManagement/Requests/CloseTicketRequest.php`
   - (Vacío, solo para consistencia)

6. `app/Features/TicketManagement/Requests/ReopenTicketRequest.php`
   - `reason` (nullable, string, max:500)

**Controllers**:
7. `app/Features/TicketManagement/Controllers/TicketActionController.php`
   - `assign(AssignTicketRequest $request, string $id): JsonResponse`
   - `resolve(ResolveTicketRequest $request, string $id): JsonResponse`
   - `close(CloseTicketRequest $request, string $id): JsonResponse`
   - `reopen(ReopenTicketRequest $request, string $id): JsonResponse`

**Routes**:
```php
Route::prefix('ticketing')->middleware(['jwt.auth'])->group(function () {
    Route::prefix('tickets/{ticket}')->group(function () {
        Route::post('assign', [TicketActionController::class, 'assign']);
        Route::post('resolve', [TicketActionController::class, 'resolve']);
        Route::post('close', [TicketActionController::class, 'close']);
        Route::post('reopen', [TicketActionController::class, 'reopen']);
    });
});
```

**Events**:
8. `app/Features/TicketManagement/Events/TicketAssigned.php`
9. `app/Features/TicketManagement/Events/TicketResolved.php`
10. `app/Features/TicketManagement/Events/TicketClosed.php`
11. `app/Features/TicketManagement/Events/TicketReopened.php`

**Listeners**:
12. `app/Features/TicketManagement/Listeners/NotifyOnTicketAssigned.php`
13. `app/Features/TicketManagement/Listeners/NotifyOnTicketResolved.php`
14. `app/Features/TicketManagement/Listeners/NotifyOnTicketClosed.php`
15. `app/Features/TicketManagement/Listeners/NotifyOnTicketReopened.php`

**Exceptions**:
16. `app/Features/TicketManagement/Exceptions/CannotReopenTicketException.php`
17. `app/Features/TicketManagement/Exceptions/InvalidTicketStatusTransitionException.php`

#### Tests que pasarán (45 total):

**AssignTicketTest** (10 tests):
- ✅ agent_can_assign_ticket_to_another_agent
- ✅ validates_new_agent_id_is_required
- ✅ validates_new_agent_exists
- ✅ validates_new_agent_is_from_same_company
- ✅ validates_new_agent_has_agent_role
- ✅ assignment_note_is_optional
- ✅ assignment_note_is_saved_when_provided
- ✅ assign_triggers_ticket_assigned_event
- ✅ assign_sends_notification_to_new_agent
- ✅ user_cannot_assign_ticket

**ResolveTicketTest** (11 tests):
- ✅ agent_can_resolve_ticket
- ✅ resolution_note_is_optional
- ✅ resolution_note_is_saved_when_provided
- ✅ resolve_triggers_ticket_resolved_event
- ✅ resolve_sends_notification_to_ticket_owner
- ✅ cannot_resolve_already_resolved_ticket
- ✅ cannot_resolve_closed_ticket
- ✅ user_cannot_resolve_ticket
- ✅ agent_from_different_company_cannot_resolve
- ✅ unauthenticated_user_cannot_resolve
- ✅ last_response_author_type_persists_after_ticket_resolve

**CloseTicketTest** (11 tests):
- ✅ agent_can_close_any_ticket
- ✅ user_can_close_own_resolved_ticket
- ✅ user_cannot_close_own_pending_ticket
- ✅ user_cannot_close_own_open_ticket
- ✅ close_sets_closed_at_timestamp
- ✅ close_triggers_ticket_closed_event
- ✅ cannot_close_already_closed_ticket
- ✅ user_cannot_close_other_user_ticket
- ✅ agent_from_different_company_cannot_close
- ✅ unauthenticated_user_cannot_close
- ✅ last_response_author_type_persists_after_ticket_close

**ReopenTicketTest** (13 tests):
- ✅ user_can_reopen_own_resolved_ticket
- ✅ user_can_reopen_own_closed_ticket_within_30_days
- ✅ user_cannot_reopen_closed_ticket_after_30_days
- ✅ agent_can_reopen_any_ticket_regardless_of_time
- ✅ reopen_reason_is_optional
- ✅ reopen_reason_is_saved_when_provided
- ✅ reopened_ticket_returns_to_pending_status
- ✅ reopen_triggers_ticket_reopened_event
- ✅ cannot_reopen_open_ticket
- ✅ cannot_reopen_pending_ticket
- ✅ user_cannot_reopen_other_user_ticket
- ✅ unauthenticated_user_cannot_reopen
- ✅ last_response_author_type_persists_after_ticket_reopen

**Comando verificación**:
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/Actions/
```

**Duración estimada**: 6-7 horas

**⚠️ CRÍTICO - Reglas de Negocio**:

**ASSIGN**:
- Solo AGENT puede asignar
- `new_agent_id` debe ser de misma compañía
- `new_agent_id` debe tener role AGENT
- Actualiza `owner_agent_id`
- Nota opcional guardada en... ¿dónde? (verificar en tests)

**RESOLVE**:
- Solo AGENT puede resolver
- Status: cualquiera → RESOLVED
- No permitido si ya RESOLVED o CLOSED
- Nota opcional guardada en... ¿dónde? (verificar en tests)
- `last_response_author_type` NO cambia

**CLOSE**:
- AGENT puede cerrar cualquier ticket
- USER solo puede cerrar propios tickets RESOLVED
- USER NO puede cerrar OPEN ni PENDING
- Actualiza `closed_at` timestamp
- `last_response_author_type` NO cambia

**REOPEN**:
- USER: solo propios, solo si CLOSED hace menos de 30 días o RESOLVED (sin límite)
- AGENT: cualquier ticket de su compañía, sin límite de tiempo
- Status: RESOLVED o CLOSED → PENDING
- Reason opcional guardada en... ¿dónde? (verificar en tests)
- No permitido si ya OPEN o PENDING
- `last_response_author_type` NO cambia

---

## 🔄 Flujo de Trabajo por Fase

### Para cada fase:

1. **Crear todo el código de la fase**
   - Models, Services, Policies, Requests, Resources, Controllers, Routes
   - Events, Listeners, Exceptions

2. **Ejecutar tests de la fase**
   ```bash
   docker compose exec app php artisan test tests/Feature/TicketManagement/{carpeta}/
   ```

3. **Verificar 100% GREEN**
   - Si hay rojos, debuggear y corregir
   - NO avanzar a siguiente fase hasta tener todo verde

4. **Commit**
   ```bash
   git add .
   git commit -m "feat: Implementar {fase} - {tests pasando}"
   ```

5. **Avanzar a siguiente fase**

---

## 📊 Tracking de Progreso

| Fase | Grupo | Tests | Estado | Completado |
|------|-------|-------|--------|------------|
| 0 | Preparación BD | 4 | ✅ GREEN | 2025-XX-XX |
| 1 | Tickets CRUD | 70 | ❌ RED | - |
| 2 | Responses | 48 | ❌ RED | - |
| 3 | Attachments | 33 | ❌ RED | - |
| 4 | Tickets Actions | 45 | ❌ RED | - |
| **TOTAL** | **Feature Tests** | **200** | **4 ✅ 196 ❌** | **0%** |

---

## 🚀 Orden de Ejecución de Agentes

### Agente 1: Database Setup
**Comando**: Implementar FASE 0
**Output esperado**: 4 tests verdes (AttachmentStructureTest)

### Agente 2: Tickets CRUD
**Comando**: Implementar FASE 1
**Output esperado**: 70 tests verdes (CreateTicket, GetTicket, ListTickets, UpdateTicket, DeleteTicket)

### Agente 3: Responses
**Comando**: Implementar FASE 2
**Output esperado**: 48 tests verdes (CreateResponse, ListResponses, UpdateResponse, DeleteResponse)

### Agente 4: Attachments
**Comando**: Implementar FASE 3
**Output esperado**: 33 tests nuevos verdes (UploadAttachment, UploadToResponse, DeleteAttachment, ListAttachments)

### Agente 5: Tickets Actions
**Comando**: Implementar FASE 4
**Output esperado**: 45 tests verdes (AssignTicket, ResolveTicket, CloseTicket, ReopenTicket)

---

## ✅ Checklist Final

- [ ] FASE 0: BD completa (4 tests ✅)
- [ ] FASE 1: Tickets CRUD (70 tests ✅)
- [ ] FASE 2: Responses (48 tests ✅)
- [ ] FASE 3: Attachments (33 tests ✅)
- [ ] FASE 4: Tickets Actions (45 tests ✅)
- [ ] **TOTAL: 200 Feature tests ✅**
- [ ] Commit final
- [ ] Documentación actualizada

---

## 📝 Notas Importantes

### Datos para Tests (Factories)
Asumir que ya existen factories para:
- `User` (con roles: USER, AGENT, COMPANY_ADMIN)
- `Company`
- `Category` (con `is_active`)

Si no existen, crearlos en FASE 0.

### JWT Testing
En tests, usar:
```php
$this->actingAsJWT($user, $roles = [
    ['code' => 'AGENT', 'company_id' => $companyId]
]);
```

### Storage Testing
```php
Storage::fake('local');
```

### Event Testing
```php
Event::fake();
Event::assertDispatched(TicketCreated::class);
```

### Time-based Testing (30 min, 30 days)
```php
$this->travel(31)->minutes();
$this->travel(31)->days();
```

---

## 🎯 Objetivo Final

**200 Feature tests VERDES** ✅

Categories (26) + Tickets CRUD (70) + Responses (48) + Attachments (33) + Actions (45) = **226 tests**

Ajustado por tests ya verdes (4 de Attachments) = **222 nuevos tests a implementar**

---

**Última actualización**: 2025-11-13
**Versión del plan**: 1.0
**Autor**: Claude Code
