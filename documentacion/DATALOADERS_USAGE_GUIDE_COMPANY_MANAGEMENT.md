# Guía de Uso: DataLoaders CompanyManagement

**Propósito:** Guía rápida para desarrolladores sobre cómo usar los DataLoaders implementados en CompanyManagement.

---

## PATRÓN CORRECTO (Lighthouse 6)

Todos los DataLoaders siguen el patrón con `GraphQL\Deferred`:

```php
<?php declare(strict_types=1);

namespace App\Features\Example\GraphQL\DataLoaders;

use GraphQL\Deferred;

class ExampleBatchLoader
{
    protected array $items = [];
    protected array $results = [];
    protected bool $hasResolved = false;

    public function load($id): Deferred
    {
        $this->items[$id] = $id;

        return new Deferred(function () use ($id) {
            if (! $this->hasResolved) {
                $this->resolve();
            }

            return $this->results[$id] ?? null;
        });
    }

    protected function resolve(): void
    {
        $ids = array_keys($this->items);

        // BATCH QUERY HERE (whereIn, GROUP BY, etc.)
        $models = Model::whereIn('id', $ids)->get()->keyBy('id');

        foreach ($ids as $id) {
            $this->results[$id] = $models->get($id);
        }

        $this->hasResolved = true;
    }
}
```

---

## DATALOADERS DISPONIBLES

### 1. FollowedCompanyIdsByUserIdBatchLoader

**Propósito:** Obtener array de company IDs que un usuario sigue.

**Ubicación:** `app/Features/CompanyManagement/GraphQL/DataLoaders/FollowedCompanyIdsByUserIdBatchLoader.php`

**Uso en Resolver:**
```php
use App\Features\CompanyManagement\GraphQL\DataLoaders\FollowedCompanyIdsByUserIdBatchLoader;

class CompaniesQuery
{
    public function __invoke($root, array $args)
    {
        $companies = Company::paginate(20);
        $user = auth()->user();

        // Cargar todos los company IDs seguidos en 1 query
        $loader = app(FollowedCompanyIdsByUserIdBatchLoader::class);
        $followedIds = $loader->load($user->id); // Returns: array<string>

        // Iterar sin N+1
        foreach ($companies as $company) {
            $company->isFollowedByMe = in_array($company->id, $followedIds);
        }

        return $companies;
    }
}
```

**Query SQL generada:**
```sql
-- Solo 1 query para TODOS los company IDs del usuario:
SELECT user_id, company_id
FROM business.user_company_followers
WHERE user_id IN ('user-uuid')
```

---

### 2. FollowersCountByCompanyIdBatchLoader

**Propósito:** Obtener conteo de followers por empresa.

**Ubicación:** `app/Features/CompanyManagement/GraphQL/DataLoaders/FollowersCountByCompanyIdBatchLoader.php`

**Uso en Field Resolver:**
```php
use App\Features\CompanyManagement\GraphQL\DataLoaders\FollowersCountByCompanyIdBatchLoader;

class CompanyFieldResolvers
{
    public function followersCount($company): int
    {
        $loader = app(FollowersCountByCompanyIdBatchLoader::class);
        return $loader->load($company->id); // Returns: int
    }
}
```

**Registrar en Schema:**
```graphql
type Company {
    followersCount: Int!
        @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@followersCount")
}
```

**Query SQL generada (batch):**
```sql
-- Solo 1 query con GROUP BY para TODAS las empresas:
SELECT company_id, COUNT(*) as count
FROM business.user_company_followers
WHERE company_id IN ('comp1', 'comp2', 'comp3', ...)
GROUP BY company_id
```

---

### 3. CompanyStatsBatchLoader

**Propósito:** Obtener estadísticas de agentes y usuarios por empresa.

**Ubicación:** `app/Features/CompanyManagement/GraphQL/DataLoaders/CompanyStatsBatchLoader.php`

