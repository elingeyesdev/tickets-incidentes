# Plan de Acción: Implementación Sistema Multi-Rol Activo

## Fecha: 2025-12-07
## Proyecto: Helpdesk - Sistema Active Role

---

## Fase 1: Infraestructura Base (Día 1)

### 1.1. Migration - active_role_id
**Archivo:** `database/migrations/YYYY_MM_DD_add_active_role_id_to_users.php`

```php
Schema::table('auth.users', function (Blueprint $table) {
    $table->uuid('active_role_id')->nullable()->after('id');
    $table->foreign('active_role_id')
          ->references('id')
          ->on('auth.user_roles')
          ->onDelete('set null');
});
```

**Tareas:**
- [ ] Crear migration
- [ ] Ejecutar migration en dev
- [ ] Actualizar modelo User con relación activeRole
- [ ] Verificar que tests no se rompan

---

### 1.2. Helper - ActiveRoleHelper
**Archivo:** `app/Shared/Helpers/ActiveRoleHelper.php`

```php
<?php

namespace App\Shared\Helpers;

use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;

class ActiveRoleHelper
{
    /**
     * Obtener el rol activo del usuario autenticado
     */
    public static function getActiveRole(?User $user = null): ?UserRole
    {
        $user = $user ?? JWTHelper::getAuthenticatedUser();
        
        if (!$user || !$user->active_role_id) {
            return null;
        }
        
        return $user->userRoles()
            ->where('id', $user->active_role_id)
            ->where('is_active', true)
            ->with('role')
            ->first();
    }
    
    public static function getActiveRoleCode(?User $user = null): ?string
    {
        return self::getActiveRole($user)?->role_code;
    }
    
    public static function getActiveCompanyId(?User $user = null): ?string
    {
        return self::getActiveRole($user)?->company_id;
    }
    
    public static function hasActiveRole(string $roleCode, ?User $user = null): bool
    {
        $activeRole = self::getActiveRole($user);
        return $activeRole && $activeRole->role_code === $roleCode;
    }
    
    /**
     * Verificar si usuario puede cambiar a un rol específico
     */
    public static function canSwitchToRole(string $userRoleId, ?User $user = null): bool
    {
        $user = $user ?? JWTHelper::getAuthenticatedUser();
        
        return $user->userRoles()
            ->where('id', $userRoleId)
            ->where('is_active', true)
            ->exists();
    }
}
```

**Tareas:**
- [ ] Crear helper con métodos básicos
- [ ] Escribir tests unitarios para ActiveRoleHelper
- [ ] Documentar en README.md

---

### 1.3. Middleware - ValidateActiveRole
**Archivo:** `app/Http/Middleware/ValidateActiveRole.php`

```php
<?php

namespace App\Http\Middleware;

use App\Shared\Helpers\ActiveRoleHelper;
use App\Shared\Helpers\JWTHelper;
use Closure;
use Illuminate\Http\Request;

class ValidateActiveRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = JWTHelper::getAuthenticatedUser();
        
        // Si no tiene active_role_id, asignar el primer rol activo
        if (!$user->active_role_id) {
            $firstActiveRole = $user->userRoles()
                ->where('is_active', true)
                ->orderByRaw("
                    CASE role_code
                        WHEN 'PLATFORM_ADMIN' THEN 1
                        WHEN 'COMPANY_ADMIN' THEN 2
                        WHEN 'AGENT' THEN 3
                        WHEN 'USER' THEN 4
                        ELSE 5
                    END
                ")
                ->first();
            
            if ($firstActiveRole) {
                $user->update(['active_role_id' => $firstActiveRole->id]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no tiene roles activos',
                ], 403);
            }
        }
        
        // Validar que active_role_id siga siendo válido
        $activeRole = ActiveRoleHelper::getActiveRole($user);
        
        if (!$activeRole) {
            return response()->json([
                'success' => false,
                'message' => 'Rol activo inválido o desactivado',
            ], 403);
        }
        
        return $next($request);
    }
}
```

**Tareas:**
- [ ] Crear middleware
- [ ] Registrar en Kernel.php como 'active.role'
- [ ] Aplicar a rutas api.php (excepto login/register)
- [ ] Escribir tests

---

### 1.4. Endpoints de Gestión de Rol Activo
**Archivo:** `app/Features/UserManagement/Http/Controllers/ActiveRoleController.php`

