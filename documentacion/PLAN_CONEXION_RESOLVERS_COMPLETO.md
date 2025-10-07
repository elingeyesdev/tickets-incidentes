# 🚀 PLAN COMPLETO: CONEXIÓN DE RESOLVERS - Sistema Helpdesk

**Fecha de Creación**: 07 de Octubre de 2025
**Estado Actual**: Post-FASE 0 (BD 100% alineada con Modelado V7.0)
**Objetivo**: Conectar 43 resolvers dummy con lógica real, uno por uno con testing iterativo

---

## 📊 ESTADO ACTUAL DEL PROYECTO

### ✅ COMPLETADO (FASE 0 + Infrastructure)

#### 1. Base de Datos PostgreSQL
- ✅ **4 Schemas**: auth, business, ticketing, audit
- ✅ **8 Tablas migradas**: users, user_profiles, roles, user_roles, refresh_tokens, companies, company_requests, user_company_followers
- ✅ **3 ENUM Types**: auth.user_status, business.request_status, business.publication_status
- ✅ **Extensiones**: uuid-ossp, pgcrypto, citext
- ✅ **Triggers**: update_updated_at_column activos
- ✅ **Índices**: Parciales y compuestos optimizados
- ✅ **100% alineación con Modelado V7.0** ✅

#### 2. Models Eloquent (8 Models - Actualizados Post-FASE 0)
```
app/Features/UserManagement/Models/
├── User.php ✅ (19 columnas, relaciones actualizadas)
├── UserProfile.php ✅ (PK=user_id, display_name accessor)
├── Role.php ✅ (FK a role_code, sin permissions)
└── UserRole.php ✅ (FK a role_code VARCHAR)

app/Features/Authentication/Models/
└── RefreshToken.php ✅ (revoke_reason agregado)

app/Features/CompanyManagement/Models/
├── Company.php ✅
├── CompanyRequest.php ✅
└── CompanyFollower.php ✅
```

#### 3. Services (9 Services con lógica completa)
```
app/Features/Authentication/Services/
├── AuthService.php ✅ (register, login, logout, refreshToken)
├── TokenService.php ✅ (generateAccessToken, generateRefreshToken, validateToken)
└── PasswordResetService.php ✅ (requestReset, confirmReset, validateToken)

app/Features/UserManagement/Services/
├── UserService.php ✅ (create, update, delete, suspend, activate, createFromCompanyRequest)
├── RoleService.php ✅ (assignRole, revokeRole, updateRole, hasRole)
└── ProfileService.php ✅ (completeProfile, updateProfile, updatePreferences)

app/Features/CompanyManagement/Services/
├── CompanyService.php ✅ (create, update, suspend, activate, getStats)
├── CompanyRequestService.php ✅ (submit, approve, reject)
└── CompanyFollowService.php ✅ (follow, unfollow, isFollowing)
```

#### 4. Policies (3 Policies)
```
app/Features/UserManagement/Policies/
└── UserPolicy.php ✅

app/Features/CompanyManagement/Policies/
└── CompanyPolicy.php ✅

app/Features/Authentication/Policies/
└── (No requiere Policy específica - usa guards)
```

#### 5. Events + Listeners + Jobs
```
Authentication:
├── Events: UserRegistered, UserLoggedIn, UserLoggedOut, EmailVerificationRequested, PasswordResetRequested
├── Listeners: SendVerificationEmail, SendPasswordResetEmail, RecordLoginActivity, RevokeOtherSessions
└── Jobs: SendEmailVerificationJob, SendPasswordResetEmailJob

UserManagement:
├── Events: UserCreated, UserUpdated, UserDeleted, UserSuspended, UserActivated, RoleAssigned, RoleRevoked, ProfileCompleted, ProfileUpdated
├── Listeners: NotifyAdminOfNewUser, SendWelcomeEmail, SendSuspensionNotice, NotifyOfRoleChange, SendProfileCompletionEmail
└── Jobs: SendWelcomeEmailJob, SendSuspensionNoticeJob, SendRoleChangeEmailJob

CompanyManagement:
├── Events: CompanyCreated, CompanyUpdated, CompanySuspended, CompanyActivated, CompanyRequestSubmitted, CompanyRequestApproved, CompanyRequestRejected, CompanyFollowed, CompanyUnfollowed
├── Listeners: SendCompanyRequestConfirmationEmail, NotifyAdminOfNewRequest, SendApprovalEmail, SendRejectionEmail, CreateCompanyFromRequest
└── Jobs: SendCompanyRequestEmailJob, SendCompanyApprovalEmailJob, SendCompanyRejectionEmailJob
```

#### 6. GraphQL Schema
- ✅ **3 Feature Schemas**: authentication.graphql, user-management.graphql, company-management.graphql
- ✅ **Shared**: scalars, directives, enums, base-types, interfaces, pagination
- ✅ **Schema validado**: `php artisan lighthouse:validate-schema` ✅
- ✅ **Endpoint**: POST http://localhost:8000/graphql

