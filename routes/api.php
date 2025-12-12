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
use App\Features\ServiceIntegration\Http\Controllers\ServiceFileController;
use App\Features\ContentManagement\Http\Controllers\AnnouncementSchemaController;
use App\Features\ContentManagement\Http\Controllers\AnnouncementActionController;
use App\Features\ContentManagement\Http\Controllers\MaintenanceAnnouncementController;
use App\Features\ContentManagement\Http\Controllers\IncidentAnnouncementController;
use App\Features\ContentManagement\Http\Controllers\NewsAnnouncementController;
use App\Features\ContentManagement\Http\Controllers\AlertAnnouncementController;
use App\Features\ContentManagement\Http\Controllers\HelpCenterCategoryController;
use App\Features\ContentManagement\Http\Controllers\ArticleController;
use App\Features\TicketManagement\Http\Controllers\CategoryController;
use App\Features\CompanyManagement\Http\Controllers\AreaController;
use App\Features\TicketManagement\Http\Controllers\TicketPredictionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas para tu API. Estas rutas son cargadas
| por el RouteServiceProvider dentro del grupo "api" con el prefijo /api
|
*/

// API root endpoint - serves OpenAPI/Swagger specification
Route::get('/', function () {
    $docsPath = storage_path('api-docs/api-docs.json');

    if (file_exists($docsPath)) {
        $docs = json_decode(file_get_contents($docsPath), true);
        return response()->json($docs);
    }

    return response()->json([
        'name' => 'Helpdesk API',
        'version' => '1.0.0',
        'status' => 'operational',
        'documentation' => '/api/documentation',
    ]);
})->name('api.root');

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

        // ========== Active Role Management (NEW) ==========
        Route::post('/select-role', [AuthController::class, 'selectRole'])->name('auth.select-role');
        Route::get('/available-roles', [AuthController::class, 'availableRoles'])->name('auth.available-roles');
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

    Route::post('/users/me/avatar', [ProfileController::class, 'uploadAvatar'])
        ->middleware('throttle:3,60')  // 3 requests per hour
        ->name('users.avatar.upload');

    // ========== User Viewing (Any Authenticated User can view themselves, admins can view others) ==========
    Route::get('/users/{id}', [UserController::class, 'show'])->name('users.show');

    // User listing (Accessible by PLATFORM_ADMIN, COMPANY_ADMIN, AGENT)
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('role:PLATFORM_ADMIN,COMPANY_ADMIN,AGENT')
        ->name('users.index');

    // ========== Admin Endpoints (PLATFORM_ADMIN or COMPANY_ADMIN) ==========
    Route::middleware(['role:PLATFORM_ADMIN,COMPANY_ADMIN'])->group(function () {
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

// Get company areas enabled status (for frontend ticket creation)
// UUID constraint prevents capturing /companies/me/settings/areas-enabled
Route::get('/companies/{companyId}/settings/areas-enabled', [CompanyController::class, 'getCompanyAreasEnabledPublic'])
    ->where('companyId', '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}')
    ->name('companies.areas-enabled.public');

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

    // ========== COMPANY SETTINGS - Areas Feature Toggle (COMPANY_ADMIN Only) ==========
    // IMPORTANT: Must be BEFORE /companies/{company} to avoid route conflict
    Route::middleware(['role:COMPANY_ADMIN'])->group(function () {
        // Get if areas are enabled for the company
        Route::get('/companies/me/settings/areas-enabled', [CompanyController::class, 'getAreasEnabled'])
            ->name('companies.settings.areas-enabled.get');

        // Enable/disable areas for the company
        Route::patch('/companies/me/settings/areas-enabled', [CompanyController::class, 'toggleAreasEnabled'])
            ->name('companies.settings.areas-enabled.toggle');
    });

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

    // Upload company branding (PLATFORM_ADMIN or COMPANY_ADMIN owner)
    Route::post('/companies/{company}/logo', [CompanyController::class, 'uploadLogo'])
        ->middleware('throttle:3,60')  // 3 requests per hour (180 second limit)
        ->name('companies.logo.upload');

    Route::post('/companies/{company}/favicon', [CompanyController::class, 'uploadFavicon'])
        ->middleware('throttle:3,60')  // 3 requests per hour (180 second limit)
        ->name('companies.favicon.upload');

    // ========== AREAS - Read Endpoints (All Authenticated Users) ==========
    // List areas for a company
    Route::get('/areas', [AreaController::class, 'index'])
        ->name('areas.index');

    // ========== AREAS - Management Endpoints (COMPANY_ADMIN or PLATFORM_ADMIN) ==========
    Route::middleware(['role:COMPANY_ADMIN,PLATFORM_ADMIN'])->group(function () {
        // Create an area
        Route::post('/areas', [AreaController::class, 'store'])
            ->name('areas.store');

        // Update an area
        Route::put('/areas/{id}', [AreaController::class, 'update'])
            ->name('areas.update');

        // Delete an area
        Route::delete('/areas/{id}', [AreaController::class, 'destroy'])
            ->name('areas.destroy');
    });

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

// ========== HELP CENTER - Public Endpoints ==========
Route::get('/help-center/categories', [HelpCenterCategoryController::class, 'index'])
    ->name('help-center.categories.index');

// ========== ANNOUNCEMENTS - Read Endpoints (All Authenticated Users) ==========
Route::middleware('jwt.require')->group(function () {
    // List announcements with role-based visibility (CAPA 3E)
    Route::get('/announcements', [AnnouncementController::class, 'index'])
        ->name('announcements.index');

    // Get announcement schemas (COMPANY_ADMIN and PLATFORM_ADMIN only) - MUST come before {announcement}
    Route::get('/announcements/schemas', AnnouncementSchemaController::class)
        ->name('announcements.schemas');

    // Get single announcement with role-based visibility (CAPA 3E)
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])
        ->name('announcements.show')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // List help center articles (only PUBLISHED articles visible to all users)
    Route::get('/help-center/articles', [ArticleController::class, 'index'])
        ->name('articles.index');

    // Get single help center article
    Route::get('/help-center/articles/{article}', [ArticleController::class, 'show'])
        ->name('articles.show');
});

