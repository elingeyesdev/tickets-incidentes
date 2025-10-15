<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

// ================================================================================
// PÁGINAS PÚBLICAS
// ================================================================================

// Página de bienvenida
Route::get('/', function () {
    return Inertia::render('Public/Welcome');
})->name('welcome');

// Páginas de autenticación
Route::get('/login', function () {
    return Inertia::render('Public/Login');
})->name('login');

Route::get('/solicitud-empresa', function () {
    return Inertia::render('Public/RegisterCompany');
})->name('solicitud-empresa');

// Alias para compatibilidad
Route::get('/register', function () {
    return redirect('/solicitud-empresa');
});

Route::get('/register-user', function () {
    return Inertia::render('Public/Register');
})->name('register-user');

Route::get('/verify-email', function (Request $request) {
    return Inertia::render('Public/VerifyEmail', [
        'token' => $request->query('token'),
    ]);
})->name('verify-email');

// Onboarding para nuevos usuarios (requiere autenticación)
Route::get('/onboarding/profile', function () {
    return Inertia::render('Authenticated/Onboarding/CompleteProfile');
})->name('onboarding.profile');

Route::get('/onboarding/preferences', function () {
    return Inertia::render('Authenticated/Onboarding/ConfigurePreferences');
})->name('onboarding.preferences');

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

// ================================================================================
// ZONA AUTENTICADA - Dashboards por rol
// ================================================================================

// USER Dashboard (ruta principal: /tickets)
Route::get('/tickets', function () {
    return Inertia::render('User/Dashboard');
})->name('tickets');

// AGENT Dashboard  
Route::get('/agent/dashboard', function () {
    return Inertia::render('Agent/Dashboard');
})->name('agent.dashboard');

// COMPANY_ADMIN Dashboard
Route::get('/empresa/dashboard', function () {
    return Inertia::render('CompanyAdmin/Dashboard');
})->name('empresa.dashboard');

// PLATFORM_ADMIN Dashboard
Route::get('/platform/dashboard', function () {
    return Inertia::render('PlatformAdmin/Dashboard');
})->name('platform.dashboard');

// Role Selector - ZONA AUTENTICADA (requiere tokens válidos)
Route::get('/role-selector', function () {
    return Inertia::render('Authenticated/RoleSelector');
})->name('role-selector');

// ================================================================================
// FALLBACK - Cualquier ruta no definida muestra "Próximamente"
// ================================================================================

Route::fallback(function () {
    return Inertia::render('Public/ComingSoon');
});
