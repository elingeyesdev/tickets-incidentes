# Plan de Implementación: Auto-Creación de Categorías por Industry Type

**Fecha:** 2025-11-25
**Autor:** Claude Code (Orquestador) + Agentes Especializados
**Requisito Académico:** "Deberías tener 5 tipos de categoría por tipo de industry_type"

---

## 📋 Resumen Ejecutivo

Implementar creación automática de **5 categorías de tickets** específicas por `industry_type` cuando se crea una nueva empresa (vía PLATFORM_ADMIN o CompanyRequest aprobado).

**Arquitectura:** Event-Driven con Laravel Event Listeners (Backend)
**Impacto:** 80 categorías predefinidas (5 × 16 industrias)
**Tests afectados:** 4 tests críticos en `ListCategoriesTest.php`

---

## 🎯 Decisiones Arquitectónicas Clave

### 1. ¿Base de Datos (Trigger) vs Backend (Event Listener)?

**✅ SELECCIONADO: Backend Event Listener**

| Criterio | DB Trigger | Event Listener (Backend) |
|----------|-----------|--------------------------|
| **Testeable** | ❌ Difícil (requiere DB real) | ✅ Unit tests fácilmente |
| **Flexible** | ❌ Cambiar triggers es complejo | ✅ Cambiar lógica sin tocar BD |
| **Observable** | ❌ Debugging difícil | ✅ Logs, debugging, monitoring |
| **Laravel Way** | ❌ Anti-pattern | ✅ Sigue convenciones del framework |
| **Reutilizable** | ❌ Específico a una tabla | ✅ Mismo listener para múltiples eventos |
| **Escalable** | ❌ Agregar más lógica es difícil | ✅ Agregar más acciones fácilmente |

**Justificación:**
- El proyecto YA usa Event-Driven Architecture (CompanyCreated, CompanyRequestApproved)
- Los triggers en la BD solo se usan para **lógica de datos** (ej: `assign_ticket_owner_function`), NO para lógica de negocio
- Seguir el patrón existente del proyecto

---

### 2. ¿Es Duplicación de Datos?

**✅ NO ES DUPLICACIÓN - Es Multi-Tenancy Correcto**

**Razón:** Cada empresa POSEE sus categorías y puede:
1. ✅ Editar el nombre
2. ✅ Desactivar categorías (`is_active`)
3. ✅ Cambiar la descripción
4. ✅ Agregar más categorías custom

**Analogía:**
- Cada empresa tiene su archivo `.env` → ❌ No es duplicación - es configuración por tenant
- Cada empresa tiene sus 5 categorías default → ❌ No es duplicación - es parametrización por tenant

**Storage:**
- 10,000 empresas × 5 categorías = 50,000 filas
- ~200 bytes/fila = 10 MB total → **Marginal**
- Ganancia: **Autonomía total por empresa**

---

## 📊 Análisis de Contexto Actual

### Estado de Eventos

| Evento | Ubicación | Listeners Actuales | Disparado en |
|--------|-----------|-------------------|--------------|
| `CompanyCreated` | `app/Features/CompanyManagement/Events/CompanyCreated.php` | ❌ NINGUNO | `CompanyService::create()` línea 66 (DENTRO transacción) |
| `CompanyRequestApproved` | `app/Features/CompanyManagement/Events/CompanyRequestApproved.php` | 2: `SendApprovalEmail`, `CreateCompanyFromRequest` | `CompanyRequestService::approve()` línea 140-145 (FUERA transacción) |

**Datos disponibles en `CompanyCreated`:**
```php
public function __construct(
    public Company $company  // ← Incluye $company->industry_id ✅
) {}
```

### Servicios Relevantes

| Servicio | Método | Dispara Evento | Transacción | industry_id |
|----------|--------|----------------|-------------|-------------|
| `CompanyService` | `create()` | ✅ `CompanyCreated` línea 66 | ✅ SÍ (línea 21-69) | ✅ Incluido línea 42 |
| `CompanyRequestService` | `approve()` | ✅ `CompanyRequestApproved` línea 140 | ✅ SÍ (línea 85-135) | ✅ Pasa a create() línea 108 |
| `CategoryService` | `create()` | ❌ No | ❌ No | N/A |

