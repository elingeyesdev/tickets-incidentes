# 🔍 PLAN DE DIAGNÓSTICO JWT - INVESTIGACIÓN EXHAUSTIVA

## 📋 RESUMEN

He añadido **logging exhaustivo** en todos los puntos críticos del flujo de autenticación. Ahora necesitamos ejecutar escenarios específicos para diagnosticar los dos problemas reportados.

---

## 🎯 PROBLEMAS A DIAGNOSTICAR

### **Problema 1: Login después de 2 días muestra "sesión expirada"**
**Síntoma:** Login exitoso → Redirige → "Sesión expirada" → Segundo login funciona

### **Problema 2: Logout → "/" muestra "sesión expirada"**
**Síntoma:** Logout exitoso → Navegar a "/" → Mensaje "Tu sesión ha expirado"

---

## 🛠️ INSTRUCCIONES DE PRUEBA

### **PASO 1: Preparar el entorno**

1. Limpiar logs actuales:
```bash
docker exec helpdesk-app php artisan log:clear
# O manualmente
rm storage/logs/laravel-*.log
```

2. Asegurarse de que el frontend esté compilado:
```bash
npm run dev
# O si usas build
npm run build
```

---

### **PASO 2: Reproducir Problema 1 (Login después de 2 días)**

#### **Escenario A: Simular sesión expirada**

1. **Hacer login normalmente**
   - Ve a `/login`
   - Inicia sesión
   - Observa que funciona correctamente

2. **Simular token expirado (sin esperar 2 días)**
   
   Opción 1 - Modificar token en localStorage:
   - Abre DevTools (F12) → Console
   - Ejecuta:
   ```javascript
   // Ver token actual
   console.log('Token actual:', localStorage.getItem('access_token'));
   
   // Corromper el token (cambiar algunos caracteres)
   let token = localStorage.getItem('access_token');
   localStorage.setItem('access_token', token.substr(0, 50) + 'CORRUPTED' + token.substr(50));
   
   // Verificar
   console.log('Token corrupto:', localStorage.getItem('access_token'));
   ```

   Opción 2 - Usar cookie expirada:
   - DevTools → Application → Cookies
   - Edita `jwt_token` y `refresh_token` (cambia algunos caracteres)

3. **Recargar la página**
   - Presiona F5
   - Observa el comportamiento

4. **Capturar logs**
   - Backend: `tail -f storage/logs/laravel.log`
   - Frontend: DevTools → Console (filtra por `[JWT`, `[ROUTE`, `[LOGOUT`, `[DASHBOARD]`)

#### **Escenario B: Simular solo refresh token expirado**

1. Abre DevTools → Application → Cookies
2. Elimina SOLO la cookie `refresh_token`
3. Mantén `jwt_token` y `access_token` en localStorage
4. Recarga la página
5. Observa qué sucede

---

### **PASO 3: Reproducir Problema 2 (Logout → "/" muestra sesión expirada)**

1. **Hacer login normalmente**

2. **Hacer logout**
   - Haz clic en el botón de logout
   - Observa los logs en la consola del navegador

3. **Verificar estado después de logout**
   - DevTools → Console:
   ```javascript
   // Ver qué quedó en localStorage
   console.log('localStorage keys:', Object.keys(localStorage));
   console.log('access_token:', localStorage.getItem('access_token'));
   console.log('active_role:', localStorage.getItem('active_role'));
   ```
   
   - DevTools → Application → Cookies:
   ```
   Verificar si quedan:
   - jwt_token (NO debería estar)
   - refresh_token (NO debería estar)
   ```

4. **Navegar a "/"**
   - En la URL, escribe directamente: `http://localhost/`
   - Presiona Enter
   - Observa qué mensaje muestra

5. **Capturar logs**
   - Backend: Busca `[ROUTE /]` en logs
   - Frontend: Busca `[LOGOUT FRONTEND]` en consola

---

### **PASO 4: Escenario de control (Login/Logout normal)**

1. **Login**
2. **Espera 5 segundos**
3. **Logout**
4. **Ve a "/"**
5. Debería mostrar la página de bienvenida SIN mensaje de "sesión expirada"

---

## 📊 LOGS A REVISAR

### **Backend (Laravel)**

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log | grep -E '\[ROUTE|\[JWT|\[LOGOUT|\[DASHBOARD\]'

