````# Plan de Implementación TDD - Ticket Management Backend

## Objetivo
Transformar todos los tests RED a GREEN del feature Ticket Management siguiendo metodología TDD, implementando en fases manejables y verificables.

## Scope de Esta Iteración
Solo implementar los siguientes grupos de tests:
- ✅ Categories: **YA ESTÁN GREEN** (26 tests)
- ❌ Tickets CRUD: 70 tests RED
- ❌ Tickets Actions: 45 tests RED
- ❌ Responses: 48 tests RED
- ❌ Attachments: 37 tests RED
- ❌ Unit Tests: 41 tests RED (Models, Rules, Services, Jobs)

**Total a implementar: 241 tests RED → GREEN**

---

## Reglas Fundamentales para Agentes

### Arquitectura JWT Stateless
```php
// ❌ NUNCA usar Laravel sessions
Session::get('user_id')

// ✅ SIEMPRE usar JWTHelper
JWTHelper::getAuthenticatedUser()
JWTHelper::hasRoleFromJWT('AGENT')
JWTHelper::getCompanyIdFromJWT('AGENT')
```

### Middlewares Existentes (REUTILIZAR)
- `AuthenticateJwt`: Para autenticación JWT
- `EnsureUserHasRole`: Para protección por roles
```php
// Sintaxis en routes
Route::post('/tickets', [TicketController::class, 'store'])
    ->middleware(['auth.jwt', 'role:USER']);
```

### Policies para Autorización
Usar Laravel Policies (NO middlewares custom) para lógica de autorización específica por recurso.

### Multi-tenancy
Todos los recursos están scopeados por `company_id`. Usar JWTHelper para obtener contexto de compañía.

### PostgreSQL Triggers
La DB tiene triggers que auto-ejecutan lógica de negocio:
- Auto-asignación cuando agent responde
- Cambio PENDING → OPEN cuando user responde
- Actualización de `last_response_author_type`

### Validación de Tiempo
- Editar/eliminar respuestas: 30 minutos desde creación
- Reabrir tickets (user): 30 días desde cierre
- Auto-cierre tickets resueltos: 7 días

### Testing con Docker
```bash
# ✅ SIEMPRE usar Docker
docker compose exec app php artisan test tests/Feature/TicketManagement
docker compose exec app php artisan test tests/Unit/TicketManagement

# ❌ NUNCA usar Herd directamente
```

---

## Estrategia de Implementación

### Principio: Bottom-Up + Unit First
1. **Base de datos** (migrations + triggers)
2. **Models** (Unit tests primero)
3. **Rules de validación** (Unit tests)
4. **Services** (Unit tests primero, Feature después)
5. **Policies** (para Feature tests)
6. **Resources/Requests** (para Feature tests)
7. **Controllers** (Feature tests en orden de dependencias)
8. **Jobs** (Unit tests)

### Verificación Incremental
Después de cada fase, ejecutar los tests específicos de esa fase para verificar GREEN antes de continuar.

---

## FASE 1: Migraciones y Triggers PostgreSQL

### Objetivo
Crear estructura de base de datos completa con triggers para lógica automática.

### Archivos a Crear

#### 1.1 Migration: tickets table
**Path:** `database/migrations/YYYY_MM_DD_HHMMSS_create_ticketing_tickets_table.php`

**Campos críticos:**
```php
$table->uuid('id')->primary();
$table->string('ticket_code', 20)->unique();
$table->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');
$table->string('last_response_author_type', 20)->default('none'); // none, user, agent
$table->uuid('created_by_user_id');
$table->uuid('owner_agent_id')->nullable();
$table->uuid('company_id');
$table->uuid('category_id');
$table->timestamp('first_response_at')->nullable();
$table->timestamp('closed_at')->nullable();

// Índices
$table->index(['company_id', 'status']);
$table->index(['owner_agent_id']);
$table->index(['created_by_user_id']);
$table->index(['ticket_code']);
$table->index(['last_response_author_type']);

// Foreign keys
$table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');
$table->foreign('owner_agent_id')->references('id')->on('users')->onDelete('set null');
$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
$table->foreign('category_id')->references('id')->on('ticketing.categories')->onDelete('restrict');
```

#### 1.2 Migration: ticket_responses table
**Path:** `database/migrations/YYYY_MM_DD_HHMMSS_create_ticketing_ticket_responses_table.php`

```php
$table->uuid('id')->primary();
$table->uuid('ticket_id');
$table->uuid('author_id');
$table->enum('author_type', ['user', 'agent']);
$table->text('content');
$table->timestamps();

$table->foreign('ticket_id')->references('id')->on('ticketing.tickets')->onDelete('cascade');
$table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');

$table->index(['ticket_id', 'created_at']);
```

#### 1.3 Migration: ticket_attachments table
**Path:** `database/migrations/YYYY_MM_DD_HHMMSS_create_ticketing_ticket_attachments_table.php`

```php
$table->uuid('id')->primary();
$table->uuid('ticket_id');
$table->uuid('response_id')->nullable();
$table->uuid('uploaded_by_user_id');
$table->string('file_name');
$table->string('file_path');
$table->string('file_type', 100);
$table->unsignedInteger('file_size'); // bytes
$table->timestamps();

$table->foreign('ticket_id')->references('id')->on('ticketing.tickets')->onDelete('cascade');
$table->foreign('response_id')->references('id')->on('ticketing.ticket_responses')->onDelete('cascade');
$table->foreign('uploaded_by_user_id')->references('id')->on('users')->onDelete('cascade');

$table->index(['ticket_id']);
$table->index(['response_id']);
```

#### 1.4 Migration: ticket_ratings table
**Path:** `database/migrations/YYYY_MM_DD_HHMMSS_create_ticketing_ticket_ratings_table.php`

```php
$table->uuid('id')->primary();
$table->uuid('ticket_id')->unique(); // Un rating por ticket
$table->uuid('rated_by_user_id');
$table->uuid('rated_agent_id')->nullable();
$table->unsignedTinyInteger('rating'); // 1-5
$table->text('comment')->nullable();
$table->timestamps();

$table->foreign('ticket_id')->references('id')->on('ticketing.tickets')->onDelete('cascade');
$table->foreign('rated_by_user_id')->references('id')->on('users')->onDelete('cascade');
$table->foreign('rated_agent_id')->references('id')->on('users')->onDelete('set null');
```

#### 1.5 Migration: Trigger - Auto-asignación cuando agent responde
**Path:** `database/migrations/YYYY_MM_DD_HHMMSS_create_trigger_assign_ticket_owner.php`

```php
public function up()
{
    DB::unprepared("
        CREATE OR REPLACE FUNCTION ticketing.assign_ticket_owner()
        RETURNS TRIGGER AS $$
        BEGIN
            IF NEW.author_type = 'agent' THEN
                UPDATE ticketing.tickets
                SET
                    owner_agent_id = CASE
                        WHEN owner_agent_id IS NULL
                        THEN NEW.author_id
                        ELSE owner_agent_id
                    END,
                    first_response_at = CASE
                        WHEN first_response_at IS NULL
                        THEN NOW()
                        ELSE first_response_at
                    END,
                    status = 'pending',
                    last_response_author_type = 'agent',
                    updated_at = NOW()
                WHERE id = NEW.ticket_id;
            END IF;

            IF NEW.author_type = 'user' THEN
                UPDATE ticketing.tickets
                SET
                    last_response_author_type = 'user',
                    updated_at = NOW()
                WHERE id = NEW.ticket_id;
            END IF;

            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;

        DROP TRIGGER IF EXISTS trigger_assign_ticket_owner ON ticketing.ticket_responses;
        CREATE TRIGGER trigger_assign_ticket_owner
        AFTER INSERT ON ticketing.ticket_responses
        FOR EACH ROW
        EXECUTE FUNCTION ticketing.assign_ticket_owner();
    ");
}

public function down()
{
    DB::unprepared("
        DROP TRIGGER IF EXISTS trigger_assign_ticket_owner ON ticketing.ticket_responses;
        DROP FUNCTION IF EXISTS ticketing.assign_ticket_owner();
    ");
}
```

#### 1.6 Migration: Trigger - PENDING → OPEN cuando user responde
**Path:** `database/migrations/YYYY_MM_DD_HHMMSS_create_trigger_return_pending_to_open.php`

```php
public function up()
{
    DB::unprepared("
        CREATE OR REPLACE FUNCTION ticketing.return_pending_to_open_on_user_response()
        RETURNS TRIGGER AS $$
        BEGIN
            IF NEW.author_type = 'user' THEN
                UPDATE ticketing.tickets
                SET
                    status = 'open',
                    updated_at = NOW()
                WHERE id = NEW.ticket_id
                AND status = 'pending';
            END IF;

            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;

        DROP TRIGGER IF EXISTS trigger_return_pending_to_open ON ticketing.ticket_responses;
        CREATE TRIGGER trigger_return_pending_to_open
        AFTER INSERT ON ticketing.ticket_responses
        FOR EACH ROW
        EXECUTE FUNCTION ticketing.return_pending_to_open_on_user_response();
    ");
}

public function down()
{
    DB::unprepared("
        DROP TRIGGER IF EXISTS trigger_return_pending_to_open ON ticketing.ticket_responses;
        DROP FUNCTION IF EXISTS ticketing.return_pending_to_open_on_user_response();
    ");
}
```

