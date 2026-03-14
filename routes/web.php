<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\ApoyoController;
use App\Http\Controllers\BusquedaController;
use App\Http\Controllers\CampanaController;
use App\Http\Controllers\DocumentoHechoController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\EstadisticasGlobalesController;
use App\Http\Controllers\FormatoController;
use App\Http\Controllers\GruaController;
use App\Http\Controllers\HechosController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiberacionController;
use App\Http\Controllers\LicenciaController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\MapaPatrullasController;
use App\Http\Controllers\MapaIncidenciasController;
use App\Http\Controllers\OficioController;
use App\Http\Controllers\PatrullaController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceScheduleController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehiculosController;
use App\Http\Controllers\LesionadoController;
use App\Http\Controllers\ActividadController;
use App\Http\Controllers\DictamenController;
use App\Http\Controllers\PendientesCortesController;
use App\Http\Controllers\OperativoController;
use App\Http\Controllers\TramoController;
use App\Http\Controllers\GruaGuardiaController;
use App\Http\Controllers\GruaGuardiaSctController;
use App\Http\Controllers\GruaTramoController;
use App\Http\Controllers\TramoLookupController;
use App\Http\Controllers\DestacamentoController;
use App\Http\Controllers\WazeAlertWebController;
use App\Http\Controllers\RiesgoDashboardController;
use App\Http\Controllers\PuestaDisposicionController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\PersonalContactoController;
use App\Http\Controllers\PersonalDomicilioController;
use App\Http\Controllers\PersonalEmergenciaController;
use App\Http\Controllers\PersonalAsignacionController;
use App\Http\Controllers\PersonalIncidenciaController;
use App\Http\Controllers\DelegacionController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\BackupsSqlController;
use App\Http\Controllers\ModuloConstanciaExamenController;
use App\Http\Controllers\ArmamentoController;
use App\Http\Controllers\PatrullaKilometrajeController;
use App\Http\Controllers\RadarRiesgoController;
use App\Http\Controllers\ModuloExamenDiarioController;

Route::get('/', function () { return view('welcome'); })->name('welcome');

Route::middleware(['auth','can:ver mapa'])->group(function () {
    Route::get('/mapa',[MapaPatrullasController::class,'index'])->name('mapa.index');
    Route::get('/mapa-patrullas/data',[MapaPatrullasController::class,'data'])->name('mapa.patrullas.data');
    Route::get('/mapa/mi-personal',[MapaPatrullasController::class,'miPersonal'])->name('mapa.mi_personal');
    Route::post('/mapa/mi-personal/{user}/ubicacion',[MapaPatrullasController::class,'toggleUbicacionUsuario'])->name('mapa.mi_personal.toggle');
    Route::post('/mapa/mi-personal/ubicacion/todos',[MapaPatrullasController::class,'toggleUbicacionTodos'])->name('mapa.mi_personal.toggle_all');
});

Route::prefix('operativos')->middleware(['auth','can:ver operativos'])->group(function () {
    Route::get('/', [OperativoController::class, 'index'])->name('operativos.index');
    Route::get('/create', [OperativoController::class, 'create'])->middleware('can:crear operativos')->name('operativos.create');
    Route::post('/', [OperativoController::class, 'store'])->middleware('can:crear operativos')->name('operativos.store');
    Route::get('/{operativo}', [OperativoController::class, 'show'])->name('operativos.show');
    Route::get('/{operativo}/edit', [OperativoController::class, 'edit'])->middleware('can:editar operativos')->name('operativos.edit');
    Route::put('/{operativo}', [OperativoController::class, 'update'])->middleware('can:editar operativos')->name('operativos.update');
    Route::delete('/{operativo}', [OperativoController::class, 'destroy'])->middleware('can:eliminar operativos')->name('operativos.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/waze/alerts', [WazeAlertWebController::class, 'index'])->name('waze.alerts.index');
    Route::post('/waze/alerts/{alert}/read', [WazeAlertWebController::class, 'markRead'])->name('waze.alerts.read');
    Route::post('/waze/alerts/read-all', [WazeAlertWebController::class, 'markAllRead'])->name('waze.alerts.read_all');
    Route::get('/waze/alerts/unread-count', [WazeAlertWebController::class, 'unreadCount'])->name('waze.alerts.unread_count');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/waze/riesgo', [RiesgoDashboardController::class, 'index'])->name('waze.riesgo.index');
    Route::get('/waze/riesgo/data', [RiesgoDashboardController::class, 'data'])->name('waze.riesgo.data');
});

Route::middleware(['auth','can:ver mapa'])->group(function () {
    Route::get('/mapa-incidencias', [MapaIncidenciasController::class,'index'])->name('mapa.incidencias.index');
    Route::get('/mapa-incidencias/data', [MapaIncidenciasController::class,'data'])->name('mapa.incidencias.data');
});


Route::get('/liberacion/{vehiculo}',[LiberacionController::class,'publica'])->name('liberacion.publica');
Route::get('liberacion/qr/{token}',[LiberacionController::class,'desdeToken'])->name('liberacion.publica.token');

