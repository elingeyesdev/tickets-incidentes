<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PlatformAdminController;
use App\Http\Controllers\Dashboard\CompanyAdminController;
use App\Http\Controllers\Dashboard\AgentController;
use App\Http\Controllers\Dashboard\UserController;
use App\Features\CompanyManagement\Http\Controllers\CompanyRequestAdminController;
use App\Shared\Helpers\JWTHelper;

// ========== TESTING ROUTES (Development Only) ==========
// Remove these routes in production

Route::prefix('test')->group(function () {
    // Alpine.js test
    Route::get('/alpine-test', function () {
        return view('test.alpine-test');
    })->name('test.alpine-test');

    // JWT System testing
    Route::get('/jwt-interactive', function () {
        return view('test.jwt-interactive');
    })->name('test.jwt-interactive');
});

// Visual Examples / Communication Lab (NO AUTHENTICATION REQUIRED)
// CURRENTLY SHOWING: Ticket List Comparison (USER vs AGENT/ADMIN)
Route::get('/tests', function () {
    return view('tests.experiments.ticket-list-comparison');
})->name('tests.index');

// ========== PUBLIC ROUTES ==========

// Welcome / Landing page
Route::get('/', function () {
    return view('public.welcome');
})->name('welcome');

Route::get('/welcome', function () {
    return view('public.welcome');
});

// Company Request / Solicitud de Empresa
Route::get('/solicitud-empresa', function () {
    return view('public.company-request');
})->name('company.request');

// ========== AUTHENTICATION ROUTES (Blade) ==========

// Public auth pages
Route::get('/login', function () {
    return view('public.login');
})->name('login');

Route::get('/register', function () {
    return view('public.register');
})->name('register');

Route::get('/forgot-password', function () {
    return view('public.forgot-password');
})->name('password.request');

// Email verification (requires authentication)
Route::get('/verify-email', function () {
    return view('public.verify-email');
})->middleware('jwt.require')->name('verification.notice');

// Confirm password (requires authentication)
Route::get('/confirm-password', function () {
    return view('public.confirm-password');
})->middleware('jwt.require')->name('password.confirm');

// ========== PROTECTED ROUTES (Requires JWT) ==========

Route::middleware('jwt.require')->group(function () {
    // Legacy dashboard route (kept for backward compatibility)
    Route::get('/profile', function () {
        return view('app.profile.index');
    })->name('profile');

    // User Profile Page
    Route::get('/app/profile', function () {
        return view('app.profile.index');
    })->name('app.profile');
});

// ========== AUTH-FLOW ROUTES (Role Selection, Onboarding) ==========

// Prepare web route - establishes JWT cookie and redirects
Route::get('/auth/prepare-web', function () {
    $token = request()->query('token');
    $redirect = request()->query('redirect', '/app/dashboard');

    if (!$token) {
        return redirect('/login')->with('error', 'Token no proporcionado');
    }

    try {
        // Validate token on backend to ensure it's valid
        $tokenService = app(App\Features\Authentication\Services\TokenService::class);
        $payload = $tokenService->validateAccessToken($token);

        // Token is valid, establish JWT cookie and redirect
        return redirect($redirect)
            ->cookie('jwt_token', $token, 60, '/', null, false, true); // 60 minutes, HttpOnly, Lax
    } catch (\Exception $e) {
        return redirect('/login')->with('error', 'Token inválido: ' . $e->getMessage());
    }
})->name('auth.prepare-web');

Route::middleware('jwt.require')->prefix('auth-flow')->group(function () {
    // Role selector - shown when user has multiple roles and needs to select one
    Route::get('/role-selector', function () {
        return view('auth-flow.role-selector');
    })->name('auth-flow.role-selector');
});

// ========== DASHBOARD ROUTES (Role-Based) ==========

