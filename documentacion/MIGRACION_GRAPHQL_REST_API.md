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

### Fase 2: Controllers (3 horas) - 🟡 PENDIENTE
- [ ] AuthController.php (register, login, loginWithGoogle, refresh, status)
- [ ] PasswordResetController.php (store, confirm, status)
- [ ] EmailVerificationController.php (verify, resend, status)
- [ ] SessionController.php (logout, revoke, index)
- [ ] OnboardingController.php (markCompleted)

### Fase 3: Form Requests (2 horas) - 🟡 PENDIENTE
- [ ] RegisterRequest.php
- [ ] LoginRequest.php
- [ ] GoogleLoginRequest.php
- [ ] PasswordResetRequest.php (confirmar reset)
- [ ] EmailVerifyRequest.php
- [ ] LogoutRequest.php

### Fase 4: API Resources (2 horas) - 🟡 PENDIENTE
- [ ] AuthPayloadResource.php
- [ ] RefreshPayloadResource.php
- [ ] AuthStatusResource.php
- [ ] UserAuthInfoResource.php
- [ ] RoleContextResource.php
- [ ] SessionInfoResource.php + Collection
- [ ] PasswordResetStatusResource.php
- [ ] PasswordResetResultResource.php
- [ ] EmailVerificationStatusResource.php
- [ ] EmailVerificationResultResource.php
- [ ] MarkOnboardingCompletedResource.php

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

**Fin del documento de referencia**

*Este archivo debe mantenerse actualizado durante todo el proceso de migración.*
