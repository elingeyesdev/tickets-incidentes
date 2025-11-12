# REPORTE: Análisis de Vistas del Administrador de Plataforma

**Fecha**: 2025-11-12
**Sistema**: HELPDESK - AdminLTE v3
**Rol Analizado**: PLATFORM_ADMIN (Administrador de Plataforma)

---

## 1. RESUMEN EJECUTIVO

Este reporte documenta el estado actual de las vistas del **Administrador de Plataforma** (PLATFORM_ADMIN), comparando la funcionalidad documentada en las especificaciones con la implementación actual en el sistema HELPDESK basado en AdminLTE v3.

### Estado General
- **Documentación**: 4 pantallas principales especificadas
- **Implementado**: 1 vista principal (Dashboard)
- **Pendiente**: 3 vistas principales + vistas de detalle
- **Cobertura**: ~25% implementado

---

## 2. FUNCIONALIDAD ADMIN SEGÚN DOCUMENTACIÓN

### 2.1. Permisos y Alcance del PLATFORM_ADMIN

Según la documentación (`C:\Users\lukem\Helpdesk\documentacion\idea completa pero no es el mvp.txt`):

```
PLATFORM_ADMIN:
- Gestión completa de empresas y usuarios
- Aprobación de solicitudes de empresa
- Métricas globales del sistema
- Acceso a auditoría completa
```

### 2.2. URL Base de Acceso
- **URL Principal**: `/admin/` o `/app/admin/`
- **Credenciales de Prueba**: admin@helpdesk.com, super@helpdesk.com
- **Rol en BD**: `auth.user_roles` con `role_code = 'platform_admin'`

### 2.3. Pantallas Documentadas

#### PANTALLA 1: Dashboard Principal (`/admin/dashboard`)
**Secciones**:
- **KPIs Globales** (4 tarjetas métricas):
  - Total de Empresas → `COUNT(business.companies)` → Link: `/admin/companies`
  - Usuarios Activos → `COUNT(auth.users WHERE status = 'active')` → Link: `/admin/users`
  - Solicitudes Pendientes → `COUNT(business.company_requests WHERE status = 'pending')` → Link: `/admin/requests`
  - Tickets Totales → `COUNT(ticketing.tickets)` → Link: vista global tickets

- **Gráficos y Tendencias**:
  - Gráfico de empresas por estado (Dona)
  - Tendencia de registros (Líneas - últimos 12 meses)
  - Tabla de actividad del sistema (20 registros)

- **Acciones Rápidas**:
  - Lista de últimas 5 solicitudes pendientes con botones [Ver Detalles], [Aprobar], [Rechazar]

#### PANTALLA 2: Gestión de Empresas (`/admin/companies`)
**Funcionalidades**:
- Filtros: Estado, búsqueda, industria, rango de fechas
- Tabla con columnas:
  - Código (CMP-2025-00001)
  - Nombre + logo
  - Administrador (email + nombre)
  - Industria
  - # Usuarios
  - # Tickets
  - Estado (badge)
  - Fecha registro
  - Acciones: [Ver], [Editar], [Suspender/Activar], [Eliminar]

- **Modal de Detalles**:
  - Pestaña 1: Información General
  - Pestaña 2: Estadísticas
  - Pestaña 3: Configuración

- **Acciones de Gestión**:
  - Suspender/Activar empresa
  - Eliminar empresa (solo si 0 tickets)
  - Botón [+ Nueva Empresa]

#### PANTALLA 3: Gestión de Solicitudes (`/admin/requests`)
**Funcionalidades**:
- Filtros: [Todas], [Pendientes], [Aprobadas], [Rechazadas]
- Layout tipo Card (no tabla)
- Por cada solicitud:
  - request_code (REQ-2025-00001)
  - company_name
  - admin_email
  - industry_type
  - estimated_users
  - Botones: [Ver Detalles], [Aprobar], [Rechazar]

- **Modal de Detalles**: Información completa en 2 columnas
- **Proceso de Aprobación**:
  - Crea empresa en `business.companies`
  - Verifica/crea usuario admin
  - Asigna rol `company_admin`
  - Envía email con credenciales
- **Proceso de Rechazo**:
  - Captura motivo obligatorio
  - Envía email con razón del rechazo

