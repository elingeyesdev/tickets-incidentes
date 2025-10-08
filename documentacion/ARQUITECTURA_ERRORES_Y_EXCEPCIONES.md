# ARQUITECTURA DE ERRORES Y EXCEPCIONES - Guía Profesional

## Principio Fundamental: Separación por Responsabilidad

La arquitectura de errores en este proyecto sigue el **principio de Feature-First PURO**, pero con una excepción importante: **la infraestructura de manejo de errores va en Shared**.

---

## 🎯 REGLA DE ORO

```
┌─────────────────────────────────────────────────────────────┐
│  INFRAESTRUCTURA (cómo mostrar errores) → Shared/           │
│  EXCEPCIONES DE NEGOCIO (qué errores) → Feature/ o Shared/  │
└─────────────────────────────────────────────────────────────┘
```

---

## 1. ERROR HANDLERS (SIEMPRE en Shared)

**Ubicación:** `app/Shared/GraphQL/Errors/`

Los **Error Handlers** son **infraestructura cross-cutting** que afectan a **TODA la aplicación GraphQL**. Son como "middleware" que procesan errores globalmente.

### ✅ Qué va aquí:

```
app/Shared/GraphQL/Errors/
├── CustomValidationErrorHandler.php      ← Limpia errores de validación
├── CustomAuthenticationErrorHandler.php  ← Formatea errores de autenticación
├── CustomAuthorizationErrorHandler.php   ← Formatea errores de permisos
└── GraphQLErrorFormatter.php             ← Formateador genérico
```

### ✅ ¿Reutilizable? **SÍ - Por TODOS los features**

Un solo `CustomValidationErrorHandler` procesa **TODOS** los errores de validación de:
- `Authentication/Mutations/RegisterMutation`
- `UserManagement/Mutations/UpdateUserMutation`
- `CompanyManagement/Mutations/CreateCompanyMutation`
- **Cualquier mutation/query futura**

### ✅ ¿Escalable? **SÍ - Patrón Chain of Responsibility**

Los handlers se ejecutan en cadena (configurados en `config/lighthouse.php`):

```php
'error_handlers' => [
    AuthenticationErrorHandler::class,     // 1. Procesa errores de auth
    AuthorizationErrorHandler::class,      // 2. Procesa errores de permisos
    CustomValidationErrorHandler::class,   // 3. Procesa errores de validación
    ReportingErrorHandler::class,          // 4. Loguea todos los demás
],
```

Cada handler:
- Procesa su tipo de error
- Pasa al siguiente si no es su responsabilidad
- **NO modifica errores de otros handlers**

### ✅ ¿Profesional? **SÍ - Sigue GraphQL Spec**

- Oculta información sensible en producción
- Mensajes user-friendly
- Estructura estándar de GraphQL errors
- Logging automático para debugging

---

## 2. EXCEPCIONES COMPARTIDAS (Shared)

**Ubicación:** `app/Shared/Exceptions/`

### ✅ Qué va aquí:

**SOLO excepciones GENÉRICAS usadas por MÚLTIPLES features:**

```
app/Shared/Exceptions/
├── ValidationException.php        ← Errores de validación genéricos
├── UnauthorizedException.php      ← Usuario no autenticado
├── ForbiddenException.php         ← Sin permisos
├── NotFoundException.php          ← Recurso no encontrado
├── ConflictException.php          ← Conflicto (ej: email duplicado)
└── RateLimitExceededException.php ← Demasiadas peticiones
```

### ❌ Qué NO va aquí:

**Excepciones específicas de dominio de negocio:**

```
❌ app/Shared/Exceptions/InvalidCredentialsException.php
   ✅ Mejor en: app/Features/Authentication/Exceptions/

❌ app/Shared/Exceptions/CompanyAlreadyExistsException.php
   ✅ Mejor en: app/Features/CompanyManagement/Exceptions/

❌ app/Shared/Exceptions/TicketAlreadyClosedException.php
   ✅ Mejor en: app/Features/TicketManagement/Exceptions/
```

### Ejemplo de uso:

```php
// EN CUALQUIER FEATURE - Usar excepción compartida
use App\Shared\Exceptions\NotFoundException;

public function deleteUser(string $userId): void
{
    $user = User::find($userId);

    if (!$user) {
        throw new NotFoundException('User not found');
    }

    $user->delete();
}
```

---

## 3. EXCEPCIONES ESPECÍFICAS DE FEATURE

**Ubicación:** `app/Features/{Feature}/Exceptions/`

### ✅ Qué va aquí:

**Excepciones del DOMINIO DE NEGOCIO específicas del feature:**

```
app/Features/Authentication/Exceptions/
├── InvalidCredentialsException.php       ← Email/password incorrectos
├── EmailNotVerifiedException.php         ← Email no verificado
├── AccountSuspendedException.php         ← Cuenta suspendida
└── PasswordExpiredException.php          ← Contraseña expirada

app/Features/CompanyManagement/Exceptions/
├── CompanyAlreadyExistsException.php     ← Empresa ya existe
├── CompanyNotActiveException.php         ← Empresa inactiva
└── MaxCompaniesReachedException.php      ← Límite de empresas alcanzado

app/Features/TicketManagement/Exceptions/
├── TicketAlreadyClosedException.php      ← Ticket ya cerrado
├── TicketNotAssignedException.php        ← Ticket sin asignar
└── InvalidTicketTransitionException.php  ← Transición de estado inválida
```

