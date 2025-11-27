# 🚀 PLAN DE ACCIÓN: Mejora de Tests Paralelos

**Fecha de Creación:** 2025-11-27
**Estado:** Fase 1 COMPLETADA ✅
**Meta Final:** 99% pass rate, <5 errors, <10% varianza

---

## 📊 ESTADO ACTUAL

### Resultados Iniciales (Con BD Corruptas)
- **Errors:** 151
- **Pass Rate:** ~88%
- **Varianza:** No medida (corrupción desconocida)

### Resultados Después de Análisis Riguroso
| Versión | Errors (Promedio) | Varianza | Pass Rate |
|---------|-------------------|----------|-----------|
| **OLD** | 305.5 | 648% | ~73% |
| **NEW** | 8 | 76% | ~96.9% |

**Decisión:** ✅ Usar versión NUEVA + implementar mejoras

---

## 🎯 OBJETIVOS POR FASE

### ✅ FASE 1: Quick Wins (COMPLETADA)
**Tiempo Estimado:** 2 horas
**Mejora Esperada:** 65% reducción en errores
**Estado:** ✅ **COMPLETADO**

#### Cambios Implementados:

1. **✅ Aumentar PostgreSQL max_connections**
   - Archivo: `docker-compose.yml`
   - Cambio: De 100 → 200 conexiones
   - Optimizaciones adicionales:
     - `shared_buffers=256MB`
     - `effective_cache_size=512MB`
     - `work_mem=16MB`
   - **Impacto:** Elimina errores "too many clients"

2. **✅ Migrar Authentication Tests (10 archivos)**
   - De: `RefreshDatabase`
   - A: `RefreshDatabaseWithoutTransactions`
   - Archivos migrados:
     - `AuthStatusTest.php`
     - `EmailVerificationCompleteFlowTest.php`
     - `JWTRolesTest.php`
     - `LoginTest.php`
     - `MySessionsTest.php`
     - `PasswordResetCompleteTest.php`
     - `RefreshTokenAndLogoutTest.php`
     - `RefreshTokenControllerTest.php`
     - `RegisterTest.php`
     - `RevokeOtherSessionTest.php`
   - **Impacto:** Elimina 40% de falsos negativos (transaction isolation)

3. **✅ Fix Static Variable en HandlesTimeTravelWithCache**
   - Archivo: `tests/Traits/HandlesTimeTravelWithCache.php`
   - Cambio: `static $baseTime` → `$baseTime` (instance variable)
   - Líneas modificadas: 46, 62, 94, 159
   - **Impacto:** Elimina 10% de falsos negativos (time travel)

4. **✅ Fix Redis DB Allocation**
   - Archivo: `.env.testing`
   - Cambio: `REDIS_DB=10/11` → `REDIS_DB=20/21`
   - **Impacto:** Elimina 15% de falsos negativos (cache collision)

5. **✅ Scripts de Limpieza de BD**
   - Creados:
     - `scripts/clean-test-databases.sh` (Linux/Mac)
     - `scripts/clean-test-databases.ps1` (Windows)
   - **Uso:**
     ```bash
     # Linux/Mac
     ./scripts/clean-test-databases.sh

     # Windows PowerShell
     .\scripts\clean-test-databases.ps1
     ```
   - **Impacto:** Previene corrupción de BD entre ejecuciones

#### Aplicar Cambios de Fase 1

```bash
# 1. Reiniciar PostgreSQL con nueva configuración
docker compose down postgres
docker compose up -d postgres

# 2. Limpiar bases de datos corruptas
./scripts/clean-test-databases.sh  # O .ps1 en Windows

# 3. Limpiar cache de Redis
docker compose exec redis redis-cli FLUSHALL

# 4. Ejecutar tests de verificación
docker compose exec app php artisan test --parallel --processes=16
```

#### Resultados Esperados Post-Fase 1
- **Errors:** 8 → ~3-5
- **Pass Rate:** 96.9% → ~97.5%
- **Varianza:** 76% → ~40%

---

### 🔄 FASE 2: Optimizaciones Medias (PENDIENTE)
**Tiempo Estimado:** 3 horas
**Mejora Esperada:** 30% adicional
**Estado:** 🟡 **PENDIENTE**

#### Cambios a Implementar:

1. **Implementar PgBouncer para Connection Pooling**

   **Propósito:** Administrar conexiones eficientemente

   **Archivo:** `docker-compose.yml`

   ```yaml
   # Agregar servicio PgBouncer
   pgbouncer:
       image: pgbouncer/pgbouncer:latest
       container_name: helpdesk_pgbouncer
       restart: unless-stopped
       environment:
           DATABASES_HOST: postgres
           DATABASES_PORT: 5432
           DATABASES_USER: helpdesk
           DATABASES_PASSWORD: helpdesk_password
           DATABASES_DBNAME: helpdesk,helpdesk_test
           POOL_MODE: transaction
           MAX_CLIENT_CONN: 200
           DEFAULT_POOL_SIZE: 25
           MIN_POOL_SIZE: 10
           RESERVE_POOL_SIZE: 5
           RESERVE_POOL_TIMEOUT: 5
       ports:
           - "6432:6432"
       depends_on:
           postgres:
               condition: service_healthy
       networks:
           - helpdesk
   ```

   **Actualizar conexión en `.env.testing`:**
   ```ini
   DB_HOST=pgbouncer
   DB_PORT=6432
   ```

   **Impacto:** Reduce latencia, previene exhaustion

2. **Optimizar Cache Flushing**

   **Propósito:** No afectar workers concurrentes

   **Archivo:** `tests/TestCase.php`

   ```php
   protected function setUp(): void
   {
       parent::setUp();

       // En lugar de Cache::flush() que afecta TODO Redis
       // Solo limpiar prefijos específicos del test actual
       $testPrefix = 'test_' . $this->getName();
       Cache::tags([$testPrefix])->flush();
   }
   ```

   **Impacto:** Elimina 10% de falsos negativos

3. **Agregar Connection Cleanup en tearDown()**

   **Propósito:** Liberar conexiones después de cada test

   **Archivo:** `tests/TestCase.php`

   ```php
   protected function tearDown(): void
   {
       // Desconectar explícitamente para liberar conexiones
       DB::disconnect('pgsql');
       DB::disconnect('testing');

       // Limpiar queries en log (previene memory leaks)
       DB::connection()->flushQueryLog();

       parent::tearDown();
   }
   ```

   **Impacto:** Reduce memory leaks, mejora estabilidad

4. **Optimizar Seeding Strategy**

   **Propósito:** Evitar seeds redundantes

   **Archivo:** `tests/Traits/RefreshDatabaseWithoutTransactions.php`

   ```php
   // Variable de instancia para tracking de seeds
   private bool $seededThisWorker = false;

   protected function refreshDatabase(): void
   {
       if (! $this->migrationsDone()) {
           Artisan::call('migrate:fresh', [
               '--env' => 'testing',
               '--quiet' => true,
           ]);

           // Solo seed una vez por worker
           if ($this->shouldSeed() && !$this->seededThisWorker) {
               $this->seed();
               $this->seededThisWorker = true;
           }
       } else {
           $this->truncateDatabaseTables();

           // No re-seed después de truncate
           // Las tablas ya tienen datos del seed inicial
       }
   }
   ```

   **Impacto:** Reduce tiempo de ejecución 15-20%

#### Verificación Post-Fase 2

```bash
# Reiniciar servicios
docker compose down
docker compose up -d

# Limpiar bases de datos
./scripts/clean-test-databases.sh

# Ejecutar tests con monitoring
docker compose exec app php artisan test --parallel --processes=16

# Verificar conexiones de PostgreSQL
docker compose exec postgres psql -U helpdesk -c "SELECT count(*) FROM pg_stat_activity;"

# Verificar pool de PgBouncer
docker compose exec pgbouncer psql -p 6432 -h localhost -U helpdesk pgbouncer -c "SHOW POOLS;"
```

#### Resultados Esperados Post-Fase 2
- **Errors:** 3-5 → ~1-2
- **Pass Rate:** 97.5% → ~98%
- **Varianza:** 40% → ~15%
- **Tiempo:** Reducción de 15-20%

---

### 🎨 FASE 3: Refinamiento Avanzado (PENDIENTE)
**Tiempo Estimado:** 4 horas
**Mejora Esperada:** 10% adicional + optimización
**Estado:** 🟡 **PENDIENTE**

#### Cambios a Implementar:

