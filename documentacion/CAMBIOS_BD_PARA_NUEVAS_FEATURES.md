# Cambios en la Base de Datos para Nuevas Features

**Documento de Especificación de Migraciones**
**Fecha:** Noviembre 26, 2025
**Estado:** Listo para implementación

---

## 📋 Resumen de Cambios

```
NUEVOS ENUMS:           3
NUEVAS TABLAS:          4
TABLAS MODIFICADAS:     1 (tickets)
NUEVAS FUNCIONES:       2
NUEVOS TRIGGERS:        2
NUEVOS ÍNDICES:         6-8
```

---

## 🔄 Cambios por Feature

### Feature 1: PRIORIDAD

#### 1.1 Nuevo ENUM

```sql
-- Agregar a la SECCIÓN DE TIPOS ENUMERADOS
CREATE TYPE ticketing.ticket_priority AS ENUM (
    'low',      -- Baja urgencia, respuesta en 24 horas
    'medium',   -- Normal, respuesta en 4-8 horas
    'high',     -- Urgente, respuesta en 1-2 horas
    'critical'  -- Emergencia, respuesta en 30 min
);
```

**Ubicación en archivo actual:** Línea 58-60 (después de `author_type`)

#### 1.2 Modificación a Tabla TICKETS

```sql
-- AGREGAR COLUMNA a ticketing.tickets (línea ~449)
ALTER TABLE ticketing.tickets
ADD COLUMN priority ticketing.ticket_priority DEFAULT 'medium' NOT NULL;

-- AGREGAR ÍNDICE para búsquedas por prioridad
CREATE INDEX idx_tickets_priority ON ticketing.tickets(priority)
WHERE priority IN ('high', 'critical');

CREATE INDEX idx_tickets_priority_status ON ticketing.tickets(priority, status)
WHERE status IN ('open', 'pending');
```

**Impacto:** Mínimo, solo 1 columna + 2 índices

---

### Feature 2: AUTO-ESCALADA (24h sin respuesta)

#### 2.1 Nuevo ENUM

```sql
CREATE TYPE ticketing.ticket_escalation_reason AS ENUM (
    'inactivity_24h',       -- Sin respuesta en 24 horas
    'manual_escalation',    -- Agente escaló manualmente
    'user_request'          -- Usuario lo pidió
);
```

**Ubicación:** Línea 60-62 (después de `ticket_priority`)

#### 2.2 Nueva Tabla TICKET_ESCALATIONS

```sql
-- Insertar DESPUÉS de TICKET_RATINGS (línea ~531)
CREATE TABLE ticketing.ticket_escalations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,

    old_priority ticketing.ticket_priority NOT NULL,
    new_priority ticketing.ticket_priority NOT NULL,

    reason ticketing.ticket_escalation_reason NOT NULL,

    escalated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_escalation_per_ticket UNIQUE (ticket_id, escalated_at)
);

-- Índices para auditoría y reporting
CREATE INDEX idx_escalations_ticket_id ON ticketing.ticket_escalations(ticket_id);
CREATE INDEX idx_escalations_escalated_at ON ticketing.ticket_escalations(escalated_at DESC);
CREATE INDEX idx_escalations_reason ON ticketing.ticket_escalations(reason);
```

**Tablas relacionadas:** Depende de `ticket_priority` (nuevo enum)

#### 2.3 Nueva Función para Auto-Escalada

```sql
-- Agregar en SECCIÓN DE FUNCIONES Y TRIGGERS (línea ~620)
CREATE OR REPLACE FUNCTION ticketing.escalate_unattended_tickets()
RETURNS TABLE (escalated_count INT) AS $$
DECLARE
    v_escalated_count INT := 0;
    v_ticket RECORD;
BEGIN
    -- Encuentra tickets OPEN hace más de 24h sin respuesta de agente
    FOR v_ticket IN
        SELECT t.id, t.priority
        FROM ticketing.tickets t
        WHERE t.status = 'open'::ticketing.ticket_status
        AND t.created_at <= CURRENT_TIMESTAMP - INTERVAL '24 hours'
        AND t.first_response_at IS NULL
        AND t.priority IN ('low'::ticketing.ticket_priority, 'medium'::ticketing.ticket_priority)
    LOOP
        -- Actualizar prioridad
        UPDATE ticketing.tickets
        SET priority = 'high'::ticketing.ticket_priority
        WHERE id = v_ticket.id;

        -- Registrar escalada
        INSERT INTO ticketing.ticket_escalations (ticket_id, old_priority, new_priority, reason)
        VALUES (v_ticket.id, v_ticket.priority, 'high'::ticketing.ticket_priority, 'inactivity_24h'::ticketing.ticket_escalation_reason);

        v_escalated_count := v_escalated_count + 1;
    END LOOP;

    RETURN QUERY SELECT v_escalated_count;
END;
$$ LANGUAGE plpgsql;
```