```php
<?php

namespace App\Features\UserManagement\Http\Controllers;

use App\Shared\Helpers\ActiveRoleHelper;
use App\Shared\Helpers\JWTHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ActiveRoleController extends Controller
{
    /**
     * GET /api/users/me/available-roles
     * Listar roles disponibles para cambiar
     */
    public function availableRoles(): JsonResponse
    {
        $user = JWTHelper::getAuthenticatedUser();
        
        $roles = $user->userRoles()
            ->where('is_active', true)
            ->with(['role', 'company'])
            ->get()
            ->map(function ($userRole) use ($user) {
                return [
                    'id' => $userRole->id,
                    'role_code' => $userRole->role_code,
                    'role_name' => $userRole->role->name,
                    'company_id' => $userRole->company_id,
                    'company_name' => $userRole->company?->name,
                    'is_active_role' => $userRole->id === $user->active_role_id,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }
    
    /**
     * POST /api/users/me/active-role
     * Cambiar rol activo
     */
    public function switchRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_role_id' => 'required|uuid|exists:auth.user_roles,id',
        ]);
        
        $user = JWTHelper::getAuthenticatedUser();
        
        // Verificar que el usuario pueda cambiar a ese rol
        if (!ActiveRoleHelper::canSwitchToRole($validated['user_role_id'], $user)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para cambiar a ese rol',
            ], 403);
        }
        
        // Actualizar active_role_id
        $user->update(['active_role_id' => $validated['user_role_id']]);
        
        $activeRole = ActiveRoleHelper::getActiveRole($user);
        
        return response()->json([
            'success' => true,
            'message' => 'Rol activo actualizado correctamente',
            'data' => [
                'active_role_id' => $activeRole->id,
                'role_code' => $activeRole->role_code,
                'company_id' => $activeRole->company_id,
            ],
        ]);
    }
}
```

**Rutas a agregar en `routes/api.php`:**
```php
Route::middleware(['auth:api', 'active.role'])->group(function () {
    Route::get('/users/me/available-roles', [ActiveRoleController::class, 'availableRoles']);
    Route::post('/users/me/active-role', [ActiveRoleController::class, 'switchRole']);
});
```

**Tareas:**
- [ ] Crear controlador
- [ ] Agregar rutas
- [ ] Escribir tests funcionales
- [ ] Documentar en Swagger/OpenAPI

---

## Fase 2: Endpoints Críticos (Días 2-3)

### Prioridad 1: TicketService

**Archivo:** `app/Features/TicketManagement/Services/TicketService.php`

#### Cambio 1: getUserRole() → getActiveRole()
```php
// ❌ ANTES (líneas 106-120)
private function getUserRole(User $user): string
{
    if (JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN')) {
        return 'PLATFORM_ADMIN';
    }
    // ... resto de prioridades fijas
}

// ✅ DESPUÉS
private function getUserRole(User $user): string
{
    $activeRole = ActiveRoleHelper::getActiveRole($user);
    
    if (!$activeRole) {
        throw new \Exception('Usuario no tiene rol activo configurado', 403);
    }
    
    return $activeRole->role_code;
}
```

#### Cambio 2: applyVisibilityFilters() - getCompanyIdFromJWT() → getActiveCompanyId()
```php
// ❌ ANTES (líneas 216-246)
private function applyVisibilityFilters(Builder $query, string $userId, string $userRole): void
{
    if ($userRole === 'COMPANY_ADMIN') {
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN'); // ❌
        // ...
    }
}

// ✅ DESPUÉS
private function applyVisibilityFilters(Builder $query, string $userId, string $userRole): void
{
    if ($userRole === 'COMPANY_ADMIN') {
        $companyId = ActiveRoleHelper::getActiveCompanyId(); // ✅
        // ...
    }
    
    if ($userRole === 'AGENT') {
        $companyId = ActiveRoleHelper::getActiveCompanyId(); // ✅
        // ...
    }
}
```

**Tareas:**
- [ ] Modificar getUserRole()
- [ ] Modificar applyVisibilityFilters()
- [ ] Actualizar tests de TicketServiceTest
- [ ] Validar con tests funcionales de GET /api/tickets

---

### Prioridad 2: ResponseService

**Archivo:** `app/Features/TicketManagement/Services/ResponseService.php`

```php
// ❌ ANTES (líneas 84-99)
private function determineAuthorType(User $user): AuthorType
{
    if (JWTHelper::hasRoleFromJWT('AGENT')) {
        return AuthorType::AGENT;
    }
    return AuthorType::USER;
}

// ✅ DESPUÉS
private function determineAuthorType(User $user): AuthorType
{
    $activeRoleCode = ActiveRoleHelper::getActiveRoleCode($user);
    
    if ($activeRoleCode === 'AGENT') {
        return AuthorType::AGENT;
    }
    
    return AuthorType::USER;
}
```

