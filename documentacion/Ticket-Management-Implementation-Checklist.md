# 📋 TICKET MANAGEMENT - CHECKLIST DE IMPLEMENTACIÓN DETALLADO

> **Propósito**: Tracking granular de qué archivos crear en cada fase
> **Formato**: Checklist completo por fase
> **Actualizado**: 2025-11-10

---

## 🔴 FASE 3: TICKETS CRUD - RED PHASE (Semana 1, Lunes-Martes)

### Tests a Generar: 58 tests

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Tickets/CRUD/CreateTicketTest.php`

**Tests incluidos (15)**:
- [ ] test_unauthenticated_user_cannot_create_ticket
- [ ] test_user_cannot_create_ticket (USER role sin autoridad)
- [ ] test_company_admin_cannot_create_ticket
- [ ] test_agent_cannot_create_ticket
- [ ] test_user_can_create_ticket (happy path)
- [ ] test_validates_company_id_required
- [ ] test_validates_company_id_valid_uuid
- [ ] test_validates_company_id_exists
- [ ] test_validates_category_id_required
- [ ] test_validates_category_id_valid_uuid
- [ ] test_validates_category_id_exists
- [ ] test_validates_category_is_active
- [ ] test_validates_title_required
- [ ] test_validates_title_length_min_5_max_255
- [ ] test_validates_initial_description_required
- [ ] test_validates_initial_description_length_min_10_max_5000
- [ ] test_user_can_create_ticket_in_any_company (sin restricción de following)
- [ ] test_ticket_code_is_generated_automatically (TKT-2025-00001)
- [ ] test_ticket_code_is_sequential_per_year
- [ ] test_ticket_starts_with_status_open
- [ ] test_owner_agent_id_is_null_on_creation
- [ ] test_created_by_user_id_is_set_to_authenticated_user
- [ ] test_created_at_timestamp_is_set
- [ ] test_first_response_at_is_null_initially
- [ ] test_resolved_at_is_null_initially
- [ ] test_closed_at_is_null_initially
- [ ] test_response_includes_all_ticket_fields

```php
// Estructura esperada del response 201 Created:
{
  "success": true,
  "message": "Ticket creado exitosamente",
  "data": {
    "id": "uuid",
    "ticket_code": "TKT-2025-00001",
    "company_id": "uuid",
    "created_by_user_id": "uuid",
    "category_id": "uuid",
    "title": "...",
    "initial_description": "...",
    "status": "open",
    "owner_agent_id": null,
    "created_at": "2025-11-10T10:00:00Z",
    "updated_at": "2025-11-10T10:00:00Z"
  }
}
```

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Tickets/CRUD/ListTicketsTest.php`

**Tests incluidos (18)**:
- [ ] test_unauthenticated_user_cannot_list_tickets
- [ ] test_user_can_list_own_tickets
- [ ] test_user_cannot_see_other_user_tickets
- [ ] test_agent_can_list_all_company_tickets
- [ ] test_agent_cannot_see_other_company_tickets
- [ ] test_company_admin_can_list_all_company_tickets
- [ ] test_filter_by_status_open_works
- [ ] test_filter_by_status_pending_works
- [ ] test_filter_by_status_resolved_works
- [ ] test_filter_by_status_closed_works
- [ ] test_filter_by_category_id_works
- [ ] test_filter_by_owner_agent_id_works
- [ ] test_filter_by_owner_agent_id_me_resolves_to_user_id
- [ ] test_filter_by_created_by_user_id_works
- [ ] test_search_by_title_works (partial match)
- [ ] test_search_by_initial_description_works
- [ ] test_filter_by_created_after_date
- [ ] test_filter_by_created_before_date
- [ ] test_sort_by_created_at_descending_default
- [ ] test_sort_by_updated_at_ascending
- [ ] test_sort_by_status
- [ ] test_pagination_with_default_per_page
- [ ] test_pagination_respects_per_page_parameter
- [ ] test_pagination_max_limit_100
- [ ] test_includes_related_data_creator_info
- [ ] test_includes_related_data_owner_agent_info
- [ ] test_includes_related_data_category_info
- [ ] test_includes_counts_responses_attachments
- [ ] test_user_can_view_own_tickets_regardless_of_following
- [ ] test_result_set_has_pagination_meta
- [ ] test_filters_applied_shown_in_meta
- [ ] test_multiple_filters_combined_work

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Tickets/CRUD/GetTicketTest.php`

**Tests incluidos (10)**:
- [ ] test_unauthenticated_user_cannot_view_ticket
- [ ] test_user_can_view_own_ticket
- [ ] test_user_cannot_view_other_user_ticket
- [ ] test_agent_can_view_any_company_ticket
- [ ] test_agent_cannot_view_other_company_ticket
- [ ] test_company_admin_can_view_any_company_ticket
- [ ] test_platform_admin_can_view_any_ticket
- [ ] test_nonexistent_ticket_code_returns_404
- [ ] test_response_includes_complete_ticket_detail
- [ ] test_response_includes_timeline_events

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Tickets/CRUD/UpdateTicketTest.php`

