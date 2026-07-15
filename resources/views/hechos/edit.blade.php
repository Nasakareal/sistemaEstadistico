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
                            $fotoLugar2Path = $hecho->foto_lugar_2_path ?? ($hecho->foto_lugar_2 ?? null);
                            $fotoSituacionPath = $hecho->foto_situacion_path ?? ($hecho->foto_situacion ?? null);

                            $fotoStorage = app(\App\Services\Fotos\HechoFotoStorage::class);
                            $fotoLugarUrl = $fotoLugarPath ? $fotoStorage->url($fotoLugarPath) : null;
                            $fotoLugar2Url = $fotoLugar2Path ? $fotoStorage->url($fotoLugar2Path) : null;
                            $fotoSituacionUrl = $fotoSituacionPath ? $fotoStorage->url($fotoSituacionPath) : null;
                            $coordenadasManualValue = old('coordenadas_manual', (is_numeric($hecho->lat) && is_numeric($hecho->lng))
                                ? number_format((float) $hecho->lat, 7, '.', '') . ', ' . number_format((float) $hecho->lng, 7, '.', '')
                                : '');
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
                                    <label for="perito">Agente vial o nombre<span style="color: red">*</span></label>
                                    <input type="text" name="perito" id="perito"
                                           class="form-control @error('perito') is-invalid @enderror"
                                           value="{{ old('perito', $hecho->perito) }}"
                                           placeholder="Nombre del perito" required>
                                    @error('perito')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            @unless($ocultarCamposAdministrativosDelegaciones ?? false)
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
                            @endunless

                            <div class="{{ ($ocultarCamposAdministrativosDelegaciones ?? false) ? 'col-md-6' : 'col-md-3' }}">
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
                            @if($puedeCapturarFechaHora ?? false)
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
                            @endif

                            @unless($usaReglasFlexibles ?? false)
                                <div class="{{ ($puedeCapturarFechaHora ?? false) ? 'col-md-3' : 'col-md-6' }}">
                                    @include('hechos.partials.catalog_select', [
                                        'nombre' => 'sector',
                                        'etiqueta' => 'Sector',
                                        'opciones' => config('hechos.catalogos.sectores'),
                                        'valor' => $hecho->sector,
                                        'placeholder' => 'Seleccione un sector',
                                    ])
                                </div>
                            @endunless

                            <div class="{{ ($puedeCapturarFechaHora ?? false) ? (($usaReglasFlexibles ?? false) ? 'col-md-6' : 'col-md-3') : (($usaReglasFlexibles ?? false) ? 'col-md-12' : 'col-md-6') }}">
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

                                    @if($puedeEditarCoordenadasManual ?? false)
                                        <div class="mt-3" style="max-width: 420px;">
                                            <label for="coordenadas_manual" class="mb-1">Coordenadas</label>
                                            <input type="text"
                                                   name="coordenadas_manual"
                                                   id="coordenadas_manual"
                                                   class="form-control @error('coordenadas_manual') is-invalid @enderror"
                                                   value="{{ $coordenadasManualValue }}"
                                                   placeholder="19.6808588, -101.2339535"
                                                   autocomplete="off">
                                            <small class="help-muted">Formato: latitud, longitud.</small>
                                            <span id="coordenadas_manual_feedback" class="invalid-feedback d-block">
                                                @error('coordenadas_manual'){{ $message }}@enderror
                                            </span>
                                        </div>
                                    @endif

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
                                @include('hechos.partials.catalog_select', [
                                    'nombre' => 'tipo_hecho',
                                    'etiqueta' => 'Tipo de Hecho de Tránsito',
                                    'opciones' => config('hechos.catalogos.tipos_hecho'),
                                    'valor' => $hecho->tipo_hecho,
                                    'placeholder' => 'Seleccione el tipo de hecho',
                                ])
                            </div>

                            <div class="col-md-2">
                                @include('hechos.partials.catalog_select', ['nombre' => 'superficie_via', 'etiqueta' => 'Superficie de la Vía', 'opciones' => config('hechos.catalogos.superficies_via'), 'valor' => $hecho->superficie_via])
                            </div>

                            <div class="col-md-2">
                                @include('hechos.partials.catalog_select', ['nombre' => 'tiempo', 'etiqueta' => 'Tiempo', 'opciones' => config('hechos.catalogos.tiempos'), 'valor' => $hecho->tiempo])
                            </div>

                            <div class="col-md-2">
                                @include('hechos.partials.catalog_select', ['nombre' => 'clima', 'etiqueta' => 'Clima', 'opciones' => config('hechos.catalogos.climas'), 'valor' => $hecho->clima])
                            </div>

                            <div class="col-md-2">
                                @include('hechos.partials.catalog_select', ['nombre' => 'condiciones', 'etiqueta' => 'Condiciones', 'opciones' => config('hechos.catalogos.condiciones'), 'valor' => $hecho->condiciones])
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                @include('hechos.partials.catalog_select', ['nombre' => 'situacion', 'etiqueta' => 'Situación', 'opciones' => config('hechos.catalogos.situaciones'), 'valor' => $hecho->situacion])
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
                                        <small class="help-muted">Solo aparecen dictámenes {{ $anioHecho }} no usados en otros hechos y el actual si ya tiene.</small>
                                    </div>
                                </div>

                                <input type="hidden" name="oficio_mp" id="oficio_mp" value="{{ old('oficio_mp', $hecho->oficio_mp) }}">
                            @endif

                            <div class="col-md-4">
                                @include('hechos.partials.catalog_select', ['nombre' => 'control_transito', 'etiqueta' => 'Control de Tránsito', 'opciones' => config('hechos.catalogos.controles_transito'), 'valor' => $hecho->control_transito])
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                @include('hechos.partials.catalog_select', ['nombre' => 'colision_camino', 'etiqueta' => 'Colisión sobre el Camino', 'opciones' => config('hechos.catalogos.colisiones_camino'), 'valor' => $hecho->colision_camino])
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
                                @include('hechos.partials.catalog_select', ['nombre' => 'causas', 'etiqueta' => 'Causas', 'opciones' => config('hechos.catalogos.causas'), 'valor' => $hecho->causas])
                            </div>

                            <div class="col-md-4">
                                @include('hechos.partials.catalog_select', ['nombre' => 'responsable', 'etiqueta' => 'Responsable', 'opciones' => config('hechos.catalogos.responsables'), 'valor' => $hecho->responsable])
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="foto_lugar">Foto del hecho 1 (opcional)</label>
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

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="foto_lugar_2">Foto del hecho 2 (opcional)</label>
                                    <input type="file" name="foto_lugar_2" id="foto_lugar_2" accept="image/*"
                                           class="form-control @error('foto_lugar_2') is-invalid @enderror">
                                    @error('foto_lugar_2')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror

                                    @if ($fotoLugar2Url)
                                        <div class="mt-2" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                                            <img src="{{ $fotoLugar2Url }}" alt="Foto del hecho 2"
                                                 style="width:110px; height:80px; object-fit:cover; border-radius:12px; border:1px solid rgba(255,255,255,.12);">
                                            <a class="btn btn-sm btn-info" href="{{ $fotoLugar2Url }}" target="_blank" rel="noopener">
                                                <i class="fa-solid fa-up-right-from-square"></i> Ver
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" id="btn_quitar_foto_lugar_2">
                                                <i class="fa-solid fa-trash"></i> Quitar
                                            </button>
                                            <input type="hidden" name="quitar_foto_lugar_2" id="quitar_foto_lugar_2" value="0">
                                        </div>
                                        <small class="help-muted d-block mt-1">Si subes otra imagen, reemplaza la actual.</small>
                                    @else
                                        <input type="hidden" name="quitar_foto_lugar_2" id="quitar_foto_lugar_2" value="0">
                                    @endif

                                    <small id="foto_lugar_2_name" class="help-muted"></small>
                                </div>
                            </div>

                            <div class="col-md-4" id="foto_situacion_group" style="display:none;">
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
    @include('partials.landscape_photo_cropper_styles')

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
    @include('partials.landscape_photo_cropper_scripts')

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
            const fotoLugar2Input = document.getElementById('foto_lugar_2');
            const fotoLugar2Name  = document.getElementById('foto_lugar_2_name');

            const btnGeo      = document.getElementById('btn_geo');
            const btnGeoClear = document.getElementById('btn_geo_clear');
            const geoStatus   = document.getElementById('geo_status');
            const coordenadasManualInput = document.getElementById('coordenadas_manual');
            const coordenadasManualFeedback = document.getElementById('coordenadas_manual_feedback');
            const formHecho = document.getElementById('form_hecho');

            const latInput       = document.getElementById('lat');
            const lngInput       = document.getElementById('lng');
            const precisionInput = document.getElementById('calidad_geo');
            const fuenteInput    = document.getElementById('fuente_ubicacion');

            const danosInput = document.getElementById('danos_patrimoniales');
            const danosFields = document.getElementById('danos_patrimoniales_fields');
            const propiedadesAfectadasInput = document.getElementById('propiedades_afectadas');
            const montoDanosInput = document.getElementById('monto_danos_patrimoniales');

            if (window.SeguridadVialLandscapeCropper) {
                window.SeguridadVialLandscapeCropper.attach(fotoLugarInput);
                window.SeguridadVialLandscapeCropper.attach(fotoLugar2Input);
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

            function parseCoordenadasManual(value) {
                const texto = String(value || '').trim();
                if (!texto) return null;

                const partes = texto.split(/\s*,\s*|\s+/).filter(Boolean);
                if (partes.length !== 2) return null;

                const lat = Number(partes[0]);
                const lng = Number(partes[1]);

                if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
                if (lat < -90 || lat > 90 || lng < -180 || lng > 180) return null;

                return { lat, lng };
            }

            function setCoordenadasManualError(message) {
                if (!coordenadasManualInput) return;

                coordenadasManualInput.classList.toggle('is-invalid', Boolean(message));

                if (coordenadasManualFeedback) {
                    coordenadasManualFeedback.textContent = message || '';
                }
            }

            function syncCoordenadasManualFromHidden() {
                if (!coordenadasManualInput || !latInput || !lngInput) return;

                coordenadasManualInput.value = latInput.value && lngInput.value
                    ? `${latInput.value}, ${lngInput.value}`
                    : '';
                setCoordenadasManualError('');
            }

            function aplicarCoordenadasManual() {
                if (!coordenadasManualInput || !latInput || !lngInput) return true;

                const texto = coordenadasManualInput.value.trim();

                if (!texto) {
                    latInput.value = '';
                    lngInput.value = '';
                    if (precisionInput) precisionInput.value = '';
                    if (fuenteInput) fuenteInput.value = '';
                    setCoordenadasManualError('');
                    setGeoUI();
                    return true;
                }

                const coordenadas = parseCoordenadasManual(texto);

                if (!coordenadas) {
                    setCoordenadasManualError('Captura las coordenadas en formato latitud, longitud.');
                    if (geoStatus) geoStatus.textContent = 'Formato de coordenadas inválido';
                    return false;
                }

                latInput.value = coordenadas.lat.toFixed(7);
                lngInput.value = coordenadas.lng.toFixed(7);
                if (precisionInput) precisionInput.value = '';
                if (fuenteInput) fuenteInput.value = 'MANUAL_WEB';
                setCoordenadasManualError('');
                setGeoUI();

                return true;
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

                            syncCoordenadasManualFromHidden();
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
                    syncCoordenadasManualFromHidden();
                    setGeoUI();
                });
            }

            if (fotoLugar2Input) {
                fotoLugar2Input.addEventListener('change', function () {
                    const f = fotoLugar2Input.files && fotoLugar2Input.files[0] ? fotoLugar2Input.files[0].name : '';
                    if (fotoLugar2Name) fotoLugar2Name.textContent = f ? ('Archivo: ' + f) : '';

                    const h = document.getElementById('quitar_foto_lugar_2');
                    if (h) h.value = '0';
                });
            }

            if (coordenadasManualInput) {
                coordenadasManualInput.addEventListener('input', aplicarCoordenadasManual);
            }

            if (formHecho && coordenadasManualInput) {
                formHecho.addEventListener('submit', function (event) {
                    if (!aplicarCoordenadasManual()) {
                        event.preventDefault();
                        toastError('Revisa el formato de las coordenadas.');
                    }
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

            const btnQuitarLugar2 = document.getElementById('btn_quitar_foto_lugar_2');
            if (btnQuitarLugar2) {
                btnQuitarLugar2.addEventListener('click', function () {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Quitar foto',
                        text: 'Se quitará la foto 2 del hecho al guardar cambios.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, quitar',
                        cancelButtonText: 'Cancelar'
                    }).then((r) => {
                        if (r.isConfirmed) {
                            const h = document.getElementById('quitar_foto_lugar_2');
                            if (h) h.value = '1';
                            if (fotoLugar2Input) fotoLugar2Input.value = '';
                            if (fotoLugar2Name) fotoLugar2Name.textContent = '';
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
