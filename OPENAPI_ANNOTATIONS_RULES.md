# OpenAPI Annotations Rules - Documentación Profesional

## Principios Generales

1. **Cada endpoint debe tener anotaciones OpenAPI completas** sobre el método público
2. **Sin redundancias**: El docblock y la anotación NO deben duplicar información
3. **Type hints siempre**: `public function method(): JsonResponse`
4. **Documentar TODO pero minimalista**: Resumen, descripción, parámetros, request, response, seguridad

## Estructura Base de Anotaciones

### 1. Endpoints GET (Lectura)

```php
/**
 * Descripción corta de qué hace.
 */
#[OA\Get(
    path: '/api/ruta/{param}',
    operationId: 'unique_operation_id', // lowercase_with_underscores
    summary: 'Corta descripción (máx 120 chars)',
    description: 'Descripción detallada de qué hace, cuándo usarlo, qué retorna',
    tags: ['Category'],
    parameters: [
        new OA\Parameter(
            name: 'param',
            in: 'path', // 'path', 'query', 'header', 'cookie'
            required: true,
            description: 'Descripción del parámetro',
            schema: new OA\Schema(type: 'string', format: 'uuid')
        ),
        new OA\Parameter(
            name: 'search',
            in: 'query',
            required: false,
            description: 'Filtrar por nombre',
            schema: new OA\Schema(type: 'string')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Descripción de respuesta exitosa',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'success', type: 'boolean'),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                ]
            )
        ),
        new OA\Response(response: 401, description: 'Sin autenticación'),
        new OA\Response(response: 403, description: 'Sin permisos'),
        new OA\Response(response: 404, description: 'Recurso no encontrado'),
    ],
    security: [['bearerAuth' => []]] // Si requiere autenticación
)]
public function method(Request $request): JsonResponse
```

### 2. Endpoints POST (Creación)

```php
/**
 * Descripción corta.
 */
#[OA\Post(
    path: '/api/ruta',
    operationId: 'create_resource',
    summary: 'Crear nuevo recurso',
    description: 'Crea un nuevo recurso con los datos proporcionados. Validación: ...',
    tags: ['Category'],
    requestBody: new OA\RequestBody(
        required: true,
        description: 'Datos para crear el recurso',
        content: new OA\JsonContent(
            type: 'object',
            required: ['field1', 'field2'],
            properties: [
                new OA\Property(property: 'field1', type: 'string', description: 'Descripción'),
                new OA\Property(property: 'field2', type: 'string', format: 'email', description: 'Email del usuario'),
                new OA\Property(property: 'field3', type: 'integer', description: 'Número opcional', nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Recurso creado exitosamente',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        ),
        new OA\Response(response: 422, description: 'Error de validación'),
        new OA\Response(response: 401, description: 'Sin autenticación'),
    ],
    security: [['bearerAuth' => []]]
)]
public function store(CreateRequest $request): JsonResponse
```

### 3. Endpoints PATCH (Actualización Parcial)

```php
#[OA\Patch(
    path: '/api/ruta/{id}',
    operationId: 'update_resource',
    summary: 'Actualizar recurso',
    description: 'Actualiza campos específicos del recurso. Todos los campos son opcionales.',
    tags: ['Category'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string', format: 'uuid')
        ),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'field1', type: 'string', nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Actualizado exitosamente'),
        new OA\Response(response: 404, description: 'No encontrado'),
        new OA\Response(response: 422, description: 'Error de validación'),
    ],
    security: [['bearerAuth' => []]]
)]
public function update(UpdateRequest $request, Resource $resource): JsonResponse
```

### 4. Endpoints DELETE

```php
#[OA\Delete(
    path: '/api/ruta/{id}',
    operationId: 'delete_resource',
    summary: 'Eliminar recurso',
    description: 'Elimina el recurso permanentemente',
    tags: ['Category'],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Eliminado exitosamente'),
        new OA\Response(response: 404, description: 'No encontrado'),
    ],
    security: [['bearerAuth' => []]]
)]
public function destroy(Resource $resource): JsonResponse
```

## Reglas Específicas para CompanyManagement

### CompanyController

#### minimal()
- **Path**: `/api/companies/minimal`
- **Method**: GET
- **Auth**: NO
- **Propósito**: Listar empresas para selectores/dropdowns (solo 4 campos)
- **Parameters**: search (query, optional), per_page (query, optional, int)
- **Response**: 200 - array of objects con {id, company_code, name, logo_url}

#### explore()
- **Path**: `/api/companies/explore`
- **Method**: GET
- **Auth**: YES - Bearer token
- **Propósito**: Explorar empresas con filtros y ordenamiento
- **Parameters**: search, country, followed_by_me (bool), sort_by, sort_direction, per_page (todos query, opcionales)
- **Response**: 200 - array de objetos extendidos, 401 sin auth

#### index()
- **Path**: `/api/companies`
- **Method**: GET
- **Auth**: YES - Bearer token
- **Propósito**: Listar todas las empresas (admin only, verificado en middleware)
- **Parameters**: search, status, sort_by, sort_direction, per_page (todos query, opcionales)
- **Response**: 200 - array completo, 401 sin auth, 403 sin permisos