### Tests que se verifican al final de Fase 1
```bash
docker compose exec app php artisan migrate:fresh --seed

# Verificar que las tablas existen
docker compose exec app php artisan tinker
>>> \DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'ticketing'");

# Los tests aún estarán RED porque falta el código de aplicación
```

### Criterio de Éxito Fase 1
- ✅ Migraciones ejecutan sin errores
- ✅ Tablas creadas en schema `ticketing`
- ✅ Triggers creados correctamente
- ✅ Foreign keys funcionando

---

## FASE 2: Models y Relaciones Eloquent

### Objetivo
Crear modelos Eloquent con relaciones, casts, y scopes básicos.

### Tests a Pasar (Unit Tests)
```bash
docker compose exec app php artisan test tests/Unit/TicketManagement/Models/TicketTest.php
docker compose exec app php artisan test tests/Unit/TicketManagement/Models/TicketFieldsTest.php
```

**Tests específicos:**
- ✅ status casts to enum
- ✅ belongs to creator
- ✅ belongs to owner agent (RED → GREEN)
- ✅ belongs to company
- ✅ belongs to category
- ✅ has many responses
- ✅ open scope
- ✅ pending scope
- ✅ casts last_response_author_type as string

### Archivos a Crear

#### 2.1 Modelo: Ticket
**Path:** `app/Features/TicketManagement/Models/Ticket.php`

```php
<?php

namespace App\Features\TicketManagement\Models;

use App\Features\Companies\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ticketing.tickets';

    protected $fillable = [
        'ticket_code',
        'title',
        'description',
        'status',
        'last_response_author_type',
        'created_by_user_id',
        'owner_agent_id',
        'company_id',
        'category_id',
        'first_response_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'first_response_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_response_author_type' => 'string',
    ];

    // Relaciones
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function ownerAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_agent_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(TicketResponse::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(TicketRating::class);
    }

    // Scopes
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
}
```

#### 2.2 Modelo: TicketResponse
**Path:** `app/Features/TicketManagement/Models/TicketResponse.php`

```php
<?php

namespace App\Features\TicketManagement\Models;

use App\Features\TicketManagement\Enums\AuthorType;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketResponse extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ticketing.ticket_responses';

    protected $fillable = [
        'ticket_id',
        'author_id',
        'author_type',
        'content',
    ];

    protected $casts = [
        'author_type' => AuthorType::class,
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'response_id');
    }
}
```

#### 2.3 Modelo: TicketAttachment
**Path:** `app/Features/TicketManagement/Models/TicketAttachment.php`

```php
<?php

namespace App\Features\TicketManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ticketing.ticket_attachments';

    protected $fillable = [
        'ticket_id',
        'response_id',
        'uploaded_by_user_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function response(): BelongsTo
    {
        return $this->belongsTo(TicketResponse::class, 'response_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
```

#### 2.4 Modelo: TicketRating
**Path:** `app/Features/TicketManagement/Models/TicketRating.php`

```php
<?php

namespace App\Features\TicketManagement\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketRating extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ticketing.ticket_ratings';

    protected $fillable = [
        'ticket_id',
        'rated_by_user_id',
        'rated_agent_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function ratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_by_user_id');
    }

    public function ratedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rated_agent_id');
    }
}
```

#### 2.5 Verificar Enums Existentes
Los enums ya deben existir según el contexto anterior:
- `App\Features\TicketManagement\Enums\TicketStatus`
- `App\Features\TicketManagement\Enums\AuthorType`

**Verificar que contienen:**

**TicketStatus.php:**
```php
enum TicketStatus: string
{
    case OPEN = 'open';
    case PENDING = 'pending';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
}
```

**AuthorType.php:**
```php
enum AuthorType: string
{
    case USER = 'user';
    case AGENT = 'agent';
}
```

### Criterio de Éxito Fase 2
```bash
docker compose exec app php artisan test tests/Unit/TicketManagement/Models/
# Todos los tests de Models deben pasar (8 tests GREEN)
```

---

## FASE 3: Rules de Validación Personalizadas

### Objetivo
Implementar reglas de validación custom que se usan en múltiples lugares.

### Tests a Pasar (Unit Tests)
```bash
docker compose exec app php artisan test tests/Unit/TicketManagement/Rules/ValidFileTypeTest.php
docker compose exec app php artisan test tests/Unit/TicketManagement/Rules/CanReopenTicketTest.php
```

**Tests específicos:**
- ✅ validates all allowed file types
- ✅ rejects executable and script files
- ✅ error message lists allowed types
- ✅ user can reopen within 30 days
- ✅ user cannot reopen after 30 days
- ✅ agent can reopen regardless of time
- ✅ must be resolved or closed status
- ✅ error message explains 30 day limit

### Archivos a Crear

#### 3.1 Rule: ValidFileType
**Path:** `app/Features/TicketManagement/Rules/ValidFileType.php`

```php
<?php

namespace App\Features\TicketManagement\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidFileType implements ValidationRule
{
    public const ALLOWED_TYPES = [
        // Documentos
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
        // Imágenes
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp',
        // Comprimidos
        'zip', 'rar', '7z', 'tar', 'gz',
    ];

    public const FORBIDDEN_TYPES = [
        // Ejecutables
        'exe', 'bat', 'cmd', 'com', 'msi', 'app', 'dmg',
        // Scripts
        'sh', 'bash', 'ps1', 'vbs', 'js', 'jar',
        // Otros peligrosos
        'dll', 'sys', 'scr',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value instanceof UploadedFile) {
            $fail('El archivo debe ser un archivo válido.');
            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());

        if (in_array($extension, self::FORBIDDEN_TYPES)) {
            $fail("El tipo de archivo .{$extension} no está permitido por razones de seguridad.");
            return;
        }

        if (!in_array($extension, self::ALLOWED_TYPES)) {
            $allowedList = implode(', ', self::ALLOWED_TYPES);
            $fail("El tipo de archivo debe ser uno de los siguientes: {$allowedList}.");
            return;
        }
    }
}
```

#### 3.2 Rule: CanReopenTicket
**Path:** `app/Features/TicketManagement/Rules/CanReopenTicket.php`

```php
<?php

namespace App\Features\TicketManagement\Rules;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CanReopenTicket implements ValidationRule
{
    private Ticket $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Solo se pueden reabrir tickets resolved o closed
        if (!in_array($this->ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED])) {
            $fail('Solo se pueden reabrir tickets resueltos o cerrados.');
            return;
        }

        // Si es agent, puede reabrir siempre
        if (JWTHelper::hasRoleFromJWT('AGENT')) {
            return;
        }

        // Si es user, validar 30 días
        if ($this->ticket->status === TicketStatus::CLOSED && $this->ticket->closed_at) {
            $daysSinceClosed = Carbon::parse($this->ticket->closed_at)->diffInDays(Carbon::now());

            if ($daysSinceClosed > 30) {
                $fail('No puedes reabrir un ticket cerrado después de 30 días.');
                return;
            }
        }
    }
}
```

### Criterio de Éxito Fase 3
```bash
docker compose exec app php artisan test tests/Unit/TicketManagement/Rules/
# Todos los tests de Rules deben pasar (8 tests GREEN)
```

---

## FASE 4: Services - Lógica de Negocio (Unit Tests)

### Objetivo
Implementar servicios con lógica de negocio core, validando primero con Unit tests.

### Tests a Pasar (Unit Tests)
```bash
docker compose exec app php artisan test tests/Unit/TicketManagement/Services/TicketServiceTest.php
docker compose exec app php artisan test tests/Unit/TicketManagement/Services/ResponseServiceTest.php
docker compose exec app php artisan test tests/Unit/TicketManagement/Services/AttachmentServiceTest.php
docker compose exec app php artisan test tests/Unit/TicketManagement/Services/RatingServiceTest.php
```

**Tests específicos:**
- ✅ generates unique ticket codes
- ✅ validates company exists
- ✅ filters tickets by owner for users
- ✅ delete only allows closed tickets
- ✅ determines author type automatically
- ✅ validates auto assignment trigger only first agent
- ✅ validates file size max 10mb
- ✅ validates allowed file types
- ✅ stores file in correct path
- ✅ validates ticket resolved or closed only
- ✅ validates user is ticket owner
- ✅ saves rated agent id from current owner

### Archivos a Crear

#### 4.1 Service: TicketCodeGenerator
**Path:** `app/Features/TicketManagement/Services/TicketCodeGenerator.php`

