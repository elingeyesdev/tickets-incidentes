# Análisis Detallado de Endpoints Multi-Rol - Código Fuente

## 1. TicketService::list - GET /api/tickets

**Archivo:** `app/Features/TicketManagement/Services/TicketService.php`

### Código Problemático (Líneas 76-90):
```php
public function list(array $filters, User $user): LengthAwarePaginator
{
    // Determinar rol del usuario desde JWT
    $userRole = $this->getUserRole($user);  // ❌ PROBLEMA: Rol con prioridad fija

    $query = Ticket::query();

    // Cargar relaciones para TicketListResource
    $query->with(['creator.profile', 'ownerAgent.profile', 'category', 'area']);
    $query->withCount(['responses', 'attachments']);

    // Aplicar filtros de visibilidad según rol
    $this->applyVisibilityFilters($query, $user->id, $userRole);  // ❌ PROBLEMA
    
    // ... resto del método
}
```

### getUserRole() - Líneas 106-120:
```php
private function getUserRole(User $user): string
{
    if (JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN')) {  // ❌ Prioridad 1
        return 'PLATFORM_ADMIN';
    }
    if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {  // ❌ Prioridad 2
        return 'COMPANY_ADMIN';
    }
    if (JWTHelper::hasRoleFromJWT('AGENT')) {  // ❌ Prioridad 3
        return 'AGENT';
    }
    if (JWTHelper::hasRoleFromJWT('USER')) {  // ❌ Prioridad 4
        return 'USER';
    }
    return 'USER'; // Fallback
}
```

**PROBLEMA:** Usuario con roles AGENT+USER SIEMPRE retorna 'AGENT', nunca puede ver solo sus tickets.

### applyVisibilityFilters() - Líneas 216-246:
```php
private function applyVisibilityFilters(Builder $query, string $userId, string $userRole): void
{
    // Si es PLATFORM_ADMIN: ve TODO (no aplicar filtros)
    if ($userRole === 'PLATFORM_ADMIN') {
        return;
    }

    // Si es COMPANY_ADMIN: ve todos los tickets de su empresa
    if ($userRole === 'COMPANY_ADMIN') {
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');  // ❌ PROBLEMA
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return;
    }

    // Si es AGENT: ve todos los tickets de su empresa
    if ($userRole === 'AGENT') {
        $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');  // ❌ PROBLEMA
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return;
    }

    // Si es USER: solo ve sus propios tickets
    if ($userRole === 'USER') {
        $query->where('created_by_user_id', $userId);
        return;
    }
}
```

**IMPACTO:** Usuario AGENT+USER con active_role='USER' debería ver solo tickets propios, pero ve todos de la empresa.

---

## 2. ArticleService::listArticles - GET /api/help-center/articles

**Archivo:** `app/Features/ContentManagement/Services/ArticleService.php`

### Código Problemático (Líneas 382-469):
```php
public function listArticles(User $user, array $filters): LengthAwarePaginator
{
    $query = HelpCenterArticle::query();
    $requestedCompanyId = $filters['company_id'] ?? null;

    // PLATFORM_ADMIN: Ve TODO
    if ($user->hasRole('PLATFORM_ADMIN')) {  // ❌ PROBLEMA: hasRole() no verifica activo
        if ($requestedCompanyId) {
            $query->where('company_id', $requestedCompanyId);
        }
    } 
    
    // COMPANY_ADMIN: Solo su empresa
    elseif ($user->hasRole('COMPANY_ADMIN')) {  // ❌ PROBLEMA
        $adminRole = $user->userRoles()
            ->where('role_code', 'COMPANY_ADMIN')
            ->first();

        if (!$adminRole || !$adminRole->company_id) {
            throw new Exception('Usuario no tiene empresa asignada', 500);
        }

        $adminCompanyId = $adminRole->company_id;

        if ($requestedCompanyId && $requestedCompanyId !== $adminCompanyId) {
            throw new Exception('Forbidden: No tienes permiso...', 403);
        }

        $query->where('company_id', $adminCompanyId);

        // ❌ COMPANY_ADMIN ve DRAFT + PUBLISHED (no filtra por status)
        if (isset($filters['status'])) {
            $status = strtoupper($filters['status']);
            $query->where('status', $status);
        }
    } 
    
    // AGENT: Solo su empresa + PUBLISHED
    elseif ($user->hasRole('AGENT')) {  // ❌ PROBLEMA
        $agentRole = $user->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        if (!$agentRole || !$agentRole->company_id) {
            throw new Exception('Usuario no tiene empresa asignada', 500);
        }

        $agentCompanyId = $agentRole->company_id;

        if ($requestedCompanyId && $requestedCompanyId !== $agentCompanyId) {
            throw new Exception('Forbidden...', 403);
        }

        $query->where('company_id', $agentCompanyId);
        $query->where('status', 'PUBLISHED');  // ✅ Solo PUBLISHED
    } 
    
    // USER: Empresas seguidas + PUBLISHED
    else {
        $followedCompanyIds = $user->followedCompanies()
            ->pluck('business.companies.id')
            ->toArray();

        if ($requestedCompanyId) {
            if (!in_array($requestedCompanyId, $followedCompanyIds)) {
                throw new Exception('Forbidden...', 403);
            }
            $query->where('company_id', $requestedCompanyId);
        } else {
            if (!empty($followedCompanyIds)) {
                $query->whereIn('company_id', $followedCompanyIds);
            } else {
                $query->where('company_id', null);  // Vacío
            }
        }

        $query->where('status', 'PUBLISHED');  // ✅ Solo PUBLISHED
    }
    
    // ... resto de filtros
}
```

