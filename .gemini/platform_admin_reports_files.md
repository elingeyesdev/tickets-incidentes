# 📊 Archivos de Reportes - Platform Admin

**Fecha:** 2025-12-13  
**Rol:** PLATFORM_ADMIN  
**Objetivo:** Identificar archivos exclusivos del módulo de reportes de Platform Admin

---

## 🎯 Resumen

El Platform Admin tiene acceso a **3 reportes específicos**:
1. Reporte de Empresas
2. Reporte de Crecimiento de Plataforma
3. Reporte de Solicitudes de Empresa

---

## 📁 Archivos Exclusivos del Platform Admin

### 🎮 **1. Controlador**

| Archivo | Ubicación | Descripción |
|---------|-----------|-------------|
| `PlatformReportController.php` | `app/Features/Reports/Http/Controllers/` | Controlador principal que maneja todos los reportes del Platform Admin |

**Métodos principales:**
- `index()` - Muestra la vista principal de reportes
- `companiesExcel()` - Descarga reporte de empresas en Excel
- `companiesPdf()` - Descarga reporte de empresas en PDF
- `growthExcel()` - Descarga reporte de crecimiento en Excel
- `growthPdf()` - Descarga reporte de crecimiento en PDF
- `requestsExcel()` - Descarga reporte de solicitudes en Excel
- `requestsPdf()` - Descarga reporte de solicitudes en PDF

---

### 🖼️ **2. Vista Principal**

| Archivo | Ubicación | Descripción |
|---------|-----------|-------------|
| `index.blade.php` | `resources/views/app/platform-admin/reports/` | Dashboard principal de reportes con 3 tarjetas interactivas |

**Características:**
- 3 tarjetas de reportes (Empresas, Crecimiento, Solicitudes)
- Filtros dinámicos por estado y periodo
- Botones de descarga Excel/PDF
- Estadísticas rápidas en tiempo real
- Integración con AdminLTE

**Ruta:** `/app/admin/reports`

---

### 📄 **3. Templates PDF (3 archivos)**

| Archivo | Ubicación | Propósito |
|---------|-----------|-----------|
| `companies-pdf.blade.php` | `resources/views/app/platform-admin/reports/templates/` | Template para generar PDF de empresas |
| `growth-pdf.blade.php` | `resources/views/app/platform-admin/reports/templates/` | Template para generar PDF de crecimiento |
| `requests-pdf.blade.php` | `resources/views/app/platform-admin/reports/templates/` | Template para generar PDF de solicitudes |

**Orientación:**
- `companies-pdf.blade.php` → **Landscape** (horizontal)
- `growth-pdf.blade.php` → **Portrait** (vertical)
- `requests-pdf.blade.php` → **Landscape** (horizontal)

---

### 📊 **4. Exportadores Excel (3 archivos)**

| Archivo | Ubicación | Propósito |
|---------|-----------|-----------|
| `CompaniesExport.php` | `app/Features/Reports/Exports/` | Exporta listado de empresas a Excel |
| `PlatformGrowthExport.php` | `app/Features/Reports/Exports/` | Exporta estadísticas de crecimiento a Excel |
| `CompanyRequestsExport.php` | `app/Features/Reports/Exports/` | Exporta solicitudes de empresa a Excel |

**Utiliza:** Librería `maatwebsite/excel` (Laravel Excel)

---

## 📋 Lista Completa de Archivos

### **TOTAL: 8 archivos**

```
1. app/Features/Reports/Http/Controllers/PlatformReportController.php
2. resources/views/app/platform-admin/reports/index.blade.php
3. resources/views/app/platform-admin/reports/templates/companies-pdf.blade.php
4. resources/views/app/platform-admin/reports/templates/growth-pdf.blade.php
5. resources/views/app/platform-admin/reports/templates/requests-pdf.blade.php
6. app/Features/Reports/Exports/CompaniesExport.php
7. app/Features/Reports/Exports/PlatformGrowthExport.php
8. app/Features/Reports/Exports/CompanyRequestsExport.php
```

---

## 🛤️ Rutas Web Asociadas

**Archivo:** `routes/web.php` (líneas 259-274)

```php
// Vista principal de reportes
Route::get('/app/admin/reports', [PlatformReportController::class, 'index'])
    ->name('admin.reports.index');

// Reporte de Empresas
Route::get('/app/admin/reports/companies/excel', [PlatformReportController::class, 'companiesExcel'])
    ->name('admin.reports.companies.excel');
Route::get('/app/admin/reports/companies/pdf', [PlatformReportController::class, 'companiesPdf'])
    ->name('admin.reports.companies.pdf');

// Reporte de Crecimiento
Route::get('/app/admin/reports/growth/excel', [PlatformReportController::class, 'growthExcel'])
    ->name('admin.reports.growth.excel');
Route::get('/app/admin/reports/growth/pdf', [PlatformReportController::class, 'growthPdf'])
    ->name('admin.reports.growth.pdf');

// Reporte de Solicitudes
Route::get('/app/admin/reports/requests/excel', [PlatformReportController::class, 'requestsExcel'])
    ->name('admin.reports.requests.excel');
Route::get('/app/admin/reports/requests/pdf', [PlatformReportController::class, 'requestsPdf'])
    ->name('admin.reports.requests.pdf');
```

