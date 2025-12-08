# 🗺️ MAPEO COMPLETO: Endpoints que Dependen del Rol del Usuario

**Fecha:** 2025-12-07  
**Branch:** `feature/active-role-system`  
**Análisis:** Exhaustivo de 100% de controllers y services

---

## 📊 Resumen Ejecutivo

| Categoría | Cantidad | Acción |
|-----------|----------|--------|
| **Críticos** (Requieren modificación urgente) | 9 | ✅ Modificar |
| **Media Prioridad** (Requieren modificación) | 4 | ✅ Modificar |
| **Excluidos** (No requieren cambios) | 12 | ❌ No tocar |
| **Total Endpoints Analizados** | 25 | - |

---

## 🔴 ENDPOINTS CRÍTICOS (Prioridad 1)

Estos endpoints **FILTRAN datos diferentes** según el rol y **DEBEN modificarse** para soportar multi-rol.

### 1️⃣ GET `/api/tickets` - Listar Tickets
**Controller:** `TicketController::index`  
**Service:** `TicketService::list`  
**Archivos:** `TicketService.php`

**Código Problemático:**
```php
// Líneas 108-117: getUserRole() - Prioridad fija
private function getUserRole(User $user): string
{
    if (JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN')) {
        return 'PLATFORM_ADMIN';  // ← SIEMPRE prioriza este
    }
    if (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) {
        return 'COMPANY_ADMIN';   // ← Aunque también sea AGENT
    }
    if (JWTHelper::hasRoleFromJWT('AGENT')) {
        return 'AGENT';
    }
    return 'USER';
}

// Líneas 225-234: applyVisibilityFilters()
if ($userRole === 'COMPANY_ADMIN') {
    $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
    // ⚠️ ¿Qué pasa si el usuario es COMPANY_ADMIN en 2 empresas?
}
```

**Filtrado Actual:**
- PLATFORM_ADMIN → Ve TODO
- COMPANY_ADMIN → Ve todos los tickets de SU empresa
- AGENT → Ve todos los tickets de SU empresa  
- USER → Ve solo SUS propios tickets

**Problema Multi-Rol:**
- Usuario con AGENT + USER **siempre** ve todos los tickets de la empresa (como AGENT)
- NO puede ver solo los suyos como USER
- Usuario con COMPANY_ADMIN en 2 empresas ve solo tickets de la primera

**Solución Requerida:**
```php
// Usar rol ACTIVO del usuario
$activeRole = JWTHelper::getActiveRoleCode();
$companyId = JWTHelper::getActiveCompanyId();

if ($activeRole === 'COMPANY_ADMIN' || $activeRole === 'AGENT') {
    $query->where('company_id', $companyId);
} elseif ($activeRole === 'USER') {
    $query->where('created_by_user_id', $userId);
}
```

**Company ID Required:** ✅ Sí (para AGENT, COMPANY_ADMIN)

---

### 2️⃣ POST `/api/tickets/{ticket}/responses` - Crear Respuesta
**Controller:** `TicketResponseController::store`  
**Service:** `ResponseService::create`  
**Archivos:** `ResponseService.php`

**Código Problemático:**
```php
// Líneas 94-99: determineAuthorType()
private function determineAuthorType(User $user, Ticket $ticket): string
{
    // ⚠️ Asume que si TIENE rol AGENT, está actuando como AGENT
    if (JWTHelper::hasRoleFromJWT('AGENT') && 
        $user->hasRoleInCompany('AGENT', $ticket->company_id)) {
        return 'agent';
    }
    return 'user';
}
```

**Filtrado Actual:**
- Si el usuario tiene rol AGENT → La respuesta se marca como "de agente"
- Si NO tiene rol AGENT → La respuesta se marca como "de usuario"

**Problema Multi-Rol:**
- Usuario AGENT + USER que crea un ticket propio → Sus respuestas siempre son "de agente"
- Confunde la auditoría y analytics (no se sabe si respondió como agente o como cliente)

