<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HechoController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\LesionadoController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\GruaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MapaPatrullasController;
use App\Http\Controllers\Api\DocumentoHechoController;

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
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/gruas', [GruaController::class, 'index']);

    Route::get('/hechos', [HechoController::class, 'index']);
    Route::post('/hechos', [HechoController::class, 'store']);
    Route::get('/hechos/{hecho}', [HechoController::class, 'show']);
    Route::put('/hechos/{hecho}', [HechoController::class, 'update']);
    Route::delete('/hechos/{hecho}', [HechoController::class, 'destroy']);
    Route::post('/hechos/{hecho}/descargo', [HechoController::class, 'subirDescargo']);

    Route::get('/hechos/{hecho}/vehiculos', [VehiculoController::class, 'index']);
    Route::post('/hechos/{hecho}/vehiculos', [VehiculoController::class, 'store']);
    Route::get('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'show']);
    Route::put('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'update']);
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'destroy']);

    Route::get('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'foto']);
    Route::post('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'fotoUpdate']);
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'fotoDestroy']);

    Route::get('/hechos/{hecho}/lesionados', [LesionadoController::class, 'index']);
    Route::post('/hechos/{hecho}/lesionados', [LesionadoController::class, 'store']);
    Route::get('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'show']);
    Route::put('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'update']);
    Route::delete('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'destroy']);

    Route::post('/location', [LocationController::class, 'store']);
    Route::get('/location/last', [LocationController::class, 'last']);
    Route::get('/users/{user}/location/last', [LocationController::class, 'lastByUser']);
    Route::get('/locations', [LocationController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Dashboard 
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard/accidentes-hoy', [DashboardController::class, 'accidentesHoy']);
    Route::get('/dashboard/gruas-hoy', [DashboardController::class, 'gruasHoy']);

    Route::get('/mapa/patrullas', [MapaPatrullasController::class, 'data']);

    Route::get('/hechos/{hecho}/reporte-doc', [DocumentoHechoController::class, 'descargarDoc']);
});
