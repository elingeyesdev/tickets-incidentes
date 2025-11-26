# Plan de Implementación Final: Nuevas Features en Tickets

**Documento listo para desarrollo**
**Fecha:** Noviembre 26, 2025
**Estado:** ✅ Aprobado - Listo para iniciar

---

## 📋 Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Cambios en Base de Datos](#cambios-en-base-de-datos)
3. [Migraciones Necesarias](#migraciones-necesarias)
4. [Feature 1: Prioridad](#feature-1-prioridad)
5. [Feature 2: Auto-Escalada](#feature-2-auto-escalada)
6. [Feature 3: Recordatorios](#feature-3-recordatorios)
7. [Feature 4: Áreas](#feature-4-áreas)
8. [Plan de Desarrollo](#plan-de-desarrollo)

---

## 🎯 Resumen Ejecutivo

Se implementarán **4 features** de manera simple y directa:

| Feature | BD | Laravel | Tiempo |
|---------|-----|---------|--------|
| **Prioridad** | 1 ENUM + 1 columna | Enum + Model | 20 min |
| **Auto-Escalada** | ❌ Ninguno | Event + Job + Listener | 30 min |
| **Recordatorios** | ❌ Ninguno | Endpoint + Mail | 30 min |
| **Áreas** | 1 tabla | Model + Service | 30 min |
| **TOTAL** | Simple | ~120 líneas | **~2 horas** |

---

## 🔄 Cambios en Base de Datos

### Resumen

```
✨ Nuevo ENUM:        ticket_priority
✨ Nueva tabla:       ticketing.areas
✨ Nueva columna:     ticketing.tickets.priority
✨ Nueva columna:     ticketing.tickets.area_id
✨ Nuevos índices:    4
✨ Configuración:     company.settings (JSON, ya existe)
```

---

## 📊 Migraciones Necesarias

### Migration 1: Prioridad (5 minutos)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear ENUM
        DB::statement(
            "CREATE TYPE ticketing.ticket_priority AS ENUM ('low', 'medium', 'high', 'critical')"
        );

        // Agregar columna a tickets
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->string('priority')
                ->default('medium')
                ->comment('Prioridad: low, medium, high, critical');
        });

        // Índice para búsquedas por prioridad
        DB::statement(
            "CREATE INDEX idx_tickets_priority ON ticketing.tickets(priority)
             WHERE priority IN ('high', 'critical')"
        );
    }

    public function down(): void
    {
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->dropColumn('priority');
        });

        DB::statement('DROP TYPE ticketing.ticket_priority');
    }
};
```

---

### Migration 2: Áreas (5 minutos)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // Relaciones y constraints
            $table->foreign('company_id')
                ->references('id')
                ->on('business.companies')
                ->onDelete('cascade');

            // Nombre único por empresa
            $table->unique(['company_id', 'name']);

            // Índices
            $table->index('company_id');
            $table->index('is_active');
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

            $table->index('area_id');
        });
    }

    public function down(): void
    {
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropIndex('ticketing_tickets_area_id_index');
            $table->dropColumn('area_id');
        });

        Schema::dropIfExists('ticketing.areas');
    }
};
```

---

## 🎯 Feature 1: Prioridad

### Enumeración

```php
// app/Features/TicketManagement/Enums/TicketPriority.php

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
}
```

### Model Actualizado

```php
// app/Features/TicketManagement/Models/Ticket.php

protected $fillable = [
    // ... existing ...
    'priority',  // NUEVO
];

protected $casts = [
    // ... existing ...
    'priority' => TicketPriority::class,  // NUEVO
];
```

### Validación

```php
// app/Features/TicketManagement/Http/Requests/StoreTicketRequest.php

public function rules(): array
{
    return [
        // ... existing ...
        'priority' => 'sometimes|in:low,medium,high,critical',
    ];
}
```

### Response

```php
// app/Features/TicketManagement/Http/Resources/TicketResource.php

public function toArray($request): array
{
    return [
        // ... existing ...
        'priority' => $this->priority->value,  // Convierte enum a string
    ];
}
```

---

## ⏰ Feature 2: Auto-Escalada (24h sin respuesta)

**Lógica:** Si ticket OPEN lleva 24h sin respuesta de agente → prioridad cambia a HIGH

### Event

```php
// app/Features/TicketManagement/Events/TicketCreated.php

namespace App\Features\TicketManagement\Events;

use App\Features\TicketManagement\Models\Ticket;

class TicketCreated
{
    public function __construct(public Ticket $ticket) {}
}
```

### Job (se ejecuta después de 24h)

```php
// app/Features/TicketManagement/Jobs/EscalateTicketPriorityJob.php

namespace App\Features\TicketManagement\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Enums\TicketPriority;
use App\Features\TicketManagement\Enums\TicketStatus;

class EscalateTicketPriorityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function handle(): void
    {
        // Verificar que sigue siendo OPEN y sin respuesta
        $this->ticket->refresh();

        if ($this->ticket->status === TicketStatus::OPEN &&
            $this->ticket->first_response_at === null &&
            $this->ticket->priority !== TicketPriority::CRITICAL) {

            $this->ticket->update([
                'priority' => TicketPriority::HIGH,
            ]);
        }
    }
}
```