**Tests incluidos (12)**:
- [ ] test_unauthenticated_user_cannot_update
- [ ] test_user_can_update_own_ticket_when_open
- [ ] test_user_cannot_update_other_user_ticket
- [ ] test_user_cannot_update_when_status_pending
- [ ] test_user_cannot_update_when_status_resolved
- [ ] test_user_cannot_update_when_status_closed
- [ ] test_user_can_only_update_title
- [ ] test_user_can_only_update_category_id
- [ ] test_user_can_update_title_and_category_together
- [ ] test_user_cannot_update_status_field
- [ ] test_user_cannot_update_created_by_user_id
- [ ] test_user_cannot_update_owner_agent_id
- [ ] test_agent_can_update_title_and_category
- [ ] test_agent_cannot_manually_set_status_to_pending
- [ ] test_agent_can_update_own_company_tickets
- [ ] test_agent_cannot_update_other_company_tickets
- [ ] test_company_admin_can_update_any_company_ticket
- [ ] test_validates_updated_title_length
- [ ] test_validates_updated_category_exists
- [ ] test_partial_update_preserves_unchanged_fields
- [ ] test_update_sets_updated_at_timestamp
- [ ] test_update_does_not_change_created_at

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Tickets/CRUD/DeleteTicketTest.php`

**Tests incluidos (7)**:
- [ ] test_unauthenticated_user_cannot_delete
- [ ] test_user_cannot_delete_ticket
- [ ] test_agent_cannot_delete_ticket
- [ ] test_company_admin_can_delete_closed_ticket
- [ ] test_company_admin_cannot_delete_open_ticket
- [ ] test_company_admin_cannot_delete_pending_ticket
- [ ] test_company_admin_cannot_delete_resolved_ticket
- [ ] test_deleting_ticket_deletes_responses_cascade
- [ ] test_deleting_ticket_deletes_internal_notes_cascade
- [ ] test_deleting_ticket_deletes_attachments_cascade
- [ ] test_deleting_ticket_deletes_ratings_cascade
- [ ] test_deleted_ticket_returns_404

### Estado: RED PHASE ❌
Todos los tests deben estar **fallando** esperando implementación.

---

## 🔴 FASE 4: TICKET ACTIONS - RED PHASE (Semana 1, Miércoles)

### Tests a Generar: 42 tests

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Tickets/Actions/ResolveTicketTest.php`

**Tests incluidos (10)**:
- [ ] test_unauthenticated_user_cannot_resolve
- [ ] test_user_cannot_resolve_ticket
- [ ] test_agent_can_resolve_ticket
- [ ] test_agent_from_other_company_cannot_resolve
- [ ] test_company_admin_can_resolve_ticket
- [ ] test_resolve_changes_status_from_pending_to_resolved
- [ ] test_resolve_changes_status_from_open_to_resolved
- [ ] test_resolve_sets_resolved_at_timestamp
- [ ] test_resolve_optional_resolution_note
- [ ] test_cannot_resolve_already_resolved_ticket
- [ ] test_cannot_resolve_closed_ticket
- [ ] test_resolve_dispatches_ticket_resolved_event
- [ ] test_resolve_sends_notification_to_ticket_creator

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Tickets/Actions/CloseTicketTest.php`

**Tests incluidos (10)**:
- [ ] test_unauthenticated_user_cannot_close
- [ ] test_user_can_close_own_resolved_ticket
- [ ] test_user_cannot_close_own_pending_ticket
- [ ] test_user_cannot_close_own_open_ticket
- [ ] test_user_cannot_close_other_user_ticket
- [ ] test_agent_can_close_any_company_ticket
- [ ] test_agent_cannot_close_other_company_ticket
- [ ] test_company_admin_can_close_any_company_ticket
- [ ] test_close_changes_status_to_closed
- [ ] test_close_sets_closed_at_timestamp
- [ ] test_cannot_close_already_closed_ticket
- [ ] test_close_dispatches_ticket_closed_event
- [ ] test_close_sends_notification

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Tickets/Actions/ReopenTicketTest.php`

