# Auditoría de Services y DataLoaders contra Modelado V7.0

**Fecha**: 07-Oct-2025
**Objetivo**: Detectar discrepancias entre la implementación actual y el Modelado V7.0 de base de datos

**Contexto**: Después de completar FASE 0 (database 100% alineada con Modelado V7.0), se identificó que algunos Services y DataLoaders fueron escritos antes de los cambios estructurales y pueden tener incompatibilidades críticas.

---

## 🔴 DISCREPANCIAS CRÍTICAS (Bloqueantes)

### 1. RoleService.php - REQUIERE REFACTORIZACIÓN COMPLETA

**Ubicación**: `app/Features/UserManagement/Services/RoleService.php`

**Problema**: El servicio usa `role_id` (UUID) en todos los métodos, pero Modelado V7.0 cambió la FK de UserRole de `role_id` UUID a `role_code` VARCHAR.

**Impacto**: 🔴 **BLOQUEANTE** - El servicio fallará completamente al intentar asignar/revocar roles.

**Métodos afectados**:

#### `getRoleById()` (líneas 28-37)
```php
// ❌ INCORRECTO
public function getRoleById(string $roleId): Role
{
    $role = Role::find($roleId);  // Busca por UUID
    // ...
}

// ✅ CORRECTO (debería ser)
public function getRoleByCode(string $roleCode): Role
{
    $role = Role::where('role_code', $roleCode)->first();
    // ...
}
```

#### `getRoleByName()` (línea 48)
```php
// ❌ INCORRECTO
public function getRoleByName(string $roleName): Role
{
    $role = Role::where('name', $roleName)->first();
    // ...
}

// ✅ CORRECTO - 'name' es correcto, pero 'role_code' es más eficiente
// El método está bien, pero debería llamarse getRoleByCode() y usar 'role_code'
```

#### `getAllRoles()` (línea 64)
```php
// ❌ INCORRECTO - scope no existe
public function getAllRoles()
{
    return Role::byPriority()->get();  // byPriority() no está definido
}

// ✅ CORRECTO
public function getAllRoles()
{
    return Role::orderBy('role_code')->get();
}
```

#### `assignRoleToUser()` (líneas 98-155) - MÁS CRÍTICO
```php
// ❌ INCORRECTO
public function assignRoleToUser(
    string $userId,
    string $roleId,  // ❌ Debería ser $roleCode
    ?string $companyId = null,
    ?string $assignedById = null  // ❌ Campo es 'assigned_by', no 'assigned_by_id'
): UserRole {
    // Línea 111
    $role = $this->getRoleById($roleId);  // ❌ Busca por UUID

    // Líneas 131-133 - Query incorrecta
    $existingRole = UserRole::where('user_id', $userId)
        ->where('role_id', $roleId)  // ❌ Campo es 'role_code', no 'role_id'
        ->where('company_id', $companyId)
        ->first();

    // Líneas 149-155 - Creación incorrecta
    return UserRole::create([
        'user_id' => $userId,
        'role_id' => $roleId,  // ❌ Campo es 'role_code', no 'role_id'
        'company_id' => $companyId,
        'is_active' => true,
        'assigned_by_id' => $assignedById,  // ❌ Campo es 'assigned_by'
    ]);
}

// ✅ CORRECTO (debería ser)
public function assignRoleToUser(
    string $userId,
    string $roleCode,  // ✅ VARCHAR, no UUID
    ?string $companyId = null,
    ?string $assignedBy = null  // ✅ Nombre correcto
): UserRole {
    // Validar que el role_code existe
    $role = $this->getRoleByCode($roleCode);

    // Verificar si ya existe
    $existingRole = UserRole::where('user_id', $userId)
        ->where('role_code', $roleCode)  // ✅ Correcto
        ->where('company_id', $companyId)
        ->first();

    // Si existe y está activo, retornar
    if ($existingRole && $existingRole->is_active) {
        return $existingRole;
    }

    // Si existe pero está inactivo, reactivar
    if ($existingRole) {
        $existingRole->update([
            'is_active' => true,
            'assigned_by' => $assignedBy,
        ]);
        return $existingRole->fresh();
    }

    // Crear nuevo
    return UserRole::create([
        'user_id' => $userId,
        'role_code' => $roleCode,  // ✅ Correcto
        'company_id' => $companyId,
        'is_active' => true,
        'assigned_by' => $assignedBy,  // ✅ Correcto
    ]);
}
```

