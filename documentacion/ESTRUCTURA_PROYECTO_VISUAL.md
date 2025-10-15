# 📁 Estructura del Proyecto Helpdesk

> **Proyecto:** Sistema de Helpdesk Multi-tenant con Laravel + GraphQL (Lighthouse)  
> **Arquitectura:** Feature-Based (Modular)  
> **Fecha:** Octubre 2025

---

## 📂 Vista General

```
Helpdesk/
├── 🎯 app/                          # Código de la aplicación
│   ├── Features/                    # Módulos principales (Feature-Based Architecture)
│   ├── Http/                        # Controladores y Middleware
│   ├── Providers/                   # Service Providers
│   └── Shared/                      # Código compartido entre features
│
├── ⚙️ config/                       # Archivos de configuración
├── 🐳 docker/                       # Configuración Docker
├── 📖 documentacion/                # Documentación del proyecto
├── 🔌 graphql/                      # Esquemas GraphQL
├── 🌐 public/                       # Archivos públicos
├── 💾 resources/                    # Vistas, CSS, JS
├── 🛣️ routes/                       # Rutas de la aplicación
├── 🧪 tests/                        # Tests automatizados
└── 📦 vendor/                       # Dependencias de Composer
```

---

## 🎯 Módulo: Features (Arquitectura Modular)

### 📋 Estructura de cada Feature

Cada feature es un módulo **autocontenido** con la siguiente estructura:

```
Feature/
├── Database/
│   ├── Factories/          # Factory para generar datos de prueba
│   ├── Migrations/         # Migraciones de base de datos
│   └── Seeders/           # Seeders para datos iniciales
│
├── Events/                # Eventos del dominio
├── Exceptions/            # Excepciones específicas del feature
├── GraphQL/               # Resolvers y tipos GraphQL
│   ├── DataLoaders/      # Batch loading (N+1 queries)
│   ├── Errors/           # Manejadores de error
│   ├── Mutations/        # Mutaciones GraphQL
│   ├── Queries/          # Consultas GraphQL
│   ├── Schema/           # Definiciones de esquema
│   └── Types/            # Tipos personalizados
│
├── Jobs/                  # Jobs para colas
├── Listeners/             # Event Listeners
├── Mail/                  # Clases para envío de emails
├── Models/                # Modelos Eloquent
├── Policies/              # Políticas de autorización
├── Services/              # Lógica de negocio
└── [Feature]ServiceProvider.php   # Service Provider del módulo
```

---

## 🔐 Feature: Authentication

```
Authentication/
├── Events/
│   ├── EmailVerified.php
│   ├── PasswordResetCompleted.php
│   ├── PasswordResetRequested.php
│   ├── UserLoggedIn.php
│   ├── UserLoggedOut.php
│   └── UserRegistered.php
│
├── Exceptions/
│   ├── AuthenticationException.php
│   ├── CannotRevokeCurrentSessionException.php
│   ├── EmailNotVerifiedException.php
│   ├── InvalidCredentialsException.php
│   ├── InvalidRefreshTokenException.php
│   ├── RefreshTokenExpiredException.php
│   ├── RefreshTokenRequiredException.php
│   ├── SessionNotFoundException.php
│   ├── TokenExpiredException.php
│   └── TokenInvalidException.php
│
├── Jobs/
│   ├── SendEmailVerificationJob.php
│   └── SendPasswordResetEmailJob.php
│
├── Listeners/
│   ├── LogLoginActivity.php
│   ├── SendPasswordResetEmail.php
│   └── SendVerificationEmail.php
│
├── Mail/
│   ├── EmailVerificationMail.php
│   └── PasswordResetMail.php
│
├── Models/
│   └── RefreshToken.php
│
├── Services/
│   ├── AuthService.php
│   ├── PasswordResetService.php
│   └── TokenService.php
│
└── AuthenticationServiceProvider.php
```

**Funcionalidades:**
- ✅ Registro de usuarios con verificación de email
- ✅ Login/Logout con JWT
- ✅ Refresh tokens
- ✅ Gestión de sesiones
- ✅ Recuperación de contraseña
- ✅ Auditoría de actividad

