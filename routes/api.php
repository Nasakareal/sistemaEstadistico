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
use App\Http\Controllers\Api\DictamenController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Api\ActividadController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\MapaIncidenciasController;
use App\Http\Controllers\Api\PendientesCortesController;
use App\Http\Controllers\WhatsAppWebhookController;

Route::get('/whatsapp/webhook', [WhatsAppWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsAppWebhookController::class, 'handle']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/home', [DashboardController::class, 'home'])->name('api.home');
    Route::get('/home/perito', [DashboardController::class, 'homePerito'])->middleware('can:ver home perito')->name('api.home.perito');


    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/permissions', [AuthController::class, 'permissions']);

    Route::get('/feed', [FeedController::class, 'index'])->name('api.feed.index');

    Route::middleware('can:ver mapa')->group(function () {
        Route::get('/mapa-incidencias', [MapaIncidenciasController::class, 'index'])->name('api.mapa.incidencias.index');
        Route::get('/mapa-incidencias/data', [MapaIncidenciasController::class, 'data'])->name('api.mapa.incidencias.data');
    });

    Route::prefix('pendientes')->middleware('can:ver hechos')->group(function () {
        Route::get('/cortes', [PendientesCortesController::class, 'index'])->name('api.hechos.pendientes.cortes.index');
        Route::get('/cortes/{corte}', [PendientesCortesController::class, 'show'])->name('api.hechos.pendientes.cortes.show');
    });

    Route::prefix('actividades')->group(function () {
        Route::get('/categorias', [ActividadController::class, 'categorias'])->name('api.actividades.categorias');
        Route::get('/subcategorias/{categoria}', [ActividadController::class, 'subcategorias'])->name('api.actividades.subcategorias');
        Route::get('/', [ActividadController::class, 'index'])->name('api.actividades.index');
        Route::post('/', [ActividadController::class, 'store'])->middleware('can:crear actividades')->name('api.actividades.store');
        Route::get('/{actividad}', [ActividadController::class, 'show'])->name('api.actividades.show');
        Route::put('/{actividad}', [ActividadController::class, 'update'])->middleware('can:editar actividades')->name('api.actividades.update');
        Route::delete('/{actividad}', [ActividadController::class, 'destroy'])->middleware('can:eliminar actividades')->name('api.actividades.destroy');
    });

    Route::get('/alerts', [AlertController::class, 'index']);
    Route::post('/alerts/{alert}/read', [AlertController::class, 'markRead']);
    Route::post('/alerts/read-all', [AlertController::class, 'markReadAll']);

    Route::get('/dictamenes/buscar', [DictamenController::class, 'buscar'])->middleware('can:ver dictamenes');
    Route::get('/dictamenes', [DictamenController::class, 'index'])->middleware('can:ver dictamenes');
    Route::post('/dictamenes', [DictamenController::class, 'store'])->middleware('can:crear dictamenes');
    Route::get('/dictamenes/{dictamen}', [DictamenController::class, 'show'])->middleware('can:ver dictamenes');
    Route::put('/dictamenes/{dictamen}', [DictamenController::class, 'update'])->middleware('can:editar dictamenes');
    Route::delete('/dictamenes/{dictamen}', [DictamenController::class, 'destroy'])->middleware('can:eliminar dictamenes');

    Route::get('/gruas', [GruaController::class, 'index'])->middleware('can:ver gruas');
    Route::get('/gruas/listado', [GruaController::class, 'listado'])->middleware('can:ver gruas');
    Route::get('/gruas/grafica-semanal', [GruaController::class, 'graficaSemanal'])->middleware('can:ver estadisticas');
    Route::get('/gruas/resumen-semanal', [GruaController::class, 'resumenSemanal'])->middleware('can:ver estadisticas');
    Route::get('/gruas/resumen-semanal-detallado', [GruaController::class, 'resumenSemanalDetallado'])->middleware('can:ver estadisticas');

    Route::get('/hechos/buscar', [HechoController::class, 'buscar'])->middleware('can:ver hechos');
    Route::get('/hechos', [HechoController::class, 'index'])->middleware('can:ver hechos');
    Route::post('/hechos', [HechoController::class, 'store'])->middleware('can:crear hechos');
    Route::get('/hechos/{hecho}', [HechoController::class, 'show'])->middleware('can:ver hechos');
    Route::put('/hechos/{hecho}', [HechoController::class, 'update'])->middleware('can:editar hechos');
    Route::delete('/hechos/{hecho}', [HechoController::class, 'destroy'])->middleware('can:eliminar hechos');
    Route::post('/hechos/{hecho}/descargo', [HechoController::class, 'subirDescargo'])->middleware('can:editar hechos');

    Route::get('/hechos/{hecho}/vehiculos', [VehiculoController::class, 'index'])->middleware('can:ver vehiculos');
    Route::post('/hechos/{hecho}/vehiculos', [VehiculoController::class, 'store'])->middleware('can:crear vehiculos');
    Route::get('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'show'])->middleware('can:ver vehiculos');
    Route::put('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'update'])->middleware('can:editar vehiculos');
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'destroy'])->middleware('can:eliminar vehiculos');
    Route::get('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'foto'])->middleware('can:editar vehiculos');
    Route::post('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'fotoUpdate'])->middleware('can:editar vehiculos');
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'fotoDestroy'])->middleware('can:editar vehiculos');

    Route::get('/hechos/{hecho}/lesionados', [LesionadoController::class, 'index'])->middleware('can:ver lesionados');
    Route::post('/hechos/{hecho}/lesionados', [LesionadoController::class, 'store'])->middleware('can:crear lesionados');
    Route::get('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'show'])->middleware('can:ver lesionados');
    Route::put('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'update'])->middleware('can:editar lesionados');
    Route::delete('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'destroy'])->middleware('can:eliminar lesionados');
    Route::post('/hechos/{hecho}/whatsapp', [HechoController::class, 'sendWhatsapp'])->middleware('can:ver hechos');

    Route::post('/location', [LocationController::class, 'store']);
    Route::get('/location/last', [LocationController::class, 'last']);
    Route::get('/users/{user}/location/last', [LocationController::class, 'lastByUser']);
    Route::get('/locations', [LocationController::class, 'index']);

    Route::get('/dashboard/accidentes-hoy', [DashboardController::class, 'accidentesHoy'])->middleware('can:ver estadisticas');
    Route::get('/dashboard/gruas-hoy', [DashboardController::class, 'gruasHoy'])->middleware('can:ver estadisticas');

    Route::get('/mapa/patrullas', [MapaPatrullasController::class, 'data'])->middleware('can:ver mapa');

    Route::get('/hechos/{hecho}/reporte-doc', [DocumentoHechoController::class, 'descargarDoc'])->middleware('can:ver hechos');

    Route::prefix('estadisticas-globales')->middleware('can:ver estadisticas globales')->group(function () {
        Route::get('/kpis', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'kpis']);
        Route::get('/series/hechos', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesHechos']);
        Route::get('/series/lesionados', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesLesionados']);
        Route::get('/series/tipo-hecho', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesTipoHecho']);
        Route::get('/series/sector', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesSector']);
        Route::get('/series/municipio', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesMunicipio']);
        Route::get('/series/tiempo', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesTiempo']);
        Route::get('/series/clima', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesClima']);
        Route::get('/series/condiciones', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesCondiciones']);
        Route::get('/series/control-transito', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesControlTransito']);
        Route::get('/series/vehiculos/tipo', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesVehiculosTipo']);
        Route::get('/series/vehiculos/marca', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesVehiculosMarca']);
        Route::get('/series/vehiculos/modelo', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'seriesVehiculosModelo']);
        Route::get('/hechos', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'hechos']);
        Route::get('/export/hechos', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'exportHechos');
    });

    Route::get('/mi-personal', [PersonalController::class, 'index'])->middleware('can:ver personal turno');
    Route::post('/mi-personal/{user}/ubicacion', [PersonalController::class, 'toggleUbicacion'])->middleware('can:gestionar ubicaciones turno');
    Route::post('/mi-personal/ubicacion/todos', [PersonalController::class, 'toggleUbicacionTodos'])->middleware('can:gestionar ubicaciones turno');
    Route::post('/mi-personal/{user}/ubicacion/limpiar', [PersonalController::class, 'limpiarUbicacionUsuario'])->middleware('can:gestionar ubicaciones turno');
    Route::post('/mi-personal/ubicacion/limpiar-todos', [PersonalController::class, 'limpiarUbicacionTodos'])->middleware('can:gestionar ubicaciones turno');

    Route::get('/app/version', [AppVersionController::class, 'show']);
});
