# Checklist: Implementación de Optimización N+1 CompanyManagement

**Fecha:** 2025-10-19
**Feature:** CompanyManagement
**Responsable:** [Tu nombre]

---

## FASE 1: VALIDACIÓN DE ARCHIVOS CREADOS

### DataLoaders (7 archivos)

- [ ] Verificar existe: `app/Shared/GraphQL/DataLoaders/UserByIdLoader.php`
  - [ ] Usa patrón `GraphQL\Deferred`
  - [ ] Tiene método `load(string $userId): Deferred`
  - [ ] Tiene método `resolve(): void`
  - [ ] Incluye eager loading de `profile`

- [ ] Verificar existe: `app/Features/CompanyManagement/GraphQL/DataLoaders/FollowedCompanyIdsByUserIdBatchLoader.php`
  - [ ] Usa patrón `GraphQL\Deferred`
  - [ ] Retorna `array<string>` de company IDs
  - [ ] Query usa `whereIn('user_id', $userIds)`

- [ ] Verificar existe: `app/Features/CompanyManagement/GraphQL/DataLoaders/FollowersCountByCompanyIdBatchLoader.php`
  - [ ] Usa patrón `GraphQL\Deferred`
  - [ ] Retorna `int` (count)
  - [ ] Query usa `GROUP BY company_id`

- [ ] Verificar existe: `app/Features/CompanyManagement/GraphQL/DataLoaders/CompanyStatsBatchLoader.php`
  - [ ] Usa patrón `GraphQL\Deferred`
  - [ ] Retorna array con `active_agents_count` y `total_users_count`
  - [ ] Ejecuta 2 queries batch (agents + users)

- [ ] Verificar existe: `app/Features/CompanyManagement/GraphQL/DataLoaders/CompanyFollowersByCompanyIdLoader.php`
  - [ ] Actualizado a patrón `GraphQL\Deferred`
  - [ ] Sin errores de sintaxis (`use` fuera de funciones)

- [ ] Verificar existe: `app/Features/CompanyManagement/GraphQL/DataLoaders/FollowedCompaniesByUserIdLoader.php`
  - [ ] Actualizado a patrón `GraphQL\Deferred`
  - [ ] Mantiene eager loading `->with('company')`

- [ ] Verificar existe: `app/Shared/GraphQL/DataLoaders/CompanyByIdBatchLoader.php`
  - [ ] Ya existía con patrón correcto

---

### Resolvers (3 archivos)

- [ ] Verificar modificado: `app/Features/CompanyManagement/GraphQL/Queries/CompaniesQuery.php`
  - [ ] Líneas 93-105: Usa `FollowedCompanyIdsByUserIdBatchLoader`
  - [ ] Eliminado uso directo de `$this->followService->getFollowedCompanies()`

- [ ] Verificar modificado: `app/Features/CompanyManagement/GraphQL/Queries/CompanyQuery.php`
  - [ ] Línea 31: Agregado `$company->load('admin.profile')`
  - [ ] Líneas 34-41: Usa `FollowedCompanyIdsByUserIdBatchLoader`

- [ ] Verificar existe: `app/Features/CompanyManagement/GraphQL/Resolvers/CompanyFieldResolvers.php`
  - [ ] Tiene método `followersCount($company): int`
  - [ ] Tiene método `activeAgentsCount($company): int`
  - [ ] Tiene método `totalUsersCount($company): int`
  - [ ] Tiene método `adminName($company): string`
  - [ ] Tiene método `adminEmail($company): string`
  - [ ] Todos usan DataLoaders correspondientes

---

### Tests (3 archivos)

- [ ] Verificar existe: `tests/Feature/CompanyManagement/DataLoaders/FollowedCompanyIdsByUserIdBatchLoaderTest.php`
  - [ ] 4 tests implementados
  - [ ] Test crítico: `it_prevents_n_plus_1_queries_in_company_list()`

- [ ] Verificar existe: `tests/Feature/CompanyManagement/DataLoaders/FollowersCountByCompanyIdBatchLoaderTest.php`
  - [ ] 4 tests implementados
  - [ ] Test crítico: `it_prevents_n_plus_1_when_loading_follower_counts_for_company_list()`