---

## 🏢 Feature: CompanyManagement

```
CompanyManagement/
├── Events/
│   ├── CompanyActivated.php
│   ├── CompanyCreated.php
│   ├── CompanyFollowed.php
│   ├── CompanyRequestApproved.php
│   ├── CompanyRequestRejected.php
│   ├── CompanyRequestSubmitted.php
│   ├── CompanySuspended.php
│   ├── CompanyUnfollowed.php
│   └── CompanyUpdated.php
│
├── Jobs/
│   ├── SendCompanyApprovalEmailJob.php
│   ├── SendCompanyRejectionEmailJob.php
│   └── SendCompanyRequestEmailJob.php
│
├── Listeners/
│   ├── CreateCompanyFromRequest.php
│   ├── NotifyAdminOfNewRequest.php
│   ├── SendApprovalEmail.php
│   ├── SendCompanyRequestConfirmationEmail.php
│   └── SendRejectionEmail.php
│
├── Models/
│   ├── Company.php
│   ├── CompanyFollower.php
│   └── CompanyRequest.php
│
├── Policies/
│   └── CompanyPolicy.php
│
├── Services/
│   ├── CompanyFollowService.php
│   ├── CompanyRequestService.php
│   └── CompanyService.php
│
└── CompanyManagementServiceProvider.php
```

**Funcionalidades:**
- ✅ Sistema multi-tenant
- ✅ Solicitudes de creación de empresas
- ✅ Aprobación/Rechazo de empresas
- ✅ Seguimiento de empresas
- ✅ Gestión de estados (activa/suspendida)
- ✅ Notificaciones por email

---

## 👥 Feature: UserManagement

```
UserManagement/
├── Events/
│   ├── RoleAssigned.php
│   ├── RoleRevoked.php
│   ├── UserActivated.php
│   ├── UserCreated.php
│   ├── UserDeleted.php
│   ├── UserProfileUpdated.php
│   ├── UserSuspended.php
│   └── UserUpdated.php
│
├── Models/
│   ├── Role.php
│   ├── User.php
│   ├── UserProfile.php
│   └── UserRole.php
│
├── Policies/
│   ├── UserPolicy.php
│   └── UserRolePolicy.php
│
├── Services/
│   ├── ProfileService.php
│   ├── RoleService.php
│   └── UserService.php
│
└── UserManagementServiceProvider.php
```

**Funcionalidades:**
- ✅ Gestión de usuarios
- ✅ Sistema de roles contextuales (Global + por empresa)
- ✅ Perfiles de usuario
- ✅ Suspender/Activar usuarios
- ✅ Eliminación lógica de usuarios
- ✅ Gestión de preferencias

---

## 🔗 Shared (Código Compartido)

