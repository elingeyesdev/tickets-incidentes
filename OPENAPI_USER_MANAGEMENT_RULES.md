# OpenAPI Annotations Rules - User Management Feature

Basado en OPENAPI_ANNOTATIONS_RULES.md pero específico para User Management.

## UserController - 5 métodos

### 1. me()
- **Path**: `/api/users/me`
- **Method**: GET
- **Auth**: YES - Bearer token (required)
- **Propósito**: Obtener información del usuario autenticado
- **Parameters**: NONE
- **Response**:
  - 200: Usuario completo con todos los datos (id, email, status, profile, roleContexts, etc)
  - 401: sin autenticación
- **operationId**: `get_current_user`
- **Tag**: `Users`
- **Security**: `[['bearerAuth' => []]]`

### 2. index()
- **Path**: `/api/users`
- **Method**: GET
- **Auth**: YES - Bearer token (admin required)
- **Propósito**: Listar usuarios con filtros y paginación
- **Parameters** (todos query, opcionales):
  - `search`: string - Buscar por email, user_code, displayName
  - `status`: enum (active, suspended) - Filtrar por estado
  - `role`: string - Filtrar por role_code (USER, AGENT, COMPANY_ADMIN, PLATFORM_ADMIN)
  - `company`: UUID - Filtrar usuarios de una empresa específica
  - `verificationStatus`: enum (verified, unverified) - Filtrar por verificación
  - `activityLevel`: enum (active7d, active30d, neverActive) - Filtrar por última actividad
  - `createdAfter`: datetime - Usuarios creados después de esta fecha
  - `createdBefore`: datetime - Usuarios creados antes de esta fecha
  - `sortBy`: enum (name, email, createdAt, lastLogin) - Campo para ordenar
  - `sortDirection`: enum (asc, desc) - Dirección del ordenamiento
  - `page`: integer - Número de página (default 1)
  - `perPage`: integer - Items por página (default 20, máx 50)
- **Response**:
  - 200: Array paginado de usuarios con metadata (total, currentPage, lastPage, perPage)
  - 401: sin autenticación
  - 403: sin permisos (requiere PLATFORM_ADMIN o COMPANY_ADMIN)
- **operationId**: `list_users`
- **Tag**: `Users`
- **Security**: `[['bearerAuth' => []]]`

### 3. show()
- **Path**: `/api/users/{id}`
- **Method**: GET
- **Auth**: YES - Bearer token
- **Propósito**: Ver detalles de un usuario específico
- **Parameters**:
  - `id`: UUID (path, required) - ID del usuario
- **Response**:
  - 200: Usuario completo con todos los datos
  - 401: sin autenticación
  - 403: sin permisos (user solo puede ver su propia info)
  - 404: usuario no encontrado
- **operationId**: `show_user`
- **Tag**: `Users`
- **Security**: `[['bearerAuth' => []]]`

### 4. updateStatus()
- **Path**: `/api/users/{id}/status`
- **Method**: PUT
- **Auth**: YES - Bearer token (PLATFORM_ADMIN only)
- **Propósito**: Cambiar estado de un usuario (activo/suspendido)
- **Parameters**:
  - `id`: UUID (path, required) - ID del usuario
- **RequestBody** (requerido):
  - `status`: enum (active, suspended) - Nuevo estado
  - `reason`: string - Razón del cambio (requerido si status=suspended, máx 500 chars)
- **Response**:
  - 200: Usuario actualizado con nuevo estado
  - 401: sin autenticación
  - 403: sin permisos (solo PLATFORM_ADMIN)
  - 404: usuario no encontrado
  - 422: validación fallida (ej: reason faltante)
- **operationId**: `update_user_status`
- **Tag**: `Users`
- **Security**: `[['bearerAuth' => []]]`

### 5. destroy()
- **Path**: `/api/users/{id}`
- **Method**: DELETE
- **Auth**: YES - Bearer token (PLATFORM_ADMIN only)
- **Propósito**: Eliminar (soft delete) un usuario
- **Parameters**:
  - `id`: UUID (path, required) - ID del usuario a eliminar
