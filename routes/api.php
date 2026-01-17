<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HechoController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\LesionadoController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\GruaController;

/*
|--------------------------------------------------------------------------
| API Pública
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| API Protegida (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // --- AUTH ---
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get ('/me',     [AuthController::class, 'me']);

    // --- GRÚAS (para dropdown en Android) ---
    // GET /api/gruas => { data: [ {id, nombre}, ... ] }
    Route::get('/gruas', [GruaController::class, 'index']);

    // --- HECHOS ---
    Route::get   ('/hechos',         [HechoController::class, 'index']);
    Route::post  ('/hechos',         [HechoController::class, 'store']);
    Route::get   ('/hechos/{hecho}', [HechoController::class, 'show']);
    Route::put   ('/hechos/{hecho}', [HechoController::class, 'update']);
    Route::delete('/hechos/{hecho}', [HechoController::class, 'destroy']);

    // Descargo / archivo
    Route::post('/hechos/{hecho}/descargo', [HechoController::class, 'subirDescargo']);

    // --- VEHÍCULOS (anidados al hecho) ---
    Route::get   ('/hechos/{hecho}/vehiculos',            [VehiculoController::class, 'index']);
    Route::post  ('/hechos/{hecho}/vehiculos',            [VehiculoController::class, 'store']);
    Route::get   ('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'show']);
    Route::put   ('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'update']);
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'destroy']);

    // --- LESIONADOS (anidados al hecho) ---
    Route::get   ('/hechos/{hecho}/lesionados',             [LesionadoController::class, 'index']);
    Route::post  ('/hechos/{hecho}/lesionados',             [LesionadoController::class, 'store']);
    Route::get   ('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'show']);
    Route::put   ('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'update']);
    Route::delete('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'destroy']);

    // --- UBICACIÓN PATRULLA ---

    Route::post('/location', [LocationController::class, 'store']);
    Route::get('/location/last', [LocationController::class, 'last']);
    Route::get('/users/{user}/location/last', [LocationController::class, 'lastByUser']);
    Route::get('/locations', [LocationController::class, 'index']);
});
