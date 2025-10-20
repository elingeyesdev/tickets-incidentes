# JWT PURE AUTHENTICATION REFACTORING PLAN
**Date:** 2025-10-20
**Branch:** feature/company-management → refactor/pure-jwt-authentication
**Status:** 🚀 EXECUTION IN PROGRESS

---

## 🎯 MISSION OBJECTIVE

**ERADICATE** all session-based authentication and establish a **100% PURE JWT STATELESS** architecture.

### Business Drivers:
1. ✅ Fix critical frontend authentication bugs
2. ✅ Enable future mobile app development
3. ✅ Eliminate hybrid authentication complexity

---

## 📋 REFACTORING STRATEGY

### Phase 1: ✅ COMPLETE - Intelligence Audit
- Identified 259+ legacy auth occurrences across 66 files
- Confirmed JWT infrastructure is production-ready
- Mapped all Auth::user(), auth()->user(), @guard usage

### Phase 2: 🔄 IN PROGRESS - Plan Generation
- This document

### Phase 3: 🚀 EXECUTION - Systematic Code Surgery

#### 3.1: Create JWT Pure Infrastructure (FOUNDATION)

**NEW FILES TO CREATE:**

1. **`app/Shared/Helpers/JWTHelper.php`**
   - Primary method: `JWTHelper::getAuthenticatedUser()`
   - Retrieves user from `$request->attributes->get('jwt_user')`
   - Throws `AuthenticationException` if no user found

2. **`app/Http/Middleware/JWT/JWTAuthenticationMiddleware.php`**
   - Validates JWT token from `Authorization` header
   - Loads user from database
   - Stores user in `$request->attributes->set('jwt_user', $user)`
   - Throws 401 if invalid/missing token

3. **`app/Http/Middleware/JWT/JWTRoleMiddleware.php`**
   - Checks if authenticated user has required role(s)
   - Usage: `->middleware('jwt.role:PLATFORM_ADMIN,COMPANY_ADMIN')`
   - Throws 403 if unauthorized

4. **`app/Http/Middleware/JWT/JWTOnboardingMiddleware.php`**
   - Checks if user has completed onboarding
   - Redirects to onboarding if incomplete
   - Uses `$user->onboarding_completed_at`

5. **`app/Http/Middleware/JWT/JWTGuestMiddleware.php`**
   - Ensures user is NOT authenticated
   - Redirects authenticated users to dashboard
   - For login/register routes

6. **`routes/web-jwt-pure.php`**
   - New route file with JWT middleware
   - Replace session-based `'auth'` middleware with `'jwt.auth'`

---

#### 3.2: Refactor GraphQL Resolvers (21 FILES)

**Pattern to Apply Everywhere:**

```php
// ❌ BEFORE (Legacy Session)
public function __invoke($rootValue, array $args)
{
    $user = Auth::user(); // REMOVE
    return $this->service->doSomething($user, $args);
}

// ✅ AFTER (Pure JWT)
use App\Shared\Helpers\JWTHelper;

public function __invoke($rootValue, array $args)
{
    $user = JWTHelper::getAuthenticatedUser();
    return $this->service->doSomething($user, $args);
}
```

**Files to Modify (UserManagement - 12 files):**
- `app/Features/UserManagement/GraphQL/Mutations/UpdateMyProfileMutation.php`
- `app/Features/UserManagement/GraphQL/Mutations/DeleteUserMutation.php`
- `app/Features/UserManagement/GraphQL/Mutations/RemoveRoleMutation.php`
- `app/Features/UserManagement/GraphQL/Mutations/ActivateUserMutation.php`
- `app/Features/UserManagement/GraphQL/Mutations/SuspendUserMutation.php`
- `app/Features/UserManagement/GraphQL/Mutations/AssignRoleMutation.php`
- `app/Features/UserManagement/GraphQL/Mutations/UpdateMyPreferencesMutation.php`
- `app/Features/UserManagement/GraphQL/Queries/MyProfileQuery.php`
- `app/Features/UserManagement/GraphQL/Queries/UserQuery.php`
- `app/Features/UserManagement/GraphQL/Queries/AvailableRolesQuery.php`
- `app/Features/UserManagement/GraphQL/Queries/UsersQuery.php`
- `app/Features/UserManagement/GraphQL/Queries/MeQuery.php`

