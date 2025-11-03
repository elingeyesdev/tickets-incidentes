# 📰 CONTENT MANAGEMENT API v2.0 - DOCUMENTACIÓN FINAL

> **Sistema**: Helpdesk Multi-Tenant  
> **Feature**: Content Management  
> **Versión**: 2.0 Final  
> **Base URL**: `/api/v1`  
> **Autenticación**: Bearer Token (JWT)  
> **Scheduling**: Redis Queue (Laravel Horizon)

---

## 📑 TABLA DE CONTENIDOS

1. [Arquitectura del Sistema](#arquitectura-del-sistema)
2. [Índice Completo de Endpoints](#índice-completo-de-endpoints)
3. [Autenticación y Contexto](#autenticación-y-contexto)
4. [Endpoints - Anuncios](#endpoints---anuncios)
5. [Endpoints - Artículos](#endpoints---artículos)
6. [Metadata por Tipo de Anuncio](#metadata-por-tipo-de-anuncio)
7. [Permisos y Visibilidad](#permisos-y-visibilidad)
8. [Códigos de Error](#códigos-de-error)
9. [Casos de Uso Completos](#casos-de-uso-completos)

---

## 🏗️ ARQUITECTURA DEL SISTEMA

### Filosofía de Diseño

**✅ Endpoints Separados por Tipo**: Cada tipo de anuncio tiene su propio endpoint de creación
- `/announcements/maintenance` - Mantenimientos programados
- `/announcements/incidents` - Incidentes y fallos
- `/announcements/news` - Noticias y actualizaciones
- `/announcements/alerts` - Alertas críticas

**✅ Scheduling con Redis**: La programación real se maneja en Redis Queue
- `scheduled_for` en metadata JSONB es solo para display
- Redis ejecuta `PublishAnnouncementJob` en el momento exacto
- No se necesitan Cron jobs ni polling

**✅ Company ID Inferido**: Backend infiere `company_id` del JWT token
- COMPANY_ADMIN solo puede crear contenido de su empresa
- No hay riesgo de manipulación de company_id en requests

**✅ Acción al Crear**: Un solo request para crear + draft/publish/schedule
- `action: "draft"` - Crea como borrador (default)
- `action: "publish"` - Publica inmediatamente
- `action: "schedule"` - Programa para después

---

## 📋 ÍNDICE COMPLETO DE ENDPOINTS

### 🔔 Anuncios (Announcements)

| Método | Endpoint | Descripción | Roles |
|--------|----------|-------------|-------|
| **Creación por Tipo** |
| POST | `/announcements/maintenance` | Crear anuncio de mantenimiento | 👨‍💼 COMPANY_ADMIN |
| POST | `/announcements/incidents` | Crear anuncio de incidente | 👨‍💼 COMPANY_ADMIN |
| POST | `/announcements/news` | Crear anuncio de noticia | 👨‍💼 COMPANY_ADMIN |
| POST | `/announcements/alerts` | Crear anuncio de alerta | 👨‍💼 COMPANY_ADMIN |
| **Gestión General** |
| GET | `/announcements` | Listar anuncios (cualquier tipo) | 👤 END_USER, 👨‍💼 ADMIN |
| GET | `/announcements/:id` | Ver anuncio específico | 👤 END_USER, 👨‍💼 ADMIN |
| GET | `/announcements/schemas` | Schemas de metadata por tipo | 👨‍💼 COMPANY_ADMIN |
| PUT | `/announcements/:id` | Actualizar anuncio | 👨‍💼 COMPANY_ADMIN |
| DELETE | `/announcements/:id` | Eliminar anuncio | 👨‍💼 COMPANY_ADMIN |
| **Acciones de Estado** |
| POST | `/announcements/:id/publish` | Publicar anuncio | 👨‍💼 COMPANY_ADMIN |
| POST | `/announcements/:id/schedule` | Programar anuncio | 👨‍💼 COMPANY_ADMIN |
| POST | `/announcements/:id/unschedule` | Desprogramar anuncio | 👨‍💼 COMPANY_ADMIN |
| POST | `/announcements/:id/archive` | Archivar anuncio | 👨‍💼 COMPANY_ADMIN |
| POST | `/announcements/:id/restore` | Restaurar archivado | 👨‍💼 COMPANY_ADMIN |
| **Acciones Específicas por Tipo** |
| POST | `/announcements/incidents/:id/resolve` | Marcar incidente como resuelto | 👨‍💼 COMPANY_ADMIN |
| POST | `/announcements/maintenance/:id/start` | Marcar inicio real de mantenimiento | 👨‍💼 COMPANY_ADMIN |
| POST | `/announcements/maintenance/:id/complete` | Marcar fin de mantenimiento | 👨‍💼 COMPANY_ADMIN |

### 📚 Artículos (Help Center)

| Método | Endpoint | Descripción | Roles |
|--------|----------|-------------|-------|
| GET | `/help-center/categories` | Listar 4 categorías globales | 🌐 Público |
| GET | `/help-center/articles` | Listar artículos | 👤 END_USER, 👨‍💼 ADMIN |
| GET | `/help-center/articles/:id` | Ver artículo (+ views count) | 👤 END_USER, 👨‍💼 ADMIN |
| POST | `/help-center/articles` | Crear artículo | 👨‍💼 COMPANY_ADMIN |
| PUT | `/help-center/articles/:id` | Actualizar artículo | 👨‍💼 COMPANY_ADMIN |
| POST | `/help-center/articles/:id/publish` | Publicar artículo | 👨‍💼 COMPANY_ADMIN |
| POST | `/help-center/articles/:id/unpublish` | Despublicar artículo | 👨‍💼 COMPANY_ADMIN |
| DELETE | `/help-center/articles/:id` | Eliminar artículo | 👨‍💼 COMPANY_ADMIN |

---

## 🔑 AUTENTICACIÓN Y CONTEXTO

### JWT Token Structure

```json
{
  "sub": "user-uuid-here",
  "role": "COMPANY_ADMIN",
  "company_id": "company-uuid-here",
  "exp": 1699000000
}
```

### Headers Requeridos

```http
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json
```

### ⚠️ Company ID Inferido (Seguridad)

**Backend infiere automáticamente el `company_id` del JWT token**. Un COMPANY_ADMIN **NO puede** especificar el company_id manualmente.

```php
// Backend (Controller)
$companyId = auth()->user()->company_id;  // Del JWT
$announcement = Announcement::create([
    'company_id' => $companyId,  // ← Inferido, no del request
    'author_id' => auth()->id(),
    // ...
]);
```

---

## 🔔 ENDPOINTS - ANUNCIOS

### 1. Listar Anuncios

```http
GET /api/v1/announcements
```

**Query Parameters**:

| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `status` | enum | - | `draft`, `scheduled`, `published`, `archived` |
| `type` | enum | - | `MAINTENANCE`, `INCIDENT`, `NEWS`, `ALERT` |
| `company_id` | uuid | - | Filtrar por empresa (solo PLATFORM_ADMIN) |
| `published_after` | date | - | Anuncios publicados después de esta fecha |
| `published_before` | date | - | Anuncios publicados antes de esta fecha |
| `sort` | string | `-published_at` | `-published_at`, `-created_at`, `title` |
| `page` | int | 1 | Número de página |
| `per_page` | int | 20 | Items por página (max: 100) |

**Reglas de Visibilidad**:
- **END_USER**: Solo ve anuncios `PUBLISHED` de empresas que sigue
- **AGENT**: Solo ve anuncios `PUBLISHED` de su empresa
- **COMPANY_ADMIN**: Ve TODOS los anuncios de su empresa (cualquier estado)
- **PLATFORM_ADMIN**: Ve todo (read-only)

**Ejemplo Request**:
```http
GET /api/v1/announcements?status=published&type=INCIDENT&sort=-published_at&per_page=10
Authorization: Bearer {token}
```

**Response 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "aa0e8400-e29b-41d4-a716-446655440001",
      "company_id": "550e8400-e29b-41d4-a716-446655440001",
      "company_name": "Tech Solutions Inc.",
      "author_id": "660e8400-e29b-41d4-a716-446655440010",
      "author_name": "Carlos Mendoza",
      "title": "Sistema de Pagos Restaurado",
      "content": "El sistema de pagos ha sido completamente restaurado...",
      "type": "INCIDENT",
      "status": "PUBLISHED",
      "metadata": {
        "urgency": "HIGH",
        "is_resolved": true,
        "resolved_at": "2025-11-02T20:30:00Z",
        "resolution_content": "Problema en servidor de BD corregido",
        "started_at": "2025-11-02T18:45:00Z",
        "ended_at": "2025-11-02T20:30:00Z",
        "affected_services": ["payments", "billing"]
      },
      "published_at": "2025-11-02T20:35:00Z",
      "created_at": "2025-11-02T19:00:00Z",
      "updated_at": "2025-11-02T20:35:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1,
    "from": 1,
    "to": 1
  }
}
```

**Response 403 Forbidden** (Usuario no sigue la empresa):
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOLLOWING_COMPANY",
    "message": "No puedes ver anuncios de empresas que no sigues",
    "company_id": "550e8400-e29b-41d4-a716-446655440001"
  }
}
```

---

### 2. Obtener Schema de Metadata

```http
GET /api/v1/announcements/schemas
```

**Descripción**: Retorna la estructura de metadata para cada tipo de anuncio. El frontend usa esto para construir formularios dinámicos.

**Response 200 OK**:
```json
{
  "success": true,
  "data": {
    "MAINTENANCE": {
      "required": ["urgency", "scheduled_start", "scheduled_end", "is_emergency"],
      "optional": ["actual_start", "actual_end", "affected_services"],
      "fields": {
        "urgency": {
          "type": "enum",
          "values": ["LOW", "MEDIUM", "HIGH"],
          "label": "Urgencia",
          "description": "Nivel de urgencia del mantenimiento"
        },
        "scheduled_start": {
          "type": "datetime",
          "label": "Inicio Planeado",
          "description": "Cuándo inicia el mantenimiento"
        },
        "scheduled_end": {
          "type": "datetime",
          "label": "Fin Planeado",
          "description": "Cuándo termina el mantenimiento",
          "after": "scheduled_start"
        },
        "is_emergency": {
          "type": "boolean",
          "label": "¿Es Emergencia?",
          "default": false
        },
        "actual_start": {
          "type": "datetime",
          "label": "Inicio Real",
          "description": "Cuándo realmente inició (opcional)"
        },
        "actual_end": {
          "type": "datetime",
          "label": "Fin Real",
          "description": "Cuándo realmente terminó (opcional)"
        },
        "affected_services": {
          "type": "array",
          "items": "string",
          "label": "Servicios Afectados",
          "max_items": 20,
          "placeholder": ["payments", "api", "reports"]
        }
      }
    },
    "INCIDENT": {
      "required": ["urgency", "is_resolved", "started_at"],
      "optional": ["resolved_at", "resolution_content", "ended_at", "affected_services"],
      "fields": {
        "urgency": {
          "type": "enum",
          "values": ["LOW", "MEDIUM", "HIGH", "CRITICAL"],
          "label": "Severidad"
        },
        "is_resolved": {
          "type": "boolean",
          "label": "¿Resuelto?",
          "default": false
        },
        "started_at": {
          "type": "datetime",
          "label": "Inicio del Incidente"
        },
        "ended_at": {
          "type": "datetime",
          "label": "Fin del Incidente"
        },
        "resolved_at": {
          "type": "datetime",
          "label": "Fecha de Resolución",
          "required_if": "is_resolved=true"
        },
        "resolution_content": {
          "type": "text",
          "max_length": 1000,
          "label": "Descripción de la Resolución",
          "required_if": "is_resolved=true"
        },
        "affected_services": {
          "type": "array",
          "items": "string",
          "label": "Servicios Afectados"
        }
      }
    },
    "NEWS": {
      "required": ["news_type", "target_audience", "summary"],
      "optional": ["call_to_action"],
      "fields": {
        "news_type": {
          "type": "enum",
          "values": ["feature_release", "policy_update", "general_update"],
          "label": "Tipo de Noticia"
        },
        "target_audience": {
          "type": "array",
          "items": "enum",
          "values": ["users", "agents", "admins"],
          "label": "Audiencia Objetivo",
          "min_items": 1
        },
        "summary": {
          "type": "text",
          "min_length": 10,
          "max_length": 500,
          "label": "Resumen"
        },
        "call_to_action": {
          "type": "object",
          "label": "Llamado a la Acción (opcional)",
          "properties": {
            "text": {
              "type": "string",
              "max_length": 50,
              "placeholder": "Leer Más"
            },
            "url": {
              "type": "url",
              "placeholder": "https://docs.company.com/feature"
            }
          }
        }
      }
    },
    "ALERT": {
      "required": ["urgency", "alert_type", "message", "action_required", "started_at"],
      "optional": ["action_description", "affected_services", "ended_at"],
      "fields": {
        "urgency": {
          "type": "enum",
          "values": ["HIGH", "CRITICAL"],
          "label": "Urgencia",
          "description": "Solo alertas HIGH o CRITICAL permitidas"
        },
        "alert_type": {
          "type": "enum",
          "values": ["security", "system", "service", "compliance"],
          "label": "Tipo de Alerta"
        },
        "message": {
          "type": "text",
          "min_length": 10,
          "max_length": 500,
          "label": "Mensaje de Alerta"
        },
        "action_required": {
          "type": "boolean",
          "label": "¿Requiere Acción del Usuario?",
          "default": false
        },
        "action_description": {
          "type": "text",
          "max_length": 300,
          "label": "Descripción de la Acción",
          "required_if": "action_required=true"
        },
        "started_at": {
          "type": "datetime",
          "label": "Inicio de la Alerta"
        },
        "ended_at": {
          "type": "datetime",
          "label": "Fin de la Alerta (opcional)"
        },
        "affected_services": {
          "type": "array",
          "items": "string",
          "label": "Servicios Afectados"
        }
      }
    }
  }
}
```

---

### 3. Crear Anuncio de Mantenimiento

```http
POST /api/v1/announcements/maintenance
```

**Request Body**:
```json
{
  "title": "Mantenimiento de Base de Datos - Sábado 9 Nov",
  "content": "Realizaremos mantenimiento en nuestra base de datos principal este sábado de 10:00 a 14:00. Durante este período:\n\n- El acceso a reportes históricos estará limitado\n- La creación de nuevos tickets funcionará normalmente\n- El panel de analíticas estará deshabilitado\n\nAgradecemos su comprensión.",
  "urgency": "MEDIUM",
  "scheduled_start": "2025-11-09T10:00:00Z",
  "scheduled_end": "2025-11-09T14:00:00Z",
  "is_emergency": false,
  "affected_services": ["reports", "analytics"],
  
  "action": "schedule",
  "scheduled_for": "2025-11-08T08:00:00Z"
}
```

**Campos del Request**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `title` | string | ✅ | 3-255 caracteres |
| `content` | string | ✅ | 10-5000 caracteres |
| `urgency` | enum | ✅ | LOW, MEDIUM, HIGH |
| `scheduled_start` | datetime | ✅ | Cuándo inicia el mantenimiento |
| `scheduled_end` | datetime | ✅ | Cuándo termina (después de start) |
| `is_emergency` | boolean | ✅ | ¿Es mantenimiento de emergencia? |
| `affected_services` | array | ❌ | Lista de servicios afectados (max 20) |
| `action` | enum | ❌ | `draft` (default), `publish`, `schedule` |
| `scheduled_for` | datetime | ⚠️ | Requerido si `action=schedule` |

**Response 201 Created** (action=schedule):
```json
{
  "success": true,
  "message": "Mantenimiento programado para publicación el 2025-11-08 a las 08:00 AM",
  "data": {
    "id": "aa0e8400-new-uuid",
    "company_id": "550e8400-e29b-41d4-a716-446655440001",
    "author_id": "660e8400-e29b-41d4-a716-446655440010",
    "author_name": "Carlos Mendoza",
    "title": "Mantenimiento de Base de Datos - Sábado 9 Nov",
    "type": "MAINTENANCE",
    "status": "SCHEDULED",
    "metadata": {
      "urgency": "MEDIUM",
      "scheduled_start": "2025-11-09T10:00:00Z",
      "scheduled_end": "2025-11-09T14:00:00Z",
      "is_emergency": false,
      "affected_services": ["reports", "analytics"],
      "scheduled_for": "2025-11-08T08:00:00Z"
    },
    "published_at": null,
    "created_at": "2025-11-02T22:00:00Z",
    "updated_at": "2025-11-02T22:00:00Z"
  }
}
```

**Response 201 Created** (action=publish):
```json
{
  "success": true,
  "message": "Mantenimiento publicado exitosamente",
  "data": {
    "id": "aa0e8400-new-uuid",
    "status": "PUBLISHED",
    "published_at": "2025-11-02T22:00:23Z",
    // ... resto de campos
  }
}
```

**Response 201 Created** (action=draft o sin action):
```json
{
  "success": true,
  "message": "Mantenimiento creado como borrador",
  "data": {
    "id": "aa0e8400-new-uuid",
    "status": "DRAFT",
    "published_at": null,
    // ... resto de campos
  }
}
```

---

### 4. Crear Anuncio de Incidente

```http
POST /api/v1/announcements/incidents
```

**Request Body** (Publicación inmediata):
```json
{
  "title": "⚠️ Incidente: Sistema de Login No Disponible",
  "content": "Estamos experimentando problemas técnicos con el sistema de autenticación. Nuestro equipo está trabajando para resolver el issue lo antes posible.\n\nEstado: Investigando\nInicio: 18:45\nServicios Afectados: Login, API\n\nActualizaremos este anuncio cuando tengamos más información.",
  "urgency": "CRITICAL",
  "is_resolved": false,
  "started_at": "2025-11-02T18:45:00Z",
  "affected_services": ["login", "api"],
  
  "action": "publish"
}
```

**Campos Específicos de Incident**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `urgency` | enum | ✅ | LOW, MEDIUM, HIGH, CRITICAL |
| `is_resolved` | boolean | ✅ | ¿El incidente está resuelto? |
| `started_at` | datetime | ✅ | Cuándo inició el incidente |
| `ended_at` | datetime | ❌ | Cuándo terminó (si ya terminó) |
| `resolved_at` | datetime | ⚠️ | Requerido si `is_resolved=true` |
| `resolution_content` | string | ⚠️ | Requerido si `is_resolved=true` (max 1000) |
| `affected_services` | array | ❌ | Servicios impactados |

**Response 201 Created**:
```json
{
  "success": true,
  "message": "Incidente publicado exitosamente",
  "data": {
    "id": "aa0e8400-incident-1",
    "type": "INCIDENT",
    "status": "PUBLISHED",
    "metadata": {
      "urgency": "CRITICAL",
      "is_resolved": false,
      "started_at": "2025-11-02T18:45:00Z",
      "affected_services": ["login", "api"]
    },
    "published_at": "2025-11-02T18:46:12Z"
  }
}
```

---

### 5. Crear Anuncio de Noticia

```http
POST /api/v1/announcements/news
```

**Request Body**:
```json
{
  "title": "Nueva Feature: Exportación de Tickets a Excel",
  "content": "Nos complace anunciar que ahora puedes exportar tus tickets a formato Excel directamente desde el panel de control.\n\n## Características\n\n- Exporta tickets individuales o en lote\n- Incluye toda la información: mensajes, adjuntos, historial\n- Formato Excel compatible con todas las versiones\n\n## Cómo usarlo\n\n1. Ve a la lista de tickets\n2. Selecciona los tickets que deseas exportar\n3. Haz clic en 'Exportar a Excel'\n4. Descarga el archivo generado\n\n¡Esperamos que esta feature mejore tu productividad!",
  "news_type": "feature_release",
  "target_audience": ["users", "agents"],
  "summary": "Ahora puedes exportar tus tickets a Excel con un solo clic",
  "call_to_action": {
    "text": "Ver Guía Completa",
    "url": "https://docs.company.com/export-tickets-excel"
  },
  
  "action": "publish"
}
```

**Campos Específicos de News**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `news_type` | enum | ✅ | feature_release, policy_update, general_update |
| `target_audience` | array | ✅ | ["users", "agents", "admins"] (min 1) |
| `summary` | string | ✅ | Resumen breve (10-500 chars) |
| `call_to_action` | object | ❌ | {text: string, url: string} |

---

### 6. Crear Anuncio de Alerta

```http
POST /api/v1/announcements/alerts
```

**Request Body**:
```json
{
  "title": "🚨 Alerta de Seguridad: Actualización de Contraseña Requerida",
  "content": "Hemos detectado una vulnerabilidad de seguridad que podría afectar algunas cuentas.\n\nPor precaución, te pedimos que actualices tu contraseña en las próximas 24 horas.\n\n## ¿Qué hacer?\n\n1. Ve a Configuración > Seguridad\n2. Haz clic en 'Cambiar Contraseña'\n3. Usa una contraseña fuerte y única\n\nSi no actualizas tu contraseña en 24 horas, tu cuenta será temporalmente suspendida por seguridad.",
  "urgency": "CRITICAL",
  "alert_type": "security",
  "message": "Actualización de contraseña requerida por vulnerabilidad de seguridad detectada",
  "action_required": true,
  "action_description": "Cambia tu contraseña en las próximas 24 horas",
  "started_at": "2025-11-02T22:00:00Z",
  "affected_services": ["authentication"],
  
  "action": "publish"
}
```

**Campos Específicos de Alert**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `urgency` | enum | ✅ | HIGH, CRITICAL (solo alertas importantes) |
| `alert_type` | enum | ✅ | security, system, service, compliance |
| `message` | string | ✅ | Mensaje de alerta (10-500 chars) |
| `action_required` | boolean | ✅ | ¿Requiere acción del usuario? |
| `action_description` | string | ⚠️ | Requerido si `action_required=true` (max 300) |
| `started_at` | datetime | ✅ | Cuándo inició la alerta |
| `ended_at` | datetime | ❌ | Cuándo terminó (si terminó) |

---

### 7. Actualizar Anuncio

```http
PUT /api/v1/announcements/:id
```

**Restricciones**:
- Solo se puede editar si está en estado `DRAFT` o `SCHEDULED`
- No se puede editar si está `PUBLISHED` o `ARCHIVED`

**Request Body** (parcial):
```json
{
  "title": "Título actualizado",
  "urgency": "HIGH",
  "affected_services": ["all_services"]
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Anuncio actualizado exitosamente",
  "data": {
    "id": "aa0e8400-uuid",
    "title": "Título actualizado",
    "updated_at": "2025-11-02T22:30:00Z"
  }
}
```

**Response 403 Forbidden** (ya publicado):
```json
{
  "success": false,
  "error": {
    "code": "ANNOUNCEMENT_NOT_EDITABLE",
    "message": "No se puede editar un anuncio que ya está publicado",
    "current_status": "PUBLISHED"
  }
}
```

---

### 8. Publicar Anuncio (Desde Borrador)

```http
POST /api/v1/announcements/:id/publish
```

**Descripción**: Publica un anuncio en estado `DRAFT` o `SCHEDULED`

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Anuncio publicado exitosamente",
  "data": {
    "id": "aa0e8400-uuid",
    "status": "PUBLISHED",
    "published_at": "2025-11-02T22:35:00Z"
  }
}
```

---

### 9. Programar Anuncio (Desde Borrador)

```http
POST /api/v1/announcements/:id/schedule
```

**Request Body**:
```json
{
  "scheduled_for": "2025-11-09T08:00:00Z"
}
```

**Validaciones**:
- `scheduled_for` debe ser mínimo 5 minutos en el futuro
- `scheduled_for` no puede ser más de 1 año en el futuro

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Anuncio programado para publicación el 2025-11-09 a las 08:00 AM",
  "data": {
    "id": "aa0e8400-uuid",
    "status": "SCHEDULED",
    "metadata": {
      // ... metadata existente
      "scheduled_for": "2025-11-09T08:00:00Z"
    }
  }
}
```

**Arquitectura Backend** (Redis Queue):
```php
// Service encolará PublishAnnouncementJob en Redis
PublishAnnouncementJob::dispatch($announcement)
    ->delay(Carbon::parse($scheduledFor));

// Redis ejecutará automáticamente en ese momento
// No se necesitan Cron jobs
```

---

### 10. Desprogramar Anuncio

```http
POST /api/v1/announcements/:id/unschedule
```

**Descripción**: Regresa un anuncio `SCHEDULED` a estado `DRAFT` y cancela el job en Redis

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Anuncio desprogramado y regresado a borrador",
  "data": {
    "id": "aa0e8400-uuid",
    "status": "DRAFT",
    "metadata": {
      // scheduled_for removido
    }
  }
}
```

---

### 11. Archivar Anuncio

```http
POST /api/v1/announcements/:id/archive
```

**Restricción**: Solo se pueden archivar anuncios `PUBLISHED`

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Anuncio archivado exitosamente",
  "data": {
    "id": "aa0e8400-uuid",
    "status": "ARCHIVED",
    "updated_at": "2025-11-02T23:00:00Z"
  }
}
```

---

### 12. Restaurar Anuncio Archivado

```http
POST /api/v1/announcements/:id/restore
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Anuncio restaurado a borrador",
  "data": {
    "id": "aa0e8400-uuid",
    "status": "DRAFT"
  }
}
```

---

### 13. Eliminar Anuncio

```http
DELETE /api/v1/announcements/:id
```

**Restricción**: Solo se pueden eliminar anuncios en `DRAFT` o `ARCHIVED`

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Anuncio eliminado permanentemente"
}
```

**Response 403 Forbidden**:
```json
{
  "success": false,
  "error": {
    "code": "CANNOT_DELETE_PUBLISHED",
    "message": "No se puede eliminar un anuncio publicado. Archívalo primero.",
    "current_status": "PUBLISHED"
  }
}
```

---

### 14. Resolver Incidente (Acción Específica)

```http
POST /api/v1/announcements/incidents/:id/resolve
```

**Request Body**:
```json
{
  "resolution_content": "Se identificó y corrigió un error de configuración en el servidor de autenticación. Se implementaron medidas preventivas para evitar recurrencia.",
  "ended_at": "2025-11-02T20:30:00Z"
}
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Incidente marcado como resuelto",
  "data": {
    "id": "aa0e8400-incident",
    "metadata": {
      "is_resolved": true,
      "resolved_at": "2025-11-02T20:30:23Z",
      "resolution_content": "Se identificó y corrigió...",
      "ended_at": "2025-11-02T20:30:00Z"
    }
  }
}
```

---

### 15. Marcar Inicio Real de Mantenimiento

```http
POST /api/v1/announcements/maintenance/:id/start
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Inicio de mantenimiento registrado",
  "data": {
    "id": "aa0e8400-maintenance",
    "metadata": {
      "actual_start": "2025-11-09T09:58:00Z"
    }
  }
}
```

---

### 16. Marcar Fin de Mantenimiento

```http
POST /api/v1/announcements/maintenance/:id/complete
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Mantenimiento completado",
  "data": {
    "id": "aa0e8400-maintenance",
    "metadata": {
      "actual_end": "2025-11-09T13:45:00Z"
    }
  }
}
```

---

## 📚 ENDPOINTS - ARTÍCULOS

### 17. Listar Categorías (Global)

```http
GET /api/v1/help-center/categories
```

**⚠️ Público**: No requiere autenticación

**Response 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "cc0e8400-1",
      "code": "ACCOUNT_PROFILE",
      "name": "Account & Profile",
      "description": "Gestión de cuenta y perfil de usuario"
    },
    {
      "id": "cc0e8400-2",
      "code": "SECURITY_PRIVACY",
      "name": "Security & Privacy",
      "description": "Seguridad y privacidad de datos"
    },
    {
      "id": "cc0e8400-3",
      "code": "BILLING_PAYMENTS",
      "name": "Billing & Payments",
      "description": "Facturación y pagos"
    },
    {
      "id": "cc0e8400-4",
      "code": "TECHNICAL_SUPPORT",
      "name": "Technical Support",
      "description": "Soporte técnico y troubleshooting"
    }
  ]
}
```

---

### 18. Listar Artículos

```http
GET /api/v1/help-center/articles
```

**Query Parameters**:

| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `company_id` | uuid | - | Filtrar por empresa |
| `status` | enum | published | `draft`, `published` |
| `category` | string | - | Código de categoría (ej: `SECURITY_PRIVACY`) |
| `search` | string | - | Búsqueda en título y contenido |
| `sort` | string | `-created_at` | `-created_at`, `-views`, `title` |
| `page` | int | 1 | Número de página |
| `per_page` | int | 20 | Items por página (max: 50) |

**Reglas de Visibilidad**:
- **END_USER**: Solo ve artículos `PUBLISHED` de empresas que sigue
- **COMPANY_ADMIN**: Ve todos los artículos de su empresa (DRAFT + PUBLISHED)

**Ejemplo Request**:
```http
GET /api/v1/help-center/articles?company_id=550e8400-e29b&category=SECURITY_PRIVACY&sort=-views
Authorization: Bearer {token}
```

**Response 200 OK**:
```json
{
  "success": true,
  "data": [
    {
      "id": "bb0e8400-1",
      "company_id": "550e8400-1",
      "company_name": "Tech Solutions Inc.",
      "author_id": "660e8400-10",
      "author_name": "Carlos Mendoza",
      "category_id": "cc0e8400-2",
      "category_code": "SECURITY_PRIVACY",
      "category_name": "Security & Privacy",
      "title": "Cómo cambiar tu contraseña",
      "excerpt": "Guía paso a paso para cambiar tu contraseña de forma segura",
      "status": "PUBLISHED",
      "views_count": 1248,
      "published_at": "2024-10-15T10:00:00Z",
      "created_at": "2024-10-14T15:30:00Z",
      "updated_at": "2024-10-15T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

---

### 19. Ver Artículo (Incrementa Views)

```http
GET /api/v1/help-center/articles/:id
```

**⚠️ Side Effect**: Incrementa automáticamente `views_count` en 1 (solo para artículos PUBLISHED)

**Response 200 OK**:
```json
{
  "success": true,
  "data": {
    "id": "bb0e8400-1",
    "company_id": "550e8400-1",
    "company_name": "Tech Solutions Inc.",
    "author_name": "Carlos Mendoza",
    "category_code": "SECURITY_PRIVACY",
    "category_name": "Security & Privacy",
    "title": "Cómo cambiar tu contraseña",
    "excerpt": "Guía paso a paso para cambiar tu contraseña de forma segura",
    "content": "Para cambiar tu contraseña, sigue estos pasos:\n\n1. Ve a **Configuración > Seguridad**\n2. Haz clic en **'Cambiar Contraseña'**\n3. Ingresa tu contraseña actual\n4. Ingresa tu nueva contraseña (mínimo 8 caracteres)\n5. Confirma tu nueva contraseña\n6. Haz clic en **'Guardar Cambios'**\n\nRecibirás un email de confirmación.\n\n## Recomendaciones de Seguridad\n\n- Usa una contraseña única que no uses en otros sitios\n- Combina letras mayúsculas, minúsculas, números y símbolos\n- Evita información personal obvia\n- Considera usar un gestor de contraseñas",
    "status": "PUBLISHED",
    "views_count": 1249,
    "published_at": "2024-10-15T10:00:00Z",
    "created_at": "2024-10-14T15:30:00Z",
    "updated_at": "2024-10-15T10:00:00Z"
  }
}
```

---

### 20. Crear Artículo

```http
POST /api/v1/help-center/articles
```

**Request Body**:
```json
{
  "category_id": "cc0e8400-2",
  "title": "Autenticación de Dos Factores (2FA) - Guía Completa",
  "excerpt": "Protege tu cuenta con una capa adicional de seguridad usando 2FA",
  "content": "La autenticación de dos factores (2FA) añade una capa extra de seguridad a tu cuenta.\n\n## ¿Qué es 2FA?\n\n2FA requiere dos formas de verificación:\n1. Algo que sabes (tu contraseña)\n2. Algo que tienes (tu teléfono)\n\n## Cómo activar 2FA\n\n1. Ve a **Configuración > Seguridad**\n2. Haz clic en **'Activar 2FA'**\n3. Escanea el código QR con Google Authenticator\n4. Ingresa el código de 6 dígitos para confirmar\n5. Guarda tus códigos de respaldo en un lugar seguro\n\n## Aplicaciones Recomendadas\n\n- Google Authenticator\n- Microsoft Authenticator\n- Authy\n\n⚠️ **Importante**: Guarda tus códigos de respaldo. Los necesitarás si pierdes acceso a tu dispositivo.",
  
  "action": "draft"
}
```

**Campos**:

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `category_id` | uuid | ✅ | ID de categoría (una de las 4 globales) |
| `title` | string | ✅ | 3-255 caracteres, único por empresa |
| `excerpt` | string | ❌ | Resumen breve (max 500 chars) |
| `content` | string | ✅ | Contenido Markdown (50-20000 chars) |
| `action` | enum | ❌ | `draft` (default), `publish` |

**Response 201 Created**:
```json
{
  "success": true,
  "message": "Artículo creado como borrador",
  "data": {
    "id": "bb0e8400-new",
    "category_id": "cc0e8400-2",
    "title": "Autenticación de Dos Factores (2FA) - Guía Completa",
    "status": "DRAFT",
    "views_count": 0,
    "published_at": null,
    "created_at": "2025-11-02T23:00:00Z"
  }
}
```

---

### 21. Actualizar Artículo

```http
PUT /api/v1/help-center/articles/:id
```

**⚠️ Se puede actualizar en cualquier estado** (DRAFT o PUBLISHED)

**Request Body** (parcial):
```json
{
  "title": "Título actualizado",
  "content": "Contenido actualizado..."
}
```

---

### 22. Publicar Artículo

```http
POST /api/v1/help-center/articles/:id/publish
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Artículo publicado exitosamente",
  "data": {
    "id": "bb0e8400-uuid",
    "status": "PUBLISHED",
    "published_at": "2025-11-02T23:10:00Z"
  }
}
```

---

### 23. Despublicar Artículo

```http
POST /api/v1/help-center/articles/:id/unpublish
```

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Artículo despublicado y regresado a borrador",
  "data": {
    "id": "bb0e8400-uuid",
    "status": "DRAFT",
    "published_at": null
  }
}
```

