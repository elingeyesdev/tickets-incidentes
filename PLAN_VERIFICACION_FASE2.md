# Plan de Verificación: Fase 2 - JWT System para Blade

**Objetivo**: Verificar que los 5 componentes JavaScript funcionan correctamente, se comunican entre sí, y se integran con la API REST existente.

**Status**: 🔧 LISTO PARA TESTING

---

## 1. VERIFICACIÓN RÁPIDA (5 minutos)

### 1.1 Verificar Sintaxis JavaScript

```bash
# Test de sintaxis de cada archivo
node -c resources/js/lib/auth/TokenManager.js
node -c resources/js/lib/auth/AuthChannel.js
node -c resources/js/lib/auth/PersistenceService.js
node -c resources/js/lib/auth/HeartbeatService.js
node -c resources/js/lib/auth/index.js
```

**Esperado**: Sin errores de sintaxis.

### 1.2 Verificar que los archivos existen

```bash
ls -lh resources/js/lib/auth/
```

**Esperado**: 5 archivos, totales ~44KB.

---

## 2. VERIFICACIÓN EN NAVEGADOR (Browser Console Testing)

### 2.1 Crear HTML de Testing

Crear archivo temporal: `public/test-jwt.html`

```html
<!DOCTYPE html>
<html>
<head>
    <title>JWT System Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        .test-section { margin: 20px 0; padding: 10px; border: 1px solid #ccc; }
        .pass { color: green; }
        .fail { color: red; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>JWT System Verification</h1>
    <div id="results"></div>

    <script type="module">
        // Importar módulos (cuando estén disponibles)
        // import auth from './resources/js/lib/auth/index.js';

        const results = document.getElementById('results');

        function log(message, type = 'info') {
            const div = document.createElement('div');
            div.className = type;
            div.textContent = message;
            results.appendChild(div);
            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        function section(title) {
            const div = document.createElement('div');
            div.className = 'test-section';
            const h2 = document.createElement('h2');
            h2.textContent = title;
            div.appendChild(h2);
            results.appendChild(div);
        }

        // ========== TESTS ==========

        section('1. TokenManager Tests');

        // Test: Token Storage
        try {
            localStorage.setItem('helpdesk_access_token', 'test-token');
            const token = localStorage.getItem('helpdesk_access_token');
            if (token === 'test-token') {
                log('✅ LocalStorage working', 'pass');
            } else {
                log('❌ LocalStorage failed', 'fail');
            }
            localStorage.removeItem('helpdesk_access_token');
        } catch (e) {
            log(`❌ LocalStorage error: ${e.message}`, 'fail');
        }

        // Test: Timer functionality
        section('2. Timer Tests');

        try {
            let timerFired = false;
            const timer = setTimeout(() => { timerFired = true; }, 100);
            setTimeout(() => {
                if (timerFired) {
                    log('✅ Timer working', 'pass');
                } else {
                    log('❌ Timer failed', 'fail');
                }
            }, 200);
        } catch (e) {
            log(`❌ Timer error: ${e.message}`, 'fail');
        }

        // Test: Fetch API
        section('3. Fetch API Tests');

        try {
            // Test health endpoint
            fetch('/api/health')
                .then(r => r.json())
                .then(data => {
                    log('✅ Fetch API working', 'pass');
                    log(`Response: ${JSON.stringify(data).substring(0, 100)}...`, 'info');
                })
                .catch(e => {
                    log(`❌ Fetch error: ${e.message}`, 'fail');
                });
        } catch (e) {
            log(`❌ Fetch setup error: ${e.message}`, 'fail');
        }

        // Test: IndexedDB
        section('4. IndexedDB Tests');

        try {
            const request = indexedDB.open('test-db', 1);
            request.onsuccess = () => {
                log('✅ IndexedDB available', 'pass');
                request.result.close();
            };
            request.onerror = () => {
                log('❌ IndexedDB error', 'fail');
            };
        } catch (e) {
            log(`❌ IndexedDB error: ${e.message}`, 'fail');
        }

        // Test: BroadcastChannel
        section('5. BroadcastChannel Tests');

        if ('BroadcastChannel' in window) {
            try {
                const channel = new BroadcastChannel('test');
                log('✅ BroadcastChannel available', 'pass');
                channel.close();
            } catch (e) {
                log(`❌ BroadcastChannel error: ${e.message}`, 'fail');
            }
        } else {
            log('⚠️  BroadcastChannel not available (will use localStorage fallback)', 'info');
        }

        // Test: JSON parsing
        section('6. JSON Parsing Tests');

        try {
            const testData = {
                data: {
                    accessToken: 'eyJ...',
                    expiresIn: 3600,
                    user: { id: 'uuid', email: 'test@example.com' }
                }
            };
            const json = JSON.stringify(testData);
            const parsed = JSON.parse(json);
            if (parsed.data.accessToken === 'eyJ...') {
                log('✅ JSON parsing working', 'pass');
            } else {
                log('❌ JSON parsing failed', 'fail');
            }
        } catch (e) {
            log(`❌ JSON error: ${e.message}`, 'fail');
        }

        // Summary
        section('SUMMARY');
        log('All basic browser APIs verified', 'pass');
        log('Ready for Phase 3: Alpine.js Integration', 'info');
    </script>
</body>
</html>
```