**Solución Requerida:**
```php
private function determineAuthorType(User $user, Ticket $ticket): string
{
    $activeRole = JWTHelper::getActiveRoleCode();
    
    if ($activeRole === 'AGENT' && 
        $user->hasRoleInCompany('AGENT', $ticket->company_id)) {
        return 'agent';
    }
    return 'user';
}
```

**Company ID Required:** ❌ No (pero verifica el rol activo)

---

### 3️⃣ GET `/api/help-center/articles` - Listar Artículos
**Controller:** `ArticleController::index`  
**Service:** `ArticleService::listArticles`  
**Archivos:** `ArticleService.php`

**Código Problemático:**
```php
// Líneas 382-469: listArticles() - Cadena if/elseif
if ($user->hasRole('PLATFORM_ADMIN')) {
    // Ve TODO: drafts, published, archived
} elseif ($user->hasRole('COMPANY_ADMIN')) {
    $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
    // Ve todos los estados de su empresa
    $query->where('company_id', $companyId);
} elseif ($user->hasRole('AGENT')) {
    $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
    // Ve solo PUBLISHED de su empresa
    $query->where('company_id', $companyId)
          ->where('status', PublicationStatus::PUBLISHED);
} else {
    // USER: Ve solo PUBLISHED de empresas seguidas
    $query->whereIn('company_id', $user->followedCompanies->pluck('id'))
          ->where('status', PublicationStatus::PUBLISHED);
}
```

**Filtrado Actual:**
- PLATFORM_ADMIN → Todos los artículos, todos los estados
- COMPANY_ADMIN → Todos los estados de SU empresa
- AGENT → Solo PUBLISHED de SU empresa
- USER → Solo PUBLISHED de empresas seguidas

**Problema Multi-Rol:**
- Usuario COMPANY_ADMIN + USER ve DRAFTS cuando quizás solo quiere ver publicados
- Usuario AGENT en Empresa A + COMPANY_ADMIN en Empresa B siempre ve artículos de Empresa B

**Solución Requerida:**
```php
$activeRole = JWTHelper::getActiveRoleCode();
$companyId = JWTHelper::getActiveCompanyId();

if ($activeRole === 'PLATFORM_ADMIN') {
    // Sin filtros
} elseif ($activeRole === 'COMPANY_ADMIN') {
    $query->where('company_id', $companyId);
} elseif ($activeRole === 'AGENT') {
    $query->where('company_id', $companyId)
          ->where('status', PublicationStatus::PUBLISHED);
} else {
    // USER
    $query->whereIn('company_id', $user->followedCompanies->pluck('id'))
          ->where('status', PublicationStatus::PUBLISHED);
}
```

**Company ID Required:** ✅ Sí (para AGENT, COMPANY_ADMIN)

---

### 4️⃣ GET `/api/help-center/articles/{article}` - Ver Artículo Individual
**Controller:** `ArticleController::show`  
**Service:** `ArticleService::viewArticle`  
**Archivos:** `ArticleService.php`

**Código Problemático:**
```php
// Líneas 289-369: viewArticle() - Similar a listArticles()
if ($user->hasRole('PLATFORM_ADMIN')) {
    // Puede ver cualquier artículo
    return $article;
} elseif ($user->hasRole('COMPANY_ADMIN')) {
    if ($article->company_id !== JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN')) {
        throw new ArticleNotFoundException();
    }
    // Puede ver cualquier estado de su empresa
} // ... etc
```

**Filtrado Actual:**
Similar a listArticles pero para un artículo específico.

**Problema Multi-Rol:**
Mismo problema que listArticles.

**Company ID Required:** ✅ Sí

---

### 5️⃣ GET `/api/announcements` - Listar Anuncios
**Controller:** `AnnouncementController::index`  
**Service:** `AnnouncementController::index` (lógica inline)
**Archivos:** `AnnouncementController.php`

