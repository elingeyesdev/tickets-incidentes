
# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Helpdesk System - Laravel 12 + React + Inertia.js

This is a helpdesk system built with Laravel 12 backend, React 18 frontend via Inertia.js, and planned GraphQL API for future mobile apps.

### Tech Stack
- **Backend**: Laravel 12 + Lighthouse GraphQL 6
- **Frontend Web**: React 19 + Inertia.js (TypeScript support available)
- **Database**: PostgreSQL 17 (4 schemas: auth, business, ticketing, audit)
- **Authentication**: JWT with Refresh Tokens
- **Build Tools**: Vite 7 + TailwindCSS 4
- **Development**: Docker + Docker Compose

### Docker Services
- `app` - PHP-FPM application container (Laravel)
- `nginx` - Web server (port 8000)
- `postgres` - PostgreSQL 17 database (port 5432)
- `redis` - Redis cache/session store (port 6379)
- `queue` - Laravel queue worker (background jobs)
- `scheduler` - Laravel task scheduler (cron)
- `mailpit` - Email testing (SMTP:1025, UI:8025)

### Key Commands

**Development**:
```bash
# Start all services with Docker
docker compose up

# Start in background
docker compose up -d

# Frontend development (Vite HMR) - inside container
docker compose exec app npm run dev

# Build for production
docker compose exec app npm run build

# Stop all services
docker compose down

# View logs (all services)
docker compose logs -f

# View logs (specific service)
docker compose logs -f app
```

**Windows-Specific Notes**:
- Running on Laravel Herd (local PHP installation)
- Use PowerShell for better command compatibility
- Local PHP commands run faster than Docker for CPU-intensive tasks
- Use `powershell -Command "php artisan ..."` for artisan commands when Docker is slow

**Testing**:
```bash
# Run all tests
docker compose exec app php artisan test

# Run specific feature tests
docker compose exec app php artisan test --filter=[Feature]

# Run via composer
docker compose exec app composer test
```

**Laravel**:
```bash
# Run migrations (all features)
docker compose exec app php artisan migrate

# Run specific seeder
docker compose exec app php artisan db:seed --class=Features\\[Feature]\\Database\\Seeders\\[Seeder]

# Refresh database (⚠️ drops all tables)
docker compose exec app php artisan migrate:fresh --seed

# Access container shell
docker compose exec app bash

# Clear all caches (when troubleshooting)
docker compose exec app php artisan optimize:clear

# Run deployment scripts (from host)
bash scripts/deploy-dev.sh      # Development deployment
bash scripts/deploy-prod.sh     # Production deployment
bash scripts/optimize-performance.sh  # Performance optimization
```

**Artisan Custom Commands** (when needed):
```bash
# Generate feature components (custom commands - to be implemented)
php artisan make:feature [FeatureName]  # Create complete feature structure
php artisan make:resolver [Feature]/[ResolverName]  # Create GraphQL resolver
php artisan make:dataloader [Feature]/[LoaderName]  # Create DataLoader
```

**Code Quality**:
```bash
# Lint code (Laravel Pint)
docker compose exec app ./vendor/bin/pint

# Validate GraphQL schema (Docker)
docker compose exec app php artisan lighthouse:validate-schema

# Validate GraphQL schema (Local PHP - recommended for better performance)
# Use this when Docker validation is slow or timing out
powershell -Command "php artisan lighthouse:validate-schema"

# Type checking (when available)
docker compose exec app npm run type-check

# Cache GraphQL schema for performance
docker compose exec app php artisan lighthouse:cache
```

### Architecture: Feature-First Organization (PURE)

**CRITICAL**: This project uses **Feature-First PURE** organization. ALL code related to a feature lives inside its folder.

**ONLY EXCEPTION**: `tests/` stays in root (Laravel convention), but organized by features inside.

