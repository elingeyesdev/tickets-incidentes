# Análisis de Implementación de Registro de Actividad

## 1. Diagnóstico del Error Actual "Database error occurred"

**Causa Raíz:**
El filtro "Actividad reciente (7 días)" en el panel de usuarios intenta ejecutar una consulta SQL utilizando la columna `last_activity_at` en la tabla `auth.users`. 
Tras revisar las migraciones existentes en `app/Features/UserManagement/Database/Migrations`, se confirmó que **la columna `last_activity_at` NO existe en la base de datos**, aunque sí está definida en el modelo `User.php`.

**Estado Actual:**
- **Modelo `User.php`**: Define `last_activity_at` en `$fillable` y `$casts`.
- **Migración**: Falta la migración que agregue esta columna.
- **Funcionalidad**: El filtro falla catastróficamente (Error 500) al intentar acceder a una columna inexistente.

---

## 2. Análisis del Código Base (Logging)

**Sistema de Logs Actual:**
- **Login**: Existe un listener `LogLoginActivity` que actualmente solo escribe en el archivo de log del servidor (`laravel.log`) mediante `Log::info()`. 
- **Base de Datos**: No existe ninguna tabla dedicada a auditoría o logs de actividad (e.g., `audit_logs`).
- **Tickets y Críticos**: No hay evidencia de un sistema centralizado para registrar acciones críticas (creación/edición de tickets, cambios de estado, eliminación de usuarios).

**Conclusión**: El sistema de "Registro de Actividad" **NO está implementado**. Solo existen los placeholders y la intención en el código (comentarios "TODO: Phase 6").

---

## 3. Plan de Implementación

Para solucionar el error y cumplir con los requerimientos, se requiere ejecutar las siguientes acciones:

### Fase 1: Corrección de Base de Datos (Inmediato)
1. **Crear Migración de Usuarios**: Agregar la columna `last_activity_at` (timestamp, nullable, indexada) a la tabla `auth.users`.
2. **Crear Tabla de Auditoría**: Crear una nueva tabla `audit.logs` para registrar el historial detallado.
   - Campos sugeridos: `id`, `user_id`, `action` (e.g., 'ticket.create'), `entity_type`, `entity_id`, `metadata` (JSON), `ip_address`, `user_agent`, `created_at`.

### Fase 2: Tracking de "Última Actividad"
1. **Middleware Global**: Crear un middleware `TrackLastActive` que intercepte peticiones autenticadas.
   - Actualizará `last_activity_at` en el usuario.
   - **Optimización**: Usar Cache (Redis/File) para no impactar la DB en cada request (e.g., actualizar máximo cada 5 minutos).

### Fase 3: Registro de Eventos (Tickets y Acciones Críticas)
1. **Servicio de Auditoría**: Crear `AuditService` con un método simple `log($action, $entity, $metadata)`.
2. **Integración en Login**: Actualizar `LogLoginActivity` para usar `AuditService`.
3. **Integración en Tickets**: 
   - Usar "Model Observers" o "Events" en `Ticket` para detectar `created`, `updated` (cambios de estado).
   - Registrar automáticamente cambios críticos.

---

## 4. Análisis de Complejidad y Riesgo

> "Me da miedo la complejidad..."

**Nivel de Complejidad: BAJO - MEDIO**

No tengas miedo. La solución es estándar y modular.
- **Agregar columna**: Trivial (Riesgo casi nulo).
- **Tabla de Auditoría**: Estándar. No requiere lógica compleja de relaciones, es solo una tabla de "escritura".
- **Middleware**: Patrón común en Laravel.
- **Observer para Tickets**: Es la forma limpia de hacerlo sin ensuciar los controladores.

**Tiempos Estimados:**
- Corrección del error (Columna): 30 mins
- Tabla de Auditoría: 1 hora
- Middleware y Lógica de Login: 2 horas
- Integración con Tickets: 2-3 horas

**Recomendación**:
Empezar con la **Fase 1 (Columna y Tabla)** y la **Fase 2 (Middleware)**. Esto arreglará el error del filtro y empezará a guardar "Cuándo fue la última vez que se vio al usuario". Luego, progresivamente conectar los eventos de tickets (Fase 3).