#### 7. Shared Components
```
app/Shared/
├── Enums/
│   ├── UserStatus.php ✅
│   ├── Role.php ✅
│   ├── CompanyStatus.php ✅
│   └── CompanyRequestStatus.php ✅
│
├── Exceptions/
│   ├── HelpdeskException.php ✅ (base)
│   ├── AuthenticationException.php ✅
│   ├── AuthorizationException.php ✅
│   ├── ValidationException.php ✅
│   └── NotFoundException.php ✅
│
├── Helpers/
│   └── CodeGenerator.php ✅ (genera USR-2025-00001, CMP-2025-00001, etc.)
│
├── Traits/
│   ├── HasUuid.php ✅ (auto-genera UUID en models)
│   └── Auditable.php ✅ (tracking de created_by, updated_by, deleted_by)
│
└── GraphQL/
    ├── Scalars/ (7 scalars personalizados) ✅
    ├── Directives/ (@auth, @company, @audit, @rateLimit) ✅
    ├── Queries/ (BaseQuery, PingQuery, VersionQuery, HealthQuery) ✅
    └── Mutations/ (BaseMutation) ✅
```

---

## 🔍 AUDITORÍA: DataLoaders

### DataLoaders Existentes (13 total)

#### Shared DataLoaders (6)
```
app/Shared/GraphQL/DataLoaders/
├── UserByIdLoader.php ✅
├── CompanyByIdLoader.php ✅
├── CompaniesByUserIdLoader.php ✅
├── UsersByCompanyIdLoader.php ✅
├── UserProfileByUserIdLoader.php ✅
└── UserRolesByUserIdLoader.php ✅
```

#### Authentication DataLoaders (2)
```
app/Features/Authentication/GraphQL/DataLoaders/
├── RefreshTokenBySessionIdLoader.php ✅
└── RefreshTokensByUserIdLoader.php ✅
```

#### UserManagement DataLoaders (3)
```
app/Features/UserManagement/GraphQL/DataLoaders/
├── UserProfileByUserIdLoader.php ⚠️ (DUPLICADO con Shared)
├── UserRolesByUserIdLoader.php ⚠️ (DUPLICADO con Shared)
└── UserRoleHistoryByUserIdLoader.php ✅
```

#### CompanyManagement DataLoaders (2)
```
app/Features/CompanyManagement/GraphQL/DataLoaders/
├── CompanyFollowersByCompanyIdLoader.php ✅
└── FollowedCompaniesByUserIdLoader.php ✅
```

### ⚠️ PROBLEMA: DataLoaders Duplicados

**UserProfileByUserIdLoader** y **UserRolesByUserIdLoader** existen en:
- `app/Shared/GraphQL/DataLoaders/` ✅
- `app/Features/UserManagement/GraphQL/DataLoaders/` ❌

**Decisión**: Eliminar de UserManagement, usar solo los de Shared (ya están registrados en Lighthouse).

### ⚠️ PROBLEMA: Lighthouse Config Incompleto

```php
// config/lighthouse.php línea 238
'dataLoaders' => ['App\\Shared\\GraphQL\\DataLoaders'],
```

**Falta registrar DataLoaders de features**:
```php
'dataLoaders' => [
    'App\\Shared\\GraphQL\\DataLoaders',
    'App\\Features\\Authentication\\GraphQL\\DataLoaders',
    'App\\Features\\UserManagement\\GraphQL\\DataLoaders',
    'App\\Features\\CompanyManagement\\GraphQL\\DataLoaders',
],
```

---

## 📋 INVENTARIO COMPLETO: 43 Resolvers

### AUTHENTICATION FEATURE (14 resolvers)

#### Mutations (10)
1. `RegisterMutation` - Registro de nuevo usuario
2. `LoginMutation` - Login con email/password
3. `GoogleLoginMutation` - Login con OAuth Google
4. `LogoutMutation` - Cerrar sesión actual
5. `RefreshTokenMutation` - Renovar access token
6. `VerifyEmailMutation` - Confirmar email
7. `ResendVerificationMutation` - Reenviar email de verificación
8. `ResetPasswordMutation` - Solicitar reset de contraseña
9. `ConfirmPasswordResetMutation` - Confirmar reset con token
10. `RevokeOtherSessionMutation` - Revocar otras sesiones

#### Queries (4)
11. `AuthStatusQuery` - Estado de autenticación actual
12. `MySessionsQuery` - Listar mis sesiones activas
13. `EmailVerificationStatusQuery` - Estado de verificación de email
14. `PasswordResetStatusQuery` - Estado de solicitud de reset

---

### USER MANAGEMENT FEATURE (17 resolvers)

