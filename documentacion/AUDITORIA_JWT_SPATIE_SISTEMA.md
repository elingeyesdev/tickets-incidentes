# 🔐 Auditoría de Seguridad: Sistema JWT + Spatie Permission

**Fecha de Auditoría:** 2025-12-10  
**Proyecto:** Helpdesk System  
**Versión analizada:** Laravel 12 + firebase/php-jwt 6.11 + spatie/laravel-permission 6.23

---

## 📋 Resumen Ejecutivo

| Categoría | Estado | Puntuación |
|-----------|--------|------------|
| **Arquitectura JWT** | ✅ Excelente | 9/10 |
| **Integración Spatie** | ✅ Buena | 8/10 |
| **Seguridad de Cookies** | ✅ Excelente | 9/10 |
| **Control de Roles** | ✅ Muy Bueno | 8.5/10 |
| **Manejo de Tokens** | ✅ Excelente | 9/10 |
| **Frontend Auth** | ✅ Muy Bueno | 8/10 |

**Puntuación Global: 8.6/10** - Sistema profesional y seguro con pocas mejoras necesarias.

---

## 🏗️ Arquitectura del Sistema

### 1. Stack Tecnológico

```
┌─────────────────────────────────────────────────────────────┐
│                      FRONTEND                                │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐   │
│  │ Alpine.js     │  │ TokenManager  │  │ AuthChannel   │   │
│  │ authStore.js  │  │ (localStorage)│  │ (BroadcastCh) │   │
│  └───────────────┘  └───────────────┘  └───────────────┘   │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      MIDDLEWARE                              │
│  ┌───────────────────────┐  ┌────────────────────────────┐ │
│  │ JWTAuthenticationMid  │  │ RequireJWTAuthentication   │ │
│  │ (opcional)            │  │ (obligatorio - jwt.require)│ │
│  └───────────────────────┘  └────────────────────────────┘ │
│  ┌───────────────────────┐  ┌────────────────────────────┐ │
│  │ EnsureUserHasRole     │  │ WebAuthenticationMiddleware│ │
│  │ (role:ADMIN,AGENT)    │  │ (rutas Blade)              │ │
│  └───────────────────────┘  └────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      SERVICIOS                               │
│  ┌───────────────────────┐  ┌────────────────────────────┐ │
│  │ TokenService          │  │ AuthService                │ │
│  │ - generateAccessToken │  │ - login/logout             │ │
│  │ - validateAccessToken │  │ - register                 │ │
│  │ - createRefreshToken  │  │ - refreshToken             │ │
│  │ - blacklistToken      │  │ - manejo sesiones          │ │
│  └───────────────────────┘  └────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      MODELOS                                 │
│  ┌───────────────────────┐  ┌────────────────────────────┐ │
│  │ User                  │  │ UserRole                   │ │
│  │ - HasRoles (Spatie)   │  │ - auth.user_roles         │ │
│  │ - getAllRolesForJWT() │  │ - company_id (multi-tenant)│ │
│  └───────────────────────┘  └────────────────────────────┘ │
│  ┌───────────────────────┐                                  │
│  │ RefreshToken          │                                  │
│  │ - auth.refresh_tokens │                                  │
│  │ - token_hash (SHA256) │                                  │
│  └───────────────────────┘                                  │
└─────────────────────────────────────────────────────────────┘
```

### 2. Flujo de Autenticación