```php
<?php

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketCodeGenerator
{
    /**
     * Genera código único de ticket formato: TKT-YYYY-NNNNN
     * Secuencial por año.
     */
    public function generate(): string
    {
        $year = now()->year;
        $prefix = "TKT-{$year}-";

        return DB::transaction(function () use ($prefix, $year) {
            // Obtener el último código del año actual
            $lastTicket = Ticket::where('ticket_code', 'LIKE', "{$prefix}%")
                ->orderByDesc('ticket_code')
                ->lockForUpdate()
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
            return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
        });
    }
}
```

#### 4.2 Service: TicketService
**Path:** `app/Features/TicketManagement/Services/TicketService.php`

```php
<?php

namespace App\Features\TicketManagement\Services;

use App\Features\Companies\Models\Company;
use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Exceptions\TicketCannotBeDeletedException;
use App\Features\TicketManagement\Models\Ticket;
use App\Models\User;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function __construct(
        private TicketCodeGenerator $codeGenerator
    ) {}

    /**
     * Crear un nuevo ticket.
     */
    public function create(array $data, User $user): Ticket
    {
        // Validar que la compañía existe
        $company = Company::findOrFail($data['company_id']);

        return DB::transaction(function () use ($data, $user) {
            $ticket = Ticket::create([
                'ticket_code' => $this->codeGenerator->generate(),
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => TicketStatus::OPEN,
                'last_response_author_type' => 'none',
                'created_by_user_id' => $user->id,
                'company_id' => $data['company_id'],
                'category_id' => $data['category_id'],
            ]);

            return $ticket->load(['creator', 'company', 'category']);
        });
    }

    /**
     * Listar tickets con filtros y permisos.
     */
    public function list(array $filters, User $user): LengthAwarePaginator
    {
        $query = Ticket::query()
            ->with(['creator', 'ownerAgent', 'category', 'company'])
            ->withCount(['responses', 'attachments']);

        // Aplicar visibilidad según rol
        $this->applyVisibilityFilters($query, $user);

        // Aplicar filtros adicionales
        $this->applyFilters($query, $filters, $user);

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * Actualizar ticket.
     */
    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->update($data);
        return $ticket->fresh(['creator', 'ownerAgent', 'category', 'company']);
    }

    /**
     * Eliminar ticket (solo si está cerrado).
     */
    public function delete(Ticket $ticket): void
    {
        if ($ticket->status !== TicketStatus::CLOSED) {
            throw new TicketCannotBeDeletedException(
                'Solo se pueden eliminar tickets cerrados.'
            );
        }

        DB::transaction(function () use ($ticket) {
            // Cascade delete manejado por foreign keys
            $ticket->delete();
        });
    }

    /**
     * Aplicar filtros de visibilidad según rol del usuario.
     */
    private function applyVisibilityFilters(Builder $query, User $user): void
    {
        // Si es USER, solo ve sus propios tickets
        if (!JWTHelper::hasRoleFromJWT('AGENT') && !JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
            $query->where('created_by_user_id', $user->id);
            return;
        }

        // Si es AGENT o COMPANY_ADMIN, ve todos los de su compañía
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
            ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }
    }

    /**
     * Aplicar filtros adicionales de búsqueda.
     */
    private function applyFilters(Builder $query, array $filters, User $user): void
    {
        // Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por categoría
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtro por owner_agent_id
        if (isset($filters['owner_agent_id'])) {
            if ($filters['owner_agent_id'] === 'me') {
                $query->where('owner_agent_id', $user->id);
            } elseif ($filters['owner_agent_id'] === 'null') {
                $query->whereNull('owner_agent_id');
            } else {
                $query->where('owner_agent_id', $filters['owner_agent_id']);
            }
        }

        // Filtro por created_by_user_id
        if (!empty($filters['created_by_user_id'])) {
            $query->where('created_by_user_id', $filters['created_by_user_id']);
        }

        // Filtro por last_response_author_type
        if (!empty($filters['last_response_author_type'])) {
            $query->where('last_response_author_type', $filters['last_response_author_type']);
        }

        // Búsqueda por texto (título o descripción)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        // Filtro por rango de fechas
        if (!empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }
        if (!empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }
    }
}
```

#### 4.3 Service: ResponseService
**Path:** `app/Features/TicketManagement/Services/ResponseService.php`

```php
<?php

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Enums\AuthorType;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Models\User;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ResponseService
{
    /**
     * Crear una respuesta a un ticket.
     * El trigger de BD se encarga de auto-asignación y cambio de estado.
     */
    public function create(Ticket $ticket, array $data, User $user): TicketResponse
    {
        return DB::transaction(function () use ($ticket, $data, $user) {
            // Determinar author_type automáticamente
            $authorType = $this->determineAuthorType($user);

            $response = TicketResponse::create([
                'ticket_id' => $ticket->id,
                'author_id' => $user->id,
                'author_type' => $authorType,
                'content' => $data['content'],
            ]);

            // El trigger de BD ya maneja:
            // - Auto-asignación si es primera respuesta de agent
            // - Cambio PENDING → OPEN si es respuesta de user
            // - Actualización de last_response_author_type

            return $response->load(['author', 'ticket']);
        });
    }

    /**
     * Listar respuestas de un ticket.
     */
    public function list(Ticket $ticket): Collection
    {
        return TicketResponse::where('ticket_id', $ticket->id)
            ->with(['author.profile', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Actualizar respuesta (solo dentro de 30 minutos).
     */
    public function update(TicketResponse $response, array $data): TicketResponse
    {
        $response->update($data);
        return $response->fresh(['author', 'attachments']);
    }

    /**
     * Eliminar respuesta (solo dentro de 30 minutos).
     */
    public function delete(TicketResponse $response): void
    {
        DB::transaction(function () use ($response) {
            // Cascade delete manejado por foreign keys (attachments)
            $response->delete();
        });
    }

    /**
     * Determinar automáticamente el author_type según roles JWT.
     */
    private function determineAuthorType(User $user): AuthorType
    {
        if (JWTHelper::hasRoleFromJWT('AGENT')) {
            return AuthorType::AGENT;
        }

        return AuthorType::USER;
    }
}
```

#### 4.4 Service: AttachmentService
**Path:** `app/Features/TicketManagement/Services/AttachmentService.php`

```php
<?php

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Exceptions\MaxAttachmentsExceededException;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Rules\ValidFileType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    private const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10MB en bytes
    private const MAX_ATTACHMENTS_PER_TICKET = 5;

    /**
     * Subir attachment a un ticket.
     */
    public function upload(
        Ticket $ticket,
        UploadedFile $file,
        User $user,
        ?TicketResponse $response = null
    ): TicketAttachment {
        // Validar límite de archivos por ticket
        $currentCount = TicketAttachment::where('ticket_id', $ticket->id)->count();
        if ($currentCount >= self::MAX_ATTACHMENTS_PER_TICKET) {
            throw new MaxAttachmentsExceededException(
                'Se ha alcanzado el límite máximo de 5 archivos adjuntos por ticket.'
            );
        }

        // Validar archivo
        $this->validateFile($file);

        // Guardar archivo
        $path = $this->storeFile($file, $ticket->id);

        // Crear registro
        $attachment = TicketAttachment::create([
            'ticket_id' => $ticket->id,
            'response_id' => $response?->id,
            'uploaded_by_user_id' => $user->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
        ]);

        return $attachment->load(['uploader', 'ticket']);
    }

    /**
     * Listar attachments de un ticket.
     */
    public function list(Ticket $ticket): Collection
    {
        return TicketAttachment::where('ticket_id', $ticket->id)
            ->with(['uploader.profile', 'response'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Eliminar attachment (solo dentro de 30 minutos por el uploader).
     */
    public function delete(TicketAttachment $attachment): void
    {
        // Eliminar archivo físico
        if (Storage::disk('local')->exists($attachment->file_path)) {
            Storage::disk('local')->delete($attachment->file_path);
        }

        // Eliminar registro
        $attachment->delete();
    }

    /**
     * Validar archivo subido.
     */
    private function validateFile(UploadedFile $file): void
    {
        $validator = Validator::make(
            ['file' => $file],
            [
                'file' => [
                    'required',
                    'file',
                    'max:' . (self::MAX_FILE_SIZE / 1024), // Laravel espera KB
                    new ValidFileType(),
                ],
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Guardar archivo en storage.
     * Path: tickets/{ticket_id}/{timestamp}_{filename}
     */
    private function storeFile(UploadedFile $file, string $ticketId): string
    {
        $timestamp = now()->timestamp;
        $filename = $timestamp . '_' . $file->getClientOriginalName();
        $directory = "tickets/{$ticketId}";

        return $file->storeAs($directory, $filename, 'local');
    }
}
```

#### 4.5 Service: RatingService
**Path:** `app/Features/TicketManagement/Services/RatingService.php`

