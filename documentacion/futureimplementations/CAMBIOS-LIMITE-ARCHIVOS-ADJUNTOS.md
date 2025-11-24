# Guía Completa: Cambios Necesarios para Modificar el Límite de Archivos Adjuntos

## Descripción General
Este documento enumera **TODOS** los lugares en el codebase donde se requieren cambios para aumentar o disminuir el límite máximo de archivos adjuntos por ticket (actualmente: **5 archivos**).

---

## 🔴 CAMBIOS CRÍTICOS (OBLIGATORIOS)

### 1. Backend Service - FUENTE DE VERDAD ÚNICA
**Archivo:** `app/Features/TicketManagement/Services/AttachmentService.php`

#### Ubicación 1.1
- **Línea:** 30
- **Código Actual:**
  ```php
  private const MAX_ATTACHMENTS_PER_TICKET = 5;
  ```
- **Cambio Requerido:** Modificar el número `5` al nuevo límite
- **Código Nuevo (ejemplo para 10):**
  ```php
  private const MAX_ATTACHMENTS_PER_TICKET = 10;
  ```
- **Impacto:** Define el límite en toda la lógica backend
- **Criticidad:** ⚠️ CRÍTICA - Afecta la validación principal

#### Ubicación 1.2
- **Línea:** 55
- **Código Actual:**
  ```php
  if ($attachmentCount >= self::MAX_ATTACHMENTS_PER_TICKET) {
  ```
- **Cambio Requerido:** No requiere cambio directo (usa la constante)
- **Nota:** Se actualiza automáticamente al cambiar la constante en línea 30

#### Ubicación 1.3
- **Línea:** 57
- **Código Actual:**
  ```php
  "Maximum " . self::MAX_ATTACHMENTS_PER_TICKET . " attachments per ticket exceeded"
  ```
- **Cambio Requerido:** No requiere cambio directo (usa la constante)
- **Nota:** El mensaje se actualiza automáticamente con el nuevo valor

---

## 🟠 CAMBIOS EN API (CONTROLLERS)

### 2. API Controller - Respuestas HTTP y Documentación OpenAPI
**Archivo:** `app/Features/TicketManagement/Http/Controllers/TicketAttachmentController.php`

#### Ubicación 2.1 - Documentación OpenAPI (Endpoint store)
- **Línea:** 41
- **Código Actual:**
  ```php
  * - Maximum 5 attachments per ticket
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  * - Maximum 10 attachments per ticket
  ```
- **Impacto:** Documentación visible en OpenAPI specs
- **Criticidad:** 🟠 MEDIA - Afecta documentación de API

#### Ubicación 2.2 - Descripción OpenAPI
- **Línea:** 58
- **Código Actual:**
  ```php
  description: "Only 5 attachments are allowed per ticket"
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  description: "Only 10 attachments are allowed per ticket"
  ```
- **Impacto:** Especificación OpenAPI
- **Criticidad:** 🟠 MEDIA - Afecta documentación API

#### Ubicación 2.3 - Mensaje HTTP 422 (store method)
- **Línea:** 301
- **Código Actual:**
  ```php
  'errors' => ['file' => ['Maximum 5 attachments per ticket.']]
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  'errors' => ['file' => ['Maximum 10 attachments per ticket.']]
  ```
- **Impacto:** Respuesta de error a clientes
- **Criticidad:** 🟠 MEDIA - Afecta feedback al usuario

#### Ubicación 2.4 - Documentación OpenAPI (Endpoint storeToResponse)
- **Línea:** 443
- **Código Actual:**
  ```php
  description: "5 attachments max per ticket"
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  description: "10 attachments max per ticket"
  ```
- **Impacto:** Especificación OpenAPI
- **Criticidad:** 🟠 MEDIA - Afecta documentación API

#### Ubicación 2.5 - Mensaje HTTP 422 (storeToResponse method)
- **Línea:** 671
- **Código Actual:**
  ```php
  'errors' => ['file' => ['Maximum 5 attachments per ticket.']]
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  'errors' => ['file' => ['Maximum 10 attachments per ticket.']]
  ```
- **Impacto:** Respuesta de error a clientes
- **Criticidad:** 🟠 MEDIA - Afecta feedback al usuario

---