#### Queries (6)
1. `MeQuery` - Datos del usuario autenticado
2. `MyProfileQuery` - Perfil completo del usuario autenticado
3. `UsersQuery` - Listar usuarios (paginado, filtros)
4. `UserQuery` - Detalle de un usuario
5. `CompanyUsersQuery` - Usuarios de una empresa
6. `AvailableRolesQuery` - Roles disponibles del sistema

#### Mutations (11)
7. `CompleteMyProfileMutation` - Completar perfil (first_name, last_name)
8. `UpdateMyProfileMutation` - Actualizar mi perfil
9. `UpdateMyPreferencesMutation` - Actualizar preferencias (theme, language, notifications)
10. `CreateUserMutation` - Crear usuario (platform_admin)
11. `UpdateUserMutation` - Actualizar usuario
12. `DeleteUserMutation` - Eliminar usuario (soft delete)
13. `SuspendUserMutation` - Suspender usuario
14. `ActivateUserMutation` - Activar usuario suspendido
15. `AssignRoleMutation` - Asignar rol a usuario
16. `RevokeRoleMutation` - Revocar rol de usuario
17. `UpdateUserRoleMutation` - Actualizar configuración de rol

---

### COMPANY MANAGEMENT FEATURE (12 resolvers)

#### Queries (5)
1. `CompaniesQuery` - Listar empresas (paginado, filtros)
2. `CompanyQuery` - Detalle de una empresa
3. `MyFollowedCompaniesQuery` - Empresas que sigo
4. `IsFollowingCompanyQuery` - Verificar si sigo una empresa
5. `CompanyRequestsQuery` - Listar solicitudes de empresa (platform_admin)

#### Mutations (7)
6. `RequestCompanyMutation` - Solicitar creación de empresa (público)
7. `ApproveCompanyRequestMutation` - Aprobar solicitud (platform_admin)
8. `RejectCompanyRequestMutation` - Rechazar solicitud (platform_admin)
9. `CreateCompanyMutation` - Crear empresa directamente (platform_admin)
10. `UpdateCompanyMutation` - Actualizar empresa
11. `FollowCompanyMutation` - Seguir empresa
12. `UnfollowCompanyMutation` - Dejar de seguir empresa

---

## 🎯 ORDEN DE IMPLEMENTACIÓN (Estrategia Feature-First)

### FASE 3.1: AUTHENTICATION (Prioridad MÁXIMA)
**Razón**: Base de todo el sistema. Sin auth, nada más funciona.

**Orden sugerido** (14 resolvers):
```
1️⃣ RegisterMutation (COMENZAR AQUÍ) ⭐
   ├─ Crea User + UserProfile
   ├─ Genera user_code (USR-2025-00001)
   ├─ Dispara evento UserRegistered → SendEmailVerificationJob
   └─ Retorna AuthPayload (accessToken, refreshToken, user)

2️⃣ LoginMutation
   ├─ Valida credenciales
   ├─ Genera tokens
   ├─ Registra last_login_at/last_login_ip
   └─ Retorna AuthPayload

3️⃣ AuthStatusQuery (testear login)
   └─ Retorna User actual desde JWT

4️⃣ VerifyEmailMutation
   ├─ Valida token de verificación
   ├─ Marca email_verified = true
   └─ Dispara evento EmailVerified

5️⃣ ResendVerificationMutation
   └─ Reenvía email si no verificado

6️⃣ RefreshTokenMutation
   ├─ Valida refresh token
   ├─ Genera nuevo access token
   └─ Actualiza last_used_at

7️⃣ MySessionsQuery
   └─ Lista RefreshTokens activos del user

8️⃣ RevokeOtherSessionMutation
   └─ Revoca token específico

9️⃣ LogoutMutation
   └─ Revoca refresh token actual

🔟 ResetPasswordMutation
   └─ Solicita reset (genera token, envía email)

1️⃣1️⃣ ConfirmPasswordResetMutation
   └─ Confirma reset con token

1️⃣2️⃣ PasswordResetStatusQuery
   └─ Verifica validez de token

1️⃣3️⃣ EmailVerificationStatusQuery
   └─ Verifica si email está verificado

1️⃣4️⃣ GoogleLoginMutation (ÚLTIMO - requiere OAuth setup)
   └─ Login con Google OAuth
```

**Testing entre cada resolver**: Probar en GraphiQL/Postman

---

### FASE 3.2: USER MANAGEMENT (Depende de Auth)
**Razón**: Gestión de usuarios requiere auth funcionando.

