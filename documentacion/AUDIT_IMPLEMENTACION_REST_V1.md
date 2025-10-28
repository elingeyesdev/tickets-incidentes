# 🔍 AUDITORÍA DE IMPLEMENTACIÓN REST - FASE 2-4

**Fecha:** 27-Oct-2025
**Status:** ❌ HALLAZGOS CRÍTICOS ENCONTRADOS
**Acción:** CORRECCIONES REQUERIDAS ANTES DE FASE 5

---

## 📋 RESUMEN EJECUTIVO

Se encontraron **8 problemas críticos** y **3 warnings** que impiden que la implementación funcione correctamente.

| Severidad | Cantidad | Estado |
|-----------|----------|--------|
| 🔴 CRÍTICOS | 8 | Requieren corrección inmediata |
| 🟡 WARNINGS | 3 | Mejoras recomendadas |
| ✅ CORRECTOS | 12 | Implementados correctamente |

---

## 🔴 PROBLEMAS CRÍTICOS

### CRÍTICO #1: AuthController.register() - Transformación de datos camelCase → snake_case

**Ubicación:** `app/Features/Authentication/Http/Controllers/AuthController.php:77-80`

**Problema:**
```php
$payload = $this->authService->register(
    $request->validated(),  // ❌ RETORNA camelCase
    $deviceInfo
);
```

**Análisis:**
- FormRequest retorna: `['email' => '...', 'firstName' => '...', 'lastName' => '...']` (camelCase)
- AuthService.register() espera: `['email' => '...', 'first_name' => '...', 'last_name' => '...']` (snake_case)
- **Resultado:** Error en AuthService - campos no encontrados

**Solución:**
Transformar datos a snake_case:
```php
$data = collect($request->validated())
    ->mapKeys(fn($value, $key) => Str::snake($key))
    ->all();

$payload = $this->authService->register($data, $deviceInfo);
```

---

### CRÍTICO #2: AuthController.status() - Relación refreshTokens() no existe

**Ubicación:** `app/Features/Authentication/Http/Controllers/AuthController.php:295-297`

**Problema:**
```php
$currentSession = $user->refreshTokens()  // ❌ NO EXISTE
    ->where('id', $tokenPayload['session_id'])
    ->first();
```

**Análisis:**
- User model NO tiene relación `refreshTokens()`
- RefreshToken tiene `user(): BelongsTo` hacia User
- **Resultado:** Error 500 - Call to undefined method

**Solución:**
```php
$currentSession = \App\Features\Authentication\Models\RefreshToken::query()
    ->where('user_id', $user->id)
    ->where('id', $tokenPayload['session_id'])
    ->first();
```

---

### CRÍTICO #3: AuthController.status() - Relación roleContexts no existe

**Ubicación:** `app/Features/Authentication/Http/Controllers/AuthController.php:300`

**Problema:**
```php
$user->load(['profile', 'roleContexts']);  // ❌ roleContexts NO EXISTE
```

**Análisis:**
- User model tiene `userRoles(): HasMany`, no `roleContexts`
- **Resultado:** Error en carga de relaciones

**Solución:**
```php
$user->load(['profile', 'userRoles']);
```

---

### CRÍTICO #4: DeviceInfoParser - Nombre incorrecto del método

**Ubicación:** Todos los Controllers (líneas 74, 136, 238, etc.)

**Problema:**
```php
$deviceInfo = DeviceInfoParser::parse($request);  // ❌ MÉTODO NO EXISTE
```

**Análisis:**
- El método correcto es `fromRequest()`, no `parse()`
- **Resultado:** Error fatal - método no existe

**Solución:**
```php
$deviceInfo = DeviceInfoParser::fromRequest($request);
```

---

### CRÍTICO #5: AuthController.register() y login() - authService.register() NO retorna expiresIn

**Ubicación:** `AuthPayloadResource.php`

**Problema:**
AuthService.register() retorna:
```php
[
    'user' => User,
    'access_token' => string,
    'refresh_token' => string,
    'expires_in' => int,  // ✅ SÍ está aquí
    'requires_verification' => bool
]
```

Pero mi Resource asume que viene `expiresIn`:
```php
'expiresIn' => $this['expiresIn'] ?? 2592000  // ❌ Key incorrecta
```

**Análisis:**
- Service retorna `expires_in` (snake_case)
- Resource espera `expiresIn` (camelCase)
- **Resultado:** El valor por defecto se usa, no el real

**Solución:**
```php
'expiresIn' => $this['expires_in'] ?? 2592000
```

---

### CRÍTICO #6: AuthService.getEmailVerificationStatus() - Retorna campos diferentes

**Ubicación:** `EmailVerificationStatusResource.php`

**Problema:**
AuthService.getEmailVerificationStatus() retorna:
```php
[
    'is_verified' => bool,
    'verified_at' => DateTime|null,
    'email' => string
]
```

