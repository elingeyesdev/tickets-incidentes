# OpenAPI Attributes - Reglas de Documentación SIN WARNINGS

> **CRÍTICO**: Este documento define el orden EXACTO de parámetros para TODAS las clases OpenAPI.
> El orden importa cuando usas argumentos nombrados en PHP 8+.

## 1. OA\Get, OA\Post, OA\Put, OA\Delete (OperationTrait)

**Orden EXACTO del constructor:**

```php
#[OA\Get(
    path: '/endpoint',                    // 1. path (string)
    operationId: 'operation_id',          // 2. operationId (string)
    description: 'Descripción...',        // 3. description (string)
    summary: 'Resumen...',                // 4. summary (string)
    // security: [...],                  // 5. security (array) - OMITIR si no aplica
    // servers: [...],                   // 6. servers (array) - OMITIR si no aplica
    // requestBody: new OA\RequestBody(...), // 7. requestBody - OMITIR si no aplica
    tags: ['TagName'],                    // 8. tags (string[])
    // parameters: [...],                // 9. parameters - OMITIR si no aplica
    responses: [                          // 10. responses (Response[]) - REQUERIDO
        new OA\Response(...),
    ],
    // callbacks: [...],                 // 11. callbacks - OMITIR si no aplica
    // externalDocs: new OA\ExternalDocumentation(...), // 12. externalDocs
    // deprecated: false,                // 13. deprecated - OMITIR si false
)]
```

**✅ CORRECTO:**
```php
#[OA\Get(
    path: '/users',
    operationId: 'list_users',
    description: 'List all users...',
    summary: 'Get users',
    tags: ['Users'],
    responses: [new OA\Response(response: 200, description: 'OK')]
)]
```

**❌ INCORRECTO:**
```php
#[OA\Get(
    path: '/users',
    tags: ['Users'],              // ← ORDEN INCORRECTO
    summary: 'Get users',
    operationId: 'list_users',    // ← ORDEN INCORRECTO
    responses: [...]
)]
```

---

## 2. OA\Response

**Orden EXACTO del constructor:**

```php
new OA\Response(
    ref: null,                    // 1. ref (string|object|null) - OPCIONAL
    response: 200,                // 2. response (int|string) - Código HTTP
    description: 'Success...',    // 3. description (string)
    headers: null,                // 4. headers (Header[]) - OMITIR si vacío
    content: new OA\JsonContent(...), // 5. content (MediaType|JsonContent|XmlContent)
    links: null,                  // 6. links (Link[]) - OMITIR si vacío
)
```

**✅ CORRECTO:**
```php
new OA\Response(
    response: 200,
    description: 'User found',
    content: new OA\JsonContent(properties: [...], type: 'object')
)
```

**❌ INCORRECTO:**
```php
new OA\Response(
    description: 'User found',    // ← ORDEN INCORRECTO
    response: 200,
    content: new OA\JsonContent(...)
)
```

---

## 3. OA\JsonContent (hereda de Schema)

**Orden EXACTO del constructor:**

```php
new OA\JsonContent(
    // ref: null,               // 1. ref - OMITIR si null
    // schema: null,            // 2. schema - OMITIR si null
    // title: null,             // 3. title - OMITIR si null
    // description: null,       // 4. description - OMITIR si null
    // maxProperties: null,     // 5. maxProperties - OMITIR si null
    // minProperties: null,     // 6. minProperties - OMITIR si null
    // required: null,          // 7. required - OMITIR si null
    properties: [               // 8. properties (Property[]) - ANTES de type
        new OA\Property(...),
    ],
    type: 'object',             // 9. type (string) - DESPUÉS de properties
    // format: null,            // 10. format - OMITIR si null
    // items: null,             // 11. items - OMITIR si null
    // ... más parámetros opcionales
)
```

**✅ CORRECTO:**
```php
new OA\JsonContent(
    properties: [
        new OA\Property(property: 'id', type: 'string'),
        new OA\Property(property: 'name', type: 'string'),
    ],
    type: 'object'
)
```

**❌ INCORRECTO:**
```php
new OA\JsonContent(
    type: 'object',             // ← ANTES de properties
    properties: [...]
)
```

---

## 4. OA\Property (hereda de Schema)

**Orden EXACTO del constructor:**