**Orden sugerido** (17 resolvers):
```
1️⃣ MeQuery ⭐ (más simple, testea auth)
   └─ Retorna User actual

2️⃣ MyProfileQuery
   └─ Retorna User + UserProfile + roles

3️⃣ CompleteMyProfileMutation
   ├─ Usuario recién registrado completa perfil
   ├─ first_name, last_name obligatorios
   └─ Dispara ProfileCompleted event

4️⃣ UpdateMyProfileMutation
   └─ Actualizar nombre, avatar, phone

5️⃣ UpdateMyPreferencesMutation
   └─ Actualizar theme, language, timezone, notificaciones

6️⃣ AvailableRolesQuery
   └─ Lista 4 roles del sistema

7️⃣ UsersQuery (requiere permisos)
   └─ Lista usuarios (paginado, filtros)

8️⃣ UserQuery
   └─ Detalle de usuario específico

9️⃣ CompanyUsersQuery
   └─ Usuarios de una empresa (con roles)

🔟 CreateUserMutation (solo platform_admin)
   ├─ Crea User sin password (invitación)
   └─ Envía email de invitación

1️⃣1️⃣ UpdateUserMutation
   └─ Actualizar datos de otro usuario

1️⃣2️⃣ SuspendUserMutation
   └─ Cambiar status a 'suspended'

1️⃣3️⃣ ActivateUserMutation
   └─ Cambiar status a 'active'

1️⃣4️⃣ DeleteUserMutation
   └─ Soft delete (deleted_at, status='deleted')

1️⃣5️⃣ AssignRoleMutation
   ├─ Asignar rol (company_admin/agent requieren company_id)
   └─ Dispara RoleAssigned event

1️⃣6️⃣ RevokeRoleMutation
   └─ Revocar rol activo

1️⃣7️⃣ UpdateUserRoleMutation
   └─ Cambiar is_active o company_id
```

---

### FASE 3.3: COMPANY MANAGEMENT (Depende de Auth + Users)
**Razón**: Empresas requieren usuarios con roles específicos.

**Orden sugerido** (12 resolvers):
```
1️⃣ RequestCompanyMutation ⭐ (público, no requiere auth)
   ├─ Usuario llena formulario
   ├─ Genera request_code (REQ-2025-00001)
   ├─ Status = 'pending'
   └─ Dispara CompanyRequestSubmitted → SendCompanyRequestEmailJob

2️⃣ CompanyRequestsQuery (platform_admin)
   └─ Lista solicitudes pendientes

3️⃣ ApproveCompanyRequestMutation (platform_admin)
   ├─ Crea User desde admin_email (si no existe)
   ├─ Crea Company (genera company_code CMP-2025-00001)
   ├─ Asigna rol company_admin al admin_user_id
   ├─ Actualiza request: status='approved', created_company_id
   └─ Dispara CompanyRequestApproved → CreateCompanyFromRequest listener

4️⃣ RejectCompanyRequestMutation (platform_admin)
   ├─ Actualiza status='rejected', rejection_reason
   └─ Dispara CompanyRequestRejected → SendRejectionEmail

5️⃣ CreateCompanyMutation (platform_admin)
   └─ Crear empresa directamente (bypass request process)

6️⃣ CompaniesQuery
   └─ Lista empresas (paginado, filtros)

7️⃣ CompanyQuery
   └─ Detalle de empresa

8️⃣ UpdateCompanyMutation (company_admin o platform_admin)
   └─ Actualizar datos de empresa

9️⃣ FollowCompanyMutation
   ├─ Usuario sigue empresa
   └─ Dispara CompanyFollowed event

🔟 UnfollowCompanyMutation
   └─ Usuario deja de seguir

1️⃣1️⃣ MyFollowedCompaniesQuery
   └─ Empresas que sigo

1️⃣2️⃣ IsFollowingCompanyQuery
   └─ Verificar si sigo empresa X
```

---

## 🛠️ PROTOCOLO DE IMPLEMENTACIÓN (Por Resolver)

### PASO 1: Preparación (ANTES de escribir código)

#### 1.1. Leer Documentación
```bash
# Leer spec completa del resolver
cat documentacion/[FEATURE]_FEATURE_DOCUMENTACION.txt | grep -A 50 "[resolver_name]"

# Leer schema GraphQL
cat app/Features/[Feature]/GraphQL/Schema/*.graphql
```

#### 1.2. Identificar Dependencias
- ¿Qué Service(s) necesito?
- ¿Qué DataLoader(s) necesito?
- ¿Qué Models involucra?
- ¿Requiere Policy check?
- ¿Qué Events dispara?

#### 1.3. Verificar Shared Components
```bash
# ⚠️ ANTES de crear nueva Exception/Helper/Validator
# SIEMPRE revisar si ya existe en Shared

ls app/Shared/Exceptions/
ls app/Shared/Helpers/
ls app/Shared/Validators/
```

**Regla de Oro**: Si 2+ features lo usarán → Va en Shared

---

### PASO 2: Implementación

#### 2.1. Template Base para Resolvers

