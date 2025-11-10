# 🎫 TICKET MANAGEMENT - PLAN DE IMPLEMENTACIÓN POR FASES

> **Metodología**: TDD (Test-Driven Development)
> **Estado**: Fases 0-2 Completadas ✅
> **Próxima**: Fase 3 (Tickets CRUD)
> **Total Estimado**: 11 fases
> **Duración Estimada**: 4-6 semanas

---

## 📊 RESUMEN EJECUTIVO

| Fase | Nombre | Tests | Archivos | Duración | Estado |
|------|--------|-------|----------|----------|--------|
| **0** | Setup Base (Enums, Exceptions) | 0 | 12 | ✅ 2h | DONE |
| **1** | Tests TDD Categories | 27 | 4 | ✅ 3h | DONE |
| **2** | Implementación Categories | 26 | 18 | ✅ 1 semana | DONE |
| **3** | Tickets CRUD - Red | 58 | 5 | ⏳ ~4h | PENDIENTE |
| **4** | Tickets Actions - Red | 42 | 4 | ⏳ ~3h | PENDIENTE |
| **5** | Responses - Red | 40 | 4 | ⏳ ~3h | PENDIENTE |
| **6** | Internal Notes - Red | 25 | 4 | ⏳ ~2h | PENDIENTE |
| **7** | Attachments - Red | 37 | 4 | ⏳ ~3h | PENDIENTE |
| **8** | Ratings - Red | 26 | 3 | ⏳ ~2h | PENDIENTE |
| **9** | Implementación Core (3-8) | 293 | 30 | ⏳ 2-3 semanas | PENDIENTE |
| **10** | Permissions & Security | 26 | 3 | ⏳ 1 semana | PENDIENTE |
| **11** | Unit & Integration | 45 | 7 | ⏳ 1 semana | PENDIENTE |

**Totales**: 786/786 tests, 42 archivos de test, ~4-5 semanas

---

## ✅ FASE 0: SETUP BASE (COMPLETADA)

### ¿Qué se hizo?
- ✅ Enums: `TicketStatus`, `AuthorType`
- ✅ Excepciones: 8 clases custom
- ✅ Eventos: 8 clases de eventos
- ✅ Contratos de base de datos

### Archivos Creados
```
app/Features/TicketManagement/
├── Enums/
│   ├── TicketStatus.php
│   └── AuthorType.php
├── Exceptions/
│   ├── TicketNotFoundException.php
│   ├── TicketNotEditableException.php
│   ├── ResponseNotEditableException.php
│   ├── NotTicketOwnerException.php
│   ├── CategoryInUseException.php
│   ├── CannotReopenTicketException.php
│   ├── RatingAlreadyExistsException.php
│   └── FileUploadException.php
└── Events/
    ├── TicketCreated.php
    ├── TicketAssigned.php
    ├── TicketResolved.php
    ├── TicketClosed.php
    ├── TicketReopened.php
    ├── ResponseAdded.php
    ├── InternalNoteAdded.php
    └── TicketRated.php
```

---

## ✅ FASE 1: TESTS TDD CATEGORIES (COMPLETADA)

### Tests Generados: 26 ✅

**CreateCategoryTest.php**: 9 tests
- ✅ company_admin_can_create_category
- ✅ validates_name_required
- ✅ validates_name_length
- ✅ validates_name_unique_per_company
- ✅ name_uniqueness_is_per_company
- ✅ description_is_optional
- ✅ company_id_inferred_from_jwt
- ✅ user_cannot_create_category
- ✅ agent_cannot_create_category

**ListCategoriesTest.php**: 6 tests
**UpdateCategoryTest.php**: 6 tests
**DeleteCategoryTest.php**: 6 tests

---

## ✅ FASE 2: IMPLEMENTACIÓN CATEGORIES (COMPLETADA)

### Implementado
- ✅ Model: `Category`
- ✅ Controller: `CategoryController`
- ✅ Requests: `StoreCategoryRequest`, `UpdateCategoryRequest`
- ✅ Resources: `CategoryResource`
- ✅ Service: `CategoryService`
- ✅ Policy: `CategoryPolicy`
- ✅ Factory: `CategoryFactory`
- ✅ Migrations: Tables + Indexes
- ✅ Seeders: `DefaultCategoriesSeeder`

### Estado Actual
**786 tests pasando** en total del proyecto

---

## 🔴 FASE 3: TICKETS CRUD - RED PHASE

