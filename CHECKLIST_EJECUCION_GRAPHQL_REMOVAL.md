# ✅ CHECKLIST INTERACTIVO: Eliminación GraphQL → REST API

**Inicio:** _____________
**Completado:** _____________
**Tiempo Total:** _____________

---

## 🔴 PHASE 1: Eliminar Dependencias PHP (Est. 2 min)

**Descripción:** Remover paquetes GraphQL del proyecto Laravel

```bash
# Comando a ejecutar:
composer remove nuwave/lighthouse mll-lab/laravel-graphiql
```

**Validaciones:**
- [ ] Comando ejecutado sin errores
- [ ] `nuwave/lighthouse` no aparece en `composer.json`
- [ ] `mll-lab/laravel-graphiql` no aparece en `composer.json`
- [ ] `firebase/php-jwt` sigue en `composer.json` ✓
- [ ] `composer update` ejecutado correctamente

**Commit Parcial:**
```bash
git add composer.json composer.lock
git commit -m "refactor: Remove GraphQL packages from composer"
```

**Status:**
- [ ] COMPLETADO ✅

---

## 🔴 PHASE 2: Eliminar Archivos de Configuración (Est. 1 min)

**Descripción:** Remover configuración específica de GraphQL

**Archivos a Eliminar:**
```bash
# Windows PowerShell
Remove-Item config/lighthouse.php -Force -ErrorAction SilentlyContinue
Remove-Item codegen.ts -Force -ErrorAction SilentlyContinue
Remove-Item graphql/ -Recurse -Force -ErrorAction SilentlyContinue

# Linux/WSL
rm -f config/lighthouse.php codegen.ts
rm -rf graphql/
```

**Validaciones:**
- [ ] `config/lighthouse.php` no existe
- [ ] `codegen.ts` no existe
- [ ] `graphql/` directorio no existe
- [ ] No hay otros archivos `.graphql` en proyecto
  ```bash
  find . -name "*.graphql" 2>/dev/null | wc -l  # Debe ser 0
  ```

**Verificar:**
```bash
ls config/lighthouse.php 2>/dev/null && echo "ERROR" || echo "✓ Eliminado"
ls codegen.ts 2>/dev/null && echo "ERROR" || echo "✓ Eliminado"
ls graphql/ 2>/dev/null && echo "ERROR" || echo "✓ Eliminado"
```

**Commit Parcial:**
```bash
git add -A
git commit -m "refactor: Remove GraphQL configuration files"
```

**Status:**
- [ ] COMPLETADO ✅

---

## 🔴 PHASE 3: Eliminar Backend GraphQL Code (Est. 2 min)

**Descripción:** Remover código GraphQL compartido y específico de features

### 3A: Eliminar Código Shared

```bash
# Verificar qué existe antes
ls -la app/Shared/GraphQL/

# Eliminar
rm -rf app/Shared/GraphQL/
```

**Validaciones:**
- [ ] `app/Shared/GraphQL/` no existe
- [ ] Verificar:
  ```bash
  find app/Shared -name "*GraphQL*" 2>/dev/null | wc -l  # Debe ser 0
  ```

### 3B: Eliminar Código Features

```bash
# Verificar qué existe
find app/Features -type d -name "GraphQL"

# Eliminar cada uno
rm -rf app/Features/Authentication/GraphQL/
rm -rf app/Features/UserManagement/GraphQL/
rm -rf app/Features/CompanyManagement/GraphQL/
```

**Validaciones:**
- [ ] `app/Features/Authentication/GraphQL/` no existe
- [ ] `app/Features/UserManagement/GraphQL/` no existe
- [ ] `app/Features/CompanyManagement/GraphQL/` no existe
- [ ] Verificar:
  ```bash
  find app/Features -name "*GraphQL*" -type d 2>/dev/null | wc -l  # Debe ser 0
  ```

