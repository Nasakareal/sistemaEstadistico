@extends('adminlte::page')

@section('title', 'Crear Hecho de Tránsito')

@section('content_header')
    <h1>Creación de un Nuevo Hecho de Tránsito</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>

                <div class="card-body">
                    <form action="{{ route('hechos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Folio de C5i, Perito, N° Autorización de Práctico, Unidad -->
                        <div class="row">
                            <!-- Folio de C5i -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="folio_c5i">Folio de C5i<span style="color: red">*</span></label>
                                    <input type="text" name="folio_c5i" id="folio_c5i"
                                           class="form-control @error('folio_c5i') is-invalid @enderror"
                                           value="{{ old('folio_c5i') }}" placeholder="Ingrese el folio de C5i" required>
                                    @error('folio_c5i')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Perito -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="perito">Perito<span style="color: red">*</span></label>
                                    <input type="text" name="perito" id="perito"
                                           class="form-control @error('perito') is-invalid @enderror"
                                           value="{{ old('perito') }}" placeholder="Nombre del perito" required>
                                    @error('perito')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- N° Autorización de Práctico -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="autorizacion_practico">N° Autorización de Práctico</label>
                                    <input type="text" name="autorizacion_practico" id="autorizacion_practico"
                                           class="form-control @error('autorizacion_practico') is-invalid @enderror"
                                           value="{{ old('autorizacion_practico') }}" placeholder="Ingrese el número de autorización">
                                    @error('autorizacion_practico')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Unidad -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="unidad">Unidad<span style="color: red">*</span></label>
                                    <input type="text" name="unidad" id="unidad"
                                           class="form-control @error('unidad') is-invalid @enderror"
                                           value="{{ old('unidad') }}" placeholder="Ingrese la unidad" required>
                                    @error('unidad')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Hora, Fecha, Sector, Municipio -->
                        <div class="row">
                            <!-- Hora -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora">Hora<span style="color: red">*</span></label>

                                    {{-- Firefox no muestra reloj nativo para <input type="time">.
                                         Usamos flatpickr para tener selector de hora siempre. --}}
                                    <input
                                        type="text"
                                        name="hora"
                                        id="hora"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        class="form-control @error('hora') is-invalid @enderror"
                                        value="{{ old('hora') }}"
                                        placeholder="HH:MM"
                                        required
                                    >

                                    @error('hora')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Fecha -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha">Fecha <span style="color: red">*</span></label>
                                    <input type="date" name="fecha" id="fecha"
                                           class="form-control @error('fecha') is-invalid @enderror"
                                           value="{{ old('fecha', \Carbon\Carbon::now()->toDateString()) }}"
                                           readonly required>
                                    @error('fecha')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Sector -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sector">Sector<span style="color: red">*</span></label>
                                    <select name="sector" id="sector" class="form-control @error('sector') is-invalid @enderror" required>
                                        <option value="" disabled selected>Seleccione un sector</option>
                                        <option value="REVOLUCIÓN" {{ old('sector') == 'REVOLUCIÓN' ? 'selected' : '' }}>REVOLUCIÓN</option>
                                        <option value="NUEVA ESPAÑA" {{ old('sector') == 'NUEVA ESPAÑA' ? 'selected' : '' }}>NUEVA ESPAÑA</option>
                                        <option value="INDEPENDENCIA" {{ old('sector') == 'INDEPENDENCIA' ? 'selected' : '' }}>INDEPENDENCIA</option>
                                        <option value="REPÚBLICA" {{ old('sector') == 'REPÚBLICA' ? 'selected' : '' }}>REPÚBLICA</option>
                                        <option value="CENTRO" {{ old('sector') == 'CENTRO' ? 'selected' : '' }}>CENTRO</option>
                                    </select>
                                    @error('sector')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Municipio -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="municipio">Municipio<span style="color: red">*</span></label>
                                    <input type="text" name="municipio" id="municipio"
                                           class="form-control @error('municipio') is-invalid @enderror"
                                           value="{{ old('municipio') }}" placeholder="Ingrese el municipio" required>
                                    @error('municipio')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Calle, Colonia, Entre Calles -->
                        <div class="row">
                            <!-- Calle -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="calle">Calle<span style="color: red">*</span></label>
                                    <input type="text" name="calle" id="calle"
                                           class="form-control @error('calle') is-invalid @enderror"
                                           value="{{ old('calle') }}" placeholder="Ingrese la calle" required>
                                    @error('calle')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Colonia -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="colonia">Colonia<span style="color: red">*</span></label>
                                    <input type="text" name="colonia" id="colonia"
                                           class="form-control @error('colonia') is-invalid @enderror"
                                           value="{{ old('colonia') }}" placeholder="Ingrese la colonia" required>
                                    @error('colonia')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Entre Calles -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="entre_calles">Entre Calles</label>
                                    <input type="text" name="entre_calles" id="entre_calles"
                                           class="form-control @error('entre_calles') is-invalid @enderror"
                                           value="{{ old('entre_calles') }}" placeholder="Ingrese entre calles">
                                    @error('entre_calles')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Tipo de Hecho, Superficie, Tiempo, Clima, Condiciones -->
                        <div class="row">
                            <!-- Tipo de Hecho de Tránsito -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipo_hecho">Tipo de Hecho de Tránsito<span style="color: red">*</span></label>
                                    <select name="tipo_hecho" id="tipo_hecho" class="form-control @error('tipo_hecho') is-invalid @enderror" required>
                                        <option value="" disabled selected>Seleccione el tipo de hecho</option>
                                        <option value="VOLCADURA" {{ old('tipo_hecho') == 'VOLCADURA' ? 'selected' : '' }}>VOLCADURA</option>
                                        <option value="SALIDA DE SUPERFICIE DE RODAMIENTO" {{ old('tipo_hecho') == 'SALIDA DE SUPERFICIE DE RODAMIENTO' ? 'selected' : '' }}>SALIDA DE SUPERFICIE DE RODAMIENTO</option>
                                        <option value="SUBIDA AL CAMELLÓN" {{ old('tipo_hecho') == 'SUBIDA AL CAMELLÓN' ? 'selected' : '' }}>SUBIDA AL CAMELLÓN</option>
                                        <option value="CAIDA DE MOTOCICLETA" {{ old('tipo_hecho') == 'CAIDA DE MOTOCICLETA' ? 'selected' : '' }}>CAIDA DE MOTOCICLETA</option>
                                        <option value="COLISIÓN CON PEATÓN" {{ old('tipo_hecho') == 'COLISIÓN CON PEATÓN' ? 'selected' : '' }}>COLISIÓN CON PEATÓN</option>
                                        <option value="COLISIÓN POR ALCANCE" {{ old('tipo_hecho') == 'COLISIÓN POR ALCANCE' ? 'selected' : '' }}>COLISIÓN POR ALCANCE</option>
                                        <option value="COLISIÓN POR NO RESPETAR SEMÁFORO" {{ old('tipo_hecho') == 'COLISIÓN POR NO RESPETAR SEMÁFORO' ? 'selected' : '' }}>COLISIÓN POR NO RESPETAR SEMÁFORO</option>
                                        <option value="COLISIÓN POR INVASIÓN DE CARRIL" {{ old('tipo_hecho') == 'COLISIÓN POR INVASIÓN DE CARRIL' ? 'selected' : '' }}>COLISIÓN POR INVASIÓN DE CARRIL</option>
                                        <option value="COLISIÓN POR CAMBIO DE CARRIL" {{ old('tipo_hecho') == 'COLISIÓN POR CAMBIO DE CARRIL' ? 'selected' : '' }}>COLISIÓN POR CAMBIO DE CARRIL</option>
                                        <option value="COLISIÓN POR CORTE DE CIRCULACIÓN" {{ old('tipo_hecho') == 'COLISIÓN POR CORTE DE CIRCULACIÓN' ? 'selected' : '' }}>COLISIÓN POR CORTE DE CIRCULACIÓN</option>
                                        <option value="COLISIÓN POR MANIOBRA DE REVERSA" {{ old('tipo_hecho') == 'COLISIÓN POR MANIOBRA DE REVERSA' ? 'selected' : '' }}>COLISIÓN POR MANIOBRA DE REVERSA</option>
                                        <option value="COLISIÓN CONTRA OBJETO FIJO" {{ old('tipo_hecho') == 'COLISIÓN CONTRA OBJETO FIJO' ? 'selected' : '' }}>COLISIÓN CONTRA OBJETO FIJO</option>
                                        <option value="CAIDA ACUATICA DE VEHÍCULO" {{ old('tipo_hecho') == 'CAIDA ACUATICA DE VEHÍCULO' ? 'selected' : '' }}>CAIDA ACUATICA DE VEHÍCULO</option>
                                        <option value="DESBARRANCAMIENTO" {{ old('tipo_hecho') == 'DESBARRANCAMIENTO' ? 'selected' : '' }}>DESBARRANCAMIENTO</option>
                                        <option value="INCENDIO" {{ old('tipo_hecho') == 'INCENDIO' ? 'selected' : '' }}>INCENDIO</option>
                                        <option value="EXPLOSIÓN" {{ old('tipo_hecho') == 'EXPLOSIÓN' ? 'selected' : '' }}>EXPLOSIÓN</option>
                                        <option value="Otro" {{ old('tipo_hecho') == 'Otro' ? 'selected' : '' }}>Otro</option>
                                    </select>
                                    @error('tipo_hecho')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Superficie de la Vía -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="superficie_via">Superficie de la Vía<span style="color: red">*</span></label>
                                    <input type="text" name="superficie_via" id="superficie_via"
                                           class="form-control @error('superficie_via') is-invalid @enderror"
                                           value="{{ old('superficie_via') }}" placeholder="Ingrese la superficie de la vía" required>
                                    @error('superficie_via')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Tiempo -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="tiempo">Tiempo<span style="color: red">*</span></label>
                                    <select name="tiempo" id="tiempo" class="form-control @error('tiempo') is-invalid @enderror" required>
                                        <option value="" disabled selected>Seleccione el tiempo</option>
                                        <option value="Día" {{ old('tiempo') == 'Día' ? 'selected' : '' }}>DÍA</option>
                                        <option value="Noche" {{ old('tiempo') == 'Noche' ? 'selected' : '' }}>NOCHE</option>
                                        <option value="Amanecer" {{ old('tiempo') == 'Amanecer' ? 'selected' : '' }}>AMANECER</option>
                                        <option value="Atardecer" {{ old('tiempo') == 'Atardecer' ? 'selected' : '' }}>ATARDECER</option>
                                    </select>
                                    @error('tiempo')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Clima -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="clima">Clima<span style="color: red">*</span></label>
                                    <select name="clima" id="clima" class="form-control @error('clima') is-invalid @enderror" required>
                                        <option value="" disabled selected>Seleccione el clima</option>
                                        <option value="Bueno" {{ old('clima') == 'Bueno' ? 'selected' : '' }}>BUENO</option>
                                        <option value="Malo" {{ old('clima') == 'Malo' ? 'selected' : '' }}>MALO</option>
                                        <option value="Nublado" {{ old('clima') == 'Nublado' ? 'selected' : '' }}>NUBLADO</option>
                                        <option value="Lluvioso" {{ old('clima') == 'Lluvioso' ? 'selected' : '' }}>LLUVIOSO</option>
                                    </select>
                                    @error('clima')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Condiciones -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="condiciones">Condiciones<span style="color: red">*</span></label>
                                    <select name="condiciones" id="condiciones" class="form-control @error('condiciones') is-invalid @enderror" required>
                                        <option value="" disabled selected>Seleccione las condiciones</option>
                                        <option value="Bueno" {{ old('condiciones') == 'Bueno' ? 'selected' : '' }}>BUENO</option>
                                        <option value="Regular" {{ old('condiciones') == 'Regular' ? 'selected' : '' }}>REGULAR</option>
                                        <option value="Malo" {{ old('condiciones') == 'Malo' ? 'selected' : '' }}>MALO</option>
                                    </select>
                                    @error('condiciones')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Situación, Control de Tránsito, Colisión -->
                        <div class="row">
                            <!-- Situación -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="situacion">Situación<span style="color: red">*</span></label>
                                    <select name="situacion" id="situacion" class="form-control @error('situacion') is-invalid @enderror" required>
                                        <option value="" disabled selected>Seleccione la situación</option>
                                        <option value="RESUELTO" {{ old('situacion') == 'RESUELTO' ? 'selected' : '' }}>RESUELTO</option>
                                        <option value="PENDIENTE" {{ old('situacion') == 'PENDIENTE' ? 'selected' : '' }}>PENDIENTE</option>
                                        <option value="TURNADO" {{ old('situacion') == 'TURNADO' ? 'selected' : '' }}>TURNADO</option>
                                        <option value="REPORTE" {{ old('situacion') == 'REPORTE' ? 'selected' : '' }}>REPORTE</option>
                                    </select>
                                    @error('situacion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Control de Tránsito -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="control_transito">Control de Tránsito<span style="color: red">*</span></label>
                                    <input type="text" name="control_transito" id="control_transito"
                                           class="form-control @error('control_transito') is-invalid @enderror"
                                           value="{{ old('control_transito') }}" placeholder="Ingrese el control de tránsito" required>
                                    @error('control_transito')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Colisión -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="colision_camino">Colisión sobre el Camino<span style="color: red">*</span></label>
                                    <input type="text" name="colision_camino" id="colision_camino"
                                           class="form-control @error('colision_camino') is-invalid @enderror"
                                           value="{{ old('colision_camino') }}" placeholder="Ingrese la colisión sobre el camino" required>
                                    @error('colision_camino')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- ✅ NUEVAS FOTOS -->
                        <div class="row">
                            <!-- Foto del lugar -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="foto_lugar">Foto del lugar (opcional)</label>
                                    <input type="file"
                                           name="foto_lugar"
                                           id="foto_lugar"
                                           accept="image/*"
                                           class="form-control @error('foto_lugar') is-invalid @enderror">
                                    @error('foto_lugar')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    <small id="foto_lugar_name" class="help-muted"></small>
                                </div>
                            </div>

                            <!-- Foto situación (condicionada) -->
                            <div class="col-md-6" id="foto_situacion_group" style="display:none;">
                                <div class="form-group">
                                    <label for="foto_situacion">
                                        Foto de la situación <span id="foto_situacion_required" style="color:red; display:none;">*</span>
                                    </label>
                                    <input type="file"
                                           name="foto_situacion"
                                           id="foto_situacion"
                                           accept="image/*"
                                           class="form-control @error('foto_situacion') is-invalid @enderror">
                                    @error('foto_situacion')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    <small id="foto_situacion_hint" class="help-muted"></small><br>
                                    <small id="foto_situacion_name" class="help-muted"></small>
                                </div>
                            </div>
                        </div>

                        <!-- Antecedentes, Causas -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="checaron_antecedentes">Se checaron antecedentes?<span style="color: red">*</span></label>
                                    <select name="checaron_antecedentes" id="checaron_antecedentes" class="form-control">
                                        <option value="0" {{ old('checaron_antecedentes') == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('checaron_antecedentes') == '1' ? 'selected' : '' }}>Sí</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Causas -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="causas">Causas<span style="color: red">*</span></label>
                                    <input type="text" name="causas" id="causas"
                                           class="form-control @error('causas') is-invalid @enderror"
                                           value="{{ old('causas') }}" placeholder="Ingrese las causas" required>
                                    @error('causas')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Oficio MP, Vehículos y Personas presentados al MP -->
                        <div class="row">
                            <!-- Oficio MP -->
                            <div class="col-md-4" id="oficio_mp_group" style="display: none;">
                                <div class="form-group">
                                    <label for="oficio_mp">Oficio MP<span style="color: red">*</span></label>
                                    <input type="text" name="oficio_mp" id="oficio_mp"
                                           class="form-control @error('oficio_mp') is-invalid @enderror"
                                           value="{{ old('oficio_mp') }}" placeholder="Ingrese el número de oficio">
                                    @error('oficio_mp')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Vehículos presentados al MP -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="vehiculos_mp">Vehículos presentados al MP</label>
                                    <input type="number" name="vehiculos_mp" id="vehiculos_mp"
                                           class="form-control @error('vehiculos_mp') is-invalid @enderror"
                                           value="{{ old('vehiculos_mp', 0) }}" min="0" required>
                                    @error('vehiculos_mp')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Personas presentadas al MP -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="personas_mp">Personas presentadas al MP</label>
                                    <input type="number" name="personas_mp" id="personas_mp"
                                           class="form-control @error('personas_mp') is-invalid @enderror"
                                           value="{{ old('personas_mp', 0) }}" min="0" required>
                                    @error('personas_mp')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Submit y Cancelar -->
                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>

                                    <a href="{{ route('hechos.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    {{-- Flatpickr (selector de hora cross-browser, incluyendo Firefox) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .help-muted { color: rgba(234,240,255,.65); }

        /* ===== Labels ===== */
        .form-group label {
            font-weight: bold;
            color: #eaf0ff;
        }

        /* ===== Inputs / selects (modo oscuro AdminLTE) ===== */
        .form-control,
        select.form-control {
            color: #eaf0ff;
            background-color: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 12px;
        }

        .form-control::placeholder {
            color: rgba(234,240,255,.55);
        }

        /* ===== Opciones del dropdown ===== */
        select option {
            color: #111 !important;
            background-color: #ffffff !important;
        }

        select optgroup {
            color: #111 !important;
            background-color: #ffffff !important;
            font-weight: bold;
        }

        select option:checked {
            background-color: #dbeafe !important;
            color: #0b1220 !important;
        }

        select option:hover {
            background-color: #bfdbfe !important;
            color: #0b1220 !important;
        }

        /* ===== Focus ===== */
        .form-control:focus,
        select:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(45,168,255,.35);
            border-color: rgba(45,168,255,.55);
        }

        /* ===== Flatpickr: que se vea bien en modo oscuro ===== */
        .flatpickr-calendar {
            border-radius: 14px;
            overflow: hidden;
        }
        .flatpickr-time input,
        .flatpickr-time .flatpickr-am-pm {
            font-size: 16px;
        }
    </style>
