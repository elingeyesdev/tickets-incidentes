# 🔐 AUTHENTICATION FEATURE - CHECKLIST DE IMPLEMENTACIÓN

**Fecha:** 01-Oct-2025
**Estrategia:** Construir primero toda la infraestructura, luego conectar resolvers como puentes

---

## 📋 RESUMEN EJECUTIVO

Según la documentación, el Authentication Feature incluye:
- **4 Queries** (authStatus, mySessions, passwordResetStatus, emailVerificationStatus)
- **10 Mutations** (register, login, loginWithGoogle, refreshToken, logout, revokeOtherSession, resetPassword, confirmPasswordReset, verifyEmail, resendEmailVerification)
- **JWT Tokens** con refresh tokens
- **Rate limiting** por endpoint
- **OAuth Google** (opcional para MVP)
- **Email verification** y **password reset**

---

## 🏗️ FASE 1: ACTUALIZAR SHARED (si necesita)

### ✅ Enums Nuevos
- [ ] **NO NECESITA** - Los enums de UserStatus ya existen

### ✅ Exceptions Nuevas
- [ ] **NO NECESITA** - AuthenticationException ya existe en Shared
- [ ] **VERIFICAR** - Si AuthenticationException cubre todos los casos:
  - InvalidCredentials
  - EmailAlreadyExists
  - InvalidToken
  - TokenExpired
  - RateLimitExceeded

### ✅ Traits
- [ ] **NO NECESITA** - No hay traits específicos de auth

### ✅ Helpers
- [ ] **VERIFICAR** - Si necesitamos agregar helpers para:
  - Token generation/validation (podría ir en TokenService)
  - Email masking (m***a@empresa.com)
  - Device detection (nombre del dispositivo)

**DECISIÓN: Todos los helpers irán en los Services, NO en Shared**

---

## 🏗️ FASE 2: MIGRATIONS ✅ COMPLETADO

### 📁 app/Features/Authentication/Database/Migrations/

#### ✅ Migración 1: create_refresh_tokens_table ✅ COMPLETADO
```php
app/Features/Authentication/Database/Migrations/
└── 2025_10_02_000001_create_refresh_tokens_table.php
```

**Estructura de la tabla: auth.refresh_tokens**
```sql
CREATE TABLE auth.refresh_tokens (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    device_name VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    last_used_at TIMESTAMP,
    is_revoked BOOLEAN DEFAULT FALSE,
    revoked_at TIMESTAMP NULL,
    revoked_by_id UUID NULL REFERENCES auth.users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_refresh_tokens_user_id (user_id),
    INDEX idx_refresh_tokens_token_hash (token_hash),
    INDEX idx_refresh_tokens_expires_at (expires_at),
    INDEX idx_refresh_tokens_is_revoked (is_revoked)
);
```

**Campos importantes:**
- `token_hash`: SHA-256 del refresh token (nunca guardar plain text)
- `device_name`: Para identificar dispositivo en mySessions
- `ip_address`: Para seguridad y auditoría
- `expires_at`: Tokens expiran automáticamente (30 días default)
- `last_used_at`: Para detectar tokens inactivos

---

## 🏗️ FASE 3: MODELS ✅ COMPLETADO

### 📁 app/Features/Authentication/Models/

#### ✅ Model 1: RefreshToken.php ✅ COMPLETADO
```php
app/Features/Authentication/Models/RefreshToken.php
```

**Relaciones:**
- `belongsTo(User::class, 'user_id')`
- `belongsTo(User::class, 'revoked_by_id')`

**Métodos clave:**
- `isValid()`: Verifica si token es válido (no expirado, no revocado)
- `revoke(?string $userId)`: Revoca el token
- `updateLastUsed()`: Actualiza timestamp de uso
- `scopeActive()`: Solo tokens válidos
- `scopeForUser(string $userId)`: Tokens de un usuario

**Casts:**
- `expires_at`: datetime
- `last_used_at`: datetime
- `revoked_at`: datetime
- `is_revoked`: boolean

---