```php
new OA\Property(
    property: 'field_name',       // 1. property (string)
    // ref: null,                // 2. ref - OMITIR si null
    // schema: null,             // 3. schema - OMITIR si null
    // title: null,              // 4. title - OMITIR si null
    description: 'Field desc...', // 5. description
    // maxProperties: null,      // 6. maxProperties - OMITIR si null
    // minProperties: null,      // 7. minProperties - OMITIR si null
    // required: null,           // 8. required - OMITIR si null
    // properties: null,         // 9. properties - OMITIR si null
    type: 'string',              // 10. type
    // format: 'email',          // 11. format - OMITIR si null
    // items: null,              // 12. items - OMITIR si null
    // ... más parámetros
    example: 'john@example.com',  // ejemplo: position ~28
)
```

**✅ CORRECTO:**
```php
new OA\Property(
    property: 'email',
    type: 'string',
    description: 'User email address',
    example: 'user@example.com'
)
```

**❌ INCORRECTO:**
```php
new OA\Property(
    property: 'email',
    description: 'User email',    // ← type DEBE ir después de property
    type: 'string',
    example: 'user@example.com'
)
```

---

## 5. OA\RequestBody

**Orden EXACTO del constructor:**

```php
new OA\RequestBody(
    // description: null,       // 1. description - OMITIR si null
    // content: null,          // 2. content - OMITIR si null
    required: true,            // 3. required (bool)
    // x: null,                // 4. x - OMITIR si null
)
```

**✅ CORRECTO:**
```php
requestBody: new OA\RequestBody(
    description: 'User data',
    required: true,
    content: new OA\JsonContent(properties: [...], type: 'object')
)
```

---

## 6. OA\Parameter

**Orden EXACTO del constructor:**

```php
new OA\Parameter(
    name: 'param_name',           // 1. name (string)
    in: 'query',                  // 2. in ('query|path|header|cookie')
    description: 'Param desc...', // 3. description (string)
    required: false,              // 4. required (bool)
    schema: new OA\Schema(...),   // 5. schema
    // style: null,              // 6. style - OMITIR si null
    // explode: null,            // 7. explode - OMITIR si null
    // allowReserved: null,      // 8. allowReserved - OMITIR si null
    // example: null,            // 9. example - OMITIR si null
)
```

**✅ CORRECTO:**
```php
new OA\Parameter(
    name: 'page',
    in: 'query',
    description: 'Page number',
    required: false,
    schema: new OA\Schema(type: 'integer', default: 1)
)
```

---

## REGLA DE ORO

> **IMPORTANTE**: Cuando usas argumentos nombrados en PHP 8+:
>
> El orden DEBE coincidir EXACTAMENTE con el orden de parámetros en el `__construct()` de la clase.
>
> Si omites un parámetro opcional, puedes omitir los que vienen después PERO NO desordenarlos.

---

## Referencia Rápida para Controladores

### Template para Documentar un GET

```php
#[OA\Get(
    path: '/endpoint/{id}',
    operationId: 'get_resource',
    description: 'Retrieves a resource...',
    summary: 'Get resource',
    tags: ['ResourceTag'],
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            description: 'Resource ID',
            required: true,
            schema: new OA\Schema(type: 'string', format: 'uuid')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Resource found',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'name', type: 'string'),
                ],
                type: 'object'
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Not found',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string'),
                ],
                type: 'object'
            )
        ),
    ]
)]
public function show(string $id): JsonResponse
{
    // ...
}
```

### Template para Documentar un POST

```php
#[OA\Post(
    path: '/endpoint',
    operationId: 'create_resource',
    description: 'Creates a new resource...',
    summary: 'Create resource',
    tags: ['ResourceTag'],
    requestBody: new OA\RequestBody(
        description: 'Resource data',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', description: 'Resource name'),
                new OA\Property(property: 'email', type: 'string', description: 'Email'),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Resource created',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'string'),
                    new OA\Property(property: 'name', type: 'string'),
                ],
                type: 'object'
            )
        ),
    ]
)]
public function store(Request $request): JsonResponse
{
    // ...
}
```

---

## Validación

Después de documentar, ejecuta:

```bash
# Validar sintaxis PHP (no warnings)
php -l app/Features/YourFeature/Http/Controllers/YourController.php

# Validar GraphQL schema (si aplica)
php artisan lighthouse:validate-schema

# Ejecutar tests
php artisan test
```

Si aún hay warnings en el IDE, recuerda:
- ✅ Copiar el orden EXACTO de los constructores en `/vendor/zircote/swagger-php/src/Attributes/`
- ✅ Respetar la jerarquía: properties ANTES de type
- ✅ En OA\Get: operationId ANTES de summary
- ✅ En OA\Response: response ANTES de description

---

**Última actualización:** 2025-11-04
**Aplicado en:** AnnouncementSchemaController.php
