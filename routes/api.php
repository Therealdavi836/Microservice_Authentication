<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por token
Route::middleware('auth:sanctum')->group(function() {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Usuarios solo admin
    Route::middleware('checkrole:admin')->get('/users', [UserController::class, 'index']);

    // Gateway genérico para microservicios (endpoint opcional y wildcard)
    Route::match(['get', 'post', 'put', 'delete', 'patch'],
        '/forward/{service}/{endpoint?}', [AuthController::class, 'forward'])
        ->where('endpoint', '.*');
});
