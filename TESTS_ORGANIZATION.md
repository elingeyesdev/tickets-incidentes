# 📋 Organización de Tests - Frontend y Backend

## 🏗️ Estructura Actual (Después de agregar tests frontend)

```
/tests
├── 📁 Feature/              ← Tests de LARAVEL (Backend) - Feature tests
│   ├── Authentication/      ✅ PHP tests para login, refresh, logout
│   ├── CompanyManagement/   ✅ PHP tests para gestión de empresas
│   ├── GraphQL/             ✅ PHP tests para queries/mutations
│   └── UserManagement/      ✅ PHP tests para usuarios
│
├── 📁 Unit/                 ← Tests UNITARIOS de LARAVEL (Backend)
│   └── ExampleTest.php
│
├── 📁 GraphQL/              ← Tests GraphQL de LARAVEL (Backend)
│   └── BasicQueriesTest.php
│
├── 📁 auth/                 ← Tests de FRONTEND (React/TS) - NUEVO
│   ├── TokenManager.test.ts        (En progreso)
│   └── TokenRefreshService.test.ts (En progreso)
│
├── 📁 integration/          ← Tests de INTEGRACIÓN Frontend - NUEVO
│   └── auth-flow.test.ts    ✅ Funcionando
│
├── 📁 mocks/                ← Mocks compartidos Frontend - NUEVO
│   └── handlers.ts          (MSW handlers)
│
├── setup.ts                 ← Setup FRONTEND - NUEVO
└── TestCase.php             ← Setup BACKEND (Laravel)
```

---

## 🔍 Explicación por Carpeta

### **BACKEND (Laravel/PHP)**

```
tests/Feature/
├── Authentication/
│   ├── LoginMutationTest.php       → Prueba mutation login
│   ├── RegisterMutationTest.php     → Prueba mutation register
│   ├── RefreshTokenTest.php         → Prueba endpoint refresh
│   └── LogoutMutationTest.php       → Prueba mutation logout
│
├── UserManagement/
│   ├── UpdateProfileTest.php        → CRUD de usuarios
│   ├── AssignRoleTest.php           → Asignación de roles
│   └── DeleteUserTest.php           → Eliminar usuario
│
└── CompanyManagement/
    ├── CreateCompanyTest.php        → CRUD de empresas
    └── ManageTeamTest.php           → Gestión de equipos
```

**Ejecutar tests Laravel:**
```bash
php artisan test                    # Todos
php artisan test tests/Feature      # Solo Feature tests
php artisan test tests/Unit         # Solo Unit tests
```

---

### **FRONTEND (React/TypeScript)**

```
tests/
├── auth/                          ← Tests UNITARIOS del sistema auth
│   ├── TokenManager.test.ts       → Unit tests del TokenManager
│   ├── TokenRefreshService.test.ts → Unit tests del refresh
│   ├── PersistenceService.test.ts → (Próximo)
│   ├── AuthChannel.test.ts        → (Próximo)
│   └── HeartbeatService.test.ts   → (Próximo)
│
├── integration/                    ← Tests de INTEGRACIÓN Frontend
│   ├── auth-flow.test.ts          → Flujo login→onboarding→dashboard
│   ├── multi-tab-sync.test.ts     → (Próximo)
│   └── token-refresh.test.ts      → (Próximo)
│
├── components/                     ← Tests de COMPONENTES React
│   ├── AuthGuard.test.tsx         → (Próximo)
│   ├── Login.test.tsx             → (Próximo)
│   └── RoleSelector.test.tsx      → (Próximo)
│
└── e2e/                            ← Tests END-TO-END (Próximo)
    ├── login-flow.e2e.ts
    └── onboarding-flow.e2e.ts
```

**Ejecutar tests Frontend:**
```bash
npm run test                   # Todos
npm run test:watch            # Watch mode
npm run test -- tests/auth    # Solo auth tests
```

---

## 📊 Diferencias Clave

| Aspecto | Backend (Laravel) | Frontend (React) |
|---------|-------------------|------------------|
| **Ubicación** | `tests/Feature` `tests/Unit` | `tests/auth` `tests/integration` `tests/components` |
| **Lenguaje** | PHP | TypeScript |
| **Framework** | PHPUnit (Laravel) | Vitest |
| **Runtime** | PHP CLI | Node.js/jsdom |
| **HTTP Mock** | Fakes Laravel routes | MSW (Mock Service Worker) |
| **Ejecutar** | `php artisan test` | `npm run test` |
| **Config** | `phpunit.xml` | `vitest.config.ts` |
| **Setup** | `tests/TestCase.php` | `tests/setup.ts` |

---

## 🎯 Guía: Dónde agregar mis tests

### ✅ Backend - Test para nueva mutation de GraphQL
```php
// tests/Feature/UserManagement/UpdateCompanyNameMutationTest.php
<?php
namespace Tests\Feature\UserManagement;

use Tests\TestCase;

class UpdateCompanyNameMutationTest extends TestCase
{
    public function test_can_update_company_name()
    {
        // Tu test aquí
    }
}
```

**Ejecutar:**
```bash
php artisan test tests/Feature/UserManagement/UpdateCompanyNameMutationTest.php
```

---

### ✅ Frontend - Test para nuevo service
```typescript
// tests/auth/NewService.test.ts
import { describe, it, expect } from 'vitest';
import { NewService } from '@/lib/auth/NewService';

describe('NewService', () => {
  it('debería hacer algo', () => {
    expect(NewService.method()).toBe('expected');
  });
});
```

**Ejecutar:**
```bash
npm run test -- tests/auth/NewService.test.ts
```

---