```php
<?php

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Exceptions\TicketNotRateableException;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketRating;
use App\Models\User;

class RatingService
{
    /**
     * Crear o actualizar rating de un ticket.
     */
    public function createOrUpdate(Ticket $ticket, array $data, User $user): TicketRating
    {
        // Validar que el ticket esté resolved o closed
        if (!in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED])) {
            throw new TicketNotRateableException(
                'Solo se pueden calificar tickets resueltos o cerrados.'
            );
        }

        // Validar que el usuario es el creador del ticket
        if ($ticket->created_by_user_id !== $user->id) {
            throw new TicketNotRateableException(
                'Solo el creador del ticket puede calificarlo.'
            );
        }

        // Crear o actualizar rating
        $rating = TicketRating::updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'rated_by_user_id' => $user->id,
                'rated_agent_id' => $ticket->owner_agent_id, // Agent actual asignado
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        return $rating->load(['ratedAgent', 'ticket']);
    }

    /**
     * Obtener rating de un ticket.
     */
    public function get(Ticket $ticket): ?TicketRating
    {
        return TicketRating::where('ticket_id', $ticket->id)
            ->with(['ratedAgent.profile', 'ratedBy'])
            ->first();
    }
}
```

### Excepciones Necesarias
**Path:** `app/Features/TicketManagement/Exceptions/`

```php
// MaxAttachmentsExceededException.php
class MaxAttachmentsExceededException extends \Exception
{
    protected $message = 'Se ha alcanzado el límite máximo de archivos adjuntos.';
}

// TicketCannotBeDeletedException.php
class TicketCannotBeDeletedException extends \Exception
{
    protected $message = 'El ticket no puede ser eliminado en su estado actual.';
}

// TicketNotRateableException.php
class TicketNotRateableException extends \Exception
{
    protected $message = 'El ticket no puede ser calificado en su estado actual.';
}
```

### Criterio de Éxito Fase 4
```bash
docker compose exec app php artisan test tests/Unit/TicketManagement/Services/
# Todos los tests unitarios de Services deben pasar (11 tests GREEN)
```

---

## FASE 5: Policies - Autorización por Recurso

### Objetivo
Implementar políticas de autorización que serán usadas por los controllers.

### Tests que Validan (Indirectamente via Feature)
Las policies se validan principalmente en los Feature tests, pero su lógica debe estar lista antes de implementar controllers.

### Archivos a Crear

#### 5.1 Policy: TicketPolicy
**Path:** `app/Features/TicketManagement/Policies/TicketPolicy.php`

```php
<?php

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Models\User;
use App\Shared\Helpers\JWTHelper;

class TicketPolicy
{
    /**
     * Solo USER puede crear tickets.
     */
    public function create(User $user): bool
    {
        return JWTHelper::hasRoleFromJWT('USER');
    }

    /**
     * Ver ticket: creador o agent/admin de la misma compañía.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Creador puede ver siempre
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent/Admin pueden ver tickets de su compañía
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
            ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Actualizar ticket: creador (solo OPEN) o agent de la compañía.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Creador solo puede editar si está OPEN
        if ($ticket->created_by_user_id === $user->id) {
            return $ticket->status === TicketStatus::OPEN;
        }

        // Agent/Admin pueden editar tickets de su compañía
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
            ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Eliminar ticket: solo COMPANY_ADMIN y ticket debe estar cerrado.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId
            && $ticket->company_id === $companyId
            && $ticket->status === TicketStatus::CLOSED;
    }

    /**
     * Resolver ticket: solo AGENT de la compañía.
     */
    public function resolve(User $user, Ticket $ticket): bool
    {
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');

        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Cerrar ticket: AGENT puede cerrar cualquiera, USER solo si está resolved.
     */
    public function close(User $user, Ticket $ticket): bool
    {
        // Agent de la compañía puede cerrar cualquiera
        $agentCompanyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        if ($agentCompanyId && $ticket->company_id === $agentCompanyId) {
            return true;
        }

        // Creador solo puede cerrar si está RESOLVED
        return $ticket->created_by_user_id === $user->id
            && $ticket->status === TicketStatus::RESOLVED;
    }

    /**
     * Reabrir ticket: creador o agent de la compañía.
     * La Rule CanReopenTicket maneja la validación de 30 días.
     */
    public function reopen(User $user, Ticket $ticket): bool
    {
        // Creador puede reabrir (con restricción de 30 días en Rule)
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent puede reabrir sin restricciones
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Asignar ticket: solo AGENT de la compañía.
     */
    public function assign(User $user, Ticket $ticket): bool
    {
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');

        return $companyId && $ticket->company_id === $companyId;
    }
}
```

#### 5.2 Policy: TicketResponsePolicy
**Path:** `app/Features/TicketManagement/Policies/TicketResponsePolicy.php`

```php
<?php

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Models\User;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;

class TicketResponsePolicy
{
    /**
     * Crear respuesta: creador del ticket o agent de la compañía.
     */
    public function create(User $user, Ticket $ticket): bool
    {
        // Creador puede responder
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent de la compañía puede responder
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Ver respuestas: creador del ticket o agent de la compañía.
     */
    public function viewAny(User $user, Ticket $ticket): bool
    {
        // Creador puede ver
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent de la compañía puede ver
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Actualizar respuesta: solo autor dentro de 30 minutos.
     */
    public function update(User $user, TicketResponse $response): bool
    {
        // Debe ser el autor
        if ($response->author_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 30 minutos
        $minutesSinceCreation = Carbon::parse($response->created_at)->diffInMinutes(Carbon::now());
        return $minutesSinceCreation <= 30;
    }

    /**
     * Eliminar respuesta: solo autor dentro de 30 minutos.
     */
    public function delete(User $user, TicketResponse $response): bool
    {
        // Debe ser el autor
        if ($response->author_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 30 minutos
        $minutesSinceCreation = Carbon::parse($response->created_at)->diffInMinutes(Carbon::now());
        return $minutesSinceCreation <= 30;
    }
}
```

#### 5.3 Policy: TicketAttachmentPolicy
**Path:** `app/Features/TicketManagement/Policies/TicketAttachmentPolicy.php`

```php
<?php

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Models\User;
use App\Shared\Helpers\JWTHelper;
use Carbon\Carbon;

class TicketAttachmentPolicy
{
    /**
     * Subir attachment: creador del ticket o agent de la compañía.
     */
    public function upload(User $user, Ticket $ticket): bool
    {
        // Creador puede subir
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent de la compañía puede subir
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Subir attachment a response específica: solo autor de la response dentro de 30 min.
     */
    public function uploadToResponse(User $user, TicketResponse $response): bool
    {
        // Debe ser el autor de la response
        if ($response->author_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 30 minutos
        $minutesSinceCreation = Carbon::parse($response->created_at)->diffInMinutes(Carbon::now());
        return $minutesSinceCreation <= 30;
    }

    /**
     * Ver attachments: creador del ticket o agent de la compañía.
     */
    public function viewAny(User $user, Ticket $ticket): bool
    {
        // Creador puede ver
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent de la compañía puede ver
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
        return $companyId && $ticket->company_id === $companyId;
    }

    /**
     * Eliminar attachment: solo uploader dentro de 30 minutos.
     */
    public function delete(User $user, TicketAttachment $attachment): bool
    {
        // Debe ser el uploader
        if ($attachment->uploaded_by_user_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 30 minutos
        $minutesSinceUpload = Carbon::parse($attachment->created_at)->diffInMinutes(Carbon::now());
        return $minutesSinceUpload <= 30;
    }
}
```

#### 5.4 Policy: TicketRatingPolicy
**Path:** `app/Features/TicketManagement/Policies/TicketRatingPolicy.php`

```php
<?php

namespace App\Features\TicketManagement\Policies;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketRating;
use App\Models\User;
use Carbon\Carbon;

class TicketRatingPolicy
{
    /**
     * Crear rating: solo creador del ticket y debe estar resolved/closed.
     */
    public function create(User $user, Ticket $ticket): bool
    {
        return $ticket->created_by_user_id === $user->id
            && in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED]);
    }

    /**
     * Actualizar rating: solo creador dentro de 24 horas.
     */
    public function update(User $user, TicketRating $rating): bool
    {
        // Debe ser el creador
        if ($rating->rated_by_user_id !== $user->id) {
            return false;
        }

        // Debe estar dentro de 24 horas
        $hoursSinceCreation = Carbon::parse($rating->created_at)->diffInHours(Carbon::now());
        return $hoursSinceCreation <= 24;
    }

    /**
     * Ver rating: creador del ticket o agent de la compañía.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Creador puede ver
        if ($ticket->created_by_user_id === $user->id) {
            return true;
        }

        // Agent/Admin de la compañía puede ver
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT')
            ?? JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');

        return $companyId && $ticket->company_id === $companyId;
    }
}
```

### Registro de Policies
**Path:** `app/Providers/AuthServiceProvider.php`

