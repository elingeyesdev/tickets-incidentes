# 🗃️ Plan de Normalización: `companies` ↔ `company_requests`

> **Fecha de creación:** 2025-12-13  
> **Estado:** Análisis completo - Listo para implementación mañana

---

## 📊 RESUMEN EJECUTIVO

Actualmente tienes **DOS TABLAS** que almacenan información similar de empresas:

| Tabla | Esquema | Propósito |
|-------|---------|-----------|
| `business.company_requests` | Solicitudes de registro | Datos iniciales de empresas pendientes/rechazadas/aprobadas |
| `business.companies` | Empresas activas | Datos de empresas aprobadas y en operación |

### ⚠️ Problema de Duplicación
Cuando se **aprueba** una solicitud, mucha información se **copia** de `company_requests` a `companies`:
- `company_name` → `name`
- `legal_name` → `legal_name`
- `admin_email` → `support_email`
- `tax_id` → `tax_id`
- `industry_id` → `industry_id`
- `website` → `website`
- `contact_*` → `contact_*`

---

## 📁 ARCHIVOS AFECTADOS - LISTA COMPLETA

### 🔴 CRÍTICO - Modelos (Cambios directos)
```
app/Features/CompanyManagement/Models/
├── Company.php                 ← Modelo principal de empresas
├── CompanyRequest.php          ← Modelo de solicitudes
├── CompanyIndustry.php         ← Relación con industrias
├── CompanyFollower.php         ← Seguidores de empresas
└── Area.php                    ← Áreas de empresas
```

### 🔴 CRÍTICO - Servicios (Lógica de negocio)
```
app/Features/CompanyManagement/Services/
├── CompanyService.php                    ← Crea/actualiza empresas
├── CompanyRequestService.php             ← Procesa solicitudes (approve/reject)
├── CompanyDuplicateDetectionService.php  ← Detecta duplicados (usa ambas tablas!)
├── CompanyIndustryService.php            ← Industrias
├── CompanyFollowService.php              ← Seguidores
└── AreaService.php                       ← Áreas
```

### 🔴 CRÍTICO - Migraciones de Base de Datos
```
app/Features/CompanyManagement/Database/Migrations/
├── 2025_10_04_000003_create_company_requests_table.php  ← Tabla company_requests
├── 2025_10_04_000004_create_companies_table.php         ← Tabla companies
├── 2025_10_04_000005_create_user_company_followers_table.php
├── 2025_11_26_000002_create_areas_table.php            ← FK a companies
└── 2025_11_26_000003_add_areas_enabled_to_company_settings.php

database/migrations/
└── 2025_12_03_224550_add_duplicate_prevention_constraints_to_company_tables.php ← ÍNDICES ÚNICOS!
```

### 🟠 IMPORTANTE - Controladores
```
app/Features/CompanyManagement/Http/Controllers/
├── CompanyController.php              ← CRUD de empresas (1583 líneas!)
├── CompanyRequestController.php       ← Lista/crear solicitudes (505 líneas)
├── CompanyRequestAdminController.php  ← Aprobar/rechazar (15KB)
├── CompanyFollowerController.php      ← Seguidores
├── CompanyIndustryController.php      ← Industrias
└── AreaController.php                 ← Áreas
```

### 🟠 IMPORTANTE - Form Requests (Validaciones)
```
app/Features/CompanyManagement/Http/Requests/
├── CreateCompanyRequest.php          ← Validación crear empresa
├── UpdateCompanyRequest.php          ← Validación actualizar empresa
├── StoreCompanyRequestRequest.php    ← Validación crear solicitud
├── ApproveCompanyRequestRequest.php  ← Validación aprobar
├── RejectCompanyRequestRequest.php   ← Validación rechazar
└── ListCompaniesRequest.php          ← Filtros de listado
```

### 🟠 IMPORTANTE - Resources (API Responses)
```
app/Features/CompanyManagement/Http/Resources/
├── CompanyResource.php           ← Respuesta completa de empresa
├── CompanyRequestResource.php    ← Respuesta de solicitud
├── CompanyApprovalResource.php   ← Respuesta de aprobación
├── CompanyRejectionResource.php  ← Respuesta de rechazo
├── CompanyExploreResource.php    ← Listado público
├── CompanyMinimalResource.php    ← Respuesta mínima
└── CompanyFollowResource.php     ← Seguidores
```