#### PANTALLA 4: Gestión de Usuarios (`/admin/users`)
**Funcionalidades**:
- Filtros avanzados:
  - Búsqueda por email/nombre/código
  - Estado: Activos/Suspendidos/Eliminados
  - Rol: Todos/Platform Admin/Company Admin/Agent/User
  - Empresa (selector)
  - Verificación de email
  - Rango de fechas

- Tabla con columnas:
  - Usuario (código, email, avatar)
  - Nombre completo
  - Roles activos
  - Estado + verificación email
  - Último acceso
  - Empresa principal
  - Fecha registro
  - Acciones: [Ver Perfil], [Suspender], [Eliminar]

- **Modal de Perfil**:
  - Pestaña 1: Información Personal
  - Pestaña 2: Roles y Permisos
  - Pestaña 3: Actividad

---

## 3. VISTAS ACTUALMENTE IMPLEMENTADAS

### 3.1. Estructura de Directorios

```
C:\Users\lukem\Helpdesk\resources\views\
├── app\
│   ├── platform-admin\
│   │   └── dashboard.blade.php ✅ IMPLEMENTADO
│   ├── company-admin\
│   │   └── dashboard.blade.php
│   ├── agent\
│   │   └── dashboard.blade.php
│   ├── user\
│   │   └── dashboard.blade.php
│   ├── shared\
│   │   ├── sidebar.blade.php
│   │   └── navbar.blade.php
│   └── components\
│       └── ...
├── layouts\
│   ├── authenticated.blade.php ✅ Layout AdminLTE v3
│   ├── app.blade.php
│   └── ...
└── ...
```

### 3.2. Vista Principal: Dashboard Platform Admin

**Archivo**: `C:\Users\lukem\Helpdesk\resources\views\app\platform-admin\dashboard.blade.php`

**Ruta Web**: `/app/admin/dashboard`
**Ruta Named**: `dashboard.platform-admin`
**Controller**: `App\Http\Controllers\Dashboard\PlatformAdminController@dashboard`

**Contenido Actual**:
- ✅ 4 KPI Cards (Small Boxes AdminLTE):
  - Total Users (azul info) → Link: `/app/admin/users`
  - Total Companies (verde success) → Link: `/app/admin/companies`
  - Total Tickets (amarillo warning) → Link: `/app/admin/tickets`
  - Pending Company Requests (rojo danger) → Link: `/app/admin/company-requests`

- ✅ Card: Recent Company Requests (tabla estática con datos mock)
- ✅ Card: System Health Status (4 info-boxes: API, Database, Email, Storage)
- ✅ Card: Recent Activity (timeline con eventos mock)

**Estado**: **IMPLEMENTADO PARCIALMENTE** (datos mock, sin integración API)

### 3.3. Controlador Platform Admin

**Archivo**: `C:\Users\lukem\Helpdesk\app\Http\Controllers\Dashboard\PlatformAdminController.php`

**Método `dashboard()`**:
```php
public function dashboard(): View
{
    $user = JWTHelper::getAuthenticatedUser();

    return view('app.platform-admin.dashboard', [
        'user' => $user,
        'stats' => [
            'total_users' => 1250,      // MOCK DATA
            'total_companies' => 45,    // MOCK DATA
            'total_tickets' => 3890,    // MOCK DATA
            'pending_requests' => 8,    // MOCK DATA
        ]
    ]);
}
```

**Estado**: Implementado con datos estáticos. **Requiere integración con API/BD**.

### 3.4. Rutas Web Implementadas

**Archivo**: `C:\Users\lukem\Helpdesk\routes\web.php`

```php
Route::middleware('jwt.require')->prefix('app')->group(function () {
    // Platform Admin Dashboard (PLATFORM_ADMIN role)
    Route::middleware('role:PLATFORM_ADMIN')->prefix('admin')->group(function () {
        Route::get('/dashboard', [PlatformAdminController::class, 'dashboard'])
            ->name('dashboard.platform-admin');
    });
});
```

**Ruta única implementada**:
- `GET /app/admin/dashboard` → PlatformAdminController@dashboard

### 3.5. Sidebar Navigation (Menú Lateral)

**Archivo**: `C:\Users\lukem\Helpdesk\resources\views\app\shared\sidebar.blade.php`

