Quiere# Reporte de Endpoints API - Helpdesk

**Versión**: 1.0.0
**Descripción**: API REST del Sistema Helpdesk. Migración de GraphQL a REST con autenticación JWT.

**Servidores**:
- http://localhost:8000 - Development Server

---

## Tabla de Contenidos

- [Resumen Ejecutivo](#resumen-ejecutivo)
- [Alert Announcements](#alert-announcements)
- [Announcement Actions](#announcement-actions)
- [Announcements](#announcements)
- [Authentication](#authentication)
- [Companies](#companies)
- [Company Followers](#company-followers)
- [Company Industries](#company-industries)
- [Company Requests](#company-requests)
- [Company Requests - Admin](#company-requests---admin)
- [Email Verification](#email-verification)
- [Health](#health)
- [Help Center: Articles](#help-center-articles)
- [Help Center: Categories](#help-center-categories)
- [Incident Announcements](#incident-announcements)
- [Maintenance Announcements](#maintenance-announcements)
- [News Announcements](#news-announcements)
- [Onboarding](#onboarding)
- [Password Reset](#password-reset)
- [Roles](#roles)
- [Sessions](#sessions)
- [User Profile](#user-profile)
- [Users](#users)
- [Componentes de Seguridad](#componentes-de-seguridad)

---

## Resumen Ejecutivo

**Total de Endpoints**: 67

### Endpoints por Módulo

| Módulo | Cantidad de Endpoints |
|--------|----------------------|
| Alert Announcements | 1 |
| Announcement Actions | 5 |
| Announcements | 5 |
| Authentication | 5 |
| Companies | 6 |
| Company Followers | 4 |
| Company Industries | 1 |
| Company Requests | 2 |
| Company Requests - Admin | 2 |
| Email Verification | 3 |
| Health | 1 |
| Help Center: Articles | 7 |
| Help Center: Categories | 1 |
| Incident Announcements | 2 |
| Maintenance Announcements | 3 |
| News Announcements | 1 |
| Onboarding | 1 |
| Password Reset | 3 |
| Roles | 3 |
| Sessions | 3 |
| User Profile | 3 |
| Users | 5 |

### Endpoints por Método HTTP

| Método | Cantidad |
|--------|----------|
| DELETE | 6 |
| GET | 24 |
| PATCH | 3 |
| POST | 31 |
| PUT | 3 |

---

## Alert Announcements

### `POST` /api/announcements/alerts

**Resumen**: Create alert announcement

**Descripción**: Create a new alert announcement for urgent notifications. Only COMPANY_ADMIN role can create alerts. Company ID is automatically inferred from JWT token. Alerts can be created as DRAFT (default), published immediately (action=publish), or scheduled for future publication (action=schedule). Alert-specific metadata includes urgency (HIGH or CRITICAL only), alert_type (security, system, service, compliance), message, action_required flag, optional action_description (required if action_required=true), started_at datetime, optional ended_at datetime, and optional affected_services array. If action=schedule, a PublishAnnouncementJob is dispatched with calculated delay.

**Operation ID**: `create_alert_announcement`

**Autenticación**:
- bearerAuth

**Request Body**:
**Descripción**: Alert announcement creation data with security-critical metadata

**Content-Type**: `application/json`

- **title** (required): `string`
  - Alert title (5-200 characters)
  - Ejemplo: `Security Breach Detected`
- **content** (required): `string`
  - Alert content/description (minimum 10 characters)
  - Ejemplo: `We have detected unauthorized access attempts. Please change your password immediately.`
- **metadata** (required): `object`
  - Alert-specific metadata object with security and operational details
  - **urgency**: `string`
    - Urgency level (HIGH or CRITICAL only for alerts)
    - Ejemplo: `CRITICAL`
  - **alert_type**: `string`
    - Type of alert
    - Ejemplo: `security`
  - **message**: `string`
    - Alert message (10-500 characters)
    - Ejemplo: `Immediate action required: Change your password now`
  - **action_required**: `boolean`
    - Whether action is required from users (boolean, if true action_description becomes required)
    - Ejemplo: `True`
  - **action_description**: `string`
    - Description of required action (required if action_required=true, max 300 chars)
    - Ejemplo: `Navigate to Settings > Security and update your password`
  - **started_at**: `string (date-time)`
    - Alert start datetime (ISO8601, required)
    - Ejemplo: `2025-11-06T10:00:00Z`
  - **ended_at**: `string (date-time)`
    - Alert end datetime (ISO8601, optional, must be after started_at)
    - Ejemplo: `2025-11-06T18:00:00Z`
  - **affected_services**: `array`
    - Array of affected service names (optional)
    - Ejemplo: `['authentication', 'user_management']`
- **action**: `string`
  - Action to perform: draft (default), publish (immediately), or schedule (requires scheduled_for)
  - Ejemplo: `publish`
- **scheduled_for**: `string (date-time)`
  - ISO8601 datetime for scheduling publication (required if action=schedule, must be at least 5 minutes in future, max 1 year)
  - Ejemplo: `2025-11-20T10:00:00Z`

**Respuestas**:

### Status 201
Alert announcement created successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message indicating the action performed
- **data**: `object`
  - Created alert announcement resource with type=ALERT, status (DRAFT/PUBLISHED/SCHEDULED), full metadata, and timestamps

### Status 400
Bad request - validation or logic errors

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `The title must be at least 5 characters.`

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthenticated`

### Status 403
Forbidden - user lacks COMPANY_ADMIN role or valid company in JWT

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

### Status 422
Unprocessable Entity - validation errors in request data

**Content-Type**: `application/json`

- **message**: `string`
  - Validation error message
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`
  - Object with field names as keys and array of error messages as values
  - Ejemplo: `{'title': ['The title must be at least 5 characters.'], 'metadata.urgency': ['The metadata.urgency must be HIGH or CRITICAL.']}`

### Status 500
Internal Server Error - unexpected server error

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `An unexpected error occurred`

---

## Announcement Actions

### `POST` /api/announcements/{id}/publish

**Resumen**: Publish announcement immediately

**Descripción**: Publish an announcement immediately, changing its status to PUBLISHED and setting published_at to current timestamp. Can publish announcements in DRAFT or SCHEDULED status. Cannot publish announcements that are already PUBLISHED or ARCHIVED. User must be the COMPANY_ADMIN who owns the announcement. If announcement was previously SCHEDULED, any queued publication jobs are automatically cancelled by the service.

**Operation ID**: `publish_announcement`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Announcement ID (UUID)

**Respuestas**:

### Status 200
Announcement published successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message
  - Ejemplo: `Announcement published successfully`
- **data**: `object`
  - Published announcement resource with status=PUBLISHED and current published_at timestamp

### Status 400
Bad request - invalid state transition

**Content-Type**: `application/json`

- **message**: `string`
  - Error message

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden - user lacks COMPANY_ADMIN role or does not own announcement

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

### Status 404
Announcement not found

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Announcement not found`

### Status 500
Internal Server Error

**Content-Type**: `application/json`

- **message**: `string`
  - Error message

---

### `POST` /api/announcements/{id}/schedule

**Resumen**: Schedule announcement for future publication

**Descripción**: Schedule an announcement for future publication. Changes status to SCHEDULED and enqueues PublishAnnouncementJob with calculated delay. The scheduled_for datetime must be 5 minutes to 1 year in the future. Can only schedule announcements in DRAFT status. If rescheduling a previously SCHEDULED announcement, the old job is cancelled and new job is enqueued. Automatic job cancellation occurs if announcement is published before scheduled time.

**Operation ID**: `schedule_announcement`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Announcement ID (UUID)

**Request Body**:
**Descripción**: Schedule data with future publication datetime

**Content-Type**: `application/json`

- **scheduled_for** (required): `string (date-time)`
  - ISO8601 datetime for publication (required, must be 5 min - 1 year in future)
  - Ejemplo: `2025-11-20T15:30:00Z`

**Respuestas**:

### Status 200
Announcement scheduled successfully, job enqueued

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message with formatted publication date
  - Ejemplo: `Announcement scheduled for publication on 20/11/2025 15:30`
- **data**: `object`
  - Scheduled announcement resource with status=SCHEDULED and metadata containing scheduled_for

### Status 400
Bad request - validation or invalid state transition

**Content-Type**: `application/json`

- **message**: `string`
  - Error message describing validation failure

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden - user lacks COMPANY_ADMIN role or does not own announcement

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

### Status 404
Announcement not found

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Announcement not found`

### Status 500
Internal Server Error

**Content-Type**: `application/json`

- **message**: `string`
  - Error message

---

### `POST` /api/announcements/{id}/unschedule

**Resumen**: Unschedule announcement

**Descripción**: Unschedule a SCHEDULED announcement, returning it to DRAFT status. Removes scheduled_for from metadata and cancels any queued PublishAnnouncementJob in Redis. Cannot unschedule announcements that are not SCHEDULED (DRAFT, PUBLISHED, ARCHIVED return 400 errors). Also prevents unscheduling of PUBLISHED announcements (separate validation).

**Operation ID**: `unschedule_announcement`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Announcement ID (UUID)

**Respuestas**:

### Status 200
Announcement unscheduled successfully, job cancelled

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message
  - Ejemplo: `Announcement unscheduled and returned to draft`
- **data**: `object`
  - Unscheduled announcement resource with status=DRAFT, published_at=null, and scheduled_for removed from metadata

### Status 400
Bad request - invalid state transition

**Content-Type**: `application/json`

- **message**: `string`
  - Error message

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden - user lacks COMPANY_ADMIN role or does not own announcement

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

### Status 404
Announcement not found

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Announcement not found`

### Status 500
Internal Server Error

**Content-Type**: `application/json`

- **message**: `string`
  - Error message

---

### `POST` /api/announcements/{id}/archive

**Resumen**: Archive published announcement

**Descripción**: Archive a PUBLISHED announcement, changing its status to ARCHIVED. Only PUBLISHED announcements can be archived (service validation). Preserves published_at timestamp. Archived announcements can be restored to DRAFT status later. Cannot archive DRAFT or SCHEDULED announcements.

**Operation ID**: `archive_announcement`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Announcement ID (UUID)

**Respuestas**:

### Status 200
Announcement archived successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message
  - Ejemplo: `Announcement archived successfully`
- **data**: `object`
  - Archived announcement resource with status=ARCHIVED and published_at preserved

### Status 400
Bad request - invalid state transition

**Content-Type**: `application/json`

- **message**: `string`
  - Error message explaining why announcement cannot be archived

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden - user lacks COMPANY_ADMIN role or does not own announcement

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

### Status 404
Announcement not found

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Announcement not found`

### Status 500
Internal Server Error

**Content-Type**: `application/json`

- **message**: `string`
  - Error message

---

### `POST` /api/announcements/{id}/restore

**Resumen**: Restore archived announcement to draft

**Descripción**: Restore an ARCHIVED announcement to DRAFT status, clearing published_at timestamp. Only ARCHIVED announcements can be restored (service validation). Preserves original content and metadata (urgency, scheduled dates, etc.). Restored announcements become editable again and can be re-published. Cannot restore DRAFT or PUBLISHED announcements.

**Operation ID**: `restore_announcement`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Announcement ID (UUID)

**Respuestas**:

### Status 200
Announcement restored successfully to draft status

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message
  - Ejemplo: `Announcement restored to draft`
- **data**: `object`
  - Restored announcement resource with status=DRAFT, published_at=null, and all metadata preserved

### Status 400
Bad request - invalid state transition

**Content-Type**: `application/json`

- **message**: `string`
  - Error message explaining why announcement cannot be restored

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden - user lacks COMPANY_ADMIN role or does not own announcement

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

### Status 404
Announcement not found

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Announcement not found`

### Status 500
Internal Server Error

**Content-Type**: `application/json`

- **message**: `string`
  - Error message

---

## Announcements

### `GET` /api/announcements

**Resumen**: List announcements with role-based visibility

**Descripción**: Returns paginated list of announcements with role-based visibility. PLATFORM_ADMIN sees all from all companies. COMPANY_ADMIN sees all states from their company. AGENT/USER see only PUBLISHED from followed companies.

**Operation ID**: `list_announcements`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **status** (query): `string`
  - Filter by announcement status
- **type** (query): `string`
  - Filter by announcement type
- **search** (query): `string`
  - Search in title and content (max 100 chars)
- **sort** (query): `string`
  - Sort field and direction (default: -published_at)
- **published_after** (query): `string`
  - Filter announcements published after this date
- **published_before** (query): `string`
  - Filter announcements published before this date
- **company_id** (query): `string`
  - Filter by company (UUID, only for PLATFORM_ADMIN)
- **page** (query): `integer`
  - Page number (default: 1)
- **per_page** (query): `integer`
  - Items per page (default: 20, max: 100)

**Respuestas**:

### Status 200
Announcements list with pagination

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **data**: `array`
  - Array of announcements
  Items:

- **meta**: `object`
  - Pagination metadata (current_page, per_page, total, last_page)

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthenticated`

### Status 403
Forbidden (insufficient permissions)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

---

### `GET` /api/announcements/{announcement}

**Resumen**: Get announcement by ID

**Descripción**: Returns a single announcement by ID with role-based visibility. PLATFORM_ADMIN can view any announcement. COMPANY_ADMIN can view any announcement from their company. AGENT/USER can only view PUBLISHED announcements from followed companies.

**Operation ID**: `get_announcement`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **announcement** (required) (path): `string`
  - The announcement ID (UUID)

**Respuestas**:

### Status 200
Announcement retrieved successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **data**: `object`
  - Announcement object with full details

### Status 404
Announcement not found

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
- **message**: `string`
  - Error message
  - Ejemplo: `Announcement not found`
- **code**: `string`
  - Error code
  - Ejemplo: `NOT_FOUND`
- **category**: `string`
  - Error category
  - Ejemplo: `resource`

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden (insufficient permissions - user role or company mismatch)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

---

### `PUT` /api/announcements/{announcement}

**Resumen**: Update announcement

**Descripción**: Update an existing announcement with partial data. Only DRAFT and SCHEDULED announcements can be edited. Published ALERT announcements (via special exception) can only update ended_at field. Supports type-specific metadata fields: MAINTENANCE (urgency, scheduled_start, scheduled_end, is_emergency, affected_services), INCIDENT (resolution_content, affected_services), NEWS (news_type, target_audience, summary, call_to_action), ALERT (urgency, alert_type, message, action_required, action_description, started_at, ended_at, affected_services).

**Operation ID**: `update_announcement`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **announcement** (required) (path): `string`
  - The announcement ID (UUID)

**Request Body**:
**Descripción**: Announcement update data with type-specific metadata fields (all fields optional)

**Content-Type**: `application/json`

- **title**: `string`
  - Announcement title (3-255 chars)
- **content**: `string`
  - Announcement content (10-5000 chars)
- **urgency**: `string`
  - Urgency level (LOW/MEDIUM/HIGH/CRITICAL)
- **scheduled_start**: `string (date-time)`
  - Scheduled start datetime (MAINTENANCE only)
- **scheduled_end**: `string (date-time)`
  - Scheduled end datetime (MAINTENANCE only, after start)
- **is_emergency**: `boolean`
  - Is emergency flag (MAINTENANCE only)
- **affected_services**: `array`
  - List of affected service IDs (array of strings)
- **resolution_content**: `string`
  - Resolution details (INCIDENT only, max 1000 chars)
- **metadata**: `object`
  - Complex metadata object for type-specific fields (NEWS/ALERT)

**Respuestas**:

### Status 200
Announcement updated successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message
  - Ejemplo: `Announcement updated successfully`
- **data**: `object`
  - Updated announcement object with all fields

### Status 400
Bad request (validation error or state constraint violation)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Cannot edit published announcement`

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden (user not COMPANY_ADMIN or company mismatch, or cannot edit published ALERT announcement)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

---

### `DELETE` /api/announcements/{announcement}

**Resumen**: Delete announcement

**Descripción**: Delete an announcement permanently. Only DRAFT or ARCHIVED announcements can be deleted. Published and SCHEDULED announcements cannot be deleted.

**Operation ID**: `delete_announcement`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **announcement** (required) (path): `string`
  - The announcement ID (UUID)

**Respuestas**:

### Status 200
Announcement deleted successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message
  - Ejemplo: `Announcement deleted successfully`

### Status 400
Bad request (cannot delete published or scheduled announcement)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Cannot delete published announcement`

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden (insufficient permissions - user not COMPANY_ADMIN or company mismatch)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

---

### `GET` /api/announcements/schemas

**Resumen**: Get announcement type schemas

**Descripción**: Returns the metadata schema structure for each announcement type. Only COMPANY_ADMIN and PLATFORM_ADMIN can access this endpoint. Used by frontend to dynamically build forms.

**Operation ID**: `get_announcement_schemas`

**Autenticación**:
No requiere autenticación

**Respuestas**:

### Status 200
Schemas for all announcement types (MAINTENANCE, INCIDENT, NEWS, ALERT)

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **data**: `object`
  - Schema definitions keyed by announcement type

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthenticated`

### Status 403
Forbidden (insufficient permissions - requires COMPANY_ADMIN or PLATFORM_ADMIN)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

---

## Authentication

### `POST` /api/auth/register

**Resumen**: Register a new user

**Descripción**: Creates a new user account with email and password authentication. Automatically generates JWT access token and refresh token. The refresh token is securely stored in an HttpOnly cookie for enhanced security. Upon successful registration, the user receives a verification email and can immediately start using the application.

**Operation ID**: `021e877864f2b053f14af19f4fce5dd7`

**Autenticación**:
No requiere autenticación

**Request Body**:
**Descripción**: User registration data

**Content-Type**: `application/json`

- **email** (required): `string (email)`
  - User email address (must be unique)
  - Ejemplo: `user@example.com`
- **password** (required): `string (password)`
  - Password (minimum 8 characters, must contain letters, numbers, and symbols)
  - Ejemplo: `SecurePass123!`
- **passwordConfirmation** (required): `string (password)`
  - Password confirmation (must match password field)
  - Ejemplo: `SecurePass123!`
- **firstName** (required): `string`
  - User first name
  - Ejemplo: `Juan`
- **lastName** (required): `string`
  - User last name
  - Ejemplo: `Pérez`
- **acceptsTerms** (required): `boolean`
  - User must accept terms of service (must be true)
  - Ejemplo: `True`
- **acceptsPrivacyPolicy** (required): `boolean`
  - User must accept privacy policy (must be true)
  - Ejemplo: `True`

**Respuestas**:

### Status 201
User created successfully. Returns authentication tokens and user data. Refresh token is set in HttpOnly cookie named "refresh_token".

**Headers**:
- **Set-Cookie**: HttpOnly cookie containing refresh token (name: refresh_token, path: /, httpOnly: true, sameSite: lax, maxAge: 43200 minutes)

**Content-Type**: `application/json`

- **accessToken**: `string`
  - JWT access token for API authentication
  - Ejemplo: `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...`
- **refreshToken**: `string`
  - Information message (actual token is in HttpOnly cookie)
  - Ejemplo: `Refresh token set in httpOnly cookie`
- **tokenType**: `string`
  - Token type
  - Ejemplo: `Bearer`
- **expiresIn**: `integer`
  - Access token expiration time in seconds
  - Ejemplo: `2592000`
- **user**: `object`
  - Authenticated user information
  - **id**: `string (uuid)`
    - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
  - **userCode**: `string`
    - Ejemplo: `USR-20241101-001`
  - **email**: `string (email)`
    - Ejemplo: `user@example.com`
  - **emailVerified**: `boolean`
  - **onboardingCompleted**: `boolean`
  - **status**: `string`
    - Ejemplo: `ACTIVE`
  - **displayName**: `string`
    - Ejemplo: `Juan Pérez`
  - **avatarUrl**: `string`
  - **theme**: `string`
    - Ejemplo: `light`
  - **language**: `string`
    - Ejemplo: `es`
  - **roleContexts**: `array`
    - User roles and their associated contexts
    Items:
      - **roleCode**: `string`
        - Ejemplo: `USER`
      - **roleName**: `string`
        - Ejemplo: `Cliente`
      - **dashboardPath**: `string`
        - Ejemplo: `/tickets`
      - **company**: `object`
        - **id**: `string (uuid)`
        - **companyCode**: `string`
        - **name**: `string`
- **sessionId**: `string (uuid)`
  - Session identifier
  - Ejemplo: `660e8400-e29b-41d4-a716-446655440011`
- **loginTimestamp**: `string (date-time)`
  - ISO 8601 timestamp
  - Ejemplo: `2024-11-01T10:30:00+00:00`

### Status 422
Validation error - Invalid input data

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `The email field is required. (and 2 more errors)`
- **errors**: `object`
  - Field-specific validation errors
  - Ejemplo: `{'email': ['El email es requerido.'], 'password': ['La contraseña debe tener al menos 8 caracteres.'], 'acceptsTerms': ['Debes aceptar los términos de servicio.']}`

### Status 409
Conflict - Email already registered

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Este email ya está registrado.`
- **errors**: `object`
  - Ejemplo: `{'email': ['Este email ya está registrado.']}`

---

### `POST` /api/auth/login

**Resumen**: Login user

**Descripción**: Authenticates a user using email and password credentials. Generates a new JWT access token and refresh token for the session. The refresh token is securely stored in an HttpOnly cookie. Device information is automatically captured from the request headers for session tracking.

**Operation ID**: `7571e1edb2e53d940f2029c7829e720d`

**Autenticación**:
No requiere autenticación

**Request Body**:
**Descripción**: Login credentials

**Content-Type**: `application/json`

- **email** (required): `string (email)`
  - User email address (case-insensitive)
  - Ejemplo: `lukqs05@gmail.com`
- **password** (required): `string (password)`
  - User password
  - Ejemplo: `mklmklmkl`
- **deviceName**: `string`
  - Optional custom device name (auto-detected if not provided)
  - Ejemplo: `fifon 15`
- **rememberMe**: `boolean`
  - Keep session active for longer (currently not implemented)
  - Ejemplo: `True`

**Respuestas**:

### Status 200
Login successful. Returns authentication tokens and user data. Refresh token is set in HttpOnly cookie named "refresh_token".

**Headers**:
- **Set-Cookie**: HttpOnly cookie containing refresh token (name: refresh_token, path: /, httpOnly: true, sameSite: lax, maxAge: 43200 minutes)

**Content-Type**: `application/json`

- **accessToken**: `string`
  - JWT access token for API authentication
  - Ejemplo: `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...`
- **refreshToken**: `string`
  - Information message (actual token is in HttpOnly cookie)
  - Ejemplo: `Refresh token set in httpOnly cookie`
- **tokenType**: `string`
  - Token type
  - Ejemplo: `Bearer`
- **expiresIn**: `integer`
  - Access token expiration time in seconds
  - Ejemplo: `2592000`
- **user**: `object`
  - Authenticated user information
  - **id**: `string (uuid)`
    - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
  - **userCode**: `string`
    - Ejemplo: `USR-20241101-001`
  - **email**: `string (email)`
    - Ejemplo: `user@example.com`
  - **emailVerified**: `boolean`
    - Ejemplo: `True`
  - **onboardingCompleted**: `boolean`
    - Ejemplo: `True`
  - **status**: `string`
    - Ejemplo: `ACTIVE`
  - **displayName**: `string`
    - Ejemplo: `Juan Pérez`
  - **avatarUrl**: `string`
    - Ejemplo: `https://example.com/avatars/user.jpg`
  - **theme**: `string`
    - Ejemplo: `light`
  - **language**: `string`
    - Ejemplo: `es`
  - **roleContexts**: `array`
    - User roles and their associated contexts
    Items:
      - **roleCode**: `string`
        - Ejemplo: `COMPANY_ADMIN`
      - **roleName**: `string`
        - Ejemplo: `Administrador de Empresa`
      - **dashboardPath**: `string`
        - Ejemplo: `/empresa/dashboard`
      - **company**: `object`
        - **id**: `string (uuid)`
          - Ejemplo: `770e8400-e29b-41d4-a716-446655440088`
        - **companyCode**: `string`
          - Ejemplo: `CMP-20241101-001`
        - **name**: `string`
          - Ejemplo: `Acme Corp`
- **sessionId**: `string (uuid)`
  - Session identifier
  - Ejemplo: `660e8400-e29b-41d4-a716-446655440011`
- **loginTimestamp**: `string (date-time)`
  - ISO 8601 timestamp
  - Ejemplo: `2024-11-01T10:30:00+00:00`

### Status 422
Validation error - Invalid input data

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `The email field is required.`
- **errors**: `object`
  - Field-specific validation errors
  - Ejemplo: `{'email': ['El email es requerido.'], 'password': ['La contraseña es requerida.']}`

### Status 401
Unauthorized - Invalid credentials or user account is suspended/inactive

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Invalid credentials`
- **error**: `string`
  - Ejemplo: `INVALID_CREDENTIALS`

---

### `POST` /api/auth/login/google

**Resumen**: Login with Google OAuth (NOT IMPLEMENTED)

**Descripción**: Authenticate user using Google ID token. If the user does not exist, it will be automatically created. NOTE: This endpoint is currently NOT IMPLEMENTED and will return HTTP 501 (Not Implemented). It is planned for a future release.

**Operation ID**: `09fd714107f3ee9528c85d31498e16ab`

**Autenticación**:
No requiere autenticación

**Request Body**:
**Descripción**: Google authentication token

**Content-Type**: `application/json`

- **googleToken** (required): `string`
  - Google ID token obtained from Google OAuth flow
  - Ejemplo: `eyJhbGciOiJSUzI1NiIsImtpZCI6IjZmNzI1NDEwMWY1NmU0M2...`

**Respuestas**:

### Status 501
Not Implemented - This feature is not yet available

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Google login not yet implemented`

### Status 422
Validation error - Invalid input data (when implemented)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `The googleToken field is required.`
- **errors**: `object`
  - Field-specific validation errors
  - Ejemplo: `{'googleToken': ['The googleToken field is required.']}`

### Status 401
Unauthorized - Invalid or expired Google token (when implemented)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Invalid Google token`
- **error**: `string`
  - Ejemplo: `INVALID_GOOGLE_TOKEN`

---

### `GET` /api/auth/status

**Resumen**: Get authentication status

**Descripción**: Retrieves the current authentication status for the authenticated user. Returns complete user information, active session details, and JWT token metadata. Requires a valid Bearer token in the Authorization header. This endpoint is useful for checking if a user session is still valid and retrieving updated user data.

**Operation ID**: `8a24b7b2f49b7e0cb087a5b10704059b`

**Autenticación**:
- bearerAuth

**Respuestas**:

### Status 200
Authentication status retrieved successfully

**Content-Type**: `application/json`

- **isAuthenticated**: `boolean`
  - Always true for successful responses
  - Ejemplo: `True`
- **user**: `object`
  - Current authenticated user information
  - **id**: `string (uuid)`
    - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
  - **userCode**: `string`
    - Ejemplo: `USR-20241101-001`
  - **email**: `string (email)`
    - Ejemplo: `user@example.com`
  - **emailVerified**: `boolean`
    - Ejemplo: `True`
  - **onboardingCompleted**: `boolean`
    - Ejemplo: `True`
  - **status**: `string`
    - Ejemplo: `ACTIVE`
  - **displayName**: `string`
    - Ejemplo: `Juan Pérez`
  - **avatarUrl**: `string`
    - Ejemplo: `https://example.com/avatars/user.jpg`
  - **theme**: `string`
    - Ejemplo: `light`
  - **language**: `string`
    - Ejemplo: `es`
  - **roleContexts**: `array`
    - User roles and their associated contexts
    Items:
      - **roleCode**: `string`
        - Ejemplo: `USER`
      - **roleName**: `string`
        - Ejemplo: `Cliente`
      - **dashboardPath**: `string`
        - Ejemplo: `/tickets`
      - **company**: `object`
        - **id**: `string (uuid)`
          - Ejemplo: `770e8400-e29b-41d4-a716-446655440088`
        - **companyCode**: `string`
          - Ejemplo: `CMP-20241101-001`
        - **name**: `string`
          - Ejemplo: `Acme Corp`
- **currentSession**: `object`
  - Current active session information (null if session not found)
  - **sessionId**: `string (uuid)`
    - Ejemplo: `660e8400-e29b-41d4-a716-446655440011`
  - **deviceName**: `string`
    - Ejemplo: `Chrome on Windows`
  - **ipAddress**: `string (ipv4)`
    - Ejemplo: `192.168.1.100`
  - **userAgent**: `string`
    - Ejemplo: `Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36...`
  - **lastUsedAt**: `string (date-time)`
    - Ejemplo: `2024-11-01T10:25:00+00:00`
  - **expiresAt**: `string (date-time)`
    - Ejemplo: `2024-12-01T10:30:00+00:00`
  - **isCurrent**: `boolean`
    - Ejemplo: `True`
- **tokenInfo**: `object`
  - JWT token metadata
  - **expiresIn**: `integer`
    - Seconds until token expires
    - Ejemplo: `2591000`
  - **issuedAt**: `string (date-time)`
    - Token issue timestamp
    - Ejemplo: `2024-11-01T10:30:00+00:00`
  - **tokenType**: `string`
    - Ejemplo: `Bearer`

### Status 401
Unauthorized - Invalid, expired, or missing access token

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Unauthenticated.`

---

### `POST` /api/auth/refresh

**Resumen**: Refresh access token

**Descripción**: Generates a new JWT access token and refresh token using a valid refresh token. The refresh token is read from an HttpOnly cookie (recommended for security), or alternatively from the X-Refresh-Token header (for testing in Swagger) or request body. This endpoint implements token rotation: the old refresh token is invalidated and a new one is generated. The new refresh token is automatically set in an HttpOnly cookie.

**Operation ID**: `3c243abb35865fca479a53debfe9abae`

**Autenticación**:
No requiere autenticación

**Parámetros**:
- **X-Refresh-Token** (header): `string`
  - Refresh token for testing in Swagger UI. In production, the refresh token should be sent via HttpOnly cookie (automatically handled by browsers). This header takes priority over cookie and body.

**Request Body**:
**Descripción**: Alternative method to send refresh token (not recommended for production)

**Content-Type**: `application/json`

- **refreshToken**: `string`
  - Refresh token (use only if cookie and header are not available)
  - Ejemplo: `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...`

**Respuestas**:

### Status 200
Token refreshed successfully. New access token returned in response body. New refresh token set in HttpOnly cookie.

**Headers**:
- **Set-Cookie**: HttpOnly cookie containing new refresh token (name: refresh_token, path: /, httpOnly: true, sameSite: strict, secure: true in production, maxAge: 43200 minutes / 30 days)

**Content-Type**: `application/json`

- **accessToken**: `string`
  - New JWT access token for API authentication
  - Ejemplo: `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...`
- **tokenType**: `string`
  - Token type
  - Ejemplo: `Bearer`
- **expiresIn**: `integer`
  - Access token expiration time in seconds
  - Ejemplo: `2592000`
- **message**: `string`
  - Success message explaining that refresh token is in cookie
  - Ejemplo: `Token refreshed successfully. New refresh token set in HttpOnly cookie.`

### Status 401
Unauthorized - Invalid, expired, or missing refresh token

**Content-Type**: `application/json`

- **message**: `string`
  - Human-readable error message
  - Ejemplo: `Invalid or expired refresh token. Please login again.`
- **error**: `string`
  - Error code for programmatic handling
  - Ejemplo: `INVALID_REFRESH_TOKEN`

---

## Companies

### `GET` /api/companies/minimal

**Resumen**: List minimal companies for selectors

**Descripción**: Returns a paginated list of active companies with minimal information (id, code, name, logo). Public endpoint without authentication required.

**Operation ID**: `list_companies_minimal`

**Autenticación**:
No requiere autenticación

**Parámetros**:
- **search** (query): `string`
  - Filter companies by name (case-insensitive search)
- **per_page** (query): `integer`
  - Number of items per page
- **page** (query): `integer`
  - Page number for pagination

**Respuestas**:

### Status 200
Minimal company list with pagination

**Content-Type**: `application/json`

- **data**: `array`
  Items:
    - **id**: `string (uuid)`
      - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
    - **company_code**: `string`
      - Ejemplo: `CMP-2025-00001`
    - **name**: `string`
      - Ejemplo: `Acme Corporation`
    - **logo_url**: `string`
      - Ejemplo: `https://example.com/logo.png`
- **meta**: `object`
  - **total**: `integer`
    - Ejemplo: `150`
  - **current_page**: `integer`
    - Ejemplo: `1`
  - **last_page**: `integer`
    - Ejemplo: `3`
  - **per_page**: `integer`
    - Ejemplo: `50`
- **links**: `object`
  - **first**: `string`
  - **last**: `string`
  - **prev**: `string`
  - **next**: `string`

---

### `GET` /api/companies/explore

**Resumen**: Explore companies with filters

**Descripción**: Returns paginated list of companies with extended information for exploration. Includes follow indicators specific to authenticated user. Requires JWT authentication.

**Operation ID**: `explore_companies`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **search** (query): `string`
  - Search companies by name
- **industry_id** (query): `string`
  - Filter by industry UUID
- **country** (query): `string`
  - Filter by country
- **followed_by_me** (query): `boolean`
  - Show only companies followed by the user
- **sort_by** (query): `string`
  - Field to sort results by
- **sort_direction** (query): `string`
  - Sort direction
- **per_page** (query): `integer`
  - Number of items per page
- **page** (query): `integer`
  - Page number for pagination

**Respuestas**:

### Status 200
Company list with extended information

**Content-Type**: `application/json`

- **data**: `array`
  Items:
    - **id**: `string (uuid)`
    - **company_code**: `string`
    - **name**: `string`
    - **logo_url**: `string`
    - **website**: `string`
    - **contact_country**: `string`
    - **industry_id**: `string (uuid)`
    - **industry**: `object`
      - **id**: `string (uuid)`
      - **code**: `string`
      - **name**: `string`
    - **followers_count**: `integer`
    - **is_followed_by_me**: `boolean`
- **meta**: `object`
  - **total**: `integer`
  - **current_page**: `integer`
  - **last_page**: `integer`
  - **per_page**: `integer`
- **links**: `object`
  - **first**: `string`
  - **last**: `string`
  - **prev**: `string`
  - **next**: `string`

### Status 401
Unauthenticated (invalid or missing JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Unauthenticated.`

---

### `GET` /api/companies

**Resumen**: List all companies (admin)

**Descripción**: Returns complete company list with all information and calculated fields. Requires HELPDESK_ADMIN or COMPANY_ADMIN role. COMPANY_ADMIN users can only see their own company.

**Operation ID**: `list_companies`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **search** (query): `string`
  - Search companies by name
- **status** (query): `string`
  - Filter by company status
- **industry_id** (query): `string`
  - Filter by industry UUID
- **sort_by** (query): `string`
  - Field to sort results by
- **sort_direction** (query): `string`
  - Sort direction
- **per_page** (query): `integer`
  - Number of items per page
- **page** (query): `integer`
  - Page number for pagination

**Respuestas**:

### Status 200
Complete company list with administrative information

**Content-Type**: `application/json`

- **data**: `array`
  Items:
    - **id**: `string (uuid)`
    - **company_code**: `string`
    - **name**: `string`
    - **status**: `string`
    - **industry_id**: `string (uuid)`
    - **industry**: `object`
      - **id**: `string (uuid)`
      - **code**: `string`
      - **name**: `string`
      - **description**: `string`
    - **admin**: `object`
    - **followers_count**: `integer`
    - **active_agents_count**: `integer`
    - **total_users_count**: `integer`
    - **created_at**: `string (date-time)`
- **meta**: `object`
  - **total**: `integer`
  - **current_page**: `integer`
  - **last_page**: `integer`
  - **per_page**: `integer`
- **links**: `object`
  - **first**: `string`
  - **last**: `string`
  - **prev**: `string`
  - **next**: `string`

### Status 401
Unauthenticated (invalid or missing JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden (requires administrator role)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Forbidden.`

---

### `POST` /api/companies

**Resumen**: Create new company

**Descripción**: Creates a new company directly without request process. Only available for users with PLATFORM_ADMIN role. Automatically assigns COMPANY_ADMIN role to the designated administrator user.

**Operation ID**: `create_company`

**Autenticación**:
- bearerAuth

**Request Body**:
**Descripción**: New company data

**Content-Type**: `application/json`

- **name** (required): `string`
  - Company trade name
  - Ejemplo: `Acme Corporation`
- **legal_name** (required): `string`
  - Legal company name
  - Ejemplo: `Acme Corp S.A.`
- **support_email** (required): `string (email)`
  - Support email address
  - Ejemplo: `support@acme.com`
- **phone**: `string`
  - Contact phone number
  - Ejemplo: `+56912345678`
- **website**: `string (uri)`
  - Company website
  - Ejemplo: `https://acme.com`
- **admin_user_id** (required): `string (uuid)`
  - User ID who will be the company administrator (required)
- **contact_address**: `string`
  - Physical address
- **contact_city**: `string`
  - City
- **contact_state**: `string`
  - State/Region
- **contact_country**: `string`
  - Country
- **contact_postal_code**: `string`
  - Postal code
- **tax_id**: `string`
  - Tax ID (RUT/NIT)
- **legal_representative**: `string`
  - Legal representative name
- **business_hours**: `object`
  - Business hours (JSONB)
  - Ejemplo: `{'monday': {'open': '09:00', 'close': '18:00'}}`
- **timezone**: `string`
  - Timezone (e.g., America/Santiago)
  - Ejemplo: `America/Santiago`
- **settings**: `object`
  - Additional settings (JSONB)

**Respuestas**:

### Status 201
Company created successfully

**Content-Type**: `application/json`

- **id**: `string (uuid)`
- **company_code**: `string`
- **name**: `string`
- **status**: `string`
  - Ejemplo: `active`
- **admin**: `object`
- **created_at**: `string (date-time)`

### Status 401
Unauthenticated

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden (requires PLATFORM_ADMIN role)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Forbidden.`

### Status 422
Validation error

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`

---

### `GET` /api/companies/{company}

**Resumen**: View complete company details

**Descripción**: Returns all information about a specific company including calculated fields, admin info, and user follow status. Requires authentication and access permissions.

**Operation ID**: `show_company`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **company** (required) (path): `string`
  - Company ID or UUID

**Respuestas**:

### Status 200
Complete company details

**Content-Type**: `application/json`

- **id**: `string (uuid)`
- **company_code**: `string`
- **name**: `string`
- **legal_name**: `string`
- **status**: `string`
- **support_email**: `string (email)`
- **phone**: `string`
- **website**: `string`
- **logo_url**: `string`
- **industry_id**: `string (uuid)`
- **industry**: `object`
  - **id**: `string (uuid)`
  - **code**: `string`
  - **name**: `string`
  - **description**: `string`
- **admin**: `object`
- **followers_count**: `integer`
- **active_agents_count**: `integer`
- **total_users_count**: `integer`
- **is_followed_by_me**: `boolean`
- **created_at**: `string (date-time)`
- **updated_at**: `string (date-time)`

### Status 401
Unauthenticated (invalid or missing JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden (no permission to view this company)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Forbidden.`

### Status 404
Company not found

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Company not found.`

---

### `PATCH` /api/companies/{company}

**Resumen**: Update company

**Descripción**: Updates information of an existing company. Requires being PLATFORM_ADMIN or COMPANY_ADMIN owner of the company. All fields are optional.

**Operation ID**: `update_company`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **company** (required) (path): `string`
  - Company ID or UUID to update

**Request Body**:
**Descripción**: Data to update (all fields are optional)

**Content-Type**: `application/json`

- **name**: `string`
  - Trade name (2-255 characters)
- **legal_name**: `string`
  - Legal name (2-255 characters)
- **support_email**: `string (email)`
  - Support email (max 255 characters)
- **phone**: `string`
  - Phone number (max 20 characters)
- **website**: `string (uri)`
  - Website URL (max 255 characters)
- **contact_address**: `string`
  - Address (max 255 characters)
- **contact_city**: `string`
  - City (max 100 characters)
- **contact_state**: `string`
  - State/Region (max 100 characters)
- **contact_country**: `string`
  - Country (max 100 characters)
- **contact_postal_code**: `string`
  - Postal code (max 20 characters)
- **tax_id**: `string`
  - Tax ID (RUT/NIT, max 50 characters)
- **legal_representative**: `string`
  - Legal representative (max 255 characters)
- **business_hours**: `object`
  - Business hours (JSONB)
- **timezone**: `string`
  - Timezone (e.g., America/Santiago)
- **logo_url**: `string (uri)`
  - Logo URL
- **favicon_url**: `string (uri)`
  - Favicon URL
- **primary_color**: `string`
  - Primary color in hexadecimal format
  - Ejemplo: `#FF5733`
- **secondary_color**: `string`
  - Secondary color in hexadecimal format
  - Ejemplo: `#33FF57`
- **settings**: `object`
  - Additional settings (JSONB)

**Respuestas**:

### Status 200
Company updated successfully

**Content-Type**: `application/json`

- **id**: `string (uuid)`
- **company_code**: `string`
- **name**: `string`
- **status**: `string`
- **admin**: `object`
- **followers_count**: `integer`
- **active_agents_count**: `integer`
- **total_users_count**: `integer`
- **is_followed_by_me**: `boolean`
- **updated_at**: `string (date-time)`

### Status 401
No autenticado

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden (no permission to update this company)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Forbidden.`

### Status 404
Company not found

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Company not found.`

### Status 422
Validation error

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`

---

## Company Followers

### `GET` /api/companies/followed

**Resumen**: List companies followed by authenticated user

**Descripción**: Returns all companies that the authenticated user is following, ordered by most recent follow first. Includes company details and user-specific metrics like ticket count.

**Operation ID**: `list_followed_companies`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **page** (query): `integer`
  - Page number for pagination
- **per_page** (query): `integer`
  - Number of items per page

**Respuestas**:

### Status 200
List of companies followed by the authenticated user

**Content-Type**: `application/json`

- **data**: `array`
  Items:
    - **id**: `string (uuid)`
    - **company**: `object`
      - **id**: `string (uuid)`
      - **companyCode**: `string`
      - **name**: `string`
      - **logoUrl**: `string (uri)`
    - **followedAt**: `string (date-time)`
    - **myTicketsCount**: `integer`
    - **lastTicketCreatedAt**: `string (date-time)`
    - **hasUnreadAnnouncements**: `boolean`

### Status 401
Unauthenticated


---

### `GET` /api/companies/{company}/is-following

**Resumen**: Check if user follows a company

**Descripción**: Verifies if the authenticated user is currently following the specified company.

**Operation ID**: `check_if_following`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **company** (required) (path): `string`
  - Company UUID or identifier

**Respuestas**:

### Status 200
Follow status retrieved successfully

**Content-Type**: `application/json`

- **success**: `boolean`
- **data**: `object`
  - **isFollowing**: `boolean`

### Status 401
Unauthenticated


### Status 404
Company not found


---

### `POST` /api/companies/{company}/follow

**Resumen**: Follow a company

**Descripción**: Allows the authenticated user to start following a company. If already following, returns current follow status. Rate limited to 20 requests per hour.

**Operation ID**: `follow_company`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **company** (required) (path): `string`
  - Company UUID or identifier

**Respuestas**:

### Status 200
Already following the company

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
- **company**: `object`
  - **id**: `string (uuid)`
  - **companyCode**: `string`
  - **name**: `string`
  - **logoUrl**: `string (uri)`
- **followedAt**: `string (date-time)`

### Status 201
Successfully started following the company

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
- **company**: `object`
  - **id**: `string (uuid)`
  - **companyCode**: `string`
  - **name**: `string`
  - **logoUrl**: `string (uri)`
- **followedAt**: `string (date-time)`

### Status 401
Unauthenticated


### Status 404
Company not found


### Status 409
Already following this company


### Status 429
Too many follow requests (rate limit exceeded)


---

### `DELETE` /api/companies/{company}/unfollow

**Resumen**: Unfollow a company

**Descripción**: Allows the authenticated user to stop following a company. Returns an error if the user is not currently following the company.

**Operation ID**: `unfollow_company`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **company** (required) (path): `string`
  - Company UUID or identifier

**Respuestas**:

### Status 200
Successfully unfollowed the company

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`

### Status 401
Unauthenticated


### Status 404
Company not found


### Status 409
User is not following this company


---

## Company Industries

### `GET` /api/company-industries

**Resumen**: Listar todas las industrias disponibles

**Descripción**: Obtiene el catálogo completo de industrias para selección en formularios. Endpoint público sin autenticación requerida. Opcionalmente incluye conteos de empresas activas por industria.

**Operation ID**: `list_company_industries`

**Autenticación**:
No requiere autenticación

**Parámetros**:
- **with_counts** (query): `boolean`
  - Incluir conteo de empresas activas por industria (default: false)

**Respuestas**:

### Status 200
Lista de industrias obtenida exitosamente

**Content-Type**: `application/json`

- **data**: `array`
  Items:
    - **id**: `string (uuid)`
      - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
    - **code**: `string`
      - Código único de la industria
      - Ejemplo: `technology`
    - **name**: `string`
      - Nombre de la industria
      - Ejemplo: `Tecnología`
    - **description**: `string`
      - Descripción de la industria
      - Ejemplo: `Empresas de tecnología y software`
    - **createdAt**: `string (date-time)`
      - Ejemplo: `2025-10-31T12:00:00Z`
    - **activeCompaniesCount**: `integer`
      - Conteo de empresas activas (solo si with_counts=true)
      - Ejemplo: `45`

---

## Company Requests

### `GET` /api/company-requests

**Resumen**: List company requests

**Descripción**: Returns paginated list of company requests. Requires PLATFORM_ADMIN role. Includes eager loading of reviewer and createdCompany.

**Operation ID**: `list_company_requests`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **status** (query): `string`
  - Filter by request status
- **search** (query): `string`
  - Search by company name
- **sort** (query): `string`
  - Field to sort by
- **order** (query): `string`
  - Sort direction
- **per_page** (query): `integer`
  - Number of items per page
- **page** (query): `integer`
  - Page number

**Respuestas**:

### Status 200
List of company requests

**Content-Type**: `application/json`

- **data**: `array`
  Items:
    - **id**: `string (uuid)`
      - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
    - **requestCode**: `string`
      - Ejemplo: `REQ-20251101-001`
    - **companyName**: `string`
      - Ejemplo: `TechCorp Solutions`
    - **legalName**: `string`
      - Ejemplo: `TechCorp Solutions S.A.`
    - **adminEmail**: `string (email)`
      - Ejemplo: `admin@techcorp.com`
    - **businessDescription**: `string`
      - Ejemplo: `We are a leading technology solutions company with over 10 years of experience providing enterprise software solutions to businesses worldwide.`
    - **requestMessage**: `string`
      - Ejemplo: `We need a professional helpdesk system for our customer support team of 50+ agents.`
    - **website**: `string (uri)`
      - Ejemplo: `https://techcorp.com`
    - **industryId**: `string (uuid)`
      - Ejemplo: `650e8400-e29b-41d4-a716-446655440001`
    - **industry**: `object`
      - **id**: `string (uuid)`
        - Ejemplo: `650e8400-e29b-41d4-a716-446655440001`
      - **code**: `string`
        - Ejemplo: `TECH`
      - **name**: `string`
        - Ejemplo: `Technology`
    - **estimatedUsers**: `integer`
      - Ejemplo: `500`
    - **contactAddress**: `string`
      - Ejemplo: `Main Avenue 123, Office 456`
    - **contactCity**: `string`
      - Ejemplo: `Santiago`
    - **contactCountry**: `string`
      - Ejemplo: `Chile`
    - **contactPostalCode**: `string`
      - Ejemplo: `8340000`
    - **taxId**: `string`
      - Ejemplo: `12.345.678-9`
    - **status**: `string`
      - Ejemplo: `APPROVED`
    - **reviewedAt**: `string (date-time)`
      - Ejemplo: `2025-11-01T14:30:00Z`
    - **rejectionReason**: `string`
    - **reviewer**: `object`
      - **id**: `string (uuid)`
        - Ejemplo: `750e8400-e29b-41d4-a716-446655440002`
      - **user_code**: `string`
        - Ejemplo: `USR-ADMIN-001`
      - **email**: `string (email)`
        - Ejemplo: `platform.admin@helpdesk.com`
      - **name**: `string`
        - Ejemplo: `John Administrator`
    - **createdCompany**: `object`
      - **id**: `string (uuid)`
        - Ejemplo: `850e8400-e29b-41d4-a716-446655440003`
      - **companyCode**: `string`
        - Ejemplo: `COMP-TECH-001`
      - **name**: `string`
        - Ejemplo: `TechCorp Solutions`
      - **logoUrl**: `string`
        - Ejemplo: `https://storage.example.com/logos/techcorp.png`
    - **createdAt**: `string (date-time)`
      - Ejemplo: `2025-11-01T10:00:00Z`
    - **updatedAt**: `string (date-time)`
      - Ejemplo: `2025-11-01T14:30:00Z`
- **meta**: `object`
  - **total**: `integer`
  - **current_page**: `integer`
  - **last_page**: `integer`
  - **per_page**: `integer`
- **links**: `object`
  - **first**: `string`
  - **last**: `string`
  - **prev**: `string`
  - **next**: `string`

### Status 401
Unauthenticated


### Status 403
Forbidden - requires PLATFORM_ADMIN role


---

### `POST` /api/company-requests

**Resumen**: Create company request

**Descripción**: Public endpoint to create new company request. Rate limit: 3 requests per hour. Automatic validation of duplicate email in pending requests.

**Operation ID**: `create_company_request`

**Autenticación**:
No requiere autenticación

**Request Body**:
**Descripción**: Company request data

**Content-Type**: `application/json`

- **company_name** (required): `string`
  - Company name (2-200 characters)
  - Ejemplo: `TechCorp Solutions`
- **legal_name**: `string`
  - Legal company name (2-200 characters)
  - Ejemplo: `TechCorp Solutions S.A.`
- **admin_email** (required): `string (email)`
  - Administrator email (max 255 characters)
  - Ejemplo: `admin@techcorp.com`
- **company_description** (required): `string`
  - Company description (50-1000 characters)
  - Ejemplo: `We are a leading technology solutions company with over 10 years of experience...`
- **website**: `string (uri)`
  - Company website (max 255 characters)
  - Ejemplo: `https://techcorp.com`
- **industry_id** (required): `string (uuid)`
  - Industry UUID (reference to company_industries)
  - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
- **request_message**: `string`
  - Request message (10-500 characters)
  - Ejemplo: `We need a professional helpdesk system for our customer support`
- **estimated_users**: `integer`
  - Estimated number of users (1-10000)
  - Ejemplo: `500`
- **contact_address**: `string`
  - Contact address (max 255 characters)
  - Ejemplo: `Main Avenue 123`
- **contact_city**: `string`
  - City (max 100 characters)
  - Ejemplo: `Santiago`
- **contact_country**: `string`
  - Country (max 100 characters)
  - Ejemplo: `Chile`
- **contact_postal_code**: `string`
  - Postal code (max 20 characters)
  - Ejemplo: `8340000`
- **tax_id**: `string`
  - Tax ID - RUT/NIT (max 50 characters)
  - Ejemplo: `12.345.678-9`

**Respuestas**:

### Status 201
Company request created successfully

**Content-Type**: `application/json`

- **id**: `string (uuid)`
  - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
- **requestCode**: `string`
  - Ejemplo: `REQ-20251101-001`
- **companyName**: `string`
  - Ejemplo: `TechCorp Solutions`
- **legalName**: `string`
  - Ejemplo: `TechCorp Solutions S.A.`
- **adminEmail**: `string (email)`
  - Ejemplo: `admin@techcorp.com`
- **businessDescription**: `string`
  - Ejemplo: `We are a leading technology solutions company...`
- **requestMessage**: `string`
  - Ejemplo: `We need a professional helpdesk system for our customer support`
- **website**: `string (uri)`
  - Ejemplo: `https://techcorp.com`
- **industryId**: `string (uuid)`
  - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
- **industry**: `object`
  - **id**: `string (uuid)`
    - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
  - **code**: `string`
    - Ejemplo: `TECH`
  - **name**: `string`
    - Ejemplo: `Technology`
- **estimatedUsers**: `integer`
  - Ejemplo: `500`
- **contactAddress**: `string`
  - Ejemplo: `Main Avenue 123`
- **contactCity**: `string`
  - Ejemplo: `Santiago`
- **contactCountry**: `string`
  - Ejemplo: `Chile`
- **contactPostalCode**: `string`
  - Ejemplo: `8340000`
- **taxId**: `string`
  - Ejemplo: `12.345.678-9`
- **status**: `string`
  - Ejemplo: `PENDING`
- **reviewedAt**: `string (date-time)`
- **rejectionReason**: `string`
- **reviewer**: `object`
  - **id**: `string (uuid)`
  - **user_code**: `string`
  - **email**: `string (email)`
  - **name**: `string`
- **createdCompany**: `object`
  - **id**: `string (uuid)`
  - **companyCode**: `string`
  - **name**: `string`
  - **logoUrl**: `string`
- **createdAt**: `string (date-time)`
  - Ejemplo: `2025-11-01T12:00:00Z`
- **updatedAt**: `string (date-time)`
  - Ejemplo: `2025-11-01T12:00:00Z`

### Status 422
Validation error - invalid data or duplicate email


### Status 429
Rate limit exceeded - maximum 3 requests per hour


---

## Company Requests - Admin

### `POST` /api/company-requests/{companyRequest}/approve

**Resumen**: Approve company request

**Descripción**: Approves a company request. Automatically: creates company, creates admin user, assigns COMPANY_ADMIN role, generates temporary password (valid 7 days), sends email with credentials

**Operation ID**: `approve_company_request`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **companyRequest** (required) (path): `string`
  - Company request UUID

**Request Body**:
**Descripción**: Additional approval data (optional)

**Content-Type**: `application/json`

- **notes**: `string`
  - Additional notes

**Respuestas**:

### Status 200
Request approved successfully

**Content-Type**: `application/json`

- **data**: `object`
  - **success**: `boolean`
    - Ejemplo: `True`
  - **message**: `string`
    - Ejemplo: `Solicitud aprobada exitosamente. Se ha creado la empresa 'TechCorp Bolivia' y se envió un email con las credenciales de acceso a admin@techcorp.com.bo.`
  - **company**: `object`
    - **id**: `string (uuid)`
      - Ejemplo: `9d8e4f1a-2b3c-4d5e-6f7a-8b9c0d1e2f3a`
    - **companyCode**: `string`
      - Ejemplo: `COMP-20250001`
    - **name**: `string`
      - Ejemplo: `TechCorp Bolivia`
    - **legalName**: `string`
      - Ejemplo: `TechCorp Bolivia S.R.L.`
    - **description**: `string`
      - Ejemplo: `Empresa líder en soluciones tecnológicas para el sector empresarial`
    - **status**: `string`
      - Ejemplo: `ACTIVE`
    - **industryId**: `string (uuid)`
      - Ejemplo: `7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f`
    - **industry**: `object`
      - **id**: `string (uuid)`
        - Ejemplo: `7c6d5e4f-3a2b-1c0d-9e8f-7a6b5c4d3e2f`
      - **code**: `string`
        - Ejemplo: `TECH`
      - **name**: `string`
        - Ejemplo: `Tecnología`
    - **adminId**: `string (uuid)`
      - Ejemplo: `1a2b3c4d-5e6f-7a8b-9c0d-1e2f3a4b5c6d`
    - **adminEmail**: `string (email)`
      - Ejemplo: `admin@techcorp.com.bo`
    - **adminName**: `string`
      - Ejemplo: `Juan Carlos Pérez`
    - **createdAt**: `string (date-time)`
      - Ejemplo: `2025-11-01T10:30:00+00:00`
  - **newUserCreated**: `boolean`
    - Ejemplo: `True`
  - **notificationSentTo**: `string (email)`
    - Ejemplo: `admin@techcorp.com.bo`

### Status 401
Unauthenticated


### Status 403
Forbidden - requires PLATFORM_ADMIN role


### Status 404
Request not found


### Status 409
Request already processed


### Status 422
Validation error


---

### `POST` /api/company-requests/{companyRequest}/reject

**Resumen**: Reject company request

**Descripción**: Rejects a company request. Automatically: marks as REJECTED, sends email to requester with rejection reason

**Operation ID**: `reject_company_request`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **companyRequest** (required) (path): `string`
  - Company request UUID

**Request Body**:
**Descripción**: Rejection reason

**Content-Type**: `application/json`

- **reason** (required): `string`
  - Rejection reason (required, minimum 10 characters)
- **notes**: `string`
  - Additional notes

**Respuestas**:

### Status 200
Request rejected successfully

**Content-Type**: `application/json`

- **data**: `object`
  - **success**: `boolean`
    - Ejemplo: `True`
  - **message**: `string`
    - Ejemplo: `La solicitud de empresa 'TechCorp Bolivia' ha sido rechazada. Se ha enviado un email a admin@techcorp.com.bo con la razón del rechazo.`
  - **reason**: `string`
    - Rejection reason
    - Ejemplo: `La documentación proporcionada no cumple con los requisitos mínimos establecidos. Por favor, adjunte el NIT actualizado y el testimonio de constitución.`
  - **notificationSentTo**: `string (email)`
    - Ejemplo: `admin@techcorp.com.bo`
  - **requestCode**: `string`
    - Ejemplo: `REQ-20250001`

### Status 401
Unauthenticated


### Status 403
Forbidden - requires PLATFORM_ADMIN role


### Status 404
Request not found


### Status 409
Request already processed


### Status 422
Validation error - reason is required


---

## Email Verification

### `POST` /api/auth/email/verify

**Resumen**: Verify email

**Descripción**: Verify user email with token. Public endpoint (no authentication required). Always returns 200 status, check "success" field in response body to determine result.

**Operation ID**: `077140d57b4d70ffd89a68e5a5899364`

**Autenticación**:
No requiere autenticación

**Request Body**:
**Content-Type**: `application/json`

- **token** (required): `string`
  - Email verification token (received via email)
  - Ejemplo: `abc123def456ghi789jkl012mno345pq`

**Respuestas**:

### Status 200
Verification result (always returns 200, check success field)

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `¡Email verificado exitosamente! Ya puedes usar todas las funciones del sistema.`
- **canResend**: `boolean`
- **resendAvailableAt**: `string`

---

### `POST` /api/auth/email/verify/resend

**Resumen**: Resend verification email

**Descripción**: Resend verification email to authenticated user. Rate limited: 3 attempts every 5 minutes. Always returns success message for security (even if already verified).

**Operation ID**: `68696da7a70f3e6377685eab5496aa04`

**Autenticación**:
- bearerAuth

**Respuestas**:

### Status 200
Email resent successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Email de verificación enviado correctamente. Revisa tu bandeja de entrada.`
- **canResend**: `boolean`
- **resendAvailableAt**: `string (date-time)`
  - Ejemplo: `2025-11-01T15:30:00+00:00`

### Status 401
Unauthenticated


### Status 429
Rate limit exceeded (3 attempts per 5 minutes)


---

### `GET` /api/auth/email/status

**Resumen**: Get email verification status

**Descripción**: Get email verification status for authenticated user. Returns verification state, timestamps, and resend availability.

**Operation ID**: `f9d76d9ffd5f5b4d7f72eee0ba75f695`

**Autenticación**:
- bearerAuth

**Respuestas**:

### Status 200
Status retrieved successfully

**Content-Type**: `application/json`

- **is_verified**: `boolean`
- **email**: `string (email)`
  - Ejemplo: `user@example.com`
- **verified_at**: `string (date-time)`
  - Ejemplo: `2025-11-01T10:30:00+00:00`
- **can_resend**: `boolean`
  - Ejemplo: `True`
- **resend_available_at**: `string (date-time)`
  - Ejemplo: `2025-11-01T15:30:00+00:00`
- **attempts_remaining**: `integer`
  - Ejemplo: `3`

### Status 401
Unauthenticated


---

## Health

### `GET` /api/health

**Resumen**: Health check

**Descripción**: Verify that the API is operational and responding correctly

**Operation ID**: `1c4061d7fcb253d4c29c0060f524b139`

**Autenticación**:
No requiere autenticación

**Respuestas**:

### Status 200
API is operational

**Content-Type**: `application/json`

- **status**: `string`
  - Health status of the API
  - Ejemplo: `ok`
- **timestamp**: `string (date-time)`
  - ISO 8601 timestamp of the health check
  - Ejemplo: `2025-11-01T12:00:00+00:00`

---

## Help Center: Articles

### `GET` /api/help-center/articles

**Resumen**: List help center articles

**Descripción**: List help center articles with advanced filtering, searching, sorting, and pagination. Visibility rules vary by user role: END_USER sees only PUBLISHED articles from followed companies, COMPANY_ADMIN sees all articles (PUBLISHED + DRAFT) from their company, PLATFORM_ADMIN sees all articles from all companies.

**Operation ID**: `list_articles`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **page** (query): `integer`
  - Page number for pagination (1-indexed)
- **per_page** (query): `integer`
  - Number of items per page (max 100)
- **search** (query): `string`
  - Search term (case-insensitive) to search in title and content fields
- **category** (query): `string`
  - Filter by category code (ACCOUNT_PROFILE, SECURITY_PRIVACY, BILLING_PAYMENTS, TECHNICAL_SUPPORT)
- **status** (query): `string`
  - Filter by article status. Default: PUBLISHED for END_USER, ALL for COMPANY_ADMIN/PLATFORM_ADMIN
- **sort** (query): `string`
  - Sort field and direction. Use "-" prefix for descending. Options: title, views (views_count), created_at
- **company_id** (query): `string`
  - Filter by company ID (only for COMPANY_ADMIN of that company or PLATFORM_ADMIN). END_USER cannot use this parameter.

**Respuestas**:

### Status 200
Articles retrieved successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **data**: `array`
  Items:
    - **id**: `string (uuid)`
    - **company_id**: `string (uuid)`
    - **author_id**: `string (uuid)`
    - **category_id**: `string (uuid)`
    - **title**: `string`
    - **excerpt**: `string`
    - **content**: `string`
    - **status**: `string`
    - **views_count**: `integer`
    - **published_at**: `string (date-time)`
    - **created_at**: `string (date-time)`
    - **updated_at**: `string (date-time)`
- **meta**: `object`
  - **current_page**: `integer`
  - **per_page**: `integer`
  - **total**: `integer`
  - **last_page**: `integer`

### Status 401
Unauthorized - Missing or invalid JWT token

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden - User does not have permission to access articles from specified company

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Insufficient permissions`

### Status 404
Not Found - Company or category does not exist

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Resource not found`

### Status 500
Internal Server Error - Unexpected error occurred

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

### `POST` /api/help-center/articles

**Resumen**: Create a new article

**Descripción**: Create a new help center article in DRAFT status. Only COMPANY_ADMIN users can create articles. Company ID is automatically inferred from JWT token. Author ID is set to the authenticated user. Articles always start in DRAFT status regardless of the action parameter. Category must be one of the 4 global categories.

**Operation ID**: `create_article`

**Autenticación**:
- bearerAuth

**Request Body**:
**Descripción**: Article data to create

**Content-Type**: `application/json`

- **category_id** (required): `string (uuid)`
  - Article category ID (must exist and be a global category)
- **title** (required): `string`
  - Article title
- **content** (required): `string`
  - Article content (full body)
- **excerpt**: `string`
  - Brief excerpt/summary (optional)

**Respuestas**:

### Status 201
Article created successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **data**: `object`
  - **id**: `string (uuid)`
  - **company_id**: `string (uuid)`
    - Set from JWT token
  - **author_id**: `string (uuid)`
    - Set to authenticated user ID
  - **category_id**: `string (uuid)`
  - **title**: `string`
  - **excerpt**: `string`
  - **content**: `string`
  - **status**: `string`
    - Always DRAFT on creation
    - Ejemplo: `DRAFT`
  - **views_count**: `integer`
    - Always 0 on creation
  - **published_at**: `string (date-time)`
    - Always null on creation
  - **created_at**: `string (date-time)`
  - **updated_at**: `string (date-time)`

### Status 401
Unauthorized - Missing or invalid JWT token

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden - Only COMPANY_ADMIN can create articles

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Insufficient permissions`

### Status 422
Unprocessable Entity - Validation failed

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`
  - **category_id**: `array`
    - Ejemplo: `['The category_id field is required.']`
  - **title**: `array`
    - Ejemplo: `['The title must be at least 3 characters.']`
  - **content**: `array`
    - Ejemplo: `['The content must be at least 50 characters.']`
  - **excerpt**: `array`
    - Ejemplo: `['The excerpt must not be greater than 500 characters.']`

### Status 500
Internal Server Error - Unexpected error occurred

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

### `GET` /api/help-center/articles/{id}

**Resumen**: View a single article

**Descripción**: Retrieve a single help center article by ID. Visibility rules: END_USER can only view PUBLISHED articles from companies they follow. COMPANY_ADMIN can view any article (PUBLISHED or DRAFT) from their company. PLATFORM_ADMIN can view any article from any company. Automatically increments views_count by 1 when a PUBLISHED article is viewed (DRAFT articles do not increment views_count).

**Operation ID**: `view_article`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Article unique identifier (UUID)

**Respuestas**:

### Status 200
Article retrieved successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Article retrieved successfully`
- **data**: `object`
  - **id**: `string (uuid)`
  - **company_id**: `string (uuid)`
  - **author_id**: `string (uuid)`
  - **category_id**: `string (uuid)`
  - **title**: `string`
  - **excerpt**: `string`
  - **content**: `string`
  - **status**: `string`
  - **views_count**: `integer`
    - Incremented by 1 if article status is PUBLISHED
  - **published_at**: `string (date-time)`
  - **created_at**: `string (date-time)`
  - **updated_at**: `string (date-time)`

### Status 401
Unauthorized - Missing or invalid JWT token

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden - User does not have permission to view this article (e.g., DRAFT article from different company, or PUBLISHED article from non-followed company)

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Insufficient permissions`

### Status 404
Not Found - Article does not exist or has been deleted

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Article not found`

### Status 500
Internal Server Error - Unexpected error occurred

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

### `PUT` /api/help-center/articles/{id}

**Resumen**: Update an article

**Descripción**: Update an existing help center article. Only COMPANY_ADMIN can update articles from their company. Articles can be updated in any status (DRAFT or PUBLISHED). For PUBLISHED articles, published_at timestamp is preserved. Views count is always preserved. Title must be unique per company. Category can be changed to any valid global category. Partial updates are supported.

**Operation ID**: `update_article`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Article unique identifier (UUID)

**Request Body**:
**Descripción**: Article fields to update (all fields optional for partial updates)

**Content-Type**: `application/json`

- **category_id**: `string (uuid)`
  - Article category ID (optional, must exist)
- **title**: `string`
  - Article title (optional, must be unique per company)
- **content**: `string`
  - Article content (optional)
- **excerpt**: `string`
  - Brief excerpt/summary (optional)

**Respuestas**:

### Status 200
Article updated successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Artículo actualizado exitosamente`
- **data**: `object`
  - **id**: `string (uuid)`
  - **company_id**: `string (uuid)`
  - **author_id**: `string (uuid)`
  - **category_id**: `string (uuid)`
  - **title**: `string`
  - **excerpt**: `string`
  - **content**: `string`
  - **status**: `string`
  - **views_count**: `integer`
    - Preserved from original article
  - **published_at**: `string (date-time)`
    - Preserved if article is PUBLISHED
  - **created_at**: `string (date-time)`
  - **updated_at**: `string (date-time)`

### Status 401
Unauthorized - Missing or invalid JWT token

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden - User is not COMPANY_ADMIN of the article's company

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Insufficient permissions`

### Status 404
Not Found - Article does not exist or has been deleted

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Article not found`

### Status 422
Unprocessable Entity - Validation failed

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`
  - **title**: `array`
    - Ejemplo: `['The title has already been taken.']`
  - **category_id**: `array`
    - Ejemplo: `['The selected category_id is invalid.']`

### Status 500
Internal Server Error - Unexpected error occurred

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

### `DELETE` /api/help-center/articles/{id}

**Resumen**: Delete an article

**Descripción**: Permanently delete a help center article using soft delete. Only COMPANY_ADMIN can delete articles from their company. Articles must be in DRAFT status to be deleted. PUBLISHED articles cannot be deleted and will return 403 Forbidden. DELETE is idempotent - subsequent calls to a deleted article return 404. Deleted articles are soft-deleted and can be recovered from database if needed.

**Operation ID**: `delete_article`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Article unique identifier (UUID)

**Respuestas**:

### Status 200
Article deleted successfully (soft delete)

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Artículo eliminado permanentemente`

### Status 401
Unauthorized - Missing or invalid JWT token

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden - User is not COMPANY_ADMIN of the article's company, or article is PUBLISHED and cannot be deleted

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `No se puede eliminar un artículo publicado`
- **code**: `string`
  - Error code present only when trying to delete PUBLISHED article
  - Ejemplo: `CANNOT_DELETE_PUBLISHED_ARTICLE`

### Status 404
Not Found - Article does not exist or has already been deleted

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Article not found`

### Status 500
Internal Server Error - Unexpected error occurred

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

### `POST` /api/help-center/articles/{id}/publish

**Resumen**: Publish an article

**Descripción**: Publish a help center article from DRAFT to PUBLISHED status. Only COMPANY_ADMIN can publish articles from their company. Article must be in DRAFT status to publish. Sets published_at to current timestamp and fires ArticlePublished event. Published articles become visible to END_USERs who follow the company.

**Operation ID**: `publish_article`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Article unique identifier (UUID)

**Respuestas**:

### Status 200
Article published successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Artículo publicado exitosamente`
- **data**: `object`
  - **id**: `string (uuid)`
  - **company_id**: `string (uuid)`
  - **author_id**: `string (uuid)`
  - **category_id**: `string (uuid)`
  - **title**: `string`
  - **excerpt**: `string`
  - **content**: `string`
  - **status**: `string`
    - Ejemplo: `PUBLISHED`
  - **views_count**: `integer`
  - **published_at**: `string (date-time)`
    - Set to current timestamp
  - **created_at**: `string (date-time)`
  - **updated_at**: `string (date-time)`

### Status 400
Bad Request - Article is already in PUBLISHED status

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Article is already published`

### Status 401
Unauthorized - Missing or invalid JWT token

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden - User is not COMPANY_ADMIN of the article's company

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Insufficient permissions`

### Status 404
Not Found - Article does not exist or has been deleted

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Article not found`

### Status 500
Internal Server Error - Unexpected error occurred

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

### `POST` /api/help-center/articles/{id}/unpublish

**Resumen**: Unpublish an article

**Descripción**: Unpublish a help center article from PUBLISHED back to DRAFT status. Only COMPANY_ADMIN can unpublish articles from their company. Article must be in PUBLISHED status to unpublish. Sets published_at to null. Views count is preserved. Unpublished articles become invisible to END_USERs.

**Operation ID**: `unpublish_article`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Article unique identifier (UUID)

**Respuestas**:

### Status 200
Article unpublished successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Artículo despublicado y regresado a borrador`
- **data**: `object`
  - **id**: `string (uuid)`
  - **company_id**: `string (uuid)`
  - **author_id**: `string (uuid)`
  - **category_id**: `string (uuid)`
  - **title**: `string`
  - **excerpt**: `string`
  - **content**: `string`
  - **status**: `string`
    - Ejemplo: `DRAFT`
  - **views_count**: `integer`
    - Preserved from published article
  - **published_at**: `string (date-time)`
    - Set to null
  - **created_at**: `string (date-time)`
  - **updated_at**: `string (date-time)`

### Status 400
Bad Request - Article is in DRAFT status and cannot be unpublished

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Article is not published`

### Status 401
Unauthorized - Missing or invalid JWT token

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Unauthenticated.`

### Status 403
Forbidden - User is not COMPANY_ADMIN of the article's company

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Insufficient permissions`

### Status 404
Not Found - Article does not exist or has been deleted

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `Article not found`

### Status 500
Internal Server Error - Unexpected error occurred

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

## Help Center: Categories

### `GET` /api/help-center/categories

**Resumen**: List all help center categories

**Descripción**: Retrieve all available help center article categories. Returns the 4 global categories: ACCOUNT_PROFILE, SECURITY_PRIVACY, BILLING_PAYMENTS, and TECHNICAL_SUPPORT. These categories are used to organize and filter help center articles. All users (authenticated or not) can view categories to understand the available article organization structure.

**Operation ID**: `list_article_categories`

**Autenticación**:
No requiere autenticación

**Respuestas**:

### Status 200
Categories retrieved successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **data**: `array`
  Items:
    - **id**: `string (uuid)`
      - Category unique identifier
    - **code**: `string`
      - Unique category code used for filtering articles
    - **name**: `string`
      - Human-readable category name
    - **description**: `string`
      - Category description explaining the types of articles it contains
    - **created_at**: `string (date-time)`
    - **updated_at**: `string (date-time)`

### Status 500
Internal Server Error - Unexpected error occurred

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

## Incident Announcements

### `POST` /api/v1/announcements/incidents

**Resumen**: Create a new incident announcement

**Descripción**: Create a new incident announcement. Only COMPANY_ADMIN users can create incidents. Company ID is automatically inferred from JWT token. Incidents are created in DRAFT status by default, but can be immediately published or scheduled using the action parameter. Metadata includes urgency level, affected services, incident timestamps (started_at, ended_at), resolution tracking (is_resolved, resolved_at, resolution_content).

**Operation ID**: `create_incident_announcement`

**Autenticación**:
- bearerAuth

**Request Body**:
**Descripción**: Incident announcement data to create

**Content-Type**: `application/json`

- **title** (required): `string`
  - Incident title
- **content** (required): `string`
  - Incident description and details
- **urgency** (required): `string`
  - Urgency level of the incident
- **is_resolved** (required): `boolean`
  - Whether the incident is initially marked as resolved
- **started_at**: `string (date-time)`
  - When the incident started (ISO 8601). Defaults to current time if not provided
- **ended_at**: `string (date-time)`
  - When the incident ended (ISO 8601, optional)
- **resolved_at**: `string (date-time)`
  - When the incident was resolved (ISO 8601, optional)
- **resolution_content**: `string`
  - Details about how the incident was resolved (optional)
- **affected_services**: `array`
  - List of affected services (optional)
  - Ejemplo: `['API', 'Dashboard']`
- **action**: `string`
  - Action to perform: draft (default), publish (immediately), or schedule (for scheduled_for parameter)
  - Ejemplo: `draft`
- **scheduled_for**: `string (date-time)`
  - When to publish the incident (ISO 8601, required if action=schedule)

**Respuestas**:

### Status 201
Incident announcement created successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Incident created as draft`
- **data**: `object`
  - **id**: `string (uuid)`
  - **company_id**: `string (uuid)`
    - Set from JWT token
  - **author_id**: `string (uuid)`
    - Set to authenticated user ID
  - **type**: `string`
    - Ejemplo: `INCIDENT`
  - **title**: `string`
  - **content**: `string`
  - **status**: `string`
    - DRAFT by default, PUBLISHED if action=publish, SCHEDULED if action=schedule
  - **metadata**: `object`
    - **urgency**: `string`
    - **is_resolved**: `boolean`
    - **started_at**: `string (date-time)`
    - **ended_at**: `string (date-time)`
    - **resolved_at**: `string (date-time)`
    - **resolution_content**: `string`
    - **affected_services**: `array`
  - **created_at**: `string (date-time)`
  - **updated_at**: `string (date-time)`

### Status 401
Unauthorized - Missing JWT token or JWT invalid (cannot extract company ID)

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `User not authenticated or invalid JWT`

### Status 403
Forbidden - User has no assigned company in JWT token

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `User has no assigned company`

### Status 422
Unprocessable Entity - Validation failed

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`
  - Ejemplo: `{'title': ['The title field is required.'], 'urgency': ['The urgency field is required.']}`

### Status 500
Internal Server Error - Database or service error

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

### `POST` /api/v1/announcements/incidents/{id}/resolve

**Resumen**: Resolve an incident announcement

**Descripción**: Mark an incident announcement as resolved with resolution details and timestamps. Only COMPANY_ADMIN users can resolve incidents from their company. Can only be called once per incident - subsequent calls will return 400 Bad Request if already resolved. Updates the metadata with is_resolved=true, resolution_content, and resolved_at timestamp. Can optionally update the title and ended_at timestamp. Announcement type must be INCIDENT.

**Operation ID**: `resolve_incident_announcement`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - Incident announcement unique identifier (UUID)

**Request Body**:
**Descripción**: Incident resolution data

**Content-Type**: `application/json`

- **resolution_content** (required): `string`
  - Details about how the incident was resolved
- **resolved_at**: `string (date-time)`
  - When the incident was resolved (ISO 8601). Defaults to current time if not provided
- **ended_at**: `string (date-time)`
  - When the incident ended (ISO 8601, optional)
- **title**: `string`
  - Updated incident title (optional)

**Respuestas**:

### Status 200
Incident resolved successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Incident resolved successfully`
- **data**: `object`
  - **id**: `string (uuid)`
  - **company_id**: `string (uuid)`
  - **author_id**: `string (uuid)`
  - **type**: `string`
    - Ejemplo: `INCIDENT`
  - **title**: `string`
    - Updated title if provided
  - **content**: `string`
  - **status**: `string`
  - **metadata**: `object`
    - **urgency**: `string`
    - **is_resolved**: `boolean`
      - Set to true by resolve operation
      - Ejemplo: `True`
    - **started_at**: `string (date-time)`
    - **ended_at**: `string (date-time)`
      - Updated if provided in request
    - **resolved_at**: `string (date-time)`
      - Set by resolve operation (defaults to now if not provided)
    - **resolution_content**: `string`
      - Set from request body
    - **affected_services**: `array`
  - **created_at**: `string (date-time)`
  - **updated_at**: `string (date-time)`

### Status 400
Bad Request - Incident already resolved, not an incident type, or invalid data

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Incident is already resolved`

### Status 401
Unauthorized - Missing JWT token or JWT invalid

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden - User does not belong to the incident's company

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Insufficient permissions`

### Status 404
Not Found - Incident announcement does not exist

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Announcement not found`

### Status 422
Unprocessable Entity - Validation failed

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`
  - Ejemplo: `{'resolution_content': ['The resolution_content field is required.']}`

### Status 500
Internal Server Error - Database or service error

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `An unexpected error occurred`

---

## Maintenance Announcements

### `POST` /api/announcements/maintenance

**Resumen**: Create maintenance announcement

**Descripción**: Create a new maintenance announcement. Only COMPANY_ADMIN can create maintenance announcements. Company ID is automatically inferred from JWT token. Can be created in DRAFT status by default, published immediately, or scheduled for future publication. Metadata includes scheduled maintenance times (scheduled_start, scheduled_end), actual execution times (actual_start, actual_end), urgency level, and affected services.

**Operation ID**: `create_maintenance_announcement`

**Autenticación**:
- bearerAuth

**Request Body**:
**Descripción**: Maintenance announcement data to create

**Content-Type**: `application/json`

- **title** (required): `string`
  - Announcement title
  - Ejemplo: `Server Maintenance`
- **content** (required): `string`
  - Announcement content describing the maintenance
  - Ejemplo: `We will be performing scheduled maintenance on our servers...`
- **urgency** (required): `string`
  - Maintenance urgency level
  - Ejemplo: `HIGH`
- **scheduled_start** (required): `string (date-time)`
  - Scheduled maintenance start datetime (ISO 8601)
  - Ejemplo: `2025-11-05T10:00:00Z`
- **scheduled_end** (required): `string (date-time)`
  - Scheduled maintenance end datetime (ISO 8601, must be after scheduled_start)
  - Ejemplo: `2025-11-05T12:00:00Z`
- **is_emergency** (required): `boolean`
  - Whether this is an emergency maintenance
- **affected_services**: `array`
  - Array of affected service names (optional)
  - Ejemplo: `['API', 'Dashboard']`
- **action**: `string`
  - Action to perform: draft (default), publish (immediately), or schedule (for scheduled_for parameter)
  - Ejemplo: `draft`
- **scheduled_for**: `string (date-time)`
  - When to publish the announcement (ISO 8601, required if action=schedule)
  - Ejemplo: `2025-11-05T09:00:00Z`

**Respuestas**:

### Status 201
Maintenance announcement created successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Mantenimiento creado como borrador`
- **data**: `object`
  - Created announcement resource with full details

### Status 400
Bad Request - Validation failed (invalid dates or missing required fields)

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `The scheduled_end must be after scheduled_start.`

### Status 401
Unauthorized - Missing or invalid JWT token

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Usuario no autenticado o JWT inválido`

### Status 403
Forbidden - User has no company assigned in JWT

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Usuario no tiene compañía asignada`

---

### `POST` /api/announcements/maintenance/{announcement}/start

**Resumen**: Mark maintenance as started

**Descripción**: Record the actual start time of a maintenance window. Sets actual_start timestamp in metadata to current time. Can only be called once per maintenance announcement - subsequent calls return 400 if already started. User must be the COMPANY_ADMIN who owns the announcement and it must be a MAINTENANCE type announcement.

**Operation ID**: `mark_maintenance_start`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **announcement** (required) (path): `string`
  - Announcement unique identifier (UUID)

**Respuestas**:

### Status 200
Maintenance start recorded successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Maintenance start recorded`
- **data**: `object`
  - Updated announcement resource with actual_start in metadata

### Status 400
Bad Request - Not a maintenance type or already started

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Maintenance start already marked`

### Status 401
Unauthorized - Missing or invalid JWT token

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden - Announcement belongs to different company

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Insufficient permissions`

### Status 404
Not Found - Announcement does not exist

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Announcement not found`

---

### `POST` /api/announcements/maintenance/{announcement}/complete

**Resumen**: Mark maintenance as completed

**Descripción**: Records the actual end time of a maintenance window. Requires maintenance to have been started first (actual_start must be set). Validates that end time is after start time. User must be the COMPANY_ADMIN who owns the announcement. The actual_end is set to the current time automatically.

**Operation ID**: `mark_maintenance_complete`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **announcement** (required) (path): `string`
  - Announcement ID (UUID)

**Respuestas**:

### Status 200
Maintenance completion recorded successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message
  - Ejemplo: `Maintenance completed`
- **data**: `object`
  - Updated announcement resource with metadata containing actual_end timestamp

### Status 400
Bad request - multiple possible error scenarios

**Content-Type**: `application/json`

- **message**: `string`
  - Error message explaining what went wrong

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthorized or invalid JWT`

### Status 403
Forbidden (announcement belongs to different company)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

### Status 404
Announcement not found

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Announcement not found`

---

## News Announcements

### `POST` /api/announcements/news

**Resumen**: Create news announcement

**Descripción**: Create a new news announcement. Only COMPANY_ADMIN role can create news announcements. Company ID is automatically inferred from JWT token. News can be created as DRAFT (default), published immediately (action=publish), or scheduled for future publication (action=schedule). The request body field "body" is stored as "content" in the database. Metadata includes news_type (feature_release, policy_update, general_update), target_audience array (users, agents, admins), summary text, and optional call_to_action with text and https URL. If action=schedule, a PublishAnnouncementJob is dispatched with calculated delay.

**Operation ID**: `create_news_announcement`

**Autenticación**:
- bearerAuth

**Request Body**:
**Descripción**: News announcement creation data

**Content-Type**: `application/json`

- **title** (required): `string`
  - Announcement title (5-200 characters)
  - Ejemplo: `New Feature Release: Dark Mode`
- **body** (required): `string`
  - Announcement content/body text (minimum 10 characters, stored as "content" in database)
  - Ejemplo: `We are excited to announce the launch of dark mode across all our applications.`
- **metadata** (required): `object`
  - News-specific metadata object containing news type, target audience, summary, and optional call to action
  - **news_type**: `string`
    - Type of news announcement
    - Ejemplo: `feature_release`
  - **target_audience**: `array`
    - Array of target audiences (1-5 items from: users, agents, admins)
    - Ejemplo: `['users', 'agents']`
  - **summary**: `string`
    - Summary of the news (10-500 characters)
    - Ejemplo: `Dark mode is now available for all users`
  - **call_to_action**: `object`
    - Optional call to action with text and https URL
    - **text**: `string`
      - CTA button text (required if call_to_action is provided)
      - Ejemplo: `Read more`
    - **url**: `string (uri)`
      - CTA URL (must be valid HTTPS URL, required if call_to_action is provided)
      - Ejemplo: `https://example.com/feature`
- **action**: `string`
  - Action to perform: draft (default), publish (immediately), or schedule (requires scheduled_for)
  - Ejemplo: `publish`
- **scheduled_for**: `string (date-time)`
  - ISO8601 datetime for scheduling publication (required if action=schedule, must be at least 5 minutes in future, max 1 year)
  - Ejemplo: `2025-11-20T10:00:00Z`

**Respuestas**:

### Status 201
News announcement created successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Success indicator
  - Ejemplo: `True`
- **message**: `string`
  - Success message indicating the action performed
- **data**: `object`
  - Created announcement resource with full details including type=NEWS, status (DRAFT/PUBLISHED/SCHEDULED), metadata, and timestamps

### Status 400
Bad request - validation or logic errors

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `The title must be at least 5 characters.`

### Status 401
Unauthenticated (missing or invalid JWT token)

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Unauthenticated`

### Status 403
Forbidden - user lacks COMPANY_ADMIN role or valid company in JWT

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `Insufficient permissions`

### Status 422
Unprocessable Entity - validation errors in request data

**Content-Type**: `application/json`

- **message**: `string`
  - Validation error message
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`
  - Object with field names as keys and array of error messages as values
  - Ejemplo: `{'title': ['The title must be at least 5 characters.'], 'metadata.news_type': ['The metadata.news_type must be one of: feature_release, policy_update, general_update.']}`

### Status 500
Internal Server Error - unexpected server error

**Content-Type**: `application/json`

- **message**: `string`
  - Error message
  - Ejemplo: `An unexpected error occurred`

---

## Onboarding

### `POST` /api/auth/onboarding/completed

**Resumen**: Mark onboarding as completed

**Descripción**: Marks the onboarding process as completed for the authenticated user. Sets the onboarding_completed_at timestamp. If onboarding is already completed, returns success without modifications. Email verification is NOT a prerequisite for completing onboarding.

**Operation ID**: `d7efab01fa98353947198030b8eb5e4d`

**Autenticación**:
- bearerAuth

**Respuestas**:

### Status 200
Onboarding successfully marked as completed

**Content-Type**: `application/json`

- **success** (required): `boolean`
  - Ejemplo: `True`
- **message** (required): `string`
  - Ejemplo: `Onboarding completado exitosamente`
- **user** (required): `object`
  - **id** (required): `string (uuid)`
    - Ejemplo: `9d5e8e42-3f1c-4e8d-a8c4-5e3f1c4e8d9a`
  - **userCode** (required): `string`
    - Ejemplo: `USR-20251101-001`
  - **email** (required): `string (email)`
    - Ejemplo: `john.doe@example.com`
  - **emailVerified** (required): `boolean`
  - **onboardingCompleted** (required): `boolean`
    - Ejemplo: `True`
  - **status** (required): `string`
    - Ejemplo: `ACTIVE`
  - **displayName** (required): `string`
    - Ejemplo: `John Doe`
  - **avatarUrl**: `string (url)`
    - Ejemplo: `https://example.com/avatars/john-doe.jpg`
  - **theme**: `string`
    - Ejemplo: `light`
  - **language**: `string`
    - Ejemplo: `es`
  - **roleContexts**: `array`
    - Ejemplo: `[{'roleCode': 'USER', 'roleName': 'Cliente', 'dashboardPath': '/tickets', 'company': None}]`
    Items:
      - **roleCode** (required): `string`
        - Ejemplo: `USER`
      - **roleName** (required): `string`
        - Ejemplo: `Cliente`
      - **dashboardPath** (required): `string`
        - Ejemplo: `/tickets`
      - **company** (required): `object`
        - **id** (required): `string (uuid)`
          - Ejemplo: `8d4e7e31-2f0b-3d7c-b9c3-4e2f0b3d7c8d`
        - **companyCode** (required): `string`
          - Ejemplo: `COMP-20251101-001`
        - **name** (required): `string`
          - Ejemplo: `Acme Corporation`

### Status 401
User not authenticated - Missing or invalid bearer token

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `Unauthenticated.`

---

## Password Reset

### `POST` /api/auth/password-reset

**Resumen**: Request password reset

**Descripción**: Request a password reset email. Public endpoint (no authentication required). ALWAYS returns 200 success for security - does not reveal if email exists in system.

**Operation ID**: `67699b55b6b45dc8a8f476a6a079595b`

**Autenticación**:
No requiere autenticación

**Request Body**:
**Content-Type**: `application/json`

- **email** (required): `string (email)`
  - Email address to send password reset link
  - Ejemplo: `user@example.com`

**Respuestas**:

### Status 200
Reset requested successfully (always returns 200, even if email does not exist)

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Si el email existe en nuestro sistema, recibirás un enlace para resetear tu contraseña.`

### Status 422
Validation error (invalid email format)


---

### `POST` /api/auth/password-reset/confirm

**Resumen**: Confirm password reset

**Descripción**: Confirm password reset with new password and token/code. Public endpoint (no authentication required). Automatically logs in user and returns session tokens after successful reset.

**Operation ID**: `4f87e9e00e2080d94b31eb313af97f9c`

**Autenticación**:
No requiere autenticación

**Request Body**:
**Content-Type**: `application/json`

- **token**: `string`
  - Reset token (32 chars) - received via email. Use token OR code, not both.
  - Ejemplo: `abc123def456ghi789jkl012mno345pq`
- **code**: `string`
  - Reset code (6 digits) - alternative to token. Use token OR code, not both.
  - Ejemplo: `123456`
- **password** (required): `string (password)`
  - New password (minimum 8 characters)
  - Ejemplo: `NewSecurePass123!`
- **passwordConfirmation** (required): `string (password)`
  - Password confirmation (must match password)
  - Ejemplo: `NewSecurePass123!`

**Respuestas**:

### Status 200
Password reset successfully - user automatically logged in

**Content-Type**: `application/json`

- **accessToken**: `string`
  - Ejemplo: `eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...`
- **refreshToken**: `string`
  - Ejemplo: `def502004b4c8a1b3f7e...`
- **tokenType**: `string`
  - Ejemplo: `Bearer`
- **expiresIn**: `integer`
  - Ejemplo: `3600`
- **user**: `object`
  - **id**: `string (uuid)`
    - Ejemplo: `550e8400-e29b-41d4-a716-446655440000`
  - **email**: `string (email)`
    - Ejemplo: `user@example.com`
  - **userCode**: `string`
    - Ejemplo: `USER001`
- **sessionId**: `string (uuid)`
  - Ejemplo: `650e8400-e29b-41d4-a716-446655440001`
- **loginTimestamp**: `string (date-time)`
  - Ejemplo: `2025-11-01T15:30:00+00:00`

### Status 422
Validation error (invalid format, passwords do not match, etc.)


### Status 404
Invalid token/code - not found in database


### Status 401
Token expired or already used


---

### `GET` /api/auth/password-reset/status

**Resumen**: Get password reset status

**Descripción**: Validate password reset token and return its status. Public endpoint (no authentication required). Use this before showing the password reset form to verify the token is valid.

**Operation ID**: `ff2ace448696392eb45ebde2e5e43915`

**Autenticación**:
No requiere autenticación

**Parámetros**:
- **token** (required) (query): `string`
  - Password reset token (32 chars) received via email

**Respuestas**:

### Status 200
Token status retrieved successfully (always returns 200, check is_valid field)

**Content-Type**: `application/json`

- **is_valid**: `boolean`
  - Whether the token is valid and not expired
  - Ejemplo: `True`
- **can_reset**: `boolean`
  - Whether password can be reset (same as is_valid)
  - Ejemplo: `True`
- **email**: `string (email)`
  - Email associated with token (null if invalid)
  - Ejemplo: `user@example.com`
- **expires_at**: `string (date-time)`
  - Token expiration timestamp
  - Ejemplo: `2025-11-01T16:30:00+00:00`
- **attempts_remaining**: `integer`
  - Number of remaining reset attempts
  - Ejemplo: `3`

### Status 404
Token not found in database


### Status 410
Token expired (gone)


---

## Roles

### `GET` /api/roles

**Resumen**: Get all available roles

**Descripción**: Returns list of all available roles in the system. Only PLATFORM_ADMIN or COMPANY_ADMIN can view roles

**Operation ID**: `list_roles`

**Autenticación**:
- bearerAuth

**Respuestas**:

### Status 200
Roles retrieved successfully

**Content-Type**: `application/json`

- **data**: `array`
  Items:
    - **code**: `string`
      - Role code
      - Ejemplo: `COMPANY_ADMIN`
    - **name**: `string`
      - Role display name
      - Ejemplo: `Company Administrator`
    - **description**: `string`
      - Role description
      - Ejemplo: `Manages company settings and users`
    - **requiresCompany**: `boolean`
      - Whether this role requires a company context
      - Ejemplo: `True`
    - **defaultDashboard**: `string`
      - Default dashboard route for this role
      - Ejemplo: `/empresa/dashboard`
    - **isSystemRole**: `boolean`
      - Whether this is a system-protected role
      - Ejemplo: `True`

### Status 401
Unauthorized


### Status 403
Insufficient permissions


---

### `POST` /api/users/{userId}/roles

**Resumen**: Assign a role to a user

**Descripción**: Assign a role to a user with optional company context. Only PLATFORM_ADMIN or COMPANY_ADMIN can assign roles. Returns 200 if role was reactivated, 201 if newly assigned. Throttled: 100 requests/hour per authenticated user. Reactivates revoked roles if applicable.

**Operation ID**: `assign_role_to_user`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **userId** (required) (path): `string`
  - User UUID

**Request Body**:
**Descripción**: Role assignment data

**Content-Type**: `application/json`

- **roleCode** (required): `string`
  - Role code to assign
  - Ejemplo: `AGENT`
- **companyId**: `string (uuid)`
  - Company UUID. REQUIRED if roleCode is AGENT or COMPANY_ADMIN. MUST be null if roleCode is USER or PLATFORM_ADMIN.
  - Ejemplo: `8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f`

**Respuestas**:

### Status 201
Role assigned successfully (newly created)

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Rol asignado exitosamente`
- **data**: `object`
  - **id**: `string (uuid)`
    - Ejemplo: `7b5a4c3d-9e8f-1a2b-3c4d-5e6f7a8b9c0d`
  - **roleCode**: `string`
    - Ejemplo: `AGENT`
  - **roleName**: `string`
    - Ejemplo: `Agent`
  - **company**: `object`
    - **id**: `string (uuid)`
      - Ejemplo: `8c3d5e1f-2a4b-5c6d-7e8f-9a0b1c2d3e4f`
    - **name**: `string`
      - Ejemplo: `Acme Corporation`
    - **logoUrl**: `string`
      - Ejemplo: `https://example.com/logos/acme.png`
  - **isActive**: `boolean`
    - Ejemplo: `True`
  - **assignedAt**: `string (date-time)`
    - Ejemplo: `2025-11-01T14:30:00Z`
  - **assignedBy**: `object`
    - **id**: `string (uuid)`
      - Ejemplo: `6a4b3c2d-8e7f-9a0b-1c2d-3e4f5a6b7c8d`
    - **userCode**: `string`
      - Ejemplo: `USR-20250001`
    - **email**: `string`
      - Ejemplo: `admin@example.com`

### Status 200
Role reactivated successfully (was previously inactive)

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Rol reactivado exitosamente`
- **data**: `object`
  - **id**: `string (uuid)`
  - **roleCode**: `string`
  - **roleName**: `string`
  - **company**: `object`
  - **isActive**: `boolean`
  - **assignedAt**: `string (date-time)`
  - **assignedBy**: `object`

### Status 400
Validation failed


### Status 401
Unauthorized


### Status 403
Insufficient permissions


### Status 404
User not found


### Status 422
Validation error


---

### `DELETE` /api/users/roles/{roleId}

**Resumen**: Remove a role from a user

**Descripción**: Deactivate a role assignment (soft delete). Only PLATFORM_ADMIN or COMPANY_ADMIN can remove roles. COMPANY_ADMIN can only remove roles from their own company

**Operation ID**: `remove_role_from_user`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **roleId** (required) (path): `string`
  - UserRole UUID (not Role UUID) - the ID of the role assignment to remove
- **reason** (query): `string`
  - Optional reason for removal (max 500 characters)

**Respuestas**:

### Status 200
Role removed successfully

**Content-Type**: `application/json`

- **success**: `boolean`
- **message**: `string`

### Status 401
Unauthorized


### Status 403
Insufficient permissions


### Status 404
Role assignment not found


### Status 422
Validation error


---

## Sessions

### `GET` /api/auth/sessions

**Resumen**: List user sessions

**Descripción**: Get all active sessions for authenticated user. Returns all non-revoked refresh tokens ordered by last usage.

**Operation ID**: `9c723308a97b1e896f71b03ef5541c5a`

**Autenticación**:
- bearerAuth

**Respuestas**:

### Status 200
Sessions retrieved successfully

**Content-Type**: `application/json`

- **sessions**: `array`
  Items:
    - **sessionId**: `string (uuid)`
      - Session identifier
      - Ejemplo: `9d4e3c2a-8b7f-4e5d-9c8b-7a6f5e4d3c2b`
    - **deviceName**: `string`
      - Device name
      - Ejemplo: `Chrome on Windows`
    - **ipAddress**: `string`
      - IP address
      - Ejemplo: `192.168.1.1`
    - **userAgent**: `string`
      - User agent string
      - Ejemplo: `Mozilla/5.0...`
    - **lastUsedAt**: `string (date-time)`
      - Last usage timestamp (ISO 8601)
      - Ejemplo: `2025-11-01T12:00:00+00:00`
    - **expiresAt**: `string (date-time)`
      - Expiration timestamp (ISO 8601)
      - Ejemplo: `2025-11-08T12:00:00+00:00`
    - **isCurrent**: `boolean`
      - Whether this is the current session
      - Ejemplo: `True`
    - **location**: `string`
      - GeoIP location (not yet implemented)

### Status 401
Unauthenticated


---

### `POST` /api/auth/logout

**Resumen**: Logout user

**Descripción**: Logout from current session or all sessions. Revokes tokens, blacklists access token, and clears the refresh_token cookie.

**Operation ID**: `b471d4a100ddd7d6def4d28f7f51b1e1`

**Autenticación**:
- bearerAuth

**Request Body**:
**Content-Type**: `application/json`

- **everywhere**: `boolean`
  - Logout from all sessions

**Respuestas**:

### Status 200
Logout successful. Cookie refresh_token is cleared.

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Logged out successfully`

### Status 401
Unauthenticated


---

### `DELETE` /api/auth/sessions/{sessionId}

**Resumen**: Revoke a session

**Descripción**: Revoke a specific session from another device. Blacklists associated access tokens and revokes the refresh token. Cannot revoke the current session.

**Operation ID**: `f80106f8794ae1d6b079027b9d102edb`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **sessionId** (required) (path): `string`
  - UUID of the session to revoke

**Respuestas**:

### Status 200
Session revoked successfully

**Content-Type**: `application/json`

- **success**: `boolean`
  - Ejemplo: `True`
- **message**: `string`
  - Ejemplo: `Session revoked successfully`

### Status 401
Unauthenticated


### Status 403
Not authorized to revoke this session


### Status 404
Session not found or already revoked


### Status 409
Conflict: Cannot revoke the current session. Use logout endpoint instead.


---

## User Profile

### `GET` /api/users/me/profile

**Resumen**: Get authenticated user profile

**Descripción**: Retrieve the complete profile information of the currently authenticated user

**Operation ID**: `get_my_profile`

**Autenticación**:
- bearerAuth

**Respuestas**:

### Status 200
Profile retrieved successfully

**Content-Type**: `application/json`

- **data**: `object`
  - **firstName**: `string`
    - User first name
    - Ejemplo: `Juan`
  - **lastName**: `string`
    - User last name
    - Ejemplo: `Pérez`
  - **displayName**: `string`
    - Display name (firstName + lastName)
    - Ejemplo: `Juan Pérez`
  - **phoneNumber**: `string`
    - Phone number
    - Ejemplo: `+56912345678`
  - **avatarUrl**: `string`
    - Avatar image URL
    - Ejemplo: `https://example.com/avatars/user123.jpg`
  - **theme**: `string`
    - UI theme preference
    - Ejemplo: `light`
  - **language**: `string`
    - Language preference
    - Ejemplo: `es`
  - **timezone**: `string`
    - Timezone identifier
    - Ejemplo: `America/Santiago`
  - **pushWebNotifications**: `boolean`
    - Push web notifications enabled
    - Ejemplo: `True`
  - **notificationsTickets**: `boolean`
    - Ticket notifications enabled
    - Ejemplo: `True`
  - **createdAt**: `string (date-time)`
    - Profile creation timestamp
    - Ejemplo: `2025-01-15T10:30:00Z`
  - **updatedAt**: `string (date-time)`
    - Last update timestamp
    - Ejemplo: `2025-11-01T14:25:30Z`

### Status 401
Unauthorized


---

### `PATCH` /api/users/me/profile

**Resumen**: Update authenticated user profile

**Descripción**: Update profile information for the currently authenticated user. Throttled: 30 requests/hour. All fields are optional.

**Operation ID**: `update_my_profile`

**Autenticación**:
- bearerAuth

**Request Body**:
**Descripción**: Profile fields to update. All fields are optional.

**Content-Type**: `application/json`

- **firstName**: `string`
  - User first name
  - Ejemplo: `María`
- **lastName**: `string`
  - User last name
  - Ejemplo: `González`
- **phoneNumber**: `string`
  - Phone number (digits, spaces, +, -, (, ) allowed)
  - Ejemplo: `+56912345678`
- **avatarUrl**: `string (uri)`
  - Avatar image URL
  - Ejemplo: `https://example.com/avatars/maria.jpg`

**Respuestas**:

### Status 200
Profile updated successfully

**Content-Type**: `application/json`

- **data**: `object`
  - **userId**: `string (uuid)`
    - User ID
    - Ejemplo: `9d4e8c91-5f2a-4b3e-8a1f-2c3d4e5f6a7b`
  - **profile**: `object`
    - Updated profile data (ProfileResource with 12 fields)
    - **firstName**: `string`
      - Ejemplo: `María`
    - **lastName**: `string`
      - Ejemplo: `González`
    - **displayName**: `string`
      - Ejemplo: `María González`
    - **phoneNumber**: `string`
      - Ejemplo: `+56987654321`
    - **avatarUrl**: `string`
      - Ejemplo: `https://example.com/avatars/maria.jpg`
    - **theme**: `string`
      - Ejemplo: `light`
    - **language**: `string`
      - Ejemplo: `es`
    - **timezone**: `string`
      - Ejemplo: `America/Santiago`
    - **pushWebNotifications**: `boolean`
      - Ejemplo: `True`
    - **notificationsTickets**: `boolean`
      - Ejemplo: `True`
    - **createdAt**: `string (date-time)`
      - Ejemplo: `2025-01-15T10:30:00Z`
    - **updatedAt**: `string (date-time)`
      - Ejemplo: `2025-11-01T15:45:20Z`
  - **updatedAt**: `string (date-time)`
    - Last update timestamp
    - Ejemplo: `2025-11-01T15:45:20Z`

### Status 401
Unauthorized


### Status 422
Validation error

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`
  - Ejemplo: `{'firstName': ['First name must be at least 2 characters'], 'phoneNumber': ['Phone number format is invalid']}`

---

### `PATCH` /api/users/me/preferences

**Resumen**: Update authenticated user preferences

**Descripción**: Update preferences for the currently authenticated user. Throttled: 50 requests/hour. All fields are optional.

**Operation ID**: `update_my_preferences`

**Autenticación**:
- bearerAuth

**Request Body**:
**Descripción**: Preference fields to update. All fields are optional.

**Content-Type**: `application/json`

- **theme**: `string`
  - UI theme preference
  - Ejemplo: `dark`
- **language**: `string`
  - Language preference (ISO 639-1)
  - Ejemplo: `en`
- **timezone**: `string`
  - Timezone identifier (IANA timezone database)
  - Ejemplo: `America/New_York`
- **pushWebNotifications**: `boolean`
  - Enable push web notifications
- **notificationsTickets**: `boolean`
  - Enable ticket notifications
  - Ejemplo: `True`

**Respuestas**:

### Status 200
Preferences updated successfully

**Content-Type**: `application/json`

- **data**: `object`
  - **userId**: `string (uuid)`
    - User ID
    - Ejemplo: `9d4e8c91-5f2a-4b3e-8a1f-2c3d4e5f6a7b`
  - **preferences**: `object`
    - Updated preferences data (PreferencesResource with 6 fields)
    - **theme**: `string`
      - Ejemplo: `dark`
    - **language**: `string`
      - Ejemplo: `en`
    - **timezone**: `string`
      - Ejemplo: `America/New_York`
    - **pushWebNotifications**: `boolean`
    - **notificationsTickets**: `boolean`
      - Ejemplo: `True`
    - **updatedAt**: `string (date-time)`
      - Ejemplo: `2025-11-01T16:20:15Z`
  - **updatedAt**: `string (date-time)`
    - Last update timestamp
    - Ejemplo: `2025-11-01T16:20:15Z`

### Status 401
Unauthorized


### Status 422
Validation error

**Content-Type**: `application/json`

- **message**: `string`
  - Ejemplo: `The given data was invalid.`
- **errors**: `object`
  - Ejemplo: `{'theme': ['Theme must be either "light" or "dark"'], 'language': ['Language must be either "es" or "en"'], 'timezone': ['Invalid timezone']}`

---

## Users

### `GET` /api/users/me

**Resumen**: Get authenticated user information

**Descripción**: Returns complete user info with profile, roleContexts, and statistics

**Operation ID**: `get_current_user`

**Autenticación**:
- bearerAuth

**Respuestas**:

### Status 200
User information retrieved successfully

**Content-Type**: `application/json`

- **data**: `object`
  - **id**: `string (uuid)`
  - **userCode**: `string`
  - **email**: `string (email)`
  - **status**: `string`
    - Ejemplo: `ACTIVE`
  - **emailVerified**: `boolean`
    - Ejemplo: `True`
  - **authProvider**: `string`
    - Ejemplo: `google`
  - **profile**: `object`
    - ProfileResource with 12 fields
  - **roleContexts**: `array`
    - Array of role contexts
    Items:

  - **ticketsCount**: `integer`
    - Ejemplo: `42`
  - **resolvedTicketsCount**: `integer`
    - Ejemplo: `38`
  - **averageRating**: `number (float)`
    - Ejemplo: `4.5`
  - **lastLoginAt**: `string (date-time)`
  - **lastActivityAt**: `string (date-time)`
  - **createdAt**: `string (date-time)`
  - **updatedAt**: `string (date-time)`

### Status 401
Unauthorized


---

### `GET` /api/users

**Resumen**: List users with filters and pagination

**Descripción**: Returns paginated list of users with optional filters. PLATFORM_ADMIN sees all users, COMPANY_ADMIN sees only users from their company

**Operation ID**: `list_users`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **search** (query): `string`
  - Search by email, user_code, or profile name
- **status** (query): `string`
  - Filter by status
- **emailVerified** (query): `boolean`
  - Filter by email verification
- **role** (query): `string`
  - Filter by role
- **companyId** (query): `string`
  - Filter by company UUID
- **recentActivity** (query): `boolean`
  - Filter users active in last 7 days
- **createdAfter** (query): `string`
  - Filter users created after datetime
- **createdBefore** (query): `string`
  - Filter users created before datetime
- **order_by** (query): `string`
  - Order by field
- **order_direction** (query): `string`
  - Order direction
- **page** (query): `integer`
  - Page number
- **per_page** (query): `integer`
  - Items per page (max 50)

**Respuestas**:

### Status 200
Users list retrieved successfully

**Content-Type**: `application/json`

- **data**: `array`
  - Array of UserResource objects (15 fields each)
  Items:

- **meta**: `object`
  - **total**: `integer`
    - Ejemplo: `156`
  - **per_page**: `integer`
    - Ejemplo: `15`
  - **current_page**: `integer`
    - Ejemplo: `1`
  - **last_page**: `integer`
    - Ejemplo: `11`
- **links**: `object`
  - **first**: `string`
  - **last**: `string`
  - **prev**: `string`
  - **next**: `string`

### Status 401
Unauthorized


### Status 403
Insufficient permissions


---

### `GET` /api/users/{id}

**Resumen**: Get specific user by ID

**Descripción**: Returns complete user information. PLATFORM_ADMIN can view any user, COMPANY_ADMIN can view users from their company only, any user can view themselves

**Operation ID**: `show_user`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - User UUID

**Respuestas**:

### Status 200
User retrieved successfully

**Content-Type**: `application/json`

- **data**: `object`
  - UserResource with 15 fields

### Status 401
Unauthorized


### Status 403
Insufficient permissions


### Status 404
User not found


---

### `DELETE` /api/users/{id}

**Resumen**: Delete user (soft delete)

**Descripción**: Soft delete a user (sets status to deleted and deleted_at timestamp). Only PLATFORM_ADMIN can perform this action. User cannot delete themselves

**Operation ID**: `delete_user`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - User UUID
- **reason** (query): `string`
  - Optional deletion reason

**Respuestas**:

### Status 200
User deleted successfully

**Content-Type**: `application/json`

- **data**: `object`
  - **success**: `boolean`

### Status 401
Unauthorized


### Status 403
Only platform administrators can delete users


### Status 404
User not found


### Status 422
Cannot delete self


---

### `PUT` /api/users/{id}/status

**Resumen**: Update user status

**Descripción**: Suspend or activate a user. Only PLATFORM_ADMIN can perform this action

**Operation ID**: `update_user_status`

**Autenticación**:
- bearerAuth

**Parámetros**:
- **id** (required) (path): `string`
  - User UUID

**Request Body**:
**Content-Type**: `application/json`

- **status** (required): `string`
- **reason**: `string`
  - Required when status is suspended

**Respuestas**:

### Status 200
User status updated successfully

**Content-Type**: `application/json`

- **data**: `object`
  - **userId**: `string (uuid)`
  - **status**: `string`
  - **updatedAt**: `string (date-time)`

### Status 401
Unauthorized


### Status 403
Only platform administrators can update user status


### Status 404
User not found


### Status 422
Validation error


---

## Componentes de Seguridad

### bearerAuth

**Tipo**: http
**Esquema**: bearer
**Bearer Format**: JWT
**Descripción**: JWT Bearer Token. Include in Authorization header: Bearer <token>