#### `revokeRoleFromUser()` (líneas 174-178)
```php
// ❌ INCORRECTO
$userRole = UserRole::where('user_id', $userId)
    ->where('role_id', $roleId)  // ❌ Campo es 'role_code'
    ->where('company_id', $companyId)
    // ...

// ✅ CORRECTO
$userRole = UserRole::where('user_id', $userId)
    ->where('role_code', $roleCode)  // ✅ Correcto
    ->where('company_id', $companyId)
    // ...
```

#### `getUserRoles()` (líneas 241-267)
```php
// ❌ INCORRECTO - Mapea incorrectamente
->map(function ($userRole) {
    return [
        'role' => $userRole->role,  // ✅ Relación está correcta
        'company' => $userRole->company,
        'is_active' => $userRole->is_active,
        'assigned_at' => $userRole->assigned_at,
        'assigned_by' => $userRole->assignedBy,  // ✅ Relación correcta
    ];
});

// Nota: El mapeo está bien, el problema es que cualquier consulta previa
// usando role_id fallará antes de llegar aquí
```

**Archivos que dependen de RoleService**:
- `app/Features/CompanyManagement/Services/CompanyRequestService.php` (línea 102)
- Todos los resolvers de UserManagement que asignan roles
- RegisterMutation (pendiente de implementar)

---

### 2. CompanyRequestService.php - Llamada a método incorrecto

**Ubicación**: `app/Features/CompanyManagement/Services/CompanyRequestService.php` líneas 101-107

**Problema**: Llama a método `assignRole()` que no existe en RoleService.

```php
// ❌ INCORRECTO (líneas 101-107)
$this->roleService->assignRole(
    userId: $adminUser->id,
    roleCode: 'company_admin',
    companyId: $company->id,
    assignedBy: $reviewer
);

// ✅ CORRECTO (debería ser)
$this->roleService->assignRoleToUser(
    userId: $adminUser->id,
    roleCode: 'company_admin',  // Después de refactorizar RoleService
    companyId: $company->id,
    assignedBy: $reviewer->id  // assignedBy espera UUID, no objeto User
);
```

**Impacto**: 🔴 **BLOQUEANTE** - El flujo de aprobación de empresas fallará completamente.

---

## ⚠️ DISCREPANCIAS IMPORTANTES (No bloqueantes pero deben corregirse)

### 3. DataLoaders con Mock Data (7 archivos)

**Problema**: 7 DataLoaders aún retornan datos mock en lugar de datos reales. Los modelos ya existen y están listos para usar.

**Impacto**: 🟡 **IMPORTANTE** - Las queries GraphQL funcionarán pero retornarán datos falsos.

#### Shared DataLoaders con Mock Data:

1. **UserByIdLoader** (`app/Shared/GraphQL/DataLoaders/UserByIdLoader.php`)
   - Líneas 39-69: Mock data
   - Implementación real comentada en líneas 42-48
   - ✅ Modelo disponible: `App\Features\UserManagement\Models\User`

2. **CompaniesByUserIdLoader** (`app/Shared/GraphQL/DataLoaders/CompaniesByUserIdLoader.php`)
   - Líneas 68-91: Mock data
   - Implementación real comentada en líneas 42-65
   - ✅ Modelos disponibles: `UserRole`, `Company`

3. **CompanyByIdLoader** (`app/Shared/GraphQL/DataLoaders/CompanyByIdLoader.php`)
   - Líneas 51-79: Mock data
   - Implementación real comentada en líneas 42-48
   - ✅ Modelo disponible: `App\Features\CompanyManagement\Models\Company`

4. **UsersByCompanyIdLoader** (`app/Shared/GraphQL/DataLoaders/UsersByCompanyIdLoader.php`)
   - Líneas 69-103: Mock data
   - Implementación real comentada en líneas 42-66
   - ✅ Modelos disponibles: `UserRole`, `User`

#### Feature DataLoaders con Mock Data:

5. **RefreshTokensByUserIdLoader** (`app/Features/Authentication/GraphQL/DataLoaders/RefreshTokensByUserIdLoader.php`)
   - Líneas 54-88: Mock data
   - Implementación real comentada en líneas 39-52
   - ✅ Modelo disponible: `App\Features\Authentication\Models\RefreshToken`

6. **RefreshTokenBySessionIdLoader** (`app/Features/Authentication/GraphQL/DataLoaders/RefreshTokenBySessionIdLoader.php`)
   - Líneas 53-83: Mock data
   - Implementación real comentada en líneas 39-51
   - ❌ **PROBLEMA ARQUITECTURAL**: RefreshToken NO tiene campo `session_id` en Modelado V7.0

