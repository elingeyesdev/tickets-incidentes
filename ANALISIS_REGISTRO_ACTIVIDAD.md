# Análisis: Sistema de Registro de Actividad para Helpdesk

**Fecha**: 7 de diciembre de 2025  
**Autor**: GitHub Copilot  
**Versión**: 1.0

---

## 📋 Resumen Ejecutivo

Este documento analiza la implementación de un sistema de registro de actividad (Activity Log) para el sistema Helpdesk, enfocado en:
1. **Ticket Management**: Todas las acciones críticas sobre tickets
2. **Autenticación**: Login, logout, cambios de sesión
3. **Acciones críticas**: Cambios de rol, estado de usuarios, gestión de empresas

### Estado Actual
- ✅ Existe infraestructura de auditoría en PostgreSQL (`audit.audit_logs`)
- ✅ Existe función trigger `audit.log_changes()` para auditoría a nivel de BD
- ⚠️ **NO está conectada/activa** - Los triggers no están creados en las tablas
- ⚠️ No existe modelo Eloquent `AuditLog` 
- ⚠️ No existe endpoint API para consultar actividad
- ⚠️ Los campos `last_login_at`, `last_activity_at` existen pero no se actualizan consistentemente
- ⚠️ El frontend muestra datos de actividad **hardcodeados/simulados**

---

## 🏗️ Arquitectura Actual

### 1. Infraestructura de Base de Datos Existente

```sql
-- Schema: audit
-- Tabla: audit.audit_logs
CREATE TABLE audit.audit_logs (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id),
    action audit.action_type NOT NULL,  -- ENUM: create, update, delete, login, logout
    performed_at TIMESTAMPTZ,
    table_name VARCHAR(100),
    record_id UUID,
    old_values JSONB,
    new_values JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMPTZ
);
```

**Ubicación**: `app/Shared/Database/Migrations/2025_10_07_000002_create_audit_schema.php`

### 2. Función de Trigger (No Activa)

```sql
CREATE OR REPLACE FUNCTION audit.log_changes()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO audit.audit_logs (...)
    VALUES (...);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

**Ubicación**: `app/Shared/Database/Migrations/2025_10_07_000003_create_audit_log_changes_function.php`

### 3. Trait Auditable (Limitado)

El trait `App\Shared\Traits\Auditable` solo rastrea `created_by_id`, `updated_by_id`, `deleted_by_id` - **NO registra en audit_logs**.

### 4. Listener de Login (Solo Log::info)

```php
// app/Features/Authentication/Listeners/LogLoginActivity.php
class LogLoginActivity
{
    public function handle(UserLoggedIn $event): void
    {
        // Por ahora solo loguear en archivo
        // TODO: En Phase 6, reemplazar con AuditLog::create()
        Log::info('User logged in', [...]);
    }
}
```

---

## 🎯 Acciones a Registrar

### Nivel 1: Ticket Management (CRÍTICO)

| Acción | Evento Existente | Controller/Service | Prioridad |
|--------|------------------|-------------------|-----------|
| Crear ticket | `TicketCreated` | `TicketController::store` | 🔴 Alta |
| Actualizar ticket | - | `TicketController::update` | 🔴 Alta |
| Eliminar ticket | - | `TicketController::destroy` | 🔴 Alta |
| Resolver ticket | `TicketResolved` | `TicketActionController::resolve` | 🔴 Alta |
| Cerrar ticket | `TicketClosed` | `TicketActionController::close` | 🔴 Alta |
| Reabrir ticket | `TicketReopened` | `TicketActionController::reopen` | 🔴 Alta |
| Asignar ticket | `TicketAssigned` | `TicketActionController::assign` | 🔴 Alta |
| Agregar respuesta | `ResponseAdded` | `TicketResponseController::store` | 🟡 Media |
| Agregar adjunto | - | `TicketAttachmentController::store` | 🟡 Media |

### Nivel 2: Autenticación (CRÍTICO)

| Acción | Evento Existente | Prioridad |
|--------|------------------|-----------|
| Login exitoso | `UserLoggedIn` ✅ | 🔴 Alta |
| Login fallido | - | 🔴 Alta |
| Logout | `UserLoggedOut` | 🔴 Alta |
| Registro | `UserRegistered` | 🟡 Media |
| Reset password | `PasswordResetRequested` | 🟡 Media |
| Verificar email | `EmailVerified` | 🟡 Media |

### Nivel 3: Gestión de Usuarios y Empresas (IMPORTANTE)

| Acción | Prioridad |
|--------|-----------|
| Cambio de estado de usuario (activar/suspender) | 🔴 Alta |
| Asignación/remoción de roles | 🔴 Alta |
| Actualización de perfil | 🟢 Baja |
| Creación de empresa | 🟡 Media |
| Aprobación/rechazo de solicitud de empresa | 🔴 Alta |

---

## 📊 Modelo de Datos Propuesto

### Extender audit.action_type ENUM

```sql
ALTER TYPE audit.action_type ADD VALUE 'login_failed';
ALTER TYPE audit.action_type ADD VALUE 'resolve';
ALTER TYPE audit.action_type ADD VALUE 'close';
ALTER TYPE audit.action_type ADD VALUE 'reopen';
ALTER TYPE audit.action_type ADD VALUE 'assign';
ALTER TYPE audit.action_type ADD VALUE 'role_assign';
ALTER TYPE audit.action_type ADD VALUE 'role_remove';
ALTER TYPE audit.action_type ADD VALUE 'status_change';
```

### Modelo Eloquent AuditLog

```php
// app/Features/AuditLog/Models/AuditLog.php
namespace App\Features\AuditLog\Models;

