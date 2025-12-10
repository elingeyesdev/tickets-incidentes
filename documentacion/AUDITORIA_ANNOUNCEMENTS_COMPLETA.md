# AUDITORÍA COMPLETA: SISTEMA DE ANNOUNCEMENTS

## RESUMEN EJECUTIVO

Sistema multi-tipo de anuncios empresariales con 4 tipos especializados (MAINTENANCE, INCIDENT, NEWS, ALERT), 4 estados de publicación (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED), metadata dinámica tipo-específica con validación estricta, y gestión completa de ciclo de vida.

**Tabla:** `company_announcements`
**Namespace:** `App\Features\ContentManagement`
**Multi-tenancy:** Sí (company_id)
**Soft Deletes:** No (hard delete únicamente)
**UUID:** Sí (primary key)

---

## 1. ESTRUCTURA DE BASE DE DATOS

### Tabla: `company_announcements`

```sql
CREATE TABLE company_announcements (
    id UUID PRIMARY KEY,
    company_id UUID NOT NULL,          -- FK: business.companies (CASCADE)
    author_id UUID NOT NULL,           -- FK: auth.users (CASCADE)

    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,

    type ENUM('MAINTENANCE', 'INCIDENT', 'NEWS', 'ALERT') NOT NULL,
    status ENUM('DRAFT', 'SCHEDULED', 'PUBLISHED', 'ARCHIVED') DEFAULT 'DRAFT',

    metadata JSONB DEFAULT '{}',       -- Tipo-específico, validado por reglas
    published_at TIMESTAMP NULL,       -- NULL para DRAFT/SCHEDULED

    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Foreign Keys
FOREIGN KEY (company_id) REFERENCES business.companies(id) ON DELETE CASCADE;
FOREIGN KEY (author_id) REFERENCES auth.users(id) ON DELETE CASCADE;

-- Indexes
INDEX idx_company_id (company_id);
INDEX idx_company_status (company_id, status);
INDEX idx_type (type);
INDEX idx_status (status);
INDEX idx_published_at (published_at);
```

### Casts del Modelo

```php
protected $casts = [
    'metadata' => 'array',                    // JSONB → PHP array
    'status' => PublicationStatus::class,     // ENUM → Backed enum
    'type' => AnnouncementType::class,        // ENUM → Backed enum
    'published_at' => 'datetime',             // TIMESTAMP → Carbon
];
```

### Relaciones

```php
// Announcement belongsTo Company
public function company(): BelongsTo

// Announcement belongsTo User (author)
public function author(): BelongsTo
```

### Scopes

```php
// Filtra solo PUBLISHED
public function scopePublished($query)
```

### Métodos del Modelo

```php
// Verifica si es editable (DRAFT o SCHEDULED únicamente)
public function isEditable(): bool

// Obtiene scheduled_for desde metadata como Carbon
public function getScheduledForAttribute(): ?Carbon

// Retorna urgencia formatteda y localizada
public function formattedUrgency(): string
```

---

## 2. ENUMS

### AnnouncementType (backed: string)

| Case | Value | Metadata Schema |
|------|-------|-----------------|
| `MAINTENANCE` | `'MAINTENANCE'` | Mantenimientos programados o emergencias |
| `INCIDENT` | `'INCIDENT'` | Incidentes activos o resueltos |
| `NEWS` | `'NEWS'` | Noticias, features, políticas |
| `ALERT` | `'ALERT'` | Alertas críticas (seguridad, compliance) |

**Método del Enum:**
```php
public function metadataSchema(): array
```
Retorna el esquema de campos requeridos/opcionales para cada tipo.

---

### PublicationStatus (backed: string)

| Case | Value | Descripción |
|------|-------|-------------|
| `DRAFT` | `'DRAFT'` | Borrador, no visible públicamente |
| `SCHEDULED` | `'SCHEDULED'` | Programado para publicación futura |
| `PUBLISHED` | `'PUBLISHED'` | Publicado, visible según permisos |
| `ARCHIVED` | `'ARCHIVED'` | Archivado, no visible públicamente |

**Transiciones Permitidas:**
```
DRAFT      → PUBLISHED   (publish)
DRAFT      → SCHEDULED   (schedule)
SCHEDULED  → PUBLISHED   (publish)
SCHEDULED  → DRAFT       (unschedule)
PUBLISHED  → ARCHIVED    (archive)
ARCHIVED   → DRAFT       (restore)
DRAFT      → DELETE      (destroy)
ARCHIVED   → DELETE      (destroy)
```

---

### UrgencyLevel (backed: string)

| Case | Value |
|------|-------|
| `LOW` | `'LOW'` |
| `MEDIUM` | `'MEDIUM'` |
| `HIGH` | `'HIGH'` |
| `CRITICAL` | `'CRITICAL'` |

