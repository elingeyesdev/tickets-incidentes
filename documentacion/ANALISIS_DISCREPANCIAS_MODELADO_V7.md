# 🔴 ANÁLISIS CRÍTICO: Discrepancias vs Modelado V7.0

**Fecha:** 2025-10-07
**Status:** 🚨 ALTO - Diferencias Estructurales Importantes

---

## 📊 Resumen Ejecutivo

La implementación actual tiene **diferencias estructurales significativas** con el Modelado V7.0. No son simples ajustes - son decisiones de arquitectura diferentes.

**Nivel de Divergencia:** 🔴 ALTO (40-50% de diferencia en algunas tablas)

---

## 🔍 Análisis Detallado por Tabla

### ❌ CRÍTICO: auth.user_status ENUM

**Modelado V7.0:**
```sql
CREATE TYPE auth.user_status AS ENUM ('active', 'suspended', 'deleted');
```

**Implementación Actual:**
```php
$table->enum('status', ['active', 'suspended', 'deleted'])
```

**Problema:**
- Laravel crea ENUM inline, NO como TYPE PostgreSQL
- El Modelado espera `auth.user_status` type
- Inconsistente con otros ENUMs del sistema

**Impacto:** 🟡 MEDIO
- Funciona, pero no sigue estándar del proyecto
- Otros schemas usan `business.request_status`, `business.publication_status`, etc.

**Fix Requerido:**
```php
// En migration create_auth_schema.php
DB::statement("CREATE TYPE auth.user_status AS ENUM ('active', 'suspended', 'deleted')");

// En create_users_table.php
DB::statement("ALTER TABLE auth.users ALTER COLUMN status TYPE auth.user_status USING status::auth.user_status");
```

---

### 🔴 CRÍTICO: auth.user_profiles - Primary Key Incorrecta

**Modelado V7.0:**
```sql
CREATE TABLE auth.user_profiles (
    user_id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    -- NO HAY CAMPO 'id'
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    -- display_name NO SE ALMACENA, se calcula en queries
    ...
);
```

**Implementación Actual:**
```php
$table->uuid('id')->primary();  // ❌ NO DEBE EXISTIR
$table->uuid('user_id')->unique()->comment('FK a auth.users');
$table->string('display_name', 200)->comment('Nombre completo calculado'); // ❌ NO DEBE ALMACENARSE
```

**Problema:**
- PK debe ser `user_id`, NO `id`
- Relación 1:1 pura (un usuario = un perfil)
- Campo `display_name` NO debe almacenarse (se calcula)

**Impacto:** 🔴 ALTO
- Cambia relaciones en Eloquent
- Afecta queries y joins
- Desperdicia espacio con `display_name`

**Fix Requerido:**
```php
// RECREAR COMPLETA migration create_user_profiles_table.php
Schema::create('auth.user_profiles', function (Blueprint $table) {
    // ===== PRIMARY KEY = user_id (NO id) =====
    $table->uuid('user_id')->primary();
    $table->foreign('user_id')
        ->references('id')
        ->on('auth.users')
        ->onDelete('cascade');

    // ===== INFORMACIÓN PERSONAL =====
    $table->string('first_name', 100)->comment('Nombre del usuario');
    $table->string('last_name', 100)->comment('Apellido del usuario');
    // ❌ NO incluir display_name - se calcula con accesor en Model

    $table->string('phone_number', 20)->nullable();
    $table->string('avatar_url', 500)->nullable();

    // ... resto igual
    $table->timestamps();
});
```

---

### 🔴 CRÍTICO: auth.roles - Estructura Completamente Diferente

**Modelado V7.0:**
```sql
CREATE TABLE auth.roles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    role_code VARCHAR(50) UNIQUE NOT NULL,        -- ✅ 'platform_admin', 'company_admin'
    role_name VARCHAR(100) NOT NULL,              -- ✅ 'Administrador de Plataforma'
    description TEXT,
    is_system BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO auth.roles (role_code, role_name, description, is_system) VALUES
('platform_admin', 'Administrador de Plataforma', 'Acceso total al sistema', true),
('company_admin', 'Administrador de Empresa', 'Gestiona una empresa específica', true),
('agent', 'Agente de Soporte', 'Atiende tickets de soporte', true),
('user', 'Cliente', 'Usuario que crea tickets', true);
```

