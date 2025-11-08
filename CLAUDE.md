# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Helpdesk System - Laravel 12 + React + Inertia.js

Enterprise-grade helpdesk system built with Laravel 12 backend, React 19 frontend via Inertia.js, and GraphQL API for future mobile apps.

### Tech Stack
- **Backend**: Laravel 12 + Lighthouse GraphQL 6
- **Frontend Web**: React 19 + Inertia.js (TypeScript available)
- **Database**: PostgreSQL 17 (4 schemas: auth, business, ticketing, audit)
- **Authentication**: JWT with Refresh Tokens
- **Build Tools**: Vite 7 + TailwindCSS 4 (via @tailwindcss/vite)
- **Development**: Docker + Docker Compose + Laravel Herd (Windows)

### Docker Services
- `app` - PHP-FPM application container (Laravel)
- `nginx` - Web server (port 8000)
- `postgres` - PostgreSQL 17 database (port 5432)
- `redis` - Redis cache/session store (port 6379)
- `queue` - Laravel queue worker (background jobs)
- `scheduler` - Laravel task scheduler (cron)
- `mailpit` - Email testing (SMTP:1025, UI:8025)
- `vite` - Vite development server (port 5173)

## Key Commands

### Development Environment

**IMPORTANT**: Commands can run either:
- **Local PHP** (via Herd/local installation) - Faster for CPU-intensive tasks
- **Docker** - Isolated environment, consistent across platforms

**Note**: The project supports both Windows (PowerShell) and Linux environments.

### Development Workflow

```bash
# Start all services (Docker)
docker compose up -d

# View logs (all services)
docker compose logs -f

# View logs (specific service)
docker compose logs -f app

# Stop all services
docker compose down

# Restart specific service (needed after config changes)
docker compose restart app queue scheduler
```

### Frontend Development

```bash
# Development with Vite HMR (Docker)
docker compose up vite

# OR run Vite locally (faster)
npm run dev

# Build for production
docker compose exec app npm run build
# OR locally
npm run build

# GraphQL Code Generation (generate TypeScript types from schema)
npm run codegen              # Generate types once
npm run codegen:watch        # Watch mode for development
```

### Laravel Artisan Commands

**Choose Docker OR Local PHP** based on performance needs:

```bash
# === Docker Commands (isolated, consistent) ===
docker compose exec app php artisan [command]

# === Local PHP Commands (faster, requires Herd) ===
php artisan [command]
powershell -Command "php artisan [command]"  # From other shells
```

**Common artisan commands**:

```bash
# Migrations
php artisan migrate                    # Run pending migrations
php artisan migrate:fresh --seed       # ⚠️ Reset DB with seeders
php artisan migrate:status             # Check migration status

# Seeders
php artisan db:seed                    # Run all seeders
php artisan db:seed --class=Features\\Authentication\\Database\\Seeders\\RolesSeeder

# Cache management
php artisan optimize:clear             # Clear all caches (troubleshooting)
php artisan config:clear               # Clear config cache
php artisan route:clear                # Clear route cache

# GraphQL
php artisan lighthouse:validate-schema  # Validate GraphQL schema (use local PHP!)
php artisan lighthouse:cache           # Cache schema for performance

# Access container shell
docker compose exec app bash
```

### Testing

**Backend Testing (PHPUnit)**:
```bash
# Run all tests (Docker)
docker compose exec app php artisan test

# Run specific feature tests
docker compose exec app php artisan test --filter=RegisterMutationTest

# Run via composer
docker compose exec app composer test

# Local testing (faster)
php artisan test
```

**Frontend Testing (Vitest)**:
```bash
# Run all frontend tests
npm run test

# Watch mode (re-run on changes)
npm run test:watch

# UI mode (interactive browser)
npm run test:ui

# Coverage report
npm run test:coverage
```

### Code Quality

```bash
# Lint code with Laravel Pint (Docker)
docker compose exec app ./vendor/bin/pint

# Lint locally (faster)
./vendor/bin/pint

# Validate GraphQL schema
# ⚠️ IMPORTANT: Use local PHP, Docker may timeout
powershell -Command "php artisan lighthouse:validate-schema"

# Type checking (when TypeScript is configured)
npm run type-check
```

### GraphQL Development