// ========== ANNOUNCEMENTS - Management Endpoints (COMPANY_ADMIN Only) ==========
Route::middleware(['jwt.require', 'role:COMPANY_ADMIN'])->prefix('announcements')->group(function () {

    // ========== MAINTENANCE ANNOUNCEMENTS ==========

    // Create a maintenance announcement (draft, publish or schedule in one request)
    Route::post('/maintenance', [MaintenanceAnnouncementController::class, 'store'])
        ->name('announcements.maintenance.store');

    // Mark maintenance as started
    Route::post('/maintenance/{announcement}/start', [MaintenanceAnnouncementController::class, 'markStart'])
        ->name('announcements.maintenance.start')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // Mark maintenance as completed
    Route::post('/maintenance/{announcement}/complete', [MaintenanceAnnouncementController::class, 'markComplete'])
        ->name('announcements.maintenance.complete')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // ========== GENERAL ANNOUNCEMENT ACTIONS ==========

    // Update announcement (partial updates for DRAFT or SCHEDULED only)
    Route::put('/{announcement}', [AnnouncementController::class, 'update'])
        ->name('announcements.update')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // Delete announcement (soft delete)
    Route::delete('/{announcement}', [AnnouncementController::class, 'destroy'])
        ->name('announcements.destroy')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // Publish announcement immediately
    Route::post('/{announcement}/publish', [AnnouncementActionController::class, 'publish'])
        ->name('announcements.publish')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // Schedule announcement for future publication
    Route::post('/{announcement}/schedule', [AnnouncementActionController::class, 'schedule'])
        ->name('announcements.schedule')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // Unschedule announcement (back to DRAFT)
    Route::post('/{announcement}/unschedule', [AnnouncementActionController::class, 'unschedule'])
        ->name('announcements.unschedule')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // Archive announcement
    Route::post('/{announcement}/archive', [AnnouncementActionController::class, 'archive'])
        ->name('announcements.archive')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // Restore archived announcement
    Route::post('/{announcement}/restore', [AnnouncementActionController::class, 'restore'])
        ->name('announcements.restore')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // ========== INCIDENT ANNOUNCEMENTS ==========

    // Create incident announcement (draft, publish or schedule in one request)
    Route::post('/incidents', [IncidentAnnouncementController::class, 'store'])
        ->name('announcements.incidents.store');

    // Resolve incident
    Route::post('/incidents/{announcement}/resolve', [IncidentAnnouncementController::class, 'resolve'])
        ->name('announcements.incidents.resolve')
        ->missing(function () {
            return response()->json([
                'success' => false,
                'message' => 'Announcement not found',
                'code' => 'NOT_FOUND',
                'category' => 'resource',
            ], 404);
        });

    // ========== NEWS ANNOUNCEMENTS ==========

    // Create news announcement (draft, publish or schedule in one request)
    Route::post('/news', [NewsAnnouncementController::class, 'store'])
        ->name('announcements.news.store');

    // ========== ALERT ANNOUNCEMENTS ==========

    // Create alert announcement (draft, publish or schedule in one request)
    Route::post('/alerts', [AlertAnnouncementController::class, 'store'])
        ->name('announcements.alerts.store');
});