**Middleware:** `spatie.active_role:PLATFORM_ADMIN`

---

## 🎨 Detalle de Reportes

### 📊 **1. Reporte de Empresas**

**Descripción:** Listado completo de todas las empresas registradas en la plataforma

**Incluye:**
- Código de empresa
- Nombre
- Email de contacto
- Industria
- Estado (activa/suspendida)
- Cantidad de agentes
- Cantidad de tickets

**Filtros:**
- Estado: Todas / Solo Activas / Solo Suspendidas

**Formatos:** Excel, PDF

**Archivos involucrados:**
- Vista: `index.blade.php`
- Controlador: `PlatformReportController::companiesExcel()`, `companiesPdf()`
- Excel: `CompaniesExport.php`
- PDF: `companies-pdf.blade.php`

---

### 📈 **2. Reporte de Crecimiento de Plataforma**

**Descripción:** Estadísticas de crecimiento mensual de la plataforma

**Incluye:**
- Nuevas empresas por mes
- Nuevos usuarios por mes
- Nuevos tickets por mes
- Resumen general con totales

**Filtros:**
- Periodo: Últimos 3, 6 o 12 meses

**Formatos:** Excel, PDF

**Archivos involucrados:**
- Vista: `index.blade.php`
- Controlador: `PlatformReportController::growthExcel()`, `growthPdf()`, `gatherGrowthData()`
- Excel: `PlatformGrowthExport.php`
- PDF: `growth-pdf.blade.php`

**Datos del resumen:**
- Total de empresas
- Total de usuarios
- Total de tickets
- Empresas activas
- Solicitudes pendientes
- Nuevas empresas en el periodo
- Nuevos usuarios en el periodo
- Nuevos tickets en el periodo

---

### 📥 **3. Reporte de Solicitudes de Empresa**

**Descripción:** Historial de solicitudes de registro de empresas

**Incluye:**
- Nombre de empresa solicitada
- Email del solicitante
- Nombre del administrador
- Fecha de solicitud
- Estado (pendiente/aprobada/rechazada)
- Revisor (quién procesó la solicitud)
- Empresa creada (si fue aprobada)

**Filtros:**
- Estado: Todas / Pendientes / Aprobadas / Rechazadas

**Formatos:** Excel, PDF

**Archivos involucrados:**
- Vista: `index.blade.php`
- Controlador: `PlatformReportController::requestsExcel()`, `requestsPdf()`
- Excel: `CompanyRequestsExport.php`
- PDF: `requests-pdf.blade.php`

---

## 🔗 Dependencias con Otros Sistemas

### Modelos Utilizados
```php
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyRequest;
use App\Features\UserManagement\Models\User;
use App\Features\TicketManagement\Models\Ticket;
```

### Librerías
- `Maatwebsite\Excel\Facades\Excel` - Exportación a Excel
- `Barryvdh\DomPDF\Facade\Pdf` - Generación de PDFs

### API Endpoints Utilizados
- `/api/analytics/platform-dashboard` - Para estadísticas en tiempo real en la vista

---

## 🔐 Seguridad y Permisos

- ✅ **Middleware:** `jwt.require` + `spatie.active_role:PLATFORM_ADMIN`
- ✅ **Autorización:** Solo usuarios con rol PLATFORM_ADMIN pueden acceder
- ✅ **Rutas protegidas:** Todas las rutas requieren autenticación
- ✅ **Datos sensibles:** Solo el Platform Admin puede ver datos de todas las empresas

---

## 📝 Notas Técnicas

1. **Nombres de archivos:** Incluyen timestamp para evitar colisiones
   - Formato: `{tipo}_{fecha}_{hora}.{ext}`
   - Ejemplo: `empresas_2025-12-13_221500.xlsx`

2. **Generación sincrónica:** Los reportes se generan en tiempo real al hacer clic

3. **Sin caché:** Los datos siempre son actuales de la base de datos

4. **Orientación PDF:**
   - Empresas y Solicitudes: Landscape (más columnas)
   - Crecimiento: Portrait (gráficos verticales)

5. **Estadísticas en vivo:** La vista principal carga KPIs del dashboard via AJAX

---

## 🎯 Resumen Final

**8 archivos exclusivos** conforman el módulo de reportes del Platform Admin:

- **1 Controlador** - Lógica de negocio
- **1 Vista Blade** - Interfaz de usuario
- **3 Templates PDF** - Diseño de PDFs
- **3 Exportadores** - Generación de Excel

Todos ubicados en:
- `app/Features/Reports/` (backend)
- `resources/views/app/platform-admin/reports/` (frontend)