**Tareas:**
- [ ] Modificar determineAuthorType()
- [ ] Actualizar ResponseServiceTest
- [ ] Validar con test funcional de POST /api/tickets/responses

---

### Prioridad 3: ArticleService

**Archivo:** `app/Features/ContentManagement/Services/ArticleService.php`

#### Cambio 1: listArticles() - hasRole() → hasActiveRole()
```php
// ❌ ANTES (líneas 382-469)
public function listArticles(User $user, array $filters): LengthAwarePaginator
{
    if ($user->hasRole('PLATFORM_ADMIN')) { // ❌
        // ...
    } elseif ($user->hasRole('COMPANY_ADMIN')) { // ❌
        $adminRole = $user->userRoles()
            ->where('role_code', 'COMPANY_ADMIN')
            ->first();
        $adminCompanyId = $adminRole->company_id; // ❌ Puede tener varios
        // ...
    }
}

// ✅ DESPUÉS
public function listArticles(User $user, array $filters): LengthAwarePaginator
{
    $activeRole = ActiveRoleHelper::getActiveRole($user);
    
    if (!$activeRole) {
        throw new Exception('Usuario no tiene rol activo', 403);
    }
    
    $activeRoleCode = $activeRole->role_code;
    
    if ($activeRoleCode === 'PLATFORM_ADMIN') { // ✅
        // ...
    } elseif ($activeRoleCode === 'COMPANY_ADMIN') { // ✅
        $adminCompanyId = $activeRole->company_id; // ✅ Del rol activo
        // ...
    } elseif ($activeRoleCode === 'AGENT') { // ✅
        $agentCompanyId = $activeRole->company_id; // ✅
        // ...
    } else { // USER
        // ...
    }
}
```

#### Cambio 2: viewArticle() - Similar
```php
// ✅ DESPUÉS
public function viewArticle(?User $user, string $articleId): HelpCenterArticle
{
    if (!$user) {
        throw new Exception('Unauthenticated', 401);
    }

    $article = HelpCenterArticle::with([...])->find($articleId);

    if (!$article) {
        throw new Exception('Article not found', 404);
    }

    $activeRole = ActiveRoleHelper::getActiveRole($user);
    $activeRoleCode = $activeRole?->role_code;

    if ($activeRoleCode === 'PLATFORM_ADMIN') {
        return $article;
    }

    if ($activeRoleCode === 'COMPANY_ADMIN') {
        if ($activeRole->company_id !== $article->company_id) {
            throw new Exception('Article not found', 404);
        }
        return $article;
    }

    if ($activeRoleCode === 'AGENT') {
        if ($activeRole->company_id !== $article->company_id) {
            throw new Exception('Article not found', 404);
        }
        if ($article->status !== 'PUBLISHED') {
            throw new Exception('Article not found', 404);
        }
        return $article;
    }

    // USER: Empresas seguidas + PUBLISHED + incrementa views
    // ... (sin cambios)
}
```

**Tareas:**
- [ ] Modificar listArticles()
- [ ] Modificar viewArticle()
- [ ] Actualizar ArticleServiceTest
- [ ] Validar con tests funcionales

---

### Prioridad 4: AnnouncementController

**Archivo:** `app/Features/ContentManagement/Http/Controllers/AnnouncementController.php`

```php
// ❌ ANTES (líneas 163-258)
public function index(Request $request, VisibilityService $visibilityService): JsonResponse
{
    $user = auth()->user();
    $query = Announcement::query();

    if ($visibilityService->isPlatformAdmin($user)) { // ❌
        // ...
    } elseif ($user->hasRole('COMPANY_ADMIN')) { // ❌
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN'); // ❌
        // ...
    } else {
        // AGENT/USER
    }
}

// ✅ DESPUÉS
public function index(Request $request, VisibilityService $visibilityService): JsonResponse
{
    $user = auth()->user();
    $query = Announcement::query();
    
    $activeRole = ActiveRoleHelper::getActiveRole($user);
    $activeRoleCode = $activeRole?->role_code;

    if ($activeRoleCode === 'PLATFORM_ADMIN') { // ✅
        if (isset($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }
    } elseif ($activeRoleCode === 'COMPANY_ADMIN') { // ✅
        $companyId = $activeRole->company_id; // ✅
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid company context',
            ], 403);
        }
        $query->where('company_id', $companyId);
    } else {
        // AGENT and USER: only PUBLISHED from followed companies
        $followedCompanyIds = \DB::table('business.user_company_followers')
            ->where('user_id', $user->id)
            ->pluck('company_id')
            ->toArray();

        $query->where('status', PublicationStatus::PUBLISHED->value)
            ->whereIn('company_id', $followedCompanyIds);
    }
    
    // ... resto sin cambios
}
```

