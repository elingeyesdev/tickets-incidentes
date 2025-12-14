# 📋 Inventario de Archivos Afectados: Normalización de Empresas

> **Fecha de Creación:** 13/12/2025
> **Última Actualización:** 14/12/2025 07:45
> **Estado:** ✅ CÓDIGO DE PRODUCCIÓN COMPLETADO - Tests pendientes de actualización
> **Objetivo:** Listado exhaustivo de todos los archivos que requieren modificación para eliminar la tabla `company_requests` y unificarla con `companies`.

---

## 🎯 RESUMEN EJECUTIVO

| Categoría | Estado |
|-----------|--------|
| **Código de Producción (app/)** | ✅ **100% COMPLETADO** |
| **Modelo y Factory eliminados** | ✅ **COMPLETADO** |
| **Migración creada** | ✅ **PENDIENTE DE EJECUTAR** |
| **Tests actualizados** | ⚠️ 2/8 actualizados, 6 legacy |

---

## 🚨 NIVEL 1: CRÍTICO (Cambios Estructurales) - ✅ COMPLETADO

### Migraciones (Database)
- [x] `database/migrations/2025_12_14_000001_normalize_company_tables.php` **(CREADA)**

### Modelos (Models)
- [x] `app/Features/CompanyManagement/Models/Company.php`:
    - ✅ Agregado `GlobalScope` para ocultar pendientes
    - ✅ Agregada relación `onboardingDetails()`
    - ✅ Agregados métodos `approve()`, `reject()`, `isPending()`, `isRejected()`
    - ✅ Agregados scopes `pending()`, `rejected()`, `withAllStatuses()`
- [x] `app/Features/CompanyManagement/Models/CompanyRequest.php`: **ELIMINADO** ✅
- [x] `app/Features/CompanyManagement/Models/CompanyOnboardingDetails.php` **(CREADO)**

### Servicios Core (Services) - ✅ COMPLETADO
- [x] `app/Features/CompanyManagement/Services/CompanyRequestService.php`: **REFACTOR TOTAL**
- [x] `app/Features/CompanyManagement/Services/CompanyService.php`: Eliminada referencia obsoleta
- [x] `app/Features/CompanyManagement/Services/CompanyDuplicateDetectionService.php`: **REFACTOR TOTAL**

---

## ⚠️ NIVEL 2: ALTO (Lógica de Negocio) - ✅ COMPLETADO

### Controladores (Controllers)
- [x] `app/Features/CompanyManagement/Http/Controllers/CompanyRequestController.php`: Actualizado
- [x] `app/Features/CompanyManagement/Http/Controllers/CompanyRequestAdminController.php`: Actualizado

### Eventos y Listeners
- [x] `app/Features/CompanyManagement/Events/CompanyRequestSubmitted.php`: Ahora usa `Company`
- [x] `app/Features/CompanyManagement/Events/CompanyRequestApproved.php`: Ahora usa `Company`
- [x] `app/Features/CompanyManagement/Events/CompanyRequestRejected.php`: Ahora usa `Company`
- [x] `app/Features/CompanyManagement/Listeners/SendApprovalEmail.php`: Actualizado
- [x] `app/Features/CompanyManagement/Listeners/SendRejectionEmail.php`: Actualizado
- [x] `app/Features/CompanyManagement/Listeners/NotifyAdminOfNewRequest.php`: Actualizado
- [x] `app/Features/CompanyManagement/Listeners/CreateCompanyFromRequest.php`: Actualizado
- [x] `app/Features/CompanyManagement/Listeners/SendCompanyRequestConfirmationEmail.php`: Actualizado

### Jobs y Mails
- [x] `app/Features/CompanyManagement/Jobs/SendCompanyRequestEmailJob.php`: Actualizado
- [x] `app/Features/CompanyManagement/Jobs/SendCompanyApprovalEmailJob.php`: Actualizado
- [x] `app/Features/CompanyManagement/Jobs/SendCompanyRejectionEmailJob.php`: Actualizado
- [x] `app/Features/CompanyManagement/Mail/CompanyRejectionMail.php`: Actualizado con alias

---

## 🔸 NIVEL 3: MEDIO (Dependencias) - ✅ COMPLETADO

### Reportes (Features/Reports)
- [x] `app/Features/Reports/Http/Controllers/PlatformReportController.php`: Actualizado
- [x] `app/Features/Reports/Exports/CompanyRequestsExport.php`: **REFACTOR TOTAL**
- [x] `app/Features/Reports/Exports/PlatformGrowthExport.php`: Actualizado

