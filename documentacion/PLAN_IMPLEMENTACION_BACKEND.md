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
- [ ] Crear migraciones de auth schema
- [ ] Crear 4 Models con relaciones
- [ ] Implementar 3 Services con lógica completa
- [ ] Implementar 17 Resolvers funcionales
- [ ] Crear 2 Policies de autorización
- [ ] Crear Seeders para roles del sistema
- [ ] Crear Factories para testing
- [ ] Escribir tests (Feature + Unit)
- [ ] Probar en GraphiQL

**TIEMPO ESTIMADO:** 5-7 días

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
- [ ] Crear migración de refresh_tokens
- [ ] Implementar RefreshToken model
- [ ] Implementar 4 Services (Auth, Token, Google, PasswordReset)
- [ ] Configurar JWT (tymon/jwt-auth o similar)
- [ ] Implementar 18 Resolvers
- [ ] Crear Events & Listeners
- [ ] Crear Jobs para emails
- [ ] Configurar rate limiting por endpoint
- [ ] Tests de autenticación completos
- [ ] Test OAuth flow con Google

**TIEMPO ESTIMADO:** 4-6 días

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

**PRÓXIMO PASO:** Implementar FASE 1 (DataLoaders) en 2-3 días

¿Listo para comenzar con UserByIdLoader mañana? 🚀
