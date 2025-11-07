# Guía Rápida: Verificar Fase 2 - Sistema JWT en Blade

**Tiempo estimado**: 10-20 minutos

---

## ✅ PASO 1: Verificar Archivos (1 minuto)

```bash
ls -lh resources/js/lib/auth/
```

**Esperado**: 5 archivos
```
TokenManager.js      (15KB)
AuthChannel.js       (8.7KB)
PersistenceService.js (11KB)
HeartbeatService.js  (8.5KB)
index.js             (1.6KB)
TOTAL: ~44KB
```

---

## ✅ PASO 2: Verificar Sintaxis (1 minuto)

```bash
node -c resources/js/lib/auth/TokenManager.js
node -c resources/js/lib/auth/AuthChannel.js
node -c resources/js/lib/auth/PersistenceService.js
node -c resources/js/lib/auth/HeartbeatService.js
node -c resources/js/lib/auth/index.js
```

**Esperado**: Sin output = sin errores de sintaxis

---

## ✅ PASO 3: Test Básico en Navegador (5 minutos)

### Opción A: Static HTML Test

```bash
# Abrir en navegador
open http://localhost:8000/test-jwt.html

# O via terminal
curl http://localhost:8000/test-jwt.html | grep "✅" | head -10
```

**Verifica**:
- ✅ LocalStorage API disponible
- ✅ Timer/setTimeout funciona
- ✅ Fetch API disponible
- ✅ IndexedDB disponible
- ✅ BroadcastChannel o fallback

**Esperado**: Todos los tests en verde (✅)

---

## ✅ PASO 4: Test Interactivo con API Real (10-15 minutos)

### URL
```
http://localhost:8000/test/jwt-interactive
```

### Test Secuencial

#### 1️⃣ **Login**
- Email: `test@example.com` (cambiar si no existe)
- Password: (tu contraseña real)
- Click "Login"
- **Esperado**: ✅ Login successful + token guardado

#### 2️⃣ **Inspect Storage**
- Click "Inspect Storage"
- **Esperado**:
  - ✅ `helpdesk_access_token` = JWT token
  - ✅ `helpdesk_token_expiry` = timestamp
  - ✅ `refresh_token` = (HttpOnly cookie, no visible)

#### 3️⃣ **Get Status** (Test Protected Endpoint)
- Click "Get Status"
- **Esperado**: ✅ Retorna user data + roles

#### 4️⃣ **Refresh Token**
- Click "Refresh Token"
- **Esperado**: ✅ Nuevo token generado

#### 5️⃣ **Get Status Again** (Verify new token works)
- Click "Get Status"
- **Esperado**: ✅ Funciona con nuevo token

#### 6️⃣ **View Sessions**
- Click "Get Sessions"
- **Esperado**: ✅ Lista sesiones activas

#### 7️⃣ **Logout**
- Click "Logout"
- **Esperado**: ✅ Tokens limpios, localStorage vacío

---

## ✅ PASO 5: Inspect DevTools (3 minutos)

### Console (F12)

```
Expected logs:
[INFO] Token storage initialized
[SUCCESS] Login successful
[INFO] Token refresh scheduled
[INFO] Heartbeat started
```

Buscar logs con prefijos:
- `[TokenManager]`
- `[AuthChannel]`
- `[PersistenceService]`
- `[HeartbeatService]`

### Application Tab (F12)

**LocalStorage**:
```
helpdesk_access_token → eyJ0eXAi...
helpdesk_token_expiry → 1731012345000
```

**Cookies**:
```
refresh_token → (HttpOnly ✅, no accessible from JS)
```

---

## ✅ PASO 6: Test con cURL (Opcional, 5 minutos)

### 6.1 Login y Capturar Token

```bash
RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}')

TOKEN=$(echo $RESPONSE | jq -r '.data.accessToken')
echo "Token: $TOKEN"
```

### 6.2 Test Protected Endpoint

```bash
curl -X GET http://localhost:8000/api/auth/status \
  -H "Authorization: Bearer $TOKEN" \
  | jq '.'
```