```bash
# Access GraphQL endpoints:
# - GraphQL API: http://localhost:8000/graphql
# - GraphiQL IDE: http://localhost:8000/graphiql
# - Main app:     http://localhost:8000
# - Mailpit UI:   http://localhost:8025

# Test GraphQL query in GraphiQL:
query {
  ping
  version {
    version
    laravel
  }
}

# Test mutation (Register):
mutation {
  register(input: {
    email: "test@example.com"
    password: "SecurePass123!"
    passwordConfirmation: "SecurePass123!"
    firstName: "Test"
    lastName: "User"
    acceptsTerms: true
    acceptsPrivacyPolicy: true
  }) {
    accessToken
    user {
      id
      email
      profile { firstName lastName }
    }
  }
}
```

## Architecture: Feature-First Organization (PURE)

**CRITICAL**: This project uses **Feature-First PURE** organization. ALL code for a feature lives in its folder.

**ONLY EXCEPTION**: `tests/` stays in root (Laravel convention), organized by features inside.

### Directory Structure

```
app/
├── Shared/                         # Code shared between features
│   ├── Services/                   # Shared business logic
│   ├── GraphQL/
│   │   ├── Scalars/               # UUID, Email, PhoneNumber, HexColor
│   │   ├── Directives/            # @company, @audit, @rateLimit
│   │   ├── Queries/               # ping, version, health
│   │   ├── Mutations/             # BaseMutation (abstract class)
│   │   └── DataLoaders/           # Shared DataLoaders (UserByIdLoader, etc.)
│   ├── Traits/                    # HasUuid, Auditable
│   ├── Enums/                     # UserStatus, TicketStatus
│   ├── Exceptions/                # Custom exceptions
│   └── Database/
│       └── Migrations/            # Shared infrastructure (schemas, extensions)
│
├── Features/                       # Independent business features
│   ├── Authentication/            # Login, registration, JWT, OAuth
│   │   ├── Services/              # AuthService, TokenService
│   │   ├── Models/                # User, RefreshToken
│   │   ├── GraphQL/
│   │   │   ├── Schema/            # authentication.graphql
│   │   │   ├── Queries/           # AuthStatusQuery, MySessionsQuery
│   │   │   ├── Mutations/         # LoginMutation, RegisterMutation
│   │   │   ├── Types/             # Feature-specific types
│   │   │   └── DataLoaders/       # Feature-specific loaders
│   │   ├── Events/                # UserLoggedIn, UserRegistered
│   │   ├── Listeners/             # SendLoginNotification
│   │   ├── Jobs/                  # SendEmailVerificationJob
│   │   ├── Policies/              # UserPolicy
│   │   └── Database/              # ALL database related
│   │       ├── Migrations/        # Feature migrations
│   │       ├── Seeders/           # Feature seeders
│   │       └── Factories/         # Feature factories
│   │
│   ├── UserManagement/            # User CRUD, profiles, roles
│   │   └── (same structure)
│   │
│   └── CompanyManagement/         # Company CRUD, requests
│       └── (same structure)
│
tests/                             # ⚠️ ONLY EXCEPTION to feature-first
├── Feature/                       # Integration tests
│   ├── Authentication/
│   ├── UserManagement/
│   └── CompanyManagement/
└── Unit/                          # Unit tests
    └── Services/

resources/js/
├── Pages/                         # Inertia.js pages
│   ├── Home.tsx                   # Working homepage
│   └── [Features]/                # Feature pages (pending)
├── Features/                      # Frontend logic by feature
│   ├── Authentication/
│   ├── UserManagement/
│   └── CompanyManagement/
├── Components/                    # Shared UI components
│   ├── ui/                        # Base UI components (Button, Input, etc.)
│   ├── Skeleton/                  # Loading skeleton system (see frontend rules)
│   ├── forms/                     # Form components
│   ├── feedback/                  # Alert, Toast, Loading
│   ├── navigation/                # Breadcrumb, Pagination, Tabs
│   └── data-display/              # Avatar, StatusBadge, DataTable
└── Shared/                        # Shared utilities and hooks

graphql/
├── schema.graphql                 # Main schema entry point
└── shared/                        # Shared GraphQL definitions
    ├── scalars.graphql            # UUID, Email, PhoneNumber, etc.
    ├── directives.graphql         # @auth, @can, @company, @rateLimit, etc.
    ├── interfaces.graphql         # Node, Timestamped, BelongsToCompany
    ├── enums.graphql              # Role, UserStatus, CompanyStatus, etc.
    ├── base-types.graphql         # Anti-loop types (UserBasicInfo, etc.)
    └── pagination.graphql         # PaginatorInfo
```

