# 📁 CONTENT MANAGEMENT FEATURE - ESTRUCTURA FINAL + TESTING PLAN

> **Feature**: Content Management (Announcements + Help Center Articles)  
> **Cobertura de Tests**: Unit + Integration + Feature + Edge Cases  
> **Total de Archivos de Test**: 28 archivos  
> **Total de Tests Estimados**: ~215 tests

---

## 📂 PARTE 1: ESTRUCTURA DE CARPETAS FINAL

```
app/Features/ContentManagement/
│
├── Database/
│   ├── Factories/
│   │   ├── AnnouncementFactory.php
│   │   ├── HelpCenterArticleFactory.php
│   │   └── ArticleCategoryFactory.php
│   │
│   ├── Migrations/
│   │   ├── 2025_11_01_000001_create_article_categories_table.php
│   │   ├── 2025_11_01_000002_create_company_announcements_table.php
│   │   └── 2025_11_01_000003_create_help_center_articles_table.php
│   │
│   └── Seeders/
│       ├── ArticleCategoriesSeeder.php (4 categorías globales)
│       └── ContentManagementSeeder.php
│
├── Enums/
│   ├── AnnouncementType.php (MAINTENANCE, INCIDENT, NEWS, ALERT)
│   ├── PublicationStatus.php (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED)
│   ├── UrgencyLevel.php (LOW, MEDIUM, HIGH, CRITICAL)
│   ├── NewsType.php (feature_release, policy_update, general_update)
│   └── AlertType.php (security, system, service, compliance)
│
├── Events/
│   ├── AnnouncementCreated.php
│   ├── AnnouncementPublished.php
│   ├── AnnouncementScheduled.php
│   ├── AnnouncementArchived.php
│   ├── IncidentResolved.php
│   ├── ArticleCreated.php
│   ├── ArticlePublished.php
│   └── ArticleViewed.php
│
├── Exceptions/
│   ├── AnnouncementNotFoundException.php
│   ├── AnnouncementNotEditableException.php
│   ├── InvalidScheduleDateException.php
│   ├── NotFollowingCompanyException.php
│   ├── ArticleNotFoundException.php
│   └── DuplicateArticleTitleException.php
│
├── Http/
│   ├── Controllers/
│   │   ├── AnnouncementController.php (lista, show general)
│   │   ├── MaintenanceAnnouncementController.php (CRUD maintenance)
│   │   ├── IncidentAnnouncementController.php (CRUD incident)
│   │   ├── NewsAnnouncementController.php (CRUD news)
│   │   ├── AlertAnnouncementController.php (CRUD alert)
│   │   ├── AnnouncementActionController.php (publish, schedule, archive, etc.)
│   │   ├── HelpCenterCategoryController.php (lista categorías)
│   │   ├── HelpCenterArticleController.php (CRUD articles)
│   │   └── AnnouncementSchemaController.php (schemas endpoint)
│   │
│   ├── Middleware/
│   │   ├── EnsureFollowsCompany.php (valida seguimiento)
│   │   └── EnsureCompanyAdmin.php (valida role)
│   │
│   ├── Requests/
│   │   ├── Announcements/
│   │   │   ├── StoreMaintenanceRequest.php
│   │   │   ├── StoreIncidentRequest.php
│   │   │   ├── StoreNewsRequest.php
│   │   │   ├── StoreAlertRequest.php
│   │   │   ├── UpdateAnnouncementRequest.php
│   │   │   └── ScheduleAnnouncementRequest.php
│   │   │
│   │   └── Articles/
│   │       ├── StoreArticleRequest.php
│   │       ├── UpdateArticleRequest.php
│   │       └── PublishArticleRequest.php
│   │
│   └── Resources/
│       ├── AnnouncementResource.php
│       ├── AnnouncementListResource.php
│       ├── ArticleResource.php
│       ├── ArticleListResource.php
│       ├── CategoryResource.php
│       └── SchemaResource.php
│
├── Jobs/
│   ├── PublishAnnouncementJob.php (ejecutado por Redis)
│   ├── IncrementArticleViewsJob.php
│   └── CleanupArchivedAnnouncementsJob.php (opcional)
│
├── Listeners/
│   ├── SendAnnouncementNotification.php
│   ├── NotifyIncidentResolution.php
│   ├── LogAnnouncementPublished.php
│   └── UpdateArticleSearchIndex.php (opcional)
│
├── Mail/
│   ├── AnnouncementPublishedMail.php
│   ├── IncidentResolvedMail.php
│   └── CriticalAlertMail.php
│
├── Models/
│   ├── Announcement.php
│   ├── HelpCenterArticle.php
│   └── ArticleCategory.php
│
├── Observers/
│   ├── AnnouncementObserver.php (timestamps, auditoría)
│   └── ArticleObserver.php (views increment)
│
├── Policies/
│   ├── AnnouncementPolicy.php
│   └── ArticlePolicy.php
│
├── Rules/
│   ├── ValidAnnouncementMetadata.php (valida metadata por tipo)
│   ├── ValidScheduleDate.php (min 5 min, max 1 año)
│   └── UniqueArticleTitle.php (por empresa)
│
├── Services/
│   ├── AnnouncementService.php (lógica de negocio)
│   ├── MaintenanceService.php
│   ├── IncidentService.php
│   ├── NewsService.php
│   ├── AlertService.php
│   ├── ArticleService.php
│   ├── SchedulingService.php (maneja Redis Queue)
│   └── VisibilityService.php (valida seguimiento empresas)
│
├── DTOs/
│   ├── CreateMaintenanceData.php
│   ├── CreateIncidentData.php
│   ├── CreateNewsData.php
│   ├── CreateAlertData.php
│   └── CreateArticleData.php
│
└── ContentManagementServiceProvider.php
```

---

## 🧪 PARTE 2: PLAN COMPLETO DE TESTING

### Estructura de Tests

```
tests/Feature/ContentManagement/
├── Announcements/
│   ├── Maintenance/
│   ├── Incidents/
│   ├── News/
│   ├── Alerts/
│   └── General/
│
├── Articles/
│
└── Permissions/

tests/Unit/ContentManagement/
├── Services/
├── Models/
├── Rules/
└── Enums/
```

---

## 📋 TESTS DETALLADOS POR ARCHIVO

---