// ========== HELP CENTER ARTICLES - Management Endpoints (COMPANY_ADMIN Only) ==========
Route::middleware(['jwt.require', 'role:COMPANY_ADMIN'])->group(function () {
    // Create a help center a rticle (always as DRAFT)
    Route::post('/help-center/articles', [ArticleController::class, 'store'])
        ->name('articles.store');

    // Update help center article
    Route::put('/help-center/articles/{article}', [ArticleController::class, 'update'])
        ->name('articles.update');

    // Publish help center article
    Route::post('/help-center/articles/{article}/publish', [ArticleController::class, 'publish'])
        ->name('articles.publish');

    // Unpublish help center article
    Route::post('/help-center/articles/{article}/unpublish', [ArticleController::class, 'unpublish'])
        ->name('articles.unpublish');

    // Delete help center article (only DRAFT articles)
    Route::delete('/help-center/articles/{article}', [ArticleController::class, 'destroy'])
        ->name('articles.destroy');
});

// ================================================================================
// REST API ENDPOINTS - Ticket Management (Categories)
// ================================================================================

// ========== TICKET CATEGORIES - Read Endpoints (All Authenticated Users) ==========
Route::middleware('jwt.require')->group(function () {
    // List categories for a company
    Route::get('/tickets/categories', [CategoryController::class, 'index'])
        ->name('tickets.categories.index');
});

// ========== TICKET CATEGORIES - Management Endpoints (COMPANY_ADMIN Only) ==========
Route::middleware(['jwt.require', 'role:COMPANY_ADMIN'])->group(function () {
    // Create a ticket category
    Route::post('/tickets/categories', [CategoryController::class, 'store'])
        ->name('tickets.categories.store');

    // Update a ticket category
    Route::put('/tickets/categories/{id}', [CategoryController::class, 'update'])
        ->name('tickets.categories.update');

    // Delete a ticket category
    Route::delete('/tickets/categories/{id}', [CategoryController::class, 'destroy'])
        ->name('tickets.categories.destroy');
});

// ================================================================================
// REST API ENDPOINTS - Ticket Management (Tickets CRUD)
// ================================================================================

// Route Model Binding: Use ticket_code instead of UUID primary key
Route::bind('ticket', function ($value) {
    return \App\Features\TicketManagement\Models\Ticket::where('ticket_code', $value)->firstOrFail();
});