Agregar al array `$policies`:
```php
protected $policies = [
    Ticket::class => TicketPolicy::class,
    TicketResponse::class => TicketResponsePolicy::class,
    TicketAttachment::class => TicketAttachmentPolicy::class,
    TicketRating::class => TicketRatingPolicy::class,
    // CategoryPolicy ya existe
];
```

### Criterio de Éxito Fase 5
No hay tests específicos de Policies, pero estas se validarán cuando implementemos los controllers y ejecutemos los Feature tests.

**Checklist:**
- ✅ Todas las policies creadas
- ✅ Registradas en AuthServiceProvider
- ✅ Usan JWTHelper (no sessions)
- ✅ Respetan reglas de tiempo (30 min, 24h, 30 días)

---

## FASE 6: Resources y Requests - API Layer

### Objetivo
Crear transformadores de respuesta (Resources) y validadores de entrada (Requests).

### Tests que Validan (Indirectamente)
Se validan en Feature tests cuando se implementen los controllers.

### Archivos a Crear

#### 6.1 Resource: TicketResource
**Path:** `app/Features/TicketManagement/Resources/TicketResource.php`

```php
<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'last_response_author_type' => $this->last_response_author_type,

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

            'responses_count' => $this->when(isset($this->responses_count), $this->responses_count),
            'attachments_count' => $this->when(isset($this->attachments_count), $this->attachments_count),

            'first_response_at' => $this->first_response_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

#### 6.2 Resource: TicketListResource (simplificado para listas)
**Path:** `app/Features/TicketManagement/Resources/TicketListResource.php`

```php
<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'title' => $this->title,
            'status' => $this->status->value,
            'last_response_author_type' => $this->last_response_author_type,

            'creator_name' => $this->creator->profile->full_name ?? $this->creator->email,
            'owner_agent_name' => $this->ownerAgent->profile->full_name ?? null,
            'category_name' => $this->category->name,

            'responses_count' => $this->responses_count ?? 0,
            'attachments_count' => $this->attachments_count ?? 0,

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

#### 6.3 Resource: TicketResponseResource
**Path:** `app/Features/TicketManagement/Resources/TicketResponseResource.php`

```php
<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'content' => $this->content,
            'author_type' => $this->author_type->value,

            'author' => $this->whenLoaded('author', function () {
                return [
                    'id' => $this->author->id,
                    'name' => $this->author->profile->full_name ?? $this->author->email,
                    'email' => $this->author->email,
                ];
            }),

            'attachments' => TicketAttachmentResource::collection($this->whenLoaded('attachments')),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

#### 6.4 Resource: TicketAttachmentResource
**Path:** `app/Features/TicketManagement/Resources/TicketAttachmentResource.php`

```php
<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketAttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'response_id' => $this->response_id,
            'file_name' => $this->file_name,
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'download_url' => route('tickets.attachments.download', $this->id),

            'uploaded_by' => $this->whenLoaded('uploader', function () {
                return [
                    'id' => $this->uploader->id,
                    'name' => $this->uploader->profile->full_name ?? $this->uploader->email,
                ];
            }),

            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

#### 6.5 Resource: TicketRatingResource
**Path:** `app/Features/TicketManagement/Resources/TicketRatingResource.php`

```php
<?php

namespace App\Features\TicketManagement\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'rating' => $this->rating,
            'comment' => $this->comment,

            'rated_agent' => $this->whenLoaded('ratedAgent', function () {
                return $this->ratedAgent ? [
                    'id' => $this->ratedAgent->id,
                    'name' => $this->ratedAgent->profile->full_name ?? $this->ratedAgent->email,
                ] : null;
            }),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

#### 6.6 Request: StoreTicketRequest
**Path:** `app/Features/TicketManagement/Requests/StoreTicketRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Requests;

use App\Features\TicketManagement\Models\Category;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return JWTHelper::hasRoleFromJWT('USER');
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:5|max:200',
            'description' => 'required|string|min:10|max:2000',
            'company_id' => 'required|uuid|exists:companies,id',
            'category_id' => [
                'required',
                'uuid',
                'exists:ticketing.categories,id',
                function ($attribute, $value, $fail) {
                    $category = Category::find($value);
                    if ($category && !$category->is_active) {
                        $fail('La categoría seleccionada no está activa.');
                    }
                    if ($category && $category->company_id !== $this->input('company_id')) {
                        $fail('La categoría no pertenece a la compañía seleccionada.');
                    }
                },
            ],
        ];
    }
}
```

#### 6.7 Request: UpdateTicketRequest
**Path:** `app/Features/TicketManagement/Requests/UpdateTicketRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Requests;

use App\Features\TicketManagement\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketPolicy
        return true;
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');

        return [
            'title' => 'sometimes|required|string|min:5|max:200',
            'description' => 'sometimes|required|string|min:10|max:2000',
            'category_id' => [
                'sometimes',
                'required',
                'uuid',
                'exists:ticketing.categories,id',
                function ($attribute, $value, $fail) use ($ticket) {
                    $category = Category::find($value);
                    if ($category && !$category->is_active) {
                        $fail('La categoría seleccionada no está activa.');
                    }
                    if ($category && $category->company_id !== $ticket->company_id) {
                        $fail('La categoría no pertenece a la compañía del ticket.');
                    }
                },
            ],
        ];
    }
}
```

#### 6.8 Request: StoreResponseRequest
**Path:** `app/Features/TicketManagement/Requests/StoreResponseRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketResponsePolicy
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1|max:5000',
        ];
    }
}
```

#### 6.9 Request: UpdateResponseRequest
**Path:** `app/Features/TicketManagement/Requests/UpdateResponseRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketResponsePolicy
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:1|max:5000',
        ];
    }
}
```

#### 6.10 Request: UploadAttachmentRequest
**Path:** `app/Features/TicketManagement/Requests/UploadAttachmentRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Requests;

use App\Features\TicketManagement\Rules\ValidFileType;
use Illuminate\Foundation\Http\FormRequest;

class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketAttachmentPolicy
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB en KB
                new ValidFileType(),
            ],
            'response_id' => 'sometimes|uuid|exists:ticketing.ticket_responses,id',
        ];
    }
}
```

#### 6.11 Request: StoreRatingRequest
**Path:** `app/Features/TicketManagement/Requests/StoreRatingRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketRatingPolicy
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }
}
```

#### 6.12 Request: ReopenTicketRequest
**Path:** `app/Features/TicketManagement/Requests/ReopenTicketRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Requests;

use App\Features\TicketManagement\Rules\CanReopenTicket;
use Illuminate\Foundation\Http\FormRequest;

class ReopenTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketPolicy
        return true;
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');

        return [
            'reason' => 'nullable|string|max:500',
            'ticket_status' => [new CanReopenTicket($ticket)],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Add dummy field for CanReopenTicket rule validation
        $this->merge(['ticket_status' => 'check']);
    }
}
```

#### 6.13 Request: AssignTicketRequest
**Path:** `app/Features/TicketManagement/Requests/AssignTicketRequest.php`

```php
<?php

namespace App\Features\TicketManagement\Requests;

use App\Models\User;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Foundation\Http\FormRequest;

class AssignTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled by TicketPolicy
        return true;
    }

    public function rules(): array
    {
        $ticket = $this->route('ticket');
        $companyId = $ticket->company_id;

        return [
            'new_agent_id' => [
                'required',
                'uuid',
                'exists:users,id',
                function ($attribute, $value, $fail) use ($companyId) {
                    $agent = User::find($value);
                    if (!$agent) {
                        $fail('El agente especificado no existe.');
                        return;
                    }

                    // Validar que tiene rol AGENT en la compañía correcta
                    $hasAgentRole = collect($agent->roles)
                        ->contains(function ($role) use ($companyId) {
                            return $role->code === 'AGENT' && $role->pivot->company_id === $companyId;
                        });

                    if (!$hasAgentRole) {
                        $fail('El usuario especificado no es un agente de esta compañía.');
                    }
                },
            ],
            'note' => 'nullable|string|max:500',
        ];
    }
}
```

### Criterio de Éxito Fase 6
No hay tests específicos, pero los archivos deben estar creados y listos para usarse en controllers.

**Checklist:**
- ✅ Todos los Resources creados
- ✅ Todos los Requests creados
- ✅ Requests usan JWTHelper donde corresponde
- ✅ Validaciones custom integradas (ValidFileType, CanReopenTicket)

---

## FASE 7: Controllers - Tickets CRUD

### Objetivo
Implementar endpoints CRUD de tickets (crear, listar, ver, actualizar, eliminar).

### Tests a Pasar (Feature Tests)
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/CRUD/
```

**Tests específicos (70 tests):**
- CreateTicketTest (16 tests)
- ListTicketsTest (25 tests)
- GetTicketTest (11 tests)
- UpdateTicketTest (11 tests)
- DeleteTicketTest (8 tests)

### Archivos a Crear

#### 7.1 Controller: TicketController
**Path:** `app/Features/TicketManagement/Controllers/TicketController.php`