Route::middleware('jwt.require')->prefix('app')->group(function () {
    // Fallback dashboard redirect (if frontend doesn't handle role detection)
    // Normally frontend redirects directly to role-specific dashboard
    Route::get('/dashboard', [DashboardController::class, 'redirect'])->name('dashboard');

    // Platform Admin Dashboard (PLATFORM_ADMIN role)
    Route::middleware('role:PLATFORM_ADMIN')->prefix('admin')->group(function () {
        Route::get('/dashboard', [PlatformAdminController::class, 'dashboard'])
            ->name('dashboard.platform-admin');

        // Company Requests Management
        Route::get('/company-requests', [CompanyRequestAdminController::class, 'index'])
            ->name('admin.requests.index');

        // Companies Management
        Route::get('/companies', function () {
            return view('app.platform-admin.companies.index');
        })->name('admin.companies.index');

        // Users Management
        Route::get('/users', function () {
            return view('app.platform-admin.users.index');
        })->name('admin.users.index');
    });

    // Company Admin Dashboard (COMPANY_ADMIN role)
    Route::middleware('role:COMPANY_ADMIN')->prefix('company')->group(function () {
        Route::get('/dashboard', [CompanyAdminController::class, 'dashboard'])
            ->name('dashboard.company-admin');

        // Categories Management
        Route::get('/categories', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.categories.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.categories.index');

        // Settings (Company Configuration)
        Route::get('/settings', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.settings.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.settings.index');

        // Articles Management (Help Center)
        Route::get('/articles', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.articles.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.articles.index');

        // Announcements Management
        Route::get('/announcements', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.announcements.index', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.announcements.index');

        Route::get('/announcements/manage', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.company-admin.announcements.manage', [
                'user' => $user,
                'companyId' => $companyId
            ]);
        })->name('company.announcements.manage');

        // Tickets Management (Company Admin)
        Route::get('/tickets', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.shared.tickets.index', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'COMPANY_ADMIN'
            ]);
        })->name('company.tickets.index');

        Route::get('/tickets/manage', function () {
            $user = JWTHelper::getAuthenticatedUser();
            $companyId = JWTHelper::getCompanyIdFromJWT('COMPANY_ADMIN');
            return view('app.shared.tickets.manage', [
                'user' => $user,
                'companyId' => $companyId,
                'role' => 'COMPANY_ADMIN'
            ]);
        })->name('company.tickets.manage');
    });

    // Agent Dashboard (AGENT role)
    Route::middleware('role:AGENT')->prefix('agent')->group(function () {
        Route::get('/dashboard', [AgentController::class, 'dashboard'])
            ->name('dashboard.agent');

        // Tickets (Agent)
        Route::get('/tickets', function () {
            $user = JWTHelper::getAuthenticatedUser();
            return view('app.shared.tickets.index', [
                'user' => $user,
                'role' => 'AGENT'
            ]);
        })->name('agent.tickets.index');

        Route::get('/tickets/manage', function () {
            $user = JWTHelper::getAuthenticatedUser();
            return view('app.shared.tickets.manage', [
                'user' => $user,
                'role' => 'AGENT'
            ]);
        })->name('agent.tickets.manage');
    });

    // User Dashboard (USER role)
    Route::middleware('role:USER')->prefix('user')->group(function () {
        Route::get('/dashboard', [UserController::class, 'dashboard'])
            ->name('dashboard.user');

        // Tickets (User)
        Route::get('/tickets', function () {
            $user = JWTHelper::getAuthenticatedUser();
            return view('app.shared.tickets.index', [
                'user' => $user,
                'role' => 'USER'
            ]);
        })->name('user.tickets.index');
    });

    // Generic Tickets Routes (will redirect to role-specific route)
    Route::get('/tickets', function () {
        $user = JWTHelper::getAuthenticatedUser();
        $activeRole = $user['activeRole']['code'] ?? null;

        if ($activeRole === 'COMPANY_ADMIN') {
            return redirect()->route('company.tickets.index');
        } elseif ($activeRole === 'AGENT') {
            return redirect()->route('agent.tickets.index');
        } elseif ($activeRole === 'USER') {
            return redirect()->route('user.tickets.index');
        }

        return redirect()->route('dashboard');
    })->name('tickets.index');

    Route::get('/tickets/manage', function () {
        $user = JWTHelper::getAuthenticatedUser();
        $activeRole = $user['activeRole']['code'] ?? null;

        if ($activeRole === 'COMPANY_ADMIN') {
            return redirect()->route('company.tickets.manage');
        } elseif ($activeRole === 'AGENT') {
            return redirect()->route('agent.tickets.manage');
        }

        return redirect()->route('dashboard');
    })->name('tickets.manage');
});