### Listener (dispatchea el job)

```php
// app/Features/TicketManagement/Listeners/DispatchEscalationJob.php

namespace App\Features\TicketManagement\Listeners;

use App\Features\TicketManagement\Events\TicketCreated;
use App\Features\TicketManagement\Jobs\EscalateTicketPriorityJob;

class DispatchEscalationJob
{
    public function handle(TicketCreated $event): void
    {
        // Dispatchear job que se ejecuta en 24 horas
        EscalateTicketPriorityJob::dispatch($event->ticket)
            ->delay(now()->addHours(24));
    }
}
```

### Registrar Listener

```php
// app/Providers/EventServiceProvider.php

protected $listen = [
    \App\Features\TicketManagement\Events\TicketCreated::class => [
        \App\Features\TicketManagement\Listeners\DispatchEscalationJob::class,
    ],
];
```

### Disparar evento en Service

```php
// app/Features/TicketManagement/Services/TicketService.php

public function create(array $data, User $user): Ticket
{
    $ticket = Ticket::create($data);

    // Disparar evento para auto-escalada
    event(new TicketCreated($ticket));

    return $ticket;
}
```

---

## 📧 Feature 3: Recordatorios

### Mail

```php
// app/Features/TicketManagement/Mail/TicketReminderMail.php

namespace App\Features\TicketManagement\Mail;

use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Envelope;

class TicketReminderMail extends Mailable
{
    public function __construct(
        public Ticket $ticket,
        public string $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recordatorio: Ticket #{$this->ticket->ticket_code}",
        );
    }

    public function content()
    {
        return view('app.emails.ticket-reminder', [
            'ticket' => $this->ticket,
            'message' => $this->message,
        ]);
    }
}
```

### Blade Email

```blade
<!-- resources/views/app/emails/ticket-reminder.blade.php -->
<x-mail::message>
# Recordatorio: Ticket {{ $ticket->ticket_code }}

Hola {{ $ticket->creator->name }},

{{ $message }}

**Detalles:**
- Categoría: {{ $ticket->category->name ?? 'N/A' }}
- Estado: {{ $ticket->status->value }}

<x-mail::button :url="route('tickets.show', $ticket)">
Ver Ticket
</x-mail::button>

Gracias,
{{ config('app.name') }} Support
</x-mail::message>
```

### Controller

```php
// app/Features/TicketManagement/Http/Controllers/TicketReminderController.php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Mail\TicketReminderMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TicketReminderController
{
    public function send(Request $request, Ticket $ticket)
    {
        // Autorizar
        $this->authorize('sendReminder', $ticket);

        // Validar
        $message = $request->get('message') ??
            '¿Hay algo más en lo que podamos ayudarte?';

        // Enviar email
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

### Policy

```php
// Agregar a app/Features/TicketManagement/Policies/TicketPolicy.php

public function sendReminder(User $user, Ticket $ticket): bool
{
    // Solo agentes y admins de la empresa
    return ($user->isAgent() || $user->isCompanyAdmin($ticket->company_id))
        && $user->hasCompanyContext($ticket->company_id);
}
```

### Ruta

```php
// routes/api.php

Route::post('/tickets/{ticket}/remind', [TicketReminderController::class, 'send'])
    ->middleware(['auth:api'])
    ->name('tickets.remind');
```

---

## 🏢 Feature 4: Áreas

### Model

```php
// app/Features/TicketManagement/Models/Area.php

namespace App\Features\TicketManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Area extends Model
{
    use HasUuids;

    protected $table = 'ticketing.areas';
    protected $fillable = ['company_id', 'name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Features\CompanyManagement\Models\Company::class);
    }
}
```

### Actualizar Company Model

```php
// app/Features/CompanyManagement/Models/Company.php

public function areas()
{
    return $this->hasMany(Area::class);
}

// Helper para check si áreas están habilitadas
public function hasAreasEnabled(): bool
{
    return data_get($this->settings, 'areas_enabled', false) === true;
}
```

### Actualizar Ticket Model

```php
// app/Features/TicketManagement/Models/Ticket.php

public function area(): BelongsTo
{
    return $this->belongsTo(Area::class);
}
```

### Service

```php
// app/Features/TicketManagement/Services/AreaService.php

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;

class AreaService
{
    public function create(Company $company, array $data): Area
    {
        return $company->areas()->create($data);
    }

    public function update(Area $area, array $data): Area
    {
        $area->update($data);
        return $area;
    }

    public function delete(Area $area): bool
    {
        return $area->delete();
    }

