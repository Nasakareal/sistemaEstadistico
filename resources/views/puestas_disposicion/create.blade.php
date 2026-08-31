@extends('adminlte::page')

@section('title', 'Crear Puesta a Disposición')

@section('content_header')
    <h1>Crear Puesta a Disposición</h1>
@stop

@section('content')
    @php
        $tipoSeleccionado = old('tipo_puesta', $tipoPuestaDefault ?? null);
        $motivoSeleccionado = old('motivo', $motivoDefault ?? null);
        $fechaPuestaSeleccionada = old('fecha_puesta', $fechaPuestaDefault ?? now()->toDateString());
        $horaPuestaSeleccionada = old('hora_puesta', $horaPuestaDefault ?? null);
        $lugarPuestaSeleccionado = old('lugar_puesta', $lugarPuestaDefault ?? null);
        $nombrePoliciaSeleccionado = old('nombre_policia', $nombrePoliciaDefault ?? (auth()->user()->name ?? ''));
        $oficioSeleccionado = old('oficio', $oficioDefault ?? null);
        $hechoOrigen = $hechoOrigen ?? null;
        $vehiculosHechoPuesta = $vehiculosHechoPuesta ?? [];
        $motivosPuestaOptions = $motivosPuestaOptions ?? [];
        $hechosTurnadosDisponibles = $hechosTurnadosDisponibles ?? [];
        $hechoTurnadoSeleccionadoId = (int) old('hecho_id');
        $motivoOtroSeleccionado = old('motivo_otro');
        $motivoUsaOtro = $motivoSeleccionado && !in_array($motivoSeleccionado, $motivosPuestaOptions, true);
        if ($motivoUsaOtro) {
            $motivoOtroSeleccionado = old('motivo_otro', $motivoSeleccionado);
            $motivoSeleccionado = 'OTRO';
        }
        $unidadDelegacionesId = $unidadDelegacionesId ?? 2;
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los Datos</h3>
                </div>
                <div class="card-body">
                    <form id="puestaDisposicionForm" action="{{ route('puestas_disposicion.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if($hechoOrigen)
                            <input type="hidden" name="hecho_id" value="{{ $hechoOrigen->id }}">

                            <div class="alert alert-info">
                                <strong>Hecho vinculado:</strong>
                                #{{ $hechoOrigen->id }}
                                @if($hechoOrigen->folio_c5i)
                                    · Folio {{ $hechoOrigen->folio_c5i }}
                                @endif
                                @if($hechoOrigen->situacion)
                                    · {{ $hechoOrigen->situacion }}
                                @endif
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero_puesta_preview">Número de Puesta</label>
                                    <input type="text" id="numero_puesta_preview" class="form-control"
                                           value="{{ $numeroSiguiente }}/{{ now()->year }}" readonly>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_puesta">Tipo de Puesta</label>
                                    <select name="tipo_puesta" id="tipo_puesta"
                                            class="form-control @error('tipo_puesta') is-invalid @enderror" required>
                                        <option value="" disabled {{ $tipoSeleccionado ? '' : 'selected' }}>Seleccione una opción</option>
                                        <option value="PERSONA" {{ $tipoSeleccionado == 'PERSONA' ? 'selected' : '' }}>PERSONA</option>
                                        <option value="VEHICULO" {{ $tipoSeleccionado == 'VEHICULO' ? 'selected' : '' }}>VEHÍCULO</option>
                                        <option value="OBJETO" {{ $tipoSeleccionado == 'OBJETO' ? 'selected' : '' }}>OBJETO</option>
                                        <option value="MIXTA" {{ $tipoSeleccionado == 'MIXTA' ? 'selected' : '' }}>MIXTA</option>
                                    </select>
                                    @error('tipo_puesta')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="motivo">Motivo</label>
                                    <select name="motivo" id="motivo"
                                            class="form-control @error('motivo') is-invalid @enderror" required>
                                        <option value="" disabled {{ $motivoSeleccionado ? '' : 'selected' }}>Seleccione una opción</option>
                                        @foreach($motivosPuestaOptions as $motivoOption)
                                            <option value="{{ $motivoOption }}" {{ $motivoSeleccionado === $motivoOption ? 'selected' : '' }}>
                                                {{ $motivoOption === 'OTRO' ? 'OTRO (ESPECIFICAR)' : $motivoOption }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="motivo_otro" id="motivo_otro"
                                           class="form-control mt-2 d-none @error('motivo_otro') is-invalid @enderror"
                                           value="{{ $motivoOtroSeleccionado }}"
                                           placeholder="Especifique el motivo">
                                    @error('motivo')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                    @error('motivo_otro')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estatus_preview">Estatus</label>
                                    <input type="text" id="estatus_preview" class="form-control" value="ACTIVA" readonly>
                                    <input type="hidden" name="estatus" value="ACTIVA">
                                </div>
                            </div>
                        </div>

                        @unless($hechoOrigen)
                            <div id="sv-hecho-transito-warning" class="alert alert-warning d-none">
                                <strong>Vincula el hecho turnado.</strong>
                                Para registrar una puesta por hecho de tránsito, selecciona el ID del hecho que ya está TURNADO y no tiene puesta vinculada.
                            </div>

                            <div id="hecho_turnado_group" class="row d-none">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="hecho_id">Hecho turnado a vincular</label>
                                        <select name="hecho_id" id="hecho_id"
                                                class="form-control @error('hecho_id') is-invalid @enderror"
                                                disabled>
                                            <option value="">Seleccione el hecho turnado</option>
                                            @foreach($hechosTurnadosDisponibles as $hechoTurnado)
                                                <option value="{{ $hechoTurnado['id'] }}"
                                                        data-tipo-puesta="{{ $hechoTurnado['tipo_puesta'] }}"
                                                        data-fecha-puesta="{{ $hechoTurnado['fecha_puesta'] }}"
                                                        data-hora-puesta="{{ $hechoTurnado['hora_puesta'] }}"
                                                        data-lugar-puesta="{{ $hechoTurnado['lugar_puesta'] }}"
                                                        data-nombre-policia="{{ $hechoTurnado['nombre_policia'] }}"
                                                        data-oficio="{{ $hechoTurnado['oficio'] }}"
                                                        {{ $hechoTurnadoSeleccionadoId === (int)$hechoTurnado['id'] ? 'selected' : '' }}>
                                                    {{ $hechoTurnado['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if(empty($hechosTurnadosDisponibles))
                                            <small class="form-text text-warning">
                                                No hay hechos TURNADO visibles sin puesta vinculada.
                                            </small>
                                        @else
                                            <small class="form-text text-muted">
                                                Solo aparecen hechos TURNADO de Delegaciones que todavía no tienen puesta.
                                            </small>
                                        @endif
                                        @error('hecho_id')
                                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endunless

                        <hr>

                        <div class="row">

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fecha_puesta">Fecha de Puesta</label>
                                    <input type="date" name="fecha_puesta" id="fecha_puesta"
                                           class="form-control @error('fecha_puesta') is-invalid @enderror"
                                           value="{{ $fechaPuestaSeleccionada }}" required>
                                    @error('fecha_puesta')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="hora_puesta">Hora de Puesta</label>
                                    <input type="time" name="hora_puesta" id="hora_puesta"
                                           class="form-control @error('hora_puesta') is-invalid @enderror"
                                           value="{{ $horaPuestaSeleccionada }}">
                                    @error('hora_puesta')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lugar_puesta">Lugar de Puesta</label>
                                    <input type="text" name="lugar_puesta" id="lugar_puesta"
                                           class="form-control @error('lugar_puesta') is-invalid @enderror"
                                           value="{{ $lugarPuestaSeleccionado }}">
                                    @error('lugar_puesta')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre_policia">Nombre del Policía</label>
                                    <input type="text" name="nombre_policia" id="nombre_policia"
                                           class="form-control @error('nombre_policia') is-invalid @enderror"
                                           value="{{ $nombrePoliciaSeleccionado }}" required>
                                    @error('nombre_policia')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombre_mp">Nombre del MP</label>
                                    <input type="text" name="nombre_mp" id="nombre_mp"
                                           class="form-control @error('nombre_mp') is-invalid @enderror"
                                           value="{{ old('nombre_mp') }}">
                                    @error('nombre_mp')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="autoridad_receptora">Autoridad Receptora</label>
                                    <input type="text" name="autoridad_receptora" id="autoridad_receptora"
                                           class="form-control @error('autoridad_receptora') is-invalid @enderror"
                                           value="{{ old('autoridad_receptora') }}">
                                    @error('autoridad_receptora')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    @if($hechoOrigen)
                                        <label for="area">Área</label>
                                        <input type="text" id="area"
                                               class="form-control"
                                               value="{{ $unidadNombre }}" readonly>
                                        <input type="hidden" name="unidad_id" value="{{ $unidadSeleccionadaId }}">
                                    @elseif($puedeSeleccionarUnidad ?? false)
                                        <label for="unidad_id">Unidad / Área</label>
                                        <select name="unidad_id" id="unidad_id"
                                                class="form-control @error('unidad_id') is-invalid @enderror" required>
                                            <option value="" disabled {{ old('unidad_id', $unidadSeleccionadaId ?? null) ? '' : 'selected' }}>Seleccione una unidad</option>
                                            @foreach(($unidades ?? collect()) as $unidad)
                                                <option value="{{ $unidad->id }}"
                                                        data-next="{{ $numerosSiguientesPorUnidad[(int)$unidad->id] ?? 1 }}"
                                                        {{ (int)old('unidad_id', $unidadSeleccionadaId ?? 0) === (int)$unidad->id ? 'selected' : '' }}>
                                                    {{ $unidad->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('unidad_id')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    @else
                                        <label for="area">Área</label>
                                        <input type="text" name="area" id="area"
                                               class="form-control"
                                               value="{{ $unidadNombre }}" readonly>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="carpeta_investigacion">Carpeta de Investigación</label>
                                    <input type="text" name="carpeta_investigacion" id="carpeta_investigacion"
                                           class="form-control @error('carpeta_investigacion') is-invalid @enderror"
                                           value="{{ old('carpeta_investigacion') }}">
                                    @error('carpeta_investigacion')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="oficio">Oficio</label>
                                    <input type="text" name="oficio" id="oficio"
                                           class="form-control @error('oficio') is-invalid @enderror"
                                           value="{{ $oficioSeleccionado }}">
                                    @error('oficio')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archivo_puesta">PDF de puesta a disposición</label>
                                    <input type="file" name="archivo_puesta" id="archivo_puesta"
                                           class="form-control @error('archivo_puesta') is-invalid @enderror"
                                           accept="application/pdf">
                                    <small class="form-text text-muted">
                                        Máximo {{ (int) ceil(config('pdf_compression.max_upload_kb', 51200) / 1024) }} MB; se comprimirá automáticamente cuando sea posible.
                                    </small>
                                    @error('archivo_puesta')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archivo_uso_fuerza">PDF de uso de la fuerza</label>
                                    <input type="file" name="archivo_uso_fuerza" id="archivo_uso_fuerza"
                                           class="form-control @error('archivo_uso_fuerza') is-invalid @enderror"
                                           accept="application/pdf">
                                    <small class="form-text text-muted">
                                        Documento independiente del PDF de la puesta. Máximo {{ (int) ceil(config('pdf_compression.max_upload_kb', 51200) / 1024) }} MB.
                                    </small>
                                    @error('archivo_uso_fuerza')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="narrativa">Narrativa</label>
                                    <textarea name="narrativa" id="narrativa" rows="4"
                                              class="form-control @error('narrativa') is-invalid @enderror">{{ old('narrativa') }}</textarea>
                                    @error('narrativa')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea name="observaciones" id="observaciones" rows="3"
                                              class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        @if($hechoOrigen)
                            <div class="card card-outline card-primary sv-hecho-origen-card">
                                <div class="card-header">
                                    <h3 class="card-title mb-0">Datos ya capturados del hecho</h3>
                                </div>
                                <div class="card-body">
                                    <p class="mb-3">
                                        Selecciona lo que se va a turnar para copiarlo a esta puesta.
                                    </p>

                                    @if(count($vehiculosHechoPuesta))
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h5 class="sv-copy-title">Vehículos</h5>
                                                @foreach($vehiculosHechoPuesta as $vehiculoHecho)
                                                    <label class="sv-copy-option">
                                                        <input type="checkbox"
                                                               class="js-hecho-vehiculo"
                                                               data-source-key="{{ $vehiculoHecho['source_key'] }}">
                                                        <span>{{ $vehiculoHecho['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>

                                            <div class="col-md-6">
                                                <h5 class="sv-copy-title">Conductores</h5>
                                                @php
                                                    $hayConductoresHecho = false;
                                                @endphp
                                                @foreach($vehiculosHechoPuesta as $vehiculoHecho)
                                                    @foreach($vehiculoHecho['conductores'] as $conductorHecho)
                                                        @php $hayConductoresHecho = true; @endphp
                                                        <label class="sv-copy-option">
                                                            <input type="checkbox"
                                                                   class="js-hecho-conductor"
                                                                   data-source-key="{{ $conductorHecho['source_key'] }}">
                                                            <span>{{ $conductorHecho['label'] }}</span>
                                                        </label>
                                                    @endforeach
                                                @endforeach

                                                @if(!$hayConductoresHecho)
                                                    <div class="text-muted">No hay conductores capturados en los vehículos del hecho.</div>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-muted">No hay vehículos capturados en este hecho.</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="card card-outline card-info">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">Personas</h3>
                                <button type="button" class="btn btn-success btn-sm" id="btnAgregarPersona">
                                    <i class="fa-solid fa-plus"></i> Agregar Persona
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="contenedorPersonas"></div>
                            </div>
                        </div>

                        <div class="card card-outline card-warning">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">Vehículos</h3>
                                <button type="button" class="btn btn-success btn-sm" id="btnAgregarVehiculo">
                                    <i class="fa-solid fa-plus"></i> Agregar Vehículo
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="contenedorVehiculos"></div>
                            </div>
                        </div>

                        <div class="card card-outline card-secondary">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0">Objetos</h3>
                                <button type="button" class="btn btn-success btn-sm" id="btnAgregarObjeto">
                                    <i class="fa-solid fa-plus"></i> Agregar Objeto
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="contenedorObjetos"></div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-solid fa-check"></i> Registrar
                                    </button>
                                    <a href="{{ route('puestas_disposicion.index') }}" class="btn btn-secondary">
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
    <style>
        .content-wrapper .card.card-outline.card-info,
        .content-wrapper .card.card-outline.card-warning,
        .content-wrapper .card.card-outline.card-secondary,
        .content-wrapper .sv-hecho-origen-card {
            border-radius: 18px !important;
            overflow: hidden !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(6px);
        }

        .content-wrapper .card.card-outline.card-info > .card-header,
        .content-wrapper .card.card-outline.card-warning > .card-header,
        .content-wrapper .card.card-outline.card-secondary > .card-header,
        .content-wrapper .sv-hecho-origen-card > .card-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 14px 20px !important;
        }

        .content-wrapper .card.card-outline.card-info > .card-header .card-title,
        .content-wrapper .card.card-outline.card-warning > .card-header .card-title,
        .content-wrapper .card.card-outline.card-secondary > .card-header .card-title,
        .content-wrapper .sv-hecho-origen-card > .card-header .card-title {
            color: #ffffff !important;
            font-size: 1.15rem !important;
            font-weight: 700 !important;
            margin: 0 !important;
        }

        .content-wrapper .card.card-outline.card-info > .card-body,
        .content-wrapper .card.card-outline.card-warning > .card-body,
        .content-wrapper .card.card-outline.card-secondary > .card-body,
        .content-wrapper .sv-hecho-origen-card > .card-body {
            background: transparent !important;
            padding: 24px !important;
        }

        .sv-copy-title {
            color: #ffffff !important;
            font-weight: 800 !important;
            margin-bottom: 12px !important;
        }

        .sv-copy-option {
            display: flex !important;
            align-items: flex-start !important;
            gap: 10px !important;
            padding: 10px 12px !important;
            border: 1px solid rgba(255, 255, 255, 0.14) !important;
            border-radius: 14px !important;
            color: #ffffff !important;
            cursor: pointer !important;
            margin-bottom: 10px !important;
            background: rgba(255, 255, 255, 0.05) !important;
        }

        .sv-copy-option input {
            margin-top: 3px !important;
            width: 18px !important;
            height: 18px !important;
            accent-color: #2563eb !important;
        }

        #btnAgregarPersona,
        #btnAgregarVehiculo,
        #btnAgregarObjeto {
            background: #10b981 !important;
            border-color: #10b981 !important;
            color: #ffffff !important;
            border-radius: 16px !important;
            font-weight: 700 !important;
            padding: 10px 18px !important;
            box-shadow: none !important;
        }

        #btnAgregarPersona:hover,
        #btnAgregarPersona:focus,
        #btnAgregarVehiculo:hover,
        #btnAgregarVehiculo:focus,
        #btnAgregarObjeto:hover,
        #btnAgregarObjeto:focus {
            background: #059669 !important;
            border-color: #059669 !important;
            color: #ffffff !important;
        }

        /* ===== SELECTS PRINCIPALES ===== */
        #tipo_puesta,
        #motivo,
        #unidad_id,
        #hecho_id {
            background-color: #12263c !important;
            color: #f8fafc !important;
            border: 1px solid rgba(125, 178, 225, .45) !important;
            border-radius: 14px !important;
            min-height: 48px !important;
            box-shadow: none !important;
            -webkit-text-fill-color: #f8fafc !important;
            appearance: auto !important;
            -webkit-appearance: menulist !important;
            -moz-appearance: menulist !important;
        }

        #tipo_puesta:focus,
        #motivo:focus,
        #unidad_id:focus,
        #hecho_id:focus {
            background-color: #12263c !important;
            color: #ffffff !important;
            border-color: #64b5f6 !important;
            box-shadow: 0 0 0 .2rem rgba(100, 181, 246, .18) !important;
            outline: none !important;
            -webkit-text-fill-color: #ffffff !important;
        }

        #tipo_puesta option,
        #motivo option,
        #unidad_id option,
        #hecho_id option {
            background-color: #12263c !important;
            color: #f8fafc !important;
        }

        #tipo_puesta option:checked,
        #motivo option:checked,
        #unidad_id option:checked,
        #hecho_id option:checked {
            background-color: #2563d8 !important;
            color: #ffffff !important;
        }

        #tipo_puesta option:disabled,
        #motivo option:disabled,
        #unidad_id option:disabled,
        #hecho_id option:disabled {
            color: #94a3b8 !important;
        }

        /* ===== BLOQUES DINAMICOS ===== */
        .bloque-dinamico {
            background: #f8fafc !important;
            border: 1px solid #d9dee7 !important;
            border-radius: 18px !important;
            padding: 22px !important;
            margin-bottom: 20px !important;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18) !important;
        }

        .bloque-dinamico h5,
        .bloque-dinamico .h5,
        .bloque-dinamico .titulo-bloque {
            color: #16324f !important;
            font-weight: 800 !important;
            font-size: 1.1rem !important;
            margin: 0 !important;
        }

        .bloque-dinamico .form-group {
            margin-bottom: 14px !important;
        }

        .bloque-dinamico .form-group label,
        .bloque-dinamico label {
            color: #344054 !important;
            font-weight: 700 !important;
            font-size: 0.98rem !important;
            display: inline-block !important;
            margin-bottom: 6px !important;
            opacity: 1 !important;
        }

        .bloque-dinamico .form-control,
        .bloque-dinamico input[type="text"],
        .bloque-dinamico input[type="number"],
        .bloque-dinamico input[type="date"],
        .bloque-dinamico input[type="time"],
        .bloque-dinamico textarea,
        .bloque-dinamico select {
            background: #ffffff !important;
            color: #1f2937 !important;
            border: 1px solid #cfd6df !important;
            border-radius: 14px !important;
            min-height: 48px !important;
            box-shadow: none !important;
            padding: 10px 14px !important;
            -webkit-text-fill-color: #1f2937 !important;
        }

        .bloque-dinamico textarea.form-control {
            min-height: 90px !important;
            resize: vertical !important;
        }

        .bloque-dinamico .form-control:focus,
        .bloque-dinamico input:focus,
        .bloque-dinamico textarea:focus,
        .bloque-dinamico select:focus {
            background: #ffffff !important;
            color: #111827 !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.18) !important;
            outline: none !important;
            -webkit-text-fill-color: #111827 !important;
        }

        .bloque-dinamico .form-control::placeholder,
        .bloque-dinamico input::placeholder,
        .bloque-dinamico textarea::placeholder {
            color: #98a2b3 !important;
            opacity: 1 !important;
            -webkit-text-fill-color: #98a2b3 !important;
        }

        .bloque-dinamico select.form-control,
        .bloque-dinamico select {
            appearance: auto !important;
            -webkit-appearance: menulist !important;
            -moz-appearance: menulist !important;
            background-color: #ffffff !important;
            color: #1f2937 !important;
        }

        .bloque-dinamico select option {
            background: #ffffff !important;
            color: #1f2937 !important;
        }

        .bloque-dinamico input[type="date"],
        .bloque-dinamico input[type="time"] {
            color-scheme: light !important;
        }

        .bloque-dinamico input[type="date"]::-webkit-datetime-edit,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-fields-wrapper,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-text,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-month-field,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-day-field,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-year-field,
        .bloque-dinamico input[type="time"]::-webkit-datetime-edit,
        .bloque-dinamico input[type="time"]::-webkit-datetime-edit-fields-wrapper,
        .bloque-dinamico input[type="time"]::-webkit-datetime-edit-text,
        .bloque-dinamico input[type="time"]::-webkit-datetime-edit-hour-field,
        .bloque-dinamico input[type="time"]::-webkit-datetime-edit-minute-field {
            color: #1f2937 !important;
            -webkit-text-fill-color: #1f2937 !important;
        }

        .bloque-dinamico input[type="date"]::-webkit-calendar-picker-indicator,
        .bloque-dinamico input[type="time"]::-webkit-calendar-picker-indicator {
            opacity: 1 !important;
            cursor: pointer;
            filter: none !important;
        }

        .bloque-dinamico input[type="checkbox"] {
            width: 20px !important;
            height: 20px !important;
            accent-color: #2563eb !important;
            cursor: pointer !important;
            vertical-align: middle !important;
        }

        .bloque-dinamico .btn-danger,
        .bloque-dinamico .btn-danger.btn-sm {
            background: #e74c3c !important;
            border-color: #e74c3c !important;
            color: #ffffff !important;
            border-radius: 14px !important;
            font-weight: 700 !important;
            padding: 9px 18px !important;
            box-shadow: none !important;
        }

        .bloque-dinamico .btn-danger:hover,
        .bloque-dinamico .btn-danger:focus {
            background: #d62c1a !important;
            border-color: #d62c1a !important;
            color: #ffffff !important;
        }

        .bloque-dinamico .invalid-feedback,
        .bloque-dinamico .text-danger {
            color: #dc2626 !important;
            font-weight: 600 !important;
        }

        @media (max-width: 768px) {
            .content-wrapper .card.card-outline.card-info > .card-body,
            .content-wrapper .card.card-outline.card-warning > .card-body,
            .content-wrapper .card.card-outline.card-secondary > .card-body {
                padding: 16px !important;
            }

            .bloque-dinamico {
                padding: 16px !important;
                border-radius: 14px !important;
            }

            .bloque-dinamico .d-flex.justify-content-between {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 12px !important;
            }

            .bloque-dinamico .btn-danger,
            #btnAgregarPersona,
            #btnAgregarVehiculo,
            #btnAgregarObjeto {
                width: 100% !important;
            }
        }
    </style>
