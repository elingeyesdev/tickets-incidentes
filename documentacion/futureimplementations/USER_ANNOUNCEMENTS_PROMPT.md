# Prompt: Crear Vista de Anuncios para Rol USER

## Estado: EN CONSTRUCCIÓN
*Este documento se está generando de manera incremental y sistemática*

---

## 1. RESUMEN EJECUTIVO

Se requiere crear una vista completa de **Anuncios** para el rol **USER**, permitiendo que los usuarios vean anuncios de las empresas que siguen. La implementación debe:

- Seguir 100% el template **AdminLTE v3** (sin inventar diseños)
- Usar **jQuery** y plugins estándar de AdminLTE v3
- Reutilizar componentes predefinidos y establecimientos de la plataforma
- Soportar 4 tipos de anuncios con metadata JSON diferente
- Mostrar vista alternativa cuando el usuario no sigue empresas

---

## 2. CONTEXTO DE NEGOCIO

### Propósito
Los usuarios pueden seguir empresas para recibir información adicional. Esta vista permite visualizar los **anuncios** que estas empresas publican.

### Tipos de Anuncios
El sistema soporta 4 tipos distintos de anuncios:

1. **MAINTENANCE** - Mantenimiento planeado o emergencias
2. **INCIDENT** - Incidentes en los servicios
3. **NEWS** - Noticias, actualizaciones, y cambios
4. **ALERT** - Alertas de seguridad, sistema, servicios o compliance

Cada tipo tiene una estructura de metadata JSON única que debe ser renderizada apropiadamente en la UI.

---

## 3. DATOS DISPONIBLES (API)

### Endpoint Principal
- **URL**: `GET /api/announcements`
- **Autenticación**: Bearer Token (JWT)
- **Visibilidad**: Usuario solo ve anuncios PUBLICADOS de empresas que sigue

### Estructura de Respuesta

```json
{
  "data": [
    {
      "id": "uuid",
      "title": "string",
      "content": "string",
      "type": "MAINTENANCE|INCIDENT|NEWS|ALERT",
      "status": "DRAFT|SCHEDULED|PUBLISHED|ARCHIVED",
      "metadata": {
        // Varía según el tipo
      },
      "company": {
        "id": "uuid",
        "name": "string",
        "logo_url": "string"
      },
      "author": {
        "id": "uuid",
        "name": "string"
      },
      "published_at": "datetime",
      "created_at": "datetime"
    }
  ],
  "meta": {
    "total": 0,
    "per_page": 15,
    "current_page": 1
  }
}
```

---

## 4. ESTRUCTURA DE METADATA POR TIPO

### 4.1 MAINTENANCE (Mantenimiento)
Usado para comunicar mantenimientos planeados o emergencias en servicios.

**Campos Requeridos:**
- `urgency` (enum): LOW | MEDIUM | HIGH
- `scheduled_start` (datetime): Inicio planeado del mantenimiento
- `scheduled_end` (datetime): Fin planeado del mantenimiento
- `is_emergency` (boolean): Si es una emergencia

**Campos Opcionales:**
- `actual_start` (datetime): Inicio real (cuando ya comenzó)
- `actual_end` (datetime): Fin real (cuando ya terminó)
- `affected_services` (array): Lista de servicios afectados

**Caso de Uso UI:**
Mostrar cronograma, estado actual, servicios afectados, con colores según urgencia.

---

### 4.2 INCIDENT (Incidente)
Usado para reportar incidentes en los servicios.

**Campos Requeridos:**
- `urgency` (enum): LOW | MEDIUM | HIGH | CRITICAL
- `is_resolved` (boolean): Estado de resolución
- `started_at` (datetime): Cuándo inició el incidente

**Campos Opcionales:**
- `ended_at` (datetime): Cuándo terminó
- `resolution_content` (string): Descripción de la resolución
- `affected_services` (array): Servicios afectados

**Caso de Uso UI:**
Card que muestra estado del incidente, duración, contenido de resolución si aplica.

---

### 4.3 NEWS (Noticias)
Usado para anunciar noticias, actualizaciones y cambios de política.

