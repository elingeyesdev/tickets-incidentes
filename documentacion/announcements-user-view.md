# Vista de Anuncios para Rol USER - Prompt Mejorado

## 📋 FASE 1: Análisis de Modelos y Estructura Base

### ✅ Modelo Announcement - Encontrado
**Ubicación**: `app/Features/ContentManagement/Models/Announcement.php`

**Tabla BD**: `company_announcements`

**Atributos Principales**:
- `id` (UUID)
- `company_id` (FK a companies)
- `author_id` (FK a users - quien crea el anuncio)
- `title` - Título del anuncio
- `content` - Contenido principal
- `type` - Tipo de anuncio (enum: MAINTENANCE, INCIDENT, NEWS, ALERT)
- `status` - Estado (enum: DRAFT, SCHEDULED, PUBLISHED, ARCHIVED)
- `metadata` - JSON Array con datos específicos según tipo
- `published_at` - Fecha de publicación
- `created_at`, `updated_at`

**Relaciones Modelo**:
- `belongsTo(Company)` - Empresa propietaria
- `belongsTo(User)` - Autor del anuncio

**Métodos Útiles**:
- `scopePublished()` - Filtra anuncios publicados
- `isEditable()` - Verifica si es editable (DRAFT o SCHEDULED)
- `formattedUrgency()` - Convierte urgencia a string localizado

---

## 📋 FASE 2: Enums y Tipos de Anuncios

### ✅ AnnouncementType Enum
**Ubicación**: `app/Features/ContentManagement/Enums/AnnouncementType.php`

**Tipos Disponibles**:
1. **MAINTENANCE**: Mantenimiento programado
   - Requeridos: `urgency`, `scheduled_start`, `scheduled_end`, `is_emergency`
   - Opcionales: `actual_start`, `actual_end`, `affected_services`

2. **INCIDENT**: Incidentes
   - Requeridos: `urgency`, `is_resolved`, `started_at`
   - Opcionales: `resolved_at`, `resolution_content`, `ended_at`, `affected_services`

3. **NEWS**: Noticias
   - Requeridos: `news_type`, `target_audience`, `summary`
   - Opcionales: `call_to_action`

4. **ALERT**: Alertas
   - Requeridos: `urgency`, `alert_type`, `message`, `action_required`, `started_at`
   - Opcionales: `action_description`, `affected_services`, `ended_at`

### ✅ PublicationStatus Enum
**Estados Posibles**:
- `DRAFT` - Borrador
- `SCHEDULED` - Programado
- `PUBLISHED` - Publicado
- `ARCHIVED` - Archivado

### ✅ UrgencyLevel Enum
**Niveles**:
- `LOW` - Bajo
- `MEDIUM` - Medio
- `HIGH` - Alto
- `CRITICAL` - Crítico

---

## 📋 FASE 3: Controladores y Endpoints de API

### ✅ AnnouncementController - Métodos Disponibles
**Ubicación**: `app/Features/ContentManagement/Http/Controllers/AnnouncementController.php`

**Base URL**: `/api/announcements`

**Endpoints** (Role-based visibility):

1. **GET /api/announcements** - Listar anuncios
   - Parámetros: `status`, `type`, `search`, `sort`, `published_after`, `published_before`, `company_id`, `page`, `per_page`
   - PLATFORM_ADMIN: ve todos de todas las empresas
   - COMPANY_ADMIN: ve todos de su empresa
   - AGENT/USER: ve solo PUBLISHED de empresas que sigue
   - Retorna: Paginated list con metadata

2. **GET /api/announcements/{id}** - Obtener anuncio específico
   - Mismas reglas de visibilidad que index
   - Retorna: Single announcement object

3. **PUT /api/announcements/{id}** - Actualizar anuncio
   - Solo COMPANY_ADMIN
   - Solo DRAFT y SCHEDULED editables
   - Excepción: PUBLISHED ALERT solo puede actualizar `ended_at`
   - Soporta campos type-specific en metadata

