<?php
use Illuminate\Support\Facades\Route;
use App\Features\Authentication\Http\Controllers\AuthController;
use App\Features\Authentication\Http\Controllers\RefreshTokenController;
use App\Features\Authentication\Http\Controllers\HealthController;
use App\Features\Authentication\Http\Controllers\PasswordResetController;
use App\Features\Authentication\Http\Controllers\EmailVerificationController;
use App\Features\Authentication\Http\Controllers\SessionController;
use App\Features\Authentication\Http\Controllers\OnboardingController;
use App\Features\UserManagement\Http\Controllers\UserController;
use App\Features\UserManagement\Http\Controllers\ProfileController;
use App\Features\UserManagement\Http\Controllers\RoleController;
use App\Features\CompanyManagement\Http\Controllers\CompanyController;
use App\Features\CompanyManagement\Http\Controllers\CompanyFollowerController;
use App\Features\CompanyManagement\Http\Controllers\CompanyRequestController;
use App\Features\CompanyManagement\Http\Controllers\CompanyRequestAdminController;
use App\Features\CompanyManagement\Http\Controllers\CompanyIndustryController;
use App\Features\ContentManagement\Http\Controllers\AnnouncementController;
use App\Features\ContentManagement\Http\Controllers\AnnouncementActionController;
use App\Features\ContentManagement\Http\Controllers\MaintenanceAnnouncementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas para tu API. Estas rutas son cargadas
| por el RouteServiceProvider dentro del grupo "api" con el prefijo /api
|
*/

// Health check para REST API
Route::get('health', [HealthController::class, 'check'])->name('api.health');

// ================================================================================
// REST API ENDPOINTS - Authentication
// ================================================================================

Route::prefix('auth')->group(function () {
    // ========== Registro y Login (Público) ==========
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/login/google', [AuthController::class, 'loginWithGoogle'])->name('auth.login.google');

    // ========== Tokens (Público) ==========
    Route::post('/refresh', [RefreshTokenController::class, 'refresh'])->name('auth.refresh');

    // ========== Contraseña (Público) ==========
    Route::post('/password-reset', [PasswordResetController::class, 'store'])->name('auth.password.reset');
    Route::post('/password-reset/confirm', [PasswordResetController::class, 'confirm'])->name('auth.password.confirm');
    Route::get('/password-reset/status', [PasswordResetController::class, 'status'])->name('auth.password.status');

    // ========== Email (Público) ==========
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->name('auth.email.verify');

    // ========== Rutas Autenticadas (Requieren JWT OBLIGATORIAMENTE) ==========
    Route::middleware('jwt.require')->group(function () {
        // ========== Sesiones ==========
        Route::post('/logout', [SessionController::class, 'logout'])->name('auth.logout');
        Route::get('/sessions', [SessionController::class, 'index'])->name('auth.sessions');
        Route::delete('/sessions/{sessionId}', [SessionController::class, 'revoke'])->name('auth.session.revoke');

        // ========== Email (Autenticado) ==========
        Route::get('/email/status', [EmailVerificationController::class, 'status'])->name('auth.email.status');
        Route::post('/email/verify/resend', [EmailVerificationController::class, 'resend'])->name('auth.email.resend');

        // ========== Info del Usuario ==========
        Route::get('/status', [AuthController::class, 'status'])->name('auth.status');

        // ========== Onboarding ==========
        Route::post('/onboarding/completed', [OnboardingController::class, 'markCompleted'])->name('auth.onboarding.completed');
    });
});

// ================================================================================
// REST API ENDPOINTS - User Management
// ================================================================================