- **Response**:
  - 200: Usuario eliminado exitosamente (soft delete, no desaparece de BD)
  - 401: sin autenticación
  - 403: sin permisos (solo PLATFORM_ADMIN)
  - 404: usuario no encontrado
  - 409: no se puede eliminar usuario con ciertas condiciones
- **operationId**: `delete_user`
- **Tag**: `Users`
- **Security**: `[['bearerAuth' => []]]`

---

## ProfileController - 3 métodos

### 1. show()
- **Path**: `/api/users/me/profile`
- **Method**: GET
- **Auth**: YES - Bearer token
- **Propósito**: Obtener perfil personal del usuario autenticado
- **Parameters**: NONE
- **Response**:
  - 200: Perfil completo (first_name, last_name, phone_number, avatar_url, theme, language, timezone, etc)
  - 401: sin autenticación
- **operationId**: `get_my_profile`
- **Tag**: `User Profile`
- **Security**: `[['bearerAuth' => []]]`

### 2. update()
- **Path**: `/api/users/me/profile`
- **Method**: PATCH
- **Auth**: YES - Bearer token
- **Propósito**: Actualizar datos personales del perfil
- **Parameters**: NONE
- **RequestBody** (todos opcionales):
  - `firstName`: string - Nombre (máx 255)
  - `lastName`: string - Apellido (máx 255)
  - `phoneNumber`: string - Teléfono (máx 20)
  - `avatar`: file/URL - Avatar (opcional)
  - `timezone`: string - Zona horaria (ej: Europe/Madrid)
- **Response**:
  - 200: Perfil actualizado
  - 401: sin autenticación
  - 422: validación fallida
  - **Throttled**: 30 requests/hora
- **operationId**: `update_my_profile`
- **Tag**: `User Profile`
- **Security**: `[['bearerAuth' => []]]`

### 3. updatePreferences()
- **Path**: `/api/users/me/preferences`
- **Method**: PATCH
- **Auth**: YES - Bearer token
- **Propósito**: Actualizar preferencias del usuario (tema, idioma, notificaciones)
- **Parameters**: NONE
- **RequestBody** (todos opcionales):
  - `theme`: enum (light, dark) - Tema de interfaz
  - `language`: enum (en, es, fr, de) - Idioma preferido
  - `pushNotifications`: boolean - Habilitar notificaciones push
  - `emailNotifications`: boolean - Habilitar notificaciones por email
  - `weeklyDigest`: boolean - Recibir resumen semanal
- **Response**:
  - 200: Preferencias actualizadas
  - 401: sin autenticación
  - 422: validación fallida
  - **Throttled**: 50 requests/hora
- **operationId**: `update_my_preferences`
- **Tag**: `User Profile`
- **Security**: `[['bearerAuth' => []]]`

---

## RoleController - 3 métodos

### 1. index()
- **Path**: `/api/roles`
- **Method**: GET
- **Auth**: YES - Bearer token (admin required)
- **Propósito**: Listar todos los roles disponibles
- **Parameters**: NONE
- **Response**:
  - 200: Array de roles [{id, roleCode, name, description, requiresCompany}]
  - 401: sin autenticación
  - 403: sin permisos (PLATFORM_ADMIN o COMPANY_ADMIN)
- **operationId**: `list_roles`
- **Tag**: `Roles`
- **Security**: `[['bearerAuth' => []]]`

### 2. assign()
- **Path**: `/api/users/{userId}/roles`
- **Method**: POST
- **Auth**: YES - Bearer token (admin required)
- **Propósito**: Asignar rol a un usuario (reactivar si ya existía pero fue revocado)
- **Parameters**:
  - `userId`: UUID (path, required) - ID del usuario
- **RequestBody** (requerido):
  - `roleCode`: enum (PLATFORM_ADMIN, COMPANY_ADMIN, AGENT, USER) - Rol a asignar
  - `companyId`: UUID - Requerido si roleCode es COMPANY_ADMIN o AGENT