**Uso en Field Resolver:**
```php
use App\Features\CompanyManagement\GraphQL\DataLoaders\CompanyStatsBatchLoader;

class CompanyFieldResolvers
{
    public function activeAgentsCount($company): int
    {
        $loader = app(CompanyStatsBatchLoader::class);
        $stats = $loader->load($company->id);
        // Returns: ['active_agents_count' => int, 'total_users_count' => int]

        return $stats['active_agents_count'];
    }

    public function totalUsersCount($company): int
    {
        $loader = app(CompanyStatsBatchLoader::class);
        $stats = $loader->load($company->id);

        return $stats['total_users_count'];
    }
}
```

**Registrar en Schema:**
```graphql
type Company {
    activeAgentsCount: Int!
        @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@activeAgentsCount")

    totalUsersCount: Int!
        @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@totalUsersCount")
}
```

**Queries SQL generadas (batch - 2 queries):**
```sql
-- Query 1: Conteo de agentes activos
SELECT company_id, COUNT(*) as count
FROM auth.user_company_roles
WHERE company_id IN ('comp1', 'comp2', ...)
  AND role_code = 'agent'
  AND is_active = true
GROUP BY company_id;

-- Query 2: Conteo total de usuarios
SELECT company_id, COUNT(DISTINCT user_id) as count
FROM auth.user_company_roles
WHERE company_id IN ('comp1', 'comp2', ...)
  AND is_active = true
GROUP BY company_id;
```

---

### 4. UserByIdLoader (Shared)

**Propósito:** Cargar usuarios por ID con profiles eager loaded.

**Ubicación:** `app/Shared/GraphQL/DataLoaders/UserByIdLoader.php`

**Uso en Field Resolver:**
```php
use App\Shared\GraphQL\DataLoaders\UserByIdLoader;

class CompanyFieldResolvers
{
    public function adminName($company): string
    {
        $loader = app(UserByIdLoader::class);
        $admin = $loader->load($company->admin_user_id);
        // Returns: User model with profile eager loaded

        if (!$admin) {
            return 'Unknown';
        }

        $profile = $admin->profile;
        if (!$profile) {
            return $admin->email;
        }

        return $profile->first_name . ' ' . $profile->last_name;
    }

    public function adminEmail($company): string
    {
        $loader = app(UserByIdLoader::class);
        $admin = $loader->load($company->admin_user_id);

        return $admin?->email ?? 'unknown@example.com';
    }
}
```

**Registrar en Schema:**
```graphql
type Company {
    adminName: String!
        @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@adminName")

    adminEmail: Email!
        @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@adminEmail")
}
```

**Queries SQL generadas (batch - 2 queries):**
```sql
-- Query 1: Cargar usuarios
SELECT * FROM auth.users
WHERE id IN ('user1', 'user2', 'user3', ...);

-- Query 2: Cargar profiles (eager loaded)
SELECT * FROM auth.user_profiles
WHERE user_id IN ('user1', 'user2', 'user3', ...);
```

---

### 5. CompanyByIdBatchLoader (Shared)

**Propósito:** Cargar empresas por ID.

**Ubicación:** `app/Shared/GraphQL/DataLoaders/CompanyByIdBatchLoader.php`

**Uso en Field Resolver:**
```php
use App\Shared\GraphQL\DataLoaders\CompanyByIdBatchLoader;

class CompanyRequestFieldResolvers
{
    public function createdCompany($companyRequest)
    {
        if (!$companyRequest->created_company_id) {
            return null;
        }

        $loader = app(CompanyByIdBatchLoader::class);
        return $loader->load($companyRequest->created_company_id);
        // Returns: Company model
    }
}
```

**Registrar en Schema (futuro):**
```graphql
type CompanyRequest {
    createdCompany: Company
        @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyRequestFieldResolvers@createdCompany")
}
```

**Query SQL generada (batch):**
```sql
SELECT * FROM business.companies
WHERE id IN ('comp1', 'comp2', 'comp3', ...);
```

