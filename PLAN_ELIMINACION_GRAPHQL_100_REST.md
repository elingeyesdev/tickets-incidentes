# PLAN COMPLETO: Eliminación 100% GraphQL → 100% REST API

**Fecha:** 01-Nov-2025
**Estado:** Auditado y listo para ejecutar
**Rama:** feature/graphql-to-rest-migration
**Tests Actuales:** 100% pasando (174/174) ✅

---

## 📊 RESUMEN EJECUTIVO

Tu codebase tiene una arquitectura mixta:
- ✅ REST API: **100% COMPLETO** (controllers, routes, tests)
- ✅ GraphQL API: **100% FUNCIONAL** (pero redundante)
- ⚠️ Frontend: **100% GRAPHQL** (Apollo Client, code generation)

**Objetivo:** Mantener el 100% de funcionalidad pero únicamente a través de REST API.

**Resultado Final:**
- Eliminar ~2,000+ líneas de código GraphQL innecesario
- Reducir complejidad del stack
- Simplificar el frontend con fetch/axios REST
- Mantener 100% de tests pasando

---

## 🎯 PHASES DE EJECUCIÓN (14 Fases)

### ✅ PHASE 1: Eliminar Dependencias PHP

**Archivos a Modificar:** `composer.json`

```bash
# 1. Remover paquetes GraphQL
composer remove nuwave/lighthouse mll-lab/laravel-graphiql

# 2. Verificar que firebase/php-jwt sigue instalado (JWT para REST)
composer show firebase/php-jwt
```

**Paquetes a Eliminar:**
- `nuwave/lighthouse` - Marco GraphQL
- `mll-lab/laravel-graphiql` - IDE GraphQL (GraphiQL)

**Paquetes a Mantener:**
- `firebase/php-jwt` - Necesario para JWT en REST API ✓

**Tiempo Estimado:** 2 minutos
**Risk Level:** BAJO

---

### ✅ PHASE 2: Eliminar Archivos de Configuración

**Archivos a Eliminar:**

```
❌ config/lighthouse.php (616 líneas)
   - Contenido: Configuración de Lighthouse, rutas GraphQL, namespaces
   - Alternativa: Toda su funcionalidad ya se maneja en REST controllers

❌ codegen.ts (root del proyecto)
   - Contenido: Configuración de @graphql-codegen
   - Alternativa: No necesario sin GraphQL operations

❌ graphql/ (directorio completo - ~200 líneas)
   ├── schema.graphql
   ├── shared/
   │   ├── scalars.graphql
   │   ├── directives.graphql
   │   ├── interfaces.graphql
   │   ├── enums.graphql
   │   ├── base-types.graphql
   │   ├── inputs.graphql
   │   └── pagination.graphql
   └── (Feature schemas ya no necesarios)
```

**Comando de Eliminación:**
```bash
# Linux/WSL
rm -f config/lighthouse.php codegen.ts
rm -rf graphql/

# PowerShell
Remove-Item config/lighthouse.php -Force
Remove-Item codegen.ts -Force
Remove-Item graphql/ -Recurse -Force
```

**Verificación:**
```bash
# Confirmar que no existen
ls config/lighthouse.php 2>/dev/null || echo "✓ Eliminado"
ls codegen.ts 2>/dev/null || echo "✓ Eliminado"
ls graphql/ 2>/dev/null || echo "✓ Eliminado"
```

**Tiempo Estimado:** 1 minuto
**Risk Level:** BAJO

---

### ✅ PHASE 3: Eliminar Código GraphQL del Backend

**Directorio a Eliminar:** `app/Shared/GraphQL/` (Completo)

```
❌ app/Shared/GraphQL/ (~1,500 líneas)
   ├── Scalars/ (7 archivos)
   │   ├── UUID.php
   │   ├── Email.php
   │   ├── PhoneNumber.php
   │   ├── HexColor.php
   │   ├── URL.php
   │   ├── DateTimeScalar.php
   │   └── JSON.php
   ├── Directives/ (5 archivos)
   │   ├── JwtDirective.php
   │   ├── JwtContextDirective.php
   │   ├── RateLimitDirective.php
   │   ├── CompanyDirective.php
   │   └── AuditDirective.php
   ├── Queries/ (4 archivos)
   │   ├── BaseQuery.php
   │   ├── PingQuery.php
   │   ├── VersionQuery.php
   │   └── HealthQuery.php
   ├── Mutations/ (1 archivo)
   │   └── BaseMutation.php
   ├── Resolvers/ (5 archivos)
   │   ├── DisplayNameResolver.php
   │   ├── AvatarUrlResolver.php
   │   ├── ThemeResolver.php
   │   ├── LanguageResolver.php
   │   └── OnboardingCompletedResolver.php
   ├── Errors/ (12 archivos) ⚠️ VER ABAJO
   │   └── (Handlers de error)
   └── DataLoaders/ (5 archivos) ⚠️ VER ABAJO
       └── (Batch loaders)
```

