# Refactorización - Vista de Gestión de Anuncios

## RESUMEN EJECUTIVO

Refactorizar completamente la vista de gestión de anuncios (`manage.blade.php`) para seguir al 100% las guías de AdminLTE v3, mejorar la UX, y agregar funcionalidades faltantes.

---

## PROBLEMAS IDENTIFICADOS

1. ❌ **Tarjetas de estadísticas**: Usan `info-box` con `bg-light` (no resaltan)
2. ❌ **Falta tab "Publicados"**: Solo hay Borradores, Programados y Archivados
3. ❌ **No usa DataTables**: Lista simple sin búsqueda/ordenamiento/paginación avanzada
4. ❌ **Estilos custom innecesarios**: No aprovecha componentes nativos AdminLTE
5. ❌ **Formulario incompleto**: Faltan campos de metadata según schema
6. ❌ **No hay modal de edición**: Solo dice "en desarrollo"
7. ❌ **No hay modal de vista**: Solo dice "en desarrollo"

---

## ENDPOINTS DE LA API

### Listado
```
GET /api/announcements
Parámetros:
- status: draft|scheduled|published|archived
- type: MAINTENANCE|INCIDENT|NEWS|ALERT
- search: string (max 100 chars)
- page: integer
- per_page: integer (default 20, max 100)

Respuesta:
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "company_id": "uuid",
      "company_name": "string",
      "author_id": "uuid",
      "author_name": "string",
      "title": "string",
      "content": "string",
      "type": "MAINTENANCE|INCIDENT|NEWS|ALERT",
      "status": "DRAFT|SCHEDULED|PUBLISHED|ARCHIVED",
      "metadata": {},
      "published_at": "datetime",
      "created_at": "datetime",
      "updated_at": "datetime"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 100,
    "last_page": 5
  }
}
```

### Obtener por ID
```
GET /api/announcements/{id}
Respuesta: Igual que item en listado
```

### Crear
```
POST /api/announcements/news (para NEWS)
POST /api/announcements/maintenance (para MAINTENANCE)
POST /api/v1/announcements/incidents (para INCIDENT)
POST /api/announcements/alerts (para ALERT)

Body varía según tipo (ver schemas más abajo)
```

### Actualizar
```
PUT /api/announcements/{id}
Body: Campos opcionales según tipo
```

### Eliminar
```
DELETE /api/announcements/{id}
Solo DRAFT o ARCHIVED
```

### Acciones
```
POST /api/announcements/{id}/publish
POST /api/announcements/{id}/schedule
  Body: { "scheduled_for": "datetime" }
POST /api/announcements/{id}/unschedule
POST /api/announcements/{id}/archive
POST /api/announcements/{id}/restore
```

### Schemas
```
GET /api/announcements/schemas
Devuelve estructura completa de metadata por tipo
```

---

## SCHEMAS DE METADATA

### MAINTENANCE
**Requeridos**: urgency, scheduled_start, scheduled_end, is_emergency
**Opcionales**: actual_start, actual_end, affected_services

**Campos**:
- urgency: enum [LOW, MEDIUM, HIGH]
- scheduled_start: datetime
- scheduled_end: datetime
- is_emergency: boolean
- affected_services: array

### INCIDENT
**Requeridos**: urgency, is_resolved, started_at
**Opcionales**: ended_at, resolution_content, affected_services

**Campos**:
- urgency: enum [LOW, MEDIUM, HIGH, CRITICAL]
- is_resolved: boolean
- started_at: datetime
- ended_at: datetime (nullable)
- resolution_content: string
- affected_services: array

### NEWS
**Requeridos**: news_type, target_audience, summary
**Opcionales**: call_to_action

**Campos**:
- news_type: enum [feature_release, policy_update, general_update]
- target_audience: array [users, agents, admins]
- summary: string
- call_to_action: object { text: string, url: string (https) }

### ALERT
**Requeridos**: urgency, alert_type, message, action_required, started_at
**Opcionales**: action_description, ended_at, affected_services

**Campos**:
- urgency: enum [HIGH, CRITICAL]
- alert_type: enum [security, system, service, compliance]
- message: string
- action_required: boolean
- action_description: string (nullable, required if action_required=true)
- started_at: datetime
- ended_at: datetime (nullable)
- affected_services: array (nullable)

---

## CAMBIOS A IMPLEMENTAR

### 1. REEMPLAZAR TARJETAS DE ESTADÍSTICAS