**Esperado**: User data

### 6.3 Test Refresh

```bash
REFRESH_RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -b "refresh_token=..." )

NEW_TOKEN=$(echo $REFRESH_RESPONSE | jq -r '.data.accessToken')
echo "New Token: $NEW_TOKEN"
```

**Esperado**: Nuevo token válido

---

## ✅ PASO 7: Verify Cookie Handling

### En el navegador (F12):

1. Ir a **Application** → **Cookies**
2. Buscar `refresh_token`
3. Verificar:
   - ✅ **HttpOnly**: Checked (no accessible from JavaScript)
   - ✅ **Secure**: Checked (en production, HTTP en dev)
   - ✅ **SameSite**: Lax
   - ✅ **Path**: /
   - ✅ **Expires**: 30 días desde ahora

---

## ✅ CHECKLIST FINAL

- [ ] 5 archivos JS creados en `resources/js/lib/auth/`
- [ ] Sintaxis JavaScript válida (sin errores con `node -c`)
- [ ] Test HTML (`/test-jwt.html`) - todos los tests en verde
- [ ] Test interactivo (`/test/jwt-interactive`) - login → status → refresh → logout
- [ ] LocalStorage guardando tokens correctamente
- [ ] Access token funciona en endpoints protegidos
- [ ] Refresh token actualiza el access token
- [ ] HttpOnly cookie configurada correctamente
- [ ] DevTools muestra logs esperados
- [ ] API endpoints responden correctamente

---

## 🎯 RESULTADOS ESPERADOS

### Si TODO está en VERDE ✅

```
✅ TokenManager.js - Token storage + auto-refresh
✅ AuthChannel.js - Multi-tab sync
✅ PersistenceService.js - IndexedDB persistence
✅ HeartbeatService.js - Session keepalive
✅ Fetch wrapper - Auto-refresh on 401
✅ LocalStorage - Tokens guardados
✅ Cookies - HttpOnly refresh_token
✅ API Integration - Endpoints funcionando
✅ Multi-tab - BroadcastChannel working
✅ Security - Proper error handling

FASE 2: ✅ VERIFICACIÓN COMPLETADA
READY FOR PHASE 3: Alpine.js Integration
```

---

## 🔴 TROUBLESHOOTING

### Error: "No token found"
→ Ejecutar Login primero

### Error: "401 Unauthorized"
→ Token expirado, hacer refresh

### Error: "CORS error"
→ Asegurar que Laravel escucha en puerto 8000

### Error: "Network error in fetch"
→ Verificar que el servidor está corriendo: `docker compose up`

### Cookies no aparecen
→ Verificar Application → Cookies (buscar refresh_token)

### LocalStorage vacío
→ Hacer login nuevamente

---

## ⏱️ TIEMPO ESTIMADO POR PASO

| Paso | Duración | Total |
|------|----------|-------|
| 1. Verificar archivos | 1 min | 1 min |
| 2. Verificar sintaxis | 1 min | 2 min |
| 3. Test básico | 5 min | 7 min |
| 4. Test interactivo | 10 min | 17 min |
| 5. DevTools | 3 min | 20 min |
| 6. cURL (opcional) | 5 min | 25 min |
| 7. Cookie check | 2 min | 27 min |

**TOTAL**: ~20 minutos (sin pasos opcionales)

---

## 📝 NOTAS

- **Testing en desarrollo**: Todos los tests están disponibles en dev
- **Testing en producción**: Remover archivos de test antes de desplegar
- **Credenciales**: Cambiar las credenciales de test por reales
- **CORS**: Asegurar que CORS está configurado correctamente en Laravel
- **HTTPS**: En producción, cambiar `Secure=false` a `Secure=true` en cookies

---

## 🚀 PRÓXIMO PASO

Una vez que TODO esté verificado y en verde:

```bash
# Iniciar FASE 3: Alpine.js Integration
# Esto incluye:
# - authStore.js (Alpine store)
# - Blade layouts (guest, onboarding, app)
# - Componentes interactivos
```

---

**Status**: ✅ READY FOR TESTING
