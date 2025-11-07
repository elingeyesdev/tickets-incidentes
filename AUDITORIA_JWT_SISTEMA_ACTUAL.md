# AUDITORÍA: Sistema JWT Actual vs Adaptación Blade

**Fecha**: 7 Noviembre 2025
**Status**: ✅ SISTEMA FUNCIONAL Y PRODUCTION-READY

---

## 1. RESUMEN EJECUTIVO

Tu sistema JWT backend es **profesional, seguro y completo**.

**Todos los componentes necesarios existen y funcionan correctamente**:
- ✅ TokenService (generación, validación, refresh)
- ✅ AuthService (login, register, logout, refresh)
- ✅ RefreshToken Model (con persistencia en BD)
- ✅ JWTAuthenticationMiddleware (validación)
- ✅ Endpoints REST completos
- ✅ HttpOnly cookie para refresh token
- ✅ Token rotation y revocation
- ✅ Device tracking
- ✅ Email verification

**No hay nada que "reparar"** - Solo necesita adaptación frontend en Blade + Alpine.js.

---

## 2. ARQUITECTURA ACTUAL

```
Frontend (Blade + Alpine) ← → Middlewares JWT ← → Services Auth/Token
                                    ↓
                          Request Validation
                                    ↓
                          AuthService Logic
                                    ↓
                          TokenService Ops
                                    ↓
                          Database & Cache
```

### Flujo Completo Actual

**Login/Register**:
```
1. Client POST /api/auth/login
   ↓
2. AuthController::login()
   ↓
3. AuthService::login()
   ↓
4. TokenService::generateAccessToken()  → JWT con sessionId
5. TokenService::createRefreshToken()   → BD + Hash
   ↓
6. Response:
   - JSON: { accessToken, user, expiresIn, sessionId }
   - Cookie: refresh_token (HttpOnly, 30 días)
```

**Protected Request**:
```
1. Client: Authorization: Bearer {accessToken}
   ↓
2. JWTAuthenticationMiddleware::handle()
   ↓
3. TokenService::validateAccessToken()
   ↓
4. Check blacklist + revocation
   ↓
5. Set request->jwt_user (User model)
   ↓
6. Controller accesses auth()->user()
```

**Token Refresh**:
```
1. Client: Cookie refresh_token (HttpOnly)
   ↓
2. RefreshTokenController::refresh()
   ↓
3. TokenService::validateRefreshToken()
   ↓
4. TokenService::refreshAccessToken()
   - Revoke old token
   - Generate new tokens (ROTATION)
   ↓
5. Response: { accessToken, expiresIn }
   Cookie: new refresh_token
```

---

## 3. ENDPOINTS API (CONFIRMADOS)

### Authentication Endpoints

| Endpoint | Method | Auth | Status Code | Response |
|----------|--------|------|------------|----------|
| `/api/auth/register` | POST | ❌ | 201 | `{ accessToken, refreshToken, tokenType, expiresIn, user, sessionId, loginTimestamp }` |
| `/api/auth/login` | POST | ❌ | 200 | `{ accessToken, refreshToken, tokenType, expiresIn, user, sessionId, loginTimestamp }` |
| `/api/auth/refresh` | POST | ❌ | 200 | `{ accessToken, tokenType, expiresIn }` |
| `/api/auth/logout` | POST | ✅ | 200 | `{ success, message }` |
| `/api/auth/status` | GET | ✅ | 200 | `{ isAuthenticated, user }` |
| `/api/auth/email/verify` | POST | ❌ | 200 | `{ success, message }` |
| `/api/auth/email/verify/resend` | POST | ✅ | 200 | `{ success, message }` |
| `/api/auth/password-reset` | POST | ❌ | 200 | `{ success, message }` |
| `/api/auth/password-reset/confirm` | POST | ❌ | 200 | `{ success, message }` |
| `/api/auth/sessions` | GET | ✅ | 200 | `{ data: [{ id, device_name, ip, lastUsedAt }] }` |
| `/api/auth/sessions/{id}` | DELETE | ✅ | 204 | (empty) |