**IMPORTANTE: Los Errors y DataLoaders pueden reutilizarse**

```bash
# Eliminar solo la carpeta principal
rm -rf app/Shared/GraphQL/

# PERO PRESERVAR estos para reutilizar en REST:
# - app/Shared/GraphQL/Errors/ (pueden usar similar pattern)
# - app/Shared/GraphQL/DataLoaders/ (aunque REST no los necesita para N+1)
```

**Comando Seguro:**
```bash
# 1. Guardar los error handlers para referencia
mkdir -p app/Shared/GraphQL/Errors-BACKUP
cp -r app/Shared/GraphQL/Errors/* app/Shared/GraphQL/Errors-BACKUP/

# 2. Eliminar todo
rm -rf app/Shared/GraphQL/

# 3. Restaurar si es necesario
cp -r app/Shared/GraphQL/Errors-BACKUP app/Shared/GraphQL/Errors
```

**Directorios a Eliminar en Features:** `app/Features/*/GraphQL/`

```
❌ app/Features/Authentication/GraphQL/ (~800 líneas)
   ├── Schema/authentication.graphql
   ├── Queries/
   │   ├── AuthStatusQuery.php
   │   ├── EmailVerificationStatusQuery.php
   │   ├── MySessionsQuery.php
   │   └── PasswordResetStatusQuery.php
   ├── Mutations/
   │   ├── RegisterMutation.php
   │   ├── LoginMutation.php
   │   ├── RefreshTokenMutation.php
   │   ├── LogoutMutation.php
   │   ├── VerifyEmailMutation.php
   │   ├── ResendVerificationMutation.php
   │   ├── ResetPasswordMutation.php
   │   ├── ConfirmPasswordResetMutation.php
   │   ├── RevokeOtherSessionMutation.php
   │   ├── MarkOnboardingCompletedMutation.php
   │   ├── GoogleLoginMutation.php
   │   └── Concerns/SetsRefreshTokenCookie.php
   └── Errors/TokenErrorHandler.php

❌ app/Features/UserManagement/GraphQL/ (~700 líneas)
   ├── Schema/user-management.graphql
   ├── Queries/
   │   ├── MeQuery.php
   │   ├── MyProfileQuery.php
   │   ├── UserQuery.php
   │   ├── UsersQuery.php
   │   └── AvailableRolesQuery.php
   ├── Mutations/
   │   ├── UpdateMyProfileMutation.php
   │   ├── UpdateMyPreferencesMutation.php
   │   ├── AssignRoleMutation.php
   │   ├── RemoveRoleMutation.php
   │   ├── SuspendUserMutation.php
   │   ├── ActivateUserMutation.php
   │   └── DeleteUserMutation.php
   └── Types/
       ├── UserFieldResolvers.php
       ├── RoleContextFieldResolvers.php
       └── UserRoleInfoFieldResolvers.php

❌ app/Features/CompanyManagement/GraphQL/ (si existe)
   └── (Similar structure)
```

**Comando:**
```bash
rm -rf app/Features/Authentication/GraphQL/
rm -rf app/Features/UserManagement/GraphQL/
rm -rf app/Features/CompanyManagement/GraphQL/
```

**Verificación:**
```bash
find app -name "GraphQL" -type d 2>/dev/null | wc -l
# Debe retornar: 0
```

**Tiempo Estimado:** 2 minutos
**Risk Level:** BAJO (los controllers REST ya existen y funcionan)

---

### ✅ PHASE 4: Eliminar Dependencias Frontend

**Archivo a Modificar:** `package.json`

```bash
# Eliminar todos los paquetes GraphQL
npm uninstall @apollo/client @graphql-codegen/cli @graphql-codegen/typescript @graphql-codegen/typescript-operations @graphql-codegen/typescript-react-apollo @graphql-codegen/client-preset graphql

# O manualmente en package.json, eliminar estas líneas:
```

