# 🚀 REST API Authentication - Guía Rápida

**Documentación automática:** http://localhost:8000/api/documentation (Swagger UI)

**Fecha:** 28-Octubre-2025
**Estado:** ✅ Completo (15/15 endpoints)
**Branch:** `feature/graphql-to-rest-migration`

---

## 📋 TABLA DE CONTENIDOS

1. [Acceso a la documentación](#acceso-a-la-documentación)
2. [Autenticación con JWT](#autenticación-con-jwt)
3. [Endpoints por categoría](#endpoints-por-categoría)
4. [Ejemplos prácticos](#ejemplos-prácticos)
5. [Códigos de error](#códigos-de-error)
6. [Testing con cURL](#testing-con-curl)

---

## 🔗 ACCESO A LA DOCUMENTACIÓN

### Interfaz Swagger UI (Recomendado)
```
http://localhost:8000/api/documentation
```
✅ Interfaz interactiva
✅ Prueba endpoints desde el navegador
✅ Documentación automática desde OpenAPI

### JSON OpenAPI
```
http://localhost:8000/docs
```
Para integraciones programáticas

---

## 🔐 AUTENTICACIÓN CON JWT

### Tokens de Autenticación

La API usa **JWT (JSON Web Tokens)** para autenticación:

```
Authorization: Bearer <ACCESS_TOKEN>
```

### Ciclo de vida de tokens

```
┌─────────────────────────────────────┐
│ 1. Usuario hace login               │
├─────────────────────────────────────┤
│ Recibe:                             │
│  - accessToken (corta duración)     │
│  - refreshToken (en cookie HTTP)    │
└──────────┬──────────────────────────┘
           │
           ├─→ Usar accessToken en cada request
           │   Header: Authorization: Bearer <accessToken>
           │
           ├─→ Cuando expira accessToken:
           │   POST /api/auth/refresh
           │   (con refreshToken del cookie)
           │
           └─→ Obtiene nuevo accessToken
               (sin salir de sesión)
```

### Duración de tokens

| Token | Duración | Ubicación |
|-------|----------|-----------|
| **Access Token** | 30 días | Header `Authorization` |
| **Refresh Token** | 30 días | Cookie HttpOnly `refresh_token` |

### Headers requeridos

```http
GET /api/auth/status HTTP/1.1
Host: localhost:8000
Authorization: Bearer eyJhbGciOiJIUzI1NiIs...
Content-Type: application/json
```

---

## 📡 ENDPOINTS POR CATEGORÍA

### 1️⃣ Autenticación (Público)

#### Registrar usuario
```http
POST /api/auth/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "SecurePass123!",
  "passwordConfirmation": "SecurePass123!",
  "firstName": "Juan",
  "lastName": "Pérez",
  "acceptsTerms": true,
  "acceptsPrivacyPolicy": true
}
```

**Respuesta (201 Created):**
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIs...",
  "refreshToken": "Refresh token set in httpOnly cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "user": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "user@example.com",
    "profile": {
      "firstName": "Juan",
      "lastName": "Pérez"
    },
    "roles": [
      {
        "id": "uuid",
        "name": "user",
        "description": "Regular user"
      }
    ]
  },
  "sessionId": "uuid",
  "loginTimestamp": "2025-10-28T12:00:00Z"
}
```

---

#### Iniciar sesión
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "SecurePass123!",
  "deviceName": "Chrome on Windows",
  "rememberMe": true
}
```

**Respuesta (200 OK):**
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIs...",
  "refreshToken": "Refresh token set in httpOnly cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "user": { /* igual que register */ },
  "sessionId": "uuid",
  "loginTimestamp": "2025-10-28T12:00:00Z"
}
```

**Cookie automática:**
```
Set-Cookie: refresh_token=<token>; Max-Age=2592000; Path=/; HttpOnly; SameSite=Lax;
```

---

#### Refrescar access token
```http
POST /api/auth/refresh
Content-Type: application/json

{}
```

El `refresh_token` se envía automáticamente en el cookie.

**Alternativas:**
```http
# Opción 1: Desde header
POST /api/auth/refresh
X-Refresh-Token: <token>

# Opción 2: Desde body
POST /api/auth/refresh
Content-Type: application/json

{
  "refreshToken": "<token>"
}
```

**Respuesta (200 OK):**
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIs...",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "message": "Token refreshed successfully"
}
```

---

### 2️⃣ Sesiones (Autenticado)

#### Obtener información de autenticación actual
```http
GET /api/auth/status
Authorization: Bearer <ACCESS_TOKEN>
```

**Respuesta:**
```json
{
  "isAuthenticated": true,
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "profile": { /* ... */ },
    "roles": [ /* ... */ ]
  },
  "currentSession": {
    "id": "uuid",
    "deviceName": "Chrome on Windows",
    "ip": "192.168.1.100",
    "userAgent": "Mozilla/5.0...",
    "lastActivityAt": "2025-10-28T12:00:00Z",
    "expiresAt": "2025-11-27T12:00:00Z",
    "isCurrent": true
  },
  "tokenInfo": {
    "expiresIn": 2592000,
    "issuedAt": "2025-10-28T12:00:00Z",
    "tokenType": "Bearer"
  }
}
```

---

#### Listar todas las sesiones activas
```http
GET /api/auth/sessions
Authorization: Bearer <ACCESS_TOKEN>
```

**Respuesta:**
```json
{
  "data": [
    {
      "id": "uuid1",
      "deviceName": "Chrome on Windows",
      "ip": "192.168.1.100",
      "userAgent": "Mozilla/5.0...",
      "lastActivityAt": "2025-10-28T12:00:00Z",
      "expiresAt": "2025-11-27T12:00:00Z",
      "isCurrent": true
    },
    {
      "id": "uuid2",
      "deviceName": "Safari on iPhone",
      "ip": "192.168.1.105",
      "userAgent": "Mozilla/5.0...",
      "lastActivityAt": "2025-10-27T18:00:00Z",
      "expiresAt": "2025-11-27T18:00:00Z",
      "isCurrent": false
    }
  ]
}
```

---

#### Cerrar sesión (logout)
```http
POST /api/auth/logout
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json

{}
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

#### Revocar otra sesión
```http
DELETE /api/auth/sessions/{sessionId}
Authorization: Bearer <ACCESS_TOKEN>
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "message": "Session revoked successfully"
}
```

---

### 3️⃣ Contraseña (Público)

#### Solicitar reset de contraseña
```http
POST /api/auth/password-reset
Content-Type: application/json

{
  "email": "user@example.com"
}
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "message": "Password reset link sent to your email"
}
```

---

#### Confirmar reset de contraseña
```http
POST /api/auth/password-reset/confirm
Content-Type: application/json

{
  "token": "reset_token_from_email",
  "password": "NewPassword123!",
  "passwordConfirmation": "NewPassword123!"
}
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "message": "Password reset successfully",
  "user": { /* ... */ }
}
```

---

#### Verificar estado de reset de contraseña
```http
GET /api/auth/password-reset/status?token=reset_token
```

**Respuesta:**
```json
{
  "valid": true,
  "email": "user@example.com",
  "expiresAt": "2025-10-28T18:00:00Z"
}
```

---

### 4️⃣ Email (Público + Autenticado)

#### Verificar email (Público)
```http
POST /api/auth/email/verify
Content-Type: application/json

