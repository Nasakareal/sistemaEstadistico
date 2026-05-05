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
                    <form id="form_hecho" action="{{ route('hechos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
                        <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
                        <input type="hidden" name="calidad_geo" id="calidad_geo" value="{{ old('calidad_geo') }}">
                        <input type="hidden" name="fuente_ubicacion" id="fuente_ubicacion" value="{{ old('fuente_ubicacion') }}">

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="folio_c5i">Folio de C5i</label>
                                    <input type="text" name="folio_c5i" id="folio_c5i"
                                           class="form-control @error('folio_c5i') is-invalid @enderror"
                                           value="{{ old('folio_c5i') }}" placeholder="Ingrese el folio de C5i">
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
                                           value="{{ old('perito') }}" placeholder="Nombre del perito" required>
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
                                           value="{{ old('autorizacion_practico') }}" placeholder="Ingrese el número de autorización">
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
                                           value="{{ old('unidad') }}" placeholder="Ingrese la unidad" required>
                                    @error('unidad')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            @if(!auth()->user()->hasRole('Perito'))
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="hora">Hora<span style="color: red">*</span></label>
                                    <input type="text"
                                           name="hora"
                                           id="hora"
                                           inputmode="numeric"
                                           autocomplete="off"
                                           class="form-control @error('hora') is-invalid @enderror"
                                           value="{{ old('hora') }}"
                                           placeholder="HH:MM"
                                           required>
                                    @error('hora')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            @endif

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha">Fecha <span style="color: red">*</span></label>
                                    <input type="date" name="fecha" id="fecha"
                                           class="form-control @error('fecha') is-invalid @enderror"
                                           value="{{ old('fecha', \Carbon\Carbon::now()->toDateString()) }}"
                                           readonly required>
                                    @error('fecha')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sector">
                                        Sector
                                        @if((int) (auth()->user()->unidad_id ?? 0) !== 2)
                                            <span style="color: red">*</span>
                                        @endif
                                    </label>
                                    <select name="sector" id="sector"
                                            class="form-control @error('sector') is-invalid @enderror"
                                            @if((int) (auth()->user()->unidad_id ?? 0) !== 2) required @endif>
                                        <option value="" disabled {{ old('sector') ? '' : 'selected' }}>Seleccione un sector</option>
                                        <option value="REVOLUCIÓN" {{ old('sector') == 'REVOLUCIÓN' ? 'selected' : '' }}>REVOLUCIÓN</option>
                                        <option value="NUEVA ESPAÑA" {{ old('sector') == 'NUEVA ESPAÑA' ? 'selected' : '' }}>NUEVA ESPAÑA</option>
                                        <option value="INDEPENDENCIA" {{ old('sector') == 'INDEPENDENCIA' ? 'selected' : '' }}>INDEPENDENCIA</option>
                                        <option value="REPÚBLICA" {{ old('sector') == 'REPÚBLICA' ? 'selected' : '' }}>REPÚBLICA</option>
                                        <option value="CENTRO" {{ old('sector') == 'CENTRO' ? 'selected' : '' }}>CENTRO</option>
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
                                           value="{{ old('municipio') }}" placeholder="Ingrese el municipio" required>
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
                                           value="{{ old('calle') }}" placeholder="Ingrese la calle" required>
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
                                           value="{{ old('colonia') }}" placeholder="Ingrese la colonia" required>
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
                                           value="{{ old('entre_calles') }}" placeholder="Ingrese entre calles">
                                    @error('entre_calles')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Ubicación (coordenadas) <span style="color:red">*</span></label>

                                    <div class="d-flex align-items-center" style="gap:10px; flex-wrap:wrap;">
                                        <button type="button" class="btn btn-outline-info" id="btn_geo">
                                            <i class="fa-solid fa-location-crosshairs"></i> Usar mi ubicación
                                        </button>

                                        <span id="geo_status" class="help-muted">
                                            @if(old('lat') && old('lng'))
                                                OK: {{ old('lat') }}, {{ old('lng') }}
                                            @else
                                                Sin coordenadas
                                            @endif
                                        </span>

                                        <button type="button" class="btn btn-outline-danger btn-sm" id="btn_geo_clear" style="display:none;">
                                            <i class="fa-solid fa-trash"></i> Quitar
                                        </button>
                                    </div>

                                    <small class="help-muted">
                                        Es obligatorio capturar lat/lng. Si el navegador pregunta permisos, acepta.
                                    </small>

                                    @error('lat')
                                        <div class="text-danger small"><strong>{{ $message }}</strong></div>
                                    @enderror
                                    @error('lng')
                                        <div class="text-danger small"><strong>{{ $message }}</strong></div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tipo_hecho">Tipo de Hecho de Tránsito<span style="color: red">*</span></label>
                                    <select name="tipo_hecho" id="tipo_hecho" class="form-control @error('tipo_hecho') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('tipo_hecho') ? '' : 'selected' }}>Seleccione el tipo de hecho</option>
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
                                           value="{{ old('superficie_via') }}" placeholder="Ingrese la superficie de la vía" required>
                                    @error('superficie_via')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="tiempo">Tiempo<span style="color: red">*</span></label>
                                    <select name="tiempo" id="tiempo" class="form-control @error('tiempo') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('tiempo') ? '' : 'selected' }}>Seleccione el tiempo</option>
                                        <option value="Día" {{ old('tiempo') == 'Día' ? 'selected' : '' }}>DÍA</option>
                                        <option value="Noche" {{ old('tiempo') == 'Noche' ? 'selected' : '' }}>NOCHE</option>
                                        <option value="Amanecer" {{ old('tiempo') == 'Amanecer' ? 'selected' : '' }}>AMANECER</option>
                                        <option value="Atardecer" {{ old('tiempo') == 'Atardecer' ? 'selected' : '' }}>ATARDECER</option>
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
                                        <option value="" disabled {{ old('clima') ? '' : 'selected' }}>Seleccione el clima</option>
                                        <option value="Bueno" {{ old('clima') == 'Bueno' ? 'selected' : '' }}>BUENO</option>
                                        <option value="Malo" {{ old('clima') == 'Malo' ? 'selected' : '' }}>MALO</option>
                                        <option value="Nublado" {{ old('clima') == 'Nublado' ? 'selected' : '' }}>NUBLADO</option>
                                        <option value="Lluvioso" {{ old('clima') == 'Lluvioso' ? 'selected' : '' }}>LLUVIOSO</option>
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
                                        <option value="" disabled {{ old('condiciones') ? '' : 'selected' }}>Seleccione las condiciones</option>
                                        <option value="Bueno" {{ old('condiciones') == 'Bueno' ? 'selected' : '' }}>BUENO</option>
                                        <option value="Regular" {{ old('condiciones') == 'Regular' ? 'selected' : '' }}>REGULAR</option>
                                        <option value="Malo" {{ old('condiciones') == 'Malo' ? 'selected' : '' }}>MALO</option>
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
                                        <option value="" disabled {{ old('situacion') ? '' : 'selected' }}>Seleccione la situación</option>
                                        <option value="RESUELTO" {{ old('situacion') == 'RESUELTO' ? 'selected' : '' }}>RESUELTO</option>
                                        <option value="PENDIENTE" {{ old('situacion') == 'PENDIENTE' ? 'selected' : '' }}>PENDIENTE</option>
                                        <option value="TURNADO" {{ old('situacion') == 'TURNADO' ? 'selected' : '' }}>TURNADO</option>
                                        <option value="REPORTE" {{ old('situacion') == 'REPORTE' ? 'selected' : '' }}>REPORTE</option>
                                    </select>
                                    @error('situacion')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4" id="dictamen_group" style="display:none;">
                                <div class="form-group">
                                    <label for="dictamen_id">Dictamen / MP <span style="color:red">*</span></label>
                                    <select name="dictamen_id" id="dictamen_id"
                                            class="form-control @error('dictamen_id') is-invalid @enderror">
                                        <option value="" disabled {{ old('dictamen_id') ? '' : 'selected' }}>Seleccione un dictamen</option>

                                        @if(isset($dictamenesDisponibles))
                                            @foreach($dictamenesDisponibles as $d)
                                                @php
                                                    $oficio = $d->numero_dictamen . '/' . $d->anio . ' ' . $d->nombre_mp;
                                                @endphp
                                                <option value="{{ $d->id }}"
                                                    data-oficio="{{ $oficio }}"
                                                    {{ (string)old('dictamen_id') === (string)$d->id ? 'selected' : '' }}>
                                                    {{ $oficio }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('dictamen_id')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small class="help-muted">Solo aparecen dictámenes no usados en otros hechos.</small>
                                </div>
                            </div>

                            <input type="hidden" name="oficio_mp" id="oficio_mp" value="{{ old('oficio_mp') }}">
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="control_transito">Control de Tránsito<span style="color: red">*</span></label>
                                    <input type="text" name="control_transito" id="control_transito"
                                           class="form-control @error('control_transito') is-invalid @enderror"
                                           value="{{ old('control_transito') }}" placeholder="Ingrese el control de tránsito" required>
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
                                           value="{{ old('colision_camino') }}" placeholder="Ingrese la colisión sobre el camino" required>
                                    @error('colision_camino')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="vehiculos_mp">Vehículos presentados al MP</label>
                                    <input type="number" name="vehiculos_mp" id="vehiculos_mp"
                                           class="form-control @error('vehiculos_mp') is-invalid @enderror"
                                           value="{{ old('vehiculos_mp', 0) }}" min="0" required>
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
                                           value="{{ old('personas_mp', 0) }}" min="0" required>
                                    @error('personas_mp')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>¿Se checaron antecedentes?</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="checaron_antecedentes"
                                               name="checaron_antecedentes"
                                               value="1"
                                               {{ old('checaron_antecedentes') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="checaron_antecedentes">Sí</label>
                                    </div>
                                    @error('checaron_antecedentes')
                                        <div class="text-danger small"><strong>{{ $message }}</strong></div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>¿Daños patrimoniales?</label>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox"
                                               class="custom-control-input"
                                               id="danos_patrimoniales"
                                               name="danos_patrimoniales"
                                               value="1"
                                               {{ old('danos_patrimoniales') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="danos_patrimoniales">Sí</label>
                                    </div>
                                    @error('danos_patrimoniales')
                                        <div class="text-danger small"><strong>{{ $message }}</strong></div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="causas">Causas<span style="color: red">*</span></label>
                                    <input type="text" name="causas" id="causas"
                                           class="form-control @error('causas') is-invalid @enderror"
                                           value="{{ old('causas') }}" placeholder="Ingrese las causas" required>
                                    @error('causas')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="responsable">Responsable<span style="color: red">*</span></label>
                                    <input type="text" name="responsable" id="responsable"
                                           class="form-control @error('responsable') is-invalid @enderror"
                                           value="{{ old('responsable') }}" placeholder="Ingrese el responsable" required>
                                    @error('responsable')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row" id="danos_patrimoniales_fields" style="display:none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="propiedades_afectadas">Propiedades afectadas</label>
                                    <input type="text"
                                           name="propiedades_afectadas"
                                           id="propiedades_afectadas"
                                           class="form-control @error('propiedades_afectadas') is-invalid @enderror"
                                           value="{{ old('propiedades_afectadas') }}"
                                           placeholder="Ingrese las propiedades afectadas">
                                    @error('propiedades_afectadas')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="monto_danos_patrimoniales">Monto de daños patrimoniales</label>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           name="monto_danos_patrimoniales"
                                           id="monto_danos_patrimoniales"
                                           class="form-control @error('monto_danos_patrimoniales') is-invalid @enderror"
                                           value="{{ old('monto_danos_patrimoniales') }}"
                                           placeholder="Ingrese el monto">
                                    @error('monto_danos_patrimoniales')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="foto_lugar">Foto del lugar (opcional)</label>
                                    <input type="file"
                                           name="foto_lugar"
                                           id="foto_lugar"
                                           accept="image/*"
                                           class="form-control @error('foto_lugar') is-invalid @enderror">
                                    @error('foto_lugar')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small id="foto_lugar_name" class="help-muted"></small>
                                </div>
                            </div>

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
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    <small id="foto_situacion_hint" class="help-muted"></small><br>
                                    <small id="foto_situacion_name" class="help-muted"></small>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <button id="btn_submit" type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>

                                    <a href="{{ route('hechos.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
                                <small id="geo_required_hint" class="help-muted" style="display:none;">
                                    Captura la ubicación para poder registrar.
                                </small>
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

        @include('hechos.partials.turnado_mp_styles')
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('form_hecho');
            const btnSubmit = document.getElementById('btn_submit');
            const geoRequiredHint = document.getElementById('geo_required_hint');

            const situacionSelect = document.getElementById('situacion');

            const dictamenGroup = document.getElementById('dictamen_group');
            const dictamenSelect = document.getElementById('dictamen_id');

            const fotoSituacionGroup = document.getElementById('foto_situacion_group');
            const fotoSituacionInput = document.getElementById('foto_situacion');
            const fotoSituacionRequired = document.getElementById('foto_situacion_required');
            const fotoSituacionHint = document.getElementById('foto_situacion_hint');

            const fotoLugarInput = document.getElementById('foto_lugar');
            const fotoLugarName = document.getElementById('foto_lugar_name');
            const fotoSituacionName = document.getElementById('foto_situacion_name');

            const btnGeo = document.getElementById('btn_geo');
            const btnGeoClear = document.getElementById('btn_geo_clear');
            const geoStatus = document.getElementById('geo_status');

            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');
            const precisionInput = document.getElementById('calidad_geo');
            const fuenteInput = document.getElementById('fuente_ubicacion');

            const danosSwitch = document.getElementById('danos_patrimoniales');
            const danosFields = document.getElementById('danos_patrimoniales_fields');
            const propiedadesAfectadasInput = document.getElementById('propiedades_afectadas');
            const montoDanosInput = document.getElementById('monto_danos_patrimoniales');

            function fillOficioFromDictamen() {
                const oficioInput = document.getElementById('oficio_mp');
                if (!oficioInput) return;

                if (!dictamenSelect) {
                    oficioInput.value = '';
                    return;
                }

                const selectedOption = dictamenSelect.options[dictamenSelect.selectedIndex];
                const oficio = selectedOption && selectedOption.dataset && selectedOption.dataset.oficio
                    ? String(selectedOption.dataset.oficio).trim()
                    : '';

                oficioInput.value = oficio;
            }

            function toggleTurnado() {
                if (!situacionSelect) return;

                const isTurnado = situacionSelect.value === 'TURNADO';

                if (isTurnado) {
                    if (dictamenGroup) dictamenGroup.style.display = 'block';
                    if (dictamenSelect) dictamenSelect.required = true;
                    fillOficioFromDictamen();
                } else {
                    if (dictamenGroup) dictamenGroup.style.display = 'none';

                    if (dictamenSelect) {
                        dictamenSelect.required = false;
                        dictamenSelect.value = '';
                    }

                    const oficioInput = document.getElementById('oficio_mp');
                    if (oficioInput) oficioInput.value = '';
                }
            }

            function toggleFotoSituacion() {
                if (!situacionSelect) return;

                const mustShow = situacionSelect.value === 'RESUELTO';

                if (mustShow) {
                    if (fotoSituacionGroup) fotoSituacionGroup.style.display = 'block';
                    if (fotoSituacionRequired) fotoSituacionRequired.style.display = 'inline';
                    if (fotoSituacionInput) fotoSituacionInput.required = true;
                    if (fotoSituacionHint) {
                        fotoSituacionHint.textContent = 'Obligatoria: foto de la situación (RESUELTO).';
                    }
                } else {
                    if (fotoSituacionGroup) fotoSituacionGroup.style.display = 'none';
                    if (fotoSituacionRequired) fotoSituacionRequired.style.display = 'none';
                    if (fotoSituacionHint) fotoSituacionHint.textContent = '';

                    if (fotoSituacionInput) {
                        fotoSituacionInput.required = false;
                        fotoSituacionInput.value = '';
                    }

                    if (fotoSituacionName) fotoSituacionName.textContent = '';
                }
            }

            function toggleDanosPatrimoniales() {
                if (!danosSwitch || !danosFields) return;

                const activo = danosSwitch.checked;

                danosFields.style.display = activo ? 'flex' : 'none';

                if (!activo) {
                    if (propiedadesAfectadasInput) propiedadesAfectadasInput.value = '';
                    if (montoDanosInput) montoDanosInput.value = '';
                }
            }

            function setGeoUI() {
                const lat = latInput ? String(latInput.value || '').trim() : '';
                const lng = lngInput ? String(lngInput.value || '').trim() : '';
                const prec = precisionInput ? String(precisionInput.value || '').trim() : '';

                if (lat && lng) {
                    if (geoStatus) geoStatus.textContent = `OK: ${lat}, ${lng}` + (prec ? ` (±${prec} m)` : '');
                    if (btnGeoClear) btnGeoClear.style.display = 'inline-block';
                    if (btnSubmit) btnSubmit.disabled = false;
                    if (geoRequiredHint) geoRequiredHint.style.display = 'none';
                } else {
                    if (geoStatus) geoStatus.textContent = 'Sin coordenadas';
                    if (btnGeoClear) btnGeoClear.style.display = 'none';
                    if (btnSubmit) btnSubmit.disabled = true;
                    if (geoRequiredHint) geoRequiredHint.style.display = 'block';
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

            toggleTurnado();
            toggleFotoSituacion();
            toggleDanosPatrimoniales();
            setGeoUI();
            fillOficioFromDictamen();

            if (situacionSelect) {
                situacionSelect.addEventListener('change', function () {
                    toggleTurnado();
                    toggleFotoSituacion();
                });
            }

            @include('hechos.partials.turnado_mp_scripts')

            if (dictamenSelect) {
                dictamenSelect.addEventListener('change', function () {
                    fillOficioFromDictamen();
                });
            }

            if (danosSwitch) {
                danosSwitch.addEventListener('change', function () {
                    toggleDanosPatrimoniales();
                });
            }

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

            if (fotoLugarInput) {
                fotoLugarInput.addEventListener('change', function () {
                    const f = fotoLugarInput.files && fotoLugarInput.files[0] ? fotoLugarInput.files[0].name : '';
                    if (fotoLugarName) fotoLugarName.textContent = f ? ('Archivo: ' + f) : '';
                });
            }

            if (fotoSituacionInput) {
                fotoSituacionInput.addEventListener('change', function () {
                    const f = fotoSituacionInput.files && fotoSituacionInput.files[0] ? fotoSituacionInput.files[0].name : '';
                    if (fotoSituacionName) fotoSituacionName.textContent = f ? ('Archivo: ' + f) : '';
                });
            }

            if (btnGeo) {
                btnGeo.addEventListener('click', function () {
                    if (!navigator.geolocation) {
                        toastError('Tu navegador no soporta geolocalización.');
                        return;
                    }

                    if (geoStatus) geoStatus.textContent = 'Obteniendo ubicación...';

                    navigator.geolocation.getCurrentPosition(
                        function (pos) {
                            const lat = pos.coords.latitude;
                            const lng = pos.coords.longitude;
                            const acc = pos.coords.accuracy;

                            if (latInput) latInput.value = typeof lat === 'number' ? lat.toFixed(7) : '';
                            if (lngInput) lngInput.value = typeof lng === 'number' ? lng.toFixed(7) : '';
                            if (precisionInput) precisionInput.value = typeof acc === 'number' ? String(Math.round(acc)) : '';
                            if (fuenteInput) fuenteInput.value = 'GPS_WEB';

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
                    if (latInput) latInput.value = '';
                    if (lngInput) lngInput.value = '';
                    if (precisionInput) precisionInput.value = '';
                    if (fuenteInput) fuenteInput.value = '';
                    setGeoUI();
                });
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    const lat = latInput ? String(latInput.value || '').trim() : '';
                    const lng = lngInput ? String(lngInput.value || '').trim() : '';

                    if (!lat || !lng) {
                        e.preventDefault();
                        setGeoUI();
                        toastError('Captura la ubicación antes de registrar.');
                    }
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
