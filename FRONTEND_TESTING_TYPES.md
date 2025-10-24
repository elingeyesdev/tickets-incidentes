# 🧪 Tipos de Tests Frontend - Guía Profesional

## 📊 Pirámide de Tests (La Correcta)

```
                    ▲
                   /|\
                  / | \
                 /  |  \       🔴 E2E Tests (5-10%)
                /   |   \      Cypress, Playwright
               /    |    \     • Flujos completos en browser real
              /     |     \    • Slowest, más realistas
             /_____E2E____\
            /       |       \
           /        |        \      🟠 Integration Tests (15-30%)
          /         |         \     • Componentes + servicios
         /          |          \    • APIs mocked
        /__Component_Tests___\
       /          |          \
      /           |           \    🟢 Unit Tests (60-70%)
     /            |            \   • Funciones aisladas
    /     Unit Tests & Hooks    \  • Fastest, más tests
   /________________|____________\
```

---

## 🔍 Tipos de Tests Explicados

### 1️⃣ **UNIT TESTS** (60-70%)

**¿Qué testean?**
- Funciones individuales
- Hooks personalizados
- Utilidades
- Servicios
- Validadores

**Características:**
- ⚡ MUY rápidos
- 🔌 Sin dependencias externas
- 📝 Muchos tests (100+)
- ✅ Fáciles de mantener

**Ejemplo:**
```typescript
// src/lib/utils/validation.ts
export const isValidEmail = (email: string) => {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
};

// resources/js/tests/unit/validation.test.ts
import { describe, it, expect } from 'vitest';
import { isValidEmail } from '@/lib/utils/validation';

describe('isValidEmail', () => {
  it('should validate correct email', () => {
    expect(isValidEmail('user@example.com')).toBe(true);
  });

  it('should reject invalid email', () => {
    expect(isValidEmail('invalid.email')).toBe(false);
  });
});
```

**Runner:** Vitest, Jest  
**Ubicación en tu proyecto:** ✅ Tienes (parcial)

---

### 2️⃣ **COMPONENT TESTS** (15-30%)

**¿Qué testean?**
- Componentes React individuales
- Rendering
- Interacción (clicks, inputs)
- Props
- Estado local

**Características:**
- 🔧 Usan @testing-library/react
- 🖱️ Simulan interacción del usuario
- 📦 Componentes aislados
- 🟠 Velocidad media

**Ejemplo:**
```typescript
// resources/js/tests/components/Button.test.tsx
import { render, screen, fireEvent } from '@testing-library/react';
import { Button } from '@/Components/Button';

describe('Button', () => {
  it('should render', () => {
    render(<Button>Click me</Button>);
    expect(screen.getByText('Click me')).toBeInTheDocument();
  });

  it('should call onClick when clicked', () => {
    const handleClick = vi.fn();
    render(<Button onClick={handleClick}>Click</Button>);
    
    fireEvent.click(screen.getByText('Click'));
    expect(handleClick).toHaveBeenCalled();
  });
});
```

**Runner:** Vitest + @testing-library/react  
**Ubicación en tu proyecto:** ❌ NO tienes

---

### 3️⃣ **HOOK TESTS** (Subcategoría de Unit)

**¿Qué testean?**
- Hooks personalizados
- Estado
- Side effects
- Ciclo de vida

**Características:**
- 🎣 Usan @testing-library/react hooks
- 📊 Prueban estado y efectos
- 🔄 Simulan re-renders

**Ejemplo:**
```typescript
// resources/js/tests/hooks/useLogin.test.ts
import { describe, it, expect, vi } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import { useLogin } from '@/Features/authentication/hooks/useLogin';

describe('useLogin', () => {
  it('should update form data when fields change', () => {
    const { result } = renderHook(() => useLogin());
    
    act(() => {
      result.current.setFormData({ 
        email: 'user@example.com', 
        password: 'password123',
        rememberMe: false,
        deviceName: 'test'
      });
    });
    
    expect(result.current.formData.email).toBe('user@example.com');
  });
});
```

**Runner:** Vitest + @testing-library/react  
**Ubicación en tu proyecto:** ❌ NO tienes

---

### 4️⃣ **INTEGRATION TESTS** (15-30%)

**¿Qué testean?**
- Múltiples componentes juntos
- Servicios + componentes
- Flujos internos (sin navegar todo el app)
- APIs mocked

**Características:**
- 🔗 Componentes interconectados
- 📡 HTTP calls mocked (MSW)
- 🟠 Velocidad media-lenta
- 📝 20-50 tests típicamente