**Menú Platform Admin** (Alpine.js template):
```html
<template x-if="activeRole === 'PLATFORM_ADMIN'">
    <div>
        <li class="nav-header">SYSTEM MANAGEMENT</li>
        <li class="nav-item">
            <a href="/app/admin/users" class="nav-link">
                <i class="nav-icon fas fa-users"></i>
                <p>Users</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="/app/admin/companies" class="nav-link">
                <i class="nav-icon fas fa-building"></i>
                <p>Companies</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="/app/admin/company-requests" class="nav-link">
                <i class="nav-icon fas fa-file-invoice"></i>
                <p>Company Requests <span class="badge badge-warning right">8</span></p>
            </a>
        </li>
        <li class="nav-item">
            <a href="/app/admin/settings" class="nav-link">
                <i class="nav-icon fas fa-cogs"></i>
                <p>System Settings</p>
            </a>
        </li>
    </div>
</template>
```

**Estado**: Menú definido pero **enlaces apuntan a rutas no implementadas** (404).

---

## 4. VISTAS FALTANTES A IMPLEMENTAR

### 4.1. Vistas Principales Pendientes

| # | Vista | Ruta Web Propuesta | Prioridad | Complejidad |
|---|-------|-------------------|-----------|-------------|
| 1 | **Gestión de Empresas** | `/app/admin/companies` | 🔴 ALTA | Media |
| 2 | **Gestión de Solicitudes** | `/app/admin/company-requests` | 🔴 ALTA | Alta |
| 3 | **Gestión de Usuarios** | `/app/admin/users` | 🟡 MEDIA | Alta |
| 4 | **Configuración de Sistema** | `/app/admin/settings` | 🟢 BAJA | Media |
| 5 | **Tickets Globales** | `/app/admin/tickets` | 🟡 MEDIA | Alta |

### 4.2. Vistas de Detalle y Modales Pendientes

| Vista Principal | Componentes Faltantes |
|----------------|----------------------|
| **Companies** | Modal de detalles (3 pestañas), Modal de creación, Modal de edición, Modal de confirmación suspender/eliminar |
| **Company Requests** | Modal de detalles completo, Modal de aprobación, Modal de rechazo con campo motivo |
| **Users** | Modal de perfil (3 pestañas), Modal de asignación de roles, Modal de confirmación suspender/eliminar |
| **Dashboard** | Gráficos interactivos (Chart.js), Tabla de actividad con paginación |

### 4.3. Componentes Compartidos Faltantes

- Filtros avanzados expandibles
- DataTables con ordenamiento y paginación
- Búsqueda con autocompletado
- Selector de fechas (daterangepicker)
- Exportación a CSV/Excel
- Toast notifications
- Confirmación de acciones destructivas
- Skeleton loaders para carga

---

## 5. ESTRUCTURA PROPUESTA DE VISTAS ADMINLTE V3

### 5.1. Árbol de Archivos Propuesto

```
C:\Users\lukem\Helpdesk\resources\views\app\platform-admin\
├── dashboard.blade.php                        ✅ EXISTENTE (mejorar)
│
├── companies\
│   ├── index.blade.php                        ❌ CREAR
│   ├── _table.blade.php                       ❌ CREAR (partial)
│   ├── _filters.blade.php                     ❌ CREAR (partial)
│   ├── _modal-details.blade.php               ❌ CREAR (modal)
│   ├── _modal-create.blade.php                ❌ CREAR (modal)
│   └── _modal-confirm-delete.blade.php        ❌ CREAR (modal)
│
├── company-requests\
│   ├── index.blade.php                        ❌ CREAR
│   ├── _card-item.blade.php                   ❌ CREAR (partial)
│   ├── _modal-details.blade.php               ❌ CREAR (modal)
│   ├── _modal-approve.blade.php               ❌ CREAR (modal)
│   └── _modal-reject.blade.php                ❌ CREAR (modal)
│
├── users\
│   ├── index.blade.php                        ❌ CREAR
│   ├── _table.blade.php                       ❌ CREAR (partial)
│   ├── _filters-advanced.blade.php            ❌ CREAR (partial)
│   ├── _modal-profile.blade.php               ❌ CREAR (modal - 3 tabs)
│   └── _modal-assign-role.blade.php           ❌ CREAR (modal)
│
├── tickets\
│   ├── index.blade.php                        ❌ CREAR
│   └── _filters-sidebar.blade.php             ❌ CREAR (partial)
│
└── settings\
    ├── index.blade.php                        ❌ CREAR
    ├── _general.blade.php                     ❌ CREAR (tab)
    ├── _security.blade.php                    ❌ CREAR (tab)
    └── _email.blade.php                       ❌ CREAR (tab)
```