### Objetivo
Generar todos los tests en rojo para CRUD de tickets sin implementar lógica.

### Tests a Generar: 58 tests

#### CreateTicketTest.php (15 tests)
```php
- test_user_can_create_ticket
- test_validates_required_fields (4 sub-tests)
- test_validates_title_length
- test_validates_description_length
- test_validates_company_exists
- test_validates_category_exists_and_is_active
- test_user_can_create_ticket_in_any_company
- test_ticket_code_is_generated_automatically
- test_ticket_code_is_sequential_per_year
- test_ticket_starts_with_status_open
- test_created_by_user_id_is_set_correctly
- test_ticket_creation_triggers_event
- test_agent_cannot_create_ticket
- test_company_admin_cannot_create_ticket
- test_unauthenticated_user_cannot_create_ticket
```

#### ListTicketsTest.php (18 tests)
```php
- test_user_can_list_own_tickets
- test_user_cannot_see_tickets_from_other_users
- test_agent_can_list_all_company_tickets
- test_agent_cannot_see_other_companies_tickets
- test_filter_by_status_works
- test_filter_by_category_works
- test_filter_by_owner_agent_id_works
- test_filter_owner_agent_id_me_resolves_correctly
- test_filter_by_created_by_user_id
- test_search_in_title_works
- test_search_in_description_works
- test_filter_by_date_range
- test_sort_by_created_at_desc_default
- test_sort_by_updated_at_asc
- test_pagination_works
- test_includes_related_data_in_list
- test_user_can_view_own_tickets_regardless_of_following
- test_unauthenticated_user_cannot_list_tickets
```

#### GetTicketTest.php (10 tests)
```php
- test_user_can_view_own_ticket
- test_user_cannot_view_other_user_ticket
- test_agent_can_view_any_ticket_from_own_company
- test_agent_cannot_view_ticket_from_other_company
- test_company_admin_can_view_any_ticket_from_own_company
- test_ticket_detail_includes_complete_information
- test_ticket_detail_includes_responses_count
- test_ticket_detail_includes_timeline
- test_nonexistent_ticket_returns_404
- test_unauthenticated_user_cannot_view_ticket
```

#### UpdateTicketTest.php (12 tests)
```php
- test_user_can_update_own_ticket_when_status_open
- test_user_cannot_update_ticket_when_status_pending
- test_user_cannot_update_ticket_when_status_resolved
- test_user_can_only_update_title_and_category
- test_agent_can_update_ticket_title_and_category
- test_agent_cannot_manually_change_status_to_pending
- test_validates_updated_title_length
- test_validates_updated_category_exists
- test_partial_update_preserves_unchanged_fields
- test_user_cannot_update_other_user_ticket
- test_company_admin_from_different_company_cannot_update
- test_unauthenticated_user_cannot_update
```

#### DeleteTicketTest.php (7 tests)
```php
- test_company_admin_can_delete_closed_ticket
- test_cannot_delete_open_ticket
- test_cannot_delete_pending_ticket
- test_cannot_delete_resolved_ticket
- test_deleting_ticket_cascades_to_related_data
- test_user_cannot_delete_ticket
- test_agent_cannot_delete_ticket
```

### Archivos a Crear
```
tests/Feature/TicketManagement/Tickets/CRUD/
├── CreateTicketTest.php
├── ListTicketsTest.php
├── GetTicketTest.php
├── UpdateTicketTest.php
└── DeleteTicketTest.php
```

### Dependencias
- ✅ Models: Category, Company, User (ya existen)
- ✅ Factories: CategoryFactory, CompanyFactory, UserFactory (ya existen)
- ✅ Base Test Class
- ⏳ Models: Ticket
- ⏳ Controllers: TicketController
- ⏳ Requests: StoreTicketRequest, UpdateTicketRequest
- ⏳ Resources: TicketResource, TicketListResource, TicketDetailResource
- ⏳ Services: TicketService, TicketCodeGenerator
- ⏳ Factory: TicketFactory
- ⏳ Migrations

### Duración Estimada
**~4 horas** (1 test cada 4 minutos)

### Criterios de Éxito
- ✅ 58 tests en rojo (failing)
- ✅ Todos los tests están bien estructurados
- ✅ Los tests son independientes entre sí
- ✅ Cada test valida UN comportamiento

---

## 🔴 FASE 4: TICKET ACTIONS - RED PHASE

### Objetivo
Tests para resolve, close, reopen, assign sin implementación.

### Tests a Generar: 42 tests