## 🟡 CAMBIOS EN FRONTEND - VALIDACIÓN JAVASCRIPT

### 3. Formulario de Creación de Tickets
**Archivo:** `resources/views/app/shared/tickets/partials/create-ticket.blade.php`

#### Ubicación 3.1 - Texto de Ayuda para Usuario
- **Línea:** 55
- **Código Actual:**
  ```html
  <small class="form-text text-muted">Máximo 10MB por archivo. Límite de 5 archivos. Formatos permitidos: PDF, imágenes, documentos Office, videos.</small>
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```html
  <small class="form-text text-muted">Máximo 10MB por archivo. Límite de 10 archivos. Formatos permitidos: PDF, imágenes, documentos Office, videos.</small>
  ```
- **Impacto:** Información visible al usuario
- **Criticidad:** 🟡 BAJA - Solo información

#### Ubicación 3.2 - Constante JavaScript
- **Línea:** 174
- **Código Actual:**
  ```javascript
  const MAX_FILES = 5;
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```javascript
  const MAX_FILES = 10;
  ```
- **Impacto:** Validación en tiempo real del lado cliente
- **Criticidad:** 🔴 CRÍTICA - Valida en frontend

#### Ubicación 3.3 - Validación de Conteo de Archivos
- **Línea:** 328
- **Código Actual:**
  ```javascript
  if (selectedFiles.length >= MAX_FILES) {
  ```
- **Cambio Requerido:** No requiere cambio directo (usa la constante)
- **Nota:** Se actualiza automáticamente al cambiar MAX_FILES en línea 174

#### Ubicación 3.4 - Mensaje de Error en Consola
- **Línea:** 330
- **Código Actual:**
  ```javascript
  console.warn(`[Create Ticket] ❌ Límite alcanzado: ${MAX_FILES} archivos máximo`);
  ```
- **Cambio Requerido:** No requiere cambio directo (usa la constante)
- **Nota:** Se actualiza automáticamente al cambiar MAX_FILES en línea 174

#### Ubicación 3.5 - Alert SweetAlert
- **Línea:** 331
- **Código Actual:**
  ```javascript
  Swal.fire('Límite alcanzado', 'Máximo 5 archivos permitidos.', 'warning');
  ```
- **Cambio Requerido:** Actualizar el número en la cadena de texto
- **Código Nuevo (ejemplo para 10):**
  ```javascript
  Swal.fire('Límite alcanzado', 'Máximo 10 archivos permitidos.', 'warning');
  ```
- **Impacto:** Mensaje de error visual al usuario
- **Criticidad:** 🟡 BAJA - Solo feedback de UI

#### Ubicación 3.6 - Log de Validación
- **Línea:** 354
- **Código Actual:**
  ```javascript
  console.log(`[Create Ticket] ✓ Archivo validado y agregado. Total: ${selectedFiles.length}/${MAX_FILES}`);
  ```
- **Cambio Requerido:** No requiere cambio directo (usa la constante)
- **Nota:** Se actualiza automáticamente al cambiar MAX_FILES en línea 174

---

### 4. Componente de Chat/Respuesta de Tickets
**Archivo:** `resources/views/components/ticket-chat.blade.php`

#### Ubicación 4.1 - Validación de Conteo de Archivos
- **Línea:** 135
- **Código Actual:**
  ```javascript
  if (selectedFiles.length + files.length > 5) {
  ```
- **Cambio Requerido:** Actualizar el número `5` al nuevo límite
- **Código Nuevo (ejemplo para 10):**
  ```javascript
  if (selectedFiles.length + files.length > 10) {
  ```
- **Impacto:** Validación en tiempo real en el chat
- **Criticidad:** 🔴 CRÍTICA - Valida en frontend

