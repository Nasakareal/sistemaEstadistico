<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HechoController;

// Login y autenticación
Route::post('/login', [AuthController::class, 'login']);

// Proteger las rutas con Sanctum o Passport (recomiendo Sanctum por simplicidad)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/hechos', [HechoController::class, 'index']);
    Route::post('/hechos', [HechoController::class, 'store']);
    Route::get('/hechos/{id}', [HechoController::class, 'show']);
    Route::put('/hechos/{id}', [HechoController::class, 'update']);
    Route::post('/hechos/{id}/descargo', [HechoController::class, 'subirDescargo']);
});