    public function list(Company $company)
    {
        return $company->areas()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
```

### Controller

```php
// app/Features/TicketManagement/Http/Controllers/AreaController.php

namespace App\Features\TicketManagement\Http\Controllers;

use App\Features\TicketManagement\Models\Area;
use App\Features\TicketManagement\Services\AreaService;
use Illuminate\Http\Request;

class AreaController
{
    public function __construct(private AreaService $service) {}

    public function index(Request $request)
    {
        $company = $request->user()->company;
        $areas = $this->service->list($company);

        return response()->json([
            'success' => true,
            'data' => $areas,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Area::class);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique_for_company',
            'description' => 'nullable|string',
        ]);

        $area = $this->service->create($request->user()->company, $validated);

        return response()->json([
            'success' => true,
            'data' => $area,
        ], 201);
    }

    public function update(Request $request, Area $area)
    {
        $this->authorize('update', $area);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $area = $this->service->update($area, $validated);

        return response()->json([
            'success' => true,
            'data' => $area,
        ]);
    }

    public function destroy(Request $request, Area $area)
    {
        $this->authorize('delete', $area);
        $this->service->delete($area);

        return response()->json(['success' => true]);
    }
}
```

### Rutas

```php
// routes/api.php

Route::apiResource('areas', AreaController::class)
    ->middleware(['auth:api']);
```

### Validación en Tickets

```php
// app/Features/TicketManagement/Http/Requests/StoreTicketRequest.php

public function rules(): array
{
    $company = $this->user()->company;

    $rules = [
        'title' => 'required|string',
        'description' => 'required|string',
        'priority' => 'sometimes|in:low,medium,high,critical',
        'category_id' => 'required|uuid|exists:categories,id',
    ];

    // Si empresa tiene áreas habilitadas, es requerido
    if ($company->hasAreasEnabled()) {
        $rules['area_id'] = 'required|uuid|exists:areas,id';
    } else {
        $rules['area_id'] = 'nullable|uuid|exists:areas,id';
    }

    return $rules;
}
```

### Habilitar/Deshabilitar Áreas (Admin)

```php
// En panel admin de settings de empresa
// Actualizar company.settings

$company->update([
    'settings' => array_merge(
        $company->settings ?? [],
        ['areas_enabled' => $request->boolean('areas_enabled')]
    )
]);
```

---

## 📋 Plan de Desarrollo

### Fase 1: Base (1 hora)

```
✅ Crear 2 migraciones (Prioridad + Áreas)
✅ Ejecutar migraciones
✅ Crear Enums (TicketPriority)
✅ Actualizar Modelos (Ticket, Company, Area)
✅ Tests básicos
```

### Fase 2: Features (1 hora)

```
✅ Prioridad: Validación + Response (10 min)
✅ Auto-Escalada: Event + Job + Listener (20 min)
✅ Recordatorios: Controller + Mail + Route (15 min)
✅ Áreas: Service + Controller + Routes (15 min)
```

### Fase 3: Testing (30 min)

```
✅ Tests unitarios
✅ Tests de integración
✅ Validar todas las features funcionan
```

### Fase 4: Deploy (15 min)

```
✅ Code review
✅ Merge a main
✅ Clear caches
```

---

## 🚀 Comandos para Ejecutar

```bash
# 1. Crear migraciones
docker compose exec app php artisan make:migration add_ticket_priority
docker compose exec app php artisan make:migration create_areas_table

# 2. Ejecutar migraciones
docker compose exec app php artisan migrate

# 3. Crear enums
mkdir -p app/Features/TicketManagement/Enums

# 4. Crear modelos, controllers, etc
docker compose exec app php artisan make:model Area
docker compose exec app php artisan make:controller AreaController

# 5. Tests
docker compose exec app php artisan test

# 6. Format
docker compose exec app ./vendor/bin/pint

# 7. Clear caches
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan route:clear
```

---

## ✅ Checklist Final

```
PRE-IMPLEMENTACIÓN:
☐ Backup de BD
☐ Revisar documento
☐ Ambiente test listo

MIGRACIONES:
☐ Migration prioridad creada
☐ Migration áreas creada
☐ Migraciones ejecutadas sin errores

CÓDIGO:
☐ Enums creados
☐ Modelos actualizados
☐ Services implementados
☐ Controllers creados
☐ Rutas registradas

TESTS:
☐ Todos los tests pasan
☐ Migraciones testeadas

FINALIZACIÓN:
☐ Code formatted (pint)
☐ Caches limpiados
☐ Ready for merge
```

---

## 📊 Resumen de Cambios

### Base de Datos
```
- 1 ENUM nuevo (ticket_priority)
- 1 tabla nueva (areas)
- 2 columnas nuevas (priority, area_id en tickets)
- 4 índices nuevos
- 2 migraciones
```

### Laravel
```
- 1 Enum: TicketPriority
- 2 Models: Area
- 1 Event: TicketCreated
- 1 Job: EscalateTicketPriorityJob
- 1 Listener: DispatchEscalationJob
- 1 Mail: TicketReminderMail
- 2 Controllers: AreaController, TicketReminderController
- 1 Service: AreaService
- 4 Rutas nuevas
- Actualizaciones: Ticket, Company, User models
```

### Tiempo Total
```
Migraciones:    10 min
Código:         60 min
Tests:          30 min
Deploy:         15 min
─────────────────────
TOTAL:          ~2 horas
```

---

**Documento preparado:** Noviembre 26, 2025
**Versión:** 3.0 (FINAL - Implementación Lista)
**Estado:** ✅ APROBADO - LISTO PARA INICIAR DESARROLLO
