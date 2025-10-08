# 🚀 MEJORAS PROFESIONALES: Manejo de Errores GraphQL por Entorno

**Fecha:** 08-Oct-2025
**Estado:** Pendiente de Implementación
**Prioridad:** Alta
**Estimación:** 45 minutos

---

## 📋 TABLA DE CONTENIDOS

1. [Contexto e Investigación](#contexto-e-investigación)
2. [Análisis de la Implementación Actual](#análisis-de-la-implementación-actual)
3. [Brecha (Gap Analysis)](#brecha-gap-analysis)
4. [Plan de Modificaciones Detallado](#plan-de-modificaciones-detallado)
5. [Ejemplos de Salida Esperada](#ejemplos-de-salida-esperada)
6. [Verificación y Testing](#verificación-y-testing)

---

## 🎯 CONTEXTO E INVESTIGACIÓN

### Investigación Realizada

Se realizó una investigación exhaustiva sobre las mejores prácticas de manejo de errores en APIs GraphQL, consultando:

- Especificación oficial de GraphQL
- Documentación de Apollo Server
- Artículos de seguridad en GraphQL (Escape.tech)
- Prácticas de la industria (Medium, Daily.dev)

### Conclusiones de la Investigación

#### 1. **Estructura de Errores Estándar de GraphQL**

La especificación define campos predeterminados en cada objeto de error:

| Campo | Propósito | Consideración de Seguridad |
|-------|-----------|---------------------------|
| `message` | Descripción legible del error | Debe ser genérico en producción |
| `path` | Ruta en la query hasta el campo con error | **Puede revelar estructura interna** |
| `locations` | Línea/columna donde ocurrió el error | **Puede revelar estructura de queries** |
| `extensions` | Información adicional personalizada | Controlar qué se expone |

**Riesgo de Seguridad:** Exponer detalles como `stacktrace`, `path`, `locations` puede revelar:
- Estructura interna de la API
- Nombres de archivos y rutas del servidor
- Lógica de negocio implementada
- Vulnerabilidades explotables

#### 2. **Mecanismos para Información Personalizada**

**Patrón Recomendado:** Usar el campo `extensions`

```json
{
  "extensions": {
    "code": "DUPLICATE_EMAIL",          // Código programático
    "timestamp": "2023-10-24T12:00:00Z", // Metadata
    "service": "user-service",           // Contexto
    "stacktrace": [...]                  // Solo en desarrollo
  }
}
```

**Ventajas:**
- ✅ Estándar oficial de GraphQL
- ✅ Flexible y escalable
- ✅ No rompe la estructura principal
- ✅ Fácil de mantener

**Alternativas descartadas:**
- ❌ Union Types (complejo, verboso)
- ❌ Campos custom (no estándar)

#### 3. **Requisitos por Entorno**

##### **DESARROLLO (DEV)**
**Objetivo:** Facilitar debugging

```json
{
  "message": "Ya existe un usuario con el correo 'test@example.com'.", // Específico
  "locations": [...],  // ✅ Visible
  "path": [...],       // ✅ Visible
  "extensions": {
    "code": "DUPLICATE_EMAIL",
    "timestamp": "2023-10-24T12:00:00Z",
    "service": "user-service",
    "stacktrace": [...]  // ✅ Completo
  }
}
```

##### **PRODUCCIÓN (PROD)**
**Objetivo:** Seguridad y UX

```json
{
  "message": "No se pudo completar el registro.",  // Genérico
  // ❌ locations omitido
  // ❌ path omitido
  "extensions": {
    "code": "VALIDATION_ERROR",
    "fieldErrors": [
      {
        "field": "email",
        "message": "Esta dirección de correo ya está en uso."
      }
    ],
    "timestamp": "2023-10-24T12:00:00Z"
    // ❌ stacktrace omitido
    // ❌ service omitido
  }
}
```

---

## 🔍 ANÁLISIS DE LA IMPLEMENTACIÓN ACTUAL

### Estado Actual (08-Oct-2025)

#### Archivos Implementados

```
app/Shared/GraphQL/Errors/
├── CustomAuthenticationErrorHandler.php    ✅ Creado
├── CustomAuthorizationErrorHandler.php     ✅ Creado
├── CustomValidationErrorHandler.php        ✅ Creado
└── GraphQLErrorFormatter.php               ⚠️ Deprecado (no usado)
```

#### Comportamiento Actual

**CustomValidationErrorHandler:**
```php
// SIEMPRE hace esto (en DEV y PROD):
unset($result['extensions']['file']);
unset($result['extensions']['line']);
unset($result['extensions']['trace']);

// NO diferencia entornos
// NO oculta locations/path en producción
// NO cambia mensajes según entorno
// NO agrega metadata útil
```

**Salida Actual (DEV y PROD son IGUALES):**
```json
{
  "errors": [{
    "message": "Validation error",
    "locations": [{"line": 2, "column": 3}],  // ⚠️ Expuesto en PROD
    "path": ["register"],                      // ⚠️ Expuesto en PROD
    "extensions": {
      "validation": {
        "passwordConfirmation": ["The password confirmation field must match password."]
      }
    }
  }]
}
```

---

## ⚠️ BRECHA (GAP ANALYSIS)

| Aspecto | Estado Actual | Estado Deseado | Prioridad |
|---------|--------------|----------------|-----------|
| **Ocultación de `locations`** | ❌ Siempre visible | ✅ Oculto en PROD | 🔴 ALTA |
| **Ocultación de `path`** | ❌ Siempre visible | ✅ Oculto en PROD | 🔴 ALTA |
| **Mensajes contextuales** | ❌ Siempre genérico | ✅ Detallado en DEV | 🟡 MEDIA |
| **Metadata (timestamp, service)** | ❌ No existe | ✅ En DEV | 🟡 MEDIA |
| **Estructura `fieldErrors`** | ❌ No existe | ✅ En PROD | 🟢 BAJA |
| **Código de error consistente** | ✅ Ya existe | ✅ Mantener | ✅ OK |
| **Stacktrace** | ✅ Siempre oculto | ⚠️ Visible en DEV | 🟡 MEDIA |

**Resumen:**
- 🔴 2 cambios críticos (locations, path)
- 🟡 3 cambios importantes (mensajes, metadata, stacktrace)
- 🟢 1 cambio nice-to-have (fieldErrors)

---

## 📐 PLAN DE MODIFICACIONES DETALLADO

### FASE 1: Modificar CustomValidationErrorHandler (30 min)

#### Archivo: `app/Shared/GraphQL/Errors/CustomValidationErrorHandler.php`

#### Cambios Específicos:

**1.1 Agregar imports necesarios:**
```php
use Illuminate\Support\Facades\Log;
```

**1.2 Modificar método `__invoke()` - ANTES:**
```php
// Limpiar y formatear el error
$result['extensions']['validation'] = $cleanedErrors;
$result['message'] = 'Validation error';

// SIEMPRE quitar file/line/trace
unset($result['extensions']['file']);
unset($result['extensions']['line']);
unset($result['extensions']['trace']);

return $result;
```

**1.2 Modificar método `__invoke()` - DESPUÉS:**
```php
// Limpiar y formatear el error
$isProduction = config('app.env') === 'production';
$isDebug = config('app.debug');

// Mensaje contextual según entorno
$result['message'] = $this->getContextualMessage($isProduction);

// Estructura de errores según entorno
if ($isProduction) {
    // PRODUCCIÓN: fieldErrors limpio y estructurado
    $result['extensions']['fieldErrors'] = $this->toFieldErrors($cleanedErrors);
    unset($result['extensions']['validation']); // Quitar estructura técnica

    // PRODUCCIÓN: Ocultar locations y path (pueden revelar estructura)
    unset($result['locations']);
    unset($result['path']);
} else {
    // DESARROLLO: validation detallado
    $result['extensions']['validation'] = $cleanedErrors;

    // DESARROLLO: Agregar metadata útil
    $result['extensions']['timestamp'] = now()->toIso8601String();
    $result['extensions']['environment'] = config('app.env');
}

// SIEMPRE quitar file/line/trace de Lighthouse (internos)
unset($result['extensions']['file']);
unset($result['extensions']['line']);
unset($result['extensions']['trace']);

return $result;
```

**1.3 Agregar método helper `getContextualMessage()`:**
```php
/**
 * Obtiene mensaje contextual según entorno
 *
 * @param bool $isProduction
 * @return string
 */
private function getContextualMessage(bool $isProduction): string
{
    return $isProduction
        ? 'No se pudo completar la operación. Verifica los datos ingresados.'
        : 'Validation error';
}
```

**1.4 Agregar método helper `toFieldErrors()`:**
```php
/**
 * Convierte errores de validación a estructura fieldErrors
 *
 * Formato PROD-friendly:
 * [
 *   {"field": "email", "message": "Email ya registrado"},
 *   {"field": "password", "message": "Debe tener 8 caracteres"}
 * ]
 *
 * @param array<string, array<string>> $validationErrors
 * @return array
 */
private function toFieldErrors(array $validationErrors): array
{
    $fieldErrors = [];

    foreach ($validationErrors as $field => $messages) {
        foreach ($messages as $message) {
            $fieldErrors[] = [
                'field' => $field,
                'message' => $message
            ];
        }
    }

    return $fieldErrors;
}
```

---

### FASE 2: Modificar CustomAuthenticationErrorHandler (10 min)

#### Archivo: `app/Shared/GraphQL/Errors/CustomAuthenticationErrorHandler.php`

#### Cambios Específicos:

**2.1 Modificar método `__invoke()` - Agregar después de limpiar mensaje:**
```php
// Limpiar y formatear el error
$isProduction = config('app.env') === 'production';

$result['message'] = $this->getCleanMessage($underlyingException);
$result['extensions']['category'] = 'authentication';
$result['extensions']['code'] = 'UNAUTHENTICATED';

// PRODUCCIÓN: Ocultar locations y path
if ($isProduction) {
    unset($result['locations']);
    unset($result['path']);
} else {
    // DESARROLLO: Agregar metadata
    $result['extensions']['timestamp'] = now()->toIso8601String();
    $result['extensions']['environment'] = config('app.env');
}

// SIEMPRE quitar file/line/trace
unset($result['extensions']['file']);
unset($result['extensions']['line']);
unset($result['extensions']['trace']);

return $result;
```

---

### FASE 3: Modificar CustomAuthorizationErrorHandler (10 min)

#### Archivo: `app/Shared/GraphQL/Errors/CustomAuthorizationErrorHandler.php`

#### Cambios Específicos:

**3.1 Idéntico a AuthenticationHandler:**
```php
// Limpiar y formatear el error
$isProduction = config('app.env') === 'production';

$result['message'] = $this->getCleanMessage($underlyingException);
$result['extensions']['category'] = 'authorization';
$result['extensions']['code'] = 'FORBIDDEN';

// PRODUCCIÓN: Ocultar locations y path
if ($isProduction) {
    unset($result['locations']);
    unset($result['path']);
} else {
    // DESARROLLO: Agregar metadata
    $result['extensions']['timestamp'] = now()->toIso8601String();
    $result['extensions']['environment'] = config('app.env');
}

// SIEMPRE quitar file/line/trace
unset($result['extensions']['file']);
unset($result['extensions']['line']);
unset($result['extensions']['trace']);

return $result;
```

---

### FASE 4: Configuración en .env (2 min)

#### Archivo: `.env`

**Asegurar que existen estas variables:**

```bash
# Ambiente (development, production, staging)
APP_ENV=development

# Debug mode (true = dev, false = prod)
APP_DEBUG=true

# Lighthouse Debug (controla stacktrace base)
LIGHTHOUSE_DEBUG=INCLUDE_DEBUG_MESSAGE|INCLUDE_TRACE
```

**Para simular PRODUCCIÓN localmente:**
```bash
APP_ENV=production
APP_DEBUG=false
LIGHTHOUSE_DEBUG=INCLUDE_NONE
```

---

## 📊 EJEMPLOS DE SALIDA ESPERADA

### Escenario: Error de validación - Email duplicado en registro

#### ANTES (Actual - Sin diferenciación)

```json
{
  "errors": [{
    "message": "Validation error",
    "locations": [{"line": 2, "column": 3}],
    "path": ["register"],
    "extensions": {
      "validation": {
        "email": ["The email has already been taken."]
      }
    }
  }],
  "data": null
}
```

---

#### DESPUÉS - DESARROLLO (APP_ENV=development, APP_DEBUG=true)

```json
{
  "errors": [{
    "message": "Validation error",
    "locations": [{"line": 2, "column": 3}],
    "path": ["register"],
    "extensions": {
      "validation": {
        "email": ["The email has already been taken."]
      },
      "timestamp": "2025-10-08T14:30:00Z",
      "environment": "development"
    }
  }],
  "data": null
}
```

**Cambios:**
- ✅ `locations` visible (útil para debugging)
- ✅ `path` visible (identifica operación)
- ✅ `timestamp` agregado (tracking)
- ✅ `environment` agregado (contexto)
- ✅ Estructura `validation` detallada

---

#### DESPUÉS - PRODUCCIÓN (APP_ENV=production, APP_DEBUG=false)

```json
{
  "errors": [{
    "message": "No se pudo completar la operación. Verifica los datos ingresados.",
    "extensions": {
      "fieldErrors": [
        {
          "field": "email",
          "message": "The email has already been taken."
        }
      ]
    }
  }],
  "data": null
}
```

**Cambios:**
- ✅ Mensaje genérico user-friendly
- ❌ `locations` OCULTO (seguridad)
- ❌ `path` OCULTO (seguridad)
- ❌ `timestamp` OCULTO (no necesario)
- ✅ Estructura `fieldErrors` limpia para frontend

---

### Escenario: Error de autenticación - Token inválido

#### DESARROLLO

```json
{
  "errors": [{
    "message": "Token de acceso inválido o expirado.",
    "locations": [{"line": 5, "column": 7}],
    "path": ["me"],
    "extensions": {
      "code": "UNAUTHENTICATED",
      "category": "authentication",
      "timestamp": "2025-10-08T14:35:00Z",
      "environment": "development"
    }
  }],
  "data": null
}
```

#### PRODUCCIÓN

```json
{
  "errors": [{
    "message": "Token de acceso inválido o expirado.",
    "extensions": {
      "code": "UNAUTHENTICATED",
      "category": "authentication"
    }
  }],
  "data": null
}
```

**Diferencias clave:**
- ❌ Sin `locations`, `path` en PROD
- ❌ Sin metadata en PROD
- ✅ Código de error consistente

---

## ✅ VERIFICACIÓN Y TESTING

### Checklist de Implementación

**Fase 1: Validation Handler**
- [ ] Imports agregados
- [ ] Lógica condicional `isProduction` implementada
- [ ] Método `getContextualMessage()` creado
- [ ] Método `toFieldErrors()` creado
- [ ] `locations` y `path` ocultos en PROD
- [ ] Metadata agregada en DEV

**Fase 2: Authentication Handler**
- [ ] Lógica condicional agregada
- [ ] `locations` y `path` ocultos en PROD
- [ ] Metadata agregada en DEV

**Fase 3: Authorization Handler**
- [ ] Lógica condicional agregada
- [ ] `locations` y `path` ocultos en PROD
- [ ] Metadata agregada en DEV

**Fase 4: Configuración**
- [ ] Variables `.env` verificadas
- [ ] Configuración de `APP_ENV` funcional
- [ ] Configuración de `APP_DEBUG` funcional

---

### Plan de Testing

#### Test 1: Validation Error en DESARROLLO

```bash
# .env
APP_ENV=development
APP_DEBUG=true
```

**GraphQL Query:**
```graphql
mutation {
  register(input: {
    email: "duplicate@example.com"  # Email ya existe
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

**Verificar:**
- ✅ Tiene `locations`
- ✅ Tiene `path`
- ✅ Tiene `timestamp`
- ✅ Tiene `environment: "development"`
- ✅ Estructura `validation` presente

---

#### Test 2: Validation Error en PRODUCCIÓN

```bash
# .env
APP_ENV=production
APP_DEBUG=false
```

**Misma query que Test 1**

**Verificar:**
- ❌ NO tiene `locations`
- ❌ NO tiene `path`
- ❌ NO tiene `timestamp`
- ✅ Tiene estructura `fieldErrors`
- ✅ Mensaje genérico

---

#### Test 3: Authentication Error

```bash
# Ambos entornos
```

**GraphQL Query:**
```graphql
query {
  me {
    id
    email
  }
}
# Sin header Authorization
```

**DEV - Verificar:**
- ✅ Tiene `locations`, `path`, `timestamp`

**PROD - Verificar:**
- ❌ NO tiene `locations`, `path`, `timestamp`
- ✅ Solo mensaje y código

---

### Comandos de Verificación

```bash
# 1. Verificar sintaxis PHP
cd C:/Users/heisn/Herd/Helpdesk
powershell -Command "php -l app/Shared/GraphQL/Errors/CustomValidationErrorHandler.php"

# 2. Validar schema GraphQL
powershell -Command "php artisan lighthouse:validate-schema"

# 3. Clear config cache
powershell -Command "php artisan config:clear"

# 4. Test manual con Apollo/GraphiQL
# http://localhost:8000/graphiql
```

---

## 📚 REFERENCIAS

### Documentación Oficial
- [GraphQL Spec - Errors](https://spec.graphql.org/June2018/#sec-Errors)
- [Apollo Server - Error Handling](https://www.apollographql.com/docs/apollo-server/data/errors/)

### Seguridad
- [GraphQL Security Best Practices](https://escape.tech/blog/9-graphql-security-best-practices/)

### Código Actual
- `app/Shared/GraphQL/Errors/CustomValidationErrorHandler.php`
- `app/Shared/GraphQL/Errors/CustomAuthenticationErrorHandler.php`
- `app/Shared/GraphQL/Errors/CustomAuthorizationErrorHandler.php`
- `config/lighthouse.php`

---

## 🎯 RESULTADO ESPERADO

Después de implementar estas mejoras:

✅ **Desarrollo:** Debugging fácil con información completa
✅ **Producción:** Seguro, no expone estructura interna
✅ **Escalable:** Solo cambiar `.env` para alternar
✅ **Profesional:** Sigue especificación de GraphQL
✅ **Reutilizable:** Se aplica automáticamente a todos los resolvers

---

## 📝 NOTAS PARA EL AGENTE

**Tiempo estimado total:** 45 minutos

**Orden de implementación:**
1. CustomValidationErrorHandler (más complejo, 30 min)
2. CustomAuthenticationErrorHandler (10 min)
3. CustomAuthorizationErrorHandler (10 min)
4. Testing manual (15 min)

**Precauciones:**
- NO modificar `GraphQLErrorFormatter.php` (deprecado)
- Probar en DEV antes de cambiar a PROD
- Reiniciar servidor después de cambios en config
- Usar `php artisan config:clear` si los cambios no se aplican

**Éxito se mide en:**
- Tests 1, 2 y 3 pasan correctamente
- Salida JSON coincide con ejemplos esperados
- No se rompe funcionalidad existente

---

**FIN DEL DOCUMENTO**
