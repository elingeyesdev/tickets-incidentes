<?php

/**
 * DEPRECATED: This file is replaced by routes/web-jwt-pure.php
 *
 * All web routes now use pure JWT authentication with new middleware:
 * - jwt.auth (replaces 'auth')
 * - jwt.role (replaces 'role')
 * - jwt.onboarding (replaces 'onboarding.completed')
 * - jwt.guest (replaces 'guest')
 *
 * See: routes/web-jwt-pure.php for active routes
 *
 * This file is preserved for reference only.
 * These routes are NO LONGER ACTIVE.
 */

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistema de 3 Zonas con Protección por Middleware (DEPRECATED)
|--------------------------------------------------------------------------
|
| Arquitectura de seguridad:
| - ZONA PÚBLICA: Sin autenticación, redirige si ya está autenticado
| - ZONA ONBOARDING: Autenticado pero sin completar perfil/roles
| - ZONA AUTHENTICATED: Autenticado + onboarding completo + rol específico
|
*/

// ============================================================================
// ZONA 1: PÚBLICA
// Sin autenticación requerida, redirige a dashboard si ya está autenticado
// ============================================================================

Route::middleware(['guest'])->group(function () {
    // Página de bienvenida
    Route::get('/', function () {
        return Inertia::render('Public/Welcome');
    })->name('welcome');

    // Páginas de autenticación
    Route::get('/login', function () {
        return Inertia::render('Public/Login');
    })->name('login');

    Route::get('/register-user', function () {
        return Inertia::render('Public/Register');
    })->name('register-user');

    Route::get('/solicitud-empresa', function () {
        return Inertia::render('Public/RegisterCompany');
    })->name('solicitud-empresa');

    // Alias para compatibilidad
    Route::get('/register', function () {
        return redirect('/solicitud-empresa');
    });

    // Páginas informativas (próximamente)
    Route::get('/forgot-password', function () {
        return Inertia::render('Public/ComingSoon');
    })->name('forgot-password');

    Route::get('/terms', function () {
        return Inertia::render('Public/ComingSoon');
    })->name('terms');

    Route::get('/privacy', function () {
        return Inertia::render('Public/ComingSoon');
    })->name('privacy');
});

// Página de error 403 (accesible para usuarios autenticados)
Route::get('/unauthorized', function () {
    return Inertia::render('Public/Unauthorized');
})->name('unauthorized');

// ============================================================================
// ZONA 2: ONBOARDING
// Autenticado pero sin completar onboarding
// ============================================================================

Route::middleware(['auth'])->group(function () {
    // Verify email (accesible sin onboarding completado)
    Route::get('/verify-email', function (Request $request) {
        return Inertia::render('Public/VerifyEmail', [
            'token' => $request->query('token'),
        ]);
    })->name('verify-email');

    // Onboarding steps
    Route::get('/onboarding/profile', function () {
        return Inertia::render('Authenticated/Onboarding/CompleteProfile');
    })->name('onboarding.profile');

    Route::get('/onboarding/preferences', function () {
        return Inertia::render('Authenticated/Onboarding/ConfigurePreferences');
    })->name('onboarding.preferences');
});

// ============================================================================
// ZONA 3: AUTHENTICATED
// Autenticado + Onboarding completado + Rol específico
// ============================================================================

Route::middleware(['auth', 'onboarding.completed'])->group(function () {

    // Role Selector (accesible para todos los autenticados con onboarding completo)
    Route::get('/role-selector', function () {
        return Inertia::render('Authenticated/RoleSelector');
    })->name('role-selector');

    // ========== USER ROLE ==========
    // Usuarios finales que crean tickets
    Route::middleware(['role:USER'])->group(function () {
        Route::get('/tickets', function () {
            return Inertia::render('User/Dashboard');
        })->name('tickets.index');

        // Futuras rutas USER:
        // Route::get('/create', ...)->name('create');
        // Route::get('/{id}', ...)->name('show');
    });

    // ========== AGENT ROLE ==========
    // Agentes que responden tickets
    Route::middleware(['role:AGENT'])->prefix('agent')->name('agent.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Agent/Dashboard');
        })->name('dashboard');

        // Futuras rutas AGENT:
        // Route::get('/tickets', ...)->name('tickets.index');
        // Route::get('/tickets/{id}', ...)->name('tickets.show');
        // Route::get('/knowledge', ...)->name('knowledge');
    });

    // ========== COMPANY_ADMIN ROLE ==========
    // Administradores de empresa que gestionan agentes y configuración
    Route::middleware(['role:COMPANY_ADMIN'])->prefix('empresa')->name('empresa.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('CompanyAdmin/Dashboard');
        })->name('dashboard');

        // Futuras rutas COMPANY_ADMIN:
        // Route::get('/agents', ...)->name('agents.index');
        // Route::get('/agents/invite', ...)->name('agents.invite');
        // Route::get('/settings', ...)->name('settings');
        // Route::get('/categories', ...)->name('categories');
    });

    // ========== PLATFORM_ADMIN ROLE ==========
    // Administradores de plataforma (super admin)
    Route::middleware(['role:PLATFORM_ADMIN'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('PlatformAdmin/Dashboard');
        })->name('dashboard');

        // Futuras rutas PLATFORM_ADMIN:
        // Route::get('/companies', ...)->name('companies.index');
        // Route::get('/companies/{id}', ...)->name('companies.show');
        // Route::get('/users', ...)->name('users.index');
        // Route::get('/requests', ...)->name('requests.index');
    });
});

// ============================================================================
// FALLBACK
// Cualquier ruta no definida muestra "Próximamente"
// ============================================================================

Route::fallback(function () {
    return Inertia::render('Public/ComingSoon');
});