```php
<?php declare(strict_types=1);

namespace App\Features\[Feature]\GraphQL\[Mutations|Queries];

use App\Features\[Feature]\Services\[Service];
use App\Shared\Exceptions\ValidationException;
use App\Shared\GraphQL\[Mutations|Queries]\Base[Mutation|Query];
use Illuminate\Support\Facades\Auth;

/**
 * [Descripción breve del resolver]
 *
 * @param mixed $root
 * @param array{input: array} $args
 * @return mixed
 * @throws ValidationException
 */
class [ResolverName] extends Base[Mutation|Query]
{
    public function __construct(
        private readonly [Service] $service
    ) {}

    public function __invoke($root, array $args)
    {
        // 1. Extraer input
        $input = $args['input'] ?? $args;

        // 2. Validación adicional (si Service no lo hace)
        // ...

        // 3. Autorización (si no usa @can directive)
        // $this->authorize('action', Model::class);

        // 4. Llamar Service
        $result = $this->service->method($input);

        // 5. Transformar respuesta (si es necesario)
        return $result;
    }
}
```

#### 2.2. Inyección de Dependencias
```php
// ✅ CORRECTO: Inyectar Services por constructor
public function __construct(
    private readonly AuthService $authService,
    private readonly TokenService $tokenService
) {}

// ❌ INCORRECTO: No usar facades si hay Service
Auth::attempt(...); // NO! Usar AuthService
```

#### 2.3. Manejo de Errores
```php
use App\Shared\Exceptions\{
    ValidationException,
    AuthenticationException,
    AuthorizationException,
    NotFoundException
};

// Ejemplo
if (!$user) {
    throw new NotFoundException('User', $userId);
}

if (!$user->canAccess()) {
    throw new AuthorizationException('User is suspended');
}
```

---

### PASO 3: Testing (DESPUÉS de cada resolver)

#### 3.1. Testing Manual en GraphiQL
```bash
# Abrir GraphiQL
http://localhost:8000/graphiql

# Ejemplo: RegisterMutation
mutation {
  register(input: {
    email: "test@example.com"
    password: "password123"
    password_confirmation: "password123"
  }) {
    accessToken
    refreshToken
    user {
      id
      email
      userCode
    }
  }
}
```

#### 3.2. Checklist de Testing
- [ ] ¿Retorna los campos esperados?
- [ ] ¿Maneja errores correctamente? (email duplicado, validación, etc.)
- [ ] ¿Los DataLoaders evitan N+1?
- [ ] ¿Los eventos se disparan? (revisar logs)
- [ ] ¿Los jobs se encolan? (`docker compose logs queue`)
- [ ] ¿La BD se actualiza correctamente?

#### 3.3. Verificar Base de Datos
```bash
# Conectar a PostgreSQL
docker compose exec postgres psql -U helpdesk -d helpdesk

# Verificar datos
helpdesk=# SELECT * FROM auth.users ORDER BY created_at DESC LIMIT 1;
helpdesk=# SELECT * FROM auth.user_profiles WHERE user_id = '...';
helpdesk=# SELECT * FROM auth.refresh_tokens WHERE user_id = '...';
```

---

### PASO 4: Documentación y Commit

#### 4.1. Git Commit Message Template
```bash
git add app/Features/[Feature]/GraphQL/[Mutations|Queries]/[Resolver].php

git commit -m "feat([feature]): implement [ResolverName]

- Connect [Service] to [Resolver]
- Add validation for [campo]
- Dispatch [Event] on success
- Add DataLoader for [relación]
- Test: [breve descripción del test manual]

Resolves #[issue_number] (si aplica)"
```

#### 4.2. Actualizar Checklist
```markdown
# En este archivo (PLAN_CONEXION_RESOLVERS_COMPLETO.md)

## Progress Tracker
- [x] RegisterMutation ✅ (07-Oct-2025)
- [ ] LoginMutation ⏳
- [ ] ...
```

---

## 🚨 PROTOCOLO ANTI-OLVIDO (Contexto Permanente)

### CHECKLIST OBLIGATORIO ANTES DE CADA RESOLVER

#### ✅ 1. Verificar Compatibilidad Post-FASE 0
```bash
# ¿Los Models cambiaron estructura?
# - UserProfile.user_id es PK (no tiene 'id')
# - Role usa role_code como FK (no role_id)
# - UserRole.role_code es VARCHAR FK
# - RefreshToken tiene revoke_reason

# ¿El Service usa campos correctos?
git diff HEAD~10 app/Features/[Feature]/Services/
```

**Acción**: Si Service usa campos obsoletos → Refactorizar ANTES de conectar

---

#### ✅ 2. Reutilizar Shared Components

**ANTES de crear**:
```php
// Nueva Exception?
ls app/Shared/Exceptions/

// Nuevo Helper?
ls app/Shared/Helpers/

// Nuevo Validator?
ls app/Shared/Validators/

// Nueva directiva GraphQL?
ls app/Shared/GraphQL/Directives/
```

**Pregunta clave**: ¿Lo usarán 2+ features? → Shared

---

#### ✅ 3. DataLoaders SIEMPRE para Relaciones

