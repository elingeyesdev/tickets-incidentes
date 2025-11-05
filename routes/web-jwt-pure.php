<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Blade Template Engine
|--------------------------------------------------------------------------
|
| All routes now use Laravel Blade templates instead of Inertia.js/React
| Place corresponding blade templates in resources/views/
|
*/

// ============================================================================
// PUBLIC ROUTES (No auth required)
// ============================================================================

Route::get('/login', function () {
    return view('public.login');
})->name('login');

Route::get('/register', function () {
    return view('public.register');
})->name('register');

Route::get('/forgot-password', function () {
    return view('public.forgot-password');
})->name('password.request');

Route::get('/reset-password/{token}', function (string $token) {
    return view('public.reset-password', ['token' => $token]);
})->name('password.reset');

Route::get('/', function () {
    return view('public.welcome');
})->name('home');

Route::get('/register-user', function () {
    return view('public.register');
})->name('register.user');

Route::get('/solicitud-empresa', function () {
    return view('public.register-company');
})->name('register.company');

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================

Route::middleware(['web.auth'])->group(function () {

    Route::get('/verify-email', function () {
        return view('public.verify-email');
    })->name('verify.email');

    Route::prefix('onboarding')->group(function () {
        Route::get('/profile', function () {
            return view('authenticated.onboarding.complete-profile');
        })->name('onboarding.profile');

        Route::get('/preferences', function () {
            return view('authenticated.onboarding.configure-preferences');
        })->name('onboarding.preferences');
    });

    Route::get('/role-selector', function () {
        return view('authenticated.role-selector');
    })->name('role.selector');

    Route::get('/dashboard', function () {
        return view('user.dashboard');
    })->name('dashboard');

    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('admin.dashboard');

        Route::get('/users', function () {
            return view('admin.users');
        })->name('admin.users');

        Route::get('/users/create', function () {
            return view('admin.users.create');
        })->name('admin.users.create');

        Route::get('/users/{id}', function (string $id) {
            return view('admin.users.show', ['userId' => $id]);
        })->name('admin.users.show');

        Route::get('/users/{id}/edit', function (string $id) {
            return view('admin.users.edit', ['userId' => $id]);
        })->name('admin.users.edit');

        Route::get('/companies', function () {
            return view('admin.companies');
        })->name('admin.companies');

        Route::get('/companies/create', function () {
            return view('admin.companies.create');
        })->name('admin.companies.create');

        Route::get('/companies/{id}', function (string $id) {
            return view('admin.companies.show', ['companyId' => $id]);
        })->name('admin.companies.show');

        Route::get('/companies/{id}/edit', function (string $id) {
            return view('admin.companies.edit', ['companyId' => $id]);
        })->name('admin.companies.edit');

        Route::get('/settings', function () {
            return view('admin.settings');
        })->name('admin.settings');

        Route::get('/audit-logs', function () {
            return view('admin.audit-logs');
        })->name('admin.audit.logs');
    });

    Route::prefix('empresa')->group(function () {
        Route::get('/dashboard', function () {
            return view('company.dashboard');
        })->name('company.dashboard');

        Route::get('/tickets', function () {
            return view('company.tickets');
        })->name('company.tickets');

        Route::get('/tickets/{id}', function (string $id) {
            return view('company.tickets.show', ['ticketId' => $id]);
        })->name('company.tickets.show');

        Route::get('/team', function () {
            return view('company.team');
        })->name('company.team');

        Route::get('/team/invite', function () {
            return view('company.team.invite');
        })->name('company.team.invite');

        Route::get('/settings', function () {
            return view('company.settings');
        })->name('company.settings');

        Route::get('/profile', function () {
            return view('company.profile');
        })->name('company.profile');
    });

    Route::prefix('agent')->group(function () {
        Route::get('/dashboard', function () {
            return view('agent.dashboard');
        })->name('agent.dashboard');

        Route::get('/tickets', function () {
            return view('agent.tickets');
        })->name('agent.tickets');

        Route::get('/tickets/{id}', function (string $id) {
            return view('agent.tickets.show', ['ticketId' => $id]);
        })->name('agent.tickets.show');

        Route::get('/knowledge-base', function () {
            return view('agent.knowledge-base');
        })->name('agent.knowledge.base');
    });

    Route::prefix('tickets')->group(function () {
        Route::get('/', function () {
            return view('user.dashboard');
        })->name('tickets.index');

        Route::get('/create', function () {
            return view('tickets.create');
        })->name('tickets.create');

        Route::get('/{id}', function (string $id) {
            return view('tickets.show', ['ticketId' => $id]);
        })->name('tickets.show');

        Route::get('/{id}/edit', function (string $id) {
            return view('tickets.edit', ['ticketId' => $id]);
        })->name('tickets.edit');
    });

    Route::prefix('profile')->group(function () {
        Route::get('/', function () {
            return view('profile.index');
        })->name('profile.index');

        Route::get('/edit', function () {
            return view('profile.edit');
        })->name('profile.edit');

        Route::get('/security', function () {
            return view('profile.security');
        })->name('profile.security');

        Route::get('/notifications', function () {
            return view('profile.notifications');
        })->name('profile.notifications');

        Route::get('/sessions', function () {
            return view('profile.sessions');
        })->name('profile.sessions');
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', function () {
            return view('notifications.index');
        })->name('notifications.index');

        Route::get('/{id}', function (string $id) {
            return view('notifications.show', ['notificationId' => $id]);
        })->name('notifications.show');
    });

    Route::get('/search', function () {
        return view('search.index', [
            'query' => request()->query('q', ''),
        ]);
    })->name('search');
});