#### Ubicación 4.2 - Toast de Error
- **Línea:** 139
- **Código Actual:**
  ```javascript
  body: 'Máximo 5 archivos permitidos.'
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```javascript
  body: 'Máximo 10 archivos permitidos.'
  ```
- **Impacto:** Notificación de error al usuario
- **Criticidad:** 🟡 BAJA - Solo feedback de UI

---

## 🟢 CAMBIOS EN TESTING

### 5. Tests de Carga de Archivos
**Archivo:** `tests/Feature/TicketManagement/Attachments/UploadAttachmentTest.php`

#### Ubicación 5.1 - Documentación del Test
- **Línea:** 28
- **Código Actual:**
  ```php
  * - Maximum attachments per ticket (max 5)
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  * - Maximum attachments per ticket (max 10)
  ```
- **Impacto:** Documentación de test
- **Criticidad:** 🟢 BAJA - Solo documentación

#### Ubicación 5.2 - Documentación de Validación
- **Línea:** 39
- **Código Actual:**
  ```php
  * - 422: Validation errors (file required, type not allowed, max 5 attachments)
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  * - 422: Validation errors (file required, type not allowed, max 10 attachments)
  ```
- **Impacto:** Documentación de test
- **Criticidad:** 🟢 BAJA - Solo documentación

#### Ubicación 5.3 - Documentación de Límite
- **Línea:** 57
- **Código Actual:**
  ```php
  * - Max files per ticket: 5 (total including responses)
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  * - Max files per ticket: 10 (total including responses)
  ```
- **Impacto:** Documentación de test
- **Criticidad:** 🟢 BAJA - Solo documentación

#### Ubicación 5.4 - Método del Test
- **Línea:** 421-430
- **Código Actual:**
  ```php
  public function validates_max_5_attachments_per_ticket(): void
  ```
- **Cambio Requerido:** Actualizar nombre del método y lógica
- **Código Nuevo (ejemplo para 10):**
  ```php
  public function validates_max_10_attachments_per_ticket(): void
  ```
- **Nota:** El test debe crear N+1 archivos (11 en este ejemplo) para validar el rechazo
- **Impacto:** Lógica de test
- **Criticidad:** 🔴 CRÍTICA - Test debe validar el nuevo límite

#### Ubicación 5.5 - Comentario de Creación de Archivos
- **Línea:** 449
- **Código Actual:**
  ```php
  // Create 5 attachments (max allowed)
  ```
- **Cambio Requerido:** Actualizar el número y crear loop correspondiente
- **Código Nuevo (ejemplo para 10):**
  ```php
  // Create 10 attachments (max allowed)
  ```
- **Impacto:** Lógica de test
- **Criticidad:** 🔴 CRÍTICA - Test debe validar el nuevo límite

#### Ubicación 5.6 - Assertions
- **Línea:** 458, 472
- **Código Actual:**
  ```php
  // Debe haber exactamente 5 attachments
  ```
- **Cambio Requerido:** Actualizar assertions al nuevo número
- **Código Nuevo (ejemplo para 10):**
  ```php
  // Debe haber exactamente 10 attachments
  ```
- **Impacto:** Validación de test
- **Criticidad:** 🔴 CRÍTICA - Assertions deben ser correctas

---

### 6. Tests de Carga de Archivos en Respuestas
**Archivo:** `tests/Feature/TicketManagement/Attachments/UploadAttachmentToResponseTest.php`

#### Ubicación 6.1 - Documentación del Test
- **Línea:** 32
- **Código Actual:**
  ```php
  * - Max 5 attachments applies to entire ticket (ticket + responses combined)
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  * - Max 10 attachments applies to entire ticket (ticket + responses combined)
  ```
- **Impacto:** Documentación de test
- **Criticidad:** 🟢 BAJA - Solo documentación

#### Ubicación 6.2 - Método del Test
- **Línea:** 362
- **Código Actual:**
  ```php
  public function max_5_attachments_applies_to_entire_ticket(): void
  ```
- **Cambio Requerido:** Actualizar nombre del método
- **Código Nuevo (ejemplo para 10):**
  ```php
  public function max_10_attachments_applies_to_entire_ticket(): void
  ```
- **Impacto:** Lógica de test
- **Criticidad:** 🔴 CRÍTICA - Test debe validar el nuevo límite

#### Ubicación 6.3 - Comentario de Carga
- **Línea:** 395
- **Código Actual:**
  ```php
  // Upload 2 attachments to response (total = 5)
  ```
- **Cambio Requerido:** Actualizar el comentario y la lógica
- **Código Nuevo (ejemplo para 10):**
  ```php
  // Upload 5 attachments to response (total = 10)
  ```
- **Impacto:** Lógica de test
- **Criticidad:** 🔴 CRÍTICA - Test debe validar el nuevo límite

---

### 7. Tests de Estructura de Archivos
**Archivo:** `tests/Feature/TicketManagement/Attachments/AttachmentStructureTest.php`

#### Ubicación 7.1 - Documentación Esperada
- **Línea:** 209-210
- **Código Actual:**
  ```
  Expected: All 5 attachments should be created successfully
  Database: Should persist 5 attachments with same ticket_id
  ```
- **Cambio Requerido:** Actualizar los números
- **Código Nuevo (ejemplo para 10):**
  ```
  Expected: All 10 attachments should be created successfully
  Database: Should persist 10 attachments with same ticket_id
  ```
- **Impacto:** Documentación de test
- **Criticidad:** 🟢 BAJA - Solo documentación

#### Ubicación 7.2 - Comentario de Creación
- **Línea:** 231
- **Código Actual:**
  ```php
  // Create 5 attachments for the SAME ticket
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  // Create 10 attachments for the SAME ticket
  ```
- **Impacto:** Lógica de test
- **Criticidad:** 🔴 CRÍTICA - Loop de creación debe ajustarse

#### Ubicación 7.3 - Assertion de Conteo
- **Línea:** 267
- **Código Actual:**
  ```php
  $this->assertCount(5, $ticket->attachments);
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```php
  $this->assertCount(10, $ticket->attachments);
  ```