#### Antes (info-box con bg-light):
```blade
<div class="info-box">
    <span class="info-box-icon bg-light"><i class="fas fa-edit"></i></span>
    <div class="info-box-content">
        <span class="info-box-text">Borradores</span>
        <span class="info-box-number" id="stat-drafts">-</span>
    </div>
</div>
```

#### Después (small-box con colores):
```blade
<x-adminlte-small-box
    title="Borradores"
    text="-"
    icon="fas fa-edit"
    theme="secondary"
    id="stat-drafts"
/>

<x-adminlte-small-box
    title="Programados"
    text="-"
    icon="fas fa-calendar-alt"
    theme="info"
    id="stat-scheduled"
/>

<x-adminlte-small-box
    title="Publicados"
    text="-"
    icon="fas fa-check-circle"
    theme="success"
    id="stat-published"
/>

<x-adminlte-small-box
    title="Archivados"
    text="-"
    icon="fas fa-archive"
    theme="dark"
    id="stat-archived"
/>
```

**JavaScript para actualizar**:
```javascript
// Acceder al componente
const smallBox = document.querySelector('[data-widget="small-box"]');
// Actualizar usando método update de AdminLTE
```

---

### 2. AGREGAR TAB "PUBLICADOS"

**Ubicación**: Entre "Programados" y "Archivados"

```blade
<li class="nav-item">
    <a class="nav-link" id="tab-published-tab" data-toggle="tab" href="#tab-published" role="tab">
        <i class="fas fa-check-circle mr-1"></i>
        Publicados
        <span class="badge badge-success ml-1" id="badge-published">0</span>
    </a>
</li>
```

**Tab content**:
```blade
<div class="tab-pane fade" id="tab-published" role="tabpanel">
    <table id="table-published" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Título</th>
                <th>Fecha Publicación</th>
                <th>Urgencia</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
```

**Acciones para Publicados**: Ver | Archivar

---

### 3. REEMPLAZAR LISTAS POR DATATABLES

Para cada tab (Borradores, Programados, Publicados, Archivados):

```blade
<x-adminlte-datatable
    id="table-drafts"
    :heads="['Tipo', 'Título', 'Fecha', 'Urgencia', 'Acciones']"
    :config="[
        'responsive' => true,
        'autoWidth' => false,
        'order' => [[2, 'desc']], // Ordenar por fecha descendente
        'language' => ['url' => '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json']
    ]"
    beautify
    hoverable
    bordered
    striped
/>
```

**JavaScript para poblar**:
```javascript
function loadDraftsTable() {
    const table = $('#table-drafts').DataTable();
    table.clear();

    // Fetch data
    fetch('/api/announcements?status=draft&per_page=100', {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(data => {
        data.data.forEach(announcement => {
            table.row.add([
                getTypeBadge(announcement.type),
                announcement.title,
                formatDate(announcement.updated_at),
                announcement.metadata?.urgency || '-',
                renderActions(announcement)
            ]);
        });
        table.draw();
    });
}
```

**Helpers**:
```javascript
function getTypeBadge(type) {
    const badges = {
        'NEWS': '<span class="badge badge-info">Noticia</span>',
        'MAINTENANCE': '<span class="badge badge-warning">Mantenimiento</span>',
        'INCIDENT': '<span class="badge badge-danger">Incidente</span>',
        'ALERT': '<span class="badge badge-secondary">Alerta</span>'
    };
    return badges[type] || '';
}

function renderActions(announcement) {
    let html = '<div class="btn-group btn-group-sm">';

    if (announcement.status === 'DRAFT') {
        html += `<button class="btn btn-secondary" onclick="editAnnouncement('${announcement.id}')"><i class="fas fa-edit"></i></button>`;
        html += `<button class="btn btn-primary" onclick="publishAnnouncement('${announcement.id}')"><i class="fas fa-paper-plane"></i></button>`;
        html += `<button class="btn btn-info" onclick="scheduleAnnouncement('${announcement.id}')"><i class="fas fa-calendar"></i></button>`;
        html += `<button class="btn btn-danger" onclick="deleteAnnouncement('${announcement.id}')"><i class="fas fa-trash"></i></button>`;
    }
    // ... más condiciones

    html += '</div>';
    return html;
}
```

---

### 4. MEJORAR MODAL DE CREACIÓN

**Estructura con componentes AdminLTE**:

```blade
<x-adminlte-modal
    id="modal-create"
    title="Crear Nuevo Anuncio"
    theme="primary"
    size="lg"
    staticBackdrop
    icon="fas fa-plus"
>
    <form id="form-create">
        {{-- Tipo --}}
        <x-adminlte-select
            name="type"
            label="Tipo de Anuncio"
            icon="fas fa-tag"
            enable-old-support
        >
            <option value="">Seleccionar tipo...</option>
            <option value="NEWS">Noticia</option>
            <option value="MAINTENANCE">Mantenimiento</option>
            <option value="INCIDENT">Incidente</option>
            <option value="ALERT">Alerta</option>
        </x-adminlte-select>

        {{-- Título --}}
        <x-adminlte-input
            name="title"
            label="Título"
            placeholder="Ingrese el título del anuncio"
            icon="fas fa-heading"
        >
            <x-slot name="prependSlot">
                <span class="text-danger">*</span>
            </x-slot>
        </x-adminlte-input>

        {{-- Contenido --}}
        <x-adminlte-textarea
            name="content"
            label="Contenido"
            rows="4"
            placeholder="Ingrese el contenido del anuncio"
        >
            <x-slot name="prependSlot">
                <span class="text-danger">*</span>
            </x-slot>
        </x-adminlte-textarea>

        {{-- Contenedor dinámico para metadata --}}
        <div id="metadata-fields"></div>
    </form>

    <x-slot name="footerSlot">
        <x-adminlte-button
            theme="secondary"
            label="Cancelar"
            data-dismiss="modal"
        />
        <x-adminlte-button
            theme="primary"
            label="Guardar como Borrador"
            icon="fas fa-save"
            id="btn-create-draft"
        />
    </x-slot>
</x-adminlte-modal>
```

**Campos dinámicos según tipo**:

```javascript
function updateMetadataFields(type) {
    const container = $('#metadata-fields');
    container.empty();

    if (type === 'MAINTENANCE') {
        container.html(`
            <div class="row">
                <div class="col-md-6">
                    <x-adminlte-input-date
                        name="scheduled_start"
                        label="Inicio Programado"
                        icon="fas fa-calendar-alt"
                    />
                </div>
                <div class="col-md-6">
                    <x-adminlte-input-date
                        name="scheduled_end"
                        label="Fin Programado"
                        icon="fas fa-calendar-alt"
                    />
                </div>
            </div>

            <x-adminlte-select name="urgency" label="Urgencia" icon="fas fa-bolt">
                <option value="LOW">Baja</option>
                <option value="MEDIUM" selected>Media</option>
                <option value="HIGH">Alta</option>
            </x-adminlte-select>

            <x-adminlte-input
                name="affected_services"
                label="Servicios Afectados"
                placeholder="API, Dashboard, etc. (separados por coma)"
                icon="fas fa-server"
            />

            <x-adminlte-input-switch
                name="is_emergency"
                label="Emergencia"
                data-on-text="Sí"
                data-off-text="No"
            />
        `);
    } else if (type === 'NEWS') {
        container.html(`
            <x-adminlte-select name="news_type" label="Tipo de Noticia" icon="fas fa-newspaper">
                <option value="general_update">Actualización General</option>
                <option value="feature_release">Nuevo Lanzamiento</option>
                <option value="policy_update">Actualización de Políticas</option>
            </x-adminlte-select>

            <x-adminlte-textarea
                name="summary"
                label="Resumen"
                rows="2"
                placeholder="Breve resumen de la noticia"
            />

            <div class="form-group">
                <label>Audiencia Objetivo</label>
                <div>
                    <div class="icheck-primary d-inline mr-3">
                        <input type="checkbox" id="aud-users" name="target_audience[]" value="users" checked>
                        <label for="aud-users">Usuarios</label>
                    </div>
                    <div class="icheck-primary d-inline mr-3">
                        <input type="checkbox" id="aud-agents" name="target_audience[]" value="agents" checked>
                        <label for="aud-agents">Agentes</label>
                    </div>
                    <div class="icheck-primary d-inline">
                        <input type="checkbox" id="aud-admins" name="target_audience[]" value="admins">
                        <label for="aud-admins">Administradores</label>
                    </div>
                </div>
            </div>

            <x-adminlte-input
                name="cta_text"
                label="Texto del Botón (Opcional)"
                placeholder="Ej: Leer más"
            />

            <x-adminlte-input
                name="cta_url"
                label="URL del Botón (Opcional)"
                placeholder="https://..."
                type="url"
            />
        `);
    } else if (type === 'INCIDENT') {
        container.html(`
            <x-adminlte-select name="urgency" label="Urgencia" icon="fas fa-bolt">
                <option value="LOW">Baja</option>
                <option value="MEDIUM">Media</option>
                <option value="HIGH" selected>Alta</option>
                <option value="CRITICAL">Crítica</option>
            </x-adminlte-select>

            <x-adminlte-input
                name="affected_services"
                label="Servicios Afectados"
                placeholder="API, Dashboard, etc. (separados por coma)"
                icon="fas fa-server"
            />
        `);
    } else if (type === 'ALERT') {
        container.html(`
            <x-adminlte-select name="urgency" label="Urgencia" icon="fas fa-bolt">
                <option value="HIGH" selected>Alta</option>
                <option value="CRITICAL">Crítica</option>
            </x-adminlte-select>

            <x-adminlte-select name="alert_type" label="Tipo de Alerta" icon="fas fa-exclamation-triangle">
                <option value="security">Seguridad</option>
                <option value="system">Sistema</option>
                <option value="service">Servicio</option>
                <option value="compliance">Cumplimiento</option>
            </x-adminlte-select>

            <x-adminlte-input
                name="message"
                label="Mensaje Corto"
                placeholder="Mensaje breve de la alerta"
                icon="fas fa-comment"
            />

            <x-adminlte-input-switch
                name="action_required"
                label="Acción Requerida"
                data-on-text="Sí"
                data-off-text="No"
                id="alert-action-required"
            />

            <div id="action-description-container" style="display: none;">
                <x-adminlte-textarea
                    name="action_description"
                    label="Descripción de la Acción"
                    rows="2"
                    placeholder="Describa qué debe hacer el usuario"
                />
            </div>
        `);

        // Toggle action_description based on checkbox
        $('#alert-action-required').on('change', function() {
            $('#action-description-container').toggle(this.checked);
        });
    }
}
```

