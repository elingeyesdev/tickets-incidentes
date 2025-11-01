# PLAN DE REFACTORIZACIÓN V8.0 - Company Management Feature

## 🎯 CONTEXTO DEL PROYECTO

**Ubicación:** `app/Features/CompanyManagement/`
**Arquitectura:** Feature-first Laravel con GraphQL API
**Testing:** PHPUnit con RefreshDatabase

Necesito implementar cambios en la base de datos V8.0 del sistema Helpdesk. Los cambios son:

### Cambios en `business.company_requests`:
- ❌ Eliminar: `business_description`
- ✅ Agregar: `company_description TEXT NOT NULL` (descripción pública)
- ✅ Agregar: `request_message TEXT NOT NULL` (justificación privada)

### Cambios en `business.companies`:
- ✅ Agregar: `description TEXT` (hereda de company_description)
- ✅ Agregar: `industry_id UUID` con FK a `business.company_industries`

### Nueva tabla `business.company_industries`:
```sql
CREATE TABLE business.company_industries (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
```

---

## 📂 ARCHIVOS A MODIFICAR

**Feature afectado:** `app/Features/CompanyManagement/`

Los cambios impactan:
1. **Migraciones**: Crear nueva migración para V8.0
2. **Modelos**: `Company.php`, `CompanyRequest.php`, nuevo `CompanyIndustry.php`
3. **Requests**: `RequestCompanyInput.php`, `CreateCompanyInput.php`
4. **Resources**: Todos los GraphQL types que exponen estos campos
5. **Servicios**: `CompanyRequestService.php`, `CompanyService.php`
6. **Controladores**: `CompanyRequestController.php`, lógica de aprobación
7. **Seeders**: Agregar `CompanyIndustrySeeder.php`
8. **Factories**: Actualizar para los nuevos campos
9. **Tests**: Feature tests y unit tests de todo el feature

---

## 🎯 OBJETIVO

Quiero que me ayudes a:

1. **Organizar la implementación** en fases secuenciales
2. **Dividir el trabajo** en tareas pequeñas y manejables
3. **Mantener calidad** con tests en cada fase
4. **Evitar breaking changes** migrando datos existentes
5. **Documentar** cada cambio para el equipo

---

## 📋 METODOLOGÍA REQUERIDA

### FASE 1: ANÁLISIS Y PLANIFICACIÓN
**Acción:** Antes de tocar código, analiza:
- Lee TODOS los archivos del feature `CompanyManagement`
- Identifica dependencias entre archivos
- Crea un plan de implementación secuencial
- Identifica riesgos (datos existentes, APIs públicas, etc.)

**Entregable:** Un plan detallado con orden de implementación

---

### FASE 2: IMPLEMENTACIÓN BASE DE DATOS
**Orden sugerido:**
1. Crear migración para `company_industries`
2. Crear seeder con las 16 industrias estándar
3. Crear migración para modificar `company_requests`
4. Crear migración para modificar `companies`
5. Ejecutar y verificar migraciones en local

**Validación:** Correr migraciones en DB de testing

---

### FASE 3: MODELOS Y RELACIONES
**Orden sugerido:**
1. Crear modelo `CompanyIndustry.php`
2. Actualizar modelo `Company.php` (relación industry, atributo description)
3. Actualizar modelo `CompanyRequest.php` (company_description, request_message)
4. Actualizar factories con los nuevos campos

**Validación:** Tests unitarios de modelos y relaciones

---

### FASE 4: CAPA DE SERVICIO
**Orden sugerido:**
1. Actualizar `CompanyRequestService` (lógica de aprobación con descripción)
2. Actualizar `CompanyService` (incluir industry_id en queries)
3. Actualizar validaciones de inputs

**Validación:** Tests unitarios de servicios

---

### FASE 5: CAPA DE PRESENTACIÓN (GraphQL)
**Orden sugerido:**
1. Actualizar GraphQL Types (Company, CompanyRequest, CompanyIndustry)
2. Actualizar Inputs (RequestCompanyInput, CreateCompanyInput)
3. Actualizar Resolvers si es necesario
4. Actualizar Resources