Route::middleware('jwt.require')->group(function () {
    // AI-powered area prediction for tickets (USER only)
    Route::post('/tickets/predict-area', [TicketPredictionController::class, 'predictArea'])
        ->middleware('role:USER')
        ->name('tickets.predict-area');

    // Create ticket (USER only)
    Route::post('/tickets', [\App\Features\TicketManagement\Http\Controllers\TicketController::class, 'store'])
        ->middleware('role:USER')
        ->name('tickets.store');

    // List tickets (all authenticated users with role-based filtering)/l
    Route::get('/tickets', [\App\Features\TicketManagement\Http\Controllers\TicketController::class, 'index'])
        ->name('tickets.index');

    // Get single ticket (policy-based authorization)
    Route::get('/tickets/{ticket}', [\App\Features\TicketManagement\Http\Controllers\TicketController::class, 'show'])
        ->name('tickets.show');

    // Update ticket (policy-based authorization)
    Route::patch('/tickets/{ticket}', [\App\Features\TicketManagement\Http\Controllers\TicketController::class, 'update'])
        ->name('tickets.update');

    // Delete ticket (COMPANY_ADMIN only, policy validates CLOSED status)
    Route::delete('/tickets/{ticket}', [\App\Features\TicketManagement\Http\Controllers\TicketController::class, 'destroy'])
        ->middleware('role:COMPANY_ADMIN')
        ->name('tickets.destroy');

    // ================================================================================
    // REST API ENDPOINTS - Ticket Management (Responses)
    // ================================================================================

    // Create response (authenticated users with ticket access)
    Route::post('/tickets/{ticket}/responses', [\App\Features\TicketManagement\Http\Controllers\TicketResponseController::class, 'store'])
        ->name('tickets.responses.store');

    // List responses (authenticated users with ticket access)
    Route::get('/tickets/{ticket}/responses', [\App\Features\TicketManagement\Http\Controllers\TicketResponseController::class, 'index'])
        ->name('tickets.responses.index');

    // Get individual response (authenticated users with ticket access)
    Route::get('/tickets/{ticket}/responses/{response}', [\App\Features\TicketManagement\Http\Controllers\TicketResponseController::class, 'show'])
        ->name('tickets.responses.show');

    // Update response (policy-based authorization)
    Route::patch('/tickets/{ticket}/responses/{response}', [\App\Features\TicketManagement\Http\Controllers\TicketResponseController::class, 'update'])
        ->name('tickets.responses.update');

    // Delete response (policy-based authorization)
    Route::delete('/tickets/{ticket}/responses/{response}', [\App\Features\TicketManagement\Http\Controllers\TicketResponseController::class, 'destroy'])
        ->name('tickets.responses.destroy');

    // ================================================================================
    // REST API ENDPOINTS - Ticket Management (Attachments)
    // ================================================================================

    // Upload attachment to ticket (policy-based authorization)
    Route::post('/tickets/{ticket}/attachments', [\App\Features\TicketManagement\Http\Controllers\TicketAttachmentController::class, 'store'])
        ->name('tickets.attachments.store');

    // List attachments for ticket (policy-based authorization)
    Route::get('/tickets/{ticket}/attachments', [\App\Features\TicketManagement\Http\Controllers\TicketAttachmentController::class, 'index'])
        ->name('tickets.attachments.index');

    // Upload attachment to specific response (policy-based authorization)
    Route::post('/tickets/{ticket}/responses/{response}/attachments', [\App\Features\TicketManagement\Http\Controllers\TicketAttachmentController::class, 'storeToResponse'])
        ->name('tickets.responses.attachments.store');

    // Delete attachment (policy-based authorization)
    Route::delete('/tickets/{ticket}/attachments/{attachment}', [\App\Features\TicketManagement\Http\Controllers\TicketAttachmentController::class, 'destroy'])
        ->name('tickets.attachments.destroy');

    // Download attachment (policy-based authorization)
    Route::get('/tickets/attachments/{attachment}/download', [\App\Features\TicketManagement\Http\Controllers\TicketAttachmentController::class, 'download'])
        ->name('tickets.attachments.download');
    // ================================================================================
    // REST API ENDPOINTS - Ticket Management (Actions)
    // ================================================================================

    // Resolve ticket (AGENT only, policy-based authorization)
    Route::post('/tickets/{ticket}/resolve', [\App\Features\TicketManagement\Http\Controllers\TicketActionController::class, 'resolve'])
        ->name('tickets.resolve');

    // Close ticket (policy-based authorization)
    Route::post('/tickets/{ticket}/close', [\App\Features\TicketManagement\Http\Controllers\TicketActionController::class, 'close'])
        ->name('tickets.close');

    // Reopen ticket (policy-based authorization)
    Route::post('/tickets/{ticket}/reopen', [\App\Features\TicketManagement\Http\Controllers\TicketActionController::class, 'reopen'])
        ->name('tickets.reopen');

    // Assign ticket to agent (AGENT only, policy-based authorization)
    Route::post('/tickets/{ticket}/assign', [\App\Features\TicketManagement\Http\Controllers\TicketActionController::class, 'assign'])
        ->name('tickets.assign');

    // Send reminder email to ticket creator (AGENT only, policy-based authorization)
    Route::post('/tickets/{ticket}/remind', [\App\Features\TicketManagement\Http\Controllers\TicketReminderController::class, 'sendReminder'])
        ->name('tickets.remind');
});