## 🏗️ FASE 4: SERVICES (CRÍTICOS)

### 📁 app/Features/Authentication/Services/

#### ✅ Service 1: AuthService.php
**Responsabilidades:**
- `register(array $input): AuthPayload` - Registrar nuevo usuario
- `login(string $email, string $password, array $options): AuthPayload` - Login
- `logout(string $refreshToken, bool $everywhere): bool` - Cerrar sesión
- `verifyEmail(string $token): array` - Verificar email
- `resendEmailVerification(string $userId): array` - Reenviar verificación

**Lógica crítica:**
- Delegar creación de usuario a UserService
- Delegar creación de tokens a TokenService
- Disparar eventos (UserRegistered, UserLoggedIn, etc.)
- Validar rate limiting
- Registrar actividad de login (last_login_at, last_login_ip)

---

#### ✅ Service 2: TokenService.php
**Responsabilidades:**
- `generateAccessToken(User $user): string` - Generar JWT access token
- `generateRefreshToken(User $user, array $deviceInfo): RefreshToken` - Crear refresh token
- `refreshAccessToken(string $refreshToken): AuthPayload` - Renovar access token
- `revokeRefreshToken(string $refreshToken, ?string $userId): bool` - Revocar token
- `revokeAllRefreshTokens(string $userId): int` - Revocar todos los tokens
- `validateAccessToken(string $token): ?array` - Validar JWT
- `decodeToken(string $token): ?object` - Decodificar payload de JWT

**Lógica crítica:**
- **JWT Structure:**
```json
{
  "iss": "helpdesk-api",
  "sub": "user-id",
  "user_id": "uuid",
  "email": "user@example.com",
  "roles": ["USER", "AGENT"],
  "companies": ["cmp-001"],
  "session_id": "sess_abc123",
  "exp": 1695308700,
  "iat": 1695305100
}
```
- Hash refresh tokens con SHA-256 antes de guardar
- Refresh token rotation: al renovar, invalidar el anterior
- Expiración: Access token 60 min, Refresh token 30 días

**Dependencias:**
- Biblioteca JWT: `firebase/php-jwt` o `tymon/jwt-auth`
- Config: `config/jwt.php` (secret, expiration, etc.)

---

#### ✅ Service 3: PasswordResetService.php
**Responsabilidades:**
- `requestReset(string $email): bool` - Solicitar reset (siempre retorna true)
- `validateResetToken(string $token): array` - Validar token de reset
- `confirmReset(string $token, string $newPassword): array` - Confirmar reset
- `generateResetToken(User $user): string` - Generar token de reset
- `invalidateResetToken(string $token): bool` - Invalidar token usado

**Lógica crítica:**
- Tokens de reset en cache (Redis) con TTL de 1 hora
- Formato: `reset_abc123def456ghi789` (random 32 chars)
- Máximo 3 intentos de reset por token
- Rate limiting: 3 solicitudes por hora
- Disparar evento PasswordResetRequested
- Cuando reset exitoso: logout everywhere + disparar UserPasswordChanged

**Almacenamiento de tokens:**
```php
// Redis key: password_reset:{token}
[
    'user_id' => 'uuid',
    'email' => 'user@example.com',
    'expires_at' => timestamp,
    'attempts_remaining' => 3
]
```

---

#### ⏳ Service 4: GoogleAuthService.php (OPCIONAL - Phase 4B)
**Responsabilidades:**
- `validateGoogleToken(string $googleToken): array` - Validar con Google API
- `loginOrRegisterWithGoogle(string $googleToken, array $deviceInfo): AuthPayload`
- `linkGoogleAccount(string $userId, string $googleId): bool`

**Lógica crítica:**
- Validar token con Google OAuth API
- Si usuario existe (por email): login
- Si usuario NO existe: crear cuenta con datos de Google
- Email auto-verificado para usuarios de Google
- Avatar URL de Google

**Dependencias:**
- `google/apiclient` o `socialiteproviders/google`
- Google Client ID y Secret en `.env`

**DECISIÓN: Implementar en Phase 4B después de tener login/register básico funcionando**

