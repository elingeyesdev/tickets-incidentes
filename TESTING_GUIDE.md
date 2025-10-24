# 🧪 Guía de Testing - Sistema de Autenticación

## 📋 Tabla de Contenidos
1. [Instalación](#instalación)
2. [Ejecutar Tests](#ejecutar-tests)
3. [Estructura de Tests](#estructura-de-tests)
4. [Escribir Nuevos Tests](#escribir-nuevos-tests)
5. [Mejores Prácticas](#mejores-prácticas)
6. [Troubleshooting](#troubleshooting)

---

## Instalación

Las dependencias ya están instaladas. Verifica:

```bash
npm ls vitest @testing-library/react msw
```

Deberías ver:
- `vitest@4.0.2`
- `@testing-library/react@16.3.0`
- `msw@2.11.6`

---

## Ejecutar Tests

### 1. **Tests Unitarios (Una sola vez)**
```bash
npm run test
```

Ejecuta todos los tests en `tests/` una vez y muestra el resultado.

### 2. **Tests en Modo Watch (Durante desarrollo)**
```bash
npm run test:watch
```

Rerun automático cuando cambies código. Perfecto mientras desarrollas.

### 3. **UI de Vitest (Interactivo)**
```bash
npm run test:ui
```

Abre una interfaz web en `http://localhost:51204/__vitest__/` donde puedes:
- Ver tests en tiempo real
- Filtrar por nombre
- Ver stack traces interactivos
- Rerun tests individuales

### 4. **Cobertura de Código**
```bash
npm run test:coverage
```

Genera reportes en `coverage/` mostrando qué código está testeado:
- `coverage/index.html` → Abre en browser
- Verde = cubierto
- Rojo = no cubierto

---

## Estructura de Tests

```
tests/
├── setup.ts                    # Configuración global (MSW, mocks)
├── auth/
│   ├── TokenManager.test.ts    # Tests del TokenManager
│   └── TokenRefreshService.test.ts  # Tests del refresh
└── mocks/
    └── handlers.ts             # MSW handlers (endpoints fake)
```

### ¿Qué hace cada archivo?

**`setup.ts`**: Se ejecuta ANTES de todos los tests
- Inicia MSW (Mock Service Worker)
- Mockea localStorage, IndexedDB
- Mockea window.location
- Silencia console en tests

**`mocks/handlers.ts`**: Define endpoints fake
- `/api/auth/refresh` → Responde con token fresco
- `/graphql` (LOGIN mutation) → Responde con usuario
- `/graphql` (AUTH_STATUS query) → Verifica autenticación

**`TokenManager.test.ts`**: Tests del TokenManager
- `setToken()` → Almacena token
- `getAccessToken()` → Recupera token
- `validateToken()` → Valida expiración
- Callbacks: `onRefresh()`, `onExpiry()`

**`TokenRefreshService.test.ts`**: Tests del refresh
- `refresh()` → Solicita nuevo token
- Retry logic → Reintentos con backoff
- Queue handling → Múltiples peticiones simultáneas
- Error mapping → Tipos de error retryables

---

## Escribir Nuevos Tests

### Estructura Básica

```typescript
import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { MyService } from '@/lib/auth/MyService';

describe('MyService', () => {
  beforeEach(() => {
    // Setup antes de cada test
    vi.clearAllTimers();
  });

  afterEach(() => {
    // Limpieza después de cada test
    vi.clearAllTimers();
  });

  describe('myMethod()', () => {
    it('debería hacer algo específico', () => {
      // Arrange: Preparar datos
      const input = { foo: 'bar' };

      // Act: Ejecutar la función
      const result = MyService.myMethod(input);

      // Assert: Verificar resultado
      expect(result).toBe('expected');
    });

    it('debería manejar errores', async () => {
      // Test async
      const result = await MyService.riskyMethod();
      expect(result).toThrow();
    });
  });
});
```

### Test Async con MSW

```typescript
it('debería refrescar el token', async () => {
  // Override handler para este test específico
  server.use(
    http.post('http://localhost:8000/api/auth/refresh', () => {
      return HttpResponse.json({
        accessToken: 'new-token',
        expiresIn: 3600
      });
    })
  );

  const result = await TokenRefreshService.refresh();

  expect(result.success).toBe(true);
  expect(result.accessToken).toBe('new-token');
});
```

### Test de Componentes React

```typescript
import { render, screen, fireEvent } from '@testing-library/react';
import { LoginPage } from '@/Pages/Public/Login';
import { ApolloProvider } from '@apollo/client';
import { apolloClient } from '@/lib/apollo/client';

describe('LoginPage', () => {
  it('debería mostrar formulario de login', () => {
    render(
      <ApolloProvider client={apolloClient}>
        <LoginPage />
      </ApolloProvider>
    );

    expect(screen.getByLabelText(/email/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
  });

  it('debería hacer login con credenciales válidas', async () => {
    render(
      <ApolloProvider client={apolloClient}>
        <LoginPage />
      </ApolloProvider>
    );

    const emailInput = screen.getByLabelText(/email/i);
    const passwordInput = screen.getByLabelText(/password/i);
    const submitButton = screen.getByRole('button', { name: /submit/i });

    fireEvent.change(emailInput, { target: { value: 'test@example.com' } });
    fireEvent.change(passwordInput, { target: { value: 'password123' } });
    fireEvent.click(submitButton);

    // Esperar a que la mutación se complete
    await screen.findByText(/redirecting/i);
  });
});
```

---

## Mejores Prácticas

### ✅ DO's

1. **Prueba comportamiento, no implementación**
   ```typescript
   ✅ expect(TokenManager.getAccessToken()).toBe(token);
   ❌ expect(TokenManager['accessToken']).toBe(token); // Acceso privado
   ```

2. **Usa nombres descriptivos**
   ```typescript
   ✅ it('debería rechazar un token JWT inválido', () => {})
   ❌ it('test 1', () => {})
   ```

3. **Sigue patrón Arrange-Act-Assert**
   ```typescript
   it('debería...', () => {
     // Arrange
     const token = 'valid-jwt';
     
     // Act
     TokenManager.setToken(token, 3600, user, roles);
     
     // Assert
     expect(TokenManager.getAccessToken()).toBe(token);
   });
   ```

4. **Mockea solo lo necesario**
   ```typescript
   // ✅ Usa handlers globales, override cuando sea necesario
   server.use(http.post(...));
   
   // ❌ No mockees todo cada vez
   ```

5. **Limpia después de cada test**
   ```typescript
   afterEach(() => {
     TokenManager.clearToken();
     vi.clearAllTimers();
     server.resetHandlers();
   });
   ```

### ❌ DON'Ts

1. **No des por sentado el order de tests**
   - Tests pueden ejecutarse en cualquier orden
   - Cada test debe ser independiente

2. **No uses `setTimeout` en tests sin control**
   ```typescript
   ❌ it('debería...', async () => {
        await new Promise(r => setTimeout(r, 1000));
      });
   
   ✅ it('debería...', () => {
        vi.useFakeTimers();
        vi.advanceTimersByTime(1000);
      });
   ```

3. **No hardcodees valores**
   ```typescript
   ❌ expect(result).toBe('specific-string');
   
   ✅ expect(result).toContain('substring');
   expect(result).toMatch(/pattern/);
   ```

4. **No ignores errores de TypeScript**
   - Todos los tests deben pasar `strict: true`

---

## Qué Testear en Tu Sistema

### TokenManager (Core)
- ✅ setToken con JWT válido/inválido
- ✅ getAccessToken retorna null si expirado
- ✅ validateToken indica estado correcto
- ✅ clearToken limpia todo
- ✅ Callbacks se disparan correctamente
- ✅ Rol automático con un rol

### TokenRefreshService (Integración)
- ✅ Refresh exitoso con token válido
- ✅ Falla con refresh token inválido
- ✅ Retry logic con exponential backoff
- ✅ Queue multiple requests
- ✅ Distingue errores retryables vs no-retryables
- ✅ Maneja errores de red

### AuthContext (UI Logic)
- ✅ Inicializa sesión desde persistence
- ✅ Detecta email no verificado
- ✅ Detecta onboarding incompleto
- ✅ Redirige multi-role users a role-selector
- ✅ Logout limpia todo
- ✅ Multi-tab sync funciona

### AuthGuard (Routing)
- ✅ Permite acceso a usuarios autenticados
- ✅ Redirige a login usuarios no autenticados
- ✅ Valida email verification
- ✅ Valida onboarding completion
- ✅ Valida role selection
- ✅ Valida permissions

### GraphQL Integration
- ✅ Apollo auth link inyecta token
- ✅ Apollo error link maneja 401
- ✅ Refresh se intenta en error 401
- ✅ Session expiry redirige a login

---

## Troubleshooting

### "Cannot find module '@/lib/auth/TokenManager'"
**Solución**: Verifica que `vitest.config.ts` tiene alias `@`:
```typescript
resolve: {
  alias: {
    '@': path.resolve(__dirname, './resources/js'),
  },
}
```

### "MSW server failed to listen"
**Solución**: Asegúrate que `tests/setup.ts` se ejecuta primero:
```typescript
// vitest.config.ts
test: {
  setupFiles: ['./tests/setup.ts'],
}
```

### "localStorage is not defined"
**Solución**: `setup.ts` debe mockear localStorage:
```typescript
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock,
});
```

### "Test times out"
**Causa**: Promise no se resuelve  
**Solución**:
```typescript
// Aumenta timeout
it('test', async () => { ... }, { timeout: 10000 });

// O usa fake timers
vi.useFakeTimers();
vi.advanceTimersByTime(5000);
```

### "test A interferes with test B"
**Causa**: Estado compartido  
**Solución**:
```typescript
beforeEach(() => {
  TokenManager.clearToken();
  server.resetHandlers();
  vi.clearAllMocks();
});
```

---

## Cobertura de Código Esperada

**Meta**: 80%+ de líneas cubiertas

```
statements   : 85% (200 líneas / 235)
branches     : 82% (41 ramas / 50)
functions    : 90% (18 funciones / 20)
lines        : 85% (200 líneas / 235)
```

Para mejorar cobertura:
1. Encuentra archivos sin coverage: `coverage/index.html`
2. Abre y haz click en rojo para ver líneas no cubiertas
3. Escribe tests para esas líneas

---

## Ejemplo: Test Completo Real

```typescript
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { TokenRefreshService } from '@/lib/auth/TokenRefreshService';
import { server } from '../setup';
import { http, HttpResponse } from 'msw';

describe('TokenRefreshService - Session Expiry Scenario', () => {
  beforeEach(() => {
    vi.clearAllTimers();
  });

  it('debería manejar sesión expirada después de 2 horas', async () => {
    // Scenario: Usuario se fue por 2 horas
    // Backend: Refresh token también expiró
    // Expected: Fuerza logout

    server.use(
      http.post('http://localhost:8000/api/auth/refresh', () => {
        return HttpResponse.json(
          { error: 'INVALID_REFRESH_TOKEN', message: 'Token expirado' },
          { status: 401 }
        );
      })
    );

    const result = await TokenRefreshService.refresh();

    expect(result.success).toBe(false);
    expect(result.error?.type).toBe('INVALID_GRANT');
    expect(result.error?.retryable).toBe(false); // No reintentar
    expect(result.attempt).toBe(1); // Solo 1 intento
  });
});
```

---

## Próximos Pasos

1. ✅ Tests para `PersistenceService` (storage)
2. ✅ Tests para `AuthChannel` (multi-tab sync)
3. ✅ Tests para `HeartbeatService` (keep-alive)
4. ✅ Tests de componentes React (Login, AuthGuard, etc)
5. ✅ Tests E2E (flujo completo: login → onboarding → dashboard)
6. ✅ Tests de performance (refresh speed, storage latency)

---

## CI/CD Integration

Agrega a tu `.github/workflows/test.yml` (GitHub Actions):

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: 18
      - run: npm ci
      - run: npm run test
      - run: npm run test:coverage
      - uses: codecov/codecov-action@v3
```

---

*Última actualización: Octubre 24, 2024*  
*Mantén estos tests actualizados cuando cambies la lógica de auth*