@stop

@section('js')
    <script>
        (function () {
            const personasOld = @json(old('personas', []));
            const vehiculosOld = @json(old('vehiculos', []));
            const objetosOld = @json(old('objetos', []));
            const hechoVehiculos = @json($vehiculosHechoPuesta ?? []);
            const hechoConductores = hechoVehiculos.flatMap(v => Array.isArray(v.conductores) ? v.conductores : []);

            let personaIndex = 0;
            let vehiculoIndex = 0;
            let objetoIndex = 0;

            const contenedorPersonas = document.getElementById('contenedorPersonas');
            const contenedorVehiculos = document.getElementById('contenedorVehiculos');
            const contenedorObjetos = document.getElementById('contenedorObjetos');
            const unidadSelect = document.getElementById('unidad_id');
            const numeroPreview = document.getElementById('numero_puesta_preview');
            const anioPuesta = @json(now()->year);
            const formPuesta = document.getElementById('puestaDisposicionForm');
            const motivoInput = document.getElementById('motivo');
            const motivoOtroInput = document.getElementById('motivo_otro');
            const avisoHechoTransito = document.getElementById('sv-hecho-transito-warning');
            const hechoTurnadoGroup = document.getElementById('hecho_turnado_group');
            const hechoTurnadoSelect = document.getElementById('hecho_id');
            const fechaPuestaInput = document.getElementById('fecha_puesta');
            const horaPuestaInput = document.getElementById('hora_puesta');
            const lugarPuestaInput = document.getElementById('lugar_puesta');
            const nombrePoliciaInput = document.getElementById('nombre_policia');
            const oficioInput = document.getElementById('oficio');
            const tipoPuestaInput = document.getElementById('tipo_puesta');
            const unidadFijaId = @json((int)($unidadSeleccionadaId ?? 0));
            const unidadDelegacionesId = @json((int)$unidadDelegacionesId);
            const tieneHechoVinculado = @json((bool)$hechoOrigen);

            function valor(v) {
                return String(v ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function checked(v) {
                return v ? 'checked' : '';
            }

            function normalizarMotivo(v) {
                return String(v ?? '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toUpperCase();
            }

            function motivoActual() {
                return motivoInput?.value === 'OTRO'
                    ? motivoOtroInput?.value
                    : motivoInput?.value;
            }

            function actualizarMotivoOtro() {
                const usaOtro = motivoInput?.value === 'OTRO';

                motivoOtroInput?.classList.toggle('d-none', !usaOtro);

                if (motivoOtroInput) {
                    motivoOtroInput.required = usaOtro;
                }
            }

            function unidadSeleccionadaEsDelegaciones() {
                if (tieneHechoVinculado) {
                    return false;
                }

                const unidadId = unidadSelect
                    ? Number(unidadSelect.value || 0)
                    : Number(unidadFijaId || 0);

                return unidadId === Number(unidadDelegacionesId);
            }

            function debeUsarPuestaVinculada() {
                return unidadSeleccionadaEsDelegaciones()
                    && normalizarMotivo(motivoActual()).includes('HECHO DE TRANSITO');
            }

            function actualizarAvisoHechoTransito() {
                const debeVincular = debeUsarPuestaVinculada();

                avisoHechoTransito?.classList.toggle('d-none', !debeVincular);
                hechoTurnadoGroup?.classList.toggle('d-none', !debeVincular);

                if (hechoTurnadoSelect) {
                    hechoTurnadoSelect.disabled = !debeVincular;
                    hechoTurnadoSelect.required = debeVincular;

                    if (!debeVincular) {
                        hechoTurnadoSelect.value = '';
                    }
                }
            }

            function rellenarSiVacio(input, value) {
                if (input && String(input.value || '').trim() === '' && String(value || '').trim() !== '') {
                    input.value = value;
                }
            }

            function aplicarDatosHechoTurnado() {
                const selected = hechoTurnadoSelect?.options[hechoTurnadoSelect.selectedIndex];

                if (!selected || !selected.value) {
                    return;
                }

                if (tipoPuestaInput && selected.dataset.tipoPuesta) {
                    tipoPuestaInput.value = selected.dataset.tipoPuesta;
                }

                rellenarSiVacio(fechaPuestaInput, selected.dataset.fechaPuesta);
                rellenarSiVacio(horaPuestaInput, selected.dataset.horaPuesta);
                rellenarSiVacio(lugarPuestaInput, selected.dataset.lugarPuesta);
                rellenarSiVacio(nombrePoliciaInput, selected.dataset.nombrePolicia);
                rellenarSiVacio(oficioInput, selected.dataset.oficio);
            }

            function sourceAttrs(kind, key) {
                if (!key) return '';
                return ` data-source-kind="${valor(kind)}" data-source-key="${valor(key)}"`;
            }

            function sourceBlock(kind, key) {
                return Array.from(document.querySelectorAll('.bloque-dinamico'))
                    .find(block => block.dataset.sourceKind === kind && block.dataset.sourceKey === key);
            }

            function removeSourceBlock(kind, key) {
                sourceBlock(kind, key)?.remove();
            }

            function setSourceCheckbox(kind, key, checkedValue) {
                const selector = kind === 'vehiculo' ? '.js-hecho-vehiculo' : '.js-hecho-conductor';
                const checkbox = Array.from(document.querySelectorAll(selector))
                    .find(input => input.dataset.sourceKey === key);

                if (checkbox) {
                    checkbox.checked = checkedValue;
                }
            }

            function syncSourceCheckboxesFromBlocks() {
                document.querySelectorAll('.bloque-dinamico[data-source-kind][data-source-key]').forEach(block => {
                    setSourceCheckbox(block.dataset.sourceKind, block.dataset.sourceKey, true);
                });
            }

            function agregarPersona(data = {}) {
                const i = personaIndex++;
                const sourceKey = String(data.source_key ?? '').trim();
                const html = `
                    <div class="bloque-dinamico"${sourceAttrs('conductor', sourceKey)}>
                        <input type="hidden" name="personas[${i}][source_key]" value="${valor(sourceKey)}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Persona</h5>
                            <button type="button" class="btn btn-danger btn-sm btn-eliminar-bloque">
                                <i class="fa-solid fa-trash"></i> Quitar
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Nombre Completo</label>
                                    <input type="text" name="personas[${i}][nombre_completo]" class="form-control" value="${valor(data.nombre_completo)}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Alias</label>
                                    <input type="text" name="personas[${i}][alias]" class="form-control" value="${valor(data.alias)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Edad</label>
                                    <input type="number" name="personas[${i}][edad]" class="form-control" value="${valor(data.edad)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Sexo</label>
                                    <input type="text" name="personas[${i}][sexo]" class="form-control" value="${valor(data.sexo)}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Fecha de Nacimiento</label>
                                    <input type="date" name="personas[${i}][fecha_nacimiento]" class="form-control" value="${valor(data.fecha_nacimiento)}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>CURP</label>
                                    <input type="text" name="personas[${i}][curp]" class="form-control" value="${valor(data.curp)}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>RFC</label>
                                    <input type="text" name="personas[${i}][rfc]" class="form-control" value="${valor(data.rfc)}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Calidad</label>
                                    <input type="text" name="personas[${i}][calidad]" class="form-control" value="${valor(data.calidad)}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Domicilio</label>
                                    <input type="text" name="personas[${i}][domicilio]" class="form-control" value="${valor(data.domicilio)}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Delito o Motivo</label>
                                    <input type="text" name="personas[${i}][delito_o_motivo]" class="form-control" value="${valor(data.delito_o_motivo)}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mandamiento Judicial</label>
                                    <input type="text" name="personas[${i}][mandamiento_judicial]" class="form-control" value="${valor(data.mandamiento_judicial)}">
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-center">
                                <div class="form-group mb-0">
                                    <label class="d-block">Orden de Aprehensión</label>
                                    <input type="checkbox" name="personas[${i}][orden_aprehension]" value="1" ${checked(data.orden_aprehension)}>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Observaciones</label>
                                    <input type="text" name="personas[${i}][observaciones]" class="form-control" value="${valor(data.observaciones)}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>PDF individual de uso de fuerza <span class="text-muted">(opcional)</span></label>
                                    <input type="file" name="personas[${i}][archivo_uso_fuerza]"
                                           class="form-control" accept="application/pdf">
                                    <small class="form-text text-muted">
                                        Úselo sólo si esta persona tiene un documento distinto al PDF general. Máximo {{ (int) ceil(config('pdf_compression.max_upload_kb', 51200) / 1024) }} MB.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                contenedorPersonas.insertAdjacentHTML('beforeend', html);
            }

            function agregarVehiculo(data = {}) {
                const i = vehiculoIndex++;
                const sourceKey = String(data.source_key ?? '').trim();
                const html = `
                    <div class="bloque-dinamico"${sourceAttrs('vehiculo', sourceKey)}>
                        <input type="hidden" name="vehiculos[${i}][vehiculo_id]" value="${valor(data.vehiculo_id)}">
                        <input type="hidden" name="vehiculos[${i}][source_key]" value="${valor(sourceKey)}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Vehículo</h5>
                            <button type="button" class="btn btn-danger btn-sm btn-eliminar-bloque">
                                <i class="fa-solid fa-trash"></i> Quitar
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Tipo</label>
                                    <input type="text" name="vehiculos[${i}][tipo]" class="form-control" value="${valor(data.tipo)}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Marca</label>
                                    <input type="text" name="vehiculos[${i}][marca]" class="form-control" value="${valor(data.marca)}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Línea</label>
                                    <input type="text" name="vehiculos[${i}][submarca]" class="form-control" value="${valor(data.submarca)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Modelo</label>
                                    <input type="text" name="vehiculos[${i}][modelo]" class="form-control" value="${valor(data.modelo)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Color</label>
                                    <input type="text" name="vehiculos[${i}][color]" class="form-control" value="${valor(data.color)}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Placas</label>
                                    <input type="text" name="vehiculos[${i}][placas]" class="form-control" value="${valor(data.placas)}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Serie</label>
                                    <input type="text" name="vehiculos[${i}][serie]" class="form-control" value="${valor(data.serie)}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Calidad</label>
                                    <input type="text" name="vehiculos[${i}][calidad]" class="form-control" value="${valor(data.calidad)}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Motivo Relación</label>
                                    <input type="text" name="vehiculos[${i}][motivo_relacion]" class="form-control" value="${valor(data.motivo_relacion)}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 d-flex align-items-center">
                                <div class="form-group mb-0">
                                    <label class="d-block">Con Reporte de Robo</label>
                                    <input type="checkbox" name="vehiculos[${i}][con_reporte_robo]" value="1" ${checked(data.con_reporte_robo)}>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Número Reporte Robo</label>
                                    <input type="text" name="vehiculos[${i}][numero_reporte_robo]" class="form-control" value="${valor(data.numero_reporte_robo)}">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Observaciones</label>
                                    <input type="text" name="vehiculos[${i}][observaciones]" class="form-control" value="${valor(data.observaciones)}">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                contenedorVehiculos.insertAdjacentHTML('beforeend', html);
            }

            function agregarObjeto(data = {}) {
                const i = objetoIndex++;
                const html = `
                    <div class="bloque-dinamico">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Objeto</h5>
                            <button type="button" class="btn btn-danger btn-sm btn-eliminar-bloque">
                                <i class="fa-solid fa-trash"></i> Quitar
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Tipo de Objeto</label>
                                    <input type="text" name="objetos[${i}][tipo_objeto]" class="form-control" value="${valor(data.tipo_objeto)}">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Descripción</label>
                                    <input type="text" name="objetos[${i}][descripcion]" class="form-control" value="${valor(data.descripcion)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Cantidad</label>
                                    <input type="number" step="0.01" name="objetos[${i}][cantidad]" class="form-control" value="${valor(data.cantidad)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Unidad Medida</label>
                                    <input type="text" name="objetos[${i}][unidad_medida]" class="form-control" value="${valor(data.unidad_medida)}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Cadena de Custodia</label>
                                    <input type="text" name="objetos[${i}][cadena_custodia]" class="form-control" value="${valor(data.cadena_custodia)}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Observaciones</label>
                                    <input type="text" name="objetos[${i}][observaciones]" class="form-control" value="${valor(data.observaciones)}">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                contenedorObjetos.insertAdjacentHTML('beforeend', html);
            }

            function inicializarBloques() {
                if (personasOld.length) {
                    personasOld.forEach(p => agregarPersona(p));
                }

                if (vehiculosOld.length) {
                    vehiculosOld.forEach(v => agregarVehiculo(v));
                }

                if (objetosOld.length) {
                    objetosOld.forEach(o => agregarObjeto(o));
                }
            }

            document.addEventListener('click', function (e) {
                if (e.target.closest('.btn-eliminar-bloque')) {
                    const block = e.target.closest('.bloque-dinamico');
                    const sourceKind = block?.dataset?.sourceKind;
                    const sourceKey = block?.dataset?.sourceKey;

                    block?.remove();

                    if (sourceKind && sourceKey) {
                        setSourceCheckbox(sourceKind, sourceKey, false);
                    }
                }
            });

            document.addEventListener('change', function (e) {
                if (e.target.matches('.js-hecho-vehiculo')) {
                    const key = e.target.dataset.sourceKey;
                    const payload = hechoVehiculos.find(item => item.source_key === key);

                    if (!payload) return;

                    if (e.target.checked) {
                        if (!sourceBlock('vehiculo', key)) {
                            agregarVehiculo(payload);
                        }
                    } else {
                        removeSourceBlock('vehiculo', key);
                    }
                }

                if (e.target.matches('.js-hecho-conductor')) {
                    const key = e.target.dataset.sourceKey;
                    const payload = hechoConductores.find(item => item.source_key === key);

                    if (!payload) return;

                    if (e.target.checked) {
                        if (!sourceBlock('conductor', key)) {
                            agregarPersona(payload);
                        }
                    } else {
                        removeSourceBlock('conductor', key);
                    }
                }
            });

            document.getElementById('btnAgregarPersona')?.addEventListener('click', function () {
                agregarPersona();
            });

            document.getElementById('btnAgregarVehiculo')?.addEventListener('click', function () {
                agregarVehiculo();
            });

            document.getElementById('btnAgregarObjeto')?.addEventListener('click', function () {
                agregarObjeto();
            });

            unidadSelect?.addEventListener('change', function () {
                const selected = unidadSelect.options[unidadSelect.selectedIndex];
                const siguiente = selected?.dataset?.next || '1';

                if (numeroPreview) {
                    numeroPreview.value = `${siguiente}/${anioPuesta}`;
                }
            });

            motivoInput?.addEventListener('change', function () {
                actualizarMotivoOtro();
                actualizarAvisoHechoTransito();
            });
            motivoOtroInput?.addEventListener('input', actualizarAvisoHechoTransito);
            unidadSelect?.addEventListener('change', actualizarAvisoHechoTransito);
            hechoTurnadoSelect?.addEventListener('change', aplicarDatosHechoTurnado);

            formPuesta?.addEventListener('submit', function (e) {
                if (!debeUsarPuestaVinculada() || hechoTurnadoSelect?.value) {
                    return;
                }

                e.preventDefault();

                if (typeof Swal === 'undefined') {
                    hechoTurnadoSelect?.focus();
                    return;
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Selecciona el hecho turnado',
                    text: 'Para HECHO DE TRANSITO, elige el ID del hecho TURNADO que quedará vinculado a esta puesta.',
                    confirmButtonText: 'Aceptar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        hechoTurnadoSelect?.focus();
                    }
                });
            });

            document.addEventListener('DOMContentLoaded', function () {
                inicializarBloques();
                syncSourceCheckboxesFromBlocks();
                actualizarMotivoOtro();
                actualizarAvisoHechoTransito();
                aplicarDatosHechoTurnado();
            });
        })();

        @if ($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Errores en el formulario',
                html: `
                    <ul style="text-align:left;">
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
