# 📊 Investigación Completa: Sistema de Reportes

**Fecha:** 2025-12-13  
**Objetivo:** Identificar todos los archivos que conforman el sistema de reportes del Helpdesk

---

## 🎯 Resumen Ejecutivo

El sistema de reportes está organizado por **roles** y consta de **4 módulos principales**:

1. **Platform Admin Reports** - Reportes a nivel de plataforma
2. **Company Admin Reports** - Reportes a nivel de empresa
3. **Agent Reports** - Reportes individuales de agentes
4. **User Reports** - Reportes de usuario final

Cada módulo cuenta con:
- ✅ Controladores específicos
- ✅ Vistas Blade (HTML)
- ✅ Templates PDF
- ✅ Exportadores Excel
- ✅ Rutas web específicas

---

## 📁 Estructura de Carpetas

```
Helpdesk/
├── app/Features/Reports/
│   ├── Http/Controllers/
│   │   ├── PlatformReportController.php
│   │   ├── CompanyReportController.php
│   │   ├── AgentReportController.php
│   │   ├── UserReportController.php
│   │   └── TicketChatExportController.php
│   └── Exports/
│       ├── CompaniesExport.php
│       ├── CompanyRequestsExport.php
│       ├── PlatformGrowthExport.php
│       ├── CompanyTicketsExport.php
│       ├── AgentTicketsExport.php
│       ├── AgentsPerformanceExport.php
│       └── UserTicketsExport.php
│
└── resources/views/app/
    ├── platform-admin/reports/
    │   ├── index.blade.php
    │   └── templates/
    │       ├── companies-pdf.blade.php
    │       ├── growth-pdf.blade.php
    │       └── requests-pdf.blade.php
    │
    ├── company-admin/reports/
    │   ├── tickets.blade.php
    │   ├── agents.blade.php
    │   ├── summary.blade.php
    │   ├── company.blade.php
    │   └── templates/
    │       ├── tickets-pdf.blade.php
    │       ├── agents-pdf.blade.php
    │       ├── summary-pdf.blade.php
    │       └── company-pdf.blade.php
    │
    ├── agent/reports/
    │   ├── tickets.blade.php
    │   ├── performance.blade.php
    │   └── templates/
    │       ├── tickets-pdf.blade.php
    │       └── performance-pdf.blade.php
    │
    └── user/reports/
        ├── tickets.blade.php
        ├── activity.blade.php
        └── templates/
            ├── tickets-pdf.blade.php
            └── activity-pdf.blade.php
```

---

## 🔍 Detalle por Módulo

### 1️⃣ **PLATFORM ADMIN REPORTS** (Reportes de Plataforma)

#### 📄 Archivos Principales

| Tipo | Archivo | Ubicación | Descripción |
|------|---------|-----------|-------------|
| **Controlador** | `PlatformReportController.php` | `app/Features/Reports/Http/Controllers/` | Controlador principal para reportes de Platform Admin |
| **Vista Principal** | `index.blade.php` | `resources/views/app/platform-admin/reports/` | Dashboard de reportes |
| **Template PDF** | `companies-pdf.blade.php` | `resources/views/app/platform-admin/reports/templates/` | Template PDF para reporte de empresas |
| **Template PDF** | `growth-pdf.blade.php` | `resources/views/app/platform-admin/reports/templates/` | Template PDF para reporte de crecimiento |
| **Template PDF** | `requests-pdf.blade.php` | `resources/views/app/platform-admin/reports/templates/` | Template PDF para solicitudes de empresa |
| **Exportador** | `CompaniesExport.php` | `app/Features/Reports/Exports/` | Exporta listado de empresas a Excel |
| **Exportador** | `PlatformGrowthExport.php` | `app/Features/Reports/Exports/` | Exporta estadísticas de crecimiento a Excel |
| **Exportador** | `CompanyRequestsExport.php` | `app/Features/Reports/Exports/` | Exporta solicitudes de empresa a Excel |

#### 🎯 Reportes Disponibles

1. **Reporte de Empresas**
   - Listado completo de empresas registradas
   - Incluye: Código, Nombre, Email, Industria, Estado, Agentes, Tickets
   - Filtros: Estado (activas/suspendidas)
   - Formatos: Excel, PDF

2. **Reporte de Crecimiento de Plataforma**
   - Estadísticas de crecimiento mensual
   - Incluye: Nuevas empresas, usuarios y tickets por mes
   - Filtros: Periodo (3, 6 o 12 meses)
   - Formatos: Excel, PDF

3. **Reporte de Solicitudes de Empresa**
   - Historial de solicitudes de registro
   - Incluye: Empresa, Admin, Fecha, Estado, Revisor
   - Filtros: Estado (pendiente/aprobada/rechazada)
   - Formatos: Excel, PDF

