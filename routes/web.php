<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

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

// ========== PUBLIC ROUTES ==========

// Welcome / Landing page
Route::get('/', function () {
    return view('public.welcome');
})->name('welcome');

Route::get('/welcome', function () {
    return view('public.welcome');
});

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
    Route::get('/dashboard', function () {
        return view('app.dashboard');
    })->name('dashboard');

    Route::get('/profile', function () {
        return view('app.profile');
    })->name('profile');
});