```php
// ❌ MAL: N+1 Query Problem
foreach ($users as $user) {
    $user->profile; // Query por cada user!
}

// ✅ BIEN: Usar DataLoader
use App\Shared\GraphQL\DataLoaders\UserProfileByUserIdLoader;

$loader = app(UserProfileByUserIdLoader::class);
$profiles = $loader->loadMany($userIds);
```

**Regla**: Si el resolver retorna lista → Usar DataLoader

---

#### ✅ 4. Autorización en Capas

```graphql
# En Schema GraphQL
type Query {
  users: [User!]! @guard @can(ability: "viewAny", model: "User")
}
```

```php
// En Resolver (si schema no es suficiente)
$this->authorize('viewAny', User::class);

// En Service (lógica de negocio)
if (!$user->canAccess()) {
    throw new AuthorizationException('User suspended');
}
```

**Estrategia**: Schema → Resolver → Service (triple validación)

---

#### ✅ 5. Excepciones Profesionales

```php
// ✅ BIEN: Usar excepciones tipadas
use App\Shared\Exceptions\NotFoundException;

throw new NotFoundException('User', $userId);
// Output: "User with ID xxx not found"

// ❌ MAL: Exception genérica
throw new \Exception("User not found");
```

**Jerarquía**:
```
HelpdeskException (base)
├── ValidationException (input inválido)
├── AuthenticationException (no autenticado)
├── AuthorizationException (sin permisos)
└── NotFoundException (recurso no existe)
```

---

#### ✅ 6. Code Generator para Códigos Únicos

```php
use App\Shared\Helpers\CodeGenerator;

// Generar user_code
$userCode = CodeGenerator::generate('USR');
// Output: USR-2025-00001

// Generar company_code
$companyCode = CodeGenerator::generate('CMP');
// Output: CMP-2025-00001
```

**Códigos del sistema**:
- `USR` → Users (auth.users.user_code)
- `CMP` → Companies (business.companies.company_code)
- `REQ` → Company Requests (business.company_requests.request_code)
- `TKT` → Tickets (pendiente)

---

#### ✅ 7. Events para Acciones Importantes

```php
// En Service
use App\Features\Authentication\Events\UserRegistered;

$user = User::create($data);
event(new UserRegistered($user));
```

**Cuándo disparar Events**:
- ✅ Usuario registrado/eliminado/suspendido
- ✅ Rol asignado/revocado
- ✅ Empresa creada/actualizada
- ✅ Solicitud aprobada/rechazada
- ❌ Queries simples (no modifican datos)

---

#### ✅ 8. Testing Iterativo (Uno por Uno)

```bash
# Implementar RegisterMutation
# ↓
# Testear en GraphiQL
# ↓
# Verificar BD
# ↓
# Commit
# ↓
# Implementar LoginMutation
# (REPETIR)
```

**NO implementar múltiples resolvers sin testear**

---

## 📦 ESTRUCTURA ACTUAL DEL PROYECTO

### Features Completos
```
app/Features/
├── Authentication/
│   ├── GraphQL/
│   │   ├── DataLoaders/ (2) ✅
│   │   ├── Mutations/ (10) ⏳ DUMMY
│   │   ├── Queries/ (4) ⏳ DUMMY
│   │   └── Schema/authentication.graphql ✅
│   ├── Services/ (3) ✅ AuthService, TokenService, PasswordResetService
│   ├── Models/ (1) ✅ RefreshToken
│   ├── Events/ (5) ✅
│   ├── Listeners/ (4) ✅
│   ├── Jobs/ (2) ✅
│   ├── Policies/ (0) ✅ (usa guards)
│   └── Database/
│       ├── Migrations/ (1) ✅
│       ├── Seeders/ (0)
│       └── Factories/ (1) ✅
│
├── UserManagement/
│   ├── GraphQL/
│   │   ├── DataLoaders/ (3) ⚠️ 2 DUPLICADOS
│   │   ├── Mutations/ (11) ⏳ DUMMY
│   │   ├── Queries/ (6) ⏳ DUMMY
│   │   └── Schema/user-management.graphql ✅
│   ├── Services/ (3) ✅ UserService, RoleService, ProfileService
│   ├── Models/ (4) ✅ User, UserProfile, Role, UserRole
│   ├── Events/ (9) ✅
│   ├── Listeners/ (5) ✅
│   ├── Jobs/ (3) ✅
│   ├── Policies/ (2) ✅ UserPolicy, UserRolePolicy
│   └── Database/
│       ├── Migrations/ (5) ✅
│       ├── Seeders/ (2) ✅ RolesSeeder, DemoUsersSeeder
│       └── Factories/ (4) ✅
│
└── CompanyManagement/
    ├── GraphQL/
    │   ├── DataLoaders/ (2) ✅
    │   ├── Mutations/ (7) ⏳ DUMMY
    │   ├── Queries/ (5) ⏳ DUMMY
    │   └── Schema/company-management.graphql ✅
    ├── Services/ (3) ✅ CompanyService, CompanyRequestService, CompanyFollowService
    ├── Models/ (3) ✅ Company, CompanyRequest, CompanyFollower
    ├── Events/ (9) ✅
    ├── Listeners/ (5) ✅
    ├── Jobs/ (3) ✅
    ├── Policies/ (1) ✅ CompanyPolicy
    └── Database/
        ├── Migrations/ (4) ✅
        ├── Seeders/ (1) ✅ DemoCompaniesSeeder
        └── Factories/ (3) ✅
```