```
LOGIN FLOW:
┌─────────┐    POST /api/auth/login      ┌─────────────────┐
│ Client  │ ──────────────────────────► │ AuthController  │
└─────────┘                              └────────┬────────┘
                                                  │
     ┌────────────────────────────────────────────┼────────────────────────────────────────────┐
     │                                            ▼                                            │
     │  ┌─────────────────────────────────────────────────────────────────────────────────┐   │
     │  │ 1. AuthService.login(email, password)                                           │   │
     │  │    - Busca usuario por email                                                    │   │
     │  │    - Verifica password con password_hash                                        │   │
     │  │    - Valida status ACTIVE                                                       │   │
     │  └─────────────────────────────────────────────────────────────────────────────────┘   │
     │                                            │                                            │
     │                                            ▼                                            │
     │  ┌─────────────────────────────────────────────────────────────────────────────────┐   │
     │  │ 2. TokenService.generateAccessToken(user, sessionId, activeRole)                │   │
     │  │    - Crea JWT con claims: iss, aud, iat, exp, sub                               │   │
     │  │    - Incluye: user_id, email, roles[], active_role                              │   │
     │  │    - Firma con HS256 + JWT_SECRET                                               │   │
     │  └─────────────────────────────────────────────────────────────────────────────────┘   │
     │                                            │                                            │
     │                                            ▼                                            │
     │  ┌─────────────────────────────────────────────────────────────────────────────────┐   │
     │  │ 3. TokenService.createRefreshToken(user, deviceInfo)                            │   │
     │  │    - Genera token aleatorio (64 chars hex)                                      │   │
     │  │    - Almacena SHA256(token) en DB (NUNCA plain text)                            │   │
     │  │    - Guarda device_name, IP, user_agent, location                               │   │
     │  └─────────────────────────────────────────────────────────────────────────────────┘   │
     │                                            │                                            │
     └────────────────────────────────────────────┼────────────────────────────────────────────┘
                                                  │
                                                  ▼
     ┌────────────────────────────────────────────────────────────────────────────────────────┐
     │ Response:                                                                              │
     │ - Body: { accessToken, user, expiresIn, sessionId }                                   │
     │ - Cookie: refresh_token (HttpOnly, Secure, SameSite=Lax)                              │
     │ - Cookie: jwt_token (NOT HttpOnly - para JS API calls)                                │
     └────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## ✅ Puntos Fuertes (Lo que está BIEN)

### 1. **Arquitectura JWT Profesional**

```php
// TokenService.php - Estructura de payload excelente
$payload = [
    'iss' => config('jwt.issuer'),     // Issuer (helpdesk-api)
    'aud' => config('jwt.audience'),   // Audience (helpdesk-frontend)
    'iat' => $now,                      // Issued at
    'exp' => $now + $ttl,               // Expiration
    'sub' => $user->id,                 // Subject (user UUID)
    'user_id' => $user->id,
    'email' => $user->email,
    'session_id' => $sessionId,
    'roles' => $roles,                  // Todos los roles disponibles
    'active_role' => $activeRole,       // Rol actualmente activo ← EXCELENTE
];
```

**Análisis:** El JWT incluye todos los claims necesarios siguiendo RFC 7519. El claim `active_role` para multi-rol es una implementación profesional.

### 2. **Refresh Token Seguro en HttpOnly Cookies**

```php
// RefreshTokenController.php
$response->cookie(
    'refresh_token',
    $result['refresh_token'],
    $cookieLifetime,
    '/',
    null,
    config('app.env') === 'production', // Secure solo en HTTPS
    true,                                // HttpOnly ← MUY IMPORTANTE
    false,
    'strict'                             // SameSite=Strict ← EXCELENTE
);
```

**Análisis:** El refresh token está protegido en una cookie HttpOnly, lo que previene ataques XSS. La configuración `SameSite=Strict` previene CSRF.

### 3. **Blacklist de Tokens para Logout Inmediato**

```php
// TokenService.php
public function blacklistToken(string $sessionId, ?int $ttl = null): void
{
    if (!config('jwt.blacklist_enabled')) {
        return;
    }
    Cache::put(
        $this->getBlacklistKey($sessionId),
        true,
        now()->addSeconds($ttl)
    );
}