**Similar para show():**
```php
public function show(string $announcement, VisibilityService $visibilityService): JsonResponse
{
    // ...
    $user = auth()->user();
    $activeRole = ActiveRoleHelper::getActiveRole($user);
    $activeRoleCode = $activeRole?->role_code;

    if ($activeRoleCode === 'PLATFORM_ADMIN') {
        // ...
    }

    if ($activeRoleCode === 'COMPANY_ADMIN') {
        if ($announcement->company_id !== $activeRole->company_id) {
            return response()->json(['message' => 'Insufficient permissions'], 403);
        }
        // ...
    }

    // AGENT/USER
    // ...
}
```

**Tareas:**
- [ ] Modificar index()
- [ ] Modificar show()
- [ ] Actualizar tests
- [ ] Validar funcionalidad

---

## Fase 3: Endpoints Media Prioridad (Día 4)

### 3.1. ActivityLogController
```php
// ✅ DESPUÉS
public function index(Request $request): JsonResponse
{
    $user = JWTHelper::getAuthenticatedUser();
    $activeRoleCode = ActiveRoleHelper::getActiveRoleCode($user);
    
    $isAdmin = in_array($activeRoleCode, ['PLATFORM_ADMIN', 'COMPANY_ADMIN']);
    
    // ... resto de lógica
}
```

### 3.2. UserController
```php
// ✅ DESPUÉS
public function index(Request $request): JsonResponse
{
    $currentUser = JWTHelper::getAuthenticatedUser();
    $activeRoleCode = ActiveRoleHelper::getActiveRoleCode($currentUser);

    $isPlatformAdmin = $activeRoleCode === 'PLATFORM_ADMIN';
    $isCompanyAdmin = $activeRoleCode === 'COMPANY_ADMIN';
    $isAgent = $activeRoleCode === 'AGENT';

    if (!$isPlatformAdmin && !$isCompanyAdmin && !$isAgent) {
        return response()->json([...], 403);
    }
    
    // ... resto
}

protected function applyFilters($query, Request $request, User $currentUser)
{
    $activeRoleCode = ActiveRoleHelper::getActiveRoleCode($currentUser);
    $isPlatformAdmin = $activeRoleCode === 'PLATFORM_ADMIN';
    $isCompanyAdmin = $activeRoleCode === 'COMPANY_ADMIN';
    $isAgent = $activeRoleCode === 'AGENT';

    if (($isCompanyAdmin || $isAgent) && !$isPlatformAdmin) {
        $activeCompanyId = ActiveRoleHelper::getActiveCompanyId($currentUser);
        
        if ($activeCompanyId) {
            $query->whereHas('userRoles', function ($q) use ($activeCompanyId) {
                $q->where('is_active', true)
                  ->where('company_id', $activeCompanyId);
            });
        }
    }
    
    // ... resto de filtros
}
```

### 3.3. CompanyController
```php
// ✅ DESPUÉS
public function index(ListCompaniesRequest $request)
{
    // ...
    $user = JWTHelper::getAuthenticatedUser();
    $activeRoleCode = ActiveRoleHelper::getActiveRoleCode($user);
    
    if ($activeRoleCode === 'COMPANY_ADMIN') {
        $activeCompanyId = ActiveRoleHelper::getActiveCompanyId($user);
        $query->where('id', $activeCompanyId);
    }
    
    // ... resto
}
```

**Tareas:**
- [ ] Modificar ActivityLogController::index y entityActivity
- [ ] Modificar UserController::index y applyFilters
- [ ] Modificar CompanyController::index
- [ ] Actualizar tests respectivos

---

## Fase 4: Testing y Validación (Día 5)

### 4.1. Tests Unitarios
- [ ] ActiveRoleHelperTest (crear)
- [ ] ValidateActiveRoleMiddlewareTest (crear)
- [ ] TicketServiceTest (actualizar)
- [ ] ResponseServiceTest (actualizar)
- [ ] ArticleServiceTest (actualizar)