**Código Problemático:**
```php
// Líneas 163-258
if ($visibilityService->isPlatformAdmin($user)) {
    // PLATFORM_ADMIN ve TODO
    if (isset($validated['company_id'])) {
        $query->where('company_id', $validated['company_id']);
    }
} elseif ($user->hasRole('COMPANY_ADMIN')) {
    // ⚠️ Usa hasRole() sin verificar que es el rol ACTIVO
    $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
    if (!$companyId) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $query->where('company_id', $companyId);
    // Ve todos los estados (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED)
} else {
    // AGENT/USER: Solo PUBLISHED de empresas seguidas
    $query->whereIn('company_id', $user->followedCompanies->pluck('id'))
          ->where('status', PublicationStatus::PUBLISHED);
}
```

**Filtrado Actual:**
- PLATFORM_ADMIN → Todos los anuncios, opcionalmente filtrados por empresa
- COMPANY_ADMIN → Todos los estados de SU empresa
- AGENT/USER → Solo PUBLISHED de empresas seguidas

**Problema Multi-Rol:**
- Usuario COMPANY_ADMIN + USER ve DRAFTS de su empresa cuando quizás solo quiere ver PUBLISHED como usuario
- Usuario con múltiples COMPANY_ADMIN ve solo anuncios de la primera empresa

**Solución Requerida:**
```php
$activeRole = JWTHelper::getActiveRoleCode();

if ($activeRole === 'PLATFORM_ADMIN') {
    // Sin filtros o filtrado opcional
} elseif ($activeRole === 'COMPANY_ADMIN') {
    $companyId = JWTHelper::getActiveCompanyId();
    $query->where('company_id', $companyId);
} else {
    // USER/AGENT actuando como user
    $query->whereIn('company_id', $user->followedCompanies->pluck('id'))
          ->where('status', PublicationStatus::PUBLISHED);
}
```

**Company ID Required:** ✅ Sí (para COMPANY_ADMIN)

---

### 6️⃣ GET `/api/announcements/{announcement}` - Ver Anuncio Individual
**Controller:** `AnnouncementController::show`  
**Archivos:** `AnnouncementController.php`

**Código Problemático:**
```php
// Líneas 340-400: Similar a index pero para un anuncio
if ($visibilityService->isPlatformAdmin($user)) {
    return response()->json(['data' => new AnnouncementResource($announcement)]);
} elseif ($user->hasRole('COMPANY_ADMIN')) {
    if ($announcement->company_id !== JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN')) {
        return response()->json(['message' => 'Forbidden'], 403);
    }
    // Puede ver cualquier estado
} // ... etc
```

**Filtrado Actual:**
Similar a index, valida acceso según rol.

**Problema Multi-Rol:**
Mismo problema que index.

**Company ID Required:** ✅ Sí

---

### 7️⃣ GET `/api/activity-logs` - Listar Logs de Actividad
**Controller:** `ActivityLogController::index`  
**Archivos:** `ActivityLogController.php`

**Código Problemático:**
```php
// Líneas 97-130
$isAdmin = JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN') || 
           JWTHelper::hasRoleFromJWT('COMPANY_ADMIN');

if ($isAdmin) {
    // Admins pueden ver logs de otros usuarios
    // Aplicar filtros de query params
} else {
    // Usuarios normales solo ven sus propios logs
    $query->where('user_id', $user->id);
}
```

**Filtrado Actual:**
- PLATFORM_ADMIN/COMPANY_ADMIN → Pueden ver logs de todos (filtrados opcionalmente)
- USER/AGENT → Solo ven sus propios logs

**Problema Multi-Rol:**
- Usuario COMPANY_ADMIN + USER siempre ve logs de todos cuando quizás solo quiere ver los suyos
- No distingue entre PLATFORM_ADMIN (global) y COMPANY_ADMIN (empresa)

