# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Enterprise Helpdesk System** - A professional, multi-tenant support platform built with Laravel 12, REST API, PostgreSQL 17, and AdminLTE v3. Feature-first architecture with JWT authentication, role-based access control, and comprehensive ticket management.

**Tech Stack:**
- **Backend:** Laravel 12, REST API (migrated from GraphQL), PostgreSQL 17 (4 schemas), Redis
- **Frontend:** AdminLTE v3 (Blade + jQuery), Vite, Alpine.js (React/Inertia planned)
- **Infrastructure:** Docker Compose with PHP-FPM, Nginx, PostgreSQL, Redis, Mailpit
- **Auth:** Stateless JWT (15min access + 7 day refresh tokens)

## Critical Rules

### Docker-First Development
- **ALWAYS use Docker commands** - Never use PHP Herd or local PHP
- **Docker is mandatory** for all operations (artisan, composer, tests, migrations)

### Data Consistency
- **ALWAYS use `description` field** - Never `initial_description` (standardized across all features)
- This was a critical change to maintain consistency

### Route Caching
- **ALWAYS clear route cache after adding new routes** - Routes won't be visible without this:
  ```bash
  docker compose exec app php artisan route:clear
  ```

### Blade Components with jQuery
- **NEVER use `@push('scripts')` inside `@include` partials** - It will fail silently
- **ALWAYS check for jQuery availability** before using `$` in Blade partials
- **ALWAYS wrap scripts in IIFE** with jQuery detection pattern (see `.cursor/rules/blade-components-jquery.mdc`)

### jQuery Validation + Select2
- **CRITICAL:** Select2 fields don't auto-clear validation errors when changed
- **MUST manually trigger re-validation** after Select2 change:
  ```javascript
  $selectField.on('change', function() {
      // Your logic...
      $form.validate().element('#selectFieldId'); // Force re-validation
  });
  ```

### Form Fields
- **ALWAYS use `name` attribute** on form inputs - jQuery Validation uses `name`, not `id`
- Missing `name` = validation rules silently ignored

## Common Commands

### Docker Container Access
```bash
# Execute artisan commands
docker compose exec app php artisan [command]

# Composer commands
docker compose exec app composer [command]

# Access app container shell
docker compose exec app bash

# View logs
docker compose logs -f [service]
```

### Development Workflow
```bash
# Start environment
docker compose up -d

# Stop environment
docker compose down

# Rebuild containers (after Dockerfile changes)
docker compose up -d --build

# View running containers
docker compose ps
```

### Database Operations
```bash
# Run migrations
docker compose exec app php artisan migrate

# Rollback last migration
docker compose exec app php artisan migrate:rollback

# Seed database
docker compose exec app php artisan db:seed

# Fresh migration with seed
docker compose exec app php artisan migrate:fresh --seed

# Access PostgreSQL CLI
docker compose exec postgres psql -U helpdesk -d helpdesk
```

### Testing
```bash
# Run all tests
docker compose exec app php artisan test

# Run specific test file
docker compose exec app php artisan test --filter=TestClassName

# Run specific test suite
docker compose exec app php artisan test tests/Feature/Authentication

# Run with coverage
docker compose exec app php artisan test --coverage

# Run in parallel (faster)
docker compose exec app php artisan test --parallel
```

### Cache Management
```bash
# Clear all caches
docker compose exec app php artisan optimize:clear

# Clear specific caches
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan cache:clear

# Cache for performance (production-like)
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache

# Optimize autoloader
docker compose exec app composer dump-autoload -o
```

### Code Quality
```bash
# Format code with Laravel Pint
docker compose exec app ./vendor/bin/pint

# Format specific file
docker compose exec app ./vendor/bin/pint app/Features/Authentication/Services/AuthService.php

# Dry run (check without changing)
docker compose exec app ./vendor/bin/pint --test
```

### Performance Optimization
```bash
# Run optimization script
./scripts/optimize-performance.sh

# Manual optimization steps
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app composer dump-autoload -o
```

## Architecture Overview

### Feature-First Structure
All business logic organized by feature in `app/Features/[FeatureName]/`:

**Implemented Features:**
- `Authentication/` - JWT auth, login, register, password reset, email verification
- `UserManagement/` - User CRUD, profiles, roles (RBAC)
- `CompanyManagement/` - Multi-tenant companies, requests, followers
- `ContentManagement/` - Announcements, help center articles
- `TicketManagement/` - Ticket system (in progress)