```php
<?php

namespace App\Features\TicketManagement\Controllers;

use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Requests\StoreTicketRequest;
use App\Features\TicketManagement\Requests\UpdateTicketRequest;
use App\Features\TicketManagement\Resources\TicketListResource;
use App\Features\TicketManagement\Resources\TicketResource;
use App\Features\TicketManagement\Services\TicketService;
use App\Http\Controllers\Controller;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(
        private TicketService $ticketService
    ) {}

    /**
     * POST /api/v1/tickets
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        $ticket = $this->ticketService->create($request->validated(), $user);

        return response()->json([
            'message' => 'Ticket creado exitosamente',
            'data' => new TicketResource($ticket),
        ], 201);
    }

    /**
     * GET /api/v1/tickets
     */
    public function index(Request $request): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();

        $filters = $request->only([
            'status',
            'category_id',
            'owner_agent_id',
            'created_by_user_id',
            'last_response_author_type',
            'search',
            'created_from',
            'created_to',
            'sort_by',
            'sort_order',
            'per_page',
        ]);

        $tickets = $this->ticketService->list($filters, $user);

        return response()->json([
            'data' => TicketListResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'total' => $tickets->total(),
                'per_page' => $tickets->perPage(),
                'last_page' => $tickets->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/tickets/{ticket}
     */
    public function show(Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'creator.profile',
            'ownerAgent.profile',
            'company',
            'category',
        ]);
        $ticket->loadCount(['responses', 'attachments']);

        return response()->json([
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * PATCH /api/v1/tickets/{ticket}
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $ticket = $this->ticketService->update($ticket, $request->validated());

        return response()->json([
            'message' => 'Ticket actualizado exitosamente',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * DELETE /api/v1/tickets/{ticket}
     */
    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->authorize('delete', $ticket);

        $this->ticketService->delete($ticket);

        return response()->json([
            'message' => 'Ticket eliminado exitosamente',
        ]);
    }
}
```

### Routes
**Path:** `routes/api_v1.php`

Agregar dentro del grupo de TicketManagement:
```php
// Tickets CRUD
Route::middleware(['auth.jwt'])->group(function () {
    Route::post('/tickets', [TicketController::class, 'store'])
        ->middleware('role:USER');

    Route::get('/tickets', [TicketController::class, 'index']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);

    Route::patch('/tickets/{ticket}', [TicketController::class, 'update']);

    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])
        ->middleware('role:COMPANY_ADMIN');
});
```

### Criterio de Éxito Fase 7
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/CRUD/
# Los 70 tests de CRUD deben pasar GREEN
```

---

## FASE 8: Controllers - Responses

### Objetivo
Implementar endpoints de respuestas (crear, listar, actualizar, eliminar).

### Tests a Pasar (Feature Tests)
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Responses/
```

**Tests específicos (48 tests):**
- CreateResponseTest (23 tests) - incluye tests de triggers
- ListResponsesTest (8 tests)
- UpdateResponseTest (10 tests)
- DeleteResponseTest (7 tests)

### Archivos a Crear

#### 8.1 Controller: TicketResponseController
**Path:** `app/Features/TicketManagement/Controllers/TicketResponseController.php`

```php
<?php

namespace App\Features\TicketManagement\Controllers;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Requests\StoreResponseRequest;
use App\Features\TicketManagement\Requests\UpdateResponseRequest;
use App\Features\TicketManagement\Resources\TicketResponseResource;
use App\Features\TicketManagement\Services\ResponseService;
use App\Http\Controllers\Controller;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;

class TicketResponseController extends Controller
{
    public function __construct(
        private ResponseService $responseService
    ) {}

    /**
     * POST /api/v1/tickets/{ticket}/responses
     */
    public function store(StoreResponseRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('create', [TicketResponse::class, $ticket]);

        // Validar que el ticket no esté cerrado
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'No se puede responder a un ticket cerrado',
            ], 403);
        }

        $user = JWTHelper::getAuthenticatedUser();
        $response = $this->responseService->create($ticket, $request->validated(), $user);

        // Refrescar el ticket para obtener cambios del trigger
        $ticket->refresh();

        return response()->json([
            'message' => 'Respuesta creada exitosamente',
            'data' => new TicketResponseResource($response),
        ], 201);
    }

    /**
     * GET /api/v1/tickets/{ticket}/responses
     */
    public function index(Ticket $ticket): JsonResponse
    {
        $this->authorize('viewAny', [TicketResponse::class, $ticket]);

        $responses = $this->responseService->list($ticket);

        return response()->json([
            'data' => TicketResponseResource::collection($responses),
        ]);
    }

    /**
     * PATCH /api/v1/tickets/{ticket}/responses/{response}
     */
    public function update(
        UpdateResponseRequest $request,
        Ticket $ticket,
        TicketResponse $response
    ): JsonResponse {
        $this->authorize('update', $response);

        // Validar que el ticket no esté cerrado
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'No se puede actualizar respuesta de un ticket cerrado',
            ], 403);
        }

        $response = $this->responseService->update($response, $request->validated());

        return response()->json([
            'message' => 'Respuesta actualizada exitosamente',
            'data' => new TicketResponseResource($response),
        ]);
    }

    /**
     * DELETE /api/v1/tickets/{ticket}/responses/{response}
     */
    public function destroy(Ticket $ticket, TicketResponse $response): JsonResponse
    {
        $this->authorize('delete', $response);

        // Validar que el ticket no esté cerrado
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'No se puede eliminar respuesta de un ticket cerrado',
            ], 403);
        }

        $this->responseService->delete($response);

        return response()->json([
            'message' => 'Respuesta eliminada exitosamente',
        ]);
    }
}
```

### Routes
Agregar a `routes/api_v1.php`:
```php
// Responses
Route::middleware(['auth.jwt'])->group(function () {
    Route::post('/tickets/{ticket}/responses', [TicketResponseController::class, 'store']);
    Route::get('/tickets/{ticket}/responses', [TicketResponseController::class, 'index']);
    Route::patch('/tickets/{ticket}/responses/{response}', [TicketResponseController::class, 'update']);
    Route::delete('/tickets/{ticket}/responses/{response}', [TicketResponseController::class, 'destroy']);
});
```

### Criterio de Éxito Fase 8
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Responses/
# Los 48 tests de Responses deben pasar GREEN
```

---

## FASE 9: Controllers - Attachments

### Objetivo
Implementar endpoints de attachments (subir, listar, eliminar, descargar).

### Tests a Pasar (Feature Tests)
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Attachments/
```

**Tests específicos (37 tests):**
- AttachmentStructureTest (4 tests) - validaciones de BD
- UploadAttachmentTest (15 tests)
- UploadAttachmentToResponseTest (8 tests)
- ListAttachmentsTest (6 tests)
- DeleteAttachmentTest (8 tests)

### Archivos a Crear

#### 9.1 Controller: TicketAttachmentController
**Path:** `app/Features/TicketManagement/Controllers/TicketAttachmentController.php`

```php
<?php

namespace App\Features\TicketManagement\Controllers;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketAttachment;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\TicketManagement\Requests\UploadAttachmentRequest;
use App\Features\TicketManagement\Resources\TicketAttachmentResource;
use App\Features\TicketManagement\Services\AttachmentService;
use App\Http\Controllers\Controller;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function __construct(
        private AttachmentService $attachmentService
    ) {}

    /**
     * POST /api/v1/tickets/{ticket}/attachments
     */
    public function store(UploadAttachmentRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('upload', [TicketAttachment::class, $ticket]);

        // Validar que el ticket no esté cerrado
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'No se pueden subir archivos a un ticket cerrado',
            ], 403);
        }

        $user = JWTHelper::getAuthenticatedUser();
        $file = $request->file('file');

        // Si viene response_id, validar y obtener la response
        $response = null;
        if ($request->has('response_id')) {
            $response = TicketResponse::findOrFail($request->input('response_id'));

            // Validar que la response pertenece al ticket
            if ($response->ticket_id !== $ticket->id) {
                return response()->json([
                    'code' => 'INVALID_RESPONSE',
                    'message' => 'La respuesta no pertenece a este ticket',
                ], 422);
            }

            // Validar permisos para subir a response específica
            $this->authorize('uploadToResponse', [TicketAttachment::class, $response]);
        }

        $attachment = $this->attachmentService->upload($ticket, $file, $user, $response);

        return response()->json([
            'message' => 'Archivo subido exitosamente',
            'data' => new TicketAttachmentResource($attachment),
        ], 201);
    }

    /**
     * GET /api/v1/tickets/{ticket}/attachments
     */
    public function index(Ticket $ticket): JsonResponse
    {
        $this->authorize('viewAny', [TicketAttachment::class, $ticket]);

        $attachments = $this->attachmentService->list($ticket);

        return response()->json([
            'data' => TicketAttachmentResource::collection($attachments),
        ]);
    }

    /**
     * DELETE /api/v1/tickets/attachments/{attachment}
     */
    public function destroy(TicketAttachment $attachment): JsonResponse
    {
        $this->authorize('delete', $attachment);

        // Validar que el ticket no esté cerrado
        $ticket = $attachment->ticket;
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_CLOSED',
                'message' => 'No se pueden eliminar archivos de un ticket cerrado',
            ], 403);
        }

        $this->attachmentService->delete($attachment);

        return response()->json([
            'message' => 'Archivo eliminado exitosamente',
        ]);
    }

    /**
     * GET /api/v1/tickets/attachments/{attachment}/download
     */
    public function download(TicketAttachment $attachment): StreamedResponse
    {
        $ticket = $attachment->ticket;
        $this->authorize('viewAny', [TicketAttachment::class, $ticket]);

        $path = $attachment->file_path;

        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }

        return Storage::disk('local')->download($path, $attachment->file_name);
    }
}
```