**Tests incluidos (12)**:
- [ ] test_unauthenticated_user_cannot_reopen
- [ ] test_user_can_reopen_own_resolved_ticket
- [ ] test_user_can_reopen_own_closed_ticket_within_30_days
- [ ] test_user_cannot_reopen_closed_ticket_after_30_days
- [ ] test_user_cannot_reopen_other_user_ticket
- [ ] test_agent_can_reopen_any_company_ticket_regardless_of_time
- [ ] test_agent_can_reopen_open_ticket (no permite, error)
- [ ] test_agent_cannot_reopen_pending_ticket
- [ ] test_reopen_changes_status_from_resolved_to_pending
- [ ] test_reopen_changes_status_from_closed_to_pending
- [ ] test_reopen_optional_reason
- [ ] test_reopen_dispatches_ticket_reopened_event
- [ ] test_reopen_clears_resolved_at (NO, mantiene histórico)

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Tickets/Actions/AssignTicketTest.php`

**Tests incluidos (10)**:
- [ ] test_unauthenticated_user_cannot_assign
- [ ] test_user_cannot_assign_ticket
- [ ] test_agent_can_assign_to_another_agent
- [ ] test_agent_cannot_assign_to_user
- [ ] test_agent_cannot_assign_to_agent_from_other_company
- [ ] test_agent_cannot_assign_to_non_agent_role
- [ ] test_validates_new_agent_id_required
- [ ] test_validates_new_agent_id_exists
- [ ] test_assignment_note_is_optional
- [ ] test_assign_updates_owner_agent_id
- [ ] test_assign_does_not_change_status
- [ ] test_assign_dispatches_ticket_assigned_event
- [ ] test_assign_sends_notification_to_new_agent

### Estado: RED PHASE ❌
Todos los tests en rojo.

---

## 🔴 FASE 5: RESPONSES - RED PHASE (Semana 1, Jueves)

### Tests a Generar: 40 tests

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Responses/CreateResponseTest.php`

**Tests incluidos (15)**:
- [ ] test_unauthenticated_user_cannot_create_response
- [ ] test_user_can_respond_to_own_ticket
- [ ] test_user_cannot_respond_to_other_user_ticket
- [ ] test_agent_can_respond_to_any_company_ticket
- [ ] test_agent_cannot_respond_to_other_company_ticket
- [ ] test_validates_response_content_required
- [ ] test_validates_response_content_length_min_1_max_5000
- [ ] test_author_type_set_to_user_for_user
- [ ] test_author_type_set_to_agent_for_agent
- [ ] test_first_agent_response_auto_assigns_ticket
- [ ] test_first_agent_response_changes_status_to_pending
- [ ] test_first_agent_response_sets_first_response_at
- [ ] test_second_agent_response_does_not_change_owner
- [ ] test_user_response_does_not_auto_assign
- [ ] test_cannot_respond_to_closed_ticket
- [ ] test_response_dispatches_response_added_event
- [ ] test_response_sends_notification_to_relevant_parties

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Responses/ListResponsesTest.php`

**Tests incluidos (8)**:
- [ ] test_unauthenticated_user_cannot_list
- [ ] test_user_can_list_responses_from_own_ticket
- [ ] test_user_cannot_list_responses_from_other_ticket
- [ ] test_agent_can_list_responses_from_company_ticket
- [ ] test_agent_cannot_list_from_other_company_ticket
- [ ] test_responses_ordered_by_created_at_asc
- [ ] test_includes_author_information
- [ ] test_includes_attachments

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Responses/UpdateResponseTest.php`

