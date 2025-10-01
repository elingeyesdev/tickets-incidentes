
# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Helpdesk System - Laravel 12 + React + Inertia.js

This is a helpdesk system built with Laravel 12 backend, React 18 frontend via Inertia.js, and planned GraphQL API for future mobile apps.

### Tech Stack
- **Backend**: Laravel 12 + planned Lighthouse GraphQL
- **Frontend Web**: React 18 + TypeScript + Inertia.js
- **Database**: PostgreSQL 17 (4 schemas: auth, business, ticketing, audit)
- **Authentication**: JWT with Refresh Tokens
- **Development**: Currently using Laravel Herd, planned migration to Docker

### Key Commands

**Development**:exa
```bash
# Start development server with all services
composer dev

# Frontend development (Vite HMR)
npm run dev

# Build for production
npm run build
```

**Testing**:
```bash
# Run all tests
php artisan test

# Run specific feature tests
php artisan test --filter=[Feature]

# Run via composer
composer test
```

**Laravel**:
```bash
# Generate services using feature structure
php artisan make:service [Feature]/[Feature]Service

# Generate models with migrations
php artisan make:model Features/[Feature]/Models/[Model] -m

# Run migrations
php artisan migrate
```

**Code Quality**:
```bash
# Lint code (Laravel Pint)
./vendor/bin/pint

# Type checking (when available)
npm run type-check
```

### Architecture: Feature-First Organization

**CRITICAL**: This project uses feature-first organization. All code must be organized by business features, not technical layers.

```
app/
├── Core/                    # Shared code between features
│   ├── Services/BaseService.php
│   ├── GraphQL/Mutations/BaseMutation.php
│   ├── GraphQL/Queries/BaseQuery.php
│   └── GraphQL/DataLoaders/BaseDataLoader.php
│
├── Features/                # Independent business features
│   ├── Authentication/      # Login, registration, JWT, OAuth
│   │   ├── Services/
│   │   ├── GraphQL/
│   │   │   ├── Queries/
│   │   │   └── Mutations/
│   │   ├── Models/
│   │   ├── Events/
│   │   └── Tests/
│   ├── UserManagement/      # User CRUD, profiles, roles
│   └── CompanyManagement/   # Company CRUD, requests, settings
│
resources/js/
├── Pages/                   # Inertia.js pages
│   ├── Home.tsx            # Current working page
│   └── [Features]/
├── Features/                # Frontend logic by feature
│   ├── Authentication/
│   ├── UserManagement/
│   └── CompanyManagement/
└── Shared/                  # Shared components
```

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
- Endpoint: Single `/graphql` endpoint
- Client: Apollo Client
- Status: ⏳ Lighthouse GraphQL pending installation

### Development Rules

**Backend (Laravel)**:
- ✅ Feature-first organization (REQUIRED)
- ✅ Service layer for all business logic
- ✅ Type hints on all functions
- ✅ Dependency injection
- ✅ Use Eloquent (no raw SQL)
- ❌ NEVER put business logic in Resolvers/Controllers

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
- `AUTHENTICATION FEATURE - DOCUMENTACIÓN.txt`
- `USER MANAGEMENT FEATURE - DOCUMENTACIÓN.txt`
- `COMPANY MANAGEMENT FEATURE - DOCUMENTACIÓN.txt`
- `*SCHEMA.txt` files contain GraphQL type definitions
- `Modelado final de base de datos.txt` - Complete database schema

### Current State

- ✅ Laravel 12 initialized
- ✅ Inertia.js configured and working (Home.tsx renders)
- ✅ **Lighthouse GraphQL - Schema-First COMPLETADO (29-Sep-2025)**
  - ✅ `graphql/shared/` con scalars, directives, interfaces, enums, base-types, pagination
  - ✅ 3 feature schemas: Authentication, UserManagement, CompanyManagement
  - ✅ 43 resolvers dummy creados (retornan null/arrays vacíos)
  - ✅ Scalars personalizados: UUID, PhoneNumber, HexColor
  - ✅ Directivas: @auth, @can, @company, @rateLimit, @audit
  - ✅ **Anti-loop types:** UserBasicInfo, CompanyBasicInfo, TicketBasicInfo
  - ⏳ **PENDIENTE:** Validar schema con `php artisan lighthouse:validate-schema`
  - 📄 **Ver detalles:** `IMPLEMENTATION_STATUS.md`
- ⏳ PostgreSQL schemas - pending migration from Herd
- ⏳ Features - pending backend implementation (resolvers son dummy)

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

**Last updated:** 29-Sep-2025 23:15 (Bogotá Time)

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

### ⏳ Next Step: VALIDATE SCHEMA

**You MUST validate the schema in a NEW TERMINAL with Herd PHP:**

```bash
# Open new terminal
cd C:\Users\heisn\Herd\helpdesk

# Validate schema
php artisan lighthouse:validate-schema

# If errors occur:
# 1. DO NOT simplify the schema
# 2. DO resolve the specific error
# 3. Check IMPLEMENTATION_STATUS.md for debugging tips
```

**Common validation errors and solutions:**
- Missing Core queries → Implement ping, version, health resolvers
- Directive not found → Check registration in config/lighthouse.php
- Scalar conflicts → Use Lighthouse built-in vs custom (Email, URL, DateTime)
- Import path errors → Fix paths in schema.graphql

### 🎯 After Validation: Test in Apollo Sandbox

```bash
# Start server
php artisan serve
# Or use Herd: http://helpdesk.test/graphql

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
