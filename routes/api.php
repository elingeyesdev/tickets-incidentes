<?php
use Illuminate\Support\Facades\Route;
use App\Features\Authentication\Http\Controllers\RefreshTokenController;

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

// ================================================================================
// REST API ENDPOINTS - Authentication
// ================================================================================

// Endpoint REST para renovar access token usando refresh token en cookie HttpOnly
Route::post('/auth/refresh', [RefreshTokenController::class, 'refresh'])
    ->name('auth.refresh');

// Aquí puedes agregar otras rutas de API si las necesitas
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