- **Impacto:** Validación de test
- **Criticidad:** 🔴 CRÍTICA - Assertion debe ser correcta

---

### 8. Tests de Flujo Completo
**Archivo:** `tests/Feature/TicketManagement/Integration/CompleteTicketFlowTest.php`

#### Ubicación 8.1 - Documentación del Test
- **Línea:** 253
- **Código Actual:**
  ```php
  * 5. attachment_count increases correctly
  ```
- **Cambio Requerido:** No requiere cambio (es solo un número secuencial del test)
- **Nota:** Revisar si la lógica del test valida el límite de 5 y ajustar si es necesario
- **Impacto:** Documentación de test
- **Criticidad:** 🟢 BAJA - Depende de la lógica específica

---

## 🔵 CAMBIOS EN REGLAS DE DESARROLLO

### 9. Cursor Rules - Patrones de Referencia
**Archivo:** `.cursor/rules/adminlte-file-uploads.mdc`

#### Ubicación 9.1 - Ejemplo de Implementación
- **Línea:** 333
- **Código Actual:**
  ```javascript
  const MAX_FILES = 5;
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```javascript
  const MAX_FILES = 10;
  ```
- **Impacto:** Patrón de referencia para desarrolladores
- **Criticidad:** 🟡 BAJA - Solo referencia

#### Ubicación 9.2 - Comentario de Validación
- **Línea:** 358
- **Código Actual:**
  ```javascript
  console.warn(`[Component] ❌ Límite alcanzado: ${MAX_FILES} archivos`);
  ```
- **Cambio Requerido:** No requiere cambio directo (usa la constante)
- **Nota:** Se actualiza automáticamente al cambiar MAX_FILES en línea 333
- **Impacto:** Patrón de referencia
- **Criticidad:** 🟢 BAJA - Solo referencia

---

## 📚 CAMBIOS EN DOCUMENTACIÓN

### 10. Documentación de Mapeo de Features
**Archivo:** `documentacion/tickets-feature-maping.md`

#### Ubicación 10.1 - Configuración JSON
- **Línea:** 2051-2052
- **Código Actual:**
  ```json
  "max_attachments": 5,
  "current_attachments": 5
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```json
  "max_attachments": 10,
  "current_attachments": 10
  ```
- **Impacto:** Documentación de especificaciones
- **Criticidad:** 🟢 BAJA - Solo documentación

---

### 11. Documentación de Implementación Backend
**Archivo:** `documentacion/IMPLEMENTACION-TICKET-MANAGEMENT-BACKEND.md`

#### Ubicación 11.1 - Referencias al Límite
- **Búsqueda:** Todas las referencias a `MAX_ATTACHMENTS_PER_TICKET = 5`
- **Cambio Requerido:** Actualizar todos los números
- **Impacto:** Documentación de implementación
- **Criticidad:** 🟢 BAJA - Solo documentación