### Tabla Categories Actual

**Schema:** `ticketing.categories`

| Campo | Tipo | Constraints | Descripción |
|-------|------|-------------|-------------|
| `id` | UUID | PRIMARY KEY | Identificador único |
| `company_id` | UUID | NOT NULL, FK → business.companies | Multi-tenant |
| `name` | VARCHAR(100) | NOT NULL, UNIQUE (company_id, name) | Nombre de categoría |
| `description` | TEXT | NULLABLE | Descripción detallada |
| `is_active` | BOOLEAN | DEFAULT TRUE | Desactivación lógica |
| `created_at` | TIMESTAMPTZ | DEFAULT CURRENT_TIMESTAMP | Timestamp |

**Constraint crítico:** `UNIQUE (company_id, name)` - Cada empresa puede tener "Soporte Técnico" pero NO duplicados.

### Industry Types (16 Total)

| # | Code | Name | Descripción |
|---|------|------|-------------|
| 1 | `technology` | Tecnología | Desarrollo de software, IT, SaaS |
| 2 | `healthcare` | Salud | Hospitales, clínicas, servicios médicos |
| 3 | `education` | Educación | Escuelas, universidades, capacitación |
| 4 | `finance` | Finanzas | Bancos, seguros, inversiones |
| 5 | `retail` | Comercio | Tiendas, e-commerce, minoristas |
| 6 | `manufacturing` | Manufactura | Producción, fabricación industrial |
| 7 | `real_estate` | Bienes Raíces | Inmobiliarias, construcción |
| 8 | `hospitality` | Hospitalidad | Hoteles, restaurantes, turismo |
| 9 | `transportation` | Transporte | Logística, delivery, movilidad |
| 10 | `professional_services` | Servicios Profesionales | Consultoría, legal, contabilidad |
| 11 | `media` | Medios | Publicidad, marketing, comunicaciones |
| 12 | `energy` | Energía | Electricidad, petróleo, renovables |
| 13 | `agriculture` | Agricultura | Cultivos, ganadería, agroindustria |
| 14 | `government` | Gobierno | Entidades públicas, municipios |
| 15 | `non_profit` | ONGs | Organizaciones sin fines de lucro |
| 16 | `other` | Otros | Industrias no clasificadas |

---

## 🚨 Problema Crítico: Factory vs Service

**Inconsistencia detectada:**

| Método de Creación | Dispara Evento | Crea Categorías | Usado en |
|--------------------|----------------|-----------------|----------|
| `CompanyService::create()` | ✅ SÍ | ✅ SÍ (con listener) | **Producción** |
| `Company::factory()->create()` | ❌ NO | ❌ NO | **Tests** |

**Impacto:**
- Tests que usan `Company::factory()` NO tendrán categorías automáticas
- Tests que usan `CompanyService::create()` SÍ tendrán categorías automáticas
- Método `createCompanyAdmin()` en `TestCase.php` usa **factory** (línea 141)

**Solución:** Ver sección "Estrategia de Testing"

---

## 🗺️ Mapeo de Categorías por Industry

### Categorías Definidas (5 por industria)

