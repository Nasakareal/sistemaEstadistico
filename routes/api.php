<?php

use Illuminate\Http\Request;
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
use App\Http\Controllers\Api\WhatsAppWebhookController;
use App\Http\Controllers\Api\C5IInboundController;
use App\Http\Controllers\Api\BotC5IController;
use App\Http\Controllers\Api\WabotIncomingController;
use App\Http\Controllers\Api\WazeFeedController;
use App\Http\Controllers\Api\PeritoHomeController;
use App\Http\Controllers\Api\GuardianesCaminoController as ApiGuardianesCaminoController;
use App\Http\Controllers\Api\GuardianesCaminoDispositivoController as ApiGuardianesCaminoDispositivoController;
use App\Http\Controllers\Api\AgenteUpecHomeController;
use App\Http\Controllers\Api\PuestaDisposicionController;
use App\Http\Controllers\Api\CroquisController;
use App\Http\Controllers\Api\CulturaVialController;
use App\Http\Controllers\Api\ChoquesDiariosController;
use App\Http\Controllers\Api\ConstanciaManejoController as ApiConstanciaManejoController;
use App\Http\Controllers\Api\ModuloExamenDiarioController as ApiModuloExamenDiarioController;

Route::post('/wabot/incoming',[WabotIncomingController::class,'handle']);
Route::post('/bot/c5i/reco',[BotC5IController::class,'recommend']);
Route::post('/c5i/report',[C5IInboundController::class,'handle']);
Route::get('/whatsapp/webhook',[WhatsAppWebhookController::class,'verify']);
Route::post('/whatsapp/webhook',[WhatsAppWebhookController::class,'handle']);

Route::post('/login', [AuthController::class, 'login']);

Route::get('/waze/incidents', [WazeFeedController::class, 'incidents']);

Route::prefix('choques-diarios')->group(function () {
    Route::get('/', [ChoquesDiariosController::class, 'index'])->name('api.choques_diarios.index');
    Route::get('/fecha/{fecha}', [ChoquesDiariosController::class, 'porFecha'])->where('fecha', '[0-9]{4}-[0-9]{2}-[0-9]{2}')->name('api.choques_diarios.fecha');
    Route::get('/hoy', [ChoquesDiariosController::class, 'hoy'])->name('api.choques_diarios.hoy');
    Route::get('/rango', [ChoquesDiariosController::class, 'rango'])->name('api.choques_diarios.rango');
    Route::get('/{hecho}', [ChoquesDiariosController::class, 'show'])->whereNumber('hecho')->name('api.choques_diarios.show');
    Route::get('/eliminados/fecha/{fecha}', [ChoquesDiariosController::class, 'eliminadosPorFecha'])->where('fecha', '[0-9]{4}-[0-9]{2}-[0-9]{2}')->name('api.choques_diarios.eliminados.fecha');
    Route::get('/eliminados/rango', [ChoquesDiariosController::class, 'eliminadosRango'])->name('api.choques_diarios.eliminados.rango');
});