#### show()
- **Path**: `/api/companies/{company}`
- **Method**: GET
- **Auth**: YES - Bearer token
- **Propósito**: Ver detalles completos de una empresa
- **Parameters**: company (path, UUID)
- **Response**: 200 - objeto completo, 404 no existe, 403 sin acceso

#### store()
- **Path**: `/api/companies`
- **Method**: POST
- **Auth**: YES - Bearer token (PLATFORM_ADMIN only)
- **Propósito**: Crear nueva empresa
- **RequestBody**: name, legal_name, support_email, phone, website, admin_user_id, contact_info (address, city, etc), config, branding
- **Response**: 201 - empresa creada, 422 validación, 401 sin auth, 403 sin permisos

#### update()
- **Path**: `/api/companies/{company}`
- **Method**: PATCH
- **Auth**: YES - Bearer token (PLATFORM_ADMIN o COMPANY_ADMIN owner)
- **Propósito**: Actualizar información de empresa
- **Parameters**: company (path, UUID)
- **RequestBody**: Todos los campos del create son opcionales
- **Response**: 200 - empresa actualizada, 404 no existe, 422 validación, 403 sin permisos

### CompanyFollowerController

#### followed()
- **Path**: `/api/companies/followed`
- **Method**: GET
- **Auth**: YES
- **Propósito**: Listar empresas que el usuario sigue
- **Parameters**: page, per_page (query, opcionales)
- **Response**: 200 - array de empresas

#### isFollowing()
- **Path**: `/api/companies/{company}/is-following`
- **Method**: GET
- **Auth**: YES
- **Propósito**: Verificar si usuario sigue una empresa
- **Parameters**: company (path, UUID)
- **Response**: 200 - {success: bool, data: {isFollowing: bool}}

#### follow()
- **Path**: `/api/companies/{company}/follow`
- **Method**: POST
- **Auth**: YES
- **Propósito**: Comenzar a seguir una empresa
- **Parameters**: company (path, UUID)
- **RequestBody**: none
- **Response**: 200 o 201 - {isFollowing: true, followersCount: int}, 404 no existe, 409 ya sigue

#### unfollow()
- **Path**: `/api/companies/{company}/unfollow`
- **Method**: DELETE
- **Auth**: YES
- **Propósito**: Dejar de seguir una empresa
- **Parameters**: company (path, UUID)
- **Response**: 200 - {isFollowing: false, followersCount: int}, 404 no existe, 409 no sigue

### CompanyRequestController

#### index()
- **Path**: `/api/company-requests`
- **Method**: GET
- **Auth**: YES (PLATFORM_ADMIN only)
- **Propósito**: Listar solicitudes de empresas
- **Parameters**: status, search, sort, order, page (query, opcionales)
- **Response**: 200 - array de solicitudes, 401, 403

#### store()
- **Path**: `/api/company-requests`
- **Method**: POST
- **Auth**: NO (público, throttled 3/hora)
- **Propósito**: Crear solicitud de empresa
- **RequestBody**: companyName, legalName, email, phone, website, contactInfo (address, city, etc)
- **Response**: 201 - solicitud creada, 422 validación, 429 rate limit

### CompanyRequestAdminController

#### approve()
- **Path**: `/api/company-requests/{companyRequest}/approve`
- **Method**: POST
- **Auth**: YES (PLATFORM_ADMIN only)
- **Propósito**: Aprobar solicitud y crear empresa
- **Parameters**: companyRequest (path, UUID)
- **RequestBody**: notes (opcional)
- **Response**: 200 - {id, status, company, adminUser}, 404 no existe, 409 ya procesada, 401, 403

#### reject()
- **Path**: `/api/company-requests/{companyRequest}/reject`
- **Method**: POST
- **Auth**: YES (PLATFORM_ADMIN only)
- **Propósito**: Rechazar solicitud
- **Parameters**: companyRequest (path, UUID)
- **RequestBody**: reason (requerido), notes (opcional)
- **Response**: 200 - {id, status, rejectedAt, reason}, 404 no existe, 409 ya procesada, 401, 403

## Errores a Evitar

❌ `new OA\Schema(type: 'string', default: 'value')` - NO usar default en Schema, solo en Parameter
❌ Olvidar `operationId` - Swagger lo necesita
❌ Usar tipos no estándar - Usar: string, integer, boolean, number, array, object
❌ Parámetros sin `in` - Especificar: path, query, header, cookie
❌ RequestBody sin `required: true/false`
❌ Responses sin descriptions
❌ Security array vacío - O incluirlo o NO incluirlo

## Patrones Correctos

✅ Usar `format: 'uuid'` para UUIDs
✅ Usar `format: 'email'` para emails
✅ Usar `nullable: true` si campo puede ser null
✅ Response 201 para POST (creación), 200 para PATCH
✅ Security: `[['bearerAuth' => []]]` si requiere auth
✅ Tags con CamelCase y singular: ['Company'], ['User'], ['Authentication']