### Response Structure: Login/Register (201/200)

```json
{
  "data": {
    "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "refreshToken": "Refresh token set in httpOnly cookie",
    "tokenType": "Bearer",
    "expiresIn": 3600,
    "user": {
      "id": "uuid",
      "userCode": "USR-20241101-001",
      "email": "user@example.com",
      "displayName": "Juan Pérez",
      "emailVerified": false,
      "onboardingCompleted": false,
      "status": "ACTIVE",
      "avatarUrl": null,
      "theme": "light",
      "language": "es",
      "roleContexts": [
        {
          "roleCode": "USER",
          "roleName": "Cliente",
          "dashboardPath": "/dashboard",
          "company": null
        }
      ]
    },
    "sessionId": "uuid",
    "loginTimestamp": "2024-11-07T10:30:00+00:00"
  }
}
```

### Response Structure: Refresh (200)

```json
{
  "data": {
    "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "tokenType": "Bearer",
    "expiresIn": 3600
  }
}
```

### Response Structure: Status (200)

```json
{
  "data": {
    "isAuthenticated": true,
    "user": { /* same as login */ }
  }
}
```

### Response Structure: Error (422, 401, 409)

```json
{
  "message": "Validation error message",
  "errors": {
    "email": ["Email already registered"],
    "password": ["Password too short"]
  }
}
```

---

## 4. COOKIE HANDLING

### Refresh Token Cookie

**Set-Cookie Header** (from /api/auth/login, /api/auth/register, /api/auth/refresh):
```
refresh_token=<plainToken>;
Path=/;
HttpOnly;
SameSite=Lax;
Max-Age=2592000;
Secure (in production)
```

**Critical Properties**:
- ✅ **HttpOnly**: JavaScript CANNOT access this cookie (XSS protection)
- ✅ **SameSite=Lax**: CSRF protection
- ✅ **Secure**: HTTPS only in production
- ✅ **Max-Age=2592000**: 30 days = 2592000 seconds

**How it's sent**:
- Browser automatically includes in requests (by default)
- No JavaScript intervention needed
- Use `credentials: 'include'` in fetch()

---

## 5. DATOS ALMACENADOS EN JWT (Access Token)

**Header**:
```json
{
  "typ": "JWT",
  "alg": "HS256"
}
```

**Payload** (decoded):
```json
{
  "iss": "helpdesk-api",
  "aud": "helpdesk-frontend",
  "iat": 1699348200,
  "exp": 1699351800,
  "sub": "user-uuid",
  "user_id": "user-uuid",
  "email": "user@example.com",
  "session_id": "refresh-token-uuid",
  "roles": ["USER"],
  "companies": [
    {
      "id": "company-uuid",
      "companyCode": "COMP-001",
      "name": "Acme Corp"
    }
  ]
}
```

---

## 6. CONFIGURACIÓN JWT

**File**: `/config/jwt.php`

```php
'secret' => env('JWT_SECRET', env('APP_KEY')),
'algo' => 'HS256',
'ttl' => 3600,                    // 1 hora (access token)
'refresh_ttl' => 2592000,         // 30 días (refresh token)
'issuer' => 'helpdesk-api',
'audience' => 'helpdesk-frontend',
'blacklist_enabled' => true,      // Token revocation
```

---

## 7. BASE DE DATOS: REFRESH_TOKENS

**Table**: `auth.refresh_tokens`

```sql
Columns:
- id (UUID, PK)
- user_id (UUID, FK)
- token_hash (VARCHAR 255, UNIQUE) ← SHA-256 HASH (never plain text)
- device_name (VARCHAR 100)
- ip_address (INET)
- user_agent (TEXT)
- expires_at (TIMESTAMPTZ)
- last_used_at (TIMESTAMPTZ)
- is_revoked (BOOLEAN)
- revoked_at (TIMESTAMPTZ)
- revoke_reason (VARCHAR 100)
- created_at, updated_at (TIMESTAMPTZ)

Constraints:
- NOT NULL: user_id, token_hash, expires_at
- UNIQUE: token_hash
- INDEX: user_id, is_revoked, expires_at
```

