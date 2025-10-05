 # 🎯 PLAN DE IMPLEMENTACIÓN BACKEND - Sistema Helpdesk

**Fecha:** 01 de Octubre de 2025
**Estado Actual:** Schema-First completado, 43 resolvers dummy creados
**Objetivo:** Implementar lógica real de los 3 features principales

---

## 📚 ANÁLISIS DE DOCUMENTACIÓN COMPLETADO

### ✅ Lo que ya está hecho:
- ✅ GraphQL Schemas completos (3 features)
- ✅ 43 Resolvers dummy (todos retornan null/arrays vacíos)
- ✅ Scalars personalizados (UUID, PhoneNumber, HexColor)
- ✅ Directivas básicas (@auth, @can, @company, @audit, @rateLimit)
- ✅ Base types anti-loop (UserBasicInfo, CompanyBasicInfo)
- ✅ Schema validado exitosamente
- ✅ Docker + PostgreSQL + Redis configurado
- ✅ Inertia.js + React funcionando

### ❌ Lo que falta implementar:
- ❌ **Models Eloquent con relaciones**
- ❌ **Migraciones de base de datos** (18 tablas + 4 schemas)
- ❌ **Services con lógica de negocio**
- ❌ **Resolvers funcionales** (43 archivos)
- ❌ **DataLoaders para N+1** (CRÍTICO!)
- ❌ **Policies de autorización**
- ❌ **Events & Listeners**
- ❌ **Jobs para tareas asíncronas**
- ❌ **Tests unitarios y de integración**

---

## 🔍 DEPENDENCIAS ENTRE FEATURES

```
┌─────────────────────────────────────┐
│     SHARED (Foundation)             │
│  - Enums, Traits, Exceptions       │
│  - Base Models, Services            │
│  - DataLoaders (CRÍTICO!)           │
└──────────────┬──────────────────────┘
               ↓
┌──────────────────────────────────────┐
│   USER MANAGEMENT (Núcleo)           │
│  Models: User, UserProfile, UserRole │
│  - Base para todo el sistema         │
└──────────────┬───────────────────────┘
               ↓
    ┌──────────┴───────────┐
    ↓                      ↓
┌─────────────────┐  ┌──────────────────────┐
│  AUTHENTICATION │  │ COMPANY MANAGEMENT   │
│  (Usa User)     │  │ (Usa User como admin)│
└─────────────────┘  └──────────────────────┘
```

**ORDEN OBLIGATORIO:**
1. **Shared** (base común)
2. **UserManagement** (modelos centrales)
3. **Authentication** Y **CompanyManagement** (en paralelo)

---

## 🎯 FASE 1: DATALOADERS - CRÍTICO PARA N+1 (2-3 días)

**¿POR QUÉ PRIMERO?**
- Sin DataLoaders, las queries anidadas causan N+1 queries
- Lighthouse funciona mejor con DataLoaders desde el inicio
- Evita refactorizar después

### DataLoaders Necesarios (6 críticos):

#### 1. `UserByIdLoader`
```php
app/Shared/GraphQL/DataLoaders/UserByIdLoader.php
```
**Uso:** Cargar usuarios en relaciones (created_by, assigned_to, etc.)

#### 2. `CompanyByIdLoader`
```php
app/Shared/GraphQL/DataLoaders/CompanyByIdLoader.php
```
**Uso:** Cargar empresas en contexto de roles y tickets

#### 3. `UserProfileByUserIdLoader`
```php
app/Shared/GraphQL/DataLoaders/UserProfileByUserIdLoader.php
```
**Uso:** Cargar perfiles de usuarios (relación 1:1)

#### 4. `UserRolesByUserIdLoader`
```php
app/Shared/GraphQL/DataLoaders/UserRolesByUserIdLoader.php
```
**Uso:** Cargar roles activos de usuarios

#### 5. `CompaniesByUserIdLoader`
```php
app/Shared/GraphQL/DataLoaders/CompaniesByUserIdLoader.php
```
**Uso:** Cargar empresas donde usuario tiene roles

#### 6. `UsersByCompanyIdLoader`
```php
app/Shared/GraphQL/DataLoaders/UsersByCompanyIdLoader.php
```
**Uso:** Cargar usuarios de una empresa (agentes, admins)