### Shared Components
```
app/Shared/
├── Enums/ (4) ✅
│   ├── UserStatus.php
│   ├── Role.php
│   ├── CompanyStatus.php
│   └── CompanyRequestStatus.php
│
├── Exceptions/ (5) ✅
│   ├── HelpdeskException.php (base)
│   ├── AuthenticationException.php
│   ├── AuthorizationException.php
│   ├── ValidationException.php
│   └── NotFoundException.php
│
├── Helpers/ (1) ✅
│   └── CodeGenerator.php
│
├── Traits/ (2) ✅
│   ├── HasUuid.php
│   └── Auditable.php
│
├── Validators/ (0) ⏳
│   └── (crear según necesidad)
│
└── GraphQL/
    ├── DataLoaders/ (6) ✅
    ├── Directives/ (3) ✅
    ├── Scalars/ (7) ✅
    ├── Queries/ (4) ✅
    └── Mutations/ (1) ✅ BaseMutation
```

---

## 🔧 TAREAS PREVIAS ANTES DE PRIMER RESOLVER

### 1. Actualizar Lighthouse Config
```php
// config/lighthouse.php línea 238
'dataLoaders' => [
    'App\\Shared\\GraphQL\\DataLoaders',
    'App\\Features\\Authentication\\GraphQL\\DataLoaders',
    'App\\Features\\UserManagement\\GraphQL\\DataLoaders',
    'App\\Features\\CompanyManagement\\GraphQL\\DataLoaders',
],
```

### 2. Eliminar DataLoaders Duplicados
```bash
# Eliminar estos 2 archivos (ya existen en Shared)
rm app/Features/UserManagement/GraphQL/DataLoaders/UserProfileByUserIdLoader.php
rm app/Features/UserManagement/GraphQL/DataLoaders/UserRolesByUserIdLoader.php
```

### 3. Auditar Discrepancias Model-Service
Verificar que Services usen campos correctos post-FASE 0:
- ✅ `UserProfile::find($userId)` NO `UserProfile::find($id)`
- ✅ `$userRole->role_code` NO `$userRole->role_id`
- ✅ `$refreshToken->revoke_reason` está disponible

---

## 📈 PROGRESS TRACKER

### Authentication (0/14)
- [ ] RegisterMutation ⏳ **PRÓXIMO**
- [ ] LoginMutation
- [ ] AuthStatusQuery
- [ ] VerifyEmailMutation
- [ ] ResendVerificationMutation
- [ ] RefreshTokenMutation
- [ ] MySessionsQuery
- [ ] RevokeOtherSessionMutation
- [ ] LogoutMutation
- [ ] ResetPasswordMutation
- [ ] ConfirmPasswordResetMutation
- [ ] PasswordResetStatusQuery
- [ ] EmailVerificationStatusQuery
- [ ] GoogleLoginMutation

### UserManagement (0/17)
- [ ] MeQuery
- [ ] MyProfileQuery
- [ ] CompleteMyProfileMutation
- [ ] UpdateMyProfileMutation
- [ ] UpdateMyPreferencesMutation
- [ ] AvailableRolesQuery
- [ ] UsersQuery
- [ ] UserQuery
- [ ] CompanyUsersQuery
- [ ] CreateUserMutation
- [ ] UpdateUserMutation
- [ ] SuspendUserMutation
- [ ] ActivateUserMutation
- [ ] DeleteUserMutation
- [ ] AssignRoleMutation
- [ ] RevokeRoleMutation
- [ ] UpdateUserRoleMutation

### CompanyManagement (0/12)
- [ ] RequestCompanyMutation
- [ ] CompanyRequestsQuery
- [ ] ApproveCompanyRequestMutation
- [ ] RejectCompanyRequestMutation
- [ ] CreateCompanyMutation
- [ ] CompaniesQuery
- [ ] CompanyQuery
- [ ] UpdateCompanyMutation
- [ ] FollowCompanyMutation
- [ ] UnfollowCompanyMutation
- [ ] MyFollowedCompaniesQuery
- [ ] IsFollowingCompanyQuery

**Total: 0/43 (0%)**

---

## 🎯 RESPUESTA A TUS PREGUNTAS

### ¿Es buen momento para iniciar RegisterMutation?

**SÍ, ES EL MOMENTO PERFECTO** ✅