**Security Note**:
- Plain token never stored in DB
- Only SHA-256 hash is stored
- Plain token sent once in Set-Cookie header
- Browser stores it in secure cookie

---

## 8. SEGURIDAD IMPLEMENTADA

### ✅ Token Security
- JWT signed with HS256 (HMAC-SHA256)
- Signature verified on every request
- Claims validation (iss, aud, iat, exp, sub required)
- Algorithm validation (HS256 enforced)

### ✅ Refresh Token Security
- Hashed with SHA-256 before storage
- Token rotation on refresh (old invalidated)
- Expiration tracking (30 days)
- Per-device tracking (IP, user-agent, device name)

### ✅ Cookie Security
- HttpOnly (XSS protection)
- SameSite=Lax (CSRF protection)
- Secure flag (HTTPS only in prod)
- Auto-sent by browser (no JS manipulation)

### ✅ Session Management
- Per-device refresh tokens
- Session tracking with metadata
- Manual session revocation
- Logout all devices (global blacklist)

### ✅ Revocation
- **Token-level**: Individual session logout
- **User-level**: Global logout everywhere
- **Cache-based**: Redis blacklist for immediate effect
- **DB-based**: Permanent revocation records

### ✅ User Validation
- Check user status (not suspended/deleted)
- Check email verification (for critical operations)
- Check role/permission (via middleware)
- Rate limiting on auth endpoints

---

## 9. ERRORES ESPERADOS

### 400 Bad Request
- Missing required fields
- Invalid input format

### 401 Unauthorized
- Invalid/expired/missing token
- Invalid credentials
- Email not verified
- Session not found

### 409 Conflict
- Email already registered
- User already verified

### 422 Unprocessable Entity
- Validation errors (fields shown)

### 429 Too Many Requests
- Rate limit exceeded (password reset, email resend)

---

## 10. CÓMO USARLOS DESDE BLADE

### Obtener Access Token (Blade Controller)

```php
// Blade Controller
public function show(Request $request)
{
    // Token viene en Authorization header (si es request API)
    // O viene de cookie refresh_token (para refresh)

    $token = $request->bearerToken();  // Authorization: Bearer {token}
    $refreshToken = $request->cookie('refresh_token');  // Cookie

    return view('app.dashboard', [
        'user' => $request->user(),
        'token' => $token,
    ]);
}
```

### Pasar Token a JavaScript (Blade Template)

```blade
<!-- En Blade template -->
<script>
  // NO recomendado: pasar accessToken a JS (XSS risk)

  // RECOMENDADO: Pasar solo datos del usuario
  window.__INITIAL_STATE__ = @json([
    'user' => auth()->user(),
    'isAuthenticated' => auth()->check(),
  ]);

  // JavaScript obtiene token desde:
  // 1. Response de /api/auth/login (JSON body)
  // 2. localStorage (guardar localmente)
  // 3. Cookie refresh_token (automática, no JS)
</script>
```

### Hacer Requests Autenticados (JavaScript)

```javascript
// TokenManager (sin cookies)
const response = await fetch('/api/tickets', {
  headers: {
    'Authorization': `Bearer ${accessToken}`,
    'Content-Type': 'application/json'
  }
});

// Refresh endpoint (con HttpOnly cookie)
const response = await fetch('/api/auth/refresh', {
  method: 'POST',
  credentials: 'include',  // ← Envía cookies automáticamente
  headers: {
    'Content-Type': 'application/json'
  }
});
```

---

## 11. CAMBIOS NECESARIOS PARA BLADE

**NO NECESITA CAMBIOS** en:
- TokenService
- AuthService
- RefreshToken Model
- Middleware
- API Endpoints
- Configuration

