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

                        <input type="hidden" name="lat" id="lat" value="{{ old('lat', $hecho->lat) }}">
                        <input type="hidden" name="lng" id="lng" value="{{ old('lng', $hecho->lng) }}">
                        <input type="hidden" name="calidad_geo" id="calidad_geo" value="{{ old('calidad_geo', $hecho->calidad_geo) }}">
                        <input type="hidden" name="fuente_ubicacion" id="fuente_ubicacion" value="{{ old('fuente_ubicacion', $hecho->fuente_ubicacion) }}">

                        @php
                            $fotoLugarPath = $hecho->foto_lugar_path ?? ($hecho->foto_lugar ?? null);
                            $fotoSituacionPath = $hecho->foto_situacion_path ?? ($hecho->foto_situacion ?? null);

                            $fotoLugarUrl = $fotoLugarPath ? Storage::url($fotoLugarPath) : null;
                            $fotoSituacionUrl = $fotoSituacionPath ? Storage::url($fotoSituacionPath) : null;
                        @endphp

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="folio_c5i">Folio de C5i</label>
                                    <input type="text" name="folio_c5i" id="folio_c5i"
                                           class="form-control @error('folio_c5i') is-invalid @enderror"
                                           value="{{ old('folio_c5i', $hecho->folio_c5i) }}"
                                           placeholder="Ingrese el folio de C5i">
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
                                           value="{{ old('hora', !empty($hecho->hora) ? substr($hecho->hora, 0, 5) : '') }}"
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
                                    <label for="fecha">Fecha<span style="color: red">*</span></label>
                                    <input type="date" name="fecha" id="fecha"
                                           class="form-control @error('fecha') is-invalid @enderror"
                                           value="{{ old('fecha', \Carbon\Carbon::parse($hecho->fecha)->format('Y-m-d')) }}" required>
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
                                        <option value="" disabled {{ old('situacion', $hecho->situacion) ? '' : 'selected' }}>Seleccione la situación</option>
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

                            @if($puedeUsarDictamenes)
                                <div class="col-md-4" id="dictamen_group" style="display:none;">
                                    <div class="form-group">
                                        <label for="dictamen_id">Dictamen / MP <span style="color:red">*</span></label>
                                        <select name="dictamen_id" id="dictamen_id"
                                                class="form-control @error('dictamen_id') is-invalid @enderror">
                                            <option value="" disabled {{ old('dictamen_id', optional($dictamenActual)->id) ? '' : 'selected' }}>
                                                Seleccione un dictamen
                                            </option>

                                            @if(isset($dictamenesDisponibles))
                                                @foreach($dictamenesDisponibles as $d)
                                                    @php
                                                        $oficio = $d->numero_dictamen . '/' . $d->anio . ' ' . $d->nombre_mp;
                                                        $selectedId = (string) old('dictamen_id', optional($dictamenActual)->id);
                                                    @endphp
                                                    <option value="{{ $d->id }}"
                                                            data-oficio="{{ $oficio }}"
                                                            {{ $selectedId === (string)$d->id ? 'selected' : '' }}>
                                                        {{ $oficio }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('dictamen_id')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                        <small class="help-muted">Solo aparecen dictámenes no usados en otros hechos y el actual si ya tiene.</small>
                                    </div>
                                </div>

                                <input type="hidden" name="oficio_mp" id="oficio_mp" value="{{ old('oficio_mp', $hecho->oficio_mp) }}">
                            @endif

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
                        </div>

                        <div class="row">
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

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>¿Se checaron antecedentes?</label>
                                    <select name="checaron_antecedentes" id="checaron_antecedentes" class="form-control">
                                        <option value="0" {{ old('checaron_antecedentes', (string)($hecho->checaron_antecedentes ?? '0')) == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('checaron_antecedentes', (string)($hecho->checaron_antecedentes ?? '0')) == '1' ? 'selected' : '' }}>Sí</option>
                                    </select>
                                    @error('checaron_antecedentes')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>¿Daños patrimoniales?</label>
                                    <select name="danos_patrimoniales" id="danos_patrimoniales" class="form-control">
                                        <option value="0" {{ old('danos_patrimoniales', (string)($hecho->danos_patrimoniales ?? '0')) == '0' ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('danos_patrimoniales', (string)($hecho->danos_patrimoniales ?? '0')) == '1' ? 'selected' : '' }}>Sí</option>
                                    </select>
                                    @error('danos_patrimoniales')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row" id="danos_patrimoniales_fields" style="display:none;">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="propiedades_afectadas">Propiedades afectadas</label>
                                    <input type="text" name="propiedades_afectadas" id="propiedades_afectadas"
                                           class="form-control @error('propiedades_afectadas') is-invalid @enderror"
                                           value="{{ old('propiedades_afectadas', $hecho->propiedades_afectadas) }}"
                                           placeholder="Ingrese las propiedades afectadas">
                                    @error('propiedades_afectadas')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="monto_danos_patrimoniales">Monto de daños patrimoniales</label>
                                    <input type="number" step="0.01" min="0" name="monto_danos_patrimoniales" id="monto_danos_patrimoniales"
                                           class="form-control @error('monto_danos_patrimoniales') is-invalid @enderror"
                                           value="{{ old('monto_danos_patrimoniales', $hecho->monto_danos_patrimoniales) }}"
                                           placeholder="Ingrese el monto">
                                    @error('monto_danos_patrimoniales')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
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

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="responsable">Responsable<span style="color: red">*</span></label>
                                    <input type="text" name="responsable" id="responsable"
                                           class="form-control @error('responsable') is-invalid @enderror"
                                           value="{{ old('responsable', $hecho->responsable) }}"
                                           placeholder="Ingrese el responsable" required>
                                    @error('responsable')
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

                        @if($puedeGestionarTotalesEsperados ?? false)
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="vehiculos_esperados">Vehículos esperados<span style="color: red">*</span></label>
                                        <input type="number"
                                               name="vehiculos_esperados"
                                               id="vehiculos_esperados"
                                               class="form-control @error('vehiculos_esperados') is-invalid @enderror"
                                               value="{{ old('vehiculos_esperados', $hecho->vehiculos_esperados) }}"
                                               min="0"
                                               required>
                                        @error('vehiculos_esperados')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="conductores_esperados">Conductores esperados<span style="color: red">*</span></label>
                                        <input type="number"
                                               name="conductores_esperados"
                                               id="conductores_esperados"
                                               class="form-control @error('conductores_esperados') is-invalid @enderror"
                                               value="{{ old('conductores_esperados', $hecho->conductores_esperados) }}"
                                               min="0"
                                               required>
                                        @error('conductores_esperados')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="lesionados_esperados">Lesionados esperados<span style="color: red">*</span></label>
                                        <input type="number"
                                               name="lesionados_esperados"
                                               id="lesionados_esperados"
                                               class="form-control @error('lesionados_esperados') is-invalid @enderror"
                                               value="{{ old('lesionados_esperados', $hecho->lesionados_esperados) }}"
                                               min="0"
                                               required>
                                        @error('lesionados_esperados')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

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
                                    @else
                                        <input type="hidden" name="quitar_foto_lugar" id="quitar_foto_lugar" value="0">
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
                                                <i class="fa-solid fa-up-right-from-square"></i> Ver
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" id="btn_quitar_foto_situacion">
                                                <i class="fa-solid fa-trash"></i> Quitar
                                            </button>
                                            <input type="hidden" name="quitar_foto_situacion" id="quitar_foto_situacion" value="0">
                                        </div>
                                        <small class="help-muted d-block mt-1">Si subes otra imagen, reemplaza la actual.</small>
                                    @else
                                        <input type="hidden" name="quitar_foto_situacion" id="quitar_foto_situacion" value="0">
                                    @endif

                                    <small id="foto_situacion_name" class="help-muted d-block mt-1"></small>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="form-group">
                                    <button id="btn_submit" type="submit" class="btn btn-success">
                                        <i class="fa-solid fa-check"></i> Actualizar
                                    </button>

                                    <a href="{{ route('hechos.index') }}" class="btn btn-secondary">
                                        <i class="fa-solid fa-ban"></i> Cancelar
                                    </a>
                                </div>
                                <small id="geo_required_hint" class="help-muted" style="display:none;">
                                    Captura la ubicación para poder actualizar.
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

            const situacionSelect = document.getElementById('situacion');
            const puedeUsarDictamenes = @json((bool)($puedeUsarDictamenes ?? false));

            const dictamenGroup  = document.getElementById('dictamen_group');
            const dictamenSelect = document.getElementById('dictamen_id');
            const oficioInput    = document.getElementById('oficio_mp');

            const fotoSituacionGroup    = document.getElementById('foto_situacion_group');
            const fotoSituacionInput    = document.getElementById('foto_situacion');
            const fotoSituacionRequired = document.getElementById('foto_situacion_required');
            const fotoSituacionHint     = document.getElementById('foto_situacion_hint');
            const fotoSituacionName     = document.getElementById('foto_situacion_name');

            const fotoLugarInput = document.getElementById('foto_lugar');
            const fotoLugarName  = document.getElementById('foto_lugar_name');

            const btnGeo      = document.getElementById('btn_geo');
            const btnGeoClear = document.getElementById('btn_geo_clear');
            const geoStatus   = document.getElementById('geo_status');

            const latInput       = document.getElementById('lat');
            const lngInput       = document.getElementById('lng');
            const precisionInput = document.getElementById('calidad_geo');
            const fuenteInput    = document.getElementById('fuente_ubicacion');

            const danosInput = document.getElementById('danos_patrimoniales');
            const danosFields = document.getElementById('danos_patrimoniales_fields');
            const propiedadesAfectadasInput = document.getElementById('propiedades_afectadas');
            const montoDanosInput = document.getElementById('monto_danos_patrimoniales');

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

            function fillOficioFromDictamen() {
                if (!oficioInput) return;
                if (!dictamenSelect || !dictamenSelect.value) return;

                const opt = dictamenSelect.options[dictamenSelect.selectedIndex];
                const oficio = opt && opt.dataset && opt.dataset.oficio ? String(opt.dataset.oficio).trim() : '';
                if (oficio) oficioInput.value = oficio;
            }

            function toggleTurnado() {
                if (!situacionSelect) return;

                const isTurnado = (situacionSelect.value === 'TURNADO');

                if (dictamenGroup) dictamenGroup.style.display = isTurnado && puedeUsarDictamenes ? 'block' : 'none';
                if (dictamenSelect) dictamenSelect.required = isTurnado && puedeUsarDictamenes;

                if (isTurnado && puedeUsarDictamenes) {
                    fillOficioFromDictamen();
                } else {
                    if (dictamenSelect) dictamenSelect.value = '';
                    if (oficioInput) oficioInput.value = '';
                }
            }

            function toggleFotoSituacion() {
                if (!situacionSelect || !fotoSituacionGroup || !fotoSituacionRequired || !fotoSituacionHint || !fotoSituacionInput) return;

                const val = situacionSelect.value;
                const mustShow = (val === 'RESUELTO' || val === 'TURNADO');

                if (mustShow) {
                    fotoSituacionGroup.style.display = 'block';
                    fotoSituacionRequired.style.display = 'inline';

                    const quitarFotoSituacionInput = document.getElementById('quitar_foto_situacion');
                    const seMarcaraParaQuitar = quitarFotoSituacionInput && quitarFotoSituacionInput.value === '1';
                    const hayGuardada = {{ $fotoSituacionUrl ? 'true' : 'false' }} && !seMarcaraParaQuitar;

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

            function toggleDanosPatrimoniales() {
                if (!danosInput || !danosFields) return;

                let activo = false;

                if (danosInput.type === 'checkbox') {
                    activo = danosInput.checked;
                } else {
                    activo = String(danosInput.value) === '1';
                }

                danosFields.style.display = activo ? 'flex' : 'none';

                if (!activo) {
                    if (propiedadesAfectadasInput) propiedadesAfectadasInput.value = '';
                    if (montoDanosInput) montoDanosInput.value = '';
                }
            }

            function setGeoUI() {
                if (!geoStatus || !btnGeoClear || !latInput || !lngInput || !precisionInput) return;

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

                    if (situacionSelect && dictamenSelect.value) {
                        situacionSelect.value = 'TURNADO';
                        toggleTurnado();
                        toggleFotoSituacion();
                        if (window.actualizarGuardiaTurnadoMp) window.actualizarGuardiaTurnadoMp();
                        if (window.preguntarGuardiaTurnadoMp) window.preguntarGuardiaTurnadoMp();
                    }
                });
            }

            if (danosInput) {
                danosInput.addEventListener('change', function () {
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

                    const h = document.getElementById('quitar_foto_situacion');
                    if (h) h.value = '0';

                    toggleFotoSituacion();
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

                            if (latInput) latInput.value = (typeof lat === 'number') ? lat.toFixed(7) : '';
                            if (lngInput) lngInput.value = (typeof lng === 'number') ? lng.toFixed(7) : '';
                            if (precisionInput) precisionInput.value = (typeof acc === 'number') ? Math.round(acc) : '';
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

                            toggleFotoSituacion();
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