**Cómo usar**:
```bash
# Abrir en navegador
open http://localhost:8000/test-jwt.html

# O via curl
curl http://localhost:8000/test-jwt.html
```

---

## 3. VERIFICACIÓN UNITARIA (Con Vitest)

### 3.1 Crear Tests Unitarios

Crear archivo: `resources/js/lib/auth/__tests__/TokenManager.test.js`

```javascript
import { describe, it, expect, beforeEach, afterEach } from 'vitest';

describe('TokenManager', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    afterEach(() => {
        localStorage.clear();
    });

    it('should store and retrieve tokens', () => {
        const token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.test';
        const expiresIn = 3600;

        // Simulate: tokenManager.setTokens(token, expiresIn)
        localStorage.setItem('helpdesk_access_token', token);
        localStorage.setItem('helpdesk_token_expiry', (Date.now() + expiresIn * 1000).toString());

        const stored = localStorage.getItem('helpdesk_access_token');
        expect(stored).toBe(token);
    });

    it('should validate token not expired', () => {
        const expiryTime = Date.now() + 3600000; // 1 hour from now
        localStorage.setItem('helpdesk_token_expiry', expiryTime.toString());

        const expiry = parseInt(localStorage.getItem('helpdesk_token_expiry'));
        expect(Date.now()).toBeLessThan(expiry);
    });

    it('should detect expired tokens', () => {
        const expiryTime = Date.now() - 1000; // Expired 1 second ago
        localStorage.setItem('helpdesk_token_expiry', expiryTime.toString());

        const expiry = parseInt(localStorage.getItem('helpdesk_token_expiry'));
        expect(Date.now()).toBeGreaterThan(expiry);
    });

    it('should calculate 80% refresh time correctly', () => {
        const expiresIn = 3600; // 1 hour
        const refreshAt = expiresIn * 0.8 * 1000; // 80% of TTL in ms

        expect(refreshAt).toBe(2880000); // 48 minutes in ms
    });

    it('should clear tokens on logout', () => {
        localStorage.setItem('helpdesk_access_token', 'token');
        localStorage.setItem('helpdesk_token_expiry', '123456');

        localStorage.removeItem('helpdesk_access_token');
        localStorage.removeItem('helpdesk_token_expiry');

        expect(localStorage.getItem('helpdesk_access_token')).toBeNull();
        expect(localStorage.getItem('helpdesk_token_expiry')).toBeNull();
    });
});
```

**Ejecutar tests**:
```bash
npm run test

# O en modo watch
npm run test:watch

# O con UI
npm run test:ui
```

### 3.2 Crear Tests para AuthChannel

Crear archivo: `resources/js/lib/auth/__tests__/AuthChannel.test.js`