---

### 24. Eliminar Artículo

```http
DELETE /api/v1/help-center/articles/:id
```

**Restricción**: Solo se pueden eliminar artículos en `DRAFT`

**Response 200 OK**:
```json
{
  "success": true,
  "message": "Artículo eliminado permanentemente"
}
```

---

## 🔒 PERMISOS Y VISIBILIDAD

### Matriz de Permisos - Anuncios

| Operación | END_USER | AGENT | COMPANY_ADMIN | PLATFORM_ADMIN |
|-----------|:--------:|:-----:|:-------------:|:--------------:|
| Listar PUBLISHED (empresas seguidas) | ✅ | ✅ | ✅ | ✅ |
| Listar todos estados (su empresa) | ❌ | ❌ | ✅ | ✅ (read) |
| Ver PUBLISHED | ✅ | ✅ | ✅ | ✅ |
| Ver DRAFT/SCHEDULED | ❌ | ❌ | ✅ | ✅ |
| Crear | ❌ | ❌ | ✅ | ❌ |
| Actualizar | ❌ | ❌ | ✅ | ❌ |
| Publicar/Programar | ❌ | ❌ | ✅ | ❌ |
| Archivar/Eliminar | ❌ | ❌ | ✅ | ❌ |

### Matriz de Permisos - Artículos

