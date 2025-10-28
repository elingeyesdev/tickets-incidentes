# 🔄 Tutorial: Cómo probar Refresh Token en Swagger

**Fecha:** 28-Octubre-2025
**URL:** http://localhost:8000/docs

---

## 📋 PROBLEMA: Cookies HttpOnly no se ven en Swagger

Las cookies `HttpOnly` están **protegidas contra XSS** y Swagger no las muestra. Pero tenemos **3 formas** de testear el refresh token:

---

## ✅ SOLUCIÓN 1: Usar Header `X-Refresh-Token` (Recomendado para Swagger)

### Paso 1: Login para obtener refresh token

1. **Abre Swagger:** http://localhost:8000/docs
2. **Busca:** `POST /api/auth/login`
3. **Click:** "Try it out"
4. **Ingresa:**
   ```json
   {
     "email": "user@example.com",
     "password": "SecurePass123!"
   }
   ```
5. **Click:** "Execute"

**Respuesta:**
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshToken": "Refresh token set in httpOnly cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  ...
}
```

⚠️ **El refresh token está en la cookie (no lo ves aquí)**, pero la respuesta te lo confirma.

---

### Paso 2: Obtener el refresh token de la cookie

Aunque no lo ves en Swagger, **el navegador ya lo guardó**. Pero para probarlo en Swagger, necesitas:

**Opción A: Ver la cookie en DevTools**
1. **F12** → Abre DevTools
2. **Application** tab
3. **Cookies** → `localhost:8000`
4. **Busca:** `refresh_token`
5. **Copia el valor**

**Opción B: Usar el endpoint sin nada (automático)**
```
POST /api/auth/refresh
(vacío, la cookie se envía automáticamente)
```

---

### Paso 3: Probar `/api/auth/refresh` con Header en Swagger

**Para enviar el refresh token manualmente (útil para testing):**

1. **Busca:** `POST /api/auth/refresh`
2. **Click:** "Try it out"
3. **Busca campo:** `X-Refresh-Token` (aparece en los parámetros)
4. **Pega ahí el valor de la cookie:**
   ```
   eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
   ```
5. **Dejar body vacío:** `{}`
6. **Click:** "Execute"

**Respuesta:**
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "message": "Token refreshed successfully. New refresh token set in HttpOnly cookie."
}
```

✅ **Éxito:** Refresh token renovado y nuevo token en la cookie

---

## ✅ SOLUCIÓN 2: Dejar que Swagger envíe la cookie automáticamente

**Es más simple:**

1. **Login:** `POST /api/auth/login` (ya lo hiciste arriba)
2. **Swagger automáticamente guardó la cookie** (no la ves, pero está)
3. **Ir a:** `POST /api/auth/refresh`
4. **Click:** "Try it out"
5. **Dejar body vacío:** `{}`
6. **NO ingresar nada en headers**
7. **Click:** "Execute"

**Swagger envía automáticamente:**
```
Cookie: refresh_token=<token>
```

**Respuesta:**
```json
{
  "accessToken": "eyJhbGc...",
  ...
}
```

✅ **La cookie se envía automáticamente** sin que hagas nada

---

## ✅ SOLUCIÓN 3: Enviar en Body (no recomendado, pero funciona)

Si Swagger tuviera problemas con headers/cookies:

1. **Busca:** `POST /api/auth/refresh`
2. **Click:** "Try it out"
3. **Body:**
   ```json
   {
     "refreshToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
   }
   ```
4. **Click:** "Execute"

⚠️ **NO recomendado en producción** (menos seguro que header/cookie)

---

## 🔐 ¿QUÉ PASA EN LA RESPUESTA?

En la respuesta de `/api/auth/refresh`, verás:

```json
{
  "accessToken": "eyJhbGc...",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  "message": "Token refreshed successfully. New refresh token set in HttpOnly cookie."
}
```

**Lo que NO ves (pero está):**

En los headers HTTP (mira las pestañas en Swagger):

```
Set-Cookie: refresh_token=<nuevo_token>; HttpOnly; SameSite=Strict; Max-Age=2592000; Path=/
```

**El navegador automáticamente:**
- ✅ Lee el header `Set-Cookie`
- ✅ Actualiza la cookie `refresh_token`
- ✅ No debes hacer nada

---

## 📊 TABLA: TRES FORMAS DE PROBAR REFRESH TOKEN

| Método | Swagger | Producción | Seguridad | Complejidad |
|--------|---------|-----------|-----------|------------|
| **Header X-Refresh-Token** | ✅ Sí | ❌ No (solo Swagger) | ⭐⭐ | Baja |
| **Cookie automática** | ✅ Sí | ✅ Sí | ⭐⭐⭐ | Muy baja |
| **Body refreshToken** | ✅ Sí | ❌ No (menos seguro) | ⭐ | Baja |

