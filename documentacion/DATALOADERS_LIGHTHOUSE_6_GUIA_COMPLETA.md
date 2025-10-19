# 📘 GUÍA COMPLETA DE DATALOADERS - Lighthouse 6

**Fecha:** 16 de Enero de 2025  
**Versión:** Lighthouse 6.x  
**Estado:** ✅ Patrón moderno implementado

---

## 🎯 RESUMEN EJECUTIVO

Esta guía documenta la **migración de DataLoaders de Lighthouse 5 a Lighthouse 6** y establece el **patrón estándar** para futuras implementaciones en el proyecto Helpdesk.

### **Cambios Principales:**
- ❌ **Lighthouse 5**: Patrón `__invoke()` - **DEPRECADO**
- ✅ **Lighthouse 6**: Patrón `GraphQL\Deferred` + `BatchLoaderRegistry` - **ACTUAL**

---

## 📊 COMPARACIÓN DETALLADA

### **PATRÓN ANTIGUO (Lighthouse 5) - DEPRECADO**

```php
<?php
// ❌ PATRÓN ANTIGUO - NO USAR
class UserProfileByUserIdLoader
{
    public function __invoke(array $keys): array
    {
        $profiles = UserProfile::query()
            ->whereIn('user_id', $keys)
            ->get()
            ->keyBy('user_id');

        return array_map(fn($key) => $profiles->get($key), $keys);
    }
}
```

**Características del patrón antiguo:**
- ✅ Funciona pero está **deprecado**
- ❌ No usa `GraphQL\Deferred`
- ❌ No integra con `BatchLoaderRegistry`
- ❌ Patrón menos eficiente
- ❌ No compatible con optimizaciones futuras

### **PATRÓN NUEVO (Lighthouse 6) - RECOMENDADO**

```php
<?php declare(strict_types=1);

// ✅ PATRÓN NUEVO - USAR SIEMPRE
class UserProfileBatchLoader
{
    protected array $users = [];
    protected array $results = [];
    protected bool $hasResolved = false;

    public function load(Model $user): Deferred
    {
        $userId = $user->id;
        $this->users[$userId] = $user;

        return new Deferred(function () use ($userId) {
            if (! $this->hasResolved) {
                $this->resolve();
            }
            return $this->results[$userId] ?? null;
        });
    }

    protected function resolve(): void
    {
        $userIds = array_keys($this->users);
        
        $profiles = UserProfile::query()
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        foreach ($userIds as $userId) {
            $this->results[$userId] = $profiles->get($userId);
        }

        $this->hasResolved = true;
    }
}
```

**Características del patrón nuevo:**
- ✅ **Oficial** de Lighthouse 6
- ✅ Usa `GraphQL\Deferred` para lazy loading
- ✅ Integra con `BatchLoaderRegistry`
- ✅ Más eficiente y escalable
- ✅ Compatible con futuras versiones
- ✅ Mejor gestión de memoria

---

## 🔧 IMPLEMENTACIÓN PASO A PASO

### **Paso 1: Crear la Clase BatchLoader**

```php
<?php declare(strict_types=1);

namespace App\Features\{FeatureName}\GraphQL\DataLoaders;

use App\Features\{FeatureName}\Models\{ModelName};
use GraphQL\Deferred;
use Illuminate\Database\Eloquent\Model;

class {ModelName}BatchLoader
{
    /**
     * Map from key to Model instances that need loading
     * @var array<string, Model>
     */
    protected array $items = [];

    /**
     * Map from key to loaded results
     * @var array<string, mixed>
     */
    protected array $results = [];

    /** Marks when the actual batch loading happened */
    protected bool $hasResolved = false;

    /**
     * Schedule loading for a model
     *
     * @param Model $model
     * @return Deferred
     */
    public function load(Model $model): Deferred
    {
        $key = $model->id; // o la clave que necesites
        $this->items[$key] = $model;

        return new Deferred(function () use ($key) {
            if (! $this->hasResolved) {
                $this->resolve();
            }
            return $this->results[$key] ?? null;
        });
    }

    /**
     * Resolve all queued items in a single batch query
     */
    protected function resolve(): void
    {
        $keys = array_keys($this->items);

        // TU LÓGICA DE CARGA AQUÍ
        $loadedItems = {ModelName}::query()
            ->whereIn('id', $keys)
            ->get()
            ->keyBy('id');

        // Map results back to keys
        foreach ($keys as $key) {
            $this->results[$key] = $loadedItems->get($key);
        }

        $this->hasResolved = true;
    }
}
```

