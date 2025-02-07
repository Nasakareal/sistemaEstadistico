<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Campañas
Route::get('/campanas', [App\Http\Controllers\CampanaController::class, 'index'])->name('campanas.index');

// Contacto
Route::get('/contacto', [App\Http\Controllers\ContactoController::class, 'index'])->name('contacto.index');

// Apoyos
Route::get('/apoyo', [App\Http\Controllers\ApoyoController::class, 'index'])->name('apoyo.index');

Auth::routes();

Route::get('/servicios/grafico', [App\Http\Controllers\ServicioController::class, 'grafico'])->name('servicios.grafico');

// Home
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rutas para Listas
Route::prefix('listas')->middleware('can:ver listas')->group(function () {
    Route::get('/', [App\Http\Controllers\ListaController::class, 'index'])->name('listas.index');
    Route::get('/create', [App\Http\Controllers\ListaController::class, 'create'])->middleware('can:crear listas')->name('listas.create');
    Route::post('/', [App\Http\Controllers\ListaController::class, 'store'])->middleware('can:crear listas')->name('listas.store');
    Route::get('/{lista}', [App\Http\Controllers\ListaController::class, 'show'])->middleware('can:ver listas')->name('listas.show');
    Route::get('/{lista}/edit', [App\Http\Controllers\ListaController::class, 'edit'])->middleware('can:editar listas')->name('listas.edit');
    Route::put('/{lista}', [App\Http\Controllers\ListaController::class, 'update'])->middleware('can:editar listas')->name('listas.update');
    Route::delete('/{lista}', [App\Http\Controllers\ListaController::class, 'destroy'])->middleware('can:eliminar listas')->name('listas.destroy');
});

// Rutas para Formatos
Route::prefix('formatos')->middleware('can:ver formatos')->group(function () {
    Route::get('/', [App\Http\Controllers\FormatoController::class, 'index'])->name('formatos.index');
    Route::get('/create', [App\Http\Controllers\FormatoController::class, 'create'])->middleware('can:crear formatos')->name('formatos.create');
    Route::post('/', [App\Http\Controllers\FormatoController::class, 'store'])->middleware('can:crear formatos')->name('formatos.store');
    Route::get('/{formato}', [App\Http\Controllers\FormatoController::class, 'show'])->middleware('can:ver formatos')->name('formatos.show');
    Route::get('/{formato}/edit', [App\Http\Controllers\FormatoController::class, 'edit'])->middleware('can:editar formatos')->name('formatos.edit');
    Route::put('/{formato}', [App\Http\Controllers\FormatoController::class, 'update'])->middleware('can:editar formatos')->name('formatos.update');
    Route::delete('/{formato}', [App\Http\Controllers\FormatoController::class, 'destroy'])->middleware('can:eliminar formatos')->name('formatos.destroy');
});

// Rutas para Dictamenes
Route::prefix('dictamenes')->middleware('can:ver dictamenes')->group(function () {
    Route::get('/', [App\Http\Controllers\DictamenController::class, 'index'])->name('dictamenes.index');
    Route::get('/create', [App\Http\Controllers\DictamenController::class, 'create'])->middleware('can:crear dictamenes')->name('dictamenes.create');
    Route::post('/', [App\Http\Controllers\DictamenController::class, 'store'])->middleware('can:crear dictamenes')->name('dictamenes.store');
    Route::get('/{dictamen}', [App\Http\Controllers\DictamenController::class, 'show'])->middleware('can:ver dictamenes')->name('dictamenes.show');
    Route::get('/{dictamen}/edit', [App\Http\Controllers\DictamenController::class, 'edit'])->middleware('can:editar dictamenes')->name('dictamenes.edit');
    Route::put('/{dictamen}', [App\Http\Controllers\DictamenController::class, 'update'])->middleware('can:editar dictamenes')->name('dictamenes.update');
    Route::delete('/{dictamen}', [App\Http\Controllers\DictamenController::class, 'destroy'])->middleware('can:eliminar dictamenes')->name('dictamenes.destroy');
});

// Rutas para Grúas
Route::prefix('gruas')->middleware('can:ver gruas')->group(function () {
    Route::get('/', [App\Http\Controllers\GruaController::class, 'index'])->name('gruas.index');
    Route::get('/create', [App\Http\Controllers\GruaController::class, 'create'])->middleware('can:crear gruas')->name('gruas.create');
    Route::post('/', [App\Http\Controllers\GruaController::class, 'store'])->middleware('can:crear gruas')->name('gruas.store');
    Route::get('/{grua}', [App\Http\Controllers\GruaController::class, 'show'])->middleware('can:ver gruas')->name('gruas.show');
    Route::get('/{grua}/edit', [App\Http\Controllers\GruaController::class, 'edit'])->middleware('can:editar gruas')->name('gruas.edit');
    Route::put('/{grua}', [App\Http\Controllers\GruaController::class, 'update'])->middleware('can:editar gruas')->name('gruas.update');
    Route::delete('/{grua}', [App\Http\Controllers\GruaController::class, 'destroy'])->middleware('can:eliminar gruas')->name('gruas.destroy');

    // Rutas para Servicios relacionados a Grúas
    Route::prefix('/{grua}/servicios')->group(function () {
        Route::get('/', [App\Http\Controllers\ServicioController::class, 'index'])->name('servicios.index');
        Route::get('/create', [App\Http\Controllers\ServicioController::class, 'create'])->name('servicios.create');
        Route::post('/', [App\Http\Controllers\ServicioController::class, 'store'])->name('servicios.store');
        Route::get('/{servicio}', [App\Http\Controllers\ServicioController::class, 'show'])->name('servicios.show');
        Route::get('/{servicio}/edit', [App\Http\Controllers\ServicioController::class, 'edit'])->name('servicios.edit');
        Route::put('/{servicio}', [App\Http\Controllers\ServicioController::class, 'update'])->name('servicios.update');
        Route::delete('/{servicio}', [App\Http\Controllers\ServicioController::class, 'destroy'])->name('servicios.destroy');
    });
});