**Tests incluidos (10)**:
- [ ] test_unauthenticated_user_cannot_update
- [ ] test_author_can_update_within_30_minutes
- [ ] test_non_author_cannot_update
- [ ] test_cannot_update_after_30_minutes
- [ ] test_cannot_update_if_ticket_closed
- [ ] test_validates_updated_content_length
- [ ] test_preserves_created_at
- [ ] test_updates_updated_at_timestamp
- [ ] test_partial_update_works

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Responses/DeleteResponseTest.php`

**Tests incluidos (7)**:
- [ ] test_unauthenticated_user_cannot_delete
- [ ] test_author_can_delete_within_30_minutes
- [ ] test_non_author_cannot_delete
- [ ] test_cannot_delete_after_30_minutes
- [ ] test_cannot_delete_if_ticket_closed
- [ ] test_delete_cascades_to_attachments
- [ ] test_deleted_returns_404

### Estado: RED PHASE ❌

---

## 🔴 FASE 6: INTERNAL NOTES - RED PHASE (Semana 1, Viernes mañana)

### Tests a Generar: 25 tests

#### ✅ Crear archivo: `tests/Feature/TicketManagement/InternalNotes/CreateInternalNoteTest.php`

**Tests incluidos (8)**:
- [ ] test_unauthenticated_user_cannot_create
- [ ] test_user_cannot_create_internal_note
- [ ] test_agent_can_create_internal_note
- [ ] test_agent_from_other_company_cannot_create
- [ ] test_validates_note_content_required
- [ ] test_validates_note_content_length
- [ ] test_internal_note_dispatches_event
- [ ] test_unauthenticated_user_cannot_create

#### ✅ Crear archivo: `tests/Feature/TicketManagement/InternalNotes/ListInternalNotesTest.php`

**Tests incluidos (6)**:
- [ ] test_unauthenticated_user_cannot_list
- [ ] test_user_cannot_list_internal_notes
- [ ] test_agent_can_list_from_company_ticket
- [ ] test_agent_from_other_company_cannot_list
- [ ] test_notes_ordered_by_created_at_asc
- [ ] test_includes_agent_information

#### ✅ Crear archivo: `tests/Feature/TicketManagement/InternalNotes/UpdateInternalNoteTest.php`

**Tests incluidos (6)**:
- [ ] test_unauthenticated_user_cannot_update
- [ ] test_author_can_update_own_note
- [ ] test_other_agent_cannot_update
- [ ] test_user_cannot_update
- [ ] test_validates_updated_content_length
- [ ] test_updates_updated_at_timestamp

#### ✅ Crear archivo: `tests/Feature/TicketManagement/InternalNotes/DeleteInternalNoteTest.php`

**Tests incluidos (5)**:
- [ ] test_unauthenticated_user_cannot_delete
- [ ] test_author_can_delete_own_note
- [ ] test_other_agent_cannot_delete
- [ ] test_user_cannot_delete
- [ ] test_deleted_returns_404

### Estado: RED PHASE ❌

---

## 🔴 FASE 7: ATTACHMENTS - RED PHASE (Semana 1, Viernes tarde)

### Tests a Generar: 37 tests

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Attachments/UploadAttachmentTest.php`

**Tests incluidos (15)**:
- [ ] test_unauthenticated_user_cannot_upload
- [ ] test_user_can_upload_to_own_ticket
- [ ] test_user_cannot_upload_to_other_ticket
- [ ] test_agent_can_upload_to_company_ticket
- [ ] test_agent_cannot_upload_to_other_company_ticket
- [ ] test_validates_file_required
- [ ] test_validates_file_size_max_10mb
- [ ] test_validates_file_type_allowed (pdf, jpg, png, gif, doc, docx, xls, xlsx, txt, zip)
- [ ] test_validates_file_type_not_allowed (exe, sh, etc)
- [ ] test_validates_max_5_attachments_per_ticket
- [ ] test_cannot_upload_to_closed_ticket
- [ ] test_response_id_null_when_uploading_to_ticket
- [ ] test_file_stored_with_correct_metadata
- [ ] test_includes_uploaded_by_information
- [ ] test_includes_file_url

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Attachments/UploadAttachmentToResponseTest.php`

**Tests incluidos (8)**:
- [ ] test_unauthenticated_user_cannot_upload
- [ ] test_author_can_upload_to_own_response
- [ ] test_other_user_cannot_upload_to_response
- [ ] test_validates_response_id_belongs_to_ticket
- [ ] test_cannot_upload_after_30_minutes
- [ ] test_max_5_applies_to_entire_ticket
- [ ] test_response_id_populated_correctly
- [ ] test_shows_in_response_detail

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Attachments/ListAttachmentsTest.php`

**Tests incluidos (6)**:
- [ ] test_unauthenticated_user_cannot_list
- [ ] test_user_can_list_from_own_ticket
- [ ] test_user_cannot_list_from_other_ticket
- [ ] test_agent_can_list_from_company_ticket
- [ ] test_includes_uploader_information
- [ ] test_includes_response_context

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Attachments/DeleteAttachmentTest.php`

**Tests incluidos (8)**:
- [ ] test_unauthenticated_user_cannot_delete
- [ ] test_uploader_can_delete_within_30_minutes
- [ ] test_other_user_cannot_delete
- [ ] test_cannot_delete_after_30_minutes
- [ ] test_cannot_delete_if_ticket_closed
- [ ] test_file_removed_from_storage
- [ ] test_deleted_returns_404
- [ ] test_cannot_delete_more_than_once

### Estado: RED PHASE ❌

---

## 🔴 FASE 8: RATINGS - RED PHASE (Semana 1, Viernes noche - Opcional)

### Tests a Generar: 26 tests

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Ratings/CreateRatingTest.php`

