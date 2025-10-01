# 📁 GUÍA DE ESTRUCTURA DE CARPETAS - Helpdesk System

**Proyecto:** Sistema Helpdesk Feature-First
**Framework:** Laravel 12 + React 18 + GraphQL
**Fecha:** Septiembre 2025
**Audiencia:** Desarrolladores en aprendizaje

---

## 🎯 FILOSOFÍA: Arquitectura Feature-First Pura

Este proyecto usa **Feature-First puro**: TODO el código relacionado con un feature está dentro de su carpeta.

**Única excepción:** La carpeta `tests/` queda fuera de `Features/` por convención de Laravel (`php artisan test` espera que los tests estén en la raíz del proyecto).

### ¿Qué significa Feature-First?

**Laravel Tradicional (por capas):**
```
app/
├── Models/          (TODOS los models juntos)
├── Services/        (TODOS los services juntos)
├── Controllers/     (TODOS los controllers juntos)
```

**Feature-First (por dominio):**
```
app/Features/
├── Authentication/  (TODO de autenticación junto)
│   ├── Models/
│   ├── Services/
│   └── GraphQL/
├── UserManagement/  (TODO de usuarios junto)
│   ├── Models/
│   ├── Services/
│   └── GraphQL/
```

**Ventaja:** Cuando trabajas en login, TODOS los archivos de login están en `Authentication/`. No tienes que saltar entre carpetas.

---

## 📦 ESTRUCTURA COMPLETA DEL PROYECTO (FEATURE-FIRST)

```
helpdesk/
├── app/
│   ├── Shared/              # 🟢 Código compartido entre features
│   └── Features/            # 🟠 TODO por feature (Feature-First puro)
│       ├── Authentication/
│       │   ├── Services/    ✅ Lógica de negocio
│       │   ├── Models/      ✅ Modelos Eloquent
│       │   ├── GraphQL/     ✅ Resolvers y schemas
│       │   ├── Policies/    ✅ Autorización
│       │   ├── Events/      ✅ Eventos del dominio
│       │   ├── Listeners/   ✅ Event listeners
│       │   ├── Jobs/        ✅ Tareas asíncronas
│       │   └── Database/    ✅ Migrations, Seeders, Factories
│       │       ├── Migrations/
│       │       ├── Seeders/
│       │       └── Factories/
│       ├── UserManagement/
│       │   └── Database/    ✅ Todo dentro del feature
│       └── CompanyManagement/
│           └── Database/    ✅ Todo dentro del feature
│
├── tests/                   # 🟣 ÚNICA EXCEPCIÓN: Tests fuera de Features
│   ├── Feature/             #    (por convención de Laravel)
│   │   ├── Authentication/  ✅ Tests de integración por feature
│   │   ├── UserManagement/
│   │   └── CompanyManagement/
│   └── Unit/
│       └── Services/
│           ├── Authentication/  ✅ Tests unitarios por feature
│           ├── UserManagement/
│           └── CompanyManagement/
│
├── graphql/                 # 🟡 Schemas GraphQL compartidos
│   ├── schema.graphql       # Schema principal
│   └── shared/              # Scalars, directives, enums, etc.
│
└── documentacion/           # 📄 Documentación del proyecto
```

---

## 🟢 APP/SHARED/ - Código Compartido

**Propósito:** Código que usan **TODOS** los features (o varios).

### 📂 `app/Shared/Services/`

**¿Para qué?** Servicios de lógica de negocio que usan múltiples features.

**Ejemplo:**
```php
// app/Shared/Services/CodeGeneratorService.php
class CodeGeneratorService
{
    public function generateUserCode(): string
    {
        return 'USR-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public function generateTicketCode(): string
    {
        return 'TKT-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, STR_PAD_LEFT);
    }
}
```

**¿Cuándo usarlo?**
- ✅ El código es usado por 2+ features (ej: generar códigos para Users Y Tickets)
- ❌ NO uses para lógica específica de UN feature (va en ese feature)

---

### 📂 `app/Shared/Models/`

**¿Para qué?** Modelos Eloquent compartidos por múltiples features.

**⚠️ CUIDADO:** En general, los modelos deberían ir en cada feature. Solo pon aquí si es un modelo MUY genérico.

**Ejemplo (poco común):**
```php
// app/Shared/Models/AuditLog.php
class AuditLog extends Model
{
    // Usado por TODOS los features para logging
}
```

**Regla general:** ❌ Evita usar esta carpeta, pon modelos en cada feature.

---

### 📂 `app/Shared/Traits/`

**¿Para qué?** Código reutilizable que se añade a Models o clases con `use`.