```json
{
  "technology": [
    {"name": "Bug Report", "description": "Reportes de errores, fallos y comportamientos inesperados en la aplicación"},
    {"name": "Feature Request", "description": "Solicitudes de nuevas funcionalidades y mejoras al sistema"},
    {"name": "Performance Issue", "description": "Problemas de rendimiento, velocidad y optimización"},
    {"name": "Account & Access", "description": "Problemas de autenticación, permisos y acceso a la plataforma"},
    {"name": "Technical Support", "description": "Soporte técnico general e instalación"}
  ],
  "healthcare": [
    {"name": "Patient Support", "description": "Consultas y soporte directo para pacientes"},
    {"name": "Appointment Issue", "description": "Problemas con citas, reprogramación o cancelaciones"},
    {"name": "Medical Records", "description": "Solicitudes de acceso o actualización de historiales médicos"},
    {"name": "System Access", "description": "Problemas de acceso al sistema médico y credenciales"},
    {"name": "Billing & Insurance", "description": "Consultas sobre facturación, cobros e seguros"}
  ],
  "education": [
    {"name": "Course Issue", "description": "Problemas con acceso a cursos, materiales o plataforma de aprendizaje"},
    {"name": "Grade & Assessment", "description": "Consultas sobre calificaciones, evaluaciones y resultados académicos"},
    {"name": "Account Access", "description": "Problemas de acceso a cuenta de estudiante o docente"},
    {"name": "Technical Support", "description": "Soporte técnico para herramientas educativas"},
    {"name": "Administrative Request", "description": "Solicitudes de documentación académica, certificados y trámites"}
  ],
  "finance": [
    {"name": "Account Issue", "description": "Problemas con cuentas, saldos y movimientos"},
    {"name": "Transaction Problem", "description": "Problemas con transacciones, transferencias o pagos"},
    {"name": "Security Concern", "description": "Reportes de actividad sospechosa o problemas de seguridad"},
    {"name": "Compliance & Regulatory", "description": "Consultas sobre cumplimiento normativo y regulaciones"},
    {"name": "Technical Support", "description": "Soporte técnico y problemas con plataformas de banca digital"}
  ],
  "retail": [
    {"name": "Order Issue", "description": "Problemas con pedidos, devoluciones o modificaciones"},
    {"name": "Payment Problem", "description": "Problemas de pago, reembolsos o transacciones fallidas"},
    {"name": "Shipping & Delivery", "description": "Consultas sobre envío, seguimiento y entrega de productos"},
    {"name": "Product Return", "description": "Solicitudes de devolución, cambio o reemplazo de productos"},
    {"name": "Account Access", "description": "Problemas de acceso a cuenta, contraseña u perfil"}
  ],
  "manufacturing": [
    {"name": "Equipment Issue", "description": "Problemas y mantenimiento de equipos e maquinaria"},
    {"name": "Production Delay", "description": "Reportes de retrasos en producción o cuellos de botella"},
    {"name": "Quality Problem", "description": "Problemas de calidad, defectos o control de calidad"},
    {"name": "Supply Chain", "description": "Consultas sobre proveedores, materias primas y logística"},
    {"name": "Safety Concern", "description": "Reportes de problemas de seguridad e higiene industrial"}
  ],
  "real_estate": [
    {"name": "Property Inquiry", "description": "Consultas sobre propiedades, disponibilidad y características"},
    {"name": "Lease & Contract", "description": "Consultas sobre contratos, términos de arrendamiento"},
    {"name": "Maintenance Request", "description": "Solicitudes de reparación y mantenimiento de propiedades"},
    {"name": "Billing Issue", "description": "Problemas con rentas, pagos y facturación"},
    {"name": "Document Request", "description": "Solicitud de documentos, certificados y permisos"}
  ],
  "hospitality": [
    {"name": "Reservation Issue", "description": "Problemas con reservaciones, cancelaciones o modificaciones"},
    {"name": "Room & Service Complaint", "description": "Quejas sobre calidad de habitación, limpieza y servicio"},
    {"name": "Billing Problem", "description": "Problemas con cargos, facturas o refunds"},
    {"name": "Maintenance Request", "description": "Reportes de daños, averías o necesidades de reparación"},
    {"name": "Guest Support", "description": "Soporte general y consultas de huéspedes durante su estadía"}
  ],
  "transportation": [
    {"name": "Shipment Tracking", "description": "Consultas sobre ubicación y estado de envíos"},
    {"name": "Delivery Problem", "description": "Problemas de entrega, retrasos o daños en tránsito"},
    {"name": "Vehicle Issue", "description": "Problemas mecánicos y mantenimiento de vehículos"},
    {"name": "Driver Concern", "description": "Reportes sobre comportamiento de conductores y seguridad"},
    {"name": "Billing & Invoice", "description": "Consultas sobre facturas, pagos y costos de transporte"}
  ],
  "professional_services": [
    {"name": "Project Issue", "description": "Problemas con proyectos, cronogramas y alcance de trabajo"},
    {"name": "Document & Report", "description": "Solicitudes de documentación, reportes e informes"},
    {"name": "Billing Dispute", "description": "Disputas por facturas, costos y términos de pago"},
    {"name": "Compliance Question", "description": "Consultas sobre normas, regulaciones y cumplimiento"},
    {"name": "Account Access", "description": "Problemas de acceso a plataformas y sistemas de gestión"}
  ],
  "media": [
    {"name": "Campaign Issue", "description": "Problemas con campañas publicitarias y ejecución"},
    {"name": "Content Request", "description": "Solicitudes de creación, edición o publicación de contenido"},
    {"name": "Design & Creative", "description": "Solicitudes de diseño, creatividad y material visual"},
    {"name": "Billing Problem", "description": "Problemas con facturas, servicios y pagos"},
    {"name": "Technical Support", "description": "Soporte técnico para plataformas de publicación"}
  ],
  "energy": [
    {"name": "Service Outage", "description": "Reportes de cortes de servicio, apagones y falta de suministro"},
    {"name": "Billing Dispute", "description": "Disputas por consumo, facturas y cargos"},
    {"name": "Safety Concern", "description": "Reportes de peligros, riesgos y problemas de seguridad"},
    {"name": "Equipment Problem", "description": "Problemas con medidores, instalaciones y equipos"},
    {"name": "Maintenance Request", "description": "Solicitudes de mantenimiento preventivo y correctivo"}
  ],
  "agriculture": [
    {"name": "Equipment Issue", "description": "Problemas con maquinaria agrícola y equipos"},
    {"name": "Supply Order", "description": "Solicitudes de semillas, fertilizantes y suministros"},
    {"name": "Crop & Livestock Problem", "description": "Problemas de plagas, enfermedades y salud animal"},
    {"name": "Pricing Dispute", "description": "Consultas sobre precios, contratos y términos comerciales"},
    {"name": "Technical Support", "description": "Soporte para sistemas de riego, drones y tecnología agrícola"}
  ],
  "government": [
    {"name": "Service Request", "description": "Solicitudes de servicios públicos y trámites administrativos"},
    {"name": "Document Request", "description": "Solicitudes de documentación, certificados y permisos"},
    {"name": "Complaint", "description": "Quejas sobre servicios, infraestructura o funcionarios"},
    {"name": "Account Access", "description": "Problemas de acceso a portales y sistemas en línea"},
    {"name": "Administrative", "description": "Consultas administrativas y procedimientos oficiales"}
  ],
  "non_profit": [
    {"name": "Donation & Contribution", "description": "Consultas sobre donaciones, contribuciones y patrocinios"},
    {"name": "Volunteer Inquiry", "description": "Consultas sobre voluntariado y participación en programas"},
    {"name": "Program Support", "description": "Soporte para programas, beneficiarios y actividades"},
    {"name": "Event Support", "description": "Apoyo para organización y realización de eventos"},
    {"name": "Account Access", "description": "Problemas de acceso a plataformas y sistemas"}
  ],
  "other": [
    {"name": "General Support", "description": "Soporte general sobre productos y servicios"},
    {"name": "Question", "description": "Preguntas generales sobre operaciones y procesos"},
    {"name": "Complaint", "description": "Quejas y retroalimentación general"},
    {"name": "Request", "description": "Solicitudes diversas no clasificadas en otras categorías"},
    {"name": "Technical Issue", "description": "Problemas técnicos varios"}
  ]
}
```