```
app/
├── Shared/                         # Code shared between features
│   ├── Services/                   # Shared business logic
│   ├── GraphQL/
│   │   ├── Scalars/               # UUID, Email, PhoneNumber, HexColor
│   │   ├── Directives/            # @company, @audit, @rateLimit
│   │   ├── Queries/               # ping, version, health
│   │   └── Mutations/             # BaseMutation (inheritance)
│   ├── Traits/                    # HasUuid, Auditable
│   ├── Enums/                     # UserStatus, TicketStatus
│   ├── Exceptions/                # Custom exceptions
│   └── Helpers/                   # Utility functions
│
├── Features/                       # Independent business features
│   ├── Authentication/            # ✅ Login, registration, JWT, OAuth
│   │   ├── Services/              # AuthenticationService
│   │   ├── Models/                # User, RefreshToken
│   │   ├── GraphQL/
│   │   │   ├── Schema/            # authentication.graphql
│   │   │   ├── Queries/           # AuthStatusQuery, MySessionsQuery
│   │   │   ├── Mutations/         # LoginMutation, RegisterMutation
│   │   │   ├── Types/             # Feature-specific types
│   │   │   └── DataLoaders/       # ⏳ (pending)
│   │   ├── Events/                # ⏳ UserLoggedIn, UserRegistered
│   │   ├── Listeners/             # ⏳ SendLoginNotification
│   │   ├── Jobs/                  # ⏳ SendEmailVerificationJob
│   │   ├── Policies/              # ⏳ UserPolicy
│   │   └── Database/              # ⏳ ALL database related
│   │       ├── Migrations/        # Create users table
│   │       ├── Seeders/           # UsersSeeder
│   │       └── Factories/         # UserFactory
│   │
│   ├── UserManagement/            # ✅ User CRUD, profiles, roles
│   │   └── (same structure)
│   │
│   └── CompanyManagement/         # ✅ Company CRUD, requests
│       └── (same structure)
│
tests/                             # ⚠️ ONLY EXCEPTION
├── Feature/                       # Integration tests
│   ├── Authentication/
│   ├── UserManagement/
│   └── CompanyManagement/
└── Unit/                          # Unit tests
    └── Services/
        ├── Authentication/
        ├── UserManagement/
        └── CompanyManagement/

resources/js/
├── Pages/                         # Inertia.js pages
│   ├── Home.tsx                   # ✅ Working
│   └── [Features]/                # ⏳ Pending
├── Features/                      # Frontend logic by feature
│   ├── Authentication/
│   ├── UserManagement/
│   └── CompanyManagement/
└── Shared/                        # Shared components
```

**Current Implementation Status**:
- ✅ GraphQL schemas and dummy resolvers (schema-first)
- 🔄 Models, Services, Events, Listeners, Jobs, Policies (in progress)
- 🔄 Database: Migrations, Seeders, Factories (in progress - see git status)

### Database Schema (PostgreSQL V7.0)

**Four PostgreSQL schemas**:
- `auth` - Users, roles, authentication
- `business` - Companies, requests
- `ticketing` - Tickets, responses, ratings
- `audit` - Audit logs

**Key tables**:
- `auth.users` (id, user_code, email, password_hash, status)
- `business.companies` (id, company_code, name, admin_user_id, status)
- `ticketing.tickets` (id, ticket_code, author_id, company_id, status)

See `/documentacion/Modelado final de base de datos.txt` for complete schema.

### Dual Frontend Approach

**Web Frontend (Inertia.js)**:
- Purpose: Main helpdesk web application
- Routes: Laravel routes (`routes/web.php`)
- Components: `resources/js/Pages/`
- Navigation: Inertia `<Link>` components (NO React Router)
- Current status: ✅ Working with Home.tsx