## 🔔 ANNOUNCEMENTS - MAINTENANCE

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/CreateMaintenanceAnnouncementTest.php`

**Total de Tests: 15**

1. **test_company_admin_can_create_maintenance_as_draft**
   - Usuario COMPANY_ADMIN crea mantenimiento sin especificar action
   - Verifica status = DRAFT
   - Verifica que NO se encola job en Redis

2. **test_company_admin_can_create_and_publish_maintenance_immediately**
   - Usuario COMPANY_ADMIN crea con action=publish
   - Verifica status = PUBLISHED, published_at != null
   - Verifica que NO se encola job

3. **test_company_admin_can_create_and_schedule_maintenance_in_one_request**
   - Usuario COMPANY_ADMIN crea con action=schedule, scheduled_for
   - Verifica status = SCHEDULED
   - Verifica metadata.scheduled_for presente
   - Verifica PublishAnnouncementJob encolado en Redis con delay correcto

4. **test_validates_required_fields_for_maintenance**
   - Omite title → error 422
   - Omite urgency → error 422
   - Omite scheduled_start → error 422
   - Omite scheduled_end → error 422
   - Omite is_emergency → error 422

5. **test_validates_scheduled_end_is_after_scheduled_start**
   - scheduled_end anterior a scheduled_start → error 422

6. **test_validates_urgency_enum_values**
   - urgency="INVALID" → error 422
   - urgency="CRITICAL" → error 422 (solo LOW, MEDIUM, HIGH para maintenance)

7. **test_validates_affected_services_is_array**
   - affected_services="string" → error 422
   - affected_services con 25 items → error 422 (max 20)

8. **test_validates_scheduled_for_is_at_least_5_minutes_in_future**
   - action=schedule, scheduled_for en el pasado → error 422
   - action=schedule, scheduled_for en 2 minutos → error 422
   - action=schedule, scheduled_for en 6 minutos → ✅ 201

9. **test_validates_scheduled_for_is_not_more_than_1_year_in_future**
   - action=schedule, scheduled_for en 400 días → error 422

10. **test_scheduled_for_is_required_when_action_is_schedule**
    - action=schedule pero sin scheduled_for → error 422

11. **test_scheduled_for_is_ignored_when_action_is_not_schedule**
    - action=draft con scheduled_for → ✅ crea DRAFT (ignora scheduled_for)

12. **test_company_id_is_inferred_from_jwt_token**
    - Usuario COMPANY_ADMIN de empresa A crea anuncio
    - Verifica company_id = empresa A (no manipulable)

13. **test_author_id_is_set_to_authenticated_user**
    - Verifica author_id = user autenticado

14. **test_end_user_cannot_create_maintenance**
    - Usuario END_USER → error 403

15. **test_agent_cannot_create_maintenance**
    - Usuario AGENT → error 403

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/UpdateMaintenanceAnnouncementTest.php`

**Total de Tests: 10**

1. **test_company_admin_can_update_maintenance_in_draft_status**
   - Actualiza title, urgency, affected_services
   - Verifica cambios aplicados

2. **test_company_admin_can_update_maintenance_in_scheduled_status**
   - Actualiza campos, verifica sigue SCHEDULED

3. **test_cannot_update_maintenance_in_published_status**
   - Intenta actualizar PUBLISHED → error 403
   - Mensaje: "No se puede editar un anuncio publicado"

4. **test_cannot_update_maintenance_in_archived_status**
   - Intenta actualizar ARCHIVED → error 403

5. **test_validates_updated_scheduled_end_is_after_scheduled_start**
   - Actualiza con scheduled_end < scheduled_start → error 422

6. **test_company_admin_from_different_company_cannot_update**
   - Admin de empresa B intenta actualizar anuncio de empresa A → error 403

7. **test_partial_update_preserves_unchanged_fields**
   - Actualiza solo title
   - Verifica otros campos intactos

8. **test_update_does_not_change_type_or_company_id**
   - Intenta cambiar type o company_id en request
   - Verifica son ignorados (inmutables)

9. **test_updating_scheduled_maintenance_does_not_reschedule_job**
   - Mantenimiento SCHEDULED
   - Actualiza title (no scheduled_for)
   - Verifica job en Redis no cambió

10. **test_platform_admin_cannot_update_announcements**
    - PLATFORM_ADMIN intenta actualizar → error 403 (read-only)

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/PublishMaintenanceTest.php`

**Total de Tests: 8**

1. **test_company_admin_can_publish_maintenance_from_draft**
   - POST /announcements/:id/publish
   - Verifica status DRAFT → PUBLISHED

2. **test_company_admin_can_publish_maintenance_from_scheduled**
   - Publica SCHEDULED → PUBLISHED
   - Verifica job en Redis es cancelado/eliminado

3. **test_cannot_publish_already_published_maintenance**
   - Intenta publicar PUBLISHED → error 400

4. **test_publish_sets_published_at_timestamp**
   - Verifica published_at = now()

5. **test_publish_triggers_announcement_published_event**
   - Verifica evento AnnouncementPublished disparado

6. **test_publish_triggers_listeners_and_notifications**
   - Verifica listeners ejecutados (SendAnnouncementNotification, etc.)

7. **test_end_user_cannot_publish_maintenance**
   - END_USER → error 403

8. **test_cannot_publish_archived_maintenance**
   - ARCHIVED → error 400
   - Mensaje: "Usa restore primero"

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/ScheduleMaintenanceTest.php`

**Total de Tests: 12**

1. **test_company_admin_can_schedule_maintenance_from_draft**
   - POST /announcements/:id/schedule con scheduled_for
   - Verifica DRAFT → SCHEDULED

2. **test_scheduling_enqueues_publish_job_in_redis**
   - Verifica PublishAnnouncementJob en Redis
   - Verifica delay = scheduled_for - now()

3. **test_scheduling_adds_scheduled_for_to_metadata**
   - Verifica metadata.scheduled_for presente

4. **test_validates_scheduled_for_is_required**
   - Sin scheduled_for → error 422

5. **test_validates_scheduled_for_is_at_least_5_minutes_future**
   - scheduled_for en 2 min → error 422

6. **test_validates_scheduled_for_is_not_more_than_1_year_future**
   - scheduled_for en 400 días → error 422

7. **test_cannot_schedule_already_published_maintenance**
   - PUBLISHED → error 400