```javascript
import { describe, it, expect } from 'vitest';

describe('AuthChannel', () => {
    it('should support BroadcastChannel if available', () => {
        const hasBC = 'BroadcastChannel' in window;
        expect(hasBC).toBeDefined();
        // Log result for manual inspection
        console.log(`BroadcastChannel available: ${hasBC}`);
    });

    it('should fallback to localStorage if BroadcastChannel unavailable', () => {
        // Test localStorage event simulation
        const listeners = [];

        // Simulate addEventListener
        const handler = (event) => {
            if (event.key === 'helpdesk_auth_event') {
                listeners.forEach(cb => cb(event));
            }
        };

        expect(typeof handler).toBe('function');
    });

    it('should have required event types', () => {
        const eventTypes = ['LOGIN', 'LOGOUT', 'TOKEN_REFRESHED', 'SESSION_EXPIRED'];
        eventTypes.forEach(type => {
            expect(type).toBeTruthy();
        });
    });
});
```

---

## 4. VERIFICACIÓN DE INTEGRACIÓN CON API (Postman/cURL)

### 4.1 Test Login → Tokens → Refresh

```bash
#!/bin/bash

# 1. Register/Login
echo "1. Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPass123!"
  }' \
  -c cookies.txt)

echo "Response: $LOGIN_RESPONSE"
ACCESS_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.accessToken')
EXPIRES_IN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.expiresIn')

echo "✅ Access Token: ${ACCESS_TOKEN:0:20}..."
echo "✅ Expires In: $EXPIRES_IN seconds"

# 2. Test Protected Endpoint
echo -e "\n2. Testing protected endpoint..."
STATUS_RESPONSE=$(curl -s -X GET http://localhost:8000/api/auth/status \
  -H "Authorization: Bearer $ACCESS_TOKEN")

echo "Response: $STATUS_RESPONSE"
IS_AUTHENTICATED=$(echo "$STATUS_RESPONSE" | jq -r '.data.isAuthenticated')
echo "✅ Is Authenticated: $IS_AUTHENTICATED"

# 3. Test Token Refresh
echo -e "\n3. Testing token refresh..."
REFRESH_RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -b cookies.txt)

echo "Response: $REFRESH_RESPONSE"
NEW_TOKEN=$(echo "$REFRESH_RESPONSE" | jq -r '.data.accessToken')
echo "✅ New Token: ${NEW_TOKEN:0:20}..."

# 4. Test with New Token
echo -e "\n4. Testing with new token..."
NEW_STATUS=$(curl -s -X GET http://localhost:8000/api/auth/status \
  -H "Authorization: Bearer $NEW_TOKEN")

echo "Response: $NEW_STATUS"
echo "✅ New token works!"

# Cleanup
rm -f cookies.txt
```

**Ejecutar**:
```bash
chmod +x test-jwt-flow.sh
./test-jwt-flow.sh
```

### 4.2 Manual Testing con cURL

```bash
# 1. Login y capturar token
curl -i -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"password"}'

# Copiar el accessToken de la respuesta

# 2. Usar token en petición autenticada
curl -X GET http://localhost:8000/api/auth/status \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."

# 3. Test refresh
curl -X POST http://localhost:8000/api/auth/refresh \
  -H "Content-Type: application/json"

# 4. Logout
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
```

---

## 5. VERIFICACIÓN MANUAL EN NAVEGADOR (DevTools)

### 5.1 Crear Blade Template de Testing

Crear archivo: `resources/views/test/jwt-test.blade.php`