### 5.2. Componentes Compartidos a Crear

```
C:\Users\lukem\Helpdesk\resources\views\app\components\
├── datatables\
│   ├── table-wrapper.blade.php                ❌ CREAR
│   ├── pagination.blade.php                   ❌ CREAR
│   └── search-bar.blade.php                   ❌ CREAR
│
├── filters\
│   ├── date-range.blade.php                   ❌ CREAR
│   ├── status-selector.blade.php              ❌ CREAR
│   └── multi-select.blade.php                 ❌ CREAR
│
├── modals\
│   ├── confirm-action.blade.php               ❌ CREAR
│   └── base-modal.blade.php                   ❌ CREAR
│
└── charts\
    ├── donut-chart.blade.php                  ❌ CREAR
    ├── line-chart.blade.php                   ❌ CREAR
    └── bar-chart.blade.php                    ❌ CREAR
```

### 5.3. Patrón de Diseño AdminLTE v3

Todas las vistas deben seguir esta estructura:

```blade
@extends('layouts.authenticated')

@section('title', 'Título de la Página')

@section('content_header', 'Título Principal')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.platform-admin') }}">Admin</a></li>
    <li class="breadcrumb-item active">Título</li>
@endsection

@section('css')
    <!-- CSS específico de la página -->
@endsection

@section('content')
<div class="row">
    <!-- Contenido de la página -->
    <!-- Usar Cards de AdminLTE -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Título del Card</h3>
                <div class="card-tools">
                    <!-- Botones de herramientas -->
                </div>
            </div>
            <div class="card-body">
                <!-- Contenido -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <!-- JavaScript específico de la página -->
@endsection
```

### 5.4. Componentes AdminLTE v3 a Utilizar

- **Small Box**: KPIs y métricas principales
- **Info Box**: Estadísticas secundarias
- **Card**: Contenedor principal de contenido
- **DataTables**: Tablas con paginación y búsqueda
- **Chart.js**: Gráficos (dona, líneas, barras)
- **Timeline**: Eventos y actividad
- **Modal**: Diálogos y formularios
- **Select2**: Selectores mejorados
- **DateRangePicker**: Selector de fechas
- **SweetAlert2**: Confirmaciones y alertas
- **Toastr**: Notificaciones toast

---

## 6. RELACIÓN: VISTA → ENDPOINT API

### 6.1. Dashboard Platform Admin

| Elemento Vista | Endpoint API | Método | Implementado |
|---------------|-------------|--------|--------------|
| Total Users | `/api/users?count_only=true` | GET | ✅ Parcial |
| Total Companies | `/api/companies?count_only=true` | GET | ✅ Parcial |
| Total Tickets | `/api/tickets?count_only=true` | GET | ❌ Falta |
| Pending Requests | `/api/company-requests?status=pending&count_only=true` | GET | ✅ Parcial |
| Recent Requests (5) | `/api/company-requests?status=pending&limit=5&sort=-created_at` | GET | ✅ |
| Activity Log | `/api/admin/activity-log?limit=20` | GET | ❌ Falta |
| System Health | `/api/admin/system-health` | GET | ❌ Falta |

**Endpoints Existentes**:
- ✅ `GET /api/users` → `UserController@index` (filtros: role, company, status)
- ✅ `GET /api/companies` → `CompanyController@index` (requiere role PLATFORM_ADMIN o COMPANY_ADMIN)
- ✅ `GET /api/company-requests` → `CompanyRequestController@index` (requiere role PLATFORM_ADMIN)

### 6.2. Gestión de Empresas

