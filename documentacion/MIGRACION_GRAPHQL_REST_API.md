# 🚀 MIGRACIÓN GraphQL → REST API - GUÍA COMPLETA

**Fecha de Inicio:** 27-Octubre-2025
**Última actualización:** 27-Octubre-2025 15:45 UTC
**Estado:** 🟢 Fase 1 Completada | 🟡 Fase 2 Pendiente
**Rama:** feature/graphql-to-rest-migration
**Cambios Esperados:** 41 GraphQL endpoints → 15 REST endpoints (Authentication feature)
**Tests Esperados:** 471 tests (100% pasando)

---

## 📋 TABLA DE CONTENIDOS

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Estado Actual - GraphQL](#estado-actual-graphql)
3. [Estrategia de Migración](#estrategia-de-migración)
4. [Sistema de Errores (Crítico)](#sistema-de-errores-crítico)
5. [Estructura REST](#estructura-rest)
6. [Mapeo de Endpoints](#mapeo-de-endpoints)
7. [Checklist de Progreso](#checklist-de-progreso)
8. [Fases de Implementación](#fases-de-implementación)
9. [Notas Técnicas](#notas-técnicas)

---

## 🎯 RESUMEN EJECUTIVO

### Objetivo
Migrar la API de GraphQL a REST manteniendo:
- ✅ **Funcionalidad idéntica**: Misma lógica de negocio
- ✅ **Respuestas idénticas**: Mismo JSON, mismo formato
- ✅ **Seguridad idéntica**: Sistema de errores profesional
- ✅ **Tests reutilizables**: 471 tests al 100%
- ✅ **Documentación automática**: L5-Swagger (OpenAPI 3.0)

### Enfoque
**Feature-First REST** (mantener arquitectura existente):
```
app/Features/Authentication/
├── Http/Controllers/        ← REST Controllers
├── Http/Requests/           ← Form Requests (validación)
├── Http/Resources/          ← API Resources (JSON)
├── Services/                ← Lógica idéntica
└── Exceptions/              ← Manejo de errores
```

### Ganancia Principal
- 📊 **1 endpoint** (`/graphql`) → **15 endpoints REST** más intuitivos
- 📚 **Documentación automática** en `http://localhost:8000/api/docs`
- 🔒 **Mismo nivel de seguridad** con sistema de errores profesional
- 🧪 **Tests reutilizables** (cambiar solo método de invocación)

---

## 🔄 ESTADO ACTUAL - GraphQL

### Arquitectura Actual
```
┌─────────────────────────────────────┐
│         GraphQL Endpoint             │
│         POST /graphql                │
└────────────┬────────────────────────┘
             │
      ┌──────┴──────────────────┬──────────────────┬──────────────┐
      │                         │                  │              │
   Queries (4)           Mutations (11)      Shared Types       Error Handlers
   ├─ authStatus         ├─ register         (UserAuthInfo)     (BaseErrorHandler
   ├─ mySessions         ├─ login            (RoleContext)      + 3 Handlers)
   ├─ passwordResetStatus├─ loginWithGoogle  (SessionInfo)
   └─ emailVerificationStatus├─ refreshToken
                         ├─ logout
                         ├─ revokeOtherSession
                         ├─ resetPassword
                         ├─ confirmPasswordReset
                         ├─ verifyEmail
                         ├─ resendVerification
                         └─ markOnboardingCompleted
```

### Estadísticas
| Métrica | Cantidad |
|---------|----------|
| Queries | 4 |
| Mutations | 11 |
| Types | 13+ |
| Resolvers | 15 |
| Tests | 9 |
| Error Handlers | 3+ |
| Validación | @rules directive |
| Documentación | GraphiQL IDE |

### Sistema de Errores Actual (GraphQL)
**Componentes:**
- `HelpdeskException` - Clase base (ClientAware)
- `ValidationException` - Errores de validación
- `AuthenticationException` - Errores de auth
- `AuthorizationException` - Errores de permisos
- `BaseErrorHandler` - Plantilla para handlers
- `EnvironmentErrorFormatter` - Diferenciación DEV/PROD
- `ErrorCodeRegistry` - Códigos centralizados

**Flujo:**
```
GraphQL Mutation/Query
    ↓
Service (lógica de negocio)
    ↓
Throw HelpdeskException (o subclase)
    ↓
Error Handler (BaseErrorHandler)
    ↓
EnvironmentErrorFormatter (DEV/PROD)
    ↓
JSON Response (DEV detallado, PROD seguro)
```

---

## 🔀 ESTRATEGIA DE MIGRACIÓN

### Principio Clave: Separación de Concerns

```
┌────────────────────────────────────┐
│  REST Controller                   │  ← Solo orquestación
│  - Recibe HTTP Request             │     No lógica de negocio
│  - Delega a Service                │
└───────────────┬────────────────────┘
                ↓
┌────────────────────────────────────┐
│  Service (IDÉNTICO a GraphQL)      │  ← Misma lógica
│  - Validación                      │     Mismas operaciones
│  - Lógica de negocio               │     Mismas excepciones
│  - Manejo de errores               │
└───────────────┬────────────────────┘
                ↓
┌────────────────────────────────────┐
│  Exception (IDÉNTICA)              │  ← Misma estructura
│  - HelpdeskException               │     Mismo código de error
│  - Métodos de conversión           │
└───────────────┬────────────────────┘
                ↓
┌────────────────────────────────────┐
│  ExceptionHandler Middleware       │  ← Manejo centralizado
│  - Captura excepciones             │     DEV/PROD
│  - EnvironmentErrorFormatter       │     Logging
└───────────────┬────────────────────┘
                ↓
┌────────────────────────────────────┐
│  Resource (Transformación)         │  ← JSON idéntico
│  - Estructura de JSON              │     Mismo anidamiento
│  - Relaciones anidadas             │
└───────────────┬────────────────────┘
                ↓
┌────────────────────────────────────┐
│  JSON Response (IDÉNTICO)          │  ← Mismo formato
└────────────────────────────────────┘
```

### Cómo Garantizar Respuestas Idénticas

**Regla 1: Mismos Services**
```php
// Se usa en ambos contextos
class AuthService {
    public function login(array $data): AuthPayload { ... }
}

// GraphQL Resolver → AuthService.login()
// REST Controller → AuthService.login()
// ✅ LÓGICA IDÉNTICA
```

**Regla 2: API Resources = GraphQL Types**
```php
// Retorna exactamente lo que GraphQL retornaba
class AuthPayloadResource extends JsonResource {
    return [
        'accessToken' => $this->accessToken,
        'user' => new UserAuthInfoResource($this->user),
        'roleContexts' => RoleContextResource::collection($this->roleContexts),
    ];
}
```

**Regla 3: Form Requests = @rules Directives**
```php
// Mismas validaciones
public function rules(): array {
    return ['email' => 'required|email|unique:...'];
}
```

---

## 🚨 SISTEMA DE ERRORES - CRÍTICO

### Migrando GraphQL Error Handlers a REST

#### Estructura Actual (GraphQL)

```php
// app/Shared/Exceptions/HelpdeskException.php
abstract class HelpdeskException extends Exception implements ClientAware
{
    protected string $category = 'general';
    protected bool $isClientSafe = true;
    protected string $errorCode;

    public function getErrorCode(): string { ... }
    public function getCategory(): string { ... }
    public function toArray(): array { ... }
}
```

#### Adaptación para REST

El sistema se **REUTILIZA COMPLETAMENTE** en REST:
- ✅ Mismas excepciones
- ✅ Mismo middleware de manejo
- ✅ Mismo sistema de categorías
- ✅ Mismo formato JSON

**Cambio ÚNICO: HTTP Status Codes**

En GraphQL, todo era 200 con errores en `errors[]`.
En REST, usamos códigos HTTP estándar:

| Excepción | GraphQL | REST |
|-----------|---------|------|
| `ValidationException` | 200 + errors | **422** Unprocessable Entity |
| `AuthenticationException` | 200 + errors | **401** Unauthorized |
| `AuthorizationException` | 200 + errors | **403** Forbidden |
| `NotFoundException` | 200 + errors | **404** Not Found |
| `ConflictException` | 200 + errors | **409** Conflict |
| `RateLimitExceededException` | 200 + errors | **429** Too Many Requests |
| Otros errores | 200 + errors | **500** Internal Server Error |

#### Middleware de Manejo de Excepciones (NUEVO)

```php
// app/Http/Middleware/ApiExceptionHandler.php
namespace App\Http\Middleware;

class ApiExceptionHandler
{
    public function handle($request, Closure $next)
    {
        try {
            return $next($request);
        } catch (HelpdeskException $e) {
            return $this->handleHelpdeskException($e);
        } catch (Exception $e) {
            return $this->handleGenericException($e);
        }
    }

    private function handleHelpdeskException(HelpdeskException $e)
    {
        // Determinar HTTP status code según tipo de excepción
        $statusCode = $this->getStatusCodeFor($e);

        // Formatear respuesta (usar EnvironmentErrorFormatter)
        $response = [
            'success' => false,
            'message' => $e->getMessage(),
            'code' => $e->getErrorCode(),
        ];

        // En PROD, formatear para seguridad
        if (!app()->isLocal()) {
            $response = EnvironmentErrorFormatter::formatForProduction($response, $e);
        }

        return response()->json($response, $statusCode);
    }

    private function getStatusCodeFor(HelpdeskException $e): int
    {
        return match($e::class) {
            ValidationException::class => 422,
            AuthenticationException::class => 401,
            AuthorizationException::class => 403,
            NotFoundException::class => 404,
            ConflictException::class => 409,
            RateLimitExceededException::class => 429,
            default => 500,
        };
    }
}
```

#### Diferenciación DEV/PROD en REST

**DESARROLLO (local):**
```json
{
    "success": false,
    "message": "Credenciales incorrectas. Verifica tu email y contraseña.",
    "code": "INVALID_CREDENTIALS",
    "category": "authentication",
    "timestamp": "2025-10-27T12:30:00Z",
    "environment": "local",
    "debug": {
        "file": "/var/www/app/Features/Authentication/Services/AuthService.php",
        "line": 45,
        "trace": [...]
    }
}
```

**PRODUCCIÓN:**
```json
{
    "success": false,
    "message": "Credenciales incorrectas. Verifica tu email y contraseña.",
    "code": "INVALID_CREDENTIALS",
    "timestamp": "2025-10-27T12:30:00Z"
}
```

#### Validación en Form Requests

```php
// app/Features/Authentication/Http/Requests/LoginRequest.php
class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'deviceName' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El email es requerido.',
            'email.email' => 'El email debe ser válido.',
            'password.required' => 'La contraseña es requerida.',
        ];
    }
}
```

**Resultado:**
```
POST /api/auth/login
{ "email": "", "password": "" }

Response 422:
{
    "success": false,
    "message": "Errores de validación",
    "code": "VALIDATION_ERROR",
    "errors": {
        "email": ["El email es requerido."],
        "password": ["La contraseña es requerida."]
    }
}
```

---

## 🏗️ ESTRUCTURA REST

### Organización de Carpetas

```
app/Features/Authentication/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php              ← 5 métodos
│   │   ├── PasswordResetController.php     ← 3 métodos
│   │   ├── EmailVerificationController.php ← 3 métodos
│   │   ├── SessionController.php           ← 3 métodos
│   │   └── OnboardingController.php        ← 1 método
│   │
│   ├── Requests/
│   │   ├── RegisterRequest.php
│   │   ├── LoginRequest.php
│   │   ├── GoogleLoginRequest.php
│   │   ├── PasswordResetRequest.php
│   │   ├── EmailVerifyRequest.php
│   │   ├── LogoutRequest.php
│   │   └── LogoutEverywhereRequest.php
│   │
│   └── Resources/
│       ├── AuthPayloadResource.php
│       ├── RefreshPayloadResource.php
│       ├── AuthStatusResource.php
│       ├── UserAuthInfoResource.php
│       ├── RoleContextResource.php
│       ├── SessionInfoResource.php
│       ├── PasswordResetStatusResource.php
│       ├── PasswordResetResultResource.php
│       ├── EmailVerificationStatusResource.php
│       ├── EmailVerificationResultResource.php
│       ├── MarkOnboardingCompletedResource.php
│       └── SessionInfoResourceCollection.php
│
├── Services/                              ← IDÉNTICO a GraphQL
│   ├── AuthService.php
│   ├── TokenService.php
│   ├── PasswordResetService.php
│   ├── EmailVerificationService.php
│   └── SessionService.php
│
├── Models/                                ← IDÉNTICO
├── Events/                                ← IDÉNTICO
├── Listeners/                             ← IDÉNTICO
├── Jobs/                                  ← IDÉNTICO
├── Policies/                              ← IDÉNTICO
│
└── Database/
    ├── Migrations/                        ← IDÉNTICO
    ├── Seeders/                           ← IDÉNTICO
    └── Factories/                         ← IDÉNTICO
```

### Routes

```php
// routes/api.php
Route::prefix('auth')->group(function () {
    // Público
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/google', [AuthController::class, 'loginWithGoogle']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::post('/password-reset', [PasswordResetController::class, 'store']);
    Route::post('/password-reset/confirm', [PasswordResetController::class, 'confirm']);
    Route::get('/password-reset/status', [PasswordResetController::class, 'status']);

    Route::post('/email/verify', [EmailVerificationController::class, 'verify']);
    Route::get('/email/status', [EmailVerificationController::class, 'status']);

    // Autenticado
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [SessionController::class, 'logout']);
        Route::delete('/sessions/{sessionId}', [SessionController::class, 'revoke']);
        Route::post('/email/verify/resend', [EmailVerificationController::class, 'resend']);
        Route::get('/status', [AuthController::class, 'status']);
        Route::get('/sessions', [SessionController::class, 'index']);
        Route::post('/onboarding/completed', [OnboardingController::class, 'markCompleted']);
    });
});
```

---

## 📍 MAPEO DE ENDPOINTS

### Tabla Completa de Mapeo

| # | Tipo | GraphQL | REST | Método | Status |
|---|------|---------|------|--------|--------|
| 1 | Query | `authStatus` | `/api/auth/status` | GET | 🟡 Pendiente |
| 2 | Query | `mySessions` | `/api/auth/sessions` | GET | 🟡 Pendiente |
| 3 | Query | `passwordResetStatus` | `/api/auth/password-reset/status` | GET | 🟡 Pendiente |
| 4 | Query | `emailVerificationStatus` | `/api/auth/email/status` | GET | 🟡 Pendiente |
| 5 | Mutation | `register` | `/api/auth/register` | POST | 🟡 Pendiente |
| 6 | Mutation | `login` | `/api/auth/login` | POST | 🟡 Pendiente |
| 7 | Mutation | `loginWithGoogle` | `/api/auth/login/google` | POST | 🟡 Pendiente |
| 8 | Mutation | `refreshToken` | `/api/auth/refresh` | POST | 🟡 Pendiente |
| 9 | Mutation | `logout` | `/api/auth/logout` | POST | 🟡 Pendiente |
| 10 | Mutation | `revokeOtherSession` | `/api/auth/sessions/{id}` | DELETE | 🟡 Pendiente |
| 11 | Mutation | `resetPassword` | `/api/auth/password-reset` | POST | 🟡 Pendiente |
| 12 | Mutation | `confirmPasswordReset` | `/api/auth/password-reset/confirm` | POST | 🟡 Pendiente |
| 13 | Mutation | `verifyEmail` | `/api/auth/email/verify` | POST | 🟡 Pendiente |
| 14 | Mutation | `resendVerification` | `/api/auth/email/verify/resend` | POST | 🟡 Pendiente |
| 15 | Mutation | `markOnboardingCompleted` | `/api/auth/onboarding/completed` | POST | 🟡 Pendiente |

---

## ✅ CHECKLIST DE PROGRESO

### Fase 1: Setup (2 horas) - 🟢 COMPLETADA
- [x] Instalar L5-Swagger en Docker
- [x] Crear estructura de carpetas Http/Controllers, Http/Requests, Http/Resources
- [x] Crear rutas base en routes/api.php
- [x] Crear Middleware de autenticación JWT (AuthenticateJwt.php)
- [x] Crear Middleware de manejo de excepciones REST (ApiExceptionHandler.php)
- [x] Registrar middlewares en bootstrap/app.php
- [x] Crear OpenApiInfo.php para anotaciones
- [x] Generar documentación Swagger inicial

### Fase 2: Controllers (3 horas) - 🟢 COMPLETADA ✅
- [x] AuthController.php (register, login, loginWithGoogle, refresh, status)
- [x] PasswordResetController.php (store, confirm, status)
- [x] EmailVerificationController.php (verify, resend, status)
- [x] SessionController.php (logout, revoke, index)
- [x] OnboardingController.php (markCompleted)

**Archivos creados:** 5 Controllers (~300 líneas de código)
**Características:** Anotaciones OpenAPI, validación, manejo de cookies, delegación a Services

### Fase 3: Form Requests (2 horas) - 🟢 COMPLETADA ✅
- [x] RegisterRequest.php (7 campos)
- [x] LoginRequest.php (4 campos)
- [x] GoogleLoginRequest.php (1 campo)
- [x] PasswordResetRequest.php (1 campo)
- [x] PasswordResetConfirmRequest.php (4 campos + validación token/code)
- [x] EmailVerifyRequest.php (1 campo)

**Archivos creados:** 6 Form Requests (~150 líneas de código)
**Características:** Validación completa, mensajes personalizados, reglas de seguridad

### Fase 4: API Resources (2 horas) - 🟢 COMPLETADA ✅
- [x] AuthPayloadResource.php
- [x] RefreshPayloadResource.php
- [x] AuthStatusResource.php
- [x] UserAuthInfoResource.php
- [x] RoleContextResource.php
- [x] SessionInfoResource.php
- [x] PasswordResetStatusResource.php
- [x] PasswordResetResultResource.php
- [x] EmailVerificationStatusResource.php
- [x] EmailVerificationResultResource.php
- [x] MarkOnboardingCompletedResource.php

**Archivos creados:** 11 API Resources (~180 líneas de código)
**Características:** Transformación JSON idéntica a GraphQL, manejo de relaciones

### Fase 5: Tests (2 horas) - 🟡 PENDIENTE
- [ ] Adaptar LoginMutationTest → LoginControllerTest
- [ ] Adaptar RegisterMutationTest → RegisterControllerTest
- [ ] Adaptar PasswordResetTest → PasswordResetControllerTest
- [ ] Adaptar EmailVerificationTest → EmailVerificationControllerTest
- [ ] Adaptar SessionTests → SessionControllerTest
- [ ] Ejecutar suite de tests
- [ ] Verificar 9 tests al 100%

### Fase 6: Documentación (1 hora) - 🟡 PENDIENTE
- [ ] Agregar anotaciones PHP Attributes en Controllers
- [ ] Generar docs con `php artisan scribe:generate`
- [ ] Validar en http://localhost:8000/api/docs
- [ ] Verificar OpenAPI spec completo

### Fase 7: Validación Final - 🟡 PENDIENTE
- [ ] Todos 9 tests de Authentication pasando
- [ ] Documentación Swagger accesible
- [ ] Rate limiting funcionando
- [ ] Validación de datos correcta
- [ ] Sistema de errores DEV/PROD funcionando

---

## 🎯 FASES DE IMPLEMENTACIÓN

### FASE 1: Setup (Hoy)
**Objetivo:** Preparar la infraestructura base

**Tareas:**
1. Instalar L5-Swagger en Docker
2. Crear estructura de carpetas
3. Configurar rutas base
4. Setup Middleware de excepciones

**Duración:** 2 horas

**Entrega:** Proyecto listo para agregar controllers

---

### FASE 2-6: Implementación
**Objetivo:** Crear todos los controllers, requests y resources

**Duración:** ~10 horas

**Entrega:** API REST funcional idéntica a GraphQL

---

### FASE 7: Validación
**Objetivo:** Asegurar funcionalidad completa

**Duración:** 2 horas

**Entrega:** 100% tests pasando + documentación completa

---

## 📝 NOTAS TÉCNICAS

### Principios Clave

1. **Services son la Fuente de Verdad**
   - Los Services contienen toda la lógica de negocio
   - Resolvers GraphQL los usaban
   - Controllers REST también los usarán
   - ✅ LÓGICA IDÉNTICA

2. **Excepciones son Reutilizables**
   - Las excepciones de Authentication funcionan igual
   - El middleware maneja la conversión a HTTP status codes
   - ✅ MANEJO IDÉNTICO

3. **API Resources = JSON Transformer**
   - Transforman modelos/objetos a JSON
   - Tienen la misma estructura que GraphQL retornaba
   - ✅ FORMATO IDÉNTICO

4. **Form Requests = Validación Centralizada**
   - Reemplazan `@rules` directives de GraphQL
   - Mismas reglas, mismo formato de errores
   - ✅ VALIDACIÓN IDÉNTICA

### Consideraciones Docker

**Containers afectados:**
- `app` - Ejecuta servicios, colas, middleware
- `nginx` - Sirve rutas `/api/*`
- Otros servicios no afectados (postgres, redis, mailpit)

**Reiniciar después de cambios:**
```bash
docker compose down
docker compose up -d
```

### Testing Strategy

**Cambios mínimos en tests:**
```php
// ANTES (GraphQL)
$response = $this->graphQL('mutation { login(...) { ... } }');
$this->assertTrue($response['data']['login']['accessToken'] !== null);

// DESPUÉS (REST)
$response = $this->postJson('/api/auth/login', [...]);
$this->assertTrue($response['accessToken'] !== null);
```

**Lo que NO cambia:**
- Lógica de las pruebas
- Validaciones de resultado
- Setup de datos
- Llamadas a Services

### Documentación Swagger

**Se genera automáticamente** con anotaciones:

```php
#[OA\Post(
    path: '/api/auth/login',
    summary: 'Login de usuario',
    tags: ['Authentication'],
)]
public function login(LoginRequest $request, AuthService $service)
```

**Resultado:**
- `http://localhost:8000/api/docs` - Swagger UI
- `http://localhost:8000/api/docs.json` - OpenAPI spec

---

## 🔄 FLOW DE ACTUALIZACIÓN DE ESTE DOCUMENTO

Este documento es tu **fuente de verdad** durante la migración.

**Actualizar cada vez que:**
1. ✅ Completes una fase
2. ✅ Descubras un nuevo detalle
3. ✅ Tomes una decisión de arquitectura
4. ✅ Encuentres un problema y su solución

**Formato de actualización:**
```md
### Fase X: [Nombre]
**Status:** 🟢 Completado (Fecha)
**Notas:** Detalles importantes descubiertos
**Problemas resueltos:** Lista de issues
```

---

## 📞 PUNTO DE REFERENCIA

**Última actualización:** 27-Octubre-2025, 12:30 UTC
**Responsable:** Claude Code
**Rama activa:** feature/graphql-to-rest-migration
**Siguiente paso:** Iniciar Fase 1 - Setup

**Links de referencia:**
- 📘 AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt
- 📊 SISTEMA_ERRORES_GRAPHQL_IMPLEMENTADO.md
- 🏗️ GUIA_ESTRUCTURA_CARPETAS_PROYECTO.md

---

---

# 🔍 ANÁLISIS DETALLADO DE RESOLVERS - BLUEPRINT PARA REST

**Este documento mapea CADA resolver GraphQL con exactitud para garantizar migración profesional.**

---

## 📋 QUERIES (4)

### Query 1: authStatus
**Archivo:** `app/Features/Authentication/GraphQL/Queries/AuthStatusQuery.php` (139 líneas)

**Propósito:** Obtener estado actual de autenticación del usuario

**Autenticación:** ✅ Requiere @jwt directive

**Parámetros:** NINGUNO (No acepta argumentos)

**Flujo:**
```
1. Lee Authorization header → Bearer token
2. Llama TokenService.validateAccessToken(accessToken)
3. Obtiene session_id del payload JWT
4. Obtiene Session por session_id de RefreshToken table
5. Carga relaciones: user->profile, user->roleContexts (con DataLoaders)
6. Retorna estructura completa
```

**Service Methods:**
- `TokenService.validateAccessToken($token)` → Returns: token_payload array con claims (user_id, session_id, etc)
- `RefreshToken.where('id', session_id)` → Get current session
- DataLoaders para: profile, roleContexts

**Excepciones:**
- `AuthenticationException` - Token inválido/expirado

**Response Success (HTTP 200):**
```json
{
  "isAuthenticated": true,
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "status": "active",
    "emailVerifiedAt": "2025-01-01T00:00:00Z",
    "onboardingCompletedAt": "2025-01-01T00:00:00Z",
    "profile": {
      "firstName": "John",
      "lastName": "Doe",
      "phoneNumber": "+5491234567890",
      "avatarUrl": "https://..."
    },
    "roleContexts": [
      {
        "roleId": "uuid",
        "roleCode": "admin",
        "roleName": "Administrador",
        "companyId": "uuid",
        "companyCode": "COMP001"
      }
    ]
  },
  "currentSession": {
    "sessionId": "uuid",
    "deviceName": "iPhone 14",
    "ipAddress": "192.168.1.1",
    "userAgent": "Mozilla/5.0...",
    "lastUsedAt": "2025-10-27T12:00:00Z",
    "expiresAt": "2025-11-27T12:00:00Z",
    "isCurrent": true
  },
  "tokenInfo": {
    "expiresIn": 2592000,
    "issuedAt": "2025-10-27T12:00:00Z",
    "tokenType": "Bearer"
  }
}
```

**REST Mapping:**
- **Endpoint:** `GET /api/auth/status`
- **HTTP Status:** 200 (success), 401 (unauthenticated)
- **Middleware:** `auth:api` (AuthenticateJwt)
- **Controller Method:** `AuthController@status()`

---

### Query 2: mySessions
**Archivo:** `app/Features/Authentication/GraphQL/Queries/MySessionsQuery.php` (80 líneas)

**Propósito:** Listar todas las sesiones activas del usuario

**Autenticación:** ✅ Requiere @jwt directive

**Parámetros:** NINGUNO

**Flujo:**
```
1. Lee Authorization header → Bearer token
2. Obtiene user_id del token
3. Query RefreshToken table:
   - WHERE user_id = user_id
   - WHERE revoked_at IS NULL
   - WHERE expires_at > NOW()
   - ORDER BY last_used_at DESC
4. Lee X-Refresh-Token header o refresh_token cookie
5. Compara token_hash para marcar current session
6. Retorna colección de SessionInfo
```

**Service Methods:**
- `TokenService.validateAccessToken($token)` → Get user_id
- Direct Eloquent query: `RefreshToken.where(...)`

**Excepciones:**
- `AuthenticationException` - Token inválido

**Response Success (HTTP 200):**
```json
{
  "sessions": [
    {
      "sessionId": "uuid",
      "deviceName": "iPhone 14",
      "ipAddress": "192.168.1.1",
      "userAgent": "Mozilla/5.0...",
      "lastUsedAt": "2025-10-27T12:00:00Z",
      "expiresAt": "2025-11-27T12:00:00Z",
      "isCurrent": true
    }
  ]
}
```

**REST Mapping:**
- **Endpoint:** `GET /api/auth/sessions`
- **HTTP Status:** 200 (success), 401 (unauthenticated)
- **Middleware:** `auth:api`
- **Controller Method:** `SessionController@index()`

---

### Query 3: passwordResetStatus
**Archivo:** `app/Features/Authentication/GraphQL/Queries/PasswordResetStatusQuery.php` (56 líneas)

**Propósito:** Verificar validez de token de reset de contraseña

**Autenticación:** ❌ NO requiere autenticación

**Parámetros:**
```php
'token' => 'required|string' // Token de reset (32 chars)
```

**Flujo:**
```
1. Recibe $args['token']
2. Llama PasswordResetService.validateResetToken(token)
3. Service retorna token info o lanza exception
4. Retorna resultado
```

**Service Methods:**
- `PasswordResetService.validateResetToken($token)` → Returns: array con {isValid, canReset, email, expiresAt, attemptsRemaining}

**Excepciones:**
- `NotFoundException` - Token no existe
- `HelpdeskException` - Token expirado

**Response Success (HTTP 200):**
```json
{
  "isValid": true,
  "canReset": true,
  "email": "use*****@example.com",
  "expiresAt": "2025-10-28T12:00:00Z",
  "attemptsRemaining": 3
}
```

**REST Mapping:**
- **Endpoint:** `GET /api/auth/password-reset/status?token={token}`
- **HTTP Status:** 200 (success), 404 (token inválido), 410 (expirado)
- **Middleware:** NINGUNO (público)
- **Controller Method:** `PasswordResetController@status()`

---

### Query 4: emailVerificationStatus
**Archivo:** `app/Features/Authentication/GraphQL/Queries/EmailVerificationStatusQuery.php` (76 líneas)

**Propósito:** Obtener estado de verificación de email del usuario autenticado

**Autenticación:** ✅ Requiere @jwt directive

**Parámetros:** NINGUNO

**Flujo:**
```
1. Obtiene user del contexto JWT
2. Llama AuthService.getEmailVerificationStatus(user->id)
3. Service retorna estado detallado
```

**Service Methods:**
- `AuthService.getEmailVerificationStatus($userId)` → Returns: array con {isVerified, email, verificationSentAt, canResend, resendAvailableAt, attemptsRemaining}

**Excepciones:**
- `AuthenticationException` - Usuario no autenticado

**Response Success (HTTP 200):**
```json
{
  "isVerified": false,
  "email": "user@example.com",
  "verificationSentAt": "2025-10-27T12:00:00Z",
  "canResend": true,
  "resendAvailableAt": null,
  "attemptsRemaining": 5
}
```

**REST Mapping:**
- **Endpoint:** `GET /api/auth/email/status`
- **HTTP Status:** 200 (success), 401 (unauthenticated)
- **Middleware:** `auth:api`
- **Controller Method:** `EmailVerificationController@status()`

---

## 🔄 MUTATIONS (11)

### Mutation 1: register
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/RegisterMutation.php` (204 líneas)

**Propósito:** Registrar nuevo usuario

**Autenticación:** ❌ NO requiere autenticación

**Input Parameters:**
```php
[
    'email' => 'required|email|unique',
    'password' => 'required|min:8|confirmed',
    'passwordConfirmation' => 'required',
    'firstName' => 'required|string|max:255',
    'lastName' => 'required|string|max:255',
    'acceptsTerms' => 'required|boolean|accepted',
    'acceptsPrivacyPolicy' => 'required|boolean|accepted'
]
```

**Validaciones:**
- Email: formato válido, no existente en DB
- Password: mínimo 8 caracteres
- Confirmación: debe coincidir con password
- FirstName/LastName: no HTML, máximo 255 chars
- Terms/Privacy: debe ser true

**Device Info Extraction:**
```php
DeviceInfoParser::parse($context) → {
    'deviceName' => User-Agent parsed,
    'ipAddress' => client IP,
    'userAgent' => Raw User-Agent
}
```

**Flujo:**
```
1. Valida input (camelCase → snake_case)
2. Sanitiza nombres (capitalize, strip HTML)
3. Extrae device info del contexto
4. Llama AuthService.register(input, deviceInfo)
5. Service retorna AuthPayload con tokens
6. Set refresh token en HttpOnly cookie
7. Retorna respuesta
```

**Service Methods:**
- `AuthService.register($input, $deviceInfo)` → Returns: AuthPayload {accessToken, refreshToken, tokenType, expiresIn, user, sessionId, loginTimestamp}

**Excepciones:**
- `ValidationException` - Datos inválidos (422)
- `ConflictException` - Email ya existe (409)
- `HelpdeskException` - Error durante registro (500)

**Response Success (HTTP 201):**
```json
{
  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "Refresh token set in httpOnly cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "profile": {
      "firstName": "John",
      "lastName": "Doe"
    }
  },
  "sessionId": "uuid",
  "loginTimestamp": "2025-10-27T12:00:00Z"
}
```

**REST Mapping:**
- **Endpoint:** `POST /api/auth/register`
- **HTTP Status:** 201 (created), 422 (validation), 409 (conflict)
- **Middleware:** NINGUNO (público)
- **Form Request:** `RegisterRequest`
- **Controller Method:** `AuthController@register()`
- **Cookie Set:** `refresh_token` (HttpOnly, Secure, SameSite=Lax)

---

### Mutation 2: login
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/LoginMutation.php` (172 líneas)

**Propósito:** Login de usuario existente

**Autenticación:** ❌ NO requiere autenticación

**Input Parameters:**
```php
[
    'email' => 'required|email',
    'password' => 'required|min:8',
    'rememberMe' => 'optional|boolean',
    'deviceName' => 'optional|string|max:255'
]
```

**Device Info Extraction:**
```php
DeviceInfoParser::parse($context) → {
    'deviceName' => deviceName arg OR User-Agent parsed,
    'ipAddress' => client IP,
    'userAgent' => Raw User-Agent
}
```

**Flujo:**
```
1. Valida input
2. Extrae device info
3. Llama AuthService.login(email, password, deviceInfo)
4. Service valida credenciales, crea sesión, retorna tokens
5. Set refresh token en HttpOnly cookie
6. Retorna AuthPayload
```

**Service Methods:**
- `AuthService.login($email, $password, $deviceInfo)` → Returns: AuthPayload {accessToken, refreshToken, tokenType, expiresIn, user, sessionId, loginTimestamp}

  **Internamente:**
  - Valida credenciales
  - Crea RefreshToken entry
  - Genera JWT access token
  - Retorna tokens

**Excepciones:**
- `ValidationException` - Email o password inválidos (422)
- `AuthenticationException` - Credenciales incorrectas (401)
- `HelpdeskException` - Error en login (500)

**Response Success (HTTP 200):**
```json
{
  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "Refresh token set in httpOnly cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "emailVerifiedAt": "2025-01-01T00:00:00Z",
    "profile": {
      "firstName": "John",
      "lastName": "Doe",
      "avatarUrl": "https://..."
    }
  },
  "sessionId": "uuid",
  "loginTimestamp": "2025-10-27T12:00:00Z"
}
```

**REST Mapping:**
- **Endpoint:** `POST /api/auth/login`
- **HTTP Status:** 200 (success), 422 (validation), 401 (auth failed)
- **Middleware:** NINGUNO (público)
- **Form Request:** `LoginRequest`
- **Controller Method:** `AuthController@login()`
- **Cookie Set:** `refresh_token` (HttpOnly, Secure, SameSite=Lax)

---

### Mutation 3: loginWithGoogle
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/GoogleLoginMutation.php` (14 líneas)

**Propósito:** Login con Google OAuth

**Autenticación:** ❌ NO requiere autenticación

**Status:** 🟡 STUB - Aún no implementado

**Input Parameters:**
```php
[
    'googleToken' => 'required|string' // Google ID token
]
```

**Flujo (Planned):**
```
1. Valida Google token
2. Extrae email + datos de Google
3. Encuentra o crea usuario
4. Crea sesión + tokens
5. Retorna AuthPayload
```

**Service Methods (TBD):**
- `AuthService.loginWithGoogle($googleToken, $deviceInfo)` → Returns: AuthPayload

**Excepciones (Planned):**
- `AuthenticationException` - Token inválido

**REST Mapping:**
- **Endpoint:** `POST /api/auth/login/google`
- **HTTP Status:** 200 (success), 401 (token inválido)
- **Middleware:** NINGUNO (público)
- **Form Request:** `GoogleLoginRequest`
- **Controller Method:** `AuthController@loginWithGoogle()`
- **Status:** 🟡 Implementar en próxima fase

---

### Mutation 4: refreshToken
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/RefreshTokenMutation.php` (98 líneas)

**Propósito:** Renovar access token expirado usando refresh token

**Autenticación:** ❌ NO requiere autenticación (access token puede estar expirado!)

**Input Parameters:**
```php
[
    'refreshToken' => 'optional|string' // Para Apollo Studio
]
```

**Refresh Token Sources (Priority Order):**
```
1. X-Refresh-Token header (más seguro)
2. refresh_token cookie (para web)
3. refreshToken en body (para Apollo)
```

**Device Info Extraction:**
```php
DeviceInfoParser::parse($context) → {
    'deviceName' => User-Agent parsed,
    'ipAddress' => client IP,
    'userAgent' => Raw User-Agent
}
```

**Flujo:**
```
1. Busca refresh token en 3 fuentes
2. Lanza RefreshTokenRequiredException si no encuentra
3. Extrae device info
4. Llama AuthService.refreshToken(token, deviceInfo)
5. Service valida token, genera nuevo access token
6. Set nuevo refresh token en HttpOnly cookie
7. Retorna RefreshPayload
```

**Service Methods:**
- `AuthService.refreshToken($refreshToken, $deviceInfo)` → Returns: RefreshPayload {accessToken, refreshToken, tokenType, expiresIn}

  **Internamente:**
  - Valida refresh token
  - Obtiene user_id del token
  - Genera nuevo access token
  - Opcionalmente genera nuevo refresh token
  - Retorna tokens

**Excepciones:**
- `RefreshTokenRequiredException` - No hay refresh token (401)
- `AuthenticationException` - Token inválido/expirado (401)
- `TokenBlacklistedException` - Token fue revocado (401)

**Response Success (HTTP 200):**
```json
{
  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "New token set in httpOnly cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000
}
```

**REST Mapping:**
- **Endpoint:** `POST /api/auth/refresh`
- **HTTP Status:** 200 (success), 401 (token inválido)
- **Middleware:** NINGUNO (público - cualquiera puede renovar)
- **Form Request:** NINGUNO (sin validación de datos)
- **Controller Method:** `AuthController@refresh()`
- **Cookie Set:** `refresh_token` (HttpOnly, Secure, SameSite=Lax)
- **Headers:** Leer `X-Refresh-Token` si existe
- **Special:** NO requiere Auth middleware

---

### Mutation 5: logout
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/LogoutMutation.php` (115 líneas)

**Propósito:** Logout de sesión actual o todas las sesiones

**Autenticación:** ✅ Requiere @jwt directive

**Input Parameters:**
```php
[
    'everywhere' => 'optional|boolean' // Default: false
]
```

**Flujo:**
```
1. Obtiene user del contexto JWT
2. Lee X-Refresh-Token header o refresh_token cookie
3. Si everywhere=true:
   - Llama logoutAllDevices(user->id)
   - Revoca todas las sesiones
4. Si everywhere=false:
   - Obtiene session_id actual del JWT
   - Llama logout(accessToken, refreshToken, user->id)
   - Revoca solo la sesión actual
5. Clear refresh token cookie
6. Retorna true
```

**Service Methods:**
- `AuthService.logoutAllDevices($userId)` → Returns: void
  - Revoca todas las sesiones del usuario
  - Agrega todos los tokens a blacklist

- `AuthService.logout($accessToken, $refreshToken, $userId)` → Returns: void
  - Revoca sesión actual
  - Agrega tokens a blacklist

**Excepciones:**
- `AuthenticationException` - Usuario no autenticado

**Response Success (HTTP 200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

**REST Mapping:**
- **Endpoint:** `POST /api/auth/logout`
- **HTTP Status:** 200 (success), 401 (unauthenticated)
- **Middleware:** `auth:api`
- **Form Request:** `LogoutRequest` (opcional, para everywhere param)
- **Controller Method:** `SessionController@logout()`
- **Cookie Clear:** `refresh_token` (set max-age=0)
- **Query Param:** `everywhere=1` para logout de todas las sesiones

---

### Mutation 6: revokeOtherSession
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/RevokeOtherSessionMutation.php` (95 líneas)

**Propósito:** Revocar sesión específica de otro dispositivo

**Autenticación:** ✅ Requiere @jwt directive

**Input Parameters:**
```php
[
    'sessionId' => 'required|string|uuid' // Session ID a revocar
]
```

**Validaciones:**
- sessionId: debe ser UUID válido
- Must belong to user (no puede revocar sesiones ajenas)
- Cannot revoke current session (lanza CannotRevokeCurrentSessionException)

**Flujo:**
```
1. Obtiene user del contexto JWT
2. Obtiene session_id actual del JWT
3. Valida que sessionId sea diferente al actual
4. Obtiene RefreshToken por sessionId
5. Valida que pertenezca al user
6. Llama TokenService.blacklistToken(sessionId)
7. Llama session.revoke(user->id)
8. Retorna true
```

**Service Methods:**
- `TokenService.blacklistToken($sessionId)` → Returns: void
  - Agrega token a blacklist
  - Previene su uso futuro

- `RefreshToken.revoke($userId)` → Returns: void
  - Establece revoked_at timestamp

**Excepciones:**
- `AuthenticationException` - No autenticado
- `NotFoundException` - Session no existe (404)
- `AuthorizationException` - No pertenece al user (403)
- `CannotRevokeCurrentSessionException` - Intenta revocar sesión actual (409)

**Response Success (HTTP 200):**
```json
{
  "success": true,
  "message": "Session revoked successfully"
}
```

**REST Mapping:**
- **Endpoint:** `DELETE /api/auth/sessions/{sessionId}`
- **HTTP Status:** 200 (success), 401 (unauthenticated), 404 (not found), 403 (forbidden)
- **Middleware:** `auth:api`
- **Form Request:** NINGUNO
- **Controller Method:** `SessionController@revoke()`
- **Route Param:** `sessionId` (UUID)

---

### Mutation 7: resetPassword
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/ResetPasswordMutation.php` (46 líneas)

**Propósito:** Solicitar reset de contraseña (envía email)

**Autenticación:** ❌ NO requiere autenticación

**Input Parameters:**
```php
[
    'email' => 'required|email'
]
```

**Flujo:**
```
1. Valida email
2. Llama PasswordResetService.requestReset(email)
3. Service envía email con token (asincrónico vía queue)
4. SIEMPRE retorna true (no revela si email existe)
```

**Service Methods:**
- `PasswordResetService.requestReset($email)` → Returns: true
  - Crea PasswordReset entry en DB
  - Dispara evento que envía email (vía queue)
  - Nota: No revela si email no existe en DB

**Excepciones:**
- NINGUNA (por diseño, siempre retorna success)

**Response Success (HTTP 200):**
```json
{
  "success": true,
  "message": "Si el email existe en nuestro sistema, recibirás un enlace para resetear tu contraseña."
}
```

**REST Mapping:**
- **Endpoint:** `POST /api/auth/password-reset`
- **HTTP Status:** 200 (siempre, por seguridad)
- **Middleware:** NINGUNO (público)
- **Form Request:** `PasswordResetRequest`
- **Controller Method:** `PasswordResetController@store()`
- **Security Note:** Retorna success siempre, no revela si email existe

---

### Mutation 8: confirmPasswordReset
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/ConfirmPasswordResetMutation.php` (117 líneas)

**Propósito:** Confirmar reset de contraseña con nueva contraseña

**Autenticación:** ❌ NO requiere autenticación

**Input Parameters:**
```php
[
    // OPCIÓN 1: Token (32 caracteres)
    'token' => 'nullable|string|size:32',

    // OPCIÓN 2: Code (6 dígitos)
    'code' => 'nullable|string|regex:/^\d{6}$/',

    // AMBOS CASOS:
    'password' => 'required|string|min:8',
    'passwordConfirmation' => 'required|string|confirmed'
]
```

**Validaciones:**
- Password: mínimo 8 caracteres
- Confirmación: debe coincidir
- Debe incluir token O code (no ambos)

**Device Info Extraction:**
```php
DeviceInfoParser::parse($context) → device info
```

**Flujo:**
```
1. Valida input
2. Valida passwords
3. Si usa token:
   - Llama PasswordResetService.confirmReset(token, password, deviceInfo)
4. Si usa code:
   - Llama PasswordResetService.confirmResetWithCode(code, password, deviceInfo)
5. Service retorna user con tokens
6. Set refresh token cookie
7. Retorna resultado
```

**Service Methods:**
- `PasswordResetService.confirmReset($token, $password, $deviceInfo)` → Returns: array {user, accessToken, refreshToken, ...}
  - Valida token
  - Cambia password
  - Crea sesión + tokens
  - Limpia token de DB

- `PasswordResetService.confirmResetWithCode($code, $password, $deviceInfo)` → Returns: array {user, accessToken, refreshToken, ...}
  - Igual pero con code (6 dígitos)

**Excepciones:**
- `ValidationException` - Datos inválidos (422)
- `NotFoundException` - Token/code inválido (404)
- `AuthenticationException` - Token expirado (401)

**Response Success (HTTP 200):**
```json
{
  "success": true,
  "message": "Contraseña reseteada correctamente. Sesión iniciada automáticamente.",
  "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "Token set in httpOnly cookie",
  "user": {
    "id": "uuid",
    "email": "user@example.com"
  }
}
```

**REST Mapping:**
- **Endpoint:** `POST /api/auth/password-reset/confirm`
- **HTTP Status:** 200 (success), 422 (validation), 404 (not found), 401 (expired)
- **Middleware:** NINGUNO (público)
- **Form Request:** `PasswordResetConfirmRequest`
- **Controller Method:** `PasswordResetController@confirm()`
- **Cookie Set:** `refresh_token` (HttpOnly, Secure, SameSite=Lax)

---

### Mutation 9: verifyEmail
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/VerifyEmailMutation.php` (86 líneas)

**Propósito:** Verificar email del usuario

**Autenticación:** ❌ NO requiere autenticación (token identifica al usuario)

**Input Parameters:**
```php
[
    'token' => 'required|string' // Email verification token
]
```

**Flujo:**
```
1. Valida token
2. Llama AuthService.verifyEmail(token)
3. Service valida token, marca email como verificado
4. Retorna resultado
5. Si falla con AuthenticationException, retorna success=false
   (no throws - compatible con clientes)
```

**Service Methods:**
- `AuthService.verifyEmail($token)` → Returns: void
  - Obtiene usuario por token
  - Establece email_verified_at timestamp
  - Limpia token de DB
  - Dispara UserEmailVerifiedEvent

**Excepciones Capturadas:**
- `AuthenticationException` - Token inválido → Retorna success=false (no throw)

**Response Success (HTTP 200):**
```json
{
  "success": true,
  "message": "Email verificado correctamente."
}
```

**Response Error (HTTP 200, pero success=false):**
```json
{
  "success": false,
  "message": "El token de verificación es inválido o ha expirado.",
  "canResend": true,
  "resendAvailableAt": null
}
```

**REST Mapping:**
- **Endpoint:** `POST /api/auth/email/verify`
- **HTTP Status:** 200 (siempre, tanto success como error)
- **Middleware:** NINGUNO (público)
- **Form Request:** `EmailVerifyRequest`
- **Controller Method:** `EmailVerificationController@verify()`
- **Special:** Retorna 200 incluso si falla, pero con success=false

---

### Mutation 10: resendVerification
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/ResendVerificationMutation.php` (100 líneas)

**Propósito:** Reenviar email de verificación

**Autenticación:** ✅ Requiere @jwt directive

**Rate Limiting:** ✅ @rateLimit(3 cada 5 minutos)

**Input Parameters:** NINGUNO

**Flujo:**
```
1. Obtiene user del contexto JWT
2. Valida que no esté verificado
3. Llama AuthService.resendEmailVerification(user->id)
4. Service envía email (asincrónico)
5. Retorna resultado con canResend=false + resendAvailableAt
```

**Service Methods:**
- `AuthService.resendEmailVerification($userId)` → Returns: token
  - Valida que email no esté verificado
  - Genera nuevo token
  - Envía email vía queue
  - Retorna token (para testing)

**Excepciones:**
- `AuthenticationException` - No autenticado
- Silenciosamente retorna success=false si ya verificado

**Response Success (HTTP 200):**
```json
{
  "success": true,
  "message": "Email de verificación enviado correctamente. Revisa tu bandeja de entrada.",
  "canResend": false,
  "resendAvailableAt": "2025-10-27T12:05:00Z"
}
```

**Response Already Verified (HTTP 200):**
```json
{
  "success": false,
  "message": "El email ya está verificado",
  "canResend": false,
  "resendAvailableAt": null
}
```

**REST Mapping:**
- **Endpoint:** `POST /api/auth/email/verify/resend`
- **HTTP Status:** 200 (siempre)
- **Middleware:** `auth:api` + Rate Limit (3 cada 5 minutos)
- **Form Request:** NINGUNO
- **Controller Method:** `EmailVerificationController@resend()`
- **Rate Limit:** Implementar con Middleware o Throttle

---

### Mutation 11: markOnboardingCompleted
**Archivo:** `app/Features/Authentication/GraphQL/Mutations/MarkOnboardingCompletedMutation.php` (94 líneas)

**Propósito:** Marcar onboarding como completado

**Autenticación:** ✅ Requiere @jwt directive

**Input Parameters:** NINGUNO

**Flujo:**
```
1. Obtiene user del contexto JWT
2. Si onboarding_completed_at ya existe:
   - Retorna success=true sin cambios
3. Si no existe:
   - Establece onboarding_completed_at = NOW()
   - Guarda user
   - Dispara UserOnboardingCompletedEvent
4. Retorna resultado con user actualizado
```

**Service Methods:** NINGUNO (Lógica directa en Mutation)
- Lógica simple: `$user->onboarding_completed_at = now(); $user->save();`

**Excepciones:**
- `AuthenticationException` - No autenticado

**Response Success (HTTP 200):**
```json
{
  "success": true,
  "message": "Onboarding completado exitosamente",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "onboardingCompletedAt": "2025-10-27T12:00:00Z"
  }
}
```

**REST Mapping:**
- **Endpoint:** `POST /api/auth/onboarding/completed`
- **HTTP Status:** 200 (success), 401 (unauthenticated)
- **Middleware:** `auth:api`
- **Form Request:** NINGUNO
- **Controller Method:** `OnboardingController@markCompleted()`

---

## 📊 RESUMEN EJECUTIVO - MAPEO COMPLETO

| # | GraphQL | REST | Auth | Rate Limit | Input | Output |
|---|---------|------|------|-----------|-------|--------|
| 1 | authStatus Query | GET /api/auth/status | ✅ JWT | ❌ | - | AuthStatusResource |
| 2 | mySessions Query | GET /api/auth/sessions | ✅ JWT | ❌ | - | SessionInfoCollection |
| 3 | passwordResetStatus Query | GET /api/auth/password-reset/status | ❌ | ❌ | token | PasswordResetStatusResource |
| 4 | emailVerificationStatus Query | GET /api/auth/email/status | ✅ JWT | ❌ | - | EmailVerificationStatusResource |
| 5 | register Mutation | POST /api/auth/register | ❌ | ❌ | RegisterRequest | AuthPayloadResource |
| 6 | login Mutation | POST /api/auth/login | ❌ | ❌ | LoginRequest | AuthPayloadResource |
| 7 | loginWithGoogle Mutation | POST /api/auth/login/google | ❌ | ❌ | GoogleLoginRequest | AuthPayloadResource |
| 8 | refreshToken Mutation | POST /api/auth/refresh | ❌ | ❌ | - (headers) | RefreshPayloadResource |
| 9 | logout Mutation | POST /api/auth/logout | ✅ JWT | ❌ | everywhere? | {success: true} |
| 10 | revokeOtherSession Mutation | DELETE /api/auth/sessions/{id} | ✅ JWT | ❌ | - | {success: true} |
| 11 | resetPassword Mutation | POST /api/auth/password-reset | ❌ | ❌ | email | {success: true} |
| 12 | confirmPasswordReset Mutation | POST /api/auth/password-reset/confirm | ❌ | ❌ | PasswordResetConfirmRequest | PasswordResetResultResource |
| 13 | verifyEmail Mutation | POST /api/auth/email/verify | ❌ | ❌ | token | EmailVerificationResultResource |
| 14 | resendVerification Mutation | POST /api/auth/email/verify/resend | ✅ JWT | ✅ (3/5m) | - | EmailVerificationResultResource |
| 15 | markOnboardingCompleted Mutation | POST /api/auth/onboarding/completed | ✅ JWT | ❌ | - | MarkOnboardingCompletedResource |

---

## 🔗 SERVICIOS REUTILIZABLES (NO CAMBIAN)

Todos estos servicios se usan IDÉNTICAMENTE en REST:

1. **AuthService**
   - `register($input, $deviceInfo)`
   - `login($email, $password, $deviceInfo)`
   - `logout($accessToken, $refreshToken, $userId)`
   - `logoutAllDevices($userId)`
   - `refreshToken($token, $deviceInfo)`
   - `verifyEmail($token)`
   - `resendEmailVerification($userId)`
   - `getEmailVerificationStatus($userId)`

2. **TokenService**
   - `validateAccessToken($token)`
   - `generateTokens($userId, $sessionId)`
   - `blacklistToken($sessionId)`

3. **PasswordResetService**
   - `requestReset($email)`
   - `validateResetToken($token)`
   - `confirmReset($token, $password, $deviceInfo)`
   - `confirmResetWithCode($code, $password, $deviceInfo)`

4. **DeviceInfoParser**
   - `parse($context)` → {deviceName, ipAddress, userAgent}

---

## 🎯 HTTP STATUS CODES MAPEADOS

| Exception | HTTP | Descripción |
|-----------|------|------------|
| ValidationException | 422 | Datos inválidos |
| AuthenticationException | 401 | Token inválido/expirado, credenciales incorrectas |
| AuthorizationException | 403 | Usuario no autorizado |
| NotFoundException | 404 | Recurso no existe |
| ConflictException | 409 | Email ya existe, no puede revocar sesión actual |
| RateLimitExceededException | 429 | Rate limit excedido |
| Generic Exception | 500 | Error del servidor |

---

**DOCUMENTO LISTO PARA FASE 2: IMPLEMENTACIÓN**

Todos los Controllers, Form Requests y Resources tienen un blueprint exacto en este documento.

*Actualizado: 27-Octubre-2025*