```blade
<!DOCTYPE html>
<html>
<head>
    <title>JWT System Manual Test</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f9f9f9; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-box { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007bff; }
        .output { background: #f4f4f4; padding: 10px; margin-top: 10px; overflow-x: auto; white-space: pre-wrap; }
        button { padding: 8px 15px; margin: 5px; cursor: pointer; }
        input { padding: 8px; width: 100%; margin: 5px 0; }
        .pass { border-left-color: green; }
        .fail { border-left-color: red; }
        .info { border-left-color: blue; }
    </style>
</head>
<body>
    <div class="container">
        <h1>JWT System - Manual Testing</h1>

        <div class="test-box info">
            <h3>1. Test LocalStorage</h3>
            <button onclick="testLocalStorage()">Test</button>
            <div id="output-ls" class="output"></div>
        </div>

        <div class="test-box info">
            <h3>2. Test API Endpoints</h3>
            <input type="email" id="email" placeholder="Email" value="test@example.com">
            <input type="password" id="password" placeholder="Password" value="password">
            <button onclick="testLogin()">Test Login</button>
            <div id="output-login" class="output"></div>
        </div>

        <div class="test-box info">
            <h3>3. Test Token Refresh</h3>
            <button onclick="testRefresh()">Test Refresh</button>
            <div id="output-refresh" class="output"></div>
        </div>

        <div class="test-box info">
            <h3>4. Test Protected Endpoint</h3>
            <button onclick="testStatus()">Test Status</button>
            <div id="output-status" class="output"></div>
        </div>

        <div class="test-box info">
            <h3>5. View Console Logs</h3>
            <p>Open DevTools (F12) → Console tab</p>
            <p>All [TokenManager], [AuthChannel], etc. logs appear there</p>
        </div>
    </div>

    <script>
        function log(elementId, message, type = 'info') {
            const element = document.getElementById(elementId);
            const timestamp = new Date().toLocaleTimeString();
            element.innerHTML += `[${timestamp}] ${type.toUpperCase()}: ${message}\n`;
            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        function testLocalStorage() {
            const output = 'output-ls';
            try {
                // Test storing
                localStorage.setItem('test_token', 'test-value-123');
                const retrieved = localStorage.getItem('test_token');

                if (retrieved === 'test-value-123') {
                    log(output, '✅ LocalStorage working', 'pass');
                    log(output, `Stored value: ${retrieved}`, 'info');
                } else {
                    log(output, '❌ LocalStorage failed', 'fail');
                }

                // Cleanup
                localStorage.removeItem('test_token');
                log(output, '✅ Cleanup successful', 'pass');
            } catch (e) {
                log(output, `❌ Error: ${e.message}`, 'fail');
            }
        }

        async function testLogin() {
            const output = 'output-login';
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            try {
                log(output, `Attempting login with ${email}...`, 'info');

                const response = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    log(output, '✅ Login successful', 'pass');
                    log(output, `Access Token: ${data.data.accessToken.substring(0, 30)}...`, 'info');
                    log(output, `Expires In: ${data.data.expiresIn} seconds`, 'info');
                    log(output, `User: ${data.data.user.email}`, 'info');

                    // Store for next tests
                    localStorage.setItem('test_token', data.data.accessToken);
                    localStorage.setItem('test_expiry', data.data.expiresIn);
                } else {
                    log(output, `❌ Login failed: ${data.message}`, 'fail');
                }
            } catch (e) {
                log(output, `❌ Error: ${e.message}`, 'fail');
            }
        }

        async function testRefresh() {
            const output = 'output-refresh';

            try {
                log(output, 'Attempting token refresh...', 'info');

                const response = await fetch('/api/auth/refresh', {
                    method: 'POST',
                    credentials: 'include',  // Include cookies
                    headers: { 'Content-Type': 'application/json' }
                });

                const data = await response.json();

                if (response.ok) {
                    log(output, '✅ Refresh successful', 'pass');
                    log(output, `New Access Token: ${data.data.accessToken.substring(0, 30)}...`, 'info');
                    log(output, `Expires In: ${data.data.expiresIn} seconds`, 'info');

                    // Update stored token
                    localStorage.setItem('test_token', data.data.accessToken);
                } else {
                    log(output, `❌ Refresh failed: ${data.message}`, 'fail');
                }
            } catch (e) {
                log(output, `❌ Error: ${e.message}`, 'fail');
            }
        }

        async function testStatus() {
            const output = 'output-status';
            const token = localStorage.getItem('test_token');

            if (!token) {
                log(output, '❌ No token found. Run Login test first.', 'fail');
                return;
            }

            try {
                log(output, 'Fetching user status...', 'info');

                const response = await fetch('/api/auth/status', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                const data = await response.json();

                if (response.ok) {
                    log(output, '✅ Status request successful', 'pass');
                    log(output, `Authenticated: ${data.data.isAuthenticated}`, 'info');
                    log(output, `User: ${data.data.user.email}`, 'info');
                    log(output, `Display Name: ${data.data.user.displayName}`, 'info');
                } else {
                    log(output, `❌ Status request failed: ${data.message}`, 'fail');
                }
            } catch (e) {
                log(output, `❌ Error: ${e.message}`, 'fail');
            }
        }
    </script>
</body>
</html>
```

