# ✅ Tests - Migración Completada

## 📍 Nueva Estructura (Correcta)

```
/proyecto
├── app/                           ← Código Backend (Laravel)
├── resources/js/                  ← Código Frontend (React)
│   └── tests/                     ← ✅ TESTS FRONTEND AQUÍ (NUEVO)
│       ├── auth/
│       │   ├── TokenManager.test.ts
│       │   └── TokenRefreshService.test.ts
│       ├── integration/
│       │   └── auth-flow.test.ts      ✅ FUNCIONANDO
│       ├── components/                 (listo para agregar tests)
│       ├── mocks/
│       │   └── handlers.ts
│       ├── setup.ts
│       └── vitest.config.ts
│
├── tests/                         ← ✅ TESTS BACKEND AQUÍ (SIN CAMBIOS)
│   ├── Feature/
│   │   ├── Authentication/
│   │   ├── UserManagement/
│   │   ├── CompanyManagement/
│   │   └── GraphQL/
│   ├── Unit/
│   ├── GraphQL/
│   ├── TestCase.php
│   └── phpunit.xml
│
└── docs/
    ├── TESTS_BEST_PRACTICE.md     ← Lee esto
    ├── TESTING_GUIDE.md           ← Guía completa
    └── TESTS_ORGANIZATION.md
```

---

## 🎯 Comandos - Rápida Referencia

### BACKEND (Laravel/PHP)
```bash
# Todos los tests backend
php artisan test

# Solo Feature tests
php artisan test tests/Feature

# Solo una carpeta
php artisan test tests/Feature/Authentication

# Solo un archivo
php artisan test tests/Feature/Authentication/LoginMutationTest.php

# Con stop on failure
php artisan test --stop-on-failure
```

### FRONTEND (React/TypeScript)
```bash
# Todos los tests frontend
npm run test

# Watch mode (reruns al cambiar código)
npm run test:watch

# UI interactiva en browser
npm run test:ui

# Reporte de cobertura
npm run test:coverage

# Solo una carpeta
npm run test -- tests/auth

# Solo un archivo
npm run test -- tests/auth/TokenManager.test.ts
```

---

## ✅ Verificación

Los tests están funcionando correctamente:

```bash
✓ tests/integration/auth-flow.test.ts (3 tests)
  ✓ should have localStorage available
  ✓ should be able to store in localStorage
  ✓ should have window.location available
```

Prueba con:
```bash
npm run test -- tests/integration/auth-flow.test.ts
```

---

## 📚 Dónde Agregar Tests

### ¿Test del backend (PHP/GraphQL)?
→ **`/tests/Feature/`** o **`/tests/Unit/`**  
Ejecutar: `php artisan test`

### ¿Test del frontend (TokenManager, TokenRefreshService, etc)?
→ **`/resources/js/tests/auth/`**  
Ejecutar: `npm run test`

### ¿Test de flujo completo (login → onboarding → dashboard)?
→ **`/resources/js/tests/integration/`**  
Ejecutar: `npm run test`

### ¿Test de componente React (AuthGuard, Login)?
→ **`/resources/js/tests/components/`**  
Ejecutar: `npm run test`

---

## 🚀 Próximos Pasos

### Fase 1: Tests de Services ⏳
```bash
# Crear tests en /resources/js/tests/auth/
npm run test:watch        # Desarrollo interactivo
```

### Fase 2: Tests de Componentes ⏳
```bash
# Crear tests en /resources/js/tests/components/
npm run test:watch
```

### Fase 3: Tests E2E ⏳ (Futuro)
```bash
# Crear tests en /resources/js/tests/e2e/
# Usar Cypress o Playwright
```

---

## 💡 Ejemplos Rápidos

### Backend - Nuevo test para mutation
```php
// /tests/Feature/UserManagement/UpdateProfileTest.php
<?php
namespace Tests\Feature\UserManagement;

use Tests\TestCase;

class UpdateProfileTest extends TestCase
{
    public function test_can_update_profile()
    {
        // Test aquí
    }
}
```

Ejecutar:
```bash
php artisan test tests/Feature/UserManagement/UpdateProfileTest.php
```

---

### Frontend - Nuevo test para service
```typescript
// /resources/js/tests/auth/MyService.test.ts
import { describe, it, expect } from 'vitest';
import { MyService } from '@/lib/auth/MyService';

describe('MyService', () => {
  it('should do something', () => {
    expect(MyService.method()).toBe('expected');
  });
});
```

Ejecutar:
```bash
npm run test -- tests/auth/MyService.test.ts
```

---

## 📊 Resumen

| Lado | Ubicación | Runner | Config |
|------|-----------|--------|--------|
| Backend | `/tests/` | `php artisan test` | `phpunit.xml` |
| Frontend | `/resources/js/tests/` | `npm run test` | `vitest.config.ts` |

---

## ✨ Ventajas de la Nueva Estructura

✅ **Separados**: Backend y frontend tests en sus propios folders  
✅ **Claros**: Fácil de encontrar qué test corresponde a dónde  
✅ **Sin conflictos**: Cada uno con su runner y configuración  
✅ **Profesional**: Sigue estándares de industria  
✅ **Escalable**: Fácil agregar E2E tests, mocks compartidos, etc  

---

## 📖 Documentación

- **`TESTS_BEST_PRACTICE.md`** - Por qué esta estructura es correcta
- **`TESTING_GUIDE.md`** - Guía completa de cómo escribir tests (español)
- **`TESTS_ORGANIZATION.md`** - Detalles de organización por tipo

---

*Migración completada: Octubre 24, 2024*  
*Tests funcionando: ✅*  
*Listo para agregar más tests.*