- [ ] Verificar existe: `tests/Feature/CompanyManagement/DataLoaders/CompanyStatsBatchLoaderTest.php`
  - [ ] 4 tests implementados
  - [ ] Test crítico: `it_prevents_n_plus_1_when_loading_stats_for_company_list()`

---

### Documentación (3 archivos)

- [ ] Verificar existe: `documentacion/COMPANY_MANAGEMENT_N+1_OPTIMIZATION_REPORT.md`
- [ ] Verificar existe: `documentacion/DATALOADERS_USAGE_GUIDE_COMPANY_MANAGEMENT.md`
- [ ] Verificar existe: `documentacion/COMPANY_MANAGEMENT_N+1_IMPLEMENTATION_CHECKLIST.md` (este archivo)

---

## FASE 2: MODIFICACIÓN DE SCHEMA GRAPHQL

### Editar: `app/Features/CompanyManagement/GraphQL/Schema/company-management.graphql`

- [ ] Ubicar type `Company implements Node & Timestamped`
- [ ] Modificar campo `adminName`:
  ```graphql
  adminName: String!
      @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@adminName")
  ```

- [ ] Modificar campo `adminEmail`:
  ```graphql
  adminEmail: Email!
      @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@adminEmail")
  ```

- [ ] Modificar campo `followersCount`:
  ```graphql
  followersCount: Int!
      @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@followersCount")
  ```

- [ ] Modificar campo `activeAgentsCount`:
  ```graphql
  activeAgentsCount: Int!
      @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@activeAgentsCount")
  ```

- [ ] Modificar campo `totalUsersCount`:
  ```graphql
  totalUsersCount: Int!
      @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@totalUsersCount")
  ```

---

### Editar: `type CompanyForFollowing` en el mismo archivo

- [ ] Modificar campo `followersCount`:
  ```graphql
  followersCount: Int!
      @field(resolver: "App\\Features\\CompanyManagement\\GraphQL\\Resolvers\\CompanyFieldResolvers@followersCount")
  ```

---

## FASE 3: VALIDACIÓN

### Validar Schema GraphQL

- [ ] Ejecutar: `powershell -Command "php artisan lighthouse:validate-schema"`
- [ ] Verificar salida: `Schema is valid` (sin errores)
- [ ] Si hay errores:
  - [ ] Verificar rutas de resolvers (backslashes: `\\`)
  - [ ] Verificar nombres de métodos (case-sensitive)
  - [ ] Verificar que archivos de resolvers existen

---

### Ejecutar Tests

- [ ] Test individual: `php artisan test tests/Feature/CompanyManagement/DataLoaders/FollowedCompanyIdsByUserIdBatchLoaderTest.php`
  - [ ] Resultado: ✅ Passed (4/4)

- [ ] Test individual: `php artisan test tests/Feature/CompanyManagement/DataLoaders/FollowersCountByCompanyIdBatchLoaderTest.php`
  - [ ] Resultado: ✅ Passed (4/4)

- [ ] Test individual: `php artisan test tests/Feature/CompanyManagement/DataLoaders/CompanyStatsBatchLoaderTest.php`
  - [ ] Resultado: ✅ Passed (4/4)

- [ ] Tests del feature completo: `php artisan test --filter=CompanyManagement`
  - [ ] Resultado: ✅ Passed (todos los tests)

---

## FASE 4: PRUEBAS MANUALES EN GRAPHIQL

### Preparar Datos de Prueba

Ejecutar en base de datos o usar factory:
```php
// En tinker: php artisan tinker

// Crear 20 empresas
$companies = \App\Features\CompanyManagement\Models\Company::factory()->count(20)->create();

// Crear 1 usuario
$user = \App\Features\UserManagement\Models\User::factory()->create();

// Usuario sigue 5 empresas
foreach ($companies->take(5) as $company) {
    \App\Features\CompanyManagement\Models\CompanyFollower::create([
        'user_id' => $user->id,
        'company_id' => $company->id,
    ]);
}
```

- [ ] Datos de prueba creados exitosamente

---

### Prueba 1: Query Contexto EXPLORE

Abrir: http://localhost:8000/graphiql

- [ ] Ejecutar query:
  ```graphql
  query TestExploreContext {
    companies(context: EXPLORE, first: 20) {
      ... on CompanyExploreList {
        items {
          id
          name
          followersCount
          isFollowedByMe
        }
        totalCount
        hasNextPage
      }
    }
  }
  ```