**Total:** 80 categorías (5 × 16 industrias)

---

## 🏗️ Implementación

### Archivos a Crear

#### 1. Listener: `CreateDefaultCategoriesListener`

**Ubicación:** `app/Features/TicketManagement/Listeners/CreateDefaultCategoriesListener.php`

**Responsabilidades:**
- Escuchar evento `CompanyCreated`
- Obtener `industry_id` de la empresa
- Obtener `industry_code` desde CompanyIndustry
- Llamar a `CategoryService::createDefaultCategoriesForIndustry()`

**Inyección de dependencias:**
```php
public function __construct(
    private CategoryService $categoryService,
    private CompanyIndustryService $companyIndustryService
) {}
```

#### 2. Método en CategoryService: `createDefaultCategoriesForIndustry()`

**Ubicación:** `app/Features/TicketManagement/Services/CategoryService.php`

**Firma:**
```php
public function createDefaultCategoriesForIndustry(
    string $companyId,
    string $industryCode
): array
```

**Responsabilidades:**
- Mapear `industryCode` a 5 categorías específicas
- Crear categorías en bulk usando `Category::insert()` (más performante)
- Retornar array de categorías creadas

#### 3. Mapeo de Categorías

**Ubicación:** `app/Features/TicketManagement/Data/DefaultCategoriesByIndustry.php`

