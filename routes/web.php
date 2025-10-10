<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home', [
        'mensaje' => 'Helpdesk frontend PROXIMAMENTE!!!'
    ]);
});

Route::get('/verify-email', function (Request $request) {
    return Inertia::render('VerifyEmail', [
        'token' => $request->query('token'),
        'userId' => $request->query('user_id'),
    ]);
});