### Feature-First vs Traditional Laravel

**Traditional Laravel (by layers)**:
```
app/Models/              ← ALL models together
app/Services/            ← ALL services together
database/migrations/     ← ALL migrations together
```

**This Project (Feature-First)**:
```
app/Features/Authentication/
  ├── Models/            ← Models for THIS feature only
  ├── Services/          ← Services for THIS feature only
  └── Database/
      ├── Migrations/    ← Migrations for THIS feature only
      ├── Seeders/       ← Seeders for THIS feature only
      └── Factories/     ← Factories for THIS feature only
```

**Why?** All related code lives together. When working on authentication, everything is in `Features/Authentication/`.

## Database Schema (PostgreSQL V7.0)

**Four PostgreSQL schemas** with complete separation:

- `auth` - Users, roles, authentication (13 tables)
- `business` - Companies, requests (5 tables)
- `ticketing` - Tickets, responses, ratings (planned)
- `audit` - System audit logs (planned)

**Key tables**:
- `auth.users` (id, user_code, email, password_hash, status)
- `auth.user_profiles` (user_id, first_name, last_name, phone_number, avatar_url)
- `auth.roles` (id, role_code, name, description)
- `business.companies` (id, company_code, name, admin_user_id, status)
- `ticketing.tickets` (id, ticket_code, author_id, company_id, status)

**Professional features**:
- ✅ ENUM types for validation
- ✅ INET for IP addresses
- ✅ CITEXT for case-insensitive emails
- ✅ JSONB for flexible data (business hours)
- ✅ Partial indexes for performance
- ✅ CHECK constraints for business rules
- ✅ Triggers for automatic fields

See `/documentacion/Modelado final de base de datos.txt` for complete schema.

## Critical Development Rules

### Backend (Laravel)

**DO**:
- ✅ Use feature-first organization (REQUIRED)
- ✅ Put ALL business logic in Services
- ✅ Use type hints on all functions
- ✅ Use dependency injection
- ✅ Use Eloquent (no raw SQL)
- ✅ Delegate all logic from Resolvers/Controllers to Services

**DON'T**:
- ❌ NEVER put business logic in Resolvers/Controllers
- ❌ NEVER put Migrations in root `database/` folder (use `app/Features/[Feature]/Database/Migrations/`)
- ❌ NEVER put Models in root `app/Models/` folder (use `app/Features/[Feature]/Models/`)
- ❌ NEVER put Seeders in root `database/seeders/` folder (use feature folders)
- ❌ NEVER put Factories in root `database/factories/` folder (use feature folders)

### Frontend (Inertia.js)

**DO**:
- ✅ Use TypeScript for all React components
- ✅ Use Inertia forms (not Axios/fetch)
- ✅ Use Laravel routes only (no React Router)
- ✅ Create custom hooks for reusable logic
- ✅ Use Inertia `<Link>` for navigation

**DON'T**:
- ❌ NEVER use React Router (incompatible with Inertia)
- ❌ NEVER put complex logic in components
- ❌ NEVER use fetch/axios for form submissions (use Inertia forms)

### GraphQL API

**DO**:
- ✅ Use single `/graphql` endpoint only
- ✅ Use DataLoaders to prevent N+1 queries
- ✅ Delegate all logic to Services
- ✅ Follow schema-first design
- ✅ Use custom scalars (UUID, Email, PhoneNumber, HexColor)

**DON'T**:
- ❌ NEVER create multiple REST endpoints
- ❌ NEVER simplify the schema to avoid errors (resolve the error properly)
- ❌ NEVER skip DataLoaders for related data

## Feature Migrations: Critical Setup

**Feature migrations must be registered in `AppServiceProvider::boot()`:**

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    $this->loadMigrationsFrom([
        app_path('Shared/Database/Migrations'),
        app_path('Features/UserManagement/Database/Migrations'),
        app_path('Features/Authentication/Database/Migrations'),
        app_path('Features/CompanyManagement/Database/Migrations'),
        // Add new features here
    ]);
}
```

**⚠️ CRITICAL**: After adding migration paths, restart containers:
```bash
docker compose restart app queue scheduler
```

## GraphQL DataLoaders (N+1 Prevention)

**Purpose**: Prevent N+1 query problems when fetching related data.

**Location**:
- `app/Shared/GraphQL/DataLoaders/` (shared)
- `app/Features/[Feature]/GraphQL/DataLoaders/` (feature-specific)

**Example**:
```php
// app/Shared/GraphQL/DataLoaders/UserByIdLoader.php
namespace App\Shared\GraphQL\DataLoaders;