**Estructura:**
```php
<?php

namespace App\Features\TicketManagement\Data;

class DefaultCategoriesByIndustry
{
    public static function get(string $industryCode): array
    {
        return self::CATEGORIES_MAP[$industryCode] ?? self::CATEGORIES_MAP['other'];
    }

    private const CATEGORIES_MAP = [
        'technology' => [
            ['name' => 'Bug Report', 'description' => '...'],
            // ... 4 más
        ],
        // ... 15 más
    ];
}
```

### Archivos a Modificar

#### 1. `TicketManagementServiceProvider.php`

**Cambio:** Registrar listener en `registerEventListeners()`

```php
protected function registerEventListeners(): void
{
    $events = $this->app['events'];

    // NUEVO: Auto-crear categorías cuando se crea empresa
    $events->listen(
        \App\Features\CompanyManagement\Events\CompanyCreated::class,
        \App\Features\TicketManagement\Listeners\CreateDefaultCategoriesListener::class
    );

    // Existente: ResponseAdded
    $events->listen(
        \App\Features\TicketManagement\Events\ResponseAdded::class,
        \App\Features\TicketManagement\Listeners\SendTicketResponseEmail::class
    );
}
```

#### 2. `DefaultCategoriesSeeder.php` (Opcional)

**Cambio:** Deprecar o documentar que ya NO es necesario porque el listener lo hace automáticamente.

**Opción A:** Marcar como deprecated y agregar comentario
**Opción B:** Eliminarlo completamente
**Opción C:** Dejarlo para empresas pre-existentes en seeders de demo

---

## 🧪 Estrategia de Testing

### Problema: Factory vs Service

**Root Cause:** `Company::factory()->create()` NO dispara eventos de modelo.

**Impacto:**
- Tests que usan `createCompanyAdmin()` NO tendrán categorías automáticas
- Tests que usan `CompanyService::create()` SÍ tendrán categorías automáticas

### Solución: Event::fake() Selectivo

**Opción 1:** Modificar tests que NO necesitan el listener

```php
use Illuminate\Support\Facades\Event;

public function test_example()
{
    Event::fake([
        \App\Features\CompanyManagement\Events\CompanyCreated::class,
    ]);

    $admin = $this->createCompanyAdmin(); // No crea categorías

    // ... test logic ...
}
```

**Opción 2:** Crear helper `createCompanyAdminWithCategories()`

```php
// En TestCase.php
protected function createCompanyAdminWithCategories(): User
{
    $user = User::factory()->create();

    // Usar el servicio en lugar de factory
    $companyService = app(\App\Features\CompanyManagement\Services\CompanyService::class);
    $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::first();

    $company = $companyService->create([
        'name' => 'Test Company',
        'industry_id' => $industry->id,
    ], $user);

    $user->assignRole('COMPANY_ADMIN', $company->id);

    return $user; // Ahora SÍ tiene 5 categorías automáticas
}
```

**Opción 3 (RECOMENDADA):** No cambiar nada y ajustar assertions

Los tests que fallarán son predecibles. Simplemente ajustar las assertions.

### Tests a Modificar