**Feature Structure:**
```
app/Features/[FeatureName]/
├── Http/
│   ├── Controllers/     # REST endpoints (delegate to Services)
│   ├── Requests/        # Form validation (Store/Update requests)
│   ├── Resources/       # JSON response transformers
│   └── Middleware/      # Feature-specific middleware
├── Services/            # ALL business logic goes here
├── Models/              # Eloquent models (data + relationships only)
├── Policies/            # Authorization rules
├── Events/              # Event classes (data only)
├── Listeners/           # Event handlers
├── Jobs/                # Background jobs
├── Enums/               # Type-safe enumerations
└── Database/
    ├── Migrations/      # Schema changes
    ├── Seeders/         # Test data
    └── Factories/       # Model factories
```

### Separation of Concerns (CRITICAL)
- **Services** → ALL business logic (create, update, delete, calculations)
- **Controllers** → Validate input, delegate to services, return responses (no logic!)
- **Models** → Data, relationships, scopes, casts (no business logic!)
- **Resources** → Transform data for JSON responses
- **Policies** → Authorization rules only
- **Form Requests** → Validation rules and messages
- **Events** → Data containers (no logic!)

### Database: PostgreSQL Multi-Schema (97% Quality Score)
**4 Schemas:**
- `auth` - Users, roles, permissions, sessions, refresh tokens
- `business` - Companies, requests, industries, followers
- `ticketing` - Tickets, responses, categories, ratings (in progress)
- `audit` - Audit logs (planned)

**Professional Features:**
- UUIDs as primary keys (HasUuid trait)
- ENUM types for validation
- INET for IP addresses
- CITEXT for case-insensitive emails
- JSONB for flexible data (business hours)
- Partial indexes for performance
- CHECK constraints for business rules
- Soft deletes (SoftDeletes trait)

### REST API Architecture
**Centralized Routes:** All API routes in `routes/api.php` organized by feature

**Naming Conventions:**
- Routes: kebab-case (`/api/ticket-categories`)
- JSON keys: camelCase (`userId`, `createdAt`)
- DB tables: snake_case (`user_profiles`)
- DB columns: snake_case (`created_at`)

**Response Format:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Success message"
}
```

**Error Format:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": { ... }
}
```

### Frontend: AdminLTE v3 + Blade + jQuery
**Current Implementation:** Traditional Blade views with AdminLTE v3, jQuery, Select2, DataTables

**View Structure:**
```
resources/views/
├── layouts/
│   ├── app.blade.php          # Main app layout
│   ├── public.blade.php       # Public pages layout
│   └── partials/              # Shared partials
├── app/
│   └── [role]/                # Role-based views (admin, company, agent, user)
│       └── [feature]/         # Feature views
│           └── partials/      # Feature-specific partials
└── public/                    # Public pages (login, register)
```

**Future:** React 19 + Inertia.js (planned migration)

### AdminLTE v3 Patterns

**Buttons:**
- Primary action: `.btn .btn-primary` (solid background)
- Secondary action: `.btn .btn-outline-dark` (border only)
- Cancel/Discard: `.btn .btn-outline-dark` with icon

**Forms:**
- Always use `name` attribute (not just `id`) for validation
- Use `.form-group` wrapper for each field
- Required fields: `<span class="text-danger">*</span>` in label
- Helper text: `<small class="form-text text-muted">`
- Configure jQuery Validation with official AdminLTE pattern (see `.cursor/rules/adminlte-forms-validation.mdc`)

**Blade Partials with jQuery:**
- Wrap scripts in IIFE: `(function() { ... })()`
- Check jQuery availability before using `$`
- Add console logs for debugging
- Use named init functions
- Set timeout to detect if jQuery never loads

## Testing

**Test Database:** Separate `helpdesk_test` database (automatically created by Docker)

**Configuration:**
- Test environment: `.env.testing`
- PHPUnit config: `phpunit.xml`
- Uses Redis for cache/queue in tests
- Mailpit for email testing

**Test Structure:**
```
tests/
├── Feature/[FeatureName]/     # Feature tests (HTTP, integration)
└── Unit/[FeatureName]/         # Unit tests (pure logic)
```

**Best Practices:**
- Test API endpoints in Feature tests
- Test Services in Unit tests
- Use factories for test data
- Clean up after tests (transactions, DatabaseMigrations trait)

## Services & Ports

**Access Points:**
- **Application:** http://localhost:8000
- **Mailpit UI:** http://localhost:8025
- **PostgreSQL:** localhost:5432
- **Redis:** localhost:6379

**Docker Services:**
- `app` - PHP-FPM application
- `nginx` - Web server
- `postgres` - PostgreSQL 17 database
- `redis` - Cache & session store
- `queue` - Laravel queue worker (emails, default queues)
- `scheduler` - Laravel task scheduler (cron)
- `mailpit` - Email testing tool

