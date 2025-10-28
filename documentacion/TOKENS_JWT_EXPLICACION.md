# 🔑 Tokens JWT - Access Token vs Refresh Token

**Fecha:** 28-Octubre-2025
**Arquitectura:** JWT con Refresh Token Rotation

---

## 📊 COMPARATIVA RÁPIDA

| Aspecto | Access Token | Refresh Token |
|---------|--------------|---------------|
| **Propósito** | Autenticar requests | Renovar access token |
| **Duración** | 30 días | 30 días |
| **Ubicación** | Header `Authorization` | Cookie HttpOnly |
| **Enviado por** | Cliente (frontend) | Navegador (automático) |
| **Seguridad** | Puede estar en localStorage | Protegido (HttpOnly, no accesible vía JS) |
| **Usado en** | TODOS los requests autenticados | Solo en `/api/auth/refresh` |
| **Visibilidad** | Visible en DevTools | NO visible en DevTools (HttpOnly) |
| **Si expira** | Llama `/api/auth/refresh` | Genera nuevo access token |
| **Si se revoca** | Usuario debe logout | Genera nuevo o invalida |

---

## 🔐 ¿Cuáles son los TRES flujos?

### 1️⃣ ENDPOINTS PÚBLICOS (Sin tokens)

```http
POST /api/auth/register
POST /api/auth/login
POST /api/auth/login/google
POST /api/auth/refresh          ← Solo refresh_token (en cookie)
POST /api/auth/password-reset
POST /api/auth/password-reset/confirm
GET  /api/auth/password-reset/status
POST /api/auth/email/verify
```

**No requieren nada especial.**

El `/api/auth/refresh` es **especial**: usa `refresh_token` de la cookie, NO un header.

---

### 2️⃣ ENDPOINTS AUTENTICADOS (Requieren access token)

```http
GET  /api/auth/status
GET  /api/auth/sessions
DELETE /api/auth/sessions/{id}
POST /api/auth/logout
GET  /api/auth/email/status
POST /api/auth/email/verify/resend
POST /api/auth/onboarding/completed
```

**TODOS requieren:**
```
Authorization: Bearer <ACCESS_TOKEN>
```

Donde `<ACCESS_TOKEN>` es el `accessToken` que recibiste del login/register.

---

### 3️⃣ FLUJO AUTOMÁTICO DE COOKIES (No haces nada)

**Cuándo se envían automáticamente:**

1. **Después de login/register:**
   ```
   Set-Cookie: refresh_token=<token>; HttpOnly; SameSite=Lax; Path=/
   ```
   El navegador **almacena automáticamente** en cookies.

2. **En POST /api/auth/refresh:**
   El navegador **envía automáticamente** la cookie:
   ```
   Cookie: refresh_token=<token>
   ```

3. **Swagger también envía cookies automáticamente** (si `credentials: include` está habilitado)

---

## 🎯 CUÁLES ENDPOINTS NECESITAN QUÉ

### Endpoints Públicos

| Endpoint | Access Token | Refresh Token | Headers | Cookies |
|----------|--------------|---------------|---------|---------|
| POST /register | ❌ No | ❌ No | - | ✅ Recibe |
| POST /login | ❌ No | ❌ No | - | ✅ Recibe |
| POST /login/google | ❌ No | ❌ No | - | ✅ Recibe |
| POST /refresh | ❌ No | ✅ Sí* | - | ✅ Automático |
| POST /password-reset | ❌ No | ❌ No | - | - |
| GET /password-reset/status | ❌ No | ❌ No | Query: token | - |
| POST /password-reset/confirm | ❌ No | ❌ No | Body: token | - |
| POST /email/verify | ❌ No | ❌ No | Body: token | - |

*El refresh token puede venir de: Header `X-Refresh-Token`, Cookie, o Body

---

### Endpoints Autenticados