**Campos Requeridos:**
- `news_type` (enum): feature_release | policy_update | general_update
- `target_audience` (array): users | agents | admins (audiencia objetivo)
- `summary` (string): Resumen de la noticia

**Campos Opcionales:**
- `call_to_action` (object): Objeto con acción sugerida (botón, link, etc)

**Caso de Uso UI:**
Mostrar noticia con tipo distintivo, resumen expandible, CTA si aplica.

---

### 4.4 ALERT (Alerta)
Usado para alertas de seguridad, sistema, servicios o compliance.

**Campos Requeridos:**
- `urgency` (enum): HIGH | CRITICAL
- `alert_type` (enum): security | system | service | compliance
- `message` (string): Mensaje de la alerta
- `action_required` (boolean): Si requiere acción del usuario
- `started_at` (datetime): Cuándo comenzó la alerta

**Campos Opcionales:**
- `action_description` (string): Descripción de la acción requerida
- `ended_at` (datetime): Cuándo termina la alerta
- `affected_services` (array): Servicios afectados

**Caso de Uso UI:**
Mostrar alerta prominente con color según urgencia, descripción de acción requerida destacada.

---

## 5. ESTRUCTURA DE DIRECTORIOS PROPUESTA

```
resources/
├── views/
│   └── app/
│       └── user/
│           ├── announcements/
│           │   └── index.blade.php              [VISTA PADRE]
│           │   └── partials/
│           │       ├── empty-state.blade.php    [Sin seguimientos]
│           │       └── filters.blade.php        [Filtros avanzados]
│           │
│           └── components/
│               └── announcements/
│                   ├── maintenance-card.blade.php
│                   ├── incident-card.blade.php
│                   ├── news-card.blade.php
│                   └── alert-card.blade.php
```

---

## 6. COMPONENTES PRINCIPALES

### 6.1 Vista Padre: `index.blade.php`
Responsabilidades:
- Timeline base AdminLTE v3
- Llamada AJAX a API de anuncios
- Renderización dinámica de componentes según tipo
- Gestión de filtros
- Manejo de estados vacíos

---

## 7. CAPACIDADES DE FILTRADO Y BÚSQUEDA (API)

### Parámetros de Consulta Soportados

La API `/api/announcements` soporta los siguientes filtros y opciones:

| Parámetro | Tipo | Descripción | Valores |
|-----------|------|-------------|---------|
| `type` | query | Filtrar por tipo de anuncio | MAINTENANCE, INCIDENT, NEWS, ALERT |
| `search` | query | Búsqueda en título y contenido | string (máx 100 chars) |
| `sort` | query | Campo y dirección de ordenamiento | -published_at (default), -created_at, title |
| `published_after` | query | Anuncios publicados después de esta fecha | date (YYYY-MM-DD) |
| `published_before` | query | Anuncios publicados antes de esta fecha | date (YYYY-MM-DD) |
| `page` | query | Número de página | integer (default: 1) |
| `per_page` | query | Items por página | integer (default: 20, máx: 100) |

### Visibilidad de Datos para Role USER
- Solo ve anuncios con status **PUBLISHED**
- Solo ve anuncios de empresas que **sigue**
- No puede ver `company_id` como filtro (restringido para PLATFORM_ADMIN)

---

## 8. DEPENDENCIAS Y PLUGINS ADMINLTE V3

### Componentes AdminLTE v3 Disponibles

Basados en la investigación del codebase:

**Componentes para Timeline:**
- `timeline` - Contenedor base para mostrar eventos en línea de tiempo
- `time-label` - Etiqueta de tiempo (ej: "Today", "Yesterday")
- `timeline-item` - Item individual en la timeline

**Componentes para Cards:**
- `x-adminlte-card` - Card component base con soporte para:
  - Title
  - Icon
  - Tools (collapse, maximize, remove)
  - Body
  - Footer

**Componentes para Widgets:**
- `small-box` - Box pequeño para estadísticas (usado en dashboard)
- `info-box` - Box informativo con icono
- `callout` - Callout/alert para mensajes destacados
- `alert` - Componente de alerta con tema

