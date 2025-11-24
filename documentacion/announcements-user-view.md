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

#### B1. Index Blade (Padre - Orquestador)
**Ubicación**: `resources/views/app/user/announcements/index.blade.php`

**Rol**: Vista principal que contiene toda la lógica de renderizado y es el orquestador de componentes.

```blade
@extends('layouts.authenticated')

@section('title', 'Anuncios')
@section('content_header', 'Anuncios de Empresas que Sigo')

@section('content')
<!-- Filtros y búsqueda (inline) -->
<!-- Timeline container para renderizado dinámico -->
<!-- Paginación -->
@endsection

@push('scripts')
<!-- JavaScript de renderizado principal -->
@endpush
```

**Responsabilidades del Index**:
- Estructura layout base (header, filtros, paginación)
- Llamadas a API `/api/announcements`
- Orquestar renderizado de componentes
- Manejar filtros y búsqueda
- Mostrar estado vacío SI user no sigue empresas
- Gestionar paginación

#### B2. Componente Anunciante (Card por Tipo)
**Ubicación**: `resources/views/components/anuncios/announcement-item.blade.php`

**Rol**: Componente reutilizable para renderizar UN anuncio dentro del timeline.

**Props**:
- `$announcement` - Objeto anuncio (viene del API)
- `$type` - Tipo específico (MAINTENANCE, INCIDENT, NEWS, ALERT)

**Estructura**:
```blade
<div>
  <i class="ICON BGCOLOR"></i>
  <div class="timeline-item">
    <span class="time">HH:MM</span>
    <!-- Badges dinámicos según tipo -->
    <h3 class="timeline-header">TIPO TÍTULO</h3>
    <div class="timeline-body">CONTENIDO</div>
    <!-- Metadata según tipo -->
  </div>
</div>
```

**Nota**: Este componente es llamado por JavaScript (renderizado dinámico), no por Blade directo.

#### B3. Componentes Específicos por Tipo (Opcional)

Opción A: **Un solo componente** `announcement-item.blade.php` que maneja los 4 tipos con `@if` internos.

Opción B: **Cuatro componentes separados**:
```
resources/views/components/anuncios/
  announcement-news.blade.php           ← Para NEWS
  announcement-maintenance.blade.php    ← Para MAINTENANCE
  announcement-incident.blade.php       ← Para INCIDENT
  announcement-alert.blade.php          ← Para ALERT
```

**Recomendación**: **Opción A** (un solo componente) porque el renderizado es 100% JavaScript, no Blade. Los componentes Blade serían solo referencias.

#### B4. Componente: Sin Empresas Que Seguir
**Ubicación**: `resources/views/components/anuncios/no-followers.blade.php`

**Rol**: Vista alternativa mostrada cuando el usuario NO sigue a ninguna empresa.

**Estructura**:
```blade
<div class="card card-info">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-inbox mr-2"></i>
      No sigues a ninguna empresa
    </h3>
  </div>
  <div class="card-body text-center py-5">
    <i class="fas fa-building fa-3x text-muted mb-3"></i>
    <p class="text-muted">
      Sigue a empresas para recibir sus anuncios, noticias sobre mantenimientos, incidentes y alertas.
    </p>

    <h5 class="mt-4 mb-3">Empresas Populares</h5>
    <!-- Lista de empresas sugeridas con botón "Seguir" -->
    <div id="suggested-companies-list">
      <!-- Cargado dinámicamente desde API -->
    </div>
  </div>
</div>
```

**Responsabilidades**:
- Mostrar mensaje amigable
- Sugerir empresas populares (top followed)
- Botones "Seguir" para cada empresa
- Redirigir a lista completa de empresas si lo desea

**Condición de Aparición**:
- Cuando API retorna 0 anuncios Y user tiene 0 followers
- Mostrar en lugar del timeline vacío

#### B5. Estructura de Directorios

```
resources/views/app/user/
  announcements/
    index.blade.php                      ← Vista padre/orquestador

resources/views/components/anuncios/
  announcement-item.blade.php            ← Item genérico (4 tipos manejados internamente)
  no-followers.blade.php                 ← Vista alternativa: sin empresas que seguir
```

**Alternativa (si separas por tipo)**:
```
resources/views/components/anuncios/
  announcement-news.blade.php
  announcement-maintenance.blade.php
  announcement-incident.blade.php
  announcement-alert.blade.php
  no-followers.blade.php
```

#### B6. Flujo de Renderizado

