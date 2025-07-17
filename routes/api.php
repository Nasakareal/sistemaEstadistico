<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HechoController;
use App\Http\Controllers\Api\VehiculoController;

// Login y autenticación
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas por Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Hechos
    Route::get   ('/hechos',                   [HechoController::class, 'index']);
    Route::post  ('/hechos',                   [HechoController::class, 'store']);
    Route::get   ('/hechos/{id}',              [HechoController::class, 'show']);
    Route::put   ('/hechos/{id}',              [HechoController::class, 'update']);
    Route::post  ('/hechos/{id}/descargo',     [HechoController::class, 'subirDescargo']);
    Route::delete('/hechos/{id}',              [HechoController::class, 'destroy']);

    // Vehículos anidados en cada hecho
    Route::get   ('/hechos/{hecho}/vehiculos',                     [VehiculoController::class, 'index']);
    Route::post  ('/hechos/{hecho}/vehiculos',                     [VehiculoController::class, 'store']);
    Route::get   ('/hechos/{hecho}/vehiculos/{vehiculo}',          [VehiculoController::class, 'show']);
    Route::put   ('/hechos/{hecho}/vehiculos/{vehiculo}',          [VehiculoController::class, 'update']);
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}',          [VehiculoController::class, 'destroy']);
});