4. **DELETE /api/announcements/{id}** - Eliminar anuncio
   - Solo COMPANY_ADMIN
   - Solo DRAFT o ARCHIVED deletables

### ✅ Relación Usuario-Empresa: CompanyFollower
**Ubicación**: `app/Features/CompanyManagement/Models/CompanyFollower.php`

**Tabla**: `business.user_company_followers`

**Estructura**:
- `id` (UUID)
- `user_id` (FK a users)
- `company_id` (FK a companies)
- `followed_at` (datetime)

**Utilidad**:
- Los AGENT/USER solo ven anuncios de empresas que siguen
- La tabla es consulta en AnnouncementController para filtrar acceso
- Query: `DB::table('business.user_company_followers')->where('user_id', $user->id)->pluck('company_id')`

---

## 📋 FASE 4: Recursos de API y Estructura de Vistas

### ✅ AnnouncementResource - Transformación de datos
**Ubicación**: `app/Features/ContentManagement/Http/Resources/AnnouncementResource.php`

**JSON Response Structure**:
```json
{
  "id": "uuid",
  "company_id": "uuid",
  "company_name": "Nombre Empresa",
  "author_id": "uuid",
  "author_name": "Nombre Autor",
  "title": "Título del Anuncio",
  "content": "Contenido principal",
  "type": "MAINTENANCE|INCIDENT|NEWS|ALERT",
  "status": "DRAFT|SCHEDULED|PUBLISHED|ARCHIVED",
  "metadata": { /* campos específicos según tipo */ },
  "published_at": "2024-01-15T10:30:00Z",
  "created_at": "2024-01-14T15:20:00Z",
  "updated_at": "2024-01-15T10:30:00Z"
}
```

---

## 📋 FASE 5: Estructura de Vistas Existentes

### ✅ Vista Company-Admin: Anuncios
**Ubicación**: `resources/views/app/company-admin/announcements/index.blade.php`

**Estructura Implementada**:
1. **Estadísticas** - Small boxes con contadores:
   - Total Publicados
   - Incidentes Activos
   - Mantenimientos Próximos
   - Este Mes

2. **Card Principal** con:
   - Header con Filtros: Tipo (NEWS, MAINTENANCE, INCIDENT, ALERT)
   - Búsqueda por texto
   - Timeline container con anuncios
   - Paginación en footer

3. **Timeline de AdminLTE v3**:
   - Carga dinámica via JavaScript
   - Estructura: `<div class="timeline">` con items
   - Cada item tiene icono, tiempo, header y body
   - Uso de colores: info (NEWS), purple (MAINTENANCE), danger (INCIDENT), warning (ALERT)

4. **Componentes Reutilizables**:
   - Badges de urgencia
   - Iconos tipo-específicos
   - Metadata display con información adicional

---

## 📋 FASE 6: User Dashboard Existente - Timeline Template
**Ubicación**: `resources/views/app/user/dashboard.blade.php` (líneas 130-167)

**Estructura Timeline AdminLTE v3**:
```html
<div class="timeline">
  <div class="time-label">
    <span class="bg-info">Today</span>
  </div>
  <div>
    <i class="fas fa-comment bg-blue"></i>
    <div class="timeline-item">
      <span class="time"><i class="fas fa-clock"></i> 1 hour ago</span>
      <h3 class="timeline-header">Título principal</h3>
      <div class="timeline-body">Contenido del evento</div>
    </div>
  </div>
  <div>
    <i class="fas fa-clock bg-gray"></i>
  </div>
</div>
```

**Características observadas**:
- Grouping por fecha con `time-label`
- Iconos antes de cada item con background color
- Flex layout para alignment
- Timestamp y header con contenido
- Timeline-end marker (icono de reloj)

---

## 📋 FASE 7: Rutas Web y Estructura del Sidebar

### ✅ Rutas Actuales (web.php)
**Ubicación**: `routes/web.php`