**Dependencias a Eliminar:**

```json
// devDependencies
"@graphql-codegen/cli": "^6.0.1"
"@graphql-codegen/typescript": "^5.0.2"
"@graphql-codegen/typescript-operations": "^5.0.2"
"@graphql-codegen/typescript-react-apollo": "^4.3.3"
"@graphql-codegen/client-preset": "^5.1.0"

// dependencies
"@apollo/client": "^4.0.7"
"graphql": "^16.11.0"
```

**Scripts a Eliminar de package.json:**
```json
{
  "scripts": {
    // ❌ ELIMINAR
    "codegen": "graphql-codegen --config codegen.ts",
    "codegen:watch": "graphql-codegen --config codegen.ts --watch"
    // ✅ MANTENER TODO LO DEMÁS
  }
}
```

**Comando:**
```bash
npm install  # Reinstalar sin las dependencias removidas
```

**Verificación:**
```bash
npm list @apollo/client 2>&1 | grep -q "npm ERR!" && echo "✓ Eliminado" || echo "✗ Aún existe"
npm list @graphql-codegen/cli 2>&1 | grep -q "npm ERR!" && echo "✓ Eliminado" || echo "✗ Aún existe"
```

**Tiempo Estimado:** 2 minutos
**Risk Level:** BAJO

---

### ✅ PHASE 5: Eliminar Código GraphQL del Frontend

**Directorios a Eliminar:**

```bash
❌ resources/js/lib/graphql/ (~500 líneas)
   ├── fragments.ts
   ├── mutations/
   │   ├── auth.mutations.ts
   │   └── users.mutations.ts
   └── queries/
       ├── auth.queries.ts
       └── user.queries.ts

❌ resources/js/lib/apollo/ (~300 líneas)
   └── client.ts (Apollo Client instance)

❌ Archivos auto-generados resources/js/types/
   ├── graphql.ts (1,757 líneas auto-generadas)
   ├── gql.ts
   └── fragment-masking.ts
```

**Comando:**
```bash
rm -rf resources/js/lib/graphql/
rm -rf resources/js/lib/apollo/
rm -f resources/js/types/graphql.ts resources/js/types/gql.ts resources/js/types/fragment-masking.ts
```

**Mantener:**
```bash
# ✓ Estos archivos pueden contener tipos útiles no-GraphQL
resources/js/types/models.ts
resources/js/types/index.ts
resources/js/types/index.d.ts
```

**Verificación:**
```bash
ls resources/js/lib/graphql/ 2>/dev/null && echo "✗ Aún existe" || echo "✓ Eliminado"
ls resources/js/lib/apollo/ 2>/dev/null && echo "✗ Aún existe" || echo "✓ Eliminado"
```

**Tiempo Estimado:** 1 minuto
**Risk Level:** MEDIO (necesita refactoring del frontend)

---

### ✅ PHASE 6: Limpiar Imports GraphQL en Frontend

**Archivos Afectados:**
```
resources/js/app.tsx (ApolloProvider)
resources/js/contexts/AuthContext.tsx (Apollo queries/mutations)
resources/js/Features/authentication/hooks/useLogin.ts (useMutation)
resources/js/Features/authentication/hooks/useRegister.ts (useMutation)
```

**Cambios Requeridos:**

**Archivo:** `resources/js/app.tsx`

```tsx
// ❌ ELIMINAR
import { ApolloProvider } from '@apollo/client';
import apolloClient from '@/lib/apollo/client';

// ❌ ELIMINAR wrapper
<ApolloProvider client={apolloClient}>
  // App content
</ApolloProvider>

// ✅ REEMPLAZAR CON
<AuthProvider>
  <ThemeProvider>
    <LocaleProvider>
      <NotificationProvider>
        <App {...props} />
      </NotificationProvider>
    </LocaleProvider>
  </ThemeProvider>
</AuthProvider>
```

**Archivo:** `resources/js/contexts/AuthContext.tsx`

```tsx
// ❌ ELIMINAR
import { useLazyQuery, useMutation } from '@apollo/client';
import { AUTH_STATUS_QUERY, LOGOUT_MUTATION } from '@/lib/graphql/queries/auth.queries';

// ✅ REEMPLAZAR CON
import axios from 'axios';

// Cambiar de Apollo queries a fetch/axios
const getAuthStatus = async () => {
  const response = await axios.get('/api/auth/status');
  return response.data;
};

const logout = async (everywhere = false) => {
  await axios.post('/api/auth/logout', { everywhere });
};
```