Route::prefix('cultura-vial/public')->group(function () {
    Route::get('/salas/{codigo}', [CulturaVialController::class, 'publicRoom'])->name('api.cultura_vial.public.salas.show');
    Route::post('/salas/{codigo}/participantes', [CulturaVialController::class, 'join'])->name('api.cultura_vial.public.participantes.store');
    Route::post('/participantes/{participante}/intentos', [CulturaVialController::class, 'storeAttempt'])->whereNumber('participante')->name('api.cultura_vial.public.intentos.store');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('perito-home')->middleware(['role:Perito', 'unidad:siniestros'])->group(function () {
        Route::get('/mapa', [PeritoHomeController::class, 'mapa'])->name('api.perito_home.mapa');
        Route::get('/filtros', [PeritoHomeController::class, 'filtros'])->name('api.perito_home.filtros');
        Route::get('/hechos/{hecho}', [PeritoHomeController::class, 'show'])->name('api.perito_home.hechos.show');
    });

    Route::prefix('agente-upec-home')->middleware(['role:Agente Upec'])->group(function () {
        Route::get('/mapa', [AgenteUpecHomeController::class, 'mapa'])->name('api.agente_upec_home.mapa');
        Route::get('/filtros', [AgenteUpecHomeController::class, 'filtros'])->name('api.agente_upec_home.filtros');
        Route::get('/alertas/{id}', [AgenteUpecHomeController::class, 'show'])->name('api.agente_upec_home.alertas.show');
    });

    Route::prefix('constancias-manejo')->middleware('can:ver modulo examenes')->group(function () {
        Route::get('/', [ApiConstanciaManejoController::class, 'index'])->name('api.constancias_manejo.index');
        Route::get('/modulos', [ApiConstanciaManejoController::class, 'modulos'])->name('api.constancias_manejo.modulos');
        Route::post('/', [ApiConstanciaManejoController::class, 'store'])->middleware('can:crear modulo examenes')->name('api.constancias_manejo.store');
        Route::get('/qr/{token}', [ApiConstanciaManejoController::class, 'buscarPorQr'])->name('api.constancias_manejo.qr');
        Route::get('/{constancia}', [ApiConstanciaManejoController::class, 'show'])->whereNumber('constancia')->name('api.constancias_manejo.show');
        Route::get('/{constancia}/acceso-qr', [ApiConstanciaManejoController::class, 'accesoQr'])->whereNumber('constancia')->name('api.constancias_manejo.acceso_qr');
        Route::post('/{constancia}/generar-acceso', [ApiConstanciaManejoController::class, 'generarAcceso'])->middleware('can:editar modulo examenes')->whereNumber('constancia')->name('api.constancias_manejo.generar_acceso');
        Route::post('/{constancia}/capturar-impreso', [ApiConstanciaManejoController::class, 'capturarImpreso'])->middleware('can:editar modulo examenes')->whereNumber('constancia')->name('api.constancias_manejo.capturar_impreso');
        Route::post('/{constancia}/activar', [ApiConstanciaManejoController::class, 'activar'])->middleware('can:editar modulo examenes')->whereNumber('constancia')->name('api.constancias_manejo.activar');
        Route::post('/{constancia}/cancelar-acceso', [ApiConstanciaManejoController::class, 'cancelarAcceso'])->middleware('can:editar modulo examenes')->whereNumber('constancia')->name('api.constancias_manejo.cancelar_acceso');
    });

    Route::prefix('modulo-examenes-diarios')->middleware('can:ver modulo examenes')->group(function () {
        Route::get('/', [ApiModuloExamenDiarioController::class, 'index'])->name('api.modulo_examenes_diarios.index');
        Route::post('/', [ApiModuloExamenDiarioController::class, 'store'])->middleware('can:crear modulo examenes')->name('api.modulo_examenes_diarios.store');
        Route::get('/{registro}', [ApiModuloExamenDiarioController::class, 'show'])->whereNumber('registro')->name('api.modulo_examenes_diarios.show');
        Route::put('/{registro}', [ApiModuloExamenDiarioController::class, 'update'])->whereNumber('registro')->middleware('can:editar modulo examenes')->name('api.modulo_examenes_diarios.update');
        Route::delete('/{registro}', [ApiModuloExamenDiarioController::class, 'destroy'])->whereNumber('registro')->middleware('can:eliminar modulo examenes')->name('api.modulo_examenes_diarios.destroy');
    });

    Route::prefix('guardianes-camino')->middleware(['unidad:carreteras'])->group(function () {
        Route::get('/', [ApiGuardianesCaminoController::class, 'index'])->name('api.guardianes_camino.index');
        Route::get('/resumen', [ApiGuardianesCaminoController::class, 'resumen'])->name('api.guardianes_camino.resumen');
        Route::get('/whatsapp', [ApiGuardianesCaminoController::class, 'whatsapp'])->name('api.guardianes_camino.whatsapp');

        Route::prefix('dispositivos')->group(function () {
            Route::get('/create', [ApiGuardianesCaminoDispositivoController::class, 'create'])->name('api.guardianes_camino.dispositivos.create');
            Route::get('/', [ApiGuardianesCaminoDispositivoController::class, 'index'])->name('api.guardianes_camino.dispositivos.index');
            Route::post('/', [ApiGuardianesCaminoDispositivoController::class, 'store'])->name('api.guardianes_camino.dispositivos.store');
            Route::post('/fotos', [ApiGuardianesCaminoDispositivoController::class, 'storeFotos'])->name('api.guardianes_camino.dispositivos.fotos.store_por_cliente');
            Route::post('/relacionados', [ApiGuardianesCaminoDispositivoController::class, 'storeRelacionados'])->name('api.guardianes_camino.dispositivos.relacionados.store_por_cliente');
            Route::get('/pendientes-revision', [ApiGuardianesCaminoDispositivoController::class, 'pendientesRevision'])->middleware('can:editar operativos carreteras')->name('api.guardianes_camino.dispositivos.pendientes_revision');
            Route::get('/count-pendientes-revision', [ApiGuardianesCaminoDispositivoController::class, 'countPendientesRevision'])->middleware('can:editar operativos carreteras')->name('api.guardianes_camino.dispositivos.count_pendientes_revision');
            Route::post('/{dispositivo}/fotos', [ApiGuardianesCaminoDispositivoController::class, 'storeFotos'])->name('api.guardianes_camino.dispositivos.fotos.store');
            Route::post('/{dispositivo}/relacionados', [ApiGuardianesCaminoDispositivoController::class, 'storeRelacionados'])->name('api.guardianes_camino.dispositivos.relacionados.store');
            Route::post('/{dispositivo}/aprobar-revision', [ApiGuardianesCaminoDispositivoController::class, 'aprobarRevision'])->middleware('can:editar operativos carreteras')->name('api.guardianes_camino.dispositivos.aprobar_revision');
            Route::post('/{dispositivo}/rechazar-revision', [ApiGuardianesCaminoDispositivoController::class, 'rechazarRevision'])->middleware('can:editar operativos carreteras')->name('api.guardianes_camino.dispositivos.rechazar_revision');
            Route::get('/{dispositivo}', [ApiGuardianesCaminoDispositivoController::class, 'show'])->name('api.guardianes_camino.dispositivos.show');
            Route::put('/{dispositivo}', [ApiGuardianesCaminoDispositivoController::class, 'update'])->middleware('can:editar operativos carreteras')->name('api.guardianes_camino.dispositivos.update');
            Route::delete('/{dispositivo}', [ApiGuardianesCaminoDispositivoController::class, 'destroy'])->middleware('can:eliminar operativos carreteras')->name('api.guardianes_camino.dispositivos.destroy');
            Route::get('/{dispositivo}/whatsapp', [ApiGuardianesCaminoDispositivoController::class, 'whatsapp'])->name('api.guardianes_camino.dispositivos.whatsapp');
        });
    });


    Route::get('/home', [DashboardController::class, 'home'])->name('api.home');
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);
    Route::get('/permissions', [AuthController::class, 'permissions']);
    Route::get('/feed', [FeedController::class, 'index'])->name('api.feed.index');

    Route::prefix('cultura-vial')->middleware(['unidad:cultura-vial'])->group(function () {
        Route::get('/salas', [CulturaVialController::class, 'index'])->name('api.cultura_vial.salas.index');
        Route::post('/salas', [CulturaVialController::class, 'store'])->name('api.cultura_vial.salas.store');
        Route::get('/salas/{sala}', [CulturaVialController::class, 'show'])->whereNumber('sala')->name('api.cultura_vial.salas.show');
        Route::post('/salas/{sala}/cerrar', [CulturaVialController::class, 'close'])->whereNumber('sala')->name('api.cultura_vial.salas.close');
        Route::get('/salas/{sala}/qr', [CulturaVialController::class, 'qr'])->whereNumber('sala')->name('api.cultura_vial.salas.qr');
    });

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
        Route::get('/subcategorias/{categoria}', [ActividadController::class, 'subcategorias'])->whereNumber('categoria')->name('api.actividades.subcategorias');
        Route::get('/informe/diario', [ActividadController::class, 'informeDiario'])->name('api.actividades.informe.diario');
        Route::get('/informe/fecha/{fecha}', [ActividadController::class, 'informeFecha'])->name('api.actividades.informe.fecha');
        Route::get('/compartir-totales-whatsapp', [ActividadController::class, 'compartirTotalesWhatsapp'])->name('api.actividades.compartir_totales');
        Route::get('/{actividad}/compartir', [ActividadController::class, 'compartir'])->whereNumber('actividad')->name('api.actividades.compartir');
        Route::get('/', [ActividadController::class, 'index'])->name('api.actividades.index');
        Route::post('/', [ActividadController::class, 'store'])->middleware('can:crear actividades')->name('api.actividades.store');
        Route::get('/{actividad}', [ActividadController::class, 'show'])->whereNumber('actividad')->name('api.actividades.show');
        Route::put('/{actividad}', [ActividadController::class, 'update'])->whereNumber('actividad')->middleware('can:editar actividades')->name('api.actividades.update');
        Route::delete('/{actividad}', [ActividadController::class, 'destroy'])->whereNumber('actividad')->middleware('can:eliminar actividades')->name('api.actividades.destroy');
        Route::post('/{actividad}/vehiculos', [ActividadController::class, 'storeVehiculo'])->whereNumber('actividad')->middleware('can:editar actividades')->name('api.actividades.vehiculos.store');
        Route::delete('/{actividad}/vehiculos/{vehiculo}', [ActividadController::class, 'destroyVehiculo'])->whereNumber('actividad')->whereNumber('vehiculo')->middleware('can:editar actividades')->name('api.actividades.vehiculos.destroy');
    });

    Route::prefix('vialidades-urbanas')->middleware(['can:ver operativos vialidades'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VialidadesUrbanasController::class, 'index'])->name('api.vialidades_urbanas.index');
        Route::post('/', [\App\Http\Controllers\Api\VialidadesUrbanasController::class, 'store'])->middleware('can:crear operativos vialidades')->name('api.vialidades_urbanas.store');
        Route::get('/{vialidadUrbana}', [\App\Http\Controllers\Api\VialidadesUrbanasController::class, 'show'])->name('api.vialidades_urbanas.show');
        Route::put('/{vialidadUrbana}', [\App\Http\Controllers\Api\VialidadesUrbanasController::class, 'update'])->middleware('can:editar operativos vialidades')->name('api.vialidades_urbanas.update');
        Route::delete('/{vialidadUrbana}', [\App\Http\Controllers\Api\VialidadesUrbanasController::class, 'destroy'])->middleware('can:eliminar operativos vialidades')->name('api.vialidades_urbanas.destroy');

        Route::get('/{vialidadUrbana}/resumen', [\App\Http\Controllers\Api\VialidadesUrbanasController::class, 'resumen'])->name('api.vialidades_urbanas.resumen');
        Route::get('/{vialidadUrbana}/whatsapp', [\App\Http\Controllers\Api\VialidadesUrbanasController::class, 'whatsapp'])->name('api.vialidades_urbanas.whatsapp');

        Route::prefix('{vialidadUrbana}/dispositivos')->group(function () {
            Route::get('/{dispositivo}', [\App\Http\Controllers\Api\VialidadesUrbanasDispositivoController::class, 'index'])->name('api.vialidades_urbanas.dispositivos.index');
            Route::post('/{dispositivo}', [\App\Http\Controllers\Api\VialidadesUrbanasDispositivoController::class, 'store'])->middleware('can:crear operativos vialidades')->name('api.vialidades_urbanas.dispositivos.store');
            Route::get('/{dispositivo}/show', [\App\Http\Controllers\Api\VialidadesUrbanasDispositivoController::class, 'show'])->name('api.vialidades_urbanas.dispositivos.show');
            Route::put('/{dispositivo}', [\App\Http\Controllers\Api\VialidadesUrbanasDispositivoController::class, 'update'])->middleware('can:editar operativos vialidades')->name('api.vialidades_urbanas.dispositivos.update');
            Route::delete('/{dispositivo}/{detalle}', [\App\Http\Controllers\Api\VialidadesUrbanasDispositivoController::class, 'destroy'])->middleware('can:eliminar operativos vialidades')->name('api.vialidades_urbanas.dispositivos.destroy');
            Route::get('/{dispositivo}/whatsapp', [\App\Http\Controllers\Api\VialidadesUrbanasDispositivoController::class, 'whatsapp'])->name('api.vialidades_urbanas.dispositivos.whatsapp');
        });
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

    Route::prefix('puestas-disposicion')->group(function () {
        Route::get('/', [PuestaDisposicionController::class, 'index'])->middleware('can:ver puestas a disposicion')->name('api.puestas_disposicion.index');
        Route::post('/', [PuestaDisposicionController::class, 'store'])->middleware('can:crear puestas a disposicion')->name('api.puestas_disposicion.store');
        Route::get('/{puestaDisposicion}', [PuestaDisposicionController::class, 'show'])->whereNumber('puestaDisposicion')->middleware('can:ver puestas a disposicion')->name('api.puestas_disposicion.show');
        Route::put('/{puestaDisposicion}', [PuestaDisposicionController::class, 'update'])->whereNumber('puestaDisposicion')->middleware('can:editar puestas a disposicion')->name('api.puestas_disposicion.update');
        Route::delete('/{puestaDisposicion}', [PuestaDisposicionController::class, 'destroy'])->whereNumber('puestaDisposicion')->middleware('can:eliminar puestas a disposicion')->name('api.puestas_disposicion.destroy');
    });

    Route::get('/gruas', [GruaController::class, 'index'])->middleware('can:ver gruas');
    Route::get('/gruas/listado', [GruaController::class, 'listado'])->middleware('can:ver gruas');
    Route::get('/gruas/delegaciones', [GruaController::class, 'delegaciones'])->middleware('can:ver estadisticas');
    Route::get('/gruas/grafica-semanal', [GruaController::class, 'graficaSemanal'])->middleware('can:ver estadisticas');
    Route::get('/gruas/resumen-semanal', [GruaController::class, 'resumenSemanal'])->middleware('can:ver estadisticas');
    Route::get('/gruas/resumen-semanal-detallado', [GruaController::class, 'resumenSemanalDetallado'])->middleware('can:ver estadisticas');

    Route::get('/hechos/buscar', [HechoController::class, 'buscar'])->middleware('can:ver hechos');
    Route::get('/hechos', [HechoController::class, 'index'])->middleware('can:ver hechos');
    Route::post('/hechos', [HechoController::class, 'store'])->middleware('can:crear hechos');
    Route::get('/hechos/{hecho}', [HechoController::class, 'show'])->middleware('can:ver hechos');
    Route::put('/hechos/{hecho}', [HechoController::class, 'update']);
    Route::delete('/hechos/{hecho}', [HechoController::class, 'destroy'])->middleware('can:eliminar hechos');
    Route::post('/hechos/{hecho}/descargo', [HechoController::class, 'subirDescargo']);
    Route::get('/hechos/{hecho}/native-share', [HechoController::class, 'nativeShare'])->middleware('can:ver hechos');
    Route::get('/hechos/{hecho}/whatsapp-link', [HechoController::class, 'whatsappLink'])->middleware('can:ver hechos');

    Route::get('/hechos/{hecho}/vehiculos', [VehiculoController::class, 'index']);
    Route::post('/hechos/{hecho}/vehiculos', [VehiculoController::class, 'store']);
    Route::post('/vehiculos', [VehiculoController::class, 'store']);
    Route::get('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'show']);
    Route::put('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'update']);
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}', [VehiculoController::class, 'destroy']);
    Route::get('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'foto']);
    Route::post('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'fotoUpdate']);
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}/foto', [VehiculoController::class, 'fotoDestroy']);

    Route::get('/hechos/{hecho}/vehiculos/{vehiculo}/inventario-grua', [VehiculoController::class, 'inventarioGrua']);
    Route::post('/hechos/{hecho}/vehiculos/{vehiculo}/inventario-grua', [VehiculoController::class, 'inventarioGruaUpdate']);
    Route::delete('/hechos/{hecho}/vehiculos/{vehiculo}/inventario-grua', [VehiculoController::class, 'inventarioGruaDestroy']);

    Route::get('/hechos/{hecho}/lesionados', [LesionadoController::class, 'index'])->middleware('can:ver lesionados');
    Route::post('/hechos/{hecho}/lesionados', [LesionadoController::class, 'store'])->middleware('can:crear lesionados');
    Route::post('/lesionados', [LesionadoController::class, 'store'])->middleware('can:crear lesionados');
    Route::get('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'show'])->middleware('can:ver lesionados');
    Route::put('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'update'])->middleware('can:editar lesionados');
    Route::delete('/hechos/{hecho}/lesionados/{lesionado}', [LesionadoController::class, 'destroy'])->middleware('can:eliminar lesionados');

    Route::get('/hechos/{hecho}/croquis', [CroquisController::class, 'show'])->middleware('can:ver hechos');
    Route::post('/hechos/{hecho}/croquis', [CroquisController::class, 'store']);
    Route::put('/hechos/{hecho}/croquis', [CroquisController::class, 'update']);
    Route::delete('/hechos/{hecho}/croquis', [CroquisController::class, 'destroy']);

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
        Route::get('/export/hechos', [\App\Http\Controllers\Api\EstadisticasGlobalesController::class, 'exportHechos']);
    });

    Route::get('/mi-personal', [PersonalController::class, 'index'])->middleware('can:ver personal turno');
    Route::post('/mi-personal/{user}/ubicacion', [PersonalController::class, 'toggleUbicacion'])->middleware('can:gestionar ubicaciones turno');
    Route::post('/mi-personal/ubicacion/todos', [PersonalController::class, 'toggleUbicacionTodos'])->middleware('can:gestionar ubicaciones turno');
    Route::post('/mi-personal/{user}/ubicacion/limpiar', [PersonalController::class, 'limpiarUbicacionUsuario'])->middleware('can:gestionar ubicaciones turno');
    Route::post('/mi-personal/ubicacion/limpiar-todos', [PersonalController::class, 'limpiarUbicacionTodos'])->middleware('can:gestionar ubicaciones turno');

    Route::get('/app/version', [AppVersionController::class, 'show']);
});
