<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentoHechoController;
use App\Http\Controllers\Api\GruaController;
use App\Http\Controllers\Api\HechoController;
use App\Http\Controllers\Api\LesionadoController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MapaPatrullasController;
use App\Http\Controllers\Api\PersonalController;
use App\Http\Controllers\Api\VehiculoController;
use App\Http\Controllers\Api\AppVersionController;

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

    /*
    |--------------------------------------------------------------------------
    | GRÚAS
    |--------------------------------------------------------------------------
    */
    Route::get('/gruas', [GruaController::class, 'index'])->middleware('can:ver gruas');
    Route::get('/gruas/listado', [GruaController::class, 'listado'])->middleware('can:ver gruas');
    Route::get('/gruas/grafica-semanal', [GruaController::class, 'graficaSemanal'])->middleware('can:ver estadisticas');

    /*
    |--------------------------------------------------------------------------
    | HECHOS
    |--------------------------------------------------------------------------
    */
    Route::get('/hechos/buscar', [HechoController::class, 'buscar'])->middleware('can:ver hechos');
    Route::get('/hechos', [HechoController::class, 'index'])->middleware('can:ver hechos');
    Route::post('/hechos', [HechoController::class, 'store'])->middleware('can:crear hechos');
    Route::get('/hechos/{hecho}', [HechoController::class, 'show'])->middleware('can:ver hechos');
    Route::put('/hechos/{hecho}', [HechoController::class, 'update'])->middleware('can:editar hechos');
    Route::delete('/hechos/{hecho}', [HechoController::class, 'destroy'])->middleware('can:eliminar hechos');
    Route::post('/hechos/{hecho}/descargo', [HechoController::class, 'subirDescargo'])->middleware('can:editar hechos');

    /*
    |--------------------------------------------------------------------------
    | VEHÍCULOS (anidados)
    |--------------------------------------------------------------------------
    */
    Route::get('/hechos/{hecho}/vehiculos', [VehiculoController::class, 'index'])->middleware('can:ver vehiculos');
    Route::post('/hechos/{hecho}/vehiculos', [VehiculoController::class, 'store'])->middleware('can:crear vehiculos');
    Route::get('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'show'])->middleware('can:ver vehiculos');
    Route::put('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'update'])->middleware('can:editar vehiculos');
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'destroy'])->middleware('can:eliminar vehiculos');

    Route::get('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'foto'])->middleware('can:editar vehiculos');
    Route::post('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'fotoUpdate'])->middleware('can:editar vehiculos');
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'fotoDestroy'])->middleware('can:editar vehiculos');

    /*
    |--------------------------------------------------------------------------
    | LESIONADOS (anidados)
    |--------------------------------------------------------------------------
    */
    Route::get('/hechos/{hecho}/lesionados', [LesionadoController::class, 'index'])->middleware('can:ver lesionados');
    Route::post('/hechos/{hecho}/lesionados', [LesionadoController::class, 'store'])->middleware('can:crear lesionados');
    Route::get('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'show'])->middleware('can:ver lesionados');
    Route::put('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'update'])->middleware('can:editar lesionados');
    Route::delete('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'destroy'])->middleware('can:eliminar lesionados');

    /*
    |--------------------------------------------------------------------------
    | UBICACIONES
    |--------------------------------------------------------------------------
    */
    Route::post('/location', [LocationController::class, 'store']);
    Route::get('/location/last', [LocationController::class, 'last']);
    Route::get('/users/{user}/location/last', [LocationController::class, 'lastByUser']);
    Route::get('/locations', [LocationController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard/accidentes-hoy', [DashboardController::class, 'accidentesHoy'])->middleware('can:ver estadisticas');
    Route::get('/dashboard/gruas-hoy', [DashboardController::class, 'gruasHoy'])->middleware('can:ver estadisticas');

    /*
    |--------------------------------------------------------------------------
    | MAPA (PATRULLAS)
    |--------------------------------------------------------------------------
    */
    Route::get('/mapa/patrullas', [MapaPatrullasController::class, 'data'])->middleware('can:ver mapa');

    /*
    |--------------------------------------------------------------------------
    | DOCUMENTOS
    |--------------------------------------------------------------------------
    */
    Route::get('/hechos/{hecho}/reporte-doc', [DocumentoHechoController::class, 'descargarDoc'])->middleware('can:ver hechos');

    /*
    |--------------------------------------------------------------------------
    | MI PERSONAL (JEFE TURNO)
    |--------------------------------------------------------------------------
    */
    Route::get('/mi-personal', [PersonalController::class, 'index'])->middleware('can:ver personal turno');
    Route::post('/mi-personal/{user}/ubicacion', [PersonalController::class, 'toggleUbicacion'])->middleware('can:gestionar ubicaciones turno');
    Route::post('/mi-personal/ubicacion/todos', [PersonalController::class, 'toggleUbicacionTodos'])->middleware('can:gestionar ubicaciones turno');
    Route::post('/mi-personal/{user}/ubicacion/limpiar', [PersonalController::class, 'limpiarUbicacionUsuario'])->middleware('can:gestionar ubicaciones turno');
    Route::post('/mi-personal/ubicacion/limpiar-todos', [PersonalController::class, 'limpiarUbicacionTodos'])->middleware('can:gestionar ubicaciones turno');

    /*
    |--------------------------------------------------------------------------
    | VERSION DE LA APP
    |--------------------------------------------------------------------------
    */
    Route::get('/app/version', [AppVersionController::class, 'show']);
});