**Plugins jQuery Estándar:**
- Card JS (data-card-widget) - collapse, maximize, remove
- Bootstrap JS - modals, dropdowns, etc
- AdminLTE JS - sidebar toggle, navbar, etc

### No Inventar Diseños
❌ **PROHIBIDO**: Crear estilos o componentes que no existan en AdminLTE v3
✅ **OBLIGATORIO**: Reutilizar componentes de AdminLTE v3 directamente

---

## 9. PATRONES DE IMPLEMENTACIÓN CON JQUERY

### Patrón de Llamadas AJAX
El proyecto usa `$.ajax()` para comunicación con API:

```javascript
// Ejemplo del codebase existente
$.ajax({
    url: `/api/announcements`,
    method: 'GET',
    headers: { 'Authorization': `Bearer ${token}` },
    success: function(data) {
        // Renderizar datos
    },
    error: function(error) {
        // Manejar error
    }
});
```

### Patrón de Eventos Custom
El proyecto usa eventos jQuery custom para comunicación entre componentes:

```javascript
// Disparar evento
$(document).trigger('announcements:loaded', { data: announcements });

// Escuchar evento
$(document).on('announcements:loaded', function(e, data) {
    // Reaccionar al evento
});
```

**Eventos Custom Esperados para Anuncios:**
- `announcements:list-loaded` - Se cargaron los anuncios
- `announcements:filter-changed` - Cambió un filtro
- `announcements:details-opened` - Se abrió detalle de anuncio

### Manejo de Tokens JWT
El proyecto usa un helper global para obtener tokens:

```javascript
const token = window.tokenManager.getAccessToken();
```

---

## 10. ESPECIFICACIONES DE LA VISTA PADRE: `index.blade.php`

### Responsabilidades Principales

1. **Renderización Base**
   - Usar layout `authenticated` de AdminLTE v3
   - Incluir navbar y sidebar standard
   - Agregar breadcrumbs: Home > Anuncios

2. **Timeline Principal**
   - Usar estructura de `timeline` de AdminLTE v3
   - Mostrar anuncios en orden de publicación descendente
   - Agrupar por fecha si aplica (hoy, ayer, etc)

3. **Carga Inicial de Datos**
   - Realizar llamada AJAX a `/api/announcements` al cargar página
   - Pasar token desde JWT
   - Manejar estados: cargando, error, vacío, con datos

4. **Componentes Dinámicos**
   - Renderizar card apropiada según tipo de anuncio
   - Componentes: `maintenance-card.blade.php`, `incident-card.blade.php`, `news-card.blade.php`, `alert-card.blade.php`
   - Pasar metadata del anuncio a componente

5. **Filtros Avanzados**
   - Barra de filtros colapsible (usando AdminLTE collapse)
   - Filtro por tipo (checkboxes o select múltiple)
   - Búsqueda por texto
   - Rango de fechas (published_after, published_before)
   - Ordenamiento (default: más recientes)
   - Aplicar filtros sin recargar página (AJAX)

6. **Vista Vacía**
   - Si usuario no sigue empresas: mostrar `empty-state.blade.php`
   - Incluir sugerencias de empresas a seguir
   - Botón "Descubrir Empresas"

7. **Paginación**
   - Mostrar control de paginación (abajo o infinito scroll)
   - Default: 20 items por página
   - Máximo: 100 items por página

### Flujo de Interacción

```
1. Página carga
   ↓
2. Script ejecuta: Obtener token JWT
   ↓
3. AJAX GET /api/announcements (sin filtros inicialmente)
   ↓
4. Respuesta recibida
   ↓
5. Si no hay empresas seguidas:
      → Mostrar empty-state
   Sino:
      → Renderizar anuncios en timeline
      → Mostrar filtros
   ↓
6. Usuario interactúa con filtros
   ↓
7. AJAX GET /api/announcements (con parámetros)
   ↓
8. Actualizar timeline con nuevos resultados
```

---

## 11. ESPECIFICACIONES DE COMPONENTES DE CARDS