@stop

@section('js')
    {{-- Flatpickr --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const situacionSelect = document.getElementById('situacion');
            const oficioMpGroup = document.getElementById('oficio_mp_group');

            const fotoSituacionGroup = document.getElementById('foto_situacion_group');
            const fotoSituacionInput = document.getElementById('foto_situacion');
            const fotoSituacionRequired = document.getElementById('foto_situacion_required');
            const fotoSituacionHint = document.getElementById('foto_situacion_hint');

            const fotoLugarInput = document.getElementById('foto_lugar');
            const fotoLugarName = document.getElementById('foto_lugar_name');
            const fotoSituacionName = document.getElementById('foto_situacion_name');

            function toggleOficioMp() {
                if (situacionSelect.value === 'TURNADO') {
                    oficioMpGroup.style.display = 'block';
                } else {
                    oficioMpGroup.style.display = 'none';
                    const oficio = document.getElementById('oficio_mp');
                    if (oficio) oficio.value = '';
                }
            }

            function toggleFotoSituacion() {
                const val = situacionSelect.value;

                // Solo aplica en RESUELTO y TURNADO
                const mustShow = (val === 'RESUELTO' || val === 'TURNADO');

                if (mustShow) {
                    fotoSituacionGroup.style.display = 'block';
                    fotoSituacionRequired.style.display = 'inline';

                    // HTML required (además de validación backend)
                    fotoSituacionInput.required = true;

                    if (val === 'RESUELTO') {
                        fotoSituacionHint.textContent = 'Obligatoria: foto del convenio (RESUELTO).';
                    } else {
                        fotoSituacionHint.textContent = 'Obligatoria: foto de la puesta (TURNADO).';
                    }
                } else {
                    fotoSituacionGroup.style.display = 'none';
                    fotoSituacionRequired.style.display = 'none';
                    fotoSituacionHint.textContent = '';

                    // Quitar required y limpiar archivo seleccionado
                    fotoSituacionInput.required = false;
                    fotoSituacionInput.value = '';
                    fotoSituacionName.textContent = '';
                }
            }

            // Inicializar en carga
            toggleOficioMp();
            toggleFotoSituacion();

            // Escuchar cambios
            situacionSelect.addEventListener('change', function () {
                toggleOficioMp();
                toggleFotoSituacion();
            });

            // ===== Hora (Firefox/Chrome/Edge): selector consistente =====
            const horaInput = document.getElementById('hora');

            // Normaliza valor a HH:MM si viene con segundos
            if (horaInput && horaInput.value) {
                horaInput.value = String(horaInput.value).substring(0, 5);
            }

            if (horaInput && window.flatpickr) {
                flatpickr(horaInput, {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: "H:i",
                    time_24hr: true,
                    allowInput: true
                });
            }

            // Mostrar nombre de archivo seleccionado
            if (fotoLugarInput) {
                fotoLugarInput.addEventListener('change', function () {
                    const f = fotoLugarInput.files && fotoLugarInput.files[0] ? fotoLugarInput.files[0].name : '';
                    fotoLugarName.textContent = f ? ('Archivo: ' + f) : '';
                });
            }

            if (fotoSituacionInput) {
                fotoSituacionInput.addEventListener('change', function () {
                    const f = fotoSituacionInput.files && fotoSituacionInput.files[0] ? fotoSituacionInput.files[0].name : '';
                    fotoSituacionName.textContent = f ? ('Archivo: ' + f) : '';
                });
            }
        });

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Errores en el formulario',
                html: `
                    <ul style="text-align: left;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Aceptar'
            });
        @endif
    </script>
@stop
