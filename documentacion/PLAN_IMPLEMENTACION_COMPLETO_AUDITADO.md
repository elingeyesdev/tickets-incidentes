# PLAN DE IMPLEMENTACIÓN COMPLETO - 4 FEATURES TICKETS
**Auditado y Verificado**
**Fecha:** 26 Noviembre 2025
**Estado:** ✅ LISTO PARA IMPLEMENTAR

---

## 📋 ÍNDICE

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Auditoría Completada](#auditoría-completada)
3. [Cambios en Base de Datos](#cambios-en-base-de-datos)
4. [Plan de Implementación por Fases](#plan-de-implementación-por-fases)
5. [Fase 1: Migraciones](#fase-1-migraciones)
6. [Fase 2: Enums y Modelos](#fase-2-enums-y-modelos)
7. [Fase 3: Feature Prioridad](#fase-3-feature-prioridad)
8. [Fase 4: Feature Auto-Escalada](#fase-4-feature-auto-escalada)
9. [Fase 5: Feature Recordatorios](#fase-5-feature-recordatorios)
10. [Fase 6: Feature Áreas](#fase-6-feature-áreas)
11. [Fase 7: Actualización de Tests](#fase-7-actualización-de-tests)
12. [Fase 8: Seeders](#fase-8-seeders)
13. [Comandos de Ejecución](#comandos-de-ejecución)
14. [Checklist Final](#checklist-final)

---

## 🎯 RESUMEN EJECUTIVO

### Features a Implementar

| # | Feature | Descripción | Complejidad |
|---|---------|-------------|-------------|
| 1 | **Prioridad** | ENUM (low, medium, high, critical) en tickets | Baja |
| 2 | **Auto-Escalada** | Cambio automático a HIGH después de 24h sin respuesta | Media |
| 3 | **Recordatorios** | Endpoint para enviar emails de recordatorio | Baja |
| 4 | **Áreas** | Tabla areas + relación con tickets | Media |

### Resumen de Cambios

```
Base de Datos:
- 1 ENUM nuevo: ticketing.ticket_priority
- 1 tabla nueva: ticketing.areas
- 2 columnas nuevas en tickets: priority, area_id
- 4 índices nuevos

Código Laravel:
- 1 Enum: TicketPriority
- 2 Models nuevos: Area
- 1 Event: TicketCreated (YA EXISTE ✓)
- 1 Job: EscalateTicketPriorityJob
- 1 Listener: DispatchEscalationJob
- 1 Mail: TicketReminderMail + vista blade
- 2 Controllers nuevos: AreaController, TicketReminderController
- 1 Service nuevo: AreaService
- 5 Form Requests: 2 actualizaciones + 3 nuevos
- 2 Resources: 2 actualizaciones + 1 nuevo
- 2 Policies: 1 actualización + 1 nueva
- 2 Seeders: 2 actualizaciones + 1 nuevo

Tests:
- 8 tests a actualizar
- 5 tests nuevos a crear
- 1 factory a actualizar
```

**Tiempo Estimado Total:** ~4-5 horas

---

## ✅ AUDITORÍA COMPLETADA

Se lanzaron 9 agentes especializados Haiku que auditaron exhaustivamente:

1. ✅ **Tests** - 39 archivos analizados (30 Feature + 9 Unit)
2. ✅ **Seeders** - 10 seeders encontrados (4 TicketManagement + 3 Company + 3 User)
3. ✅ **Modelos** - 7 modelos analizados (Ticket, Category, Response, etc.)
4. ✅ **Migraciones** - 12 migraciones encontradas (9 Features + 2 database + 1 duplicada)
5. ✅ **Controllers y Requests** - 5 controllers + 11 form requests
6. ✅ **Resources y Policies** - 6 resources + 5 policies
7. ✅ **Services** - 6 services analizados
8. ✅ **Rutas** - 19 rutas de tickets existentes
9. ✅ **Events/Jobs/Listeners/Mails** - 6 events + 2 jobs + 2 listeners + 1 mail

**Todos los reportes fueron verificados manualmente leyendo archivos críticos.**

---

## 🗄️ CAMBIOS EN BASE DE DATOS

### Resumen

```sql
-- ENUM nuevo
CREATE TYPE ticketing.ticket_priority AS ENUM ('low', 'medium', 'high', 'critical');

-- Tabla nueva
CREATE TABLE ticketing.areas (
  id UUID PRIMARY KEY,
  company_id UUID REFERENCES business.companies(id),
  name VARCHAR(100) NOT NULL,
  description TEXT,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMPTZ,
  updated_at TIMESTAMPTZ,
  UNIQUE(company_id, name)
);

-- Columnas nuevas en tickets
ALTER TABLE ticketing.tickets ADD COLUMN priority VARCHAR(20) DEFAULT 'medium';
ALTER TABLE ticketing.tickets ADD COLUMN area_id UUID REFERENCES ticketing.areas(id);

-- Índices
CREATE INDEX idx_tickets_priority ON ticketing.tickets(priority);
CREATE INDEX idx_areas_company_id ON ticketing.areas(company_id);
CREATE INDEX idx_areas_is_active ON ticketing.areas(is_active);
CREATE INDEX idx_tickets_area_id ON ticketing.tickets(area_id);
```

---

## 📅 PLAN DE IMPLEMENTACIÓN POR FASES

### Fase 1: Migraciones (30 min)
- Crear migration para priority
- Crear migration para areas
- Ejecutar migraciones
- Verificar schema

### Fase 2: Enums y Modelos (30 min)
- Crear TicketPriority enum
- Crear Area model
- Actualizar Ticket model

### Fase 3: Feature Prioridad (30 min)
- Actualizar StoreTicketRequest
- Actualizar UpdateTicketRequest
- Actualizar TicketResource
- Actualizar TicketListResource
- Actualizar TicketFactory

### Fase 4: Feature Auto-Escalada (45 min)
- Crear EscalateTicketPriorityJob
- Crear DispatchEscalationJob listener
- Registrar listener en ServiceProvider

### Fase 5: Feature Recordatorios (45 min)
- Crear TicketReminderController
- Crear TicketReminderMail
- Crear vista blade de email
- Actualizar TicketPolicy
- Agregar ruta

### Fase 6: Feature Áreas (60 min)
- Crear AreaService
- Crear AreaController
- Crear StoreAreaRequest
- Crear UpdateAreaRequest
- Crear AreaResource
- Crear AreaPolicy
- Agregar rutas apiResource

### Fase 7: Actualización de Tests (45 min)
- Actualizar CreateTicketTest
- Actualizar ListTicketsTest
- Actualizar GetTicketTest
- Actualizar TicketTest (Unit)
- Crear AutoEscalateTicketPriorityJobTest
- Crear TicketReminderTest

### Fase 8: Seeders (30 min)
- Actualizar PilAndinaTicketsSeeder
- Actualizar YPFBTicketsSeeder
- Crear AreasSeeder
- Actualizar TicketManagementSeeder

---

## 🔧 FASE 1: MIGRACIONES

### Migration 1: Add Priority to Tickets

**Archivo:** `app/Features/TicketManagement/Database/migrations/2025_11_26_000001_add_priority_to_tickets.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear ENUM para prioridad
        DB::statement(
            "CREATE TYPE ticketing.ticket_priority AS ENUM ('low', 'medium', 'high', 'critical')"
        );

        // Agregar columna priority a tickets
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->string('priority', 20)
                ->default('medium')
                ->after('description')
                ->comment('Prioridad: low, medium, high, critical');
        });

        // Índice parcial para búsquedas de alta prioridad
        DB::statement(
            "CREATE INDEX idx_tickets_priority ON ticketing.tickets(priority)
             WHERE priority IN ('high', 'critical')"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ticketing.idx_tickets_priority');

        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->dropColumn('priority');
        });

        DB::statement('DROP TYPE IF EXISTS ticketing.ticket_priority');
    }
};
```

### Migration 2: Create Areas Table

**Archivo:** `app/Features/TicketManagement/Database/migrations/2025_11_26_000002_create_areas_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla areas
        Schema::create('ticketing.areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key a companies
            $table->foreign('company_id')
                ->references('id')
                ->on('business.companies')
                ->onDelete('cascade');

            // Nombre único por empresa
            $table->unique(['company_id', 'name'], 'areas_company_name_unique');

            // Índices
            $table->index('company_id', 'idx_areas_company_id');
            $table->index('is_active', 'idx_areas_is_active');
        });

        // Agregar columna area_id a tickets
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->uuid('area_id')
                ->nullable()
                ->after('category_id')
                ->comment('Área/departamento asignado (opcional)');

            $table->foreign('area_id')
                ->references('id')
                ->on('ticketing.areas')
                ->onDelete('set null');

            $table->index('area_id', 'idx_tickets_area_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropIndex('idx_tickets_area_id');
            $table->dropColumn('area_id');
        });

        Schema::dropIfExists('ticketing.areas');
    }
};
```

**Comando para ejecutar:**

```bash
docker compose exec app php artisan migrate
```

---

## 🎨 FASE 2: ENUMS Y MODELOS

### 1. Crear TicketPriority Enum

**Archivo:** `app/Features/TicketManagement/Enums/TicketPriority.php`

```php
<?php

namespace App\Features\TicketManagement\Enums;

/**
 * Enum de prioridades del ticket
 *
 * Prioridades:
 * - low: Baja prioridad (no urgente)
 * - medium: Prioridad media (default)
 * - high: Alta prioridad (requiere atención pronta)
 * - critical: Prioridad crítica (requiere atención inmediata)
 */
enum TicketPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    /**
     * Obtiene todos los valores como array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verifica si la prioridad es alta o crítica
     */
    public function isHigh(): bool
    {
        return in_array($this, [self::HIGH, self::CRITICAL]);
    }

    /**
     * Verifica si la prioridad es crítica
     */
    public function isCritical(): bool
    {
        return $this === self::CRITICAL;
    }

    /**
     * Obtiene el peso numérico para ordenamiento
     */
    public function order(): int
    {
        return match($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::CRITICAL => 4,
        };
    }

    /**
     * Obtiene el label legible
     */
    public function label(): string
    {
        return match($this) {
            self::LOW => 'Baja',
            self::MEDIUM => 'Media',
            self::HIGH => 'Alta',
            self::CRITICAL => 'Crítica',
        };
    }
}
```

### 2. Crear Area Model

**Archivo:** `app/Features/TicketManagement/Models/Area.php`

```php
<?php

namespace App\Features\TicketManagement\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Area Model - Áreas/Departamentos para tickets
 *
 * Tabla: ticketing.areas
 *
 * @property string $id
 * @property string $company_id
 * @property string $name
 * @property string|null $description
 * @property bool $is_active
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection<Ticket> $tickets
 */
class Area extends Model
{
    use HasFactory, HasUuid;

    /**
     * Tabla en PostgreSQL
     */
    protected $table = 'ticketing.areas';

    /**
     * Primary key es UUID
     */
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Campos asignables en masa
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
    ];

    /**
     * Conversión de tipos (casts)
     */
    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Pertenece a una empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relación: Tiene muchos tickets
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'area_id');
    }

    /**
     * Scope: Áreas activas
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filtrar por empresa
     */
    public function scopeByCompany(Builder $query, string $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
```

### 3. Actualizar Ticket Model

**Archivo:** `app/Features/TicketManagement/Models/Ticket.php`

**CAMBIOS en $fillable (línea 85-98):**

```php
protected $fillable = [
    'ticket_code',
    'created_by_user_id',
    'company_id',
    'category_id',
    'title',
    'description',
    'priority',        // NUEVO ✨
    'area_id',         // NUEVO ✨
    'status',
    'owner_agent_id',
    'last_response_author_type',
    'first_response_at',
    'resolved_at',
    'closed_at',
];
```

**CAMBIOS en $casts (línea 103-116):**

```php
protected $casts = [
    'id' => 'string',
    'created_by_user_id' => 'string',
    'company_id' => 'string',
    'category_id' => 'string',
    'owner_agent_id' => 'string',
    'area_id' => 'string',                  // NUEVO ✨
    'last_response_author_type' => 'string',
    'priority' => TicketPriority::class,    // NUEVO ✨
    'status' => TicketStatus::class,
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'first_response_at' => 'datetime',
    'resolved_at' => 'datetime',
    'closed_at' => 'datetime',
];
```

**AGREGAR relación (después de línea 140):**

```php
/**
 * Relación: Pertenece a un área (opcional)
 */
public function area(): BelongsTo
{
    return $this->belongsTo(Area::class, 'area_id');
}
```

**AGREGAR scope (después de línea 236):**

```php
/**
 * Scope: Filtrar por área
 */
public function scopeByArea(Builder $query, string $areaId): Builder
{
    return $query->where('area_id', $areaId);
}

/**
 * Scope: Filtrar por prioridad
 */
public function scopeByPriority(Builder $query, string $priority): Builder
{
    return $query->where('priority', $priority);
}
```

**ACTUALIZAR docblock (línea 17-48):**

```php
/**
 * Ticket Model - Centro del sistema de soporte
 *
 * Tabla: ticketing.tickets
 *
 * Ciclo de vida: open -> pending -> resolved -> closed
 *
 * @property string $id
 * @property string $ticket_code
 * @property string $created_by_user_id
 * @property string $company_id
 * @property string|null $category_id
 * @property string $title
 * @property string $description
 * @property TicketPriority $priority               // NUEVO ✨
 * @property string|null $area_id                   // NUEVO ✨
 * @property TicketStatus $status
 * @property string|null $owner_agent_id
 * @property string $last_response_author_type
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime|null $first_response_at
 * @property \DateTime|null $resolved_at
 * @property \DateTime|null $closed_at
 *
 * @property-read User $creator
 * @property-read User|null $ownerAgent
 * @property-read Company $company
 * @property-read Category|null $category
 * @property-read Area|null $area                   // NUEVO ✨
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketResponse> $responses
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketInternalNote> $internalNotes
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketAttachment> $attachments
 * @property-read TicketRating|null $rating
 */
```

---

## 🎯 FASE 3: FEATURE PRIORIDAD

### 1. Actualizar StoreTicketRequest

**Archivo:** `app/Features/TicketManagement/Http/Requests/StoreTicketRequest.php`

**AGREGAR reglas (línea 16-40):**

```php
public function rules(): array
{
    return [
        'title' => 'required|string|min:5|max:200',
        'description' => 'required|string|min:10|max:2000',
        'company_id' => 'required|uuid|exists:companies,id',
        'category_id' => [
            'required',
            'uuid',
            function ($attribute, $value, $fail) {
                $category = Category::find($value);
                if (!$category) {
                    $fail('La categoría seleccionada no existe.');
                    return;
                }
                if (!$category->is_active) {
                    $fail('La categoría seleccionada no está activa.');
                    return;
                }
                if ($category->company_id !== $this->input('company_id')) {
                    $fail('La categoría no pertenece a la compañía seleccionada.');
                }
            },
        ],
        // NUEVO ✨
        'priority' => 'sometimes|in:low,medium,high,critical',
        // NUEVO ✨
        'area_id' => [
            'nullable',
            'uuid',
            'exists:ticketing.areas,id',
            function ($attribute, $value, $fail) {
                if ($value) {
                    $area = \App\Features\TicketManagement\Models\Area::find($value);
                    if (!$area) {
                        $fail('El área seleccionada no existe.');
                        return;
                    }
                    if (!$area->is_active) {
                        $fail('El área seleccionada no está activa.');
                        return;
                    }
                    if ($area->company_id !== $this->input('company_id')) {
                        $fail('El área no pertenece a la compañía seleccionada.');
                    }
                }
            },
        ],
    ];
}
```

### 2. Actualizar UpdateTicketRequest

**Archivo:** `app/Features/TicketManagement/Http/Requests/UpdateTicketRequest.php`

**AGREGAR reglas:**

```php
public function rules(): array
{
    $ticket = $this->route('ticket');

    return [
        'title' => 'sometimes|required|string|min:5|max:200',
        'category_id' => [
            'sometimes',
            'required',
            'uuid',
            function ($attribute, $value, $fail) use ($ticket) {
                $category = Category::find($value);
                if (!$category) {
                    $fail('La categoría seleccionada no existe.');
                    return;
                }
                if (!$category->is_active) {
                    $fail('La categoría seleccionada no está activa.');
                    return;
                }
                if ($category->company_id !== $ticket->company_id) {
                    $fail('La categoría no pertenece a la misma compañía del ticket.');
                }
            },
        ],
        // NUEVO ✨
        'priority' => 'sometimes|in:low,medium,high,critical',
        // NUEVO ✨
        'area_id' => [
            'sometimes',
            'nullable',
            'uuid',
            'exists:ticketing.areas,id',
            function ($attribute, $value, $fail) use ($ticket) {
                if ($value) {
                    $area = \App\Features\TicketManagement\Models\Area::find($value);
                    if (!$area || !$area->is_active) {
                        $fail('El área seleccionada no existe o no está activa.');
                        return;
                    }
                    if ($area->company_id !== $ticket->company_id) {
                        $fail('El área no pertenece a la misma compañía del ticket.');
                    }
                }
            },
        ],
    ];
}
```

### 3. Actualizar TicketResource

**Archivo:** `app/Features/TicketManagement/Http/Resources/TicketResource.php`

**AGREGAR campos (línea 10-68):**

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'ticket_code' => $this->ticket_code,
        'company_id' => $this->company_id,
        'category_id' => $this->category_id,
        'area_id' => $this->area_id,           // NUEVO ✨
        'created_by_user_id' => $this->created_by_user_id,
        'owner_agent_id' => $this->owner_agent_id,
        'title' => $this->title,
        'description' => $this->description,
        'priority' => $this->priority->value,  // NUEVO ✨
        'status' => $this->status->value,
        'last_response_author_type' => $this->last_response_author_type,
        'resolved_at' => $this->resolved_at?->toIso8601String(),
        'closed_at' => $this->closed_at?->toIso8601String(),

        'created_by_user' => $this->whenLoaded('creator', function () {
            return [
                'id' => $this->creator->id,
                'name' => $this->creator->profile->full_name ?? $this->creator->email,
                'email' => $this->creator->email,
            ];
        }),

        'owner_agent' => $this->whenLoaded('ownerAgent', function () {
            return $this->ownerAgent ? [
                'id' => $this->ownerAgent->id,
                'name' => $this->ownerAgent->profile->full_name ?? $this->ownerAgent->email,
            ] : null;
        }),

        'company' => $this->whenLoaded('company', function () {
            return [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ];
        }),

        'category' => $this->whenLoaded('category', function () {
            return [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ];
        }),

        // NUEVO ✨
        'area' => $this->whenLoaded('area', function () {
            return $this->area ? [
                'id' => $this->area->id,
                'name' => $this->area->name,
            ] : null;
        }),

        'responses_count' => $this->when(isset($this->responses_count), $this->responses_count),
        'attachments_count' => $this->when(isset($this->attachments_count), $this->attachments_count),

        'timeline' => [
            'created_at' => $this->created_at->toIso8601String(),
            'first_response_at' => $this->first_response_at?->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
        ],

        'created_at' => $this->created_at->toIso8601String(),
        'updated_at' => $this->updated_at->toIso8601String(),
    ];
}
```

### 4. Actualizar TicketListResource

**Archivo:** `app/Features/TicketManagement/Http/Resources/TicketListResource.php`

**AGREGAR campos:**

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'ticket_code' => $this->ticket_code,
        'title' => $this->title,
        'priority' => $this->priority->value,  // NUEVO ✨
        'status' => $this->status->value,
        'last_response_author_type' => $this->last_response_author_type,

        'company_id' => $this->company_id,
        'category_id' => $this->category_id,
        'area_id' => $this->area_id,           // NUEVO ✨
        'created_by_user_id' => $this->created_by_user_id,
        'owner_agent_id' => $this->owner_agent_id,

        'creator_name' => $this->creator->profile->full_name ?? $this->creator->email,
        'owner_agent_name' => $this->ownerAgent?->profile->full_name ?? $this->ownerAgent?->email,
        'category_name' => $this->category?->name,
        'area_name' => $this->area?->name,     // NUEVO ✨

        'created_by_user' => $this->whenLoaded('creator', function () {
            return [
                'id' => $this->creator->id,
                'name' => $this->creator->profile->full_name ?? $this->creator->email,
                'email' => $this->creator->email,
            ];
        }),

        'owner_agent' => $this->whenLoaded('ownerAgent', function () {
            return $this->ownerAgent ? [
                'id' => $this->ownerAgent->id,
                'name' => $this->ownerAgent->profile->full_name ?? $this->ownerAgent->email,
                'email' => $this->ownerAgent->email,
            ] : null;
        }),

        'category' => $this->whenLoaded('category', function () {
            return $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null;
        }),

        // NUEVO ✨
        'area' => $this->whenLoaded('area', function () {
            return $this->area ? [
                'id' => $this->area->id,
                'name' => $this->area->name,
            ] : null;
        }),

        'company' => $this->whenLoaded('company', function () {
            return [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ];
        }),

        'responses_count' => $this->when(isset($this->responses_count), $this->responses_count),
        'attachments_count' => $this->when(isset($this->attachments_count), $this->attachments_count),

        'created_at' => $this->created_at->toIso8601String(),
        'updated_at' => $this->updated_at->toIso8601String(),
    ];
}
```

### 5. Actualizar TicketFactory

**Archivo:** `app/Features/TicketManagement/Database/Factories/TicketFactory.php`

**AGREGAR campos (línea 50-66):**

```php
return [
    'ticket_code' => $ticketCode,
    'created_by_user_id' => User::factory(),
    'company_id' => Company::factory(),
    'category_id' => Category::factory(),
    'title' => $this->faker->randomElement($titles),
    'description' => $this->faker->randomElement($descriptions) . ' ' . $this->faker->realText(200),
    'priority' => TicketPriority::MEDIUM,  // NUEVO ✨
    'area_id' => null,                     // NUEVO ✨
    'status' => TicketStatus::OPEN,
    'owner_agent_id' => null,
    'last_response_author_type' => 'none',
    'created_at' => now(),
    'updated_at' => now(),
    'first_response_at' => null,
    'resolved_at' => null,
    'closed_at' => null,
];
```

**AGREGAR métodos helper (al final del archivo):**

```php
/**
 * Ticket con prioridad específica
 */
public function withPriority(TicketPriority $priority): static
{
    return $this->state(fn (array $attributes) => [
        'priority' => $priority,
    ]);
}

/**
 * Ticket en área específica
 */
public function inArea(string $areaId): static
{
    return $this->state(fn (array $attributes) => [
        'area_id' => $areaId,
    ]);
}
```

---

## ⏰ FASE 4: FEATURE AUTO-ESCALADA

### 1. Crear EscalateTicketPriorityJob

**Archivo:** `app/Features/TicketManagement/Jobs/EscalateTicketPriorityJob.php`

```php
<?php

namespace App\Features\TicketManagement\Jobs;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketPriority;
use App\Features\TicketManagement\Enums\TicketStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para auto-escalamiento de prioridad de tickets
 *
 * Si un ticket OPEN no recibe respuesta de agente en 24 horas,
 * se escala automáticamente la prioridad a HIGH.
 */
class EscalateTicketPriorityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número de veces que el job puede ser reintentado
     */
    public int $tries = 3;

    /**
     * Timeout del job en segundos
     */
    public int $timeout = 30;

    /**
     * Constructor
     */
    public function __construct(public Ticket $ticket)
    {
        // Usar cola específica para auto-escalada
        $this->onQueue('default');
    }

    /**
     * Ejecutar el job
     */
    public function handle(): void
    {
        // Refrescar el ticket para obtener el estado más reciente
        $this->ticket->refresh();

        Log::info('EscalateTicketPriorityJob: Checking ticket', [
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'current_status' => $this->ticket->status->value,
            'current_priority' => $this->ticket->priority->value,
            'first_response_at' => $this->ticket->first_response_at,
        ]);

        // Verificar que el ticket sigue siendo OPEN
        if ($this->ticket->status !== TicketStatus::OPEN) {
            Log::info('EscalateTicketPriorityJob: Ticket no longer OPEN, skipping', [
                'ticket_code' => $this->ticket->ticket_code,
            ]);
            return;
        }

        // Verificar que no ha recibido respuesta de agente
        if ($this->ticket->first_response_at !== null) {
            Log::info('EscalateTicketPriorityJob: Ticket already has first response, skipping', [
                'ticket_code' => $this->ticket->ticket_code,
            ]);
            return;
        }

        // Verificar que no es ya CRITICAL
        if ($this->ticket->priority === TicketPriority::CRITICAL) {
            Log::info('EscalateTicketPriorityJob: Ticket already CRITICAL, skipping', [
                'ticket_code' => $this->ticket->ticket_code,
            ]);
            return;
        }

        // Escalar prioridad a HIGH
        $oldPriority = $this->ticket->priority;
        $this->ticket->update([
            'priority' => TicketPriority::HIGH,
        ]);

        Log::info('EscalateTicketPriorityJob: Priority escalated', [
            'ticket_code' => $this->ticket->ticket_code,
            'old_priority' => $oldPriority->value,
            'new_priority' => TicketPriority::HIGH->value,
        ]);

        // Aquí podrías disparar una notificación adicional si es necesario
        // event(new TicketPriorityEscalated($this->ticket));
    }

    /**
     * Manejar fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('EscalateTicketPriorityJob: Failed', [
            'ticket_id' => $this->ticket->id,
            'ticket_code' => $this->ticket->ticket_code,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### 2. Crear DispatchEscalationJob Listener

**Archivo:** `app/Features/TicketManagement/Listeners/DispatchEscalationJob.php`

```php
<?php

namespace App\Features\TicketManagement\Listeners;

use App\Features\TicketManagement\Events\TicketCreated;
use App\Features\TicketManagement\Jobs\EscalateTicketPriorityJob;
use Illuminate\Support\Facades\Log;

/**
 * Listener para disparar auto-escalamiento de prioridad
 *
 * Cuando se crea un ticket, programa un job para 24 horas después
 * que verificará si el ticket necesita escalamiento de prioridad.
 */
class DispatchEscalationJob
{
    /**
     * Handle the event
     */
    public function handle(TicketCreated $event): void
    {
        Log::debug('DispatchEscalationJob: Scheduling escalation check', [
            'ticket_id' => $event->ticket->id,
            'ticket_code' => $event->ticket->ticket_code,
            'scheduled_for' => now()->addHours(24)->toIso8601String(),
        ]);

        // Disparar job que se ejecuta en 24 horas
        EscalateTicketPriorityJob::dispatch($event->ticket)
            ->delay(now()->addHours(24));
    }
}
```

### 3. Registrar Listener

**Archivo:** `app/Features/TicketManagement/TicketManagementServiceProvider.php`

**AGREGAR en método registerEventListeners():**

```php
/**
 * Registrar event listeners
 */
private function registerEventListeners(): void
{
    $events = $this->app['events'];

    // Existing listeners...
    $events->listen(
        \App\Features\CompanyManagement\Events\CompanyCreated::class,
        \App\Features\TicketManagement\Listeners\CreateDefaultCategoriesListener::class
    );

    $events->listen(
        \App\Features\TicketManagement\Events\ResponseAdded::class,
        \App\Features\TicketManagement\Listeners\SendTicketResponseEmail::class
    );

    // NUEVO ✨ - Auto-escalada de prioridad
    $events->listen(
        \App\Features\TicketManagement\Events\TicketCreated::class,
        \App\Features\TicketManagement\Listeners\DispatchEscalationJob::class
    );
}
```

**NOTA:** El evento `TicketCreated` ya existe y ya se dispara en `TicketService::create()` línea 61 ✅

---

## 📧 FASE 5: FEATURE RECORDATORIOS

### 1. Crear TicketReminderController

**Archivo:** `app/Features/TicketManagement/Http/Controllers/TicketReminderController.php`

```php
<?php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Mail\TicketReminderMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Gate;

/**
 * Controller para recordatorios de tickets
 */
class TicketReminderController
{
    /**
     * Enviar recordatorio al creador del ticket
     */
    public function send(Request $request, Ticket $ticket): JsonResponse
    {
        // Autorizar (solo agentes pueden enviar recordatorios)
        Gate::authorize('sendReminder', $ticket);

        // Validar mensaje opcional
        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        $message = $validated['message'] ??
            '¿Hay algo más en lo que podamos ayudarte con este ticket?';

        // Enviar email al creador del ticket
        Mail::to($ticket->creator->email)->send(
            new TicketReminderMail($ticket, $message)
        );

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio enviado exitosamente',
        ]);
    }
}
```

### 2. Crear TicketReminderMail

**Archivo:** `app/Features/TicketManagement/Mail/TicketReminderMail.php`

```php
<?php

namespace App\Features\TicketManagement\Mail;

use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mail de recordatorio de ticket
 */
class TicketReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Constructor
     */
    public function __construct(
        public Ticket $ticket,
        public string $message,
    ) {}

    /**
     * Get the message envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recordatorio: Ticket {$this->ticket->ticket_code}",
        );
    }

    /**
     * Get the message content definition
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticketing.ticket-reminder',
            text: 'emails.ticketing.ticket-reminder-text',
            with: [
                'ticket' => $this->ticket,
                'message' => $this->message,
                'ticketViewUrl' => config('app.frontend_url') . '/tickets/' . $this->ticket->ticket_code,
            ],
        );
    }
}
```

### 3. Crear Vista de Email (HTML)

**Archivo:** `resources/views/emails/ticketing/ticket-reminder.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .ticket-code { font-size: 18px; font-weight: bold; color: #007bff; }
        .content { padding: 20px 0; }
        .ticket-details { background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 24px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 12px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="ticket-code">Ticket {{ $ticket->ticket_code }}</div>
        </div>

        <div class="content">
            <p>Hola {{ $ticket->creator->profile->full_name ?? $ticket->creator->email }},</p>

            <p>{{ $message }}</p>

            <div class="ticket-details">
                <p><strong>Ticket:</strong> {{ $ticket->ticket_code }}</p>
                <p><strong>Asunto:</strong> {{ $ticket->title }}</p>
                <p><strong>Estado:</strong> {{ $ticket->status->value }}</p>
                <p><strong>Prioridad:</strong> {{ $ticket->priority->label() }}</p>
                @if($ticket->category)
                <p><strong>Categoría:</strong> {{ $ticket->category->name }}</p>
                @endif
            </div>

            <a href="{{ $ticketViewUrl }}" class="button">Ver Ticket</a>
        </div>

        <div class="footer">
            <p>Gracias,<br>{{ config('app.name') }} Support Team</p>
            <p>Este es un correo automático, por favor no respondas a este email.</p>
        </div>
    </div>
</body>
</html>
```

### 4. Crear Vista de Email (Text)

**Archivo:** `resources/views/emails/ticketing/ticket-reminder-text.blade.php`

```blade
Recordatorio: Ticket {{ $ticket->ticket_code }}

Hola {{ $ticket->creator->profile->full_name ?? $ticket->creator->email }},

{{ $message }}

Detalles del Ticket:
- Ticket: {{ $ticket->ticket_code }}
- Asunto: {{ $ticket->title }}
- Estado: {{ $ticket->status->value }}
- Prioridad: {{ $ticket->priority->label() }}
@if($ticket->category)
- Categoría: {{ $ticket->category->name }}
@endif

Ver ticket: {{ $ticketViewUrl }}

---
Gracias,
{{ config('app.name') }} Support Team

Este es un correo automático, por favor no respondas a este email.
```

### 5. Actualizar TicketPolicy

**Archivo:** `app/Features/TicketManagement/Policies/TicketPolicy.php`

**AGREGAR método:**

```php
/**
 * Determine if the user can send a reminder for the ticket
 */
public function sendReminder(User $user, Ticket $ticket): bool
{
    // Solo agentes y admins de la empresa pueden enviar recordatorios
    return $user->hasRoleInCompany('AGENT', $ticket->company_id) ||
           $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id);
}
```

### 6. Agregar Ruta

**Archivo:** `routes/api.php`

**AGREGAR después de línea 559:**

```php
// Send ticket reminder (policy-based authorization)
Route::post('/tickets/{ticket}/remind', [\App\Features\TicketManagement\Http\Controllers\TicketReminderController::class, 'send'])
    ->name('tickets.remind');
```

---

## 🏢 FASE 6: FEATURE ÁREAS

### 1. Crear AreaService

**Archivo:** `app/Features/TicketManagement/Services/AreaService.php`

```php
<?php

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Service para gestión de Áreas/Departamentos
 */
class AreaService
{
    /**
     * Crear nueva área
     */
    public function create(Company $company, array $data): Area
    {
        return $company->areas()->create($data);
    }

    /**
     * Actualizar área
     */
    public function update(Area $area, array $data): Area
    {
        $area->update($data);
        return $area->fresh();
    }

    /**
     * Eliminar área
     */
    public function delete(Area $area): bool
    {
        // Verificar que no haya tickets activos asociados
        $activeTicketsCount = $area->tickets()
            ->whereIn('status', ['open', 'pending', 'resolved'])
            ->count();

        if ($activeTicketsCount > 0) {
            throw new \Exception(
                "No se puede eliminar el área porque tiene {$activeTicketsCount} tickets activos asociados."
            );
        }

        return $area->delete();
    }

    /**
     * Listar áreas
     */
    public function list(Company $company, ?bool $isActive = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $company->areas()->orderBy('name');

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener área por ID
     */
    public function find(string $id): ?Area
    {
        return Area::find($id);
    }
}
```

### 2. Crear AreaController

**Archivo:** `app/Features/TicketManagement/Http/Controllers/AreaController.php`

```php
<?php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Models\Area;
use App\Features\TicketManagement\Services\AreaService;
use App\Features\TicketManagement\Http\Requests\StoreAreaRequest;
use App\Features\TicketManagement\Http\Requests\UpdateAreaRequest;
use App\Features\TicketManagement\Http\Resources\AreaResource;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Controller para gestión de Áreas
 */
class AreaController
{
    public function __construct(private AreaService $service) {}

    /**
     * Listar áreas de la empresa
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $isActive = $request->query('is_active');
        if ($isActive !== null) {
            $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN);
        }

        $perPage = (int) $request->query('per_page', 15);

        $areas = $this->service->list($company, $isActive, $perPage);

        return response()->json([
            'success' => true,
            'data' => AreaResource::collection($areas),
            'meta' => [
                'current_page' => $areas->currentPage(),
                'last_page' => $areas->lastPage(),
                'per_page' => $areas->perPage(),
                'total' => $areas->total(),
            ],
        ]);
    }

    /**
     * Crear nueva área
     */
    public function store(StoreAreaRequest $request): JsonResponse
    {
        Gate::authorize('create', Area::class);

        $validated = $request->validated();
        $company = $request->user()->company;

        $area = $this->service->create($company, $validated);

        return response()->json([
            'success' => true,
            'data' => new AreaResource($area),
            'message' => 'Área creada exitosamente',
        ], 201);
    }

    /**
     * Obtener detalle de área
     */
    public function show(Area $area): JsonResponse
    {
        Gate::authorize('view', $area);

        return response()->json([
            'success' => true,
            'data' => new AreaResource($area),
        ]);
    }

    /**
     * Actualizar área
     */
    public function update(UpdateAreaRequest $request, Area $area): JsonResponse
    {
        Gate::authorize('update', $area);

        $validated = $request->validated();
        $area = $this->service->update($area, $validated);

        return response()->json([
            'success' => true,
            'data' => new AreaResource($area),
            'message' => 'Área actualizada exitosamente',
        ]);
    }

    /**
     * Eliminar área
     */
    public function destroy(Area $area): JsonResponse
    {
        Gate::authorize('delete', $area);

        try {
            $this->service->delete($area);

            return response()->json([
                'success' => true,
                'message' => 'Área eliminada exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
```

### 3. Crear StoreAreaRequest

**Archivo:** `app/Features/TicketManagement/Http/Requests/StoreAreaRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Http\Requests;

use App\Features\TicketManagement\Models\Area;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return JWTHelper::hasRoleFromJWT('COMPANY_ADMIN');
    }

    public function rules(): array
    {
        $companyId = $this->user()->company->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('ticketing.areas', 'name')
                    ->where('company_id', $companyId),
            ],
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un área con este nombre en tu empresa.',
        ];
    }
}
```

### 4. Crear UpdateAreaRequest

**Archivo:** `app/Features/TicketManagement/Http/Requests/UpdateAreaRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Http\Requests;

use App\Features\TicketManagement\Models\Area;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return JWTHelper::hasRoleFromJWT('COMPANY_ADMIN');
    }

    public function rules(): array
    {
        $area = $this->route('area');
        $companyId = $area->company_id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('ticketing.areas', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($area->id),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Ya existe un área con este nombre en tu empresa.',
        ];
    }
}
```

### 5. Crear AreaResource

**Archivo:** `app/Features/TicketManagement/Http/Resources/AreaResource.php`

```php
<?php

namespace App\Features\TicketManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

### 6. Crear AreaPolicy

**Archivo:** `app/Features/TicketManagement/Policies/AreaPolicy.php`

```php
<?php

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Area;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;

class AreaPolicy
{
    /**
     * Determine if the user can view any areas
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden listar áreas
        return true;
    }

    /**
     * Determine if the user can view the area
     */
    public function view(User $user, Area $area): bool
    {
        // Todos los usuarios de la misma empresa pueden ver el área
        return $user->hasCompanyContext($area->company_id);
    }

    /**
     * Determine if the user can create areas
     */
    public function create(User $user): bool
    {
        // Solo COMPANY_ADMIN puede crear áreas
        return JWTHelper::hasRoleFromJWT('COMPANY_ADMIN');
    }

    /**
     * Determine if the user can update the area
     */
    public function update(User $user, Area $area): bool
    {
        // Solo COMPANY_ADMIN de la misma empresa
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
        return $companyId && $companyId === $area->company_id;
    }

    /**
     * Determine if the user can delete the area
     */
    public function delete(User $user, Area $area): bool
    {
        // Solo COMPANY_ADMIN de la misma empresa
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
        return $companyId && $companyId === $area->company_id;
    }
}
```

### 7. Registrar Policy

**Archivo:** `app/Features/TicketManagement/TicketManagementServiceProvider.php`

**ACTUALIZAR método boot():**

```php
public function boot(): void
{
    $this->registerMigrations();
    $this->registerEventListeners();
    $this->registerPolicies();  // AGREGAR ESTA LÍNEA
}

/**
 * Registrar policies
 */
private function registerPolicies(): void
{
    Gate::policy(\App\Features\TicketManagement\Models\Ticket::class, \App\Features\TicketManagement\Policies\TicketPolicy::class);
    Gate::policy(\App\Features\TicketManagement\Models\Category::class, \App\Features\TicketManagement\Policies\CategoryPolicy::class);
    Gate::policy(\App\Features\TicketManagement\Models\TicketResponse::class, \App\Features\TicketManagement\Policies\TicketResponsePolicy::class);
    Gate::policy(\App\Features\TicketManagement\Models\TicketAttachment::class, \App\Features\TicketManagement\Policies\TicketAttachmentPolicy::class);
    Gate::policy(\App\Features\TicketManagement\Models\TicketRating::class, \App\Features\TicketManagement\Policies\TicketRatingPolicy::class);
    Gate::policy(\App\Features\TicketManagement\Models\Area::class, \App\Features\TicketManagement\Policies\AreaPolicy::class);  // NUEVO ✨
}
```

### 8. Agregar Rutas

**Archivo:** `routes/api.php`

**AGREGAR después de línea 445 (antes de route model binding):**

```php
// ========== TICKET AREAS - Resource Endpoints ==========
Route::apiResource('areas', \App\Features\TicketManagement\Http\Controllers\AreaController::class);
```

**AGREGAR import al inicio del archivo:**

```php
use App\Features\TicketManagement\Http\Controllers\AreaController;
use App\Features\TicketManagement\Http\Controllers\TicketReminderController;
```

---

## 🧪 FASE 7: ACTUALIZACIÓN DE TESTS

### 1. Actualizar CreateTicketTest

**Archivo:** `tests/Feature/TicketManagement/Tickets/CRUD/CreateTicketTest.php`

**ACTUALIZAR test `user_can_create_ticket` (línea 70-120):**

```php
#[Test]
public function user_can_create_ticket(): void
{
    // Arrange
    $user = User::factory()->withRole('USER')->create();
    $company = Company::factory()->create();
    $category = Category::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);

    $payload = [
        'title' => 'Test ticket title here',
        'description' => 'This is a detailed description with more than 10 characters',
        'company_id' => $company->id,
        'category_id' => $category->id,
        'priority' => 'medium',  // NUEVO ✨
    ];

    // Act
    $response = $this->actingAs($user)
        ->postJson('/api/tickets', $payload);

    // Assert
    $response->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'ticket_code',
                'company_id',
                'category_id',
                'created_by_user_id',
                'title',
                'description',
                'priority',           // NUEVO ✨
                'area_id',           // NUEVO ✨
                'status',
                'created_at',
                'updated_at',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'title' => 'Test ticket title here',
                'priority' => 'medium',  // NUEVO ✨
                'status' => 'open',
            ],
        ]);

    $this->assertDatabaseHas('ticketing.tickets', [
        'title' => 'Test ticket title here',
        'priority' => 'medium',  // NUEVO ✨
        'company_id' => $company->id,
        'status' => 'open',
    ]);
}
```

**AGREGAR test para validación de priority:**

```php
#[Test]
public function validates_priority_enum_values(): void
{
    // Arrange
    $user = User::factory()->withRole('USER')->create();
    $company = Company::factory()->create();
    $category = Category::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);

    $payload = [
        'title' => 'Test ticket title here',
        'description' => 'This is a detailed description',
        'company_id' => $company->id,
        'category_id' => $category->id,
        'priority' => 'invalid_priority',  // Valor inválido
    ];

    // Act
    $response = $this->actingAs($user)
        ->postJson('/api/tickets', $payload);

    // Assert
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['priority']);
}
```

### 2. Actualizar ListTicketsTest

**Archivo:** `tests/Feature/TicketManagement/Tickets/CRUD/ListTicketsTest.php`

**AGREGAR test para filtrar por priority:**

```php
#[Test]
public function filter_by_priority_works(): void
{
    // Arrange
    $company = Company::factory()->create();
    $user = User::factory()->withRole('USER')->create();
    $category = Category::factory()->create(['company_id' => $company->id]);

    // Crear tickets con diferentes prioridades
    $ticketLow = Ticket::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $user->id,
        'category_id' => $category->id,
        'priority' => \App\Features\TicketManagement\Enums\TicketPriority::LOW,
    ]);

    $ticketHigh = Ticket::factory()->create([
        'company_id' => $company->id,
        'created_by_user_id' => $user->id,
        'category_id' => $category->id,
        'priority' => \App\Features\TicketManagement\Enums\TicketPriority::HIGH,
    ]);

    // Act
    $response = $this->actingAs($user)
        ->getJson('/api/tickets?priority=high');

    // Assert
    $response->assertStatus(200);
    $data = $response->json('data');

    $this->assertCount(1, $data);
    $this->assertEquals($ticketHigh->id, $data[0]['id']);
}
```

### 3. Actualizar GetTicketTest

**Archivo:** `tests/Feature/TicketManagement/Tickets/CRUD/GetTicketTest.php`

**ACTUALIZAR test `ticket_detail_includes_complete_information` (línea 100-150):**

```php
#[Test]
public function ticket_detail_includes_complete_information(): void
{
    // Arrange
    $user = User::factory()->withRole('USER')->create();
    $company = Company::factory()->create();
    $category = Category::factory()->create(['company_id' => $company->id]);
    $ticket = Ticket::factory()->create([
        'created_by_user_id' => $user->id,
        'company_id' => $company->id,
        'category_id' => $category->id,
        'priority' => \App\Features\TicketManagement\Enums\TicketPriority::HIGH,  // NUEVO ✨
    ]);

    // Act
    $response = $this->actingAs($user)
        ->getJson("/api/tickets/{$ticket->ticket_code}");

    // Assert
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'ticket_code',
                'title',
                'description',
                'priority',        // NUEVO ✨
                'area_id',        // NUEVO ✨
                'status',
                'company_id',
                'category_id',
                'created_by_user_id',
                'created_at',
                'updated_at',
                'timeline',
            ],
        ])
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $ticket->id,
                'priority' => 'high',  // NUEVO ✨
            ],
        ]);
}
```

### 4. Crear AutoEscalateTicketPriorityJobTest

**Archivo:** `tests/Unit/TicketManagement/Jobs/AutoEscalateTicketPriorityJobTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\TicketManagement\Jobs;

use App\Features\TicketManagement\Jobs\EscalateTicketPriorityJob;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketPriority;
use App\Features\TicketManagement\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AutoEscalateTicketPriorityJobTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function escalates_open_ticket_without_first_response(): void
    {
        // Arrange
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::MEDIUM,
            'first_response_at' => null,
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => TicketPriority::HIGH->value,
        ]);
    }

    #[Test]
    public function does_not_escalate_ticket_with_first_response(): void
    {
        // Arrange
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::MEDIUM,
            'first_response_at' => now(),
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => TicketPriority::MEDIUM->value,
        ]);
    }

    #[Test]
    public function does_not_escalate_non_open_ticket(): void
    {
        // Arrange
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::PENDING,
            'priority' => TicketPriority::MEDIUM,
            'first_response_at' => null,
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => TicketPriority::MEDIUM->value,
        ]);
    }

    #[Test]
    public function does_not_escalate_critical_ticket(): void
    {
        // Arrange
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::OPEN,
            'priority' => TicketPriority::CRITICAL,
            'first_response_at' => null,
        ]);

        // Act
        $job = new EscalateTicketPriorityJob($ticket);
        $job->handle();

        // Assert
        $this->assertDatabaseHas('ticketing.tickets', [
            'id' => $ticket->id,
            'priority' => TicketPriority::CRITICAL->value,
        ]);
    }
}
```

### 5. Crear TicketReminderTest

**Archivo:** `tests/Feature/TicketManagement/TicketReminderTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\TicketManagement;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Mail\TicketReminderMail;
use App\Features\UserManagement\Models\User;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

class TicketReminderTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    #[Test]
    public function agent_can_send_reminder_to_ticket_creator(): void
    {
        // Arrange
        Mail::fake();

        $company = \App\Features\CompanyManagement\Models\Company::factory()->create();
        $creator = User::factory()->withRole('USER')->create();
        $agent = User::factory()->withRole('AGENT')->create();

        // Asociar agente a la empresa
        \App\Features\UserManagement\Models\UserRole::create([
            'id' => \Illuminate\Support\Str::uuid(),
            'user_id' => $agent->id,
            'company_id' => $company->id,
            'role' => 'AGENT',
            'status' => 'active',
        ]);

        $ticket = Ticket::factory()->create([
            'created_by_user_id' => $creator->id,
            'company_id' => $company->id,
        ]);

        $payload = [
            'message' => '¿Necesitas ayuda adicional?',
        ];

        // Act
        $response = $this->actingAs($agent)
            ->postJson("/api/tickets/{$ticket->ticket_code}/remind", $payload);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Recordatorio enviado exitosamente',
            ]);

        Mail::assertSent(TicketReminderMail::class, function ($mail) use ($creator) {
            return $mail->hasTo($creator->email);
        });
    }

    #[Test]
    public function user_cannot_send_reminder(): void
    {
        // Arrange
        $company = \App\Features\CompanyManagement\Models\Company::factory()->create();
        $user = User::factory()->withRole('USER')->create();
        $ticket = Ticket::factory()->create([
            'created_by_user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        // Act
        $response = $this->actingAs($user)
            ->postJson("/api/tickets/{$ticket->ticket_code}/remind");

        // Assert
        $response->assertStatus(403);
    }
}
```

---

## 🌱 FASE 8: SEEDERS

### 1. Actualizar PilAndinaTicketsSeeder

**Archivo:** `app/Features/TicketManagement/Database/Seeders/PilAndinaTicketsSeeder.php`

**AGREGAR imports:**

```php
use App\Features\TicketManagement\Enums\TicketPriority;
```

**ACTUALIZAR cada Ticket::create() agregando 'priority':**

```php
Ticket::create([
    'id' => Str::uuid(),
    'ticket_code' => 'TKT-2025-00001',
    'created_by_user_id' => $user1->id,
    'company_id' => $pilAndina->id,
    'category_id' => $maintenanceCategory->id,
    'title' => 'Fugas en válvulas de producción',
    'description' => 'Se detectaron fugas en múltiples válvulas...',
    'priority' => TicketPriority::HIGH,  // NUEVO ✨
    'status' => TicketStatus::OPEN,
    'created_at' => now()->subDays(2),
    'updated_at' => now()->subDays(2),
]),

// Repetir para los 12 tickets con prioridades sugeridas:
// TKT-2025-00001: HIGH
// TKT-2025-00002: MEDIUM
// TKT-2025-00003: HIGH
// TKT-2025-00004: MEDIUM
// TKT-2025-00005: CRITICAL
// TKT-2025-00006: MEDIUM
// TKT-2025-00007: CRITICAL
// TKT-2025-00008: MEDIUM
// TKT-2025-00009: HIGH
// TKT-2025-00010: HIGH
// TKT-2025-00011: CRITICAL
// TKT-2025-00012: LOW
```

### 2. Actualizar YPFBTicketsSeeder

**Archivo:** `app/Features/TicketManagement/Database/Seeders/YPFBTicketsSeeder.php`

**Similar a PilAndinaTicketsSeeder, agregar 'priority' a cada ticket:**

```php
// Prioridades sugeridas para YPFB:
// TKT-2025-00001: CRITICAL
// TKT-2025-00002: MEDIUM
// TKT-2025-00003: CRITICAL
// TKT-2025-00004: MEDIUM
// TKT-2025-00005: HIGH
// TKT-2025-00006: HIGH
// TKT-2025-00007: MEDIUM
// TKT-2025-00008: MEDIUM
// TKT-2025-00009: HIGH
// TKT-2025-00010: CRITICAL
// TKT-2025-00011: HIGH
// TKT-2025-00012: MEDIUM
```

### 3. Crear AreasSeeder

**Archivo:** `app/Features/TicketManagement/Database/Seeders/AreasSeeder.php`

```php
<?php

namespace App\Features\TicketManagement\Database\Seeders;

use App\Features\TicketManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AreasSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // Áreas generales para todas las empresas
            $areas = [
                [
                    'name' => 'Soporte Técnico',
                    'description' => 'Área de soporte técnico y asistencia IT',
                ],
                [
                    'name' => 'Ventas',
                    'description' => 'Área comercial y ventas',
                ],
                [
                    'name' => 'Operaciones',
                    'description' => 'Área de operaciones y logística',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Área de gestión de personal',
                ],
                [
                    'name' => 'Finanzas',
                    'description' => 'Área contable y financiera',
                ],
            ];

            foreach ($areas as $areaData) {
                Area::create([
                    'id' => Str::uuid(),
                    'company_id' => $company->id,
                    'name' => $areaData['name'],
                    'description' => $areaData['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
```

### 4. Actualizar TicketManagementSeeder

**Archivo:** `app/Features/TicketManagement/Database/Seeders/TicketManagementSeeder.php`

**AGREGAR llamada a AreasSeeder:**

```php
public function run(): void
{
    $this->call([
        DefaultCategoriesSeeder::class,
        AreasSeeder::class,              // NUEVO ✨
        PilAndinaTicketsSeeder::class,
        YPFBTicketsSeeder::class,
    ]);
}
```

---

## 🚀 COMANDOS DE EJECUCIÓN

### 1. Ejecutar Migraciones

```bash
# Ejecutar las 2 nuevas migraciones
docker compose exec app php artisan migrate

# Verificar que se ejecutaron correctamente
docker compose exec app php artisan migrate:status
```

### 2. Ejecutar Seeders

```bash
# Ejecutar solo el seeder de áreas
docker compose exec app php artisan db:seed --class="App\Features\TicketManagement\Database\Seeders\AreasSeeder"

# O ejecutar todo TicketManagementSeeder
docker compose exec app php artisan db:seed --class="App\Features\TicketManagement\Database\Seeders\TicketManagementSeeder"
```

### 3. Limpiar Cachés

```bash
# Limpiar todas las cachés
docker compose exec app php artisan optimize:clear

# Limpiar específicas
docker compose exec app php artisan route:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan view:clear
```

### 4. Ejecutar Tests

```bash
# Ejecutar todos los tests
docker compose exec app php artisan test

# Ejecutar solo tests de TicketManagement
docker compose exec app php artisan test tests/Feature/TicketManagement
docker compose exec app php artisan test tests/Unit/TicketManagement

# Ejecutar test específico
docker compose exec app php artisan test --filter=CreateTicketTest
```

### 5. Formatear Código

```bash
# Formatear todo el código
docker compose exec app ./vendor/bin/pint

# Formatear archivo específico
docker compose exec app ./vendor/bin/pint app/Features/TicketManagement
```

---

## ✅ CHECKLIST FINAL

### PRE-IMPLEMENTACIÓN
- [ ] Backup de base de datos
- [ ] Branch feature/ticket-management actualizada
- [ ] Docker containers corriendo

### FASE 1: MIGRACIONES
- [ ] Migration priority creada y ejecutada
- [ ] Migration areas creada y ejecutada
- [ ] Schema verificado en DB
- [ ] ENUM ticket_priority existe
- [ ] Tabla ticketing.areas existe
- [ ] Columnas priority y area_id existen en tickets

### FASE 2: ENUMS Y MODELOS
- [ ] TicketPriority enum creado
- [ ] Area model creado con factory
- [ ] Ticket model actualizado ($fillable, $casts, relaciones, scopes)
- [ ] Company model actualizado (relación areas)

### FASE 3: FEATURE PRIORIDAD
- [ ] StoreTicketRequest actualizado
- [ ] UpdateTicketRequest actualizado
- [ ] TicketResource actualizado
- [ ] TicketListResource actualizado
- [ ] TicketFactory actualizado

### FASE 4: FEATURE AUTO-ESCALADA
- [ ] EscalateTicketPriorityJob creado
- [ ] DispatchEscalationJob listener creado
- [ ] Listener registrado en ServiceProvider
- [ ] Logs funcionando correctamente

### FASE 5: FEATURE RECORDATORIOS
- [ ] TicketReminderController creado
- [ ] TicketReminderMail creado
- [ ] Vista blade HTML creada
- [ ] Vista blade Text creada
- [ ] TicketPolicy.sendReminder() agregado
- [ ] Ruta POST /tickets/{ticket}/remind agregada
- [ ] Caché de rutas limpiado

### FASE 6: FEATURE ÁREAS
- [ ] AreaService creado
- [ ] AreaController creado
- [ ] StoreAreaRequest creado
- [ ] UpdateAreaRequest creado
- [ ] AreaResource creado
- [ ] AreaPolicy creado
- [ ] Policy registrado en ServiceProvider
- [ ] Rutas apiResource agregadas
- [ ] Caché de rutas limpiado

### FASE 7: TESTS
- [ ] CreateTicketTest actualizado
- [ ] ListTicketsTest actualizado con filter_by_priority
- [ ] GetTicketTest actualizado
- [ ] AutoEscalateTicketPriorityJobTest creado
- [ ] TicketReminderTest creado
- [ ] Todos los tests pasan (green)

### FASE 8: SEEDERS
- [ ] PilAndinaTicketsSeeder actualizado con priority
- [ ] YPFBTicketsSeeder actualizado con priority
- [ ] AreasSeeder creado
- [ ] TicketManagementSeeder actualizado
- [ ] Seeders ejecutados sin errores

### FINALIZACIÓN
- [ ] Código formateado con Pint
- [ ] Todos los tests pasan
- [ ] Documentación actualizada
- [ ] Commit con mensaje descriptivo
- [ ] Ready para merge a master

---

## 📊 ESTADÍSTICAS FINALES

```
ARCHIVOS NUEVOS:         17
- Migraciones:           2
- Enums:                 1
- Models:                1
- Jobs:                  1
- Listeners:             1
- Mails:                 1
- Controllers:           2
- Form Requests:         3
- Resources:             1
- Policies:              1
- Tests:                 2
- Seeders:               1

ARCHIVOS MODIFICADOS:    13
- Models:                2 (Ticket, Company)
- Form Requests:         2 (StoreTicketRequest, UpdateTicketRequest)
- Resources:             2 (TicketResource, TicketListResource)
- Policies:              1 (TicketPolicy)
- ServiceProvider:       1 (TicketManagementServiceProvider)
- Factory:               1 (TicketFactory)
- Routes:                1 (api.php)
- Tests:                 3 (CreateTicketTest, ListTicketsTest, GetTicketTest)

TOTAL ARCHIVOS:          30
```

---

**FIN DEL DOCUMENTO**

¡Todo listo para empezar la implementación! 🚀