8. **test_rescheduling_scheduled_maintenance_updates_job_in_redis**
   - Mantenimiento ya SCHEDULED
   - Programa de nuevo con nueva fecha
   - Verifica job anterior cancelado
   - Verifica nuevo job encolado con nuevo delay

9. **test_scheduling_from_scheduled_replaces_previous_schedule**
   - SCHEDULED con scheduled_for=Nov 8
   - Re-programa para Nov 10
   - Verifica metadata.scheduled_for actualizado

10. **test_end_user_cannot_schedule_maintenance**
    - END_USER → error 403

11. **test_cannot_schedule_archived_maintenance**
    - ARCHIVED → error 400

12. **test_scheduling_archived_maintenance_requires_restore_first**
    - Verifica mensaje de error apropiado

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/UnscheduleMaintenanceTest.php`

**Total de Tests: 6**

1. **test_company_admin_can_unschedule_maintenance**
   - POST /announcements/:id/unschedule
   - Verifica SCHEDULED → DRAFT

2. **test_unscheduling_removes_scheduled_for_from_metadata**
   - Verifica metadata.scheduled_for eliminado

3. **test_unscheduling_cancels_job_in_redis**
   - Verifica PublishAnnouncementJob removido de Redis

4. **test_cannot_unschedule_non_scheduled_maintenance**
   - DRAFT → error 400
   - Mensaje: "No está programado"

5. **test_cannot_unschedule_published_maintenance**
   - PUBLISHED → error 400

6. **test_end_user_cannot_unschedule**
   - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/ArchiveMaintenanceTest.php`

**Total de Tests: 7**

1. **test_company_admin_can_archive_published_maintenance**
   - POST /announcements/:id/archive
   - Verifica PUBLISHED → ARCHIVED

2. **test_cannot_archive_draft_maintenance**
   - DRAFT → error 400

3. **test_cannot_archive_scheduled_maintenance**
   - SCHEDULED → error 400

4. **test_cannot_archive_already_archived_maintenance**
   - ARCHIVED → error 400

5. **test_archive_triggers_announcement_archived_event**
   - Verifica evento disparado

6. **test_archived_maintenance_not_visible_to_end_users**
   - Usuario END_USER lista anuncios
   - Verifica ARCHIVED no aparece

7. **test_archived_maintenance_visible_to_company_admin**
   - COMPANY_ADMIN puede ver con filtro status=archived

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/RestoreMaintenanceTest.php`

**Total de Tests: 5**

1. **test_company_admin_can_restore_archived_maintenance**
   - POST /announcements/:id/restore
   - Verifica ARCHIVED → DRAFT

2. **test_cannot_restore_non_archived_maintenance**
   - DRAFT → error 400
   - PUBLISHED → error 400

3. **test_restored_maintenance_keeps_original_content_and_metadata**
   - Verifica contenido intacto después de restore

4. **test_restored_maintenance_can_be_edited_again**
   - Restore → UPDATE → ✅

5. **test_end_user_cannot_restore**
   - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/DeleteMaintenanceTest.php`

**Total de Tests: 7**

1. **test_company_admin_can_delete_draft_maintenance**
   - DELETE /announcements/:id
   - DRAFT → eliminado ✅

2. **test_company_admin_can_delete_archived_maintenance**
   - ARCHIVED → eliminado ✅

3. **test_cannot_delete_published_maintenance**
   - PUBLISHED → error 403
   - Mensaje: "Archívalo primero"

4. **test_cannot_delete_scheduled_maintenance**
   - SCHEDULED → error 403
   - Mensaje: "Desprograma primero"

5. **test_deleting_scheduled_maintenance_cancels_redis_job**
   - Si permite delete de SCHEDULED
   - Verifica job cancelado

6. **test_deleted_maintenance_cannot_be_retrieved**
   - DELETE → GET /:id → 404

7. **test_end_user_cannot_delete**
   - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/MarkMaintenanceStartTest.php`

**Total de Tests: 6**

1. **test_company_admin_can_mark_maintenance_start**
   - POST /announcements/maintenance/:id/start
   - Verifica metadata.actual_start = now()

2. **test_start_can_be_marked_before_scheduled_start**
   - scheduled_start = 10:00
   - Marca inicio a las 09:55
   - Verifica actual_start = 09:55

3. **test_start_can_be_marked_after_scheduled_start**
   - scheduled_start = 10:00
   - Marca inicio a las 10:05
   - Verifica actual_start = 10:05

4. **test_marking_start_does_not_change_status**
   - PUBLISHED → marca inicio → sigue PUBLISHED

5. **test_cannot_mark_start_twice**
   - Marca inicio
   - Intenta marcar de nuevo → error 400

6. **test_end_user_cannot_mark_start**
   - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Maintenance/MarkMaintenanceCompleteTest.php`

**Total de Tests: 7**

1. **test_company_admin_can_mark_maintenance_complete**
   - POST /announcements/maintenance/:id/complete
   - Verifica metadata.actual_end = now()

2. **test_complete_can_be_marked_before_scheduled_end**
   - scheduled_end = 14:00
   - Marca fin a las 13:30
   - Verifica actual_end = 13:30

3. **test_complete_can_be_marked_after_scheduled_end**
   - scheduled_end = 14:00
   - Marca fin a las 14:15
   - Verifica actual_end = 14:15

4. **test_marking_complete_requires_start_first**
   - Sin actual_start → error 400
   - Mensaje: "Marca inicio primero"

5. **test_actual_end_must_be_after_actual_start**
   - actual_start = 10:00
   - Intenta actual_end = 09:00 → error 400

6. **test_cannot_mark_complete_twice**
   - Ya tiene actual_end → error 400

7. **test_end_user_cannot_mark_complete**
   - END_USER → error 403

---

## 🚨 ANNOUNCEMENTS - INCIDENTS

### Archivo: `tests/Feature/ContentManagement/Announcements/Incidents/CreateIncidentAnnouncementTest.php`

**Total de Tests: 12**

1. **test_company_admin_can_create_incident_as_draft**
   - Crea sin action → DRAFT

2. **test_company_admin_can_create_and_publish_incident_immediately**
   - action=publish para incidente urgente
   - Verifica PUBLISHED inmediatamente

