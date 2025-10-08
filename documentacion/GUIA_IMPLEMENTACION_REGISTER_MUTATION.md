# 🚀 GUÍA PROFESIONAL: Implementación de RegisterMutation

**Fecha:** 07-Oct-2025
**Feature:** Authentication
**Objetivo:** Implementar RegisterMutation siguiendo mejores prácticas profesionales

---

## 📋 TABLA DE CONTENIDOS

1. [Arquitectura y Responsabilidades](#1-arquitectura-y-responsabilidades)
2. [Manejo de Excepciones](#2-manejo-de-excepciones)
3. [Validación de Inputs](#3-validación-de-inputs)
4. [Estructura del Resolver](#4-estructura-del-resolver)
5. [Seguridad](#5-seguridad)
6. [Testing](#6-testing)
7. [Checklist de Implementación](#7-checklist-de-implementación)

---

## 1. ARQUITECTURA Y RESPONSABILIDADES

### 🎯 Principio Fundamental: THIN RESOLVERS, FAT SERVICES

**El resolver NO debe contener lógica de negocio**, solo:
1. Transformar inputs GraphQL a formato de Service
2. Llamar al Service
3. Transformar respuesta del Service a formato GraphQL
4. Manejar excepciones (convertir a GraphQL errors)

```php
// ❌ MAL - Lógica de negocio en resolver
public function __invoke($root, array $args)
{
    if (User::where('email', $args['input']['email'])->exists()) {
        throw new Error('Email ya existe');
    }

    $user = User::create([...]);
    $token = JWT::encode([...]);
    return [...];
}

// ✅ BIEN - Resolver delgado, Service hace el trabajo
public function __invoke($root, array $args, $context)
{
    $result = $this->authService->register(
        data: $args['input'],
        deviceInfo: $this->extractDeviceInfo($context)
    );

    return $this->mapToGraphQLResponse($result);
}
```

### 📂 Separación de Responsabilidades

| Componente | Responsabilidad | Ejemplo |
|------------|-----------------|---------|
| **GraphQL Schema** | Validación de tipos y constraints | `@rules`, `Email!`, `min:8` |
| **Resolver (Mutation)** | Transformación de datos, orquestación | Mapear `firstName` → `first_name` |
| **Service (AuthService)** | Lógica de negocio, transacciones | Crear usuario + perfil + tokens |
| **Exceptions** | Errores de dominio | `ValidationException::duplicateValue()` |
| **Events** | Side effects asíncronos | Enviar email de verificación |
| **Jobs** | Tareas en background | Envío de emails en cola |

---

## 2. MANEJO DE EXCEPCIONES

### ✅ Tu estructura actual es EXCELENTE

Ya tienes:
- `HelpdeskException` (base)
- `ValidationException` ✅
- `AuthenticationException` ✅
- `AuthorizationException` ✅
- `NotFoundException` ✅

**Ubicación:** `app/Shared/Exceptions/`

### 📝 Excepciones a usar en RegisterMutation

```php
use App\Shared\Exceptions\ValidationException;
use App\Shared\Exceptions\AuthenticationException;

// Email duplicado
throw ValidationException::duplicateValue('email', $email);

// Contraseña débil (si haces validación adicional)
throw ValidationException::invalidFormat('password', 'Debe contener mayúsculas y números');

// Usuario suspendido intentando re-registrarse
throw AuthenticationException::accountSuspended();
```

### 🎯 Lighthouse maneja automáticamente estas excepciones

Lighthouse convierte tus excepciones a formato GraphQL:

```json
{
  "errors": [
    {
      "message": "El valor 'user@example.com' ya existe para el campo 'email'.",
      "extensions": {
        "code": "VALIDATION_ERROR",
        "category": "validation",
        "errors": {
          "email": ["duplicate"]
        }
      }
    }
  ]
}
```

### ⚠️ NO necesitas crear excepciones nuevas

Las que tienes son suficientes. Si necesitas algo específico de Authentication:

```php
// SOLO si es necesario, crear en:
// app/Features/Authentication/Exceptions/EmailAlreadyVerifiedException.php
namespace App\Features\Authentication\Exceptions;

use App\Shared\Exceptions\ValidationException;

class EmailAlreadyVerifiedException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('Este email ya ha sido verificado.', ['email' => ['already_verified']]);
    }
}
```

**Regla:** Usa excepciones compartidas primero. Crea feature-specific solo si es muy específico del dominio.

---

## 3. VALIDACIÓN DE INPUTS

### 🎯 Tres niveles de validación

#### Nivel 1: GraphQL Schema (Ya lo tienes) ✅

```graphql
input RegisterInput {
    email: Email!
        @rules(apply: ["required", "email", "unique:auth.users,email"])

    password: String!
        @rules(apply: ["required", "min:8", "confirmed"])

    passwordConfirmation: String!
}
```

**Lighthouse ejecuta esto ANTES de llegar a tu resolver.**

#### Nivel 2: Validación de Negocio en Service

```php
// AuthService.php - register()
if (User::where('email', $data['email'])->exists()) {
    throw ValidationException::duplicateValue('email', $data['email']);
}

// Validar reglas complejas que GraphQL no puede
if ($this->isDisposableEmail($data['email'])) {
    throw ValidationException::invalidFormat('email', 'No se permiten emails temporales');
}
```

#### Nivel 3: Validación de Seguridad

```php
// Sanitizar inputs (Eloquent ya lo hace, pero por si acaso)
$data['first_name'] = strip_tags($data['first_name']);
$data['last_name'] = strip_tags($data['last_name']);

// Rate limiting (ya tienes en schema)
@rateLimit(max: 5, window: 60)
```

### ⚠️ NO duplicar validaciones

Si GraphQL ya valida `email: Email!`, NO valides email en Service otra vez (solo validaciones de negocio).

---

## 4. ESTRUCTURA DEL RESOLVER

### 📐 Template Profesional para RegisterMutation

```php
<?php declare(strict_types=1);

namespace App\Features\Authentication\GraphQL\Mutations;

use App\Features\Authentication\Services\AuthService;
use App\Shared\GraphQL\Mutations\BaseMutation;
use Illuminate\Support\Str;

class RegisterMutation extends BaseMutation
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Registrar nuevo usuario
     *
     * @param  mixed  $root
     * @param  array{input: array{email: string, password: string, passwordConfirmation: string, firstName: string, lastName: string}}  $args
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context
     * @return array
     */
    public function __invoke($root, array $args, $context): array
    {
        // 1. Preparar datos para el servicio
        $input = $this->mapInputToServiceFormat($args['input']);

        // 2. Extraer información del contexto (IP, User-Agent, etc.)
        $deviceInfo = $this->extractDeviceInfo($context);

        // 3. Llamar al servicio (toda la lógica de negocio está aquí)
        $result = $this->authService->register($input, $deviceInfo);

        // 4. Transformar respuesta a formato GraphQL
        return $this->mapToGraphQLResponse($result, $context);
    }

    /**
     * Mapear inputs GraphQL a formato esperado por AuthService
     */
    private function mapInputToServiceFormat(array $input): array
    {
        return [
            'email' => strtolower(trim($input['email'])),
            'password' => $input['password'],
            'first_name' => ucfirst(strtolower(trim($input['firstName']))),
            'last_name' => ucfirst(strtolower(trim($input['lastName']))),
            'terms_accepted' => true, // Asumido al registrarse
        ];
    }

    /**
     * Extraer información del dispositivo desde contexto HTTP
     */
    private function extractDeviceInfo($context): array
    {
        $request = $context->request();

        return [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_name' => $this->parseDeviceName($request->userAgent()),
        ];
    }

    /**
     * Parsear User-Agent a nombre amigable
     */
    private function parseDeviceName(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        // Detección simple (puedes usar una librería como jenssegers/agent)
        if (str_contains($userAgent, 'iPhone')) return 'iPhone';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'Windows')) return 'Chrome on Windows';
        if (str_contains($userAgent, 'Macintosh')) return 'Safari on macOS';

        return 'Web Browser';
    }

    /**
     * Mapear respuesta del servicio a formato GraphQL AuthPayload
     */
    private function mapToGraphQLResponse(array $result, $context): array
    {
        $user = $result['user'];

        return [
            'accessToken' => $result['access_token'],
            'refreshToken' => $result['refresh_token'],
            'tokenType' => 'Bearer',
            'expiresIn' => $result['expires_in'],
            'user' => [
                'id' => $user->id,
                'userCode' => $user->user_code,
                'email' => $user->email,
                'emailVerified' => $user->email_verified,
                'status' => $user->status->value,
                'profile' => [
                    'firstName' => $user->profile->first_name,
                    'lastName' => $user->profile->last_name,
                    'displayName' => "{$user->profile->first_name} {$user->profile->last_name}",
                    'avatarUrl' => $user->profile->avatar_url,
                ],
            ],
            'roleContexts' => [], // Usuario nuevo no tiene roles de empresa aún
            'sessionId' => Str::uuid()->toString(),
            'loginTimestamp' => now()->toIso8601String(),
        ];
    }
}
```

### 🎯 Ventajas de esta estructura

✅ **Separation of Concerns:** Cada método tiene una responsabilidad clara
✅ **Testeable:** Puedes testear `mapInputToServiceFormat()` por separado
✅ **Mantenible:** Fácil de leer y modificar
✅ **Type-safe:** PHPDoc completo
✅ **Reusable:** Métodos helper pueden reutilizarse en LoginMutation

---

## 5. SEGURIDAD

### 🔐 Checklist de Seguridad

#### ✅ Ya implementado (en tu schema/service)

- [x] Rate limiting: `@rateLimit(max: 5, window: 60)`
- [x] Password hashing: `Hash::make()` en UserService
- [x] Email verification: Event `UserRegistered` → Job `SendEmailVerificationJob`
- [x] JWT tokens: `TokenService::generateAccessToken()`
- [x] Unique email validation: `@rules(apply: ["unique:auth.users,email"])`

#### ⚠️ Consideraciones adicionales

**1. Sanitización de inputs**

```php
// En mapInputToServiceFormat()
'first_name' => strip_tags(ucfirst(strtolower(trim($input['firstName'])))),
'last_name' => strip_tags(ucfirst(strtolower(trim($input['lastName'])))),
```

**2. Prevenir enumeración de usuarios**

```php
// ❌ MAL - Revela si email existe
if (User::where('email', $email)->exists()) {
    throw new Error('Email ya registrado');
}

// ✅ BIEN - Mensaje genérico
throw ValidationException::duplicateValue('email', $email);
// Cliente recibe: "El valor ya existe" (no dice que es un usuario)
```

**3. CAPTCHA (opcional, futuro)**

```php
// Si hay abuso de bots
if (!$this->verifyCaptcha($input['captchaToken'])) {
    throw ValidationException::invalidFormat('captcha', 'Verificación inválida');
}
```

**4. Logging de intentos sospechosos**

```php
// En el resolver, antes de llamar al servicio
Log::info('Registration attempt', [
    'email' => $input['email'],
    'ip' => $deviceInfo['ip_address'],
    'user_agent' => $deviceInfo['user_agent'],
]);
```

**5. CSRF Protection**

Ya está manejado por Laravel si usas cookies. Si usas pure GraphQL (stateless), no es necesario.

---

## 6. TESTING

### 🧪 Tests a implementar

#### Test 1: Feature Test - Registro Exitoso

```php
// tests/Feature/Authentication/RegisterMutationTest.php
public function test_user_can_register_successfully()
{
    $response = $this->graphQL('
        mutation Register($input: RegisterInput!) {
            register(input: $input) {
                accessToken
                refreshToken
                user {
                    email
                    profile {
                        firstName
                        lastName
                    }
                }
            }
        }
    ', [
        'input' => [
            'email' => 'newuser@example.com',
            'password' => 'SecurePass123!',
            'passwordConfirmation' => 'SecurePass123!',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]
    ]);

    $response->assertJson([
        'data' => [
            'register' => [
                'user' => [
                    'email' => 'newuser@example.com',
                    'profile' => [
                        'firstName' => 'John',
                        'lastName' => 'Doe',
                    ]
                ]
            ]
        ]
    ]);

    $this->assertDatabaseHas('auth.users', [
        'email' => 'newuser@example.com',
    ]);
}
```

#### Test 2: Email Duplicado

```php
public function test_cannot_register_with_existing_email()
{
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->graphQL('...', [
        'input' => [
            'email' => 'existing@example.com',
            // ...
        ]
    ]);

    $response->assertGraphQLErrorCategory('validation');
    $response->assertGraphQLValidationError('email', 'duplicate');
}
```

#### Test 3: Password Débil

```php
public function test_cannot_register_with_weak_password()
{
    $response = $this->graphQL('...', [
        'input' => [
            'email' => 'test@example.com',
            'password' => '123', // Muy corta
            'passwordConfirmation' => '123',
            // ...
        ]
    ]);

    $response->assertGraphQLValidationError('password', 'min');
}
```

#### Test 4: Rate Limiting

```php
public function test_rate_limiting_prevents_spam_registrations()
{
    for ($i = 0; $i < 6; $i++) {
        $response = $this->graphQL('...', [
            'input' => [
                'email' => "user{$i}@example.com",
                // ...
            ]
        ]);
    }

    // 6to intento debe fallar
    $response->assertGraphQLError('Demasiados intentos de registro');
}
```

---

## 7. CHECKLIST DE IMPLEMENTACIÓN

### Paso 1: Preparación ✅

- [x] Revisar GraphQL schema (`authentication.graphql`)
- [x] Verificar que `AuthService::register()` existe y funciona
- [x] Confirmar que excepciones personalizadas están listas
- [x] Revisar estructura de `AuthPayload` en schema

### Paso 2: Implementación

- [ ] Copiar template del resolver desde esta guía
- [ ] Implementar `__construct()` con dependency injection de `AuthService`
- [ ] Implementar `__invoke()` principal
- [ ] Implementar `mapInputToServiceFormat()`
- [ ] Implementar `extractDeviceInfo()`
- [ ] Implementar `parseDeviceName()`
- [ ] Implementar `mapToGraphQLResponse()`
- [ ] Agregar type hints completos (PHPDoc)

### Paso 3: Testing

- [ ] Crear `RegisterMutationTest.php` en `tests/Feature/Authentication/`
- [ ] Test: Registro exitoso
- [ ] Test: Email duplicado
- [ ] Test: Password confirmation no coincide
- [ ] Test: Campos requeridos faltantes
- [ ] Test: Rate limiting
- [ ] Ejecutar tests: `php artisan test --filter RegisterMutationTest`

### Paso 4: Verificación

- [ ] Probar en GraphiQL/Postman manualmente
- [ ] Verificar que se crea usuario en DB
- [ ] Verificar que se crea perfil
- [ ] Verificar que se crea rol USER por defecto (si aplica)
- [ ] Verificar que se envía email de verificación (revisar Mailpit)
- [ ] Verificar que tokens funcionan (hacer query `authStatus`)

### Paso 5: Documentación

- [ ] Agregar comentarios en código
- [ ] Actualizar CHANGELOG si tienes
- [ ] Marcar como implementado en documentación del feature

---

## 🎯 EJEMPLO COMPLETO DE USO

### GraphQL Request

```graphql
mutation Register {
  register(input: {
    email: "user@example.com"
    password: "SecurePass123!"
    passwordConfirmation: "SecurePass123!"
    firstName: "María"
    lastName: "García"
  }) {
    accessToken
    refreshToken
    tokenType
    expiresIn
    user {
      id
      userCode
      email
      emailVerified
      profile {
        firstName
        lastName
        displayName
      }
    }
    roleContexts {
      role
      company {
        id
        name
      }
    }
    sessionId
    loginTimestamp
  }
}
```

### Response Exitosa

```json
{
  "data": {
    "register": {
      "accessToken": "eyJhbGciOiJIUzI1NiIs...",
      "refreshToken": "6a8f4c2e9d1b...",
      "tokenType": "Bearer",
      "expiresIn": 3600,
      "user": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "userCode": "USR-2025-00123",
        "email": "user@example.com",
        "emailVerified": false,
        "profile": {
          "firstName": "María",
          "lastName": "García",
          "displayName": "María García"
        }
      },
      "roleContexts": [],
      "sessionId": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "loginTimestamp": "2025-10-07T15:30:00Z"
    }
  }
}
```

### Response con Error

```json
{
  "errors": [
    {
      "message": "El valor 'user@example.com' ya existe para el campo 'email'.",
      "extensions": {
        "code": "VALIDATION_ERROR",
        "category": "validation",
        "errors": {
          "email": ["duplicate"]
        }
      },
      "path": ["register"]
    }
  ],
  "data": {
    "register": null
  }
}
```

---

## 📚 RECURSOS ADICIONALES

### Documentación Relevante

- **Lighthouse GraphQL:** https://lighthouse-php.com/master/getting-started/installation.html
- **Laravel Validation:** https://laravel.com/docs/11.x/validation
- **JWT Best Practices:** https://datatracker.ietf.org/doc/html/rfc8725

### Archivos Clave en tu Proyecto

```
app/Features/Authentication/
├── GraphQL/
│   ├── Mutations/
│   │   └── RegisterMutation.php ← TU ARCHIVO
│   └── Schema/
│       └── authentication.graphql ← Schema con RegisterInput y AuthPayload
├── Services/
│   └── AuthService.php ← Lógica de negocio
├── Events/
│   └── UserRegistered.php ← Evento después de registro
└── Jobs/
    └── SendEmailVerificationJob.php ← Job que envía email

app/Shared/
├── Exceptions/
│   ├── ValidationException.php ← Para errores de validación
│   └── AuthenticationException.php ← Para errores de auth
└── GraphQL/
    └── Mutations/
        └── BaseMutation.php ← Clase base
```

---

## ✅ CONCLUSIÓN

**Implementar RegisterMutation profesionalmente significa:**

1. ✅ **Resolver delgado:** Solo orquestación y transformación
2. ✅ **Service gordo:** Toda la lógica de negocio en `AuthService::register()`
3. ✅ **Excepciones claras:** Usar las que ya tienes en `app/Shared/Exceptions/`
4. ✅ **Validación en capas:** GraphQL + Service + Seguridad
5. ✅ **Testing completo:** Feature tests cubriendo casos felices y errores
6. ✅ **Seguridad:** Rate limiting, sanitización, logging
7. ✅ **Documentación:** PHPDoc completo, código legible

**Sigue el template de esta guía y tendrás una implementación de nivel producción.** 🚀

---

**Autor:** Claude Code
**Fecha:** 07-Oct-2025
**Versión:** 1.0
