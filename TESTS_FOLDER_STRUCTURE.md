# 📁 Estructura de Carpetas de Tests - Profesional

## ✅ ESTRUCTURA CORRECTA

```
/resources/js/
├── lib/
│   ├── auth/
│   │   ├── TokenManager.ts
│   │   ├── TokenRefreshService.ts
│   │   ├── AuthChannel.ts
│   │   ├── HeartbeatService.ts
│   │   ├── PersistenceService.ts
│   │   └── AuthMachine.ts
│   └── utils/
│       └── validation.ts
│
├── tests/                              ← TESTS (Organized by Type)
│   │
│   ├── unit/                          ← Unit Tests (60-70%)
│   │   ├── auth/                      ← Organized by feature
│   │   │   ├── TokenManager.test.ts
│   │   │   ├── TokenRefreshService.test.ts
│   │   │   ├── AuthChannel.test.ts
│   │   │   ├── HeartbeatService.test.ts
│   │   │   ├── PersistenceService.test.ts
│   │   │   └── AuthMachine.test.ts
│   │   │
│   │   ├── utils/
│   │   │   └── validation.test.ts
│   │   │
│   │   └── helpers/
│   │       └── common.test.ts
│   │
│   ├── hooks/                         ← Hook Tests (Subcategory of Unit)
│   │   ├── useLogin.test.ts
│   │   ├── useAuthMachine.test.ts
│   │   ├── usePermissions.test.ts
│   │   └── useForm.test.ts
│   │
│   ├── components/                    ← Component Tests (15-30%)
│   │   ├── auth/
│   │   │   ├── AuthGuard.test.tsx
│   │   │   ├── RoleSwitcher.test.tsx
│   │   │   └── __snapshots__/
│   │   │       └── AuthGuard.test.tsx.snap
│   │   │
│   │   ├── pages/
│   │   │   ├── Login.test.tsx
│   │   │   ├── RoleSelector.test.tsx
│   │   │   ├── VerifyEmail.test.tsx
│   │   │   └── CompleteProfile.test.tsx
│   │   │
│   │   ├── layout/
│   │   │   └── AuthenticatedLayout.test.tsx
│   │   │
│   │   ├── ui/
│   │   │   ├── Button.test.tsx
│   │   │   ├── Input.test.tsx
│   │   │   └── Card.test.tsx
│   │   │
│   │   └── __snapshots__/
│   │       └── (snapshot files aquí)
│   │
│   ├── integration/                   ← Integration Tests (15-30%)
│   │   ├── auth-flow.test.ts
│   │   ├── auth/
│   │   │   ├── login-flow.test.ts
│   │   │   ├── onboarding-flow.test.ts
│   │   │   ├── token-refresh-flow.test.ts
│   │   │   ├── multi-tab-sync.test.ts
│   │   │   └── error-scenarios.test.ts
│   │   │
│   │   └── user/
│   │       ├── profile-update-flow.test.ts
│   │       └── role-switching-flow.test.ts
│   │
│   ├── visual/                        ← Visual Regression Tests (Optional)
│   │   ├── auth-pages.visual.test.ts
│   │   ├── components.visual.test.ts
│   │   └── __snapshots__/
│   │
│   ├── e2e/                           ← E2E Tests (Optional, 5-10%)
│   │   ├── auth.e2e.ts
│   │   ├── onboarding.e2e.ts
│   │   ├── dashboard.e2e.ts
│   │   └── fixtures/
│   │       └── test-data.ts
│   │
│   ├── mocks/                         ← Shared Mocks
│   │   ├── handlers.ts                (MSW handlers)
│   │   ├── data/
│   │   │   ├── user.mock.ts
│   │   │   ├── auth.mock.ts
│   │   │   └── company.mock.ts
│   │   │
│   │   └── services/
│   │       └── localStorage.mock.ts
│   │
│   ├── fixtures/                      ← Test Data & Factories
│   │   ├── user.fixture.ts
│   │   ├── auth.fixture.ts
│   │   └── company.fixture.ts
│   │
│   ├── setup.ts                       ← Global Setup
│   ├── vitest.config.ts
│   └── test-utils.ts                  ← Shared Testing Utilities
```

---

## 📊 Explicación por Carpeta

### `unit/` - Unit Tests (60-70%)

```
tests/unit/
├── auth/                       ← Tests de servicios de auth
│   ├── TokenManager.test.ts
│   ├── TokenRefreshService.test.ts
│   ├── AuthChannel.test.ts
│   ├── HeartbeatService.test.ts
│   ├── PersistenceService.test.ts
│   └── AuthMachine.test.ts
│
├── utils/                      ← Tests de funciones utility
│   ├── validation.test.ts
│   ├── navigation.test.ts
│   └── formatting.test.ts
│
└── helpers/                    ← Tests de helpers comunes
    ├── common.test.ts
    └── date.test.ts
```