---

## 🏗️ FASE 5: EVENTS

### 📁 app/Features/Authentication/Events/

#### ✅ Event 1: UserRegistered.php
```php
namespace App\Features\Authentication\Events;

class UserRegistered
{
    public function __construct(
        public User $user,
        public string $verificationToken,
        public string $ipAddress
    ) {}
}
```

#### ✅ Event 2: UserLoggedIn.php
```php
class UserLoggedIn
{
    public function __construct(
        public User $user,
        public string $sessionId,
        public string $ipAddress,
        public string $deviceName,
        public array $roles
    ) {}
}
```

#### ✅ Event 3: UserLoggedOut.php
```php
class UserLoggedOut
{
    public function __construct(
        public User $user,
        public string $sessionId,
        public bool $logoutEverywhere
    ) {}
}
```

#### ✅ Event 4: PasswordResetRequested.php
```php
class PasswordResetRequested
{
    public function __construct(
        public User $user,
        public string $resetToken,
        public string $ipAddress
    ) {}
}
```

#### ✅ Event 5: PasswordResetCompleted.php
```php
class PasswordResetCompleted
{
    public function __construct(
        public User $user,
        public string $ipAddress
    ) {}
}
```

#### ✅ Event 6: EmailVerified.php
```php
class EmailVerified
{
    public function __construct(
        public User $user,
        public \DateTime $verifiedAt
    ) {}
}
```

---

## 🏗️ FASE 6: LISTENERS

### 📁 app/Features/Authentication/Listeners/

#### ✅ Listener 1: SendVerificationEmail.php
**Escucha:** UserRegistered
**Acción:** Dispara SendEmailVerificationJob

```php
public function handle(UserRegistered $event): void
{
    SendEmailVerificationJob::dispatch(
        $event->user,
        $event->verificationToken
    );
}
```

#### ✅ Listener 2: SendPasswordResetEmail.php
**Escucha:** PasswordResetRequested
**Acción:** Dispara SendPasswordResetEmailJob

```php
public function handle(PasswordResetRequested $event): void
{
    SendPasswordResetEmailJob::dispatch(
        $event->user,
        $event->resetToken
    );
}
```

#### ✅ Listener 3: LogLoginActivity.php
**Escucha:** UserLoggedIn
**Acción:** Registra actividad en audit_logs

```php
public function handle(UserLoggedIn $event): void
{
    // Crear log de auditoría
    AuditLog::create([
        'user_id' => $event->user->id,
        'action' => 'user.login',
        'ip_address' => $event->ipAddress,
        'metadata' => [
            'session_id' => $event->sessionId,
            'device_name' => $event->deviceName,
            'roles' => $event->roles
        ]
    ]);
}
```

**NOTA:** AuditLog probablemente irá en Shared o en feature de Auditoría (Phase 6)

---

## 🏗️ FASE 7: JOBS (Asíncronos)

### 📁 app/Features/Authentication/Jobs/

#### ✅ Job 1: SendEmailVerificationJob.php
**Queue:** `emails`
**Acción:** Envía email de verificación

```php
public function handle(): void
{
    Mail::to($this->user->email)->send(
        new EmailVerificationMail(
            $this->user,
            $this->verificationToken
        )
    );
}
```

**Email incluye:**
- Link: `https://app.com/verify-email?token={token}`
- Token expira en 24 horas
- Nombre del usuario

#### ✅ Job 2: SendPasswordResetEmailJob.php
**Queue:** `emails`
**Acción:** Envía email de reset de contraseña

```php
public function handle(): void
{
    Mail::to($this->user->email)->send(
        new PasswordResetMail(
            $this->user,
            $this->resetToken
        )
    );
}
```

**Email incluye:**
- Link: `https://app.com/reset-password?token={token}`
- Token expira en 1 hora
- Advertencia de seguridad

**NOTA:** Los Mails (EmailVerificationMail, PasswordResetMail) se crearán en `app/Features/Authentication/Mail/`

---

## 🏗️ FASE 8: FACTORIES (Testing)