Mi Resource espera:
```php
[
    'isVerified' => bool,
    'email' => string,
    'verificationSentAt' => DateTime,
    'canResend' => bool,
    'resendAvailableAt' => DateTime|null,
    'attemptsRemaining' => int
]
```

**Análisis:**
- Mismatch total entre Service y Resource
- El Service NO retorna todos los campos que espero
- **Resultado:** Campos faltantes en respuesta

**Solución:**
Necesito revisar qué retorna realmente getEmailVerificationStatus() y adaptarme a eso

---

### CRÍTICO #7: SessionController.revoke() - RefreshToken model

**Ubicación:** `SessionController.php:111-125`

**Problema:**
```php
$session = $user->refreshTokens()  // ❌ NO EXISTE
    ->where('id', $sessionId)
    ->first();
```

**Análisis:**
- Mismo problema que en status()
- **Resultado:** Error al revocar sesión

**Solución:**
```php
$session = \App\Features\Authentication\Models\RefreshToken::query()
    ->where('user_id', $user->id)
    ->where('id', $sessionId)
    ->first();
```

---

### CRÍTICO #8: SessionController.logout() - is_revoked vs revoked_at

**Ubicación:** `SessionController.php:73`

**Problema:**
Según RefreshToken model, el campo es `revoked_at` (datetime):
```php
$session->revoked_at = now();
$session->save();
```

Pero no existe campo `is_revoked`:
```php
// ❌ Esto es incorrecto
$session->is_revoked = true;
```

**Análisis:**
- RefreshToken usa `revoked_at` para marcar revocación
- Mi código usa correctamente `revoked_at`
- Pero hay un método `revoke($reason)` que debería usar

**Solución:**
```php
$session->revoke('manual_logout');  // Usar el método del model
```

---

## 🟡 WARNINGS (NO CRÍTICOS PERO IMPORTANTES)

### WARNING #1: TokenBlacklistedException no existe

**Ubicación:** `SessionController.php`

**Problema:**
```php
catch (TokenBlacklistedException $e) {  // ❌ Esta excepción no existe
```

**Análisis:**
- La excepción correcta es `TokenInvalidException::revoked()`
- **Impacto:** El catch nunca se ejecutaría

**Solución:**
```php
catch (TokenInvalidException $e) {
    if ($e->getMessage() === 'Token inválido o ya revocado') {
        // Manejar
    }
}
```

---

### WARNING #2: AuthService.logout() firma incorrecta

**Ubicación:** `SessionController.php:59-68`

**Problema:**
```php
$this->authService->logout($accessToken, $refreshToken ?? '', $user->id);
```

**Análisis:**
- Estoy pasando `$refreshToken ?? ''` con coerción a string vacío
- Si refreshToken es null, AuthService podría no manejarlo bien
- **Impacto:** Posible error silencioso

**Solución:**
```php
if (!$refreshToken) {
    $this->authService->logoutAllDevices($user->id);
} else {
    $this->authService->logout($accessToken, $refreshToken, $user->id);
}
```

---

### WARNING #3: Authorization header parsing no es robusto

**Ubicación:** `AuthController.php:289`

**Problema:**
```php
$token = str_replace('Bearer ', '', $request->header('Authorization', ''));
```

**Análisis:**
- Si header no existe, obtiene string vacío y retorna string vacío
- Si header es "Bearer token123", retorna "token123" ✅
- Si header es "token123", retorna "token123" ✅ (pero es incorrecto)
- Si header es "", retorna "" ❌ (después validateAccessToken() va a fallar)

**Solución:**
```php
$token = str_replace('Bearer ', '', $request->header('Authorization') ?? '');
if (!$token) {
    throw new AuthenticationException('Missing or invalid Authorization header');
}
```

---

## ✅ IMPLEMENTACIONES CORRECTAS

1. ✅ **AuthController.refresh()** - Manejo correcto de múltiples fuentes de token
2. ✅ **SessionController.index()** - Lógica correcta para listar sesiones
3. ✅ **PasswordResetController** - Toda la implementación es correcta
4. ✅ **EmailVerificationController.verify()** - Manejo correcto de excepciones
5. ✅ **OnboardingController** - Implementación simple y correcta
6. ✅ **Form Requests** - Todas las validaciones son correctas (11/11)
7. ✅ **API Resources** - Estructura correcta (excepto los campos de Service)
8. ✅ **OpenAPI annotations** - Todas bien formateadas
9. ✅ **Cookie handling** - HttpOnly, Secure, SameSite correcto en todos lados
10. ✅ **Error handling** - Try-catch en todos los métodos
11. ✅ **Route structure** - Coincide con blueprint de fases
12. ✅ **Dependency injection** - Todos los constructores correctos

---

