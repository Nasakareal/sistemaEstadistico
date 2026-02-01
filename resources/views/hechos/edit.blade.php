@extends('adminlte::page')

@section('title', 'Editar Hecho de Tránsito')

@section('content_header')
    <h1>Edición de Hecho de Tránsito</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title">Edite los Datos</h3>
                </div>

                <div class="card-body">
                    <form id="form_hecho" action="{{ route('hechos.update', $hecho->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- ✅ GEO (COORDENADAS) --}}
                        <input type="hidden" name="lat" id="lat" value="{{ old('lat', $hecho->lat) }}">
                        <input type="hidden" name="lng" id="lng" value="{{ old('lng', $hecho->lng) }}">
                        <input type="hidden" name="precision_m" id="precision_m" value="{{ old('precision_m', $hecho->precision_m) }}">
                        <input type="hidden" name="fuente_ubicacion" id="fuente_ubicacion" value="{{ old('fuente_ubicacion', $hecho->fuente_ubicacion) }}">

                        @php
                            /**
                             * ✅ AJUSTA ESTOS CAMPOS SI TUS COLUMNAS SE LLAMAN DIFERENTE
                             * (no te cambio nombres, solo lo dejo centralizado)
                             */
                            $fotoLugarPath     = $hecho->foto_lugar_path     ?? ($hecho->foto_lugar ?? null);
                            $fotoSituacionPath = $hecho->foto_situacion_path ?? ($hecho->foto_situacion ?? null);

                            $fotoLugarUrl     = $fotoLugarPath ? Storage::url($fotoLugarPath) : null;
                            $fotoSituacionUrl = $fotoSituacionPath ? Storage::url($fotoSituacionPath) : null;
                        @endphp

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="folio_c5i">Folio de C5i<span style="color: red">*</span></label>
                                    <input type="text" name="folio_c5i" id="folio_c5i"
                                           class="form-control @error('folio_c5i') is-invalid @enderror"
                                           value="{{ old('folio_c5i', $hecho->folio_c5i) }}"
                                           placeholder="Ingrese el folio de C5i" required>
                                    @error('folio_c5i')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="perito">Perito<span style="color: red">*</span></label>
                                    <input type="text" name="perito" id="perito"
                                           class="form-control @error('perito') is-invalid @enderror"
                                           value="{{ old('perito', $hecho->perito) }}"
                                           placeholder="Nombre del perito" required>
                                    @error('perito')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="autorizacion_practico">N° Autorización de Práctico</label>
                                    <input type="text" name="autorizacion_practico" id="autorizacion_practico"
                                           class="form-control @error('autorizacion_practico') is-invalid @enderror"
                                           value="{{ old('autorizacion_practico', $hecho->autorizacion_practico) }}"
                                           placeholder="Ingrese el número de autorización">
                                    @error('autorizacion_practico')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="unidad">Unidad<span style="color: red">*</span></label>
                                    <input type="text" name="unidad" id="unidad"
                                           class="form-control @error('unidad') is-invalid @enderror"
                                           value="{{ old('unidad', $hecho->unidad) }}"
                                           placeholder="Ingrese la unidad" required>
                                    @error('unidad')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora">Hora<span style="color: red">*</span></label>
                                    <input type="text"
                                           name="hora"
                                           id="hora"
                                           inputmode="numeric"
                                           autocomplete="off"
                                           class="form-control @error('hora') is-invalid @enderror"
                                           value="{{ old('hora', substr((string)($hecho->hora ?? ''), 0, 5)) }}"
                                           placeholder="HH:MM"
                                           required>
                                    @error('hora')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha">Fecha<span style="color: red">*</span></label>
                                    <input type="date" name="fecha" id="fecha"
                                           class="form-control @error('fecha') is-invalid @enderror"
                                           value="{{ old('fecha', $hecho->fecha) }}" required>
                                    @error('fecha')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sector">Sector<span style="color: red">*</span></label>
                                    <select name="sector" id="sector" class="form-control @error('sector') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione un sector</option>
                                        <option value="REVOLUCIÓN" {{ old('sector', $hecho->sector) == 'REVOLUCIÓN' ? 'selected' : '' }}>REVOLUCIÓN</option>
                                        <option value="NUEVA ESPAÑA" {{ old('sector', $hecho->sector) == 'NUEVA ESPAÑA' ? 'selected' : '' }}>NUEVA ESPAÑA</option>
                                        <option value="INDEPENDENCIA" {{ old('sector', $hecho->sector) == 'INDEPENDENCIA' ? 'selected' : '' }}>INDEPENDENCIA</option>
                                        <option value="REPÚBLICA" {{ old('sector', $hecho->sector) == 'REPÚBLICA' ? 'selected' : '' }}>REPÚBLICA</option>
                                        <option value="CENTRO" {{ old('sector', $hecho->sector) == 'CENTRO' ? 'selected' : '' }}>CENTRO</option>
                                    </select>
                                    @error('sector')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="municipio">Municipio<span style="color: red">*</span></label>
                                    <input type="text" name="municipio" id="municipio"
                                           class="form-control @error('municipio') is-invalid @enderror"
                                           value="{{ old('municipio', $hecho->municipio) }}"
                                           placeholder="Ingrese el municipio" required>
                                    @error('municipio')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="calle">Calle<span style="color: red">*</span></label>
                                    <input type="text" name="calle" id="calle"
                                           class="form-control @error('calle') is-invalid @enderror"
                                           value="{{ old('calle', $hecho->calle) }}"
                                           placeholder="Ingrese la calle" required>
                                    @error('calle')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="colonia">Colonia<span style="color: red">*</span></label>
                                    <input type="text" name="colonia" id="colonia"
                                           class="form-control @error('colonia') is-invalid @enderror"
                                           value="{{ old('colonia', $hecho->colonia) }}"
                                           placeholder="Ingrese la colonia" required>
                                    @error('colonia')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="entre_calles">Entre Calles</label>
                                    <input type="text" name="entre_calles" id="entre_calles"
                                           class="form-control @error('entre_calles') is-invalid @enderror"
                                           value="{{ old('entre_calles', $hecho->entre_calles) }}"
                                           placeholder="Ingrese entre calles">
                                    @error('entre_calles')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- ✅ UI GEO (EDIT) --}}
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Ubicación (coordenadas)</label>

                                    <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap;">
                                        <button type="button" class="btn btn-outline-info" id="btn_geo">
                                            <i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación
                                        </button>

                                        <span id="geo_status" class="help-muted"></span>

                                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn_geo_clear" style="display:none;">
                                            <i class="fa-solid fa-trash"></i> Quitar
                                        </button>
                                    </div>

                                    <small class="help-muted">
                                        Se guardan lat/lng para reportes y mapa.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipo_hecho">Tipo de Hecho de Tránsito<span style="color: red">*</span></label>
                                    <select name="tipo_hecho" id="tipo_hecho" class="form-control @error('tipo_hecho') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione el tipo de hecho</option>
                                        <option value="VOLCADURA" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'VOLCADURA' ? 'selected' : '' }}>VOLCADURA</option>
                                        <option value="SALIDA DE SUPERFICIE DE RODAMIENTO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'SALIDA DE SUPERFICIE DE RODAMIENTO' ? 'selected' : '' }}>SALIDA DE SUPERFICIE DE RODAMIENTO</option>
                                        <option value="SUBIDA AL CAMELLÓN" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'SUBIDA AL CAMELLÓN' ? 'selected' : '' }}>SUBIDA AL CAMELLÓN</option>
                                        <option value="CAIDA DE MOTOCICLETA" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'CAIDA DE MOTOCICLETA' ? 'selected' : '' }}>CAIDA DE MOTOCICLETA</option>
                                        <option value="COLISIÓN CON PEATÓN" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN CON PEATÓN' ? 'selected' : '' }}>COLISIÓN CON PEATÓN</option>
                                        <option value="COLISIÓN POR ALCANCE" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR ALCANCE' ? 'selected' : '' }}>COLISIÓN POR ALCANCE</option>
                                        <option value="COLISIÓN POR NO RESPETAR SEMÁFORO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR NO RESPETAR SEMÁFORO' ? 'selected' : '' }}>COLISIÓN POR NO RESPETAR SEMÁFORO</option>
                                        <option value="COLISIÓN POR INVASIÓN DE CARRIL" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR INVASIÓN DE CARRIL' ? 'selected' : '' }}>COLISIÓN POR INVASIÓN DE CARRIL</option>
                                        <option value="COLISIÓN POR CAMBIO DE CARRIL" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR CAMBIO DE CARRIL' ? 'selected' : '' }}>COLISIÓN POR CAMBIO DE CARRIL</option>
                                        <option value="COLISIÓN POR CORTE DE CIRCULACIÓN" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR CORTE DE CIRCULACIÓN' ? 'selected' : '' }}>COLISIÓN POR CORTE DE CIRCULACIÓN</option>
                                        <option value="COLISIÓN POR MANIOBRA DE REVERSA" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN POR MANIOBRA DE REVERSA' ? 'selected' : '' }}>COLISIÓN POR MANIOBRA DE REVERSA</option>
                                        <option value="COLISIÓN CONTRA OBJETO FIJO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'COLISIÓN CONTRA OBJETO FIJO' ? 'selected' : '' }}>COLISIÓN CONTRA OBJETO FIJO</option>
                                        <option value="CAIDA ACUATICA DE VEHÍCULO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'CAIDA ACUATICA DE VEHÍCULO' ? 'selected' : '' }}>CAIDA ACUATICA DE VEHÍCULO</option>
                                        <option value="DESBARRANCAMIENTO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'DESBARRANCAMIENTO' ? 'selected' : '' }}>DESBARRANCAMIENTO</option>
                                        <option value="INCENDIO" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'INCENDIO' ? 'selected' : '' }}>INCENDIO</option>
                                        <option value="EXPLOSIÓN" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'EXPLOSIÓN' ? 'selected' : '' }}>EXPLOSIÓN</option>
                                        <option value="Otro" {{ old('tipo_hecho', $hecho->tipo_hecho) == 'Otro' ? 'selected' : '' }}>Otro</option>
                                    </select>
                                    @error('tipo_hecho')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="superficie_via">Superficie de la Vía<span style="color: red">*</span></label>
                                    <input type="text" name="superficie_via" id="superficie_via"
                                           class="form-control @error('superficie_via') is-invalid @enderror"
                                           value="{{ old('superficie_via', $hecho->superficie_via) }}"
                                           placeholder="Ingrese la superficie de la vía" required>
                                    @error('superficie_via')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="tiempo">Tiempo<span style="color: red">*</span></label>
                                    <select name="tiempo" id="tiempo" class="form-control @error('tiempo') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione el tiempo</option>
                                        <option value="Día" {{ old('tiempo', $hecho->tiempo) == 'Día' ? 'selected' : '' }}>DÍA</option>
                                        <option value="Noche" {{ old('tiempo', $hecho->tiempo) == 'Noche' ? 'selected' : '' }}>NOCHE</option>
                                        <option value="Amanecer" {{ old('tiempo', $hecho->tiempo) == 'Amanecer' ? 'selected' : '' }}>AMANECER</option>
                                        <option value="Atardecer" {{ old('tiempo', $hecho->tiempo) == 'Atardecer' ? 'selected' : '' }}>ATARDECER</option>
                                    </select>
                                    @error('tiempo')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="clima">Clima<span style="color: red">*</span></label>
                                    <select name="clima" id="clima" class="form-control @error('clima') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione el clima</option>
                                        <option value="Bueno" {{ old('clima', $hecho->clima) == 'Bueno' ? 'selected' : '' }}>BUENO</option>
                                        <option value="Malo" {{ old('clima', $hecho->clima) == 'Malo' ? 'selected' : '' }}>MALO</option>
                                        <option value="Nublado" {{ old('clima', $hecho->clima) == 'Nublado' ? 'selected' : '' }}>NUBLADO</option>
                                        <option value="Lluvioso" {{ old('clima', $hecho->clima) == 'Lluvioso' ? 'selected' : '' }}>LLUVIOSO</option>
                                    </select>
                                    @error('clima')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="condiciones">Condiciones<span style="color: red">*</span></label>
                                    <select name="condiciones" id="condiciones" class="form-control @error('condiciones') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione las condiciones</option>
                                        <option value="Bueno" {{ old('condiciones', $hecho->condiciones) == 'Bueno' ? 'selected' : '' }}>BUENO</option>
                                        <option value="Regular" {{ old('condiciones', $hecho->condiciones) == 'Regular' ? 'selected' : '' }}>REGULAR</option>
                                        <option value="Malo" {{ old('condiciones', $hecho->condiciones) == 'Malo' ? 'selected' : '' }}>MALO</option>
                                    </select>
                                    @error('condiciones')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="situacion">Situación<span style="color: red">*</span></label>
                                    <select name="situacion" id="situacion" class="form-control @error('situacion') is-invalid @enderror" required>
                                        <option value="" disabled>Seleccione la situación</option>
                                        <option value="RESUELTO" {{ old('situacion', $hecho->situacion) == 'RESUELTO' ? 'selected' : '' }}>RESUELTO</option>
                                        <option value="PENDIENTE" {{ old('situacion', $hecho->situacion) == 'PENDIENTE' ? 'selected' : '' }}>PENDIENTE</option>
                                        <option value="TURNADO" {{ old('situacion', $hecho->situacion) == 'TURNADO' ? 'selected' : '' }}>TURNADO</option>
                                        <option value="REPORTE" {{ old('situacion', $hecho->situacion) == 'REPORTE' ? 'selected' : '' }}>REPORTE</option>
                                    </select>
                                    @error('situacion')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="control_transito">Control de Tránsito<span style="color: red">*</span></label>
                                    <input type="text" name="control_transito" id="control_transito"
                                           class="form-control @error('control_transito') is-invalid @enderror"
                                           value="{{ old('control_transito', $hecho->control_transito) }}"
                                           placeholder="Ingrese el control de tránsito" required>
                                    @error('control_transito')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="colision_camino">Colisión sobre el Camino<span style="color: red">*</span></label>
                                    <input type="text" name="colision_camino" id="colision_camino"
                                           class="form-control @error('colision_camino') is-invalid @enderror"
                                           value="{{ old('colision_camino', $hecho->colision_camino) }}"
                                           placeholder="Ingrese la colisión sobre el camino" required>
                                    @error('colision_camino')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="foto_lugar">Foto del lugar (opcional)</label>
                                    <input type="file" name="foto_lugar" id="foto_lugar" accept="image/*"
                                           class="form-control @error('foto_lugar') is-invalid @enderror">
                                    @error('foto_lugar')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror

                                    @if ($fotoLugarUrl)
                                        <div class="mt-2" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                                            <img src="{{ $fotoLugarUrl }}" alt="Foto lugar"
                                                 style="width:110px; height:80px; object-fit:cover; border-radius:12px; border:1px solid rgba(255,255,255,.12);">
                                            <a class="btn btn-sm btn-info" href="{{ $fotoLugarUrl }}" target="_blank" rel="noopener">
                                                <i class="fa-solid fa-up-right-from-square"></i> Ver
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" id="btn_quitar_foto_lugar">
                                                <i class="fa-solid fa-trash"></i> Quitar
                                            </button>
                                            <input type="hidden" name="quitar_foto_lugar" id="quitar_foto_lugar" value="0">
                                        </div>
                                        <small class="help-muted d-block mt-1">Si subes otra imagen, reemplaza la actual.</small>
                                    @endif

                                    <small id="foto_lugar_name" class="help-muted"></small>
                                </div>
                            </div>

                            <div class="col-md-6" id="foto_situacion_group" style="display:none;">
                                <div class="form-group">
                                    <label for="foto_situacion">
                                        Foto de la situación <span id="foto_situacion_required" style="color:red; display:none;">*</span>
                                    </label>
                                    <input type="file" name="foto_situacion" id="foto_situacion" accept="image/*"
                                           class="form-control @error('foto_situacion') is-invalid @enderror">
                                    @error('foto_situacion')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror

                                    <small id="foto_situacion_hint" class="help-muted"></small>

                                    @if ($fotoSituacionUrl)
                                        <div class="mt-2" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                                            <img src="{{ $fotoSituacionUrl }}" alt="Foto situación"
                                                 style="width:110px; height:80px; object-fit:cover; border-radius:12px; border:1px solid rgba(255,255,255,.12);">
                                            <a class="btn btn-sm btn-info" href="{{ $fotoSituacionUrl }}" target="_blank" rel="noopener">
                                                <i class="fa-solid fa-up-right-from.square"></i> Ver
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" id="btn_quitar_foto_situacion">
                                                <i class="fa-solid fa-trash"></i> Quitar
                                            </button>
                                            <input type="hidden" name="quitar_foto_situacion" id="quitar_foto_situacion" value="0">
                                        </div>
                                        <small class="help-muted d-block mt-1">Si subes otra imagen, reemplaza la actual.</small>
                                    @endif

                                    <small id="foto_situacion_name" class="help-muted d-block mt-1"></small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="checaron_antecedentes">Se checaron antecedentes?<span style="color: red">*</span></label>
                                    <select name="checaron_antecedentes" id="checaron_antecedentes" class="form-control">
                                        <option value="0" {{ old('checaron_antecedentes', (string)($hecho->checaron_antecedentes ?? '0')) == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('checaron_antecedentes', (string)($hecho->checaron_antecedentes ?? '0')) == '1' ? 'selected' : '' }}>Sí</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="causas">Causas<span style="color: red">*</span></label>
                                    <input type="text" name="causas" id="causas"
                                           class="form-control @error('causas') is-invalid @enderror"
                                           value="{{ old('causas', $hecho->causas) }}"
                                           placeholder="Ingrese las causas" required>
                                    @error('causas')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4" id="oficio_mp_group" style="display: none;">
                                <div class="form-group">
                                    <label for="oficio_mp">Oficio MP<span style="color: red">*</span></label>
                                    <input type="text" name="oficio_mp" id="oficio_mp"
                                           class="form-control @error('oficio_mp') is-invalid @enderror"
                                           value="{{ old('oficio_mp', $hecho->oficio_mp) }}"
                                           placeholder="Ingrese el número de oficio">
                                    @error('oficio_mp')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="vehiculos_mp">Vehículos presentados al MP</label>
                                    <input type="number" name="vehiculos_mp" id="vehiculos_mp"
                                           class="form-control @error('vehiculos_mp') is-invalid @enderror"
                                           value="{{ old('vehiculos_mp', $hecho->vehiculos_mp) }}"
                                           min="0" required>
                                    @error('vehiculos_mp')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="personas_mp">Personas presentadas al MP</label>
                                    <input type="number" name="personas_mp" id="personas_mp"
                                           class="form-control @error('personas_mp') is-invalid @enderror"
                                           value="{{ old('personas_mp', $hecho->personas_mp) }}"
                                           min="0" required>
                                    @error('personas_mp')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2" style="display:flex; align-items:end;">
                                <a href="{{ route('vehiculos.index', $hecho->id) }}" class="btn btn-success btn-lg w-100">
                                    <i class="fa-solid fa-car-side"></i> Vehículos
                                </a>
                            </div>

                            <div class="col-md-2" style="display:flex; align-items:end;">
                                <a href="{{ route('lesionados.index', $hecho->id) }}" class="btn btn-primary btn-lg w-100">
                                    <i class="fa-solid fa-user-injured"></i> Lesionados
                                </a>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa-solid fa-check"></i> Actualizar
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        .help-muted { color: rgba(234,240,255,.65); }

        .form-group label {
            font-weight: bold;
            color: #eaf0ff;
        }

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

        .form-control:focus,
        select:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(45,168,255,.35);
            border-color: rgba(45,168,255,.55);
        }

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

            // ===== GEO =====
            const btnGeo = document.getElementById('btn_geo');
            const btnGeoClear = document.getElementById('btn_geo_clear');
            const geoStatus = document.getElementById('geo_status');

            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');
            const precisionInput = document.getElementById('precision_m');
            const fuenteInput = document.getElementById('fuente_ubicacion');

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
                const mustShow = (val === 'RESUELTO' || val === 'TURNADO');

                if (mustShow) {
                    fotoSituacionGroup.style.display = 'block';
                    fotoSituacionRequired.style.display = 'inline';

                    // Si ya hay foto guardada, no forzamos required (solo si NO hay guardada)
                    const hayGuardada = {{ $fotoSituacionUrl ? 'true' : 'false' }};
                    fotoSituacionInput.required = !hayGuardada;

                    if (val === 'RESUELTO') {
                        fotoSituacionHint.textContent = hayGuardada
                            ? 'Ya existe foto del convenio. Si quieres cambiarla, sube otra.'
                            : 'Obligatoria: foto del convenio (RESUELTO).';
                    } else {
                        fotoSituacionHint.textContent = hayGuardada
                            ? 'Ya existe foto de la puesta. Si quieres cambiarla, sube otra.'
                            : 'Obligatoria: foto de la puesta (TURNADO).';
                    }
                } else {
                    fotoSituacionGroup.style.display = 'none';
                    fotoSituacionRequired.style.display = 'none';
                    fotoSituacionHint.textContent = '';
                    fotoSituacionInput.required = false;
                    fotoSituacionInput.value = '';
                    if (fotoSituacionName) fotoSituacionName.textContent = '';
                }
            }

            function setGeoUI() {
                const lat = latInput.value;
                const lng = lngInput.value;
                const prec = precisionInput.value;

                if (lat && lng) {
                    geoStatus.textContent = `OK: ${lat}, ${lng}` + (prec ? ` (±${prec} m)` : '');
                    btnGeoClear.style.display = 'inline-block';
                } else {
                    geoStatus.textContent = 'Sin coordenadas';
                    btnGeoClear.style.display = 'none';
                }
            }

            function toastError(msg) {
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Ubicación', text: msg });
                } else {
                    alert(msg);
                }
            }

            function toastOk(msg) {
                if (window.Swal) {
                    Swal.fire({ icon: 'success', title: 'Ubicación', text: msg, timer: 1600, showConfirmButton: false });
                }
            }

            // Inicializar
            toggleOficioMp();
            toggleFotoSituacion();
            setGeoUI();

            situacionSelect.addEventListener('change', function () {
                toggleOficioMp();
                toggleFotoSituacion();
            });

            // Flatpickr hora
            const horaInput = document.getElementById('hora');
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

            // Mostrar nombres de archivo
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

            // GEO: capturar
            if (btnGeo) {
                btnGeo.addEventListener('click', function () {
                    if (!navigator.geolocation) {
                        toastError('Tu navegador no soporta geolocalización.');
                        return;
                    }

                    geoStatus.textContent = 'Obteniendo ubicación...';

                    navigator.geolocation.getCurrentPosition(
                        function (pos) {
                            const lat = pos.coords.latitude;
                            const lng = pos.coords.longitude;
                            const acc = pos.coords.accuracy;

                            latInput.value = (typeof lat === 'number') ? lat.toFixed(7) : '';
                            lngInput.value = (typeof lng === 'number') ? lng.toFixed(7) : '';
                            precisionInput.value = (typeof acc === 'number') ? Math.round(acc) : '';
                            fuenteInput.value = 'GPS_WEB';

                            setGeoUI();
                            toastOk('Coordenadas capturadas.');
                        },
                        function (err) {
                            let msg = 'No se pudo obtener la ubicación.';
                            if (err && err.code === 1) msg = 'Permiso denegado. Activa la ubicación y permite el acceso.';
                            if (err && err.code === 2) msg = 'Ubicación no disponible.';
                            if (err && err.code === 3) msg = 'Tiempo de espera agotado. Intenta otra vez.';
                            setGeoUI();
                            toastError(msg);
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 12000,
                            maximumAge: 0
                        }
                    );
                });
            }

            if (btnGeoClear) {
                btnGeoClear.addEventListener('click', function () {
                    latInput.value = '';
                    lngInput.value = '';
                    precisionInput.value = '';
                    fuenteInput.value = '';
                    setGeoUI();
                });
            }

            // Quitar fotos (marca hidden=1)
            const btnQuitarLugar = document.getElementById('btn_quitar_foto_lugar');
            if (btnQuitarLugar) {
                btnQuitarLugar.addEventListener('click', function () {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Quitar foto',
                        text: 'Se quitará la foto del lugar al guardar cambios.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, quitar',
                        cancelButtonText: 'Cancelar'
                    }).then((r) => {
                        if (r.isConfirmed) {
                            const h = document.getElementById('quitar_foto_lugar');
                            if (h) h.value = '1';
                            if (fotoLugarInput) fotoLugarInput.value = '';
                            if (fotoLugarName) fotoLugarName.textContent = '';
                        }
                    });
                });
            }

            const btnQuitarSituacion = document.getElementById('btn_quitar_foto_situacion');
            if (btnQuitarSituacion) {
                btnQuitarSituacion.addEventListener('click', function () {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Quitar foto',
                        text: 'Se quitará la foto de la situación al guardar cambios.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, quitar',
                        cancelButtonText: 'Cancelar'
                    }).then((r) => {
                        if (r.isConfirmed) {
                            const h = document.getElementById('quitar_foto_situacion');
                            if (h) h.value = '1';
                            if (fotoSituacionInput) fotoSituacionInput.value = '';
                            if (fotoSituacionName) fotoSituacionName.textContent = '';
                            const val = situacionSelect.value;
                            if (val === 'RESUELTO' || val === 'TURNADO') {
                                fotoSituacionInput.required = true;
                                fotoSituacionHint.textContent = (val === 'RESUELTO')
                                    ? 'Obligatoria: sube otra foto del convenio (RESUELTO).'
                                    : 'Obligatoria: sube otra foto de la puesta (TURNADO).';
                            }
                        }
                    });
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