| Operación | END_USER | AGENT | COMPANY_ADMIN | PLATFORM_ADMIN |
|-----------|:--------:|:-----:|:-------------:|:--------------:|
| Ver categorías | ✅ Público | ✅ Público | ✅ Público | ✅ Público |
| Listar PUBLISHED (empresas seguidas) | ✅ | ✅ | ✅ | ✅ |
| Listar DRAFT (su empresa) | ❌ | ❌ | ✅ | ✅ (read) |
| Ver PUBLISHED | ✅ | ✅ | ✅ | ✅ |
| Ver DRAFT | ❌ | ❌ | ✅ | ✅ |
| Crear/Actualizar | ❌ | ❌ | ✅ | ❌ |
| Publicar/Eliminar | ❌ | ❌ | ✅ | ❌ |

---

## 🚨 CÓDIGOS DE ERROR

### Autenticación/Permisos (400-403)

```json
{
  "success": false,
  "error": {
    "code": "UNAUTHENTICATED",
    "message": "Token inválido o expirado"
  }
}
```

```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_PERMISSIONS",
    "message": "No tienes permisos para esta operación",
    "required_role": "COMPANY_ADMIN",
    "current_role": "END_USER"
  }
}
```

```json
{
  "success": false,
  "error": {
    "code": "NOT_FOLLOWING_COMPANY",
    "message": "No puedes ver contenido de empresas que no sigues",
    "company_id": "550e8400-..."
  }
}
```