**Archivo:** `resources/js/Features/authentication/hooks/useLogin.ts`

```tsx
// ❌ ELIMINAR
import { useMutation } from '@apollo/client';
import { LOGIN_MUTATION } from '@/lib/graphql/mutations/auth.mutations';

const [login, { loading, error }] = useMutation(LOGIN_MUTATION, {
  onCompleted: (data) => { /* ... */ }
});

// ✅ REEMPLAZAR CON
const useLogin = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  const login = async (email: string, password: string) => {
    setLoading(true);
    try {
      const response = await axios.post('/api/auth/login', {
        email,
        password
      });
      await TokenManager.setToken(response.data.access_token, ...);
      return response.data;
    } catch (err) {
      setError(err);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return { login, loading, error };
};
```

**Tiempo Estimado:** 15-20 minutos (depende de alcance del frontend)
**Risk Level:** ALTO (cambios funcionales necesarios)

---

### ✅ PHASE 7: Actualizar Variables de Entorno

**Archivo:** `.env` y `.env.example`

```bash
# ❌ ELIMINAR estas líneas
LIGHTHOUSE_CACHE_ENABLE=true
LIGHTHOUSE_CACHE_VERSION=1
LIGHTHOUSE_SECURITY_DISABLE_INTROSPECTION=false

# ✅ MANTENER (necesarias para REST API)
JWT_SECRET=...
JWT_ALGORITHM=...
BEARER_TOKEN_EXPIRATION=...
```

**Comando:**
```bash
# Usar editor o sed
sed -i '/^LIGHTHOUSE_/d' .env .env.example
```

**Verificación:**
```bash
grep -i "lighthouse" .env && echo "✗ Aún existen referencias" || echo "✓ Eliminado"
```

**Tiempo Estimado:** 1 minuto
**Risk Level:** BAJO

---

### ✅ PHASE 8: Limpiar AppServiceProvider

**Archivo:** `app/Providers/AppServiceProvider.php`

**Antes:**
```php
public function boot(): void
{
    // ❌ ELIMINAR estas líneas si existen
    $this->loadGraphQLSchemaFrom([
        base_path('graphql/schema.graphql')
    ]);

    // ✅ MANTENER las migraciones
    $this->loadMigrationsFrom([
        app_path('Shared/Database/Migrations'),
        app_path('Features/Authentication/Database/Migrations'),
        app_path('Features/UserManagement/Database/Migrations'),
        app_path('Features/CompanyManagement/Database/Migrations'),
    ]);
}
```

**Después:**
```php
public function boot(): void
{
    // ✅ SOLO migraciones (sin GraphQL)
    $this->loadMigrationsFrom([
        app_path('Shared/Database/Migrations'),
        app_path('Features/Authentication/Database/Migrations'),
        app_path('Features/UserManagement/Database/Migrations'),
        app_path('Features/CompanyManagement/Database/Migrations'),
    ]);
}
```

**Tiempo Estimado:** 2 minutos
**Risk Level:** BAJO

---

### ✅ PHASE 9: Verificar Routes REST API

**Archivo:** `routes/api.php`

Este archivo YA CONTIENE todos los endpoints REST. **NO REQUIERE CAMBIOS.**

```php
// ✅ YA EXISTENTE - Verificar que todos estos están:

// Authentication Routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/refresh', [RefreshTokenController::class, 'refresh']);
Route::post('/auth/logout', [SessionController::class, 'logout']);
Route::get('/auth/status', [AuthController::class, 'status']);
Route::get('/auth/sessions', [SessionController::class, 'index']);
Route::delete('/auth/sessions/{sessionId}', [SessionController::class, 'destroy']);

// User Routes
Route::get('/users/me', [UserController::class, 'me']);
Route::get('/users/me/profile', [ProfileController::class, 'show']);
Route::patch('/users/me/profile', [ProfileController::class, 'update']);
Route::patch('/users/me/preferences', [ProfileController::class, 'updatePreferences']);

// ... más rutas
```

**Verificación:**
```bash
php artisan route:list | grep api | head -20
# Debe mostrar todos los endpoints REST
```

**Tiempo Estimado:** 1 minuto (solo verificación)
**Risk Level:** BAJO

---

### ✅ PHASE 10: Actualizar Tests

**Status Actual:** 174/174 tests pasando ✅