3. **test_validates_required_fields_for_incident**
   - Omite urgency → error
   - Omite is_resolved → error
   - Omite started_at → error

4. **test_validates_urgency_includes_critical_for_incidents**
   - urgency="CRITICAL" → ✅ (permitido para incidents)
   - urgency="LOW" → ✅
   - urgency="INVALID" → error 422

5. **test_validates_is_resolved_is_boolean**
   - is_resolved="yes" → error 422

6. **test_validates_resolved_at_required_when_is_resolved_true**
   - is_resolved=true sin resolved_at → error 422

7. **test_validates_resolution_content_required_when_is_resolved_true**
   - is_resolved=true sin resolution_content → error 422

8. **test_validates_ended_at_is_after_started_at**
   - ended_at anterior a started_at → error 422

9. **test_can_create_unresolved_incident_without_resolution_fields**
   - is_resolved=false
   - Sin resolved_at ni resolution_content → ✅

10. **test_creating_resolved_incident_includes_all_resolution_data**
    - is_resolved=true con resolved_at y resolution_content → ✅

11. **test_incidents_can_be_scheduled_but_unusual**
    - action=schedule → ✅ funciona
    - Nota: casos edge, normalmente incidents son publish inmediato

12. **test_end_user_cannot_create_incident**
    - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Incidents/ResolveIncidentTest.php`

**Total de Tests: 10**

1. **test_company_admin_can_resolve_incident**
   - POST /announcements/incidents/:id/resolve
   - body: {resolution_content, ended_at}
   - Verifica is_resolved=true, resolved_at != null

2. **test_resolve_validates_resolution_content_required**
   - Sin resolution_content → error 422

3. **test_resolve_validates_ended_at_is_after_started_at**
   - ended_at < started_at → error 422

4. **test_resolve_sets_resolved_at_to_now_if_not_provided**
   - Sin resolved_at en request
   - Verifica resolved_at = now()

5. **test_resolve_uses_provided_ended_at**
   - Provee ended_at específico
   - Verifica usado correctamente

6. **test_resolve_updates_incident_title_optionally**
   - Permite actualizar title a "✅ Resuelto: ..."

7. **test_resolve_triggers_incident_resolved_event**
   - Verifica evento IncidentResolved disparado

8. **test_resolve_sends_notification_to_followers**
   - Verifica email/notif enviado a usuarios que siguen la empresa

9. **test_cannot_resolve_already_resolved_incident**
   - is_resolved=true → resolve again → error 400

10. **test_end_user_cannot_resolve_incident**
    - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Incidents/UpdateIncidentTest.php`

**Total de Tests: 8**

1. **test_can_update_unresolved_incident_details**
   - Actualiza title, urgency, affected_services

2. **test_can_update_resolved_incident_to_add_more_info**
   - Incidente ya resuelto
   - Actualiza resolution_content con más detalles → ✅

3. **test_cannot_change_is_resolved_from_true_to_false**
   - Una vez resuelto, no se puede "des-resolver"
   - Intenta is_resolved=false → error 400

4. **test_updating_urgency_from_critical_to_low_after_resolution**
   - Incidente resuelto
   - Baja urgencia a LOW → ✅

5. **test_validates_ended_at_not_before_started_at_on_update**
   - Actualiza ended_at < started_at → error 422

6. **test_partial_update_of_incident_metadata**
   - Actualiza solo affected_services
   - Verifica otros campos intactos

7. **test_cannot_update_published_incident_basic_info_after_24_hours**
   - Incidente publicado hace >24h
   - Intenta actualizar → error 403 (opcional, regla de negocio)

8. **test_company_admin_from_different_company_cannot_update_incident**
   - Admin B → incident de empresa A → error 403

---

## 📰 ANNOUNCEMENTS - NEWS

### Archivo: `tests/Feature/ContentManagement/Announcements/News/CreateNewsAnnouncementTest.php`

**Total de Tests: 10**

1. **test_company_admin_can_create_news_as_draft**
   - Crea news sin action → DRAFT

2. **test_company_admin_can_create_and_publish_news**
   - action=publish → PUBLISHED

3. **test_validates_required_fields_for_news**
   - Omite news_type → error 422
   - Omite target_audience → error 422
   - Omite summary → error 422

4. **test_validates_news_type_enum**
   - news_type="invalid" → error 422
   - Solo: feature_release, policy_update, general_update

5. **test_validates_target_audience_is_array_with_valid_values**
   - target_audience="users" (string) → error 422
   - target_audience=["invalid"] → error 422
   - target_audience=[] → error 422 (min 1)

6. **test_validates_summary_length**
   - summary con 5 chars → error 422 (min 10)
   - summary con 600 chars → error 422 (max 500)

7. **test_call_to_action_is_optional**
   - Sin call_to_action → ✅

8. **test_validates_call_to_action_structure**
   - call_to_action sin "text" → error 422
   - call_to_action sin "url" → error 422
   - call_to_action.url inválida → error 422

9. **test_news_can_be_scheduled**
   - action=schedule con scheduled_for → SCHEDULED

10. **test_end_user_cannot_create_news**
    - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Announcements/News/UpdateNewsAnnouncementTest.php`

**Total de Tests: 6**

1. **test_can_update_news_in_draft**
   - Actualiza news_type, summary, call_to_action

2. **test_can_update_news_call_to_action**
   - Agrega/modifica CTA

3. **test_can_remove_call_to_action**
   - Actualiza call_to_action=null → removido

4. **test_cannot_update_published_news**
   - PUBLISHED → error 403

5. **test_validates_updated_target_audience**
   - target_audience=[] → error 422

6. **test_updating_preserves_other_metadata**
   - Actualiza solo summary
   - Verifica news_type intacto

---

## 🚨 ANNOUNCEMENTS - ALERTS

### Archivo: `tests/Feature/ContentManagement/Announcements/Alerts/CreateAlertAnnouncementTest.php`

**Total de Tests: 11**

1. **test_company_admin_can_create_alert_as_draft**
   - Crea sin action → DRAFT

2. **test_company_admin_can_create_and_publish_critical_alert**
   - urgency=CRITICAL, action=publish → PUBLISHED

3. **test_validates_required_fields_for_alert**
   - Omite urgency → error
   - Omite alert_type → error
   - Omite message → error
   - Omite action_required → error
   - Omite started_at → error

4. **test_validates_urgency_only_allows_high_or_critical**
   - urgency="LOW" → error 422
   - urgency="MEDIUM" → error 422
   - urgency="HIGH" → ✅
   - urgency="CRITICAL" → ✅

5. **test_validates_alert_type_enum**
   - alert_type="invalid" → error 422
   - Solo: security, system, service, compliance

6. **test_validates_message_length**
   - message con 5 chars → error 422 (min 10)
   - message con 600 chars → error 422 (max 500)

7. **test_validates_action_description_required_when_action_required_true**
   - action_required=true sin action_description → error 422

8. **test_action_description_optional_when_action_required_false**
   - action_required=false sin action_description → ✅

9. **test_validates_ended_at_is_after_started_at**
   - ended_at < started_at → error 422

10. **test_critical_security_alerts_send_immediate_notifications**
    - urgency=CRITICAL, alert_type=security, action=publish
    - Verifica emails enviados inmediatamente

11. **test_end_user_cannot_create_alert**
    - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Announcements/Alerts/UpdateAlertTest.php`