#### 🛤️ Rutas Web

```php
// Vista principal
Route::get('/app/admin/reports', [PlatformReportController::class, 'index'])
    ->name('admin.reports.index');

// Descargas - Companies
Route::get('/app/admin/reports/companies/excel', [PlatformReportController::class, 'companiesExcel'])
    ->name('admin.reports.companies.excel');
Route::get('/app/admin/reports/companies/pdf', [PlatformReportController::class, 'companiesPdf'])
    ->name('admin.reports.companies.pdf');

// Descargas - Growth
Route::get('/app/admin/reports/growth/excel', [PlatformReportController::class, 'growthExcel'])
    ->name('admin.reports.growth.excel');
Route::get('/app/admin/reports/growth/pdf', [PlatformReportController::class, 'growthPdf'])
    ->name('admin.reports.growth.pdf');

// Descargas - Requests
Route::get('/app/admin/reports/requests/excel', [PlatformReportController::class, 'requestsExcel'])
    ->name('admin.reports.requests.excel');
Route::get('/app/admin/reports/requests/pdf', [PlatformReportController::class, 'requestsPdf'])
    ->name('admin.reports.requests.pdf');
```

---

### 2️⃣ **COMPANY ADMIN REPORTS** (Reportes de Empresa)

#### 📄 Archivos Principales

| Tipo | Archivo | Ubicación | Descripción |
|------|---------|-----------|-------------|
| **Controlador** | `CompanyReportController.php` | `app/Features/Reports/Http/Controllers/` | Controlador para reportes de Company Admin |
| **Vista** | `tickets.blade.php` | `resources/views/app/company-admin/reports/` | Reporte de tickets de la empresa |
| **Vista** | `agents.blade.php` | `resources/views/app/company-admin/reports/` | Reporte de desempeño de agentes |
| **Vista** | `summary.blade.php` | `resources/views/app/company-admin/reports/` | Resumen operativo |
| **Vista** | `company.blade.php` | `resources/views/app/company-admin/reports/` | Reporte de empresa y equipo |
| **Template PDF** | `tickets-pdf.blade.php` | `resources/views/app/company-admin/reports/templates/` | Template PDF para tickets |
| **Template PDF** | `agents-pdf.blade.php` | `resources/views/app/company-admin/reports/templates/` | Template PDF para agentes |
| **Template PDF** | `summary-pdf.blade.php` | `resources/views/app/company-admin/reports/templates/` | Template PDF para resumen |
| **Template PDF** | `company-pdf.blade.php` | `resources/views/app/company-admin/reports/templates/` | Template PDF para empresa |
| **Exportador** | `CompanyTicketsExport.php` | `app/Features/Reports/Exports/` | Exporta tickets de la empresa a Excel |

#### 🎯 Reportes Disponibles

1. **Reporte de Tickets**
   - Listado de tickets de la empresa
   - Formatos: Excel, PDF

2. **Reporte de Agentes**
   - Desempeño de agentes de la empresa
   - Formatos: Excel, PDF

3. **Resumen Operativo**
   - Estadísticas generales de operación
   - Formato: PDF

4. **Reporte de Empresa y Equipo**
   - Información completa de la empresa
   - Formato: PDF

#### 🛤️ Rutas Web

```php
// Vistas
Route::get('/app/company/reports/tickets', /* closure */)
    ->name('company.reports.tickets');
Route::get('/app/company/reports/agents', /* closure */)
    ->name('company.reports.agents');
Route::get('/app/company/reports/summary', /* closure */)
    ->name('company.reports.summary');
Route::get('/app/company/reports/company', /* closure */)
    ->name('company.reports.company');

// Descargas
Route::get('/app/company/reports/tickets/excel', [CompanyReportController::class, 'ticketsExcel'])
    ->name('company.reports.tickets.excel');
Route::get('/app/company/reports/tickets/pdf', [CompanyReportController::class, 'ticketsPdf'])
    ->name('company.reports.tickets.pdf');
Route::get('/app/company/reports/agents/excel', [CompanyReportController::class, 'agentsExcel'])
    ->name('company.reports.agents.excel');
Route::get('/app/company/reports/agents/pdf', [CompanyReportController::class, 'agentsPdf'])
    ->name('company.reports.agents.pdf');
Route::get('/app/company/reports/summary/pdf', [CompanyReportController::class, 'summaryPdf'])
    ->name('company.reports.summary.pdf');
Route::get('/app/company/reports/company/pdf', [CompanyReportController::class, 'companyPdf'])
    ->name('company.reports.company.pdf');
```