```
Shared/
├── Constants/              # Constantes globales
│
├── Database/
│   └── Migrations/        # Migraciones compartidas (schemas, funciones)
│       ├── 0000_00_00_000000_create_postgresql_extensions_and_functions.php
│       ├── 2025_10_07_000001_create_ticketing_schema.php
│       ├── 2025_10_07_000002_create_audit_schema.php
│       └── 2025_10_07_000003_create_audit_log_changes_function.php
│
├── Enums/                 # Enumeraciones compartidas
│   ├── CompanyRequestStatus.php
│   ├── CompanyStatus.php
│   ├── Role.php
│   └── UserStatus.php
│
├── Exceptions/            # Excepciones base
│   ├── AuthenticationException.php
│   ├── AuthorizationException.php
│   ├── ConflictException.php
│   ├── ForbiddenException.php
│   ├── HelpdeskException.php (Base)
│   ├── NotFoundException.php
│   ├── RateLimitExceededException.php
│   ├── UnauthorizedException.php
│   └── ValidationException.php
│
├── GraphQL/               # GraphQL compartido
│   ├── DataLoaders/      # DataLoaders reutilizables
│   │   ├── CompaniesByUserIdLoader.php
│   │   ├── CompanyByIdLoader.php
│   │   ├── UserByIdLoader.php
│   │   ├── UserProfileBatchLoader.php
│   │   ├── UserProfileByUserIdLoader.php
│   │   ├── UserRoleContextsBatchLoader.php
│   │   ├── UserRolesBatchLoader.php
│   │   ├── UserRolesByUserIdLoader.php
│   │   └── UsersByCompanyIdLoader.php
│   │
│   ├── Directives/       # Directivas personalizadas
│   │   ├── AuditDirective.php
│   │   ├── CompanyDirective.php
│   │   ├── JwtDirective.php
│   │   └── RateLimitDirective.php
│   │
│   ├── Errors/           # Sistema de manejo de errores
│   │   ├── BaseErrorHandler.php
│   │   ├── CustomAuthenticationErrorHandler.php
│   │   ├── CustomAuthorizationErrorHandler.php
│   │   ├── CustomValidationErrorHandler.php
│   │   ├── EnvironmentErrorFormatter.php
│   │   ├── ErrorCodeRegistry.php
│   │   └── GraphQLErrorFormatter.php
│   │
│   ├── Mutations/        # Mutaciones base
│   │   └── BaseMutation.php
│   │
│   ├── Queries/          # Queries compartidas
│   │   ├── BaseQuery.php
│   │   ├── HealthQuery.php
│   │   ├── PingQuery.php
│   │   └── VersionQuery.php
│   │
│   ├── Scalars/          # Tipos escalares personalizados
│   │   ├── DateTimeScalar.php
│   │   ├── Email.php
│   │   ├── HexColor.php
│   │   ├── JSON.php
│   │   ├── PhoneNumber.php
│   │   ├── URL.php
│   │   └── UUID.php
│   │
│   ├── Types/            # Tipos GraphQL compartidos
│   └── Unions/           # Union types
│
├── Helpers/              # Funciones helper
│   ├── CodeGenerator.php
│   └── DeviceInfoParser.php
│
├── Models/               # Modelos base (si los hay)
├── Services/             # Servicios compartidos
│
└── Traits/               # Traits reutilizables
    ├── Auditable.php    # Auditoría automática
    └── HasUuid.php      # UUIDs como primary key
```

---

## 🔌 GraphQL Schemas

```
graphql/
├── shared/
│   ├── base-types.graphql      # Tipos base compartidos
│   ├── directives.graphql      # Directivas personalizadas
│   ├── enums.graphql          # Enumeraciones
│   ├── inputs.graphql         # Input types
│   ├── interfaces.graphql     # Interfaces GraphQL
│   ├── pagination.graphql     # Paginación
│   └── scalars.graphql        # Tipos escalares
│
└── schema.graphql             # Schema principal (importa todo)
```

**Características:**
- 🔒 Autenticación JWT con directivas `@jwt`
- 📊 Paginación con relay/cursor
- 🔍 DataLoaders para evitar N+1 queries
- ⚡ Rate limiting
- 📝 Auditoría automática
- 🎯 Directivas personalizadas

---

## 🧪 Tests

```
tests/
├── Feature/
│   ├── Authentication/
│   │   ├── AuthStatusQueryTest.php
│   │   ├── EmailVerificationCompleteFlowTest.php
│   │   ├── LoginMutationTest.php
│   │   ├── MySessionsQueryTest.php
│   │   ├── RefreshTokenAndLogoutTest.php
│   │   ├── RegisterMutationTest.php
│   │   └── RevokeOtherSessionMutationTest.php
│   │
│   ├── CompanyManagement/
│   │   └── [En desarrollo]
│   │
│   ├── UserManagement/
│   │   ├── AssignRoleMutationTest.php
│   │   ├── AvailableRolesQueryTest.php
│   │   ├── DeleteUserMutationTest.php
│   │   ├── MeQueryTest.php
│   │   ├── MyProfileQueryTest.php
│   │   ├── RemoveRoleMutationTest.php
│   │   ├── SuspendAndActivateUserMutationsTest.php
│   │   ├── UpdateMyPreferencesMutationTest.php
│   │   ├── UpdateMyProfileMutationTest.php
│   │   ├── UserQueryTest.php
│   │   └── UsersQueryTest.php
│   │
│   └── GraphQL/
│       └── ErrorFormattingTest.php
│
├── GraphQL/
│   └── BasicQueriesTest.php
│
├── Unit/
│   └── ExampleTest.php
│
└── TestCase.php
```