**Implementación Actual:**
```php
$table->string('name', 50)->unique();              // ❌ Debería ser 'role_code'
$table->string('display_name', 100);               // ❌ Debería ser 'role_name'
$table->text('description')->nullable();           // ✅ OK
$table->json('permissions');                       // ❌ NO EXISTE en Modelado
$table->boolean('requires_company')->default(false); // ❌ NO EXISTE
$table->string('default_dashboard', 100);          // ❌ NO EXISTE
$table->integer('priority')->default(0);           // ❌ NO EXISTE
$table->timestamps();                              // ❌ Modelado solo tiene created_at

// INSERT usa nombres diferentes:
'name' => 'USER',                                  // ❌ Debería ser role_code='user'
'display_name' => 'Usuario',                       // ❌ Debería ser role_name
```

**Problema:**
- Estructura diseñada para sistema de permisos complejo (permissions JSONB)
- Modelado V7.0 es más simple: solo códigos y nombres
- Permisos deberían manejarse a nivel de código/Policy, no BD

**Impacto:** 🔴 MUY ALTO
- Toda la lógica de roles es diferente
- Models usan campos diferentes
- Seeders insertan datos diferentes

**Opciones:**

**OPCIÓN A: Seguir Modelado V7.0 (RECOMENDADO)**
```php
// Tabla MÁS SIMPLE
Schema::create('auth.roles', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('role_code', 50)->unique();
    $table->string('role_name', 100);
    $table->text('description')->nullable();
    $table->boolean('is_system')->default(true);
    $table->timestamp('created_at')->useCurrent();
});

// Permisos se manejan en Policies de Laravel, NO en BD
```

**OPCIÓN B: Mantener Actual + Agregar role_code**
```php
// Agregar role_code para compatibilidad, mantener lo demás
$table->string('role_code', 50)->unique()->after('id');
$table->string('name', 50)->unique(); // Mantener
// ... resto igual
```

---

### 🔴 CRÍTICO: auth.user_roles - FK a role_code vs role_id

**Modelado V7.0:**
```sql
CREATE TABLE auth.user_roles (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    role_code VARCHAR(50) NOT NULL REFERENCES auth.roles(role_code), -- ✅ FK a VARCHAR

    company_id UUID,
    is_active BOOLEAN DEFAULT TRUE,

    assigned_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    assigned_by UUID REFERENCES auth.users(id),
    revoked_at TIMESTAMPTZ,

    CONSTRAINT uq_user_role_context UNIQUE (user_id, role_code, company_id),
    CONSTRAINT chk_company_context CHECK (
        (role_code IN ('company_admin', 'agent') AND company_id IS NOT NULL) OR
        (role_code NOT IN ('company_admin', 'agent'))
    )
);
```

**Implementación Actual:**
```php
$table->uuid('role_id')->comment('FK a auth.roles');  // ❌ Debería ser role_code VARCHAR
$table->foreign('role_id')
    ->references('id')  // ❌ Debería referenciar 'role_code'
    ->on('auth.roles');

$table->uuid('revoked_by_id')->nullable(); // ❌ NO EXISTE en Modelado
$table->unique(['user_id', 'role_id', 'company_id']); // ❌ Debería ser role_code
```

**Problema:**
- FK a UUID requiere join para obtener nombre de rol
- FK a VARCHAR permite acceso directo al código
- CHECK constraint depende de role_code

**Impacto:** 🔴 ALTO
- Cambia queries de roles
- Afecta lógica de autorización
- Models necesitan ajustes

---

### 🟡 MEDIO: auth.refresh_tokens - Campo faltante

**Modelado V7.0:**
```sql
revoke_reason VARCHAR(100), -- 'manual_logout', 'security_breach', 'expired'
```

**Implementación Actual:**
```php
// Campo NO EXISTE
$table->uuid('revoked_by_id')->nullable(); // Existe pero no en Modelado
```

**Fix:**
```php
$table->string('revoke_reason', 100)->nullable()->after('revoked_at');
// Mantener revoked_by_id (útil para auditoría)
```

---

### ✅ OK: business.company_requests

**Comparación:**
- ✅ Todos los campos coinciden
- ✅ FK correctas
- ✅ ENUM `business.request_status` usado correctamente
- ⚠️ Falta campo en Model: NO existe en Modelado pero existe en implementación: `created_company_id`

**Decisión:** Mantener `created_company_id` (útil, no contradice Modelado)

---

### ✅ OK: business.companies

**Comparación:**
- ✅ Todos los campos coinciden
- ✅ JSONB business_hours correcto
- ✅ FK correctas
- ⚠️ Campo adicional: `settings` JSONB (NO en Modelado pero útil)

