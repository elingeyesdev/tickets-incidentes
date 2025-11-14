# 🎯 PLAN DE IMPLEMENTACIÓN: TICKET MANAGEMENT BACKEND (RED → GREEN)

> **Feature**: Ticket Management
> **Tipo**: Backend Implementation (PHP/Laravel + PostgreSQL)
> **Tests TDD**: 383 tests RED → GREEN
> **Autenticación**: JWT Stateless
> **Base de Datos**: PostgreSQL con schemas y triggers
> **Versión**: 1.0
> **Fecha**: Noviembre 2025

---

## 📋 TABLA DE CONTENIDOS

1. [Análisis Inicial](#análisis-inicial)
2. [Arquitectura JWT Stateless](#arquitectura-jwt-stateless)
3. [Middlewares: Reutilización vs Creación Nueva](#middlewares-reutilización-vs-creación-nueva)
4. [Orden de Implementación](#orden-de-implementación)
5. [Fase 1: Base de Datos](#fase-1-base-de-datos)
6. [Fase 2: Modelos y Relaciones](#fase-2-modelos-y-relaciones)
7. [Fase 3: Enums](#fase-3-enums)
8. [Fase 4: Exceptions Personalizadas](#fase-4-exceptions-personalizadas)
9. [Fase 5: Factories (Seeders de Tests)](#fase-5-factories)
10. [Fase 6: Services (Lógica de Negocio)](#fase-6-services)
11. [Fase 7: Rules (Validaciones Personalizadas)](#fase-7-rules)
12. [Fase 8: Policies (Autorización)](#fase-8-policies)
13. [Fase 9: Resources (Transformadores)](#fase-9-resources)
14. [Fase 10: Requests (Validación de Inputs)](#fase-10-requests)
15. [Fase 11: Controllers](#fase-11-controllers)
16. [Fase 12: Routes](#fase-12-routes)
17. [Fase 13: Jobs (Auto-Close)](#fase-13-jobs)
18. [Fase 14: Events y Listeners](#fase-14-events-y-listeners)
19. [Fase 15: Observers](#fase-15-observers)
20. [Fase 16: Service Provider](#fase-16-service-provider)
21. [Checklist Final](#checklist-final)

---

## 📊 ANÁLISIS INICIAL

### Tests a Transformar de RED → GREEN

| Categoría | Archivos | Tests | Prioridad |
|-----------|----------|-------|-----------|
| **Unit Tests - Services** | 4 | 19 | ⭐⭐⭐⭐⭐ |
| **Unit Tests - Models** | 2 | 9 | ⭐⭐⭐⭐⭐ |
| **Unit Tests - Rules** | 2 | 8 | ⭐⭐⭐⭐ |
| **Unit Tests - Jobs** | 1 | 5 | ⭐⭐⭐ |
| **Feature Tests - Categories** | 4 | 26 | ⭐⭐⭐⭐⭐ |
| **Feature Tests - Tickets CRUD** | 5 | 70 | ⭐⭐⭐⭐⭐ |
| **Feature Tests - Tickets Actions** | 4 | 45 | ⭐⭐⭐⭐⭐ |
| **Feature Tests - Responses** | 4 | 48 | ⭐⭐⭐⭐⭐ |
| **Feature Tests - Attachments** | 5 | 37 | ⭐⭐⭐⭐ |
| **Feature Tests - Ratings** | 3 | 26 | ⭐⭐⭐ |
| **Feature Tests - Permissions** | 3 | 26 | ⭐⭐⭐⭐⭐ |
| **Integration Tests** | 3 | 19 | ⭐⭐⭐ |
| **TOTAL** | **40** | **338** | - |

### Estructura del Sistema

```
app/Features/TicketManagement/
├── Database/
│   ├── Factories/          # ✅ Ya creadas (Fase 5 completada)
│   ├── Migrations/         # ⭐ Prioridad 1
│   └── Seeders/            # Baja prioridad
│
├── Enums/                  # ⭐ Prioridad 2
│   ├── TicketStatus.php    # ✅ Ya existe
│   └── AuthorType.php      # ✅ Ya existe
│
├── Exceptions/             # ⭐ Prioridad 3
│   ├── TicketNotFoundException.php
│   ├── TicketNotEditableException.php
│   └── ...
│
├── Models/                 # ⭐ Prioridad 4
│   ├── Category.php
│   ├── Ticket.php
│   ├── TicketResponse.php
│   ├── TicketAttachment.php
│   └── TicketRating.php
│
├── Services/               # ⭐ Prioridad 5
│   ├── CategoryService.php
│   ├── TicketService.php
│   ├── TicketCodeGenerator.php
│   ├── ResponseService.php
│   ├── AttachmentService.php
│   └── RatingService.php
│
├── Rules/                  # ⭐ Prioridad 6
│   ├── ValidFileType.php
│   └── CanReopenTicket.php
│
├── Policies/               # ⭐ Prioridad 7
│   ├── CategoryPolicy.php  # ✅ Ya existe
│   ├── TicketPolicy.php
│   ├── ResponsePolicy.php
│   ├── AttachmentPolicy.php
│   └── RatingPolicy.php
│
├── Http/
│   ├── Resources/          # ⭐ Prioridad 8
│   ├── Requests/           # ⭐ Prioridad 9
│   └── Controllers/        # ⭐ Prioridad 10
│
├── Jobs/                   # ⭐ Prioridad 11
│   └── AutoCloseResolvedTicketsJob.php
│
├── Events/                 # ⭐ Prioridad 12
│   ├── TicketCreated.php
│   ├── TicketAssigned.php
│   └── ...
│
├── Listeners/              # ⭐ Prioridad 12
│   └── ...
│
├── Observers/              # ⭐ Prioridad 13
│   └── TicketObserver.php
│
└── TicketManagementServiceProvider.php  # ⭐ Prioridad 14
```

---

## 🔐 ARQUITECTURA JWT STATELESS

### Sistema Actual de Autenticación

El sistema **NO USA Laravel Sessions**, usa **JWT Stateless** puro:

#### 1. Middleware de Autenticación

**Archivo**: `app/Http/Middleware/AuthenticateJwt.php`

```php
// Extrae token del header: Authorization: Bearer <token>
// Valida usando TokenService
// Establece usuario en request y auth() helper
```

**Cómo funciona**:
1. Lee header `Authorization: Bearer {token}`
2. Llama a `TokenService->validateToken($token)`
3. Devuelve objeto `User` autenticado
4. Lo almacena en `$request->setUserResolver()` y `auth()->setUser()`

#### 2. JWTHelper - Acceso al Usuario Autenticado

**Archivo**: `app/Shared/Helpers/JWTHelper.php`

**Métodos clave**:

```php
// Obtener usuario autenticado
JWTHelper::getAuthenticatedUser(): User

// Verificar si está autenticado
JWTHelper::isAuthenticated(): bool

// Obtener ID del usuario
JWTHelper::getUserId(): string

// Verificar rol (desde DB o JWT)
JWTHelper::hasRole('COMPANY_ADMIN'): bool
JWTHelper::hasAnyRole(['AGENT', 'COMPANY_ADMIN']): bool

// Verificar rol SOLO desde JWT (stateless)
JWTHelper::hasRoleFromJWT('AGENT'): bool

// Obtener company_id desde JWT
JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN'): ?string
```

#### 3. Estructura del JWT Payload

```json
{
  "iss": "helpdesk-api",
  "aud": "helpdesk-frontend",
  "iat": 1699000000,
  "exp": 1699003600,
  "sub": "user-uuid-here",
  "user_id": "user-uuid-here",
  "email": "juan.perez@example.com",
  "session_id": "random-session-id",
  "roles": [
    {
      "code": "USER",
      "company_id": null
    },
    {
      "code": "AGENT",
      "company_id": "company-uuid-here"
    }
  ]
}
```

**IMPORTANTE**:
- `roles` es un ARRAY de objetos `{code, company_id}`
- Un usuario puede tener múltiples roles en diferentes empresas
- `company_id = null` para rol `USER` (no pertenece a ninguna empresa como empleado)

#### 4. TokenService - Generación y Validación

**Archivo**: `app/Features/Authentication/Services/TokenService.php`

**Responsabilidades**:
- Generar access tokens JWT
- Validar tokens (firma, expiración, blacklist)
- Generar refresh tokens
- Blacklist de tokens (logout inmediato)

---

## 🛡️ MIDDLEWARES: REUTILIZACIÓN VS CREACIÓN NUEVA

### Middlewares Existentes (REUTILIZAR ✅)

#### 1. `AuthenticateJwt` (app/Http/Middleware/AuthenticateJwt.php)

**Uso**:
```php
Route::middleware(['auth.jwt'])->group(function () {
    // Rutas protegidas
});
```

**Qué hace**:
- Valida JWT del header `Authorization: Bearer {token}`
- Establece usuario autenticado en request
- Lanza `AuthenticationException` si token inválido/expirado

**¿Necesito crear nuevo middleware?** ❌ NO. Reutilizar este.

---

#### 2. `EnsureUserHasRole` (app/Http/Middleware/EnsureUserHasRole.php)

**Uso**:
```php
Route::middleware(['role:COMPANY_ADMIN'])->group(function () {
    // Solo COMPANY_ADMIN
});

Route::middleware(['role:AGENT,COMPANY_ADMIN'])->group(function () {
    // AGENT o COMPANY_ADMIN
});
```

**Qué hace**:
- Verifica que usuario tenga uno de los roles especificados
- Hybrid approach: verifica JWT payload primero (stateless), luego DB
- Devuelve 403 si no tiene el rol

**¿Necesito crear `EnsureAgentRole`?** ❌ NO. Reutilizar `EnsureUserHasRole`.

---

#### 3. `EnsureCompanyOwnership` (app/Features/CompanyManagement/Http/Middleware/EnsureCompanyOwnership.php)

**Uso**:
```php
Route::get('/companies/{company}', [CompanyController::class, 'show'])
    ->middleware(['auth.jwt', 'company.ownership']);
```

**Qué hace**:
- Verifica que `COMPANY_ADMIN` solo pueda acceder a su propia empresa
- `PLATFORM_ADMIN` tiene acceso a todas las empresas
- Verifica route parameter `{company}`

**¿Puedo reutilizarlo para tickets?** ⚠️ NO directamente.

**Razón**:
- Este middleware trabaja con route parameter `{company}`
- Tickets usan route parameter `{code}` (ticket_code)
- Lógica de ownership es diferente (ticket owner vs company admin)

**Solución**:
- NO crear middleware `EnsureTicketOwner`
- Usar **Policies** para manejar ownership (más flexible y testeable)

---

### Middlewares a NO Crear ❌

Según el plan TDD, se sugería crear:
1. ❌ `EnsureTicketOwner.php` → **NO CREAR**. Usar **TicketPolicy** en su lugar.
2. ❌ `EnsureAgentRole.php` → **NO CREAR**. Usar `EnsureUserHasRole` existente.

**Razón**: Laravel Policies son más apropiadas para lógica de autorización compleja y específica por recurso.

---

### Stack de Middlewares para Ticket Management

```php
// Rutas de categorías (solo COMPANY_ADMIN puede crear/actualizar/eliminar)
Route::middleware(['auth.jwt', 'role:COMPANY_ADMIN'])->group(function () {
    Route::post('/tickets/categories', [CategoryController::class, 'store']);
    Route::put('/tickets/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/tickets/categories/{id}', [CategoryController::class, 'destroy']);
});

// Rutas de tickets (todos los roles autenticados)
Route::middleware(['auth.jwt'])->group(function () {
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{code}', [TicketController::class, 'show']);
    Route::post('/tickets', [TicketController::class, 'store']); // Policy: solo USER
    Route::put('/tickets/{code}', [TicketController::class, 'update']); // Policy: verifica ownership
    Route::delete('/tickets/{code}', [TicketController::class, 'destroy']); // Policy: solo COMPANY_ADMIN

    // Acciones de tickets
    Route::post('/tickets/{code}/resolve', [TicketActionController::class, 'resolve']); // Policy: solo AGENT/ADMIN
    Route::post('/tickets/{code}/close', [TicketActionController::class, 'close']); // Policy: verifica status
    Route::post('/tickets/{code}/reopen', [TicketActionController::class, 'reopen']); // Policy: verifica tiempo
    Route::post('/tickets/{code}/assign', [TicketActionController::class, 'assign']); // Policy: solo AGENT/ADMIN

    // Respuestas
    Route::get('/tickets/{code}/responses', [TicketResponseController::class, 'index']);
    Route::post('/tickets/{code}/responses', [TicketResponseController::class, 'store']); // Policy: ownership
    Route::put('/tickets/{code}/responses/{id}', [TicketResponseController::class, 'update']); // Policy: author + 30min
    Route::delete('/tickets/{code}/responses/{id}', [TicketResponseController::class, 'destroy']); // Policy: author + 30min

    // Adjuntos
    Route::get('/tickets/{code}/attachments', [TicketAttachmentController::class, 'index']);
    Route::post('/tickets/{code}/attachments', [TicketAttachmentController::class, 'store']); // Policy: ownership
    Route::delete('/tickets/{code}/attachments/{id}', [TicketAttachmentController::class, 'destroy']); // Policy: uploader + 30min

    // Calificaciones
    Route::get('/tickets/{code}/rating', [TicketRatingController::class, 'show']);
    Route::post('/tickets/{code}/rating', [TicketRatingController::class, 'store']); // Policy: solo USER, owner
    Route::put('/tickets/{code}/rating', [TicketRatingController::class, 'update']); // Policy: author + 24h
});
```

**Políticas aplicadas en Controllers**:
```php
// En TicketController
public function update(Request $request, string $code)
{
    $ticket = Ticket::where('ticket_code', $code)->firstOrFail();

    // ✅ Autorización con Policy
    $this->authorize('update', $ticket);

    // ... lógica
}
```

---

## 🎯 ORDEN DE IMPLEMENTACIÓN

### Principio: Bottom-Up (De Abajo Hacia Arriba)

**Razón**: Los tests son Feature Tests que esperan endpoints funcionando. Para que los endpoints funcionen, necesito:
1. Base de datos con tablas y triggers
2. Modelos Eloquent con relaciones
3. Servicios con lógica de negocio
4. Validaciones (Rules, Requests)
5. Autorización (Policies)
6. Controllers que orquestan todo
7. Routes que exponen endpoints

**NUNCA empezar por Controllers**. Empezar por la base.

---

### Orden Detallado

| Fase | Componente | Archivos | Razón | Tests que pasan |
|------|------------|----------|-------|-----------------|
| **1** | Migraciones + Triggers | 8 archivos | Sin BD, no hay modelos | 0 |
| **2** | Modelos + Relaciones | 5 archivos | Sin modelos, no hay servicios | 9 tests Unit (Models) |
| **3** | Enums | 0 (ya existen) | Validar que existan TicketStatus y AuthorType | 0 |
| **4** | Exceptions | 8 archivos | Servicios lanzan excepciones | 0 |
| **5** | Factories | 0 (ya creadas) | Seeders de tests | 0 |
| **6** | Services | 6 archivos | Lógica de negocio central | 19 tests Unit (Services) |
| **7** | Rules | 2 archivos | Validaciones personalizadas | 8 tests Unit (Rules) |
| **8** | Policies | 5 archivos | Autorización | 0 directamente, pero habilita Feature Tests |
| **9** | Resources | 8 archivos | Transformadores de respuestas | 0 |
| **10** | Requests | 13 archivos | Validación de inputs | 0 |
| **11** | Controllers | 7 archivos | Orquestadores | 281 tests Feature |
| **12** | Routes | 1 archivo | Exposición de endpoints | 0 |
| **13** | Jobs | 1 archivo | Auto-close job | 5 tests Unit (Jobs) |
| **14** | Events + Listeners | 10 archivos | Notificaciones y side effects | 0 |
| **15** | Observers | 1 archivo | Audit trail | 0 |
| **16** | Service Provider | 1 archivo | Registro de bindings | 0 |
| **TOTAL** | - | **75 archivos** | - | **322 tests** |

---

## 📦 FASE 1: BASE DE DATOS

### Objetivo
Crear todas las tablas, índices, constraints y triggers PostgreSQL necesarios.

### Archivos a Crear (8 migraciones)

#### 1.1. `2025_11_14_000001_create_ticketing_categories_table.php`

**Responsabilidad**: Tabla de categorías de tickets

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
        // Crear schema ticketing si no existe
        DB::statement('CREATE SCHEMA IF NOT EXISTS ticketing');

        Schema::create('ticketing.categories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('company_id');
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));

            // Foreign keys
            $table->foreign('company_id')
                  ->references('id')
                  ->on('business.companies')
                  ->onDelete('cascade');

            // Unique constraint: nombre único por empresa
            $table->unique(['company_id', 'name'], 'uq_company_category_name');

            // Índices para performance
            $table->index('company_id', 'idx_categories_company_id');
            $table->index('is_active', 'idx_categories_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing.categories');
    }
};
```

**Tests que pasan**:
- Ninguno aún (los tests requieren datos, no solo estructura)

---

#### 1.2. `2025_11_14_000002_create_ticketing_tickets_table.php`

**Responsabilidad**: Tabla principal de tickets

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
        Schema::create('ticketing.tickets', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('ticket_code', 50)->unique();
            $table->uuid('company_id');
            $table->uuid('category_id');
            $table->uuid('created_by_user_id');
            $table->uuid('owner_agent_id')->nullable();
            $table->string('title', 255);
            $table->text('initial_description');
            $table->string('status', 20)->default('open');
            $table->string('last_response_author_type', 20)->default('none');
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            $table->timestampTz('first_response_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('closed_at')->nullable();

            // Foreign keys
            $table->foreign('company_id')
                  ->references('id')
                  ->on('business.companies')
                  ->onDelete('cascade');

            $table->foreign('category_id')
                  ->references('id')
                  ->on('ticketing.categories')
                  ->onDelete('restrict');

            $table->foreign('created_by_user_id')
                  ->references('id')
                  ->on('auth.users')
                  ->onDelete('restrict');

            $table->foreign('owner_agent_id')
                  ->references('id')
                  ->on('auth.users')
                  ->onDelete('set null');

            // Check constraints
            DB::statement("
                ALTER TABLE ticketing.tickets
                ADD CONSTRAINT chk_status
                CHECK (status IN ('open', 'pending', 'resolved', 'closed'))
            ");

            DB::statement("
                ALTER TABLE ticketing.tickets
                ADD CONSTRAINT chk_last_response_author
                CHECK (last_response_author_type IN ('none', 'user', 'agent'))
            ");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing.tickets');
    }
};
```

**Tests que pasan**:
- Ninguno aún

---

#### 1.3. `2025_11_14_000003_create_ticketing_ticket_responses_table.php`

**Responsabilidad**: Tabla de respuestas a tickets

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
        Schema::create('ticketing.ticket_responses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('ticket_id');
            $table->uuid('response_id')->nullable(); // Para threading (no usado en MVP)
            $table->uuid('author_id');
            $table->string('author_type', 20);
            $table->text('response_content');
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));

            // Foreign keys
            $table->foreign('ticket_id')
                  ->references('id')
                  ->on('ticketing.tickets')
                  ->onDelete('cascade');

            $table->foreign('response_id')
                  ->references('id')
                  ->on('ticketing.ticket_responses')
                  ->onDelete('cascade');

            $table->foreign('author_id')
                  ->references('id')
                  ->on('auth.users')
                  ->onDelete('restrict');

            // Check constraint
            DB::statement("
                ALTER TABLE ticketing.ticket_responses
                ADD CONSTRAINT chk_author_type
                CHECK (author_type IN ('user', 'agent'))
            ");

            // Índices
            $table->index('ticket_id', 'idx_responses_ticket_id');
            $table->index('author_id', 'idx_responses_author_id');
            $table->index('created_at', 'idx_responses_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing.ticket_responses');
    }
};
```

**Tests que pasan**:
- Ninguno aún

---

#### 1.4. `2025_11_14_000004_create_ticketing_ticket_attachments_table.php`

**Responsabilidad**: Tabla de adjuntos (archivos)

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
        Schema::create('ticketing.ticket_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('ticket_id');
            $table->uuid('response_id')->nullable(); // NULL = adjunto directo al ticket
            $table->uuid('uploaded_by_user_id');
            $table->string('file_name', 255);
            $table->string('file_url', 500);
            $table->string('file_type', 100);
            $table->bigInteger('file_size_bytes');
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));

            // Foreign keys
            $table->foreign('ticket_id')
                  ->references('id')
                  ->on('ticketing.tickets')
                  ->onDelete('cascade');

            $table->foreign('response_id')
                  ->references('id')
                  ->on('ticketing.ticket_responses')
                  ->onDelete('cascade');

            $table->foreign('uploaded_by_user_id')
                  ->references('id')
                  ->on('auth.users')
                  ->onDelete('restrict');

            // Índices
            $table->index('ticket_id', 'idx_attachments_ticket_id');
            $table->index('response_id', 'idx_attachments_response_id');
            $table->index('uploaded_by_user_id', 'idx_attachments_uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing.ticket_attachments');
    }
};
```

**Tests que pasan**:
- Ninguno aún

---

#### 1.5. `2025_11_14_000005_create_ticketing_ticket_ratings_table.php`

**Responsabilidad**: Tabla de calificaciones

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
        Schema::create('ticketing.ticket_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('ticket_id')->unique(); // Solo UNA calificación por ticket
            $table->uuid('rated_by_user_id');
            $table->uuid('rated_agent_id'); // Snapshot histórico
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));

            // Foreign keys
            $table->foreign('ticket_id')
                  ->references('id')
                  ->on('ticketing.tickets')
                  ->onDelete('cascade');

            $table->foreign('rated_by_user_id')
                  ->references('id')
                  ->on('auth.users')
                  ->onDelete('restrict');

            $table->foreign('rated_agent_id')
                  ->references('id')
                  ->on('auth.users')
                  ->onDelete('restrict');

            // Check constraint: rating entre 1 y 5
            DB::statement("
                ALTER TABLE ticketing.ticket_ratings
                ADD CONSTRAINT chk_rating_range
                CHECK (rating BETWEEN 1 AND 5)
            ");

            // Índices
            $table->index('ticket_id', 'idx_ratings_ticket_id');
            $table->index('rated_agent_id', 'idx_ratings_rated_agent_id');
            $table->index('rating', 'idx_ratings_rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticketing.ticket_ratings');
    }
};
```

**Tests que pasan**:
- Ninguno aún

---

#### 1.6. `2025_11_14_000006_add_indexes_to_ticketing_tables.php`

**Responsabilidad**: Índices adicionales para performance

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Índices compuestos en tickets para queries frecuentes
        DB::statement('
            CREATE INDEX idx_tickets_status_owner
            ON ticketing.tickets(status, owner_agent_id)
        ');

        DB::statement('
            CREATE INDEX idx_tickets_created_at_desc
            ON ticketing.tickets(created_at DESC)
        ');

        DB::statement('
            CREATE INDEX idx_tickets_last_response_author
            ON ticketing.tickets(last_response_author_type)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS ticketing.idx_tickets_status_owner');
        DB::statement('DROP INDEX IF EXISTS ticketing.idx_tickets_created_at_desc');
        DB::statement('DROP INDEX IF EXISTS ticketing.idx_tickets_last_response_author');
    }
};
```

**Tests que pasan**:
- Mejora performance de Feature Tests (indirectamente)

---

#### 1.7. `2025_11_14_000007_create_assign_ticket_owner_trigger.php`

**Responsabilidad**: Trigger 1 - Auto-Assignment (OPEN → PENDING)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear función del trigger
        DB::statement("
            CREATE OR REPLACE FUNCTION ticketing.assign_ticket_owner()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Solo ejecutar si:
                -- 1. El autor es un agente (author_type = 'agent')
                -- 2. El ticket NO tiene owner asignado aún
                IF NEW.author_type = 'agent' THEN
                    UPDATE ticketing.tickets
                    SET
                        owner_agent_id = NEW.author_id,
                        first_response_at = CASE
                            WHEN first_response_at IS NULL THEN NOW()
                            ELSE first_response_at
                        END,
                        status = 'pending',
                        last_response_author_type = 'agent',
                        updated_at = NOW()
                    WHERE id = NEW.ticket_id
                    AND owner_agent_id IS NULL;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Crear trigger
        DB::statement("
            CREATE TRIGGER assign_ticket_owner_after_agent_response
            AFTER INSERT ON ticketing.ticket_responses
            FOR EACH ROW
            EXECUTE FUNCTION ticketing.assign_ticket_owner();
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS assign_ticket_owner_after_agent_response ON ticketing.ticket_responses');
        DB::statement('DROP FUNCTION IF EXISTS ticketing.assign_ticket_owner()');
    }
};
```

**Tests que pasan**:
- ✅ `test_first_agent_response_triggers_auto_assignment` (CreateResponseTest.php)
- ✅ `test_auto_assignment_only_happens_once` (CreateResponseTest.php)
- ✅ `test_first_agent_response_sets_first_response_at` (CreateResponseTest.php)
- ✅ Integration tests de auto-assignment

---

#### 1.8. `2025_11_14_000008_create_pending_to_open_trigger.php`

**Responsabilidad**: Trigger 2 - Status Change (PENDING → OPEN cuando cliente responde)

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear función del trigger
        DB::statement("
            CREATE OR REPLACE FUNCTION ticketing.change_pending_to_open()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Solo ejecutar si:
                -- 1. El autor es un usuario (author_type = 'user')
                -- 2. El ticket está en estado 'pending'
                IF NEW.author_type = 'user' THEN
                    UPDATE ticketing.tickets
                    SET
                        status = 'open',
                        last_response_author_type = 'user',
                        updated_at = NOW()
                    WHERE id = NEW.ticket_id
                    AND status = 'pending';
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Crear trigger
        DB::statement("
            CREATE TRIGGER change_pending_to_open_after_user_response
            AFTER INSERT ON ticketing.ticket_responses
            FOR EACH ROW
            EXECUTE FUNCTION ticketing.change_pending_to_open();
        ");

        // Crear función del trigger para actualizar last_response_author_type
        DB::statement("
            CREATE OR REPLACE FUNCTION ticketing.update_last_response_author()
            RETURNS TRIGGER AS $$
            BEGIN
                UPDATE ticketing.tickets
                SET
                    last_response_author_type = NEW.author_type,
                    updated_at = NOW()
                WHERE id = NEW.ticket_id;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        // Crear trigger para actualizar last_response_author_type
        DB::statement("
            CREATE TRIGGER update_last_response_author_type
            AFTER INSERT ON ticketing.ticket_responses
            FOR EACH ROW
            EXECUTE FUNCTION ticketing.update_last_response_author();
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS change_pending_to_open_after_user_response ON ticketing.ticket_responses');
        DB::statement('DROP FUNCTION IF EXISTS ticketing.change_pending_to_open()');
        DB::statement('DROP TRIGGER IF EXISTS update_last_response_author_type ON ticketing.ticket_responses');
        DB::statement('DROP FUNCTION IF EXISTS ticketing.update_last_response_author()');
    }
};
```

**Tests que pasan**:
- ✅ `test_user_response_to_pending_ticket_changes_status_to_open` (CreateResponseTest.php) ⭐
- ✅ `test_user_response_to_pending_ticket_updates_last_response_author_type_to_user` (CreateResponseTest.php)
- ✅ `test_pending_to_open_transition_preserves_owner_agent_id` (CreateResponseTest.php)
- ✅ Todos los tests de last_response_author_type

---

### Ejecutar Migraciones

```bash
docker compose exec helpdesk-app php artisan migrate
```

**Tests que deberían pasar después de Fase 1**:
- 0 tests directamente (las migraciones solo crean estructura)
- Pero habilitan que los modelos y factories funcionen

---

## 🏗️ FASE 2: MODELOS Y RELACIONES

### Objetivo
Crear modelos Eloquent con todas sus relaciones, casts, scopes y accesorios.

### Archivos a Crear (5 modelos)

#### 2.1. `app/Features/TicketManagement/Models/Category.php`

**Responsabilidad**: Modelo de categorías

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Models;

use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Features\CompanyManagement\Models\Company;

/**
 * Category Model
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
class Category extends Model
{
    use HasFactory;
    use HasUuid;

    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\CategoryFactory::new();
    }

    protected $table = 'ticketing.categories';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    /**
     * Empresa a la que pertenece la categoría
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Tickets que usan esta categoría
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: solo categorías activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: categorías de una empresa específica
     */
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // ==================== MÉTODOS ====================

    /**
     * Obtener cantidad de tickets activos (open, pending) en esta categoría
     */
    public function getActiveTicketsCountAttribute(): int
    {
        return $this->tickets()
            ->whereIn('status', ['open', 'pending'])
            ->count();
    }

    /**
     * Verificar si la categoría puede ser eliminada
     * (solo si NO tiene tickets activos)
     */
    public function canBeDeleted(): bool
    {
        return $this->active_tickets_count === 0;
    }
}
```

**Tests que pasan**:
- Ninguno aún (requiere que otros componentes estén listos)

---

#### 2.2. `app/Features/TicketManagement/Models/Ticket.php`

**Responsabilidad**: Modelo principal de tickets

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Models;

use App\Features\CompanyManagement\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Ticket Model
 *
 * @property string $id
 * @property string $ticket_code
 * @property string $company_id
 * @property string $category_id
 * @property string $created_by_user_id
 * @property string|null $owner_agent_id
 * @property string $title
 * @property string $initial_description
 * @property TicketStatus $status
 * @property string $last_response_author_type
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime|null $first_response_at
 * @property \DateTime|null $resolved_at
 * @property \DateTime|null $closed_at
 *
 * @property-read Company $company
 * @property-read Category $category
 * @property-read User $creator
 * @property-read User|null $ownerAgent
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketResponse> $responses
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketAttachment> $attachments
 * @property-read TicketRating|null $rating
 */
class Ticket extends Model
{
    use HasFactory;
    use HasUuid;

    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\TicketFactory::new();
    }

    protected $table = 'ticketing.tickets';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ticket_code',
        'company_id',
        'category_id',
        'created_by_user_id',
        'owner_agent_id',
        'title',
        'initial_description',
        'status',
        'last_response_author_type',
        'first_response_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function ownerAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_agent_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(TicketResponse::class, 'ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(TicketRating::class, 'ticket_id');
    }

    // ==================== SCOPES ====================

    public function scopeOpen($query)
    {
        return $query->where('status', TicketStatus::OPEN);
    }

    public function scopePending($query)
    {
        return $query->where('status', TicketStatus::PENDING);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', TicketStatus::RESOLVED);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', TicketStatus::CLOSED);
    }

    public function scopeForCompany($query, string $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForUser($query, string $userId)
    {
        return $query->where('created_by_user_id', $userId);
    }

    public function scopeAssignedTo($query, ?string $agentId)
    {
        if ($agentId === null || $agentId === 'null') {
            return $query->whereNull('owner_agent_id');
        }

        return $query->where('owner_agent_id', $agentId);
    }

    // ==================== ACCESORIOS ====================

    /**
     * Código formateado del ticket (ej: TKT-2025-00001)
     */
    public function getFormattedCodeAttribute(): string
    {
        return $this->ticket_code;
    }

    /**
     * Verificar si el ticket está abierto
     */
    public function isOpen(): bool
    {
        return $this->status === TicketStatus::OPEN;
    }

    /**
     * Verificar si el ticket está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === TicketStatus::PENDING;
    }

    /**
     * Verificar si el ticket está resuelto
     */
    public function isResolved(): bool
    {
        return $this->status === TicketStatus::RESOLVED;
    }

    /**
     * Verificar si el ticket está cerrado
     */
    public function isClosed(): bool
    {
        return $this->status === TicketStatus::CLOSED;
    }

    /**
     * Verificar si el ticket tiene agente asignado
     */
    public function hasOwner(): bool
    {
        return $this->owner_agent_id !== null;
    }
}
```

**Tests que pasan después de crear este modelo**:
- ✅ `test_status_casts_to_enum` (TicketTest.php)
- ✅ `test_belongs_to_creator` (TicketTest.php)
- ✅ `test_belongs_to_owner_agent` (TicketTest.php)
- ✅ `test_belongs_to_company` (TicketTest.php)
- ✅ `test_belongs_to_category` (TicketTest.php)
- ✅ `test_has_many_responses` (TicketTest.php)
- ✅ `test_open_scope` (TicketTest.php)
- ✅ `test_pending_scope` (TicketTest.php)
- ✅ `test_casts_last_response_author_type_as_string` (TicketFieldsTest.php)

**Total: 9 tests Unit (Models)** ✅

---

#### 2.3. `app/Features/TicketManagement/Models/TicketResponse.php`

**Responsabilidad**: Modelo de respuestas

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Models;

use App\Features\TicketManagement\Enums\AuthorType;
use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * TicketResponse Model
 *
 * @property string $id
 * @property string $ticket_id
 * @property string|null $response_id
 * @property string $author_id
 * @property AuthorType $author_type
 * @property string $response_content
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read Ticket $ticket
 * @property-read User $author
 * @property-read TicketResponse|null $parentResponse
 * @property-read \Illuminate\Database\Eloquent\Collection<TicketAttachment> $attachments
 */
class TicketResponse extends Model
{
    use HasFactory;
    use HasUuid;

    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\TicketResponseFactory::new();
    }

    protected $table = 'ticketing.ticket_responses';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ticket_id',
        'response_id',
        'author_id',
        'author_type',
        'response_content',
    ];

    protected $casts = [
        'author_type' => AuthorType::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parentResponse(): BelongsTo
    {
        return $this->belongsTo(TicketResponse::class, 'response_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'response_id');
    }

    // ==================== MÉTODOS ====================

    /**
     * Verificar si la respuesta puede ser editada
     * (dentro de 30 minutos de creación)
     */
    public function canBeEdited(): bool
    {
        return $this->created_at->diffInMinutes(now()) <= 30;
    }

    /**
     * Verificar si la respuesta puede ser eliminada
     * (dentro de 30 minutos de creación)
     */
    public function canBeDeleted(): bool
    {
        return $this->created_at->diffInMinutes(now()) <= 30;
    }

    /**
     * Verificar si el autor es un usuario
     */
    public function isFromUser(): bool
    {
        return $this->author_type === AuthorType::USER;
    }

    /**
     * Verificar si el autor es un agente
     */
    public function isFromAgent(): bool
    {
        return $this->author_type === AuthorType::AGENT;
    }
}
```

**Tests que pasan**:
- Ninguno directamente (los tests de responses requieren servicios)

---

#### 2.4. `app/Features/TicketManagement/Models/TicketAttachment.php`

**Responsabilidad**: Modelo de adjuntos

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TicketAttachment Model
 *
 * @property string $id
 * @property string $ticket_id
 * @property string|null $response_id
 * @property string $uploaded_by_user_id
 * @property string $file_name
 * @property string $file_url
 * @property string $file_type
 * @property int $file_size_bytes
 * @property \DateTime $created_at
 *
 * @property-read Ticket $ticket
 * @property-read TicketResponse|null $response
 * @property-read User $uploader
 */
class TicketAttachment extends Model
{
    use HasFactory;
    use HasUuid;

    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\TicketAttachmentFactory::new();
    }

    protected $table = 'ticketing.ticket_attachments';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false; // Solo created_at, no updated_at

    protected $fillable = [
        'ticket_id',
        'response_id',
        'uploaded_by_user_id',
        'file_name',
        'file_url',
        'file_type',
        'file_size_bytes',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'created_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(TicketResponse::class, 'response_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    // ==================== MÉTODOS ====================

    /**
     * Verificar si el adjunto puede ser eliminado
     * (dentro de 30 minutos de creación)
     */
    public function canBeDeleted(): bool
    {
        return $this->created_at->diffInMinutes(now()) <= 30;
    }

    /**
     * Obtener tamaño del archivo en MB
     */
    public function getFileSizeMbAttribute(): float
    {
        return round($this->file_size_bytes / 1048576, 2); // 1024 * 1024
    }

    /**
     * Verificar si el adjunto pertenece a una respuesta
     */
    public function belongsToResponse(): bool
    {
        return $this->response_id !== null;
    }
}
```

**Tests que pasan**:
- Ninguno directamente

---

#### 2.5. `app/Features/TicketManagement/Models/TicketRating.php`

**Responsabilidad**: Modelo de calificaciones

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Models;

use App\Features\UserManagement\Models\User;
use App\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TicketRating Model
 *
 * @property string $id
 * @property string $ticket_id
 * @property string $rated_by_user_id
 * @property string $rated_agent_id
 * @property int $rating
 * @property string|null $comment
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 *
 * @property-read Ticket $ticket
 * @property-read User $ratedBy
 * @property-read User $ratedAgent
 */
class TicketRating extends Model
{
    use HasFactory;
    use HasUuid;

    protected static function newFactory()
    {
        return \App\Features\TicketManagement\Database\Factories\TicketRatingFactory::new();
    }

    protected $table = 'ticketing.ticket_ratings';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ticket_id',
        'rated_by_user_id',
        'rated_agent_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELACIONES ====================

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function ratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by_user_id');
    }

    public function ratedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_agent_id');
    }

    // ==================== MÉTODOS ====================

    /**
     * Verificar si la calificación puede ser actualizada
     * (dentro de 24 horas de creación)
     */
    public function canBeUpdated(): bool
    {
        return $this->created_at->diffInHours(now()) <= 24;
    }
}
```

**Tests que pasan**:
- Ninguno directamente

---

### Tests que pasan después de Fase 2

- ✅ **9 tests Unit (Models)** → TicketTest.php, TicketFieldsTest.php

---

## 🔢 FASE 3: ENUMS

### Objetivo
Verificar que los Enums necesarios ya existan o crearlos si faltan.

### Enums Necesarios

#### 3.1. `app/Features/TicketManagement/Enums/TicketStatus.php`

**Status**: ✅ **YA EXISTE** (según Glob output)

**Contenido esperado**:
```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Enums;

enum TicketStatus: string
{
    case OPEN = 'open';
    case PENDING = 'pending';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
}
```

---

#### 3.2. `app/Features/TicketManagement/Enums/AuthorType.php`

**Status**: ✅ **YA EXISTE** (según Glob output)

**Contenido esperado**:
```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Enums;

enum AuthorType: string
{
    case USER = 'user';
    case AGENT = 'agent';
}
```

---

### Acción Necesaria

✅ **Verificar que ambos Enums existan y tengan los valores correctos**

Si no existen, crearlos con el contenido de arriba.

### Tests que pasan después de Fase 3

- Ninguno directamente (los Enums son dependencias de otros componentes)

---

## ⚠️ FASE 4: EXCEPTIONS PERSONALIZADAS

### Objetivo
Crear excepciones específicas del dominio para mejor manejo de errores.

### Archivos a Crear (8 excepciones)

#### 4.1. `app/Features/TicketManagement/Exceptions/TicketNotFoundException.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Exceptions;

use Exception;

class TicketNotFoundException extends Exception
{
    public function __construct(string $ticketCode)
    {
        parent::__construct("Ticket '{$ticketCode}' not found", 404);
    }
}
```

---

#### 4.2. `app/Features/TicketManagement/Exceptions/TicketNotEditableException.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Exceptions;

use Exception;

class TicketNotEditableException extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct("Ticket cannot be edited: {$reason}", 403);
    }
}
```

---

#### 4.3. `app/Features/TicketManagement/Exceptions/ResponseNotEditableException.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Exceptions;

use Exception;

class ResponseNotEditableException extends Exception
{
    public static function timeExceeded(int $minutes): self
    {
        return new self("Response cannot be edited after {$minutes} minutes", 403);
    }

    public static function ticketClosed(): self
    {
        return new self("Cannot edit response on a closed ticket", 403);
    }

    public static function notAuthor(): self
    {
        return new self("You can only edit your own responses", 403);
    }
}
```

---

#### 4.4. `app/Features/TicketManagement/Exceptions/CategoryInUseException.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Exceptions;

use Exception;

class CategoryInUseException extends Exception
{
    public function __construct(int $activeTicketsCount)
    {
        parent::__construct(
            "Cannot delete category because it has {$activeTicketsCount} active tickets",
            409
        );
    }
}
```

---

#### 4.5. `app/Features/TicketManagement/Exceptions/CannotReopenTicketException.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Exceptions;

use Exception;

class CannotReopenTicketException extends Exception
{
    public static function timeExceeded(int $days): self
    {
        return new self(
            "Cannot reopen ticket after {$days} days since closure",
            403
        );
    }

    public static function invalidStatus(string $currentStatus): self
    {
        return new self(
            "Cannot reopen ticket with status '{$currentStatus}'. Only 'resolved' or 'closed' can be reopened",
            400
        );
    }
}
```

---

#### 4.6. `app/Features/TicketManagement/Exceptions/RatingAlreadyExistsException.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Exceptions;

use Exception;

class RatingAlreadyExistsException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            "This ticket already has a rating. You can update it using PUT method",
            409
        );
    }
}
```

---

#### 4.7. `app/Features/TicketManagement/Exceptions/FileUploadException.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Exceptions;

use Exception;

class FileUploadException extends Exception
{
    public static function sizeTooLarge(float $sizeMb, float $maxMb): self
    {
        return new self(
            "File size ({$sizeMb} MB) exceeds maximum allowed size ({$maxMb} MB)",
            413
        );
    }

    public static function invalidType(string $type, array $allowedTypes): self
    {
        $allowed = implode(', ', $allowedTypes);
        return new self(
            "File type '{$type}' is not allowed. Allowed types: {$allowed}",
            422
        );
    }

    public static function maxAttachmentsExceeded(int $current, int $max): self
    {
        return new self(
            "Maximum number of attachments ({$max}) exceeded. Current: {$current}",
            422
        );
    }
}
```

---

#### 4.8. `app/Features/TicketManagement/Exceptions/NotTicketOwnerException.php`

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Exceptions;

use Exception;

class NotTicketOwnerException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            "You do not have permission to access this ticket",
            403
        );
    }
}
```

---

### Tests que pasan después de Fase 4

- Ninguno directamente (las excepciones son lanzadas por servicios)

---

## 🏭 FASE 5: FACTORIES (Ya completadas)

### Status

✅ **YA COMPLETADAS** en sesiones anteriores:
- CategoryFactory.php
- TicketFactory.php
- TicketResponseFactory.php
- TicketAttachmentFactory.php
- TicketRatingFactory.php

### Tests que pasan

- ✅ `test_factory_creates_ticket_with_default_last_response_author_type` (TicketFieldsTest.php)

---

## ⚙️ FASE 6: SERVICES (Lógica de Negocio)

### Objetivo
Implementar toda la lógica de negocio central del feature.

### Orden de Implementación

1. TicketCodeGenerator (no tiene dependencias)
2. CategoryService (usa Category model)
3. TicketService (usa TicketCodeGenerator)
4. ResponseService (usa Ticket model)
5. AttachmentService (usa Storage y Ticket model)
6. RatingService (usa Ticket model)

---

### 6.1. `app/Features/TicketManagement/Services/TicketCodeGenerator.php`

**Responsabilidad**: Generar códigos de ticket secuenciales por año

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Models\Ticket;

/**
 * TicketCodeGenerator
 *
 * Genera códigos únicos de tickets en formato: TKT-YYYY-NNNNN
 * Secuencia se resetea cada año.
 */
class TicketCodeGenerator
{
    /**
     * Generar nuevo código de ticket para el año actual
     *
     * Formato: TKT-2025-00001, TKT-2025-00002, etc.
     */
    public function generate(): string
    {
        $year = now()->year;

        // Obtener último ticket del año
        $lastTicket = Ticket::where('ticket_code', 'LIKE', "TKT-{$year}-%")
            ->orderBy('ticket_code', 'desc')
            ->lockForUpdate() // Evitar race conditions
            ->first();

        if ($lastTicket) {
            // Extraer número y sumar 1
            $lastNumber = (int) substr($lastTicket->ticket_code, -5);
            $newNumber = $lastNumber + 1;
        } else {
            // Primer ticket del año
            $newNumber = 1;
        }

        // Formatear con padding de 5 dígitos
        return sprintf('TKT-%d-%05d', $year, $newNumber);
    }
}
```

**Tests que pasan**:
- ✅ `test_generates_unique_ticket_codes` (TicketServiceTest.php)

---

### 6.2. `app/Features/TicketManagement/Services/CategoryService.php`

**Responsabilidad**: Lógica de negocio para categorías

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Exceptions\CategoryInUseException;
use App\Features\TicketManagement\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CategoryService
 *
 * Maneja la lógica de negocio de categorías.
 */
class CategoryService
{
    /**
     * Listar categorías de una empresa
     */
    public function list(string $companyId, ?bool $isActive = null): Collection
    {
        $query = Category::forCompany($companyId)
            ->withCount(['tickets' => function ($q) {
                $q->whereIn('status', ['open', 'pending']);
            }]);

        if ($isActive !== null) {
            $query->where('is_active', $isActive);
        }

        return $query->get();
    }

    /**
     * Crear categoría
     */
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Actualizar categoría
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh();
    }

    /**
     * Eliminar categoría
     *
     * @throws CategoryInUseException si tiene tickets activos
     */
    public function delete(Category $category): void
    {
        $activeTicketsCount = $category->active_tickets_count;

        if ($activeTicketsCount > 0) {
            throw new CategoryInUseException($activeTicketsCount);
        }

        $category->delete();
    }
}
```

**Tests que pasan**:
- Feature Tests de categorías (indirectamente)

---

### 6.3. `app/Features/TicketManagement/Services/TicketService.php`

**Responsabilidad**: Lógica de negocio principal de tickets

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Exceptions\TicketNotFoundException;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * TicketService
 *
 * Maneja la lógica de negocio de tickets.
 */
class TicketService
{
    public function __construct(
        protected TicketCodeGenerator $codeGenerator
    ) {}

    /**
     * Listar tickets con filtros
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        $query = Ticket::query()
            ->with(['creator', 'ownerAgent', 'category', 'company'])
            ->withCount(['responses', 'attachments']);

        // Filtro de visibilidad por rol
        $this->applyVisibilityFilters($query, $user);

        // Filtros adicionales
        $this->applyFilters($query, $filters);

        // Ordenamiento
        $sortBy = $filters['sort'] ?? '-created_at';
        $this->applySorting($query, $sortBy);

        // Paginación
        $perPage = min($filters['per_page'] ?? 20, 100);

        return $query->paginate($perPage);
    }

    /**
     * Obtener ticket por código
     */
    public function getByCode(string $code): Ticket
    {
        $ticket = Ticket::where('ticket_code', $code)
            ->with(['creator', 'ownerAgent', 'category', 'company', 'rating'])
            ->withCount(['responses', 'attachments'])
            ->first();

        if (!$ticket) {
            throw new TicketNotFoundException($code);
        }

        return $ticket;
    }

    /**
     * Crear ticket
     */
    public function create(array $data, User $user): Ticket
    {
        // Generar código único
        $data['ticket_code'] = $this->codeGenerator->generate();

        // Usuario que crea
        $data['created_by_user_id'] = $user->id;

        // Estado inicial
        $data['status'] = TicketStatus::OPEN->value;
        $data['last_response_author_type'] = 'none';

        return DB::transaction(function () use ($data) {
            return Ticket::create($data);
        });
    }

    /**
     * Actualizar ticket
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);

        return $ticket->fresh();
    }

    /**
     * Eliminar ticket
     *
     * Solo si está cerrado
     */
    public function delete(Ticket $ticket): void
    {
        if (!$ticket->isClosed()) {
            throw new \Exception("Only closed tickets can be deleted", 400);
        }

        DB::transaction(function () use ($ticket) {
            // Eliminar archivos físicos de adjuntos
            foreach ($ticket->attachments as $attachment) {
                \Storage::delete($attachment->file_url);
            }

            // Cascade elimina responses, attachments, rating
            $ticket->delete();
        });
    }

    /**
     * Resolver ticket
     */
    public function resolve(Ticket $ticket, ?string $note = null): Ticket
    {
        if ($ticket->isResolved() || $ticket->isClosed()) {
            throw new \Exception("Ticket is already resolved or closed", 400);
        }

        $ticket->update([
            'status' => TicketStatus::RESOLVED->value,
            'resolved_at' => now(),
        ]);

        return $ticket->fresh();
    }

    /**
     * Cerrar ticket
     */
    public function close(Ticket $ticket, ?string $note = null): Ticket
    {
        if ($ticket->isClosed()) {
            throw new \Exception("Ticket is already closed", 400);
        }

        $ticket->update([
            'status' => TicketStatus::CLOSED->value,
            'closed_at' => now(),
        ]);

        return $ticket->fresh();
    }

    /**
     * Reabrir ticket
     */
    public function reopen(Ticket $ticket, User $user, ?string $reason = null): Ticket
    {
        if ($ticket->isOpen() || $ticket->isPending()) {
            throw new \Exception("Ticket is not resolved or closed", 400);
        }

        // Validar ventana de tiempo para USER
        if ($user->hasRole('USER') && $ticket->isClosed()) {
            $daysSinceClosed = $ticket->closed_at->diffInDays(now());
            if ($daysSinceClosed > 30) {
                throw new \Exception("Cannot reopen ticket after 30 days", 403);
            }
        }

        $ticket->update([
            'status' => TicketStatus::PENDING->value,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        return $ticket->fresh();
    }

    /**
     * Reasignar ticket a otro agente
     */
    public function assign(Ticket $ticket, string $newAgentId, ?string $note = null): Ticket
    {
        $ticket->update([
            'owner_agent_id' => $newAgentId,
        ]);

        return $ticket->fresh();
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Aplicar filtros de visibilidad según rol
     */
    protected function applyVisibilityFilters(Builder $query, User $user): void
    {
        // USER: solo ve sus propios tickets
        if ($user->hasRole('USER')) {
            $query->where('created_by_user_id', $user->id);
            return;
        }

        // AGENT/COMPANY_ADMIN: ven todos los tickets de su empresa
        if ($user->hasRole('AGENT') || $user->hasRole('COMPANY_ADMIN')) {
            $companyId = \App\Shared\Helpers\JWTHelper::getCompanyIdFromJWT('AGENT')
                ?? \App\Shared\Helpers\JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            if ($companyId) {
                $query->where('company_id', $companyId);
            }
        }
    }

    /**
     * Aplicar filtros adicionales
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        // Filtro por status
        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status'])
                ? $filters['status']
                : explode(',', $filters['status']);

            $query->whereIn('status', $statuses);
        }

        // Filtro por categoría
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtro por owner_agent_id
        if (isset($filters['owner_agent_id'])) {
            if ($filters['owner_agent_id'] === 'null') {
                $query->whereNull('owner_agent_id');
            } elseif ($filters['owner_agent_id'] === 'me') {
                $query->where('owner_agent_id', auth()->id());
            } else {
                $query->where('owner_agent_id', $filters['owner_agent_id']);
            }
        }

        // Filtro por created_by
        if (!empty($filters['created_by'])) {
            if ($filters['created_by'] === 'me') {
                $query->where('created_by_user_id', auth()->id());
            } else {
                $query->where('created_by_user_id', $filters['created_by']);
            }
        }

        // Filtro por last_response_author_type
        if (!empty($filters['last_response_author_type'])) {
            $query->where('last_response_author_type', $filters['last_response_author_type']);
        }

        // Búsqueda en título y descripción
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('initial_description', 'ILIKE', "%{$search}%");
            });
        }

        // Filtro por rango de fechas
        if (!empty($filters['created_after'])) {
            $query->where('created_at', '>=', $filters['created_after']);
        }

        if (!empty($filters['created_before'])) {
            $query->where('created_at', '<=', $filters['created_before']);
        }
    }

    /**
     * Aplicar ordenamiento
     */
    protected function applySorting(Builder $query, string $sortBy): void
    {
        // Formato: -created_at (desc) o created_at (asc)
        $direction = str_starts_with($sortBy, '-') ? 'desc' : 'asc';
        $field = ltrim($sortBy, '-');

        $query->orderBy($field, $direction);
    }
}
```

**Tests que pasan**:
- ✅ `test_validates_company_exists` (TicketServiceTest.php)
- ✅ `test_filters_tickets_by_owner_for_users` (TicketServiceTest.php)
- ✅ `test_delete_only_allows_closed_tickets` (TicketServiceTest.php)
- ✅ Muchos Feature Tests de tickets CRUD

---

### 6.4. `app/Features/TicketManagement/Services/ResponseService.php`

**Responsabilidad**: Lógica de respuestas

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Enums\AuthorType;
use App\Features\TicketManagement\Exceptions\ResponseNotEditableException;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ResponseService
 *
 * Maneja la lógica de respuestas a tickets.
 */
class ResponseService
{
    /**
     * Listar respuestas de un ticket
     */
    public function list(Ticket $ticket): Collection
    {
        return $ticket->responses()
            ->with(['author', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Crear respuesta
     *
     * Los triggers de PostgreSQL se encargan de:
     * - Auto-assignment si es primera respuesta de agente
     * - Cambio de status PENDING→OPEN si cliente responde
     * - Actualización de last_response_author_type
     */
    public function create(Ticket $ticket, array $data, User $user): TicketResponse
    {
        if ($ticket->isClosed()) {
            throw new \Exception("Cannot add response to closed ticket", 403);
        }

        // Determinar author_type según rol del usuario
        $data['author_type'] = $this->determineAuthorType($user);
        $data['author_id'] = $user->id;
        $data['ticket_id'] = $ticket->id;

        return DB::transaction(function () use ($data) {
            $response = TicketResponse::create($data);

            // Los triggers se ejecutan automáticamente después del INSERT

            return $response;
        });
    }

    /**
     * Actualizar respuesta
     *
     * Solo el autor puede editar, dentro de 30 minutos
     */
    public function update(TicketResponse $response, array $data, User $user): TicketResponse
    {
        // Validar que sea el autor
        if ($response->author_id !== $user->id) {
            throw ResponseNotEditableException::notAuthor();
        }

        // Validar ventana de tiempo
        if (!$response->canBeEdited()) {
            throw ResponseNotEditableException::timeExceeded(30);
        }

        // Validar que ticket no esté cerrado
        if ($response->ticket->isClosed()) {
            throw ResponseNotEditableException::ticketClosed();
        }

        $response->update($data);

        return $response->fresh();
    }

    /**
     * Eliminar respuesta
     */
    public function delete(TicketResponse $response, User $user): void
    {
        // Validar que sea el autor
        if ($response->author_id !== $user->id) {
            throw new \Exception("You can only delete your own responses", 403);
        }

        // Validar ventana de tiempo
        if (!$response->canBeDeleted()) {
            throw new \Exception("Cannot delete response after 30 minutes", 403);
        }

        // Validar que ticket no esté cerrado
        if ($response->ticket->isClosed()) {
            throw new \Exception("Cannot delete response on closed ticket", 403);
        }

        DB::transaction(function () use ($response) {
            // Eliminar archivos físicos de adjuntos
            foreach ($response->attachments as $attachment) {
                \Storage::delete($attachment->file_url);
            }

            // Cascade elimina attachments
            $response->delete();
        });
    }

    // ==================== MÉTODOS PRIVADOS ====================

    /**
     * Determinar author_type según rol del usuario
     */
    protected function determineAuthorType(User $user): string
    {
        if ($user->hasRole('AGENT') || $user->hasRole('COMPANY_ADMIN')) {
            return AuthorType::AGENT->value;
        }

        return AuthorType::USER->value;
    }
}
```

**Tests que pasan**:
- ✅ `test_determines_author_type_automatically` (ResponseServiceTest.php)
- ✅ `test_validates_auto_assignment_trigger_only_first_agent` (ResponseServiceTest.php)
- ✅ Feature Tests de responses

---

### 6.5. `app/Features/TicketManagement/Services/AttachmentService.php`

**Responsabilidad**: Lógica de adjuntos

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Exceptions\FileUploadException;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * AttachmentService
 *
 * Maneja la lógica de adjuntos (upload, validación, eliminación).
 */
class AttachmentService
{
    protected const MAX_FILE_SIZE_MB = 10;
    protected const MAX_ATTACHMENTS_PER_TICKET = 5;
    protected const ALLOWED_TYPES = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
        'txt', 'zip'
    ];

    /**
     * Subir adjunto a ticket
     */
    public function upload(
        Ticket $ticket,
        UploadedFile $file,
        User $user,
        ?string $responseId = null
    ): TicketAttachment {
        // Validaciones
        $this->validateFile($file);
        $this->validateMaxAttachments($ticket);

        if ($responseId) {
            $this->validateResponseAttachment($ticket, $responseId, $user);
        }

        // Guardar archivo
        $path = $this->storeFile($file);

        // Crear registro
        return TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => $responseId,
            'uploaded_by_user_id' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'file_url' => $path,
            'file_type' => $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
        ]);
    }

    /**
     * Eliminar adjunto
     */
    public function delete(TicketAttachment $attachment, User $user): void
    {
        // Validar que sea el uploader
        if ($attachment->uploaded_by_user_id !== $user->id) {
            throw new \Exception("You can only delete your own attachments", 403);
        }

        // Validar ventana de tiempo
        if (!$attachment->canBeDeleted()) {
            throw new \Exception("Cannot delete attachment after 30 minutes", 403);
        }

        // Validar que ticket no esté cerrado
        if ($attachment->ticket->isClosed()) {
            throw new \Exception("Cannot delete attachment on closed ticket", 403);
        }

        // Eliminar archivo físico
        Storage::delete($attachment->file_url);

        // Eliminar registro
        $attachment->delete();
    }

    // ==================== VALIDACIONES ====================

    /**
     * Validar archivo (tamaño y tipo)
     */
    protected function validateFile(UploadedFile $file): void
    {
        // Validar tamaño
        $sizeMb = $file->getSize() / 1048576;
        if ($sizeMb > self::MAX_FILE_SIZE_MB) {
            throw FileUploadException::sizeTooLarge($sizeMb, self::MAX_FILE_SIZE_MB);
        }

        // Validar tipo
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_TYPES)) {
            throw FileUploadException::invalidType($extension, self::ALLOWED_TYPES);
        }
    }

    /**
     * Validar límite de adjuntos por ticket
     */
    protected function validateMaxAttachments(Ticket $ticket): void
    {
        $currentCount = $ticket->attachments()->count();

        if ($currentCount >= self::MAX_ATTACHMENTS_PER_TICKET) {
            throw FileUploadException::maxAttachmentsExceeded(
                $currentCount,
                self::MAX_ATTACHMENTS_PER_TICKET
            );
        }
    }

    /**
     * Validar adjunto a respuesta específica
     */
    protected function validateResponseAttachment(
        Ticket $ticket,
        string $responseId,
        User $user
    ): void {
        $response = TicketResponse::find($responseId);

        if (!$response) {
            throw new \Exception("Response not found", 404);
        }

        // Verificar que la respuesta pertenece al ticket
        if ($response->ticket_id !== $ticket->id) {
            throw new \Exception("Response does not belong to this ticket", 422);
        }

        // Verificar que el usuario es el autor de la respuesta
        if ($response->author_id !== $user->id) {
            throw new \Exception("You can only upload attachments to your own responses", 403);
        }

        // Verificar ventana de 30 minutos
        if ($response->created_at->diffInMinutes(now()) > 30) {
            throw new \Exception("Cannot upload attachment to response after 30 minutes", 403);
        }
    }

    /**
     * Almacenar archivo en storage
     */
    protected function storeFile(UploadedFile $file): string
    {
        // Generar nombre único
        $extension = $file->getClientOriginalExtension();
        $filename = \Str::uuid() . '.' . $extension;

        // Guardar en storage/app/public/tickets/attachments/
        $path = $file->storeAs('tickets/attachments', $filename, 'public');

        return $path;
    }
}
```

**Tests que pasan**:
- ✅ `test_validates_file_size_max_10mb` (AttachmentServiceTest.php)
- ✅ `test_validates_allowed_file_types` (AttachmentServiceTest.php)
- ✅ `test_stores_file_in_correct_path` (AttachmentServiceTest.php)
- ✅ Feature Tests de attachments

---

### 6.6. `app/Features/TicketManagement/Services/RatingService.php`

**Responsabilidad**: Lógica de calificaciones

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Exceptions\RatingAlreadyExistsException;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketRating;
use App\Features\UserManagement\Models\User;

/**
 * RatingService
 *
 * Maneja la lógica de calificaciones de tickets.
 */
class RatingService
{
    /**
     * Crear calificación
     */
    public function create(Ticket $ticket, array $data, User $user): TicketRating
    {
        // Validar que ticket esté resolved o closed
        if (!$ticket->isResolved() && !$ticket->isClosed()) {
            throw new \Exception(
                "Can only rate resolved or closed tickets. Current status: {$ticket->status->value}",
                400
            );
        }

        // Validar que usuario sea el owner del ticket
        if ($ticket->created_by_user_id !== $user->id) {
            throw new \Exception("You can only rate your own tickets", 403);
        }

        // Validar que no exista calificación previa
        if ($ticket->rating) {
            throw new RatingAlreadyExistsException();
        }

        // Crear calificación con snapshot de rated_agent_id
        return TicketRating::create([
            'ticket_id' => $ticket->id,
            'rated_by_user_id' => $user->id,
            'rated_agent_id' => $ticket->owner_agent_id, // Snapshot histórico
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }

    /**
     * Actualizar calificación
     */
    public function update(TicketRating $rating, array $data, User $user): TicketRating
    {
        // Validar que usuario sea el autor
        if ($rating->rated_by_user_id !== $user->id) {
            throw new \Exception("You can only update your own ratings", 403);
        }

        // Validar ventana de 24 horas
        if (!$rating->canBeUpdated()) {
            throw new \Exception("Cannot update rating after 24 hours", 403);
        }

        // Actualizar (rated_agent_id NO cambia, es snapshot histórico)
        $rating->update([
            'rating' => $data['rating'] ?? $rating->rating,
            'comment' => $data['comment'] ?? $rating->comment,
        ]);

        return $rating->fresh();
    }
}
```

**Tests que pasan**:
- ✅ `test_validates_ticket_resolved_or_closed_only` (RatingServiceTest.php)
- ✅ `test_validates_user_is_ticket_owner` (RatingServiceTest.php)
- ✅ `test_saves_rated_agent_id_from_current_owner` (RatingServiceTest.php)
- ✅ Feature Tests de ratings

---

### Tests que pasan después de Fase 6

- ✅ **19 tests Unit (Services)** → TicketServiceTest.php, ResponseServiceTest.php, AttachmentServiceTest.php, RatingServiceTest.php

---

## ✔️ FASE 7: RULES (Validaciones Personalizadas)

### Objetivo
Crear reglas de validación personalizadas para Laravel.

### Archivos a Crear (2 rules)

#### 7.1. `app/Features/TicketManagement/Rules/ValidFileType.php`

**Responsabilidad**: Validar tipos de archivos permitidos

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

/**
 * ValidFileType
 *
 * Valida que el archivo sea de un tipo permitido.
 */
class ValidFileType implements Rule
{
    protected const ALLOWED_TYPES = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
        'txt', 'zip'
    ];

    protected string $invalidType = '';

    /**
     * Determinar si la validación pasa
     */
    public function passes($attribute, $value): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $this->invalidType = $extension;

        return in_array($extension, self::ALLOWED_TYPES);
    }

    /**
     * Mensaje de error
     */
    public function message(): string
    {
        $allowed = implode(', ', array_map('strtoupper', self::ALLOWED_TYPES));

        return "The file type '{$this->invalidType}' is not allowed. Allowed types: {$allowed}";
    }
}
```

**Tests que pasan**:
- ✅ `test_validates_all_allowed_file_types` (ValidFileTypeTest.php)
- ✅ `test_rejects_executable_and_script_files` (ValidFileTypeTest.php)
- ✅ `test_error_message_lists_allowed_types` (ValidFileTypeTest.php)

**Total: 3 tests** ✅

---

#### 7.2. `app/Features/TicketManagement/Rules/CanReopenTicket.php`

**Responsabilidad**: Validar si un ticket puede ser reabierto

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Rules;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use Illuminate\Contracts\Validation\Rule;

/**
 * CanReopenTicket
 *
 * Valida que un ticket pueda ser reabierto según reglas de negocio.
 */
class CanReopenTicket implements Rule
{
    protected string $errorMessage = '';

    public function __construct(
        protected User $user
    ) {}

    /**
     * Determinar si la validación pasa
     */
    public function passes($attribute, $value): bool
    {
        // $value es el ticket_id o el Ticket model
        $ticket = $value instanceof Ticket ? $value : Ticket::find($value);

        if (!$ticket) {
            $this->errorMessage = 'Ticket not found';
            return false;
        }

        // Validar que esté en estado resolved o closed
        if (!$ticket->isResolved() && !$ticket->isClosed()) {
            $this->errorMessage = "Can only reopen resolved or closed tickets. Current status: {$ticket->status->value}";
            return false;
        }

        // AGENT o COMPANY_ADMIN: sin restricción de tiempo
        if ($this->user->hasRole('AGENT') || $this->user->hasRole('COMPANY_ADMIN')) {
            return true;
        }

        // USER: restricción de 30 días si está closed
        if ($this->user->hasRole('USER') && $ticket->isClosed()) {
            $daysSinceClosed = $ticket->closed_at->diffInDays(now());

            if ($daysSinceClosed > 30) {
                $this->errorMessage = "Cannot reopen ticket after 30 days since closure";
                return false;
            }
        }

        return true;
    }

    /**
     * Mensaje de error
     */
    public function message(): string
    {
        return $this->errorMessage;
    }
}
```

**Tests que pasan**:
- ✅ `test_user_can_reopen_within_30_days` (CanReopenTicketTest.php)
- ✅ `test_user_cannot_reopen_after_30_days` (CanReopenTicketTest.php)
- ✅ `test_agent_can_reopen_regardless_of_time` (CanReopenTicketTest.php)
- ✅ `test_must_be_resolved_or_closed_status` (CanReopenTicketTest.php)
- ✅ `test_error_message_explains_30_day_limit` (CanReopenTicketTest.php)

**Total: 5 tests** ✅

---

### Tests que pasan después de Fase 7

- ✅ **8 tests Unit (Rules)** → ValidFileTypeTest.php, CanReopenTicketTest.php

---

## 🔒 FASE 8: POLICIES (Autorización)

### Objetivo
Implementar lógica de autorización usando Laravel Policies.

### Archivos a Crear (4 policies) + 1 ya existe

#### 8.1. CategoryPolicy.php

**Status**: ✅ **YA EXISTE**

Verificar que tenga los métodos:
- `create()` - solo COMPANY_ADMIN
- `update()` - solo COMPANY_ADMIN de la misma empresa
- `delete()` - solo COMPANY_ADMIN de la misma empresa
- `view()` - todos
- `viewAny()` - todos

---

#### 8.2. `app/Features/TicketManagement/Policies/TicketPolicy.php`

**Responsabilidad**: Autorización para tickets

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;

/**
 * TicketPolicy - Autorización para tickets
 */
class TicketPolicy
{
    /**
     * Determinar si el usuario puede listar tickets
     */
    public function viewAny(User $user): bool
    {
        // Todos los usuarios autenticados pueden listar
        return true;
    }

    /**
     * Determinar si el usuario puede ver un ticket específico
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // USER: solo tickets propios
        if ($user->hasRole('USER')) {
            return $ticket->created_by_user_id === $user->id;
        }

        // AGENT/COMPANY_ADMIN: tickets de su empresa
        if ($user->hasRole('AGENT') || $user->hasRole('COMPANY_ADMIN')) {
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
                ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            return $companyId && $ticket->company_id === $companyId;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede crear tickets
     */
    public function create(User $user): bool
    {
        // Solo USER puede crear tickets
        return $user->hasRole('USER');
    }

    /**
     * Determinar si el usuario puede actualizar un ticket
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // USER: solo tickets propios y SOLO si status = 'open'
        if ($user->hasRole('USER')) {
            return $ticket->created_by_user_id === $user->id
                && $ticket->isOpen();
        }

        // AGENT/COMPANY_ADMIN: tickets de su empresa, cualquier status
        if ($user->hasRole('AGENT') || $user->hasRole('COMPANY_ADMIN')) {
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
                ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            return $companyId && $ticket->company_id === $companyId;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede eliminar un ticket
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Solo COMPANY_ADMIN puede eliminar
        if (!$user->hasRole('COMPANY_ADMIN')) {
            return false;
        }

        // Verificar que el ticket esté cerrado
        if (!$ticket->isClosed()) {
            return false;
        }

        // Verificar que sea de la misma empresa
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Determinar si el usuario puede resolver un ticket
     */
    public function resolve(User $user, Ticket $ticket): bool
    {
        // Solo AGENT/COMPANY_ADMIN
        if (!$user->hasRole('AGENT') && !$user->hasRole('COMPANY_ADMIN')) {
            return false;
        }

        // Verificar que sea de la misma empresa
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
            ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Determinar si el usuario puede cerrar un ticket
     */
    public function close(User $user, Ticket $ticket): bool
    {
        // USER: solo tickets propios y SOLO si status = 'resolved'
        if ($user->hasRole('USER')) {
            return $ticket->created_by_user_id === $user->id
                && $ticket->isResolved();
        }

        // AGENT/COMPANY_ADMIN: tickets de su empresa, cualquier status
        if ($user->hasRole('AGENT') || $user->hasRole('COMPANY_ADMIN')) {
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
                ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            return $companyId && $ticket->company_id === $companyId;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede reabrir un ticket
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        // USER: solo tickets propios (validación de tiempo se hace en Rule)
        if ($user->hasRole('USER')) {
            return $ticket->created_by_user_id === $user->id;
        }

        // AGENT/COMPANY_ADMIN: tickets de su empresa
        if ($user->hasRole('AGENT') || $user->hasRole('COMPANY_ADMIN')) {
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
                ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            return $companyId && $ticket->company_id === $companyId;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede reasignar un ticket
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        // Solo AGENT/COMPANY_ADMIN
        if (!$user->hasRole('AGENT') && !$user->hasRole('COMPANY_ADMIN')) {
            return false;
        }

        // Verificar que sea de la misma empresa
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
            ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $ticket->company_id === $companyId;
    }
}
```

**Tests que habilita**:
- Todos los Feature Tests de tickets CRUD y Actions

---

#### 8.3. `app/Features/TicketManagement/Policies/ResponsePolicy.php`

**Responsabilidad**: Autorización para respuestas

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;

/**
 * ResponsePolicy - Autorización para respuestas
 */
class ResponsePolicy
{
    /**
     * Determinar si el usuario puede ver respuestas de un ticket
     */
    public function viewAny(User $user, Ticket $ticket): bool
    {
        // Usa TicketPolicy->view()
        return app(TicketPolicy::class)->view($user, $ticket);
    }

    /**
     * Determinar si el usuario puede agregar respuesta
     */
    public function create(User $user, Ticket $ticket): bool
    {
        // USER: solo tickets propios
        if ($user->hasRole('USER')) {
            return $ticket->created_by_user_id === $user->id;
        }

        // AGENT/COMPANY_ADMIN: tickets de su empresa
        if ($user->hasRole('AGENT') || $user->hasRole('COMPANY_ADMIN')) {
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
                ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            return $companyId && $ticket->company_id === $companyId;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede editar una respuesta
     */
    public function update(User $user, TicketResponse $response): bool
    {
        // Solo el autor puede editar
        // (validación de tiempo se hace en Service)
        return $response->author_id === $user->id;
    }

    /**
     * Determinar si el usuario puede eliminar una respuesta
     */
    public function delete(User $user, TicketResponse $response): bool
    {
        // Solo el autor puede eliminar
        // (validación de tiempo se hace en Service)
        return $response->author_id === $user->id;
    }
}
```

**Tests que habilita**:
- Todos los Feature Tests de responses

---

#### 8.4. `app/Features/TicketManagement/Policies/AttachmentPolicy.php`

**Responsabilidad**: Autorización para adjuntos

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;

/**
 * AttachmentPolicy - Autorización para adjuntos
 */
class AttachmentPolicy
{
    /**
     * Determinar si el usuario puede ver adjuntos de un ticket
     */
    public function viewAny(User $user, Ticket $ticket): bool
    {
        // Usa TicketPolicy->view()
        return app(TicketPolicy::class)->view($user, $ticket);
    }

    /**
     * Determinar si el usuario puede subir adjunto
     */
    public function create(User $user, Ticket $ticket): bool
    {
        // USER: solo tickets propios
        if ($user->hasRole('USER')) {
            return $ticket->created_by_user_id === $user->id;
        }

        // AGENT/COMPANY_ADMIN: tickets de su empresa
        if ($user->hasRole('AGENT') || $user->hasRole('COMPANY_ADMIN')) {
            $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
                ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

            return $companyId && $ticket->company_id === $companyId;
        }

        return false;
    }

    /**
     * Determinar si el usuario puede eliminar un adjunto
     */
    public function delete(User $user, TicketAttachment $attachment): bool
    {
        // Solo el uploader puede eliminar
        // (validación de tiempo se hace en Service)
        return $attachment->uploaded_by_user_id === $user->id;
    }
}
```

**Tests que habilita**:
- Todos los Feature Tests de attachments

---

#### 8.5. `app/Features/TicketManagement/Policies/RatingPolicy.php`

**Responsabilidad**: Autorización para calificaciones

```php
<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketRating;
use App\Features\UserManagement\Models\User;
use App\Shared\Helpers\JWTHelper;

/**
 * RatingPolicy - Autorización para calificaciones
 */
class RatingPolicy
{
    /**
     * Determinar si el usuario puede ver calificación
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Usa TicketPolicy->view()
        return app(TicketPolicy::class)->view($user, $ticket);
    }

    /**
     * Determinar si el usuario puede calificar un ticket
     */
    public function create(User $user, Ticket $ticket): bool
    {
        // Solo USER puede calificar
        if (!$user->hasRole('USER')) {
            return false;
        }

        // Solo tickets propios
        return $ticket->created_by_user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede actualizar calificación
     */
    public function update(User $user, TicketRating $rating): bool
    {
        // Solo el autor puede actualizar
        // (validación de tiempo se hace en Service)
        return $rating->rated_by_user_id === $user->id;
    }
}
```

**Tests que habilita**:
- Todos los Feature Tests de ratings

---

### Tests que pasan después de Fase 8

- ✅ **Habilita TODOS los Feature Tests** (indirectamente)
- ✅ **26 tests de Permissions** (directamente)

---

## 📤 FASE 9: RESOURCES (Transformadores)

### Objetivo
Crear transformadores para las respuestas JSON de la API.

### Archivos a Crear (8 resources)

*Continúa en siguiente mensaje debido al límite de tokens...*