### **Paso 2: Usar en Field Resolvers**

```php
<?php declare(strict_types=1);

namespace App\Features\{FeatureName}\GraphQL\Types;

use App\Features\{FeatureName}\GraphQL\DataLoaders\{ModelName}BatchLoader;
use Nuwave\Lighthouse\Execution\BatchLoader\BatchLoaderRegistry;
use Nuwave\Lighthouse\Execution\ResolveInfo;

class {ModelName}FieldResolvers
{
    public function {fieldName}($root, array $args, $context, ResolveInfo $resolveInfo)
    {
        // Get or create BatchLoader instance for this field path
        $batchLoader = BatchLoaderRegistry::instance(
            $resolveInfo->path,
            static fn (): {ModelName}BatchLoader => new {ModelName}BatchLoader(),
        );

        return $batchLoader->load($root);
    }
}
```

### **Paso 3: Registrar en Schema GraphQL**

```graphql
# En tu schema.graphql
type User {
    profile: UserProfile
        @field(resolver: "App\\Features\\UserManagement\\GraphQL\\Types\\UserFieldResolvers@profile")
    
    roleContexts: [RoleContext!]!
        @field(resolver: "App\\Features\\UserManagement\\GraphQL\\Types\\UserFieldResolvers@roleContexts")
}
```

---

## 📋 PATRONES ESPECÍFICOS POR TIPO DE RELACIÓN

### **1. Relación 1:1 (One-to-One)**

```php
class UserProfileBatchLoader
{
    protected function resolve(): void
    {
        $userIds = array_keys($this->users);
        
        $profiles = UserProfile::query()
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        foreach ($userIds as $userId) {
            $this->results[$userId] = $profiles->get($userId); // Puede ser null
        }

        $this->hasResolved = true;
    }
}
```

### **2. Relación 1:N (One-to-Many)**

```php
class UserRolesBatchLoader
{
    protected function resolve(): void
    {
        $userIds = array_keys($this->users);
        
        $userRoles = UserRole::query()
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->with(['role', 'company']) // Eager loading
            ->get()
            ->groupBy('user_id');

        foreach ($userIds as $userId) {
            $this->results[$userId] = $userRoles->get($userId, collect());
        }

        $this->hasResolved = true;
    }
}
```

### **3. Relación N:M (Many-to-Many)**

```php
class UserCompaniesBatchLoader
{
    protected function resolve(): void
    {
        $userIds = array_keys($this->users);
        
        $userCompanies = UserCompany::query()
            ->whereIn('user_id', $userIds)
            ->with(['company'])
            ->get()
            ->groupBy('user_id');

        foreach ($userIds as $userId) {
            $companies = $userCompanies->get($userId, collect())
                ->pluck('company')
                ->filter(); // Remove nulls
            
            $this->results[$userId] = $companies;
        }

        $this->hasResolved = true;
    }
}
```

### **4. Con Transformación de Datos**

```php
class UserRoleContextsBatchLoader
{
    protected function resolve(): void
    {
        $userIds = array_keys($this->users);
        
        $userRoles = UserRole::query()
            ->whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->with(['role', 'company'])
            ->get()
            ->groupBy('user_id');

        foreach ($userIds as $userId) {
            $rolesForUser = $userRoles->get($userId, collect());

            // Transformar a formato RoleContext
            $this->results[$userId] = $rolesForUser->map(function ($userRole) {
                return [
                    'roleCode' => strtoupper($userRole->role_code),
                    'roleName' => $userRole->role->role_name,
                    'company' => $userRole->company ? [
                        'id' => $userRole->company->id,
                        'name' => $userRole->company->name,
                    ] : null,
                ];
            })->values()->toArray();
        }

        $this->hasResolved = true;
    }
}
```

---

## ⚠️ REGLAS IMPORTANTES

### **1. Naming Convention**
- ✅ **Usar**: `{ModelName}BatchLoader`
- ❌ **Evitar**: `{ModelName}By{Field}Loader` (patrón antiguo)

### **2. Estructura de Archivos**
```
app/Features/{FeatureName}/GraphQL/DataLoaders/
├── {ModelName}BatchLoader.php
├── {AnotherModel}BatchLoader.php
└── README.md
```

### **3. Type Safety**
- ✅ **Usar**: `declare(strict_types=1);`
- ✅ **Usar**: Type hints completos
- ✅ **Usar**: PHPDoc con tipos específicos