---

### Feature 3: RECORDATORIOS

#### 3.1 Nueva Tabla TICKET_REMINDERS

```sql
-- Insertar DESPUÉS de TICKET_ESCALATIONS
CREATE TABLE ticketing.ticket_reminders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    ticket_id UUID NOT NULL REFERENCES ticketing.tickets(id) ON DELETE CASCADE,

    sent_by_user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE RESTRICT,

    message TEXT,

    sent_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT one_reminder_per_ticket_per_hour
        CHECK (1=1) -- Validación en aplicación con Cache
);

-- Índices para búsquedas
CREATE INDEX idx_reminders_ticket_id ON ticketing.ticket_reminders(ticket_id);
CREATE INDEX idx_reminders_sent_at ON ticketing.ticket_reminders(sent_at DESC);
CREATE INDEX idx_reminders_sent_by ON ticketing.ticket_reminders(sent_by_user_id);
CREATE INDEX idx_reminders_ticket_user ON ticketing.ticket_reminders(ticket_id, sent_by_user_id);
```

**Características:**
- Anti-spam: Validación en aplicación (Cache de Redis)
- Auditoría: Registra quién envió recordatorios
- Flexibilidad: Mensaje personalizable

#### 3.2 Comentario de Auditoría

```sql
COMMENT ON TABLE ticketing.ticket_reminders IS 'Historial de recordatorios enviados a usuarios. Se valida 1 por hora vía Cache. Auditoría completa.';
COMMENT ON COLUMN ticketing.ticket_reminders.sent_by_user_id IS 'El agente que envió el recordatorio. RESTRICT para auditoría.';
COMMENT ON COLUMN ticketing.ticket_reminders.message IS 'Mensaje personalizado. Si NULL, se usa default en aplicación.';
```

---

### Feature 4: ÁREAS

#### 4.1 Nueva Tabla AREAS

```sql
-- Insertar DESPUÉS de CATEGORIES (línea ~440)
CREATE TABLE ticketing.areas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    company_id UUID NOT NULL REFERENCES business.companies(id) ON DELETE CASCADE,

    name VARCHAR(100) NOT NULL,
    description TEXT,

    is_active BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    -- Constraint: nombre único por empresa
    CONSTRAINT uq_company_area_name UNIQUE (company_id, name)
);

-- Agregar trigger para updated_at
CREATE TRIGGER trigger_update_areas_updated_at
BEFORE UPDATE ON ticketing.areas
FOR EACH ROW EXECUTE FUNCTION public.update_updated_at_column();

-- Índices
CREATE INDEX idx_areas_company_id ON ticketing.areas(company_id);
CREATE INDEX idx_areas_is_active ON ticketing.areas(is_active) WHERE is_active = true;
CREATE INDEX idx_areas_company_active ON ticketing.areas(company_id, is_active);
```

#### 4.2 Nueva Tabla AGENT_AREAS (Junction)

```sql
-- Insertar DESPUÉS de AREAS
CREATE TABLE ticketing.agent_areas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),

    user_id UUID NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE,
    area_id UUID NOT NULL REFERENCES ticketing.areas(id) ON DELETE CASCADE,

    is_active BOOLEAN DEFAULT TRUE,

    assigned_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,

    -- Constraint: un agente solo puede tener un rol por área
    CONSTRAINT uq_agent_area UNIQUE (user_id, area_id)
);

-- Índices para búsquedas
CREATE INDEX idx_agent_areas_user_id ON ticketing.agent_areas(user_id);
CREATE INDEX idx_agent_areas_area_id ON ticketing.agent_areas(area_id);
CREATE INDEX idx_agent_areas_active ON ticketing.agent_areas(is_active);
```

#### 4.3 Modificación a Tabla TICKETS

```sql
-- AGREGAR COLUMNA a ticketing.tickets
ALTER TABLE ticketing.tickets
ADD COLUMN area_id UUID REFERENCES ticketing.areas(id) ON DELETE SET NULL;

-- AGREGAR ÍNDICES para búsquedas por área
CREATE INDEX idx_tickets_area_id ON ticketing.tickets(area_id);
CREATE INDEX idx_tickets_area_status ON ticketing.tickets(area_id, status)
WHERE status IN ('open', 'pending');
```

#### 4.4 Comentarios de Auditoría

