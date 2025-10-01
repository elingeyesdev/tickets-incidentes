# 📘 GUÍA DE DATALOADERS - Sistema Helpdesk

**Fecha:** 01 de Octubre de 2025
**Estado:** ✅ 6 DataLoaders implementados

---

## 🎯 ¿QUÉ SON LOS DATALOADERS?

Los **DataLoaders** son un patrón de optimización que **resuelve el problema N+1** en GraphQL al combinar múltiples consultas individuales en una sola consulta por lote.

### Problema N+1 sin DataLoaders:

```graphql
query {
  users {
    id
    name
    profile { firstName }  # ← 1 query por cada user!
  }
}
```

**Consultas SQL generadas:**
```sql
SELECT * FROM users;                    -- 1 query
SELECT * FROM user_profiles WHERE user_id = 1;  -- N queries
SELECT * FROM user_profiles WHERE user_id = 2;
SELECT * FROM user_profiles WHERE user_id = 3;
... (100 queries más si hay 100 usuarios!)
```

### Solución con DataLoaders:

**Consultas SQL generadas:**
```sql
SELECT * FROM users;                                     -- 1 query
SELECT * FROM user_profiles WHERE user_id IN (1,2,3,...); -- 1 query batched
```

✅ **2 queries en total** vs ❌ **102 queries sin DataLoaders**

---

## 📦 DATALOADERS IMPLEMENTADOS

### 1. **UserByIdLoader**
**Archivo:** `app/Shared/GraphQL/DataLoaders/UserByIdLoader.php`
**Propósito:** Cargar usuarios por ID
**Uso:** En relaciones donde se referencia un usuario (created_by, assigned_to, etc.)

```php
// Ejemplo de uso en un resolver:
use App\Shared\GraphQL\DataLoaders\UserByIdLoader;

public function createdBy($root, array $args, GraphQLContext $context)
{
    return $context->dataLoader(UserByIdLoader::class)
        ->load($root->created_by_id);
}
```

---

### 2. **CompanyByIdLoader**
**Archivo:** `app/Shared/GraphQL/DataLoaders/CompanyByIdLoader.php`
**Propósito:** Cargar empresas por ID
**Uso:** En roles, tickets, y contextos empresariales

```php
// Ejemplo de uso:
use App\Shared\GraphQL\DataLoaders\CompanyByIdLoader;

public function company($root, array $args, GraphQLContext $context)
{
    return $context->dataLoader(CompanyByIdLoader::class)
        ->load($root->company_id);
}
```

---

### 3. **UserProfileByUserIdLoader**
**Archivo:** `app/Shared/GraphQL/DataLoaders/UserProfileByUserIdLoader.php`
**Propósito:** Cargar perfiles de usuarios (relación 1:1)
**Uso:** En User.profile

```php
// Ejemplo de uso en User type:
use App\Shared\GraphQL\DataLoaders\UserProfileByUserIdLoader;

public function profile($root, array $args, GraphQLContext $context)
{
    return $context->dataLoader(UserProfileByUserIdLoader::class)
        ->load($root->id);
}
```

---

### 4. **UserRolesByUserIdLoader**
**Archivo:** `app/Shared/GraphQL/DataLoaders/UserRolesByUserIdLoader.php`
**Propósito:** Cargar roles activos de usuarios (relación 1:N)
**Uso:** En User.activeRoles

```php
// Ejemplo de uso:
use App\Shared\GraphQL\DataLoaders\UserRolesByUserIdLoader;

public function activeRoles($root, array $args, GraphQLContext $context)
{
    return $context->dataLoader(UserRolesByUserIdLoader::class)
        ->load($root->id);
}
```

---

### 5. **CompaniesByUserIdLoader**
**Archivo:** `app/Shared/GraphQL/DataLoaders/CompaniesByUserIdLoader.php`
**Propósito:** Cargar empresas donde el usuario tiene roles
**Uso:** En User.companies

```php
// Ejemplo de uso:
use App\Shared\GraphQL\DataLoaders\CompaniesByUserIdLoader;

public function companies($root, array $args, GraphQLContext $context)
{
    return $context->dataLoader(CompaniesByUserIdLoader::class)
        ->load($root->id);
}
```

---

### 6. **UsersByCompanyIdLoader**
**Archivo:** `app/Shared/GraphQL/DataLoaders/UsersByCompanyIdLoader.php`
**Propósito:** Cargar usuarios de una empresa (agentes/admins)
**Uso:** En Company.users o companyUsers query

```php
// Ejemplo de uso:
use App\Shared\GraphQL\DataLoaders\UsersByCompanyIdLoader;

public function users($root, array $args, GraphQLContext $context)
{
    return $context->dataLoader(UsersByCompanyIdLoader::class)
        ->load($root->id);
}
```

---

## 🔧 CÓMO USAR DATALOADERS

### Paso 1: Importar el DataLoader

```php
use App\Shared\GraphQL\DataLoaders\UserByIdLoader;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
```

### Paso 2: Usar en el resolver