**Recomendación:**
- **En Swagger:** Usa Header `X-Refresh-Token` (más visible)
- **En Producción:** Cookie automática (más segura)

---

## 🎯 FLUJO COMPLETO PASO A PASO

### 1️⃣ Login
```
POST /api/auth/login
Body: {
  "email": "user@example.com",
  "password": "SecurePass123!"
}

Respuesta:
{
  "accessToken": "eyJhbGc...",
  "refreshToken": "Refresh token set in httpOnly cookie"
}

Cookie guardada: refresh_token=<token> (HttpOnly, no se ve)
```

---

### 2️⃣ Autorizar endpoints autenticados
```
Click "Authorize" (arriba a la derecha)

Pega: Bearer eyJhbGc...

Click "Authorize"
```

---

### 3️⃣ Probar endpoint autenticado
```
GET /api/auth/status

Swagger envía automáticamente:
Authorization: Bearer eyJhbGc...

Respuesta: 200 OK
```

---

### 4️⃣ Refrescar token (cuando expire)
```
POST /api/auth/refresh

Opción A: No hacer nada (cookie automática)
Opción B: Pegar en header X-Refresh-Token
Opción C: Pegar en body refreshToken

Respuesta:
{
  "accessToken": "eyJhbGc...",
  "tokenType": "Bearer"
}

Cookie actualizada: refresh_token=<nuevo_token>
```

---

### 5️⃣ Actualizar Authorization con nuevo token
```
Click "Authorize"

Pega nuevo token: Bearer eyJhbGc...

Click "Authorize"

Listo para los próximos 30 días
```

---

## 🔍 CÓMO VER LA COOKIE EN DEVTOOLS

Si quieres verificar que la cookie se guardó:

1. **F12** → DevTools
2. **Pestaña:** "Application" (o "Storage" en Firefox)
3. **Left menu:** "Cookies"
4. **Selecciona:** `http://localhost:8000`
5. **Busca:** `refresh_token`
6. **Atributos:**
   - ✅ `HttpOnly` = Sí (no accesible desde JS, protegido contra XSS)
   - ✅ `Secure` = Sí (solo HTTPS en prod)
   - ✅ `SameSite` = Strict (protección CSRF)
   - ✅ `Max-Age` = 2592000 (30 días)

---

## ⚠️ ERRORES COMUNES

### Error: "401 Invalid or missing refresh token"

**Causa 1:** No hiciste login primero
- **Solución:** `POST /api/auth/login` primero

**Causa 2:** Copiaste mal el token
- **Solución:** Copia exactamente de DevTools → Application → Cookies

**Causa 3:** Token expiró
- **Solución:** El refresh token dura 30 días. Si pasó, haz login nuevamente.

---

### Error: "Refresh token not found in header/cookie"

**Causa:** No estás enviando el refresh token en ningún lugar
- **Solución A:** Usa header `X-Refresh-Token`
- **Solución B:** Usa body `{"refreshToken": "..."}`
- **Solución C:** Asegúrate de haber hecho login primero

---

### No veo el campo `X-Refresh-Token` en Swagger

**Causa:** El endpoint no tiene documentación OpenAPI para el header
- **Solución:** Ya lo hemos agregado. Regenera Swagger:
  ```bash
  docker compose exec -T app php artisan l5-swagger:generate
  ```

---

## 🎓 RESUMEN

| Aspecto | Detalle |
|---------|---------|
| **Refresh token guardado en** | Cookie HttpOnly (automático) |
| **Se envía automáticamente** | Sí, en TODOS los requests |
| **¿Se ve en DevTools?** | Sí (Application → Cookies) |
| **¿Se ve en Swagger?** | No (HttpOnly lo protege) |
| **Cómo probarlo en Swagger** | Header `X-Refresh-Token` |
| **Duración** | 30 días |
| **Endpoint para renovar** | `POST /api/auth/refresh` |
| **Seguridad en cookies** | HttpOnly + Secure + SameSite=Strict |

---

## 🚀 QUICK START

**Para probar refresh token en Swagger ahora:**

1. `POST /api/auth/login` → Copia accessToken
2. Click "Authorize" → Pega `Bearer <accessToken>`
3. `POST /api/auth/refresh` → Click "Execute"
4. Nuevo token en respuesta → Actualiza "Authorize"

**Listo.** ✅

---

**Última actualización:** 28-Octubre-2025
**Mantenedor:** Equipo de desarrollo