**Files to Modify (CompanyManagement - 9 files):**
- `app/Features/CompanyManagement/GraphQL/Mutations/RejectCompanyRequestMutation.php`
- `app/Features/CompanyManagement/GraphQL/Mutations/ApproveCompanyRequestMutation.php`
- `app/Features/CompanyManagement/GraphQL/Mutations/FollowCompanyMutation.php`
- `app/Features/CompanyManagement/GraphQL/Mutations/CreateCompanyMutation.php`
- `app/Features/CompanyManagement/GraphQL/Mutations/UnfollowCompanyMutation.php`
- `app/Features/CompanyManagement/GraphQL/Queries/MyFollowedCompaniesQuery.php`
- `app/Features/CompanyManagement/GraphQL/Queries/CompanyQuery.php`
- `app/Features/CompanyManagement/GraphQL/Queries/CompaniesQuery.php`
- `app/Features/CompanyManagement/GraphQL/Queries/IsFollowingCompanyQuery.php`

**Special Cases:**
- `auth()->check()` → `JWTHelper::isAuthenticated()` (new method)
- `Auth::id()` → `JWTHelper::getAuthenticatedUser()->id`

---

#### 3.3: Update GraphQL Schemas (2 FILES)

**Find & Replace: `@guard` → `@jwt`**

**File 1:** `app/Features/UserManagement/GraphQL/Schema/user-management.graphql`
- Lines: 12, 20, 38, 47, 58, 71, 82, 96, 106, 116, 130, 141
- Total: 12 replacements

**File 2:** `app/Features/CompanyManagement/GraphQL/Schema/company-management.graphql`
- Lines: 36, 44, 51, 61, 72, 77, 89, 94, 99, 105, 237
- Total: 11 replacements

**Example:**
```graphql
# BEFORE
type Query {
    users(companyId: UUID): [User!]!
        @guard(requires: [PLATFORM_ADMIN, COMPANY_ADMIN])
}

# AFTER
type Query {
    users(companyId: UUID): [User!]!
        @jwt(requires: [PLATFORM_ADMIN, COMPANY_ADMIN])
}
```

---

#### 3.4: Refactor Auditable Trait (CRITICAL)

**File:** `app/Shared/Traits/Auditable.php`

**New Strategy: Use JWTHelper**

```php
<?php

namespace App\Shared\Traits;

use App\Shared\Helpers\JWTHelper;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::creating(function (Model $model) {
            if ($model->hasAttribute('created_by_id')) {
                try {
                    $user = JWTHelper::getAuthenticatedUser();
                    $model->created_by_id = $user->id;
                } catch (\Exception $e) {
                    // Allow creation without auth (e.g., seeders)
                }
            }
        });

        static::updating(function (Model $model) {
            if ($model->hasAttribute('updated_by_id')) {
                try {
                    $user = JWTHelper::getAuthenticatedUser();
                    $model->updated_by_id = $user->id;
                } catch (\Exception $e) {
                    // Allow updates without auth
                }
            }
        });

        static::deleting(function (Model $model) {
            if ($model->hasAttribute('deleted_by_id')) {
                try {
                    $user = JWTHelper::getAuthenticatedUser();
                    $model->deleted_by_id = $user->id;
                    $model->save();
                } catch (\Exception $e) {
                    // Allow deletes without auth
                }
            }
        });
    }
}
```

---

#### 3.5: Update Test Suite (41 FILES)

**Create New Test Helper in `tests/TestCase.php`:**

```php
protected function authenticateWithJWT(User $user): self
{
    $token = app(\App\Features\Authentication\Services\TokenService::class)
        ->generateAccessToken($user, uniqid('test_session_'));

    $this->withHeaders([
        'Authorization' => "Bearer {$token}",
    ]);

    return $this;
}
```

**Pattern to Apply:**
```php
// BEFORE
$this->actingAs($admin)->graphQL('...');

// AFTER
$this->authenticateWithJWT($admin)->graphQL('...');
```

**Files to Update:** All 41 test files (see audit report for complete list)

---

#### 3.6: Update Other Middleware (2 FILES)

**File 1:** `app/Shared/Http/Middleware/RedirectIfAuthenticated.php`

```php
// BEFORE
if (Auth::guard($guard)->check()) {
    $user = Auth::guard($guard)->user();
}

// AFTER
use App\Shared\Helpers\JWTHelper;

try {
    $user = JWTHelper::getAuthenticatedUser();
    return redirect($this->getDashboardPath($user));
} catch (\Exception $e) {
    // User not authenticated, continue
}
```

**Files Already Compatible (No Changes):**
- ✅ `app/Shared/Http/Middleware/EnsureOnboardingCompleted.php` (uses `$request->user()`)
- ✅ `app/Shared/Http/Middleware/EnsureUserHasRole.php` (uses `$request->user()`)

---

#### 3.7: ANNIHILATION PHASE - Delete Legacy Code

**FILES TO DELETE:**
1. ❌ `app/Http/Middleware/GraphQLJWTMiddleware.php` (hybrid bridge)

**CONFIGURATION CLEANUP:**

