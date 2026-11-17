<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArchivoAreaController;
use App\Http\Controllers\ArchivoAnaquelController;
use App\Http\Controllers\ArchivoBloqueController;
use App\Http\Controllers\ArchivoLibroController;
use App\Http\Controllers\ArchivoDocumentoController;
use App\Http\Controllers\ArchivoFolioController;
use App\Http\Controllers\ArchivoMovimientoController;

Route::prefix('admin/settings/archivo-siniestros')
    ->name('archivo_siniestros.')
    ->middleware(['auth', 'can:ver archivo siniestros'])
    ->group(function () {

        Route::get('/', [ArchivoLibroController::class, 'index'])->name('index');
        Route::get('/buscar', [ArchivoLibroController::class, 'buscar'])->name('buscar');
        Route::get('/mapa', [ArchivoAnaquelController::class, 'mapa'])->name('mapa');

        Route::get('/libros/qr/{uuid}', [ArchivoLibroController::class, 'porQr'])->name('libros.qr');
        Route::get('/bloques/qr/{uuid}', [ArchivoBloqueController::class, 'porQr'])->name('bloques.qr');

        Route::get('/libros/{libro}', [ArchivoLibroController::class, 'show'])
            ->whereNumber('libro')
            ->name('libros.show');

        Route::get('/libros/{libro}/folios', [ArchivoFolioController::class, 'index'])
            ->whereNumber('libro')
            ->name('libros.folios.index');

        Route::get('/libros/{libro}/movimientos', [ArchivoMovimientoController::class, 'index'])
            ->whereNumber('libro')
            ->name('libros.movimientos.index');

        Route::get('/libros/{libro}/documentos/{documento}', [ArchivoDocumentoController::class, 'show'])
            ->whereNumber('libro')
            ->whereNumber('documento')
            ->name('libros.documentos.show');

        Route::middleware('can:gestionar archivo siniestros')->group(function () {

            Route::resource('areas', ArchivoAreaController::class)
                ->except(['show', 'destroy']);

            Route::resource('anaqueles', ArchivoAnaquelController::class)
                ->parameters(['anaqueles' => 'anaquel'])
                ->except(['show', 'destroy']);

            Route::resource('bloques', ArchivoBloqueController::class)
                ->except(['show', 'destroy']);

            Route::resource('libros', ArchivoLibroController::class)
                ->only(['create', 'store', 'edit', 'update']);

            Route::post('/libros/{libro}/asignar-destino', [ArchivoLibroController::class, 'asignarDestino'])
                ->name('libros.asignar_destino');

            Route::post('/libros/{libro}/confirmar-ubicacion', [ArchivoMovimientoController::class, 'confirmarUbicacion'])
                ->name('libros.confirmar_ubicacion');

            Route::post('/libros/{libro}/retirar', [ArchivoMovimientoController::class, 'retirar'])
                ->name('libros.retirar');

            Route::post('/libros/{libro}/devolver', [ArchivoMovimientoController::class, 'devolver'])
                ->name('libros.devolver');

            Route::post('/libros/{libro}/reubicar', [ArchivoMovimientoController::class, 'reubicar'])
                ->name('libros.reubicar');
        });

        Route::middleware('can:digitalizar archivo siniestros')->group(function () {

            Route::post('/libros/{libro}/documentos', [ArchivoDocumentoController::class, 'store'])
                ->name('libros.documentos.store');

            Route::post('/libros/{libro}/folios', [ArchivoFolioController::class, 'store'])
                ->name('libros.folios.store');
        });
    });