### Ejemplo de uso:

```php
// EN AUTHENTICATION FEATURE
namespace App\Features\Authentication\Services;

use App\Features\Authentication\Exceptions\InvalidCredentialsException;
use App\Features\Authentication\Exceptions\EmailNotVerifiedException;

class AuthService
{
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        // Excepción específica del dominio Authentication
        if (!$user || !Hash::check($credentials['password'], $user->password_hash)) {
            throw new InvalidCredentialsException('Invalid email or password');
        }

        // Otra excepción específica del dominio Authentication
        if (!$user->email_verified) {
            throw new EmailNotVerifiedException('Please verify your email first');
        }

        return $this->generateTokens($user);
    }
}
```

---

## 4. RESUMEN: ¿Dónde poner cada cosa?

### ERROR HANDLERS (Infraestructura)

| Tipo | Ubicación | Reutilizable | Ejemplo |
|------|-----------|--------------|---------|
| Validation Handler | `Shared/GraphQL/Errors/` | ✅ Por TODOS | `CustomValidationErrorHandler` |
| Auth Handler | `Shared/GraphQL/Errors/` | ✅ Por TODOS | `CustomAuthenticationErrorHandler` |
| Generic Handler | `Shared/GraphQL/Errors/` | ✅ Por TODOS | `GraphQLErrorFormatter` |

### EXCEPCIONES (Lógica de Negocio)

| Tipo | Ubicación | Cuándo usarla | Ejemplo |
|------|-----------|---------------|---------|
| Genérica cross-cutting | `Shared/Exceptions/` | Usada por 3+ features | `NotFoundException` |
| Dominio específico | `Feature/Exceptions/` | Solo un feature | `InvalidCredentialsException` |
| Validación Laravel | Usar `ValidationException` | Reglas @rules | Ya existe en Shared |

---

## 5. BENEFICIOS DE ESTA ARQUITECTURA

### ✅ Reutilizable
- Un solo handler para TODOS los errores de validación
- Excepciones compartidas evitan duplicación

### ✅ Escalable
- Agregar nuevo feature: solo creas excepciones específicas
- Handlers ya funcionan automáticamente
- Chain of Responsibility permite agregar handlers sin modificar existentes

### ✅ Profesional
- Separación clara: infraestructura vs dominio
- Errores user-friendly en frontend
- Logs detallados en backend
- Cumple GraphQL spec

### ✅ Mantenible
- Feature-First: excepciones de negocio con su feature
- Shared: solo infraestructura y excepciones MUY genéricas
- Fácil encontrar dónde está cada cosa

---

## 6. EJEMPLO COMPLETO: Flujo de un Error

### Escenario: Usuario intenta registrarse con email duplicado

```
1. RegisterMutation valida con @rules
   ↓
2. Laravel detecta email duplicado
   ↓
3. Lighthouse lanza ValidationException (Shared)
   ↓
4. CustomValidationErrorHandler (Shared) intercepta
   ↓
5. Limpia campo: "input.email" → "email"
   ↓
6. Quita file/line/trace
   ↓
7. Frontend recibe JSON limpio:
   {
     "message": "Validation error",
     "extensions": {
       "validation": {
         "email": ["The email has already been taken."]
       }
     }
   }
```

---

## 7. CHECKLIST: ¿Dónde poner mi excepción?

```
┌─ ¿Es un ERROR HANDLER (formatea errores)?
│  └─ ✅ SÍ → Shared/GraphQL/Errors/
│  └─ ❌ NO → Continuar
│
├─ ¿Es una EXCEPCIÓN?
│  ├─ ¿La usarán 3+ features diferentes?
│  │  └─ ✅ SÍ → Shared/Exceptions/
│  │  └─ ❌ NO → Continuar
│  │
│  └─ ¿Es específica del dominio de negocio de UN feature?
│     └─ ✅ SÍ → Features/{Feature}/Exceptions/
```

---

## 8. SIGUIENTE PASO: Implementar Handlers Faltantes

Cuando necesites manejar otros tipos de errores:

```php
// app/Shared/GraphQL/Errors/CustomAuthenticationErrorHandler.php
// Para formatear errores de autenticación (401)

// app/Shared/GraphQL/Errors/CustomAuthorizationErrorHandler.php
// Para formatear errores de permisos (403)

// app/Features/Authentication/Exceptions/InvalidCredentialsException.php
// Para login fallido (específico de Authentication)
```

---

**Resumen:**
- **Handlers** = Infraestructura = Shared (reutilizable por TODOS)
- **Excepciones genéricas** = Shared (si 3+ features las usan)
- **Excepciones de dominio** = Feature (específicas de lógica de negocio)