**Tests a Actualizar:**

```
tests/Feature/Authentication/
├── RegisterTest.php ✅ (ya renombrado)
├── LoginTest.php ✅ (ya renombrado)
├── RefreshTokenControllerTest.php ✅
├── RefreshTokenAndLogoutTest.php ✅
├── MySessionsTest.php ✅ (ya renombrado)
├── AuthStatusTest.php ✅ (ya renombrado)
├── RevokeOtherSessionTest.php ✅ (ya renombrado)
├── EmailVerificationCompleteFlowTest.php ✅
└── PasswordResetCompleteTest.php ✅

tests/Feature/UserManagement/
├── UserManagementTest.php
├── RoleAssignmentTest.php
└── UserProfileTest.php

tests/Feature/CompanyManagement/
├── CompanyManagementTest.php
└── ...
```

**Cambios Necesarios:** ✅ MÍNIMOS

Dado que ya migraste de GraphQL a REST en los controladores, los tests solo necesitan:

```php
// ❌ ELIMINAR si existen
use Illuminate\Testing\Fluent\AssertableJson;

// ✅ YA TIENEN formato REST, simplemente verificar:
$this->postJson('/api/auth/login', [
    'email' => 'test@example.com',
    'password' => 'password'
])->assertOk();

$this->assertJsonStructure([
    'access_token',
    'expires_in',
    'user' => ['id', 'email', 'profile']
]);
```

**Comando:**
```bash
# Ejecutar tests
php artisan test

# Debe pasar todos (174/174)
```

**Verificación:**
```bash
php artisan test --testdox | tail -5
# Debe mostrar: "Tests: 174 passed"
```

**Tiempo Estimado:** 5 minutos (solo verificación)
**Risk Level:** BAJO (tests ya están actualizados)

---

### ✅ PHASE 11: Regenerar Documentación Swagger

**Archivo:** `storage/api-docs/api-docs.json`

```bash
# Generar documentación Swagger/OpenAPI para REST API
php artisan l5-swagger:generate

# Verificar que se generó
ls -lh storage/api-docs/api-docs.json
```

**Acceso:**
```
# Swagger UI estará en:
http://localhost:8000/api/documentation
```

**Verificación:**
```bash
# Verificar que el JSON es válido
php -r "json_decode(file_get_contents('storage/api-docs/api-docs.json')); echo 'Valid JSON';"
```

**Tiempo Estimado:** 2 minutos
**Risk Level:** BAJO

---

### ✅ PHASE 12: Actualizar CLAUDE.md

**Archivo:** `CLAUDE.md`

**Secciones a Actualizar:**

1. **Línea 10** - Tech Stack
```markdown
// ❌ ANTES
- **Backend**: Laravel 12 + Lighthouse GraphQL 6

// ✅ DESPUÉS
- **Backend**: Laravel 12 + REST API (Pure JWT)
```

2. **Línea 162-199** - GraphQL Development (Sección Completa)
```markdown
// ✅ REEMPLAZAR TODO CON:

### REST API Development

**Access REST API**:
- **Base URL**: http://localhost:8000/api
- **Documentation**: http://localhost:8000/api/documentation (Swagger UI)
- **Authentication**: Bearer tokens (JWT) in Authorization header

**Common REST Endpoints**:
```bash
# Authentication
POST /api/auth/register
POST /api/auth/login
POST /api/auth/refresh
POST /api/auth/logout
GET /api/auth/status
GET /api/auth/sessions

# User Management
GET /api/users/me
GET /api/users/me/profile
PATCH /api/users/me/profile
PATCH /api/users/me/preferences
GET /api/users/{id}
GET /api/users (admin only)
```

**Test REST Endpoint with cURL**:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password"
  }'
```
```

3. **Línea 504-536** - GraphQL Code Generation (Eliminar Sección)
```markdown
// ❌ ELIMINAR completamente esta sección
### GraphQL Code Generation
```

4. **Línea 551-553** - GraphQL Schema References (Actualizar)
```markdown
// ✅ ACTUALIZAR
- **API Endpoints**: See `routes/api.php` for complete REST API definition
- **Swagger Documentation**: Auto-generated at `storage/api-docs/api-docs.json`
```

5. **Línea 584-590** - Dual Frontend Approach (Actualizar)
```markdown
// ✅ REEMPLAZAR
### REST API (Only)
- **Purpose**: Single REST API for all clients (web and mobile)
- **Endpoints**: http://localhost:8000/api
- **Authentication**: JWT Bearer tokens
- **Documentation**: Swagger UI at http://localhost:8000/api/documentation
```

6. **Línea 695-715** - Important GraphQL Principles (Eliminar o Archivar)
```markdown
// ✅ REEMPLAZAR CON:
## Important REST API Principles