#### ResolveTicketTest.php (10 tests)
#### CloseTicketTest.php (10 tests)
#### ReopenTicketTest.php (12 tests)
#### AssignTicketTest.php (10 tests)

### Duración Estimada
**~3 horas**

### Archivos a Crear
```
tests/Feature/TicketManagement/Tickets/Actions/
├── ResolveTicketTest.php
├── CloseTicketTest.php
├── ReopenTicketTest.php
└── AssignTicketTest.php
```

---

## 🔴 FASE 5: RESPONSES - RED PHASE

### Tests a Generar: 40 tests

#### CreateResponseTest.php (15 tests)
#### ListResponsesTest.php (8 tests)
#### UpdateResponseTest.php (10 tests)
#### DeleteResponseTest.php (7 tests)

### Características Clave a Testear
- Auto-assignment del primer agente
- Conversación pública (visible a usuario y agente)
- Cambios automáticos de status
- Trigger PostgreSQL validation

### Duración Estimada
**~3 horas**

---

## 🔴 FASE 6: INTERNAL NOTES - RED PHASE

### Tests a Generar: 25 tests

#### CreateInternalNoteTest.php (8 tests)
#### ListInternalNotesTest.php (6 tests)
#### UpdateInternalNoteTest.php (6 tests)
#### DeleteInternalNoteTest.php (5 tests)

### Características Clave a Testear
- Invisible a usuarios (solo agentes)
- Colaboración entre agentes
- Auditoría completa

### Duración Estimada
**~2 horas**

---

## 🔴 FASE 7: ATTACHMENTS - RED PHASE

### Tests a Generar: 37 tests

#### UploadAttachmentTest.php (15 tests)
#### UploadAttachmentToResponseTest.php (8 tests)
#### ListAttachmentsTest.php (6 tests)
#### DeleteAttachmentTest.php (8 tests)

### Características Clave a Testear
- Validación de tipos de archivo
- Límite de 10 MB
- Máximo 5 por ticket
- Storage en S3/Disk
- Eliminación en cascada

### Duración Estimada
**~3 horas**

---

## 🟡 FASE 8: RATINGS - RED PHASE

### Tests a Generar: 26 tests

#### CreateRatingTest.php (12 tests)
#### GetRatingTest.php (6 tests)
#### UpdateRatingTest.php (8 tests)

### Características Clave a Testear
- Solo owner puede calificar
- Solo tickets resolved/closed
- Una calificación por ticket
- Snapshot histórico de agente
- Límite 24h para actualizar

### Duración Estimada
**~2 horas**

---

## 🟢 FASE 9: IMPLEMENTACIÓN CORE (Fases 3-8)

### Objetivo
Implementar toda la lógica para que 293 tests pasen.

### Sub-fases Recomendadas

#### Fase 9A: Tickets CRUD (1 semana)
```
✅ Models:
  - Ticket
  - TicketResponse
  - TicketInternalNote
  - TicketAttachment
  - TicketRating

✅ Controllers:
  - TicketController (CRUD)
  - TicketResponseController
  - TicketInternalNoteController
  - TicketAttachmentController
  - TicketRatingController
  - TicketActionController (resolve, close, reopen, assign)

✅ Requests (Form Validation):
  - StoreTicketRequest
  - UpdateTicketRequest
  - StoreResponseRequest
  - UpdateResponseRequest
  - etc.

✅ Resources (Response Formatting):
  - TicketResource
  - TicketListResource
  - TicketDetailResource
  - ResponseResource
  - AttachmentResource
  - RatingResource

✅ Services:
  - TicketService
  - TicketCodeGenerator
  - ResponseService
  - AttachmentService
  - RatingService
  - TicketVisibilityService

✅ Policies:
  - TicketPolicy
  - ResponsePolicy
  - InternalNotePolicy
  - AttachmentPolicy
  - RatingPolicy

✅ Factories:
  - TicketFactory
  - TicketResponseFactory
  - TicketInternalNoteFactory
  - TicketAttachmentFactory
  - TicketRatingFactory

✅ Migrations:
  - Create tables (7 tables)
  - Create indexes
  - Create trigger
```

#### Fase 9B: Ticket Actions (4-5 días)
- Resolve logic
- Close logic
- Reopen logic (con validación 30 días)
- Assign logic

#### Fase 9C: Responses & Auto-assignment (5-6 días)
- Response CRUD
- Auto-assignment trigger
- First response timestamp
- Event dispatching