// ================================================================================
// REST API ENDPOINTS - Analytics
// ================================================================================

Route::middleware(['jwt.require'])->prefix('analytics')->group(function () {
    Route::get('/company-dashboard', [\App\Features\Analytics\Http\Controllers\AnalyticsController::class, 'dashboard'])
        ->middleware('role:COMPANY_ADMIN')
        ->name('analytics.company-dashboard');

    Route::get('/user-dashboard', [\App\Features\Analytics\Http\Controllers\AnalyticsController::class, 'userDashboard'])
        ->name('analytics.user-dashboard');

    Route::get('/agent-dashboard', [\App\Features\Analytics\Http\Controllers\AnalyticsController::class, 'agentDashboard'])
        ->middleware('role:AGENT')
        ->name('analytics.agent-dashboard');

    Route::get('/platform-dashboard', [\App\Features\Analytics\Http\Controllers\AnalyticsController::class, 'platformDashboard'])
        ->middleware('role:PLATFORM_ADMIN')
        ->name('analytics.platform-dashboard');

    // Real-time API Traffic Monitoring (PLATFORM_ADMIN only)
    Route::middleware('role:PLATFORM_ADMIN')->group(function () {
        // Get full traffic history (last 60 seconds) + statistics
        Route::get('/realtime-traffic', [\App\Features\Analytics\Http\Controllers\RealtimeTrafficController::class, 'index'])
            ->name('analytics.realtime-traffic');
        
        // Get only the latest data point (for efficient polling after initial load)
        Route::get('/realtime-traffic/latest', [\App\Features\Analytics\Http\Controllers\RealtimeTrafficController::class, 'latest'])
            ->name('analytics.realtime-traffic.latest');
    });

    // Company Full Stats - for Platform Admin viewing company details
    Route::get('/companies/{companyId}/stats', [\App\Features\Analytics\Http\Controllers\AnalyticsController::class, 'companyStats'])
        ->middleware('role:PLATFORM_ADMIN')
        ->name('analytics.company-stats');
});

// ================================================================================
// REST API ENDPOINTS - Activity Logs
// ================================================================================

Route::middleware(['jwt.require'])->prefix('activity-logs')->group(function () {
    // List activity logs (admins can see all, users see only their own)
    Route::get('/', [\App\Features\AuditLog\Http\Controllers\ActivityLogController::class, 'index'])
        ->name('activity-logs.index');

    // Get my activity logs
    Route::get('/my', [\App\Features\AuditLog\Http\Controllers\ActivityLogController::class, 'myActivity'])
        ->name('activity-logs.my');

    // Get activity logs for a specific entity
    Route::get('/entity/{entityType}/{entityId}', [\App\Features\AuditLog\Http\Controllers\ActivityLogController::class, 'entityActivity'])
        ->name('activity-logs.entity');
});

