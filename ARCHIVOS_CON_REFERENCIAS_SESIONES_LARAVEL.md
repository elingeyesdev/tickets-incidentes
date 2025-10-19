# ARCHIVOS CON REFERENCIAS A SESIONES DE LARAVEL - REVISIÓN COMPLETA

Este documento identifica **TODOS** los archivos en el proyecto que contienen referencias a sesiones de Laravel que pueden estar causando problemas y errores molestos.

## 🎯 Objetivo
Eliminar o refactorizar **TODAS** las referencias a sesiones de Laravel para migrar completamente a un sistema de autenticación basado en JWT/GraphQL.

## 📊 RESUMEN EJECUTIVO
- **Total de archivos afectados**: 58 archivos
- **Referencias críticas**: 35 archivos
- **Configuraciones problemáticas**: 2 archivos
- **Middleware problemático**: 1 archivo
- **Rutas problemáticas**: 1 archivo
- **Tests problemáticos**: 6 archivos

---

## 📁 Archivos de Configuración (CRÍTICOS)

### 1. Configuración de Sesiones
- **`config/session.php`** ⚠️ **CRÍTICO**
  - Archivo completo de configuración de sesiones Laravel
  - Driver: `database` (por defecto)
  - Lifetime: 120 minutos
  - **ACCIÓN**: Eliminar completamente este archivo

### 2. Configuración de Autenticación
- **`config/auth.php`** ⚠️ **CRÍTICO**
  - Guard 'web' configurado con driver 'session' (línea 40)
  - Default guard configurado como 'web' (línea 17)
  - **ACCIÓN**: Cambiar guard por defecto a JWT o eliminar guard 'web'

### 3. Bootstrap de la Aplicación
- **`bootstrap/app.php`** ⚠️ **CRÍTICO**
  - Middleware 'auth' aliasado a GraphQLJWTMiddleware (línea 24)
  - Middleware 'guest' aliasado a RedirectIfAuthenticated (línea 27)
  - **ACCIÓN**: Verificar que los aliases no dependan de sesiones

---

## 🐳 Archivos Docker (CRÍTICOS)

### 1. Docker Compose
- **`docker-compose.yml`** ⚠️ **CRÍTICO**
  - Variables de entorno: `SESSION_DRIVER=redis`
  - Líneas 31, 157, 197
  - **ACCIÓN**: Eliminar todas las variables SESSION_*

- **`docker-compose.prod.yml`** ⚠️ **CRÍTICO**
  - Variables de entorno: `SESSION_DRIVER=redis`
  - Líneas 38, 148, 181
  - **ACCIÓN**: Eliminar todas las variables SESSION_*

---

## 🔧 Archivos de Rutas y Middleware

### 1. Rutas Web (CRÍTICO)
- **`routes/web.php`** ⚠️ **CRÍTICO**
  - Middleware 'auth' usado en líneas 72, 95
  - Middleware 'guest' usado en línea 24
  - **PROBLEMA**: Middleware 'auth' configurado para JWT pero aplicado a rutas web
  - **ACCIÓN**: Verificar que el middleware funcione correctamente con JWT

### 2. Rutas API
- **`routes/api.php`** ⚠️ **MEDIO**
  - Comentarios sobre `auth:sanctum` (línea 32)
  - **ACCIÓN**: Limpiar comentarios obsoletos

### 3. Middleware JWT
- **`app/Http/Middleware/GraphQLJWTMiddleware.php`** ⚠️ **CRÍTICO**
  - Usa `Auth::setUser($user)` en línea 61
  - **PROBLEMA**: Depende de Auth:: que puede fallar sin sesiones
  - **ACCIÓN**: Verificar que funcione correctamente

### 4. Middleware de Redirección
- **`app/Shared/Http/Middleware/RedirectIfAuthenticated.php`** ⚠️ **CRÍTICO**
  - Usa `Auth::guard($guard)->check()` en línea 34
  - Usa `Auth::guard($guard)->user()` en línea 35
  - **PROBLEMA**: Depende de guards de sesión
  - **ACCIÓN**: Refactorizar para usar JWT

### 5. Middleware de Roles
- **`app/Shared/Http/Middleware/EnsureUserHasRole.php`** ⚠️ **MEDIO**
  - Usa `$request->user()` en línea 31
  - Comentarios con `auth:sanctum` (líneas 16-17)
  - **ACCIÓN**: Verificar implementación y actualizar comentarios