**CHECKLIST FASE 1:**
- [x] Crear directorio `app/Shared/GraphQL/DataLoaders/`
- [x] Implementar los 6 DataLoaders básicos
- [x] Registrar DataLoaders en `config/lighthouse.php`
- [ ] Crear tests unitarios para cada DataLoader ⏳ (pending)
- [x] Documentar uso de DataLoaders

**TIEMPO ESTIMADO:** 2-3 días
**ESTADO:** ✅ COMPLETADO (01-Oct-2025)

---

## 🎯 FASE 2: SHARED FOUNDATION (3-4 días)

### 2.1 Enums (app/Shared/Enums/)
```
✅ Ya definidos en schema, crear clases PHP:
- UserStatus (active, suspended, deleted)
- Role (platform_admin, company_admin, agent, user)
- CompanyStatus (active, suspended)
- TicketStatus (open, pending, resolved, closed)
- CompanyRequestStatus (pending, approved, rejected)
```

### 2.2 Traits (app/Shared/Traits/)
```
- HasUuid.php (para generar UUIDs automáticamente)
- Auditable.php (para tracking de cambios)
- BelongsToCompany.php (para validar contexto empresarial)
- SoftDeletes.php (si no usamos el de Laravel)
```

### 2.3 Exceptions (app/Shared/Exceptions/)
```
- AuthenticationException.php
- AuthorizationException.php
- ValidationException.php
- NotFoundException.php
- RateLimitException.php
```

### 2.4 Helpers (app/Shared/Helpers/)
```
- CodeGenerator.php (para USR-2025-00001, CMP-2025-00001)
- DateTimeHelper.php (para manejo de timezones)
- ValidationHelper.php (reglas custom)
```

**CHECKLIST FASE 2:**
- [x] Crear todos los Enums con sus métodos helper
- [x] Implementar Traits reutilizables
- [x] Crear Exceptions personalizadas
- [x] Implementar Helpers (CodeGenerator)
- [ ] Tests unitarios para Helpers ⏳ (pending)
- [x] Documentar uso de cada componente

**TIEMPO ESTIMADO:** 3-4 días
**ESTADO:** ✅ COMPLETADO (01-Oct-2025)

---

## 🎯 FASE 3: USER MANAGEMENT - NÚCLEO DEL SISTEMA (5-7 días)

**¿POR QUÉ PRIMERO ENTRE FEATURES?**
- Authentication y CompanyManagement dependen del modelo User
- Todos los features necesitan gestión de usuarios
- Define la estructura de roles (crítico para multi-tenant)

### 3.1 Migraciones de Base de Datos
```
app/Features/UserManagement/Database/Migrations/
├── 2025_10_01_000001_create_auth_schema.php
├── 2025_10_01_000002_create_users_table.php
├── 2025_10_01_000003_create_user_profiles_table.php
├── 2025_10_01_000004_create_roles_table.php
├── 2025_10_01_000005_create_user_roles_table.php
└── 2025_10_01_000006_insert_system_roles.php (seeder en migración)
```

**Crítico:** Crear schema `auth` y todas las tablas con:
- UUIDs como primary keys
- Índices para performance
- Foreign keys con ON DELETE CASCADE
- Triggers para updated_at

### 3.2 Models Eloquent
```
app/Features/UserManagement/Models/
├── User.php (modelo principal)
├── UserProfile.php (relación 1:1)
├── UserRole.php (pivot table mejorada)
└── Role.php (catálogo fijo)
```

**Relaciones críticas:**
```php
// User.php
hasOne(UserProfile::class)
hasMany(UserRole::class)
belongsToMany(Company::class, 'user_roles')
```

### 3.3 Services
```
app/Features/UserManagement/Services/
├── UserService.php (CRUD de usuarios)
├── ProfileService.php (gestión de perfiles)
└── RoleService.php (asignación de roles)
```

**Lógica de negocio:**
- Validación de roles con contexto empresarial
- Soft delete de usuarios
- Sincronización de preferencias
- Contadores de estadísticas