### 4.2. Tests Funcionales
- [ ] GET /api/tickets con different active roles
- [ ] POST /api/tickets/responses como USER vs AGENT
- [ ] GET /api/announcements con different active roles
- [ ] GET /api/help-center/articles con different active roles
- [ ] POST /api/users/me/active-role (switch role)

### 4.3. Tests de Integración
- [ ] Usuario AGENT+USER cambia rol y ve datos diferentes
- [ ] Usuario COMPANY_ADMIN+USER cambia rol y permisos cambian
- [ ] Usuario sin active_role_id recibe rol por defecto

---

## Fase 5: Documentación y Deploy (Día 6)

### 5.1. Documentación
- [ ] README.md - Sección "Sistema de Roles Activos"
- [ ] OpenAPI/Swagger - Endpoints /api/users/me/active-role
- [ ] CHANGELOG.md - Versión con breaking changes
- [ ] Guía de migración para usuarios existentes

### 5.2. Script de Migración de Datos
```php
// database/seeders/SetDefaultActiveRolesSeeder.php
class SetDefaultActiveRolesSeeder extends Seeder
{
    public function run()
    {
        $users = User::whereNull('active_role_id')->get();
        
        foreach ($users as $user) {
            $firstActiveRole = $user->userRoles()
                ->where('is_active', true)
                ->orderByRaw("
                    CASE role_code
                        WHEN 'PLATFORM_ADMIN' THEN 1
                        WHEN 'COMPANY_ADMIN' THEN 2
                        WHEN 'AGENT' THEN 3
                        WHEN 'USER' THEN 4
                        ELSE 5
                    END
                ")
                ->first();
            
            if ($firstActiveRole) {
                $user->update(['active_role_id' => $firstActiveRole->id]);
            }
        }
    }
}
```

**Tareas:**
- [ ] Crear seeder
- [ ] Ejecutar en dev, stage, prod
- [ ] Validar que todos los usuarios tengan active_role_id

---

## Checklist Final

### Backend
- [ ] Migration ejecutada en todos los entornos
- [ ] ActiveRoleHelper creado y testeado
- [ ] ValidateActiveRole middleware registrado
- [ ] ActiveRoleController con endpoints de gestión
- [ ] 9 endpoints críticos actualizados
- [ ] 4 endpoints media prioridad actualizados
- [ ] Todos los tests pasando

### Frontend (si aplica)
- [ ] UI para seleccionar rol activo (dropdown en navbar)
- [ ] Llamada a GET /api/users/me/available-roles
- [ ] Llamada a POST /api/users/me/active-role
- [ ] Actualización de UI al cambiar rol (refetch data)
- [ ] Indicador visual de rol activo actual

### Deployment
- [ ] Scripts de migración documentados
- [ ] Rollback plan definido
- [ ] Monitoreo de errores configurado
- [ ] Comunicación a usuarios sobre nueva feature

---

## Métricas de Éxito

1. **Tests:** 100% de tests pasando (unitarios + funcionales)
2. **Coverage:** Mantener/mejorar cobertura de código
3. **Performance:** No degradación en tiempos de respuesta
4. **UX:** Usuarios pueden cambiar de rol sin fricción
5. **Data Integrity:** Filtros correctos según active_role

---

## Riesgos y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Usuarios sin active_role_id rompen aplicación | Alta | Alto | Middleware auto-asigna rol por defecto |
| Performance degradada por queries extra | Media | Medio | Eager loading de activeRole en auth |
| Tests legacy se rompen | Alta | Medio | Actualizar tests progresivamente |
| Rollback complejo | Baja | Alto | Mantener código legacy comentado por 1 sprint |

---

## Timeline Estimado

- **Día 1:** Fase 1 completa (infraestructura)
- **Días 2-3:** Fase 2 completa (9 endpoints críticos)
- **Día 4:** Fase 3 completa (4 endpoints media prioridad)
- **Día 5:** Fase 4 completa (testing)
- **Día 6:** Fase 5 completa (documentación + deploy)

**Total: 6 días de desarrollo + 2 días de QA/UAT = 8 días**

---

## Comandos Útiles

```bash
# Crear migration
php artisan make:migration add_active_role_id_to_users

# Ejecutar migrations
php artisan migrate

# Ejecutar seeder
php artisan db:seed --class=SetDefaultActiveRolesSeeder

# Ejecutar tests
php artisan test --filter=ActiveRole
php artisan test --filter=TicketService
php artisan test --filter=ArticleService

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```