### Routes
Agregar a `routes/api_v1.php`:
```php
// Attachments
Route::middleware(['auth.jwt'])->group(function () {
    Route::post('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'store']);
    Route::get('/tickets/{ticket}/attachments', [TicketAttachmentController::class, 'index']);
    Route::delete('/tickets/attachments/{attachment}', [TicketAttachmentController::class, 'destroy']);
    Route::get('/tickets/attachments/{attachment}/download', [TicketAttachmentController::class, 'download'])
        ->name('tickets.attachments.download');
});
```

### Criterio de Éxito Fase 9
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Attachments/
# Los 37 tests de Attachments deben pasar GREEN
```

---

## FASE 10: Controllers - Ticket Actions (Assign, Resolve, Close, Reopen)

### Objetivo
Implementar endpoints de acciones sobre tickets (asignar, resolver, cerrar, reabrir).

### Tests a Pasar (Feature Tests)
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/Actions/
```

**Tests específicos (45 tests):**
- AssignTicketTest (10 tests)
- ResolveTicketTest (11 tests)
- CloseTicketTest (11 tests)
- ReopenTicketTest (13 tests)

### Archivos a Crear

#### 10.1 Service: TicketActionService
**Path:** `app/Features/TicketManagement/Services/TicketActionService.php`

```php
<?php

namespace App\Features\TicketManagement\Services;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use Illuminate\Support\Facades\DB;

class TicketActionService
{
    /**
     * Asignar ticket a un nuevo agent.
     */
    public function assign(Ticket $ticket, string $newAgentId, ?string $note = null): Ticket
    {
        DB::transaction(function () use ($ticket, $newAgentId, $note) {
            $ticket->update([
                'owner_agent_id' => $newAgentId,
            ]);

            // TODO: Dispatch TicketAssignedEvent con $note
        });

        return $ticket->fresh(['ownerAgent', 'creator', 'category', 'company']);
    }

    /**
     * Resolver ticket.
     */
    public function resolve(Ticket $ticket, ?string $resolutionNote = null): Ticket
    {
        DB::transaction(function () use ($ticket, $resolutionNote) {
            $ticket->update([
                'status' => TicketStatus::RESOLVED,
            ]);

            // TODO: Dispatch TicketResolvedEvent con $resolutionNote
        });

        return $ticket->fresh(['ownerAgent', 'creator', 'category', 'company']);
    }

    /**
     * Cerrar ticket.
     */
    public function close(Ticket $ticket): Ticket
    {
        DB::transaction(function () use ($ticket) {
            $ticket->update([
                'status' => TicketStatus::CLOSED,
                'closed_at' => now(),
            ]);

            // TODO: Dispatch TicketClosedEvent
        });

        return $ticket->fresh(['ownerAgent', 'creator', 'category', 'company']);
    }

    /**
     * Reabrir ticket.
     */
    public function reopen(Ticket $ticket, ?string $reopenReason = null): Ticket
    {
        DB::transaction(function () use ($ticket, $reopenReason) {
            $ticket->update([
                'status' => TicketStatus::PENDING,
                'closed_at' => null,
            ]);

            // TODO: Dispatch TicketReopenedEvent con $reopenReason
        });

        return $ticket->fresh(['ownerAgent', 'creator', 'category', 'company']);
    }
}
```

#### 10.2 Controller: TicketActionController
**Path:** `app/Features/TicketManagement/Controllers/TicketActionController.php`

```php
<?php

namespace App\Features\TicketManagement\Controllers;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Requests\AssignTicketRequest;
use App\Features\TicketManagement\Requests\ReopenTicketRequest;
use App\Features\TicketManagement\Resources\TicketResource;
use App\Features\TicketManagement\Services\TicketActionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketActionController extends Controller
{
    public function __construct(
        private TicketActionService $actionService
    ) {}

    /**
     * POST /api/v1/tickets/{ticket}/assign
     */
    public function assign(AssignTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('assign', $ticket);

        $data = $request->validated();
        $ticket = $this->actionService->assign(
            $ticket,
            $data['new_agent_id'],
            $data['note'] ?? null
        );

        return response()->json([
            'message' => 'Ticket asignado exitosamente',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * POST /api/v1/tickets/{ticket}/resolve
     */
    public function resolve(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('resolve', $ticket);

        // Validar que no esté ya resolved o closed
        if (in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED])) {
            return response()->json([
                'code' => 'TICKET_ALREADY_RESOLVED_OR_CLOSED',
                'message' => 'El ticket ya está resuelto o cerrado',
            ], 422);
        }

        $request->validate([
            'resolution_note' => 'nullable|string|max:1000',
        ]);

        $ticket = $this->actionService->resolve(
            $ticket,
            $request->input('resolution_note')
        );

        return response()->json([
            'message' => 'Ticket resuelto exitosamente',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * POST /api/v1/tickets/{ticket}/close
     */
    public function close(Ticket $ticket): JsonResponse
    {
        $this->authorize('close', $ticket);

        // Validar que no esté ya cerrado
        if ($ticket->status === TicketStatus::CLOSED) {
            return response()->json([
                'code' => 'TICKET_ALREADY_CLOSED',
                'message' => 'El ticket ya está cerrado',
            ], 422);
        }

        $ticket = $this->actionService->close($ticket);

        return response()->json([
            'message' => 'Ticket cerrado exitosamente',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * POST /api/v1/tickets/{ticket}/reopen
     */
    public function reopen(ReopenTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('reopen', $ticket);

        // Validar que esté resolved o closed
        if (!in_array($ticket->status, [TicketStatus::RESOLVED, TicketStatus::CLOSED])) {
            return response()->json([
                'code' => 'TICKET_NOT_CLOSED',
                'message' => 'Solo se pueden reabrir tickets resueltos o cerrados',
            ], 422);
        }

        $ticket = $this->actionService->reopen(
            $ticket,
            $request->input('reason')
        );

        return response()->json([
            'message' => 'Ticket reabierto exitosamente',
            'data' => new TicketResource($ticket),
        ]);
    }
}
```

### Routes
Agregar a `routes/api_v1.php`:
```php
// Ticket Actions
Route::middleware(['auth.jwt'])->group(function () {
    Route::post('/tickets/{ticket}/assign', [TicketActionController::class, 'assign'])
        ->middleware('role:AGENT');

    Route::post('/tickets/{ticket}/resolve', [TicketActionController::class, 'resolve'])
        ->middleware('role:AGENT');

    Route::post('/tickets/{ticket}/close', [TicketActionController::class, 'close']);

    Route::post('/tickets/{ticket}/reopen', [TicketActionController::class, 'reopen']);
});
```

### Criterio de Éxito Fase 10
```bash
docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/Actions/
# Los 45 tests de Actions deben pasar GREEN
```

---

## FASE 11: Job - Auto-Close Resolved Tickets

### Objetivo
Implementar job que cierra automáticamente tickets resueltos después de 7 días.

### Tests a Pasar (Unit Test)
```bash
docker compose exec app php artisan test tests/Unit/TicketManagement/Jobs/AutoCloseResolvedTicketsJobTest.php
```

**Tests específicos (5 tests):**
- ✅ closes tickets resolved more than 7 days ago
- ✅ does not close tickets resolved less than 7 days ago
- ✅ sets closed at timestamp
- ✅ only affects resolved tickets
- ✅ logs closed tickets count

### Archivos a Crear

#### 11.1 Job: AutoCloseResolvedTicketsJob
**Path:** `app/Features/TicketManagement/Jobs/AutoCloseResolvedTicketsJob.php`

```php
<?php

namespace App\Features\TicketManagement\Jobs;

use App\Features\TicketManagement\Enums\TicketStatus;
use App\Features\TicketManagement\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoCloseResolvedTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DAYS_UNTIL_AUTO_CLOSE = 7;

    public function handle(): void
    {
        $cutoffDate = Carbon::now()->subDays(self::DAYS_UNTIL_AUTO_CLOSE);

        $closedCount = Ticket::where('status', TicketStatus::RESOLVED)
            ->where('updated_at', '<=', $cutoffDate)
            ->update([
                'status' => TicketStatus::CLOSED,
                'closed_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

        if ($closedCount > 0) {
            Log::info("Auto-closed {$closedCount} resolved tickets older than 7 days");
        }
    }
}
```