### 3.4 Resolvers Funcionales (17 archivos)
```
app/Features/UserManagement/GraphQL/
├── Queries/ (6 archivos)
│   ├── MeQuery.php
│   ├── MyProfileQuery.php
│   ├── UsersQuery.php (paginado)
│   ├── UserQuery.php
│   ├── CompanyUsersQuery.php
│   └── AvailableRolesQuery.php
└── Mutations/ (11 archivos)
    ├── UpdateMyProfileMutation.php
    ├── UpdateMyPreferencesMutation.php
    ├── CompleteMyProfileMutation.php
    ├── CreateUserMutation.php
    ├── UpdateUserMutation.php
    ├── SuspendUserMutation.php
    ├── ActivateUserMutation.php
    ├── DeleteUserMutation.php
    ├── AssignRoleMutation.php
    ├── RevokeRoleMutation.php
    └── UpdateUserRoleMutation.php
```

### 3.5 Policies
```
app/Features/UserManagement/Policies/
├── UserPolicy.php
└── UserRolePolicy.php
```

### 3.6 Tests
```
tests/Feature/UserManagement/
├── UserQueriesTest.php
├── UserMutationsTest.php
├── ProfileManagementTest.php
└── RoleManagementTest.php

tests/Unit/Services/UserManagement/
├── UserServiceTest.php
├── ProfileServiceTest.php
└── RoleServiceTest.php
```

**CHECKLIST FASE 3:**
- [x] Crear 5 migraciones de auth schema ✅
  - [x] create_auth_schema.php
  - [x] create_users_table.php
  - [x] create_user_profiles_table.php
  - [x] create_roles_table.php (con 4 roles insertados)
  - [x] create_user_roles_table.php
- [x] Crear 4 Models con relaciones ✅
  - [x] User.php (con métodos de auth, verificación, roles, actividad)
  - [x] UserProfile.php (información personal y preferencias)
  - [x] Role.php (catálogo de roles con permisos)
  - [x] UserRole.php (pivot multi-tenant)
- [x] Implementar 3 Services con lógica completa ✅
  - [x] UserService.php (CRUD, passwords, verificación, términos, stats)
  - [x] ProfileService.php (info personal, avatar, preferencias UI/notificaciones)
  - [x] RoleService.php (asignación roles, permisos, multi-tenant)
- [x] Crear 2 Policies de autorización ✅
  - [x] UserPolicy.php (viewAny, view, create, update, delete, suspend)
  - [x] UserRolePolicy.php (assign, revoke, update)
- [x] Crear 8 Events ✅
  - [x] UserCreated, UserUpdated, UserSuspended, UserActivated
  - [x] UserDeleted, UserProfileUpdated, RoleAssigned, RoleRevoked
- [x] Crear 4 Factories para testing ✅
  - [x] UserFactory.php, UserProfileFactory.php
  - [x] RoleFactory.php, UserRoleFactory.php
- [x] Crear 2 Seeders ✅
  - [x] RolesSeeder.php (4 roles del sistema)
  - [x] DemoUsersSeeder.php (usuarios de prueba para desarrollo)
- [x] Actualizar 3 DataLoaders con modelos reales ✅
  - [x] UserByIdLoader → usa User::class
  - [x] UserProfileByUserIdLoader → usa UserProfile::class
  - [x] UserRolesByUserIdLoader → usa UserRole::class
- [ ] ⏳ Implementar 17 Resolvers funcionales (POSPUESTO - requiere Authentication)
- [ ] ⏳ Escribir tests (Feature + Unit) (después de Resolvers)
- [ ] ⏳ Probar en GraphiQL (después de Authentication)

**ESTRATEGIA ITERATIVA:**
Se decidió implementar Authentication PRIMERO antes de completar los resolvers de UserManagement, porque:
- Los resolvers necesitan Auth::user() para funcionar
- No se pueden testear sin login/register
- Es mejor validar iterativamente: Auth → Test → UserMgmt Resolvers → Test

**TIEMPO ESTIMADO:** 3-4 días (sin resolvers por ahora)
**ESTADO:** ✅ COMPLETADO (Foundation) - 01-Oct-2025

---

## 🎯 FASE 4: AUTHENTICATION FEATURE (4-6 días)

**Depende de:** UserManagement (usa modelo User)

### 4.1 Migraciones
```
app/Features/Authentication/Database/Migrations/
└── 2025_10_02_000001_create_refresh_tokens_table.php
```

### 4.2 Models
```
app/Features/Authentication/Models/
└── RefreshToken.php
```