**Mobile API (GraphQL)**:
- Purpose: Future React Native mobile app
- Endpoint: Single `/graphql` endpoint (http://localhost:8000/graphql)
- GraphiQL: http://localhost:8000/graphiql
- Client: Apollo Client
- Status: ✅ Lighthouse GraphQL installed and configured

### Feature-First PURE: Key Differences from Laravel Traditional

**🔴 Laravel Traditional (by layers):**
```
app/Models/              ← ALL models together
app/Services/            ← ALL services together
database/migrations/     ← ALL migrations together
database/seeders/        ← ALL seeders together
database/factories/      ← ALL factories together
```

**🟢 This Project (Feature-First PURE):**
```
app/Features/Authentication/
  ├── Models/            ← Models for THIS feature only
  ├── Services/          ← Services for THIS feature only
  └── Database/
      ├── Migrations/    ← Migrations for THIS feature only
      ├── Seeders/       ← Seeders for THIS feature only
      └── Factories/     ← Factories for THIS feature only
```

**Why?** When working on login, ALL files (Models, Services, Migrations, GraphQL) are in `Features/Authentication/`. No jumping between folders.

**IMPORTANT**: Migrations/Seeders/Factories are **inside each feature**, not in root `database/` folder.

**Loading Feature Migrations**:
Feature migrations must be loaded in `AppServiceProvider::boot()`:
```php
// In app/Providers/AppServiceProvider.php
$this->loadMigrationsFrom([
    database_path('migrations'),
    app_path('Features/Authentication/Database/Migrations'),
    app_path('Features/UserManagement/Database/Migrations'),
    app_path('Features/CompanyManagement/Database/Migrations'),
    // Add new features here
]);
```

**⚠️ IMPORTANT**: After adding migration paths, you must restart the application/queue containers:
```bash
docker compose restart app queue scheduler
```

### Development Rules

**Backend (Laravel)**:
- ✅ Feature-first organization (REQUIRED)
- ✅ Service layer for all business logic
- ✅ Type hints on all functions
- ✅ Dependency injection
- ✅ Use Eloquent (no raw SQL)
- ❌ NEVER put business logic in Resolvers/Controllers
- ❌ NEVER put Migrations in root `database/` folder (use `app/Features/[Feature]/Database/Migrations/`)
- ❌ NEVER put Models in root `app/Models/` folder (use `app/Features/[Feature]/Models/`)

**Frontend Web (Inertia.js)**:
- ✅ TypeScript for all React components
- ✅ Use Inertia forms (not Axios/fetch)
- ✅ Laravel routes only (no React Router)
- ✅ Custom hooks for reusable logic
- ❌ NEVER complex logic in components

**GraphQL API (Future)**:
- ✅ Single `/graphql` endpoint only
- ✅ DataLoaders to prevent N+1 queries
- ✅ All logic delegated to Services
- ❌ NEVER multiple REST endpoints

### Documentation References

Feature specifications and GraphQL schemas are in `/documentacion/`:
- `GUIA_ESTRUCTURA_CARPETAS_PROYECTO.md` - **COMPLETE guide to Feature-First architecture** (read this first!)
- `AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt`
- `USER MANAGEMENT FEATURE - DOCUMENTACIÓN.txt`
- `COMPANY MANAGEMENT FEATURE - DOCUMENTACIÓN.txt`
- `*SCHEMA.txt` files contain GraphQL type definitions
- `Modelado final de base de datos.txt` - Complete database schema

### Current State

- ✅ Laravel 12 initialized
- ✅ Docker environment configured (Docker Compose with app, postgres, redis, nginx, mailpit)
- ✅ Inertia.js configured and working (Home.tsx renders)
- ✅ **Lighthouse GraphQL - Schema-First COMPLETADO (01-Oct-2025)**
  - ✅ `graphql/shared/` con scalars, directives, interfaces, enums, base-types, pagination
  - ✅ 3 feature schemas: Authentication, UserManagement, CompanyManagement
  - ✅ 43 resolvers dummy creados (retornan null/arrays vacíos)
  - ✅ Scalars personalizados: UUID, PhoneNumber, HexColor
  - ✅ Directivas: @auth, @can, @company, @rateLimit, @audit
  - ✅ **Anti-loop types:** UserBasicInfo, CompanyBasicInfo, TicketBasicInfo
  - ✅ **Schema validado exitosamente** (usando PHP local por rendimiento)
- 🔄 **Backend Implementation - IN PROGRESS (03-Oct-2025)**
  - ✅ Models: User, UserProfile, UserRole, Role, RefreshToken
  - ✅ Services: AuthService, TokenService, PasswordResetService, UserService, RoleService, ProfileService
  - ✅ Events/Listeners: Authentication events system
  - ✅ Jobs: Email verification and password reset jobs
  - ✅ Policies: UserPolicy, UserRolePolicy
  - ✅ Migrations: Authentication and UserManagement tables created
  - ✅ Factories: User, UserProfile, UserRole, Role, RefreshToken
  - ✅ Seeders: RolesSeeder, DemoUsersSeeder
  - ✅ DataLoaders: UserByIdLoader, UserProfileByUserIdLoader, UserRolesByUserIdLoader
  - 🔄 CompanyManagement: Mutations and queries (in progress)
- ⏳ PostgreSQL schemas - migrations need to be run
- ⏳ Real resolvers implementation - currently dummy

### Development Workflow

1. Read feature documentation in `/documentacion/`
2. Read corresponding GraphQL schema files
3. Create Models with migrations (PostgreSQL schemas)
4. Create Service with business logic
5. Implement GraphQL Resolvers that delegate to Services
6. Create Inertia routes in `routes/web.php`
7. Implement Pages in `resources/js/Pages/[Feature]/`
8. Create custom hooks for reusable logic
9. Write unit and integration tests

When implementing features, follow the existing patterns in the codebase and maintain the feature-first organization structure.

---

## GraphQL Schema-First Implementation (CURRENT STATUS)

**Last updated:** 03-Oct-2025 (Active Development)

### ✅ What's Completed

1. **Shared GraphQL Foundation** (`graphql/shared/`):
   - ✅ `scalars.graphql` - UUID, Email, PhoneNumber, URL, DateTime, JSON, HexColor
   - ✅ `directives.graphql` - @auth, @can, @company, @rateLimit, @cache, @audit
   - ✅ `interfaces.graphql` - Node, Timestamped, BelongsToCompany
   - ✅ `enums.graphql` - Role, UserStatus, CompanyStatus, TicketStatus, SortOrder
   - ✅ `base-types.graphql` - UserBasicInfo, CompanyBasicInfo, TicketBasicInfo (prevents infinite loops)
   - ✅ `pagination.graphql` - PaginatorInfo

2. **Feature Schemas**:
   - ✅ `app/Features/Authentication/GraphQL/Schema/authentication.graphql` (14 mutations, 4 queries)
   - ✅ `app/Features/UserManagement/GraphQL/Schema/user-management.graphql` (11 mutations, 6 queries)
   - ✅ `app/Features/CompanyManagement/GraphQL/Schema/company-management.graphql` (7 mutations, 5 queries)

3. **Backend PHP Implementation**:
   - ✅ **Scalars**: `app/Shared/GraphQL/Scalars/` (UUIDScalar, PhoneNumberScalar, HexColorScalar)
   - ✅ **Directives**: `app/Shared/GraphQL/Directives/` (CompanyDirective, AuditDirective, RateLimitDirective)
   - ✅ **Base Classes**: `app/Shared/GraphQL/{Queries,Mutations}/` (BaseQuery, BaseMutation)
   - ✅ **Dummy Resolvers**: 43 files created (all return null/empty arrays)
     - Authentication: 14 resolvers (4 queries + 10 mutations)
     - UserManagement: 17 resolvers (6 queries + 11 mutations)
     - CompanyManagement: 12 resolvers (5 queries + 7 mutations)

4. **Configuration**:
   - ✅ `config/lighthouse.php` - Namespaces updated for Shared directory
   - ✅ `graphql/schema.graphql` - Main schema with all imports

### ✅ Schema Validation

**Schema has been validated successfully!**

```bash
# Validate schema (preferred: local PHP for better performance)
powershell -Command "php artisan lighthouse:validate-schema"

# Alternative: Docker (slower, may timeout on complex schemas)
docker compose exec app php artisan lighthouse:validate-schema

# If errors occur:
# 1. DO NOT simplify the schema
# 2. DO resolve the specific error
# 3. Check logs: docker compose logs app
```

**Performance Note:** Use local PHP (Laravel Herd) for validation commands when Docker performance is insufficient. This applies to CPU-intensive artisan commands that may timeout in Docker containers.

**Common validation errors and solutions:**
- Missing Core queries → Implement ping, version, health resolvers
- Directive not found → Check registration in config/lighthouse.php
- Scalar conflicts → Use Lighthouse built-in vs custom (Email, URL, DateTime)
- Import path errors → Fix paths in schema.graphql

### 🎯 After Validation: Test in GraphiQL/Apollo Sandbox

```bash
# Ensure services are running
docker compose up -d

# Access GraphQL endpoints:
# - GraphQL API: http://localhost:8000/graphql
# - GraphiQL IDE: http://localhost:8000/graphiql
# - App: http://localhost:8000

# Test basic query:
query {
  ping
  version {
    version
    laravel
  }
}
```

### 📚 Key Files Reference

- **Status Doc**: `IMPLEMENTATION_STATUS.md` - Detailed implementation status
- **Main Schema**: `graphql/schema.graphql` - Entry point with all imports
- **Shared Types**: `graphql/shared/*.graphql` - 6 files with common definitions
- **Feature Schemas**: `app/Features/*/GraphQL/Schema/*.graphql` - 3 complete schemas
- **Resolvers**: `app/Features/*/GraphQL/{Queries,Mutations}/*.php` - 43 dummy files

### 🚨 IMPORTANT: Schema-First Principles

- ❌ **NEVER** simplify the schema to avoid errors
- ✅ **ALWAYS** resolve errors properly
- ✅ Keep all 3 feature schemas complete (Authentication, UserManagement, CompanyManagement)
- ✅ Maintain anti-loop types (UserBasicInfo, CompanyBasicInfo, TicketBasicInfo)
- ✅ All resolvers return null/empty for now (dummy implementation)

---

## GraphQL DataLoaders (N+1 Query Prevention)

**Purpose:** Prevent N+1 query problems when fetching related data in GraphQL.

**Location:** `app/Shared/GraphQL/DataLoaders/` (shared) or `app/Features/[Feature]/GraphQL/DataLoaders/` (feature-specific)

**Example Pattern:**
```php
// app/Shared/GraphQL/DataLoaders/UserByIdLoader.php
namespace App\Shared\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\User;
use Closure;

class UserByIdLoader
{
    public function __invoke(array $keys): array
    {
        // Batch load all users at once
        $users = User::whereIn('id', $keys)->get()->keyBy('id');

        // Return in same order as keys
        return array_map(fn($id) => $users->get($id), $keys);
    }
}
```

**Usage in Resolvers:**
```php
// In any Query/Mutation
use Nuwave\Lighthouse\Execution\Utils\Subscription;

public function __invoke($rootValue, array $args)
{
    // GraphQL will automatically batch these calls
    return app(\App\Shared\GraphQL\DataLoaders\UserByIdLoader::class)
        ->load($args['userId']);
}
```

**Common DataLoaders Needed:**
- `UserByIdLoader` - Load users by ID
- `UserProfileByUserIdLoader` - Load profiles by user ID
- `UserRolesByUserIdLoader` - Load roles by user ID
- `CompanyByIdLoader` - Load companies by ID
- `TicketsByCompanyIdLoader` - Load tickets by company ID

**When to Use:**
- ✅ When fetching related models in GraphQL fields
- ✅ When a field might be called multiple times in a single query
- ✅ When implementing `author`, `company`, `creator` fields on types
- ❌ NOT needed for simple direct queries (single record fetch)
