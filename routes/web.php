<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// ========== TESTING ROUTES (Development Only) ==========
// Remove these routes in production

Route::prefix('test')->group(function () {
    // JWT System testing
    Route::get('/jwt-interactive', function () {
        return view('test.jwt-interactive');
    })->name('test.jwt-interactive');
});

// ========== AUTHENTICATION ROUTES (Blade) ==========
// To be implemented in Phase 3

// Route::post('/login', [\App\Features\Authentication\Http\Controllers\AuthController::class, 'showLoginForm']);
// Route::get('/register', function () { return view('public.register'); });
// Route::get('/dashboard', function () { return view('app.dashboard'); })->middleware('jwt');