7. **UserRoleHistoryByUserIdLoader** (`app/Features/UserManagement/GraphQL/DataLoaders/UserRoleHistoryByUserIdLoader.php`)
   - Líneas 55-114: Mock data
   - Implementación real comentada en líneas 39-52
   - ✅ Modelo disponible: `App\Features\UserManagement\Models\UserRole`

**Acción requerida**:
- Descomentar implementación real
- Eliminar bloques de mock data
- Probar en GraphiQL

---

### 4. RefreshTokenBySessionIdLoader - Problema Arquitectural

**Ubicación**: `app/Features/Authentication/GraphQL/DataLoaders/RefreshTokenBySessionIdLoader.php`

**Problema**: El DataLoader busca por campo `session_id`, pero RefreshToken NO tiene este campo en Modelado V7.0.

**Estructura actual de RefreshToken**:
```
- id (UUID, PK)
- user_id (UUID, FK)
- token_hash (VARCHAR)
- device_name (VARCHAR, nullable)
- device_fingerprint (VARCHAR, nullable)
- ip_address (INET, nullable)
- user_agent (TEXT, nullable)
- last_used_at (TIMESTAMP)
- expires_at (TIMESTAMP)
- revoked_at (TIMESTAMP, nullable)
- revoke_reason (VARCHAR, nullable)  ← Nuevo en V7.0
- created_at
- updated_at
```

**Implementación comentada (líneas 39-51)**:
```php
/*
$refreshTokens = RefreshToken::query()
    ->whereIn('session_id', $keys)  // ❌ session_id NO EXISTE
    ->where('expires_at', '>', now())
    ->get()
    ->keyBy('session_id');
*/
```

**Posibles soluciones**:

1. **Opción A**: Eliminar este DataLoader
   - `session_id` está en el JWT payload, no en DB
   - Usar `RefreshTokensByUserIdLoader` en su lugar
   - Filtrar en cliente por `session_id` del token

2. **Opción B**: Agregar campo `session_id` a RefreshToken
   - Modificar migración
   - Actualizar modelo
   - **NO RECOMENDADO** - duplicaría información del JWT

3. **Opción C**: Usar `token_hash` como identificador único
   - Crear `RefreshTokenByTokenHashLoader`
   - Más semánticamente correcto

**Recomendación**: **Opción A** - Eliminar el DataLoader. El `session_id` es información transitoria del JWT, no debe buscarse en DB.

**Impacto**: 🟡 **BAJO** - Este DataLoader probablemente no se usa aún (resolvers son dummy).

---

## ✅ COMPONENTES SIN DISCREPANCIAS

### Services (6 de 9)

1. **AuthService** (`app/Features/Authentication/Services/AuthService.php`)
   - ✅ Usa correctamente UserService
   - ✅ Carga relaciones correctamente: `$user->fresh(['profile', 'roles', 'companies'])`
   - ✅ Maneja eventos correctamente

2. **TokenService** (`app/Features/Authentication/Services/TokenService.php`)
   - ✅ Genera JWT correctamente
   - ✅ Crea RefreshToken en DB con todos los campos
   - ✅ Valida expiración y revocación

3. **PasswordResetService** (`app/Features/Authentication/Services/PasswordResetService.php`)
   - ✅ Usa tabla `password_reset_tokens` correctamente
   - ✅ Revoca todos los refresh tokens al resetear contraseña

4. **UserService** (`app/Features/UserManagement/Services/UserService.php`)
   - ✅ Crea UserProfile con `user_id` correctamente (PK es user_id ahora)
   - ✅ Genera `user_code` con CodeGenerator
   - ✅ Maneja relaciones correctamente

5. **ProfileService** (`app/Features/UserManagement/Services/ProfileService.php`)
   - ✅ Busca perfiles por `user_id` (línea 26)
   - ✅ Actualiza campos correctamente

6. **CompanyService** (`app/Features/CompanyManagement/Services/CompanyService.php`)
   - ✅ Usa `role_code` en queries (líneas 104-107, 141-144)
   - ✅ Genera `company_code` correctamente
   - ✅ Maneja estadísticas de agentes activos

7. **CompanyFollowService** (`app/Features/CompanyManagement/Services/CompanyFollowService.php`)
   - ✅ CRUD de CompanyFollower correcto
   - ✅ Previene duplicados correctamente

### DataLoaders (4 de 11)

1. **UserProfileByUserIdLoader** (`app/Shared/GraphQL/DataLoaders/UserProfileByUserIdLoader.php`)
   - ✅ Implementación real activa
   - ✅ Usa `user_id` correctamente