**Solución Requerida:**
```php
$activeRole = JWTHelper::getActiveRoleCode();
$companyId = JWTHelper::getActiveCompanyId();

if ($activeRole === 'PLATFORM_ADMIN') {
    // Ve todos los logs sin filtro de empresa
} elseif ($activeRole === 'COMPANY_ADMIN') {
    // Ve logs de usuarios de su empresa
    $query->whereHas('user.userRoles', function($q) use ($companyId) {
        $q->where('company_id', $companyId);
    });
} else {
    // USER/AGENT: solo sus propios logs
    $query->where('user_id', $user->id);
}
```

**Company ID Required:** ✅ Sí (para COMPANY_ADMIN)

---

### 8️⃣ GET `/api/activity-logs/entity/{entityType}/{entityId}` - Logs de Entidad
**Controller:** `ActivityLogController::entityActivity`  
**Archivos:** `ActivityLogController.php`

**Código Problemático:**
```php
// Líneas 252-280: Similar a index()
$isAdmin = JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN') || 
           JWTHelper::hasRoleFromJWT('COMPANY_ADMIN');

if (!$isAdmin) {
    // Verificar que el usuario tiene acceso a la entidad
    // ...
}
```

**Filtrado Actual:**
Similar a index, pero filtra por entidad específica.

**Problema Multi-Rol:**
Mismo que index.

**Company ID Required:** ✅ Sí (para COMPANY_ADMIN)

---

### 9️⃣ GET `/api/users` - Listar Usuarios
**Controller:** `UserController::index`  
**Archivos:** `UserController.php`

**Código Problemático:**
```php
// Líneas 191-217: Filtrado por rol
if ($user->hasRole('PLATFORM_ADMIN')) {
    // Ve TODOS los usuarios de toda la plataforma
    $query = User::query();
} elseif ($user->hasRole('COMPANY_ADMIN')) {
    // Ve usuarios de su empresa
    $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
    $query = User::whereHas('userRoles', function($q) use ($companyId) {
        $q->where('company_id', $companyId);
    });
} elseif ($user->hasRole('AGENT')) {
    // Ve usuarios de su empresa (clientes que han creado tickets)
    $companyId = JWTHelper::getCompanyIdFromJWT('AGENT');
    // ...
}
```

**Filtrado Actual:**
- PLATFORM_ADMIN → Todos los usuarios
- COMPANY_ADMIN → Usuarios de su empresa
- AGENT → Usuarios de su empresa (clientes)

**Problema Multi-Rol:**
- Usuario COMPANY_ADMIN en 2 empresas ve solo usuarios de la primera
- Usuario COMPANY_ADMIN + AGENT siempre filtra como COMPANY_ADMIN

**Solución Requerida:**
```php
$activeRole = JWTHelper::getActiveRoleCode();
$companyId = JWTHelper::getActiveCompanyId();

if ($activeRole === 'PLATFORM_ADMIN') {
    $query = User::query();
} elseif ($activeRole === 'COMPANY_ADMIN' || $activeRole === 'AGENT') {
    $query = User::whereHas('userRoles', function($q) use ($companyId) {
        $q->where('company_id', $companyId);
    });
}
```

**Company ID Required:** ✅ Sí (para AGENT, COMPANY_ADMIN)

---

## 🟡 ENDPOINTS MEDIA PRIORIDAD (Prioridad 2)

Estos endpoints también filtran por rol, pero tienen **menor impacto** en la experiencia del usuario.

### 1️⃣ GET `/api/analytics/company-dashboard`
**Controller:** `AnalyticsController::dashboard`  
**Problema:** Usa `$user->activeRoles()->where('role_code', 'COMPANY_ADMIN')->value('company_id')`  
**Impacto:** Dashboard de empresa incorrecta si tiene múltiples COMPANY_ADMIN  
**Company ID Required:** ✅ Sí

---

### 2️⃣ GET `/api/analytics/agent-dashboard`
**Controller:** `AnalyticsController::agentDashboard`  
**Problema:** Usa `$user->activeRoles()->where('role_code', 'AGENT')->value('company_id')`  
**Impacto:** Dashboard de empresa incorrecta  
**Company ID Required:** ✅ Sí

---