### Anuncios (400-404)

```json
{
  "success": false,
  "error": {
    "code": "ANNOUNCEMENT_NOT_FOUND",
    "message": "Anuncio no encontrado",
    "announcement_id": "aa0e8400-..."
  }
}
```

```json
{
  "success": false,
  "error": {
    "code": "INVALID_METADATA",
    "message": "Metadata inválida para tipo MAINTENANCE",
    "details": {
      "scheduled_start": ["El campo scheduled_start es requerido"],
      "scheduled_end": ["debe ser posterior a scheduled_start"]
    }
  }
}
```

```json
{
  "success": false,
  "error": {
    "code": "ANNOUNCEMENT_NOT_EDITABLE",
    "message": "No se puede editar un anuncio publicado",
    "current_status": "PUBLISHED"
  }
}
```

```json
{
  "success": false,
  "error": {
    "code": "INVALID_SCHEDULE_DATE",
    "message": "La fecha de programación debe ser al menos 5 minutos en el futuro",
    "provided_date": "2025-11-02T22:00:00Z",
    "minimum_date": "2025-11-02T22:10:00Z"
  }
}
```

### Artículos (400-404)

```json
{
  "success": false,
  "error": {
    "code": "ARTICLE_NOT_FOUND",
    "message": "Artículo no encontrado",
    "article_id": "bb0e8400-..."
  }
}
```