**Total de Tests: 5**

1. **test_can_update_alert_message_and_action_description**
   - Actualiza message, action_description

2. **test_can_mark_alert_as_ended**
   - Actualiza ended_at → alerta terminada

3. **test_cannot_change_action_required_from_true_to_false**
   - action_required=true → no se puede cambiar a false

4. **test_cannot_update_published_alert**
   - PUBLISHED → error 403

5. **test_validates_updated_urgency_still_high_or_critical**
   - Intenta urgency=MEDIUM → error 422

---

## 📊 ANNOUNCEMENTS - GENERAL

### Archivo: `tests/Feature/ContentManagement/Announcements/General/ListAnnouncementsTest.php`

**Total de Tests: 15**

1. **test_end_user_can_list_published_announcements_from_followed_companies**
   - Usuario sigue empresa A y B
   - GET /announcements
   - Verifica solo ve PUBLISHED de A y B

2. **test_end_user_cannot_see_announcements_from_non_followed_companies**
   - Usuario sigue empresa A
   - Empresa B tiene anuncios PUBLISHED
   - Verifica usuario no los ve

3. **test_company_admin_can_see_all_announcements_of_own_company**
   - Admin de empresa A
   - GET /announcements
   - Verifica ve DRAFT, SCHEDULED, PUBLISHED, ARCHIVED de su empresa

4. **test_company_admin_cannot_see_announcements_from_other_companies**
   - Admin de empresa A
   - Verifica no ve anuncios de empresa B

5. **test_platform_admin_can_see_all_announcements_from_all_companies**
   - PLATFORM_ADMIN
   - Verifica ve todo

6. **test_filter_by_status_works**
   - GET /announcements?status=published
   - Verifica solo PUBLISHED

7. **test_filter_by_type_works**
   - GET /announcements?type=INCIDENT
   - Verifica solo INCIDENT

8. **test_filter_by_multiple_criteria**
   - status=published&type=MAINTENANCE
   - Verifica filtros combinados

9. **test_search_by_title_works**
   - GET /announcements?search=mantenimiento
   - Verifica búsqueda en title

10. **test_search_by_content_works**
    - Búsqueda en content también

11. **test_sort_by_published_at_desc_default**
    - Verifica orden descendente por defecto

12. **test_sort_by_created_at_asc**
    - sort=created_at (sin -)
    - Verifica orden ascendente

13. **test_pagination_works_correctly**
    - page=2, per_page=10
    - Verifica paginación

14. **test_unauthenticated_user_cannot_list_announcements**
    - Sin token → error 401

15. **test_filter_by_date_range**
    - published_after & published_before
    - Verifica filtrado por fechas

---

### Archivo: `tests/Feature/ContentManagement/Announcements/General/GetAnnouncementByIdTest.php`

**Total de Tests: 8**

1. **test_end_user_can_view_published_announcement_from_followed_company**
   - GET /announcements/:id
   - PUBLISHED de empresa seguida → ✅

2. **test_end_user_cannot_view_draft_announcement**
   - DRAFT → error 403

3. **test_end_user_cannot_view_announcement_from_non_followed_company**
   - PUBLISHED de empresa NO seguida → error 403

4. **test_company_admin_can_view_any_status_announcement_from_own_company**
   - Admin ve DRAFT, SCHEDULED, etc.

5. **test_company_admin_cannot_view_announcement_from_other_company**
   - Admin A → anuncio empresa B → error 403

6. **test_platform_admin_can_view_any_announcement**
   - PLATFORM_ADMIN ve todo

7. **test_get_nonexistent_announcement_returns_404**
   - UUID inválido → 404

8. **test_announcement_includes_all_expected_fields**
   - Verifica estructura completa del response

---

### Archivo: `tests/Feature/ContentManagement/Announcements/General/GetAnnouncementSchemasTest.php`

**Total de Tests: 6**

1. **test_company_admin_can_get_schemas**
   - GET /announcements/schemas
   - Verifica respuesta exitosa

2. **test_schemas_include_all_four_announcement_types**
   - Verifica MAINTENANCE, INCIDENT, NEWS, ALERT presentes

3. **test_maintenance_schema_has_correct_structure**
   - Verifica required, optional, fields

4. **test_incident_schema_includes_critical_urgency**
   - Verifica CRITICAL en values

5. **test_schemas_include_validation_rules**
   - Verifica min_length, max_length, etc.

6. **test_end_user_cannot_access_schemas**
   - END_USER → error 403 (solo COMPANY_ADMIN)

---

## 📚 HELP CENTER ARTICLES

### Archivo: `tests/Feature/ContentManagement/Articles/ListCategoriesTest.php`

**Total de Tests: 4**

1. **test_unauthenticated_user_can_list_categories**
   - GET /help-center/categories sin token → ✅ 200

2. **test_returns_exactly_four_categories**
   - Verifica total = 4

3. **test_categories_include_expected_fields**
   - id, code, name, description

4. **test_categories_are_in_expected_order**
   - Verifica orden consistente

---

### Archivo: `tests/Feature/ContentManagement/Articles/CreateArticleTest.php`

**Total de Tests: 12**

1. **test_company_admin_can_create_article_as_draft**
   - POST /help-center/articles sin action → DRAFT