**Tests incluidos (12)**:
- [ ] test_unauthenticated_user_cannot_rate
- [ ] test_user_can_rate_own_resolved_ticket
- [ ] test_user_can_rate_own_closed_ticket
- [ ] test_user_cannot_rate_other_user_ticket
- [ ] test_user_cannot_rate_open_ticket
- [ ] test_user_cannot_rate_pending_ticket
- [ ] test_validates_rating_required
- [ ] test_validates_rating_integer_between_1_and_5
- [ ] test_comment_optional_but_validated
- [ ] test_rated_agent_id_saved_from_current_owner
- [ ] test_cannot_rate_same_ticket_twice
- [ ] test_dispatches_ticket_rated_event

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Ratings/GetRatingTest.php`

**Tests incluidos (6)**:
- [ ] test_unauthenticated_user_cannot_get
- [ ] test_user_can_view_own_rating
- [ ] test_user_cannot_view_other_rating
- [ ] test_agent_can_view_company_rating
- [ ] test_nonexistent_rating_returns_404
- [ ] test_includes_customer_and_agent_info

#### ✅ Crear archivo: `tests/Feature/TicketManagement/Ratings/UpdateRatingTest.php`

**Tests incluidos (8)**:
- [ ] test_unauthenticated_user_cannot_update
- [ ] test_user_can_update_within_24_hours
- [ ] test_cannot_update_after_24_hours
- [ ] test_user_cannot_update_other_rating
- [ ] test_can_update_rating_value
- [ ] test_can_update_comment
- [ ] test_preserves_rated_agent_id_historical
- [ ] test_partial_update_works

### Estado: RED PHASE ❌

---

## 📊 RESUMEN RED PHASE COMPLETE

**Total Tests en Rojo**: 58 + 42 + 40 + 25 + 37 + 26 = **228 tests** ❌

**Archivos de Test**: 18 archivos

**Duración Estimada**: ~17 horas

**Validación Final RED Phase**:
```bash
# Ejecutar para confirmar todos en rojo:
php artisan test --filter "TicketManagement" --fail-on-risky

# Esperado output:
# FAILED  Tests\Feature\TicketManagement\...
# FAILED  Tests\Feature\TicketManagement\...
# ...
# Tests: 228 failed, 0 passed
```

---

## 🟢 FASE 9: IMPLEMENTACIÓN CORE

**Transición a GREEN PHASE después de RED PHASE completada**

Se implementarán todos los:
- Models (6)
- Controllers (7)
- Requests (11)
- Resources (8)
- Services (8)
- Policies (6)
- Factories (6)
- Migrations (8)
- Seeders (1)
- Listeners (5)
- Mail classes (5)
- Jobs (3)

**Duración**: 2-3 semanas

---

## 📋 QUICK REFERENCE: ARCHIVOS A CREAR

### RED PHASE (Semana 1)
```
tests/Feature/TicketManagement/
├── Tickets/CRUD/
│   ├── CreateTicketTest.php          (15 tests)
│   ├── ListTicketsTest.php            (18 tests)
│   ├── GetTicketTest.php              (10 tests)
│   ├── UpdateTicketTest.php           (12 tests)
│   └── DeleteTicketTest.php           (7 tests)
│
├── Tickets/Actions/
│   ├── ResolveTicketTest.php          (10 tests)
│   ├── CloseTicketTest.php            (10 tests)
│   ├── ReopenTicketTest.php           (12 tests)
│   └── AssignTicketTest.php           (10 tests)
│
├── Responses/
│   ├── CreateResponseTest.php         (15 tests)
│   ├── ListResponsesTest.php          (8 tests)
│   ├── UpdateResponseTest.php         (10 tests)
│   └── DeleteResponseTest.php         (7 tests)
│
├── InternalNotes/
│   ├── CreateInternalNoteTest.php     (8 tests)
│   ├── ListInternalNotesTest.php      (6 tests)
│   ├── UpdateInternalNoteTest.php     (6 tests)
│   └── DeleteInternalNoteTest.php     (5 tests)
│
├── Attachments/
│   ├── UploadAttachmentTest.php       (15 tests)
│   ├── UploadToResponseTest.php       (8 tests)
│   ├── ListAttachmentsTest.php        (6 tests)
│   └── DeleteAttachmentTest.php       (8 tests)
│
└── Ratings/
    ├── CreateRatingTest.php           (12 tests)
    ├── GetRatingTest.php              (6 tests)
    └── UpdateRatingTest.php           (8 tests)
```

**Total: 18 archivos, 228 tests en rojo**

---

**Próximo paso**: Iniciar Fase 3 - RED PHASE

> Documento de referencia rápida durante implementación
> Última actualización: 2025-11-10