| Acción Vista | Endpoint API | Método | Implementado |
|-------------|-------------|--------|--------------|
| Listar empresas | `/api/companies` | GET | ✅ |
| Ver detalles | `/api/companies/{id}` | GET | ✅ |
| Crear empresa | `/api/companies` | POST | ✅ |
| Actualizar empresa | `/api/companies/{id}` | PUT/PATCH | ✅ |
| Suspender empresa | `/api/companies/{id}/suspend` | POST | ❌ Falta |
| Activar empresa | `/api/companies/{id}/activate` | POST | ❌ Falta |
| Eliminar empresa | `/api/companies/{id}` | DELETE | ❌ Falta |
| Estadísticas empresa | `/api/companies/{id}/stats` | GET | ❌ Falta |

**Policy**: `CompanyPolicy` controla acceso (PLATFORM_ADMIN full access, COMPANY_ADMIN solo su empresa)

### 6.3. Gestión de Solicitudes

| Acción Vista | Endpoint API | Método | Implementado |
|-------------|-------------|--------|--------------|
| Listar solicitudes | `/api/company-requests` | GET | ✅ |
| Ver detalles | `/api/company-requests/{id}` | GET | ✅ |
| Aprobar solicitud | `/api/company-requests/{id}/approve` | POST | ✅ |
| Rechazar solicitud | `/api/company-requests/{id}/reject` | POST | ✅ |

**Controller**: `CompanyRequestAdminController` (approve, reject)

**Proceso de Aprobación** (según código):
1. Valida que status = 'pending'
2. Crea empresa en `business.companies`
3. Verifica si admin_email existe en `auth.users`
4. Si existe: asigna rol `COMPANY_ADMIN`
5. Si no existe: crea usuario + perfil + rol
6. Genera password temporal
7. Envía email de bienvenida
8. Actualiza solicitud: status = 'approved'

### 6.4. Gestión de Usuarios

| Acción Vista | Endpoint API | Método | Implementado |
|-------------|-------------|--------|--------------|
| Listar usuarios | `/api/users` | GET | ✅ |
| Ver usuario | `/api/users/{id}` | GET | ✅ |
| Ver perfil | `/api/users/{id}/profile` | GET | ✅ (solo /users/me/profile) |
| Actualizar estado | `/api/users/{id}/status` | PUT | ✅ |
| Asignar rol | `/api/users/{userId}/roles` | POST | ✅ |
| Remover rol | `/api/users/roles/{roleId}` | DELETE | ✅ |
| Eliminar usuario | `/api/users/{id}` | DELETE | ✅ |
| Listar roles | `/api/roles` | GET | ✅ |
| Sesiones activas | `/api/auth/sessions` | GET | ✅ |
| Revocar sesión | `/api/auth/sessions/{id}` | DELETE | ✅ |

**Middleware**: `role:PLATFORM_ADMIN` (exclusivo) o `role:PLATFORM_ADMIN,COMPANY_ADMIN` (compartido)

### 6.5. Endpoints Faltantes

| Endpoint | Método | Descripción | Prioridad |
|----------|--------|-------------|-----------|
| `/api/tickets` | GET | Listar todos los tickets (global) | 🔴 ALTA |
| `/api/admin/activity-log` | GET | Log de actividad del sistema | 🟡 MEDIA |
| `/api/admin/system-health` | GET | Estado de servicios (DB, cache, email, storage) | 🟢 BAJA |
| `/api/companies/{id}/suspend` | POST | Suspender empresa | 🔴 ALTA |
| `/api/companies/{id}/activate` | POST | Activar empresa | 🔴 ALTA |
| `/api/companies/{id}` | DELETE | Eliminar empresa (solo si 0 tickets) | 🟡 MEDIA |
| `/api/companies/{id}/stats` | GET | Estadísticas detalladas de empresa | 🟡 MEDIA |
| `/api/users/{id}/activity` | GET | Historial de actividad de usuario | 🟢 BAJA |
| `/api/admin/metrics/growth` | GET | Métricas de crecimiento (usuarios, empresas) | 🟢 BAJA |
| `/api/admin/metrics/tickets` | GET | Métricas de tickets por empresa | 🟢 BAJA |

---

## 7. PLAN DE IMPLEMENTACIÓN PROPUESTO

### Fase 1: Dashboard Mejorado (1-2 días)
**Objetivo**: Completar dashboard con datos reales

