# 📋 Inventario de Archivos Afectados: Normalización de Empresas

> **Fecha de Creación:** 13/12/2025
> **Objetivo:** Listado exhaustivo de todos los archivos que requieren modificación para eliminar la tabla `company_requests` y unificarla con `companies`.

---

## 🚨 NIVEL 1: CRÍTICO (Cambios Estructurales)
*Archivos que definen la base de datos y los modelos. Si esto falla, nada funciona.*

### Migraciones (Database)
- [ ] `database/migrations/YYYY_MM_DD_XXXXXX_unify_company_tables.php` **(NUEVA)**: Migración maestra que crearemos.
- [ ] `database/migrations/2025_12_03_224550_add_duplicate_prevention_constraints_to_company_tables.php`: Referencia índices viejos que hay que limpiar.

### Modelos (Models)
- [ ] `app/Features/CompanyManagement/Models/Company.php`:
    - Agregar `GlobalScope` para ocultar pendientes.
    - Agregar relación `onboardingDetails()`.
    - Agregar métodos `approve()`, `reject()`.
- [ ] `app/Features/CompanyManagement/Models/CompanyRequest.php`: **ELIMINAR** (Reemplazado por `CompanyOnboardingDetails`).
- [ ] `app/Features/CompanyManagement/Models/CompanyOnboardingDetails.php` **(NUEVO)**.

### Servicios Core (Services)
- [ ] `app/Features/CompanyManagement/Services/CompanyRequestService.php`:
    - **REFACTOR TOTAL**: Ya no debe crear `CompanyRequest`, sino `Company` con status 'pending'.
    - Eliminar lógica de copia de datos en `approve()`.
- [ ] `app/Features/CompanyManagement/Services/CompanyService.php`:
    - Ajustar validaciones para permitir duplicados SOLO si el status es 'pending' o 'rejected' (si el constraint lo permite, o manejar excepciones).
- [ ] `app/Features/CompanyManagement/Services/CompanyDuplicateDetectionService.php`:
    - Actualizar para buscar solo en `companies` (ignorando o incluyendo scope según corresponda).

---

## ⚠️ NIVEL 2: ALTO (Lógica de Negocio)
*Controladores y lógica que maneja el flujo de la aplicación.*

### Controladores (Controllers)
- [ ] `app/Features/CompanyManagement/Http/Controllers/CompanyRequestController.php`:
    - `store()`: Debe crear `Company` (pending) + `CompanyOnboardingDetails`.
    - `index()`: Debe listar `Company::withoutGlobalScope()->where('pending')`.
- [ ] `app/Features/CompanyManagement/Http/Controllers/CompanyRequestAdminController.php`:
    - `approve()`: Solo actualizar status en `Company`, no crear registro nuevo.
    - `reject()`: Actualizar status y guardar razón en `details`.

### Eventos y Listeners
- [ ] `app/Features/CompanyManagement/Events/CompanyRequestSubmitted.php`:
    - Cambiar tipo de propiedad: de `CompanyRequest` a `Company`.
- [ ] `app/Features/CompanyManagement/Events/CompanyRequestApproved.php`
- [ ] `app/Features/CompanyManagement/Events/CompanyRequestRejected.php`
- [ ] `app/Features/CompanyManagement/Listeners/SendApprovalEmail.php`
- [ ] `app/Features/CompanyManagement/Listeners/SendRejectionEmail.php`
- [ ] `app/Features/CompanyManagement/Listeners/CreateCompanyFromRequest.php`: **ELIMINAR/REVISAR** (Ya no es necesario "crear desde", ya existe).

### Jobs y Mails
- [ ] `app/Features/CompanyManagement/Jobs/SendCompanyRequestEmailJob.php`
- [ ] `app/Features/CompanyManagement/Jobs/SendCompanyApprovalEmailJob.php`
- [ ] `app/Features/CompanyManagement/Jobs/SendCompanyRejectionEmailJob.php`
- [ ] `app/Features/CompanyManagement/Mail/CompanyRejectionMail.php`: Actualizar constructor.
- [ ] `app/Features/CompanyManagement/Mail/CompanyApprovalMailForNewUser.php`
- [ ] `app/Features/CompanyManagement/Mail/CompanyApprovalMailForExistingUser.php`