**IMPACTO:** Usuario COMPANY_ADMIN+USER con active_role='USER' ve artículos DRAFT de su empresa cuando debería ver solo PUBLISHED.

---

## 3. ArticleService::viewArticle - GET /api/help-center/articles/{id}

**Archivo:** `app/Features/ContentManagement/Services/ArticleService.php`

### Código Problemático (Líneas 293-369):
```php
public function viewArticle(?User $user, string $articleId): HelpCenterArticle
{
    if (!$user) {
        throw new Exception('Unauthenticated', 401);
    }

    $article = HelpCenterArticle::with(['company.industry', 'author', 'category'])
        ->find($articleId);

    if (!$article) {
        throw new Exception('Article not found', 404);
    }

    // PLATFORM_ADMIN: Ve todo
    if ($user->hasRole('PLATFORM_ADMIN')) {  // ❌ PROBLEMA
        return $article;  // ✅ No incrementa views
    }

    // COMPANY_ADMIN: Solo su empresa
    if ($user->hasRole('COMPANY_ADMIN')) {  // ❌ PROBLEMA
        $adminRole = $user->userRoles()
            ->where('role_code', 'COMPANY_ADMIN')
            ->first();

        if (!$adminRole || $adminRole->company_id !== $article->company_id) {
            throw new Exception('Article not found', 404);
        }

        return $article;  // ✅ No incrementa views (admin)
    }

    // AGENT: Solo su empresa + PUBLISHED
    if ($user->hasRole('AGENT')) {  // ❌ PROBLEMA
        $agentRole = $user->userRoles()
            ->where('role_code', 'AGENT')
            ->first();

        if (!$agentRole || $agentRole->company_id !== $article->company_id) {
            throw new Exception('Article not found', 404);
        }

        if ($article->status !== 'PUBLISHED') {
            throw new Exception('Article not found', 404);
        }

        return $article;  // ✅ No incrementa views (staff)
    }

    // USER: Empresas seguidas + PUBLISHED + incrementa views
    $isFollowing = $user->followedCompanies()
        ->where('business.companies.id', $article->company_id)
        ->exists();

    if (!$isFollowing) {
        throw new Exception('Article not found', 404);
    }

    if ($article->status !== 'PUBLISHED') {
        throw new Exception('Article not found', 404);
    }

    // ✅ Incrementar views SOLO para USER
    $article->increment('views_count');

    return $article;
}
```

**IMPACTO:** Usuario COMPANY_ADMIN viendo artículo como "preview de usuario final" NO incrementa views_count, dificultando testeo.

---

## 4. AnnouncementController::index - GET /api/announcements

**Archivo:** `app/Features/ContentManagement/Http/Controllers/AnnouncementController.php`

