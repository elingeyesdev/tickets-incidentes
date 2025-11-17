# OPCIÓN 1: Plan Operativo Completo
## JWT con Rol Seleccionado - Todos los Archivos a Cambiar

**Fecha:** 2025-11-17
**Duración Estimada:** 7-9 horas
**Estado:** Listo para Implementar
**Entrega:** 1 mes ✅

---

## Tabla de Contenidos

1. [Archivos a Modificar](#archivos-a-modificar)
2. [Fase 1: Core JWT (2-3 horas)](#fase-1-core-jwt-2-3-horas)
3. [Fase 2: Refactor LISTA A Crítica (4-5 horas)](#fase-2-refactor-lista-a-crítica-4-5-horas)
4. [Fase 3: Testing (2 horas)](#fase-3-testing-2-horas)
5. [Checklist de Implementación](#checklist-de-implementación)

---

## Archivos a Modificar

### FASE 1: Core JWT (2-3 horas)

| Archivo | Líneas | Cambio | Tiempo |
|---------|--------|--------|--------|
| `TokenService.php` | 37-58 | Generar JWT con 1 rol | 45 min |
| `AuthController.php` | NEW | Crear switch-role endpoint | 60 min |
| `JWTHelper.php` | NEW | Agregar helpers | 30 min |
| `routes/api.php` | NEW | Agregar ruta | 5 min |

### FASE 2: Refactor LISTA A Crítica (4-5 horas)

| Archivo | Métodos | Líneas | Cambio | Tiempo |
|---------|---------|--------|--------|--------|
| `TicketPolicy.php` | 7 | 46-138 | Cambiar hasRoleInCompany → JWT | 60 min |
| `ArticleService.php` | 2 | 288-466 | Cambiar hasRole + userRoles → JWT | 90 min |
| `VisibilityService.php` | 2 | 71-93 | Cambiar DB queries → JWT | 30 min |
| `EnsureCompanyOwnership.php` | 2 | 40, 45 | Cambiar hasRole/hasRoleInCompany → JWT | 30 min |

### FASE 3: Testing (2 horas)

| Tipo | Archivos | Cambios | Tiempo |
|------|----------|---------|--------|
| Unit Tests | `JWTHelperTest.php` | Nuevos | 30 min |
| Feature Tests | `SwitchRoleTest.php` | Nuevos | 45 min |
| Mock Updates | `*Test.php` | Actualizaciones | 15 min |
| Integration | `IntegrationTest.php` | Nuevos | 30 min |

---

# FASE 1: Core JWT (2-3 horas)

## 1.1 TokenService.php

**Archivo:** `app/Features/Authentication/Services/TokenService.php`

**Cambio:** Generar JWT con UN SOLO rol (el seleccionado)

### ANTES

```php
// Línea 37-58
public function generateAccessToken(User $user, ?string $sessionId = null): string
{
    $payload = [
        'iss' => config('jwt.issuer'),
        'aud' => config('jwt.audience'),
        'iat' => time(),
        'exp' => time() + (config('jwt.ttl') * 60),
        'sub' => $user->id,
        'user_id' => $user->id,
        'email' => $user->email,
        'session_id' => $sessionId ?? Str::random(32),
        'roles' => $user->getAllRolesForJWT(), // ← TODOS los roles
    ];

    return JWT::encode(
        $payload,
        config('jwt.secret'),
        config('jwt.algo')
    );
}
```

### DESPUÉS

```php
// Línea 37-90
public function generateAccessToken(
    User $user,
    ?string $selectedRole = null,
    ?string $selectedCompanyId = null,
    ?string $sessionId = null
): string {
    // Determinar rol por defecto si no se especifica
    if (!$selectedRole) {
        $defaultRole = $this->getDefaultRole($user);
        $selectedRole = $defaultRole['code'];
        $selectedCompanyId = $defaultRole['company_id'] ?? null;
    }

    // Crear entrada de rol ÚNICA
    $roleEntry = ['code' => $selectedRole];
    if ($selectedCompanyId) {
        $roleEntry['company_id'] = $selectedCompanyId;
    }

    // JWT con UN SOLO rol en array
    $payload = [
        'iss' => config('jwt.issuer'),
        'aud' => config('jwt.audience'),
        'iat' => time(),
        'exp' => time() + (config('jwt.ttl') * 60),
        'sub' => $user->id,
        'user_id' => $user->id,
        'email' => $user->email,
        'session_id' => $sessionId ?? Str::random(32),
        'roles' => [$roleEntry], // ← UN SOLO rol en array
    ];

    return JWT::encode(
        $payload,
        config('jwt.secret'),
        config('jwt.algo')
    );
}

/**
 * Determinar rol por defecto (prioridad: COMPANY_ADMIN > AGENT > USER)
 */
private function getDefaultRole(User $user): array
{
    $allRoles = $user->getAllRolesForJWT();

    $priority = ['COMPANY_ADMIN', 'AGENT', 'USER', 'PLATFORM_ADMIN'];

    foreach ($priority as $roleCode) {
        $role = collect($allRoles)->firstWhere('code', $roleCode);
        if ($role) {
            return $role;
        }
    }

    // Fallback
    return ['code' => 'USER', 'company_id' => null];
}
```

**Qué cambió:**
- ✅ Acepta parámetros: `$selectedRole`, `$selectedCompanyId`
- ✅ Genera JWT con UN SOLO rol
- ✅ Método helper para determinar rol por defecto
- ✅ Estructura JWT: `"roles": [{ "code": "...", "company_id": "..." }]`

---

## 1.2 AuthController.php - Nuevo Endpoint switch-role

**Archivo:** `app/Features/Authentication/Http/Controllers/AuthController.php`

**Agregar nuevo método al controller:**

```php
/**
 * POST /api/auth/switch-role
 *
 * Cambiar el rol activo del usuario autenticado
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\JsonResponse
 *
 * Request:
 * {
 *   "role": "USER" | "AGENT" | "COMPANY_ADMIN" | "PLATFORM_ADMIN",
 *   "company_id": "uuid" (opcional, requerido para AGENT/COMPANY_ADMIN)
 * }
 *
 * Response:
 * {
 *   "accessToken": "eyJhbGc...",
 *   "tokenType": "Bearer",
 *   "expiresIn": 3600,
 *   "activeRole": {
 *     "code": "AGENT",
 *     "company_id": "uuid"
 *   }
 * }
 */
public function switchRole(Request $request): JsonResponse
{
    // Validar input
    $validated = $request->validate([
        'role' => 'required|string|in:USER,AGENT,COMPANY_ADMIN,PLATFORM_ADMIN',
        'company_id' => 'nullable|uuid',
    ]);

    try {
        $user = JWTHelper::getAuthenticatedUser();
    } catch (AuthenticationException $e) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    $roleCode = $validated['role'];
    $companyId = $validated['company_id'] ?? null;

    // VALIDAR contra BD que el usuario TIENE ese rol
    if ($companyId) {
        // Roles específicos a empresa
        if (!$user->hasRoleInCompany($roleCode, $companyId)) {
            return response()->json([
                'error' => "You don't have role {$roleCode} in this company",
                'code' => 'ROLE_NOT_FOUND'
            ], 403);
        }
    } else {
        // Roles globales (USER, PLATFORM_ADMIN)
        if (!$user->hasRole($roleCode)) {
            return response()->json([
                'error' => "You don't have role {$roleCode}",
                'code' => 'ROLE_NOT_FOUND'
            ], 403);
        }
    }

    // Generar JWT NUEVO con el rol seleccionado
    try {
        $accessToken = $this->tokenService->generateAccessToken(
            $user,
            $roleCode,
            $companyId
        );
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to generate token',
            'code' => 'TOKEN_GENERATION_FAILED'
        ], 500);
    }

    // Respuesta exitosa
    return response()->json([
        'accessToken' => $accessToken,
        'tokenType' => 'Bearer',
        'expiresIn' => config('jwt.ttl') * 60,
        'activeRole' => [
            'code' => $roleCode,
            'company_id' => $companyId,
        ],
    ], 200);
}
```

**Qué hace:**
- ✅ Recibe: `{ "role": "AGENT", "company_id": "..." }`
- ✅ Valida contra BD que el usuario tiene ese rol
- ✅ Regenera JWT con el nuevo rol
- ✅ Retorna nuevo JWT + metadata

---

## 1.3 routes/api.php - Agregar Ruta

**Archivo:** `routes/api.php`

**Agregar en el grupo de rutas autenticadas:**

```php
// Aproximadamente línea 40-50, dentro de Route::middleware('auth:jwt')
Route::post('/auth/switch-role', [AuthController::class, 'switchRole'])
    ->name('auth.switch-role');
```

---

## 1.4 JWTHelper.php - Agregar Helpers

**Archivo:** `app/Shared/Helpers/JWTHelper.php`

**Agregar estos métodos al final de la clase:**

```php
/**
 * Obtener el rol activo actual desde el JWT
 *
 * Con OPCIÓN 1, el JWT siempre contiene UN SOLO rol
 *
 * @return string El código del rol activo (ej: "AGENT", "USER")
 * @throws AuthenticationException Si JWT no tiene roles
 */
public static function getSelectedRole(): string
{
    try {
        $roles = self::getRoles();

        if (empty($roles)) {
            throw new AuthenticationException('No roles found in JWT');
        }

        // Retornar el código del ÚNICO rol en el JWT
        return $roles[0]['code'] ?? 'USER';
    } catch (\Exception $e) {
        throw new AuthenticationException('Failed to get selected role from JWT');
    }
}

/**
 * Obtener el company_id del rol activo
 *
 * @return string|null El company_id del rol activo (null para USER, PLATFORM_ADMIN)
 * @throws AuthenticationException Si JWT no tiene roles
 */
public static function getSelectedCompanyId(): ?string
{
    try {
        $roles = self::getRoles();

        if (empty($roles)) {
            return null;
        }

        return $roles[0]['company_id'] ?? null;
    } catch (\Exception $e) {
        return null;
    }
}
```

**Qué hace:**
- ✅ `getSelectedRole()`: Retorna el código del rol activo ("USER", "AGENT", etc)
- ✅ `getSelectedCompanyId()`: Retorna el company_id del rol activo (null si no aplica)
- ✅ Ambos leen directamente del JWT payload

---

# FASE 2: Refactor LISTA A Crítica (4-5 horas)

## 2.1 TicketPolicy.php - 7 Métodos a Cambiar

**Archivo:** `app/Features/TicketManagement/Policies/TicketPolicy.php`

**Este es el patrón que se repite en TODOS los métodos:**

### PATRÓN: Cambiar hasRoleInCompany → JWT

```php
// ANTES
if ($user->hasRoleInCompany('AGENT', $ticket->company_id)) {
    return true;
}

// DESPUÉS
if (JWTHelper::hasRoleFromJWT('AGENT')) {
    if (JWTHelper::getSelectedCompanyId() === $ticket->company_id) {
        return true;
    }
}
```

### Método 1: view() - Línea 38-54

```php
// ANTES
public function view(User $user, Ticket $ticket): bool
{
    if ($ticket->created_by_user_id === $user->id) {
        return true;
    }

    if ($user->hasRoleInCompany('AGENT', $ticket->company_id)) {
        return true;
    }

    if ($user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)) {
        return true;
    }

    return false;
}

// DESPUÉS
public function view(User $user, Ticket $ticket): bool
{
    // Creador puede ver siempre
    if ($ticket->created_by_user_id === $user->id) {
        return true;
    }

    // AGENT de la empresa puede ver
    if (JWTHelper::hasRoleFromJWT('AGENT')) {
        if (JWTHelper::getSelectedCompanyId() === $ticket->company_id) {
            return true;
        }
    }

    // COMPANY_ADMIN de la empresa puede ver
    if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
        if (JWTHelper::getSelectedCompanyId() === $ticket->company_id) {
            return true;
        }
    }

    return false;
}
```

### Métodos 2-7: Aplicar Mismo Patrón

**update()** - Línea 60-76

```php
// ANTES
if ($user->hasRoleInCompany('AGENT', $ticket->company_id)) {
    return true;
}

if ($user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)) {
    return true;
}

// DESPUÉS
if (JWTHelper::hasRoleFromJWT('AGENT')) {
    if (JWTHelper::getSelectedCompanyId() === $ticket->company_id) {
        return true;
    }
}

if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
    if (JWTHelper::getSelectedCompanyId() === $ticket->company_id) {
        return true;
    }
}
```

**delete()** - Línea 82-86

```php
// ANTES
return $user->hasRoleInCompany('COMPANY_ADMIN', $ticket->company_id)
    && $ticket->status === TicketStatus::CLOSED;

// DESPUÉS
return JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')
    && JWTHelper::getSelectedCompanyId() === $ticket->company_id
    && $ticket->status === TicketStatus::CLOSED;
```

**resolve()** - Línea 91-94

```php
// ANTES
return $user->hasRoleInCompany('AGENT', $ticket->company_id);

// DESPUÉS
return JWTHelper::hasRoleFromJWT('AGENT')
    && JWTHelper::getSelectedCompanyId() === $ticket->company_id;
```

**close()** - Línea 99-109

```php
// ANTES
if ($user->hasRoleInCompany('AGENT', $ticket->company_id)) {
    return true;
}

return $ticket->created_by_user_id === $user->id
    && $ticket->status === TicketStatus::RESOLVED;

// DESPUÉS
if (JWTHelper::hasRoleFromJWT('AGENT')) {
    if (JWTHelper::getSelectedCompanyId() === $ticket->company_id) {
        return true;
    }
}

return $ticket->created_by_user_id === $user->id
    && $ticket->status === TicketStatus::RESOLVED;
```

**reopen()** - Línea 115-131

```php
// ANTES
return $user->hasRoleInCompany('AGENT', $ticket->company_id);

// DESPUÉS
return JWTHelper::hasRoleFromJWT('AGENT')
    && JWTHelper::getSelectedCompanyId() === $ticket->company_id;
```

**assign()** - Línea 136-139

```php
// ANTES
return $user->hasRoleInCompany('AGENT', $ticket->company_id);

// DESPUÉS
return JWTHelper::hasRoleFromJWT('AGENT')
    && JWTHelper::getSelectedCompanyId() === $ticket->company_id;
```

---

## 2.2 ArticleService.php - 2 Métodos a Cambiar

**Archivo:** `app/Features/ContentManagement/Services/ArticleService.php`

### Método 1: listArticles() - Línea 372-466

Este es el cambio más grande. **REEMPLAZAR COMPLETO:**

```php
// ANTES (if/elseif en cascada)
public function listArticles(User $user, array $filters): LengthAwarePaginator
{
    $query = HelpCenterArticle::query();

    $requestedCompanyId = $filters['company_id'] ?? null;

    if ($user->hasRole('PLATFORM_ADMIN')) {
        // PLATFORM_ADMIN
        if ($requestedCompanyId) {
            $query->where('company_id', $requestedCompanyId);
        }
    } elseif ($user->hasRole('COMPANY_ADMIN')) {
        // COMPANY_ADMIN
        $adminRole = $user->userRoles()
            ->where('role_code', 'COMPANY_ADMIN')
            ->first();

        if (!$adminRole || !$adminRole->company_id) {
            throw new Exception('Usuario no tiene empresa asignada', 500);
        }

        $adminCompanyId = $adminRole->company_id;

        if ($requestedCompanyId && $requestedCompanyId !== $adminCompanyId) {
            throw new Exception('Forbidden: No tienes permiso para acceder a artículos de otra empresa', 403);
        }

        $query->where('company_id', $adminCompanyId);

        if (!isset($filters['status'])) {
            // No agregar WHERE para status (ve todos)
        } else {
            $status = strtoupper($filters['status']);
            $query->where('status', $status);
        }
    } elseif ($user->hasRole('AGENT')) {
        // AGENT
        // ... más lógica
    } else {
        // USER
        // ... más lógica
    }

    // ... rest
}

// DESPUÉS (usa JWT)
public function listArticles(User $user, array $filters): LengthAwarePaginator
{
    $query = HelpCenterArticle::query();

    $requestedCompanyId = $filters['company_id'] ?? null;

    // Obtener rol activo del JWT
    $activeRole = JWTHelper::getSelectedRole();
    $activeCompanyId = JWTHelper::getSelectedCompanyId();

    if ($activeRole === 'PLATFORM_ADMIN') {
        // PLATFORM_ADMIN: Ve TODO sin restricción
        if ($requestedCompanyId) {
            $query->where('company_id', $requestedCompanyId);
        }
        // Sin WHERE para status (ve todos)

    } elseif ($activeRole === 'COMPANY_ADMIN') {
        // COMPANY_ADMIN: Solo su empresa
        if (!$activeCompanyId) {
            throw new Exception('Company ID not found in JWT for COMPANY_ADMIN role', 500);
        }

        // Si especifica ?company_id, validar que sea su empresa
        if ($requestedCompanyId && $requestedCompanyId !== $activeCompanyId) {
            throw new Exception('Forbidden: No tienes permiso para acceder a artículos de otra empresa', 403);
        }

        $query->where('company_id', $activeCompanyId);

        // Status default COMPANY_ADMIN: todos (DRAFT + PUBLISHED)
        if (isset($filters['status'])) {
            $status = strtoupper($filters['status']);
            $query->where('status', $status);
        }

    } elseif ($activeRole === 'AGENT') {
        // AGENT: Solo PUBLISHED de su empresa
        if (!$activeCompanyId) {
            throw new Exception('Company ID not found in JWT for AGENT role', 500);
        }

        if ($requestedCompanyId && $requestedCompanyId !== $activeCompanyId) {
            throw new Exception('Forbidden: No tienes permiso para acceder a artículos de otra empresa', 403);
        }

        $query->where('company_id', $activeCompanyId);
        $query->where('status', 'PUBLISHED');

    } else {
        // USER: Solo PUBLISHED de empresas que sigue
        $followedCompanyIds = $user->followedCompanies()
            ->pluck('business.companies.id')
            ->toArray();

        if ($requestedCompanyId && !in_array($requestedCompanyId, $followedCompanyIds)) {
            throw new Exception('Forbidden: No tienes permiso para acceder a artículos de esta empresa', 403);
        }

        if (empty($followedCompanyIds)) {
            return $query->paginate(0); // Sin resultados
        }

        $query->whereIn('company_id', $followedCompanyIds);
        $query->where('status', 'PUBLISHED');
    }

    // Aplicar filtros, búsqueda, paginación... (igual que antes)
    if (!empty($filters['search'])) {
        $search = $filters['search'];
        $query->where(function (Builder $q) use ($search) {
            $q->whereRaw("title ILIKE ?", ["%{$search}%"])
                ->orWhereRaw("content ILIKE ?", ["%{$search}%"]);
        });
    }

    $perPage = $filters['per_page'] ?? 15;
    $sortBy = $filters['sort_by'] ?? 'created_at';
    $sortOrder = $filters['sort_order'] ?? 'desc';

    $query->orderBy($sortBy, $sortOrder);

    return $query->paginate($perPage);
}
```

### Método 2: viewArticle() - Línea 271-357

```php
// ANTES
if ($user->hasRole('PLATFORM_ADMIN')) {
    return $article;
}

if ($user->hasRole('COMPANY_ADMIN')) {
    $adminRole = $user->userRoles()
        ->where('role_code', 'COMPANY_ADMIN')
        ->first();

    if (!$adminRole || $adminRole->company_id !== $article->company_id) {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }

    return $article;
}

if ($user->hasRole('AGENT')) {
    $agentRole = $user->userRoles()
        ->where('role_code', 'AGENT')
        ->first();

    if (!$agentRole || !$agentRole->company_id) {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }

    if ($article->status !== 'PUBLISHED') {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }

    if ($agentRole->company_id !== $article->company_id) {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }

    return $article;
}

if ($user->hasRole('USER')) {
    if ($article->status !== 'PUBLISHED') {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }

    $isFollowing = $user->followedCompanies()
        ->where('business.companies.id', $article->company_id)
        ->exists();

    if (!$isFollowing) {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }

    $article->increment('views_count');
    $article->refresh();

    return $article;
}

// DESPUÉS
$activeRole = JWTHelper::getSelectedRole();
$activeCompanyId = JWTHelper::getSelectedCompanyId();

if ($activeRole === 'PLATFORM_ADMIN') {
    return $article;
}

if ($activeRole === 'COMPANY_ADMIN') {
    if (!$activeCompanyId || $activeCompanyId !== $article->company_id) {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }
    return $article;
}

if ($activeRole === 'AGENT') {
    if (!$activeCompanyId) {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }

    if ($article->status !== 'PUBLISHED') {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }

    if ($activeCompanyId !== $article->company_id) {
        throw new AuthorizationException('Forbidden: You do not have permission to view this article');
    }

    return $article;
}

// USER
if ($article->status !== 'PUBLISHED') {
    throw new AuthorizationException('Forbidden: You do not have permission to view this article');
}

$isFollowing = $user->followedCompanies()
    ->where('business.companies.id', $article->company_id)
    ->exists();

if (!$isFollowing) {
    throw new AuthorizationException('Forbidden: You do not have permission to view this article');
}

$article->increment('views_count');
$article->refresh();

return $article;
```

---

## 2.3 VisibilityService.php - 2 Métodos Simples

**Archivo:** `app/Features/ContentManagement/Services/VisibilityService.php`

### Método 1: isPlatformAdmin() - Línea 69-76

```php
// ANTES
public function isPlatformAdmin(User $user): bool
{
    return DB::table('auth.user_roles')
        ->where('user_id', $user->id)
        ->where('role_code', 'PLATFORM_ADMIN')
        ->where('is_active', true)
        ->exists();
}

// DESPUÉS
public function isPlatformAdmin(User $user): bool
{
    return JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN');
}
```

### Método 2: isCompanyAdmin() - Línea 85-93

```php
// ANTES
public function isCompanyAdmin(User $user, string $companyId): bool
{
    return DB::table('auth.user_roles')
        ->where('user_id', $user->id)
        ->where('company_id', $companyId)
        ->where('role_code', 'COMPANY_ADMIN')
        ->where('is_active', true)
        ->exists();
}

// DESPUÉS
public function isCompanyAdmin(User $user, string $companyId): bool
{
    return JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')
        && JWTHelper::getSelectedCompanyId() === $companyId;
}
```

---

## 2.4 EnsureCompanyOwnership.php - 2 Cambios Pequeños

**Archivo:** `app/Http/Middleware/EnsureCompanyOwnership.php`

### Cambio 1: Línea 40

```php
// ANTES
if ($user->hasRole('PLATFORM_ADMIN')) {
    return $next($request);
}

// DESPUÉS
if (JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN')) {
    return $next($request);
}
```

### Cambio 2: Línea 45

```php
// ANTES
if ($user->hasRoleInCompany('COMPANY_ADMIN', $company->id)) {
    return $next($request);
}

// DESPUÉS
if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')
    && JWTHelper::getSelectedCompanyId() === $company->id) {
    return $next($request);
}
```

---

# FASE 3: Testing (2 horas)

## 3.1 JWTHelperTest.php - Nuevos Unit Tests

**Archivo:** `tests/Unit/Helpers/JWTHelperTest.php`

```php
<?php

namespace Tests\Unit\Helpers;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Auth\AuthenticationException;
use PHPUnit\Framework\TestCase;

class JWTHelperTest extends TestCase
{
    /**
     * Test getSelectedRole retorna el rol del JWT
     */
    public function test_get_selected_role_returns_role_code(): void
    {
        $payload = [
            'roles' => [
                ['code' => 'AGENT', 'company_id' => 'emp-1']
            ]
        ];

        request()->attributes->set('jwt_payload', $payload);

        $role = JWTHelper::getSelectedRole();

        $this->assertEquals('AGENT', $role);
    }

    /**
     * Test getSelectedCompanyId retorna el company_id
     */
    public function test_get_selected_company_id_returns_company_id(): void
    {
        $payload = [
            'roles' => [
                ['code' => 'AGENT', 'company_id' => 'emp-1']
            ]
        ];

        request()->attributes->set('jwt_payload', $payload);

        $companyId = JWTHelper::getSelectedCompanyId();

        $this->assertEquals('emp-1', $companyId);
    }

    /**
     * Test getSelectedCompanyId retorna null para USER
     */
    public function test_get_selected_company_id_returns_null_for_user(): void
    {
        $payload = [
            'roles' => [
                ['code' => 'USER', 'company_id' => null]
            ]
        ];

        request()->attributes->set('jwt_payload', $payload);

        $companyId = JWTHelper::getSelectedCompanyId();

        $this->assertNull($companyId);
    }
}
```

---

## 3.2 SwitchRoleTest.php - Feature Tests del Endpoint

**Archivo:** `tests/Feature/Authentication/SwitchRoleTest.php`

```php
<?php

namespace Tests\Feature\Authentication;

use App\Features\CompanyManagement\Models\Company;
use App\Features\UserManagement\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\RefreshDatabaseWithoutTransactions;

class SwitchRoleTest extends TestCase
{
    use RefreshDatabaseWithoutTransactions;

    /**
     * Test: Switch a rol válido retorna 200 con nuevo JWT
     */
    #[Test]
    public function test_switch_role_valid_returns_200(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()
            ->withRole('USER')
            ->withRole('AGENT', $company->id)
            ->create();

        // Login como USER (default)
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $currentToken = $loginResponse->json('accessToken');

        // Switch a AGENT
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$currentToken}",
        ])->postJson('/api/auth/switch-role', [
            'role' => 'AGENT',
            'company_id' => $company->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'accessToken',
            'tokenType',
            'expiresIn',
            'activeRole' => [
                'code',
                'company_id',
            ],
        ]);
        $response->assertJsonPath('activeRole.code', 'AGENT');
        $response->assertJsonPath('activeRole.company_id', $company->id);
    }

    /**
     * Test: Switch a rol que no tiene retorna 403
     */
    #[Test]
    public function test_switch_role_invalid_returns_403(): void
    {
        $user = User::factory()->withRole('USER')->create();

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $currentToken = $loginResponse->json('accessToken');

        // Intentar switch a AGENT (no tiene)
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$currentToken}",
        ])->postJson('/api/auth/switch-role', [
            'role' => 'AGENT',
            'company_id' => 'fake-company-id',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('code', 'ROLE_NOT_FOUND');
    }

    /**
     * Test: Nuevo JWT funciona para GET /api/tickets como AGENT
     */
    #[Test]
    public function test_switched_jwt_affects_ticket_list(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()
            ->withRole('USER')
            ->withRole('AGENT', $company->id)
            ->create();

        $ticket = \App\Features\TicketManagement\Models\Ticket::factory()->create([
            'company_id' => $company->id,
            'created_by_user_id' => $user->id,
        ]);

        // Login
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $loginResponse->json('accessToken');

        // GET /api/tickets como USER (default)
        $responseAsUser = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/tickets');

        $ticketsAsUser = $responseAsUser->json('data');

        // Switch a AGENT
        $switchResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/auth/switch-role', [
            'role' => 'AGENT',
            'company_id' => $company->id,
        ]);

        $newToken = $switchResponse->json('accessToken');

        // GET /api/tickets como AGENT
        $responseAsAgent = $this->withHeaders([
            'Authorization' => "Bearer {$newToken}",
        ])->getJson('/api/tickets');

        $ticketsAsAgent = $responseAsAgent->json('data');

        // Ambas deberían retornar el ticket (USER: creador, AGENT: de su empresa)
        $this->assertCount(1, $ticketsAsUser);
        $this->assertCount(1, $ticketsAsAgent);
    }
}
```

---

## 3.3 Actualizar Mocks en Tests Existentes

**En todos los tests que mockean JWT, cambiar:**

```php
// ANTES
'roles' => [
    ['code' => 'USER'],
    ['code' => 'AGENT', 'company_id' => $company->id],
]

// DESPUÉS
'roles' => [
    ['code' => 'AGENT', 'company_id' => $company->id]
]
```

**Archivos a actualizar:**
- `tests/Feature/TicketManagement/Permissions/RoleBasedAccessTest.php`
- `tests/Feature/TicketManagement/Integration/CompleteTicketFlowTest.php`
- `tests/Feature/ContentManagement/Permissions/*.php`
- Cualquier otro test que use JWT mock

---

# Checklist de Implementación

## Antes de Empezar

- [ ] Crea rama: `git checkout -b feature/opcion-1-jwt-rol`
- [ ] Lee este documento completo
- [ ] Ten el documento original abierto como referencia

## Fase 1: Core JWT (2-3 horas)

### TokenService.php

- [ ] Modificar `generateAccessToken()` para aceptar `$selectedRole`, `$selectedCompanyId`
- [ ] Crear método `getDefaultRole()`
- [ ] JWT genera con UN SOLO rol en array
- [ ] Tests unitarios pasan

### AuthController.php

- [ ] Crear método `switchRole()`
- [ ] Validar contra BD
- [ ] Regenerar JWT
- [ ] Retornar JSON correcto
- [ ] Probr endpoint con Postman/curl

### JWTHelper.php

- [ ] Agregar `getSelectedRole()`
- [ ] Agregar `getSelectedCompanyId()`
- [ ] Unit tests pasan

### routes/api.php

- [ ] Agregar ruta POST `/api/auth/switch-role`
- [ ] Verificar que está en middleware `auth:jwt`

### Tests Fase 1

- [ ] `php artisan test tests/Unit/Helpers/JWTHelperTest.php` → PASS
- [ ] Verificar que tests de login siguen pasando

## Fase 2: Refactor LISTA A (4-5 horas)

### TicketPolicy.php

- [ ] Cambiar `view()` (línea 38-54)
- [ ] Cambiar `update()` (línea 60-76)
- [ ] Cambiar `delete()` (línea 82-86)
- [ ] Cambiar `resolve()` (línea 91-94)
- [ ] Cambiar `close()` (línea 99-109)
- [ ] Cambiar `reopen()` (línea 115-131)
- [ ] Cambiar `assign()` (línea 136-139)
- [ ] Tests de TicketManagement pasan

### ArticleService.php

- [ ] Cambiar `listArticles()` (línea 372-466)
- [ ] Cambiar `viewArticle()` (línea 271-357)
- [ ] Tests de ContentManagement pasan

### VisibilityService.php

- [ ] Cambiar `isPlatformAdmin()` (línea 69-76)
- [ ] Cambiar `isCompanyAdmin()` (línea 85-93)
- [ ] Tests relacionados pasan

### EnsureCompanyOwnership.php

- [ ] Cambiar línea 40 (hasRole → JWT)
- [ ] Cambiar línea 45 (hasRoleInCompany → JWT)
- [ ] Tests de middleware pasan

### Verificación Fase 2

- [ ] `php artisan test tests/Feature/TicketManagement --no-coverage` → PASS
- [ ] `php artisan test tests/Feature/ContentManagement --no-coverage` → PASS

## Fase 3: Testing (2 horas)

### Nuevos Tests

- [ ] Crear `tests/Feature/Authentication/SwitchRoleTest.php`
- [ ] Tests de switch-role pasan

### Mock Updates

- [ ] Actualizar mocks en RoleBasedAccessTest.php
- [ ] Actualizar mocks en CompleteTicketFlowTest.php
- [ ] Actualizar mocks en otros tests
- [ ] Todos los tests pasan

### Validación Final

- [ ] `php artisan test --no-coverage` → Todos PASS
- [ ] NO hay regressiones
- [ ] Tests de integración E2E OK

## Deploy Simulado

- [ ] Verificar que no hay errores en IDE
- [ ] Revisar que no quedan `hasRole(` o `hasRoleInCompany(` en componentes críticos
- [ ] Git status limpio (todos los cambios committed)

## Post-Implementation

- [ ] Documentar cualquier encontramiento especial
- [ ] Crear PR si es necesario
- [ ] Deploy a staging
- [ ] Testing en staging
- [ ] Deploy a production

---

# Resumen Ejecutivo

## Cambios Principales

| Componente | Líneas | Tipo | Tiempo |
|-----------|--------|------|--------|
| **TokenService** | 30 | Nueva lógica | 45 min |
| **AuthController** | 40 | Nuevo endpoint | 60 min |
| **JWTHelper** | 30 | Nuevos helpers | 30 min |
| **TicketPolicy** | 50 | Reemplazo patrones | 60 min |
| **ArticleService** | 130 | Reemplazo patrones | 90 min |
| **VisibilityService** | 15 | Reemplazo patrones | 30 min |
| **EnsureCompanyOwnership** | 10 | Reemplazo patrones | 30 min |
| **Tests** | 200 | Nuevos + updates | 120 min |
| **TOTAL** | **495** | **Implementación** | **7-9 horas** |

## JWT Antes vs Después

```json
ANTES (múltiples roles):
{
  "roles": [
    { "code": "USER" },
    { "code": "AGENT", "company_id": "emp-1" }
  ]
}

DESPUÉS (1 rol):
{
  "roles": [
    { "code": "AGENT", "company_id": "emp-1" }
  ]
}
```

## Impacto en Endpoints

```
GET /api/tickets
ANTES: Retorna tickets basado en PRIMER rol encontrado
DESPUÉS: Retorna tickets basado en rol seleccionado en JWT

POST /api/auth/switch-role
ANTES: No existe
DESPUÉS: Regenera JWT con nuevo rol
```

---

**Próximos Pasos:** Comienza por Fase 1 mañana. ¡Puedes hacerlo! 💪