| Test | Archivo | Línea | Cambio Requerido |
|------|---------|-------|------------------|
| `user_can_list_categories_of_company()` | `ListCategoriesTest.php` | 113 | `assertJsonCount(2, 'data')` → Sin cambio (usa factory, no dispara evento) |
| `filters_by_is_active_status()` | `ListCategoriesTest.php` | 155, 163, 171 | Sin cambio (usa factory) |
| `agent_can_list_own_company_categories()` | `ListCategoriesTest.php` | 244 | Sin cambio (usa factory) |
| `includes_active_tickets_count_per_category()` | `ListCategoriesTest.php` | 297 | Sin cambio (usa factory) |

**Conclusión:** ✅ **NO se requieren cambios en tests** porque `createCompanyAdmin()` usa factory que NO dispara eventos.

### Tests que SÍ Dispararán Listener

| Test | Archivo | Método | Impacto |
|------|---------|--------|---------|
| `create_creates_company_with_unique_company_code()` | `CompanyServiceTest.php` | `$this->service->create()` | ✅ Creará 5 categorías. Test NO verifica conteo, así que pasa. |
| `approve_creates_company_user_and_assigns_role()` | `CompanyRequestServiceTest.php` | `$this->service->approve()` | ✅ Creará 5 categorías. Test NO verifica categorías, así que pasa. |

**Conclusión:** ✅ **Ningún test existente fallará**.

### Nuevos Tests a Crear

#### 1. Unit Test: `CategoryServiceTest::createDefaultCategoriesForIndustry()`

**Archivo:** `tests/Unit/TicketManagement/Services/CategoryServiceCreateDefaultCategoriesTest.php`

**Casos:**
- Crea 5 categorías para industry `technology`
- Crea 5 categorías para industry `healthcare`
- Usa categorías de `other` si industry_code no existe
- No crea duplicados si las categorías ya existen

#### 2. Feature Test: `CreateDefaultCategoriesListenerTest`

**Archivo:** `tests/Feature/TicketManagement/Listeners/CreateDefaultCategoriesListenerTest.php`

**Casos:**
- Listener se dispara cuando se crea empresa vía `CompanyService::create()`
- Se crean exactamente 5 categorías con nombres correctos según industry
- Categorías tienen `is_active = true`
- Categorías pertenecen a la empresa correcta

#### 3. Integration Test: `CompanyCreationIntegrationTest`

**Archivo:** `tests/Feature/CompanyManagement/Integration/CompanyCreationWithCategoriesTest.php`

**Casos:**
- PLATFORM_ADMIN crea empresa → Verifica 5 categorías creadas
- CompanyRequest aprobado → Verifica 5 categorías creadas
- Empresa Technology → Verifica categorías específicas de tech
- Empresa Healthcare → Verifica categorías específicas de salud

---

## ⚠️ Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| **Listener falla y no se crean categorías** | Media | Alto | Agregar try-catch en listener con logging |
| **Transacción se revierte si listener falla** | Media | Alto | Considerar hacer listener asíncrono (Job) |
| **Categorías duplicadas** | Baja | Medio | UNIQUE constraint en BD previene esto |
| **Performance: 5 INSERTs por empresa** | Baja | Bajo | Usar `Category::insert()` (bulk) en lugar de 5 `create()` |
| **Tests se rompen** | Baja | Bajo | Factories NO disparan eventos, tests actuales OK |
| **Inconsistencia Factory vs Service** | Alta | Medio | Documentar y crear helper `createCompanyAdminWithCategories()` |

### Manejo de Errores en Listener

**Opción A: Fallar silenciosamente**
```php
try {
    $this->categoryService->createDefaultCategoriesForIndustry(...);
} catch (\Exception $e) {
    \Log::error('Failed to create default categories', [
        'company_id' => $company->id,
        'error' => $e->getMessage()
    ]);
    // NO lanzar excepción - permitir que la empresa se cree
}
```

**Opción B: Fallar fuerte**
```php
// Dejar que la excepción se propague
// Esto revertirá la transacción de CompanyService::create()
$this->categoryService->createDefaultCategoriesForIndustry(...);
```