---

## CUÁNDO USAR DATALOADERS

### ✅ USAR DataLoader cuando:

1. **Listas de modelos** que acceden a relaciones:
   ```php
   // BAD (N+1):
   foreach ($companies as $company) {
       echo $company->admin->name; // Query por cada empresa
   }

   // GOOD (DataLoader):
   $loader = app(UserByIdLoader::class);
   foreach ($companies as $company) {
       $admin = $loader->load($company->admin_user_id);
       echo $admin->name; // 1 query para todas
   }
   ```

2. **Campos calculados** en GraphQL types:
   ```graphql
   type Company {
       # BAD (getter que ejecuta query):
       followersCount: Int!

       # GOOD (field resolver con DataLoader):
       followersCount: Int!
           @field(resolver: "...CompanyFieldResolvers@followersCount")
   }
   ```

3. **Verificaciones EXISTS** en loops:
   ```php
   // BAD (N+1):
   foreach ($companies as $company) {
       $isFollowing = CompanyFollower::where('user_id', $user->id)
           ->where('company_id', $company->id)
           ->exists(); // Query por cada empresa
   }

   // GOOD (DataLoader):
   $loader = app(FollowedCompanyIdsByUserIdBatchLoader::class);
   $followedIds = $loader->load($user->id); // 1 query
   foreach ($companies as $company) {
       $isFollowing = in_array($company->id, $followedIds);
   }
   ```

4. **Conteos agregados** (COUNT, SUM, etc.):
   ```php
   // BAD (N+1):
   foreach ($companies as $company) {
       $count = $company->followers()->count(); // Query por cada empresa
   }

   // GOOD (DataLoader con GROUP BY):
   $loader = app(FollowersCountByCompanyIdBatchLoader::class);
   foreach ($companies as $company) {
       $count = $loader->load($company->id); // 1 query con GROUP BY
   }
   ```

---

### ❌ NO USAR DataLoader cuando:

1. **Query individual simple** (no hay lista):
   ```php
   // Innecesario (no hay riesgo de N+1):
   $company = Company::find($id);
   $admin = $company->admin; // Solo 1 query adicional
   ```

2. **Eager loading ya resuelve el problema**:
   ```php
   // Ya optimizado sin DataLoader:
   $companies = Company::with('admin.profile')->get();
   foreach ($companies as $company) {
       echo $company->admin->name; // Ya está cargado
   }
   ```

3. **Datos ya en memoria**:
   ```php
   // Innecesario:
   $company->name; // Ya está en el modelo
   ```

---

## DEBUGGING: VERIFICAR N+1

### Habilitar Query Logging en Resolver (temporal):

```php
use Illuminate\Support\Facades\DB;

public function __invoke($root, array $args)
{
    DB::enableQueryLog();

    // Tu código del resolver aquí...
    $companies = Company::paginate(20);
    // ...

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Ver queries ejecutadas (solo en desarrollo)
    if (app()->environment('local')) {
        logger()->info('Queries ejecutadas', [
            'count' => count($queries),
            'queries' => $queries,
        ]);
    }

    return $companies;
}
```

### Interpretar Resultados:

**SIN DataLoaders (N+1 presente):**
```
Queries ejecutadas: 121
- SELECT * FROM companies (1 query)
- SELECT * FROM user_company_followers WHERE user_id=... AND company_id=... (20 queries)
- SELECT COUNT(*) FROM user_company_followers WHERE company_id=... (20 queries)
- SELECT * FROM users WHERE id=... (20 queries)
- SELECT * FROM user_profiles WHERE user_id=... (20 queries)
- ... etc
```

**CON DataLoaders (optimizado):**
```
Queries ejecutadas: 7
- SELECT * FROM companies (1 query)
- SELECT user_id, company_id FROM user_company_followers WHERE user_id IN (...) (1 query)
- SELECT company_id, COUNT(*) FROM user_company_followers WHERE company_id IN (...) GROUP BY company_id (1 query)
- SELECT * FROM users WHERE id IN (...) (1 query)
- SELECT * FROM user_profiles WHERE user_id IN (...) (1 query)
- SELECT company_id, COUNT(*) FROM user_company_roles ... GROUP BY company_id (2 queries)
```