### 📁 app/Features/Authentication/Database/Factories/

#### ✅ Factory 1: RefreshTokenFactory.php
```php
public function definition(): array
{
    return [
        'user_id' => User::factory(),
        'token_hash' => hash('sha256', Str::random(64)),
        'device_name' => fake()->randomElement([
            'Chrome on Windows',
            'Safari on iPhone',
            'Firefox on Mac'
        ]),
        'ip_address' => fake()->ipv4(),
        'user_agent' => fake()->userAgent(),
        'expires_at' => now()->addDays(30),
        'last_used_at' => now(),
        'is_revoked' => false,
    ];
}

public function expired(): static {
    return $this->state(['expires_at' => now()->subDays(1)]);
}

public function revoked(): static {
    return $this->state([
        'is_revoked' => true,
        'revoked_at' => now()
    ]);
}
```

---

## 🏗️ FASE 9: SEEDERS

### 📁 app/Features/Authentication/Database/Seeders/

#### ❌ NO NECESITA SEEDERS
Los refresh tokens se crean dinámicamente al hacer login.
No hay datos de seed para Authentication.

---

## 🏗️ FASE 10: POLICIES

### 📁 app/Features/Authentication/Policies/

#### ❌ NO NECESITA POLICIES
Authentication es un feature público (register, login).
Las operaciones autenticadas (logout, refreshToken) validan el token directamente, no roles.

---

## 🏗️ FASE 11: DATALOADERS

### 📁 app/Shared/GraphQL/DataLoaders/

#### ❌ NO NECESITA DATALOADERS ESPECÍFICOS
Authentication no tiene relaciones complejas que requieran DataLoaders.
Los DataLoaders existentes (UserByIdLoader) son suficientes.

---

## 🏗️ FASE 12: CONFIGURACIÓN ✅ COMPLETADO

### ✅ Config 1: JWT Configuration ✅ COMPLETADO
**Archivo:** `config/jwt.php`

```php
return [
    'secret' => env('JWT_SECRET'),
    'ttl' => env('JWT_TTL', 60), // Access token: 60 minutos
    'refresh_ttl' => env('JWT_REFRESH_TTL', 43200), // Refresh: 30 días
    'algo' => 'HS256',
    'required_claims' => ['iss', 'iat', 'exp', 'sub', 'user_id'],
    'blacklist_enabled' => true,
    'blacklist_grace_period' => 0,
];
```

### ✅ Config 2: Auth Guards
**Archivo:** `config/auth.php` (actualizar)

```php
'guards' => [
    'web' => [...],
    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
```

### ✅ Config 3: Rate Limiting
**Archivo:** `config/rate-limiting.php` (crear)

```php
return [
    'register' => [
        'max' => 5,
        'decay' => 3600, // 1 hora
    ],
    'login' => [
        'max' => 5,
        'decay' => 900, // 15 minutos
    ],
    'password_reset' => [
        'max' => 3,
        'decay' => 3600,
    ],
];
```

---

## 📊 RESUMEN DE ARCHIVOS A CREAR

### Migraciones: **1 archivo**
- create_refresh_tokens_table.php

### Models: **1 archivo**
- RefreshToken.php

### Services: **3 archivos** (4 con Google OAuth)
- AuthService.php ✅
- TokenService.php ✅
- PasswordResetService.php ✅
- GoogleAuthService.php ⏳ (Phase 4B)

### Events: **6 archivos**
- UserRegistered, UserLoggedIn, UserLoggedOut
- PasswordResetRequested, PasswordResetCompleted, EmailVerified

### Listeners: **3 archivos**
- SendVerificationEmail
- SendPasswordResetEmail
- LogLoginActivity

### Jobs: **2 archivos**
- SendEmailVerificationJob
- SendPasswordResetEmailJob

### Mails: **2 archivos** (nuevos)
- EmailVerificationMail
- PasswordResetMail

### Factories: **1 archivo**
- RefreshTokenFactory

### Config: **2 archivos**
- jwt.php (crear)
- rate-limiting.php (crear)