| Endpoint | Access Token | Cookies |
|----------|--------------|---------|
| GET /status | ✅ **SÍ** (Header Bearer) | ❌ No |
| GET /sessions | ✅ **SÍ** (Header Bearer) | ❌ No |
| DELETE /sessions/{id} | ✅ **SÍ** (Header Bearer) | ❌ No |
| POST /logout | ✅ **SÍ** (Header Bearer) | ✅ Borra cookie |
| GET /email/status | ✅ **SÍ** (Header Bearer) | ❌ No |
| POST /email/verify/resend | ✅ **SÍ** (Header Bearer) | ❌ No |
| POST /onboarding/completed | ✅ **SÍ** (Header Bearer) | ❌ No |

---

## 💡 CÓMO FUNCIONA EL FLUJO PASO A PASO

### Paso 1: Registrarse o Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Respuesta:**
```http
HTTP/1.1 200 OK
Set-Cookie: refresh_token=eyJhbGc...; HttpOnly; SameSite=Lax; Max-Age=2592000

{
  "accessToken": "eyJhbGc...",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "user": { ... }
}
```

**Tu navegador automáticamente:**
- ✅ Guarda el `refresh_token` en una cookie HttpOnly
- ✅ TÚ (frontend) debes guardar el `accessToken` en localStorage

```javascript
localStorage.setItem('accessToken', response.accessToken);
// La cookie se guardó automáticamente, no debes hacer nada
```

---

### Paso 2: Hacer requests autenticados
```http
GET /api/auth/status
Authorization: Bearer eyJhbGc...
```

**TÚ envías:**
- ✅ El `accessToken` del localStorage en el header `Authorization: Bearer`
- ❌ NO envías el refresh token (está en la cookie, automático)

```javascript
const accessToken = localStorage.getItem('accessToken');

fetch('/api/auth/status', {
  headers: {
    'Authorization': `Bearer ${accessToken}`
  }
});
```

---

### Paso 3: Access token expira (después de 30 días)
```http
POST /api/auth/refresh
```

**El navegador envía automáticamente:**
```
Cookie: refresh_token=eyJhbGc...
```

**NO necesitas enviar nada especial** (la cookie se envía automáticamente).

```javascript
// Simplemente llamar al endpoint, las cookies se envían automáticas
fetch('/api/auth/refresh', {
  method: 'POST',
  credentials: 'include'  // ← IMPORTANTE: permite enviar cookies
});
```

**Respuesta:**
```json
{
  "accessToken": "eyJhbGc...",  ← Token nuevo
  "tokenType": "Bearer",
  "expiresIn": 2592000
}
```

**TÚ actualizas localStorage:**
```javascript
const newAccessToken = response.accessToken;
localStorage.setItem('accessToken', newAccessToken);

// El refresh_token cookie se actualiza automáticamente en el response header Set-Cookie
```

---

### Paso 4: Logout
```http
POST /api/auth/logout
Authorization: Bearer eyJhbGc...
```

**Respuesta:**
```http
HTTP/1.1 200 OK
Set-Cookie: refresh_token=; Max-Age=0; Path=/

{
  "success": true,
  "message": "Logged out successfully"
}
```

**Qué pasa:**
- ✅ Cookie `refresh_token` se borra (Max-Age=0)
- ✅ Access token en localStorage se invalida (en el backend)
- ✅ Debes borrar localStorage:

```javascript
localStorage.removeItem('accessToken');
// Cookie se borró automáticamente
```

---

## 🌐 CÓMO SWAGGER MANEJA LOS TOKENS

### ❌ Problema: Las cookies HttpOnly no se ven en DevTools

Las cookies `HttpOnly` están **protegidas contra XSS** y no se pueden ver en DevTools (por seguridad). Pero Swagger las maneja automáticamente:

### ✅ Solución 1: Usar el botón "Authorize" en Swagger

**Para endpoints autenticados:**

1. **Login primero:**
   - POST `/api/auth/login`
   - Click "Try it out"
   - Ingresa credenciales
   - Click "Execute"
   - **Swagger almacena automáticamente** la cookie y el token