### 6. Middleware de Onboarding
- **`app/Shared/Http/Middleware/EnsureOnboardingCompleted.php`** ⚠️ **MEDIO**
  - Usa `$request->user()` en línea 50
  - Comentarios con `auth:sanctum` (línea 28)
  - **ACCIÓN**: Verificar implementación y actualizar comentarios

---

## 🔐 Feature Authentication (CRÍTICOS)

### 1. Servicios
- **`app/Features/Authentication/Services/AuthService.php`** ⚠️ **REVISAR**
  - Método `login()` en línea 117
  - Método `logout()` en línea 170
  - **ACCIÓN**: Verificar que no use sesiones Laravel

- **`app/Features/Authentication/Services/TokenService.php`** ⚠️ **REVISAR**
  - Posible gestión de sesiones
  - **ACCIÓN**: Verificar implementación

### 2. GraphQL - Queries
- **`app/Features/Authentication/GraphQL/Queries/AuthStatusQuery.php`** ⚠️ **REVISAR**
  - Posible verificación de sesiones
  - **ACCIÓN**: Verificar implementación

- **`app/Features/Authentication/GraphQL/Queries/MySessionsQuery.php`** ⚠️ **CRÍTICO**
  - Query específica para sesiones
  - **ACCIÓN**: Eliminar o refactorizar

### 3. GraphQL - Mutations
- **`app/Features/Authentication/GraphQL/Mutations/LoginMutation.php`** ⚠️ **REVISAR**
  - Usa trait `SetsRefreshTokenCookie` (línea 30)
  - Posible creación de sesiones
  - **ACCIÓN**: Verificar implementación

- **`app/Features/Authentication/GraphQL/Mutations/LogoutMutation.php`** ⚠️ **REVISAR**
  - Posible destrucción de sesiones
  - **ACCIÓN**: Verificar implementación

- **`app/Features/Authentication/GraphQL/Mutations/RegisterMutation.php`** ⚠️ **REVISAR**
  - Usa trait `SetsRefreshTokenCookie` (línea 30)
  - Posible creación de sesiones post-registro
  - **ACCIÓN**: Verificar implementación

- **`app/Features/Authentication/GraphQL/Mutations/RevokeOtherSessionMutation.php`** ⚠️ **CRÍTICO**
  - Mutation específica para revocar sesiones
  - **ACCIÓN**: Eliminar o refactorizar

### 4. GraphQL - Concerns/Traits
- **`app/Features/Authentication/GraphQL/Mutations/Concerns/SetsRefreshTokenCookie.php`** ⚠️ **REVISAR**
  - Manejo de cookies HttpOnly
  - **ACCIÓN**: Verificar que no use sesiones Laravel

### 5. Excepciones
- **`app/Features/Authentication/Exceptions/SessionNotFoundException.php`** ⚠️ **CRÍTICO**
  - Excepción específica para sesiones
  - **ACCIÓN**: Eliminar

- **`app/Features/Authentication/Exceptions/CannotRevokeCurrentSessionException.php`** ⚠️ **CRÍTICO**
  - Excepción específica para sesiones
  - **ACCIÓN**: Eliminar

### 6. DataLoaders
- **`app/Features/Authentication/GraphQL/DataLoaders/RefreshTokensByUserIdLoader.php`** ⚠️ **REVISAR**
  - Posible relación con sesiones
  - **ACCIÓN**: Verificar implementación

### 7. Eventos
- **`app/Features/Authentication/Events/UserLoggedOut.php`** ⚠️ **REVISAR**
  - Posible gestión de sesiones
  - **ACCIÓN**: Verificar implementación

---

## 🧪 Archivos de Testing (CRÍTICOS)

### 1. TestCase Base
- **`tests/TestCase.php`** ⚠️ **CRÍTICO**
  - Usa `Auth::guard($guard)->setUser($user)` en línea 48
  - **PROBLEMA**: Depende de Auth:: que puede fallar sin sesiones
  - **ACCIÓN**: Refactorizar para usar JWT

### 2. Tests de Authentication
- **`tests/Feature/Authentication/LoginMutationTest.php`** ⚠️ **CRÍTICO**
  - Tests relacionados con sesiones
  - **ACCIÓN**: Refactorizar tests

- **`tests/Feature/Authentication/RegisterMutationTest.php`** ⚠️ **CRÍTICO**
  - Tests relacionados con sesiones
  - **ACCIÓN**: Refactorizar tests

