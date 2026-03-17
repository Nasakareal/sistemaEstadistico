@extends('adminlte::page')

@section('title', 'Crear Operativo Guardianes del Camino')

@section('content_header')
    <h1>Crear Operativo Guardianes del Camino</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Llene los datos del operativo</h3>
                    <div class="card-tools">
                        <a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <form action="{{ route('guardianes_camino.store') }}" method="POST" autocomplete="off">
                        @csrf

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Operativo</label>
                                    <input type="text" class="form-control" value="{{ $catalogo->nombre ?? 'Guardianes del Camino' }}" readonly>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fecha">Fecha</label>
                                    <input
                                        type="date"
                                        name="fecha"
                                        id="fecha"
                                        class="form-control @error('fecha') is-invalid @enderror"
                                        value="{{ old('fecha', now('America/Mexico_City')->format('Y-m-d')) }}"
                                        required
                                    >
                                    @error('fecha')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="hora">Hora</label>
                                    <input
                                        type="time"
                                        name="hora"
                                        id="hora"
                                        class="form-control @error('hora') is-invalid @enderror"
                                        value="{{ old('hora', now('America/Mexico_City')->format('H:i')) }}"
                                    >
                                    @error('hora')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unidad_org_id">Unidad</label>
                                    <select name="unidad_org_id" id="unidad_org_id" class="form-control @error('unidad_org_id') is-invalid @enderror" required>
                                        <option value="">Seleccione una unidad</option>
                                        @foreach(\App\Models\Unidad::orderBy('nombre')->get() as $unidad)
                                            <option value="{{ $unidad->id }}" {{ old('unidad_org_id', 4) == $unidad->id ? 'selected' : '' }}>
                                                {{ $unidad->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unidad_org_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="delegacion_id">Delegación</label>
                                    <select name="delegacion_id" id="delegacion_id" class="form-control @error('delegacion_id') is-invalid @enderror">
                                        <option value="">Seleccione una delegación</option>
                                        @foreach(\App\Models\Delegacion::orderBy('nombre')->get() as $delegacion)
                                            <option value="{{ $delegacion->id }}" {{ old('delegacion_id') == $delegacion->id ? 'selected' : '' }}>
                                                {{ $delegacion->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('delegacion_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="destacamento_id">Destacamento</label>
                                    <select name="destacamento_id" id="destacamento_id" class="form-control @error('destacamento_id') is-invalid @enderror">
                                        <option value="">Seleccione un destacamento</option>
                                        @foreach(\App\Models\Destacamento::orderBy('nombre')->get() as $destacamento)
                                            <option value="{{ $destacamento->id }}" {{ old('destacamento_id') == $destacamento->id ? 'selected' : '' }}>
                                                {{ $destacamento->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('destacamento_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="lugar">Lugar</label>
                                    <input
                                        type="text"
                                        name="lugar"
                                        id="lugar"
                                        class="form-control @error('lugar') is-invalid @enderror"
                                        value="{{ old('lugar') }}"
                                        placeholder="Ejemplo: AUTOPISTA SIGLO XXI KM 120"
                                    >
                                    @error('lugar')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea
                                        name="descripcion"
                                        id="descripcion"
                                        rows="3"
                                        class="form-control @error('descripcion') is-invalid @enderror"
                                        placeholder="Descripción general del operativo"
                                    >{{ old('descripcion', 'Operativo Guardianes del Camino') }}</textarea>
                                    @error('descripcion')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="observaciones">Observaciones</label>
                                    <textarea
                                        name="observaciones"
                                        id="observaciones"
                                        rows="3"
                                        class="form-control @error('observaciones') is-invalid @enderror"
                                        placeholder="Observaciones generales"
                                    >{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-floppy-disk"></i> Guardar Operativo
                                </button>

                                <a href="{{ route('guardianes_camino.index') }}" class="btn btn-secondary">
                                    <i class="fa-solid fa-xmark"></i> Cancelar
                                </a>
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
        .form-control[readonly] {
            background-color: #e9ecef;
            opacity: 1;
        }

        input[type="date"].form-control,
        input[type="time"].form-control,
        select.form-control,
        textarea.form-control {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        input[type="date"].form-control:focus,
        input[type="time"].form-control:focus,
        select.form-control:focus,
        textarea.form-control:focus {
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
    </style>
@stop

@section('js')
    <script>
        @if ($errors->any())
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: 'Hay errores en el formulario',
                html: `{!! implode('<br>', $errors->all()) !!}`,
                showConfirmButton: true
            });
        @endif
    </script>
@stop