**Restricciones por Tipo:**
- **MAINTENANCE:** Solo `LOW`, `MEDIUM`, `HIGH` (CRITICAL prohibido)
- **INCIDENT:** Todos los valores permitidos
- **ALERT:** Solo `HIGH`, `CRITICAL` (LOW/MEDIUM prohibidos)

---

### AlertType (backed: string)

| Case | Value | isCritical() | Descripción |
|------|-------|--------------|-------------|
| `SECURITY` | `'security'` | `true` | Seguridad, autenticación, vulnerabilidades |
| `COMPLIANCE` | `'compliance'` | `true` | Cumplimiento legal, regulatorio, fiscal |
| `SYSTEM` | `'system'` | `false` | Problemas de sistema, infraestructura |
| `SERVICE` | `'service'` | `false` | Interrupciones de servicio |

**Métodos del Enum:**
```php
public function isCritical(): bool      // true para SECURITY, COMPLIANCE
public function isOperational(): bool   // true para SYSTEM, SERVICE
```

---

### NewsType (backed: string)

| Case | Value | Uso |
|------|-------|-----|
| `FEATURE_RELEASE` | `'feature_release'` | Lanzamiento de nuevas funcionalidades |
| `POLICY_UPDATE` | `'policy_update'` | Cambios en políticas, términos, privacidad |
| `GENERAL_UPDATE` | `'general_update'` | Actualizaciones generales, comunicados |

---

## 3. SCHEMAS DE METADATA POR TIPO

### MAINTENANCE

**Uso:** Mantenimientos programados, actualizaciones de sistema, emergencias técnicas.

**Campos Requeridos:**
```json
{
  "urgency": "LOW|MEDIUM|HIGH",               // CRITICAL prohibido
  "scheduled_start": "ISO8601 datetime",      // Inicio programado
  "scheduled_end": "ISO8601 datetime",        // Fin programado (> scheduled_start)
  "is_emergency": "boolean"                   // ¿Mantenimiento de emergencia?
}
```

**Campos Opcionales:**
```json
{
  "actual_start": "ISO8601 datetime|null",    // Inicio real (marcado manualmente)
  "actual_end": "ISO8601 datetime|null",      // Fin real (marcado manualmente)
  "affected_services": ["string", "..."]      // Servicios afectados
}
```

**Validaciones Especiales:**
- `scheduled_end` DEBE ser después de `scheduled_start`
- `urgency` NO puede ser `CRITICAL`
- `affected_services` máximo 20 items (si se proporciona)

**Operaciones Especiales:**
- `markStart()` → Establece `actual_start` a now()
- `markComplete()` → Establece `actual_end` a now()

**Ejemplo:**
```json
{
  "urgency": "HIGH",
  "scheduled_start": "2025-12-15T02:00:00Z",
  "scheduled_end": "2025-12-15T06:00:00Z",
  "is_emergency": false,
  "affected_services": ["Portal Web", "API REST", "Sistema de Reportes"],
  "actual_start": "2025-12-15T02:00:00Z",
  "actual_end": "2025-12-15T05:45:00Z"
}
```

---

### INCIDENT

**Uso:** Incidentes activos, problemas en producción, interrupciones no planificadas.

**Campos Requeridos:**
```json
{
  "urgency": "LOW|MEDIUM|HIGH|CRITICAL",      // Todos permitidos
  "is_resolved": "boolean",                   // ¿Incidente resuelto?
  "started_at": "ISO8601 datetime"            // Cuándo inició el incidente
}
```

**Campos Opcionales:**
```json
{
  "resolved_at": "ISO8601 datetime",          // REQUERIDO si is_resolved=true
  "resolution_content": "string",             // REQUERIDO si is_resolved=true
  "ended_at": "ISO8601 datetime|null",        // Cuándo terminó el impacto
  "affected_services": ["string", "..."]      // Servicios impactados
}
```

**Validaciones Condicionales:**
- Si `is_resolved === true`:
  - `resolved_at` es REQUERIDO
  - `resolution_content` es REQUERIDO
- `ended_at` DEBE ser después de `started_at` (si se proporciona)
- No se puede cambiar `is_resolved` de `true` → `false`

**Operación Especial:**
- `resolve()` → Marca incidente como resuelto con `resolution_content` y `resolved_at`

**Ejemplo (Activo):**
```json
{
  "urgency": "CRITICAL",
  "is_resolved": false,
  "started_at": "2025-11-18T14:30:00Z",
  "affected_services": ["Sistema de Pedidos", "API REST"]
}
```