### 4.3 Services
```
app/Features/Authentication/Services/
├── AuthService.php (login, register, logout)
├── TokenService.php (JWT + refresh tokens)
├── GoogleAuthService.php (OAuth Google)
└── PasswordResetService.php (reset de contraseñas)
```

**Lógica crítica:**
- Generación de JWT con claims (user_id, roles, companies)
- Refresh token rotation (invalidar anterior)
- Rate limiting por IP
- Email verification tokens
- Password reset con expiración

### 4.4 Resolvers (18 archivos)
```
app/Features/Authentication/GraphQL/
├── Queries/ (4 archivos)
│   ├── AuthStatusQuery.php
│   ├── MySessionsQuery.php
│   ├── PasswordResetStatusQuery.php
│   └── EmailVerificationStatusQuery.php
└── Mutations/ (14 archivos)
    ├── RegisterMutation.php
    ├── LoginMutation.php
    ├── LoginWithGoogleMutation.php
    ├── RefreshTokenMutation.php
    ├── LogoutMutation.php
    ├── RevokeSessionMutation.php
    ├── ResetPasswordMutation.php
    ├── ConfirmPasswordResetMutation.php
    ├── VerifyEmailMutation.php
    └── ResendEmailVerificationMutation.php
```

### 4.5 Events & Listeners
```
app/Features/Authentication/Events/
├── UserRegistered.php
├── UserLoggedIn.php
├── UserLoggedOut.php
└── PasswordResetRequested.php

app/Features/Authentication/Listeners/
├── SendVerificationEmail.php
├── SendPasswordResetEmail.php
└── LogLoginActivity.php
```

### 4.6 Jobs
```
app/Features/Authentication/Jobs/
├── SendEmailVerificationJob.php
└── SendPasswordResetEmailJob.php
```

**CHECKLIST FASE 4:**
- [x] ✅ Crear migración de refresh_tokens
- [x] ✅ Implementar RefreshToken model
- [x] ✅ Implementar 3 Services (Auth, Token, PasswordReset) + Configs
- [ ] ⏳ Configurar JWT (instalar firebase/php-jwt)
- [ ] ⏳ Implementar 14 Resolvers (PHASE 4-Puentes)
- [x] ✅ Crear 6 Events
- [x] ✅ Crear 3 Listeners
- [x] ✅ Crear 2 Jobs para emails
- [x] ✅ Crear 2 Mails (EmailVerificationMail, PasswordResetMail)
- [x] ✅ Configurar rate limiting (config/rate-limiting.php)
- [ ] ⏳ Vistas Blade de emails (4 archivos)
- [ ] ⏳ Registrar Listeners en EventServiceProvider
- [ ] ⏳ GoogleAuthService (opcional Phase 4B)
- [ ] ⏳ Tests de autenticación completos

**TIEMPO ESTIMADO:** 2 días (Infrastructure) + 1-2 días (Resolvers) = 3-4 días
**ESTADO:** ✅ Infrastructure COMPLETADA (01-Oct-2025) - ⏳ Resolvers pendientes

---

## 🎯 FASE 5: COMPANY MANAGEMENT FEATURE (4-5 días)

**Depende de:** UserManagement (usa User para admin_user_id)

### 5.1 Migraciones
```
app/Features/CompanyManagement/Database/Migrations/
├── 2025_10_03_000001_create_business_schema.php
├── 2025_10_03_000002_create_company_requests_table.php
├── 2025_10_03_000003_create_companies_table.php
├── 2025_10_03_000004_add_company_fk_to_user_roles.php
└── 2025_10_03_000005_create_company_followers_table.php (opcional, futuro)
```

### 5.2 Models
```
app/Features/CompanyManagement/Models/
├── Company.php
└── CompanyRequest.php
```

### 5.3 Services
```
app/Features/CompanyManagement/Services/
├── CompanyService.php (CRUD empresas)
├── CompanyRequestService.php (flujo de solicitudes)
└── CompanyConfigService.php (configuración)
```

**Lógica crítica:**
- Aprobación automática: crear empresa + crear/asignar admin
- Generación de códigos (CMP-2025-00001)
- Validación de contexto empresarial
- Suspensión en cascada (desactivar agentes)