{
  "token": "verification_token_from_email"
}
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "message": "Email verified successfully",
  "user": { /* ... */ }
}
```

---

#### Obtener estado de verificación (Autenticado)
```http
GET /api/auth/email/status
Authorization: Bearer <ACCESS_TOKEN>
```

**Respuesta:**
```json
{
  "isVerified": true,
  "verifiedAt": "2025-10-28T12:00:00Z",
  "resendAvailableAt": "2025-10-28T13:00:00Z"
}
```

---

#### Reenviar email de verificación (Autenticado + Rate Limit)
```http
POST /api/auth/email/verify/resend
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json

{}
```

**Rate limit:** 3 intentos por 5 minutos

**Respuesta (200 OK):**
```json
{
  "success": true,
  "message": "Verification email sent",
  "resendAvailableAt": "2025-10-28T13:00:00Z"
}
```

**Si se excede rate limit (429 Too Many Requests):**
```json
{
  "message": "Too many verification emails sent. Try again later.",
  "error": "RATE_LIMIT_EXCEEDED",
  "retryAfter": 300
}
```

---

### 5️⃣ Onboarding (Autenticado)

#### Marcar onboarding como completado
```http
POST /api/auth/onboarding/completed
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json

{}
```

**Respuesta (200 OK):**
```json
{
  "success": true,
  "message": "Onboarding completado exitosamente",
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "onboardingCompletedAt": "2025-10-28T12:00:00Z"
  }
}
```

---

## 💡 EJEMPLOS PRÁCTICOS

### Flujo completo: Registrarse, Login y Refresh

#### 1. Registrar usuario
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "SecurePass123!",
    "passwordConfirmation": "SecurePass123!",
    "firstName": "John",
    "lastName": "Doe",
    "acceptsTerms": true,
    "acceptsPrivacyPolicy": true
  }' \
  -c cookies.txt
```