### 11.1 Componente: `maintenance-card.blade.php`

**Datos Requeridos:**
- `$announcement` (Announcement model o recurso)
- Acceso a metadata: `$announcement['metadata']`

**Estructura Visual:**
```
┌─ HEADER ────────────────────────────────┐
│ [Logo] Nombre Empresa | Fecha           │
│                                         │
│ Mantenimiento Programado               │
└─────────────────────────────────────────┘
┌─ BODY ──────────────────────────────────┐
│                                         │
│ Descripción: [content]                 │
│                                         │
│ ⏰ Programado: YYYY-MM-DD HH:MM        │
│ ⏱️  Duración Estimada: X horas        │
│ 🚨 Urgencia: [badge color según]      │
│ 🆘 Es Emergencia: [Yes/No]             │
│ 📋 Servicios Afectados: [lista]        │
│                                         │
│ [Estado] En Tiempo / En Progreso       │
└─────────────────────────────────────────┘
```

**Lógica:**
- Si `is_emergency = true`: Mostrar badge ROJO prominente
- Si `actual_start` y `actual_end` existen: Mostrar "Completado en X horas"
- Si `actual_start` existe pero no `actual_end`: Mostrar "En progreso - Iniciado hace X"
- Si aún no ha iniciado: Mostrar fecha/hora de inicio planeado

---

### 11.2 Componente: `incident-card.blade.php`

**Estructura Visual:**
```
┌─ HEADER ────────────────────────────────┐
│ [Logo] Nombre Empresa | Fecha           │
│                                         │
│ Incidente Reportado                    │
└─────────────────────────────────────────┘
┌─ BODY ──────────────────────────────────┐
│                                         │
│ Descripción: [content]                 │
│                                         │
│ 🚨 Urgencia: [badge color según]      │
│ ⏱️  Duración: Hace X horas             │
│ 📋 Servicios Afectados: [lista]        │
│ ✅ Estado: [RESUELTO / EN PROGRESO]    │
│                                         │
│ [Si resuelto]                          │
│ Resolución: [resolution_content]       │
│ Finalizado hace: [X horas/días]        │
└─────────────────────────────────────────┘
```

**Lógica:**
- Cambiar color de header según urgencia
- Si `is_resolved = true`: Mostrar checkmark verde y contenido de resolución
- Si `is_resolved = false`: Mostrar spinner/warning y "Aún en investigación"

---

### 11.3 Componente: `news-card.blade.php`

**Estructura Visual:**
```
┌─ HEADER ────────────────────────────────┐
│ [Logo] Nombre Empresa | Fecha           │
│                                         │
│ 📰 Noticia - [Tipo: Feature/Policy/Upd]│
└─────────────────────────────────────────┘
┌─ BODY ──────────────────────────────────┐
│                                         │
│ [Title]                                │
│                                         │
│ [content]                              │
│                                         │
│ Resumen: [metadata.summary]            │
│                                         │
│ Dirigida a: [target_audience badges]   │
│                                         │
│ [Si tiene CTA]                         │
│ [Botón call_to_action]                 │
└─────────────────────────────────────────┘
```

**Lógica:**
- Mostrar icono según `news_type`
- Expandible si content es muy largo
- Mostrar badges con audience objetivo

---

### 11.4 Componente: `alert-card.blade.php`

**Estructura Visual:**
```
┌─ HEADER (Fondo: Rojo/Naranja) ──────────┐
│ ⚠️  ALERTA CRÍTICA                      │
│ Nombre Empresa | Fecha                 │
└─────────────────────────────────────────┘
┌─ BODY (Borde rojo izquierdo) ──────────┐
│                                         │
│ Tipo: [security/system/service/comp]   │
│                                         │
│ Mensaje: [metadata.message - BOLD]     │
│                                         │
│ 📋 Servicios Afectados: [lista]        │
│                                         │
│ [Si action_required = true]            │
│ ⚡ ACCIÓN REQUERIDA:                   │
│ [action_description - destacado]       │
│                                         │
│ Iniciada: [started_at]                 │
│ [Si ended_at] Finaliza: [ended_at]     │
└─────────────────────────────────────────┘
```