# Ver últimas 100 líneas de logs relevantes
tail -n 100 storage/logs/laravel.log | grep -E '\[ROUTE|\[JWT|\[LOGOUT|\[DASHBOARD\]'
```

**Buscar específicamente:**

- `[ROUTE /] Welcome page accessed` - Ver si detecta cookies después de logout
- `[JWT MIDDLEWARE] Request received` - Ver el estado de cookies al entrar a dashboard
- `[JWT MIDDLEWARE] Server-side auto-refresh` - Ver si intenta refresh automático
- `[JWT MIDDLEWARE] Redirecting to login with session_expired reason` - Ver por qué redirige
- `[LOGOUT] Logout initiated` - Ver el proceso de logout
- `[DASHBOARD] Redirect method called` - Ver si llega al dashboard

### **Frontend (Browser Console)**

Buscar en consola del navegador:

- `[LOGOUT FRONTEND]` - Todo el proceso de logout
- `[Auth Check]` - Verificación de autenticación al cargar
- `[TokenManager]` - Gestión de tokens
- `[Server Refresh]` - Tokens inyectados por server-side refresh

---

## 🔬 ANÁLISIS ESPERADO

### **Para Problema 1 (Login después de 2 días)**

**Hipótesis:**
1. El token en localStorage está corrupto/expirado
2. El refresh token también expiró (después de 2 días)
3. El server-side auto-refresh falla y redirige a login
4. Las cookies NO se limpian correctamente
5. Al hacer segundo login, las cookies viejas interfieren

**Logs clave a buscar:**
```
[JWT MIDDLEWARE] Token extraction result
  token_found: true/false
  
[JWT MIDDLEWARE] Web request, checking for refresh token
  has_refresh_token: true/false

[JWT MIDDLEWARE] Server-side auto-refresh failed
  error: "..."
  
[ROUTE /] Welcome page accessed
  has_jwt_cookie: true/false  <-- Si es TRUE después de logout, problema aquí
```

### **Para Problema 2 (Logout → "/")**

**Hipótesis:**
1. El logout NO está limpiando la cookie `jwt_token`
2. La cookie `jwt_token` persiste después de logout
3. La ruta "/" detecta la cookie y redirige a dashboard
4. El dashboard intenta autenticar pero el token está blacklisted
5. Redirige a login con "sesión expirada"

**Logs clave a buscar:**
```
[LOGOUT FRONTEND] localStorage after cleanup:
  <- Debe estar vacío o casi vacío

[LOGOUT] Creating response with cleared refresh_token cookie
  <- Verifica que se cree la cookie vacía

[ROUTE /] Welcome page accessed
  has_jwt_cookie: true/false  <-- Si es TRUE, cookie no se limpió
  jwt_cookie_length: X  <-- Si > 0, cookie sigue ahí
```

---

## 🐛 POSIBLES CAUSAS Y SOLUCIONES

### **Causa potencial 1: Cookie `jwt_token` no se limpia en logout**

**Problema:** El logout solo limpia `refresh_token`, pero NO `jwt_token`

**Verificar en:** `SessionController.php` línea 224-239

**Solución potencial:**
```php
return response()
    ->json([...], 200)
    ->cookie('refresh_token', '', 0, ...)  // Ya existe
    ->cookie('jwt_token', '', 0, '/', null, !app()->isLocal(), false, false, 'lax');  // AÑADIR ESTO
```

---

### **Causa potencial 2: Cookies con SameSite=Strict bloquean limpieza**

**Problema:** La cookie `refresh_token` usa `SameSite=strict`, lo que puede impedir que se limpie en ciertos navegadores

**Verificar en:** 
- `SessionController.php` línea 238 (logout)
- `RequireJWTAuthentication.php` línea 139 (refresh)

**Solución potencial:**
Cambiar `'strict'` a `'lax'` en ambos lugares

---

### **Causa potencial 3: Server-side refresh inyecta token pero localStorage no se actualiza**

**Problema:** El middleware refresca pero el JavaScript no lo detecta

**Verificar en:** `authenticated.blade.php` línea 194-209

**El código ya tiene esto, verificar que se ejecute:**
```javascript
@if(request()->attributes->has('server_refreshed_token'))
    localStorage.setItem('access_token', serverToken.access_token);
@endif
```

---

### **Causa potencial 4: Redirección a "/" después de logout no espera respuesta**

**Problema:** El frontend redirige a `/login` inmediatamente después de logout, pero la respuesta con cookies limpias no se aplica

**Verificar en:** `authenticated.blade.php` línea 315

**Solución potencial:**
```javascript
// Esperar un momento antes de redirigir para que las cookies se limpien
await new Promise(resolve => setTimeout(resolve, 100));
window.location.href = '/login';
```

---

## 📝 REPORTE DE RESULTADOS

Por favor ejecuta las pruebas y comparte:

1. **Logs del backend** (últimas 100 líneas con filtro)
2. **Screenshots de la consola del navegador** (filtrada por `[LOGOUT`, `[JWT`, `[ROUTE`)
3. **Estado de cookies** después de logout (screenshot de DevTools → Application → Cookies)
4. **Estado de localStorage** después de logout (ejecuta el comando JS arriba)

Con esta información podré identificar la causa exacta y proponer la solución definitiva.

---

## 🚀 SIGUIENTE PASO

Una vez tengas los logs, compártelos aquí y haré:
1. ✅ Análisis de logs
2. ✅ Identificación de causa raíz
3. ✅ Propuesta de solución específica (no suposiciones)
4. ✅ Implementación de fix
5. ✅ Verificación de que el problema está resuelto
