<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArchivoAreaController;
use App\Http\Controllers\Api\ArchivoAnaquelController;
use App\Http\Controllers\Api\ArchivoBloqueController;
use App\Http\Controllers\Api\ArchivoLibroController;
use App\Http\Controllers\Api\ArchivoDocumentoController;
use App\Http\Controllers\Api\ArchivoFolioController;
use App\Http\Controllers\Api\ArchivoMovimientoController;

Route::prefix('archivo-siniestros')
    ->name('api.archivo_siniestros.')
    ->middleware('can:ver archivo siniestros')
    ->group(function () {

        Route::get('/mapa', [ArchivoAnaquelController::class, 'mapa'])->name('mapa');
        Route::get('/buscar', [ArchivoLibroController::class, 'buscar'])->name('buscar');

        Route::get('/libros/qr/{uuid}', [ArchivoLibroController::class, 'porQr'])
            ->whereUuid('uuid')
            ->name('libros.qr');

        Route::get('/bloques/qr/{uuid}', [ArchivoBloqueController::class, 'porQr'])
            ->whereUuid('uuid')
            ->name('bloques.qr');

        Route::apiResource('areas', ArchivoAreaController::class)
            ->only(['index', 'show']);

        Route::apiResource('anaqueles', ArchivoAnaquelController::class)
            ->parameters(['anaqueles' => 'anaquel'])
            ->only(['index', 'show']);

        Route::apiResource('bloques', ArchivoBloqueController::class)
            ->only(['index', 'show']);

        Route::apiResource('libros', ArchivoLibroController::class)
            ->only(['index', 'show']);

        Route::get('/libros/{libro}/folios', [ArchivoFolioController::class, 'index'])
            ->whereNumber('libro')
            ->name('libros.folios.index');

        Route::get('/libros/{libro}/documentos', [ArchivoDocumentoController::class, 'index'])
            ->whereNumber('libro')
            ->name('libros.documentos.index');

        Route::get('/libros/{libro}/documentos/{documento}', [ArchivoDocumentoController::class, 'show'])
            ->whereNumber('libro')
            ->whereNumber('documento')
            ->name('libros.documentos.show');

        Route::get('/libros/{libro}/movimientos', [ArchivoMovimientoController::class, 'index'])
            ->whereNumber('libro')
            ->name('libros.movimientos.index');

        Route::middleware('can:gestionar archivo siniestros')->group(function () {

            Route::apiResource('areas', ArchivoAreaController::class)
                ->only(['store', 'update']);

            Route::apiResource('anaqueles', ArchivoAnaquelController::class)
                ->parameters(['anaqueles' => 'anaquel'])
                ->only(['store', 'update']);

            Route::apiResource('bloques', ArchivoBloqueController::class)
                ->only(['store', 'update']);

            Route::apiResource('libros', ArchivoLibroController::class)
                ->only(['store', 'update']);

            Route::post('/libros/{libro}/asignar-destino', [ArchivoLibroController::class, 'asignarDestino'])
                ->whereNumber('libro')
                ->name('libros.asignar_destino');

            Route::post('/libros/{libro}/confirmar-ubicacion', [ArchivoMovimientoController::class, 'confirmarUbicacion'])
                ->whereNumber('libro')
                ->name('libros.confirmar_ubicacion');

            Route::post('/libros/{libro}/retirar', [ArchivoMovimientoController::class, 'retirar'])
                ->whereNumber('libro')
                ->name('libros.retirar');

            Route::post('/libros/{libro}/devolver', [ArchivoMovimientoController::class, 'devolver'])
                ->whereNumber('libro')
                ->name('libros.devolver');

            Route::post('/libros/{libro}/reubicar', [ArchivoMovimientoController::class, 'reubicar'])
                ->whereNumber('libro')
                ->name('libros.reubicar');
        });

        Route::middleware('can:digitalizar archivo siniestros')->group(function () {

            Route::post('/libros/{libro}/documentos', [ArchivoDocumentoController::class, 'store'])
                ->whereNumber('libro')
                ->name('libros.documentos.store');

            Route::post('/libros/{libro}/folios', [ArchivoFolioController::class, 'store'])
                ->whereNumber('libro')
                ->name('libros.folios.store');
        });
    });
