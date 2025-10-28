<?php
use Illuminate\Support\Facades\Route;
use App\Features\Authentication\Http\Controllers\AuthController;
use App\Features\Authentication\Http\Controllers\RefreshTokenController;
use App\Features\Authentication\Http\Controllers\HealthController;
use App\Features\Authentication\Http\Controllers\PasswordResetController;
use App\Features\Authentication\Http\Controllers\EmailVerificationController;
use App\Features\Authentication\Http\Controllers\SessionController;
use App\Features\Authentication\Http\Controllers\OnboardingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí se registran las rutas para tu API. Estas rutas son cargadas
| por el RouteServiceProvider dentro del grupo "api" con el prefijo /api
|
*/

// Health check para el balanceador de cargas - GraphQL
Route::get('healthgraphql', function () {
    return response('OK', 200)
        ->header('Content-Type', 'text/plain');
});

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