**Rutas USER (middleware role:USER, prefix: user)**:
```
GET  /app/user/dashboard       -> UserController@dashboard  (name: dashboard.user)
GET  /app/user/tickets         -> view tickets.index       (name: user.tickets.index)
GET  /app/user/tickets/manage  -> view tickets.manage      (name: user.tickets.manage)
```

**Rutas Company-Admin (middleware role:COMPANY_ADMIN, prefix: company)**:
```
GET  /app/company/announcements        -> view announcements.index  (name: company.announcements.index)
GET  /app/company/announcements/manage -> view announcements.manage (name: company.announcements.manage)
```

**Nota**: La ruta `/app/user/announcements` NO existe aún pero está referenciada en el sidebar.

---

### ✅ Sidebar Navigation Structure
**Ubicación**: `resources/views/app/shared/sidebar.blade.php`

**USER Menu (líneas 147-177)**:
```blade
<!-- User Menu -->
<template x-if="activeRole === 'USER'">
    <li class="nav-header">SOPORTE</li>
    <li><a href="/app/user/tickets">Mis Tickets</a></li>

    <li class="nav-header">INFORMACIÓN</li>
    <li><a href="/app/user/announcements">Anuncios</a></li>  <!-- ← ESTE ENLACE YA EXISTE -->
    <li><a href="/app/user/help-center">Centro de Ayuda</a></li>

    <li class="nav-header">CUENTA</li>
    <li><a href="/profile">Perfil</a></li>
</template>
```

**Observación**: El menú ya tiene el enlace a Anuncios, solo falta crear la ruta y vista.

---

## 📋 FASE 8: Renderizado Dinámico - Company-Admin Implementation

### ✅ Arquitectura de Renderizado Dinámico
**Ubicación**: `resources/views/app/company-admin/announcements/index.blade.php` (líneas 275-388)

**Flujo Principal**:
1. **loadAnnouncements()** - Obtiene datos de API
   - URL: `/api/announcements?status=published&per_page=10&page=X`
   - Parámetros: type (filtro), search (búsqueda), página
   - Headers: `Authorization: Bearer ${token}`

2. **renderTimeline()** - Transforma datos en HTML
   - Agrupa por fecha con `time-label`
   - Alterna colores de fechas: bg-red, bg-green, bg-blue, bg-yellow
   - Para cada anuncio:
     - Icono type-specific con fondo
     - Timestamp formateado (HH:MM)
     - Status badges dinámicos
     - Header con tipo + título
     - Body con contenido
     - Metadata renderizada
     - Footer con botones de acción

3. **getTypeConfig()** - Mapeo de tipos a iconos/colores
   ```javascript
   NEWS: {icon: 'fas fa-newspaper', bgColor: 'bg-blue', label: 'Noticia'},
   MAINTENANCE: {icon: 'fas fa-tools', bgColor: 'bg-purple', label: 'Mantenimiento'},
   INCIDENT: {icon: 'fas fa-exclamation-triangle', bgColor: 'bg-red', label: 'Incidente'},
   ALERT: {icon: 'fas fa-bell', bgColor: 'bg-yellow', label: 'Alerta'}
   ```

### ✅ Renderizado de Metadata Específica por Tipo

**MAINTENANCE**:
- Urgency (LOW/MEDIUM/HIGH) con colores
- Fechas programadas: `Programado: DD/MM/YYYY, HH:MM - HH:MM (Xh)`
- Fechas reales si inició: `Inicio real: HH:MM - HH:MM (Xh)`
- Servicios afectados: lista separada por comas

**INCIDENT**:
- Urgency (LOW/MEDIUM/HIGH/CRITICAL) con colores
- Duración: `Duración: Xmin/Xh/Xd`
- Resolución (collapsible): `<div class="collapse">` con contenido
- Servicios afectados

**NEWS**:
- Target audience: Iconos para users/agents/admins
- Summary (subtítulo): mostrado en cursiva
- Call to action: botón con `url` y `text` desde metadata

**ALERT**:
- Urgency (HIGH/CRITICAL)
- Alert type badge: security, system, service, compliance
- Message: alertbox destacada
- Action required: alert box rojo si aplica
- Active duration: si no ha finalizado
- Servicios afectados