**Ejemplo (Resuelto):**
```json
{
  "urgency": "HIGH",
  "is_resolved": true,
  "started_at": "2025-03-05T14:30:00Z",
  "ended_at": "2025-03-05T16:45:00Z",
  "resolved_at": "2025-03-05T16:45:00Z",
  "resolution_content": "Se identificó problema con pool de conexiones DB. Se aumentó límite y optimizaron queries.",
  "affected_services": ["Sistema de Pedidos", "Inventario"]
}
```

---

### NEWS

**Uso:** Anuncios de nuevas funcionalidades, actualizaciones de políticas, comunicados generales.

**Campos Requeridos:**
```json
{
  "news_type": "feature_release|policy_update|general_update",
  "target_audience": ["users", "agents", "admins"],    // Array, 1-5 items
  "summary": "string (10-500 chars)"                   // Resumen conciso
}
```

**Campos Opcionales:**
```json
{
  "call_to_action": {
    "text": "string",                                   // Texto del CTA
    "url": "https://..."                                // URL (HTTPS únicamente)
  }
}
```

**Validaciones:**
- `target_audience`:
  - DEBE ser array
  - Mínimo 1 item, máximo 5
  - Valores válidos: `users`, `agents`, `admins`
- `summary`:
  - Mínimo 10 caracteres
  - Máximo 500 caracteres
- `call_to_action.url`:
  - DEBE iniciar con `https://`
  - Formato URL válido

**Ejemplo:**
```json
{
  "news_type": "feature_release",
  "target_audience": ["users", "agents"],
  "summary": "Nueva funcionalidad de seguimiento de pedidos en tiempo real disponible desde hoy",
  "call_to_action": {
    "text": "Ver tutorial completo",
    "url": "https://example.com/tutoriales/seguimiento-tiempo-real"
  }
}
```

---

### ALERT

**Uso:** Alertas críticas de seguridad, cumplimiento, sistema que requieren atención inmediata.

**Campos Requeridos:**
```json
{
  "urgency": "HIGH|CRITICAL",                 // Solo HIGH/CRITICAL permitidos
  "alert_type": "security|system|service|compliance",
  "message": "string (10-500 chars)",         // Mensaje corto y directo
  "action_required": "boolean",               // ¿Usuario debe hacer algo?
  "started_at": "ISO8601 datetime"            // Cuándo inició la alerta
}
```

**Campos Opcionales:**
```json
{
  "action_description": "string (max 300)",   // REQUERIDO si action_required=true
  "ended_at": "ISO8601 datetime|null",        // Cuándo termina/terminó la alerta
  "affected_services": ["string", "..."]      // Servicios afectados
}
```

**Validaciones Especiales:**
- `urgency` SOLO puede ser `HIGH` o `CRITICAL` (LOW/MEDIUM prohibidos)
- Si `action_required === true`:
  - `action_description` es REQUERIDO
  - Máximo 300 caracteres
- `ended_at` DEBE ser después de `started_at` (si se proporciona)
- No se puede cambiar `action_required` de `true` → `false`

**Excepción CRÍTICA de Edición:**
- ALERT en estado PUBLISHED puede actualizar ÚNICAMENTE `metadata.ended_at`
- Ningún otro campo puede modificarse en PUBLISHED

**Ejemplo:**
```json
{
  "urgency": "HIGH",
  "alert_type": "security",
  "message": "Actualización de seguridad requerida - Cambie su contraseña antes del 31 de mayo",
  "action_required": true,
  "action_description": "Acceda a Configuración > Seguridad y establezca una nueva contraseña",
  "started_at": "2025-05-01T00:00:00Z",
  "ended_at": "2025-05-31T23:59:59Z"
}
```

---

## 4. REGLAS DE VALIDACIÓN

### Campos Base (todos los tipos)

```php
// CREATE
'title' => 'required|string|min:3|max:255'
'content' => 'required|string|min:10|max:5000'  // 'body' para NEWS (alias)
'action' => 'nullable|in:draft,publish,schedule'
'scheduled_for' => 'required_if:action,schedule|date|ValidScheduleDate'

// UPDATE
'title' => 'sometimes|string|min:3|max:255'
'content' => 'sometimes|string|min:10|max:5000'
```

### ValidScheduleDate Rule

Valida que `scheduled_for` esté:
- **Mínimo:** 5 minutos en el futuro
- **Máximo:** 1 año en el futuro

### ValidAnnouncementMetadata Rule

Validador dinámico que aplica reglas específicas según `type`:

**MAINTENANCE:**
```php
'urgency' => 'required|in:LOW,MEDIUM,HIGH',        // NO CRITICAL
'scheduled_start' => 'required|date',
'scheduled_end' => 'required|date|after:scheduled_start',
'is_emergency' => 'required|boolean',
'affected_services' => 'nullable|array|max:20',
'actual_start' => 'nullable|date',
'actual_end' => 'nullable|date'
```