```json
{
  "success": false,
  "error": {
    "code": "DUPLICATE_ARTICLE_TITLE",
    "message": "Ya existe un artículo con este título en tu empresa",
    "title": "Cómo cambiar tu contraseña"
  }
}
```

```json
{
  "success": false,
  "error": {
    "code": "CANNOT_DELETE_PUBLISHED_ARTICLE",
    "message": "No se puede eliminar un artículo publicado. Despublícalo primero.",
    "current_status": "PUBLISHED"
  }
}
```

---

## 💡 CASOS DE USO COMPLETOS

### Caso 1: Incidente Urgente (1 Request)

**Contexto**: El sistema de login falló, necesito publicar un incidente YA

```http
POST /api/v1/announcements/incidents
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "⚠️ Sistema de Login No Disponible",
  "content": "Estamos experimentando problemas con autenticación. Trabajando en solución.",
  "urgency": "CRITICAL",
  "is_resolved": false,
  "started_at": "2025-11-02T18:45:00Z",
  "affected_services": ["login", "api"],
  "action": "publish"
}
```

**✅ Resultado**: Anuncio creado y publicado en 1 solo request

---

### Caso 2: Mantenimiento Programado

**Contexto**: Admin planea mantenimiento para el próximo sábado

**Paso 1: Crear y programar (1 request)**
```http
POST /api/v1/announcements/maintenance

{
  "title": "Mantenimiento BD - Sábado 9 Nov",
  "content": "...",
  "urgency": "MEDIUM",
  "scheduled_start": "2025-11-09T10:00:00Z",
  "scheduled_end": "2025-11-09T14:00:00Z",
  "is_emergency": false,
  "action": "schedule",
  "scheduled_for": "2025-11-08T08:00:00Z"
}
```