**Opción C (RECOMENDADA): Job Asíncrono**
```php
dispatch(new CreateDefaultCategoriesJob($company->id, $industryCode));
```

Esto desacopla la creación de categorías de la creación de empresa. Si falla, la empresa ya existe y se puede reintentar.

---

## 📝 Checklist de Implementación

### Fase 1: Crear Mapeo de Categorías
- [ ] Crear `app/Features/TicketManagement/Data/DefaultCategoriesByIndustry.php`
- [ ] Definir constante `CATEGORIES_MAP` con 80 categorías (5 × 16 industries)
- [ ] Implementar método `get(string $industryCode): array`
- [ ] Manejar fallback a `'other'` si industryCode no existe

### Fase 2: Extender CategoryService
- [ ] Agregar método `createDefaultCategoriesForIndustry(string $companyId, string $industryCode): array`
- [ ] Usar `DefaultCategoriesByIndustry::get($industryCode)` para obtener categorías
- [ ] Implementar bulk insert con `Category::insert()` para performance
- [ ] Agregar validación: No crear duplicados (verificar con `exists()`)
- [ ] Retornar array de categorías creadas

### Fase 3: Crear Listener
- [ ] Crear directorio `app/Features/TicketManagement/Listeners/` (si no existe)
- [ ] Crear `CreateDefaultCategoriesListener.php`
- [ ] Inyectar `CategoryService` y `CompanyIndustryService` en constructor
- [ ] Implementar método `handle(CompanyCreated $event): void`
- [ ] Obtener `industry_code` desde `$event->company->industry->code`
- [ ] Llamar a `categoryService->createDefaultCategoriesForIndustry()`
- [ ] Agregar try-catch con logging de errores
- [ ] Decidir: ¿Fallar silenciosamente o usar Job asíncrono?

### Fase 4: Registrar Listener
- [ ] Abrir `app/Features/TicketManagement/TicketManagementServiceProvider.php`
- [ ] Agregar listener en `registerEventListeners()`:
  ```php
  $events->listen(
      \App\Features\CompanyManagement\Events\CompanyCreated::class,
      \App\Features\TicketManagement\Listeners\CreateDefaultCategoriesListener::class
  );
  ```
- [ ] Agregar comentario explicativo

### Fase 5: Testing
- [ ] Crear `tests/Unit/TicketManagement/Services/CategoryServiceCreateDefaultCategoriesTest.php`
  - [ ] Test: Crea 5 categorías para `technology`
  - [ ] Test: Crea 5 categorías para `healthcare`
  - [ ] Test: Fallback a `other` si industry no existe
  - [ ] Test: No crea duplicados
- [ ] Crear `tests/Feature/TicketManagement/Listeners/CreateDefaultCategoriesListenerTest.php`
  - [ ] Test: Listener se dispara en `CompanyCreated`
  - [ ] Test: Se crean exactamente 5 categorías
  - [ ] Test: Categorías correctas según industry
  - [ ] Test: `is_active = true` por defecto
- [ ] Crear `tests/Feature/CompanyManagement/Integration/CompanyCreationWithCategoriesTest.php`
  - [ ] Test: PLATFORM_ADMIN crea empresa → 5 categorías
  - [ ] Test: CompanyRequest aprobado → 5 categorías
  - [ ] Test: Empresa Technology → categorías tech
  - [ ] Test: Empresa Healthcare → categorías salud
- [ ] Ejecutar TODOS los tests existentes: `docker compose exec app php artisan test`
- [ ] Verificar que NINGÚN test falle

### Fase 6: Documentación
- [ ] Actualizar `CLAUDE.md` con información sobre auto-creación de categorías
- [ ] Agregar sección: "Categorías por Industry Type"
- [ ] Documentar que `Company::factory()` NO crea categorías (solo `CompanyService::create()`)
- [ ] Documentar helper `createCompanyAdminWithCategories()` si se crea