**Ejemplo:**
```typescript
// resources/js/tests/integration/auth-flow.test.ts
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { LoginForm } from '@/Pages/Public/Login';
import { server } from '../mocks/handlers';
import { http, HttpResponse } from 'msw';

describe('Login Flow', () => {
  it('should complete login flow', async () => {
    render(<LoginForm />);
    
    // User fills form
    fireEvent.change(screen.getByLabelText(/email/i), {
      target: { value: 'user@example.com' }
    });
    fireEvent.change(screen.getByLabelText(/password/i), {
      target: { value: 'password123' }
    });
    
    // User submits
    fireEvent.click(screen.getByRole('button', { name: /submit/i }));
    
    // Verify success
    await waitFor(() => {
      expect(screen.getByText(/success/i)).toBeInTheDocument();
    });
  });
});
```

**Runner:** Vitest + @testing-library/react + MSW  
**Ubicación en tu proyecto:** ✅ Tienes (parcial - auth-flow.test.ts)

---

### 5️⃣ **VISUAL REGRESSION TESTS** (Opcional)

**¿Qué testean?**
- Cambios visuales no intencionales
- Screenshots comparativos
- Estilos CSS

**Características:**
- 📸 Captura screenshots
- 🔍 Compara con baseline
- 🟡 Útil pero opcional
- ⏱️ Lento

**Herramientas:** Percy, Chromatic, Pixelmatch

**Ubicación en tu proyecto:** ❌ NO tienes (opcional)

---

### 6️⃣ **E2E TESTS** (5-10%)

**¿Qué testean?**
- Flujos **completamente reales** end-to-end
- Login → Onboarding → Dashboard completo
- Navegación entre rutas
- Browser real (Chrome, Firefox)

**Características:**
- 🌐 En browser real
- 🐌 MUY lentos (1-5 segundos por test)
- 🎭 Prueban TODO (frontend + backend + network)
- 📝 5-20 tests típicamente
- 💰 Costosos (lentos, caros de mantener)

**Ejemplo:**
```typescript
// resources/js/tests/e2e/auth.e2e.ts
// Con Cypress o Playwright

describe('Complete Login Flow E2E', () => {
  it('should login and navigate to dashboard', async () => {
    // Ir a login
    await page.goto('http://localhost:3000/login');
    
    // Llenar formulario
    await page.fill('[name="email"]', 'user@example.com');
    await page.fill('[name="password"]', 'password123');
    
    // Enviar
    await page.click('button[type="submit"]');
    
    // Esperar redirección a dashboard
    await page.waitForURL('**/dashboard');
    
    // Verificar que está autenticado
    expect(await page.isVisible('text=Welcome')).toBe(true);
  });
});
```

**Herramientas:** Cypress, Playwright, Selenium  
**Ubicación en tu proyecto:** ❌ NO tienes

---

## 📊 Lo Que TIENES vs. Lo Que NECESITAS

### ✅ QUÉ TIENES

```
/resources/js/tests/
├── auth/
│   ├── TokenManager.test.ts              ✅ Unit (services)
│   └── TokenRefreshService.test.ts       ✅ Unit (services)
│
├── integration/
│   └── auth-flow.test.ts                 ✅ Integration (partial)
│
├── components/                           ❌ VACÍO
├── mocks/
│   └── handlers.ts                       ✅ MSW setup
└── setup.ts

Total: 2 tests (ambos básicos)
Cobertura: ~5%
```

### ❌ QUÉ TE FALTA

```
Priority 1 (DEBE TENER):
  □ Component Tests          (AuthGuard, Login, RoleSelector, etc)
  □ Hook Tests              (useLogin, useAuthMachine, etc)
  □ More Integration Tests  (flujos multi-paso)
  □ Auth Services Unit      (HeartbeatService, PersistenceService, etc)

Priority 2 (DEBERÍA TENER):
  □ Más tests de services   (CompleteFlow, Edge cases)
  □ Form validation tests
  □ Error scenario tests

Priority 3 (NICE TO HAVE):
  □ Visual Regression       (Percy, Chromatic)
  □ E2E Tests              (Cypress, Playwright)
  □ Performance tests
```

---

## 🎯 Pirámide DE TESTS - Números Profesionales

### Proyecto Pequeño (30-50 tests)
```
          E2E: 2-3 tests
       Integration: 5-8 tests
       Component: 10-15 tests
    Unit & Hooks: 15-25 tests
```