### Código Problemático (Líneas 163-258):
```php
public function index(Request $request, VisibilityService $visibilityService): JsonResponse
{
    // ... validación

    $user = auth()->user();
    $query = Announcement::query();

    // PLATFORM_ADMIN sees EVERYTHING
    if ($visibilityService->isPlatformAdmin($user)) {  // ❌ PROBLEMA
        if (isset($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }
    } 
    
    // COMPANY_ADMIN sees only their company
    elseif ($user->hasRole('COMPANY_ADMIN')) {  // ❌ PROBLEMA
        $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');  // ❌ PROBLEMA
        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid company context',
            ], 403);
        }
        $query->where('company_id', $companyId);
        // ❌ Ve TODOS los estados (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED)
    } 
    
    // AGENT and USER: only PUBLISHED from followed companies
    else {
        $followedCompanyIds = \DB::table('business.user_company_followers')
            ->where('user_id', $user->id)
            ->pluck('company_id')
            ->toArray();

        $query->where('status', PublicationStatus::PUBLISHED->value)
            ->whereIn('company_id', $followedCompanyIds);
    }

    // ... filtros adicionales, paginación
}
```

**IMPACTO:** Usuario COMPANY_ADMIN+USER con active_role='USER' ve anuncios en estado DRAFT/SCHEDULED cuando solo debería ver PUBLISHED.

---

## 5. ResponseService::create - POST /api/tickets/responses

**Archivo:** `app/Features/TicketManagement/Services/ResponseService.php`

### Código Problemático (Líneas 84-99):
```php
public function create(Ticket $ticket, array $data, User $user): TicketResponse
{
    // Determinar tipo de autor
    $authorType = $this->determineAuthorType($user);  // ❌ PROBLEMA

    $response = TicketResponse::create([
        'ticket_id' => $ticket->id,
        'author_user_id' => $user->id,
        'author_type' => $authorType,
        'content' => $data['content'],
        'is_internal' => $data['is_internal'] ?? false,
    ]);

    return $response;
}

private function determineAuthorType(User $user): AuthorType
{
    // Si tiene rol AGENT, es autor tipo AGENT
    if (JWTHelper::hasRoleFromJWT('AGENT')) {  // ❌ PROBLEMA
        return AuthorType::AGENT;
    }

    // De lo contrario, es USER
    return AuthorType::USER;
}
```

**IMPACTO CRÍTICO:** Usuario AGENT+USER que crea un ticket propio (con active_role='USER') y luego responde, la respuesta se marca como AGENT en lugar de USER, rompiendo la lógica de negocio.

---

## 6. ActivityLogController::index - GET /api/activity-logs

**Archivo:** `app/Features/AuditLog/Http/Controllers/ActivityLogController.php`

### Código Problemático (Líneas 110-130):
```php
public function index(Request $request): JsonResponse
{
    $user = JWTHelper::getAuthenticatedUser();
    $isAdmin = JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN') 
        || JWTHelper::hasRoleFromJWT('COMPANY_ADMIN');  // ❌ PROBLEMA

    $targetUserId = $request->query('user_id');

    // Solo admins pueden ver logs de otros usuarios
    if ($targetUserId && $targetUserId !== $user->id && !$isAdmin) {  // ❌ PROBLEMA
        return response()->json([
            'message' => 'No tienes permiso para ver la actividad de otros usuarios',
        ], 403);
    }

    // Si no es admin y no especificó user_id, mostrar solo sus logs
    if (!$isAdmin && !$targetUserId) {  // ❌ PROBLEMA
        $targetUserId = $user->id;
    }

    $query = \App\Features\AuditLog\Models\ActivityLog::query()
        ->orderBy('created_at', 'desc');

    if ($targetUserId) {
        $query->forUser($targetUserId);
    }
    
    // ... resto de filtros
}
```

**IMPACTO:** Usuario COMPANY_ADMIN con active_role='USER' puede ver logs de otros usuarios cuando debería ver solo los suyos.

---

## 7. UserController::index - GET /api/users

**Archivo:** `app/Features/UserManagement/Http/Controllers/UserController.php`

### Código Problemático (Líneas 186-217):
```php
public function index(Request $request): JsonResponse
{
    $currentUser = JWTHelper::getAuthenticatedUser();

    // Authorization check
    $isPlatformAdmin = $currentUser->hasRole('PLATFORM_ADMIN');  // ❌ PROBLEMA
    $isCompanyAdmin = $currentUser->hasRole('COMPANY_ADMIN');  // ❌ PROBLEMA
    $isAgent = $currentUser->hasRole('AGENT');  // ❌ PROBLEMA

    if (!$isPlatformAdmin && !$isCompanyAdmin && !$isAgent) {
        return response()->json([
            'code' => 'INSUFFICIENT_PERMISSIONS',
            'message' => 'You do not have permission to list users',
        ], 403);
    }

    $query = User::with([
        'profile',
        'userRoles' => fn($q) => $q->where('is_active', true)
            ->with(['role', 'company'])
    ]);

    // Apply filters
    $query = $this->applyFilters($query, $request, $currentUser);  // ❌ PROBLEMA

    // ... paginación
}
```