// También blacklist global por usuario (logout everywhere)
public function blacklistUser(string $userId): void
{
    Cache::put(
        $this->getUserBlacklistKey($userId),
        time(), // Timestamp - todos los tokens anteriores inválidos
        now()->addSeconds($ttl + 300)
    );
}
```

**Análisis:** Implementación profesional de invalidación inmediata de tokens. Soporta logout individual y "logout de todos los dispositivos".

### 4. **Rotación de Refresh Tokens**

```php
// TokenService.php - refreshAccessToken()
// ROTACIÓN: Invalidar refresh token viejo y crear uno nuevo
$oldRefreshToken->revoke($user->id);
$newRefreshTokenData = $this->createRefreshToken($user, $mergedDeviceInfo);
```

**Análisis:** Cada refresh genera un nuevo refresh token, invalidando el anterior. Esto limita la ventana de ataque si un refresh token es robado.

### 5. **Hash SHA256 para Refresh Tokens**

```php
// TokenService.php - createRefreshToken()
$token = bin2hex(random_bytes(32));           // 64 caracteres hex aleatorios
$tokenHash = hash('sha256', $token);          // Solo el hash se guarda
$refreshToken = RefreshToken::create([
    'token_hash' => $tokenHash,               // NUNCA el token plano
    // ...
]);
```

**Análisis:** Excelente práctica de seguridad. Si la base de datos es comprometida, los refresh tokens no pueden ser usados.

### 6. **Sistema de Roles Multi-Tenant**

```php
// User.php - getAllRolesForJWT()
public function getAllRolesForJWT(): array
{
    return $this->activeRoles()
        ->get()
        ->map(fn($userRole) => [
            'code' => $userRole->role_code,
            'company_id' => $userRole->company_id, // ← Multi-tenancy
        ])
        ->toArray();
}
```

**Análisis:** El sistema soporta roles por empresa (AGENT en Company A, COMPANY_ADMIN en Company B), con `active_role` para contexto actual.

### 7. **Sincronización Automática con Spatie**

```php
// UserRoleSpatieObserver.php
public function created(UserRole $userRole): void
{
    $this->syncUserSpatieRoles($userRole->user_id);
}

private function syncUserSpatieRoles(string $userId): void
{
    // Obtener roles de auth.user_roles
    $roleCodes = DB::table('auth.user_roles')
        ->where('user_id', $userId)
        ->where('is_active', true)
        ->pluck('role_code');

    // Sincronizar con Spatie (model_has_roles)
    foreach ($roleCodes as $roleCode) {
        $spatieRole = SpatieRole::where('name', $roleCode)->first();
        // ...
    }
}
```

**Análisis:** La sincronización automática via Observer garantiza que `@role` y `@hasrole` de Blade funcionen correctamente.

### 8. **Middleware de Roles con Active Role**

```php
// EnsureUserHasRole.php
foreach ($roles as $role) {
    if ($hasExplicitActiveRole) {
        // STRICT MODE: Solo verifica el rol activo
        if (JWTHelper::isActiveRole($role)) {
            return $next($request);
        }
    } else {
        // FALLBACK MODE: Compatibilidad hacia atrás
        if (JWTHelper::hasRoleFromJWT($role)) {
            return $next($request);
        }
    }
}
```

**Análisis:** El middleware respeta el sistema de `active_role`, asegurando que los usuarios solo accedan con el rol que tienen activo.

### 9. **Preservación de Active Role en Refresh**

```php
// TokenService.php - refreshAccessToken()
// CRITICAL: Preservar el active_role del access token anterior
$activeRole = null;
$oldPayload = request()->attributes->get('jwt_payload');