```
index.blade.php (padre)
  ├─ Carga inicial: render spinner
  │
  ├─ fetch /api/announcements
  │  └─ Si respuesta OK (data.length > 0)
  │     ├─ renderTimeline(data)
  │     │  ├─ Agrupar por fecha
  │     │  └─ Para cada announcement:
  │     │     └─ Incluir componente announcement-item.blade.php
  │     │        (O generar HTML pure JavaScript)
  │     │
  │     └─ renderPagination(meta)
  │
  └─ Si respuesta vacía (data.length === 0)
     └─ Mostrar componente no-followers.blade.php
        (O componente empty-state.blade.php)
```

#### B7. Lógica Blade vs JavaScript

**En Blade (Estático)**:
```blade
<!-- Estructura HTML base -->
<div class="card">
  <div class="card-header"><!-- Filtros --></div>
  <div class="card-body">
    <div id="announcements-timeline" class="timeline">
      <!-- Será rellenado por JavaScript -->
    </div>
  </div>
</div>
```

**En JavaScript (Dinámico)**:
```javascript
// Genera HTML de cada anuncio
const html = `
  <div>
    <i class="${iconClass} ${bgColor}"></i>
    <div class="timeline-item">
      <!-- ... contenido ... -->
    </div>
  </div>
`;

// Inserta en DOM
document.getElementById('announcements-timeline').innerHTML += html;
```

**Para componentes**:
- `announcement-item.blade.php`: Es una **referencia/plantilla**, no se usa directamente
- El JavaScript genera el HTML, no Blade
- O: Mantener en Blade pero llamarlo vía AJAX si necesitas server-side rendering

**Recomendación**: **100% JavaScript** para renderizado dinámico (más eficiente)

#### B8. Diferencias vs Company-Admin

| Aspecto | Company-Admin | User |
|---------|---------------|------|
| Componentes | Integrados en una sola vista | Estructura modular con componentes |
| Renderizado | 100% JavaScript | 100% JavaScript |
| Gestión | Botones de acción (crear, editar, eliminar) | Solo lectura |
| Estadísticas | Small-boxes con contadores | Sin estadísticas (o minimal) |
| Vista Alternativa | No aplica (es admin) | `no-followers.blade.php` |
| Carpeta | `/app/company-admin/announcements/` | `/app/user/announcements/` + `/components/anuncios/` |

### C. Lógica de Renderizado JavaScript

**Variables globales** (dentro de document.ready):
```javascript
let currentPage = 1;
let currentType = '';
let currentSearch = '';
const dateColors = ['bg-red', 'bg-green', 'bg-blue', 'bg-yellow'];
```

**Flujo principal**:

1. **loadAnnouncements()**:
   - URL: `/api/announcements?per_page=10&page=currentPage&status=published`
   - Agregar `&type=X` si hay filtro
   - Agregar `&search=X` si hay búsqueda
   - Headers: `Authorization: Bearer token`, `Accept: application/json`
   - Mostrar spinner durante carga

2. **renderTimeline(announcements)**:
   - Agrupar por fecha con `time-label`
   - Alternar colores de fechas
   - Para cada anuncio llamar `renderAnnouncementItem()`
   - Agregar marcador de fin (icono reloj gris)

3. **renderAnnouncementItem(announcement)**:
   - Obtener config (icono, color, label) según tipo
   - Renderizar item con estructura:
     ```html
     <div>
       <i class="ICON BGCOLOR"></i>
       <div class="timeline-item">
         <span class="time">HH:MM</span>
         BADGES (status)
         <h3 class="timeline-header">TIPO TÍTULO</h3>
         <div class="timeline-body">CONTENIDO</div>
         METADATA (según tipo)
       </div>
     </div>
     ```

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

### D. Mapeando Tipos a Configuración

```javascript
function getTypeConfig(type) {
    const configs = {
        'NEWS': {
            icon: 'fas fa-newspaper',
            bgColor: 'bg-blue',
            badgeColor: 'badge-info',
            label: 'Noticia'
        },
        'MAINTENANCE': {
            icon: 'fas fa-tools',
            bgColor: 'bg-purple',
            badgeColor: 'badge-purple',
            label: 'Mantenimiento'
        },
        'INCIDENT': {
            icon: 'fas fa-exclamation-triangle',
            bgColor: 'bg-red',
            badgeColor: 'badge-danger',
            label: 'Incidente'
        },
        'ALERT': {
            icon: 'fas fa-bell',
            bgColor: 'bg-yellow',
            badgeColor: 'badge-warning',
            label: 'Alerta'
        }
    };
    return configs[type] || configs['NEWS'];
}
```

### E. Renderizado de Status Badges (dinámico)

**INCIDENT**:
- `badge-success`: "Resuelto" si `is_resolved === true`
- `badge-warning`: "En Investigación" si `is_resolved === false`