**Tareas**:
1. Crear endpoint `/api/admin/dashboard-stats` que retorne:
   - Count real de users, companies, tickets, pending_requests
   - Crecimiento mensual (% change)
   - Últimas 5 solicitudes pendientes (datos reales)
2. Integrar Alpine.js para cargar datos vía API
3. Agregar skeleton loaders durante carga
4. Implementar gráficos Chart.js:
   - Donut: Empresas por estado
   - Line: Tendencia últimos 12 meses
5. Tabla de actividad con datos de `audit_logs` o eventos del sistema

### Fase 2: Gestión de Solicitudes (2-3 días)
**Objetivo**: Vista completa de company-requests

**Tareas**:
1. Crear `resources/views/app/platform-admin/company-requests/index.blade.php`
2. Layout tipo Card (según especificación)
3. Filtros: Todas/Pendientes/Aprobadas/Rechazadas
4. Modales:
   - Detalles completos (2 columnas)
   - Aprobar (con checkbox enviar email)
   - Rechazar (con campo motivo obligatorio)
5. Integrar con endpoints existentes:
   - `GET /api/company-requests`
   - `POST /api/company-requests/{id}/approve`
   - `POST /api/company-requests/{id}/reject`
6. Agregar ruta web: `GET /app/admin/company-requests`

### Fase 3: Gestión de Empresas (3-4 días)
**Objetivo**: Vista completa de companies

**Tareas**:
1. Crear `resources/views/app/platform-admin/companies/index.blade.php`
2. Implementar DataTables con filtros:
   - Estado: Todas/Activas/Suspendidas
   - Búsqueda por nombre/código
   - Industria (selector)
   - Rango de fechas
3. Modales:
   - Detalles (3 pestañas: Info General, Estadísticas, Configuración)
   - Crear empresa manual
   - Confirmar suspender/activar
   - Confirmar eliminar (solo si 0 tickets)
4. Crear endpoints faltantes:
   - `POST /api/companies/{id}/suspend`
   - `POST /api/companies/{id}/activate`
   - `DELETE /api/companies/{id}`
   - `GET /api/companies/{id}/stats`
5. Agregar ruta web: `GET /app/admin/companies`

### Fase 4: Gestión de Usuarios (3-4 días)
**Objetivo**: Vista completa de users

**Tareas**:
1. Crear `resources/views/app/platform-admin/users/index.blade.php`
2. Filtros avanzados expandibles:
   - Búsqueda por email/nombre/código
   - Estado, rol, empresa, verificación email
   - Rango de fechas
3. DataTables con columnas especificadas
4. Modales:
   - Perfil completo (3 pestañas: Personal, Roles, Actividad)
   - Asignar rol
   - Confirmar suspender/eliminar
5. Integrar con endpoints existentes (ya implementados)
6. Agregar ruta web: `GET /app/admin/users`

### Fase 5: Extras y Mejoras (2-3 días)
**Tareas**:
1. Vista de tickets globales (`/app/admin/tickets`)
2. Vista de configuración de sistema (`/app/admin/settings`)
3. Componentes compartidos reutilizables
4. Exportación a CSV/Excel
5. Toast notifications con Toastr
6. Mejoras UX: skeleton loaders, animaciones, feedback visual

---

## 8. COMPONENTES Y LIBRERÍAS REQUERIDAS

### 8.1. AdminLTE v3 (Ya incluido)
- ✅ AdminLTE CSS/JS
- ✅ Font Awesome
- ✅ Bootstrap 4
- ✅ Select2

### 8.2. A Incluir

**DataTables**:
```html
<!-- CSS -->
<link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">

<!-- JS -->
<script src="{{ asset('vendor/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
```

**Chart.js**:
```html
<script src="{{ asset('vendor/adminlte/plugins/chart.js/Chart.min.js') }}"></script>
```

**DateRangePicker**:
```html
<link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/daterangepicker/daterangepicker.css') }}">
<script src="{{ asset('vendor/adminlte/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/plugins/daterangepicker/daterangepicker.js') }}"></script>
```

**SweetAlert2**:
```html
<script src="{{ asset('vendor/adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
```