### Analytics
- [x] `app/Features/Analytics/Services/AnalyticsService.php`: Actualizado

### Middleware
- [x] `app/Http/Middleware/ApiExceptionHandler.php`: Eliminada referencia

### Resources (API Responses)
- [x] `app/Features/CompanyManagement/Http/Resources/CompanyRequestResource.php`: **REFACTOR TOTAL**
- [x] `app/Features/CompanyManagement/Http/Resources/CompanyResource.php`: Actualizado

### Factories
- [x] `app/Features/CompanyManagement/Database/Factories/CompanyFactory.php`: Agregados states `pending()`, `rejected()`
- [x] `app/Features/CompanyManagement/Database/Factories/CompanyOnboardingDetailsFactory.php`: **(CREADA)**
- [x] `app/Features/CompanyManagement/Database/Factories/CompanyRequestFactory.php`: **ELIMINADA** ✅

### Seeders
- [x] `app/Features/CompanyManagement/Database/Seeders/CompanyRequestApprovalSimulationSeeder.php`: **REFACTOR TOTAL**

---

## 🧪 NIVEL 5: TESTS (Pendientes de Actualización)

### Tests Actualizados ✅
- [x] `tests/Feature/CompanyManagement/Controllers/CompanyRequestControllerStoreTest.php`
- [x] `tests/Feature/CompanyManagement/Controllers/CompanyRequestControllerIndexTest.php`

### Tests Legacy (Requieren Actualización Manual) ⚠️
Estos tests fallarán porque usan `CompanyRequest::factory()` que ya no existe:

- [ ] `tests/Feature/CompanyManagement/Controllers/CompanyRequestAdminControllerApproveTest.php`
- [ ] `tests/Feature/CompanyManagement/Controllers/CompanyRequestAdminControllerRejectTest.php`
- [ ] `tests/Feature/CompanyManagement/Services/CompanyRequestServiceTest.php`
- [ ] `tests/Feature/CompanyManagement/CompanyDuplicateDetectionTest.php`
- [ ] `tests/Feature/Analytics/DashboardStatsTest.php`

### Helper Trait Creado para Tests
- [x] `tests/Feature/CompanyManagement/CreatesCompanyRequests.php`: Helper para crear empresas pendientes/rechazadas

---

## 🗑️ ARCHIVOS ELIMINADOS

- [x] `app/Features/CompanyManagement/Models/CompanyRequest.php` - **ELIMINADO**
- [x] `app/Features/CompanyManagement/Database/Factories/CompanyRequestFactory.php` - **ELIMINADO**

---

## 📝 PRÓXIMOS PASOS

### 1. Ejecutar la Migración (CRÍTICO)
```bash
docker compose exec app php artisan migrate
```

### 2. Actualizar Tests Legacy
Usar el trait `CreatesCompanyRequests` para reemplazar `CompanyRequest::factory()`:
```php
use Tests\Feature\CompanyManagement\CreatesCompanyRequests;

// En lugar de:
$request = CompanyRequest::factory()->create(['status' => 'pending']);

// Usar:
$company = $this->createPendingCompanyWithOnboarding([
    'name' => 'Test Company',
], [
    'submitter_email' => 'admin@test.com',
]);
```

### 3. Verificar Endpoints
```bash
# POST /api/company-requests - Crea empresa con status pending
# POST /api/company-requests/{id}/approve - Cambia status a active  
# POST /api/company-requests/{id}/reject - Cambia status a rejected
# GET /api/company-requests - Lista todas las solicitudes
```

---

## 📊 ESTADÍSTICAS FINALES

| Categoría | Total | Completados | Pendientes |
|-----------|-------|-------------|------------|
| Modelos | 3 | 3 | 0 |
| Servicios | 3 | 3 | 0 |
| Controladores | 2 | 2 | 0 |
| Eventos | 3 | 3 | 0 |
| Listeners | 5 | 5 | 0 |
| Jobs | 3 | 3 | 0 |
| Mails | 1 | 1 | 0 |
| Resources | 2 | 2 | 0 |
| Reportes/Analytics | 4 | 4 | 0 |
| Middleware | 1 | 1 | 0 |
| Factories | 2 | 2 | 0 |
| Seeders | 1 | 1 | 0 |
| Migraciones | 1 | 1 | 0 |
| **PRODUCCIÓN TOTAL** | **31** | **31** | **0** |
| Tests | 7 | 2 | 5 |