**MAINTENANCE**:
- `badge-danger`: "EMERGENCIA" si `is_emergency === true`
- `badge-success`: "Completado" si `actual_end` existe
- `badge-warning`: "En Progreso" si `actual_start` existe (sin actual_end)
- `badge-info`: "Programado" por defecto

**NEWS**:
- Badge según `news_type`: feature_release, policy_update, general_update
- Con iconos: ⭐ Nuevo Feature, ⚖️ Política, ℹ️ Actualización

**ALERT**:
- Badge según `alert_type`: security, system, service, compliance
- Icono: 🔐 Seguridad, 🖥️ Sistema, 📡 Servicio, ⚖️ Cumplimiento
- `badge-danger`: "Activa" si sin `ended_at`
- `badge-success`: "Finalizada" si tiene `ended_at`

### F. Renderizado de Metadata por Tipo

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

---

## 📁 Estructura de Archivos

### Opción A: Estructura Modular (Recomendada)

```
resources/views/app/user/
  announcements/
    index.blade.php                      ← Vista PADRE (orquestador)

resources/views/components/anuncios/
  announcement-item.blade.php            ← Componente genérico para todos los tipos
  no-followers.blade.php                 ← Vista alternativa: sin empresas
```

**Ventajas**:
- Componentes reutilizables
- Separación de responsabilidades
- Fácil de mantener
- Estructura escalable

### Opción B: Estructura Type-Specific (Si requieres componentes por tipo)

```
resources/views/app/user/
  announcements/
    index.blade.php                      ← Vista PADRE (orquestador)

resources/views/components/anuncios/
  announcement-news.blade.php            ← Componente para NEWS
  announcement-maintenance.blade.php     ← Componente para MAINTENANCE
  announcement-incident.blade.php        ← Componente para INCIDENT
  announcement-alert.blade.php           ← Componente para ALERT
  no-followers.blade.php                 ← Vista alternativa
```

**Ventajas**:
- Componentes específicos por tipo
- Lógica más clara (menos @if internos)
- Cada componente manejable por separado

**Recomendación**: **Opción A** (más eficiente con renderizado 100% JavaScript)

---

## 🔄 Integración con Existente

### Backend (Ya Existente)
- **Sidebar**: Enlace ya existe en `resources/views/app/shared/sidebar.blade.php` → `/app/user/announcements`
- **API**: `/api/announcements` con filtros y role-based visibility (fully funcional)
- **Modelos**:
  - `Announcement` - modelo base con metadata JSON
  - `CompanyFollower` - relación user-company
  - Tabla: `business.user_company_followers`
- **Resources**: `AnnouncementResource` - transformación JSON con author data
- **Controllers**: `AnnouncementController` - endpoints de lectura completos

### Frontend (A Crear)
- **Rutas**: Agregar GET `/app/user/announcements` en `routes/web.php` bajo middleware `role:USER`
- **Vistas**:
  - `resources/views/app/user/announcements/index.blade.php` (PADRE)
  - `resources/views/components/anuncios/announcement-item.blade.php` (componente genérico)
  - `resources/views/components/anuncios/no-followers.blade.php` (empty state)
- **JavaScript**: Renderizado 100% dinámico en index.blade.php

### Dependencias Existentes Aprovechadas
- **AdminLTE v3**: Timeline component + CSS classes (bg-red, bg-blue, etc)
- **jQuery**: Ya presente en layout.authenticated
- **Font Awesome**: Icons (fas fa-newspaper, fas fa-tools, etc)
- **TokenManager**: window.tokenManager.getAccessToken() para auth headers
- **JWTHelper**: PHP-side para extraer user data

---

## 🎯 Estados de la Vista y Renderizado Condicional

### Estado 1: Vista Normal (Usuario sigue empresas + hay anuncios)
**Condición**: `data.length > 0` en API response

**Renderizado**:
- Timeline con anuncios agrupados por fecha
- Paginación funcional
- Filtros visibles y activos
- Búsqueda disponible

**Componente**: `announcements-timeline` (generado por JavaScript)

### Estado 2: Sin Empresas Que Seguir
**Condición**: `data.length === 0` AND user tiene 0 followers

**Renderizado**:
- Mostrar componente `no-followers.blade.php`
- Timeline OCULTO o VACÍO
- Mensaje amigable: "No sigues a ninguna empresa"
- Sugerencias de empresas populares
- Botón para ir a "Seguir Empresas"

**Componente**: `no-followers.blade.php`