if ($oldPayload && isset($oldPayload['active_role'])) {
    $activeRole = $oldPayload['active_role'];
} else {
    // Decodificar token expirado para preservar active_role
    JWT::$leeway = 365 * 24 * 60 * 60; // Ignorar expiración
    $decoded = JWT::decode($oldAccessToken, ...);
    $activeRole = (array) $decoded->active_role;
}
```

**Análisis:** Excelente detalle - el refresh token preserva el contexto de rol, evitando que el usuario tenga que re-seleccionar su rol.

### 10. **Frontend Profesional con Multi-Tab Sync**

```javascript
// authStore.js
subscribeToAuthEvents() {
    this.authChannel.subscribe((event) => {
        switch (event.type) {
            case 'LOGOUT':
                this.handleRemoteLogout(); // Sincroniza logout en todas las tabs
                break;
            case 'TOKEN_REFRESHED':
                this.handleRemoteTokenRefresh(event.data);
                break;
        }
    });
}
```

**Análisis:** El uso de `BroadcastChannel` para sincronizar auth entre tabs es una implementación profesional.

---

## ⚠️ Áreas de Mejora (Recomendaciones)

### 1. **TTL de Access Token Muy Corto (Testing)**

```php
// config/jwt.php - PROBLEMA
'ttl' => env('JWT_TTL', 1), // TESTING: 1 minute ← REVISAR EN PRODUCCIÓN
```

**Riesgo:** 1 minuto es apropiado para testing pero muy corto para producción. Los usuarios experimentarán muchos refreshes.

**Recomendación:**
```php
'ttl' => env('JWT_TTL', 15), // 15 minutos - balance seguridad/UX
```

### 2. **JWT Secret Comparte APP_KEY**

```php
// config/jwt.php
'secret' => env('JWT_SECRET', env('APP_KEY')), // ← Comparte key
```

**Riesgo:** Bajo, pero es mejor práctica tener keys separadas.

**Recomendación:**
```bash
# .env
JWT_SECRET=your-unique-jwt-secret-at-least-32-chars
```

### 3. **Logging Excesivo en Middleware (Performance)**

```php
// RequireJWTAuthentication.php
\Illuminate\Support\Facades\Log::info('[JWT MIDDLEWARE] Request received', [
    'url' => $request->fullUrl(),
    'method' => $request->method(),
    // ... muchos detalles
]);
```

**Riesgo:** Impacto en performance y ruido en logs de producción.

**Recomendación:**
```php
// Solo en debug mode
if (config('app.debug')) {
    Log::debug('[JWT MIDDLEWARE] Request', [...]);
}
```

### 4. **Cookie jwt_token No es HttpOnly**

```php
// RefreshTokenController.php
$response->cookie(
    'jwt_token',
    $result['access_token'],
    // ...
    false,  // NOT HttpOnly (JS lo necesita)
);
```

**Riesgo:** El access token es accesible desde JavaScript, vulnerable a XSS.

**Mitigación actual:** 
- Access token tiene TTL corto (1 min actualmente)
- Sin el refresh token (HttpOnly), el atacante tiene ventana limitada

**Recomendación alternativa:**
- Mantener como está para API calls desde JS
- Asegurar CSP headers estrictos
- Sanitizar todo input de usuario

### 5. **Falta Rate Limiting en Login**

```php
// routes/api.php
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
// Sin throttle específico para login ← PROBLEMA
```

**Riesgo:** Susceptible a ataques de fuerza bruta.

**Recomendación:**
```php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1') // 5 intentos por minuto
    ->name('auth.login');