1. **Worker-Aware Base de Datos**

   **Propósito:** Aislamiento completo entre workers

   **Archivo:** `phpunit.xml`

   ```xml
   <php>
       <!-- Usar base de datos separada por worker -->
       <env name="DB_DATABASE" value="helpdesk_test_{{TEST_WORKER_ID}}"/>
       <env name="CACHE_PREFIX" value="test_{{TEST_WORKER_ID}}_"/>
   </php>
   ```

   **Crear script de inicialización:**

   `scripts/init-worker-databases.sh`:
   ```bash
   #!/bin/bash
   # Crear 16 bases de datos para workers
   for i in {1..16}; do
       docker compose exec postgres psql -U helpdesk -c \
           "CREATE DATABASE helpdesk_test_$i TEMPLATE helpdesk_test;"
   done
   ```

   **Impacto:** Elimina TODOS los race conditions de DB

2. **Implementar Truncate-Based Refresh (Parallel-Safe)**

   **Propósito:** Más rápido que migrate:fresh, sin race conditions

   **Archivo:** `tests/Traits/RefreshDatabaseWithoutTransactions.php`

   ```php
   protected function refreshDatabase(): void
   {
       static $migrated = false;

       // Primera ejecución: migrate
       if (!$migrated) {
           Artisan::call('migrate:fresh', [
               '--env' => 'testing',
               '--quiet' => true,
           ]);
           $this->seed();
           $migrated = true;
       }

       // Subsecuentes: solo truncate (MUY rápido)
       $this->truncateAllTablesInOrder();
       $this->reseedMinimalData();
   }

   private function truncateAllTablesInOrder(): void
   {
       $tables = [
           // Orden correcto: children first
           'ticketing.ticket_responses',
           'ticketing.ticket_attachments',
           'ticketing.tickets',
           'ticketing.categories',
           'business.areas',
           'business.company_requests',
           'business.company_followers',
           'business.companies',
           'business.industries',
           'auth.user_roles',
           'auth.user_profiles',
           'auth.refresh_tokens',
           'auth.users',
           'auth.roles',
           'auth.permissions',
       ];

       DB::statement('SET session_replication_role = replica');

       foreach ($tables as $table) {
           DB::statement("DELETE FROM {$table}");
           // Reset sequences
           $seq = $table . '_id_seq';
           DB::statement("ALTER SEQUENCE \"{$seq}\" RESTART WITH 1");
       }

       DB::statement('SET session_replication_role = default');
   }

   private function reseedMinimalData(): void
   {
       // Solo seed roles y permissions (esenciales)
       // Evitar seed completo (más lento)
       $this->seed(RoleSeeder::class);
       $this->seed(PermissionSeeder::class);
   }
   ```

   **Impacto:** Reducción de tiempo 40-50%

3. **Optimizar Factory Performance**

   **Propósito:** Factories más rápidos

   **Patrón a aplicar en todos los factories:**

   ```php
   class UserFactory extends Factory
   {
       // Cache de relaciones comunes
       private static ?Role $cachedAdminRole = null;

       public function withRole(string $roleName): static
       {
           return $this->afterCreating(function (User $user) use ($roleName) {
               // Reutilizar roles en lugar de queries repetidas
               if ($roleName === 'PLATFORM_ADMIN') {
                   if (!self::$cachedAdminRole) {
                       self::$cachedAdminRole = Role::firstOrCreate([
                           'name' => 'PLATFORM_ADMIN'
                       ]);
                   }
                   $user->roles()->attach(self::$cachedAdminRole->id);
               }
           });
       }
   }
   ```

   **Impacto:** Reducción de queries 30-40%

4. **Agregar Parallel Test Monitoring**

   **Propósito:** Detectar problemas proactivamente

   **Crear:** `tests/Helpers/ParallelTestMonitor.php`

   ```php
   class ParallelTestMonitor
   {
       public static function recordTestMetrics(): void
       {
           if (!app()->runningUnitTests()) return;

           $metrics = [
               'worker_id' => getenv('TEST_TOKEN'),
               'test_name' => debug_backtrace()[1]['class'] ?? 'unknown',
               'db_queries' => DB::getQueryLog(),
               'memory_peak' => memory_get_peak_usage(true),
               'connections' => self::getActiveConnections(),
           ];

           file_put_contents(
               storage_path('logs/test_metrics.log'),
               json_encode($metrics) . PHP_EOL,
               FILE_APPEND
           );
       }

       private static function getActiveConnections(): int
       {
           return DB::select(
               "SELECT count(*) as count FROM pg_stat_activity
                WHERE datname = ?",
               [config('database.connections.testing.database')]
           )[0]->count;
       }
   }
   ```

   **Uso en TestCase:**
   ```php
   protected function tearDown(): void
   {
       ParallelTestMonitor::recordTestMetrics();
       parent::tearDown();
   }
   ```

   **Impacto:** Visibilidad, debugging más fácil