### applyFilters() - Líneas 588-609:
```php
protected function applyFilters($query, Request $request, User $currentUser)
{
    $isPlatformAdmin = $currentUser->hasRole('PLATFORM_ADMIN');  // ❌ PROBLEMA
    $isCompanyAdmin = $currentUser->hasRole('COMPANY_ADMIN');  // ❌ PROBLEMA
    $isAgent = $currentUser->hasRole('AGENT');  // ❌ PROBLEMA

    if (($isCompanyAdmin || $isAgent) && !$isPlatformAdmin) {  // ❌ PROBLEMA
        // Get company IDs for current user
        $companyIds = $currentUser->userRoles()
            ->where('is_active', true)
            ->whereNotNull('company_id')
            ->pluck('company_id')
            ->toArray();

        if (!empty($companyIds)) {
            $query->whereHas('userRoles', function ($q) use ($companyIds) {
                $q->where('is_active', true)
                  ->whereIn('company_id', $companyIds);
            });
        }
    }
    
    // ... resto de filtros
}
```

**IMPACTO:** Usuario COMPANY_ADMIN puede listar usuarios aunque tenga active_role='USER'.

---

## Patrón General Detectado

### Métodos problemáticos usados:
1. `JWTHelper::hasRoleFromJWT($roleCode)` - ✅ Verifica si usuario TIENE el rol (no si está activo)
2. `User::hasRole($roleCode)` - ✅ Similar, verifica posesión, no estado activo
3. `JWTHelper::getCompanyIdFromJWT($roleCode)` - ❌ Busca company_id del primer userRole con ese código

### Solución requerida:
```php
// ❌ ANTES (incorrecto)
if (JWTHelper::hasRoleFromJWT('AGENT')) {
    $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
    // ... lógica
}

// ✅ DESPUÉS (correcto)
$activeRole = ActiveRoleHelper::getActiveRole($user);
if ($activeRole->role_code === 'AGENT') {
    $companyId = $activeRole->company_id;
    // ... lógica
}
```

### Helpers a crear:
```php
class ActiveRoleHelper
{
    public static function getActiveRole(User $user): ?UserRole
    {
        if (!$user->active_role_id) {
            return null;
        }
        
        return $user->userRoles()
            ->where('id', $user->active_role_id)
            ->where('is_active', true)
            ->with('role')
            ->first();
    }
    
    public static function getActiveRoleCode(User $user): ?string
    {
        return self::getActiveRole($user)?->role_code;
    }
    
    public static function getActiveCompanyId(User $user): ?string
    {
        return self::getActiveRole($user)?->company_id;
    }
    
    public static function hasActiveRole(User $user, string $roleCode): bool
    {
        $activeRole = self::getActiveRole($user);
        return $activeRole && $activeRole->role_code === $roleCode;
    }
}
```

---

## Resumen de Cambios por Archivo

### Services (5 archivos):
1. `TicketService.php` - Modificar `getUserRole()` y `applyVisibilityFilters()`
2. `ArticleService.php` - Modificar `listArticles()` y `viewArticle()`
3. `ResponseService.php` - Modificar `determineAuthorType()`
4. `AnnouncementService.php` - (Si tiene lógica de visibilidad interna)
5. `VisibilityService.php` - Actualizar métodos de verificación de roles

### Controllers (4 archivos):
1. `AnnouncementController.php` - Modificar `index()` y `show()`
2. `ActivityLogController.php` - Modificar `index()` y `entityActivity()`
3. `UserController.php` - Modificar `index()` y `applyFilters()`
4. `CompanyController.php` - Modificar `index()`

### Helpers nuevos (1 archivo):
1. `ActiveRoleHelper.php` - Crear con métodos `getActiveRole()`, `getActiveRoleCode()`, `getActiveCompanyId()`, `hasActiveRole()`

### Middleware nuevo (1 archivo):
1. `ValidateActiveRole.php` - Validar que `active_role_id` sea válido y activo

### Migration (1 archivo):
1. `add_active_role_id_to_users.php` - Agregar columna `active_role_id` a `auth.users`

**Total: 12 archivos a modificar/crear**