2. **test_company_admin_can_create_and_publish_article**
   - action=publish → PUBLISHED

3. **test_validates_required_fields**
   - Omite category_id → error
   - Omite title → error
   - Omite content → error

4. **test_validates_category_id_exists**
   - category_id inválido → error 422

5. **test_validates_title_is_unique_per_company**
   - Empresa A ya tiene "Cómo cambiar contraseña"
   - Intenta crear duplicado → error 422

6. **test_title_uniqueness_is_per_company_not_global**
   - Empresa A y B pueden tener mismo título → ✅

7. **test_validates_content_length**
   - content con 30 chars → error (min 50)
   - content con 25000 chars → error (max 20000)

8. **test_excerpt_is_optional**
   - Sin excerpt → ✅

9. **test_validates_excerpt_max_length**
   - excerpt con 600 chars → error (max 500)

10. **test_article_is_created_with_zero_views**
    - Verifica views_count = 0

11. **test_company_id_is_inferred_from_token**
    - Verifica company_id = del JWT

12. **test_end_user_cannot_create_article**
    - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Articles/UpdateArticleTest.php`

**Total de Tests: 8**

1. **test_company_admin_can_update_draft_article**
   - Actualiza title, content, excerpt

2. **test_company_admin_can_update_published_article**
   - Actualiza contenido de PUBLISHED → ✅
   - Nota: Artículos se pueden editar publicados

3. **test_updating_published_article_does_not_change_published_at**
   - published_at sigue igual

4. **test_can_change_category**
   - Cambia category_id → ✅

5. **test_validates_updated_title_uniqueness**
   - Cambia a título que ya existe → error 422

6. **test_partial_update_works**
   - Actualiza solo excerpt

7. **test_cannot_update_article_from_different_company**
   - Admin B → artículo empresa A → error 403

8. **test_updating_resets_views_count_is_false**
   - views_count NO se resetea al actualizar

---

### Archivo: `tests/Feature/ContentManagement/Articles/PublishArticleTest.php`

**Total de Tests: 6**

1. **test_company_admin_can_publish_draft_article**
   - POST /help-center/articles/:id/publish
   - DRAFT → PUBLISHED

2. **test_publish_sets_published_at**
   - Verifica published_at = now()

3. **test_cannot_publish_already_published_article**
   - PUBLISHED → error 400

4. **test_publish_triggers_article_published_event**
   - Verifica evento disparado

5. **test_published_article_becomes_visible_to_end_users**
   - END_USER puede listar/ver

6. **test_end_user_cannot_publish_article**
   - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Articles/UnpublishArticleTest.php`

**Total de Tests: 5**

1. **test_company_admin_can_unpublish_article**
   - POST /help-center/articles/:id/unpublish
   - PUBLISHED → DRAFT

2. **test_unpublish_sets_published_at_to_null**
   - Verifica published_at = null

3. **test_unpublished_article_not_visible_to_end_users**
   - END_USER no lo ve en lista

4. **test_cannot_unpublish_draft_article**
   - DRAFT → error 400

5. **test_unpublish_preserves_views_count**
   - views_count NO se resetea

---

### Archivo: `tests/Feature/ContentManagement/Articles/DeleteArticleTest.php`

**Total de Tests: 5**

1. **test_company_admin_can_delete_draft_article**
   - DELETE /help-center/articles/:id
   - DRAFT → eliminado ✅

2. **test_cannot_delete_published_article**
   - PUBLISHED → error 403
   - Mensaje: "Despublica primero"

3. **test_deleted_article_returns_404**
   - DELETE → GET → 404

4. **test_company_admin_cannot_delete_article_from_other_company**
   - Admin B → artículo A → error 403

5. **test_end_user_cannot_delete_article**
   - END_USER → error 403

---

### Archivo: `tests/Feature/ContentManagement/Articles/ListArticlesTest.php`

**Total de Tests: 12**

1. **test_end_user_can_list_published_articles_from_followed_company**
   - Usuario sigue empresa A
   - GET /help-center/articles?company_id=A
   - Ve solo PUBLISHED

2. **test_end_user_cannot_see_draft_articles**
   - DRAFT no aparece en lista

3. **test_end_user_cannot_list_articles_from_non_followed_company**
   - Usuario no sigue empresa B
   - GET /help-center/articles?company_id=B → error 403

4. **test_company_admin_can_see_all_articles_of_own_company**
   - Ve DRAFT + PUBLISHED

5. **test_filter_by_category_works**
   - category=SECURITY_PRIVACY
   - Verifica filtrado correcto

6. **test_filter_by_status_works**
   - status=published

7. **test_search_by_title_works**
   - search=contraseña

8. **test_search_by_content_works**
   - Búsqueda en content

9. **test_sort_by_views_desc**
   - sort=-views
   - Verifica orden por vistas

10. **test_sort_by_title_asc**
    - sort=title
    - Orden alfabético

11. **test_pagination_works**
    - page=2, per_page=20

12. **test_unauthenticated_user_cannot_list_articles**
    - Sin token → error 401

---

### Archivo: `tests/Feature/ContentManagement/Articles/ViewArticleTest.php`

**Total de Tests: 10**

1. **test_end_user_can_view_published_article_from_followed_company**
   - GET /help-center/articles/:id → ✅

2. **test_viewing_article_increments_views_count**
   - GET → views_count += 1

3. **test_views_count_only_increments_for_published_articles**
   - GET DRAFT → views_count NO incrementa

4. **test_multiple_views_by_same_user_increment_count**
   - Usuario ve 3 veces → views_count += 3
   - (Sin tracking de usuario único)

5. **test_end_user_cannot_view_draft_article**
   - DRAFT → error 403

6. **test_end_user_cannot_view_article_from_non_followed_company**
   - Empresa no seguida → error 403

7. **test_company_admin_can_view_draft_article**
   - Admin ve DRAFT → ✅

8. **test_viewing_draft_does_not_increment_views**
   - Admin ve DRAFT → views NO incrementa

9. **test_article_content_is_returned_complete**
   - Verifica content completo en response

10. **test_nonexistent_article_returns_404**
    - UUID inválido → 404

---

## 🔐 PERMISSIONS & VISIBILITY

### Archivo: `tests/Feature/ContentManagement/Permissions/CompanyFollowingTest.php`

**Total de Tests: 8**

