# 🔐 Endpoints de Autenticación - Mapeo Completo

**Fecha:** 28-Octubre-2025
**Feature:** Authentication
**Total Endpoints:** 15

---

## 📊 TABLA RÁPIDA - AUTENTICACIÓN POR ENDPOINT

| # | Método | Ruta | Requiere JWT | Headers | Cookies | Descripción |
|---|--------|------|--------------|---------|---------|-------------|
| 1 | POST | `/api/auth/register` | ❌ No | - | ✅ Recibe refresh_token | Registrar nuevo usuario |
| 2 | POST | `/api/auth/login` | ❌ No | - | ✅ Recibe refresh_token | Iniciar sesión |
| 3 | POST | `/api/auth/login/google` | ❌ No | - | ✅ Recibe refresh_token | Login con Google OAuth |
| 4 | POST | `/api/auth/refresh` | ❌ No | (opcional) | ✅ Envía refresh_token | Renovar access token |
| 5 | POST | `/api/auth/password-reset` | ❌ No | - | - | Solicitar reset contraseña |
| 6 | GET | `/api/auth/password-reset/status` | ❌ No | - | - | Validar token de reset |
| 7 | POST | `/api/auth/password-reset/confirm` | ❌ No | - | - | Confirmar nueva contraseña |
| 8 | POST | `/api/auth/email/verify` | ❌ No | - | - | Verificar email (token) |
| 9 | GET | `/api/auth/status` | ✅ **SÍ** | ✅ Authorization: Bearer | - | Obtener estado auth actual |
| 10 | GET | `/api/auth/sessions` | ✅ **SÍ** | ✅ Authorization: Bearer | - | Listar sesiones activas |
| 11 | DELETE | `/api/auth/sessions/{id}` | ✅ **SÍ** | ✅ Authorization: Bearer | - | Revocar sesión |
| 12 | POST | `/api/auth/logout` | ✅ **SÍ** | ✅ Authorization: Bearer | - | Cerrar sesión |
| 13 | GET | `/api/auth/email/status` | ✅ **SÍ** | ✅ Authorization: Bearer | - | Ver estado verificación email |
| 14 | POST | `/api/auth/email/verify/resend` | ✅ **SÍ** | ✅ Authorization: Bearer | ⏱️ Rate limit 3/5m | Reenviar email verificación |
| 15 | POST | `/api/auth/onboarding/completed` | ✅ **SÍ** | ✅ Authorization: Bearer | - | Marcar onboarding completado |

---

## 🔓 ENDPOINTS PÚBLICOS (Sin autenticación)

### 1. Registro
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

**No requiere:**
- ❌ Authorization header
- ❌ JWT token

**Respuesta:**
```json
HTTP/1.1 201 Created
Set-Cookie: refresh_token=<token>; ...

{
  "accessToken": "eyJhbGc...",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "user": { ... }
}
```

---

### 2. Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "SecurePass123!",
  "deviceName": "Chrome Windows"
}
```

**No requiere:**
- ❌ Authorization header
- ❌ JWT token

---

### 3. Login con Google
```http
POST /api/auth/login/google
Content-Type: application/json

{
  "googleToken": "<google_id_token>"
}
```

**No requiere:**
- ❌ Authorization header
- ❌ JWT token

---

### 4. Refrescar Token
```http
POST /api/auth/refresh
Content-Type: application/json

{}
```

**Opciones de autenticación:**

**Opción A: Cookie automática (Recomendado)**
```http
POST /api/auth/refresh
Content-Type: application/json

{}
```
El `refresh_token` se envía automáticamente desde la cookie HttpOnly.

**Opción B: Header X-Refresh-Token**
```http
POST /api/auth/refresh
X-Refresh-Token: <token>
Content-Type: application/json

{}
```

**Opción C: Body (no recomendado)**
```http
POST /api/auth/refresh
Content-Type: application/json

{
  "refreshToken": "<token>"
}
```

**No requiere:**
- ❌ Authorization header (parámetro diferente)
- ❌ JWT access token

---

### 5. Reset de Contraseña - Solicitar
```http
POST /api/auth/password-reset
Content-Type: application/json