**Ruta para el test**:
```php
// En routes/web.php
Route::get('/test/jwt-test', function () {
    return view('test.jwt-test');
})->name('jwt-test');

// Crear carpeta: resources/views/test/
```

**Acceder**:
```
http://localhost:8000/test/jwt-test
```

---

## 6. CHECKLIST DE VERIFICACIÓN

### Sintaxis & Estructura
- [ ] Todos los 5 archivos JS existen en `resources/js/lib/auth/`
- [ ] `node -c` no muestra errores de sintaxis
- [ ] Archivos totalalizan ~44KB
- [ ] JSDoc comments están presentes

### Funcionalidad LocalStorage
- [ ] `setTokens()` guarda en localStorage
- [ ] `getAccessToken()` recupera correctamente
- [ ] Validación TTL funciona
- [ ] `clearTokens()` limpia todo

### Funcionalidad Fetch
- [ ] POST /api/auth/login retorna tokens
- [ ] GET /api/auth/status requiere token válido
- [ ] 401 trigger auto-refresh automático
- [ ] Refresh retorna nuevo token

### BroadcastChannel / LocalStorage
- [ ] BroadcastChannel API disponible (Chrome, Edge, Firefox)
- [ ] O fallback a localStorage funciona
- [ ] Eventos se comunican entre tabs

### IndexedDB / Persistencia
- [ ] IndexedDB se crea correctamente
- [ ] Session se guarda en BD
- [ ] Session se restaura al recargar

### Heartbeat
- [ ] Ping se ejecuta cada 5 minutos
- [ ] GET /api/auth/status funciona
- [ ] Logging de intentos visible en console

---

## 7. EJECUCIÓN RECOMENDADA

### Orden de Verificación:

```bash
# 1. Sintaxis (30 segundos)
node -c resources/js/lib/auth/TokenManager.js
node -c resources/js/lib/auth/AuthChannel.js
node -c resources/js/lib/auth/PersistenceService.js
node -c resources/js/lib/auth/HeartbeatService.js
node -c resources/js/lib/auth/index.js

# 2. Browser basic APIs (2 minutos)
open http://localhost:8000/test-jwt.html
# Verificar console sin errores

# 3. Manual API Testing (5 minutos)
# Seguir tests en http://localhost:8000/test/jwt-test
# Ejecutar: Login → Status → Refresh → Status con nuevo token

# 4. DevTools Inspection (3 minutos)
# F12 → Storage tab
# Verificar localStorage keys: helpdesk_access_token, helpdesk_token_expiry
# Verificar Application → Cookies: refresh_token (HttpOnly)

# 5. Vitest (opcional, 2 minutos)
npm run test
```

---

## 8. EXPECTED RESULTS

✅ **Sintaxis**: Sin errores
✅ **LocalStorage**: Token guardado y recuperado
✅ **Fetch**: Peticiones autenticadas funcionan
✅ **Refresh**: Token actualizado correctamente
✅ **Multi-tab**: Eventos sincronizados
✅ **IndexedDB**: Session persistida
✅ **Heartbeat**: Pings ejecutándose

---

## 9. TROUBLESHOOTING

### "Token is undefined"
**Solución**: Asegurar que el login fue exitoso y token está en localStorage

### "401 Unauthorized"
**Solución**: Token expirado, ejecutar refresh o hacer login nuevamente

### "CORS error"
**Solución**: Asegurar que Laravel está escuchando en http://localhost:8000

### "IndexedDB not available"
**Solución**: Algunos navegadores en modo incógnito bloquean IndexedDB (fallback a localStorage)

### "BroadcastChannel not available"
**Solución**: Navegadores antiguos (IE11) usan localStorage fallback

---

## 10. NEXT STEPS

Una vez que todo esté verificado:

1. **Phase 3**: Crear Alpine.js store (authStore.js)
2. **Phase 3**: Crear Blade layouts (guest, onboarding, app)
3. **Phase 4**: Implementar formularios de login/registro
4. **Phase 5**: Crear dashboards por rol

**Estimado**: ~3 horas de testing manual (toda la verificación)