### ✅ Status Badges (Dinámicos por Tipo)
**INCIDENT**:
- `Resuelto` (badge-success) si `is_resolved=true`
- `En Investigación` (badge-warning) si `is_resolved=false`

**MAINTENANCE**:
- `EMERGENCIA` (badge-danger) si `is_emergency=true`
- `Completado` (badge-success) si `actual_end` existe
- `En Progreso` (badge-warning) si `actual_start` existe
- `Programado` (badge-info) si solo está programado

**NEWS**:
- Según `news_type`: feature_release, policy_update, general_update

**ALERT**:
- Según `alert_type`: security, system, service, compliance
- `Finalizada` (badge-success) si `ended_at` existe
- `Activa` (badge-danger) si no finalizó

### ✅ Funciones Auxiliares
```javascript
formatDuration(minutes)  // Convierte min a formato legible (Xmin, Xh, Xd Xh)
renderFooterButtons()    // Botones de acción específicos por tipo
renderPagination()       // Genera controles de paginación
loadStatistics()         // Carga contadores para small boxes
```

---

## 🎯 RESUMEN GENERAL DEL CONTEXTO

### Relaciones Clave:
1. **User → CompanyFollower → Company → Announcement**
2. **Announcement** tiene metadata JSON específica por tipo
3. **API** filtra anuncios por visibilidad (USER solo ve PUBLISHED de seguidas)
4. **Sidebar** ya tiene enlace a `/app/user/announcements`
5. **Timeline** de AdminLTE v3 es el patrón UI oficial

### Archivos Relacionados:
- **Modelo**: `app/Features/ContentManagement/Models/Announcement.php`
- **Enums**: `AnnouncementType`, `PublicationStatus`, `UrgencyLevel`
- **Controller**: `app/Features/ContentManagement/Http/Controllers/AnnouncementController.php`
- **Resource**: `app/Features/ContentManagement/Http/Resources/AnnouncementResource.php`
- **Vista Ref**: `resources/views/app/company-admin/announcements/index.blade.php`
- **Rutas**: `routes/web.php`
- **Sidebar**: `resources/views/app/shared/sidebar.blade.php`

---

## ✨ PROMPT MEJORADO Y PROFESIONAL

---

# 📰 Crear Vista de Anuncios para Rol USER

## 🎯 Objetivo General

Implementar una vista completa de **Anuncios** para el rol **USER** que permita consumir y visualizar anuncios publicados por las empresas que el usuario sigue. La vista debe seguir el patrón de **Timeline de AdminLTE v3** con renderizado dinámico via jQuery, filtrado avanzado y visualización type-specific (MAINTENANCE, INCIDENT, NEWS, ALERT).

---

## 📋 Contexto de Negocio

### Actores y Relaciones
- **Users** pueden seguir múltiples **Companies** (relación: `business.user_company_followers`)
- **Companies** publican **Announcements** (4 tipos diferentes)
- **Users** solo ven anuncios **PUBLISHED** de empresas que siguen
- Cada tipo de anuncio tiene estructura de metadata JSON diferente
- La información incluye detalles críticos: mantenimientos, incidentes, noticias, alertas

### Tipos de Anuncios y su Propósito

**MAINTENANCE** (Mantenimiento programado):
- Comunica trabajos programados en infraestructura
- Metadata: urgency, scheduled_start, scheduled_end, is_emergency, affected_services, actual_start, actual_end
- Relevancia: Impacto directo en disponibilidad de servicios

**INCIDENT** (Incidente activo):
- Reporta problemas en tiempo real que afectan servicios
- Metadata: urgency, is_resolved, started_at, ended_at, resolution_content, affected_services
- Relevancia: Información crítica sobre problemas actuales

**NEWS** (Noticias/Actualizaciones):
- Comunica novedades, releases, cambios de política
- Metadata: news_type, target_audience, summary, call_to_action
- Relevancia: Informativo, puede contener acciones sugeridas