**Razones**:
1. ✅ Base de datos 100% alineada con Modelado V7.0
2. ✅ Models actualizados y funcionando
3. ✅ Services con lógica completa
4. ✅ AuthService.register() ya implementado
5. ✅ Events/Jobs preparados
6. ✅ Schema GraphQL validado

**Lo único que falta**: Conectar el resolver dummy con AuthService

---

### ¿Los cambios de BD causarán discrepancias con el backend?

**POSIBLEMENTE, PERO MANEJABLES** ⚠️

**Potenciales discrepancias**:

1. **UserProfile PK cambió** (id → user_id)
   ```php
   // ❌ Antes
   UserProfile::find($profileId);

   // ✅ Ahora
   UserProfile::find($userId); // user_id es PK
   ```

2. **Role FK cambió** (role_id UUID → role_code VARCHAR)
   ```php
   // ❌ Antes
   $userRole->role_id

   // ✅ Ahora
   $userRole->role_code
   ```

3. **RefreshToken tiene campo nuevo**
   ```php
   // ✅ Ahora disponible
   $token->revoke_reason
   ```

**Estrategia**: Refactorizar **mientras conectamos** cada resolver. Si encontramos discrepancia → Fix inmediato.

---

### ¿Cómo evitar que yo olvide el contexto?

**PROTOCOLO ANTI-OLVIDO** 🧠

#### Cada vez que implementes un resolver, YO (Claude) haré:

1. ✅ **Leer este documento** (PLAN_CONEXION_RESOLVERS_COMPLETO.md)
2. ✅ **Verificar Shared/** antes de crear componentes
3. ✅ **Usar DataLoaders** para relaciones
4. ✅ **Revisar compatibilidad** Model-Service post-FASE 0
5. ✅ **Aplicar buenas prácticas** (excepciones tipadas, eventos, etc.)
6. ✅ **Testing iterativo** después de cada resolver

#### Cómo recordármelo:

**Opción A**: En cada petición, menciona:
> "Implementa RegisterMutation siguiendo el PLAN_CONEXION_RESOLVERS_COMPLETO.md"

**Opción B**: Crea un checklist corto:
> "RegisterMutation:
> - [ ] Verificar Shared exceptions
> - [ ] Usar DataLoaders
> - [ ] Testear en GraphiQL
> - [ ] Commit"

**Opción C**: Yo lo recordaré porque este documento está en `/documentacion/` y lo leeré antes de cada tarea.

---

## 🚀 PRÓXIMOS PASOS INMEDIATOS

### Paso 1: Preparación (5 minutos)
```bash
# 1. Actualizar config/lighthouse.php (DataLoaders)
# 2. Eliminar DataLoaders duplicados
# 3. Restart containers
docker compose restart app queue scheduler
```

### Paso 2: Implementar RegisterMutation (20 minutos)
```php
// app/Features/Authentication/GraphQL/Mutations/RegisterMutation.php

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\AuthService;
use App\Shared\Exceptions\ValidationException;
use App\Shared\GraphQL\Mutations\BaseMutation;

class RegisterMutation extends BaseMutation
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function __invoke($root, array $args)
    {
        $input = $args['input'];

        // AuthService ya valida todo
        $result = $this->authService->register($input);

        return [
            'accessToken' => $result['accessToken'],
            'refreshToken' => $result['refreshToken'],
            'expiresIn' => $result['expiresIn'],
            'user' => $result['user'],
        ];
    }
}
```

### Paso 3: Testing (10 minutos)
```graphql
mutation {
  register(input: {
    email: "admin@example.com"
    password: "SecurePass123!"
    password_confirmation: "SecurePass123!"
  }) {
    accessToken
    refreshToken
    expiresIn
    user {
      id
      email
      userCode
      emailVerified
    }
  }
}
```

### Paso 4: Verificar BD (2 minutos)
```sql
SELECT * FROM auth.users WHERE email = 'admin@example.com';
SELECT * FROM auth.user_profiles WHERE user_id = '...';
```

### Paso 5: Commit (2 minutos)
```bash
git add .
git commit -m "feat(auth): implement RegisterMutation

- Connect AuthService to RegisterMutation
- Return AuthPayload with tokens and user
- Test: Successfully registers user with email verification"
```

---

## 🎓 CONCLUSIÓN

**Estado**: LISTO PARA FASE 3 ✅

**Primer Resolver**: RegisterMutation ⭐

**Filosofía**: Implementar → Testear → Commit → Repetir

**Documentación**: Este archivo es la guía completa.

**Soporte**: Tengo toda la infraestructura lista. Solo falta conectar resolvers uno por uno.

---

**Documento creado**: 07 de Octubre de 2025
**Autor**: Claude Code + Desarrollador
**Próxima actualización**: Después de primer resolver implementado

---

¿Listo para implementar RegisterMutation? 🚀
