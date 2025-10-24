<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - Refactored for Clean Separation of Concerns
|--------------------------------------------------------------------------
|
| Architecture:
| - Backend (Laravel): Only provides JSON responses via GraphQL + JWT validation
| - Frontend (React): Handles ALL authorization logic via AuthGuard
|
| Middleware aliases:
| - web.auth: WebAuthenticationMiddleware (validates JWT token only)
|
*/

// ============================================================================
// PUBLIC ROUTES (No auth required)
// Frontend (AuthGuard + PublicRoute) handles redirections
// ============================================================================

Route::get('/login', function () {
    return Inertia::render('Public/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Public/Register');
})->name('register');

Route::get('/forgot-password', function () {
    return Inertia::render('Public/ForgotPassword');
})->name('password.request');

Route::get('/reset-password/{token}', function (string $token) {
    return Inertia::render('Public/ResetPassword', ['token' => $token]);
})->name('password.reset');

// ============================================================================
// AUTHENTICATED ROUTES (ALL behind web.auth middleware)
// Frontend (AuthGuard) handles ALL authorization & redirections
// ============================================================================

Route::middleware(['web.auth'])->group(function () {

    // Email verification (before onboarding)
    Route::get('/verify-email', function () {
        return Inertia::render('Public/VerifyEmail');
    })->name('verify.email');

    // Onboarding steps
    Route::prefix('onboarding')->group(function () {
        Route::get('/profile', function () {
            return Inertia::render('Authenticated/Onboarding/CompleteProfile');
        })->name('onboarding.profile');

        Route::get('/preferences', function () {
            return Inertia::render('Authenticated/Onboarding/ConfigurePreferences');
        })->name('onboarding.preferences');
    });

    // Role selector (for multi-role users)
    Route::get('/role-selector', function () {
        return Inertia::render('Authenticated/RoleSelector');
    })->name('role.selector');

    // Home & Dashboard
    Route::get('/', function () {
        return Inertia::render('Home');
    })->name('home');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('admin.dashboard');

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

        Route::get('/settings', function () {
            return Inertia::render('Admin/Settings');
        })->name('admin.settings');

        Route::get('/audit-logs', function () {
            return Inertia::render('Admin/AuditLogs');
        })->name('admin.audit.logs');
    });

    // Company admin routes
    Route::prefix('empresa')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Company/Dashboard');
        })->name('company.dashboard');

        Route::get('/tickets', function () {
            return Inertia::render('Company/Tickets');
        })->name('company.tickets');

        Route::get('/tickets/{id}', function (string $id) {
            return Inertia::render('Company/Tickets/Show', ['ticketId' => $id]);
        })->name('company.tickets.show');

        Route::get('/team', function () {
            return Inertia::render('Company/Team');
        })->name('company.team');

        Route::get('/team/invite', function () {
            return Inertia::render('Company/Team/Invite');
        })->name('company.team.invite');

        Route::get('/settings', function () {
            return Inertia::render('Company/Settings');
        })->name('company.settings');

        Route::get('/profile', function () {
            return Inertia::render('Company/Profile');
        })->name('company.profile');
    });

    // Agent routes
    Route::prefix('agent')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Agent/Dashboard');
        })->name('agent.dashboard');

        Route::get('/tickets', function () {
            return Inertia::render('Agent/Tickets');
        })->name('agent.tickets');

        Route::get('/tickets/{id}', function (string $id) {
            return Inertia::render('Agent/Tickets/Show', ['ticketId' => $id]);
        })->name('agent.tickets.show');

        Route::get('/knowledge-base', function () {
            return Inertia::render('Agent/KnowledgeBase');
        })->name('agent.knowledge.base');
    });

    // User tickets
    Route::prefix('tickets')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Tickets/Index');
        })->name('tickets.index');

        Route::get('/create', function () {
            return Inertia::render('Tickets/Create');
        })->name('tickets.create');

        Route::get('/{id}', function (string $id) {
            return Inertia::render('Tickets/Show', ['ticketId' => $id]);
        })->name('tickets.show');

        Route::get('/{id}/edit', function (string $id) {
            return Inertia::render('Tickets/Edit', ['ticketId' => $id]);
        })->name('tickets.edit');
    });

    // Profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Profile/Index');
        })->name('profile.index');

        Route::get('/edit', function () {
            return Inertia::render('Profile/Edit');
        })->name('profile.edit');

        Route::get('/security', function () {
            return Inertia::render('Profile/Security');
        })->name('profile.security');

        Route::get('/notifications', function () {
            return Inertia::render('Profile/Notifications');
        })->name('profile.notifications');

        Route::get('/sessions', function () {
            return Inertia::render('Profile/Sessions');
        })->name('profile.sessions');
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', function () {
            return Inertia::render('Notifications/Index');
        })->name('notifications.index');

        Route::get('/{id}', function (string $id) {
            return Inertia::render('Notifications/Show', ['notificationId' => $id]);
        })->name('notifications.show');
    });

    // Search
    Route::get('/search', function () {
        return Inertia::render('Search/Index', [
            'query' => request()->query('q', ''),
        ]);
    })->name('search');
});