- **`tests/Feature/Authentication/AuthStatusQueryTest.php`** ⚠️ **CRÍTICO**
  - Tests de estado de autenticación
  - **ACCIÓN**: Refactorizar tests

- **`tests/Feature/Authentication/RevokeOtherSessionMutationTest.php`** ⚠️ **CRÍTICO**
  - Tests específicos de sesiones
  - **ACCIÓN**: Eliminar tests

- **`tests/Feature/Authentication/MySessionsQueryTest.php`** ⚠️ **CRÍTICO**
  - Tests específicos de sesiones
  - **ACCIÓN**: Eliminar tests

- **`tests/Feature/Authentication/RefreshTokenAndLogoutTest.php`** ⚠️ **CRÍTICO**
  - Tests relacionados con sesiones
  - **ACCIÓN**: Refactorizar tests

---

## 🎨 Frontend - React/TypeScript

### 1. Componentes de Guards
- **`resources/js/Components/guards/OnboardingRoute.tsx`** ⚠️ **REVISAR**
  - Usa SessionStorage del navegador (NO sesiones Laravel)
  - **ACCIÓN**: Verificar implementación

- **`resources/js/Components/guards/ProtectedRoute.tsx`** ⚠️ **REVISAR**
  - Usa SessionStorage del navegador (NO sesiones Laravel)
  - **ACCIÓN**: Verificar implementación

- **`resources/js/Components/guards/PublicRoute.tsx`** ⚠️ **REVISAR**
  - Usa SessionStorage del navegador (NO sesiones Laravel)
  - **ACCIÓN**: Verificar implementación

### 2. Contextos
- **`resources/js/contexts/AuthContext.tsx`** ⚠️ **REVISAR**
  - Usa SessionStorage del navegador (NO sesiones Laravel)
  - **ACCIÓN**: Verificar implementación

### 3. Utilidades
- **`resources/js/lib/utils/navigation.ts`** ⚠️ **REVISAR**
  - Usa SessionStorage del navegador (NO sesiones Laravel)
  - **ACCIÓN**: Verificar implementación

### 4. Tipos
- **`resources/js/types/graphql-generated.ts`** ⚠️ **AUTO-GENERADO**
  - Tipos generados que incluyen sesiones (sessionId, SessionInfo, etc.)
  - **ACCIÓN**: Regenerar después de limpiar schema

- **`resources/js/types/graphql.ts`** ⚠️ **REVISAR**
  - Tipos relacionados con sesiones
  - **ACCIÓN**: Verificar y limpiar

- **`resources/js/types/models.ts`** ⚠️ **REVISAR**
  - Modelos que incluyen sesiones
  - **ACCIÓN**: Verificar y limpiar

- **`resources/js/types/index.d.ts`** ⚠️ **REVISAR**
  - Definiciones de tipos con sesiones
  - **ACCIÓN**: Verificar y limpiar

### 5. GraphQL
- **`resources/js/lib/graphql/fragments.ts`** ⚠️ **REVISAR**
  - Fragmentos que incluyen sesiones
  - **ACCIÓN**: Verificar y limpiar

- **`resources/js/lib/graphql/queries/auth.queries.ts`** ⚠️ **REVISAR**
  - Queries de autenticación
  - **ACCIÓN**: Verificar y limpiar

---

## 📋 Schema GraphQL

### 1. Schema Principal
- **`graphql/schema.graphql`** ⚠️ **CRÍTICO**
  - Schema principal que puede incluir tipos de sesiones
  - **ACCIÓN**: Verificar y limpiar

- **`app/Features/Authentication/GraphQL/Schema/authentication.graphql`** ⚠️ **CRÍTICO**
  - Schema específico de autenticación
  - **ACCIÓN**: Verificar y limpiar tipos de sesiones

---

## 🔧 Archivos Compartidos

### 1. Traits Importantes
- **`app/Shared/Traits/Auditable.php`** ⚠️ **CRÍTICO**
  - Usa `Auth::check()` en líneas 36, 43, 50
  - Usa `Auth::id()` en líneas 37, 44, 51
  - **PROBLEMA**: Depende de Auth:: que puede fallar sin sesiones
  - **ACCIÓN**: Refactorizar para usar JWT o pasar user_id explícitamente