### **4. Performance**
- ✅ **SIEMPRE** usar `whereIn()` para batch loading
- ✅ **SIEMPRE** usar `keyBy()` para mapeo eficiente
- ✅ **SIEMPRE** usar `with()` para eager loading
- ❌ **NUNCA** hacer queries dentro de loops

### **5. Error Handling**
- ✅ **Manejar** casos donde no existen datos (retornar `null` o `collect()`)
- ✅ **Validar** que los keys existen antes de procesar
- ✅ **Loggear** errores críticos

---

## 🧪 TESTING

### **Test Unitario Básico**

```php
<?php declare(strict_types=1);

namespace Tests\Unit\DataLoaders;

use App\Features\UserManagement\GraphQL\DataLoaders\UserProfileBatchLoader;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileBatchLoaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_loads_profiles_in_batch(): void
    {
        // Arrange
        $users = User::factory()->count(3)->create();
        $profiles = UserProfile::factory()->count(3)->create([
            'user_id' => $users->pluck('id')->toArray()
        ]);

        $loader = new UserProfileBatchLoader();

        // Act
        $deferred1 = $loader->load($users[0]);
        $deferred2 = $loader->load($users[1]);
        $deferred3 = $loader->load($users[2]);

        $result1 = $deferred1->resolve();
        $result2 = $deferred2->resolve();
        $result3 = $deferred3->resolve();

        // Assert
        $this->assertInstanceOf(UserProfile::class, $result1);
        $this->assertInstanceOf(UserProfile::class, $result2);
        $this->assertInstanceOf(UserProfile::class, $result3);
        
        $this->assertEquals($users[0]->id, $result1->user_id);
        $this->assertEquals($users[1]->id, $result2->user_id);
        $this->assertEquals($users[2]->id, $result3->user_id);
    }

    public function test_handles_missing_profiles(): void
    {
        // Arrange
        $user = User::factory()->create();
        $loader = new UserProfileBatchLoader();

        // Act
        $deferred = $loader->load($user);
        $result = $deferred->resolve();

        // Assert
        $this->assertNull($result);
    }
}
```

---

## 🔄 MIGRACIÓN DE DATALOADERS EXISTENTES

### **Checklist de Migración**

- [ ] **Identificar** DataLoaders con patrón `__invoke()`
- [ ] **Crear** nueva versión con patrón `BatchLoader`
- [ ] **Actualizar** Field Resolvers para usar `BatchLoaderRegistry`
- [ ] **Probar** que funciona correctamente
- [ ] **Eliminar** DataLoader antiguo
- [ ] **Actualizar** documentación

### **Archivos a Migrar (si existen)**

```bash
# Buscar DataLoaders antiguos
find app/ -name "*Loader.php" -exec grep -l "__invoke" {} \;

# Verificar que no se usan
grep -r "OldLoader::class" app/
```

---

## 📚 RECURSOS Y REFERENCIAS

### **Documentación Oficial**
- [Lighthouse DataLoaders Docs](https://lighthouse-php.com/master/performance/n-plus-one.html)
- [GraphQL Deferred](https://webonyx.github.io/graphql-php/data-fetching/#deferred-resolvers)
- [BatchLoader Pattern](https://github.com/graphql/dataloader)

### **Archivos de Referencia en el Proyecto**
- `app/Shared/GraphQL/DataLoaders/UserProfileBatchLoader.php` ✅
- `app/Shared/GraphQL/DataLoaders/UserRolesBatchLoader.php` ✅
- `app/Shared/GraphQL/DataLoaders/UserRoleContextsBatchLoader.php` ✅
- `app/Features/UserManagement/GraphQL/Types/UserFieldResolvers.php` ✅

### **Archivos Deprecados (Eliminar)**
- `app/Shared/GraphQL/DataLoaders/UserProfileByUserIdLoader.php` ❌
- `app/Shared/GraphQL/DataLoaders/UserRolesByUserIdLoader.php` ❌

---

## 🎯 PRÓXIMOS PASOS

1. ✅ **Patrón establecido** - Lighthouse 6 con `GraphQL\Deferred`
2. ⏳ **Migrar** DataLoaders existentes al nuevo patrón
3. ⏳ **Eliminar** DataLoaders con patrón `__invoke()`
4. ⏳ **Crear** DataLoaders para nuevas features siguiendo este patrón
5. ⏳ **Documentar** casos específicos según necesidades del proyecto

---

**✅ ESTÁNDAR ESTABLECIDO:** Todos los futuros DataLoaders deben seguir el patrón Lighthouse 6 documentado en esta guía.