---

## 🔸 NIVEL 3: MEDIO (Dependencias e Integraciones)
*Reportes, exports y utilidades que se romperán silenciosamente.*

### Reportes (Features/Reports)
- [ ] `app/Features/Reports/Http/Controllers/PlatformReportController.php`:
    - Queries como `CompanyRequest::count()` deben cambiar a `Company::pending()->count()`.
- [ ] `app/Features/Reports/Exports/CompanyRequestsExport.php`:
    - Cambiar fuente de datos.
- [ ] `app/Features/Reports/Exports/PlatformGrowthExport.php`.

### Manejo de Errores
- [ ] `app/Http/Middleware/ApiExceptionHandler.php`:
    - Eliminar chequeo `if ($model === ...CompanyRequest)`.

### Integraciones de Usuario
- [ ] `app/Features/UserManagement/Services/UserService.php`:
    - Método `createFromCompanyRequest`: Actualizar firma para recibir `Company`.

---

## 🔹 NIVEL 4: BAJO (Frontend y Rutas)
*Cambios de nombres y ajustes visuales.*

### Vistas Blade (Resources)
- [ ] `resources/views/app/platform-admin/requests/index.blade.php`:
    - Iterar sobre `$companies` (pendientes) en lugar de `$requests`.
- [ ] `resources/views/app/platform-admin/requests/partials/view-request-modal.blade.php`:
    - Ajustar nombres de variables.
- [ ] `resources/views/public/company-request.blade.php`:
    - El formulario manda al endpoint `store`, verificar nombres de campos si cambian (no deberían).
- [ ] `resources/views/app/platform-admin/dashboard.blade.php`:
    - Variable JS `company_requests_stats`.

### Rutas
- [ ] `routes/api.php`: Verificar Model Binding. `Route::post('/{companyRequest}...')` cambiará a `/{company}`.
- [ ] `routes/web.php`

---

## 🧪 NIVEL 5: TESTS (Aseguramiento)
*Tests que fallarán y necesitan actualización.*

- [ ] `tests/Feature/CompanyManagement/Controllers/CompanyRequestControllerStoreTest.php`
- [ ] `tests/Feature/CompanyManagement/Controllers/CompanyRequestAdminControllerApproveTest.php`
- [ ] `tests/Feature/CompanyManagement/Services/CompanyRequestServiceTest.php`
- [ ] `tests/Feature/CompanyManagement/CompanyDuplicateDetectionTest.php`
- [ ] `tests/Feature/Reports/PlatformReportTest.php` (si existe)

---

## 📝 Notas de Precaución
1.  **Enums:** Revisar `app/Shared/Enums/CompanyRequestStatus.php`. Puede reutilizarse o moverse a `CompanyStatus`.
2.  **Factories:** `CompanyRequestFactory.php` será obsoleto.
3.  **Seeders:** `CompanyRequestApprovalSimulationSeeder.php` debe reescribirse para simular el nuevo flujo unificado.

---

## 🔍 SEGUNDA PASADA: Hallazgos Adicionales

### ⚠️ CRÍTICO: Generación de Códigos (`request_code`)
- [ ] `app/Features/CompanyManagement/Services/CompanyRequestService.php` (Línea 32):
    ```php
    $requestCode = CodeGenerator::generate('business.company_requests', 'REQ', 'request_code');
    ```
    **¡ALERTA!** Esta línea asume que la tabla `business.company_requests` existe para calcular el siguiente número correlativo (REQ-001, REQ-002...).
    
    **Acción requerida:** Actualizar para que cuente sobre `company_onboarding_details` o la nueva tabla. Si no se actualiza, los códigos de solicitud se **reiniciarán desde 1** o **fallarán con error SQL**.