**Paso 2: Backend encola en Redis**
```php
PublishAnnouncementJob::dispatch($announcement)
    ->delay(Carbon::parse('2025-11-08 08:00:00'));
```

**Paso 3: Redis ejecuta automáticamente el viernes a las 08:00**
- Cambia status → PUBLISHED
- Actualiza published_at
- Dispara evento AnnouncementPublished

**Paso 4: Usuarios ven el anuncio**

---

### Caso 3: Usuario Busca Ayuda

**Contexto**: Usuario necesita cambiar su contraseña

**Paso 1: Buscar artículos**
```http
GET /api/v1/help-center/articles?search=contraseña&company_id=550e8400
```

**Paso 2: Ver artículo**
```http
GET /api/v1/help-center/articles/bb0e8400-1
```

**✅ Side Effect**: `views_count` incrementa automáticamente

---

### Caso 4: Admin Gestiona Incidente en Tiempo Real

**Paso 1: Crear y publicar incidente**
```http
POST /api/v1/announcements/incidents
{
  "title": "Problemas de Login",
  "urgency": "CRITICAL",
  "is_resolved": false,
  "started_at": "2025-11-02T18:45:00Z",
  "action": "publish"
}
```

**Paso 2: Una hora después, resolver**
```http
POST /api/v1/announcements/incidents/{id}/resolve
{
  "resolution_content": "Error de configuración corregido",
  "ended_at": "2025-11-02T20:30:00Z"
}
```