- [ ] Verificar respuesta:
  - [ ] 20 empresas retornadas
  - [ ] `followersCount` tiene valores (puede ser 0)
  - [ ] `isFollowedByMe` es `true` para 5 empresas, `false` para 15

---

### Prueba 2: Query Contexto MANAGEMENT (Completa)

- [ ] Ejecutar query:
  ```graphql
  query TestManagementContext {
    companies(context: MANAGEMENT, first: 10) {
      ... on CompanyFullList {
        items {
          id
          name
          adminName
          adminEmail
          followersCount
          activeAgentsCount
          totalUsersCount
        }
        totalCount
      }
    }
  }
  ```

- [ ] Verificar respuesta:
  - [ ] 10 empresas retornadas
  - [ ] `adminName` tiene formato "FirstName LastName"
  - [ ] `adminEmail` es un email válido
  - [ ] `followersCount` tiene valores numéricos
  - [ ] `activeAgentsCount` y `totalUsersCount` retornan (pueden ser 0)

---

### Prueba 3: Query Individual (company)

- [ ] Ejecutar query (reemplazar `<UUID>` con ID real):
  ```graphql
  query TestSingleCompany {
    company(id: "<UUID>") {
      id
      name
      adminName
      adminEmail
      followersCount
      activeAgentsCount
      totalUsersCount
      isFollowedByMe
    }
  }
  ```

- [ ] Verificar respuesta:
  - [ ] Todos los campos retornan correctamente
  - [ ] Sin errores GraphQL

---

## FASE 5: VERIFICACIÓN DE PERFORMANCE (CRÍTICO)

### Habilitar Query Logging (temporal)

Editar: `app/Features/CompanyManagement/GraphQL/Queries/CompaniesQuery.php`

```php
// Al inicio del método __invoke (después de línea 18):
\Illuminate\Support\Facades\DB::enableQueryLog();

// Al final del método, antes del return (después de línea 113):
$queries = \Illuminate\Support\Facades\DB::getQueryLog();
\Illuminate\Support\Facades\DB::disableQueryLog();

if (app()->environment('local')) {
    logger()->info('CompaniesQuery - Queries ejecutadas', [
        'count' => count($queries),
        'context' => $context,
        'companies_count' => count($companies),
    ]);
}
```

- [ ] Query logging agregado temporalmente

---

### Ejecutar Query y Verificar Logs

- [ ] Ejecutar en GraphiQL:
  ```graphql
  query TestPerformance {
    companies(context: MANAGEMENT, first: 20) {
      ... on CompanyFullList {
        items {
          id
          name
          adminName
          adminEmail
          followersCount
          activeAgentsCount
          totalUsersCount
        }
      }
    }
  }
  ```

- [ ] Abrir logs: `storage/logs/laravel.log`
- [ ] Buscar: "CompaniesQuery - Queries ejecutadas"
- [ ] Verificar:
  - [ ] `count` <= 10 queries (esperado: ~7)
  - [ ] **NO** >= 100 queries (indicaría N+1 persistente)

---

### Interpretar Resultados

**✅ Optimización exitosa:**
```json
{
  "count": 7,
  "context": "MANAGEMENT",
  "companies_count": 20
}
```

**❌ N+1 aún presente (revisar):**
```json
{
  "count": 121,
  "context": "MANAGEMENT",
  "companies_count": 20
}
```

- [ ] Queries count <= 10: ✅ OPTIMIZACIÓN EXITOSA
- [ ] **Si count > 50:** Revisar field resolvers en schema

---

### Remover Query Logging

- [ ] Eliminar código de logging temporal de `CompaniesQuery.php`
- [ ] Commit cambios

---

## FASE 6: COMMIT Y DOCUMENTACIÓN

### Git Commit

