# 🔐 Guía: Dónde está el botón "Authorize" en Swagger

**Problema:** No veo dónde poner el access token en Swagger
**Solución:** Busca el botón "Authorize"

---

## 📍 UBICACIÓN DEL BOTÓN

### En la UI de Swagger:

```
┌─────────────────────────────────────────────────────────┐
│ Helpdesk API    GET    POST    DELETE    [Authorize]   │  ← AQUÍ
└─────────────────────────────────────────────────────────┘
     ↑                                          ↑
   Título                              Botón en esquina superior derecha
```

**El botón "Authorize" está en la ESQUINA SUPERIOR DERECHA de Swagger UI.**

---

## 🎬 PASOS PARA AUTORIZAR

### 1. Busca el botón "Authorize"

En la esquina superior derecha de http://localhost:8000/docs, verás:

```
[Authorize]  o  [🔒 Authorize]
```

### 2. Haz click en "Authorize"

Se abrirá un modal con campos para ingresar credenciales.

### 3. En el campo "bearerAuth"

Verás un campo de texto que dice:
```
bearerAuth
[____________________________________________]
```

### 4. Pega tu token en formato:

```
Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**O solo el token sin "Bearer"** (Swagger lo agrega automáticamente):
```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### 5. Click "Authorize"

Botón azul "Authorize" en el modal.

### 6. Listo

Ahora todos los endpoints autenticados enviarán el header:
```
Authorization: Bearer <tu_token>
```

---

## 🎬 PASO A PASO CON IMÁGENES

### Paso 1: Abre Swagger
```
http://localhost:8000/docs
```

### Paso 2: Busca "POST /api/auth/login"
Desplázate hasta encontrar la sección "Authentication".

### Paso 3: Haz click en "Try it out"
```
┌─────────────────────────────────┐
│ POST /api/auth/login            │
│ Login user                       │
│                                 │
│        [Try it out]  ← CLICK    │
└─────────────────────────────────┘
```

### Paso 4: Ingresa credenciales
```
Request body:
{
  "email": "user@example.com",
  "password": "SecurePass123!"
}
```

### Paso 5: Click "Execute"

Respuesta:
```json
{
  "accessToken": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "refreshToken": "Refresh token set in httpOnly cookie",
  "tokenType": "Bearer",
  "expiresIn": 2592000,
  ...
}
```

### Paso 6: Copia el accessToken

```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Paso 7: Click en "Authorize" (arriba a la derecha)

En la esquina superior derecha de Swagger, verás:
```
[Authorize]
```

### Paso 8: Pega el token

En el campo "bearerAuth":
```
Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### Paso 9: Click "Authorize" (en el modal)

```
┌──────────────────────────────┐
│ bearerAuth                   │
│ [________________________]    │
│                              │
│      [Authorize]  [Close]    │  ← CLICK Authorize
└──────────────────────────────┘
```

### Paso 10: Listo

Ahora ve a cualquier endpoint autenticado como:
- `GET /api/auth/status`
- `GET /api/auth/sessions`
- `POST /api/auth/logout`

**Swagger enviará automáticamente el Authorization header.**

---

## 🔍 VERIFICAR QUE FUNCIONA

Después de hacer click en "Authorize":

1. **Busca** `GET /api/auth/status`
2. **Click** "Try it out"
3. **Click** "Execute"
4. **Deberías ver** respuesta 200 OK (no 401 Unauthorized)

---

## ⚠️ PROBLEMAS COMUNES

### No veo el botón "Authorize"

**Causa:** La página no se cargó correctamente

**Soluciones:**
1. Presiona **F5** (refresh/reload)
2. Presiona **Ctrl+Shift+Delete** (limpiar caché)
3. Abre en **incógnito** (Ctrl+Shift+N)
4. Intenta otra URL: `http://localhost:8000/api/documentation`

### Veo el botón pero dice "🔒 (locked)"

**Significa:** No hay token ingresado

**Solución:**
- Click en el botón
- Ingresa el token
- Click "Authorize"

### Después de "Authorize" sigo viendo "401 Unauthorized"

**Causa 1:** Token inválido o expirado
- **Solución:** Haz login nuevamente

**Causa 2:** El token no se está enviando
- **Solución:** Verifica en DevTools → Network que el header esté ahí

### No veo "Parameters" en los endpoints autenticados

**Esto es NORMAL.** Los parámetros no aparecen porque:
- El Authorization header es global (aplica a todos)
- Se configura una vez con "Authorize"
- No es un parámetro por endpoint

---

## 🎓 RESUMEN

| Paso | Acción | Ubicación |
|------|--------|-----------|
| 1 | Login | `POST /api/auth/login` |
| 2 | Copiar accessToken | Respuesta del login |
| 3 | Click "Authorize" | Esquina superior derecha |
| 4 | Pegar token | Campo "bearerAuth" |
| 5 | Click "Authorize" | Botón en el modal |
| 6 | Probar endpoints | `GET /api/auth/status`, etc. |

---

## 🚀 QUICK REFERENCE

```
URL: http://localhost:8000/docs
Button: Authorize (top right)
Format: Bearer <token> OR just <token>
Applies to: All authenticated endpoints automatically
Duration: Until you close Swagger or logout
Refresh: Click Authorize again with new token
```

---

**Última actualización:** 28-Octubre-2025