// Rutas para Hechos
Route::prefix('hechos')->middleware('can:ver hechos')->group(function () {
    Route::get('/', [App\Http\Controllers\HechosController::class, 'index'])->name('hechos.index');
    Route::get('/create', [App\Http\Controllers\HechosController::class, 'create'])->middleware('can:crear hechos')->name('hechos.create');
    Route::post('/', [App\Http\Controllers\HechosController::class, 'store'])->middleware('can:crear hechos')->name('hechos.store');
    Route::get('/{hecho}', [App\Http\Controllers\HechosController::class, 'show'])->middleware('can:ver hechos')->name('hechos.show');
    Route::get('/{hecho}/edit', [App\Http\Controllers\HechosController::class, 'edit'])->middleware('can:editar hechos')->name('hechos.edit');
    Route::put('/{hecho}', [App\Http\Controllers\HechosController::class, 'update'])->middleware('can:editar hechos')->name('hechos.update');
    Route::delete('/{hecho}', [App\Http\Controllers\HechosController::class, 'destroy'])->middleware('can:eliminar hechos')->name('hechos.destroy');

    // Rutas anidadas para Vehículos
    Route::prefix('/{hecho}/vehiculos')->middleware('can:crear vehiculos')->group(function () {
        Route::get('/', [App\Http\Controllers\VehiculosController::class, 'index'])->name('vehiculos.index');
        Route::get('/create', [App\Http\Controllers\VehiculosController::class, 'create'])->middleware('can:crear vehiculos')->name('vehiculos.create');
        Route::post('/', [App\Http\Controllers\VehiculosController::class, 'store'])->middleware('can:crear vehiculos')->name('vehiculos.store');
        Route::get('/{vehiculo}/edit', [App\Http\Controllers\VehiculosController::class, 'edit'])->middleware('can:editar vehiculos')->name('vehiculos.edit');
        Route::put('/{vehiculo}', [App\Http\Controllers\VehiculosController::class, 'update'])->middleware('can:editar vehiculos')->name('vehiculos.update');
        Route::delete('/{vehiculo}', [App\Http\Controllers\VehiculosController::class, 'destroy'])->middleware('can:eliminar vehiculos')->name('vehiculos.destroy');
    });
});


// Configuraciones generales
Route::prefix('admin/settings')->middleware('can:ver configuraciones')->group(function () {
    // Configuración general
    Route::get('/', [App\Http\Controllers\SettingsController::class, 'index'])->name('settings.index');

    // Usuarios
    Route::prefix('users')->middleware('can:ver usuarios')->group(function () {
        Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::get('/create', [App\Http\Controllers\UserController::class, 'create'])->middleware('can:crear usuarios')->name('users.create');
        Route::post('/', [App\Http\Controllers\UserController::class, 'store'])->middleware('can:crear usuarios')->name('users.store');
        Route::get('/{user}', [App\Http\Controllers\UserController::class, 'show'])->middleware('can:ver usuarios')->name('users.show');
        Route::get('/{user}/edit', [App\Http\Controllers\UserController::class, 'edit'])->middleware('can:editar usuarios')->name('users.edit');
        Route::put('/{user}', [App\Http\Controllers\UserController::class, 'update'])->middleware('can:editar usuarios')->name('users.update');
        Route::delete('/{user}', [App\Http\Controllers\UserController::class, 'destroy'])->middleware('can:eliminar usuarios')->name('users.destroy');
    });


    // Roles
    Route::prefix('roles')->middleware('can:ver roles')->group(function () {
        Route::get('/', [App\Http\Controllers\RoleController::class, 'index'])->name('roles.index');
        Route::get('/create', [App\Http\Controllers\RoleController::class, 'create'])->middleware('can:crear roles')->name('roles.create');
        Route::post('/', [App\Http\Controllers\RoleController::class, 'store'])->middleware('can:crear roles')->name('roles.store');
        Route::get('/{role}', [App\Http\Controllers\RoleController::class, 'show'])->name('roles.show');
        Route::get('/{role}/edit', [App\Http\Controllers\RoleController::class, 'edit'])->middleware('can:editar roles')->name('roles.edit');
        Route::put('/{role}', [App\Http\Controllers\RoleController::class, 'update'])->middleware('can:editar roles')->name('roles.update');
        Route::delete('/{role}', [App\Http\Controllers\RoleController::class, 'destroy'])->middleware('can:eliminar roles')->name('roles.destroy');
        Route::get('/{role}/permissions', [App\Http\Controllers\RoleController::class, 'permissions'])->middleware('can:editar roles')->name('roles.permissions');
        Route::post('/{role}/permissions', [App\Http\Controllers\RoleController::class, 'assignPermissions'])->middleware('can:editar roles')->name('roles.assignPermissions');
    });
});

Route::get('/prueba-404', function () {
    return response()->view('errors.404', [], 404);
});

