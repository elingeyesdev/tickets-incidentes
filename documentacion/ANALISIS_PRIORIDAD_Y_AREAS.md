# Análisis: Prioridad, Áreas y Departamentos en Tickets

**Documento de análisis y recomendaciones arquitectónicas**
**Fecha:** Noviembre 26, 2025
**Estado:** Propuesta para implementación

---

## 📋 Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Análisis Actual del Sistema](#análisis-actual-del-sistema)
3. [Atributo: Prioridad](#atributo-prioridad)
4. [Atributos: Área y Departamento](#atributos-área-y-departamento)
5. [Comparación: Área vs Categoría](#comparación-área-vs-categoría)
6. [Recomendación Final](#recomendación-final)
7. [Arquitectura Propuesta](#arquitectura-propuesta)
8. [Plan de Implementación](#plan-de-implementación)
9. [Ejemplos Visuales](#ejemplos-visuales)
10. [Auto-Escalada de Prioridad (24h sin respuesta)](#auto-escalada-de-prioridad-24h-sin-respuesta)
11. [Sistema de Recordatorios para Usuarios](#sistema-de-recordatorios-para-usuarios)

---

## 🎯 Resumen Ejecutivo

Se proponen **CUATRO mejoras** al sistema de tickets:

### 1. **Prioridad** (Simple - Baja Complejidad)
- Atributo ENUM como `TicketStatus`
- Valores: `low`, `medium`, `high`, `critical`
- **Complejidad:** ⭐ BAJA (150-200 líneas de código)
- **Tiempo:** 15-30 minutos
- **Impacto:** Mínimo, no afecta lógica existente

### 2. **Área** (Moderada - Media Complejidad)
- Nueva entidad para agrupar agentes por función
- Soluciona problema de routing y asignación de tickets
- **Complejidad:** ⭐⭐ MEDIA (1000+ líneas de código)
- **Tiempo:** 4-6 horas
- **Impacto:** Moderado, extiende capacidades de ticket management

### 3. **Auto-Escalada de Prioridad** (Simple - Baja Complejidad)
- Si ticket no recibe respuesta de agente en 24h → prioridad cambia a `HIGH`
- Implementado con Job/Scheduler + Event Listener
- **Complejidad:** ⭐ BAJA (200-300 líneas de código)
- **Tiempo:** 30-45 minutos
- **Impacto:** Bajo, mejora SLA tracking

### 4. **Sistema de Recordatorios** (Simple - Baja Complejidad)
- Endpoint para que agentes envíen email de recordatorio a usuarios
- Si usuario no responde por mucho tiempo
- **Complejidad:** ⭐ BAJA (250-350 líneas de código)
- **Tiempo:** 45 minutos - 1 hora
- **Impacto:** Bajo, mejora engagement con usuarios

---

## 📊 Análisis Actual del Sistema

### Estructura Multi-Tenant Existente

```
Platform (Global)
│
└── Company (Empresa)
    ├── Users (Agentes/Admins/Usuarios)
    │   └── Roles: USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN
    │
    ├── Categories (Clasificación de problemas)
    │   └── Per-company: cada empresa crea sus propias
    │
    └── Tickets
        ├── Creados por: USER
        ├── Asignados a: AGENT (directo)
        └── Status: open → pending → resolved → closed
```

### Estado Actual de Campos en Tickets

| Campo | Tipo | ¿Existe? | Propósito |
|-------|------|----------|-----------|
| `id` | UUID | ✅ | Clave primaria |
| `ticket_code` | VARCHAR | ✅ | Identificador humano (TKT-2025-00001) |
| `company_id` | UUID FK | ✅ | Empresa propietaria |
| `created_by_user_id` | UUID FK | ✅ | Usuario que creó |
| `category_id` | UUID FK | ✅ | Tipo de problema |
| `owner_agent_id` | UUID FK | ✅ | Agente asignado |
| `title` | VARCHAR | ✅ | Título del ticket |
| `description` | TEXT | ✅ | Descripción detallada |
| `status` | ENUM | ✅ | open/pending/resolved/closed |
| `priority` | ENUM | ❌ | **PROPUESTO** |
| `area_id` | UUID FK | ❌ | **PROPUESTO** |
| `first_response_at` | TIMESTAMPTZ | ✅ | Métrica SLA |
| `resolved_at` | TIMESTAMPTZ | ✅ | Cuándo se resolvió |

---

## 🎯 Atributo: Prioridad

### Descripción

Campo ENUM que indica la **urgencia/importancia relativa** del ticket.

### Valores Recomendados

```php
enum TicketPriority: string {
    case LOW = 'low';           // Informativo, sin urgencia
    case MEDIUM = 'medium';     // Normal, responder en horario
    case HIGH = 'high';         // Urgente, requiere atención rápida
    case CRITICAL = 'critical'; // Emergencia, peligro de downtime
}
```

### Uso Esperado

```
CRITICAL: "El sistema está down"
          → SLA: Respuesta en 30 min

HIGH:     "Algunos usuarios no pueden crear órdenes"
          → SLA: Respuesta en 1-2 horas

MEDIUM:   "Reporte de facturas tarda mucho"
          → SLA: Respuesta en 4-8 horas

LOW:      "¿Pueden cambiar el color del botón?"
          → SLA: Respuesta en 24 horas
```

### Implementación

#### Paso 1: Crear ENUM PHP
**Archivo:** `app/Features/TicketManagement/Enums/TicketPriority.php`
```php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Enums;

enum TicketPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isCritical(): bool
    {
        return $this === self::CRITICAL;
    }

    public function isHigh(): bool
    {
        return $this === self::HIGH;
    }
}
```

#### Paso 2: Crear Migration
```sql
-- Migration file
CREATE TYPE ticketing.ticket_priority AS ENUM (
    'low', 'medium', 'high', 'critical'
);

ALTER TABLE ticketing.tickets
ADD COLUMN priority ticketing.ticket_priority DEFAULT 'medium';

CREATE INDEX idx_tickets_priority ON ticketing.tickets(priority)
WHERE priority IN ('high', 'critical');
```

#### Paso 3: Actualizar Model
**Archivo:** `app/Features/TicketManagement/Models/Ticket.php`
```php
protected $fillable = [
    // ... campos existentes ...
    'priority',  // NUEVO
];

protected $casts = [
    // ... casts existentes ...
    'priority' => TicketPriority::class,  // NUEVO
];
```

#### Paso 4: Validación en Request
```php
// StoreTicketRequest & UpdateTicketRequest
'priority' => 'sometimes|required|in:low,medium,high,critical',
```

#### Paso 5: Transformación en Resources
```php
// TicketResource
'priority' => $this->priority->value,  // Convierte enum a string
```

#### Paso 6: Tests
```php
// Test que priority se guarda correctamente
$ticket = Ticket::factory()->create(['priority' => 'critical']);
$this->assertEquals('critical', $ticket->priority->value);
```

### Complejidad

| Métrica | Valor |
|---------|-------|
| Líneas de código | 150-200 |
| Archivos modificados | 7-8 |
| Tiempo estimado | 15-30 minutos |
| Impacto en código existente | Mínimo |
| Riesgo | Muy bajo |

---

## 🏢 Atributos: Área y Departamento

### Pregunta Clave: ¿Son lo MISMO o DIFERENTES?

#### Opción A: Lo MISMO (Recomendado)
- **Un concepto:** "Área"
- **Sinónimos:** Área = Departamento = Equipo = Sección
- **Complejidad:** Media
- **Recomendación:** ✅ Usar esta

#### Opción B: DIFERENTES (Jerarquía)
- **Dos conceptos:** Departamento (nivel superior) + Área (subdivisión)
- **Ejemplo:** Departamento IT → Áreas: Backend, Frontend, DevOps
- **Complejidad:** Alta
- **Recomendación:** ❌ No necesario al inicio

**RECOMENDACIÓN:** Implementar **Opción A** (Área como concepto único)

---

## 🔄 Comparación: Área vs Categoría

### ¿Qué representa CATEGORÍA?

**Refleja:** El TIPO DE PROBLEMA o TEMA

**Ejemplos:**
- "Technical Issue"
- "Invoice Problem"
- "Feature Request"
- "Password Reset"
- "Refund Request"

**Propósito:** Clasificar contenido del ticket

**¿Quién lo decide?** COMPANY_ADMIN (cada empresa crea sus propias)

**Existe?** ✅ SÍ - Ya implementado

### ¿Qué representa ÁREA?

**Refleja:** La ESTRUCTURA ORGANIZACIONAL - "quién maneja qué"

**Ejemplos:**
- "Technical Support"
- "Billing Department"
- "HR Support"
- "Sales Support"
- "Customer Service"

**Propósito:** Agrupar agentes por función para routing inteligente

**¿Quién lo decide?** COMPANY_ADMIN (cada empresa define su estructura)

**¿Existe?** ❌ NO - Necesita implementación

### Tabla Comparativa

| Aspecto | Categoría | Área |
|---------|-----------|------|
| **Refleja** | Tipo de problema | Estructura organizacional |
| **Pregunta** | ¿QUÉ es el problema? | ¿QUIÉN lo maneja? |
| **Quién lo usa** | Sistema (clasificación) | Agentes (routing) |
| **Relación** | 1 a muchos con tickets | Muchos a muchos con agentes |
| **Ejemplo** | "Invoice Issue" | "Billing Dept" |
| **Obligatorio** | ✅ Sí (actualmente) | ❌ No (propuesta: opcional) |
| **¿Existe?** | ✅ Sí | ❌ No |

### Relación entre Categoría y Área

```
Categoría NO depende de Área
Área NO depende de Categoría
SON INDEPENDIENTES

Pero pueden usarse JUNTAS para routing:

Ticket creado:
├─ Categoría: "Technical Issue"        ← QUÉ es
├─ Área: "Technical Support"           ← QUIÉN lo maneja
└─ Owner: John (agente en esa área)    ← El agente específico

Ejemplo 2 - Ticket sin área (empresa pequeña):
├─ Categoría: "Billing Question"       ← QUÉ es
├─ Área: null                          ← No usa áreas
└─ Owner: Lisa (asignado directamente) ← Cualquier agente
```

---

## ✅ Recomendación Final

### Para Prioridad
✅ **IMPLEMENTAR INMEDIATAMENTE**
- Baja complejidad
- Sin impacto en código existente
- Alto valor: mejor gestión de SLA y urgencia
- Patrón establecido en el código

### Para Área
✅ **IMPLEMENTAR DESPUÉS (pero pronto)**
- Media complejidad
- Extiende capacidades del sistema
- Útil cuando empresa crece
- Arquitectura preparada para ello

### ¿Departamento adicional?
❌ **NO AL INICIO**
- Agregar complejidad sin valor inmediato
- Empezar con Área simple
- Si se necesita jerarquía, agregar después

---

## 🏗️ Arquitectura Propuesta

### Estructura de Base de Datos

#### Nuevas Tablas

```sql
-- 1. Tabla de Áreas (similar a categorías)
CREATE TABLE ticketing.areas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_id UUID NOT NULL REFERENCES business.companies(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_company_area_name UNIQUE (company_id, name)
);

CREATE INDEX idx_areas_company_id ON ticketing.areas(company_id);
CREATE INDEX idx_areas_is_active ON ticketing.areas(is_active) WHERE is_active = true;

-- 2. Tabla junction: Agentes en Áreas (muchos a muchos)
CREATE TABLE ticketing.agent_areas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    area_id UUID NOT NULL REFERENCES ticketing.areas(id) ON DELETE CASCADE,
    is_active BOOLEAN DEFAULT TRUE,
    assigned_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_agent_area UNIQUE (user_id, area_id)
);

CREATE INDEX idx_agent_areas_user_id ON ticketing.agent_areas(user_id);
CREATE INDEX idx_agent_areas_area_id ON ticketing.agent_areas(area_id);

-- 3. Modificación a tabla de tickets
ALTER TABLE ticketing.tickets
ADD COLUMN area_id UUID REFERENCES ticketing.areas(id) ON DELETE SET NULL;

CREATE INDEX idx_tickets_area_id ON ticketing.tickets(area_id);
```

#### Jerarquía de Datos

```
business.companies
├── id
├── name
└── [NEW] areas → ticketing.areas

ticketing.areas
├── id
├── company_id
├── name
└── [PIVOT] agent_areas → ticketing.agent_areas

ticketing.agent_areas
├── user_id
├── area_id
└── is_active

auth.users
├── id
└── [PIVOT] agent_areas → ticketing.agent_areas

ticketing.tickets
├── id
├── company_id
├── category_id      ← QUÉ es el problema
├── area_id          ← QUIÉN lo maneja [NUEVO]
├── owner_agent_id   ← Agente específico
└── priority         ← Urgencia [NUEVO]
```

### Modelos PHP Propuestos

#### `app/Features/TicketManagement/Models/Area.php`
```php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;

class Area extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $table = 'ticketing.areas';
    protected $fillable = ['company_id', 'name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ticketing.agent_areas')
            ->wherePivot('is_active', true);
    }

    public function activeAgents(): BelongsToMany
    {
        return $this->agents()->wherePivot('is_active', true);
    }
}
```

#### `app/Features/TicketManagement/Models/AgentArea.php`
```php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AgentArea extends Pivot
{
    protected $table = 'ticketing.agent_areas';
    public $timestamps = false;
    protected $casts = ['is_active' => 'boolean'];
}
```

#### Actualización `app/Features/TicketManagement/Models/Ticket.php`
```php
// Agregar relación
public function area(): BelongsTo
{
    return $this->belongsTo(Area::class);
}

// Actualizar fillable
protected $fillable = [
    // ... campos existentes ...
    'area_id',     // NUEVO
    'priority',    // NUEVO
];

// Actualizar casts
protected $casts = [
    // ... casts existentes ...
    'area_id' => 'uuid',              // NUEVO
    'priority' => TicketPriority::class, // NUEVO
];
```

#### Actualización `app/Features/UserManagement/Models/User.php`
```php
// Agregar relación
public function areas(): BelongsToMany
{
    return $this->belongsToMany(
        Area::class,
        'ticketing.agent_areas',
        'user_id',
        'area_id'
    )->wherePivot('is_active', true);
}
```

---

## 📋 Plan de Implementación

### Fase 1: Prioridad (Rápido - 1ª opción)

**Duración:** 30 minutos
**Dependencias:** Ninguna

1. ✅ Crear `Enums/TicketPriority.php`
2. ✅ Crear migration con ENUM PostgreSQL
3. ✅ Actualizar `Ticket.php` (fillable + casts)
4. ✅ Actualizar `TicketFactory.php`
5. ✅ Actualizar `StoreTicketRequest` y `UpdateTicketRequest`
6. ✅ Actualizar `TicketResource` y `TicketListResource`
7. ✅ Crear tests
8. ✅ Clear route cache

**Archivos:**
- 1 nuevo enum
- 1 nueva migration
- 4 archivos a actualizar
- 1 archivo de tests

---

### Fase 2: Área (Moderado - 2ª opción)

**Duración:** 4-6 horas
**Dependencias:** Fase 1 (opcional pero recomendado)

#### 2.1: Base de Datos (30 minutos)
1. ✅ Crear 3 migrations:
   - ENUM `ticketing.ticket_priority` (si no está en Fase 1)
   - Tabla `ticketing.areas`
   - Tabla `ticketing.agent_areas`
   - Columna `area_id` en tickets

#### 2.2: Modelos (45 minutos)
2. ✅ Crear `Models/Area.php`
3. ✅ Crear `Models/AgentArea.php`
4. ✅ Actualizar `Models/Ticket.php`
5. ✅ Actualizar `Models/User.php`
6. ✅ Actualizar `Models/Company.php`

#### 2.3: Servicios (1 hora)
7. ✅ Crear `Services/AreaService.php`:
   - `create()`, `update()`, `delete()`
   - `addAgentToArea()`, `removeAgentFromArea()`
   - `getActiveAgentsInArea()`
   - `suggestAreaByCategory()` (mapping)

#### 2.4: Controllers (1 hora)
8. ✅ Crear `Http/Controllers/AreaController.php`:
   - `index()`, `store()`, `show()`, `update()`, `destroy()`
   - `agents()` - listar agentes en área
   - `assignAgent()`, `removeAgent()` - gestión de agentes

#### 2.5: Validation (30 minutos)
9. ✅ Crear `Http/Requests/StoreAreaRequest.php`
10. ✅ Crear `Http/Requests/UpdateAreaRequest.php`
11. ✅ Actualizar `StoreTicketRequest` para validar área

#### 2.6: Authorization (30 minutos)
12. ✅ Crear `Policies/AreaPolicy.php`:
   - `create()` - COMPANY_ADMIN
   - `update()` - COMPANY_ADMIN
   - `delete()` - COMPANY_ADMIN
   - `view()` - AGENT de la empresa

#### 2.7: Resources (30 minutos)
13. ✅ Crear `Http/Resources/AreaResource.php`
14. ✅ Crear `Http/Resources/AreaWithAgentsResource.php`
15. ✅ Actualizar `TicketResource` para incluir área

#### 2.8: Routes (15 minutos)
16. ✅ Agregar rutas en `routes/api.php`:
   ```php
   Route::apiResource('areas', AreaController::class)
       ->middleware(['auth:api', 'company.context']);
   Route::post('/areas/{area}/agents', [AreaController::class, 'assignAgent']);
   Route::delete('/areas/{area}/agents/{agent}', [AreaController::class, 'removeAgent']);
   ```

#### 2.9: Business Logic (1-1.5 horas)
17. ✅ Actualizar `TicketService`:
    - `create()` - auto-asignar área según categoría
    - `suggestAreaForTicket()` - lógica de mapeo
    - `assignToAreaQueue()` - asignar a agente disponible en área

#### 2.10: Tests (1-1.5 horas)
18. ✅ Crear `tests/Feature/TicketManagement/AreaManagementTest.php`
19. ✅ Crear `tests/Unit/TicketManagement/Services/AreaServiceTest.php`
20. ✅ Tests para: CRUD, agent assignment, ticket auto-assignment

#### 2.11: Cleanup
21. ✅ `docker compose exec app php artisan route:clear`
22. ✅ `docker compose exec app php artisan test`
23. ✅ `docker compose exec app ./vendor/bin/pint`

**Archivos:**
- 2 nuevos modelos
- 3 nuevas migrations
- 1 nuevo servicio
- 1 nuevo controller
- 2 nuevos requests
- 1 nueva policy
- 2 nuevos resources
- 2 nuevos test files
- 5-6 archivos a actualizar

---

## 📊 Ejemplos Visuales

### Ejemplo 1: Empresa SIN Áreas (Startup pequeña)

```
ESTRUCTURA:
┌─────────────────────────────┐
│ Acme Startup (3 agentes)    │
├─────────────────────────────┤
│ ├─ John - Multifuncional    │
│ ├─ Sarah - Multifuncional   │
│ └─ Mike - Multifuncional    │
│                             │
│ ❌ Sin Áreas                 │
│ ✅ Con Categorías            │
└─────────────────────────────┘

CREAR TICKET:
┌──────────────────────────────┐
│ Crear Nuevo Ticket           │
├──────────────────────────────┤
│ Título: [Mi factura no llega]│
│ Descripción: [...]           │
│ Categoría: [▼ Billing Issue ]│
│                              │
│ ⚠️ Área: NO VISIBLE          │
│    (empresa no usa áreas)    │
│                              │
│ [Crear Ticket]               │
└──────────────────────────────┘

BASE DE DATOS:
{
  ticket_id: uuid-123,
  category_id: uuid-456,
  area_id: null,                    ← Vacío
  owner_agent_id: null,             ← Sin asignar
  priority: "medium"                ← Prioridad normal
}

CUANDO RESPONDE UN AGENTE:
├─ owner_agent_id = John (cualquiera disponible)
└─ status = pending
```

---

### Ejemplo 2: Empresa CON Áreas (Mediana)

```
ESTRUCTURA:
┌───────────────────────────────┐
│ Acme Corp (20 agentes)        │
├───────────────────────────────┤
│ ├─ Area: Technical Support    │
│ │  ├─ John (especialista)     │
│ │  ├─ Sarah (senior)          │
│ │  └─ Mike (junior)           │
│ │                             │
│ ├─ Area: Billing              │
│ │  ├─ Lisa (jefe)             │
│ │  └─ Carlos (analista)       │
│ │                             │
│ └─ Area: HR Support           │
│    └─ Emma (única)            │
│                             │
│ ✅ Con Áreas                  │
│ ✅ Con Categorías              │
└───────────────────────────────┘

CREAR TICKET:
┌──────────────────────────────────┐
│ Crear Nuevo Ticket               │
├──────────────────────────────────┤
│ Título: [Mi factura no llega____]│
│ Descripción: [...]               │
│ Categoría: [▼ Billing Issue    ] │
│ Priority: [▼ Medium            ] │
│                                  │
│ Área: [▼ Billing  ✓ AUTO]       │
│      (▼ Usuario PUEDE cambiar)   │
│       ├─ Technical Support       │
│       ├─ Billing (✓ sugerida)   │
│       └─ HR Support              │
│                                  │
│ [Crear Ticket]                   │
└──────────────────────────────────┘

CUANDO SE CREA:
├─ category_id: uuid-billing-issue
├─ area_id: uuid-billing (AUTO-ASIGNADO por categoría)
├─ owner_agent_id: null (sin asignar aún)
├─ priority: "medium"
└─ status: "open"

CUANDO RESPONDE AGENTE:
├─ Opción A: Lisa (está en Billing area)
│  └─ owner_agent_id = Lisa
│
├─ Opción B: Carlos (está en Billing area)
│  └─ owner_agent_id = Carlos
│
└─ status = "pending"

TICKET RESULTANTE:
┌──────────────────────────────────┐
│ Ticket TKT-2025-00042            │
├──────────────────────────────────┤
│ Creado: John Smith (Customer)    │
│                                  │
│ Categoría: Billing Issue         │
│ Área: Billing ← VISIBLE          │
│ Agente: Lisa                      │
│ Priority: Medium                 │
│ Status: PENDING                  │
│                                  │
│ [Conversación...] [Archivos...]  │
└──────────────────────────────────┘
```

---

### Ejemplo 3: Flujo Completo de Auto-Asignación

```
CONFIGURACIÓN ADMIN:
┌─────────────────────────────────────┐
│ Mapeo: Categoría → Área Default     │
├─────────────────────────────────────┤
│ Categoría              → Área        │
│ Technical Issue        → Tech Supp   │
│ Invoice Problem        → Billing     │
│ Account Setup          → (ninguna)   │
│ Password Reset         → Tech Supp   │
│ Refund Request         → Billing     │
│ Feature Request        → (ninguna)   │
│ Complaint              → (ninguna)   │
└─────────────────────────────────────┘

FLUJO DE USUARIO:
┌────────────────────────────────────────────┐
│ 1. Usuario crea ticket                     │
│    "Mi factura no llega"                   │
│    Category: Billing Issue                 │
│    ↓                                       │
│ 2. Sistema detecta auto-asignación         │
│    Busca en mapeo:                         │
│    Billing Issue → Billing area            │
│    ↓                                       │
│ 3. Usuario ve dropdown pre-seleccionado    │
│    Área: [▼ Billing ✓]                     │
│    (puede cambiar si quiere)               │
│    ↓                                       │
│ 4. Usuario presiona "Crear"                │
│    ↓                                       │
│ 5. Ticket se crea con:                     │
│    - category_id: billing-issue            │
│    - area_id: billing                      │
│    - owner_agent_id: null (sin asignar)    │
│    ↓                                       │
│ 6. Primer agente de Billing que responde:  │
│    Lisa o Carlos                           │
│    ↓                                       │
│ 7. Sistema asigna automáticamente:         │
│    owner_agent_id: Lisa                    │
│    status: pending                         │
│    ↓                                       │
│ 8. Usuario ve que Lisa del área Billing    │
│    está resolviendo su ticket              │
│                                            │
└────────────────────────────────────────────┘
```

---

### Ejemplo 4: Gestión de Áreas (Admin)

```
VISTA DE ADMINISTRADOR:

┌──────────────────────────────────────┐
│ [Gestión de Áreas - Acme Corp]       │
├──────────────────────────────────────┤
│                                      │
│ ┌──────────────────────────────────┐ │
│ │ ✓ Technical Support              │ │
│ │  Descripción: Soporte técnico    │ │
│ │  Agentes: 3 (John, Sarah, Mike)  │ │
│ │  Tickets abiertos: 12            │ │
│ │  [Editar] [Agentes] [Eliminar]   │ │
│ └──────────────────────────────────┘ │
│                                      │
│ ┌──────────────────────────────────┐ │
│ │ ✓ Billing                        │ │
│ │  Descripción: Facturación        │ │
│ │  Agentes: 2 (Lisa, Carlos)       │ │
│ │  Tickets abiertos: 5             │ │
│ │  [Editar] [Agentes] [Eliminar]   │ │
│ └──────────────────────────────────┘ │
│                                      │
│ ┌──────────────────────────────────┐ │
│ │ ✓ HR Support                     │ │
│ │  Descripción: Recursos humanos   │ │
│ │  Agentes: 1 (Emma)               │ │
│ │  Tickets abiertos: 2             │ │
│ │  [Editar] [Agentes] [Eliminar]   │ │
│ └──────────────────────────────────┘ │
│                                      │
│  [+ Crear Nueva Área]                │
│                                      │
└──────────────────────────────────────┘

MODAL: AGREGAR AGENTE A ÁREA

┌──────────────────────────────────────┐
│ Agregar Agente a Billing             │
├──────────────────────────────────────┤
│                                      │
│ Seleccionar Agente:                  │
│ [▼ Lisa (Lisa Garcia)_____________]  │
│   ├─ Lisa Garcia (AGENT)             │
│   ├─ Carlos Mendez (AGENT)           │
│   ├─ Sofia Lopez (AGENT)             │
│   └─ ...                             │
│                                      │
│ [Agregar]  [Cancelar]                │
│                                      │
└──────────────────────────────────────┘
```

---

## 🔌 Endpoints API Propuestos

### Prioridad (Automático en Tickets)
```
POST   /api/tickets
PATCH  /api/tickets/{ticket}

Body:
{
  "title": "...",
  "description": "...",
  "category_id": "uuid",
  "priority": "high"  ← NUEVO
}

Response:
{
  "id": "uuid",
  "priority": "high",  ← NUEVO
  "category_id": "uuid",
  ...
}
```

### Áreas (Endpoints Específicos)
```
GET    /api/areas                    - Listar áreas de la empresa
POST   /api/areas                    - Crear área
GET    /api/areas/{area}             - Ver detalles de área
PATCH  /api/areas/{area}             - Actualizar área
DELETE /api/areas/{area}             - Eliminar área

GET    /api/areas/{area}/agents      - Listar agentes en área
POST   /api/areas/{area}/agents      - Agregar agente a área
DELETE /api/areas/{area}/agents/{id} - Remover agente de área
```

### Recordatorios (Endpoint Específico)
```
POST   /api/tickets/{ticket}/remind  - Enviar recordatorio al usuario
Content-Type: application/json

Body:
{
  "message": "Recordatorio amigable",  ← Opcional
}

Response:
{
  "success": true,
  "message": "Email de recordatorio enviado a usuario",
  "reminder_sent_at": "2025-11-26T15:30:45Z"
}
```

---

## 📈 Impacto y Beneficios

### Con Prioridad

```
Antes:
├─ Tickets sin niveles de urgencia
├─ Agentes responden en orden cronológico
└─ Downtime crítico mismo SLA que consulta

Después:
├─ CRITICAL: Respuesta 30 min
├─ HIGH: Respuesta 1-2 horas
├─ MEDIUM: Respuesta 4-8 horas
└─ LOW: Respuesta 24 horas
```

### Con Áreas

```
Antes:
├─ Agentes asignados directamente a tickets
├─ Admin manual para cada ticket
├─ Difícil de escalar
└─ Sin especialización

Después:
├─ Agentes especializados por área
├─ Auto-asignación por categoría/área
├─ Fácil de escalar (agregar agentes a áreas)
├─ Mejor experiencia del cliente
└─ Métricas por área
```

---

## ⚠️ Consideraciones Importantes

### Opcional vs Obligatorio

**Recomendación:**
- **Prioridad:** DEFAULT = `medium` (opcional en create, pero siempre existe)
- **Área:** OPCIONAL = null permitido (para empresas sin esta estructura)

### Backward Compatibility

```
Tickets existentes:
├─ priority: null → migración asigna 'medium'
└─ area_id: null → sin cambio (opcional)

Empresas sin áreas:
├─ Pueden seguir usando el sistema
└─ Tickets se asignan directamente a agentes
```

### Seguridad

```
Validaciones necesarias:
├─ Agente debe estar en la empresa del ticket
├─ Área debe existir en la empresa del ticket
├─ COMPANY_ADMIN solo puede ver/editar sus áreas
├─ AGENT solo puede ver áreas donde está asignado
└─ User no puede crear áreas (solo COMPANY_ADMIN)
```

---

## ⏰ Auto-Escalada de Prioridad (24h sin respuesta)

### Descripción

Cuando un ticket **OPEN** (sin respuesta de agente) lleva **24 horas sin recibir atención**, el sistema automáticamente cambia la prioridad a **HIGH** para asegurar que se le dé atención más urgente.

### Escenarios de Uso

```
SCENARIO 1: Ticket urgente olvidado
├─ Ticket creado: Lunes 10:00 AM (Priority: LOW)
├─ Pasan 24 horas: Martes 10:00 AM
├─ Sistema detecta: Sin respuesta de agente
├─ Sistema actualiza: Priority = HIGH
└─ Efecto: Aparece en vista "Urgentes" de agentes

SCENARIO 2: Ticket con respuesta rápida
├─ Ticket creado: Lunes 10:00 AM (Priority: MEDIUM)
├─ Agente responde: Lunes 2:00 PM (8 horas después)
├─ Status cambia: OPEN → PENDING
├─ Resultado: NO se escalada (solo afecta OPEN)
└─ Efecto: Sistema detiene el contador

SCENARIO 3: Escalada múltiple
├─ Ticket CRITICAL: Creado hace 12h, sin respuesta
├─ Sistema: NO CAMBIA (ya es CRITICAL)
├─ Resultado: Se respeta el nivel máximo
└─ Efecto: Solo escala si priority < HIGH
```

### Implementación

#### Paso 1: Crear Migration (tabla auxiliar opcional)

```sql
-- Tabla para registrar escaladas (auditoría)
CREATE TABLE ticketing.ticket_escalations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
    old_priority ticketing.ticket_priority NOT NULL,
    new_priority ticketing.ticket_priority NOT NULL,
    reason VARCHAR(255),
    escalated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_escalation_per_ticket UNIQUE (ticket_id, escalated_at)
);

CREATE INDEX idx_escalations_ticket_id ON ticketing.ticket_escalations(ticket_id);
CREATE INDEX idx_escalations_escalated_at ON ticketing.ticket_escalations(escalated_at DESC);
```

#### Paso 2: Crear Enum para Eventos

```php
// app/Features/TicketManagement/Enums/TicketEscalationReason.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Enums;

enum TicketEscalationReason: string
{
    case INACTIVITY_24H = 'inactivity_24h';      // Sin respuesta en 24h
    case MANUAL_ESCALATION = 'manual_escalation'; // Agente escaló manualmente
    case USER_REQUEST = 'user_request';           // Usuario lo pidió
}
```

#### Paso 3: Crear Job Scheduler

```php
// app/Features/TicketManagement/Jobs/EscalateUnattendedTicketsJob.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Enums\TicketPriority;
use App\Features\TicketManagement\Enums\TicketEscalationReason;
use Carbon\Carbon;

class EscalateUnattendedTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Encuentra tickets OPEN hace más de 24h sin respuesta de agente
        $cutoffTime = Carbon::now()->subHours(24);

        $tickets = Ticket::query()
            ->where('status', TicketStatus::OPEN->value)
            ->where('created_at', '<=', $cutoffTime)
            ->where('first_response_at', null) // Sin respuesta de agente aún
            ->whereIn('priority', [
                TicketPriority::LOW->value,
                TicketPriority::MEDIUM->value,
                // No escalamos CRITICAL ni HIGH
            ])
            ->get();

        foreach ($tickets as $ticket) {
            $oldPriority = $ticket->priority;

            // Actualizar prioridad
            $ticket->update([
                'priority' => TicketPriority::HIGH,
            ]);

            // Registrar la escalada (auditoría)
            $ticket->escalations()->create([
                'old_priority' => $oldPriority->value,
                'new_priority' => TicketPriority::HIGH->value,
                'reason' => TicketEscalationReason::INACTIVITY_24H->value,
            ]);

            // Disparar evento
            event(new TicketPriorityEscalated($ticket, $oldPriority));
        }
    }
}
```

#### Paso 4: Registrar en Scheduler

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Ejecutar cada hora para verificar escaladas
    $schedule->job(new EscalateUnattendedTicketsJob)
        ->hourly()
        ->name('escalate-unattended-tickets');
}
```

#### Paso 5: Evento de Escalada

```php
// app/Features/TicketManagement/Events/TicketPriorityEscalated.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Events;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketPriority;
use Illuminate\Foundation\Events\Dispatchable;

class TicketPriorityEscalated
{
    use Dispatchable;

    public function __construct(
        public Ticket $ticket,
        public TicketPriority $oldPriority,
    ) {}
}
```

#### Paso 6: Listener para Notificación

```php
// app/Features/TicketManagement/Listeners/NotifyAgentOnEscalation.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Listeners;

use App\Features\TicketManagement\Events\TicketPriorityEscalated;
use App\Features\TicketManagement\Notifications\TicketEscalatedNotification;

class NotifyAgentOnEscalation
{
    public function handle(TicketPriorityEscalated $event): void
    {
        // Notificar a agentes del área si existe
        if ($event->ticket->area_id) {
            $agents = $event->ticket->area->agents()
                ->where('is_active', true)
                ->get();

            foreach ($agents as $agent) {
                $agent->notify(
                    new TicketEscalatedNotification($event->ticket)
                );
            }
        }
    }
}
```

#### Paso 7: Actualizar Modelo

```php
// app/Features/TicketManagement/Models/Ticket.php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function escalations(): HasMany
{
    return $this->hasMany(TicketEscalation::class);
}
```

#### Paso 8: Model para Escalaciones

```php
// app/Features/TicketManagement/Models/TicketEscalation.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEscalation extends Model
{
    use HasUuids;

    protected $table = 'ticketing.ticket_escalations';
    public $timestamps = false;
    protected $fillable = ['ticket_id', 'old_priority', 'new_priority', 'reason'];
    protected $casts = [
        'escalated_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
```

### Complejidad

| Métrica | Valor |
|---------|-------|
| Líneas de código | 200-300 |
| Archivos nuevos | 5-6 |
| Archivos modificados | 2-3 |
| Tiempo estimado | 30-45 minutos |
| Impacto en código existente | Mínimo |
| Riesgo | Muy bajo |

---

## 📧 Sistema de Recordatorios para Usuarios

### Descripción

Endpoint que permite a agentes enviar un **email de recordatorio** a usuarios que no han respondido por mucho tiempo. Útil para:
- Mantener tickets activos
- Re-enganche de usuarios pasivos
- Seguimiento de problemas pendientes

### Escenarios de Uso

```
SCENARIO 1: Usuario sin respuesta
├─ Agente espera respuesta del usuario
├─ Pasan 48+ horas sin interacción
├─ Agente presiona botón: "Enviar Recordatorio"
├─ Usuario recibe email: "¿Necesitas más ayuda con tu problema?"
└─ Usuario puede responder o indicar si está resuelto

SCENARIO 2: Consulta de seguimiento
├─ Implementación de solución propuesta
├─ Agente verifica si funcionó
├─ Presiona: "Enviar seguimiento"
├─ Email: "¿La solución que propusimos funcionó?"
└─ Usuario responde sí/no

SCENARIO 3: Ticket olvidado (status pending)
├─ Ticket PENDING hace 5 días
├─ Usuario no ha respondido desde hace 3 días
├─ Agente: "Enviar recordatorio"
├─ Email: "Notamos inactividad, ¿podemos ayudarte?"
└─ Resultado: Re-activar o cerrar ticket
```

### Implementación

#### Paso 1: Crear Migration (tabla de recordatorios)

```sql
CREATE TABLE ticketing.ticket_reminders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,
    sent_by_user_id UUID NOT NULL REFERENCES auth.users(id),
    message TEXT,
    sent_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT one_reminder_per_ticket_per_hour
        CHECK (1=1) -- Validación en aplicación
);

CREATE INDEX idx_reminders_ticket_id ON ticketing.ticket_reminders(ticket_id);
CREATE INDEX idx_reminders_sent_at ON ticketing.ticket_reminders(sent_at DESC);
CREATE INDEX idx_reminders_sent_by ON ticketing.ticket_reminders(sent_by_user_id);
```

#### Paso 2: Crear Model

```php
// app/Features/TicketManagement/Models/TicketReminder.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReminder extends Model
{
    use HasUuids;

    protected $table = 'ticketing.ticket_reminders';
    public $timestamps = false;
    protected $fillable = ['ticket_id', 'sent_by_user_id', 'message'];
    protected $casts = ['sent_at' => 'datetime'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(\App\Features\UserManagement\Models\User::class, 'sent_by_user_id');
    }
}
```

#### Paso 3: Crear Request de Validación

```php
// app/Features/TicketManagement/Http/Requests/SendReminderRequest.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Validar que es agente o admin de la empresa
        return $this->user()->isAgent() || $this->user()->isCompanyAdmin();
    }

    public function rules(): array
    {
        return [
            'message' => 'nullable|string|max:500|min:10',
        ];
    }

    public function messages(): array
    {
        return [
            'message.max' => 'El mensaje no puede exceder 500 caracteres',
            'message.min' => 'El mensaje debe tener mínimo 10 caracteres',
        ];
    }
}
```

#### Paso 4: Crear Service

```php
// app/Features/TicketManagement/Services/ReminderService.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketReminder;
use App\Features\UserManagement\Models\User;
use App\Features\TicketManagement\Mail\TicketReminderMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class ReminderService
{
    /**
     * Enviar recordatorio al usuario del ticket
     *
     * @throws \Exception si se intenta enviar recordatorios muy frecuentes
     */
    public function sendReminder(Ticket $ticket, User $agentUser, ?string $message = null): TicketReminder
    {
        // Validación 1: Ticket debe estar en estado que permita recordatorios
        if (!$this->canSendReminder($ticket)) {
            throw new \Exception(
                "No se puede enviar recordatorio a tickets en estado {$ticket->status->value}"
            );
        }

        // Validación 2: Prevent spam - máximo 1 recordatorio por hora
        $cacheKey = "ticket_reminder_cooldown_{$ticket->id}";
        if (Cache::has($cacheKey)) {
            throw new \Exception(
                'Se envió un recordatorio hace poco. Espera antes de enviar otro.'
            );
        }

        // Validación 3: Usuario debe ser propietario del ticket
        $recipientUser = $ticket->creator;
        if (!$recipientUser) {
            throw new \Exception('Ticket no tiene usuario propietario');
        }

        // Mensaje por defecto si no se proporciona
        $finalMessage = $message ?? $this->getDefaultMessage($ticket);

        // Crear registro de recordatorio
        $reminder = $ticket->reminders()->create([
            'sent_by_user_id' => $agentUser->id,
            'message' => $finalMessage,
        ]);

        // Enviar email
        Mail::to($recipientUser->email)->send(
            new TicketReminderMail($ticket, $agentUser, $finalMessage)
        );

        // Cachear para evitar spam (60 minutos)
        Cache::put($cacheKey, true, now()->addMinutes(60));

        // Disparar evento
        event(new \App\Features\TicketManagement\Events\ReminderSent($ticket, $reminder));

        return $reminder;
    }

    /**
     * Verificar si se puede enviar recordatorio al ticket
     */
    private function canSendReminder(Ticket $ticket): bool
    {
        // Solo tickets OPEN y PENDING pueden recibir recordatorios
        $allowedStatuses = [
            \App\Features\TicketManagement\Enums\TicketStatus::OPEN->value,
            \App\Features\TicketManagement\Enums\TicketStatus::PENDING->value,
        ];

        return in_array($ticket->status->value, $allowedStatuses);
    }

    /**
     * Generar mensaje por defecto
     */
    private function getDefaultMessage(Ticket $ticket): string
    {
        return "Hola,\n\nSeguimos trabajando en tu ticket #{$ticket->ticket_code}. "
            . "¿Hay algo más que podamos ayudarte o necesitas más información?";
    }

    /**
     * Obtener historial de recordatorios de un ticket
     */
    public function getReminderHistory(Ticket $ticket)
    {
        return $ticket->reminders()
            ->with('sentBy:id,name,email')
            ->orderByDesc('sent_at')
            ->paginate(10);
    }
}
```

#### Paso 5: Crear Controller

```php
// app/Features/TicketManagement/Http/Controllers/TicketReminderController.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Http\Requests\SendReminderRequest;
use App\Features\TicketManagement\Http\Resources\TicketReminderResource;
use App\Features\TicketManagement\Services\ReminderService;
use Illuminate\Http\JsonResponse;

class TicketReminderController
{
    public function __construct(
        private readonly ReminderService $reminderService,
    ) {}

    /**
     * POST /api/tickets/{ticket}/remind
     */
    public function send(SendReminderRequest $request, Ticket $ticket): JsonResponse
    {
        // Autorizar agente
        $this->authorize('sendReminder', $ticket);

        try {
            $reminder = $this->reminderService->sendReminder(
                ticket: $ticket,
                agentUser: $request->user(),
                message: $request->get('message'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Recordatorio enviado exitosamente',
                'reminder' => new TicketReminderResource($reminder),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar recordatorio',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/tickets/{ticket}/reminders
     */
    public function history(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $reminders = $this->reminderService->getReminderHistory($ticket);

        return response()->json([
            'success' => true,
            'data' => TicketReminderResource::collection($reminders->items()),
            'meta' => [
                'current_page' => $reminders->currentPage(),
                'total' => $reminders->total(),
                'per_page' => $reminders->perPage(),
            ],
        ]);
    }
}
```

#### Paso 6: Crear Policy

```php
// Agregar a app/Features/TicketManagement/Policies/TicketPolicy.php

public function sendReminder(User $user, Ticket $ticket): bool
{
    // Solo agentes y company_admins pueden enviar recordatorios
    if (!$user->isAgent() && !$user->isCompanyAdmin($ticket->company_id)) {
        return false;
    }

    // Debe pertenecer a la misma empresa
    return $user->hasCompanyContext($ticket->company_id);
}
```

#### Paso 7: Crear Resource

```php
// app/Features/TicketManagement/Http/Resources/TicketReminderResource.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketReminderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'ticketId' => $this->ticket_id,
            'sentByUser' => [
                'id' => $this->sentBy->id,
                'name' => $this->sentBy->name,
                'email' => $this->sentBy->email,
            ],
            'message' => $this->message,
            'sentAt' => $this->sent_at?->toIso8601String(),
        ];
    }
}
```

#### Paso 8: Crear Email

```php
// app/Features/TicketManagement/Mail/TicketReminderMail.php
<?php
declare(strict_types=1);

namespace App\Features\TicketManagement\Mail;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Envelope;

class TicketReminderMail extends Mailable
{
    public function __construct(
        public Ticket $ticket,
        public User $agent,
        public string $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recordatorio: Ticket #{$this->ticket->ticket_code}",
        );
    }

    public function content(): object
    {
        return view('app.emails.ticket-reminder', [
            'ticket' => $this->ticket,
            'agent' => $this->agent,
            'message' => $this->message,
            'ticketUrl' => route('tickets.show', $this->ticket),
        ]);
    }
}
```

#### Paso 9: Crear Blade Email

```blade
<!-- resources/views/app/emails/ticket-reminder.blade.php -->
<x-mail::message>
# Recordatorio: Ticket {{ $ticket->ticket_code }}

Hola {{ $ticket->creator->name }},

{{ $message }}

**Detalles del Ticket:**
- **Categoría:** {{ $ticket->category->name ?? 'Sin categoría' }}
- **Estado:** {{ $ticket->status->value }}
- **Agente:** {{ $agent->name }}

<x-mail::button :url="$ticketUrl">
Ver Ticket
</x-mail::button>

Si tienes preguntas, responde a este email o accede a tu panel.

Gracias,
{{ config('app.name') }} Support

</x-mail::message>
```

#### Paso 10: Registrar Rutas

```php
// routes/api.php
Route::post('/tickets/{ticket}/remind', [TicketReminderController::class, 'send'])
    ->middleware(['auth:api', 'company.context'])
    ->name('tickets.remind');

Route::get('/tickets/{ticket}/reminders', [TicketReminderController::class, 'history'])
    ->middleware(['auth:api', 'company.context'])
    ->name('tickets.reminders');
```

### Complejidad

| Métrica | Valor |
|---------|-------|
| Líneas de código | 250-350 |
| Archivos nuevos | 8-10 |
| Archivos modificados | 2-3 |
| Tiempo estimado | 45 min - 1 hora |
| Impacto en código existente | Mínimo |
| Riesgo | Muy bajo |

---

## 📝 Próximos Pasos

### Acción Inmediata

```
☐ Revisión de este documento por team lead
☐ Aprobación de las 4 características propuestas
☐ Priorizar fases de implementación
```

### Implementación Fase 1 (Rápido - Prioridad + Auto-Escalada + Recordatorios)

**Duración estimada:** 2-2.5 horas

```
Rama: feature/ticket-management-v2

PARTE A: Prioridad
☐ Crear rama: feature/ticket-priority
☐ Implementar pasos 1-8 del plan (Prioridad)
☐ Tests pasando 100%

PARTE B: Auto-Escalada
☐ Crear migration para escalaciones
☐ Crear enums y job scheduler
☐ Registrar en Console/Kernel.php
☐ Crear eventos y listeners
☐ Tests para auto-escalada

PARTE C: Recordatorios
☐ Crear migration para reminders
☐ Crear modelo y service
☐ Crear controller y rutas
☐ Crear email y blade template
☐ Tests para recordatorios

Finalmente:
☐ Code review
☐ Merge a main
```

### Implementación Fase 2 (Mediano - Áreas)

**Duración estimada:** 4-6 horas

```
Rama: feature/ticket-areas

☐ Crear rama: feature/ticket-areas
☐ Implementar pasos 1-23 del plan
☐ Tests pasando 100%
☐ Integración con Fase 1 (recordatorios notifican a área)
☐ Documentación en Admin LTE
☐ Code review
☐ Merge a main
```

### Orden Recomendado

```
1️⃣  Fase 1 PRIMERO (todas las características rápidas juntas)
    - Prioridad: campo base
    - Auto-escalada: aprovecha el campo priority
    - Recordatorios: funcionalidad independiente

    Ventaja: 2-2.5h = todo listo, tests pasan

2️⃣  Fase 2 DESPUÉS
    - Áreas: integra con todo lo anterior
    - Recordatorios notifican a agentes del área
    - Auto-escalada notifica a agentes del área

    Ventaja: Mejor contexto, menos conflictos
```

---

## 📚 Referencias

### Archivos Existentes a Consultar

- `app/Features/TicketManagement/Enums/TicketStatus.php` - Patrón de ENUM
- `app/Features/TicketManagement/Models/Ticket.php` - Modelo a extender
- `app/Features/TicketManagement/Models/Category.php` - Patrón per-company
- `app/Features/UserManagement/Models/UserRole.php` - Patrón many-to-many
- `app/Features/CompanyManagement/Models/Company.php` - Multi-tenant

### Documentación Interna

- `/CLAUDE.md` - Directrices del proyecto
- `/documentacion/ESTADO_COMPLETO_PROYECTO.md` - Estado del proyecto
- `/documentacion/GUIA_ESTRUCTURA_CARPETAS_PROYECTO.md` - Arquitectura

---

## 📊 Resumen Tabular Comparativo

| Aspecto | Prioridad | Auto-Escalada | Recordatorios | Área |
|---------|-----------|---------------|---------------|------|
| **Complejidad** | ⭐ Baja | ⭐ Baja | ⭐ Baja | ⭐⭐ Media |
| **Tiempo** | 15-30 min | 30-45 min | 45 min-1h | 4-6 h |
| **Código** | ~200 líneas | ~200-300 | ~250-350 | ~1000 líneas |
| **Archivos Nuevos** | 1-2 | 5-6 | 8-10 | 15-20 |
| **Archivos Modificados** | 7-8 | 2-3 | 2-3 | 5-6 |
| **Impacto Existente** | Mínimo | Mínimo | Mínimo | Moderado |
| **Dependencias** | Ninguna | Prioridad | Ninguna | Ninguna |
| **Riesgo** | Muy bajo | Muy bajo | Muy bajo | Bajo |
| **Valor Negocio** | Alto | Alto | Medio-Alto | Muy alto |
| **Urgencia** | Alta | Alta | Media | Media |
| **Fase** | 1️⃣ | 1️⃣ | 1️⃣ | 2️⃣ |
| **¿Implementar?** | ✅ SÍ | ✅ SÍ | ✅ SÍ | ✅ SÍ (después) |

---

## 📈 Timeline Estimado

```
Semana 1:
├─ Día 1-2: Implementar Fase 1 (2-2.5h)
│  ├─ Prioridad
│  ├─ Auto-Escalada
│  └─ Recordatorios
├─ Día 2-3: Code review + Tests
└─ Día 3: Deploy a main

Semana 2:
├─ Día 1-2: Implementar Fase 2 (4-6h)
│  └─ Áreas + Integración
├─ Día 3: Code review + Tests
└─ Día 4: Deploy a main
```

---

## ✅ Checklist Final

```
Pre-Implementación:
☐ Documento revisado por team lead
☐ Aprobación de ingeniero original
☐ Aprobación de arquitecto
☐ Documentación técnica completa ✓

Implementación:
☐ Fase 1 completa (Prioridad + Auto-Escalada + Recordatorios)
☐ Fase 2 completa (Áreas)
☐ Todos los tests pasando
☐ Code reviews completos
☐ Documentación de usuario

Deployment:
☐ Migrations ejecutadas
☐ Cache limpio
☐ QA testing
☐ Release notes
☐ Documentación de API
```

---

**Documento preparado:** Noviembre 26, 2025
**Versión:** 2.0 (Actualizado con Auto-Escalada y Recordatorios)
**Estado:** Listo para revisión y aprobación