**Toastr**:
```html
<link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/toastr/toastr.min.css') }}">
<script src="{{ asset('vendor/adminlte/plugins/toastr/toastr.min.js') }}"></script>
```

### 8.3. Alpine.js (Ya incluido)
- ✅ Incluido vía CDN en `layouts/authenticated.blade.php`
- Usar para interactividad sin escribir mucho JS vanilla

---

## 9. EJEMPLO: VISTA DE GESTIÓN DE EMPRESAS

### 9.1. Vista Principal (index.blade.php)

```blade
@extends('layouts.authenticated')

@section('title', 'Company Management')

@section('content_header', 'Company Management')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('dashboard.platform-admin') }}">Admin</a></li>
    <li class="breadcrumb-item active">Companies</li>
@endsection

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Companies</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalCreateCompany">
                        <i class="fas fa-plus"></i> New Company
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-control" id="filterStatus">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filterIndustry">
                            <option value="">All Industries</option>
                            <!-- Cargar dinámicamente -->
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="searchCompany" placeholder="Search by name or code...">
                    </div>
                </div>

                <!-- Tabla -->
                <table id="companiesTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Admin</th>
                            <th>Industry</th>
                            <th>Users</th>
                            <th>Tickets</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Cargado vía DataTables Ajax -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('app.platform-admin.companies._modal-details')
@include('app.platform-admin.companies._modal-create')
@include('app.platform-admin.companies._modal-confirm-delete')
@endsection

@section('js')
<script src="{{ asset('vendor/adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(function() {
    // DataTables con Ajax
    $('#companiesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/api/companies',
            headers: {
                'Authorization': 'Bearer ' + window.tokenManager.getAccessToken()
            }
        },
        columns: [
            { data: 'company_code' },
            { data: 'name' },
            { data: 'admin_email' },
            { data: 'industry_type' },
            { data: 'users_count' },
            { data: 'tickets_count' },
            {
                data: 'status',
                render: function(data) {
                    return data === 'active'
                        ? '<span class="badge badge-success">Active</span>'
                        : '<span class="badge badge-danger">Suspended</span>';
                }
            },
            { data: 'created_at' },
            {
                data: 'id',
                orderable: false,
                render: function(data) {
                    return `
                        <button class="btn btn-info btn-sm" onclick="viewCompany(${data})">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-warning btn-sm" onclick="toggleStatus(${data})">
                            <i class="fas fa-ban"></i>
                        </button>
                    `;
                }
            }
        ]
    });
});

function viewCompany(id) {
    // Cargar modal con detalles
}

function toggleStatus(id) {
    // Suspender/Activar empresa
}
</script>
@endsection
```

### 9.2. Modal de Detalles (_modal-details.blade.php)

```blade
<!-- Modal Company Details -->
<div class="modal fade" id="modalCompanyDetails" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Company Details</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="companyTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tabGeneral">General Info</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tabStats">Statistics</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tabConfig">Configuration</a>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="tabGeneral">
                        <!-- Información general de la empresa -->
                    </div>
                    <div class="tab-pane fade" id="tabStats">
                        <!-- Gráficos y estadísticas -->
                    </div>
                    <div class="tab-pane fade" id="tabConfig">
                        <!-- Categorías, macros, artículos -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
```

---

## 10. CONCLUSIONES Y RECOMENDACIONES

### 10.1. Estado Actual
- ✅ **Infraestructura base**: AdminLTE v3 correctamente integrado
- ✅ **Layout authenticated**: Funcional con sidebar dinámico
- ✅ **Dashboard básico**: Implementado con datos mock
- ✅ **API Backend**: 80% de endpoints necesarios ya implementados
- ❌ **Vistas admin**: Solo 25% completado
- ❌ **Integración API-Vista**: Pendiente en todas las vistas

### 10.2. Recomendaciones Prioritarias

1. **Prioridad ALTA - Gestión de Solicitudes**:
   - Es el flujo crítico del negocio (onboarding de empresas)
   - Backend completamente implementado
   - Solo falta la vista frontend
   - Estimación: 2-3 días

2. **Prioridad ALTA - Gestión de Empresas**:
   - Segunda funcionalidad más crítica
   - Requiere algunos endpoints adicionales (suspend/activate/delete)
   - Estimación: 3-4 días