**Validación:** Tests de integración GraphQL

---

### FASE 6: TESTS COMPLETOS
**Orden sugerido:**
1. Actualizar feature tests existentes
2. Agregar tests para nuevos campos
3. Agregar tests para catálogo de industrias
4. Verificar cobertura de código

**Validación:** `php artisan test --coverage`

---

### FASE 7: MIGRACIÓN DE DATOS
**Orden sugerido:**
1. Crear script de migración de datos existentes
2. Mapear `industry_type` (string) → `industry_id` (UUID)
3. Copiar `business_description` → `company_description` en requests existentes
4. Validar integridad de datos

**Validación:** Verificar que no hay datos perdidos

---

## 🚨 REGLAS CRÍTICAS

### NO HACER:
- ❌ NO modificar múltiples archivos a la vez sin plan
- ❌ NO borrar campos sin verificar dependencias
- ❌ NO hacer cambios sin tests
- ❌ NO commitear código que rompa tests existentes

### SÍ HACER:
- ✅ Un archivo a la vez, commits atómicos
- ✅ Tests antes de cada commit
- ✅ Documentar cambios en cada archivo
- ✅ Verificar backwards compatibility
- ✅ Correr `php artisan test` después de cada cambio

---

## 🔍 PREGUNTAS PARA CLAUDE CODE

1. **¿Has leído todos los archivos del feature CompanyManagement?**
2. **¿Identificaste alguna dependencia que pueda romperse?**
3. **¿Hay datos en producción que debamos migrar?**
4. **¿Los cambios afectan APIs públicas documentadas?**
5. **¿Propones algún cambio adicional para mejorar la implementación?**

---

## 📦 ENTREGABLES ESPERADOS

Al finalizar, deberías tener:
- [ ] Migración V8.0 ejecutada exitosamente
- [ ] Todos los tests pasando (100% green)
- [ ] Datos existentes migrados correctamente
- [ ] Documentación actualizada
- [ ] No hay breaking changes en la API
- [ ] Cobertura de tests >= 80%

---

## 🎬 INSTRUCCIONES DE INICIO

**Por favor:**

1. **Lee este plan completo**
2. **Analiza el feature CompanyManagement** en el proyecto
3. **Propón un orden de implementación específico** con nombres de archivos
4. **Identifica riesgos** que yo deba conocer
5. **Dame un checklist** de tareas para aprobar antes de empezar

**Formato de respuesta esperado:**

```
## ANÁLISIS COMPLETADO

### Archivos identificados:
- [lista de archivos relevantes]

### Orden de implementación propuesto:
1. [Tarea 1 con archivos específicos]
2. [Tarea 2 con archivos específicos]
...

### Riesgos identificados:
- [Riesgo 1]
- [Riesgo 2]

### Preguntas antes de empezar:
- [Pregunta 1]
- [Pregunta 2]

### Checklist de aprobación:
- [ ] Punto 1
- [ ] Punto 2
```

**¿Estás listo para comenzar?**


luego del cambio nueva tabla:
CREATE TABLE business.company_industries (
id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
code VARCHAR(50) UNIQUE NOT NULL,
name VARCHAR(100) NOT NULL,
description TEXT,
created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);
Poblar industrias
INSERT INTO business.company_industries (code, name, description) VALUES
('technology', 'Tecnología', 'Desarrollo de software, IT, SaaS'),
('healthcare', 'Salud', 'Hospitales, clínicas, servicios médicos'),
('education', 'Educación', 'Escuelas, universidades, capacitación'),
('finance', 'Finanzas', 'Bancos, seguros, inversiones'),
('retail', 'Comercio', 'Tiendas, e-commerce, minoristas'),
('manufacturing', 'Manufactura', 'Producción, fabricación industrial'),
('real_estate', 'Bienes Raíces', 'Inmobiliarias, construcción'),
('hospitality', 'Hospitalidad', 'Hoteles, restaurantes, turismo'),
('transportation', 'Transporte', 'Logística, delivery, movilidad'),
('professional_services', 'Servicios Profesionales', 'Consultoría, legal, contabilidad'),
('media', 'Medios', 'Publicidad, marketing, comunicaciones'),
('energy', 'Energía', 'Electricidad, petróleo, renovables'),
('agriculture', 'Agricultura', 'Cultivos, ganadería, agroindustria'),
('government', 'Gobierno', 'Entidades públicas, municipios'),
('non_profit', 'ONGs', 'Organizaciones sin fines de lucro'),
('other', 'Otros', 'Industrias no clasificadas');

