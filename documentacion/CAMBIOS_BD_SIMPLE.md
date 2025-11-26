# Cambios en Base de Datos - VERSIÓN SIMPLE

**Documento simple y directo**
**Fecha:** Noviembre 26, 2025

---

## 🎯 Cambios Reales (Sin Overengineering)

### Feature 1: PRIORIDAD
**BD:** Mínimo

```sql
-- 1. Crear ENUM
CREATE TYPE ticketing.ticket_priority AS ENUM (
    'low', 'medium', 'high', 'critical'
);

-- 2. Agregar columna a tickets
ALTER TABLE ticketing.tickets
ADD COLUMN priority ticketing.ticket_priority DEFAULT 'medium' NOT NULL;

-- 3. Índices simples
CREATE INDEX idx_tickets_priority ON ticketing.tickets(priority)
WHERE priority IN ('high', 'critical');
```

**Eso es todo.**

---

### Feature 2: AUTO-ESCALADA (24h sin respuesta)
**BD:** NADA

```
El evento + listener se manejan en Laravel:

1. Ticket creado → TicketCreated event
2. Listener dispatchea ChangeTicketPriorityJob (delay 24 horas)
3. Después de 24h, el Job ejecuta:
   UPDATE tickets SET priority = 'high' WHERE id = ? AND status = 'open'

No necesita tabla de auditoría.
No necesita función PostgreSQL.
No necesita guardar escaladas.

Es una lógica simple en la aplicación.
```

---

### Feature 3: RECORDATORIOS
**BD:** NADA

```
Solo endpoint en Laravel que envía email:

POST /api/tickets/{ticket}/remind
{
  "message": "¿Algo más en lo que podamos ayudarte?"
}

↓

Endpoint manda email al usuario
↓

Listo. No guardar en BD.

Si después quieren auditoría, agregan:
  - Una columna en tickets: last_reminder_sent_at
  - Nada más.
```

---

### Feature 4: ÁREAS
**BD:** Una tabla simple

```sql
-- SOLO esta tabla
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

-- Índices básicos
CREATE INDEX idx_areas_company_id ON ticketing.areas(company_id);
CREATE INDEX idx_areas_is_active ON ticketing.areas(is_active);
```

**Eso es todo.**

Agent-areas mapping se implementa después si es necesario.

---

## 📊 Resumen REAL de Cambios

```
PRIORIDAD:
  ✓ 1 ENUM
  ✓ 1 columna en tickets (priority)
  ✓ 2 índices

AUTO-ESCALADA:
  ✓ 0 cambios en BD
  ✓ Lógica 100% en Laravel (Event + Job + Listener)

RECORDATORIOS:
  ✓ 0 cambios en BD
  ✓ Solo endpoint + Mail en Laravel

ÁREAS:
  ✓ 1 tabla nueva (areas)
  ✓ 2 índices

TOTAL:
  ✓ 1 ENUM
  ✓ 1 tabla nueva
  ✓ 1 columna nueva
  ✓ 4 índices
```

---

## 🔄 Migraciones Necesarias

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
        DB::statement("CREATE TYPE ticketing.ticket_priority AS ENUM ('low', 'medium', 'high', 'critical')");

        // Agregar columna
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->string('priority')
                ->default('medium')
                ->nullable(false)
                ->comment('Prioridad del ticket: low, medium, high, critical');
        });

        // Índices
        DB::statement('CREATE INDEX idx_tickets_priority ON ticketing.tickets(priority) WHERE priority IN (\'high\', \'critical\')');
    }

    public function down(): void
    {
        Schema::table('ticketing.tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_priority');
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
        Schema::create('ticketing.areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')
                ->references('id')
                ->on('business.companies')
                ->onDelete('cascade');

            $table->unique(['company_id', 'name']);

            $table->index('company_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing.areas');
    }
};
```

---

## 📝 Código en Laravel (No BD)

### Auto-Escalada: Event + Job

```php
// app/Features/TicketManagement/Events/TicketCreated.php
class TicketCreated
{
    public function __construct(public Ticket $ticket) {}
}

// app/Features/TicketManagement/Jobs/EscalateTicketPriorityJob.php
class EscalateTicketPriorityJob implements ShouldQueue
{
    public function handle()
    {
        $this->ticket->update([
            'priority' => TicketPriority::HIGH
        ]);
    }
}

// En TicketService::create()
event(new TicketCreated($ticket));

// En EventServiceProvider
protected $listen = [
    TicketCreated::class => [
        // Listener que dispatchea el job con delay 24h
        DispatchEscalationJob::class,
    ],
];

// El listener
class DispatchEscalationJob
{
    public function handle(TicketCreated $event)
    {
        if ($event->ticket->status->value === 'open') {
            EscalateTicketPriorityJob::dispatch($event->ticket)
                ->delay(now()->addHours(24));
        }
    }
}
```

---

### Recordatorios: Solo Endpoint

```php
// app/Features/TicketManagement/Http/Controllers/TicketReminderController.php
class TicketReminderController
{
    public function send(Request $request, Ticket $ticket)
    {
        $this->authorize('sendReminder', $ticket);

        $message = $request->get('message') ?? 'Recordatorio: ¿Hay algo más en lo que podamos ayudarte?';

        Mail::to($ticket->creator->email)->send(
            new TicketReminderMail($ticket, $message)
        );

        return response()->json([
            'success' => true,
            'message' => 'Recordatorio enviado'
        ]);
    }
}

// routes/api.php
Route::post('/tickets/{ticket}/remind', [TicketReminderController::class, 'send'])
    ->middleware(['auth:api']);
```

---

## ✅ Lo que REALMENTE cambia

```
BASE DE DATOS:
  ✓ +1 ENUM (ticket_priority)
  ✓ +1 columna (tickets.priority)
  ✓ +1 tabla (areas)
  ✓ +4 índices
  ✓ 2 migraciones simples (5 min cada una)

CÓDIGO LARAVEL:
  ✓ Event + Job para auto-escalada
  ✓ Endpoint para recordatorios
  ✓ Modelos actualizados
  ✓ Services actualizados
  ✓ No tablas raras
  ✓ No triggers complejos
  ✓ No funciones PostgreSQL
```

---

## 🚀 Duración Real

```
Migraciones:          10 minutos
Event + Job:          15 minutos
Recordatorios:        15 minutos
Modelos + Services:   20 minutos
Tests:                30 minutos

TOTAL: ~90 minutos (1.5 horas)
```

---

## 🎯 Próximos Pasos

1. ✅ Crear 2 migraciones (prioridad + áreas)
2. ✅ Implementar Event + Job para auto-escalada
3. ✅ Implementar endpoint recordatorios
4. ✅ Tests
5. 🔜 DESPUÉS: agent_areas si se necesita

---

**Documento preparado:** Noviembre 26, 2025
**Versión:** SIMPLE (sin overengineering)
**Estado:** Listo para implementación