### ⚠️ CRÍTICO: Seeders Complejos
- [ ] `app/Features/CompanyManagement/Database/Seeders/CompanyRequestApprovalSimulationSeeder.php`:
    - Líneas 143, 148, 248, 370, 401, 414, 420, 422: Múltiples referencias a `request_code`.
    - Tiene lógica compleja para generar `request_code` únicos usando método `generateUniqueRequestCode()`.
    - **Este archivo DEBE reescribirse completamente** para simular el nuevo flujo unificado.

---

## 🔍 TERCERA PASADA: Archivos Adicionales Encontrados

### Resources (API Responses) - Lista Completa
- [ ] `app/Features/CompanyManagement/Http/Resources/CompanyRequestResource.php`:
    - Líneas 30, 33, 35, 37, 45: Usa `request_code`, `company_name`, `admin_email`, `request_message`, `estimated_users`.
- [ ] `app/Features/CompanyManagement/Http/Resources/CompanyRejectionResource.php`:
    - Línea 31: Usa `request_code`.
- [ ] `app/Features/CompanyManagement/Http/Resources/CompanyResource.php`:
    - Línea 98: Usa `created_from_request_id`.

### Form Requests (Validaciones) - Lista Completa
- [ ] `app/Features/CompanyManagement/Http/Requests/StoreCompanyRequestRequest.php`:
    - Validaciones para: `company_name`, `admin_email`, `request_message`, `estimated_users`.
    - Mensajes de error personalizados que mencionan estos campos.

### Listeners Adicionales
- [ ] `app/Features/CompanyManagement/Listeners/NotifyAdminOfNewRequest.php`:
    - Líneas 17, 19, 20, 21: Usa `request_code`, `company_name`, `admin_email`.
- [ ] `app/Features/CompanyManagement/Listeners/CreateCompanyFromRequest.php`:
    - Línea 21: Usa `request_code`.

### Vista Pública (Frontend)
- [ ] `resources/views/public/company-request.blade.php`:
    - Línea 392: Campo `estimated_users` en HTML.
    - Línea 895: JS envía `request_message`.
    - Línea 898: JS envía `estimated_users`.
    - Líneas 1031, 1047: Traducciones/mapeo de campos.

### Tests Adicionales Encontrados
- [ ] `tests/Feature/CompanyManagement/Controllers/CompanyRequestAdminControllerRejectTest.php` (si existe)
- [ ] `tests/Feature/CompanyManagement/Controllers/CompanyRequestControllerIndexTest.php` (si existe)

---

## 📊 RESUMEN ESTADÍSTICO

| Categoría | Archivos Afectados |
|-----------|-------------------|
| Migraciones | 2 (modificar) + 1 (nueva) |
| Modelos | 2 (modificar) + 1 (nuevo) + 1 (eliminar) |
| Servicios | 3 |
| Controladores | 2 |
| Eventos | 3 |
| Listeners | 5 |
| Jobs | 3 |
| Mails | 3 |
| Resources | 3 |
| Form Requests | 1 |
| Reportes/Exports | 3 |
| Middleware | 1 |
| Vistas Blade | 4 |
| Rutas | 2 |
| Tests | 5+ |
| Factories | 1 (obsoleto) |
| Seeders | 1 (reescribir) |
| Enums | 1 |
| **TOTAL ESTIMADO** | **~45 archivos** |

---

## ✅ CHECKLIST DE VALIDACIÓN POST-MIGRACIÓN

Después de aplicar todos los cambios, ejecutar:

1. [ ] `php artisan migrate` - Sin errores
2. [ ] `php artisan test --filter=Company` - Todos los tests pasan
3. [ ] Verificar endpoint `POST /api/company-requests` - Crea empresa con status pending
4. [ ] Verificar endpoint `POST /api/company-requests/{id}/approve` - Cambia status a active
5. [ ] Verificar Dashboard Admin - Estadísticas correctas
6. [ ] Verificar Reportes Excel - Exportan datos correctos
7. [ ] Verificar Formulario Público - Envía solicitud correctamente