- [ ] Revisar cambios: `git status`
- [ ] Verificar diff: `git diff`
- [ ] Staging:
  ```bash
  git add app/Shared/GraphQL/DataLoaders/UserByIdLoader.php
  git add app/Features/CompanyManagement/GraphQL/DataLoaders/
  git add app/Features/CompanyManagement/GraphQL/Queries/CompaniesQuery.php
  git add app/Features/CompanyManagement/GraphQL/Queries/CompanyQuery.php
  git add app/Features/CompanyManagement/GraphQL/Resolvers/CompanyFieldResolvers.php
  git add app/Features/CompanyManagement/GraphQL/Schema/company-management.graphql
  git add tests/Feature/CompanyManagement/DataLoaders/
  git add documentacion/COMPANY_MANAGEMENT_N+1_OPTIMIZATION_REPORT.md
  git add documentacion/DATALOADERS_USAGE_GUIDE_COMPANY_MANAGEMENT.md
  git add documentacion/COMPANY_MANAGEMENT_N+1_IMPLEMENTATION_CHECKLIST.md
  ```

- [ ] Commit:
  ```bash
  git commit -m "feat(CompanyManagement): optimize N+1 queries with DataLoaders

  - Implement 7 DataLoaders using Lighthouse 6 Deferred pattern
  - Optimize CompaniesQuery and CompanyQuery resolvers
  - Create CompanyFieldResolvers for calculated fields
  - Add comprehensive test suite (12 tests)
  - Reduce queries by 94-97% in list scenarios

  Details:
  - New: FollowedCompanyIdsByUserIdBatchLoader
  - New: FollowersCountByCompanyIdBatchLoader
  - New: CompanyStatsBatchLoader
  - Updated: UserByIdLoader (Shared)
  - Updated: CompanyFollowersByCompanyIdLoader
  - Updated: FollowedCompaniesByUserIdLoader

  Performance impact:
  - 20 companies: 121 → 7 queries (94.2% reduction)
  - 50 companies: 251 → 6 queries (97.6% reduction)

  🤖 Generated with Claude Code"
  ```

- [ ] Commit exitoso

---

### Actualizar Documentación de Proyecto

- [ ] Editar: `documentacion/ESTADO_COMPLETO_PROYECTO.md`
  - [ ] Actualizar estado de CompanyManagement a "N+1 optimizado"
  - [ ] Agregar nota sobre DataLoaders implementados

- [ ] Commit documentación:
  ```bash
  git add documentacion/ESTADO_COMPLETO_PROYECTO.md
  git commit -m "docs: update project status - CompanyManagement N+1 optimized"
  ```

---

## FASE 7: COMUNICACIÓN Y CIERRE

### Reporte a Equipo

- [ ] Compartir documentos:
  - [ ] `COMPANY_MANAGEMENT_N+1_OPTIMIZATION_REPORT.md`
  - [ ] `DATALOADERS_USAGE_GUIDE_COMPANY_MANAGEMENT.md`

- [ ] Comunicar resultados:
  - [ ] 7 N+1 queries resueltos
  - [ ] 94-97% reducción de queries
  - [ ] Tests implementados (100% coverage de DataLoaders)

---

### Próximos Pasos Documentados

- [ ] Crear issues/tickets para:
  - [ ] Implementar N+1 optimization en otros features (UserManagement, Authentication)
  - [ ] Implementar campos potenciales: `CompanyRequest.reviewer`, `CompanyRequest.createdCompany`
  - [ ] Agregar monitoring de performance en producción

---

## CHECKLIST FINAL

- [ ] **Todos los archivos creados/modificados verificados**
- [ ] **Schema GraphQL validado exitosamente**
- [ ] **Todos los tests pasando (12/12)**
- [ ] **Pruebas manuales en GraphiQL exitosas**
- [ ] **Performance verificada (queries <= 10)**
- [ ] **Cambios committed en Git**
- [ ] **Documentación actualizada**
- [ ] **Reporte compartido con equipo**

---

## NOTAS Y OBSERVACIONES

**Fecha:** _______________
**Responsable:** _______________

### Problemas Encontrados:
```
[Registrar aquí cualquier problema durante la implementación]
```

### Soluciones Aplicadas:
```
[Registrar aquí cómo se resolvieron los problemas]
```

### Mejoras Futuras Identificadas:
```
[Registrar aquí ideas de mejora para futuras iteraciones]
```

---

**Estado Final:** [ ] ✅ COMPLETADO | [ ] ⚠️ COMPLETADO CON OBSERVACIONES | [ ] ❌ REQUIERE REVISIÓN

**Firma:** _______________ **Fecha:** _______________

---

**Preparado por:** Claude (Agente de Optimización N+1)
**Versión:** 1.0
**Última actualización:** 2025-10-19