### 🟡 MODERADO - Vistas Blade
```
resources/views/app/platform-admin/
├── requests/
│   ├── index.blade.php                         ← Lista de solicitudes
│   └── partials/
│       ├── approve-request-modal.blade.php     ← Modal aprobar
│       ├── reject-request-modal.blade.php      ← Modal rechazar
│       └── view-request-modal.blade.php        ← Ver detalles
│
├── companies/
│   ├── index.blade.php                         ← Lista de empresas
│   └── partials/
│       ├── form-company-modal.blade.php        ← Formulario crear/editar
│       ├── view-company-modal.blade.php        ← Ver detalles
│       ├── status-company-modal.blade.php      ← Cambiar estado
│       └── delete-company-modal.blade.php      ← Eliminar

resources/views/public/
└── company-request.blade.php                   ← Formulario público de solicitud
```

### 🟡 MODERADO - Eventos y Listeners
```
app/Features/CompanyManagement/Events/
├── CompanyRequestSubmitted.php   ← Cuando se envía solicitud
├── CompanyRequestApproved.php    ← Cuando se aprueba (CLAVE!)
├── CompanyRequestRejected.php    ← Cuando se rechaza
├── CompanyCreated.php            ← Cuando se crea empresa
├── CompanyUpdated.php
├── CompanyActivated.php
└── CompanySuspended.php

app/Features/CompanyManagement/Listeners/
├── CreateCompanyFromRequest.php              ← Crea empresa desde solicitud
├── SendApprovalEmail.php                     ← Email de aprobación
├── SendRejectionEmail.php                    ← Email de rechazo
├── SendCompanyRequestConfirmationEmail.php   ← Confirmación de solicitud
└── NotifyAdminOfNewRequest.php               ← Notifica a admin
```

### 🟡 MODERADO - Factories y Seeders
```
app/Features/CompanyManagement/Database/Factories/
├── CompanyFactory.php          ← Factory de empresas
├── CompanyRequestFactory.php   ← Factory de solicitudes
├── CompanyIndustryFactory.php
├── CompanyFollowerFactory.php
└── AreaFactory.php

app/Features/CompanyManagement/Database/Seeders/
├── CompanyIndustrySeeder.php
├── CompanyRequestApprovalSimulationSeeder.php  ← Simula aprobaciones
├── LargeBolivianCompaniesSeeder.php
├── MediumBolivianCompaniesSeeder.php
└── SmallBolivianCompaniesSeeder.php
```

### 🟡 MODERADO - Jobs y Mail
```
app/Features/CompanyManagement/Jobs/
├── SendCompanyApprovalEmailJob.php
├── SendCompanyRejectionEmailJob.php
└── SendCompanyRequestEmailJob.php

app/Features/CompanyManagement/Mail/
├── CompanyApprovalMailForExistingUser.php
├── CompanyApprovalMailForNewUser.php
└── CompanyRejectionMail.php
```

### 🟡 MODERADO - Tests
```
tests/Feature/CompanyManagement/
├── CompanyDuplicateDetectionTest.php                    ← ¡Importante!
├── MultiRoleCompanyAccessTest.php
├── Controllers/
│   ├── CompanyControllerCreateTest.php
│   ├── CompanyControllerIndexTest.php
│   ├── CompanyControllerShowTest.php
│   ├── CompanyControllerUpdateTest.php
│   ├── CompanyControllerUploadBrandingTest.php
│   ├── CompanyRequestControllerIndexTest.php
│   ├── CompanyRequestControllerStoreTest.php           ← ¡Importante!
│   ├── CompanyRequestAdminControllerApproveTest.php    ← ¡Importante!
│   └── CompanyRequestAdminControllerRejectTest.php
└── Services/
    ├── CompanyServiceTest.php
    ├── CompanyRequestServiceTest.php                   ← ¡Importante!
    └── CompanyFollowServiceTest.php
```

### 🟢 MENOR - Rutas
```
routes/
├── api.php   ← Rutas API de companies y company-requests
└── web.php   ← Rutas web para vistas admin
```

### 🟢 MENOR - Otras dependencias
```
app/Features/Reports/
├── Http/Controllers/PlatformReportController.php       ← Usa CompanyRequest
└── Exports/
    ├── CompanyRequestsExport.php                       ← Exporta solicitudes
    └── PlatformGrowthExport.php                        ← Estadísticas

app/Features/UserManagement/Services/
└── UserService.php                                     ← createFromCompanyRequest()

app/Features/AuditLog/Services/
└── ActivityLogService.php                              ← logCompanyRequestApproved/Rejected

app/Shared/Enums/
└── CompanyRequestStatus.php                            ← Enum de estados

app/Http/Middleware/
└── ApiExceptionHandler.php                             ← Maneja errores de ambas tablas
```