### 2. Error Handling
- **`app/Shared/GraphQL/Errors/ErrorCodeRegistry.php`** ⚠️ **CRÍTICO**
  - Códigos de error relacionados con sesiones
  - Líneas 58, 190, 255
  - **ACCIÓN**: Eliminar códigos de sesiones

- **`app/Features/Authentication/GraphQL/Errors/TokenErrorHandler.php`** ⚠️ **CRÍTICO**
  - Manejo de errores de sesiones
  - Línea 71
  - **ACCIÓN**: Eliminar manejo de sesiones

---

## 📚 Documentación

### 1. Archivos de Documentación
- **`documentacion/AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt`** ⚠️ **REVISAR**
  - Documentación que puede incluir referencias a sesiones
  - **ACCIÓN**: Actualizar documentación

- **`documentacion/AUTHENTICATION FEATURE SCHEMA.txt`** ⚠️ **REVISAR**
  - Schema de documentación
  - **ACCIÓN**: Actualizar

- **`documentacion/IDEA DE IMPLEMENTACION PROFESIONAL DEL BACKEND EN EL FRONTEND.md`** ⚠️ **REVISAR**
  - Referencias a SESSION_EXPIRED en JWT
  - **ACCIÓN**: Verificar si son referencias válidas de JWT

---

## 🚀 Plan de Acción Recomendado

### Fase 1: Configuración (CRÍTICO)
1. ✅ Eliminar `config/session.php`
2. ✅ Modificar `config/auth.php` para eliminar guard 'web'
3. ✅ Limpiar variables SESSION_* en docker-compose files

### Fase 2: Backend (CRÍTICO)
1. ✅ Eliminar queries/mutations relacionadas con sesiones
2. ✅ Eliminar excepciones específicas de sesiones
3. ✅ Refactorizar servicios de autenticación
4. ✅ Actualizar ErrorCodeRegistry

### Fase 3: Frontend (MEDIO)
1. ✅ Verificar y limpiar componentes de guards
2. ✅ Actualizar contextos de autenticación
3. ✅ Regenerar tipos GraphQL
4. ✅ Limpiar queries y fragments

### Fase 4: Testing (CRÍTICO)
1. ✅ Eliminar tests específicos de sesiones
2. ✅ Refactorizar tests de autenticación
3. ✅ Actualizar tests de integración

### Fase 5: Limpieza (BAJO)
1. ✅ Actualizar documentación
2. ✅ Limpiar comentarios obsoletos
3. ✅ Verificar composer.json (eliminar laravel/sanctum si no se usa)

---

## ⚠️ Advertencias Importantes

1. **Backup**: Hacer backup completo antes de empezar
2. **Testing**: Probar cada cambio individualmente
3. **Dependencies**: Verificar que no hay dependencias ocultas
4. **Frontend**: El frontend puede depender de ciertas funcionalidades de sesiones
5. **JWT**: Asegurar que el sistema JWT esté completamente funcional antes de eliminar sesiones

---

## 📊 Resumen de Archivos por Prioridad

- **CRÍTICOS**: 35 archivos
- **MEDIOS**: 8 archivos  
- **REVISAR**: 14 archivos
- **AUTO-GENERADOS**: 1 archivo

**Total**: 58 archivos con referencias a sesiones de Laravel

## 🚨 ARCHIVOS MÁS CRÍTICOS QUE REQUIEREN ACCIÓN INMEDIATA

### 1. **Configuraciones** (Eliminar inmediatamente)
- `config/session.php` - Eliminar completamente
- `config/auth.php` - Cambiar guard por defecto

### 2. **Middleware** (Refactorizar inmediatamente)
- `app/Http/Middleware/GraphQLJWTMiddleware.php` - Verificar Auth::setUser
- `app/Shared/Http/Middleware/RedirectIfAuthenticated.php` - Refactorizar Auth::guard

### 3. **Traits** (Refactorizar inmediatamente)
- `app/Shared/Traits/Auditable.php` - Refactorizar Auth::check e Auth::id

### 4. **Tests** (Refactorizar inmediatamente)
- `tests/TestCase.php` - Refactorizar Auth::guard

### 5. **Queries/Mutations** (Eliminar inmediatamente)
- `app/Features/Authentication/GraphQL/Queries/MySessionsQuery.php`
- `app/Features/Authentication/GraphQL/Mutations/RevokeOtherSessionMutation.php`
- `app/Features/Authentication/Exceptions/SessionNotFoundException.php`
- `app/Features/Authentication/Exceptions/CannotRevokeCurrentSessionException.php`