**Propósito:** Tests de funciones y servicios aislados

---

### `hooks/` - Hook Tests

```
tests/hooks/
├── useLogin.test.ts            ← Hook de login
├── useAuthMachine.test.ts      ← XState machine
├── usePermissions.test.ts      ← Permisos
├── useForm.test.ts            ← Form handling
└── useRefreshToken.test.ts    ← Token refresh
```

**Propósito:** Tests de hooks personalizados con `renderHook`

---

### `components/` - Component Tests (15-30%)

```
tests/components/
├── auth/                       ← Componentes de auth
│   ├── AuthGuard.test.tsx
│   └── RoleSwitcher.test.tsx
│
├── pages/                      ← Tests de páginas completas
│   ├── Login.test.tsx
│   ├── RoleSelector.test.tsx
│   ├── VerifyEmail.test.tsx
│   └── CompleteProfile.test.tsx
│
├── layout/                     ← Tests de layouts
│   └── AuthenticatedLayout.test.tsx
│
├── ui/                         ← Tests de componentes UI
│   ├── Button.test.tsx
│   ├── Input.test.tsx
│   ├── Card.test.tsx
│   └── Modal.test.tsx
│
└── __snapshots__/
    └── (auto-generados)
```

**Propósito:** Tests de rendering, props, interacción de componentes React

---

### `integration/` - Integration Tests (15-30%)

```
tests/integration/
├── auth-flow.test.ts           ← Test general de auth
│
├── auth/                       ← Flujos relacionados a auth
│   ├── login-flow.test.ts
│   ├── onboarding-flow.test.ts
│   ├── token-refresh-flow.test.ts
│   ├── multi-tab-sync.test.ts
│   └── error-scenarios.test.ts
│
└── user/                       ← Flujos de usuario
    ├── profile-update-flow.test.ts
    └── role-switching-flow.test.ts
```

**Propósito:** Tests de múltiples componentes/servicios juntos con MSW mocked APIs

---

### `e2e/` - E2E Tests (Optional, 5-10%)

```
tests/e2e/
├── auth.e2e.ts                 ← Login, logout en browser real
├── onboarding.e2e.ts           ← Flujo onboarding completo
├── dashboard.e2e.ts            ← Dashboard funcionalidad
│
└── fixtures/
    ├── test-data.ts            ← Data para tests
    └── users.fixture.ts        ← Usuarios de prueba
```

**Propósito:** Tests en browser real (Cypress, Playwright)

---

### `visual/` - Visual Regression Tests (Optional)

```
tests/visual/
├── auth-pages.visual.test.ts   ← Screenshots de páginas auth
├── components.visual.test.ts   ← Screenshots de componentes
│
└── __snapshots__/
    ├── auth-pages.png
    └── components.png
```

**Propósito:** Detectar cambios visuales no intencionales

---

### `mocks/` - Shared Mocks

```
tests/mocks/
├── handlers.ts                 ← MSW handlers (GraphQL, REST)
│
├── data/
│   ├── user.mock.ts            ← Mock de usuario
│   ├── auth.mock.ts            ← Mock de auth data
│   └── company.mock.ts         ← Mock de empresa
│
└── services/
    └── localStorage.mock.ts    ← Mock de localStorage
```

**Propósito:** Datos y handlers compartidos para todos los tests

---

### `fixtures/` - Test Data & Factories

```
tests/fixtures/
├── user.fixture.ts             ← Factory para crear users
├── auth.fixture.ts             ← Factory para auth data
└── company.fixture.ts          ← Factory para companies
```

**Ejemplo:**
```typescript
// tests/fixtures/user.fixture.ts
export const createMockUser = (overrides = {}) => ({
  id: '1',
  email: 'user@example.com',
  displayName: 'John Doe',
  ...overrides
});
```

**Propósito:** Reutilizar datos de prueba entre múltiples tests

---

### `setup.ts` - Global Setup

```typescript
// tests/setup.ts
import { beforeAll, afterEach, afterAll } from 'vitest';
import { server } from './mocks/handlers';
import '@testing-library/jest-dom';

// MSW setup
beforeAll(() => server.listen());
afterEach(() => server.resetHandlers());
afterAll(() => server.close());

// Mocks globales
// localStorage, indexedDB, etc.
```

---

### `vitest.config.ts` - Configuration

```typescript
// tests/vitest.config.ts
import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./setup.ts'],
    include: ['**/*.test.{ts,tsx}'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
    }
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, '../'),
    },
  },
});
```

---

### `test-utils.ts` - Shared Utilities