**Lógica:**
- Background color según `urgency`: HIGH = naranja, CRITICAL = rojo
- `alert_type` determina icono (🔒 security, ⚙️ system, 🔗 service, ✅ compliance)
- Si `action_required = true`: Resaltar prominentemente en rojo
- Mostrar fecha de vencimiento si existe `ended_at`

---

## 12. COMPONENTE: `empty-state.blade.php`

**Usado Cuando:**
- Usuario no sigue ninguna empresa

**Estructura:**
```
┌──────────────────────────────────────────┐
│                                          │
│         🏢 ¡Oops! No hay anuncios       │
│                                          │
│   Aún no sigues a ninguna empresa       │
│   Sigue empresas para ver sus anuncios  │
│                                          │
│   [Botón: Descubrir Empresas]           │
│                                          │
└──────────────────────────────────────────┘

┌─ SUGERENCIAS DE EMPRESAS ──────────────┐
│ Las más seguidas:                      │
│                                        │
│  [Card Empresa 1]  [Card Empresa 2]   │
│  [Card Empresa 3]  [Card Empresa 4]   │
│                                        │
│  [Ver más empresas]                    │
└────────────────────────────────────────┘
```

---

## 13. INTEGRACIÓN CON SISTEMA EXISTENTE

### Rutas Propuestas

```php
// En routes/web.php
Route::middleware('role:USER')->prefix('user')->group(function () {
    Route::get('/announcements', function () {
        return view('app.user.announcements.index');
    })->name('user.announcements.index');
});
```

### Endpoints API Utilizados

```
GET /api/announcements
├─ Parámetros: type, search, sort, published_after, published_before, page, per_page
├─ Headers: Authorization: Bearer {token}
└─ Retorna: 200 OK con paginación

GET /api/companies
├─ Usado para: Sugerencias de empresas en empty-state (si aplica)
└─ Filtro: Por más seguidas (popular)
```

---

## 14. REGLAS NO NEGOCIABLES

✅ **OBLIGATORIO:**
1. Seguir 100% AdminLTE v3 - NO inventar diseños
2. Usar jQuery y plugins estándar AdminLTE v3
3. Usar eventos custom $(document).trigger() para comunicación
4. Bearer Token desde JWT (window.tokenManager.getAccessToken())
5. Pasar metadata completa a componentes
6. Mostrar empty-state cuando no hay empresas seguidas
7. Soportar todos los filtros disponibles de la API
8. Responsive design (mobile-first basado en AdminLTE)

❌ **PROHIBIDO:**
1. Crear estilos/componentes que no existan en AdminLTE v3
2. Usar Vue, React, Alpine.js o frameworks frontend alternativos
3. Hacer múltiples llamadas AJAX innecesarias
4. Hardcodear datos (todo debe venir de API)
5. Ignorar permisos de visibilidad (solo PUBLISHED para USER)

---

## 15. ESTRUCTURA DETALLADA DE ARCHIVOS

### 15.1 `index.blade.php` - Sección Script Principal

