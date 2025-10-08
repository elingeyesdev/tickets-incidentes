# 🚀 Sistema Profesional de Manejo de Errores GraphQL

**Versión**: 2.0
**Fecha**: 08-Oct-2025
**Estado**: ✅ Implementado y Probado

---

## 📋 Índice

1. [Descripción General](#descripción-general)
2. [Características](#características)
3. [Arquitectura](#arquitectura)
4. [Cómo Funciona](#cómo-funciona)
5. [Uso Básico para Features](#uso-básico-para-features)
6. [Crear Error Handlers Personalizados](#crear-error-handlers-personalizados)
7. [Crear Excepciones Personalizadas](#crear-excepciones-personalizadas)
8. [Códigos de Error](#códigos-de-error)
9. [Ejemplos Completos](#ejemplos-completos)
10. [Diferencias DEV vs PROD](#diferencias-dev-vs-prod)
11. [Testing](#testing)
12. [Troubleshooting](#troubleshooting)

---

## 🎯 Descripción General

Este sistema proporciona un manejo **profesional, reutilizable y altamente escalable** de errores para la API GraphQL. Implementa las mejores prácticas de la especificación oficial de GraphQL y consideraciones de seguridad de la industria.

### ✅ Problemas que Resuelve

- ❌ **Antes**: Errores técnicos expuestos en producción (riesgo de seguridad)
- ✅ **Ahora**: Mensajes genéricos en PROD, detallados en DEV

- ❌ **Antes**: Código duplicado en múltiples handlers
- ✅ **Ahora**: Arquitectura DRY con `BaseErrorHandler`

- ❌ **Antes**: Sin diferenciación entre entornos
- ✅ **Ahora**: Automático basado en `APP_ENV`

- ❌ **Antes**: Difícil agregar nuevos error handlers
- ✅ **Ahora**: Extender `BaseErrorHandler` (10 líneas de código)

---

## ⚡ Características

### 🔐 Seguridad

- ✅ Oculta `locations` y `path` en producción
- ✅ Oculta `stacktrace` en producción
- ✅ Mensajes genéricos user-friendly en PROD
- ✅ Logging automático de errores en producción

### 🧑‍💻 Debugging

- ✅ `stacktrace` completo en desarrollo
- ✅ `timestamp`, `environment`, `service` en DEV
- ✅ Mensajes detallados con contexto técnico
- ✅ `locations` y `path` visibles para depuración

### 🏗️ Escalabilidad

- ✅ Arquitectura basada en clases abstractas
- ✅ Sistema de códigos centralizados (`ErrorCodeRegistry`)
- ✅ Fácil agregar nuevos handlers (herencia)
- ✅ Funciona automáticamente con cualquier feature

### 📊 Profesional

- ✅ Basado en GraphQL Spec oficial
- ✅ Sigue Apollo Server Best Practices
- ✅ Códigos de error consistentes para clientes
- ✅ Estructura `extensions` estándar

---

## 🏛️ Arquitectura

```
app/Shared/GraphQL/Errors/
├── BaseErrorHandler.php                 ← Clase abstracta base (reutilizable)
├── EnvironmentErrorFormatter.php        ← Formateo por entorno (DEV/PROD)
├── ErrorCodeRegistry.php                ← Códigos centralizados
└── Handlers/
    ├── CustomValidationErrorHandler.php
    ├── CustomAuthenticationErrorHandler.php
    └── CustomAuthorizationErrorHandler.php
```

### Componentes Clave

#### 1. **BaseErrorHandler** (Clase Abstracta)

Proporciona funcionalidad común para todos los handlers.

**Métodos que debes implementar:**
- `shouldHandle(Throwable $exception): bool` - ¿Este handler maneja esta excepción?
- `formatError(array $result, Throwable $exception): array` - Formateo específico
- `getErrorCode(Throwable $exception): string` - Código del error

**Métodos opcionales:**
- `getDevelopmentMessage()` - Mensaje para desarrollo
- `getProductionMessage()` - Mensaje para producción
- `getServiceName()` - Nombre del servicio (metadata)

#### 2. **EnvironmentErrorFormatter** (Helper)

Formatea errores según `APP_ENV`:
- `isProduction()` - ¿Estamos en producción?
- `format()` - Formatea error según entorno
- `toFieldErrors()` - Convierte validation errors a fieldErrors
- `logError()` - Log de errores

#### 3. **ErrorCodeRegistry** (Constantes)

Códigos de error centralizados para consistencia:
- `UNAUTHENTICATED`, `FORBIDDEN`, `VALIDATION_ERROR`, etc.
- `getDescription()` - Descripción del código
- `getCategory()` - Categoría (authentication, validation, etc.)
- `getSuggestedHttpStatus()` - Status HTTP sugerido

---

## 🔄 Cómo Funciona

### Flujo de Ejecución

```
1. GraphQL lanza excepción
   ↓
2. Lighthouse captura el error
   ↓
3. BaseErrorHandler.__invoke()
   ├─→ shouldHandle() - ¿Manejar este error?
   ├─→ formatError() - Formateo específico
   ├─→ getErrorCode() - Obtener código
   ├─→ EnvironmentErrorFormatter.format() - Aplicar DEV/PROD
   └─→ logError() - Log si es PROD
   ↓
4. Respuesta JSON al cliente
```

### Ejemplo de Transformación

**Excepción lanzada:**
```php
throw new ValidationException([
    'input.email' => ['The email has already been taken.']
]);
```

**Respuesta DEV:**
```json
{
  "errors": [{
    "message": "Validation error",
    "locations": [{"line": 2, "column": 3}],
    "path": ["register"],
    "extensions": {
      "code": "VALIDATION_ERROR",
      "category": "validation",
      "validation": {
        "email": ["The email has already been taken."]
      },
      "timestamp": "2025-10-08T14:30:00Z",
      "environment": "development"
    }
  }]
}
```

**Respuesta PROD:**
```json
{
  "errors": [{
    "message": "Los datos proporcionados no son válidos.",
    "extensions": {
      "code": "VALIDATION_ERROR",
      "category": "validation",
      "fieldErrors": [
        {"field": "email", "message": "The email has already been taken."}
      ],
      "timestamp": "2025-10-08T14:30:00Z"
    }
  }]
}
```

---

## 🚀 Uso Básico para Features

### Paso 1: Crear Excepción Personalizada

Crea tu excepción en tu feature:

```php
// app/Features/CompanyManagement/Exceptions/CompanyNotFoundException.php

namespace App\Features\CompanyManagement\Exceptions;

use Exception;

class CompanyNotFoundException extends Exception
{
    public function __construct(string $companyId)
    {
        parent::__construct("Company with ID {$companyId} not found.");
    }
}
```

### Paso 2: Lanzar la Excepción

Úsala en tu Service:

```php
// app/Features/CompanyManagement/Services/CompanyService.php

public function getCompanyById(string $id): Company
{
    $company = Company::find($id);

    if ($company === null) {
        throw new CompanyNotFoundException($id);
    }

    return $company;
}
```

### Paso 3: Crear Error Handler (Opcional)

Si necesitas formateo especial, crea un handler:

```php
// app/Features/CompanyManagement/GraphQL/Errors/CompanyNotFoundErrorHandler.php

namespace App\Features\CompanyManagement\GraphQL\Errors;

use App\Shared\GraphQL\Errors\BaseErrorHandler;
use App\Shared\GraphQL\Errors\ErrorCodeRegistry;
use App\Features\CompanyManagement\Exceptions\CompanyNotFoundException;

class CompanyNotFoundErrorHandler extends BaseErrorHandler
{
    protected function shouldHandle(\Throwable $exception): bool
    {
        return $exception instanceof CompanyNotFoundException;
    }

    protected function formatError(array $result, \Throwable $exception): array
    {
        // No necesita formateo adicional
        return $result;
    }

    protected function getErrorCode(\Throwable $exception): string
    {
        return ErrorCodeRegistry::COMPANY_NOT_FOUND;
    }

    protected function getDevelopmentMessage(\Throwable $exception): string
    {
        return $exception->getMessage(); // "Company with ID X not found."
    }

    protected function getProductionMessage(\Throwable $exception): string
    {
        return 'La empresa solicitada no existe.';
    }
}
```

### Paso 4: Registrar el Handler

Agrega tu handler a `config/lighthouse.php`:

```php
'error_handlers' => [
    \App\Shared\GraphQL\Errors\CustomValidationErrorHandler::class,
    \App\Shared\GraphQL\Errors\CustomAuthenticationErrorHandler::class,
    \App\Shared\GraphQL\Errors\CustomAuthorizationErrorHandler::class,

    // Tu nuevo handler
    \App\Features\CompanyManagement\GraphQL\Errors\CompanyNotFoundErrorHandler::class,
],
```

### Paso 5: ¡Listo!

El sistema automáticamente:
- ✅ Detecta la excepción
- ✅ Aplica tu handler
- ✅ Formatea según DEV/PROD
- ✅ Agrega código y categoría
- ✅ Registra logs en PROD

---

## 🎨 Crear Error Handlers Personalizados

### Template Mínimo

```php
namespace App\Features\[Feature]\GraphQL\Errors;

use App\Shared\GraphQL\Errors\BaseErrorHandler;
use App\Shared\GraphQL\Errors\ErrorCodeRegistry;

class CustomFeatureErrorHandler extends BaseErrorHandler
{
    // 1. ¿Qué excepciones maneja?
    protected function shouldHandle(\Throwable $exception): bool
    {
        return $exception instanceof YourCustomException;
    }

    // 2. Formateo específico (opcional)
    protected function formatError(array $result, \Throwable $exception): array
    {
        // Agregar datos adicionales si necesitas
        // $result['extensions']['customField'] = 'value';
        return $result;
    }

    // 3. Código de error
    protected function getErrorCode(\Throwable $exception): string
    {
        return ErrorCodeRegistry::YOUR_CODE;
    }

    // 4. Mensaje para DESARROLLO (opcional)
    protected function getDevelopmentMessage(\Throwable $exception): string
    {
        return $exception->getMessage();
    }

    // 5. Mensaje para PRODUCCIÓN (opcional)
    protected function getProductionMessage(\Throwable $exception): string
    {
        return 'Mensaje user-friendly para el cliente.';
    }
}
```

### Ejemplo: Handler con Múltiples Excepciones

```php
class TicketErrorHandler extends BaseErrorHandler
{
    protected function shouldHandle(\Throwable $exception): bool
    {
        return $exception instanceof TicketNotFoundException
            || $exception instanceof TicketAlreadyClosedException
            || $exception instanceof TicketInvalidStateException;
    }

    protected function formatError(array $result, \Throwable $exception): array
    {
        // Agregar ID del ticket si está disponible
        if (method_exists($exception, 'getTicketId')) {
            $result['extensions']['ticketId'] = $exception->getTicketId();
        }

        return $result;
    }

    protected function getErrorCode(\Throwable $exception): string
    {
        return match (get_class($exception)) {
            TicketNotFoundException::class => ErrorCodeRegistry::TICKET_NOT_FOUND,
            TicketAlreadyClosedException::class => ErrorCodeRegistry::INVALID_STATE,
            TicketInvalidStateException::class => ErrorCodeRegistry::INVALID_STATE,
            default => ErrorCodeRegistry::INTERNAL_SERVER_ERROR,
        };
    }

    protected function getDevelopmentMessage(\Throwable $exception): string
    {
        return $exception->getMessage();
    }

    protected function getProductionMessage(\Throwable $exception): string
    {
        return match (get_class($exception)) {
            TicketNotFoundException::class => 'El ticket solicitado no existe.',
            TicketAlreadyClosedException::class => 'Este ticket ya está cerrado.',
            TicketInvalidStateException::class => 'No se puede realizar esta acción en el estado actual del ticket.',
            default => 'Ocurrió un error al procesar el ticket.',
        };
    }
}
```

---

## 🧩 Crear Excepciones Personalizadas

### Exception Básica

```php
namespace App\Features\[Feature]\Exceptions;

use Exception;

class ResourceNotFoundException extends Exception
{
    public function __construct(string $resourceType, string $id)
    {
        parent::__construct(
            "{$resourceType} with ID {$id} not found."
        );
    }
}
```

### Exception con Metadata

```php
namespace App\Features\Ticketing\Exceptions;

use Exception;

class TicketInvalidStateException extends Exception
{
    private string $ticketId;
    private string $currentState;
    private string $requiredState;

    public function __construct(
        string $ticketId,
        string $currentState,
        string $requiredState
    ) {
        $this->ticketId = $ticketId;
        $this->currentState = $currentState;
        $this->requiredState = $requiredState;

        parent::__construct(
            "Ticket {$ticketId} is in state '{$currentState}' but requires '{$requiredState}'."
        );
    }

    public function getTicketId(): string
    {
        return $this->ticketId;
    }

    public function getCurrentState(): string
    {
        return $this->currentState;
    }

    public function getRequiredState(): string
    {
        return $this->requiredState;
    }
}
```

---

## 📟 Códigos de Error

### Códigos Disponibles

Ver `ErrorCodeRegistry::class` para la lista completa:

**Authentication (401):**
- `UNAUTHENTICATED`
- `TOKEN_EXPIRED`
- `INVALID_TOKEN`
- `INVALID_CREDENTIALS`
- `EMAIL_NOT_VERIFIED`
- `INVALID_REFRESH_TOKEN`

**Authorization (403):**
- `FORBIDDEN`
- `INSUFFICIENT_ROLE`
- `WRONG_COMPANY`
- `ACTION_NOT_ALLOWED`

**Validation (400):**
- `VALIDATION_ERROR`
- `REQUIRED_FIELD`
- `INVALID_FORMAT`
- `OUT_OF_RANGE`

**Business Logic (409):**
- `RESOURCE_ALREADY_EXISTS`
- `DUPLICATE_EMAIL`
- `DUPLICATE_CODE`
- `CONFLICT`
- `INVALID_STATE`

**Not Found (404):**
- `NOT_FOUND`
- `USER_NOT_FOUND`
- `COMPANY_NOT_FOUND`
- `TICKET_NOT_FOUND`

**Rate Limiting (429):**
- `RATE_LIMIT_EXCEEDED`
- `TOO_MANY_LOGIN_ATTEMPTS`

### Agregar Códigos Nuevos

Edita `ErrorCodeRegistry.php`:

```php
// Agregar constante
public const YOUR_NEW_CODE = 'YOUR_NEW_CODE';

// Agregar descripción
public static function getDescription(string $code): string
{
    return match ($code) {
        // ...
        self::YOUR_NEW_CODE => 'Description of your code',
        default => 'Unknown error',
    };
}

// Agregar categoría
public static function getCategory(string $code): string
{
    return match ($code) {
        // ...
        self::YOUR_NEW_CODE => 'your_category',
        default => 'unknown',
    };
}
```

---

## 📊 Ejemplos Completos

### Ejemplo 1: Feature Completo (Ticketing)

**1. Crear Excepciones:**

```php
// app/Features/Ticketing/Exceptions/TicketNotFoundException.php
namespace App\Features\Ticketing\Exceptions;

use Exception;

class TicketNotFoundException extends Exception
{
    public function __construct(string $ticketId)
    {
        parent::__construct("Ticket with ID {$ticketId} not found.");
    }
}
```

**2. Usar en Service:**

```php
// app/Features/Ticketing/Services/TicketService.php
public function closeTicket(string $ticketId): Ticket
{
    $ticket = Ticket::find($ticketId);

    if (!$ticket) {
        throw new TicketNotFoundException($ticketId);
    }

    // ... lógica de cierre

    return $ticket;
}
```

**3. Crear Handler:**

```php
// app/Features/Ticketing/GraphQL/Errors/TicketErrorHandler.php
class TicketErrorHandler extends BaseErrorHandler
{
    protected function shouldHandle(\Throwable $exception): bool
    {
        return $exception instanceof TicketNotFoundException;
    }

    protected function formatError(array $result, \Throwable $exception): array
    {
        return $result;
    }

    protected function getErrorCode(\Throwable $exception): string
    {
        return ErrorCodeRegistry::TICKET_NOT_FOUND;
    }

    protected function getProductionMessage(\Throwable $exception): string
    {
        return 'El ticket solicitado no existe.';
    }
}
```

**4. Registrar Handler:**

```php
// config/lighthouse.php
'error_handlers' => [
    // ... otros handlers
    \App\Features\Ticketing\GraphQL\Errors\TicketErrorHandler::class,
],
```

**5. Respuestas Automáticas:**

**DEV:**
```json
{
  "errors": [{
    "message": "Ticket with ID abc123 not found.",
    "locations": [{"line": 5, "column": 3}],
    "path": ["closeTicket"],
    "extensions": {
      "code": "TICKET_NOT_FOUND",
      "category": "not_found",
      "timestamp": "2025-10-08T15:00:00Z",
      "environment": "development",
      "stacktrace": [...]
    }
  }]
}
```

**PROD:**
```json
{
  "errors": [{
    "message": "El ticket solicitado no existe.",
    "extensions": {
      "code": "TICKET_NOT_FOUND",
      "category": "not_found",
      "timestamp": "2025-10-08T15:00:00Z"
    }
  }]
}
```

---

## 🔀 Diferencias DEV vs PROD

### Tabla Comparativa

| Campo | DEV (APP_ENV=development) | PROD (APP_ENV=production) |
|-------|---------------------------|---------------------------|
| **message** | Técnico/detallado | Genérico/user-friendly |
| **locations** | ✅ Visible | ❌ Oculto |
| **path** | ✅ Visible | ❌ Oculto |
| **extensions.code** | ✅ Visible | ✅ Visible |
| **extensions.category** | ✅ Visible | ✅ Visible |
| **extensions.timestamp** | ✅ Visible | ✅ Visible |
| **extensions.environment** | ✅ Visible | ❌ Oculto |
| **extensions.stacktrace** | ✅ Visible | ❌ Oculto |
| **extensions.service** | ✅ Visible (si configurado) | ❌ Oculto |
| **extensions.validation** | ✅ Map detallado | ❌ Oculto |
| **extensions.fieldErrors** | ❌ No se usa | ✅ Array user-friendly |

### Cambiar Entorno

```bash
# .env para DESARROLLO
APP_ENV=development
APP_DEBUG=true

# .env para PRODUCCIÓN
APP_ENV=production
APP_DEBUG=false

# Limpiar cache después de cambiar
php artisan config:clear
```

---

## 🧪 Testing

### Test Manual con GraphiQL

1. **Iniciar servidor:**
```bash
docker compose up
```

2. **Abrir GraphiQL:**
```
http://localhost:8000/graphiql
```

3. **Probar error de validación:**
```graphql
mutation {
  register(input: {
    email: "test@example.com"  # Email duplicado
    password: "test123"
    passwordConfirmation: "test123"
    firstName: "Test"
    lastName: "User"
    acceptsTerms: true
    acceptsPrivacyPolicy: true
  }) {
    accessToken
  }
}
```

4. **Verificar respuesta:**
- DEV: Debe tener `locations`, `path`, `stacktrace`
- PROD: NO debe tener `locations`, `path`, `stacktrace`

### Test Automatizado

```php
// tests/Feature/GraphQL/ErrorHandlingTest.php

use Tests\TestCase;

class ErrorHandlingTest extends TestCase
{
    public function test_validation_error_hides_sensitive_data_in_production()
    {
        config(['app.env' => 'production']);

        $response = $this->graphQL('
            mutation {
                register(input: {
                    email: "invalid-email"
                    password: "123"
                }) {
                    accessToken
                }
            }
        ');

        $response->assertGraphQLErrorCategory('validation');

        // No debe exponer locations/path en PROD
        $errors = $response->json('errors.0');
        $this->assertArrayNotHasKey('locations', $errors);
        $this->assertArrayNotHasKey('path', $errors);

        // Debe tener fieldErrors en PROD
        $this->assertArrayHasKey('fieldErrors', $errors['extensions']);
    }

    public function test_validation_error_shows_debug_info_in_development()
    {
        config(['app.env' => 'development']);

        $response = $this->graphQL('...');

        // Debe tener locations/path en DEV
        $errors = $response->json('errors.0');
        $this->assertArrayHasKey('locations', $errors);
        $this->assertArrayHasKey('path', $errors);
        $this->assertArrayHasKey('environment', $errors['extensions']);
    }
}
```

---

## 🔧 Troubleshooting

### Problema: Los cambios no se aplican

**Solución:**
```bash
# Limpiar caches
php artisan config:clear
php artisan cache:clear

# Reiniciar servidor
docker compose restart app
```

### Problema: El handler no se ejecuta

**Verificar:**
1. ¿Está registrado en `config/lighthouse.php`?
2. ¿`shouldHandle()` retorna `true`?
3. ¿La excepción se está lanzando correctamente?

**Debug:**
```php
protected function shouldHandle(\Throwable $exception): bool
{
    \Log::info('Checking exception: ' . get_class($exception));
    return $exception instanceof YourException;
}
```

### Problema: No diferencia DEV y PROD

**Verificar `.env`:**
```bash
# Debe ser exactamente:
APP_ENV=production  # o development
APP_DEBUG=false     # false para prod, true para dev

# NO usar:
APP_ENV=prod        # ❌ Incorrecto
APP_ENV="production" # ❌ Comillas no necesarias
```

**Limpiar config:**
```bash
php artisan config:clear
```

### Problema: Stacktrace no aparece en DEV

**Verificar:**
```php
// En tu handler, asegúrate de pasar la excepción:
protected function formatError(array $result, \Throwable $exception): array
{
    // BaseErrorHandler ya pasa $exception automáticamente
    return $result;
}
```

**Si aún no funciona:**
```php
// Verificar en EnvironmentErrorFormatter::formatForDevelopment()
// que $options['exception'] esté siendo recibido
```

---

## 📚 Referencias

### Documentación Oficial

- [GraphQL Spec - Errors](https://spec.graphql.org/June2018/#sec-Errors)
- [Apollo Server - Error Handling](https://www.apollographql.com/docs/apollo-server/data/errors/)
- [Lighthouse PHP - Error Handling](https://lighthouse-php.com/master/api-reference/error-handling.html)

### Archivos del Sistema

- `app/Shared/GraphQL/Errors/BaseErrorHandler.php`
- `app/Shared/GraphQL/Errors/EnvironmentErrorFormatter.php`
- `app/Shared/GraphQL/Errors/ErrorCodeRegistry.php`
- `config/lighthouse.php`

### Ejemplos en el Proyecto

- `app/Shared/GraphQL/Errors/CustomValidationErrorHandler.php`
- `app/Shared/GraphQL/Errors/CustomAuthenticationErrorHandler.php`
- `app/Shared/GraphQL/Errors/CustomAuthorizationErrorHandler.php`

---

## 🎉 Conclusión

Este sistema te proporciona:

✅ **Seguridad**: No expone información sensible en producción
✅ **Debugging**: Información completa en desarrollo
✅ **Escalabilidad**: Fácil agregar nuevos handlers
✅ **Profesionalismo**: Sigue estándares de la industria
✅ **Reutilización**: Código DRY sin duplicación

**Para agregar un nuevo feature:**
1. Crea tus excepciones en `app/Features/[Feature]/Exceptions/`
2. Crea tu handler extendiendo `BaseErrorHandler`
3. Regístralo en `config/lighthouse.php`
4. ¡Listo! El sistema hace el resto automáticamente

---

**¿Preguntas?** Consulta este README o revisa los ejemplos en `app/Shared/GraphQL/Errors/`.