### 5.4 Resolvers (12 archivos)
```
app/Features/CompanyManagement/GraphQL/
├── Queries/ (5 archivos)
│   ├── PublicCompaniesQuery.php
│   ├── CompanyQuery.php
│   ├── MyCompaniesQuery.php
│   ├── CompaniesQuery.php (admin, paginado)
│   └── CompanyRequestsQuery.php
└── Mutations/ (7 archivos)
    ├── RequestCompanyMutation.php
    ├── ApproveCompanyRequestMutation.php
    ├── RejectCompanyRequestMutation.php
    ├── CreateCompanyMutation.php
    ├── UpdateCompanyMutation.php
    ├── SuspendCompanyMutation.php
    └── ActivateCompanyMutation.php
```

### 5.5 Policies
```
app/Features/CompanyManagement/Policies/
└── CompanyPolicy.php
```

**CHECKLIST FASE 5:**
- [ ] Crear migraciones de business schema
- [ ] Crear 2 Models con relaciones
- [ ] Implementar 3 Services
- [ ] Implementar 12 Resolvers
- [ ] Crear CompanyPolicy
- [ ] Implementar proceso de aprobación automática
- [ ] Tests de flujo completo (request -> approve -> company)
- [ ] Probar contexto multi-tenant

**TIEMPO ESTIMADO:** 4-5 días

---

## 🎯 FASE 6: REFINAMIENTO Y OPTIMIZACIÓN (3-4 días)

### 6.1 Auditoría Automática
```
- Implementar trigger de audit_logs
- Configurar directiva @audit funcional
- Tests de auditoría
```

### 6.2 Rate Limiting Avanzado
```
- Configurar límites por endpoint
- Implementar cache en Redis
- Mensajes personalizados
```

### 6.3 Directives Funcionales
```
app/Shared/GraphQL/Directives/
├── CompanyDirective.php (validar contexto empresarial)
├── AuditDirective.php (logging automático)
└── RateLimitDirective.php (throttling)
```

### 6.4 Performance
```
- Verificar N+1 con Telescope/Debugbar
- Optimizar queries con eager loading
- Cache de queries frecuentes (availableRoles, etc.)
```

### 6.5 Documentación
```
- Documentar todos los Services
- Documentar DataLoaders y su uso
- Ejemplos de queries en GraphiQL
```

**CHECKLIST FASE 6:**
- [ ] Audit logs funcionales
- [ ] Rate limiting completo
- [ ] Directivas funcionando
- [ ] Performance optimizado (sin N+1)
- [ ] Documentación actualizada
- [ ] Tests E2E de flujos completos

**TIEMPO ESTIMADO:** 3-4 días

---

## 📊 RESUMEN DE TIEMPOS

| Fase | Descripción | Tiempo Estimado | Prioridad |
|------|-------------|-----------------|-----------|
| **1** | DataLoaders | 2-3 días | 🔴 CRÍTICO |
| **2** | Shared Foundation | 3-4 días | 🔴 CRÍTICO |
| **3** | UserManagement | 5-7 días | 🔴 CRÍTICO |
| **4** | Authentication | 4-6 días | 🟠 ALTO |
| **5** | CompanyManagement | 4-5 días | 🟠 ALTO |
| **6** | Refinamiento | 3-4 días | 🟡 MEDIO |
| **TOTAL** | | **21-29 días** | |

**Distribución:**
- Semanas 1-2: Phases 1-3 (Foundation + UserManagement)
- Semanas 3-4: Phases 4-5 (Authentication + CompanyManagement)
- Semana 5: Phase 6 (Refinamiento + Testing)

---

## ✅ CHECKLIST GENERAL POR COMPONENTE

### Por cada Feature, implementar:
- [ ] Migraciones de base de datos
- [ ] Models Eloquent con relaciones
- [ ] Services con lógica de negocio
- [ ] Resolvers GraphQL funcionales
- [ ] Policies de autorización
- [ ] Events & Listeners (si aplica)
- [ ] Jobs asíncronos (si aplica)
- [ ] DataLoaders específicos
- [ ] Factories para testing
- [ ] Seeders iniciales
- [ ] Tests unitarios (Services)
- [ ] Tests de integración (Resolvers)
- [ ] Documentación del feature

---

## 🚀 RECOMENDACIÓN: ¿POR DÓNDE EMPEZAR MAÑANA?

**EMPEZAR POR: FASE 1 - DATALOADERS**

