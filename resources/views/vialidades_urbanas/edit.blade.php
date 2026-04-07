@extends('adminlte::page')

@section('title', 'Editar Dispositivo de Vialidades Urbanas')

@section('content_header')
    <h1>Editar Dispositivo de Vialidades Urbanas</h1>
@stop

@section('content')
    @php
        $vialidadUrbanaId = $vialidadUrbana ?? $dispositivo->id;
    @endphp

    <div class="row">
        <div class="col-md-12">
            <form action="{{ route('vialidades_urbanas.update', $dispositivo->id) }}" method="POST" enctype="multipart/form-data" autocomplete="off">
                @csrf
                @method('PUT')

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Edición del dispositivo</h3>

                        <div class="card-tools">
                            <a href="{{ route('vialidades_urbanas.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-arrow-left"></i> Volver
                            </a>

                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-floppy-disk"></i> Actualizar dispositivo
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="vialidad_dispositivo_catalogo_id">Catálogo</label>
                                <select name="vialidad_dispositivo_catalogo_id" id="vialidad_dispositivo_catalogo_id" class="form-control @error('vialidad_dispositivo_catalogo_id') is-invalid @enderror" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($catalogos as $catalogo)
                                        <option value="{{ $catalogo->id }}" {{ old('vialidad_dispositivo_catalogo_id', $dispositivo->vialidad_dispositivo_catalogo_id) == $catalogo->id ? 'selected' : '' }}>
                                            {{ $catalogo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('vialidad_dispositivo_catalogo_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="fecha">Fecha</label>
                                <input type="date" name="fecha" id="fecha" class="form-control @error('fecha') is-invalid @enderror" value="{{ old('fecha', optional($dispositivo->fecha)->format('Y-m-d') ?? \Carbon\Carbon::parse($dispositivo->fecha)->format('Y-m-d')) }}" required>
                                @error('fecha')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="hora">Hora</label>
                                <input type="time" name="hora" id="hora" class="form-control @error('hora') is-invalid @enderror" value="{{ old('hora', substr((string) $dispositivo->hora, 0, 5)) }}" required>
                                @error('hora')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mt-3">
                                <label for="asunto">Asunto</label>
                                <input type="text" name="asunto" id="asunto" class="form-control @error('asunto') is-invalid @enderror" value="{{ old('asunto', $dispositivo->asunto) }}" required>
                                @error('asunto')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mt-3">
                                <label for="municipio">Municipio</label>
                                <input type="text" name="municipio" id="municipio" class="form-control @error('municipio') is-invalid @enderror" value="{{ old('municipio', $dispositivo->municipio) }}">
                                @error('municipio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mt-3">
                                <label for="lugar">Lugar</label>
                                <input type="text" name="lugar" id="lugar" class="form-control @error('lugar') is-invalid @enderror" value="{{ old('lugar', $dispositivo->lugar) }}">
                                @error('lugar')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mt-3">
                                <label for="evento">Evento</label>
                                <input type="text" name="evento" id="evento" class="form-control @error('evento') is-invalid @enderror" value="{{ old('evento', $dispositivo->evento) }}">
                                @error('evento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mt-3">
                                <label for="supervision">Supervisión</label>
                                <input type="text" name="supervision" id="supervision" class="form-control @error('supervision') is-invalid @enderror" value="{{ old('supervision', $dispositivo->supervision) }}">
                                @error('supervision')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mt-3">
                                <label for="descripcion">Descripción principal</label>
                                <textarea name="descripcion" id="descripcion" rows="5" class="form-control @error('descripcion') is-invalid @enderror">{{ old('descripcion', $dispositivo->descripcion) }}</textarea>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mt-3">
                                <label for="objetivo">Objetivo</label>
                                <textarea name="objetivo" id="objetivo" rows="3" class="form-control @error('objetivo') is-invalid @enderror">{{ old('objetivo', $dispositivo->objetivo) }}</textarea>
                                @error('objetivo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <h4 class="mb-3">Estado de fuerza</h4>

                        <div class="row">
                            <div class="col-md-2">
                                <label for="elementos">Elementos</label>
                                <input type="number" min="0" name="elementos" id="elementos" class="form-control" value="{{ old('elementos', $dispositivo->elementos ?? 0) }}">
                            </div>

                            <div class="col-md-2">
                                <label for="crp">CRP</label>
                                <input type="number" min="0" name="crp" id="crp" class="form-control" value="{{ old('crp', $dispositivo->crp ?? 0) }}">
                            </div>

                            <div class="col-md-2">
                                <label for="motopatrullas">Motopatrullas</label>
                                <input type="number" min="0" name="motopatrullas" id="motopatrullas" class="form-control" value="{{ old('motopatrullas', $dispositivo->motopatrullas ?? 0) }}">
                            </div>

                            <div class="col-md-2">
                                <label for="fenix">Fénix</label>
                                <input type="number" min="0" name="fenix" id="fenix" class="form-control" value="{{ old('fenix', $dispositivo->fenix ?? 0) }}">
                            </div>

                            <div class="col-md-2">
                                <label for="unidades_motorizadas">Unid. motorizadas</label>
                                <input type="number" min="0" name="unidades_motorizadas" id="unidades_motorizadas" class="form-control" value="{{ old('unidades_motorizadas', $dispositivo->unidades_motorizadas ?? 0) }}">
                            </div>

                            <div class="col-md-2">
                                <label for="patrullas">Patrullas</label>
                                <input type="number" min="0" name="patrullas" id="patrullas" class="form-control" value="{{ old('patrullas', $dispositivo->patrullas ?? 0) }}">
                            </div>

                            <div class="col-md-2 mt-3">
                                <label for="gruas">Grúas</label>
                                <input type="number" min="0" name="gruas" id="gruas" class="form-control" value="{{ old('gruas', $dispositivo->gruas ?? 0) }}">
                            </div>

                            <div class="col-md-2 mt-3">
                                <label for="otros_apoyos">Otros apoyos</label>
                                <input type="number" min="0" name="otros_apoyos" id="otros_apoyos" class="form-control" value="{{ old('otros_apoyos', $dispositivo->otros_apoyos ?? 0) }}">
                            </div>
                        </div>

                        <hr>

                        <h4 class="mb-3">Fotos actuales</h4>

                        <div class="row">
                            @forelse($dispositivo->fotos as $foto)
                                <div class="col-md-3 mb-4">
                                    <div class="card h-100 bg-transparent border">
                                        <img src="{{ asset('storage/' . $foto->ruta) }}" class="card-img-top" style="height: 220px; object-fit: cover; border-radius: 12px 12px 0 0;">

                                        <div class="card-body">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="radio" name="foto_portada_id" id="foto_portada_id_{{ $foto->id }}" value="{{ $foto->id }}" {{ old('foto_portada_id', optional($dispositivo->fotoPortada)->id) == $foto->id ? 'checked' : '' }}>
                                                <label class="form-check-label" for="foto_portada_id_{{ $foto->id }}">
                                                    Portada
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="eliminar_fotos[]" id="eliminar_fotos_{{ $foto->id }}" value="{{ $foto->id }}">
                                                <label class="form-check-label" for="eliminar_fotos_{{ $foto->id }}">
                                                    Eliminar
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-md-12">
                                    <div class="alert alert-secondary mb-0">
                                        No hay fotos registradas para este dispositivo.
                                    </div>
                                </div>
                            @endforelse
                        </div>

                        <hr>

                        <h4 class="mb-3">Agregar más fotos</h4>

                        <div class="row">
                            <div class="col-md-12">
                                <label for="fotos">Subir fotos</label>
                                <input type="file" name="fotos[]" id="fotos" class="form-control-file" multiple accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                            </div>
                        </div>
                    </div>

                    <div class="card-footer text-right">
                        <a href="{{ route('vialidades_urbanas.index') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-xmark"></i> Cancelar
                        </a>

                        <a href="{{ route('vialidades_urbanas.dispositivos.create', [1, $dispositivo->id]) }}" class="btn btn-info">
                            <i class="fa-solid fa-list"></i> Capturar detalles
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Actualizar dispositivo
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <style>
        input[type="date"].form-control,
        input[type="time"].form-control,
        select.form-control,
        textarea.form-control,
        input.form-control {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06) !important;
            border: 1px solid rgba(255, 255, 255, 0.15) !important;
            border-radius: 18px !important;
            box-shadow: none !important;
        }

        input[type="date"].form-control,
        input[type="time"].form-control,
        select.form-control,
        input.form-control {
            height: calc(2.25rem + 10px) !important;
            padding: .45rem 1rem !important;
            line-height: 1.5 !important;
        }

        textarea.form-control {
            min-height: 140px;
            padding: .85rem 1rem !important;
            line-height: 1.45 !important;
            resize: vertical;
        }

        input[type="date"].form-control:focus,
        input[type="time"].form-control:focus,
        select.form-control:focus,
        textarea.form-control:focus,
        input.form-control:focus {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.10) !important;
            border-color: rgba(255, 255, 255, 0.30) !important;
            box-shadow: 0 0 0 .2rem rgba(255, 255, 255, 0.10) !important;
        }

        input[type="date"].form-control::-webkit-calendar-picker-indicator,
        input[type="time"].form-control::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.9;
            cursor: pointer;
        }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 2.6rem !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='rgba(255,255,255,0.9)'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z' clip-rule='evenodd'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .9rem center;
            background-size: 16px 16px;
        }

        select.form-control option {
            color: #111827 !important;
            background-color: #ffffff !important;
            padding: 6px 10px !important;
        }

        select.form-control option[value=""] {
            color: #6b7280 !important;
        }

        .form-control.is-invalid,
        .was-validated .form-control:invalid {
            border-color: #dc3545 !important;
            padding-right: inherit !important;
            background-image: none !important;
        }

        .invalid-feedback {
            color: #ffb4b4 !important;
        }

        .card .form-group label,
        .card label {
            color: #e5e7eb;
            font-weight: 600;
            margin-bottom: .45rem;
        }

        .form-check-label,
        .form-check-input {
            cursor: pointer;
        }

        .card img {
            width: 100%;
        }
    </style>
@stop

@section('js')
    <script>
        $(function () {
            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'Revisa el formulario',
                    html: `{!! implode('<br>', $errors->all()) !!}`
                });
            @endif
        });
    </script>
@stop