1. **test_user_following_company_can_see_announcements**
   - Usuario sigue empresa → ve anuncios ✅

2. **test_user_not_following_company_cannot_see_announcements**
   - No sigue → error 403

3. **test_user_following_company_can_see_articles**
   - Sigue → ve artículos ✅

4. **test_user_not_following_company_cannot_see_articles**
   - No sigue → error 403

5. **test_user_unfollows_company_loses_access**
   - Sigue → ve contenido
   - Deja de seguir → ya no ve

6. **test_company_admin_sees_own_content_regardless_of_following**
   - Admin no necesita "seguir" su empresa

7. **test_platform_admin_sees_all_content_regardless_of_following**
   - PLATFORM_ADMIN ve todo

8. **test_middleware_validates_following_status**
   - Verifica EnsureFollowsCompany middleware funciona

---

### Archivo: `tests/Feature/ContentManagement/Permissions/RoleBasedAccessTest.php`

**Total de Tests: 12**

1. **test_end_user_can_only_read_published_content**
   - No puede crear, editar, eliminar

2. **test_agent_can_only_read_published_content_of_own_company**
   - Mismo que END_USER para content management

3. **test_company_admin_has_full_crud_on_own_company_content**
   - Crear, leer, actualizar, eliminar → ✅

4. **test_company_admin_cannot_access_other_company_content**
   - Admin A → empresa B → error 403

5. **test_platform_admin_has_read_only_access_to_all_content**
   - Puede ver todo
   - No puede crear/editar

6. **test_unauthenticated_user_can_only_access_categories**
   - Solo GET /help-center/categories → ✅
   - Todo lo demás → error 401

7. **test_company_admin_cannot_create_content_for_other_company**
   - company_id inferido del JWT previene esto

8. **test_role_validation_happens_before_business_logic**
   - Middleware valida role primero

9. **test_end_user_attempting_admin_action_gets_clear_error**
   - Mensaje apropiado de permisos insuficientes

10. **test_suspended_user_cannot_access_any_endpoint**
    - Usuario suspendido → error 403

11. **test_expired_token_returns_401**
    - Token expirado → error 401

12. **test_invalid_token_returns_401**
    - Token malformado → error 401

---

## 🧪 UNIT TESTS

### Archivo: `tests/Unit/ContentManagement/Services/AnnouncementServiceTest.php`

**Total de Tests: 8**

1. **test_create_announcement_sets_default_status_to_draft**
   - Sin action → status = DRAFT

2. **test_publish_action_sets_status_and_published_at**
   - action=publish → verifica campos

3. **test_schedule_action_enqueues_redis_job**
   - Mock Redis Queue
   - Verifica job encolado

4. **test_schedule_validates_future_date**
   - Fecha pasada → exception

5. **test_update_only_allows_draft_or_scheduled**
   - PUBLISHED → exception

6. **test_archive_only_allows_published**
   - DRAFT → exception

7. **test_restore_only_allows_archived**
   - PUBLISHED → exception

8. **test_delete_only_allows_draft_or_archived**
   - PUBLISHED → exception

---

### Archivo: `tests/Unit/ContentManagement/Services/SchedulingServiceTest.php`

**Total de Tests: 6**

1. **test_enqueue_job_calculates_correct_delay**
   - scheduled_for - now() = delay

2. **test_cancel_job_removes_from_redis**
   - Mock Redis
   - Verifica job eliminado

3. **test_reschedule_cancels_old_and_enqueues_new**
   - 2 operaciones en Redis

4. **test_validate_schedule_date_throws_on_past_date**
   - Exception apropiada

5. **test_validate_schedule_date_throws_on_too_far_future**
   - >1 año → exception

6. **test_get_scheduled_jobs_for_announcement**
   - Retorna jobs pendientes

---

### Archivo: `tests/Unit/ContentManagement/Services/VisibilityServiceTest.php`

**Total de Tests: 5**

1. **test_user_can_see_announcement_when_following_company**
   - canSeeAnnouncement() → true

2. **test_user_cannot_see_announcement_when_not_following**
   - canSeeAnnouncement() → false

3. **test_company_admin_can_always_see_own_company_announcements**
   - Ignora following status

4. **test_platform_admin_can_see_any_announcement**
   - Siempre true

5. **test_draft_announcement_only_visible_to_company_admin**
   - END_USER → false
   - COMPANY_ADMIN → true

---

### Archivo: `tests/Unit/ContentManagement/Models/AnnouncementTest.php`

**Total de Tests: 10**

1. **test_announcement_casts_metadata_to_array**
   - metadata es array, no JSON string

2. **test_announcement_casts_status_to_enum**
   - status es PublicationStatus enum

3. **test_announcement_casts_type_to_enum**
   - type es AnnouncementType enum

4. **test_belongs_to_company_relationship**
   - $announcement->company → Company

5. **test_belongs_to_author_relationship**
   - $announcement->author → User

6. **test_is_editable_returns_true_for_draft**
   - isEditable() → true

7. **test_is_editable_returns_false_for_published**
   - isEditable() → false

8. **test_is_published_scope_filters_correctly**
   - Announcement::published()->get()

9. **test_scheduled_for_accessor_parses_from_metadata**
   - $announcement->scheduled_for → Carbon

10. **test_formatted_urgency_returns_localized_string**
    - formattedUrgency() → "Alto", "Medio", etc.

---

### Archivo: `tests/Unit/ContentManagement/Models/HelpCenterArticleTest.php`

**Total de Tests: 8**

1. **test_article_belongs_to_company**
   - $article->company → Company

2. **test_article_belongs_to_category**
   - $article->category → ArticleCategory

3. **test_article_belongs_to_author**
   - $article->author → User

4. **test_increment_views_increments_correctly**
   - incrementViews() → views_count += 1

5. **test_is_published_scope**
   - Article::published()->get()

6. **test_by_category_scope**
   - Article::byCategory('SECURITY_PRIVACY')->get()

7. **test_search_scope_searches_title_and_content**
   - Article::search('password')->get()

8. **test_formatted_published_date**
   - formattedPublishedDate() → "15 Oct 2024"

---

### Archivo: `tests/Unit/ContentManagement/Rules/ValidAnnouncementMetadataTest.php`

**Total de Tests: 8**

1. **test_validates_maintenance_metadata_structure**
   - Metadata válido → passes()