## Important Documentation

**In `/documentacion/`:**
- `ESTADO_COMPLETO_PROYECTO.md` - Complete project status
- `GUIA_ESTRUCTURA_CARPETAS_PROYECTO.md` - Feature-first architecture guide
- `OPINION_PROFESIONAL_MODELADO_V7.md` - Database design analysis (97% score)
- Various endpoint documentation files

**In `.cursor/rules/`:**
- `backend-architecture.mdc` - Complete backend patterns
- `frontend-architecture.mdc` - Frontend structure (React/Inertia planned)
- `blade-components-jquery.mdc` - **CRITICAL** jQuery loading patterns
- `adminlte-forms-validation.mdc` - **CRITICAL** Form validation patterns
- `adminlte-buttons.mdc` - Button styling patterns

## Type Safety

**PHP 8.3:**
- `declare(strict_types=1);` in ALL files
- Type hint ALL parameters and returns
- Use `final` classes by default
- Use readonly properties where appropriate
- Use backed enums for type safety

## Authentication & Authorization

**JWT Authentication:**
- Stateless tokens (no database lookups per request)
- Access token: 15 minutes
- Refresh token: 7 days
- Middleware: `JWTAuthenticationMiddleware` (optional), `JWTRequiredMiddleware` (enforce)

**Roles:**
- PLATFORM_ADMIN - Full system access
- COMPANY_ADMIN - Company management
- AGENT - Ticket handling
- USER - Regular user

**Authorization:**
- Use Policies for all authorization checks
- Controllers should call `$this->authorize('action', $model)`
- Policies in `app/Features/[Feature]/Policies/`

## Performance Features

**Implemented:**
- OPcache with optimized settings
- Redis caching for config, routes, views
- Stateless JWT (no DB queries per request)
- Eager loading to prevent N+1 queries
- Optimized Docker setup with health checks

**Expected Performance:**
- Cold start: ~200-500ms
- Warm requests: <165ms

## Common Patterns

### Creating a New Feature
1. Create feature directory: `app/Features/[FeatureName]/`
2. Create Controller in `Http/Controllers/`
3. Create Service in `Services/` (business logic)
4. Create Model in `Models/`
5. Create Policy in `Policies/`
6. Create Form Requests in `Http/Requests/`
7. Create Resources in `Http/Resources/`
8. Create migration in `Database/Migrations/`
9. Add routes in `routes/api.php`
10. Clear route cache: `docker compose exec app php artisan route:clear`
11. Write tests in `tests/Feature/[FeatureName]/`

### Creating a REST Endpoint
1. Create Form Request for validation
2. Create Resource for response transformation
3. Create Controller method (validate, delegate to service, return resource)
4. Service method handles business logic
5. Policy method handles authorization
6. Add route in `routes/api.php`
7. Clear route cache
8. Write feature test

### Creating a Blade View with Form
1. Create view file in `resources/views/app/[role]/[feature]/`
2. Use AdminLTE card components
3. Add form with `name` attributes on all inputs
4. Add helper text with `<small class="form-text text-muted">`
5. If using partials with jQuery, follow `.cursor/rules/blade-components-jquery.mdc` pattern
6. Configure jQuery Validation with official AdminLTE pattern
7. For Select2 fields, add manual re-validation on change
8. Use `.btn .btn-outline-dark` for Cancel/Discard buttons
9. Clear view cache: `docker compose exec app php artisan view:clear`

## Git Workflow

**Current branch:** feature/ticket-management
**Main branch:** master

**Before committing:**
1. Run tests: `docker compose exec app php artisan test`
2. Format code: `docker compose exec app ./vendor/bin/pint`
3. Clear caches if needed

## Quick Troubleshooting

**Routes not working:**
```bash
docker compose exec app php artisan route:clear
```

**Views not updating:**
```bash
docker compose exec app php artisan view:clear
```

**Config not updating:**
```bash
docker compose exec app php artisan config:clear
```

**jQuery not working in Blade partial:**
- Check if you're using `@push('scripts')` in `@include` (won't work!)
- Add jQuery availability check (see `.cursor/rules/blade-components-jquery.mdc`)

**Select2 validation errors not clearing:**
- Add `$form.validate().element('#fieldId')` in Select2 change handler
- See `.cursor/rules/adminlte-forms-validation.mdc`

**Form validation not working:**
- Check if inputs have `name` attribute (not just `id`)
- Verify jQuery Validation Plugin is loaded
- See `.cursor/rules/adminlte-forms-validation.mdc`

**Tests failing:**
- Ensure `.env.testing` is properly configured
- Check if using correct database connection
- Run `docker compose exec app php artisan config:clear --env=testing`