use App\Features\UserManagement\Models\User;

class UserByIdLoader
{
    public function __invoke(array $keys): array
    {
        $users = User::whereIn('id', $keys)->get()->keyBy('id');
        return array_map(fn($id) => $users->get($id), $keys);
    }
}
```

**Usage in Resolvers**:
```php
public function __invoke($rootValue, array $args)
{
    return app(\App\Shared\GraphQL\DataLoaders\UserByIdLoader::class)
        ->load($args['userId']);
}
```

**When to Use**:
- ✅ When fetching related models in GraphQL fields
- ✅ When a field might be called multiple times in a single query
- ✅ When implementing `author`, `company`, `creator` fields on types
- ❌ NOT needed for simple direct queries (single record fetch)

**Existing DataLoaders**:
- `UserByIdLoader` - Load users by ID
- `UserProfileByUserIdLoader` - Load profiles by user ID
- `UserRolesByUserIdLoader` - Load roles by user ID
- `CompanyByIdLoader` - Load companies by ID
- `CompaniesByUserIdLoader` - Load companies by user ID
- `UsersByCompanyIdLoader` - Load users by company ID

## Dual Frontend Approach

### Web Frontend (Inertia.js)
- **Purpose**: Main helpdesk web application
- **Routes**: Laravel routes (`routes/web.php`)
- **Components**: `resources/js/Pages/`
- **Navigation**: Inertia `<Link>` components (NO React Router)
- **Current status**: ✅ Working (Home.tsx)

### Mobile API (GraphQL)
- **Purpose**: Future React Native mobile app
- **Endpoint**: Single `/graphql` endpoint
- **URLs**:
  - API: http://localhost:8000/graphql
  - GraphiQL: http://localhost:8000/graphiql
- **Client**: Apollo Client (future)
- **Status**: ✅ Lighthouse GraphQL installed and configured

## Frontend UI Components

**Skeleton Loading System** (`resources/js/Components/Skeleton/`):
- **Purpose**: Professional loading states (never show blank screens)
- **Structure**: Organized by functionality (base, forms, cards, data-display)
- **Components**:
  - Base: Skeleton, Input, Button, Avatar, Badge
  - Forms: FormSkeleton, OnboardingForm
  - Cards: Card, CardGrid, ListItem
- **Usage**: Import from `@/Components/Skeleton/` and use during data loading
- **Documentation**: See `Components/Skeleton/README.md` and `EXAMPLES.tsx`

**Note**: See `.cursor/rules/arquitecture-frontend.mdc` for complete component organization and the skeleton system rule.

## Frontend Authentication Architecture

**XState v5 State Machine** (`resources/js/lib/auth/AuthMachine.ts`):
- Manages authentication state transitions (idle → authenticating → authenticated → error)
- Handles JWT token lifecycle (access token + refresh token)
- Automatic token refresh before expiration
- Session restoration on page reload via PersistenceService
- Multi-tab synchronization via BroadcastChannel API

**Key Services**:
- `TokenManager` - JWT storage, validation, and refresh logic
- `PersistenceService` - Persist and restore auth state from localStorage
- `HeartbeatService` - Keep-alive mechanism for long-running sessions

**Integration**:
- `useLogin` hook - React hook for login functionality with XState machine
- GraphQL mutations for auth operations (login, register, refresh, logout)
- Type-safe operations via GraphQL Code Generator

## GraphQL Code Generation

**Configuration** (`codegen.ts`):
- Generates TypeScript types from GraphQL schema
- Schema source: `http://localhost:8000/graphql`
- Output: `resources/js/types/` (client preset)
- Watches: `resources/js/**/*.{ts,tsx}` for GraphQL operations

**Generated Files**:
- `resources/js/types/graphql.ts` - All GraphQL types
- `resources/js/types/gql.ts` - Type-safe gql tag function
- `resources/js/types/fragment-masking.ts` - Fragment utilities
- `resources/js/types/index.ts` - Barrel exports