Guarda el `accessToken` y la cookie `refresh_token`.

#### 2. Usar accessToken para obtener estado
```bash
curl -X GET http://localhost:8000/api/auth/status \
  -H "Authorization: Bearer $ACCESS_TOKEN"
```

#### 3. Refrescar token cuando expire
```bash
curl -X POST http://localhost:8000/api/auth/refresh \
  -b cookies.txt
```

La cookie `refresh_token` se envía automáticamente.

---

### Ejemplo en JavaScript/Fetch

```javascript
// 1. Registrar
const registerResponse = await fetch('/api/auth/register', {
  method: 'POST',
  credentials: 'include', // Envía cookies automáticamente
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'SecurePass123!',
    passwordConfirmation: 'SecurePass123!',
    firstName: 'Juan',
    lastName: 'Pérez',
    acceptsTerms: true,
    acceptsPrivacyPolicy: true
  })
});

const { accessToken } = await registerResponse.json();
localStorage.setItem('accessToken', accessToken);

// 2. Hacer request autenticado
const statusResponse = await fetch('/api/auth/status', {
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('accessToken')}`
  }
});

// 3. Refrescar token
const refreshResponse = await fetch('/api/auth/refresh', {
  method: 'POST',
  credentials: 'include' // Envía refresh_token cookie
});

const { accessToken: newToken } = await refreshResponse.json();
localStorage.setItem('accessToken', newToken);
```

---

## ⚠️ CÓDIGOS DE ERROR

### Status Codes

| Código | Significado | Caso de uso |
|--------|-------------|-----------|
| **200** | OK | Request exitoso |
| **201** | Created | Usuario registrado exitosamente |
| **401** | Unauthorized | Token inválido, expirado o faltante |
| **403** | Forbidden | Permiso denegado |
| **404** | Not Found | Recurso no existe |
| **409** | Conflict | Email ya registrado |
| **422** | Unprocessable Entity | Validación falló |
| **429** | Too Many Requests | Rate limit excedido |
| **500** | Internal Server Error | Error del servidor |

### Ejemplo de respuesta de error (422 Validation)

```json
{
  "success": false,
  "message": "Validation failed",
  "error": "VALIDATION_ERROR",
  "errors": {
    "email": ["Este email ya está registrado."],
    "password": ["La contraseña debe contener al menos una mayúscula."]
  }
}
```

### Ejemplo de error de autenticación (401)

```json
{
  "success": false,
  "message": "Invalid or expired refresh token. Please login again.",
  "error": "INVALID_REFRESH_TOKEN"
}
```

---

## 🔧 TESTING CON cURL

### Test de registro fallido (email duplicado)

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "existing@example.com",
    "password": "SecurePass123!",
    "passwordConfirmation": "SecurePass123!",
    "firstName": "Test",
    "lastName": "User",
    "acceptsTerms": true,
    "acceptsPrivacyPolicy": true
  }' \
  -w "\nHTTP Status: %{http_code}\n"
```

**Esperado:** HTTP 409 (Conflict)

---

### Test de login exitoso

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123!"
  }' \
  -c cookies.txt \
  -w "\nHTTP Status: %{http_code}\n" \
  -v
```

**Esperado:** HTTP 200 + Cookie `refresh_token`

---

### Test con token inválido

```bash
curl -X GET http://localhost:8000/api/auth/status \
  -H "Authorization: Bearer invalid_token" \
  -w "\nHTTP Status: %{http_code}\n"
```

**Esperado:** HTTP 401 (Unauthorized)

---

## 📚 DOCUMENTACIÓN ADICIONAL

- **GraphQL (Legacy):** GraphiQL en http://localhost:8000/graphiql
- **Guía de Migración:** Ver `MIGRACION_GRAPHQL_REST_API.md`
- **Implementación detallada:** Ver `AUDIT_IMPLEMENTACION_REST_V1.md`
- **Tests:** Ver `tests/Feature/Authentication/Services/`

---

## ✅ CHECKLIST DE VERIFICACIÓN

Al integrar la API REST, verifica:

- [ ] Swagger UI accesible en `/api/documentation`
- [ ] Registro funciona (201 Created + accessToken)
- [ ] Login funciona (200 OK + refresh_token cookie)
- [ ] JWT validation en cada request
- [ ] Refresh token rotation funciona
- [ ] Rate limit en resend verificación (3/5m)
- [ ] Logout limpia sesión
- [ ] Errores devuelven HTTP status codes correctos
- [ ] Cookies HttpOnly en desarrollo y producción
- [ ] CORS correctamente configurado para frontend

---

**Última actualización:** 28-Octubre-2025
**Mantenedor:** Equipo de desarrollo