nuevo indice importante 

CREATE INDEX idx_companies_industry_id ON business.companies(industry_id);

como quedaria la tabla de compania 
CREATE TABLE business.companies (
id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
company_code VARCHAR(20) UNIQUE NOT NULL, -- CMP-2025-00001

    -- Información básica y de contacto
    name VARCHAR(200) NOT NULL,                 -- Nombre comercial
    legal_name VARCHAR(250),                    -- Razón social
    description TEXT,                           -- Descripción pública de la empresa
    support_email CITEXT,                       -- Email público de soporte
    phone VARCHAR(20),
    website VARCHAR(200),

    -- Dirección
    contact_address TEXT,
    contact_city VARCHAR(100),
    contact_state VARCHAR(100),
    contact_country VARCHAR(100),
    contact_postal_code VARCHAR(20),

    -- Información legal y fiscal
    tax_id VARCHAR(50),                         -- RUT/NIT (considerar encriptación)
    legal_representative VARCHAR(200),          -- Representante legal

    -- Categorización
    industry_id UUID REFERENCES business.company_industries(id),

    -- Configuración operativa (JSONB para flexibilidad)
    business_hours JSONB DEFAULT '{"monday": {"open": "09:00", "close": "18:00"}, "tuesday": {"open": "09:00", "close": "18:00"}, "wednesday": {"open": "09:00", "close": "18:00"}, "thursday": {"open": "09:00", "close": "18:00"}, "friday": {"open": "09:00", "close": "18:00"}}'::JSONB,
    timezone VARCHAR(50) DEFAULT 'America/La_Paz',

    -- Branding
    logo_url VARCHAR(500),
    favicon_url VARCHAR(500),
    primary_color VARCHAR(7) DEFAULT '#007bff',
    secondary_color VARCHAR(7) DEFAULT '#6c757d',

    -- Configuración adicional flexible
    settings JSONB DEFAULT '{}'::JSONB,

    -- Estado
    status VARCHAR(20) DEFAULT 'active' NOT NULL, -- active, suspended

    -- Trazabilidad
    created_from_request_id UUID REFERENCES business.company_requests(id),
    admin_user_id UUID NOT NULL REFERENCES auth.users(id),

    -- Auditoría
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

como quedaria:
-- TABLA 7: COMPANY_REQUESTS (PROCESO DE ONBOARDING)
CREATE TABLE business.company_requests (
id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
request_code VARCHAR(20) UNIQUE NOT NULL, -- REQ-2025-00001

    -- Datos del formulario público
    company_name VARCHAR(200) NOT NULL,
    legal_name VARCHAR(250),
    admin_email CITEXT NOT NULL,
    company_description TEXT NOT NULL,        -- Descripción pública de la empresa
    request_message TEXT NOT NULL,            -- Justificación privada de la solicitud
    website VARCHAR(200),
    industry_type VARCHAR(100) NOT NULL,
    estimated_users INT,
    contact_address TEXT,
    contact_city VARCHAR(100),
    contact_country VARCHAR(100),
    contact_postal_code VARCHAR(20),
    tax_id VARCHAR(50), -- RUT, NIT, Tax ID 

    status business.request_status DEFAULT 'pending' NOT NULL,

    -- Proceso de revisión
    reviewed_by UUID REFERENCES auth.users(id), -- Admin plataforma que revisó
    reviewed_at TIMESTAMPTZ,
    rejection_reason TEXT,

    -- Link to created company (if approved)
    created_company_id UUID,

    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

