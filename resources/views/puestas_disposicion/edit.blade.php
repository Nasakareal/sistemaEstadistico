@extends('adminlte::page')

@section('title', 'Editar Puesta a Disposición')

@section('content_header')
    <h1>Editar Puesta a Disposición</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Actualice los Datos</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('puestas_disposicion.update', $puestaDisposicion->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="numero_puesta">Número de Puesta</label>
                                    <input type="number" name="numero_puesta" id="numero_puesta"
                                           class="form-control @error('numero_puesta') is-invalid @enderror"
                                           value="{{ old('numero_puesta', $puestaDisposicion->numero_puesta) }}" required>
                                    @error('numero_puesta')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="anio">Año</label>
                                    <input type="number" name="anio" id="anio"
                                           class="form-control @error('anio') is-invalid @enderror"
                                           value="{{ old('anio', $puestaDisposicion->anio) }}" required>
                                    @error('anio')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_puesta">Tipo de Puesta</label>
                                    <select name="tipo_puesta" id="tipo_puesta"
                                            class="form-control @error('tipo_puesta') is-invalid @enderror" required>
                                        <option value="" disabled {{ old('tipo_puesta', $puestaDisposicion->tipo_puesta) ? '' : 'selected' }}>Seleccione una opción</option>
                                        <option value="PERSONA" {{ old('tipo_puesta', $puestaDisposicion->tipo_puesta) == 'PERSONA' ? 'selected' : '' }}>PERSONA</option>
                                        <option value="VEHICULO" {{ old('tipo_puesta', $puestaDisposicion->tipo_puesta) == 'VEHICULO' ? 'selected' : '' }}>VEHÍCULO</option>
                                        <option value="OBJETO" {{ old('tipo_puesta', $puestaDisposicion->tipo_puesta) == 'OBJETO' ? 'selected' : '' }}>OBJETO</option>
                                        <option value="MIXTA" {{ old('tipo_puesta', $puestaDisposicion->tipo_puesta) == 'MIXTA' ? 'selected' : '' }}>MIXTA</option>
                                    </select>
                                    @error('tipo_puesta')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="motivo">Motivo</label>
                                    <input type="text" name="motivo" id="motivo"
                                           class="form-control @error('motivo') is-invalid @enderror"
                                           value="{{ old('motivo', $puestaDisposicion->motivo) }}" required>
                                    @error('motivo')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="estatus">Estatus</label>
                                    <input type="text" name="estatus" id="estatus"
                                           class="form-control @error('estatus') is-invalid @enderror"
                                           value="{{ old('estatus', $puestaDisposicion->estatus) }}">
                                    @error('estatus')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fecha_puesta">Fecha de Puesta</label>
                                    <input type="date" name="fecha_puesta" id="fecha_puesta"
                                           class="form-control @error('fecha_puesta') is-invalid @enderror"
                                           value="{{ old('fecha_puesta', optional($puestaDisposicion->fecha_puesta)->format('Y-m-d') ?? $puestaDisposicion->fecha_puesta) }}" required>
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
                                           value="{{ old('hora_puesta', $puestaDisposicion->hora_puesta ? \Carbon\Carbon::parse($puestaDisposicion->hora_puesta)->format('H:i') : '') }}">
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
                                           value="{{ old('lugar_puesta', $puestaDisposicion->lugar_puesta) }}">
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
                                           value="{{ old('nombre_policia', $puestaDisposicion->nombre_policia) }}" required>
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
                                           value="{{ old('nombre_mp', $puestaDisposicion->nombre_mp) }}">
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
                                           value="{{ old('autoridad_receptora', $puestaDisposicion->autoridad_receptora) }}">
                                    @error('autoridad_receptora')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area">Área</label>
                                    <input type="text" name="area" id="area"
                                           class="form-control @error('area') is-invalid @enderror"
                                           value="{{ old('area', $puestaDisposicion->area) }}">
                                    @error('area')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="carpeta_investigacion">Carpeta de Investigación</label>
                                    <input type="text" name="carpeta_investigacion" id="carpeta_investigacion"
                                           class="form-control @error('carpeta_investigacion') is-invalid @enderror"
                                           value="{{ old('carpeta_investigacion', $puestaDisposicion->carpeta_investigacion) }}">
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
                                           value="{{ old('oficio', $puestaDisposicion->oficio) }}">
                                    @error('oficio')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        @if(auth()->user()->hasRole('Superadmin'))
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="unidad_id">Unidad</label>
                                        <input type="number" name="unidad_id" id="unidad_id"
                                               class="form-control @error('unidad_id') is-invalid @enderror"
                                               value="{{ old('unidad_id', $puestaDisposicion->unidad_id) }}">
                                        @error('unidad_id')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="delegacion_id">Delegación</label>
                                        <input type="number" name="delegacion_id" id="delegacion_id"
                                               class="form-control @error('delegacion_id') is-invalid @enderror"
                                               value="{{ old('delegacion_id', $puestaDisposicion->delegacion_id) }}">
                                        @error('delegacion_id')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="destacamento_id">Destacamento</label>
                                        <input type="number" name="destacamento_id" id="destacamento_id"
                                               class="form-control @error('destacamento_id') is-invalid @enderror"
                                               value="{{ old('destacamento_id', $puestaDisposicion->destacamento_id) }}">
                                        @error('destacamento_id')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="archivo_puesta">Archivo PDF</label>
                                    <input type="file" name="archivo_puesta" id="archivo_puesta"
                                           class="form-control @error('archivo_puesta') is-invalid @enderror"
                                           accept="application/pdf">
                                    @error('archivo_puesta')
                                        <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>

                                @if($puestaDisposicion->archivo_puesta)
                                    <div class="mb-3">
                                        <a href="{{ asset('storage/' . $puestaDisposicion->archivo_puesta) }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="fa-solid fa-file-pdf"></i> Ver archivo actual
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="narrativa">Narrativa</label>
                                    <textarea name="narrativa" id="narrativa" rows="4"
                                              class="form-control @error('narrativa') is-invalid @enderror">{{ old('narrativa', $puestaDisposicion->narrativa) }}</textarea>
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
                                              class="form-control @error('observaciones') is-invalid @enderror">{{ old('observaciones', $puestaDisposicion->observaciones) }}</textarea>
                                    @error('observaciones')
                                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

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
                                        <i class="fa-solid fa-check"></i> Actualizar
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
        .content-wrapper .card.card-outline.card-secondary {
            border-radius: 18px !important;
            overflow: hidden !important;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(6px);
        }

        .content-wrapper .card.card-outline.card-info > .card-header,
        .content-wrapper .card.card-outline.card-warning > .card-header,
        .content-wrapper .card.card-outline.card-secondary > .card-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            padding: 14px 20px !important;
        }

        .content-wrapper .card.card-outline.card-info > .card-header .card-title,
        .content-wrapper .card.card-outline.card-warning > .card-header .card-title,
        .content-wrapper .card.card-outline.card-secondary > .card-header .card-title {
            color: #ffffff !important;
            font-size: 1.15rem !important;
            font-weight: 700 !important;
            margin: 0 !important;
        }

        .content-wrapper .card.card-outline.card-info > .card-body,
        .content-wrapper .card.card-outline.card-warning > .card-body,
        .content-wrapper .card.card-outline.card-secondary > .card-body {
            background: transparent !important;
            padding: 24px !important;
        }

        .bloque-dinamico {
            background: #ffffff !important;
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

        .bloque-dinamico input[type="date"] {
            color-scheme: light !important;
        }

        .bloque-dinamico input[type="date"]::-webkit-datetime-edit,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-fields-wrapper,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-text,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-month-field,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-day-field,
        .bloque-dinamico input[type="date"]::-webkit-datetime-edit-year-field {
            color: #1f2937 !important;
            -webkit-text-fill-color: #1f2937 !important;
        }

        .bloque-dinamico input[type="date"]:focus::-webkit-datetime-edit,
        .bloque-dinamico input[type="date"]:focus::-webkit-datetime-edit-fields-wrapper,
        .bloque-dinamico input[type="date"]:focus::-webkit-datetime-edit-text,
        .bloque-dinamico input[type="date"]:focus::-webkit-datetime-edit-month-field,
        .bloque-dinamico input[type="date"]:focus::-webkit-datetime-edit-day-field,
        .bloque-dinamico input[type="date"]:focus::-webkit-datetime-edit-year-field {
            color: #111827 !important;
            -webkit-text-fill-color: #111827 !important;
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

        .card-header .btn-success,
        .card-header .btn-success.btn-sm {
            background: #10b981 !important;
            border-color: #10b981 !important;
            color: #ffffff !important;
            border-radius: 16px !important;
            font-weight: 700 !important;
            padding: 10px 18px !important;
            box-shadow: none !important;
        }

        .card-header .btn-success:hover,
        .card-header .btn-success:focus {
            background: #059669 !important;
            border-color: #059669 !important;
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
            .card-header .btn-success {
                width: 100% !important;
            }
        }
    </style>
@stop

@section('js')
    @php
        $personasJs = old('personas', $puestaDisposicion->personas->map(function ($p) {
            return [
                'nombre_completo' => $p->nombre_completo,
                'alias' => $p->alias,
                'edad' => $p->edad,
                'sexo' => $p->sexo,
                'fecha_nacimiento' => $p->fecha_nacimiento ? \Carbon\Carbon::parse($p->fecha_nacimiento)->format('Y-m-d') : '',
                'curp' => $p->curp,
                'rfc' => $p->rfc,
                'domicilio' => $p->domicilio,
                'calidad' => $p->calidad,
                'delito_o_motivo' => $p->delito_o_motivo,
                'orden_aprehension' => $p->orden_aprehension,
                'mandamiento_judicial' => $p->mandamiento_judicial,
                'observaciones' => $p->observaciones,
            ];
        })->values()->all());

        $vehiculosJs = old('vehiculos', $puestaDisposicion->vehiculos->map(function ($v) {
            return [
                'vehiculo_id' => $v->vehiculo_id,
                'tipo' => $v->tipo,
                'marca' => $v->marca,
                'submarca' => $v->submarca,
                'modelo' => $v->modelo,
                'color' => $v->color,
                'placas' => $v->placas,
                'serie' => $v->serie,
                'calidad' => $v->calidad,
                'motivo_relacion' => $v->motivo_relacion,
                'con_reporte_robo' => $v->con_reporte_robo,
                'numero_reporte_robo' => $v->numero_reporte_robo,
                'observaciones' => $v->observaciones,
            ];
        })->values()->all());

        $objetosJs = old('objetos', $puestaDisposicion->objetos->map(function ($o) {
            return [
                'tipo_objeto' => $o->tipo_objeto,
                'descripcion' => $o->descripcion,
                'cantidad' => $o->cantidad,
                'unidad_medida' => $o->unidad_medida,
                'cadena_custodia' => $o->cadena_custodia,
                'observaciones' => $o->observaciones,
            ];
        })->values()->all());
    @endphp

    <script>
        (function () {
            const personasOld = @json($personasJs);
            const vehiculosOld = @json($vehiculosJs);
            const objetosOld = @json($objetosJs);

            let personaIndex = 0;
            let vehiculoIndex = 0;
            let objetoIndex = 0;

            const contenedorPersonas = document.getElementById('contenedorPersonas');
            const contenedorVehiculos = document.getElementById('contenedorVehiculos');
            const contenedorObjetos = document.getElementById('contenedorObjetos');

            function valor(v) {
                return v ?? '';
            }

            function checked(v) {
                return v ? 'checked' : '';
            }

            function agregarPersona(data = {}) {
                const i = personaIndex++;
                const html = `
                    <div class="bloque-dinamico">
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
                        </div>
                    </div>
                `;
                contenedorPersonas.insertAdjacentHTML('beforeend', html);
            }

            function agregarVehiculo(data = {}) {
                const i = vehiculoIndex++;
                const html = `
                    <div class="bloque-dinamico">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Vehículo</h5>
                            <button type="button" class="btn btn-danger btn-sm btn-eliminar-bloque">
                                <i class="fa-solid fa-trash"></i> Quitar
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Vehículo ID</label>
                                    <input type="number" name="vehiculos[${i}][vehiculo_id]" class="form-control" value="${valor(data.vehiculo_id)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Tipo</label>
                                    <input type="text" name="vehiculos[${i}][tipo]" class="form-control" value="${valor(data.tipo)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Marca</label>
                                    <input type="text" name="vehiculos[${i}][marca]" class="form-control" value="${valor(data.marca)}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Submarca</label>
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
                    e.target.closest('.bloque-dinamico').remove();
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

            document.addEventListener('DOMContentLoaded', function () {
                inicializarBloques();
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
