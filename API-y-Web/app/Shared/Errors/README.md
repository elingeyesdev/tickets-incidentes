# 🚀 Sistema Agnóstico de Códigos de Error

**Versión**: 1.0
**Propósito**: Códigos de error compartidos entre GraphQL y REST
**Estado**: ✅ Implementado

---

## 🎯 Descripción

Este sistema proporciona **códigos de error centralizados** agnósticos de la implementación (GraphQL o REST). Esto significa:

- ✅ GraphQL y REST devuelven los **mismos códigos de error**
- ✅ Frontend puede usar el mismo código para ambos
- ✅ Cuando se elimine GraphQL, los códigos permanecen
- ❌ No está atado a GraphQL ni REST

---

## 📁 Estructura

```
app/Shared/Errors/
├── ErrorCodeRegistry.php   ← Códigos centralizados (AGNÓSTICO)
└── README.md              ← Esta documentación
```

---

## 🔢 Códigos de Error

### Ejemplo: ValidationException

Cualquier lugar que lance una excepción:

```php
use App\Shared\Exceptions\ValidationException;
use App\Shared\Errors\ErrorCodeRegistry;

throw ValidationException::withField('email', 'Email is required');
// El ValidationException debe tener un código de error:
// 'VALIDATION_ERROR' (definido en ErrorCodeRegistry)
```

**En GraphQL**, el `CustomValidationErrorHandler` la captura y retorna:
```json
{
  "errors": [{
    "message": "Validation error",
    "extensions": {
      "code": "VALIDATION_ERROR",
      "category": "validation",
      "validation": { "email": ["Email is required"] }
    }
  }]
}
```

**En REST**, el `ApiExceptionHandler` middleware la captura y retorna:
```json
{
  "success": false,
  "message": "Validation failed.",
  "code": "VALIDATION_ERROR",
  "category": "validation",
  "errors": { "email": ["Email is required"] }
}
```

**El código `VALIDATION_ERROR` es el mismo** - eso es lo importante.

---

## 💡 Cómo Funciona

### 1. Excepciones (app/Shared/Exceptions/)

Las excepciones DEBEN tener un `getErrorCode()` que retorne una constante de `ErrorCodeRegistry`:

```php
// app/Shared/Exceptions/ValidationException.php
class ValidationException extends HelpdeskException
{
    protected $errorCode = ErrorCodeRegistry::VALIDATION_ERROR;

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}
```

### 2. ErrorCodeRegistry (app/Shared/Errors/)

Mapea códigos a metadatos:

```php
ErrorCodeRegistry::VALIDATION_ERROR   // Constante
ErrorCodeRegistry::getCategory('VALIDATION_ERROR')    // → 'validation'
ErrorCodeRegistry::getSuggestedHttpStatus('VALIDATION_ERROR')  // → 400
ErrorCodeRegistry::getDescription('VALIDATION_ERROR')  // → 'Input validation failed'
```

### 3. Handlers (específicos de cada implementación)

**GraphQL** (app/Shared/GraphQL/Errors/):
- `CustomValidationErrorHandler` captura ValidationException
- Usa ErrorCodeRegistry para obtener metadatos
- Retorna estructura GraphQL

**REST** (app/Http/Middleware/):
- `ApiExceptionHandler` captura excepciones
- Usa ErrorCodeRegistry para obtener metadatos
- Retorna estructura JSON

---

## 🗂️ Categorías de Códigos

```
authentication    → 401
authorization     → 403
validation        → 400/422
business_logic    → 409
not_found         → 404
rate_limit        → 429
server_error      → 500
```

---

## 🔄 Flujo Completo

```
1. Service lanza: ValidationException::withField('email', 'Required')
   ↓
2. Excepción tiene: getErrorCode() → 'VALIDATION_ERROR'
   ↓
3. GraphQL Handler captura:
   - Código: 'VALIDATION_ERROR'
   - Categoría: 'validation'
   - Respuesta GraphQL
   ↓
4. REST Middleware captura:
   - Código: 'VALIDATION_ERROR'
   - Categoría: 'validation'
   - Respuesta REST
   ↓
5. Frontend recibe mismo código: 'VALIDATION_ERROR'
   - Puede usar mismo código de manejo
```

---

## 🚀 Ejemplo: Crear Nueva Excepción

### 1. Crear Excepción (en tu feature)

```php
// app/Features/MyFeature/Exceptions/MyCustomException.php
namespace App\Features\MyFeature\Exceptions;

use App\Shared\Exceptions\HelpdeskException;
use App\Shared\Errors\ErrorCodeRegistry;

class MyCustomException extends HelpdeskException
{
    public function __construct(string $message = 'Something went wrong')
    {
        parent::__construct($message);
        $this->errorCode = ErrorCodeRegistry::CONFLICT;
    }
}
```

### 2. Usar en Service

```php
public function doSomething()
{
    if (/* error condition */) {
        throw new MyCustomException('Cannot process this request');
    }
}
```

### 3. Automáticamente:
- ✅ GraphQL manejará el error (si existe handler para HelpdeskException)
- ✅ REST manejará el error (ApiExceptionHandler lo captura)
- ✅ Ambos retornarán `code: 'CONFLICT'` y `category: 'business_logic'`
- ✅ Cliente recibe mismo código

---

## 📋 Códigos Disponibles

Todos en `ErrorCodeRegistry::`:

**Authentication:**
- `UNAUTHENTICATED`
- `INVALID_CREDENTIALS`
- `TOKEN_EXPIRED`
- `EMAIL_NOT_VERIFIED`
- `ACCOUNT_SUSPENDED`

**Authorization:**
- `FORBIDDEN`
- `INSUFFICIENT_ROLE`
- `WRONG_COMPANY`

**Validation:**
- `VALIDATION_ERROR`
- `REQUIRED_FIELD`
- `INVALID_FORMAT`

**Business Logic:**
- `CONFLICT`
- `INVALID_STATE`
- `DUPLICATE_EMAIL`
- `RESOURCE_ALREADY_EXISTS`

**Not Found:**
- `NOT_FOUND`
- `USER_NOT_FOUND`

**Rate Limiting:**
- `RATE_LIMIT_EXCEEDED`

**Server Errors:**
- `INTERNAL_SERVER_ERROR`
- `DATABASE_ERROR`

---

## ✅ Ventajas

- ✅ **Sin duplicación**: Un único lugar para códigos
- ✅ **Agnóstico**: No atado a GraphQL ni REST
- ✅ **Escalable**: Agregar códigos = 1 línea
- ✅ **Migratable**: Cuando elimines GraphQL, esto queda
- ✅ **Consistente**: GraphQL y REST usan los mismos códigos
- ✅ **Frontend friendly**: Mismo código para ambas APIs

---

## 🗑️ Migración Futura (Cuando Elimines GraphQL)

**QUE SE QUEDA:**
- ✅ `app/Shared/Errors/ErrorCodeRegistry.php` (agnóstico)
- ✅ `app/Shared/Exceptions/` (agnóstico)
- ✅ `app/Http/Middleware/ApiExceptionHandler.php` (REST)

**QUE SE ELIMINA:**
- ❌ `app/Shared/GraphQL/Errors/` (específico de GraphQL)
- ❌ `config/lighthouse.php` (configuración de GraphQL)
- ❌ `graphql/` (schema de GraphQL)

REST seguirá funcionando exactamente igual.

---

## 🔗 Referencias

- `app/Shared/Errors/ErrorCodeRegistry.php` - Códigos centralizados
- `app/Http/Middleware/ApiExceptionHandler.php` - Manejo REST
- `app/Shared/Exceptions/` - Excepciones agnósticas