**Commit Parcial:**
```bash
git add -A
git commit -m "refactor: Remove all backend GraphQL code"
```

**Status:**
- [ ] Shared GraphQL eliminado
- [ ] Feature GraphQL eliminado
- [ ] COMPLETADO ✅

---

## 🔴 PHASE 4: Eliminar Dependencias Frontend (Est. 2 min)

**Descripción:** Remover paquetes GraphQL y Apollo Client de npm

```bash
# Opción 1: Comando npm (recomendado)
npm uninstall @apollo/client @graphql-codegen/cli @graphql-codegen/typescript @graphql-codegen/typescript-operations @graphql-codegen/typescript-react-apollo @graphql-codegen/client-preset graphql

# Opción 2: Editar package.json manualmente y ejecutar npm install
```

**Validaciones:**
- [ ] Comando ejecutado sin errores
- [ ] `@apollo/client` no aparece en `package.json`
- [ ] `@graphql-codegen/cli` no aparece en `package.json`
- [ ] `graphql` no aparece en `package.json`
- [ ] Verificar:
  ```bash
  npm ls @apollo/client 2>&1 | grep -q "npm ERR!" && echo "✓ Eliminado" || echo "ERROR"
  npm ls @graphql-codegen/cli 2>&1 | grep -q "npm ERR!" && echo "✓ Eliminado" || echo "ERROR"
  ```

**Verificar scripts en package.json:**
```bash
grep -E "(codegen|graphql)" package.json
# No debe retornar nada
```

**Commit Parcial:**
```bash
git add package.json package-lock.json
git commit -m "refactor: Remove GraphQL packages from npm"
```

**Status:**
- [ ] COMPLETADO ✅

---

## 🔴 PHASE 5: Eliminar Código Frontend GraphQL (Est. 1 min)

**Descripción:** Remover directorio de operaciones GraphQL y Apollo Client

```bash
# Eliminar archivos
rm -rf resources/js/lib/graphql/
rm -rf resources/js/lib/apollo/
rm -f resources/js/types/graphql.ts
rm -f resources/js/types/gql.ts
rm -f resources/js/types/fragment-masking.ts
```

**Validaciones:**
- [ ] `resources/js/lib/graphql/` no existe
- [ ] `resources/js/lib/apollo/` no existe
- [ ] `resources/js/types/graphql.ts` no existe
- [ ] Verificar:
  ```bash
  ls resources/js/lib/graphql/ 2>/dev/null && echo "ERROR" || echo "✓ Eliminado"
  ls resources/js/lib/apollo/ 2>/dev/null && echo "ERROR" || echo "✓ Eliminado"
  ```

**Buscar referencias restantes:**
```bash
grep -r "@apollo/client" resources/js/ 2>/dev/null | wc -l  # Debe ser 0
grep -r "graphql" resources/js/lib/ 2>/dev/null | wc -l  # Debe ser 0
```

**Commit Parcial:**
```bash
git add -A
git commit -m "refactor: Remove frontend GraphQL code"
```

**Status:**
- [ ] COMPLETADO ✅

---

## 🟠 PHASE 6: Actualizar React Components para REST (Est. 20 min)

**Descripción:** Cambiar componentes de Apollo Client a fetch/axios REST

### 6A: Actualizar `resources/js/app.tsx`

**Archivo:** `resources/js/app.tsx`

Buscar y reemplazar:
```tsx
// ❌ ELIMINAR
import { ApolloProvider } from '@apollo/client';
import apolloClient from '@/lib/apollo/client';

// ❌ ELIMINAR wrapper
<ApolloProvider client={apolloClient}>
  // content
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

**Validaciones:**
- [ ] No hay imports de `@apollo/client`
- [ ] No hay `<ApolloProvider>` wrapper
- [ ] App compila sin errores

### 6B: Actualizar `resources/js/contexts/AuthContext.tsx`

Buscar y reemplazar todos los `useLazyQuery` y `useMutation`:

```tsx
// ❌ ELIMINAR
import { useLazyQuery, useMutation } from '@apollo/client';
import { AUTH_STATUS_QUERY, LOGOUT_MUTATION } from '@/lib/graphql/...';

