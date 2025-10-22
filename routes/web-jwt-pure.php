<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| JWT Pure Web Routes
|--------------------------------------------------------------------------
|
| These routes use pure JWT authentication (no sessions).
| All authenticated routes use 'jwt.auth' middleware.
|
| Middleware aliases (must be registered in bootstrap/app.php):
| - jwt.auth: JWTAuthenticationMiddleware
| - jwt.role: JWTRoleMiddleware
| - jwt.onboarding: JWTOnboardingMiddleware
| - jwt.guest: JWTGuestMiddleware
|
*/

// ============================================================================
// GUEST ROUTES (Public - Not Authenticated)
// ============================================================================

Route::middleware(['jwt.guest'])->group(function () {

    // Login page
    Route::get('/login', function () {
        return Inertia::render('Public/Login');
    })->name('login');

    // Registration page
    Route::get('/register', function () {
        return Inertia::render('Public/Register');
    })->name('register');

    // Forgot password page
    Route::get('/forgot-password', function () {
        return Inertia::render('Public/ForgotPassword');
    })->name('password.request');

    // Reset password page (with token)
    Route::get('/reset-password/{token}', function (string $token) {
        return Inertia::render('Public/ResetPassword', ['token' => $token]);
    })->name('password.reset');
});

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================

Route::middleware(['jwt.auth'])->group(function () {

    // ========================================================================
    // ONBOARDING ROUTES (No onboarding middleware - accessible during setup)
    // ========================================================================

    Route::prefix('onboarding')->group(function () {

        Route::get('/', function () {
            return Inertia::render('Onboarding/Index');
        })->name('onboarding.index');

        Route::get('/step/{step}', function (string $step) {
            return Inertia::render('Onboarding/Step', ['step' => $step]);
        })->name('onboarding.step');
    });

    // Role selector (for users with multiple roles or no role)
    Route::get('/role-selector', function () {
        return Inertia::render('Authenticated/RoleSelector');
    })->name('role.selector');

    // ========================================================================
    // ONBOARDING-REQUIRED ROUTES (User must complete onboarding)
    // ========================================================================

    Route::middleware(['jwt.onboarding'])->group(function () {

        // --------------------------------------------------------------------
        // HOME & GENERAL ROUTES
        // --------------------------------------------------------------------

        Route::get('/', function () {
            return Inertia::render('Home');
        })->name('home');

        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');

        // --------------------------------------------------------------------
        // PLATFORM ADMIN ROUTES
        // --------------------------------------------------------------------

        Route::prefix('admin')->middleware(['jwt.role:PLATFORM_ADMIN'])->group(function () {

            // Admin dashboard
            Route::get('/dashboard', function () {
                return Inertia::render('Admin/Dashboard');
            })->name('admin.dashboard');

            // User management
            Route::get('/users', function () {
                return Inertia::render('Admin/Users');
            })->name('admin.users');

            Route::get('/users/create', function () {
                return Inertia::render('Admin/Users/Create');
            })->name('admin.users.create');

            Route::get('/users/{id}', function (string $id) {
                return Inertia::render('Admin/Users/Show', ['userId' => $id]);
            })->name('admin.users.show');

            Route::get('/users/{id}/edit', function (string $id) {
                return Inertia::render('Admin/Users/Edit', ['userId' => $id]);
            })->name('admin.users.edit');

            // Company management
            Route::get('/companies', function () {
                return Inertia::render('Admin/Companies');
            })->name('admin.companies');

            Route::get('/companies/create', function () {
                return Inertia::render('Admin/Companies/Create');
            })->name('admin.companies.create');

            Route::get('/companies/{id}', function (string $id) {
                return Inertia::render('Admin/Companies/Show', ['companyId' => $id]);
            })->name('admin.companies.show');

            Route::get('/companies/{id}/edit', function (string $id) {
                return Inertia::render('Admin/Companies/Edit', ['companyId' => $id]);
            })->name('admin.companies.edit');

            // System settings
            Route::get('/settings', function () {
                return Inertia::render('Admin/Settings');
            })->name('admin.settings');

            // Audit logs
            Route::get('/audit-logs', function () {
                return Inertia::render('Admin/AuditLogs');
            })->name('admin.audit.logs');
        });

        // --------------------------------------------------------------------
        // COMPANY ADMIN ROUTES
        // --------------------------------------------------------------------

        Route::prefix('empresa')->middleware(['jwt.role:COMPANY_ADMIN'])->group(function () {

            // Company dashboard
            Route::get('/dashboard', function () {
                return Inertia::render('Company/Dashboard');
            })->name('company.dashboard');

            // Ticket management
            Route::get('/tickets', function () {
                return Inertia::render('Company/Tickets');
            })->name('company.tickets');

            Route::get('/tickets/{id}', function (string $id) {
                return Inertia::render('Company/Tickets/Show', ['ticketId' => $id]);
            })->name('company.tickets.show');

            // Team management
            Route::get('/team', function () {
                return Inertia::render('Company/Team');
            })->name('company.team');

            Route::get('/team/invite', function () {
                return Inertia::render('Company/Team/Invite');
            })->name('company.team.invite');

            // Company settings
            Route::get('/settings', function () {
                return Inertia::render('Company/Settings');
            })->name('company.settings');

            // Company profile
            Route::get('/profile', function () {
                return Inertia::render('Company/Profile');
            })->name('company.profile');
        });

        // --------------------------------------------------------------------
        // AGENT ROUTES
        // --------------------------------------------------------------------

        Route::prefix('agent')->middleware(['jwt.role:AGENT'])->group(function () {

            // Agent dashboard
            Route::get('/dashboard', function () {
                return Inertia::render('Agent/Dashboard');
            })->name('agent.dashboard');

            // Assigned tickets
            Route::get('/tickets', function () {
                return Inertia::render('Agent/Tickets');
            })->name('agent.tickets');

            Route::get('/tickets/{id}', function (string $id) {
                return Inertia::render('Agent/Tickets/Show', ['ticketId' => $id]);
            })->name('agent.tickets.show');

            // Knowledge base (for agents to reference)
            Route::get('/knowledge-base', function () {
                return Inertia::render('Agent/KnowledgeBase');
            })->name('agent.knowledge.base');
        });

        // --------------------------------------------------------------------
        // USER ROUTES (Accessible to all authenticated users)
        // --------------------------------------------------------------------

        Route::prefix('tickets')->group(function () {

            // List user's tickets
            Route::get('/', function () {
                return Inertia::render('Tickets/Index');
            })->name('tickets.index');

            // Create new ticket
            Route::get('/create', function () {
                return Inertia::render('Tickets/Create');
            })->name('tickets.create');

            // View ticket details
            Route::get('/{id}', function (string $id) {
                return Inertia::render('Tickets/Show', ['ticketId' => $id]);
            })->name('tickets.show');

            // Edit ticket (if allowed)
            Route::get('/{id}/edit', function (string $id) {
                return Inertia::render('Tickets/Edit', ['ticketId' => $id]);
            })->name('tickets.edit');
        });

        // --------------------------------------------------------------------
        // PROFILE ROUTES (Accessible to all authenticated users)
        // --------------------------------------------------------------------

        Route::prefix('profile')->group(function () {

            // View profile
            Route::get('/', function () {
                return Inertia::render('Profile/Index');
            })->name('profile.index');

            // Edit profile
            Route::get('/edit', function () {
                return Inertia::render('Profile/Edit');
            })->name('profile.edit');

            // Security settings
            Route::get('/security', function () {
                return Inertia::render('Profile/Security');
            })->name('profile.security');

            // Notification preferences
            Route::get('/notifications', function () {
                return Inertia::render('Profile/Notifications');
            })->name('profile.notifications');

            // Active sessions
            Route::get('/sessions', function () {
                return Inertia::render('Profile/Sessions');
            })->name('profile.sessions');
        });

        // --------------------------------------------------------------------
        // NOTIFICATIONS ROUTES
        // --------------------------------------------------------------------

        Route::prefix('notifications')->group(function () {

            Route::get('/', function () {
                return Inertia::render('Notifications/Index');
            })->name('notifications.index');

            Route::get('/{id}', function (string $id) {
                return Inertia::render('Notifications/Show', ['notificationId' => $id]);
            })->name('notifications.show');
        });

        // --------------------------------------------------------------------
        // SEARCH ROUTE
        // --------------------------------------------------------------------

        Route::get('/search', function () {
            return Inertia::render('Search/Index', [
                'query' => request()->query('q', ''),
            ]);
        })->name('search');
    });
});