## 📊 COMPARACIÓN: GraphQL Resolvers vs REST Controllers

### Mutation: register

**GraphQL Resolver (RegisterMutation.php:77-80):**
```php
$payload = $this->authService->register(
    $request->input(),  // Parámetros del request
    $deviceInfo
);
```

**Mi Controller (AuthController.php:77-80):**
```php
$payload = $this->authService->register(
    $request->validated(),  // ❌ DEBE TRANSFORMAR A snake_case
    $deviceInfo
);
```

**VEREDICTO:** ❌ NO IDÉNTICO - Necesita transformación

---

### Mutation: login

**GraphQL Resolver (LoginMutation.php:138-142):**
```php
$payload = $this->authService->login(
    $request->input('email'),
    $request->input('password'),
    $deviceInfo
);
```

**Mi Controller (AuthController.php:138-142):**
```php
$payload = $this->authService->login(
    $request->input('email'),
    $request->input('password'),
    $deviceInfo
);
```

**VEREDICTO:** ✅ IDÉNTICO

---

### Query: status

**GraphQL Resolver (AuthStatusQuery.php:52-75):**
```php
$user = $context->user;  // Del contexto JWT
$tokenPayload = $this->tokenService->validateAccessToken($token);
$currentSession = RefreshToken::find($tokenPayload['session_id']);
$user->load(['profile', 'roleContexts']);
```

**Mi Controller (AuthController.php:282-300):**
```php
$user = $request->user();  // ✅ Equivalente
$tokenPayload = $this->tokenService->validateAccessToken($token);  // ✅ Igual
$currentSession = $user->refreshTokens()->where('id', ...)->first();  // ❌ INCORRECTO
$user->load(['profile', 'roleContexts']);  // ❌ roleContexts NO EXISTE
```

**VEREDICTO:** ❌ NO IDÉNTICO - 2 errores

---

### Response: AuthPayload

**GraphQL retorna (desde resolver):**
```json
{
  "accessToken": "...",
  "refreshToken": "set in cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "user": { ... },
  "sessionId": "...",
  "loginTimestamp": "2025-10-27T12:00:00Z"
}
```

**Mi Resource retorna:**
```json
{
  "accessToken": "...",
  "refreshToken": "Refresh token set in httpOnly cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "user": { ... },
  "sessionId": "...",
  "loginTimestamp": "2025-10-27T12:00:00Z"
}
```

**VEREDICTO:** ✅ IDÉNTICO (mensaje de refreshToken es diferente pero comunicativo)

---

## 📝 TAREAS DE CORRECCIÓN

### Correcciones requeridas (Orden de prioridad):

1. **CRÍTICO** - Reemplazar `DeviceInfoParser::parse()` con `fromRequest()` en todos los Controllers
2. **CRÍTICO** - Transformar camelCase → snake_case en register()
3. **CRÍTICO** - Reemplazar `$user->refreshTokens()` con query directa a RefreshToken
4. **CRÍTICO** - Reemplazar `roleContexts` con `userRoles`
5. **CRÍTICO** - Arreglar keys en AuthPayloadResource (expires_in)
6. **CRÍTICO** - Verificar getEmailVerificationStatus() retorno real
7. **CRÍTICO** - Usar método `revoke()` en RefreshToken
8. **CRÍTICO** - Reemplazar TokenBlacklistedException con TokenInvalidException

### Mejoras recomendadas:

1. **WARNING** - Manejar mejor el caso de refreshToken faltante en logout
2. **WARNING** - Validar Authorization header más robustamente
3. **WARNING** - Considerar usar métodos más seguros para acceso a tokens

---

## 📈 CHECKLIST DE CORRECCIÓN

- [ ] Corrección #1: DeviceInfoParser en todos Controllers (5 archivos)
- [ ] Corrección #2: camelCase → snake_case en register()
- [ ] Corrección #3: Usar RefreshToken query directa en status() y revoke()
- [ ] Corrección #4: Cambiar roleContexts a userRoles
- [ ] Corrección #5: Arreglar keys en Resources
- [ ] Corrección #6: Revisar getEmailVerificationStatus()
- [ ] Corrección #7: Usar método revoke() del model
- [ ] Corrección #8: Actualizar excepciones
- [ ] Validación: Ejecutar código
- [ ] Tests: Actualizar tests con APIs corregidas
- [ ] Final: Commit después de todas las correcciones

---

## 🎯 CONCLUSIÓN

La estructura está **CORRECTA** pero la **IMPLEMENTACIÓN tiene ERRORES** que impiden que funcione.

**No se puede pasar a Fase 5** hasta corregir estos 8 problemas críticos.

Estimado tiempo de corrección: **30-45 minutos**

---

*Auditoría completada: 27-Oct-2025*
*Status: BLOQUEADO hasta correcciones*