**Cobertura:** ~85% en features implementadas

---

## 🐳 Docker

```
docker/
├── nginx/
│   └── default.conf          # Configuración Nginx
│
├── php/
│   ├── Dockerfile           # Imagen PHP-FPM
│   ├── entrypoint.sh        # Script de inicio
│   ├── entrypoint-vite.sh   # Script para Vite
│   ├── local.ini            # Configuración PHP
│   └── www.conf             # Pool PHP-FPM
│
└── postgres/
    ├── create-multiple-databases.sh
    └── init.sql             # Inicialización DB
```

**Stack:**
- 🐘 PHP 8.3 FPM
- 🐘 PostgreSQL 16
- 🌐 Nginx 1.25
- ⚡ Redis (caché y colas)

---

## ⚙️ Configuración

```
config/
├── app.php              # Configuración general
├── auth.php            # Autenticación
├── cache.php           # Sistema de caché
├── cors.php            # CORS
├── database.php        # Base de datos
├── filesystems.php     # Almacenamiento
├── jwt.php             # JWT tokens
├── lighthouse.php      # GraphQL (Lighthouse)
├── logging.php         # Logs
├── mail.php            # Email
├── queue.php           # Colas
├── rate-limiting.php   # Rate limiting
├── services.php        # Servicios externos
└── session.php         # Sesiones
```

---

## 📖 Documentación

```
documentacion/
├── AUDITORIA_SERVICES_CORRECCION_FINAL.md
├── AUDITORIA_SERVICES_DATALOADERS_V7.md
├── AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt
├── AUTHENTICATION FEATURE SCHEMA.txt
├── COMPANY MANAGEMENT FEATURE - DOCUMENTACIÓN.txt
├── COMPANY MANAGEMENT FEATURE SCHEMA.txt
├── DATALOADERS_GUIA.md
├── EMAIL_VERIFICATION_IMPLEMENTATION.md
├── ESTADO_COMPLETO_PROYECTO.md
├── ESTRUCTURA_PROYECTO_VISUAL.md         # 👈 Este archivo
├── GraphQL-Examples.md
├── GRAPHQL_PLAYGROUND_GUIA.md
├── GUIA_ESTRUCTURA_CARPETAS_PROYECTO.md
├── GUIA_IMPLEMENTACION_REGISTER_MUTATION.md
├── idea completa pero no es el mvp.txt
├── LARAVEL-LIGHTHOUSE-REFERENCE.md
├── Modelado final de base de datos.txt
├── OPINION_PROFESIONAL_MODELADO_V7.md
├── OPTIMIZACION-RENDIMIENTO.md
├── PLAN_IMPLEMENTACION_BACKEND.md
├── SISTEMA_ERRORES_GRAPHQL_IMPLEMENTADO.md
├── USER MANAGEMENT FEATURE SCHEMA.txt
└── USER MANAGMENT FEATURE - DOCUMENTACION.txt
```

---

## 📦 Frontend (Recursos)

```
resources/
├── css/
│   └── app.css
│
├── js/
│   ├── Pages/
│   │   ├── Home.tsx
│   │   └── VerifyEmail.tsx
│   ├── app.jsx
│   └── bootstrap.js
│
└── views/
    ├── emails/
    │   └── auth/
    │       ├── verify-email.blade.php
    │       └── verify-email-text.blade.php
    └── app.blade.php
```

**Stack Frontend:**
- ⚛️ React 18
- 📘 TypeScript
- 🎨 Inertia.js
- ⚡ Vite

---

## 🛣️ Rutas

```
routes/
├── api.php          # Rutas API REST (si las hay)
├── console.php      # Comandos Artisan
└── web.php          # Rutas web (Inertia)
```