### Proyecto Mediano (100-200 tests)
```
          E2E: 5-10 tests
       Integration: 20-40 tests
       Component: 30-60 tests
    Unit & Hooks: 50-100 tests
```

### Proyecto Grande (500+ tests)
```
          E2E: 20-50 tests
       Integration: 100-200 tests
       Component: 150-300 tests
    Unit & Hooks: 200-500 tests
```

---

## 📈 Tu Proyecto - Plan de Tests

**Estado Actual:** 2 tests básicos (5% cobertura)

### Fase 1: Fundamentos (1-2 semanas)
```
UNIT TESTS (25 tests)
├── TokenManager.test.ts           ✅ Parcial, completar
├── TokenRefreshService.test.ts    ✅ Parcial, completar
├── PersistenceService.test.ts     (agregar)
├── AuthChannel.test.ts            (agregar)
├── HeartbeatService.test.ts       (agregar)
└── Utility functions.test.ts      (agregar)

HOOK TESTS (10 tests)
├── useLogin.test.ts               (agregar)
├── useAuthMachine.test.ts         (agregar)
└── usePermissions.test.ts         (agregar)

Total: 35 tests
```

### Fase 2: Componentes (2-3 semanas)
```
COMPONENT TESTS (20 tests)
├── AuthGuard.test.tsx             (agregar)
├── Login.test.tsx                 (agregar)
├── RoleSelector.test.tsx          (agregar)
├── VerifyEmail.test.tsx           (agregar)
└── OnboardingForm.test.tsx        (agregar)

Total: 20 tests
```

### Fase 3: Integración (1-2 semanas)
```
INTEGRATION TESTS (10 tests)
├── auth-flow.test.ts              ✅ Parcial, completar
├── multi-tab-sync.test.ts         (agregar)
├── token-refresh-flow.test.ts     (agregar)
├── onboarding-flow.test.ts        (agregar)
└── error-scenarios.test.ts        (agregar)

Total: 10 tests
```

### Fase 4: E2E (Opcional - después)
```
E2E TESTS (5-10 tests) con Cypress o Playwright
├── Complete login flow
├── Onboarding flow
├── Multi-role switching
└── Session management
```

---

## 🔧 Instalación de lo que Necesitas

### Ya tienes instalado:
- ✅ Vitest
- ✅ @testing-library/react
- ✅ MSW

### Necesitas instalar (para tests de componentes):
```bash
npm install -D @testing-library/jest-dom
# Ya está instalado ✅
```

### Para E2E (opcional, después):
```bash
npm install -D cypress
# o
npm install -D playwright
```

---

## 🎓 Resumen: Tipos de Tests

| Tipo | Speed | Cuántos | Ubicación | Estado |
|------|-------|---------|-----------|--------|
| **Unit** | ⚡⚡⚡ | 50-100 | `/tests/auth/` | ✅ Parcial |
| **Hooks** | ⚡⚡⚡ | 10-20 | `/tests/hooks/` | ❌ NO tienes |
| **Component** | ⚡⚡ | 20-60 | `/tests/components/` | ❌ NO tienes |
| **Integration** | ⚡ | 10-40 | `/tests/integration/` | ✅ Parcial |
| **Visual** | 🐌 | 5-20 | `/tests/visual/` | ❌ Opcional |
| **E2E** | 🐌🐌 | 5-20 | `/tests/e2e/` | ❌ Opcional |

---

## 💡 Mi Recomendación para Tu Proyecto

### Semana 1: Completar Fase 1
```bash
# Completar Unit tests que ya empezaste
npm run test:watch

# Agregar tests para:
# - PersistenceService
# - AuthChannel
# - HeartbeatService
# - Utility functions
```

### Semana 2-3: Fase 2 (Componentes)
```bash
# Agregar Component tests para:
# - AuthGuard (CRÍTICO)
# - Login
# - RoleSelector
# - VerifyEmail
```

### Semana 4: Fase 3 (Integración)
```bash
# Completar/agregar Integration tests
# - Complete flows
# - Error scenarios
```

### Mes 2+: E2E (si lo necesitas)
```bash
npm install -D cypress
# E2E para flujos críticos
```

---

## 📚 Documentación

Lee primero:
1. `TESTS_MIGRATED.md` - Estructura
2. `TESTING_GUIDE.md` - Cómo escribir tests
3. Este documento - Tipos de tests

---

*Última actualización: Octubre 24, 2024*  
**Estado actual: 5% cobertura (2 tests)**  
**Meta recomendada: 70% (100+ tests)**