---

### 5. CREAR MODAL DE EDICIÓN

Similar al de creación pero precargando datos:

```blade
<x-adminlte-modal
    id="modal-edit"
    title="Editar Anuncio"
    theme="warning"
    size="lg"
    staticBackdrop
    icon="fas fa-edit"
>
    <form id="form-edit">
        <input type="hidden" id="edit-id">

        {{-- Tipo (solo lectura) --}}
        <x-adminlte-input
            name="type_display"
            label="Tipo"
            readonly
            id="edit-type-display"
        />

        {{-- Resto de campos igual que crear --}}
        <x-adminlte-input
            name="title"
            label="Título"
            id="edit-title"
        />

        <x-adminlte-textarea
            name="content"
            label="Contenido"
            rows="4"
            id="edit-content"
        />

        <div id="edit-metadata-fields"></div>
    </form>

    <x-slot name="footerSlot">
        <x-adminlte-button theme="secondary" label="Cancelar" data-dismiss="modal" />
        <x-adminlte-button theme="warning" label="Actualizar" icon="fas fa-save" id="btn-update" />
    </x-slot>
</x-adminlte-modal>
```

**JavaScript para cargar datos**:
```javascript
function editAnnouncement(id) {
    fetch(`/api/announcements/${id}`, {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(data => {
        const announcement = data.data;

        // Cargar campos básicos
        $('#edit-id').val(announcement.id);
        $('#edit-type-display').val(getTypeLabel(announcement.type));
        $('#edit-title').val(announcement.title);
        $('#edit-content').val(announcement.content);

        // Cargar metadata según tipo
        loadEditMetadata(announcement.type, announcement.metadata);

        // Mostrar modal
        $('#modal-edit').modal('show');
    });
}

function loadEditMetadata(type, metadata) {
    // Similar a updateMetadataFields pero con valores precargados
    const container = $('#edit-metadata-fields');
    // ... poblar con valores de metadata
}
```

---

### 6. CREAR MODAL DE VISTA (SOLO LECTURA)