---

## EJEMPLO COMPLETO: Query GraphQL Optimizada

### GraphQL Query:
```graphql
query OptimizedCompaniesQuery {
  companies(context: EXPLORE, first: 20) {
    ... on CompanyExploreList {
      items {
        id
        name
        logoUrl
        followersCount
        isFollowedByMe
      }
      totalCount
      hasNextPage
    }
  }
}
```

### Resolver (CompaniesQuery.php):
```php
public function __invoke($root, array $args)
{
    $companies = Company::select(['id', 'company_code', 'name', 'logo_url'])
        ->paginate(20);

    if (auth()->check()) {
        $user = auth()->user();

        // DataLoader para isFollowedByMe (1 query)
        $loader = app(FollowedCompanyIdsByUserIdBatchLoader::class);
        $followedIds = $loader->load($user->id);

        foreach ($companies as $company) {
            $company->isFollowedByMe = in_array($company->id, $followedIds);
        }
    }

    return [
        '__typename' => 'CompanyExploreList',
        'items' => $companies,
        'totalCount' => $companies->total(),
        'hasNextPage' => $companies->hasMorePages(),
    ];
}
```

### Field Resolver (CompanyFieldResolvers.php):
```php
public function followersCount($company): int
{
    // DataLoader para followersCount (1 query con GROUP BY)
    $loader = app(FollowersCountByCompanyIdBatchLoader::class);
    return $loader->load($company->id);
}
```

### Schema GraphQL:
```graphql
type CompanyForFollowing {
    id: UUID!
    name: String!
    logoUrl: URL

    followersCount: Int!
        @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@followersCount")

    isFollowedByMe: Boolean!
}
```

### Resultado: Solo 3 queries para 20 empresas
1. Query principal (companies)
2. Query batch (followed IDs)
3. Query batch (followers count con GROUP BY)

**Vs. 42 queries sin DataLoaders (21x menos queries)** 🚀

---

## TESTING DATALOADERS

### Test básico de funcionalidad:
```php
/** @test */
public function it_loads_data_for_single_item()
{
    $company = Company::factory()->create();
    // Setup data...

    $loader = app(FollowersCountByCompanyIdBatchLoader::class);
    $count = $loader->load($company->id);

    $this->assertEquals(5, $count);
}
```

### Test crítico de batch loading:
```php
/** @test */
public function it_prevents_n_plus_1_in_list()
{
    $companies = Company::factory()->count(20)->create();
    // Setup data...

    DB::enableQueryLog();

    $loader = app(FollowersCountByCompanyIdBatchLoader::class);
    $counts = [];
    foreach ($companies as $company) {
        $counts[$company->id] = $loader->load($company->id);
    }

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Solo 1 query con GROUP BY, no 20 queries individuales
    $countQueries = collect($queries)->filter(function($query) {
        return strpos($query['query'], 'user_company_followers') !== false;
    });

    $this->assertCount(1, $countQueries, 'Should execute only 1 query, not 20 (N+1 prevented)');
}
```

---

## RECURSOS ADICIONALES

- **Reporte completo:** `documentacion/COMPANY_MANAGEMENT_N+1_OPTIMIZATION_REPORT.md`
- **Patrón DataLoader:** `app/Shared/GraphQL/DataLoaders/CompanyByIdBatchLoader.php` (referencia)
- **Tests de ejemplo:** `tests/Feature/CompanyManagement/DataLoaders/`
- **Lighthouse Docs:** https://lighthouse-php.com/master/performance/n-plus-one.html

---

**Autor:** Claude (Agente de Optimización N+1)
**Fecha:** 2025-10-19
**Versión:** 1.0