Route::middleware('auth')->group(function () {
    Route::get('/profile',[UserController::class,'profile'])->name('profile');
    Route::get('/change-password',[UserController::class,'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password',[UserController::class,'updatePassword'])->name('user.password.update');
});

Route::middleware(['auth','can:subir liberacion grua'])->group(function () {
    Route::get('/liberacion/{vehiculo}/grua',[LiberacionController::class,'verParaGruas'])->name('liberacion.grua.ver');
    Route::post('/liberacion/{vehiculo}/grua',[LiberacionController::class,'storePdfGruas'])->name('liberacion.grua.subir');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/liberacion/{vehiculo}/crear',[LiberacionController::class,'create'])->name('liberacion.create');
    Route::post('/liberacion/{vehiculo}',[LiberacionController::class,'store'])->name('liberacion.store');
    Route::get('/liberacion/{vehiculo}/editar',[LiberacionController::class,'edit'])->name('liberacion.edit');
    Route::put('/liberacion/{vehiculo}',[LiberacionController::class,'update'])->name('liberacion.update');
    Route::get('/liberacion/{vehiculo}/detalles',[LiberacionController::class,'detalles'])->name('liberacion.detalles');
    Route::get('/liberacion/{vehiculo}/acuse',[LiberacionController::class,'generarAcuse'])->name('liberacion.descargar');
});

Route::get('/busqueda',[BusquedaController::class,'index'])->name('busqueda.index');
Route::get('/campanas',[CampanaController::class,'index'])->name('campanas.index');
Route::get('/apoyo',[ApoyoController::class,'index'])->name('apoyo.index');

Route::prefix('licencias')->group(function () {
    Route::get('/requisitos',[LicenciaController::class,'requisitos'])->name('licencias.requisitos');
    Route::get('/costos',[LicenciaController::class,'costos'])->name('licencias.costos');
    Route::get('/ubicaciones',[LicenciaController::class,'ubicaciones'])->name('licencias.ubicaciones');
});

Auth::routes();

Route::get('/home',[HomeController::class,'index'])->name('home');

Route::prefix('actividades')->middleware(['auth','can:ver actividades'])->group(function () {
    Route::get('/subcategorias/{categoria}', [ActividadController::class, 'subcategorias'])->name('actividades.subcategorias');
    Route::get('/',[ActividadController::class,'index'])->name('actividades.index');
    Route::get('/create',[ActividadController::class,'create'])->middleware('can:crear actividades')->name('actividades.create');
    Route::post('/',[ActividadController::class,'store'])->middleware('can:crear actividades')->name('actividades.store');
    Route::get('/informe/diario',[ActividadController::class,'informeDiario'])->name('actividades.informe.diario');
    Route::get('/informe/fecha/{fecha}',[ActividadController::class,'informeFecha'])->name('actividades.informe.fecha');
    Route::get('/{actividad}',[ActividadController::class,'show'])->name('actividades.show');
    Route::get('/{actividad}/edit',[ActividadController::class,'edit'])->middleware('can:editar actividades')->name('actividades.edit');
    Route::put('/{actividad}',[ActividadController::class,'update'])->middleware('can:editar actividades')->name('actividades.update');
    Route::delete('/{actividad}',[ActividadController::class,'destroy'])->middleware('can:eliminar actividades')->name('actividades.destroy');
});

Route::prefix('estadisticas-globales')->middleware(['auth','can:ver estadisticas globales'])->group(function () {
    Route::get('/',[EstadisticasGlobalesController::class,'index'])->name('estadisticas_globales.index');
    Route::get('/kpis',[EstadisticasGlobalesController::class,'kpis'])->name('estadisticas_globales.kpis');
    Route::get('/series/hechos',[EstadisticasGlobalesController::class,'seriesHechos'])->name('estadisticas_globales.series.hechos');
    Route::get('/series/lesionados',[EstadisticasGlobalesController::class,'seriesLesionados'])->name('estadisticas_globales.series.lesionados');
    Route::get('/series/tipo-hecho',[EstadisticasGlobalesController::class,'seriesTipoHecho'])->name('estadisticas_globales.series.tipo_hecho');
    Route::get('/series/sector',[EstadisticasGlobalesController::class,'seriesSector'])->name('estadisticas_globales.series.sector');
    Route::get('/series/municipio',[EstadisticasGlobalesController::class,'seriesMunicipio'])->name('estadisticas_globales.series.municipio');
    Route::get('/series/tiempo',[EstadisticasGlobalesController::class,'seriesTiempo'])->name('estadisticas_globales.series.tiempo');
    Route::get('/series/clima',[EstadisticasGlobalesController::class,'seriesClima'])->name('estadisticas_globales.series.clima');
    Route::get('/series/condiciones',[EstadisticasGlobalesController::class,'seriesCondiciones'])->name('estadisticas_globales.series.condiciones');
    Route::get('/series/control-transito',[EstadisticasGlobalesController::class,'seriesControlTransito'])->name('estadisticas_globales.series.control_transito');
    Route::get('/series/vehiculos/tipo',[EstadisticasGlobalesController::class,'seriesVehiculosTipo'])->name('estadisticas_globales.series.vehiculos_tipo');
    Route::get('/series/vehiculos/marca',[EstadisticasGlobalesController::class,'seriesVehiculosMarca'])->name('estadisticas_globales.series.vehiculos_marca');
    Route::get('/series/vehiculos/modelo',[EstadisticasGlobalesController::class,'seriesVehiculosModelo'])->name('estadisticas_globales.series.vehiculos_modelo');
    Route::get('/hechos',[EstadisticasGlobalesController::class,'hechos'])->name('estadisticas_globales.hechos');
    Route::get('/export/hechos',[EstadisticasGlobalesController::class,'exportHechos'])->name('estadisticas_globales.export.hechos');
    Route::get('/export/mensual', [EstadisticasGlobalesController::class, 'exportMensual'])->name('estadisticas_globales.export.mensual');
});

Route::prefix('oficios')->middleware('can:ver oficios')->group(function () {
    Route::get('/',[OficioController::class,'index'])->name('oficios.index');
    Route::get('/create',[OficioController::class,'create'])->middleware('can:crear oficios')->name('oficios.create');
    Route::post('/',[OficioController::class,'store'])->middleware('can:crear oficios')->name('oficios.store');
    Route::get('/{oficio}',[OficioController::class,'show'])->middleware('can:ver oficios')->name('oficios.show');
    Route::get('/{oficio}/edit',[OficioController::class,'edit'])->middleware('can:editar oficios')->name('oficios.edit');
    Route::put('/{oficio}',[OficioController::class,'update'])->middleware('can:editar oficios')->name('oficios.update');
    Route::delete('/{oficio}',[OficioController::class,'destroy'])->middleware('can:eliminar oficios')->name('oficios.destroy');
});

Route::prefix('listas')->middleware('can:ver listas')->group(function () {
    Route::get('/',[ListaController::class,'index'])->name('listas.index');
    Route::get('/create',[ListaController::class,'create'])->middleware('can:crear listas')->name('listas.create');
    Route::post('/',[ListaController::class,'store'])->middleware('can:crear listas')->name('listas.store');
    Route::get('/{lista}',[ListaController::class,'show'])->middleware('can:ver listas')->name('listas.show');
    Route::get('/{lista}/edit',[ListaController::class,'edit'])->middleware('can:editar listas')->name('listas.edit');
    Route::put('/{lista}',[ListaController::class,'update'])->middleware('can:editar listas')->name('listas.update');
    Route::delete('/{lista}',[ListaController::class,'destroy'])->middleware('can:eliminar listas')->name('listas.destroy');
});

Route::prefix('formatos')->middleware('can:ver formatos')->group(function () {
    Route::get('/',[FormatoController::class,'index'])->name('formatos.index');
    Route::get('/create',[FormatoController::class,'create'])->middleware('can:crear formatos')->name('formatos.create');
    Route::post('/',[FormatoController::class,'store'])->middleware('can:crear formatos')->name('formatos.store');
    Route::get('/{formato}',[FormatoController::class,'show'])->middleware('can:ver formatos')->name('formatos.show');
    Route::get('/{formato}/edit',[FormatoController::class,'edit'])->middleware('can:editar formatos')->name('formatos.edit');
    Route::put('/{formato}',[FormatoController::class,'update'])->middleware('can:editar formatos')->name('formatos.update');
    Route::delete('/{formato}',[FormatoController::class,'destroy'])->middleware('can:eliminar formatos')->name('formatos.destroy');
});

Route::prefix('dictamenes')->middleware(['auth', 'can:ver dictamenes', 'unidad:siniestros'])->group(function () {
    Route::get('/', [DictamenController::class, 'index'])->name('dictamenes.index');
    Route::get('/create', [DictamenController::class, 'create'])->middleware('can:crear dictamenes')->name('dictamenes.create');
    Route::post('/', [DictamenController::class, 'store'])->middleware('can:crear dictamenes')->name('dictamenes.store');
    Route::get('/{dictamen}', [DictamenController::class, 'show'])->middleware('can:ver dictamenes')->name('dictamenes.show');
    Route::get('/{dictamen}/edit', [DictamenController::class, 'edit'])->middleware('can:editar dictamenes')->name('dictamenes.edit');
    Route::put('/{dictamen}', [DictamenController::class, 'update'])->middleware('can:editar dictamenes')->name('dictamenes.update');
    Route::delete('/{dictamen}', [DictamenController::class, 'destroy'])->middleware('can:eliminar dictamenes')->name('dictamenes.destroy');
});

Route::prefix('puestas-disposicion')->middleware(['auth'])->group(function () {
    Route::get('/', [PuestaDisposicionController::class, 'index'])->middleware('can:ver puestas a disposicion')->name('puestas_disposicion.index');
    Route::get('/create', [PuestaDisposicionController::class, 'create'])->middleware('can:crear puestas a disposicion')->name('puestas_disposicion.create');
    Route::post('/', [PuestaDisposicionController::class, 'store'])->middleware('can:crear puestas a disposicion')->name('puestas_disposicion.store');
    Route::get('/{puestaDisposicion}', [PuestaDisposicionController::class, 'show'])->middleware('can:ver puestas a disposicion')->name('puestas_disposicion.show');
    Route::get('/{puestaDisposicion}/edit', [PuestaDisposicionController::class, 'edit'])->middleware('can:editar puestas a disposicion')->name('puestas_disposicion.edit');
    Route::put('/{puestaDisposicion}', [PuestaDisposicionController::class, 'update'])->middleware('can:editar puestas a disposicion')->name('puestas_disposicion.update');
    Route::delete('/{puestaDisposicion}', [PuestaDisposicionController::class, 'destroy'])->middleware('can:eliminar puestas a disposicion')->name('puestas_disposicion.destroy');
});

    /** =========================
     *  GRÚAS
     *  ========================= */

Route::middleware(['auth','can:ver gruas'])->group(function () {
    Route::resource('tramos', TramoController::class);
    Route::resource('grua-guardias', GruaGuardiaController::class);
    Route::resource('grua-guardias-sct', GruaGuardiaSctController::class);
    Route::get('tramos-lookup', [TramoLookupController::class,'index'])->name('tramos.lookup.index');
    Route::post('tramos-lookup', [TramoLookupController::class,'resolve'])->name('tramos.lookup.resolve');
});

Route::prefix('gruas')->middleware(['auth','can:ver gruas'])->group(function () {
    Route::get('/',[GruaController::class,'index'])->name('gruas.index');
    Route::get('/create',[GruaController::class,'create'])->middleware('can:crear gruas')->name('gruas.create');
    Route::post('/',[GruaController::class,'store'])->middleware('can:crear gruas')->name('gruas.store');
    Route::get('/{grua}',[GruaController::class,'show'])->middleware('can:ver gruas')->name('gruas.show');
    Route::get('/{grua}/edit',[GruaController::class,'edit'])->middleware('can:editar gruas')->name('gruas.edit');
    Route::put('/{grua}',[GruaController::class,'update'])->middleware('can:editar gruas')->name('gruas.update');
    Route::delete('/{grua}',[GruaController::class,'destroy'])->middleware('can:eliminar gruas')->name('gruas.destroy');

    Route::prefix('{grua}/servicios')->group(function () {
        Route::get('/',[ServicioController::class,'index'])->name('servicios.index');
        Route::get('/create',[ServicioController::class,'create'])->name('servicios.create');
        Route::post('/',[ServicioController::class,'store'])->name('servicios.store');
        Route::get('/{servicio}',[ServicioController::class,'show'])->name('servicios.show');
        Route::get('/{servicio}/edit',[ServicioController::class,'edit'])->name('servicios.edit');
        Route::put('/{servicio}',[ServicioController::class,'update'])->name('servicios.update');
        Route::delete('/{servicio}',[ServicioController::class,'destroy'])->name('servicios.destroy');
    });

    Route::get('/{grua}/tramos', [GruaTramoController::class,'index'])->name('gruas.tramos.index');
    Route::post('/{grua}/tramos', [GruaTramoController::class,'store'])->name('gruas.tramos.store');
    Route::delete('/{grua}/tramos/{tramo}', [GruaTramoController::class,'destroy'])->name('gruas.tramos.destroy');
});

Route::prefix('hechos')->middleware('can:ver hechos')->group(function () {
    Route::get('/',[HechosController::class,'index'])->name('hechos.index');
    Route::get('/seguimiento', [HechosController::class, 'seguimiento'])->name('hechos.seguimiento');
    Route::get('/create',[HechosController::class,'create'])->middleware('can:crear hechos')->name('hechos.create');
    Route::post('/',[HechosController::class,'store'])->middleware('can:crear hechos')->name('hechos.store');
    Route::get('/{hecho}',[HechosController::class,'show'])->middleware('can:ver hechos')->name('hechos.show');
    Route::get('/{hecho}/edit',[HechosController::class,'edit'])->middleware('can:editar hechos')->name('hechos.edit');
    Route::put('/{hecho}',[HechosController::class,'update'])->middleware('can:editar hechos')->name('hechos.update');
    Route::delete('/{hecho}',[HechosController::class,'destroy'])->middleware('can:eliminar hechos')->name('hechos.destroy');
    Route::get('/{hecho}/descargar',[DocumentoHechoController::class,'descargarDocx'])->name('hechos.descargar');

    Route::prefix('/{hecho}/vehiculos')->middleware('can:ver vehiculos')->group(function () {
        Route::get('/',[VehiculosController::class,'index'])->name('vehiculos.index');
        Route::get('/create',[VehiculosController::class,'create'])->middleware('can:crear vehiculos')->name('vehiculos.create');
        Route::post('/',[VehiculosController::class,'store'])->middleware('can:crear vehiculos')->name('vehiculos.store');
        Route::get('/{vehiculo}/edit',[VehiculosController::class,'edit'])->middleware('can:editar vehiculos')->name('vehiculos.edit');
        Route::put('/{vehiculo}',[VehiculosController::class,'update'])->middleware('can:editar vehiculos')->name('vehiculos.update');
        Route::delete('/{vehiculo}',[VehiculosController::class,'destroy'])->middleware('can:eliminar vehiculos')->name('vehiculos.destroy');
        Route::get('/{vehiculo}/foto',[VehiculosController::class,'foto'])->middleware('can:editar vehiculos')->name('vehiculos.foto');
        Route::post('/{vehiculo}/foto',[VehiculosController::class,'fotoUpdate'])->middleware('can:editar vehiculos')->name('vehiculos.foto.update');
        Route::delete('/{vehiculo}/foto',[VehiculosController::class,'fotoDestroy'])->middleware('can:editar vehiculos')->name('vehiculos.foto.destroy');
    });

    Route::prefix('/{hecho}/lesionados')->middleware('can:ver lesionados')->group(function () {
        Route::get('/',[LesionadoController::class,'index'])->name('lesionados.index');
        Route::get('/create',[LesionadoController::class,'create'])->middleware('can:crear lesionados')->name('lesionados.create');
        Route::post('/',[LesionadoController::class,'store'])->middleware('can:crear lesionados')->name('lesionados.store');
        Route::get('/{lesionado}/edit',[LesionadoController::class,'edit'])->middleware('can:editar lesionados')->name('lesionados.edit');
        Route::put('/{lesionado}',[LesionadoController::class,'update'])->middleware('can:editar lesionados')->name('lesionados.update');
        Route::delete('/{lesionado}',[LesionadoController::class,'destroy'])->middleware('can:eliminar lesionados')->name('lesionados.destroy');
    });

    Route::prefix('pendientes')->group(function () {
        Route::get('/cortes', [PendientesCortesController::class, 'index'])->name('hechos.pendientes.cortes.index');
        Route::get('/cortes/{corte}', [PendientesCortesController::class, 'show'])->name('hechos.pendientes.cortes.show');
    });

    Route::post('/{hecho}/whatsapp', [HechosController::class, 'sendWhatsapp'])->name('hechos.whatsapp.send');
});

Route::prefix('modulo-examenes-diarios')->middleware('can:ver modulo examenes')->group(function () {
    Route::get('/', [ModuloExamenDiarioController::class, 'index'])->name('modulo_examenes_diarios.index');
    Route::get('/create', [ModuloExamenDiarioController::class, 'create'])->middleware('can:crear modulo examenes')->name('modulo_examenes_diarios.create');
    Route::post('/', [ModuloExamenDiarioController::class, 'store'])->middleware('can:crear modulo examenes')->name('modulo_examenes_diarios.store');
    Route::get('/{registro}', [ModuloExamenDiarioController::class, 'show'])->name('modulo_examenes_diarios.show');
    Route::get('/{registro}/edit', [ModuloExamenDiarioController::class, 'edit'])->middleware('can:editar modulo examenes')->name('modulo_examenes_diarios.edit');
    Route::put('/{registro}', [ModuloExamenDiarioController::class, 'update'])->middleware('can:editar modulo examenes')->name('modulo_examenes_diarios.update');
    Route::delete('/{registro}', [ModuloExamenDiarioController::class, 'destroy'])->middleware('can:eliminar modulo examenes')->name('modulo_examenes_diarios.destroy');
});

Route::prefix('modulo-constancias-examenes')->middleware(['auth','can:ver modulo examenes'])->group(function () {
    Route::get('/', [ModuloConstanciaExamenController::class, 'index'])->name('modulo_constancias_examenes.index');
    Route::get('/create', [ModuloConstanciaExamenController::class, 'create'])->middleware('can:crear modulo examenes')->name('modulo_constancias_examenes.create');
    Route::post('/', [ModuloConstanciaExamenController::class, 'store'])->middleware('can:crear modulo examenes')->name('modulo_constancias_examenes.store');
    Route::get('/{constancia}', [ModuloConstanciaExamenController::class, 'show'])->name('modulo_constancias_examenes.show');
    Route::get('/{constancia}/descargar-pdf', [ModuloConstanciaExamenController::class, 'descargarPdf'])->name('modulo_constancias_examenes.descargar_pdf');
    Route::get('/{constancia}/reimprimir', [ModuloConstanciaExamenController::class, 'reimprimir'])->middleware('can:crear modulo examenes')->name('modulo_constancias_examenes.reimprimir');
    Route::post('/{constancia}/cancelar', [ModuloConstanciaExamenController::class, 'cancelar'])->middleware('can:editar modulo examenes')->name('modulo_constancias_examenes.cancelar');
});

Route::get('/servicios/grafico',[ServicioController::class,'grafico'])->name('servicios.grafico');

Route::prefix('admin/settings')->middleware('can:ver configuraciones')->group(function () {
    Route::get('/',[SettingsController::class,'index'])->name('settings.index');

    Route::prefix('patrullas')->middleware('can:ver patrullas')->group(function () {
        Route::get('/',[PatrullaController::class,'index'])->name('patrullas.index');
        Route::get('/create',[PatrullaController::class,'create'])->middleware('can:crear patrullas')->name('patrullas.create');
        Route::post('/',[PatrullaController::class,'store'])->middleware('can:crear patrullas')->name('patrullas.store');
        Route::get('/{patrulla}',[PatrullaController::class,'show'])->middleware('can:ver patrullas')->name('patrullas.show');
        Route::get('/{patrulla}/edit',[PatrullaController::class,'edit'])->middleware('can:editar patrullas')->name('patrullas.edit');
        Route::put('/{patrulla}',[PatrullaController::class,'update'])->middleware('can:editar patrullas')->name('patrullas.update');
        Route::delete('/{patrulla}',[PatrullaController::class,'destroy'])->middleware('can:eliminar patrullas')->name('patrullas.destroy');

        Route::prefix('{patrulla}/kilometrajes')->middleware('can:ver kilometrajes patrullas')->group(function () {
            Route::get('/', [PatrullaKilometrajeController::class, 'index'])->name('patrullas.kilometrajes.index');
            Route::get('/create', [PatrullaKilometrajeController::class, 'create'])->middleware('can:crear kilometrajes patrullas')->name('patrullas.kilometrajes.create');
            Route::post('/', [\App\Http\Controllers\PatrullaKilometrajeController::class, 'store'])->middleware('can:crear kilometrajes patrullas')->name('patrullas.kilometrajes.store');
            Route::get('/{kilometraje}/edit', [PatrullaKilometrajeController::class, 'edit'])->middleware('can:editar kilometrajes patrullas')->name('patrullas.kilometrajes.edit');
            Route::put('/{kilometraje}', [PatrullaKilometrajeController::class, 'update'])->middleware('can:editar kilometrajes patrullas')->name('patrullas.kilometrajes.update');
            Route::delete('/{kilometraje}', [PatrullaKilometrajeController::class, 'destroy'])->middleware('can:eliminar kilometrajes patrullas')->name('patrullas.kilometrajes.destroy');
        });
    });

    Route::prefix('delegaciones')->middleware('can:ver delegaciones')->group(function () {
        Route::get('/', [DelegacionController::class, 'index'])->name('delegaciones.index');
        Route::get('/create', [DelegacionController::class, 'create'])->middleware('can:crear delegaciones')->name('delegaciones.create');
        Route::post('/', [DelegacionController::class, 'store'])->middleware('can:crear delegaciones')->name('delegaciones.store');
        Route::get('/{delegacion}', [DelegacionController::class, 'show'])->name('delegaciones.show');
        Route::get('/{delegacion}/edit', [DelegacionController::class, 'edit'])->middleware('can:editar delegaciones')->name('delegaciones.edit');
        Route::put('/{delegacion}', [DelegacionController::class, 'update'])->middleware('can:editar delegaciones')->name('delegaciones.update');
        Route::delete('/{delegacion}', [DelegacionController::class, 'destroy'])->middleware('can:eliminar delegaciones')->name('delegaciones.destroy');
        Route::get('/{delegacion}/hijas', [DelegacionController::class, 'hijas'])->name('delegaciones.hijas');
    });

    Route::prefix('users')->middleware('can:ver usuarios')->group(function () {
        Route::get('/',[UserController::class,'index'])->name('users.index');
        Route::get('/create',[UserController::class,'create'])->middleware('can:crear usuarios')->name('users.create');
        Route::post('/',[UserController::class,'store'])->middleware('can:crear usuarios')->name('users.store');
        Route::get('/{user}',[UserController::class,'show'])->middleware('can:ver usuarios')->name('users.show');
        Route::get('/{user}/edit',[UserController::class,'edit'])->middleware('can:editar usuarios')->name('users.edit');
        Route::put('/{user}',[UserController::class,'update'])->middleware('can:editar usuarios')->name('users.update');
        Route::delete('/{user}',[UserController::class,'destroy'])->middleware('can:eliminar usuarios')->name('users.destroy');
    });

    Route::prefix('roles')->middleware('can:ver roles')->group(function () {
        Route::get('/',[RoleController::class,'index'])->name('roles.index');
        Route::get('/create',[RoleController::class,'create'])->middleware('can:crear roles')->name('roles.create');
        Route::post('/',[RoleController::class,'store'])->middleware('can:crear roles')->name('roles.store');
        Route::get('/{role}',[RoleController::class,'show'])->name('roles.show');
        Route::get('/{role}/edit',[RoleController::class,'edit'])->middleware('can:editar roles')->name('roles.edit');
        Route::put('/{role}',[RoleController::class,'update'])->middleware('can:editar roles')->name('roles.update');
        Route::delete('/{role}',[RoleController::class,'destroy'])->middleware('can:eliminar roles')->name('roles.destroy');
        Route::get('/{role}/permissions',[RoleController::class,'permissions'])->middleware('can:editar roles')->name('roles.permissions');
        Route::post('/{role}/permissions',[RoleController::class,'assignPermissions'])->middleware('can:editar roles')->name('roles.assignPermissions');
    });

    Route::prefix('personal')->middleware('can:ver personal')->group(function () {
        Route::get('/', [PersonalController::class, 'index'])->name('personal.index');
        Route::get('/create', [PersonalController::class, 'create'])->middleware('can:crear personal')->name('personal.create');
        Route::post('/', [PersonalController::class, 'store'])->middleware('can:crear personal')->name('personal.store');
        Route::get('/{personal}', [PersonalController::class, 'show'])->name('personal.show');
        Route::get('/{personal}/edit', [PersonalController::class, 'edit'])->middleware('can:editar personal')->name('personal.edit');
        Route::put('/{personal}', [PersonalController::class, 'update'])->middleware('can:editar personal')->name('personal.update');
        Route::delete('/{personal}', [PersonalController::class, 'destroy'])->middleware('can:borrar personal')->name('personal.destroy');

        Route::post('/{personal}/contactos', [PersonalContactoController::class, 'store'])->middleware('can:editar personal')->name('personal.contactos.store');
        Route::put('/{personal}/contactos/{contacto}', [PersonalContactoController::class, 'update'])->middleware('can:editar personal')->name('personal.contactos.update');
        Route::delete('/{personal}/contactos/{contacto}', [PersonalContactoController::class, 'destroy'])->middleware('can:editar personal')->name('personal.contactos.destroy');

        Route::post('/{personal}/domicilios', [PersonalDomicilioController::class, 'store'])->middleware('can:editar personal')->name('personal.domicilios.store');
        Route::put('/{personal}/domicilios/{domicilio}', [PersonalDomicilioController::class, 'update'])->middleware('can:editar personal')->name('personal.domicilios.update');
        Route::delete('/{personal}/domicilios/{domicilio}', [PersonalDomicilioController::class, 'destroy'])->middleware('can:editar personal')->name('personal.domicilios.destroy');

        Route::post('/{personal}/emergencias', [PersonalEmergenciaController::class, 'store'])->middleware('can:editar personal')->name('personal.emergencias.store');
        Route::put('/{personal}/emergencias/{emergencia}', [PersonalEmergenciaController::class, 'update'])->middleware('can:editar personal')->name('personal.emergencias.update');
        Route::delete('/{personal}/emergencias/{emergencia}', [PersonalEmergenciaController::class, 'destroy'])->middleware('can:editar personal')->name('personal.emergencias.destroy');

        Route::get('/{personal}/incidencias/create', [PersonalIncidenciaController::class, 'create'])->middleware('can:editar personal')->name('personal.incidencias.create');
        Route::post('/{personal}/incidencias', [PersonalIncidenciaController::class, 'store'])->middleware('can:editar personal')->name('personal.incidencias.store');
        Route::get('/{personal}/incidencias/{incidencia}/edit', [PersonalIncidenciaController::class, 'edit'])->middleware('can:editar personal')->name('personal.incidencias.edit');
        Route::put('/{personal}/incidencias/{incidencia}', [PersonalIncidenciaController::class, 'update'])->middleware('can:editar personal')->name('personal.incidencias.update');
        Route::delete('/{personal}/incidencias/{incidencia}', [PersonalIncidenciaController::class, 'destroy'])->middleware('can:editar personal')->name('personal.incidencias.destroy');

        Route::get('/{personal}/asignaciones/create', [PersonalAsignacionController::class, 'create'])->middleware('can:editar personal')->name('personal.asignaciones.create');
        Route::post('/{personal}/asignaciones', [PersonalAsignacionController::class, 'store'])->middleware('can:editar personal')->name('personal.asignaciones.store');
        Route::get('/{personal}/asignaciones/{asignacion}/edit', [PersonalAsignacionController::class, 'edit'])->middleware('can:editar personal')->name('personal.asignaciones.edit');
        Route::put('/{personal}/asignaciones/{asignacion}', [PersonalAsignacionController::class, 'update'])->middleware('can:editar personal')->name('personal.asignaciones.update');
        Route::post('/{personal}/asignaciones/{asignacion}/cerrar', [PersonalAsignacionController::class, 'cerrar'])->middleware('can:editar personal')->name('personal.asignaciones.cerrar');
        Route::delete('/{personal}/asignaciones/{asignacion}', [PersonalAsignacionController::class, 'destroy'])->middleware('can:editar personal')->name('personal.asignaciones.destroy');

        Route::post('/{personal}/armamento/asignar',[PersonalAsignacionController::class,'asignarArmamento'])->middleware('can:editar personal')->name('personal.armamento.asignar');
        Route::post('/{personal}/armamento/{asignacion}/quitar',[PersonalAsignacionController::class,'quitarArmamento'])->middleware('can:editar personal')->name('personal.armamento.quitar');
    });

    Route::prefix('armamentos')->middleware('can:ver armamentos')->group(function () {
        Route::get('/', [ArmamentoController::class, 'index'])->name('armamentos.index');
        Route::get('/create', [ArmamentoController::class, 'create'])->middleware('can:crear armamentos')->name('armamentos.create');
        Route::post('/', [ArmamentoController::class, 'store'])->middleware('can:crear armamentos')->name('armamentos.store');
        Route::get('/{armamento}', [ArmamentoController::class, 'show'])->name('armamentos.show');
        Route::get('/{armamento}/edit', [ArmamentoController::class, 'edit'])->middleware('can:editar armamentos')->name('armamentos.edit');
        Route::put('/{armamento}', [ArmamentoController::class, 'update'])->middleware('can:editar armamentos')->name('armamentos.update');
        Route::delete('/{armamento}', [ArmamentoController::class, 'destroy'])->middleware('can:eliminar armamentos')->name('armamentos.destroy');
    });

    Route::prefix('estadisticas')->middleware('can:ver estadisticas')->group(function () {
        Route::get('/',[EstadisticasController::class,'index'])->name('estadisticas.index');
        Route::get('/parte-novedades',[EstadisticasController::class,'parteNovedades'])->name('estadisticas.parteNovedades');
        Route::get('/parte-novedades/descargar',[EstadisticasController::class,'descargarParte'])->name('estadisticas.parteNovedades.descargar');
        Route::get('/mini-parte',[EstadisticasController::class,'miniParte'])->name('estadisticas.miniParte');
        Route::get('/mini-parte/descargar',[EstadisticasController::class,'descargarMiniParte'])->name('estadisticas.miniParte.descargar');
        Route::get('/dictamen',[EstadisticasController::class,'dictamen'])->name('estadisticas.dictamen');
        Route::get('/dictamen/{id}',[EstadisticasController::class,'dictamenShow'])->name('estadisticas.dictamen.show');
        Route::get('/dictamen/{id}/docx',[EstadisticasController::class,'dictamenDocx'])->name('estadisticas.dictamen.docx');
        Route::get('/bitacora',[EstadisticasController::class,'bitacora'])->name('estadisticas.bitacora');
        Route::get('/bitacora/descargar',[EstadisticasController::class,'descargarBitacora'])->name('estadisticas.bitacora.descargar');
    });

    Route::get('/radar-riesgo', [RadarRiesgoController::class, 'index'])->name('radar.riesgo');

    Route::prefix('backups-sql')->middleware(['auth'])->group(function () {
        Route::get('/', [BackupsSqlController::class, 'index'])->name('backups_sql.index');

        Route::get('/{file}', [BackupsSqlController::class, 'download'])
            ->where('file', '[A-Za-z0-9._-]+\.sql(\.gz)?')
            ->name('backups_sql.download');
    });

    Route::prefix('destacamentos')->middleware(['auth','can:ver destacamentos'])->group(function () {
        Route::get('/', [DestacamentoController::class, 'index'])->name('destacamentos.index');
        Route::get('/create', [DestacamentoController::class, 'create'])->middleware('can:crear destacamentos')->name('destacamentos.create');
        Route::post('/', [DestacamentoController::class, 'store'])->middleware('can:crear destacamentos')->name('destacamentos.store');
        Route::get('/{destacamento}', [DestacamentoController::class, 'show'])->name('destacamentos.show');
        Route::get('/{destacamento}/edit', [DestacamentoController::class, 'edit'])->middleware('can:editar destacamentos')->name('destacamentos.edit');
        Route::put('/{destacamento}', [DestacamentoController::class, 'update'])->middleware('can:editar destacamentos')->name('destacamentos.update');
        Route::delete('/{destacamento}', [DestacamentoController::class, 'destroy'])->middleware('can:eliminar destacamentos')->name('destacamentos.destroy');
    });

    Route::get('/exports/estado-fuerza', [ExportController::class, 'estadoFuerza'])->name('settings.exports.estado_fuerza');
    Route::get('/admin/settings/exports/parte-novedades', [ExportController::class, 'parteNovedades'])->name('settings.exports.parte_novedades');
    Route::get('/admin/settings/exports/bitacora', [ExportController::class, 'bitacora'])->name('settings.exports.bitacora');
    Route::get('/admin/settings/exports/mini-parte', [ExportController::class, 'miniParte'])->name('settings.exports.mini_parte');
    Route::get('/exports/bitacora-turno', [ExportController::class, 'bitacoraTurno'])->name('settings.exports.bitacora_turno');
});

Route::get('/prueba-404', function () { return response()->view('errors.404', [], 404); });

Route::view('/privacy-policy', 'privacy_policy')->name('privacy.policy');