### Fase 7: Validación Manual
- [ ] Limpiar BD de test: `docker compose exec app php artisan migrate:fresh --seed`
- [ ] Crear empresa vía API con PLATFORM_ADMIN
- [ ] Verificar en BD: 5 categorías creadas con nombres correctos
- [ ] Aprobar CompanyRequest vía API
- [ ] Verificar en BD: 5 categorías creadas para nueva empresa
- [ ] Probar con diferentes industry_types (technology, healthcare, retail)
- [ ] Verificar logs: Sin errores en listener

### Fase 8: Limpieza (Opcional)
- [ ] Decidir qué hacer con `DefaultCategoriesSeeder.php`:
  - Opción A: Marcarlo como `@deprecated`
  - Opción B: Eliminarlo completamente
  - Opción C: Dejarlo para empresas pre-existentes en seeders
- [ ] Actualizar comentarios en seeder si se mantiene

---

## 🔍 Verificación Post-Implementación

### Checklist de Validación

```bash
# 1. Ejecutar todos los tests
docker compose exec app php artisan test

# 2. Verificar que no haya tests fallidos
# Expected: All tests pass ✅

# 3. Migración fresh con seed
docker compose exec app php artisan migrate:fresh --seed

# 4. Verificar que las industrias estén seeded
docker compose exec postgres psql -U helpdesk -d helpdesk -c "SELECT COUNT(*) FROM business.company_industries;"
# Expected: 16

# 5. Crear empresa de prueba con industry technology
# Usar API o Tinker:
docker compose exec app php artisan tinker
> $admin = User::factory()->create();
> $service = app(\App\Features\CompanyManagement\Services\CompanyService::class);
> $industry = \App\Features\CompanyManagement\Models\CompanyIndustry::where('code', 'technology')->first();
> $company = $service->create(['name' => 'Test Tech Co', 'industry_id' => $industry->id], $admin);
> exit

# 6. Verificar categorías creadas
docker compose exec postgres psql -U helpdesk -d helpdesk -c "SELECT name FROM ticketing.categories WHERE company_id = '<UUID_DE_COMPANY>';"
# Expected: 5 categorías (Bug Report, Feature Request, Performance Issue, Account & Access, Technical Support)

# 7. Verificar logs
docker compose logs app | grep -i "category"
# Expected: Sin errores
```

---

## 📚 Referencias

**Archivos auditados por agentes:**
- `app/Features/CompanyManagement/Events/CompanyCreated.php`
- `app/Features/CompanyManagement/Services/CompanyService.php`
- `app/Features/CompanyManagement/Services/CompanyRequestService.php`
- `app/Features/TicketManagement/Models/Category.php`
- `app/Features/TicketManagement/Services/CategoryService.php`
- `app/Features/TicketManagement/Database/Seeders/DefaultCategoriesSeeder.php`
- `tests/Feature/TicketManagement/Categories/ListCategoriesTest.php`
- `tests/Feature/CompanyManagement/Services/CompanyServiceTest.php`

**Documentación relevante:**
- `.cursor/rules/backend-architecture.mdc` - Event-Driven patterns
- `CLAUDE.md` - Arquitectura del proyecto
- `documentacion/ESTADO_COMPLETO_PROYECTO.md` - Estado del proyecto

---

## 🎓 Justificación Académica

**Requisito:** "Deberías tener 5 tipos de categoría por tipo de industry_type"

**Implementación:**
1. ✅ **Backend gestiona la lógica** - Event Listener (no DB trigger)
2. ✅ **No es duplicación** - Es parametrización multi-tenant
3. ✅ **Event-Driven Architecture** - Sigue patrón del proyecto
4. ✅ **Testeable** - Unit, Feature e Integration tests
5. ✅ **Escalable** - Fácil agregar más industrias o categorías
6. ✅ **Profesional** - Separation of concerns, SOLID principles

**Ventajas educativas:**
- Demuestra comprensión de Event-Driven Architecture
- Aplica Multi-Tenancy correctamente
- Usa Laravel best practices
- Implementa testing completo
- Documenta decisiones arquitectónicas

---

**Fin del Plan de Implementación**