**NECESITA AGREGAR** (sin modificar lo existente):
- Blade layouts (guest, onboarding, app)
- Alpine.js stores (authStore)
- TokenManager.js (frontend JWT handling)
- AuthChannel.js (multi-tab sync)
- PersistenceService.js (IndexedDB)
- HeartbeatService.js (session keepalive)
- Blade controllers para renderizar vistas
- Blade routes en web.php

---

## 12. PUNTOS CRÍTICOS A NO ROMPER

1. **No modificar TokenService**
   - Genera JWTs, valida, refresca
   - Usado por toda la app

2. **No modificar RefreshToken model**
   - Gestiona persistencia en BD
   - Tracking de sesiones

3. **No modificar middleware**
   - Valida tokens en requests
   - Protege rutas

4. **No cambiar endpoints**
   - API es estable y funcional
   - Múltiples clientes dependientes

5. **No alterar cookie handling**
   - HttpOnly es crítico para seguridad
   - SameSite=Lax es importante

---

## 13. READINESS PARA FASE 2

✅ **Backend 100% listo**

**Para Fase 2 (Blade Frontend)**, necesitas:

1. **TokenManager.js**
   - Leer accessToken de JSON response
   - Guardar en localStorage
   - Auto-refresh al 80% TTL
   - Interceptar 401 → refresh → retry

2. **AuthChannel.js**
   - Multi-tab sync via BroadcastChannel
   - Events: LOGIN, LOGOUT, TOKEN_REFRESHED

3. **PersistenceService.js**
   - Guardar estado en IndexedDB
   - Restaurar al recargar página

4. **HeartbeatService.js**
   - Ping periódico a /api/auth/status
   - Detect inactividad → logout

5. **authStore.js (Alpine)**
   - Estado global: user, isAuthenticated
   - Métodos: login(), logout(), loadUser()

6. **Blade routes & controllers**
   - GET /login → login.blade.php
   - GET /dashboard → dashboard.blade.php
   - Usar middleware jwt para proteger

---

## 14. CHECKLIST BEFORE PHASE 2

- ✅ API endpoints tested and working
- ✅ JWT generation confirmed
- ✅ Refresh token rotation verified
- ✅ HttpOnly cookie set correctly
- ✅ Middleware validating tokens
- ✅ Error responses documented
- ✅ Database schema confirmed
- ✅ Configuration reviewed
- ✅ Security measures verified

**Status**: ✅ READY FOR PHASE 2

---

## 15. NOTAS PARA IMPLEMENTACIÓN

### Access Token Lifecycle

```
1. User logs in
   ↓
2. Backend genera JWT (3600 segundos = 1 hora)
3. Response JSON: { accessToken }
4. Response Cookie: { refresh_token (30 días) }
   ↓
5. Frontend:
   - Guarda accessToken en localStorage
   - Browser guarda refresh_token en cookie (automático)
   ↓
6. Al 80% TTL (48 minutos):
   - Frontend POST /api/auth/refresh
   - Envía cookie refresh_token (automático)
   - Recibe nuevo accessToken
   - Recibe nueva cookie refresh_token (rotation)
   ↓
7. Cuando expira access token (1 hora):
   - 401 response
   - Frontend intenta refresh
   - Si refresh falla → redirige a login
```

### Multi-Tab Sync Flow

```
Tab 1: User clicks Logout
   ↓
POST /api/auth/logout (con accessToken)
   ↓
Backend invalida tokens + database revocation
   ↓
Tab 1: AuthChannel.broadcast('LOGOUT')
   ↓
Tab 2: Recibe evento
   ↓
Tab 2: tokenManager.clearTokens()
Tab 2: window.location.href = '/login'
```

### Session Restoration Flow

```
User abre navegador (después de cerrar tab)
   ↓
authStore.init() en Blade template
   ↓
persistenceService.loadAuthState() from IndexedDB
   ↓
¿Token aún válido?
   YES → tokenManager.setTokens()
        → authStore.loadUser()
        → mostrar dashboard
   NO → window.location.href = '/login'
```

---

**CONCLUSIÓN**: Tu sistema JWT es enterprise-grade y completamente funcional. Fase 2 es pura adaptación frontend sin modificar el backend.