```blade
<x-adminlte-modal
    id="modal-view"
    title="Detalle del Anuncio"
    theme="info"
    size="lg"
    icon="fas fa-eye"
>
    <div id="view-content">
        {{-- Información básica --}}
        <dl class="row">
            <dt class="col-sm-3">Tipo:</dt>
            <dd class="col-sm-9"><span id="view-type"></span></dd>

            <dt class="col-sm-3">Título:</dt>
            <dd class="col-sm-9" id="view-title"></dd>

            <dt class="col-sm-3">Estado:</dt>
            <dd class="col-sm-9"><span id="view-status"></span></dd>

            <dt class="col-sm-3">Autor:</dt>
            <dd class="col-sm-9" id="view-author"></dd>

            <dt class="col-sm-3">Fecha Creación:</dt>
            <dd class="col-sm-9" id="view-created"></dd>
        </dl>

        <hr>

        <h5>Contenido</h5>
        <p id="view-content-text"></p>

        <hr>

        <h5>Metadatos</h5>
        <div id="view-metadata"></div>
    </div>

    <x-slot name="footerSlot">
        <x-adminlte-button theme="secondary" label="Cerrar" data-dismiss="modal" />
    </x-slot>
</x-adminlte-modal>
```

**JavaScript**:
```javascript
function viewAnnouncement(id) {
    fetch(`/api/announcements/${id}`, {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(res => res.json())
    .then(data => {
        const a = data.data;

        $('#view-type').html(getTypeBadge(a.type));
        $('#view-title').text(a.title);
        $('#view-status').html(getStatusBadge(a.status));
        $('#view-author').text(a.author_name);
        $('#view-created').text(formatDateTime(a.created_at));
        $('#view-content-text').text(a.content);

        // Renderizar metadata según tipo
        renderViewMetadata(a.type, a.metadata);

        $('#modal-view').modal('show');
    });
}

function renderViewMetadata(type, metadata) {
    const container = $('#view-metadata');
    let html = '';

    if (type === 'MAINTENANCE') {
        html += `<dl class="row">`;
        html += `<dt class="col-sm-4">Urgencia:</dt><dd class="col-sm-8">${metadata.urgency}</dd>`;
        html += `<dt class="col-sm-4">Inicio Programado:</dt><dd class="col-sm-8">${formatDateTime(metadata.scheduled_start)}</dd>`;
        html += `<dt class="col-sm-4">Fin Programado:</dt><dd class="col-sm-8">${formatDateTime(metadata.scheduled_end)}</dd>`;
        html += `<dt class="col-sm-4">Emergencia:</dt><dd class="col-sm-8">${metadata.is_emergency ? 'Sí' : 'No'}</dd>`;
        if (metadata.affected_services?.length) {
            html += `<dt class="col-sm-4">Servicios Afectados:</dt><dd class="col-sm-8">${metadata.affected_services.join(', ')}</dd>`;
        }
        html += `</dl>`;
    }
    // ... más tipos

    container.html(html);
}
```

---

### 7. ELIMINAR ESTILOS CUSTOM

**Eliminar del @section('css')**:
- `.announcement-item`
- `.announcement-item:hover`
- `.announcement-item h6`
- `.announcement-item .meta`
- `.announcement-item .actions`
- `.badge-type`
- `.empty-state`
- Ajustes de info-box

**Mantener solo**:
- `.text-purple` (si se usa)
- `.bg-purple` (si se usa)

---

## PASOS DE IMPLEMENTACIÓN

### Paso 1: Actualizar Tarjetas de Estadísticas
- [ ] Reemplazar info-box por small-box
- [ ] Actualizar JavaScript para usar métodos de AdminLTE
- [ ] Probar actualización dinámica

### Paso 2: Agregar Tab Publicados
- [ ] Agregar tab en HTML
- [ ] Crear DataTable para publicados
- [ ] Implementar loadPublished()
- [ ] Actualizar loadStatistics()

### Paso 3: Implementar DataTables en todos los tabs
- [ ] Reemplazar divs por tables con x-adminlte-datatable
- [ ] Actualizar loadDrafts() para usar DataTable API
- [ ] Actualizar loadScheduled() para usar DataTable API
- [ ] Actualizar loadArchived() para usar DataTable API
- [ ] Implementar loadPublished() con DataTable

### Paso 4: Mejorar Modal de Creación
- [ ] Reemplazar HTML por componentes x-adminlte-*
- [ ] Actualizar updateMetadataFields() con componentes
- [ ] Implementar validación visual
- [ ] Actualizar createDraft() para nuevos campos

### Paso 5: Implementar Modal de Edición
- [ ] Crear modal-edit con componentes AdminLTE
- [ ] Implementar editAnnouncement(id)
- [ ] Implementar loadEditMetadata()
- [ ] Implementar updateAnnouncement()