#### Fase 9D: Internal Notes (3-4 días)
- Note CRUD
- Visibility logic (solo agentes)

#### Fase 9E: Attachments (4-5 días)
- File upload/storage
- Validation (tipo, tamaño, cantidad)
- Cascading delete

#### Fase 9F: Ratings (3-4 días)
- Rating CRUD
- Historical snapshot
- Time restrictions (24h)

### Criterios de Éxito
- 293 tests pasando
- 100% cobertura de líneas de código
- Toda la lógica de BD implementada
- Todos los servicios funcionando

### Duración Estimada
**2-3 semanas** (trabajando 4-5 horas diarias)

---

## 🟣 FASE 10: PERMISSIONS & SECURITY

### Tests a Generar: 26 tests

#### TicketOwnershipTest.php (10 tests)
```php
- test_user_can_only_access_own_tickets
- test_user_can_respond_only_to_own_tickets
- test_user_can_upload_attachments_only_to_own_tickets
- test_user_can_rate_only_own_tickets
- test_agent_can_access_all_tickets_from_own_company
- test_agent_cannot_access_tickets_from_other_companies
- test_company_admin_has_full_access_to_own_company_tickets
- test_company_admin_cannot_access_other_company_tickets
- test_platform_admin_has_read_only_access_to_all_tickets
- test_suspended_user_cannot_access_tickets
```

#### CompanyFollowingTest.php (6 tests)
```php
- test_user_can_create_ticket_in_any_company (no restrictions)
- test_following_affects_company_listing_order_not_access
- test_following_affects_notifications_not_access
- test_agent_does_not_need_to_follow_own_company
- test_company_admin_does_not_need_to_follow_own_company
- test_following_provides_information_priority_only
```

#### RoleBasedAccessTest.php (10 tests)
```php
- test_user_can_only_create_tickets
- test_agent_has_full_ticket_management_permissions
- test_company_admin_can_manage_categories
- test_company_admin_can_delete_closed_tickets
- test_agent_cannot_create_tickets
- test_user_cannot_see_internal_notes
- test_agent_cannot_rate_tickets
- test_platform_admin_has_read_only_access
- test_role_validation_happens_before_business_logic
- test_expired_token_returns_401
```

### Archivos a Crear
```
tests/Feature/TicketManagement/Permissions/
├── TicketOwnershipTest.php
├── CompanyFollowingTest.php
└── RoleBasedAccessTest.php
```

### Archivos a Modificar
```
app/Features/TicketManagement/
├── Http/Middleware/
│   ├── EnsureTicketOwner.php
│   └── EnsureAgentRole.php
├── Policies/ (actualizar existentes)
└── Services/
    └── TicketVisibilityService.php
```

### Duración Estimada
**1 semana** (Algunos servicios ya existirán)

---

## 🔵 FASE 11: UNIT & INTEGRATION TESTS

### Tests a Generar: 45 tests

#### Unit Tests (30 tests)

**TicketServiceTest.php** (10 tests)
- Code generation
- Status transitions
- Agent assignment
- Reopen validations
- Delete validations

**ResponseServiceTest.php** (6 tests)
- Author type determination
- Edit time limit validation
- Ticket closure validation
- Auto-assignment trigger

**AttachmentServiceTest.php** (8 tests)
- File size validation
- File type validation
- Max attachments check
- Storage & URL generation

**RatingServiceTest.php** (6 tests)
- Status validation
- Owner validation
- Time limit validation
- Historical snapshot

#### Model Tests (10 tests)

**TicketTest.php** (6 tests)
**TicketResponseTest.php** (4 tests)

#### Validation Rules (5 tests)

**ValidFileTypeTest.php** (5 tests)
**CanReopenTicketTest.php** (6 tests)

#### Jobs (3 tests)

**AutoCloseResolvedTicketsJobTest.php** (3 tests)

#### Integration Tests (15 tests)

**CompleteTicketFlowTest.php** (6 tests)
- User crea → Agent responde → Resolve → Califica

**AutoAssignmentFlowTest.php** (5 tests)
- Trigger validation
- Status changes
- First response timestamp

**PermissionsIntegrationTest.php** (4 tests)
- Cross-company isolation
- Role changes affecting permissions

### Duración Estimada
**1 semana** (muchos servicios/modelos ya implementados)

---

## 📋 RESUMEN DE ORDEN RECOMENDADO