**Nota:** La mayoría de la lógica está en GraphQL, no en REST.

---

## 🚀 Scripts de Deployment

```
scripts/
├── deploy-dev.sh           # Deploy a desarrollo
├── deploy-prod.sh          # Deploy a producción
└── optimize-performance.sh # Optimizaciones
```

---

## 📊 Características del Proyecto

### ✅ Implementado

- **Authentication:**
  - Registro con verificación de email
  - Login/Logout con JWT
  - Refresh tokens
  - Gestión de sesiones
  - Recuperación de contraseña

- **User Management:**
  - CRUD de usuarios
  - Sistema de roles contextuales
  - Perfiles de usuario
  - Suspender/Activar usuarios

- **Company Management:**
  - Multi-tenancy
  - Solicitudes de empresa
  - Aprobación/Rechazo
  - Seguimiento de empresas

- **GraphQL:**
  - Schema completo
  - DataLoaders (N+1 prevención)
  - Directivas personalizadas
  - Sistema de errores robusto
  - Rate limiting

- **Infraestructura:**
  - Docker completo
  - PostgreSQL con schemas
  - Sistema de auditoría
  - Logging avanzado
  - Tests automatizados

### 🚧 En Desarrollo

- **Ticketing System** (Próximo MVP)
- **Notifications System**
- **Analytics Dashboard**

---

## 🏗️ Principios de Arquitectura

### 1. **Feature-Based Architecture**
Cada feature es un módulo autocontenido con su propia estructura completa.

### 2. **Separation of Concerns**
- **Models:** Solo definición de datos y relaciones
- **Services:** Toda la lógica de negocio
- **Resolvers:** Solo validación y llamadas a servicios
- **Policies:** Autorización separada

### 3. **Event-Driven**
Uso extensivo de Events y Listeners para desacoplar funcionalidades.

### 4. **DataLoaders**
Prevención de N+1 queries mediante batch loading.

### 5. **Auditoría Automática**
Trait `Auditable` para tracking automático de cambios.

### 6. **Multi-Tenancy**
Aislamiento por empresa con contexto global/empresa.

---

## 🔒 Seguridad

- ✅ JWT para autenticación
- ✅ Rate limiting por endpoint
- ✅ Políticas de autorización granulares
- ✅ Validación exhaustiva de inputs
- ✅ Sanitización de errores en producción
- ✅ CORS configurado
- ✅ Auditoría de todas las acciones críticas

---

## 📈 Performance

- ⚡ DataLoaders para batch queries
- ⚡ Caché con Redis
- ⚡ Índices de base de datos optimizados
- ⚡ Eager loading estratégico
- ⚡ Jobs en cola para operaciones pesadas
- ⚡ OPcache configurado
- ⚡ Preload.php para clases críticas

---

## 📝 Convenciones de Código

### Nomenclatura:
- **Clases:** PascalCase
- **Métodos:** camelCase
- **Variables:** camelCase
- **Constantes:** UPPER_SNAKE_CASE
- **Archivos:** PascalCase.php
- **Tablas:** snake_case (plural)
- **Columnas:** snake_case

### GraphQL:
- **Types:** PascalCase
- **Fields:** camelCase
- **Inputs:** PascalCase + "Input"
- **Enums:** UPPER_SNAKE_CASE

---

## 🎯 Próximos Pasos

1. ⏳ **Ticketing System Feature**
   - Modelos: Ticket, TicketMessage, TicketAttachment
   - Estados: Open, In Progress, Resolved, Closed
   - Prioridades: Low, Medium, High, Critical
   - Asignación y escalado

2. ⏳ **Notification System**
   - In-app notifications
   - Email notifications
   - Push notifications (futuro)

3. ⏳ **Analytics Dashboard**
   - Métricas de tickets
   - Performance de agentes
   - SLA tracking

---

## 📞 Contacto y Soporte

Para más información, consulta los archivos de documentación en `/documentacion/`.

---

**Generado:** Octubre 2025  
**Versión:** 1.0  
**Estado:** En desarrollo activo