**Ejemplo:**
```php
// app/Shared/Traits/HasUuid.php
trait HasUuid
{
    protected static function bootHasUuid()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid();
            }
        });
    }
}

// Uso en un Model:
class User extends Model
{
    use HasUuid;  // Ahora User tiene UUID automático
}
```

**Ejemplos de traits útiles:**
- `HasUuid` - Asigna UUID automático al crear el modelo
- `Auditable` - Registra automáticamente cambios en audit_logs
- `BelongsToCompany` - Agrega scope para filtrar por empresa
- `SoftDeletes` - Ya viene con Laravel (borrado lógico)

---

### 📂 `app/Shared/Helpers/`

**¿Para qué?** Funciones helper globales (sin clase).

**Ejemplo:**
```php
// app/Shared/Helpers/StringHelper.php
class StringHelper
{
    public static function slugify(string $text): string
    {
        return Str::slug($text);
    }

    public static function maskEmail(string $email): string
    {
        // juan@ejemplo.com → ju**@ejemplo.com
        $parts = explode('@', $email);
        return substr($parts[0], 0, 2) . '**@' . $parts[1];
    }
}

// Uso:
$slug = StringHelper::slugify('Mi Título!');  // "mi-titulo"
```

**¿Cuándo usarlo?**
- ✅ Funciones simples sin estado
- ✅ Usadas en múltiples lugares
- ❌ NO uses para lógica compleja (crea un Service)

---

### 📂 `app/Shared/Exceptions/`

**¿Para qué?** Excepciones personalizadas del sistema.

**Ejemplo:**
```php
// app/Shared/Exceptions/BusinessLogicException.php
class BusinessLogicException extends Exception
{
    public static function invalidUserStatus(string $status): self
    {
        return new self("Estado de usuario inválido: {$status}");
    }
}

// Uso:
if (!in_array($status, ['active', 'suspended'])) {
    throw BusinessLogicException::invalidUserStatus($status);
}
```

**Ventaja:** Mensajes de error centralizados y reutilizables.

---

### 📂 `app/Shared/Enums/`

**¿Para qué?** Enumeraciones (valores fijos) del sistema.

**Ejemplo:**
```php
// app/Shared/Enums/UserStatus.php
enum UserStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case DELETED = 'deleted';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Activo',
            self::SUSPENDED => 'Suspendido',
            self::DELETED => 'Eliminado',
        };
    }
}

// Uso:
$user->status = UserStatus::ACTIVE;
echo UserStatus::ACTIVE->label();  // "Activo"
```

**¿Cuándo usarlo?**
- ✅ Valores fijos que usan múltiples features (UserStatus, TicketStatus)
- ✅ Prevenir strings mágicos (`'active'` → `UserStatus::ACTIVE`)

---

### 📂 `app/Shared/Constants/`

**¿Para qué?** Constantes del sistema (números mágicos, límites, etc.).

**Ejemplo:**
```php
// app/Shared/Constants/LimitsConstants.php
class LimitsConstants
{
    public const MAX_UPLOAD_SIZE_MB = 10;
    public const MAX_TICKETS_PER_PAGE = 50;
    public const SESSION_TIMEOUT_MINUTES = 60;
}

// Uso:
if ($file->size > LimitsConstants::MAX_UPLOAD_SIZE_MB * 1024 * 1024) {
    throw new Exception('Archivo muy grande');
}
```

---

### 📂 `app/Shared/GraphQL/Scalars/`

**¿Para qué?** Tipos de datos custom para GraphQL (más allá de String, Int, Boolean).

**Ejemplo:**
```php
// app/Shared/GraphQL/Scalars/UUID.php
class UUID extends ScalarType
{
    public function parseValue($value): string
    {
        if (!Uuid::isValid($value)) {
            throw new Error('UUID inválido');
        }
        return $value;
    }
}
```

**En el schema GraphQL:**
```graphql
scalar UUID

type User {
  id: UUID!  # En lugar de String!
}
```