**Usage Pattern**:
```typescript
import { gql } from '@/types/gql';

const LOGIN_MUTATION = gql(`
  mutation Login($input: LoginInput!) {
    login(input: $input) {
      accessToken
      user { id email }
    }
  }
`);
```

**Benefits**:
- Full type safety between frontend and backend
- Autocomplete for GraphQL operations
- Compile-time error detection
- No manual type definitions needed

## Documentation References

Comprehensive documentation in `/documentacion/`:

**Architecture & Planning**:
- `GUIA_ESTRUCTURA_CARPETAS_PROYECTO.md` - Complete Feature-First architecture guide (read this first!)
- `ESTADO_COMPLETO_PROYECTO.md` - Current project state and roadmap

**Feature Documentation**:
- `AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt` - Auth feature specs
- `USER MANAGEMENT FEATURE - DOCUMENTACIÓN.txt` - User management specs
- `COMPANY MANAGEMENT FEATURE - DOCUMENTACIÓN.txt` - Company specs

**GraphQL Schemas**:
- `AUTHENTICATION FEATURE SCHEMA.txt` - Auth GraphQL types
- Feature schema files: `app/Features/*/GraphQL/Schema/*.graphql`

**Database & Implementation**:
- `Modelado final de base de datos.txt` - Complete database schema (V7.0)
- `OPINION_PROFESIONAL_MODELADO_V7.md` - Database design analysis (97% score)

**Implementation Guides**:
- `GUIA_IMPLEMENTACION_REGISTER_MUTATION.md` - Example mutation implementation
- `DATALOADERS_GUIA.md` - DataLoader implementation guide
- `SISTEMA_ERRORES_GRAPHQL_IMPLEMENTADO.md` - Error handling system
- `EMAIL_VERIFICATION_IMPLEMENTATION.md` - Email verification flow

**Audits & Quality**:
- `AUDITORIA_SERVICES_CORRECCION_FINAL.md` - Services audit results
- `AUDITORIA_SERVICES_DATALOADERS_V7.md` - DataLoaders audit

## Current Implementation Status

**Last Updated:** 25-Oct-2025
**Current Branch:** feature/auth-refactor

### ✅ Production-Ready

**Infrastructure**:
- ✅ Laravel 12 + Docker environment
- ✅ Inertia.js configured (Home.tsx working)
- ✅ PostgreSQL 17 with 4 schemas
- ✅ Redis cache/queue/sessions
- ✅ Database 100% aligned with Modelado V7.0
- ✅ Vitest testing framework configured

**GraphQL API**:
- ✅ Lighthouse GraphQL schema-first complete
- ✅ 3 feature schemas (Authentication, UserManagement, CompanyManagement)
- ✅ Scalars, Directives, Interfaces, Enums
- ✅ Schema validated successfully
- ✅ Error handling (DEV/PROD differentiation)
- ✅ 6 DataLoaders implemented

**Authentication Feature (100%)**:
- ✅ Register mutation implemented & tested (100% tests passing)
- ✅ Models: User, UserProfile, UserRole, Role, RefreshToken
- ✅ Services: AuthService, TokenService, PasswordResetService, HeartbeatService (audited)
- ✅ Events/Listeners: Email verification flow
- ✅ Jobs: Email jobs (verification, password reset)
- ✅ Migrations & Seeders complete
- ✅ Frontend: XState v5 state machine (AuthMachine) with PersistenceService
- ✅ Token management: JWT with automatic refresh, multi-tab synchronization
- ✅ Session restoration: Persistent auth state across page reloads

**UserManagement Feature (100%)**:
- ✅ Models, Services, Policies (100% audited)
- ✅ Factories and Seeders
- ✅ Events system
- ✅ Tests 100% passing

**CompanyManagement Feature (100%)**:
- ✅ Models: Company, CompanyRequest, CompanyFollower
- ✅ Services: CompanyService, CompanyRequestService
- ✅ Migrations & Factories
- ✅ Tests 100% passing

### ⏳ In Progress (feature/auth-refactor branch)
- ⏳ Stateless JWT authentication refactoring (eliminating Laravel sessions)
- ⏳ Frontend authentication UI with XState machine integration
- ⏳ Additional GraphQL resolvers
- ⏳ Company management workflows

### ❌ Future Features
- ❌ Ticketing feature
- ❌ Audit logs activation
- ❌ Real-time subscriptions (GraphQL)