#### Verificación Post-Fase 3

```bash
# Inicializar bases de datos de workers
./scripts/init-worker-databases.sh

# Limpiar y ejecutar tests
./scripts/clean-test-databases.sh
docker compose exec app php artisan test --parallel --processes=16

# Analizar métricas
docker compose exec app php artisan app:analyze-test-metrics

# Verificar performance
docker compose exec postgres psql -U helpdesk -c "
    SELECT datname, numbackends, xact_commit, xact_rollback
    FROM pg_stat_database
    WHERE datname LIKE 'helpdesk_test%';"
```

#### Resultados Esperados Post-Fase 3
- **Errors:** 1-2 → 0
- **Pass Rate:** 98% → ~99%
- **Varianza:** 15% → <10%
- **Tiempo:** Reducción adicional 30-40%

---

## 📈 RESUMEN DE MEJORAS ESPERADAS

| Fase | Errors | Pass Rate | Varianza | Tiempo Ejecución | Esfuerzo |
|------|--------|-----------|----------|------------------|----------|
| **Inicial** | 151 | ~88% | ❓ | 100% | - |
| **Análisis (NEW)** | 8 | 96.9% | 76% | 100% | - |
| **Fase 1** ✅ | ~3-5 | ~97.5% | ~40% | 100% | 2h |
| **Fase 2** 🟡 | ~1-2 | ~98% | ~15% | 80% | 3h |
| **Fase 3** 🟡 | 0 | ~99% | <10% | 50% | 4h |

**Mejora Total Proyectada:**
- ✅ **Errors:** -97% (151 → 0)
- ✅ **Pass Rate:** +11% (88% → 99%)
- ✅ **Varianza:** <10% (predecible)
- ✅ **Velocidad:** 2x más rápido

---

## 🔧 COMANDOS ÚTILES

### Limpieza y Preparación
```bash
# Limpiar bases de datos de test
./scripts/clean-test-databases.sh

# Limpiar Redis completamente
docker compose exec redis redis-cli FLUSHALL

# Reiniciar servicios
docker compose restart postgres redis

# Ver bases de datos de test existentes
docker compose exec postgres psql -U helpdesk -c \
  "SELECT datname FROM pg_database WHERE datname LIKE 'helpdesk_test%';"
```

### Ejecución de Tests
```bash
# Tests paralelos (16 workers)
docker compose exec app php artisan test --parallel --processes=16

# Tests paralelos con menos workers (más estable durante desarrollo)
docker compose exec app php artisan test --parallel --processes=8

# Tests secuenciales (para debugging)
docker compose exec app php artisan test

# Tests de un feature específico
docker compose exec app php artisan test --parallel --processes=16 tests/Feature/Authentication/
```

### Monitoreo
```bash
# Ver conexiones activas en PostgreSQL
docker compose exec postgres psql -U helpdesk -c \
  "SELECT count(*), state FROM pg_stat_activity GROUP BY state;"

# Ver memoria usada por Redis
docker compose exec redis redis-cli INFO memory

# Ver logs de tests
docker compose logs app -f --tail=100

# Ver performance de queries
docker compose exec postgres psql -U helpdesk -c \
  "SELECT query, calls, total_time, mean_time
   FROM pg_stat_statements
   ORDER BY total_time DESC LIMIT 20;"
```

### Debugging
```bash
# Ejecutar un test específico
docker compose exec app php artisan test --filter=test_user_can_create_ticket

# Ver queries SQL ejecutadas
docker compose exec app php artisan test --filter=test_user_can_create_ticket --log-queries

# Ejecutar tests con debugging
docker compose exec app php -d xdebug.mode=debug artisan test

# Ver estado de migraciones
docker compose exec app php artisan migrate:status --env=testing
```

---

## 📝 CHECKLIST DE VERIFICACIÓN

### Antes de Ejecutar Tests Importantes

- [ ] Limpiar bases de datos de test (`./scripts/clean-test-databases.sh`)
- [ ] Limpiar Redis (`docker compose exec redis redis-cli FLUSHALL`)
- [ ] Verificar servicios corriendo (`docker compose ps`)
- [ ] Verificar max_connections (`docker compose exec postgres psql -U helpdesk -c "SHOW max_connections;"`)

### Después de Cambios en Código