class AuditLog extends Model
{
    protected $table = 'audit.audit_logs';
    
    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'performed_at' => 'datetime',
    ];
}
```

---

## 🔧 Plan de Implementación

### Fase 1: Infraestructura Base (Complejidad: BAJA)

1. **Crear Feature AuditLog**
   ```
   app/Features/AuditLog/
   ├── Models/
   │   └── AuditLog.php
   ├── Services/
   │   └── ActivityLogService.php
   ├── Http/
   │   ├── Controllers/
   │   │   └── ActivityLogController.php
   │   └── Resources/
   │       └── ActivityLogResource.php
   ├── Database/
   │   └── Migrations/
   │       └── 2025_12_07_000001_extend_audit_action_types.php
   └── AuditLogServiceProvider.php
   ```

2. **Crear migración para extender ENUM**

3. **Crear modelo Eloquent AuditLog**

4. **Crear servicio ActivityLogService**
   ```php
   class ActivityLogService
   {
       public function log(
           string $action,
           ?string $userId = null,
           ?string $tableName = null,
           ?string $recordId = null,
           ?array $oldValues = null,
           ?array $newValues = null
       ): AuditLog;
   }
   ```

### Fase 2: Integración con Ticket Management (Complejidad: MEDIA)

1. **Crear listeners para eventos existentes**
   ```php
   // TicketCreated -> LogTicketActivity
   // TicketResolved -> LogTicketActivity
   // TicketClosed -> LogTicketActivity
   // etc.
   ```

2. **Agregar logging en controllers donde no hay eventos**
   - `TicketController::update`
   - `TicketController::destroy`

### Fase 3: Integración con Autenticación (Complejidad: BAJA)

1. **Modificar LogLoginActivity** para usar AuditLog::create() en lugar de Log::info()

2. **Agregar listener para login fallido**

3. **Agregar listener para logout**

### Fase 4: API y Frontend (Complejidad: MEDIA)

1. **Crear endpoint GET /api/activity-logs**
   - Filtros: user_id, action, date_range, record_type
   - Paginación
   - Solo accesible por el propio usuario o admins

2. **Actualizar frontend del perfil**
   - Reemplazar datos simulados por llamada a API real

### Fase 5: Actualización de last_login_at (Complejidad: BAJA)

1. **Verificar que AuthService::login() actualiza last_login_at** ✅ Ya lo hace

2. **Implementar middleware para actualizar last_activity_at**
   ```php
   // app/Http/Middleware/RecordActivity.php
   class RecordActivity
   {
       public function handle(Request $request, Closure $next)
       {
           $response = $next($request);
           
           if (auth()->check()) {
               auth()->user()->recordActivity();
           }
           
           return $response;
       }
   }
   ```

---

## ⚠️ Evaluación de Complejidad y Riesgos

### Complejidad General: **MEDIA** (3/5)

| Componente | Complejidad | Tiempo Estimado |
|------------|-------------|-----------------|
| Infraestructura base | Baja | 2-3 horas |
| Modelo y servicio | Baja | 2-3 horas |
| Listeners de tickets | Media | 4-6 horas |
| Listeners de auth | Baja | 2-3 horas |
| API endpoint | Media | 3-4 horas |
| Frontend | Baja | 2-3 horas |
| Testing | Media | 4-6 horas |
| **TOTAL** | **MEDIA** | **~20-28 horas** |

### Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Performance en alta carga | Media | Alto | Usar queue para logging asíncrono |
| Crecimiento de tabla audit_logs | Alta | Medio | Implementar política de retención (90 días) |
| Migración ENUM puede fallar | Baja | Alto | Probar en staging primero |
| Falta de contexto en algunos logs | Media | Bajo | Documentar bien qué se registra |

### Dependencias

1. **Ninguna dependencia externa** - Todo usa infraestructura existente
2. **Compatible con arquitectura Feature-First** actual
3. **Compatible con sistema de eventos de Laravel** ya implementado

---

## 🧪 Consideraciones de Testing

### Tests Unitarios Requeridos

```php
// tests/Feature/AuditLog/ActivityLogServiceTest.php
- test_can_log_activity()
- test_log_captures_user_context()
- test_log_captures_ip_and_user_agent()

