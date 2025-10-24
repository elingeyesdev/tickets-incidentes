# 🚀 Tests - Quick Start

## ✅ Tests Setup Completado

Tu proyecto ahora tiene un sistema de testing profesional listo para usar.

### Lo que instalamos:
- ✅ **Vitest** (v4.0.2) - Framework de testing rápido
- ✅ **@testing-library/react** (v16.3.0) - Utilities para testing de componentes
- ✅ **MSW** (Mock Service Worker) - Mockear HTTP requests
- ✅ **jsdom** - Simulador de DOM en Node.js

### Comandos disponibles:

```bash
# Ejecutar todos los tests una sola vez
npm run test

# Watch mode - reruns cuando cambias código  
npm run test:watch

# UI Interactiva - ver tests en browser
npm run test:ui

# Coverage - reportes de código testeado
npm run test:coverage
```

---

## 📝 Tests que ya existen

### ✅ Funcionando ahora:

**`tests/integration/auth-flow.test.ts`**
- ✓ localStorage disponible
- ✓ Almacenar/recuperar en localStorage
- ✓ window.location disponible

Ejecuta con:
```bash
npm run test -- tests/integration/auth-flow.test.ts
```

---

## 🏗️ Estructura de Tests

```
tests/
├── setup.ts                      # Configuración global
├── mocks/
│   └── handlers.ts              # MSW handlers (endpoints mock)
├── auth/
│   ├── TokenManager.test.ts      # (En progreso)
│   └── TokenRefreshService.test.ts  # (En progreso)
└── integration/
    └── auth-flow.test.ts        # (Funcionando ✅)
```

---

## 📚 Archivos de Referencia

Para entender cómo escribir tests:

1. **TESTING_GUIDE.md** ← Lee esto para guía completa
   - Cómo escribir tests
   - Mejores prácticas
   - Troubleshooting

2. **tests/mocks/handlers.ts** ← Endpoints fake para MSW
   - LOGIN
   - LOGOUT
   - AUTH_STATUS
   - REFRESH
   - VERIFY_EMAIL

3. **tests/auth/TokenManager.test.ts** ← Ejemplo de tests
   - setToken()
   - getAccessToken()
   - validateToken()
   - Callbacks

---

## 🔧 Próximos Pasos

### Paso 1: Entender la estructura
```bash
# Ver contenido de los archivos de test
cat tests/integration/auth-flow.test.ts
cat tests/setup.ts
cat tests/mocks/handlers.ts
```

### Paso 2: Ejecutar tests existentes
```bash
npm run test
```

### Paso 3: Ver la UI
```bash
npm run test:ui
# Abre http://localhost:51204/__vitest__/
```

### Paso 4: Agregar más tests
Copia `tests/auth/TokenManager.test.ts` y personaliza para tus servicios.

---

## 🛠️ Troubleshooting

### "Module not found"
**Solución**: Verifica que `vitest.config.ts` tiene el alias `@`:
```typescript
resolve: {
  alias: {
    '@': path.resolve(__dirname, './resources/js'),
  },
}
```

### "localStorage/indexedDB not available"
**Solución**: Se mockean en `tests/setup.ts` - debería funcionar automáticamente.

### "Tests no se ejecutan"
```bash
# Verifica que los tests existen
find tests -name "*.test.ts"

# Ejecuta con verbose
npm run test -- --reporter=verbose
```

---

## 📊 Cobertura Actual

```bash
npm run test:coverage
```

Genera `coverage/index.html` con:
- ✅ Verde = código testeado
- ❌ Rojo = código sin testear
- 📊 % de cobertura por archivo

---

## 💡 Ejemplo: Tu Primer Test

Crea `tests/my-service.test.ts`:

```typescript
import { describe, it, expect } from 'vitest';
import { myService } from '@/lib/myService';

describe('MyService', () => {
  it('debería hacer algo', () => {
    const result = myService.doSomething('input');
    expect(result).toBe('expected');
  });
});
```

Ejecuta:
```bash
npm run test -- tests/my-service.test.ts
```

---

## ✨ Lo que sigue

1. ✅ Infraestructura lista
2. ⏳ Agregar tests para TokenManager
3. ⏳ Agregar tests para TokenRefreshService
4. ⏳ Agregar tests para AuthContext
5. ⏳ Tests E2E (flujo completo)
6. ⏳ CI/CD integration (GitHub Actions)

---

## 🎯 Objetivo Final

**Cobertura: 80%+** de líneas de auth services

**Estado actual**: 0 tests (solo infraestructura)
**Meta**: 50+ tests para auth system

---

*Última actualización: Octubre 24, 2024*

¿Preguntas? Ver `TESTING_GUIDE.md` para detalles completos.