---

### 3️⃣ **AGENT REPORTS** (Reportes de Agente)

#### 📄 Archivos Principales

| Tipo | Archivo | Ubicación | Descripción |
|------|---------|-----------|-------------|
| **Controlador** | `AgentReportController.php` | `app/Features/Reports/Http/Controllers/` | Controlador para reportes individuales de agente |
| **Vista** | `tickets.blade.php` | `resources/views/app/agent/reports/` | Reporte de mis tickets |
| **Vista** | `performance.blade.php` | `resources/views/app/agent/reports/` | Reporte de mi rendimiento |
| **Template PDF** | `tickets-pdf.blade.php` | `resources/views/app/agent/reports/templates/` | Template PDF para tickets |
| **Template PDF** | `performance-pdf.blade.php` | `resources/views/app/agent/reports/templates/` | Template PDF para rendimiento |
| **Exportador** | `AgentTicketsExport.php` | `app/Features/Reports/Exports/` | Exporta tickets del agente a Excel |
| **Exportador** | `AgentsPerformanceExport.php` | `app/Features/Reports/Exports/` | Exporta rendimiento del agente a Excel |

#### 🎯 Reportes Disponibles

1. **Mis Tickets**
   - Tickets asignados al agente
   - Formatos: Excel, PDF

2. **Mi Rendimiento**
   - Estadísticas de desempeño individual
   - Formato: PDF

#### 🛤️ Rutas Web

```php
// Vistas
Route::get('/app/agent/reports/tickets', /* closure */)
    ->name('agent.reports.tickets');
Route::get('/app/agent/reports/performance', /* closure */)
    ->name('agent.reports.performance');

// Descargas
Route::get('/app/agent/reports/tickets/excel', [AgentReportController::class, 'ticketsExcel'])
    ->name('agent.reports.tickets.excel');
Route::get('/app/agent/reports/tickets/pdf', [AgentReportController::class, 'ticketsPdf'])
    ->name('agent.reports.tickets.pdf');
Route::get('/app/agent/reports/performance/pdf', [AgentReportController::class, 'performancePdf'])
    ->name('agent.reports.performance.pdf');
```

---

### 4️⃣ **USER REPORTS** (Reportes de Usuario)

#### 📄 Archivos Principales

| Tipo | Archivo | Ubicación | Descripción |
|------|---------|-----------|-------------|
| **Controlador** | `UserReportController.php` | `app/Features/Reports/Http/Controllers/` | Controlador para reportes de usuario |
| **Vista** | `tickets.blade.php` | `resources/views/app/user/reports/` | Historial de tickets del usuario |
| **Vista** | `activity.blade.php` | `resources/views/app/user/reports/` | Resumen de actividad del usuario |
| **Template PDF** | `tickets-pdf.blade.php` | `resources/views/app/user/reports/templates/` | Template PDF para tickets |
| **Template PDF** | `activity-pdf.blade.php` | `resources/views/app/user/reports/templates/` | Template PDF para actividad |
| **Exportador** | `UserTicketsExport.php` | `app/Features/Reports/Exports/` | Exporta tickets del usuario a Excel |

#### 🎯 Reportes Disponibles

1. **Historial de Tickets**
   - Todos los tickets creados por el usuario
   - Formatos: Excel, PDF

2. **Resumen de Actividad**
   - Estadísticas de uso del sistema
   - Formatos: Excel, PDF

#### 🛤️ Rutas Web

```php
// Vistas
Route::get('/app/user/reports/tickets', /* closure */)
    ->name('user.reports.tickets');
Route::get('/app/user/reports/activity', /* closure */)
    ->name('user.reports.activity');

// Descargas
Route::get('/app/user/reports/tickets/excel', [UserReportController::class, 'ticketsExcel'])
    ->name('user.reports.tickets.excel');
Route::get('/app/user/reports/tickets/pdf', [UserReportController::class, 'ticketsPdf'])
    ->name('user.reports.tickets.pdf');
Route::get('/app/user/reports/activity/excel', [UserReportController::class, 'activityExcel'])
    ->name('user.reports.activity.excel');
Route::get('/app/user/reports/activity/pdf', [UserReportController::class, 'activityPdf'])
    ->name('user.reports.activity.pdf');
```

---

### 5️⃣ **TICKET CHAT EXPORT** (Exportación de Chat de Tickets)

#### 📄 Archivo

| Tipo | Archivo | Ubicación | Descripción |
|------|---------|-----------|-------------|
| **Controlador** | `TicketChatExportController.php` | `app/Features/Reports/Http/Controllers/` | Exporta conversaciones de tickets a TXT |

#### 🛤️ Ruta Web