**Decisión:** Mantener `settings` (flexibilidad futura)

---

### ✅ OK: business.user_company_followers

**Comparación:**
- ✅ Estructura idéntica
- ✅ Unique constraint correcto

---

## 📋 Tabla de Impacto

| Tabla | Divergencia | Impacto | Fix Requerido |
|-------|-------------|---------|---------------|
| `auth.users` | ENUM inline vs TYPE | 🟡 MEDIO | Crear auth.user_status TYPE |
| `auth.user_profiles` | PK incorrecta | 🔴 ALTO | Recrear sin id, sin display_name |
| `auth.roles` | Estructura diferente | 🔴 MUY ALTO | Recrear completa O agregar role_code |
| `auth.user_roles` | FK a UUID vs VARCHAR | 🔴 ALTO | Cambiar FK a role_code |
| `auth.refresh_tokens` | Campo faltante | 🟡 MEDIO | Agregar revoke_reason |
| `business.company_requests` | OK | ✅ BAJO | Ninguno |
| `business.companies` | OK | ✅ BAJO | Ninguno |
| `business.user_company_followers` | OK | ✅ BAJO | Ninguno |

---

## 🎯 Opciones de Acción

### OPCIÓN A: 100% Fidelidad al Modelado V7.0 ⏱️ 4-6 horas

**Pros:**
- ✅ Consistente con diseño original
- ✅ Más simple (menos campos en BD)
- ✅ Permisos en código (Policies), no BD
- ✅ Queries más eficientes (FK a VARCHAR)

**Contras:**
- ❌ Requiere reescribir 4 migrations
- ❌ Requiere actualizar 4 Models
- ❌ Requiere actualizar Seeders
- ❌ Puede romper código existente

**Archivos a Reescribir:**
1. `create_auth_schema.php` - Agregar auth.user_status TYPE
2. `create_user_profiles_table.php` - Cambiar PK, eliminar id y display_name
3. `create_roles_table.php` - Simplificar estructura
4. `create_user_roles_table.php` - Cambiar FK a role_code
5. `app/Features/UserManagement/Models/UserProfile.php` - Actualizar
6. `app/Features/UserManagement/Models/Role.php` - Simplificar
7. `app/Features/UserManagement/Models/UserRole.php` - Actualizar FK

---

### OPCIÓN B: Mantener Actual + Mínimos Ajustes ⏱️ 1-2 horas

**Pros:**
- ✅ Menos trabajo
- ✅ No rompe código existente
- ✅ Sistema de permisos más flexible (JSONB)

**Contras:**
- ❌ No sigue Modelado V7.0
- ❌ Más complejo de mantener
- ❌ FK a UUID menos eficiente

**Cambios Mínimos:**
1. Crear `auth.user_status` TYPE (por consistencia)
2. Agregar campo `role_code` a `auth.roles` (para queries)
3. Agregar `revoke_reason` a `auth.refresh_tokens`
4. Documentar diferencias en README

---

### OPCIÓN C: Híbrida (RECOMENDACIÓN) ⏱️ 2-3 horas

**Mantener:**
- ✅ `auth.roles` con estructura actual (más flexible)
- ✅ `auth.user_roles` con FK a UUID

**Cambiar:**
- ✅ `auth.user_profiles` - Remover id, hacer user_id PK
- ✅ Crear `auth.user_status` TYPE
- ✅ Agregar `role_code` a roles (índice único adicional)
- ✅ Agregar `revoke_reason` a refresh_tokens

**Justificación:**
- user_profiles: Cambio fácil, sigue estándar 1:1
- auth.user_status: Por consistencia con otros ENUMs
- roles: Estructura actual más útil para permisos granulares
- role_code: Best of both worlds - queries directas + flexibilidad UUID

---

## 🤔 Pregunta para el Usuario

**¿Qué opción prefieres?**

**A)** 100% fiel al Modelado V7.0 (4-6h, reescribir todo)
**B)** Mantener actual + ajustes mínimos (1-2h, documentar diferencias)
**C)** Híbrida (2-3h, balance entre ambos) **⭐ RECOMENDADO**

**Consideraciones:**
- ¿El Modelado V7.0 es el diseño definitivo o puede ajustarse?
- ¿Prefieres simplicidad (Modelado) o flexibilidad (Actual)?
- ¿Es más importante fidelidad al diseño o velocidad de desarrollo?

---

**Última actualización:** 2025-10-07
**Estado:** 🟡 Esperando decisión del usuario