**Paso 3: Actualizar anuncio con resolución**
```http
PUT /api/v1/announcements/{id}
{
  "title": "✅ Resuelto: Problemas de Login"
}
```

**Paso 4: Archivar después de 24h**
```http
POST /api/v1/announcements/{id}/archive
```

---

## 📊 DIAGRAMAS DE FLUJO

### Estados de Anuncios

```
DRAFT ─┬─> [action=publish] ──────> PUBLISHED ──> ARCHIVED
       │                                │
       └─> [action=schedule] ──> SCHEDULED    │
                 │                     │       │
                 │                     │       │
            [unschedule]          [Redis]     │
                 │                  auto      │
                 ▼                     │       │
               DRAFT <─────────────────┘       │
                 ▲                             │
                 │                             │
                 └─────────[restore]───────────┘
```

### Estados de Artículos

```
DRAFT ←────────────> PUBLISHED
      [publish]      [unpublish]
```

---

## 🎯 RESUMEN EJECUTIVO

### ✅ Decisiones de Diseño

1. **Endpoints separados por tipo** → Validaciones limpias, código mantenible
2. **Campo `action` al crear** → 1 request para publicar/programar
3. **scheduled_for en metadata JSONB** → Redis maneja scheduling real
4. **Company ID inferido del JWT** → Seguridad garantizada
5. **Visibilidad por seguimiento** → Solo empresas que sigues

### 🚀 Ventajas del Sistema

- ✅ **Seguro**: Company ID no manipulable
- ✅ **Eficiente**: Redis maneja scheduling (no polling)
- ✅ **Flexible**: 3 flujos en 1 request (draft/publish/schedule)
- ✅ **Escalable**: Arquitectura limpia y mantenible
- ✅ **Type-safe**: Validaciones específicas por tipo

### 📈 Performance

- Scheduling: Redis Queue (sin Cron jobs)
- Visibilidad: Validación en middleware
- Views: Incremento atómico en BD
- Paginación: 20 items default, 100 max

---

**Fin de la Documentación v2.0 Final** 🎉