3. **Prioridad MEDIA - Dashboard Mejorado**:
   - Reemplazar datos mock por API reales
   - Agregar gráficos Chart.js
   - Estimación: 1-2 días

4. **Prioridad MEDIA - Gestión de Usuarios**:
   - Backend completo
   - Solo falta vista con filtros complejos
   - Estimación: 3-4 días

### 10.3. Mejores Prácticas

1. **Reutilización de Componentes**: Crear partials blade reutilizables
2. **Alpine.js para Interactividad**: Preferir Alpine.js sobre jQuery para lógica simple
3. **DataTables Server-Side**: Implementar paginación en servidor para grandes volúmenes
4. **Validación Client + Server**: Doble validación para mejor UX
5. **Skeleton Loaders**: Mejorar percepción de velocidad durante cargas
6. **Toast Notifications**: Feedback visual consistente en todas las acciones
7. **Confirmaciones**: Usar SweetAlert2 para acciones destructivas
8. **Responsive Design**: Todas las vistas deben funcionar en mobile (AdminLTE es responsive)

### 10.4. Tiempo Estimado Total

| Fase | Tareas | Días |
|------|--------|------|
| Fase 1: Dashboard Mejorado | Integración API, gráficos | 1-2 |
| Fase 2: Gestión de Solicitudes | Vista completa + modales | 2-3 |
| Fase 3: Gestión de Empresas | Vista + endpoints faltantes | 3-4 |
| Fase 4: Gestión de Usuarios | Vista + filtros avanzados | 3-4 |
| Fase 5: Extras y Mejoras | Tickets, settings, componentes | 2-3 |
| **TOTAL** | | **11-16 días** |

---

## ANEXO A: Checklist de Implementación

### Dashboard Platform Admin
- [ ] Integrar endpoint `/api/admin/dashboard-stats`
- [ ] Cargar KPIs con datos reales vía Alpine.js
- [ ] Implementar gráfico donut (empresas por estado)
- [ ] Implementar gráfico línea (tendencia 12 meses)
- [ ] Tabla de actividad con datos reales
- [ ] Recent requests con datos API
- [ ] Skeleton loaders durante carga

### Gestión de Solicitudes
- [ ] Crear vista `company-requests/index.blade.php`
- [ ] Layout tipo Card según especificación
- [ ] Filtros: Todas/Pendientes/Aprobadas/Rechazadas
- [ ] Modal de detalles (2 columnas)
- [ ] Modal de aprobación (con checkbox email)
- [ ] Modal de rechazo (con campo motivo)
- [ ] Integrar API `GET /api/company-requests`
- [ ] Integrar API `POST /api/company-requests/{id}/approve`
- [ ] Integrar API `POST /api/company-requests/{id}/reject`
- [ ] Agregar ruta web `GET /app/admin/company-requests`
- [ ] Toast notifications para feedback

### Gestión de Empresas
- [ ] Crear vista `companies/index.blade.php`
- [ ] DataTables con Ajax server-side
- [ ] Filtros: Estado, Industria, Búsqueda, Fechas
- [ ] Modal detalles (3 pestañas)
- [ ] Modal crear empresa
- [ ] Modal confirmar suspender/activar
- [ ] Modal confirmar eliminar
- [ ] Crear endpoint `POST /api/companies/{id}/suspend`
- [ ] Crear endpoint `POST /api/companies/{id}/activate`
- [ ] Crear endpoint `DELETE /api/companies/{id}`
- [ ] Crear endpoint `GET /api/companies/{id}/stats`
- [ ] Agregar ruta web `GET /app/admin/companies`
- [ ] Exportación a CSV/Excel

### Gestión de Usuarios
- [ ] Crear vista `users/index.blade.php`
- [ ] DataTables con Ajax server-side
- [ ] Filtros avanzados expandibles
- [ ] Modal perfil (3 pestañas)
- [ ] Modal asignar rol
- [ ] Modal confirmar suspender/eliminar
- [ ] Integrar endpoints existentes
- [ ] Agregar ruta web `GET /app/admin/users`
- [ ] Visualización de sesiones activas

---

**FIN DEL REPORTE**

Generado el: 2025-11-12
Ruta del archivo: `C:\Users\lukem\Helpdesk\REPORTE_vistas_admin.md`