- **Response**:
  - 201: Rol asignado exitosamente
  - 400: Validación fallida (ej: companyId requerido para COMPANY_ADMIN)
  - 401: sin autenticación
  - 403: sin permisos (PLATFORM_ADMIN o COMPANY_ADMIN)
  - 404: usuario no encontrado
  - 422: validación fallida
  - **Throttled**: 100 requests/hora (por usuario autenticado)
- **operationId**: `assign_role_to_user`
- **Tag**: `Roles`
- **Security**: `[['bearerAuth' => []]]`

### 3. remove()
- **Path**: `/api/users/roles/{roleId}`
- **Method**: DELETE
- **Auth**: YES - Bearer token (admin required)
- **Propósito**: Revocar rol de un usuario (soft delete del rol)
- **Parameters**:
  - `roleId`: UUID (path, required) - ID de la asignación de rol
- **RequestBody** (opcional):
  - `reason`: string - Razón de revocación (máx 500 chars)
- **Response**:
  - 200: Rol revocado exitosamente
  - 401: sin autenticación
  - 403: sin permisos (PLATFORM_ADMIN o COMPANY_ADMIN)
  - 404: asignación de rol no encontrada
  - 422: validación fallida
- **operationId**: `remove_role_from_user`
- **Tag**: `Roles`
- **Security**: `[['bearerAuth' => []]]`

---

## Reglas Generales para User Management

### OpenAPI Patterns

1. **Type hints siempre**: `public function method(): JsonResponse`
2. **Security**: Todos los endpoints requieren `bearerAuth`
3. **Tags**:
   - `['Users']` para UserController
   - `['User Profile']` para ProfileController
   - `['Roles']` para RoleController

4. **Response structures**:
   ```json
   // Single user
   {
     "id": "uuid",
     "email": "user@example.com",
     "status": "active",
     "profile": { "firstName": "...", ... },
     "roleContexts": [{ "roleCode": "...", "companyId": "..." }, ...],
     ...
   }

   // List users
   {
     "data": [user, user, ...],
     "meta": {
       "total": 100,
       "currentPage": 1,
       "lastPage": 5,
       "perPage": 20
     },
     "links": {
       "first": "...",
       "last": "...",
       "prev": null,
       "next": "..."
     }
   }
   ```

5. **Error responses**:
   - 401: Unauthenticated (missing or invalid JWT)
   - 403: Forbidden (valid JWT but no permission)
   - 404: Not found (resource doesn't exist)
   - 409: Conflict (logical error, e.g., role already assigned)
   - 422: Validation error (invalid input data)
   - 429: Too many requests (rate limit exceeded)

6. **Docblocks**:
   - Minimalist (1-2 lines)
   - All documentation in OpenAPI attributes
   - No duplication with @OA attributes

7. **Parameters**:
   - All query parameters are optional unless specified
   - Use proper formats: `format: 'uuid'`, `format: 'email'`, `format: 'date-time'`
   - Enums: use `enum: ['value1', 'value2']`

8. **RequestBody**:
   - Always specify `required: true/false`
   - For PATCH: all fields are typically optional
   - For POST: some fields required, others optional
   - Type hints: `type: 'object'`, `type: 'array'`, `type: 'string'`, etc.

9. **Responses**:
   - Always include 401, 403 (if auth-required)
   - Always include 404 (if resource-specific)
   - Add 422 for endpoints with validation
   - Add 429 for throttled endpoints
   - Document with `description` and `content` (JsonContent)

10. **Consistent naming**:
    - operationId: lowercase_with_underscores
    - Tags: CamelCase, singular
    - Properties: camelCase in JSON but snake_case in DB

---

## Errors to Avoid

❌ Parámetros sin `in` especificado
❌ Olvidar operationId
❌ RequestBody sin `required: true/false`
❌ Response sin descriptions
❌ Type mismatches (Schema vs AdditionalProperties)
❌ Olvidar security arrays cuando auth es requerida
❌ No documentar rate limiting en description
❌ Inconsistencias entre path parameters y documentación