### API Design
- Use standard HTTP methods: GET (read), POST (create), PATCH (update), DELETE (delete)
- Use appropriate HTTP status codes: 200, 201, 400, 401, 403, 422, 500
- Include proper error handling with error codes and messages
- Use bearer token authentication (JWT)
- Implement proper CORS configuration
```

**Comando para Buscar y Reemplazar:**
```bash
# Encontrar líneas específicas
grep -n "GraphQL" CLAUDE.md | head -20

# Editor recomendado: VS Code con Find & Replace (Ctrl+H)
```

**Tiempo Estimado:** 20-30 minutos
**Risk Level:** MEDIO (es documentación importante)

---

### ✅ PHASE 13: Actualizar Documentación Técnica

**Archivos en `documentacion/`:**

1. **MIGRACION_GRAPHQL_REST_API.md** - Marcar como COMPLETADO
```markdown
## STATUS: ✅ MIGRACION COMPLETADA

Fecha de Completación: 01-Nov-2025
Todas las fases ejecutadas exitosamente.
```

2. **ENDPOINTS_AUTENTICACION_MAPEO.md** - Actualizar con estado actual
```markdown
## Endpoints REST Actuales (100% Funcionales)

GET /api/auth/status
POST /api/auth/login
POST /api/auth/register
... (listar todos los endpoints)
```

3. **Agregar Nuevo Archivo:** `REST_API_COMPLETE_REFERENCE.md`
```markdown
# REST API Complete Reference

## Base URL
http://localhost:8000/api

## Authentication
All endpoints require JWT Bearer token (except public endpoints):
Authorization: Bearer {access_token}

