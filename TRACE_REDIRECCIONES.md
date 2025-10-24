# 🔴 TRACE DE REDIRECCIONES - Simulación de Login

## Estado Inicial: Usuario acaba de hacer LOGIN
```
user: {
  id: 'aef5e5fb-0f2d-440a-bb21-b0568b63b0a3',
  email: 'juca@gmail.com',
  emailVerified: false,
  onboardingCompletedAt: null,  ← ⚠️ CRÍTICO: null = no completó
  roleContexts: [...]
}
```

---

## ❓ Pregunta 1: Después del login, ¿A cuál página debería ir?

Con estos datos:
- `emailVerified: false` → Usuario DEBE verificar email primero
- `onboardingCompletedAt: null` → Usuario DEBE completar onboarding

**Respuesta correcta:** `/verify-email` (paso 0, antes de onboarding)

---

## ❓ Pregunta 2: ¿Quién está causando el loop?

Checklist:
- [ ] **PublicRoute** - ¿Redirige a usuario nuevo?
- [ ] **AuthGuard** - ¿Redirige antes de que se complete verify-email?
- [ ] **VerifyEmail component** - ¿Tiene su propia lógica de redirección?
- [ ] **useLogin hook** - ¿Redirige a lugar incorrecto post-login?

---

## 📋 Pasos para diagnosticar

**PASO 1:** Abre DevTools → Console → limpia todo con `console.clear()`

**PASO 2:** Haz login con credentials

**PASO 3:** Copia los logs que aparezcan (primeras 50 líneas)

**PASO 4:** Dime:
1. ¿A cuál URL termina redirigiendo?
2. ¿Ves logs de `[DIAGNOSTIC]`, `[AuthContext]`, o `[useLogin]`?
3. ¿Se repite el mismo log múltiples veces (= loop)?