```sql
COMMENT ON TABLE ticketing.areas IS 'Áreas/departamentos de una empresa. Agrupan agentes por función. Opcional por empresa.';
COMMENT ON COLUMN ticketing.areas.is_active IS 'Áreas inactivas no aparecen en dropdowns pero sus tickets existentes se mantienen.';

COMMENT ON TABLE ticketing.agent_areas IS 'Asignación many-to-many de agentes a áreas. Un agente puede estar en múltiples áreas.';
COMMENT ON COLUMN ticketing.agent_areas.is_active IS 'Si false, agente no recibe nuevos tickets de esa área pero mantiene los existentes.';

COMMENT ON COLUMN ticketing.tickets.area_id IS 'Área a la que pertenece el ticket. Usado para routing inteligente. Opcional (NULL si empresa no usa áreas).';
```

---

## 📊 Resumen Visual de Cambios

### ENUMS Nuevos

```
ticketing.
├── ticket_priority          [NUEVO]
│   ├── low
│   ├── medium
│   ├── high
│   └── critical
│
└── ticket_escalation_reason [NUEVO]
    ├── inactivity_24h
    ├── manual_escalation
    └── user_request
```

### Tablas Nuevas

```
ticketing.
├── ticket_escalations       [NUEVA - Auditoría de escaladas]
│   └── Campos: id, ticket_id, old_priority, new_priority, reason, escalated_at
│
├── ticket_reminders         [NUEVA - Recordatorios a usuarios]
│   └── Campos: id, ticket_id, sent_by_user_id, message, sent_at
│
├── areas                    [NUEVA - Departamentos/Equipos]
│   └── Campos: id, company_id, name, description, is_active, created_at, updated_at
│
└── agent_areas              [NUEVA - Asignación agentes a áreas]
    └── Campos: id, user_id, area_id, is_active, assigned_at
```

### Tablas Modificadas

```
ticketing.tickets           [MODIFICADA]
├── + priority              (ENUM ticket_priority DEFAULT 'medium')
├── + area_id               (UUID FK → areas, ON DELETE SET NULL)
└── Índices: +2 para priority, +1 para area_id
```

---

## 🔧 Funciones PostgreSQL Nuevas

```sql
-- 1. Función para auto-escalada de prioridad
ticketing.escalate_unattended_tickets()
    Entrada:  (ninguna, automática desde scheduler)
    Salida:   TABLE(escalated_count INT)
    Propósito: Encuentra tickets OPEN sin respuesta en 24h y escala a HIGH

-- 2. (Opcional) Función para auto-asignar área según categoría
ticketing.auto_assign_area_by_category(category_id UUID)
    Entrada:  category_id
    Salida:   area_id (UUID)
    Propósito: Retorna el área recomendada para una categoría
```

---

## 🔔 Triggers Nuevos

```sql
-- 1. Actualizar updated_at en areas
trigger_update_areas_updated_at
    Tabla:    ticketing.areas
    Evento:   BEFORE UPDATE
    Función:  public.update_updated_at_column()

-- 2. (Opcional) Notificar agentes cuando se escala un ticket
trigger_notify_escalation
    Tabla:    ticketing.ticket_escalations
    Evento:   AFTER INSERT
    Función:  custom function para enviar notificación
```

---

## 📈 Índices Nuevos (Resumen)

### Para Prioridad
```sql
idx_tickets_priority
idx_tickets_priority_status
```

### Para Auto-Escalada
```sql
idx_escalations_ticket_id
idx_escalations_escalated_at
idx_escalations_reason
```

### Para Recordatorios
```sql
idx_reminders_ticket_id
idx_reminders_sent_at
idx_reminders_sent_by
idx_reminders_ticket_user
```

### Para Áreas
```sql
idx_areas_company_id
idx_areas_is_active
idx_areas_company_active
idx_agent_areas_user_id
idx_agent_areas_area_id
idx_agent_areas_active
idx_tickets_area_id
idx_tickets_area_status
```

**Total de índices nuevos:** ~16

---

## 📋 Orden de Migraciones Recomendado

### Migration #1: Prioridad (15 min)
```
1. Crear ENUM ticket_priority
2. Agregar columna priority a tickets (DEFAULT 'medium')
3. Crear índices para priority
```

### Migration #2: Auto-Escalada (20 min)
```
1. Crear ENUM ticket_escalation_reason
2. Crear tabla ticket_escalations
3. Crear función escalate_unattended_tickets()
4. Crear índices
```

### Migration #3: Recordatorios (15 min)
```
1. Crear tabla ticket_reminders
2. Crear índices
3. Agregar comentarios de auditoría
```

### Migration #4: Áreas (25 min)
```
1. Crear tabla areas
2. Crear tabla agent_areas
3. Agregar columna area_id a tickets
4. Crear triggers para updated_at
5. Crear índices
```

**Total estimado:** 75 minutos de migraciones

---

## 🔍 Análisis de Impacto

### Performance

| Aspecto | Impacto | Mitigación |
|---------|---------|-----------|
| Tamaño tabla tickets | +2 columnas | Mínimo (16 bytes por fila) |
| Nuevas tablas | +4 tablas medianas | Están en ticketing schema |
| Índices | +16 nuevos | Bien estructurados, evitan N+1 |
| Queries existentes | Ninguno | Todas las columnas nuevas son opcionales |

