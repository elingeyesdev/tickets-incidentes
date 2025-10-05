<?php
use Illuminate\Support\Facades\Route;

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

// Aquí puedes agregar otras rutas de API si las necesitas
// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