2. **test_maintenance_requires_scheduled_start**
   - Sin scheduled_start → fails()

3. **test_maintenance_scheduled_end_after_start**
   - end < start → fails()

4. **test_incident_requires_started_at**
   - Sin started_at → fails()

5. **test_incident_resolution_content_required_when_resolved**
   - is_resolved=true sin resolution_content → fails()

6. **test_news_requires_target_audience_array**
   - target_audience="string" → fails()

7. **test_alert_action_description_required_when_action_required**
   - action_required=true sin action_description → fails()

8. **test_rule_returns_correct_error_messages**
   - Verifica mensajes descriptivos

---

### Archivo: `tests/Unit/ContentManagement/Rules/ValidScheduleDateTest.php`

**Total de Tests: 5**

1. **test_future_date_passes**
   - +10 min → passes()

2. **test_past_date_fails**
   - -10 min → fails()

3. **test_date_less_than_5_minutes_fails**
   - +2 min → fails()

4. **test_date_more_than_1_year_fails**
   - +400 días → fails()

5. **test_error_message_is_descriptive**
   - Mensaje: "Debe ser entre 5 min y 1 año futuro"

---

### Archivo: `tests/Unit/ContentManagement/Enums/PublicationStatusTest.php`

**Total de Tests: 4**

1. **test_enum_has_all_expected_values**
   - DRAFT, SCHEDULED, PUBLISHED, ARCHIVED

2. **test_enum_values_are_strings**
   - ->value es string

3. **test_from_method_works**
   - PublicationStatus::from('DRAFT')

4. **test_invalid_value_throws_error**
   - from('INVALID') → exception

---

### Archivo: `tests/Unit/ContentManagement/Enums/AnnouncementTypeTest.php`

**Total de Tests: 3**

1. **test_enum_has_four_types**
   - MAINTENANCE, INCIDENT, NEWS, ALERT

2. **test_metadata_schema_method_returns_array**
   - ->metadataSchema() → array

3. **test_each_type_has_unique_required_fields**
   - Verifica diferencias

---

## 🎭 INTEGRATION TESTS

### Archivo: `tests/Integration/ContentManagement/SchedulingFlowTest.php`

**Total de Tests: 5**

1. **test_complete_scheduling_flow**
   - Crear → Schedule → Redis → Publish automático

2. **test_rescheduling_flow**
   - Schedule → Reschedule → Verifica job actualizado

3. **test_unscheduling_flow**
   - Schedule → Unschedule → Verifica job cancelado

4. **test_scheduled_job_executes_at_correct_time**
   - Mock time, avanza, verifica ejecución

5. **test_multiple_announcements_scheduled_independently**
   - 3 anuncios programados en diferentes fechas

---

### Archivo: `tests/Integration/ContentManagement/AnnouncementLifecycleTest.php`

**Total de Tests: 6**

1. **test_draft_to_published_lifecycle**
   - DRAFT → Publish → PUBLISHED

2. **test_draft_to_scheduled_to_published_lifecycle**
   - DRAFT → Schedule → (Redis) → PUBLISHED

3. **test_published_to_archived_to_restored_lifecycle**
   - PUBLISHED → Archive → ARCHIVED → Restore → DRAFT

4. **test_scheduled_to_unscheduled_to_published_lifecycle**
   - SCHEDULED → Unschedule → DRAFT → Publish → PUBLISHED

5. **test_incident_creation_to_resolution_lifecycle**
   - Crear incidente → Publicar → Resolver

6. **test_cannot_skip_validation_states**
   - No se puede DRAFT → ARCHIVED directamente

---

### Archivo: `tests/Integration/ContentManagement/ArticleLifecycleTest.php`

**Total de Tests: 4**

1. **test_create_publish_unpublish_delete_flow**
   - DRAFT → PUBLISHED → DRAFT → DELETE

2. **test_article_views_accumulate_correctly**
   - Múltiples vistas incrementan contador

3. **test_updating_published_article_preserves_state**
   - Update no despublica

4. **test_deleting_published_article_requires_unpublish**
   - PUBLISHED → Delete → error
   - Unpublish → Delete → ✅

---

## 🎯 RESUMEN DE COBERTURA

### Por Categoría

| Categoría | Archivos | Tests | Cobertura |
|-----------|----------|-------|-----------|
| **Maintenance Announcements** | 9 | 71 | CRUD + Actions |
| **Incident Announcements** | 3 | 30 | CRUD + Resolve |
| **News Announcements** | 2 | 16 | CRUD |
| **Alert Announcements** | 2 | 16 | CRUD |
| **General Announcements** | 3 | 29 | List, Get, Schemas |
| **Help Center Articles** | 8 | 72 | CRUD + Views |
| **Permissions** | 2 | 20 | Roles + Following |
| **Unit Tests (Services)** | 3 | 19 | Business Logic |
| **Unit Tests (Models)** | 2 | 18 | Relationships |
| **Unit Tests (Rules/Enums)** | 3 | 20 | Validations |
| **Integration Tests** | 3 | 15 | Flows completos |
| **TOTAL** | **40** | **326** | **100%** |

---

## ✅ CHECKLIST DE COBERTURA

### Funcionalidades Core
- ✅ CRUD completo para 4 tipos de anuncios
- ✅ Sistema de scheduling con Redis
- ✅ Acciones específicas (resolve, start, complete)
- ✅ CRUD de artículos con views tracking
- ✅ Visibilidad basada en seguimiento de empresas
- ✅ Permisos por roles

### Edge Cases
- ✅ Validaciones de metadata por tipo
- ✅ Transiciones de estado inválidas
- ✅ Fechas de programación edge cases
- ✅ Unicidad de títulos por empresa
- ✅ Company ID no manipulable
- ✅ Múltiples vistas por mismo usuario

### Security
- ✅ Autenticación requerida
- ✅ Autorización por roles
- ✅ Company ownership validation
- ✅ Following status validation
- ✅ Token expiration/invalidation

### Performance
- ✅ Paginación
- ✅ Filtros eficientes
- ✅ Redis Queue para scheduling
- ✅ Índices en BD validados

---

**FIN DEL PLAN DE TESTING COMPLETO** 🎉

> **Próximo paso**: Implementar estos tests siguiendo TDD (Test-Driven Development)