### Paso 6: Implementar Modal de Vista
- [ ] Crear modal-view
- [ ] Implementar viewAnnouncement(id)
- [ ] Implementar renderViewMetadata()

### Paso 7: Limpieza
- [ ] Eliminar estilos custom
- [ ] Eliminar código comentado
- [ ] Verificar que todo usa componentes AdminLTE
- [ ] Probar todas las funcionalidades

---

## HELPERS ÚTILES

```javascript
// Formato de fechas
function formatDateTime(datetime) {
    if (!datetime) return '-';
    const d = new Date(datetime);
    return d.toLocaleDateString('es-ES') + ' ' + d.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Badges de tipo
function getTypeBadge(type) {
    const badges = {
        'NEWS': '<span class="badge badge-info">Noticia</span>',
        'MAINTENANCE': '<span class="badge badge-warning">Mantenimiento</span>',
        'INCIDENT': '<span class="badge badge-danger">Incidente</span>',
        'ALERT': '<span class="badge badge-secondary">Alerta</span>'
    };
    return badges[type] || '';
}

// Badges de estado
function getStatusBadge(status) {
    const badges = {
        'DRAFT': '<span class="badge badge-secondary">Borrador</span>',
        'SCHEDULED': '<span class="badge badge-info">Programado</span>',
        'PUBLISHED': '<span class="badge badge-success">Publicado</span>',
        'ARCHIVED': '<span class="badge badge-dark">Archivado</span>'
    };
    return badges[status] || '';
}

// Label de tipo
function getTypeLabel(type) {
    const labels = {
        'NEWS': 'Noticia',
        'MAINTENANCE': 'Mantenimiento',
        'INCIDENT': 'Incidente',
        'ALERT': 'Alerta'
    };
    return labels[type] || type;
}

// Formato ISO8601 para Laravel (con +00:00)
function toISO8601(date) {
    const d = new Date(date);
    const year = d.getUTCFullYear();
    const month = String(d.getUTCMonth() + 1).padStart(2, '0');
    const day = String(d.getUTCDate()).padStart(2, '0');
    const hours = String(d.getUTCHours()).padStart(2, '0');
    const minutes = String(d.getUTCMinutes()).padStart(2, '0');
    const seconds = String(d.getUTCSeconds()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}+00:00`;
}
```

---

## ESTRUCTURA FINAL

```
[4 Small-Boxes con colores] ← Borradores | Programados | Publicados | Archivados

[Card con Tabs]
├─ Tab: Borradores (DataTable)
│  └─ Acciones: Editar | Publicar | Programar | Eliminar
├─ Tab: Programados (DataTable)
│  └─ Acciones: Ver | Editar | Desprogramar | Publicar Ahora
├─ Tab: Publicados (DataTable) ← NUEVO
│  └─ Acciones: Ver | Archivar
└─ Tab: Archivados (DataTable)
   └─ Acciones: Ver | Restaurar | Eliminar

[Modales]
├─ Modal Crear (mejorado con componentes AdminLTE)
├─ Modal Editar (NUEVO)
├─ Modal Ver (NUEVO)
└─ Modal Programar (existente)
```

---

## NOTAS IMPORTANTES

1. **Formato de fechas**: Laravel espera `Y-m-d\TH:i:sP` (ej: `2025-11-19T08:00:00+00:00`), NO acepta `Z`
2. **DataTables**: Usar configuración en español desde CDN
3. **Validación**: Usar clases de Bootstrap (`is-invalid`, `is-valid`)
4. **Toastr**: AdminLTE incluye toastr, usarlo para notificaciones
5. **Target audience**: En NEWS es array de checkboxes múltiples
6. **Call to action**: En NEWS es opcional, si se proporciona requiere text Y url (https)
7. **Action description**: En ALERT es requerido solo si action_required=true

---

## TESTING

Probar cada funcionalidad:
- [ ] Crear anuncio de cada tipo (NEWS, MAINTENANCE, INCIDENT, ALERT)
- [ ] Editar borradores
- [ ] Publicar borradores
- [ ] Programar borradores
- [ ] Desprogramar programados
- [ ] Ver publicados
- [ ] Archivar publicados
- [ ] Restaurar archivados
- [ ] Eliminar borradores/archivados
- [ ] Actualización de estadísticas en tiempo real
- [ ] DataTables: búsqueda, ordenamiento, paginación
- [ ] Validación de formularios
- [ ] Formato de fechas correcto

---

FIN DEL DOCUMENTO