2. **UserRolesByUserIdLoader** (`app/Shared/GraphQL/DataLoaders/UserRolesByUserIdLoader.php`)
   - ✅ Implementación real activa
   - ✅ Carga relación `role` correctamente
   - ✅ Filtra por `is_active = true`

3. **FollowedCompaniesByUserIdLoader** (`app/Features/CompanyManagement/GraphQL/DataLoaders/FollowedCompaniesByUserIdLoader.php`)
   - ✅ Implementación real activa
   - ✅ Usa CompanyFollower correctamente
   - ⚠️ Tiene placeholders temporales para TicketManagement (futuro)

4. **CompanyFollowersByCompanyIdLoader** (`app/Features/CompanyManagement/GraphQL/DataLoaders/CompanyFollowersByCompanyIdLoader.php`)
   - ✅ Implementación real activa
   - ✅ Agrupa por `company_id` correctamente

---

## 📋 PLAN DE REFACTORIZACIÓN

### Fase 1: Corrección Crítica (Bloqueantes) - PRIORIDAD MÁXIMA

1. **Refactorizar RoleService.php**
   - Cambiar todos los parámetros `$roleId` → `$roleCode`
   - Cambiar `getRoleById()` → `getRoleByCode()`
   - Actualizar todas las queries de `role_id` → `role_code`
   - Cambiar `assigned_by_id` → `assigned_by`
   - Eliminar `byPriority()` scope, usar `orderBy('role_code')`
   - Actualizar docblocks y type hints

2. **Corregir CompanyRequestService.php**
   - Cambiar `assignRole()` → `assignRoleToUser()`
   - Ajustar parámetros según nueva firma de RoleService
   - Pasar `$reviewer->id` en lugar de `$reviewer`

**Tiempo estimado**: 30-45 minutos
**Archivos afectados**: 2
**Tests requeridos**: Unit tests de RoleService, integration test de company approval

---

### Fase 2: Activación de DataLoaders (Importante) - PRIORIDAD ALTA

3. **Activar implementación real en 6 DataLoaders con mock data**
   - UserByIdLoader
   - CompaniesByUserIdLoader
   - CompanyByIdLoader
   - UsersByCompanyIdLoader
   - RefreshTokensByUserIdLoader
   - UserRoleHistoryByUserIdLoader

   **Pasos por DataLoader**:
   1. Descomentar bloque de implementación real
   2. Eliminar bloque de mock data
   3. Verificar nombres de campos vs Modelado V7.0
   4. Probar en GraphiQL con query real

4. **Evaluar RefreshTokenBySessionIdLoader**
   - Revisar si algún resolver lo usa
   - Si no se usa: Eliminar archivo
   - Si se usa: Implementar Opción C (RefreshTokenByTokenHashLoader)

**Tiempo estimado**: 1 hora
**Archivos afectados**: 7
**Tests requeridos**: GraphQL integration tests

---

### Fase 3: Verificación Final

5. **Validar GraphQL schema completo**
   ```bash
   powershell -Command "php artisan lighthouse:validate-schema"
   ```

6. **Probar en GraphiQL** queries que usen DataLoaders refactorizados:
   ```graphql
   query TestDataLoaders {
     me {
       id
       userCode
       profile { firstName lastName }
       activeRoles { role { roleCode name } }
       companies { id name }
     }
   }
   ```

7. **Commit de refactorización**
   ```bash
   git add .
   git commit -m "refactor: align Services and DataLoaders with Modelado V7.0

   - Refactor RoleService to use role_code instead of role_id
   - Fix CompanyRequestService method call
   - Activate real implementation in 6 DataLoaders
   - Remove RefreshTokenBySessionIdLoader (architectural mismatch)

   🤖 Generated with Claude Code

   Co-Authored-By: Claude <noreply@anthropic.com>"
   ```

---

## 📊 RESUMEN EJECUTIVO

| Categoría | Total | ✅ OK | ⚠️ Mock Data | 🔴 Bloqueante |
|-----------|-------|-------|--------------|---------------|
| **Services** | 9 | 7 (78%) | - | 2 (22%) |
| **DataLoaders** | 11 | 4 (36%) | 7 (64%) | - |
| **TOTAL** | 20 | 11 (55%) | 7 (35%) | 2 (10%) |

**Componentes bloqueantes**: 2
**Componentes con mock data**: 7
**Componentes listos para producción**: 11

**Próximo paso**: Ejecutar Fase 1 de refactorización antes de implementar RegisterMutation.