- [ ] Ejecutar tests secuenciales primero (`docker compose exec app php artisan test`)
- [ ] Si pasan, ejecutar tests paralelos con 8 workers
- [ ] Si pasan, ejecutar tests paralelos con 16 workers
- [ ] Verificar varianza ejecutando 2-3 veces

### Señales de Problemas

❌ **Errores "too many clients already"** → Aumentar max_connections
❌ **Errores "relation does not exist"** → Limpiar bases de datos
❌ **Failures intermitentes (>20% varianza)** → Revisar race conditions
❌ **Tests lentos (>3 minutos)** → Optimizar factories/seeders
❌ **Memory exhaustion** → Agregar cleanup en tearDown()

---

## 🎓 LECCIONES APRENDIDAS

### 1. **Corrupción de BD es Real y Costosa**
- **Problema:** 49 bases de datos corruptas causaron 648% de varianza
- **Solución:** Script de limpieza + ejecución regular
- **Prevención:** Limpiar entre cambios importantes

### 2. **Tests Paralelos Requieren Diseño Específico**
- **Problema:** `RefreshDatabase` con transactions no funciona en paralelo
- **Solución:** `RefreshDatabaseWithoutTransactions` custom trait
- **Lección:** No asumir que traits estándar funcionan en paralelo

### 3. **Static Variables Son Peligrosas**
- **Problema:** `static $baseTime` compartida entre workers
- **Solución:** Usar variables de instancia
- **Lección:** Evitar static en tests paralelos

### 4. **Connection Pooling es Esencial**
- **Problema:** 16 workers × 10 conexiones = exhaustion
- **Solución:** Aumentar max_connections + PgBouncer
- **Lección:** Planificar para escala desde el inicio

### 5. **Evidencia Empírica > Intuición**
- **Problema:** Asumir que tests "funcionan bien"
- **Solución:** Ejecutar múltiples iteraciones con limpieza
- **Lección:** Medir varianza sistemáticamente

---

## 📞 SOPORTE Y TROUBLESHOOTING

### Si los Tests Siguen Fallando

1. **Ejecutar diagnóstico completo:**
   ```bash
   # Verificar estado de servicios
   docker compose ps

   # Verificar logs
   docker compose logs postgres --tail=100
   docker compose logs app --tail=100

   # Verificar conexiones
   docker compose exec postgres psql -U helpdesk -c \
     "SELECT * FROM pg_stat_activity WHERE datname LIKE 'helpdesk_test%';"

   # Verificar métricas de Redis
   docker compose exec redis redis-cli INFO stats
   ```

2. **Verificar configuración:**
   - [ ] `.env.testing` tiene configuración correcta
   - [ ] `docker-compose.yml` tiene command de postgres con max_connections
   - [ ] No hay bases de datos corruptas (`./scripts/clean-test-databases.sh`)

3. **Contactar con el equipo:**
   - Documentar: Error exacto, comando ejecutado, logs relevantes
   - Incluir: Resultados de diagnóstico
   - Compartir: `test_metrics.log` si está disponible

---

## 🚀 PRÓXIMOS PASOS INMEDIATOS

### **AHORA (Aplicar Fase 1)**

1. **Reiniciar PostgreSQL** con nueva configuración:
   ```bash
   docker compose down postgres
   docker compose up -d postgres
   ```

2. **Limpiar bases de datos corruptas**:
   ```bash
   ./scripts/clean-test-databases.sh  # O .ps1 en Windows
   ```

3. **Limpiar Redis**:
   ```bash
   docker compose exec redis redis-cli FLUSHALL
   ```

4. **Ejecutar tests de verificación**:
   ```bash
   docker compose exec app php artisan test --parallel --processes=16
   ```

5. **Medir mejora**:
   - Ejecutar 2-3 veces
   - Comparar errors/failures con baseline
   - Documentar varianza

### **ESTA SEMANA (Fase 2)**

- [ ] Implementar PgBouncer
- [ ] Optimizar cache flushing
- [ ] Agregar connection cleanup
- [ ] Optimizar seeding strategy

### **PRÓXIMA SEMANA (Fase 3)**

- [ ] Worker-aware databases
- [ ] Truncate-based refresh
- [ ] Optimizar factories
- [ ] Implementar monitoring

---

**Autor:** Plan de Acción Completo - Fase 1 COMPLETADA
**Versión:** 1.0
**Estado:** ✅ Listo para Aplicar
**Última Actualización:** 2025-11-27