---

## 🔗 RELACIONES ENTRE TABLAS (FK Constraints)

### Tabla `business.company_requests`:
```sql
-- FK a industries
industry_id → business.company_industries(id)

-- FK a usuarios (reviewer)
reviewed_by → auth.users(id)

-- FK a empresas (cuando se aprueba)
created_company_id → business.companies(id)
```

### Tabla `business.companies`:
```sql
-- FK a industries
industry_id → business.company_industries(id)

-- FK a solicitud origen
created_from_request_id → business.company_requests(id)

-- FK a usuario admin
admin_user_id → auth.users(id)
```

### Tablas que referencian `business.companies`:
```sql
business.user_company_followers(company_id) → companies(id)
business.areas(company_id) → companies(id)
tickets.tickets(company_id) → companies(id)
content.help_center_articles(company_id) → companies(id)
content.company_announcements(company_id) → companies(id)
tickets.ticket_categories(company_id) → companies(id)
auth.user_roles(company_id) → companies(id)
```

---

## 🛠️ OPCIONES DE NORMALIZACIÓN

### Opción A: Mantener estructura actual (mínimo cambio)
- Mantener ambas tablas separadas
- Solo mejorar índices y constraints
- **Pro:** Menos riesgo, menos trabajo
- **Con:** Sigue habiendo duplicación de datos

### Opción B: Tabla intermedia de datos compartidos
```sql
business.company_base_info (
    id UUID PRIMARY KEY,
    name VARCHAR,
    legal_name VARCHAR,
    tax_id VARCHAR UNIQUE,
    industry_id UUID,
    contact_*,
    created_at, updated_at
)

-- company_requests referencia a base_info
company_requests.base_info_id → company_base_info(id)

-- companies también
companies.base_info_id → company_base_info(id)
```
- **Pro:** Elimina duplicación completamente
- **Con:** Cambio estructural mayor, muchas migraciones

### Opción C: Usar company_requests solo como historial
- Al aprobar, **mover** datos a companies (no copiar)
- company_requests solo guarda: request_code, status, reviewed_by, reviewed_at, rejection_reason
- Referencia a companies.id para historial
- **Pro:** Limpio, menos duplicación
- **Con:** Requiere refactorización de modelos y servicios

---

## ⚠️ PUNTOS CRÍTICOS A TENER CUIDADO

### 1. **CompanyDuplicateDetectionService.php**
Este servicio busca duplicados en AMBAS tablas. Si cambias la estructura, debes actualizarlo.

### 2. **CompanyRequestService.php → approve()**
Este método copia datos de request a company. Es el punto central de la duplicación.

### 3. **Índices UNIQUE en ambas tablas**
```sql
idx_company_requests_tax_id_unique
idx_companies_tax_id_unique
```
Si normalizas, debes manejar estos índices cuidadosamente.

### 4. **Foreign Keys CASCADE DELETE**
Muchas tablas tienen FK a companies con CASCADE DELETE. Asegúrate de no romper estas relaciones.

### 5. **Seeders existentes**
Los seeders crean datos directamente en ambas tablas. Deberás actualizarlos.

---

## 📋 CHECKLIST PARA MAÑANA

### Pre-implementación:
- [ ] Hacer backup de la base de datos
- [ ] Crear rama feature nueva: `git checkout -b feature/normalize-company-tables`
- [ ] Revisar datos existentes en producción (si aplica)

### Durante implementación:
- [ ] Empezar por la migración (crear nueva, no modificar existentes)
- [ ] Actualizar modelos (relaciones Eloquent)
- [ ] Actualizar servicios (especialmente CompanyRequestService)
- [ ] Actualizar controladores
- [ ] Actualizar tests
- [ ] Ejecutar test suite completa: `php artisan test`

### Post-implementación:
- [ ] Verificar que las vistas funcionen correctamente
- [ ] Probar flujo completo: solicitud → aprobación → empresa
- [ ] Verificar reportes y exports
- [ ] Verificar detección de duplicados

---

## 📞 CONTACTO

Si tienes dudas durante la implementación, puedo ayudarte paso a paso con cada archivo.

**¡Buena suerte mañana! 🚀**