**INCIDENT:**
```php
'urgency' => 'required|in:LOW,MEDIUM,HIGH,CRITICAL',
'is_resolved' => 'required|boolean',
'started_at' => 'required|date_format:ISO8601',
'resolved_at' => 'required_if:is_resolved,true|date_format:ISO8601',
'resolution_content' => 'required_if:is_resolved,true|string',
'ended_at' => 'nullable|date_format:ISO8601|after:started_at',
'affected_services' => 'nullable|array'
```

**NEWS:**
```php
'news_type' => 'required|in:feature_release,policy_update,general_update',
'target_audience' => 'required|array|min:1|max:5',  // Custom: users,agents,admins
'summary' => 'required|string|min:10|max:500',
'call_to_action' => 'nullable|array',
'call_to_action.text' => 'required_with:call_to_action|string',
'call_to_action.url' => 'required_with:call_to_action|url|starts_with:https://'
```

**ALERT:**
```php
'urgency' => 'required|in:HIGH,CRITICAL',          // Solo HIGH/CRITICAL
'alert_type' => 'required|in:security,system,service,compliance',
'message' => 'required|string|min:10|max:500',
'action_required' => 'required|boolean',
'action_description' => 'required_if:action_required,true|nullable|string|max:300',
'started_at' => 'required|date_format:ISO8601',
'ended_at' => 'nullable|date_format:ISO8601|after:started_at',
'affected_services' => 'nullable|array'
```

---

## 5. LÓGICA DE NEGOCIO (AnnouncementService)

### create(array $data): Announcement

**Comportamiento:**
1. Establece `status = DRAFT` por defecto
2. Procesa parámetro `action`:
   - `'publish'` → `status = PUBLISHED`, `published_at = now()`
   - `'schedule'` → `status = SCHEDULED`
3. Crea el announcement
4. Si `SCHEDULED`: encola `PublishAnnouncementJob` con delay
5. Retorna `fresh()` del modelo

**Parámetros:**
```php
[
    'company_id' => 'uuid',
    'author_id' => 'uuid',
    'title' => 'string',
    'content' => 'string',
    'type' => AnnouncementType,
    'metadata' => [],
    'action' => 'publish|schedule|null',
    'scheduled_for' => 'datetime'  // Si action=schedule
]
```

---

### update(Announcement $announcement, array $data): Announcement

**Reglas de Edición:**
- Solo `DRAFT` o `SCHEDULED` son editables
- **EXCEPCIÓN:** ALERT en `PUBLISHED` puede actualizar solo `metadata.ended_at`
- `PUBLISHED` genera RuntimeException
- `ARCHIVED` genera RuntimeException

**Retorna:** `fresh()` del modelo

---

### publish(Announcement $announcement): Announcement

**Precondiciones:**
- Estado actual: `DRAFT` o `SCHEDULED`
- No puede estar `PUBLISHED` (genera RuntimeException)
- No puede estar `ARCHIVED` (genera RuntimeException)

**Acción:**
- `status = PUBLISHED`
- `published_at = now()`

---

### schedule(Announcement $announcement, Carbon $scheduledFor): Announcement

**Precondiciones:**
- Estado actual: `DRAFT` o `SCHEDULED`
- `$scheduledFor` DEBE estar en el futuro (InvalidArgumentException)
- No puede estar `PUBLISHED` o `ARCHIVED`

**Acción:**
1. `status = SCHEDULED`
2. `metadata['scheduled_for'] = $scheduledFor->toIso8601String()`
3. Encola `PublishAnnouncementJob::dispatch($announcement)->delay($scheduledFor)`

---

### unschedule(Announcement $announcement): Announcement

**Precondiciones:**
- Estado actual: `SCHEDULED`

**Acción:**
- `status = DRAFT`
- Remueve `metadata['scheduled_for']`
- **NOTA:** Job encolado NO se cancela (limitación de Laravel Queue)

---

### archive(Announcement $announcement): Announcement

**Precondiciones:**
- Estado actual: `PUBLISHED`

**Acción:**
- `status = ARCHIVED`
- `published_at` se mantiene (histórico)

---

### restore(Announcement $announcement): Announcement

**Precondiciones:**
- Estado actual: `ARCHIVED`

**Acción:**
- `status = DRAFT`
- `published_at = null`

---

### delete(Announcement $announcement): bool

**Precondiciones:**
- Estado actual: `DRAFT` o `ARCHIVED`
- `SCHEDULED` genera RuntimeException
- `PUBLISHED` genera RuntimeException

**Acción:**
- Hard delete del registro

---

### Operaciones Específicas por Tipo

#### MAINTENANCE

**markStart(Announcement $announcement)**
- Establece `metadata['actual_start'] = now()`
- Solo si tipo es `MAINTENANCE`