```javascript
<script>
    // Configuración Global
    const AnnouncementsConfig = {
        role: '{{ $role }}',
        userId: '{{ auth()->id() }}',
        endpoints: {
            list: '/api/announcements',
            companies: '/api/companies'
        }
    };

    (function() {
        let currentFilters = {
            type: null,
            search: '',
            sort: '-published_at',
            published_after: null,
            published_before: null,
            page: 1,
            per_page: 20
        };

        function init() {
            // Obtener token
            const token = window.tokenManager.getAccessToken();
            if (!token) return;

            // Cargar anuncios inicialmente
            loadAnnouncements(token);

            // Listeners de filtros
            setupFilterListeners(token);
        }

        async function loadAnnouncements(token) {
            try {
                const query = new URLSearchParams(currentFilters).toString();
                const response = await $.ajax({
                    url: `${AnnouncementsConfig.endpoints.list}?${query}`,
                    method: 'GET',
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                if (response.data.length === 0) {
                    showEmptyState(token);
                } else {
                    renderTimeline(response.data);
                    setupPagination(response.meta);
                }

                $(document).trigger('announcements:list-loaded', { data: response.data });

            } catch (error) {
                console.error('[Announcements] Error loading:', error);
                showError('Error cargando anuncios');
            }
        }

        function renderTimeline(announcements) {
            const $timeline = $('#announcements-timeline');
            $timeline.empty();

            announcements.forEach(announcement => {
                const component = getComponentForType(announcement.type);
                const html = `<div class="announcement-item">${component}</div>`;
                $timeline.append(html);
            });
        }

        function getComponentForType(type) {
            // Retornar include del componente apropiado
            switch(type) {
                case 'MAINTENANCE':
                    return '@include("app.user.announcements.components.maintenance-card", ["announcement" => $announcement])';
                // ... otros casos
            }
        }

        function setupFilterListeners(token) {
            // Filter by type
            $('[data-filter-type]').on('change', function() {
                currentFilters.type = $(this).val() || null;
                currentFilters.page = 1;
                loadAnnouncements(token);
            });

            // Search
            $('#search-input').on('keyup', function() {
                currentFilters.search = $(this).val();
                currentFilters.page = 1;
                loadAnnouncements(token);
            });

            // Dates
            $('#date-from').on('change', function() {
                currentFilters.published_after = $(this).val();
                currentFilters.page = 1;
                loadAnnouncements(token);
            });

            $('#date-to').on('change', function() {
                currentFilters.published_before = $(this).val();
                currentFilters.page = 1;
                loadAnnouncements(token);
            });

            // Sort
            $('[data-sort]').on('click', function(e) {
                e.preventDefault();
                currentFilters.sort = $(this).data('sort');
                currentFilters.page = 1;
                loadAnnouncements(token);
            });
        }

        function showEmptyState(token) {
            // Mostrar vista vacía
            $('#announcements-timeline').html('@include("app.user.announcements.partials.empty-state")');

            // Cargar sugerencias de empresas
            loadCompanySuggestions(token);
        }

        async function loadCompanySuggestions(token) {
            try {
                const response = await $.ajax({
                    url: `${AnnouncementsConfig.endpoints.companies}?sort=-followers_count&per_page=4`,
                    method: 'GET',
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                renderCompanySuggestions(response.data);
            } catch (error) {
                console.error('[Announcements] Error loading suggestions:', error);
            }
        }

        // Cuando DOM esté listo
        if (typeof jQuery !== 'undefined') {
            $(document).ready(init);
        }
    })();
</script>
```

### 15.2 `index.blade.php` - Sección HTML Base

```blade
@extends('layouts.authenticated')

@section('title', 'Anuncios')

@section('content_header', 'Anuncios de Empresas')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.user') }}">Home</a></li>
    <li class="breadcrumb-item active">Anuncios</li>
@endsection

@section('content')
<div class="row" id="announcements-app">
    <!-- Filtros (LEFT) -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filtros</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Type Filter -->
                <div class="form-group">
                    <label>Tipo de Anuncio</label>
                    <select id="filter-type" class="form-control" data-filter-type>
                        <option value="">Todos</option>
                        <option value="MAINTENANCE">Mantenimiento</option>
                        <option value="INCIDENT">Incidente</option>
                        <option value="NEWS">Noticia</option>
                        <option value="ALERT">Alerta</option>
                    </select>
                </div>

                <!-- Search -->
                <div class="form-group">
                    <label>Buscar</label>
                    <input type="text" id="search-input" class="form-control" placeholder="Título o contenido...">
                </div>

                <!-- Date Range -->
                <div class="form-group">
                    <label>Desde</label>
                    <input type="date" id="date-from" class="form-control">
                </div>
                <div class="form-group">
                    <label>Hasta</label>
                    <input type="date" id="date-to" class="form-control">
                </div>

                <!-- Sort -->
                <div class="form-group">
                    <label>Ordenar por</label>
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-sort="-published_at">Recientes</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-sort="title">Título</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline (RIGHT) -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Últimos Anuncios</h3>
            </div>
            <div class="card-body">
                <div class="timeline" id="announcements-timeline">
                    <!-- Anuncios renderizados aquí via JS -->
                    <div class="text-center text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Cargando anuncios...
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div id="announcements-pagination">
            <!-- Paginación generada dinamicamente -->
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- Scripts aquí -->
@endpush
```