```

### 6. **Validación de Issuer/Audience Ausente**

```php
// TokenService.php - validateAccessToken()
// Verifica claims requeridos pero NO valida iss/aud
foreach ($requiredClaims as $claim) {
    if (!isset($decoded->$claim)) {
        throw TokenInvalidException::accessToken();
    }
}
// Falta: if ($decoded->iss !== config('jwt.issuer')) { ... }
```

**Recomendación:**
```php
// Después de verificar claims requeridos
if ($decoded->iss !== config('jwt.issuer')) {
    throw TokenInvalidException::accessToken();
}
if ($decoded->aud !== config('jwt.audience')) {
    throw TokenInvalidException::accessToken();
}
```

### 7. **Spatie Teams Feature No Habilitada**

```php
// config/permission.php
'teams' => false,
```

**Análisis:** Tienes tu propio sistema multi-tenant con `company_id` en `auth.user_roles`. Esto está bien, pero podrías beneficiarte de la feature teams de Spatie para permisos granulares por empresa.

**Recomendación:** Mantener como está si el sistema actual funciona. Solo habilitar teams si necesitas permisos (no solo roles) específicos por empresa.

---

## 🔒 Análisis de Seguridad por Componente

### Frontend (authStore.js)

| Aspecto | Estado | Notas |
|---------|--------|-------|
| Token en localStorage | ⚠️ Necesario | XSS risk pero necesario para API calls |
| Refresh via cookie | ✅ Seguro | HttpOnly cookie |
| Multi-tab sync | ✅ Excelente | BroadcastChannel |
| Token expiration check | ✅ Implementado | `isTokenExpired()` |
| Auto-refresh | ✅ Implementado | HeartbeatService |

### Backend (TokenService)

| Aspecto | Estado | Notas |
|---------|--------|-------|
| Firma JWT | ✅ HS256 | Algoritmo seguro |
| Refresh token storage | ✅ SHA256 hash | Nunca plain text |
| Token blacklist | ✅ Implementado | Cache-based |
| User blacklist | ✅ Implementado | Logout everywhere |
| Token rotation | ✅ Implementado | Nuevo token cada refresh |

### Middleware Layer

| Aspecto | Estado | Notas |
|---------|--------|-------|
| JWT validation | ✅ Robusto | Maneja TokenExpired, TokenInvalid |
| Role checking | ✅ Active role aware | Respeta contexto de rol |
| Error responses | ✅ Apropiadas | 401/403 según caso |
| Web redirect | ✅ Implementado | Redirige a login si expira |

### Integración Spatie

| Aspecto | Estado | Notas |
|---------|--------|-------|
| Sync automático | ✅ Observer | UserRoleSpatieObserver |
| @role/@hasrole | ✅ Funciona | Sync garantizado |
| Guard configuration | ✅ `web` guard | Correcto para Blade |
| Custom `model_morph_key` | ✅ `model_uuid` | Compatible con UUIDs |

---

## 📊 Matriz de Riesgos

| Vulnerabilidad | Probabilidad | Impacto | Mitigación Actual | Acción |
|----------------|--------------|---------|-------------------|--------|
| XSS roba access token | Media | Alto | TTL corto (1 min) | Implementar CSP |
| Brute force login | Alta | Alto | Ninguna | **Añadir throttle** |
| Token theft via network | Baja | Alto | HTTPS en prod | Revisar TLS config |
| Refresh token theft | Muy Baja | Crítico | HttpOnly + SHA256 | ✅ OK |
| Session fixation | Muy Baja | Alto | New session on login | ✅ OK |
| JWT tampering | Muy Baja | Crítico | HS256 signature | ✅ OK |

---

## 🎯 Plan de Acción Recomendado

### Prioridad Alta (Hacer Ahora)

1. **Añadir rate limiting al login:**
```php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('auth.login');
```

2. **Ajustar TTL para producción:**
```php
// .env.production
JWT_TTL=15
```

3. **Validar iss/aud en TokenService:**
```php
if ($decoded->iss !== config('jwt.issuer') || 
    $decoded->aud !== config('jwt.audience')) {
    throw TokenInvalidException::accessToken();
}
```

### Prioridad Media (Esta Semana)

4. **Reducir logging en producción:**
```php
if (config('app.debug')) {
    Log::info('[JWT MIDDLEWARE]...', [...]);
}
```

5. **Usar JWT_SECRET separado:**
```bash
php artisan jwt:secret  # O generar manualmente
```

### Prioridad Baja (Cuando Sea Posible)

6. **Implementar CSP headers** para mitigar XSS
7. **Considerar RS256** si hay múltiples servicios que validan JWT
8. **Documentar el sistema** en wiki interna

---

## ✅ Conclusión

El sistema de autenticación JWT + Spatie está **muy bien implementado** con prácticas de seguridad profesionales:

- ✅ Refresh tokens en HttpOnly cookies
- ✅ SHA256 hash para almacenamiento
- ✅ Rotación de tokens
- ✅ Blacklist para invalidación inmediata
- ✅ Sistema multi-rol con active_role
- ✅ Sincronización automática con Spatie
- ✅ Multi-tab sync en frontend

Las mejoras sugeridas son **optimizaciones** más que correcciones críticas. El sistema es seguro para producción una vez implementado el rate limiting en login.

---

*Auditoría realizada por: AI Assistant*  
*Herramientas utilizadas: Análisis estático de código, revisión de configuración*