```php
public function user($root, array $args, GraphQLContext $context)
{
    // El DataLoader agrupa automáticamente todas las llamadas
    return $context->dataLoader(UserByIdLoader::class)
        ->load($root->user_id);
}
```

### Paso 3: Lighthouse hace la magia

Lighthouse automáticamente:
1. **Agrupa** todas las llamadas a `load()` en el mismo request
2. **Ejecuta** el método `resolve()` del DataLoader UNA SOLA VEZ
3. **Distribuye** los resultados a cada llamada original

---

## 📝 PATRÓN DE CREACIÓN DE DATALOADERS

Cuando necesites crear un nuevo DataLoader, sigue este patrón:

```php
<?php

namespace App\Shared\GraphQL\DataLoaders;

use Closure;
use Illuminate\Support\Collection;
use Nuwave\Lighthouse\Execution\DataLoader\BatchLoader;

class MyCustomLoader extends BatchLoader
{
    /**
     * Resuelve múltiples IDs en una sola query
     *
     * @param array<string> $keys Array de IDs a cargar
     * @return Closure
     */
    public function resolve(array $keys): Closure
    {
        return function () use ($keys): Collection {
            // 1. Cargar todos los registros en UNA query
            $items = MyModel::query()
                ->whereIn('id', $keys)
                ->get()
                ->keyBy('id');

            // 2. Retornar en el MISMO ORDEN que los keys
            return collect($keys)->map(fn($key) => $items->get($key));
        };
    }
}
```

### ⚠️ REGLAS IMPORTANTES:

1. **SIEMPRE** cargar en una sola query (usar `whereIn`)
2. **SIEMPRE** retornar en el mismo orden que `$keys`
3. **SIEMPRE** usar `keyBy()` antes de mapear
4. **NUNCA** hacer queries dentro del `map()`

---

## 🧪 TESTING DE DATALOADERS

### Test Unitario Básico:

```php
<?php

namespace Tests\Unit\DataLoaders;

use App\Shared\GraphQL\DataLoaders\UserByIdLoader;
use Tests\TestCase;

class UserByIdLoaderTest extends TestCase
{
    /** @test */
    public function it_loads_multiple_users_in_one_query()
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $loader = new UserByIdLoader();
        $keys = [$user1->id, $user2->id, $user3->id];

        // Act
        $resolver = $loader->resolve($keys);
        $results = $resolver();

        // Assert
        $this->assertCount(3, $results);
        $this->assertEquals($user1->id, $results[0]->id);
        $this->assertEquals($user2->id, $results[1]->id);
        $this->assertEquals($user3->id, $results[2]->id);
    }

    /** @test */
    public function it_returns_results_in_same_order_as_keys()
    {
        // Test order preservation
    }

    /** @test */
    public function it_handles_missing_ids_gracefully()
{
        // Test null handling
    }
}
```

---

## 🚀 MIGRACIÓN DE MOCK A REAL

Actualmente los DataLoaders usan **datos mock**. Cuando implementes los Models reales, reemplaza el código mock:

### Antes (Mock):
```php
return function () use ($keys): Collection {
    // MOCK DATA
    $mockUsers = collect($keys)->mapWithKeys(function ($userId) {
        return [$userId => (object) ['id' => $userId, ...]];
    });

    return collect($keys)->map(fn($key) => $mockUsers->get($key));
};
```

### Después (Real):
```php
return function () use ($keys): Collection {
    // REAL QUERY
    $users = \App\Features\UserManagement\Models\User::query()
        ->whereIn('id', $keys)
        ->get()
        ->keyBy('id');

    return collect($keys)->map(fn($key) => $users->get($key));
};
```

---

## 📊 DEBUGGING DATALOADERS

### Verificar N+1 con Laravel Debugbar:

```bash
composer require barryvdh/laravel-debugbar --dev
```

Ejecuta una query y verifica que:
- ✅ Solo 1-2 queries por relación (no N queries)
- ✅ Uso de `whereIn` en lugar de múltiples `where`

### Habilitar logs de Lighthouse:

```php
// config/lighthouse.php
'route' => [
    'middleware' => [
        Nuwave\Lighthouse\Http\Middleware\LogGraphQLQueries::class,
    ],
],
```

---

## 🎯 PRÓXIMOS PASOS

1. ✅ **DataLoaders creados** (6/6)
2. ✅ **Configuración actualizada** (lighthouse.php)
3. ⏳ **Reemplazar mock por Models reales** (cuando estén listos)
4. ⏳ **Crear tests unitarios** para cada DataLoader
5. ⏳ **Integrar en Resolvers** cuando implementes las features

---

## 📚 RECURSOS

- [Lighthouse DataLoaders Docs](https://lighthouse-php.com/master/performance/n-plus-one.html)
- [Facebook DataLoader Pattern](https://github.com/graphql/dataloader)
- [Solving N+1 in GraphQL](https://www.apollographql.com/blog/backend/data-sources/batching-and-caching-layers/)

---

**✅ FASE 1 COMPLETADA:** DataLoaders listos para usar en las siguientes fases del proyecto.