**ALERT** (Alertas de seguridad/sistema):
- Alertas urgentes sobre seguridad, cumplimiento, problemas de sistema
- Metadata: urgency, alert_type, message, action_required, action_description, started_at, ended_at, affected_services
- Relevancia: Crítica - requiere atención inmediata

---

## 🏗️ Estructura de Implementación

### A. Ruta Web (routes/web.php)

**Agregar bajo middleware `role:USER` con prefix `user`**:
```php
Route::get('/announcements', function () {
    $user = JWTHelper::getAuthenticatedUser();
    return view('app.user.announcements.index', [
        'user' => $user,
    ]);
})->name('user.announcements.index');
```

**Notas**:
- El usuario no necesita `company_id` porque la API filtra automáticamente
- La API retorna solo anuncios de empresas que sigue
- Ruta debe existir para coincidir con enlace en sidebar (ya presente)

### B. Vista Blade - Estructura de Componentes


4. **renderMetadata(announcement)**:
   - Llamar función específica por tipo
   - MAINTENANCE: urgency, fechas programadas/reales, servicios
   - INCIDENT: urgency, duración, resolución (collapsible)
   - NEWS: target_audience, summary, call_to_action
   - ALERT: urgency, alert_type, message, action_required

5. **renderPagination(meta)**:
   - Mostrar información: "Mostrando X-Y de Z"
   - Generar links de páginas
   - Agregar click handlers para cargar página

**Event Listeners**:
- Filtro tipo: click → resetear página → loadAnnouncements()
- Búsqueda: click botón o Enter → resetear página → loadAnnouncements()
- Paginación: click página → loadAnnouncements()


#### MAINTENANCE
```
Urgency: [LOW/MEDIUM/HIGH] con colores (success/info/warning)
Programado: DD/MM/YYYY, HH:MM - HH:MM (Xh Xmin)
[Si iniciado] Inicio real: HH:MM - HH:MM (Xh Xmin)
Servicios: lista, separada, por, comas
```

#### INCIDENT
```
Urgency: [LOW/MEDIUM/HIGH/CRITICAL] con colores
Duración: Xmin / Xh Xmin / Xd Xh
[Si resuelto] Resolución (collapsible):
  - <div class="collapse">: resolution_content en alert-success
Servicios: lista, separada, por, comas
```

#### NEWS
```
Audiencia: Iconos para users/agents/admins
Summary: Mostrado en cursiva/muted
[Si call_to_action] Botón: <a href=url>text</a>
```

#### ALERT
```
Urgency: [HIGH/CRITICAL] con colores
Alert Type: [security/system/service/compliance] con badge
Message: <div class="alert alert-warning">
[Si action_required] <div class="alert alert-danger">
  Acción Requerida: action_description
Duración activa: "Activa desde hace Xmin/Xh/Xd" si sin ended_at
Servicios: lista, separada, por, comas
```

### G. Estado Vacío / Error

**Sin anuncios o sin empresas que seguir**:
```html
<div class="text-center py-5">
  <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
  <p class="text-muted">
    No hay anuncios. Sigue empresas para recibir sus anuncios.
  </p>
</div>
```

**Error de carga**:
```html
<div class="text-center py-5 text-danger">
  <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
  <p>Error al cargar los anuncios. Intenta de nuevo.</p>
</div>
```

---

## ✅ Requisitos NO Negociables

1. **Template AdminLTE v3**: Usar Timeline oficial, sin inventar diseños
2. **jQuery + vanilla JS**: Plugins estándar de AdminLTE, sin frameworks adicionales
3. **API Existente**: Usar `/api/announcements` (ya funcional con role-based visibility)
4. **Type-specific rendering**: Cada tipo debe mostrar su metadata de forma distinta
5. **Paginación funcional**: Integración completa con API meta
6. **Filtros activos**: Tipo y búsqueda deben filtrar en tiempo real
7. **Responsive**: Debe funcionar en desktop y mobile (AdminLTE lo maneja)
8. **Autorización**: La API filtra automáticamente (solo PUBLISHED de seguidas)