const [getAuthStatus] = useLazyQuery(AUTH_STATUS_QUERY);

// ✅ REEMPLAZAR CON
import axios from 'axios';

const getAuthStatus = async () => {
  return await axios.get('/api/auth/status');
};
```

**Validaciones:**
- [ ] No hay imports de `@apollo/client`
- [ ] No hay queries GraphQL
- [ ] Todos los endpoints apuntan a `/api/*`
- [ ] Errores manejados correctamente

### 6C: Actualizar `resources/js/Features/authentication/hooks/useLogin.ts`

Buscar y reemplazar:
```tsx
// ❌ ELIMINAR
import { useMutation } from '@apollo/client';
import { LOGIN_MUTATION } from '@/lib/graphql/mutations/auth.mutations';

const [login, { loading, error }] = useMutation(LOGIN_MUTATION);

// ✅ REEMPLAZAR CON
import axios from 'axios';

const useLogin = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<Error | null>(null);

  const login = async (email: string, password: string) => {
    setLoading(true);
    try {
      const response = await axios.post('/api/auth/login', {
        email,
        password,
      });
      await TokenManager.setToken(
        response.data.access_token,
        response.data.expires_in,
        response.data.user,
        response.data.roleContexts
      );
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

**Validaciones:**
- [ ] No hay imports Apollo Client
- [ ] Todos los endpoints son REST (`/api/*`)
- [ ] TokenManager integrado
- [ ] Error handling implementado
- [ ] Loading state manejado

### 6D: Actualizar `resources/js/Features/authentication/hooks/useRegister.ts`

**Aplicar los mismos cambios que en useLogin.ts**

- [ ] No hay Apollo imports
- [ ] Endpoints REST
- [ ] TokenManager integrado

### 6E: Buscar Otros Archivos con Referencias GraphQL

```bash
# Buscar todos los archivos con referencias
grep -r "useMutation\|useLazyQuery\|useQuery" resources/js --include="*.ts" --include="*.tsx" | grep -v "node_modules"

grep -r "@apollo/client" resources/js --include="*.ts" --include="*.tsx"

grep -r "graphql" resources/js --include="*.ts" --include="*.tsx" | grep -v types
```

**Para cada archivo encontrado:**
- [ ] Analizar si es necesario
- [ ] Reemplazar GraphQL por REST
- [ ] Validar que funciona

**Compilar y Verificar:**
```bash
npm run build

# Verificar que no hay errores
echo $?  # Debe ser 0
```

**Commit Parcial:**
```bash
git add -A
git commit -m "refactor: Update React components from Apollo to REST API"
```

**Status:**
- [ ] app.tsx actualizado
- [ ] AuthContext actualizado
- [ ] useLogin actualizado
- [ ] useRegister actualizado
- [ ] Otros archivos actualizados
- [ ] Frontend compila exitosamente
- [ ] COMPLETADO ✅

---

## 🔴 PHASE 7: Limpiar Variables de Entorno (Est. 1 min)

**Descripción:** Remover variables de entorno específicas de GraphQL

**Archivos:** `.env`, `.env.example`, `.env.testing`

```bash
# Opción 1: Editar manualmente y buscar "LIGHTHOUSE"

# Opción 2: Usar sed para remover automáticamente
sed -i '/^LIGHTHOUSE_/d' .env .env.example .env.testing 2>/dev/null || true
```

**Verificar que no hay referencias:**
```bash
grep -i "lighthouse" .env .env.example 2>/dev/null || echo "✓ Eliminado"
```

**Validaciones:**
- [ ] No hay variables LIGHTHOUSE_* en .env
- [ ] No hay variables LIGHTHOUSE_* en .env.example
- [ ] JWT variables todavía existen (JWT_SECRET, etc.)
- [ ] Otros env vars intactos

**Commit Parcial:**
```bash
git add .env .env.example .env.testing
git commit -m "refactor: Remove GraphQL-related environment variables"
```

**Status:**
- [ ] COMPLETADO ✅

---

## 🔴 PHASE 8: Limpiar AppServiceProvider (Est. 2 min)

**Descripción:** Remover registros de GraphQL del proveedor de servicios

**Archivo:** `app/Providers/AppServiceProvider.php`

**Antes:**
```php
public function boot(): void
{
    $this->loadGraphQLSchemaFrom([
        base_path('graphql/schema.graphql')  // ❌ ELIMINAR
    ]);

    $this->loadMigrationsFrom([  // ✅ MANTENER
        app_path('Shared/Database/Migrations'),
        app_path('Features/Authentication/Database/Migrations'),
        // ...
    ]);
}
```

**Después:**
```php
public function boot(): void
{
    $this->loadMigrationsFrom([  // ✅ MANTENER
        app_path('Shared/Database/Migrations'),
        app_path('Features/Authentication/Database/Migrations'),
        app_path('Features/UserManagement/Database/Migrations'),
        app_path('Features/CompanyManagement/Database/Migrations'),
    ]);
}
```

**Validaciones:**
- [ ] No hay referencias a `loadGraphQLSchemaFrom`
- [ ] No hay imports de Lighthouse
- [ ] `loadMigrationsFrom` intacto y funcional
- [ ] Archivo compila sin errores

**Verificar:**
```bash
php artisan tinker <<< 'exit;'
echo $?  # Debe ser 0
```

**Commit Parcial:**
```bash
git add app/Providers/AppServiceProvider.php
git commit -m "refactor: Remove Lighthouse registrations from AppServiceProvider"
```

**Status:**
- [ ] COMPLETADO ✅

---

## 🟢 PHASE 9: Verificar REST API Routes (Est. 1 min)

**Descripción:** Confirmar que todas las rutas REST API existen y funcionan

**No requiere cambios, solo verificación**

```bash
# Listar todas las rutas API
php artisan route:list | grep api

# Contar rutas API
php artisan route:list | grep api | wc -l  # Debe ser 20+
```

**Validaciones:**
- [ ] POST /api/auth/register existe
- [ ] POST /api/auth/login existe
- [ ] POST /api/auth/refresh existe
- [ ] POST /api/auth/logout existe
- [ ] GET /api/auth/status existe
- [ ] GET /api/users/me existe
- [ ] GET /api/users/me/profile existe
- [ ] PATCH /api/users/me/profile existe
- [ ] PATCH /api/users/me/preferences existe
- [ ] Total de rutas API: _____ (mín. 20)

**Verificar que funcionan:**
```bash
# Iniciar servidor si no está
php artisan serve

# En otra terminal, probar un endpoint público
curl http://localhost:8000/api/health

# Debe retornar 200 OK con JSON
```

**Status:**
- [ ] Rutas verificadas
- [ ] Endpoints accesibles
- [ ] COMPLETADO ✅

---

## 🟢 PHASE 10: Verificar Tests (Est. 5 min)

**Descripción:** Ejecutar suite de tests para asegurar que todo sigue funcionando

**Ejecutar Tests:**
```bash
php artisan test

# Con reporte detallado
php artisan test --testdox
```

**Validaciones:**
- [ ] Tests ejecutados sin errores
- [ ] Total de tests: 174 ✅
- [ ] Número de tests pasados: _____
- [ ] Número de tests fallidos: _____ (debe ser 0)
- [ ] Tests en archivo RegisterTest.php: _____ pasando
- [ ] Tests en archivo LoginTest.php: _____ pasando
- [ ] Tests en archivo AuthStatusTest.php: _____ pasando
- [ ] Tests en archivo MySessionsTest.php: _____ pasando

**Si hay fallos:**
```bash
php artisan test --filter=NombreDelTestFallido --verbose
```

- [ ] Todos los fallos investigados
- [ ] Todos los fallos reparados
- [ ] Tests reejecutados
- [ ] 174/174 pasando

**Status:**
- [ ] COMPLETADO ✅

---

## 🟢 PHASE 11: Regenerar Documentación Swagger (Est. 2 min)

**Descripción:** Generar documentación Swagger/OpenAPI para REST API

```bash
# Generar documentación
php artisan l5-swagger:generate

# Verificar que se generó
ls -lh storage/api-docs/api-docs.json
```

**Validaciones:**
- [ ] Comando ejecutado sin errores
- [ ] Archivo `storage/api-docs/api-docs.json` existe
- [ ] Archivo tiene contenido (>100KB)
- [ ] JSON es válido:
  ```bash
  php -r "json_decode(file_get_contents('storage/api-docs/api-docs.json')); echo 'Valid JSON';"
  ```

**Acceder a Swagger UI:**
```bash
# Una vez que el servidor está corriendo
http://localhost:8000/api/documentation
```

- [ ] Swagger UI carga sin errores
- [ ] Todos los endpoints visibles
- [ ] Esquemas correctos

**Commit Parcial:**
```bash
git add storage/api-docs/api-docs.json
git commit -m "refactor: Regenerate Swagger documentation for REST API"
```

**Status:**
- [ ] COMPLETADO ✅

---

## 🟠 PHASE 12: Actualizar CLAUDE.md (Est. 30 min)

**Descripción:** Actualizar documentación principal del proyecto

**Secciones a Actualizar:**

### 12A: Tech Stack (línea ~10)

```markdown
// ❌ CAMBIAR DE
- **Backend**: Laravel 12 + Lighthouse GraphQL 6

// ✅ CAMBIAR A
- **Backend**: Laravel 12 + REST API (Pure JWT)
```

- [ ] Tech Stack actualizado

### 12B: GraphQL Development Section (línea ~162-199)

**Completamente reemplazar por:**
```markdown
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

- [ ] GraphQL Development section reemplazada

### 12C: GraphQL Code Generation Section (línea ~504-536)

**Completamente eliminar esta sección**

- [ ] Sección eliminada

### 12D: API Endpoints Documentation (línea ~551-553)

```markdown
// ✅ ACTUALIZAR
- **API Endpoints**: See `routes/api.php` for complete REST API definition
- **Documentation**: Swagger UI at http://localhost:8000/api/documentation
- **Schema**: Auto-generated at `storage/api-docs/api-docs.json`
```

- [ ] Actualizado

### 12E: Dual Frontend Approach (línea ~584-590)

```markdown
// ✅ ACTUALIZAR
### REST API (Only)
- **Purpose**: Single REST API for all clients (web and mobile)
- **Endpoints**: http://localhost:8000/api
- **Authentication**: JWT Bearer tokens
- **Documentation**: Swagger UI at http://localhost:8000/api/documentation
- **Status**: ✅ Fully functional
```

- [ ] Actualizado

### 12F: Important GraphQL Principles (línea ~695-715)

**Reemplazar con:**
```markdown
## Important REST API Principles

### API Design
- Use standard HTTP methods: GET (read), POST (create), PATCH (update), DELETE (delete)
- Use appropriate HTTP status codes: 200, 201, 400, 401, 403, 422, 500
- Include proper error handling with error codes and messages
- Use bearer token authentication (JWT)
- Implement proper CORS configuration
- Return consistent JSON response format
```

- [ ] REST API principles agregadas

**Validar archivo:**
```bash
# Buscar referencias GraphQL que quedan
grep -n "GraphQL" CLAUDE.md | head -10
grep -n "graphql" CLAUDE.md | head -10

# No debe haber referencias excepto históricas
```

**Commit Parcial:**
```bash
git add CLAUDE.md
git commit -m "docs: Update CLAUDE.md - REST API only documentation"
```

**Status:**
- [ ] Tech Stack actualizado
- [ ] GraphQL section reemplazada
- [ ] Code Generation section eliminada
- [ ] REST API principles agregados
- [ ] COMPLETADO ✅

---

## 🟠 PHASE 13: Actualizar Documentación Técnica (Est. 45 min)

**Descripción:** Actualizar archivos en documentacion/

### 13A: Marcar Migración como Completada

**Archivo:** `documentacion/MIGRACION_GRAPHQL_REST_API.md`

Agregar al inicio:
```markdown
## ✅ STATUS: MIGRACION COMPLETADA

**Fecha de Completación:** 01-Nov-2025
**Versión Final:** REST API 100% Funcional

Todas las fases ejecutadas exitosamente.
Sin referencias GraphQL en codebase.
Todos los 174 tests pasando.
```

- [ ] Completado

### 13B: Actualizar Endpoints Reference

**Archivo:** `documentacion/ENDPOINTS_AUTENTICACION_MAPEO.md`

Agregar sección:
```markdown
## Endpoints REST Actuales (100% Funcionales)

### Authentication
GET /api/auth/status
POST /api/auth/login
POST /api/auth/register
POST /api/auth/refresh
POST /api/auth/logout
GET /api/auth/sessions
DELETE /api/auth/sessions/{sessionId}

### User Management
GET /api/users/me
GET /api/users/me/profile
PATCH /api/users/me/profile
PATCH /api/users/me/preferences
...
```

- [ ] Completado

### 13C: Crear Nuevo Archivo: REST API Complete Reference

**Archivo:** `documentacion/REST_API_COMPLETE_REFERENCE.md`

Crear nuevo archivo con:
- Base URL: http://localhost:8000/api
- Authentication: JWT Bearer token
- All endpoints listing
- Error handling
- Status codes
- Examples

- [ ] Archivo creado

### 13D: Archivar Documentación GraphQL

```bash
mkdir -p documentacion/ARCHIVED_GRAPHQL

# Mover archivos
mv documentacion/*GRAPHQL* documentacion/ARCHIVED_GRAPHQL/ 2>/dev/null || true
mv documentacion/*LIGHTHOUSE* documentacion/ARCHIVED_GRAPHQL/ 2>/dev/null || true
mv documentacion/*DATALOADERS* documentacion/ARCHIVED_GRAPHQL/ 2>/dev/null || true
```

**Archivos archivados:**
- [ ] LARAVEL-LIGHTHOUSE-REFERENCE.md
- [ ] DATALOADERS_LIGHTHOUSE_6_GUIA_COMPLETA.md
- [ ] DATALOADERS_GUIA.md
- [ ] DATALOADERS_USAGE_GUIDE_COMPANY_MANAGEMENT.md
- [ ] AUTHENTICATION FEATURE SCHEMA.txt
- [ ] USER MANAGEMENT FEATURE SCHEMA.txt
- [ ] COMPANY MANAGEMENT FEATURE SCHEMA.txt

**Commit Parcial:**
```bash
git add documentacion/
git commit -m "docs: Archive GraphQL-specific documentation, update REST API docs"
```

**Status:**
- [ ] Migración marcada como completada
- [ ] Endpoints actualizados
- [ ] REST API reference creado
- [ ] Docs GraphQL archivados
- [ ] COMPLETADO ✅

---

## 🟢 PHASE 14: FINAL VERIFICATION (Est. 10 min)

**Descripción:** Suite completa de verificación antes de finalizar

### 14A: Verificar Eliminación de GraphQL

```bash
echo "=== VERIFICACION: Archivos GraphQL ==="

# No debe encontrar archivos GraphQL
find app -name "*GraphQL*" -type d 2>/dev/null
RESULT=$?
[ $RESULT -eq 0 ] && echo "✗ FOUND GraphQL directories" || echo "✓ No GraphQL dirs"
[ -z "$(find . -name "*.graphql" 2>/dev/null)" ] && echo "✓ No .graphql files" || echo "✗ FOUND .graphql files"

# No debe haber referencias a lighthouse
grep -r "nuwave/lighthouse" . --include="*.json" 2>/dev/null | wc -l
RESULT=$?
[ $RESULT -eq 0 ] && echo "✓ No Lighthouse references" || echo "✗ FOUND Lighthouse refs"
```

**Validaciones:**
- [ ] 0 archivos GraphQL encontrados
- [ ] 0 archivos .graphql encontrados
- [ ] 0 referencias a lighthouse

### 14B: Verificar Estructura REST API

```bash
echo "=== VERIFICACION: REST API Controllers ==="

# Controllers deben existir
ls app/Features/Authentication/Http/Controllers/*.php | wc -l
# Debe ser: 6+

ls app/Features/UserManagement/Http/Controllers/*.php | wc -l
# Debe ser: 3+

ls app/Features/CompanyManagement/Http/Controllers/*.php | wc -l
# Debe ser: 4+
```

**Validaciones:**
- [ ] Authentication controllers: _____ (mín. 6)
- [ ] UserManagement controllers: _____ (mín. 3)
- [ ] CompanyManagement controllers: _____ (mín. 4)

### 14C: Limpiar y Compilar

```bash
echo "=== COMPILACION Y CACHE ==="

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✓ Cache cleared and recompiled"
```

**Validaciones:**
- [ ] optimize:clear ejecutado
- [ ] config:cache ejecutado
- [ ] route:cache ejecutado
- [ ] view:cache ejecutado

### 14D: Verificar Migraciones

```bash
echo "=== MIGRACIONES ==="

php artisan migrate:status | grep "Y\|N" | wc -l
# Debe mostrar todas las migraciones

php artisan tinker
# > Schema::hasTable('users')
# > true
# > exit
```

**Validaciones:**
- [ ] Migraciones listadas correctamente
- [ ] Tablas existen
- [ ] DB accesible

### 14E: Ejecutar Tests Completos

```bash
echo "=== EJECUTAR TESTS (174/174) ==="

php artisan test

# Con reporte
php artisan test --testdox | tail -20
```

**Validaciones:**
- [ ] Tests ejecutados: 174
- [ ] Tests pasados: 174 ✅
- [ ] Tests fallidos: 0
- [ ] Duración: _____

**Si falla:**
```bash
# Ejecutar tests en modo verbose
php artisan test --verbose
```

- [ ] Todos los fallos resueltos
- [ ] 174/174 pasando

### 14F: Verificar Linting

```bash
echo "=== CODE LINTING ==="

./vendor/bin/pint --test

# Si hay errores, arreglar
./vendor/bin/pint

echo "✓ Code linting passed"
```

**Validaciones:**
- [ ] Pint ejecutado sin errores
- [ ] Código formateado correctamente

### 14G: Frontend Build

```bash
echo "=== FRONTEND BUILD ==="

npm run build

# Verificar que no hay errores
echo $?  # Debe ser 0

echo "✓ Frontend build successful"
```

**Validaciones:**
- [ ] npm run build sin errores
- [ ] Build exitoso
- [ ] Output generado en public/

### 14H: Verificar APIs y Documentación

```bash
echo "=== VERIFICACION: APIs Y DOCUMENTACION ==="

# Generar Swagger
php artisan l5-swagger:generate

# Verificar que existe
[ -f storage/api-docs/api-docs.json ] && echo "✓ Swagger generated" || echo "✗ Swagger missing"

# Verificar JSON válido
php -r "json_decode(file_get_contents('storage/api-docs/api-docs.json')); echo 'Valid JSON';" 2>/dev/null && echo "✓ Valid JSON" || echo "✗ Invalid JSON"
```

**Validaciones:**
- [ ] Swagger generado
- [ ] JSON válido
- [ ] API documentada

### 14I: Resummen Final

```bash
echo "=== RESUMEN FINAL ==="

echo "✓ GraphQL eliminado: $([ -z "$(find . -name "*.graphql" 2>/dev/null)" ] && echo "SI" || echo "NO")"
echo "✓ REST API funcionando: $(php artisan route:list 2>&1 | grep -c /api)"
echo "✓ Tests pasando: $(php artisan test 2>&1 | grep -c "passed" || echo "0/174")"
echo "✓ Frontend compilado: $([ -d public/build ] && echo "SI" || echo "NO")"
echo "✓ Swagger generado: $([ -f storage/api-docs/api-docs.json ] && echo "SI" || echo "NO")"
```

**Output Esperado:**
```
✓ GraphQL eliminado: SI
✓ REST API funcionando: 25+ endpoints
✓ Tests pasando: 174/174
✓ Frontend compilado: SI
✓ Swagger generado: SI
```

**Validaciones:**
- [ ] GraphQL eliminado: ✓
- [ ] REST API funcionando: ✓
- [ ] Tests pasando: ✓
- [ ] Frontend compilado: ✓
- [ ] Swagger generado: ✓

### 14J: Commit Final

```bash
git status  # Verificar cambios

git add -A

git commit -m "refactor: Complete GraphQL removal - REST API only

BACKEND:
- Removed Lighthouse GraphQL and related packages
- Deleted all GraphQL schema files (graphql/ directory)
- Removed all feature GraphQL code
- Cleaned up configuration and environment variables

FRONTEND:
- Removed Apollo Client and @graphql-codegen packages
- Updated React components to use fetch/axios REST
- Deleted auto-generated GraphQL types

VERIFICATION:
- All 174 tests passing ✅
- REST API fully functional with 25+ endpoints
- Swagger documentation generated
- Code linting passed
- Frontend build successful

Migration completed successfully!

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>"

git log --oneline | head -5  # Ver últimos commits
```

**Validaciones:**
- [ ] Cambios commiteados
- [ ] Commit message descriptivo
- [ ] Historial de git limpio

**Status:**
- [ ] COMPLETADO ✅

---

## 📊 RESUMEN FINAL

**Inicio:** _____________
**Completado:** _____________
**Tiempo Total:** _____________

### Phases Completados:
- [ ] Phase 1: Dependencias PHP ✅
- [ ] Phase 2: Archivos de Configuración ✅
- [ ] Phase 3: Backend GraphQL Code ✅
- [ ] Phase 4: Dependencias Frontend ✅
- [ ] Phase 5: Código Frontend GraphQL ✅
- [ ] Phase 6: React Components → REST ✅
- [ ] Phase 7: Env Variables ✅
- [ ] Phase 8: AppServiceProvider ✅
- [ ] Phase 9: Verificar Routes ✅
- [ ] Phase 10: Tests ✅
- [ ] Phase 11: Swagger Docs ✅
- [ ] Phase 12: CLAUDE.md ✅
- [ ] Phase 13: Documentación Técnica ✅
- [ ] Phase 14: Verificación Final ✅

### Métricas Finales:
- Archivos GraphQL Eliminados: _____
- Líneas de Código Eliminadas: _____
- Tests Pasando: 174/174 ✅
- Rutas REST Funcionales: _____
- Documentación Actualizada: ✅
- Frontend Compilado: ✅

### Status General:
- [ ] ✅ 100% COMPLETADO Y VERIFICADO

---

**🎉 ¡MIGRACION EXITOSA!**

Proyecto migrado de GraphQL a REST API 100%.
Todos los tests pasando.
Documentación actualizada.
Listo para producción.

---

*Fecha de Completación: ________________*
*Firmado por: ________________*