// ================================================================================
// REST API ENDPOINTS - Storage for Microservices
// ================================================================================
// Used by microservices (Ratings, Macros) for file attachments
// Authenticated via standard JWT

Route::middleware(['jwt.require'])->prefix('files')->group(function () {
    Route::get('/', [ServiceFileController::class, 'index'])
        ->name('files.index');
    
    Route::post('/upload', [ServiceFileController::class, 'upload'])
        ->name('files.upload');
    
    Route::get('/{key}', [ServiceFileController::class, 'show'])
        ->where('key', '.*')
        ->name('files.show');
    
    Route::delete('/{key}', [ServiceFileController::class, 'destroy'])
        ->where('key', '.*')
        ->name('files.destroy');
});

// ================================================================================
// REST API ENDPOINTS - External Integration (Widget)
// ================================================================================
// Endpoints para el widget embebible de Helpdesk.
// Todos requieren API Key válida en el header X-Service-Key.

use App\Features\ExternalIntegration\Http\Controllers\ExternalAuthController;

Route::prefix('external')->middleware(['service.api-key', 'throttle:60,1'])->group(function () {
    // Validar que la API Key sea válida
    Route::post('/validate-key', [ExternalAuthController::class, 'validateKey'])
        ->name('external.validate-key');
    
    // Verificar si un email existe en Helpdesk
    Route::post('/check-user', [ExternalAuthController::class, 'checkUser'])
        ->name('external.check-user');
    
    // Login automático (trusted, sin contraseña)
    Route::post('/login', [ExternalAuthController::class, 'login'])
        ->name('external.login');
    
    // Login manual (con contraseña, fallback)
    Route::post('/login-manual', [ExternalAuthController::class, 'loginManual'])
        ->name('external.login-manual');
    
    // Registro de nuevo usuario (con contraseña)
    Route::post('/register', [ExternalAuthController::class, 'register'])
        ->name('external.register');
    
    // Refresh token (sin cookies, usa Authorization header)
    Route::post('/refresh', [ExternalAuthController::class, 'refreshToken'])
        ->name('external.refresh');
});

// ================================================================================
// REST API ENDPOINTS - API Keys Management (Platform Admin)
// ================================================================================
// Endpoints para gestionar las API Keys de integración externa.
// Requieren autenticación JWT y rol PLATFORM_ADMIN.

use App\Features\ExternalIntegration\Http\Controllers\ApiKeyAdminController;

Route::prefix('admin/api-keys')->middleware(['jwt.require', 'spatie.active_role:PLATFORM_ADMIN'])->group(function () {
    // Listar todas las API Keys con filtros y paginación
    Route::get('/', [ApiKeyAdminController::class, 'list'])
        ->name('admin.api-keys.list');
    
    // Obtener estadísticas de API Keys
    Route::get('/statistics', [ApiKeyAdminController::class, 'statistics'])
        ->name('admin.api-keys.statistics');
    
    // Crear nueva API Key
    Route::post('/', [ApiKeyAdminController::class, 'store'])
        ->name('admin.api-keys.store');
    
    // Revocar una API Key
    Route::post('/{id}/revoke', [ApiKeyAdminController::class, 'revoke'])
        ->name('admin.api-keys.revoke');
    
    // Activar una API Key revocada
    Route::post('/{id}/activate', [ApiKeyAdminController::class, 'activate'])
        ->name('admin.api-keys.activate');
    
    // Eliminar una API Key permanentemente
    Route::delete('/{id}', [ApiKeyAdminController::class, 'destroy'])
        ->name('admin.api-keys.destroy');
    
    // Obtener API Keys de una empresa específica (para modal de empresa)
    Route::get('/by-company/{companyId}', [ApiKeyAdminController::class, 'byCompany'])
        ->name('admin.api-keys.by-company');
});