### Shared: **0 archivos** (todo está listo)

---

## ✅ TOTAL DE ARCHIVOS

**Infrastructure (sin resolvers):** **21 archivos**
- 1 Migration
- 1 Model
- 3 Services
- 6 Events
- 3 Listeners
- 2 Jobs
- 2 Mails
- 1 Factory
- 2 Configs

**Resolvers (puentes - Phase 4-Puentes):** **14 archivos**
- 4 Queries
- 10 Mutations

---

## 🎯 ORDEN DE IMPLEMENTACIÓN RECOMENDADO

### **Día 1 (3-4 horas):** ✅ COMPLETADO (01-Oct-2025)
1. ✅ Migration: create_refresh_tokens_table
2. ✅ Model: RefreshToken
3. ✅ Config: jwt.php + rate-limiting.php
4. ✅ Service: TokenService (núcleo crítico)

### **Día 2 (3-4 horas):** ✅ COMPLETADO (01-Oct-2025)
5. ✅ Service: AuthService (usa TokenService + UserService)
6. ✅ Service: PasswordResetService
7. ✅ Events: Los 6 eventos
8. ✅ Factories: RefreshTokenFactory

### **Día 3 (2-3 horas):** ✅ COMPLETADO (01-Oct-2025)
9. ✅ Listeners: Los 3 listeners
10. ✅ Jobs: Los 2 jobs
11. ✅ Mails: Los 2 mails
12. ⏳ Tests unitarios de Services (pendiente)
13. ⏳ Vistas de email Blade (pendiente)
14. ⏳ Registrar Listeners en EventServiceProvider (pendiente)

---

## 🚀 READY PARA CONECTAR PUENTES

Una vez completada toda la infraestructura, comenzamos Phase 4-Puentes:
1. RegisterMutation → TESTEAR en GraphiQL
2. LoginMutation → TESTEAR
3. RefreshTokenMutation → TESTEAR
4. (continuar con los 11 resolvers restantes)

---

## 📝 ESTADO ACTUAL (01-Oct-2025)

✅ **Día 1 COMPLETADO:**
- Migration: `auth.refresh_tokens` con SHA-256 hashing, device tracking
- Model: `RefreshToken` con métodos de validación, scopes y revocación
- Config: `jwt.php` con HS256, TTL 60 min, refresh 30 días
- Config: `rate-limiting.php` con límites por endpoint
- Service: `TokenService` con JWT generation, validation, rotation, blacklist

✅ **Día 2 COMPLETADO:**
- Service: `AuthService` con register, login, logout, email verification
- Service: `PasswordResetService` con tokens en Redis, email masking, 3 intentos
- 6 Events: UserRegistered, UserLoggedIn, UserLoggedOut, PasswordResetRequested, PasswordResetCompleted, EmailVerified
- Factory: `RefreshTokenFactory` con estados (expired, revoked, mobile, desktop)

✅ **Día 3 COMPLETADO:**
- 3 Listeners: SendVerificationEmail, SendPasswordResetEmail, LogLoginActivity
- 2 Jobs (queue: emails): SendEmailVerificationJob, SendPasswordResetEmailJob
- 2 Mails: EmailVerificationMail, PasswordResetMail

## ⏳ TAREAS PENDIENTES (antes de Phase 4-Puentes)

1. **Vistas Blade de emails** (resources/views/emails/auth/):
   - verify-email.blade.php
   - verify-email-text.blade.php
   - reset-password.blade.php
   - reset-password-text.blade.php

2. **Registrar Listeners en EventServiceProvider**:
   ```php
   protected $listen = [
       UserRegistered::class => [SendVerificationEmail::class],
       PasswordResetRequested::class => [SendPasswordResetEmail::class],
       UserLoggedIn::class => [LogLoginActivity::class],
   ];
   ```

3. **Tests unitarios** de Services (opcional para MVP)

4. **Instalar dependencia JWT**: `composer require firebase/php-jwt`

**SIGUIENTE PASO:** Phase 4-Puentes - Implementar Resolvers uno por uno y testear en GraphiQL