{
  "email": "user@example.com"
}
```

**Seguridad:** Siempre retorna `success: true` para evitar enumerar usuarios.

**No requiere:**
- ❌ Authorization header
- ❌ JWT token

---

### 6. Reset de Contraseña - Validar Token
```http
GET /api/auth/password-reset/status?token=<reset_token>
```

**Parámetro:**
- `token` (query string) - Token del email de reset

**No requiere:**
- ❌ Authorization header
- ❌ JWT token

---

### 7. Reset de Contraseña - Confirmar
```http
POST /api/auth/password-reset/confirm
Content-Type: application/json

{
  "token": "<reset_token>",
  "password": "NewPassword123!",
  "passwordConfirmation": "NewPassword123!"
}
```

**No requiere:**
- ❌ Authorization header
- ❌ JWT token

---

### 8. Verificar Email
```http
POST /api/auth/email/verify
Content-Type: application/json

{
  "token": "<verification_token>"
}
```

**Token:** Viene del email de verificación

**No requiere:**
- ❌ Authorization header
- ❌ JWT token

---

## 🔒 ENDPOINTS AUTENTICADOS (Requieren JWT)

### ⚠️ IMPORTANTE: Cómo pasar el JWT en Swagger

**En Swagger UI, cuando veas un endpoint que requiere autenticación:**

1. **Busca el botón "Authorize"** en la parte superior derecha
2. **Ingresa tu token** en el formato:
   ```
   Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
   ```
3. **O simplemente:** (sin "Bearer", Swagger lo agrega automáticamente)
   ```
   eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
   ```
4. **Swagger enviará automáticamente:**
   ```
   Authorization: Bearer <tu_token>
   ```

---

### 1. Obtener Estado de Autenticación
```http
GET /api/auth/status
Authorization: Bearer <ACCESS_TOKEN>
```

**Requiere:**
- ✅ Authorization header
- ✅ Valid JWT access token

**Respuesta (200 OK):**
```json
{
  "isAuthenticated": true,
  "user": {
    "id": "uuid",
    "email": "user@example.com",
    "profile": {
      "firstName": "Juan",
      "lastName": "Pérez"
    },
    "roles": [...]
  },
  "currentSession": {
    "id": "uuid",
    "deviceName": "Chrome",
    "ip": "192.168.1.100",
    "lastActivityAt": "2025-10-28T12:00:00Z",
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

### 2. Listar Sesiones
```http
GET /api/auth/sessions
Authorization: Bearer <ACCESS_TOKEN>
```

**Requiere:**
- ✅ Authorization header
- ✅ Valid JWT access token

**Respuesta (200 OK):**
```json
{
  "data": [
    {
      "id": "uuid1",
      "deviceName": "Chrome Windows",
      "ip": "192.168.1.100",
      "userAgent": "Mozilla/5.0...",
      "lastActivityAt": "2025-10-28T12:00:00Z",
      "expiresAt": "2025-11-27T12:00:00Z",
      "isCurrent": true
    },
    {
      "id": "uuid2",
      "deviceName": "Safari iPhone",
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

### 3. Revocar Sesión
```http
DELETE /api/auth/sessions/{sessionId}
Authorization: Bearer <ACCESS_TOKEN>
```

**Parámetro:**
- `{sessionId}` (path) - ID de la sesión a revocar

**Requiere:**
- ✅ Authorization header
- ✅ Valid JWT access token

**Validaciones:**
- ❌ NO puedes revocar tu sesión actual (recibirás error)
- ✅ Puedes revocar otras sesiones (otros dispositivos)

---

### 4. Logout
```http
POST /api/auth/logout
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json

{}
```

**Requiere:**
- ✅ Authorization header
- ✅ Valid JWT access token

**Efecto:**
- Revoca la sesión actual (refresh token)
- Invalida el access token
- Usuario debe hacer login nuevamente

---

### 5. Ver Estado de Verificación de Email
```http
GET /api/auth/email/status
Authorization: Bearer <ACCESS_TOKEN>
```

**Requiere:**
- ✅ Authorization header
- ✅ Valid JWT access token

**Respuesta (200 OK):**
```json
{
  "isVerified": true,
  "verifiedAt": "2025-10-28T10:00:00Z",
  "resendAvailableAt": null
}
```

---

### 6. Reenviar Email de Verificación
```http
POST /api/auth/email/verify/resend
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json

{}
```

**Requiere:**
- ✅ Authorization header
- ✅ Valid JWT access token

**Rate Limit:**
- ⏱️ 3 intentos por 5 minutos
- Si se excede: HTTP 429 Too Many Requests

**Si se excede límite:**
```json
HTTP/1.1 429 Too Many Requests

{
  "message": "Too many verification emails sent. Try again later.",
  "error": "RATE_LIMIT_EXCEEDED",
  "retryAfter": 300
}
```

---

### 7. Marcar Onboarding Completado
```http
POST /api/auth/onboarding/completed
Authorization: Bearer <ACCESS_TOKEN>
Content-Type: application/json

{}
```

**Requiere:**
- ✅ Authorization header
- ✅ Valid JWT access token

**Comportamiento:**
- Si ya está completado: retorna success sin cambiar nada
- Si no está completado: establece `onboarding_completed_at = NOW()`

---

## 📋 CÓMO OBTENER Y USAR EL JWT EN SWAGGER

### Paso 1: Obtener el token
```
POST /api/auth/login
```
Recibirás:
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  ...
}
```

### Paso 2: Ir a "Authorize" en Swagger UI
- Arriba a la derecha verás un botón "Authorize"
- Haz clic

### Paso 3: Ingresar el token
```
Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Paso 4: Usar endpoints autenticados
- Todos los requests incluirán automáticamente:
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## 🧪 EJEMPLO COMPLETO CON cURL

### 1. Login para obtener token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "SecurePass123!"
  }' \
  -c cookies.txt
```

Respuesta:
```json
{
  "accessToken": "eyJhbGc...",
  "refreshToken": "Refresh token set in httpOnly cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "user": { ... }
}
```

**Guarda el `accessToken`:**
```bash
export TOKEN="eyJhbGc..."
```

### 2. Usar token en endpoint autenticado
```bash
curl -X GET http://localhost:8000/api/auth/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json"
```

### 3. Refrescar token cuando expire
```bash
curl -X POST http://localhost:8000/api/auth/refresh \
  -b cookies.txt \
  -H "Content-Type: application/json" \
  -d '{}'
```

---

## ✅ CHECKLIST PARA USAR SWAGGER

- [ ] Accede a http://localhost:8000/docs
- [ ] Busca endpoint `/api/auth/login` (no autenticado)
- [ ] Click en "Try it out"
- [ ] Ingresa email y password
- [ ] Click "Execute"
- [ ] Copia el `accessToken` de la respuesta
- [ ] Click en botón "Authorize" (arriba a la derecha)
- [ ] Pega el token: `Bearer <accessToken>`
- [ ] Click "Authorize"
- [ ] Ahora prueba endpoints autenticados:
  - `GET /api/auth/status`
  - `GET /api/auth/sessions`
  - `POST /api/auth/logout`

---

## 🚨 ERRORES COMUNES

| Error | Causa | Solución |
|-------|-------|----------|
| `401 Unauthorized` | Token faltante o inválido | Verifica que incluyas `Authorization: Bearer <token>` |
| `401 Unauthenticated` | Token expirado | Usa `/api/auth/refresh` para obtener nuevo token |
| `403 Forbidden` | Permiso denegado | Este endpoint requiere ciertos permisos |
| `422 Validation error` | Datos inválidos | Verifica los parámetros requeridos |
| `429 Too Many Requests` | Rate limit excedido | Espera antes de reintentar |

---

**Última actualización:** 28-Octubre-2025
**Mantenedor:** Equipo de desarrollo