2. **Click en botón "Authorize" (arriba a la derecha)**
   - Pega el `accessToken` del response anterior
   - Formato: `Bearer eyJhbGc...`
   - Click "Authorize"

3. **Swagger ahora:**
   - ✅ Envía la cookie `refresh_token` automáticamente
   - ✅ Envía el header `Authorization: Bearer <token>` en todos los requests

### ✅ Solución 2: Usar el endpoint `/api/auth/refresh` en Swagger

Si necesitas probar el refresh:

1. **POST `/api/auth/refresh`**
   - Click "Try it out"
   - Dejar body vacío: `{}`
   - Click "Execute"
   - **Swagger envía automáticamente la cookie**

2. **Copiar el nuevo `accessToken`**
   - Click "Authorize" de nuevo
   - Actualizar con el nuevo token

---

## 📝 RESUMEN: QUÉ HACE SWAGGER AUTOMÁTICAMENTE

| Acción | Manual | Automático |
|--------|--------|-----------|
| Guardar accessToken | ❌ NO | ✅ Desde respuesta |
| Enviar accessToken en header | ✅ SÍ (después de Authorize) | ✅ Después de Authorize |
| Guardar refresh_token cookie | ✅ SÍ (navegador) | ✅ El navegador lo hace |
| Enviar refresh_token cookie | ❌ NO (HttpOnly) | ✅ Automático |
| Renovar access token | ✅ Llamar /refresh | ✅ Manual cuando expire |

---

## 🔧 TABLA DE CONFIGURACIÓN

### Para endpoints PÚBLICOS en Swagger
```
✅ No requiere "Authorize"
✅ No requiere Authorization header
```

### Para endpoints AUTENTICADOS en Swagger
```
✅ Requiere:
   1. Click "Authorize"
   2. Pegar: Bearer <accessToken>
   3. Click "Authorize"

✅ Swagger envía automáticamente:
   - Cookie: refresh_token=<token>
   - Header: Authorization: Bearer <token>
```

### Para /api/auth/refresh
```
✅ No requiere "Authorize"
✅ Swagger envía automáticamente:
   - Cookie: refresh_token=<token>

✅ Respuesta:
   - Nuevo accessToken
   - Cookie actualizada (Set-Cookie)
```

---

## ⚠️ ERRORES COMUNES EN SWAGGER

### Error: "401 Unauthorized" en endpoint autenticado
**Causa:** No hiciste click en "Authorize" o el token expiró

**Solución:**
1. Login nuevamente: `POST /api/auth/login`
2. Click "Authorize"
3. Pega el nuevo `accessToken`

### Error: "Cookie not found" en /api/auth/refresh
**Causa:** No hiciste login previamente (no hay cookie)

**Solución:**
1. `POST /api/auth/login` primero
2. Luego llamar `POST /api/auth/refresh`

### No ves el token en la respuesta
**Causa:** El `accessToken` está en el JSON, la cookie HttpOnly NO se ve (por seguridad)

**Solución:**
- Copia el `accessToken` del JSON
- La cookie está ahí pero no se ve (está protegida)

---

## 🎓 RESUMEN FINAL

```
Access Token (30 días):
├─ Ubicación: localStorage (TÚ lo guardas)
├─ Se envía en: Header "Authorization: Bearer <token>"
├─ Usado en: TODOS los requests autenticados
└─ Cuando expira: Llama a POST /api/auth/refresh

Refresh Token (30 días):
├─ Ubicación: Cookie HttpOnly (navegador lo guarda)
├─ Se envía en: Automático (la cookie se envía sola)
├─ Usado en: Solo POST /api/auth/refresh
├─ Seguridad: No se ve en DevTools (está protegido)
└─ Cuando expira: Usuario debe hacer login nuevamente

Swagger:
├─ Maneja cookies automáticamente
├─ Requiere click "Authorize" para endpoints autenticados
├─ Permite probar /api/auth/refresh sin problemas
└─ NO muestra cookies (por seguridad), pero están ahí
```

---

**Última actualización:** 28-Octubre-2025
**Mantenedor:** Equipo de desarrollo