**markComplete(Announcement $announcement)**
- Establece `metadata['actual_end'] = now()`
- Solo si tipo es `MAINTENANCE`

#### INCIDENT

**resolve(Announcement $announcement, array $data)**
- Valida campos requeridos: `resolution_content`
- Establece `metadata['is_resolved'] = true`
- Establece `metadata['resolved_at'] = $data['resolved_at'] ?? now()`
- Opcionalmente actualiza `ended_at` y `title`
- Solo si tipo es `INCIDENT`

---

## 6. VISIBILIDAD POR ROL

### PLATFORM_ADMIN
- **Ve:** Todos los announcements de todas las compañías
- **Acciones:** Ninguna (read-only cross-company)

### COMPANY_ADMIN
- **Ve:** Todos los estados de su compañía activa (DRAFT, SCHEDULED, PUBLISHED, ARCHIVED)
- **Acciones:** CRUD completo, publicar, programar, archivar, restaurar, eliminar

### AGENT / USER
- **Ve:** Solo `PUBLISHED` de compañías que sigue (tabla `user_company_followers`)
- **Acciones:** Solo lectura

**Query de Visibilidad:**
```php
// Admin de compañía
Announcement::where('company_id', $activeCompanyId);

// Usuario/Agent
Announcement::where('status', PublicationStatus::PUBLISHED)
    ->whereIn('company_id', $followedCompanyIds);
```

---

## 7. API ENDPOINTS

### Públicos (Lectura)

```
GET    /api/announcements              Lista paginada (filtrable por status, type)
GET    /api/announcements/{id}         Detalle individual
```

**Query Params:**
- `status` → `DRAFT|SCHEDULED|PUBLISHED|ARCHIVED`
- `type` → `MAINTENANCE|INCIDENT|NEWS|ALERT`
- `per_page` → 1-100 (default: 20)
- `sort_by` → `created_at|published_at|title`
- `sort_order` → `asc|desc`

### Por Tipo (Creación)

```
POST   /api/announcements/news         Crear NEWS
POST   /api/announcements/alerts       Crear ALERT
POST   /api/announcements/maintenance  Crear MAINTENANCE
POST   /api/announcements/incidents    Crear INCIDENT
```

### Gestión (Actualización/Eliminación)

```
PATCH  /api/announcements/{id}         Actualizar
DELETE /api/announcements/{id}         Eliminar (solo DRAFT/ARCHIVED)
```

### Acciones de Ciclo de Vida

```
POST   /api/announcements/{id}/publish      Publicar ahora
POST   /api/announcements/{id}/schedule     Programar publicación
POST   /api/announcements/{id}/unschedule   Cancelar programación
POST   /api/announcements/{id}/archive      Archivar
POST   /api/announcements/{id}/restore      Restaurar desde archivo
```

### Operaciones Específicas

```
POST   /api/announcements/{id}/mark-start    (MAINTENANCE) Marcar inicio
POST   /api/announcements/{id}/mark-complete (MAINTENANCE) Marcar finalización
POST   /api/announcements/{id}/resolve       (INCIDENT) Resolver incidente
```

### Utilidades

```
GET    /api/announcements/metadata-schema    Schemas de metadata por tipo
```

---

## 8. FACTORY PATTERNS (AnnouncementFactory)

### Estado Base

```php
Announcement::factory()->create([
    'company_id' => $company->id,
    'author_id' => $admin->id,
]);
// Estado: DRAFT, metadata generado según tipo aleatorio
```

### Modificadores de Estado

```php
->published()    // status = PUBLISHED, published_at = now()
->scheduled()    // status = SCHEDULED, metadata.scheduled_for = now()+1day
->archived()     // status = ARCHIVED, published_at = now()-30days
```

### Modificadores de Tipo

```php
->maintenance()  // type = MAINTENANCE, metadata según esquema
->incident()     // type = INCIDENT, metadata según esquema
->news()         // type = NEWS, metadata según esquema
->alert()        // type = ALERT, metadata según esquema
```

### Ejemplos de Uso

```php
// MAINTENANCE publicado
Announcement::factory()
    ->maintenance()
    ->published()
    ->create([
        'company_id' => $company->id,
        'author_id' => $admin->id,
        'title' => 'Mantenimiento programado',
    ]);

// INCIDENT activo
Announcement::factory()
    ->incident()
    ->published()
    ->create([
        'company_id' => $company->id,
        'author_id' => $admin->id,
        'metadata' => [
            'urgency' => 'CRITICAL',
            'is_resolved' => false,
            'started_at' => now()->toIso8601String(),
            'affected_services' => ['API', 'Portal Web'],
        ],
    ]);

// NEWS programado
Announcement::factory()
    ->news()
    ->scheduled()
    ->create([
        'company_id' => $company->id,
        'author_id' => $admin->id,
        'metadata' => [
            'news_type' => 'feature_release',
            'target_audience' => ['users', 'agents'],
            'summary' => 'Nueva funcionalidad próximamente',
            'scheduled_for' => now()->addWeek()->toIso8601String(),
        ],
    ]);
```