```typescript
// tests/test-utils.ts
import { render } from '@testing-library/react';
import { ReactNode } from 'react';

// Custom render con providers
export function renderWithProviders(
  ui: ReactNode,
  options = {}
) {
  return render(ui, {
    wrapper: ({ children }) => (
      <AuthProvider>
        <ApolloProvider client={apolloClient}>
          {children}
        </ApolloProvider>
      </AuthProvider>
    ),
    ...options,
  });
}

export * from '@testing-library/react';
```

---

## 🎯 Cómo Organizar por Feature

**OPCIÓN A: Por tipo de test (Recomendado)**
```
tests/
├── unit/auth/
├── unit/utils/
├── hooks/
├── components/pages/
├── integration/auth/
└── e2e/
```

**OPCIÓN B: Por feature**
```
tests/
├── auth/
│   ├── unit/
│   ├── components/
│   ├── integration/
│   └── e2e/
│
└── user/
    ├── unit/
    ├── components/
    └── integration/
```

**Recomendación:** Usa **OPCIÓN A** (por tipo de test). Es más fácil de mantener.

---

## 📋 Plantilla para Crear Nueva Carpeta

Cuando agregues tests para una nueva feature:

### Para Unit Test:
```
tests/unit/{feature}/
└── MyService.test.ts
```

### Para Component Test:
```
tests/components/{category}/
└── MyComponent.test.tsx
└── __snapshots__/
    └── MyComponent.test.tsx.snap
```

### Para Integration Test:
```
tests/integration/{feature}/
└── my-flow.test.ts
```

---

## 🚀 Migración a Estructura Correcta

Si ya tienes tests, así los reorganizas:

```bash
# Crear estructura
mkdir -p resources/js/tests/{unit/auth,unit/utils,hooks,components/{auth,pages,ui},integration/auth,e2e,mocks,fixtures}

# Mover tests existentes
mv tests/auth/TokenManager.test.ts resources/js/tests/unit/auth/
mv tests/auth/TokenRefreshService.test.ts resources/js/tests/unit/auth/
mv tests/integration/auth-flow.test.ts resources/js/tests/integration/auth/
mv tests/mocks/handlers.ts resources/js/tests/mocks/
mv tests/setup.ts resources/js/tests/
```

---

## 📊 Resumen: Dónde Va Cada Test

| Tipo de Test | Dónde Va | Nombre del File |
|--------------|----------|-----------------|
| Unit (servicio) | `unit/auth/` | `TokenManager.test.ts` |
| Unit (utilidad) | `unit/utils/` | `validation.test.ts` |
| Hook | `hooks/` | `useLogin.test.ts` |
| Component | `components/auth/` | `AuthGuard.test.tsx` |
| Component (página) | `components/pages/` | `Login.test.tsx` |
| Component (UI) | `components/ui/` | `Button.test.tsx` |
| Integration | `integration/auth/` | `login-flow.test.ts` |
| E2E | `e2e/` | `auth.e2e.ts` |

---

## ✅ Checklist: ¿Tu Estructura es Correcta?

- ✅ `unit/` contiene solo tests de funciones/servicios aislados
- ✅ `hooks/` contiene solo tests de hooks con `renderHook`
- ✅ `components/` contiene tests de componentes React
- ✅ `integration/` contiene tests de múltiples componentes/servicios
- ✅ `e2e/` contiene tests en browser real (o no existe si no lo usas)
- ✅ `mocks/` tiene MSW handlers y mock data
- ✅ `fixtures/` tiene factories para datos de prueba
- ✅ Cada test está en la carpeta correcta según su tipo
- ✅ Los nombres de archivos son descriptivos y consistentes
- ✅ Hay `setup.ts` y `vitest.config.ts` en la raíz de `tests/`

---

## 🎓 Ejemplo Completo

Si tienes una feature "Auth", así se organiza:

```
tests/
├── unit/auth/
│   ├── TokenManager.test.ts        ← Service test
│   ├── TokenRefreshService.test.ts ← Service test
│   └── AuthChannel.test.ts         ← Service test
│
├── hooks/
│   ├── useLogin.test.ts            ← Hook test
│   └── useAuthMachine.test.ts      ← Hook test
│
├── components/auth/
│   ├── AuthGuard.test.tsx          ← Component test
│   └── RoleSwitcher.test.tsx       ← Component test
│
├── components/pages/
│   ├── Login.test.tsx              ← Page test
│   └── RoleSelector.test.tsx       ← Page test
│
├── integration/auth/
│   ├── login-flow.test.ts          ← Integration test
│   ├── onboarding-flow.test.ts     ← Integration test
│   └── error-scenarios.test.ts     ← Integration test
│
├── mocks/
│   ├── handlers.ts                 ← MSW setup
│   └── data/
│       └── auth.mock.ts            ← Mock data
│
└── fixtures/
    └── user.fixture.ts             ← Factory
```

---

*Última actualización: Octubre 24, 2024*  
**Esta es la estructura profesional y correcta para un proyecto frontend.**