### 3️⃣ POST `/api/areas`, PUT `/api/areas/{id}`, DELETE `/api/areas/{id}`
**Controller:** `AreaController::store/update/destroy`  
**Problema:** Usa `JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN')` para asignar empresa  
**Impacto:** Área creada en empresa incorrecta si tiene múltiples COMPANY_ADMIN  
**Company ID Required:** ✅ Sí

---

### 4️⃣ POST `/api/tickets/categories`, PUT `/api/tickets/categories/{id}`, DELETE `/api/tickets/categories/{id}`
**Controller:** `CategoryController::store/update/destroy`  
**Problema:** Usa `JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN')` para asignar empresa  
**Impacto:** Categoría creada en empresa incorrecta  
**Company ID Required:** ✅ Sí

---

## ✅ ENDPOINTS EXCLUIDOS (No Requieren Cambios)

Estos endpoints **NO filtran datos** según el rol, solo **validan permisos** con policies/middleware.

### Justificación de Exclusión

| Endpoint | Razón de Exclusión |
|----------|-------------------|
| `POST /api/tickets` | Solo valida que sea USER, no filtra datos de retorno |
| `GET /api/tickets/{ticket}` | Policy valida acceso, pero no filtra (o ve o no ve) |
| `PATCH /api/tickets/{ticket}` | Policy valida, no filtra |
| `DELETE /api/tickets/{ticket}` | Policy + middleware role:COMPANY_ADMIN |
| `POST /api/tickets/{ticket}/responses` | YA INCLUIDO en críticos (ResponseService) |
| `POST /api/announcements/*` | Solo validan COMPANY_ADMIN, asignan empresa del JWT (MEDIA PRIORIDAD) |
| `POST /api/users/{userId}/roles` | Policy valida, no filtra datos de lectura |
| `DELETE /api/users/roles/{roleId}` | Policy valida |
| `GET /api/companies/{company}` | Policy valida acceso puntual |
| `POST /api/companies/{company}/follow` | Acción puntual, no filtra listado |
| `GET /api/roles` | No filtra por rol de usuario |
| Web Routes (todas) | Usan JWT para inyectar user, pero no filtran datos de API |

**Total Excluidos:** 12 endpoints

---

## 📋 RESUMEN DE ACCIONES REQUERIDAS

### Modificaciones de Código

| Tipo de Cambio | Archivos Afectados | Cantidad |
|----------------|-------------------|----------|
| **Servicios** | TicketService, ResponseService, ArticleService | 3 |
| **Controladores** | AnnouncementController, ActivityLogController, UserController, AnalyticsController, AreaController, CategoryController | 6 |
| **Helpers** | JWTHelper (agregar métodos nuevos) | 1 |
| **Auth** | TokenService, AuthController, RefreshTokenController | 3 |
| **Total Archivos** | - | **13** |

### Patrón de Migración

**ANTES:**
```php
// Prioridad fija
if (JWTHelper::hasRoleFromJWT('PLATFORM_ADMIN')) { ... }
elseif (JWTHelper::hasRoleFromJWT('COMPANY_ADMIN')) { ... }

// O extracción arbitraria
$companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
```

**DESPUÉS:**
```php
// Rol actualmente seleccionado por el usuario
$activeRole = JWTHelper::getActiveRoleCode();
$companyId = JWTHelper::getActiveCompanyId();

if ($activeRole === 'PLATFORM_ADMIN') { ... }
elseif ($activeRole === 'COMPANY_ADMIN' || $activeRole === 'AGENT') {
    $query->where('company_id', $companyId);
}
```

---

## 🎯 Siguiente Paso

Con este mapeo completo, procederé a:
1. ✅ Implementar Backend Core (JWTHelper, TokenService, AuthController)
2. ✅ Migrar los 9 endpoints críticos
3. ✅ Migrar los 4 endpoints de media prioridad
4. ✅ Testing exhaustivo

**Todos los cambios se mantienen en la rama `feature/active-role-system` para revisión segura.**

---

*Generado: 2025-12-07*  
*Branch: feature/active-role-system*