---

## 16. CHECKLIST DE VERIFICACIÓN

Antes de entregar, verificar que:

### Estructura & Archivos
- [ ] Carpeta `app/user/announcements/` existe
- [ ] Carpeta `app/user/components/announcements/` existe
- [ ] Todos los 4 archivos de componentes creados (maintenance, incident, news, alert)
- [ ] Archivo `empty-state.blade.php` creado
- [ ] Archivo `filters.blade.php` creado (si aplica)
- [ ] Ruta en `routes/web.php` agregada para `user.announcements.index`

### Funcionalidad
- [ ] Carga inicial de anuncios sin errores
- [ ] Filtro por tipo funciona correctamente
- [ ] Búsqueda funciona correctamente
- [ ] Rango de fechas funciona correctamente
- [ ] Ordenamiento funciona correctamente
- [ ] Paginación funciona correctamente
- [ ] Vista vacía aparece cuando no hay empresas seguidas
- [ ] Sugerencias de empresas cargadas en empty-state

### UI & Diseño
- [ ] Sigue 100% AdminLTE v3 (sin inventar CSS)
- [ ] Responsive en mobile (AdminLTE breakpoints)
- [ ] Colores de badges según urgencia correctos
- [ ] Iconos de FontAwesome cargados y visibles
- [ ] Timeline estructura correcta (timeline, time-label, timeline-item)
- [ ] Cards tienen headers con logo, nombre empresa, fecha
- [ ] Metadata mostrada apropiadamente por tipo

### Datos & API
- [ ] Token JWT se obtiene correctamente
- [ ] Headers de Authorization enviados
- [ ] Parámetros de query se pasan correctamente
- [ ] Respuesta de API se renderiza sin errores
- [ ] Manejo de errores HTTP implementado
- [ ] Solo mostrando anuncios PUBLISHED para USER

### Performance
- [ ] Sin múltiples AJAX calls innecesarias
- [ ] Loading states mostrados durante carga
- [ ] Datos cacheados donde aplica
- [ ] Sin console errors o warnings

### Documentación
- [ ] Componentes documentados con @params
- [ ] Funciones JavaScript comentadas
- [ ] Rutas y endpoints documentados

---

## 17. NOTAS ADICIONALES

### Consideraciones de Metadata

1. **MAINTENANCE**:
   - Mostrar duración calculada: `end - start`
   - Mostrar estado: Futuro / En Progreso / Completado
   - Mostrar icono de emergencia si `is_emergency = true`

2. **INCIDENT**:
   - Mostrar tiempo transcurrido desde `started_at`
   - Si `is_resolved = true`, mostrar badge verde
   - Si `is_resolved = false`, mostrar badge amarilla/roja

3. **NEWS**:
   - `target_audience` es array - mostrar como badges (users, agents, admins)
   - `call_to_action` puede tener estructura flexible - validar

4. **ALERT**:
   - `action_required = true` es crítico - resaltar prominentemente
   - `alert_type` determina icono y contexto
   - Mostrar `ended_at` si disponible como límite de tiempo

### Sobre Sugerencias de Empresas

En `empty-state.blade.php`, las sugerencias de empresas pueden:
- Cargarse desde API `/api/companies` con sort por followers
- O ser estáticas si se prefiere performance

---

## 18. REFERENCIAS Y RECURSOS

- AdminLTE v3 Docs: https://adminlte.io/docs/3.1/
- Blade Templating: https://laravel.com/docs/11.x/blade
- jQuery AJAX: https://api.jquery.com/jquery.ajax/
- Specification de Announcements: Ver `AnnouncementSchemaController.php`

---

**Versión del Documento**: 1.0
**Última Actualización**: 2025-01-11
**Estado**: ✅ Listo para Implementación