```php
Route::get('/app/tickets/{ticketCode}/export-chat', [TicketChatExportController::class, 'exportTxt'])
    ->name('tickets.export-chat');
```

---

## 📊 Resumen de Archivos

### Controladores (5)
1. `PlatformReportController.php`
2. `CompanyReportController.php`
3. `AgentReportController.php`
4. `UserReportController.php`
5. `TicketChatExportController.php`

### Exportadores Excel (7)
1. `CompaniesExport.php`
2. `CompanyRequestsExport.php`
3. `PlatformGrowthExport.php`
4. `CompanyTicketsExport.php`
5. `AgentTicketsExport.php`
6. `AgentsPerformanceExport.php`
7. `UserTicketsExport.php`

### Vistas Blade (11)
1. Platform Admin: `index.blade.php`
2. Company Admin: `tickets.blade.php`, `agents.blade.php`, `summary.blade.php`, `company.blade.php`
3. Agent: `tickets.blade.php`, `performance.blade.php`
4. User: `tickets.blade.php`, `activity.blade.php`

### Templates PDF (13)
1. Platform Admin: `companies-pdf.blade.php`, `growth-pdf.blade.php`, `requests-pdf.blade.php`
2. Company Admin: `tickets-pdf.blade.php`, `agents-pdf.blade.php`, `summary-pdf.blade.php`, `company-pdf.blade.php`
3. Agent: `tickets-pdf.blade.php`, `performance-pdf.blade.php`
4. User: `tickets-pdf.blade.php`, `activity-pdf.blade.php`

### **TOTAL: 36 archivos principales**

---

## 🔗 Rutas en `web.php`

Todas las rutas de reportes están definidas en `routes/web.php` dentro de los grupos de middleware correspondientes:
- `spatie.active_role:PLATFORM_ADMIN`
- `spatie.active_role:COMPANY_ADMIN`
- `spatie.active_role:AGENT`
- `spatie.active_role:USER`

**Ubicación:** `routes/web.php` (líneas 259-583)

---

## ⚙️ Dependencias Técnicas

### Librerías Utilizadas
1. **Laravel Excel** (`maatwebsite/excel`) - Para exportación a Excel
2. **DomPDF** (`barryvdh/dompdf`) - Para generación de PDFs
3. **AdminLTE** - Para los estilos de la interfaz

### Modelos Principales
1. `Company` - Empresas
2. `User` - Usuarios
3. `Ticket` - Tickets
4. `CompanyRequest` - Solicitudes de empresa

---

## 🎨 Interfaz de Usuario

### Vista Principal (Platform Admin)
- **Archivo:** `resources/views/app/platform-admin/reports/index.blade.php`
- **Características:**
  - Dashboard con 3 tarjetas de reportes
  - Filtros dinámicos (estado, periodo)
  - Estadísticas rápidas en tiempo real
  - Botones de descarga para Excel y PDF
  - Integración con AdminLTE Toast para notificaciones

### JavaScript Integrado
- Sistema de tokens JWT para autenticación
- Llamadas AJAX para estadísticas en tiempo real
- Descarga directa de archivos sin recargar página
- Manejo de errores con toasts de AdminLTE

---

## 🚀 Flujo de Generación de Reportes

### Excel
```
Usuario → Clic botón → Controlador → Exportador → Excel → Descarga
```

### PDF
```
Usuario → Clic botón → Controlador → Vista Template → DomPDF → Descarga
```

---

## 📝 Notas Importantes

1. **No existe API REST** para reportes - Todo se maneja vía rutas web con descarga directa
2. **Autorización** - Cada ruta está protegida con middleware de rol apropiado
3. **Filtros** - Soportan filtros dinámicos por estado y periodo
4. **Nombres de archivos** - Incluyen timestamp para evitar colisiones
5. **Orientación PDF** - Algunos reportes usan landscape, otros portrait
6. **Estadísticas** - Se obtienen del API endpoint `/api/analytics/*`

---

## 🔐 Seguridad

- ✅ Todas las rutas requieren autenticación JWT
- ✅ Middleware de roles específicos por sección
- ✅ Validación de permisos en controladores
- ✅ Rate limiting en endpoints sensibles

---

## 📌 Conclusión

El sistema de reportes está **completamente implementado** y **bien organizado** por roles. Cada rol tiene acceso únicamente a los reportes relevantes para su función, con una clara separación de responsabilidades y una arquitectura coherente.

**Total de archivos identificados: 36**
- 5 Controladores
- 7 Exportadores Excel
- 11 Vistas Blade
- 13 Templates PDF

Todos los archivos están ubicados en `app/Features/Reports/` y `resources/views/app/[role]/reports/`.