### ✅ Frontend - Test de componente React
```typescript
// tests/components/MyComponent.test.tsx
import { render, screen } from '@testing-library/react';
import { MyComponent } from '@/Pages/MyComponent';

describe('MyComponent', () => {
  it('debería renderizar', () => {
    render(<MyComponent />);
    expect(screen.getByText(/expected/i)).toBeInTheDocument();
  });
});
```

**Ejecutar:**
```bash
npm run test -- tests/components/MyComponent.test.tsx
```

---

## 🚀 Resumen por Tipo de Test

### 1️⃣ **Unit Tests** (Pruebas de una función)
- **Backend**: `tests/Unit/` (si necesitas)
- **Frontend**: `tests/auth/` (TokenManager, TokenRefreshService, etc)
- ✅ Rápidos, aislados, sin dependencias

### 2️⃣ **Feature Tests** (Pruebas de funcionalidad completa)
- **Backend**: `tests/Feature/` (mutations, queries, workflows)
- **Frontend**: `tests/integration/` (login→onboarding→dashboard)
- ✅ Más lentos, más realistas

### 3️⃣ **Component Tests** (Pruebas de UI)
- **Frontend**: `tests/components/` (AuthGuard, Login, etc)
- ✅ Prueba render + interacción

### 4️⃣ **E2E Tests** (Pruebas end-to-end)
- **Frontend**: `tests/e2e/` (Cypress, Playwright)
- ✅ Prueba en browser real

---

## 📝 Ejemplo Completo: Feature - Login

### Backend - Mutation GraphQL
```php
// tests/Feature/Authentication/CompleteLoginFlowTest.php
class CompleteLoginFlowTest extends TestCase
{
    public function test_complete_login_flow()
    {
        // 1. Usuario hace login
        $response = $this->graphQL('
            mutation {
                login(input: {
                    email: "user@example.com"
                    password: "password123"
                }) {
                    accessToken
                    user { id email }
                }
            }
        ');
        
        // 2. Verificar token
        $response->assertHasData();
    }
}
```

**Ejecutar:**
```bash
php artisan test tests/Feature/Authentication/CompleteLoginFlowTest.php
```

---

### Frontend - Token Management
```typescript
// tests/auth/LoginFlow.test.ts
describe('Login Flow', () => {
  it('debería almacenar token después de login', async () => {
    // 1. Simular login mutation
    const result = await TokenRefreshService.refresh();
    
    // 2. Verificar token almacenado
    expect(TokenManager.getAccessToken()).toBeDefined();
    
    // 3. Verificar expiración programada
    expect(TokenManager.validateToken().isValid).toBe(true);
  });
});
```

**Ejecutar:**
```bash
npm run test -- tests/auth/LoginFlow.test.ts
```

---

## 🔄 Flujo Completo: User Logs In

```
┌─────────────────────────────────────┐
│ FRONTEND: User clicks "Login"        │
│ React Component triggering mutation  │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ Apollo Client sends LOGIN mutation   │
│ (tests/integration/auth-flow.test) │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ BACKEND: GraphQL Resolver            │
│ (tests/Feature/Auth/LoginTest.php)   │
│ - Verify credentials                 │
│ - Generate JWT token                 │
│ - Save refresh token                 │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ FRONTEND: Receive token              │
│ (tests/auth/TokenManager.test.ts)    │
│ - Store in IndexedDB/localStorage    │
│ - Schedule refresh                   │
│ - Broadcast to other tabs            │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│ FRONTEND: Navigate to onboarding     │
│ (tests/components/AuthGuard.test)    │
│ - Check email verified               │
│ - Check onboarding complete          │
└─────────────────────────────────────┘
```

---

## 🎯 Plan de Tests Recomendado

### **Fase 1: Infraestructura** ✅ (Hecho)
- ✅ Vitest configurado
- ✅ MSW handlers listos
- ✅ Setup.ts completado

### **Fase 2: Auth Backend** (⏳ Próximo)
```bash
# Ya existen algunos
php artisan test tests/Feature/Authentication
```

### **Fase 3: Auth Frontend** (⏳ Próximo)
```bash
npm run test -- tests/auth
# 25+ tests para:
# - TokenManager
# - TokenRefreshService
# - PersistenceService
# - AuthChannel
```

### **Fase 4: Integration Tests** (⏳ Después)
```bash
npm run test -- tests/integration
# Tests completos: login → onboarding → dashboard
```

### **Fase 5: Component Tests** (⏳ Después)
```bash
npm run test -- tests/components
# React component testing
```

---

## ✨ Comandos Útiles

```bash
# FRONTEND
npm run test                          # Todos los tests
npm run test:watch                    # Watch mode
npm run test:ui                       # UI interactiva
npm run test:coverage                 # Reporte de cobertura
npm run test -- tests/auth            # Solo auth folder

# BACKEND
php artisan test                      # Todos
php artisan test tests/Feature        # Solo Feature
php artisan test --filter LoginTest   # Por nombre
php artisan test --stop-on-failure    # Para en primer fallo
```

---

## 🎓 Resumen Final

| Tipo | Dónde | Qué testear | Ejecutar |
|------|-------|------------|----------|
| **Backend Unit** | `tests/Unit/` | Funciones aisladas | `php artisan test tests/Unit` |
| **Backend Feature** | `tests/Feature/` | Mutations, Queries, Workflows | `php artisan test tests/Feature` |
| **Frontend Unit** | `tests/auth/` | TokenManager, Services | `npm run test -- tests/auth` |
| **Frontend Integration** | `tests/integration/` | Flujos completos | `npm run test -- tests/integration` |
| **Frontend Components** | `tests/components/` | React Components | `npm run test -- tests/components` |

---

*Última actualización: Octubre 24, 2024*  
Pon tests en la carpeta correcta según el tipo y ejecuta con los comandos apropiados.