### Metadata Generado por Defecto

**MAINTENANCE:**
```php
'urgency' => ['LOW', 'MEDIUM', 'HIGH'][random],
'scheduled_start' => now()->addDays(2),
'scheduled_end' => now()->addDays(2)->addHours(4),
'is_emergency' => false,
'affected_services' => ['api', 'reports']
```

**INCIDENT:**
```php
'urgency' => ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'][random],
'is_resolved' => false,
'started_at' => now()->subHours(2),
'affected_services' => ['login', 'api']
```

**NEWS:**
```php
'news_type' => ['feature_release', 'policy_update', 'general_update'][random],
'target_audience' => ['users', 'agents'],
'summary' => faker()->sentence()
```

**ALERT:**
```php
'urgency' => ['HIGH', 'CRITICAL'][random],
'alert_type' => ['security', 'system', 'service', 'compliance'][random],
'message' => faker()->sentence(),
'action_required' => false,
'started_at' => now()
```

---

## 9. PATRÓN DE SEEDER (Idempotente)

### Estructura Recomendada

```php
use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Seeder;

class CompanyAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener compañía
        $company = Company::where('name', 'Nombre Exacto')->first();

        if (!$company) {
            $this->command->error('Company not found');
            return;
        }

        // 2. Obtener autor (admin de compañía)
        $admin = User::where('email', 'admin@company.com')->first();

        if (!$admin) {
            $this->command->error('Admin not found');
            return;
        }

        $this->command->info("Creating announcements for {$company->name}...");

        // 3. Crear announcements con firstOrCreate (idempotencia)
        $this->createPublishedAnnouncements($company, $admin);
        $this->createDraftAnnouncements($company, $admin);
        $this->createScheduledAnnouncements($company, $admin);
        $this->createArchivedAnnouncements($company, $admin);

        $this->command->info('Announcements created successfully!');
    }

    private function createPublishedAnnouncements(Company $company, User $admin): void
    {
        // MAINTENANCE publicado (completado)
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Título Único Identificador',
            ],
            [
                'author_id' => $admin->id,
                'content' => 'Contenido detallado...',
                'type' => AnnouncementType::MAINTENANCE,
                'status' => PublicationStatus::PUBLISHED,
                'metadata' => [
                    'urgency' => 'HIGH',
                    'scheduled_start' => '2025-06-20T00:00:00Z',
                    'scheduled_end' => '2025-06-20T08:00:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['Portal Web', 'API'],
                    'actual_start' => '2025-06-20T00:00:00Z',
                    'actual_end' => '2025-06-20T07:30:00Z',
                ],
                'published_at' => '2025-06-15 10:00:00',
            ]
        );

        // INCIDENT resuelto
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Incidente Resuelto - Título Único',
            ],
            [
                'author_id' => $admin->id,
                'content' => 'Descripción del incidente...',
                'type' => AnnouncementType::INCIDENT,
                'status' => PublicationStatus::PUBLISHED,
                'metadata' => [
                    'urgency' => 'CRITICAL',
                    'is_resolved' => true,
                    'started_at' => '2025-08-10T10:00:00Z',
                    'ended_at' => '2025-08-10T12:30:00Z',
                    'resolved_at' => '2025-08-10T12:30:00Z',
                    'resolution_content' => 'Problema identificado y resuelto...',
                    'affected_services' => ['API REST', 'Integraciones'],
                ],
                'published_at' => '2025-08-10 10:00:00',
            ]
        );

        // NEWS con CTA
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Lanzamiento: Nueva Funcionalidad',
            ],
            [
                'author_id' => $admin->id,
                'content' => 'Descripción de la nueva funcionalidad...',
                'type' => AnnouncementType::NEWS,
                'status' => PublicationStatus::PUBLISHED,
                'metadata' => [
                    'news_type' => 'feature_release',
                    'target_audience' => ['users', 'agents', 'admins'],
                    'summary' => 'Nueva funcionalidad disponible desde hoy',
                    'call_to_action' => [
                        'text' => 'Ver tutorial',
                        'url' => 'https://example.com/tutorial',
                    ],
                ],
                'published_at' => '2025-04-15 09:00:00',
            ]
        );

        // ALERT con acción requerida
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Actualización de Seguridad Requerida',
            ],
            [
                'author_id' => $admin->id,
                'content' => 'Medidas de seguridad implementadas...',
                'type' => AnnouncementType::ALERT,
                'status' => PublicationStatus::PUBLISHED,
                'metadata' => [
                    'urgency' => 'HIGH',
                    'alert_type' => 'security',
                    'message' => 'Actualice su contraseña antes del 31 de mayo',
                    'action_required' => true,
                    'action_description' => 'Ingrese a Configuración > Seguridad',
                    'started_at' => '2025-05-01T00:00:00Z',
                    'ended_at' => '2025-05-31T23:59:59Z',
                ],
                'published_at' => '2025-05-01 08:00:00',
            ]
        );
    }

    private function createDraftAnnouncements(Company $company, User $admin): void
    {
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Borrador - Nueva Política',
            ],
            [
                'author_id' => $admin->id,
                'content' => 'Contenido en desarrollo...',
                'type' => AnnouncementType::NEWS,
                'status' => PublicationStatus::DRAFT,
                'metadata' => [
                    'news_type' => 'policy_update',
                    'target_audience' => ['users', 'agents'],
                    'summary' => 'Actualización de políticas en revisión',
                ],
                'published_at' => null,
            ]
        );
    }

    private function createScheduledAnnouncements(Company $company, User $admin): void
    {
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Mantenimiento Programado - Diciembre',
            ],
            [
                'author_id' => $admin->id,
                'content' => 'Mantenimiento preventivo...',
                'type' => AnnouncementType::MAINTENANCE,
                'status' => PublicationStatus::SCHEDULED,
                'metadata' => [
                    'urgency' => 'LOW',
                    'scheduled_start' => '2025-12-15T00:00:00Z',
                    'scheduled_end' => '2025-12-15T04:00:00Z',
                    'is_emergency' => false,
                    'affected_services' => ['Todos los servicios'],
                    'scheduled_for' => '2025-12-10T08:00:00Z',
                ],
                'published_at' => null,
            ]
        );
    }

    private function createArchivedAnnouncements(Company $company, User $admin): void
    {
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Incidente Antiguo - Archivado',
            ],
            [
                'author_id' => $admin->id,
                'content' => 'Incidente histórico...',
                'type' => AnnouncementType::INCIDENT,
                'status' => PublicationStatus::ARCHIVED,
                'metadata' => [
                    'urgency' => 'HIGH',
                    'is_resolved' => true,
                    'started_at' => '2025-02-01T09:00:00Z',
                    'ended_at' => '2025-02-01T11:30:00Z',
                    'resolved_at' => '2025-02-01T11:30:00Z',
                    'resolution_content' => 'Problema resuelto exitosamente',
                    'affected_services' => ['Sistema de Pedidos'],
                ],
                'published_at' => '2025-02-01 09:00:00',
            ]
        );
    }
}
```