#### 11.2 Schedule (opcional para ejecución automática)
**Path:** `app/Console/Kernel.php`

Agregar en el método `schedule()`:
```php
protected function schedule(Schedule $schedule): void
{
    // Auto-cerrar tickets resueltos hace más de 7 días (diario a las 2 AM)
    $schedule->job(new AutoCloseResolvedTicketsJob())
        ->dailyAt('02:00')
        ->name('auto-close-resolved-tickets')
        ->withoutOverlapping();
}
```

### Criterio de Éxito Fase 11
```bash
docker compose exec app php artisan test tests/Unit/TicketManagement/Jobs/AutoCloseResolvedTicketsJobTest.php
# Los 5 tests del Job deben pasar GREEN
```

---

## Resumen de Progreso

### Matriz de Tests por Fase

| Fase | Grupo de Tests | Cantidad | Comando Verificación |
|------|----------------|----------|----------------------|
| 1 | Migrations + Triggers | N/A | `php artisan migrate` |
| 2 | Unit/Models | 8 | `docker compose exec app php artisan test tests/Unit/TicketManagement/Models/` |
| 3 | Unit/Rules | 8 | `docker compose exec app php artisan test tests/Unit/TicketManagement/Rules/` |
| 4 | Unit/Services | 11 | `docker compose exec app php artisan test tests/Unit/TicketManagement/Services/` |
| 5 | Policies | N/A | Se validan en Feature tests |
| 6 | Resources/Requests | N/A | Se validan en Feature tests |
| 7 | Feature/CRUD | 70 | `docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/CRUD/` |
| 8 | Feature/Responses | 48 | `docker compose exec app php artisan test tests/Feature/TicketManagement/Responses/` |
| 9 | Feature/Attachments | 37 | `docker compose exec app php artisan test tests/Feature/TicketManagement/Attachments/` |
| 10 | Feature/Actions | 45 | `docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/Actions/` |
| 11 | Unit/Jobs | 5 | `docker compose exec app php artisan test tests/Unit/TicketManagement/Jobs/` |
| **TOTAL** | **241 tests** | **241** | `docker compose exec app php artisan test tests/Feature/TicketManagement tests/Unit/TicketManagement` |

### Checklist Final

#### Base de Datos
- [ ] Migration: tickets table
- [ ] Migration: ticket_responses table
- [ ] Migration: ticket_attachments table
- [ ] Migration: ticket_ratings table
- [ ] Migration: trigger auto-asignación
- [ ] Migration: trigger PENDING → OPEN

#### Models
- [ ] Ticket model
- [ ] TicketResponse model
- [ ] TicketAttachment model
- [ ] TicketRating model
- [ ] Enums verificados (TicketStatus, AuthorType)

#### Services
- [ ] TicketCodeGenerator
- [ ] TicketService
- [ ] ResponseService
- [ ] AttachmentService
- [ ] RatingService
- [ ] TicketActionService

#### Rules
- [ ] ValidFileType
- [ ] CanReopenTicket

#### Policies
- [ ] TicketPolicy
- [ ] TicketResponsePolicy
- [ ] TicketAttachmentPolicy
- [ ] TicketRatingPolicy
- [ ] Registradas en AuthServiceProvider

#### Resources
- [ ] TicketResource
- [ ] TicketListResource
- [ ] TicketResponseResource
- [ ] TicketAttachmentResource
- [ ] TicketRatingResource

#### Requests
- [ ] StoreTicketRequest
- [ ] UpdateTicketRequest
- [ ] StoreResponseRequest
- [ ] UpdateResponseRequest
- [ ] UploadAttachmentRequest
- [ ] StoreRatingRequest
- [ ] AssignTicketRequest
- [ ] ReopenTicketRequest

#### Controllers
- [ ] TicketController (CRUD)
- [ ] TicketResponseController
- [ ] TicketAttachmentController
- [ ] TicketActionController

#### Jobs
- [ ] AutoCloseResolvedTicketsJob
- [ ] Schedule configurado (opcional)

#### Routes
- [ ] Todas las rutas registradas en api_v1.php
- [ ] Middlewares correctos aplicados
- [ ] Named routes donde corresponde

#### Exceptions
- [ ] MaxAttachmentsExceededException
- [ ] TicketCannotBeDeletedException
- [ ] TicketNotRateableException

---

## Orden de Ejecución Recomendado

### Para Agentes Especializados

**Agente 1: Database & Models**
- Implementar Fase 1 (Migrations + Triggers)
- Implementar Fase 2 (Models)
- Verificar: `docker compose exec app php artisan test tests/Unit/TicketManagement/Models/`

**Agente 2: Validation Layer**
- Implementar Fase 3 (Rules)
- Verificar: `docker compose exec app php artisan test tests/Unit/TicketManagement/Rules/`

**Agente 3: Business Logic**
- Implementar Fase 4 (Services)
- Implementar Exceptions
- Verificar: `docker compose exec app php artisan test tests/Unit/TicketManagement/Services/`

**Agente 4: Authorization**
- Implementar Fase 5 (Policies)
- Registrar en AuthServiceProvider

**Agente 5: API Layer Preparation**
- Implementar Fase 6 (Resources + Requests)

**Agente 6: Tickets CRUD Endpoints**
- Implementar Fase 7 (TicketController + Routes)
- Verificar: `docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/CRUD/`

**Agente 7: Responses Endpoints**
- Implementar Fase 8 (TicketResponseController + Routes)
- Verificar: `docker compose exec app php artisan test tests/Feature/TicketManagement/Responses/`

**Agente 8: Attachments Endpoints**
- Implementar Fase 9 (TicketAttachmentController + Routes)
- Verificar: `docker compose exec app php artisan test tests/Feature/TicketManagement/Attachments/`

**Agente 9: Actions Endpoints**
- Implementar Fase 10 (TicketActionService + TicketActionController + Routes)
- Verificar: `docker compose exec app php artisan test tests/Feature/TicketManagement/Tickets/Actions/`

**Agente 10: Background Jobs**
- Implementar Fase 11 (AutoCloseResolvedTicketsJob)
- Verificar: `docker compose exec app php artisan test tests/Unit/TicketManagement/Jobs/`

### Verificación Final
```bash
# Ejecutar TODOS los tests de esta iteración
docker compose exec app php artisan test tests/Feature/TicketManagement tests/Unit/TicketManagement

# Resultado esperado: 241 tests GREEN
```

---

## Notas Importantes para Agentes

1. **SIEMPRE usar Docker**: Nunca ejecutar comandos con Herd
2. **JWT Stateless**: Usar `JWTHelper` en lugar de `auth()->user()` o sessions
3. **Triggers de BD**: No reimplementar lógica que ya hacen los triggers (auto-asignación, cambios de estado)
4. **Validaciones de tiempo**: 30 minutos para editar/eliminar, 30 días para reabrir (user), 7 días para auto-cierre
5. **Multi-tenancy**: Siempre filtrar por `company_id` usando `JWTHelper::getCompanyIdFromJWT()`
6. **Policies > Middlewares**: Usar Policies para autorización por recurso
7. **Form Requests**: Toda validación de entrada va en Form Requests
8. **Resources**: Toda transformación de salida va en Resources
9. **Services**: Lógica de negocio va en Services, no en Controllers
10. **Tests incrementales**: Ejecutar tests después de cada fase antes de continuar

---

## Contexto de Roles y Permisos

### Matriz de Permisos por Acción

| Acción | USER | AGENT | COMPANY_ADMIN |
|--------|------|-------|---------------|
| Crear ticket | ✅ | ❌ | ❌ |
| Ver propio ticket | ✅ | - | - |
| Ver tickets de compañía | ❌ | ✅ | ✅ |
| Editar ticket (OPEN) | ✅ (propio) | ✅ (compañía) | ✅ (compañía) |
| Editar ticket (otros estados) | ❌ | ✅ (compañía) | ✅ (compañía) |
| Eliminar ticket | ❌ | ❌ | ✅ (solo CLOSED) |
| Responder ticket | ✅ (propio) | ✅ (compañía) | - |
| Asignar ticket | ❌ | ✅ | - |
| Resolver ticket | ❌ | ✅ | - |
| Cerrar ticket | ✅ (propio, solo RESOLVED) | ✅ (compañía) | ✅ (compañía) |
| Reabrir ticket | ✅ (propio, <30 días) | ✅ (compañía, sin límite) | - |
| Subir attachment | ✅ (propio) | ✅ (compañía) | - |
| Calificar ticket | ✅ (propio, RESOLVED/CLOSED) | ❌ | ❌ |

---

## Fin del Plan