**Lógica en JavaScript**:
```javascript
if (data.data.length === 0) {
    // Verificar si user tiene followers
    const hasFollowers = await checkUserFollowers();

    if (!hasFollowers) {
        // Mostrar no-followers.blade.php
        document.getElementById('announcements-timeline').innerHTML = `
            [Incluir contenido de no-followers.blade.php]
        `;
    } else {
        // Mostrar empty state normal
        document.getElementById('announcements-timeline').innerHTML = `
            <div class="text-center py-5">
              <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
              <p class="text-muted">No hay anuncios publicados.</p>
            </div>
        `;
    }
}
```

### Estado 3: Error de Carga (API error o network error)
**Condición**: `fetch()` error o response.ok === false

**Renderizado**:
- Mostrar mensaje de error
- Botón "Reintentar"
- Ícono de error

**HTML**:
```html
<div class="text-center py-5 text-danger">
  <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
  <p>Error al cargar los anuncios. Intenta de nuevo.</p>
  <button class="btn btn-primary mt-2" onclick="location.reload()">
    <i class="fas fa-redo mr-2"></i> Reintentar
  </button>
</div>
```

### Estado 4: Cargando (Loading)
**Condición**: Durante `fetch()` (antes de respuesta)

**Renderizado**:
- Spinner animado
- Mensaje "Cargando anuncios..."

**HTML**:
```html
<div class="text-center py-5">
  <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
  <p class="mt-2 text-muted">Cargando anuncios...</p>
</div>
```

---

## 🎨 Paleta de Colores y Estilos

| Tipo | Icono | BG Color | Badge Color | Uso |
|------|-------|----------|-------------|-----|
| NEWS | newspaper | bg-blue | badge-info | Información general |
| MAINTENANCE | tools | bg-purple | badge-purple | Trabajos programados |
| INCIDENT | exclamation-triangle | bg-red | badge-danger | Problemas críticos |
| ALERT | bell | bg-yellow | badge-warning | Alertas urgentes |

---

## 🧪 Checklist de Implementación

### Fase 1: Estructura Base
- [ ] Crear directorio `resources/views/app/user/announcements/`
- [ ] Crear directorio `resources/views/components/anuncios/`
- [ ] Crear archivo `resources/views/app/user/announcements/index.blade.php` (PADRE)
- [ ] Crear archivo `resources/views/components/anuncios/announcement-item.blade.php`
- [ ] Crear archivo `resources/views/components/anuncios/no-followers.blade.php`
- [ ] Agregar ruta en `routes/web.php` bajo middleware USER

### Fase 2: Lógica JavaScript (en index.blade.php)
- [ ] Implementar `loadAnnouncements()` con fetch API
- [ ] Implementar `renderTimeline()` con agrupación por fecha
- [ ] Implementar `getTypeConfig()` para mapeo de tipos
- [ ] Implementar `getStatusBadge()` para badges dinámicos
- [ ] Implementar `renderMetadata()` con 4 variantes tipo-specific
  - [ ] Variant MAINTENANCE
  - [ ] Variant INCIDENT
  - [ ] Variant NEWS
  - [ ] Variant ALERT
- [ ] Implementar `renderPagination()` con click handlers
- [ ] Implementar `formatDuration()` para tiempos legibles

### Fase 3: Interactividad
- [ ] Agregar event listeners: filtro tipo (dropdown)
- [ ] Agregar event listener: búsqueda (input + botón)
- [ ] Agregar event listener: Enter en búsqueda
- [ ] Agregar event listeners: paginación (click páginas)

### Fase 4: Validación y Testing
- [ ] Probar con anuncios tipo NEWS
- [ ] Probar con anuncios tipo MAINTENANCE
- [ ] Probar con anuncios tipo INCIDENT
- [ ] Probar con anuncios tipo ALERT
- [ ] Probar filtro por tipo
- [ ] Probar búsqueda por texto
- [ ] Probar paginación (anterior/siguiente/números)
- [ ] Validar sin anuncios (empty state)
- [ ] Validar sin empresas que seguir (no-followers.blade.php)
- [ ] Validar error de API (error state)
- [ ] Verificar responsive design en mobile (AdminLTE)
- [ ] Probar con diferentes combinaciones de filtros

---

## 📌 Notas Importantes

1. **Sin Gestión**: Esta vista es READ-ONLY. No incluir botones de crear/editar/eliminar.
2. **SIN Estadísticas**: No agregar small-boxes a menos que sea decisión de UX posterior.
3. **Sincronización**: La API ya filtra automáticamente. JavaScript solo renderiza.
4. **Localization**: Usar format español (DD/MM/YYYY, "Noticia" en lugar de "News").
5. **Performance**: Limitar a 10 anuncios por página. La API soporta `per_page` hasta 100.
6. **Estado de Carga**: Mostrar spinner mientras se fetching. Mejorar UX.
7. **Fallback**: Si sin empresas, mostrar mensaje amigable que dirija a seguir empresas.