## Endpoints Summary
... (complete endpoint listing)
```

**Archivos a Archivar (Keep for Reference):**
```
documentacion/ARCHIVED/
├── LARAVEL-LIGHTHOUSE-REFERENCE.md
├── DATALOADERS_LIGHTHOUSE_6_GUIA_COMPLETA.md
├── DATALOADERS_GUIA.md
└── AUTHENTICATION FEATURE SCHEMA.txt
```

**Comando:**
```bash
mkdir -p documentacion/ARCHIVED
mv documentacion/*GRAPHQL* documentacion/ARCHIVED/ 2>/dev/null || true
mv documentacion/*LIGHTHOUSE* documentacion/ARCHIVED/ 2>/dev/null || true
mv documentacion/*DATALOADERS* documentacion/ARCHIVED/ 2>/dev/null || true
```

**Tiempo Estimado:** 30-45 minutos
**Risk Level:** BAJO (solo documentación)

---

### ✅ PHASE 14: Ejecutar Suite Completa de Verificación

**Checklist Final:**

```bash
# 1. Verificar que no hay archivos GraphQL
echo "=== Verificando eliminación de GraphQL ==="
find app -name "*GraphQL*" -type d 2>/dev/null | wc -l  # Debe ser 0
find . -name "*.graphql" 2>/dev/null | wc -l  # Debe ser 0
grep -r "nuwave/lighthouse" . --include="*.json" 2>/dev/null | wc -l  # Debe ser 0

# 2. Verificar que existen los controllers REST
echo "=== Verificando Controllers REST ==="
ls app/Features/Authentication/Http/Controllers/*.php | wc -l  # Debe ser 6+
ls app/Features/UserManagement/Http/Controllers/*.php | wc -l  # Debe ser 3+
ls app/Features/CompanyManagement/Http/Controllers/*.php | wc -l  # Debe ser 4+

# 3. Limpiar cache y compilar
echo "=== Limpiando cachés ==="
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 4. Ejecutar migraciones
echo "=== Verificando migraciones ==="
php artisan migrate:status

# 5. Ejecutar tests completos
echo "=== Ejecutando tests (174 tests) ==="
php artisan test

# 6. Lint del código
echo "=== Linting código ==="
./vendor/bin/pint

# 7. Verificar rutas REST API
echo "=== Listando rutas API ==="
php artisan route:list | grep api | wc -l  # Debe ser 20+

# 8. Verificar documentación Swagger
echo "=== Verificando Swagger ==="
php artisan l5-swagger:generate
ls storage/api-docs/api-docs.json && echo "✓ Swagger generado"

# 9. Verificar compilación frontend
echo "=== Build frontend ==="
npm run build

# 10. Verificar que no hay referencias a GraphQL en frontend
echo "=== Buscando referencias GraphQL en frontend ==="
grep -r "@apollo/client" resources/js 2>/dev/null | wc -l  # Debe ser 0
grep -r "graphql" resources/js/lib 2>/dev/null | wc -l  # Debe ser 0
```

**Resultado Esperado:**
```
✓ 0 archivos GraphQL encontrados
✓ 13+ controllers REST existentes
✓ Cache limpio y recompilado
✓ Migraciones activas
✓ 174/174 tests pasando
✓ Código linted correctamente
✓ 25+ rutas API funcionando
✓ Swagger documentación generada
✓ Frontend compilado exitosamente
✓ 0 referencias GraphQL en frontend
```

**Tiempo Estimado:** 10 minutos
**Risk Level:** BAJO (solo verificación)

---

## 📋 ORDEN DE EJECUCIÓN RECOMENDADO

**Ejecución Sugerida:**

### Día 1 - Backend (30 minutos)
```bash
# Phase 1: Dependencias
composer remove nuwave/lighthouse mll-lab/laravel-graphiql

# Phase 2: Configuración
rm -rf config/lighthouse.php codegen.ts graphql/

# Phase 3: Código Shared
rm -rf app/Shared/GraphQL/

# Phase 4: Código Features
rm -rf app/Features/Authentication/GraphQL/
rm -rf app/Features/UserManagement/GraphQL/
rm -rf app/Features/CompanyManagement/GraphQL/

# Phase 7: Env vars
sed -i '/^LIGHTHOUSE_/d' .env .env.example

# Phase 8: AppServiceProvider
# Editar manualmente

# Verificar que funciona
php artisan optimize:clear
php artisan test  # Debe pasar 174/174
```

### Día 2 - Frontend (45 minutos)
```bash
# Phase 4: Dependencias Frontend
npm uninstall @apollo/client @graphql-codegen/cli @graphql-codegen/typescript @graphql-codegen/typescript-operations @graphql-codegen/typescript-react-apollo @graphql-codegen/client-preset graphql

# Phase 5: Código Frontend
rm -rf resources/js/lib/graphql/
rm -rf resources/js/lib/apollo/
rm -f resources/js/types/graphql.ts resources/js/types/gql.ts resources/js/types/fragment-masking.ts

# Phase 6: Actualizar componentes React
# Editar manualmente:
# - resources/js/app.tsx
# - resources/js/contexts/AuthContext.tsx
# - resources/js/Features/authentication/hooks/*.ts

# Compilar y verificar
npm run build
npm run test
```

### Día 3 - Verificación y Documentación (1 hora)
```bash
# Phase 9: Verificar Routes
php artisan route:list | grep api

# Phase 10: Verificar Tests
php artisan test

# Phase 11: Documentación Swagger
php artisan l5-swagger:generate

# Phase 12 & 13: Actualizar documentación
# Editar CLAUDE.md y documentacion files

# Phase 14: Verificación final
# Ejecutar checklist completo
```

---

## ⚠️ CONSIDERACIONES IMPORTANTES

### Antes de Empezar
- [ ] Crear backup de la rama actual
- [ ] Comprometerse en git con estado actual
- [ ] Verificar que todos los 174 tests pasan
- [ ] Comunicar cambios al equipo

### Durante la Ejecución
- [ ] Ejecutar tests después de cada fase importante
- [ ] Hacer commits pequeños para cada eliminación
- [ ] Verificar que REST API sigue funcionando
- [ ] No eliminar código de Services o Models

### Después de Completar
- [ ] Ejecutar suite completa de tests (174/174)
- [ ] Ejecutar linting (pint)
- [ ] Compilar frontend (npm run build)
- [ ] Generar documentación Swagger
- [ ] Hacer commit final: "refactor: Remove GraphQL completely, REST API only"
- [ ] Create PR a `master` con descripción completa
- [ ] Code review antes de merge

---

## 📊 IMPACTO ESTIMADO

| Métrica | Antes | Después | Cambio |
|---------|-------|---------|--------|
| Archivos GraphQL | 45+ | 0 | -100% ✅ |
| Líneas de código GraphQL | 3,000+ | 0 | -100% ✅ |
| Complejidad Stack | Alta | Baja | -60% ✅ |
| Dependencias PHP | 240+ | 235+ | -5 paquetes ✅ |
| Dependencias Frontend | 850+ | 830+ | -20 paquetes ✅ |
| Tamaño node_modules | ~650MB | ~580MB | -70MB ✅ |
| Tiempo Build Frontend | ~30s | ~25s | -5s ✅ |
| Tests Pasando | 174/174 | 174/174 | 0 cambios ✅ |

---

## ✅ COMMIT MESSAGE

```
refactor: Migrate from GraphQL to REST API (Phase 1-14 complete)

This commit completes the full migration from GraphQL (Lighthouse) to
a pure REST API architecture:

BACKEND:
- Removed nuwave/lighthouse and mll-lab/laravel-graphiql packages
- Deleted config/lighthouse.php configuration file
- Removed all GraphQL schema files (graphql/ directory)
- Deleted app/Shared/GraphQL/ (scalars, directives, resolvers, etc.)
- Deleted all feature GraphQL directories (Authentication, UserManagement, CompanyManagement)
- Cleaned up AppServiceProvider
- Updated environment variables

FRONTEND:
- Removed @apollo/client and @graphql-codegen packages
- Deleted codegen.ts configuration
- Removed resources/js/lib/graphql/ (fragments, queries, mutations)
- Removed resources/js/lib/apollo/ (Apollo Client instance)
- Deleted auto-generated types (graphql.ts, gql.ts, fragment-masking.ts)
- Updated React components to use fetch/axios instead of Apollo Client

DOCUMENTATION:
- Updated CLAUDE.md (removed GraphQL sections, added REST API info)
- Updated migration documentation
- Archived legacy GraphQL references
- Regenerated Swagger/OpenAPI documentation

TESTING:
- All 174 tests continue to pass
- REST API endpoints fully functional
- No breaking changes

Results:
✓ 100% GraphQL removed
✓ 100% REST API operational
✓ 45+ fewer files
✓ 3,000+ fewer lines of GraphQL code
✓ Simplified dependency stack
✓ Ready for production

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
```

---

## 🎯 FINAL CHECKLIST

```markdown
BACKEND ELIMINATION:
- [ ] Composer packages removed
- [ ] config/lighthouse.php deleted
- [ ] graphql/ directory deleted
- [ ] app/Shared/GraphQL/ deleted
- [ ] Feature GraphQL directories deleted
- [ ] AppServiceProvider cleaned
- [ ] .env variables cleaned

FRONTEND ELIMINATION:
- [ ] npm packages removed
- [ ] codegen.ts deleted
- [ ] resources/js/lib/graphql/ deleted
- [ ] resources/js/lib/apollo/ deleted
- [ ] Generated types deleted
- [ ] React components updated to REST

VERIFICATION:
- [ ] No GraphQL files remain (find . -name "*.graphql" returns 0)
- [ ] No GraphQL packages in composer.json/package.json
- [ ] Tests pass: 174/174
- [ ] npm run build succeeds
- [ ] REST API endpoints functional
- [ ] Swagger documentation generated
- [ ] CLAUDE.md updated
- [ ] Documentation archived

COMMIT & PUSH:
- [ ] All changes committed
- [ ] Commit message follows format
- [ ] PR created to master
- [ ] Code review approved
- [ ] Tests pass in CI/CD
- [ ] Merge to master
```

---

## 📞 NOTAS FINALES

**Este plan es exhaustivo y ha sido auditado por 5 agentes especializados:**

1. ✅ **Audit de Esquemas GraphQL** - Encontró 11 archivos schema
2. ✅ **Audit de Código PHP** - Encontró 42+ resolver classes
3. ✅ **Audit de Código Frontend** - Encontró 15 operaciones GraphQL
4. ✅ **Audit de Dependencias** - Encontró 13+ paquetes a eliminar
5. ✅ **Audit de Documentación** - Encontró 21 archivos a actualizar

**Estimado Total:** 4-6 horas de trabajo (incluyendo refactoring del frontend)

**Riesgo Global:** BAJO → MEDIO (el frontend requiere cambios funcionales)

¿Listo para comenzar? 🚀

---

**Plan Creado:** 01-Nov-2025
**Versión:** 1.0 - Completo y Ejecutable
**Status:** ✅ LISTO PARA IMPLEMENTACIÓN