## Development Workflow

1. **Read Documentation**: Start with feature docs in `/documentacion/`
2. **Review GraphQL Schema**: Check corresponding `.graphql` files
3. **Create Models**: With migrations in `app/Features/[Feature]/Database/Migrations/`
4. **Register Migrations**: Add path to `AppServiceProvider::boot()`, restart containers
5. **Create Service**: Business logic in `app/Features/[Feature]/Services/`
6. **Implement Resolvers**: GraphQL resolvers that delegate to Services
7. **Add Inertia Routes**: In `routes/web.php`
8. **Create Pages**: React components in `resources/js/Pages/[Feature]/`
9. **Write Tests**: Feature tests in `tests/Feature/[Feature]/`, Unit tests in `tests/Unit/`
10. **Validate**: Run tests, lint code, validate GraphQL schema

## Performance & Optimization

**Implemented Optimizations**:
- ✅ OPcache with optimized settings
- ✅ PHP-FPM static pool configuration
- ✅ Redis caching for sessions/queues
- ✅ DataLoaders for N+1 prevention
- ✅ Partial database indexes
- ✅ Query optimization with Eloquent

**Scripts**:
```bash
# Run optimization script
bash scripts/optimize-performance.sh

# Deployment scripts
bash scripts/deploy-dev.sh      # Development
bash scripts/deploy-prod.sh     # Production
```

**Performance Metrics** (documented):
- Cold start: ~500ms
- Warm queries: ~165ms
- Consistent performance

## Troubleshooting

### GraphQL Schema Validation Timeout
**Problem**: Schema validation times out in Docker
**Solution**: Use local PHP (faster for CPU-intensive tasks)
```bash
powershell -Command "php artisan lighthouse:validate-schema"
```

### Migrations Not Found
**Problem**: Feature migrations not running
**Solution**: Register in `AppServiceProvider::boot()` and restart containers
```bash
docker compose restart app queue scheduler
```

### Cache Issues
**Problem**: Changes not reflecting
**Solution**: Clear all caches
```bash
php artisan optimize:clear
```

### Queue Jobs Not Processing
**Problem**: Jobs stuck in queue
**Solution**: Check queue worker logs
```bash
docker compose logs -f queue
docker compose restart queue
```

## Important GraphQL Principles

### Schema-First Development
- ❌ **NEVER** simplify the schema to avoid errors
- ✅ **ALWAYS** resolve errors properly
- ✅ Keep all feature schemas complete
- ✅ Maintain anti-loop types (UserBasicInfo, CompanyBasicInfo, TicketBasicInfo)
- ✅ Dummy resolvers return null/empty until implemented

### Error Handling
**Production vs Development**:
- **DEV**: Shows stacktrace, detailed errors
- **PROD**: Hides sensitive data, user-friendly messages

**Error Handlers** (registered in `config/lighthouse.php`):
1. `CustomAuthenticationErrorHandler` (401)
2. `CustomAuthorizationErrorHandler` (403)
3. `CustomValidationErrorHandler` (422)
4. `ReportingErrorHandler` (catch-all)

See `documentacion/SISTEMA_ERRORES_GRAPHQL_IMPLEMENTADO.md` for details.

## Configuration Files

**Key files to understand**:
- `config/lighthouse.php` - GraphQL configuration, namespaces, error handlers
- `graphql/schema.graphql` - Main GraphQL schema entry point
- `docker-compose.yml` - Docker services configuration
- `app/Providers/AppServiceProvider.php` - Migration loading, service registration
- `.env` - Environment configuration

## Queue System

**Queues configured** (priority order):
- `emails` - High priority (email verification, password resets)
- `default` - Standard background jobs

**Queue worker** (runs in Docker):
```bash
php artisan queue:work redis --queue=emails,default --verbose --tries=3 --timeout=90
```

**Monitor queues**:
```bash
docker compose logs -f queue
```

## Additional Development Rules

For comprehensive coding standards and patterns, see:
- `.cursorrules` - Professional Cursor AI rules with detailed patterns
- `.cursor/rules/backend-architecture.mdc` - Backend-specific architecture rules
- `.cursor/rules/arquitecture-frontend.mdc` - Frontend-specific architecture rules

These files contain detailed code patterns, naming conventions, and best practices that complement this CLAUDE.md file.
- to memorize cuando hagas commits no coloques hecho por claude
- usar siempre docker