**Scalars incluidos en tu proyecto:**
- `UUID` - Validación de UUID v4
- `Email` - Validación de emails
- `URL` - Validación de URLs
- `PhoneNumber` - Validación formato E.164
- `HexColor` - Validación de colores (#FF5733)
- `JSON` - Objetos JSON arbitrarios

---

### 📂 `app/Shared/GraphQL/Directives/`

**¿Para qué?** Directivas custom de GraphQL (validaciones, autorización, etc.).

**Ejemplo:**
```php
// app/Shared/GraphQL/Directives/CompanyDirective.php
class CompanyDirective extends BaseDirective implements FieldMiddleware
{
    // Valida que el usuario tenga acceso a la empresa
}
```

**En el schema:**
```graphql
type Query {
  companyTickets(companyId: UUID!): [Ticket!]!
    @company(requireOwnership: true)  # Valida acceso a empresa
}
```

**Directivas incluidas:**
- `@company` - Valida acceso a empresa
- `@audit` - Registra operación en logs
- `@rateLimit` - Limita requests por tiempo

---

### 📂 `app/Shared/GraphQL/Types/`

**¿Para qué?** Types GraphQL compartidos (respuestas genéricas).

**Ejemplo:**
```graphql
# app/Shared/GraphQL/Types/ErrorPayload.graphql
type ErrorPayload {
  message: String!
  code: String!
  field: String
}
```

---

### 📂 `app/Shared/GraphQL/DataLoaders/`

**¿Para qué?** Prevenir problema N+1 de queries (cargar datos de forma eficiente).

**Problema N+1:**
```php
// ❌ MAL: 1 query por cada ticket (N+1 queries)
foreach ($tickets as $ticket) {
    $user = $ticket->user;  // Query individual
}
```

**Solución con DataLoader:**
```php
// ✅ BIEN: 1 query para todos los users
$userLoader = new UserDataLoader();
foreach ($tickets as $ticket) {
    $user = $userLoader->load($ticket->user_id);  // Batch query
}
```

**Cuándo usarlo:** Cuando cargas relaciones en GraphQL.

---

### 📂 `app/Shared/GraphQL/Queries/`

**¿Para qué?** Queries GraphQL del **sistema base** (no de features).

**Ejemplo:**
```php
// app/Shared/GraphQL/Queries/PingQuery.php
class PingQuery
{
    public function __invoke(): string
    {
        return 'pong';
    }
}
```

**Queries incluidos:**
- `ping` - Health check simple
- `version` - Versión del API
- `health` - Estado de servicios (DB, Redis, etc.)

---

### 📂 `app/Shared/GraphQL/Mutations/`

**¿Para qué?** Clase base para mutations (herencia).

**Ejemplo:**
```php
// app/Shared/GraphQL/Mutations/BaseMutation.php
abstract class BaseMutation
{
    protected function validateUser(User $user): void
    {
        if ($user->status !== UserStatus::ACTIVE) {
            throw new Exception('Usuario inactivo');
        }
    }
}

// Uso en un feature:
class LoginMutation extends BaseMutation
{
    // Hereda validateUser()
}
```

---

## 🟠 APP/FEATURES/[FEATURE]/ - Lógica de Negocio

**Propósito:** TODO el código relacionado con un feature específico.

### Estructura de cada feature (app/Features/[Feature]/):

```
app/Features/Authentication/
├── Services/            # Lógica de negocio del feature
├── Models/              # Modelos Eloquent del feature
├── GraphQL/
│   ├── Schema/          # Schema GraphQL (.graphql)
│   ├── Queries/         # Resolvers de queries
│   ├── Mutations/       # Resolvers de mutations
│   ├── Types/           # Types específicos del feature
│   └── DataLoaders/     # DataLoaders del feature
├── Events/              # Eventos del dominio
├── Listeners/           # Escuchan eventos
├── Jobs/                # Tareas asíncronas
├── Policies/            # Autorización
└── Database/            # ✅ TODO lo de base de datos del feature
    ├── Migrations/      # Migraciones del feature
    ├── Seeders/         # Seeders del feature
    └── Factories/       # Factories del feature
```

**⚠️ NOTA:** La **única excepción** son los tests, que quedan en `tests/Feature/[Feature]/` y `tests/Unit/Services/[Feature]/` por convención de Laravel (para que `php artisan test` funcione sin configuración adicional).

---

### 📂 `Services/`

**¿Para qué?** TODA la lógica de negocio del feature.

**Regla de oro:** ❌ **NUNCA** pongas lógica en Controllers/Resolvers. **SIEMPRE** en Services.

**Ejemplo:**
```php
// app/Features/Authentication/Services/AuthenticationService.php
class AuthenticationService
{
    public function login(string $email, string $password): array
    {
        // 1. Validar credenciales
        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($password, $user->password_hash)) {
            throw new Exception('Credenciales inválidas');
        }

        // 2. Generar tokens
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        // 3. Registrar login
        event(new UserLoggedIn($user));

        return [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'user' => $user,
        ];
    }
}
```

**GraphQL Resolver (solo delega):**
```php
class LoginMutation
{
    public function __invoke($root, array $args, AuthenticationService $service)
    {
        // ✅ SOLO delega al service
        return $service->login($args['email'], $args['password']);
    }
}
```

---

### 📂 `Models/`

**¿Para qué?** Modelos Eloquent del feature (representan tablas de DB).

**Ejemplo:**
```php
// app/Features/Authentication/Models/User.php
class User extends Model
{
    use HasUuid, Auditable;

    protected $table = 'auth.users';  // Schema PostgreSQL

    protected $fillable = [
        'email',
        'password_hash',
        'status',
    ];

    protected $casts = [
        'status' => UserStatus::class,
        'email_verified_at' => 'datetime',
    ];

    // Relaciones
    public function roles()
    {
        return $this->hasMany(UserRole::class);
    }
}
```

**¿Cuándo crear un Model?** Por cada tabla de la DB.

---

### 📂 `GraphQL/Schema/`

**¿Para qué?** Archivo `.graphql` con el schema del feature.

**Ejemplo:**
```graphql
# app/Features/Authentication/GraphQL/Schema/authentication.graphql
extend type Mutation {
  login(email: Email!, password: String!): AuthPayload!
    @field(resolver: "App\\Features\\Authentication\\GraphQL\\Mutations\\LoginMutation")
}

type AuthPayload {
  accessToken: String!
  refreshToken: String!
  user: AuthUser!
}
```

---

### 📂 `GraphQL/Queries/` y `GraphQL/Mutations/`

**¿Para qué?** Resolvers de GraphQL (conectan schema con Services).

**Estructura:**
```
Queries/
├── AuthStatusQuery.php
└── MySessionsQuery.php

Mutations/
├── LoginMutation.php
├── RegisterMutation.php
└── LogoutMutation.php
```

**Regla:** 1 archivo por cada query/mutation del schema.

---

### 📂 `Events/`

**¿Para qué?** Eventos del dominio (cosas que pasan en el sistema).

**Ejemplo:**
```php
// app/Features/Authentication/Events/UserLoggedIn.php
class UserLoggedIn
{
    public function __construct(
        public User $user,
        public string $ipAddress
    ) {}
}

// Disparar evento:
event(new UserLoggedIn($user, request()->ip()));
```

**¿Para qué sirven?** Desacoplar código. Cuando un usuario hace login:
- Listener 1: Envía email de notificación
- Listener 2: Registra en audit log
- Listener 3: Actualiza estadísticas

---

### 📂 `Listeners/`

**¿Para qué?** Escuchan eventos y ejecutan código.

**Ejemplo:**
```php
// app/Features/Authentication/Listeners/SendLoginNotification.php
class SendLoginNotification
{
    public function handle(UserLoggedIn $event): void
    {
        Mail::to($event->user->email)->send(
            new LoginNotificationMail($event->user, $event->ipAddress)
        );
    }
}
```

**Registro en EventServiceProvider:**
```php
protected $listen = [
    UserLoggedIn::class => [
        SendLoginNotification::class,
        LogLoginToAudit::class,
    ],
];
```

---

### 📂 `Jobs/`

**¿Para qué?** Tareas asíncronas (se ejecutan en background con colas).

**Ejemplo:**
```php
// app/Features/Authentication/Jobs/SendEmailVerificationJob.php
class SendEmailVerificationJob implements ShouldQueue
{
    public function __construct(public User $user) {}

    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new EmailVerificationMail($this->user)
        );
    }
}

// Despachar job:
SendEmailVerificationJob::dispatch($user);  // Se ejecuta en background
```

**¿Cuándo usarlo?** Tareas lentas que no deben bloquear la respuesta:
- Enviar emails
- Procesar imágenes
- Generar reportes PDF
- Llamar APIs externas

---

### 📂 `Policies/`

**¿Para qué?** Lógica de autorización (¿puede este usuario hacer X?).

**Ejemplo:**
```php
// app/Features/UserManagement/Policies/UserPolicy.php
class UserPolicy
{
    public function update(User $authUser, User $targetUser): bool
    {
        // ¿Puede authUser editar targetUser?

        // 1. Platform admins pueden editar a cualquiera
        if ($authUser->hasRole(Role::PLATFORM_ADMIN)) {
            return true;
        }

        // 2. Los usuarios pueden editarse a sí mismos
        if ($authUser->id === $targetUser->id) {
            return true;
        }

        // 3. Company admins pueden editar usuarios de su empresa
        if ($authUser->hasRole(Role::COMPANY_ADMIN)) {
            return $authUser->companies->contains($targetUser->company_id);
        }

        return false;
    }
}
```

**Uso en GraphQL:**
```graphql
type Mutation {
  updateUser(id: UUID!, input: UpdateUserInput!): User!
    @can(ability: "update", model: "User", find: "id")
}
```

---

### 📂 `Database/Migrations/`

**¿Para qué?** Cambios en la estructura de la DB.

**Ejemplo:**
```php
// app/Features/Authentication/Database/Migrations/2024_01_01_create_users_table.php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth.users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_code')->unique();
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->enum('status', ['active', 'suspended', 'deleted']);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }
};
```

**✅ Ubicación:** Las migraciones están dentro de cada feature en `app/Features/[Feature]/Database/Migrations/`

---

### 📂 `Database/Seeders/`

**¿Para qué?** Datos de prueba para la DB.

**Ejemplo:**
```php
// app/Features/Authentication/Database/Seeders/UsersSeeder.php
class UsersSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password'),
            'status' => UserStatus::ACTIVE,
        ]);
    }
}
```

---

### 📂 `Database/Factories/`

**¿Para qué?** Generar modelos fake para testing.

**Ejemplo:**
```php
// app/Features/Authentication/Database/Factories/UserFactory.php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password_hash' => Hash::make('password'),
            'status' => UserStatus::ACTIVE,
        ];
    }
}

// Uso en tests:
$user = User::factory()->create();  // Crea un usuario fake
```

---

### 📂 `Tests/Feature/`

**¿Para qué?** Tests de integración (prueban flujos completos).

**Ejemplo:**
```php
// app/Features/Authentication/Tests/Feature/LoginTest.php
class LoginTest extends TestCase
{
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postGraphQL([
            'query' => '
                mutation {
                    login(email: "test@example.com", password: "password123") {
                        accessToken
                        user { email }
                    }
                }
            ',
        ]);

        $response->assertOk();
        $this->assertNotNull($response['data']['login']['accessToken']);
    }
}
```

---

### 📂 `Tests/Unit/`

**¿Para qué?** Tests unitarios (prueban funciones individuales).

**Ejemplo:**
```php
// app/Features/Authentication/Tests/Unit/AuthenticationServiceTest.php
class AuthenticationServiceTest extends TestCase
{
    public function test_generates_valid_access_token()
    {
        $service = new AuthenticationService();
        $user = User::factory()->create();

        $token = $service->generateAccessToken($user);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }
}
```

---

## 📊 ARQUITECTURA IMPLEMENTADA: Feature-First Puro

### Este proyecto usa Feature-First PURO:

| Aspecto | Ubicación | Nota |
|---------|-----------|------|
| **Models** | ✅ `app/Features/[Feature]/Models/` | Dentro del feature |
| **Services** | ✅ `app/Features/[Feature]/Services/` | Dentro del feature |
| **GraphQL** | ✅ `app/Features/[Feature]/GraphQL/` | Dentro del feature |
| **Policies** | ✅ `app/Features/[Feature]/Policies/` | Dentro del feature |
| **Events/Listeners/Jobs** | ✅ `app/Features/[Feature]/` | Dentro del feature |
| **Migraciones** | ✅ `app/Features/[Feature]/Database/Migrations/` | Dentro del feature |
| **Seeders** | ✅ `app/Features/[Feature]/Database/Seeders/` | Dentro del feature |
| **Factories** | ✅ `app/Features/[Feature]/Database/Factories/` | Dentro del feature |
| **Tests** | ⚠️ `tests/Feature/[Feature]/`, `tests/Unit/` | **ÚNICA EXCEPCIÓN** |

**Única excepción:** Los tests quedan en `tests/` por convención de Laravel, pero organizados por features dentro de esa carpeta.

---

## 🎓 GUÍA DE DECISIÓN: ¿Dónde pongo mi código?

### ✅ Pon en `Shared/` si:
- Lo usan 2+ features
- Es una utilidad genérica
- Es infraestructura técnica (scalars, directives, base classes)

### ✅ Pon en `Features/[Feature]/` si:
- Es específico de UN feature
- Es lógica de negocio del dominio

### ❌ NO pongas en `Shared/` si:
- Solo 1 feature lo usa (va en ese feature)
- Es lógica de negocio específica (va en el feature)

---

## 📚 RECURSOS ADICIONALES

**Para aprender más:**
- Feature-First: https://laracasts.com/series/domain-driven-design-in-laravel
- GraphQL en Laravel: https://lighthouse-php.com/
- Tests en Laravel: https://laravel.com/docs/testing

---

**¿Dudas?** Revisa este documento cada vez que no sepas dónde poner un archivo. Con el tiempo se volverá intuitivo. 🚀