// tests/Feature/TicketManagement/TicketActivityLoggingTest.php
- test_ticket_creation_is_logged()
- test_ticket_update_is_logged()
- test_ticket_status_changes_are_logged()

// tests/Feature/Authentication/LoginActivityLoggingTest.php
- test_successful_login_is_logged()
- test_failed_login_is_logged()
- test_logout_is_logged()
```

### Tests de Integración

```php
// tests/Feature/AuditLog/ActivityLogApiTest.php
- test_user_can_view_own_activity()
- test_admin_can_view_any_activity()
- test_regular_user_cannot_view_others_activity()
- test_activity_log_is_paginated()
- test_activity_log_can_be_filtered()
```

---

## 📝 Archivos a Crear/Modificar

### Nuevos Archivos

```
app/Features/AuditLog/
├── Models/AuditLog.php
├── Services/ActivityLogService.php
├── Http/Controllers/ActivityLogController.php
├── Http/Resources/ActivityLogResource.php
├── Listeners/LogTicketActivity.php
├── Listeners/LogAuthActivity.php
├── Database/Migrations/2025_12_07_000001_extend_audit_action_types.php
└── AuditLogServiceProvider.php

app/Http/Middleware/RecordActivity.php
```

### Archivos a Modificar

```
bootstrap/providers.php                    # Registrar AuditLogServiceProvider
routes/api.php                             # Agregar rutas de activity-logs
app/Features/Authentication/Listeners/LogLoginActivity.php  # Usar AuditLog
app/Features/TicketManagement/TicketManagementServiceProvider.php  # Registrar listeners
resources/views/app/profile/index.blade.php  # Conectar con API real
```

---

## 🎯 Recomendaciones

### Implementación por Fases

1. **Fase MVP (1-2 días)**
   - Crear modelo AuditLog
   - Crear servicio básico
   - Integrar con login/logout

2. **Fase Tickets (2-3 días)**
   - Listeners para eventos de tickets
   - Logging en controllers

3. **Fase API + Frontend (1-2 días)**
   - Endpoint REST
   - Actualizar perfil de usuario

4. **Fase Polish (1 día)**
   - Política de retención
   - Documentación
   - Tests completos

### Configuración Recomendada

```php
// config/audit.php
return [
    'enabled' => env('AUDIT_ENABLED', true),
    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),
    'async' => env('AUDIT_ASYNC', true),  // Usar queue
    'excluded_actions' => ['read'],  // No registrar lecturas
];
```

---

## ✅ Conclusión

La implementación del sistema de registro de actividad es **factible y de complejidad media**. 

**Puntos a favor:**
- Infraestructura de BD ya existe
- Sistema de eventos de Laravel facilita integración
- Arquitectura Feature-First permite modularidad

**Puntos de atención:**
- Necesita extender ENUM de PostgreSQL (requiere migración cuidadosa)
- Considerar performance en producción (usar queue)
- Definir política de retención desde el inicio

**Recomendación final:** Proceder con implementación por fases, comenzando con MVP de autenticación y luego tickets.