```
SEMANA 1:
├── Fase 3: Tickets CRUD - RED (4h)
├── Fase 4: Ticket Actions - RED (3h)
├── Fase 5: Responses - RED (3h)
├── Fase 6: Internal Notes - RED (2h)
├── Fase 7: Attachments - RED (3h)
└── Fase 8: Ratings - RED (2h)
   Total RED Phase: ~17 horas

SEMANA 2-4:
├── Fase 9A: Tickets CRUD Implementation (1 semana)
├── Fase 9B: Ticket Actions Implementation (4-5 días)
├── Fase 9C: Responses & Auto-assignment (5-6 días)
├── Fase 9D: Internal Notes Implementation (3-4 días)
├── Fase 9E: Attachments Implementation (4-5 días)
└── Fase 9F: Ratings Implementation (3-4 días)
   Total Implementation: 2-3 semanas

SEMANA 5:
├── Fase 10: Permissions & Security (1 semana)
└── Bugfixes & Refinements

SEMANA 6:
├── Fase 11: Unit & Integration Tests (1 semana)
└── Final polish & optimization
```

---

## 🎯 CADENCIA RECOMENDADA

### Por Día (Sprints de 1-2 días)

**Patrón TDD:**
```
1. Escribir tests (2-3 horas)
   └── Todos fallan en rojo

2. Implementar features (4-6 horas)
   └── Todos pasan en verde

3. Refactorizar (1-2 horas)
   └── Mejorar código, mantener verde

4. Commit & Push
   └── Git: "feat: implementar [feature]"
```

### Ejemplo - Fase 3 Día 1 (Tickets CRUD):
```
09:00-11:00  → Escribir CreateTicketTest (9 tests)
11:00-17:00  → Implementar CreateTicket logic (9 tests pasan)
17:00-18:00  → Refactorizar, mejorar código
18:00        → Commit: "feat: implement ticket creation"
```

---

## ✅ CRITERIOS DE ÉXITO POR FASE

### Fase 3 ✅
- [ ] 58 tests corriendo en rojo
- [ ] Tests bien organizados
- [ ] Cada test valida UN comportamiento
- [ ] Documentación clara de qué debe implementarse

### Fase 4 ✅
- [ ] 42 tests en rojo
- [ ] Actions identificadas claramente
- [ ] Edge cases cubiertas

### Fase 5-8 ✅
- [ ] 40 + 25 + 37 + 26 = 128 tests en rojo
- [ ] Todas las features mapeadas
- [ ] Dependencias claras

### Fase 9 ✅
- [ ] 293 tests pasando (100% verde)
- [ ] BD completamente implementada
- [ ] Todos los servicios funcionando
- [ ] No hay warnings en tests

### Fase 10 ✅
- [ ] 26 tests pasando
- [ ] Permisos validados en cada endpoint
- [ ] Company isolation garantizada
- [ ] Role-based access funcionando

### Fase 11 ✅
- [ ] 45 tests pasando
- [ ] Unit tests para servicios
- [ ] Integration tests para flujos
- [ ] 100% cobertura de código crítico

---

## 🚀 PRÓXIMOS PASOS INMEDIATOS

### Esta Semana (Fase 3):
1. **Lunes-Martes**: Escribir 58 tests para Tickets CRUD (RED phase)
   - CreateTicketTest.php
   - ListTicketsTest.php
   - GetTicketTest.php
   - UpdateTicketTest.php
   - DeleteTicketTest.php

2. **Miércoles-Jueves**: Escribir 42 tests para Ticket Actions (RED phase)
   - ResolveTicketTest.php
   - CloseTicketTest.php
   - ReopenTicketTest.php
   - AssignTicketTest.php

3. **Viernes**: Escribir tests para Responses, Internal Notes (42+25 tests)

### Validación:
```bash
# Todos los tests deben estar en rojo:
php artisan test --filter "TicketManagement" --fail-on-risky

# Verificar que hay 100+ tests nuevos
# Esperado: FAIL  Tests\Feature\TicketManagement\...
```

---

## 📚 REFERENCIAS

- 📄 **BD Design**: `Modelado final de base de datos.txt`
- 📄 **API Spec**: `tickets-feature-maping.md`
- 📄 **Testing Plan**: `Tickets-tests-TDD-plan.md`
- 📊 **DB Diagram**: Ver `Modelado final de base de datos.txt` líneas 1-803

---

**FIN DEL PLAN DE FASES**

> **Última actualización**: 2025-11-10
> **Siguiente revisión**: Después de completar Fase 3
> **Responsable**: Luke (Implementación)