### Backward Compatibility

```
✅ Tickets existentes:
   - priority: null → migración asigna 'medium' a todos
   - area_id: null → queda NULL, búsquedas ignoran

✅ Empresas sin áreas:
   - area_id será NULL en todos los tickets
   - Tablas areas/agent_areas existen pero vacías
   - Cero impacto en lógica existente
```

---

## 🚀 Checklist de Implementación

### Pre-Migration
```
☐ Backup completo de BD producción
☐ Revisar el archivo actual de migraciones
☐ Probar migraciones en ambiente test
```

### Migration Script (Docker)
```bash
# Ejecutar las 4 migraciones en orden
docker compose exec app php artisan migrate --path=database/migrations/2025_11_26_add_priority.php
docker compose exec app php artisan migrate --path=database/migrations/2025_11_26_add_escalations.php
docker compose exec app php artisan migrate --path=database/migrations/2025_11_26_add_reminders.php
docker compose exec app php artisan migrate --path=database/migrations/2025_11_26_add_areas.php

# Verificar
docker compose exec app php artisan migrate:status
```

### Post-Migration
```
☐ Verificar estructura: \d ticketing.* en psql
☐ Validar índices: SELECT * FROM pg_indexes WHERE schemaname='ticketing';
☐ Verificar constraints: \d ticketing.tickets
☐ Tests unitarios de migraciones
☐ Clear cache: docker compose exec app php artisan optimize:clear
```

---

## 📐 Diagrama de Relaciones Actualizado

```
auth.users
│
├── ANTES:
│   └── user_roles
│       └── companies
│           └── tickets
│               └── ticket_responses
│
└── AHORA (con nuevas features):
    ├── user_roles
    │   └── companies
    │       ├── areas [NUEVA]
    │       │   ├── agent_areas [NUEVA]
    │       │   │   └── users (muchos a muchos)
    │       │   │
    │       │   └── tickets
    │       │
    │       └── tickets
    │           ├── priority [CAMPO NUEVO]
    │           ├── area_id [CAMPO NUEVO]
    │           ├── ticket_escalations [NUEVA]
    │           ├── ticket_reminders [NUEVA]
    │           └── ticket_responses
    │
    └── ticket_reminders [NUEVA]
        └── tickets
            └── sent_by_user_id (agente)
```

---

## 💾 Tamaño Estimado de BD

```
Incremento estimado:
├── Tablas nuevas: ~50 MB (inicialmente vacío)
├── Índices nuevos: ~20 MB
├── Tickets tabla (con 2 columnas): ~10 MB
└── Total: ~80 MB para millones de registros

Crecimiento mensual (10k tickets/mes):
├── ticket_escalations: ~100 KB/mes
├── ticket_reminders: ~500 KB/mes
└── Total: ~600 KB/mes
```

---

## ✅ Validación de Integridad

```sql
-- Verificar que no hay orphaned areas
SELECT a.id, a.name FROM ticketing.areas a
WHERE a.company_id NOT IN (SELECT id FROM business.companies);

-- Verificar que agent_areas tiene agentes válidos
SELECT aa.id FROM ticketing.agent_areas aa
WHERE aa.user_id NOT IN (SELECT id FROM auth.users WHERE status = 'active');

-- Verificar tickets con área pero empresa no usa áreas
SELECT t.id, t.area_id FROM ticketing.tickets t
WHERE t.area_id IS NOT NULL
AND t.company_id IN (
    SELECT c.id FROM business.companies c
    WHERE c.id NOT IN (SELECT DISTINCT company_id FROM ticketing.areas)
);
```

---

## 📝 Consideraciones Finales

### Lo que CAMBIA
- ✅ Tickets tabla: +2 columnas
- ✅ Creadas: 4 nuevas tablas
- ✅ Creados: 2 nuevos ENUMS
- ✅ Creados: 1 nueva función PostgreSQL
- ✅ Creados: 16 nuevos índices

### Lo que NO cambia
- ✅ Esquemas existentes (auth, business, ticketing)
- ✅ Tablas existentes (solo se extienden)
- ✅ Funciones existentes (se agregan nuevas)
- ✅ Triggers existentes (coexisten)

### Riesgos Mitigados
- ✅ Backward compatible: tickets sin prioridad → 'medium'
- ✅ Opcional: áreas pueden no usarse (area_id = NULL)
- ✅ Auditable: ticket_escalations registra todo
- ✅ Seguro: ON DELETE CASCADE/RESTRICT bien configurado

---

**Documento preparado:** Noviembre 26, 2025
**Versión:** 1.0
**Estado:** Listo para crear migraciones Laravel