**File 1:** `bootstrap/app.php`
```php
// REMOVE middleware alias (line 24):
'auth' => \App\Http\Middleware\GraphQLJWTMiddleware::class,

// ADD new JWT middleware aliases:
'jwt.auth' => \App\Http\Middleware\JWT\JWTAuthenticationMiddleware::class,
'jwt.role' => \App\Http\Middleware\JWT\JWTRoleMiddleware::class,
'jwt.onboarding' => \App\Http\Middleware\JWT\JWTOnboardingMiddleware::class,
'jwt.guest' => \App\Http\Middleware\JWT\JWTGuestMiddleware::class,
```

**File 2:** `config/lighthouse.php`
```php
// REMOVE (line 47):
Nuwave\Lighthouse\Http\Middleware\AttemptAuthentication::class,
```

**File 3:** `routes/web.php`
```php
// REPLACE all middleware(['auth']) with middleware(['jwt.auth'])
```

**File 4:** `config/auth.php`
```php
// REMOVE 'web' guard (session-based)
// Keep only minimal configuration for future use
```

---

## 📊 EXECUTION SEQUENCE (Critical Path)

```
┌─────────────────────────────────────────────────────────────┐
│ PHASE 3: EXECUTION (Parallel Agents)                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  [Agent_Architect_01]                                       │
│  ├── Create JWTHelper.php                                   │
│  ├── Create JWTAuthenticationMiddleware.php                 │
│  ├── Create JWTRoleMiddleware.php                           │
│  ├── Create JWTOnboardingMiddleware.php                     │
│  ├── Create JWTGuestMiddleware.php                          │
│  └── Create routes/web-jwt-pure.php                         │
│      (Estimated: 2 hours)                                   │
│                                                              │
│  ⬇️ THEN (sequential - depends on JWTHelper)                │
│                                                              │
│  [Agent_Refactor_Code_01] (Parallel with others)           │
│  ├── Refactor 21 GraphQL Resolvers                         │
│  ├── Refactor Auditable Trait                              │
│  └── Refactor RedirectIfAuthenticated                      │
│      (Estimated: 3 hours)                                   │
│                                                              │
│  [Agent_Schema_01] (Parallel)                               │
│  ├── Update user-management.graphql                         │
│  └── Update company-management.graphql                      │
│      (Estimated: 30 minutes)                                │
│                                                              │
│  [Agent_Refactor_Tests_01] (Parallel)                      │
│  ├── Create authenticateWithJWT() helper                    │
│  └── Update 41 test files                                  │
│      (Estimated: 2 hours)                                   │
│                                                              │
│  ⬇️ FINALLY (after all refactoring complete)                │
│                                                              │
│  [Agent_Annihilator_01]                                     │
│  ├── Delete GraphQLJWTMiddleware.php                        │
│  ├── Update bootstrap/app.php                               │
│  ├── Update config/lighthouse.php                           │
│  ├── Update routes/web.php                                  │
│  └── Clean config/auth.php                                  │
│      (Estimated: 30 minutes)                                │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

**Total Estimated Time:** 5-6 hours (with parallel execution)

---

## ✅ SUCCESS CRITERIA

### Phase 3 Complete When:
- [ ] JWTHelper created with `getAuthenticatedUser()` method
- [ ] 4 JWT pure middlewares created and registered
- [ ] routes/web-jwt-pure.php created
- [ ] All 21 GraphQL resolvers use `JWTHelper::getAuthenticatedUser()`
- [ ] All 2 GraphQL schemas use `@jwt` instead of `@guard`
- [ ] Auditable trait uses JWTHelper
- [ ] All 41 test files use `authenticateWithJWT()`
- [ ] Legacy GraphQLJWTMiddleware deleted
- [ ] Session configuration purged

### Phase 4 Complete When:
- [ ] Test suite runs: `php artisan test`
- [ ] Target: 100% of previously passing tests still pass
- [ ] GraphQL schema validates: `php artisan lighthouse:validate-schema`
- [ ] No `Auth::user()` or `auth()->user()` found in codebase (except test helpers)

---

## 🚨 ROLLBACK PLAN

If critical failures occur:
```bash
git checkout feature/company-management
git branch -D refactor/pure-jwt-authentication
```

Backup branch created at: `backup/pre-jwt-refactor-2025-10-20`

---

## 📝 NOTES

- **Inertia.js UI will break temporarily** (accepted collateral damage)
- **Mobile app development unblocked** after this refactoring
- **GraphQL API becomes truly stateless** (production-ready for scaling)

---

**PLAN STATUS:** ✅ APPROVED FOR IMMEDIATE EXECUTION
**Next Action:** Launch Agent_Architect_01 to create JWT infrastructure

**End of Plan**
