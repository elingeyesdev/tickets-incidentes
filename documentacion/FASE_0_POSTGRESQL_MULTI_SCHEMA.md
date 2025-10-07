# FASE 0: Integración PostgreSQL Multi-Schema

**Status:** 🟡 IN PROGRESS
**Priority:** 🔴 CRÍTICO - Debe completarse ANTES de conectar resolvers
**Fecha:** 2025-10-07

---

## 📋 Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Problema Identificado](#problema-identificado)
3. [Análisis Completo](#análisis-completo)
4. [Plan de Acción](#plan-de-acción)
5. [Cambios Necesarios](#cambios-necesarios)
6. [Validación](#validación)

---

## 🎯 Resumen Ejecutivo

Al intentar conectar el primer resolver de Authentication, se descubrieron múltiples problemas relacionados con la integración de PostgreSQL multi-schema con Laravel/Eloquent.

**TL;DR:** Las migraciones y models **ya están correctos**, pero hay discrepancias con el Modelado V7.0 y falta configuración en Laravel.

---

## 🔴 Problema Identificado

Laravel/Eloquent asume por defecto que todas las tablas están en el schema `public` de PostgreSQL. Este proyecto usa **4 schemas separados**:

- `auth` - Usuarios, roles, autenticación
- `business` - Empresas, solicitudes
- `ticketing` - Tickets, respuestas (pendiente)
- `audit` - Logs de auditoría (pendiente)

### Síntomas

- Errores al ejecutar resolvers: `relation "users" does not exist`
- Eloquent no encuentra las tablas correctas
- Foreign keys entre schemas fallan

---

## 🔍 Análisis Completo

### ✅ Estado Actual: BUENAS NOTICIAS

**MIGRACIONES (10 archivos):**
```
✅ Todas correctamente implementadas con schemas
✅ Usan Schema::create('auth.users', ...) o DB::statement("CREATE TABLE auth.users ...")
✅ Foreign keys entre schemas funcionan
✅ Enums creados en schemas correctos
```

**MODELS (8 archivos):**
```
✅ Todos tienen protected $table con schema correcto
✅ User: 'auth.users'
✅ UserProfile: 'auth.user_profiles'
✅ Role: 'auth.roles'
✅ UserRole: 'auth.user_roles'
✅ RefreshToken: 'auth.refresh_tokens'
✅ Company: 'business.companies'
✅ CompanyRequest: 'business.company_requests'
✅ CompanyFollower: 'business.user_company_followers'
```

### ❌ Problemas Encontrados

#### 1. **Laravel config/database.php - search_path incorrecto**

**Ubicación:** `config/database.php:97`

```php
// ❌ ACTUAL (INCORRECTO)
'search_path' => 'public',

// ✅ DEBE SER
'search_path' => 'public,auth,business,ticketing,audit',
```

**Impacto:** Alto - Laravel no puede encontrar tablas en otros schemas.

---

#### 2. **Falta migración de extensiones y funciones PostgreSQL**

**Problema:** Las migraciones usan funciones/extensiones que no existen:
- `uuid_generate_v4()` requiere extensión `uuid-ossp`
- `gen_random_uuid()` requiere extensión `pgcrypto` (o PostgreSQL 13+)
- `update_updated_at_column()` función no creada
- Extensión `citext` no creada

**Solución:** Crear migración `0000_00_00_000000_create_postgresql_extensions_and_functions.php`

---

#### 3. **Discrepancias vs Modelado V7.0**

| Categoría | Modelado V7.0 | Implementación Actual | Impacto |
|-----------|---------------|----------------------|---------|
| **User** | Campo `external_auth_id` | ❌ No existe | OAuth no funcionará |
| **User** | Campos `password_reset_token`, `password_reset_expires` | ❌ No existen | Reset password no funcionará |
| **User** | Enum `auth.user_status` | ❌ Usa string en migration | Inconsistente |
| **UserProfile** | Campo `display_name` calculado (no almacenado) | ✅ Almacenado con observer | Aceptable (diferente enfoque) |
| **Roles** | Campo `role_code` (VARCHAR 50) | ❌ Usa `name` | Inconsistente con Modelado |
| **UserRoles** | FK a `role_code` | ❌ FK a `role_id` (UUID) | Estructura diferente |
| **Companies** | Campo `settings` JSONB | ✅ Existe | ✅ Correcto |

---

## 📋 Plan de Acción

### Fase 0.1: Configuración Base ⏱️ 30 min

1. ✅ Analizar migraciones existentes
2. ✅ Analizar models existentes
3. ✅ Identificar discrepancias vs Modelado V7.0
4. ⏳ Actualizar `config/database.php` (search_path)
5. ⏳ Crear migración de extensiones y funciones

### Fase 0.2: Corrección de Discrepancias ⏱️ 2-3 horas

6. ⏳ Decidir enfoque: ¿Seguir Modelado V7.0 al 100% o aceptar diferencias?
7. ⏳ Actualizar User model y migration (campos faltantes)
8. ⏳ Decidir: ¿Mantener `role_id` UUID o cambiar a `role_code` VARCHAR?
9. ⏳ Crear enum `auth.user_status` si es necesario

### Fase 0.3: Validación ⏱️ 30 min

10. ⏳ Ejecutar `php artisan migrate:fresh`
11. ⏳ Verificar estructura de base de datos
12. ⏳ Ejecutar seeders
13. ⏳ Probar queries básicas desde Eloquent

---

## 🛠️ Cambios Necesarios

### 1. Actualizar `config/database.php`

**Archivo:** `config/database.php`

```php
'pgsql' => [
    'driver' => 'pgsql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'laravel'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => env('DB_CHARSET', 'utf8'),
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public,auth,business,ticketing,audit', // ✅ CAMBIO AQUÍ
    'sslmode' => 'prefer',
],
```

---

### 2. Crear Migración de Extensiones y Funciones

**Archivo:** `database/migrations/0000_00_00_000000_create_postgresql_extensions_and_functions.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Crear extensiones necesarias
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
        DB::statement('CREATE EXTENSION IF NOT EXISTS "citext"');

        // Crear función update_updated_at_column() en schema public
        DB::statement("
            CREATE OR REPLACE FUNCTION public.update_updated_at_column()
            RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS public.update_updated_at_column()');
        DB::statement('DROP EXTENSION IF EXISTS "citext"');
        DB::statement('DROP EXTENSION IF EXISTS "pgcrypto"');
        DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
};
```

**Ubicación sugerida:** `database/migrations/` (root, no dentro de features)

---

### 3. Decisión: Estructura de Roles

**OPCIÓN A: Mantener UUID (Actual)**
- ✅ Más flexible
- ✅ Más fácil de implementar
- ❌ Diferente al Modelado V7.0
- ❌ Requiere join adicional para obtener nombre del rol

**OPCIÓN B: Cambiar a role_code VARCHAR (Modelado V7.0)**
- ✅ Consistente con Modelado V7.0
- ✅ Queries más simples (sin join)
- ❌ Requiere migración compleja
- ❌ Puede romper código existente

**RECOMENDACIÓN:** Mantener UUID por ahora, agregar campo `role_code` adicional para compatibilidad.

---

### 4. Actualizar User Model y Migration

**Campos faltantes en `auth.users`:**

```php
// Agregar a la migración create_users_table.php
$table->string('external_auth_id', 255)->nullable()->comment('Google ID, Microsoft ID, etc.');
$table->string('password_reset_token', 255)->nullable()->comment('Token para reset password');
$table->timestamp('password_reset_expires')->nullable()->comment('Expiración del token');
```

**Agregar a User Model:**

```php
protected $fillable = [
    // ... existentes
    'external_auth_id',
    'password_reset_token',
    'password_reset_expires',
];

protected $casts = [
    // ... existentes
    'password_reset_expires' => 'datetime',
];

protected $hidden = [
    'password_hash',
    'password_reset_token', // ⚠️ NUNCA exponer tokens
];
```

---

## ✅ Validación

### Checklist Pre-Ejecución

- [ ] Backup de base de datos actual
- [ ] Verificar que Docker containers estén corriendo
- [ ] Confirmar variables de entorno `.env`
- [ ] Leer este documento completo

### Checklist Post-Ejecución

```bash
# 1. Limpiar migraciones anteriores
docker compose exec app php artisan migrate:fresh

# 2. Verificar que se crearon los schemas
docker compose exec postgres psql -U helpdesk -d helpdesk -c "\dn"

# Expected output:
# List of schemas
#   Name    |  Owner
# ----------+----------
#  auth     | helpdesk
#  business | helpdesk
#  public   | helpdesk

# 3. Verificar que se crearon las extensiones
docker compose exec postgres psql -U helpdesk -d helpdesk -c "\dx"

# Expected output (debe incluir):
# uuid-ossp
# pgcrypto
# citext

# 4. Verificar tablas en schema auth
docker compose exec postgres psql -U helpdesk -d helpdesk -c "\dt auth.*"

# Expected output:
# auth.users
# auth.user_profiles
# auth.roles
# auth.user_roles
# auth.refresh_tokens

# 5. Verificar tablas en schema business
docker compose exec postgres psql -U helpdesk -d helpdesk -c "\dt business.*"

# Expected output:
# business.companies
# business.company_requests
# business.user_company_followers

# 6. Probar query simple desde Laravel
docker compose exec app php artisan tinker
>>> \App\Features\UserManagement\Models\User::count()
=> 0  # ✅ Si no hay error, está funcionando

# 7. Ejecutar seeders
docker compose exec app php artisan db:seed

# 8. Verificar datos insertados
>>> \App\Features\UserManagement\Models\Role::all()->pluck('name')
=> ["USER", "AGENT", "COMPANY_ADMIN", "PLATFORM_ADMIN"]  # ✅ Correcto
```

---

## 📊 Resumen de Archivos Afectados

### Archivos a CREAR
1. `database/migrations/0000_00_00_000000_create_postgresql_extensions_and_functions.php`

### Archivos a MODIFICAR
1. `config/database.php` (línea 97)
2. `app/Features/UserManagement/Database/Migrations/2025_10_01_000002_create_users_table.php` (agregar campos)
3. `app/Features/UserManagement/Models/User.php` (agregar campos a $fillable, $casts, $hidden)

### Archivos a REVISAR (decisión pendiente)
- `app/Features/UserManagement/Database/Migrations/2025_10_01_000004_create_roles_table.php`
- `app/Features/UserManagement/Database/Migrations/2025_10_01_000005_create_user_roles_table.php`
- `app/Features/UserManagement/Models/Role.php`
- `app/Features/UserManagement/Models/UserRole.php`

---

## 🎯 Próximos Pasos (Post-FASE 0)

Una vez completada la FASE 0:

1. ✅ Base de datos funcionando correctamente
2. ✅ Models pueden hacer queries sin errores
3. ✅ Schemas correctamente configurados
4. ➡️ **FASE 3:** Conectar Resolvers (Authentication → UserManagement → CompanyManagement)

---

## 📚 Referencias

- **Modelado Completo:** `documentacion/Modelado final de base de datos.txt`
- **Laravel Multi-Schema:** https://stackoverflow.com/questions/42304245/laravel-postgres-multiple-schemas
- **PostgreSQL Schemas:** https://www.postgresql.org/docs/current/ddl-schemas.html
- **Eloquent:** https://laravel.com/docs/12.x/eloquent

---

**Última actualización:** 2025-10-07
**Autor:** Claude Code
**Estado:** 🟡 Análisis Completado - Esperando Ejecución