---

## 10. CONSIDERACIONES ESPECIALES

### Idempotencia en Seeders

**CRÍTICO:** Usar `firstOrCreate()` con campos únicos identificadores:

```php
Announcement::firstOrCreate(
    ['company_id' => $companyId, 'title' => 'Título Único'],  // Búsqueda
    [ /* datos completos */ ]                                  // Creación
);
```

### Timestamps en Metadata

**SIEMPRE usar formato ISO8601:**
```php
'started_at' => '2025-11-18T14:30:00Z'  // ✓ Correcto
'started_at' => '2025-11-18 14:30:00'   // ✗ Incorrecto
```

**Generación dinámica:**
```php
'started_at' => now()->toIso8601String()
'scheduled_start' => now()->addDays(2)->toIso8601String()
```

### Validación de Urgency por Tipo

| Tipo | Valores Permitidos |
|------|-------------------|
| MAINTENANCE | `LOW`, `MEDIUM`, `HIGH` |
| INCIDENT | `LOW`, `MEDIUM`, `HIGH`, `CRITICAL` |
| ALERT | `HIGH`, `CRITICAL` |
| NEWS | N/A (no usa urgency) |

### Affected Services

**Formato:** Array de strings, máximo 20 items
```php
'affected_services' => ['Portal Web', 'API REST', 'Sistema de Reportes']
```

### Call to Action (NEWS)

**DEBE usar HTTPS:**
```php
'call_to_action' => [
    'text' => 'Ver más información',
    'url' => 'https://example.com/info',  // ✓ HTTPS obligatorio
]
```

### Target Audience (NEWS)

**Valores permitidos:** `users`, `agents`, `admins`
**Cantidad:** 1-5 items
```php
'target_audience' => ['users', 'agents']  // ✓ Correcto
'target_audience' => ['all']              // ✗ Inválido
```

---

## 11. JOBS Y EVENTOS

### PublishAnnouncementJob

**Trigger:** Al crear/programar announcement con `status = SCHEDULED`

**Comportamiento:**
```php
PublishAnnouncementJob::dispatch($announcement)
    ->delay($scheduledFor);
```