---

### 12. Plan de Implementación TDD
**Archivo:** `documentacion/PLAN-IMPLEMENTACION-TICKETS-TDD.md`

#### Ubicación 12.1 - Referencias al Límite
- **Búsqueda:** Todas las referencias a `private const MAX_ATTACHMENTS_PER_TICKET = 5;`
- **Cambio Requerido:** Actualizar todos los números
- **Impacto:** Plan de desarrollo
- **Criticidad:** 🟢 BAJA - Solo documentación

---

### 13. Plan de Tests TDD
**Archivo:** `documentacion/Tickets-tests-TDD-plan.md`

#### Ubicación 13.1 - Casos de Test
- **Búsqueda:** Todas las referencias a max 5 attachments
- **Cambio Requerido:** Actualizar todos los números
- **Impacto:** Plan de testing
- **Criticidad:** 🟢 BAJA - Solo documentación

---

### 14. Documentación de Cambios en Tests
**Archivo:** `documentacion/CAMBIOS-EN-TESTS.md`

#### Ubicación 14.1 - Referencias a Tests
- **Búsqueda:** Métodos `validates_max_5_attachments_per_ticket` y `max_5_attachments_applies_to_entire_ticket`
- **Cambio Requerido:** Actualizar todas las referencias
- **Impacto:** Documentación de cambios
- **Criticidad:** 🟢 BAJA - Solo documentación

---

### 15. Plan de Feature Tests
**Archivo:** `documentacion/PLAN-IMPLEMENTACION-FEATURE-TESTS.md`

#### Ubicación 15.1 - Requerimientos
- **Búsqueda:** "Validar max 5 attachments por ticket"
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```
  Validar max 10 attachments por ticket
  ```
- **Impacto:** Plan de testing
- **Criticidad:** 🟢 BAJA - Solo documentación

---

### 16. Documentación de Endpoints
**Archivo:** `documentacion/ticketsentpoints.txt`

#### Ubicación 16.1 - Línea 978
- **Código Actual:**
  ```
  Only 5 attachments are allowed per ticket
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```
  Only 10 attachments are allowed per ticket
  ```
- **Impacto:** Documentación de API
- **Criticidad:** 🟢 BAJA - Solo documentación

#### Ubicación 16.2 - Línea 1120
- **Código Actual:**
  ```
  5 attachments max per ticket
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```
  10 attachments max per ticket
  ```
- **Impacto:** Documentación de API
- **Criticidad:** 🟢 BAJA - Solo documentación

---

### 17. Documentación de Diseño de Test
**Archivo:** `resources/views/tests/create-ticket-design.blade.php`

#### Ubicación 17.1 - Texto de Ayuda
- **Línea:** 212
- **Código Actual:**
  ```html
  Máximo 5 archivos de 10MB cada uno...
  ```
- **Cambio Requerido:** Actualizar el número
- **Código Nuevo (ejemplo para 10):**
  ```html
  Máximo 10 archivos de 10MB cada uno...
  ```
- **Impacto:** Vista de test/diseño
- **Criticidad:** 🟢 BAJA - Solo test view

---

## 📋 RESUMEN DE CAMBIOS POR PRIORIDAD

### 🔴 CRÍTICA (Deben cambiarse SIEMPRE)
1. **AttachmentService.php:30** - Constante principal
2. **create-ticket.blade.php:174** - Constante MAX_FILES (formulario)
3. **ticket-chat.blade.php:135** - Validación de conteo en chat
4. **TicketAttachmentController.php:301** - Respuesta de error HTTP
5. **TicketAttachmentController.php:671** - Respuesta de error HTTP
6. **Tests - Métodos principales** - Deben validar el nuevo límite

### 🟠 MEDIA (Documentación de API/Usuario)
1. **TicketAttachmentController.php:41** - OpenAPI doc
2. **TicketAttachmentController.php:58** - OpenAPI description
3. **TicketAttachmentController.php:443** - OpenAPI description
4. **create-ticket.blade.php:55** - Texto de ayuda para usuario
5. **ticket-chat.blade.php:139** - Toast de error

### 🟡 BAJA (Comentarios y documentación)
1. **Todos los comentarios en tests**
2. **Todas las referencias en archivos .md de documentación**
3. **Cursor rules**
4. **Vistas de test**