### ¿Por qué?
1. **Crítico para performance:** Sin DataLoaders, tendrás N+1 desde el inicio
2. **Independiente:** Puedes hacerlo sin Models completos (usar arrays temporales)
3. **Rápido:** 2-3 días vs 5-7 de UserManagement
4. **Educativo:** Aprenderás el patrón que usarás en todo el proyecto

### Plan de Acción Día 1-3:

#### **Día 1: Setup + 2 DataLoaders básicos**
```bash
# Crear estructura
mkdir -p app/Shared/GraphQL/DataLoaders

# Implementar:
- UserByIdLoader
- CompanyByIdLoader

# Tests básicos con datos mock
```

#### **Día 2: 2 DataLoaders de relaciones**
```bash
# Implementar:
- UserProfileByUserIdLoader
- UserRolesByUserIdLoader

# Registrar en lighthouse.php
# Tests completos
```

#### **Día 3: 2 DataLoaders finales + Integración**
```bash
# Implementar:
- CompaniesByUserIdLoader
- UsersByCompanyIdLoader

# Integrar con un resolver dummy como prueba
# Documentar uso
```

### Resultado esperado al final de Día 3:
✅ 6 DataLoaders funcionales
✅ Tests pasando
✅ Documentación clara
✅ Listo para FASE 2 (Shared Foundation)

---

## 📝 NOTAS IMPORTANTES

### Principles to Follow:
1. **TDD cuando sea posible:** Tests primero, implementación después
2. **Service Layer obligatorio:** NUNCA lógica de negocio en Resolvers
3. **DataLoaders siempre:** Evitar N+1 desde el inicio
4. **Feature-First:** Todo dentro de su feature, compartir solo lo necesario
5. **Eloquent puro:** No SQL directo salvo casos excepcionales

### Common Pitfalls to Avoid:
- ❌ No crear loops infinitos en tipos GraphQL
- ❌ No poner lógica en Resolvers (solo delegación)
- ❌ No olvidar índices en migraciones
- ❌ No skip eager loading (causa N+1)
- ❌ No hardcodear valores (usar Enums)

### Tools necesarios:
- Laravel Telescope (debugging)
- Laravel Debugbar (N+1 detection)
- PHPUnit (testing)
- GraphiQL/Apollo Sandbox (testing GraphQL)

---

## 🎯 ESTRATEGIA DEFINITIVA - FLUJO DE PUENTES (01-Oct-2025)

### 📐 FILOSOFÍA: "Construir Primero, Conectar Después"

**Concepto clave:** Los **Resolvers son PUENTES** que conectan GraphQL con la lógica de negocio.

#### Proceso por Feature:
```
1. 

2. CONECTAR mediante Resolvers (uno a uno):
   ├─ Implementar RegisterMutation
   ├─ TESTEAR en GraphiQL ← ¡En caliente!
   │  ├─ ✅ Funciona? → Siguiente resolver
   │  └─ ❌ Error? → PARAR, investigar, corregir, refactorizar, documentar
   ├─ Implementar LoginMutation
   ├─ TESTEAR en GraphiQL
   └─ Continuar iterativamente...

3. VALIDAR feature completo
4. SIGUIENTE feature
```

### ✅ Completado hasta ahora:

#### ✅ **FASE 1** - DataLoaders (COMPLETADO)
- 6 DataLoaders base (3 con modelos reales, 3 con mock)

#### ✅ **FASE 2** - Shared Foundation (COMPLETADO)
- 4 Enums (UserStatus, Role, CompanyStatus, CompanyRequestStatus)
- 2 Traits (HasUuid, Auditable)
- 5 Exceptions
- 1 Helper (CodeGenerator)

#### ✅ **FASE 3** - UserManagement Foundation (COMPLETADO)
- ✅ 5 Migraciones (auth schema + tablas)
- ✅ 4 Models (User, UserProfile, Role, UserRole)
- ✅ 3 Services (UserService, ProfileService, RoleService)
- ✅ 2 Policies (UserPolicy, UserRolePolicy)
- ✅ 8 Events (UserCreated, UserUpdated, etc.)
- ✅ 4 Factories
- ✅ 2 Seeders
- ⏸️ 17 Resolvers → POSPUESTOS (son puentes, se conectan después)

---

### 🔄 Nuevo Orden de Implementación:

#### 📍 **FASE 4: AUTHENTICATION - Construcción Completa**
**Objetivo:** Construir TODA la infraestructura de Authentication SIN resolvers

**Construir:**
1. ✅ Migrations (refresh_tokens)
2. ✅ Models (RefreshToken)
3. ✅ Services (AuthService, TokenService, PasswordResetService)
4. ✅ Policies (si necesita)
5. ✅ Events (UserRegistered, UserLoggedIn, UserLoggedOut, PasswordResetRequested)
6. ✅ Listeners (SendVerificationEmail, SendPasswordResetEmail, LogLoginActivity)
7. ✅ Jobs (SendEmailVerificationJob, SendPasswordResetEmailJob)
8. ✅ Factories (RefreshTokenFactory)
9. ✅ Seeders (si necesita)
10. ✅ Actualizar Shared (si necesita nuevos Enums, Exceptions, etc.)

**Luego Conectar Puentes (Resolvers) uno por uno:**
1. RegisterMutation → TESTEAR → ✅ o ❌ → Corregir
2. LoginMutation → TESTEAR → ✅ o ❌ → Corregir
3. RefreshTokenMutation → TESTEAR
4. LogoutMutation → TESTEAR
5. (continuar con los 14 resolvers restantes)

---


---

## 🎯 VENTAJAS DE ESTA ESTRATEGIA:

✅ **Validación inmediata:** Cada resolver se prueba apenas se conecta
✅ **Debugging rápido:** Si falla, sabemos exactamente qué resolver tiene el problema
✅ **Rollback fácil:** Si un resolver falla, solo desconectamos ese puente
✅ **Desarrollo paralelo posible:** Podemos construir infrastructure mientras otro conecta puentes
✅ **Código sin validar reducido:** No acumulamos 43 resolvers sin probar
✅ **Feedback loop ultra-corto:** Codificar → Conectar → Testear → Corregir (minutos, no días)

---

## 🚨 PROTOCOLO DE ERROR:

Cuando un Resolver falla al testear:

```
❌ ERROR DETECTADO
  ↓
🔍 INVESTIGAR
  - ¿Error en el Service?
  - ¿Error en el Model?
  - ¿Error en la Migration?
  - ¿Error en el Resolver mismo?
  ↓
🔧 CORREGIR
  - Fix en el archivo correspondiente
  - NO hacer workarounds
  ↓
♻️ REFACTORIZAR
  - ¿Mejora el diseño?
  - ¿Hay código duplicado?
  ↓
📝 DOCUMENTAR
  - Actualizar comentarios
  - Actualizar PLAN si cambió algo
  ↓
✅ RE-TESTEAR
  - Probar el resolver que falló
  - Probar resolvers relacionados
  ↓
✅ FUNCIONA? → SIGUIENTE RESOLVER
❌ SIGUE FALLANDO? → REPETIR CICLO
```

---

## 📊 TIEMPO ACTUALIZADO

| Fase | Descripción | Tiempo Real | Estado                        |
|------|-------------|-------------|-------------------------------|
| **1** | DataLoaders | 1 día | ✅ COMPLETADO                  |
| **2** | Shared Foundation | 1 día | ✅ COMPLETADO                  |
| **3** | UserManagement Infrastructure | 1 día | ✅ COMPLETADO                  |
| **4** | Authentication Infrastructure | 1 día | ✅ COMPLETADO ( qq01-Oct-2025) |
| **4-Puentes** | Authentication Resolvers | 1-2 días | 🔄 SIGUIENTE                  |
| **4B-Puentes** | UserManagement Resolvers | 1-2 días | ⏳ Después                     |
| **5** | CompanyManagement Infrastructure | 2 días | ⏳ Pendiente                   |
| **5-Puentes** | CompanyManagement Resolvers | 1 día | ⏳ Pendiente                   |
| **6** | Refinamiento | 2-3 días | ⏳ Pendiente                   |
| **TOTAL REAL** | | **4 días hechos** | **9-14 días restantes**       |

---

## 📍 ESTADO ACTUAL (01-Oct-2025 23:30)

✅ **Authentication Infrastructure COMPLETADA:**
- 21 archivos de infrastructure creados en 1 día
- Services funcionan con dependency injection
- Events/Listeners/Jobs listos para pruebas
- Configs (JWT, Rate Limiting) listos