Route::middleware('jwt.require')->group(function () {
    // ========== Current User Endpoints (Any Authenticated User) ==========
    Route::get('/users/me', [UserController::class, 'me'])->name('users.me');
    Route::get('/users/me/profile', [ProfileController::class, 'show'])->name('users.profile.show');

    Route::patch('/users/me/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:30,60')  // 30 requests per hour
        ->name('users.profile.update');

    Route::patch('/users/me/preferences', [ProfileController::class, 'updatePreferences'])
        ->middleware('throttle:50,60')  // 50 requests per hour
        ->name('users.preferences.update');

    // ========== User Viewing (Any Authenticated User can view themselves, admins can view others) ==========
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');

    // ========== Admin Endpoints (PLATFORM_ADMIN or COMPANY_ADMIN) ==========
    Route::middleware(['role:PLATFORM_ADMIN,COMPANY_ADMIN'])->group(function () {
        // User listing
        Route::get('/users', [UserController::class, 'index'])->name('users.index');

        // Role management
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');

        Route::post('/users/{userId}/roles', [RoleController::class, 'assign'])
            ->middleware('throttle.user:100,60,assign-role')  // 100 requests per 60 minutes per authenticated user
            ->name('users.roles.assign');

        Route::delete('/users/roles/{roleId}', [RoleController::class, 'remove'])->name('users.roles.remove');
    });

    // ========== Platform Admin Only Endpoints ==========
    Route::middleware(['role:PLATFORM_ADMIN'])->group(function () {
        Route::put('/users/{id}/status', [UserController::class, 'updateStatus'])->name('users.status.update');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

// ================================================================================
// REST API ENDPOINTS - Company Management
// ================================================================================

// ========== Public Routes (No Authentication Required) ==========
Route::get('/companies/minimal', [CompanyController::class, 'minimal'])->name('companies.minimal');

// Company Industries (Public - for form selectors)
Route::get('/company-industries', [CompanyIndustryController::class, 'index'])->name('company-industries.index');

Route::post('/company-requests', [CompanyRequestController::class, 'store'])
    ->middleware('throttle:3,60')  // 3 requests per hour
    ->name('company-requests.store');

// ========== Authenticated Routes (Require JWT) ==========
Route::middleware('jwt.require')->group(function () {

    // ========== COMPANIES - User Endpoints ==========

    // Explore companies (authenticated users)
    Route::get('/companies/explore', [CompanyController::class, 'explore'])->name('companies.explore');

    // Companies followed by the user
    Route::get('/companies/followed', [CompanyFollowerController::class, 'followed'])->name('companies.followed');

    // Check if following a company
    Route::get('/companies/{company}/is-following', [CompanyFollowerController::class, 'isFollowing'])
        ->name('companies.following');

    // Follow/Unfollow company
    Route::post('/companies/{company}/follow', [CompanyFollowerController::class, 'follow'])
        ->middleware('throttle:20,60')  // 20 requests per hour
        ->name('companies.follow');

    Route::delete('/companies/{company}/unfollow', [CompanyFollowerController::class, 'unfollow'])
        ->name('companies.unfollow');

    // View company details (with policy)
    Route::get('/companies/{company}', [CompanyController::class, 'show'])
        ->name('companies.show');

    // ========== COMPANIES - Admin Endpoints ==========

    // List companies (PLATFORM_ADMIN or COMPANY_ADMIN)
    Route::middleware(['role:PLATFORM_ADMIN,COMPANY_ADMIN'])->group(function () {
        Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    });

    // Create company (PLATFORM_ADMIN only)
    Route::post('/companies', [CompanyController::class, 'store'])
        ->middleware(['role:PLATFORM_ADMIN', 'throttle:10,60'])  // 10 per hour
        ->name('companies.store');

    // Update company (PLATFORM_ADMIN or COMPANY_ADMIN owner with policy)
    Route::match(['put', 'patch'], '/companies/{company}', [CompanyController::class, 'update'])
        ->name('companies.update');

    // ========== COMPANY REQUESTS - Admin Endpoints ==========

    Route::middleware(['role:PLATFORM_ADMIN'])->prefix('company-requests')->group(function () {
        // List company requests
        Route::get('/', [CompanyRequestController::class, 'index'])->name('company-requests.index');

        // Approve/Reject company requests
        Route::post('/{companyRequest}/approve', [CompanyRequestAdminController::class, 'approve'])
            ->name('company-requests.approve')
            ->missing(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found',
                    'code' => 'REQUEST_NOT_FOUND',
                    'category' => 'resource',
                ], 404);
            });

        Route::post('/{companyRequest}/reject', [CompanyRequestAdminController::class, 'reject'])
            ->name('company-requests.reject')
            ->missing(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Request not found',
                    'code' => 'REQUEST_NOT_FOUND',
                    'category' => 'resource',
                ], 404);
            });
    });
});
// ================================================================================
// REST API ENDPOINTS - Content Management (Announcements)
// ================================================================================

Route::middleware(['jwt.require', 'role:COMPANY_ADMIN'])->prefix('announcements')->group(function () {

    // ========== MAINTENANCE ANNOUNCEMENTS ==========

    // Create maintenance announcement (draft, publish or schedule in one request)
    Route::post('/maintenance', [MaintenanceAnnouncementController::class, 'store'])
        ->name('announcements.maintenance.store');

    // Mark maintenance as started
    Route::post('/maintenance/{announcement}/start', [MaintenanceAnnouncementController::class, 'markStart'])
        ->name('announcements.maintenance.start');

    // Mark maintenance as completed
    Route::post('/maintenance/{announcement}/complete', [MaintenanceAnnouncementController::class, 'markComplete'])
        ->name('announcements.maintenance.complete');

    // ========== GENERAL ANNOUNCEMENT ACTIONS ==========

    // Update announcement (partial updates for DRAFT or SCHEDULED only)
    Route::put('/{announcement}', [AnnouncementController::class, 'update'])
        ->name('announcements.update');

    // Delete announcement (soft delete)
    Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])
        ->name('announcements.destroy');

    // Publish announcement immediately
    Route::post('/{announcement}/publish', [AnnouncementActionController::class, 'publish'])
        ->name('announcements.publish');

    // Schedule announcement for future publication
    Route::post('/{announcement}/schedule', [AnnouncementActionController::class, 'schedule'])
        ->name('announcements.schedule');

    // Unschedule announcement (back to DRAFT)
    Route::post('/{announcement}/unschedule', [AnnouncementActionController::class, 'unschedule'])
        ->name('announcements.unschedule');

    // Archive announcement
    Route::post('/{announcement}/archive', [AnnouncementActionController::class, 'archive'])
        ->name('announcements.archive');

    // Restore archived announcement
    Route::post('/{announcement}/restore', [AnnouncementActionController::class, 'restore'])
        ->name('announcements.restore');
});