---

## ✅ CHECKLIST DE CAMBIOS

```markdown
## Para cambiar el límite de 5 a X archivos:

### Cambios Críticos (OBLIGATORIOS)
- [ ] AttachmentService.php línea 30
- [ ] create-ticket.blade.php línea 174
- [ ] ticket-chat.blade.php línea 135
- [ ] TicketAttachmentController.php línea 301
- [ ] TicketAttachmentController.php línea 671
- [ ] Actualizar lógica de tests (archivo por archivo)

### Cambios de Mensajes/UI
- [ ] TicketAttachmentController.php línea 41
- [ ] TicketAttachmentController.php línea 58
- [ ] TicketAttachmentController.php línea 443
- [ ] create-ticket.blade.php línea 55
- [ ] create-ticket.blade.php línea 331
- [ ] ticket-chat.blade.php línea 139

### Cambios en Tests
- [ ] UploadAttachmentTest.php (líneas 28, 39, 57, 421-430, 449, 458, 472)
- [ ] UploadAttachmentToResponseTest.php (líneas 32, 362, 395)
- [ ] AttachmentStructureTest.php (líneas 209-210, 231, 267)
- [ ] CompleteTicketFlowTest.php (revisar línea 253)

### Cambios en Documentación
- [ ] tickets-feature-maping.md
- [ ] IMPLEMENTACION-TICKET-MANAGEMENT-BACKEND.md
- [ ] PLAN-IMPLEMENTACION-TICKETS-TDD.md
- [ ] Tickets-tests-TDD-plan.md
- [ ] CAMBIOS-EN-TESTS.md
- [ ] PLAN-IMPLEMENTACION-FEATURE-TESTS.md
- [ ] ticketsentpoints.txt
- [ ] .cursor/rules/adminlte-file-uploads.mdc
- [ ] create-ticket-design.blade.php

### Post-Cambios
- [ ] Ejecutar todos los tests: `php artisan test`
- [ ] Regenerar documentación API: `php artisan l5-swagger:generate`
- [ ] Verificar validación frontend en navegador
- [ ] Verificar validación backend con API
```

---

## 🚀 ORDEN RECOMENDADO DE CAMBIOS

1. **Primero:** Cambiar `AttachmentService.php` línea 30 (fuente de verdad)
2. **Segundo:** Cambiar validaciones en formularios (create-ticket.blade.php y ticket-chat.blade.php)
3. **Tercero:** Cambiar respuestas de API (TicketAttachmentController.php)
4. **Cuarto:** Actualizar y ejecutar tests
5. **Quinto:** Regenerar documentación (OpenAPI, etc.)
6. **Sexto:** Actualizar archivos .md de documentación
7. **Séptimo:** Verificar funcionamiento completo

---

## ⚠️ NOTAS IMPORTANTES

- El cambio en `AttachmentService.php:30` es la **fuente de verdad única** - todos los demás cambios dependen de este
- Los tests **DEBEN** ser actualizados correctamente para validar el nuevo límite, no solo cambiar los números
- La validación frontend es **importante** pero NO es suficiente - el backend debe siempre validar
- La API documentación se regenera automáticamente si se actualiza correctamente el OpenAPI en el controller
- No olvides correr `php artisan test` después de cualquier cambio para asegurar que todo funciona

---

## 📞 REFERENCIAS RÁPIDAS

| Componente | Archivo | Línea(s) | Cambio |
|---|---|---|---|
| **Backend Service** | AttachmentService.php | 30 | Constante MAX_ATTACHMENTS_PER_TICKET |
| **Frontend Ticket** | create-ticket.blade.php | 55, 174, 331 | MAX_FILES constante y mensajes |
| **Frontend Chat** | ticket-chat.blade.php | 135, 139 | Validación y toast |
| **API Responses** | TicketAttachmentController.php | 41, 58, 301, 443, 671 | Documentación y errores |
| **Tests Upload** | UploadAttachmentTest.php | Múltiples | Lógica y assertions |
| **Tests Response** | UploadAttachmentToResponseTest.php | Múltiples | Lógica y assertions |
| **Tests Structure** | AttachmentStructureTest.php | Múltiples | Lógica y assertions |