**Ejecución:**
- Cambia `status` a `PUBLISHED`
- Establece `published_at = now()`
- **NOTA:** No se cancela automáticamente al unschedule

### Eventos (futuro)

Sistema NO implementa eventos actualmente, pero se recomienda:
- `AnnouncementPublished`
- `AnnouncementScheduled`
- `IncidentResolved`
- `MaintenanceStarted`

---

## 12. TESTING PATTERNS

### Factory Usage en Tests

```php
use App\Features\ContentManagement\Models\Announcement;

// Setup
$company = Company::factory()->create();
$admin = User::factory()->create();

// Test MAINTENANCE workflow
$announcement = Announcement::factory()
    ->maintenance()
    ->create([
        'company_id' => $company->id,
        'author_id' => $admin->id,
    ]);

$this->assertEquals(PublicationStatus::DRAFT, $announcement->status);

// Publish
$service = app(AnnouncementService::class);
$published = $service->publish($announcement);

$this->assertEquals(PublicationStatus::PUBLISHED, $published->status);
$this->assertNotNull($published->published_at);
```

### Testing Metadata Validation

```php
// Invalid urgency for MAINTENANCE
$this->postJson('/api/announcements/maintenance', [
    'title' => 'Test',
    'content' => 'Test content',
    'urgency' => 'CRITICAL',  // ✗ No permitido
    'scheduled_start' => now()->addDay()->toIso8601String(),
    'scheduled_end' => now()->addDay()->addHours(4)->toIso8601String(),
    'is_emergency' => false,
])->assertStatus(422)
  ->assertJsonValidationErrors(['metadata']);
```

### Testing State Transitions

```php
// Cannot edit published (except ALERT ended_at)
$announcement = Announcement::factory()
    ->news()
    ->published()
    ->create();

$this->expectException(RuntimeException::class);
$service->update($announcement, ['title' => 'New Title']);
```

---

## 13. RESUMEN DE RESTRICCIONES CRÍTICAS

| Restricción | Descripción |
|-------------|-------------|
| MAINTENANCE urgency | NO puede ser CRITICAL |
| ALERT urgency | SOLO puede ser HIGH o CRITICAL |
| ALERT editing | PUBLISHED solo puede actualizar metadata.ended_at |
| scheduled_end | DEBE ser después de scheduled_start |
| ended_at | DEBE ser después de started_at |
| is_resolved | NO puede cambiar de true → false |
| action_required | NO puede cambiar de true → false |
| Editing states | Solo DRAFT y SCHEDULED (con excepción ALERT) |
| Deletion states | Solo DRAFT y ARCHIVED |
| CTA URL | DEBE usar HTTPS |
| target_audience | 1-5 items, valores: users/agents/admins |
| affected_services | Máximo 20 items |
| Timestamp format | ISO8601 obligatorio (toIso8601String()) |

---

## 14. CHECKLIST PARA CREAR SEEDERS

- [ ] Obtener compañía con `Company::where('name', '...')->first()`
- [ ] Validar que compañía existe (early return si no)
- [ ] Obtener admin con `User::where('email', '...')->first()`
- [ ] Validar que admin existe (early return si no)
- [ ] Usar `firstOrCreate()` con `company_id` + `title` como identificador
- [ ] Usar formato ISO8601 para todos los datetime (`toIso8601String()`)
- [ ] Validar `urgency` según tipo de announcement
- [ ] Si INCIDENT resuelto: incluir `resolved_at` + `resolution_content`
- [ ] Si ALERT con acción: incluir `action_description`
- [ ] Si NEWS con CTA: incluir `text` + `url` (HTTPS)
- [ ] Si MAINTENANCE completado: incluir `actual_start` + `actual_end`
- [ ] Establecer `published_at` solo para PUBLISHED/ARCHIVED
- [ ] Establecer `published_at = null` para DRAFT/SCHEDULED
- [ ] Si SCHEDULED: incluir `metadata.scheduled_for`
- [ ] Distribuir fechas de manera realista a lo largo del año
- [ ] Incluir mix de estados: PUBLISHED, DRAFT, SCHEDULED, ARCHIVED
- [ ] Incluir mix de tipos: MAINTENANCE, INCIDENT, NEWS, ALERT
- [ ] Añadir mensajes informativos con `$this->command->info()`

---

## CONCLUSIÓN

Este documento proporciona una auditoría técnica completa del sistema de announcements, cubriendo todos los aspectos necesarios para crear seeders sofisticados y realistas que respeten todas las reglas de validación, transiciones de estado, y restricciones de negocio del sistema.

**Archivo:** `documentacion/AUDITORIA_ANNOUNCEMENTS_COMPLETA.md`
**Versión:** 1.0
**Fecha:** 2025-12-08
**Autor:** Claude Code Audit System
